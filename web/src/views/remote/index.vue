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
    <ProTable ref="proTableRef" :columns="columns" :request-api="getRemoteListApi" :data-callback="dataCallback" row-key="keyId">
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
            <!-- 普通用户只看得到绑在自己任务上的键，说一句免得以为丢了数据 -->
            <el-tag v-if="scopeNote" type="info" size="small" effect="plain">{{ scopeNote }}</el-tag>
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
        <!--
          映射按键是下拉，1~8 —— 旧版 set_task_mapping.html 里就是
          `for(var i=1; i<=8; i++)` 写死八项，对应遥控器上的八个物理按键。
        -->
        <el-form-item label="映射按键" required>
          <el-select v-model="dlg.form.keyId" class="fill" style="width: 160px">
            <el-option v-for="k in 8" :key="k" :label="`${k} 键`" :value="k" />
          </el-select>
        </el-form-item>

        <!--
          映射任务做成树，按三类分组（文件广播 / 采播 / 终端功放），
          与旧版那棵 dhtmlxtree 的三支一一对应（keyset_task_mapping.php）。

          ⚠ 一个按键**只能选一个任务**。旧版是勾完了再 alert「只能选择一项」，
            这里直接做成单选：勾第二个时自动把上一个取消，压根选不出第二项。
        -->
        <el-form-item label="映射任务" required>
          <div class="pick-bar">
            <el-input
              v-model="pickKeyword"
              placeholder="任务名称搜索"
              clearable
              size="small"
              :prefix-icon="Search"
              style="width: 220px"
              @input="loadTasks"
            />
            <span class="dlg-note">{{ pickedTaskName ? `已选：${pickedTaskName}` : "未选择任务" }}</span>
          </div>
          <el-tree
            ref="taskTreeRef"
            v-loading="taskLoading"
            class="rk-tree"
            :data="taskTree"
            :props="{ label: 'label', children: 'children' }"
            node-key="key"
            show-checkbox
            check-strictly
            check-on-click-node
            :expand-on-click-node="false"
            :default-expand-all="true"
            @check="onTaskCheck"
          >
            <template #default="{ data }">
              <span class="rk-node">
                <span class="rk-label">{{ data.label }}</span>
                <el-tag v-if="data.usedBy && data.usedBy !== dlg.originalKeyId" size="small" type="warning" effect="plain">
                  已绑在 {{ data.usedBy }} 键
                </el-tag>
                <span v-if="data.count !== undefined" class="rk-count">{{ data.count }}</span>
              </span>
            </template>
          </el-tree>
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
import { Search } from "@element-plus/icons-vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { computed, nextTick, onMounted, reactive, ref } from "vue";

import { createRemoteApi, deleteRemotesApi, getRemoteListApi, getRemoteTasksApi } from "@/api/modules/basecfg";
import type { RemoteKey, RemotePickTask } from "@/api/modules/basecfg";
import ProTable from "@/components/ProTable/index.vue";
import { useAuthStore } from "@/stores/modules/auth";
import type { ColumnProps, ProTableInstance } from "@/components/ProTable/interface";

const authStore = useAuthStore();
const canEdit = computed(() => !!(authStore.authButtonListGet as any)?.remote?.edit);

const scopeNote = ref("");
const dataCallback = (data: any) => {
  scopeNote.value = data.scopeNote ?? "";
  return { list: data.list, total: data.total, pageNum: data.pageNum, pageSize: data.pageSize };
};

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
const pickKeyword = ref("");
/** 选中的任务。一个按键只能绑一条，所以是单值不是数组 */
const pickedTaskId = ref(0);
const taskTreeRef = ref();

/**
 * 任务树：按三类分组，与旧版 keyset_task_mapping.php 建的那棵树一一对应。
 *
 *   文件广播   tasktype IN (2,15) AND info=''
 *   采播管理   tasktype = 3
 *   终端功放   tasktype = 5 AND sec_task_id=0 AND prepower=0
 *
 * 后端一次把三类都返回（每条带 kind / kindText），这里按 kind 归组。
 * 空的分类不显示 —— 与「增补终端」那棵树同一个处理。
 */
