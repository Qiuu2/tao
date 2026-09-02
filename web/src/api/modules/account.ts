import http from "@/api";
import { PORT1 } from "@/api/config/servicePort";

/** 13 项功能权限位，字段名与 usergroup 表列名一一对应 */
export interface Rights {
  taskpriv: number;
  terminalpriv: number;
  mediapriv: number;
  userpriv: number;
  serverpriv: number;
  folderpriv: number;
  terminalgrouppriv: number;
  alarmgrouppriv: number;
  bellpriv: number;
  admpriv: number;
  telephonepriv: number;
  powerplay: number;
  ttspriv: number;
}

/** 权限项的中文名与说明，供权限勾选面板使用 */
export const RIGHT_ITEMS: { key: keyof Rights; label: string; tip: string }[] = [
  { key: "taskpriv", label: "任务管理", tip: "新建 / 修改 / 删除广播任务" },
  { key: "terminalpriv", label: "终端管理", tip: "终端参数、音量、重启等" },
  { key: "mediapriv", label: "媒体管理", tip: "上传 / 删除媒体文件" },
  { key: "folderpriv", label: "文件夹管理", tip: "新建 / 重命名 / 删除媒体目录" },
  { key: "terminalgrouppriv", label: "终端分区", tip: "维护终端分区及其成员" },
  { key: "alarmgrouppriv", label: "报警分区", tip: "维护报警分区与联动映射" },
  { key: "bellpriv", label: "作息方案", tip: "打铃方案的编排与启停" },
  { key: "userpriv", label: "用户管理", tip: "管理用户与用户组" },
  { key: "serverpriv", label: "服务器参数", tip: "修改服务器运行参数" },
  { key: "admpriv", label: "系统维护", tip: "日志、备份恢复等" },
  { key: "telephonepriv", label: "电话广播", tip: "电话接入与寻呼" },
  { key: "powerplay", label: "强插播放", tip: "抢占正在播出的低优先级任务" },
  { key: "ttspriv", label: "语音合成", tip: "TTS 文本转语音" }
];

export const emptyRights = (v = 0): Rights =>
  RIGHT_ITEMS.reduce((acc, i) => ({ ...acc, [i.key]: v }), {} as Rights);

/* ---------------- 用户组 ---------------- */

export interface UserGroup {
  id: number;
  name: string;
  info: string;
  /**
   * level 是 10~109 的两位复合值：十位 = 组级别，个位 = 任务优先级基数。
   * 后台 C 服务按此解析任务优先级，存储格式不可更改。
   * 界面上拆成 groupLevel / priorityBase 两个控件，提交时由后端合成。
   */
  level: number;
  groupLevel: number;
  priorityBase: number;
  system: boolean;
  userCount: number;
  rights: Rights;
  canModify: boolean;
  canDelete: boolean;
}

export interface GroupForm {
  name: string;
  info: string;
  groupLevel: number;
  priorityBase: number;
  rights: Rights;
}

/** 删除影响面。删组 = 删掉组内全部用户及其名下所有数据 */
export interface CascadeImpact {
  users: number;
  userNames: string[];
  folders: number;
  media: number;
  tasks: number;
  terminalGroups: number;
  alarmAreas: number;
  taskFolders: number;
  terminalBinds: number;
}

export interface PriorityRecalc {
  affectedUsers: number;
  affectedTasks: number;
}

export const getGroupListApi = (params: any) => {
  return http.get<{ list: UserGroup[]; total: number; pageNum: number; pageSize: number }>(
    PORT1 + `/api/usergroups`,
    params,
    { loading: false }
  );
};

/** 下拉用的精简用户组，只含选择所需字段 */
export interface GroupOption {
  id: number;
  name: string;
  level: number;
  groupLevel: number;
  system: boolean;
}

export const getGroupOptionsApi = () => {
  return http.get<GroupOption[]>(PORT1 + `/api/usergroups/options`, {}, { loading: false });
};

export const createGroupApi = (data: GroupForm) => http.post(PORT1 + `/api/usergroups`, data);

