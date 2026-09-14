/*
 * 资源管理 → 地图 的回归测试。
 *
 * # 它盯的是什么
 *
 * 一张校园平面图当底图，把终端按实际位置摆上去 —— 列表回答「哪一台怎么样」，
 * 地图回答「它在哪」。要盯的不只是「页面打得开」，还有这几件容易坏的事：
 *
 *   ① 菜单里有入口，页面打得开，默认看到的是「南昌理工学院」
 *   ② 还没传真图时画占位底图（不是空白页），且照样能摆终端
 *   ③ **右键点在图上添加终端 —— 落点就是右键的那个点**（这一条是「可以定位」的判据）
 *   ④ 拖动改位置**存得下来**，刷新之后还在原处
 *   ⑤ 换底图：传一张真图，占位消失，**已经摆好的点不跑位**（坐标是百分比）
 *   ⑥ 右键终端 → 移出地图，终端本身不受影响
 *   ⑦ 删底图会连带清掉图上的摆放
 *   ⑧ 不同状态画不同图标（不只是换颜色），图例只列图上出现过的那几种，
 *     而且**状态变了图标自己跟着换**（页面开着不动，直接改库）
 *
 * ⑤ 是这一套设计的核心：坐标存的是图上的百分比，不是像素，
 * 所以换底图、换分辨率、收起侧边栏都不该让点跑位。
 *
 * ③ 是需求方后来提的：原来「添加终端」在工具栏，选完全堆在左上角再一台台拖。
 * 人是先想好「这台在三楼东头」才去加的，位置本来就是这个动作的一部分。
 * 所以这一条不是「能加上就行」，而是**落点必须落在右键的那个点上**（容差 2%）。
 *
 * # 怎么跑
 *
 *   node e2e/map.mjs
 *
 * 前置：库里已经执行过 db/map_tables.sql。
 *
 * ⚠ 会**真的改库和磁盘**：新建一张叫 E2E地图 的底图、传一张图、摆几台终端，
 *   跑完（含中途退出）都会删掉。默认那张「南昌理工学院」一根毛都不动。
 */
const BASE = process.env.E2E_BASE || "http://127.0.0.1:5199";
const CHROME = process.env.E2E_CHROME || "/opt/pw-browsers/chromium-1194/chrome-linux/chrome";
/** ⑤ 用的真图。⚠ 路径必须是纯 ASCII —— setInputFiles 遇到中文路径会悄悄什么都不挂 */
const IMG = process.env.E2E_MAP_IMAGE || "/tmp/claude-0/campus-test.png";
const SQL = q => `mariadb -uroot audioserver -e "${q}"`;
const UNSEED =
  process.env.E2E_UNSEED_DB ||
  SQL(
    "DELETE mt FROM map_terminal mt JOIN map_image mi ON mi.id=mt.mapid WHERE mi.name LIKE 'E2E%'; " +
      "DELETE FROM map_image WHERE name LIKE 'E2E%'; " +
      // ⑧ 会临时把两台终端改成「对讲中 / 寻呼中」，好验出不同状态画的是不同图标。
      // taskstate 本来由后台 C 服务维护，测试改完必须放回去，否则终端列表那边也会跟着变。
      "UPDATE terminal SET taskstate = 0 WHERE taskstate IN (2, 5) AND id IN (5, 6); " +
      // ⑨ 还会把一台改成离线来验无感刷新。中途崩了要放回去，
      // 否则终端列表上会凭空多一台「离线」的机器，查半天查不出原因。
      "UPDATE terminal SET netstate = 1 WHERE netstate = 0 AND devicestate = 1"
  );

const { chromium } = await import(process.env.E2E_PLAYWRIGHT || "/opt/node22/lib/node_modules/playwright/index.mjs");
const { execSync } = await import("node:child_process");
const { existsSync } = await import("node:fs");

let fails = 0;
const ok = (c, m) => {
  console.log((c ? "  ✓ " : "  ✗ ") + m);
  if (!c) fails++;
};

if (!existsSync(IMG)) {
  console.log(`✗ 找不到测试底图 ${IMG}，先生成一张 png（见文件头说明）`);
  process.exit(1);
}

const cleanup = () => {
  try {
    execSync(UNSEED, { stdio: "pipe" });
  } catch {
    console.log("  ! 清理失败，手工执行：" + UNSEED);
  }
};
process.on("exit", cleanup);
execSync(UNSEED, { stdio: "pipe" }); // 上次跑崩留下的先清掉

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

