package offline

import (
	"context"
	"database/sql"
	"fmt"
	"strings"

	"htweb/internal/auth"
)

// 离线媒体下发（F-43）。
//
// # 旧版是 M×N 次查询
//
// `for(媒体) { for(终端) { 4~5 条 SQL } }` —— 10 个媒体 × 50 个终端 = 2000+ 次查询，
// 页面必然超时（D-146）。而且外面还套着
// `LOCK TABLES offlinemediaofterminal WRITE, offlinemedia WRITE, media WRITE` ——
// **把 media 表整张写锁**，期间全站媒体功能阻塞（D-151）。
//
// 新版：三条批量查询把现状读进内存，比对后用批量 INSERT / UPDATE，一个事务，无表锁。

// MediaInput 是媒体下发的入参。
type MediaInput struct {
	MediaIDs    []int64
	TerminalIDs []int64
	Mode        Mode
}

type MediaResult struct {
	MediaCount    int    `json:"mediaCount"`
	TerminalCount int    `json:"terminalCount"`
	CopiesCreated int    `json:"copiesCreated"`
	CopiesUpdated int    `json:"copiesUpdated"`
	LinksCreated  int    `json:"linksCreated"`
	LinksUpdated  int    `json:"linksUpdated"`
	State         State  `json:"offlinestate"`
	StateText     string `json:"offlineStateText"`
}

type mediaRow struct {
	id                                   int64
	name, typeid, filename               string
	size, priority, folderid, timelength int64
	channel, sample, bitrate             int64
	codecid                              sql.NullInt64
}

