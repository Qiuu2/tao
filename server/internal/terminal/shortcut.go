package terminal

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"strconv"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/store"
)

// 快捷键寻呼（ok112 的 setterminalkeyoption / addshotcutkey_msg / modifyshotcutkey_msg）。
//
// # 一个快捷键是什么
//
// 「在**这台**终端上按**这个键**，去寻呼**那些**终端」。三段信息落在两张表：
//
//	terminalkey     (id, name, terminalid, key, sendmodul, flag)
//	    ↑ 键的定义：归属哪台终端、键值是多少、叫什么名字
//	    flag  0 = 快捷键，1 = 急救
//	terminalkeymap  (id, keyid, terminalid, area, groupid)
//	    ↑ 这个键按下去寻呼谁，一个键可以有多条（多个目标终端）
//
// # ⚠ 三个会咬人的地方
//
//  1. **`terminalkey.terminalid` 是 varchar(45)，不是 int。**
//     直接 `WHERE terminalid = 5` 在 MySQL 里会做隐式类型转换，
//     '5abc' 也会等于 5。一律按字符串比较、按字符串写入。
//
//  2. **`key` 是 MySQL 保留字**，不加反引号语法就错。
//
//  3. **键值不是随便填的**：每种终端类型可选的键值不同，而且
//     「人看到的」和「存进去的」可能不是一个数（比如第 10 个键存 30）。
//     规格与测试见 keyspec.go / keyspec_test.go。这里保存前必须按类型校验，
//     否则存进去一个该型号根本没有的键，现场按不出任何反应，还很难查。
//
// # area 分区掩码
//
// `terminalkeymap.area` 是 varchar(16)，默认 '1111111111111111'（16 个 1）。
// ok112 的 addshotcutkey_msg 里是先插入再单独 UPDATE 一次 area，
// 这里合并成插入时一次写好，语义不变、少一轮往返。

// ShortcutTarget 是一个快捷键要寻呼的目标终端。
type ShortcutTarget struct {
	TerminalID   int64  `json:"terminalId"`
	TerminalName string `json:"terminalname"`
	// Deleted 目标终端已经被删掉了，但关联行还在。
	Deleted bool  `json:"deleted"`
	GroupID int64 `json:"groupId"`
	// Area 分区掩码，16 位 0/1。
	Area string `json:"area"`
}

// ShortcutKey 是一个快捷键（含它的目标终端）。
type ShortcutKey struct {
	ID   int64  `json:"id"`
	Name string `json:"name"`
	// OwnerID 是按这个键的终端（terminalkey.terminalid，库里是字符串）。
	OwnerID int64 `json:"ownerId"`
	// Key 是**存库值**。0 = 紧急触发，30 = 短路触发。
	Key int `json:"key"`
	// KeyLabel 是给人看的键值说明，按终端类型算出来。
	KeyLabel string `json:"keyLabel"`
	// SendModul 单播/组播。ok112 建表默认 1，新增时不给界面，原样带着。
	SendModul int `json:"sendmodul"`
	// Emergency 对应 flag：false = 快捷键，true = 急救。
	Emergency bool             `json:"emergency"`
	Targets   []ShortcutTarget `json:"targets"`
}

// DefaultArea 是 terminalkeymap.area 的默认值（16 个 1），照建表默认。
const DefaultArea = "1111111111111111"

var (
	ErrKeyNotFound  = errors.New("快捷键不存在")
	ErrKeyValueBad  = errors.New("该终端类型不支持这个键值")
	ErrKeyDuplicate = errors.New("这台终端上这个键值已经用过了")
)

// terminalType 取一台终端的类型与编码能力，快捷键的校验都要用它。
func (s *Service) terminalType(ctx context.Context, id int64) (typeID int, isEncode bool, name string, err error) {
	var enc int
	err = s.db.QueryRowContext(ctx, `
		SELECT COALESCE(t.typeid,0), COALESCE(tt.isencode,0), COALESCE(t.terminalname,'')
		FROM terminal t
		LEFT JOIN terminaltype tt ON tt.id = t.typeid
		WHERE t.id = ? LIMIT 1`, id).Scan(&typeID, &enc, &name)
	if errors.Is(err, sql.ErrNoRows) {
		return 0, false, "", fmt.Errorf("终端不存在")
	}
	if err != nil {
		return 0, false, "", fmt.Errorf("查询终端类型: %w", err)
	}
	return typeID, enc == 1, name, nil
}

// KeyOptionsFor 返回某台终端可选的键值（界面下拉用）。
func (s *Service) KeyOptionsFor(ctx context.Context, id int64, emergency bool) ([]KeyOption, error) {
	typeID, isEncode, _, err := s.terminalType(ctx, id)
	if err != nil {
		return nil, err
	}
	return ShortcutKeyOptions(typeID, isEncode, emergency), nil
}

