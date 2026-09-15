<!--
  任务传送 —— :80 的 set_offline.php

  左边是一棵只有两个叶子的「任务管理」树，右边是同一张表换数据源：

    任务管理
     ├─ 服务器任务   task       里 offlinestate = 0 的，也就是**还没下发过**的
     └─ 云广播任务   offlinetask 里的副本，也就是**已经下发过**的

  两边合起来才是全集，互不重叠：一条任务被「空闲离线 / 立即离线」发出去之后，
  task.offlinestate 从 0 变成 1 或 2，它就从左边的叶子挪到右边那个去了。

  按钮也不是一套（旧版 offlinetask_form.html 里 getflag==1 / ==2 两个分支）：

    服务器任务：空闲离线 立即离线
    云广播任务：空闲离线 立即离线 空闲删除 立即删除
                停止离线 离线播放 停止离线播放 删除离线音乐

  ⚠ 这一页早前只做了「云广播任务」那一半、而且只有 3 个动作 ——
    现场那句「任务传送不是有任务管理树吗？不是有服务器任务和云广播任务吗」
    指的就是这个。

  行内两个链接「终端 / 媒体」在两个页签下问的是不同的表：
  服务器任务读源表（terminaloftask / mediaoftask），云广播任务读离线表。
  拿离线表去问一条还没下发的任务，只会得到一张空清单。
