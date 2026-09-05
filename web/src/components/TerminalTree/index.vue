<!--
  终端选择树 —— 全站选终端的地方统一用它（终端管理页除外）。

  # 为什么要有这个组件

  改之前全站有 15 处选终端的地方，除「音乐下发」外全是扁平 el-select：
  几百台终端拉成一条长列表，看不出哪台在哪个分区。现在一律换成按
  **终端分区**分组的两层树，一眼看得出结构。

  # 用法

  多选（默认）：v-model 绑一个 id 数组
    <TerminalTree v-model="ids" :terminals="list" :loading="loading" @search="reload" />

  单选：加 :multiple="false"，v-model 绑一个 id（或 undefined）
    <TerminalTree v-model="id" :multiple="false" :terminals="list" />

  # 为什么要 normalize

  七个终端接口的字段名互不一致 —— 主键有的叫 id 有的叫 terminalId，
  名称有的叫 name 有的叫 terminalname。与其逐个改接口（会牵动一堆调用方），
  不如在这里做一层适配：认这几种写法中的任意一种。
  详见下面 normalize() 的注释。

  # 一个容易踩的坑

  el-tree 的 setCheckedKeys 会**连带**勾上父节点/子节点。分组节点是虚拟的
  （key 形如 g:xxx），不能混进选中结果里，所以取值时一律用
  getCheckedNodes(true)（true = 只要叶子），并且只认带 terminalId 的节点。
-->
<template>
  <div class="tt-wrap">
    <div v-if="searchable || multiple" class="tt-bar">
      <el-input
        v-if="searchable"
        v-model="keyword"
        placeholder="搜索终端名称"
        clearable
        size="small"
        :prefix-icon="Search"
        @input="onSearch"
      />
      <span v-else class="tt-spacer"></span>
      <template v-if="multiple">
        <el-button size="small" link type="primary" @click="checkAll(true)">全选</el-button>
        <el-button size="small" link @click="checkAll(false)">清空</el-button>
      </template>
    </div>

    <el-tree
      ref="treeRef"
      v-loading="loading"
      class="tt-tree"
      :data="nodes"
      :props="{ label: 'label', children: 'children' }"
      node-key="key"
      :show-checkbox="multiple"
      :check-on-click-node="multiple"
      :expand-on-click-node="false"
      :highlight-current="!multiple"
      :default-expanded-keys="expandedKeys"
      :empty-text="loading ? '加载中…' : '没有可选的终端'"
      @check="emitChecked"
      @node-click="onNodeClick"
    >
      <template #default="{ data }">
        <span class="tt-node">
          <span class="tt-label">{{ data.label }}</span>
          <span v-if="data.terminalId" class="tt-meta">
            <!--
              分区/通道勾选。照 ok112：终端型号的 switchcount ≥ 2 时才有这一项
              （get_terminaltype.php 查的就是 terminaltype.switchcount，
              addterminalfunctionplay.html 里的判据是 channelnum%2==0 && channelnum!=0，
              现网所有型号的 switchcount 不是 0/1 就是 4/8/10/16，两者等价）。
            -->
            <el-button
              v-if="zonePickable && data.switchCount >= 2"
              link
              type="primary"
              size="small"
              class="tt-zone"
              @click.stop="openZone(data)"
            >
              {{ zoneSummary(data) }}
            </el-button>
            <el-tag v-if="data.netstate === 1" type="success" size="small" effect="plain">在线</el-tag>
            <el-tag v-else type="info" size="small" effect="plain">离线</el-tag>
            <span v-if="data.sub" class="tt-sub">{{ data.sub }}</span>
          </span>
          <span v-else class="tt-count">{{ data.children?.length ?? 0 }} 台</span>
        </span>
      </template>
    </el-tree>

    <div v-if="multiple" class="tt-foot">已选 {{ (modelValue as number[])?.length || 0 }} 台</div>

    <!--
      分区勾选弹窗。旧版是点终端时在树旁边浮出来的一小块（div#lead），
      里面按 switchcount 排出「分区一…分区十六」的复选框，底下「确定 / 取消」。
      这里做成对话框：16 个复选框浮层里放不下，也挡不住树。
    -->
    <el-dialog v-model="zone.visible" :title="zone.title" width="420px" append-to-body>
      <div class="tt-zone-bar">
        <el-button size="small" link type="primary" @click="zoneAll(true)">全选</el-button>
        <el-button size="small" link @click="zoneAll(false)">清空</el-button>
      </div>
      <el-checkbox-group v-model="zone.checked" class="tt-zone-group">
        <el-checkbox v-for="(label, i) in zone.labels" :key="i" :value="i">{{ label }}</el-checkbox>
      </el-checkbox-group>
      <div class="tt-zone-note">不勾任何一项等于这台终端不参与播放，保存前请至少留一个分区。</div>
      <template #footer>
        <el-button @click="zone.visible = false">取消</el-button>
        <el-button type="primary" @click="submitZone">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="TerminalTree">
