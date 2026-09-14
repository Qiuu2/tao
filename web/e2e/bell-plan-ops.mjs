/*
 * 作息方案页的一批修正（需求方一次提的六条）。
 *
 * # 怎么跑
 *
 *   node e2e/bell-plan-ops.mjs
 *
 * ⚠ 会**真的改库**：建两个叫「E2E删甲 / E2E删乙」的方案，删掉、改音量、
 *   进修改对话框点几下。跑完（含中途退出）都会清干净。
 *
 * 六条：
 *   #1 勾两个方案点「删除方案」，**两个都要删掉**（原来只删第一个，还弹一句
 *      「一次删一个」—— 人以为没删掉又点一次）
 *   #2 「调整音量」支持多选（原来只有勾一个才可点）
 *   #3 智能排课的执行列**只显示勾上的星期**（原来七天全画、没勾的刷灰）
 *   #4 方案名称去掉「只能中文/字母/数字」的限制
 *   #5 方案名称长度 8 → 12
 *   #6 修改方案里序号那列是**单选**，选中哪一行、哪一行才可编辑
 *      （旧版 modifybell.html:424 的 radio + getonebelltaskterminal()）
 */
const BASE = process.env.E2E_BASE || "http://127.0.0.1:5199";
const CHROME = process.env.E2E_CHROME || "/opt/pw-browsers/chromium-1194/chrome-linux/chrome";
const { chromium } = await import(process.env.E2E_PLAYWRIGHT || "/opt/node22/lib/node_modules/playwright/index.mjs");
const { execSync } = await import("node:child_process");
const SQL = q => `mariadb -uroot --default-character-set=utf8 audioserver -e "${q}"`;
const q1 = s => execSync(`mariadb -uroot --default-character-set=utf8 -N -B audioserver -e "${s}"`, { encoding: "utf8" }).trim();
let fails = 0;
const ok = (c, m) => {
  console.log((c ? "  ✓ " : "  ✗ ") + m);
  if (!c) fails++;
};
const P1 = "E2E删甲",
  P2 = "E2E删乙";
const clean = () => {
  try {
    execSync(
      SQL(
        `DELETE mt FROM mediaoftask mt JOIN task t ON t.taskid=mt.taskid WHERE t.info LIKE 'E2E%'; DELETE FROM terminaloftask WHERE taskid IN (SELECT taskid FROM task WHERE info LIKE 'E2E%'); DELETE FROM task WHERE info LIKE 'E2E%'`
      ),
      { stdio: "pipe" }
    );
  } catch {}
};
process.on("exit", clean);
clean();

const b = await chromium.launch({ executablePath: CHROME });
const p = await b.newPage({ viewport: { width: 1700, height: 1000 } });
p.on("pageerror", e => {
  console.log("  [pageerror] " + String(e).slice(0, 160));
  fails++;
});
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
await p.waitForTimeout(4000);
const token = await p.evaluate(() => {
  for (const s of [localStorage, sessionStorage])
    for (let i = 0; i < s.length; i++) {
      const v = s.getItem(s.key(i)) || "";
      const m = v.match(/[A-Za-z0-9_-]{20,}\.[a-f0-9]{64}/);
      if (m) return m[0];
    }
  return "";
});
const call = (m, u, bd) =>
  p.evaluate(
    async ([m, u, tk, bd]) =>
      await (
        await fetch(u, {
          method: m,
          headers: { "Content-Type": "application/json", "Accept-Language": "zh-CN", "x-access-token": tk },
          body: bd === null ? undefined : JSON.stringify(bd)
        })
      ).json(),
    [m, u, token, bd === undefined ? null : bd]
  );

