/*
 * 登录页滑动验证的端到端测试。
 *
 * # 怎么跑
 *
 *   1. 起后端（config.yaml 里 auth.captcha_enabled: true、captcha_mode: slider）
 *   2. 起前端：在 web/ 下建一个 .env.development.local，把代理指到后端，例如
 *
 *        VITE_PROXY = [["/api","http://127.0.0.1:18080"]]
 *
 *      然后 `npx vite --mode development --port 5199 --host 127.0.0.1`
 *   3. `node e2e/login-slider.mjs`（需要 playwright 与一个 chromium；
 *      本项目的开发容器里两样都是现成的，路径见下面两个常量）
 *
 * # ⚠ 写这个测试时踩到的坑，别再踩一遍
 *
 *   · `waitUntil: "networkidle"` 在 vite dev 下**永远不会到** —— HMR 的
 *     websocket 一直开着。用 domcontentloaded + waitForSelector。
 *
 *   · 统计接口请求时不能只判 `url.includes("/api/")`：vite 会去拉
 *     `/src/api/modules/login.ts` 这种源码路径，一样含 "/api/"。
 *     第一版就是被它骗了，明明一个请求都没发出去，测试还报「发出了 POST /api/login」——
 *     而真实情况是代理没配好，请求全挂在那儿。**假绿比红还糟。**
 *
 *   · 拖动要一小步一小步 move，不能 `mouse.move(终点)` 一步到位：
 *     组件是按 pointermove 累计位移的，一步跳过去和真人拖不是一回事。
 */

const BASE = process.env.E2E_BASE || "http://127.0.0.1:5199";
const API = process.env.E2E_API || "http://127.0.0.1:18080";
const CHROME = process.env.E2E_CHROME || "/opt/pw-browsers/chromium-1194/chrome-linux/chrome";

/*
  playwright 不在 web/ 的依赖里（它只为这一个脚本存在，不值得进 package.json）。
  开发容器里它装在全局，所以默认按绝对路径 import；别的机器上
  `npm i -D playwright` 之后用 E2E_PLAYWRIGHT=playwright 覆盖即可。

  ⚠ 这里必须是**动态** import：ESM 不认 NODE_PATH，静态 import 'playwright'
    在没装到本地 node_modules 时直接 ERR_MODULE_NOT_FOUND。
*/
const { chromium } = await import(process.env.E2E_PLAYWRIGHT || "/opt/node22/lib/node_modules/playwright/index.mjs");
let fails = 0;
const ok = (c, msg) => {
  console.log((c ? "  ✓ " : "  ✗ ") + msg);
  if (!c) fails++;
};

const b = await chromium.launch({ executablePath: CHROME });

async function newPage() {
  const p = await b.newPage({ viewport: { width: 1280, height: 800 } });
  p.errs = [];
  p.api = [];
  p.on("console", m => {
    if (m.type() === "error") p.errs.push(m.text());
  });
  p.on("pageerror", e => p.errs.push("PAGEERROR " + e.message));
  p.on("request", r => {
    const u = r.url();
    if (/\/api\/[a-z]/.test(u) && !u.includes("/src/") && !u.includes("node_modules"))
      p.api.push(r.method() + " " + u.replace(/^https?:\/\/[^/]+/, "").split("?")[0]);
  });
  return p;
}

async function gotoLogin(p) {
  await p.goto(BASE + "/#/login", { waitUntil: "domcontentloaded" });
  await p.waitForSelector(".slider-captcha", { timeout: 20000 });
  await p.waitForTimeout(900);
}

/** 像人一样按住滑块，分步拖到最右边，松手 */
async function drag(p, toFraction = 1) {
  const track = await p.locator(".slider-captcha").boundingBox();
  const h = await p.locator(".slider-captcha .handle").boundingBox();
  const span = (track.width - h.width) * toFraction;
  await p.mouse.move(h.x + h.width / 2, h.y + h.height / 2);
  await p.mouse.down();
  for (let i = 1; i <= 18; i++) {
    await p.mouse.move(h.x + h.width / 2 + span * (i / 18), h.y + h.height / 2 + (i % 3) - 1);
    await p.waitForTimeout(12);
  }
  await p.mouse.up();
  await p.waitForTimeout(350);
}

