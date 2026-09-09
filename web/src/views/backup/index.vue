<!--
  备份与恢复

  这一页和旧版最根本的不同，必须写在最前面：

  **备份包里没有任何 DDL。**

  旧版备份是逐表拼 `DROP TABLE` + `SHOW CREATE TABLE` 原文 + `INSERT`，
  恢复时按 `;` 拆开逐条执行。于是「恢复一个结构不一致的旧包」
  等于把线上表结构改成备份那一刻的结构 —— 这是表结构锁定红线上最大的实际风险。

  新版是纯数据备份：包里只有每张表的行数据。
  恢复 = 在现有表里 DELETE + INSERT，全程不碰表结构，
  而且因为没有 DDL，整个恢复是一个真正能回滚的事务
  （旧版那个事务因为 DROP/CREATE 隐式提交而形同虚设）。

  代价是结构不同的包恢复不了 —— 这正是我们想要的行为。
-->
<template>
  <div class="backup-page">
    <el-alert type="info" :closable="false" class="mb12">
      <template #title>{{ $t("backup.backupContent") }}</template>
    </el-alert>

    <div class="tool-bar">
      <el-input v-model="label" :placeholder="$t('backup.notePlaceholder')" style="width: 240px" maxlength="40" />
      <el-button type="primary" :icon="Plus" :loading="creating" @click="create">{{ $t("backup.backupNow") }}</el-button>

      <!--
        上传备份包：把下载下来的、或者别的机器上备的包传回来。
        ⚠ :auto-upload="false" + 自己发请求 —— el-upload 自带的上传走不了项目的
          axios 拦截器，拿不到 x-access-token，也套不上统一的错误提示。
      -->
      <el-upload :show-file-list="false" :auto-upload="false" accept=".zip" :on-change="onPick" class="up">
        <el-button :icon="Upload" :loading="uploading">{{ $t("backup.uploadPackage") }}</el-button>
      </el-upload>

      <el-button :icon="Refresh" @click="load">{{ $t("common.refresh") }}</el-button>
      <span v-if="list.length" class="summary">共 {{ list.length }} 个备份包</span>
    </div>

    <el-progress v-if="uploading" :percentage="upPercent" :stroke-width="10" class="mb12" />

    <el-table :data="list" v-loading="loading" row-key="name">
      <el-table-column prop="name" :label="$t('backup.package')" min-width="240" show-overflow-tooltip />
      <el-table-column prop="createdAt" :label="$t('backup.generatedAt')" width="170" />
      <el-table-column prop="sizeText" :label="$t('common.size')" width="100" />
      <el-table-column :label="$t('backup.content')" width="200">
        <template #default="{ row }">
          <span v-if="row.manifest">
            {{ row.manifest.tables?.length ?? 0 }} 表 / {{ row.manifest.totalRows }} 行
            <br />
            <span class="muted">媒体 {{ row.manifest.media?.length ?? 0 }} 个 · {{ human(row.manifest.mediaBytes) }}</span>
          </span>
          <span v-else class="muted">—</span>
        </template>
      </el-table-column>
      <el-table-column :label="$t('backup.restorable')" width="180">
        <template #default="{ row }">
          <el-tag v-if="row.compatible" type="success" size="small">{{ $t("backup.structureConsistent") }}</el-tag>
          <el-tooltip v-else :content="row.note || $t('common.notRecoverable')" placement="top">
            <el-tag type="danger" size="small">{{ $t("common.notRecoverable") }}</el-tag>
          </el-tooltip>
        </template>
      </el-table-column>
      <el-table-column prop="manifest.createdBy" :label="$t('backup.operator')" width="110">
        <template #default="{ row }">{{ row.manifest?.createdBy || "—" }}</template>
      </el-table-column>
      <el-table-column :label="$t('common.operation')" width="230" fixed="right">
        <template #default="{ row }">
          <el-button type="primary" link :icon="Download" @click="download(row)">{{ $t("media.download") }}</el-button>
          <el-button type="warning" link :icon="RefreshLeft" :disabled="!row.compatible" @click="openRestore(row)">
            {{ $t("backup.restore") }}
          </el-button>
          <el-button type="danger" link :icon="Delete" @click="remove(row)">{{ $t("common.delete") }}</el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- 恢复 -->
    <el-dialog v-model="rst.visible" :title="$t('backup.restoreTitle')" width="720px" top="5vh">
      <el-alert type="error" :closable="false" class="mb12">
        <template #title>{{ $t("backup.restoreWipes") }}</template>
        <div class="alert-body">{{ $t("backup.irreversible") }}</div>
      </el-alert>

      <el-descriptions v-if="rst.pre" :column="2" border size="small" class="mb12">
        <el-descriptions-item :label="$t('backup.package')">{{ rst.pre.name }}</el-descriptions-item>
        <el-descriptions-item :label="$t('backup.generatedAt')">{{ rst.pre.manifest?.createdAt }}</el-descriptions-item>
        <el-descriptions-item :label="$t('backup.willClear')">{{ rst.pre.willDeleteRows }} 行（当前数据）</el-descriptions-item>
        <el-descriptions-item :label="$t('backup.willWrite')">{{ rst.pre.willInsertRows }} 行（备份数据）</el-descriptions-item>
        <el-descriptions-item :label="$t('taskCommon.mediaFile')">{{ rst.pre.mediaFiles }} 个</el-descriptions-item>
        <el-descriptions-item :label="$t('backup.structureFingerprint')">
          <el-tag v-if="rst.pre.schemaHashSame" type="success" size="small">{{ $t("backup.consistent") }}</el-tag>
          <el-tag v-else type="danger" size="small">{{ $t("backup.inconsistent") }}</el-tag>
        </el-descriptions-item>
      </el-descriptions>

      <el-table v-if="rst.pre?.schemaDiff.length" :data="rst.pre.schemaDiff" size="small" max-height="200" class="mb12">
        <el-table-column prop="table" :label="$t('backup.tables')" width="150" />
        <el-table-column prop="column" :label="$t('backup.columns')" width="140" />
        <el-table-column prop="issue" :label="$t('backup.diff')" width="180" />
        <el-table-column prop="detail" :label="$t('common.description')" min-width="200" />
      </el-table>

      <el-form label-width="120px">
        <el-form-item :label="$t('backup.restoreMedia')">
          <el-switch v-model="rst.restoreMedia" />
        </el-form-item>
        <el-form-item :label="$t('backup.safeBackupFirst')">
          <el-switch v-model="rst.safetyBackup" />
        </el-form-item>
        <el-form-item :label="$t('backup.confirmText')" required>
          <el-input v-model="rst.confirmText" :placeholder="$t('backup.typePackageName')" />
          <span class="form-tip block">
            {{ $t("backup.needInput") }}<code>{{ rst.pre?.name }}</code>
          </span>
        </el-form-item>
      </el-form>

      <el-alert type="warning" :closable="false">
        {{ $t("backup.afterRestore") }}<b>{{ $t("backup.allSessionsGone") }}</b
        >{{ $t("backup.includingYou") }}
      </el-alert>

      <template #footer>
        <el-button @click="rst.visible = false">{{ $t("common.cancel") }}</el-button>
        <el-button type="danger" :loading="rst.busy" :disabled="rst.confirmText !== rst.pre?.name" @click="doRestore">
          {{ $t("backup.confirmRestore") }}
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="backupPage">
import { useI18n } from "vue-i18n";
import { onMounted, reactive, ref } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { Delete, Download, Plus, Refresh, RefreshLeft, Upload } from "@element-plus/icons-vue";
import {
  backupDownloadUrl,
  createBackupApi,
  deleteBackupApi,
  getBackupListApi,
  precheckBackupApi,
  restoreBackupApi,
  uploadBackupApi,
  type BackupItem,
  type BackupPrecheck
} from "@/api/modules/backup";
import { useUserStore } from "@/stores/modules/user";

