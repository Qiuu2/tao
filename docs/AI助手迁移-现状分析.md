# AI 助手迁移到 tao —— 现状分析

> 目标：把 `ai_speaker_project` 里那套 AI 语音助手**一比一**搬进 `tao`，
> 并把执行层从「调 HTTP SDK」换成「直接写 audioserver 数据库」。
>
> 这一份只讲**现状**：那套助手到底由哪些部分组成、一句话进去发生了什么、
> 每个动作最终打到哪个 SDK 接口。方案与排期在《AI助手迁移-实施方案》里。
>
> 分析基于 `Qiuu2/ai_speaker_project` 的 `2ada1e9`（test-push-20260527，T124）。

---

## 1. 一句话概括

那不是一个「聊天框」，是一套**完整的中文指令 → 广播系统操作**的流水线：
本地 BERT 模型做意图识别与槽位抽取，32 个意图各自有专门的执行逻辑，
带确认/撤销/指代消解的多轮对话，最后通过 HTTP SDK 落到广播服务器上。

**体量**（不含测试）：

| 层 | 位置 | 行数 |
|---|---|---|
| NLU 引擎 | `src/engine.py` 等 | 4,505 |
| 模型权重 | `models/joint_rbt3/` | 147 MB |
| 会话编排 | `backend/assistant/` | 1,810 |
| **意图执行** | `backend/api_public.py` 里 36 个 `_apply_*_intent` | **3,504** |
| 执行支撑 | `task_writer` / `terminal_lookup` / `helpers` / `schedule_parser` … | ~3,900 |
| **SDK 客户端** | `backend/services/remote_client.py` | **1,366** |
| 前端浮窗 | `web/src/components/AiAssistantFloat.vue` | **4,776** |
| 前端配套 | `assistant-timeline` / `quick-actions` / `onceTaskSpecs` … | ~1,200 |

真正要搬的核心逻辑在 **1 万行上下**，前端另有 **5 千行**。

---

## 2. 一次对话走完的完整链路

```
用户在浮窗里打字：「把春季方案明天8点的早读挪到9点」
        │
        ▼
① 前端 AiAssistantFloat.vue
        POST /assistant/chat  { text, session_id, ... }
        │
        ▼
② backend/assistant/chat.py  ── 会话编排
        ├─ 指代消解：「刚才那个」「上面第二个」→ 补回上一轮的 slots
        ├─ pending 确认：上一轮问了「确定吗」，这一轮的「是/否」在这里被截住
        ├─ 锁定指令：已锁定的方案名/任务名不被本轮覆盖
        └─ 连续听不懂 3 次 → 塞示例话术 + 快捷按钮
        │
        ▼
③ src/engine.py  ── NLU（本地 PyTorch 模型）
        joint_rbt3：RBT3（3 层中文 RoBERTa）
        ├─ 意图头：softmax 分类，置信度 < 0.5 落到 none
        └─ 槽位头：BIO 序列标注（可选 CRF），按字切分再对齐回字符级
        输出：intent + confidence + slots{time, content, schedule_name, ...}
        │
        ▼
④ backend/assistant/dispatch.py  ── 32 个意图 → 处理函数
        │
        ▼
⑤ backend/api_public.py 的 _apply_<intent>_intent  ── 真正的业务逻辑
        ├─ 槽位补全与消歧（缺 time？缺 content？同名方案有两个？）
        ├─ 名称解析：媒体名/终端名/分区名 → id（模糊匹配 + 缓存）
        ├─ 可行性预检：目标时间点已被占用？终端不在线？
        ├─ 需要确认的动作 → 返回 pending_action，等下一轮的「是」
        └─ 执行
        │
        ▼
⑥ backend/services/remote_client.py  ── HTTP SDK
        login 拿 token → POST /task/taskinfotwo、/task/sechinfoall …
        │
        ▼
⑦ 广播服务器（另一台机器）→ 最终落到 audioserver 数据库
```

