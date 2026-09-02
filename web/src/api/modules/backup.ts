import http from "@/api";
import { PORT1 } from "@/api/config/servicePort";

export interface BackupColumn {
  name: string;
  type: string;
  nullable: boolean;
  key: string;
  charset: string;
}

export interface BackupTable {
  name: string;
  columns: BackupColumn[];
  rows: number;
}

export interface BackupMediaEntry {
  path: string;
  size: number;
  sha256: string;
}

export interface BackupManifest {
  formatVersion: number;
  createdAt: string;
  createdBy: string;
  label: string;
  database: string;
  /** 表与列的结构指纹，恢复前用它和当前库比对 */
  schemaHash: string;
  tables: BackupTable[];
  totalRows: number;
  skippedMediaDirs: string[];
  media: BackupMediaEntry[];
  mediaBytes: number;
}

export interface BackupItem {
  name: string;
  size: number;
  sizeText: string;
  createdAt: string;
  readable: boolean;
  note: string;
  manifest?: BackupManifest;
  compatible: boolean;
}

export interface BackupCreateResult {
  name: string;
  size: number;
  sizeText: string;
  manifest: BackupManifest;
  elapsed: string;
  skippedMediaDirs: string[];
}

export interface SchemaDiff {
  table: string;
  column: string;
  issue: string;
  detail: string;
}

export interface BackupPrecheck {
  name: string;
  compatible: boolean;
  formatOk: boolean;
  schemaHashSame: boolean;
  schemaDiff: SchemaDiff[];
  manifest: BackupManifest;
  recommendation: string;
  willDeleteRows: number;
  willInsertRows: number;
  mediaFiles: number;
}

export interface BackupRestoreResult {
  name: string;
  tablesRestored: number;
  rowsDeleted: number;
  rowsInserted: number;
  mediaRestored: number;
  mediaFailed: string[];
  safetyBackup: string;
  elapsed: string;
  sessionsInvalidated: boolean;
  /** 后台 C 服务内存里仍是恢复前的数据，需要人工选时机重启 */
  backendNeedsRestart: boolean;
  restartHint: string;
}

export const getBackupListApi = () => http.get<{ list: BackupItem[] }>(PORT1 + `/api/backups`, {}, { loading: false });

export const createBackupApi = (label: string) => http.post<BackupCreateResult>(PORT1 + `/api/backups`, { label });

/**
 * 上传备份包。
 *
 * 用途：把下载下来的包、或者另一台机器上备的包传回来。落地之后它与
 * 「本机自己备的包」完全等价 —— 同一个列表、同一套还原前置检查、同一条恢复路径。
 *
 * ⚠ 服务端**不在上传阶段比对结构指纹**：指纹不一致的包照样收下，
 *   兼容与否由列表里的 `compatible` 显示，真正的拦截在还原前置检查。
 *   上传就拒收会让人连包里是什么都看不到。
 */
export interface BackupUploadResult {
  name: string;
  size: number;
  /** 目录里已有同名包，落地时自动加了时间戳后缀 */
  renamed: boolean;
  item?: BackupItem;
}

export const uploadBackupApi = (file: File, onProgress?: (percent: number) => void) => {
  const fd = new FormData();
  // 字段名必须是 file，服务端按它取
  fd.append("file", file, file.name);
  return http.post<BackupUploadResult>(PORT1 + `/api/backups/upload`, fd, {
    // 大包上传要久，别用默认超时把它掐断
    timeout: 30 * 60 * 1000,
    onUploadProgress: (e: any) => {
      if (!onProgress || !e.total) return;
      onProgress(Math.round((e.loaded * 100) / e.total));
    }
  } as any);
};

export const deleteBackupApi = (name: string) =>
  http.delete<{ name: string }>(PORT1 + `/api/backups`, {}, { data: { name, confirmed: true } });

export const precheckBackupApi = (name: string) =>
  http.get<BackupPrecheck>(PORT1 + `/api/backups/${encodeURIComponent(name)}/restore-precheck`);

export const restoreBackupApi = (data: {
  name: string;
  confirmText: string;
  safetyBackup: boolean;
  restoreMedia: boolean;
}) => http.post<BackupRestoreResult>(PORT1 + `/api/backups/restore`, data);

/** 下载走浏览器直连，带上 token 查询串 */
export const backupDownloadUrl = (name: string) => `${PORT1}/api/backups/${encodeURIComponent(name)}/download`;
