package assistant

import (
	"context"
	"errors"
	"strings"

	"htweb/internal/auth"
)

// 一轮对话的编排。
//
// # 顺序为什么是这样
//
//	1. pending 确认      上一轮问了「确定吗」，这一轮的「是/否」要在 NLU **之前**截住
//	                     —— 「是」这个字送进模型只会得到一个莫名其妙的意图
//	2. NLU 推理          文本 → intent + slots
//	3. 指代消解          「刚才那个」「上一个」→ 从会话里回填上一轮的意图槽位
//	4. 锁定项覆盖        已确认过的方案名/任务名不许被本轮的模型输出改掉
//	5. 认识吗            不在 32 个意图里 → 如实说不会，不瞎猜
//	6. 权限              走与页面完全相同的权限位
//	7. 执行              分意图落到各自的执行器（分阶段接入）
//	8. 落库              会话上下文 + 对话历史
//
// 这个顺序逐条对着原实现的 chat.py 抄的，不是重新设计的 ——
// 顺序换一下就会出现「确认词被当成新指令」这类难查的毛病。

// ChatRequest 是前端发上来的一轮。
type ChatRequest struct {
	Text       string `json:"text"`
	SessionKey string `json:"sessionKey"`
}

// ChatResponse 与原实现的 ChatResponse 字段一一对应 ——
// 前端那套渲染逻辑是照它写的，字段名不能改。
type ChatResponse struct {
	Reply         string              `json:"reply"`
	OutputSpeech  string              `json:"outputSpeech,omitempty"`
	Intent        string              `json:"intent"`
	Confidence    float64             `json:"confidence"`
	Slots         map[string][]string `json:"slots"`
	MissingSlots  []string            `json:"missingSlots"`
	DialogState   string              `json:"dialogStateDetail,omitempty"`
	Tokens        []string            `json:"tokens,omitempty"`
	Tags          []int               `json:"tags,omitempty"`
	ActionLog     []map[string]any    `json:"actionLog"`
	Diagnostics   []map[string]any    `json:"diagnostics"`
	Warnings      []map[string]any    `json:"warnings"`
	PendingAction map[string]any      `json:"pendingAction,omitempty"`
	// 下面三个是**给前端渲染按钮用的结构化提示**，让前端不必去解析
	// pendingAction 的内部结构。这个边界照搬原实现。
	Choices     []map[string]any `json:"choices,omitempty"`
	ConfirmKind string           `json:"confirmKind,omitempty"`
	UndoToken   map[string]any   `json:"undoToken,omitempty"`
}

// 听不懂时给的例句。逐字取自原实现的 _FAILURE_TEMPLATE_HINTS。
var failureHints = []string{
	"取消春季方案今天的早间播放",
	"把春季方案明天 8 点的任务挪到 9 点",
	"把方案1和方案2的早间任务对调",
	"播放儿歌到 1 号分区",
}

// 连续听不懂三次以上时给的快捷按钮。取自 _FAILURE_QUICK_ACTIONS。
var failureQuickActions = []map[string]any{
	{"label": "查询今天的作息", "value": "今天有哪些作息", "hint": ""},
	{"label": "取消今天的任务", "value": "取消今天的任务", "hint": ""},
	{"label": "播放音乐", "value": "播放音乐", "hint": ""},
}

// 不认识的指令时说的话。取自原实现的 OTHERS_MESSAGE。
const othersMessage = "您好！我是校园广播小助手小电。我目前主要负责设置播放任务、" +
	"取消广播记录以及调节音量。暂时还不会陪您聊天或处理其他事务哦。" +
	"您可以试着对我说：'明天早上八点播放国歌'。"

