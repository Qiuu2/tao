export type LayoutType = "vertical" | "classic" | "transverse" | "columns";

export type AssemblySizeType = "large" | "default" | "small";

/**
 * 界面语言。
 *
 * ⚠ 不再有 null。null 的含义是「没选过，去猜浏览器语言」，
 * 而在这个产品里猜出来的结果（这台机器上是 en-US）几乎总是错的，
 * 还让「当前是什么语言」变成一个要跑一遍逻辑才知道的问题。
 * 没选过就是中文，写在默认值里。
 */
export type LanguageType = "zh" | "en";

/* UserState */
export interface UserState {
  token: string;
  /**
   * name 供模板自带的头部组件展示；其余字段来自后端 /api/login 返回的用户信息，
   * 包含用户组的 13 项功能权限位与备机只读标记。
   */
  userInfo: { name: string; [key: string]: any };
}

/* tabsMenuProps */
export interface TabsMenuProps {
  icon: string;
  title: string;
  path: string;
  name: string;
  close: boolean;
  isKeepAlive: boolean;
}

/* TabsState */
export interface TabsState {
  tabsMenuList: TabsMenuProps[];
}
