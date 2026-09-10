<!--
  声场任务（噪声检测 → 声场任务，旧版 zhaoshentaskmanager.php，tasktype = 25）。

  与终端功放 / 采播管理 / 文字语音 / led播放 是**同一个组件**，差异由 kind 决定：
  声场任务多的是「从声场分区树上挑终端和噪声探头」「每个探头六档噪声值」
  「设置 / 应用默认噪声」这三样，都在 TypedTaskPage 里按 kind 分支。
-->
<template>
  <TypedTaskPage kind="sound" />
</template>

<script setup lang="ts" name="noiseTask">
import TypedTaskPage from "../../typed/TypedTaskPage.vue";
</script>
