<!--
  云广播终端

  「有存储容量的终端」——判据是 terminal.totalcapacity != 0，
  这也是旧版 offlinemusicmanager.php 唯一的筛选条件。
  点开一行能看到这台终端里到底装了哪些离线媒体与离线任务。

  ⚠ resetcapacity 大概率是 restcapacity（剩余）的拼写错误，但现网所有终端
     这两列都是 0，无从佐证。所以这里**原样列出两个数字、不做减法、不算百分比** ——
     算错了会让运维以为终端满了或空着。

  下发动作仍然在「音乐传输」页（/offline），这一页只看现状。
-->
<template>
  <div class="table-box">
    <ProTable ref="proTableRef" :columns="columns" :request-api="getCloudTerminalsApi" :data-callback="dataCallback" row-key="id">
      <!--
        按钮照 :80：空闲传输 / 立即传输 / 停止传输 / 全部清除 / 同步时间 / 清除空闲媒体。
        除「同步时间」是给终端下指令外，其余五个都是**对这台终端上已有的下发关系改状态**，
        不需要再挑一遍媒体 —— 挑媒体是「音乐传输」页的事。
      -->
      <template #tableHeader="scope">
        <div class="header-bar">
          <div class="header-left">
            <el-button :disabled="!scope.isSelected" @click="bulk('idle', scope.selectedListIds)">{{ $t("taskCommon.idleTransfer") }}</el-button>
            <el-button :disabled="!scope.isSelected" @click="bulk('immediate', scope.selectedListIds)"> {{ $t("taskCommon.nowTransfer") }} </el-button>
            <el-button :disabled="!scope.isSelected" @click="bulk('deleteIdle', scope.selectedListIds)"> {{ $t("offline.idleDelete") }} </el-button>
            <el-button :disabled="!scope.isSelected" @click="bulk('deleteNow', scope.selectedListIds)"> {{ $t("offline.nowDelete") }} </el-button>
            <el-button :disabled="!scope.isSelected" @click="bulk('stop', scope.selectedListIds)">{{ $t("cloud.stopTransfer") }}</el-button>
            <el-button :disabled="!scope.isSelected" @click="bulk('clearAll', scope.selectedListIds)"> {{ $t("cloud.clearAll") }} </el-button>
            <el-button :disabled="!scope.isSelected" @click="syncTime(scope.selectedListIds)">{{ $t("term.syncTime") }}</el-button>
            <el-button :disabled="!scope.isSelected" @click="bulk('clearTerminalMedia', scope.selectedListIds)">
              {{ $t("cloud.clearTerminalMedia") }}
            </el-button>
            <el-button :disabled="!scope.isSelected" @click="bulk('clearIdleMedia', scope.selectedListIds)">
              {{ $t("cloud.clearIdleMedia") }}
            </el-button>
            <el-button :icon="Download" @click="goOffline">{{ $t("cloud.goOffline") }}</el-button>
          </div>
          <div class="header-right">
            <el-tag v-if="scopeNote" type="info" size="small" effect="plain">{{ scopeNote }}</el-tag>
            <el-tag type="info" size="small" effect="plain">{{ $t("cloud.onlyWithCapacity") }}</el-tag>
          </div>
        </div>
      </template>

      <template #netstate="s">
        <el-tag :type="s.row.netstate === 1 ? 'success' : 'info'" size="small">
          {{ s.row.netstate === 1 ? $t("common.online") : $t("common.offline") }}
        </el-tag>
      </template>

      <template #taskstate="s">
        <el-tag :type="s.row.taskstate === 1 ? 'warning' : 'info'" size="small" effect="plain">
          {{ s.row.taskstate === 1 ? $t("terminalCommon.playing") : $t("terminalCommon.idle") }}
        </el-tag>
      </template>

      <template #devicestate="s">
        <el-tag :type="s.row.devicestate === 1 ? 'success' : 'info'" size="small" effect="plain">
          {{ s.row.devicestate === 1 ? $t("common.started") : $t("common.stopped") }}
        </el-tag>
      </template>

      <template #totalcapacity="s">{{ human(s.row.totalcapacity) }}</template>
      <template #resetcapacity="s">{{ human(s.row.resetcapacity) }}</template>

      <template #operation="s">
        <el-button type="primary" link :icon="View" @click="openInventory(s.row)">
          {{ $t("cloud.viewContent") }}
          <span v-if="s.row.mediaCount + s.row.taskCount" class="cnt"> （{{ s.row.mediaCount + s.row.taskCount }}） </span>
        </el-button>
      </template>
    </ProTable>

    <el-dialog v-model="inv.visible" :title="inv.title" width="820px" top="6vh">
      <el-tabs v-model="inv.tab">
        <el-tab-pane :label='$t("cloud.mediaTab", { n: mediaItems.length })' name="media">
          <el-table :data="mediaItems" size="small" max-height="420">
            <el-table-column prop="id" :label='$t("cloud.mediaId")' width="90" />
            <el-table-column prop="name" :label='$t("common.name")' min-width="220" show-overflow-tooltip />
            <el-table-column :label='$t("common.size")' width="110">
              <template #default="{ row }">{{ human(row.size) }}</template>
            </el-table-column>
            <el-table-column :label='$t("cloud.belongsTo")' width="130">
              <template #default="{ row }">
                <span v-if="row.taskId">{{ $t("cloud.taskNo", { id: row.taskId }) }}</span>
                <span v-else class="muted">{{ $t("cloud.standalone") }}</span>
              </template>
            </el-table-column>
            <el-table-column :label='$t("common.status")' width="130">
              <template #default="{ row }">
                <el-tag :type="stateType(row.offlinestate)" size="small">{{ row.stateText }}</el-tag>
              </template>
            </el-table-column>
          </el-table>
        </el-tab-pane>

        <el-tab-pane :label='$t("cloud.taskTab", { n: taskItems.length })' name="task">
          <el-table :data="taskItems" size="small" max-height="420">
            <el-table-column prop="id" :label='$t("cloud.taskId")' width="90" />
            <el-table-column prop="name" :label='$t("taskCommon.taskName")' min-width="260" show-overflow-tooltip />
            <el-table-column :label='$t("common.status")' width="130">
              <template #default="{ row }">
                <el-tag :type="stateType(row.offlinestate)" size="small">{{ row.stateText }}</el-tag>
              </template>
            </el-table-column>
          </el-table>
        </el-tab-pane>
      </el-tabs>

      <div v-if="!mediaItems.length && !taskItems.length" class="empty">{{ $t("cloud.emptyInventory") }}</div>

      <template #footer>
        <el-button @click="inv.visible = false">{{ $t("common.close") }}</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="tsx" name="cloudTerminal">
