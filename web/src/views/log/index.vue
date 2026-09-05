<!--
  日志：操作日志（F-54）+ 任务日志（F-55）

  两件事必须先说清楚，否则用户会误判：

  1. **log 表是三方共用的**。现网 156 行里 admin 是旧 PHP Web 写的、
     server 与「主机:-1」是后台 C 服务写的。清空会连 C 服务的记录一起清掉，
     而且清完它还会继续往里写。所以清理面板里把「后台服务写的条数」单列出来。

  2. **任务日志不在数据库里**，是后台服务写在 datelog/ 下的
     logYYYY-MM-DD.html 文件。现网 414 个文件 5.4MB。

  相对旧版的关键修复：
    · 筛选的**列名**来自 URL 直接拼进 SQL（`where log.$searchkey like '%$v%'`），
      列名与值双重注入 → 列名走白名单、值走参数绑定
    · 排序 `ORDER BY $searchsequence DESC` 无引号裸拼 → 白名单
    · 清空用 TRUNCATE（DDL、隐式提交、重置自增）→ 改 DELETE，并支持按天保留
    · 清空本身不留痕 → 同事务先写一条审计记录，再删 id 小于它的行
    · 权限判断从「用户名等于 admin」改成按 id 判定
-->
<template>
  <div class="log-page">
    <el-tabs v-model="tab" class="log-tabs">
      <!-- ============ 操作日志 ============ -->
      <el-tab-pane label="操作日志" name="operate">
        <div class="table-box">
          <ProTable
            ref="proTableRef"
            :columns="columns"
            :request-api="getLogListApi"
            :init-param="initParam"
            :data-callback="dataCallback"
            row-key="id"
            @sort-change="onSortChange"
          >
            <template #tableHeader>
              <div class="header-bar">
                <div class="header-left">
                  <el-button type="danger" :icon="Delete" @click="openClear">清理日志</el-button>
                </div>
                <div class="header-right">
                  <el-tag v-if="stats" type="info" size="small" effect="plain">
                    共 {{ stats.total }} 条 · {{ stats.earliest }} ~ {{ stats.latest }}
                  </el-tag>
                </div>
              </div>
            </template>

            <template #source="scope">
              <el-tag :type="sourceTag(scope.row.source)" size="small" effect="plain">
                {{ scope.row.source }}
              </el-tag>
            </template>

            <template #user="scope">
              <span v-if="scope.row.user">{{ scope.row.user }}</span>
              <span v-else class="muted">(未记录)</span>
            </template>

            <template #ip="scope">
              <span v-if="scope.row.ip">{{ scope.row.ip }}</span>
              <span v-else class="muted">—</span>
            </template>
          </ProTable>
        </div>
      </el-tab-pane>

      <!-- ============ 任务日志 ============ -->
      <el-tab-pane label="任务日志" name="task">
        <div class="task-log">
          <el-alert v-if="taskLogError" type="warning" :closable="false" class="mb12">
            {{ taskLogError }}
          </el-alert>

          <template v-else>
            <div class="task-bar">
              <el-date-picker
                v-model="fileRange"
                type="daterange"
                value-format="YYYY-MM-DD"
                start-placeholder="起始日期"
                end-placeholder="结束日期"
                size="default"
                @change="loadFiles"
              />
              <el-button :icon="Refresh" @click="loadFiles">刷新</el-button>
              <el-button type="danger" :icon="Delete" :disabled="!files?.dirWritable" @click="openTaskClear">
                清理任务日志
              </el-button>
              <span v-if="files" class="task-summary">
                {{ files.total }} 个文件 · {{ humanSize(files.totalSize) }} · {{ files.dir }}
              </span>
            </div>

            <!-- 目录还没建出来：这不是故障，说清楚就行，别让人以为日志丢了 -->
            <el-alert v-if="files?.note" type="info" :closable="false" show-icon class="mb12">
              {{ files.note }}
            </el-alert>
            <el-alert v-else-if="files && !files.dirWritable" type="info" :closable="false" class="mb12">
              当前服务进程对该目录没有写权限，清理功能不可用。
            </el-alert>

            <div class="task-body">
              <el-table :data="files?.files ?? []" height="calc(100vh - 320px)" @row-click="openFile">
                <el-table-column prop="date" label="日期" width="120" />
                <el-table-column label="大小" width="100">
                  <template #default="{ row }">{{ humanSize(row.size) }}</template>
                </el-table-column>
                <el-table-column prop="modTime" label="修改时间" width="170" />
                <el-table-column label="" width="80">
                  <template #default="{ row }">
                    <el-tag v-if="row.today" type="warning" size="small" effect="plain">今天</el-tag>
                  </template>
                </el-table-column>
              </el-table>
            </div>
          </template>
        </div>
      </el-tab-pane>
    </el-tabs>

    <!-- 清理操作日志 -->
    <el-dialog v-model="clr.visible" title="清理操作日志" width="600px">
      <el-alert type="warning" :closable="false" class="mb12">
        <div>清理使用 DELETE，可回滚、不重置自增（旧版用的是 TRUNCATE）。</div>
        <div v-if="stats?.fromServer">
          注意：其中 <b>{{ stats.fromServer }}</b> 条是后台广播服务写的（回收临时文件、播放通道等），
          清理会一并删除，且服务之后还会继续写入。
        </div>
      </el-alert>
      <el-form label-width="100px">
        <el-form-item label="清理方式">
          <el-radio-group v-model="clr.mode">
            <el-radio value="keepDays">只保留最近 N 天</el-radio>
            <el-radio value="beforeDate">删除某日之前</el-radio>
            <el-radio value="all">全部清空</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-if="clr.mode === 'keepDays'" label="保留天数">
          <el-input-number v-model="clr.keepDays" :min="1" :max="3650" />
        </el-form-item>
        <el-form-item v-if="clr.mode === 'beforeDate'" label="截止日期">
          <el-date-picker v-model="clr.beforeDate" type="date" value-format="YYYY-MM-DD" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="clr.visible = false">取消</el-button>
        <el-button type="danger" :loading="clr.busy" @click="confirmClear">确认清理</el-button>
      </template>
    </el-dialog>

    <!-- 清理任务日志 -->
    <el-dialog v-model="tclr.visible" title="清理任务日志" width="640px">
      <el-form label-width="100px">
        <el-form-item label="清理方式">
          <el-radio-group v-model="tclr.mode" @change="previewTaskClear">
            <el-radio value="keepDays">只保留最近 N 天</el-radio>
            <el-radio value="beforeDate">删除某日之前</el-radio>
            <el-radio value="all">全部清空</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-if="tclr.mode === 'keepDays'" label="保留天数">
          <el-input-number v-model="tclr.keepDays" :min="1" :max="3650" @change="previewTaskClear" />
        </el-form-item>
        <el-form-item v-if="tclr.mode === 'beforeDate'" label="截止日期">
          <el-date-picker v-model="tclr.beforeDate" type="date" value-format="YYYY-MM-DD" @change="previewTaskClear" />
        </el-form-item>
      </el-form>

      <el-descriptions v-if="tclr.preview" :column="2" border size="small">
        <el-descriptions-item label="将删除">{{ tclr.preview.count }} 个文件</el-descriptions-item>
        <el-descriptions-item label="释放空间">{{ humanSize(tclr.preview.size) }}</el-descriptions-item>
      </el-descriptions>
      <el-alert v-if="tclr.preview?.skippedToday.length" type="info" :closable="false" class="mt12">
        跳过今天的文件（{{ tclr.preview.skippedToday.join("、") }}）——
        后台服务很可能正开着句柄往里写，删掉后它会继续写进一个已被删除的 inode，日志静默丢失。
      </el-alert>
      <template #footer>
        <el-button @click="tclr.visible = false">取消</el-button>
        <el-button type="danger" :loading="tclr.busy" :disabled="!tclr.preview?.count" @click="confirmTaskClear">
          确认删除{{ tclr.preview?.count ? `(${tclr.preview.count})` : "" }}
        </el-button>
      </template>
    </el-dialog>

    <!-- 任务日志内容 -->
    <el-drawer v-model="viewer.visible" :title="viewer.name" size="70%">
      <el-alert v-if="viewer.truncated" type="info" :closable="false" class="mb12"> 文件较大，仅显示末尾部分内容。 </el-alert>
      <el-alert v-if="viewer.gbkLines" type="info" :closable="false" class="mb12">
        该文件里有 {{ viewer.gbkLines }} 行是 GBK 编码（其余是 UTF-8），已自动转码后显示。
        后台服务在同一个文件里混用了两种编码，旧页面对这部分内容一直是乱码。
      </el-alert>
      <!--
        内容是后台服务写的 HTML 片段，这里刻意用 <pre> 文本渲染而不是 v-html：
        C 服务会把任务名、终端名一类的用户可控内容直接拼进去，
        v-html 渲染等于把它变成存储型 XSS。
      -->
      <pre class="log-content">{{ viewer.content }}</pre>
    </el-drawer>
  </div>
