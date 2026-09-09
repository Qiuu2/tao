<!--
  节假日管理

  holidaytime 决定「放假这天打不打铃」：后台在判断一条作息任务今天要不要响时会查它，
  落在启用的节假日区间里就不打。所以这一页看着简单，配错了整栋楼的铃就会在放假那天照响。

  ⚠ projectstate 的取值和 task 表**正好相反**：
      holidaytime.projectstate  1 = 启用, 0 = 停用
      task.projectstate         0 = 启用, 1 = 停用
  依据是旧版 enableholiday() 写 1、disableholiday() 写 0。别凭直觉改。

  相对旧版的修复：
    · 旧版不校验起止日期顺序，结束日填在开始日之前照存，后台按空区间处理，
      等于这条配置静默失效。现在拒绝倒挂的区间。
    · 新增重叠提示：同一天被多条节假日覆盖时给出提醒（不阻断 —— 拆成两段录入也合理）。
    · 列表直接标出「今天生效」，不用自己拿日历对。
-->
<template>
  <div class="table-box">
    <ProTable ref="proTableRef" :columns="columns" :request-api="getHolidayListApi" :init-param="initParam" row-key="id">
      <!-- 按钮文案与顺序对齐 :80（docs/image/oktw/05-节假日管理.png）：
           添加节假日 / 删除 / 批量启用 / 批量禁用，实心彩色。 -->
      <template #tableHeader="scope">
        <div class="header-bar">
          <div class="header-left">
            <el-button type="primary" :disabled="!canEdit" @click="openCreate">{{ $t("holiday.addHoliday") }}</el-button>
            <el-button type="danger" :disabled="!canEdit || !scope.isSelected" @click="doDelete(scope.selectedListIds)">
              {{ $t("common.delete") }}
            </el-button>
            <el-button type="primary" :disabled="!canEdit || !scope.isSelected" @click="setState(scope.selectedListIds, true)">
              {{ $t("holiday.enableSelected") }}
            </el-button>
            <el-button type="warning" :disabled="!canEdit || !scope.isSelected" @click="setState(scope.selectedListIds, false)">
              {{ $t("holiday.disableSelected") }}
            </el-button>
          </div>
          <div class="header-right">
            <el-tag v-if="todayCount" type="danger" size="small" effect="dark">
              今天有 {{ todayCount }} 条节假日生效 · 作息任务不打铃
            </el-tag>
          </div>
        </div>
      </template>

      <template #projectstate="scope">
        <el-tag :type="scope.row.projectstate === HOLIDAY_ENABLED ? 'success' : 'info'" size="small">
          {{ scope.row.stateText }}
        </el-tag>
        <el-tag v-if="scope.row.active" type="danger" size="small" effect="dark" class="ml6">{{ $t("holiday.effectiveToday") }}</el-tag>
      </template>

      <template #operation="scope">
        <el-button type="primary" link :icon="EditPen" :disabled="!canEdit" @click="openEdit(scope.row)"> {{ $t("common.modify") }} </el-button>
        <el-button
          v-if="scope.row.projectstate === HOLIDAY_ENABLED"
          type="warning"
          link
          :disabled="!canEdit"
          @click="setState([scope.row.id], false)"
        >
          {{ $t("common.disable") }}
        </el-button>
        <el-button v-else type="success" link :disabled="!canEdit" @click="setState([scope.row.id], true)"> {{ $t("common.enable") }} </el-button>
        <el-button type="danger" link :icon="Delete" :disabled="!canEdit" @click="doDelete([scope.row.id])"> {{ $t("common.delete") }} </el-button>
      </template>
    </ProTable>

    <!-- 新建 / 修改 -->
    <el-dialog v-model="dlg.visible" :title="dlg.title" width="620px">
      <!-- 表单项与占位符照 :80 的「添加节假日」弹窗 -->
      <el-form :model="dlg.form" label-width="120px">
        <el-form-item :label='$t("holiday.name")' required>
          <el-input v-model="dlg.form.name" maxlength="10" show-word-limit :placeholder='$t("holiday.nameRequired")' />
        </el-form-item>
        <el-form-item :label='$t("holiday.startLabel")' required>
          <el-date-picker
            v-model="dlg.range[0]"
            type="date"
            value-format="YYYY-MM-DD"
            :placeholder='$t("holiday.startRequired")'
            :clearable="false"
            class="fill"
            @change="checkOverlaps"
          />
        </el-form-item>
        <el-form-item :label='$t("holiday.endLabel")' required>
          <el-date-picker
            v-model="dlg.range[1]"
            type="date"
            value-format="YYYY-MM-DD"
            :placeholder='$t("holiday.endRequired")'
            :clearable="false"
            class="fill"
            @change="checkOverlaps"
          />
        </el-form-item>
        <el-form-item :label='$t("common.status")'>
          <!-- ⚠ holidaytime 的取值与 task 相反：1 = 启用、0 = 停用 -->
          <el-radio-group v-model="dlg.form.projectstate">
            <el-radio :value="HOLIDAY_ENABLED">{{ $t("common.enable") }}</el-radio>
            <el-radio :value="HOLIDAY_DISABLED">{{ $t("common.disable") }}</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>

      <el-alert v-if="overlaps.length" type="warning" :closable="false" show-icon class="mt4">
        <template #title>{{ $t("holiday.overlap") }}</template>
      </el-alert>

      <template #footer>
        <el-button @click="dlg.visible = false">{{ $t("common.cancel") }}</el-button>
        <el-button type="primary" :loading="dlg.saving" @click="submit">{{ $t("common.confirm") }}</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="tsx" name="holiday">
import { useI18n } from "vue-i18n";
import { Delete, EditPen } from "@element-plus/icons-vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { computed, reactive, ref } from "vue";

