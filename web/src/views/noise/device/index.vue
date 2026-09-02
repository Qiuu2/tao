<!--
  噪声设备（探头）

  sounddevice (id, ip, devaddr, name, groupid, dbvalue, sendport)。
  探头量到环境噪声，后台据此把所在声场分区里那组终端的音量调上去或调下来。

  ⚠ dbvalue 是探头采回来的实测值，Web 只读不写 —— 旧版修改设备时也不碰它。
  ⚠ devaddr 是 tinyint(3) unsigned（0~255）。旧版不校验，填 300 会被静默截成 255，
     指向另一台设备。这里挡住。
  归属（groupid）由「声场分区」页维护，这一页不改它 —— 在这里改会绕过分区那边的一致性处理。
-->
<template>
  <div class="table-box">
    <ProTable
      ref="proTableRef"
      :columns="columns"
      :request-api="getSoundDevicesApi"
      :init-param="initParam"
      row-key="id"
      @sort-change="onSortChange"
    >
      <!-- 按钮对齐 :80（docs/image/oktw/页面规格.txt「噪声设备」）：添加设备 / 删除设备 -->
      <template #tableHeader="scope">
        <div class="header-bar">
          <div class="header-left">
            <el-button type="primary" :disabled="!canEdit" @click="openCreate">添加设备</el-button>
            <el-button type="danger" :disabled="!canEdit || !scope.isSelected" @click="doDelete(scope.selectedListIds)">
              删除设备
            </el-button>
          </div>
          <div class="header-right">
            <el-tag type="info" size="small" effect="plain">噪声值由后台采集回写，本页只读</el-tag>
          </div>
        </div>
      </template>

      <template #dbvalue="s">
        <el-tag v-if="s.row.dbvalue > 0" size="small" effect="plain">{{ s.row.dbvalue }} dB</el-tag>
        <span v-else class="muted">未采到</span>
      </template>

      <template #operation="s">
        <el-button type="primary" link :icon="EditPen" :disabled="!canEdit" @click="openEdit(s.row)">修改</el-button>
        <el-button type="danger" link :icon="Delete" :disabled="!canEdit" @click="doDelete([s.row.id])">删除</el-button>
      </template>
    </ProTable>

    <el-dialog v-model="dlg.visible" :title="dlg.title" width="560px">
      <!-- 表单项与占位符照 :80 的「添加设备」弹窗 -->
      <el-form :model="form" label-width="120px">
        <el-form-item label="设备地址名称" required>
          <el-input v-model="form.name" maxlength="10" show-word-limit placeholder="请输入设备地址名称" />
        </el-form-item>
        <el-form-item label="设备IP" required>
          <el-input v-model="form.ip" placeholder="请输入设备IP" />
        </el-form-item>
        <el-form-item label="设备地址" required>
          <el-input-number v-model="form.devaddr" :min="0" :max="255" controls-position="right" />
        </el-form-item>
        <el-form-item label="发送端口">
          <el-input-number v-model="form.sendport" :min="0" :max="65535" controls-position="right" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dlg.visible = false">取消</el-button>
        <el-button type="primary" :loading="dlg.saving" @click="submit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="tsx" name="noiseDevice">
import { Delete, EditPen } from "@element-plus/icons-vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { computed, reactive, ref } from "vue";

import {
  createSoundDeviceApi,
  deleteSoundDevicesApi,
  getSoundDeviceApi,
  getSoundDevicesApi,
  updateSoundDeviceApi
} from "@/api/modules/ninemod";
import type { SoundDevice } from "@/api/modules/ninemod";
import ProTable from "@/components/ProTable/index.vue";
import { useAuthStore } from "@/stores/modules/auth";
import type { ColumnProps, ProTableInstance } from "@/components/ProTable/interface";

const authStore = useAuthStore();
const canEdit = computed(() => !!(authStore.authButtonListGet as any)?.zone?.edit);
const toIds = (raw: (string | number)[]) => (raw ?? []).map(Number).filter(n => Number.isFinite(n) && n > 0);

const proTableRef = ref<ProTableInstance>();
const initParam = reactive({ orderBy: "", order: "" });

const onSortChange = ({ prop, order }: { prop: string; order: string | null }) => {
  if (!order) {
    initParam.orderBy = "";
    initParam.order = "";
    return;
  }
  initParam.orderBy = prop === "groupName" ? "id" : prop;
  initParam.order = order === "ascending" ? "asc" : "desc";
};

// 列清单严格照 :80：ID | 设备IP | 设备名称 | 设备地址 | 设备噪声值 | 操作，无搜索区。
// 「发送端口」「声场分区」两列已按要求去掉，两者仍可在修改弹窗与声场分区页里看到。
const columns = reactive<ColumnProps<SoundDevice>[]>([
  { type: "selection", fixed: "left", width: 50 },
  { prop: "id", label: "ID", width: 90 },
  { prop: "ip", label: "设备IP", width: 180 },
  { prop: "name", label: "设备名称", minWidth: 220 },
  { prop: "devaddr", label: "设备地址", width: 130 },
  { prop: "dbvalue", label: "设备噪声值", width: 140 },
  { prop: "operation", label: "操作", fixed: "right", width: 140 }
]);

const refresh = () => proTableRef.value?.getTableList();

const form = reactive({ name: "", ip: "", devaddr: 0, sendport: 0 });
const dlg = reactive({ visible: false, saving: false, isEdit: false, title: "", id: 0 });

const openCreate = () => {
  Object.assign(form, { name: "", ip: "", devaddr: 0, sendport: 0 });
  Object.assign(dlg, { visible: true, saving: false, isEdit: false, title: "添加设备", id: 0 });
};

const openEdit = async (row: SoundDevice) => {
  const { data } = await getSoundDeviceApi(row.id);
  Object.assign(form, { name: data.name, ip: data.ip, devaddr: data.devaddr, sendport: data.sendport });
  Object.assign(dlg, { visible: true, saving: false, isEdit: true, title: `修改噪声设备：${data.name}`, id: data.id });
};

const submit = async () => {
  if (!form.name.trim()) return ElMessage.warning("请输入设备名称");
  if (!form.ip.trim()) return ElMessage.warning("请输入 IP 地址");
  dlg.saving = true;
  try {
    if (dlg.isEdit) await updateSoundDeviceApi(dlg.id, { ...form });
    else await createSoundDeviceApi({ ...form });
    ElMessage.success("保存成功");
    dlg.visible = false;
    refresh();
  } finally {
    dlg.saving = false;
  }
};

const doDelete = async (raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning("请先勾选噪声设备");
  await ElMessageBox.confirm(`确认删除选中的 ${ids.length} 台噪声设备？`, "删除噪声设备", {
    type: "warning",
    confirmButtonText: "确认删除"
  });
  const { data } = await deleteSoundDevicesApi(ids);
  ElMessage.success(`已删除 ${data.deleted} 台`);
  refresh();
};
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
.tip {
  margin-left: 8px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
  &.block {
    display: block;
    margin-left: 0;
    line-height: 1.6;
  }
}
</style>
