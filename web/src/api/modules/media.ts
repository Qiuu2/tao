import http from "@/api";
import { PORT1 } from "@/api/config/servicePort";
import { ResPage } from "@/api/interface/index";

/** 文件夹树节点 */
export interface FolderNode {
  id: number;
  name: string;
  parentId: number;
  userId: number;
  shared: boolean;
  createTime: string;
  system: boolean;
  depth: number;
  mediaCount: number;
  canCreateChild: boolean;
  canModify: boolean;
  canDelete: boolean;
  children: FolderNode[];
}

export interface FolderTree {
  rootName: string;
  tree: FolderNode[];
  /** parentid 指向不存在节点的游离目录。旧版会让它们彻底消失，新版必须展示 */
  orphans: FolderNode[];
}

/** 媒体列表行 */
export interface MediaItem {
  id: number;
  name: string;
  size: number;
  sizeText: string;
  typeid: string;
  bitrate: number;
  bitrateText: string;
  timelength: number;
  timelengthText: string;
  folderId: number;
  userId: number;
  streamUrl: string;
  downloadUrl: string;
}

/** 当前文件夹的汇总信息 */
export interface MediaFolderInfo {
  id: number;
  name: string;
  system: boolean;
  totalSizeKB: number;
  totalSizeText: string;
  canUpload: boolean;
  canCreateChild: boolean;
  /** 录音媒体库：只允许试听/下载/删除，禁止上传与改目录结构 */
  isRecordLibrary: boolean;
}

export type MediaListRes = ResPage<MediaItem> & {
  folder: MediaFolderInfo;
  /** 普通用户会带上「仅显示我上传的媒体」的说明 */
  scopeNote: string;
};

/**
 * @description 获取文件夹树
 * @param scene manage=媒体管理场景；taskPicker=任务选媒体场景（排除语音合成媒体库）
 */
export const getFolderTreeApi = (scene: "manage" | "taskPicker" = "manage") => {
  return http.get<FolderTree>(PORT1 + `/api/folders/tree`, { scene, includeMediaCount: true }, { loading: false });
};

/**
 * @description 获取指定文件夹下的媒体列表
 * 注意分页参数是 pageNum（不是 page），与 ProTable/useTable 约定一致
 */
export const getMediaListApi = (params: any) => {
  return http.get<MediaListRes>(PORT1 + `/api/media`, params, { loading: false });
};

/* ---------------- 文件夹写操作 ---------------- */

/**
 * @description 新建文件夹
 * shared 必须显式传：旧版把共享开关注释掉了，导致新建恒共享、
 * 改名还会把私有目录静默改成共享。服务端会拒绝不带该字段的请求。
 */
export const createFolderApi = (data: { name: string; parentId: number; shared: boolean }) => {
  return http.post(PORT1 + `/api/folders`, data);
};

/** @description 修改文件夹名称与共享属性。shared 同样必须显式传 */
export const updateFolderApi = (id: number, data: { name: string; shared: boolean }) => {
  return http.put(PORT1 + `/api/folders/${id}`, data);
};

/** 删除影响面预览：告诉用户会连带删掉多少子目录与媒体，以及哪些被引用而删不掉 */
export interface FolderDeletePreview {
  deletable: { id: number; name: string; descendantFolders: number; mediaCount: number }[];
  blocked: { id: number; name: string; reason: string; detail?: string }[];
}

export const previewDeleteFolderApi = (ids: number[]) => {
  return http.get<FolderDeletePreview>(PORT1 + `/api/folders/delete-preview`, { ids: ids.join(",") });
};

/** 删除文件夹的返回体 */
export interface FolderDeleteRes {
  deletedFolders: number[];
  deletedMediaCount: number;
  blocked: { id: number; name: string; reason: string }[];
  notified: boolean;
}

// 注意：封装的 delete 会把第二个参数放进 query string，
// 而服务端要的是 JSON body，所以要通过第三个配置参数传 data
export const deleteFolderApi = (ids: number[]) => {
  return http.delete<FolderDeleteRes>(PORT1 + `/api/folders`, {}, { data: { ids, confirmed: true } });
};

/* ---------------- 媒体写操作 ---------------- */

export interface MediaDeletePreview {
  deletable: { id: number; name: string }[];
  blocked: { id: number; name: string; reason: string; refName?: string }[];
}

export const previewDeleteMediaApi = (ids: number[]) => {
  return http.get<MediaDeletePreview>(PORT1 + `/api/media/delete-preview`, { ids: ids.join(",") });
};

/** 删除媒体的返回体，清空目录复用同一结构 */
export interface MediaDeleteRes {
  deleted: number[];
  deletedCount: number;
  blocked: { id: number; name: string; reason: string; refName?: string }[];
  notified: boolean;
}

export const deleteMediaApi = (ids: number[]) => {
  return http.delete<MediaDeleteRes>(PORT1 + `/api/media`, {}, { data: { ids, confirmed: true } });
};

/**
 * @description 清空文件夹内的全部媒体（高危）
 *
 * 旧版「删除」按钮在未勾选时就是这个行为，且不做任何校验，误点即清空整个目录。
 * 新版拆成独立接口，并要求输入与文件夹名完全一致的确认文本。
 */
export const clearFolderMediaApi = (folderId: number, confirmFolderName: string) => {
  return http.post<MediaDeleteRes>(PORT1 + `/api/folders/${folderId}/media:clear`, { confirmFolderName });
};

/** 被阻断原因的中文说明 */
export const blockReasonText = (reason: string): string => {
  const map: Record<string, string> = {
    IN_USE_TASK: "正被任务使用",
    IN_USE_SHORTCUT: "正被终端快捷键使用",
    IN_USE_ALARM: "正被报警映射使用",
    IN_USE_BELL: "正被打铃条目使用",
    SYSTEM_FOLDER_NO_PERMISSION: "位于系统预置库，仅超级管理员可删",
    SYSTEM_RESERVED: "系统预置媒体库，不可删除",
    NO_PERMISSION: "无权操作",
    MEDIA_IN_USE: "目录内有媒体正被使用",
    NOT_FOUND: "对象不存在"
  };
  return map[reason] || reason;
};
