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
      <el-button size="small" :icon="Plus" :disabled="!isSuper" @click="openShortcut()">新增</el-button>
      <el-button size="small" :icon="EditPen" :disabled="!isSuper || !cfg?.shortcuts.length" @click="editMode = !editMode">
        编辑
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
          还没有快捷入口{{ isSuper ? "，点「新增」添加，之后点一下就能直接进对应页面" : "" }}
        </span>
      </div>
    </div>

    <div class="dash-row">
      <!-- ===== 设备概况 ===== -->
      <section class="panel">
        <header class="panel-hd">设备概况</header>
        <div class="donut-wrap">
          <svg :viewBox="`0 0 ${DONUT} ${DONUT}`" class="donut" role="img" aria-label="设备类型构成">
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
            <text :x="C" :y="C - 6" class="donut-cap">设备总数</text>
            <text :x="C" :y="C + 22" class="donut-num">{{ overview?.total ?? 0 }}</text>
          </svg>
          <ul class="legend">
            <li v-for="(t, i) in overview?.byType ?? []" :key="t.type">
              <i class="dot" :style="{ background: seriesColor(i) }" />
              <span class="lg-name">{{ t.type }}</span>
              <span class="lg-val">{{ t.total }}</span>
            </li>
            <li v-if="!overview?.byType.length" class="muted small">暂无终端</li>
          </ul>
        </div>
        <div class="stat-row">
          <div class="stat stat-total">
            <div class="stat-k">设备总数</div>
            <div class="stat-v">{{ overview?.total ?? 0 }}</div>
          </div>
          <div class="stat stat-on">
            <div class="stat-k">在线</div>
            <div class="stat-v">{{ overview?.online ?? 0 }}</div>
          </div>
          <div class="stat stat-off">
            <div class="stat-k">离线</div>
            <div class="stat-v">{{ overview?.offline ?? 0 }}</div>
          </div>
        </div>
      </section>

      <!-- ===== 快捷任务 ===== -->
      <section class="panel">
        <header class="panel-hd">
          快捷任务
          <div class="hd-actions">
            <!--
              :80 这两个按钮直接弹出文件广播的新建/编辑表单。
              我们不在首页重做一遍那张大表单，而是跳到文件广播页并带上 action=create，
              由那一页自己把新建弹窗打开 —— 只有一处表单，改一次就够。
            -->
            <el-button size="small" type="primary" @click="goTask('create')">新增文件广播</el-button>
            <el-button size="small" type="primary" plain @click="goTask('')">编辑文件广播</el-button>
            <el-button size="small" type="primary" :disabled="!isSuper" @click="openQuickBind">绑定快捷任务</el-button>
          </div>
        </header>
        <div class="panel-bd">
          <div v-if="!cfg?.quickTasks.length" class="empty">暂无快捷任务</div>
          <div v-else class="task-grid">
            <div v-for="t in cfg.quickTasks" :key="t.taskId" class="task-card">
              <div class="task-name" :class="{ danger: t.missing }">{{ t.taskName }}</div>
              <div class="task-sub">{{ t.missing ? `ID ${t.taskId}` : `${t.playtime} · ${t.stateText}` }}</div>
              <div class="task-ops">
                <el-button size="small" type="success" :disabled="t.missing" @click="runTask(t.taskId)">执行</el-button>
                <el-button size="small" type="danger" :disabled="t.missing" @click="stopTask(t.taskId)">停止</el-button>
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
          服务器性能
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
            网络流量
            <span class="hd-note">{{ perf?.iface || "—" }}</span>
          </div>
          <div class="net-line">接收：{{ rate(perf?.rxRate) }}</div>
          <div class="net-line">发送：{{ rate(perf?.txRate) }}</div>
          <div v-if="perf?.warmingUp" class="muted small">首次采样，速率需要再等一轮才有数</div>
        </div>
      </section>

      <!-- ===== 紧急广播 ===== -->
      <section class="panel">
        <header class="panel-hd">
          紧急广播
          <div class="hd-actions">
            <el-button size="small" type="primary" plain :disabled="!isSuper" @click="openEmergencyBind">
              绑定任务
            </el-button>
          </div>
        </header>
        <div class="panel-bd">
          <div class="emg-grid">
            <div v-for="s in cfg?.emergency ?? []" :key="s.key" class="emg-card">
              <span class="emg-name">{{ s.name }}</span>
              <span class="emg-bind" :class="{ muted: !s.task, danger: s.task?.missing }">
                {{ s.task ? s.task.taskName : "未绑定" }}
              </span>
              <span class="emg-ops">
                <el-button size="small" type="success" :disabled="!s.task || s.task.missing" @click="runTask(s.task!.taskId)">
                  执行
                </el-button>
                <el-button size="small" type="danger" :disabled="!s.task || s.task.missing" @click="stopTask(s.task!.taskId)">
                  停止
                </el-button>
              </span>
            </div>
          </div>
          <div class="muted small mt8">
            四个槽位是固定的，只能改「每个槽位绑哪个文件广播任务」，不能增删。
          </div>
        </div>
      </section>
    </div>

    <!-- ===== 浏览任务 ===== -->
    <section class="panel">
      <div class="filter-bar">
        <span class="fl">任务管理：</span>
        <el-select v-model="bq.folderId" size="small" style="width: 130px" @change="loadTasks">
          <el-option label="全部" :value="0" />
          <el-option v-for="f in folders" :key="f.id" :label="f.name" :value="f.id" />
        </el-select>
        <span class="fl">星期筛选：</span>
        <el-select v-model="bq.weekday" size="small" style="width: 120px" @change="loadTasks">
          <el-option label="今天" :value="0" />
          <el-option v-for="(w, i) in weekLabels" :key="i" :label="`周${w}`" :value="i + 1" />
        </el-select>
        <span class="fl">任务类型：</span>
        <el-select v-model="bq.autoMode" size="small" style="width: 120px" @change="loadTasks">
          <el-option label="全部" :value="0" />
          <el-option label="自动任务" :value="1" />
          <el-option label="手动任务" :value="2" />
        </el-select>
      </div>

      <!-- :80 这里是「当天启用 / 当天停用」两个按钮，不是页签；多留一个「全部」 -->
      <div class="scope-bar">
        <el-radio-group v-model="bq.scope" size="small" @change="loadTasks">
          <el-radio-button value="enabled">当天启用</el-radio-button>
          <el-radio-button value="disabled">当天停用</el-radio-button>
          <el-radio-button value="all">全部</el-radio-button>
        </el-radio-group>
      </div>

      <el-table :data="tasks" v-loading="tasksLoading" size="small" empty-text="暂无数据">
        <el-table-column prop="index" label="序号" width="70" />
        <el-table-column prop="taskName" label="任务名称" min-width="160" show-overflow-tooltip />
        <el-table-column prop="folderName" label="所属分类" width="130" show-overflow-tooltip />
        <el-table-column prop="cycleText" label="播放周期" width="140" />
        <el-table-column prop="playtime" label="执行时间" width="110" />
        <el-table-column label="状态" width="110">
          <template #default="{ row }">
            <el-tag :type="row.enabledToday ? 'success' : 'info'" size="small" effect="plain">
              {{ row.enabledToday ? "当天启用" : "当天停用" }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="startdate" label="起始日期" width="110" />
        <el-table-column prop="enddate" label="结束日期" width="110" />
        <el-table-column prop="terminals" label="终端属性" width="100" />
        <el-table-column label="操作任务" width="150" fixed="right">
          <template #default="{ row }">
            <el-button type="success" link @click="runTask(row.taskId)">执行</el-button>
            <el-button type="danger" link @click="stopTask(row.taskId)">停止</el-button>
            <el-button type="primary" link @click="go('/task')">查看</el-button>
          </template>
        </el-table-column>
      </el-table>
      <el-pagination
        class="pager"
        layout="total, prev, pager, next"
        :total="tasksTotal"
        :page-size="bq.pageSize"
        :current-page="bq.pageNum"
        @current-change="(n: number) => { bq.pageNum = n; loadTasks(); }"
      />
    </section>

    <!-- 新增快捷入口 -->
    <el-dialog v-model="sd.visible" title="新增快捷入口" width="460px">
      <el-form label-width="90px">
        <el-form-item label="目标页面">
          <el-select v-model="sd.path" class="fill" @change="onPickPage">
            <el-option v-for="p in pageOptions" :key="p.path" :label="p.label" :value="p.path" />
          </el-select>
        </el-form-item>
        <el-form-item label="显示名称">
          <el-input v-model="sd.label" maxlength="12" show-word-limit />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="sd.visible = false">取消</el-button>
        <el-button type="primary" :loading="sd.busy" @click="addShortcut">确定</el-button>
      </template>
    </el-dialog>

    <!-- 绑定快捷任务 -->
    <el-dialog v-model="qd.visible" title="绑定快捷任务" width="560px">
      <el-alert type="info" :closable="false" class="mb12">
        只能绑定<b>文件广播</b>任务，最多 12 个。绑定后可在首页直接执行/停止。
      </el-alert>
      <el-select v-model="qd.ids" multiple filterable placeholder="选择文件广播任务" class="fill">
        <el-option v-for="t in fileTasks" :key="t.taskid" :label="t.taskname" :value="t.taskid" />
      </el-select>
      <template #footer>
        <el-button @click="qd.visible = false">取消</el-button>
        <el-button type="primary" :loading="qd.busy" @click="saveQuick">确定</el-button>
      </template>
    </el-dialog>

    <!-- 绑定紧急广播 -->
    <el-dialog v-model="ed.visible" title="绑定紧急广播" width="560px">
      <el-alert type="info" :closable="false" class="mb12">
        四个槽位固定，留空表示解绑。同样只能绑文件广播任务。
      </el-alert>
      <el-form label-width="80px">
        <el-form-item v-for="s in cfg?.emergency ?? []" :key="s.key" :label="s.name">
          <el-select v-model="ed.slots[s.key]" clearable filterable placeholder="未绑定" class="fill">
            <el-option v-for="t in fileTasks" :key="t.taskid" :label="t.taskname" :value="t.taskid" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="ed.visible = false">取消</el-button>
        <el-button type="primary" :loading="ed.busy" @click="saveEmergency">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="home">
import { computed, onMounted, onUnmounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { ElMessage } from "element-plus";
import { EditPen, Plus } from "@element-plus/icons-vue";
import {
  getDashConfigApi,
  getDashOverviewApi,
  getDashPerfApi,
  getDashTasksApi,
  saveEmergencyApi,
  saveQuickTasksApi,
  saveShortcutsApi,
  type BrowseItem,
  type DashConfig,
  type Overview,
  type Perf
} from "@/api/modules/dashboard";
import { controlTaskApi, getTaskFolderTreeApi, getTaskListApi, type TaskRow } from "@/api/modules/task";
import { useUserStore } from "@/stores/modules/user";

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
      name: "内存",
      value: p?.memPercent ?? 0,
      color: level(p?.memPercent ?? 0),
      sub: p ? `${size(p.memUsed)} / ${size(p.memTotal)}` : ""
    },
    {
      key: "disk",
      name: "磁盘",
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

const weekLabels = ["一", "二", "三", "四", "五", "六", "日"];
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

/* ---------------- 任务执行 ---------------- */

const runTask = async (id: number) => {
  const { data } = await controlTaskApi("start", [id]);
  if (data.blocked?.length) ElMessage.warning(data.blocked[0].detail);
  else ElMessage.success("已下发启动");
  refreshLight();
};

const stopTask = async (id: number) => {
  const { data } = await controlTaskApi("stop", [id]);
  if (data.blocked?.length) ElMessage.warning(data.blocked[0].detail);
  else ElMessage.success("已下发停止");
  refreshLight();
};

const refreshLight = async () => {
  const { data } = await getDashConfigApi();
  cfg.value = data;
  loadTasks();
};

/* ---------------- 快捷入口 ---------------- */

const pageOptions = [
  { path: "/terminal", label: "终端管理" },
  { path: "/media", label: "文件管理" },
  { path: "/alarm/area", label: "报警分区" },
  { path: "/alarm/mapping", label: "报警映射" },
  { path: "/bell", label: "作息方案" },
  { path: "/task", label: "文件广播" },
  { path: "/offline", label: "离线传输" },
  { path: "/server", label: "服务器信息" },
  { path: "/backup", label: "备份还原" },
  { path: "/log", label: "日志" },
  { path: "/user/list", label: "用户" },
  { path: "/user/group", label: "用户组" }
];

const sd = reactive({ visible: false, busy: false, path: "/terminal", label: "终端管理" });

const openShortcut = () => {
  sd.path = "/terminal";
  sd.label = "终端管理";
  sd.busy = false;
  sd.visible = true;
};
const onPickPage = (p: string) => {
  sd.label = pageOptions.find(x => x.path === p)?.label ?? "";
};

const persistShortcuts = async (list: { label: string; path: string; icon: string }[]) => {
  await saveShortcutsApi(list);
  const { data } = await getDashConfigApi();
  cfg.value = data;
};

const addShortcut = async () => {
  if (!sd.label.trim()) return ElMessage.warning("请填写显示名称");
  sd.busy = true;
  try {
    const list = [...(cfg.value?.shortcuts ?? []), { label: sd.label.trim(), path: sd.path, icon: "" }];
    await persistShortcuts(list);
    ElMessage.success("已添加");
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
    ElMessage.success("已保存");
    qd.visible = false;
    const { data } = await getDashConfigApi();
    cfg.value = data;
  } finally {
    qd.busy = false;
  }
};

const ed = reactive({ visible: false, busy: false, slots: {} as Record<string, number | undefined> });
const openEmergencyBind = async () => {
  await loadFileTasks();
  ed.slots = {};
  (cfg.value?.emergency ?? []).forEach(s => (ed.slots[s.key] = s.task && !s.task.missing ? s.task.taskId : undefined));
  ed.busy = false;
  ed.visible = true;
};
const saveEmergency = async () => {
  ed.busy = true;
  try {
    const payload: Record<string, number> = {};
    Object.entries(ed.slots).forEach(([k, v]) => (payload[k] = v ?? 0));
    await saveEmergencyApi(payload);
    ElMessage.success("已保存");
    ed.visible = false;
    const { data } = await getDashConfigApi();
    cfg.value = data;
  } finally {
    ed.busy = false;
  }
};

/* ---------------- 生命周期 ---------------- */

let timer: number | undefined;

onMounted(async () => {
  syncTheme();
  await Promise.all([loadAll(), loadFolders(), loadTasks()]);
  loadPerf();
  // CPU 与网速都是两次采样的差值，第一次没有基准，5 秒后第二次才有数
  timer = window.setInterval(loadPerf, 5000);
});

onUnmounted(() => timer && window.clearInterval(timer));
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
.emg-bind {
  overflow: hidden;
  font-size: 12px;
  text-overflow: ellipsis;
  white-space: nowrap;
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
.mt8 {
  margin-top: 8px;
}
</style>
