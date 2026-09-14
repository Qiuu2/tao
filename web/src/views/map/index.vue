<!--
  资源管理 → 地图

  一张校园平面图当底图，把终端按实际位置摆上去。

  # 为什么要有这一页

  终端列表回答的是「哪一台怎么样」—— 一行 20 列，适合按字段扫。
  巡检时真正想知道的是「掉线那台在哪」，那是列表答不了的：
  得拿着终端名字去脑子里翻楼栋。地图把这个问题变成看一眼。

  # 坐标是百分比，不是像素

  底图按容器宽度缩放显示。存像素的话，换个分辨率、收起侧边栏，点就全偏了。
  存百分比之后：显示多大都对得上，换底图（同一张图换清晰版）也不用重摆。
  服务端那边同理，见 server/internal/mapview 的包注释。

  # 还没传底图时不是空页面

  默认那条底图记录 filename 是空的，这里画一张占位图（底图名字 + 一层网格）。
  占位图上照样能摆终端 —— 坐标是百分比，等真图传上来原样对得上，不用重摆。
-->
<template>
  <div class="map-page">
    <!-- 左：底图清单 -->
    <div class="map-side">
      <div class="side-head">
        <span class="side-title">{{ $t("mapView.baseMaps") }}</span>
        <el-button :icon="Plus" size="small" text :disabled="!canEdit" @click="onCreate">
          {{ $t("mapView.newMap") }}
        </el-button>
      </div>
      <el-scrollbar class="side-scroll">
        <div v-for="m in maps" :key="m.id" class="side-item" :class="{ active: m.id === currentId }" @click="selectMap(m.id)">
          <el-icon><Picture v-if="m.hasImage" /><PictureFilled v-else /></el-icon>
          <span class="side-name" :title="m.name">{{ m.name }}</span>
          <span class="side-count">{{ m.terminals }}</span>
        </div>
        <p v-if="!maps.length && !loading" class="side-empty">{{ $t("mapView.noMapYet") }}</p>
      </el-scrollbar>
    </div>

    <!-- 右：画布 -->
    <div class="map-main">
      <div class="map-bar">
        <div class="bar-left">
          <b class="cur-name">{{ current?.name || "—" }}</b>
          <el-tag v-if="current && !current.hasImage" type="warning" size="small" effect="plain">
            {{ $t("mapView.placeholderTip") }}
          </el-tag>
          <el-tag v-else-if="current" type="info" size="small" effect="plain">
            {{ current.width }} × {{ current.height }}
          </el-tag>
          <el-tag type="info" size="small" effect="plain">{{ $t("mapView.placedN", { n: placements.length }) }}</el-tag>
        </div>
        <div class="bar-right">
          <!-- 换底图：挑完文件立刻传，不需要再点一次确定 -->
          <el-upload
            ref="uploadRef"
            :auto-upload="false"
            :show-file-list="false"
            accept=".png,.jpg,.jpeg"
            :on-change="onPickImage"
          >
            <el-button :icon="UploadIcon" :disabled="!canEdit || !current" :loading="uploading">
              {{ $t("mapView.uploadMap") }}
            </el-button>
          </el-upload>
          <el-button :icon="Plus" :disabled="!canEdit || !current" @click="openPicker">
            {{ $t("mapView.addTerminal") }}
          </el-button>
          <el-button :icon="EditPen" :disabled="!canEdit || !current" @click="onRename">
            {{ $t("mapView.renameMap") }}
          </el-button>
          <el-button :icon="Delete" type="danger" plain :disabled="!canEdit || !current" @click="onDelete">
            {{ $t("mapView.deleteMap") }}
          </el-button>
          <el-button :icon="Refresh" circle @click="reloadAll" />
        </div>
      </div>

      <!--
        画布本体。
        ⚠ 宽高由底图原始比例决定（aspect-ratio），不写死像素 ——
          写死的话换一张长宽比不同的底图就会被拉伸变形，摆好的点跟着错位。
      -->
      <div v-loading="loading" class="canvas-wrap">
        <div v-if="current" ref="canvasRef" class="canvas" :style="{ aspectRatio: aspect }">
          <img v-if="current.hasImage" class="canvas-img" :src="withToken(current.imageUrl!)" :alt="current.name" />
          <!--
            占位底图画在 SVG 里，而不是往安装包里塞一张图片 ——
            省掉一个要跟着版本走的二进制文件，换真图时也不用清理它。
          -->
          <svg v-else class="canvas-img" viewBox="0 0 1600 1000" preserveAspectRatio="none">
            <defs>
              <pattern id="mapgrid" width="80" height="80" patternUnits="userSpaceOnUse">
                <path d="M 80 0 L 0 0 0 80" fill="none" stroke="var(--el-border-color)" stroke-width="1" />
              </pattern>
            </defs>
            <rect width="1600" height="1000" fill="var(--el-fill-color-light)" />
            <rect width="1600" height="1000" fill="url(#mapgrid)" />
            <text x="800" y="480" text-anchor="middle" class="ph-title">{{ current.name }}</text>
            <text x="800" y="545" text-anchor="middle" class="ph-sub">{{ $t("mapView.placeholderHint") }}</text>
          </svg>

          <!-- 终端点：拖动改位置，双击拿下来 -->
          <div
            v-for="p in placements"
            :key="p.terminalId"
            class="pin"
            :class="[pinClass(p), { dragging: dragId === p.terminalId }]"
            :style="{ left: p.x + '%', top: p.y + '%' }"
            :title="pinTitle(p)"
            @pointerdown="onPinDown($event, p)"
            @dblclick.stop="removeOne(p)"
          >
            <span class="pin-dot"></span>
            <span class="pin-label">{{ p.missing ? $t("mapView.deletedNo", { id: p.terminalId }) : p.terminalname }}</span>
          </div>
        </div>
        <el-empty v-else-if="!loading" :description="$t('mapView.pickOrCreate')" />
      </div>

      <p class="map-note">{{ $t("mapView.dragHint") }}</p>
    </div>

    <!-- 添加终端：只列还没上图的 -->
    <el-dialog v-model="picker.visible" :title="$t('mapView.addTerminal')" width="680px" top="8vh">
      <div class="pick-bar">
        <el-input
          v-model="picker.keyword"
          clearable
          size="small"
          style="width: 240px"
          :prefix-icon="Search"
          :placeholder="$t('term.searchTerminalNameOrIp')"
        />
        <span class="muted">{{ $t("mapView.pickerNote") }}</span>
      </div>
      <el-table :data="pickerRows" size="small" border max-height="46vh" row-key="id" @selection-change="onPickChange">
        <el-table-column type="selection" width="48" />
        <el-table-column prop="id" :label="$t('common.id')" width="80" align="center" />
        <el-table-column prop="terminalname" :label="$t('terminalCommon.terminalName')" min-width="160" show-overflow-tooltip />
        <el-table-column prop="typeName" :label="$t('terminalCommon.terminalType')" width="130" show-overflow-tooltip />
        <el-table-column prop="ip" :label="$t('common.ipAddress')" width="130" />
      </el-table>
      <p v-if="!pickerRows.length" class="dlg-note">{{ $t("mapView.allPlaced") }}</p>
      <template #footer>
        <el-button @click="picker.visible = false">{{ $t("common.cancel") }}</el-button>
        <el-button type="primary" :disabled="!picker.picked.length" :loading="picker.saving" @click="submitPicker">
          {{ $t("common.confirm") }}{{ picker.picked.length ? `(${picker.picked.length})` : "" }}
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="mapView">
import { useI18n } from "vue-i18n";
import { Delete, EditPen, Picture, PictureFilled, Plus, Refresh, Search, Upload as UploadIcon } from "@element-plus/icons-vue";
import { ElMessage, ElMessageBox, type UploadUserFile } from "element-plus";
import { computed, onMounted, reactive, ref, watch } from "vue";

