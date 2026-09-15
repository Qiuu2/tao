<!--
  启用管理（旧版 displayenablemanager.php + enableadd.php / enablemodify.php）

  一条记录 = 「到了某年某月某日某时某分某秒，把这一批任务按各自的安排启用或停用」。
  后台扫 enabletask 表，到点执行。

  ⚠ enstate 与 taskid 是**两串并列的逗号分隔值**，逐项对应，
     取值 0 = 启用、1 = 停用（和 task.projectstate 一致，
     和 holidaytime.projectstate（1=启用）相反）。
     旧版「提交」按钮 tijiaoselects() 拼的就是这两串，详见 server/internal/enable。

  弹窗照旧版 addmanager.html：一张**全部任务**的表，每行一个「启用 / 停用」单选，
  单选的初值取自这条任务当前的 projectstate；底下三个按钮
  「全选启用 / 全选停用 / 提交」。旧版的「启用/停用」总下拉和表单 submit
  在模板里是被注释掉的，这里不做。
-->
<template>
  <div class="table-box">
    <ProTable ref="proTableRef" :columns="columns" :request-api="getEnableListApi" row-key="id">
      <template #tableHeader="scope">
        <div class="header-bar">
          <!-- 按钮照旧版 enableManager_form.html：全选 / 取消 / 添加 / 修改 / 删除
               （全选与取消由 ProTable 的复选框代劳，修改是行内按钮） -->
          <div class="header-left">
            <el-button type="primary" :disabled="!canEdit" @click="openCreate">{{ $t("common.add") }}</el-button>
            <el-button type="danger" :disabled="!canEdit || !scope.isSelected" @click="doDelete(scope.selectedListIds)">
              {{ $t("common.delete") }}
            </el-button>
          </div>
          <div class="header-right">
            <el-tag type="info" size="small" effect="plain">{{ $t("enable.expiredNote") }}</el-tag>
          </div>
        </div>
      </template>

      <template #starttime="s">
        {{ s.row.starttime }}
        <el-tag v-if="s.row.expired" type="info" size="small" effect="plain" class="ml6">{{ $t("enable.expired") }}</el-tag>
      </template>

      <template #tasks="s">
        <template v-if="s.row.tasks?.length">
          <el-tag
            v-for="t in s.row.tasks?.slice(0, 8)"
            :key="t.taskId"
            :type="t.missing ? 'danger' : t.action === 0 ? 'success' : 'warning'"
            size="small"
            effect="plain"
            class="task-tag"
          >
            {{ t.taskName }} · {{ t.actionText }}
          </el-tag>
          <span v-if="s.row.tasks?.length > 8" class="muted">…共 {{ s.row.tasks?.length }} 条</span>
        </template>
        <span v-else class="muted">{{ $t("enable.noTaskBound") }}</span>
      </template>

      <template #operation="s">
        <el-button type="primary" link :icon="EditPen" :disabled="!canEdit" @click="openEdit(s.row)">{{
          $t("common.modify")
        }}</el-button>
        <el-button type="danger" link :icon="Delete" :disabled="!canEdit" @click="doDelete([s.row.id])">{{
          $t("common.delete")
        }}</el-button>
      </template>
    </ProTable>

    <!--
      ⚠ 宽度要装得下里面那张任务表，否则就得横向拖着看。
        固定列加起来 920px（选项 70 + 类型 120 + 开始日期 110 + 播放时间 100 +
        播放时长 110 + 结束日期 110 + 操作 190 + 计入本次 110），
        任务名称那一列最少 220 —— 一共 1140，再加弹窗自己的左右内边距。
        加了那四列排期之后 900px 已经装不下了。

        写成 min(1280px, 96vw)：宽屏上给任务名称留出余量，
        窄屏（笔记本 1366、外接副屏）上退成视口的 96%，不会把弹窗撑出屏幕。
    -->
    <el-dialog v-model="dlg.visible" :title="dlg.title" width="min(1280px, 96vw)" top="6vh">
      <el-form :model="form" label-width="110px">
        <el-row :gutter="18">
          <el-col :span="12">
            <el-form-item :label="$t('common.startDate')" required>
              <el-date-picker
                v-model="form.startdate"
                type="date"
                value-format="YYYY-MM-DD"
                :placeholder="$t('enable.startDateRequired')"
                :clearable="false"
                class="fill"
              />
              <div v-if="err.startdate" class="err">{{ err.startdate }}</div>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item :label="$t('common.startTime')" required>
              <el-time-picker
                v-model="form.starttime"
                value-format="HH:mm:ss"
                :placeholder="$t('enable.pickStartTime')"
                :clearable="false"
                class="fill"
              />
            </el-form-item>
          </el-col>
          <!--
            结束日期 / 结束时间：enabletask 上后加的两列。
            ⚠ 不是必填 —— 老数据里这两列是 NULL，硬要求填会让人连改都改不了。
              但要填就得两个一起填（只填一个后台没法判断什么时候结束），后端也拦这一条。
              所以这两个都带 clearable，清掉一个的时候顺手把另一个也清掉。
          -->
          <el-col :span="12">
            <el-form-item :label="$t('common.endDate')">
              <el-date-picker
                v-model="form.enddate"
                type="date"
                value-format="YYYY-MM-DD"
                :placeholder="$t('enable.endOptional')"
                class="fill"
                @change="onEndChange"
              />
              <div v-if="err.enddate" class="err">{{ err.enddate }}</div>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item :label="$t('common.endTime')">
              <el-time-picker
                v-model="form.endtime"
                value-format="HH:mm:ss"
                :placeholder="$t('enable.endOptional')"
                class="fill"
                @change="onEndChange"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <!--
          ⚠ 表格必须包一层 width:100% 的块，否则会被 el-form-item 的 flex 压扁。
            label-width="0"：外面这个「任务名称」标签去掉了 —— 表里第二列
            本来就写着任务名称，外面再挂一个是重复的，还白占一段左边距。
        -->
        <el-form-item label-width="0" required>
          <div class="pick-wrap">
            <div class="pick-bar">
              <el-input
                v-model="pickKeyword"
                :placeholder="$t('term.searchTaskName')"
                clearable
                size="small"
                style="width: 240px"
                @input="() => loadTasks()"
              />
              <div class="grow"></div>
              <el-button size="small" type="primary" :disabled="!rows.length" @click="setAll(0)">{{
                $t("enable.enableAll")
              }}</el-button>
              <el-button size="small" type="warning" :disabled="!rows.length" @click="setAll(1)">{{
                $t("enable.disableAll")
              }}</el-button>
            </div>

            <el-table
              :data="rows"
              size="small"
              max-height="360"
              class="mt8"
              v-loading="taskLoading"
              :empty-text="$t('enable.noSelectableTask')"
            >
              <el-table-column type="index" :label="$t('enable.options')" width="70" />
              <el-table-column prop="taskName" :label="$t('taskCommon.taskName')" min-width="220" show-overflow-tooltip />
              <el-table-column prop="typeText" :label="$t('typed.taskType')" width="120" />
              <!--
                这四列是任务本身的排期，只读，给人判断「到点启用这条任务有没有意义」用：
                没有这些，一屏几十条任务只看名字根本分不出该挑哪条。
              -->
              <el-table-column prop="startdate" :label="$t('common.startDate')" width="110" />
              <el-table-column prop="playtime" :label="$t('taskCommon.playTime')" width="100" />
              <el-table-column prop="timelengthText" :label="$t('taskCommon.playLength')" width="110" />
              <el-table-column prop="enddate" :label="$t('common.endDate')" width="110" />
              <el-table-column :label="$t('common.operation')" width="190">
                <template #default="{ row }">
                  <el-radio-group v-model="row.action" size="small">
                    <el-radio-button :value="0">{{ $t("common.enable") }}</el-radio-button>
                    <el-radio-button :value="1">{{ $t("common.disable") }}</el-radio-button>
                  </el-radio-group>
                </template>
              </el-table-column>
              <!--
                「计入本次」：勾上的才会写进这条计划。
                表头那个复选框是全选/全不选 —— 一屏几十条任务，一条条点太费事。
                半选状态（只勾了一部分）用 indeterminate 画出来。
              -->
              <el-table-column width="110">
                <template #header>
                  <el-checkbox
                    :model-value="allPicked"
                    :indeterminate="somePicked"
                    :disabled="!rows.length"
                    @change="(v: any) => pickAll(!!v)"
                  >
                    {{ $t("enable.includeThisTime") }}
                  </el-checkbox>
                </template>
                <template #default="{ row }">
                  <el-checkbox v-model="row.picked" />
                </template>
              </el-table-column>
            </el-table>
            <div class="tip">{{ $t("enable.includeTip") }}</div>
            <div v-if="err.tasks" class="err">{{ err.tasks }}</div>
          </div>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dlg.visible = false">{{ $t("common.cancel") }}</el-button>
        <el-button type="primary" :loading="dlg.saving" @click="submit">{{ $t("common.submit") }}</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="tsx" name="enablePlan">
