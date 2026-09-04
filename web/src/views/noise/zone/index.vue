<!--
  声场分区

  把**一组终端**和**一组噪声探头**绑在一起：探头量到环境噪声，
  后台据此把这组终端的音量调上去或调下来。

  终端归属记两份：soundgroup 关联表 + terminal.soundsgroupid 单值列。
  旧版新建/修改分区时只写前者、从来不写后者（D-220），
  只在删除分区时才复位 —— 于是建完分区，终端自己那一列还是 0，
  后台按它取值就找不到分区。新版两处同时维护。
-->
<template>
  <div class="table-box">
    <ProTable ref="proTableRef" :columns="columns" :request-api="getSoundGroupsApi" :data-callback="dataCallback" row-key="id">
      <template #tableHeader="scope">
        <div class="header-bar">
          <!-- 按钮照旧版 streammanager_form.html：全选 / 取消 / 添加分区 / 修改分区 / 删除分区
               （全选与取消由 ProTable 的复选框代劳） -->
          <div class="header-left">
            <el-button type="primary" :disabled="!canEdit" @click="openCreate">添加分区</el-button>
            <el-button :disabled="!canEdit || scope.selectedListIds.length !== 1" @click="openEditById(scope.selectedListIds)">
              修改分区
            </el-button>
            <el-button type="danger" :disabled="!canEdit || !scope.isSelected" @click="doDelete(scope.selectedListIds)">
              删除分区
            </el-button>
          </div>
          <div class="header-right">
            <el-tag v-if="scopeNote" type="info" size="small" effect="plain">{{ scopeNote }}</el-tag>
          </div>
        </div>
      </template>

      <template #operation="s">
        <!-- 旧版这一列就是一个「浏览终端」链接（zhaoshengdisplayterminal.php） -->
        <el-button type="primary" link @click="openTerminals(s.row)">浏览终端</el-button>
        <el-button type="primary" link :icon="EditPen" :disabled="!canEdit || !s.row.canModify" @click="openEdit(s.row)">
          修改
        </el-button>
        <el-button type="danger" link :icon="Delete" :disabled="!canEdit || !s.row.canModify" @click="doDelete([s.row.id])">
          删除
        </el-button>
      </template>
    </ProTable>

    <el-dialog v-model="dlg.visible" :title="dlg.title" width="760px" top="6vh">
      <!-- 表单项照 :80 的「添加分区」弹窗：分区名称 / 选择终端 / 选择设备 -->
      <el-form :model="form" label-width="110px">
        <el-form-item label="分区名称" required>
          <el-input v-model="form.name" maxlength="21" show-word-limit placeholder="请输入分区名称" />
        </el-form-item>
        <el-form-item label="选择终端">
          <!-- 按终端分区分组的树。⚠ 这个接口的主键叫 terminalId 不是 id，组件里已适配 -->
          <TerminalTree v-model="selectedTerminals" :terminals="terminals" :loading="terminalLoading" @search="searchTerminals" />
        </el-form-item>
        <el-form-item label="选择设备">
          <el-select
            v-model="selectedDevices"
            multiple
            filterable
            collapse-tags
            collapse-tags-tooltip
            placeholder="选择探头"
            class="fill"
          >
            <el-option v-for="d in devices" :key="d.id" :label="`${d.name}（${d.ip}）`" :value="d.id">
              <span>{{ d.name }}</span>
              <span class="opt-sub">{{ d.ip }} · 地址 {{ d.devaddr }}</span>
              <el-tag v-if="d.groupId && d.groupId !== dlg.id" size="small" type="warning" effect="plain" class="opt-tag">
                已属于「{{ d.groupName || d.groupId }}」
              </el-tag>
            </el-option>
          </el-select>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dlg.visible = false">取消</el-button>
        <el-button type="primary" :loading="dlg.saving" @click="submit">提交</el-button>
      </template>
    </el-dialog>

    <!-- 浏览终端：旧版 zhaoshengdisplayterminal.php 那张表 -->
    <el-dialog v-model="termDlg.visible" :title="termDlg.title" width="760px" top="8vh">
      <el-table :data="termDlg.list" size="small" max-height="400" empty-text="这个分区里还没有终端">
        <el-table-column prop="terminalname" label="终端名称" min-width="180" show-overflow-tooltip />
        <el-table-column prop="typeName" label="终端类型" width="150" show-overflow-tooltip />
        <el-table-column label="网络状态" width="110">
          <template #default="{ row }">
            <el-tag :type="row.netstate === 1 ? 'success' : 'info'" size="small">
              {{ row.netstate === 1 ? "在线" : "离线" }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="ip" label="终端IP" width="160" />
      </el-table>
      <template #footer>
        <el-button @click="termDlg.visible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="tsx" name="noiseZone">
import { Delete, EditPen } from "@element-plus/icons-vue";
import { ElMessage, ElMessageBox, ElNotification } from "element-plus";
import { computed, onMounted, reactive, ref } from "vue";

import TerminalTree from "@/components/TerminalTree/index.vue";

import {
  createSoundGroupApi,
  deleteSoundGroupsApi,
  getSoundDeviceOptionsApi,
  getSoundGroupApi,
  getSoundGroupsApi,
  getSoundGroupTerminalsApi,
  updateSoundGroupApi
} from "@/api/modules/ninemod";
import type { SoundDevice, SoundGroup, SoundGroupTerminal } from "@/api/modules/ninemod";
import ProTable from "@/components/ProTable/index.vue";
import { useAuthStore } from "@/stores/modules/auth";
import type { ColumnProps, ProTableInstance } from "@/components/ProTable/interface";

const authStore = useAuthStore();
const canEdit = computed(() => !!(authStore.authButtonListGet as any)?.zone?.edit);
const toIds = (raw: (string | number)[]) => (raw ?? []).map(Number).filter(n => Number.isFinite(n) && n > 0);

const proTableRef = ref<ProTableInstance>();
const scopeNote = ref("");

const dataCallback = (data: any) => {
  scopeNote.value = data.scopeNote ?? "";
  return { list: data.list, total: data.total, pageNum: data.pageNum, pageSize: data.pageSize };
};

// 列清单严格照 :80：分区名称 | 操作，就这两列，无搜索区。
// 终端数 / 探头数 / 创建者已按要求去掉；点「修改」进弹窗仍能看到分区里有哪些终端和探头。
const columns = reactive<ColumnProps<SoundGroup>[]>([
  { type: "selection", fixed: "left", width: 50 },
  { prop: "name", label: "分区名称", minWidth: 400 },
  { prop: "operation", label: "操作", fixed: "right", width: 230 }
]);

const refresh = () => proTableRef.value?.getTableList();

const terminals = ref<SoundGroupTerminal[]>([]);
const terminalLoading = ref(false);
const selectedTerminals = ref<number[]>([]);
const devices = ref<SoundDevice[]>([]);
const selectedDevices = ref<number[]>([]);

const searchTerminals = async (kw: string) => {
  terminalLoading.value = true;
  try {
    const { data } = await getSoundGroupTerminalsApi(kw ?? "");
    terminals.value = data ?? [];
  } finally {
    terminalLoading.value = false;
  }
};

const loadDevices = async () => {
  const { data } = await getSoundDeviceOptionsApi();
  devices.value = data ?? [];
};

const form = reactive({ name: "" });
const dlg = reactive({ visible: false, saving: false, isEdit: false, title: "", id: 0 });

const openCreate = async () => {
  form.name = "";
  selectedTerminals.value = [];
  selectedDevices.value = [];
  Object.assign(dlg, { visible: true, saving: false, isEdit: false, title: "添加分区", id: 0 });
  await Promise.all([searchTerminals(""), loadDevices()]);
};

const openEdit = async (row: SoundGroup) => {
  const { data } = await getSoundGroupApi(row.id);
  form.name = data.name;
  // 已删除的终端不回填，否则保存时会被服务端的存在性校验挡下来
  selectedTerminals.value = data.terminals.filter(t => !t.deleted).map(t => t.terminalId);
  selectedDevices.value = data.devices.map(d => d.id);
  const dropped = data.terminals.filter(t => t.deleted).length;
  Object.assign(dlg, { visible: true, saving: false, isEdit: true, title: `修改声场分区：${data.name}`, id: data.id });
  await Promise.all([searchTerminals(""), loadDevices()]);
  if (dropped) ElMessage.warning(`该分区里有 ${dropped} 台终端已被删除，已自动移除`);
};

/** 工具栏上的「修改分区」：旧版是「勾一条再点」，这里保留同一套语义 */
const openEditById = async (raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (ids.length !== 1) return ElMessage.warning("请勾选一个分区");
  await openEdit({ id: ids[0] } as SoundGroup);
};

/* ---------------- 浏览终端 ---------------- */

const termDlg = reactive({ visible: false, title: "", list: [] as SoundGroupTerminal[] });

const openTerminals = async (row: SoundGroup) => {
  const { data } = await getSoundGroupApi(row.id);
  termDlg.title = `${data.name} 的终端`;
  termDlg.list = data.terminals ?? [];
  termDlg.visible = true;
};

const submit = async () => {
  if (!form.name.trim()) return ElMessage.warning("请输入分区名称");
  const body = {
    name: form.name.trim(),
    terminalIds: selectedTerminals.value,
    deviceIds: selectedDevices.value
  };
  dlg.saving = true;
  try {
    if (dlg.isEdit) await updateSoundGroupApi(dlg.id, body);
    else await createSoundGroupApi(body);
    ElMessage.success("保存成功");
    dlg.visible = false;
    refresh();
  } finally {
    dlg.saving = false;
  }
};

const doDelete = async (raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning("请先勾选声场分区");
  await ElMessageBox.confirm(
    `确认删除选中的 ${ids.length} 个声场分区？成员终端与探头会被移出分区（设备本身不删）。`,
    "删除声场分区",
    { type: "warning", confirmButtonText: "确认删除" }
  );
  const { data } = await deleteSoundGroupsApi(ids);
  ElNotification({
    title: "删除完成",
    message: `分区 ${data.deleted.length} 个，${data.resetTerminals} 台终端、${data.resetDevices} 台探头已移出分区`,
    type: "success"
  });
  refresh();
};

onMounted(async () => {
  await Promise.all([searchTerminals(""), loadDevices()]);
});
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
.warn {
  color: var(--el-color-warning);
}
.opt-sub {
  margin-left: 10px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.opt-tag {
  margin-left: 8px;
}
.tip {
  margin-top: 4px;
  font-size: 12px;
  line-height: 1.7;
  color: var(--el-text-color-secondary);
}
.fill {
  width: 100%;
}
</style>
