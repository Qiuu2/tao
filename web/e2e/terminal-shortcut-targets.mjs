/*
 * 「查看快捷键 → 映射终端」的回归测试。
 *
 * # 它盯的是什么
 *
 * 快捷键列表原来把每条映射的目标终端**全部平铺**成一排 el-tag。
 * 一个快捷键映射几十台终端是常事，那一行会被撑到完全看不清，
 * 而且这一列真正要回答的问题是「这个键打给谁」—— 那是一张表，不是一串标签。
 *
 * 现在这一列只给台数（外加一个「N 台已删除」的红标），点进去才逐条列。
 *
 * 五条：
 *
 *   ① 列里不再平铺目标名字，只有一个「N 台」的链接
 *   ② 目标里有已删除的，红标要把台数说出来
 *   ③ 点「N 台」弹出目标终端表格，行数对得上
 *   ④ 已删除的目标在表里单独成行，状态列标「已删除」—— 不是悄悄消失
 *   ⑤ area 掩码翻成人话：全 1 是「全部分区」，部分 1 列出位号
 *
 * # 怎么跑
 *
 * 后端 + vite + 一份带快捷键数据的库：
 *
 *   node e2e/terminal-shortcut-targets.mjs
 *
 * ⚠ 它**不改库**，只读。但需要库里至少有一台终端配了快捷键。
 *   E2E_KEY_OWNER 指定那台终端的名字（默认「广播室主话筒」）。
 */
const BASE = process.env.E2E_BASE || "http://127.0.0.1:5199";
const CHROME = process.env.E2E_CHROME || "/opt/pw-browsers/chromium-1194/chrome-linux/chrome";
const OWNER = process.env.E2E_KEY_OWNER || "广播室主话筒";

const { chromium } = await import(process.env.E2E_PLAYWRIGHT || "/opt/node22/lib/node_modules/playwright/index.mjs");

let fails = 0;
const ok = (c, m) => {
  console.log((c ? "  ✓ " : "  ✗ ") + m);
  if (!c) fails++;
};

const b = await chromium.launch({ executablePath: CHROME });
const p = await b.newPage({ viewport: { width: 1500, height: 900 } });

// ── 登录（滑动验证）──
await p.goto(BASE + "/#/login", { waitUntil: "domcontentloaded" });
await p.waitForSelector(".slider-captcha", { timeout: 25000 });
await p.waitForTimeout(1200);
await p.fill(".login-form input >> nth=0", "admin");
await p.fill(".login-form input >> nth=1", "123456");
{
  const t = await p.locator(".slider-captcha").boundingBox();
  const h = await p.locator(".slider-captcha .handle").boundingBox();
  await p.mouse.move(h.x + h.width / 2, h.y + h.height / 2);
  await p.mouse.down();
  for (let i = 1; i <= 14; i++) {
    await p.mouse.move(h.x + h.width / 2 + (t.width - h.width) * (i / 14), h.y + h.height / 2);
    await p.waitForTimeout(10);
  }
  await p.mouse.up();
}
await p.waitForTimeout(300);
await p.locator(".login-btn button").last().click();
await p.waitForTimeout(3500);

// ── 进终端管理，搜到那台配了快捷键的终端并勾上 ──
await p.goto(BASE + "/#/terminal", { waitUntil: "domcontentloaded" });
await p.waitForSelector(".el-table__row", { timeout: 25000 });
await p.waitForTimeout(1200);
await p.locator(".header-right input, .table-search-input input").first().fill(OWNER);
await p.waitForTimeout(1500);
const firstName = (await p.locator(".el-table__body .el-table__row").nth(0).locator("td").nth(2).innerText()).trim();
ok(firstName.includes(OWNER), `搜到了「${OWNER}」（第一行是「${firstName}」）`);
await p.locator(".el-table__body .el-table__row").nth(0).locator(".el-checkbox").click();
await p.waitForTimeout(600);

// ── 批量操作 → 查看快捷键 ──
await p.locator(".header-left button").first().click();
await p.waitForTimeout(500);
await p.locator(".el-dropdown-menu__item", { hasText: "查看快捷键" }).first().click();
await p.waitForTimeout(2000);

const dlg = p.locator(".el-dialog:visible").first();
await dlg.waitFor({ timeout: 10000 });
const rows = dlg.locator(".el-table__body .el-table__row");
const nKeys = await rows.count();
ok(nKeys > 0, `快捷键列表有 ${nKeys} 条`);

