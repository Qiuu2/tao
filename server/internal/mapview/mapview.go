// Package mapview 实现「资源管理 → 地图」：一张校园平面图当底图，
// 终端按实际位置摆在图上。
//
// # 为什么要有这一页
//
// 终端列表回答的是「哪一台怎么样」—— 一行 20 列，适合按字段扫。
// 巡检时真正想知道的是「掉线那台在哪」，那是列表答不了的，
// 得拿着终端名字去脑子里翻一遍楼栋。地图把这个问题变成看一眼。
//
// # 坐标为什么是百分比不是像素
//
// 底图在界面上按容器大小缩放显示。存像素的话，换个屏幕分辨率、
// 收起侧边栏，终端就全偏了。存百分比（0~100）之后：
//
//   - 显示多大都对得上；
//   - 换底图（同一张图换清晰版、换比例接近的新版）不用重摆。
//
// ⚠ terminal 表里确实有 longitude / latitude 两列，但那是**经纬度**，
// 给真实地理坐标用；我们的底图是一张图片，两者不是一回事。
// 而且那两列是后台 C 服务的地盘，不该由 Web 去写。
//
// # 建了哪两张表
//
//	map_image     底图（名称、文件、原始宽高、排序）
//	map_terminal  终端摆在哪张图的哪个位置
//
// 建表脚本 db/map_tables.sql，由管理员执行一次 —— htweb 的运行账号没有 DDL。
// 两张表都已加进恢复出厂的清空清单（serverparam/factory.go 的 clearTables）。
package mapview

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"image"
	"io"
	"os"
	"path/filepath"
	"strings"
	"time"

	// 认这两种图片头就够了：上传口只放 png / jpg。
	// 注册进 image 包之后 image.DecodeConfig 才认得出来。
	_ "image/jpeg"
	_ "image/png"

	"htweb/internal/auth"
)

var (
	// ErrNotFound 底图不存在。
	ErrNotFound = errors.New("底图不存在")
	// ErrNoPermission 无权操作。
	ErrNoPermission = errors.New("无权操作")
	// ErrBadImage 不是能用的图片。
	ErrBadImage = errors.New("只支持 png / jpg 图片")
	// ErrTableMissing 建表脚本还没执行。
	ErrTableMissing = errors.New("地图功能的两张表还没建，请先执行 db/map_tables.sql")
)

// maxImageBytes 底图大小上限。校园平面图通常几百 KB 到几 MB，
// 20MB 足够，又不至于让人把一张没压过的扫描件直接丢进来。
const maxImageBytes = 20 << 20

// mapDir 是底图在媒体根目录下的相对位置。与媒体分开放，
// 免得「文件管理」里冒出一堆看不懂的图片。
const mapDir = "backup/mapdata"

// Service 是地图模块的服务。
type Service struct {
	db   *sql.DB
	root string // 媒体根目录，底图放在它下面的 backup/mapdata
}

func New(db *sql.DB, mediaRoot string) *Service {
	return &Service{db: db, root: mediaRoot}
}

// Map 是一张底图。
type Map struct {
	ID     int64  `json:"id"`
	Name   string `json:"name"`
	Width  int    `json:"width"`
	Height int    `json:"height"`
	Sort   int    `json:"sort"`
	// HasImage 为 false 时界面画占位底图（见包注释）。
	HasImage bool `json:"hasImage"`
	// ImageURL 带 id 的取图地址；HasImage 为 false 时为空。
	ImageURL string `json:"imageUrl,omitempty"`
	// Terminals 是这张图上已经摆了几台终端。
	Terminals  int    `json:"terminals"`
	CreateTime string `json:"createTime,omitempty"`
}

// Placement 是一台摆在图上的终端，带够界面显示的状态。
type Placement struct {
	TerminalID int64   `json:"terminalId"`
	X          float64 `json:"x"`
	Y          float64 `json:"y"`
	Name       string  `json:"terminalname"`
	IP         string  `json:"ip"`
	TypeName   string  `json:"typeName"`
	Volume     int     `json:"volume"`
	NetState   int     `json:"netstate"`
	DeviceSt   int     `json:"devicestate"`
	TaskState  int     `json:"taskstate"`
	GroupName  string  `json:"groupName"`
	// Missing 表示这条摆放记录指向的终端已经被删了。
	// 不悄悄藏起来：图上留个灰点，让人知道这儿原来有台设备。
	Missing bool `json:"missing"`
}

