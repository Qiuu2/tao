package assistant

import (
	"encoding/json"
	"os"
	"testing"
)

// 回话措辞的 1:1 校验。
//
// testdata/reply_golden.json 里的 want 是**拿原 Python 实现真跑出来的输出**
// （backend/assistant/runtime_reply.py，2ada1e9 那一版），不是手写的期望值。
// 只要 Go 版有任何一处与它不一致，这个测试就红 ——
// 这是"1:1 复刻"这句话唯一说得清的证明方式。
//
// 重新生成基准的办法写在《AI助手迁移-现状分析》里。

type goldenFile struct {
	Query []struct {
		Intent  string `json:"intent"`
		Desc    string `json:"desc"`
		Online  int    `json:"online"`
		Offline int    `json:"offline"`
		Unknown int    `json:"unknown"`
		Want    string `json:"want"`
	} `json:"query"`
	Preview []struct {
		Vals  []string `json:"vals"`
		Limit int      `json:"limit"`
		Noun  string   `json:"noun"`
		Want  string   `json:"want"`
	} `json:"preview"`
	Finalize []struct {
		Intent  string   `json:"intent"`
		Reply   string   `json:"reply"`
		Details []string `json:"details"`
		Want    string   `json:"want"`
	} `json:"finalize"`
}

func loadGolden(t *testing.T) goldenFile {
	t.Helper()
	raw, err := os.ReadFile("testdata/reply_golden.json")
	if err != nil {
		t.Fatalf("读取基准数据: %v", err)
	}
	var g goldenFile
	if err := json.Unmarshal(raw, &g); err != nil {
		t.Fatalf("解析基准数据: %v", err)
	}
	return g
}

func TestQueryRuntimeReplyMatchesPython(t *testing.T) {
	g := loadGolden(t)
	if len(g.Query) == 0 {
		t.Fatal("基准数据里没有 query 用例")
	}
	for _, c := range g.Query {
		var variants []string
		switch c.Intent {
		case "query_terminal":
			variants = queryTerminalVariants
		case "check_terminal":
			variants = checkTerminalVariants
		default:
			t.Fatalf("基准里出现了未知意图 %s", c.Intent)
		}
		got := queryRuntimeReply(c.Intent, variants,
			[]string{c.Desc, seedNum(c.Online), seedNum(c.Offline), seedNum(c.Unknown)},
			map[string]string{
				"terminal_desc": c.Desc,
				"online":        itoa(c.Online),
				"offline":       itoa(c.Offline),
				"unknown":       itoa(c.Unknown),
			})
		if got != c.Want {
			t.Errorf("%s / %q:\n  Go   = %q\n  原版 = %q", c.Intent, c.Desc, got, c.Want)
		}
	}
}

func TestPreviewNamesMatchesPython(t *testing.T) {
	for _, c := range loadGolden(t).Preview {
		if got := previewNames(c.Vals, c.Limit, c.Noun); got != c.Want {
			t.Errorf("previewNames(%v,%d,%q):\n  Go   = %q\n  原版 = %q",
				c.Vals, c.Limit, c.Noun, got, c.Want)
		}
	}
}

func TestFinalizeKeyIntentReplyMatchesPython(t *testing.T) {
	for _, c := range loadGolden(t).Finalize {
		if got := finalizeKeyIntentReply(c.Intent, c.Reply, c.Details...); got != c.Want {
			t.Errorf("finalize(%s):\n  Go   = %q\n  原版 = %q", c.Intent, got, c.Want)
		}
	}
}

// TestStableReplyIsStable 保证同样输入永远同样输出 ——
// 这是整套措辞机制的前提，一旦哈希实现被"优化"掉就会破。
func TestStableReplyIsStable(t *testing.T) {
	variants := []string{"甲{x}", "乙{x}", "丙{x}"}
	first := stableReply("demo", variants, []string{"a", "b"}, map[string]string{"x": "！"})
	for i := 0; i < 50; i++ {
		if got := stableReply("demo", variants, []string{"a", "b"}, map[string]string{"x": "！"}); got != first {
			t.Fatalf("第 %d 次结果变了: %q != %q", i, got, first)
		}
	}
	// 换了种子就该换一句（否则等于没在选）
	other := stableReply("demo", variants, []string{"c"}, map[string]string{"x": "！"})
	if other == first {
		t.Logf("注意：不同种子选到了同一句（%q），三选一时有概率碰上，不算错", first)
	}
}
