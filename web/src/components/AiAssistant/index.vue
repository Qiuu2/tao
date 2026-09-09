<!--
  AI 助手悬浮窗。

  # 它是什么

  浮在所有页面之上的一个对话框：用中文说一句「把春季作息明天8点的早读挪到9点」，
  它把这句话变成对广播系统的实际操作。收起时是右下角一个球。

  # 从哪搬来的

  照 ai-speaker-web 的 AiAssistantFloat.vue（Vue2 + Element UI，4898 行）重做。
  **不是逐行翻译**：那份文件里有大半是远端诊断面板、模板选择器、指令大全抽屉
  这些与这套库无关或还没接的东西。这里先把主干做扎实 ——
  对话、待确认按钮、撤销、历史 —— 缺的部分如实留空，不摆一个点不动的按钮。

  # 与后端的边界

  前端**不解析** pendingAction 的内部结构，只认后端给的 choices / confirmKind。
  这个边界是照原实现定的，好处是后端换消歧策略时前端不用跟着改。
-->
<template>
  <div class="ai-assistant-root">
    <!-- 收起时的悬浮球 -->
    <button v-if="ready && collapsed" type="button" class="ai-ball" :title='$t("ai.open")' @click="collapsed = false">
      <el-icon><Microphone /></el-icon>
    </button>

    <div v-show="ready && !collapsed" ref="panelRef" class="ai-panel" :style="panelStyle">
      <div class="ai-header" @mousedown.prevent="startDrag">
        <div class="title">
          <el-icon><Microphone /></el-icon>
          <span>{{ $t("ai.title") }}</span>
          <el-tag v-if="!status.nluReady" size="small" type="warning" effect="plain">{{ $t("ai.offline") }}</el-tag>
        </div>
        <div class="actions" @mousedown.stop>
          <el-tooltip :content='$t("ai.history")' placement="top">
            <el-button link :icon="Document" @click.stop="openHistory" />
          </el-tooltip>
          <el-tooltip :content='$t("ai.newSession")' placement="top">
            <el-button link :icon="Refresh" @click.stop="onReset" />
          </el-tooltip>
          <el-tooltip :content='$t("ai.collapse")' placement="top">
            <el-button link :icon="Minus" @click.stop="collapsed = true" />
          </el-tooltip>
        </div>
      </div>

      <!-- NLU 不可用时把原因说清楚，别让用户对着一个不回话的框子发呆 -->
      <el-alert v-if="!status.nluReady" type="warning" :closable="false" show-icon class="ai-offline">
        <template #title>{{ $t("ai.offlineTitle") }}</template>
        <div v-if="status.reason" class="ai-offline-reason">{{ status.reason }}</div>
      </el-alert>

      <div ref="chatBoxRef" class="chat-box">
        <div v-if="!lines.length" class="placeholder">
          <p>{{ $t("ai.trySaying") }}</p>
          <!--
            例句一律保持中文，英文界面下也不翻 —— NLU 只认中文，
            翻成英文的话点一下发出去的是它听不懂的话，比不给例句更糟。
            回话同理：那套措辞是按 SHA1 从中文变体池里稳定挑的（见 reply.go），
            换语言等于重做一套。所以两种语言下都明说这件事，别让人以为是坏了。
          -->
          <p class="sample-note">{{ $t("ai.chineseOnly") }}</p>
          <button v-for="s in samples" :key="s" type="button" class="sample" @click="useSample(s)">{{ s }}</button>
        </div>

        <div v-for="msg in lines" :key="msg.id" :class="['chat-line', msg.role]">
          <div :class="['bubble', { warning: msg.warning, pending: msg.pending }]">
            <div v-if="msg.pending" class="thinking">
              <span>{{ $t("ai.thinking") }}</span>
              <span class="dots"><i /><i /><i /></span>
            </div>
            <div v-else class="text">{{ msg.text }}</div>

            <!-- 待确认按钮。点一下等于把 value 当成一句话发出去 —— 与用户自己打字完全同一条路 -->
            <div v-if="msg.choices?.length" class="choices">
              <el-button
                v-for="c in msg.choices"
                :key="c.label"
                size="small"
                type="primary"
                plain
                :disabled="loading"
                @click="send(c.value)"
              >
                {{ c.label }}
              </el-button>
            </div>

            <div v-if="msg.undo" class="undo">
              <!-- 这句是发给 NLU 的原话，不能翻 —— NLU 只认中文 -->
              <el-button size="small" type="warning" plain :disabled="loading" @click="send('停止播放')"><!-- i18n-ignore -->
                {{ $t("ai.stopThat", { what: msg.undo.summary || $t("ai.thatOne") }) }}
              </el-button>
            </div>

            <!-- 诊断默认折叠：真实原因是给运维看的，不糊到用户脸上 -->
            <details v-if="msg.diagnostics?.length" class="diag">
              <summary>{{ $t("ai.errorDetail") }}</summary>
              <pre v-for="(d, i) in msg.diagnostics" :key="i">{{ formatDiag(d) }}</pre>
            </details>
          </div>
        </div>
      </div>

      <div class="ai-input">
        <el-input
          ref="inputRef"
          v-model="command"
          type="textarea"
          :rows="2"
          resize="none"
          :disabled="!status.nluReady"
          :placeholder='$t("ai.inputPlaceholder")'
          @keydown.enter.exact.prevent="onSend"
        />
        <div class="ai-actions">
          <span class="hint">{{ $t("ai.sendHint") }}</span>
          <el-button size="small" :disabled="!command.trim()" @click="command = ''">{{ $t("ai.clear") }}</el-button>
          <el-button
            size="small"
            type="primary"
            :loading="loading"
            :disabled="!status.nluReady || !command.trim()"
            @click="onSend"
          >
            {{ $t("ai.send") }}
          </el-button>
        </div>
      </div>
    </div>

    <el-drawer v-model="historyOpen" :title='$t("ai.history")' size="420px" :append-to-body="true">
      <div class="history-toolbar">
        <span class="history-count">共 {{ history.length }} 条</span>
        <el-button size="small" :disabled="!history.length" @click="onClearHistory">{{ $t("ai.clear") }}</el-button>
      </div>
      <el-empty v-if="!history.length" :description='$t("ai.noHistory")' />
      <div v-for="h in history" :key="h.id" :class="['history-item', h.role]">
        <div class="history-head">
          <span class="role">{{ h.role === "user" ? "我" : "小电" }}</span>
          <span class="time">{{ h.createTime }}</span>
        </div>
        <div class="history-text">{{ h.text }}</div>
        <div v-if="h.intent && h.role !== 'user'" class="history-meta">意图：{{ h.intent }}</div>
      </div>
    </el-drawer>
  </div>
