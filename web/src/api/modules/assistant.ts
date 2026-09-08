import http from "@/api";
import { PORT1 } from "@/api/config/servicePort";

/**
 * AI 助手接口。
 *
 * 字段名与后端 assistant.ChatResponse 一一对应，**不要改名** ——
 * 这套字段是从原 Python 实现照搬的，前端渲染逻辑也是照它写的，
 * 改一个名字就要同时改后端、原实现的对照表和这里三处。
 */

/** 助手可不可用。不可用时 reason 会说明缺什么。 */
export interface AssistantStatus {
  enabled: boolean;
  nluReady: boolean;
  nluUrl: string;
  reason?: string;
  lastOkAt?: string;
}

/** 一条待选按钮。pendingAction 的内部结构不需要前端解析，看这个就够。 */
export interface AssistantChoice {
  label: string;
  value: string;
  hint?: string;
}

export interface AssistantActionLog {
  intent?: string;
  mode?: string;
  schedule_name?: string;
  details?: Record<string, any>;
}

export interface ChatResponse {
  reply: string;
  outputSpeech?: string;
  intent: string;
  confidence: number;
  slots: Record<string, string[]>;
  missingSlots: string[];
  dialogStateDetail?: string;
  tokens?: string[];
  tags?: number[];
  actionLog: AssistantActionLog[];
  /** 给运维看的真实原因，不直接展示给用户 */
  diagnostics: Record<string, any>[];
  warnings: Record<string, any>[];
  pendingAction?: Record<string, any>;
  choices?: AssistantChoice[];
  confirmKind?: string;
  undoToken?: Record<string, any>;
}

/** 一条历史消息。role 是 user / assistant。 */
export interface AssistantMessage {
  id: number;
  sessionKey: string;
  role: string;
  text: string;
  intent: string;
  confidence: number;
  status: string;
  createTime: string;
  actionLog?: AssistantActionLog[];
}

/**
 * 助手设置是一张扁平的键值表（后端 assistant_setting）。
 * 目前用到两个键：
 *
 *   default_schedule_kind    学校类型：小学 / 中学 / 高中 / 大学
 *   default_schedule_season  作息季节：夏季 / 冬季
 *
 * 键名与原实现一致，新建作息方案时后端要按它挑模板。
 */
export type AssistantSettings = Record<string, string>;

export const getAssistantStatusApi = () => http.get<AssistantStatus>(PORT1 + "/api/assistant/status", {}, { loading: false });

export const assistantChatApi = (params: { text: string; sessionKey: string }) =>
  // ⚠ loading: false —— 对话有自己的"思考中"气泡，
  //   再叠一层全屏遮罩会把整个页面锁住，用户连别的都干不了
  http.post<ChatResponse>(PORT1 + "/api/assistant/chat", params, { loading: false });

export const getAssistantHistoryApi = (params?: { limit?: number }) =>
  http.get<{ list: AssistantMessage[] }>(PORT1 + "/api/assistant/history", params, { loading: false });

export const clearAssistantHistoryApi = () => http.delete<{ deleted: number }>(PORT1 + "/api/assistant/history");

export const getAssistantSettingsApi = () =>
  http.get<AssistantSettings>(PORT1 + "/api/assistant/settings", {}, { loading: false });

/**
 * 保存助手设置。
 *
 * global = true 是**所有人共用**的那一份，只有管理员能改；
 * 不传就是只改自己的。
 */
export const saveAssistantSettingsApi = (params: { global?: boolean; settings: Record<string, string> }) =>
  http.put<{ saved: number }>(PORT1 + "/api/assistant/settings", params);
