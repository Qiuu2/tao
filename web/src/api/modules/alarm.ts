import http from "@/api";
import { PORT1 } from "@/api/config/servicePort";

/** 一条报警映射：报警主机的某个通道 → 报警分区 → 播放媒体 */
export interface AlarmMapping {
  id: number;
  info: string;
  alarmTerminalId: number;
  alarmTerminalName: string;
  terminalDeleted: boolean;
  /** 该主机的通道总数（terminal.channel） */
  terminalChannels: number;
  alarmChannel: number;
  alarmAreaId: number;
  alarmAreaName: string;
  /** 该报警分区下挂了多少台终端（:80 的「分区终端」列） */
  areaTerminalCount: number;
  areaDeleted: boolean;
  mediaId: number;
  mediaName: string;
  mediaDeleted: boolean;
  /** 引用了已删除的对象，或通道号超范围。旧版会把这类行整条藏起来，但它仍在库里生效 */
  invalid: boolean;
  invalidReason: string;
  channelOutOfRange: boolean;
}

export interface AlarmMappingForm {
  info: string;
  alarmTerminalId: number;
  alarmChannel: number;
  alarmAreaId: number;
  mediaId: number;
}

export interface AlarmArea {
  id: number;
  name: string;
  info: string;
  createTime: string;
  userId: number;
  userName: string;
  terminalCount: number;
  mappingCount: number;
  canModify: boolean;
}

export interface AlarmAreaTerminal {
  terminalId: number;
  terminalname: string;
  typeName: string;
  groupId: number;
  netstate: number;
  deleted: boolean;
}

export interface AlarmAreaDetail {
  id: number;
  name: string;
  info: string;
  userId: number;
  terminals: AlarmAreaTerminal[];
}

export interface AlarmAreaForm {
  name: string;
  info: string;
  terminals: { terminalId: number; groupId: number }[];
}

export interface AlarmAreaImpact {
  terminals: number;
  alarmMappings: number;
}

export interface AlarmAreaPreviewItem {
  id: number;
  name: string;
  impact: AlarmAreaImpact;
}

export interface AlarmBlocked {
  id: number;
  name: string;
  reason: string;
  detail: string;
}

export interface AlarmAreaPreview {
  deletable: AlarmAreaPreviewItem[];
  blocked: AlarmBlocked[];
}

export interface AlarmAreaDeleteResult {
  deleted: number[];
  deletedMappings: number;
  resetTerminals: number;
  blocked: AlarmBlocked[];
}

export interface AlarmHostOption {
  id: number;
  name: string;
  ip: string;
  /** 通道数，界面据此生成 1..channels 的通道下拉 */
  channels: number;
  /** 终端类型声明的开关路数，与 channels 常常对不上，仅供排查 */
  typeSwitchCount: number;
  netstate: number;
}

export interface AlarmAreaOption {
  id: number;
  name: string;
}

export interface AlarmMediaOption {
  id: number;
  name: string;
  timelength: number;
  folderId: number;
  folderName: string;
}

/** 报警媒体下拉带一句说明，用来解释「为什么是空的」 */
export interface AlarmMediaResult {
  list: AlarmMediaOption[];
  note: string;
}

export interface AlarmTerminalOption {
  id: number;
  name: string;
  typeName: string;
  groupId: number;
  groupName: string;
  netstate: number;
  /** 该终端当前所属的报警分区，-1 表示没有 */
  currentAreaId: number;
  currentAreaName: string;
}

/* ---------------- 报警映射 ---------------- */

export const getAlarmMappingListApi = (params: any) => {
  return http.get<{ list: AlarmMapping[]; total: number; pageNum: number; pageSize: number; scopeNote: string }>(
    PORT1 + `/api/alarm-mappings`,
    params,
    { loading: false }
  );
};

export const getAlarmMappingApi = (id: number) => http.get<AlarmMapping>(PORT1 + `/api/alarm-mappings/${id}`);

export const createAlarmMappingApi = (data: AlarmMappingForm) => {
  return http.post<{ id: number }>(PORT1 + `/api/alarm-mappings`, data);
};

export const updateAlarmMappingApi = (id: number, data: AlarmMappingForm) => {
  return http.put<{ id: number }>(PORT1 + `/api/alarm-mappings/${id}`, data);
};

export const deleteAlarmMappingsApi = (ids: number[]) => {
  return http.delete<{ deleted: number[]; skipped: number[] }>(PORT1 + `/api/alarm-mappings`, {}, { data: { ids } });
};

/* ---------------- 报警分区 ---------------- */

export const getAlarmAreaListApi = (params: any) => {
  return http.get<{ list: AlarmArea[]; total: number; pageNum: number; pageSize: number; scopeNote: string }>(
    PORT1 + `/api/alarm-areas`,
    params,
    { loading: false }
  );
};

export const getAlarmAreaApi = (id: number) => http.get<AlarmAreaDetail>(PORT1 + `/api/alarm-areas/${id}`);

export const createAlarmAreaApi = (data: AlarmAreaForm) => http.post<{ id: number }>(PORT1 + `/api/alarm-areas`, data);

export const updateAlarmAreaApi = (id: number, data: AlarmAreaForm) => {
  return http.put<{ id: number }>(PORT1 + `/api/alarm-areas/${id}`, data);
};

export const previewDeleteAlarmAreasApi = (ids: number[]) => {
  return http.get<AlarmAreaPreview>(PORT1 + `/api/alarm-areas/delete-preview`, { ids: ids.join(",") });
};

export const deleteAlarmAreasApi = (ids: number[]) => {
  return http.delete<AlarmAreaDeleteResult>(PORT1 + `/api/alarm-areas`, {}, { data: { ids, confirmed: true } });
};

/* ---------------- 选择器 ---------------- */

export const getAlarmHostsApi = () => http.get<AlarmHostOption[]>(PORT1 + `/api/alarm-options/hosts`, {}, { loading: false });

export const getAlarmAreaOptionsApi = () =>
  http.get<AlarmAreaOption[]>(PORT1 + `/api/alarm-options/areas`, {}, { loading: false });

export const getAlarmMediaApi = (keyword = "") =>
  http.get<AlarmMediaResult>(PORT1 + `/api/alarm-options/media`, { keyword }, { loading: false });

export const getAlarmTerminalOptionsApi = (keyword = "") =>
  http.get<AlarmTerminalOption[]>(PORT1 + `/api/alarm-options/terminals`, { keyword }, { loading: false });
