package assistant

import (
	"bytes"
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"net/http"
	"strings"
	"sync"
	"time"
)

// NLU 客户端：把一句中文丢给本机的推理服务，拿回意图与槽位。
//
// # 契约（与 nlu/server.py 一一对应）
//
//	POST /infer   {"text": "把早读挪到9点"}
//	→ 200 {"intent":"move_schedule","confidence":0.97,
//	       "slots":{"time":["9点"],"content":["早读"]},
//	       "tokens":["把","早",...],"tags":[0,1,...]}
//
// tokens/tags 是模型的原始输出，只用于调试面板，业务逻辑不看它们。
//
// # 这一层不做业务判断
//
// 置信度低于阈值时 Python 侧已经回 intent="none"，Go 这边不再自己定阈值 ——
// 阈值属于模型的一部分，两处各定一个迟早会对不上。
//
// # 服务不可用怎么办
//
// **如实说，不猜**。原实现在这种情况下回「当前网络不稳定，请重新发送。」，
// 照搬这句话术（用户已经习惯了），同时在 diagnostics 里带上真实原因给运维看。
type NLU struct {
	url     string
	client  *http.Client
	enabled bool

	// 健康状态只用于 /api/assistant/status 展示，不参与请求路径的判断 ——
	// 每次请求该打还是打，探活结果过期了反而会误伤。
	mu       sync.RWMutex
	lastErr  string
	lastOKAt time.Time
}

// NLUUnavailableReply 是 NLU 不可用时对用户说的话。
// 逐字取自原实现的 ASSISTANT_UNAVAILABLE_REPLY。
const NLUUnavailableReply = "当前网络不稳定，请重新发送。"

// ErrNLUDisabled 表示压根没配 NLU 地址。
var ErrNLUDisabled = errors.New("未配置 NLU 服务地址")

func NewNLU(url string, timeout time.Duration) *NLU {
	url = strings.TrimRight(strings.TrimSpace(url), "/")
	return &NLU{
		url:     url,
		enabled: url != "",
		client:  &http.Client{Timeout: timeout},
	}
}

// Result 是一次推理的结果。
type Result struct {
	Intent     Intent              `json:"intent"`
	Confidence float64             `json:"confidence"`
	Slots      map[string][]string `json:"slots"`
	Tokens     []string            `json:"tokens"`
	Tags       []int               `json:"tags"`
}

// Infer 跑一次推理。
func (n *NLU) Infer(ctx context.Context, text string) (*Result, error) {
	if !n.enabled {
		return nil, ErrNLUDisabled
	}
	body, err := json.Marshal(map[string]string{"text": text})
	if err != nil {
		return nil, err
	}
	req, err := http.NewRequestWithContext(ctx, http.MethodPost, n.url+"/infer", bytes.NewReader(body))
	if err != nil {
		return nil, err
	}
	req.Header.Set("Content-Type", "application/json")

	resp, err := n.client.Do(req)
	if err != nil {
		n.note(err.Error())
		return nil, fmt.Errorf("调用 NLU 服务: %w", err)
	}
	defer resp.Body.Close()

	// 限一下响应体：推理结果就几百字节，读到 1MB 还没完说明对面不是我们的服务
	raw, err := io.ReadAll(io.LimitReader(resp.Body, 1<<20))
	if err != nil {
		n.note(err.Error())
		return nil, fmt.Errorf("读取 NLU 响应: %w", err)
	}
	if resp.StatusCode != http.StatusOK {
		msg := fmt.Sprintf("NLU 服务返回 %d: %s", resp.StatusCode, compact(string(raw), 200))
		n.note(msg)
		return nil, errors.New(msg)
	}

	var out Result
	if err := json.Unmarshal(raw, &out); err != nil {
		msg := fmt.Sprintf("NLU 响应不是预期的 JSON: %s", compact(string(raw), 200))
		n.note(msg)
		return nil, errors.New(msg)
	}
	if out.Slots == nil {
		out.Slots = map[string][]string{}
	}
	if out.Intent == "" {
		out.Intent = IntentNone
	}
	n.ok()
	return &out, nil
}

// Health 探一次活，给状态接口用。
func (n *NLU) Health(ctx context.Context) error {
	if !n.enabled {
		return ErrNLUDisabled
	}
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, n.url+"/healthz", nil)
	if err != nil {
		return err
	}
	resp, err := n.client.Do(req)
	if err != nil {
		n.note(err.Error())
		return err
	}
	defer resp.Body.Close()
	_, _ = io.Copy(io.Discard, io.LimitReader(resp.Body, 4096))
	if resp.StatusCode != http.StatusOK {
		msg := fmt.Sprintf("NLU /healthz 返回 %d", resp.StatusCode)
		n.note(msg)
		return errors.New(msg)
	}
	n.ok()
	return nil
}

// Status 报告 NLU 的可用情况。
func (n *NLU) Status() (enabled bool, url string, lastErr string, lastOK time.Time) {
	n.mu.RLock()
	defer n.mu.RUnlock()
	return n.enabled, n.url, n.lastErr, n.lastOKAt
}

func (n *NLU) note(msg string) {
	n.mu.Lock()
	n.lastErr = msg
	n.mu.Unlock()
}

func (n *NLU) ok() {
	n.mu.Lock()
	n.lastErr = ""
	n.lastOKAt = time.Now()
	n.mu.Unlock()
}

func compact(s string, max int) string {
	s = strings.Join(strings.Fields(s), " ")
	if len(s) > max {
		return s[:max] + "…"
	}
	return s
}
