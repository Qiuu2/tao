package user

import (
	"context"
	"database/sql"
	"fmt"
	"strings"
)

// CascadeImpact 描述删除用户（组）会连带删掉哪些数据。
type CascadeImpact struct {
	Users          int      `json:"users"`
	UserNames      []string `json:"userNames"`
	Folders        int      `json:"folders"`
	Media          int      `json:"media"`
	Tasks          int      `json:"tasks"`
	TerminalGroups int      `json:"terminalGroups"`
	AlarmAreas     int      `json:"alarmAreas"`
	TaskFolders    int      `json:"taskFolders"`
	TerminalBinds  int      `json:"terminalBinds"`
}

// cascadeTargets 汇总一批用户名下的全部关联对象 ID。
//
// 这是删除用户/用户组的基础。旧版在这里犯了本项目最严重的数据损坏错误：
//
//	$sql = "SELECT id FROM book_admin WHERE usergroupid='$group_id'";
//	if($row = mysqli_fetch_array($result)) { $get_userid = $row['id']; ... }
//
// 用 if 而不是 while —— $get_userid 只是**组内第一个用户**。
// 随后所有 "... IN($get_userid)" 的清理只对这一个用户生效，
// 但紧接着 DELETE FROM book_admin WHERE usergroupid='$group_id' 把全组用户都删了。
// 结果：第 2 个及以后用户的文件夹、媒体、任务、分区、报警区全部变成孤儿数据，
// 永久残留且再也无法通过界面访问（缺陷 D-45）。
type cascadeTargets struct {
	userIDs      []int64
	userNames    []string
	folderIDs    []int64
	mediaIDs     []int64
	mediaFiles   []string
	mediaFolders []int64
	taskIDs      []int64
	streamIDs    []int64
	alarmIDs     []int64
}

func (s *Service) collectCascade(ctx context.Context, userIDs []int64) (*cascadeTargets, error) {
	t := &cascadeTargets{userIDs: userIDs}
	if len(userIDs) == 0 {
		return t, nil
	}
	ph, args := placeholders(userIDs)

	// 用户名
	if err := s.collect(ctx, `SELECT username FROM book_admin WHERE id IN (`+ph+`)`, args,
		func(rows *sql.Rows) error {
			var n string
			if err := rows.Scan(&n); err != nil {
				return err
			}
			t.userNames = append(t.userNames, n)
			return nil
		}); err != nil {
		return nil, err
	}

	// 用户创建的文件夹
	if err := s.collectInt64(ctx, `SELECT id FROM filefolder WHERE userid IN (`+ph+`)`, args, &t.folderIDs); err != nil {
		return nil, err
	}

	// 这些文件夹下的媒体（含物理路径，供提交后清理）
	if len(t.folderIDs) > 0 {
		fph, fargs := placeholders(t.folderIDs)
		if err := s.collect(ctx,
			`SELECT id, COALESCE(filename,''), folderid FROM media WHERE folderid IN (`+fph+`)`, fargs,
			func(rows *sql.Rows) error {
				var id, folderID int64
				var fn string
				if err := rows.Scan(&id, &fn, &folderID); err != nil {
					return err
				}
				t.mediaIDs = append(t.mediaIDs, id)
				t.mediaFolders = append(t.mediaFolders, folderID)
				// media.id = 1 是系统内置提示音，物理文件永不删除
				if id > 1 && fn != "" && fn != "none" && fn != "tts" {
					t.mediaFiles = append(t.mediaFiles, fn)
				}
				return nil
			}); err != nil {
			return nil, err
		}
	}

	// 用户创建的任务（含次任务）
	if err := s.collectInt64(ctx,
		`SELECT taskid FROM task WHERE task_user_id IN (`+ph+`)`, args, &t.taskIDs); err != nil {
		return nil, err
	}
	// 用户的终端分区
	if err := s.collectInt64(ctx,
		`SELECT streamid FROM serverplaystream WHERE userid IN (`+ph+`)`, args, &t.streamIDs); err != nil {
		return nil, err
	}
	// 用户的报警分区
	if err := s.collectInt64(ctx,
		`SELECT id FROM alarmarea WHERE userid IN (`+ph+`)`, args, &t.alarmIDs); err != nil {
		return nil, err
	}
	return t, nil
}