import { useI18n } from "vue-i18n";
import { Download, View } from "@element-plus/icons-vue";
import { computed, reactive, ref } from "vue";
import { useRouter } from "vue-router";

import { ElMessage, ElMessageBox } from "element-plus";

import { cloudBulkApi, getCloudInventoryApi, getCloudTerminalsApi } from "@/api/modules/ninemod";
import type { CloudItem, CloudTerminal } from "@/api/modules/ninemod";
import { syncTerminalTimeApi } from "@/api/modules/terminal";
import ProTable from "@/components/ProTable/index.vue";
import type { ColumnProps, ProTableInstance } from "@/components/ProTable/interface";

// 脚本里拼的文案用 t()；模板里的 $t 不用引入
const { t } = useI18n();

const router = useRouter();
const proTableRef = ref<ProTableInstance>();
const scopeNote = ref("");

const dataCallback = (data: any) => {
  scopeNote.value = data.scopeNote ?? "";
  return { list: data.list, total: data.total, pageNum: data.pageNum, pageSize: data.pageSize };
};

// 列清单严格照 :80（页面规格.txt「云广播终端」）：
// 终端名称 | 终端类型 | 任务状态 | 网络状态 | 设备状态 | IP地址 | 总容量 | 剩余容量 | 操作，无搜索区。
//
// 「任务状态 / 设备状态」我们的列表接口没有单独带出来（terminal.taskstate / devicestate），
// 已在后端补上；「离线内容」那一列去掉了，条数改到操作列里的「查看内容」上看。
const columns = reactive<ColumnProps<CloudTerminal>[]>([
  { type: "selection", fixed: "left", width: 50 },
  { prop: "terminalname", label: t("terminalCommon.terminalName"), minWidth: 180 },
  // 旧版 terminalmanager_form.html 里终端名称后面就是「所属分区」
  { prop: "groupName", label: t("term.myZone"), width: 150, showOverflowTooltip: true },
  { prop: "typeName", label: t("terminalCommon.terminalType"), minWidth: 140, showOverflowTooltip: true },
  { prop: "taskstate", label: t("term.taskStateLabel"), width: 110 },
  { prop: "netstate", label: t("terminalCommon.netState"), width: 110 },
  { prop: "devicestate", label: t("terminalCommon.deviceState"), width: 110 },
  { prop: "ip", label: t("common.ipAddress"), width: 150 },
  { prop: "totalcapacity", label: t("cloud.totalCapacity"), width: 120 },
  { prop: "resetcapacity", label: t("cloud.freeCapacity"), width: 130 },
  { prop: "operation", label: t("common.operation"), fixed: "right", width: 130 }
]);

