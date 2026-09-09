<!--
  页脚。

  原来是 Geeker-Admin 模板自带的那行英文署名 + 指向模板作者 GitHub 的外链，
  换成产品名 —— 界面上不留英文，也不该在客户机器上链到第三方仓库。
-->
<template>
  <div class="footer flx-center">
    <span>{{ title }}</span>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

import { appTitle } from "@/languages";

// 与侧边栏 Logo、登录页大标题、标签页标题同源 —— 四处各写一份的话，
// 改产品名迟早漏掉其中一处
const title = computed(() => appTitle());
</script>

<style scoped lang="scss">
@use "./index";
</style>