const mid = Number(q1("SELECT id FROM media ORDER BY id LIMIT 1"));
const tid = Number(q1("SELECT id FROM terminal ORDER BY id LIMIT 1"));
for (const nm of [P1, P2]) {
  await call("POST", "/api/bell-plans", {
    planName: nm,
    schedule: { startdate: "2026-09-14", enddate: "2026-12-31", exemodel: "1010100" },
    playback: { defaultvolume: 80, priority: 10, prepower: 15, datasendmodel: 0, israndomplay: 0 },
    terminals: [{ terminalId: tid, groupId: 0, area: "" }],
    items: [
      { taskname: "第一节", playtime: "08:00:00", timelengthtype: 2, timelength: 1, media: [{ mediaId: mid, sort: 0 }] },
      { taskname: "第二节", playtime: "09:00:00", timelengthtype: 2, timelength: 1, media: [{ mediaId: mid, sort: 0 }] }
    ]
  });
}
await p.evaluate(() => {
  location.hash = "#/bell";
});
await p.waitForTimeout(500);
await p.reload({ waitUntil: "domcontentloaded" });
await p.waitForTimeout(4000);

const rowOf = n => p.locator(".el-table__body .el-table__row", { hasText: n }).first();
// ── #1 删除两个 ──
console.log("#1 勾两个方案，两个都要删掉");
await rowOf(P1).locator(".el-checkbox").first().click();
await p.waitForTimeout(300);
await rowOf(P2).locator(".el-checkbox").first().click();
await p.waitForTimeout(300);
await p.locator("button", { hasText: "删除方案" }).first().click();
await p.waitForTimeout(2500);
{
  const dlg = p.locator(".el-dialog:visible").first();
  const txt = (await dlg.innerText()).replace(/\s+/g, " ");
  console.log("   弹窗:", txt.slice(0, 130));
  ok(txt.includes(P1) && txt.includes(P2), "确认框里两个方案名都列出来了");
  await dlg.locator(".el-dialog__footer button").last().click();
  await p.waitForTimeout(3500);
}
const left = q1(`SELECT COUNT(*) FROM task WHERE info IN ('${P1}','${P2}')`);
console.log("   库里还剩:", left, "条");
ok(left === "0", "两个方案都真的删掉了（原来只删第一个）");

// 重新建两个方案给后面几条用
for (const nm of [P1, P2]) {
  await call("POST", "/api/bell-plans", {
    planName: nm,
    schedule: { startdate: "2026-09-14", enddate: "2026-12-31", exemodel: "1010100" },
    playback: { defaultvolume: 80, priority: 10, prepower: 15, datasendmodel: 0, israndomplay: 0 },
    terminals: [{ terminalId: tid, groupId: 0, area: "" }],
    items: [
      { taskname: "第一节", playtime: "08:00:00", timelengthtype: 2, timelength: 1, media: [{ mediaId: mid, sort: 0 }] },
      { taskname: "第二节", playtime: "09:00:00", timelengthtype: 2, timelength: 1, media: [{ mediaId: mid, sort: 0 }] }
    ]
  });
}
await p.reload({ waitUntil: "domcontentloaded" });
await p.waitForTimeout(4000);

// ── #2 调整音量支持多选 ──
console.log("#2 勾两个方案，调整音量能一起调");
await rowOf(P1).locator(".el-checkbox").first().click();
await p.waitForTimeout(300);
await rowOf(P2).locator(".el-checkbox").first().click();
await p.waitForTimeout(300);
const volBtn = p.locator("button", { hasText: "调整音量" }).first();
ok(!(await volBtn.isDisabled()), "勾了两个时「调整音量」是可点的（原来只有勾一个才行）");
await volBtn.click();
await p.waitForTimeout(2000);
{
  const dlg = p.locator(".el-dialog:visible").first();
  const txt = (await dlg.innerText()).replace(/\s+/g, " ");
  ok(txt.includes(P1) && txt.includes(P2), "音量弹窗里列出了两个方案：" + txt.slice(0, 80));
  // 拖到一个明确的值：直接改输入框
  const num = dlg.locator(".el-input-number input").first();
  await num.fill("42");
  await num.press("Enter");
  await p.waitForTimeout(400);
  await dlg.locator(".el-dialog__footer button").last().click();
  await p.waitForTimeout(3500);
}
{
  const v1 = q1(`SELECT DISTINCT defaultvolume FROM task WHERE info='${P1}'`);
  const v2 = q1(`SELECT DISTINCT defaultvolume FROM task WHERE info='${P2}'`);
  console.log("   两个方案的音量:", v1, "/", v2);
  ok(v1 === "42" && v2 === "42", "两个方案的音量都改成了 42");
}

