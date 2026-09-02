<!--
  用户管理

  相对旧版的关键修复：
    · 编辑时密码留空 = 不改密码。
      旧版把「两个空串相等且长度合法」当成了合法输入，
      管理员只改个描述也会把密码悄悄改成 md5("")，用户直接登不进去。
    · 删除用户前展示影响面（文件夹 / 媒体 / 任务 / 分区）。
    · 分控 ID 由服务端在区间内自动分配，界面只给开关，
      并实时显示三类分控的授权名额占用情况。
    · 终端绑定改为结构化选择，不再靠两条逗号串按下标对齐。
    · 停用用户会连带停用其名下全部任务，操作前明确告知条数。
-->
<template>
  <div class="table-box">
    <ProTable ref="proTableRef" :columns="columns" :request-api="getUserListApi" :data-callback="dataCallback" row-key="id">
      <template #tableHeader="scope">
        <div class="header-bar">
          <div class="header-left">
            <el-button
              type="primary"
              :icon="CirclePlus"
              :disabled="!canAdd || !capacity?.canCreateUser"
              :title="addDisabledReason"
              @click="openCreate"
            >
              新建用户
            </el-button>
            <el-button
              type="danger"
              :icon="Delete"
              :disabled="!canDelete || !scope.isSelected"
              @click="openDelete(scope.selectedListIds)"
            >
              删除选中{{ scope.selectedListIds.length ? `(${scope.selectedListIds.length})` : "" }}
            </el-button>
          </div>
          <div class="header-right">
            <el-tag v-if="scopeNote" type="info" size="small" effect="plain">{{ scopeNote }}</el-tag>
            <el-tag v-if="capacity" size="small" effect="plain">
              分控授权 {{ capacity.capacity }} 路 · 手机 {{ capacity.ctrlUsed }} / 分控 {{ capacity.subUsed }} / 监控
              {{ capacity.cameraUsed }}
            </el-tag>
          </div>
        </div>
      </template>

      <template #enableCol="scope">
        <el-tag v-if="scope.row.enable === 1" type="success" size="small">启用</el-tag>
        <el-tag v-else type="info" size="small">停用</el-tag>
      </template>

      <template #windCol="scope">
        <el-tag v-if="scope.row.ctrlwind > 0" size="small" effect="plain" class="mr4">手机 {{ scope.row.ctrlwind }}</el-tag>
        <el-tag v-if="scope.row.subwind > 0" size="small" effect="plain" class="mr4">分控 {{ scope.row.subwind }}</el-tag>
        <el-tag v-if="scope.row.camerawind > 0" size="small" effect="plain">监控 {{ scope.row.camerawind }}</el-tag>
        <span v-if="!scope.row.ctrlwind && !scope.row.subwind && !scope.row.camerawind" class="muted">—</span>
      </template>

      <template #operation="scope">
        <el-button type="primary" link :icon="EditPen" @click="openEdit(scope.row)">编辑</el-button>
        <el-button
          v-if="scope.row.enable === 1"
          type="warning"
          link
          :icon="TurnOff"
          :disabled="!canEnable || !scope.row.canDelete"
          @click="toggleEnable(scope.row, false)"
        >
          停用
        </el-button>
        <el-button v-else type="success" link :icon="Open" :disabled="!canEnable" @click="toggleEnable(scope.row, true)">
          启用
        </el-button>
      </template>
    </ProTable>

    <!-- 新建 / 编辑 -->
    <el-dialog v-model="dlg.visible" :title="dlg.title" width="780px" top="5vh">
      <el-form :model="dlg.form" label-width="120px">
        <el-divider content-position="left">基本信息</el-divider>

        <el-form-item label="用户名" required>
          <el-input v-model="dlg.form.username" :disabled="dlg.usernameLocked" maxlength="50" show-word-limit />
        </el-form-item>

        <el-form-item :label="dlg.isEdit ? '新密码' : '密码'" :required="!dlg.isEdit">
          <el-input v-model="dlg.form.password" type="password" show-password maxlength="20" autocomplete="new-password" />
        </el-form-item>

        <el-form-item label="确认密码" :required="!dlg.isEdit">
          <el-input
            v-model="dlg.form.confirmPassword"
            type="password"
            show-password
            maxlength="20"
            autocomplete="new-password"
          />
        </el-form-item>

        <el-form-item label="所属用户组" required>
          <el-select v-model="dlg.form.usergroupId" :disabled="dlg.groupLocked" style="width: 260px">
            <el-option v-for="g in groupOptions" :key="g.id" :label="`${g.name}（级别 ${g.groupLevel}）`" :value="g.id" />
          </el-select>
        </el-form-item>

        <el-form-item label="描述">
          <el-input v-model="dlg.form.info" maxlength="60" show-word-limit placeholder="选填" />
        </el-form-item>

        <el-divider content-position="left">分控软件</el-divider>
        <el-form-item label="启用分控">
          <div class="wind-row">
            <el-checkbox v-model="dlg.form.enableCtrlwind">
              手机分控
              <span class="wind-quota">（1 ~ {{ capacity?.capacity ?? "-" }}）</span>
            </el-checkbox>
            <el-checkbox v-model="dlg.form.enableSubwind">
              分控软件
              <span class="wind-quota">（1001 ~ {{ 1000 + (capacity?.capacity ?? 0) }}）</span>
            </el-checkbox>
            <el-checkbox v-model="dlg.form.enableCamerawind">
              监控软件
              <span class="wind-quota">（2001 ~ {{ 2000 + (capacity?.capacity ?? 0) }}）</span>
            </el-checkbox>
          </div>
        </el-form-item>

        <el-divider content-position="left">授权序列号（最多 3 条）</el-divider>
        <el-form-item v-for="i in 3" :key="i" :label="`序列号 ${i}`">
          <el-input v-model="dlg.form.serials[i - 1]" maxlength="64" placeholder="选填，留空表示删除该条" />
        </el-form-item>

        <el-divider content-position="left">终端绑定</el-divider>
        <el-form-item label="可控制的终端">
          <!--
            ⚠ 这个接口是一次性全量返回的（没有 keyword 参数），所以不监听 @search，
              树组件的搜索框在这里只会白跑一趟。传 :searchable="false" 把它藏掉。
          -->
          <TerminalTree
            v-model="selectedTerminalIds"
            :terminals="terminalNodes"
            height="280px"
            :searchable="false"
          />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dlg.visible = false">取消</el-button>
        <el-button type="primary" :loading="dlg.loading" @click="submit">确定</el-button>
      </template>
    </el-dialog>

    <!-- 删除影响面 -->
    <el-dialog v-model="del.visible" title="删除用户" width="560px">
      <el-alert type="error" :closable="false" show-icon class="mb12">
        删除用户会同时清空他名下的<b>全部数据</b>，不可恢复。
      </el-alert>

      <el-descriptions v-if="del.impact" :column="2" border size="small">
        <el-descriptions-item label="用户">{{ del.impact.users }} 个</el-descriptions-item>
        <el-descriptions-item label="文件夹">{{ del.impact.folders }} 个</el-descriptions-item>
        <el-descriptions-item label="媒体">{{ del.impact.media }} 个</el-descriptions-item>
        <el-descriptions-item label="任务">{{ del.impact.tasks }} 条</el-descriptions-item>
        <el-descriptions-item label="终端分区">{{ del.impact.terminalGroups }} 个</el-descriptions-item>
        <el-descriptions-item label="报警分区">{{ del.impact.alarmAreas }} 个</el-descriptions-item>
      </el-descriptions>

      <div v-if="del.impact?.userNames?.length" class="mt12">
        <el-tag v-for="n in del.impact.userNames" :key="n" size="small" type="danger" effect="plain" class="mr4">
          {{ n }}
        </el-tag>
      </div>

      <template #footer>
        <el-button @click="del.visible = false">取消</el-button>
        <el-button type="danger" :disabled="!del.ids.length" :loading="del.loading" @click="confirmDelete">
          确认删除
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="tsx" name="userList">
import { CirclePlus, Delete, EditPen, Open, TurnOff } from "@element-plus/icons-vue";
import { ElMessage, ElMessageBox, ElNotification } from "element-plus";
import { computed, onMounted, reactive, ref } from "vue";