// Chat 处理一轮对话。
func (s *Service) Chat(ctx context.Context, u *auth.User, in ChatRequest) (*ChatResponse, error) {
	text := sanitize(strings.TrimSpace(in.Text), 8000)
	out := &ChatResponse{
		Slots:        map[string][]string{},
		MissingSlots: []string{},
		ActionLog:    []map[string]any{},
		Diagnostics:  []map[string]any{},
		Warnings:     []map[string]any{},
		Intent:       string(IntentNone),
	}
	if text == "" {
		out.Reply = "您想让我做什么？说一句就行，比如「取消今天的早读」。"
		return out, nil
	}

	sess, err := s.LoadSession(ctx, in.SessionKey, u.ID)
	if err != nil {
		return nil, err
	}

	// 用户这一轮说了什么，先落一条历史 —— 无论后面成不成，
	// 说过的话都要留痕（原实现也是先记 user 轮再记 assistant 轮）。
	_ = s.AppendMessage(ctx, &Message{
		SessionKey: in.SessionKey, UserID: u.ID, Username: u.Username,
		Role: "user", Text: text,
	})

	// ── 2. NLU ──
	res, err := s.nlu.Infer(ctx, text)
	if err != nil {
		out.Reply = NLUUnavailableReply
		out.DialogState = "nlu_unavailable"
		// 真实原因给运维看，不给用户看 —— 用户看不懂，也帮不上忙
		out.Diagnostics = append(out.Diagnostics, map[string]any{
			"kind": "nlu_error", "detail": errText(err),
		})
		s.finish(ctx, in, u, sess, out, text)
		return out, nil
	}

	out.Intent = string(res.Intent)
	out.Confidence = res.Confidence
	out.Slots = res.Slots
	out.Tokens = res.Tokens
	out.Tags = res.Tags

	// ── 3./4. 指代消解与锁定项（阶段 6 接入，先把会话读写打通）──

	// ── 5. 认识这个意图吗 ──
	if res.Intent == IntentNone || !Known(res.Intent) {
		sess.FailCount++
		out.Reply = s.failureReply(sess.FailCount)
		out.DialogState = "not_understood"
		if sess.FailCount >= 3 {
			out.Choices = failureQuickActions
		}
		s.finish(ctx, in, u, sess, out, text)
		return out, nil
	}
	sess.FailCount = 0

	// ── 6. 权限：与页面同一套 ──
	if ok, why := Allowed(u, res.Intent); !ok {
		out.Reply = why
		out.DialogState = "forbidden"
		out.Warnings = append(out.Warnings, map[string]any{
			"kind": "forbidden", "intent": string(res.Intent),
		})
		s.finish(ctx, in, u, sess, out, text)
		return out, nil
	}

	// ── 7. 执行 ──
	spec, _ := Spec(res.Intent)
	out.Reply = "我听懂了：" + spec.Title + "。这个动作还在接入中，暂时没有真正执行。"
	out.DialogState = "not_implemented"
	out.Warnings = append(out.Warnings, map[string]any{
		"kind": "not_implemented", "intent": string(res.Intent), "title": spec.Title,
	})

	sess.LastIntent = res.Intent
	sess.LastSlots = res.Slots
	s.finish(ctx, in, u, sess, out, text)
	return out, nil
}

// finish 收尾：落助手这一轮的历史、存会话、裁剪超量历史。
//
// 这几步的失败都**不**让整轮对话失败 —— 用户已经得到回答了，
// 因为写历史失败就回一个错误，是拿一个次要问题毁掉一次成功的交互。
func (s *Service) finish(ctx context.Context, in ChatRequest, u *auth.User,
	sess *Session, out *ChatResponse, text string) {

	status := "ok"
	switch out.DialogState {
	case "not_understood", "forbidden", "nlu_unavailable":
		status = "failed"
	case "not_implemented":
		status = "pending"
	}
	if err := s.AppendMessage(ctx, &Message{
		SessionKey: in.SessionKey, UserID: u.ID, Username: u.Username,
		Role: "assistant", Text: out.Reply, Intent: out.Intent,
		Confidence: out.Confidence, Slots: out.Slots,
		ActionLog: out.ActionLog, Status: status,
	}); err != nil {
		logf("写入历史失败: %v", err)
	}
	sess.SessionKey = in.SessionKey
	sess.UserID = u.ID
	if err := s.SaveSession(ctx, sess); err != nil {
		logf("保存会话失败: %v", err)
	}
	if _, err := s.TrimHistory(ctx, u.ID); err != nil {
		logf("裁剪历史失败: %v", err)
	}
}

// failureReply 按连续失败次数给不同的话，逐条对着原实现的
// _build_failure_hint_reply：第一次只说没听懂，第二次给例句，第三次起给按钮。
func (s *Service) failureReply(count int) string {
	switch {
	case count <= 1:
		return othersMessage
	case count == 2:
		return "还是没太明白。可以换个说法，比如：" + strings.Join(failureHints[:2], "；") + "。"
	default:
		return "我确实没听懂。要不直接点下面的按钮试试，或者照这样说：" +
			strings.Join(failureHints, "；") + "。"
	}
}

func errText(err error) string {
	if err == nil {
		return ""
	}
	if errors.Is(err, ErrNLUDisabled) {
		return "未配置 assistant.nlu_url，助手的识别服务没有启用"
	}
	return err.Error()
}