// ── #3 智能排课只显示勾上的星期 ──
console.log("#3 智能排课的执行列表只显示勾上的星期");
await p.reload({ waitUntil: "domcontentloaded" });
await p.waitForTimeout(4000);
await rowOf(P1).locator(".el-checkbox").first().click();
await p.waitForTimeout(400);
await p.locator("button", { hasText: "智能排课" }).first().click();
await p.waitForTimeout(3000);
{
  const dlg = p.locator(".el-dialog:visible").first();
  // 方案的 exemodel 是 1010100 = 周日/周二/周四
  // 执行列是第 7 列：选择(1) 序号(2) 打铃时间(3) 课时名称(4) 铃声(5) 播放时长(6) 执行(7)
  const cells = await dlg.locator(".el-table__body .el-table__row td:nth-child(7)").allInnerTexts();
  const shown = cells[0].replace(/\s+/g, "");
  console.log("   执行列显示:", JSON.stringify(shown));
  ok(shown.includes("周日") && shown.includes("周二") && shown.includes("周四"), "勾上的三天显示出来了");
  ok(
    !shown.includes("周一") && !shown.includes("周三") && !shown.includes("周五") && !shown.includes("周六"),
    "没勾的四天不显示了"
  );
  await dlg.locator(".el-dialog__footer button").first().click();
  await p.waitForSelector(".el-overlay", { state: "hidden", timeout: 10000 }).catch(() => undefined);
  await p.waitForTimeout(800);
}

// ── #4 #5 #6 修改方案 ──
console.log("#4#5#6 修改方案：名称 12 字、不限字符、序号单选");
await p.reload({ waitUntil: "domcontentloaded" });
await p.waitForTimeout(4000);
await rowOf(P1).locator("button", { hasText: "修改" }).first().click();
await p.waitForTimeout(3500);
{
  const dlg = p.locator(".el-dialog:visible").first();
  const nameInput = dlg.locator(".el-form-item").filter({ hasText: "方案名称" }).locator("input").first();
  ok((await nameInput.getAttribute("maxlength")) === "12", "方案名称 maxlength 是 12（原来 8）");
  // 带括号和横杠的名字不该被挡
  await nameInput.fill("第一节(上)-A");
  await p.waitForTimeout(600);
  const err = await dlg.locator(".el-form-item.is-error").count();
  ok(err === 0, "带括号/横杠的名字不再报「只能中文字母数字」");

  // 序号是单选
  const radios = dlg.locator(".el-table__body .idx-radio");
  ok((await radios.count()) >= 2, `序号那列是单选按钮（${await radios.count()} 行）`);
  // ⚠ 课时名称在第 3 列。不能用 .locator("input").first() —— 那是序号那一格的
  //   radio（和左边的选择框），它们永远可点，测出来就永远绿。
  const nameOf = r => dlg.locator(".el-table__body .el-table__row").nth(r).locator("td:nth-child(3) input").first();
  ok(!(await nameOf(0).isDisabled()), "还没选任何一行时，各行都可编辑（不会整张表灰掉）");
  await radios.nth(0).click();
  await p.waitForTimeout(600);
  const r0 = nameOf(0);
  const r1 = nameOf(1);
  ok(!(await r0.isDisabled()), "选中第 1 行后，它可编辑");
  ok(await r1.isDisabled(), "其它行被锁住 —— 一次只改一条，不会改错行");
  await radios.nth(1).click();
  await p.waitForTimeout(600);
  ok(await r0.isDisabled(), "改选第 2 行后，第 1 行锁上了");
  ok(!(await r1.isDisabled()), "第 2 行解锁了");
}

await b.close();
console.log(fails === 0 ? "\n通过" : `\n${fails} 条不通过`);
process.exit(fails === 0 ? 0 : 1);
