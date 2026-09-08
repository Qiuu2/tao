<!--
  接口调用平台（新版新增，旧系统没有这一页）

  给要对接的人看的一页：有哪些接口、每个接口怎么调、**当场就能试一次**。

  # 与一份静态文档的区别，只有「能试」这一条

  文档写得再全，对接方第一件事仍然是「我先随便调一个看看通不通」。
  在这一页点一下就能看见真实返回，比读完三页文档再自己拼 curl 快得多，
  也把「是我写错了还是接口坏了」这个问题当场分开。

  # 试一试是浏览器**直接打** /openapi/v1，不经后端代理

  这样点出来的结果和对接方在他自己机器上得到的完全一样 ——
  同一条认证路径、同一套权限、同一份错误。
  走后端代理的话，「代理用登录身份还是用密钥」就成了一个说不清的问题，
  而「在平台上能跑、在你那儿跑不通」是最难查的一类问题。

  代价是这一页需要用户**粘一把密钥**进来。这不是缺陷，是这套设计的必然：
  服务器只存密钥的摘要，谁也拿不回明文。刚发完密钥的那一次会自动带过来
  （存在 sessionStorage，关掉标签页就没）。

  # 接口目录来自后端

  前端不写死接口清单 —— 写死了的话，后端改了参数这份清单不会跟着变，
  而它看起来还是对的。见 server/internal/openapi/spec.go。
