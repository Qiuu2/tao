import { ElMessage } from "element-plus";

import i18n from "@/languages";

/**
 * @description: 校验网络请求状态码
 * @param {Number} status
 * @return void
 */
export const checkStatus = (status: number) => {
  // 这里在组件外面，取不到 setup 里的 t()，所以直接用 i18n 实例上的全局 t。
  const t = i18n.global.t;
  const known = [400, 401, 403, 404, 405, 408, 500, 502, 503, 504];
  ElMessage.error(known.includes(status) ? t(`http.e${status}`) : t("http.eDefault"));
};
