/*
 * 终端管理「勾选」的回归测试。
 *
 * # 它盯的是什么
 *
 * ProTable 给每个 selection 列都加了 `:reserve-selection="true"`，
 * 让勾选能跨刷新、跨翻页保留住 —— 翻到第 2 页再翻回来，第 1 页勾的还在。
 * 但 Element Plus 连**已经被删掉的行**也一起留着：
 *
 *   现网实测：勾 1 台 → 重新注册（即删除）→ 列表少了一行，
 *   可按钮仍然显示「批量操作(1)」；再勾一台别的就变成 (2)，
 *   下一次批量操作会把那个**已经不存在的 id** 一起发上去。
 *
 * 修法在 ProTable 的 dropVanishedSelections：同样的请求参数下重新拉一次，
 * 之前看得见、现在看不见的行，从勾选里摘掉。
 *
 * 所以这个测试有四条，缺一不可：
 *
 *   ① 删完之后勾选清零          —— 修的就是这个
 *   ② 下一次批量操作只带新勾的那个 id
 *   ③ 翻页仍然保住勾选          —— 别把 reserve-selection 的正事误伤了
 *   ④ 无感刷新不弄丢勾选        —— 后台 C 服务改一下库就触发一次刷新，
 *                                  那一刷不能把人勾好的东西冲掉
 *
 * # 怎么跑
 *
 * 和 login-slider.mjs 一样（后端 + vite + 一份带终端数据的库）：
 *
 *   node e2e/terminal-selection.mjs
 *
 * ⚠ 它会**真的删掉一台终端**。跑之前先备份，或者用一次性的测试库：
 *
 *   mariadb-dump -uroot --no-tablespaces audioserver > /tmp/before.sql
 *   node e2e/terminal-selection.mjs
 *   mariadb -uroot audioserver < /tmp/before.sql
 */
const BASE = process.env.E2E_BASE || "http://127.0.0.1:5199";
const CHROME = process.env.E2E_CHROME || "/opt/pw-browsers/chromium-1194/chrome-linux/chrome";
/** 让「别的程序」改一下 terminal 表，用来触发无感刷新。库不是本地的就改这一条 */
const POKE_DB =
  process.env.E2E_POKE_DB ||
  `mariadb -uroot audioserver -e "UPDATE terminal SET netstate=IF(netstate=1,0,1) WHERE id=(SELECT * FROM (SELECT MAX(id) FROM terminal) x)"`;

const { chromium } = await import(process.env.E2E_PLAYWRIGHT || "/opt/node22/lib/node_modules/playwright/index.mjs");
const { execSync } = await import("node:child_process");

let fails = 0;
const ok = (c, m) => {
  console.log((c ? "  ✓ " : "  ✗ ") + m);
  if (!c) fails++;
};

const b = await chromium.launch({ executablePath: CHROME });
const p = await b.newPage({ viewport: { width: 1500, height: 900 } });

