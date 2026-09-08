# -*- coding: utf-8 -*-
"""生成回话措辞的黄金用例。

runtime_reply.py 只依赖 hashlib 与 re，能直接 import —— 这里就直接调**原模块**，
连抠函数体都不用。期望值是原版一字不差算出来的。
"""
import json, os, sys

BASE = os.environ.get("AI_SPEAKER_HOME", "/home/user/ai_speaker_project")
sys.path.insert(0, os.path.join(BASE, "backend", "assistant"))
import runtime_reply as R  # noqa: E402

ACTION_LABELS = {
    "play_task": "开始播放",
    "stop_task": "停止",
    "task_pause": "暂停",
    "task_resume": "恢复播放",
}

SUCCESS_PLAIN = [
    '已为您将任务“{task_name}”设为{action_label}。',
    '任务“{task_name}”现在是{action_label}状态。',
    '“{task_name}”已经调整为{action_label}。',
]
SUCCESS_SCOPED = [
    '已将方案“{schedule_name}”里的任务“{task_name}”设为{action_label}。',
    '方案“{schedule_name}”中的“{task_name}”现在是{action_label}状态。',
    '任务“{task_name}”已在方案“{schedule_name}”中调整为{action_label}。',
]

cases = []

def add(name, reply, **extra):
    row = {"name": name, "want": reply}
    row.update(extra)
    cases.append(row)

# ① 单任务状态变更（不带方案）
for intent in ("play_task", "stop_task", "task_pause", "task_resume"):
    label = ACTION_LABELS[intent]
    for task in ("早读预备铃", "课间轻音乐", "升旗仪式-国歌"):
        add("success_plain/%s/%s" % (intent, task),
            R.success_runtime(intent, SUCCESS_PLAIN, task,
                              task_name=task, action_label=label),
            kind="success_plain", intent=intent, task_name=task, action_label=label)

# ② 带方案的
for intent in ("play_task", "stop_task"):
    label = ACTION_LABELS[intent]
    for sched, task in (("admin", "早读预备铃"), ("春季作息", "课间轻音乐")):
        add("success_scoped/%s/%s/%s" % (intent, sched, task),
            R.success_runtime(intent, SUCCESS_SCOPED, sched, task,
                              schedule_name=sched, task_name=task, action_label=label),
            kind="success_scoped", intent=intent, schedule_name=sched,
            task_name=task, action_label=label)

# ③ 音量
VOLUME_VARIANTS = [
    "已把{scope_desc}的音量调整为 {volume}。",
    "{scope_desc}的音量已经设为 {volume} 了。",
    "音量已调整：{scope_desc} 现在是 {volume}。",
]
for scope, vol in (("任务“早读预备铃”", 80), ("任务“课间轻音乐”", 30), ("任务“放学铃”", 100)):
    add("success_volume/%s/%d" % (scope, vol),
        R.success_runtime("adjust_volume", VOLUME_VARIANTS, scope, vol,
                          scope_desc=scope, volume=vol),
        kind="success_volume", intent="adjust_volume", scope_desc=scope, volume=vol)

# ④ 追问
for intent, need, example in (
    ("play_task", "要执行的任务名称", "比如说出任务的名字哈~"),
    ("stop_task", "要停止的任务名称", "比如说出任务的名字哈~"),
    ("task_pause", "要暂停的任务名称", "比如说出任务的名字哈~"),
    ("task_resume", "要恢复的任务名称", "比如说出任务的名字哈~"),
    ("adjust_volume", "要调整到的音量值", "比如说“把音量调到 80”哈~"),
):
    add("ask/%s" % intent, R.ask_runtime(intent, need, example=example),
        kind="ask", intent=intent, need=need, example=example)

# ⑤ 失败
for intent, topic, reason, suggestion in (
    ("play_task", "任务“早读预备铃”的执行", "方案已停用，请先启用后再启动",
     "您可以换个说法再试，或者补充更明确的任务名。"),
    ("stop_task", "任务“课间轻音乐”的停止", "只能操作自己创建的任务",
     "您可以换个说法再试，或者补充更明确的任务名。"),
    ("play_task", "任务“放学铃”的执行", "", ""),
    ("adjust_volume", "任务“升旗仪式-国歌”的音量调整", "音量只能是 0 ~ 100", ""),
):
    add("failure/%s/%s" % (intent, topic),
        R.failure_runtime(intent, topic, reason=reason, suggestion=suggestion),
        kind="failure", intent=intent, topic=topic, reason=reason, suggestion=suggestion)

json.dump(cases, open(sys.argv[1], "w", encoding="utf-8"), ensure_ascii=False, indent=1)
for c in cases:
    print("%-46s %s" % (c["name"], c["want"]))
