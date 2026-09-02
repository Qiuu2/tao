<!--
  报警分区：一组终端，报警触发时在这组终端上播放

  相对旧版的关键修复：
    · 「某终端属于哪个报警分区」在库里记了两份（terminalofalarmgroup 与
      terminal.firealarmgroup）。旧版维护后者的语句被整段注释掉了，两套数据长期不一致。
      现在两处同时维护，删除分区时把成员的 firealarmgroup 复位为 -1。
    · 删除分区会连带删掉该分区下的全部报警映射 —— 旧版不提示，
      管理员在不知情下就把报警联动配置删没了。现在删除前必须先看影响面。
    · 成员「全删重插」放进事务，中途失败不会把分区变空。
    · 新建改用 LAST_INSERT_ID，不再是 SELECT MAX(id)。
-->
<template>
  <div class="table-box">
    <ProTable
      ref="proTableRef"
      :columns="columns"
      :request-api="getAlarmAreaListApi"
      :init-param="initParam"
      :data-callback="dataCallback"
      row-key="id"
      @sort-change="onSortChange"
    >
      <template #tableHeader="scope">
        <div class="header-bar">
          <div class="header-left">
            <!-- :80 这一页顶部只有「添加」一个按钮，删除放在行内操作里 -->
            <el-button type="primary" :disabled="!canEdit" @click="openCreate">添加</el-button>
            <el-button
              type="danger"
              :disabled="!canEdit || !scope.isSelected"
              @click="openDelete(scope.selectedListIds)"
            >
              删除
            </el-button>
          </div>
          <div class="header-right">
            <el-tag v-if="scopeNote" type="info" size="small" effect="plain">{{ scopeNote }}</el-tag>
          </div>
        </div>
      </template>

      <template #terminalCount="scope">
        <el-tag size="small" effect="plain">{{ scope.row.terminalCount }} 台</el-tag>
        <!-- 「报警映射」列已按严格对齐去掉，但删分区会连带删掉这些映射，
             所以把条数就地标在成员数旁边，删之前仍然看得见。 -->
        <el-tag v-if="scope.row.mappingCount" type="warning" size="small" effect="plain" class="ml6">
          映射 {{ scope.row.mappingCount }}
        </el-tag>
      </template>

      <template #operation="scope">
        <el-button
          type="primary"
          link
          :icon="EditPen"
          :disabled="!canEdit || !scope.row.canModify"
          @click="openEdit(scope.row)"
        >
          修改
        </el-button>
        <el-button
          type="danger"
          link
          :icon="Delete"
          :disabled="!canEdit || !scope.row.canModify"
          @click="openDelete([scope.row.id])"
        >
          删除
        </el-button>
      </template>
    </ProTable>

    <!-- 新建 / 修改分区 -->
    <el-dialog v-model="dlg.visible" :title="dlg.title" width="700px" top="6vh">
      <el-form :model="dlg.form" label-width="100px">
        <!-- 表单项与占位符照 :80 的「添加」弹窗：分区名称 / 描述 / 终端 -->
        <el-form-item label="分区名称" required>
          <el-input v-model="dlg.form.name" maxlength="15" show-word-limit placeholder="请输入分区名称" />
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="dlg.form.info" maxlength="15" show-word-limit placeholder="请输入描述" />
        </el-form-item>
        <el-form-item label="终端">
          <!--
            按**终端分区**（groupName）分组的树。
            ⚠ 这里分组用的不是「报警分区」—— 报警分区正是这个对话框在编辑的东西，
              拿它分组是循环的。终端已属于哪个报警分区，改用名字后缀提示。
          -->
          <TerminalTree
            v-model="selectedTerminalIds"
            :terminals="terminalNodes"
            :loading="terminalLoading"
            @search="searchTerminals"
          />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dlg.visible = false">取消</el-button>
        <el-button type="primary" :loading="dlg.saving" @click="submit">确定</el-button>
      </template>
    </el-dialog>

    <!-- 删除影响面 -->
    <el-dialog v-model="del.visible" title="删除报警分区" width="600px">
      <el-alert type="error" :closable="false" show-icon class="mb12">
        删除分区会<b>一并删除该分区下的全部报警映射</b>，成员终端会被移出分区。不可恢复。
      </el-alert>

      <el-table v-if="del.preview?.deletable.length" :data="del.preview.deletable" size="small" max-height="280">
        <el-table-column prop="name" label="分区" min-width="140" />
        <el-table-column label="影响面" min-width="280">
          <template #default="{ row }">
            <el-tag v-if="row.impact?.terminals" size="small" class="mr4">成员终端 {{ row.impact?.terminals }} 台</el-tag>
            <el-tag v-if="row.impact?.alarmMappings" type="danger" size="small">
              报警映射 {{ row.impact?.alarmMappings }} 条将被删除
            </el-tag>
            <span v-if="!row.impact?.terminals && !row.impact?.alarmMappings" class="muted">空分区</span>
          </template>
        </el-table-column>
      </el-table>

      <el-alert v-if="del.preview?.blocked.length" type="warning" :closable="false" class="mt12">
        以下分区不会被删除：
        <div v-for="b in del.preview.blocked" :key="b.id">· {{ b.name || b.id }}：{{ b.detail }}</div>
      </el-alert>

      <template #footer>
        <el-button @click="del.visible = false">取消</el-button>
        <el-button type="danger" :disabled="!del.preview?.deletable.length" :loading="del.saving" @click="submitDelete">
          确认删除
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="tsx" name="alarmArea">
import { CirclePlus, Delete, EditPen } from "@element-plus/icons-vue";
import { ElMessage, ElMessageBox, ElNotification } from "element-plus";
import { computed, onMounted, reactive, ref } from "vue";

