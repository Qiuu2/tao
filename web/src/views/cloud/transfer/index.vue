<!--
  任务传送

  offlinetask 是 task 的**离线副本**：下发一条任务给云广播终端时，
  整行任务被复制到 offlinetask，下发关系记在 offlinetaskofterminal。
  这一页看的就是这些副本，以及每条副本发给了谁、到哪一步了。

  相对旧版放开了两处：
    · 旧版必须先选一台终端才能看（displayofflinetask.php 的 SQL 就是这么写的）。
      这里不选终端就列全部副本，选了再收窄。
    · 旧版限定 tasktype IN (1,2)。那会让功放/语音/LED 的离线副本凭空消失 ——
      offlinetask 的列结构和 task 完全一样，它们确实能被下发。这里不限类型。

  下发与停止仍然在「音乐传输」页（/offline），这一页只看现状。
-->
<template>
  <div class="table-box">
    <!-- 页签照 :80：作息方案 | 文件广播。多留一个「全部」，因为功放/语音/LED 的副本也能存在 -->
    <el-tabs v-model="initParam.kind" class="kind-tabs">
      <el-tab-pane :label='$t("menu.bell")' name="bell" />
      <el-tab-pane :label='$t("menu.task")' name="file" />
      <el-tab-pane :label='$t("common.all")' name="" />
    </el-tabs>

    <ProTable ref="proTableRef" :columns="columns" :request-api="getTransferListApi" :init-param="initParam" row-key="taskId">
      <template #tableHeader="scope">
        <div class="header-bar">
          <div class="header-left">
            <el-button :disabled="!scope.isSelected" @click="bulk('idle', scope.selectedListIds)">{{ $t("taskCommon.idleTransfer") }}</el-button>
            <el-button :disabled="!scope.isSelected" @click="bulk('immediate', scope.selectedListIds)"> {{ $t("taskCommon.nowTransfer") }} </el-button>
            <el-button :disabled="!scope.isSelected" @click="bulk('stop', scope.selectedListIds)">{{ $t("cloud.stopTransfer") }}</el-button>
            <el-button :icon="Download" @click="goOffline">{{ $t("cloud.goOffline") }}</el-button>
          </div>
          <div class="header-right">
            <el-tag type="info" size="small" effect="plain">{{ $t("cloud.isCopyNotSource") }}</el-tag>
          </div>
        </div>
      </template>

      <template #taskName="s">
        {{ s.row.taskName }}
        <el-tag v-if="s.row.sourceMissing" type="danger" size="small" effect="plain" class="ml6">{{ $t("cloud.sourceMissing") }}</el-tag>
      </template>

      <!-- 所属分类：作息方案连它的方案名一起显示（旧版 `作息方案(info)`） -->
      <template #category="s">
        {{ s.row.typeText }}<span v-if="s.row.info" class="muted">（{{ s.row.info }}）</span>
      </template>

      <template #offlinestate="s">
        <el-tag :type="stateType(s.row.offlinestate)" size="small">{{ s.row.stateText }}</el-tag>
      </template>

      <!-- 行内两个链接照 :80：终端 / 媒体 -->
      <template #operation="s">
        <el-button type="primary" link :icon="View" @click="openDetail(s.row)">
          {{ $t("terminalCommon.terminal") }}
          <span v-if="s.row.terminalCount" class="small">（{{ s.row.doneCount }}/{{ s.row.terminalCount }}）</span>
        </el-button>
        <el-button type="primary" link :icon="Files" @click="openMedia(s.row)">{{ $t("taskCommon.media") }}</el-button>
      </template>
    </ProTable>

    <el-dialog v-model="dlg.visible" :title="dlg.title" width="760px" top="6vh">
      <el-table :data="detail" size="small" max-height="420">
        <el-table-column prop="terminalId" label="ID" width="80" />
        <el-table-column prop="terminalname" :label='$t("terminalCommon.terminalName")' min-width="180" show-overflow-tooltip />
        <el-table-column prop="typeName" :label='$t("cloud.model")' min-width="140" show-overflow-tooltip />
        <el-table-column prop="ip" label="IP" width="140" />
        <el-table-column :label='$t("cloud.networked")' width="90">
          <template #default="{ row }">
            <el-tag :type="row.netstate === 1 ? 'success' : 'info'" size="small">
              {{ row.netstate === 1 ? $t("common.online") : $t("common.offline") }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column :label='$t("cloud.transferState")' width="130">
          <template #default="{ row }">
            <el-tag :type="stateType(row.offlinestate)" size="small">{{ row.stateText }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="area" :label='$t("cloud.area")' width="110" />
      </el-table>
      <div v-if="!detail.length" class="empty">{{ $t("cloud.noTerminalsForCopy") }}</div>
      <template #footer>
        <el-button @click="dlg.visible = false">{{ $t("common.close") }}</el-button>
      </template>
    </el-dialog>

    <!-- 行内「媒体」链接：这条离线任务副本里带了哪些媒体 -->
    <el-dialog v-model="mdlg.visible" :title="mdlg.title" width="720px" top="6vh">
      <el-table :data="mediaList" size="small" max-height="420">
        <el-table-column prop="sort" :label='$t("common.index")' width="70" />
        <el-table-column prop="name" :label='$t("taskCommon.mediaName")' min-width="220" show-overflow-tooltip>
          <template #default="{ row }">
            {{ row.name }}
            <el-tag v-if="row.missing" type="danger" size="small" effect="plain" class="ml6">{{ $t("cloud.copyMissing") }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="typeid" :label='$t("media.mediaType")' width="100" />
        <el-table-column :label='$t("taskCommon.mediaSize")' width="110">
          <template #default="{ row }">{{ human(row.size) }}</template>
        </el-table-column>
        <el-table-column :label='$t("cloud.pushProgress")' width="130">
          <template #default="{ row }">{{ $t("cloud.progressN", { done: row.done, total: row.terminals }) }}</template>
        </el-table-column>
      </el-table>
      <div v-if="!mediaList.length" class="empty">{{ $t("cloud.noMediaInCopy") }}</div>
      <template #footer>
        <el-button @click="mdlg.visible = false">{{ $t("common.close") }}</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="tsx" name="cloudTransfer">
import { useI18n } from "vue-i18n";
import { Download, Files, View } from "@element-plus/icons-vue";
import { ElMessage } from "element-plus";
import { reactive, ref } from "vue";
import { useRouter } from "vue-router";

import { getTransferDetailApi, getTransferListApi, getTransferMediaApi, transferBulkApi } from "@/api/modules/ninemod";
import type { TransferMediaItem, TransferTask, TransferTerminal } from "@/api/modules/ninemod";
import ProTable from "@/components/ProTable/index.vue";
import type { ColumnProps, ProTableInstance } from "@/components/ProTable/interface";

// 脚本里拼的文案用 t()；模板里的 $t 不用引入
const { t } = useI18n();

const router = useRouter();
const proTableRef = ref<ProTableInstance>();

/*
 * 列清单照 ok112 **真正能用的那一页**：displayofflinetask.php +
 * offlinetask/displayterminaltask_form.html，逐列同序 ——
 *
 *   序号 | 任务名称 | 所属类型 | 执行模式 | 任务状态 | 铃声音量 |
 *   播放时间 | 开始日期 | 结束日期 | 时长 | 任务终端
 *
 * ⚠ 不照 offline_task.php（那才是旧版叫「任务传送」的那个单独页）——
 *   它的主查询是 `SELECT … FROM task WHERE offlinetask = 1`，而 task 表里
 *   **没有 offlinetask 这一列**（只有 offlinestate），所以那一页在旧系统里
 *   从来没有列出过任何东西；而且它那两个动作链接指向的是**按键映射**：
 *   「设置绑定」→ set_task_mapping.php，「删除离线」→ do.php?act=del_task_mapping_msg，
 *   后者真正执行的是 `DELETE FROM terminalkeymap/terminalkey WHERE … IN (选中的任务 id)`。
 *   照抄等于给这一页装一个会删掉按键映射的按钮。详见开发文档 7.5.3。
 *
 * 我们比旧版多两列，都是这一页的正事，不去掉：
 *   · 终端状态：这条副本发到哪一步了（旧版这一页压根不显示传输状态）
 *   · 操作：终端清单 / 媒体清单，对应旧版那一列「任务终端」的链接
 */
const columns = reactive<ColumnProps<TransferTask>[]>([
  { type: "selection", fixed: "left", width: 50 },
  { type: "index", label: t("enable.options"), width: 60 },
  { prop: "taskName", label: t("taskCommon.taskName"), minWidth: 200 },
  // 旧版 offlinetask_form.html 这一列叫「所属分类」，作息方案还带着方案名
  { prop: "category", label: t("cloud.category"), width: 150 },
  { prop: "cycleText", label: t("taskCommon.cycle"), width: 110 },
  { prop: "projectText", label: t("common.status"), width: 90 },
  { prop: "defaultvolume", label: t("common.volume"), width: 90 },
  { prop: "playtime", label: t("taskCommon.runTime"), width: 100 },
  { prop: "startdate", label: t("common.startDate"), width: 110 },
  { prop: "enddate", label: t("common.endDate"), width: 110 },
  { prop: "lengthText", label: t("taskCommon.playLength"), width: 110 },
  // 旧版这一列的表头写的是「终端状态」，不是「离线状态」
  { prop: "offlinestate", label: t("cloud.terminalState"), width: 130 },
  { prop: "operation", label: t("common.operation"), fixed: "right", width: 120 }
]);

// 3 = 离线完成、8 = 删除完成 算「好」；6/7/9/10 是进行中；11/12 是被停掉的
const stateType = (s: number) => {
  if (s === 3 || s === 8) return "success";
  if ([6, 7, 9, 10].includes(s)) return "warning";
  if ([11, 12].includes(s)) return "danger";
  return "info";
};

// 页签放在 initParam 里，ProTable 深度 watch 到变化就重新拉取
const initParam = reactive({ kind: "bell" });

const dlg = reactive({ visible: false, title: "" });
const detail = ref<TransferTerminal[]>([]);

const openDetail = async (row: TransferTask) => {
  const { data } = await getTransferDetailApi(row.taskId);
  detail.value = data ?? [];
  dlg.title = t("cloud.detailTitle", { name: row.taskName });
  dlg.visible = true;
};

const mdlg = reactive({ visible: false, title: "" });
const mediaList = ref<TransferMediaItem[]>([]);

const openMedia = async (row: TransferTask) => {
  const { data } = await getTransferMediaApi(row.taskId);
  mediaList.value = data ?? [];
  mdlg.title = t("cloud.mediaTitle", { name: row.taskName });
  mdlg.visible = true;
};

const human = (n: number) => {
  if (!n) return "0";
  if (n < 1024) return `${n} B`;
  if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`;
  return `${(n / 1024 / 1024).toFixed(1)} MB`;
};

const toIds = (raw: (string | number)[]) => (raw ?? []).map(Number).filter(n => Number.isFinite(n) && n > 0);

const bulk = async (action: string, raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning(t("cloud.pickTaskFirst"));
  const { data } = await transferBulkApi(ids, action);
  ElMessage.success(t("cloud.transferDone", { action: data.actionText, n: data.taskRows, state: data.stateText }));
  proTableRef.value?.getTableList();
};

const goOffline = () => router.push("/offline");
</script>

<style scoped lang="scss">
.header-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
  justify-content: space-between;
  width: 100%;
}
.header-left,
.header-right {
  display: flex;
  gap: 8px;
  align-items: center;
}
.muted {
  color: var(--el-text-color-secondary);
}
.small {
  font-size: 12px;
}
.ml6 {
  margin-left: 6px;
}
.kind-tabs {
  :deep(.el-tabs__header) {
    margin-bottom: 8px;
  }
}
.empty {
  padding: 20px 0;
  font-size: 13px;
  color: var(--el-text-color-secondary);
  text-align: center;
}
</style>
