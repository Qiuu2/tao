# -*- coding: utf-8 -*-
"""生成作息方案相关回话的黄金用例。直接 import 原实现的 runtime_reply 模块。"""
import json, os, sys

BASE = os.environ.get("AI_SPEAKER_HOME", "/home/user/ai_speaker_project")
sys.path.insert(0, os.path.join(BASE, "backend", "assistant"))
import runtime_reply as R  # noqa: E402

cases = []
def add(case_name, want, **extra):
    row = {"name": case_name, "want": want}; row.update(extra); cases.append(row)

# ① 方案没找到
for name in ("春季作息", "冬季作息", "admin", ""):
    add("schedule_not_found/%s" % (name or "(空)"),
        R.reply_schedule_not_found(name),
        kind="schedule_not_found", name_arg=name)

# ② 没有可操作的任务
for label in ("挪动", "对调", "取消", "删除", "操作"):
    add("no_matching_tasks/%s" % label,
        R.reply_no_matching_tasks(label),
        kind="no_matching_tasks", action_label=label)

# ③ 待确认那一问
QUESTION = "您想这次只执行一次，还是永久这么改呢？告诉我一声哈~"
for intent, summary in (
    ("cancel_schedule", "要取消的是方案“admin”里 07:20 的早读预备铃"),
    ("move_schedule", "要把“早读预备铃”从 07:20 挪到 08:00"),
    ("swap_schedule", "要把“早读预备铃”和“放学铃”对调"),
):
    add("confirm/%s" % intent, R.confirm_runtime(intent, summary, question=QUESTION),
        kind="confirm", intent=intent, summary=summary, question=QUESTION)

# ④ 追问
for intent, need, example in (
    ("enable_schedule", "要操作哪个作息方案", "比如冬季作息"),
    ("disable_schedule", "要操作哪个作息方案", "比如冬季作息"),
    ("delete_schedule", "要删除的方案名称", "比如删除冬季作息"),
    ("shift_schedule", "源方案、位移量和新方案名称", "比如把冬季作息整体后移 30 分钟生成临时方案"),
):
    add("ask/%s" % intent, R.ask_runtime(intent, need, example=example),
        kind="ask", intent=intent, need=need, example=example)

json.dump(cases, open(sys.argv[1], "w", encoding="utf-8"), ensure_ascii=False, indent=1)
for c in cases:
    print("%-34s %s" % (c["name"], c["want"].replace("\n", " / ")))
