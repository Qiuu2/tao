import http from "@/api";
import { PORT1 } from "@/api/config/servicePort";

/** 一个 query 或路径参数。 */
export interface SpecParam {
  name: string;
  /** query | path */
  in: string;
  type: string;
  required: boolean;
  desc: string;
  /** 「试一试」表单的预填值。填的是真能跑通的值，不是 "string" 这种占位 */
  example: string;
}

/** 请求体里的一个字段。 */
export interface SpecField {
  name: string;
  type: string;
  required: boolean;
  desc: string;
}

export interface SpecEndpoint {
  id: string;
  method: string;
  /** 不含 /openapi/v1 前缀 */
  path: string;
  summary: string;
  desc: string;
  right: string;
  params?: SpecParam[];
  fields?: SpecField[];
  /** 请求体示例（JSON 文本） */
  body?: string;
  /** 响应示例（JSON 文本，不含 code/msg 信封） */
  sample?: string;
  notes?: string[];
  /** 真的会让喇叭响、或者真的删东西 */
  danger?: boolean;
  /**
   * 没有逐参数说明的接口：平台上给可编辑的完整路径 + 自由 JSON 请求体。
   *
   * 全功能那 200 多条用的就是界面在用的那套参数，逐条抄一遍势必抄错、
   * 也势必跟不上改动 —— 一份看起来完整但有错的说明比明说「这里没有」更坏。
   */
  freeform?: boolean;
}

export interface SpecGroup {
  name: string;
  desc: string;
  /** 这一组的路径前缀。全功能那组的 path 本身就是完整路径，这里是空串 */
  prefix: string;
  /** curated = /openapi/v1 稳定合同；full = 全部功能接口，跟着界面走 */
  section: string;
  endpoints: SpecEndpoint[];
}

export interface ApiSpec {
  title: string;
  version: string;
  /** /openapi/v1 */
  prefix: string;
  groups: SpecGroup[];
}

export const getApiSpecApi = () => http.get<ApiSpec>(PORT1 + `/api/openapi/spec`, {}, { loading: false });