**⑥⑦ 这两步正是这次要换掉的**：tao 自己就在 audioserver 上，
不需要绕一圈 HTTP。

---

## 3. 逐层拆解

### 3.1 NLU 层 —— 这是整个迁移最大的一块石头

```
models/joint_rbt3/joint_model.pt        147 MB
src/engine.py                           加载 torch + transformers 做推理
src/trainer.py                          训练脚本（JointRBT3Model：意图头 + 槽位头 + CRF）
src/preprocessor.py / corpus_tools.py   语料处理
src/evaluate.py                         评估
```

- **模型**：RBT3 = 哈工大讯飞的 3 层中文 RoBERTa 蒸馏版。
  一个 backbone 上挂两个头：意图分类 + 槽位 BIO 标注。
- **推理**：按**字**切分（`tokens = list(text)`），`is_split_into_words=True`，
  推完再把 subword 级标签对齐回字符级。
- **阈值**：意图置信度 < 0.5 判为 `none`，走「听不懂」话术。
- **依赖**：`torch` + `transformers`。这就是 `.venv` 7 GB 的由来。

⚠ **tao 是 Go**。这一层没法直译，是必须单独决策的地方（见 §5.1）。

### 3.2 会话层 `backend/assistant/chat.py`（869 行）

不是简单的「一问一答」，有四套状态机叠在一起：

| 机制 | 干什么 | 关键实现 |
|---|---|---|
| **指代消解** | 「刚才那个」「上一个」「上面第二个」→ 回填上一轮的 intent/slots | `_detect_referent_phrase` / `_apply_referent_resolution` |
| **pending 确认** | 危险动作先问一句，下一轮的「是/否/取消」在 NLU 之前就被截住 | `handle_pending_action`（`pending.py`） |
| **锁定指令** | 已确认的方案名/任务名不被后续轮次的模型输出覆盖 | `_apply_locked_directive` |
| **失败提示升级** | 连续听不懂：1 次→重述，2 次→给例句，3 次→给快捷按钮 | `_build_failure_hint_reply` + `_FAILURE_QUICK_ACTIONS` |

**返回契约**（`ChatResponse`，前端按它渲染）：

```
reply             给用户看的文字
output_speech     给 TTS 念的文字（可与 reply 不同）
intent/confidence/slots/missing_slots
dialog_state_detail
raw_entities/tokens/tags        NLU 原始输出，调试用
action_log[]                    这一轮实际做了什么（前端展示"已执行"）
diagnostics[] / warnings[]
pending_action{}                待确认动作
choices[]                       让用户点选的选项（消歧）
confirm_kind                    确认类型
undo_token{}                    撤销凭据
```

⚠ `choices` / `confirm_kind` / `undo_token` 是**给前端渲染按钮用的结构化提示**，
不是让前端去解析 `pending_action` 内部结构 —— 这个边界要照搬。

### 3.3 意图层 —— 32 个意图

```
作息方案   move_schedule  swap_schedule  cancel_schedule  create_schedule
           enable_schedule  disable_schedule  delete_schedule
           shift_schedule_later  shift_schedule_earlier
任务       play_task  stop_task  task_pause/pause_task  task_resume/resume_task
           query_task  broadcast_emergency
媒体       play_media  replace_media  replace_media_in_task
终端       query_terminal  enable_terminal  disable_terminal
           sync_terminal_time  check_terminal
           add_terminal_to_task  remove_terminal_from_task
分区       create_zone  delete_zone  add_terminal_to_zone  remove_terminal_from_zone
音量       adjust_volume
```

**实现体量差得很远**，前四个是大头：