-->
<template>
  <div class="transfer-page">
    <!-- 左边：任务管理树。旧版 offlinemanagertree.php 里就是这么两个叶子 -->
    <div class="tree-pane">
      <h4 class="tree-title">{{ $t("cloud.taskTree") }}</h4>
      <el-tree
        :data="treeData"
        node-key="key"
        :current-node-key="source"
        highlight-current
        default-expand-all
        :expand-on-click-node="false"
        @node-click="onNode"
      />
    </div>

    <div class="table-pane">
      <!-- 类型页签。旧版这一页写死 tasktype IN (1,2,7)，「全部」是我们多给的 -->
      <el-tabs v-model="kind" class="kind-tabs">
        <el-tab-pane :label="$t('menu.bell')" name="bell" />
        <el-tab-pane :label="$t('menu.task')" name="file" />
        <el-tab-pane :label="$t('common.all')" name="" />
      </el-tabs>

      <!--
        ⚠ :key="source" 是必须的：ProTable 在 setup 里就把 request-api 抓走了
        （useTable(props.requestApi, …)），换了数据源不重新挂载它还在问旧的接口。
      -->
      <ProTable
        :key="source"
        ref="proTableRef"
        :columns="columns"
        :request-api="listApi"
        :init-param="initParam"
        row-key="taskId"
      >
        <template #tableHeader="scope">
          <div class="header-bar">
            <div class="header-left">
              <template v-if="source === 'server'">
                <el-button :disabled="!scope.isSelected" @click="run('idle', scope.selectedListIds)">
                  {{ $t("offline.idleOffline") }}
                </el-button>
                <el-button :disabled="!scope.isSelected" @click="run('immediate', scope.selectedListIds)">
                  {{ $t("offline.nowOffline") }}
                </el-button>
              </template>
              <template v-else>
                <el-button :disabled="!scope.isSelected" @click="run('idle', scope.selectedListIds)">
                  {{ $t("offline.idleOffline") }}
                </el-button>
                <el-button :disabled="!scope.isSelected" @click="run('immediate', scope.selectedListIds)">
                  {{ $t("offline.nowOffline") }}
                </el-button>
                <el-button :disabled="!scope.isSelected" @click="run('deleteIdle', scope.selectedListIds)">
                  {{ $t("offline.idleDelete") }}
                </el-button>
                <el-button :disabled="!scope.isSelected" @click="run('deleteNow', scope.selectedListIds)">
                  {{ $t("offline.nowDelete") }}
                </el-button>
                <el-button :disabled="!scope.isSelected" @click="run('stop', scope.selectedListIds)">
                  {{ $t("cloud.offlineStop") }}
                </el-button>
                <el-button :disabled="!scope.isSelected" @click="run('offlinePlay', scope.selectedListIds)">
                  {{ $t("cloud.offlinePlay") }}
                </el-button>
                <el-button :disabled="!scope.isSelected" @click="run('offlinePlayStop', scope.selectedListIds)">
                  {{ $t("cloud.offlinePlayStop") }}
                </el-button>
                <el-button
                  type="danger"
                  plain
                  :disabled="!scope.isSelected"
                  @click="run('deleteOfflineMusic', scope.selectedListIds)"
                >
                  {{ $t("cloud.delOfflineMusic") }}
                </el-button>
              </template>
            </div>
            <div class="header-right">
              <el-tag type="info" size="small" effect="plain">
                {{ source === "server" ? $t("cloud.isSourceTask") : $t("cloud.isCopyNotSource") }}
              </el-tag>
            </div>
          </div>
        </template>

        <template #taskName="s">
          {{ s.row.taskName }}
          <el-tag v-if="s.row.sourceMissing" type="danger" size="small" effect="plain" class="ml6">
            {{ $t("cloud.sourceMissing") }}
          </el-tag>
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
            <span v-if="s.row.terminalCount" class="small">
              （{{ source === "server" ? s.row.terminalCount : `${s.row.doneCount}/${s.row.terminalCount}` }}）
            </span>
          </el-button>
          <el-button type="primary" link :icon="Files" @click="openMedia(s.row)">
            {{ $t("taskCommon.media") }}
            <span v-if="source === 'server' && s.row.mediaCount" class="small">（{{ s.row.mediaCount }}）</span>
          </el-button>
        </template>
      </ProTable>
    </div>

    <el-dialog v-model="dlg.visible" :title="dlg.title" width="820px" top="6vh">
      <el-table :data="detail" size="small" max-height="420">
        <el-table-column prop="terminalId" label="ID" width="80" />
        <el-table-column prop="terminalname" :label="$t('terminalCommon.terminalName')" min-width="180" show-overflow-tooltip />
        <el-table-column prop="typeName" :label="$t('cloud.model')" min-width="140" show-overflow-tooltip />
        <el-table-column prop="ip" label="IP" width="140" />
        <el-table-column :label="$t('cloud.networked')" width="90">
          <template #default="{ row }">
            <el-tag :type="row.netstate === 1 ? 'success' : 'info'" size="small">
              {{ row.netstate === 1 ? $t("common.online") : $t("common.offline") }}
            </el-tag>
          </template>
        </el-table-column>
        <!--
          服务器任务这一栏看的是「这台能不能收」——没有存储容量的终端存不下离线文件，
          点下发时会被跳过（旧版那句 totalcapacity!='0'）。标出来，免得人以为全发到了。
          云广播任务那边已经发过了，看的是传输进度。
        -->
        <el-table-column v-if="source === 'server'" :label="$t('cloud.canStore')" width="110">
          <template #default="{ row }">
            <el-tag :type="row.capable ? 'success' : 'info'" size="small">
              {{ row.capable ? $t("common.yes") : $t("cloud.willSkip") }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column v-else :label="$t('cloud.transferState')" width="130">
          <template #default="{ row }">
            <el-tag :type="stateType(row.offlinestate)" size="small">{{ row.stateText }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="area" :label="$t('cloud.area')" width="110" />
      </el-table>
      <div v-if="!detail.length" class="empty">
        {{ source === "server" ? $t("cloud.noTerminalsForTask") : $t("cloud.noTerminalsForCopy") }}
      </div>
      <template #footer>
        <el-button @click="dlg.visible = false">{{ $t("common.close") }}</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="mdlg.visible" :title="mdlg.title" width="720px" top="6vh">
      <el-table :data="mediaList" size="small" max-height="420">
        <el-table-column prop="sort" :label="$t('common.index')" width="70" />
        <el-table-column prop="name" :label="$t('taskCommon.mediaName')" min-width="220" show-overflow-tooltip>
          <template #default="{ row }">
            {{ row.name }}
            <el-tag v-if="row.missing" type="danger" size="small" effect="plain" class="ml6">{{
              $t("cloud.copyMissing")
            }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="typeid" :label="$t('media.mediaType')" width="100" />
        <el-table-column :label="$t('taskCommon.mediaSize')" width="110">
          <template #default="{ row }">{{ human(row.size) }}</template>
        </el-table-column>
        <el-table-column v-if="source !== 'server'" :label="$t('cloud.pushProgress')" width="130">
          <template #default="{ row }">{{ $t("cloud.progressN", { done: row.done, total: row.terminals }) }}</template>
        </el-table-column>
      </el-table>
      <div v-if="!mediaList.length" class="empty">
        {{ source === "server" ? $t("cloud.noMediaInTask") : $t("cloud.noMediaInCopy") }}
      </div>
      <template #footer>
        <el-button @click="mdlg.visible = false">{{ $t("common.close") }}</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="tsx" name="cloudTransfer">
import { useI18n } from "vue-i18n";
import { Files, View } from "@element-plus/icons-vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { computed, reactive, ref, watch } from "vue";

import {
  getServerTaskListApi,
  getServerTaskMediaApi,
  getServerTaskTerminalsApi,
  getTransferDetailApi,
  getTransferListApi,
  getTransferMediaApi,
  serverTransferApi,
  transferBulkApi
} from "@/api/modules/ninemod";
import type { TransferMediaItem, TransferTask, TransferTerminal } from "@/api/modules/ninemod";
import ProTable from "@/components/ProTable/index.vue";
import type { ColumnProps, ProTableInstance } from "@/components/ProTable/interface";

// 脚本里拼的文案用 t()；模板里的 $t 不用引入
const { t } = useI18n();

const proTableRef = ref<ProTableInstance>();

/** 左边那棵树。两个叶子，对应旧版 set_offline.php?id=1 和 ?id=2 */
const source = ref<"server" | "cloud">("server");
const treeData = computed(() => [
  {
    key: "root",
    label: t("cloud.taskTree"),
    children: [
      { key: "server", label: t("cloud.serverTasks") },
      { key: "cloud", label: t("cloud.cloudTasks") }
    ]
  }
]);
const onNode = (node: any) => {
  if (node.key === "server" || node.key === "cloud") source.value = node.key;
};

/*
 * 类型页签放在 initParam 里，ProTable 深度 watch 到它变化就重新拉取。
 *
 * kind 单独拎一个 ref、再同步进 initParam：换数据源时 ProTable 整个重挂
 * （见下面的 :key="source"），重挂之后要能接着用上一次选的类型页签，
 * 所以这份状态得活在 ProTable 外面。
 * initParam 这个对象 ProTable 只读不写（useTable 里是 Object.assign 到自己的
 * totalParam 上），两个实例先后共用同一份引用没问题。
 */
const kind = ref("bell");
const initParam = reactive({ kind: kind.value });
watch(kind, v => (initParam.kind = v));

const listApi = computed(() => (source.value === "server" ? getServerTaskListApi : getTransferListApi));

/*
 * 列清单照旧版 offlinetask_form.html，逐列同序：
 *
 *   选择 | 任务名称 | 所属分类 | 播放周期 | 开始日期 | 结束日期 |
 *   播放时间 | 时长 | 状态 | 终端状态 | 任务终端
 *
 * 我们多一个「序号」和一个「铃声音量」——序号是表格惯例，
 * 音量是这一页唯一能看出「发下去会用多大声」的地方。
 */
const columns = computed<ColumnProps<TransferTask>[]>(() => [
  { type: "selection", fixed: "left", width: 50 },
  { type: "index", label: t("enable.options"), width: 60 },
  { prop: "taskName", label: t("taskCommon.taskName"), minWidth: 200 },
  { prop: "category", label: t("cloud.category"), width: 150 },
  { prop: "cycleText", label: t("taskCommon.cycle"), width: 110 },
  { prop: "startdate", label: t("common.startDate"), width: 110 },
  { prop: "enddate", label: t("common.endDate"), width: 110 },
  { prop: "playtime", label: t("taskCommon.runTime"), width: 100 },
  { prop: "lengthText", label: t("taskCommon.playLength"), width: 110 },
  { prop: "projectText", label: t("common.status"), width: 90 },
  { prop: "defaultvolume", label: t("common.volume"), width: 90 },
  // 旧版这一列的表头写的是「终端状态」，不是「离线状态」
  { prop: "offlinestate", label: t("cloud.terminalState"), width: 130 },
  { prop: "operation", label: t("common.operation"), fixed: "right", width: 160 }
]);

// 3 = 离线完成、8 = 删除完成 算「好」；6/7/9/10 是进行中；11/12 是被停掉的
const stateType = (s: number) => {
  if (s === 3 || s === 8) return "success";
  if ([6, 7, 9, 10].includes(s)) return "warning";
  if ([11, 12].includes(s)) return "danger";
  return "info";
};

const dlg = reactive({ visible: false, title: "" });
const detail = ref<TransferTerminal[]>([]);

const openDetail = async (row: TransferTask) => {
  const api = source.value === "server" ? getServerTaskTerminalsApi : getTransferDetailApi;
  const { data } = await api(row.taskId);
  detail.value = data ?? [];
  dlg.title = t("cloud.detailTitle", { name: row.taskName });
  dlg.visible = true;
};

const mdlg = reactive({ visible: false, title: "" });
const mediaList = ref<TransferMediaItem[]>([]);

const openMedia = async (row: TransferTask) => {
  const api = source.value === "server" ? getServerTaskMediaApi : getTransferMediaApi;
  const { data } = await api(row.taskId);
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

const ACTION_TEXT = computed<Record<string, string>>(() => ({
  idle: t("offline.idleOffline"),
  immediate: t("offline.nowOffline"),
  deleteIdle: t("offline.idleDelete"),
  deleteNow: t("offline.nowDelete"),
  stop: t("cloud.offlineStop"),
  offlinePlay: t("cloud.offlinePlay"),
  offlinePlayStop: t("cloud.offlinePlayStop"),
  deleteOfflineMusic: t("cloud.delOfflineMusic")
}));

/*
 * 要先确认的两类动作，和旧版 setofflinetask2() 里那两个 window.confirm 对齐：
 *
 *   空闲删除 / 立即删除  → confirm_deleting
 *   删除离线音乐        → confirm_clear
 *
 * 删除离线音乐比前两个更重：前两个只是**打标记**，由后台广播服务去删；
 * 它是当场把三张离线表里这条任务的行删掉，并给终端发指令删本地文件。
 */
const CONFIRM_DELETE = ["deleteIdle", "deleteNow"];

const run = async (action: string, raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning(t("cloud.pickTaskFirst"));
  const text = ACTION_TEXT.value[action] ?? action;

  if (CONFIRM_DELETE.includes(action)) {
    await ElMessageBox.confirm(t("cloud.confirmDeleting", { n: ids.length, action: text }), text, { type: "warning" });
  } else if (action === "deleteOfflineMusic") {
    await ElMessageBox.confirm(t("cloud.confirmClearMusic", { n: ids.length }), text, {
      type: "warning",
      confirmButtonClass: "el-button--danger"
    });
  }

  const api = source.value === "server" ? serverTransferApi : transferBulkApi;
  const { data } = await api(ids, action);

  if (action === "deleteOfflineMusic") {
    ElMessage.success(t("cloud.deleteMusicDone", { n: data.deletedRows ?? 0, terms: data.terminalCount }));
  } else if (action === "offlinePlay" || action === "offlinePlayStop") {
    // 这两个一行库都不写，回执里不要提「已置为某状态」——那会是句假话
    ElMessage.success(t("cloud.commandSent", { action: data.actionText, n: ids.length }));
  } else if (source.value === "server") {
    ElMessage.success(
      t("cloud.serverTransferDone", {
        action: data.actionText,
        n: data.taskRows,
        terms: data.terminalCount,
        state: data.stateText
      })
    );
  } else {
    ElMessage.success(t("cloud.transferDone", { action: data.actionText, n: data.taskRows, state: data.stateText }));
  }

  proTableRef.value?.clearSelection();
  proTableRef.value?.getTableList();
};
</script>

<style scoped lang="scss">
.transfer-page {
  display: flex;
  gap: 12px;
  height: 100%;
  overflow: hidden;
}
.tree-pane {
  box-sizing: border-box;
  flex: 0 0 200px;
  padding: 12px;
  overflow: auto;
  background-color: var(--el-bg-color);
  border-radius: 6px;
  box-shadow: var(--el-box-shadow-lighter);
}
.tree-title {
  margin: 0 0 8px;
  font-size: 14px;
  font-weight: 600;
}
.table-pane {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-width: 0;
}
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
  flex-wrap: wrap;
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
