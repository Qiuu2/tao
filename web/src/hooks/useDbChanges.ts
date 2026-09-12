import { onMounted, onUnmounted } from "vue";

import { ResultEnum } from "@/enums/httpEnum";
import { useUserStore } from "@/stores/modules/user";

/**
 * 「数据库被别人改了就自己刷新」——挂在页面上的那一半。
 *
 * # 为什么需要它
 *
 * 这个 audioserver 库**不止新版 Web 一个程序在写**：后台 C 服务
 * （a9000_audioserver）写终端上下线和任务状态，旧版 ok112 还在跑，现场还有各种
 * 脚本。所以「谁改的谁发通知」这条路走不通 —— 只能从库里看出来。
 *
 * 服务端那一半在 `server/internal/dbwatch`：一个进程一个轮询协程，
 * 对 terminal / task 两张表算指纹，算出来的 rev 通过长轮询发给浏览器。
 *
 * # 用法
 *
 * ```ts
 * useDbChanges(["terminal"], () => refresh());
 * ```
 *
 * `onChange` 里就调页面原来那个刷新函数。这里**不回任何数据** ——
 * 每个页面的筛选、分页、列都不一样，让它自己去查才可靠。
 *
 * # ⚠ 为什么用裸 fetch，不走 @/api 那个 axios
 *
 * 那个实例上挂了三样东西，每一样都会咬这种长连接：
 *
 *   1. `timeout: 30000` —— 长轮询挂 25 秒，擦着边过，网络稍慢就报超时
 *   2. `axiosCanceler` —— 会把「重复请求」取消掉，而这里每一轮都是同一个 URL
 *   3. 响应拦截器里那句 `ElMessage.error` —— 网络抖一下、或者切走标签页导致
 *      请求被中止，用户就会看到一串莫名其妙的红条
 *
 * 三样都得关掉才能用，那还不如直接 fetch。这里的错误一律**静默重试**：
 * 它是个后台的东西，坏了顶多是「不自动刷新了」，不该打扰正在干活的人。
 */
export type DbTopic = "terminal" | "task";

/** 一轮长轮询最多挂多久（秒）。与服务端的上限对齐 */
const WAIT_SECONDS = 25;
/** 出错之后隔这么久再试。别把一台正在重启的服务器打死 */
const RETRY_MS = 5000;

interface ChangesResp {
  code: number;
  data?: { revs: Record<string, number>; changed: DbTopic[] };
}

export function useDbChanges(topics: DbTopic[], onChange: (changed: DbTopic[]) => void) {
  const userStore = useUserStore();

  /** 手里这份版本号。第一次是空的，服务端会立刻回一份让我们对齐 */
  let revs: Record<string, number> = {};
  let ctrl: AbortController | null = null;
  let running = false;
  let stopped = false;
  let retryTimer: number | undefined;

  const buildUrl = () => {
    const p = new URLSearchParams();
    // 只报上自己关心的主题。别的表变了不该把这个页面叫醒 ——
    // 叫醒就意味着一次没必要的列表查询。
    for (const t of topics) {
      if (revs[t] !== undefined) p.set(t, String(revs[t]));
    }
    if ([...p.keys()].length) p.set("wait", String(WAIT_SECONDS));
    return `/api/changes${p.toString() ? "?" + p.toString() : ""}`;
  };

  const loop = async () => {
    if (running || stopped) return;
    running = true;
    try {
      while (!stopped && !document.hidden && userStore.token) {
        ctrl = new AbortController();
        let body: ChangesResp;
        try {
          const resp = await fetch(buildUrl(), {
            headers: { "x-access-token": userStore.token },
            signal: ctrl.signal
          });
          // 会话过期就安静收手。真正的跳登录页交给用户下一次操作时
          // axios 那边的拦截器去做 —— 一个后台轮询不该抢着改路由。
          if (resp.status === ResultEnum.OVERDUE) return;
          if (!resp.ok) throw new Error(String(resp.status));
          body = await resp.json();
        } catch {
          // 中止（切走标签页、离开页面）不是错误，直接退出
          if (stopped || ctrl.signal.aborted) return;
          await sleep(RETRY_MS);
          continue;
        }
        if (body.code === ResultEnum.OVERDUE) return;
        if (body.code !== ResultEnum.SUCCESS || !body.data) {
          await sleep(RETRY_MS);
          continue;
        }

        const first = Object.keys(revs).length === 0;
        revs = { ...revs, ...body.data.revs };
        // 第一轮只是对齐，页面刚加载过，不要再刷一次
        if (!first && body.data.changed?.length) onChange(body.data.changed);
      }
    } finally {
      running = false;
      ctrl = null;
    }
  };

  const sleep = (ms: number) =>
    new Promise<void>(res => {
      retryTimer = window.setTimeout(res, ms);
    });

  /*
    标签页切到后台就停，切回来再接着跑。

    ⚠ 停的时候**不清空 revs** —— 这样切回来第一次问的时候，服务端一比对就
      发现不一样，立刻回 changed，页面当场刷新成最新的。
      清空了的话那一次会被当成「对齐」而跳过，人就得多等一轮。
  */
  const onVisible = () => {
    if (document.hidden) {
      ctrl?.abort();
    } else {
      loop();
    }
  };

  onMounted(() => {
    document.addEventListener("visibilitychange", onVisible);
    loop();
  });

  onUnmounted(() => {
    stopped = true;
    document.removeEventListener("visibilitychange", onVisible);
    if (retryTimer) window.clearTimeout(retryTimer);
    ctrl?.abort();
  });
}
