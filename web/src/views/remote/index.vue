<!--
  遥控任务（旧版 keytask_mapping）

  一个遥控器按键号绑一个名字和若干条任务，按下就执行。
  存在 shortcutkeytask (keyid, mediaid, keyname)，一个键对应多行、每行一条任务。

  ⚠ 那个叫 mediaid 的列里装的是 taskid。
  旧版写入端取的是 `SELECT taskid, taskname FROM task ...`，POST 变量也叫 task_map_id，
  却塞进了名为 mediaid 的列；而读取端拿它去 join media.id ——
  join 不上就整行消失，所以旧版这个页面配好之后明细永远是空的。
  新版写入沿用旧语义（存 taskid），读取改成 join task 表，明细才对得上。
  列名不能改（数据库红线），所以这段注释就是唯一的路标。

  另外补了旧版没有的校验：键号范围、任务存在性、任务类型必须是可绑的三类。
-->
<template>
  <div class="table-box">
    <ProTable
      ref="proTableRef"
      :columns="columns"
      :request-api="getRemoteListApi"
      row-key="keyId"
    >
      <template #tableHeader="scope">
        <div class="header-bar">
          <!-- 按钮对齐 :80（页面规格.txt「遥控任务」）：添加映射 / 删除映射 -->
          <div class="header-left">
            <el-button type="primary" :disabled="!canEdit" @click="openCreate">添加映射</el-button>
            <el-button type="danger" :disabled="!canEdit || !scope.isSelected" @click="doDelete(scope.selectedListIds)">
              删除映射
            </el-button>
          </div>
          <div class="header-right">
            <el-tag type="info" size="small" effect="plain">一个键号只能配一次</el-tag>
          </div>
        </div>
      </template>

      <template #keyId="scope">
        <el-tag size="small" effect="dark">{{ scope.row.keyId }} 键</el-tag>
      </template>

      <template #tasks="scope">
        <!-- ⚠ 必须用可选链：el-table-column 会拿 row = {} 试跑一次插槽 -->
        <template v-if="scope.row.tasks?.length">
          <el-tag
            v-for="t in scope.row.tasks"
            :key="t.taskId"
            :type="t.missing ? 'danger' : 'primary'"
            size="small"
            effect="plain"
            class="task-tag"
          >
            {{ t.taskName }}
            <span v-if="!t.missing" class="tag-sub">· {{ t.kindText }}</span>
          </el-tag>
        </template>
        <span v-else class="muted">未绑定任务</span>
      </template>

    </ProTable>

    <!-- 新建 / 修改 -->
    <el-dialog v-model="dlg.visible" :title="dlg.title" width="760px" top="6vh">
      <!-- 表单项与顺序照 :80 的「添加映射」弹窗：映射名称 / 映射按键 / 映射任务 -->
      <el-form :model="dlg.form" label-width="100px">
        <el-form-item label="映射名称" required>
          <el-input v-model="dlg.form.keyName" maxlength="10" show-word-limit placeholder="请输入映射名称" />
        </el-form-item>
        <el-form-item label="映射按键" required>
          <el-input-number v-model="dlg.form.keyId" :min="1" :max="999" controls-position="right" style="width: 160px" />
        </el-form-item>
        <el-form-item label="映射任务" required>
          <div class="pick-bar">
            <el-radio-group v-model="pickKind" size="small" @change="loadTasks">
              <el-radio-button label="">全部</el-radio-button>
              <el-radio-button label="file">文件广播</el-radio-button>
              <el-radio-button label="collect">采播</el-radio-button>
              <el-radio-button label="amplifier">终端功放</el-radio-button>
            </el-radio-group>
            <el-input
              v-model="pickKeyword"
              placeholder="任务名称搜索"
              clearable
              size="small"
              style="width: 200px"
              @input="loadTasks"
            />
          </div>
          <el-select
            v-model="selectedTaskIds"
            multiple
            filterable
            collapse-tags
            collapse-tags-tooltip
            :loading="taskLoading"
            placeholder="选择要绑定的任务（最多 20 条）"
            class="fill"
          >
            <el-option v-for="t in pickTasks" :key="t.taskId" :label="t.taskName" :value="t.taskId">
              <span>{{ t.taskName }}</span>
              <span class="opt-sub">{{ t.kindText }}</span>
              <el-tag v-if="t.usedBy && t.usedBy !== dlg.originalKeyId" size="small" type="warning" effect="plain" class="opt-tag">
                已绑在 {{ t.usedBy }} 键
              </el-tag>
            </el-option>
          </el-select>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dlg.visible = false">取消</el-button>
        <el-button type="primary" :loading="dlg.saving" @click="submit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="tsx" name="remote">
