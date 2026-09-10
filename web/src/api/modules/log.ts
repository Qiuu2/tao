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

/* ---------------- 日志保留期 ---------------- */

/**
 * 保留期的四档。存到后端的就是这几个字符串，不是天数 ——
 * 「1 个月」按自然月算，2 月和 8 月不一样长。
 */
export type RetentionOption = "1m" | "3m" | "6m" | "1y";

export interface RetentionChoice {
  value: RetentionOption;
  label: string;
}

export interface RetentionSettings {
  option: RetentionOption;
  label: string;
  /** 当前设置下的保留边界（YYYY-MM-DD），这一天**之前**的会被滚掉 */
  cutoffDate: string;
  choices: RetentionChoice[];
  /** 上一次滚动清理的时间，空串表示这个进程起来之后还没跑过 */
  lastRunAt: string;
  lastResult: string;
}

export interface RetentionPurgeResult {
  cutoff: string;
  operationRows: number;
}

/** 保存保留期的返回：设置本身 + 这一下立刻滚掉了什么 */
export interface RetentionSaveResult {
  settings: RetentionSettings;
  purge: RetentionPurgeResult | null;
}

export const getRetentionApi = () => http.get<RetentionSettings>(PORT1 + `/api/logs/retention`, {}, { loading: false });

/**
 * 保存保留期，并**立刻滚一次**。
 *
 * 界面上就是「选完点确定」那一下：存设置和清理是同一个动作，
 * 分开做会出现「设置存下来了但清理没跑」的中间态，用户看不出来。
 * 清理的边界与每天那次定时滚动完全一致。
 */
export const setRetentionApi = (option: RetentionOption) =>
  http.put<RetentionSaveResult>(PORT1 + `/api/logs/retention`, { option });
