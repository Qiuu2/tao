<template>
  <el-dialog v-model="dialogVisible" :title='$t("upload.bulkAdd", { what: parameter.title })' :destroy-on-close="true" width="580px" draggable>
    <el-form class="drawer-multiColumn-form" label-width="100px">
      <el-form-item :label='$t("upload.templateDownload")'>
        <el-button type="primary" :icon="Download" @click="downloadTemp">{{ $t("upload.clickToDownload") }}</el-button>
      </el-form-item>
      <el-form-item :label='$t("upload.fileUpload")'>
        <el-upload
          action="#"
          class="upload"
          :drag="true"
          :limit="excelLimit"
          :multiple="true"
          :show-file-list="true"
          :http-request="uploadExcel"
          :before-upload="beforeExcelUpload"
          :on-exceed="handleExceed"
          :on-success="excelUploadSuccess"
          :on-error="excelUploadError"
          :accept="parameter.fileType!.join(',')"
        >
          <slot name="empty">
            <el-icon class="el-icon--upload">
              <upload-filled />
            </el-icon>
            <div class="el-upload__text">{{ $t("upload.dragHere") }}<em>{{ $t("upload.clickToUpload") }}</em></div>
          </slot>
          <template #tip>
            <slot name="tip">
              <div class="el-upload__tip">请上传 .xls , .xlsx 标准格式文件，文件最大为 {{ parameter.fileSize }}M</div>
            </slot>
          </template>
        </el-upload>
      </el-form-item>
      <el-form-item :label='$t("upload.overwrite")'>
        <el-switch v-model="isCover" />
      </el-form-item>
    </el-form>
  </el-dialog>
</template>

<script setup lang="ts" name="ImportExcel">
import { useI18n } from "vue-i18n";
import { Download } from "@element-plus/icons-vue";
import { ElNotification, UploadRawFile, UploadRequestOptions } from "element-plus";
import { ref } from "vue";

import { useDownload } from "@/hooks/useDownload";

export interface ExcelParameterProps {
  title: string; // 标题
  fileSize?: number; // 上传文件的大小
  fileType?: File.ExcelMimeType[]; // 上传文件的类型
  tempApi?: (params: any) => Promise<any>; // 下载模板的Api
  importApi?: (params: any) => Promise<any>; // 批量导入的Api
  getTableList?: () => void; // 获取表格数据的Api
}

// 是否覆盖数据
// 脚本里拼的文案用 t()；模板里的 $t 不用引入
const { t } = useI18n();

const isCover = ref(false);
// 最大文件上传数
const excelLimit = ref(1);
// dialog状态
const dialogVisible = ref(false);
// 父组件传过来的参数
const parameter = ref<ExcelParameterProps>({
  title: "",
  fileSize: 5,
  fileType: ["application/vnd.ms-excel", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"]
});

// 接收父组件参数
const acceptParams = (params: ExcelParameterProps) => {
  parameter.value = { ...parameter.value, ...params };
  dialogVisible.value = true;
};

// Excel 导入模板下载
const downloadTemp = () => {
  if (!parameter.value.tempApi) return;
  useDownload(parameter.value.tempApi, t("upload.templateOf", { what: parameter.value.title }));
};

// 文件上传
const uploadExcel = async (param: UploadRequestOptions) => {
  let excelFormData = new FormData();
  excelFormData.append("file", param.file);
  excelFormData.append("isCover", isCover.value as unknown as Blob);
  await parameter.value.importApi!(excelFormData);
  if (parameter.value.getTableList) parameter.value.getTableList();
  dialogVisible.value = false;
};

/**
 * @description 文件上传之前判断
 * @param file 上传的文件
 * */
const beforeExcelUpload = (file: UploadRawFile) => {
  const isExcel = parameter.value.fileType!.includes(file.type as File.ExcelMimeType);
  const fileSize = file.size / 1024 / 1024 < parameter.value.fileSize!;
  if (!isExcel)
    ElNotification({
      title: t("upload.tip"),
      message: t("upload.onlyExcel"),
      type: "warning"
    });
  if (!fileSize)
    setTimeout(() => {
      ElNotification({
        title: t("upload.tip"),
        message: t("upload.tooLargeMB", { n: parameter.value.fileSize }),
        type: "warning"
      });
    }, 0);
  return isExcel && fileSize;
};

// 文件数超出提示
const handleExceed = () => {
  ElNotification({
    title: t("upload.tip"),
    message: t("upload.onlyOneFile"),
    type: "warning"
  });
};

// 上传错误提示
const excelUploadError = () => {
  ElNotification({
    title: t("upload.tip"),
    message: t("upload.bulkAddFailed", { what: parameter.value.title }),
    type: "error"
  });
};

// 上传成功提示
const excelUploadSuccess = () => {
  ElNotification({
    title: t("upload.tip"),
    message: t("upload.bulkAddOk", { what: parameter.value.title }),
    type: "success"
  });
};

defineExpose({
  acceptParams
});
</script>
<style lang="scss" scoped>
@use "./index";
</style>