// 「映射终端」是第 4 列（序号 / 名称 / 快捷键 / 映射终端 / 操作）
const mapCell = i => rows.nth(i).locator("td").nth(3);

// ── ① 列里只有一个「N 台」链接，没有平铺的目标名字 ──
console.log("① 映射终端列不再平铺目标名字");
let linkTexts = [];
for (let i = 0; i < nKeys; i++) linkTexts.push((await mapCell(i).innerText()).trim());
console.log("   四列实际内容：" + JSON.stringify(linkTexts));
ok(
  linkTexts.every(s => /^\d+ 台/.test(s) || s === "未指定"),
  "每一格要么是「N 台(…)」要么是「未指定」，没有一串终端名"
);
const linkBtns = await dlg.locator(".el-table__body .el-table__row td:nth-child(4) button").count();
ok(linkBtns >= 1, `映射终端列里有 ${linkBtns} 个可点的链接`);

// ── ② 已删除的目标要单独报台数 ──
console.log("② 目标里有已删除的，红标报台数");
const delTag = dlg.locator(".el-table__body td:nth-child(4) .el-tag--danger").first();
const hasDelTag = (await delTag.count()) > 0;
ok(hasDelTag, hasDelTag ? `红标写着「${(await delTag.innerText()).trim()}」` : "没有已删除的目标（库里没造这种数据？）");

// ── ③ 点开看表格 ──
console.log("③ 点「N 台」弹出目标终端表格");
// 挑一行台数最多的点
let pick = 0;
let picked = 0;
for (let i = 0; i < nKeys; i++) {
  const m = linkTexts[i].match(/^(\d+) 台/);
  if (m && Number(m[1]) > picked) {
    picked = Number(m[1]);
    pick = i;
  }
}
await mapCell(pick).locator("button").first().click();
await p.waitForTimeout(1200);
const all = p.locator(".el-dialog:visible");
const tgt = all.nth((await all.count()) - 1);
const tTitle = (await tgt.locator(".el-dialog__title").innerText()).trim();
ok(tTitle.startsWith("映射终端 ·"), `标题是「${tTitle}」`);
const tRows = tgt.locator(".el-table__body .el-table__row");
ok((await tRows.count()) === picked, `表里 ${await tRows.count()} 行，和列上写的 ${picked} 台对得上`);
// ⚠ 表头单独查一遍：i18n 的 key 写错（比如 common.terminalName 这种根本不存在的）
//   不会报错，vue-i18n 直接把 key 原样显示出来，页面上就是一列叫 "common.terminalName"。
//   截图里一眼能看见，断言里不查就永远绿着 —— 这一条就是被截图抓出来的。
const head = (await tgt.locator(".el-table__header").innerText()).replace(/\s+/g, " ");
console.log("   表头：" + JSON.stringify(head));
ok(!/[a-z][a-zA-Z]*\.[a-zA-Z]/.test(head), "表头没有漏出未翻译的 i18n key");

// ── ④ 已删除的目标单独成行 ──
console.log("④ 已删除的目标在表里看得见，不是悄悄消失");
const body = await tgt.locator(".el-table__body").innerText();
console.log("   表格内容：" + JSON.stringify(body.replace(/\s+/g, " ").slice(0, 300)));
if (hasDelTag) {
  ok(/已删除\s*#\d+/.test(body), "有「已删除 #id」这样一行");
  ok((await tgt.locator(".el-table__body .el-tag--danger").count()) >= 1, "那一行状态列是红色的「已删除」");
}
ok((await tgt.locator(".el-table__body .el-tag--success").count()) >= 1, "正常的目标状态列是绿色的");

// ── ⑤ area 掩码翻成人话 ──
console.log("⑤ 分区掩码不能原样贴 1111111111111111");
ok(!/1{8,}/.test(body), "表里没有出现成串的 1");
ok(body.includes("全部分区"), "全 1 的显示成「全部分区」");
const partial = body.match(/(\d+(?:、\d+)+)/);
ok(!!partial, partial ? `部分开放的列成了位号「${partial[1]}」` : "没有部分开放的掩码（库里没造这种数据？）");

await b.close();
console.log(fails ? `\n✗ ${fails} 条没过` : "\n✓ 全过");
process.exit(fails ? 1 : 0);
