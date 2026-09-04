<!--
  时/分/秒 三个下拉，值是「秒」。

  旧版表单里凡是填时长的地方（文件广播的播放时长、间隔长度、间隔时长，
  作息方案的播放时长弹层）都是这三个下拉，而不是让人直接填秒数 ——
  「1 小时 30 分」比「5400」好填也好核对。这里把这套控件抽出来复用。
-->
<template>
  <div class="hms">
    <el-select :model-value="h" size="small" :disabled="disabled" style="width: 74px" @update:model-value="v => emitAt(v, m, s)">
      <el-option v-for="n in 24" :key="n - 1" :label="pad(n - 1)" :value="n - 1" />
    </el-select>
    <span class="hms-u">时</span>
    <el-select :model-value="m" size="small" :disabled="disabled" style="width: 74px" @update:model-value="v => emitAt(h, v, s)">
      <el-option v-for="n in 60" :key="n - 1" :label="pad(n - 1)" :value="n - 1" />
    </el-select>
    <span class="hms-u">分</span>
    <el-select :model-value="s" size="small" :disabled="disabled" style="width: 74px" @update:model-value="v => emitAt(h, m, v)">
      <el-option v-for="n in 60" :key="n - 1" :label="pad(n - 1)" :value="n - 1" />
    </el-select>
    <span class="hms-u">秒</span>
  </div>
</template>

<script setup lang="ts" name="HmsInput">
import { computed } from "vue";

const props = withDefaults(defineProps<{ modelValue: number; disabled?: boolean }>(), { disabled: false });
const emit = defineEmits<{ "update:modelValue": [value: number] }>();

const total = computed(() => Math.max(0, Math.floor(props.modelValue || 0)));
const h = computed(() => Math.min(23, Math.floor(total.value / 3600)));
const m = computed(() => Math.floor((total.value % 3600) / 60));
const s = computed(() => total.value % 60);

const pad = (n: number) => String(n).padStart(2, "0");
const emitAt = (hh: number, mm: number, ss: number) => emit("update:modelValue", hh * 3600 + mm * 60 + ss);
</script>

<style scoped lang="scss">
.hms {
  display: inline-flex;
  gap: 4px;
  align-items: center;
}
.hms-u {
  margin-right: 6px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
</style>
