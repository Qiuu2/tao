<template>
  <div class="fullscreen">
    <i :class="['iconfont', isFullscreen ? 'icon-suoxiao' : 'icon-fangda']" class="toolBar-icon" @click="handleFullScreen"></i>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from "vue-i18n";
import { ElMessage } from "element-plus";
import screenfull from "screenfull";
import { onMounted, ref } from "vue";

// 脚本里拼的文案用 t()；模板里的 $t 不用引入
const { t } = useI18n();

const isFullscreen = ref(screenfull.isFullscreen);

onMounted(() => {
  screenfull.on("change", () => {
    if (screenfull.isFullscreen) isFullscreen.value = true;
    else isFullscreen.value = false;
  });
});

const handleFullScreen = () => {
  if (!screenfull.isEnabled) ElMessage.warning(t("sys.noFullscreen"));
  screenfull.toggle();
};
</script>
