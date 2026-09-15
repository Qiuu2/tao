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

  # 加终端是右键点在图上，不是工具栏按钮

  原来「添加终端」在工具栏：选完一批，全堆在左上角，再一台台拖到位置上。
  可实际用的时候，人是先想好「这台在三楼东头」才去加的 —— 位置是这个动作的
  一部分，不该是加完之后的第二步。所以改成在目标位置点右键：菜单里选终端，
  落点就是刚才右键的那个点，一步到位。移出地图同理，在终端上点右键。

  # 状态用图标区分，不只是颜色

  原来全是圆点，靠颜色分状态。颜色在投影仪上、在色觉障碍的人眼里都可能分不开，
  而且「橙色」只能表达一个笼统的「在播」—— 定时播放、对讲、寻呼在现场是三件
  完全不同的事。改成一状态一图标（形状可分），颜色只作辅助，
  右下角配一张图例。口径与终端列表的 taskstate 一致，见 pinKind()。
-->
<template>
  <div class="map-page">
    <!-- 左：底图清单 -->
    <div class="map-side">
      <div class="side-head">
        <span class="side-title">{{ $t("mapView.baseMaps") }}</span>
        <el-button :icon="Plus" size="small" text :disabled="!canEdit || tablesMissing" @click="onCreate">
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
        <div v-if="current" ref="canvasRef" class="canvas" :style="{ aspectRatio: aspect }" @contextmenu="onCanvasMenu">
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

          <!--
            终端点：按住拖动改位置，点右键移出地图。
            图标按状态换（pinKind），颜色只是辅助 —— 见文件头注释。
          -->
          <div
            v-for="p in placements"
            :key="p.terminalId"
            class="pin"
            :class="[`k-${pinKind(p)}`, { dragging: dragId === p.terminalId }]"
            :style="{ left: p.x + '%', top: p.y + '%' }"
            :title="pinTitle(p)"
            :data-kind="pinKind(p)"
            :data-terminal-id="p.terminalId"
            @pointerdown="onPinDown($event, p)"
            @contextmenu.stop="onPinMenu($event, p)"
          >
            <span class="pin-icon">
              <el-icon><component :is="KIND_ICON[pinKind(p)]" /></el-icon>
            </span>
            <span class="pin-label">{{ p.missing ? $t("mapView.deletedNo", { id: p.terminalId }) : p.terminalname }}</span>
          </div>

          <!--
            图例钉在画布右下角。没有图例，「一状态一图标」就变成了让人猜谜 ——
            只画出图上真的出现过的那几种，否则七个图标一字排开比地图本身还显眼。
          -->
          <div v-if="legendKinds.length" class="legend">
            <span class="legend-title">{{ $t("mapView.legend") }}</span>
            <span v-for="k in legendKinds" :key="k" class="legend-item" :class="`k-${k}`">
              <el-icon><component :is="KIND_ICON[k]" /></el-icon>
              {{ $t(KIND_LABEL[k]) }}
            </span>
          </div>
        </div>
        <!--
          建表脚本没跑。留一块**不会自己消失**的说明 —— 原来只弹一句红条，
          几秒钟就没了，人根本抓不住，表现就成了「地图打不开」。
        -->
        <div v-else-if="tablesMissing" class="need-sql">
          <el-icon class="need-sql-icon"><WarningFilled /></el-icon>
          <p class="need-sql-title">{{ $t("mapView.needTables") }}</p>
          <p class="need-sql-tip">{{ $t("mapView.needTablesTip") }}</p>
          <code class="need-sql-cmd">mysql -uroot audioserver &lt; db/map_tables.sql</code>
          <el-button class="need-sql-retry" :icon="Refresh" @click="reloadAll">{{ $t("mapView.retryAfterSql") }}</el-button>
        </div>
        <el-empty v-else-if="!loading" :description="$t('mapView.pickOrCreate')" />
      </div>

      <p class="map-note">{{ $t("mapView.dragHint") }}</p>
    </div>

    <!--
      右键菜单。自己画一个而不是找组件：菜单只有两三项，
      而画布是 overflow:hidden 的 —— 菜单必须用 fixed 定位脱出去，
      否则贴着右下角点右键时菜单会被裁掉一半。
    -->
    <div v-if="ctx.visible" class="ctx-menu" :style="{ left: ctx.left + 'px', top: ctx.top + 'px' }" @contextmenu.prevent>
      <div v-if="ctx.pin" class="ctx-head" :title="ctx.pin.terminalname">
        {{ ctx.pin.missing ? $t("mapView.deletedNo", { id: ctx.pin.terminalId }) : ctx.pin.terminalname }}
      </div>
      <button v-if="ctx.pin" class="ctx-item danger" @click="ctxRemove">
        <el-icon><Delete /></el-icon>{{ $t("mapView.removeHere") }}
      </button>
      <button v-else class="ctx-item" @click="ctxAddHere">
        <el-icon><Plus /></el-icon>{{ $t("mapView.addHere") }}
      </button>
      <button class="ctx-item" @click="ctxRefresh">
        <el-icon><Refresh /></el-icon>{{ $t("mapView.ctxRefresh") }}
      </button>
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
        <span class="muted">{{ $t("mapView.pickerNote") }} · {{ $t("mapView.pickerNoteHere") }}</span>
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
import {
  Bell,
  CircleCloseFilled,
  Delete,
  EditPen,
  Headset,
  Microphone,
  Picture,
  PictureFilled,
  Plus,
  Refresh,
  Search,
  SwitchButton,
  Upload as UploadIcon,
  VideoPlay,
  WarningFilled
} from "@element-plus/icons-vue";
import { ElMessage, ElMessageBox, type UploadUserFile } from "element-plus";
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from "vue";

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
import { useDbChanges } from "@/hooks/useDbChanges";
import { useAuthStore } from "@/stores/modules/auth";
import { useGlobalStore } from "@/stores/modules/global";
import { useUserStore } from "@/stores/modules/user";

