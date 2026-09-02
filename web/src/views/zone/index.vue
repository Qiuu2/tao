<!--
  终端分区（旧版 StreamManager）

  一组终端，任务下发时按分区号定位。分区定义在 serverplaystream，
  成员在 terminalofgroup。

  相对旧版的关键修复：
    · 旧版从头到尾没写过 terminal.groupid —— 而终端列表读的是 terminalofgroup、
      终端编辑页写的是 terminal.groupid，三方各说各话。现在四处一起维护。
    · 一台终端只能属于一个分区：加进新分区会先从原分区摘掉，
      否则成员列表会虚报（旧版就是这样）。
    · 删除分区会把任务里的分区号复位成 0（等于全终端播），旧版不提示，
      现在删除前必须先看影响面。
    · 成员「全清再写」放进事务，中途失败不会把分区变空。
-->
<template>
  <div class="table-box">
    <ProTable
      ref="proTableRef"
      :columns="columns"
      :request-api="getZoneListApi"
      :init-param="initParam"
      :data-callback="dataCallback"
      row-key="id"
      @sort-change="onSortChange"
    >
      <template #tableHeader="scope">
        <div class="header-bar">
          <!-- 按钮对齐 :80（页面规格.txt「终端分区」）：添加 / 删除 -->
          <div class="header-left">
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
        <el-tag v-if="scope.row.terminalCount" size="small" effect="plain">{{ scope.row.terminalCount }} 台</el-tag>
        <span v-else class="muted">空分区</span>
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
    <el-dialog v-model="dlg.visible" :title="dlg.title" width="720px" top="6vh">
      <el-form :model="dlg.form" label-width="100px">
        <!-- 表单项与占位符照 :80 的「添加」弹窗：分区名称 / 描述 / 分区终端 -->
        <el-form-item label="分区名称" required>
          <el-input v-model="dlg.form.name" maxlength="60" show-word-limit placeholder="请输入分区名称" />
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="dlg.form.info" maxlength="60" show-word-limit placeholder="请输入描述" />
        </el-form-item>
        <el-form-item label="分区终端">
          <!--
            ⚠ 这一页归组用的是 currentZoneId / currentZoneName（终端当前所属分区），
              不是 groupName —— 这个页面编辑的就是终端分区本身，
              按「当前在哪个分区」摆出来，正好一眼看出要从哪里把终端搬过来。
          -->
          <TerminalTree
            v-model="selectedTerminalIds"
            :terminals="terminals"
            :loading="terminalLoading"
            group-field="currentZoneName"
            group-id-field="currentZoneId"
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
    <el-dialog v-model="del.visible" title="删除终端分区" width="620px">
      <el-alert type="error" :closable="false" show-icon class="mb12">
        删除分区后，成员终端变成<b>未分区</b>；引用了这个分区号的任务会被复位成 0，
        也就是<b>不再按分区限定播放范围</b>。不可恢复。
      </el-alert>

      <el-table v-if="del.preview?.deletable.length" :data="del.preview.deletable" size="small" max-height="280">
        <el-table-column prop="name" label="分区" min-width="140" />
        <el-table-column label="影响面" min-width="300">
          <template #default="{ row }">
            <el-tag v-if="row.impact?.terminals" size="small" class="mr4">成员终端 {{ row.impact?.terminals }} 台</el-tag>
            <el-tag v-if="row.impact?.tasks" type="danger" size="small">
              {{ row.impact?.tasks }} 条任务的分区号将被复位
            </el-tag>
            <span v-if="!row.impact?.terminals && !row.impact?.tasks" class="muted">空分区</span>
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

<script setup lang="tsx" name="zone">
import { Delete, EditPen } from "@element-plus/icons-vue";
import { ElMessage, ElMessageBox, ElNotification } from "element-plus";
import { computed, onMounted, reactive, ref } from "vue";

import TerminalTree from "@/components/TerminalTree/index.vue";
import { invalidateZones } from "@/components/TerminalTree/zones";

import {
  createZoneApi,
  deleteZonesApi,
  getZoneApi,
  getZoneListApi,
  getZoneTerminalsApi,
  previewDeleteZonesApi,
  updateZoneApi
} from "@/api/modules/basecfg";
import type { Zone, ZonePreview, ZoneTerminalOption } from "@/api/modules/basecfg";
import ProTable from "@/components/ProTable/index.vue";
import { useAuthStore } from "@/stores/modules/auth";
import type { ColumnProps, ProTableInstance } from "@/components/ProTable/interface";

