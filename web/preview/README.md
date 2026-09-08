# 静态预览

把整个前端打成**一个不需要后端的 HTML**，用来给人看东西。
界面与交互都是真实前端，数据是接口快照。

```bash
cd web
npx vite build --config vite.preview.config.ts   # 打成单个 app.js + app.css
node preview/assemble.mjs                        # 内联成 dist-preview/preview.html
```

产物 `dist-preview/preview.html` 约 2.4 MB，双击就能开。
页面底部有一条横幅说明这是快照、新增修改不会生效。

## 两份数据

| 文件 | 装什么 |
|---|---|
| `api-fixtures.json` | 按 `"METHOD /path?query"` 索引的接口快照。找不到精确匹配时退化为只比路径，所以翻页、换排序、改关键字都能命中同一份 |
| `assistant-canned.json` | AI 助手的对话，按**说了什么**索引。预览里能真的聊几句，而不是每句都回同一条 |

没录到的 GET 回一个空集合（页面显示「暂无数据」而不是报错）；
没录到的写操作回一句"静态预览不支持新增/修改/删除"；
没录到的助手对话会**如实说这是预览**并列出录过哪几句 —— 不假装听懂了。

## 怎么重录

起一套真的后端（见《本地测试运行》），然后：

```bash
# 1. 普通接口：把要展示的页面逐个点一遍，用浏览器 DevTools 导出，
#    或者直接 curl 那些 GET 接口，按 "METHOD /path" 存进 api-fixtures.json

# 2. 助手对话：每句用**独立的 sessionKey**，免得上一句的待确认影响下一句
TOKEN=...   # 登录拿到的 access_token
for t in "今天有哪些任务" "查一下A101的状态" "取消明天早读预备铃"; do
  curl -s -X POST http://127.0.0.1:8080/api/assistant/chat \
    -H "x-access-token: $TOKEN" -H 'Content-Type: application/json' \
    -d "{\"text\":\"$t\",\"sessionKey\":\"p-$RANDOM\"}"
done
```

⚠ 录助手对话会**真的执行写操作**（建任务、排启用计划）。录完记得按
《本地测试运行》7.5 节末尾那段 SQL 把数据清干净。

## 一个反复踩到的坑

`assemble.mjs` 里那段 mock 最后会进一个 **JS 模板字符串**，所以里面
不能出现 `\n` `\s` 这类转义 —— 模板字符串会先吃掉一层反斜杠，
`"…\n…"` 就变成一个断成两行的字符串字面量，整段 mock 语法错误，
XHR 拦截根本没装上，页面上表现为所有接口都去请求 `file:///api/...`。

要换行用 `String.fromCharCode(10)`，要正则用 `new RegExp("\\\\s+")`，
一个反斜杠都别写。改完务必跑一次语法检查：

```bash
node -e '
const h=require("fs").readFileSync("dist-preview/preview.html","utf8");
const i=h.indexOf("use strict"), s=h.lastIndexOf("<script>",i), e=h.indexOf("</script>",i);
require("fs").writeFileSync("/tmp/mock.js", h.slice(s+8,e));
' && node --check /tmp/mock.js && echo "mock 语法 OK"
```