import { Search } from "@element-plus/icons-vue";
import { computed, nextTick, onMounted, reactive, ref, watch } from "vue";

import { loadZones, type ZoneNode } from "./zones";

/** 组件内部用的统一形状。各页面的接口字段名不一致，由 normalize 抹平。 */
interface TermNode {
  key: string;
  label: string;
  /** 只有叶子节点有；分组节点没有，用来把分组从选中结果里排除掉 */
  terminalId?: number;
  netstate?: number;
  /** 型号 / IP 之类的次要信息，显示在名字右边 */
  sub?: string;
  disabled?: boolean;
  /** 分区/通道数（terminaltype.switchcount）。≥ 2 时这台终端可以逐分区勾选 */
  switchCount?: number;
  children?: TermNode[];
}

/**
 * 分区/通道的名字，逐个照 ok112 的 language/chinese.php：
 * zone_1..zone_6 是分区一~六，zone_7/zone_8 是**电源一 / 电源二**，
 * zone_9..zone_16 又回到分区九~十六。第 7、8 位不是分区，别顺手改成「分区七/八」。
 */
const ZONE_LABELS = [
  "分区一",
  "分区二",
  "分区三",
  "分区四",
  "分区五",
  "分区六",
  "电源一",
  "电源二",
  "分区九",
  "分区十",
  "分区十一",
  "分区十二",
  "分区十三",
  "分区十四",
  "分区十五",
  "分区十六"
];

/** terminaloftask.area 是 varchar(16)，旧版勾选后补 0 补满 16 位 */
const AREA_LEN = 16;

const props = withDefaults(
  defineProps<{
    /** 多选时是 number[]，单选时是 number | undefined */
    modelValue: number[] | number | undefined;
    /** 各接口原样返回的终端数组 */
    terminals: any[];
    multiple?: boolean;
    loading?: boolean;
    /**
     * 分区清单。**树的骨架由它决定**，不是从终端归纳出来的 ——
     * 这样一台终端都没有的分区也会作为空节点出现（与 ok112 一致）。
     * 不传时组件自己去 /api/zones/options 拉。
     */
    groups?: { id: number; name: string }[];
    /** 用哪个字段取分区名。默认 groupName；终端分区页要用 currentZoneName */
    groupField?: string;
    /** 用哪个字段取分区 id。默认 groupId；终端分区页要用 currentZoneId */
    groupIdField?: string;
    /** 没有分组时归到哪个节点下 */
    ungroupedLabel?: string;
    height?: string;
    /**
     * 显不显示搜索框。
     * ⚠ 有些接口是一次性全量返回、**不收 keyword**（比如用户绑定终端那个），
     *   摆一个搜出来没反应的框比没有框更糟，那种地方传 false。
     */
    searchable?: boolean;
    /**
     * 每台终端的分区/通道掩码（terminaloftask.area），形如 "1011000000000000"。
     * 传了这个属性就会在节点上显示「分区」入口，配合 update:areas 使用。
     * 没传的终端按后端默认（全通道）处理。
     */
    areas?: Record<number, string>;
  }>(),
  {
    multiple: true,
    loading: false,
    groupField: "groupName",
    groupIdField: "groupId",
    // 措辞照 ok112：language/chinese.php 的 No_group_terminal = "无分区终端"
    ungroupedLabel: "无分区终端",
    height: "300px",
    searchable: true
  }
);

