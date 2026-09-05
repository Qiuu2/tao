import http from "@/api";
import { PORT1 } from "@/api/config/servicePort";
import type { ResPage } from "@/api/interface";

/* ==================== 终端分区 ==================== */

export interface Zone {
  id: number;
  name: string;
  info: string;
  createTime: string;
  userId: number;
  userName: string;
  terminalCount: number;
  /** 有多少条任务按这个分区号下发。删除前要提示。 */
  taskCount: number;
  canModify: boolean;
}

export interface ZoneMember {
  terminalId: number;
  terminalname: string;
  typeName: string;
  ip: string;
  netstate: number;
  /** 终端已被删除，但关联行还在 */
  deleted: boolean;
}

export interface ZoneDetail {
  id: number;
  name: string;
  info: string;
  userId: number;
  members: ZoneMember[];
}

export interface ZonePreview {
  deletable: { id: number; name: string; impact: { terminals: number; tasks: number } }[];
  blocked: { id: number; name: string; reason: string; detail: string }[];
}

export interface ZoneDeleteResult {
  deleted: number[];
  resetTerminals: number;
  resetTasks: number;
  blocked: { id: number; name: string; detail: string }[];
}

export interface ZoneOption {
  id: number;
  name: string;
}

export interface ZoneTerminalOption {
  id: number;
  terminalname: string;
  ip: string;
  typeName: string;
  netstate: number;
  /** 这台终端当前属于哪个分区，0 = 未分区 */
  currentZoneId: number;
  currentZoneName: string;
}

export const getZoneTerminalsApi = (keyword = "") =>
  http.get<ZoneTerminalOption[]>(PORT1 + `/api/zones/terminals`, { keyword }, { loading: false });

export const getZoneListApi = (params: any) => http.get<ResPage<Zone>>(PORT1 + `/api/zones`, params);
export const getZoneOptionsApi = () => http.get<ZoneOption[]>(PORT1 + `/api/zones/options`, {}, { loading: false });
export const getZoneApi = (id: number) => http.get<ZoneDetail>(PORT1 + `/api/zones/${id}`, {}, { loading: false });
export const createZoneApi = (data: { name: string; info: string; terminalIds: number[] }) =>
  http.post<{ id: number }>(PORT1 + `/api/zones`, data);
export const updateZoneApi = (id: number, data: { name: string; info: string; terminalIds: number[] }) =>
  http.put<{ updated: boolean }>(PORT1 + `/api/zones/${id}`, data);
export const previewDeleteZonesApi = (ids: number[]) =>
  http.get<ZonePreview>(PORT1 + `/api/zones/delete-preview`, { ids: ids.join(",") }, { loading: false });
// ⚠ DELETE 带参数必须走 `http.delete(url, {}, { data: ... })`：
// 第二个参数是 **query string**，后端这些接口读的是 **JSON body**，
// 写成 http.delete(url, { ids }) 会发出一个没有 body 的请求，
// 后端解析 body 直接 EOF，报「请求参数格式错误」—— 界面上表现为删除按钮点了没反应。
export const deleteZonesApi = (ids: number[]) => http.delete<ZoneDeleteResult>(PORT1 + `/api/zones`, {}, { data: { ids } });

/* ==================== 节假日 ==================== */

/**
 * ⚠ projectstate 的取值和 task 表**正好相反**：
 *   holidaytime.projectstate  1 = 启用, 0 = 停用
 *   task.projectstate         0 = 启用, 1 = 停用
 * 依据是旧版 enableholiday() 写 1、disableholiday() 写 0。别凭直觉改。
 */
export const HOLIDAY_ENABLED = 1;
export const HOLIDAY_DISABLED = 0;

export interface Holiday {
  id: number;
  name: string;
  startdate: string;
  enddate: string;
  projectstate: number;
  stateText: string;
  /** 区间天数（含首尾） */
  days: number;
  /** 今天正落在这个区间里且是启用状态 —— 也就是「今天不打铃」 */
  active: boolean;
}

export const getHolidayListApi = (params: any) => http.get<ResPage<Holiday>>(PORT1 + `/api/holidays`, params);
export const getHolidayApi = (id: number) => http.get<Holiday>(PORT1 + `/api/holidays/${id}`, {}, { loading: false });
export const getHolidayOverlapsApi = (startdate: string, enddate: string, excludeId = 0) =>
  http.get<Holiday[]>(PORT1 + `/api/holidays/overlaps`, { startdate, enddate, excludeId }, { loading: false });
/** projectstate 用 holidaytime 自己的口径：1 = 启用、0 = 停用 */
export interface HolidayForm {
  name: string;
  startdate: string;
  enddate: string;
  projectstate: number;
}

export const createHolidayApi = (data: HolidayForm) => http.post<{ id: number }>(PORT1 + `/api/holidays`, data);
export const updateHolidayApi = (id: number, data: HolidayForm) =>
  http.put<{ updated: boolean }>(PORT1 + `/api/holidays/${id}`, data);
