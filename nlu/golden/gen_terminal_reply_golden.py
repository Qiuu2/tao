# -*- coding: utf-8 -*-
"""生成终端与分区意图的回话黄金用例。直接 import 原实现的 runtime_reply 模块。"""
import json, os, sys

BASE = os.environ.get("AI_SPEAKER_HOME", "/home/user/ai_speaker_project")
sys.path.insert(0, os.path.join(BASE, "backend", "assistant"))
import runtime_reply as R  # noqa: E402

cases = []
def add(name, want, **extra):
    row = {"name": name, "want": want}
    row.update(extra)
    cases.append(row)

# ① 终端启用 / 停用
TERM_STATE = [
    '已为您{state_text}{terminal_desc}。',
    '{terminal_desc} 现在是{state_text}状态。',
    '{terminal_desc} 已调整为{state_text}状态。',
]
for intent, state_text in (("enable_terminal", "启用"), ("disable_terminal", "停用")):
    for desc in ("A101教室音箱", "A101教室音箱、A102教室音箱", "3个终端"):
        add("term_state/%s/%s" % (intent, desc),
            R.success_runtime(intent, TERM_STATE, desc, state_text,
                              terminal_desc=desc, state_text=state_text),
            kind="term_state", intent=intent, terminal_desc=desc, state_text=state_text)

# ② 终端校时
SYNC = [
    "已向 {terminal_desc} 发出校时指令，稍后可以再确认结果。",
    "{terminal_desc} 正在同步时间。",
    "{terminal_desc} 的校时指令已经发出。",
]
for desc in ("A101教室音箱", "A101教室音箱、A102教室音箱", "5个终端"):
    add("sync/%s" % desc,
        R.success_runtime("sync_terminal_time", SYNC, desc, terminal_desc=desc),
        kind="sync", intent="sync_terminal_time", terminal_desc=desc)

# ③ 终端音量
VOL = [
    "已把{scope_desc}的音量调整为 {volume}。",
    "{scope_desc}的音量已经设为 {volume} 了。",
    "音量已调整：{scope_desc} 现在是 {volume}。",
]
for desc, vol in (("终端“A101教室音箱”", 70), ("2个终端", 40), ("终端“广播室主话筒”", 0)):
    add("term_volume/%s/%d" % (desc, vol),
        R.success_runtime("adjust_volume_terminal", VOL, desc, vol,
                          scope_desc=desc, volume=vol),
        kind="term_volume", intent="adjust_volume_terminal", scope_desc=desc, volume=vol)

# ④ 新建分区（注意：分区这几个走 stable_reply，没有 light 外壳）
CREATE_ZONE = [
    "分区已经建好：{zone_desc}。",
    "已完成分区创建：{zone_desc}。",
    "新的分区已经准备好了：{zone_desc}。",
]
for desc in ("一号分区", "教学楼分区", "一号分区、二号分区"):
    add("create_zone/%s" % desc,
        R.stable_reply("create_zone", CREATE_ZONE, desc, zone_desc=desc),
        kind="create_zone", intent="create_zone", zone_desc=desc)

# ⑤ 删除分区
DELETE_ZONE = [
    "已删除分区：{zone_desc}。",
    "{zone_desc}分区已经删除完成。",
    "分区删除已完成，涉及：{zone_desc}。",
]
for desc in ("一号分区", "教学楼分区", "2个分区"):
    add("delete_zone/%s" % desc,
        R.stable_reply("delete_zone", DELETE_ZONE, desc, zone_desc=desc),
        kind="delete_zone", intent="delete_zone", zone_desc=desc)

# ⑥ 分区加 / 移终端
ADD = [
    "已将{terminal_desc}{action_label}分区{zone_desc}。",
    "{terminal_desc}已经{action_label}到分区{zone_desc}。",
    "分区调整完成：{terminal_desc}已{action_label}到{zone_desc}。",
]
REMOVE = [
    "已将{terminal_desc}从分区{zone_desc}移出。",
    "{terminal_desc}已经从分区{zone_desc}移除。",
    "分区调整完成：{terminal_desc}已从{zone_desc}移出。",
]
for intent, variants, label in (("add_terminal_to_zone", ADD, "加入"),
                                ("remove_terminal_from_zone", REMOVE, "移出")):
    for zone, term in (("一号分区", "A101教室音箱"),
                       ("教学楼分区", "A101教室音箱、A102教室音箱"),
                       ("一号分区", "3个终端")):
        add("zone_member/%s/%s/%s" % (intent, zone, term),
            R.stable_reply(intent, variants, zone, term, label,
                           zone_desc=zone, terminal_desc=term, action_label=label),
            kind="zone_member", intent=intent, zone_desc=zone,
            terminal_desc=term, action_label=label)

# ⑦ 追问
for intent, need, example in (
    ("create_zone", "分区名称", "比如教学楼分区"),
    ("delete_zone", "分区名称", "比如教学楼分区"),
    ("add_terminal_to_zone", "分区名称", "比如教学楼分区"),
    ("remove_terminal_from_zone", "分区名称", "比如教学楼分区"),
):
    add("ask/%s" % intent, R.ask_runtime(intent, need, example=example),
        kind="ask", intent=intent, need=need, example=example)

# ⑧ 失败
add("failure/no_zone_to_delete",
    R.failure_runtime("no_zone_to_delete", "可删除的分区", suggestion="确认一下分区名再说一次哈~"),
    kind="failure", intent="no_zone_to_delete", topic="可删除的分区",
    reason="", suggestion="确认一下分区名再说一次哈~")
add("failure/no_zone_to_operate",
    R.failure_runtime("no_zone_to_operate", "可操作的分区", suggestion="确认一下分区名再说一次哈~"),
    kind="failure", intent="no_zone_to_operate", topic="可操作的分区",
    reason="", suggestion="确认一下分区名再说一次哈~")
for intent, topic, reason in (
    ("enable_terminal", "启用终端", "终端不在线"),
    ("disable_terminal", "停用终端", "终端不在线"),
    ("sync_terminal_time", "终端校时", "终端不在线"),
):
    add("failure/%s" % intent,
        R.failure_runtime(intent, topic, reason=reason, suggestion="稍后可以再试一次。"),
        kind="failure", intent=intent, topic=topic, reason=reason,
        suggestion="稍后可以再试一次。")

json.dump(cases, open(sys.argv[1], "w", encoding="utf-8"), ensure_ascii=False, indent=1)
for c in cases:
    print("%-52s %s" % (c["name"], c["want"].replace("\n", " / ")))
