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

/**
 * 保存时「网卡那一步」的结果。
 *
 * 只有**主/备服务器地址**（masterip / slaveip，按 model 选）或者掩码、网关
 * 真的变了才会有这个对象；没变时后端不返回它。见 serverparam/netaddr.go。
 *
 * ⚠ 「服务器地址」那一栏（network.ip）是 heartbeat 的虚拟地址，
 *   改它**不会**动网卡 —— 只写 haresources / ha-post.sh / graylog / swagger
 *   那几个配置文件，要 heartbeat 重新接管资源才生效。
 */
export interface SpNetworkApply {
  /** 真的去改网卡了。为 true 时当前这条连接马上会断 —— 界面要拦住人并给出新地址 */
  attempted: boolean;
  /** 非空表示地址变了但没能改网卡，值就是原因（缺 nmcli、缺 sudo、掩码非法…） */
  blocked: string;
  /** 改的是哪个 NetworkManager 连接、哪块网卡，给人核对用 */
  connection: string;
  device: string;
  /** 设上去的地址，形如 192.168.1.50/24（来自主/备服务器地址，不是「服务器地址」那一栏） */
  address: string;
  gateway: string;
  /** 换完地址之后这一页的新入口 */
  newUrl: string;
}

/**
 * 旧系统里那几个记着同一个地址的配置文件的同步结果
 * （haresources / graylog.conf / ha-post.sh / swagger1.json）。
 * 只在 IP / 掩码 / 网关变了时才有。见 serverparam/syncfiles.go。
 */
export interface SpFileSync {
  path: string;
  /** 这个文件里改的是什么，给人看的一句话 */
  what: string;
  /**
   * updated   改好了
   * unchanged 本来就对，没动
   * missing   文件不存在（这台机器多半没装旧系统，不是故障）
   * no-anchor 文件在，但没找到该改的那一行 —— 不按行号猜，原样保留
   * failed    读写失败，多半是权限
   */
  status: "updated" | "unchanged" | "missing" | "no-anchor" | "failed";
  detail?: string;
}

/**
 * 吃这些配置的两个服务的重启结果。
 * 只在地址（虚拟或主/备）、掩码、网关或主备角色变了时才有。
 * 见 serverparam/svcrestart.go。
 *
 * · heartbeat          重读 /etc/ha.d/ha.cf 与 haresources
 * · a9000_audioserver  重读 serverbaseparam（它是 docker 容器，不是 systemd 单元）
 */
export interface SpServiceRestart {
  /** 服务名，给人核对用 */
  name: string;
  /** 为什么要重启它 */
  what: string;
  /** updated = 重启了；missing = 这台机器上没有它；failed = 没能重启（多半缺免密 sudo） */
  status: "updated" | "unchanged" | "missing" | "no-anchor" | "failed";
  detail?: string;
}

export interface SpSaveResult {
  updated: boolean;
  requiresRestart: boolean;
  restartReason: string[];
  network?: SpNetworkApply;
  files?: SpFileSync[];
  services?: SpServiceRestart[];
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

/**
 * 重启**整台服务器**。
 *
 * ⚠ 不是重启后台服务：实测指令发出后 1 秒系统就开始走关机流程。
 *   早前要求逐字输入「重启服务器」才放行，已按要求去掉 —— 界面上点确定即重启。
 *   接口仍挂在超管路由上，后端在发包之前写审计。
 */
export const rebootServerApi = () => http.post<{ sent: boolean; note: string }>(PORT1 + `/api/server/reboot`, {});
