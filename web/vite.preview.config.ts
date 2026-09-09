// 只用于生成静态预览：把整个应用打成单个 app.js + app.css，便于内联进一个 HTML。
//
// ⚠ 必须放在 web/ 下：放到别处再 import 基础配置，vite 会把两份配置一起打包，
//   打包后 `import vite from "vite"` 变成 CJS interop 的 default，
//   基础配置里的 `loadEnv(...)` 就成了 "not a function"。
import { resolve } from "path";
import { defineConfig, mergeConfig } from "vite";

import base from "./vite.config";

export default defineConfig(async env => {
  const cfg = await (base as any)(env);
  // 预览产物是**一个自足的 HTML**，没有 service worker 可言。
  // 但基础配置里的 PWA 插件照样会去生成 precache 清单，而单文件打包出来的
  // app.js 超过它默认 2 MiB 的上限时，整个构建就以「资源过大」失败 ——
  // 字典翻倍那次正是这样断的。这里直接把它摘掉。
  cfg.plugins = (cfg.plugins ?? []).flat().filter((p: any) => !p || !String(p.name).startsWith("vite-plugin-pwa"));

  return mergeConfig(cfg, {
    base: "./",
    build: {
      outDir: resolve(__dirname, "dist-preview"),
      emptyOutDir: true,
      cssCodeSplit: false,
      rollupOptions: {
        output: {
          inlineDynamicImports: true,
          entryFileNames: "app.js",
          chunkFileNames: "app.js",
          assetFileNames: (info: any) => (info.name?.endsWith(".css") ? "app.css" : "assets/[name][extname]")
        }
      }
    }
  });
});