</template>

<script setup lang="ts" name="AiAssistant">
import { useI18n } from "vue-i18n";
import { Document, Microphone, Minus, Refresh } from "@element-plus/icons-vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref } from "vue";

import {
  clearAssistantHistoryApi,
  getAssistantHistoryApi,
  getAssistantStatusApi,
  type AssistantMessage,
  type AssistantStatus
} from "@/api/modules/assistant";

import { useAssistantChat } from "./useAssistantChat";

// 脚本里拼的文案用 t()；模板里的 $t 不用引入
const { t } = useI18n();

const { lines, loading, send: sendText, reset } = useAssistantChat();

const collapsed = ref(true);
const command = ref("");
const chatBoxRef = ref<HTMLElement>();
const panelRef = ref<HTMLElement>();
const inputRef = ref();

const status = reactive<AssistantStatus>({ enabled: false, nluReady: false, nluUrl: "" });
/** 助手整个功能关掉时连球都不出现 —— 摆一个点了没反应的球比没有更糟 */
const ready = computed(() => status.enabled);

// 例句一律是中文，英文界面下也不翻。
//
// 它们不是文案，是**点一下就原样发出去的指令**，而 NLU 只认中文 ——
// 翻成英文的话，点一下发出去的是一句它听不懂的话，比不给例句更糟。
// 英文界面上会多一句 ai.chineseOnly 把这件事说明白。
// prettier-ignore
const samples = ["今天有哪些任务", "把A101教室音箱的音量调到 60", "取消明天早读预备铃", "停用方案春季作息"]; // i18n-ignore

