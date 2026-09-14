/*
 * 操作日志「写明具体动的是谁」的回归测试。
 *
 * # 它盯的是什么
 *
 * 需求原话：「所有操作内容需要写明具体哪个任务的操作，还有哪个终端，哪个分区，
 * 等等必需具体说明，超过一行用...代替。」
 *
 * 在这之前，日志里一行「删除终端」，追责时等于没记 —— 删的是哪一台？
 * 现在中间件在调 handler **之前**把对象名查出来，成功了才写进去。
 *
 * 七条：
 *   ① 新建：对象还不存在，名字从请求体里取
 *   ② 修改（路径带 id）：按 id 查库拿当时的名字
 *   ③ 批量（请求体带 ids）：列前三个
 *   ④ 超过三个收成「等 N 个」
 *   ⑤ **删除也要有名字** —— 这条最要紧：名字必须在删之前取，
 *      handler 跑完那行记录就没了，事后查只能查到空
 *   ⑥ 超长的截断并补省略号，而且塞得进 operate 列（varchar(255) 字节）
 *   ⑦ 日志页面那一列**看得到**这些名字（不是只落了库）
 *
 * # 怎么跑
 *
 *   node e2e/audit-detail.mjs
 *
 * ⚠ 会**真的改库**：建几个叫 E2E审计* 的终端分区，改一轮再删掉；
 *   log 表里会多出这一轮的记录，跑完一并清掉。
 */
const BASE = process.env.E2E_BASE || "http://127.0.0.1:5199";
const CHROME = process.env.E2E_CHROME || "/opt/pw-browsers/chromium-1194/chrome-linux/chrome";
const SQL = q => `mariadb -uroot --default-character-set=utf8 audioserver -e "${q}"`;

const { chromium } = await import(process.env.E2E_PLAYWRIGHT || "/opt/node22/lib/node_modules/playwright/index.mjs");
const { execSync } = await import("node:child_process");

let fails = 0;
const ok = (c, m) => {
  console.log((c ? "  ✓ " : "  ✗ ") + m);
  if (!c) fails++;
};

const UNSEED = [
  SQL("DELETE FROM serverplaystream WHERE name LIKE 'E2E审计%'"),
  SQL("DELETE FROM log WHERE operate LIKE '%E2E审计%'"),
];
const cleanup = () => {
  for (const q of UNSEED) {
    try {
      execSync(q, { stdio: "pipe" });
    } catch {
      console.log("  ! 清理失败，手工执行：" + q);
    }
  }
};
process.on("exit", cleanup);
cleanup(); // 上一次跑崩留下的先清掉

const q1 = sql => execSync(SQL(sql).replace(' -e "', ' -N -B -e "'), { encoding: "utf8" }).trim();

const b = await chromium.launch({ executablePath: CHROME });
const p = await b.newPage({ viewport: { width: 1500, height: 950 } });
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

// 拿页面里那把 token，后面直接打接口 —— 这一组盯的是**日志内容**，
// 走界面点一遍只会把测试变长变脆，最后一条才需要真界面。
const token = await p.evaluate(() => {
  for (const s of [localStorage, sessionStorage]) {
    for (let i = 0; i < s.length; i++) {
      const v = s.getItem(s.key(i)) || "";
      const m = v.match(/[A-Za-z0-9_-]{20,}\.[a-f0-9]{64}/);
      if (m) return m[0];
    }
  }
  return "";
});
ok(!!token, "拿到登录态");
if (!token) {
  await b.close();
  process.exit(1);
}

const call = async (method, path, body) => {
  const r = await p.evaluate(
    async ([m, u, tk, bd]) => {
      // 走页面自己的源（vite 代理转发到后端），否则是跨域，fetch 直接被拦
      const res = await fetch(u, {
        method: m,
        headers: { "Content-Type": "application/json", "Accept-Language": "zh-CN", "x-access-token": tk },
        body: bd === null ? undefined : JSON.stringify(bd),
      });
      return await res.json();
    },
    [method, path, token, body === undefined ? null : body],
  );
  return r;
};

const sinceID = () => q1("SELECT IFNULL(MAX(id),0) FROM log");

// ⚠ 分隔符不能用 \n 或 \t：mysql 的 -B 批处理模式会把输出里的控制字符转义成
// 字面的两个字符，拆出来的永远是一整条。用一个不会出现在日志正文里的记号。
const SEP = "|@|";
const opsSince = id => q1(`SELECT GROUP_CONCAT(operate SEPARATOR '${SEP}') FROM log WHERE id > ${id} ORDER BY id`).split(SEP);

