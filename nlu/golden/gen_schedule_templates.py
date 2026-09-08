# -*- coding: utf-8 -*-
"""把原实现的作息模板蒸馏成 tao 用得上的那几列。

原来的 schedule_template_catalog.json 有 396K，绝大部分是 SDK 那边的字段
（taskid、terminalids、offlinestate、state…）。这套库要的只有：
条目名、时刻、时长、星期、媒体名。终端由建方案时按"全部播放终端"另填，
媒体按名字在本库的 media 表里找 —— 原库的 mediaid 在这里没有意义。

输出：server/internal/assistant/templates/schedules.json
"""
import json, os, sys

BASE = os.environ.get("AI_SPEAKER_HOME", "/home/user/ai_speaker_project")
DATA = os.path.join(BASE, "backend", "default_data")

manifest = json.load(open(os.path.join(DATA, "schedule_template_manifest.json"), encoding="utf-8"))
catalog = json.load(open(os.path.join(DATA, "schedule_template_catalog.json"), encoding="utf-8"))

by_name = {}
for s in catalog.get("schedules", []):
    name = str(s.get("schedule_name") or "").strip()
    if name:
        by_name[name] = s

def local(fname):
    return json.load(open(os.path.join(DATA, "schedule_templates", fname), encoding="utf-8"))

def slim(tasks):
    out = []
    for t in tasks:
        if not isinstance(t, dict):
            continue
        name = str(t.get("taskname") or t.get("name") or t.get("customName") or "").strip()
        start = str(t.get("starttime") or "").strip()
        if not name or not start:
            continue
        length = t.get("timelength") or t.get("length") or 0
        try:
            length = int(length)
        except Exception:
            length = 0
        wd = [str(d) for d in (t.get("weekdays") or []) if str(d).strip()]
        media = str(t.get("medianame") or t.get("audio") or "").strip()
        vol = t.get("volume")
        try:
            vol = int(vol)
        except Exception:
            vol = 80
        out.append({
            "name": name,
            "start": start,
            "seconds": max(1, length),
            "weekdays": wd,
            "media": media,
            "volume": vol if 0 <= vol <= 100 else 80,
        })
    out.sort(key=lambda r: r["start"])
    return out

result = {
    "kindAliases": manifest.get("kind_aliases", {}),
    "seasonAliases": manifest.get("season_aliases", {}),
    "default": {
        "kind": manifest.get("default", {}).get("kind", ""),
        "season": manifest.get("default", {}).get("season", ""),
    },
    "templates": {},
}

for kind, kcfg in (manifest.get("kind_templates") or {}).items():
    for season, scfg in (kcfg.get("season_templates") or {}).items():
        src = str(scfg.get("source_schedule_name") or "").strip()
        fname = str(scfg.get("local_template_file") or "").strip()
        if src and src in by_name:
            items = slim(by_name[src].get("tasks") or [])
            label = src
        elif fname:
            doc = local(fname)
            items = slim(doc.get("tasks") or [])
            label = str(doc.get("schedule_name") or fname)
        else:
            continue
        result["templates"]["%s/%s" % (kind, season)] = {"label": label, "items": items}

out_path = sys.argv[1]
os.makedirs(os.path.dirname(out_path), exist_ok=True)
json.dump(result, open(out_path, "w", encoding="utf-8"), ensure_ascii=False, indent=1)
for k, v in result["templates"].items():
    print("%-12s %-20s %2d 条  %s" % (k, v["label"], len(v["items"]),
          "、".join(i["name"] for i in v["items"][:4])))
print("默认:", result["default"], " 体积:", os.path.getsize(out_path), "字节")