function scrollToBottom() {
  const el = chatBoxRef.value;
  if (el) el.scrollTop = el.scrollHeight;
}

async function send(text: string) {
  await sendText(text, scrollToBottom);
}

function onSend() {
  const text = command.value.trim();
  if (!text) return;
  command.value = "";
  send(text);
}

function useSample(s: string) {
  command.value = s;
  nextTick(() => inputRef.value?.focus());
}

function onReset() {
  reset();
  command.value = "";
}

function formatDiag(d: Record<string, any>) {
  try {
    return JSON.stringify(d, null, 2);
  } catch {
    return String(d);
  }
}

// ---------- 历史 ----------

const historyOpen = ref(false);
const history = ref<AssistantMessage[]>([]);

async function openHistory() {
  historyOpen.value = true;
  try {
    const { data } = await getAssistantHistoryApi({ limit: 200 });
    history.value = data?.list ?? [];
  } catch {
    history.value = [];
  }
}

async function onClearHistory() {
  await ElMessageBox.confirm(t("ai.clearConfirm"), t("ai.clearHistory"), { type: "warning" });
  await clearAssistantHistoryApi();
  history.value = [];
  ElMessage.success(t("ai.cleared"));
}

// ---------- 拖动 ----------
//
// 只在标题栏上按住才拖。位置用 fixed 的 left/top，
// 拖完不落库 —— 这是个临时的窗口位置，记住它意义不大，
// 而且存起来还要考虑不同屏幕尺寸下会不会拖出可视区。

const pos = reactive({ left: 0, top: 0, moved: false });
let dragFrom = { x: 0, y: 0, left: 0, top: 0 };

const panelStyle = computed(() => {
  if (!pos.moved) return {};
  return { left: `${pos.left}px`, top: `${pos.top}px`, right: "auto", bottom: "auto" };
});

function startDrag(e: MouseEvent) {
  const el = panelRef.value;
  if (!el) return;
  const rect = el.getBoundingClientRect();
  dragFrom = { x: e.clientX, y: e.clientY, left: rect.left, top: rect.top };
  pos.left = rect.left;
  pos.top = rect.top;
  pos.moved = true;
  window.addEventListener("mousemove", onDrag);
  window.addEventListener("mouseup", stopDrag);
}

function onDrag(e: MouseEvent) {
  const el = panelRef.value;
  if (!el) return;
  const w = el.offsetWidth;
  const h = el.offsetHeight;
  // 夹在可视区内：拖出去就再也拖不回来了
  const left = Math.min(Math.max(0, dragFrom.left + (e.clientX - dragFrom.x)), window.innerWidth - w);
  const top = Math.min(Math.max(0, dragFrom.top + (e.clientY - dragFrom.y)), window.innerHeight - h);
  pos.left = left;
  pos.top = top;
}

function stopDrag() {
  window.removeEventListener("mousemove", onDrag);
  window.removeEventListener("mouseup", stopDrag);
}

onBeforeUnmount(stopDrag);

onMounted(async () => {
  try {
    const { data } = await getAssistantStatusApi();
    Object.assign(status, data);
  } catch {
    // 状态查不到就当没开：不出现，也不报错打扰用户
    status.enabled = false;
  }
});
</script>

