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
      <el-tab-pane label="作息方案" name="bell" />
      <el-tab-pane label="文件广播" name="file" />
      <el-tab-pane label="全部" name="" />
    </el-tabs>

    <ProTable
      ref="proTableRef"
      :columns="columns"
      :request-api="getTransferListApi"
      :init-param="initParam"
      row-key="taskId"
    >
      <template #tableHeader="scope">
        <div class="header-bar">
          <div class="header-left">
            <el-button :disabled="!scope.isSelected" @click="bulk('idle', scope.selectedListIds)">空闲传输</el-button>
            <el-button :disabled="!scope.isSelected" @click="bulk('immediate', scope.selectedListIds)">
              立即传输
            </el-button>
            <el-button :disabled="!scope.isSelected" @click="bulk('stop', scope.selectedListIds)">停止传输</el-button>
            <el-button :icon="Download" @click="goOffline">去音乐传输下发</el-button>
          </div>
          <div class="header-right">
            <el-tag type="info" size="small" effect="plain">这里是离线副本，不是原任务</el-tag>
          </div>
        </div>
      </template>

      <template #taskName="s">
        {{ s.row.taskName }}
        <el-tag v-if="s.row.sourceMissing" type="danger" size="small" effect="plain" class="ml6">原任务已删除</el-tag>
      </template>

      <template #offlinestate="s">
        <el-tag :type="stateType(s.row.offlinestate)" size="small">{{ s.row.stateText }}</el-tag>
      </template>

      <!-- 行内两个链接照 :80：终端 / 媒体 -->
      <template #operation="s">
        <el-button type="primary" link :icon="View" @click="openDetail(s.row)">
          终端
          <span v-if="s.row.terminalCount" class="small">（{{ s.row.doneCount }}/{{ s.row.terminalCount }}）</span>
        </el-button>
        <el-button type="primary" link :icon="Files" @click="openMedia(s.row)">媒体</el-button>
      </template>
    </ProTable>

    <el-dialog v-model="dlg.visible" :title="dlg.title" width="760px" top="6vh">
      <el-table :data="detail" size="small" max-height="420">
        <el-table-column prop="terminalId" label="ID" width="80" />
        <el-table-column prop="terminalname" label="终端名称" min-width="180" show-overflow-tooltip />
        <el-table-column prop="typeName" label="型号" min-width="140" show-overflow-tooltip />
        <el-table-column prop="ip" label="IP" width="140" />
        <el-table-column label="联网" width="90">
          <template #default="{ row }">
            <el-tag :type="row.netstate === 1 ? 'success' : 'info'" size="small">
              {{ row.netstate === 1 ? "在线" : "离线" }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="传输状态" width="130">
          <template #default="{ row }">
            <el-tag :type="stateType(row.offlinestate)" size="small">{{ row.stateText }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="area" label="区域" width="110" />
      </el-table>
      <div v-if="!detail.length" class="empty">这条离线副本还没有下发到任何终端。</div>
      <template #footer>
        <el-button @click="dlg.visible = false">关闭</el-button>
      </template>
    </el-dialog>

    <!-- 行内「媒体」链接：这条离线任务副本里带了哪些媒体 -->
    <el-dialog v-model="mdlg.visible" :title="mdlg.title" width="720px" top="6vh">
      <el-table :data="mediaList" size="small" max-height="420">
        <el-table-column prop="sort" label="序号" width="70" />
        <el-table-column prop="name" label="媒体名称" min-width="220" show-overflow-tooltip>
          <template #default="{ row }">
            {{ row.name }}
            <el-tag v-if="row.missing" type="danger" size="small" effect="plain" class="ml6">副本已删除</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="typeid" label="媒体类型" width="100" />
        <el-table-column label="媒体大小" width="110">
          <template #default="{ row }">{{ human(row.size) }}</template>
        </el-table-column>
        <el-table-column label="下发进度" width="130">
          <template #default="{ row }">{{ row.done }} / {{ row.terminals }} 台</template>
        </el-table-column>
      </el-table>
      <div v-if="!mediaList.length" class="empty">这条离线副本里没有媒体。</div>
      <template #footer>
        <el-button @click="mdlg.visible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="tsx" name="cloudTransfer">
import { Download, Files, View } from "@element-plus/icons-vue";
import { ElMessage } from "element-plus";
import { reactive, ref } from "vue";
import { useRouter } from "vue-router";

import {
  getTransferDetailApi,
  getTransferListApi,
  getTransferMediaApi,
  transferBulkApi
} from "@/api/modules/ninemod";
import type { TransferMediaItem, TransferTask, TransferTerminal } from "@/api/modules/ninemod";
import ProTable from "@/components/ProTable/index.vue";
import type { ColumnProps, ProTableInstance } from "@/components/ProTable/interface";

const router = useRouter();
const proTableRef = ref<ProTableInstance>();

// 列清单严格照 :80（页面规格.txt「任务传送」）：
// 任务名称 | 播放周期 | 开始日期 | 结束日期 | 执行时间 | 播放时长 | 状态 | 离线状态 | 操作，无搜索区。
const columns = reactive<ColumnProps<TransferTask>[]>([
  { type: "selection", fixed: "left", width: 50 },
  { prop: "taskName", label: "任务名称", minWidth: 220 },
  { prop: "cycleText", label: "播放周期", width: 120 },
  { prop: "startdate", label: "开始日期", width: 120 },
  { prop: "enddate", label: "结束日期", width: 120 },
  { prop: "playtime", label: "执行时间", width: 110 },
  { prop: "lengthText", label: "播放时长", width: 120 },
  { prop: "projectText", label: "状态", width: 100 },
  { prop: "offlinestate", label: "离线状态", width: 140 },
  { prop: "operation", label: "操作", fixed: "right", width: 120 }
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
  dlg.title = `「${row.taskName}」的下发终端`;
  dlg.visible = true;
};

const mdlg = reactive({ visible: false, title: "" });
const mediaList = ref<TransferMediaItem[]>([]);

const openMedia = async (row: TransferTask) => {
  const { data } = await getTransferMediaApi(row.taskId);
  mediaList.value = data ?? [];
  mdlg.title = `「${row.taskName}」的媒体清单`;
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
  if (!ids.length) return ElMessage.warning("请先勾选任务");
  const { data } = await transferBulkApi(ids, action);
  ElMessage.success(`${data.actionText}：${data.taskRows} 条下发关系已置为「${data.stateText}」`);
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