const { t } = useI18n();
const authStore = useAuthStore();
const userStore = useUserStore();
const globalStore = useGlobalStore();

// 写操作跟着终端管理那把钥匙（terminalpriv）——
// 把终端摆到图上本质上是终端配置，不另起一套权限。
const canEdit = computed(() => !!(authStore.authButtonListGet as any)?.map?.edit);

const loading = ref(false);
const uploading = ref(false);
/**
 * 建表脚本还没跑。
 *
 * 这两张表 htweb 自己建不了（运行账号没有 DDL），要管理员单独跑一次
 * db/map_tables.sql。现场每次上新服务器都可能漏，而漏了的表现就是
 * 「地图打不开」—— 原来只弹一句几秒钟就消失的红条，人根本抓不住。
 * 所以在页面上留一块说清楚：缺什么、跑哪个脚本。
 */
const tablesMissing = ref(false);
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
  let data: MapImage[] | undefined;
  try {
    ({ data } = await getMapsApi());
    tablesMissing.value = false;
  } catch (e: any) {
    // 后端对「表还没建」回的是 40001 + 一句说明（见 mapview.ErrTableMissing）。
    // 认这一种，其余的错交给 axios 拦截器照常弹提示。
    const msg = String(e?.msg ?? e?.message ?? e);
    if (!msg.includes("map_tables.sql")) throw e;
    tablesMissing.value = true;
    maps.value = [];
    currentId.value = 0;
    placements.value = [];
    return;
  }
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

/* ---------------- 终端点的状态与图标 ---------------- */

