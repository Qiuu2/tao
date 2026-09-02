<!--
  终端选择下拉（树形）—— 给**表单里那种一格宽的单选**用。

  和同目录的 index.vue 是一对：
    index.vue   整棵树摊开，给对话框里「勾一批终端」用
    Select.vue  收成一个下拉，展开后里面还是同一棵树，给表单里一格宽的单选用

  为什么不统一用 index.vue：像「TTS采播终端」「采播源终端」「LED 所属终端」
  这些都躺在 :span="12" 的表单格里，塞一棵 300px 高的树会把整个对话框撑变形。
  el-tree-select 保持了下拉的外形，展开后仍是按分区分组的树，两边都顾到。

  用法：
    <TerminalTreeSelect v-model="form.sourceTerminalId" :terminals="terminals" />
    <TerminalTreeSelect v-model="id" :terminals="list" :allow-empty="true" empty-label="（不指定）" />
-->
<template>
  <el-tree-select
    :model-value="shown"
    :data="nodes"
    :props="{ label: 'label', children: 'children' }"
    node-key="key"
    :placeholder="placeholder"
    :clearable="clearable"
    :disabled="disabled"
    filterable
    check-strictly
    :render-after-expand="false"
    default-expand-all
    class="fill"
    @update:model-value="onPick"
  />
</template>

<script setup lang="ts" name="TerminalTreeSelect">
import { computed, onMounted, ref } from "vue";

import { loadZones, type ZoneNode } from "./zones";

interface Node {
  key: string | number;
  label: string;
  terminalId?: number;
  /** 分组节点设为 true —— 分组不是一台终端，不能被选中 */
  disabled?: boolean;
  children?: Node[];
}

const props = withDefaults(
  defineProps<{
    modelValue: number | undefined;
    terminals: any[];
    placeholder?: string;
    clearable?: boolean;
    disabled?: boolean;
    /** 允许一个「不指定」的空选项（TTS采播终端就需要，0 = 不指定） */
    allowEmpty?: boolean;
    emptyLabel?: string;
    /** 分区清单。不传时组件自己去 /api/zones/options 拉（结果全站共用一份缓存） */
    groups?: { id: number; name: string }[];
    groupField?: string;
    groupIdField?: string;
    ungroupedLabel?: string;
  }>(),
  {
    placeholder: "选择终端",
    clearable: true,
    disabled: false,
    allowEmpty: false,
    emptyLabel: "（不指定）",
    groupField: "groupName",
    groupIdField: "groupId",
    // 措辞照 ok112：language/chinese.php 的 No_group_terminal = "无分区终端"
    ungroupedLabel: "无分区终端"
  }
);

const emit = defineEmits<{ (e: "update:modelValue", v: number | undefined): void }>();

/** 与 index.vue 的 normalize 保持一致：七个接口字段名不统一，在这里抹平 */
const normalize = (t: any) => ({
  id: Number(t.id ?? t.terminalId ?? 0),
  name: String(t.name ?? t.terminalname ?? "").trim(),
  group: cleanGroup(t[props.groupField]),
  groupId: Number(t[props.groupIdField] ?? 0),
  netstate: Number(t.netstate ?? 0)
});

/* 分区清单：调用方没传就自己拉，与 index.vue 共用同一份缓存 */
const fetchedZones = ref<ZoneNode[]>([]);
const groups = computed(() => props.groups ?? fetchedZones.value);

onMounted(async () => {
  if (props.groups) return;
  fetchedZones.value = await loadZones();
});

/** ⚠ 与 index.vue 同因：task/alarm 两个接口用字面量 "(未分区)"，其余用空串 */
const cleanGroup = (v: any) => {
  const s = String(v ?? "").trim();
  return s === "(未分区)" || s === "（未分区）" ? "" : s;
};

/*
  和 index.vue 同一套骨架：**分区节点来自分区表**，空分区照样出现。
  ⚠ 分组节点一律 disabled —— 分区不是一台终端，选中它 v-model 会拿到 "g:1" 这种字符串。
*/
const nodes = computed<Node[]>(() => {
  const list: Node[] = [];
  const byId = new Map<number, Node>();
  const byName = new Map<string, Node>();

  for (const g of groups.value) {
    const node: Node = {
      key: `g:${g.id}`,
      label: g.name || `分区 ${g.id}`,
      disabled: true,
      children: []
    };
    list.push(node);
    byId.set(Number(g.id), node);
    if (g.name) byName.set(g.name, node);
  }

  const ungrouped: Node = { key: "g:0", label: props.ungroupedLabel, disabled: true, children: [] };

  for (const raw of props.terminals ?? []) {
    const t = normalize(raw);
    if (!t.id) continue;
    let b = t.groupId ? byId.get(t.groupId) : undefined;
    if (!b && t.group) b = byName.get(t.group);
    if (!b) b = ungrouped;
    b.children!.push({
      key: t.id,
      label: `${t.name || "终端 " + t.id}${t.netstate === 1 ? "" : "（离线）"}`,
      terminalId: t.id
    });
  }

  list.sort((a, b) => String(a.label).localeCompare(String(b.label), "zh"));
  list.push(ungrouped);
  // 「不指定」放最前，且不属于任何分区
  if (props.allowEmpty) list.unshift({ key: 0, label: props.emptyLabel });
  return list;
});

/**
 * 真正传给 el-tree-select 的值。
 *
 * ⚠ 0 在库里表示「未选/不指定」，但树里**没有** 0 这个节点
 *   （除非 allowEmpty 显式加了一个）。直接把 0 传下去，el-tree-select
 *   找不到匹配节点，就会把裸的「0」显示在框里，占位符也不出现。
 *   现网实测过这个现象：北斗校时和采播源终端两处都是这样。
 */
const shown = computed(() => {
  const v = props.modelValue;
  if (v === undefined || v === null) return undefined;
  if (v === 0 && !props.allowEmpty) return undefined;
  return v;
});

/** 分组节点已 disabled，正常选不中；万一选中也挡掉 */
const onPick = (v: any) => {
  if (typeof v === "string" && v.startsWith("g:")) return;
  emit("update:modelValue", v === undefined || v === null ? undefined : Number(v));
};
</script>

<style scoped>
.fill {
  width: 100%;
}
</style>