-->
<template>
  <div class="table-box console-page">
    <!-- 顶部：这是什么 + 密钥 -->
    <div class="card intro">
      <div class="intro-main">
        <h2 class="title">{{ spec?.title || "开发者接口" }}</h2>
        <p class="lead">
          第三方系统（教务、门禁、消防告警）可以直接调这些接口，让广播系统播点什么、查点什么。
          下面每个接口都能<b>当场试一次</b>，返回的就是对方程序会拿到的东西。
        </p>
        <ol class="steps">
          <li>
            管理员在
            <el-link type="primary" :underline="false" @click="goKeys">「开发者密钥」</el-link>
            页发一把密钥，把生成的那串抄给对接方
          </li>
          <li>对接方每个请求带上请求头 <code class="mono">X-API-Key: 那串密钥</code></li>
          <li>接口地址以 <code class="mono">{{ base }}{{ spec?.prefix }}</code> 开头</li>
        </ol>
      </div>
      <div class="intro-side">
        <el-button type="primary" :icon="Download" @click="downloadSpec">下载 OpenAPI 文件</el-button>
        <div class="side-tip">标准 OpenAPI 3.0，可以直接导进 Postman / Apifox / 代码生成器。</div>
      </div>
    </div>

    <!-- 密钥输入 -->
    <div class="card keybar" :class="{ 'keybar--empty': !apiKey }">
      <span class="keybar-label">试一试用的密钥</span>
      <el-input
        v-model="apiKey"
        placeholder="粘贴一把密钥，形如 hb_xxxxxxxx_xxxxxxxx…"
        clearable
        class="keybar-input mono"
        :type="showKey ? 'text' : 'password'"
      >
        <template #suffix>
          <el-icon class="eye" @click="showKey = !showKey"><View v-if="!showKey" /><Hide v-else /></el-icon>
        </template>
      </el-input>
      <el-tag v-if="fromSession" type="success" size="small" effect="plain">已自动带入刚才新建的那把</el-tag>
      <span v-else-if="!apiKey" class="keybar-hint">
        没有密钥？到「开发者密钥」页发一把 —— 服务器只存摘要，已发出去的看不到明文。
      </span>
    </div>

    <div class="body">
      <!-- 左：接口目录 -->
      <div class="card nav">
        <el-input v-model="filter" placeholder="搜接口" clearable size="small" :prefix-icon="Search" class="nav-search" />
        <div v-for="g in visibleGroups" :key="g.name" class="nav-group">
          <div class="nav-group-name">{{ g.name }}</div>
          <div
            v-for="ep in g.endpoints"
            :key="ep.id"
            class="nav-item"
            :class="{ active: current?.id === ep.id }"
            @click="select(ep)"
          >
            <span class="method" :class="'m-' + ep.method.toLowerCase()">{{ ep.method }}</span>
            <span class="nav-summary">{{ ep.summary }}</span>
          </div>
        </div>
        <div v-if="!visibleGroups.length" class="nav-empty">没有匹配的接口</div>
      </div>

      <!-- 右：详情 + 试一试 -->
      <div class="card detail" v-if="current">
        <div class="detail-head">
          <span class="method big" :class="'m-' + current.method.toLowerCase()">{{ current.method }}</span>
          <span class="mono path">{{ spec?.prefix }}{{ current.path }}</span>
          <el-tag size="small" effect="plain" class="right-tag">需要：{{ current.right }}</el-tag>
        </div>
        <h3 class="detail-title">{{ current.summary }}</h3>
        <p class="detail-desc">{{ current.desc }}</p>

        <el-alert
          v-for="(n, i) in current.notes"
          :key="i"
          :title="n"
          :type="n.startsWith('⚠') ? 'warning' : 'info'"
          :closable="false"
          class="note"
        />

        <!-- 参数 -->
        <template v-if="current.params?.length">
          <h4 class="sec">参数</h4>
          <el-table :data="current.params" size="small" class="param-table">
            <el-table-column label="名称" width="150">
              <template #default="{ row }">
                <span class="mono">{{ row.name }}</span>
                <span v-if="row.in === 'path'" class="in-tag">路径</span>
              </template>
            </el-table-column>
            <el-table-column prop="type" label="类型" width="90" />
            <el-table-column label="必填" width="70">
              <template #default="{ row }">
                <span v-if="row.required || row.in === 'path'" class="req">是</span>
                <span v-else class="muted">否</span>
              </template>
            </el-table-column>
            <el-table-column prop="desc" label="说明" min-width="260" />
          </el-table>
        </template>

        <!-- 请求体字段 -->
        <template v-if="current.fields?.length">
          <h4 class="sec">请求体字段</h4>
          <el-table :data="current.fields" size="small" class="param-table">
            <el-table-column label="名称" width="150">
              <template #default="{ row }"><span class="mono">{{ row.name }}</span></template>
            </el-table-column>
            <el-table-column prop="type" label="类型" width="90" />
            <el-table-column label="必填" width="70">
              <template #default="{ row }">
                <span v-if="row.required" class="req">是</span>
                <span v-else class="muted">否</span>
              </template>
            </el-table-column>
            <el-table-column prop="desc" label="说明" min-width="260" />
          </el-table>
        </template>

        <!-- 试一试 -->
        <h4 class="sec">试一试</h4>
        <div class="try">
          <div v-if="current.params?.length" class="try-params">
            <div v-for="p in current.params" :key="p.name" class="try-row">
              <label class="try-label">
                {{ p.name }}
                <span v-if="p.required || p.in === 'path'" class="req">*</span>
              </label>
              <el-input v-model="paramValues[p.name]" :placeholder="p.desc" size="small" class="mono" />
            </div>
          </div>

          <div v-if="current.body !== undefined && current.body !== ''" class="try-body">
            <div class="try-body-head">
              <span>请求体（JSON）</span>
              <el-button link type="primary" size="small" @click="resetBody">恢复示例</el-button>
            </div>
            <el-input v-model="bodyText" type="textarea" :rows="bodyRows" class="mono" spellcheck="false" />
            <div v-if="bodyErr" class="err">{{ bodyErr }}</div>
          </div>

          <div class="curl">
            <div class="curl-head">
              <span>等价的 curl 命令</span>
              <el-button link type="primary" size="small" :icon="CopyDocument" @click="copyCurl">复制</el-button>
            </div>
            <pre class="mono curl-body">{{ curl }}</pre>
          </div>

          <div class="try-bar">
            <el-button
              :type="current.danger ? 'danger' : 'primary'"
              :loading="sending"
              :disabled="!apiKey"
              @click="send"
            >
              {{ current.danger ? "执行（会真的生效）" : "发送请求" }}
            </el-button>
            <span v-if="!apiKey" class="muted">先在上面粘一把密钥</span>
            <span v-else-if="current.danger" class="danger-hint">
              这个接口会真的改动系统 —— 建好的任务、播出去的声音都是真的。
            </span>
          </div>

          <div v-if="resp" class="resp">
            <div class="resp-head">
              <el-tag :type="resp.ok ? 'success' : 'danger'" size="small" effect="dark">
                code {{ resp.code }}
              </el-tag>
              <span class="muted">{{ resp.ms }} ms</span>
              <span v-if="resp.msg" class="resp-msg" :class="{ bad: !resp.ok }">{{ resp.msg }}</span>
            </div>
            <pre class="mono resp-body">{{ resp.text }}</pre>
            <div v-if="!resp.ok" class="resp-help">{{ codeHelp(resp.code) }}</div>
          </div>
        </div>

        <!-- 响应示例 -->
        <template v-if="current.sample">
          <h4 class="sec">响应示例（data 部分）</h4>
          <pre class="mono sample">{{ current.sample }}</pre>
        </template>
      </div>

      <div class="card detail placeholder" v-else>从左边挑一个接口</div>
    </div>
  </div>
