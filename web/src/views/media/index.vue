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
        <span class="tree-title">{{ treeData?.rootName || "文件管理" }}</span>
        <span>
          <el-button link :icon="Plus" title="在选中目录下新建" :disabled="!canCreateUnderCurrent" @click="openCreate" />
          <el-button link :icon="Refresh" :loading="treeLoading" title="刷新" @click="loadTree" />
        </span>
      </div>
      <el-input v-model="treeFilter" placeholder="筛选目录" clearable :prefix-icon="Search" class="tree-filter" />
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
              <el-tag v-if="data.system" size="small" type="info" effect="plain">系统</el-tag>
              <el-tag v-else-if="!data.shared" size="small" type="warning" effect="plain">私有</el-tag>
              <span v-if="data.mediaCount > 0" class="node-count">{{ data.mediaCount }}</span>
            </span>
          </template>
        </el-tree>

        <!-- 游离目录：parentid 指向已不存在的父级。旧版会让它们彻底消失 -->
        <div v-if="treeData?.orphans?.length" class="orphan-block">
          <el-divider content-position="left">
            <span class="orphan-title">游离目录（父级已删除）</span>
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
      <div class="tree-tip">右键目录可新建 / 重命名 / 删除</div>
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
                <el-tag size="small" effect="plain">合计 {{ folderInfo.totalSizeText }}</el-tag>
                <el-tag v-if="folderInfo.isRecordLibrary" size="small" type="warning" effect="plain">
                  录音库：仅可试听 / 下载 / 删除
                </el-tag>
                <el-tag v-if="scopeNote" size="small" type="info" effect="plain">{{ scopeNote }}</el-tag>
              </template>
              <span v-else class="cur-folder muted">请选择左侧目录</span>
            </div>
            <div class="table-header-ops">
              <el-button
                type="primary"
                :icon="Upload"
                :disabled="!folderInfo?.canUpload"
                :title="uploadDisabledReason"
                @click="uploadVisible = true"
              >
                添加媒体
              </el-button>
              <!-- 未勾选时禁用。旧版此时的行为是清空整个目录，误点即灾难 -->
              <el-button
                type="danger"
                :icon="Delete"
                :disabled="!scope.isSelected"
                title="删除选中的媒体"
                @click="onDeleteMedia(scope.selectedListIds)"
              >
                删除{{ scope.selectedListIds.length ? `(${scope.selectedListIds.length})` : "" }}
              </el-button>
              <el-button
                type="danger"
                plain
                :icon="DeleteFilled"
                :disabled="!folderInfo || folderInfo.totalSizeKB === 0"
                @click="openClear"
              >
                清空目录
              </el-button>
            </div>
          </div>
        </template>

        <template #operation="scope">
          <el-button type="primary" link :icon="VideoPlay" @click="onPreview(scope.row)">试听</el-button>
          <el-button type="primary" link :icon="Download" @click="onDownload(scope.row)">下载</el-button>
        </template>
      </ProTable>
    </div>

    <!-- 右键菜单 -->
    <div v-show="ctx.visible" class="ctx-menu" :style="{ left: ctx.x + 'px', top: ctx.y + 'px' }">
      <div :class="['ctx-item', { disabled: !ctx.node?.canCreateChild }]" @click="ctx.node?.canCreateChild && openCreate()">
        <el-icon><Plus /></el-icon> 新建子目录
        <span v-if="ctx.node && !ctx.node.canCreateChild" class="ctx-why">已达 3 层上限</span>
      </div>
      <div :class="['ctx-item', { disabled: !ctx.node?.canModify }]" @click="ctx.node?.canModify && openEdit()">
        <el-icon><EditPen /></el-icon> 重命名 / 共享设置
        <span v-if="ctx.node?.system" class="ctx-why">系统预置</span>
      </div>
      <div :class="['ctx-item danger', { disabled: !ctx.node?.canDelete }]" @click="ctx.node?.canDelete && onDeleteFolder()">
        <el-icon><Delete /></el-icon> 删除目录
        <span v-if="ctx.node?.system" class="ctx-why">系统预置</span>
      </div>
    </div>

    <!-- 新建 / 编辑目录 -->
    <el-dialog v-model="folderDlg.visible" :title="folderDlg.isEdit ? '修改文件夹' : '新建文件夹'" width="440px">
      <el-form label-width="90px">
        <el-form-item v-if="!folderDlg.isEdit" label="上级目录">
          <el-input :model-value="folderDlg.parentName" disabled />
        </el-form-item>
        <el-form-item label="名称" required>
          <el-input v-model="folderDlg.name" maxlength="60" show-word-limit placeholder="请输入文件夹名称" />
        </el-form-item>
        <!--
          「是否共享」只在**修改**时给 —— 右键菜单那一项本来就叫「重命名 / 共享设置」。
          新建时不问：ok112 的 filefolderadd.html 虽然摆了个勾选框，但 do.php 里
          未勾选就是 0，新目录默认私有；建的时候还没内容，共享与否没什么可决定的，
          需要共享再进「修改」改一次。
        -->
        <el-form-item v-if="folderDlg.isEdit" label="是否共享">
          <el-switch v-model="folderDlg.shared" active-text="共享（全员可见）" inactive-text="私有（仅自己可见）" />
        </el-form-item>
      </el-form>
      <div v-if="folderDlg.isEdit" class="dlg-tip">
        共享目录全员可见；私有目录仅创建者可见。<br />
        注意：目录内的媒体仍按「仅显示我上传的」过滤，共享的是目录本身。
      </div>
      <div v-else class="dlg-tip">新目录默认<b>私有</b>（仅自己可见）。需要共享的话，建好后右键「重命名 / 共享设置」再改。</div>
      <template #footer>
        <el-button @click="folderDlg.visible = false">取消</el-button>
        <el-button type="primary" :loading="folderDlg.loading" @click="submitFolder">确定</el-button>
      </template>
    </el-dialog>

    <!-- 删除影响面预览 -->
    <el-dialog v-model="delDlg.visible" :title="delDlg.title" width="560px">
      <div v-if="delDlg.deletable.length">
        <el-alert type="warning" :closable="false" show-icon class="mb10"> 以下内容将被<b>永久删除</b>，且不可恢复： </el-alert>
        <el-table :data="delDlg.deletable" size="small" border max-height="240">
          <el-table-column prop="name" label="名称" />
          <el-table-column v-if="delDlg.kind === 'folder'" label="影响范围" width="200">
            <template #default="s"> 子目录 {{ s.row.descendantFolders }} 个 · 媒体 {{ s.row.mediaCount }} 个 </template>
          </el-table-column>
        </el-table>
      </div>
      <div v-if="delDlg.blocked.length" class="mt10">
        <el-alert type="error" :closable="false" show-icon class="mb10"> 以下内容<b>无法删除</b>： </el-alert>
        <el-table :data="delDlg.blocked" size="small" border max-height="200">
          <el-table-column prop="name" label="名称" />
          <el-table-column label="原因">
            <template #default="s">
              {{ blockReasonText(s.row.reason) }}
              <span v-if="s.row.refName">（{{ s.row.refName }}）</span>
              <span v-else-if="s.row.detail">（{{ s.row.detail }}）</span>
            </template>
          </el-table-column>
        </el-table>
      </div>
      <div v-if="!delDlg.deletable.length && !delDlg.blocked.length" class="muted">没有可处理的对象。</div>
      <template #footer>
        <el-button @click="delDlg.visible = false">取消</el-button>
        <el-button type="danger" :disabled="!delDlg.deletable.length" :loading="delDlg.loading" @click="confirmDelete">
          确认删除
        </el-button>
      </template>
    </el-dialog>

    <!-- 清空目录（高危，需输入目录名） -->
    <el-dialog v-model="clearDlg.visible" title="清空目录内的全部媒体" width="480px">
      <el-alert type="error" :closable="false" show-icon class="mb10">
        此操作会删除「{{ folderInfo?.name }}」下的<b>全部媒体及其物理文件</b>，不可恢复。<br />
        正被任务、快捷键、报警映射或打铃条目引用的媒体会被自动跳过。
      </el-alert>
      <el-form label-width="110px">
        <el-form-item label="请输入目录名">
          <el-input v-model="clearDlg.confirmText" :placeholder="folderInfo?.name" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="clearDlg.visible = false">取消</el-button>
        <el-button
          type="danger"
          :disabled="clearDlg.confirmText !== folderInfo?.name"
          :loading="clearDlg.loading"
          @click="confirmClear"
        >
          我确认清空
        </el-button>
      </template>
    </el-dialog>

    <!-- 添加媒体：表单项与按钮照 :80 的「添加媒体」弹窗（所属文件夹 / 媒体文件） -->
    <el-drawer v-model="uploadVisible" title="添加媒体" size="480px" @close="uploadResults = []">
      <el-form label-width="90px">
        <el-form-item label="所属文件夹">
          <!-- 目录在左树里选，这里只读回显，避免两处可改导致传到别的目录去 -->
          <el-input :model-value="folderInfo?.name ?? ''" readonly />
        </el-form-item>
        <el-form-item label="媒体文件">
          <el-upload
            ref="uploadRef"
            drag
            multiple
            :auto-upload="false"
            :file-list="fileList"
            accept=".mp3,.wav"
            :on-change="onFileChange"
            :on-remove="onFileChange"
            class="fill"
          >
            <el-icon class="el-icon--upload"><UploadFilled /></el-icon>
            <div class="el-upload__text">拖拽文件到此处，或<em>选择文件</em></div>
          </el-upload>
        </el-form-item>
      </el-form>

      <el-alert type="info" :closable="false" show-icon class="mb10">
        支持 mp3 / wav，单文件不超过 300MB。<br />
        上传的 MP3 码率各不相同（32k 到 320k 都有），服务端会先认文件头确认格式， 再统一转成 <b>128kbps 立体声</b>（提示音目录
        16000Hz，其余 44100Hz）， 并在尾部追加 2 秒静音（沿用原系统约定）。<br />
        同名文件将<b>覆盖</b>原有媒体。
      </el-alert>

      <div class="mt10">
        <el-button :disabled="uploading" @click="uploadVisible = false">取消</el-button>
        <el-button type="primary" :loading="uploading" :disabled="!fileList.length" @click="doUpload">
          确定{{ fileList.length ? `（${fileList.length} 个）` : "" }}
        </el-button>
      </div>

      <!--
        进度条。百分比走的是浏览器的 xhr.upload.onprogress，量的是
        「字节发出去了多少」；发完之后服务端还要逐个转码，那一段没有进度可报，
        所以到 100% 就把文案换成「服务器转码中…」。
      -->
      <div v-if="progress.phase" class="up-progress">
        <el-progress
          :percentage="progress.percent"
          :status="progress.phase === 'transcoding' ? 'success' : undefined"
          :stroke-width="14"
          text-inside
        />
        <p class="up-progress-text">{{ progressText }}</p>
      </div>

      <div v-if="uploadResults.length" class="mt10">
        <el-divider content-position="left">上传结果</el-divider>
        <el-table :data="uploadResults" size="small" border>
          <el-table-column prop="fileName" label="文件" min-width="120" show-overflow-tooltip />
          <el-table-column label="结果" width="80" align="center">
            <template #default="s">
              <el-tag v-if="s.row.status === 'created'" type="success" size="small">新增</el-tag>
              <el-tag v-else-if="s.row.status === 'overwritten'" type="warning" size="small">覆盖</el-tag>
              <el-tag v-else type="danger" size="small">失败</el-tag>
            </template>
          </el-table-column>
          <!--
            转码前后的参数摆出来 —— 上传的码率五花八门，用户得看得见
            「我这个 320k 单声道的文件，进来之后变成了 128k 立体声」。
            失败的行这里改放原因，比缩在 tooltip 里靠谱。
          -->
          <el-table-column label="转换" min-width="200">
            <template #default="s">
              <span v-if="s.row.status === 'failed'" class="up-bad">{{ s.row.message }}</span>
              <span v-else class="up-fmt">
                <span class="up-src">{{ s.row.sourceFormat || "WAV" }}</span>
                <el-icon><Right /></el-icon>
                <span class="up-dst">{{ s.row.targetFormat }}</span>
              </span>
            </template>
          </el-table-column>
        </el-table>
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
import {
  Delete,
  DeleteFilled,
  Download,
  EditPen,
  FolderOpened,
  Plus,
  Refresh,
  Right,
  Search,
  Upload,
  UploadFilled,
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
import { useUserStore } from "@/stores/modules/user";

const userStore = useUserStore();

const treeRef = ref();
const proTableRef = ref();
const audioRef = ref<HTMLAudioElement>();
const uploadRef = ref();

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
  if (!folderInfo.value) return "请先选择目录";
  if (folderInfo.value.isRecordLibrary) return "录音媒体库不允许上传";
  if (!folderInfo.value.canUpload) return "无媒体权限，或服务器处于备机只读模式";
  return "";
});