// ── ① 登录框里没有验证码输入框，只有滑块 ──
console.log("① 验证码输入框已取消，只剩滑块");
{
  const p = await newPage();
  await gotoLogin(p);
  ok((await p.locator(".slider-captcha").count()) === 1, "滑块在");
  ok((await p.locator('input[placeholder*="验证码"]').count()) === 0, "没有验证码输入框");
  ok((await p.locator("img.captcha-img").count()) === 0, "没有验证码图片");
  const inputs = await p.locator(".login-form input").count();
  ok(inputs === 2, `表单里只剩用户名和密码两个输入框（实际 ${inputs}）`);
  await p.close();
}

// ── ② 不拖就点登录：拦住，且不发登录请求 ──
console.log("② 没拖滑块就点登录 → 拦住，不发请求");
{
  const p = await newPage();
  await gotoLogin(p);
  await p.fill(".login-form input >> nth=0", "admin");
  await p.fill(".login-form input >> nth=1", "123456");
  p.api.length = 0;
  await p.locator(".login-btn button").last().click();
  await p.waitForTimeout(800);
  ok(!p.api.includes("POST /api/login"), "没有发出 POST /api/login");
  const warn = await p
    .locator(".el-message--warning")
    .innerText()
    .catch(() => "");
  ok(warn.includes("滑块"), `弹出了提示：「${warn.trim()}」`);
  ok(!p.url().includes("/home"), "没有跳转");
  await p.close();
}

// ── ③ 只拖一半：弹回起点，不算通过 ──
console.log("③ 只拖一半 → 弹回起点，不算通过");
{
  const p = await newPage();
  await gotoLogin(p);
  await drag(p, 0.5);
  const cls = await p.locator(".slider-captcha").getAttribute("class");
  ok(!cls.includes("is-done"), "没有标记为通过");
  const h = await p.locator(".slider-captcha .handle").boundingBox();
  const t = await p.locator(".slider-captcha").boundingBox();
  ok(Math.abs(h.x - t.x) < 3, `滑块弹回了起点（x 差 ${Math.round(h.x - t.x)}px）`);
  const tip = await p.locator(".slider-captcha .tip").innerText();
  ok(!tip.includes("通过"), `提示仍是「${tip.trim()}」`);
  await p.close();
}

// ── ④ 拖到底 → 通过 → 登录成功进首页 ──
console.log("④ 拖到底 → 通过 → 登录成功");
{
  const p = await newPage();
  await gotoLogin(p);
  await p.fill(".login-form input >> nth=0", "admin");
  await p.fill(".login-form input >> nth=1", "123456");
  await drag(p);
  const cls = await p.locator(".slider-captcha").getAttribute("class");
  ok(cls.includes("is-done"), "滑块标记为通过");
  ok((await p.locator(".slider-captcha .tip").innerText()).includes("通过"), "提示变成「验证通过」");
  await p.screenshot({ path: (process.env.E2E_SHOTS || "/tmp") + "/20-verified.png" });

  await p.locator(".login-btn button").last().click();
  await p.waitForTimeout(3500);
  ok(p.api.includes("POST /api/login"), "发出了 POST /api/login");
  ok(p.url().includes("/home"), `跳到了首页（当前 ${p.url().split("#")[1]}）`);
  await p.screenshot({ path: (process.env.E2E_SHOTS || "/tmp") + "/21-home.png" });
  ok(p.errs.length === 0, p.errs.length ? "控制台报错：" + p.errs.join(" | ") : "控制台无报错");
  await p.close();
}

// ── ⑤ 密码错 → 滑块自动重置，并重新领一张凭据 ──
console.log("⑤ 密码错 → 滑块重置 + 重新领凭据");
{
  const p = await newPage();
  await gotoLogin(p);
  await p.fill(".login-form input >> nth=0", "admin");
  await p.fill(".login-form input >> nth=1", "错误的密码");
  await drag(p);
  p.api.length = 0;
  await p.locator(".login-btn button").last().click();
  await p.waitForTimeout(2000);
  const cls = await p.locator(".slider-captcha").getAttribute("class");
  ok(!cls.includes("is-done"), "滑块重置成未通过");
  ok(p.api.filter(u => u === "GET /api/captcha").length >= 1, "重新领了一张凭据（凭据是一次性的）");
  const h = await p.locator(".slider-captcha .handle").boundingBox();
  const t = await p.locator(".slider-captcha").boundingBox();
  ok(Math.abs(h.x - t.x) < 3, "滑块回到起点");
  await p.close();
}

