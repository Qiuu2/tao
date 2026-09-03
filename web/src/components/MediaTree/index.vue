<!--
  媒体选择树 —— 按媒体库分组挑音频文件。

  # 为什么要有这个组件

  ok112 建任务时选媒体用的是一棵树（set_task_quickplay.html 里的 treeofbox）：
  媒体库是枝、音频文件是叶，勾选叶子。新 Web 之前一律用扁平 el-select 加远程
  搜索，几百个文件拉成一条长列表，看不出哪个文件在哪个库里 —— 与旧版差得远，
  也不好用。这里把树补回来。

  # 数据怎么来

  文件夹树走 /api/folders/tree?scene=taskPicker；每个文件夹下的媒体在展开时
  才按 folderId 拉（懒加载）。一次性把所有库的文件全拉下来在几千条媒体的现网
  上会很慢，而实际每次只会展开一两个库。

  # 一个容易踩的坑

  el-tree 的 setCheckedKeys 会连带勾上父节点。文件夹节点不是可选项，
  所以取值一律用 getCheckedNodes(true)（true = 只要叶子），并且只认带
  mediaId 的节点 —— 与 TerminalTree 同样的处理。
-->
<template>
  <div class="mt-wrap">
    <div class="mt-bar">
      <el-input v-model="keyword" placeholder="搜索媒体名称" clearable size="small" :prefix-icon="Search" @input="onSearch" />
      <el-button size="small" link @click="clearAll">清空</el-button>
    </div>

    <el-tree
      ref="treeRef"
      v-loading="loading"
      class="mt-tree"
      :data="nodes"
      :props="{ label: 'label', children: 'children', isLeaf: 'isLeaf' }"
      node-key="key"
      show-checkbox
      check-on-click-node
      :expand-on-click-node="false"
      :default-checked-keys="checkedKeys"
      :filter-node-method="filterNode"
      :style="{ height }"
      lazy
      :load="loadNode"
      @check="onCheck"
    >
      <template #default="{ data }">
        <span class="mt-node">
          <el-icon v-if="!data.isLeaf"><Folder /></el-icon>
          <span class="mt-label">{{ data.label }}</span>
          <span v-if="!data.isLeaf && data.mediaCount" class="mt-count">{{ data.mediaCount }}</span>
        </span>
      </template>
    </el-tree>
    <p class="mt-sum">已选 {{ modelValue.length }} 个媒体文件</p>
  </div>
</template>

<script setup lang="ts" name="MediaTree">
import { Folder, Search } from "@element-plus/icons-vue";
import { nextTick, onMounted, ref, watch } from "vue";

import { getFolderTreeApi, getMediaListApi } from "@/api/modules/media";

interface MediaNode {
  key: string;
  label: string;
  isLeaf: boolean;
  mediaId?: number;
  folderId?: number;
  mediaCount?: number;
  children?: MediaNode[];
}

const props = withDefaults(
  defineProps<{
    /** 选中的媒体 id */
    modelValue: number[];
    height?: string;
    /**
     * 回填时用来补名字。树是懒加载的，没展开过的库里的文件不在树上，
     * 光有 id 显示不出名字，所以调用方把已选项的名字一并传进来。
     */
    selectedNames?: { mediaId: number; name: string }[];
  }>(),
  { height: "260px", selectedNames: () => [] }
);

const emit = defineEmits<{ (e: "update:modelValue", v: number[]): void }>();

const treeRef = ref();
const keyword = ref("");
const loading = ref(false);
const nodes = ref<MediaNode[]>([]);
const checkedKeys = ref<string[]>([]);
/** 叶子节点缓存：懒加载过的库，其文件留在这里，勾选状态才不会因折叠而丢 */
const leafCache = new Map<number, MediaNode[]>();

const folderKey = (id: number) => `f:${id}`;
const mediaKey = (id: number) => `m:${id}`;

