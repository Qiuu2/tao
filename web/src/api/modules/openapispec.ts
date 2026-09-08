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
}

export interface SpecGroup {
  name: string;
  desc: string;
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
