/*
 * 遥控任务「修改」的回归测试。
 *
 * # 它盯的是什么
 *
 * 这一页原来只有「添加映射 / 删除映射」，列清单严格照 :80 —— 那边就没有行内
 * 修改入口，改一条映射只能删掉重建。需求方要求补一个「修改」。
 *
 * 后端的 PUT /api/remote-keys/{id} 和 api 层的 updateRemoteApi 本来就在，
 * 之前只是没把入口放出来，所以这次改的全是前端。
 *
 * 六条：
 *   ① 列表上有「操作 → 修改」
 *   ② 点开是同一个弹窗，标题是「修改映射 · <名字>」，且三项都回填了
 *   ③ 改名字能存下，列表跟着变
 *   ④ 改键号能存下 —— 提交要按**打开时**的旧键号定位记录，不是新键号
 *   ⑤ 改自己的映射时，树上不该给自己挂「已被键 N 绑走」的黄标
 *   ⑥ 改完之后再打开，回填的是改过的值（不是缓存里的旧值）
 *
 * # 怎么跑
 *
 *   node e2e/remote-edit.mjs
 *
 * ⚠ 会**真的改库**：自己先建一条映射，改两轮，最后删掉。中途崩了要手工清：
 *   DELETE FROM shortcutkeytask WHERE keyname LIKE 'E2E%';
 */
const BASE = process.env.E2E_BASE || "http://127.0.0.1:5199";
const CHROME = process.env.E2E_CHROME || "/opt/pw-browsers/chromium-1194/chrome-linux/chrome";
const SQL = q => `mariadb -uroot audioserver -e "${q}"`;
const UNSEED = process.env.E2E_UNSEED_DB || SQL("DELETE FROM shortcutkeytask WHERE keyname LIKE 'E2E%'");

const { chromium } = await import(process.env.E2E_PLAYWRIGHT || "/opt/node22/lib/node_modules/playwright/index.mjs");
const { execSync } = await import("node:child_process");

let fails = 0;
const ok = (c, m) => {
  console.log((c ? "  ✓ " : "  ✗ ") + m);
  if (!c) fails++;
};

const cleanup = () => {
  try {
    execSync(UNSEED, { stdio: "pipe" });
  } catch {
    console.log("  ! 清理失败，手工执行：" + UNSEED);
  }
};
process.on("exit", cleanup);
execSync(UNSEED, { stdio: "pipe" }); // 上一次跑崩留下的先清掉

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

const openPage = async () => {
  await p.goto(BASE + "/#/remote", { waitUntil: "domcontentloaded" });
  await p.waitForSelector(".table-box", { timeout: 25000 });
  await p.waitForTimeout(2000);
};
/** 关掉还开着的弹窗，再刷新列表 —— hash 路由下 goto 同一个地址不发生导航 */
const closeAndReload = async () => {
  const cancel = p.locator(".el-dialog:visible .el-dialog__footer button", { hasText: "取消" });
  if (await cancel.count()) {
    await cancel.first().click();
    await p.waitForSelector(".el-overlay", { state: "hidden", timeout: 10000 }).catch(() => undefined);
  }
  await p.reload({ waitUntil: "domcontentloaded" });
  await p.waitForSelector(".table-box", { timeout: 25000 });
  await p.waitForTimeout(2000);
};
/** 列表里找一行，返回 {名字, 键号文字} */
const rowOf = async name => {
  const rs = p.locator(".el-table__body .el-table__row");
  for (let i = 0; i < (await rs.count()); i++) {
    const td = await rs.nth(i).locator("td").allInnerTexts();
    if ((td[1] || "").includes(name)) return { i, name: td[1].trim(), key: (td[2] || "").trim() };
  }
  return null;
};
/** 在弹窗里选中第一个可选任务 */
const pickFirstTask = async dlg => {
  const nodes = dlg.locator(".rk-tree .el-tree-node__content .el-checkbox");
  for (let i = 0; i < (await nodes.count()); i++) {
    const label = await nodes.nth(i).locator("xpath=../..").innerText();
    if (/文件广播|采播|功放/.test(label) && (await nodes.nth(i).count())) continue;
  }
  // 叶子节点才有 taskId —— 直接点最后一个（分组节点排在前面）
  const leaves = dlg.locator(".rk-tree .el-tree-node__children .el-tree-node__content");
  const n = await leaves.count();
  if (!n) return false;
  await leaves.nth(0).click();
  await p.waitForTimeout(600);
  return true;
};

await openPage();

// ── ① 列表上有修改 ──
console.log("① 列表上有「操作 → 修改」");
const heads = (await p.locator(".el-table__header-wrapper th").allInnerTexts()).map(x => x.trim());
console.log("   表头：" + JSON.stringify(heads));
ok(heads.includes("操作"), "有「操作」列");
ok(
  heads.every(h => !/[a-z][a-zA-Z]*\.[a-zA-Z]/.test(h)),
  "表头没有漏出未翻译的 i18n key"
);

