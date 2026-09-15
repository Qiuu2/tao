/*
 * 终端管理「网格视图」的回归测试。
 *
 * # 它盯的是什么
 *
 * 终端原来只有列表一种摆法：一行 20 列，看的是「这一批终端的某个字段怎么样」。
 * 巡检、找某台机器时要的是反过来的东西 ——「这一台现在什么状况」。网格一张卡片
 * 一台，把需求方点的那几个字段摆在一块：终端名称 / 终端类型 / 编号 / IP / 音量，
 * 外加三种状态（任务：空闲·播放中；网络：在线·离线；设备：已启动·已停止）。
 *
 * 实现上是 ProTable 新开的 body 插槽接管表体，两种表体**互斥渲染**。
 * 所以要盯的不只是「卡片长出来了」，还有那些共用的东西有没有被切坏：
 *
 *   ① 切到网格：卡片出现，el-table 不在了（不是藏起来）
 *   ② 每张卡片上那几个字段都在，且和列表里同一台终端对得上
 *   ③ 网格里能勾选，「批量操作(N)」跟着变 —— 批量操作在网格下不能变成摆设
 *   ④ 全选只动当前页
 *   ⑤ 分页 / 搜索 在网格下照常work
 *   ⑥ 切回列表：el-table 回来，勾选被清空（互斥渲染的代价，是有意的）
 *   ⑦ 刷新页面后还是上次选的那个视图（记在 localStorage）
 *   ⑧ 记着网格再重新进这一页，列表接口要真的发出去（现场那句「网格中显示暂无数据」）
 *
 * # 怎么跑
 *
 *   node e2e/terminal-grid.mjs
 *
 * ⚠ 只读，不改库。
 */
const BASE = process.env.E2E_BASE || "http://127.0.0.1:5199";
const CHROME = process.env.E2E_CHROME || "/opt/pw-browsers/chromium-1194/chrome-linux/chrome";

const { chromium } = await import(process.env.E2E_PLAYWRIGHT || "/opt/node22/lib/node_modules/playwright/index.mjs");

let fails = 0;
const ok = (c, m) => {
  console.log((c ? "  ✓ " : "  ✗ ") + m);
  if (!c) fails++;
};

