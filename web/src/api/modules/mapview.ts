/**
 * 资源管理 → 地图。
 *
 * 底图是一张图片，终端按**图上的百分比**摆放（不是经纬度，也不是像素）。
 * 为什么是百分比、为什么另建两张表，见 server/internal/mapview 的包注释。
 */
import http from "@/api";
import { PORT1 } from "@/api/config/servicePort";

export interface MapImage {
  id: number;
  name: string;
  /** 原始像素宽高，仅用于按原比例显示；没传过图时是 0 */
  width: number;
  height: number;
  sort: number;
  /** false 表示还没上传真实底图，界面画占位底图 */
  hasImage: boolean;
  /** 取图地址，hasImage 为 false 时没有这个字段 */
  imageUrl?: string;
  /** 这张图上已经摆了几台终端 */
  terminals: number;
  createTime?: string;
}

export interface MapPlacement {
  terminalId: number;
  /** 距左边 / 顶部的百分比，0~100 */
  x: number;
  y: number;
  terminalname: string;
  ip: string;
  typeName: string;
  volume: number;
  netstate: number;
  devicestate: number;
  taskstate: number;
  groupName: string;
  /** 这条摆放记录指向的终端已经被删了 —— 图上留个灰点，不悄悄藏起来 */
  missing: boolean;
}

export const getMapsApi = () => http.get<MapImage[]>(PORT1 + `/api/maps`, {}, { loading: false });
export const createMapApi = (name: string) => http.post<{ id: number }>(PORT1 + `/api/maps`, { name });
export const renameMapApi = (id: number, name: string) => http.put<{ updated: boolean }>(PORT1 + `/api/maps/${id}`, { name });
export const deleteMapApi = (id: number) => http.delete<{ deleted: boolean }>(PORT1 + `/api/maps/${id}`);

export const getMapTerminalsApi = (id: number) =>
  http.get<MapPlacement[]>(PORT1 + `/api/maps/${id}/terminals`, {}, { loading: false });

/**
 * 摆一台终端上去，或挪动已经在图上的那台 —— 同一个接口。
 * 表上有 (mapid, terminalid) 唯一键，走的是 upsert，界面不用区分新增和移动。
 */
export const placeMapTerminalApi = (id: number, terminalId: number, x: number, y: number) =>
  http.post<{ placed: boolean }>(PORT1 + `/api/maps/${id}/terminals`, { terminalId, x, y }, { loading: false });

export const removeMapTerminalsApi = (id: number, terminalIds: number[]) =>
  http.delete<{ removed: number }>(PORT1 + `/api/maps/${id}/terminals`, {}, { data: { terminalIds } });
