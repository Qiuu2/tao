<template>
  <el-config-provider :locale="locale" :size="assemblySize" :button="buttonConfig">
    <router-view></router-view>
  </el-config-provider>
</template>

<script setup lang="ts">
import { ElConfigProvider } from "element-plus";
import en from "element-plus/es/locale/lang/en";
import zhCn from "element-plus/es/locale/lang/zh-cn";
import { computed, reactive, watch } from "vue";
import { useI18n } from "vue-i18n";

import { useTheme } from "@/hooks/useTheme";
import { useGlobalStore } from "@/stores/modules/global";

const globalStore = useGlobalStore();

// init theme
const { initTheme } = useTheme();
initTheme();

/*
  语言跟着用户选的走。

  ⚠ 这里原来是 onMounted 里无条件 `i18n.locale.value = "zh"` ——
    右上角点 English 之后，只要有任何一次重新挂载（刷新、路由到全屏页再回来）
    就被按回中文，而 Element Plus 的 locale 更是写死 zhCn 从来没动过。
    表现就是「点了 English 什么也没发生」。

    默认值放在 store 里（"zh"），初始值由 languages/index.ts 从
    localStorage 读回来，两边同一个来源，不再各写各的。
*/
const i18n = useI18n();
watch(
  () => globalStore.language,
  lang => {
    i18n.locale.value = lang;
    // 记在 html 上：CSS 里要按语言微调宽度时有个抓手，
    // 也方便排查「现在到底是什么语言」。
    document.documentElement.setAttribute("lang", lang === "en" ? "en" : "zh-CN");
  },
  { immediate: true }
);

// Element Plus 自带的文案（分页「共 x 条」、日期选择器的星期、表格空数据提示）
// 跟着一起切。只切我们自己的 $t 而不切它，会切出一个中英夹杂的界面。
const locale = computed(() => (globalStore.language === "en" ? en : zhCn));

// element assemblySize
const assemblySize = computed(() => globalStore.assemblySize);

// element button config
const buttonConfig = reactive({ autoInsertSpace: false });
</script>