/** 把接口的文件夹树转成 el-tree 的节点（只放枝，叶子等展开时再拉） */
const toFolderNodes = (list: any[]): MediaNode[] =>
  (list ?? []).map(f => ({
    key: folderKey(f.id),
    label: f.name,
    isLeaf: false,
    folderId: f.id,
    mediaCount: f.mediaCount,
    children: toFolderNodes(f.children ?? [])
  }));

const loadTree = async () => {
  loading.value = true;
  try {
    const { data } = await getFolderTreeApi("taskPicker");
    nodes.value = toFolderNodes(data.tree);
  } finally {
    loading.value = false;
  }
};

/**
 * 懒加载：展开一个媒体库时才去拉它下面的文件。
 *
 * ⚠ el-tree 的 lazy 模式下根节点也会走这里（node.level === 0），
 *   此时要把已经拉好的 nodes 交回去，否则树是空的。
 */
const loadNode = async (node: any, resolve: (data: MediaNode[]) => void) => {
  if (node.level === 0) return resolve(nodes.value);
  const data = node.data as MediaNode;
  if (data.isLeaf || data.folderId === undefined) return resolve([]);

  // 子文件夹先摆上，再追加本层的媒体文件
  const subFolders = data.children ?? [];
  const cached = leafCache.get(data.folderId);
  if (cached) return resolve([...subFolders, ...cached]);

  const { data: page } = await getMediaListApi({ folderId: data.folderId, pageNum: 1, pageSize: 500 });
  const leaves: MediaNode[] = (page.list ?? []).map((m: any) => ({
    key: mediaKey(m.id),
    label: m.name,
    isLeaf: true,
    mediaId: m.id
  }));
  leafCache.set(data.folderId, leaves);
  resolve([...subFolders, ...leaves]);
  // 新挂上的叶子要把已选状态补回去
  await nextTick();
  syncChecked();
};

/** 取值只认叶子，且必须带 mediaId —— 文件夹节点不是可选项 */
const onCheck = () => {
  const picked = (treeRef.value?.getCheckedNodes(true) ?? []) as MediaNode[];
  emit(
    "update:modelValue",
    picked.filter(n => n.mediaId !== undefined).map(n => n.mediaId as number)
  );
};

const syncChecked = () => {
  checkedKeys.value = props.modelValue.map(mediaKey);
  treeRef.value?.setCheckedKeys(checkedKeys.value, true);
};

const clearAll = () => {
  treeRef.value?.setCheckedKeys([], true);
  emit("update:modelValue", []);
};

// el-tree 的 FilterNodeMethodFunction 给的是宽松的 TreeNodeData，这里自己收窄
const filterNode = (value: string, data: any) => {
  if (!value) return true;
  // 文件夹一律留着，否则搜出来的文件没有落脚的枝
  if (!data.isLeaf) return true;
  return String(data.label ?? "")
    .toLowerCase()
    .includes(value.toLowerCase());
};

const onSearch = () => treeRef.value?.filter(keyword.value);

watch(
  () => props.modelValue,
  () => nextTick(syncChecked)
);

onMounted(loadTree);
</script>

<style scoped lang="scss">
.mt-wrap {
  width: 100%;
  border: 1px solid var(--el-border-color-light);
  border-radius: 4px;
}
.mt-bar {
  display: flex;
  gap: 8px;
  align-items: center;
  padding: 6px 8px;
  border-bottom: 1px solid var(--el-border-color-lighter);
}
.mt-tree {
  overflow: auto;
}
.mt-node {
  display: flex;
  gap: 4px;
  align-items: center;
  min-width: 0;
}
.mt-label {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.mt-count {
  flex: none;
  padding: 0 5px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
  background: var(--el-fill-color-light);
  border-radius: 8px;
}
.mt-sum {
  padding: 5px 10px;
  margin: 0;
  font-size: 12px;
  color: var(--el-text-color-secondary);
  border-top: 1px solid var(--el-border-color-lighter);
}
</style>