import TerminalTree from "@/components/TerminalTree/index.vue";

import {
  createAlarmAreaApi,
  deleteAlarmAreasApi,
  getAlarmAreaApi,
  getAlarmAreaListApi,
  getAlarmTerminalOptionsApi,
  previewDeleteAlarmAreasApi,
  updateAlarmAreaApi
} from "@/api/modules/alarm";
import type { AlarmArea, AlarmAreaPreview, AlarmTerminalOption } from "@/api/modules/alarm";
import ProTable from "@/components/ProTable/index.vue";
import { useAuthStore } from "@/stores/modules/auth";
import type { ColumnProps, ProTableInstance } from "@/components/ProTable/interface";

const authStore = useAuthStore();
const btn = computed(() => (authStore.authButtonListGet as any)?.alarm ?? {});
const canEdit = computed(() => !!btn.value.area);

const toIds = (raw: (string | number)[]) => (raw ?? []).map(Number).filter(n => Number.isFinite(n) && n > 0);

const proTableRef = ref<ProTableInstance>();
const scopeNote = ref("");
const initParam = reactive({ orderBy: "", order: "" });

const onSortChange = ({ prop, order }: { prop: string; order: string | null }) => {
  if (!order) {
    initParam.orderBy = "";
    initParam.order = "";
    return;
  }
  const map: Record<string, string> = { createTime: "createtime" };
  initParam.orderBy = map[prop] ?? prop;
  initParam.order = order === "ascending" ? "asc" : "desc";
};

// 列清单严格照 :80（docs/image/oktw/页面规格.txt「报警分区」）：
// 分区名称 | 分区描述 | 分区终端 | 创建时间 | 操作，无搜索区。
const columns = reactive<ColumnProps<AlarmArea>[]>([
  { type: "selection", fixed: "left", width: 50 },
  { prop: "name", label: "分区名称", minWidth: 200 },
  { prop: "info", label: "分区描述", minWidth: 220, showOverflowTooltip: true },
  { prop: "terminalCount", label: "分区终端", width: 130 },
  { prop: "createTime", label: "创建时间", width: 180 },
  { prop: "operation", label: "操作", fixed: "right", width: 140 }
]);

const dataCallback = (data: any) => {
  scopeNote.value = data.scopeNote ?? "";
  return { list: data.list, total: data.total, pageNum: data.pageNum, pageSize: data.pageSize };
};

const refresh = () => proTableRef.value?.getTableList();

/* ---------------- 终端选择 ---------------- */

const terminals = ref<AlarmTerminalOption[]>([]);
const terminalLoading = ref(false);
const selectedTerminalIds = ref<number[]>([]);
const terminalGroupOf = reactive<Record<number, number>>({});