</template>

<script setup lang="ts" name="openapiConsole">
import { CopyDocument, Download, Hide, Search, View } from "@element-plus/icons-vue";
import { ElMessage } from "element-plus";
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useRouter } from "vue-router";

import { getApiSpecApi } from "@/api/modules/openapispec";
import type { ApiSpec, SpecEndpoint } from "@/api/modules/openapispec";
import { useUserStore } from "@/stores/modules/user";

const router = useRouter();
const userStore = useUserStore();

const spec = ref<ApiSpec>();
const current = ref<SpecEndpoint>();
const filter = ref("");
const apiKey = ref("");
const showKey = ref(false);
const fromSession = ref(false);

/**
 * 接口的绝对地址前缀。取当前站点 —— 这一页就开在广播服务器上。
 *
 * ⚠ 静态预览是用 file:// 打开的，origin 会是 "file://"，
 *   照抄出来就成了 "file:///openapi/v1"，看着像坏了。
 *   这种情况下给一个占位地址，让人知道该换成自己的服务器。
 */
const base = /^https?:$/.test(location.protocol) ? location.origin : "http://广播服务器:8080";

/** 刚在「开发者密钥」页发完的那把会存在这里，省得用户再翻一遍。 */
const SESSION_KEY = "openapi.lastSecret";

const visibleGroups = computed(() => {
  const kw = filter.value.trim().toLowerCase();
  const groups = spec.value?.groups ?? [];
  if (!kw) return groups;
  return groups
    .map(g => ({ ...g, endpoints: g.endpoints.filter(e => (e.summary + e.path + e.desc).toLowerCase().includes(kw)) }))
    .filter(g => g.endpoints.length);
});

/* ---------------- 试一试的表单 ---------------- */

const paramValues = reactive<Record<string, string>>({});
const bodyText = ref("");
const bodyErr = ref("");
const sending = ref(false);
const resp = ref<{ ok: boolean; code: number | string; msg: string; text: string; ms: number }>();

const bodyRows = computed(() => Math.min(18, Math.max(4, bodyText.value.split("\n").length + 1)));

const select = (ep: SpecEndpoint) => {
  current.value = ep;
  resp.value = undefined;
  bodyErr.value = "";
  Object.keys(paramValues).forEach(k => delete paramValues[k]);
  (ep.params ?? []).forEach(p => (paramValues[p.name] = p.example ?? ""));
  bodyText.value = ep.body ?? "";
};

const resetBody = () => {
  bodyText.value = current.value?.body ?? "";
  bodyErr.value = "";
};

/** 把路径占位换成填的值，并拼上非空的 query。 */
const builtPath = computed(() => {
  const ep = current.value;
  if (!ep) return "";
  let path = (spec.value?.prefix ?? "") + ep.path;
  const qs: string[] = [];
  for (const p of ep.params ?? []) {
    const v = (paramValues[p.name] ?? "").trim();
    if (p.in === "path") {
      // 路径段要编码：方案名里有中文、也可能有 / 之类
      path = path.replace(`{${p.name}}`, v ? encodeURIComponent(v) : `{${p.name}}`);
    } else if (v !== "") {
      qs.push(`${encodeURIComponent(p.name)}=${encodeURIComponent(v)}`);
    }
  }
  return path + (qs.length ? "?" + qs.join("&") : "");
});