import {
  CascadeImpact,
  createUserApi,
  deleteUsersApi,
  getGroupOptionsApi,
  getTerminalOptionsApi,
  getUserApi,
  getUserListApi,
  getWindCapacityApi,
  GroupOption,
  previewDeleteUserApi,
  setUserEnableApi,
  TerminalOption,
  updateUserApi,
  UserRow,
  WindCapacity
} from "@/api/modules/account";
import ProTable from "@/components/ProTable/index.vue";
import TerminalTree from "@/components/TerminalTree/index.vue";
import { ColumnProps } from "@/components/ProTable/interface";
import { useAuthStore } from "@/stores/modules/auth";

const authStore = useAuthStore();
const btn = computed(() => (authStore.authButtonListGet as any)?.user ?? {});
const canAdd = computed(() => !!btn.value.add);
const canDelete = computed(() => !!btn.value.delete);
const canEnable = computed(() => !!btn.value.enable);

const proTableRef = ref();
const refresh = () => proTableRef.value?.getTableList?.();

const scopeNote = ref("");
const capacity = ref<WindCapacity>();
const groupOptions = ref<GroupOption[]>([]);
const terminalOptions = ref<TerminalOption[]>([]);

/*
  「已绑定给别人」这条提示要在树上看得见。树组件只认 name / groupName / netstate，
  所以把提示拼进名字里 —— 比给组件加一次性插槽干净。
  ⚠ 重复绑定是允许的（两人同时有控制权），所以只是提示，不置灰。
*/
const terminalNodes = computed(() =>
  terminalOptions.value.map(t => ({
    ...t,
    name: t.occupied ? `${t.name || "终端 " + t.id}（已绑定 ${t.ownerName}）` : t.name || `终端 ${t.id}`
  }))
);

