/*
 * 任务传送：任务管理树 + 服务器任务 / 云广播任务两个页签。
 *
 * # 它盯的是什么
 *
 * 这一页早前只做了「云广播任务」那一半、而且只有 3 个动作，左边那棵树根本没有。
 * 现场那句「任务传送不是有任务管理树吗？不是有服务器任务和云广播任务吗？到哪云了」
 * 说的就是它。对照的是旧版 set_offline.php + offlinetask/offlinetask_form.html：
 *
 *   任务管理
 *    ├─ 服务器任务  task 里 offlinestate=0 的      按钮：空闲离线 立即离线
 *    └─ 云广播任务  offlinetask 里的副本            按钮：上面两个 + 空闲删除 立即删除
 *                                                        停止离线 离线播放 停止离线播放
 *                                                        删除离线音乐
 *
 * 断言：
 *   ① 左边那棵树在，两个叶子都在
 *   ② 默认落在「服务器任务」，列表有数据（库里有 13 条没下发过的任务）
 *   ③ 服务器任务的按钮**只有两个**
 *   ④ 点「云广播任务」，列表接口换成 /api/transfer/tasks（不是 server-tasks）
 *   ⑤ 云广播任务的按钮是 8 个，且「删除离线音乐」是红的
 *   ⑥ 换类型页签（作息方案/文件广播/全部）请求跟着变，且带对 kind
 *   ⑦ 切回服务器任务，接口换回 server-tasks —— ProTable 的 request-api 是
 *      setup 时抓走的，不靠 :key 重挂就会一直问旧的那个接口
 *   ⑧ 行内「终端 / 媒体」两个链接在服务器任务下问的是**源表**那两个接口
 *
 * # 怎么跑
 *
 *   node e2e/transfer-tree.mjs
 *
 * ⚠ 只读：一个按钮都不点，不改库。
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
const p = await b.newPage({ viewport: { width: 1600, height: 950 } });
p.on("pageerror", e => {
  console.log("  [pageerror] " + String(e).slice(0, 200));
  fails++;
});

// 把这一页发出去的列表请求记下来，⑦ 靠它才验得到「接口真的换了」
const calls = [];
p.on("request", r => {
  const u = r.url();
  if (u.includes("/api/transfer/")) calls.push(u);
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

// ⚠ 已经在这个 hash 上时 goto 什么都不做，必须真 reload 才会重新挂载
await p.goto(BASE + "/#/transfer", { waitUntil: "domcontentloaded" });
await p.reload({ waitUntil: "domcontentloaded" });
await p.waitForSelector(".transfer-page", { timeout: 25000 });
await p.waitForTimeout(2200);

const rows = () => p.locator(".transfer-page .el-table__body-wrapper tbody tr").count();
const btnTexts = async () => {
  const n = await p.locator(".header-left button").count();
  const out = [];
  for (let i = 0; i < n; i++) out.push((await p.locator(".header-left button").nth(i).innerText()).replace(/\s+/g, ""));
  return out;
};
const last = () => calls[calls.length - 1] ?? "";

console.log("① 任务管理树");
ok(await p.locator(".tree-pane .el-tree").isVisible(), "左边有一棵树");
const leaves = (await p.locator(".tree-pane .el-tree-node__label").allInnerTexts()).map(s => s.trim());
ok(leaves.includes("服务器任务"), "叶子里有「服务器任务」，实际：" + leaves.join(" / "));
ok(leaves.includes("云广播任务"), "叶子里有「云广播任务」");

console.log("② 默认落在服务器任务");
ok(last().includes("/api/transfer/server-tasks"), "首次拉的是 server-tasks，实际：" + last());
const serverRows = await rows();
ok(serverRows > 0, `服务器任务有 ${serverRows} 行（库里有还没下发过的任务）`);

console.log("③ 服务器任务只有两个按钮");
const sb = await btnTexts();
ok(sb.length === 2, "按钮数 = " + sb.length + "，实际：" + sb.join(" / "));
ok(sb.includes("空闲离线") && sb.includes("立即离线"), "两个按钮是空闲离线 / 立即离线");

console.log("④⑤ 切到云广播任务");
calls.length = 0;
await p.locator(".tree-pane .el-tree-node__label", { hasText: "云广播任务" }).click();
await p.waitForTimeout(2000);
ok(
  last().includes("/api/transfer/tasks") && !last().includes("server-tasks"),
  "换成了 /api/transfer/tasks，实际：" + last()
);
const cb = await btnTexts();
ok(cb.length === 8, "云广播任务有 8 个按钮，实际 " + cb.length + "：" + cb.join(" / "));
for (const want of ["空闲离线", "立即离线", "空闲删除", "立即删除", "停止离线", "离线播放", "停止离线播放", "删除离线音乐"]) {
  ok(cb.includes(want), "有「" + want + "」");
}
ok(
  (await p.locator(".header-left button.el-button--danger").count()) === 1,
  "「删除离线音乐」是危险色 —— 它是这一页唯一真删行的动作"
);

console.log("⑥ 类型页签带对 kind");
calls.length = 0;
await p.locator(".kind-tabs .el-tabs__item", { hasText: "文件广播" }).first().click();
await p.waitForTimeout(1800);
ok(last().includes("kind=file"), "切到文件广播带 kind=file，实际：" + last());
calls.length = 0;
await p.locator(".kind-tabs .el-tabs__item", { hasText: "全部" }).first().click();
await p.waitForTimeout(1800);
ok(/kind=(&|$)/.test(last()) || !last().includes("kind="), "「全部」不带 kind，实际：" + last());

console.log("⑦ 切回服务器任务，接口要真的换回去");
calls.length = 0;
await p.locator(".kind-tabs .el-tabs__item", { hasText: "作息方案" }).first().click();
await p.waitForTimeout(1500);
await p.locator(".tree-pane .el-tree-node__label", { hasText: "服务器任务" }).click();
await p.waitForTimeout(2000);
ok(
  last().includes("/api/transfer/server-tasks"),
  "换回 server-tasks（ProTable 靠 :key 重挂才换得掉 request-api），实际：" + last()
);
ok((await rows()) > 0, "服务器任务列表还有数据");

console.log("⑧ 行内两个链接问的是源表");
calls.length = 0;
await p.locator(".el-table__body-wrapper tbody tr").first().locator("button", { hasText: "终端" }).first().click();
await p.waitForTimeout(1500);
ok(
  calls.some(u => /\/api\/transfer\/server-tasks\/\d+(\?|$)/.test(u)),
  "「终端」问的是 server-tasks/{id}，实际：" + calls.join(" , ")
);
await p.keyboard.press("Escape");
await p.waitForTimeout(600);
calls.length = 0;
await p.locator(".el-table__body-wrapper tbody tr").first().locator("button", { hasText: "媒体" }).first().click();
await p.waitForTimeout(1500);
ok(
  calls.some(u => /\/api\/transfer\/server-tasks\/\d+\/media/.test(u)),
  "「媒体」问的是 server-tasks/{id}/media，实际：" + calls.join(" , ")
);

await b.close();
console.log(fails ? `\n✗ ${fails} 条断言没过` : "\n✓ 全部通过");
process.exit(fails ? 1 : 0);
