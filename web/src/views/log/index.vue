<!--
  日志：操作日志（F-54）

  先说清楚一件事，否则用户会误判：**log 表是三方共用的**。
  现网 156 行里 admin 是旧 PHP Web 写的、server 与「主机:-1」是后台 C 服务写的。
  清空会连 C 服务的记录一起清掉，而且清完它还会继续往里写。
  所以清理面板里把「后台服务写的条数」单列出来。

  ⚠ 任务日志（datelog 下每天一个 logYYYY-MM-DD.html）这一页**已经撤掉**，
  连带撤掉的还有后台滚动清理删那批文件的能力 ——
  界面上看不见的东西不该在后台被悄悄删掉。那些文件仍由 C 服务自己管。

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
              <el-button type="danger" :icon="Delete" @click="openClear">{{ $t("log.cleanLog") }}</el-button>

              <!--
                保留期：默认 1 个月，可选 3 个月 / 半年 / 1 年。
                选完点「确定」：设置存下来，超期的当场滚掉，不用等到明天。
                服务里另有一个每天跑一次的定时滚动，不靠这一页开着。
              -->
              <el-divider direction="vertical" />
              <span class="keep-label">{{ $t("log.retention") }}</span>
              <el-select v-model="keepOption" style="width: 120px">
                <el-option v-for="c in keep?.choices ?? []" :key="c.value" :label="c.label" :value="c.value" />
              </el-select>
              <el-button type="primary" :loading="keepSaving" :disabled="!keep" @click="onKeepConfirm">{{
                $t("common.confirm")
              }}</el-button>
            </div>
            <div class="header-right">
              <el-tag v-if="pendingCutoff" type="warning" size="small" effect="plain">
                {{ $t("log.pendingCutoffTip", { date: pendingCutoff }) }}
              </el-tag>
              <el-tag v-if="stats" type="info" size="small" effect="plain">
                {{ $t("log.statsLine", { n: stats.total, from: stats.earliest, to: stats.latest }) }}
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
          <span v-else class="muted">{{ $t("log.notRecorded") }}</span>
        </template>

        <template #ip="scope">
          <span v-if="scope.row.ip">{{ scope.row.ip }}</span>
          <span v-else class="muted">—</span>
        </template>
      </ProTable>
    </div>

    <!-- 清理操作日志 -->
    <el-dialog v-model="clr.visible" :title="$t('log.cleanOpLog')" width="600px">
      <el-alert type="warning" :closable="false" class="mb12">
        <div>{{ $t("log.deleteUsesDelete") }}</div>
        <div v-if="stats?.fromServer">
          {{ $t("log.noteAmong") }} <b>{{ stats.fromServer }}</b> {{ $t("log.serviceWrites") }}
        </div>
      </el-alert>
      <el-form label-width="100px">
        <el-form-item :label="$t('log.cleanMode')">
          <el-radio-group v-model="clr.mode">
            <el-radio value="keepDays">{{ $t("log.keepRecentN") }}</el-radio>
            <el-radio value="beforeDate">{{ $t("log.deleteBefore") }}</el-radio>
            <el-radio value="all">{{ $t("log.clearAll") }}</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-if="clr.mode === 'keepDays'" :label="$t('log.keepDays')">
          <el-input-number v-model="clr.keepDays" :min="1" :max="3650" />
        </el-form-item>
        <el-form-item v-if="clr.mode === 'beforeDate'" :label="$t('log.cutoffDate')">
          <el-date-picker v-model="clr.beforeDate" type="date" value-format="YYYY-MM-DD" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="clr.visible = false">{{ $t("common.cancel") }}</el-button>
        <el-button type="danger" :loading="clr.busy" @click="confirmClear">{{ $t("log.confirmClean") }}</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="logPage">
import { useI18n } from "vue-i18n";
import { computed, onMounted, reactive, ref } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { Delete } from "@element-plus/icons-vue";
import ProTable from "@/components/ProTable/index.vue";
import type { ColumnProps, ProTableInstance } from "@/components/ProTable/interface";
import {
  clearLogsApi,
  getLogListApi,
  getLogStatsApi,
  getRetentionApi,
  setRetentionApi,
  type LogClearMode,
  type LogEntry,
  type LogStats,
  type RetentionOption,
  type RetentionSettings
} from "@/api/modules/log";

