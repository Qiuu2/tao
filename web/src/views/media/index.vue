<!--
  文件管理：左侧文件夹树 + 右侧媒体列表

  保留旧系统的「左树右表」操作范式（用户已形成肌肉记忆），并修复其若干缺陷：
    · 树支持任意深度（旧版三层写死，第 4 层看不见）
    · 游离节点（parentid 指向已删除目录）单独归类展示，不再凭空消失
    · 删除一律先展示影响面预览，旧版点一下就连锁删掉整棵子树
    · 「删除」在未勾选时禁用；清空目录拆为独立高危操作并需输入目录名确认
      （旧版未勾选时的行为就是清空整个目录，且不做任何校验）
    · 无权限时按钮禁用而非整页链接置灰，只读用户仍可试听与下载
-->
<template>
  <div class="media-container">
    <!-- 左：文件夹树 -->
    <div class="tree-panel">
      <div class="tree-header">
        <span class="tree-title">{{ treeData?.rootName || $t("menu.media") }}</span>
        <span>
          <el-button
            link
            :icon="Plus"
            :title="$t('media.newUnderFolder')"
            :disabled="!canCreateUnderCurrent"
            @click="openCreate"
          />
          <el-button link :icon="Refresh" :loading="treeLoading" :title="$t('common.refresh')" @click="loadTree" />
        </span>
      </div>
      <el-input
        v-model="treeFilter"
        :placeholder="$t('media.filterFolder')"
        clearable
        :prefix-icon="Search"
        class="tree-filter"
      />
      <el-scrollbar class="tree-scroll">
        <el-tree
          ref="treeRef"
          :data="treeNodes"
          node-key="id"
          :props="{ label: 'name', children: 'children' }"
          :filter-node-method="filterNode"
          :expand-on-click-node="false"
          :default-expanded-keys="expandedKeys"
          highlight-current
          @node-click="onNodeClick"
        >
          <template #default="{ data }">
            <span class="tree-node" @contextmenu.prevent="openContextMenu($event, data)">
              <el-icon><FolderOpened /></el-icon>
              <span class="node-name" :title="data.name">{{ data.name }}</span>
              <el-tag v-if="data.system" size="small" type="info" effect="plain">{{ $t("media.systemLabel") }}</el-tag>
              <el-tag v-else-if="!data.shared" size="small" type="warning" effect="plain">{{ $t("media.privateLabel") }}</el-tag>
              <span v-if="data.mediaCount > 0" class="node-count">{{ data.mediaCount }}</span>
            </span>
          </template>
        </el-tree>

        <!-- 游离目录：parentid 指向已不存在的父级。旧版会让它们彻底消失 -->
        <div v-if="treeData?.orphans?.length" class="orphan-block">
          <el-divider content-position="left">
            <span class="orphan-title">{{ $t("media.orphanFolder") }}</span>
          </el-divider>
          <el-tree
            :data="treeData.orphans"
            node-key="id"
            :props="{ label: 'name', children: 'children' }"
            :expand-on-click-node="false"
            highlight-current
            @node-click="onNodeClick"
          >
            <template #default="{ data }">
              <span class="tree-node" @contextmenu.prevent="openContextMenu($event, data)">
                <el-icon color="var(--el-color-warning)"><WarningFilled /></el-icon>
                <span class="node-name">{{ data.name }}</span>
                <span v-if="data.mediaCount > 0" class="node-count">{{ data.mediaCount }}</span>
              </span>
            </template>
          </el-tree>
        </div>
      </el-scrollbar>
      <div class="tree-tip">{{ $t("media.rightClickFolder") }}</div>
    </div>

    <!-- 右：媒体列表 -->
    <div class="table-panel">
      <ProTable
        ref="proTableRef"
        :key="currentFolderId"
        :columns="columns"
        :request-api="getMediaList"
        :init-param="initParam"
        :data-callback="dataCallback"
        row-key="id"
      >
        <template #tableHeader="scope">
          <div class="table-header-bar">
            <div class="table-header-info">
              <template v-if="folderInfo">
                <span class="cur-folder">{{ folderInfo.name }}</span>
                <el-tag size="small" effect="plain">{{ $t("common.totalSize", { size: folderInfo.totalSizeText }) }}</el-tag>
                <el-tag v-if="folderInfo.isRecordLibrary" size="small" type="warning" effect="plain">
                  {{ $t("media.recordLibNote") }}
                </el-tag>
                <el-tag v-if="scopeNote" size="small" type="info" effect="plain">{{ scopeNote }}</el-tag>
              </template>
              <span v-else class="cur-folder muted">{{ $t("media.pickLeftFolder") }}</span>
            </div>
            <div class="table-header-ops">
              <el-button
                type="primary"
                :icon="Upload"
                :disabled="!folderInfo?.canUpload"
                :title="uploadDisabledReason"
                @click="uploadVisible = true"
              >
                {{ $t("media.addMedia") }}
              </el-button>
              <!-- 未勾选时禁用。旧版此时的行为是清空整个目录，误点即灾难 -->
              <el-button
                type="danger"
                :icon="Delete"
                :disabled="!scope.isSelected"
                :title="$t('media.deleteSelected')"
                @click="onDeleteMedia(scope.selectedListIds)"
              >
                {{ $t("common.delete") }}{{ scope.selectedListIds.length ? `(${scope.selectedListIds.length})` : "" }}
              </el-button>
              <el-button
                type="danger"
                plain
                :icon="DeleteFilled"
                :disabled="!folderInfo || folderInfo.totalSizeKB === 0"
                @click="openClear"
              >
                {{ $t("media.clearFolderBtn") }}
              </el-button>
            </div>
          </div>
        </template>

        <template #operation="scope">
          <el-button type="primary" link :icon="VideoPlay" @click="onPreview(scope.row)">{{ $t("media.preview") }}</el-button>
          <el-button type="primary" link :icon="Download" @click="onDownload(scope.row)">{{ $t("media.download") }}</el-button>
        </template>
      </ProTable>
    </div>

    <!-- 右键菜单 -->
    <div v-show="ctx.visible" class="ctx-menu" :style="{ left: ctx.x + 'px', top: ctx.y + 'px' }">
      <div :class="['ctx-item', { disabled: !ctx.node?.canCreateChild }]" @click="ctx.node?.canCreateChild && openCreate()">
        <el-icon><Plus /></el-icon> {{ $t("media.newSub") }}
        <span v-if="ctx.node && !ctx.node.canCreateChild" class="ctx-why">{{ $t("media.depthReached") }}</span>
      </div>
      <div :class="['ctx-item', { disabled: !ctx.node?.canModify }]" @click="ctx.node?.canModify && openEdit()">
        <el-icon><EditPen /></el-icon> {{ $t("media.renameShare") }}
        <span v-if="ctx.node?.system" class="ctx-why">{{ $t("media.systemPreset") }}</span>
      </div>
      <div :class="['ctx-item danger', { disabled: !ctx.node?.canDelete }]" @click="ctx.node?.canDelete && onDeleteFolder()">
        <el-icon><Delete /></el-icon> {{ $t("taskCommon.deleteFolder") }}
        <span v-if="ctx.node?.system" class="ctx-why">{{ $t("media.systemPreset") }}</span>
      </div>
    </div>

    <!-- 新建 / 编辑目录 -->
    <el-dialog
      v-model="folderDlg.visible"
      :title="folderDlg.isEdit ? $t('media.editFolder') : $t('media.newFolder')"
      width="440px"
    >
      <el-form label-width="90px">
        <el-form-item v-if="!folderDlg.isEdit" :label="$t('media.parentFolder')">
          <el-input :model-value="folderDlg.parentName" disabled />
        </el-form-item>
        <el-form-item :label="$t('common.name')" required>
          <el-input v-model="folderDlg.name" maxlength="60" show-word-limit :placeholder="$t('media.folderNamePlaceholder')" />
        </el-form-item>
        <!--
          「是否共享」只在**修改**时给 —— 右键菜单那一项本来就叫「重命名 / 共享设置」。
          新建时不问：ok112 的 filefolderadd.html 虽然摆了个勾选框，但 do.php 里
          未勾选就是 0，新目录默认私有；建的时候还没内容，共享与否没什么可决定的，
          需要共享再进「修改」改一次。
        -->
        <el-form-item v-if="folderDlg.isEdit" :label="$t('media.isShared')">
          <el-switch v-model="folderDlg.shared" :active-text="$t('media.shared')" :inactive-text="$t('media.private')" />
        </el-form-item>
      </el-form>
      <div v-if="folderDlg.isEdit" class="dlg-tip">
        {{ $t("media.sharedVisibility") }}<br />
        {{ $t("media.filterNote") }}
      </div>
      <div v-else class="dlg-tip">
        {{ $t("media.newFolderDefault") }}<b>{{ $t("media.privateLabel") }}</b
        >{{ $t("media.onlyMe") }}
      </div>
      <template #footer>
        <el-button @click="folderDlg.visible = false">{{ $t("common.cancel") }}</el-button>
        <el-button type="primary" :loading="folderDlg.loading" @click="submitFolder">{{ $t("common.confirm") }}</el-button>
      </template>
    </el-dialog>

    <!-- 删除影响面预览 -->
    <el-dialog v-model="delDlg.visible" :title="delDlg.title" width="560px">
      <div v-if="delDlg.deletable.length">
        <el-alert type="warning" :closable="false" show-icon class="mb10">
          {{ $t("media.willBe") }}<b>{{ $t("media.permanentDelete") }}</b
          >{{ $t("media.notRecoverableColon") }}
        </el-alert>
        <el-table :data="delDlg.deletable" size="small" border max-height="240">
          <el-table-column prop="name" :label="$t('common.name')" />
          <el-table-column v-if="delDlg.kind === 'folder'" :label="$t('media.impactScope')" width="200">
            <template #default="s"> 子目录 {{ s.row.descendantFolders }} 个 · 媒体 {{ s.row.mediaCount }} 个 </template>
          </el-table-column>
        </el-table>
      </div>
      <div v-if="delDlg.blocked.length" class="mt10">
        <el-alert type="error" :closable="false" show-icon class="mb10">
          {{ $t("media.following") }}<b>{{ $t("media.cannotDelete") }}</b
          >：
        </el-alert>
        <el-table :data="delDlg.blocked" size="small" border max-height="200">
          <el-table-column prop="name" :label="$t('common.name')" />
          <el-table-column :label="$t('media.reason')">
            <template #default="s">
              {{ blockReasonText(s.row.reason) }}
              <span v-if="s.row.refName">（{{ s.row.refName }}）</span>
              <span v-else-if="s.row.detail">（{{ s.row.detail }}）</span>
            </template>
          </el-table-column>
        </el-table>
      </div>
      <div v-if="!delDlg.deletable.length && !delDlg.blocked.length" class="muted">{{ $t("media.nothingToDo") }}</div>
      <template #footer>
        <el-button @click="delDlg.visible = false">{{ $t("common.cancel") }}</el-button>
        <el-button type="danger" :disabled="!delDlg.deletable.length" :loading="delDlg.loading" @click="confirmDelete">
          {{ $t("common.confirmDelete") }}
        </el-button>
      </template>
    </el-dialog>

    <!-- 清空目录（高危，需输入目录名） -->
    <el-dialog v-model="clearDlg.visible" :title="$t('media.clearFolder')" width="480px">
      <el-alert type="error" :closable="false" show-icon class="mb10">
        此操作会删除「{{ folderInfo?.name }}」下的<b>{{ $t("media.allMediaAndFiles") }}</b
        >{{ $t("media.notRecoverableComma") }}<br />
        {{ $t("media.skipReferenced") }}
      </el-alert>
      <el-form label-width="110px">
        <el-form-item :label="$t('media.folderNamePrompt')">
          <el-input v-model="clearDlg.confirmText" :placeholder="folderInfo?.name" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="clearDlg.visible = false">{{ $t("common.cancel") }}</el-button>
        <el-button
          type="danger"
          :disabled="clearDlg.confirmText !== folderInfo?.name"
          :loading="clearDlg.loading"
          @click="confirmClear"
        >
          {{ $t("media.confirmClear") }}
        </el-button>
      </template>
    </el-dialog>

    <!--
      添加媒体（:80 的「添加媒体」弹窗：所属文件夹 / 媒体文件）。

      # 为什么是一张表

      原来是一个拖拽框加**一条总进度条**。多传几个文件时那条总进度条什么也说不清：
      看不出在传哪一个、哪一个已经好了、哪一个失败了；而且字节一发完它就卡在
      100%，后面服务端逐个转码的那段时间里，整个界面是死的。

      现在一行一个文件，各自一条进度条，各自报自己的状态。下面的「添加媒体」
      按钮继续往表里加，确定之后**一个一个传**（见 doUpload 的注释）。

      ⚠ 播放时长要等这一行真的传完才有 —— 它是服务端在转码产物上数 MP3 帧
        算出来的，不是浏览器能预先知道的东西。所以传完之前那一格是「—」。
    -->
    <el-drawer
      v-model="uploadVisible"
      :title="$t('media.addMedia')"
      size="720px"
      destroy-on-close
      :close-on-click-modal="!uploading"
      @close="onUploadClose"
    >
      <el-form label-width="90px">
        <el-form-item :label="$t('media.belongFolder')">
          <!-- 目录在左树里选，这里只读回显，避免两处可改导致传到别的目录去 -->
          <el-input :model-value="folderInfo?.name ?? ''" readonly />
        </el-form-item>
      </el-form>

      <el-table :data="rows" size="small" border max-height="46vh" class="up-table">
        <el-table-column type="index" :label="$t('common.index')" width="55" align="center" />
        <el-table-column :label="$t('taskCommon.mediaName')" min-width="170" show-overflow-tooltip>
          <template #default="s">
            <div class="up-name">{{ s.row.name }}</div>
            <div class="up-size">{{ fmtBytes(s.row.size) }}</div>
          </template>
        </el-table-column>
        <el-table-column :label="$t('media.uploadProgress')" min-width="230">
          <template #default="s">
            <el-progress :percentage="s.row.percent" :status="progressStatus(s.row)" :stroke-width="12" text-inside />
            <p class="up-progress-text" :class="{ bad: s.row.phase === 'failed' }">{{ rowText(s.row) }}</p>
          </template>
        </el-table-column>
        <!--
          播放时长：服务端返回的 timelengthText。没传完之前没有这个值，
          编不出来也不该编 —— 显示「—」，比放一个占位数字诚实。
        -->
        <el-table-column :label="$t('taskCommon.playLength')" width="100" align="center">
          <template #default="s">
            <span v-if="s.row.timeText">{{ s.row.timeText }}</span>
            <span v-else class="muted">—</span>
          </template>
        </el-table-column>
        <el-table-column :label="$t('common.operation')" width="70" align="center">
          <template #default="s">
            <el-button type="danger" link :disabled="uploading" @click="dropRow(s.$index)">
              {{ $t("common.remove") }}
            </el-button>
          </template>
        </el-table-column>
      </el-table>
      <p v-if="!rows.length" class="dlg-note">{{ $t("media.noFilePicked") }}</p>

      <!--
        「添加媒体」：只负责往上面那张表里加行，不上传。
        :show-file-list="false" —— 文件清单已经是那张表了，el-upload 自己再列一遍是重复。
      -->
      <div class="up-add">
        <el-upload
          ref="uploadRef"
          multiple
          :auto-upload="false"
          :show-file-list="false"
          accept=".mp3,.wav"
          :on-change="onFileChange"
        >
          <el-button :icon="Plus" :disabled="uploading">{{ $t("media.addMedia") }}</el-button>
        </el-upload>
      </div>

      <el-alert type="info" :closable="false" show-icon class="mb10">
        {{ $t("media.uploadLimit") }}<br />
        {{ $t("media.bitrateNote") }} <b>{{ $t("media.bitrateTarget") }}</b
        >{{ $t("media.sampleRateNote") }}<br />
        {{ $t("media.sameNameWill") }}<b>{{ $t("media.overwrite") }}</b
        >{{ $t("media.existingMedia") }}
      </el-alert>

      <div class="mt10">
        <el-button :disabled="uploading" @click="uploadVisible = false">{{ $t("common.cancel") }}</el-button>
        <el-button type="primary" :loading="uploading" :disabled="!pendingCount" @click="doUpload">
          {{ $t("common.confirm") }}{{ pendingCount ? $t("sys.nFiles", { n: pendingCount }) : "" }}
        </el-button>
      </div>
    </el-drawer>

    <!-- 试听 -->
    <el-dialog v-model="playerVisible" :title="playing?.name" width="460px" @close="stopAudio">
      <audio v-if="playing" ref="audioRef" :src="withToken(playing.streamUrl)" controls autoplay style="width: 100%" />
      <div class="player-meta">
        {{ playing?.typeid }} · {{ playing?.sizeText }} · {{ playing?.bitrateText }} · {{ playing?.timelengthText }}
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="tsx" name="mediaManage">
import { useI18n } from "vue-i18n";
import {
  Delete,
  DeleteFilled,
  Download,
  EditPen,
  FolderOpened,
  Plus,
  Refresh,
  Search,
  Upload,
  VideoPlay,
  WarningFilled
} from "@element-plus/icons-vue";
import { ElMessage, ElNotification, type UploadUserFile } from "element-plus";
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, watch } from "vue";

