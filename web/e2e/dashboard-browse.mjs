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
 * 验的需求：
 *   ① 「任务管理」下拉按模块筛
 *   ② 星期选「今天」时，起止日期不覆盖今天的任务**不出现**
 *   ③ **停用的任务（projectstate=1）一条都不出现**
 *   ④ 「单独停用日」列显示 task.disableday
 *   ⑤ 换一个星期，「看的是哪一天」跟着变，列表跟着那一天走
 *   ⑥ 状态列五个取值（已执行 / 准备执行 / 正在执行 / 暂停 / 立即执行），
 *     只有 state = 0 才拿钟点去分前两个
 *   ⑦ 单独停用日**原样**显示库里那一列（0000-00-00 也照显）
 *   ⑧ 所属分类：只有作息方案带括号（里面是**方案名**），
 *     不是 parentid 指的那个默认目录
 *   ⑨ 「所属用户」列
 *   ⑩ 「当天启用 / 当天停用」是**操作**不是筛选：勾几行点下去，
 *     停用写入所看那一天的日期、启用写回 0000-00-00，子任务跟着一起改
 *   ⑪ 行上的「执行 / 停止」对**列得出来的每一种任务**都生效
 *     （原来只认 tasktype 2/7/15，点别的一律弹「不支持启停」）
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
const OUT = "E2E过期任务",
  OFF = "E2E停用任务";