const emit = defineEmits<{
  (e: "update:modelValue", v: any): void;
  (e: "update:areas", v: Record<number, string>): void;
  /** 关键字变化。父组件通常拿它去服务端重新查（各接口都支持 keyword） */
  (e: "search", kw: string): void;
}>();

const treeRef = ref();
const keyword = ref("");

/*
  分区清单。调用方没传 groups 时，组件自己去拉（结果在 zones.ts 里进程内缓存，
  全站 14 处树共用同一次请求）。
*/
const fetchedZones = ref<ZoneNode[]>([]);
const groups = computed(() => props.groups ?? fetchedZones.value);

onMounted(async () => {
  if (props.groups) return;
  fetchedZones.value = await loadZones();
});

/**
 * 抹平七个接口的字段差异。
 *
 * 主键：id / terminalId          （声场分区那个接口用的是 terminalId）
 * 名称：name / terminalname      （task/alarm/account 用 name，其余用 terminalname）
 * 分组：由 groupField 指定，默认 groupName
 *
 * ⚠ 不要改成「只认一种」再去改接口 —— 那七个接口各有别的调用方，
 *   改字段名会牵动一堆地方。适配放在这里，改动面最小。
 */
const normalize = (t: any) => ({
  id: Number(t.id ?? t.terminalId ?? 0),
  name: String(t.name ?? t.terminalname ?? "").trim(),
  group: cleanGroup(t[props.groupField]),
  groupId: Number(t[props.groupIdField] ?? 0),
  netstate: Number(t.netstate ?? 0),
  typeName: String(t.typeName ?? "").trim(),
  switchCount: Number(t.switchCount ?? 0),
  disabled: !!t.disabled
});

/**
 * 归一化分组名。
 *
 * ⚠ 后端对「没有分区」有两种写法：task/alarm 那两个接口在
 *   fillOptionGroupNames 里填的是字面量 "(未分区)"，其余接口返回空串。
 *   不处理的话同一棵树上会同时冒出「(未分区)」和「未分区」两个组。
 *   一律当成空，交给 ungroupedLabel 统一命名。
 */
const cleanGroup = (v: any) => {
  const s = String(v ?? "").trim();
  return s === "(未分区)" || s === "（未分区）" ? "" : s;
};

/*
  按 ok112 的口径建树（inc/common.php 的 get_terminallist5）：

      <tree>
        <item text="分区名" id="stream_1">          ← 遍历 serverplaystream **全表**
            <item text="终端名" id="stream_1::5"/>
        </item>
        <item text="无分区终端" id="stream_0">      ← 恒在最后
            <item text="终端名" id="stream_0::9"/>
        </item>
      </tree>

  ⚠ 关键在于**分区节点来自分区表，不是从终端身上归纳出来的**。
    先前的写法是遍历终端、按 groupName 分桶，于是「一台终端都没有的分区
    根本不会出现」—— 现网只有一个空分区「22」，结果树上只剩「无分区终端」
    一个节点，看着就像分组没做。ok112 是先把每个分区都摆出来（哪怕是空的），
    再往里填终端。这里跟它一致。
*/
const nodes = computed<TermNode[]>(() => {
  const list: TermNode[] = [];
  const byId = new Map<number, TermNode>();
  const byName = new Map<string, TermNode>();

  for (const g of groups.value) {
    const node: TermNode = { key: `g:${g.id}`, label: g.name || `分区 ${g.id}`, children: [] };
    list.push(node);
    byId.set(Number(g.id), node);
    if (g.name) byName.set(g.name, node);
  }

  // 无分区节点恒在最后，且**恒存在**（哪怕一台都没有）——
  // 与 ok112 略有出入：它在没有无分区终端时不输出这个节点。
  // 保留它是为了让「一台都没归位」和「归位完了」两种状态在界面上长得一样，
  // 不至于树的行数忽然变化。
  const ungrouped: TermNode = { key: "g:0", label: props.ungroupedLabel, children: [] };

  for (const raw of props.terminals ?? []) {
    const t = normalize(raw);
    if (!t.id) continue;

    // 先按 id 归，再按名字兜底。
    // ⚠ 按 id 优先：serverplaystream.name 上没有唯一索引（建索引属 DDL，红线禁止），
    //   两个同名分区按名字归会被并成一个。
    let bucket = t.groupId ? byId.get(t.groupId) : undefined;
    if (!bucket && t.group) bucket = byName.get(t.group);
    if (!bucket) bucket = ungrouped;

    // 次要信息只放型号，**不放 IP**。
    //
    // ok112 树上的叶子就是「终端名-型号名」（inc/common.php 的
    // get_terminallistoggroup2：`$terminal_row['terminalname']."-".$faname`），
    // 从来没有 IP。这里跟它一致 —— 挑终端时看的是「这是哪一台、什么型号」，
    // IP 只会把节点撑成两行；真要查 IP，终端列表里那一列一直都在。
    const sub = t.typeName;
    bucket.children!.push({
      key: `t:${t.id}`,
      label: t.name || `终端 ${t.id}`,
      terminalId: t.id,
      netstate: t.netstate,
      sub,
      switchCount: t.switchCount,
      disabled: t.disabled
    });
  }

  list.sort((a, b) => a.label.localeCompare(b.label, "zh"));
  list.push(ungrouped);
  return list;
});

