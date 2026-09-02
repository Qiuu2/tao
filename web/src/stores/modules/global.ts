import { defineStore } from "pinia";
import { ref, type UnwrapRef } from "vue";

import { DEFAULT_PRIMARY } from "@/config";
import type { AssemblySizeType, LanguageType, LayoutType } from "@/stores/interface";

export const useGlobalStore = defineStore(
  "geeker-global",
  () => {
    // 布局模式 (纵向：vertical | 经典：classic | 横向：transverse | 分栏：columns)
    const layout = ref<LayoutType>("vertical");
    // element 组件大小
    const assemblySize = ref<AssemblySizeType>("default");
    // 当前系统语言。⚠ 默认就是中文，不再是 null（null 会触发按浏览器语言猜，
    // 这台机器上会猜成英文，界面文案全变英文）。见 App.vue 的说明。
    const language = ref<LanguageType>("zh");
    // 当前页面是否全屏
    const maximize = ref<boolean>(false);
    // 主题颜色
    const primary = ref<string>(DEFAULT_PRIMARY);
    // 深色模式
    const isDark = ref<boolean>(false);
    // 灰色模式
    const isGrey = ref<boolean>(false);
    // 色弱模式
    const isWeak = ref<boolean>(false);
    // 侧边栏反转
    const asideInverted = ref<boolean>(false);
    // 头部反转
    const headerInverted = ref<boolean>(false);
    // 折叠菜单
    const isCollapse = ref<boolean>(false);
    // 菜单手风琴
    const accordion = ref<boolean>(true);
    // 页面水印
    const watermark = ref<boolean>(false);
    // 面包屑导航
    const breadcrumb = ref<boolean>(true);
    // 面包屑导航图标
    const breadcrumbIcon = ref<boolean>(true);
    // 标签页
    const tabs = ref<boolean>(true);
    // 标签页图标
    const tabsIcon = ref<boolean>(true);
    // 页脚
    const footer = ref<boolean>(true);

    const stateMap = {
      layout,
      assemblySize,
      language,
      maximize,
      primary,
      isDark,
      isGrey,
      isWeak,
      asideInverted,
      headerInverted,
      isCollapse,
      accordion,
      watermark,
      breadcrumb,
      breadcrumbIcon,
      tabs,
      tabsIcon,
      footer
    } as const;

    // Set GlobalState
    const setGlobalState = <K extends keyof typeof stateMap>(key: K, value: UnwrapRef<(typeof stateMap)[K]>) => {
      const stateRef = stateMap[key];
      if (stateRef) {
        stateRef.value = value;
      }
    };

    return {
      ...stateMap,
      setGlobalState
    };
  },
  {
    persist: {
      key: "geeker-global",
      storage: localStorage
    }
  }
);
