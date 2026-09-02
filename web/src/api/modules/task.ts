import http from "@/api";
import { PORT1 } from "@/api/config/servicePort";

/** 任务媒体清单的一项 */
export interface TaskMedia {
  mediaId: number;
  name: string;
  size: number;
  sort: number;
  /** 媒体已被删除。旧版用内连接会让这一项直接消失，看不出任务少了东西 */
  deleted: boolean;
}

/** 任务终端清单的一项 */
export interface TaskTerminal {
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

export interface TaskRow {
  taskid: number;
  taskname: string;
  tasktype: number;
  /** 取值反直觉：0 = 随机，1 = 顺序。服务端已给出 playModeText，界面用它 */
  israndomplay: number;
  playModeText: string;
  projectstate: number;
  state: number;
  stateText: string;
  startdate: string;
  enddate: string;
  playtime: string;
  endtime: string;
  timelengthtype: number;
  timelength: number;
  timelengthText: string;
  exemodel: string;
  weekdays: number[];
  priority: number;
  defaultvolume: number;
  prepower: number;
  folderId: number;
  ownerUserId: number;
  ownerUserName: string;
  powerTaskId: number;
  media: TaskMedia[];
  terminals: TaskTerminal[];
  /** 服务端算好的启动前置条件，界面不必自己复刻一遍 */
  startable: boolean;
  blockReason: string;
}

export interface TaskFolderNode {
  id: number;
  name: string;
  parentId: number;
  userId: number;
  userName: string;
  taskCount: number;
  canDelete: boolean;
  children: TaskFolderNode[];
}

export interface TaskDetail {
  taskid: number;
  taskname: string;
  tasktype: number;
  folderId: number;
  projectstate: number;
  israndomplay: number;
  startdate: string;
  enddate: string;
  playtime: string;
  endtime: string;
  exemodel: string;
  disableday: string;
  timelengthtype: number;
  timelength: number;
  interval_s: number;
  intplaylength: number;
  intplaylengthtype: number;
  defaultvolume: number;
  priority: number;
  localplay: number;
  prepower: number;
  datasendmodel: number;
  ownerUserId: number;
  media: TaskMedia[];
  terminals: TaskTerminal[];
  /** 挂在这条任务上的 LED 字幕子任务；没有就是 null */
  led: TaskLEDSub | null;
  /** 该任务归属用户被允许的优先级区间，由其用户组 level 决定 */
  priorityMin: number;
  priorityMax: number;
}

/**
 * LED 字幕子任务（:80 表单里的「led播放 / Led字幕 / Led速度」）。
 *
 * ⚠ 它不是一个开关列，而是另建一条 tasktype = 30 的任务，
 *   sec_task_id 指回主任务，除任务名外整行照抄主任务。
 */
export interface TaskLEDSub {
  /** 留空则与主任务同名 */
  name: string;
  text: string;
  speed: number;
  ledmode: number;
}

/** 提交体。媒体与终端是对象数组，不是旧版那两条按下标对齐的逗号串 */
export interface TaskForm {
  taskname: string;
  folderId: number;
  projectstate: number;
  israndomplay: number;
  media: { mediaId: number; sort: number }[];
  terminals: { terminalId: number; groupId: number; area: string }[];
  schedule: {
    startdate: string;
    enddate: string;
    playtime: string;
    endtime: string;
    exemodel: string;
    disableday: string;
  };
  playback: {
    timelengthtype: number;
    timelength: number;
    interval_s: number;
    intplaylength: number;
    intplaylengthtype: number;
    defaultvolume: number;
    priority: number;
    localplay: number;
  };
  power: {
    prepower: number;
    datasendmodel: number;
  };
  /** null = 不上屏；服务端会把已有的 LED 子任务删掉 */
  led: TaskLEDSub | null;
}

/** 某条任务为什么没被执行 */
export interface TaskBlocked {
  id: number;
  name: string;
  /** NOT_FOUND / NOT_OWNER / DISABLED / NO_MEDIA / NO_TERMINAL / BAD_TYPE / HAS_CHANNEL / IS_SCHEME / NOT_DELETABLE_TYPE */
  reason: string;
  detail: string;
}

export interface TaskControlResult {
  succeeded: number[];
  blocked: TaskBlocked[];
  notified: boolean;
}

export interface TaskDeleteImpact {
  media: number;
  terminals: number;
  shortcutKeys: number;
  offlineTasks: number;
  offlineMedia: number;
  powerTaskId: number;
  ledTaskId: number;
  /** sec_task_id 指向本任务、但既不是功放也不是 LED 的任务数：不会被删，但引用会悬空 */
  otherLinked: number;
}

export interface TaskDeletePreviewItem {
  taskid: number;
  taskname: string;
  impact: TaskDeleteImpact;
}

export interface TaskDeletePreview {
  deletable: TaskDeletePreviewItem[];
  blocked: TaskBlocked[];
}

export interface TaskDeleteResult {
  deleted: number[];
  deletedSubTasks: number[];
  blocked: TaskBlocked[];
  notified: boolean;
}

export interface TaskFolderDeleteResult {
  deletedFolders: number[];
  deletedTasks: number[];
  deletedSubTasks: number[];
}

export interface TaskSaveResult {
  taskid: number;
  powerTaskId: number;
  /** LED 字幕子任务 id，0 表示这条任务没开 led播放 */
  ledTaskId: number;
  mediaCount: number;
  terminalCount: number;
}

export interface MediaOption {
  id: number;
  name: string;
  size: number;
  timelength: number;
  folderId: number;
  folderName: string;
}

export interface TaskTerminalOption {
  id: number;
  name: string;
  typeName: string;
  groupId: number;
  groupName: string;
  netstate: number;
}

export type TaskAction = "start" | "stop" | "pause" | "resume";

export const getTaskListApi = (params: any) => {
  return http.get<{ list: TaskRow[]; total: number; pageNum: number; pageSize: number; scopeNote: string }>(
    PORT1 + `/api/tasks`,
    params,
    { loading: false }
  );
};

export const getTaskFolderTreeApi = () => {
  return http.get<TaskFolderNode[]>(PORT1 + `/api/task-folders/tree`, {}, { loading: false });
};

export const createTaskFolderApi = (data: { name: string; parentId: number }) => {
  return http.post<{ id: number }>(PORT1 + `/api/task-folders`, data);
};

export const renameTaskFolderApi = (id: number, name: string) => {
  return http.put<{ id: number }>(PORT1 + `/api/task-folders/${id}`, { name, parentId: 0 });
};

export const deleteTaskFolderApi = (id: number) => {
  return http.delete<TaskFolderDeleteResult>(PORT1 + `/api/task-folders/${id}`);
};

export const getTaskApi = (id: number) => http.get<TaskDetail>(PORT1 + `/api/tasks/${id}`);

export const createTaskApi = (data: TaskForm) => http.post<TaskSaveResult>(PORT1 + `/api/tasks`, data);

export const updateTaskApi = (id: number, data: TaskForm) => http.put<TaskSaveResult>(PORT1 + `/api/tasks/${id}`, data);

export const controlTaskApi = (action: TaskAction, ids: number[]) => {
  return http.put<TaskControlResult>(PORT1 + `/api/tasks/control/${action}`, { ids });
};

export const setTaskProjectStateApi = (ids: number[], enable: boolean) => {
  return http.put<TaskControlResult>(PORT1 + `/api/tasks/project-state`, { ids, enable });
};

/**
 * 紧急任务：设为紧急 = tasktype 2 → 7，取消 = 7 → 2。
 * ⚠ 全系统同时只能有一条，服务端会挡住第二条。
 */
export interface TaskEmergencyInfo {
  taskId: number;
  taskName: string;
  exists: boolean;
}

export const getTaskEmergencyApi = () => http.get<TaskEmergencyInfo>(PORT1 + `/api/tasks/emergency`, {}, { loading: false });
export const setTaskEmergencyApi = (id: number) => http.put<TaskEmergencyInfo>(PORT1 + `/api/tasks/emergency`, { id });
export const cancelTaskEmergencyApi = () => http.delete<TaskEmergencyInfo>(PORT1 + `/api/tasks/emergency`);

export const setTaskVolumeApi = (ids: number[], volume: number) => {
  return http.put<TaskControlResult>(PORT1 + `/api/tasks/volume`, { ids, volume });
};

export const previewDeleteTasksApi = (ids: number[]) => {
  return http.get<TaskDeletePreview>(PORT1 + `/api/tasks/delete-preview`, { ids: ids.join(",") });
};

export const deleteTasksApi = (ids: number[]) => {
  return http.delete<TaskDeleteResult>(PORT1 + `/api/tasks`, {}, { data: { ids, confirmed: true } });
};

export const copyTaskApi = (id: number, targetFolderId: number, newName: string) => {
  return http.post<TaskSaveResult>(PORT1 + `/api/tasks/${id}/copy`, { targetFolderId, newName });
};

export const syncTaskTerminalsApi = (taskIds: number[], terminalIds: number[]) => {
  return http.post<{ added: number; tasks: number[]; blocked: TaskBlocked[] }>(
    PORT1 + `/api/tasks/sync-terminals`,
    { taskIds, terminalIds }
  );
};

/** 按需搜索的媒体下拉。旧版是把整张 media 表读进内存渲染下拉框 */
export const searchTaskMediaApi = (keyword: string, folderId = 0) => {
  return http.get<MediaOption[]>(PORT1 + `/api/task-options/media`, { keyword, folderId }, { loading: false });
};

export const searchTaskTerminalsApi = (keyword: string, groupId = 0) => {
  return http.get<TaskTerminalOption[]>(PORT1 + `/api/task-options/terminals`, { keyword, groupId }, { loading: false });
};