// isTableMissing 认「表不存在」这一种错误。
//
// 建表脚本要管理员单独执行，现场很可能先升级了程序、忘了跑脚本。
// 那时候界面上应该说「去跑 db/map_tables.sql」，而不是甩一个
// 「Error 1146: Table 'audioserver.map_image' doesn't exist」。
func isTableMissing(err error) bool {
	return err != nil && strings.Contains(err.Error(), "1146")
}

// visibleCond 把终端收敛到当前用户看得见的范围，与终端模块口径一致：
// 管理员看全部，普通用户只看 userterminal 绑给他的那些。
func visibleCond(u *auth.User) (string, []interface{}) {
	if u.IsAdmin {
		return "", nil
	}
	return " AND t.id IN (SELECT terminalid FROM userterminal WHERE userid = ?)", []interface{}{u.ID}
}

// ---------- 底图 ----------

// List 列出全部底图，按 sort、id 排。排第一的那张就是界面打开时的默认底图。
func (s *Service) List(ctx context.Context) ([]Map, error) {
	rs, err := s.db.QueryContext(ctx, `
		SELECT m.id, COALESCE(m.name,''), COALESCE(m.filename,''),
		       COALESCE(m.width,0), COALESCE(m.height,0), COALESCE(m.sort,0),
		       COALESCE(DATE_FORMAT(m.createtime,'%Y-%m-%d %H:%i'),''),
		       (SELECT COUNT(*) FROM map_terminal mt WHERE mt.mapid = m.id)
		FROM map_image m ORDER BY m.sort, m.id`)
	if err != nil {
		if isTableMissing(err) {
			return nil, ErrTableMissing
		}
		return nil, fmt.Errorf("查询底图: %w", err)
	}
	defer rs.Close()

	out := []Map{}
	for rs.Next() {
		var it Map
		var file string
		if err := rs.Scan(&it.ID, &it.Name, &file, &it.Width, &it.Height, &it.Sort,
			&it.CreateTime, &it.Terminals); err != nil {
			return nil, err
		}
		it.HasImage = file != ""
		if it.HasImage {
			it.ImageURL = fmt.Sprintf("/api/maps/%d/image", it.ID)
		}
		out = append(out, it)
	}
	return out, rs.Err()
}

// Create 新建一张底图记录（先不带图片，随后用 SaveImage 传）。
func (s *Service) Create(ctx context.Context, u *auth.User, name string) (int64, error) {
	name = strings.TrimSpace(name)
	if name == "" {
		return 0, fmt.Errorf("底图名称不能为空")
	}
	if len([]rune(name)) > 32 {
		return 0, fmt.Errorf("底图名称最多 32 个字")
	}
	// 新的排在最后
	var maxSort int
	_ = s.db.QueryRowContext(ctx, `SELECT COALESCE(MAX(sort),0) FROM map_image`).Scan(&maxSort)

	r, err := s.db.ExecContext(ctx, `
		INSERT INTO map_image (name, filename, width, height, sort, userid, createtime)
		VALUES (?, '', 0, 0, ?, ?, NOW())`, name, maxSort+1, u.ID)
	if err != nil {
		if isTableMissing(err) {
			return 0, ErrTableMissing
		}
		return 0, fmt.Errorf("新建底图: %w", err)
	}
	id, _ := r.LastInsertId()
	return id, nil
}