const openMap = async () => {
  await p.goto(BASE + "/#/map", { waitUntil: "domcontentloaded" });
  await p.waitForSelector(".map-page", { timeout: 25000 });
  await p.waitForTimeout(2200);
};
const pins = () => p.locator(".pin");
/** 读某个点的位置（百分比），用来验「存得下来」和「换底图不跑位」 */
const pinPos = async i => {
  const st = await pins().nth(i).getAttribute("style");
  return {
    x: Number(/left:\s*([\d.]+)%/.exec(st || "")?.[1] ?? NaN),
    y: Number(/top:\s*([\d.]+)%/.exec(st || "")?.[1] ?? NaN)
  };
};

/** 在画布的 (x%, y%) 处点右键。传百分比而不是像素，断言才和存进去的东西同一个口径。 */
const rightClickAt = async (xPct, yPct) => {
  const box = await p.locator(".canvas").boundingBox();
  await p.mouse.click(box.x + (box.width * xPct) / 100, box.y + (box.height * yPct) / 100, { button: "right" });
};

// ── ① 入口与默认底图 ──
console.log("① 菜单里有入口，默认看到「南昌理工学院」");
ok((await p.locator(".el-menu").first().innerText()).includes("资源管理"), "侧边栏有资源管理");
await openMap();
const side = (await p.locator(".map-side").innerText()).replace(/\s+/g, " ");
console.log("   底图清单：" + side);
ok(side.includes("南昌理工学院"), "默认底图是「南昌理工学院」");
ok((await p.locator(".map-side .side-item.active").innerText()).includes("南昌理工学院"), "打开页面默认选中它");

// ── ② 占位底图 ──
console.log("② 还没传真图时画占位底图，不是空白页");
// ⚠ 选择器要钉死 .canvas-img：终端点和图例现在也是 svg（图标），
//   写成 ".canvas svg" 的话数进来的是一堆图标，这一条就永远绿/永远红。
ok((await p.locator(".canvas > svg.canvas-img").count()) === 1, "画的是占位 SVG");
ok((await p.locator(".canvas > img.canvas-img").count()) === 0, "没有真图");
ok((await p.locator(".bar-left").innerText()).includes("占位底图"), "工具栏上标明了这是占位底图");

// 之后的破坏性操作都在自己新建的 E2E地图 上做，不碰默认那张
console.log("   新建一张 E2E地图，后面的操作都在它上面做");
await p.locator(".side-head button").click();
await p.waitForTimeout(1200);
await p.locator(".el-message-box__input input").fill("E2E地图");
await p.locator(".el-message-box__btns button").last().click();
await p.waitForTimeout(2500);
ok((await p.locator(".map-side .side-item.active").innerText()).includes("E2E地图"), "新建后自动切过去了");

// ── ③ 右键添加终端，落点就是右键的位置 ──
console.log("③ 右键点在图上添加终端 —— 落点就是右键的那个点");
// 工具栏上不该再有「添加终端」按钮：加终端只有右键一条路，
// 两条路并存的话，工具栏那条仍然是「堆在左上角再拖」，等于没改。
ok(!(await p.locator(".bar-right").innerText()).includes("添加终端"), "工具栏上已经没有「添加终端」按钮了");

// 在画布 30% / 70% 处点右键
const RC = { x: 30, y: 70 };
await rightClickAt(RC.x, RC.y);
await p.waitForTimeout(600);
ok((await p.locator(".ctx-menu").count()) === 1, "右键弹出了菜单");
ok((await p.locator(".ctx-menu").innerText()).includes("在这里添加终端"), "菜单里有「在这里添加终端」");
await p.locator(".ctx-menu .ctx-item", { hasText: "在这里添加终端" }).click();
await p.waitForTimeout(2500);
{
  const dlg = p.locator(".el-dialog:visible").first();
  const n0 = await dlg.locator(".el-table__body .el-table__row").count();
  ok(n0 > 0, `可选终端 ${n0} 台`);
  // 先只选一台 —— 单台的落点必须**精确**落在右键那个点上，这是这一条的核心
  await dlg.locator(".el-table__body .el-table__row").nth(0).locator(".el-checkbox").click();
  await p.waitForTimeout(200);
  await dlg.locator(".el-dialog__footer .el-button--primary").click();
  await p.waitForTimeout(3000);
}
ok((await pins().count()) === 1, `图上 1 个点（实际 ${await pins().count()}）`);
{
  const q = await pinPos(0);
  console.log(`   右键在 (${RC.x}, ${RC.y})，终端落在 (${q.x.toFixed(1)}, ${q.y.toFixed(1)})`);
  ok(Math.abs(q.x - RC.x) < 2 && Math.abs(q.y - RC.y) < 2, "落点就是右键的位置 —— 这就是需求方要的「可以定位」");
}

