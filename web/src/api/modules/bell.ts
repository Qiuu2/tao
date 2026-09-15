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
  /*
   * 下面这几项名义上是「方案级」，实际每一行 task 各存一份，可以不一致。
   * 旧版 modifybell.html 的课时表 radio 一选中，就把**这一课时自己的**这几项
   * 灌回上面那排控件（getonetaskterminal.js）—— 所以逐条目都得给一份。
   */
  startdate: string;
  enddate: string;
  exemodel: string;
  prepower: number;
  defaultvolume: number;
  priority: number;
  datasendmodel: number;
  israndomplay: number;
  /** 这一条目自己挂的字幕，没挂就是 null —— 字幕也是每个条目各挂一条子任务 */
  led: BellLED | null;
  timelengthtype: number;
  timelength: number;
  projectstate: number;
  projectStateText: string;
  media: BellMedia[];
  powerTaskId: number;
  powerPlayTime: string;
  duplicateTime: boolean;
  /** 这一条目自己挂了几台终端（各条目可以不一样：行内「修改」是按条目写的） */
  terminalCount: number;
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
  /** ⚠ 1 = 随机、0 = 顺序（列注释写反了） */
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
  /** 这一条目自己的终端清单。只有 applyTerminals 为 true 时才生效 */
  terminals?: { terminalId: number; groupId: number; area: string }[];
  applyTerminals?: boolean;
  /**
   * 这一条目自己的「方案级」属性。不传表示这次不动它们。
   *
   * 旧版行内「修改」（modifyonebellplan.php）的 URL 里就带着这一整组 ——
   * 上面那排控件当时的值，只写给这一条目。
   */
  attrs?: BellItemAttrs;
}

export interface BellItemAttrs {
  startdate: string;
  enddate: string;
  exemodel: string;
  prepower: number;
  defaultvolume: number;
  priority: number;
  datasendmodel: number;
  israndomplay: number;
  /** 字幕。null 或正文为空 = 这一条目不要字幕，保存时会把它已有的删掉 */
  led: BellLED | null;
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

/** 读某条打铃自己的终端清单（对应旧版 getonetaskterminal.php） */
export const getBellItemTerminalsApi = (planName: string, taskId: number) =>
  http.get<{ taskid: number; terminals: BellTerminal[] }>(
    PORT1 + `/api/bell-plans/items/${taskId}/terminals`,
    { plan: planName },
    { loading: false }
  );

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
