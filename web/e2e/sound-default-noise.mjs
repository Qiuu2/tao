/*
 * 声场任务 · 「设置默认噪声」整条路走一遍。
 *
 * # 它盯的是什么
 *
 * 现场 2026-09-16 报「声场任务中设置默认噪声点击后显示内部服务器错误」。
 * 这颗按钮背后是两个接口，点一次只走第一个：
 *
 *   点按钮          GET  /api/sound-tasks/db-template   → 填六格，开弹窗
 *   弹窗里点确定    PUT  /api/sound-tasks/db-template   → 整组重写
 *
 * 所以这个脚本必须**两个都走**，只点开弹窗是测不到写那一半的。
 *
 * # 顺带盯住的那个坏法
 *
 * soundtask 这张表**没有主键也没有唯一索引**（建表语句里一个键都没有），
 * 写那一侧原来是「先 UPDATE、RowsAffected()==0 就 INSERT」。连接串没开
 * clientFoundRows，值没变时 UPDATE 返回 0，于是被当成「这行不存在」又插一条——
 * taskid=0 那一组每存一次就涨一截（6 → 12 → 18 …）。
 *
 * 读那一侧按 volume 往六个格子里填，重复行只是互相覆盖，**界面上完全看不出来**。
 * 所以这里不能只看界面：存两次之后必须回头数一遍行数。
 *
 * 断言：
 *   ① 点按钮不报错，弹窗开出来，六格都填上了
 *   ② 改一格存下去，提示保存成功，不是「服务器内部错误」
 *   ③ 重新点开，改的那格真的存住了
 *   ④ 再存一次，taskid=0 仍然正好 6 行（不重复增长）
 *
 * # 怎么跑
 *
 *   node e2e/sound-default-noise.mjs
 *
 * ⚠ 会写库：改的是 soundtask 里 taskid = 0 那六行（全站默认噪声值）。
 *   run-all.sh 每个脚本跑完都会还原基线，单独跑请自己心里有数。
 */
const BASE = process.env.E2E_BASE || "http://127.0.0.1:5199";
const CHROME = process.env.E2E_CHROME || "/opt/pw-browsers/chromium-1194/chrome-linux/chrome";

const { chromium } = await import(process.env.E2E_PLAYWRIGHT || "/opt/node22/lib/node_modules/playwright/index.mjs");
const { execFileSync } = await import("node:child_process");

/*
 * taskid = 0 那一组在库里有几行。
 *
 * 这一条必须直接问数据库 —— 重复行在界面上是**隐形的**（读那一侧按 volume
 * 往六个格子里填，多出来的行只是把格子又覆盖一遍），光看弹窗永远是六格正常。
 */
const templateRows = () => {
  try {
    const out = execFileSync(
      "mariadb",
      [
        "--default-character-set=utf8",
        "-uroot",
        "-N",
        "-B",
        "audioserver",
        "-e",
        "SELECT COUNT(*) FROM soundtask WHERE taskid = 0"
      ],
      { encoding: "utf8" }
    );
    return Number(out.trim());
  } catch {
    // 连不上库就返回 null，让调用处跳过这一条而不是假绿
    return null;
  }
};

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

/*
 * 把这两个接口的每一次响应都记下来。
 *
 * 光看界面不够：GET 挂了的表现是「弹窗没开」，跟「按钮没点着」长得一模一样，
 * 不记状态码就分不清，会假绿。
 */
