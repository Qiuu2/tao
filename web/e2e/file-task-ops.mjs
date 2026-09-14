/*
 * 文件广播页的一批修正。
 *
 * # 怎么跑
 *
 *   node e2e/file-task-ops.mjs
 *
 * ⚠ 会**真的改库**：改一条真任务的 israndomplay / timelengthtype，跑完还原。
 *
 * 验：
 *   ① 播放时长显示「播放 N 秒」——「timelengthtype = 0」的存量行不能被当成循环次数
 *   ② 播放模式列：israndomplay = 1 是**随机**、0 是**顺序**（库里列注释写反了）
 *   ③ 打开修改：播放模式是「普通模式」，时长按秒回填；点保存之后库里不变样
 *      （原来会把 900 秒的任务存成「循环 1 次」）
 *   ④ 媒体树里选中项的序号**红色标在名字前面**，下面不再另摆一份清单
 *   ⑤ 任务名称能填到 12 个字
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

// 挑一条真任务来折腾，记下原值，跑完还原
const TASK = "课间轻音乐";
const tid = q1(`SELECT taskid FROM task WHERE taskname='${TASK}' AND sec_task_id=0 LIMIT 1`);
const orig = q1(
  `SELECT CONCAT(COALESCE(israndomplay,0),'|',COALESCE(timelengthtype,0),'|',COALESCE(timelength,0),'|',COALESCE(interval_s,0),'|',COALESCE(intplaylength,0),'|',COALESCE(intplaylengthtype,0)) FROM task WHERE taskid=${tid}`
).split("|");
const restore = () => {
  try {
    execSync(
      SQL(
        `UPDATE task SET israndomplay=${orig[0]}, timelengthtype=${orig[1]}, timelength=${orig[2]}, ` +
          `interval_s=${orig[3]}, intplaylength=${orig[4]}, intplaylengthtype=${orig[5]} WHERE taskid=${tid}`
      ),
      { stdio: "pipe" }
    );
  } catch {}
};
process.on("exit", restore);
console.log(`   拿「${TASK}」（taskid=${tid}）做试验，原值 ${orig.join("|")}`);

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

const openTask = async () => {
  await p.goto(BASE + "/#/task", { waitUntil: "domcontentloaded" });
  // ⚠ 必须真的 reload。`#/task` 是同文档的 hash 跳转，已经在这一页时 goto 什么都不做，
  //   列表还是上一次的数据 —— 在库里改完再来看，看到的会是旧值，
  //   断言就红在一个与被测功能无关的地方（这个坑在 terminal-selection 里也栽过）。
  await p.reload({ waitUntil: "domcontentloaded" });
  await p.waitForTimeout(4500);
};
const rowOf = n => p.locator(".el-table__body .el-table__row", { hasText: n }).first();

// ① 播放时长：timelengthtype = 0 的存量行按**秒**显示
console.log("① 「播放时长」列：timelengthtype = 0 的存量行按秒显示");
{
  execSync(SQL(`UPDATE task SET timelengthtype=0, timelength=900 WHERE taskid=${tid}`), { stdio: "pipe" });
  await openTask();
  const line = (await rowOf(TASK).innerText()).replace(/\s+/g, " ");
  console.log("   这一行:", line.slice(0, 130));
  ok(line.includes("播放 900 秒"), "显示「播放 900 秒」");
  ok(!line.includes("循环 900 次"), "不是「循环 900 次」—— 旧版两张表单的第一个 radio（时长）默认就是选中的，0 等同于 1");
}

// ② 播放模式列：1 = 随机、0 = 顺序
console.log("② 「播放模式」列：1 是随机、0 是顺序（库里列注释写反了）");
{
  for (const [v, want, other] of [
    [1, "随机", "顺序"],
    [0, "顺序", "随机"]
  ]) {
    execSync(SQL(`UPDATE task SET israndomplay=${v} WHERE taskid=${tid}`), { stdio: "pipe" });
    await openTask();
    const line = (await rowOf(TASK).innerText()).replace(/\s+/g, " ");
    const got = line.includes(want) && !line.includes(other);
    console.log(`   israndomplay=${v} → ${line.includes("随机") ? "随机" : line.includes("顺序") ? "顺序" : "(都没有)"}`);
    ok(got, `israndomplay=${v} 显示「${want}」（旧版那个复选框叫「随机播放」，value=1）`);
  }
}

// ③ 修改：普通模式 + 时长按秒回填；保存之后库里不变样
console.log("③ 打开修改是「普通模式」，保存之后 900 秒还是 900 秒");
{
  execSync(SQL(`UPDATE task SET timelengthtype=0, timelength=900, interval_s=0 WHERE taskid=${tid}`), { stdio: "pipe" });
  await openTask();
  await rowOf(TASK).locator("button", { hasText: "编辑" }).first().click();
  await p.waitForTimeout(3000);
  const d = p.locator(".el-dialog:visible").first();
  const mode = (await d.locator(".el-form-item", { hasText: "播放模式" }).first().locator(".el-select__wrapper").first().innerText()).trim();
  console.log("   播放模式:", mode);
  ok(mode === "普通模式", "打开时是「普通模式」");
  const body = (await d.innerText()).replace(/\s+/g, " ");
  ok(!body.includes("间隔长度"), "表单上没有间隔模式那几栏");
  ok(/时长 00 时 15 分 00 秒/.test(body), "时长按秒回填成 00:15:00（900 秒），不是默认的 60 秒：" + body.slice(body.indexOf("时长"), body.indexOf("时长") + 40));

  await d.locator(".el-dialog__footer button").last().click();
  await p.waitForTimeout(4000);
  const after = q1(
    `SELECT CONCAT(COALESCE(timelengthtype,0),'|',COALESCE(timelength,0),'|',COALESCE(interval_s,0),'|',COALESCE(intplaylengthtype,0)) FROM task WHERE taskid=${tid}`
  );
  console.log("   保存后 tltype|tl|int_s|inttype =", after);
  const [tlt, tl, ints, intt] = after.split("|");
  ok(tlt === "1" && tl === "900", "保存后还是「按秒 900」—— 原来会存成「循环 1 次」");
  ok(ints === "0", "普通模式下 interval_s 归 0");
  ok(intt === "1", "普通模式下 intplaylengthtype 也复位了，不会留着上次的 2");
}

// ④ 媒体树里的序号标在名字前面，下面没有另一块清单
console.log("④ 选中媒体的序号红色标在名字前面，下面不再另摆一份清单");
{
  await openTask();
  await rowOf(TASK).locator("button", { hasText: "编辑" }).first().click();
  await p.waitForTimeout(3000);
  const d = p.locator(".el-dialog:visible").first();
  ok((await d.locator(".sortable").count()) === 0, "下面那块「已选清单」没了");
  // 展开一个媒体库，勾两个文件，看序号
  /*
   * 挑文件最多的那个库来展开 —— 随便挑一个可能只有一个文件，验不出序号。
   *
   * ⚠ 两个坑：
   *   1. `.mt-tree` 这个类挂在 el-tree 自己身上，不是外层容器，
   *      写成 `.mt-tree > .el-tree > …` 一个都选不到；
   *   2. 「文件数」那个角标（.mt-count）**只有带数字的库才有**，
   *      拿它的下标去索引 folders 会对错行 —— 得逐个节点自己读。
   */
  const folders = d.locator(".mt-tree > .el-tree-node > .el-tree-node__content");
  const counts = await folders.evaluateAll(ns =>
    ns.map(n => Number((n.querySelector(".mt-count") || {}).textContent || 0))
  );
  let best = 0;
  counts.forEach((c, i) => {
    if (c > counts[best]) best = i;
  });
  console.log("   各库的文件数:", JSON.stringify(counts), "→ 展开第", best + 1, "个");
  await folders.nth(best).locator(".el-tree-node__expand-icon").click();
  // 懒加载：展开之后要等接口回来，节点才挂上（2.5 秒不够，见下面那次「只数到 1 个」）
  await p.waitForTimeout(5000);
  // 叶子＝带 mt-seq 位置的那一层，用「没有展开箭头的可见节点」更稳：
  // 文件夹节点有 .el-tree-node__expand-icon 且不是 is-leaf
  const leaves = d.locator(".mt-tree .el-tree-node__children .el-tree-node__content:has(.el-tree-node__expand-icon.is-leaf)");
  const n = await leaves.count();
  console.log("   展开后叶子数:", n, " 树上节点总数:", await d.locator(".mt-tree .el-tree-node__content").count());
  console.log("   树上的节点:", JSON.stringify((await d.locator(".mt-tree .el-tree-node__content").allInnerTexts()).map(v => v.replace(/\s+/g, " ").trim())));
  if (n >= 2) {
    // 先清空，序号才从 1 开始数
    await d.locator(".mt-bar button", { hasText: "清空" }).first().click();
    await p.waitForTimeout(800);
    await leaves.nth(0).locator(".el-checkbox").first().click();
    await p.waitForTimeout(600);
    await leaves.nth(1).locator(".el-checkbox").first().click();
    await p.waitForTimeout(600);
    const seqs = (await d.locator(".mt-tree .mt-seq").allInnerTexts()).map(v => v.trim());
    console.log("   树上的序号:", JSON.stringify(seqs));
    ok(seqs.length === 2, "两个选中项各标了一个序号");
    ok(seqs.includes("1-|") && seqs.includes("2-|"), "序号是 1-| / 2-|，与旧版 select_item_sequence.js 的写法一致");
    const color = await d.locator(".mt-tree .mt-seq").first().evaluate(el => getComputedStyle(el).color);
    console.log("   序号颜色:", color);
    ok(/^rgb\(2[0-9]{2},\s*\d+,\s*\d+\)$/.test(color), "序号是红的：" + color);
    // 序号要跟着勾选先后走：取消第一个，第二个应该变成 1
    await leaves.nth(0).locator(".el-checkbox").first().click();
    await p.waitForTimeout(800);
    const after = (await d.locator(".mt-tree .mt-seq").allInnerTexts()).map(v => v.trim());
    console.log("   取消第一个之后:", JSON.stringify(after));
    ok(after.length === 1 && after[0] === "1-|", "剩下那个补成 1-|");
  } else {
    console.log("   这个库里文件不够两个，跳过序号断言");
  }
  await d.locator(".el-dialog__footer button", { hasText: "取消" }).first().click().catch(() => undefined);
  await p.keyboard.press("Escape");
  await p.waitForTimeout(800);
}

// ⑤ 任务名称 12 个字
console.log("⑤ 任务名称能填到 12 个字");
{
  await openTask();
  await p.locator("button", { hasText: "新增任务" }).first().click().catch(() => undefined);
  await p.waitForTimeout(2500);
  let d = p.locator(".el-dialog:visible").first();
  if (!(await d.count())) {
    await rowOf(TASK).locator("button", { hasText: "编辑" }).first().click();
    await p.waitForTimeout(3000);
    d = p.locator(".el-dialog:visible").first();
  }
  const nameInput = d.locator(".el-form-item", { hasText: "任务名称" }).first().locator("input").first();
  const max = await nameInput.getAttribute("maxlength");
  console.log("   maxlength =", max);
  ok(max === "12", "任务名称的 maxlength 是 12（旧版是 8）");
  await nameInput.fill("一二三四五六七八九十甲乙丙丁");
  const got = await nameInput.inputValue();
  console.log("   填 14 个字，实际留下:", got, `（${[...got].length} 个）`);
  ok([...got].length === 12, "按**字**数截断成 12 个，不是按字节");
}

console.log(fails ? `\n✗ ${fails} 条没过` : "\n全部通过");
await b.close();
restore();
process.exit(fails ? 1 : 0);