import {
  blockReasonText,
  clearFolderMediaApi,
  createFolderApi,
  deleteFolderApi,
  deleteMediaApi,
  FolderNode,
  FolderTree,
  getFolderTreeApi,
  getMediaListApi,
  MediaFolderInfo,
  MediaItem,
  previewDeleteFolderApi,
  previewDeleteMediaApi,
  updateFolderApi
} from "@/api/modules/media";
import ProTable from "@/components/ProTable/index.vue";
import { ColumnProps } from "@/components/ProTable/interface";
import { useGlobalStore } from "@/stores/modules/global";
import { useUserStore } from "@/stores/modules/user";

// 脚本里拼的文案用 t()；模板里的 $t 不用引入
const { t } = useI18n();

const userStore = useUserStore();
const globalStore = useGlobalStore();

const treeRef = ref();
const proTableRef = ref();
const audioRef = ref<HTMLAudioElement>();

const treeData = ref<FolderTree>();
const treeNodes = computed(() => treeData.value?.tree ?? []);
const treeLoading = ref(false);
const treeFilter = ref("");
const expandedKeys = ref<number[]>([]);

const currentFolderId = ref<number>(0);
const currentNode = ref<FolderNode>();
const folderInfo = ref<MediaFolderInfo>();
const scopeNote = ref("");

