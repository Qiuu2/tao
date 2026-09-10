<!--
  修改密码（旧版 modifypassword.php + UserManager/userpasswordmodify_form.html）

  三个框、maxlength 16，与旧版表单一致。复杂度要求跟着 serverconfig.fuzamima 走：
  非 0 时要求「数字 + 大写 + 小写 + 符号，8 位以上」，为 0 时旧版不做任何要求。

  ⚠ 这里的校验只是**先说清楚要求**，能不能改成由服务端说了算 ——
  旧版三条规则全在浏览器里跑，服务端一条都不查，绕过页面直接发请求空密码也能设进去。
  服务端那边的校验见 server/internal/auth/password.go。
-->
<template>
  <el-dialog v-model="visible" :title='$t("header.changePassword")' width="460px" draggable @closed="reset">
    <!-- label-width 用 auto：英文标签比中文长得多，写死宽度会把 Current password 挤成两行 -->
    <el-form ref="formRef" :model="form" :rules="rules" label-width="auto" @submit.prevent>
      <el-form-item :label='$t("pwd.current")' prop="oldPassword">
        <el-input
          v-model="form.oldPassword"
          type="password"
          show-password
          :maxlength="MAX_LEN"
          :placeholder='$t("pwd.currentPlaceholder")'
        />
      </el-form-item>

      <el-form-item :label='$t("pwd.new")' prop="newPassword">
        <el-input
          v-model="form.newPassword"
          type="password"
          show-password
          :maxlength="MAX_LEN"
          :placeholder='$t("pwd.newPlaceholder")'
        />
      </el-form-item>

      <el-form-item :label='$t("pwd.confirm")' prop="confirmPassword">
        <el-input
          v-model="form.confirmPassword"
          type="password"
          show-password
          :maxlength="MAX_LEN"
          :placeholder='$t("pwd.confirmPlaceholder")'
          @keyup.enter="submit"
        />
      </el-form-item>

      <!-- 放进表单里而不是表单外面：这样它跟着 label-width 自动对齐，
           中英文换来换去都不用调这个数 -->
      <el-form-item label=" ">
        <div class="rule-tip">{{ ruleText }}</div>
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="visible = false">{{ $t("common.cancel") }}</el-button>
      <el-button type="primary" :loading="saving" @click="submit">{{ $t("common.confirm2") }}</el-button>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ElMessage } from "element-plus";
import type { FormInstance, FormRules } from "element-plus";
import { computed, reactive, ref } from "vue";
import { useI18n } from "vue-i18n";

import { changeOwnPasswordApi, getPasswordPolicyApi, type PasswordPolicy } from "@/api/modules/login";

const { t } = useI18n();

/** 与旧版表单的 maxlength 一致。正则里那个 30 够不着，输入框先挡住了 */
const MAX_LEN = 16;

const visible = ref(false);
const saving = ref(false);
const formRef = ref<FormInstance>();
const policy = ref<PasswordPolicy>({ complex: false, minLength: 1, maxLength: MAX_LEN });

const form = reactive({ oldPassword: "", newPassword: "", confirmPassword: "" });

const ruleText = computed(() =>
  policy.value.complex
    ? t("pwd.ruleComplex", { min: policy.value.minLength, max: policy.value.maxLength })
    : t("pwd.ruleSimple", { min: policy.value.minLength, max: policy.value.maxLength })
);

/** 旧版正则的等价拆分：数字 / 大写 / 小写 / 非字母数字，各要有一个 */
const isComplexEnough = (s: string) =>
  /[0-9]/.test(s) && /[A-Z]/.test(s) && /[a-z]/.test(s) && /[^a-zA-Z0-9]/.test(s);

const rules = computed<FormRules>(() => ({
  oldPassword: [{ required: true, message: t("pwd.currentRequired"), trigger: "blur" }],
  newPassword: [
    { required: true, message: t("pwd.newRequired"), trigger: "blur" },
    {
      trigger: "blur",
      validator: (_r: any, v: string, cb: (e?: Error) => void) => {
        if (!v) return cb();
        const n = [...v].length;
        if (n < policy.value.minLength || n > policy.value.maxLength) {
          return cb(new Error(t("pwd.lengthRule", { min: policy.value.minLength, max: policy.value.maxLength })));
        }
        if (policy.value.complex && !isComplexEnough(v)) return cb(new Error(t("pwd.complexRule")));
        if (v === form.oldPassword) return cb(new Error(t("pwd.sameAsOld")));
        cb();
      }
    }
  ],
  confirmPassword: [
    { required: true, message: t("pwd.confirmRequired"), trigger: "blur" },
    {
      trigger: "blur",
      // 旧版那个「再输一次」的框只参与复杂度校验，从头到尾没和新密码比过 ——
      // 两个框填不一样也能改成功，用户会以为自己设的是第二个。
      validator: (_r: any, v: string, cb: (e?: Error) => void) =>
        v && v !== form.newPassword ? cb(new Error(t("pwd.mismatch"))) : cb()
    }
  ]
}));

const reset = () => {
  form.oldPassword = "";
  form.newPassword = "";
  form.confirmPassword = "";
  formRef.value?.clearValidate();
};

const openDialog = async () => {
  reset();
  visible.value = true;
  // 取不到就按最宽松的来：这一句只影响提示文字，拦不拦得住由服务端决定
  try {
    const { data } = await getPasswordPolicyApi();
    if (data) policy.value = data;
  } catch {
    /* 忽略：提示退回默认文案 */
  }
};

const submit = async () => {
  if (!(await formRef.value?.validate().catch(() => false))) return;
  saving.value = true;
  try {
    await changeOwnPasswordApi({ ...form });
    visible.value = false;
    ElMessage.success({ message: `${t("pwd.changedTitle")} — ${t("pwd.changedTip")}`, duration: 4000 });
  } finally {
    saving.value = false;
  }
};

defineExpose({ openDialog });
</script>

<style scoped lang="scss">
.rule-tip {
  font-size: 12px;
  line-height: 1.6;
  color: var(--el-text-color-secondary);
}

/* 校验提示默认是绝对定位的，英文那几句长，会被下一行盖住/裁掉。
   改成占位的，让它把下面的内容顶开。 */
:deep(.el-form-item__error) {
  position: static;
  padding-top: 2px;
  line-height: 1.4;
}
</style>