| 意图 | 行数 | | 意图 | 行数 |
|---|---|---|---|---|
| `create_scheme` | **509** | | `add/remove_terminal_to_task` | 261 |
| `cancel_schedule` | **242** | | `play_media` | 252 |
| `shift_schedule` | 233 | | `replace_media_in_task` | 224 |
| `swap_schedule` | 221 | | `enable/disable_schedule` | 222 |
| `move_schedule` | 201 | | `query_task` | 144 |
| `replace_media` | 119 | | `add/remove_terminal_to_zone` | 111 |
| `enable/disable_terminal` | 111 | | `delete_schedule` | 95 |
| `create_zone` | 88 | | `check_terminal` | 76 |
| `adjust_volume` | 76 | | `sync_terminal_time` | 71 |
| `delete_zone` | 70 | | `query_terminal` | 52 |
| `play_task`/`stop_task`/`pause`/`resume` | 各 10~18 | | | |
| | | | **合计** | **3,504** |

这 3,504 行里装的不只是「调接口」，大部分是**消歧和防呆**：
同名方案怎么办、时间点冲突怎么办、终端不在线要不要拦、
批量操作要不要先给预览。这些正是 1:1 复刻必须逐条搬过去的东西。

### 3.4 执行层 —— SDK 调用清单

`remote_client.py` 提供的写侧能力，以及**在 tao 里对应什么**：

| SDK 函数 | 打的接口 | tao 里的等价物 |
|---|---|---|
| `remote_login` | `/auth/login` | 不需要 —— tao 直连库 |
| `remote_ensure_schedule` | 建方案 | `bell` 包：方案 = `task.info` 分组 |
| `remote_rename_schedule_entry` | 改方案名 | `UPDATE task SET info=? WHERE info=?` |
| `remote_set_schedule_status` | 方案启停 | `PUT /api/bell-plans/state` |
| `remote_add_task` / `remote_add_taskinfo` | 建任务 | `task.Create`（含 `mediaoftask`/`terminaloftask`/子任务） |
| `remote_update_task` / `remote_update_taskinfo` | 改任务 | `task.Update` |
| `remote_delete_task` / `remote_delete_taskinfo` | 删任务 | `task.Delete`（带级联预览） |
| `remote_set_task_state` | 任务执行/停止 | `PUT /api/tasks/control/{action}` |
| `remote_set_task_doorno`(enablestate) | 任务启用/停用 | `PUT /api/tasks/project-state`（⚠ 0=启用） |
| `remote_set_task_volume` | 任务音量 | `PUT /api/tasks/volume` |
| `remote_replace_taskmusic` | 换任务媒体 | 重写 `mediaoftask` |
| `remote_replace_taskterminals` | 换任务终端 | 重写 `terminaloftask` |
| `remote_add_temp_task` / `remote_stop_temp_tasks` | 立即播放 | 建一条临时文件广播 + 启动 |
| `remote_set_terminal_volume` | 终端音量 | `PUT /api/terminals/volume` |
| `check_terminal_online_status` | 终端在线 | `terminal.netstate` |
| `remote_zone_items` / `remote_zoneterminal_cached` | 分区 | `zone` 包（`serverplaystream` + `terminalofgroup`） |
| `fetch_remote_all_audio` | 媒体清单 | `GET /api/media` |
| `fetch_remote_all_loc` | 位置/终端清单 | `GET /api/terminals` |
| `remote_fetch_schedule_tasks` | 方案下的任务 | `GET /api/bell-plans/detail` |

**结论：tao 已经把这些能力全做过了。** 换成数据库方式不是要新写一套写入逻辑，
而是把这张表右边一列接上去 —— 这也顺带解决了 SDK 那些老毛病
（token 过期、超时重试、写完还要轮询确认、缓存失效满天飞）。

### 3.5 助手自己的数据存哪

现在全是 **JSON 文件**（`backend/data/`）：

```
assistant_command_logs.json   指令历史（上限 1000 条）
assistant_settings.json       助手设置
broadcast_schedules.json      方案缓存
all_audio.json / all_loc.json / all_task.json   SDK 拉回来的缓存
task_overrides.json           任务覆盖
```

