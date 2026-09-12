<!--
  顶栏的服务器时钟。

  # 显示的是**服务器**的时间，不是这台浏览器的

  广播系统里所有任务都按服务器的钟走。运维的笔记本时间偏了两分钟，
  看着「10:00 的早读没响」，其实是服务器上才 9:58 —— 这个钟就是为了
  当场排除掉这种误会，所以它一秒都不能取自 `new Date()` 直接显示。

  # 走秒靠本地，对时靠服务器

  每 3 分钟拉一次 `GET /api/time/now`，记下两个数：
  `skew = 服务器毫秒 - 本地毫秒`（两台机器的钟差多少）和服务器时区的分钟偏移；
  中间每秒重算一次显示值。这样：

    · 3 分钟里只有一次请求，不是每秒一次；
    · 浏览器标签页被挂到后台、`setInterval` 被节流，也只是**刷得慢**，
      再次可见时显示的仍是对的时间 —— 因为值是算出来的，不是累加出来的；
    · 浏览器自己的钟不准不影响显示，只有「浏览器钟在这 3 分钟里被人拨过」
      才会飘，而下一次同步就纠回来了。

  # 同步失败怎么办

  接着用上一次的 offset 走，但把「上次对时」的时间摆进悬浮提示，
  超过 7 分钟（错过两轮）就把颜色改掉 —— 停在一个不知道准不准的时间上
  却装作没事，比显示不出来更糟。

  首次同步成功之前**什么都不显示**：那时候唯一能拿到的是浏览器的时间，
  把它当服务器时间摆出来，正好制造了这个组件要消除的那种误会。

  # ⚠ 为什么排版用的是 getUTC*，不是 getHours()

  `new Date(服务器毫秒)` 只定死了「哪一个瞬间」，用 `getHours()` 取出来的是
  **浏览器所在时区**的那个小时。服务器在东八区、运维的笔记本在 UTC 的话，
  服务器墙上写着 15:00，这里会显示 07:00 —— 同一个瞬间，但不是服务器的钟面，
  而钟面才是任务时间表对齐的东西。

  所以把服务器的时区偏移一起加进去，再用 `getUTC*` 取值：
  等于「把时间轴挪到服务器那边再读数」，浏览器在哪个时区都不影响结果。
-->
<template>
  <span v-if="text" class="server-clock" :class="{ stale }" :title="tip">
    <el-icon class="clock-icon"><Clock /></el-icon>
    {{ text }}
  </span>
</template>

<script setup lang="ts">
import { useI18n } from "vue-i18n";
import { computed, onMounted, onUnmounted, ref } from "vue";
import { Clock } from "@element-plus/icons-vue";
import { getServerNowApi } from "@/api/modules/basecfg";
import { onServerClockChanged } from "@/utils/serverClockBus";

const { t } = useI18n();

/** 每 3 分钟和服务器对一次时 */
const SYNC_MS = 3 * 60 * 1000;
/** 超过这么久没对上时，把显示改成警示色（错过两轮） */
const STALE_MS = 7 * 60 * 1000;

/** 服务器毫秒 - 本地毫秒（两台机器钟面的差）。null = 还没对上过时，此时不显示任何东西 */
const skew = ref<number | null>(null);
/** 服务器时区相对 UTC 的分钟数。东八区 = 480 */
const zoneOffsetMin = ref(0);
const zone = ref("");
/** 上一次对时成功的**本地**时刻 */
const syncedAt = ref(0);
const nowMs = ref(Date.now());

const pad = (n: number) => String(n).padStart(2, "0");

/** 把一个**本地**毫秒时刻排成服务器钟面上的样子。取值用 getUTC*，理由见文件头 */
const fmtServer = (localMs: number) => {
  const d = new Date(localMs + (skew.value ?? 0) + zoneOffsetMin.value * 60000);
  return (
    `${d.getUTCFullYear()}-${pad(d.getUTCMonth() + 1)}-${pad(d.getUTCDate())} ` +
    `${pad(d.getUTCHours())}:${pad(d.getUTCMinutes())}:${pad(d.getUTCSeconds())}`
  );
};

const text = computed(() => (skew.value === null ? "" : fmtServer(nowMs.value)));
const stale = computed(() => skew.value !== null && nowMs.value - syncedAt.value > STALE_MS);

const tip = computed(() => {
  const head = zone.value ? t("header.serverTimeTipZone", { zone: zone.value }) : t("header.serverTimeTip");
  if (!syncedAt.value) return head;
  return `${head}\n${t("header.lastSync", { time: fmtServer(syncedAt.value) })}`;
});

/*
  ⚠ 这里刻意**不显示服务器和浏览器差了多少秒**：那是「时间设置」那一页的事
  （它连数据库的钟一起比），顶栏塞进去只会让人以为哪边出了故障。
*/
const sync = async () => {
  try {
    const { data } = await getServerNowApi();
    // ⚠ 先确认真的拿到了一个数。拿不到就当这次同步没发生 ——
    // 少了这一条，epochMs 是 undefined 时 offset 会变成 NaN，
    // 顶栏就挂上一串「NaN-NaN-NaN NaN:NaN:NaN」，而且它还每秒刷新一次。
    if (!Number.isFinite(data?.epochMs)) return;
    // 用发回来的毫秒时间戳，不用 serverTime 那个字面值 ——
    // 字面值要按服务器时区解析才对得上，而浏览器的 Date 只会按自己的时区解析。
    skew.value = data.epochMs - Date.now();
    zoneOffsetMin.value = Number.isFinite(data?.offsetMinutes) ? data.offsetMinutes : 0;
    zone.value = data.timezone ?? "";
    syncedAt.value = Date.now();
  } catch {
    // 拉不到就接着用上一次的 skew。首次就失败的话 skew 仍是 null，
    // 组件整个不显示 —— 不拿浏览器时间冒充服务器时间。
  }
};

let tick: number | undefined;
let syncTimer: number | undefined;
let offClockChanged: (() => void) | undefined;

onMounted(() => {
  sync();
  tick = window.setInterval(() => (nowMs.value = Date.now()), 1000);
  syncTimer = window.setInterval(sync, SYNC_MS);

  /*
    「时间设置」页刚把服务器时间拨过去时，立刻重对一次。

    ⚠ 少了这一条，顶栏会顶着旧时间接着走，最多 3 分钟才自己纠回来 ——
      而运维改完时第一眼看的就是这个钟，看见没变就以为没设置成功。
  */
  offClockChanged = onServerClockChanged(sync);
});

onUnmounted(() => {
  if (tick) window.clearInterval(tick);
  if (syncTimer) window.clearInterval(syncTimer);
  offClockChanged?.();
});
</script>

<style scoped lang="scss">
.server-clock {
  display: inline-flex;
  gap: 5px;
  align-items: center;
  margin-right: 4px;
  font-size: 14px;
  // 等宽数字：不加这一条，秒数从 1 跳到 2 时整串字会左右抖
  font-variant-numeric: tabular-nums;
  color: var(--el-header-text-color);
  white-space: nowrap;
  &.stale {
    color: var(--el-color-warning);
  }
  .clock-icon {
    font-size: 15px;
  }
}
</style>