// Rename 改底图名称。
func (s *Service) Rename(ctx context.Context, id int64, name string) error {
	name = strings.TrimSpace(name)
	if name == "" {
		return fmt.Errorf("底图名称不能为空")
	}
	if len([]rune(name)) > 32 {
		return fmt.Errorf("底图名称最多 32 个字")
	}
	r, err := s.db.ExecContext(ctx, `UPDATE map_image SET name = ? WHERE id = ?`, name, id)
	if err != nil {
		return fmt.Errorf("重命名底图: %w", err)
	}
	if n, _ := r.RowsAffected(); n == 0 {
		// 名字没变时 RowsAffected 也是 0，所以再确认一次记录在不在
		var exists int
		if err := s.db.QueryRowContext(ctx, `SELECT 1 FROM map_image WHERE id = ?`, id).Scan(&exists); err != nil {
			return ErrNotFound
		}
	}
	return nil
}

// Delete 删掉一张底图：记录、图上的终端摆放、磁盘文件。
//
// ⚠ 顺序是「先删库再删文件」。反过来的话，库删失败就留下一条指向空文件的记录，
// 界面上是一张永远加载不出来的图。文件删失败只是留个孤儿文件，无害。
func (s *Service) Delete(ctx context.Context, id int64) error {
	var file string
	err := s.db.QueryRowContext(ctx,
		`SELECT COALESCE(filename,'') FROM map_image WHERE id = ?`, id).Scan(&file)
	if errors.Is(err, sql.ErrNoRows) {
		return ErrNotFound
	}
	if err != nil {
		return fmt.Errorf("查询底图: %w", err)
	}

	if _, err := s.db.ExecContext(ctx, `DELETE FROM map_terminal WHERE mapid = ?`, id); err != nil {
		return fmt.Errorf("清理底图上的终端: %w", err)
	}
	if _, err := s.db.ExecContext(ctx, `DELETE FROM map_image WHERE id = ?`, id); err != nil {
		return fmt.Errorf("删除底图: %w", err)
	}
	if file != "" {
		if p, err := s.PhysicalPath(file); err == nil {
			_ = os.Remove(p)
		}
	}
	return nil
}

// SaveImage 给一张底图换图片。
//
// 认文件头而不是只看扩展名：一个改名成 .png 的文本文件照样能传上来，
// 等到界面上显示不出来才发现，那时候已经不知道是哪一步错了。
func (s *Service) SaveImage(ctx context.Context, id int64, origName string, src io.Reader, declared int64) error {
	if declared > 0 && declared > maxImageBytes {
		return fmt.Errorf("图片超过 %dMB 上限", maxImageBytes>>20)
	}
	ext := strings.ToLower(strings.TrimPrefix(filepath.Ext(origName), "."))
	if ext == "jpeg" {
		ext = "jpg"
	}
	if ext != "png" && ext != "jpg" {
		return ErrBadImage
	}

	var oldFile string
	err := s.db.QueryRowContext(ctx,
		`SELECT COALESCE(filename,'') FROM map_image WHERE id = ?`, id).Scan(&oldFile)
	if errors.Is(err, sql.ErrNoRows) {
		return ErrNotFound
	}
	if err != nil {
		return fmt.Errorf("查询底图: %w", err)
	}

	dir := filepath.Join(s.root, mapDir)
	if err := os.MkdirAll(dir, 0o755); err != nil {
		return fmt.Errorf("创建底图目录: %w", err)
	}
	rel := fmt.Sprintf("/%s/%d.%s", mapDir, time.Now().UnixNano(), ext)
	abs := filepath.Join(s.root, filepath.FromSlash(strings.TrimPrefix(rel, "/")))

	f, err := os.Create(abs)
	if err != nil {
		return fmt.Errorf("写入底图: %w", err)
	}
	// ⚠ 多写 1 字节的上限：正好等于上限时说明可能还没读完，按超限处理。
	n, cerr := io.Copy(f, io.LimitReader(src, maxImageBytes+1))
	closeErr := f.Close()
	if cerr != nil || closeErr != nil {
		_ = os.Remove(abs)
		return fmt.Errorf("写入底图: %w", errors.Join(cerr, closeErr))
	}
	if n > maxImageBytes {
		_ = os.Remove(abs)
		return fmt.Errorf("图片超过 %dMB 上限", maxImageBytes>>20)
	}

	w, h, err := imageSize(abs)
	if err != nil {
		_ = os.Remove(abs)
		return ErrBadImage
	}

	if _, err := s.db.ExecContext(ctx,
		`UPDATE map_image SET filename = ?, width = ?, height = ? WHERE id = ?`,
		rel, w, h, id); err != nil {
		_ = os.Remove(abs)
		return fmt.Errorf("更新底图记录: %w", err)
	}
	// 库更新成功之后才删旧文件，顺序理由同 Delete
	if oldFile != "" && oldFile != rel {
		if p, err := s.PhysicalPath(oldFile); err == nil {
			_ = os.Remove(p)
		}
	}
	return nil
}

