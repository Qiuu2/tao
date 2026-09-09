/**
 * 保证一个 .vue 的 <script setup> 里有 `const { t } = useI18n()`。
 *
 * 模板里用 $t 不用引入，但脚本里拼的提示（ElMessage / 校验规则 / 表头数组）
 * 必须用 t()。77 个文件手工加这两行必然会漏，漏了就是编译不过 —— 还好编译不过
 * 至少会当场发现，比运行时才发现强。这个脚本把它变成一件不用记的事。
 */
import { readFileSync, writeFileSync } from "fs";

for (const file of process.argv.slice(2)) {
  let s = readFileSync(file, "utf8");
  if (/\buseI18n\s*\(/.test(s)) {
    console.log(`${file}: 已有 useI18n，跳过`);
    continue;
  }
  const m = s.match(/<script setup[^>]*>\n/);
  if (!m) {
    console.log(`${file}: ⚠ 没有 <script setup>，需要人看一眼`);
    continue;
  }
  const head = m.index + m[0].length;
  s = s.slice(0, head) + 'import { useI18n } from "vue-i18n";\n' + s.slice(head);

  // 插在 import 块之后的第一处声明前
  const body = s.slice(head);
  const decl = body.search(/\n(const |let |function |onMounted|watch)/);
  const at = decl < 0 ? s.length : head + decl + 1;
  s = s.slice(0, at) + "// 脚本里拼的文案用 t()；模板里的 $t 不用引入\nconst { t } = useI18n();\n\n" + s.slice(at);
  writeFileSync(file, s);
  console.log(`${file}: 已注入 useI18n`);
}
