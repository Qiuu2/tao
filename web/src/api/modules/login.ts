import http from "@/api";
import { PORT1 } from "@/api/config/servicePort";
import { Login } from "@/api/interface/index";

/**
 * @description 用户登录
 *
 * 注意：密码以明文提交，由服务端计算 MD5 后与 book_admin.userpwd 比对。
 * 这与旧 PHP 系统的行为一致（服务端 md5()），服务端是唯一权威。
 * 生产环境应启用 HTTPS 保护传输。
 */
export const loginApi = (params: Login.ReqLoginForm) => {
  return http.post<Login.ResLogin>(PORT1 + `/api/login`, params, { loading: false });
};

/** @description 获取图形验证码 */
export const getCaptchaApi = () => {
  return http.get<Login.ResCaptcha>(PORT1 + `/api/captcha`, {}, { loading: false });
};

/** @description 获取菜单列表（由后端按用户组的 13 项权限位下发） */
export const getAuthMenuListApi = () => {
  return http.get<Menu.MenuOptions[]>(PORT1 + `/api/menu/list`, {}, { loading: false });
};

/** @description 获取按钮权限 */
export const getAuthButtonListApi = () => {
  return http.get<Login.ResAuthButtons>(PORT1 + `/api/auth/buttons`, {}, { loading: false });
};

/** @description 用户退出登录 */
export const logoutApi = () => {
  return http.post(PORT1 + `/api/logout`);
};

/* ---------------- 自助修改密码 ---------------- */

/** 新密码要满足什么。complex 对应旧库 serverconfig.fuzamima */
export interface PasswordPolicy {
  complex: boolean;
  minLength: number;
  maxLength: number;
}

/** @description 读密码强度要求。只用来在弹窗里把要求写清楚，真正拦人的是提交那一次 */
export const getPasswordPolicyApi = () => {
  return http.get<PasswordPolicy>(PORT1 + `/api/account/password-policy`, {}, { loading: false });
};

/**
 * @description 改**自己**的登录密码。
 *
 * 请求体里没有用户名 —— 改谁的密码由会话决定。旧版是把用户名跟着表单一起提交、
 * 服务端照着改的，知道别人旧密码就能改别人的。
 */
export const changeOwnPasswordApi = (params: { oldPassword: string; newPassword: string; confirmPassword: string }) => {
  return http.put(PORT1 + `/api/account/password`, params);
};