// 脚本里拼的文案用 t()；模板里的 $t 不用引入
const { t } = useI18n();

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
  { prop: "id", label: t("common.id"), width: 90, sortable: "custom" },
  {
    prop: "user",
    label: t("common.user"),
    width: 130,
    sortable: "custom",
    search: { el: "input", key: "keyword", props: { placeholder: t("log.keyword") } }
  },
  { prop: "operate", label: t("log.opContent"), minWidth: 220, showOverflowTooltip: true, sortable: "custom" },
  { prop: "ip", label: t("common.ipAddress"), width: 140 },
  { prop: "source", label: t("log.source"), width: 100 },
  { prop: "time", label: t("log.time"), width: 180, sortable: "custom" }
]);

const dataCallback = (data: any) => ({
  list: data.list,
  total: data.total,
  pageNum: data.pageNum,
  pageSize: data.pageSize
});

const sourceTag = (s: string) => (s === t("log.backendService") ? "warning" : s === "Web" ? "success" : "info");

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
  if (clr.mode === "beforeDate" && !clr.beforeDate) return ElMessage.warning(t("log.pickCutoff"));
  const label =
    clr.mode === "all"
      ? t("log.clearAll")
      : clr.mode === "keepDays"
        ? t("log.keepRecent", { n: clr.keepDays })
        : t("log.deleteBeforeN", { date: clr.beforeDate });
  await ElMessageBox.confirm(t("log.confirmCleanBy", { label }), t("common.doubleConfirm"), { type: "warning" });

  clr.busy = true;
  try {
    const { data } = await clearLogsApi({
      mode: clr.mode,
      beforeDate: clr.beforeDate,
      keepDays: clr.keepDays
    });
    ElMessage.success(t("log.deletedRows", { n: data.deleted, kept: data.kept, audit: data.auditLogId }));
    clr.visible = false;
    proTableRef.value?.getTableList();
    loadStats();
  } finally {
    clr.busy = false;
  }
};

/* ---------------- 日志保留期 ----------------

  默认 1 个月，可选 3 个月 / 半年 / 1 年。选完点「确定」：设置存下来，
  超期的当场滚掉。服务里另有一个每天跑一次的定时滚动，不依赖这一页开着。

  「确定」这一下是会删数据的，所以点之前把边界日期摆在旁边
  （pendingCutoff 跟着下拉走，不是跟着已保存的设置走）——
  人在按下去之前就看得到自己要删掉哪一天之前的东西。
*/
const keep = ref<RetentionSettings | null>(null);
const keepOption = ref<RetentionOption>("1m");
const keepSaving = ref(false);

/** 下拉里当前选中那一档对应的保留边界。切换下拉就跟着变，让人先看到再点确定 */
const pendingCutoff = computed(() => {
  if (!keep.value) return "";
  if (keepOption.value === keep.value.option) return keep.value.cutoffDate;
  const months: Record<RetentionOption, number> = { "1m": 1, "3m": 3, "6m": 6, "1y": 12 };
  const d = new Date();
  d.setMonth(d.getMonth() - (months[keepOption.value] ?? 1));
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
});

const loadKeep = async () => {
  try {
    const { data } = await getRetentionApi();
    keep.value = data;
    keepOption.value = data.option;
  } catch {
    keep.value = null;
  }
};

const onKeepConfirm = async () => {
  if (!keep.value) return;
  keepSaving.value = true;
  try {
    const { data } = await setRetentionApi(keepOption.value);
    keep.value = data.settings;
    keepOption.value = data.settings.option;
    const p = data.purge;
    let msg = t("log.retentionIs", { label: data.settings.label });
    if (p) msg += t("log.cutoffSummary", { cutoff: p.cutoff, rows: p.operationRows });
    ElMessage.success(msg);
    await loadStats();
    proTableRef.value?.getTableList();
  } catch {
    // 保存失败就把下拉退回原值，别让界面显示一个没生效的设置
    if (keep.value) keepOption.value = keep.value.option;
  } finally {
    keepSaving.value = false;
  }
};

onMounted(() => {
  loadStats();
  loadKeep();
});
</script>

<style scoped lang="scss">
.keep-label {
  font-size: 13px;
  color: var(--el-text-color-secondary);
}
.log-page {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: 0 12px;
}
.header-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
}
.muted {
  color: var(--el-text-color-placeholder);
}
.mb12 {
  margin-bottom: 12px;
}
</style>
