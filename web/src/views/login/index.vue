<template>
  <div class="login-container flx-center">
    <div class="login-box">
      <SwitchDark class="dark" />
      <div class="login-left">
        <img class="login-left-img" src="@/assets/images/login_left.png" alt="login" />
      </div>
      <div class="login-form">
        <div class="login-logo">
          <img class="login-icon" src="@/assets/images/logo.svg" alt="" />
          <!-- 产品名统一取 .env 的 VITE_GLOB_APP_TITLE，别再写死模板名 -->
          <h2 class="logo-text">{{ appTitle }}</h2>
        </div>
        <!--
          注册状态提示。旧版 login.php 在渲染登录页之前先查 registerflag，
          为 0 时直接 `window.location='./regist_server.php'`。
          新版同样：登不进去的状态（flag 不是 1/2）直接跳到独立的注册页；
          试用期这类还能登录的状态只在这里提示一行。
        -->
        <el-alert v-if="regNotice" :title="regNotice" type="warning" :closable="false" show-icon class="login-reg-tip" />
        <LoginForm />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts" name="login">
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";

import { getRegisterStatusApi } from "@/api/modules/register";
import SwitchDark from "@/components/SwitchDark/index.vue";

import LoginForm from "./components/LoginForm.vue";

// 与侧边栏 Logo、浏览器标签页标题同源，改产品名只改 .env 一处
const appTitle = import.meta.env.VITE_GLOB_APP_TITLE;

/*
  注册状态。这个接口是公开的（登录之前就要用），只回状态与剩余天数，不含机器码。
  查不到就当没有这回事 —— 注册状态查不到不该拦住人登录。
*/
const router = useRouter();
const regNotice = ref("");
onMounted(async () => {
  try {
    const { data } = await getRegisterStatusApi();
    // 登不进去的状态（未注册 / 标准版 / 已过期）直接跳注册页，与旧版 login.php 一致
    if (data.loginBlocked) {
      router.replace("/register");
      return;
    }
    // 还能登录、但在试用期：把那行红字原样提示出来
    if (data.trialNotice) regNotice.value = data.trialNotice;
  } catch {
    regNotice.value = "";
  }
});
</script>

<style scoped lang="scss">
@use "./index";

.login-reg-tip {
  margin-bottom: 14px;
}
</style>
