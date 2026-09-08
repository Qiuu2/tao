# -*- coding: utf-8 -*-
"""生成取消/挪动/对调相关回话的黄金用例。直接 import 原实现的 runtime_reply。"""
import json, os, sys

BASE = os.environ.get("AI_SPEAKER_HOME", "/home/user/ai_speaker_project")
sys.path.insert(0, os.path.join(BASE, "backend", "assistant"))
import runtime_reply as R  # noqa: E402

cases = []
def add(case_name, want, **extra):
    row = {"name": case_name, "want": want}; row.update(extra); cases.append(row)

# ① 一次性取消已安排
ONCE_CANCEL = [
    "一次性取消帮您安排上啦~ 任务执行完会自动恢复。",
    "好嘞，先帮您临时取消一次，过了这个时段会自动复原哈~",
    "搞定，这次取消已经设好啦，事后会自动恢复~",
]
for diag in ("d-0001", "d-0002", "d-0003", "d-0004", ""):
    add("once_cancel_done/%s" % (diag or "(空)"),
        R.success_runtime("once_cancel_done", ONCE_CANCEL, diag),
        kind="once_cancel_done", diagnostic_id=diag)

# ② 一次性取消的汇总句
for sg, st, bg, bt in ((1, 3, 0, 0), (2, 5, 0, 0), (0, 0, 1, 0), (1, 2, 1, 0), (0, 0, 0, 0)):
    add("cancel_once_summary/%d-%d-%d-%d" % (sg, st, bg, bt),
        R.compose_cancel_once_reply(sg, st, bg, bt),
        kind="cancel_once_summary", schedule_group_count=sg,
        schedule_task_count=st, broadcast_group_count=bg, broadcast_task_count=bt)

# ③ 追问
for intent, need, example in (
    ("cancel_schedule", "要取消的时间范围", "比如今天上午八点到九点，或者周三早读这类说法"),
):
    add("ask/%s" % intent, R.ask_runtime(intent, need, example=example),
        kind="ask", intent=intent, need=need, example=example)

json.dump(cases, open(sys.argv[1], "w", encoding="utf-8"), ensure_ascii=False, indent=1)
for c in cases:
    print("%-40s %s" % (c["name"], c["want"].replace("\n", " / ")))