// 脚本里拼的文案用 t()；模板里的 $t 不用引入
const { t } = useI18n();

const userStore = useUserStore();
const list = ref<BackupItem[]>([]);
const loading = ref(false);
const creating = ref(false);
const label = ref("");

/* ---------------- 上传备份包 ---------------- */

const uploading = ref(false);
const upPercent = ref(0);

/**
 * el-upload 选中文件后的回调（:auto-upload="false"，所以这里自己发请求）。
 *
 * ⚠ 前端只做一道最粗的挡：扩展名和大小。真正的校验在服务端
 *   （必须是能打开的 zip、包内必须有 _manifest.json、格式版本认识、
 *   条目路径不许有 ..），前端这一道只是省一次没意义的长传输。
 */
const onPick = async (uf: any) => {
  const file: File | undefined = uf?.raw;
  if (!file || uploading.value) return;

  if (!/\.zip$/i.test(file.name)) {
    return ElMessage.warning(t("backup.onlyZip"));
  }
  // 与服务端 backup.MaxUploadBytes 保持一致
  const MAX = 512 * 1024 * 1024;
  if (file.size > MAX) {
    return ElMessage.warning(t("backup.tooLarge", { max: human(MAX) }));
  }
  if (file.size === 0) {
    return ElMessage.warning("这个文件是空的");
  }

  uploading.value = true;
  upPercent.value = 0;
  try {
    const { data } = await uploadBackupApi(file, p => (upPercent.value = p));
    ElMessage.success(
      data.renamed ? t("backup.uploadedRenamed", { name: data.name }) : t("backup.uploaded", { name: data.name })
    );
    if (data.item && !data.item.compatible) {
      ElMessage.warning("这个包与当前数据库结构不一致，列表里会标成「不可恢复」");
    }
    await load();
  } finally {
    uploading.value = false;
    upPercent.value = 0;
  }
};

