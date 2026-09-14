/*
 * 「一个课时只挂一个铃声」的验收。
 *
 * 怎么跑
 *
 *   node e2e/bell-one-tone.mjs
 *
 * ⚠ 会**真的改库**：建一个叫「E2E一铃声」的方案，中途往它的课时上多插一行
 *   mediaoftask 模拟老数据，跑完（含中途退出）都会清掉。
 *
 * 五条：
 *   ① 添加方案：铃声那一格是**单选**（不是多选的 tags）
 *   ② 存下来之后库里那个课时只有一行 mediaoftask
 *   ③ 老数据挂了两个铃声时：打开修改只回填第一个，并且**明确提示**，不闷声丢
 *   ④ 批量修改的「统一作息音乐」也是单选
 *   ⑤ 开发者接口那条路也拦得住（服务层校验，不是只改了界面）
 */
const BASE = process.env.E2E_BASE || "http://127.0.0.1:5199";
const CHROME = process.env.E2E_CHROME || "/opt/pw-browsers/chromium-1194/chrome-linux/chrome";
const { chromium } = await import(process.env.E2E_PLAYWRIGHT || "/opt/node22/lib/node_modules/playwright/index.mjs");
const { execSync } = await import("node:child_process");
// ⚠ --default-character-set=utf8 不能省：不带它时 mariadb 客户端按 latin1 发过去，
//   `LIKE 'E2E一铃声%'` 这种带中文的条件一条都匹配不上 —— 清理会「成功」但什么都没删，
//   下一次跑就撞上「作息方案名称已存在」。审计那个脚本里也踩过同一个坑。
const SQL = q => `mariadb -uroot --default-character-set=utf8 audioserver -e "${q}"`;
const q1 = s => execSync(`mariadb -uroot --default-character-set=utf8 -N -B audioserver -e "${s}"`, { encoding: "utf8" }).trim();
let fails = 0;
const ok = (c, m) => {
  console.log((c ? "  ✓ " : "  ✗ ") + m);
  if (!c) fails++;
};
const PLAN = "E2E一铃声";
const cleanup = () => {
  try {
    execSync(
      SQL(
        `DELETE mt FROM mediaoftask mt JOIN task t ON t.taskid=mt.taskid WHERE t.info LIKE '${PLAN}%'; DELETE FROM terminaloftask WHERE taskid IN (SELECT taskid FROM task WHERE info LIKE '${PLAN}%'); DELETE FROM task WHERE info LIKE '${PLAN}%'`
      ),
      { stdio: "pipe" }
    );
  } catch {}
};
process.on("exit", cleanup);
cleanup();

/**
 * 判断一个 el-select 是不是单选 —— 看**行为**不看类名。
 * Element Plus 换版本时 .el-select__tags / .el-select__selection 这些类名变过，
 * 盯类名的判据会悄悄变成永远绿。连点两个选项：单选只会留最后一个。
 */
const isSingleSelect = async sel => {
  await sel.click();
  await p.waitForTimeout(1000);
  const opts = p.locator(".el-select-dropdown:visible .el-select-dropdown__item");
  if ((await opts.count()) < 2) return { ok: false, why: "下拉里不足两个选项，判不了" };
  const first = (await opts.nth(0).innerText()).trim();
  const second = (await opts.nth(1).innerText()).trim();
  await opts.nth(0).click();
  await p.waitForTimeout(600);
  await sel.click();
  await p.waitForTimeout(700);
  await p.locator(".el-select-dropdown:visible .el-select-dropdown__item").nth(1).click();
  await p.waitForTimeout(600);
  const shown = (
    (await sel
      .locator("input")
      .first()
      .inputValue()
      .catch(() => "")) || (await sel.innerText().catch(() => ""))
  ).trim();
  return { ok: shown.includes(second) && (!shown.includes(first) || first === second), first, second, shown };
};

const b = await chromium.launch({ executablePath: CHROME });
const p = await b.newPage({ viewport: { width: 1600, height: 1000 } });
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
await p.waitForTimeout(3500);
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

await p.evaluate(() => {
  location.hash = "#/bell";
});
await p.waitForTimeout(3000);

// ── ① 添加方案：单选 ──
console.log("① 添加方案：铃声那一格是单选");
await p.locator("button", { hasText: "添加方案" }).first().click();
await p.waitForTimeout(2500);
{
  const dlg = p.locator(".el-dialog:visible").first();
  const sel = dlg.locator(".el-table__body .el-select").first();
  ok((await sel.count()) >= 1, "铃声那一格还在");
  // 行为判据：连选两个。单选只会留最后一个，多选会两个都在。
  // （不看 DOM 类名 —— Element Plus 换版本时那些类名变过，看行为才靠得住）
  const r = await isSingleSelect(sel);
  console.log(`   先选「${r.first}」再选「${r.second}」，格子里显示：「${r.shown}」`);
  ok(r.ok, "连选两个只留最后一个 —— 是单选");
  await dlg.locator(".el-dialog__footer button", { hasText: "返回" }).click();
  await p.waitForSelector(".el-overlay", { state: "hidden", timeout: 10000 }).catch(() => undefined);
  await p.waitForTimeout(600);
}

