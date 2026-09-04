import http from "@/api";
import { PORT1 } from "@/api/config/servicePort";
import type { ResPage } from "@/api/interface";

/* ==================================================================
   终端功放 / 采播管理 / 文字语音 / LED 播放（共用 typed-tasks）
   ================================================================== */

export type TypedKind = "amplifier" | "collect" | "tts" | "led";

export const KIND_TITLE: Record<TypedKind, string> = {
  amplifier: "终端功放",
  collect: "采播管理",
  tts: "文字语音",
  led: "LED 播放"
};

export interface TypedTask {
  taskId: number;
  taskName: string;
  tasktype: number;
  state: number;
  stateText: string;
  /** ⚠ 0 = 启用、1 = 停用（与列注释相反） */
  projectstate: number;
  projectText: string;
  startdate: string;
  enddate: string;
  playtime: string;
  endtime: string;
  exemodel: string;
  cycleText: string;
  defaultvolume: number;
  timelength: number;
  timelengthtype: number;
  lengthText: string;
  cmd: number;
  cmdargs: string;
  parentid: number;
  userId: number;
  userName: string;
  terminalCount: number;
  canModify: boolean;
  /**
   * 以下四列是为了和 :80 逐列对齐才带出来的。对这四类任务基本是常量：
   * priority 恒 3、bandrate/samplerate 恒 0、playfileid 由后台回写。
   */
  priority: number;
  bandrate: number;
  samplerate: number;
  playfileid: number;
  /** 预开电源（秒）。⚠ 终端功放恒 0，那一列在功放这边是列表筛选的判据 */
  prepower: number;
  /** 发送模式：0 = 单播、1 = 组播 */
  datasendmodel: number;
  /** 功放：打开 / 关闭 */
  switchText?: string;
  /** 采播：采播源终端名 */
  sourceName?: string;
  /** 文字语音 / LED：要播的文字 */
  text?: string;
  /** 间隔播放（文字语音 / led播放 的「播放模式」） */
  interval_s: number;
  intplaylength: number;
  intplaylengthtype: number;
  /** intplaylengthtype 的中文说法：普通模式 / 间隔时间 */
  playModeText?: string;
  /** tasktype 的中文说法，文字语音列表里的「任务类型」一列 */
  typeText?: string;
}

export interface TypedTerminal {
  terminalId: number;
  terminalname: string;
  typeName: string;
  area: string;
  groupId: number;
  netstate: number;
  deleted: boolean;
}

export interface TtsSentence {
  id: number;
  name: string;
  content: string;
  mediaseq: number;
  speed: number;
  volume: number;
  male: number;
  pitch: number;
  type: number;
  typeText: string;
}

export interface LedBind {
  terminalId: number;
  terminalname: string;
  deviceId: number;
  deviceName: string;
  deleted: boolean;
}

export interface LedDetail {
  sentenceId: number;
  text: string;
  speed: number;
  ledmode: number;
  modeText: string;
  devices: LedBind[];
}

export interface TypedDetail extends TypedTask {
  terminals: TypedTerminal[];
  /** 库里那几条 ttssentence 的原样 */
  sentences?: TtsSentence[];
  /** 还原成旧版表单上的那一段正文（多条 ttssentence 按 mediaseq 拼回来） */
  ttsText?: string;
  musicmode: number;
  ttsSpeed: number;
  promptId: number;
  led?: LedDetail;
  /** 当前用户被允许的任务等级区间 */
  priorityMin: number;
  priorityMax: number;
}

export interface TypedTerminalOption {
  id: number;
  terminalname: string;
  typeid: number;
  typeName: string;
  ip: string;
  netstate: number;
  groupId: number;
}

export interface TypedControlResult {
  succeeded: number[];
  blocked: { id: number; name: string; reason: string; detail: string }[];
  notified: boolean;
}

export interface TypedSaveBody {
  taskName: string;
  startdate: string;
  enddate: string;
  playtime: string;
  durationSec: number;
  timelengthtype: number;
  timelength: number;
  exemodel: string;
  defaultvolume: number;
  projectstate: number;
  folderId: number;
  switch: number;
  channel: number;
  sourceTerminalId: number;
  /** 采播的「音频设置」 */
  samplerate: number;
  bandrate: number;
  /** 间隔播放 */
  interval_s: number;
  intplaylength: number;
  intplaylengthtype: number;
  /** 文字语音：一个 textarea + 一组全局参数 */
  text: string;
  musicmode: number;
  ttsSpeed: number;
  promptId: number;
  prepower: number;
  priority: number;
  datasendmodel: number;
  terminals: { terminalId: number; area: string; groupId: number }[];
  led: { text: string; speed: number; ledmode: number; devices: { terminalId: number; deviceId: number }[] } | null;
}

