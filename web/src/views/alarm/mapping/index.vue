<!--
  报警映射：报警主机的某个通道 → 报警分区 → 报警时播放的媒体

  相对旧版的关键修复：
    · 排序参数原来是 ORDER BY <原样拼进来的 GET 参数>，是可直接利用的注入点。
    · 四表内连接改 LEFT JOIN。媒体/分区/终端任一被删，旧版这行就从列表里消失，
      但记录仍在库里继续生效 —— 管理员看不到、后台却照样触发。现在必须显示并标红。
    · 修改时同样校验「该主机该通道是否已被占用」。旧版只在新增时校验，
      修改直接 UPDATE，可以造出同主机同通道的两条映射。
    · 媒体、分区、主机的存在性与类型全部服务端校验。
-->
<template>
  <div class="table-box">
    <ProTable
      ref="proTableRef"
      :columns="columns"
      :request-api="getAlarmMappingListApi"
      :init-param="initParam"
      :data-callback="dataCallback"
      row-key="id"
      @sort-change="onSortChange"
    >
      <template #tableHeader="scope">
        <div class="header-bar">
          <div class="header-left">
            <!-- :80 这一页顶部只有「添加」，取消映射放在行内操作里 -->
            <el-button type="primary" :disabled="!canEdit" @click="openCreate">{{ $t("common.add") }}</el-button>
            <el-button type="danger" :disabled="!canEdit || !scope.isSelected" @click="confirmDelete(scope.selectedListIds)">
              {{ $t("common.delete") }}
            </el-button>
          </div>
          <div class="header-right">
            <el-tag v-if="invalidCount" type="danger" size="small" effect="plain"> {{ invalidCount }} 条异常配置 </el-tag>
            <el-tag v-if="scopeNote" type="info" size="small" effect="plain">{{ scopeNote }}</el-tag>
          </div>
        </div>
      </template>

      <template #alarmTerminalName="scope">
        <span :class="{ bad: scope.row.terminalDeleted }">{{ scope.row.alarmTerminalName }}</span>
      </template>

      <template #alarmChannel="scope">
        <el-tag :type="scope.row.channelOutOfRange ? 'danger' : 'info'" size="small" effect="plain">
          {{ $t("alarmMap.channelNo", { n: scope.row.alarmChannel }) }}
        </el-tag>
        <span v-if="scope.row.terminalChannels" class="muted"> / {{ scope.row.terminalChannels }}</span>
      </template>

      <template #alarmAreaName="scope">
        <span :class="{ bad: scope.row.areaDeleted }">{{ scope.row.alarmAreaName }}</span>
      </template>

      <!-- 「状态」列已按严格对齐去掉。但引用了已删除对象的脏映射后台照样会用（BR-190），
           不能不显示 —— 异常标记就地并到媒体名称后面。 -->
      <template #mediaName="scope">
        <span :class="{ bad: scope.row.mediaDeleted }">{{ scope.row.mediaName }}</span>
        <el-tooltip v-if="scope.row.invalid" :content="scope.row.invalidReason" placement="top">
          <el-tag type="danger" size="small" class="ml6">{{ $t("alarmMap.abnormal") }}</el-tag>
        </el-tooltip>
      </template>

      <template #areaTerminalCount="scope">
        <el-tag size="small" effect="plain">{{ $t("common.nTerminals", { n: scope.row.areaTerminalCount }) }}</el-tag>
      </template>

      <template #operation="scope">
        <el-button type="primary" link :icon="EditPen" :disabled="!canEdit" @click="openEdit(scope.row)">{{ $t("common.modify") }}</el-button>
        <el-button type="danger" link :icon="Delete" :disabled="!canEdit" @click="confirmDelete([scope.row.id])">
          {{ $t("common.cancel") }}
        </el-button>
      </template>
    </ProTable>

    <!-- 设置 / 修改映射 -->
    <el-dialog v-model="dlg.visible" :title="dlg.title" width="620px">
      <el-alert v-if="!hosts.length" type="warning" :closable="false" show-icon class="mb12">
        {{ $t("alarmMap.noAlarmHost") }}
      </el-alert>
      <el-alert v-if="mediaNote" type="warning" :closable="false" show-icon class="mb12">{{ mediaNote }}</el-alert>

      <el-form :model="dlg.form" label-width="110px">
        <el-form-item :label='$t("alarmMap.alarmHost")' required>
          <!-- 报警主机也是终端（typeid=7），一样按终端分区排成树 -->
          <TerminalTreeSelect
            v-model="dlg.form.alarmTerminalId"
            :terminals="hostNodes"
            :placeholder='$t("alarmMap.pickAlarmHost")'
            :clearable="false"
            @update:model-value="onHostChange"
          />
        </el-form-item>

        <!-- 顺序照 :80 的「添加」弹窗：报警主机 / 映射名称 / 通道 / 报警分区 / 媒体文件 -->
        <el-form-item :label='$t("alarmMap.mapName")'>
          <el-input v-model="dlg.form.info" maxlength="15" show-word-limit :placeholder='$t("alarmMap.mapNameRequired")' />
        </el-form-item>

        <el-form-item :label='$t("alarmMap.channel")' required>
          <el-select v-model="dlg.form.alarmChannel" class="fill" :disabled="!channelCount">
            <el-option v-for="c in channelCount" :key="c" :label='$t("alarmMap.channelN", { n: c })' :value="c" />
          </el-select>
        </el-form-item>

        <el-form-item :label='$t("terminalCommon.alarmZone")' required>
          <el-select v-model="dlg.form.alarmAreaId" class="fill">
            <el-option v-for="a in areas" :key="a.id" :label="a.name" :value="a.id" />
          </el-select>
        </el-form-item>

        <el-form-item :label='$t("taskCommon.mediaFile")' required>
          <el-select v-model="dlg.form.mediaId" filterable class="fill">
            <el-option v-for="m in media" :key="m.id" :label="m.name" :value="m.id">
              <span>{{ m.name }}</span>
              <span class="opt-sub">{{ m.folderName }} · {{ fmtDuration(m.timelength) }}</span>
            </el-option>
          </el-select>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dlg.visible = false">{{ $t("common.cancel") }}</el-button>
        <el-button type="primary" :loading="dlg.saving" @click="submit">{{ $t("common.confirm") }}</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="tsx" name="alarmMapping">
