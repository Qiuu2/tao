// 第三步：登录后把每个功能页整页截图，作为改版面的依据。
import { launch, attach, sleep } from "./cdp.mjs";
import { mkdirSync } from "node:fs";

const SP = "C:\\Users\\Administrator\\AppData\\Local\\Temp\\claude\\c--hangtian-software----newweb\\bc0da2fe-d1ba-4031-a82d-ff0866f5dd9d\\scratchpad";
const OUT = SP + "\\shots";
const PROFILE = SP + "\\edgeprofile";
mkdirSync(OUT, { recursive: true });

// 路由 → 文件名。顺序与 1.png 的菜单一致。
const PAGES = [
  ["01-首页",        "/index"],
  ["02-服务器信息",  "/home/home"],
  ["03-时间设置",    "/home/time"],
  ["04-备份还原",    "/home/backup"],
  ["05-节假日管理",  "/home/holiday"],
  ["06-终端管理",    "/terminal/list"],
  ["07-终端分区",    "/terminal/cate"],
  ["08-文件管理",    "/terminal/media"],
  ["09-报警分区",    "/terminal/alarm-zone"],
  ["10-报警映射",    "/terminal/alarm-map"],
  ["11-遥控任务",    "/terminal/remote-task"],
  ["12-作息方案",    "/task/rest-plan"],
  ["13-文件广播",    "/task/file"],
  ["14-终端功放",    "/task/power"],
  ["15-采播管理",    "/task/broadcast"],
  ["16-文字语音",    "/task/tts"],
  ["17-led播放",     "/task/led/index"],
  ["18-启用管理",    "/task/enableManagement/index"],
  ["19-云广播终端",  "/broad-cast/terminal"],
  ["20-音乐传输",    "/broad-cast/music-transfer"],
  ["21-任务传送",    "/broad-cast/task-transfer"],
  ["22-噪声设备",    "/noise/equipment"],
  ["23-声场分区",    "/noise/partition"]
];

const proc = await launch({ profile: PROFILE, width: 1600, height: 1000 });
const s = await attach();
await s.send("Page.enable");
await s.send("Runtime.enable");

// ---- 登录 ----
await s.goto("http://192.168.2.159/#/login", 6000);
const loggedIn = await s.eval("location.hash.indexOf('login') === -1");
if (!loggedIn) {
  await s.eval(`
    (() => {
      const set = (el, v) => {
        const d = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value');
        d.set.call(el, v);
        el.dispatchEvent(new Event('input',  { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
      };
      set(document.querySelector('#username'), 'admin');
      set(document.querySelector('#password'), '123456');
    })()
  `);
  await sleep(400);
  await s.eval(`
    (() => {
      const b = [...document.querySelectorAll('button')].find(x => (x.innerText||'').trim() === '登录');
      if (b) b.click();
    })()
  `);
  await sleep(6000);
}
console.log("登录后:", await s.eval("location.href"));

// ---- 逐页截图 ----
for (const [name, route] of PAGES) {
  try {
    // hash 路由：直接改 hash 再等渲染，比整页 navigate 快也更稳
    await s.eval(`location.hash = ${JSON.stringify("#" + route)}`);
    await sleep(2600);

    const info = await s.eval(`
      JSON.stringify({
        href: location.href,
        title: document.title,
        h: Math.max(document.body.scrollHeight, document.documentElement.scrollHeight),
        txt: (document.body.innerText||'').replace(/\\s+/g,' ').slice(0,120)
      })
    `);
    const { href, title, h, txt } = JSON.parse(info);

    // 整页高度截图
    const height = Math.min(Math.max(h, 900), 4000);
    await s.send("Emulation.setDeviceMetricsOverride", {
      width: 1600, height, deviceScaleFactor: 1, mobile: false
    });
    await sleep(500);
    await s.shot(`${OUT}\\${name}.png`);
    await s.send("Emulation.clearDeviceMetricsOverride");

    console.log(`✔ ${name.padEnd(16)} ${String(h).padStart(5)}px  ${title}  ${href.split('#')[1] || ''}`);
  } catch (e) {
    console.log(`✘ ${name}  ${e.message}`);
  }
}

s.close();
proc.kill();
console.log("\n截图目录: " + OUT);