export const getTypedListApi = (kind: TypedKind, params: any) =>
  http.get<ResPage<TypedTask> & { scopeNote: string }>(PORT1 + `/api/typed-tasks/${kind}`, params);
export const getTypedApi = (kind: TypedKind, id: number) =>
  http.get<TypedDetail>(PORT1 + `/api/typed-tasks/${kind}/${id}`, {}, { loading: false });
export const getTypedTerminalsApi = (keyword = "") =>
  http.get<TypedTerminalOption[]>(PORT1 + `/api/typed-tasks/terminals`, { keyword }, { loading: false });
export const createTypedApi = (kind: TypedKind, data: TypedSaveBody) =>
  http.post<{ taskId: number }>(PORT1 + `/api/typed-tasks/${kind}`, data);
export const updateTypedApi = (kind: TypedKind, id: number, data: TypedSaveBody) =>
  http.put<{ updated: boolean }>(PORT1 + `/api/typed-tasks/${kind}/${id}`, data);
export interface PromptMedia {
  id: number;
  name: string;
  timelength: number;
}

/** 「采播终端 / tts终端」下拉的候选：旧版按 terminal.typeid 筛出来的那一批 */
export const getTypedSourcesApi = (kind: TypedKind) =>
  http.get<TypedTerminalOption[]>(PORT1 + `/api/typed-tasks/${kind}/sources`, {}, { loading: false });
/** 「任务级别」下拉的可选区间（由用户组级别决定） */
export const getTypedPriorityRangeApi = () =>
  http.get<{ priorityMin: number; priorityMax: number }>(PORT1 + `/api/typed-tasks/priority-range`, {}, { loading: false });
/** 文字语音的「提示音」候选（旧版写死的 9 号媒体目录） */
export const getPromptMediaApi = () => http.get<PromptMedia[]>(PORT1 + `/api/typed-tasks/prompts`, {}, { loading: false });

export type TypedAction = "start" | "stop" | "pause" | "resume";

export const controlTypedApi = (kind: TypedKind, action: TypedAction, ids: number[]) =>
  http.put<TypedControlResult>(PORT1 + `/api/typed-tasks/${kind}/control/${action}`, { ids });
export const setTypedStateApi = (kind: TypedKind, ids: number[], enable: boolean) =>
  http.put<TypedControlResult>(PORT1 + `/api/typed-tasks/${kind}/project-state`, { ids, enable });
export const deleteTypedApi = (kind: TypedKind, ids: number[]) =>
  http.delete<{ deleted: number[]; blocked: any[] }>(PORT1 + `/api/typed-tasks/${kind}`, { ids });

/* ---------------- LED 分组与设备 ---------------- */

export interface LedFolder {
  id: number;
  name: string;
  parentid: number;
  userId: number;
  taskCount: number;
}

export interface LedDevice {
  id: number;
  name: string;
  terminalId: number;
  terminalname: string;
  devid: number;
  ip: string;
  width: number;
  height: number;
  sendport: number;
  mac: string;
  defaulttext: string;
}

export const getLedFoldersApi = () => http.get<LedFolder[]>(PORT1 + `/api/led/folders`, {}, { loading: false });
export const createLedFolderApi = (name: string, parentid = 0) =>
  http.post<{ id: number }>(PORT1 + `/api/led/folders`, { name, parentid });
export const renameLedFolderApi = (id: number, name: string) =>
  http.put<{ id: number }>(PORT1 + `/api/led/folders/${id}`, { name });
export const deleteLedFolderApi = (id: number) =>
  http.delete<{ deleted: number[]; blocked: any[] }>(PORT1 + `/api/led/folders/${id}`, {});
export const copyLedFolderApi = (fromId: number, toId: number) =>
  http.post<{ copied: number }>(PORT1 + `/api/led/folders:copy`, { fromId, toId });
export const getLedDevicesApi = (keyword = "") =>
  http.get<LedDevice[]>(PORT1 + `/api/led/devices`, { keyword }, { loading: false });
export const createLedDeviceApi = (data: Partial<LedDevice>) => http.post<{ id: number }>(PORT1 + `/api/led/devices`, data);
export const updateLedDeviceApi = (id: number, data: Partial<LedDevice>) =>
  http.put<{ updated: boolean }>(PORT1 + `/api/led/devices/${id}`, data);
export const deleteLedDevicesApi = (ids: number[]) => http.delete<{ deleted: number }>(PORT1 + `/api/led/devices`, { ids });

