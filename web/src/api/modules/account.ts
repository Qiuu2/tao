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
  /**
   * ⚠ 列名叫 telephonepriv，装的是 **led播放** 的权限位。
   * 旧版这一列管「电话广播」，新版没有这一页；列不能删（表结构不动），
   * 正好拿来放 LED 那一项 —— led播放 原先跟文件广播共用 taskpriv，
   * 现在单独一把钥匙。与 serverpriv 装「遥控管理」是同一类历史包袱。
   */
  telephonepriv: number;
  powerplay: number;
  ttspriv: number;
}

/*
  权限项。**每一项都对应新 web 里一块具体功能**，勾上就能用、不勾就进不去 ——
  菜单按它显示，接口按它放行（后端 main.go 的 routes / handleMenu / handleButtons）。

  ⚠ 列名与含义的对应关系照旧版 usergroup 表单原样搬过来，别按字面猜：
  旧版 language/chinese.php 的 $user_group_add 里，
  serverpriv 叫「遥控管理」、admpriv 叫「采播管理」、powerplay 叫「终端功放」、
  ttspriv 叫「文字语音」—— 跟列名字面意思都对不上，但库里已有的用户组就是按这个配的，
  改字面意思等于把现网所有用户组的权限悄悄挪了位。

  group 用来在界面上按新 web 的菜单分组排列。

  ⚠ group / label / tip 存的是 **i18n 键**，不是字面文字 —— 这张表是模块级常量，
  取不到 setup 里的 t()，所以把翻译推到用的地方（user/group 那一页）去做。

  13 项对满 usergroup 的 13 列。最后补上的是 led播放：它借用旧版空出来的
  telephonepriv 那一列（新版没有电话广播这一页），列不动、语义换。
*/
export const RIGHT_ITEMS: { key: keyof Rights; group: string; label: string; tip: string }[] = [
  // —— 资源管理 ——
  { key: "terminalpriv", group: "rights.groupResource", label: "rights.terminal", tip: "rights.terminalTip" },
  { key: "terminalgrouppriv", group: "rights.groupResource", label: "rights.zone", tip: "rights.zoneTip" },
  { key: "alarmgrouppriv", group: "rights.groupResource", label: "rights.alarm", tip: "rights.alarmTip" },
  { key: "mediapriv", group: "rights.groupResource", label: "rights.media", tip: "rights.mediaTip" },
  { key: "folderpriv", group: "rights.groupResource", label: "rights.folder", tip: "rights.folderTip" },

  // —— 任务管理 ——
  { key: "taskpriv", group: "rights.groupTask", label: "rights.task", tip: "rights.taskTip" },
  { key: "bellpriv", group: "rights.groupTask", label: "rights.bell", tip: "rights.bellTip" },
  { key: "powerplay", group: "rights.groupTask", label: "rights.amplifier", tip: "rights.amplifierTip" },
  { key: "admpriv", group: "rights.groupTask", label: "rights.collect", tip: "rights.collectTip" },
  { key: "ttspriv", group: "rights.groupTask", label: "rights.tts", tip: "rights.ttsTip" },
  { key: "telephonepriv", group: "rights.groupTask", label: "rights.led", tip: "rights.ledTip" },

  // —— 系统 ——
  { key: "serverpriv", group: "rights.groupSystem", label: "rights.remote", tip: "rights.remoteTip" },
  { key: "userpriv", group: "rights.groupSystem", label: "rights.user", tip: "rights.userTip" }
];

/** 界面上按这个顺序分组排列，与新 web 的菜单同序 */
export const RIGHT_GROUPS = ["rights.groupResource", "rights.groupTask", "rights.groupSystem"] as const;

export const emptyRights = (v = 0): Rights => RIGHT_ITEMS.reduce((acc, i) => ({ ...acc, [i.key]: v }), {} as Rights);

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
  return http.get<{ list: UserGroup[]; total: number; pageNum: number; pageSize: number }>(PORT1 + `/api/usergroups`, params, {
    loading: false
  });
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
