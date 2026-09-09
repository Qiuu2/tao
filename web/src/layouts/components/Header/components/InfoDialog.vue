<template>
  <el-dialog v-model="dialogVisible" :title='$t("header.personalData")' width="500px" draggable>
    <span>This is userInfo</span>
    <template #footer>
      <span class="dialog-footer">
        <el-button @click="dialogVisible = false">{{ $t("common.cancel") }}</el-button>
        <el-button type="primary" @click="dialogVisible = false">{{ $t("common.confirm2") }}</el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref } from "vue";

const dialogVisible = ref(false);
const openDialog = () => {
  dialogVisible.value = true;
};

defineExpose({ openDialog });
</script>