// ListShortcutKeys 列出一台终端上的快捷键。
//
// emergencyOnly 为 nil 时两种都列；否则只列 flag 对应的那一种。
func (s *Service) ListShortcutKeys(ctx context.Context, ownerID int64, emergencyOnly *bool) ([]ShortcutKey, error) {
	typeID, isEncode, _, err := s.terminalType(ctx, ownerID)
	if err != nil {
		return nil, err
	}

	cond := &store.Cond{}
	// ⚠ terminalid 是 varchar，按字符串比
	cond.Add("k.terminalid = ?", strconv.FormatInt(ownerID, 10))
	if emergencyOnly != nil {
		f := 0
		if *emergencyOnly {
			f = 1
		}
		cond.Add("COALESCE(k.flag,0) = ?", f)
	}

	// ⚠ key 是保留字，必须反引号
	rs, err := s.db.QueryContext(ctx, `
		SELECT k.id, COALESCE(k.name,''), COALESCE(k.`+"`key`"+`,0),
		       COALESCE(k.sendmodul,1), COALESCE(k.flag,0)
		FROM terminalkey k`+cond.Where()+` ORDER BY k.`+"`key`"+`, k.id`, cond.Args()...)
	if err != nil {
		return nil, fmt.Errorf("查询快捷键: %w", err)
	}
	defer rs.Close()

	out := []ShortcutKey{}
	ids := []int64{}
	for rs.Next() {
		var k ShortcutKey
		var flag int
		if err := rs.Scan(&k.ID, &k.Name, &k.Key, &k.SendModul, &flag); err != nil {
			return nil, err
		}
		k.OwnerID = ownerID
		k.Emergency = flag == 1
		k.Targets = []ShortcutTarget{}
		out = append(out, k)
		ids = append(ids, k.ID)
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}
	if len(out) == 0 {
		return out, nil
	}

	// 键值说明按终端类型算。⚠ 急救与快捷键的可选集不同，逐条按自己的 flag 算。
	for i := range out {
		out[i].KeyLabel = labelFor(typeID, isEncode, out[i].Emergency, out[i].Key)
	}

	// 目标终端一次查回来，别在循环里逐个查
	if err := s.fillShortcutTargets(ctx, out, ids); err != nil {
		return nil, err
	}
	return out, nil
}

// labelFor 在该类型的可选集里找这个键值的说明；找不到就直白说明它不在可选范围内 ——
// 库里存着一个该型号没有的键值是真实存在的情况（旧版不校验），要能看出来。
func labelFor(typeID int, isEncode, emergency bool, key int) string {
	for _, o := range ShortcutKeyOptions(typeID, isEncode, emergency) {
		if o.Value == key {
			return o.Label
		}
	}
	return fmt.Sprintf("%d（该终端类型无此键）", key)
}

func (s *Service) fillShortcutTargets(ctx context.Context, keys []ShortcutKey, keyIDs []int64) error {
	ph, args := placeholders(keyIDs)
	// LEFT JOIN：目标终端被删后关联行可能还在，内连接会让这些脏行静默消失
	rs, err := s.db.QueryContext(ctx, `
		SELECT m.keyid, m.terminalid, t.id IS NOT NULL, COALESCE(t.terminalname,''),
		       COALESCE(m.groupid,0), COALESCE(m.area,?)
		FROM terminalkeymap m
		LEFT JOIN terminal t ON t.id = m.terminalid
		WHERE m.keyid IN (`+ph+`) ORDER BY m.id`,
		append([]interface{}{DefaultArea}, args...)...)
	if err != nil {
		return fmt.Errorf("查询快捷键目标: %w", err)
	}
	defer rs.Close()

	byKey := map[int64][]ShortcutTarget{}
	for rs.Next() {
		var keyID int64
		var tg ShortcutTarget
		var exists bool
		if err := rs.Scan(&keyID, &tg.TerminalID, &exists, &tg.TerminalName,
			&tg.GroupID, &tg.Area); err != nil {
			return err
		}
		tg.Deleted = !exists
		if tg.Deleted {
			tg.TerminalName = "(终端已删除)"
		}
		byKey[keyID] = append(byKey[keyID], tg)
	}
	if err := rs.Err(); err != nil {
		return err
	}
	for i := range keys {
		if v, ok := byKey[keys[i].ID]; ok {
			keys[i].Targets = v
		}
	}
	return nil
}

// ShortcutInput 是新建/修改快捷键的入参。
type ShortcutInput struct {
	Name      string
	Key       int
	Emergency bool
	// TargetIDs 这个键按下去要寻呼的终端。可以为空（先建键、之后再配目标）。
	TargetIDs []int64
	// Area 分区掩码，留空用默认值。
	Area string
}

const shortcutLock = "htweb_terminal_shortcut"

