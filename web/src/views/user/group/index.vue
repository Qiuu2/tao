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
        <el-button v-if="canAdd" type="primary" :icon="CirclePlus" @click="openCreate">{{ $t("user.newGroup") }}</el-button>
        <span v-else class="muted">{{ $t("user.noPermissionToCreateGroup") }}</span>
      </template>

      <template #levelCol="scope">
        <el-tag size="small" effect="plain">{{ $t("user.levelN", { n: scope.row.groupLevel }) }}</el-tag>
        <el-tag size="small" type="info" effect="plain" class="ml6">
          {{ $t("user.priorityBase") }} {{ scope.row.priorityBase }}
        </el-tag>
        <span class="raw-level">{{ $t("user.rawLevel", { n: scope.row.level }) }}</span>
      </template>

      <template #rightsCol="scope">
        <el-tag v-if="scope.row.system" type="danger" size="small">{{ $t("user.allRights") }}</el-tag>
        <template v-else>
          <el-tag v-for="k in grantedOf(scope.row.rights)" :key="k" size="small" effect="plain" class="mr4">
            {{ labelOf(k) }}
          </el-tag>
          <span v-if="!grantedOf(scope.row.rights).length" class="muted">{{ $t("user.noRights") }}</span>
        </template>
      </template>

      <template #operation="scope">
        <el-button type="primary" link :icon="EditPen" :disabled="!canEdit" @click="openEdit(scope.row)">
          {{ scope.row.system ? $t("user.viewOrEditInfo") : $t("common.edit") }}
        </el-button>
        <el-button
          type="danger"
          link
          :icon="Delete"
          :disabled="!canDelete || !scope.row.canDelete"
          :title="scope.row.system ? $t('user.systemGroupUndeletable') : ''"
          @click="openDelete(scope.row)"
        >
          {{ $t("common.delete") }}
        </el-button>
      </template>
    </ProTable>

    <!-- 新建 / 编辑 -->
    <el-dialog v-model="dlg.visible" :title="dlg.title" width="720px" top="6vh">
      <el-alert v-if="dlg.system" type="warning" :closable="false" show-icon class="mb12">
        {{ $t("user.systemGroupNotice") }}
      </el-alert>

      <el-form :model="dlg.form" label-width="110px">
        <el-form-item :label='$t("user.groupName")' required>
          <el-input v-model="dlg.form.name" :disabled="dlg.system" maxlength="60" show-word-limit />
        </el-form-item>
        <el-form-item :label='$t("common.description")'>
          <el-input v-model="dlg.form.info" maxlength="60" show-word-limit :placeholder='$t("common.optional")' />
        </el-form-item>

        <el-form-item :label='$t("user.groupLevel")'>
          <el-select v-model="dlg.form.groupLevel" :disabled="dlg.system" style="width: 200px">
            <el-option v-for="n in groupLevelOptions" :key="n" :label="levelLabel(n)" :value="n" />
          </el-select>
        </el-form-item>

        <el-form-item :label='$t("user.priorityBase")'>
          <el-select v-model="dlg.form.priorityBase" :disabled="dlg.system" style="width: 140px">
            <el-option v-for="n in 10" :key="n - 1" :label="String(n - 1)" :value="n - 1" />
          </el-select>
        </el-form-item>

        <el-alert v-if="dlg.isEdit && levelChanged" type="warning" :closable="false" show-icon class="mb12">
          {{ $t("user.levelChangedWarn") }}<b>{{ $t("user.levelChangedWarnBold") }}</b>
          {{ $t("user.levelChangedWarnTail", { n: dlg.form.groupLevel * 10 + dlg.form.priorityBase }) }}
        </el-alert>

        <el-form-item :label='$t("user.rights")'>
          <!--
            按新 web 的菜单分组排列，每一项下面写清它到底管住哪几页 ——
            勾了就能进、不勾就进不去，菜单与按钮都跟着它走。
          -->
          <div class="rights-wrap">
            <div v-for="g in RIGHT_GROUPS" :key="g" class="right-group">
              <div class="right-group-head">
                <span class="right-group-title">{{ $t(g) }}</span>
                <el-button v-if="!dlg.system" link type="primary" size="small" @click="setGroupRights(g, 1)"> {{ $t("user.selectAllRights") }} </el-button>
                <el-button v-if="!dlg.system" link size="small" @click="setGroupRights(g, 0)">{{ $t("user.clearAllRights") }}</el-button>
              </div>
              <div class="rights-grid">
                <div v-for="item in itemsOf(g)" :key="item.key" class="right-item">
                  <el-checkbox
                    :model-value="dlg.form.rights[item.key] === 1"
                    :disabled="dlg.system"
                    @update:model-value="v => (dlg.form.rights[item.key] = v ? 1 : 0)"
                  >
                    {{ $t(item.label) }}
                  </el-checkbox>
                  <div class="right-tip">{{ $t(item.tip) }}</div>
                </div>
              </div>
            </div>
          </div>
          <div v-if="!dlg.system" class="rights-ops">
            <el-button link type="primary" @click="setAllRights(1)">{{ $t("user.selectAllRights") }}</el-button>
            <el-button link @click="setAllRights(0)">{{ $t("user.clearAllRights") }}</el-button>
          </div>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dlg.visible = false">{{ $t("common.cancel") }}</el-button>
        <el-button type="primary" :loading="dlg.loading" @click="submit">{{ $t("common.confirm") }}</el-button>
      </template>
    </el-dialog>

    <!-- 删除影响面 -->
    <el-dialog v-model="del.visible" :title='$t("user.deleteGroupTitle")' width="560px">
      <el-alert type="error" :closable="false" show-icon class="mb12">
        {{ $t("user.deleteGroupWarn") }}<b>{{ $t("user.deleteGroupWarnBold") }}</b>{{ $t("user.deleteGroupWarnTail") }}
      </el-alert>

      <el-descriptions v-if="del.impact" :column="2" border size="small">
        <el-descriptions-item :label='$t("user.cascadeUsers")'>{{ $t("user.nItems", { n: del.impact.users }) }}</el-descriptions-item>
        <el-descriptions-item :label='$t("user.folders")'>{{ $t("user.nItems", { n: del.impact.folders }) }}</el-descriptions-item>
        <el-descriptions-item :label='$t("taskCommon.media")'>{{ $t("user.nItems", { n: del.impact.media }) }}</el-descriptions-item>
        <el-descriptions-item :label='$t("taskCommon.task")'>{{ $t("user.nRows", { n: del.impact.tasks }) }}</el-descriptions-item>
        <el-descriptions-item :label='$t("user.terminalZones")'>{{ $t("user.nItems", { n: del.impact.terminalGroups }) }}</el-descriptions-item>
        <el-descriptions-item :label='$t("user.alarmZones")'>{{ $t("user.nItems", { n: del.impact.alarmAreas }) }}</el-descriptions-item>
      </el-descriptions>

      <div v-if="del.impact?.userNames?.length" class="mt12">
        <div class="muted mb6">{{ $t("user.usersToDelete") }}</div>
        <el-tag v-for="n in del.impact.userNames" :key="n" size="small" type="danger" effect="plain" class="mr4">
          {{ n }}
        </el-tag>
      </div>

      <el-form label-width="130px" class="mt12">
        <el-form-item :label='$t("user.typeGroupName")'>
          <el-input v-model="del.confirmText" :placeholder="del.row?.name" />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="del.visible = false">{{ $t("common.cancel") }}</el-button>
        <el-button type="danger" :disabled="del.confirmText !== del.row?.name" :loading="del.loading" @click="confirmDelete">
          {{ $t("user.confirmDeleteIt") }}
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="tsx" name="userGroup">
import { useI18n } from "vue-i18n";
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

