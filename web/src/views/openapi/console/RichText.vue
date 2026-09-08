<!--
  把接口目录里的 **粗体** 标记渲染成真的粗体。

  # 为什么需要这么个东西

  目录里的说明文字（取值对照、注意事项、字段说明）用 **…** 标出「猜错了会出事」
  的那几处。直接插值的话，页面上就是一串字面的星号 —— 该被强调的反而更难读。

  # 为什么不用 v-html

  文案眼下全部来自我们自己的后端目录，但「眼下安全」不是理由：
  这个组件将来很可能被拿去渲染别处的文字，而那时没有人会回头检查它用了 v-html。
  按分隔符切成片段、交给 Vue 正常转义，既达到同样效果，又不留这个口子。
-->
<template>
  <span>
    <template v-for="(seg, i) in segments" :key="i">
      <b v-if="seg.bold">{{ seg.text }}</b>
      <template v-else>{{ seg.text }}</template>
    </template>
  </span>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{ text?: string }>();

/** 按 ** 切片，奇数段是粗体。落单的 ** 会原样留在文字里，不会吃掉后半句。 */
const segments = computed(() => {
  const parts = (props.text ?? "").split("**");
  return parts.map((text, i) => ({ text, bold: i % 2 === 1 && i < parts.length - 1 }));
});
</script>
