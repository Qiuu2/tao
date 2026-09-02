<!--
  启用管理

  一条记录 = 「到了某年某月某日某时某分某秒，把这一批任务批量启用或停用」。
  后台扫 enabletask 表，到点执行。

  ⚠ enstate：1 = 启用、0 = 停用。
     和 task.projectstate（0=启用）相反，和 holidaytime.projectstate（1=启用）一致。
     三张表两种约定，只能逐表记住。

  表结构上的两个坑（不改表，只在读写时绕开）：
    · taskid 是 varchar(2048)，存的是**逗号分隔的任务 id 列表**。
      超长会被静默截断，而截断处很可能落在某个 id 中间 ——
      后台就会解析出一个别的任务。所以保存前按字节数挡住。
    · id 是 zerofill，查出来是 "0001" 这种补零字符串，一律 CAST 成整数用。
-->
<template>
  <div class="table-box">
    <ProTable ref="proTableRef" :columns="columns" :request-api="getEnableListApi" row-key="id">
      <template #tableHeader="scope">
        <div class="header-bar">
          <!-- 按钮对齐 :80（页面规格.txt「启用管理」）：添加 / 删除 -->
          <div class="header-left">
            <el-button type="primary" :disabled="!canEdit" @click="openCreate">添加</el-button>
            <el-button type="danger" :disabled="!canEdit || !scope.isSelected" @click="doDelete(scope.selectedListIds)">
              删除
            </el-button>
          </div>
          <div class="header-right">
            <el-tag type="info" size="small" effect="plain">已过期的计划不会再执行，但后台不会自动清理</el-tag>
          </div>
        </div>
      </template>

      <template #enstate="s">
        <el-tag :type="s.row.enstate === 1 ? 'success' : 'warning'" size="small">{{ s.row.actionText }}</el-tag>
      </template>

      <template #starttime="s">
        {{ s.row.starttime }}
        <el-tag v-if="s.row.expired" type="info" size="small" effect="plain" class="ml6">已过期</el-tag>
      </template>

      <template #tasks="s">
        <template v-if="s.row.tasks?.length">
          <el-tag
            v-for="t in s.row.tasks?.slice(0, 6)"
            :key="t.taskId"
            :type="t.missing ? 'danger' : 'primary'"
            size="small"
            effect="plain"
            class="task-tag"
          >
            {{ t.taskName }}
          </el-tag>
          <span v-if="s.row.tasks?.length > 6" class="muted">…共 {{ s.row.tasks?.length }} 条</span>
        </template>
        <span v-else class="muted">未绑定任务</span>
      </template>

      <template #operation="s">
        <el-button type="primary" link :icon="EditPen" :disabled="!canEdit" @click="openEdit(s.row)">修改</el-button>
        <el-button type="danger" link :icon="Delete" :disabled="!canEdit" @click="doDelete([s.row.id])">删除</el-button>
      </template>
    </ProTable>

    <el-dialog v-model="dlg.visible" :title="dlg.title" width="900px" top="6vh">
      <el-form :model="form" label-width="110px">
        <el-row :gutter="18">
          <el-col :span="12">
            <!-- 表单项名称照 :80 的「添加启用管理」弹窗：开始日期 / 开始时间 / 选择任务 -->
            <el-form-item label="开始日期" required>
              <el-date-picker v-model="form.startdate" type="date" value-format="YYYY-MM-DD" :clearable="false" class="fill" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="开始时间" required>
              <el-time-picker v-model="form.starttime" value-format="HH:mm:ss" :clearable="false" class="fill" />
            </el-form-item>
          </el-col>
        </el-row>

        <!--
          :80 是一张任务表格，每条单独勾「是否启用」，外加「全选启用 / 全选停用」。
          ⚠ enabletask.enstate 是**整行**一个值、taskid 是一串 id ——
            一条记录装不下两种意图。所以保存时按状态**拆成最多两条记录**
            （启用一条、停用一条，日期时间相同）。这不是绕过表结构，
            而是把「一个时间点要做两件事」如实写成两行，后台本来就是逐行执行的。
        -->
        <!-- ⚠ 表格必须包一层 width:100% 的块，否则会被 el-form-item 的 flex 压扁 -->
        <el-form-item label="选择任务" required>
          <div class="pick-wrap">
          <div class="pick-bar">
            <el-input
              v-model="pickKeyword"
              placeholder="搜索任务名称"
              clearable
              size="small"
              style="width: 220px"
              @input="loadTasks"
            />
            <el-select
              v-model="picking"
              multiple
              filterable
              collapse-tags
              collapse-tags-tooltip
              :loading="taskLoading"
              placeholder="从这里挑任务加进下表"
              size="small"
              style="width: 280px"
              @change="onPick"
            >
              <el-option v-for="t in pickTasks" :key="t.taskId" :label="t.taskName" :value="t.taskId">
                <span>{{ t.taskName }}</span>
                <span class="opt-sub">{{ t.typeText }} · 当前{{ t.stateText }}</span>
              </el-option>
            </el-select>
            <el-button size="small" type="primary" :disabled="!rows.length" @click="setAll(1)">全选启用</el-button>
            <el-button size="small" type="warning" :disabled="!rows.length" @click="setAll(0)">全选停用</el-button>
          </div>

          <el-table :data="rows" size="small" max-height="300" class="mt8" empty-text="还没有选任务">
            <el-table-column prop="taskName" label="任务名称" min-width="200" show-overflow-tooltip />
            <el-table-column prop="typeText" label="任务类型" width="120" />
            <el-table-column label="是否启用" width="180">
              <template #default="{ row }">
                <el-radio-group v-model="row.enstate" size="small">
                  <el-radio-button :value="1">启用</el-radio-button>
                  <el-radio-button :value="0">停用</el-radio-button>
                </el-radio-group>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="80">
              <template #default="{ $index }">
                <el-button type="danger" link :icon="Delete" @click="rows.splice($index, 1)" />
              </template>
            </el-table-column>
          </el-table>

          </div>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dlg.visible = false">取消</el-button>
        <el-button type="primary" :loading="dlg.saving" @click="submit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="tsx" name="enablePlan">
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
import type { EnablePickTask, EnablePlan } from "@/api/modules/ninemod";
import ProTable from "@/components/ProTable/index.vue";
import { useAuthStore } from "@/stores/modules/auth";
import type { ColumnProps, ProTableInstance } from "@/components/ProTable/interface";

