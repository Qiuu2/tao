/*
 * 任务/云广播/噪声各页的终端树，按**型号**筛。
 *
 * # 它盯的是什么
 *
 * 旧版每个「添加/修改任务」页一进来就先调 inc/config.inc.php 的
 * get_terminal_type($flag,…) 拿到允许的型号，再拿它去筛终端树：
 *
 *   belladd / taskadd / terminalfunctionplayadd / addadmtask / ledtaskadd
 *   zhaoshengtaskadd / zhaoshengstreamadd / set_offlinemusic   → flag 3
 *   taskttsadd / taskttsmodify                                 → flag 16（比 3 多排掉型号 18）
 *
 * 新版原来一台都不筛（task 那条只排除了「服务器」），于是报警主机、TTS主机、
 * 网络话筒这些根本不出声的设备也列在树上，勾上了也没用。
 * 现场 2026-09-16 提的就是这件事。
 *
 * 测试库里 12 台终端，按 flag 3 筛完应当剩 8 台：
 *
 *   留下  网络功放 ×1、一体化音箱 ×6、一键寻呼终端 ×1
 *   筛掉  服务器、网络话筒（广播室主话筒）、报警主机（消防报警主机）、TTS主机
 *
 * 断言：上面这几页的终端树里,4 台该筛的一台都不出现,8 台该留的一台不少。
 *
 * # 怎么跑
 *
 *   node e2e/terminal-type-filter.mjs
 *
 * ⚠ 只读：只开弹窗看树，不保存、不改库。
 */
const BASE = process.env.E2E_BASE || "http://127.0.0.1:5199";
const CHROME = process.env.E2E_CHROME || "/opt/pw-browsers/chromium-1194/chrome-linux/chrome";

const { chromium } = await import(process.env.E2E_PLAYWRIGHT || "/opt/node22/lib/node_modules/playwright/index.mjs");

let fails = 0;
const ok = (c, m) => {
  console.log((c ? "  ✓ " : "  ✗ ") + m);
  if (!c) fails++;
};

/** 型号不出声、该被筛掉的 */
const MUST_HIDE = ["服务器", "广播室主话筒", "消防报警主机", "TTS主机"];
/** 型号能放广播、必须留着的 */
const MUST_SHOW = [
  "办公区功放",
  "A102教室音箱",
  "A103教室音箱",
  "A201教室音箱",
  "A202教室音箱",
  "操场号角01",
  "操场号角02",
  "宿舍楼一键寻呼"
];

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

const goto = async hash => {
  await p.keyboard.press("Escape").catch(() => undefined);
  await p.goto(BASE + hash, { waitUntil: "domcontentloaded" });
  // ⚠ 同一个 hash 上 goto 什么都不做，必须真 reload 才会重新挂载
  await p.reload({ waitUntil: "domcontentloaded" });
  await p.waitForTimeout(2600);
};

/*
 * 读一棵终端树上的名字。
 *
 * ⚠ 选择器是 `.tt-label`（TerminalTree 自己的类），不是 el-tree 默认的
 *   `.el-tree-node__label` —— 这个组件用的是 #default 插槽，默认那个类根本不存在。
 *   选错了会读到空数组，然后「不该出现的一台都没出现」**假绿**。
 *   下面 assertTree 里那句「树上至少要有东西」就是防这个的。
 */
const treeNames = async (sel = ".el-dialog .tt-label") => {
  const raw = await p.locator(sel).allInnerTexts();
  return (
    raw
      .map(s => s.replace(/\s+/g, " ").trim())
      // 音乐传输那一页在名字后面缀了「（在线）/（离线）」，去掉再比。
      // 只削掉结尾那一对全角括号，不用 includes 模糊比 ——
      // 模糊比会让「广播室主话筒」被别的名字顺带匹配上，等于白测。
      .map(s => s.replace(/（[^（）]*）$/, "").trim())
      .filter(Boolean)
  );
};

const assertTree = async (label, sel = ".el-dialog .tt-label") => {
  const names = await treeNames(sel);
  // 先确认真的读到了树 —— 读空了下面两条会一起假绿
  ok(names.length > 0, `${label}：读到了终端树（${names.length} 个节点）`);
  const hidden = MUST_HIDE.filter(n => names.some(x => x === n));
  const shown = MUST_SHOW.filter(n => names.some(x => x === n));
  ok(hidden.length === 0, `${label}：不出声的型号一台都没列出来${hidden.length ? "，实际混进了 " + hidden.join("、") : ""}`);
  ok(shown.length === MUST_SHOW.length, `${label}：能放广播的 ${MUST_SHOW.length} 台都在，实际 ${shown.length} 台`);
  if (names.length === 0 || hidden.length || shown.length !== MUST_SHOW.length) {
    console.log("    树上是：" + names.join(" | ").slice(0, 400));
  }
};

const closeDialog = async () => {
  await p.locator(".el-dialog__headerbtn").last().click();
  await p.waitForTimeout(700);
};

console.log("① 作息方案 · 添加方案");
await goto("/#/bell");
await p.locator("button", { hasText: "添加方案" }).first().click();
await p.waitForTimeout(2200);
await assertTree("作息方案");
await closeDialog();

console.log("② 文件广播 · 添加");
await goto("/#/task");
await p.locator(".header-left button", { hasText: "添加" }).first().click();
await p.waitForTimeout(2200);
await assertTree("文件广播");
await closeDialog();

console.log("③ 终端功放 · 添加（flag 3）");
await goto("/#/amplifier");
await p.locator(".header-left button", { hasText: "添加" }).first().click();
await p.waitForTimeout(2200);
await assertTree("终端功放");
await closeDialog();

console.log("④ 文字语音 · 添加（flag 16，比 flag 3 多排掉型号 18）");
await goto("/#/tts");
await p.locator(".header-left button", { hasText: "添加" }).first().click();
await p.waitForTimeout(2200);
await assertTree("文字语音");
await closeDialog();

console.log("⑤ 声场分区 · 添加分区");
await goto("/#/noise/zone");
await p.locator("button", { hasText: "添加分区" }).first().click();
await p.waitForTimeout(2200);
await assertTree("声场分区");
await closeDialog();

console.log("⑥ 音乐传输 · 下发目标终端树（不在弹窗里，直接在页面上）");
await goto("/#/offline");
// ⚠ 这一页没用 TerminalTree 组件，是页面自己画的 el-tree，
//   所以选择器是 el-tree 默认的 `.el-tree-node__label`，不是 `.tt-label`。
await assertTree("音乐传输", ".el-tree-node__label");

await b.close();
console.log(fails ? `\n✗ ${fails} 条断言没过` : "\n✓ 全部通过");
process.exit(fails ? 1 : 0);
