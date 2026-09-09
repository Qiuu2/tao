<!--
  开发者密钥（新版新增，旧系统没有这一页）

  给第三方系统（教务、门禁、消防告警）发一把能长期调 /openapi/v1 的钥匙。

  # 为什么不能用界面那套令牌

  界面登录拿到的令牌存在 htweb 的进程内存里，8 小时过期、重启就没。
  人重新登录一下就好，程序不行 —— 服务半夜重启，第二天早上第三方的
  定时广播全哑，而且没人知道。所以要一份落库的、长期的、能单独吊销的凭据。

  # 这一页最要紧的一件事：明文只出现一次

  库里存的是 sha256，服务端自己也拿不回明文。所以新建后必须**当场**
  让用户抄走 —— 弹窗不给「稍后再说」，只给「我已经保存好了」。
  抄丢了只能删掉重发一把，这一点在弹窗里说清楚，别让人事后来问。

  # 停用 vs 删除

  两个都留着，因为它们不是一回事：
    停用 —— 出了事先按停，查清楚再决定删不删。之后还能查到这把是谁的、谁发的。
    删除 —— 台账一起没了。只在确认这把钥匙彻底不用了的时候删。
  默认引导到停用（列表里它是普通按钮，删除是危险色 + 二次确认）。
-->
<template>
  <div class="table-box">
    <div class="card key-page">
      <div class="header-bar">
        <div class="header-left">
          <el-button type="primary" :icon="Plus" :disabled="!canAdd" @click="openCreate">{{ $t("keys.newKey") }}</el-button>
          <el-button :icon="Refresh" @click="load">{{ $t("common.refresh") }}</el-button>
        </div>
        <div class="header-right">
          <el-tag type="info" size="small" effect="plain">
            {{ $t("keys.rightsNote") }}
          </el-tag>
        </div>
      </div>

      <el-table :data="rows" v-loading="loading" :empty-text='$t("keys.noKeysYet")' class="mt8">
        <el-table-column prop="name" :label='$t("common.name")' min-width="160" show-overflow-tooltip />
        <!-- ⚠ nowrap：这一串不能折行。折了之后最后一个 • 会掉到第二行，
             看起来像是显示坏了，而它其实只是遮住的部分。 -->
        <el-table-column :label='$t("keys.key")' width="210">
          <template #default="{ row }">
            <span class="mono nowrap">hb_{{ row.prefix }}_••••••••</span>
          </template>
        </el-table-column>
        <el-table-column prop="userName" :label='$t("keys.owner")' width="140" show-overflow-tooltip />
        <el-table-column :label='$t("common.status")' width="110">
          <template #default="{ row }">
            <el-tag v-if="expired(row)" type="danger" size="small" effect="plain">{{ $t("keys.expired") }}</el-tag>
            <el-tag v-else-if="row.enabled" type="success" size="small" effect="plain">{{ $t("common.enable") }}</el-tag>
            <el-tag v-else type="info" size="small" effect="plain">{{ $t("keys.disabled") }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column :label='$t("keys.expiry")' width="170">
          <template #default="{ row }">
            <span v-if="row.expiretime">{{ row.expiretime }}</span>
            <span v-else class="muted">{{ $t("keys.neverExpires") }}</span>
          </template>
        </el-table-column>
        <el-table-column :label='$t("keys.lastUsed")' min-width="230">
          <template #default="{ row }">
            <template v-if="row.lastusedtime">
              {{ row.lastusedtime }}
              <span class="muted"> · {{ row.lastusedip || $t("sys.unknownSource") }}</span>
            </template>
            <span v-else class="muted">{{ $t("keys.neverUsed") }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="createtime" :label='$t("common.createTime")' width="170" />
        <el-table-column :label='$t("common.operation")' fixed="right" width="170">
          <template #default="{ row }">
            <el-button type="primary" link :disabled="!canControl" @click="toggle(row)">
              {{ row.enabled ? $t("common.disable") : $t("common.enable") }}
            </el-button>
            <el-button type="danger" link :icon="Delete" :disabled="!canDelete" @click="remove(row)">{{ $t("common.delete") }}</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="tip">
        {{ $t("keys.callPrefix") }} <code class="mono">{{ $t("keys.headerSample") }}</code>{{ $t("keys.pathPrefix") }} <code class="mono">/openapi/v1</code>{{ $t("keys.pathSuffix") }}
      </div>
    </div>

    <!-- 新建 -->
    <el-dialog v-model="dlg.visible" :title='$t("keys.newKeyTitle")' width="560px">
      <el-form :model="form" label-width="96px">
        <el-form-item :label='$t("common.name")' required>
          <el-input v-model="form.name" :placeholder='$t("keys.namePlaceholder")' maxlength="32" show-word-limit />
          <div v-if="err.name" class="err">{{ err.name }}</div>
        </el-form-item>
        <el-form-item :label='$t("keys.owner")' required>
          <!-- 候选名单来自后端，与「谁能发给谁」的判断同一条规则 ——
               能选的就一定能提交成功。停用的账号后端已经过滤掉了：
               给它发的密钥永远调不通（认证时因账号停用回 401，
               对外还是恒定那句「密钥无效」，对接方查不出原因）。 -->
          <el-select v-model="form.userId" filterable :placeholder='$t("common.pleaseSelect")' class="fill">
            <el-option v-for="u in accounts" :key="u.id" :label="u.username" :value="u.id">
              <span>{{ u.username }}</span>
              <span class="opt-group">{{ u.groupName }}</span>
            </el-option>
          </el-select>
          <div class="tip inline">{{ $t("keys.sameAsUi") }}</div>
          <el-alert
            v-if="pickedIsAdmin"
            type="warning"
            :closable="false"
            class="mt6"
            :title='$t("keys.adminWarnTitle")'
            :description='$t("keys.adminWarnDesc")'
          />
          <div v-if="err.userId" class="err">{{ err.userId }}</div>
        </el-form-item>
        <el-form-item :label='$t("keys.expiryTime")'>
          <el-date-picker
            v-model="form.expiretime"
            type="date"
            value-format="YYYY-MM-DD"
            :placeholder='$t("keys.expiryPlaceholder")'
            class="fill"
          />
          <div class="tip inline">{{ $t("keys.expiryHint") }}</div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dlg.visible = false">{{ $t("common.cancel") }}</el-button>
        <el-button type="primary" :loading="dlg.saving" @click="submit">{{ $t("keys.generate") }}</el-button>
      </template>
    </el-dialog>

    <!-- 明文密钥。只有这一次能看到 -->
    <el-dialog v-model="secretDlg.visible" :title='$t("keys.saveNowTitle")' width="620px" :close-on-click-modal="false" :show-close="false">
      <el-alert
        type="warning"
        :closable="false"
        :title='$t("keys.onlyChanceTitle")'
        :description='$t("keys.onlyChanceDesc")'
      />
      <div class="secret-box mt8">
        <span class="mono secret">{{ secretDlg.secret }}</span>
        <el-button type="primary" :icon="CopyDocument" @click="copySecret">{{ $t("common.copy") }}</el-button>
      </div>
      <div class="tip">{{ $t("keys.usagePrefix") }} <code class="mono">X-API-Key: {{ secretDlg.secret }}</code></div>
      <template #footer>
        <el-button @click="goConsole">{{ $t("keys.tryIt") }}</el-button>
        <el-button type="primary" @click="secretDlg.visible = false">{{ $t("keys.savedIt") }}</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="openapiKeys">
import { useI18n } from "vue-i18n";
import { CopyDocument, Delete, Plus, Refresh } from "@element-plus/icons-vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { computed, onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";

import {
  createApiKeyApi,
  deleteApiKeyApi,
  getApiKeyAccountsApi,
  getApiKeyListApi,
  setApiKeyStateApi
} from "@/api/modules/openapikey";
import type { ApiKeyAccount, ApiKeyItem } from "@/api/modules/openapikey";
import { useAuthStore } from "@/stores/modules/auth";

// 脚本里拼的文案用 t()；模板里的 $t 不用引入
const { t } = useI18n();

const router = useRouter();
const authStore = useAuthStore();
const btn = computed(() => (authStore.authButtonListGet as any)?.openapikey ?? {});
const canAdd = computed(() => !!btn.value.add);
const canControl = computed(() => !!btn.value.control);
const canDelete = computed(() => !!btn.value.delete);

const rows = ref<ApiKeyItem[]>([]);
const loading = ref(false);
const accounts = ref<ApiKeyAccount[]>([]);

/** 到期时间过了就当失效。后端认证时也会拒，这里只是让列表一眼看得出来。 */
const expired = (row: ApiKeyItem) => !!row.expiretime && row.expiretime < new Date().toISOString().slice(0, 19).replace("T", " ");

const load = async () => {
  loading.value = true;
  try {
    const { data } = await getApiKeyListApi();
    rows.value = data ?? [];
  } finally {
    loading.value = false;
  }
};

const loadAccounts = async () => {
  const { data } = await getApiKeyAccountsApi();
  accounts.value = data ?? [];
};

/** 选中的是不是管理员账号 —— 是的话要警示一句。 */
const pickedIsAdmin = computed(() => accounts.value.find(a => a.id === form.userId)?.isAdmin ?? false);

const dlg = reactive({ visible: false, saving: false });
const secretDlg = reactive({ visible: false, secret: "" });
const form = reactive({ name: "", userId: 0 as number, expiretime: "" });
const err = reactive({ name: "", userId: "" });
const clearErr = () => Object.keys(err).forEach(k => ((err as any)[k] = ""));

const openCreate = () => {
  clearErr();
  form.name = "";
  form.userId = 0;
  form.expiretime = "";
  dlg.visible = true;
  if (!accounts.value.length) loadAccounts();
};

const submit = async () => {
  clearErr();
  if (!form.name.trim()) err.name = t("keys.nameRequired");
  if (!form.userId) err.userId = t("keys.ownerRequired");
  if (err.name || err.userId) return;

  dlg.saving = true;
  try {
    const { data } = await createApiKeyApi({
      name: form.name.trim(),
      userId: form.userId,
      expiretime: form.expiretime || undefined
    });
    dlg.visible = false;
    // 明文只在这一次的响应里。立刻弹出来让人抄走。
    secretDlg.secret = data?.secret ?? "";
    secretDlg.visible = true;
    // 顺手放进 sessionStorage，让「接口调用平台」那一页能自动带上，
    // 省得刚发完密钥的人再翻一遍剪贴板。
    // ⚠ 只在这个浏览器标签的生命周期内，关掉就没 —— 不落 localStorage：
    //   密钥长期躺在硬盘上，比让人多粘一次糟得多。
    try {
      sessionStorage.setItem("openapi.lastSecret", secretDlg.secret);
    } catch {
      // 隐私模式下会抛。带不过去就让人手动粘，不是错误。
    }
    load();
  } finally {
    dlg.saving = false;
  }
};

const goConsole = () => {
  secretDlg.visible = false;
  router.push("/dev/console");
};

const copySecret = async () => {
  try {
    await navigator.clipboard.writeText(secretDlg.secret);
    ElMessage.success(t("keys.copiedToClipboard"));
  } catch {
    // 非 https 或浏览器不给权限时 clipboard 用不了。
    // 不报错吓人 —— 密钥就在上面摆着，选中它照样能抄。
    ElMessage.warning(t("keys.copyNotAllowed"));
  }
};

const toggle = async (row: ApiKeyItem) => {
  await setApiKeyStateApi(row.id, !row.enabled);
  ElMessage.success(row.enabled ? t("keys.disabledNow") : t("keys.enabled"));
  load();
};

const remove = async (row: ApiKeyItem) => {
  await ElMessageBox.confirm(
    t("keys.deleteWarn", { name: row.name }) + "\n" + t("keys.deleteWarnTail"),
    t("keys.confirmDelete"),
    { type: "warning", confirmButtonText: t("common.confirmDelete"), cancelButtonText: t("common.cancel") }
  );
  await deleteApiKeyApi(row.id);
  ElMessage.success(t("keys.deleted"));
  load();
};

onMounted(load);
</script>

<style scoped lang="scss">
.key-page {
  padding: 16px;
}
.header-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.mt8 {
  margin-top: 8px;
}
.fill {
  width: 100%;
}
.mono {
  font-family: Menlo, Consolas, "Courier New", monospace;
}
.nowrap {
  white-space: nowrap;
}
.muted {
  color: var(--el-text-color-secondary);
}
.opt-group {
  margin-left: 12px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.mt6 {
  margin-top: 6px;
}
.err {
  margin-top: 4px;
  font-size: 12px;
  color: var(--el-color-danger);
}
.tip {
  margin-top: 10px;
  font-size: 12px;
  line-height: 1.7;
  color: var(--el-text-color-secondary);

  &.inline {
    margin-top: 2px;
  }
}
.secret-box {
  display: flex;
  gap: 10px;
  align-items: center;
  padding: 12px;
  background: var(--el-fill-color-light);
  border-radius: 6px;

  .secret {
    flex: 1;
    font-size: 15px;
    word-break: break-all;
  }
}
</style>
