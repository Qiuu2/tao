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
