# -*- coding: utf-8 -*-
"""生成任务/时间窗口匹配的黄金用例。函数体照旧用 ast 从原实现里原样抠出来跑。"""
import ast, json, os, re, sys
from datetime import datetime as _real_datetime, timedelta, date

BASE = "/home/user/ai_speaker_project"
FROZEN = _real_datetime(2026, 9, 8, 10, 30, 0)  # 周二

class datetime(_real_datetime):
    @classmethod
    def now(cls, tz=None):
        return FROZEN

class _AnyT:
    def __getitem__(self, item): return self
    def __call__(self, *a, **k): return None
_Any = _AnyT()

def cut(path, names):
    src = open(path, encoding="utf-8").read()
    lines = src.split("\n")
    tree = ast.parse(src)
    out = {}
    def walk(node):
        for child in ast.iter_child_nodes(node):
            if isinstance(child, (ast.FunctionDef, ast.AsyncFunctionDef)) and child.name in names:
                out.setdefault(child.name, "\n".join(lines[child.lineno-1:child.end_lineno]))
            elif isinstance(child, ast.Assign):
                for t in child.targets:
                    if isinstance(t, ast.Name) and t.id in names:
                        out.setdefault(t.id, "\n".join(lines[child.lineno-1:child.end_lineno]))
            if isinstance(child, ast.ClassDef):
                walk(child)
    walk(tree)
    missing = [n for n in names if n not in out]
    if missing:
        raise SystemExit("没抠到: %r (%s)" % (missing, path))
    return out

ns = {"re": re, "datetime": datetime, "timedelta": timedelta, "date": date,
      "Optional": _Any, "Dict": _Any, "Any": _Any, "List": _Any, "Tuple": _Any}

# 复用时间解析那一份（同一个抠取方式）
timegen = open(SP_PATH := os.path.join(os.path.dirname(os.path.abspath(__file__)), "gen_time_golden.py"), encoding="utf-8").read().split("CASES = [")[0]
g = {}
exec(timegen, g)
ns.update(g["ns"])
ns["ENGINE"] = g["ENGINE"]

api = cut(BASE + "/backend/api_public.py", [
    "_task_occurs_on_date", "_task_matches_dated_query_window", "_task_matches_query_time",
    "_task_date_span", "_task_weekdays_for_anchor", "_task_duration_seconds",
    "_parse_time_seconds",
])
helpers = cut(BASE + "/backend/services/helpers.py",
              ["weekday_label", "coerce_int", "parse_hms_duration_seconds", "_parse_clock_duration_seconds"])
tw = cut(BASE + "/backend/services/task_writer.py", ["coerce_duration_int"])

for seg in helpers.values():
    exec(seg, ns)
ns["_weekday_label"] = ns["weekday_label"]
ns["_coerce_int"] = ns["coerce_int"]
ns["_parse_hms_duration_seconds"] = ns["parse_hms_duration_seconds"]
exec(tw["coerce_duration_int"], ns)
ns["_coerce_duration_int"] = ns["coerce_duration_int"]
ns["_weekdays_from_execmode"] = lambda v: []
ns["_parse_iso_date"] = ns["_parse_iso_date"]

for name in ["_parse_time_seconds", "_task_date_span", "_task_weekdays_for_anchor",
             "_task_duration_seconds", "_task_occurs_on_date",
             "_task_matches_dated_query_window", "_task_matches_query_time"]:
    exec(api[name], ns)

norm = ns["_normalize_query_task_time_filters"]
matches = ns["_task_matches_query_time"]

TASKS = [
    ("早读预备铃",   "07:20:00", 30,   ["周一","周二","周三","周四","周五"], "2026-01-01", "2026-12-31"),
    ("课间轻音乐",   "09:45:00", 900,  ["周一","周二","周三","周四","周五"], "2026-01-01", "2026-12-31"),
    ("午间背景音乐", "11:40:00", 2400, ["周一","周二","周三","周四","周五"], "2026-01-01", "2026-12-31"),
    ("放学铃",       "17:30:00", 45,   ["周一","周二","周三","周四","周五"], "2026-01-01", "2026-12-31"),
    ("晚自习预备",   "18:50:00", 30,   ["周一","周二","周三","周四","周五"], "2026-01-01", "2026-12-31"),
    ("升旗仪式",     "07:50:00", 180,  ["周一"],                              "2026-01-01", "2026-12-31"),
    ("周末巡查",     "08:00:00", 60,   ["周六","周日"],                       "2026-01-01", "2026-12-31"),
    ("跨零点长播",   "23:50:00", 7200, [],                                    "2026-01-01", "2026-12-31"),
    ("不限星期的",   "10:00:00", 60,   [],                                    "",           ""),
    ("过期任务",     "08:00:00", 60,   ["周一","周二"],                       "2025-01-01", "2025-12-31"),
]

QUERIES = [
    ("今天有哪些任务",       "今天",     ""),
    ("明天有哪些任务",       "明天",     ""),
    ("今天上午有哪些任务",   "今天上午", ""),
    ("今天下午有哪些任务",   "今天下午", ""),
    ("晚上有什么任务",       "晚上",     ""),
    ("8点的任务",            "8点",      ""),
    ("今天8点到10点的任务",  "今天8点",  "10点"),
    ("周六有哪些任务",       "周六",     ""),
    ("周一有哪些任务",       "周一",     ""),
    ("查任务",               "",         ""),
    ("凌晨的任务",           "凌晨",     ""),
    ("今天9点到12点的任务",  "今天9点",  "12点"),
]

def task_dict(name, starttime, seconds, weekdays, sd, ed):
    return {"taskname": name, "starttime": starttime, "timelength": seconds,
            "timelengthtype": 1, "weekdays": list(weekdays),
            "startdate": sd, "enddate": ed}

out = []
for qname, sval, eval_ in QUERIES:
    slots = {}
    if sval: slots["source_time"] = sval
    if eval_: slots["end_time"] = eval_
    res = norm(qname, dict(slots))
    start_dt, end_dt = res["start_dt"], res["end_dt"]
    fsr, fer = res["filter_start_raw"], res["filter_end_raw"]
    # _apply_query_task_intent 里的跨夜顺延
    if start_dt and end_dt and end_dt < start_dt and not (
        ns["ENGINE"]._contains_date_word(fer) if fer else False):
        end_dt = end_dt + timedelta(days=1)
    hits = []
    for t in TASKS:
        task = task_dict(*t)
        if matches(task, start_dt, end_dt, fsr, fer):
            hits.append(t[0])
    out.append({"name": qname, "text": qname, "start_value": sval, "end_value": eval_,
                "want_hits": hits})
    print("%-22s -> %s" % (qname, "、".join(hits) or "(无)"))

json.dump({
    "now": FROZEN.strftime("%Y-%m-%d %H:%M:%S"),
    "tasks": [{"name": t[0], "starttime": t[1], "duration_seconds": t[2],
               "weekdays": t[3], "startdate": t[4], "enddate": t[5]} for t in TASKS],
    "cases": out,
}, open(sys.argv[1], "w", encoding="utf-8"), ensure_ascii=False, indent=1)