// nameLimit 是 terminalkey.name 的列宽。
const shortcutNameLimit = 45

func (in *ShortcutInput) normalize() error {
	in.Name = strings.TrimSpace(in.Name)
	if in.Name == "" {
		return fmt.Errorf("快捷键名称不能为空")
	}
	if len(in.Name) > shortcutNameLimit {
		return fmt.Errorf("快捷键名称过长：按 UTF-8 计 %d 字节，上限 %d 字节",
			len(in.Name), shortcutNameLimit)
	}
	in.Area = strings.TrimSpace(in.Area)
	if in.Area == "" {
		in.Area = DefaultArea
	}
	if len(in.Area) != 16 || strings.Trim(in.Area, "01") != "" {
		return fmt.Errorf("分区掩码必须是 16 位的 0/1 字符串")
	}
	return nil
}

// assertKeyValue 校验键值对这台终端是不是合法的。
//
// ⚠ 旧版完全不校验：下拉是按类型生成的，但提交时不再核对，
// 直接构造一个请求就能存进一个该型号根本没有的键值。
func (s *Service) assertKeyValue(ctx context.Context, ownerID int64, key int, emergency bool) error {
	typeID, isEncode, name, err := s.terminalType(ctx, ownerID)
	if err != nil {
		return err
	}
	if !isEncode {
		// 包 ErrKeyValueBad：这是「这个型号做不了」而不是服务端出错，
		// HTTP 层据此回 400 并把这句话原样给用户，不能落成 500 内部错误。
		return fmt.Errorf("%w：终端「%s」不支持编码（isencode = 0），没有可用的键值", ErrKeyValueBad, name)
	}
	for _, o := range ShortcutKeyOptions(typeID, isEncode, emergency) {
		if o.Value == key {
			return nil
		}
	}
	return fmt.Errorf("%w：终端「%s」（类型 %d）没有键值 %d", ErrKeyValueBad, name, typeID, key)
}

// assertKeyFree 同一台终端上同一个键值只能有一个定义。
//
// 旧版不查重，同一个键可以配出多条互相覆盖的定义，按下去行为不确定。
func (s *Service) assertKeyFree(ctx context.Context, ownerID int64, key int, emergency bool, excludeID int64) error {
	f := 0
	if emergency {
		f = 1
	}
	q := "SELECT id FROM terminalkey WHERE terminalid = ? AND `key` = ? AND COALESCE(flag,0) = ?"
	args := []interface{}{strconv.FormatInt(ownerID, 10), key, f}
	if excludeID > 0 {
		q += " AND id <> ?"
		args = append(args, excludeID)
	}
	q += " LIMIT 1"
	var id int64
	err := s.db.QueryRowContext(ctx, q, args...).Scan(&id)
	if err == nil {
		return ErrKeyDuplicate
	}
	if errors.Is(err, sql.ErrNoRows) {
		return nil
	}
	return fmt.Errorf("快捷键查重: %w", err)
}

// CreateShortcutKey 新建一个快捷键。
func (s *Service) CreateShortcutKey(ctx context.Context, u *auth.User, ownerID int64, in ShortcutInput) (int64, error) {
	if err := in.normalize(); err != nil {
		return 0, err
	}
	if _, _, err := s.CheckBound(ctx, u, []int64{ownerID}); err != nil {
		return 0, err
	}
	unlock, err := store.Lock(ctx, s.db, shortcutLock)
	if err != nil {
		return 0, err
	}
	defer unlock()

	if err := s.assertKeyValue(ctx, ownerID, in.Key, in.Emergency); err != nil {
		return 0, err
	}
	if err := s.assertKeyFree(ctx, ownerID, in.Key, in.Emergency, 0); err != nil {
		return 0, err
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return 0, err
	}
	defer tx.Rollback()

	flag := 0
	if in.Emergency {
		flag = 1
	}
	// sendmodul 不给界面，用建表默认的 1（单播）
	res, err := tx.ExecContext(ctx,
		"INSERT INTO terminalkey (name, terminalid, `key`, sendmodul, flag) VALUES (?,?,?,?,?)",
		in.Name, strconv.FormatInt(ownerID, 10), in.Key, 1, flag)
	if err != nil {
		return 0, fmt.Errorf("新建快捷键: %w", err)
	}
	keyID, err := res.LastInsertId()
	if err != nil {
		return 0, err
	}
	if err := insertShortcutTargets(ctx, tx, keyID, in.TargetIDs, in.Area); err != nil {
		return 0, err
	}
	if err := tx.Commit(); err != nil {
		return 0, err
	}
	return keyID, nil
}

