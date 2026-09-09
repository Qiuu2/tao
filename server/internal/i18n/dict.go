package i18n

// dict 是中文原文 → 英文。键就是代码里写的那句中文。
//
// # 分区说明
//
// 按「这句话出现在界面的什么地方」分段，而不是按 Go 包分。
// 补翻译的人是对着页面找的，不是对着目录树找的。
//
// ⚠ 只放**会显示给人看**的文案。日志、注释、内部错误细节不进这里 ——
// 它们的读者是运维和开发，中文更有用。
//
// ⚠ 用户自己起的名字（终端名 / 媒体名 / 任务名 / 分区名）**永远不进这里**。
// 那是他们的数据，翻了就和他们嘴里说的对不上了。
// codeDict 是**写在 Go 源码里**的那些文案。
// 原文改了这里会失配，所以 dict_test.go 会逐条回去源码里找一遍。
var codeDict = map[string]string{
	// ——— 紧急广播的四个固定槽位 ———
	"地震": "Earthquake",
	"疏散": "Evacuation",
	"警戒": "Alert",
	"消防": "Fire",

	// ——— 状态文字（拼在 data 里，直接显示在列表上）———
	"在线":      "Online",
	"离线":      "Offline",
	"播放中":     "Playing",
	"在线空闲":    "Online, idle",
	"已启动":     "Running",
	"已停止":     "Stopped",
	"准备":      "Ready",
	"执行中":     "Running",
	"立即执行":    "Run now",
	"启动中":     "Starting",
	"手动":      "Manual",
	"未绑定":     "Not bound",
	"(未分区)":   "(No zone)",
	"(未分组)":   "(No folder)",
	"每天":      "Every day",
	"(任务已删除)": "(Task deleted)",
	"(终端已删除)": "(Terminal deleted)",
	"当天启用":    "On today",
	"当天停用":    "Off today",
	"启用":      "Enabled",
	"停用":      "Disabled",
	"顺序":      "Sequential",
	"随机":      "Random",
	"单播":      "Unicast",
	"组播":      "Multicast",

	// ——— 通用提示 ———
	"保存成功":        "Saved",
	"删除成功":        "Deleted",
	"操作成功":        "Done",
	"服务器内部错误":     "Internal server error",
	"登录已过期，请重新登录": "Your session has expired. Please sign in again.",
	"没有权限":        "You do not have permission to do this",
	"权限不足":        "You do not have permission to do this",
	"用户名或密码错误":    "Wrong username or password",
	"验证码错误":       "Wrong captcha",
	"该用户已被停用":     "This account has been disabled",
	"密钥无效":        "Invalid API key",
	"密码长度非法":      "That password length is not allowed",
	"缺少确认标记":      "Missing the confirmation flag",
	"删除未确认":       "Deletion was not confirmed",
	"已存在":         "Already exists",
	"不存在":         "Does not exist",
	"不能为空":        "Cannot be empty",
	"格式不正确":       "Wrong format",
	"重新选择":        "Please choose again",

	// ——— 任务 ———
	"任务不存在":                     "That task does not exist",
	"终端不存在":                     "That terminal does not exist",
	"只能操作自己创建的任务":               "You can only act on tasks you created",
	"音量必须在 0 ~ 100 之间":          "Volume must be between 0 and 100",
	"结束日期不能早于开始日期":              "The end date cannot be earlier than the start date",
	"任务名称不能为空":                  "The task name cannot be empty",
	"请至少选择一个终端":                 "Choose at least one terminal",
	"终端清单里有重复项":                 "The terminal list has duplicates",
	"终端清单里有非法的终端 ID":            "The terminal list contains an invalid terminal ID",
	"终端清单里有已不存在的终端，请重新选择":       "The terminal list contains terminals that no longer exist. Please choose again.",
	"终端清单里有未绑定给你的终端":            "The terminal list contains terminals that are not assigned to you",
	"终端区域掩码必须是 1~16 位的 0/1 字符串": "The terminal area mask must be a 1–16 character string of 0s and 1s",
}

// dataDict 是**不在 Go 源码里**的那些：库里的数据（终端型号）、
// 前端自己的文案（备机提示）。
//
// 单独放一份，是因为 dict_test.go 那条「字典里配的必须在源码里找得到」
// 对它们不成立 —— 混在一起的话，要么测试天天红，要么就得把那条检查关掉，
// 而那条检查恰恰是防止「原文改了、翻译静默失效」的唯一手段。
var dataDict = map[string]string{
	"服务器":     "Server",
	"网络终端":    "Network terminal",
	"网络话筒":    "Network microphone",
	"双向寻呼终端":  "Two-way paging terminal",
	"网络前置":    "Network preamp",
	"网络功放":    "Network amplifier",
	"电源管理器":   "Power manager",
	"报警主机":    "Alarm host",
	"采样终端":    "Sampling terminal",
	"电脑":      "PC",
	"一体化音箱":   "All-in-one speaker",
	"分控软件":    "Sub-control software",
	"一键寻呼终端":  "One-touch paging terminal",
	"分控前置":    "Sub-control preamp",
	"背景音乐":    "Background music",
	"实话接口":    "Intercom interface",
	"手机终端":    "Mobile terminal",
	"分控工作站":   "Sub-control workstation",
	"透传终端":    "Pass-through terminal",
	"监控主机":    "Monitoring host",
	"TTS主机":   "TTS host",
	"离线终端":    "Offline terminal",
	"网络音柱/功放": "Network column speaker / amplifier",
	"编码器":     "Encoder",
	"网络调音台":   "Network mixer",
	"网络音频采集器": "Network audio capture",
	"线阵音柱":    "Line-array column",
	"寻呼话筒":    "Paging microphone",
	"遥控终端":    "Remote terminal",
	"网络分区前置":  "Zone preamp",
	"寻呼终端":    "Paging terminal",
	"应急终端":    "Emergency terminal",
	"LED设备":   "LED device",
	"小区广播主机":  "Community broadcast host",
	"防爆终端":    "Explosion-proof terminal",

	// 前端登录页那条备机提示（后端也会在只读拦截里用同样的说法）
	"当前服务器为备份服务器，系统处于只读状态，所有写操作将被拒绝。": "This server is the standby. The system is read-only and every write will be rejected.",
}

// dict 是查询时真正用的那一份，由上面两份合并而来。
var dict = func() map[string]string {
	m := make(map[string]string, len(codeDict)+len(dataDict))
	for k, v := range codeDict {
		m[k] = v
	}
	for k, v := range dataDict {
		if _, dup := m[k]; dup {
			// 同一句话在两份里各配一次，改的时候必然只改一处。
			panic("i18n: 词条在 codeDict 与 dataDict 里重复：" + k)
		}
		m[k] = v
	}
	return m
}()