// deleteCascade 在事务内按依赖顺序清理全部关联数据。
func (s *Service) deleteCascade(ctx context.Context, tx *sql.Tx, t *cascadeTargets) error {
	uph, uargs := placeholders(t.userIDs)

	// ---- 任务及其关联 ----
	if len(t.taskIDs) > 0 {
		tph, targs := placeholders(t.taskIDs)
		// 任务专属的 TTS 媒体与语句要一并删除（旧版逻辑，保持一致）
		if _, err := tx.ExecContext(ctx,
			`DELETE FROM ttssentence WHERE sentenceid IN
			   (SELECT mediaid FROM mediaoftask WHERE taskid IN (`+tph+`))`, targs...); err != nil {
			return fmt.Errorf("清理 TTS 语句: %w", err)
		}
		if _, err := tx.ExecContext(ctx,
			`DELETE FROM media WHERE id IN
			   (SELECT mediaid FROM mediaoftask WHERE taskid IN (`+tph+`)) AND typeid = 'tts'`, targs...); err != nil {
			return fmt.Errorf("清理 TTS 媒体: %w", err)
		}
		for _, stmt := range []string{
			`DELETE FROM terminaloftask WHERE taskid IN (` + tph + `)`,
			`DELETE FROM mediaoftask WHERE taskid IN (` + tph + `)`,
			`DELETE FROM terminalkeymaptask WHERE taskid IN (` + tph + `)`,
			`DELETE FROM offlinetaskofterminal WHERE taskid IN (` + tph + `)`,
			`DELETE FROM offlinemediaofterminal WHERE taskid IN (` + tph + `)`,
			`DELETE FROM task WHERE taskid IN (` + tph + `)`,
		} {
			if _, err := tx.ExecContext(ctx, stmt, targs...); err != nil {
				return fmt.Errorf("清理任务关联: %w", err)
			}
		}
	}

	// ---- 报警分区 ----
	if len(t.alarmIDs) > 0 {
		aph, aargs := placeholders(t.alarmIDs)
		// terminalofalarmgroup.alarmgroupid 是 varchar，比较时按字符串走（契约 C-32）
		if _, err := tx.ExecContext(ctx,
			`DELETE FROM terminalofalarmgroup WHERE alarmgroupid IN (`+aph+`)`, aargs...); err != nil {
			return fmt.Errorf("清理报警分区终端: %w", err)
		}
		if _, err := tx.ExecContext(ctx,
			`DELETE FROM alarmgroupmap WHERE firealarmgroupid IN (`+aph+`)`, aargs...); err != nil {
			return fmt.Errorf("清理报警映射: %w", err)
		}
		// 补齐旧版遗漏：把成员终端的 firealarmgroup 重置为 -1（该列注释定义的「无分区」值）
		if _, err := tx.ExecContext(ctx,
			`UPDATE terminal SET firealarmgroup = -1 WHERE firealarmgroup IN (`+aph+`)`, aargs...); err != nil {
			return fmt.Errorf("重置终端报警分区: %w", err)
		}
		if _, err := tx.ExecContext(ctx,
			`DELETE FROM alarmarea WHERE id IN (`+aph+`)`, aargs...); err != nil {
			return fmt.Errorf("删除报警分区: %w", err)
		}
	}

	// ---- 终端分区 ----
	if len(t.streamIDs) > 0 {
		sph, sargs := placeholders(t.streamIDs)
		if _, err := tx.ExecContext(ctx,
			`DELETE FROM terminalofgroup WHERE groupid IN (`+sph+`)`, sargs...); err != nil {
			return fmt.Errorf("清理分区成员: %w", err)
		}
		if _, err := tx.ExecContext(ctx,
			`DELETE FROM serverplaystream WHERE streamid IN (`+sph+`)`, sargs...); err != nil {
			return fmt.Errorf("删除终端分区: %w", err)
		}
	}

	// ---- 媒体与文件夹 ----
	if len(t.mediaIDs) > 0 {
		mph, margs := placeholders(t.mediaIDs)
		for _, stmt := range []string{
			`DELETE FROM camer_alarmofmedia WHERE mediaid IN (` + mph + `)`,
			`DELETE FROM shortcutkeytask WHERE mediaid IN (` + mph + `)`,
			`DELETE FROM media WHERE id IN (` + mph + `)`,
		} {
			if _, err := tx.ExecContext(ctx, stmt, margs...); err != nil {
				return fmt.Errorf("清理媒体: %w", err)
			}
		}
	}
	if len(t.folderIDs) > 0 {
		fph, fargs := placeholders(t.folderIDs)
		if _, err := tx.ExecContext(ctx,
			`DELETE FROM filefolder WHERE id IN (`+fph+`)`, fargs...); err != nil {
			return fmt.Errorf("删除文件夹: %w", err)
		}
	}

	// ---- 用户自身的附属数据 ----
	for _, stmt := range []string{
		`DELETE FROM filetaskfree WHERE userid IN (` + uph + `)`,
		`DELETE FROM usersn WHERE userid IN (` + uph + `)`,
		`DELETE FROM userterminal WHERE userid IN (` + uph + `)`,
		`DELETE FROM book_admin WHERE id IN (` + uph + `)`,
	} {
		if _, err := tx.ExecContext(ctx, stmt, uargs...); err != nil {
			return fmt.Errorf("清理用户数据: %w", err)
		}
	}
	return nil
}

func (s *Service) impactOf(t *cascadeTargets) CascadeImpact {
	return CascadeImpact{
		Users:          len(t.userIDs),
		UserNames:      t.userNames,
		Folders:        len(t.folderIDs),
		Media:          len(t.mediaIDs),
		Tasks:          len(t.taskIDs),
		TerminalGroups: len(t.streamIDs),
		AlarmAreas:     len(t.alarmIDs),
	}
}

// ---------- 查询小工具 ----------

func (s *Service) collect(ctx context.Context, q string, args []interface{}, fn func(*sql.Rows) error) error {
	rows, err := s.db.QueryContext(ctx, q, args...)
	if err != nil {
		return fmt.Errorf("查询失败: %w", err)
	}
	defer rows.Close()
	for rows.Next() {
		if err := fn(rows); err != nil {
			return err
		}
	}
	return rows.Err()
}

func (s *Service) collectInt64(ctx context.Context, q string, args []interface{}, dst *[]int64) error {
	return s.collect(ctx, q, args, func(rows *sql.Rows) error {
		var v int64
		if err := rows.Scan(&v); err != nil {
			return err
		}
		*dst = append(*dst, v)
		return nil
	})
}

func placeholders(ids []int64) (string, []interface{}) {
	if len(ids) == 0 {
		return "NULL", nil
	}
	ph := strings.TrimSuffix(strings.Repeat("?,", len(ids)), ",")
	args := make([]interface{}, len(ids))
	for i, v := range ids {
		args[i] = v
	}
	return ph, args
}