// Dispatch 把一批媒体下发到一批终端。
func (s *Service) Dispatch(ctx context.Context, u *auth.User, in MediaInput) (*MediaResult, error) {
	state, err := ParseMode(in.Mode)
	if err != nil {
		return nil, err
	}
	mediaIDs := dedup(in.MediaIDs)
	termIDs := dedup(in.TerminalIDs)
	if len(mediaIDs) == 0 {
		return nil, fmt.Errorf("请选择要下发的媒体")
	}
	if len(termIDs) == 0 {
		return nil, fmt.Errorf("请选择目标终端")
	}
	if len(mediaIDs) > 500 || len(termIDs) > 3000 {
		return nil, fmt.Errorf("单次最多 500 个媒体 × 3000 个终端")
	}

	src, err := s.loadMedia(ctx, mediaIDs)
	if err != nil {
		return nil, err
	}
	if len(src) != len(mediaIDs) {
		return nil, fmt.Errorf("媒体清单里有已不存在的媒体，请重新选择")
	}
	if err := s.assertTerminals(ctx, u, termIDs); err != nil {
		return nil, err
	}

	out := &MediaResult{
		MediaCount: len(mediaIDs), TerminalCount: len(termIDs),
		State: state, StateText: Text(int(state)),
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	// 1) 同步 offlinemedia 副本
	created, updated, err := syncCopies(ctx, tx, src)
	if err != nil {
		return nil, err
	}
	out.CopiesCreated, out.CopiesUpdated = created, updated

	// 2) 同步下发关系。一次查完现状，避免 M×N（修 D-146）
	have, err := s.loadLinks(ctx, tx, mediaIDs, termIDs)
	if err != nil {
		return nil, err
	}
	type pair struct{ m, t int64 }
	var toInsert []pair
	var toUpdate []pair
	for _, m := range mediaIDs {
		for _, t := range termIDs {
			if have[pair2key(m, t)] {
				toUpdate = append(toUpdate, pair{m, t})
			} else {
				toInsert = append(toInsert, pair{m, t})
			}
		}
	}

	// 批量 INSERT，500 一批
	for start := 0; start < len(toInsert); start += 500 {
		end := start + 500
		if end > len(toInsert) {
			end = len(toInsert)
		}
		chunk := toInsert[start:end]
		var sb strings.Builder
		sb.WriteString(`INSERT INTO offlinemediaofterminal (mediaid, terminalid, offlinestate, taskid, sort) VALUES `)
		args := make([]interface{}, 0, len(chunk)*5)
		for i, p := range chunk {
			if i > 0 {
				sb.WriteByte(',')
			}
			sb.WriteString("(?,?,?,?,?)")
			// taskid 显式写 0：它是复合主键的一部分，靠列默认值太脆（D-150）
			args = append(args, p.m, p.t, int(state), MediaTaskID, 0)
		}
		if _, err := tx.ExecContext(ctx, sb.String(), args...); err != nil {
			return nil, fmt.Errorf("写入离线下发关系: %w", err)
		}
		out.LinksCreated += len(chunk)
	}

	// 批量 UPDATE：用 (mediaid, terminalid) 的 IN 组合一次改完
	if len(toUpdate) > 0 {
		mph, margs := placeholders(mediaIDs)
		tph, targs := placeholders(termIDs)
		args := append([]interface{}{int(state)}, margs...)
		args = append(args, targs...)
		res, err := tx.ExecContext(ctx,
			`UPDATE offlinemediaofterminal SET offlinestate = ?
			 WHERE mediaid IN (`+mph+`) AND terminalid IN (`+tph+`) AND taskid = `+
				fmt.Sprint(MediaTaskID), args...)
		if err != nil {
			return nil, fmt.Errorf("更新离线下发状态: %w", err)
		}
		n, _ := res.RowsAffected()
		out.LinksUpdated = int(n)
	}

	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	return out, nil
}

func pair2key(m, t int64) [2]int64 { return [2]int64{m, t} }

// syncCopies 把一批媒体同步进 offlinemedia。
//
// 两个模块共用：单纯的媒体下发走它，任务下发时任务用到的铃声也走它 ——
// 没有副本，终端拿不到文件。
func syncCopies(ctx context.Context, tx *sql.Tx, src []mediaRow) (created, updated int, err error) {
	if len(src) == 0 {
		return 0, 0, nil
	}
	ids := make([]int64, len(src))
	for i, m := range src {
		ids[i] = m.id
	}
	existing, err := collectIDs(ctx, tx, ids, `SELECT id FROM offlinemedia WHERE id IN (`)
	if err != nil {
		return 0, 0, err
	}
	for _, m := range src {
		if existing[m.id] {
			// 旧版这个分支**只更新 size/timelength/sample/bitrate，不更新 filename**（D-149）：
			// 媒体被重新上传后物理路径变了，离线副本还指向已删除的旧文件，
			// 终端拉一个不存在的文件，现象是「下发成功但放不出声」。
			if _, err := tx.ExecContext(ctx, `
				UPDATE offlinemedia SET name=?, size=?, typeid=?, priority=?, filename=?,
				       folderid=?, timelength=?, channel=?, sample=?, bitrate=?, codecid=?
				WHERE id = ?`,
				m.name, m.size, m.typeid, m.priority, m.filename,
				m.folderid, m.timelength, m.channel, m.sample, m.bitrate, m.codecid, m.id); err != nil {
				return 0, 0, fmt.Errorf("更新离线媒体副本: %w", err)
			}
			updated++
			continue
		}
		// id 显式写成 media.id —— 后台服务据此关联两张表（BR-201，强契约）
		if _, err := tx.ExecContext(ctx, `
			INSERT INTO offlinemedia (id, name, size, typeid, priority, filename,
			                          folderid, timelength, channel, sample, bitrate, codecid)
			VALUES (?,?,?,?,?,?,?,?,?,?,?,?)`,
			m.id, m.name, m.size, m.typeid, m.priority, m.filename,
			m.folderid, m.timelength, m.channel, m.sample, m.bitrate, m.codecid); err != nil {
			return 0, 0, fmt.Errorf("写入离线媒体副本: %w", err)
		}
		created++
	}
	return created, updated, nil
}

func (s *Service) loadMedia(ctx context.Context, ids []int64) ([]mediaRow, error) {
	ph, args := placeholders(ids)
	rows, err := s.db.QueryContext(ctx, `
		SELECT id, COALESCE(name,''), COALESCE(size,0), COALESCE(typeid,''),
		       COALESCE(priority,0), COALESCE(filename,''), COALESCE(folderid,0),
		       COALESCE(timelength,0), COALESCE(channel,0), COALESCE(sample,0),
		       COALESCE(bitrate,0), codecid
		FROM media WHERE id IN (`+ph+`)`, args...)
	if err != nil {
		return nil, fmt.Errorf("查询媒体: %w", err)
	}
	defer rows.Close()
	out := []mediaRow{}
	for rows.Next() {
		var m mediaRow
		if err := rows.Scan(&m.id, &m.name, &m.size, &m.typeid, &m.priority, &m.filename,
			&m.folderid, &m.timelength, &m.channel, &m.sample, &m.bitrate, &m.codecid); err != nil {
			return nil, err
		}
		out = append(out, m)
	}
	return out, rows.Err()
}

// assertTerminals 校验终端存在，并对普通用户收敛到自己绑定的终端。
func (s *Service) assertTerminals(ctx context.Context, u *auth.User, ids []int64) error {
	ph, args := placeholders(ids)
	var n int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM terminal WHERE id IN (`+ph+`)`, args...).Scan(&n); err != nil {
		return fmt.Errorf("校验终端: %w", err)
	}
	if n != len(ids) {
		return fmt.Errorf("终端清单里有已不存在的终端，请重新选择")
	}
	if u.IsAdmin {
		return nil
	}
	var bound int
	bargs := append(append([]interface{}{}, args...), u.ID)
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(DISTINCT terminalid) FROM userterminal
		 WHERE terminalid IN (`+ph+`) AND userid = ?`, bargs...).Scan(&bound); err != nil {
		return fmt.Errorf("校验终端归属: %w", err)
	}
	if bound != len(ids) {
		return fmt.Errorf("终端清单里有未绑定给你的终端")
	}
	return nil
}