// 再右键一次，这回选两台：第一台仍落在点上，另一台绕开一点，不能完全重叠。
// ⚠ 换个空位置点 —— 刚才那个点上已经有终端了，在终端上点右键弹的是「移出地图」，
//   不是「在这里添加」。这本身是对的：菜单按点中的是什么来变。
const RC2 = { x: 20, y: 30 };
await rightClickAt(RC2.x, RC2.y);
await p.waitForTimeout(600);
await p.locator(".ctx-menu .ctx-item", { hasText: "在这里添加终端" }).click();
await p.waitForTimeout(2500);
{
  const dlg = p.locator(".el-dialog:visible").first();
  for (const i of [0, 1]) {
    await dlg.locator(".el-table__body .el-table__row").nth(i).locator(".el-checkbox").click();
    await p.waitForTimeout(200);
  }
  await dlg.locator(".el-dialog__footer .el-button--primary").click();
  await p.waitForTimeout(3000);
}
ok((await pins().count()) === 3, `图上 3 个点（实际 ${await pins().count()}）`);
{
  const ps = [await pinPos(0), await pinPos(1), await pinPos(2)];
  console.log("   三个点：" + JSON.stringify(ps.map(q => ({ x: +q.x.toFixed(1), y: +q.y.toFixed(1) }))));
  const keys = new Set(ps.map(q => `${q.x.toFixed(2)},${q.y.toFixed(2)}`));
  ok(keys.size === 3, "一次选多台时不会叠成一个点（叠了就拖不开也数不出几台）");
}

// 再右键一次：已经上图的不该再出现在候选里
await rightClickAt(50, 50);
await p.waitForTimeout(600);
await p.locator(".ctx-menu .ctx-item", { hasText: "在这里添加终端" }).click();
await p.waitForTimeout(2500);
{
  const dlg = p.locator(".el-dialog:visible").first();
  const names = await dlg.locator(".el-table__body .el-table__row td:nth-child(3)").allInnerTexts();
  const onMap = await p.locator(".pin .pin-label").allInnerTexts();
  console.log("   图上：" + JSON.stringify(onMap) + " 候选里还有：" + names.length + " 台");
  ok(
    onMap.every(nm => !names.some(x => x.trim() === nm.trim())),
    "已经上图的终端不再出现在候选里"
  );
  await dlg.locator(".el-dialog__footer button", { hasText: "取消" }).click();
  await p.waitForSelector(".el-overlay", { state: "hidden", timeout: 10000 }).catch(() => undefined);
  await p.waitForTimeout(800);
}

// ── ④ 拖动改位置，存得下来 ──
console.log("④ 拖动改位置，刷新之后还在原处");
const before = await pinPos(0);
{
  const box = await p.locator(".canvas").boundingBox();
  const dot = await pins().nth(0).boundingBox();
  await p.mouse.move(dot.x + dot.width / 2, dot.y + 8);
  await p.mouse.down();
  // 拖到画布右下角附近，分几步走 —— 一步到位有些实现收不到 pointermove
  for (const f of [0.3, 0.55, 0.75]) {
    await p.mouse.move(box.x + box.width * f, box.y + box.height * f);
    await p.waitForTimeout(60);
  }
  await p.mouse.up();
  await p.waitForTimeout(1500);
}
const afterDrag = await pinPos(0);
console.log(`   拖动：${JSON.stringify(before)} → ${JSON.stringify(afterDrag)}`);
ok(Math.abs(afterDrag.x - before.x) > 5 || Math.abs(afterDrag.y - before.y) > 5, "位置确实变了");
await openMap();
const afterReload = await pinPos(0);
console.log("   刷新后：" + JSON.stringify(afterReload));
ok(
  Math.abs(afterReload.x - afterDrag.x) < 1.5 && Math.abs(afterReload.y - afterDrag.y) < 1.5,
  "刷新之后还在拖过去的位置 —— 存下来了"
);

