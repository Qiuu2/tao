/**
 * 从一个 .vue / .ts 里把**会显示给人看的**中文字符串挑出来，按出现位置分类。
 *
 * 分类是给下一步的替换用的 —— 模板文本、模板属性、脚本字面量三种的改法不同：
 *
 *	text   >中文<              → >{{ $t("k") }}<
 *	attr   placeholder="中文"  → :placeholder='$t("k")'
 *	js     "中文"（脚本里）     → t("k")
 *
 * 注释一概跳过：那是写给开发者看的，翻了反而丢信息。
 *
 * 用法：node scripts/i18n-extract.mjs <文件路径>
 */
import { readFileSync } from "fs";

const file = process.argv[2];
if (!file) {
  console.error("用法: node scripts/i18n-extract.mjs <文件>");
  process.exit(2);
}
const raw = readFileSync(file, "utf8");
const CJK = /[一-鿿]/;

/** 把注释整段替换成等长空白，保持下标不变，后面按下标判断在哪个区段。 */
function blankComments(t) {
  const blank = m => " ".repeat(m.length);
  return t
    .replace(/<!--[\s\S]*?-->/g, blank)
    .replace(/\/\*[\s\S]*?\*\//g, blank)
    .replace(/(^|\n)([ \t]*\/\/[^\n]*)/g, (m, a, b) => a + " ".repeat(b.length));
}

const clean = blankComments(raw);
const tplStart = clean.indexOf("<template>");
const tplEnd = clean.lastIndexOf("</template>");
const inTemplate = i => tplStart >= 0 && i > tplStart && i < tplEnd;

const out = { text: new Set(), attr: new Set(), js: new Set() };

// 模板文本节点。允许跨行 —— 这类写法很常见，
//   <div class="x">
//     快捷任务
//   </div>
// 要求同一行的话整段都会漏掉，而漏掉的表现是页面上留一句中文。
for (const m of clean.matchAll(/>([^<>]*)</g)) {
  const v = m[1].trim();
  if (v && CJK.test(v) && !v.includes("{{") && inTemplate(m.index)) out.text.add(v);
}
// 属性值
for (const m of clean.matchAll(/\s([a-zA-Z-:@][\w:.-]*)="([^"\n]*)"/g)) {
  const v = m[2].trim();
  if (v && CJK.test(v) && inTemplate(m.index)) out.attr.add(`${m[1]}=${v}`);
}
// 字符串字面量（模板里的属性已经在上面收过，这里只收模板之外的）
for (const m of clean.matchAll(/"([^"\n]*)"|'([^'\n]*)'|`([^`]*)`/g)) {
  const v = (m[1] ?? m[2] ?? m[3] ?? "").trim();
  if (v && CJK.test(v) && !inTemplate(m.index)) out.js.add(v);
}

for (const [k, set] of Object.entries(out)) {
  if (!set.size) continue;
  console.log(`--- ${k} (${set.size}) ---`);
  [...set].sort().forEach(v => console.log(v));
}