import {
  createMapApi,
  deleteMapApi,
  getMapTerminalsApi,
  getMapsApi,
  placeMapTerminalApi,
  removeMapTerminalsApi,
  renameMapApi,
  type MapImage,
  type MapPlacement
} from "@/api/modules/mapview";
import { getTerminalListApi, type TerminalRow } from "@/api/modules/terminal";
import { useAuthStore } from "@/stores/modules/auth";
import { useGlobalStore } from "@/stores/modules/global";
import { useUserStore } from "@/stores/modules/user";

const { t } = useI18n();
const authStore = useAuthStore();
const userStore = useUserStore();
const globalStore = useGlobalStore();

// 写操作跟着终端管理那把钥匙（terminalpriv）——
// 把终端摆到图上本质上是终端配置，不另起一套权限。
const canEdit = computed(() => !!(authStore.authButtonListGet as any)?.terminal?.edit);

const loading = ref(false);
const uploading = ref(false);
const maps = ref<MapImage[]>([]);
const currentId = ref(0);
const placements = ref<MapPlacement[]>([]);
const canvasRef = ref<HTMLElement>();
const uploadRef = ref();

const current = computed(() => maps.value.find(m => m.id === currentId.value));

/** 画布按底图原始比例撑开；占位底图按 16:10（与 SVG 的 viewBox 一致） */
const aspect = computed(() => {
  const m = current.value;
  if (m?.hasImage && m.width > 0 && m.height > 0) return `${m.width} / ${m.height}`;
  return "1600 / 1000";
});