// ── ⑤ 换底图，点不跑位 ──
console.log("⑤ 传一张真底图，占位消失，已摆好的点不跑位");
const posBeforeUpload = [await pinPos(0), await pinPos(1), await pinPos(2)];
await p.locator('.bar-right input[type="file"]').setInputFiles(IMG);
await p.waitForTimeout(4000);
ok((await p.locator(".canvas > img.canvas-img").count()) === 1, "换成真图了");
ok((await p.locator(".canvas > svg.canvas-img").count()) === 0, "占位 SVG 没了");
const bar = (await p.locator(".bar-left").innerText()).replace(/\s+/g, " ");
console.log("   工具栏：" + bar);
ok(bar.includes("1200") && bar.includes("800"), "读出了图片的原始尺寸 1200 × 800");
const posAfterUpload = [await pinPos(0), await pinPos(1), await pinPos(2)];
console.log("   换图前：" + JSON.stringify(posBeforeUpload));
console.log("   换图后：" + JSON.stringify(posAfterUpload));
ok(
  posBeforeUpload.every((q, i) => Math.abs(q.x - posAfterUpload[i].x) < 0.01 && Math.abs(q.y - posAfterUpload[i].y) < 0.01),
  "三个点一个都没跑位 —— 坐标存的是百分比，换图不受影响"
);

// ── ⑥ 右键移出地图 ──
console.log("⑥ 在终端上点右键 → 移出地图，终端本身不受影响");
const goneName = (await pins().nth(0).locator(".pin-label").innerText()).trim();
{
  const dot = await pins().nth(0).boundingBox();
  await p.mouse.click(dot.x + dot.width / 2, dot.y + 8, { button: "right" });
  await p.waitForTimeout(600);
  const menu = await p.locator(".ctx-menu").innerText();
  console.log("   菜单：" + menu.replace(/\s+/g, " "));
  ok(menu.includes(goneName), "菜单顶上写着是哪一台，不会点错");
  ok(menu.includes("移出地图"), "菜单里有「移出地图」");
  await p.locator(".ctx-menu .ctx-item", { hasText: "移出地图" }).click();
}
await p.waitForTimeout(1200);
await p.locator(".el-message-box__btns button").last().click();
await p.waitForTimeout(2500);
ok((await pins().count()) === 2, `图上剩 2 个点（实际 ${await pins().count()}）`);
{
  // 那台终端应该回到候选里 —— 说明只是从图上拿掉，终端还在
  await rightClickAt(50, 20);
  await p.waitForTimeout(600);
  await p.locator(".ctx-menu .ctx-item", { hasText: "在这里添加终端" }).click();
  await p.waitForTimeout(2500);
  const dlg = p.locator(".el-dialog:visible").first();
  const names = (await dlg.locator(".el-table__body .el-table__row td:nth-child(3)").allInnerTexts()).map(x => x.trim());
  ok(names.includes(goneName), `「${goneName}」回到了候选里，终端本身没被删`);
  await dlg.locator(".el-dialog__footer button", { hasText: "取消" }).click();
  await p.waitForSelector(".el-overlay", { state: "hidden", timeout: 10000 }).catch(() => undefined);
  await p.waitForTimeout(800);
}