// imageSize 读图片头拿原始宽高。只读头，不解码整张图 —— 一张 20MB 的图
// 完整解码要几十 MB 内存，而我们只想要两个数字。
func imageSize(path string) (int, int, error) {
	f, err := os.Open(path)
	if err != nil {
		return 0, 0, err
	}
	defer f.Close()
	cfg, _, err := image.DecodeConfig(f)
	if err != nil {
		return 0, 0, err
	}
	if cfg.Width <= 0 || cfg.Height <= 0 {
		return 0, 0, ErrBadImage
	}
	return cfg.Width, cfg.Height, nil
}

// PhysicalPath 把库里存的相对路径还原成磁盘路径，并挡住越界。
//
// filename 是我们自己写进去的，但这一层仍然要查：库可以被别的程序改，
// 一条 "../../etc/passwd" 就能把任意文件当图片读出去。
func (s *Service) PhysicalPath(rel string) (string, error) {
	rel = strings.TrimPrefix(strings.TrimSpace(rel), "/")
	if rel == "" {
		return "", ErrNotFound
	}
	abs := filepath.Join(s.root, filepath.FromSlash(rel))
	base, err := filepath.Abs(filepath.Join(s.root, mapDir))
	if err != nil {
		return "", err
	}
	got, err := filepath.Abs(abs)
	if err != nil {
		return "", err
	}
	if got != base && !strings.HasPrefix(got, base+string(os.PathSeparator)) {
		return "", fmt.Errorf("底图路径越界")
	}
	if st, err := os.Stat(got); err != nil || st.IsDir() {
		return "", ErrNotFound
	}
	return got, nil
}

// ImagePath 取某张底图的磁盘路径，供 HTTP 层下发。
func (s *Service) ImagePath(ctx context.Context, id int64) (string, error) {
	var file string
	err := s.db.QueryRowContext(ctx,
		`SELECT COALESCE(filename,'') FROM map_image WHERE id = ?`, id).Scan(&file)
	if errors.Is(err, sql.ErrNoRows) {
		return "", ErrNotFound
	}
	if err != nil {
		return "", fmt.Errorf("查询底图: %w", err)
	}
	if file == "" {
		return "", ErrNotFound
	}
	return s.PhysicalPath(file)
}

// ---------- 终端摆放 ----------

// Placements 列出一张图上已经摆好的终端。
//
// LEFT JOIN：终端被删之后摆放记录还在，内连接会让这些行静默消失，
// 图上就少一个点而没人知道为什么。这里留着并标 Missing。
func (s *Service) Placements(ctx context.Context, u *auth.User, mapID int64) ([]Placement, error) {
	cond, args := visibleCond(u)
	q := `
		SELECT mt.terminalid, mt.x, mt.y,
		       t.id IS NOT NULL, COALESCE(t.terminalname,''), COALESCE(t.ip,''),
		       COALESCE(tt.name,''), COALESCE(t.volume,0),
		       COALESCE(t.netstate,0), COALESCE(t.devicestate,0), COALESCE(t.taskstate,0),
		       COALESCE((SELECT sps.name FROM terminalofgroup tog
		                 JOIN serverplaystream sps ON sps.streamid = tog.groupid
		                 WHERE tog.terminalid = t.id ORDER BY tog.id LIMIT 1), '')
		FROM map_terminal mt
		LEFT JOIN terminal t ON t.id = mt.terminalid
		LEFT JOIN terminaltype tt ON tt.id = t.typeid
		WHERE mt.mapid = ?`
	// 可见范围只收敛**还存在**的终端；已删除的那条留着标灰，
	// 否则普通用户会看到一个自己既看不到也删不掉的幽灵点。
	if cond != "" {
		q += ` AND (t.id IS NULL OR t.id IN (SELECT terminalid FROM userterminal WHERE userid = ?))`
		args = []interface{}{u.ID}
	}
	q += ` ORDER BY mt.id`

	rs, err := s.db.QueryContext(ctx, q, append([]interface{}{mapID}, args...)...)
	if err != nil {
		if isTableMissing(err) {
			return nil, ErrTableMissing
		}
		return nil, fmt.Errorf("查询图上终端: %w", err)
	}
	defer rs.Close()

	out := []Placement{}
	for rs.Next() {
		var it Placement
		var ok bool
		if err := rs.Scan(&it.TerminalID, &it.X, &it.Y, &ok, &it.Name, &it.IP,
			&it.TypeName, &it.Volume, &it.NetState, &it.DeviceSt, &it.TaskState,
			&it.GroupName); err != nil {
			return nil, err
		}
		it.Missing = !ok
		out = append(out, it)
	}
	return out, rs.Err()
}