<style scoped lang="scss">
.ai-ball {
  position: fixed;
  right: 24px;
  bottom: 24px;
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 52px;
  height: 52px;
  color: #ffffff;
  cursor: pointer;
  background: var(--el-color-primary);
  border: none;
  border-radius: 50%;
  box-shadow: 0 6px 18px rgb(0 0 0 / 22%);
  transition: transform 0.15s;
  &:hover {
    transform: scale(1.06);
  }
  .el-icon {
    font-size: 24px;
  }
}
.ai-panel {
  position: fixed;
  right: 24px;
  bottom: 24px;
  z-index: 2000;
  display: flex;
  flex-direction: column;
  width: 380px;
  height: 520px;
  overflow: hidden;
  background: var(--el-bg-color);
  border: 1px solid var(--el-border-color-light);
  border-radius: 10px;
  box-shadow: 0 8px 28px rgb(0 0 0 / 18%);
}
.ai-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 10px 8px 14px;
  cursor: move;
  user-select: none;
  background: var(--el-color-primary);
  .title {
    display: flex;
    gap: 6px;
    align-items: center;
    font-size: 14px;
    font-weight: 600;
    color: #ffffff;
  }
  .actions :deep(.el-button) {
    color: #ffffff;
  }
}
.ai-offline {
  margin: 8px;
  .ai-offline-reason {
    margin-top: 4px;
    font-size: 12px;
    word-break: break-all;
    opacity: 0.75;
  }
}
.chat-box {
  flex: 1;
  padding: 12px;
  overflow-y: auto;
  background: var(--el-fill-color-lighter);
}
.placeholder {
  display: flex;
  flex-direction: column;
  gap: 6px;
  align-items: flex-start;
  font-size: 13px;
  color: var(--el-text-color-secondary);
  .sample-note {
    margin: -2px 0 2px;
    font-size: 12px;
    line-height: 1.5;
    color: var(--el-text-color-placeholder);
  }
  .sample {
    padding: 4px 10px;
    font-size: 12px;
    color: var(--el-color-primary);
    cursor: pointer;
    background: var(--el-color-primary-light-9);
    border: 1px solid var(--el-color-primary-light-7);
    border-radius: 12px;
  }
}
.chat-line {
  display: flex;
  margin-bottom: 10px;
  &.user {
    justify-content: flex-end;
  }
}
.bubble {
  max-width: 84%;
  padding: 8px 10px;
  font-size: 13px;
  line-height: 1.6;
  overflow-wrap: anywhere;
  white-space: pre-wrap;
  background: var(--el-bg-color);
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 8px;
  .chat-line.user & {
    color: #ffffff;
    background: var(--el-color-primary);
    border-color: var(--el-color-primary);
  }
  &.warning {
    background: var(--el-color-warning-light-9);
    border-color: var(--el-color-warning-light-5);
  }
}
.thinking {
  display: flex;
  gap: 4px;
  align-items: center;
  color: var(--el-text-color-secondary);
  .dots i {
    display: inline-block;
    width: 4px;
    height: 4px;
    margin-left: 2px;
    background: currentcolor;
    border-radius: 50%;
    animation: blink 1.2s infinite;
    &:nth-child(2) {
      animation-delay: 0.2s;
    }
    &:nth-child(3) {
      animation-delay: 0.4s;
    }
  }
}

@keyframes blink {
  0%,
  80%,
  100% {
    opacity: 0.2;
  }
  40% {
    opacity: 1;
  }
}
.choices,
.undo {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 8px;
}
.diag {
  margin-top: 6px;
  font-size: 12px;
  summary {
    color: var(--el-text-color-secondary);
    cursor: pointer;
  }
  pre {
    max-height: 160px;
    padding: 6px;
    margin: 4px 0 0;
    overflow: auto;
    background: var(--el-fill-color);
    border-radius: 4px;
  }
}
.ai-input {
  padding: 8px;
  border-top: 1px solid var(--el-border-color-lighter);
}
.ai-actions {
  display: flex;
  gap: 6px;
  align-items: center;
  justify-content: flex-end;
  margin-top: 6px;
  .hint {
    margin-right: auto;
    font-size: 12px;
    color: var(--el-text-color-placeholder);
  }
}
.history-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
  .history-count {
    font-size: 13px;
    color: var(--el-text-color-secondary);
  }
}
.history-item {
  padding: 8px 10px;
  margin-bottom: 8px;
  background: var(--el-fill-color-lighter);
  border-radius: 6px;
  &.user {
    background: var(--el-color-primary-light-9);
  }
  .history-head {
    display: flex;
    justify-content: space-between;
    margin-bottom: 4px;
    font-size: 12px;
    color: var(--el-text-color-secondary);
  }
  .history-text {
    font-size: 13px;
    overflow-wrap: anywhere;
    white-space: pre-wrap;
  }
  .history-meta {
    margin-top: 4px;
    font-size: 12px;
    color: var(--el-text-color-placeholder);
  }
}
</style>
