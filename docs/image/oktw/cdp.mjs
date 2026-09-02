// 极简 CDP 驱动：靠 Node 24 自带的 WebSocket，不装任何依赖。
//
// 用途：把 192.168.2.159:80 的每个页面截下来，作为改版面的依据。
// 只读操作 —— 登录、点菜单、截图，不提交任何表单。
import { spawn } from "node:child_process";
import { mkdirSync, writeFileSync } from "node:fs";
import { setTimeout as sleep } from "node:timers/promises";

const EDGE = "C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe";
const PORT = 9222;

export async function launch({ profile, width = 1600, height = 1000, headless = true }) {
  mkdirSync(profile, { recursive: true });
  const args = [
    `--remote-debugging-port=${PORT}`,
    `--user-data-dir=${profile}`,
    `--window-size=${width},${height}`,
    "--no-first-run",
    "--no-default-browser-check",
    "--disable-extensions",
    "--disable-gpu",
    "--hide-scrollbars",
    // 内网 http，关掉一些会拦截的策略
    "--ignore-certificate-errors",
    "--allow-running-insecure-content",
    "about:blank"
  ];
  if (headless) args.unshift("--headless=new");
  const proc = spawn(EDGE, args, { stdio: "ignore", detached: false });

  // 等调试端口起来
  for (let i = 0; i < 60; i++) {
    try {
      const r = await fetch(`http://127.0.0.1:${PORT}/json/version`);
      if (r.ok) break;
    } catch {}
    await sleep(500);
  }
  return proc;
}

export async function attach() {
  // 找一个 page 类型的 target
  for (let i = 0; i < 40; i++) {
    const list = await (await fetch(`http://127.0.0.1:${PORT}/json/list`)).json();
    const page = list.find(t => t.type === "page" && t.webSocketDebuggerUrl);
    if (page) return new Session(page.webSocketDebuggerUrl);
    await sleep(500);
  }
  throw new Error("找不到可调试的页面 target");
}

class Session {
  constructor(url) {
    this.id = 0;
    this.pending = new Map();
    this.events = [];
    this.ws = new WebSocket(url);
    this.ready = new Promise((res, rej) => {
      this.ws.addEventListener("open", () => res());
      this.ws.addEventListener("error", e => rej(e));
    });
    this.ws.addEventListener("message", ev => {
      const msg = JSON.parse(ev.data);
      if (msg.id && this.pending.has(msg.id)) {
        const { resolve, reject } = this.pending.get(msg.id);
        this.pending.delete(msg.id);
        msg.error ? reject(new Error(JSON.stringify(msg.error))) : resolve(msg.result);
      } else if (msg.method) {
        this.events.push(msg);
      }
    });
  }

  async send(method, params = {}) {
    await this.ready;
    const id = ++this.id;
    this.ws.send(JSON.stringify({ id, method, params }));
    return new Promise((resolve, reject) => {
      this.pending.set(id, { resolve, reject });
      setTimeout(() => {
        if (this.pending.has(id)) {
          this.pending.delete(id);
          reject(new Error(`CDP 超时: ${method}`));
        }
      }, 60000);
    });
  }

  /** 在页面里跑一段 JS，返回它的值 */
  async eval(expr) {
    const r = await this.send("Runtime.evaluate", {
      expression: expr,
      returnByValue: true,
      awaitPromise: true
    });
    if (r.exceptionDetails) {
      throw new Error("页面脚本报错: " + JSON.stringify(r.exceptionDetails.exception?.description ?? r.exceptionDetails));
    }
    return r.result.value;
  }

  async goto(url, waitMs = 2500) {
    await this.send("Page.navigate", { url });
    await sleep(waitMs);
  }

  async shot(file, fullPage = false) {
    const params = { format: "png" };
    if (fullPage) params.captureBeyondViewport = true;
    const r = await this.send("Page.captureScreenshot", params);
    writeFileSync(file, Buffer.from(r.data, "base64"));
    return file;
  }

  close() {
    try { this.ws.close(); } catch {}
  }
}

export { sleep };
