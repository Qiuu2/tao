package httpx

import (
	"errors"
	"strings"
	"testing"
)

// 「库里少表 / 少列」要翻成人话，别的一律维持笼统的「服务器内部错误」。
//
// ⚠ 判据是**错误码**不是英文措辞：MariaDB / MySQL 的错误文本在不同版本里改过，
// 错误码没改过。拿文本匹配的话，换个版本这一层就静默失效了。
func TestSchemaGapMessageOnlyCoversMissingTableAndColumn(t *testing.T) {
	cases := []struct {
		name string
		err  error
		want bool
	}{
		{
			"少表（1146）",
			errors.New("查询默认噪声值: Error 1146 (42S02): Table 'audioserver.soundtask' doesn't exist"),
			true,
		},
		{
			"少列（1054）",
			errors.New("查询用户组权限: Error 1054 (42S22): Unknown column 'soundtaskpriv' in 'field list'"),
			true,
		},
		{
			"语法错误 —— 这是程序 bug，原文里可能带着拼好的 SQL 和参数",
			errors.New("Error 1064 (42000): You have an error in your SQL syntax near 'WHRE id = 3'"),
			false,
		},
		{"死锁", errors.New("Error 1213: Deadlock found when trying to get lock"), false},
		{"连不上", errors.New("dial tcp 127.0.0.1:3306: connect: connection refused"), false},
		{"空", nil, false},
	}
	for _, c := range cases {
		got := schemaGapMessage(c.err) != ""
		if got != c.want {
			t.Errorf("%s：翻成人话 = %v，想要 %v", c.name, got, c.want)
		}
	}
}

// 翻出来的那句话要**说得出下一步做什么**，不然和笼统报错没区别。
func TestSchemaGapMessageTellsThemWhatToDo(t *testing.T) {
	msg := schemaGapMessage(errors.New("Error 1146: Table 'audioserver.enable_run' doesn't exist"))
	if msg == "" {
		t.Fatal("1146 应该翻出来")
	}
	for _, want := range []string{"升级脚本", "db/", "日志"} {
		if !strings.Contains(msg, want) {
			t.Errorf("这句话里该提到「%s」，实际是：%s", want, msg)
		}
	}
}

// ⚠ 不能把表名列名之外的东西漏出去。这一层翻出来的是**固定文案**，
// 不拼接原始错误 —— 原始错误只进服务器日志。
func TestSchemaGapMessageDoesNotLeakTheRawError(t *testing.T) {
	raw := "Error 1146: Table 'audioserver.secret_table' doesn't exist; query was SELECT pwd FROM x WHERE token='abc123'"
	msg := schemaGapMessage(errors.New(raw))
	if msg == "" {
		t.Fatal("1146 应该翻出来")
	}
	for _, leak := range []string{"secret_table", "abc123", "SELECT"} {
		if strings.Contains(msg, leak) {
			t.Errorf("回给客户端的话里漏出了原始错误的内容「%s」：%s", leak, msg)
		}
	}
}
