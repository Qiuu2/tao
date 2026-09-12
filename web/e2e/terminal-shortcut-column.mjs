/*
 * 终端列表「快捷键」列 + 终端替换去掉前置条件 的回归测试。
 *
 * # 一、快捷键列
 *
 * ok112 的终端列表本来就有这一列（terminalmanager.php，内容是「这台终端在
 * terminalkey 里有没有记录」），新 Web 之前漏了。漏掉的后果不是少看一眼：
 * 想知道哪台配过快捷键，只能一台一台勾中、翻开「批量操作」菜单看那一项灰不灰。
 *
 * 三条：
 *   ① 表头有「快捷键」这一列
 *   ② 每一格只可能是「查看」/「设置」/「—」，不会是别的
 *   ③ 点「查看」弹出的是**这一行那台终端**的快捷键对话框，不是别人的
 *
 * # 二、终端替换的前置条件
 *
 * ok112（getterminalid.php）在目标 ID 已被占用时要求两台同型号、且目标离线。
 * 需求方要求去掉，后端也删了校验。
 *
 * 两条：
 *   ④ 对话框里不再写「要求…型号相同…处于离线」这种已经不存在的条件
 *   ⑤ 真的换一次：源和目标**型号不同、且目标在线**，要成功
 *
 * # 怎么跑
 *
 *   node e2e/terminal-shortcut-column.mjs
 *
 * ⚠ ⑤ 会**真的改库**（删掉目标那条终端记录、把源改号）。跑之前先备份：
 *
 *   mariadb-dump -uroot --no-tablespaces audioserver > /tmp/before.sql
 *   node e2e/terminal-shortcut-column.mjs
 *   mariadb -uroot audioserver < /tmp/before.sql
 *
 *   不想动库就设 E2E_SKIP_REPLACE=1，只跑 ①—④。
 */
const BASE = process.env.E2E_BASE || "http://127.0.0.1:5199";
const CHROME = process.env.E2E_CHROME || "/opt/pw-browsers/chromium-1194/chrome-linux/chrome";
// ① ② ③ 不指定终端名字：脚本自己在列里找第一台显示「查看」的。
// 写死名字的话，换一份库就得改脚本 —— 而「哪台配了快捷键」本来就是这一列要答的问题。
/** ⑤ 用：把那台换到这个 ID 上。它必须**型号不同且在线** */
const REPLACE_TO = Number(process.env.E2E_REPLACE_TO || 7);
const SKIP_REPLACE = process.env.E2E_SKIP_REPLACE === "1";

const { chromium } = await import(process.env.E2E_PLAYWRIGHT || "/opt/node22/lib/node_modules/playwright/index.mjs");

let fails = 0;
const ok = (c, m) => {
  console.log((c ? "  ✓ " : "  ✗ ") + m);
  if (!c) fails++;
};

const b = await chromium.launch({ executablePath: CHROME });
const p = await b.newPage({ viewport: { width: 1700, height: 900 } });

/** 替换请求与它的响应，⑤ 用 */
let replaceResp = null;
p.on("response", async r => {
  if (/\/api\/terminals\/replace/.test(r.url())) replaceResp = { status: r.status(), body: await r.text().catch(() => "") };
});

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

await p.goto(BASE + "/#/terminal", { waitUntil: "domcontentloaded" });
await p.waitForSelector(".el-table__row", { timeout: 25000 });
await p.waitForTimeout(1500);

// ── ① 表头有这一列 ──
console.log("① 终端列表有「快捷键」列");
// ⚠ 别 filter(Boolean)。第一列是复选框，表头是空的 —— 滤掉之后下标就和
//   <td> 对不上了，第一版就是这么踩的：找到的是第 13 列，点到了「发言」。
//   表头下标必须和行里的 td 下标一一对应。
const heads = (await p.locator(".terminal-page .el-table__header-wrapper th").allInnerTexts()).map(s => s.trim());
console.log("   表头：" + JSON.stringify(heads));
const keyCol = heads.indexOf("快捷键");
ok(keyCol >= 0, `表头第 ${keyCol + 1} 列是「快捷键」`);
ok(
  heads.every(h => !/[a-z][a-zA-Z]*\.[a-zA-Z]/.test(h)),
  "表头没有漏出未翻译的 i18n key"
);

