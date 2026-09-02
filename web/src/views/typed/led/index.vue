<!--
  led播放：task.tasktype = 24 且 sec_task_id = 0，按 parentid 落在 ledtaskfree 分组里。

  只列 24。类型 30 是「附属在别的任务上的 LED 子任务」，
  删它其实是在改另一条任务的配置，不该出现在这一页。
-->
<template>
  <TypedTaskPage kind="led" />
</template>

<script setup lang="ts" name="ledPlay">
import TypedTaskPage from "../TypedTaskPage.vue";
</script>
