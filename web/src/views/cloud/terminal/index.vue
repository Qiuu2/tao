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
    <ProTable
      ref="proTableRef"
      :columns="columns"
      :request-api="getCloudTerminalsApi"
      :data-callback="dataCallback"
      row-key="id"
    >
      <!--
        按钮照 :80：空闲传输 / 立即传输 / 停止传输 / 全部清除 / 同步时间 / 清除空闲媒体。
        除「同步时间」是给终端下指令外，其余五个都是**对这台终端上已有的下发关系改状态**，
        不需要再挑一遍媒体 —— 挑媒体是「音乐传输」页的事。
      -->
      <template #tableHeader="scope">
        <div class="header-bar">
          <div class="header-left">
            <el-button :disabled="!scope.isSelected" @click="bulk('idle', scope.selectedListIds)">空闲传输</el-button>
            <el-button :disabled="!scope.isSelected" @click="bulk('immediate', scope.selectedListIds)">
              立即传输
            </el-button>
            <el-button :disabled="!scope.isSelected" @click="bulk('stop', scope.selectedListIds)">停止传输</el-button>
            <el-button :disabled="!scope.isSelected" @click="bulk('clearAll', scope.selectedListIds)">
              全部清除
            </el-button>
            <el-button :disabled="!scope.isSelected" @click="syncTime(scope.selectedListIds)">同步时间</el-button>
            <el-button :disabled="!scope.isSelected" @click="bulk('clearIdleMedia', scope.selectedListIds)">
              清除空闲媒体
            </el-button>
            <el-button :icon="Download" @click="goOffline">去音乐传输下发</el-button>
          </div>
          <div class="header-right">
            <el-tag v-if="scopeNote" type="info" size="small" effect="plain">{{ scopeNote }}</el-tag>
            <el-tag type="info" size="small" effect="plain">只列有存储容量的终端</el-tag>
          </div>
        </div>
      </template>

      <template #netstate="s">
        <el-tag :type="s.row.netstate === 1 ? 'success' : 'info'" size="small">
          {{ s.row.netstate === 1 ? "在线" : "离线" }}
        </el-tag>
      </template>

      <template #taskstate="s">
        <el-tag :type="s.row.taskstate === 1 ? 'warning' : 'info'" size="small" effect="plain">
          {{ s.row.taskstate === 1 ? "播放中" : "空闲" }}
        </el-tag>
      </template>

      <template #devicestate="s">
        <el-tag :type="s.row.devicestate === 1 ? 'success' : 'info'" size="small" effect="plain">
          {{ s.row.devicestate === 1 ? "已启动" : "已停止" }}
        </el-tag>
      </template>

      <template #totalcapacity="s">{{ human(s.row.totalcapacity) }}</template>
      <template #resetcapacity="s">{{ human(s.row.resetcapacity) }}</template>

      <template #operation="s">
        <el-button type="primary" link :icon="View" @click="openInventory(s.row)">
          查看内容
          <span v-if="s.row.mediaCount + s.row.taskCount" class="cnt">
            （{{ s.row.mediaCount + s.row.taskCount }}）
          </span>
        </el-button>
      </template>
    </ProTable>

    <el-dialog v-model="inv.visible" :title="inv.title" width="820px" top="6vh">
      <el-tabs v-model="inv.tab">
        <el-tab-pane :label="`离线媒体（${mediaItems.length}）`" name="media">
          <el-table :data="mediaItems" size="small" max-height="420">
            <el-table-column prop="id" label="媒体 ID" width="90" />
            <el-table-column prop="name" label="名称" min-width="220" show-overflow-tooltip />
            <el-table-column label="大小" width="110">
              <template #default="{ row }">{{ human(row.size) }}</template>
            </el-table-column>
            <el-table-column label="归属" width="130">
              <template #default="{ row }">
                <span v-if="row.taskId">任务 {{ row.taskId }}</span>
                <span v-else class="muted">独立下发</span>
              </template>
            </el-table-column>
            <el-table-column label="状态" width="130">
              <template #default="{ row }">
                <el-tag :type="stateType(row.offlinestate)" size="small">{{ row.stateText }}</el-tag>
              </template>
            </el-table-column>
          </el-table>
        </el-tab-pane>

        <el-tab-pane :label="`离线任务（${taskItems.length}）`" name="task">
          <el-table :data="taskItems" size="small" max-height="420">
            <el-table-column prop="id" label="任务 ID" width="90" />
            <el-table-column prop="name" label="任务名称" min-width="260" show-overflow-tooltip />
            <el-table-column label="状态" width="130">
              <template #default="{ row }">
                <el-tag :type="stateType(row.offlinestate)" size="small">{{ row.stateText }}</el-tag>
              </template>
            </el-table-column>
          </el-table>
        </el-tab-pane>
      </el-tabs>

      <div v-if="!mediaItems.length && !taskItems.length" class="empty">这台终端里还没有任何离线内容。</div>

      <template #footer>
        <el-button @click="inv.visible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="tsx" name="cloudTerminal">
