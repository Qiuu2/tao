package assistant

import (
	"encoding/json"
	"os"
	"testing"
)

// 取消相关回话的黄金用例。期望值由 nlu/golden/gen_cancel_reply_golden.py 生成。

func TestCancelRepliesMatchPython(t *testing.T) {
	raw, err := os.ReadFile("testdata/reply_cancel_golden.json")
	if err != nil {
		t.Fatalf("读黄金用例: %v", err)
	}
	var rows []map[string]any
	if err := json.Unmarshal(raw, &rows); err != nil {
		t.Fatalf("解析黄金用例: %v", err)
	}
	if len(rows) == 0 {
		t.Fatal("黄金用例是空的")
	}
	str := func(m map[string]any, k string) string { v, _ := m[k].(string); return v }
	num := func(m map[string]any, k string) int { v, _ := m[k].(float64); return int(v) }

	for _, m := range rows {
		t.Run(str(m, "name"), func(t *testing.T) {
			var got string
			switch str(m, "kind") {
			case "once_cancel_done":
				got = successRuntimeReply("once_cancel_done", onceCancelDoneVariants,
					[]string{str(m, "diagnostic_id")}, nil)
			case "cancel_once_summary":
				got = composeCancelOnceReply(num(m, "schedule_group_count"),
					num(m, "schedule_task_count"), num(m, "broadcast_group_count"),
					num(m, "broadcast_task_count"))
			case "ask":
				got = askRuntimeReply(str(m, "intent"), str(m, "need"), str(m, "example"))
			default:
				t.Fatalf("用例类型 %q 没有对应的调用方式", str(m, "kind"))
			}
			if want := str(m, "want"); got != want {
				t.Fatalf("回话不一致\n期望（Python）: %s\n实际（Go）    : %s", want, got)
			}
		})
	}
}