import { useI18n } from "vue-i18n";
import { Delete, EditPen } from "@element-plus/icons-vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { computed, onMounted, reactive, ref } from "vue";

import {
  createEnableApi,
  deleteEnableApi,
  getEnableApi,
  getEnableListApi,
  getEnableTasksApi,
  updateEnableApi
} from "@/api/modules/ninemod";
import type { EnablePlan } from "@/api/modules/ninemod";
import ProTable from "@/components/ProTable/index.vue";
import { useAuthStore } from "@/stores/modules/auth";
import type { ColumnProps, ProTableInstance } from "@/components/ProTable/interface";

// 脚本里拼的文案用 t()；模板里的 $t 不用引入
const { t } = useI18n();

const authStore = useAuthStore();
// 启用管理与文字语音同一个权限位（ttspriv），后端把它单独给成 enable.edit
const canEdit = computed(() => !!(authStore.authButtonListGet as any)?.enable?.edit);
const toIds = (raw: (string | number)[]) => (raw ?? []).map(Number).filter(n => Number.isFinite(n) && n > 0);

const proTableRef = ref<ProTableInstance>();

// 列清单照旧版 enableManager_form.html：任务名称 | 起始日期 | 播放时间。
// （模板里还有一列「状态」，但整块是被注释掉的，所以不列。）
const columns = reactive<ColumnProps<EnablePlan>[]>([
  { type: "selection", fixed: "left", width: 50 },
  { prop: "tasks", label: t("taskCommon.taskName"), minWidth: 380 },
  // 表头与表单里的叫法要一致：表单上写的是「开始日期」，列表原来写「起始日期」
  { prop: "startdate", label: t("common.startDate"), width: 120 },
  { prop: "starttime", label: t("common.startTime"), width: 110 },
  { prop: "enddate", label: t("common.endDate"), width: 120 },
  { prop: "endtime", label: t("common.endTime"), width: 110 },
  { prop: "operation", label: t("common.operation"), fixed: "right", width: 140 }
]);

