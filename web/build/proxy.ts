import type { ProxyOptions } from "vite";

/**
 * 一条代理规则：[前缀, 目标地址, 是否剥掉前缀?]
 *
 * 第三项 stripPrefix 默认 false —— 转发时**原样保留**前缀，
 * 浏览器发什么路径，后端就收到什么路径。这样开发环境和生产环境
 * 收到的 URL 完全一致，排查问题时不用在脑子里做一次前缀加减法。
 *
 * 只有当目标地址自带路径前缀时才需要设为 true，典型是 mock 服务：
 *   ["/api", "https://mock.mengxuegu.com/mock/629d727e", true]
 *   浏览器 /api/login → 剥掉 /api → 拼到目标 → /mock/629d727e/login
 */
type ProxyItem = [string, string] | [string, string, boolean];

type ProxyList = ProxyItem[];

type ProxyTargetList = Record<string, ProxyOptions>;

/**
 * 创建代理，用于解析 .env.development 里的 VITE_PROXY 配置
 * @param list
 */
export function createProxy(list: ProxyList = []) {
  const ret: ProxyTargetList = {};
  for (const [prefix, target, stripPrefix = false] of list) {
    const httpsRE = /^https:\/\//;
    const isHttps = httpsRE.test(target);

    // https://github.com/http-party/node-http-proxy#options
    ret[prefix] = {
      target: target,
      changeOrigin: true,
      ws: true,
      ...(stripPrefix ? { rewrite: (path: string) => path.replace(new RegExp(`^${prefix}`), "") } : {}),
      // https is require secure=false
      ...(isHttps ? { secure: false } : {})
    };
  }
  return ret;
}
