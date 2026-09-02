// 第四步：把 :80 每个功能页的「列名 / 按钮 / 搜索区 / 页签 / 左侧树」抽成文字规格表。
// 比人眼读截图准，也省事。
import { launch, attach, sleep } from "./cdp.mjs";
import { writeFileSync } from "node:fs";

const SP = "C:\\Users\\Administrator\\AppData\\Local\\Temp\\claude\\c--hangtian-software----newweb\\bc0da2fe-d1ba-4031-a82d-ff0866f5dd9d\\scratchpad";
const PROFILE = SP + "\\edgeprofile";

const PAGES = [
  ["首页", "/index"], ["服务器信息", "/home/home"], ["时间设置", "/home/time"],
  ["备份还原", "/home/backup"], ["节假日管理", "/home/holiday"],
  ["终端管理", "/terminal/list"], ["终端分区", "/terminal/cate"], ["文件管理", "/terminal/media"],
  ["报警分区", "/terminal/alarm-zone"], ["报警映射", "/terminal/alarm-map"],
  ["遥控任务", "/terminal/remote-task"], ["作息方案", "/task/rest-plan"],
  ["文件广播", "/task/file"], ["终端功放", "/task/power"], ["采播管理", "/task/broadcast"],
  ["文字语音", "/task/tts"], ["led播放", "/task/led/index"],
  ["启用管理", "/task/enableManagement/index"], ["云广播终端", "/broad-cast/terminal"],
  ["音乐传输", "/broad-cast/music-transfer"], ["任务传送", "/broad-cast/task-transfer"],
  ["噪声设备", "/noise/equipment"], ["声场分区", "/noise/partition"]
];

const EXTRACT = `
JSON.stringify((() => {
  const clean = s => (s||'').replace(/\\s+/g,' ').trim();
  const main = document.querySelector('.app-main, .el-main, main') || document.body;

  // 表头（可能有多张表，取每张的第一行 th）
  const tables = [...main.querySelectorAll('table')].map(t => {
    const ths = [...t.querySelectorAll('thead th')].map(th => clean(th.innerText)).filter(Boolean);
    return ths;
  }).filter(a => a.length);

  // 工具栏按钮：正文里所有 button / 链接式按钮
  const buttons = [...main.querySelectorAll('button')]
    .map(b => ({ t: clean(b.innerText), cls: (b.className||'').toString() }))
    .filter(b => b.t && b.t.length <= 12)
    .map(b => {
      let style = 'default';
      const c = b.cls;
      if (/is-link|is-text/.test(c)) style = 'link';
      else if (/--primary/.test(c)) style = 'primary';
      else if (/--danger/.test(c)) style = 'danger';
      else if (/--warning/.test(c)) style = 'warning';
      else if (/--success/.test(c)) style = 'success';
      else if (/--info/.test(c)) style = 'info';
      return b.t + '(' + style + ')';
    });

  // 输入框 / 下拉（搜索区的迹象）
  const inputs = [...main.querySelectorAll('input')]
    .filter(i => i.type !== 'checkbox' && i.type !== 'radio')
    .map(i => clean(i.placeholder) || '(无占位符)');

  // 页签
  const tabs = [...main.querySelectorAll('.el-tabs__item, [role=tab]')].map(e => clean(e.innerText)).filter(Boolean);

  // 左侧树 / 侧栏卡片
  const tree = [...main.querySelectorAll('.el-tree-node__label, .el-tree__label')].map(e => clean(e.innerText)).filter(Boolean);

  return { tables, buttons: [...new Set(buttons)], inputs: [...new Set(inputs)], tabs: [...new Set(tabs)], tree: tree.slice(0,12) };
})())
`;

const proc = await launch({ profile: PROFILE, width: 1600, height: 1000 });
const s = await attach();
await s.send("Page.enable");
await s.send("Runtime.enable");

await s.goto("http://192.168.2.159/#/login", 6000);
if (await s.eval("location.hash.indexOf('login') !== -1")) {
  await s.eval(`
    (() => {
      const set = (el, v) => {
        const d = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value');
        d.set.call(el, v);
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
      };
      set(document.querySelector('#username'), 'admin');
      set(document.querySelector('#password'), '123456');
    })()
  `);
  await sleep(400);
  await s.eval(`(() => { const b=[...document.querySelectorAll('button')].find(x=>(x.innerText||'').trim()==='登录'); if(b) b.click(); })()`);
  await sleep(6000);
}

const lines = [];
for (const [name, route] of PAGES) {
  await s.eval(`location.hash = ${JSON.stringify("#" + route)}`);
  await sleep(2600);
  let d;
  try { d = JSON.parse(await s.eval(EXTRACT)); } catch (e) { d = { err: e.message }; }
  lines.push(`\n=== ${name}  (${route}) ===`);
  if (d.err) { lines.push("  抽取失败: " + d.err); continue; }
  (d.tables || []).forEach((t, i) =>
    lines.push(`  表${i + 1}列 (${t.length}): ${t.join(" | ")}`));
  if (!d.tables?.length) lines.push("  表: (无表格，是表单页)");
  lines.push(`  按钮: ${(d.buttons || []).join("  ") || "(无)"}`);
  lines.push(`  输入框: ${(d.inputs || []).join(" / ") || "(无)"}`);
  if (d.tabs?.length) lines.push(`  页签: ${d.tabs.join(" | ")}`);
  if (d.tree?.length) lines.push(`  左树: ${d.tree.join(" | ")}`);
  console.log(lines[lines.length - 5] ?? "");
}

const out = lines.join("\n");
writeFileSync(SP + "\\spec80.txt", out, "utf8");
console.log("\n已写出: " + SP + "\\spec80.txt");

s.close();
proc.kill();