const columns = reactive<ColumnProps<MediaItem>[]>([
  { type: "selection", fixed: "left", width: 55 },
  // 列名严格照 :80（页面规格.txt「文件管理」）：媒体名称 | 媒体大小 | 媒体类型 | 媒体比特率 | 播放时长 | 操作。
  // ID 列 :80 没有，这里也去掉 —— 需要 id 的地方（试听/下载/删除）都走行对象，不靠肉眼抄。
  { prop: "name", label: "媒体名称", search: { el: "input", key: "keyword", props: { placeholder: "请输入媒体名称" } } },
  { prop: "sizeText", label: "媒体大小", width: 110 },
  // 旧版的搜索是一个「选择类型…」下拉 +一个关键字框，可选「媒体名称 / 媒体类型」。
  // 这里做成两个并列的搜索框，两路条件可以同时生效，比旧版那个二选一好用。
  {
    prop: "typeid",
    label: "媒体类型",
    width: 100,
    search: { el: "input", key: "typeid", props: { placeholder: "如 mp3 / wav" } }
  },
  { prop: "bitrateText", label: "媒体比特率", width: 120 },
  { prop: "timelengthText", label: "播放时长", width: 120 },
  { prop: "operation", label: "操作", width: 170, fixed: "right" }
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
  if (!parent) return ElMessage.warning("请先选择上级目录");
  if (!parent.canCreateChild) return ElMessage.warning("该目录已达 3 层上限，不能再建子目录");
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
  if (!name) return ElMessage.warning("请输入文件夹名称");
  folderDlg.loading = true;
  try {
    if (folderDlg.isEdit) {
      await updateFolderApi(folderDlg.id, { name, shared: folderDlg.shared });
      ElMessage.success("修改成功");
    } else {
      const parent = currentNode.value!;
      // 新建不问共享，一律私有（与 ok112 未勾选时的取值一致）
      await createFolderApi({ name, parentId: parent.id, shared: false });
      ElMessage.success("创建成功");
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
    title: "删除文件夹",
    deletable: data.deletable ?? [],
    blocked: data.blocked ?? [],
    ids: (data.deletable ?? []).map(d => d.id),
    loading: false
  });
};

// ProTable 的 selectedListIds 是 string[]，这里统一转成数字再发给后端
const onDeleteMedia = async (rawIds: (string | number)[]) => {
  const ids = (rawIds ?? []).map(Number).filter(n => Number.isFinite(n) && n > 0);
  if (!ids.length) return ElMessage.warning("请先勾选要删除的媒体");
  const { data } = await previewDeleteMediaApi(ids);
  Object.assign(delDlg, {
    visible: true,
    kind: "media",
    title: "删除媒体",
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
        title: "删除完成",
        message: `已删除 ${data.deletedFolders?.length ?? 0} 个目录、${data.deletedMediaCount ?? 0} 个媒体`,
        type: "success"
      });
      currentFolderId.value = 0;
      await loadTree();
    } else {
      const { data } = await deleteMediaApi(delDlg.ids);
      ElNotification({ title: "删除完成", message: `已删除 ${data.deletedCount ?? 0} 个媒体`, type: "success" });
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
      title: "清空完成",
      message: `已删除 ${data.deletedCount ?? 0} 个媒体${data.blocked?.length ? `，${data.blocked.length} 个因被引用而跳过` : ""}`,
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
const fileList = ref<UploadUserFile[]>([]);
const uploadResults = ref<any[]>([]);

const onFileChange = (_f: any, list: UploadUserFile[]) => (fileList.value = list);

/**
 * 上传进度。
 *
 * ⚠ 浏览器只能报「字节发出去了多少」，报不了服务器那边转码到哪一步了。
 *   媒体上传是**先传完、再转码**：字节到 100% 之后，服务端还要逐个走 ffmpeg
 *   （统一转 128kbps 立体声）。所以百分比到 100 不等于完事，这里把阶段
 *   切成「转码中」，免得用户对着一个卡在 100% 的条不知道在等什么。
 */
const progress = reactive({
  percent: 0,
  loaded: 0,
  total: 0,
  phase: "" as "" | "uploading" | "transcoding"
});

const fmtBytes = (n: number) => {
  if (n >= 1024 * 1024) return (n / 1024 / 1024).toFixed(1) + " MB";
  if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
  return n + " B";
};

const progressText = computed(() => {
  if (progress.phase === "transcoding") return "文件已传完，服务器转码中…";
  if (progress.phase === "uploading" && progress.total) {
    return `${fmtBytes(progress.loaded)} / ${fmtBytes(progress.total)}`;
  }
  return "";
});

const doUpload = async () => {
  if (!folderInfo.value) return;
  uploading.value = true;
  uploadResults.value = [];
  progress.percent = 0;
  progress.loaded = 0;
  progress.total = 0;
  progress.phase = "uploading";
  try {
    const form = new FormData();
    form.append("folderId", String(folderInfo.value.id));
    fileList.value.forEach(f => f.raw && form.append("file", f.raw));

    // ⚠ 用 XHR 不用 fetch：fetch 没有上传进度事件（没有 request 侧的
    //   ReadableStream 进度回调），要百分比就只能走 xhr.upload.onprogress。
    const json = await new Promise<any>((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open("POST", "/api/media/upload");
      xhr.setRequestHeader("x-access-token", userStore.token);
      xhr.upload.onprogress = e => {
        if (!e.lengthComputable) return;
        progress.loaded = e.loaded;
        progress.total = e.total;
        progress.percent = Math.round((e.loaded / e.total) * 100);
        // 字节发完了，后面等的是服务端转码
        if (e.loaded >= e.total) progress.phase = "transcoding";
      };
      xhr.onload = () => {
        try {
          resolve(JSON.parse(xhr.responseText));
        } catch {
          reject(new Error(`服务器返回了无法解析的内容（HTTP ${xhr.status}）`));
        }
      };
      xhr.onerror = () => reject(new Error("网络错误"));
      xhr.ontimeout = () => reject(new Error("上传超时"));
      xhr.send(form);
    });

    if (json.code !== 200) {
      ElMessage.error(json.msg || "上传失败");
      return;
    }
    uploadResults.value = json.data.results ?? [];
    const ok = uploadResults.value.filter(r => r.status !== "failed").length;
    ElNotification({
      title: "上传完成",
      message: `成功 ${ok} 个，失败 ${uploadResults.value.length - ok} 个`,
      type: ok ? "success" : "warning"
    });
    fileList.value = [];
    uploadRef.value?.clearFiles?.();
    refreshTable();
    await loadTree();
  } catch (e: any) {
    ElMessage.error("上传失败：" + (e?.message ?? e));
  } finally {
    uploading.value = false;
    progress.phase = "";
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
  if (!row.downloadUrl) return ElMessage.warning("该媒体没有可下载的文件");
  window.open(withToken(row.downloadUrl), "_blank");
};

onMounted(loadTree);
</script>

<style scoped lang="scss">
.up-progress {
  margin-top: 12px;
}
.up-progress-text {
  margin: 6px 0 0;
  font-size: 12px;
  color: var(--el-text-color-secondary);
  text-align: right;
}
.up-fmt {
  display: inline-flex;
  gap: 6px;
  align-items: center;
  font-size: 12px;
}
.up-src {
  color: var(--el-text-color-secondary);
}
.up-dst {
  color: var(--el-color-success);
}
.up-bad {
  font-size: 12px;
  color: var(--el-color-danger);
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
