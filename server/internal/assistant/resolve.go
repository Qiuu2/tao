package assistant

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"regexp"
	"sort"
	"strings"
)

// 名称解析：把模型抽出来的一段文字（「A101教室」「方案1」）对到库里真实的名字上。
//
// # 为什么不能只做精确匹配
//
// 模型抽的是用户说的话，用户说「A101」而库里叫「A101教室音箱」。
// 只做精确匹配的表现是"我明明说了它却说没找到"。
//
// # 四层，顺序照搬原实现的 _select_candidate_with_raw
//
//	① 抽出来的就是候选之一        → 直接用，分 1.0
//	② 恰好一个候选**原样出现在整句话里** → 用它，分 1.0
//	                              （并列最长时，抽出来的那个能定夺就用它，
//	                                否则落到第 ③ 层，用低分表达"不确定"）
//	③ 模糊匹配（rapidfuzz，走旁挂服务）
//	④ 数字安全兜底：③ 选中的候选**数字轮廓与抽出来的对不上**时拒绝，
//	   除非恰好只有一个候选保住了数字
//
// # 第 ④ 层是最要紧的
//
// 「方案1」和「方案2」只差一个数字，模糊匹配分数几乎一样，随便选一个的后果是
// **改错方案** —— 用户说取消方案1，结果取消了方案2。所以宁可拒绝也不猜。
// 中文数字（「方案一」）先归一成阿拉伯数字再比。
//
// # 模糊那一层为什么在 Python 侧
//
// 原实现用 rapidfuzz 的 WRatio。在 Go 里重写它一旦有偏差就是"匹配结果悄悄变了"，
// 极难测出来。所以候选名单由这边从数据库查（新鲜、权威），打分交给旁挂服务用
// 同一个库做。rapidfuzz 没装时那一层整层失效 —— 与原实现的降级行为一致。

// Candidate 是一个候选名字及其 id。
type Candidate struct {
	ID   int64
	Name string
}

// Resolution 是一次解析的结果。
type Resolution struct {
	Matched string
	ID      int64
	Score   float64
	// Tier 记录是哪一层命中的，排查"为什么匹配到这个"时有用。
	Tier string
}

// 中文数字到阿拉伯数字。取自 _CN_DIGIT_MAP。
var cnDigits = map[rune]string{
	'零': "0", '〇': "0", '一': "1", '二': "2", '两': "2",
	'三': "3", '四': "4", '五': "5", '六': "6", '七': "7",
	'八': "8", '九': "9", '十': "10",
}

var digitRun = regexp.MustCompile(`\d+`)

// digitProfile 抽出一段文字里按顺序出现的数字组。取自 _digit_profile。
func digitProfile(text string) []string {
	if text == "" {
		return nil
	}
	var b strings.Builder
	for _, r := range text {
		if d, ok := cnDigits[r]; ok {
			b.WriteString(d)
		} else {
			b.WriteRune(r)
		}
	}
	return digitRun.FindAllString(b.String(), -1)
}

func sameDigits(a, b []string) bool {
	if len(a) != len(b) {
		return false
	}
	for i := range a {
		if a[i] != b[i] {
			return false
		}
	}
	return true
}