</template>

<script setup lang="ts" name="logPage">
import { onMounted, reactive, ref } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { Delete, Refresh } from "@element-plus/icons-vue";
import ProTable from "@/components/ProTable/index.vue";
import type { ColumnProps, ProTableInstance } from "@/components/ProTable/interface";
import {
  clearLogsApi,
  deleteTaskLogsApi,
  getLogListApi,
  getLogStatsApi,
  getTaskLogFilesApi,
  previewDeleteTaskLogsApi,
  readTaskLogApi,
  type LogClearMode,
  type LogEntry,
  type LogStats,
  type TaskLogDeletePreview,
  type TaskLogFile,
  type TaskLogList
} from "@/api/modules/log";

const tab = ref("operate");
const proTableRef = ref<ProTableInstance>();
const stats = ref<LogStats | null>(null);
const initParam = reactive({ orderBy: "", order: "" });

const onSortChange = ({ prop, order }: { prop: string; order: string | null }) => {
  if (!order) {
    initParam.orderBy = "";
    initParam.order = "";
    return;
  }
  initParam.orderBy = prop;
  initParam.order = order === "ascending" ? "asc" : "desc";
};

const columns = reactive<ColumnProps<LogEntry>[]>([
  { prop: "id", label: "编号", width: 90, sortable: "custom" },
  {
    prop: "user",
    label: "用户",
    width: 130,
    sortable: "custom",
    search: { el: "input", key: "keyword", props: { placeholder: "关键字" } }
  },
  { prop: "operate", label: "操作内容", minWidth: 220, showOverflowTooltip: true, sortable: "custom" },
  { prop: "ip", label: "IP地址", width: 140 },
  { prop: "source", label: "来源", width: 100 },
  { prop: "time", label: "时间", width: 180, sortable: "custom" }
]);

