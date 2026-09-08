package assistant

import (
	"context"
	"encoding/json"
	"errors"
	"math"
	"os"
	"testing"
)

// 名称解析的黄金用例。期望值是**跑原实现的 Python 生成的**，不是我照着代码推的 ——
// 推出来的期望值只能证明我读懂了自己写的代码，证明不了行为一致。
//
// 模糊那一层用桩替掉：桩返回的就是原实现里 rapidfuzz 会返回的结果
// （用例文件里的 fuzzy 字段）。这样四层调度逻辑能脱离 2G 的模型依赖单独验证，
// 而模糊打分本身的一致性由「两边用的是同一个 rapidfuzz」保证。

type resolveCase struct {
	Name        string   `json:"name"`
	Extracted   string   `json:"extracted"`
	Raw         string   `json:"raw"`
	Candidates  []string `json:"candidates"`
	Fuzzy       []any    `json:"fuzzy"`
	WantMatched string   `json:"want_matched"`
	WantScore   float64  `json:"want_score"`
}

func TestResolveNameMatchesPython(t *testing.T) {
	raw, err := os.ReadFile("testdata/resolve_golden.json")
	if err != nil {
		t.Fatalf("读黄金用例: %v", err)
	}
	var cases []resolveCase
	if err := json.Unmarshal(raw, &cases); err != nil {
		t.Fatalf("解析黄金用例: %v", err)
	}
	if len(cases) == 0 {
		t.Fatal("黄金用例是空的")
	}

	for _, c := range cases {
		t.Run(c.Name, func(t *testing.T) {
			s := &Service{}
			if c.Fuzzy != nil {
				want, _ := c.Fuzzy[0].(string)
				score, _ := c.Fuzzy[1].(float64)
				s.matchOne = func(context.Context, string, []string, float64) (string, float64, error) {
					return want, score, nil
				}
			} else {
				// fuzzy 为空 = 原实现里模糊层没给出结果（分数不到 cutoff，
				// 或者 rapidfuzz 根本没装）。两种情况的返回都是"没匹配上"。
				s.matchOne = func(context.Context, string, []string, float64) (string, float64, error) {
					return "", 0, nil
				}
			}

			cands := make([]Candidate, 0, len(c.Candidates))
			for i, n := range c.Candidates {
				cands = append(cands, Candidate{ID: int64(i + 1), Name: n})
			}

			got := s.ResolveName(context.Background(), c.Extracted, c.Raw, cands)
			if got.Matched != c.WantMatched {
				t.Fatalf("匹配结果不一致\n抽取: %q\n原话: %q\n候选: %v\n期望: %q（Python）\n实际: %q（Go，命中层 %s）",
					c.Extracted, c.Raw, c.Candidates, c.WantMatched, got.Matched, got.Tier)
			}
			if math.Abs(got.Score-c.WantScore) > 1e-9 {
				t.Fatalf("分数不一致：期望 %v，实际 %v（命中层 %s）", c.WantScore, got.Score, got.Tier)
			}
			// 匹配上了就必须能给出 id —— 只给名字的话调用方还得再查一次库。
			if got.Matched != "" && got.ID == 0 {
				t.Fatalf("匹配到 %q 但没带回 id", got.Matched)
			}
		})
	}
}

// 模糊层报错时必须是"没找到"，不能是"随便给一个"。
// 旁挂服务挂掉的表现应当是助手说找不到，而不是助手去操作一个猜出来的对象。
func TestResolveNameFuzzyErrorFallsBackToNoMatch(t *testing.T) {
	s := &Service{matchOne: func(context.Context, string, []string, float64) (string, float64, error) {
		return "A102教室音箱", 0.99, errors.New("旁挂服务连不上")
	}}
	got := s.ResolveName(context.Background(), "A101教室", "查一下", []Candidate{
		{ID: 1, Name: "A101教室音箱"}, {ID: 2, Name: "A102教室音箱"},
	})
	if got.Matched != "" {
		t.Fatalf("模糊层报错时不该给出匹配，却给了 %q", got.Matched)
	}
}

func TestDigitProfile(t *testing.T) {
	cases := []struct {
		in   string
		want []string
	}{
		{"方案1", []string{"1"}},
		{"方案一", []string{"1"}},
		{"三号机", []string{"3"}},
		{"A101教室音箱", []string{"101"}},
		{"广播室主话筒", nil},
		{"", nil},
		{"十点半", []string{"10"}},
	}
	for _, c := range cases {
		got := digitProfile(c.in)
		if len(got) != len(c.want) {
			t.Fatalf("%q：期望 %v，实际 %v", c.in, c.want, got)
		}
		for i := range got {
			if got[i] != c.want[i] {
				t.Fatalf("%q：期望 %v，实际 %v", c.in, c.want, got)
			}
		}
	}
}
