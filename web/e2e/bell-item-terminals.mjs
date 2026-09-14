/*
 * 作息方案：序号单选要把**这一课时自己的终端清单**带出来。
 *
 * # 怎么跑
 *
 *   node e2e/bell-item-terminals.mjs
 *
 * ⚠ 会**真的改库**：建一个叫「E2E课终」的方案，改它两个课时的终端。
 *   跑完（含中途退出）都会清干净。
 *
 * # 在验什么
 *
 * terminaloftask 本来就按 taskid 存，一课时一份。旧版 modifybell.html 点中课时表
 * 第一格那个 radio 会 getonebelltaskterminal() → getonetaskterminal.php?taskid=，
 * 把这一课时自己的终端灌进下面的树；行上的「修改」（modifyonebellplan.php）再把
 * 树里的选择只存给这一课时。整表提交（belltaskalonemodify）才是一份套全组。
 *
 * 终端只是其中一半。radio 选中时旧版还会把**这一课时自己的**提前开电源 /
 * 音量 / 任务级别 / 起止日期 / 星期灌回上面那排控件
 * （getonetaskterminal.php 一次全返回，getonetaskterminal.js 逐个填），
 * 行上的「修改」再把控件当时的值只写给这一课时
 * （modifyonebellplan.php 的 URL 里带着 getprepower / task_priority_text / …）。
 *
 * 我们以前只有「一个方案共用一份」，这里把按课时那条路补上，
 * 并且补了旧版漏的一件事：功放子任务的终端清单跟着一起重写。
 */