/** 取图要带令牌：<img src> 带不了自定义请求头，只能放 query（同媒体试听） */
const withToken = (url: string) => `${url}${url.includes("?") ? "&" : "?"}token=${encodeURIComponent(userStore.token)}`;

/* ---------------- 拉数据 ---------------- */

const VIEW_KEY = "htweb:map:last";

const loadMaps = async () => {
  const { data } = await getMapsApi();
  maps.value = data ?? [];
  if (!maps.value.length) {
    currentId.value = 0;
    placements.value = [];
    return;
  }
  // 记住上次看的那张；那张没了（被删了）就回到排第一的 —— 那就是默认底图
  if (!maps.value.some(m => m.id === currentId.value)) {
    let remembered = 0;
    try {
      remembered = Number(localStorage.getItem(VIEW_KEY) || 0);
    } catch {
      /* 隐私模式下读不到，用默认值 */
    }
    currentId.value = maps.value.some(m => m.id === remembered) ? remembered : maps.value[0].id;
  }
};

const loadPlacements = async () => {
  if (!currentId.value) {
    placements.value = [];
    return;
  }
  const { data } = await getMapTerminalsApi(currentId.value);
  placements.value = data ?? [];
};

const reloadAll = async () => {
  loading.value = true;
  try {
    await loadMaps();
    await loadPlacements();
  } finally {
    loading.value = false;
  }
};

const selectMap = (id: number) => (currentId.value = id);

watch(currentId, async id => {
  try {
    if (id) localStorage.setItem(VIEW_KEY, String(id));
  } catch {
    /* 存不下就算了，下次回到默认那张 */
  }
  await loadPlacements();
});

/* ---------------- 底图增删改 ---------------- */

const onCreate = async () => {
  const { value } = await ElMessageBox.prompt(t("mapView.newMapTip"), t("mapView.newMap"), {
    inputPattern: /\S/,
    inputErrorMessage: t("mapView.nameRequired")
  });
  const { data } = await createMapApi(String(value).trim());
  ElMessage.success(t("common.saveSuccess"));
  await loadMaps();
  currentId.value = data.id;
};