export const updateGroupApi = (id: number, data: GroupForm) => {
  return http.put<{ priorityRecalc: PriorityRecalc }>(PORT1 + `/api/usergroups/${id}`, data);
};

export const previewDeleteGroupApi = (id: number) => {
  return http.get<CascadeImpact>(PORT1 + `/api/usergroups/${id}/delete-preview`);
};

/** 删除用户组需要输入与组名完全一致的确认文本 */
export const deleteGroupApi = (id: number, confirmName: string) => {
  return http.delete<CascadeImpact>(PORT1 + `/api/usergroups/${id}`, {}, { data: { confirmName } });
};

/* ---------------- 用户 ---------------- */

export interface UserRow {
  id: number;
  username: string;
  enable: number;
  enableText: string;
  usergroupId: number;
  usergroupName: string;
  info: string;
  fullname: string;
  ctrlwind: number;
  subwind: number;
  camerawind: number;
  terminalCount: number;
  canModify: boolean;
  canDelete: boolean;
}

export interface TerminalBind {
  terminalId: number;
  groupId: number;
}

export interface UserDetail {
  id: number;
  username: string;
  usergroupId: number;
  info: string;
  fullname: string;
  enable: number;
  ctrlwind: number;
  subwind: number;
  camerawind: number;
  enableCtrlwind: boolean;
  enableSubwind: boolean;
  enableCamerawind: boolean;
  serials: string[];
  terminals: TerminalBind[];
  /** admin 的用户名与所属组不可修改 */
  usernameLocked: boolean;
  groupLocked: boolean;
}

export interface UserForm {
  username: string;
  /** 空串 = 不修改密码。新建时必填 */
  password: string;
  confirmPassword: string;
  usergroupId: number;
  info: string;
  enableCtrlwind: boolean;
  enableSubwind: boolean;
  enableCamerawind: boolean;
  serials: string[];
  terminals: TerminalBind[];
}

export interface TerminalOption {
  id: number;
  name: string;
  groupId: number;
  groupName: string;
  ownerId: number;
  ownerName: string;
  /** 已被其他用户绑定 */
  occupied: boolean;
}

export interface WindCapacity {
  capacity: number;
  ctrlUsed: number;
  subUsed: number;
  cameraUsed: number;
  registerFlag: number;
  /** 服务器未注册时禁止新建 / 删除用户 */
  canCreateUser: boolean;
}

export const getUserListApi = (params: any) => {
  return http.get<{ list: UserRow[]; total: number; pageNum: number; pageSize: number; scopeNote: string }>(
    PORT1 + `/api/users`,
    params,
    { loading: false }
  );
};

export const getUserApi = (id: number) => http.get<UserDetail>(PORT1 + `/api/users/${id}`);

export const createUserApi = (data: UserForm) => {
  return http.post<{ id: number; ctrlwind: number; subwind: number; camerawind: number }>(PORT1 + `/api/users`, data);
};

export const updateUserApi = (id: number, data: UserForm) => {
  return http.put<{ priorityRecalc: PriorityRecalc; passwordChanged: boolean }>(PORT1 + `/api/users/${id}`, data);
};

/** 启用 / 停用。会联动把该用户名下所有任务一起启停 */
export const setUserEnableApi = (id: number, enable: boolean) => {
  return http.post<{ enable: boolean; affectedTasks: number }>(PORT1 + `/api/users/${id}/enable`, { enable });
};

export const previewDeleteUserApi = (ids: number[]) => {
  return http.get<CascadeImpact>(PORT1 + `/api/users/delete-preview`, { ids: ids.join(",") });
};

export const deleteUsersApi = (ids: number[]) => {
  return http.delete<CascadeImpact>(PORT1 + `/api/users`, {}, { data: { ids, confirmed: true } });
};

export const getTerminalOptionsApi = (userId: number) => {
  return http.get<TerminalOption[]>(PORT1 + `/api/users/terminal-options`, { userId }, { loading: false });
};

export const getWindCapacityApi = () => {
  return http.get<WindCapacity>(PORT1 + `/api/users/wind-capacity`, {}, { loading: false });
};