// UpdateShortcutKey 修改快捷键。目标终端是**整体重写**（先删后插），与旧版一致。
func (s *Service) UpdateShortcutKey(ctx context.Context, u *auth.User, keyID int64, in ShortcutInput) error {
	if err := in.normalize(); err != nil {
		return err
	}
	unlock, err := store.Lock(ctx, s.db, shortcutLock)
	if err != nil {
		return err
	}
	defer unlock()

	ownerID, err := s.shortcutOwner(ctx, keyID)
	if err != nil {
		return err
	}
	if _, _, err := s.CheckBound(ctx, u, []int64{ownerID}); err != nil {
		return err
	}
	if err := s.assertKeyValue(ctx, ownerID, in.Key, in.Emergency); err != nil {
		return err
	}
	if err := s.assertKeyFree(ctx, ownerID, in.Key, in.Emergency, keyID); err != nil {
		return err
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return err
	}
	defer tx.Rollback()

	flag := 0
	if in.Emergency {
		flag = 1
	}
	if _, err := tx.ExecContext(ctx,
		"UPDATE terminalkey SET name = ?, `key` = ?, flag = ? WHERE id = ?",
		in.Name, in.Key, flag, keyID); err != nil {
		return fmt.Errorf("修改快捷键: %w", err)
	}
	if _, err := tx.ExecContext(ctx,
		"DELETE FROM terminalkeymap WHERE keyid = ?", keyID); err != nil {
		return fmt.Errorf("清理快捷键目标: %w", err)
	}
	if err := insertShortcutTargets(ctx, tx, keyID, in.TargetIDs, in.Area); err != nil {
		return err
	}
	return tx.Commit()
}

func insertShortcutTargets(ctx context.Context, tx *sql.Tx, keyID int64, targets []int64, area string) error {
	seen := map[int64]bool{}
	for _, t := range targets {
		if t <= 0 || seen[t] {
			continue
		}
		seen[t] = true
		// ⚠ 旧版是先 INSERT 再单独 UPDATE 一次 area，这里插入时一次写好。
		//   groupid 由服务端按目标终端当前归属带上，不让前端传 —— 旧版让前端
		//   把终端串和分区串按下标对齐，长度不一致就静默写 0（与 D-107 同型）。
		if _, err := tx.ExecContext(ctx, `
			INSERT INTO terminalkeymap (keyid, terminalid, area, groupid)
			VALUES (?, ?, ?, COALESCE((SELECT tog.groupid FROM terminalofgroup tog
			                            WHERE tog.terminalid = ? ORDER BY tog.id LIMIT 1), 0))`,
			keyID, t, area, t); err != nil {
			return fmt.Errorf("写入快捷键目标: %w", err)
		}
	}
	return nil
}

func (s *Service) shortcutOwner(ctx context.Context, keyID int64) (int64, error) {
	var owner string
	err := s.db.QueryRowContext(ctx,
		`SELECT COALESCE(terminalid,'') FROM terminalkey WHERE id = ? LIMIT 1`, keyID).Scan(&owner)
	if errors.Is(err, sql.ErrNoRows) {
		return 0, ErrKeyNotFound
	}
	if err != nil {
		return 0, fmt.Errorf("查询快捷键归属: %w", err)
	}
	// ⚠ 这一列是 varchar，历史数据里可能是空串或非数字
	id, convErr := strconv.ParseInt(strings.TrimSpace(owner), 10, 64)
	if convErr != nil {
		return 0, fmt.Errorf("快捷键 %d 的归属终端记录不是数字（%q），数据有问题", keyID, owner)
	}
	return id, nil
}

// DeleteShortcutKeys 删除快捷键，连同它的目标关联。
func (s *Service) DeleteShortcutKeys(ctx context.Context, u *auth.User, keyIDs []int64) (int, error) {
	if len(keyIDs) == 0 {
		return 0, nil
	}
	unlock, err := store.Lock(ctx, s.db, shortcutLock)
	if err != nil {
		return 0, err
	}
	defer unlock()

	// 逐个核对归属终端是否可被当前用户操作
	for _, id := range keyIDs {
		owner, err := s.shortcutOwner(ctx, id)
		if err != nil {
			return 0, err
		}
		if _, _, err := s.CheckBound(ctx, u, []int64{owner}); err != nil {
			return 0, err
		}
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return 0, err
	}
	defer tx.Rollback()

	ph, args := placeholders(keyIDs)
	// 先删关联再删定义，顺序反了会留下孤儿关联行
	if _, err := tx.ExecContext(ctx,
		"DELETE FROM terminalkeymap WHERE keyid IN ("+ph+")", args...); err != nil {
		return 0, fmt.Errorf("删除快捷键目标: %w", err)
	}
	res, err := tx.ExecContext(ctx,
		"DELETE FROM terminalkey WHERE id IN ("+ph+")", args...)
	if err != nil {
		return 0, fmt.Errorf("删除快捷键: %w", err)
	}
	n, _ := res.RowsAffected()
	if err := tx.Commit(); err != nil {
		return 0, err
	}
	return int(n), nil
}