const onRename = async () => {
  if (!current.value) return;
  const { value } = await ElMessageBox.prompt(t("mapView.newMapTip"), t("mapView.renameMap"), {
    inputValue: current.value.name,
    inputPattern: /\S/,
    inputErrorMessage: t("mapView.nameRequired")
  });
  await renameMapApi(current.value.id, String(value).trim());
  ElMessage.success(t("common.saveSuccess"));
  await loadMaps();
};

const onDelete = async () => {
  if (!current.value) return;
  const m = current.value;
  await ElMessageBox.confirm(t("mapView.confirmDelete", { name: m.name, n: m.terminals }), t("mapView.deleteMap"), {
    type: "warning"
  });
  await deleteMapApi(m.id);
  ElMessage.success(t("common.deleteSuccess"));
  currentId.value = 0;
  await reloadAll();
};

/**
 * 换底图。
 *
 * ⚠ 用裸 XHR 不走 axios：这是 multipart，而且服务端的报错是带语言的文案，
 *   Accept-Language 这个头不能漏（与文件管理那边的上传同一条路子，
 *   那里踩过「中文界面冒出一句英文」的坑）。
 */
const onPickImage = async (f: UploadUserFile) => {
  const raw = f?.raw as File | undefined;
  uploadRef.value?.clearFiles?.();
  if (!raw || !current.value) return;

  uploading.value = true;
  try {
    const form = new FormData();
    form.append("file", raw);
    const json = await new Promise<any>((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open("POST", `/api/maps/${current.value!.id}/image`);
      xhr.setRequestHeader("x-access-token", userStore.token);
      xhr.setRequestHeader("Accept-Language", globalStore.language);
      xhr.onload = () => {
        try {
          resolve(JSON.parse(xhr.responseText));
        } catch {
          reject(new Error(t("media.unparsableResponse", { status: xhr.status })));
        }
      };
      xhr.onerror = () => reject(new Error(t("media.networkError")));
      xhr.send(form);
    });
    if (json.code !== 200) {
      ElMessage.error(json.msg || t("mapView.uploadFailed"));
      return;
    }
    ElMessage.success(t("mapView.uploaded"));
    await loadMaps();
  } catch (e: any) {
    ElMessage.error(String(e?.message ?? e));
  } finally {
    uploading.value = false;
  }
};

/* ---------------- 摆终端 ---------------- */

// 颜色口径与终端列表一致：离线灰、播放中橙、空闲绿、记录已失效红
const pinClass = (p: MapPlacement) => {
  if (p.missing) return "gone";
  if (p.netstate !== 1) return "off";
  return p.taskstate === 1 ? "playing" : "idle";
};

const pinTitle = (p: MapPlacement) => {
  if (p.missing) return t("mapView.deletedTip", { id: p.terminalId });
  const net = p.netstate === 1 ? t("common.online") : t("common.offline");
  const task = p.taskstate === 1 ? t("terminalCommon.playing") : t("terminalCommon.idle");
  const dev = p.devicestate === 1 ? t("common.started") : t("common.stopped");
  return `${p.terminalname} · #${p.terminalId}\n${p.typeName} · ${p.ip}\n${net} · ${task} · ${dev}\n${t("common.volume")} ${p.volume}`;
};

/**
 * 拖动摆放。
 *
 * ⚠ 用 pointer 事件 + setPointerCapture，不用 mousedown/mousemove：
 *   鼠标拖出画布再松手时，mouseup 落在别的元素上，点就永远粘在手上。
 *   捕获之后无论松在哪里都回到这个元素，也顺带支持了触摸屏。
 */
const dragId = ref(0);
let dragMoved = false;

const pctOf = (e: PointerEvent) => {
  const box = canvasRef.value?.getBoundingClientRect();
  if (!box || !box.width || !box.height) return null;
  const x = ((e.clientX - box.left) / box.width) * 100;
  const y = ((e.clientY - box.top) / box.height) * 100;
  return { x: Math.min(100, Math.max(0, x)), y: Math.min(100, Math.max(0, y)) };
};

