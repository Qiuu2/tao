package assistant

import (
	"encoding/json"
	"os"
	"testing"
)

// 作息方案相关回话的黄金用例。期望值由
// nlu/golden/gen_schedule_reply_golden.py 生成 —— 直接调原实现的 runtime_reply。

func TestScheduleRepliesMatchPython(t *testing.T) {
	raw, err := os.ReadFile("testdata/reply_schedule_golden.json")
	if err != nil {
		t.Fatalf("读黄金用例: %v", err)
	}
	// 方案名那一路的参数在 JSON 里也叫 name，与用例名撞了，
	// 所以先解成 map 再各取所需。
	var rows []map[string]any
	if err := json.Unmarshal(raw, &rows); err != nil {
		t.Fatalf("解析黄金用例: %v", err)
	}
	if len(rows) == 0 {
		t.Fatal("黄金用例是空的")
	}

	str := func(m map[string]any, k string) string {
		v, _ := m[k].(string)
		return v
	}

	for _, m := range rows {
		kind := str(m, "kind")
		caseName := str(m, "name")
		want := str(m, "want")
		t.Run(caseName, func(t *testing.T) {
			var got string
			switch kind {
			case "schedule_not_found":
				got = replyScheduleNotFound(str(m, "name_arg"))
			case "no_matching_tasks":
				got = replyNoMatchingTasks(str(m, "action_label"), nil)
			case "confirm":
				got = confirmRuntimeReply(str(m, "intent"), str(m, "summary"), str(m, "question"))
			case "ask":
				got = askRuntimeReply(str(m, "intent"), str(m, "need"), str(m, "example"))
			default:
				t.Fatalf("用例类型 %q 没有对应的调用方式", kind)
			}
			if got != want {
				t.Fatalf("回话不一致\n期望（Python）: %s\n实际（Go）    : %s", want, got)
			}
		})
	}
}