/**
 * 一状态一图标。
 *
 * # 为什么不只用颜色
 *
 * 原来全是圆点靠颜色分。颜色在投影仪上、在色觉障碍的人眼里都可能分不开；
 * 更要紧的是「橙色 = 在播」把三件完全不同的事压成了一件 ——
 * 定时播放、有人在对讲、正在寻呼，现场处理方式完全不一样。
 *
 * # 分组口径
 *
 * taskstate 有 13 个取值（见终端列表的 TASK_STATE_TEXT），全画成 13 个图标
 * 没人记得住。按「现场要怎么反应」归成几类，与终端列表的文案同源：
 *
 *   定时播放(1,12) 点播(3) 选播(4) 本地扩音(7) USB播放(8) → 在播
 *   正在对讲(2) 准备对讲(6) 请求对讲(9) 被请求对讲(10)     → 对讲
 *   寻呼(5) 播放寻呼(11)                                   → 寻呼
 *   准备就绪(0)                                            → 空闲
 *
 * # 判断顺序不能换
 *
 * 记录失效 → 离线 → 在播/对讲/寻呼 → 设备已停止 → 空闲。
 * 「设备已停止」必须排在任务状态之后：一台正在播的终端显然不是停止的，
 * 反过来判就会把在播的画成停止。
 */
type PinKind = "gone" | "off" | "playing" | "intercom" | "paging" | "stopped" | "idle";

const TASK_PLAYING = new Set([1, 3, 4, 7, 8, 12]);
const TASK_INTERCOM = new Set([2, 6, 9, 10]);
const TASK_PAGING = new Set([5, 11]);

const pinKind = (p: MapPlacement): PinKind => {
  if (p.missing) return "gone";
  if (p.netstate !== 1) return "off";
  if (TASK_PLAYING.has(p.taskstate)) return "playing";
  if (TASK_INTERCOM.has(p.taskstate)) return "intercom";
  if (TASK_PAGING.has(p.taskstate)) return "paging";
  if (p.devicestate !== 1) return "stopped";
  return "idle";
};

const KIND_ICON: Record<PinKind, any> = {
  gone: WarningFilled,
  off: CircleCloseFilled,
  playing: VideoPlay,
  intercom: Microphone,
  paging: Bell,
  stopped: SwitchButton,
  idle: Headset
};

const KIND_LABEL: Record<PinKind, string> = {
  gone: "mapView.stGone",
  off: "mapView.stOffline",
  playing: "mapView.stPlaying",
  intercom: "mapView.stIntercom",
  paging: "mapView.stPaging",
  stopped: "mapView.stStopped",
  idle: "mapView.stIdle"
};

// 图例只列图上真的出现过的那几种，按固定顺序 ——
// 七个图标一字排开会比地图本身还显眼，而且大多数时候图上只有两三种状态。
const KIND_ORDER: PinKind[] = ["idle", "playing", "intercom", "paging", "stopped", "off", "gone"];
const legendKinds = computed(() => {
  const seen = new Set(placements.value.map(pinKind));
  return KIND_ORDER.filter(k => seen.has(k));
});

// 任务状态的完整文案，与终端列表同一套 term.* 词条 —— 图标归了类，
// 鼠标悬停时还是要能看到确切是哪一种。
const TASK_TEXT_KEY: Record<number, string> = {
  0: "term.ready",
  1: "term.timedPlay",
  2: "term.inIntercom",
  3: "term.onDemand",
  4: "term.selectivePlay",
  5: "term.paging",
  6: "term.readyIntercom",
  7: "term.localAmp",
  8: "term.usbPlay",
  9: "term.requestIntercom",
  10: "term.intercomRequested",
  11: "term.playPaging",
  12: "term.timedPlay"
};

const pinTitle = (p: MapPlacement) => {
  if (p.missing) return t("mapView.deletedTip", { id: p.terminalId });
  const net = p.netstate === 1 ? t("common.online") : t("common.offline");
  const key = TASK_TEXT_KEY[p.taskstate];
  const task = p.netstate !== 1 ? t("term.disconnect") : key ? t(key) : t("term.stateOf", { name: p.taskstate });
  const dev = p.devicestate === 1 ? t("common.started") : t("common.stopped");
  return `${p.terminalname} · #${p.terminalId}\n${p.typeName} · ${p.ip}\n${net} · ${task} · ${dev}\n${t("common.volume")} ${p.volume}`;
};

