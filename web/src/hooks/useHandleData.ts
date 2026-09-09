import { ElMessage, ElMessageBox } from "element-plus";

import { HandleData } from "./interface";
import i18n from "@/languages";

// 这里在组件外面，取不到 setup 里的 t() —— 用 i18n 实例上的全局 t。
const t = i18n.global.t;

/**
 * @description 操作单条数据信息 (二次确认【删除、禁用、启用、重置密码】)
 * @param {Function} api 操作数据接口的api方法 (必传)
 * @param {Object} params 携带的操作数据参数 {id,params} (必传)
 * @param {String} message 提示信息 (必传)
 * @param {String} confirmType icon类型 (不必传,默认为 warning)
 * @returns {Promise}
 */
export const useHandleData = (
  api: (params: any) => Promise<any>,
  params: any = {},
  message: string,
  confirmType: HandleData.MessageType = "warning"
) => {
  return new Promise((resolve, reject) => {
    ElMessageBox.confirm(t("handle.confirm", { action: message }), t("handle.tip"), {
      confirmButtonText: t("common.confirm"),
      cancelButtonText: t("common.cancel"),
      type: confirmType,
      draggable: true
    })
      .then(async () => {
        const res = await api(params);
        if (!res) return reject(false);
        ElMessage({
          type: "success",
          message: t("handle.done", { action: message })
        });
        resolve(true);
      })
      .catch(() => {
        // cancel operation
      });
  });
};