const authStore = useAuthStore();
const canEdit = computed(() => !!(authStore.authButtonListGet as any)?.task?.edit);
const toIds = (raw: (string | number)[]) => (raw ?? []).map(Number).filter(n => Number.isFinite(n) && n > 0);

const proTableRef = ref<ProTableInstance>();

// 列清单对齐 :80：任务名称 | 起始日期 | 播放时间 | 操作，无搜索区。
// :80 把「任务名称」放第一列 —— 我们一条计划可以绑多条任务，所以这一列是任务标签的集合。
// 「动作」是我们多的一列：一条计划到点是启用还是停用，不写出来根本看不出来。
const columns = reactive<ColumnProps<EnablePlan>[]>([
  { type: "selection", fixed: "left", width: 50 },
  { prop: "tasks", label: "任务名称", minWidth: 340 },
  { prop: "startdate", label: "起始日期", width: 150 },
  { prop: "starttime", label: "播放时间", width: 130 },
  { prop: "enstate", label: "动作", width: 110 },
  { prop: "operation", label: "操作", fixed: "right", width: 140 }
]);

const refresh = () => proTableRef.value?.getTableList();

/* ---------------- 任务选择 ---------------- */

const pickTasks = ref<EnablePickTask[]>([]);
const taskLoading = ref(false);
const pickKeyword = ref("");
/** 下拉里当前勾着的 id，仅用于「往表里加」，加完就清空 */
const picking = ref<number[]>([]);

const loadTasks = async () => {
  taskLoading.value = true;
  try {
    const { data } = await getEnableTasksApi(pickKeyword.value);
    pickTasks.value = data ?? [];
  } finally {
    taskLoading.value = false;
  }
};

/* ---------------- 新建 / 修改 ---------------- */

/** 弹窗里那张表的一行：一条任务 + 它到点是启用还是停用 */
interface Row {
  taskId: number;
  taskName: string;
  typeText: string;
  enstate: number;
}

