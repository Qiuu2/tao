# 黄金用例生成器

这两个脚本用来生成 `server/internal/assistant/testdata/*.json` 里的期望值。

**它们不重写原实现的逻辑。** 用 `ast` 按源码位置把原实现里的函数体
**原样切出来**，`exec` 进一个干净的命名空间再跑。所以期望值确确实实是
原版代码算出来的，不是照着代码推的。

```
gen_time_golden.py   → time_golden.json    中文时间范围解析
gen_match_golden.py  → match_golden.json   任务与查询窗口的比对
```

## 怎么跑

需要能读到原实现的源码（默认在 `/home/user/ai_speaker_project`，
改脚本顶上的 `BASE`）。不需要装 torch / fastapi —— 抠出来的函数只依赖
`re` 和 `datetime`。

```bash
python3 nlu/golden/gen_time_golden.py  server/internal/assistant/testdata/time_golden.json
python3 nlu/golden/gen_match_golden.py server/internal/assistant/testdata/match_golden.json
cd server && go test ./internal/assistant/
```

## 「现在」是钉死的

脚本里把 `datetime.now()` 固定成 `2026-09-08 10:30`（周二），
用例文件里也记着这个时间，Go 侧测试读出来后同样把 `nowFunc` 钉过去。
不这么做的话，「明天」「下周一」这类用例活不过一天。

## 什么时候要重跑

改了 `timeparse.go` / `taskmatch.go` 而测试挂了，**先确认是不是自己改错了**。
确实是原实现变了，才重跑生成器更新期望值 —— 期望值是用来卡行为的，
不是用来跟着代码改的。
