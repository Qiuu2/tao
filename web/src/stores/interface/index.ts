export type LayoutType = "vertical" | "classic" | "transverse" | "columns";

export type AssemblySizeType = "large" | "default" | "small";

export type LanguageType = "zh" | "en" | null;

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