/** 默认把有选中项的分组展开；一个都没选时展开全部（数量不多时） */
const expandedKeys = computed(() => nodes.value.map(n => n.key));

const selectedIds = computed<number[]>(() => {
  if (props.multiple) return (props.modelValue as number[]) ?? [];
  const v = props.modelValue as number | undefined;
  return v ? [v] : [];
});

/** 把外部传进来的选中值同步到树上 */
const syncToTree = async () => {
  await nextTick();
  if (!treeRef.value) return;
  if (props.multiple) {
    // ⚠ 只设叶子的 key。设分组 key 会把整组勾上。
    treeRef.value.setCheckedKeys(
      selectedIds.value.map(id => `t:${id}`),
      false
    );
  } else {
    const id = props.modelValue as number | undefined;
    treeRef.value.setCurrentKey(id ? `t:${id}` : null);
  }
};

watch(() => props.modelValue, syncToTree, { immediate: true });
watch(nodes, syncToTree);

/**
 * 取选中结果。
 *
 * ⚠ getCheckedNodes(true) 的 true 表示「只要叶子」。不加它会把
 *   分组节点也算进来，而分组节点没有 terminalId，会变成一堆 NaN。
 *   即便如此这里仍然再 filter 一次 terminalId，双保险。
 */
const emitChecked = () => {
  if (!props.multiple || !treeRef.value) return;
  const ids = (treeRef.value.getCheckedNodes(true) as TermNode[]).filter(n => !!n.terminalId).map(n => n.terminalId as number);
  emit("update:modelValue", ids);
};

const onNodeClick = (data: TermNode) => {
  if (props.multiple) return;
  // 单选：点分组节点只展开，不当作选择
  if (!data.terminalId) return;
  emit("update:modelValue", data.terminalId);
};

const checkAll = (on: boolean) => {
  if (!treeRef.value) return;
  const all = nodes.value.flatMap(g => g.children ?? []).filter(n => !n.disabled);
  treeRef.value.setCheckedKeys(on ? all.map(n => n.key) : [], false);
  emitChecked();
};

/* ---------------- 分区 / 通道勾选 ---------------- */

/*
  对应 ok112 挑终端时浮出来的那张勾选表（addterminalfunctionplay.html 里的 div#lead）：
  勾完点「确定」，结果拼成一串 0/1 写进 terminaloftask.area。

  ⚠ 旧版这条链路有个明显的对不上：前端 set_task_volume_prepose() 拼的是
    **逗号分隔**的 "1,0,1,0,…"，而 do.php 取的是 `substr($get_terminal,$j+1,16)`
    —— 16 个字符里有一半是逗号，落库就成了 "1,0,1,0,1,0,1,0"。
    库里现存的 area 全是纯 0/1 串（'11111111' 62 行、'1111111111111111' 8 行），
    也就是后台服务认的是纯掩码。所以这里按**每位一个字符**写，
    不复刻那个逗号 bug —— 复刻它等于写进去的值后台读不懂。
*/