const playerVisible = ref(false);
const playing = ref<MediaItem>();

const initParam = reactive({ folderId: 0 });

const canCreateUnderCurrent = computed(() => !!currentNode.value?.canCreateChild);
const uploadDisabledReason = computed(() => {
  if (!folderInfo.value) return t("media.pickFolder");
  if (folderInfo.value.isRecordLibrary) return t("media.recordNoUpload");
  if (!folderInfo.value.canUpload) return t("media.noPermissionOrStandby");
  return "";
});

const columns = reactive<ColumnProps<MediaItem>[]>([
  { type: "selection", fixed: "left", width: 55 },
  // 列名严格照 :80（页面规格.txt「文件管理」）：媒体名称 | 媒体大小 | 媒体类型 | 媒体比特率 | 播放时长 | 操作。
  // ID 列 :80 没有，这里也去掉 —— 需要 id 的地方（试听/下载/删除）都走行对象，不靠肉眼抄。
  {
    prop: "name",
    label: t("taskCommon.mediaName"),
    search: { el: "input", key: "keyword", props: { placeholder: t("media.mediaNameRequired") } }
  },
  { prop: "sizeText", label: t("taskCommon.mediaSize"), width: 110 },
  // 旧版的搜索是一个「选择类型…」下拉 +一个关键字框，可选「媒体名称 / 媒体类型」。
  // 这里做成两个并列的搜索框，两路条件可以同时生效，比旧版那个二选一好用。
  {
    prop: "typeid",
    label: t("media.mediaType"),
    width: 100,
    search: { el: "input", key: "typeid", props: { placeholder: t("media.egMp3Wav") } }
  },
  { prop: "bitrateText", label: t("media.bitrate"), width: 120 },
  { prop: "timelengthText", label: t("taskCommon.playLength"), width: 120 },
  { prop: "operation", label: t("common.operation"), width: 170, fixed: "right" }
]);