import { Download, View } from "@element-plus/icons-vue";
import { computed, reactive, ref } from "vue";
import { useRouter } from "vue-router";

import { ElMessage, ElMessageBox } from "element-plus";

import { cloudBulkApi, getCloudInventoryApi, getCloudTerminalsApi } from "@/api/modules/ninemod";
import type { CloudItem, CloudTerminal } from "@/api/modules/ninemod";
import { syncTerminalTimeApi } from "@/api/modules/terminal";
import ProTable from "@/components/ProTable/index.vue";
import type { ColumnProps, ProTableInstance } from "@/components/ProTable/interface";

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
  { prop: "terminalname", label: "终端名称", minWidth: 180 },
  { prop: "typeName", label: "终端类型", minWidth: 140, showOverflowTooltip: true },
  { prop: "taskstate", label: "任务状态", width: 110 },
  { prop: "netstate", label: "网络状态", width: 110 },
  { prop: "devicestate", label: "设备状态", width: 110 },
  { prop: "ip", label: "IP地址", width: 150 },
  { prop: "totalcapacity", label: "总容量", width: 120 },
  { prop: "resetcapacity", label: "剩余容量", width: 130 },
  { prop: "operation", label: "操作", fixed: "right", width: 130 }
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
  idle: "空闲传输",
  immediate: "立即传输",
  stop: "停止传输",
  clearAll: "全部清除",
  clearIdleMedia: "清除空闲媒体"
};

/** 清除类动作不可逆（终端上的文件会被删掉），先确认再发 */
const bulk = async (action: string, raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning("请先勾选终端");
  const text = ACTION_TEXT[action] ?? action;
  if (action === "clearAll" || action === "clearIdleMedia") {
    await ElMessageBox.confirm(
      `将对选中的 ${ids.length} 台终端执行「${text}」，终端上的离线内容会被删除，不可恢复。`,
      text,
      { type: "warning" }
    );
  }
  const { data } = await cloudBulkApi(ids, action);
  ElMessage.success(
    `${data.actionText}：媒体 ${data.mediaRows} 条、任务 ${data.taskRows} 条已置为「${data.stateText}」，` +
      `实际传输由后台广播服务完成`
  );
  proTableRef.value?.getTableList();
};

const syncTime = async (raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning("请先勾选终端");
  const { data } = await syncTerminalTimeApi(ids);
  const skipped = data.skipped?.length ?? 0;
  ElMessage.success(`时间同步指令已下发 ${data.succeeded.length} 台${skipped ? `，跳过 ${skipped} 台` : ""}`);
};

const openInventory = async (row: CloudTerminal) => {
  const { data } = await getCloudInventoryApi(row.id);
  items.value = data ?? [];
  inv.title = `${row.terminalname || "终端 " + row.id} 的离线内容`;
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