import { useI18n } from "vue-i18n";
import { Delete, EditPen } from "@element-plus/icons-vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { computed, onMounted, reactive, ref } from "vue";

import {
  createAlarmMappingApi,
  deleteAlarmMappingsApi,
  getAlarmAreaOptionsApi,
  getAlarmHostsApi,
  getAlarmMappingListApi,
  getAlarmMediaApi,
  updateAlarmMappingApi
} from "@/api/modules/alarm";
import type { AlarmAreaOption, AlarmHostOption, AlarmMapping, AlarmMediaOption } from "@/api/modules/alarm";
import ProTable from "@/components/ProTable/index.vue";
import TerminalTreeSelect from "@/components/TerminalTree/Select.vue";
import { useAuthStore } from "@/stores/modules/auth";
import type { ColumnProps, ProTableInstance } from "@/components/ProTable/interface";

// 脚本里拼的文案用 t()；模板里的 $t 不用引入
const { t } = useI18n();

const authStore = useAuthStore();
const btn = computed(() => (authStore.authButtonListGet as any)?.alarm ?? {});
const canEdit = computed(() => !!btn.value.mapping);

const toIds = (raw: (string | number)[]) => (raw ?? []).map(Number).filter(n => Number.isFinite(n) && n > 0);

const proTableRef = ref<ProTableInstance>();
const scopeNote = ref("");
const invalidCount = ref(0);
const initParam = reactive({ orderBy: "", order: "" });

const onSortChange = ({ prop, order }: { prop: string; order: string | null }) => {
  if (!order) {
    initParam.orderBy = "";
    initParam.order = "";
    return;
  }
  // 列名 → 后端白名单键
  const map: Record<string, string> = {
    alarmChannel: "alarmchannel",
    alarmTerminalName: "terminalname",
    alarmAreaName: "alarmname",
    mediaName: "medianame"
  };
  initParam.orderBy = map[prop] ?? prop;
  initParam.order = order === "ascending" ? "asc" : "desc";
};

// 列清单严格照 :80（页面规格.txt「报警映射」）：
// 报警主机名称 | 报警映射名称 | 报警分区名称 | 媒体名称 | 通道 | 分区终端 | 操作，无搜索区。
//
// 「报警映射名称」对应我们的 info 列（旧库里这一列就是映射的备注名）。
// 「分区终端」是该报警分区下的终端数，列表接口已经带出来了。
// 「状态」列 :80 没有 —— 但媒体被删、分区被删这类脏数据必须看得见，
// 所以那个标记挪到「媒体名称」列里就地显示，不单占一列。
const columns = reactive<ColumnProps<AlarmMapping>[]>([
  { type: "selection", fixed: "left", width: 50 },
  {
    prop: "alarmTerminalName",
    label: t("alarmMap.alarmHost"),
    minWidth: 160,
    search: { el: "input", key: "keyword", props: { placeholder: t("alarmMap.searchHint") } }
  },
  // 列序照旧版 alarmmanager/alarmmanager_form.html 的表头：
  // 报警主机 | 映射名称 | 映射通道 | 映射分区 | 报警媒体 | 分区终端
  { prop: "info", label: t("alarmMap.mapName"), minWidth: 160, showOverflowTooltip: true },
  { prop: "alarmChannel", label: t("alarmMap.mappedChannel"), width: 110 },
  { prop: "alarmAreaName", label: t("alarmMap.mappedZone"), minWidth: 150 },
  { prop: "mediaName", label: t("alarmMap.alarmMedia"), minWidth: 180 },
  { prop: "areaTerminalCount", label: t("alarmMap.zoneTerminals"), width: 120 },
  { prop: "operation", label: t("common.operation"), fixed: "right", width: 140 }
]);

const dataCallback = (data: any) => {
  scopeNote.value = data.scopeNote ?? "";
  invalidCount.value = (data.list ?? []).filter((r: AlarmMapping) => r.invalid).length;
  return { list: data.list, total: data.total, pageNum: data.pageNum, pageSize: data.pageSize };
};