const getMediaList = (params: any) => {
  if (!params.folderId) {
    return Promise.resolve({ data: { list: [], total: 0, pageNum: 1, pageSize: 18 } } as any);
  }
  return getMediaListApi(params);
};

const dataCallback = (data: any) => {
  folderInfo.value = data.folder;
  scopeNote.value = data.scopeNote || "";
  return data;
};

const refreshTable = () => proTableRef.value?.getTableList?.();

/* ---------------- 树 ---------------- */

const loadTree = async () => {
  treeLoading.value = true;
  try {
    const { data } = await getFolderTreeApi("manage");
    treeData.value = data;
    expandedKeys.value = (data.tree ?? []).map(n => n.id);

    if (currentFolderId.value) {
      // 保持当前选中项；若它已被删除则回退到第一个顶层目录
      const still = findNode(data.tree, currentFolderId.value) || findNode(data.orphans, currentFolderId.value);
      if (still) {
        currentNode.value = still;
        nextTick(() => treeRef.value?.setCurrentKey?.(still.id));
      } else if (data.tree?.length) {
        selectFolder(data.tree[0]);
      }
    } else {
      const first = findFirstWithMedia(data.tree) ?? data.tree?.[0];
      if (first) selectFolder(first);
    }
  } finally {
    treeLoading.value = false;
  }
};