const refresh = () => proTableRef.value?.getTableList();

/* ---------------- 弹窗里的任务表 ---------------- */

/** 弹窗里那张表的一行：一条任务 + 它到点是启用还是停用 + 这次要不要写进去 */
interface Row {
  taskId: number;
  taskName: string;
  typeText: string;
  /** 0 = 启用、1 = 停用（与 enabletask.enstate 同一套取值） */
  action: number;
  picked: boolean;
}

const rows = ref<Row[]>([]);
const taskLoading = ref(false);
const pickKeyword = ref("");
const form = reactive({ startdate: "", starttime: "08:00:00", enddate: "", endtime: "" });
const dlg = reactive({ visible: false, saving: false, isEdit: false, title: "", id: 0 });
const err = reactive({ startdate: "", enddate: "", tasks: "" });
const clearErr = () => Object.keys(err).forEach(k => ((err as any)[k] = ""));

/**
 * 拉全部候选任务铺成表（旧版就是整张表一次列出来的）。
 * chosen 是回填用的：编辑时把已存计划里那几条的状态与勾选恢复回去。
 */
const loadTasks = async (chosen?: Map<number, number>) => {
  taskLoading.value = true;
  try {
    const { data } = await getEnableTasksApi(pickKeyword.value);
    rows.value = (data ?? []).map(t => ({
      taskId: t.taskId,
      taskName: t.taskName,
      typeText: t.typeText,
      // 任务本身的排期，表上只读显示
      startdate: t.startdate,
      enddate: t.enddate,
      playtime: t.playtime,
      timelengthText: t.timelengthText,
      // 单选初值取这条任务当前的 projectstate（旧版 addmanager.html 就是这么设的）
      action: chosen?.has(t.taskId) ? (chosen.get(t.taskId) as number) : t.projectstate,
      picked: chosen?.has(t.taskId) ?? false
    }));
  } finally {
    taskLoading.value = false;
  }
};

const setAll = (v: number) =>
  rows.value.forEach(r => {
    r.action = v;
    r.picked = true;
  });