// ── ⑧ 一状态一图标 ──
console.log("⑧ 不同状态画不同图标（不是只换颜色），图例只列图上有的");
{
  // 造几种状态出来：库里现成的有「在播(1)」「空闲(0)」「离线(netstate=0)」，
  // 对讲和寻呼没有，临时改两台 —— 跑完 UNSEED 会放回去。
  execSync(SQL("UPDATE terminal SET taskstate = 2 WHERE id = 5; UPDATE terminal SET taskstate = 5 WHERE id = 6"), {
    stdio: "pipe"
  });
  // 把这五台都摆上去。用一次右键多选，落点绕着撒开，正好顺带再验一次不重叠。
  await rightClickAt(50, 45);
  await p.waitForTimeout(600);
  await p.locator(".ctx-menu .ctx-item", { hasText: "在这里添加终端" }).click();
  await p.waitForTimeout(2500);
  {
    const dlg = p.locator(".el-dialog:visible").first();
    const rows = dlg.locator(".el-table__body .el-table__row");
    const n = await rows.count();
    for (let i = 0; i < Math.min(n, 5); i++) {
      await rows.nth(i).locator(".el-checkbox").click();
      await p.waitForTimeout(150);
    }
    await dlg.locator(".el-dialog__footer .el-button--primary").click();
    await p.waitForTimeout(3500);
  }

  const kinds = await p.locator(".pin").evaluateAll(els => els.map(e => e.dataset.kind));
  console.log("   图上各点的状态：" + JSON.stringify(kinds));
  const uniq = [...new Set(kinds)];
  ok(uniq.length >= 3, `至少画出了 3 种不同状态（实际 ${uniq.length} 种：${uniq.join("/")}）`);

  // 关键的一条：不同状态用的是**不同图标**，不是同一个圆点换颜色。
  // 比 svg 的第一条 path —— Element Plus 的图标就是一段 path，换了图标这段就变。
  const shapes = await p
    .locator(".pin .pin-icon svg path")
    .evaluateAll(els => els.map(e => (e.getAttribute("d") || "").slice(0, 40)));
  const byKind = new Map();
  kinds.forEach((k, i) => byKind.set(k, shapes[i]));
  console.log("   状态 → 图标：" + JSON.stringify([...byKind.keys()]));
  ok(new Set(byKind.values()).size === byKind.size, "每种状态的图标形状都不一样（不是只改了颜色）");

  // 图例：只列图上真的出现过的那几种
  const legend = p.locator(".legend");
  ok((await legend.count()) === 1, "画布上有图例");
  const legendText = await legend.innerText();
  console.log("   图例：" + legendText.replace(/\s+/g, " "));
  const LABEL = {
    idle: "空闲",
    playing: "播放中",
    intercom: "对讲中",
    paging: "寻呼中",
    stopped: "已停止",
    off: "离线",
    gone: "记录已失效"
  };
  ok(
    uniq.every(k => legendText.includes(LABEL[k])),
    "图上出现的每一种状态，图例里都有"
  );
  const absent = Object.entries(LABEL).filter(([k]) => !uniq.includes(k));
  ok(
    absent.every(([, v]) => !legendText.includes(v)),
    `图上没有的状态不进图例（${absent.map(([, v]) => v).join("、") || "无"}）`
  );

  // 状态变了图标要自己跟着换 —— 不然「一状态一图标」只在打开页面那一刻是准的，
  // 而巡检的人恰恰是把这一页开着不动的。
  // netstate / taskstate 是后台 C 服务直接写库的，所以这里也直接改库，页面一下都不碰。
  console.log("   页面完全不动，直接改库，看图标跟不跟");
  const first = await p.locator(".pin").first().getAttribute("data-terminal-id");
  execSync(SQL(`UPDATE terminal SET netstate = 0 WHERE id = ${first}`), { stdio: "pipe" });
  let live = null;
  for (let i = 0; i < 15; i++) {
    await p.waitForTimeout(1000);
    live = await p.locator(".pin").first().getAttribute("data-kind");
    if (live === "off") break;
  }
  ok(live === "off", `改库后图标自己换成了「离线」（实际 ${live}）—— 无感刷新接上了`);
  execSync(SQL(`UPDATE terminal SET netstate = 1 WHERE id = ${first}`), { stdio: "pipe" });
  await p.waitForTimeout(3000);
}

// ── ⑦ 删底图 ──
console.log("⑦ 删掉 E2E地图，连带清掉图上的摆放");
await p.locator(".bar-right button", { hasText: "删除底图" }).click();
await p.waitForTimeout(1200);
await p.locator(".el-message-box__btns button").last().click();
await p.waitForTimeout(3000);
const side2 = (await p.locator(".map-side").innerText()).replace(/\s+/g, " ");
console.log("   剩下的底图：" + side2);
ok(!side2.includes("E2E地图"), "E2E地图没了");
ok(side2.includes("南昌理工学院"), "默认那张还在，没被误伤");
{
  const left = execSync(
    `mariadb -uroot -N audioserver -e "SELECT COUNT(*) FROM map_terminal mt LEFT JOIN map_image mi ON mi.id=mt.mapid WHERE mi.id IS NULL"`,
    { encoding: "utf8" }
  ).trim();
  ok(left === "0", `库里没有指向已删底图的摆放记录（实际 ${left} 条）`);
}

await b.close();
console.log(fails ? `\n✗ ${fails} 条没过` : "\n✓ 全过");
process.exit(fails ? 1 : 0);