// ── ② 建一个方案，库里只有一行铃声 ──
console.log("② 建出来的课时，库里只有一行 mediaoftask");
const mids = q1("SELECT GROUP_CONCAT(id) FROM (SELECT id FROM media ORDER BY id LIMIT 2) x").split(",").map(Number);
const tid = Number(q1("SELECT id FROM terminal ORDER BY id LIMIT 1"));
{
  const r = await call("POST", "/api/bell-plans", {
    planName: PLAN,
    schedule: { startdate: "2026-09-14", enddate: "2026-12-31", exemodel: "1111100" },
    playback: { defaultvolume: 80, priority: 10, prepower: 15, datasendmodel: 0, israndomplay: 0 },
    terminals: [{ terminalId: tid, groupId: 0, area: "" }],
    items: [
      { taskname: "第一节", playtime: "08:00:00", timelengthtype: 2, timelength: 1, media: [{ mediaId: mids[0], sort: 0 }] }
    ]
  });
  ok(r.code === 200, "方案建出来了：" + JSON.stringify(r).slice(0, 70));
  ok(
    q1(`SELECT COUNT(*) FROM mediaoftask mt JOIN task t ON t.taskid=mt.taskid WHERE t.info LIKE '${PLAN}%'`) === "1",
    "库里只有一行铃声"
  );
}

// ── ⑤ 开发者接口那条路也拦得住 ──
console.log("⑤ 服务层拦得住多铃声（不是只改了界面）");
{
  const r = await call("POST", "/api/bell-plans", {
    planName: PLAN + "二",
    schedule: { startdate: "2026-09-14", enddate: "2026-12-31", exemodel: "1111100" },
    playback: { defaultvolume: 80, priority: 10, prepower: 15, datasendmodel: 0, israndomplay: 0 },
    terminals: [{ terminalId: tid, groupId: 0, area: "" }],
    items: [
      {
        taskname: "第一节",
        playtime: "08:00:00",
        timelengthtype: 2,
        timelength: 1,
        media: [
          { mediaId: mids[0], sort: 0 },
          { mediaId: mids[1], sort: 1 }
        ]
      }
    ]
  });
  ok(r.code !== 200, "两个铃声被拒绝了");
  ok(String(r.msg || "").includes("只能有一个铃声"), "报错说清了规则：" + r.msg);
}

// ── ③ 老数据挂了两个：打开只回填第一个 + 明确提示 ──
console.log("③ 老数据挂了两个铃声：只回填第一个，并且明确提示");
{
  const taskid = Number(q1(`SELECT taskid FROM task WHERE info LIKE '${PLAN}%' AND taskname='第一节' LIMIT 1`));
  execSync(SQL(`INSERT INTO mediaoftask (mediaid, taskid, sort) VALUES (${mids[1]}, ${taskid}, 1)`), { stdio: "pipe" });
  ok(q1(`SELECT COUNT(*) FROM mediaoftask WHERE taskid=${taskid}`) === "2", "库里现在是两行（模拟老数据）");

  // ⚠ 用真刷新，不用改 hash：这个项目里踩过 —— hash 路由跳到同一个页面
  //   不会重新渲染，列表还是旧的，后面找按钮就一直找不到。
  await p.reload({ waitUntil: "domcontentloaded" });
  await p.waitForTimeout(4000);
  const row = p.locator(".el-table__body .el-table__row", { hasText: PLAN }).first();
  await row.locator("button", { hasText: "修改" }).first().click();
  await p.waitForTimeout(3000);
  const dlg = p.locator(".el-dialog:visible").first();
  const tip = await p
    .locator(".el-message--warning")
    .first()
    .innerText()
    .catch(() => "");
  console.log("   提示：", tip.replace(/\s+/g, " "));
  ok(tip.includes("多个铃声"), "打开时就提示了老数据挂了多个");
  ok((await dlg.locator(".el-table__body .tone-cell .el-tag").count()) >= 1, "那一行有「原有 N 个」的角标");
  const tag = await dlg
    .locator(".el-table__body .tone-cell .el-tag")
    .first()
    .innerText()
    .catch(() => "");
  ok(tag.includes("2"), "角标写明原来是 2 个：" + tag);
  await dlg.locator(".el-dialog__footer button").first().click();
  await p.waitForSelector(".el-overlay", { state: "hidden", timeout: 10000 }).catch(() => undefined);
  await p.waitForTimeout(600);
}

// ── ④ 批量修改的统一音乐也是单选 ──
console.log("④ 批量修改的「统一作息音乐」也是单选");
{
  const row = p.locator(".el-table__body .el-table__row", { hasText: PLAN }).first();
  await row.locator(".el-checkbox").first().click();
  await p.waitForTimeout(500);
  await p.locator("button", { hasText: "批量修改" }).first().click();
  await p.waitForTimeout(3000);
  const dlg = p.locator(".el-dialog:visible").first();
  const bar = dlg.locator(".batch-bar");
  ok((await bar.count()) === 1, "统一设置那一栏在");
  await bar.locator(".el-checkbox").first().click(); // 先勾「统一作息音乐」，不然下拉是禁用的
  await p.waitForTimeout(400);
  const r2 = await isSingleSelect(bar.locator(".el-select").first());
  console.log(`   先选「${r2.first}」再选「${r2.second}」，格子里显示：「${r2.shown}」`);
  ok(r2.ok, "统一音乐也是单选");
  await dlg.locator(".el-dialog__footer button").first().click();
  await p.waitForTimeout(600);
}

await b.close();
console.log(fails === 0 ? "\n全部通过" : `\n${fails} 条不通过`);
process.exit(fails === 0 ? 0 : 1);
