import http from "@/api";
import { PORT1 } from "@/api/config/servicePort";
import { ResPage, User } from "@/api/interface/index";

/**
 * Geeker-Admin 模板自带的示例接口，配套 views/proTable 与 views/assembly 下的演示页。
 *
 * 这些页面不属于广播系统业务，走的是模板作者的 Mock 服务，本项目并未接入。
 * 保留在此仅为让模板演示页继续通过类型检查；真实的用户 / 用户组接口在 account.ts。
 */

/** @description 获取用户列表 */
export const getUserList = (params: User.ReqUserParams) => {
  return http.post<ResPage<User.ResUserList>>(PORT1 + `/user/list`, params);
};

/** @description 获取树形用户列表 */
export const getUserTreeList = (params: User.ReqUserParams) => {
  return http.post<ResPage<User.ResUserList>>(PORT1 + `/user/tree/list`, params);
};

/** @description 新增用户 */
export const addUser = (params: { id: string }) => {
  return http.post(PORT1 + `/user/add`, params);
};

/** @description 编辑用户 */
export const editUser = (params: { id: string }) => {
  return http.post(PORT1 + `/user/edit`, params);
};

/** @description 删除用户 */
export const deleteUser = (params: { id: string[] }) => {
  return http.post(PORT1 + `/user/delete`, params);
};

/** @description 切换用户状态 */
export const changeUserStatus = (params: { id: string; status: number }) => {
  return http.post(PORT1 + `/user/change`, params);
};

/** @description 重置用户密码 */
export const resetUserPassWord = (params: { id: string }) => {
  return http.post(PORT1 + `/user/rest_password`, params);
};

/** @description 批量添加用户 */
export const batchAddUser = (params: FormData) => {
  return http.post(PORT1 + `/user/import`, params);
};

/** @description 导出用户数据 */
export const exportUserInfo = (params: User.ReqUserParams) => {
  return http.download(PORT1 + `/user/export`, params);
};

/** @description 获取用户状态字典 */
export const getUserStatus = () => {
  return http.get<User.ResStatus[]>(PORT1 + `/user/status`);
};

/** @description 获取用户性别字典 */
export const getUserGender = () => {
  return http.get<User.ResGender[]>(PORT1 + `/user/gender`);
};

/** @description 获取用户部门列表 */
export const getUserDepartment = () => {
  return http.get<User.ResDepartment[]>(PORT1 + `/user/department`, {}, { cancel: false });
};

/** @description 获取用户角色字典 */
export const getUserRole = () => {
  return http.get<User.ResRole[]>(PORT1 + `/user/role`);
};
