import http from "@/api";
import { PORT1 } from "@/api/config/servicePort";

/** 12 态字典，由服务端下发，保证前后端同一份（BR-215） */
export interface OfflineStateDef {
  value: number;
  text: string;
  /** 该状态是否允许 Web 写。进行中/完成态归后台服务写（BR-203） */
  writable: boolean;
}

export type OfflineMode = "idle" | "immediate" | "deleteIdle" | "deleteNow" | "stop";

export interface OfflineSummary {
  offlineMedia: number;
  offlineMediaOfTerminal: number;
  offlineTask: number;
  offlineTaskOfTerminal: number;
  /** task.offlinestate 不为 0 的任务数 */
  tasksMarked: number;
}

export interface OfflineMediaStatus {
  mediaId: number;
  mediaName: string;
  terminalId: number;
  terminalName: string;
  taskId: number;
  taskName: string;
  offlinestate: number;
  offlineStateText: string;
  sort: number;
  /** offlinemedia 副本已被清掉，旧版这种记录会整条消失 */
  copyMissing: boolean;
  terminalMissing: boolean;
}

export interface OfflineTaskStatus {
  taskId: number;
  taskName: string;
  terminalId: number;
  terminalName: string;
  offlinestate: number;
  offlineStateText: string;
  area: string;
  copyMissing: boolean;
  terminalMissing: boolean;
  mediaCount: number;
}

export interface OfflineMediaResult {
  mediaCount: number;
  terminalCount: number;
  copiesCreated: number;
  copiesUpdated: number;
  linksCreated: number;
  linksUpdated: number;
  offlinestate: number;
  offlineStateText: string;
}

export interface OfflineTaskResult {
  taskCount: number;
  terminalCount: number;
  taskCopies: number;
  terminalLinks: number;
  mediaLinks: number;
  offlinestate: number;
  offlineStateText: string;
  /** 没有媒体清单的任务，下发过去也放不出声 */
  skippedNoMedia: number[];
}

export interface OfflinePurgeResult {
  offlineMedia: number;
  offlineMediaOfTerminal: number;
  offlineTask: number;
  offlineTaskOfTerminal: number;
  tasksReset: number;
}

export const getOfflineStatesApi = () =>
  http.get<OfflineStateDef[]>(PORT1 + `/api/offline/states`, {}, { loading: false });

export const getOfflineSummaryApi = () =>
  http.get<OfflineSummary>(PORT1 + `/api/offline/summary`, {}, { loading: false });

export const getOfflineMediaStatusApi = (params: any) =>
  http.get<{ list: OfflineMediaStatus[]; total: number; pageNum: number; pageSize: number }>(
    PORT1 + `/api/offline/media`,
    params,
    { loading: false }
  );

export const getOfflineTaskStatusApi = (params: any) =>
  http.get<{ list: OfflineTaskStatus[]; total: number; pageNum: number; pageSize: number }>(
    PORT1 + `/api/offline/tasks`,
    params,
    { loading: false }
  );

export const dispatchOfflineMediaApi = (mediaIds: number[], terminalIds: number[], mode: OfflineMode) =>
  http.post<OfflineMediaResult>(PORT1 + `/api/offline/media`, { mediaIds, terminalIds, mode });

export const dispatchOfflineTaskApi = (taskIds: number[], terminalIds: number[], mode: OfflineMode) =>
  http.post<OfflineTaskResult>(PORT1 + `/api/offline/tasks`, { taskIds, terminalIds, mode });

export const stopOfflineApi = (mediaIds: number[], terminalIds: number[]) =>
  http.put<{ updated: number }>(PORT1 + `/api/offline/stop`, { mediaIds, terminalIds });

export const purgeOfflineApi = (confirmText: string) =>
  http.post<OfflinePurgeResult>(PORT1 + `/api/offline/purge-all`, { confirmText });