const taskTree = computed(() => {
  const groups = new Map<string, { key: string; label: string; count: number; children: any[] }>();
  for (const t of pickTasks.value) {
    let g = groups.get(t.kind);
    if (!g) {
      g = { key: `g:${t.kind}`, label: t.kindText || t.kind, count: 0, children: [] };
      groups.set(t.kind, g);
    }
    g.children.push({ key: `t:${t.taskId}`, label: t.taskName, taskId: t.taskId, usedBy: t.usedBy });
    g.count++;
  }
  return [...groups.values()];
});

const pickedTaskName = computed(() => pickTasks.value.find(t => t.taskId === pickedTaskId.value)?.taskName ?? "");

/**
 * 单选：勾上新的就把旧的取消。
 *
 * ⚠ check-strictly 必须开着，否则勾叶子会连带把分类节点也勾上，
 *   setCheckedKeys 再把整组子节点全勾中，一个按键就绑上一整类任务了。
 */
const onTaskCheck = (node: any) => {
  if (node.taskId === undefined) {
    // 分类节点不是可选项，点了就撤销
    taskTreeRef.value?.setCheckedKeys(pickedTaskId.value ? [`t:${pickedTaskId.value}`] : []);
    return;
  }
  const checked = taskTreeRef.value?.getCheckedKeys() ?? [];
  if (!checked.includes(node.key)) {
    // 取消勾选
    pickedTaskId.value = 0;
    taskTreeRef.value?.setCheckedKeys([]);
    return;
  }
  pickedTaskId.value = node.taskId;
  taskTreeRef.value?.setCheckedKeys([node.key]);
};

const loadTasks = async () => {
  taskLoading.value = true;
  try {
    // 树上三类都要有，所以不按 kind 过滤，只用关键字
    const { data } = await getRemoteTasksApi("", pickKeyword.value);
    pickTasks.value = data ?? [];
    // 重新拉之后把已选项的勾恢复回去（搜索会把树整棵换掉）
    await nextTick();
    taskTreeRef.value?.setCheckedKeys(pickedTaskId.value ? [`t:${pickedTaskId.value}`] : []);
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
  pickedTaskId.value = 0;
  pickKeyword.value = "";
  await loadTasks();
};

// 修改入口已随「操作」列一起去掉（:80 这一页就没有），
// 所以这里不再有 openEdit —— dlg.isEdit 恒为 false，弹窗只用于新建。
// 后端的 PUT /api/remote-keys/{id} 与 api 模块里的 updateRemoteApi 都还在，
// 哪天要把修改加回来，补一列「操作」+ 一个 openEdit 即可。

const submit = async () => {
  if (!dlg.form.keyName.trim()) return ElMessage.warning("请输入名称");
  if (!pickedTaskId.value) return ElMessage.warning("请选择一个任务");
  const payload = {
    keyId: dlg.form.keyId,
    keyName: dlg.form.keyName.trim(),
    // 接口仍收数组（表结构一个键可以有多行），但界面只让选一条
    taskIds: [pickedTaskId.value]
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
  await ElMessageBox.confirm(`确认删除选中的 ${ids.length} 个遥控键？删除后按这些键将不再触发任何任务。`, "删除遥控任务", {
    type: "warning",
    confirmButtonText: "确认删除"
  });
  const { data } = await deleteRemotesApi(ids);
  ElMessage.success(`已删除 ${data.deleted} 条绑定`);
  refresh();
};

onMounted(() => loadTasks());
</script>

<style scoped lang="scss">
.rk-tree {
  width: 100%;
  height: 300px;
  overflow: auto;
  border: 1px solid var(--el-border-color-light);
  border-radius: 4px;
}
.rk-node {
  display: flex;
  gap: 8px;
  align-items: center;
  min-width: 0;
}
.rk-label {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.rk-count {
  flex: none;
  padding: 0 5px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
  background: var(--el-fill-color-light);
  border-radius: 8px;
}
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
