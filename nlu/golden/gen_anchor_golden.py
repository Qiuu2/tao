# -*- coding: utf-8 -*-
"""生成时间锚点（挪动/对调用）的黄金用例。函数体照旧用 ast 从原实现原样抠出来跑。"""
import ast, json, os, re, sys
from datetime import datetime as _real_datetime, timedelta, date

BASE = os.environ.get("AI_SPEAKER_HOME", "/home/user/ai_speaker_project")
FROZEN = _real_datetime(2026, 9, 8, 10, 30, 0)  # 周二

class datetime(_real_datetime):
    @classmethod
    def now(cls, tz=None):
        return FROZEN

HERE = os.path.dirname(os.path.abspath(__file__))
timegen = open(os.path.join(HERE, "gen_time_golden.py"), encoding="utf-8").read().split("CASES = [")[0]
g = {}
exec(timegen, g)
ns = g["ns"]

def cut(path, names):
    src = open(path, encoding="utf-8").read()
    lines = src.split("\n")
    out = {}
    for child in ast.iter_child_nodes(ast.parse(src)):
        if isinstance(child, ast.FunctionDef) and child.name in names:
            out.setdefault(child.name, "\n".join(lines[child.lineno-1:child.end_lineno]))
    missing = [n for n in names if n not in out]
    if missing:
        raise SystemExit("没抠到: %r" % missing)
    return out

api = cut(BASE + "/backend/api_public.py",
          ["_anchor_start_minutes", "_replace_weekday", "_rotate_weekdays",
           "_shift_weekdays_by_delta", "_shift_hhmmss_with_day_delta"])
helpers = cut(BASE + "/backend/services/helpers.py", ["format_hhmmss", "unique_list"])
for seg in helpers.values():
    exec(seg, ns)
ns["_format_hhmmss"] = ns["format_hhmmss"]
ns["_unique_list"] = ns["unique_list"]
for n in ["_anchor_start_minutes", "_replace_weekday", "_rotate_weekdays",
          "_shift_weekdays_by_delta", "_shift_hhmmss_with_day_delta"]:
    exec(api[n], ns)

anchor = ns["_parse_phase1_time_anchor"]
extract = ns["_extract_time_range_minutes"]
start_min = ns["_anchor_start_minutes"]
replace_wd = ns["_replace_weekday"]
shift_wd = ns["_shift_weekdays_by_delta"]
shift_t = ns["_shift_hhmmss_with_day_delta"]

ANCHORS = [
    "周三", "周五", "下周一", "周日", "星期六", "礼拜天",
    "今天", "明天", "9月10日", "2026-10-01",
    "周三8点", "周五9点", "8点", "9点半", "下午3点",
    "周五3点到7点", "周六4点到5点", "早上八点", "周一8点30",
    "上午", "下午", "随便一句没有时间的话",
]
anchors_out = []
for raw in ANCHORS:
    a = anchor(raw)
    row = {"raw": raw}
    if a:
        row["kind"] = a.get("kind") or ""
        row["weekday"] = a.get("weekday") or ""
        d = a.get("date")
        row["date"] = d.strftime("%Y-%m-%d") if d else ""
        row["has_range"] = "time_start_minutes" in a
        row["start_minutes"] = a.get("time_start_minutes", 0) or 0
        row["end_minutes"] = a.get("time_end_minutes", 0) or 0
    else:
        row["kind"] = ""
        row["weekday"] = ""
        row["date"] = ""
        row["has_range"] = False
        row["start_minutes"] = 0
        row["end_minutes"] = 0
    sm = start_min(a) if a else None
    row["anchor_start_minutes"] = -1 if sm is None else int(sm)
    anchors_out.append(row)
    print("%-24s kind=%-8s wd=%-4s date=%-11s range=%s start=%s" % (
        raw, row["kind"], row["weekday"], row["date"],
        (row["start_minutes"], row["end_minutes"]) if row["has_range"] else "-",
        row["anchor_start_minutes"]))

RANGES = ["周五3点到7点", "下午3点到5点", "3-7点", "8点30", "下午3点", "周一8点30",
          "早上八点", "十点半", "晚上7点到9点", "没有时间"]
ranges_out = []
for raw in RANGES:
    s, e = extract(raw)
    ranges_out.append({"raw": raw, "ok": s is not None,
                       "start": int(s) if s is not None else 0,
                       "end": int(e) if e is not None else 0})

WD = [
    (["周一", "周三", "周五"], "周一", "周六"),
    (["周一", "周二"], "周三", "周日"),
    (["周六", "周日"], "周六", "周一"),
]
wd_out = [{"weekdays": w, "source": s, "target": t, "want": replace_wd(w, s, t)} for w, s, t in WD]

SHIFT_WD = [(["周一", "周三"], 1), (["周日"], 1), (["周一", "周二"], -1), (["周五"], 3), (["周一"], 0)]
shift_wd_out = [{"weekdays": w, "delta": d, "want": shift_wd(w, d)} for w, d in SHIFT_WD]

SHIFT_T = [("07:20:00", 40), ("23:50:00", 30), ("00:10:00", -30), ("08:00:00", 0), ("12:00:00", 1440)]
shift_t_out = []
for v, d in SHIFT_T:
    t, dd = shift_t(v, d)
    shift_t_out.append({"value": v, "delta_minutes": d, "want_time": t, "want_day_delta": dd})

json.dump({"now": FROZEN.strftime("%Y-%m-%d %H:%M:%S"), "anchors": anchors_out,
           "ranges": ranges_out, "replace_weekday": wd_out,
           "shift_weekdays": shift_wd_out, "shift_time": shift_t_out},
          open(sys.argv[1], "w", encoding="utf-8"), ensure_ascii=False, indent=1)