/*
  树上要显示「这台终端已经属于别的报警分区」这条信息。
  树组件只认 name / group / netstate 几个字段，所以把提示拼进名字里 ——
  与其给组件加一堆一次性的插槽，不如在这里拼好再传进去。
*/
const terminalNodes = computed(() =>
  terminals.value.map(t => ({
    ...t,
    name:
      t.currentAreaId > 0 && t.currentAreaId !== dlg.id
        ? `${t.name || "终端 " + t.id}（已属于 ${t.currentAreaName || t.currentAreaId}）`
        : t.name || `终端 ${t.id}`
  }))
);

const searchTerminals = async (kw: string) => {
  terminalLoading.value = true;
  try {
    const { data } = await getAlarmTerminalOptionsApi(kw ?? "");
    terminals.value = data ?? [];
    (data ?? []).forEach(t => (terminalGroupOf[t.id] = t.groupId));
  } finally {
    terminalLoading.value = false;
  }
};

/* ---------------- 新建 / 修改 ---------------- */

const dlg = reactive({
  visible: false,
  saving: false,
  isEdit: false,
  title: "",
  id: 0,
  form: { name: "", info: "" }
});

const openCreate = async () => {
  Object.assign(dlg, {
    visible: true,
    saving: false,
    isEdit: false,
    title: "添加",
    id: 0,
    form: { name: "", info: "" }
  });
  selectedTerminalIds.value = [];
  await searchTerminals("");
};

const openEdit = async (row: AlarmArea) => {
  const { data } = await getAlarmAreaApi(row.id);
  Object.assign(dlg, {
    visible: true,
    saving: false,
    isEdit: true,
    title: `修改报警分区：${data.name}`,
    id: data.id,
    form: { name: data.name, info: data.info }
  });
  data.terminals.forEach(t => (terminalGroupOf[t.terminalId] = t.groupId));
  // 已被删除的终端不再回填，否则保存时会被服务端的存在性校验挡下来
  selectedTerminalIds.value = data.terminals.filter(t => !t.deleted).map(t => t.terminalId);
  const dropped = data.terminals.filter(t => t.deleted).length;
  await searchTerminals("");
  if (dropped) {
    ElMessage.warning(`该分区里有 ${dropped} 台终端已被删除，已自动从成员列表中移除`);
  }
};

const submit = async () => {
  if (!dlg.form.name.trim()) return ElMessage.warning("请输入分区名称");
  const payload = {
    name: dlg.form.name.trim(),
    info: dlg.form.info.trim(),
    terminals: selectedTerminalIds.value.map(id => ({ terminalId: id, groupId: terminalGroupOf[id] ?? 0 }))
  };
  dlg.saving = true;
  try {
    if (dlg.isEdit) {
      await updateAlarmAreaApi(dlg.id, payload);
    } else {
      await createAlarmAreaApi(payload);
    }
    ElMessage.success("保存成功");
    dlg.visible = false;
    refresh();
  } finally {
    dlg.saving = false;
  }
};

/* ---------------- 删除 ---------------- */

const del = reactive({ visible: false, saving: false, preview: null as AlarmAreaPreview | null });

const openDelete = async (raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning("请先勾选报警分区");
  const { data } = await previewDeleteAlarmAreasApi(ids);
  del.preview = data;
  del.visible = true;
};

const submitDelete = async () => {
  const ids = del.preview!.deletable.map(d => d.id);
  const mappings = del.preview!.deletable.reduce((n, d) => n + d.impact.alarmMappings, 0);
  if (mappings > 0) {
    await ElMessageBox.confirm(
      `这次删除会连带删掉 <b>${mappings}</b> 条报警映射，对应通道触发时将不再播放任何内容。确认继续？`,
      "二次确认",
      { type: "warning", dangerouslyUseHTMLString: true, confirmButtonText: "确认删除" }
    );
  }
  del.saving = true;
  try {
    const { data } = await deleteAlarmAreasApi(ids);
    del.visible = false;
    ElNotification({
      title: "删除完成",
      message: `分区 ${data.deleted.length} 个、映射 ${data.deletedMappings} 条，${data.resetTerminals} 台终端已移出分区`,
      type: "success"
    });
    refresh();
  } finally {
    del.saving = false;
  }
};

onMounted(() => searchTerminals(""));
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