const b = await chromium.launch({ executablePath: CHROME });
const p = await b.newPage({ viewport: { width: 1500, height: 950 } });
p.on("pageerror", e => {
  console.log("  [pageerror] " + String(e).slice(0, 200));
  fails++;
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

const openTerminals = async () => {
  await p.keyboard.press("Escape").catch(() => undefined);
  await p.goto(BASE + "/#/terminal", { waitUntil: "domcontentloaded" });
  // ⚠ 已经在这个 hash 上时 goto 什么都不做 —— 页面不会重新挂载。
  //   ⑦、⑧ 要的恰恰是「重新进这一页」，所以必须真的 reload 一次。
  //   不 reload 的话上一次留在页面上的卡片还在，断言会假绿：
  //   「网格下进页面拉不到数据」这个 bug 当初就是这么漏过去的。
  await p.reload({ waitUntil: "domcontentloaded" });
  await p.waitForSelector(".terminal-page", { timeout: 25000 });
  await p.waitForTimeout(2000);
};
/** 「批量操作(N)」里的那个 N */
const cnt = async () => {
  const s = (await p.locator(".header-left button").first().innerText()).replace(/\s+/g, "");
  return Number(s.match(/\((\d+)\)/)?.[1] ?? 0);
};
const toGrid = async () => {
  await p.locator(".view-switch label").nth(1).click();
  await p.waitForTimeout(1200);
};
const toList = async () => {
  await p.locator(".view-switch label").nth(0).click();
  await p.waitForTimeout(1200);
};

await openTerminals();

// 先从列表里抄一份第一台终端的字段，②拿它对账
const listRow0 = await p.locator(".el-table__body .el-table__row").nth(0).locator("td").allInnerTexts();
const heads = (await p.locator(".terminal-page .el-table__header-wrapper th").allInnerTexts()).map(x => x.trim());
const col = n => (listRow0[heads.indexOf(n)] || "").trim();
const want = {
  id: col("编号"),
  name: col("终端名称"),
  type: col("终端类型"),
  ip: col("IP地址"),
  volume: col("音量"),
  task: col("任务状态"),
  net: col("网络状态"),
  device: col("设备状态")
};
console.log("列表里的第一台：" + JSON.stringify(want));

// ── ① 切到网格 ──
console.log("① 切到网格：卡片出现，el-table 真的不在了");
ok((await p.locator(".view-switch").count()) === 1, "工具栏上有列表 / 网格切换");
await toGrid();
const cards = p.locator(".term-card");
ok((await cards.count()) > 0, `渲染了 ${await cards.count()} 张卡片`);
ok(
  (await p.locator(".terminal-page .el-table").count()) === 0,
  "el-table 已经从 DOM 里卸掉了（不是 display:none 藏着白跑一遍渲染）"
);

// ── ② 卡片上的字段 ──
console.log("② 卡片上需求方点的那几个字段都在，且和列表对得上");
const c0 = cards.nth(0);
const got = {
  name: (await c0.locator(".tc-name").innerText()).trim(),
  id: (await c0.locator(".tc-id").innerText()).trim().replace(/^#/, ""),
  type: (await c0.locator(".tc-type").innerText()).trim(),
  ip: (await c0.locator(".tc-ip").innerText()).trim(),
  volume: (await c0.locator(".tc-vol-num").innerText()).trim()
};
const tags = (await c0.locator(".el-tag").allInnerTexts()).map(x => x.trim());
console.log("   卡片：" + JSON.stringify(got) + " 标签：" + JSON.stringify(tags));
ok(got.name === want.name, `终端名称 ${got.name} = ${want.name}`);
ok(got.id === want.id, `编号 ${got.id} = ${want.id}`);
ok(got.type === want.type, `终端类型 ${got.type} = ${want.type}`);
ok(got.ip === want.ip, `IP ${got.ip} = ${want.ip}`);
ok(got.volume === want.volume, `音量 ${got.volume} = ${want.volume}`);
ok((await c0.locator(".tc-vol-bar").count()) === 1, "音量还画了一条进度条");
ok(tags.includes(want.net), `网络状态「${want.net}」在卡片上`);
ok(tags.includes(want.task), `任务状态「${want.task}」在卡片上`);
ok(tags.includes(want.device), `设备状态「${want.device}」在卡片上`);
ok(["空闲", "播放中"].includes(want.task), `任务状态是空闲 / 播放中之一（${want.task}）`);

// ── ③ 网格里能勾选，批量操作跟着动 ──
console.log("③ 网格里勾选，批量操作要跟着变");
ok((await cnt()) === 0, "进来时没有勾选");
await c0.click();
await p.waitForTimeout(600);
ok((await cnt()) === 1, `点一下卡片就勾上了（批量操作(${await cnt()})）`);
ok((await c0.getAttribute("class"))?.includes("picked"), "卡片上有勾中的样子");
// 复选框自己那一下不能被卡片的点击再翻回去
await c0.locator(".el-checkbox").click();
await p.waitForTimeout(600);
ok((await cnt()) === 0, `点复选框取消，不会被卡片点击翻回来（现在 ${await cnt()}）`);
await c0.locator(".el-checkbox").click();
await p.waitForTimeout(600);
ok((await cnt()) === 1, "再点一下又勾上");
const batchBtn = p.locator(".header-left button").first();
ok(await batchBtn.isEnabled(), "「批量操作」按钮在网格下是可点的");

// ── ④ 全选只动当前页 ──
console.log("④ 全选只动当前页");
const pageN = await cards.count();
await p.locator(".grid-bar .el-checkbox").click();
await p.waitForTimeout(700);
ok((await cnt()) === pageN, `全选之后是本页的 ${pageN} 台（实际 ${await cnt()}）`);
const barText = (await p.locator(".grid-bar").innerText()).replace(/\s+/g, " ");
console.log("   " + barText);
ok(barText.includes(`本页 ${pageN} 台`), "上面那行把「已选 / 本页」说清楚了");
await p.locator(".grid-bar .el-checkbox").click();
await p.waitForTimeout(700);
ok((await cnt()) === 0, "再点一下全不选");

// ── ⑤ 分页与搜索在网格下照常 ──
console.log("⑤ 分页与搜索在网格下照常");
const next = p.locator(".el-pagination .btn-next");
if (await next.isEnabled()) {
  const before = await cards.nth(0).locator(".tc-id").innerText();
  await next.click();
  await p.waitForTimeout(1800);
  const after = await cards.nth(0).locator(".tc-id").innerText();
  ok(before !== after, `翻页换了一批（${before} → ${after}）`);
  await p.locator(".el-pagination .btn-prev").click();
  await p.waitForTimeout(1800);
} else {
  console.log("   只有一页，跳过翻页");
}
await p.locator(".term-search input").fill(want.name);
await p.waitForTimeout(1600);
const names = await p.locator(".term-card .tc-name").allInnerTexts();
console.log("   搜「" + want.name + "」→ " + JSON.stringify(names));
ok(names.length > 0 && names.every(n => n.includes(want.name)), `搜索在网格下生效，回了 ${names.length} 张卡片`);
await p.locator(".term-search input").fill("");
await p.waitForTimeout(1600);

// ── ⑥ 切回列表 ──
console.log("⑥ 切回列表：表格回来，勾选被清空（互斥渲染的代价，是有意的）");
await cards.nth(0).click();
await p.waitForTimeout(600);
ok((await cnt()) === 1, "切之前先勾一台");
await toList();
ok((await p.locator(".terminal-page .el-table").count()) > 0, "el-table 回来了");
ok((await p.locator(".term-card").count()) === 0, "卡片没了");
ok((await cnt()) === 0, `勾选清空（现在 ${await cnt()}）`);

// ── ⑦ 记住上次选的视图 ──
console.log("⑦ 刷新之后还是上次那个视图");
await toGrid();
await openTerminals();
ok((await p.locator(".term-card").count()) > 0, "重新进页面，仍然是网格");
await toList();
await openTerminals();
ok((await p.locator(".terminal-page .el-table").count()) > 0, "切回列表再进，仍然是列表");

// ── ⑧ 记着网格再重新进这一页：列表接口要真的发出去 ──
//
// 这一条盯的是现场那句「全部终端有 19 个，但右边网格中显示暂无数据」。
// 根因在 ProTable：onMounted 里 dragSort() 排在首次取数前面，它去找
// `#uuid tbody`，而网格模式走的是 #body 插槽、根本不渲染 el-table，
// 于是 Sortable.create(null) 抛在 onMounted 里，后面的 getTableList()
// 一次都执行不到 —— 请求压根没发出去，界面上就是「暂无数据」。
//
// 所以这里不光看卡片，还要盯住**列表请求真的发出去了**：
// 只断言卡片数量的话，将来若换成别的兜底渲染，仍然会假绿。
console.log("⑧ 记着网格再重新进这一页，列表接口要真的发出去（网格下取不到数的那个 bug）");
await toGrid();
const listCalls = [];
const onReq = r => {
  if (/\/api\/terminals\?/.test(r.url())) listCalls.push(r.url());
};
p.on("request", onReq);
await openTerminals();
p.off("request", onReq);
ok((await p.locator(".term-card").count()) > 0, `重新进页面就是网格，卡片直接长出来了（${await p.locator(".term-card").count()} 张）`);
ok(listCalls.length > 0, `列表接口发出去了（${listCalls.length} 次）—— 原来一次都不发`);
ok((await p.locator(".grid-wrap .table-empty").count()) === 0, "没有停在「暂无数据」上");

await toList();

await b.close();
console.log(fails ? `\n✗ ${fails} 条没过` : "\n✓ 全过");
process.exit(fails ? 1 : 0);
