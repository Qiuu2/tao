<!--
  用户组管理

  与旧版的差别：
    · level 是 10~109 的两位复合值（十位=组级别，个位=任务优先级基数）。
      旧版直接甩一个 10~109 的下拉给用户，没人知道该选什么；
      这里拆成两个语义清晰的控件，提交时由后端合成，存储格式完全不变。
    · 改组级别会连带重算组内**所有**用户的任务优先级。
      旧版只重算了组内第一个用户（取用户时用了 if 而不是 while）。
      这里改完会明确告诉你影响了多少个用户、多少条任务。
    · 删除用户组前先展示影响面，并要求输入组名确认。
      旧版只弹一句「确定删除吗?」，而实际后果是整组用户连同他们的
      文件夹、媒体、任务、分区全部消失。
    · 系统用户组（id=1）的名称、级别、权限全部只读，仅描述可改。
    · 「功能权限」按新 web 的菜单分组排列，每一项下面注明它管住哪几页。
      ⚠ 列名与含义的对应关系照旧版原样保留（serverpriv=遥控管理、admpriv=采播管理、
      powerplay=终端功放、ttspriv=文字语音），别按列名字面重新解释 ——
      库里已有的用户组就是按这套配的。
-->
<template>
  <div class="table-box">
    <ProTable ref="proTableRef" :columns="columns" :request-api="getGroupListApi" row-key="id">
      <template #tableHeader>
        <el-button v-if="canAdd" type="primary" :icon="CirclePlus" @click="openCreate">新建用户组</el-button>
        <span v-else class="muted">当前账号没有新建用户组的权限</span>
      </template>

      <template #levelCol="scope">
        <el-tag size="small" effect="plain">级别 {{ scope.row.groupLevel }}</el-tag>
        <el-tag size="small" type="info" effect="plain" class="ml6">优先级基数 {{ scope.row.priorityBase }}</el-tag>
        <span class="raw-level">（level={{ scope.row.level }}）</span>
      </template>

      <template #rightsCol="scope">
        <el-tag v-if="scope.row.system" type="danger" size="small">全部权限</el-tag>
        <template v-else>
          <el-tag v-for="k in grantedOf(scope.row.rights)" :key="k" size="small" effect="plain" class="mr4">
            {{ labelOf(k) }}
          </el-tag>
          <span v-if="!grantedOf(scope.row.rights).length" class="muted">无任何权限</span>
        </template>
      </template>

      <template #operation="scope">
        <el-button type="primary" link :icon="EditPen" :disabled="!canEdit" @click="openEdit(scope.row)">
          {{ scope.row.system ? "查看 / 改描述" : "编辑" }}
        </el-button>
        <el-button
          type="danger"
          link
          :icon="Delete"
          :disabled="!canDelete || !scope.row.canDelete"
          :title="scope.row.system ? '系统用户组不可删除' : ''"
          @click="openDelete(scope.row)"
        >
          删除
        </el-button>
      </template>
    </ProTable>

    <!-- 新建 / 编辑 -->
    <el-dialog v-model="dlg.visible" :title="dlg.title" width="720px" top="6vh">
      <el-alert v-if="dlg.system" type="warning" :closable="false" show-icon class="mb12">
        这是系统用户组，拥有全部权限。名称、级别与权限均不可修改，只能修改描述。
      </el-alert>

      <el-form :model="dlg.form" label-width="110px">
        <el-form-item label="用户组名称" required>
          <el-input v-model="dlg.form.name" :disabled="dlg.system" maxlength="60" show-word-limit />
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="dlg.form.info" maxlength="60" show-word-limit placeholder="选填" />
        </el-form-item>

        <el-form-item label="组级别">
          <el-select v-model="dlg.form.groupLevel" :disabled="dlg.system" style="width: 140px">
            <el-option v-for="n in 9" :key="n" :label="`级别 ${n}`" :value="n" />
          </el-select>
        </el-form-item>

        <el-form-item label="优先级基数">
          <el-select v-model="dlg.form.priorityBase" :disabled="dlg.system" style="width: 140px">
            <el-option v-for="n in 10" :key="n - 1" :label="String(n - 1)" :value="n - 1" />
          </el-select>
        </el-form-item>

        <el-alert v-if="dlg.isEdit && levelChanged" type="warning" :closable="false" show-icon class="mb12">
          组级别或优先级基数已改动。保存后会<b>重算该组内所有用户的任务优先级</b>： 新优先级 =
          {{ dlg.form.groupLevel * 10 + dlg.form.priorityBase }} + 原优先级个位。
        </el-alert>

        <el-form-item label="功能权限">
          <!--
            按新 web 的菜单分组排列，每一项下面写清它到底管住哪几页 ——
            勾了就能进、不勾就进不去，菜单与按钮都跟着它走。
          -->
          <div class="rights-wrap">
            <div v-for="g in RIGHT_GROUPS" :key="g" class="right-group">
              <div class="right-group-head">
                <span class="right-group-title">{{ g }}</span>
                <el-button v-if="!dlg.system" link type="primary" size="small" @click="setGroupRights(g, 1)"> 全选 </el-button>
                <el-button v-if="!dlg.system" link size="small" @click="setGroupRights(g, 0)">全不选</el-button>
              </div>
              <div class="rights-grid">
                <div v-for="item in itemsOf(g)" :key="item.key" class="right-item">
                  <el-checkbox
                    :model-value="dlg.form.rights[item.key] === 1"
                    :disabled="dlg.system"
                    @update:model-value="v => (dlg.form.rights[item.key] = v ? 1 : 0)"
                  >
                    {{ item.label }}
                  </el-checkbox>
                  <div class="right-tip">{{ item.tip }}</div>
                </div>
              </div>
            </div>
          </div>
          <div v-if="!dlg.system" class="rights-ops">
            <el-button link type="primary" @click="setAllRights(1)">全选</el-button>
            <el-button link @click="setAllRights(0)">全不选</el-button>
          </div>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dlg.visible = false">取消</el-button>
        <el-button type="primary" :loading="dlg.loading" @click="submit">确定</el-button>
      </template>
    </el-dialog>

    <!-- 删除影响面 -->
    <el-dialog v-model="del.visible" title="删除用户组" width="560px">
      <el-alert type="error" :closable="false" show-icon class="mb12">
        删除用户组会<b>连同组内全部用户一起删除</b>，并清空他们名下的所有数据，不可恢复。
      </el-alert>

      <el-descriptions v-if="del.impact" :column="2" border size="small">
        <el-descriptions-item label="连带删除用户">{{ del.impact.users }} 个</el-descriptions-item>
        <el-descriptions-item label="文件夹">{{ del.impact.folders }} 个</el-descriptions-item>
        <el-descriptions-item label="媒体">{{ del.impact.media }} 个</el-descriptions-item>
        <el-descriptions-item label="任务">{{ del.impact.tasks }} 条</el-descriptions-item>
        <el-descriptions-item label="终端分区">{{ del.impact.terminalGroups }} 个</el-descriptions-item>
        <el-descriptions-item label="报警分区">{{ del.impact.alarmAreas }} 个</el-descriptions-item>
      </el-descriptions>

      <div v-if="del.impact?.userNames?.length" class="mt12">
        <div class="muted mb6">将被删除的用户：</div>
        <el-tag v-for="n in del.impact.userNames" :key="n" size="small" type="danger" effect="plain" class="mr4">
          {{ n }}
        </el-tag>
      </div>

      <el-form label-width="130px" class="mt12">
        <el-form-item label="请输入用户组名">
          <el-input v-model="del.confirmText" :placeholder="del.row?.name" />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="del.visible = false">取消</el-button>
        <el-button type="danger" :disabled="del.confirmText !== del.row?.name" :loading="del.loading" @click="confirmDelete">
          我确认删除
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="tsx" name="userGroup">
import { CirclePlus, Delete, EditPen } from "@element-plus/icons-vue";
import { ElMessage, ElNotification } from "element-plus";
import { computed, reactive, ref } from "vue";

