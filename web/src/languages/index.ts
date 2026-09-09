import { createI18n } from "vue-i18n";

import en from "./modules/en";
import zh from "./modules/zh";

/**
 * 初始语言取**上次选的那个**。
 *
 * ⚠ 这里曾经写死 "zh"，而 App.vue 每次挂载还会再把它按回 "zh" ——
 * 于是右上角点 English 只在当前这一瞬有效，刷新就回中文，
 * Element Plus 自带的文案（分页、日期选择器）则从头到尾没变过。
 *
 * 也不能像脚手架原来那样按浏览器语言猜：这台机器上的浏览器报 en-US，
 * 一进来界面就是英文的，而绝大多数用户要的是中文。
 * 所以规则是「用户选过就听用户的，没选过默认中文」。
 *
 * 直接读 localStorage 而不是 pinia：i18n 实例在 pinia 装上之前就要建好。
 * key 是 pinia 持久化插件给 global store 用的那个。
 */
function initialLocale(): "zh" | "en" {
  try {
    const raw = localStorage.getItem("geeker-global");
    if (raw) {
      const lang = JSON.parse(raw)?.language;
      if (lang === "en" || lang === "zh") return lang;
    }
  } catch {
    // 存储被禁用或内容坏了，按默认来 —— 不该因为读不到偏好就打不开页面
  }
  return "zh";
}

const i18n = createI18n({
  // Use Composition API, Set to false
  allowComposition: true,
  legacy: false,
  locale: initialLocale(),
  fallbackLocale: "zh",
  // 缺键时不要在控制台刷屏：翻译是一页一页补上去的，
  // 补到哪儿一目了然靠的是页面本身，不是几千行警告。
  missingWarn: false,
  fallbackWarn: false,
  messages: {
    zh,
    en
  }
});

export default i18n;

/**
 * 菜单标题的翻译入口 —— 侧边栏、面包屑、页签、菜单搜索共用这一个。
 *
 * 按菜单项的 name 查 `menu.*`；字典里没有就用后端下发的标题。
 * 新加了菜单还没配翻译时，显示中文比显示一个 "menu.xxx" 的键名强。
 *
 * ⚠ 页签必须**渲染时**调它，不能只在存进 store 时翻一次：
 * 页签把标题存进了自己的持久化状态，存的时候翻等于切换语言后
 * 已经打开的那几个页签永远停在旧语言上。
 */
export function menuTitle(name: string | undefined, fallback: string): string {
  if (!name) return fallback;
  const key = `menu.${name}`;
  return i18n.global.te(key) ? (i18n.global.t(key) as string) : fallback;
}
