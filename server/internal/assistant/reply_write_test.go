package assistant

import (
	"encoding/json"
	"os"
	"testing"
)

// 写操作三类回话（成功 / 追问 / 失败）的黄金用例。
//
// 期望值由 nlu/golden/gen_reply_golden.py 生成 —— 那个脚本直接 import
// 原实现的 runtime_reply 模块（它只依赖 hashlib 和 re，能直接跑），
// 连抠函数体都不用，调的就是原版函数。

type writeReplyCase struct {
	Name         string `json:"name"`
	Kind         string `json:"kind"`
	Want         string `json:"want"`
	Intent       string `json:"intent"`
	TaskName     string `json:"task_name"`
	ScheduleName string `json:"schedule_name"`
	ActionLabel  string `json:"action_label"`
	ScopeDesc    string `json:"scope_desc"`
	Volume       int    `json:"volume"`
	Need         string `json:"need"`
	Example      string `json:"example"`
	Topic        string `json:"topic"`
	Reason       string `json:"reason"`
	Suggestion   string `json:"suggestion"`
}

func TestWriteRepliesMatchPython(t *testing.T) {
	raw, err := os.ReadFile("testdata/reply_write_golden.json")
	if err != nil {
		t.Fatalf("读黄金用例: %v", err)
	}
	var cases []writeReplyCase
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
			case "success_plain":
				got = successRuntimeReply(c.Intent, taskStateVariants,
					[]string{c.TaskName},
					map[string]string{"task_name": c.TaskName, "action_label": c.ActionLabel})
			case "success_scoped":
				got = successRuntimeReply(c.Intent, taskStateScopedVariants,
					[]string{c.ScheduleName, c.TaskName},
					map[string]string{"schedule_name": c.ScheduleName,
						"task_name": c.TaskName, "action_label": c.ActionLabel})
			case "success_volume":
				got = successRuntimeReply(c.Intent, adjustVolumeVariants,
					[]string{c.ScopeDesc, seedNum(c.Volume)},
					map[string]string{"scope_desc": c.ScopeDesc, "volume": itoa(c.Volume)})
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
