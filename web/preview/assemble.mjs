// 把 dist-preview 的 app.css / app.js 与接口快照组装成单个自足的 HTML。
//
// 用法（在 web/ 下）：
//
//   npx vite build --config vite.preview.config.ts   # 打成单个 app.js + app.css
//   node preview/assemble.mjs                        # 内联成 dist-preview/preview.html
//
// 产物是一个**不需要后端**的单文件页面：界面与交互都是真实前端，
// 数据走 api-fixtures.json 里的接口快照，AI 助手的对话走 assistant-canned.json。
// 拿它给人看东西时，记得那是快照 —— 新增/修改不会真的生效，页面上有横幅说明。
//
// 快照怎么更新：起一套真的后端，照着 preview/README.md 的脚本重录一遍。
import { readFileSync, writeFileSync } from "fs";

import { dirname, resolve } from "path";
import { fileURLToPath } from "url";

const HERE = dirname(fileURLToPath(import.meta.url));   // web/preview
const DIST = resolve(HERE, "..", "dist-preview");
const SCRATCH = HERE;

const css = readFileSync(`${DIST}/app.css`, "utf8");

// vue-i18n 的实体解码器源码里带一个字面量 U+FFFD（无效码点时的返回值）。
// Artifact 的发布校验会把裸的 U+FFFD 判为编码损坏而拒收，
// 换成等价的 � 转义：JS 语义完全一致，字节里不再有该字符。
const js = readFileSync(`${DIST}/app.js`, "utf8").replace(/�/g, "\\uFFFD");
const fixtures = JSON.parse(readFileSync(`${SCRATCH}/api-fixtures.json`, "utf8"));
// AI 助手的对话按**说了什么**分别录了一份，这样预览里能真的聊几句，
// 而不是每句都回同一条。没录到的句子回一句实话，不假装听懂了。
const canned = JSON.parse(readFileSync(`${SCRATCH}/assistant-canned.json`, "utf8"));

// index.html 里那段首屏 loading 的内联样式/脚本，原样保留
const indexHtml = readFileSync(`${DIST}/index.html`, "utf8");
const bodyInner = indexHtml.match(/<body>([\s\S]*)<\/body>/)[1];