const authStore = useAuthStore();
const canEdit = computed(() => !!(authStore.authButtonListGet as any)?.zone?.edit);

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

// 列清单严格照 :80：分类名称 | 分区描述 | 终端列表 | 创建时间 | 操作，无搜索区。
//
// 「任务引用」那一列已按要求去掉。删分区仍会连带把任务里的分区号复位成 0，
// 这个影响面在删除确认弹窗里照旧列出来，不会因为列表上看不见就悄悄发生。
const columns = reactive<ColumnProps<Zone>[]>([
  { type: "selection", fixed: "left", width: 50 },
  { prop: "name", label: "分类名称", minWidth: 200 },
  { prop: "info", label: "分区描述", minWidth: 220, showOverflowTooltip: true },
  { prop: "terminalCount", label: "终端列表", width: 130 },
  { prop: "createTime", label: "创建时间", width: 180 },
  { prop: "operation", label: "操作", fixed: "right", width: 140 }
]);

const dataCallback = (data: any) => {
  scopeNote.value = data.scopeNote ?? "";
  return { list: data.list, total: data.total, pageNum: data.pageNum, pageSize: data.pageSize };
};

const refresh = () => proTableRef.value?.getTableList();

/* ---------------- 终端选择 ---------------- */

const terminals = ref<ZoneTerminalOption[]>([]);
const terminalLoading = ref(false);
const selectedTerminalIds = ref<number[]>([]);

const searchTerminals = async (kw: string) => {
  terminalLoading.value = true;
  try {
    const { data } = await getZoneTerminalsApi(kw ?? "");
    terminals.value = data ?? [];
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

const openEdit = async (row: Zone) => {
  const { data } = await getZoneApi(row.id);
  Object.assign(dlg, {
    visible: true,
    saving: false,
    isEdit: true,
    title: `修改终端分区：${data.name}`,
    id: data.id,
    form: { name: data.name, info: data.info }
  });
  // 已被删除的终端不回填，否则保存时会被服务端的存在性校验挡下来
  selectedTerminalIds.value = data.members.filter(m => !m.deleted).map(m => m.terminalId);
  const dropped = data.members.filter(m => m.deleted).length;
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
    terminalIds: selectedTerminalIds.value
  };
  // 把「会从别的分区移走」这件事在保存前说清楚
  const moving = terminals.value.filter(
    t => selectedTerminalIds.value.includes(t.id) && t.currentZoneId > 0 && t.currentZoneId !== dlg.id
  );
  if (moving.length) {
    await ElMessageBox.confirm(
      `有 <b>${moving.length}</b> 台终端当前属于别的分区，保存后会被移过来。确认继续？`,
      "二次确认",
      { type: "warning", dangerouslyUseHTMLString: true, confirmButtonText: "确认" }
    );
  }
  dlg.saving = true;
  try {
    if (dlg.isEdit) {
      await updateZoneApi(dlg.id, payload);
    } else {
      await createZoneApi(payload);
    }
    ElMessage.success("保存成功");
    dlg.visible = false;
    // 分区名/成员变了，全站终端树的分区缓存要作废，否则别的页面还摆着旧分区
    invalidateZones();
    refresh();
  } finally {
    dlg.saving = false;
  }
};

/* ---------------- 删除 ---------------- */

const del = reactive({ visible: false, saving: false, preview: null as ZonePreview | null });

const openDelete = async (raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning("请先勾选终端分区");
  const { data } = await previewDeleteZonesApi(ids);
  del.preview = data;
  del.visible = true;
};

const submitDelete = async () => {
  const ids = del.preview!.deletable.map(d => d.id);
  const tasks = del.preview!.deletable.reduce((n, d) => n + d.impact.tasks, 0);
  if (tasks > 0) {
    await ElMessageBox.confirm(
      `这次删除会把 <b>${tasks}</b> 条任务的分区号复位，这些任务将不再按分区限定播放范围。确认继续？`,
      "二次确认",
      { type: "warning", dangerouslyUseHTMLString: true, confirmButtonText: "确认删除" }
    );
  }
  del.saving = true;
  try {
    const { data } = await deleteZonesApi(ids);
    del.visible = false;
    ElNotification({
      title: "删除完成",
      message: `分区 ${data.deleted.length} 个，${data.resetTerminals} 台终端移出分区，${data.resetTasks} 条任务分区号已复位`,
      type: "success"
    });
    invalidateZones();
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
