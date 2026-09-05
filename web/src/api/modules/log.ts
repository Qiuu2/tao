import http from "@/api";
import { PORT1 } from "@/api/config/servicePort";

/** 一行操作日志。log 表是 Web 与后台 C 服务共用的 */
export interface LogEntry {
  id: number;
  user: string;
  operate: string;
  ip: string;
  time: string;
  info: string;
  /** 来源猜测，纯展示：后台服务 / Web / 未知 */
  source: string;
}

export interface LogStats {
  total: number;
  earliest: string;
  latest: string;
  /** 后台 C 服务写的条数——清空会连它们一起清掉 */
  fromServer: number;
}

export type LogClearMode = "all" | "beforeDate" | "keepDays";

export interface LogClearResult {
  deleted: number;
  auditLogId: number;
  kept: number;
  describe: string;
}

export interface TaskLogFile {
  name: string;
  date: string;
  size: number;
  modTime: string;
  /** 今天的文件，后台可能正在写，删除时跳过 */
  today: boolean;
  writable: boolean;
}

export interface TaskLogList {
  dir: string;
  files: TaskLogFile[];
  total: number;
  totalSize: number;
  today: string;
  dirWritable: boolean;
  /**
   * 一个文件都没有时的原因说明。
   * 目前只有一种：目录还不存在 —— 后台服务写第一条任务日志时才建它。
   */
  note?: string;
}

export interface TaskLogContent {
  name: string;
  size: number;
  truncated: boolean;
  content: string;
  /** 按 GBK 转码过的行数；不为 0 说明后台服务在这个文件里混用了两种编码 */
  gbkLines: number;
}

export interface TaskLogDeletePreview {
  files: TaskLogFile[];
  count: number;
  size: number;
  skippedToday: string[];
  skippedReadonly: string[];
}

export interface TaskLogDeleteResult {
  deleted: string[];
  freedBytes: number;
  skippedToday: string[];
  skippedReadonly: string[];
  failed: string[];
}

/* ---------------- 操作日志 ---------------- */

export const getLogListApi = (params: any) => {
  return http.get<{ list: LogEntry[]; total: number; pageNum: number; pageSize: number }>(PORT1 + `/api/logs`, params, {
    loading: false
  });
};

export const getLogStatsApi = () => http.get<LogStats>(PORT1 + `/api/logs/stats`, {}, { loading: false });

export const clearLogsApi = (data: { mode: LogClearMode; beforeDate?: string; keepDays?: number }) => {
  return http.delete<LogClearResult>(PORT1 + `/api/logs`, {}, { data: { ...data, confirmed: true } });
};

/* ---------------- 任务日志 ---------------- */

export const getTaskLogFilesApi = (params: { from?: string; to?: string } = {}) =>
  http.get<TaskLogList>(PORT1 + `/api/task-logs/files`, params, { loading: false });

export const readTaskLogApi = (name: string, tailBytes = 0) =>
  http.get<TaskLogContent>(PORT1 + `/api/task-logs/files/${encodeURIComponent(name)}`, { tailBytes });

export const previewDeleteTaskLogsApi = (params: { mode: LogClearMode; beforeDate?: string; keepDays?: number }) =>
  http.get<TaskLogDeletePreview>(PORT1 + `/api/task-logs/delete-preview`, params);

export const deleteTaskLogsApi = (data: { mode: LogClearMode; beforeDate?: string; keepDays?: number }) =>
  http.delete<TaskLogDeleteResult>(PORT1 + `/api/task-logs`, {}, { data: { ...data, confirmed: true } });
