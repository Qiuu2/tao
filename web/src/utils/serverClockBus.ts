/**
 * 「服务器时间被改了」这一声招呼。
 *
 * 顶栏那个时钟（ServerClock.vue）平时每 3 分钟才和服务器对一次时。
 * 「时间设置」页刚把系统时间拨过去时，顶栏还会顶着旧时间走最多 3 分钟 ——
 * 而运维刚改完时第一眼看的就是它，看见没变就以为没设置成功。
 *
 * 所以拨完表主动喊一声，让它立刻重对。
 *
 * 用 window 的 CustomEvent 而不是引一个状态库：
 * 这是一次性的、不带状态的通知，两边也不在同一棵组件树上
 * （一个在 layouts/Header，一个在 views/time），
 * 为它单开一个 store 只是把一行事情摊成一个文件。
 */
export const SERVER_CLOCK_CHANGED = "htweb:server-clock-changed";

/** 拨完表之后喊一声 */
export const notifyServerClockChanged = () => {
  window.dispatchEvent(new CustomEvent(SERVER_CLOCK_CHANGED));
};

/** 订阅，返回取消订阅的函数 */
export const onServerClockChanged = (fn: () => void) => {
  window.addEventListener(SERVER_CLOCK_CHANGED, fn);
  return () => window.removeEventListener(SERVER_CLOCK_CHANGED, fn);
};
