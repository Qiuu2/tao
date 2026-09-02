import { createI18n } from "vue-i18n";

import en from "./modules/en";
import zh from "./modules/zh";

const i18n = createI18n({
  // Use Composition API, Set to false
  allowComposition: true,
  legacy: false,
  /*
    ⚠ 固定中文，不再用 getBrowserLang() 去猜。
      猜出来的是浏览器语言，这台机器上是 en-US，一进来界面就是英文的。
      这是个中文产品，App.vue 里的 Element Plus locale 也一并钉成了 zh-cn。
  */
  locale: "zh",
  messages: {
    zh,
    en
  }
});

export default i18n;
