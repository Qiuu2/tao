<!--
  注册服务（旧版 regist_server.php + smarty/templates/regist_server.html）

  # 这一页做什么

  一台服务器出厂后要用注册码激活：页面给出**机器码**，用户抄下来发给厂家，
  厂家算出**注册码**回给用户，填进来点「注 册」；另有一个「试 用」按钮领试用期。

  # 页面元素照旧版一比一

    机器码   只读，取 serverbaseparam.registerserial
    注册码   必填，带红星
    状态行   一行大字 + 试用期时的一行红字提醒
    按钮     注 册 / 试 用

  状态文字与试用天数的算法见 server/internal/register —— 试用期固定 5 天，
  起算日在磁盘上的 serial 文件里，**不在库里**（旧版读 trystartdate 那段是注释掉的）。
  注册这个动作本身是把注册码交给第三方程序 registerserver 去做，后端只认它
  标准输出的第一行（success / expired / failed），成功了才把 registerflag 置 1。

  # 一处排版上的取舍

  旧版把状态文字**塞在注册码输入框里**（蓝字/红字），点一下输入框才清空、才能打字
  （regist_server.html 的 registed_user() + change_input_state()）。这里把它拆成
  「服务器状态」单独一行，注册码框一开始就是空的 —— 文字、颜色、按钮文案都照旧版，
  只是不再让一个输入框兼着当状态栏。

  # ⚠ 与旧版的两处差异（都是安全上的）

   1. 旧版这一页与它的两个动作**完全不校验会话**（regist_server.php 里那段
      session 判断被整段注释掉了）。新版：状态查询公开，机器码与
      「注册 / 试用」要求登录且具备服务器权限。
   2. 旧版是 `$command = "registerserver ".$license_key;` 直接拼进 shell，
      注册码里带 `;` 就能执行任意命令。新版传参不经过 shell。
-->
<template>
  <div class="reg-page" v-loading="loading">
    <el-card shadow="never" class="reg-card">
      <!-- 旧版顶上是一张 user_regist.gif，这里换成同样位置的标题条 -->
      <div class="reg-head">注册服务</div>

      <!-- 旧版那个红色的 demos 行：试用期时显示「服务器还有 N 天到期…」 -->
      <div v-if="st.trialNotice" class="reg-demos">{{ st.trialNotice }}</div>

      <el-form label-width="110px" class="reg-form">
        <el-form-item label="服务器状态">
          <span class="reg-state" :class="{ ok: st.registered }">{{ st.statusText }}</span>
        </el-form-item>

        <el-form-item label="机器码">
          <el-input
            v-model="st.machineCode"
            readonly
            class="reg-input"
            placeholder="（未取到机器码）"
            title="机器码，用户需要把机器码复制下来发到厂家，厂家会算出一个注册码给用户完成注册"
          >
            <template #append>
              <el-button :disabled="!st.machineCode" @click="copyCode">复制</el-button>
            </template>
          </el-input>
          <div class="reg-tip">机器码，用户需要把机器码复制下来发到厂家，厂家会算出一个注册码给用户完成注册。</div>
        </el-form-item>

        <el-form-item label="注册码" required>
          <el-input
            v-model="licenseKey"
            class="reg-input"
            maxlength="128"
            show-word-limit
            placeholder="请输入注册码"
            title="注册码,用户在此输入厂家给的注册码完成注册，如果用户没有注册，则此处会有红色字体提示用户当前状态是适用期还是已过期"
            @focus="err = ''"
          />
          <div v-if="err" class="reg-err">{{ err }}</div>
        </el-form-item>

        <el-form-item label-width="110px">
          <!-- 按钮文案照抄旧版 language/chinese.php：「注 册」中间有个空格，「试用」没有 -->
          <el-button
            type="primary"
            title="用户输入注册码后点击注册，弹出成功后完成注册后即可"
            :loading="submitting"
            @click="submit"
          >
            注 册
          </el-button>
          <el-button :loading="trying" @click="doTrial">试用</el-button>
          <!-- 登录前打开这一页时给一条回登录页的路；登录后侧边栏本来就在，不用这个按钮 -->
          <el-button v-if="standalone" link type="primary" @click="$router.replace('/login')">返回登录</el-button>
        </el-form-item>
      </el-form>

      <div v-if="standalone && st.loginBlocked" class="reg-blocked">服务器当前不能登录 —— 注册成功或领取试用后再回登录页。</div>

      <div class="reg-foot">
        <div v-if="st.serialFileMissing">
          试用起算文件读不到，剩余天数算不出来 —— 到「配置文件 → register.serial_file」核对路径。
        </div>
        <div v-else-if="st.registerflag === REGISTER_FLAG.TRIAL">剩余试用天数：{{ st.trialDaysLeft }} 天（试用期共 5 天）</div>
        <div v-if="st.trialUsed">这台服务器已经领过试用期，不能再领第二次。</div>
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts" name="registerServer">
import { ElMessage, ElMessageBox } from "element-plus";
import { computed, onMounted, reactive, ref } from "vue";
import { useRoute, useRouter } from "vue-router";

import { getRegisterApi, getRegisterStatusApi, REGISTER_FLAG, startTrialApi, submitRegisterApi } from "@/api/modules/register";
import type { RegisterStatus } from "@/api/modules/register";