const findNode = (nodes: FolderNode[] | undefined, id: number): FolderNode | undefined => {
  for (const n of nodes ?? []) {
    if (n.id === id) return n;
    const hit = findNode(n.children, id);
    if (hit) return hit;
  }
  return undefined;
};

const findFirstWithMedia = (nodes?: FolderNode[]): FolderNode | undefined => {
  for (const n of nodes ?? []) {
    if (n.mediaCount > 0) return n;
    const hit = findFirstWithMedia(n.children);
    if (hit) return hit;
  }
  return undefined;
};

const selectFolder = (node: FolderNode) => {
  currentFolderId.value = node.id;
  currentNode.value = node;
  initParam.folderId = node.id;
  nextTick(() => treeRef.value?.setCurrentKey?.(node.id));
};

const onNodeClick = (node: FolderNode) => selectFolder(node);

const filterNode = (value: string, data: any) => {
  if (!value) return true;
  return String((data as FolderNode).name ?? "").includes(value);
};

watch(treeFilter, v => treeRef.value?.filter(v));

/* ---------------- 右键菜单 ---------------- */

const ctx = reactive<{ visible: boolean; x: number; y: number; node?: FolderNode }>({
  visible: false,
  x: 0,
  y: 0
});

const openContextMenu = (e: MouseEvent, node: FolderNode) => {
  selectFolder(node);
  ctx.node = node;
  ctx.x = e.clientX;
  ctx.y = e.clientY;
  ctx.visible = true;
};
const closeContextMenu = () => (ctx.visible = false);
onMounted(() => document.addEventListener("click", closeContextMenu));
onUnmounted(() => document.removeEventListener("click", closeContextMenu));

/* ---------------- 新建 / 编辑目录 ---------------- */

const folderDlg = reactive({
  visible: false,
  isEdit: false,
  id: 0,
  name: "",
  shared: false,
  parentName: "",
  loading: false
});

const openCreate = () => {
  const parent = ctx.node ?? currentNode.value;
  if (!parent) return ElMessage.warning(t("media.pickParentFolder"));
  if (!parent.canCreateChild) return ElMessage.warning(t("media.depthLimit"));
  Object.assign(folderDlg, {
    visible: true,
    isEdit: false,
    id: 0,
    name: "",
    shared: false,
    parentName: parent.name,
    loading: false
  });
  ctx.visible = false;
};

const openEdit = () => {
  const node = ctx.node ?? currentNode.value;
  if (!node) return;
  Object.assign(folderDlg, {
    visible: true,
    isEdit: true,
    id: node.id,
    name: node.name,
    shared: node.shared,
    parentName: "",
    loading: false
  });
  ctx.visible = false;
};

const submitFolder = async () => {
  const name = folderDlg.name.trim();
  if (!name) return ElMessage.warning(t("media.folderNameRequired"));
  folderDlg.loading = true;
  try {
    if (folderDlg.isEdit) {
      await updateFolderApi(folderDlg.id, { name, shared: folderDlg.shared });
      ElMessage.success(t("common.updateSuccess"));
    } else {
      const parent = currentNode.value!;
      // 新建不问共享，一律私有（与 ok112 未勾选时的取值一致）
      await createFolderApi({ name, parentId: parent.id, shared: false });
      ElMessage.success(t("common.createSuccess"));
    }
    folderDlg.visible = false;
    await loadTree();
  } finally {
    folderDlg.loading = false;
  }
};

/* ---------------- 删除（统一走影响面预览） ---------------- */

