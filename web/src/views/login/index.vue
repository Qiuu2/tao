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
        <LoginForm />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts" name="login">
import SwitchDark from "@/components/SwitchDark/index.vue";

import LoginForm from "./components/LoginForm.vue";

// 与侧边栏 Logo、浏览器标签页标题同源，改产品名只改 .env 一处
const appTitle = import.meta.env.VITE_GLOB_APP_TITLE;
</script>

<style scoped lang="scss">
@use "./index";
</style>
