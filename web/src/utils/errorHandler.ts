import { ElNotification } from "element-plus";

import i18n from "@/languages";

/**
 * 接口已经自己报过的失败长什么样。
 *
 * axios 的响应拦截器（api/index.ts）对 `code !== 200` 的返回做了两件事：
 * 先 `ElMessage.error(data.msg)` 把人话弹出来，再 `Promise.reject(data)`。
 * 组件里没接这个 reject 的话，它会一路冒到 Vue 的全局兜底 —— 也就是这个文件。
 *
 * 于是同一件事报两遍，而且第二遍是「未知错误」加一坨原始 JSON：
 *
 *     未知错误
 *     { "code": 40001, "msg": "选中的终端上没有任何离线内容，空闲传输 无事可做", … }
 *
 * 现场报上来的「云广播终端点按钮弹未知错误」就是它 —— 后端那句话本来说得清清楚楚，
 * 全靠这第二个弹窗把一次正常的「无事可做」变成了像是程序崩了。
 */
const isHandledApiError = (e: any) => e && typeof e === "object" && "code" in e && "msg" in e;

/**
 * 弹窗被取消长什么样。
 *
 * ElMessageBox.confirm 在人点「取消」或右上角叉时是 **reject**，
 * 理由是字符串 "cancel" / "close"。`await` 了又不 catch 的地方，
 * 它同样会冒到这里 —— 而「人点了取消」根本不是错误。
 */
const isDialogCancel = (e: any) => e === "cancel" || e === "close";

/**
 * @description 全局代码错误捕捉
 * */
const errorHandler = (error: any) => {
  // 过滤 HTTP 请求错误
  if (error.status || error.status == 0) return false;
  // 接口自己报过的、以及人点了取消的，都不是「代码出错」，不再弹第二遍
  if (isHandledApiError(error) || isDialogCancel(error)) return false;

  const errorMap: { [key: string]: string } = {
    InternalError: "internal",
    ReferenceError: "reference",
    TypeError: "type",
    RangeError: "range",
    SyntaxError: "syntax",
    EvalError: "eval",
    URIError: "uri"
  };
  // 这里在组件外面，取不到 setup 里的 t()
  const errorName = i18n.global.t(`jsErr.${errorMap[error.name] ?? "unknown"}`);
  ElNotification({
    title: errorName,
    message: error,
    type: "error",
    duration: 3000
  });
};

export default errorHandler;
