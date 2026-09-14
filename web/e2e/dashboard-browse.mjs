/*
 * 看板首页「浏览任务」那张表的回归测试。
 *
 * # 怎么跑
 *
 *   node e2e/dashboard-browse.mjs
 *
 * ⚠ 会**真的改库**：照着一条真任务复制出一条有效期在 2020 年的
 *   「E2E过期任务」，并把「晨间开功放」的 disableday 设成今天。
 *   跑完（含中途退出）都会还原。
 *
 * 验五条需求：
 *   ① 「任务管理」下拉按模块筛
 *   ② 星期选「今天」时，起止日期不覆盖今天的任务**不出现**
 *   ③ scope 默认「全部」
 *   ④ 「单独停用日」列显示 task.disableday
 *   ⑤ 换一个星期，「看的是哪一天」跟着变，启用/停用的判定也跟着那一天走
 *   ⑥ 状态列是「已执行 / 执行中 / 准备执行」，按 task.state + **服务器**当前时刻判
 *   ⑦ 单独停用日**原样**显示库里那一列（0000-00-00 也照显）
 *   ⑧ 所属分类：只有作息方案带括号（里面是**方案名**），
 *     不是 parentid 指的那个默认目录
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

// 造两条数据：一条有效期不含今天（该被滤掉），一条设了 disableday（该显示出来）
const OUT = "E2E过期任务";
const cleanup = () => {
  try {
    execSync(
      SQL(`DELETE FROM task WHERE taskname='${OUT}'; UPDATE task SET disableday='0000-00-00' WHERE taskname='晨间开功放'`),
      { stdio: "pipe" }
    );
  } catch {}
};
process.on("exit", cleanup);
cleanup();
// ⚠ 照着一条真任务复制再改，别手写 INSERT —— task 表有十几个 NOT NULL 且没默认值的列
//   （timelengthtype 就是一个），手写必漏。
execSync(
  SQL(`INSERT INTO task SELECT NULL, '${OUT}', t.* FROM (
                SELECT israndomplay,projectstate,timelengthtype,timelength,prepower,datasendmodel,state,
                       '2020-01-01' AS startdate,'2020-12-31' AS enddate,playtime,endtime,'1111111' AS exemodel,
                       priority,2 AS tasktype,channel,bandrate,samplerate,cmd,cmdargs,playfileid,'' AS info,
                       defaultvolume,task_user_id,sec_task_id,parentid,offlinestate,createtime,disableday,
                       interval_s,intplaylength,intplaylengthtype,localplay,keyid
                FROM task WHERE tasktype=2 AND channel=0 AND sec_task_id=0 LIMIT 1) t`),
  { stdio: "pipe" }
);
execSync(SQL(`UPDATE task SET disableday='2026-09-14' WHERE taskname='晨间开功放'`), { stdio: "pipe" });

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
await p.waitForTimeout(4500);

const bar = (await p.locator(".filter-bar").innerText()).replace(/\s+/g, " ");
console.log("① 筛选栏:", bar);
ok(bar.includes("任务管理： 全部"), "「任务管理」下拉默认显示「全部」（不是「请选择」）");
ok(bar.includes("任务分组："), "原来那个改叫「任务分组」了");
ok(/看的是 \d{4}-\d{2}-\d{2}/.test(bar), "写出了看的是哪一天");

console.log("③ scope 默认:", (await p.locator(".scope-bar .el-radio-button.is-active").innerText()).trim());
ok((await p.locator(".scope-bar .el-radio-button.is-active").innerText()).trim() === "全部", "默认是「全部」，不是「当天启用」");

const allRows = async () =>
  (await p.locator(".panel .el-table__body .el-table__row").allInnerTexts()).map(r => r.replace(/\s+/g, " "));
let rows = await allRows();
console.log("② 有效期 2020 年的那条在不在:", rows.some(r => r.includes(OUT)) ? "在（错）" : "不在（对）");
ok(!rows.some(r => r.includes(OUT)), "起止日期不覆盖今天的任务被滤掉了");
ok(q1(`SELECT COUNT(*) FROM task WHERE taskname='${OUT}'`) === "1", "那条任务确实还在库里（是被筛掉的，不是没建出来）");

const line = rows.find(r => r.includes("晨间开功放")) || "";
console.log("④ 设了 disableday 的那行:", line.slice(0, 160));
ok(line.includes("2026-09-14"), "「单独停用日」列显示出了那一天");

// ① 按模块筛
console.log("① 切到「作息方案」");
const sel = p.locator(".filter-bar .el-select").first();
await sel.click();
await p.waitForTimeout(800);
await p.locator(".el-select-dropdown:visible .el-select-dropdown__item", { hasText: "作息方案" }).first().click();
await p.waitForTimeout(2500);
rows = await allRows();
console.log("   作息方案下的行数:", rows.length);
rows.slice(0, 3).forEach(r => console.log("    ", r.slice(0, 110)));
ok(rows.length > 0, "作息方案筛出来有数据");
ok(!rows.some(r => r.includes("晨间开功放")), "文件广播那条（晨间开功放）不在作息方案里了");

// ⑤ 换一个星期：看的那一天要跟着变，日期范围也按那一天比
console.log("⑤ 星期改选「周日」，看的日期要跟着变");
{
  // 先把「任务管理」放回全部 —— 上一步切到了作息方案，而要看的那条
  // 「升旗仪式-国歌」是文件广播（tasktype=2），不放回去就根本不在列表里
  const msel = p.locator(".filter-bar .el-select").first();
  await msel.click();
  await p.waitForTimeout(800);
  await p.locator(".el-select-dropdown:visible .el-select-dropdown__item", { hasText: "全部" }).first().click();
  await p.waitForTimeout(2000);
  const wsel = p.locator(".filter-bar .el-select").nth(2);
  await wsel.click();
  await p.waitForTimeout(800);
  await p.locator(".el-select-dropdown:visible .el-select-dropdown__item", { hasText: "周日" }).first().click();
  await p.waitForTimeout(2500);
  const bar2 = (await p.locator(".filter-bar").innerText()).replace(/\s+/g, " ");
  const m = /看的是 (\d{4}-\d{2}-\d{2})/.exec(bar2);
  console.log("   ", bar2);
  ok(!!m, "还是写着看的是哪一天");
  const shown = m && m[1];
  const expect = q1("SELECT DATE_ADD(CURDATE(), INTERVAL (1 - DAYOFWEEK(CURDATE())) DAY)");
  ok(shown === expect, `那一天是本周的周日 ${expect}（界面上写的是 ${shown}）`);
  // 「那天启不启用」的判定也要跟着那一天走。
  //
  // ⚠ 这一条原来是看状态列里那句「当天启用」—— 后来状态列改成了
  //   「未执行 / 已执行」（⑥），那个字眼就不在了。启用与否现在由上面那组
  //   单选来筛，所以改成用它做判据：切到「当天启用」，只在周日执行的
  //   「升旗仪式-国歌」应该在；切回周一，它就该消失。
  const pickScope = async label => {
    await p.locator(".scope-bar .el-radio-button", { hasText: label }).first().click();
    await p.waitForTimeout(2500);
  };
  await pickScope("当天启用");
  let rows2 = await allRows();
  ok(
    rows2.some(r => r.includes("升旗仪式-国歌")),
    "周日 + 当天启用：只在周日执行的任务在列表里"
  );

  const wsel2 = p.locator(".filter-bar .el-select").nth(2);
  await wsel2.click();
  await p.waitForTimeout(800);
  await p.locator(".el-select-dropdown:visible .el-select-dropdown__item", { hasText: "周一" }).first().click();
  await p.waitForTimeout(2500);
  rows2 = await allRows();
  ok(!rows2.some(r => r.includes("升旗仪式-国歌")), "换成周一 + 当天启用：它就不在了 —— 启用判定跟着所看那一天走");
  await pickScope("全部");
}
// ⑥ 状态列：已执行 / 执行中 / 准备执行
console.log("⑥ 状态列是「已执行 / 执行中 / 准备执行」");
{
  await p.reload({ waitUntil: "domcontentloaded" });
  await p.waitForTimeout(4000);
  const nowClock = q1("SELECT TIME_FORMAT(NOW(),'%H:%i:%s')");
  const cell = i => p.locator(`.panel .el-table__body .el-table__row td:nth-child(${i})`);
  const st = (await cell(6).allInnerTexts()).map(v => v.trim());
  const tm = (await cell(5).allInnerTexts()).map(v => v.trim());
  const names = (await cell(2).allInnerTexts()).map(v => v.trim());
  console.log("   状态取值:", JSON.stringify([...new Set(st)]), " 服务器现在:", nowClock);
  ok(
    st.every(v => ["已执行", "执行中", "准备执行"].includes(v)),
    "只有「已执行 / 执行中 / 准备执行」三种取值"
  );
  // 每一行该显示什么，直接拿库里的 state 和服务器时钟算一遍对答案：
  // state 非 0（1 执行 / 2 暂停 / 3 立即执行）= 执行中，0 才比时间
  const stateOf = n => Number(q1(`SELECT COALESCE(state,0) FROM task WHERE taskname='${n}' AND sec_task_id=0 LIMIT 1`));
  const bad = st.filter((v, i) => {
    const want = stateOf(names[i]) !== 0 ? "执行中" : tm[i] <= nowClock ? "已执行" : "准备执行";
    if (v !== want) console.log(`   对不上：${names[i]} 显示「${v}」，按 state+时钟应该是「${want}」`);
    return v !== want;
  }).length;
  ok(bad === 0, `每行都与「task.state + 服务器当前时刻」对得上（${bad} 行对不上）`);

  // 颜色：执行中标红、准备执行标绿（需求方定的，旧版正好相反，不照抄）
  const tagClass = async txt => {
    const tag = p.locator(".panel .el-table__body .el-table__row .el-tag", { hasText: txt }).first();
    return (await tag.count()) ? (await tag.getAttribute("class")) || "" : "";
  };
  const readyCls = await tagClass("准备执行");
  console.log("   「准备执行」的 tag class:", readyCls || "(这一屏没有准备执行的行)");
  if (readyCls) ok(readyCls.includes("el-tag--success"), "「准备执行」是绿的（el-tag--success）");
  // 把一条任务临时置成执行中，看颜色对不对（跑完还原）
  const victim = names[0];
  const oldState = stateOf(victim);
  execSync(SQL(`UPDATE task SET state=1 WHERE taskname='${victim}' AND sec_task_id=0`), { stdio: "pipe" });
  await p.reload({ waitUntil: "domcontentloaded" });
  await p.waitForTimeout(4000);
  const nowSt = (await cell(6).allInnerTexts()).map(v => v.trim());
  const nowNm = (await cell(2).allInnerTexts()).map(v => v.trim());
  console.log(`   把「${victim}」的 state 改成 1 之后：${nowSt[nowNm.indexOf(victim)]}`);
  ok(nowSt[nowNm.indexOf(victim)] === "执行中", "state=1 的那条显示成「执行中」");
  const runCls = await tagClass("执行中");
  console.log("   「执行中」的 tag class:", runCls);
  ok(runCls.includes("el-tag--danger"), "「执行中」是红的（el-tag--danger）");
  execSync(SQL(`UPDATE task SET state=${oldState} WHERE taskname='${victim}' AND sec_task_id=0`), { stdio: "pipe" });
  await p.reload({ waitUntil: "domcontentloaded" });
  await p.waitForTimeout(4000);
}

// ⑦ 单独停用日原样显示库里的值
console.log("⑦ 单独停用日原样显示库里那一列");
{
  const shown = (await p.locator(".panel .el-table__body .el-table__row td:nth-child(9)").allInnerTexts()).map(v => v.trim());
  console.log("   这一列的取值:", JSON.stringify([...new Set(shown)]));
  ok(!shown.includes("—"), "没有把 0000-00-00 折成「—」");
  const dbVals = q1(
    "SELECT GROUP_CONCAT(DISTINCT CAST(disableday AS CHAR)) FROM task WHERE tasktype IN (1,2,3,5,7,15,17,19,24,30) AND channel=0 AND sec_task_id=0"
  ).split(",");
  ok(
    shown.every(v => dbVals.includes(v)),
    "显示的每个值都来自库里那一列：" + JSON.stringify(dbVals)
  );
}

// ⑧ 所属分类：只有作息方案带括号
console.log("⑧ 所属分类 = 模块名，只有作息方案后面跟（方案名）");
{
  const names = (await p.locator(".panel .el-table__body .el-table__row td:nth-child(2)").allInnerTexts()).map(v => v.trim());
  const cats = (await p.locator(".panel .el-table__body .el-table__row td:nth-child(3)").allInnerTexts()).map(v => v.trim());
  const of = n => cats[names.indexOf(n)] || "(没找到这一行)";

  // 作息方案的条目：括号里必须是**方案名**，不是 parentid 指的那个目录。
  // 现网这几条的 parentid 全是 1（filetaskfree 里的 admin 默认组），
  // 原来就是显示成「admin」—— 这一条专门钉住别再退回去。
  const planName = q1("SELECT info FROM task WHERE tasktype IN (1,15) AND COALESCE(info,'')<>'' LIMIT 1");
  const lesson = q1(`SELECT taskname FROM task WHERE info='${planName}' ORDER BY taskid LIMIT 1`);
  console.log(`   作息条目「${lesson}」→ ${of(lesson)}`);
  ok(of(lesson) === `作息方案（${planName}）`, `作息条目显示成「作息方案（${planName}）」`);
  ok(of(lesson) !== "admin", "不再是 parentid 指的那个默认目录名「admin」");

  // 其余几类：**只写模块名，不带括号**。
  // 文件广播原来会显示成「文件广播（admin）」—— 括号里是 filetaskfree 的分组名，
  // 而这一列问的是「属于哪个功能模块」，按需求方要求去掉。
  const want = { 终端功放: 5, 采播管理: 3, 文件广播: 2 };
  for (const [mod, ty] of Object.entries(want)) {
    const nm = q1(`SELECT taskname FROM task WHERE tasktype=${ty} AND channel=0 AND sec_task_id=0 LIMIT 1`);
    if (!nm) continue;
    console.log(`   ${mod}「${nm}」→ ${of(nm)}`);
    ok(of(nm) === mod, `${mod}这一类就写「${mod}」，不带括号`);
  }
  // 除了作息方案，谁都不许带括号
  const withParen = cats.filter(c => c.includes("（")).filter(c => !c.startsWith("作息方案"));
  ok(withParen.length === 0, "只有作息方案带括号，其余都不带：" + JSON.stringify([...new Set(withParen)]));
  ok(
    cats.every(c => c && c !== "(未分组)"),
    "没有哪一行还是空的或「(未分组)」"
  );
}

await b.close();
console.log(fails === 0 ? "\n全部通过" : `\n${fails} 条不通过`);
process.exit(fails === 0 ? 0 : 1);