const BASE = process.env.E2E_BASE || "http://127.0.0.1:5199";
const CHROME = process.env.E2E_CHROME || "/opt/pw-browsers/chromium-1194/chrome-linux/chrome";
const { chromium } = await import(process.env.E2E_PLAYWRIGHT || "/opt/node22/lib/node_modules/playwright/index.mjs");
const { execSync } = await import("node:child_process");
// ⚠ mariadb 命令行默认 latin1，不带 --default-character-set=utf8 的话
//   LIKE 'E2E课终%' 一条都匹配不上，清理会「成功」但什么都没删。
const SQL = q => `mariadb -uroot --default-character-set=utf8 audioserver -e "${q}"`;
const q1 = s => execSync(`mariadb -uroot --default-character-set=utf8 -N -B audioserver -e "${s}"`, { encoding: "utf8" }).trim();
let fails = 0;
const ok = (c, m) => {
  console.log((c ? "  ✓ " : "  ✗ ") + m);
  if (!c) fails++;
};
const PLAN = "E2E课终";
const clean = () => {
  try {
    execSync(
      SQL(
        `DELETE mt FROM mediaoftask mt JOIN task t ON t.taskid=mt.taskid WHERE t.info LIKE '${PLAN}%'; DELETE FROM terminaloftask WHERE taskid IN (SELECT taskid FROM task WHERE info LIKE '${PLAN}%'); DELETE FROM task WHERE info LIKE '${PLAN}%'`
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
// 取两台终端：T1 建方案时给全方案用，T2 留给「只改第二课时」
const [T1, T2] = q1("SELECT id FROM terminal ORDER BY id LIMIT 2").split("\n").map(Number);
const nameOf = id => q1(`SELECT terminalname FROM terminal WHERE id=${id}`);
console.log(`   两台终端：T1=${T1} ${nameOf(T1)}   T2=${T2} ${nameOf(T2)}`);

// prepower 给 15 秒，好验功放子任务的终端有没有跟着一起改
await call("POST", "/api/bell-plans", {
  planName: PLAN,
  schedule: { startdate: "2026-09-14", enddate: "2026-12-31", exemodel: "1111111" },
  playback: { defaultvolume: 80, priority: 10, prepower: 15, datasendmodel: 0, israndomplay: 0 },
  terminals: [{ terminalId: T1, groupId: 0, area: "" }],
  items: [
    { taskname: "第一节", playtime: "08:00:00", timelengthtype: 2, timelength: 1, media: [{ mediaId: mid, sort: 0 }] },
    { taskname: "第二节", playtime: "09:00:00", timelengthtype: 2, timelength: 1, media: [{ mediaId: mid, sort: 0 }] }
  ]
});
const idOf = n => Number(q1(`SELECT taskid FROM task WHERE info='${PLAN}' AND taskname='${n}' AND sec_task_id=0`));
const id1 = idOf("第一节"),
  id2 = idOf("第二节");
const termsOf = id => q1(`SELECT GROUP_CONCAT(terminalid ORDER BY terminalid) FROM terminaloftask WHERE taskid=${id}`);
const powerOf = id => Number(q1(`SELECT COALESCE(MAX(taskid),0) FROM task WHERE sec_task_id=${id} AND tasktype=9`));
console.log(`   建好了：第一节=${id1} 第二节=${id2}`);

// ── ① 读：按课时能各读各的 ──
console.log("① GET /api/bell-plans/items/{id}/terminals 读的是这一课时自己的清单");
{
  const a = await call("GET", `/api/bell-plans/items/${id1}/terminals?plan=${encodeURIComponent(PLAN)}`);
  const c = await call("GET", `/api/bell-plans/items/${id2}/terminals?plan=${encodeURIComponent(PLAN)}`);
  ok(a.code === 200 && c.code === 200, "两个课时都读得出来");
  ok(
    (a.data.terminals ?? []).map(t => t.terminalId).join() === String(T1),
    `第一节现在挂着 T1（读到 ${JSON.stringify((a.data.terminals ?? []).map(t => t.terminalId))}）`
  );
  ok((c.data.terminals ?? []).map(t => t.terminalId).join() === String(T1), "第二节也是 T1（建方案时一份套全组）");
  ok((a.data.terminals ?? [])[0]?.terminalname === nameOf(T1), "带回了终端名，不是光一个 ID");
}
// 借个别的方案的 taskid 来读，必须读不到
{
  const other = Number(q1(`SELECT taskid FROM task WHERE (info IS NULL OR info<>'${PLAN}') AND sec_task_id=0 ORDER BY taskid LIMIT 1`));
  const r = await call("GET", `/api/bell-plans/items/${other}/terminals?plan=${encodeURIComponent(PLAN)}`);
  ok(r.code !== 200, `别的任务的 taskid（${other}）读不到这个方案下的清单，返回 ${r.code}`);
}

// ── ② 写：只改一个课时，另一个不动 ──
console.log("② PUT /api/bell-plans/items/{id} 带 terminals，只改这一个课时");
{
  const r = await call("PUT", `/api/bell-plans/items/${id2}`, {
    planName: PLAN,
    item: {
      taskname: "第二节",
      playtime: "09:00:00",
      timelengthtype: 2,
      timelength: 1,
      media: [{ mediaId: mid, sort: 0 }],
      terminals: [{ terminalId: T2, groupId: 0, area: "" }],
      applyTerminals: true
    }
  });
  ok(r.code === 200, "改成功了");
  console.log(`   第一节终端=${termsOf(id1)}   第二节终端=${termsOf(id2)}`);
  ok(termsOf(id2) === String(T2), "第二节换成了 T2");
  ok(termsOf(id1) === String(T1), "第一节没被带着一起改 —— 这就是「按课时各存各的」");
}
// ── ③ 功放子任务跟着一起改（旧版漏了这一步）──
console.log("③ 功放子任务的终端清单跟着主课时一起重写");
{
  const pw1 = powerOf(id1),
    pw2 = powerOf(id2);
  console.log(`   功放子任务：第一节=${pw1}(${termsOf(pw1)})  第二节=${pw2}(${termsOf(pw2)})`);
  ok(pw2 > 0, "第二节确实有功放子任务（prepower=15）");
  ok(termsOf(pw2) === String(T2), "第二节的功放子任务也换成了 T2");
  ok(termsOf(pw1) === String(T1), "第一节的功放子任务还是 T1");
}
// ── ④ 不带 applyTerminals 就别动终端 ──
console.log("④ 不带 applyTerminals 的「改条目」不许碰终端");
{
  await call("PUT", `/api/bell-plans/items/${id2}`, {
    planName: PLAN,
    item: { taskname: "第二节", playtime: "09:05:00", timelengthtype: 2, timelength: 1, media: [{ mediaId: mid, sort: 0 }] }
  });
  ok(termsOf(id2) === String(T2), "只改了时间，终端还是 T2");
}
// ── ⑤ 详情里每个课时报自己挂了几台 ──
console.log("⑤ 方案详情里每个课时带 terminalCount");
{
  const d = await call("GET", `/api/bell-plans/detail?plan=${encodeURIComponent(PLAN)}`);
  const m = Object.fromEntries((d.data.items ?? []).map(it => [it.taskname, it.terminalCount]));
  console.log("   terminalCount:", JSON.stringify(m));
  ok(m["第一节"] === 1 && m["第二节"] === 1, "两个课时各报 1 台");
}

// ── ⑥ 界面：点序号那个 radio，树要换成这一课时的 ──
console.log("⑥ 修改方案里点序号，终端树跟着换");
await p.evaluate(() => {
  location.hash = "#/bell";
});
await p.waitForTimeout(500);
await p.reload({ waitUntil: "domcontentloaded" });
await p.waitForTimeout(4000);
const planRow = p.locator(".el-table__body .el-table__row", { hasText: PLAN }).first();
await planRow.locator("button", { hasText: "修改" }).first().click();
await p.waitForTimeout(4000);
const dlg = p.locator(".el-dialog:visible").first();
// 课时表在对话框里是第二张表（第一张是外面那张列表），用对话框内的表定位
const itemRows = dlg.locator(".el-table__body .el-table__row");
const noteText = async () => ((await dlg.locator(".dlg-note.mb6").first().innerText()) || "").replace(/\s+/g, "");
const pickedIds = () =>
  dlg.locator(".tt-tree .el-tree-node__content").evaluateAll(ns =>
    ns.filter(n => n.querySelector(".el-checkbox.is-checked")).map(n => (n.querySelector(".tt-label") || {}).textContent || "")
  );
{
  const n = await noteText();
  console.log("   刚打开时那行字:", n);
  ok(n.includes("整个方案"), "一打开说的是「整个方案」");
}
{
  // 第 2 行的序号 radio
  await itemRows.nth(1).locator(".idx-radio").first().click();
  await p.waitForTimeout(2500);
  const n = await noteText();
  console.log("   选中第 2 个课时后:", n);
  ok(n.includes("第2个课时") && n.includes("第二节"), "那行字说清楚了现在看的是第 2 个课时「第二节」");
  const picked = (await pickedIds()).filter(Boolean);
  console.log("   树上勾中的:", JSON.stringify(picked));
  ok(picked.length === 1 && picked[0].includes(nameOf(T2)), `树换成了第二节自己的 T2（${nameOf(T2)}）`);
}
{
  // 没选中的那行不能存 —— 否则会拿着第二节的终端写进第一节
  const btn = itemRows.nth(0).locator("button", { hasText: "修改" }).first();
  ok(await btn.isDisabled(), "第 1 行的「修改」按钮是灰的（没选中它）");
  ok(!(await itemRows.nth(1).locator("button", { hasText: "修改" }).first().isDisabled()), "第 2 行的「修改」可以点");
}
{
  await dlg.locator("button", { hasText: "看整个方案" }).first().click();
  await p.waitForTimeout(1500);
  const n = await noteText();
  console.log("   点了「看整个方案的终端」后:", n);
  ok(n.includes("整个方案"), "能退回方案级视图");
  const picked = (await pickedIds()).filter(Boolean);
  ok(picked.length === 1 && picked[0].includes(nameOf(T1)), "树也换回了方案级那一份（T1）");
}

// ── ⑦ 界面上改完点这一行的「修改」，只存这一个课时 ──
console.log("⑦ 选中第 2 个课时，在树上再勾一台，点这一行的「修改」");
{
  await itemRows.nth(1).locator(".idx-radio").first().click();
  await p.waitForTimeout(2500);
  // 在树上把 T1 也勾上（现在是 T2，勾完应该是 T1+T2）
  const t1Node = dlg.locator(".tt-tree .el-tree-node__content", { hasText: nameOf(T1) }).first();
  await t1Node.locator(".el-checkbox").first().click();
  await p.waitForTimeout(800);
  await itemRows.nth(1).locator("button", { hasText: "修改" }).first().click();
  await p.waitForTimeout(4000);
  const t2 = termsOf(id2),
    t1 = termsOf(id1);
  console.log(`   存完：第一节=${t1}  第二节=${t2}`);
  ok(t2 === [T1, T2].sort((a, b) => a - b).join(","), "第二节现在挂着两台");
  ok(t1 === String(T1), "第一节还是只有一台 —— 行内「修改」没有波及别的课时");
  ok(termsOf(powerOf(id2)) === [T1, T2].sort((a, b) => a - b).join(","), "第二节的功放子任务也是两台");
}

// ── ⑧ 选中序号，上面那排控件要换成这一课时自己的值 ──
console.log("⑧ 点序号，提前开电源 / 音量 / 任务级别 / 起止日期 也要跟着换");
{
  // 先把两个课时改成**不一样**的属性，不然换不换都一个样，验不出来
  execSync(SQL(`UPDATE task SET priority=12, defaultvolume=66, prepower=15 WHERE taskid=${id1} OR sec_task_id=${id1}`), {
    stdio: "pipe"
  });
  execSync(SQL(`UPDATE task SET priority=31, defaultvolume=44, prepower=20 WHERE taskid=${id2} OR sec_task_id=${id2}`), {
    stdio: "pipe"
  });
  await p.reload({ waitUntil: "domcontentloaded" });
  await p.waitForTimeout(4000);
  await p.locator(".el-table__body .el-table__row", { hasText: PLAN }).first().locator("button", { hasText: "修改" }).first().click();
  await p.waitForTimeout(4000);
  const d2 = p.locator(".el-dialog:visible").first();
  const rows2 = d2.locator(".el-table__body .el-table__row");

  /*
   * 任务级别那个下拉的当前值。
   *
   * ⚠ 两个坑，都踩过：
   *   1. 不能读 input 的 value —— el-select 里那个 input 是空的（readonly，
   *      只用来接键盘），选中项另画在别处，读它永远是空串；
   *   2. 也不能取 `.el-select__selected-item` 的**第一个** —— 第一个是
   *      `el-select__input-wrapper is-hidden`，同样是空的。
   * 直接读整个 .el-select__wrapper 的可见文字最稳。
   */
  const priorityRow = () => d2.locator(".el-form-item", { hasText: "任务级别" }).first();
  const selText = async loc => (await loc.locator(".el-select__wrapper").first().innerText()).trim();
  const priorityNow = () => selText(priorityRow());
  // 音量滑块右边那个数字输入框（el-input-number 的 input 是真的有 value 的）
  const volumeNow = async () => (await d2.locator(".el-form-item", { hasText: "音量" }).locator("input").first().inputValue()).trim();

  console.log(`   刚打开（方案级，取第一条）：任务级别 ${await priorityNow()}，音量 ${await volumeNow()}`);
  await rows2.nth(0).locator(".idx-radio").first().click();
  await p.waitForTimeout(2000);
  const p1 = await priorityNow(),
    v1 = await volumeNow();
  console.log(`   选中第 1 个课时：任务级别 ${p1}，音量 ${v1}`);
  ok(p1 === "12", "第 1 个课时的任务级别灌进了控件（12）");
  ok(v1 === "66", "第 1 个课时的音量灌进了控件（66）");

  await rows2.nth(1).locator(".idx-radio").first().click();
  await p.waitForTimeout(2000);
  const p2v = await priorityNow(),
    v2 = await volumeNow();
  console.log(`   选中第 2 个课时：任务级别 ${p2v}，音量 ${v2}`);
  ok(p2v === "31", "换到第 2 个课时，任务级别跟着变成了 31 —— 这正是「没显示到控件里」那条");
  ok(v2 === "44", "音量也跟着变成了 44");

  // ── ⑨ 行内「修改」要把这排控件的值真的存进库 ──
  console.log("⑨ 改任务级别再点这一行的「修改」，库里要真的变");
  const sel = priorityRow().locator(".el-select").first();
  await sel.click();
  await p.waitForTimeout(800);
  await p.locator(".el-select-dropdown:visible .el-select-dropdown__item", { hasText: /^10$/ }).first().click();
  await p.waitForTimeout(600);
  ok((await priorityNow()) === "10", "控件上已经是 10 了");
  await rows2.nth(1).locator("button", { hasText: "修改" }).first().click();
  await p.waitForTimeout(4000);

  const gotPri = q1(`SELECT priority FROM task WHERE taskid=${id2}`);
  const otherPri = q1(`SELECT priority FROM task WHERE taskid=${id1}`);
  console.log(`   存完：第二节 priority=${gotPri}，第一节 priority=${otherPri}`);
  ok(gotPri === "10", "第 2 个课时的任务级别真的存成了 10（原来点了「修改」整组属性被悄悄丢掉）");
  ok(otherPri === "12", "第 1 个课时没被带着一起改");
  ok(
    q1(`SELECT GROUP_CONCAT(DISTINCT priority) FROM task WHERE sec_task_id=${id2}`) === "10",
    "功放 / LED 子任务的任务级别也跟着改了"
  );

  // 重新打开再选一次，值要还是 10 —— 这才是需求方遇到的那个现象
  await p.reload({ waitUntil: "domcontentloaded" });
  await p.waitForTimeout(4000);
  await p.locator(".el-table__body .el-table__row", { hasText: PLAN }).first().locator("button", { hasText: "修改" }).first().click();
  await p.waitForTimeout(4000);
  const d3 = p.locator(".el-dialog:visible").first();
  await d3.locator(".el-table__body .el-table__row").nth(1).locator(".idx-radio").first().click();
  await p.waitForTimeout(2000);
  const again = (
    await d3.locator(".el-form-item", { hasText: "任务级别" }).first().locator(".el-select__wrapper").first().innerText()
  ).trim();
  console.log("   重新打开再选第 2 个课时：任务级别", again);
  ok(again === "10", "重新打开还是 10，不会跳回原来那个数");
}

console.log(fails ? `\n✗ ${fails} 条没过` : "\n全部通过");
await b.close();
clean();
process.exit(fails ? 1 : 0);
