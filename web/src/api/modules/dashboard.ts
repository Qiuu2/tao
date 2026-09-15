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
   * 状态那一列。后端按**服务器时钟**与 `task.state` 一起算（见 runStatusOf）：
   *
   *   done     已执行     state = 0 且执行时间已经过了
   *   ready    准备执行   state = 0 且还没到点
   *   running  正在执行   state = 1
   *   paused   暂停       state = 2
   *   playnow  立即执行   state = 3
   *   fault    播放故障   state = 5 —— 发下去了没播成，多半是终端或媒体不对
   *
   * 只有 state = 0 才去比时间；也只有看今天时 state 才算数，
   * 切到别的星期一律按日期判（拿实时 state 说「上周二那条正在执行」是假的）。
   */
  runStatus: "done" | "ready" | "running" | "paused" | "playnow" | "fault";
  /** 这条任务归谁（task.task_user_id）。0 表示库里就没写 */
  ownerUserId: number;
  /** 归属账号名。账号被删掉时回空串 —— 界面画「—」，不让整行消失 */
  ownerName: string;
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

/**
 * 看板上的「当天启用 / 当天停用」。
 *
 * ⚠ 这两个不是筛选是**操作**：勾几行点下去，改的是 task.disableday
 * —— 停用写入所看那一天的日期，启用写回 0000-00-00。
 *
 * 日期不由前端传：服务端按 weekday 当场算，与列表用的是同一份算法，
 * 免得界面写着「看的是 9-16」、点下去停的却是别的日子。
 */
export const setDisableDayApi = (ids: number[], weekday: number, disable: boolean) =>
  http.put<{ date: string; viewDate: string; tasks: number; subs: number; skipped: number[] }>(
    PORT1 + `/api/dashboard/tasks/disable-day`,
    { ids, weekday, disable }
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