const onPinDown = (e: PointerEvent, p: MapPlacement) => {
  if (!canEdit.value) return;
  e.preventDefault();
  dragId.value = p.terminalId;
  dragMoved = false;
  const el = e.currentTarget as HTMLElement;
  el.setPointerCapture?.(e.pointerId);

  const move = (ev: PointerEvent) => {
    const at = pctOf(ev);
    if (!at) return;
    dragMoved = true;
    p.x = at.x;
    p.y = at.y;
  };
  const up = async (ev: PointerEvent) => {
    el.removeEventListener("pointermove", move);
    el.removeEventListener("pointerup", up);
    el.removeEventListener("pointercancel", up);
    el.releasePointerCapture?.(ev.pointerId);
    dragId.value = 0;
    // 没动过就不发请求 —— 单纯点一下看提示不该产生一条写操作日志
    if (!dragMoved) return;
    try {
      await placeMapTerminalApi(currentId.value, p.terminalId, p.x, p.y);
    } catch {
      // 存不下就把位置拉回服务端的真值，别让界面停在一个没保存的位置上
      await loadPlacements();
    }
  };
  el.addEventListener("pointermove", move);
  el.addEventListener("pointerup", up);
  el.addEventListener("pointercancel", up);
};

/** 双击把终端从图上拿下来。只删摆放记录，终端本身一根毛都不动。 */
const removeOne = async (p: MapPlacement) => {
  if (!canEdit.value) return;
  const name = p.missing ? t("mapView.deletedNo", { id: p.terminalId }) : p.terminalname || `#${p.terminalId}`;
  await ElMessageBox.confirm(t("mapView.confirmRemove", { name }), t("mapView.removeTerminal"), { type: "warning" });
  await removeMapTerminalsApi(currentId.value, [p.terminalId]);
  ElMessage.success(t("mapView.removed"));
  await Promise.all([loadPlacements(), loadMaps()]);
};

/* ---------------- 添加终端 ---------------- */

const picker = reactive({
  visible: false,
  saving: false,
  keyword: "",
  all: [] as TerminalRow[],
  picked: [] as TerminalRow[]
});

const onPickChange = (rows: TerminalRow[]) => (picker.picked = rows);

/** 只列还没上图的终端 —— 已经在图上的再选一次没有意义 */
const pickerRows = computed(() => {
  const placed = new Set(placements.value.map(p => p.terminalId));
  const kw = picker.keyword.trim().toLowerCase();
  return picker.all
    .filter(row => !placed.has(row.id))
    .filter(row => !kw || `${row.terminalname}`.toLowerCase().includes(kw) || `${row.ip}`.toLowerCase().includes(kw));
});

const openPicker = async () => {
  picker.visible = true;
  picker.keyword = "";
  picker.picked = [];
  // 一次性拉全量：现场终端量级是几百到几千，够用；
  // 分页会让「已上图的滤掉」失真 —— 滤完可能整页空着，看着像没有终端可选。
  const { data } = await getTerminalListApi({ pageNum: 1, pageSize: 3000, groupId: 0 } as any);
  picker.all = (data as any)?.list ?? [];
};

/**
 * 确定：把选中的终端在左上角铺成一排排，等着用户拖到正确位置。
 *
 * ⚠ 不要摆在画布正中间：一来占位底图的名字就写在中间，点和字叠在一起；
 *   二来间距小了名字会互相盖住，看不出到底放了几台。所以从左上角起步、
 *   横向 16%、纵向 11% 地铺开，一排 6 个 —— 一次选十几台也不会挤成一团。
 */
