import http from "@/api";
import { PORT1 } from "@/api/config/servicePort";

/** 一把开发者密钥。 */
export interface ApiKeyItem {
  id: number;
  name: string;
  /** 明文密钥的前 8 位。不是秘密，只用来在列表里认出是哪一把。 */
  prefix: string;
  userId: number;
  userName: string;
  enabled: boolean;
  /** 空串 = 不过期 */
  expiretime: string;
  lastusedtime: string;
  lastusedip: string;
  createtime: string;
  /**
   * 明文密钥。**只有新建那一次的响应里才有值**，之后永远是空 ——
   * 库里存的是 sha256，服务端自己也拿不回来。
   * 所以新建后必须当场让用户抄走。
   */
  secret?: string;
}

export interface ApiKeyCreateReq {
  name: string;
  userId: number;
  /** 留空 = 不过期。格式 2026-12-31 或 2026-12-31 18:00:00 */
  expiretime?: string;
}

/** 「这把密钥能挂到谁名下」的一个候选。 */
export interface ApiKeyAccount {
  id: number;
  username: string;
  groupName: string;
  /** 直通所有权限的账号。界面上要警示 */
  isAdmin: boolean;
}

/**
 * 可选的归属账号。
 *
 * ⚠ 不用 /api/users —— 那个列表恒不显示 admin（BR-106，那是给用户管理页的规则），
 * 拿来当候选就永远没法给 admin 发密钥，而很多装机现场只有 admin 一个账号。
 * 这个接口返回的名单与后端 assertCanGrant 的判断完全一致：能选的就一定能提交成功。
 */
export const getApiKeyAccountsApi = () =>
  http.get<ApiKeyAccount[]>(PORT1 + `/api/openapi-keys/accounts`, {}, { loading: false });

export const getApiKeyListApi = () => http.get<ApiKeyItem[]>(PORT1 + `/api/openapi-keys`, {}, { loading: false });

export const createApiKeyApi = (data: ApiKeyCreateReq) => http.post<ApiKeyItem>(PORT1 + `/api/openapi-keys`, data);

export const setApiKeyStateApi = (id: number, enabled: boolean) =>
  http.put<null>(PORT1 + `/api/openapi-keys/${id}/state`, { enabled });

export const deleteApiKeyApi = (id: number) => http.delete<null>(PORT1 + `/api/openapi-keys/${id}`);