import {
  CascadeImpact,
  createGroupApi,
  deleteGroupApi,
  emptyRights,
  getGroupListApi,
  previewDeleteGroupApi,
  RIGHT_GROUPS,
  RIGHT_ITEMS,
  Rights,
  updateGroupApi,
  UserGroup
} from "@/api/modules/account";
import ProTable from "@/components/ProTable/index.vue";
import { ColumnProps } from "@/components/ProTable/interface";
import { useAuthStore } from "@/stores/modules/auth";

const authStore = useAuthStore();
const btn = computed(() => (authStore.authButtonListGet as any)?.usergroup ?? {});
const canAdd = computed(() => !!btn.value.add);
const canEdit = computed(() => !!btn.value.edit);
const canDelete = computed(() => !!btn.value.delete);

const proTableRef = ref();
const refresh = () => proTableRef.value?.getTableList?.();

const columns = reactive<ColumnProps<UserGroup>[]>([
  { prop: "id", label: "ID", width: 80 },
  { prop: "name", label: "用户组", search: { el: "input", key: "keyword", props: { placeholder: "按名称搜索" } } },
  { prop: "levelCol", label: "级别 / 优先级", width: 320 },
  { prop: "userCount", label: "组内用户", width: 100 },
  { prop: "rightsCol", label: "功能权限", minWidth: 300 },
  { prop: "info", label: "描述", minWidth: 140, showOverflowTooltip: true },
  { prop: "operation", label: "操作", width: 190, fixed: "right" }
]);

const labelOf = (k: string) => RIGHT_ITEMS.find(i => i.key === k)?.label ?? k;
const grantedOf = (r?: Rights) => (r ? RIGHT_ITEMS.filter(i => r[i.key] === 1).map(i => i.key as string) : []);