/* ==================================================================
   启用管理
   ================================================================== */

/**
 * ⚠ enabletask.enstate 与 taskid 是**两串并列的逗号分隔值**，逐项对应，
 *   取值 0 = 启用、1 = 停用（与 task.projectstate 一致，
 *   与 holidaytime.projectstate（1=启用）相反）。
 */
export const ENABLE_ACTION_ENABLE = 0;
export const ENABLE_ACTION_DISABLE = 1;

export interface EnableTaskRef {
  taskId: number;
  taskName: string;
  tasktype: number;
  typeText: string;
  info: string;
  /** 0 = 启用、1 = 停用 */
  action: number;
  actionText: string;
  missing: boolean;
}

export interface EnablePlan {
  id: number;
  startdate: string;
  starttime: string;
  tasks: EnableTaskRef[];
  enableCount: number;
  disableCount: number;
  /** 计划时间已过。后台执行后不会自动清理，历史记录会一直堆着。 */
  expired: boolean;
}

export interface EnablePickTask {
  taskId: number;
  taskName: string;
  tasktype: number;
  typeText: string;
  info: string;
  /** ⚠ task.projectstate：0 = 启用、1 = 停用。弹窗用它给单选按钮设初值 */
  projectstate: number;
  stateText: string;
}

export interface EnableSaveForm {
  startdate: string;
  starttime: string;
  tasks: { taskId: number; action: number }[];
}

export const getEnableListApi = (params: any) => http.get<ResPage<EnablePlan>>(PORT1 + `/api/enable-plans`, params);
export const getEnableApi = (id: number) => http.get<EnablePlan>(PORT1 + `/api/enable-plans/${id}`, {}, { loading: false });
export const getEnableTasksApi = (keyword = "") =>
  http.get<EnablePickTask[]>(PORT1 + `/api/enable-plans/tasks`, { keyword }, { loading: false });
export const createEnableApi = (data: EnableSaveForm) => http.post<{ id: number }>(PORT1 + `/api/enable-plans`, data);
export const updateEnableApi = (id: number, data: EnableSaveForm) =>
  http.put<{ id: number }>(PORT1 + `/api/enable-plans/${id}`, data);
export const deleteEnableApi = (ids: number[]) => http.delete<{ deleted: number }>(PORT1 + `/api/enable-plans`, { ids });

/* ==================================================================
   噪声设备 / 声场分区
   ================================================================== */

export interface SoundDevice {
  id: number;
  name: string;
  ip: string;
  devaddr: number;
  /** 探头采回来的噪声值，只读 —— Web 不写这一列 */
  dbvalue: number;
  sendport: number;
  groupId: number;
  groupName: string;
}

export interface SoundGroup {
  id: number;
  name: string;
  userId: number;
  userName: string;
  terminalCount: number;
  deviceCount: number;
  canModify: boolean;
}

export interface SoundGroupTerminal {
  terminalId: number;
  terminalname: string;
  typeName: string;
  ip: string;
  netstate: number;
  deleted: boolean;
}

export interface SoundGroupDetail {
  id: number;
  name: string;
  userId: number;
  terminals: SoundGroupTerminal[];
  devices: SoundDevice[];
}

export const getSoundDevicesApi = (params: any) => http.get<ResPage<SoundDevice>>(PORT1 + `/api/sound/devices`, params);
export const getSoundDeviceApi = (id: number) =>
  http.get<SoundDevice>(PORT1 + `/api/sound/devices/${id}`, {}, { loading: false });
export const getSoundDeviceOptionsApi = (keyword = "") =>
  http.get<SoundDevice[]>(PORT1 + `/api/sound/devices/options`, { keyword }, { loading: false });
export const createSoundDeviceApi = (data: { name: string; ip: string; devaddr: number; sendport: number }) =>
  http.post<{ id: number }>(PORT1 + `/api/sound/devices`, data);
export const updateSoundDeviceApi = (id: number, data: { name: string; ip: string; devaddr: number; sendport: number }) =>
  http.put<{ updated: boolean }>(PORT1 + `/api/sound/devices/${id}`, data);
export const deleteSoundDevicesApi = (ids: number[]) => http.delete<{ deleted: number }>(PORT1 + `/api/sound/devices`, { ids });

export const getSoundGroupsApi = (params: any) =>
  http.get<ResPage<SoundGroup> & { scopeNote: string }>(PORT1 + `/api/sound/groups`, params);
export const getSoundGroupApi = (id: number) =>
  http.get<SoundGroupDetail>(PORT1 + `/api/sound/groups/${id}`, {}, { loading: false });