/* ---------------- 摆终端 ---------------- */

/**
 * 拖动摆放。
 *
 * ⚠ 用 pointer 事件 + setPointerCapture，不用 mousedown/mousemove：
 *   鼠标拖出画布再松手时，mouseup 落在别的元素上，点就永远粘在手上。
 *   捕获之后无论松在哪里都回到这个元素，也顺带支持了触摸屏。
 */
const dragId = ref(0);
let dragMoved = false;

/** 视口坐标 → 画布内百分比。拖动和右键两条路都走这里，省得两份换算跑偏。 */
const pctOfClient = (clientX: number, clientY: number) => {
  const box = canvasRef.value?.getBoundingClientRect();
  if (!box || !box.width || !box.height) return null;
  const x = ((clientX - box.left) / box.width) * 100;
  const y = ((clientY - box.top) / box.height) * 100;
  return { x: Math.min(100, Math.max(0, x)), y: Math.min(100, Math.max(0, y)) };
};

const pctOf = (e: PointerEvent) => pctOfClient(e.clientX, e.clientY);

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

/* ---------------- 右键菜单 ---------------- */

/**
 * 右键菜单。
 *
 * 菜单本身用 fixed 定位（clientX/clientY 就是视口坐标，直接能用），
 * 但**要落哪个点**记的是画布内的百分比 —— 菜单弹出到点中终端之间，
 * 页面可能滚动、侧栏可能收起，等到真要写库时再去算就晚了。
 */
const ctx = reactive({
  visible: false,
  left: 0,
  top: 0,
  /** 右键点在画布上的百分比位置，加终端就落在这儿 */
  at: { x: 50, y: 50 },
  /** 右键点在某个终端上时是它，点在空白处是 null */
  pin: null as MapPlacement | null
});

// 菜单大概的尺寸，用来避免贴着右边/下边弹出时被视口切掉
const CTX_W = 180;
const CTX_H = 132;

const openCtx = (e: MouseEvent, pin: MapPlacement | null) => {
  // 只读用户、还没选底图时**不拦**浏览器自己的右键菜单 ——
  // 拦了又不给菜单，右键就成了一个「按下去什么都不发生」的死操作。
  if (!canEdit.value || !current.value) return;
  const at = pctOfClient(e.clientX, e.clientY);
  if (!at) return;
  e.preventDefault();
  ctx.at = at;
  ctx.pin = pin;
  ctx.left = Math.min(e.clientX, window.innerWidth - CTX_W);
  ctx.top = Math.min(e.clientY, window.innerHeight - CTX_H);
  ctx.visible = true;
};

const closeCtx = () => (ctx.visible = false);

const onCanvasMenu = (e: MouseEvent) => openCtx(e, null);
const onPinMenu = (e: MouseEvent, p: MapPlacement) => openCtx(e, p);

const ctxAddHere = () => {
  closeCtx();
  openPicker(ctx.at);
};

const ctxRemove = async () => {
  const p = ctx.pin;
  closeCtx();
  // 点「取消」时 ElMessageBox 是 reject 的，不接住就变成一条未捕获的 rejection。
  // 用户改主意不是错误。
  if (p) await removeOne(p).catch(() => undefined);
};

const ctxRefresh = async () => {
  closeCtx();
  await reloadAll();
};

// 点别处、按 Esc、滚动都关掉菜单。
// ⚠ pointerdown 而不是 click：菜单项自己的 click 要先跑完，
//   用 click 收尾的话在冒泡到 document 时菜单已经被关了，点不中。
const onDocDown = (e: Event) => {
  if (!ctx.visible) return;
  if ((e.target as HTMLElement)?.closest?.(".ctx-menu")) return;
  closeCtx();
};
const onEsc = (e: KeyboardEvent) => {
  if (e.key === "Escape") closeCtx();
};