/* ---------------- 新建 / 编辑 ---------------- */

const dlg = reactive({
  visible: false,
  isEdit: false,
  system: false,
  title: "",
  id: 0,
  originLevel: 0,
  loading: false,
  form: {
    name: "",
    info: "",
    groupLevel: 1,
    priorityBase: 0,
    rights: emptyRights()
  }
});

// 级别有变动时提示会重算任务优先级
const levelChanged = computed(() => dlg.form.groupLevel * 10 + dlg.form.priorityBase !== dlg.originLevel);

const openCreate = () => {
  Object.assign(dlg, {
    visible: true,
    isEdit: false,
    system: false,
    title: "新建用户组",
    id: 0,
    originLevel: 10,
    loading: false,
    form: { name: "", info: "", groupLevel: 1, priorityBase: 0, rights: emptyRights() }
  });
};

const openEdit = (row: UserGroup) => {
  Object.assign(dlg, {
    visible: true,
    isEdit: true,
    system: row.system,
    title: row.system ? "系统用户组" : "修改用户组",
    id: row.id,
    originLevel: row.level,
    loading: false,
    form: {
      name: row.name,
      info: row.info,
      groupLevel: row.groupLevel,
      priorityBase: row.priorityBase,
      // 系统组权限恒为全开，后端也会校验，这里直接铺满避免误提交
      rights: row.system ? emptyRights(1) : { ...row.rights }
    }
  });
};

const itemsOf = (group: string) => RIGHT_ITEMS.filter(i => i.group === group);

const setAllRights = (v: number) => (dlg.form.rights = emptyRights(v));
const setGroupRights = (group: string, v: number) => itemsOf(group).forEach(i => (dlg.form.rights[i.key] = v));

const submit = async () => {
  if (!dlg.form.name.trim()) return ElMessage.warning("请输入用户组名称");
  dlg.loading = true;
  try {
    if (dlg.isEdit) {
      const { data } = await updateGroupApi(dlg.id, { ...dlg.form });
      const rc = data?.priorityRecalc;
      if (rc?.affectedTasks) {
        ElNotification({
          title: "修改成功",
          message: `已重算 ${rc.affectedUsers} 个用户共 ${rc.affectedTasks} 条任务的优先级`,
          type: "success",
          duration: 6000
        });
      } else {
        ElMessage.success("修改成功");
      }
    } else {
      await createGroupApi({ ...dlg.form });
      ElMessage.success("创建成功");
    }
    dlg.visible = false;
    refresh();
  } finally {
    dlg.loading = false;
  }
};

/* ---------------- 删除 ---------------- */

const del = reactive<{
  visible: boolean;
  row?: UserGroup;
  impact?: CascadeImpact;
  confirmText: string;
  loading: boolean;
}>({ visible: false, confirmText: "", loading: false });

const openDelete = async (row: UserGroup) => {
  const { data } = await previewDeleteGroupApi(row.id);
  Object.assign(del, { visible: true, row, impact: data, confirmText: "", loading: false });
};

const confirmDelete = async () => {
  if (!del.row) return;
  del.loading = true;
  try {
    const { data } = await deleteGroupApi(del.row.id, del.confirmText);
    ElNotification({
      title: "删除完成",
      message: `已删除用户组及 ${data?.users ?? 0} 个用户、${data?.tasks ?? 0} 条任务、${data?.media ?? 0} 个媒体`,
      type: "success",
      duration: 6000
    });
    del.visible = false;
    refresh();
  } finally {
    del.loading = false;
  }
};
</script>

<style scoped lang="scss">
.rights-wrap {
  width: 100%;
}
.right-group + .right-group {
  padding-top: 10px;
  margin-top: 10px;
  border-top: 1px dashed var(--el-border-color-lighter);
}
.right-group-head {
  display: flex;
  gap: 8px;
  align-items: center;
  margin-bottom: 2px;
}
.right-group-title {
  margin-right: 4px;
  font-size: 12px;
  font-weight: 600;
  color: var(--el-text-color-secondary);
}
.rights-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 6px 14px;
  width: 100%;
}
.right-item {
  padding: 4px 0;
}
.right-tip {
  padding-left: 24px;
  font-size: 11px;
  line-height: 1.4;
  color: var(--el-text-color-secondary);
}
.rights-ops {
  width: 100%;
  margin-top: 6px;
}
.form-tip {
  margin-left: 10px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.raw-level {
  margin-left: 6px;
  font-size: 11px;
  color: var(--el-text-color-secondary);
}
.muted {
  color: var(--el-text-color-secondary);
}
.ml6 {
  margin-left: 6px;
}
.mr4 {
  margin-right: 4px;
}
.mb6 {
  margin-bottom: 6px;
}
.mb12 {
  margin-bottom: 12px;
}
.mt12 {
  margin-top: 12px;
}
</style>