import { Delete } from "@element-plus/icons-vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { computed, onMounted, reactive, ref } from "vue";

import { createRemoteApi, deleteRemotesApi, getRemoteListApi, getRemoteTasksApi } from "@/api/modules/basecfg";
import type { RemoteKey, RemotePickTask } from "@/api/modules/basecfg";
import ProTable from "@/components/ProTable/index.vue";
import { useAuthStore } from "@/stores/modules/auth";
import type { ColumnProps, ProTableInstance } from "@/components/ProTable/interface";

const authStore = useAuthStore();
const canEdit = computed(() => !!(authStore.authButtonListGet as any)?.remote?.edit);

const toIds = (raw: (string | number)[]) => (raw ?? []).map(Number).filter(n => Number.isFinite(n) && n > 0);

const proTableRef = ref<ProTableInstance>();

// 列清单严格照 :80：任务名称 | 按键映射 | 映射任务，就这三列，**没有「操作」列**。
//
// ⚠ 后果：这一页没有行内「修改」入口了，改一条映射只能删掉重建 —— :80 就是这样。
// 修改接口（PUT /api/remote-keys/{id}）还在，要把入口加回来只需补一列。
const columns = reactive<ColumnProps<RemoteKey>[]>([
  { type: "selection", fixed: "left", width: 50 },
  { prop: "keyName", label: "任务名称", minWidth: 200 },
  { prop: "keyId", label: "按键映射", width: 130 },
  { prop: "tasks", label: "映射任务", minWidth: 420 }
]);

const refresh = () => proTableRef.value?.getTableList();

/* ---------------- 任务选择 ---------------- */

const pickTasks = ref<RemotePickTask[]>([]);
const taskLoading = ref(false);
const pickKind = ref("");
const pickKeyword = ref("");
const selectedTaskIds = ref<number[]>([]);

const loadTasks = async () => {
  taskLoading.value = true;
  try {
    const { data } = await getRemoteTasksApi(pickKind.value, pickKeyword.value);
    pickTasks.value = data ?? [];
  } finally {
    taskLoading.value = false;
  }
};

/* ---------------- 新建 / 修改 ---------------- */

const dlg = reactive({
  visible: false,
  saving: false,
  isEdit: false,
  title: "",
  // originalKeyId 是打开编辑时的键号。允许改键号，所以要留一份原值定位记录。
  originalKeyId: 0,
  form: { keyId: 1, keyName: "" }
});

const openCreate = async () => {
  Object.assign(dlg, {
    visible: true,
    saving: false,
    isEdit: false,
    title: "添加映射",
    originalKeyId: 0,
    form: { keyId: 1, keyName: "" }
  });
  selectedTaskIds.value = [];
  pickKind.value = "";
  pickKeyword.value = "";
  await loadTasks();
};

// 修改入口已随「操作」列一起去掉（:80 这一页就没有），
// 所以这里不再有 openEdit —— dlg.isEdit 恒为 false，弹窗只用于新建。
// 后端的 PUT /api/remote-keys/{id} 与 api 模块里的 updateRemoteApi 都还在，
// 哪天要把修改加回来，补一列「操作」+ 一个 openEdit 即可。

const submit = async () => {
  if (!dlg.form.keyName.trim()) return ElMessage.warning("请输入名称");
  if (!selectedTaskIds.value.length) return ElMessage.warning("请至少选择一条任务");
  const payload = {
    keyId: dlg.form.keyId,
    keyName: dlg.form.keyName.trim(),
    taskIds: selectedTaskIds.value
  };
  dlg.saving = true;
  try {
    await createRemoteApi(payload);
    ElMessage.success("保存成功");
    dlg.visible = false;
    refresh();
  } finally {
    dlg.saving = false;
  }
};

/* ---------------- 删除 ---------------- */

const doDelete = async (raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning("请先勾选遥控任务");
  await ElMessageBox.confirm(
    `确认删除选中的 ${ids.length} 个遥控键？删除后按这些键将不再触发任何任务。`,
    "删除遥控任务",
    { type: "warning", confirmButtonText: "确认删除" }
  );
  const { data } = await deleteRemotesApi(ids);
  ElMessage.success(`已删除 ${data.deleted} 条绑定`);
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
.tag-sub {
  margin-left: 4px;
  opacity: 0.75;
}
.pick-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
  width: 100%;
  margin-bottom: 8px;
}
.opt-sub {
  margin-left: 10px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.opt-tag {
  margin-left: 8px;
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
</style>