const blank = (): RegisterStatus => ({
  registerflag: 0,
  statusText: "",
  registered: false,
  machineCode: "",
  trialDaysLeft: 0,
  trialNotice: "",
  trialUsed: false,
  canTrial: false,
  serialFileMissing: false,
  loginBlocked: false
});

const route = useRoute();
const router = useRouter();
/** 登录前那一版走静态路由 /register；登录后走菜单里的 /user/register */
const standalone = computed(() => route.path === "/register");

const st = reactive<RegisterStatus>(blank());
const licenseKey = ref("");
const err = ref("");
const loading = ref(false);
const submitting = ref(false);
const trying = ref(false);

/*
  取状态。登录前那一版要先问一次公开接口：
  服务器一旦注册成功 / 进了试用期，「机器码 + 注册 + 试用」这三个接口就重新
  锁回 serverpriv 之后（后端 regGate），未登录再去要机器码只会拿到 401。
  所以这时候不要它，直接把人送回登录页 —— 注册流程到此为止，本来就该去登录了。
*/
const load = async () => {
  loading.value = true;
  try {
    if (standalone.value) {
      const { data } = await getRegisterStatusApi();
      Object.assign(st, data);
      if (!data.loginBlocked) {
        router.replace("/login");
        return;
      }
    }
    const { data } = await getRegisterApi();
    Object.assign(st, data);
  } finally {
    loading.value = false;
  }
};

const copyCode = async () => {
  try {
    await navigator.clipboard.writeText(st.machineCode);
    ElMessage.success("机器码已复制");
  } catch {
    // 剪贴板在非 https / 无权限时会被浏览器挡下，这时提示手动复制
    ElMessage.warning("浏览器不允许自动复制，请手动选中机器码复制");
  }
};

const submit = async () => {
  err.value = "";
  if (!licenseKey.value.trim()) {
    err.value = "请输入注册码";
    return;
  }
  submitting.value = true;
  try {
    const { data } = await submitRegisterApi(licenseKey.value.trim());
    if (data.ok) {
      ElMessage.success(data.message);
      licenseKey.value = "";
    } else {
      // 旧版这两种失败是 alert 出来的，这里用同样的措辞
      ElMessage.error(data.message);
    }
    await load();
  } catch {
    // 注册程序没装、注册码不合规这类，请求拦截器已经把后端那句话弹出来了
  } finally {
    submitting.value = false;
  }
};

/*
  试用。旧版 settrydo(regist) 在 flag 为 1/2/3/4 时只弹一句提示、不请求后台，
  只有 0（未注册）才真的去领。这里保持同一套判断，措辞也照旧版的 alert。
*/
const doTrial = async () => {
  if (!st.canTrial) {
    ElMessage.warning(st.statusText);
    return;
  }
  try {
    await ElMessageBox.confirm("领取后服务器会重启，试用期共 5 天。确定要领取吗？", "领取试用期", {
      type: "warning",
      confirmButtonText: "确定领取"
    });
  } catch {
    return; // 点了取消
  }
  trying.value = true;
  try {
    await startTrialApi();
    ElMessage.success("您有5天的试用期，请尽快注册");
    await load();
  } catch {
    // 「服务器已试用过」这类，拦截器已经把后端那句话弹出来了
  } finally {
    trying.value = false;
  }
};

onMounted(load);
</script>

<style scoped lang="scss">
.reg-page {
  display: flex;
  justify-content: center;
  padding: 24px 16px;
}
.reg-card {
  width: 100%;
  max-width: 640px;
}
.reg-head {
  padding-bottom: 12px;
  margin-bottom: 16px;
  font-size: 16px;
  font-weight: 600;
  border-bottom: 1px solid var(--el-border-color-lighter);
}
/* 旧版那一行红色的 demos 提示 */
.reg-demos {
  padding: 8px 12px;
  margin-bottom: 16px;
  font-size: 13px;
  line-height: 1.7;
  color: var(--el-color-danger);
  background: var(--el-color-danger-light-9);
  border-radius: 4px;
}
.reg-form {
  max-width: 560px;
}
.reg-input {
  width: 100%;
}
/* 旧版：已注册显示蓝色，其余状态显示红色 */
.reg-state {
  font-weight: 600;
  color: var(--el-color-danger);
  &.ok {
    color: var(--el-color-primary);
  }
}
.reg-tip {
  margin-top: 4px;
  font-size: 12px;
  line-height: 1.7;
  color: var(--el-text-color-secondary);
}
.reg-err {
  margin-top: 2px;
  font-size: 12px;
  line-height: 1.5;
  color: var(--el-color-danger);
}
.reg-blocked {
  padding: 8px 12px;
  margin-bottom: 12px;
  font-size: 13px;
  line-height: 1.7;
  color: var(--el-color-warning);
  background: var(--el-color-warning-light-9);
  border-radius: 4px;
}
.reg-foot {
  padding-top: 12px;
  margin-top: 4px;
  font-size: 12px;
  line-height: 1.9;
  color: var(--el-text-color-secondary);
  border-top: 1px solid var(--el-border-color-lighter);
}
</style>