const dataCallback = (data: any) => ({
  list: data.list,
  total: data.total,
  pageNum: data.pageNum,
  pageSize: data.pageSize
});

const sourceTag = (s: string) => (s === "后台服务" ? "warning" : s === "Web" ? "success" : "info");

const loadStats = async () => {
  const { data } = await getLogStatsApi();
  stats.value = data;
};

/* ---------------- 清理操作日志 ---------------- */

const clr = reactive({
  visible: false,
  busy: false,
  mode: "keepDays" as LogClearMode,
  keepDays: 90,
  beforeDate: ""
});

const openClear = async () => {
  await loadStats();
  clr.visible = true;
  clr.busy = false;
};

const confirmClear = async () => {
  if (clr.mode === "beforeDate" && !clr.beforeDate) return ElMessage.warning("请选择截止日期");
  const label =
    clr.mode === "all" ? "全部清空" : clr.mode === "keepDays" ? `只保留最近 ${clr.keepDays} 天` : `删除 ${clr.beforeDate} 之前`;
  await ElMessageBox.confirm(`确定按「${label}」清理操作日志？`, "二次确认", { type: "warning" });

  clr.busy = true;
  try {
    const { data } = await clearLogsApi({
      mode: clr.mode,
      beforeDate: clr.beforeDate,
      keepDays: clr.keepDays
    });
    ElMessage.success(`已删除 ${data.deleted} 条，剩余 ${data.kept} 条（审计记录 #${data.auditLogId}）`);
    clr.visible = false;
    proTableRef.value?.getTableList();
    loadStats();
  } finally {
    clr.busy = false;
  }
};