onMounted(() => {
  document.addEventListener("pointerdown", onDocDown, true);
  document.addEventListener("keydown", onEsc);
  window.addEventListener("scroll", closeCtx, true);
  window.addEventListener("resize", closeCtx);
});
onBeforeUnmount(() => {
  document.removeEventListener("pointerdown", onDocDown, true);
  document.removeEventListener("keydown", onEsc);
  window.removeEventListener("scroll", closeCtx, true);
  window.removeEventListener("resize", closeCtx);
});

/** 把终端从图上拿下来。只删摆放记录，终端本身一根毛都不动。 */
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
  picked: [] as TerminalRow[],
  /** 右键点的那个位置（画布内百分比），选中的终端就落在这儿 */
  at: { x: 50, y: 50 }
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

const openPicker = async (at: { x: number; y: number }) => {
  picker.at = at;
  picker.visible = true;
  picker.keyword = "";
  picker.picked = [];
  // 一次性拉全量：现场终端量级是几百到几千，够用；
  // 分页会让「已上图的滤掉」失真 —— 滤完可能整页空着，看着像没有终端可选。
  const { data } = await getTerminalListApi({ pageNum: 1, pageSize: 3000, groupId: 0 } as any);
  picker.all = (data as any)?.list ?? [];
};

/**
 * 确定：把选中的终端落到**刚才右键的那个点**上。
 *
 * 一次选多台时不能全压在同一个坐标 —— 完全重叠的点拖不开、也数不出有几台。
 * 所以第一台正落在右键的位置（人指的就是这儿，必须精确），其余的绕着它
 * 撒成一小圈：半径 4%、每台转 60°，第七台起半径加一档。
 *
 * ⚠ 结果要夹在 2%~98%：右键点在边角上时，撒出去的那几台会算到画布外面，
 *   存进去就是个永远拖不回来的点。
 */
const RING_R = 4;
const RING_N = 6;

const submitPicker = async () => {
  picker.saving = true;
  try {
    const clamp = (v: number) => Math.min(98, Math.max(2, v));
    let i = 0;
    for (const row of picker.picked) {
      let x = picker.at.x;
      let y = picker.at.y;
      if (i > 0) {
        const ring = Math.ceil(i / RING_N);
        const ang = ((i - 1) % RING_N) * (Math.PI / 3);
        x += Math.cos(ang) * RING_R * ring;
        y += Math.sin(ang) * RING_R * ring;
      }
      await placeMapTerminalApi(currentId.value, row.id, clamp(x), clamp(y));
      i++;
    }
    ElMessage.success(picker.picked.length > 1 ? t("mapView.placedOk", { n: picker.picked.length }) : t("mapView.placedHere"));
    picker.visible = false;
    await Promise.all([loadPlacements(), loadMaps()]);
  } finally {
    picker.saving = false;
  }
};

/*
  图标要跟着状态走，就得知道状态变了。

  终端的在线/离线、任务状态都是**后台 C 服务**直接写进 terminal 表的，
  它不会来通知这个页面 —— 所以盯着库看，那张表一变就重新拉一次摆放。
  见 hooks/useDbChanges.ts 与 server/internal/dbwatch。

  ⚠ 三种时候不能刷：正在拖、右键菜单开着、选终端的对话框开着。
    刷新会整个换掉 placements 数组，正拖着的那个点会跳回原位（拖到一半白拖），
    菜单里记着的那台也会变成另一个对象。这几种都是人正在操作的时刻，
    晚两秒再刷没有任何损失。

  ⚠ 只重拉摆放，不重拉底图清单：底图不会被 C 服务改，
    每次都跟着查一遍纯属白花一次请求。
*/
useDbChanges(["terminal"], () => {
  if (dragId.value || ctx.visible || picker.visible) return;
  void loadPlacements();
});

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

