/*
 * 文件管理「添加媒体」的回归测试。
 *
 * # 它盯的是什么
 *
 * 原来这个抽屉是一个拖拽框加**一条总进度条**。多传几个文件时那条总进度条
 * 什么也说不清：看不出在传哪一个、哪一个已经好了、哪一个失败了；字节一发完
 * 它就卡在 100%，后面服务端逐个转码那段时间整个界面是死的。
 *
 * 而且「播放时长」那一列，上传完是 **0分0秒** —— 后端按手册 C-06 把
 * media.timelength 写 0，等后台 C 服务扫描回填。后台扫到之前，界面上摆着的
 * 是一个明显错误的数字。
 *
 * 现在：一行一个文件，各自一条进度条；传完那一行的播放时长由服务端填回来。
 *
 * 六条：
 *   ① 抽屉里是一张表，选完文件后表里出现文件名
 *   ② 没传之前播放时长是「—」，不编数字
 *   ③ 每一行有自己的进度条（不是一条总的）
 *   ④ 传完每一行的进度条走到 100%
 *   ⑤ 传完那一行的播放时长填上了，且**和文件真实时长对得上**
 *   ⑥ 关掉抽屉，媒体列表里那两条的播放时长也不是 0分0秒
 *
 * # 怎么跑
 *
 *   ffmpeg -f lavfi -i "sine=frequency=440:duration=7"  -ac 1 -b:a 320k /tmp/upl/E2E-7s.mp3
 *   ffmpeg -f lavfi -i "sine=frequency=660:duration=95" -ar 22050 -ac 1 /tmp/upl/E2E-95s.wav
 *   E2E_UPLOAD_DIR=/tmp/upl node e2e/media-upload.mjs
 *
 * ⚠ **文件名和路径都必须是纯 ASCII。**
 *   第一版这两个文件叫「E2E七秒.mp3」「E2E九十五秒.wav」，结果
 *   `setInputFiles` 悄悄什么都没挂上去 —— input.files.length 还是 0，
 *   不报错、不抛异常。于是表里 0 行、「确定」按钮灰着，看起来像页面坏了。
 *   我照这个假象去查旧版，差点得出「添加媒体本来就是坏的」这种结论。
 *   带中文的路径在 CDP 那一层就丢了，跟被测代码毫无关系。
 *
 * ⚠ 它会**真的往库里和磁盘上写两个媒体**（名字以 E2E 开头）。跑完自己删掉，
 *   但中途崩了要手工清：媒体列表里搜 E2E，以及 /opt/apps/a9000/backup/mediadata。
 */
const BASE = process.env.E2E_BASE || "http://127.0.0.1:5199";
const CHROME = process.env.E2E_CHROME || "/opt/pw-browsers/chromium-1194/chrome-linux/chrome";
const DIR = process.env.E2E_UPLOAD_DIR || "/tmp/claude-0/upl";
/**
 * 文件名 → **入库之后**应该是多少秒。⑤ 拿它和界面上显示的比。
 *
 * ⚠ 不等于源文件的时长：转码时会在尾部追加 2 秒静音（沿用原系统约定，
 *   见抽屉里那段提示和 write.go 的 transcode）。所以 7 秒的源进来是 9 秒。
 *   第一版按源时长 7 / 95 去比，红了两条 —— 红的是我的期望值，不是代码：
 *   真正要对的是「这个媒体播出来有多长」，那就包括那 2 秒。
 */
const TAIL_SILENCE = 2;
const WANT = { "E2E-7s.mp3": 7 + TAIL_SILENCE, "E2E-95s.wav": 95 + TAIL_SILENCE };

const { chromium } = await import(process.env.E2E_PLAYWRIGHT || "/opt/node22/lib/node_modules/playwright/index.mjs");
const { readdirSync } = await import("node:fs");
const { join } = await import("node:path");

let fails = 0;
const ok = (c, m) => {
  console.log((c ? "  ✓ " : "  ✗ ") + m);
  if (!c) fails++;
};

const files = readdirSync(DIR)
  .filter(f => /\.(mp3|wav)$/i.test(f))
  .map(f => join(DIR, f));
