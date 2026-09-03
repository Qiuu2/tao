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

/* ───────────── 快捷键（查看 / 删除）─────────────
 *
 * 「在这台终端上按这个键，去寻呼那些终端」。数据在 terminalkey + terminalkeymap。
 * 与下面的快捷任务不是一套结构，别混。
 */

export interface ShortcutKeyTarget {
  terminalId: number;
  terminalname: string;
  /** 目标终端已被删除，映射行还留着 */
  deleted: boolean;
  groupId: number;
  area: string;
}

export interface ShortcutKey {
  id: number;
  name: string;
  /** 这个快捷键属于哪台终端 */
  ownerId: number;
  key: number;
  keyLabel: string;
  sendmodul: number;
  emergency: boolean;
  targets: ShortcutKeyTarget[];
}

export const getShortcutKeysApi = (terminalId: number) => {
  return http.get<ShortcutKey[]>(PORT1 + `/api/terminals/${terminalId}/shortcut-keys`);
};

export interface ShortcutKeyOption {
  /** 键值本身。快捷任务的 terminalkeymaptask.keyid 存的就是它 */
  value: number;
  label: string;
}

/**
 * 某台终端可选的键值。可选集按终端型号算（服务端 terminal/keyspec.go）。
 * emergency 决定用哪一套 —— 急救与普通快捷键的可选集不同，快捷任务用非急救那套。
 */
export const getShortcutKeyOptionsApi = (terminalId: number, emergency = false) => {
  return http.get<ShortcutKeyOption[]>(PORT1 + `/api/terminals/${terminalId}/shortcut-keys/options`, { emergency });
};

/** 新建/修改快捷键的入参。字段与 ok112 的 setterminalkeyoption 表单一一对应 */
export interface ShortcutKeyForm {
  name: string;
  key: number;
  emergency: boolean;
  targetIds: number[];
}

/** 设置快捷键（ok112 的 setterminalkeyoption.php） */
export const createShortcutKeyApi = (terminalId: number, data: ShortcutKeyForm) => {
  return http.post<{ id: number }>(PORT1 + `/api/terminals/${terminalId}/shortcut-keys`, data);
};

/** 修改快捷键（ok112 的 modifyterminalkeyoption.php） */
export const updateShortcutKeyApi = (keyId: number, data: ShortcutKeyForm) => {
  return http.put<{ updated: boolean }>(PORT1 + `/api/shortcut-keys/${keyId}`, data);
};

export const deleteShortcutKeysApi = (ids: number[]) => {
  return http.delete<{ deleted: number }>(PORT1 + `/api/shortcut-keys`, {}, { data: { ids } });
};

/* ───────────── 快捷任务 ─────────────
 *
 * ⚠ 它是「为这台终端**新建**一条专属任务并绑到键上」，不是「把已有任务绑到键上」。
 *   ok112 的列表只认 tasktype IN (20,21,29) 且 task.cmdargs 指向本终端的任务。
 *
 *   20 媒体播放      按键播放选定的音频文件
 *   21 文字播报      勾了 TTS，音源是 TTS 主机
 *   29 文字播报      同上，但音源选的是「服务器」；旧版此时把语速 ×10，
 *                    这个换算在服务端做，前端填的、读到的都是原始语速
 *
 *   LED 字幕不改类型，它是另挂一条 LED 子任务。
 */

export interface QuickTask {
  taskId: number;
  taskName: string;
  key: number;
  keyLabel: string;
  /** 配合 timeLengthType：类型 1 是秒数，类型 2 是循环次数 */
  timeLength: number;
  timeLengthType: number;
  priority: number;
  volume: number;
  isRandomPlay: number;
  dataSendMode: number;
  taskType: number;
  typeText: string;
  /** 宿主终端名（ok112 列表里也有这一列） */
  terminalName: string;
}

export interface QuickTaskTTS {
  text: string;
  speed: number;
  /** 对应 ttssentence.male，0 女声 1 男声 */
  musicMode: number;
  /** 音源终端 ID，0 表示服务器 */
  audioSource: number;
}

export interface QuickTaskLED {
  text: string;
  speed: number;
  ledmode: number;
  terminalIds: number[];
}

export interface QuickTaskDetail extends QuickTask {
  mediaIds: number[];
  media: { mediaId: number; name: string; sort: number }[];
  terminalIds: number[];
  tts?: QuickTaskTTS;
  led?: QuickTaskLED;
}

