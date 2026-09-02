<!--
  文字语音：task.tasktype IN (15,17,19)。

  播报文字存在 ttssentence，靠 sentenceid = media.id 挂到一条 typeid='tts'
  的虚拟媒体上（磁盘上没有实际文件，由后台 TTS 服务合成）。
-->
<template>
  <TypedTaskPage kind="tts" />
</template>

<script setup lang="ts" name="tts">
import TypedTaskPage from "../TypedTaskPage.vue";
</script>