import {
  createHolidayApi,
  deleteHolidaysApi,
  getHolidayApi,
  getHolidayListApi,
  getHolidayOverlapsApi,
  HOLIDAY_DISABLED,
  HOLIDAY_ENABLED,
  setHolidayStateApi,
  updateHolidayApi
} from "@/api/modules/basecfg";
import type { Holiday } from "@/api/modules/basecfg";
import ProTable from "@/components/ProTable/index.vue";
import { useAuthStore } from "@/stores/modules/auth";
import type { ColumnProps, ProTableInstance } from "@/components/ProTable/interface";

// 脚本里拼的文案用 t()；模板里的 $t 不用引入
const { t } = useI18n();

const authStore = useAuthStore();
const canEdit = computed(() => !!(authStore.authButtonListGet as any)?.holiday?.edit);

const toIds = (raw: (string | number)[]) => (raw ?? []).map(Number).filter(n => Number.isFinite(n) && n > 0);

const proTableRef = ref<ProTableInstance>();
// 列表默认按开始日期倒序（后端 defaultOrder 就是这个），
// 对齐 :80 后这一页没有可排序列，initParam 只是把默认值传下去。
const initParam = reactive({ orderBy: "", order: "" });

// 「今天有几条生效」直接从当前页的数据里数。翻页会变，但它只是个提示，
// 真要精确统计得再开一个接口，不值得。
const todayCount = computed(() => (proTableRef.value?.tableData ?? []).filter((r: any) => r.active).length);

// 列清单对齐 :80（docs/image/oktw/05-节假日管理.png）：
// 节假日名称 / 开始时间 / 结束时间 / 状态 / 操作，且**没有搜索区**。
// 后端的按名搜索与按状态筛选接口保留着，将来要加回来只需补 search 配置。
const columns = reactive<ColumnProps<Holiday>[]>([
  { type: "selection", fixed: "left", width: 50 },
  { prop: "name", label: t("holiday.name"), minWidth: 220 },
  { prop: "startdate", label: t("common.startTime"), minWidth: 160 },
  { prop: "enddate", label: t("common.endTime"), minWidth: 160 },
  { prop: "projectstate", label: t("common.status"), width: 150 },
  { prop: "operation", label: t("common.operation"), fixed: "right", width: 190 }
]);

const refresh = () => proTableRef.value?.getTableList();

/* ---------------- 新建 / 修改 ---------------- */

const dlg = reactive({
  visible: false,
  saving: false,
  isEdit: false,
  title: "",
  id: 0,
  form: { name: "", projectstate: HOLIDAY_ENABLED },
  // 两个独立的日期选择器共用这个数组：[0] 开始、[1] 结束
  range: ["", ""] as string[]
});

const overlaps = ref<Holiday[]>([]);

const checkOverlaps = async () => {
  overlaps.value = [];
  if (!dlg.range?.[0] || !dlg.range?.[1]) return;
  const { data } = await getHolidayOverlapsApi(dlg.range[0], dlg.range[1], dlg.id);
  overlaps.value = data ?? [];
};

const openCreate = () => {
  Object.assign(dlg, {
    visible: true,
    saving: false,
    isEdit: false,
    title: t("holiday.addHoliday"),
    id: 0,
    form: { name: "", projectstate: HOLIDAY_ENABLED },
    range: ["", ""]
  });
  overlaps.value = [];
};

const openEdit = async (row: Holiday) => {
  const { data } = await getHolidayApi(row.id);
  Object.assign(dlg, {
    visible: true,
    saving: false,
    isEdit: true,
    title: t("holiday.editHoliday", { name: data.name }),
    id: data.id,
    form: { name: data.name, projectstate: data.projectstate },
    range: [data.startdate, data.enddate]
  });
  await checkOverlaps();
};

const submit = async () => {
  if (!dlg.form.name.trim()) return ElMessage.warning(t("holiday.nameRequired"));
  if (!dlg.range?.[0]) return ElMessage.warning(t("holiday.startRequired"));
  if (!dlg.range?.[1]) return ElMessage.warning(t("holiday.endRequired"));
  const payload = {
    name: dlg.form.name.trim(),
    startdate: dlg.range[0],
    enddate: dlg.range[1],
    projectstate: dlg.form.projectstate
  };
  dlg.saving = true;
  try {
    if (dlg.isEdit) {
      await updateHolidayApi(dlg.id, payload);
    } else {
      await createHolidayApi(payload);
    }
    ElMessage.success(t("common.saveSuccess"));
    dlg.visible = false;
    refresh();
  } finally {
    dlg.saving = false;
  }
};

/* ---------------- 启停 / 删除 ---------------- */

const setState = async (raw: (string | number)[], enable: boolean) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning(t("holiday.pickFirst"));
  const { data } = await setHolidayStateApi(ids, enable);
  ElMessage.success(t(enable ? "holiday.enabledN" : "holiday.disabledN", { n: data.affected }));
  refresh();
};

const doDelete = async (raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning(t("holiday.pickFirst"));
  await ElMessageBox.confirm(t("holiday.confirmDeleteN", { n: ids.length }), t("holiday.deleteHoliday"), {
    type: "warning",
    confirmButtonText: t("common.confirmDelete")
  });
  const { data } = await deleteHolidaysApi(ids);
  ElMessage.success(t("holiday.deletedN", { n: data.deleted }));
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
.form-tip {
  font-size: 12px;
  color: var(--el-text-color-secondary);
  &.block {
    display: block;
    line-height: 1.6;
  }
}
.alert-body {
  font-size: 13px;
  line-height: 1.7;
}
.fill {
  width: 100%;
}
.ml6 {
  margin-left: 6px;
}
.mt4 {
  margin-top: 4px;
}
</style>
