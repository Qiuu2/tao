export namespace Table {
  export interface Pageable {
    pageNum: number;
    pageSize: number;
    total: number;
  }
  export interface StateProps {
    tableData: any[];
    /** 正在请求。界面据此显示「加载中」而不是「暂无数据」 */
    loading: boolean;
    /** 上一次请求失败了。与「真的没有数据」必须分得开，见 useTable.getTableList */
    loadFailed: boolean;
    pageable: Pageable;
    searchParam: {
      [key: string]: any;
    };
    searchInitParam: {
      [key: string]: any;
    };
    totalParam: {
      [key: string]: any;
    };
    icon?: {
      [key: string]: any;
    };
  }
}

export namespace HandleData {
  export type MessageType = "" | "success" | "warning" | "info" | "error";
}

export namespace Theme {
  export type ThemeType = "light" | "inverted" | "dark";
  export type GreyOrWeakType = "grey" | "weak";
}