const curl = computed(() => {
  const ep = current.value;
  if (!ep) return "";
  const key = apiKey.value.trim() || "你的密钥";
  const lines = [`curl -X ${ep.method} '${base}${builtPath.value}' \\`, `     -H 'X-API-Key: ${key}'`];
  if (bodyText.value.trim()) {
    lines[lines.length - 1] += " \\";
    lines.push(`     -H 'Content-Type: application/json' \\`);
    // curl 的单引号串里不能出现单引号，替换成 shell 里的转义写法
    lines.push(`     -d '${bodyText.value.replace(/'/g, `'\\''`)}'`);
  }
  return lines.join("\n");
});

const copyCurl = async () => {
  try {
    await navigator.clipboard.writeText(curl.value);
    ElMessage.success("已复制");
  } catch {
    // 非 https 下 clipboard 用不了。不报错吓人 —— 命令就摆在上面，选中能抄。
    ElMessage.warning("这个浏览器不允许自动复制，请手动选中");
  }
};

const send = async () => {
  const ep = current.value;
  if (!ep) return;
  bodyErr.value = "";

  let payload: string | undefined;
  if (bodyText.value.trim()) {
    try {
      // 先在本地解析一遍。让 JSON 打错字的人当场看见「第几行不对」，
      // 而不是收到服务端一句「请求参数格式错误」再自己找。
      JSON.parse(bodyText.value);
      payload = bodyText.value;
    } catch (e: any) {
      bodyErr.value = "请求体不是合法的 JSON：" + (e?.message ?? e);
      return;
    }
  }

  sending.value = true;
  const t0 = performance.now();
  try {
    // ⚠ 用原生 fetch，不走项目那个 axios 实例 —— 那个会自动带上登录令牌、
    //   401 时还会跳登录页。这一页要的恰恰是「只用密钥、不用登录身份」，
    //   密钥无效时就该原样看见 401，而不是被弹回登录页。
    const r = await fetch(base + builtPath.value, {
      method: ep.method,
      headers: {
        "X-API-Key": apiKey.value.trim(),
        ...(payload ? { "Content-Type": "application/json" } : {})
      },
      body: payload
    });
    const text = await r.text();
    let code: number | string = r.status;
    let msg = "";
    let pretty = text;
    try {
      const j = JSON.parse(text);
      code = j.code ?? r.status;
      msg = j.msg ?? "";
      pretty = JSON.stringify(j, null, 2);
    } catch {
      /* 不是 JSON 就原样显示 */
    }
    resp.value = { ok: code === 200, code, msg, text: pretty, ms: Math.round(performance.now() - t0) };
  } catch (e: any) {
    resp.value = {
      ok: false,
      code: "网络错误",
      msg: String(e?.message ?? e),
      text: "请求没能发出去。检查服务器地址、或者浏览器是不是拦了跨域。",
      ms: Math.round(performance.now() - t0)
    };
  } finally {
    sending.value = false;
  }
};

/** 业务码的处置建议。照《开发者接口文档》那张表。 */
const codeHelp = (code: number | string) => {
  switch (code) {
    case 40001:
      return "请求填错了。上面 msg 里写了错在哪，照着改 —— 重试没用。";
    case 401:
      return "密钥无效：写错了、被停用了、过期了或被删了。到「开发者密钥」页看看，或者换一把。";
    case 40301:
      return "权限不够：这把密钥归属的账号缺对应权限位，去「用户」页给那个账号配上；也可能服务器正处于备机只读。";
    case 40401:
      return "找不到这个对象。";
    case 50001:
      return "服务器出错，可以重试。反复出现的话让管理员看服务端日志。";
    default:
      return "";
  }
};

const downloadSpec = () => {
  // 这个接口允许 ?token=，因为 window.open 带不上自定义请求头
  const token = userStore.token;
  window.open(`${base}/api/openapi/openapi.json?token=${encodeURIComponent(token)}`, "_blank");
};

const goKeys = () => router.push("/dev/keys");

watch(apiKey, v => {
  // 用户手动改过就不再声称「自动带入」
  if (fromSession.value && v !== sessionStorage.getItem(SESSION_KEY)) fromSession.value = false;
});

onMounted(async () => {
  const { data } = await getApiSpecApi();
  spec.value = data;
  const first = data?.groups?.[0]?.endpoints?.[0];
  if (first) select(first);

  try {
    const saved = sessionStorage.getItem(SESSION_KEY);
    if (saved) {
      apiKey.value = saved;
      fromSession.value = true;
    }
  } catch {
    // 隐私模式下 sessionStorage 会抛。没有就手动粘，不是错误。
  }
});
</script>

<style scoped lang="scss">
.console-page {
  display: flex;
  flex-direction: column;
  gap: 12px;
  height: 100%;
  overflow: auto;
}
.card {
  background: var(--el-bg-color);
  border-radius: 6px;
}
.intro {
  display: flex;
  gap: 24px;
  align-items: flex-start;
  justify-content: space-between;
  padding: 18px 20px;

  .title {
    margin: 0 0 8px;
    font-size: 18px;
  }
  .lead {
    max-width: 900px;
    margin: 0 0 10px;
    line-height: 1.8;
    color: var(--el-text-color-regular);
  }
  .steps {
    margin: 0;
    padding-left: 20px;
    font-size: 13px;
    line-height: 2;
    color: var(--el-text-color-regular);
  }
  .intro-side {
    flex-shrink: 0;
    width: 230px;
    text-align: right;
  }
  .side-tip {
    margin-top: 8px;
    font-size: 12px;
    line-height: 1.7;
    color: var(--el-text-color-secondary);
  }
}
.keybar {
  display: flex;
  gap: 12px;
  align-items: center;
  padding: 12px 20px;

  &--empty {
    border-left: 3px solid var(--el-color-warning);
  }
  .keybar-label {
    flex-shrink: 0;
    font-size: 13px;
    color: var(--el-text-color-regular);
  }
  .keybar-input {
    max-width: 480px;
  }
  .keybar-hint {
    font-size: 12px;
    color: var(--el-text-color-secondary);
  }
  .eye {
    cursor: pointer;
  }
}
.body {
  display: flex;
  flex: 1;
  gap: 12px;
  min-height: 0;
}
.nav {
  flex-shrink: 0;
  width: 270px;
  padding: 12px;
  overflow: auto;

  .nav-search {
    margin-bottom: 10px;
  }
  .nav-group-name {
    padding: 10px 6px 6px;
    font-size: 12px;
    font-weight: 600;
    color: var(--el-text-color-secondary);
  }
  .nav-item {
    display: flex;
    gap: 8px;
    align-items: center;
    padding: 7px 8px;
    cursor: pointer;
    border-radius: 4px;

    &:hover {
      background: var(--el-fill-color-light);
    }
    &.active {
      background: var(--el-color-primary-light-9);
    }
  }
  .nav-summary {
    overflow: hidden;
    font-size: 13px;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .nav-empty {
    padding: 20px 6px;
    font-size: 13px;
    color: var(--el-text-color-secondary);
  }
}
.detail {
  flex: 1;
  min-width: 0;
  padding: 18px 22px;
  overflow: auto;

  &.placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--el-text-color-secondary);
  }
  .detail-head {
    display: flex;
    gap: 10px;
    align-items: center;
  }
  .path {
    font-size: 15px;
  }
  .right-tag {
    margin-left: auto;
  }
  .detail-title {
    margin: 12px 0 6px;
    font-size: 16px;
  }
  .detail-desc {
    margin: 0 0 12px;
    line-height: 1.8;
    color: var(--el-text-color-regular);
  }
  .note {
    margin-bottom: 8px;
  }
  .sec {
    margin: 20px 0 8px;
    font-size: 14px;
  }
}
.method {
  flex-shrink: 0;
  min-width: 52px;
  padding: 2px 6px;
  font-family: Menlo, Consolas, monospace;
  font-size: 11px;
  font-weight: 700;
  color: #fff;
  text-align: center;
  border-radius: 3px;

  &.big {
    min-width: 62px;
    padding: 4px 8px;
    font-size: 12px;
  }
  &.m-get {
    background: #409eff;
  }
  &.m-post {
    background: #67c23a;
  }
  &.m-put {
    background: #e6a23c;
  }
  &.m-delete {
    background: #f56c6c;
  }
}
.param-table {
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 4px;
}
.in-tag {
  margin-left: 6px;
  font-size: 11px;
  color: var(--el-color-warning);
}
.req {
  color: var(--el-color-danger);
}
.muted {
  color: var(--el-text-color-secondary);
}
.try {
  padding: 14px;
  background: var(--el-fill-color-lighter);
  border-radius: 6px;

  .try-row {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 8px;
  }
  .try-label {
    flex-shrink: 0;
    width: 110px;
    font-size: 13px;
    text-align: right;
  }
  .try-body {
    margin-top: 12px;
  }
  .try-body-head,
  .curl-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 4px;
    font-size: 13px;
    color: var(--el-text-color-regular);
  }
  .curl {
    margin-top: 14px;
  }
  .curl-body {
    padding: 10px 12px;
    margin: 0;
    overflow-x: auto;
    font-size: 12px;
    line-height: 1.7;
    color: var(--el-text-color-regular);
    background: var(--el-bg-color);
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 4px;
  }
  .try-bar {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-top: 14px;
  }
  .danger-hint {
    font-size: 12px;
    color: var(--el-color-danger);
  }
}
.resp {
  margin-top: 14px;

  .resp-head {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 6px;
    font-size: 12px;
  }
  .resp-msg.bad {
    color: var(--el-color-danger);
  }
  .resp-body {
    max-height: 420px;
    padding: 10px 12px;
    margin: 0;
    overflow: auto;
    font-size: 12px;
    line-height: 1.7;
    background: var(--el-bg-color);
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 4px;
  }
  .resp-help {
    margin-top: 6px;
    font-size: 12px;
    color: var(--el-color-warning);
  }
}
.sample {
  padding: 10px 12px;
  margin: 0;
  overflow-x: auto;
  font-size: 12px;
  line-height: 1.7;
  background: var(--el-fill-color-lighter);
  border-radius: 4px;
}
.mono {
  font-family: Menlo, Consolas, "Courier New", monospace;
}
.err {
  margin-top: 4px;
  font-size: 12px;
  color: var(--el-color-danger);
}
</style>
