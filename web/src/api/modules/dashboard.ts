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

export interface EmergencySlot {
  key: string;
  name: string;
  task: BoundTask | null;
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
  folderName: string;
  weekdays: number[];
  cycleText: string;
  playtime: string;
  state: number;
  stateText: string;
  startdate: string;
  enddate: string;
  terminals: number;
  enabledToday: boolean;
  projectstate: number;
}

export const getDashOverviewApi = () => http.get<Overview>(PORT1 + `/api/dashboard/overview`, {}, { loading: false });

export const getDashPerfApi = () => http.get<Perf>(PORT1 + `/api/dashboard/perf`, {}, { loading: false });

export const getDashConfigApi = () => http.get<DashConfig>(PORT1 + `/api/dashboard/config`, {}, { loading: false });

export const getDashTasksApi = (params: any) =>
  http.get<{ list: BrowseItem[]; total: number; pageNum: number; pageSize: number }>(
    PORT1 + `/api/dashboard/tasks`,
    params,
    { loading: false }
  );

export const saveShortcutsApi = (shortcuts: Shortcut[]) =>
  http.put<{ count: number }>(PORT1 + `/api/dashboard/shortcuts`, { shortcuts });

export const saveQuickTasksApi = (taskIds: number[]) =>
  http.put<{ count: number }>(PORT1 + `/api/dashboard/quick-tasks`, { taskIds });

export const saveEmergencyApi = (slots: Record<string, number>) =>
  http.put<{ slots: number }>(PORT1 + `/api/dashboard/emergency`, { slots });
