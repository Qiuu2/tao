import http from "@/api";
import { PORT1 } from "@/api/config/servicePort";

export interface SpNetwork {
  ip: string;
  gateway: string;
  subnetmask: string;
}

export interface SpPorts {
  /** ⚠ 不是 Apache 端口，是后台 C 服务的 UDP 通知端口（现网 8886） */
  webport: number;
  port: number;
  hbport: number;
  udpport: number;
  rtspport: number;
  dataport: number;
  offlineport: number;
}

/**
 * 页面上的「web端口」与「sdk端口」不在数据库里，读自 Apache 的 httpd.conf
 * 与旧版 swagger1.json（现网 80 / 99）。读不到时字段为 0，界面回退到
 * ports.webport / ports.rtspport 并给出说明。
 */
export interface SpApache {
  webPort: number;
  sdkPort: number;
  listens: number[];
  source: string;
  err?: string;
}

export interface SpCapacity {
  maxhttpconnections: number;
  maxbandwidth: number;
}

export interface SpMulticast {
  ip: string;
  port: number;
}

export interface SpLicense {
  netradiocount: number;
  soundcardcount: number;
  ctrlterminalcount: number;
}

export interface SpHA {
  model: number;
  modelText: string;
  /** 服务器名称（serverbaseparam.name）。旧版在主备配置页里就是可改的。 */
  name: string;
  masterip: string;
  slaveip: string;
  slavename: string;
  backup: number;
}

export interface SpMisc {
  ntpserver: string;
  projectname: string;
  factory: string;
  dealerinfo: string;
  version: string;
  backupmode: number;
  ischeckmac: number;
  adjusttime: number;
  sounddetect: number;
  fuzamima: number;
}

export interface SpReadOnly {
  name: string;
  workstate: number;
  currectconnectcount: number;
  currentbandwidth: number;
  taskcount: number;
  registerflag: number;
  registerserial: string;
  trystartdate: string;
  tryenddate: string;
  netstate: number;
  terminalchange: number;
  taskchange: number;
  serverchange: number;
}

export interface ServerParams {
  network: SpNetwork;
  ports: SpPorts;
  apache: SpApache;
  capacity: SpCapacity;
  multicast: SpMulticast;
  license: SpLicense;
  ha: SpHA;
  misc: SpMisc;
  readonly: SpReadOnly;
}

export interface SpSaveResult {
  updated: boolean;
  requiresRestart: boolean;
  restartReason: string[];
}

export interface TableImpact {
  table: string;
  rows: number;
  deleteRows: number;
  note: string;
}

export interface FactoryPreview {
  clearTables: TableImpact[];
  keepTables: TableImpact[];
  untouchedTables: TableImpact[];
  /** 不在任何清单里的表；非空就拒绝执行 */
  unknownTables: string[];
  totalDeleteRows: number;
  mediaFiles: number;
  mediaBytes: number;
  executable: boolean;
  blocker: string;
}

export interface FactoryResult {
  clearedTables: number;
  deletedRows: number;
  deletedMediaFiles: number;
  failedMediaFiles: string[];
  preserved: Record<string, number>;
  requiresRestart: boolean;
  restartHint: string;
}

export const getServerParamsApi = () => http.get<ServerParams>(PORT1 + `/api/server/params`, {}, { loading: false });

export const saveServerParamsApi = (data: {
  network: SpNetwork;
  ports: SpPorts;
  capacity: SpCapacity;
  multicast: SpMulticast;
  ha: SpHA;
  misc: SpMisc;
}) => http.put<SpSaveResult>(PORT1 + `/api/server/params`, data);

/**
 * 定时重启（:80「服务设置」里的重启设置 / 重启时间）。
 *
 * ⚠ 它**不在 serverbaseparam 里** —— 那张表没有任何重启/关机相关的列。
 *   实际存在 task 表那条 `tasktype = 13`、`taskname = 'reset'` 的系统任务上
 *   （现网 taskid 70000，playtime 04:00:00），所以单独一个接口。
 */
/**
 * ⚠ 重启和关机落在**同一条** task 记录上，只有一个 playtime，靠 cmdargs 区分：
 *   cmdargs='0' 是重启、'shutdown' 是关机。所以两者**互斥**，用 mode 表示。
 */
export type RestartMode = "off" | "reboot" | "shutdown";

export interface AutoRestart {
  exists: boolean;
  taskId: number;
  mode: RestartMode;
  /** HH:MM:SS。⚠ 只属于当前 mode 那一边，另一边库里没有存储。 */
  time: string;
  /** 7 位星期掩码，第 0 位是周日 */
  exemodel: string;
}

export const getAutoRestartApi = () => http.get<AutoRestart>(PORT1 + `/api/server/auto-restart`, {}, { loading: false });

/**
 * exemodel 允许**不传**。
 *
 * :80 的服务设置页（docs/image/4.png）上没有星期选择框，只有「重启设置 + 重启时间」，
 * 新版界面照它做。不传时后端保留库里已有的掩码（现网 0001000 = 只在周四），
 * 只有掩码是全 0 且要开启时才补成 1111111。规则写在 serverparam/autorestart.go。
 */
export const saveAutoRestartApi = (data: { mode: RestartMode; time: string; exemodel?: string }) =>
  http.put<AutoRestart>(PORT1 + `/api/server/auto-restart`, data);

/**
 * 版本设置（:80「服务器信息 → 版本设置」页签）。
 *
 * ⚠ 这一页**不写数据库**。:80 的提交打到 Lumen 的 server/serverversion，
 *   做的是解压 sounds/audioserver 下的版本包 + 重建 a9000_audioserver 容器。
 *   `current` 是 serverbaseparam.version（后台服务上报的字符串），只读展示，
 *   跟下拉里那五个包名不是一回事，选了版本也不会去覆盖它。
 */
export interface VersionOption {
  id: number;
  name: string;
  /** 对应的 tar 包在机器上存在。为 false 时下拉里置灰。 */
  available: boolean;
}

export interface VersionState {
  /** 后台服务上报的版本号，只读 */
  current: string;
  options: VersionOption[];
  /** 这台机器换不换得动版本；false 时禁掉提交并显示 reason */
  canSwitch: boolean;
  reason: string;
}

export const getServerVersionApi = () => http.get<VersionState>(PORT1 + `/api/server/version`, {}, { loading: false });

/** ⚠ 会重建 audioserver 容器，期间广播中断。只有超管调得动。 */
export const switchServerVersionApi = (id: number) =>
  http.post<{ switched: boolean; note: string }>(PORT1 + `/api/server/version`, { id });

export const previewFactoryResetApi = () =>
  http.get<FactoryPreview>(PORT1 + `/api/server/factory-reset/preview`, {}, { loading: false });

export const factoryResetApi = (confirmText: string, purgeMediaFiles: boolean) =>
  http.post<FactoryResult>(PORT1 + `/api/server/factory-reset`, { confirmText, purgeMediaFiles });

/** ⚠ 这会重启**整台服务器**，不是重启后台服务 */
export const rebootServerApi = (confirmText: string) =>
  http.post<{ sent: boolean; note: string }>(PORT1 + `/api/server/reboot`, { confirmText });
