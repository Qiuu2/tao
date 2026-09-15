/*
 * 用户组的功能权限：22 项，按五组排列，改完能存下去。
 *
 * # 它盯的是什么
 *
 * 原来只有 13 项 —— 那 13 列是从旧版 ok112 原样继承的。新 web 左侧菜单比旧版多出
 * 好些页，这些页当时要么**借**别的权限位、要么**根本没有门**（云广播终端的
 * 「全部清除」、任务传送的「删除离线音乐」只要登录就能点）。现场原话：
 * 「用户组的功能权限少了。仔细查看web页面左侧列表功能。」于是一页一把钥匙。
 *
 * 断言：
 *   ① 新建用户组弹窗里有 22 个权限复选框
 *   ② 五个分组的标题都在（资源管理 / 任务管理 / 云广播管理 / 噪声检测 / 系统）
 *   ③ 新增那 9 项的名字都能找到
 *   ④ 「全选」把 22 个全勾上，「全不选」全清掉
 *   ⑤ 分组里的「全选」只动这一组
 *   ⑥ 建一个只勾「任务传送」的组，存下去再打开，仍然只勾着那一项
 *      —— 这一条盯的是「列名与 Scan 目标错位」：22 列四处写，
 *         顺序错了不会报错，表现就是「勾了 A、存下来的是 B」
 *   ⑦ 系统组（id=1）的复选框全勾上且全部置灰
 *
 * # 怎么跑
 *
 *   node e2e/usergroup-rights.mjs
 *
 * ⚠ 会建一个用户组，跑完自己删掉。
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
const p = await b.newPage({ viewport: { width: 1600, height: 1000 } });
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

await p.goto(BASE + "/#/user/group", { waitUntil: "domcontentloaded" });
await p.reload({ waitUntil: "domcontentloaded" });
await p.waitForSelector(".table-box", { timeout: 25000 });
await p.waitForTimeout(2200);

const GROUP_NAME = "E2E权限位测试组";
const boxes = () => p.locator(".rights-grid .el-checkbox");
const checkedCount = () => p.locator(".rights-grid .el-checkbox.is-checked").count();
const closeDialog = async () => {
  await p.locator(".el-dialog__headerbtn").first().click();
  await p.waitForTimeout(600);
};

/*
 * 删掉一个用户组。
 *
 * ⚠ 删除弹窗要**逐字输入组名**才会放开那个红按钮
 * （`:disabled="del.confirmText !== del.row?.name"`）——
 * 直接去点它只会等到超时，报出来是「element is not enabled」，
 * 看上去像权限不够，其实是没填确认词。
 */
const deleteGroup = async name => {
  const row = p.locator("tbody tr", { hasText: name }).first();
  if (!(await row.count())) return false;
  await row.locator("button", { hasText: "删除" }).first().click();
  await p.waitForTimeout(1200);
  // ⚠ 弹窗里**没有**组名的文字（组名只在输入框的 placeholder 里），
  //   所以按标题定位，不能按 hasText: name 找。
  const box = p.locator(".el-dialog").filter({ hasText: "删除用户组" }).last();
  await box.locator("input").last().fill(name);
  await p.waitForTimeout(300);
  await box.locator(".el-dialog__footer button").last().click();
  await p.waitForTimeout(2000);
  return true;
};

/*
 * 先把上一次跑剩下的测试组清掉。
 *
 * run-all.sh 每个脚本之前都会把库还原到基线，所以整套跑的时候用不上这一段；
 * 但单独重跑这个脚本时，上一次留下的同名组会让「新建」报「名称已被使用」，
 * 然后后面所有断言都跟着崩。
 */
if (await deleteGroup(GROUP_NAME)) {
  await p.reload({ waitUntil: "domcontentloaded" });
  await p.waitForTimeout(2000);
}

console.log("①②③ 新建弹窗里的权限清单");
await p.locator("button", { hasText: "新建用户组" }).first().click();
await p.waitForTimeout(1200);
const n = await boxes().count();
ok(n === 22, `权限复选框 ${n} 个，应为 22`);