const delDlg = reactive<{
  visible: boolean;
  kind: "folder" | "media";
  title: string;
  deletable: any[];
  blocked: any[];
  ids: number[];
  loading: boolean;
}>({ visible: false, kind: "folder", title: "", deletable: [], blocked: [], ids: [], loading: false });

const onDeleteFolder = async () => {
  const node = ctx.node ?? currentNode.value;
  if (!node) return;
  ctx.visible = false;
  const { data } = await previewDeleteFolderApi([node.id]);
  Object.assign(delDlg, {
    visible: true,
    kind: "folder",
    title: t("media.deleteFolderTitle"),
    deletable: data.deletable ?? [],
    blocked: data.blocked ?? [],
    ids: (data.deletable ?? []).map(d => d.id),
    loading: false
  });
};

// ProTable 的 selectedListIds 是 string[]，这里统一转成数字再发给后端
const onDeleteMedia = async (rawIds: (string | number)[]) => {
  const ids = (rawIds ?? []).map(Number).filter(n => Number.isFinite(n) && n > 0);
  if (!ids.length) return ElMessage.warning(t("media.pickMediaToDelete"));
  const { data } = await previewDeleteMediaApi(ids);
  Object.assign(delDlg, {
    visible: true,
    kind: "media",
    title: t("media.deleteMedia"),
    deletable: data.deletable ?? [],
    blocked: data.blocked ?? [],
    ids: (data.deletable ?? []).map(d => d.id),
    loading: false
  });
};

const confirmDelete = async () => {
  delDlg.loading = true;
  try {
    if (delDlg.kind === "folder") {
      const { data } = await deleteFolderApi(delDlg.ids);
      ElNotification({
        title: t("common.deleteDone"),
        message: t("media.deletedFoldersAndMedia", {
          folders: data.deletedFolders?.length ?? 0,
          media: data.deletedMediaCount ?? 0
        }),
        type: "success"
      });
      currentFolderId.value = 0;
      await loadTree();
    } else {
      const { data } = await deleteMediaApi(delDlg.ids);
      ElNotification({
        title: t("common.deleteDone"),
        message: t("media.deletedMedia", { n: data.deletedCount ?? 0 }),
        type: "success"
      });
      refreshTable();
      await loadTree();
    }
    delDlg.visible = false;
  } finally {
    delDlg.loading = false;
  }
};

/* ---------------- 清空目录（高危） ---------------- */

const clearDlg = reactive({ visible: false, confirmText: "", loading: false });
const openClear = () => Object.assign(clearDlg, { visible: true, confirmText: "", loading: false });

const confirmClear = async () => {
  if (!folderInfo.value) return;
  clearDlg.loading = true;
  try {
    const { data } = await clearFolderMediaApi(folderInfo.value.id, clearDlg.confirmText);
    ElNotification({
      title: t("media.clearDone"),
      message: data.blocked?.length
        ? t("media.deletedMediaWithSkip", { n: data.deletedCount ?? 0, skipped: data.blocked.length })
        : t("media.deletedMedia", { n: data.deletedCount ?? 0 }),
      type: "success"
    });
    clearDlg.visible = false;
    refreshTable();
    await loadTree();
  } finally {
    clearDlg.loading = false;
  }
};

/* ---------------- 上传 ---------------- */

const uploadVisible = ref(false);
const uploading = ref(false);
const uploadRef = ref();

/**
 * 待上传清单 —— 抽屉里那张表的数据源，一行一个文件。
 *
 * 原来这里是 fileList（el-upload 的清单）+ uploadResults（服务端返回的结果）
 * 两份数据，一条总进度条。两份对不上号：结果按返回顺序排，清单按选择顺序排，
 * 想知道「第三行那个到底成没成」得自己拿文件名去另一个数组里找。
 *
 * 现在只有这一份，每行自己带着进度和结果，从选中一直用到传完。
 */
interface UpRow {
  name: string;
  size: number;
  raw: File;
  percent: number;
  phase: "pending" | "uploading" | "transcoding" | "done" | "failed";
  /** 服务端返回的 created / overwritten，done 时才有 */
  status?: string;
  /** 服务端算出来的播放时长，done 时才有 */
  timeText?: string;
  sourceFormat?: string;
  targetFormat?: string;
  message?: string;
}

const rows = ref<UpRow[]>([]);

/** 还没传成功的行数 —— 「确定(N)」上的 N，也是能不能点确定的判据 */
const pendingCount = computed(() => rows.value.filter(r => r.phase !== "done").length);

/**
 * el-upload 只负责挑文件，挑完直接进表，它自己的清单不显示（show-file-list=false）。
 *
 * ⚠ on-change 每选一次会带着**全量**清单回调，所以这里按「文件名 + 大小 + 修改时间」
 *   去重，否则连点两次「添加媒体」选同一批文件，表里会出现两遍。
 */