const mock = `
(function () {
  "use strict";
  var FIXTURES = ${JSON.stringify(fixtures)};
  var CANNED = ${JSON.stringify(canned)};

  // 按 "METHOD pathname?search" 精确匹配；匹配不到再退化为只比 pathname，
  // 这样翻页、改排序、换关键字等只影响 query 的请求也能命中同一份快照。
  var byPath = {};
  Object.keys(FIXTURES).forEach(function (k) {
    var sp = k.indexOf(" ");
    var method = k.slice(0, sp);
    var path = k.slice(sp + 1).split("?")[0];
    var pk = method + " " + path;
    if (!byPath[pk]) byPath[pk] = FIXTURES[k];
  });

  // 助手的对话按文本查录好的那一份。
  // 预览里点「只这一次」这类按钮也走这条路 —— 按钮发出去的就是一句话。
  function lookupChat(bodyText) {
    var text = "";
    try { text = (JSON.parse(bodyText) || {}).text || ""; } catch (e) { return null; }
    text = String(text).trim();
    if (CANNED[text]) return CANNED[text];
    // 去掉空白再试一次，用户可能多打了空格
    var compact = text.replace(/\s+/g, "");
    for (var k in CANNED) {
      if (k.replace(/\s+/g, "") === compact) return CANNED[k];
    }
    // ⚠ 这段最后会进一个**模板字符串**，所以不能写 \\n 这类转义 ——
    //   模板字符串会先把它变成真的换行，字符串字面量就断成两行了。
    //   用 String.fromCharCode(10) 绕开，一个反斜杠都不出现。
    var NL = String.fromCharCode(10);
    return {
      code: 200, msg: "ok",
      data: {
        reply: "这是静态预览，只录了几句示例：" + NL + Object.keys(CANNED).slice(0, 6).join(NL) +
               NL + "照着说一句就能看到真实的回话。",
        intent: "none", confidence: 0, slots: {}, missingSlots: [],
        actionLog: [], diagnostics: [], warnings: [],
        dialogStateDetail: "preview_only"
      }
    };
  }

  function lookup(method, url) {
    var u;
    try { u = new URL(url, location.href); } catch (e) { return null; }
    if (u.pathname.indexOf("/api") !== 0) return null;
    var exact = FIXTURES[method + " " + u.pathname + u.search];
    if (exact) return exact;
    var loose = byPath[method + " " + u.pathname];
    if (loose) return loose;
    if (method !== "GET") {
      return { code: 40300, msg: "静态预览：本页面是接口快照，不支持新增 / 修改 / 删除", data: null };
    }
    // 没录到的 GET：给一个空集合，页面显示「暂无数据」而不是报错
    return { code: 200, msg: "ok", data: { list: [], total: 0, pageNum: 1, pageSize: 10 } };
  }

  var RealXHR = window.XMLHttpRequest;

  function FakeXHR() {
    this.readyState = 0;
    this.status = 0;
    this.statusText = "";
    this.response = "";
    this.responseText = "";
    this.responseType = "";
    this.responseURL = "";
    this.timeout = 0;
    this.withCredentials = false;
    this.upload = { addEventListener: function () {}, removeEventListener: function () {} };
    this._headers = {};
    this._listeners = {};
  }
  FakeXHR.UNSENT = 0; FakeXHR.OPENED = 1; FakeXHR.HEADERS_RECEIVED = 2;
  FakeXHR.LOADING = 3; FakeXHR.DONE = 4;

  FakeXHR.prototype.open = function (method, url) {
    this._method = String(method).toUpperCase();
    this._url = url;
    this.readyState = 1;
    this._fire("readystatechange");
  };
  FakeXHR.prototype.setRequestHeader = function (k, v) { this._headers[k] = v; };
  FakeXHR.prototype.getAllResponseHeaders = function () {
    return "content-type: application/json\\r\\n";
  };
  FakeXHR.prototype.getResponseHeader = function (k) {
    return String(k).toLowerCase() === "content-type" ? "application/json" : null;
  };
  FakeXHR.prototype.overrideMimeType = function () {};
  FakeXHR.prototype.abort = function () {
    this.readyState = 0;
    this._fire("abort"); this._fire("loadend");
  };
  FakeXHR.prototype.addEventListener = function (t, fn) {
    (this._listeners[t] = this._listeners[t] || []).push(fn);
  };
  FakeXHR.prototype.removeEventListener = function (t, fn) {
    var a = this._listeners[t]; if (!a) return;
    var i = a.indexOf(fn); if (i >= 0) a.splice(i, 1);
  };
  FakeXHR.prototype._fire = function (type) {
    var ev = { type: type, target: this, currentTarget: this };
    var on = this["on" + type];
    if (typeof on === "function") { try { on.call(this, ev); } catch (e) { console.error(e); } }
    (this._listeners[type] || []).forEach(function (fn) {
      try { fn.call(this, ev); } catch (e) { console.error(e); }
    }, this);
  };
  FakeXHR.prototype.send = function (sendBody) {
    var self = this;
    var body;
    if (this._method === "POST" && String(this._url).indexOf("/api/assistant/chat") >= 0) {
      body = lookupChat(sendBody);
    } else {
      body = lookup(this._method, this._url);
    }
    // 非 /api 的请求交回真实 XHR（例如 sourcemap、静态资源）
    if (body === null) {
      var real = new RealXHR();
      real.open(this._method, this._url, true);
      Object.keys(this._headers).forEach(function (k) { real.setRequestHeader(k, self._headers[k]); });
      ["load", "error", "abort", "timeout", "loadend", "readystatechange"].forEach(function (t) {
        real["on" + t] = function () {
          self.readyState = real.readyState; self.status = real.status;
          self.response = real.response; self.responseText = real.responseText;
          self._fire(t);
        };
      });
      real.send.apply(real, arguments);
      return;
    }
    // 给一点点延迟，让 loading 动画有机会出现，观感更接近真实请求
    setTimeout(function () {
      var text = JSON.stringify(body);
      self.status = 200;
      self.statusText = "OK";
      self.responseURL = String(self._url);
      self.responseText = text;
      self.response = self.responseType === "json" ? body : text;
      self.readyState = 4;
      self._fire("readystatechange");
      self._fire("load");
      self._fire("loadend");
    }, 90 + Math.random() * 120);
  };

  window.XMLHttpRequest = FakeXHR;

  // 少数地方可能直接用 fetch，一并接管
  var realFetch = window.fetch ? window.fetch.bind(window) : null;
  window.fetch = function (input, init) {
    var url = typeof input === "string" ? input : (input && input.url) || "";
    var method = ((init && init.method) || (input && input.method) || "GET").toUpperCase();
    var body = lookup(method, url);
    if (body === null && realFetch) return realFetch(input, init);
    return Promise.resolve(new Response(JSON.stringify(body), {
      status: 200, headers: { "Content-Type": "application/json" }
    }));
  };

  // Service Worker 在静态托管下没有意义，直接屏蔽注册，避免控制台噪音
  if (navigator.serviceWorker && navigator.serviceWorker.register) {
    navigator.serviceWorker.register = function () { return Promise.reject(new Error("preview")); };
  }
})();
`;

