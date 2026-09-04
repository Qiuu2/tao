import http from "@/api";
import { PORT1 } from "@/api/config/servicePort";

/**
 * 作息方案（打铃）。
 *
 * 一个方案不是一张表的一行，而是 task 表里共享同一个 info 的一组行 ——
 * 所以业务主键是 planName（字符串），不是 id。
 * 方案名可能带 `/`，放进路径段会被路由吃掉，因此读用 ?plan=、写放 body。
 */
export interface BellPlan {
  planName: string;
  /** MIN(taskid)，只用于兼容旧的按 taskid 传参 */
  representativeTaskId: number;
  itemCount: number;
  powerSubTasks: number;
  startdate: string;
  enddate: string;
  /** 0 = 启用、1 = 停用（与数据库列注释相反） */
  projectstate: number;
  projectStateText: string;
  /** 组内条目状态不一致，界面要标注出来 */
  mixedState: boolean;
  /** 组内重复的打铃时间，只提示不拦截 */
  duplicateTimes: string[];
  ownerUserId: number;
  ownerUserName: string;
  ownerDeleted: boolean;
}

export interface BellMedia {
  mediaId: number;
  name: string;
  size: number;
  sort: number;
  deleted: boolean;
}

export interface BellTerminal {
  terminalId: number;
  terminalname: string;
  typeName: string;
  netstate: number;
  taskstate: number;
  ip: string;
  volume: number;
  groupId: number;
  area: string;
  deleted: boolean;
}

export interface BellItem {
  taskid: number;
  taskname: string;
  playtime: string;
  /** 起止日期与星期掩码理论上是方案级的，但「智能排课」可以按条目改，所以逐条目也给一份 */
  startdate: string;
  enddate: string;
  exemodel: string;
  timelengthtype: number;
  timelength: number;
  projectstate: number;
  projectStateText: string;
  media: BellMedia[];
  powerTaskId: number;
  powerPlayTime: string;
  duplicateTime: boolean;
}

export interface BellSchedule {
  startdate: string;
  enddate: string;
  exemodel: string;
}

export interface BellPlayback {
  defaultvolume: number;
  priority: number;
  /** 单位是秒，不是分钟 */
  prepower: number;
  datasendmodel: number;
  /** ⚠ 取值反直觉：0 = 随机、1 = 顺序 */
  israndomplay: number;
}

export interface BellDetail {
  planName: string;
  /** 方案的 LED 字幕设置，没挂字幕时为 null */
  led: BellLED | null;
  schedule: BellSchedule;
  playback: BellPlayback;
  terminals: BellTerminal[];
  items: BellItem[];
  ownerUserId: number;
  /** 组内取值不一致的方案级属性名 */
  mixedAttrs: string[];
  priorityMin: number;
  priorityMax: number;
}

export interface BellItemForm {
  taskid?: number;
  taskname: string;
  playtime: string;
  timelengthtype: number;
  timelength: number;
  media: { mediaId: number; sort: number }[];
}

/** 方案级 LED 字幕：正文 + 速度（0~5 级）。不挂字幕时传 null */
export interface BellLED {
  text: string;
  speed: number;
}

export interface BellPlanForm {
  planName: string;
  newPlanName?: string;
  schedule: BellSchedule;
  playback: BellPlayback;
  terminals: { terminalId: number; groupId: number; area: string }[];
  items?: BellItemForm[];
  led?: BellLED | null;
  applyTerminals?: boolean;
}

export interface BellSaveResult {
  planName: string;
  createdItems: number;
  taskIds: number[];
  powerTaskIds: number[];
  terminalRows: number;
  warnings: string[];
}

export interface BellDeleteImpact {
  planName: string;
  items: number;
  powerSubTasks: number;
  mediaRows: number;
  terminalRows: number;
  keyMapRows: number;
  offlineTaskRows: number;
  offlineMediaRows: number;
  /** 同名但不属于本方案的其它任务：旧版会连它们一起删掉，新版不删 */
  sameNameOtherTasks: number;
}

export interface BellCopyResult {
  newPlanName: string;
  copiedItems: number;
  copiedPowerSubTasks: number;
  copiedMediaRows: number;
  copiedTerminalRows: number;
  idMapping: Record<string, number>;
}

export const getBellPlanListApi = (params: any) => {
  return http.get<{ list: BellPlan[]; total: number; pageNum: number; pageSize: number; scopeNote: string }>(
    PORT1 + `/api/bell-plans`,
    params,
    { loading: false }
  );
};

export const getBellPlanApi = (plan: string) => http.get<BellDetail>(PORT1 + `/api/bell-plans/detail`, { plan });

export const createBellPlanApi = (data: BellPlanForm) => http.post<BellSaveResult>(PORT1 + `/api/bell-plans`, data);

export const updateBellPlanApi = (data: BellPlanForm) => {
  return http.put<{ planName: string; affectedRows: number; terminalRows: number; renamed: boolean }>(
    PORT1 + `/api/bell-plans`,
    data
  );
};

/** 调整音量：整个方案改一次，功放子任务一并同步 */
export const setBellPlanVolumeApi = (planName: string, volume: number) => {
  return http.put<{ planName: string; volume: number; affectedTasks: number }>(PORT1 + `/api/bell-plans/volume`, {
    planName,
    volume
  });
};

export const setBellPlanStateApi = (planName: string, enable: boolean) => {
  return http.put<{ planName: string; affectedTasks: number; offlineStateReset: boolean; notified: boolean }>(
    PORT1 + `/api/bell-plans/state`,
    { planName, enable }
  );
};

export const previewDeleteBellPlanApi = (plan: string) =>
  http.get<BellDeleteImpact>(PORT1 + `/api/bell-plans/delete-preview`, { plan });

export const deleteBellPlanApi = (planName: string) => {
  return http.delete<{ planName: string; deletedTasks: number[]; items: number; powerSubTasks: number }>(
    PORT1 + `/api/bell-plans`,
    {},
    { data: { planName, confirmed: true } }
  );
};

export const copyBellPlanApi = (planName: string, newPlanName: string) =>
  http.post<BellCopyResult>(PORT1 + `/api/bell-plans/copy`, { planName, newPlanName });

export const addBellItemApi = (planName: string, item: BellItemForm) =>
  http.post<BellSaveResult>(PORT1 + `/api/bell-plans/items`, { planName, item });

export const updateBellItemApi = (planName: string, taskId: number, item: BellItemForm) =>
  http.put<{ taskid: number }>(PORT1 + `/api/bell-plans/items/${taskId}`, { planName, item });

/** 智能排课：把勾中的条目挪到新的日期时间段，并改它们的执行星期 */
export const setBellItemScheduleApi = (planName: string, ids: number[], schedule: BellSchedule) => {
  return http.put<{
    planName: string;
    changed: number;
    changedTasks: number[];
    startdate: string;
    enddate: string;
    exemodel: string;
  }>(PORT1 + `/api/bell-plans/items/schedule`, { planName, ids, ...schedule });
};

export const deleteBellItemsApi = (planName: string, ids: number[]) => {
  return http.delete<{ deleted: number; planRemoved: boolean }>(PORT1 + `/api/bell-plans/items`, {}, { data: { planName, ids } });
};