const onFileChange = (_f: any, list: UploadUserFile[]) => {
  const keyOf = (f: File) => `${f.name}|${f.size}|${f.lastModified}`;
  const have = new Set(rows.value.map(r => keyOf(r.raw)));
  for (const it of list) {
    const raw = it.raw as File | undefined;
    if (!raw || have.has(keyOf(raw))) continue;
    have.add(keyOf(raw));
    rows.value.push({ name: raw.name, size: raw.size, raw, percent: 0, phase: "pending" });
  }
  // el-upload 自己那份清单已经没人看了，清掉免得越攒越大
  uploadRef.value?.clearFiles?.();
};

const dropRow = (i: number) => rows.value.splice(i, 1);

const onUploadClose = () => {
  // 传完的行没必要留着下次再看，没传的留着 —— 用户可能只是点错了关闭
  rows.value = rows.value.filter(r => r.phase !== "done");
  rows.value.forEach(r => {
    r.percent = 0;
    r.phase = "pending";
    r.message = undefined;
  });
};

const fmtBytes = (n: number) => {
  if (n >= 1024 * 1024) return (n / 1024 / 1024).toFixed(1) + " MB";
  if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
  return n + " B";
};

const progressStatus = (r: UpRow) => {
  if (r.phase === "failed") return "exception";
  if (r.phase === "done") return "success";
  return undefined;
};

/**
 * 进度条底下那行字。
 *
 * ⚠ 浏览器只能报「字节发出去了多少」，报不了服务器那边转码到哪一步。
 *   媒体上传是先传完、再转码：字节到 100% 之后，服务端还要走一遍 ffmpeg
 *   （统一转 128kbps 立体声）。所以 100% 不等于完事，这里把阶段切成
 *   「服务器转码中」，免得用户对着一个卡在 100% 的条不知道在等什么。
 */
const rowText = (r: UpRow) => {
  switch (r.phase) {
    case "pending":
      return t("media.waitingUpload");
    case "uploading":
      return `${fmtBytes(Math.round((r.size * r.percent) / 100))} / ${fmtBytes(r.size)}`;
    case "transcoding":
      return t("media.transcoding");
    case "failed":
      return r.message || t("media.failed");
    default:
      // 转码前后的参数摆出来 —— 上传的码率五花八门，用户得看得见
      // 「我这个 320k 单声道的文件，进来之后变成了 128k 立体声」。
      return `${r.status === "overwritten" ? t("media.overwrite") : t("common.create")} · ${
        r.sourceFormat || "WAV"
      } → ${r.targetFormat || ""}`;
  }
};

/** 传一个文件，进度回写到这一行 */
const uploadOne = (row: UpRow, folderId: number) =>
  new Promise<any>((resolve, reject) => {
    const form = new FormData();
    form.append("folderId", String(folderId));
    form.append("file", row.raw);

    // ⚠ 用 XHR 不用 fetch：fetch 没有上传进度事件（没有 request 侧的
    //   ReadableStream 进度回调），要百分比就只能走 xhr.upload.onprogress。
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "/api/media/upload");
    xhr.setRequestHeader("x-access-token", userStore.token);
    // ⚠ 这一条不能漏。服务端返回的播放时长是**带语言的文案**（「3分12秒」/「3m 12s」），
    //   由 Accept-Language 决定；上传走的是裸 XHR，不经过 axios 拦截器，
    //   不自己补这个头的话，浏览器会带上它自己的默认值（Chromium 是 en-US），
    //   于是中文界面上冒出一个「0m 9s」。axios 那边在 api/index.ts 里设的就是这一行。
    xhr.setRequestHeader("Accept-Language", globalStore.language);
    xhr.upload.onprogress = e => {
      if (!e.lengthComputable) return;
      row.percent = Math.round((e.loaded / e.total) * 100);
      row.phase = e.loaded >= e.total ? "transcoding" : "uploading";
    };
    xhr.onload = () => {
      try {
        resolve(JSON.parse(xhr.responseText));
      } catch {
        reject(new Error(t("media.unparsableResponse", { status: xhr.status })));
      }
    };
    xhr.onerror = () => reject(new Error(t("media.networkError")));
    xhr.ontimeout = () => reject(new Error(t("media.uploadTimeout")));
    xhr.send(form);
  });

/**
 * 确定：把还没传成功的行**一个一个**传上去。
 *
 * ⚠ 为什么不像原来那样一次请求带走全部文件：一次请求只有一条总进度，
 *   而且字节全部发完之后服务端才开始逐个转码 —— 那段时间每一行都会卡在
 *   100%，谁也不知道轮到哪个了，结果还要等最后一个转完才一起返回。
 *   一个一个传，每一行的进度、时长、成败都在它自己走完时就落定。
 *
 * ⚠ 代价是后端会按文件数各通知一次 MediaChanged（原来一次请求只通知一次）。
 *   那是「重新加载这个目录」的通知，多发几次不会错，只是多几个包。
 *   用这个代价换「哪一个在传、哪一个好了」看得见，值。
 *
 * 已经传成功的行跳过：中途失败重试时不会把成功的再传一遍（也就不会触发同名覆盖）。
 */
