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
      <!-- 按钮照旧版 sounddevice_form.html：全选 / 取消 / 添加设备 / 修改设备 / 删除设备
           （全选与取消由 ProTable 的复选框代劳） -->
      <template #tableHeader="scope">
        <div class="header-bar">
          <div class="header-left">
            <el-button type="primary" :disabled="!canEdit" @click="openCreate">{{ $t("noise.addDevice") }}</el-button>
            <el-button :disabled="!canEdit || scope.selectedListIds.length !== 1" @click="openEditById(scope.selectedListIds)">
              {{ $t("noise.editDevice") }}
            </el-button>
            <el-button type="danger" :disabled="!canEdit || !scope.isSelected" @click="doDelete(scope.selectedListIds)">
              {{ $t("noise.deleteDevice") }}
            </el-button>
          </div>
          <div class="header-right">
            <el-tag type="info" size="small" effect="plain">{{ $t("noise.readOnlyNote") }}</el-tag>
          </div>
        </div>
      </template>

      <template #dbvalue="s">
        <el-tag v-if="s.row.dbvalue > 0" size="small" effect="plain">{{ s.row.dbvalue }} dB</el-tag>
        <span v-else class="muted">{{ $t("noise.notSampled") }}</span>
      </template>

      <template #operation="s">
        <el-button type="primary" link :icon="EditPen" :disabled="!canEdit" @click="openEdit(s.row)">{{ $t("common.modify") }}</el-button>
        <el-button type="danger" link :icon="Delete" :disabled="!canEdit" @click="doDelete([s.row.id])">{{ $t("common.delete") }}</el-button>
      </template>
    </ProTable>

    <el-dialog v-model="dlg.visible" :title="dlg.title" width="560px">
      <!-- 表单项与占位符照 :80 的「添加设备」弹窗 -->
      <el-form :model="form" label-width="120px">
        <el-form-item :label='$t("noise.deviceAddrName")' required>
          <el-input v-model="form.name" maxlength="10" show-word-limit :placeholder='$t("noise.addrNamePlaceholder")' />
        </el-form-item>
        <el-form-item :label='$t("noise.deviceIp")' required>
          <el-input v-model="form.ip" :placeholder='$t("noise.ipPlaceholder")' />
        </el-form-item>
        <el-form-item :label='$t("noise.deviceAddr")' required>
          <el-input-number v-model="form.devaddr" :min="0" :max="255" controls-position="right" />
        </el-form-item>
        <el-form-item :label='$t("noise.sendChannel")'>
          <el-input-number v-model="form.sendport" :min="0" :max="65535" controls-position="right" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dlg.visible = false">{{ $t("common.cancel") }}</el-button>
        <el-button type="primary" :loading="dlg.saving" @click="submit">{{ $t("common.submit") }}</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="tsx" name="noiseDevice">
import { useI18n } from "vue-i18n";
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

// 脚本里拼的文案用 t()；模板里的 $t 不用引入
const { t } = useI18n();

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
  // 列清单照旧版 sounddevice_form.html：选项 | 设备ip | 设备名称 | 设备地址 | 设备噪声值
  { type: "selection", fixed: "left", width: 50 },
  { prop: "ip", label: t("noise.deviceIp"), width: 200 },
  { prop: "name", label: t("noise.deviceName"), minWidth: 240 },
  { prop: "devaddr", label: t("noise.deviceAddr"), width: 140 },
  { prop: "dbvalue", label: t("noise.deviceNoise"), width: 150 },
  { prop: "operation", label: t("common.operation"), fixed: "right", width: 140 }
]);

const refresh = () => proTableRef.value?.getTableList();

const form = reactive({ name: "", ip: "", devaddr: 0, sendport: 0 });
const dlg = reactive({ visible: false, saving: false, isEdit: false, title: "", id: 0 });

const openCreate = () => {
  Object.assign(form, { name: "", ip: "", devaddr: 0, sendport: 0 });
  Object.assign(dlg, { visible: true, saving: false, isEdit: false, title: t("noise.addDevice"), id: 0 });
};

const openEdit = async (row: SoundDevice) => {
  const { data } = await getSoundDeviceApi(row.id);
  Object.assign(form, { name: data.name, ip: data.ip, devaddr: data.devaddr, sendport: data.sendport });
  Object.assign(dlg, { visible: true, saving: false, isEdit: true, title: t("noise.editDeviceTitle", { name: data.name }), id: data.id });
};

/** 工具栏上的「修改设备」：旧版是「勾一条再点」，这里保留同一套语义 */
const openEditById = async (raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (ids.length !== 1) return ElMessage.warning(t("noise.pickOneDevice"));
  const { data } = await getSoundDeviceApi(ids[0]);
  Object.assign(form, { name: data.name, ip: data.ip, devaddr: data.devaddr, sendport: data.sendport });
  Object.assign(dlg, { visible: true, saving: false, isEdit: true, title: t("noise.editDeviceTitle", { name: data.name }), id: data.id });
};

const submit = async () => {
  if (!form.name.trim()) return ElMessage.warning(t("noise.deviceNameRequired"));
  if (!form.ip.trim()) return ElMessage.warning(t("noise.ipRequired"));
  dlg.saving = true;
  try {
    if (dlg.isEdit) await updateSoundDeviceApi(dlg.id, { ...form });
    else await createSoundDeviceApi({ ...form });
    ElMessage.success(t("common.saveSuccess"));
    dlg.visible = false;
    refresh();
  } finally {
    dlg.saving = false;
  }
};

const doDelete = async (raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning(t("noise.pickDeviceFirst"));
  await ElMessageBox.confirm(t("noise.confirmDeleteDevices", { n: ids.length }), t("noise.deleteDeviceTitle"), {
    type: "warning",
    confirmButtonText: t("common.confirmDelete")
  });
  const { data } = await deleteSoundDevicesApi(ids);
  ElMessage.success(t("noise.deletedDevicesN", { n: data.deleted }));
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
