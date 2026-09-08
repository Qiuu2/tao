<!-- 💥 这里是一次性加载 LayoutComponents -->
<template>
  <el-watermark id="watermark" :font="font" :content="watermark ? ['IP数字网络广播系统'] : ''">
    <component :is="LayoutComponents[layout]" />
    <ThemeDrawer />
    <!-- AI 助手浮在所有页面之上。助手功能关掉时它自己不出现，见组件里的 ready -->
    <AiAssistant />
  </el-watermark>
</template>

<script setup lang="ts" name="layout">
import { type Component, computed, reactive, watch } from "vue";

import { LayoutType } from "@/stores/interface";
import { useGlobalStore } from "@/stores/modules/global";

import AiAssistant from "@/components/AiAssistant/index.vue";

import ThemeDrawer from "./components/ThemeDrawer/index.vue";
import LayoutClassic from "./LayoutClassic/index.vue";
import LayoutColumns from "./LayoutColumns/index.vue";
import LayoutTransverse from "./LayoutTransverse/index.vue";
import LayoutVertical from "./LayoutVertical/index.vue";

const LayoutComponents: Record<LayoutType, Component> = {
  vertical: LayoutVertical,
  classic: LayoutClassic,
  transverse: LayoutTransverse,
  columns: LayoutColumns
};

const globalStore = useGlobalStore();

const isDark = computed(() => globalStore.isDark);
const layout = computed(() => globalStore.layout);
const watermark = computed(() => globalStore.watermark);

const font = reactive({ color: "rgba(0, 0, 0, .15)" });
watch(isDark, () => (font.color = isDark.value ? "rgba(255, 255, 255, .15)" : "rgba(0, 0, 0, .15)"), {
  immediate: true
});
</script>

<style scoped lang="scss">
.layout {
  min-width: 600px;
}
</style>
