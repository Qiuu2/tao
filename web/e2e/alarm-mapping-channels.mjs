/*
 * 报警映射「通道路数」的回归测试。
 *
 * # 它盯的是什么
 *
 * 一台报警主机有几路可配的报警输入，有两个来源，单看哪一个都会算错：
 *
 *   terminaltype.switchcount   这个**型号**支持几路（类型 7 声明 16）
 *   terminal.channel           这台**设备**自己报的路数，默认值 2
 *
 * 算法在 alarm/picker.go 的 effectiveChannels：型号打底，设备报得更多就以设备为准。
 * 通道下拉和保存时的校验都走这一套 —— 但**列表**没走，它直接取了 t.channel 的原值。
 *
 * 于是同一台 16 路的主机：
 *
 *   · 下拉里有 16 项
 *   · 列表的「通道」列写着「通道 1 / 2」——「只有 2 路」
 *   · 第 3 路以上的映射被判成「超出该主机的 2 路范围」，红着标
 *     「报警触发时不会正常播放」，实际上完全正常
 *
 * 演示库直接能复现：类型 7 的 switchcount=16，那台主机的 terminal.channel=2。
 *
 * 四条：
 *   ① 通道下拉的项数 = 主机树里写的「N 路」
 *   ② 列表「通道」列的分母和下拉是同一个数
 *   ③ 第 3 路以上的映射不被误判成超范围
 *   ④ 前后两处（列表 / 编辑回填）说的是同一个数
 *
 * # 怎么跑
 *
 *   node e2e/alarm-mapping-channels.mjs
 *
 * ⚠ ③ 会临时往 alarmgroupmap 里插一条 8 路的映射，跑完删掉。
 */
const BASE = process.env.E2E_BASE || "http://127.0.0.1:5199";
const CHROME = process.env.E2E_CHROME || "/opt/pw-browsers/chromium-1194/chrome-linux/chrome";
/** ③ 用：造一条「通道 8」的映射，看它会不会被误判成超范围 */
const SEED =
  process.env.E2E_SEED_DB ||
  `mariadb -uroot audioserver -e "INSERT INTO alarmgroupmap (info, alarmterminalid, alarmchannel, firealarmgroupid, mediaid) SELECT 'E2E八路', m.alarmterminalid, 8, m.firealarmgroupid, m.mediaid FROM alarmgroupmap m ORDER BY m.id LIMIT 1"`;
const UNSEED = process.env.E2E_UNSEED_DB || `mariadb -uroot audioserver -e "DELETE FROM alarmgroupmap WHERE info='E2E八路'"`;

const { chromium } = await import(process.env.E2E_PLAYWRIGHT || "/opt/node22/lib/node_modules/playwright/index.mjs");
const { execSync } = await import("node:child_process");

let fails = 0;
const ok = (c, m) => {
  console.log((c ? "  ✓ " : "  ✗ ") + m);
  if (!c) fails++;
};

execSync(SEED, { stdio: "pipe" });
const cleanup = () => {
  try {
    execSync(UNSEED, { stdio: "pipe" });
  } catch {
    console.log("  ! 清理造的数据失败，手工执行：" + UNSEED);
  }
};
process.on("exit", cleanup);

const b = await chromium.launch({ executablePath: CHROME });
const p = await b.newPage({ viewport: { width: 1500, height: 950 } });

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

await p.goto(BASE + "/#/alarm/mapping", { waitUntil: "domcontentloaded" });
await p.waitForSelector(".el-table__row", { timeout: 25000 });
await p.waitForTimeout(1500);

// ── ① 下拉项数 = 主机树里写的 N 路 ──
console.log("① 通道下拉的项数要等于主机自己声明的路数");
await p.locator(".table-header-ops button, .header-left button").first().click();
await p.waitForTimeout(1500);
const dlg = p.locator(".el-dialog:visible").first();
await dlg.locator(".el-select, .el-tree-select").first().click();
await p.waitForTimeout(1200);
// ⚠ 主机选择是 el-tree-select（组件在 components/TerminalTree/Select.vue）。
//   下拉里每个节点外面包着一层 `li.el-select-dropdown__item`，那一层 EP 标成
//   不可点（Playwright 报 "element is not enabled"），真正能点的是里面的
//   `.el-tree-node__content`。对着外层 li 点会干等 30 秒然后超时，
//   而且报错跟被测功能毫无关系。
const HOST_OPT = ".el-select-dropdown:visible .el-tree-node__content";
await p.waitForSelector(HOST_OPT, { timeout: 10000 });
const hostLabels = await p.locator(HOST_OPT).allInnerTexts();
const hostLine = hostLabels.find(x => /\d+\s*路/.test(x));
ok(!!hostLine, `主机树里有一台写明路数的：${hostLine}`);
const declared = Number((hostLine || "").match(/(\d+)\s*路/)?.[1] ?? 0);
await p.locator(HOST_OPT, { hasText: hostLine }).first().click();
await p.waitForTimeout(1200);

