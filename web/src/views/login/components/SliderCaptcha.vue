<template>
  <!--
    滑动验证：按住滑块，拖到最右边。

    # ⚠ 它挡不住脚本，这一点别弄错

    滑动这个动作完全发生在浏览器里，服务端**验不了它真的发生过**。
    服务端那边只保证 captchaId 是刚发的、没过期、没用过
    （见 server/internal/captcha 的 GenerateSlider）。
    换成滑动是产品上的选择；要防暴力试密码，得另外加登录失败限流。

    # 做法上刻意踩住的几个坑

    · **不用 overflow:hidden 裁滑块。** 第一版是「圆形滑块 + 容器 overflow:hidden
      + border-radius:21px」，结果滑块被容器的圆角切掉一块，看着像个缺口的月亮，
      而且它比容器内高 2px（border），上下还各被削一刀。
      现在轨道和滑块都是 4px 圆角的方块（和 el-input 一致），
      填充条单独放在一个裁剪层里，滑块浮在上面，谁也不切谁。

    · **高度跟 el-input 的 large 走**（40px）。登录框里三样东西上下排着，
      差几像素一眼就看得出来。

    · 指针事件用 pointerdown/move/up，一套代码同时管鼠标和触屏 ——
      分别写 mouse* 和 touch* 那一套，在带触摸屏的一体机上两套都会触发。

    · 监听挂在 window 上并且 setPointerCapture —— 手指/鼠标拖出轨道外面时
      不能卡在半路，那是这类组件最常见的毛病。

    · **不挂 Enter/Space 直接通过。** 登录表单在 document 上挂了回车提交，
      再让这里认回车，就变成「打完密码按回车，滑块自己过了」——
      那这个验证等于不存在。键盘走 → / End，和真正的滑块一致。
  -->
  <div
    ref="trackRef"
    class="slider-captcha"
    :class="{ 'is-done': done, 'is-dragging': dragging }"
    role="slider"
    tabindex="0"
    aria-valuemin="0"
    aria-valuemax="100"
    :aria-valuenow="done ? 100 : Math.round((offset / Math.max(maxOffset(), 1)) * 100)"
    :aria-label="$t('login.slideToVerify')"
    @keydown="onKey"
  >
    <div class="track">
      <div class="fill" :style="{ width: `${offset + HANDLE}px` }"></div>
    </div>
    <span class="tip" :class="{ shimmer: !done && !dragging }">
      {{ done ? $t("login.slideVerified") : $t("login.slideToVerify") }}
    </span>
    <div class="handle" :style="{ transform: `translateX(${offset}px)` }" @pointerdown="onDown">
      <el-icon>
        <component :is="done ? Check : DArrowRight" />
      </el-icon>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Check, DArrowRight } from "@element-plus/icons-vue";
import { onBeforeUnmount, onMounted, ref } from "vue";

const emit = defineEmits<{ (e: "success"): void }>();

/** 滑块宽度，与下面 scss 里的 .handle 保持一致 */
const HANDLE = 40;
/** 差这么几个像素就算到底 —— 要求精确拖到最后一个像素太苛刻 */
const SNAP = 6;
/** 键盘一次推进多少像素 */
const KEY_STEP = 24;

const trackRef = ref<HTMLElement>();
const offset = ref(0);
const dragging = ref(false);
const done = ref(false);

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

const onMove = (e: PointerEvent) => {
  if (!dragging.value) return;
  const max = maxOffset();
  offset.value = Math.min(max, Math.max(0, startOffset + e.clientX - startX));
  if (offset.value >= max - SNAP) finish();
};

const detach = () => {
  window.removeEventListener("pointermove", onMove);
  window.removeEventListener("pointerup", onUp);
  window.removeEventListener("pointercancel", onUp);
};

function onUp() {
  if (!dragging.value) return;
  dragging.value = false;
  detach();
  // 没拖到头就弹回起点。停在半路上的滑块会让人以为「拖了就算数」。
  if (!done.value) offset.value = 0;
}

const onDown = (e: PointerEvent) => {
  if (done.value) return;
  dragging.value = true;
  startX = e.clientX;
  startOffset = offset.value;
  // 拖出轨道外面也不丢事件
  (e.currentTarget as HTMLElement).setPointerCapture?.(e.pointerId);
  window.addEventListener("pointermove", onMove);
  window.addEventListener("pointerup", onUp);
  window.addEventListener("pointercancel", onUp);
};

