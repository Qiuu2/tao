package assistant

import (
	"encoding/json"
	"os"
	"testing"
)

// 终端与分区意图的回话黄金用例。期望值由
// nlu/golden/gen_terminal_reply_golden.py 生成 —— 直接调原实现的 runtime_reply。
//
// 分区那几条走的是 stable_reply（没有 light 外壳），终端那几条走 success_runtime
// （有外壳）。这个区别不是笔误，是原实现里就分两路，抄错一路措辞就整批变。

type terminalReplyCase struct {
	Name         string `json:"name"`
	Kind         string `json:"kind"`
	Want         string `json:"want"`
	Intent       string `json:"intent"`
	TerminalDesc string `json:"terminal_desc"`
	StateText    string `json:"state_text"`
	ScopeDesc    string `json:"scope_desc"`
	Volume       int    `json:"volume"`
	ZoneDesc     string `json:"zone_desc"`
	ActionLabel  string `json:"action_label"`
	Need         string `json:"need"`
	Example      string `json:"example"`
	Topic        string `json:"topic"`
	Reason       string `json:"reason"`
	Suggestion   string `json:"suggestion"`
}

func TestTerminalZoneRepliesMatchPython(t *testing.T) {
	raw, err := os.ReadFile("testdata/reply_terminal_golden.json")
	if err != nil {
		t.Fatalf("读黄金用例: %v", err)
	}
	var cases []terminalReplyCase
	if err := json.Unmarshal(raw, &cases); err != nil {
		t.Fatalf("解析黄金用例: %v", err)
	}
	if len(cases) == 0 {
		t.Fatal("黄金用例是空的")
	}

	for _, c := range cases {
		t.Run(c.Name, func(t *testing.T) {
			var got string
			switch c.Kind {
			case "term_state":
				got = successRuntimeReply(c.Intent, terminalStateVariants,
					[]string{c.TerminalDesc, c.StateText},
					map[string]string{"terminal_desc": c.TerminalDesc, "state_text": c.StateText})
			case "sync":
				got = successRuntimeReply(c.Intent, syncTerminalTimeVariants,
					[]string{c.TerminalDesc},
					map[string]string{"terminal_desc": c.TerminalDesc})
			case "term_volume":
				got = successRuntimeReply(c.Intent, adjustVolumeVariants,
					[]string{c.ScopeDesc, seedNum(c.Volume)},
					map[string]string{"scope_desc": c.ScopeDesc, "volume": itoa(c.Volume)})
			case "create_zone":
				got = stableReply(c.Intent, createZoneVariants,
					[]string{c.ZoneDesc}, map[string]string{"zone_desc": c.ZoneDesc})
			case "delete_zone":
				got = stableReply(c.Intent, deleteZoneVariants,
					[]string{c.ZoneDesc}, map[string]string{"zone_desc": c.ZoneDesc})
			case "zone_member":
				variants := removeTerminalFromZoneVariants
				if c.ActionLabel == "加入" {
					variants = addTerminalToZoneVariants
				}
				got = stableReply(c.Intent, variants,
					[]string{c.ZoneDesc, c.TerminalDesc, c.ActionLabel},
					map[string]string{"zone_desc": c.ZoneDesc,
						"terminal_desc": c.TerminalDesc, "action_label": c.ActionLabel})
			case "ask":
				got = askRuntimeReply(c.Intent, c.Need, c.Example)
			case "failure":
				got = failureRuntimeReply(c.Intent, c.Topic, nil, c.Reason, c.Suggestion)
			default:
				t.Fatalf("用例类型 %q 没有对应的调用方式", c.Kind)
			}
			if got != c.Want {
				t.Fatalf("回话不一致\n期望（Python）: %s\n实际（Go）    : %s", c.Want, got)
			}
		})
	}
}