// ── ⑥ 凭据一次性：同一个 captchaId 不能用两次 ──
console.log("⑥ 服务端：同一个 captchaId 不能用两次");
{
  const r1 = await fetch(API + "/api/captcha").then(r => r.json());
  const id = r1.data.captchaId;
  const post = () =>
    fetch(API + "/api/login", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ username: "admin", password: "123456", captchaId: id, captcha: "" })
    }).then(r => r.json());
  const a = await post();
  const c = await post();
  ok(a.code === 200, `第一次用：code=${a.code}`);
  ok(c.code !== 200, `第二次用同一个 id 被拒：code=${c.code} msg=${c.msg}`);
  const d = await fetch(API + "/api/login", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ username: "admin", password: "123456", captchaId: "随手编的", captcha: "" })
  }).then(r => r.json());
  ok(d.code !== 200, `编一个 id 也被拒：code=${d.code}`);
}

// ── ⑦ 触屏（手机/一体机）也能拖 ──
console.log("⑦ 触屏设备上也能拖");
{
  const ctx = await b.newContext({ viewport: { width: 420, height: 820 }, hasTouch: true, isMobile: true });
  const p = await ctx.newPage();
  await p.goto(BASE + "/#/login", { waitUntil: "domcontentloaded" });
  await p.waitForSelector(".slider-captcha", { timeout: 20000 });
  await p.waitForTimeout(900);
  const t = await p.locator(".slider-captcha").boundingBox();
  const h = await p.locator(".slider-captcha .handle").boundingBox();
  ok(t.width > 0 && t.x >= 0 && t.x + t.width <= 420, `窄屏下没有溢出（宽 ${Math.round(t.width)}）`);
  await p.touchscreen.tap(h.x + h.width / 2, h.y + h.height / 2); // 唤起指针
  // 用 dispatchEvent 走 pointer 事件（Playwright 的 touchscreen 不产生 pointermove 序列）
  await p.evaluate(
    ({ hx, hy, tx, tw, hw }) => {
      const el = document.querySelector(".slider-captcha .handle");
      const fire = (type, x) =>
        el.dispatchEvent(new PointerEvent(type, { clientX: x, clientY: hy, pointerId: 1, bubbles: true, pointerType: "touch" }));
      const fireWin = (type, x) =>
        window.dispatchEvent(
          new PointerEvent(type, { clientX: x, clientY: hy, pointerId: 1, bubbles: true, pointerType: "touch" })
        );
      fire("pointerdown", hx);
      for (let i = 1; i <= 10; i++) fireWin("pointermove", hx + (tw - hw) * (i / 10));
      fireWin("pointerup", tx + tw);
    },
    { hx: h.x + h.width / 2, hy: h.y + h.height / 2, tx: t.x, tw: t.width, hw: h.width }
  );
  await p.waitForTimeout(400);
  const cls = await p.locator(".slider-captcha").getAttribute("class");
  ok(cls.includes("is-done"), "触屏拖动也能通过");
  await p.screenshot({ path: (process.env.E2E_SHOTS || "/tmp") + "/22-mobile.png" });
  await ctx.close();
}

// ── ⑧ 键盘：End 到底；回车**不**该让它过 ──
console.log("⑧ 键盘可达，但回车不能直接过");
{
  const p = await newPage();
  await gotoLogin(p);
  await p.locator(".slider-captcha").focus();
  await p.keyboard.press("Enter");
  await p.waitForTimeout(300);
  let cls = await p.locator(".slider-captcha").getAttribute("class");
  ok(!cls.includes("is-done"), "按回车不会直接通过（登录表单在 document 上挂了回车提交）");
  await p.keyboard.press("End");
  await p.waitForTimeout(300);
  cls = await p.locator(".slider-captcha").getAttribute("class");
  ok(cls.includes("is-done"), "按 End 到底，通过");
  await p.close();
}

await b.close();
console.log(fails ? `\n✗ ${fails} 条没过` : "\n✓ 全部通过");
process.exit(fails ? 1 : 0);