const doUpload = async () => {
  if (!folderInfo.value) return;
  const folderId = folderInfo.value.id;
  uploading.value = true;
  let ok = 0;
  let fail = 0;
  try {
    for (const row of rows.value) {
      if (row.phase === "done") continue;
      row.percent = 0;
      row.phase = "uploading";
      row.message = undefined;
      try {
        const json = await uploadOne(row, folderId);
        if (json.code !== 200) throw new Error(json.msg || t("media.uploadFailed"));
        const res = (json.data.results ?? [])[0];
        if (!res) throw new Error(t("media.uploadFailed"));
        row.percent = 100;
        if (res.status === "failed") {
          row.phase = "failed";
          row.message = res.message;
          fail++;
        } else {
          row.phase = "done";
          row.status = res.status;
          row.timeText = res.timelengthText;
          row.sourceFormat = res.sourceFormat;
          row.targetFormat = res.targetFormat;
          ok++;
        }
      } catch (e: any) {
        row.percent = 100;
        row.phase = "failed";
        row.message = e?.message ?? String(e);
        fail++;
      }
    }
    ElNotification({
      title: t("media.uploadDone"),
      message: t("media.uploadSummary", { ok, fail }),
      type: fail ? "warning" : "success"
    });
    refreshTable();
    await loadTree();
  } finally {
    uploading.value = false;
  }
};

/* ---------------- 试听 / 下载 ---------------- */

const withToken = (url: string) => `${url}${url.includes("?") ? "&" : "?"}token=${encodeURIComponent(userStore.token)}`;

const onPreview = (row: MediaItem) => {
  playing.value = row;
  playerVisible.value = true;
};

const stopAudio = () => {
  audioRef.value?.pause();
  playing.value = undefined;
};

const onDownload = (row: MediaItem) => {
  if (!row.downloadUrl) return ElMessage.warning(t("media.noDownloadable"));
  window.open(withToken(row.downloadUrl), "_blank");
};

onMounted(loadTree);
</script>

<style scoped lang="scss">
.up-table {
  margin-bottom: 10px;
}
.up-name {
  line-height: 1.4;
}
.up-size {
  font-size: 12px;
  line-height: 1.4;
  color: var(--el-text-color-secondary);
}
.up-progress-text {
  margin: 4px 0 0;
  overflow: hidden;
  font-size: 12px;
  color: var(--el-text-color-secondary);
  text-overflow: ellipsis;
  white-space: nowrap;
  &.bad {
    color: var(--el-color-danger);
  }
}
.up-add {
  margin-bottom: 10px;
}
.dlg-note {
  margin: 0 0 10px;
  font-size: 13px;
  color: var(--el-text-color-secondary);
}

.media-container {
  display: flex;
  gap: 10px;
  height: 100%;
}
.tree-panel {
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  width: 270px;
  padding: 12px;
  background-color: var(--el-bg-color);
  border-radius: 6px;
  box-shadow: 0 0 12px rgb(0 0 0 / 5%);
}
.tree-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}
.tree-title {
  font-size: 15px;
  font-weight: 600;
}
.tree-filter {
  margin-bottom: 10px;
}
.tree-scroll {
  flex: 1;
  overflow: hidden;
}
.tree-tip {
  padding-top: 8px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
  text-align: center;
  border-top: 1px solid var(--el-border-color-lighter);
}
.tree-node {
  display: flex;
  gap: 5px;
  align-items: center;
  width: 100%;
  overflow: hidden;
}
.node-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.node-count {
  margin-left: auto;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.orphan-block {
  margin-top: 8px;
}
.orphan-title {
  font-size: 12px;
  color: var(--el-color-warning);
}
.table-panel {
  flex: 1;
  overflow: hidden;
}
.table-header-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
  justify-content: space-between;
  width: 100%;
}
.table-header-info {
  display: flex;
  gap: 10px;
  align-items: center;
}
.table-header-ops {
  display: flex;
  gap: 8px;
}
.cur-folder {
  font-size: 15px;
  font-weight: 600;
}
.muted {
  color: var(--el-text-color-secondary);
}
.player-meta {
  margin-top: 10px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.ctx-menu {
  position: fixed;
  z-index: 3000;
  min-width: 190px;
  padding: 4px 0;
  background: var(--el-bg-color-overlay);
  border: 1px solid var(--el-border-color-light);
  border-radius: 4px;
  box-shadow: var(--el-box-shadow-light);
}
.ctx-item {
  display: flex;
  gap: 6px;
  align-items: center;
  padding: 8px 14px;
  font-size: 13px;
  cursor: pointer;
  &:hover {
    background: var(--el-fill-color-light);
  }
  &.danger {
    color: var(--el-color-danger);
  }
  &.disabled {
    color: var(--el-text-color-disabled);
    cursor: not-allowed;
    background: transparent;
  }
}
.ctx-why {
  margin-left: auto;
  font-size: 11px;
  color: var(--el-text-color-secondary);
}
.dlg-tip {
  font-size: 12px;
  line-height: 1.7;
  color: var(--el-text-color-secondary);
}
.fill {
  width: 100%;
}
.tip {
  font-size: 12px;
  color: var(--el-text-color-secondary);
  &.block {
    display: block;
    line-height: 1.6;
  }
}
.mb10 {
  margin-bottom: 10px;
}
.mt10 {
  margin-top: 10px;
}
</style>
