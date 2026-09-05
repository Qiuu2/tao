/**
 * 表格列设置的本地存放。
 *
 * # 为什么要有这个文件
 *
 * 「列设置」抽屉里改的是 ProTable 内存里那份 columns，刷新一次就回默认了 ——
 * 用户每次登录都要把不想看的列重新关一遍。这里把每张表的列设置存进 localStorage，
 * 下次进同一页直接套上。
 *
 * # 存什么、存在哪
 *
 *   key    htweb:cols:<表标识>       表标识默认取路由 path，同一页只有一张表时够用；
 *                                    一页多表时由调用方传 tableKey 区分
 *   value  { "<列的 prop>": { show, sortable } }
 *
 * 只存**用户改过的两个开关**，不存列的定义。这样后端加列 / 改列名都不会被旧记录带偏：
 * 记录里没有的列按代码里的默认值走，代码里已经删掉的列在套用时直接忽略。
 *
 * # 与「布局设置」的关系
 *
 * 布局、主题、侧边栏那些走 pinia 的 geeker-global，本来就落 localStorage、
 * 退出登录也不清（见 stores/modules/global.ts）。列设置是另一套，
 * 因为它属于某一张表而不是全局，键也要按表分开。
 *
 * localStorage 在隐私模式 / 禁用站点数据时会直接抛异常，所以读写全部包了 try。
 * 存不下就是回到「每次默认」，不该让整张表打不开。
 */

/** 一列被记住的两个开关，与「列设置」抽屉里的两个 el-switch 一一对应 */
export interface ColPref {
  /** 显示 */
  show?: boolean;
  /** 排序 */
  sortable?: boolean;
}

export type ColPrefMap = Record<string, ColPref>;

const PREFIX = "htweb:cols:";

const keyOf = (tableKey: string) => PREFIX + tableKey;

/** 读一张表的列设置；读不到（没存过 / 存储不可用 / 内容坏了）一律当没设置过 */
export const loadColPrefs = (tableKey: string): ColPrefMap => {
  try {
    const raw = localStorage.getItem(keyOf(tableKey));
    if (!raw) return {};
    const obj = JSON.parse(raw);
    return obj && typeof obj === "object" && !Array.isArray(obj) ? (obj as ColPrefMap) : {};
  } catch {
    return {};
  }
};

/** 写一张表的列设置。空对象等于清掉这条记录，免得留一堆空壳 */
export const saveColPrefs = (tableKey: string, prefs: ColPrefMap) => {
  try {
    if (!Object.keys(prefs).length) localStorage.removeItem(keyOf(tableKey));
    else localStorage.setItem(keyOf(tableKey), JSON.stringify(prefs));
  } catch {
    // 存储不可用就算了，这一页照常能用，只是下次不记得
  }
};

/** 清掉一张表的列设置，「恢复默认列」用 */
export const clearColPrefs = (tableKey: string) => saveColPrefs(tableKey, {});