/** 键盘：→ / ↑ 推一格，End 直接到底。刻意不认回车，理由见文件头 */
const onKey = (e: KeyboardEvent) => {
  if (done.value) return;
  const max = maxOffset();
  if (e.key === "ArrowRight" || e.key === "ArrowUp") {
    e.preventDefault();
    offset.value = Math.min(max, offset.value + KEY_STEP);
    if (offset.value >= max - SNAP) finish();
  } else if (e.key === "End") {
    e.preventDefault();
    finish();
  } else if (e.key === "ArrowLeft" || e.key === "ArrowDown") {
    e.preventDefault();
    offset.value = Math.max(0, offset.value - KEY_STEP);
  }
};

/** 窗口宽度变了，已经通过的滑块要重新贴到最右边，否则会飘在中间 */
const onResize = () => {
  if (done.value) offset.value = maxOffset();
};

/** 登录失败后要重来一次：凭据是一次性的，父组件会重新领一张 */
const reset = () => {
  done.value = false;
  dragging.value = false;
  offset.value = 0;
  detach();
};

defineExpose({ reset, done });
onMounted(() => window.addEventListener("resize", onResize));
onBeforeUnmount(() => {
  detach();
  window.removeEventListener("resize", onResize);
});
</script>

<style scoped lang="scss">
// 高度与 el-input 的 large 一致（40px），登录框里三样东西才对得齐
$h: 40px;

.slider-captcha {
  position: relative;
  width: 100%;
  height: $h;
  user-select: none;
  background-color: var(--el-fill-color-light);
  border: 1px solid var(--el-border-color);
  border-radius: var(--el-border-radius-base);
  transition: border-color 0.2s;
  &:focus-visible {
    border-color: var(--el-color-primary);
    outline: none;
    box-shadow: 0 0 0 2px var(--el-color-primary-light-8);
  }
  &.is-done {
    background-color: var(--el-color-success-light-9);
    border-color: var(--el-color-success);
  }
}

// 填充条单独放在这一层里裁剪 —— 滑块在它外面，不会被圆角切到
.track {
  position: absolute;
  inset: 0;
  overflow: hidden;
  border-radius: inherit;
}
.fill {
  height: 100%;
  background-color: var(--el-color-primary-light-8);
  transition: width 0.2s;
  .is-dragging & {
    transition: none; // 拖动时不过渡，否则填充条会「追着」手指走
  }
  .is-done & {
    background-color: var(--el-color-success-light-8);
    transition: width 0.2s;
  }
}

.tip {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  color: var(--el-text-color-secondary);
  pointer-events: none;
  .is-done & {
    font-weight: 500;
    color: var(--el-color-success);
  }
}

/*
  一道从左扫到右的高光，提示「这个东西是可以拖的」。
  纯装饰：prefers-reduced-motion 下自动停掉。
*/
.shimmer {
  background: linear-gradient(
    90deg,
    var(--el-text-color-secondary) 0%,
    var(--el-text-color-secondary) 40%,
    var(--el-color-primary) 50%,
    var(--el-text-color-secondary) 60%,
    var(--el-text-color-secondary) 100%
  );
  background-clip: text;
  background-size: 220% 100%;
  -webkit-text-fill-color: transparent;
  animation: slider-shimmer 2.4s linear infinite;
}
@keyframes slider-shimmer {
  from {
    background-position: 100% 0;
  }
  to {
    background-position: -120% 0;
  }
}
@media (prefers-reduced-motion: reduce) {
  .shimmer {
    animation: none;
  }
}

.handle {
  position: absolute;
  top: -1px;
  left: -1px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: $h;
  color: var(--el-color-primary);
  cursor: grab;
  background-color: var(--el-bg-color);
  border: 1px solid var(--el-border-color);
  border-radius: var(--el-border-radius-base);
  box-shadow: 0 1px 4px rgb(0 0 0 / 10%);
  transition:
    transform 0.2s,
    border-color 0.2s;
  &:active {
    cursor: grabbing;
  }
  .is-dragging & {
    transition: border-color 0.2s; // 跟手，不要位移过渡
    border-color: var(--el-color-primary);
  }
  .is-done & {
    color: var(--el-color-success);
    cursor: default;
    border-color: var(--el-color-success);
  }
}
</style>