搬到 tao 之后：缓存类的全都不需要了（直接查库）；
**`assistant_command_logs` 和 `assistant_settings` 是真需要持久化的**，
而 tao 有 R1 红线（零 DDL，不能建表）—— 处理方式见 §5.2。

### 3.6 前端 `AiAssistantFloat.vue`（4,776 行）

一个可拖动的浮窗，不只是聊天气泡：

- 消息流 + 打字机效果 + 语音输入
- 按 `choices` 渲染选项按钮、按 `confirm_kind` 渲染确认框、按 `undo_token` 渲染撤销
- `action_log` 渲染成「已执行」卡片
- 指令历史（`/data/assistant_command_logs`）、助手设置（`/data/assistant_settings`）
- 执行前的影响预览（`/data/schedule_impact_preview`）
- 执行完通过 `assistantRefreshBus` 通知当前页面刷新表格
- 错误话术统一走 `utils/httpError.js`

技术栈是 **Vue 2.6 + Element UI 2.13 + Options API**，
tao 是 **Vue 3.5 + Element Plus 2.9 + `<script setup>` + TypeScript**。

---

## 4. 这套助手依赖的「非 tao」概念

搬过去之前要先想清楚这几个概念在 tao 里对应什么 —— 有的能一一对上，有的对不上：

| 助手里的概念 | tao 里 | 对得上吗 |
|---|---|---|
| 方案（schedule/scheme） | 作息方案 = `task.info` 分组 | ✅ 完全对得上 |
| 任务（task） | 文件广播 / 打铃条目 | ✅ |
| 媒体（media） | 文件管理 | ✅ |
| 终端（terminal） | 终端管理 | ✅ |
| 分区（zone） | 终端分区（`serverplaystream`） | ✅ |
| 立即播放（temp task / runtime play） | 无直接对应，需用「建临时任务 + 执行」拼 | ⚠ 要设计 |
| 任务覆盖（task_overrides） | 无 | ⚠ 要设计 |
| 方案模板（schedule_templates） | 无 | ⚠ 要设计 |
| 授权（license） | 注册服务 | 🔶 概念不同，可能不搬 |

---

## 5. 三个必须先拍板的问题

### 5.1 ⭐ NLU 模型怎么办（最关键）

tao 是 Go，模型是 PyTorch。四条路：

| 方案 | 做法 | 好 | 不好 |
|---|---|---|---|
| **A. Python 旁挂**（推荐） | 保留一个极小的 Python 服务，**只做 NLU 推理**：文本进，`intent+slots` 出，不碰数据库。tao 用 HTTP 调它 | 模型原样复用，识别效果零损失；边界干净；这一层以后可独立换 | 部署多一个进程 + 一份 venv（可裁到 ~1.5 GB，去掉训练依赖） |
| **B. 导出 ONNX，Go 里推理** | `joint_model.pt` → ONNX，用 onnxruntime-go | 单进程；无 Python | 引 CGO 依赖（tao 现在只有 2 个纯 Go 依赖，是有意的）；分词器要在 Go 里重写，字级切分 + BIO 对齐容易出细微偏差 |
| **C. 换成大模型 API** | 意图槽位交给 LLM | 不用维护模型；泛化更好 | 要外网（广播系统通常纯内网）；有延迟和费用；行为不可复现 —— **不是 1:1 复刻** |
| **D. 规则引擎** | 正则 + 模板匹配 | 纯 Go，无依赖 | 识别率大幅下降，等于重做 NLU —— **不是 1:1 复刻** |

**建议 A。** 理由：你要的是 1:1，C 和 D 直接违背这一点；B 的风险集中在
「分词与标签对齐在 Go 里重写」，一旦有偏差就是识别结果悄悄变差，很难测出来。
A 把模型这一块原样保住，同时**执行层全部落进 Go + 数据库**，
正好满足「改成数据库方式」这个要求。

### 5.2 助手的两份数据往哪存（R1 红线）

