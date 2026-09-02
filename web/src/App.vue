<template>
  <el-config-provider :locale="locale" :size="assemblySize" :button="buttonConfig">
    <router-view></router-view>
  </el-config-provider>
</template>

<script setup lang="ts">
import { ElConfigProvider } from "element-plus";
import zhCn from "element-plus/es/locale/lang/zh-cn";
import { computed, onMounted, reactive } from "vue";
import { useI18n } from "vue-i18n";

import { useTheme } from "@/hooks/useTheme";
import { useGlobalStore } from "@/stores/modules/global";

import { LanguageType } from "./stores/interface";

const globalStore = useGlobalStore();

// init theme
const { initTheme } = useTheme();
initTheme();

/*
  语言固定中文。

  ⚠ 原来这里是「globalStore.language ?? getBrowserLang()」，
    也就是没设置过就去猜浏览器语言。这台机器上的浏览器报的是 en-US，
    于是分页条（Total / items/page / Go to）、日期时间选择器（Mon/Tue、
    Now/OK/Clear）、表格空数据提示这些 **Element Plus 自带的文案全成了英文**。

    这是个中文产品，没有出英文版的打算，所以不猜了 —— 直接钉死 zh-cn。
    要做多语言时再把 getBrowserLang 那套接回来。
*/
const i18n = useI18n();
onMounted(() => {
  i18n.locale.value = "zh";
  globalStore.setGlobalState("language", "zh" as LanguageType);
});

// element language：恒为简体中文
const locale = computed(() => zhCn);

// element assemblySize
const assemblySize = computed(() => globalStore.assemblySize);

// element button config
const buttonConfig = reactive({ autoInsertSpace: false });
</script>
