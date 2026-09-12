<template>
  <!--
    滑动验证：按住滑块拖到最右边。

    ⚠ 这**不是**图形验证码的等价替换。滑动这件事完全发生在浏览器里，
      服务端验不了它真的发生过 —— 它挡不住脚本化的暴力试密码。
      服务端那边只保证 captchaId 是刚发的、没过期、没用过
      （见 server/internal/captcha 的 GenerateSlider 注释）。
      换成滑动是产品上的选择，要防暴力破解得另外加登录失败限流。

    做法上刻意避开的两个坑：
      · 指针事件用 pointerdown/move/up，一套代码同时管鼠标和触屏 ——
        分别写 mouse* 和 touch* 那一套，在带触摸屏的一体机上会两套都触发。
      · 监听挂在 window 上而不是滑块上，并且用 setPointerCapture ——
        手指/鼠标拖出轨道外面时不能卡在半路上，那是这类组件最常见的毛病。
  -->
  <div
    ref="trackRef"
    class="slider-captcha"
    :class="{ 'is-done': done }"
    role="button"
    tabindex="0"
    :aria-label="$t('login.slideToVerify')"
    @keydown.enter.prevent="finish"
    @keydown.space.prevent="finish"
  >
    <div class="fill" :style="{ width: fillWidth }"></div>
    <span class="tip">{{ done ? $t("login.slideVerified") : $t("login.slideToVerify") }}</span>
    <div class="handle" :style="{ transform: `translateX(${offset}px)` }" :class="{ dragging }" @pointerdown="onDown">
      <el-icon>
        <component :is="done ? Check : DArrowRight" />
      </el-icon>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Check, DArrowRight } from "@element-plus/icons-vue";
import { computed, onBeforeUnmount, ref } from "vue";

const emit = defineEmits<{ (e: "success"): void }>();

/** 滑块宽度，与下面 scss 里的 .handle 宽度保持一致 */
const HANDLE = 42;
/** 差这么几个像素就算到底了 —— 要求精确拖到最后一个像素太苛刻 */
const SNAP = 6;

const trackRef = ref<HTMLElement>();
const offset = ref(0);
const dragging = ref(false);
const done = ref(false);

const fillWidth = computed(() => `${offset.value + HANDLE}px`);

/** 轨道里滑块能走的最大距离 */
const maxOffset = () => Math.max(0, (trackRef.value?.clientWidth ?? 0) - HANDLE);

const finish = () => {
  if (done.value) return;
  done.value = true;
  dragging.value = false;
  offset.value = maxOffset();
  emit("success");
};

let startX = 0;
let startOffset = 0;
let capturedBy: HTMLElement | null = null;

const onMove = (e: PointerEvent) => {
  if (!dragging.value) return;
  const max = maxOffset();
  offset.value = Math.min(max, Math.max(0, startOffset + e.clientX - startX));
  if (offset.value >= max - SNAP) finish();
};

const onUp = () => {
  if (!dragging.value) return;
  dragging.value = false;
  detach();
  // 没拖到头就弹回起点。停在半路上的滑块会让人以为「拖了就算数」。
  if (!done.value) offset.value = 0;
};

const detach = () => {
  window.removeEventListener("pointermove", onMove);
  window.removeEventListener("pointerup", onUp);
  window.removeEventListener("pointercancel", onUp);
  capturedBy = null;
};

const onDown = (e: PointerEvent) => {
  if (done.value) return;
  dragging.value = true;
  startX = e.clientX;
  startOffset = offset.value;
  // 拖出轨道外面也不丢事件
  capturedBy = e.currentTarget as HTMLElement;
  capturedBy.setPointerCapture?.(e.pointerId);
  window.addEventListener("pointermove", onMove);
  window.addEventListener("pointerup", onUp);
  window.addEventListener("pointercancel", onUp);
};

/** 登录失败后要重来一次：凭据是一次性的，父组件会重新领一张 */
const reset = () => {
  done.value = false;
  dragging.value = false;
  offset.value = 0;
  detach();
};

defineExpose({ reset, done });
onBeforeUnmount(detach);
</script>

<style scoped lang="scss">
.slider-captcha {
  position: relative;
  width: 100%;
  height: 42px;
  overflow: hidden;
  user-select: none;
  background-color: var(--el-fill-color-light);
  border: 1px solid var(--el-border-color);
  border-radius: 21px;
  &:focus-visible {
    outline: 2px solid var(--el-color-primary);
    outline-offset: 2px;
  }
  &.is-done {
    border-color: var(--el-color-success);
  }
}
.fill {
  position: absolute;
  inset: 0 auto 0 0;
  background-color: var(--el-color-primary-light-8);
  .is-done & {
    background-color: var(--el-color-success-light-8);
  }
}
.tip {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  color: var(--el-text-color-secondary);
  pointer-events: none;
  .is-done & {
    color: var(--el-color-success);
  }
}
.handle {
  position: absolute;
  top: -1px;
  left: -1px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  color: var(--el-text-color-regular);
  cursor: grab;
  background-color: var(--el-bg-color);
  border: 1px solid var(--el-border-color);
  border-radius: 50%;
  box-shadow: 0 1px 4px rgb(0 0 0 / 12%);
  // 拖动时不要过渡，否则滑块会「追着」手指走
  &:not(.dragging) {
    transition: transform 0.2s;
  }
  &.dragging {
    cursor: grabbing;
  }
  .is-done & {
    color: var(--el-color-success);
    cursor: default;
    border-color: var(--el-color-success);
  }
}
</style>
