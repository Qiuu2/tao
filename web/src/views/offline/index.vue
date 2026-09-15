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
        <!-- 整句一个键：这是一串「数字 + 量词」交替的句子，
             拆开翻译中文能拼出来，英文会拼成一句不通的话 -->
        {{
          $t("offline.summaryLine", {
            media: summary.offlineMedia,
            task: summary.offlineTask,
            mediaLinks: summary.offlineMediaOfTerminal,
            taskLinks: summary.offlineTaskOfTerminal,
            marked: summary.tasksMarked
          })
        }}
      </template>
    </el-alert>

    <el-tabs v-model="tab" @tab-change="reload">
      <!--
        「音乐下发」这一页照 :80 的音乐传输：左边终端树、右边媒体清单，
        底下两个按钮 空闲传输 / 立即传输。挑完直接发，不再经过弹窗。
        后面两个页签是我们多给的「发了之后到哪一步了」，:80 没有对应视图。
      -->
      <el-tab-pane :label="$t('offline.musicPush')" name="dispatch">
        <div class="dispatch">
          <div class="pane">
            <div class="pane-hd">
              <span>{{ $t("terminalCommon.terminal") }}</span>
              <el-input
                v-model="dp.termKeyword"
                :placeholder="$t('offline.searchDeviceName')"
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
              <span>{{ $t("taskCommon.media") }}</span>
              <el-input
                v-model="dp.mediaKeyword"
                :placeholder="$t('common.searchMediaName')"
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
              <div v-if="!dp.medias.length" class="empty-note">{{ $t("offline.noMatchingMedia") }}</div>
            </el-scrollbar>
          </div>
        </div>

        <!-- 按钮照旧版 set_offlinemusic.html：空闲传输 / 立即传输 / 空闲删除 / 立即删除
             （「全部清除」在页顶那个「清空全部离线数据」上） -->
        <div class="dispatch-bar">
          <div class="spacer" />
          <el-button :disabled="!canDispatch" @click="doDispatch('idle')">{{ $t("taskCommon.idleTransfer") }}</el-button>
          <el-button type="primary" :disabled="!canDispatch" @click="doDispatch('immediate')">{{
            $t("taskCommon.nowTransfer")
          }}</el-button>
          <el-button :disabled="!canDispatch" @click="doDispatch('deleteIdle')">{{ $t("offline.idleDelete") }}</el-button>
          <el-button type="danger" :disabled="!canDispatch" @click="doDispatch('deleteNow')">{{
            $t("offline.nowDelete")
          }}</el-button>
        </div>
      </el-tab-pane>

      <el-tab-pane :label="$t('offline.mediaPushState')" name="media">
        <div class="filter-bar">
          <el-select
            v-model="filter.state"
            :placeholder="$t('offline.allStates')"
            clearable
            style="width: 160px"
            @change="reload"
          >
            <el-option v-for="s in states" :key="s.value" :label="s.text" :value="s.value" />
          </el-select>
          <el-input-number
            v-model="filter.terminalId"
            :min="0"
            :placeholder="$t('offline.terminalId')"
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
          <el-table-column :label="$t('taskCommon.media')" min-width="180">
            <template #default="{ row }">
              <span :class="{ danger: row.copyMissing }">{{ row.mediaName }}</span>
              <span class="muted"> #{{ row.mediaId }}</span>
            </template>
          </el-table-column>
          <el-table-column :label="$t('terminalCommon.terminal')" min-width="160">
            <template #default="{ row }">
              <span :class="{ danger: row.terminalMissing }">{{ row.terminalName }}</span>
              <span class="muted"> #{{ row.terminalId }}</span>
            </template>
          </el-table-column>
          <el-table-column :label="$t('offline.belongTask')" width="150">
            <template #default="{ row }">
              <span v-if="row.taskId">{{ row.taskName || row.taskId }}</span>
              <span v-else class="muted">{{ $t("offline.pushSingle") }}</span>
            </template>
          </el-table-column>
          <el-table-column :label="$t('common.status')" width="130">
            <template #default="{ row }">
              <el-tag :type="stateTag(row.offlinestate)" size="small">{{ row.offlineStateText }}</el-tag>
            </template>
          </el-table-column>
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
    </el-tabs>
  </div>
</template>

<script setup lang="ts" name="offlinePage">
import { useI18n } from "vue-i18n";
import { computed, onMounted, reactive, ref } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { useAuthStore } from "@/stores/modules/auth";
import {
  dispatchOfflineMediaApi,
  getOfflineMediaStatusApi,
  getOfflineStatesApi,
  getOfflineSummaryApi,
  getOfflineTaskStatusApi,
  stopOfflineApi,
  type OfflineMediaStatus,
  type OfflineMode,
  type OfflineStateDef,
  type OfflineSummary,
  type OfflineTaskStatus
} from "@/api/modules/offline";
import { searchTaskMediaApi, searchTaskTerminalsApi, type MediaOption } from "@/api/modules/task";

// 脚本里拼的文案用 t()；模板里的 $t 不用引入
const { t } = useI18n();
// 有的循环里临时变量也叫 t（一台终端），会遮住上面这个 t
const t2 = t;

const authStore = useAuthStore();
const btn = computed(() => (authStore.authButtonListGet as any) ?? {});
const canMedia = computed(() => !!btn.value.terminal?.edit);

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
    const name = t.groupName || t2("offline.unclassified");
    if (!groups.has(name)) groups.set(name, { key: `g:${name}`, label: name, children: [] });
    groups.get(name)!.children!.push({
      key: `t:${t.id}`,
      label: t2("offline.termWithState", {
        name: t.name || t2("common.terminalNo", { id: t.id }),
        state: t.netstate === 1 ? t2("common.online") : t2("common.offline")
      }),
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
  idle: t("taskCommon.idleTransfer"),
  immediate: t("taskCommon.nowTransfer"),
  deleteIdle: t("offline.idleDelete"),
  deleteNow: t("offline.nowDelete")
};

const doDispatch = async (mode: OfflineMode) => {
  if (!canDispatch.value) return ElMessage.warning(t("offline.pickTermAndMedia"));
  const text = DISPATCH_TEXT[mode] ?? mode;
  // 删除类动作会让终端把文件删掉，先确认（旧版这两个也是先弹确认框的）
  if (mode === "deleteIdle" || mode === "deleteNow") {
    await ElMessageBox.confirm(
      t("offline.confirmPush", { terms: dp.terminalIds.length, media: dp.mediaIds.length, what: text }) + t("offline.filesGone"),
      text,
      { type: "warning" }
    );
  }
  const { data } = await dispatchOfflineMediaApi(dp.mediaIds, dp.terminalIds, mode);
  ElMessage.success(
    t("offline.pushed", { what: text, media: data.mediaCount, terms: data.terminalCount }) +
      t("offline.linkStats", { created: data.linksCreated, updated: data.linksUpdated, state: data.offlineStateText })
  );
  reload();
};

/* ---------------- 下发（弹窗方式，保留）---------------- */

const stopSelected = async () => {
  const mediaIds = [...new Set(checkedMedia.value.map(r => r.mediaId))];
  const terminalIds = [...new Set(checkedMedia.value.map(r => r.terminalId))];
  await ElMessageBox.confirm(t("offline.confirmStop", { n: checkedMedia.value.length }), t("offline.tip"), { type: "warning" });
  const { data } = await stopOfflineApi(mediaIds, terminalIds);
  ElMessage.success(t("offline.markedStopped", { n: data.updated }));
  reload();
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
