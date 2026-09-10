<!--
  看板 · 首页    排版参照 docs/image/2.png

  四块：
    顶部   快捷入口（新增/编辑，点一下直接进对应页面）
    上左   设备概况（环形图 + 总数/在线/离线）
    上右   快捷任务（绑定文件广播任务）
    中左   服务器性能（CPU/内存/磁盘 + 网络流量）
    中右   紧急广播（地震/疏散/警戒/消防 四个固定槽位）
    下方   浏览任务

  图表用手写 SVG 而不是图表库：
  环形图和三个仪表都只是一段圆弧，用 stroke-dasharray 就够了，
  这样能精确控制扇区之间那 2px 的留白，配色也直接吃主题变量、跟着深浅色走。

  配色说明（这一版是跑过校验的，别随手改 hex）：
    · 环形图 6 个扇区用固定顺序的分类色，**不循环取色**；
      浅色主题下 aqua/yellow/magenta 三个对白底不足 3:1，
      所以图例里必须带文字标签和数值 —— 颜色不单独承载信息。
    · 三个仪表按阈值上状态色（<70 正常 / 70~90 偏高 / ≥90 告警），
      数值和名称始终可见，同样不靠颜色单独表意。
-->
<template>
  <div class="dash">
    <!-- ===== 顶部快捷入口 ===== -->
    <div class="dash-toolbar">
      <el-button size="small" :icon="Plus" :disabled="!isSuper" @click="openShortcut()">{{ $t("common.create") }}</el-button>
      <el-button size="small" :icon="EditPen" :disabled="!isSuper || !cfg?.shortcuts.length" @click="editMode = !editMode">
        {{ $t("common.edit") }}
      </el-button>
      <div class="chips">
        <el-tag
          v-for="(sc, i) in cfg?.shortcuts ?? []"
          :key="i"
          class="chip"
          effect="plain"
          :closable="editMode && isSuper"
          @click="!editMode && go(sc.path)"
          @close="removeShortcut(i)"
        >
          {{ sc.label }}
        </el-tag>
        <span v-if="!cfg?.shortcuts.length" class="muted small">
          {{ isSuper ? $t("dash.noShortcutSuper") : $t("dash.noShortcut") }}
        </span>
      </div>
    </div>

    <div class="dash-row">
      <!-- ===== 设备概况 ===== -->
      <section class="panel">
        <header class="panel-hd">{{ $t("dash.deviceOverview") }}</header>
        <div class="donut-wrap">
          <svg :viewBox="`0 0 ${DONUT} ${DONUT}`" class="donut" role="img" :aria-label="$t('dash.deviceMixLabel')">
            <circle :cx="C" :cy="C" :r="R" class="donut-track" :stroke-width="TH" />
            <circle
              v-for="(s, i) in donutArcs"
              :key="i"
              :cx="C"
              :cy="C"
              :r="R"
              fill="none"
              :stroke="s.color"
              :stroke-width="TH"
              :stroke-dasharray="`${s.len} ${CIRC - s.len}`"
              :stroke-dashoffset="-s.offset"
              :transform="`rotate(-90 ${C} ${C})`"
            />
            <text :x="C" :y="C - 6" class="donut-cap">{{ $t("dash.deviceTotal") }}</text>
            <text :x="C" :y="C + 22" class="donut-num">{{ overview?.total ?? 0 }}</text>
          </svg>
          <ul class="legend">
            <li v-for="(t, i) in overview?.byType ?? []" :key="t.type">
              <i class="dot" :style="{ background: seriesColor(i) }" />
              <span class="lg-name">{{ t.type }}</span>
              <span class="lg-val">{{ t.total }}</span>
            </li>
            <li v-if="!overview?.byType.length" class="muted small">{{ $t("dash.noTerminals") }}</li>
          </ul>
        </div>
        <div class="stat-row">
          <div class="stat stat-total">
            <div class="stat-k">{{ $t("dash.deviceTotal") }}</div>
            <div class="stat-v">{{ overview?.total ?? 0 }}</div>
          </div>
          <div class="stat stat-on">
            <div class="stat-k">{{ $t("common.online") }}</div>
            <div class="stat-v">{{ overview?.online ?? 0 }}</div>
          </div>
          <div class="stat stat-off">
            <div class="stat-k">{{ $t("common.offline") }}</div>
            <div class="stat-v">{{ overview?.offline ?? 0 }}</div>
          </div>
        </div>
      </section>

      <!-- ===== 快捷任务 ===== -->
      <section class="panel">
        <header class="panel-hd">
          {{ $t("dash.quickTasks") }}
          <div class="hd-actions">
            <!--
              :80 这两个按钮直接弹出文件广播的新建/编辑表单。
              我们不在首页重做一遍那张大表单，而是跳到文件广播页并带上 action=create，
              由那一页自己把新建弹窗打开 —— 只有一处表单，改一次就够。
            -->
            <el-button size="small" type="primary" @click="goTask('create')">{{ $t("dash.newFileTask") }}</el-button>
            <el-button size="small" type="primary" plain @click="goTask('')">{{ $t("dash.editQuickTasks") }}</el-button>
            <el-button size="small" type="primary" :disabled="!isSuper" @click="openQuickBind">{{
              $t("dash.bindQuickTasks")
            }}</el-button>
          </div>
        </header>
        <div class="panel-bd">
          <div v-if="!cfg?.quickTasks.length" class="empty">{{ $t("dash.noQuickTasks") }}</div>
          <div v-else class="task-grid">
            <div v-for="t in cfg.quickTasks" :key="t.taskId" class="task-card">
              <div class="task-name" :class="{ danger: t.missing }">{{ t.taskName }}</div>
              <div class="task-sub">
                <template v-if="t.missing">ID {{ t.taskId }}</template>
                <template v-else>
                  {{ t.playtime }} ·
                  <span :class="['task-state', { running: isRunning(t) }]">
                    <i v-if="isRunning(t)" class="run-dot" />{{ stateTextOf(t) }}
                  </span>
                </template>
              </div>
              <div class="task-ops">
                <el-button
                  size="small"
                  type="success"
                  :disabled="t.missing"
                  :loading="pendingStart.has(t.taskId)"
                  @click="runTask(t.taskId)"
                >
                  {{ $t("dash.run") }}
                </el-button>
                <el-button size="small" type="danger" :disabled="t.missing" @click="stopTask(t.taskId)">{{
                  $t("dash.stop")
                }}</el-button>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>

    <div class="dash-row">
      <!-- ===== 服务器性能 ===== -->
      <section class="panel">
        <header class="panel-hd">
          {{ $t("dash.serverPerf") }}
          <span class="hd-note">{{ perf?.os || "—" }}</span>
        </header>
        <div class="gauge-row">
          <div v-for="g in gauges" :key="g.key" class="gauge-box">
            <svg viewBox="0 0 100 100" class="gauge" role="img" :aria-label="`${g.name} ${g.value}%`">
              <circle cx="50" cy="50" r="40" class="gauge-track" stroke-width="7" />
              <circle
                cx="50"
                cy="50"
                r="40"
                fill="none"
                :stroke="g.color"
                stroke-width="7"
                stroke-linecap="round"
                :stroke-dasharray="`${(g.value / 100) * GCIRC} ${GCIRC}`"
                transform="rotate(-90 50 50)"
              />
              <text x="50" y="55" class="gauge-num">{{ g.value }}%</text>
            </svg>
            <div class="gauge-name">{{ g.name }}</div>
            <div class="gauge-sub">{{ g.sub }}</div>
          </div>
        </div>
        <div class="net-box">
          <div class="net-hd">
            {{ $t("dash.network") }}
            <span class="hd-note">{{ perf?.iface || "—" }}</span>
          </div>
          <div class="net-line">{{ $t("dash.rx") }}{{ rate(perf?.rxRate) }}</div>
          <div class="net-line">{{ $t("dash.tx") }}{{ rate(perf?.txRate) }}</div>
          <div v-if="perf?.warmingUp" class="muted small">{{ $t("dash.firstSample") }}</div>
        </div>
      </section>

      <!-- ===== 紧急广播 ===== -->
      <section class="panel">
        <header class="panel-hd">
          {{ $t("dash.emergency") }}
        </header>
        <div class="panel-bd">
          <div class="emg-grid">
            <div v-for="s in cfg?.emergency ?? []" :key="s.key" class="emg-card">
              <span class="emg-name">{{ s.name }}</span>
              <span class="emg-ops">
                <el-button size="small" type="success" :loading="emgBusy === s.key" @click="playEmergency(s)">
                  {{ $t("dash.run") }}
                </el-button>
                <el-button size="small" type="danger" :loading="emgBusy === s.key + ':stop'" @click="stopEmergency(s)">
                  {{ $t("dash.stop") }}
                </el-button>
              </span>
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- ===== 浏览任务 ===== -->
    <section class="panel">
      <div class="filter-bar">
        <span class="fl">{{ $t("dash.filterFolder") }}</span>
        <el-select v-model="bq.folderId" size="small" style="width: 130px" @change="loadTasks">
          <el-option :label="$t('common.all')" :value="0" />
          <el-option v-for="f in folders" :key="f.id" :label="f.name" :value="f.id" />
        </el-select>
        <span class="fl">{{ $t("dash.filterWeekday") }}</span>
        <el-select v-model="bq.weekday" size="small" style="width: 120px" @change="loadTasks">
          <el-option :label="$t('dash.today')" :value="0" />
          <el-option v-for="(w, i) in weekLabels" :key="i" :label="w" :value="i + 1" />
        </el-select>
        <span class="fl">{{ $t("dash.filterType") }}</span>
        <el-select v-model="bq.autoMode" size="small" style="width: 120px" @change="loadTasks">
          <el-option :label="$t('common.all')" :value="0" />
          <el-option :label="$t('dash.autoTask')" :value="1" />
          <el-option :label="$t('dash.manualTask')" :value="2" />
        </el-select>
      </div>

      <!-- :80 这里是「当天启用 / 当天停用」两个按钮，不是页签；多留一个「全部」 -->
      <div class="scope-bar">
        <el-radio-group v-model="bq.scope" size="small" @change="loadTasks">
          <el-radio-button value="enabled">{{ $t("dash.onToday") }}</el-radio-button>
          <el-radio-button value="disabled">{{ $t("dash.offToday") }}</el-radio-button>
          <el-radio-button value="all">{{ $t("common.all") }}</el-radio-button>
        </el-radio-group>
      </div>

      <el-table :data="tasks" v-loading="tasksLoading" size="small" :empty-text="$t('common.noData')">
        <el-table-column prop="index" :label="$t('common.index')" width="70" />
        <el-table-column prop="taskName" :label="$t('dash.taskName')" min-width="160" show-overflow-tooltip />
        <el-table-column prop="folderName" :label="$t('dash.folder')" width="130" show-overflow-tooltip />
        <el-table-column prop="cycleText" :label="$t('dash.weekdays')" width="140" />
        <el-table-column prop="playtime" :label="$t('dash.playTime')" width="110" />
        <el-table-column :label="$t('common.status')" width="110">
          <template #default="{ row }">
            <el-tag :type="row.enabledToday ? 'success' : 'info'" size="small" effect="plain">
              {{ row.enabledToday ? $t("dash.onToday") : $t("dash.offToday") }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="startdate" :label="$t('common.startDate')" width="110" />
        <el-table-column prop="enddate" :label="$t('common.endDate')" width="110" />
        <el-table-column prop="terminals" :label="$t('dash.terminalCount')" width="100" />
        <el-table-column :label="$t('dash.taskActions')" width="150" fixed="right">
          <template #default="{ row }">
            <el-button type="success" link @click="runTask(row.taskId)">{{ $t("dash.run") }}</el-button>
            <el-button type="danger" link @click="stopTask(row.taskId)">{{ $t("dash.stop") }}</el-button>
            <el-button type="primary" link @click="go('/task')">{{ $t("common.view") }}</el-button>
          </template>
        </el-table-column>
      </el-table>
      <el-pagination
        class="pager"
        layout="total, prev, pager, next"
        :total="tasksTotal"
        :page-size="bq.pageSize"
        :current-page="bq.pageNum"
        @current-change="
          (n: number) => {
            bq.pageNum = n;
            loadTasks();
          }
        "
      />
    </section>

    <!-- 新增快捷入口 -->
    <el-dialog v-model="sd.visible" :title="$t('dash.newShortcut')" width="460px">
      <el-form label-width="90px">
        <el-form-item :label="$t('dash.shortcutPath')">
          <el-select v-model="sd.path" class="fill" @change="onPickPage">
            <el-option v-for="p in pageOptions" :key="p.path" :label="p.label" :value="p.path" />
          </el-select>
        </el-form-item>
        <el-form-item :label="$t('dash.shortcutLabel')">
          <el-input v-model="sd.label" maxlength="12" show-word-limit />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="sd.visible = false">{{ $t("common.cancel") }}</el-button>
        <el-button type="primary" :loading="sd.busy" @click="addShortcut">{{ $t("common.confirm") }}</el-button>
      </template>
    </el-dialog>

    <!-- 绑定快捷任务 -->
    <el-dialog v-model="qd.visible" :title="$t('dash.bindQuickTasks')" width="560px">
      <el-alert type="info" :closable="false" class="mb12">
        <!-- 整句一个键。拆成「只能绑定」+ 组件名 + 「任务，最多 12 个」去翻，
             中文能拼出来，英文语序对不上，只会拼出一句不通的话。 -->
        {{ $t("dash.quickTaskDialogTip", { kind: $t("menu.task") }) }}
      </el-alert>
      <el-select v-model="qd.ids" multiple filterable :placeholder="$t('dash.pickFileTask')" class="fill">
        <el-option v-for="t in fileTasks" :key="t.taskid" :label="t.taskname" :value="t.taskid" />
      </el-select>
      <template #footer>
        <el-button @click="qd.visible = false">{{ $t("common.cancel") }}</el-button>
        <el-button type="primary" :loading="qd.busy" @click="saveQuick">{{ $t("common.confirm") }}</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="home">
import { useI18n } from "vue-i18n";
import { computed, onMounted, onUnmounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { ElMessage, ElMessageBox } from "element-plus";
import { EditPen, Plus } from "@element-plus/icons-vue";
import {
  getDashConfigApi,
  getDashOverviewApi,
  getDashPerfApi,
  getDashTasksApi,
  playEmergencyApi,
  saveQuickTasksApi,
  saveShortcutsApi,
  type BrowseItem,
  type DashConfig,
  type EmergencySlot,
  type Overview,
  type Perf
} from "@/api/modules/dashboard";
import { controlTaskApi, getTaskFolderTreeApi, getTaskListApi, type TaskRow } from "@/api/modules/task";
import { useUserStore } from "@/stores/modules/user";

// 脚本里拼的文案用 t()；模板里的 $t 不用引入
const { t } = useI18n();

const router = useRouter();
const userStore = useUserStore();
const isSuper = computed(() => (userStore.userInfo as any)?.id === 1);

/* ---------------- 配色（跑过 CVD 校验，勿随手改） ---------------- */

// 分类色：固定顺序取用，不循环。第 9 类之后归到「其它」而不是再生成新色。
const SERIES_LIGHT = ["#2a78d6", "#eb6834", "#1baf7a", "#eda100", "#e87ba4", "#008300", "#4a3aa7", "#e34948"];
const SERIES_DARK = ["#3987e5", "#d95926", "#199e70", "#c98500", "#d55181", "#008300", "#9085e9", "#e66767"];
// 状态色：好/偏高/告警。固定语义，绝不拿来当第 N 个分类色。
const STATUS = { good: "#0ca30c", warning: "#fab219", critical: "#d03b3b" };

const isDark = ref(false);
const seriesColor = (i: number) => (isDark.value ? SERIES_DARK : SERIES_LIGHT)[i % 8];

const syncTheme = () => {
  const el = document.documentElement;
  isDark.value =
    el.getAttribute("data-theme") === "dark" ||
    el.classList.contains("dark") ||
    (!el.getAttribute("data-theme") && window.matchMedia("(prefers-color-scheme: dark)").matches);
};

/* ---------------- 环形图几何 ---------------- */

const DONUT = 220;
const C = DONUT / 2;
const TH = 26;
const R = C - TH / 2 - 6;
const CIRC = 2 * Math.PI * R;
// 扇区之间留 2px 底色缝，视觉上把相邻两段分开，不靠颜色差硬分
const GAP = 2;

const overview = ref<Overview | null>(null);

const donutArcs = computed(() => {
  const list = overview.value?.byType ?? [];
  const total = list.reduce((s, t) => s + t.total, 0);
  if (!total) return [];
  let acc = 0;
  return list.map((t, i) => {
    const full = (t.total / total) * CIRC;
    const len = Math.max(full - GAP, 1);
    const arc = { color: seriesColor(i), len, offset: acc };
    acc += full;
    return arc;
  });
});

/* ---------------- 性能仪表 ---------------- */

const GCIRC = 2 * Math.PI * 40;
const perf = ref<Perf | null>(null);

const level = (v: number) => (v >= 90 ? STATUS.critical : v >= 70 ? STATUS.warning : STATUS.good);
const size = (n?: number) => {
  if (!n) return "0 B";
  if (n < 1024) return `${n} B`;
  if (n < 1024 ** 2) return `${(n / 1024).toFixed(1)} KB`;
  if (n < 1024 ** 3) return `${(n / 1024 ** 2).toFixed(1)} MB`;
  return `${(n / 1024 ** 3).toFixed(1)} GB`;
};
const rate = (n?: number) => `${size(n)}/s`;

const gauges = computed(() => {
  const p = perf.value;
  return [
    { key: "cpu", name: "CPU", value: p?.cpuPercent ?? 0, color: level(p?.cpuPercent ?? 0), sub: p?.host || "" },
    {
      key: "mem",
      name: t("dash.memory"),
      value: p?.memPercent ?? 0,
      color: level(p?.memPercent ?? 0),
      sub: p ? `${size(p.memUsed)} / ${size(p.memTotal)}` : ""
    },
    {
      key: "disk",
      name: t("dash.disk"),
      value: p?.diskPercent ?? 0,
      color: level(p?.diskPercent ?? 0),
      sub: p ? `${size(p.diskUsed)} / ${size(p.diskTotal)}` : ""
    }
  ];
});

/* ---------------- 数据加载 ---------------- */

const cfg = ref<DashConfig | null>(null);
const editMode = ref(false);

const loadAll = async () => {
  const [o, c] = await Promise.all([getDashOverviewApi(), getDashConfigApi()]);
  overview.value = o.data;
  cfg.value = c.data;
};

const loadPerf = async () => {
  try {
    const { data } = await getDashPerfApi();
    perf.value = data;
  } catch {
    /* 性能面板拿不到数据不该影响整页 */
  }
};

/* ---------------- 浏览任务 ---------------- */

// exemodel 是周日打头的 7 位掩码，下拉的 value 就是位次（周日 = 1）
// 周几的短标签。exemodel 是**周日打头**的 7 位掩码，所以这里也从周日排起，
// 下拉的 value 就是位次（周日 = 1）。中文是「周日/周一…」，英文是「Sun/Mon…」——
// 英文里再拼一个「周」字出来会变成 "周Sun"。
const weekLabels = computed(() => [
  t("week.sun"),
  t("week.mon"),
  t("week.tue"),
  t("week.wed"),
  t("week.thu"),
  t("week.fri"),
  t("week.sat")
]);
const tasks = ref<BrowseItem[]>([]);
const tasksTotal = ref(0);
const tasksLoading = ref(false);
const folders = ref<{ id: number; name: string }[]>([]);
const bq = reactive({ folderId: 0, weekday: 0, autoMode: 0, scope: "enabled", pageNum: 1, pageSize: 20 });

const loadTasks = async () => {
  tasksLoading.value = true;
  try {
    const { data } = await getDashTasksApi({ ...bq });
    tasks.value = data.list ?? [];
    tasksTotal.value = data.total;
  } finally {
    tasksLoading.value = false;
  }
};

const loadFolders = async () => {
  try {
    const { data } = await getTaskFolderTreeApi();
    const flat: { id: number; name: string }[] = [];
    const walk = (nodes: any[]) => nodes?.forEach(n => (flat.push({ id: n.id, name: n.name }), walk(n.children)));
    walk(data as any);
    folders.value = flat;
  } catch {
    folders.value = [];
  }
};

/* ---------------- 任务执行与状态轮询 ----------------

  `task.state` 归后台 C 服务所有 —— 界面只发命令、只读状态，从不自己写。
  这带来一个时间差：点「执行」之后到 state 真的变成 1，中间隔着
  C 服务下一次扫表，快的一两秒，慢的说不准。

  所以两件事分开做：

  1. **每 3 秒拉一次真实状态**（只取 quickTasks 这一段）。
     这样不光自己点的能看见，别人点的、作息方案到点自动跑的、
     助手下发的，也一样会在卡片上出现 —— 「是不是在执行中」问的是任务本身，
     不是「我刚才那一下成没成」。
  2. **点完立刻标一个「启动中」**，等第一次轮询把真状态带回来再让位。
     没有这一步的话，点完的头三秒卡片纹丝不动，人会以为没点上。

  ⚠ 这个「启动中」有兜底：15 秒内 C 服务还没把它变成执行中，就把标记撤掉，
  让卡片回到真实状态。停在一个「启动中」上不动，比直接显示「准备」更误导。
*/

/** task.state：1 = 执行中，3 = 启动中。口径与后端 stateText 一致 */
const STATE_RUNNING = 1;
const STATE_STARTING = 3;
/** 轮询间隔 */
const QUICK_POLL_MS = 3000;
/** 「启动中」这个本地标记最多挂多久 */
const PENDING_MAX_MS = 15000;

/** taskId -> 点下「执行」的本地时刻 */
const pendingStart = ref(new Map<number, number>());

const isRunning = (row: { state: number; taskId: number }) =>
  row.state === STATE_RUNNING || row.state === STATE_STARTING || pendingStart.value.has(row.taskId);

// ⚠ 形参不能叫 t —— 这个文件里 t 是 i18n 的翻译函数，叫 t 就把它遮掉了，
// 下面那句 t("dash.starting") 会变成「拿任务对象当函数调」。
const stateTextOf = (row: { state: number; taskId: number; stateText: string }) =>
  pendingStart.value.has(row.taskId) && row.state !== STATE_RUNNING ? t("dash.starting") : row.stateText;

const runTask = async (id: number) => {
  const { data } = await controlTaskApi("start", [id]);
  if (data.blocked?.length) {
    ElMessage.warning(data.blocked[0].detail);
    return; // 被挡下了就不该显示「启动中」——它根本没启动
  }
  ElMessage.success(t("dash.startSent"));
  pendingStart.value.set(id, Date.now());
  refreshLight();
};

const stopTask = async (id: number) => {
  const { data } = await controlTaskApi("stop", [id]);
  if (data.blocked?.length) ElMessage.warning(data.blocked[0].detail);
  else ElMessage.success(t("dash.stopSent"));
  // 停止不标本地状态：它没有「启动中」那种中间态，等轮询把真状态带回来就行
  pendingStart.value.delete(id);
  refreshLight();
};

const refreshLight = async () => {
  const { data } = await getDashConfigApi();
  cfg.value = data;
  syncPending();
  loadTasks();
};

/** 只刷快捷任务那一段。轮询走这条，不动 shortcuts —— 那边可能正开着编辑模式 */
const pollQuickTasks = async () => {
  if (!cfg.value?.quickTasks.length) return;
  try {
    const { data } = await getDashConfigApi();
    if (cfg.value) cfg.value.quickTasks = data.quickTasks;
    syncPending();
  } catch {
    // 拉不到就保持上一次的显示，下一轮再试。3 秒一次，不值得为一次失败弹提示
  }
};

/** 真状态到位、或者等太久了，就把本地那个「启动中」标记撤掉 */
const syncPending = () => {
  if (!pendingStart.value.size) return;
  const now = Date.now();
  const next = new Map(pendingStart.value);
  for (const [id, at] of pendingStart.value) {
    const row = cfg.value?.quickTasks.find(q => q.taskId === id);
    if (!row || row.state === STATE_RUNNING || now - at > PENDING_MAX_MS) next.delete(id);
  }
  pendingStart.value = next;
};

/* ---------------- 快捷入口 ---------------- */

// 快捷入口的候选页。名字用侧边栏那一套（menu.*），别自己再起一份 ——
// 两份迟早对不上，而对不上的表现是「菜单里叫 A、快捷入口里叫 B」。
//
// ⚠ label 会**存进库里**：用户挑的时候看到什么，存下来的就是什么。
// 所以早先建的快捷入口保持原样（是他当时起的名字），不会跟着切语言。
const PAGE_KEYS: { path: string; key: string }[] = [
  { path: "/terminal", key: "menu.terminal" },
  { path: "/media", key: "menu.media" },
  { path: "/alarm/area", key: "menu.alarmArea" },
  { path: "/alarm/mapping", key: "menu.alarmMapping" },
  { path: "/bell", key: "menu.bell" },
  { path: "/task", key: "menu.task" },
  { path: "/offline", key: "menu.offline" },
  { path: "/server", key: "menu.server" },
  { path: "/backup", key: "menu.backup" },
  { path: "/log", key: "menu.log" },
  { path: "/user/list", key: "menu.userList" },
  { path: "/user/group", key: "menu.userGroup" }
];
const pageOptions = computed(() => PAGE_KEYS.map(p => ({ path: p.path, label: t(p.key) })));

const sd = reactive({ visible: false, busy: false, path: "/terminal", label: "" });

const openShortcut = () => {
  sd.path = "/terminal";
  sd.label = t("menu.terminal");
  sd.busy = false;
  sd.visible = true;
};
const onPickPage = (p: string) => {
  sd.label = pageOptions.value.find(x => x.path === p)?.label ?? "";
};

const persistShortcuts = async (list: { label: string; path: string; icon: string }[]) => {
  await saveShortcutsApi(list);
  const { data } = await getDashConfigApi();
  cfg.value = data;
};

const addShortcut = async () => {
  if (!sd.label.trim()) return ElMessage.warning(t("dash.shortcutLabelRequired"));
  sd.busy = true;
  try {
    const list = [...(cfg.value?.shortcuts ?? []), { label: sd.label.trim(), path: sd.path, icon: "" }];
    await persistShortcuts(list);
    ElMessage.success(t("dash.added"));
    sd.visible = false;
  } finally {
    sd.busy = false;
  }
};

const removeShortcut = async (i: number) => {
  const list = (cfg.value?.shortcuts ?? []).filter((_, idx) => idx !== i);
  await persistShortcuts(list);
};

const go = (path: string) => router.push(path);

/** 跳到文件广播页；action=create 时那一页会自动把新建弹窗打开 */
const goTask = (action: string) => router.push(action ? { path: "/task", query: { action } } : "/task");

/* ---------------- 绑定 ---------------- */

const fileTasks = ref<TaskRow[]>([]);
const loadFileTasks = async () => {
  const { data } = await getTaskListApi({ pageNum: 1, pageSize: 100 });
  fileTasks.value = data.list ?? [];
};

const qd = reactive({ visible: false, busy: false, ids: [] as number[] });
const openQuickBind = async () => {
  await loadFileTasks();
  qd.ids = (cfg.value?.quickTasks ?? []).filter(t => !t.missing).map(t => t.taskId);
  qd.busy = false;
  qd.visible = true;
};
const saveQuick = async () => {
  qd.busy = true;
  try {
    await saveQuickTasksApi(qd.ids);
    ElMessage.success(t("common.saveSuccess"));
    qd.visible = false;
    const { data } = await getDashConfigApi();
    cfg.value = data;
  } finally {
    qd.busy = false;
  }
};

/* ---------------- 紧急广播 ---------------- */

/**
 * 紧急广播不绑任务：按一下就往后台服务发一条 SDK 命令（地震/疏散/警戒/消防
 * 各占一路），由后台去驱动全部终端。
 *
 * 执行前先问一遍 —— 这四个按钮一按，全场喇叭就响，误触的代价比多点一下大得多。
 * 停止不问：真按错了要停下来的时候，不该再挡一道。
 */
const emgBusy = ref("");

const sendEmergency = async (slot: EmergencySlot, stop: boolean) => {
  emgBusy.value = stop ? `${slot.key}:stop` : slot.key;
  try {
    await playEmergencyApi(slot.key, stop);
    // 说「已下发」不说「已播放」：后端走 UDP，没有回执，响没响这边不知道
    ElMessage.success(t(stop ? "dash.emergencyStopSent" : "dash.emergencySent", { name: slot.name }));
  } finally {
    emgBusy.value = "";
  }
};

const playEmergency = async (slot: EmergencySlot) => {
  try {
    await ElMessageBox.confirm(t("dash.emergencyConfirm", { name: slot.name }), t("dash.emergencyConfirmTitle"), {
      type: "warning",
      confirmButtonText: t("dash.run"),
      cancelButtonText: t("common.cancel")
    });
  } catch {
    return; // 点了取消
  }
  await sendEmergency(slot, false);
};

const stopEmergency = (slot: EmergencySlot) => sendEmergency(slot, true);

/* ---------------- 生命周期 ---------------- */

let timer: number | undefined;
let quickTimer: number | undefined;

onMounted(async () => {
  syncTheme();
  await Promise.all([loadAll(), loadFolders(), loadTasks()]);
  loadPerf();
  // CPU 与网速都是两次采样的差值，第一次没有基准，5 秒后第二次才有数
  timer = window.setInterval(loadPerf, 5000);
  // 快捷任务的执行状态。这一条查的是 task 表里那几个 id，很轻
  quickTimer = window.setInterval(pollQuickTasks, QUICK_POLL_MS);
});

onUnmounted(() => {
  if (timer) window.clearInterval(timer);
  if (quickTimer) window.clearInterval(quickTimer);
});
</script>

<style scoped lang="scss">
.dash {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 12px;
  overflow: auto;
}
.dash-toolbar {
  display: flex;
  gap: 8px;
  align-items: center;
  padding: 8px 12px;
  background: var(--el-bg-color);
  border-radius: 6px;
}
.chips {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  align-items: center;
  margin-left: 8px;
}
.chip {
  cursor: pointer;
}
.dash-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.panel {
  padding: 12px 16px 16px;
  background: var(--el-bg-color);
  border-radius: 6px;
}
.panel-hd {
  display: flex;
  align-items: center;
  padding-bottom: 10px;
  font-size: 14px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}
.hd-actions {
  display: flex;
  gap: 6px;
  margin-left: auto;
}
.hd-note {
  margin-left: auto;
  font-size: 12px;
  font-weight: 400;
  color: var(--el-text-color-secondary);
}
.panel-bd {
  min-height: 190px;
}

/* 环形图 */
.donut-wrap {
  display: flex;
  gap: 20px;
  align-items: center;
  justify-content: center;
}
.donut {
  width: 200px;
  height: 200px;
}
.donut-track {
  fill: none;
  stroke: var(--el-fill-color-light);
}
.donut-cap,
.donut-num {
  text-anchor: middle;
}
.donut-cap {
  font-size: 12px;
  fill: var(--el-text-color-secondary);
}
.donut-num {
  font-size: 26px;
  font-weight: 700;
  fill: var(--el-text-color-primary);
}
.legend {
  min-width: 150px;
  padding: 0;
  margin: 0;
  font-size: 12px;
  list-style: none;
  li {
    display: flex;
    gap: 6px;
    align-items: center;
    line-height: 22px;
  }
}
.dot {
  width: 9px;
  height: 9px;
  border-radius: 2px;
}
.lg-name {
  color: var(--el-text-color-regular);
}
.lg-val {
  margin-left: auto;
  font-weight: 600;
  color: var(--el-text-color-primary);
}
.stat-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin-top: 14px;
}
.stat {
  padding: 10px 0;
  text-align: center;
  border-top: 2px solid var(--el-border-color);
  border-radius: 4px;
}
.stat-total {
  border-top-color: #2a78d6;
}
.stat-on {
  border-top-color: #0ca30c;
}
.stat-off {
  border-top-color: #d03b3b;
}
.stat-k {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.stat-v {
  font-size: 20px;
  font-weight: 700;
}
.stat-on .stat-v {
  color: #0ca30c;
}
.stat-off .stat-v {
  color: #d03b3b;
}

/* 仪表 */
.gauge-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}
.gauge-box {
  padding: 10px 0;
  text-align: center;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 6px;
}
.gauge {
  width: 92px;
  height: 92px;
}
.gauge-track {
  fill: none;
  stroke: var(--el-fill-color-light);
}
.gauge-num {
  font-size: 17px;
  font-weight: 700;
  text-anchor: middle;
  fill: var(--el-text-color-primary);
}
.gauge-name {
  font-size: 13px;
  color: var(--el-text-color-regular);
}
.gauge-sub {
  font-size: 11px;
  color: var(--el-text-color-secondary);
}
.net-box {
  padding: 10px 12px;
  margin-top: 12px;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 6px;
}
.net-hd {
  display: flex;
  font-size: 13px;
  font-weight: 600;
}
.net-line {
  font-size: 12px;
  line-height: 22px;
  color: var(--el-text-color-regular);
}

/* 快捷任务 */
.empty {
  padding: 70px 0;
  font-size: 13px;
  color: var(--el-text-color-placeholder);
  text-align: center;
}
.task-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
}
.task-card {
  padding: 10px 12px;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 6px;
}
.task-name {
  font-size: 13px;
  font-weight: 600;
}
.task-sub {
  margin: 2px 0 8px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

/*
  执行中的状态字上色 + 一个会呼吸的圆点。
  ⚠ 颜色不单独承载信息：字本身就写着「执行中」，圆点只是让人一眼扫到。
*/
.task-state.running {
  font-weight: 600;
  color: var(--el-color-success);
}
.run-dot {
  display: inline-block;
  width: 6px;
  height: 6px;
  margin-right: 4px;
  vertical-align: 1px;
  background: var(--el-color-success);
  border-radius: 50%;
  animation: run-pulse 1.4s ease-in-out infinite;
}
@keyframes run-pulse {
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.25;
  }
}
/* 跟随系统「减少动态效果」设置：闪烁对前庭敏感的人不友好 */
@media (prefers-reduced-motion: reduce) {
  .run-dot {
    animation: none;
  }
}

/* 紧急广播 */
.emg-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
}
.emg-card {
  display: flex;
  gap: 8px;
  align-items: center;
  padding: 10px 12px;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 6px;
}
.emg-name {
  font-size: 13px;
  font-weight: 600;
  color: #d03b3b;
}
.emg-ops {
  display: flex;
  gap: 6px;
  margin-left: auto;
}

/* 浏览任务 */
.filter-bar {
  display: flex;
  gap: 8px;
  align-items: center;
  padding-bottom: 8px;
}
.fl {
  font-size: 13px;
  color: var(--el-text-color-regular);
}
.scope-bar {
  margin-bottom: 10px;
}
.pager {
  margin-top: 10px;
}

.muted {
  color: var(--el-text-color-placeholder);
}
.small {
  font-size: 12px;
}
.danger {
  color: var(--el-color-danger);
}
.fill {
  width: 100%;
}
.mb12 {
  margin-bottom: 12px;
}
</style>