/** 批量操作请求（只收 PUT，GET 列表不算） */
const sent = [];
p.on("request", r => {
  const u = r.url();
  if (/\/api\/terminals\/(start|stop)/.test(u) && r.method() === "PUT") {
    sent.push(r.method() + " " + u.replace(/^https?:\/\/[^/]+/, "") + " body=" + (r.postData() || ""));
  }
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
  // ⚠ 先把可能还开着的下拉 / 弹框关掉。它们的遮罩会盖住表格，
  //   后面点复选框就会「等 30 秒然后超时」，而原因跟被测的功能毫无关系。
  await p.keyboard.press("Escape").catch(() => undefined);
  await p.waitForTimeout(300);
  await p.goto(BASE + "/#/terminal", { waitUntil: "domcontentloaded" });
  await p.waitForSelector(".el-table__row", { timeout: 25000 });
  // 等遮罩真的消失，再动手
  await p
    .waitForFunction(() => !document.querySelector(".el-overlay, .el-dropdown__popper:not([style*='display: none'])"), null, {
      timeout: 8000
    })
    .catch(() => undefined);
  await p.waitForTimeout(1500);
};
/** 「批量操作(N)」里的那个 N */
const cnt = async () => {
  const s = (await p.locator(".header-left button").first().innerText()).replace(/\s+/g, "");
  const m = s.match(/\((\d+)\)/);
  return m ? Number(m[1]) : 0;
};
/**
 * 勾第 i 行，并确认真的勾上了。
 *
 * ⚠ 要重试。这一页开着无感刷新：上一步动过终端（启用/删除）本身就会触发一次
 *   重新拉列表，正好撞上点击的话，那一下会被重渲染吃掉。
 *   真人遇到这种情况也是再点一次 —— 这里照做，而不是把等待时间调长了赌。
 */
const tick = async (i, tries = 3) => {
  const rowCb = () => p.locator(".el-table__body .el-table__row").nth(i).locator(".el-checkbox");
  for (let n = 0; n < tries; n++) {
    if ((await rowCb().getAttribute("class"))?.includes("is-checked")) return true;
    await rowCb()
      .click({ timeout: 10000 })
      .catch(() => undefined);
    await p.waitForTimeout(800);
  }
  return (await rowCb().getAttribute("class"))?.includes("is-checked") ?? false;
};

await openTerminals();

// ── ① 删完之后勾选必须清零 ──
console.log("① 勾 1 台 → 重新注册（即删除）→ 勾选该清零");
await tick(0);
ok((await cnt()) === 1, "勾上之后是 (1)");
await p.locator(".header-left button").first().click();
await p.waitForTimeout(500);
await p.locator(".batch-menu .el-dropdown-menu__item", { hasText: "重新注册" }).first().click();
await p.waitForTimeout(1200);
await p.locator(".el-message-box__btns button").last().click(); // 「确认重新注册」
await p.waitForTimeout(1500);
await p.locator(".el-dialog:visible .el-dialog__footer button").last().click(); // 删除预览里的确认
await p.waitForTimeout(2800);
ok((await cnt()) === 0, `删完之后勾选清零（实际 ${await cnt()}）—— 这就是那个 bug`);

// ── ② 下一次批量操作只带新勾的那个 ──
console.log("② 再勾一台别的，不该把删掉的那个带上");
await tick(0);
ok((await cnt()) === 1, `只勾了 1 台（实际 ${await cnt()}）`);
const pickedId = (await p.locator(".el-table__body .el-table__row").nth(0).locator("td").nth(1).innerText()).trim();
sent.length = 0;
await p.locator(".header-left button").first().click();
await p.waitForTimeout(400);
await p.locator(".batch-menu .el-dropdown-menu__item", { hasText: "启用终端" }).first().click();
await p.waitForTimeout(2000);
if (await p.locator(".el-message-box__btns button").count()) {
  await p.locator(".el-message-box__btns button").last().click();
  await p.waitForTimeout(1800);
}
ok(sent.length === 1 && /"ids":\[\d+\]/.test(sent[0]), `只带了 1 个 id 上去：${sent[0] || "(没发请求)"}`);
ok(sent.length === 1 && sent[0].includes(`"ids":[${pickedId}]`), `带的正是刚勾的那台（id=${pickedId}）`);

// ── ③ 翻页仍然保住勾选 ──
console.log("③ 翻页不能把勾选弄丢（reserve-selection 的正事不能被误伤）");
await openTerminals(); // 重新进一次，拿个干净状态；上面那个下拉会挡住分页器
await tick(0);
await tick(1);
const n3 = await cnt();
ok(n3 === 2, `勾了 2 台（实际 ${n3}）`);
const next = p.locator(".el-pagination .btn-next");
if (await next.isEnabled()) {
  await next.click();
  await p.waitForTimeout(2000);
  const onPage2 = await cnt();
  await p.locator(".el-pagination .btn-prev").click();
  await p.waitForTimeout(2000);
  ok((await cnt()) === n3, `翻到第 2 页再翻回来，勾选还在（${n3} → 第2页 ${onPage2} → ${await cnt()}）`);
} else {
  console.log("   只有一页，跳过（库里终端太少）");
}

// ── ④ 无感刷新不弄丢勾选 ──
console.log("④ 别的程序改库触发无感刷新，勾选不能被冲掉");
const before = await cnt();
execSync(POKE_DB + " 2>/dev/null");
await p.waitForTimeout(6000); // 服务端 1.5s 轮询 + 前端 2s 刹车，6 秒够刷到
ok((await cnt()) === before, `刷新之后勾选还在（${before} → ${await cnt()}）`);
execSync(POKE_DB + " 2>/dev/null"); // 翻回去，不留痕迹

await b.close();
console.log(fails ? `\n✗ ${fails} 条没过` : "\n✓ 全部通过");
process.exit(fails ? 1 : 0);
