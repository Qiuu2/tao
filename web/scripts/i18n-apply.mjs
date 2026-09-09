/**
 * 按一份 {中文: 键} 的映射，把一个 .vue 里的中文换成 i18n 调用。
 *
 * 三种位置改法不同，所以分开处理；改完再由 vue-tsc + 构建 + 浏览器实际点一遍来兜底。
 *
 *	模板文本   >中文<             → >{{ $t("k") }}<
 *	模板属性   placeholder="中文" → :placeholder='$t("k")'
 *	脚本字面量 "中文"             → t("k")
 *
 * ⚠ 只改**完全相等**的整串，不做子串替换 —— 子串替换会把
 * 「确定」换进「确定要删除吗」的中间，改出一堆看不出来的碎片。
 *
 * ⚠ 注释里的不改：翻掉注释等于把写给维护者的话也一起丢了。
 *
 * 用法：node scripts/i18n-apply.mjs <文件> <映射.json>
 *       映射形如 { "text": {"中文":"key"}, "attr": {...}, "js": {...} }
 */
import { readFileSync, writeFileSync } from "fs";

const [, , file, mapFile] = process.argv;
if (!file || !mapFile) {
  console.error("用法: node scripts/i18n-apply.mjs <文件> <映射.json>");
  process.exit(2);
}
const map = JSON.parse(readFileSync(mapFile, "utf8"));
let src = readFileSync(file, "utf8");

/** 注释区段的下标范围，替换时跳过。 */
function commentRanges(t) {
  const r = [];
  const push = re => {
    for (const m of t.matchAll(re)) r.push([m.index, m.index + m[0].length]);
  };
  push(/<!--[\s\S]*?-->/g);
  push(/\/\*[\s\S]*?\*\//g);
  push(/(^|\n)[ \t]*\/\/[^\n]*/g);
  return r;
}
const inComment = (ranges, i) => ranges.some(([a, b]) => i >= a && i < b);

const esc = s => s.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
let changed = 0;
const misses = [];

function replaceAll(re, fn) {
  const ranges = commentRanges(src);
  const tplEnd = src.lastIndexOf("</template>");
  let out = "";
  let last = 0;
  for (const m of [...src.matchAll(re)]) {
    if (inComment(ranges, m.index)) continue;
    const rep = fn(m, m.index, tplEnd);
    if (rep === null) continue;
    out += src.slice(last, m.index) + rep;
    last = m.index + m[0].length;
    changed++;
  }
  src = out + src.slice(last);
}

// ① 模板文本
for (const [zh, key] of Object.entries(map.text ?? {})) {
  const before = changed;
  // \s* 允许跨行：多行文本节点也要能换掉
  replaceAll(new RegExp(`>(\\s*)${esc(zh)}(\\s*)<`, "g"), (m, i, tplEnd) =>
    i < tplEnd ? `>${m[1]}{{ $t("${key}") }}${m[2]}<` : null
  );
  if (changed === before) misses.push(`text: ${zh}`);
}
// ② 模板属性（已经是 :binding 的跳过）
for (const [spec, key] of Object.entries(map.attr ?? {})) {
  const [attr, zh] = [spec.slice(0, spec.indexOf("=")), spec.slice(spec.indexOf("=") + 1)];
  const before = changed;
  replaceAll(new RegExp(`(\\s)${esc(attr)}="${esc(zh)}"`, "g"), (m, i, tplEnd) =>
    i < tplEnd ? `${m[1]}:${attr}='$t("${key}")'` : null
  );
  if (changed === before) misses.push(`attr: ${spec}`);
}
// ③ 脚本字面量
for (const [zh, key] of Object.entries(map.js ?? {})) {
  const before = changed;
  replaceAll(new RegExp(`(["'\`])${esc(zh)}\\1`, "g"), (m, i, tplEnd) => (i > tplEnd ? `t("${key}")` : null));
  if (changed === before) misses.push(`js: ${zh}`);
}

writeFileSync(file, src);
console.log(`${file}: 替换 ${changed} 处`);
if (misses.length) {
  // 一条都没换上，通常是原文对不上（多了空格、或者其实在注释里）。
  // 静默跳过的话，页面上会留下一句没翻的中文而没人知道。
  console.log(`⚠ 有 ${misses.length} 条没匹配上，需要人看一眼：`);
  misses.forEach(m => console.log("   " + m));
}