/** 只有调用方传了 areas 才显示分区入口 —— 其它地方（用户绑终端等）用不上 */
const zonePickable = computed(() => props.multiple && props.areas !== undefined);

const zone = reactive({
  visible: false,
  title: "",
  terminalId: 0,
  labels: [] as string[],
  checked: [] as number[]
});

/** 没设过的终端按「全通道」显示：与后端默认一致 */
const maskOf = (data: TermNode) => {
  const raw = props.areas?.[data.terminalId as number];
  const n = data.switchCount ?? 0;
  if (!raw) return "1".repeat(Math.min(n, AREA_LEN)).padEnd(AREA_LEN, "0");
  return raw.padEnd(AREA_LEN, "0").slice(0, AREA_LEN);
};

const zoneSummary = (data: TermNode) => {
  const n = Math.min(data.switchCount ?? 0, AREA_LEN);
  const mask = maskOf(data);
  const on: string[] = [];
  for (let i = 0; i < n; i++) if (mask[i] === "1") on.push(ZONE_LABELS[i]);
  if (on.length === 0) return "分区：未选";
  if (on.length === n) return "分区：全部";
  return `分区：${on.join("、")}`;
};

const openZone = (data: TermNode) => {
  const n = Math.min(data.switchCount ?? 0, AREA_LEN);
  const mask = maskOf(data);
  zone.terminalId = data.terminalId as number;
  zone.title = `${data.label} · 分区选择`;
  zone.labels = ZONE_LABELS.slice(0, n);
  zone.checked = [];
  for (let i = 0; i < n; i++) if (mask[i] === "1") zone.checked.push(i);
  zone.visible = true;
};

const zoneAll = (on: boolean) => {
  zone.checked = on ? zone.labels.map((_, i) => i) : [];
};

const submitZone = () => {
  const mask = Array.from({ length: AREA_LEN }, (_, i) => (zone.checked.includes(i) ? "1" : "0")).join("");
  emit("update:areas", { ...(props.areas ?? {}), [zone.terminalId]: mask });
  zone.visible = false;
};

/*
  搜索交给父组件去服务端查（各接口都收 keyword）。
  ⚠ 不在前端过滤：这些接口都有 LIMIT（200~500），前端只拿到一页，
    本地过滤会让「搜得到但列表里没有」的终端永远找不出来。
*/
let timer: any;
const onSearch = () => {
  clearTimeout(timer);
  timer = setTimeout(() => emit("search", keyword.value.trim()), 300);
};
</script>

<style scoped lang="scss">
.tt-wrap {
  width: 100%;
}
.tt-bar {
  display: flex;
  gap: 8px;
  align-items: center;
  margin-bottom: 8px;
}
.tt-bar .el-input {
  flex: 1;
}
.tt-spacer {
  flex: 1;
}
.tt-tree {
  height: v-bind(height);
  overflow: auto;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 4px;
}
.tt-node {
  display: flex;
  flex: 1;
  gap: 8px;
  align-items: center;
  min-width: 0;
  padding-right: 8px;
}
.tt-label {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.tt-meta {
  display: flex;
  gap: 8px;
  align-items: center;
  margin-left: auto;
}
.tt-sub {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.tt-count {
  margin-left: auto;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.tt-foot {
  margin-top: 6px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.tt-zone {
  font-size: 12px;
}
.tt-zone-bar {
  display: flex;
  gap: 8px;
  margin-bottom: 6px;
}
.tt-zone-group {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 2px 12px;
  :deep(.el-checkbox) {
    margin-right: 0;
  }
}
.tt-zone-note {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.7;
  color: var(--el-text-color-secondary);
}
</style>