const addDisabledReason = computed(() => {
  if (!canAdd.value) return "当前账号没有新建用户的权限";
  if (capacity.value && !capacity.value.canCreateUser) return "服务器未注册，禁止新建用户";
  return "";
});

const columns = reactive<ColumnProps<UserRow>[]>([
  { type: "selection", fixed: "left", width: 55 },
  { prop: "id", label: "编号", width: 80 },
  { prop: "username", label: "用户名", search: { el: "input", key: "keyword", props: { placeholder: "按用户名搜索" } } },
  { prop: "usergroupName", label: "用户组", width: 160 },
  { prop: "enableCol", label: "状态", width: 90 },
  { prop: "windCol", label: "分控 ID", minWidth: 220 },
  { prop: "terminalCount", label: "绑定终端", width: 100 },
  { prop: "info", label: "描述", minWidth: 140, showOverflowTooltip: true },
  { prop: "operation", label: "操作", width: 170, fixed: "right" }
]);

const dataCallback = (data: any) => {
  scopeNote.value = data.scopeNote || "";
  return data;
};

const loadMeta = async () => {
  const [cap, groups] = await Promise.all([getWindCapacityApi(), getGroupOptionsApi()]);
  capacity.value = cap.data;
  groupOptions.value = groups.data ?? [];
};

/* ---------------- 新建 / 编辑 ---------------- */

const emptyForm = () => ({
  username: "",
  password: "",
  confirmPassword: "",
  usergroupId: 0,
  info: "",
  enableCtrlwind: false,
  enableSubwind: false,
  enableCamerawind: false,
  serials: ["", "", ""] as string[],
  terminals: [] as { terminalId: number; groupId: number }[]
});

const dlg = reactive({
  visible: false,
  isEdit: false,
  title: "",
  id: 0,
  usernameLocked: false,
  groupLocked: false,
  loading: false,
  form: emptyForm()
});

// 终端多选只维护 id 列表，提交时再补上各自的分区 id
const selectedTerminalIds = ref<number[]>([]);

const loadTerminals = async (forUserId: number) => {
  const { data } = await getTerminalOptionsApi(forUserId);
  terminalOptions.value = data ?? [];
};

const openCreate = async () => {
  Object.assign(dlg, {
    visible: true,
    isEdit: false,
    title: "新建用户",
    id: 0,
    usernameLocked: false,
    groupLocked: false,
    loading: false,
    form: emptyForm()
  });
  // 默认选中级别最低的非系统组，避免误建成超级管理员
  const def = groupOptions.value.find(g => !g.system) ?? groupOptions.value[0];
  if (def) dlg.form.usergroupId = def.id;
  selectedTerminalIds.value = [];
  await loadTerminals(0);
};

const openEdit = async (row: UserRow) => {
  const { data } = await getUserApi(row.id);
  Object.assign(dlg, {
    visible: true,
    isEdit: true,
    title: `修改用户：${data.username}`,
    id: data.id,
    usernameLocked: data.usernameLocked,
    groupLocked: data.groupLocked,
    loading: false,
    form: {
      username: data.username,
      // 密码框恒为空，留空即不修改
      password: "",
      confirmPassword: "",
      usergroupId: data.usergroupId,
      info: data.info,
      enableCtrlwind: data.enableCtrlwind,
      enableSubwind: data.enableSubwind,
      enableCamerawind: data.enableCamerawind,
      serials: [data.serials?.[0] ?? "", data.serials?.[1] ?? "", data.serials?.[2] ?? ""],
      terminals: data.terminals ?? []
    }
  });
  selectedTerminalIds.value = (data.terminals ?? []).map(t => t.terminalId);
  await loadTerminals(data.id);
};