if (!files.length) {
  console.log(`✗ ${DIR} 里没有 mp3/wav，先按文件头的说明生成两个`);
  process.exit(1);
}

/** 「3分12秒」→ 192。认不出来返回 NaN，断言那边会当失败 */
const toSec = s => {
  const m = s.match(/(\d+)分(\d+)秒/);
  return m ? Number(m[1]) * 60 + Number(m[2]) : NaN;
};

const b = await chromium.launch({ executablePath: CHROME });
const p = await b.newPage({ viewport: { width: 1500, height: 950 } });

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

// ── 进文件管理，挑一个能上传的目录 ──
await p.goto(BASE + "/#/media", { waitUntil: "domcontentloaded" });
await p.waitForSelector(".el-tree-node", { timeout: 25000 });
await p.waitForTimeout(1500);
const addBtn = p.locator(".table-header-ops button").first();
for (let i = 0; i < 6; i++) {
  if (await addBtn.isEnabled()) break;
  await p.locator(".el-tree-node__content").nth(i).click();
  await p.waitForTimeout(1200);
}
ok(await addBtn.isEnabled(), "选到了一个能上传的目录");
await addBtn.click();
await p.waitForTimeout(1200);

const dlg = p.locator(".el-drawer:visible").first();
await dlg.waitFor({ timeout: 10000 });

// ── ① 选文件后表里出现文件名 ──
console.log("① 选完文件，表里出现文件名");
await dlg.locator('input[type="file"]').setInputFiles(files);
await p.waitForTimeout(1000);
const rows = dlg.locator(".up-table .el-table__body .el-table__row");
ok((await rows.count()) === files.length, `表里 ${await rows.count()} 行，选了 ${files.length} 个`);
const names = [];
for (let i = 0; i < (await rows.count()); i++) names.push((await rows.nth(i).locator("td").nth(1).innerText()).trim());
console.log("   " + JSON.stringify(names));
ok(
  files.every(f => names.some(n => n.includes(f.split("/").pop()))),
  "每个选中的文件都在表里"
);

// ── ② 没传之前播放时长是「—」 ──
console.log("② 没传之前不编时长");
const durs0 = [];
for (let i = 0; i < (await rows.count()); i++) durs0.push((await rows.nth(i).locator("td").nth(3).innerText()).trim());
console.log("   " + JSON.stringify(durs0));
ok(
  durs0.every(d => d === "—"),
  "播放时长全是「—」"
);

// ── ③ 每一行有自己的进度条 ──
console.log("③ 一行一条进度条，不是一条总的");
const bars = await dlg.locator(".up-table .el-table__body .el-progress").count();
ok(bars === files.length, `${bars} 条进度条对 ${files.length} 行`);

// ── ④⑤ 传完 ──
console.log("④⑤ 确定上传");
await dlg.locator(".el-button--primary").last().click();
// 转码要时间，等所有行都不再是「上传中 / 转码中」
await p.waitForFunction(
  n => {
    const rs = document.querySelectorAll(".el-drawer .up-table .el-table__body .el-table__row");
    if (rs.length !== n) return false;
    return [...rs].every(r => {
      const c = r.querySelector(".el-progress");
      return c && (c.className.includes("is-success") || c.className.includes("is-exception"));
    });
  },
  files.length,
  { timeout: 120000 }
);
const pcts = [];
const durs = [];
const texts = [];
for (let i = 0; i < (await rows.count()); i++) {
  pcts.push((await rows.nth(i).locator(".el-progress__text, .el-progress-bar__innerText").first().innerText()).trim());
  durs.push((await rows.nth(i).locator("td").nth(3).innerText()).trim());
  texts.push((await rows.nth(i).locator(".up-progress-text").innerText()).trim());
}
console.log("   进度：" + JSON.stringify(pcts));
console.log("   时长：" + JSON.stringify(durs));
console.log("   状态：" + JSON.stringify(texts));
ok(
  pcts.every(x => x.includes("100")),
  "每一行都走到 100%"
);
ok(
  texts.every(x => /新增|覆盖/.test(x)),
  "每一行都报了新增 / 覆盖，没有失败"
);

