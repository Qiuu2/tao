# -*- coding: utf-8 -*-
"""把原实现里的时间解析函数**原样**抠出来跑，生成黄金用例。

不重写、不改写：用 ast 按源码位置切出函数体文本，exec 进一个干净的命名空间。
这样期望值确确实实是原版代码算出来的。
"""
import ast, json, os, re, sys
from datetime import datetime as _real_datetime, timedelta, date

BASE = "/home/user/ai_speaker_project"

# 把"现在"钉死，否则用例活不过一天
FROZEN = _real_datetime(2026, 9, 8, 10, 30, 0)  # 2026-09-08 是周二

class datetime(_real_datetime):
    @classmethod
    def now(cls, tz=None):
        return FROZEN

def cut(path, names):
    src = open(path, encoding="utf-8").read()
    lines = src.split("\n")
    tree = ast.parse(src)
    out = {}
    def walk(node, prefix=""):
        for child in ast.iter_child_nodes(node):
            if isinstance(child, (ast.FunctionDef, ast.AsyncFunctionDef)):
                if child.name in names:
                    seg = "\n".join(lines[child.lineno-1:child.end_lineno])
                    out.setdefault(child.name, seg)
            elif isinstance(child, ast.Assign):
                for t in child.targets:
                    if isinstance(t, ast.Name) and t.id in names:
                        out.setdefault(t.id, "\n".join(lines[child.lineno-1:child.end_lineno]))
            elif isinstance(child, ast.AnnAssign) and isinstance(child.target, ast.Name) and child.target.id in names:
                out.setdefault(child.target.id, "\n".join(lines[child.lineno-1:child.end_lineno]))
            if isinstance(child, ast.ClassDef):
                walk(child)
    walk(tree)
    missing = [n for n in names if n not in out]
    if missing:
        raise SystemExit("没抠到: %r (%s)" % (missing, path))
    return out

class _AnyT:
    def __getitem__(self, item): return self
    def __call__(self, *a, **k): return None
_Any = _AnyT()

ns = {
    "re": re, "datetime": datetime, "timedelta": timedelta, "date": date,
    "Optional": _Any, "Dict": _Any, "Any": _Any, "List": _Any, "Tuple": _Any,
}

# engine.py 里那三个是 @staticmethod / 方法，抠出来时要去掉装饰器与 self
eng = cut(BASE + "/src/engine.py", [
    "_contains_date_word", "_resolve_date_base", "_parse_time_of_day",
    "_parse_time_point", "_DATE_KEYWORDS", "_CN_NUM",
])

def dedent_method(seg, drop_self=False):
    lines = [l for l in seg.split("\n") if l.strip() != ""]
    indent = min(len(l) - len(l.lstrip()) for l in lines)
    body = "\n".join(l[indent:] if len(l) > indent else l for l in seg.split("\n"))
    if drop_self:
        body = body.replace("(self, text: str, base: Optional[datetime] = None)", "(text, base=None)", 1)
        # 函数体里的 self._resolve_date_base / self._parse_time_of_day
        # 在原类里就是 staticmethod，改成走同名壳类即可，逻辑一字未动
        body = body.replace("self._resolve_date_base", "JointInferenceEngine._resolve_date_base")
        body = body.replace("self._parse_time_of_day", "JointInferenceEngine._parse_time_of_day")
    return body

class _EngineShim:
    pass

for name in ["_DATE_KEYWORDS", "_CN_NUM"]:
    exec(dedent_method(eng[name]), ns)

# 这三个函数体里引用 JointInferenceEngine._XXX，给它一个同名壳
class JointInferenceEngine:
    pass
ns["JointInferenceEngine"] = JointInferenceEngine
JointInferenceEngine._DATE_KEYWORDS = ns["_DATE_KEYWORDS"]
JointInferenceEngine._CN_NUM = ns["_CN_NUM"]

for name in ["_contains_date_word", "_resolve_date_base", "_parse_time_of_day", "_parse_time_point"]:
    exec(dedent_method(eng[name], drop_self=(name == "_parse_time_point")), ns)
    setattr(JointInferenceEngine, name, staticmethod(ns[name]))

ENGINE = JointInferenceEngine
ns["ENGINE"] = ENGINE

helpers = cut(BASE + "/backend/services/helpers.py", ["parse_datetime", "format_hhmm", "parse_time_minutes"])
for name, seg in helpers.items():
    exec(seg, ns)
