// 请求响应参数（不包含data）
export interface Result {
  code: string;
  msg: string;
}

// 请求响应参数（包含data）
export interface ResultData<T = any> extends Result {
  data: T;
}

// 分页响应参数
export interface ResPage<T> {
  list: T[];
  pageNum: number;
  pageSize: number;
  total: number;
}

// 分页请求参数
export interface ReqPage {
  pageNum: number;
  pageSize: number;
}

// 文件上传模块
export namespace Upload {
  export interface ResFileUrl {
    fileUrl: string;
  }
}

// 登录模块
export namespace Login {
  export interface ReqLoginForm {
    username: string;
    password: string;
    /** 图形验证码，服务端 auth.captcha_enabled 为 true 时必填 */
    captcha?: string;
    /** 验证码会话标识，与图片一同下发 */
    captchaId?: string;
  }
  export interface ResCaptcha {
    /**
     * 服务端是否要求验证码，对应 config.yaml 的 auth.captcha_enabled。
     * 为 false 时 captchaId / image 不下发，前端应隐藏输入框并撤掉必填校验。
     */
    enabled: boolean;
    captchaId?: string;
    /** PNG 的 data URI，可直接作为 img src */
    image?: string;
  }
  /** 用户组的 13 项功能权限位，取值 0/1 */
  export interface Rights {
    taskpriv: number;
    terminalpriv: number;
    mediapriv: number;
    userpriv: number;
    serverpriv: number;
    folderpriv: number;
    terminalgrouppriv: number;
    alarmgrouppriv: number;
    bellpriv: number;
    admpriv: number;
    telephonepriv: number;
    powerplay: number;
    ttspriv: number;
  }
  export interface UserInfo {
    id: number;
    username: string;
    fullname: string;
    info: string;
    usergroupId: number;
    usergroupName: string;
    /** usergroup.level，10~109 的两位复合编码：十位=组级别，个位=任务优先级基数 */
    level: number;
    isAdmin: boolean;
    rights: Rights;
    /** serverbaseparam.model==2 时为 true，此时全站只读 */
    readonly: boolean;
  }
  export interface ResLogin {
    access_token: string;
    user: UserInfo;
    server: { readonly: boolean };
  }
  export interface ResAuthButtons {
    [key: string]: string[];
  }
}

// 用户管理模块
export namespace User {
  export interface ReqUserParams extends ReqPage {
    username: string;
    gender: number;
    idCard: string;
    email: string;
    address: string;
    createTime: string[];
    status: number;
  }
  export interface ResUserList {
    id: string;
    username: string;
    gender: number;
    user: { detail: { age: number } };
    idCard: string;
    email: string;
    address: string;
    createTime: string;
    status: number;
    avatar: string;
    photo: any[];
    children?: ResUserList[];
  }
  export interface ResStatus {
    userLabel: string;
    userValue: number;
  }
  export interface ResGender {
    genderLabel: string;
    genderValue: number;
  }
  export interface ResDepartment {
    id: string;
    name: string;
    children?: ResDepartment[];
  }
  export interface ResRole {
    id: string;
    name: string;
    children?: ResDepartment[];
  }
}