const calls = [];
p.on("response", async r => {
  if (!r.url().includes("/api/sound-tasks/db-template")) return;
  let body = "";
  try {
    body = (await r.text()).slice(0, 300);
  } catch {
    /* 响应体拿不到就算了，状态码才是要紧的 */
  }
  calls.push({ method: r.request().method(), status: r.status(), body });
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

const goto = async hash => {
  await p.keyboard.press("Escape").catch(() => undefined);
  await p.goto(BASE + hash, { waitUntil: "domcontentloaded" });
  // ⚠ 同一个 hash 上 goto 什么都不做，必须真 reload 才会重新挂载
  await p.reload({ waitUntil: "domcontentloaded" });
  await p.waitForTimeout(2600);
};

const dlg = () => p.locator(".el-dialog:visible", { hasText: "设置默认噪声" });
const boxes = () => dlg().locator(".el-input-number input");
/*
 * 确定键按**结构**定位（页脚里的 primary），不按文字。
 * el-button 对两个汉字的标签会不会插一个空格（autoInsertSpace）跟版本与配置有关，
 * 按 "确定" / "确 定" 去匹配，两种写法都可能有一天悄悄匹配不上 —— 那是假绿。
 */
const confirmBtn = () => dlg().locator(".el-dialog__footer .el-button--primary");

const openDialog = async () => {
  await p.locator("button", { hasText: "设置默认噪声" }).first().click();
  await p.waitForTimeout(1600);
};

const readBoxes = async () => (await boxes().allInputValues()).map(v => v.trim());

const lastOf = m => [...calls].reverse().find(c => c.method === m);

console.log("① 点「设置默认噪声」——弹窗要开出来，六格要有值");
await goto("/#/noise/task");
await openDialog();
{
  const g = lastOf("GET");
  ok(!!g, "确实发出了 GET /api/sound-tasks/db-template");
  ok(!!g && g.status === 200, `GET 回 200（实际 ${g ? g.status : "没发出去"}）`);
  ok(!!g && !g.body.includes("服务器内部错误"), "GET 的响应里没有「服务器内部错误」");
  ok(await dlg().isVisible(), "弹窗开出来了");
  const vals = await readBoxes();
  ok(vals.length === 6, `六档音量六个输入框（实际 ${vals.length} 个）`);
  ok(vals.length === 6 && vals.every(v => v !== ""), "六格都填上了值" + (vals.length ? "：" + vals.join(" / ") : ""));
}

console.log("② 改一格存下去");
// 挑第 3 格（音量 40 那一档），换一个不会跟原值撞车的数
const MARK = "66.5";
await boxes().nth(2).fill(MARK);
// el-input-number 要失焦才把输入并回 v-model，Tab 出去再点确定
await p.keyboard.press("Tab");
await p.waitForTimeout(400);
await confirmBtn().click();
await p.waitForTimeout(2000);
{
  const s = lastOf("PUT");
  ok(!!s, "确实发出了 PUT /api/sound-tasks/db-template");
  ok(!!s && s.status === 200, `PUT 回 200（实际 ${s ? s.status : "没发出去"}）`);
  ok(!!s && !s.body.includes("服务器内部错误"), "PUT 的响应里没有「服务器内部错误」");
  const toast = await p.locator(".el-message").allInnerTexts();
  ok(
    toast.some(t => t.includes("保存成功")),
    "提示「保存成功」" + (toast.length ? "，实际：" + toast.join(" | ").slice(0, 120) : "，实际一条提示都没有")
  );
}

console.log("③ 重新点开——改的那格要存住");
await goto("/#/noise/task");
await openDialog();
{
  const vals = await readBoxes();
  ok(vals[2] === MARK, `第 3 格存住了 ${MARK}（实际 ${vals[2]}）`);
}

console.log("④ 原样再存一次——不能让 taskid=0 那一组涨行数");
// 一个字都不改直接确定：每一档的值都跟库里一样，正是原来那个坏法必然翻车的入口
await confirmBtn().click();
await p.waitForTimeout(2000);
await goto("/#/noise/task");
await openDialog();
{
  const vals = await readBoxes();
  ok(vals.length === 6, `还是六个输入框（实际 ${vals.length} 个）`);
  ok(vals[2] === MARK, `第 3 格仍然是 ${MARK}（实际 ${vals[2]}）`);
  const bad = calls.filter(c => c.status >= 500);
  ok(bad.length === 0, `全程没有 5xx（实际 ${bad.length} 次）`);

  // 存了两次（②一次、④一次）。坏法下这里会是 12 行、18 行。
  const n = templateRows();
  if (n === null) {
    console.log("  — 连不上数据库，跳过行数这一条（界面看不出重复行，这条没跑等于没测到）");
  } else {
    ok(n === 6, `存过两次之后 soundtask 里 taskid=0 仍然正好 6 行（实际 ${n} 行）`);
  }
}

await b.close();
console.log(fails ? `\n✗ ${fails} 条不通过` : "\n✓ 全部通过");
process.exit(fails ? 1 : 0);