// 脚本里拼的文案用 t()；模板里的 $t 不用引入
const { t } = useI18n();

const authStore = useAuthStore();
const btn = computed(() => (authStore.authButtonListGet as any)?.usergroup ?? {});
const canAdd = computed(() => !!btn.value.add);
const canEdit = computed(() => !!btn.value.edit);
const canDelete = computed(() => !!btn.value.delete);

const proTableRef = ref();
const refresh = () => proTableRef.value?.getTableList?.();

const columns = reactive<ColumnProps<UserGroup>[]>([
  { prop: "id", label: "ID", width: 80 },
  { prop: "name", label: t("common.userGroup"), search: { el: "input", key: "keyword", props: { placeholder: t("user.searchByName") } } },
  { prop: "levelCol", label: t("user.levelAndPriority"), width: 320 },
  { prop: "userCount", label: t("user.groupMembers"), width: 100 },
  { prop: "rightsCol", label: t("user.rights"), minWidth: 300 },
  { prop: "info", label: t("common.description"), minWidth: 140, showOverflowTooltip: true },
  { prop: "operation", label: t("common.operation"), width: 190, fixed: "right" }
]);

// 权限项的 label 存的是 i18n 键（见 account.ts 的注释），这里翻出来再显示。
const labelOf = (k: string) => {
  const item = RIGHT_ITEMS.find(i => i.key === k);
  return item ? t(item.label) : k;
};
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

/*
  组级别的可选值。level 的取值区间照旧版那个下拉：10~109
  （userGroupAdd_form.html 里 `for(levels=10; levels<=109; levels++)`），
  拆成十位（组级别 1~10）与个位（优先级基数 0~9）。

  ⚠ 旧库里有 level 为 1 / 3 / 5 的用户组，十位是 0，落在 1~10 之外。
  把这个旧值也放进选项里，否则下拉显示空白、这些用户组连描述都改不了。
  后端同样只在级别真被改动时才校验区间。
*/
const groupLevelOptions = computed(() => {
  const list = Array.from({ length: 10 }, (_, i) => i + 1);
  const cur = dlg.form.groupLevel;
  if (!list.includes(cur)) list.unshift(cur);
  return list;
});
const levelLabel = (n: number) => (n >= 1 && n <= 10 ? t("user.levelN", { n }) : t("user.levelNLegacy", { n }));

const openCreate = () => {
  Object.assign(dlg, {
    visible: true,
    isEdit: false,
    system: false,
    title: t("user.newGroup"),
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
    title: row.system ? t("user.systemGroup") : t("user.editGroup"),
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
  if (!dlg.form.name.trim()) return ElMessage.warning(t("user.groupNameRequired"));
  dlg.loading = true;
  try {
    if (dlg.isEdit) {
      const { data } = await updateGroupApi(dlg.id, { ...dlg.form });
      const rc = data?.priorityRecalc;
      if (rc?.affectedTasks) {
        ElNotification({
          title: t("common.updateSuccess"),
          message: t("user.recalcSummary", { users: rc.affectedUsers, tasks: rc.affectedTasks }),
          type: "success",
          duration: 6000
        });
      } else {
        ElMessage.success(t("common.updateSuccess"));
      }
    } else {
      await createGroupApi({ ...dlg.form });
      ElMessage.success(t("common.createSuccess"));
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
      title: t("common.deleteDone"),
      message: t("user.groupDeletedSummary", {
        users: data?.users ?? 0,
        tasks: data?.tasks ?? 0,
        media: data?.media ?? 0
      }),
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
