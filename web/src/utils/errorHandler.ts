import { ElNotification } from "element-plus";

import i18n from "@/languages";

/**
 * @description 全局代码错误捕捉
 * */
const errorHandler = (error: any) => {
  // 过滤 HTTP 请求错误
  if (error.status || error.status == 0) return false;
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