// human 把字节数变成人看得懂的单位。0 直接显示 0，不显示 "0 B" —— 现网这两列全是 0，
// 显示成 "0 B" 容易让人以为真的量到了一个 0 字节的容量。
const human = (n: number) => {
  if (!n) return "0";
  if (n < 1024) return `${n} B`;
  if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`;
  if (n < 1024 * 1024 * 1024) return `${(n / 1024 / 1024).toFixed(1)} MB`;
  return `${(n / 1024 / 1024 / 1024).toFixed(2)} GB`;
};

// 3 = 离线完成、8 = 删除完成 算「好」；6/7/9/10 是进行中；11/12 是被停掉的
const stateType = (s: number) => {
  if (s === 3 || s === 8) return "success";
  if ([6, 7, 9, 10].includes(s)) return "warning";
  if ([11, 12].includes(s)) return "danger";
  return "info";
};

const inv = reactive({ visible: false, title: "", tab: "media" });
const items = ref<CloudItem[]>([]);
const mediaItems = computed(() => items.value.filter(i => i.kind === "media"));
const taskItems = computed(() => items.value.filter(i => i.kind === "task"));

/** ProTable 的 selectedListIds 是字符串数组（row-key 取的字符串），统一转成数字 */
const toIds = (raw: (string | number)[]) => (raw ?? []).map(Number).filter(n => Number.isFinite(n) && n > 0);

const ACTION_TEXT: Record<string, string> = {
  idle: t("taskCommon.idleTransfer"),
  immediate: t("taskCommon.nowTransfer"),
  deleteIdle: t("offline.idleDelete"),
  deleteNow: t("offline.nowDelete"),
  stop: t("cloud.stopTransfer"),
  clearAll: t("cloud.clearAll"),
  clearTerminalMedia: t("cloud.clearTerminalMedia"),
  clearIdleMedia: t("cloud.clearIdleMedia")
};

/** 会让终端删文件的动作。旧版这几个也都是先弹确认框的。 */
const DESTRUCTIVE = ["deleteIdle", "deleteNow", "clearAll", "clearTerminalMedia", "clearIdleMedia"];

/** 清除类动作不可逆（终端上的文件会被删掉），先确认再发 */
const bulk = async (action: string, raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning(t("cloud.pickTerminalFirst"));
  const text = ACTION_TEXT[action] ?? action;
  if (DESTRUCTIVE.includes(action)) {
    await ElMessageBox.confirm(t("cloud.bulkConfirm", { n: ids.length, action: text }), text, {
      type: "warning"
    });
  }
  const { data } = await cloudBulkApi(ids, action);
  ElMessage.success(
    t("cloud.bulkDone", {
      action: data.actionText,
      media: data.mediaRows,
      task: data.taskRows,
      state: data.stateText
    })
  );
  proTableRef.value?.getTableList();
};

const syncTime = async (raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning(t("cloud.pickTerminalFirst"));
  const { data } = await syncTerminalTimeApi(ids);
  const skipped = data.skipped?.length ?? 0;
  ElMessage.success(
    t("cloud.syncDone", { n: data.succeeded.length }) + (skipped ? t("cloud.syncSkipped", { n: skipped }) : "")
  );
};

const openInventory = async (row: CloudTerminal) => {
  const { data } = await getCloudInventoryApi(row.id);
  items.value = data ?? [];
  inv.title = t("cloud.inventoryTitle", { name: row.terminalname || t("common.terminalNo", { id: row.id }) });
  inv.tab = mediaItems.value.length || !taskItems.value.length ? "media" : "task";
  inv.visible = true;
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
.cnt {
  font-size: 12px;
}
.empty {
  padding: 20px 0;
  font-size: 13px;
  color: var(--el-text-color-secondary);
  text-align: center;
}
</style>