func collectIDs(ctx context.Context, tx *sql.Tx, ids []int64, prefix string) (map[int64]bool, error) {
	ph, args := placeholders(ids)
	rows, err := tx.QueryContext(ctx, prefix+ph+`)`, args...)
	if err != nil {
		return nil, fmt.Errorf("查询已有离线副本: %w", err)
	}
	defer rows.Close()
	out := map[int64]bool{}
	for rows.Next() {
		var id int64
		if err := rows.Scan(&id); err != nil {
			return nil, err
		}
		out[id] = true
	}
	return out, rows.Err()
}

func (s *Service) loadLinks(ctx context.Context, tx *sql.Tx, mediaIDs, termIDs []int64) (map[[2]int64]bool, error) {
	mph, margs := placeholders(mediaIDs)
	tph, targs := placeholders(termIDs)
	args := append(append([]interface{}{}, margs...), targs...)
	rows, err := tx.QueryContext(ctx,
		`SELECT mediaid, terminalid FROM offlinemediaofterminal
		 WHERE mediaid IN (`+mph+`) AND terminalid IN (`+tph+`) AND taskid = `+
			fmt.Sprint(MediaTaskID), args...)
	if err != nil {
		return nil, fmt.Errorf("查询已有下发关系: %w", err)
	}
	defer rows.Close()
	out := map[[2]int64]bool{}
	for rows.Next() {
		var m, t int64
		if err := rows.Scan(&m, &t); err != nil {
			return nil, err
		}
		out[pair2key(m, t)] = true
	}
	return out, rows.Err()
}

// Stop 停止传输（F-46）：把选中的下发关系置为 11。
//
// 旧 `stop_offline_music` 的 WHERE 里写的是 `and task='0'` ——
// **列名写错了**（该列叫 `taskid`），后面还跟着 `or die()`，
// 所以「停止离线音乐」这个功能**从来没生效过**（D-147 / G-05）。
func (s *Service) Stop(ctx context.Context, u *auth.User, mediaIDs, termIDs []int64) (int64, error) {
	mediaIDs, termIDs = dedup(mediaIDs), dedup(termIDs)
	if len(mediaIDs) == 0 || len(termIDs) == 0 {
		return 0, fmt.Errorf("请选择要停止的媒体与终端")
	}
	if err := s.assertTerminals(ctx, u, termIDs); err != nil {
		return 0, err
	}
	mph, margs := placeholders(mediaIDs)
	tph, targs := placeholders(termIDs)
	args := append([]interface{}{int(StateStop)}, margs...)
	args = append(args, targs...)
	res, err := s.db.ExecContext(ctx,
		`UPDATE offlinemediaofterminal SET offlinestate = ?
		 WHERE mediaid IN (`+mph+`) AND terminalid IN (`+tph+`) AND taskid = `+
			fmt.Sprint(MediaTaskID), args...)
	if err != nil {
		return 0, fmt.Errorf("停止离线传输: %w", err)
	}
	n, _ := res.RowsAffected()
	return n, nil
}
