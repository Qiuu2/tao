import http from "@/api";
import { PORT1 } from "@/api/config/servicePort";

/* ==================================================================
   注册服务（旧版 regist_server.php + do.php 的 regist_server() / settrydo()）
   ================================================================== */

/** serverbaseparam.registerflag 的取值，与旧版 regist_server.html 里那串 if/else 一致 */
export const REGISTER_FLAG = {
  /** 服务器没有注册 */
  NOT_REGISTERED: 0,
  /** 服务器已注册 */
  REGISTERED: 1,
  /** 服务器在试用期 */
  TRIAL: 2,
  /** 服务器是标准版软件 */
  STANDARD: 3,
  /** 服务器已过期 */
  EXPIRED: 4
} as const;

export interface RegisterStatus {
  registerflag: number;
  /** 页面上那行大字，逐字取自旧版 language/chinese.php 的 regist_server.* */
  statusText: string;
  /** 只有 flag=1 时为真，页面据此把状态文字显示成蓝色而不是红色 */
  registered: boolean;
  /** 机器码。⚠ 公开的 /status 接口恒为空串，登录后的 /api/register 才有 */
  machineCode: string;
  /** 旧版的 $getDays：5 -（今天 - serial 里的起算日），上限 5，可以是负数 */
  trialDaysLeft: number;
  /** flag=2 且没过期时那行红字 */
  trialNotice: string;
  /** 试用标记文件已存在，再点「试用」会被拒 */
  trialUsed: boolean;
  /** 「试用」点下去会真的去后台领；旧版在 1/2/3/4 这几种状态下只弹提示 */
  canTrial: boolean;
  /** serial 文件读不到，剩余天数无从算起 */
  serialFileMissing: boolean;
  /**
   * 这个状态下**登不进系统**（后端 auth.Login：registerflag 不是 1 或 2 就拒绝登录）。
   * 登录页据此决定要不要直接跳注册页；注册页据此知道自己是不是「登录前」的那一版。
   */
  loginBlocked: boolean;
}

export interface RegisterResult {
  /** 注册程序标准输出的第一行：success / failed / expired */
  outcome: string;
  ok: boolean;
  message: string;
}

/** 公开接口：登录页用它判断服务器有没有注册，不返回机器码 */
export const getRegisterStatusApi = () => http.get<RegisterStatus>(PORT1 + `/api/register/status`, {}, { loading: false });

/** 注册服务页的数据，比公开接口多一个机器码 */
export const getRegisterApi = () => http.get<RegisterStatus>(PORT1 + `/api/register`, {}, { loading: false });

export const submitRegisterApi = (licenseKey: string) => http.post<RegisterResult>(PORT1 + `/api/register`, { licenseKey });

export const startTrialApi = () => http.post<RegisterStatus>(PORT1 + `/api/register/trial`, {});
