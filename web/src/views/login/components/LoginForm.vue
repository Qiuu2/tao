<template>
  <el-form ref="loginFormRef" :model="loginForm" :rules="loginRules" size="large">
    <el-form-item prop="username">
      <el-input v-model="loginForm.username" placeholder="用户名">
        <template #prefix>
          <el-icon class="el-input__icon">
            <user />
          </el-icon>
        </template>
      </el-input>
    </el-form-item>
    <el-form-item prop="password">
      <el-input v-model="loginForm.password" type="password" placeholder="密码" show-password autocomplete="current-password">
        <template #prefix>
          <el-icon class="el-input__icon">
            <lock />
          </el-icon>
        </template>
      </el-input>
    </el-form-item>
    <el-form-item v-if="captchaRequired" prop="captcha">
      <div class="captcha-row">
        <!--
          ⚠ 这里**不要**再挂 @keyup.enter。
            下面 onMounted 里已经注册了全局 document.onkeydown 处理回车，
            两个都挂的话，在这个框里按一次回车会走 keydown + keyup 两条路，
            于是发出**两个** /api/login 请求。而验证码是一次性的
            （captcha.Verify 无论对错都 delete），第一个请求把它消耗掉、
            又被 axiosCancel 取消，第二个带着同一个 id 上去就成了「验证码错误」——
            明明输的是对的。实测：按回车 2 次请求、点按钮 1 次请求。
        -->
        <el-input v-model="loginForm.captcha" placeholder="验证码" maxlength="10">
          <template #prefix>
            <el-icon class="el-input__icon">
              <picture-filled />
            </el-icon>
          </template>
        </el-input>
        <img
          v-if="captchaImage"
          class="captcha-img"
          :src="captchaImage"
          alt="点击刷新验证码"
          title="点击刷新验证码"
          @click="refreshCaptcha"
        />
      </div>
    </el-form-item>
  </el-form>
  <div class="login-btn">
    <el-button :icon="CircleClose" round size="large" @click="resetForm(loginFormRef)"> 重置 </el-button>
    <el-button :icon="UserFilled" round size="large" type="primary" :loading="loading" @click="login(loginFormRef)">
      登录
    </el-button>
  </div>
</template>

<script setup lang="ts">
import { CircleClose, PictureFilled, UserFilled } from "@element-plus/icons-vue";
import type { ElForm } from "element-plus";
import { ElNotification } from "element-plus";
import { onBeforeUnmount, onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";

import { Login } from "@/api/interface";
import { getCaptchaApi, loginApi } from "@/api/modules/login";
import { HOME_URL } from "@/config";
import { initDynamicRouter } from "@/routers/modules/dynamicRouter";
import { useKeepAliveStore } from "@/stores/modules/keepAlive";
import { useTabsStore } from "@/stores/modules/tabs";
import { useUserStore } from "@/stores/modules/user";

const router = useRouter();
const userStore = useUserStore();
const tabsStore = useTabsStore();
const keepAliveStore = useKeepAliveStore();

type FormInstance = InstanceType<typeof ElForm>;
const loginFormRef = ref<FormInstance>();
const loginRules = reactive({
  username: [{ required: true, message: "请输入用户名", trigger: "blur" }],
  password: [{ required: true, message: "请输入密码", trigger: "blur" }],
  captcha: [{ required: true, message: "请输入验证码", trigger: "blur" }]
});

const loading = ref(false);
// 服务端可通过 auth.captcha_enabled 关闭验证码；取不到图时自动隐藏该输入框
const captchaRequired = ref(true);
const captchaImage = ref("");

const loginForm = reactive<Login.ReqLoginForm>({
  username: "",
  password: "",
  captcha: "",
  captchaId: ""
});

const refreshCaptcha = async () => {
  try {
    const { data } = await getCaptchaApi();
    captchaImage.value = data.image;
    loginForm.captchaId = data.captchaId;
    loginForm.captcha = "";
    captchaRequired.value = true;
  } catch {
    // 验证码接口不可用时降级为不显示，由服务端决定是否拒绝登录
    captchaRequired.value = false;
  }
};

const login = (formEl: FormInstance | undefined) => {
  if (!formEl) return;
  /*
    ⚠ 重入锁必须**同步**加在这里，不能加在 validate 的回调里。
      validate 是异步的：两次触发挨得很近时（比如回车的 keydown/keyup、
      或者手快点了两下），第二次进来时第一次的回调还没执行到 loading = true，
      于是两个请求都发了出去。验证码是一次性的，第一个把它消耗掉，
      第二个就必然报「验证码错误」。
  */
  if (loading.value) return;
  loading.value = true;
  formEl.validate(async valid => {
    if (!valid) {
      loading.value = false;
      return;
    }
    try {
      // 密码以明文提交，由服务端计算 MD5 比对（与旧系统行为一致）
      const { data } = await loginApi({ ...loginForm });
      userStore.setToken(data.access_token);
      userStore.setUserInfo({ ...data.user, name: data.user.username });

      await initDynamicRouter();

      tabsStore.setTabs([]);
      keepAliveStore.setKeepAliveName([]);

      router.push(HOME_URL);
      ElNotification({
        title: "登录成功",
        message: `欢迎回来，${data.user?.username ?? loginForm.username}`,
        type: "success",
        duration: 2500
      });
      if (data.server?.readonly) {
        ElNotification({
          title: "备机模式",
          message: "当前服务器为备份服务器，系统处于只读状态，所有写操作将被拒绝。",
          type: "warning",
          duration: 0
        });
      }
    } catch {
      // 登录失败后验证码已被服务端作废（一次性使用），必须换一张
      await refreshCaptcha();
    } finally {
      loading.value = false;
    }
  });
};

const resetForm = (formEl: FormInstance | undefined) => {
  if (!formEl) return;
  formEl.resetFields();
  refreshCaptcha();
};

onMounted(() => {
  refreshCaptcha();
  document.onkeydown = (e: KeyboardEvent) => {
    if (e.code === "Enter" || e.code === "enter" || e.code === "NumpadEnter") {
      if (loading.value) return;
      login(loginFormRef.value);
    }
  };
});

onBeforeUnmount(() => {
  document.onkeydown = null;
});
</script>

<style scoped lang="scss">
@use "../index";

.captcha-row {
  display: flex;
  gap: 10px;
  align-items: center;
  width: 100%;
}
.captcha-img {
  height: 40px;
  cursor: pointer;
  border: 1px solid var(--el-border-color);
  border-radius: 4px;
}
</style>