const banner = `
(function () {
  function mount() {
    if (document.getElementById("preview-banner")) return;
    var b = document.createElement("div");
    b.id = "preview-banner";
    b.innerHTML =
      '<span><b>静态预览</b> · 界面与交互为真实前端，数据是后端接口快照（演示数据）· ' +
      '登录：<code>admin</code> / <code>123456</code>（本页任意密码均可）· 新增与修改不会生效</span>' +
      '<button aria-label="关闭">×</button>';
    document.body.appendChild(b);
    b.querySelector("button").onclick = function () { b.remove(); };
  }
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", mount);
  } else { mount(); }
})();
`;

const bannerCss = `
/* ⚠ 左对齐而不是居中：AI 助手浮窗在右下角，居中的横幅会压在它的发送按钮上。
   max-width 也留出右侧 420px 给浮窗。 */
#preview-banner{position:fixed;left:16px;bottom:16px;z-index:99999;
  display:flex;align-items:center;gap:12px;max-width:min(820px,calc(100vw - 452px));
  padding:10px 14px;border-radius:10px;font-size:13px;line-height:1.5;
  color:#e8eaed;background:rgba(28,32,38,.94);border:1px solid rgba(255,255,255,.14);
  box-shadow:0 8px 28px rgba(0,0,0,.32);backdrop-filter:blur(6px)}
#preview-banner code{padding:1px 5px;border-radius:4px;background:rgba(255,255,255,.13);
  font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px}
#preview-banner b{color:#6ee7b7}
#preview-banner button{flex:none;width:22px;height:22px;padding:0;cursor:pointer;
  color:#e8eaed;background:transparent;border:0;border-radius:5px;font-size:17px;line-height:1}
#preview-banner button:hover{background:rgba(255,255,255,.16)}
@media (max-width:640px){#preview-banner{font-size:12px}}
`;

const html = `<title>IP数字网络广播系统</title>
<style>${css}</style>
<style>${bannerCss}</style>
<script>${mock}</script>
<body-content>
${bodyInner}
<script type="module">${js}</script>
<script>${banner}</script>
`;

// Artifact 会自动包 <body>，这里去掉占位标记
// 产物放 web/dist-preview/ 下，与构建产物在一起，别污染仓库
writeFileSync(resolve(DIST, "preview.html"), html.replace("<body-content>\n", ""));
console.log("生成 dist-preview/preview.html:", (html.length / 1024 / 1024).toFixed(2), "MB");
console.log("接口快照条目:", Object.keys(fixtures).length);