// ResolveName 按四层规则把 extracted 对到 candidates 里的一个。
//
// raw 是用户这一整句原话 —— 第 ② 层要在里面找候选，没有它就少一层。
func (s *Service) ResolveName(ctx context.Context, extracted, raw string, candidates []Candidate) Resolution {
	if len(candidates) == 0 {
		return Resolution{}
	}
	extracted = strings.TrimSpace(extracted)
	raw = strings.TrimSpace(raw)

	byName := map[string]int64{}
	names := make([]string, 0, len(candidates))
	for _, c := range candidates {
		n := strings.TrimSpace(c.Name)
		if n == "" {
			continue
		}
		if _, dup := byName[n]; !dup {
			names = append(names, n)
		}
		byName[n] = c.ID
	}

	// ① 抽出来的就是候选之一
	if extracted != "" {
		if id, ok := byName[extracted]; ok {
			return Resolution{Matched: extracted, ID: id, Score: 1.0, Tier: "exact"}
		}
	}

	// ② 原样出现在整句话里
	if raw != "" {
		hits := []string{}
		for _, n := range names {
			if strings.Contains(raw, n) {
				hits = append(hits, n)
			}
		}
		// 长的优先 —— 「A101教室音箱」比「A101」更具体
		sort.SliceStable(hits, func(i, j int) bool { return len([]rune(hits[i])) > len([]rune(hits[j])) })
		if len(hits) > 0 {
			if len(hits) == 1 || len([]rune(hits[0])) > len([]rune(hits[1])) {
				return Resolution{Matched: hits[0], ID: byName[hits[0]], Score: 1.0, Tier: "verbatim"}
			}
			// 并列最长：抽出来的那个在里面就用它定夺
			topLen := len([]rune(hits[0]))
			for _, h := range hits {
				if len([]rune(h)) == topLen && h == extracted {
					return Resolution{Matched: h, ID: byName[h], Score: 1.0, Tier: "verbatim-tie"}
				}
			}
			// 仍然并列 → 落到模糊层，用低于 1.0 的分数表达"不确定"
		}
	}

	if extracted == "" {
		return Resolution{}
	}

	// ③ 模糊匹配
	if s.matchOne == nil {
		return Resolution{}
	}
	matched, score, err := s.matchOne(ctx, extracted, names, 60)
	if err != nil {
		logf("模糊匹配失败（降级为只用精确层）: %v", err)
		return Resolution{}
	}
	if matched == "" {
		return Resolution{}
	}

	// ④ 数字安全
	extDigits := digitProfile(extracted)
	if len(extDigits) > 0 && !sameDigits(extDigits, digitProfile(matched)) {
		target := extDigits[len(extDigits)-1]
		rescue := []string{}
		for _, n := range names {
			for _, d := range digitProfile(n) {
				if d == target {
					rescue = append(rescue, n)
					break
				}
			}
		}
		if len(rescue) == 1 {
			return Resolution{Matched: rescue[0], ID: byName[rescue[0]], Score: 0.9, Tier: "digit-rescue"}
		}
		// 数字对不上又救不回来 —— 宁可说没找到，也不能改错对象
		return Resolution{}
	}
	return Resolution{Matched: matched, ID: byName[matched], Score: score, Tier: "fuzzy"}
}

// MatchOne 调旁挂服务做一次模糊匹配。
func (n *NLU) MatchOne(ctx context.Context, value string, candidates []string, cutoff float64) (string, float64, error) {
	if !n.enabled || len(candidates) == 0 || strings.TrimSpace(value) == "" {
		return "", 0, nil
	}
	body, err := json.Marshal(map[string]interface{}{
		"value": value, "candidates": candidates, "cutoff": cutoff,
	})
	if err != nil {
		return "", 0, err
	}
	req, err := http.NewRequestWithContext(ctx, http.MethodPost, n.url+"/match", bytes.NewReader(body))
	if err != nil {
		return "", 0, err
	}
	req.Header.Set("Content-Type", "application/json")
	resp, err := n.client.Do(req)
	if err != nil {
		n.note(err.Error())
		return "", 0, fmt.Errorf("调用名称匹配: %w", err)
	}
	defer resp.Body.Close()
	raw, err := io.ReadAll(io.LimitReader(resp.Body, 1<<20))
	if err != nil {
		return "", 0, err
	}
	if resp.StatusCode != http.StatusOK {
		return "", 0, fmt.Errorf("名称匹配返回 %d: %s", resp.StatusCode, compact(string(raw), 200))
	}
	var out struct {
		Matched string  `json:"matched"`
		Score   float64 `json:"score"`
		Fuzzy   bool    `json:"fuzzy"`
	}
	if err := json.Unmarshal(raw, &out); err != nil {
		return "", 0, fmt.Errorf("名称匹配响应异常: %s", compact(string(raw), 200))
	}
	return out.Matched, out.Score, nil
}
