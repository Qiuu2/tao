<!--
  离线管理

  手册开篇就说这一域是旧代码质量最差的部分，本轮在现网逐条核实过：

  · `terminaloftask` **确实没有 offlineparam 这一列**。旧版「离线任务下发」引用它
    并带 or die() —— 也就是说这个功能一次都没成功过。新版改用已经存在的
    offlinetaskofterminal.offlinestate 表达同一语义，绝不新增字段。
  · 旧版「停止离线音乐」的 WHERE 里列名写成了 `task`（该列叫 `taskid`），
    同样带 or die()，所以也从来没生效过。
  · offlinetaskofterminal.area 的列默认值是字面量 '''11111111'''，
    带着三层引号 —— 插入必须显式写值。
  · offlinemedia.id 必须显式等于 media.id，后台服务据此关联两张表（强契约）。

  12 态里 Web 只写「下发意图」那 5 个（1/2/4/5/11），
  进行中与完成态由后台服务写 —— 界面上只读展示。
-->
<template>
  <div class="offline-page">
    <el-alert v-if="summary" type="info" :closable="false" class="mb12">
      <template #title>
        离线副本 {{ summary.offlineMedia }} 个媒体 / {{ summary.offlineTask }} 个任务 · 下发关系
        {{ summary.offlineMediaOfTerminal }} 条媒体 / {{ summary.offlineTaskOfTerminal }} 条任务 · 标记为离线的任务
        {{ summary.tasksMarked }} 个
      </template>
    </el-alert>

    <div class="tool-bar">
      <el-button type="primary" :icon="Upload" :disabled="!canMedia" @click="openMedia">下发媒体</el-button>
      <el-button type="primary" :icon="Files" :disabled="!canTask" @click="openTask">下发任务</el-button>
      <el-button :icon="Refresh" @click="reload">刷新</el-button>
      <div class="spacer" />
      <el-button type="danger" :icon="Delete" @click="openPurge">清空全部离线数据</el-button>
    </div>

    <el-tabs v-model="tab" @tab-change="reload">
      <!--
        「音乐下发」这一页照 :80 的音乐传输：左边终端树、右边媒体清单，
        底下两个按钮 空闲传输 / 立即传输。挑完直接发，不再经过弹窗。
        后面两个页签是我们多给的「发了之后到哪一步了」，:80 没有对应视图。
      -->
      <el-tab-pane label="音乐下发" name="dispatch">
        <div class="dispatch">
          <div class="pane">
            <div class="pane-hd">
              <span>终端</span>
              <el-input
                v-model="dp.termKeyword"
                placeholder="设备名称搜索"
                clearable
                size="small"
                style="width: 180px"
                @input="loadDispatchTerminals"
              />
            </div>
            <el-tree
              ref="termTreeRef"
              :data="dp.termTree"
              show-checkbox
              node-key="key"
              :props="{ label: 'label', children: 'children' }"
              default-expand-all
              class="pane-bd"
              @check="onTermCheck"
            />
          </div>

          <div class="pane">
            <div class="pane-hd">
              <span>媒体</span>
              <el-input
                v-model="dp.mediaKeyword"
                placeholder="媒体名称搜索"
                clearable
                size="small"
                style="width: 180px"
                @input="loadDispatchMedia"
              />
            </div>
            <el-scrollbar class="pane-bd">
              <el-checkbox-group v-model="dp.mediaIds">
                <div v-for="m in dp.medias" :key="m.id" class="media-line">
                  <el-checkbox :value="m.id">
                    {{ m.name }}
                    <span class="opt-sub">{{ m.folderName }}</span>
                  </el-checkbox>
                </div>
              </el-checkbox-group>
              <div v-if="!dp.medias.length" class="empty-note">没有匹配的媒体</div>
            </el-scrollbar>
          </div>
        </div>

        <!-- 按钮照旧版 set_offlinemusic.html：空闲传输 / 立即传输 / 空闲删除 / 立即删除
             （「全部清除」在页顶那个「清空全部离线数据」上） -->
        <div class="dispatch-bar">
          <div class="spacer" />
          <el-button :disabled="!canDispatch" @click="doDispatch('idle')">空闲传输</el-button>
          <el-button type="primary" :disabled="!canDispatch" @click="doDispatch('immediate')">立即传输</el-button>
          <el-button :disabled="!canDispatch" @click="doDispatch('deleteIdle')">空闲删除</el-button>
          <el-button type="danger" :disabled="!canDispatch" @click="doDispatch('deleteNow')">立即删除</el-button>
        </div>
      </el-tab-pane>

      <el-tab-pane label="媒体下发状态" name="media">
        <div class="filter-bar">
          <el-select v-model="filter.state" placeholder="全部状态" clearable style="width: 160px" @change="reload">
            <el-option v-for="s in states" :key="s.value" :label="s.text" :value="s.value" />
          </el-select>
          <el-input-number
            v-model="filter.terminalId"
            :min="0"
            placeholder="终端 ID"
            controls-position="right"
            style="width: 130px"
            @change="reload"
          />
          <el-button type="warning" :disabled="!canMedia || !checkedMedia.length" @click="stopSelected">
            停止传输{{ checkedMedia.length ? `(${checkedMedia.length})` : "" }}
          </el-button>
        </div>

        <el-table :data="mediaRows" v-loading="loading" @selection-change="onMediaSelect">
          <el-table-column type="selection" width="46" />
          <el-table-column label="媒体" min-width="180">
            <template #default="{ row }">
              <span :class="{ danger: row.copyMissing }">{{ row.mediaName }}</span>
              <span class="muted"> #{{ row.mediaId }}</span>
            </template>
          </el-table-column>
          <el-table-column label="终端" min-width="160">
            <template #default="{ row }">
              <span :class="{ danger: row.terminalMissing }">{{ row.terminalName }}</span>
              <span class="muted"> #{{ row.terminalId }}</span>
            </template>
          </el-table-column>
          <el-table-column label="所属任务" width="150">
            <template #default="{ row }">
              <span v-if="row.taskId">{{ row.taskName || row.taskId }}</span>
              <span v-else class="muted">单独下发</span>
            </template>
          </el-table-column>
          <el-table-column label="状态" width="130">
            <template #default="{ row }">
              <el-tag :type="stateTag(row.offlinestate)" size="small">{{ row.offlineStateText }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="sort" label="序" width="60" />
        </el-table>
        <el-pagination
          class="pager"
          layout="total, prev, pager, next, sizes"
          :total="mediaTotal"
          :page-size="filter.pageSize"
          :current-page="filter.pageNum"
          :page-sizes="[10, 20, 50, 100]"
          @current-change="
            (n: number) => {
              filter.pageNum = n;
              reload();
            }
          "
          @size-change="
            (n: number) => {
              filter.pageSize = n;
              filter.pageNum = 1;
              reload();
            }
          "
        />
      </el-tab-pane>

      <el-tab-pane label="任务下发状态" name="task">
        <el-table :data="taskRows" v-loading="loading">
          <el-table-column label="任务" min-width="180">
            <template #default="{ row }">
              {{ row.taskName || row.taskId }}
              <el-tag v-if="row.copyMissing" type="danger" size="small" effect="plain">副本缺失</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="终端" min-width="160">
            <template #default="{ row }">
              <span :class="{ danger: row.terminalMissing }">{{ row.terminalName }}</span>
            </template>
          </el-table-column>
          <el-table-column label="状态" width="130">
            <template #default="{ row }">
              <el-tag :type="stateTag(row.offlinestate)" size="small">{{ row.offlineStateText }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="mediaCount" label="媒体数" width="90" />
          <el-table-column prop="area" label="区域掩码" width="120" />
        </el-table>
        <el-pagination
          class="pager"
          layout="total, prev, pager, next"
          :total="taskTotal"
          :page-size="filter.pageSize"
          :current-page="filter.pageNum"
          @current-change="
            (n: number) => {
              filter.pageNum = n;
              reload();
            }
          "
        />
      </el-tab-pane>
    </el-tabs>

    <!-- 下发媒体 -->
    <el-dialog v-model="md.visible" title="离线媒体下发" width="640px">
      <el-form label-width="90px">
        <el-form-item label="媒体" required>
          <el-select
            v-model="md.mediaIds"
            multiple
            filterable
            remote
            reserve-keyword
            :remote-method="searchMedia"
            placeholder="媒体名称搜索"
            class="fill"
          >
            <el-option v-for="m in medias" :key="m.id" :label="m.name" :value="m.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="目标终端" required>
          <TerminalTree v-model="md.terminalIds" :terminals="terminals" height="240px" @search="searchTerminals" />
        </el-form-item>
        <el-form-item label="下发方式">
          <el-radio-group v-model="md.mode">
            <el-radio value="idle">空闲传输</el-radio>
            <el-radio value="immediate">立即传输</el-radio>
            <el-radio value="deleteIdle">空闲删除</el-radio>
            <el-radio value="deleteNow">立即删除</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="md.visible = false">取消</el-button>
        <el-button type="primary" :loading="md.busy" @click="submitMedia">下发</el-button>
      </template>
    </el-dialog>

    <!-- 下发任务 -->
    <el-dialog v-model="tk.visible" title="离线任务下发" width="640px">
      <el-alert type="info" :closable="false" class="mb12">
        任务下发会连同它的铃声清单一起下发，并自动补齐 offlinemedia 副本 —— 没有副本终端就拿不到文件。
      </el-alert>
      <el-form label-width="90px">
        <el-form-item label="任务" required>
          <el-select v-model="tk.taskIds" multiple filterable placeholder="选择任务" class="fill">
            <el-option v-for="t in tasks" :key="t.taskid" :label="t.taskname" :value="t.taskid" />
          </el-select>
        </el-form-item>
        <el-form-item label="目标终端" required>
          <TerminalTree v-model="tk.terminalIds" :terminals="terminals" height="240px" @search="searchTerminals" />
        </el-form-item>
        <el-form-item label="下发方式">
          <el-radio-group v-model="tk.mode">
            <el-radio value="idle">空闲传输</el-radio>
            <el-radio value="immediate">立即传输</el-radio>
            <el-radio value="deleteIdle">空闲删除</el-radio>
            <el-radio value="deleteNow">立即删除</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="tk.visible = false">取消</el-button>
        <el-button type="primary" :loading="tk.busy" @click="submitTask">下发</el-button>
      </template>
    </el-dialog>

    <!-- 清空 -->
    <el-dialog v-model="pg.visible" title="清空全部离线数据" width="560px">
      <el-alert type="error" :closable="false" class="mb12">
        <template #title>这是四条无 WHERE 的全表删除</template>
        <div class="alert-body">
          会清空 offlinemedia、offlinemediaofterminal、offlinetask、offlinetaskofterminal 四张表， 并把 task.offlinestate
          全部复位为 0。旧版这个动作只有前端一个 confirm 保护。
        </div>
      </el-alert>
      <el-descriptions v-if="summary" :column="2" border size="small" class="mb12">
        <el-descriptions-item label="离线媒体副本">{{ summary.offlineMedia }}</el-descriptions-item>
        <el-descriptions-item label="媒体下发关系">{{ summary.offlineMediaOfTerminal }}</el-descriptions-item>
        <el-descriptions-item label="离线任务副本">{{ summary.offlineTask }}</el-descriptions-item>
        <el-descriptions-item label="任务下发关系">{{ summary.offlineTaskOfTerminal }}</el-descriptions-item>
        <el-descriptions-item label="待复位任务" :span="2">{{ summary.tasksMarked }}</el-descriptions-item>
      </el-descriptions>
      <el-input v-model="pg.confirmText" placeholder="逐字输入：清空全部离线数据" />
      <template #footer>
        <el-button @click="pg.visible = false">取消</el-button>
        <el-button type="danger" :loading="pg.busy" :disabled="pg.confirmText !== '清空全部离线数据'" @click="submitPurge">
          确认清空
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="offlinePage">
import { computed, onMounted, reactive, ref } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { Delete, Files, Refresh, Upload } from "@element-plus/icons-vue";
import TerminalTree from "@/components/TerminalTree/index.vue";
import { useAuthStore } from "@/stores/modules/auth";
import {
  dispatchOfflineMediaApi,
  dispatchOfflineTaskApi,
  getOfflineMediaStatusApi,
  getOfflineStatesApi,
  getOfflineSummaryApi,
  getOfflineTaskStatusApi,
  purgeOfflineApi,
  stopOfflineApi,
  type OfflineMediaStatus,
  type OfflineMode,
  type OfflineStateDef,
  type OfflineSummary,
  type OfflineTaskStatus
} from "@/api/modules/offline";
import {
  getTaskListApi,
  searchTaskMediaApi,
  searchTaskTerminalsApi,
  type MediaOption,
  type TaskRow,
  type TaskTerminalOption
} from "@/api/modules/task";

const authStore = useAuthStore();
const btn = computed(() => (authStore.authButtonListGet as any) ?? {});
const canMedia = computed(() => !!btn.value.terminal?.edit);
const canTask = computed(() => !!btn.value.task?.edit);

const tab = ref("dispatch");
const loading = ref(false);
const states = ref<OfflineStateDef[]>([]);
const summary = ref<OfflineSummary | null>(null);
const mediaRows = ref<OfflineMediaStatus[]>([]);
const taskRows = ref<OfflineTaskStatus[]>([]);
const mediaTotal = ref(0);
const taskTotal = ref(0);
const checkedMedia = ref<OfflineMediaStatus[]>([]);

const filter = reactive({ state: undefined as number | undefined, terminalId: 0, pageNum: 1, pageSize: 20 });

/** 0（非离线）与 1/2（待传输）用 primary，el-tag 的 type 不接受空串 */
const stateTag = (s: number): "primary" | "success" | "warning" | "info" | "danger" => {
  if (s === 3 || s === 8) return "success";
  if (s === 6 || s === 7 || s === 9 || s === 10) return "warning";
  if (s === 11 || s === 12) return "info";
  if (s === 4 || s === 5) return "danger";
  return "primary";
};

const onMediaSelect = (rows: OfflineMediaStatus[]) => (checkedMedia.value = rows);

const reload = async () => {
  loading.value = true;
  try {
    const params: any = { pageNum: filter.pageNum, pageSize: filter.pageSize };
    if (filter.state !== undefined && filter.state !== null) params.offlinestate = filter.state;
    if (filter.terminalId) params.terminalId = filter.terminalId;
    if (tab.value === "media") {
      const { data } = await getOfflineMediaStatusApi(params);
      mediaRows.value = data.list ?? [];
      mediaTotal.value = data.total;
    } else {
      const { data } = await getOfflineTaskStatusApi(params);
      taskRows.value = data.list ?? [];
      taskTotal.value = data.total;
    }
    const s = await getOfflineSummaryApi();
    summary.value = s.data;
  } finally {
    loading.value = false;
  }
};

/* ---------------- 选择器 ---------------- */

const medias = ref<MediaOption[]>([]);
const terminals = ref<TaskTerminalOption[]>([]);
const tasks = ref<TaskRow[]>([]);

const searchMedia = async (kw: string) => {
  const { data } = await searchTaskMediaApi(kw ?? "");
  medias.value = data ?? [];
};
const searchTerminals = async (kw: string) => {
  const { data } = await searchTaskTerminalsApi(kw ?? "");
  terminals.value = data ?? [];
};
const loadTasks = async () => {
  const { data } = await getTaskListApi({ pageNum: 1, pageSize: 100 });
  tasks.value = data.list ?? [];
};

/* ---------------- 音乐下发（照 :80 的一页式表单）---------------- */

interface TermNode {
  key: string;
  label: string;
  terminalId?: number;
  children?: TermNode[];
}

const termTreeRef = ref<InstanceType<(typeof import("element-plus"))["ElTree"]>>();

const dp = reactive({
  termKeyword: "",
  mediaKeyword: "",
  termTree: [] as TermNode[],
  terminalIds: [] as number[],
  medias: [] as MediaOption[],
  mediaIds: [] as number[]
});

const canDispatch = computed(() => dp.terminalIds.length > 0 && dp.mediaIds.length > 0);

/**
 * 终端按分区分组成两层树。分区名来自终端选项自带的 groupName，
 * 没有分区的归到「未分类终端」—— 与 :80 左树里那个节点同名。
 */
const loadDispatchTerminals = async () => {
  const { data } = await searchTaskTerminalsApi(dp.termKeyword ?? "");
  const list = data ?? [];
  const groups = new Map<string, TermNode>();
  for (const t of list) {
    const name = t.groupName || "未分类终端";
    if (!groups.has(name)) groups.set(name, { key: `g:${name}`, label: name, children: [] });
    groups.get(name)!.children!.push({
      key: `t:${t.id}`,
      label: `${t.name || "终端 " + t.id}（${t.netstate === 1 ? "在线" : "离线"}）`,
      terminalId: t.id
    });
  }
  dp.termTree = [...groups.values()];
};

const onTermCheck = () => {
  const nodes = (termTreeRef.value?.getCheckedNodes(true) ?? []) as TermNode[];
  dp.terminalIds = nodes.filter(n => n.terminalId).map(n => n.terminalId!);
};

const loadDispatchMedia = async () => {
  const { data } = await searchTaskMediaApi(dp.mediaKeyword ?? "");
  dp.medias = data ?? [];
  // 搜索换词后，把已选里已经不在候选中的剔掉，免得发出去一个界面上看不见的媒体
  const ids = new Set(dp.medias.map(m => m.id));
  dp.mediaIds = dp.mediaIds.filter(id => ids.has(id));
};

const DISPATCH_TEXT: Record<string, string> = {
  idle: "空闲传输",
  immediate: "立即传输",
  deleteIdle: "空闲删除",
  deleteNow: "立即删除"
};

const doDispatch = async (mode: OfflineMode) => {
  if (!canDispatch.value) return ElMessage.warning("请先选终端和媒体");
  const text = DISPATCH_TEXT[mode] ?? mode;
  // 删除类动作会让终端把文件删掉，先确认（旧版这两个也是先弹确认框的）
  if (mode === "deleteIdle" || mode === "deleteNow") {
    await ElMessageBox.confirm(
      `将对选中的 ${dp.terminalIds.length} 台终端上的 ${dp.mediaIds.length} 个媒体执行「${text}」，` +
        `终端上的文件会被删掉，不可恢复。`,
      text,
      { type: "warning" }
    );
  }
  const { data } = await dispatchOfflineMediaApi(dp.mediaIds, dp.terminalIds, mode);
  ElMessage.success(
    `${text}已下发：${data.mediaCount} 个媒体 × ${data.terminalCount} 台终端，` +
      `新建 ${data.linksCreated} 条、更新 ${data.linksUpdated} 条，状态「${data.offlineStateText}」`
  );
  reload();
};

/* ---------------- 下发（弹窗方式，保留）---------------- */

const md = reactive({
  visible: false,
  busy: false,
  mediaIds: [] as number[],
  terminalIds: [] as number[],
  mode: "idle" as OfflineMode
});

const openMedia = async () => {
  Object.assign(md, { visible: true, busy: false, mediaIds: [], terminalIds: [], mode: "idle" });
  await Promise.all([searchMedia(""), searchTerminals("")]);
};

const submitMedia = async () => {
  if (!md.mediaIds.length) return ElMessage.warning("请选择媒体");
  if (!md.terminalIds.length) return ElMessage.warning("请选择终端");
  md.busy = true;
  try {
    const { data } = await dispatchOfflineMediaApi(md.mediaIds, md.terminalIds, md.mode);
    ElMessage.success(
      `已下发：副本新建 ${data.copiesCreated} / 更新 ${data.copiesUpdated}，` +
        `关系新建 ${data.linksCreated} / 更新 ${data.linksUpdated}，状态「${data.offlineStateText}」`
    );
    md.visible = false;
    reload();
  } finally {
    md.busy = false;
  }
};

const tk = reactive({
  visible: false,
  busy: false,
  taskIds: [] as number[],
  terminalIds: [] as number[],
  mode: "idle" as OfflineMode
});

const openTask = async () => {
  Object.assign(tk, { visible: true, busy: false, taskIds: [], terminalIds: [], mode: "idle" });
  await Promise.all([loadTasks(), searchTerminals("")]);
};

const submitTask = async () => {
  if (!tk.taskIds.length) return ElMessage.warning("请选择任务");
  if (!tk.terminalIds.length) return ElMessage.warning("请选择终端");
  tk.busy = true;
  try {
    const { data } = await dispatchOfflineTaskApi(tk.taskIds, tk.terminalIds, tk.mode);
    ElMessage.success(`已下发 ${data.taskCopies} 个任务副本、${data.terminalLinks} 条终端关系、${data.mediaLinks} 条媒体关系`);
    if (data.skippedNoMedia?.length) {
      ElMessage.warning(`任务 ${data.skippedNoMedia.join("、")} 没有铃声清单，下发过去也放不出声`);
    }
    tk.visible = false;
    reload();
  } finally {
    tk.busy = false;
  }
};

const stopSelected = async () => {
  const mediaIds = [...new Set(checkedMedia.value.map(r => r.mediaId))];
  const terminalIds = [...new Set(checkedMedia.value.map(r => r.terminalId))];
  await ElMessageBox.confirm(`确定停止选中的 ${checkedMedia.value.length} 条传输？`, "提示", { type: "warning" });
  const { data } = await stopOfflineApi(mediaIds, terminalIds);
  ElMessage.success(`已标记停止 ${data.updated} 条`);
  reload();
};

const pg = reactive({ visible: false, busy: false, confirmText: "" });

const openPurge = async () => {
  const s = await getOfflineSummaryApi();
  summary.value = s.data;
  pg.confirmText = "";
  pg.busy = false;
  pg.visible = true;
};

const submitPurge = async () => {
  pg.busy = true;
  try {
    const { data } = await purgeOfflineApi(pg.confirmText);
    ElMessage.success(
      `已清空：媒体副本 ${data.offlineMedia}、媒体关系 ${data.offlineMediaOfTerminal}、` +
        `任务副本 ${data.offlineTask}、任务关系 ${data.offlineTaskOfTerminal}，复位任务 ${data.tasksReset}`
    );
    pg.visible = false;
    reload();
  } finally {
    pg.busy = false;
  }
};

onMounted(async () => {
  const { data } = await getOfflineStatesApi();
  states.value = data ?? [];
  reload();
  // 「音乐下发」页签是默认页，两侧的候选先拉一次
  await Promise.all([loadDispatchTerminals(), loadDispatchMedia()]);
});
</script>

<style scoped lang="scss">
.offline-page {
  height: 100%;
  padding: 12px;
  overflow: auto;
}
.tool-bar {
  display: flex;
  gap: 10px;
  align-items: center;
  margin-bottom: 12px;
}
.spacer {
  flex: 1;
}
.filter-bar {
  display: flex;
  gap: 10px;
  align-items: center;
  margin-bottom: 10px;
}
.pager {
  margin-top: 10px;
}
.muted {
  font-size: 12px;
  color: var(--el-text-color-placeholder);
}
.danger {
  color: var(--el-color-danger);
}
.opt-sub {
  margin-left: 8px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.fill {
  width: 100%;
}
.form-tip {
  font-size: 12px;
  color: var(--el-text-color-secondary);
  &.block {
    display: block;
    line-height: 1.6;
  }
}
.alert-body {
  margin-top: 4px;
  font-size: 13px;
  line-height: 1.6;
}
.dispatch {
  display: flex;
  gap: 12px;
}
.pane {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-width: 0;
  height: 420px;
  border: 1px solid var(--el-border-color-light);
  border-radius: 6px;
}
.pane-hd {
  display: flex;
  gap: 10px;
  align-items: center;
  justify-content: space-between;
  padding: 8px 10px;
  font-weight: 500;
  border-bottom: 1px solid var(--el-border-color-lighter);
}
.pane-bd {
  flex: 1;
  padding: 8px 10px;
  overflow: auto;
}
.media-line {
  padding: 2px 0;
}
.dispatch-bar {
  display: flex;
  gap: 10px;
  align-items: center;
  margin-top: 12px;
}
.empty-note {
  padding: 18px 0;
  font-size: 13px;
  color: var(--el-text-color-secondary);
  text-align: center;
}
.mb12 {
  margin-bottom: 12px;
}
</style>
