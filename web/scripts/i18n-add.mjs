/**
 * 把一批词条合进 zh.ts / en.ts。
 *
 * 输入形如：
 *   { "terminal": { "title": ["终端管理", "Terminals"], ... } }
 * 即「命名空间 → 键 → [中文, 英文]」。
 *
 * 为什么要这个脚本：一个页面动辄上百条，手工往两份字典里各贴一遍，
 * 迟早贴串、贴漏 —— 而**漏在 en.ts 里不会报错**，只是那一条在英文界面上
 * 显示中文。check-i18n 能查出来，但事后查不如根本不发生。
 *
 * 已存在的键会被跳过并列出来 —— 同一个键配两次，改的时候必然只改一处。
 *
 * 用法：node scripts/i18n-add.mjs <词条.json>
 */
import { readFileSync, writeFileSync } from "fs";

const file = process.argv[2];
if (!file) {
  console.error("用法: node scripts/i18n-add.mjs <词条.json>");
  process.exit(2);
}
const groups = JSON.parse(readFileSync(file, "utf8"));

// 换行、制表符这些控制字符必须转义 —— 直接写进 .ts 的字符串字面量里
// 会把那一行截断，整份字典从此语法错误（已经这样坏过一次）。
const esc = s =>
  s
    .replace(/\\/g, "\\\\")
    .replace(/"/g, '\\"')
    .replace(/\n/g, "\\n")
    .replace(/\r/g, "\\r")
    .replace(/\t/g, "\\t");

for (const [lang, idx] of [
  ["zh", 0],
  ["en", 1]
]) {
  const path = new URL(`../src/languages/modules/${lang}.ts`, import.meta.url).pathname;
  let src = readFileSync(path, "utf8");
  const skipped = [];

  for (const [ns, entries] of Object.entries(groups)) {
    // 只在**这个命名空间自己的块**里找重复。
    //
    // ⚠ 原来用的是 `ns: {[\s\S]*?key:` 这种跨行懒匹配，它会一路越过
    // 本命名空间的右括号，在后面别的命名空间里撞上同名键 —— 于是把一个
    // 其实不存在的键报成「已存在」而跳过。跳过之后**两份字典里都没有它**，
    // 页面上那一处就永远是中文，而且不报错。已经这样丢过 3 个键。
    const block = new RegExp(`\\n  ${ns}: \\{([\\s\\S]*?)\\n  \\},`).exec(src)?.[1] ?? "";
    const lines = [];
    for (const [key, pair] of Object.entries(entries)) {
      if (new RegExp(`^\\s*${key}:`, "m").test(block)) {
        skipped.push(`${ns}.${key}`);
        continue;
      }
      lines.push(`    ${key}: "${esc(pair[idx])}",`);
    }
    if (!lines.length) continue;

    const head = new RegExp(`(\\n  ${ns}: \\{\\n)`);
    if (head.test(src)) {
      src = src.replace(head, `$1${lines.join("\n")}\n`);
    } else {
      // 新命名空间插在 menu 之前，保持字典大致有序
      src = src.replace("\n  menu: {", `\n  ${ns}: {\n${lines.join("\n")}\n  },\n  menu: {`);
    }
  }
  writeFileSync(path, src);
  if (lang === "zh" && skipped.length) {
    console.log(`跳过已存在的 ${skipped.length} 个键：${skipped.slice(0, 8).join(", ")}${skipped.length > 8 ? " …" : ""}`);
  }
}
console.log("词条已合入 zh.ts / en.ts");
