// Package assistant 实现 AI 助手（业务域十四）。
//
// # 它是什么
//
// 一个浮在所有页面之上的对话框：用中文说一句「把春季方案明天8点的早读挪到9点」，
// 它把这句话变成对广播系统的实际操作。不是聊天机器人 ——
// 它只做广播相关的事，做不了的会如实说做不了。
//
// # 从哪搬来的
//
// 一比一复刻 ai_speaker_project（2ada1e9 / T124）那套 Python 实现，
// 但**执行层全部换成直连数据库**：原来是 HTTP SDK 打到广播服务器上，
// 现在 htweb 自己就坐在 audioserver 上，走 tao 已有的 service 层。
// 详见《AI助手迁移-现状分析》。
//
// # 分工
//
//	NLU（Python 旁挂，同机 127.0.0.1）  文本 → intent + slots，只做推理
//	                                    不碰数据库、不知道广播系统的存在
//	本包（Go）                          消歧、鉴权、执行、审计、回话
//
// 这样模型效果与原系统一致，而所有写操作都在 Go 这一侧、都走 tao 的权限与审计。
//
// # 状态存哪
//
// 六张新表（assistant_*，见 db/assistant_tables.sql）。不改任何旧表。
//
// # 权限
//
// 助手**不是后门**：每个意图映射到与页面相同的权限位（见 intent.go）。
// 只有「文件广播」权限的人，不能靠对助手说话去改作息方案。
package assistant

import (
	"context"
	"database/sql"
	"time"

	"htweb/internal/config"
)

// Service 是助手的门面。
type Service struct {
	db  *sql.DB
	nlu *NLU
	cfg config.Assistant
}

func New(db *sql.DB, cfg config.Assistant) *Service {
	return &Service{
		db:  db,
		nlu: NewNLU(cfg.NLUURL, cfg.NLUTimeout),
		cfg: cfg,
	}
}

// Enabled 报告助手这个功能开没开。关掉时路由不注册、菜单不下发。
func (s *Service) Enabled() bool { return s.cfg.Enabled }

// NLUStatus 透出 NLU 的可用情况，给状态接口用。
func (s *Service) NLUStatus(ctx context.Context) (enabled bool, url, lastErr string, lastOK time.Time) {
	if s.nlu == nil {
		return false, "", "未初始化", time.Time{}
	}
	_ = s.nlu.Health(ctx)
	return s.nlu.Status()
}

// StartHousekeeping 起一个后台协程做日常清理：过期会话、过期撤销凭据。
//
// 与日志保留期那套同一个套路：起来 1 分钟后跑第一次，之后每小时一次。
// 第一次故意延后，是为了不和启动时的连接池预热抢资源。
func (s *Service) StartHousekeeping(ctx context.Context) {
	go func() {
		timer := time.NewTimer(time.Minute)
		defer timer.Stop()
		for {
			select {
			case <-ctx.Done():
				return
			case <-timer.C:
			}
			s.housekeep(ctx)
			timer.Reset(time.Hour)
		}
	}()
}

func (s *Service) housekeep(ctx context.Context) {
	if n, err := s.PurgeSessions(ctx); err != nil {
		logf("清理过期会话失败: %v", err)
	} else if n > 0 {
		logf("清理过期会话 %d 条", n)
	}
	if n, err := s.PurgeUndo(ctx); err != nil {
		logf("清理过期撤销凭据失败: %v", err)
	} else if n > 0 {
		logf("清理过期撤销凭据 %d 条", n)
	}
}