const submitPicker = async () => {
  picker.saving = true;
  try {
    let i = 0;
    for (const row of picker.picked) {
      const x = 10 + (i % 6) * 16;
      const y = 10 + Math.floor(i / 6) * 11;
      await placeMapTerminalApi(currentId.value, row.id, Math.min(x, 95), Math.min(y, 95));
      i++;
    }
    ElMessage.success(t("mapView.placedOk", { n: picker.picked.length }));
    picker.visible = false;
    await Promise.all([loadPlacements(), loadMaps()]);
  } finally {
    picker.saving = false;
  }
};

onMounted(reloadAll);
</script>

<style scoped lang="scss">
.map-page {
  display: flex;
  gap: 10px;
  height: 100%;
}

/* 左侧底图清单 */
.map-side {
  display: flex;
  flex: none;
  flex-direction: column;
  width: 210px;
  padding: 10px 0;
  background: var(--el-bg-color);
  border-radius: 6px;
  box-shadow: var(--el-box-shadow-lighter);
}
.side-head {
  display: flex;
  gap: 6px;
  align-items: center;
  justify-content: space-between;
  padding: 0 10px 8px;
}
.side-title {
  font-weight: 600;
}
.side-scroll {
  flex: 1;
}
.side-item {
  display: flex;
  gap: 8px;
  align-items: center;
  padding: 8px 12px;
  cursor: pointer;
  &:hover {
    background: var(--el-fill-color-light);
  }
  &.active {
    color: var(--el-color-primary);
    background: var(--el-color-primary-light-9);
  }
}
.side-name {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.side-count {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.side-empty {
  padding: 12px;
  font-size: 13px;
  color: var(--el-text-color-secondary);
}

/* 右侧画布 */
.map-main {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-width: 0;
  padding: 10px;
  background: var(--el-bg-color);
  border-radius: 6px;
  box-shadow: var(--el-box-shadow-lighter);
}
.map-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}
.bar-left,
.bar-right {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}
.cur-name {
  font-size: 15px;
}
.canvas-wrap {
  display: flex;
  flex: 1;
  align-items: flex-start;
  justify-content: center;
  min-height: 240px;
  overflow: auto;
}
.canvas {
  position: relative;
  width: 100%;
  max-width: 100%;
  overflow: hidden;
  background: var(--el-fill-color-lighter);
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 4px;
}
.canvas-img {
  display: block;
  width: 100%;
  height: 100%;
  user-select: none;
  object-fit: fill;
}
.ph-title {
  font-size: 64px;
  font-weight: 700;
  fill: var(--el-text-color-secondary);
}
.ph-sub {
  font-size: 30px;
  fill: var(--el-text-color-placeholder);
}

/* 终端点 */
.pin {
  position: absolute;
  display: flex;
  flex-direction: column;
  gap: 2px;
  align-items: center;
  cursor: grab;
  transform: translate(-50%, -50%);
  &.dragging {
    z-index: 5;
    cursor: grabbing;
  }
}
.pin-dot {
  width: 14px;
  height: 14px;
  border: 2px solid #fff;
  border-radius: 50%;
  box-shadow: 0 1px 4px rgb(0 0 0 / 35%);
}
.pin-label {
  max-width: 110px;
  padding: 0 4px;
  overflow: hidden;
  font-size: 12px;
  line-height: 16px;
  color: var(--el-text-color-primary);
  text-overflow: ellipsis;
  white-space: nowrap;
  background: rgb(255 255 255 / 78%);
  border-radius: 3px;
}
.pin.idle .pin-dot {
  background: var(--el-color-success);
}
.pin.playing .pin-dot {
  background: var(--el-color-warning);
}
.pin.off .pin-dot {
  background: var(--el-text-color-placeholder);
}
.pin.gone .pin-dot {
  background: var(--el-color-danger);
}

.map-note,
.dlg-note {
  margin: 8px 0 0;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.pick-bar {
  display: flex;
  gap: 10px;
  align-items: center;
  margin-bottom: 8px;
}
.muted {
  font-size: 12px;
  color: var(--el-text-color-placeholder);
}
</style>
