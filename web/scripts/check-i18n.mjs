/**
 * i18n 自检。两件事：
 *
 *  ① zh / en 两份字典的键必须**完全一致**。
 *     缺键不会报错，vue-i18n 会静默回落到中文 —— 表现是英文界面里
 *     突然冒出一句中文，而且只有那一个用户会看见。
 *
 *  ② 数一数还有多少界面文案没接进 i18n。
 *     这是「英文版做到哪儿了」的唯一客观口径，不靠印象。
 *     只数**会显示给人看的**字符串：模板里的文本与属性、脚本里的字符串字面量。
 *     代码注释一概不算 —— 那是写给开发者看的，本来就该是中文。
 *
 * 用法：node scripts/check-i18n.mjs [--list]
 * 键对不上时退出码为 1，可以直接挂进 CI。
 */
import { readFileSync, readdirSync, statSync } from "fs";
import { join } from "path";

const SRC = new URL("../src", import.meta.url).pathname;

function walk(dir, out = []) {
  for (const f of readdirSync(dir)) {
    const p = join(dir, f);
    if (statSync(p).isDirectory()) walk(p, out);
    else if (p.endsWith(".vue") || p.endsWith(".ts")) out.push(p);
  }
  return out;
}

/** 摘掉注释：<!-- -->、块注释、行注释。 */
function stripComments(t) {
  return t
    .replace(/<!--[\s\S]*?-->/g, "")
    .replace(/\/\*[\s\S]*?\*\//g, "")
    .replace(/^\s*\/\/.*$/gm, "");
}

const CJK = /[一-鿿]/;

/** 把一份字典对象拍平成 "a.b.c" 的键集合。 */
function flatten(obj, prefix = "", out = new Set()) {
  for (const [k, v] of Object.entries(obj)) {
    const key = prefix ? `${prefix}.${k}` : k;
    if (v && typeof v === "object") flatten(v, key, out);
    else out.add(key);
  }
  return out;
}

// —— ① 键集合比对 ——
// 字典是 TS 模块，这里只做文本层面的解析，避免为一个自检脚本引入编译链路。
async function loadDict(name) {
  const raw = readFileSync(join(SRC, `languages/modules/${name}.ts`), "utf8");
  const body = raw.slice(raw.indexOf("export default") + "export default".length).trim();
  const json = body
    .replace(/;\s*$/, "")
    .replace(/([{,]\s*)([A-Za-z0-9_$]+)\s*:/g, '$1"$2":')
    .replace(/,(\s*[}\]])/g, "$1");
  return JSON.parse(json);
}

const zh = flatten(await loadDict("zh"));
const en = flatten(await loadDict("en"));
const missingEn = [...zh].filter(k => !en.has(k));
const missingZh = [...en].filter(k => !zh.has(k));

console.log(`字典：zh ${zh.size} 键 / en ${en.size} 键`);
if (missingEn.length) console.log(`  ✗ en 缺 ${missingEn.length} 个键：`, missingEn.slice(0, 20).join(", "));
if (missingZh.length) console.log(`  ✗ zh 缺 ${missingZh.length} 个键：`, missingZh.slice(0, 20).join(", "));
if (!missingEn.length && !missingZh.length) console.log("  ✓ 两边键集合一致");

// —— ② 还没接进 i18n 的界面文案 ——
const files = walk(SRC).filter(p => !p.includes("/languages/"));
const perFile = [];
let total = 0;
const uniq = new Set();
for (const p of files) {
  const t = stripComments(readFileSync(p, "utf8"));
  const hits = [];
  for (const m of t.matchAll(/"([^"\n]*)"|'([^'\n]*)'|`([^`]*)`/g)) {
    const v = (m[1] ?? m[2] ?? m[3] ?? "").trim();
    if (v && CJK.test(v)) hits.push(v);
  }
  for (const m of t.matchAll(/>([^<>{}\n]*)</g)) {
    const v = m[1].trim();
    if (v && CJK.test(v)) hits.push(v);
  }
  if (hits.length) {
    perFile.push([p.replace(SRC + "/", ""), hits.length]);
    total += hits.length;
    hits.forEach(h => uniq.add(h));
  }
}
perFile.sort((a, b) => b[1] - a[1]);
console.log(`\n未接入 i18n 的界面文案：${uniq.size} 条不重复 / ${total} 处 / ${perFile.length} 个文件`);
console.log("剩得最多的文件：");
for (const [p, n] of perFile.slice(0, 15)) console.log(`  ${String(n).padStart(4)}  ${p}`);

if (process.argv.includes("--list")) {
  console.log("\n全部剩余文案：");
  [...uniq].sort().forEach(v => console.log("  " + v));
}

process.exit(missingEn.length || missingZh.length ? 1 : 0);