const groupTitles = (await p.locator(".right-group-title").allInnerTexts()).map(s => s.trim());
for (const g of ["资源管理", "任务管理", "云广播管理", "噪声检测", "系统"]) {
  ok(groupTitles.includes(g), `分组「${g}」在，实际：${groupTitles.join(" / ")}`);
}
const labels = (await p.locator(".rights-grid .el-checkbox__label").allInnerTexts()).map(s => s.trim());
for (const label of ["地图", "启用管理", "云广播终端", "音乐传输", "任务传送", "噪声设备", "声场分区", "声场任务", "接口调用平台"]) {
  ok(labels.includes(label), `新增项「${label}」在`);
}

console.log("④ 全选 / 全不选");
await p.locator(".rights-ops button", { hasText: "全选" }).first().click();
await p.waitForTimeout(400);
ok((await checkedCount()) === 22, `全选之后勾上 ${await checkedCount()} 个，应为 22`);
await p.locator(".rights-ops button", { hasText: "全不选" }).first().click();
await p.waitForTimeout(400);
ok((await checkedCount()) === 0, `全不选之后还剩 ${await checkedCount()} 个，应为 0`);

console.log("⑤ 分组全选只动这一组");
// 云广播管理这一组有 3 项
const cloudGroup = p.locator(".right-group").filter({ hasText: "云广播管理" }).first();
await cloudGroup.locator("button", { hasText: "全选" }).first().click();
await p.waitForTimeout(400);
ok((await checkedCount()) === 3, `只勾上了 ${await checkedCount()} 个，应为 3（云广播三页）`);

console.log("⑥ 只勾一项，存下去再打开还是那一项");
await p.locator(".rights-ops button", { hasText: "全不选" }).first().click();
await p.waitForTimeout(300);
const transferBox = p.locator(".rights-grid .el-checkbox").filter({ hasText: "任务传送" }).first();
await transferBox.click();
await p.waitForTimeout(300);
ok((await checkedCount()) === 1, "只勾了「任务传送」一项");

await p.locator(".el-dialog .el-input__inner").first().fill(GROUP_NAME);
await p.locator(".el-dialog__footer button").last().click();
await p.waitForTimeout(2500);
// 存不下去时把界面上那句提示打出来，不然只能看到一个「点不到行内按钮」的超时
if (await p.locator(".el-dialog").first().isVisible()) {
  const msg = await p.locator(".el-message, .el-notification").allInnerTexts();
  ok(false, "弹窗没关掉，多半是没存下去：" + (msg.join(" | ") || "（界面上没有提示）"));
  await closeDialog();
}

// 重新打开这个组
// 行内那个按钮在普通组上写的是「编辑」，在系统组上写的是「查看 / 改描述」
await p.locator("tbody tr", { hasText: GROUP_NAME }).first().locator("button", { hasText: "编辑" }).first().click();
await p.waitForTimeout(1800);
const back = await checkedCount();
ok(back === 1, `再打开时勾着 ${back} 项，应为 1`);
const backLabel = (await p.locator(".rights-grid .el-checkbox.is-checked .el-checkbox__label").allInnerTexts())
  .map(s => s.trim())
  .join("");
ok(backLabel === "任务传送", `存回来的是「${backLabel}」，应为「任务传送」—— 不一致说明 22 列的顺序在某一处对不上`);
await closeDialog();

console.log("⑦ 系统组全勾且置灰");
await p.locator("tbody tr", { hasText: "system group" }).first().locator("button", { hasText: "查看" }).first().click();
await p.waitForTimeout(1800);
ok((await checkedCount()) === 22, `系统组勾着 ${await checkedCount()} 个，应为 22`);
const disabled = await p.locator(".rights-grid .el-checkbox.is-disabled").count();
ok(disabled === 22, `置灰了 ${disabled} 个，应为 22`);
await closeDialog();

console.log("收尾：删掉测试用户组");
await deleteGroup(GROUP_NAME);
await p.waitForTimeout(800);
ok((await p.locator("tbody tr", { hasText: GROUP_NAME }).count()) === 0, "测试用户组已删掉");

await b.close();
console.log(fails ? `\n✗ ${fails} 条断言没过` : "\n✓ 全部通过");
process.exit(fails ? 1 : 0);
