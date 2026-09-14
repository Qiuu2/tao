/*
 * 报警映射「通道路数」的回归测试。
 *
 * # 它盯的是什么
 *
 * 一台报警主机有几路可配的报警输入，**以这台设备自己上报的 terminal.channel 为准**
 * （与 ok112 一致）。算法在 alarm/picker.go 的 effectiveChannels。
 *
 * ⚠ 中间有一版取过 max(terminal.channel, terminaltype.switchcount)，想法是
 *   「型号声明 16 路就该给 16 路」。需求方纠正过：**报警主机就按它自己报的路数算** ——
 *   一台 channel=2 的主机就是 2 路，界面上摆出 16 路、选得到第 16 路却接不上，
 *   比只给 2 路更糟。这个脚本把现在的语义钉住，免得哪天又「修」回去。
 *
 * 三处必须说同一个数：通道下拉、保存校验、列表「通道 N / M」的分母。各算各的就会
 * 自相矛盾 —— 两种都现网报过：「下拉 16、列表写 /2 且第 3 路被误判成超范围」、
 * 「下拉摆出 16 路，实际只有 2 路」。
 *
 * 五条：
 *   ① 通道下拉的项数 = 主机树里写的「N 路」
 *   ② 列表「通道」列的分母和下拉是同一个数
 *   ③ 范围内的映射不被误判成超范围
 *   ④ 前后两处（列表 / 编辑回填）说的是同一个数
 *   ⑤ 路数跟着 terminal.channel 走：压回 2，三处一起变成 2，而且**不受
 *      terminaltype.switchcount 影响**（型号那行写着 16 也不给 16 路）；
 *      那条超出范围的映射这时才该被标红。
 *
 * # 怎么跑
 *
 *   node e2e/alarm-mapping-channels.mjs
 *
 * ⚠ 会临时改库：把报警主机的 terminal.channel 改成 8、插一条 8 路的映射，
 *   跑完（含中途退出）都会改回 2 并删掉那条映射。
 */
const BASE = process.env.E2E_BASE || "http://127.0.0.1:5199";
const CHROME = process.env.E2E_CHROME || "/opt/pw-browsers/chromium-1194/chrome-linux/chrome";
const SQL = q => `mariadb -uroot audioserver -e "${q}"`;
/**
 * 造数据：把报警主机的上报路数改成 8（基线是 2），再挂一条「通道 8」的映射。
 * 这样 ①~④ 有 8 路可看；⑤ 把路数压回 2 时那条映射正好落到范围外，
 * 顺带验证「超出范围」的红标是**该标的时候才标**。
 */
const SEED =
  process.env.E2E_SEED_DB ||
  SQL(
    "UPDATE terminal SET channel=8 WHERE typeid=7; " +
      "INSERT INTO alarmgroupmap (info, alarmterminalid, alarmchannel, firealarmgroupid, mediaid) " +
      "SELECT 'E2E八路', m.alarmterminalid, 8, m.firealarmgroupid, m.mediaid FROM alarmgroupmap m ORDER BY m.id LIMIT 1"
  );
const UNSEED =
  process.env.E2E_UNSEED_DB ||
  SQL("DELETE FROM alarmgroupmap WHERE info='E2E八路'; UPDATE terminal SET channel=2 WHERE typeid=7");
/** ⑤ 用：把上报路数压回 2 / 再改回 8 */
const SET_CH2 = process.env.E2E_SET_CH2 || SQL("UPDATE terminal SET channel=2 WHERE typeid=7");
const SET_CH8 = process.env.E2E_SET_CH8 || SQL("UPDATE terminal SET channel=8 WHERE typeid=7");

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
    // UNSEED 一条语句里同时删映射、把 channel 改回 2，中途怎么退都收得干净
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

// ── ⑤ 路数跟着 terminal.channel 走 ──
console.log("⑤ 路数以设备上报的 terminal.channel 为准，改它三处一起变");