const rows = ref<Row[]>([]);
const form = reactive({ startdate: "", starttime: "08:00:00" });
const dlg = reactive({ visible: false, saving: false, isEdit: false, title: "", id: 0 });

/** 从下拉挑中的任务追加到表里，默认「启用」；已在表里的跳过 */
const onPick = (ids: number[]) => {
  for (const id of ids) {
    if (rows.value.some(r => r.taskId === id)) continue;
    const t = pickTasks.value.find(x => x.taskId === id);
    if (!t) continue;
    rows.value.push({ taskId: id, taskName: t.taskName, typeText: t.typeText, enstate: 1 });
  }
  picking.value = [];
};

const setAll = (v: number) => rows.value.forEach(r => (r.enstate = v));

const openCreate = async () => {
  Object.assign(form, { startdate: new Date().toISOString().slice(0, 10), starttime: "08:00:00" });
  rows.value = [];
  picking.value = [];
  pickKeyword.value = "";
  Object.assign(dlg, { visible: true, saving: false, isEdit: false, title: "添加启用管理", id: 0 });
  await loadTasks();
};

const openEdit = async (row: EnablePlan) => {
  // 接口返回的是整个「时间槽」：同一时刻的启用行与停用行合在一起
  const { data } = await getEnableApi(row.id);
  Object.assign(form, { startdate: data.startdate, starttime: data.starttime });
  const mk = (list: typeof data.enable, enstate: number) =>
    (list ?? [])
      // 已删除的任务不回填，否则保存时会被服务端的存在性校验挡下来
      .filter(t => !t.missing)
      .map(t => ({ taskId: t.taskId, taskName: t.taskName, typeText: "", enstate }));
  rows.value = [...mk(data.enable, 1), ...mk(data.disable, 0)];
  const dropped =
    (data.enable ?? []).filter(t => t.missing).length + (data.disable ?? []).filter(t => t.missing).length;
  picking.value = [];
  pickKeyword.value = "";
  Object.assign(dlg, { visible: true, saving: false, isEdit: true, title: `修改启用管理 #${data.id}`, id: data.id });
  await loadTasks();
  // 类型文字在候选列表里才有，回填完再补上
  rows.value.forEach(r => {
    r.typeText = pickTasks.value.find(t => t.taskId === r.taskId)?.typeText ?? "";
  });
  if (dropped) ElMessage.warning(`这个时间点上有 ${dropped} 条任务已被删除，已自动移除`);
};

const submit = async () => {
  if (!form.startdate) return ElMessage.warning("请选择开始日期");
  if (!rows.value.length) return ElMessage.warning("请至少选择一条任务");
  const body = {
    ...form,
    enable: rows.value.filter(r => r.enstate === 1).map(r => r.taskId),
    disable: rows.value.filter(r => r.enstate === 0).map(r => r.taskId)
  };
  dlg.saving = true;
  try {
    const { data } = dlg.isEdit ? await updateEnableApi(dlg.id, body) : await createEnableApi(body);
    // 如实说明落库拆成了几条 —— 界面上一个时间点，库里可能是两行
    const parts: string[] = [];
    if (data.created.length) parts.push(`新增 ${data.created.length} 条`);
    if (data.updated.length) parts.push(`更新 ${data.updated.length} 条`);
    if (data.deleted.length) parts.push(`删除 ${data.deleted.length} 条`);
    ElMessage.success(`保存成功${parts.length ? "（" + parts.join("，") + "）" : ""}`);
    dlg.visible = false;
    refresh();
  } finally {
    dlg.saving = false;
  }
};

const doDelete = async (raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning("请先勾选启用计划");
  await ElMessageBox.confirm(`确认删除选中的 ${ids.length} 条启用计划？`, "删除启用计划", {
    type: "warning",
    confirmButtonText: "确认删除"
  });
  const { data } = await deleteEnableApi(ids);
  ElMessage.success(`已删除 ${data.deleted} 条`);
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
.opt-sub {
  margin-left: 10px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
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
.pick-wrap {
  width: 100%;
}
.pick-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}
.mt8 {
  margin-top: 8px;
}
.mb8 {
  margin-bottom: 8px;
}
.ml6 {
  margin-left: 6px;
}
</style>