/** 表头那个全选复选框：全勾上了才算选中，勾了一部分显示成半选 */
const allPicked = computed(() => rows.value.length > 0 && rows.value.every(r => r.picked));
const somePicked = computed(() => rows.value.some(r => r.picked) && !allPicked.value);
const pickAll = (v: boolean) => rows.value.forEach(r => (r.picked = v));

const openCreate = async () => {
  clearErr();
  Object.assign(form, {
    startdate: new Date().toISOString().slice(0, 10),
    starttime: "08:00:00",
    // 结束这一对默认不填 —— 与老数据一致，人要用再填
    enddate: "",
    endtime: ""
  });
  pickKeyword.value = "";
  Object.assign(dlg, { visible: true, saving: false, isEdit: false, title: t("enable.addPlan"), id: 0 });
  await loadTasks();
};

const openEdit = async (row: EnablePlan) => {
  clearErr();
  const { data } = await getEnableApi(row.id);
  Object.assign(form, {
    startdate: data.startdate,
    starttime: data.starttime,
    enddate: data.enddate || "",
    endtime: data.endtime || ""
  });
  pickKeyword.value = "";
  // 已删除的任务不回填，否则保存时会被服务端的存在性校验挡下来
  const chosen = new Map<number, number>();
  (data.tasks ?? []).filter(t => !t.missing).forEach(t => chosen.set(t.taskId, t.action));
  const dropped = (data.tasks ?? []).filter(t => t.missing).length;
  Object.assign(dlg, { visible: true, saving: false, isEdit: true, title: t("enable.editPlan", { id: data.id }), id: data.id });
  await loadTasks(chosen);
  if (dropped) ElMessage.warning(t("enable.droppedTasks", { n: dropped }));
};

/**
 * 结束这一对要么都不填、要么一起填。
 *
 * 清掉其中一个就顺手把另一个也清掉 —— 不然人只清了日期，时间还留着，
 * 提交时被后端拦下来，还得回头猜是哪一栏的事。
 */
const onEndChange = () => {
  if (!form.enddate) form.endtime = "";
  else if (!form.endtime) form.endtime = "23:59:59";
};

const submit = async () => {
  clearErr();
  let bad = false;
  if (!form.startdate) {
    err.startdate = t("enable.startDateRequired");
    bad = true;
  }
  // 与后端同一条判据：填了结束就必须晚于开始
  if (form.enddate && form.endtime) {
    if (`${form.enddate} ${form.endtime}` <= `${form.startdate} ${form.starttime}`) {
      err.enddate = t("enable.endAfterStart");
      bad = true;
    }
  } else if (form.enddate || form.endtime) {
    err.enddate = t("enable.endBothOrNeither");
    bad = true;
  }
  const picked = rows.value.filter(r => r.picked);
  if (!picked.length) {
    err.tasks = t("enable.pickTaskFirst");
    bad = true;
  }
  if (bad) return ElMessage.warning(t("enable.requiredNotFilled"));

  const body = {
    startdate: form.startdate,
    starttime: form.starttime,
    enddate: form.enddate,
    endtime: form.endtime,
    tasks: picked.map(r => ({ taskId: r.taskId, action: r.action }))
  };
  dlg.saving = true;
  try {
    if (dlg.isEdit) await updateEnableApi(dlg.id, body);
    else await createEnableApi(body);
    ElMessage.success(t("common.saveSuccess"));
    dlg.visible = false;
    refresh();
  } finally {
    dlg.saving = false;
  }
};

const doDelete = async (raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning(t("enable.pickPlanFirst"));
  await ElMessageBox.confirm(t("enable.confirmDeleteN", { n: ids.length }), t("enable.deletePlan"), {
    type: "warning",
    confirmButtonText: t("common.confirmDelete")
  });
  const { data } = await deleteEnableApi(ids);
  ElMessage.success(t("enable.deletedN", { n: data.deleted }));
  refresh();
};

onMounted(() => loadTasks());
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
.task-tag {
  margin: 2px 6px 2px 0;
}
.tip {
  margin-top: 4px;
  font-size: 12px;
  line-height: 1.7;
  color: var(--el-text-color-secondary);
}
.err {
  margin-top: 2px;
  font-size: 12px;
  line-height: 1.5;
  color: var(--el-color-danger);
}
.fill {
  width: 100%;
}
.ml6 {
  margin-left: 6px;
}
.mt8 {
  margin-top: 8px;
}
.pick-wrap {
  width: 100%;
}
.pick-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  width: 100%;
}
.grow {
  flex: 1;
}
</style>
