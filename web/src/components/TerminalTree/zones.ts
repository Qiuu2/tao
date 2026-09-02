import { getZoneOptionsApi } from "@/api/modules/basecfg";

/**
 * 终端树的分区清单。
 *
 * # 为什么放在这里而不是各页面自己取
 *
 * 树的骨架必须是**分区表**（照 ok112 的 get_terminallist5），
 * 这样一台终端都没有的分区也会作为空节点出现。全站有 14 处终端选择器，
 * 让每一处都去 `/api/zones/options` 拉一遍既啰嗦又容易漏；
 * 收在组件里、加一层进程内缓存，14 处共用同一次请求。
 *
 * # 缓存策略
 *
 * 缓存的是 Promise 而不是结果：同一时刻多个树一起挂载时，
 * 它们会共享同一个在途请求，而不是各发一个。
 *
 * 分区增删改之后要让缓存失效 —— 终端分区页保存完会调 invalidateZones()。
 */
export interface ZoneNode {
  id: number;
  name: string;
}

let cache: Promise<ZoneNode[]> | null = null;

export const loadZones = (): Promise<ZoneNode[]> => {
  if (!cache) {
    cache = getZoneOptionsApi()
      .then(r => (r.data ?? []).map(z => ({ id: Number(z.id), name: String(z.name ?? "") })))
      .catch(() => {
        // 取不到分区就退化成「全部归到无分区终端」，而不是让整棵树空掉。
        // ⚠ 同时把缓存清掉，下次挂载时重试 —— 否则一次网络抖动会让
        //   这一整个会话里的树永远没有分区。
        cache = null;
        return [] as ZoneNode[];
      });
  }
  return cache;
};

/** 分区被增删改之后调它，下一棵树会重新拉。 */
export const invalidateZones = () => {
  cache = null;
};
