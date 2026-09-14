import http from "@/api";
import { PORT1 } from "@/api/config/servicePort";

export interface TypeCount {
  type: string;
  total: number;
  online: number;
  offline: number;
}

export interface Overview {
  total: number;
  online: number;
  offline: number;
  byType: TypeCount[];
}

export interface Perf {
  os: string;
  host: string;
  cpuPercent: number;
  memPercent: number;
  memTotal: number;
  memUsed: number;
  diskPercent: number;
  diskTotal: number;
  diskUsed: number;
  iface: string;
  /** 字节/秒 */
  rxRate: number;
  txRate: number;
  /** 首次采样还没有基准，速率不是真的 0 */
  warmingUp: boolean;
}

export interface Shortcut {
  label: string;
  path: string;
  icon: string;
}

export interface BoundTask {
  taskId: number;
  taskName: string;
  playtime: string;
  state: number;
  stateText: string;
  /** 绑定还在，任务已经被删了 */
  missing: boolean;
}

/** 紧急广播的一路。四路固定，**不绑任务** —— 按下去直接发 SDK 命令。 */
export interface EmergencySlot {
  key: string;
  name: string;
}

export interface DashConfig {
  shortcuts: Shortcut[];
  quickTasks: BoundTask[];
  emergency: EmergencySlot[];
}

export interface BrowseItem {
  index: number;
  taskId: number;
  taskName: string;
  /** 分组/目录名，没有就是空串（作息方案不按目录归组，见 category） */
  folderName: string;
  /** 属于「任务管理」下的哪个模块：作息方案 / 文件广播 / 终端功放 / … */
  module: string;
  /** 给人看的那一格：模块名（归属名），如「作息方案（春季作息）」 */
  category: string;
  weekdays: number[];
  cycleText: string;
  playtime: string;
  state: number;
  stateText: string;
  startdate: string;
  enddate: string;
  terminals: number;
  /** 所看那一天这条任务会不会响（字段名沿用旧的，语义见后端 Browse 的注释） */
  enabledToday: boolean;
  projectstate: number;
  /**
   * 这条任务被单独停掉的那一天（task.disableday），没停过是空串。
   *
   * 与 projectstate 是两回事：projectstate 管整条任务的长期启停，
   * disableday 只挖掉某一天 —— 旧版看板上那两个「当天启用 / 当天停用」
   * 按钮写的就是这一列。
   */
  disableday: string;
  /**
   * 状态那一列：已执行 / 执行中 / 准备执行。
   *
   * 后端按**服务器时钟**与 `task.state` 一起算（见 runStatusOf）：
   * state 非 0（1 执行 / 2 暂停 / 3 立即执行）= running，
   * state 为 0 时才拿执行时间和此刻比 —— 过了点 done，没到点 ready。
   * 只有看今天时 state 才算数，切到别的星期一律按日期判。
   */
  runStatus: "done" | "running" | "ready";
}

export const getDashOverviewApi = () => http.get<Overview>(PORT1 + `/api/dashboard/overview`, {}, { loading: false });

export const getDashPerfApi = () => http.get<Perf>(PORT1 + `/api/dashboard/perf`, {}, { loading: false });

export const getDashConfigApi = () => http.get<DashConfig>(PORT1 + `/api/dashboard/config`, {}, { loading: false });

export const getDashTasksApi = (params: any) =>
  http.get<{ list: BrowseItem[]; total: number; pageNum: number; pageSize: number; viewDate: string }>(
    PORT1 + `/api/dashboard/tasks`,
    params,
    { loading: false }
  );

export const saveShortcutsApi = (shortcuts: Shortcut[]) =>
  http.put<{ count: number }>(PORT1 + `/api/dashboard/shortcuts`, { shortcuts });

export const saveQuickTasksApi = (taskIds: number[]) =>
  http.put<{ count: number }>(PORT1 + `/api/dashboard/quick-tasks`, { taskIds });

/**
 * 下发一路紧急广播。stop 为 true 表示停止。
 *
 * ⚠ 后端走的是 UDP，没有回执：这个接口成功只代表命令发出去了，
 * 终端响没响这一侧看不见。所以提示语写「已下发」而不是「已播放」。
 */
export const playEmergencyApi = (key: string, stop: boolean) =>
  http.post<{ key: string; stop: boolean }>(PORT1 + `/api/dashboard/emergency`, { key, stop });
