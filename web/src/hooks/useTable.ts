import { computed, reactive, toRefs } from "vue";

import { Table } from "./interface";

/**
 * @description table 页面操作方法封装
 * @param {Function} api 获取表格数据 api 方法 (必传)
 * @param {Object} initParam 获取数据初始化参数 (非必传，默认为{})
 * @param {Boolean} isPageable 是否有分页 (非必传，默认为true)
 * @param {Function} dataCallBack 对后台返回的数据进行处理的方法 (非必传)
 * */
export const useTable = (
  api?: (params: any) => Promise<any>,
  initParam: object = {},
  isPageable: boolean = true,
  dataCallBack?: (data: any) => any,
  requestError?: (error: any) => void
) => {
  const state = reactive<Table.StateProps>({
    // 表格数据
    tableData: [],
    // 正在请求
    loading: false,
    // 上一次请求失败了
    loadFailed: false,
    // 分页数据
    pageable: {
      // 当前页数
      pageNum: 1,
      // 每页显示条数
      pageSize: 10,
      // 总条数
      total: 0
    },
    // 查询参数(只包括查询)
    searchParam: {},
    // 初始化默认的查询参数
    searchInitParam: {},
    // 总参数(包含分页和查询参数)
    totalParam: {}
  });

  /**
   * @description 分页查询参数(只包括分页和表格字段排序,其他排序方式可自行配置)
   * */
  const pageParam = computed({
    get: () => {
      return {
        pageNum: state.pageable.pageNum,
        pageSize: state.pageable.pageSize
      };
    },
    set: (newVal: any) => {
      console.log("pagination updated:", newVal);
    }
  });

  /**
   * @description 获取表格数据
   * @return void
   * */
  /*
   * ⚠ loading 与 loadFailed 是后补的，补的是一个真实的误报：
   *
   * 终端管理的网格视图直接画 `v-if="data.length" ... v-else 暂无数据`。
   * 列表接口带的是 `{ loading: false }`（这一页要无感刷新，不能每次都盖一层
   * 全屏遮罩），于是**请求还在路上的那一段，界面上就是一张「暂无数据」**——
   * 服务器慢一点就看得见，现场报上来的原话是「全部终端有 19 个，
   * 但右边网格中显示暂无数据」。
   *
   * 更糟的是下面这个 catch：请求失败时 tableData 保持原样（初次进页面就是空），
   * 也没有任何标记，于是「请求挂了」和「真的一台都没有」长得一模一样，
   * 而且会一直挂着，直到下一次刷新碰巧成功。
   *
   * 所以：请求期间 loading = true，失败时 loadFailed = true 且**不动已有数据**
   * （宁可显示上一批旧数据，也别把人看着的东西清空）。
   */
  const getTableList = async () => {
    if (!api) return;
    state.loading = true;
    try {
      // 先把初始化参数和分页参数放到总参数里面
      Object.assign(state.totalParam, initParam, isPageable ? pageParam.value : {});
      let { data } = await api({ ...state.searchInitParam, ...state.totalParam });
      if (dataCallBack) {
        data = dataCallBack(data);
      }

      // 后端返回的形状不对时（list 缺失）当空列表处理，别让 undefined 流进表格 ——
      // 下游一个 .length 就是一片白屏
      state.tableData = (isPageable ? data?.list : data) ?? [];
      // 解构后台返回的分页数据 (如果有分页更新分页信息)
      if (isPageable) {
        state.pageable.total = data?.total ?? 0;
      }
      state.loadFailed = false;
    } catch (error) {
      state.loadFailed = true;
      if (requestError) {
        requestError(error);
      }
    } finally {
      state.loading = false;
    }
  };

  /**
   * @description 更新查询参数
   * @return void
   * */
  const updatedTotalParam = () => {
    state.totalParam = {};
    // 处理查询参数，可以给查询参数加自定义前缀操作
    let nowSearchParam: Table.StateProps["searchParam"] = {};
    // 防止手动清空输入框携带参数（这里可以自定义查询参数前缀）
    for (let key in state.searchParam) {
      // 某些情况下参数为 false/0 也应该携带参数
      if (state.searchParam[key] || state.searchParam[key] === false || state.searchParam[key] === 0) {
        nowSearchParam[key] = state.searchParam[key];
      }
    }
    Object.assign(state.totalParam, nowSearchParam);
  };

  /**
   * @description 表格数据查询
   * @return void
   * */
  const search = () => {
    state.pageable.pageNum = 1;
    updatedTotalParam();
    getTableList();
  };

  /**
   * @description 表格数据重置
   * @return void
   * */
  const reset = () => {
    state.pageable.pageNum = 1;
    // 重置搜索表单的时，如果有默认搜索参数，则重置默认的搜索参数
    state.searchParam = { ...state.searchInitParam };
    updatedTotalParam();
    getTableList();
  };

  /**
   * @description 每页条数改变
   * @param {Number} val 当前条数
   * @return void
   * */
  const handleSizeChange = (val: number) => {
    state.pageable.pageNum = 1;
    state.pageable.pageSize = val;
    getTableList();
  };

  /**
   * @description 当前页改变
   * @param {Number} val 当前页
   * @return void
   * */
  const handleCurrentChange = (val: number) => {
    state.pageable.pageNum = val;
    getTableList();
  };

  return {
    ...toRefs(state),
    getTableList,
    search,
    reset,
    handleSizeChange,
    handleCurrentChange,
    updatedTotalParam
  };
};