const human = (n: number) => {
  if (n < 1024) return `${n} B`;
  if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`;
  return `${(n / 1024 / 1024).toFixed(1)} MB`;
};

const load = async () => {
  loading.value = true;
  try {
    const { data } = await getBackupListApi();
    list.value = data.list ?? [];
  } finally {
    loading.value = false;
  }
};

const create = async () => {
  creating.value = true;
  try {
    const { data } = await createBackupApi(label.value);
    ElMessage.success(
      t("backup.backupDone", {
        name: data.name,
        rows: data.manifest.totalRows,
        media: data.manifest.media.length,
        elapsed: data.elapsed
      })
    );
    (data.skippedMediaDirs ?? []).forEach(s => ElMessage.info(t("backup.skipped", { n: s })));
    label.value = "";
    load();
  } finally {
    creating.value = false;
  }
};

const download = (row: BackupItem) => {
  // <a href> / window.open 带不上自定义请求头，这个路由额外接受 ?token=
  const token = userStore.token;
  window.open(`${backupDownloadUrl(row.name)}?token=${encodeURIComponent(token)}`, "_blank");
};

const remove = async (row: BackupItem) => {
  await ElMessageBox.confirm(`确定删除备份包「${row.name}」？删除后无法恢复。`, t("common.doubleConfirm"), { type: "warning" });
  await deleteBackupApi(row.name);
  ElMessage.success(t("common.deleted"));
  load();
};

const rst = reactive({
  visible: false,
  busy: false,
  restoreMedia: true,
  safetyBackup: true,
  confirmText: "",
  pre: null as BackupPrecheck | null
});

const openRestore = async (row: BackupItem) => {
  const { data } = await precheckBackupApi(row.name);
  rst.pre = data;
  rst.confirmText = "";
  rst.busy = false;
  rst.restoreMedia = true;
  rst.safetyBackup = true;
  rst.visible = true;
  if (!data.compatible) ElMessage.error(data.recommendation);
};

const doRestore = async () => {
  if (!rst.pre) return;
  await ElMessageBox.confirm("最后确认：这会清空数据库现有全部数据并写入备份内容，不可撤销。", t("backup.dangerous"), {
    type: "error",
    confirmButtonText: t("backup.iConfirmRestore")
  });
  rst.busy = true;
  try {
    const { data } = await restoreBackupApi({
      name: rst.pre.name,
      confirmText: rst.confirmText,
      safetyBackup: rst.safetyBackup,
      restoreMedia: rst.restoreMedia
    });
    rst.visible = false;
    let msg = t("backup.restoreDone", { tables: data.tablesRestored, deleted: data.rowsDeleted, inserted: data.rowsInserted });
    if (data.mediaRestored) msg += `，媒体 ${data.mediaRestored} 个`;
    if (data.safetyBackup) msg += `；安全备份 ${data.safetyBackup}`;
    ElMessage.success(msg);
    if (data.mediaFailed?.length) {
      ElMessage.warning(t("backup.mediaFailed", { n: data.mediaFailed.length, names: data.mediaFailed.join("、") }));
    }
    // 会话已在服务端全部失效，这里直接引导重新登录。
    // 顺带把「后台服务还没加载新数据」这件事讲清楚 ——
    // 让它自动生效的那条报文实测是整机重启，不能替用户按下去。
    const hint = data.backendNeedsRestart ? `\n\n${data.restartHint}` : "";
    await ElMessageBox.alert(`数据已恢复，所有会话已失效，请重新登录。${hint}`, "请重新登录", {
      confirmButtonText: t("backup.goSignIn")
    });
    userStore.setToken("");
    window.location.href = "/#/login";
    window.location.reload();
  } finally {
    rst.busy = false;
  }
};

onMounted(load);
</script>

<style scoped lang="scss">
.backup-page {
  padding: 12px;
}
.tool-bar {
  display: flex;
  gap: 10px;
  align-items: center;
  margin-bottom: 12px;
}
// el-upload 默认是块级的，塞进 flex 工具条里会把按钮挤到下一行
.up {
  display: inline-flex;
  :deep(.el-upload) {
    display: inline-flex;
  }
}
.summary {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.alert-body {
  margin-top: 4px;
  font-size: 13px;
  line-height: 1.6;
}
.muted {
  color: var(--el-text-color-placeholder);
}
.form-tip {
  margin-left: 8px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
  &.block {
    display: block;
    margin-left: 0;
  }
}
.mb12 {
  margin-bottom: 12px;
}
</style>
