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
 *   ③ 添加终端：只列还没上图的，摆完不会叠成一团
 *   ④ 拖动改位置**存得下来**，刷新之后还在原处
 *   ⑤ 换底图：传一张真图，占位消失，**已经摆好的点不跑位**（坐标是百分比）
 *   ⑥ 双击把终端移出地图，终端本身不受影响
 *   ⑦ 删底图会连带清掉图上的摆放
 *
 * ⑤ 是这一套设计的核心：坐标存的是图上的百分比，不是像素，
 * 所以换底图、换分辨率、收起侧边栏都不该让点跑位。
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
    "DELETE mt FROM map_terminal mt JOIN map_image mi ON mi.id=mt.mapid WHERE mi.name LIKE 'E2E%'; DELETE FROM map_image WHERE name LIKE 'E2E%'"
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
ok((await p.locator(".canvas svg").count()) === 1, "画的是占位 SVG");
ok((await p.locator(".canvas img").count()) === 0, "没有真图");
ok((await p.locator(".bar-left").innerText()).includes("占位底图"), "工具栏上标明了这是占位底图");

// 之后的破坏性操作都在自己新建的 E2E地图 上做，不碰默认那张
console.log("   新建一张 E2E地图，后面的操作都在它上面做");
await p.locator(".side-head button").click();
await p.waitForTimeout(1200);
await p.locator(".el-message-box__input input").fill("E2E地图");
await p.locator(".el-message-box__btns button").last().click();
await p.waitForTimeout(2500);
ok((await p.locator(".map-side .side-item.active").innerText()).includes("E2E地图"), "新建后自动切过去了");

// ── ③ 添加终端 ──
console.log("③ 添加终端：只列还没上图的，摆完不叠在一起");
await p.locator(".bar-right button", { hasText: "添加终端" }).click();
await p.waitForTimeout(2500);
{
  const dlg = p.locator(".el-dialog:visible").first();
  const n0 = await dlg.locator(".el-table__body .el-table__row").count();
  ok(n0 > 0, `可选终端 ${n0} 台`);
  for (const i of [0, 1, 2]) {
    await dlg.locator(".el-table__body .el-table__row").nth(i).locator(".el-checkbox").click();
    await p.waitForTimeout(200);
  }
  await dlg.locator(".el-dialog__footer .el-button--primary").click();
  await p.waitForTimeout(3000);
}
ok((await pins().count()) === 3, `图上 3 个点（实际 ${await pins().count()}）`);
{
  const ps = [await pinPos(0), await pinPos(1), await pinPos(2)];
  console.log("   初始位置：" + JSON.stringify(ps));
  const xs = new Set(ps.map(q => q.x));
  ok(xs.size === 3, "三个点的横坐标各不相同，没叠成一团");
}
// 再打开一次：已经上图的不该再出现在候选里
await p.locator(".bar-right button", { hasText: "添加终端" }).click();
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
ok((await p.locator(".canvas img").count()) === 1, "换成真图了");
ok((await p.locator(".canvas svg").count()) === 0, "占位 SVG 没了");
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

// ── ⑥ 双击移出地图 ──
console.log("⑥ 双击把终端移出地图，终端本身不受影响");
const goneName = (await pins().nth(0).locator(".pin-label").innerText()).trim();
await pins().nth(0).dblclick();
await p.waitForTimeout(1200);
await p.locator(".el-message-box__btns button").last().click();
await p.waitForTimeout(2500);
ok((await pins().count()) === 2, `图上剩 2 个点（实际 ${await pins().count()}）`);
{
  // 那台终端应该回到候选里 —— 说明只是从图上拿掉，终端还在
  await p.locator(".bar-right button", { hasText: "添加终端" }).click();
  await p.waitForTimeout(2500);
  const dlg = p.locator(".el-dialog:visible").first();
  const names = (await dlg.locator(".el-table__body .el-table__row td:nth-child(3)").allInnerTexts()).map(x => x.trim());
  ok(names.includes(goneName), `「${goneName}」回到了候选里，终端本身没被删`);
  await dlg.locator(".el-dialog__footer button", { hasText: "取消" }).click();
  await p.waitForSelector(".el-overlay", { state: "hidden", timeout: 10000 }).catch(() => undefined);
  await p.waitForTimeout(800);
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
