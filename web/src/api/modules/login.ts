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
