// ? 全局默认配置项

// 首页地址（默认）
export const HOME_URL: string = "/home";

// 登录页地址（默认）
export const LOGIN_URL: string = "/login";

// 默认主题颜色
export const DEFAULT_PRIMARY: string = "#009688";

// 路由白名单地址（本地存在的路由 staticRouter.ts 中）
// 注册服务页要能在登录前打开：服务器没注册时后端直接拒绝登录（BR-71），
// 不放行的话就成了「要注册得先登录、要登录得先注册」的死循环。
export const ROUTER_WHITE_LIST: string[] = ["/500", "/register"];

// 高德地图 key
export const AMAP_MAP_KEY: string = "";

// 百度地图 key
export const BAIDU_MAP_KEY: string = "";