/** 打开「添加」弹窗、选中那台报警主机，返回弹窗、主机项文字、来源说明 */
const openAdd = async () => {
  // ⚠ 先把还开着的弹窗用「取消」关掉，别指望 goto 能冲掉它：
  //   路由是 hash 的，goto 到**当前同一个** hash 根本不发生导航，弹窗原样还在，
  //   接着点「添加」就一直被 .el-overlay 挡住（干等 30 秒然后超时）。
  await p.keyboard.press("Escape");
  await p.waitForTimeout(400);
  const open = p.locator(".el-dialog:visible .el-dialog__footer button", { hasText: "取消" });
  if (await open.count()) {
    await open.first().click();
    await p.waitForSelector(".el-overlay", { state: "hidden", timeout: 10000 }).catch(() => undefined);
    await p.waitForTimeout(800);
  }
  await p.reload({ waitUntil: "domcontentloaded" });
  await p.waitForSelector(".el-table__row", { timeout: 25000 });
  await p.waitForTimeout(1500);
  await p.locator(".table-header-ops button, .header-left button").first().click();
  await p.waitForTimeout(1500);
  const d = p.locator(".el-dialog:visible").first();
  await d.locator(".el-select, .el-tree-select").first().click();
  await p.waitForTimeout(1200);
  await p.waitForSelector(HOST_OPT, { timeout: 10000 });
  const lines = await p.locator(HOST_OPT).allInnerTexts();
  const host = lines.find(x => /\d+\s*路/.test(x));
  await p.locator(HOST_OPT, { hasText: host }).first().click();
  await p.waitForTimeout(1200);
  const chan = d.locator(".el-form-item", { hasText: "通道" }).locator(".el-select").first();
  await chan.click();
  await p.waitForTimeout(900);
  const n = await p.locator(".el-select-dropdown:visible .el-select-dropdown__item").count();
  await p.keyboard.press("Escape");
  await p.waitForTimeout(400);
  return { d, host, n, src: (await d.locator(".ch-src").innerText()).replace(/\s+/g, " ").trim() };
};

/** 列表里每行的「通道 N / M」和有没有红标 */
const listChans = async () => {
  await p.keyboard.press("Escape");
  await p.waitForTimeout(400);
  const cancel = p.locator(".el-dialog:visible .el-dialog__footer button", { hasText: "取消" });
  if (await cancel.count()) {
    await cancel.first().click();
    await p.waitForSelector(".el-overlay", { state: "hidden", timeout: 10000 }).catch(() => undefined);
  }
  await p.reload({ waitUntil: "domcontentloaded" });
  await p.waitForSelector(".el-table__row", { timeout: 25000 });
  await p.waitForTimeout(1500);
  const rs = p.locator(".el-table__body .el-table__row");
  const out = [];
  for (let i = 0; i < (await rs.count()); i++) {
    const td = await rs.nth(i).locator("td").allInnerTexts();
    out.push({
      chan: (td[cChan] || "").replace(/\s+/g, " ").trim(),
      danger: (await rs.nth(i).locator("td").nth(cChan).locator(".el-tag--danger").count()) > 0
    });
  }
  return out;
};

{
  const cur = await openAdd();
  console.log(`   channel=8：主机项「${cur.host}」，下拉 ${cur.n} 项，来源「${cur.src}」`);
  ok(cur.n === 8, `下拉 8 项（= terminal.channel）`);
  ok(cur.src.includes("本机 8 路"), "来源那行写明本机 8 路");
  ok(!/型号|switchcount/.test(cur.src), "不再提型号声明的 16 路 —— 那个数已经不参与了");
}

// 把上报路数压回 2：三处都要跟着变，且型号那行仍写着 16 也不能把它顶上去
execSync(SET_CH2, { stdio: "pipe" });
{
  const cur = await openAdd();
  console.log(`   channel=2：主机项「${cur.host}」，下拉 ${cur.n} 项，来源「${cur.src}」`);
  ok(cur.n === 2, `下拉变成 2 项（实际 ${cur.n}）—— 这就是需求方要的「只有两个通道」`);
  ok(/2\s*路/.test(cur.host), `主机项也写 2 路：${cur.host}`);
  ok(cur.src.includes("本机 2 路"), "来源那行跟着变成 2 路");

  const rows2 = await listChans();
  console.log("   列表：" + JSON.stringify(rows2));
  const den = rows2.map(r => Number(r.chan.match(/\/\s*(\d+)/)?.[1] ?? NaN)).filter(n => !Number.isNaN(n));
  ok(den.length > 0 && den.every(d => d === 2), `列表分母也全是 2（实际 ${JSON.stringify([...new Set(den)])}）`);
  const eight2 = rows2.find(r => /通道\s*8/.test(r.chan));
  ok(!!eight2 && eight2.danger, "那条通道 8 的映射这时才被标红「超出范围」—— 该标的时候才标");
}

// 改回 8，确认复原
execSync(SET_CH8, { stdio: "pipe" });
{
  const cur = await openAdd();
  console.log(`   改回 channel=8：下拉 ${cur.n} 项`);
  ok(cur.n === 8, "改回去就复原成 8 项");
  const rows8 = await listChans();
  const eight8 = rows8.find(r => /通道\s*8/.test(r.chan));
  ok(!!eight8 && !eight8.danger, "通道 8 又回到范围内，红标消失");
}

await b.close();
console.log(fails ? `\n✗ ${fails} 条没过` : "\n✓ 全过");
process.exit(fails ? 1 : 0);