// 通道是弹窗里第 2 个 el-select（主机是 tree-select，不算）
const chanSel = dlg.locator(".el-form-item", { hasText: "通道" }).locator(".el-select").first();
await chanSel.click();
await p.waitForTimeout(1000);
const chans = await p.locator(".el-select-dropdown:visible .el-select-dropdown__item").allInnerTexts();
console.log(
  `   主机声明 ${declared} 路，下拉 ${chans.length} 项：${JSON.stringify(chans.slice(0, 3))}…${JSON.stringify(chans.slice(-1))}`
);
ok(chans.length === declared, `下拉 ${chans.length} 项 = 声明的 ${declared} 路`);
// ⚠ 用「取消」关，别按 Escape。Escape 只收掉下拉，弹窗还开着，
//   接下来点列表里的「修改」就会一直被 .el-overlay 挡住
//   （Playwright 报 "subtree intercepts pointer events"，干等 30 秒）。
await p.keyboard.press("Escape");
await p.waitForTimeout(500);
await dlg.locator(".el-dialog__footer button", { hasText: "取消" }).first().click();
await p.waitForSelector(".el-overlay", { state: "hidden", timeout: 10000 }).catch(() => undefined);
await p.waitForTimeout(1000);

// ── ②③④ 列表 ──
console.log("②③ 列表里的分母和下拉是同一个数，且不误判超范围");
const heads = (await p.locator(".el-table__header-wrapper th").allInnerTexts()).map(x => x.trim());
const cChan = heads.indexOf("映射通道");
ok(cChan >= 0, `表头：${JSON.stringify(heads)}`);
const rows = p.locator(".el-table__body .el-table__row");
const cells = [];
for (let i = 0; i < (await rows.count()); i++) {
  const td = await rows.nth(i).locator("td").allInnerTexts();
  cells.push({
    chan: (td[cChan] || "").replace(/\s+/g, " ").trim(),
    danger: (await rows.nth(i).locator("td").nth(cChan).locator(".el-tag--danger").count()) > 0
  });
}
console.log("   " + JSON.stringify(cells));
const denoms = cells.map(c => Number(c.chan.match(/\/\s*(\d+)/)?.[1] ?? NaN)).filter(n => !Number.isNaN(n));
ok(denoms.length > 0, `列表里有 ${denoms.length} 行写了分母`);
ok(
  denoms.every(d => d === declared),
  `分母全是 ${declared}（实际 ${JSON.stringify([...new Set(denoms)])}）—— 和下拉说的一致`
);
const eight = cells.find(c => /通道\s*8/.test(c.chan));
ok(!!eight, `造的那条「通道 8」在列表里：${eight?.chan}`);
ok(eight && !eight.danger, "通道 8 没有被标成「超出范围」的红标");

// ── ④ 编辑回填说的也是同一个数 ──
console.log("④ 打开编辑，回填的通道下拉还是同一个数");
let editRow = -1;
for (let i = 0; i < (await rows.count()); i++) {
  const td = await rows.nth(i).locator("td").allInnerTexts();
  if (/通道\s*8/.test((td[cChan] || "").replace(/\s+/g, " "))) editRow = i;
}
await rows.nth(editRow).locator("button", { hasText: "修改" }).first().click();
await p.waitForTimeout(1800);
const dlg2 = p.locator(".el-dialog:visible").first();
const chanSel2 = dlg2.locator(".el-form-item", { hasText: "通道" }).locator(".el-select").first();
await chanSel2.click();
await p.waitForTimeout(1000);
const chans2 = await p.locator(".el-select-dropdown:visible .el-select-dropdown__item").allInnerTexts();
ok(chans2.length === declared, `编辑里下拉也是 ${chans2.length} 项`);

await b.close();
console.log(fails ? `\n✗ ${fails} 条没过` : "\n✓ 全过");
process.exit(fails ? 1 : 0);