// 先建一条自己的映射，后面拿它来改
console.log("   先建一条 E2E 映射");
await p.locator(".header-left button").first().click();
await p.waitForTimeout(1500);
{
  const dlg = p.locator(".el-dialog:visible").first();
  await dlg.locator(".el-form-item", { hasText: "映射名称" }).locator("input").fill("E2E原名");
  await p.waitForTimeout(300);
  // ⚠ 键号默认是 1，而演示库里键 1 已经被占了 —— 一个键只能绑一条映射，
  //   照默认值提交会被服务端按「键已占用」挡下来。挑一个空着的键。
  const ks = dlg.locator(".el-form-item", { hasText: "映射按键" }).locator(".el-select").first();
  await ks.click();
  await p.waitForTimeout(800);
  await p.locator(".el-select-dropdown:visible .el-select-dropdown__item").nth(4).click(); // 键 5
  await p.waitForTimeout(400);
  ok(await pickFirstTask(dlg), "选到了一个任务");
  await dlg.locator(".el-dialog__footer .el-button--primary").click();
  await p.waitForTimeout(2500);
  const err = p.locator(".el-message--error, .el-message--warning");
  if (await err.count()) console.log("   服务端拒绝了：" + (await err.first().innerText()).trim());
}
await closeAndReload();
let row = await rowOf("E2E原名");
if (!row) console.log("   列表当前内容：" + (await p.locator(".el-table__body").first().innerText()).replace(/\s+/g, " "));
ok(!!row, `建好了：${JSON.stringify(row)}`);
const origKey = row.key;

// ── ② 点修改，弹窗回填 ──
console.log("② 点「修改」，弹窗标题和回填");
await p.locator(".el-table__body .el-table__row").nth(row.i).locator("button", { hasText: "修改" }).click();
await p.waitForTimeout(2000);
{
  const dlg = p.locator(".el-dialog:visible").first();
  const title = (await dlg.locator(".el-dialog__title").innerText()).trim();
  console.log("   标题：" + title);
  ok(title.includes("修改") && title.includes("E2E原名"), `标题是「${title}」`);
  const nameVal = await dlg.locator(".el-form-item", { hasText: "映射名称" }).locator("input").inputValue();
  ok(nameVal === "E2E原名", `名称回填了：${nameVal}`);
  const checked = await dlg.locator(".rk-tree .el-checkbox.is-checked").count();
  ok(checked === 1, `任务回填了（勾中 ${checked} 项）`);

  // ── ⑤ 不该给自己挂「已被键 N 绑走」 ──
  const selfWarn = await dlg.locator(".rk-tree .el-checkbox.is-checked").locator("xpath=../..").innerText();
  console.log("   勾中那行：" + selfWarn.replace(/\s+/g, " "));
  ok(!/已被键/.test(selfWarn), "改自己的映射时，树上没给自己挂「已被键 N 绑走」");

  // ── ③④ 改名字 + 改键号 ──
  console.log("③④ 改名字和键号一起存");
  await dlg.locator(".el-form-item", { hasText: "映射名称" }).locator("input").fill("E2E改过的名字");
  await p.waitForTimeout(300);
  const keySel = dlg.locator(".el-form-item", { hasText: "映射按键" }).locator(".el-select").first();
  await keySel.click();
  await p.waitForTimeout(800);
  const opts = p.locator(".el-select-dropdown:visible .el-select-dropdown__item");
  // 挑一个和当前不一样的键号
  const want = origKey.includes("7") ? 6 : 7;
  await opts.nth(want - 1).click();
  await p.waitForTimeout(500);
  await dlg.locator(".el-dialog__footer .el-button--primary").click();
  await p.waitForTimeout(2500);
}
await closeAndReload();
row = await rowOf("E2E改过的名字");
console.log("   改完之后：" + JSON.stringify(row));
ok(!!row, "名字改成了「E2E改过的名字」");
ok(!(await rowOf("E2E原名")), "旧名字不在列表里了（是改的，不是又建了一条）");
ok(row && row.key !== origKey, `键号也改了：${origKey} → ${row?.key}`);

// ── ⑥ 再打开，回填的是改过的值 ──
console.log("⑥ 再打开，回填的是改过的值");
await p.locator(".el-table__body .el-table__row").nth(row.i).locator("button", { hasText: "修改" }).click();
await p.waitForTimeout(2000);
{
  const dlg = p.locator(".el-dialog:visible").first();
  const nameVal = await dlg.locator(".el-form-item", { hasText: "映射名称" }).locator("input").inputValue();
  // ⚠ el-select 的内层 input 读出来是空串，选中项的文字在外层元素上。
  //   第一版用 inputValue() 比，拿到空串，而 "7 键".includes("") 恒为 true ——
  //   那一条永远绿，是假绿。改成读显示出来的文字。
  const keyVal = (await dlg.locator(".el-form-item", { hasText: "映射按键" }).locator(".el-select").first().innerText())
    .replace(/\s+/g, "")
    .trim();
  console.log(`   回填：名称「${nameVal}」按键「${keyVal}」`);
  ok(nameVal === "E2E改过的名字", "名称是改过的值");
  const wantN = row.key.replace(/\D/g, "");
  const gotN = keyVal.replace(/\D/g, "");
  ok(gotN !== "" && gotN === wantN, `按键也是改过的值（列表 ${row.key} / 弹窗 ${keyVal}）`);
}

// ── 收尾 ──
await closeAndReload();
row = await rowOf("E2E改过的名字");
await p.locator(".el-table__body .el-table__row").nth(row.i).locator(".el-checkbox").click();
await p.waitForTimeout(500);
await p.locator(".header-left button").nth(1).click();
await p.waitForTimeout(1200);
if (await p.locator(".el-message-box__btns button").count()) {
  await p.locator(".el-message-box__btns button").last().click();
  await p.waitForTimeout(2000);
}
await closeAndReload();
ok(!(await rowOf("E2E")), "E2E 映射已删干净");

await b.close();
console.log(fails ? `\n✗ ${fails} 条没过` : "\n✓ 全过");
process.exit(fails ? 1 : 0);