const refresh = () => proTableRef.value?.getTableList();

/* ---------------- 选择器 ---------------- */

const hosts = ref<AlarmHostOption[]>([]);

/*
  树上要看得见「几路 / IP / 在不在线」。树组件只认 name / groupName / netstate 几个字段，
  所以把这些信息拼进名字里 —— 报警主机就那么几台，名字长一点不碍事。
*/
const hostNodes = computed(() =>
  hosts.value.map(h => ({
    ...h,
    name: t("alarmMap.hostOption", {
      name: h.name || t("common.terminalNo", { id: h.id }),
      ip: h.ip,
      n: h.channels
    })
  }))
);
const areas = ref<AlarmAreaOption[]>([]);
const media = ref<AlarmMediaOption[]>([]);
const mediaNote = ref("");

const loadOptions = async () => {
  const [h, a, m] = await Promise.all([getAlarmHostsApi(), getAlarmAreaOptionsApi(), getAlarmMediaApi()]);
  hosts.value = h.data ?? [];
  areas.value = a.data ?? [];
  media.value = m.data?.list ?? [];
  mediaNote.value = m.data?.note ?? "";
};

const fmtDuration = (sec: number) => {
  if (!sec) return "—";
  return `${Math.floor(sec / 60)}:${String(sec % 60).padStart(2, "0")}`;
};

/* ---------------- 设置 / 修改 ---------------- */

const dlg = reactive({
  visible: false,
  saving: false,
  isEdit: false,
  title: "",
  id: 0,
  form: { info: "", alarmTerminalId: 0, alarmChannel: 0, alarmAreaId: 0, mediaId: 0 }
});

const currentHost = computed(() => hosts.value.find(h => h.id === dlg.form.alarmTerminalId));
const channelCount = computed(() => currentHost.value?.channels ?? 0);

// 换主机后原来的通道号可能超出新主机的范围，直接清掉让用户重选
const onHostChange = () => {
  if (dlg.form.alarmChannel > channelCount.value) dlg.form.alarmChannel = 0;
};

const openCreate = async () => {
  Object.assign(dlg, {
    visible: true,
    saving: false,
    isEdit: false,
    title: t("common.add"),
    id: 0,
    form: { info: "", alarmTerminalId: 0, alarmChannel: 0, alarmAreaId: 0, mediaId: 0 }
  });
  await loadOptions();
};

const openEdit = async (row: AlarmMapping) => {
  await loadOptions();
  Object.assign(dlg, {
    visible: true,
    saving: false,
    isEdit: true,
    title: t("alarmMap.editMapping", { id: row.id }),
    id: row.id,
    form: {
      info: row.info,
      // 引用对象已被删除时置 0，强制用户重新选一个有效的
      alarmTerminalId: row.terminalDeleted ? 0 : row.alarmTerminalId,
      alarmChannel: row.terminalDeleted ? 0 : row.alarmChannel,
      alarmAreaId: row.areaDeleted ? 0 : row.alarmAreaId,
      mediaId: row.mediaDeleted ? 0 : row.mediaId
    }
  });
};

const submit = async () => {
  const f = dlg.form;
  if (!f.alarmTerminalId) return ElMessage.warning(t("alarmMap.alarmHostRequired"));
  if (!f.alarmChannel) return ElMessage.warning(t("alarmMap.channelRequired"));
  if (!f.alarmAreaId) return ElMessage.warning(t("alarmMap.alarmZoneRequired"));
  if (!f.mediaId) return ElMessage.warning(t("alarmMap.mediaRequired"));

  dlg.saving = true;
  try {
    if (dlg.isEdit) {
      await updateAlarmMappingApi(dlg.id, { ...f });
    } else {
      await createAlarmMappingApi({ ...f });
    }
    ElMessage.success(t("common.saveSuccess"));
    dlg.visible = false;
    refresh();
  } finally {
    dlg.saving = false;
  }
};

/* ---------------- 取消映射 ---------------- */

const confirmDelete = async (raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning(t("alarmMap.pickMappingFirst"));
  await ElMessageBox.confirm(t("alarmMap.confirmClear", { n: ids.length }), t("alarmMap.clearMapping"), {
    type: "warning",
    confirmButtonText: t("alarmMap.confirmClearTitle")
  });
  const { data } = await deleteAlarmMappingsApi(ids);
  if (data.skipped?.length) {
    ElMessage.warning(t("alarmMap.clearedNSkipped", { n: data.deleted.length, skipped: data.skipped.length }));
  } else {
    ElMessage.success(t("alarmMap.clearedN", { n: data.deleted.length }));
  }
  refresh();
};

onMounted(loadOptions);
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
.bad {
  color: var(--el-color-danger);
  text-decoration: line-through;
}
.muted {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.opt-sub {
  margin-left: 10px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.form-tip {
  margin-left: 10px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
  &.block {
    display: block;
    margin-left: 0;
    line-height: 1.6;
  }
}
.fill {
  width: 100%;
}
.mb12 {
  margin-bottom: 12px;
}
</style>