export const getSoundGroupTerminalsApi = (keyword = "") =>
  http.get<SoundGroupTerminal[]>(PORT1 + `/api/sound/groups/terminals`, { keyword }, { loading: false });
export const createSoundGroupApi = (data: { name: string; terminalIds: number[]; deviceIds: number[] }) =>
  http.post<{ id: number }>(PORT1 + `/api/sound/groups`, data);
export const updateSoundGroupApi = (id: number, data: { name: string; terminalIds: number[]; deviceIds: number[] }) =>
  http.put<{ updated: boolean }>(PORT1 + `/api/sound/groups/${id}`, data);
export const deleteSoundGroupsApi = (ids: number[]) =>
  http.delete<{ deleted: number[]; resetTerminals: number; resetDevices: number; blocked: any[] }>(PORT1 + `/api/sound/groups`, {
    ids
  });

/* ==================================================================
   云广播终端 / 任务传送
   ================================================================== */

export interface CloudTerminal {
  id: number;
  terminalname: string;
  typeName: string;
  ip: string;
  netstate: number;
  /** :80 云广播终端列表里的「任务状态 / 设备状态」两列 */
  taskstate: number;
  devicestate: number;
  groupId: number;
  groupName: string;
  /** 原样来自 terminal 表，不做减法 —— resetcapacity 的含义未经证实 */
  totalcapacity: number;
  resetcapacity: number;
  mediaCount: number;
  taskCount: number;
  transferring: number;
}

export interface CloudItem {
  kind: "media" | "task";
  id: number;
  name: string;
  size: number;
  typeid: string;
  taskId: number;
  offlinestate: number;
  stateText: string;
  missing: boolean;
}

export interface TransferTask {
  taskId: number;
  taskName: string;
  tasktype: number;
  typeText: string;
  info: string;
  startdate: string;
  enddate: string;
  playtime: string;
  exemodel: string;
  /** exemodel 的中文说法（每天 / 周一、三 / 手动），后端算好再下发 */
  cycleText: string;
  timelength: number;
  timelengthtype: number;
  /** timelength + timelengthtype 的中文说法 */
  lengthText: string;
  /** ⚠ 与 task 同源：0 = 启用、1 = 停用 */
  projectstate: number;
  projectText: string;
  defaultvolume: number;
  offlinestate: number;
  stateText: string;
  terminalCount: number;
  doneCount: number;
  /** 原任务已被删除，只剩离线副本 */
  sourceMissing: boolean;
}

export interface TransferTerminal {
  terminalId: number;
  terminalname: string;
  typeName: string;
  ip: string;
  netstate: number;
  offlinestate: number;
  stateText: string;
  area: string;
  deleted: boolean;
}

export const getCloudTerminalsApi = (params: any) =>
  http.get<ResPage<CloudTerminal> & { scopeNote: string }>(PORT1 + `/api/cloud/terminals`, params);
export const getCloudInventoryApi = (id: number) =>
  http.get<CloudItem[]>(PORT1 + `/api/cloud/terminals/${id}/inventory`, {}, { loading: false });

/** 云广播终端页那排按钮的整批动作，逐项回报改了多少行 */
export interface CloudBulkResult {
  action: string;
  actionText: string;
  terminalCount: number;
  mediaRows: number;
  taskRows: number;
  stateText: string;
}

/** action: idle | immediate | stop | clearAll | clearIdleMedia */
export const cloudBulkApi = (ids: number[], action: string) =>
  http.post<CloudBulkResult>(PORT1 + `/api/cloud/bulk`, { ids, action });
export const getTransferListApi = (params: any) => http.get<ResPage<TransferTask>>(PORT1 + `/api/transfer/tasks`, params);
export const getTransferDetailApi = (id: number) =>
  http.get<TransferTerminal[]>(PORT1 + `/api/transfer/tasks/${id}`, {}, { loading: false });

/** 一条离线任务副本里带的媒体（行内「媒体」链接） */
export interface TransferMediaItem {
  mediaId: number;
  name: string;
  size: number;
  typeid: string;
  sort: number;
  terminals: number;
  done: number;
  missing: boolean;
}

export const getTransferMediaApi = (id: number) =>
  http.get<TransferMediaItem[]>(PORT1 + `/api/transfer/tasks/${id}/media`, {}, { loading: false });

/** action: idle | immediate | stop */
export const transferBulkApi = (ids: number[], action: string) =>
  http.post<CloudBulkResult>(PORT1 + `/api/transfer/bulk`, { ids, action });