export const setHolidayStateApi = (ids: number[], enable: boolean) =>
  http.put<{ affected: number }>(PORT1 + `/api/holidays/state`, { ids, enable });
export const deleteHolidaysApi = (ids: number[]) =>
  http.delete<{ deleted: number }>(PORT1 + `/api/holidays`, {}, { data: { ids } });

/* ==================== 遥控任务 ==================== */

export interface RemoteTask {
  taskId: number;
  taskName: string;
  kind: string;
  kindText: string;
  /** 绑定指向的任务已经不存在了 */
  missing: boolean;
}

export interface RemoteKey {
  keyId: number;
  keyName: string;
  tasks: RemoteTask[];
}

export interface RemotePickTask {
  taskId: number;
  taskName: string;
  kind: string;
  kindText: string;
  /** 已经把这条任务绑走的遥控键号，0 表示没被绑 */
  usedBy: number;
}

export const getRemoteListApi = (params: any) => http.get<ResPage<RemoteKey>>(PORT1 + `/api/remote-keys`, params);
export const getRemoteApi = (keyId: number) => http.get<RemoteKey>(PORT1 + `/api/remote-keys/${keyId}`, {}, { loading: false });
export const getRemoteTasksApi = (kind = "", keyword = "") =>
  http.get<RemotePickTask[]>(PORT1 + `/api/remote-keys/tasks`, { kind, keyword }, { loading: false });
export const createRemoteApi = (data: { keyId: number; keyName: string; taskIds: number[] }) =>
  http.post<{ keyId: number }>(PORT1 + `/api/remote-keys`, data);
export const updateRemoteApi = (keyId: number, data: { keyId: number; keyName: string; taskIds: number[] }) =>
  http.put<{ updated: boolean }>(PORT1 + `/api/remote-keys/${keyId}`, data);
export const deleteRemotesApi = (ids: number[]) =>
  http.delete<{ deleted: number }>(PORT1 + `/api/remote-keys`, {}, { data: { ids } });

/* ==================== 时间设置 ==================== */

export interface TimeState {
  serverTime: string;
  timezone: string;
  timezoneOffset: number;
  uptime: number;
  dbTime: string;
  dbTimeDiff: number;
  ntpserver: string;
  /**
   * ⚠ serverbaseparam.adjusttime 存的是**终端 id**，不是 0/1 开关。
   * 0 表示未启用 GPS 校时。
   */
  gpsTerminalId: number;
  gpsTerminalName: string;
  gpsTerminalMissing: boolean;
  readOnly: boolean;
  /**
   * 能不能通过 Web 改服务器系统时钟。
   * ⚠ false 时 clockBlockReason 会说明缺什么，界面直接展示。
   *   唯一的阻断条件是「服务账号没有免密执行 timedatectl 的权限」——
   *   在服务器上跑一次 deploy/install-sudoers.sh 即可开通。
   */
  canSetClock: boolean;
  clockBlockReason: string;
  /** 自动校时守护在不在跑 */
  ntpActive: boolean;
  /**
   * 守护在跑**不等于**真同步上了。现网 192.168.2.159 就是
   * ntpActive=true 而 ntpSynced=false（内网够不着上游），
   * 这种机器手工拨的时间是留得住的。所以 ntpActive 不再作为阻断条件。
   */
  ntpSynced: boolean;
  /** ntpActive 时的一句提醒，显示在按钮旁边；不是阻断原因 */
  ntpWarning: string;
}

export interface TimeTerminal {
  id: number;
  terminalname: string;
  ip: string;
  typeName: string;
  netstate: number;
}

export const getTimeStateApi = () => http.get<TimeState>(PORT1 + `/api/time`, {}, { loading: false });
export const getTimeTerminalsApi = (keyword = "") =>
  http.get<TimeTerminal[]>(PORT1 + `/api/time/terminals`, { keyword }, { loading: false });
export const setNtpApi = (ntpserver: string) =>
  http.put<{ updated: boolean; note: string }>(PORT1 + `/api/time/ntp`, { ntpserver });
export const setGpsTerminalApi = (terminalId: number) => http.put<{ updated: boolean }>(PORT1 + `/api/time/gps`, { terminalId });
/**
 * 设置服务器系统时间。time 是 "YYYY-MM-DD HH:MM:SS"。
 *
 * stopNtp 对应界面上的「同时关闭自动校时」勾选框：勾了才会执行
 * `timedatectl set-ntp false`。关系统服务是操作员的决定，程序不代劳。
 */
export const setServerClockApi = (time: string, stopNtp = false) =>
  http.put<{ updated: boolean }>(PORT1 + `/api/time/clock`, { time, stopNtp });
export const syncTerminalTimeApi = (ids: number[]) =>
  http.post<{ sent: number; note: string }>(PORT1 + `/api/time/sync`, { ids });