// ── ① 新建：名字只能从请求体里来（对象还不存在）──
console.log("① 新建：对象还不存在，名字从请求体取");
let mark = sinceID();
const zoneIDs = [];
for (const n of ["E2E审计一区", "E2E审计二区", "E2E审计三区", "E2E审计四区"]) {
  const r = await call("POST", "/api/zones", { name: n });
  if (r.code === 200) zoneIDs.push(r.data.id);
}
ok(zoneIDs.length === 4, `建了 4 个分区（实际 ${zoneIDs.length}）`);
{
  const ops = opsSince(mark);
  ok(ops[0] === "新建终端分区：终端分区「E2E审计一区」", `日志写明了是哪个分区：${ops[0]}`);
}

// ── ② 修改：按路径里的 id 查库拿名字 ──
console.log("② 修改：按 id 查库拿名字");
mark = sinceID();
await call("PUT", `/api/zones/${zoneIDs[0]}`, { name: "E2E审计一区" });
{
  const ops = opsSince(mark);
  ok(ops[0] === "修改终端分区：终端分区「E2E审计一区」", `日志写明了改的是哪个：${ops[0]}`);
}

// ── ③④ 批量：列前三个，多了收成「等 N 个」──
console.log("③④ 批量：列前三个，多的收成「等 N 个」");
mark = sinceID();
const tIDs = q1("SELECT GROUP_CONCAT(id) FROM (SELECT id FROM terminal ORDER BY id LIMIT 5) x")
  .split(",")
  .map(Number);
await call("PUT", "/api/terminals/sync-time", { ids: tIDs.slice(0, 2) });
await call("PUT", "/api/terminals/sync-time", { ids: tIDs });
{
  const ops = opsSince(mark);
  ok(/^终端同步时间：终端「[^」]+」「[^」]+」$/.test(ops[0]), `两台：两个名字、没有「等 N 个」 → ${ops[0]}`);
  ok(/^终端同步时间：终端「[^」]+」「[^」]+」「[^」]+」等 5 个$/.test(ops[1]), `五台：列三个 + 等 5 个 → ${ops[1]}`);
}

// ── ⑤ 删除：名字必须在删之前取 ──
console.log("⑤ 删除：名字要在 handler 动手之前取（这条最容易写反）");
mark = sinceID();
await call("DELETE", "/api/zones", { ids: [zoneIDs[1]] });
{
  const ops = opsSince(mark);
  ok(
    ops[0] === "删除终端分区：终端分区「E2E审计二区」",
    `删掉的分区名进了日志（handler 跑完那行记录就没了）：${ops[0]}`,
  );
  ok(q1(`SELECT COUNT(*) FROM serverplaystream WHERE streamid=${zoneIDs[1]}`) === "0", "分区是真删掉了");
}

// ── ⑥ 超长：截断补省略号，且塞得进 operate 列 ──
console.log("⑥ 超长：截断补省略号（需求原话「超过一行用...代替」）");
mark = sinceID();
{
  const long = "E2E审计" + "超长分区名".repeat(15); // 80 字 = 240 字节，不触发后端的名称长度上限
  const r = await call("POST", "/api/zones", { name: long });
  ok(r.code === 200, "超长名字的分区建出来了");
  if (r.code === 200) zoneIDs.push(r.data.id);
  const row = q1(`SELECT CONCAT(operate,'${SEP}',CHAR_LENGTH(operate),'${SEP}',LENGTH(operate)) FROM log WHERE id > ${mark} LIMIT 1`);
  const [op, chars, bytes] = row.split(SEP);
  ok(Number(chars) === 60, `截到 60 个字（实际 ${chars}）`);
  ok(op.endsWith("…"), "末尾补了省略号");
  ok(Number(bytes) <= 255, `${bytes} 字节，塞得进 operate 列（varchar(255)）`);
}

// ── ⑦ 日志页面上看得见 ──
console.log("⑦ 日志页面那一列看得见对象名");
await p.goto(BASE + "/#/log", { waitUntil: "domcontentloaded" });
await p.waitForTimeout(2500);
{
  const txt = await p.locator(".el-table__body").first().innerText();
  ok(txt.includes("E2E审计"), "列表里能看到刚才那几条带分区名的记录");
  ok(
    txt.includes("新建终端分区：终端分区「E2E审计一区」"),
    "整句「动作：对象」都显示出来了（不是只落了库）",
  );
}

// 收尾：把自己建的分区删干净（log 行由 cleanup 清）
for (const id of zoneIDs) {
  await call("DELETE", "/api/zones", { ids: [id] });
}

await b.close();
console.log(fails === 0 ? "\n全部通过" : `\n${fails} 条不通过`);
process.exit(fails === 0 ? 0 : 1);