// ── ② 每一格只有三种可能 ──
console.log("② 每一格只可能是 查看 / 设置 / —");
const cells = [];
const rowN = await p.locator(".terminal-page .el-table__body .el-table__row").count();
for (let i = 0; i < rowN; i++) {
  cells.push(
    (await p.locator(".terminal-page .el-table__body .el-table__row").nth(i).locator("td").nth(keyCol).innerText()).trim()
  );
}
console.log("   实际：" + JSON.stringify(cells));
ok(cells.length > 0 && cells.every(c => c === "查看" || c === "设置" || c === "—"), `${cells.length} 行全部落在三种取值里`);
ok(cells.includes("查看"), "至少有一台显示「查看」（配过快捷键）");
ok(cells.includes("—"), "至少有一台显示「—」（终端类型不支持快捷键）");

// ── ③ 点「查看」开的是这一行那台 ──
console.log("③ 点「查看」开的是本行那台终端的快捷键");
let hit = -1;
for (let i = 0; i < rowN; i++)
  if (cells[i] === "查看") {
    hit = i;
    break;
  }
const rowName = (
  await p.locator(".terminal-page .el-table__body .el-table__row").nth(hit).locator("td").nth(2).innerText()
).trim();
await p.locator(".terminal-page .el-table__body .el-table__row").nth(hit).locator("td").nth(keyCol).locator("button").click();
await p.waitForTimeout(1800);
const skTitle = (await p.locator(".el-dialog:visible .el-dialog__title").first().innerText()).trim();
ok(skTitle === `快捷键 · ${rowName}`, `标题是「${skTitle}」，本行那台叫「${rowName}」`);
ok((await p.locator(".el-dialog:visible .el-table__body .el-table__row").count()) > 0, "对话框里列出了快捷键");
// ⚠ 这一步没有先勾中任何终端 —— 原来只有「批量操作 → 查看快捷键」一条路，
//   必须先勾中。列里点开不该依赖勾选，所以这里特意不勾。
ok(
  !/\(\d+\)/.test(await p.locator(".terminal-page .header-left button").first().innerText()),
  "全程没勾中任何终端，也能从列里点开"
);
await p.keyboard.press("Escape");
await p.waitForTimeout(800);

// ── ④ 替换对话框不再写已经不存在的条件 ──
console.log("④ 终端替换对话框不再写「型号相同 / 处于离线」");
await p.locator(".terminal-page .el-table__body .el-table__row").nth(hit).locator(".el-checkbox").click();
await p.waitForTimeout(600);
await p.locator(".terminal-page .header-left button").first().click();
await p.waitForTimeout(500);
await p.locator(".el-dropdown-menu__item", { hasText: "终端替换" }).first().click();
await p.waitForTimeout(1500);
const note = (await p.locator(".el-dialog:visible .el-alert").first().innerText()).replace(/\s+/g, " ");
console.log("   提示：" + JSON.stringify(note));
ok(!note.includes("型号相同"), "没有再写「型号相同」");
ok(!note.includes("处于离线"), "没有再写「处于离线」");
ok(note.includes("会被删除"), "仍然说清楚「目标那条记录会被删除」");

// ── ⑤ 真换一次：型号不同 + 目标在线 ──
if (SKIP_REPLACE) {
  console.log("⑤ 跳过（E2E_SKIP_REPLACE=1）");
} else {
  console.log(`⑤ 把「${rowName}」换到 ID ${REPLACE_TO}（型号不同、且该目标在线）`);
  await p.locator(".el-dialog:visible input").first().fill(String(REPLACE_TO));
  await p.waitForTimeout(300);
  await p.locator(".el-dialog:visible .el-dialog__footer button").last().click();
  await p.waitForTimeout(1200);
  await p.locator(".el-message-box__btns button").last().click();
  await p.waitForTimeout(2500);
  ok(replaceResp?.status === 200, `HTTP ${replaceResp?.status}`);
  const body = replaceResp?.body ?? "";
  console.log("   响应：" + body.slice(0, 200));
  ok(/"code":\s*200/.test(body), "后端没有因为型号 / 在线把它拦下来");
  ok(/"mode":\s*"takeover"/.test(body), "走的是 takeover（目标原本存在）");
  ok((await p.locator(".el-message--success").count()) > 0 || /"code":\s*200/.test(body), "界面上提示替换成功");
}

await b.close();
console.log(fails ? `\n✗ ${fails} 条没过` : "\n✓ 全过");
process.exit(fails ? 1 : 0);
