import http from "@/api";
import { PORT1 } from "@/api/config/servicePort";

/** 分区树的两个虚拟节点 */
export const GROUP_ALL = 0;
export const GROUP_UNASSIGNED = -1;

export interface TerminalRow {
  id: number;
  terminalname: string;
  typeid: number;
  typeName: string;
  groupId: number;
  groupName: string;
  netstate: number;
  devicestate: number;
  taskstate: number;
  ip: string;
  volume: number;
  isspeech: number;
  instancy: number;
  isrecord: number;
  issponsor: number;
  isselectcall: number;
  lopencircuit: number;
  ropencircuit: number;
  temperature: number;
  humidity: number;
  /** 列名是旧库的拼写。现网这一列实际存的是后台写入的固件版本串 */
  postion: string;
  hasShortcutKey: boolean;
  online: boolean;
  webUrl: string;
  canDecode: boolean;
  canEncode: boolean;
  /**
   * 这台终端支持哪些批量操作。
   *
   * ⚠ 规则在**服务端**（terminal/caps.go），逐条抄自 ok112 的 get_terminal_type()。
   *   前端只拿结果用来置灰菜单项，**不要在前端重写一份规则** ——
   *   两处各写一份，早晚会不一致。真正的拦截也在服务端，
   *   接口被直接调用时按同一套规则跳过并在 skipped 里说明原因。
   */
  caps: TerminalCaps;
}

/**
 * 判据全部在服务端 terminal/caps.go，这里只是把结果的字段名对上。
 * ⚠ 别在前端重写规则。
 */
export interface TerminalCaps {
  /** 启用/停用**对讲**：isdecode=1 **且** isencode=1（两个都要） */
  speech: boolean;
  /** 启用/停用**发言**：isdecode 与 isencode 全为 0 才不支持 */
  sponsor: boolean;
  /** 快捷键（查看 / 删除共用一套判据） */
  shortcut: boolean;
  /** 快捷任务 */
  quickTask: boolean;
  /** 设置/取消急救：isspeech = 1 */
  instancy: boolean;
  /** 授权寻呼 / 授权终端（两者判据相同：isencode=1 且不在排除表里） */
  authPaging: boolean;
  /** 自动寻检 */
  autoCheck: boolean;
  /** 线路检测 */
  circuit: boolean;
  /** 设置终端密码：isLCD >= 1（密码要在终端自己的屏上输） */
  password: boolean;
  /** 电源开关：switchcount > 1。目前界面上没用到 */
  switch: boolean;
  /** 应急播放终端：typeid = 41 */
  emergencyPlay: boolean;
  /** 急救终端本身：typeid = 33 / 34。是「急救指向哪台」的候选集，不是「哪些能被设急救」 */
  emergencyHost: boolean;
}

export interface TerminalGroupNode {
  id: number;
  name: string;
  info: string;
  count: number;
  virtual: boolean;
}

export interface TerminalType {
  id: number;
  name: string;
  isDecode: boolean;
  isEncode: boolean;
}

export interface TerminalDetail {
  id: number;
  terminalname: string;
  typeid: number;
  groupId: number;
  ip: string;
  postion: string;
  volume: number;
  mac: string;
  netstate: number;
}

export interface TerminalForm {
  terminalname: string;
  typeid: number;
  groupId: number;
  ip: string;
  postion: string;
  volume: number;
}

/** 某台终端为什么没被执行 */
export interface Skipped {
  id: number;
  name: string;
  /** OFFLINE / NOT_BOUND / NOT_FOUND / UNSUPPORTED */
  reason: string;
  detail: string;
}

/**
 * 批量操作结果，语义是「部分成功」。
 * 旧版只要有一台终端离线就整批中止，这里改成能做的做掉、做不了的列出来。
 */
export interface OpResult {
  succeeded: number[];
  skipped: Skipped[];
  notified: boolean;
}

export interface TerminalImpact {
  tasks: number;
  taskNames: string[];
  shortcutKeys: number;
  alarmAreas: number;
  boundUsers: number;
  callGroups: number;
  offlineTasks: number;
  groupWillBeDeleted: boolean;
  groupName: string;
}

export interface DeletePreviewItem {
  id: number;
  terminalname: string;
  impact: TerminalImpact;
}

export interface DeletePreview {
  deletable: DeletePreviewItem[];
  skipped: Skipped[];
}

export interface DeleteResult {
  deleted: number[];
  deletedGroups: number[];
  deletedCallGroups: number[];
  affectedTasks: number;
  skipped: Skipped[];
  notified: boolean;
}

/** 可开关的终端属性 */
export type ToggleKey = "speech" | "sponsor" | "record" | "backcall" | "instancy";

export const getTerminalListApi = (params: any) => {
  return http.get<{ list: TerminalRow[]; total: number; pageNum: number; pageSize: number; scopeNote: string }>(
    PORT1 + `/api/terminals`,
    params,
    { loading: false }
  );
};

export const getTerminalGroupTreeApi = () => {
  return http.get<TerminalGroupNode[]>(PORT1 + `/api/terminal-groups/tree`, {}, { loading: false });
};

export const getTerminalTypesApi = () => {
  return http.get<TerminalType[]>(PORT1 + `/api/terminal-types`, {}, { loading: false });
};

export const getTerminalApi = (id: number) => http.get<TerminalDetail>(PORT1 + `/api/terminals/${id}`);

export const updateTerminalApi = (id: number, data: TerminalForm) => {
  return http.put<{ notified: boolean }>(PORT1 + `/api/terminals/${id}`, data);
};

export const setTerminalRunningApi = (ids: number[], start: boolean) => {
  return http.put<OpResult>(PORT1 + `/api/terminals/${start ? "start" : "stop"}`, { ids });
};

export const setTerminalToggleApi = (ids: number[], toggle: ToggleKey, on: boolean) => {
  return http.put<OpResult>(PORT1 + `/api/terminals/toggle/${toggle}`, { ids, on });
};

export const setTerminalVolumeApi = (ids: number[], volume: number) => {
  return http.put<OpResult>(PORT1 + `/api/terminals/volume`, { ids, volume });
};

export const setTerminalPasswordApi = (ids: number[], password: string) => {
  return http.put<OpResult>(PORT1 + `/api/terminals/password`, { ids, password });
};

export const checkTerminalCircuitApi = (ids: number[]) => {
  return http.put<OpResult>(PORT1 + `/api/terminals/circuit-check`, { ids });
};

export const syncTerminalTimeApi = (ids: number[]) => {
  return http.put<OpResult>(PORT1 + `/api/terminals/sync-time`, { ids });
};

export const previewDeleteTerminalsApi = (ids: number[]) => {
  return http.get<DeletePreview>(PORT1 + `/api/terminals/delete-preview`, { ids: ids.join(",") });
};

export const deleteTerminalsApi = (ids: number[]) => {
  return http.delete<DeleteResult>(PORT1 + `/api/terminals`, {}, { data: { ids, confirmed: true } });
};