/* ---------------- 任务日志 ---------------- */

const files = ref<TaskLogList | null>(null);
const taskLogError = ref("");
/** 空数组表示不限日期；用 undefined/null 会被 el-date-picker 的类型拒掉 */
const fileRange = ref<[string, string] | []>([]);

const loadFiles = async () => {
  try {
    const { data } = await getTaskLogFilesApi({
      from: fileRange.value?.[0] ?? "",
      to: fileRange.value?.[1] ?? ""
    });
    files.value = data;
    taskLogError.value = "";
  } catch (e: any) {
    taskLogError.value = e?.message ?? "任务日志不可用";
  }
};

const humanSize = (n: number) => {
  if (n < 1024) return `${n} B`;
  if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`;
  return `${(n / 1024 / 1024).toFixed(1)} MB`;
};

const viewer = reactive({ visible: false, name: "", content: "", truncated: false, gbkLines: 0 });

const openFile = async (row: TaskLogFile) => {
  const { data } = await readTaskLogApi(row.name);
  viewer.name = data.name;
  viewer.content = data.content;
  viewer.truncated = data.truncated;
  viewer.gbkLines = data.gbkLines;
  viewer.visible = true;
};

const tclr = reactive({
  visible: false,
  busy: false,
  mode: "keepDays" as LogClearMode,
  keepDays: 90,
  beforeDate: "",
  preview: null as TaskLogDeletePreview | null
});

const previewTaskClear = async () => {
  if (tclr.mode === "beforeDate" && !tclr.beforeDate) {
    tclr.preview = null;
    return;
  }
  const { data } = await previewDeleteTaskLogsApi({
    mode: tclr.mode,
    beforeDate: tclr.beforeDate,
    keepDays: tclr.keepDays
  });
  tclr.preview = data;
};

const openTaskClear = async () => {
  tclr.visible = true;
  tclr.busy = false;
  await previewTaskClear();
};

const confirmTaskClear = async () => {
  await ElMessageBox.confirm(`确定删除 ${tclr.preview?.count} 个任务日志文件？此操作不可恢复。`, "二次确认", {
    type: "warning"
  });
  tclr.busy = true;
  try {
    const { data } = await deleteTaskLogsApi({
      mode: tclr.mode,
      beforeDate: tclr.beforeDate,
      keepDays: tclr.keepDays
    });
    ElMessage.success(`已删除 ${data.deleted.length} 个文件，释放 ${humanSize(data.freedBytes)}`);
    if (data.failed.length) ElMessage.warning(`${data.failed.length} 个文件删除失败：${data.failed.join("、")}`);
    tclr.visible = false;
    loadFiles();
  } finally {
    tclr.busy = false;
  }
};

onMounted(() => {
  loadStats();
  loadFiles();
});
</script>

<style scoped lang="scss">
.log-page {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: 0 12px;
}
.log-tabs {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-height: 0;
  :deep(.el-tabs__content) {
    flex: 1;
    min-height: 0;
  }
  :deep(.el-tab-pane) {
    height: 100%;
  }
}
.header-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
}
.task-log {
  padding: 12px;
  background: var(--el-bg-color);
  border-radius: 6px;
}
.task-bar {
  display: flex;
  gap: 10px;
  align-items: center;
  margin-bottom: 12px;
}
.task-summary {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.log-content {
  max-height: calc(100vh - 160px);
  padding: 12px;
  overflow: auto;
  font-size: 12px;
  line-height: 1.6;
  white-space: pre-wrap;
  word-break: break-all;
  background: var(--el-fill-color-light);
  border-radius: 4px;
}
.muted {
  color: var(--el-text-color-placeholder);
}
.form-tip {
  margin-left: 8px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
  &.block {
    display: block;
    margin-left: 0;
    line-height: 1.6;
  }
}
.mb12 {
  margin-bottom: 12px;
}
.mt12 {
  margin-top: 12px;
}
</style>
