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
          <el-button type="primary" :icon="Plus" :disabled="!canAdd" @click="openCreate">新建密钥</el-button>
          <el-button :icon="Refresh" @click="load">刷新</el-button>
        </div>
        <div class="header-right">
          <el-tag type="info" size="small" effect="plain">
            密钥的权限 = 它归属账号的权限。想给第三方多大权限，就在「用户」页给那个账号配多大。
          </el-tag>
        </div>
      </div>

      <el-table :data="rows" v-loading="loading" empty-text="还没有发过密钥" class="mt8">
        <el-table-column prop="name" label="名称" min-width="160" show-overflow-tooltip />
        <!-- ⚠ nowrap：这一串不能折行。折了之后最后一个 • 会掉到第二行，
             看起来像是显示坏了，而它其实只是遮住的部分。 -->
        <el-table-column label="密钥" width="210">
          <template #default="{ row }">
            <span class="mono nowrap">hb_{{ row.prefix }}_••••••••</span>
          </template>
        </el-table-column>
        <el-table-column prop="userName" label="归属账号" width="140" show-overflow-tooltip />
        <el-table-column label="状态" width="110">
          <template #default="{ row }">
            <el-tag v-if="expired(row)" type="danger" size="small" effect="plain">已过期</el-tag>
            <el-tag v-else-if="row.enabled" type="success" size="small" effect="plain">启用</el-tag>
            <el-tag v-else type="info" size="small" effect="plain">已停用</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="到期" width="170">
          <template #default="{ row }">
            <span v-if="row.expiretime">{{ row.expiretime }}</span>
            <span v-else class="muted">不过期</span>
          </template>
        </el-table-column>
        <el-table-column label="最后使用" min-width="230">
          <template #default="{ row }">
            <template v-if="row.lastusedtime">
              {{ row.lastusedtime }}
              <span class="muted"> · {{ row.lastusedip || "未知来源" }}</span>
            </template>
            <span v-else class="muted">从未调用过</span>
          </template>
        </el-table-column>
        <el-table-column prop="createtime" label="创建时间" width="170" />
        <el-table-column label="操作" fixed="right" width="170">
          <template #default="{ row }">
            <el-button type="primary" link :disabled="!canControl" @click="toggle(row)">
              {{ row.enabled ? "停用" : "启用" }}
            </el-button>
            <el-button type="danger" link :icon="Delete" :disabled="!canDelete" @click="remove(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="tip">
        调用方式：请求头带 <code class="mono">X-API-Key: hb_前缀_密钥</code>，接口路径以 <code class="mono">/openapi/v1</code> 开头。
        媒体、终端、分区、作息方案都可以直接写名字，不必先查编号。
      </div>
    </div>

    <!-- 新建 -->
    <el-dialog v-model="dlg.visible" title="新建开发者密钥" width="560px">
      <el-form :model="form" label-width="96px">
        <el-form-item label="名称" required>
          <el-input v-model="form.name" placeholder="这把钥匙给谁用，比如「教务系统」" maxlength="32" show-word-limit />
          <div v-if="err.name" class="err">{{ err.name }}</div>
        </el-form-item>
        <el-form-item label="归属账号" required>
          <!-- 候选名单来自后端，与「谁能发给谁」的判断同一条规则 ——
               能选的就一定能提交成功。停用的账号后端已经过滤掉了：
               给它发的密钥永远调不通（认证时因账号停用回 401，
               对外还是恒定那句「密钥无效」，对接方查不出原因）。 -->
          <el-select v-model="form.userId" filterable placeholder="请选择" class="fill">
            <el-option v-for="u in accounts" :key="u.id" :label="u.username" :value="u.id">
              <span>{{ u.username }}</span>
              <span class="opt-group">{{ u.groupName }}</span>
            </el-option>
          </el-select>
          <div class="tip inline">这把密钥能做的事，与这个账号在界面上能做的事完全一致。</div>
          <el-alert
            v-if="pickedIsAdmin"
            type="warning"
            :closable="false"
            class="mt6"
            title="这是管理员账号，密钥将拥有全部权限"
            description="给第三方对接时，建议单独建一个账号、只勾它真正需要的权限位，别把管理员借出去。"
          />
          <div v-if="err.userId" class="err">{{ err.userId }}</div>
        </el-form-item>
        <el-form-item label="到期时间">
          <el-date-picker
            v-model="form.expiretime"
            type="date"
            value-format="YYYY-MM-DD"
            placeholder="留空 = 不过期"
            class="fill"
          />
          <div class="tip inline">填了日期的话，到那天结束就失效。</div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dlg.visible = false">取消</el-button>
        <el-button type="primary" :loading="dlg.saving" @click="submit">生成</el-button>
      </template>
    </el-dialog>

    <!-- 明文密钥。只有这一次能看到 -->
    <el-dialog v-model="secretDlg.visible" title="请立刻保存这把密钥" width="620px" :close-on-click-modal="false" :show-close="false">
      <el-alert
        type="warning"
        :closable="false"
        title="这是唯一一次能看到完整密钥的机会"
        description="服务器里只存了它的摘要，关掉这个窗口之后谁也拿不回来。抄丢了只能删掉重发一把。"
      />
      <div class="secret-box mt8">
        <span class="mono secret">{{ secretDlg.secret }}</span>
        <el-button type="primary" :icon="CopyDocument" @click="copySecret">复制</el-button>
      </div>
      <div class="tip">用法：在请求头里带 <code class="mono">X-API-Key: {{ secretDlg.secret }}</code></div>
      <template #footer>
        <el-button @click="goConsole">拿去试一试</el-button>
        <el-button type="primary" @click="secretDlg.visible = false">我已经保存好了</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="openapiKeys">
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
  if (!form.name.trim()) err.name = "请给这把密钥起个名字";
  if (!form.userId) err.userId = "请选择归属账号";
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
    ElMessage.success("已复制到剪贴板");
  } catch {
    // 非 https 或浏览器不给权限时 clipboard 用不了。
    // 不报错吓人 —— 密钥就在上面摆着，选中它照样能抄。
    ElMessage.warning("这个浏览器不允许自动复制，请手动选中上面那串字符");
  }
};

const toggle = async (row: ApiKeyItem) => {
  await setApiKeyStateApi(row.id, !row.enabled);
  ElMessage.success(row.enabled ? "已停用，这把密钥立刻就调不通了" : "已启用");
  load();
};

const remove = async (row: ApiKeyItem) => {
  await ElMessageBox.confirm(
    `删除「${row.name}」之后，用它调接口的系统会立刻收到 401，而且再也查不到这把密钥当时是谁的、什么时候发的。
     只是想先停下来查一查的话，用「停用」。`,
    "确认删除这把密钥？",
    { type: "warning", confirmButtonText: "确认删除", cancelButtonText: "取消" }
  );
  await deleteApiKeyApi(row.id);
  ElMessage.success("已删除");
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