// Place 把一台终端摆到图上，或挪动已经摆上去的那台。
//
// 同一张图上同一台终端只允许一条记录（表上有唯一键），所以用 upsert：
// 拖动一次就是一次 Place，不用界面区分「新增」还是「移动」。
func (s *Service) Place(ctx context.Context, u *auth.User, mapID, terminalID int64, x, y float64) error {
	if x < 0 {
		x = 0
	}
	if x > 100 {
		x = 100
	}
	if y < 0 {
		y = 0
	}
	if y > 100 {
		y = 100
	}

	var exists int
	if err := s.db.QueryRowContext(ctx, `SELECT 1 FROM map_image WHERE id = ?`, mapID).Scan(&exists); err != nil {
		if isTableMissing(err) {
			return ErrTableMissing
		}
		return ErrNotFound
	}

	// 终端必须存在，且当前用户看得见 —— 否则可以把别人的终端摆到自己图上，
	// 等于绕开 userterminal 看到名字、IP 和在线状态。
	cond, args := visibleCond(u)
	var tExists int
	err := s.db.QueryRowContext(ctx,
		`SELECT 1 FROM terminal t WHERE t.id = ?`+cond,
		append([]interface{}{terminalID}, args...)...).Scan(&tExists)
	if errors.Is(err, sql.ErrNoRows) {
		return ErrNoPermission
	}
	if err != nil {
		return fmt.Errorf("校验终端: %w", err)
	}

	_, err = s.db.ExecContext(ctx, `
		INSERT INTO map_terminal (mapid, terminalid, x, y, createtime)
		VALUES (?, ?, ?, ?, NOW())
		ON DUPLICATE KEY UPDATE x = VALUES(x), y = VALUES(y)`,
		mapID, terminalID, x, y)
	if err != nil {
		return fmt.Errorf("摆放终端: %w", err)
	}
	return nil
}

// Remove 把终端从图上拿掉。只删摆放记录，终端本身一根毛都不动。
func (s *Service) Remove(ctx context.Context, mapID int64, terminalIDs []int64) (int, error) {
	if len(terminalIDs) == 0 {
		return 0, nil
	}
	ph, args := placeholders(terminalIDs)
	r, err := s.db.ExecContext(ctx,
		`DELETE FROM map_terminal WHERE mapid = ? AND terminalid IN (`+ph+`)`,
		append([]interface{}{mapID}, args...)...)
	if err != nil {
		return 0, fmt.Errorf("移除图上终端: %w", err)
	}
	n, _ := r.RowsAffected()
	return int(n), nil
}

// placeholders 拼 IN (?,?,?) —— 与 alarm / bell / dashboard 里那几份同型。
// 没抽成公共函数是因为它们各自只有这一处用，抽出去反而要多读一个包。
func placeholders(ids []int64) (string, []interface{}) {
	if len(ids) == 0 {
		return "NULL", nil
	}
	args := make([]interface{}, len(ids))
	for i, id := range ids {
		args[i] = id
	}
	return strings.TrimSuffix(strings.Repeat("?,", len(ids)), ","), args
}