const cleanup = () => {
  try {
    execSync(
      SQL(
        `DELETE FROM task WHERE taskname='${OUT}' OR taskname='${OFF}'; ` +
          `UPDATE task SET disableday='0000-00-00' WHERE taskname IN ('晨间开功放','课间轻音乐','升旗仪式-国歌'); ` +
          `UPDATE task SET disableday='0000-00-00' WHERE sec_task_id IN (SELECT taskid FROM (SELECT taskid FROM task WHERE taskname='升旗仪式-国歌') x); ` +
          `UPDATE task SET projectstate=0 WHERE taskname='课间轻音乐'`
      ),
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
// 再复制一条**停用**（projectstate=1）的，③ 要验它一条都不出现。
// 起止日期覆盖今天、星期全勾，除了停用之外没有任何别的理由被滤掉。
execSync(
  SQL(`INSERT INTO task SELECT NULL, '${OFF}', t.* FROM (
                SELECT israndomplay,1 AS projectstate,timelengthtype,timelength,prepower,datasendmodel,state,
                       '2020-01-01' AS startdate,'2099-12-31' AS enddate,playtime,endtime,'1111111' AS exemodel,
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


/*
 * 列序（1 基）。⚠ 加了最左边的勾选列和「所属用户」之后全体右移，
 * 以前散在各处的魔数一改就得全文找一遍，集中放这儿。
 *
 *   1 勾选   2 序号   3 任务名称  4 所属分类  5 所属用户  6 周期
 *   7 播放时间  8 状态  9 起始  10 结束  11 单独停用日  12 终端数  13 操作
 */
const COL = { name: 3, category: 4, owner: 5, playtime: 7, status: 8, disableday: 11 };
const cell = i => p.locator(`.panel .el-table__body .el-table__row td:nth-child(${i})`);
const colTexts = async i => (await cell(i).allInnerTexts()).map(v => v.trim());

const allRows = async () =>
  (await p.locator(".panel .el-table__body .el-table__row").allInnerTexts()).map(r => r.replace(/\s+/g, " "));
let rows = await allRows();
console.log("② 有效期 2020 年的那条在不在:", rows.some(r => r.includes(OUT)) ? "在（错）" : "不在（对）");
ok(!rows.some(r => r.includes(OUT)), "起止日期不覆盖今天的任务被滤掉了");
ok(q1(`SELECT COUNT(*) FROM task WHERE taskname='${OUT}'`) === "1", "那条任务确实还在库里（是被筛掉的，不是没建出来）");

// ③ 停用的任务一条都不出现
console.log("③ 停用的任务（projectstate=1）不该出现在看板上");
{
  const inDB = q1(`SELECT COUNT(*) FROM task WHERE taskname='${OFF}' AND projectstate=1`);
  console.log(`   库里那条停用任务:`, inDB, "条；列表里", rows.some(r => r.includes(OFF)) ? "有（错）" : "没有（对）");
  ok(inDB === "1", "那条停用任务确实建出来了（起止日期覆盖今天、星期全勾，只有停用这一条理由）");
  ok(!rows.some(r => r.includes(OFF)), "停用的任务被滤掉了 —— 与旧版 task.projectstate=0 一致");
  // 把它启用回来，就该出现 —— 证明滤掉它的确实是 projectstate，不是别的原因
  execSync(SQL(`UPDATE task SET projectstate=0 WHERE taskname='${OFF}'`), { stdio: "pipe" });
  await p.reload({ waitUntil: "domcontentloaded" });
  await p.waitForTimeout(4000);
  const back = await allRows();
  ok(back.some(r => r.includes(OFF)), "改成启用之后它就出现了（说明滤掉它的就是 projectstate）");
  execSync(SQL(`UPDATE task SET projectstate=1 WHERE taskname='${OFF}'`), { stdio: "pipe" });
  await p.reload({ waitUntil: "domcontentloaded" });
  await p.waitForTimeout(4000);
  rows = await allRows();
}

// ⑨ 所属用户列
console.log("⑨ 「所属用户」列");
{
  const head = (await p.locator(".panel .el-table__header").innerText()).replace(/\s+/g, " ");
  console.log("   表头:", head.slice(0, 140));
  ok(head.includes("所属用户"), "表头上有「所属用户」这一列");
  const owners = await colTexts(COL.owner);
  console.log("   取值:", JSON.stringify([...new Set(owners)]));
  ok(owners.length > 0 && owners.every(v => v !== ""), "每一行都写了归属，没有空格子");
  // 跟库里对答案
  const taskNames = await colTexts(COL.name);
  const want = q1(
    `SELECT COALESCE(b.username,'') FROM task t LEFT JOIN book_admin b ON b.id=t.task_user_id ` +
      `WHERE t.taskname='${taskNames[0]}' AND t.sec_task_id=0 LIMIT 1`
  );
  console.log(`   「${taskNames[0]}」→ 界面 ${owners[0]}，库里 ${want}`);
  ok(owners[0] === want, "显示的归属与 task.task_user_id 指的账号对得上");
}

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
  // 列表本身就跟着那一天走：星期掩码里那一天这一位不是 1 的任务不出现。
  //
  // ⚠ 这一条原来是靠「当天启用 / 当天停用」那组单选做判据的。
  //   那组单选是误读（它们其实是操作，见 ⑩），已经去掉 ——
  //   现在星期这一位是**硬过滤**，与旧版 Browse_active_task.php:250 的
  //   `SUBSTRING(task.exemodel, <那一位>, 1)` 一致，直接看行在不在就行。
  let rows2 = await allRows();
  ok(rows2.some(r => r.includes("升旗仪式-国歌")), "周日：只在周日执行的任务在列表里");

  const wsel2 = p.locator(".filter-bar .el-select").nth(2);
  await wsel2.click();
  await p.waitForTimeout(800);
  await p.locator(".el-select-dropdown:visible .el-select-dropdown__item", { hasText: "周一" }).first().click();
  await p.waitForTimeout(2500);
  rows2 = await allRows();
  ok(!rows2.some(r => r.includes("升旗仪式-国歌")), "换成周一：它就不在了 —— 列表跟着所看那一天走");
  ok(
    q1("SELECT exemodel FROM task WHERE taskname='升旗仪式-国歌' AND sec_task_id=0 LIMIT 1")[1] === "0",
    "它的 exemodel 里周一那一位确实是 0（筛掉它的就是这一位）"
  );
}
// ⑥ 状态列五个取值
console.log("⑥ 状态列：已执行 / 准备执行 / 正在执行 / 暂停 / 立即执行");
{
  await p.reload({ waitUntil: "domcontentloaded" });
  await p.waitForTimeout(4000);
  const nowClock = q1("SELECT TIME_FORMAT(NOW(),'%H:%i:%s')");
  const st = await colTexts(COL.status);
  const tm = await colTexts(COL.playtime);
  const names = await colTexts(COL.name);
  console.log("   状态取值:", JSON.stringify([...new Set(st)]), " 服务器现在:", nowClock);
  const ALL = ["已执行", "准备执行", "正在执行", "暂停", "立即执行"];
  ok(
    st.every(v => ALL.includes(v)),
    `只有这五种取值：${ALL.join(" / ")}`
  );
  // 每一行该显示什么，直接拿库里的 state 和服务器时钟算一遍对答案。
  // 判据：只有 state = 0 才比时间；1/2/3 各是各的。
  const stateOf = n => Number(q1(`SELECT COALESCE(state,0) FROM task WHERE taskname='${n}' AND sec_task_id=0 LIMIT 1`));
  const wantOf = (n, playtime) => {
    const s0 = stateOf(n);
    if (s0 === 1) return "正在执行";
    if (s0 === 2) return "暂停";
    if (s0 === 3) return "立即执行";
    return playtime <= nowClock ? "已执行" : "准备执行";
  };
  const bad = st.filter((v, i) => {
    const want = wantOf(names[i], tm[i]);
    if (v !== want) console.log(`   对不上：${names[i]} 显示「${v}」，按 state+时钟应该是「${want}」`);
    return v !== want;
  }).length;
  ok(bad === 0, `每行都与「task.state + 服务器当前时刻」对得上（${bad} 行对不上）`);

  const tagClass = async txt => {
    const tag = p.locator(".panel .el-table__body .el-table__row .el-tag", { hasText: txt }).first();
    return (await tag.count()) ? (await tag.getAttribute("class")) || "" : "";
  };
  const readyCls = await tagClass("准备执行");
  console.log("   「准备执行」的 tag class:", readyCls || "(这一屏没有准备执行的行)");
  if (readyCls) ok(readyCls.includes("el-tag--success"), "「准备执行」是绿的（el-tag--success）");

  // 1 / 2 / 3 各试一遍：把同一条任务的 state 依次改掉，看文案和颜色
  const victim = names[0];
  const oldState = stateOf(victim);
  for (const [stv, text, cls] of [
    [1, "正在执行", "el-tag--danger"],
    [2, "暂停", "el-tag--warning"],
    [3, "立即执行", "el-tag--danger"]
  ]) {
    execSync(SQL(`UPDATE task SET state=${stv} WHERE taskname='${victim}' AND sec_task_id=0`), { stdio: "pipe" });
    await p.reload({ waitUntil: "domcontentloaded" });
    await p.waitForTimeout(4000);
    const nowSt = await colTexts(COL.status);
    const nowNm = await colTexts(COL.name);
    const shown = nowSt[nowNm.indexOf(victim)];
    const cl = await tagClass(text);
    console.log(`   state=${stv} → 「${shown}」，tag class: ${cl}`);
    ok(shown === text, `state=${stv} 的那条显示成「${text}」`);
    ok(cl.includes(cls), `「${text}」用的是 ${cls}`);
  }
  execSync(SQL(`UPDATE task SET state=${oldState} WHERE taskname='${victim}' AND sec_task_id=0`), { stdio: "pipe" });
  await p.reload({ waitUntil: "domcontentloaded" });
  await p.waitForTimeout(4000);
}

// ⑦ 单独停用日原样显示库里的值
console.log("⑦ 单独停用日原样显示库里那一列");
{
  const shown = await colTexts(COL.disableday);
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
  const names = await colTexts(COL.name);
  const cats = await colTexts(COL.category);
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
  // ⚠ 要从**屏幕上真有的**那几行里挑，不能去库里随便捞一条 ——
  //   星期那一位现在是硬过滤，只在周日响的任务今天根本不在列表里，
  //   捞出来就永远是「(没找到这一行)」。这一条这么栽过一次。
  const want = { 终端功放: 5, 采播管理: 3, 文件广播: 2 };
  for (const [mod, ty] of Object.entries(want)) {
    const nm = names.find(
      n => q1(`SELECT COALESCE(tasktype,0) FROM task WHERE taskname='${n}' AND sec_task_id=0 LIMIT 1`) === String(ty)
    );
    if (!nm) {
      console.log(`   ${mod}：这一屏没有，跳过`);
      continue;
    }
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

// ⑩ 「当天启用 / 当天停用」是操作，不是筛选
console.log("⑩ 「当天启用 / 当天停用」勾几行点下去，写的是 task.disableday");
{
  await p.reload({ waitUntil: "domcontentloaded" });
  await p.waitForTimeout(4000);
  ok((await p.locator(".scope-bar .el-radio-button").count()) === 0, "那组单选没了（它本来就不该是筛选）");
  const offBtn = p.locator(".scope-bar button", { hasText: "当天停用" }).first();
  const onBtn = p.locator(".scope-bar button", { hasText: "当天启用" }).first();
  ok(await offBtn.isDisabled(), "一条都没勾时「当天停用」是灰的");

  // ⚠ 特意切到**周日**再操作，一石二鸟：
  //   1. 这一天列出来的「升旗仪式-国歌」带一条功放子任务，能验「子任务跟着改」；
  //   2. 写进库里的该是**所看那一天**（周日），不是今天 —— 两者不同才验得出来。
  const wsel3 = p.locator(".filter-bar .el-select").nth(2);
  await wsel3.click();
  await p.waitForTimeout(800);
  await p.locator(".el-select-dropdown:visible .el-select-dropdown__item", { hasText: "周日" }).first().click();
  await p.waitForTimeout(2500);

  const viewDate = (/看的是 (\d{4}-\d{2}-\d{2})/.exec((await p.locator(".filter-bar").innerText()).replace(/\s+/g, " ")) || [])[1];
  const todayStr = q1("SELECT CURDATE()");
  console.log(`   看的是: ${viewDate}（今天是 ${todayStr}）`);
  ok(viewDate !== todayStr, "所看那一天与今天不是同一天 —— 下面才验得出「按看的那天写」");

  // 挑一条有功放子任务的，好顺带验「子任务跟着改」
  const names = await colTexts(COL.name);
  const target =
    names.find(n => Number(q1(`SELECT COUNT(*) FROM task WHERE sec_task_id=(SELECT taskid FROM task WHERE taskname='${n}' AND sec_task_id=0 LIMIT 1)`)) > 0) ||
    names[0];
  const tid = q1(`SELECT taskid FROM task WHERE taskname='${target}' AND sec_task_id=0 LIMIT 1`);
  const subCnt = Number(q1(`SELECT COUNT(*) FROM task WHERE sec_task_id=${tid}`));
  console.log(`   挑中「${target}」（taskid=${tid}，子任务 ${subCnt} 条）`);

  const rowOf = n => p.locator(".panel .el-table__body .el-table__row", { hasText: n }).first();
  await rowOf(target).locator(".el-checkbox").first().click();
  await p.waitForTimeout(600);
  ok(!(await offBtn.isDisabled()), "勾上一条之后按钮可点了");

  await offBtn.click();
  await p.waitForTimeout(1200);
  const dlg = p.locator(".el-message-box:visible").first();
  const dlgTxt = (await dlg.innerText()).replace(/\s+/g, " ");
  console.log("   确认框:", dlgTxt.slice(0, 120));
  ok(dlgTxt.includes(viewDate), "确认框里写明了停的是哪一天");
  await dlg.locator(".el-message-box__btns button").last().click();
  await p.waitForTimeout(3000);

  const got = q1(`SELECT CAST(disableday AS CHAR) FROM task WHERE taskid=${tid}`);
  console.log(`   库里 disableday =`, got);
  ok(got === viewDate, `「当天停用」把所看那一天（${viewDate}）写进了 task.disableday`);
  ok(got !== todayStr, "写的是所看那一天，不是今天");
  if (subCnt > 0) {
    const subDays = q1(`SELECT GROUP_CONCAT(DISTINCT CAST(disableday AS CHAR)) FROM task WHERE sec_task_id=${tid}`);
    console.log("   子任务的 disableday =", subDays);
    ok(subDays === viewDate, "功放 / LED 子任务的 disableday 跟着一起改了（旧版这一句也有）");
  }
  // 那一行还在列表里，并且列上显示出了这一天 —— 不列出来就没法点回启用。
  //
  // ⚠ 这里**不能 reload**：reload 会把星期下拉打回「今天」，而这条任务只在周日响，
  //   一刷新它就不在列表里了，断言会红在一个与被测功能无关的地方。
  //   点完之后页面自己就重新拉过列表了，直接看当前这张表。
  await p.waitForTimeout(1500);
  const after = await allRows();
  ok(after.some(r => r.includes(target) && r.includes(viewDate)), "那一行还在列表里，单独停用日列写着那一天");

  // 再点回「当天启用」
  await rowOf(target).locator(".el-checkbox").first().click();
  await p.waitForTimeout(600);
  await onBtn.click();
  await p.waitForTimeout(1200);
  await p.locator(".el-message-box:visible .el-message-box__btns button").last().click();
  await p.waitForTimeout(3000);
  const back = q1(`SELECT CAST(disableday AS CHAR) FROM task WHERE taskid=${tid}`);
  console.log("   点回当天启用之后 disableday =", back);
  ok(back === "0000-00-00", "「当天启用」把它写回 0000-00-00");
  if (subCnt > 0) {
    ok(
      q1(`SELECT GROUP_CONCAT(DISTINCT CAST(disableday AS CHAR)) FROM task WHERE sec_task_id=${tid}`) === "0000-00-00",
      "子任务也一起写回去了"
    );
  }
}

// ⑪ 「执行 / 停止」对每一种任务都生效
console.log("⑪ 行上的「执行 / 停止」对列得出来的每一种任务都生效");
{
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

  // 看板列得出来的全部 tasktype，每种挑一条真实存在的
  // （口径与后端 browseAllModules 一致）
  const TYPES = [1, 2, 3, 5, 7, 15, 17, 19, 24, 30];
  let tried = 0;
  for (const ty of TYPES) {
    const row = q1(
      `SELECT CONCAT(taskid,'|',taskname,'|',COALESCE(state,0)) FROM task ` +
        `WHERE tasktype=${ty} AND channel=0 AND sec_task_id=0 AND projectstate=0 LIMIT 1`
    );
    if (!row) {
      console.log(`   tasktype=${ty}：库里没有，跳过`);
      continue;
    }
    const [id, name, oldState] = row.split("|");
    tried++;
    const r = await call("PUT", "/api/tasks/control/start", { ids: [Number(id)] });
    const blocked = r.data?.blocked ?? [];
    const badType = blocked.find(x => x.reason === "BAD_TYPE");
    console.log(
      `   tasktype=${ty}「${name}」→ ${badType ? "被挡：" + badType.detail : blocked.length ? "被挡：" + blocked[0].detail : "放行"}`
    );
    // 只钉「不是因为类型被挡」—— 没媒体 / 没终端那种前置条件是另一回事，
    // 各模块自己的页面同样会挡，不是这次要改的东西。
    ok(!badType, `tasktype=${ty}「${name}」没有再被「不支持启停」挡下`);
    // 停止也走一遍：它的报文要带真实的 tasktype（17 还要换成 state=13）
    const r2 = await call("PUT", "/api/tasks/control/stop", { ids: [Number(id)] });
    ok(
      !(r2.data?.blocked ?? []).some(x => x.reason === "BAD_TYPE"),
      `tasktype=${ty}「${name}」的「停止」也不再被类型挡下`
    );
    execSync(SQL(`UPDATE task SET state=${Number(oldState) || 0} WHERE taskid=${id}`), { stdio: "pipe" });
  }
  ok(tried >= 5, `至少试到了 5 种任务类型（实际 ${tried} 种）`);
}

await b.close();
console.log(fails === 0 ? "\n全部通过" : `\n${fails} 条不通过`);
process.exit(fails === 0 ? 0 : 1);
