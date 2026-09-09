import { defineStore } from "pinia";
import { computed, ref } from "vue";

import { getAuthButtonListApi, getAuthMenuListApi } from "@/api/modules/login";
import { menuTitle } from "@/languages";
import { getAllBreadcrumbList, getFlatMenuList, getShowMenuList } from "@/utils";

/**
 * 把后端下发的菜单标题换成当前语言的。
 *
 * # 为什么在这里翻，而不是让后端下发英文
 *
 * 菜单每一项都有一个稳定的 name（home / terminal / openapiKeys …），
 * 按 name 查字典就够了，后端一行都不用改 —— 也就不用为「界面语言」
 * 再造一套 Accept-Language 的约定。
 *
 * # 为什么在 store 里翻，而不是在渲染的地方翻
 *
 * meta.title 有 8 处在读（侧边栏、面包屑、页签、菜单搜索、三种布局…），
 * 逐处包一层 $t，漏掉哪处就是那处永远中文。更麻烦的是页签：
 * 它把 title **存进了自己的状态**，渲染时翻等于切换语言后已开的页签不变。
 * 在源头翻，这些读取方一个都不用改，切语言时整棵树跟着重算。
 *
 * 字典里没有的 name 就保留后端给的标题 —— 新加了菜单还没来得及配翻译时，
 * 显示中文总比显示一个 "menu.xxx" 的键名强。
 */
function localizeMenu(list: Menu.MenuOptions[]): Menu.MenuOptions[] {
  const walk = (items: Menu.MenuOptions[]): Menu.MenuOptions[] =>
    items.map(item => ({
      ...item,
      meta: { ...item.meta, title: menuTitle(item.name, item.meta.title) },
      ...(item.children?.length ? { children: walk(item.children) } : {})
    }));
  return walk(list);
}

export const useAuthStore = defineStore("geeker-auth", () => {
  // 按钮权限列表
  const authButtonList = ref<{ [key: string]: string[] }>({});
  // 菜单权限列表
  const authMenuList = ref<Menu.MenuOptions[]>([]);
  // 当前页面的 router name，用来做按钮权限筛选
  const routeName = ref<string>("");

  // 按钮权限列表
  const authButtonListGet = computed(() => authButtonList.value);
  // 菜单权限列表 ==> 标题已按当前语言翻好；切语言时下面几个跟着重算
  const localizedMenuList = computed(() => localizeMenu(authMenuList.value));
  const authMenuListGet = computed(() => localizedMenuList.value);
  // 菜单权限列表 ==> 左侧菜单栏渲染，需要剔除 isHide == true
  const showMenuListGet = computed(() => getShowMenuList(localizedMenuList.value));
  // 菜单权限列表 ==> 扁平化之后的一维数组菜单，主要用来添加动态路由
  const flatMenuListGet = computed(() => getFlatMenuList(localizedMenuList.value));
  // 递归处理后的所有面包屑导航列表
  const breadcrumbListGet = computed(() => getAllBreadcrumbList(localizedMenuList.value));

  // Get AuthButtonList
  const getAuthButtonList = async () => {
    const { data } = await getAuthButtonListApi();
    authButtonList.value = data;
  };

  // Get AuthMenuList
  const getAuthMenuList = async () => {
    const { data } = await getAuthMenuListApi();
    authMenuList.value = data;
  };

  // Set RouteName
  const setRouteName = async (name: string) => {
    routeName.value = name;
  };

  return {
    authButtonList,
    authMenuList,
    routeName,
    authButtonListGet,
    authMenuListGet,
    showMenuListGet,
    flatMenuListGet,
    breadcrumbListGet,
    getAuthButtonList,
    getAuthMenuList,
    setRouteName
  };
});