/*
  图标底下垫一个白圈：底图五花八门（航拍图、CAD 线稿、深色平面图），
  图标直接贴在上面经常看不清。白底 + 细边 + 投影，压在什么图上都读得出来。
*/
.pin-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  background: #ffffff;
  border: 2px solid currentcolor;
  border-radius: 50%;
  box-shadow: 0 1px 4px rgb(0 0 0 / 35%);
  .el-icon {
    font-size: 14px;
  }
}
.pin-label {
  max-width: 110px;
  padding: 0 4px;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 12px;
  line-height: 16px;
  color: var(--el-text-color-primary);
  white-space: nowrap;
  background: rgb(255 255 255 / 78%);
  border-radius: 3px;
}

/*
  一状态一颜色，但**颜色只是辅助** —— 形状（图标）才是主要区分手段，
  见文件头注释。图例里用的是同一组 .k-* 类，两边不会走偏。
*/
.k-idle {
  color: var(--el-color-success);
}
.k-playing {
  color: var(--el-color-warning);
}
.k-intercom {
  color: var(--el-color-primary);
}
.k-paging {
  color: #8957e5;
}
.k-stopped {
  color: var(--el-text-color-secondary);
}
.k-off {
  color: var(--el-text-color-placeholder);
}
.k-gone {
  color: var(--el-color-danger);
}

/* 图例：钉在画布右下角，不抢地图本身的注意力 */
.legend {
  position: absolute;
  right: 8px;
  bottom: 8px;
  display: flex;
  flex-wrap: wrap;
  gap: 4px 10px;
  align-items: center;
  max-width: 70%;
  padding: 5px 9px;
  font-size: 12px;
  background: rgb(255 255 255 / 86%);
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 4px;
}
.legend-title {
  color: var(--el-text-color-secondary);
}
.legend-item {
  display: flex;
  gap: 3px;
  align-items: center;
  .el-icon {
    font-size: 13px;
  }
}

/* 缺表时那块说明 */
.need-sql {
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: center;
  justify-content: center;
  width: 100%;
  padding: 48px 16px;
  text-align: center;
}
.need-sql-icon {
  font-size: 40px;
  color: var(--el-color-warning);
}
.need-sql-title {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
}
.need-sql-tip {
  max-width: 520px;
  margin: 0;
  font-size: 13px;
  color: var(--el-text-color-secondary);
}
.need-sql-cmd {
  padding: 6px 12px;
  font-family: Menlo, Consolas, monospace;
  font-size: 13px;
  color: var(--el-text-color-primary);
  word-break: break-all;
  background: var(--el-fill-color-light);
  border-radius: 4px;
}
.need-sql-retry {
  margin-top: 4px;
}

/*
  右键菜单。fixed 定位是必须的 —— 画布是 overflow:hidden，
  菜单画在里面的话，贴着边角点右键会被裁掉一半。
*/
.ctx-menu {
  position: fixed;
  z-index: 2200;
  min-width: 172px;
  padding: 4px;
  background: var(--el-bg-color-overlay);
  border: 1px solid var(--el-border-color-light);
  border-radius: 4px;
  box-shadow: var(--el-box-shadow-light);
}
.ctx-head {
  max-width: 220px;
  padding: 4px 10px 6px;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 12px;
  color: var(--el-text-color-secondary);
  white-space: nowrap;
  border-bottom: 1px solid var(--el-border-color-lighter);
}
.ctx-item {
  display: flex;
  gap: 7px;
  align-items: center;
  width: 100%;
  padding: 7px 10px;
  font-size: 13px;
  color: var(--el-text-color-primary);
  cursor: pointer;
  background: none;
  border: 0;
  border-radius: 3px;
  &:hover {
    background: var(--el-fill-color-light);
  }
  &.danger {
    color: var(--el-color-danger);
  }
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