ns["_parse_datetime"] = ns["parse_datetime"]

api = cut(BASE + "/backend/api_public.py", [
    "_parse_iso_date", "_parse_phase1_date", "_weekday_label_from_text",
    "_parse_phase1_time_anchor", "_contains_explicit_date",
    "_query_time_has_date_scope", "_query_time_has_clock_component",
    "_format_query_filter_raw", "_repair_query_time_fragment",
    "_query_task_fuzzy_window_minutes", "_resolve_query_task_time_token",
    "_normalize_query_task_time_filters", "_set_query_task_time_slot",
    "_slot_text", "_extract_time_range_minutes", "_cn_digit_to_arabic",
    "_split_slot_values",
    "_QUERY_TASK_TIME_FRAGMENT_CHAR_RE", "_QUERY_TASK_FUZZY_WINDOWS",
    "_parse_query_datetime",
])
order = [
    "_QUERY_TASK_TIME_FRAGMENT_CHAR_RE", "_QUERY_TASK_FUZZY_WINDOWS",
    "_split_slot_values", "_slot_text", "_cn_digit_to_arabic",
    "_extract_time_range_minutes",
    "_parse_iso_date", "_parse_phase1_date", "_parse_query_datetime",
    "_weekday_label_from_text", "_parse_phase1_time_anchor", "_contains_explicit_date",
    "_query_time_has_date_scope", "_query_time_has_clock_component",
    "_format_query_filter_raw", "_repair_query_time_fragment",
    "_query_task_fuzzy_window_minutes", "_resolve_query_task_time_token",
    "_set_query_task_time_slot", "_normalize_query_task_time_filters",
]
for name in order:
    exec(api[name], ns)

norm = ns["_normalize_query_task_time_filters"]

CASES = [
    ("今天有哪些任务",            {"source_time": "今天"}),
    ("明天有哪些任务",            {"source_time": "明天"}),
    ("今天下午有哪些任务",        {"source_time": "今天下"}),
    ("下午有哪些任务",            {"source_time": "下午"}),
    ("上午的任务",                {"source_time": "上午"}),
    ("晚上有什么任务",            {"source_time": "晚上"}),
    ("今天8点有什么",             {"source_time": "今天8点"}),
    ("8点的任务",                 {"source_time": "8点"}),
    ("八点半的任务",              {"source_time": "八点半"}),
    ("今天8点到10点的任务",       {"source_time": "今天8点", "end_time": "10点"}),
    ("周五有哪些任务",            {"source_time": "周五"}),
    ("下周一有哪些任务",          {"source_time": "下周一"}),
    ("9月10日有哪些任务",         {"source_time": "9月10日"}),
    ("2026-10-01有哪些任务",      {"source_time": "2026-10-01"}),
    ("今天中午的任务",            {"source_time": "今天中午"}),
    ("明天早上到中午的任务",      {"source_time": "明天早上", "end_time": "中午"}),
    ("查任务",                    {}),
    ("凌晨的任务",                {"source_time": "凌晨"}),
    ("今天下午三点的任务",        {"source_time": "今天下午三点"}),
    ("10点到8点的任务",           {"source_time": "10点", "end_time": "8点"}),
]

def fmt(dt):
    return dt.strftime("%Y-%m-%d %H:%M:%S") if dt else ""

out = []
for text, slots in CASES:
    res = norm(text, dict(slots))
    out.append({
        "name": text,
        "text": text,
        "start_value": slots.get("source_time", ""),
        "end_value": slots.get("end_time", ""),
        "want_start_raw": res["start_raw"],
        "want_end_raw": res["end_raw"],
        "want_filter_start_raw": res["filter_start_raw"],
        "want_filter_end_raw": res["filter_end_raw"],
        "want_start_dt": fmt(res["start_dt"]),
        "want_end_dt": fmt(res["end_dt"]),
    })

json.dump({"now": FROZEN.strftime("%Y-%m-%d %H:%M:%S"), "cases": out},
          open(sys.argv[1], "w", encoding="utf-8"), ensure_ascii=False, indent=1)
for c in out:
    print("%-24s start=%-18s end=%-18s fs=%-18s fe=%s" % (
        c["name"], c["want_start_dt"], c["want_end_dt"], c["want_filter_start_raw"], c["want_filter_end_raw"]))
