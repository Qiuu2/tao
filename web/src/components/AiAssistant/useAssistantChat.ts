import { nextTick, ref } from "vue";

import { assistantChatApi, type AssistantActionLog, type AssistantChoice, type ChatResponse } from "@/api/modules/assistant";

/**
 * 一轮对话在界面上的样子。
 *
 * 与后端的 ChatResponse 不是一一对应：界面上还要表示"正在想"这种
 * 后端不知道的状态，所以单独一层。
 */
export interface ChatLine {
  id: number;
  role: "user" | "ai";
  text: string;
  /** 正在等后端回话 */
  pending?: boolean;
  intent?: string;
  dialogState?: string;
  choices?: AssistantChoice[];
  confirmKind?: string;
  actionLog?: AssistantActionLog[];
  /** 给运维看的真实原因。默认折叠，不糊到用户脸上。 */
  diagnostics?: Record<string, any>[];
  /** 撤销提示，从 actionLog.details 的 undo_* 几个字段读出来 */
  undo?: { kind: string; taskIds: string[]; summary: string };
  /** 这条是不是"没做成"。用来给气泡换个颜色。 */
  warning?: boolean;
}

/** 会话键：一次浏览器会话一个，刷新页面就换新的。 */
function newSessionKey() {
  const rnd = Math.random().toString(36).slice(2, 10);
  return `web-${Date.now().toString(36)}-${rnd}`;
}

/**
 * 从 actionLog 里读出撤销信息。
 *
 * 后端把它放在 details 的 undo_kind / undo_task_ids / undo_summary 三个字段里
 * （字段名照搬原实现），前端不需要理解 pendingAction 的内部结构。
 */
function readUndo(log?: AssistantActionLog[]): ChatLine["undo"] | undefined {
  if (!log?.length) return undefined;
  const d = log[0]?.details;
  if (!d) return undefined;
  const kind = String(d.undo_kind || "").trim();
  if (!kind) return undefined;
  const ids = Array.isArray(d.undo_task_ids) ? d.undo_task_ids.map((v: any) => String(v)) : [];
  return { kind, taskIds: ids, summary: String(d.undo_summary || "") };
}

/**
 * 判断一条回话是不是"没做成"。
 *
 * 后端不专门给这个标记 —— 它的成功与失败都是一句自然语言。
 * 这里按 dialogState 判，判不出来就当成功：**宁可少标红，也不要把
 * 一句正常的回话染成错误**，那会让用户以为出事了。
 */
function looksLikeWarning(res: ChatResponse): boolean {
  const state = String(res.dialogStateDetail || "");
  return (
    state === "action_error" ||
    state === "forbidden" ||
    state === "nlu_unavailable" ||
    state === "not_implemented" ||
    (res.warnings?.length ?? 0) > 0
  );
}

export function useAssistantChat() {
  const sessionKey = ref(newSessionKey());
  const lines = ref<ChatLine[]>([]);
  const loading = ref(false);
  let seq = 0;

  const nextId = () => ++seq;

  /** 发一句话。text 已经去过首尾空白。 */
  async function send(text: string, scrollToBottom?: () => void) {
    if (!text || loading.value) return;
    lines.value.push({ id: nextId(), role: "user", text });
    const holder: ChatLine = { id: nextId(), role: "ai", text: "", pending: true };
    lines.value.push(holder);
    loading.value = true;
    await nextTick();
    scrollToBottom?.();

    try {
      const { data } = await assistantChatApi({ text, sessionKey: sessionKey.value });
      const res = data as ChatResponse;
      Object.assign(holder, {
        pending: false,
        text: res.reply || "（没有回话）",
        intent: res.intent,
        dialogState: res.dialogStateDetail,
        choices: res.choices,
        confirmKind: res.confirmKind,
        actionLog: res.actionLog,
        diagnostics: res.diagnostics,
        undo: readUndo(res.actionLog),
        warning: looksLikeWarning(res)
      });
    } catch {
      // 网络层的错已经被 axios 拦截器弹过提示了，这里只把气泡填上，
      // 不要让它一直停在"正在想"
      Object.assign(holder, {
        pending: false,
        warning: true,
        text: "没能把这句话发出去，检查一下网络再试试。"
      });
    } finally {
      loading.value = false;
      await nextTick();
      scrollToBottom?.();
    }
  }

  /** 换一个新会话：上下文（"刚才那个"、待确认）一并作废。 */
  function reset() {
    sessionKey.value = newSessionKey();
    lines.value = [];
  }

  return { sessionKey, lines, loading, send, reset };
}