// ⑤ 时长要和文件真实时长对得上（允许 ±1 秒的取整）
let checked = 0;
for (let i = 0; i < names.length; i++) {
  const file = Object.keys(WANT).find(k => names[i].includes(k));
  if (!file) continue;
  checked++;
  const got = toSec(durs[i]);
  ok(
    Math.abs(got - WANT[file]) <= 1,
    `${file}：界面 ${durs[i]}（${got}s），应该是 ${WANT[file]}s（源 ${WANT[file] - TAIL_SILENCE}s + ${TAIL_SILENCE}s 静音尾）`
  );
}
ok(checked > 0, `对了 ${checked} 个文件的时长`);

// ── ⑥ 媒体列表里也不是 0分0秒 ──
//
// ⚠ 必须等抽屉真的从 DOM 里消失再查。第一版没等，`.el-table__body` 的
//   第一个命中的是**抽屉里那张上传表**（el-drawer 的内容挂在 body 上，
//   按 Esc 之后还留着一会儿）。于是「列表里有 2 条 E2E、没有 0分0秒」
//   在库里一条记录都没有的情况下照样绿了 —— 假绿。
console.log("⑥ 关掉抽屉，媒体列表里的播放时长不是 0分0秒");
await p.keyboard.press("Escape");
await p.waitForSelector(".el-drawer", { state: "detached", timeout: 15000 }).catch(() => undefined);
await p.waitForTimeout(2500);
ok((await p.locator(".el-drawer .up-table").count()) === 0, "上传抽屉已经从 DOM 里没了，下面查的是主列表");

// 列名顺序：媒体名称 | 媒体大小 | 媒体类型 | 媒体比特率 | 播放时长 | 操作
const heads = (await p.locator(".media-container .el-table__header-wrapper th").allInnerTexts()).map(x => x.trim());
const cName = heads.indexOf("媒体名称");
const cTime = heads.indexOf("播放时长");
ok(cName >= 0 && cTime >= 0, `主列表表头：${JSON.stringify(heads)}`);

const listRows = p.locator(".media-container .el-table__body .el-table__row");
const found = [];
for (let i = 0; i < (await listRows.count()); i++) {
  const cells = await listRows.nth(i).locator("td").allInnerTexts();
  if (!(cells[cName] || "").includes("E2E")) continue;
  found.push([cells[cName].trim(), (cells[cTime] || "").trim()]);
}
console.log("   " + JSON.stringify(found));
ok(found.length === files.length, `列表里有 ${found.length} 条刚传的媒体，应该是 ${files.length} 条`);
ok(found.length > 0 && found.every(([, d]) => d && d !== "0分0秒"), "每一条的播放时长都不是 0分0秒");
for (const [name, d] of found) {
  const file = Object.keys(WANT).find(k => name.includes(k.replace(/\.[^.]+$/, "")));
  if (!file) continue;
  ok(Math.abs(toSec(d) - WANT[file]) <= 1, `列表里 ${name}：${d}，应该是 ${WANT[file]}s`);
}

// ── 收尾：把造的两条媒体删掉 ──
console.log("收尾：删掉刚传的 E2E 媒体");
for (let i = (await listRows.count()) - 1; i >= 0; i--) {
  const cells = await listRows.nth(i).locator("td").allInnerTexts();
  if (!(cells[cName] || "").includes("E2E")) continue;
  await listRows.nth(i).locator(".el-checkbox").click();
  await p.waitForTimeout(200);
}
const delBtn = p.locator(".table-header-ops button").nth(1);
if (await delBtn.isEnabled()) {
  await delBtn.click();
  await p.waitForTimeout(1500);
  const confirm = p.locator(".el-dialog:visible .el-dialog__footer button, .el-message-box__btns button").last();
  if (await confirm.count()) {
    await confirm.click();
    await p.waitForTimeout(2000);
  }
}
const left = await p.locator(".media-container .el-table__body").first().innerText();
ok(!/E2E/.test(left), "E2E 媒体已经删干净了");

await b.close();
console.log(fails ? `\n✗ ${fails} 条没过` : "\n✓ 全过");
process.exit(fails ? 1 : 0);
