<template>
  <el-dropdown trigger="click" @command="changeLanguage">
    <i :class="'iconfont icon-zhongyingwen'" class="toolBar-icon"></i>
    <template #dropdown>
      <el-dropdown-menu>
        <el-dropdown-item
          v-for="item in languageList"
          :key="item.value"
          :command="item.value"
          :disabled="language === item.value"
        >
          {{ item.label }}
        </el-dropdown-item>
      </el-dropdown-menu>
    </template>
  </el-dropdown>
</template>

<script setup lang="ts">
import { computed, nextTick } from "vue";
import { useI18n } from "vue-i18n";

import { LanguageType } from "@/stores/interface";
import { useGlobalStore } from "@/stores/modules/global";

const i18n = useI18n();
const globalStore = useGlobalStore();
const language = computed(() => globalStore.language);

// 语言名一律用**它自己那门语言**写 —— 切语言的人正是看不懂当前这门语言的人，
// 把「简体中文」翻成 Chinese 反而更难找。
// prettier-ignore
const languageList = [
  { label: "简体中文", value: "zh" }, // i18n-ignore
  { label: "English", value: "en" }
];

/*
  切语言之后**整页重载**。

  界面上的文案切一下就变了，但页面里还有一半内容是后端给的：
  终端型号、紧急广播的槽位名、任务状态、以及各种提示语 ——
  这些在**请求发出那一刻**就按 Accept-Language 定好了语言。
  不重载的话，切到 English 会得到一个「菜单是英文、表格里还是中文」的页面，
  而且只有再点一次别的页面才会零零星星变过来，看着像坏了。

  代价是没保存的表单会丢。这是可接受的：用户刚点的是「换语言」，
  整页刷新是这个动作在多数后台里的常态，比一个中英夹杂的界面好解释。
  语言本身存在 localStorage 里，重载后自然是新语言。
*/
const changeLanguage = (lang: string) => {
  if (lang === globalStore.language) return;
  i18n.locale.value = lang;
  globalStore.setGlobalState("language", lang as LanguageType);
  // 等 pinia 把新值写进 localStorage 再重载，否则刷新回来还是旧语言
  nextTick(() => window.location.reload());
};
</script>