`assistant_command_logs` 和 `assistant_settings` 要持久化，但**不能建表**。
tao 已经有两个先例可以照抄：

- 日志保留期 → 服务端 JSON 文件（`config.logs.settings_file`）
- 看板三块配置 → 服务端 JSON 文件（`dashboard.json`）

所以：**助手设置走同一套 JSON 文件方案**。
指令历史量大一些（上限 1000 条），也走文件；
⚠ 不要往 `log` 表里塞 —— 那张表是操作日志，混进去会污染审计。
但助手**执行的每个写操作都应该照常写审计日志**（复用 tao 的审计中间件）。

### 5.3 前端框架跨代

4,776 行 Vue2 Options API → Vue3 `<script setup>` + TS。
这不是 API 改名那么简单：`this.$refs`、mixin、`$on/$off` 事件总线、
Element UI → Element Plus 的组件属性差异，都要逐处改。
**建议**：结构和交互一比一照搬，代码按 tao 现有前端的写法重写，
不做「先转译再修」——转译出来的代码没人能维护。

---

## 6. 工作量的量级

| 部分 | 体量 | 说明 |
|---|---|---|
| NLU 旁挂服务 | 小 | 从现有代码里裁出推理路径，加个 HTTP 壳 |
| 会话层（chat/pending/指代/失败提示） | 869 行 Py → 约 1,200 行 Go | 状态机，逻辑密但直白 |
| 32 个意图的执行逻辑 | 3,504 行 Py → 约 4,500 行 Go | **大头**，且必须逐条对照 |
| 执行层接 tao 现有 service | 中 | 大部分是「找到对应的 service 方法」，不是新写 |
| 名称解析 / 消歧 / 缓存 | ~1,500 行 | `terminal_lookup` + `helpers` 里那套模糊匹配 |
| 前端浮窗 | 4,776 行 Vue2 → 约 4,000 行 Vue3 | 交互一比一 |
| 前端配套页面 | ~1,200 行 | 时间线 / 快捷操作 |
| 测试 | 现有 Python/Jest 用例可作对照 | 助手那边测试不少，是宝贵的验收基线 |

**这是一个按周计的项目，不是一次会话能做完的。** 必须分阶段，每阶段可独立验收。

---

## 7. 建议的阶段划分

```
阶段 0  骨架         浮窗 UI + /api/assistant/chat 打通（回声测试），NLU 旁挂服务跑起来
阶段 1  只读意图     query_task / query_terminal / check_terminal —— 不写库，先验证链路
阶段 2  单任务写     play_task / stop_task / pause / resume / adjust_volume
阶段 3  方案类       enable/disable/cancel/move/shift/swap —— 大头，逐个搬逐个测
阶段 4  创建类       create_schedule（509 行，最复杂）/ create_zone / play_media
阶段 5  终端与分区   add/remove terminal to task/zone、sync_time、enable/disable
阶段 6  对话增强     指代消解、pending 确认、undo、失败提示升级
阶段 7  前端配套     指令历史、助手设置、影响预览、刷新总线
```

每个阶段的验收标准照 tao 现有做法：真库真服务上跑增删改往返 + 核对数据库 + 清理测试数据。

---

## 8. 需要你回答的

1. **NLU 走哪条路？** 我建议 §5.1 的 A（Python 只做推理旁挂）。
2. **助手的执行要不要经过 tao 现有的权限体系？**
   比如一个只有「文件广播」权限的用户，能不能用助手改作息方案？
   我的建议：**必须走同一套权限**，助手只是另一个入口，不能绕过。
3. **要不要保留「立即播放」（temp task）**？tao 里没有这个概念，
   要新造一套「临时任务」语义（建任务 → 执行 → 播完清理）。
4. **授权（license）那一块搬不搬？** tao 已有「注册服务」，两者概念不同。
5. **先搬到哪个分支？** 建议 `claude/hello-hbe71g` 之外单开一个，
   这个功能体量大，跟主线并行会互相干扰。
