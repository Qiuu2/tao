package auth

import (
	"strings"
	"testing"
)

// 权限位的列清单在四个地方要保持同一个顺序（登录读、用户组列表读、新建写、修改写），
// 所以只留 RightColumns 一份，另外两个函数照它排。
//
// 三者一旦长度对不上，SQL 会在运行时报 "number of variables doesn't match"；
// 长度对得上但顺序不同，**不会报任何错**，表现是「勾了 A、生效的是 B」，
// 而且只在有人真去用那个权限时才看得出来。所以这条必须有测试盯着。
func TestRightColumnsTargetsValuesStayInSync(t *testing.T) {
	cols := strings.Split(RightColumns, ",")
	n := 0
	for _, c := range cols {
		if strings.TrimSpace(c) != "" {
			n++
		}
	}
	if n != RightCount {
		t.Errorf("RightColumns 有 %d 列，RightCount 写的是 %d", n, RightCount)
	}
	var r Rights
	if got := len(RightTargets(&r)); got != n {
		t.Errorf("RightTargets 有 %d 个，列有 %d 个", got, n)
	}
	if got := len(RightValues(r)); got != n {
		t.Errorf("RightValues 有 %d 个，列有 %d 个", got, n)
	}
}

// 顺序对不对，用「给第 i 个目标写 i、再从 RightValues 读回来」验。
// 这一条能抓住「两边都有 22 个但排序不同」——那种错 SQL 不会报。
func TestRightTargetsAndValuesShareTheSameOrder(t *testing.T) {
	var r Rights
	targets := RightTargets(&r)
	for i, tg := range targets {
		p, ok := tg.(*int)
		if !ok {
			t.Fatalf("第 %d 个目标不是 *int", i)
		}
		*p = i + 1
	}
	for i, v := range RightValues(r) {
		if v.(int) != i+1 {
			t.Errorf("第 %d 位错位了：写进去 %d，读回来 %v", i, i+1, v)
		}
	}
}

// by() 要认得每一个列名。少写一个 case 的表现是「这一项权限永远为 0」——
// 勾上了也进不去，而界面上看不出任何异常。
func TestEveryColumnHasACaseInBy(t *testing.T) {
	var r Rights
	targets := RightTargets(&r)
	cols := []string{}
	for _, c := range strings.Split(RightColumns, ",") {
		if c = strings.TrimSpace(c); c != "" {
			cols = append(cols, c)
		}
	}
	for i, col := range cols {
		// 只把第 i 位置成 1，by(col) 必须正好读到它
		for _, tg := range targets {
			*(tg.(*int)) = 0
		}
		*(targets[i].(*int)) = 1
		if got := r.by(col); got != 1 {
			t.Errorf("by(%q) = %d，想要 1 —— 多半是 by() 里少了这一条 case，"+
				"或者它读的是别的字段", col, got)
		}
	}
}

// 左侧菜单里每一项功能都该有自己的权限位（现场 2026-09-15 的要求）。
// 这一条把那张对照表钉死：漏加一列，或者改名之后忘了同步，都会红。
func TestMenuPagesEachHaveTheirOwnPriv(t *testing.T) {
	want := map[string]string{
		"终端管理":    PrivTerminal,
		"终端分区":    PrivTerminalGroup,
		"报警分区/映射": PrivAlarmGroup,
		"文件管理·媒体": PrivMedia,
		"文件管理·目录": PrivFolder,
		"地图":      PrivMap,
		"文件广播":    PrivTask,
		"作息方案":    PrivBell,
		"终端功放":    PrivPowerPlay,
		"采播管理":    PrivAdm,
		"文字语音":    PrivTts,
		"led播放":   PrivLed,
		"启用管理":    PrivEnable,
		"云广播终端":   PrivCloudTerminal,
		"音乐传输":    PrivOffline,
		"任务传送":    PrivTransfer,
		"噪声设备":    PrivNoiseDev,
		"声场分区":    PrivSoundZone,
		"声场任务":    PrivSoundTask,
		"遥控任务":    PrivServer,
		"用户/用户组":  PrivUser,
		"接口调用平台":  PrivAPI,
	}
	if len(want) != RightCount {
		t.Fatalf("对照表里 %d 项，权限位有 %d 个 —— 有页面没配钥匙，或者有钥匙没页面", len(want), RightCount)
	}
	seen := map[string]string{}
	for page, priv := range want {
		if other, dup := seen[priv]; dup {
			t.Errorf("%q 和 %q 共用 %s —— 一页一把钥匙，共用就分不开了", page, other, priv)
		}
		seen[priv] = page
		if !strings.Contains(RightColumns, priv) {
			t.Errorf("%q 用的 %s 不在 RightColumns 里", page, priv)
		}
	}
}