/** 新建 / 修改快捷任务的入参，字段与 ok112 的 set_task_quickplay 表单一一对应 */
export interface QuickTaskForm {
  taskName: string;
  key: number;
  isRandomPlay: number;
  volume: number;
  priority: number;
  timeLengthType: number;
  timeLength: number;
  dataSendMode: number;
  mediaIds: number[];
  terminalIds: number[];
  tts?: QuickTaskTTS | null;
  led?: QuickTaskLED | null;
}

export interface QuickAudioSource {
  id: number;
  name: string;
  typeId: number;
  /** 选它时任务类型走 29，语速由服务端按倍数放大 */
  isServer: boolean;
}

export const getQuickTasksApi = (terminalId: number) => {
  return http.get<QuickTask[]>(PORT1 + `/api/terminals/${terminalId}/quick-tasks`);
};

/** 修改表单的回填数据（ok112 的 modifyquickplay.php） */
export const getQuickTaskDetailApi = (terminalId: number, taskId: number) => {
  return http.get<QuickTaskDetail>(PORT1 + `/api/terminals/${terminalId}/quick-tasks/detail`, { taskId });
};

/** 音源候选：两种 TTS 主机加「服务器」，与 ok112 的 typeid IN (22,32,0) 一致 */
export const getQuickAudioSourcesApi = () => {
  return http.get<QuickAudioSource[]>(PORT1 + `/api/quick-task-audio-sources`);
};

/** 新建快捷任务（ok112 的 set_task_quick_play） */
export const createQuickTaskApi = (terminalId: number, data: QuickTaskForm) => {
  return http.post<{ taskId: number }>(PORT1 + `/api/terminals/${terminalId}/quick-tasks`, data);
};

/** 修改快捷任务（ok112 的 modify_task_quick_play） */
export const updateQuickTaskApi = (terminalId: number, taskId: number, data: QuickTaskForm) => {
  // 用 POST 而不是 PUT：PUT /api/terminals/{id}/... 会和 PUT /api/terminals/toggle/{toggle} 撞路由
  return http.post<{ updated: boolean }>(PORT1 + `/api/terminals/${terminalId}/quick-tasks/update?taskId=${taskId}`, data);
};

/** 删除快捷任务（ok112 的 del_quick_task，列表是复选框多选后一起删） */
export const deleteQuickTasksApi = (terminalId: number, taskIds: number[]) => {
  return http.delete<{ deleted: number }>(PORT1 + `/api/terminals/${terminalId}/quick-tasks`, {}, { data: { taskIds } });
};

/* ───────────── 寻呼授权（授权寻呼 / 授权终端）─────────────
 *
 * 白名单，且**不配置即全放开**：没建过名单的终端可以寻呼所有在线终端。
 * 所以清空名单是「放开到默认」，不是「全部禁止」——这一点很容易搞反。
 *
 * 「授权寻呼」和「授权终端」是同一份名单的两个入口，区别只在挑终端的界面。
 */

export interface CallGroupMember {
  id: number;
  name: string;
  typeId: number;
  ip: string;
  online: boolean;
  groupId: number;
  /** 名单里还留着，但终端已经被删了 */
  missing: boolean;
}

export interface CallGroupInfo {
  terminalId: number;
  terminalName: string;
  /** false = 没建过名单，按默认可寻呼所有在线终端 */
  configured: boolean;
  name: string;
  members: CallGroupMember[];
}

export const getCallGroupApi = (terminalId: number) => {
  return http.get<CallGroupInfo>(PORT1 + `/api/terminals/${terminalId}/call-group`);
};

export const setCallGroupApi = (terminalId: number, name: string, terminalIds: number[]) => {
  return http.post(PORT1 + `/api/terminals/${terminalId}/call-group`, { name, terminalIds });
};

/* ───────────── 终端替换 ───────────── */

export interface ReplaceResult {
  sourceId: number;
  targetId: number;
  /** renumber = 目标 ID 空闲，只是改号；takeover = 顶替掉已有记录 */
  mode: "renumber" | "takeover";
  /** 改号时迁移的关联行数；接管时是清掉的源终端关联行数 */
  affected: number;
  /** 被顶替掉的那条记录的名字，takeover 时才有 */
  targetName?: string;
}

export const replaceTerminalApi = (sourceId: number, targetId: number) => {
  // 路径用字面量：/api/terminals/{id}/replace 会和 toggle/{toggle} 撞路由
  return http.put<ReplaceResult>(PORT1 + `/api/terminals/replace`, { sourceId, targetId });
};