const submit = async () => {
  const f = dlg.form;
  if (!f.username.trim()) return ElMessage.warning("请输入用户名");
  if (!f.usergroupId) return ElMessage.warning("请选择所属用户组");
  if (!dlg.isEdit && !f.password) return ElMessage.warning("请输入密码");
  if (f.password !== f.confirmPassword) return ElMessage.warning("两次输入的密码不一致");

  // 把选中的终端 id 还原成 {terminalId, groupId} —— groupId 从选项里取，
  // 不再像旧版那样让前端传两条平行的逗号串靠下标对齐
  const terminals = selectedTerminalIds.value.map(id => {
    const opt = terminalOptions.value.find(t => t.id === id);
    return { terminalId: id, groupId: opt?.groupId ?? 0 };
  });

  dlg.loading = true;
  try {
    if (dlg.isEdit) {
      const { data } = await updateUserApi(dlg.id, { ...f, terminals });
      const parts: string[] = [];
      if (data?.passwordChanged) parts.push("密码已重置");
      if (data?.priorityRecalc?.affectedTasks) {
        parts.push(`重算了 ${data.priorityRecalc.affectedTasks} 条任务的优先级`);
      }
      ElNotification({
        title: "修改成功",
        message: parts.length ? parts.join("；") : "用户信息已更新（密码未改动）",
        type: "success"
      });
    } else {
      const { data } = await createUserApi({ ...f, terminals });
      const wind: string[] = [];
      if (data?.ctrlwind) wind.push(`手机分控 ${data.ctrlwind}`);
      if (data?.subwind) wind.push(`分控软件 ${data.subwind}`);
      if (data?.camerawind) wind.push(`监控软件 ${data.camerawind}`);
      ElNotification({
        title: "创建成功",
        message: wind.length ? `已分配：${wind.join("、")}` : "用户已创建",
        type: "success"
      });
    }
    dlg.visible = false;
    refresh();
    loadMeta();
  } finally {
    dlg.loading = false;
  }
};

/* ---------------- 启用 / 停用 ---------------- */

const toggleEnable = async (row: UserRow, enable: boolean) => {
  const verb = enable ? "启用" : "停用";
  await ElMessageBox.confirm(
    `${verb}用户「${row.username}」的同时，会${verb}他名下的<b>全部广播任务</b>。是否继续？`,
    `${verb}用户`,
    { type: "warning", dangerouslyUseHTMLString: true, confirmButtonText: `确定${verb}` }
  );
  const { data } = await setUserEnableApi(row.id, enable);
  ElNotification({
    title: `${verb}成功`,
    message: data?.affectedTasks ? `已同步${verb} ${data.affectedTasks} 条任务` : "该用户名下没有任务",
    type: "success"
  });
  refresh();
};

/* ---------------- 删除 ---------------- */

const del = reactive<{
  visible: boolean;
  ids: number[];
  impact?: CascadeImpact;
  loading: boolean;
}>({ visible: false, ids: [], loading: false });

// ProTable 的 selectedListIds 是 string[]，统一转数字
const openDelete = async (rawIds: (string | number)[]) => {
  const ids = (rawIds ?? []).map(Number).filter(n => Number.isFinite(n) && n > 0);
  if (!ids.length) return ElMessage.warning("请先勾选要删除的用户");
  const { data } = await previewDeleteUserApi(ids);
  Object.assign(del, { visible: true, ids, impact: data, loading: false });
};

const confirmDelete = async () => {
  del.loading = true;
  try {
    const { data } = await deleteUsersApi(del.ids);
    ElNotification({
      title: "删除完成",
      message: `已删除 ${data?.users ?? 0} 个用户、${data?.tasks ?? 0} 条任务、${data?.media ?? 0} 个媒体`,
      type: "success",
      duration: 6000
    });
    del.visible = false;
    refresh();
    loadMeta();
  } finally {
    del.loading = false;
  }
};

onMounted(loadMeta);
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
.wind-row {
  display: flex;
  gap: 24px;
  align-items: center;
}
.wind-quota {
  font-size: 11px;
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
.opt-group {
  margin-left: 10px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.opt-tag {
  margin-left: 8px;
}
.muted {
  color: var(--el-text-color-secondary);
}
.mr4 {
  margin-right: 4px;
}
.mb12 {
  margin-bottom: 12px;
}
.mt12 {
  margin-top: 12px;
}
</style>
