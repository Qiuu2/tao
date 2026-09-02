<!--
  终端管理（F-25 ~ F-31）

  左侧分区树 + 右侧终端列表。相对旧版的关键修复：
    · 旧版把分页整段注释掉，一次性渲染全部终端（上限 3000 行 × 20 列）
      —— 这里恢复服务端分页与服务端排序。
    · 旧版搜索分支漏掉了用户可见范围过滤，普通用户一搜索就能看到
      全系统所有终端；新版可见范围在服务端强制生效。
    · 批量操作改为「部分成功」语义：离线终端跳过并列出原因，
      不再像旧版那样一台离线就整批中止。
    · 删除前展示影响面（关联任务、快捷键、报警分区、绑定用户，
      以及会被连带回收的空分区）。
-->
<template>
  <div class="terminal-page">
    <!-- 左：分区树 -->
    <div class="group-panel">
      <div class="group-title">终端分区</div>
      <el-scrollbar class="group-scroll">
        <div
          v-for="g in groups"
          :key="g.id"
          class="group-item"
          :class="{ active: g.id === currentGroup, virtual: g.virtual }"
          @click="selectGroup(g.id)"
        >
          <el-icon><Folder v-if="!g.virtual" /><Menu v-else /></el-icon>
          <span class="group-name" :title="g.info || g.name">{{ g.name }}</span>
          <span class="group-count">{{ g.count }}</span>
        </div>
      </el-scrollbar>
    </div>

    <!-- 右：终端列表 -->
    <div class="table-box list-panel">
      <!--
        终端类型页签。分类依据是 terminaltype 的能力位（isdecode / isencode / isspeech）
        与型号名，服务端 categoryCond() 里有完整说明 —— 数据库里没有「类别」这一列。
      -->
      <el-tabs v-model="initParam.category" class="type-tabs">
        <el-tab-pane v-for="t in typeTabs" :key="t.key" :label="t.label" :name="t.key" />
      </el-tabs>

      <ProTable
        ref="proTableRef"
        :columns="columns"
        :request-api="getTerminalListApi"
        :init-param="initParam"
        :data-callback="dataCallback"
        row-key="id"
        @sort-change="onSortChange"
      >
        <!--
          :80 的头部只有一个「批量操作」下拉，所有整批动作收在里面。
          我们把原来平铺的启动/停止/音量/状态开关/线路检测/同步时间/终端密码/删除
          全部并进这一个下拉，动作一个没少，只是收进了菜单。
        -->
        <template #tableHeader="scope">
          <div class="header-bar">
            <div class="header-left">
              <!--
                ⚠ 23 项竖着排会顶出屏幕（:80 上同样如此，它是靠滚动，
                  实测只看得到前 10 项）。这里改成多列铺开：项与顺序不变，
                  一屏放得下，不用滚。列数与每列高度见 .batch-menu 的样式。
              -->
              <el-dropdown
                popper-class="batch-menu"
                :disabled="!scope.isSelected"
                @command="cmd => onBatchCmd(cmd, scope.selectedListIds)"
              >
                <el-button :disabled="!scope.isSelected">
                  批量操作{{ scope.selectedListIds.length ? `(${scope.selectedListIds.length})` : "" }}
                  <el-icon class="el-icon--right"><ArrowDown /></el-icon>
                </el-button>
                <!--
                  菜单项、措辞与顺序照 :80（从 oktw 前端产物里逐条抄的定义），
                  功能实现按 ok112（我们的后端说的是 ok112 那套文本报文）。

                  ⚠ 每项的「单选 / 需在线」也照 :80 的 single / isNetwork 字段，
                    见 batchItems 的注释。
                -->
                <template #dropdown>
                  <el-dropdown-menu>
                    <!--
                      ⚠ 多列排版下不要用 divided：分隔线是每项自己的 border-top，
                        分到不同列里就变成了几条位置莫名其妙的横线。分组改用列来体现。
                    -->
                    <el-dropdown-item
                      v-for="it in batchItems"
                      :key="it.cmd"
                      :command="it.cmd"
                      :disabled="!itemEnabled(it, scope.selectedListIds)"
                    >
                      {{ it.label }}
                    </el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </div>
            <div class="header-right">
              <el-tag v-if="scopeNote" type="info" size="small" effect="plain">{{ scopeNote }}</el-tag>
            </div>
          </div>
        </template>

        <template #netstate="scope">
          <el-tag :type="scope.row.netstate === 1 ? 'success' : 'info'" size="small">
            {{ scope.row.netstate === 1 ? "在线" : "离线" }}
          </el-tag>
        </template>

        <template #devicestate="scope">
          <el-tag :type="scope.row.devicestate === 1 ? 'success' : 'info'" size="small" effect="plain">
            {{ scope.row.devicestate === 1 ? "已启动" : "已停止" }}
          </el-tag>
        </template>

        <template #taskstate="scope">
          <el-tag :type="scope.row.taskstate === 1 ? 'warning' : 'info'" size="small" effect="plain">
            {{ scope.row.taskstate === 1 ? "播放中" : "空闲" }}
          </el-tag>
        </template>

        <!-- 对讲 / 急救 / 录音 / 发言 四列，:80 是一列一个开关状态 -->
        <template #isspeech="scope">
          <span :class="scope.row.isspeech === 1 ? 'on' : 'off'">{{ scope.row.isspeech === 1 ? "开" : "关" }}</span>
        </template>
        <template #instancy="scope">
          <span :class="scope.row.instancy === 1 ? 'on' : 'off'">{{ scope.row.instancy === 1 ? "开" : "关" }}</span>
        </template>
        <template #isrecord="scope">
          <span :class="scope.row.isrecord === 1 ? 'on' : 'off'">{{ scope.row.isrecord === 1 ? "开" : "关" }}</span>
        </template>
        <template #issponsor="scope">
          <span :class="scope.row.issponsor === 1 ? 'on' : 'off'">{{ scope.row.issponsor === 1 ? "开" : "关" }}</span>
        </template>

        <!--
          ⚠ 开路 = 线路断了。lopencircuit / ropencircuit 为 1 时是「开路」，
          所以 1 显示「开路」（红），0 显示「正常」。别把它读成「通」。
        -->
        <template #lopencircuit="scope">
          <span :class="scope.row.lopencircuit === 1 ? 'bad' : 'ok'">
            {{ scope.row.lopencircuit === 1 ? "开路" : "正常" }}
          </span>
        </template>
        <template #ropencircuit="scope">
          <span :class="scope.row.ropencircuit === 1 ? 'bad' : 'ok'">
            {{ scope.row.ropencircuit === 1 ? "开路" : "正常" }}
          </span>
        </template>

        <template #temperature="scope">
          <span v-if="scope.row.temperature">{{ scope.row.temperature }}</span>
          <span v-else class="muted">—</span>
        </template>
        <template #humidity="scope">
          <span v-if="scope.row.humidity">{{ scope.row.humidity }}</span>
          <span v-else class="muted">—</span>
        </template>

        <template #operation="scope">
          <el-button type="primary" link :icon="EditPen" :disabled="!canEdit" @click="openEdit(scope.row)">查看</el-button>
          <!--
            终端自己跑着一套独立的 Web 程序，这里只负责把用户送过去，
            设备侧的配置一律不在服务器 Web 里代管。
            离线终端旧版是点了才弹 alert，这里直接禁用并把原因写在 title 上（BR-140）。
          -->
          <el-button
            type="primary"
            link
            :icon="Link"
            :disabled="!scope.row.online"
            :title="scope.row.online ? scope.row.webUrl : '终端已断开，无法打开其 Web 页'"
            @click="browse(scope.row)"
          >
            浏览
          </el-button>
        </template>
      </ProTable>
    </div>

    <!-- 编辑终端 -->
    <el-dialog v-model="dlg.visible" title="修改终端" width="600px">
      <el-form :model="dlg.form" label-width="110px">
        <el-form-item label="终端名称" required>
          <el-input v-model="dlg.form.terminalname" maxlength="85" show-word-limit />
        </el-form-item>
        <el-form-item label="终端类型" required>
          <el-select v-model="dlg.form.typeid" filterable class="fill">
            <el-option v-for="t in types" :key="t.id" :label="t.name" :value="t.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="终端分区">
          <el-select v-model="dlg.form.groupId" class="fill">
            <el-option label="(未分区)" :value="0" />
            <el-option v-for="g in realGroups" :key="g.id" :label="g.name" :value="g.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="IP 地址" required>
          <el-input v-model="dlg.form.ip" placeholder="192.168.2.31" />
        </el-form-item>
        <el-form-item label="音量">
          <el-slider v-model="dlg.form.volume" :min="0" :max="100" show-input />
        </el-form-item>
        <!--
          terminal.postion 刻意不出现在这个表单里。
          列名虽然叫「位置」，但现网这一列存的是后台 C 服务写入的固件版本串
          （TW_V1.0.0.3:Aug 19 2026-23:57:47 这种），属于设备上报的数据，
          不归服务器 Web 代管。提交时由 openEdit 读到的原值原样回填，
          既不覆盖 C 服务写的内容，也不会因为漏传而把该列清空。
          服务端的 GBK 校验仍然保留，作为兜底。
        -->
      </el-form>
      <template #footer>
        <el-button @click="dlg.visible = false">取消</el-button>
        <el-button type="primary" :loading="dlg.saving" @click="submitEdit">保存</el-button>
      </template>
    </el-dialog>

    <!-- 音量 -->
    <el-dialog v-model="vol.visible" title="设置音量" width="440px">
      <p class="dlg-note">将对选中的 {{ vol.ids.length }} 台终端生效</p>
      <el-slider v-model="vol.value" :min="0" :max="100" show-input />
      <template #footer>
        <el-button @click="vol.visible = false">取消</el-button>
        <el-button type="primary" :loading="vol.saving" @click="submitVolume">下发</el-button>
      </template>
    </el-dialog>

    <!-- 终端密码 -->
    <el-dialog v-model="pwd.visible" title="设置终端密码" width="460px">
      <p class="dlg-note">将对选中的 {{ pwd.ids.length }} 台终端下发</p>
      <el-input v-model="pwd.value" type="password" show-password maxlength="32" placeholder="1 ~ 32 个字符" />
      <template #footer>
        <el-button @click="pwd.visible = false">取消</el-button>
        <el-button type="primary" :loading="pwd.saving" @click="submitPassword">下发</el-button>
      </template>
    </el-dialog>

    <!-- 删除确认 -->
    <el-dialog v-model="del.visible" title="删除终端" width="720px" top="6vh">
      <el-alert type="error" :closable="false" show-icon class="mb12">
        删除终端会清理它在全系统的关联数据，且<b>不可恢复</b>。
      </el-alert>

      <el-table v-if="del.preview?.deletable.length" :data="del.preview.deletable" size="small" max-height="320">
        <el-table-column prop="terminalname" label="终端" min-width="130" />
        <el-table-column label="影响面" min-width="380">
          <template #default="{ row }">
            <div class="impact">
              <el-tag v-if="row.impact?.tasks" type="danger" size="small" class="mr4">
                关联任务 {{ row.impact?.tasks }}
              </el-tag>
              <el-tag v-if="row.impact?.boundUsers" size="small" class="mr4">绑定用户 {{ row.impact?.boundUsers }}</el-tag>
              <el-tag v-if="row.impact?.shortcutKeys" size="small" class="mr4">快捷键 {{ row.impact?.shortcutKeys }}</el-tag>
              <el-tag v-if="row.impact?.alarmAreas" size="small" class="mr4">报警分区 {{ row.impact?.alarmAreas }}</el-tag>
              <el-tag v-if="row.impact?.callGroups" size="small" class="mr4">呼叫组 {{ row.impact?.callGroups }}</el-tag>
              <el-tag v-if="row.impact?.offlineTasks" size="small" class="mr4">离线任务 {{ row.impact?.offlineTasks }}</el-tag>
              <el-tag v-if="row.impact?.groupWillBeDeleted" type="warning" size="small">
                分区「{{ row.impact?.groupName }}」将一并删除
              </el-tag>
              <!-- 名字列表最多回 50 条且按名字去重，条数以 impact.tasks 为准 -->
              <div v-if="row.impact?.taskNames.length" class="task-names">
                任务：{{ row.impact?.taskNames.join("、")
                }}<template v-if="row.impact?.tasks > row.impact?.taskNames.length">
                  等 {{ row.impact?.tasks }} 条
                </template>
              </div>
            </div>
          </template>
        </el-table-column>
      </el-table>

      <el-alert v-if="del.preview?.skipped.length" type="warning" :closable="false" class="mt12">
        以下终端不会被删除：
        <span v-for="s in del.preview.skipped" :key="s.id">{{ s.name || s.id }}（{{ s.detail }}）</span>
      </el-alert>

      <template #footer>
        <el-button @click="del.visible = false">取消</el-button>
        <el-button
          type="danger"
          :loading="del.saving"
          :disabled="!del.preview?.deletable.length"
          @click="submitDelete"
        >
          确认删除 {{ del.preview?.deletable.length || 0 }} 台
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="terminalManage">
import { ArrowDown, EditPen, Folder, Link, Menu } from "@element-plus/icons-vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { computed, onMounted, reactive, ref } from "vue";

import {
  checkTerminalCircuitApi,
  deleteTerminalsApi,
  getTerminalApi,
  getTerminalGroupTreeApi,
  getTerminalListApi,
  getTerminalTypesApi,
  previewDeleteTerminalsApi,
  setTerminalPasswordApi,
  setTerminalRunningApi,
  setTerminalToggleApi,
  setTerminalVolumeApi,
  syncTerminalTimeApi,
  updateTerminalApi
} from "@/api/modules/terminal";
import type {
  DeletePreview,
  OpResult,
  TerminalCaps,
  TerminalGroupNode,
  TerminalRow,
  TerminalType,
  ToggleKey
} from "@/api/modules/terminal";
import ProTable from "@/components/ProTable/index.vue";
import { useAuthStore } from "@/stores/modules/auth";
import type { ColumnProps, ProTableInstance } from "@/components/ProTable/interface";

const authStore = useAuthStore();
// store 里 authButtonList 的声明是 { [key: string]: string[] }（Geeker 原始模板的形状），
// 而后端下发的是 map[string]map[string]bool。这里和用户模块保持同一种写法：
// 就地断言成 any，不去动共用 store 的类型声明。
const btn = computed(() => (authStore.authButtonListGet as any)?.terminal ?? {});
const canControl = computed(() => !!btn.value.control);
const canEdit = computed(() => !!btn.value.edit);
const canDelete = computed(() => !!btn.value.delete);

/** ProTable 的 selectedListIds 是 string[]（row-key 取的字符串），统一转成数字 id */
const toIds = (raw: (string | number)[]) => (raw ?? []).map(Number).filter(n => Number.isFinite(n) && n > 0);

const proTableRef = ref<ProTableInstance>();
const groups = ref<TerminalGroupNode[]>([]);
const types = ref<TerminalType[]>([]);
const currentGroup = ref<number>(0);
const scopeNote = ref("");
// initParam 被 ProTable 深度 watch，改动即触发重新拉取。
// 排序放在这里而不是让 el-table 自己排 —— 旧版就是前端排序，
// 只能排当前页，翻页后顺序就乱了（缺陷 D-75）。
const initParam = reactive({ groupId: 0, category: "", orderBy: "", order: "" });

const onSortChange = ({ prop, order }: { prop: string; order: string | null }) => {
  if (!order) {
    initParam.orderBy = "";
    initParam.order = "";
    return;
  }
  initParam.orderBy = prop;
  initParam.order = order === "ascending" ? "asc" : "desc";
};

/** 编辑弹窗里的分区下拉只列真实分区，不含「全部 / 未分区」两个虚拟节点 */
const realGroups = computed(() => groups.value.filter(g => !g.virtual));

// 列清单严格照 :80（页面规格.txt「终端管理」17 列）：
// id | 终端名称 | 终端类型 | 任务状态 | 网络状态 | 设备状态 | IP地址 | 音量 |
// 对讲 | 急救 | 录音 | 发言 | 左声道开路 | 右声道开路 | 温度(℃) | 湿度(RH) | 操作
//
// 「分区」列被拿掉了 —— :80 没有这一列，分区靠左边那棵树看。
// 搜索也收敛成一个「设备名称查找」，与 :80 一致（IP 搜索随之取消）。
const columns = reactive<ColumnProps<TerminalRow>[]>([
  { type: "selection", fixed: "left", width: 50 },
  { prop: "id", label: "编号", width: 70, sortable: "custom" },
  {
    prop: "terminalname",
    label: "终端名称",
    minWidth: 160,
    sortable: "custom",
    search: { el: "input", props: { placeholder: "设备名称查找" } }
  },
  { prop: "typeName", label: "终端类型", width: 130 },
  { prop: "taskstate", label: "任务状态", width: 100, sortable: "custom" },
  { prop: "netstate", label: "网络状态", width: 100, sortable: "custom" },
  { prop: "devicestate", label: "设备状态", width: 100, sortable: "custom" },
  { prop: "ip", label: "IP地址", width: 130, sortable: "custom" },
  { prop: "volume", label: "音量", width: 80, sortable: "custom" },
  { prop: "isspeech", label: "对讲", width: 70 },
  { prop: "instancy", label: "急救", width: 70 },
  { prop: "isrecord", label: "录音", width: 70 },
  { prop: "issponsor", label: "发言", width: 70 },
  { prop: "lopencircuit", label: "左声道开路", width: 110 },
  { prop: "ropencircuit", label: "右声道开路", width: 110 },
  { prop: "temperature", label: "温度(℃)", width: 95 },
  { prop: "humidity", label: "湿度(RH)", width: 95 },
  { prop: "operation", label: "操作", fixed: "right", width: 140 }
]);

// 页签的取值由服务端 categoryCond() 解释；key 是空串时不过滤。
const typeTabs = [
  { key: "", label: "全部" },
  { key: "decode", label: "解码终端" },
  { key: "encode", label: "采集终端" },
  { key: "mic", label: "话筒" },
  { key: "remote", label: "遥控终端" },
  { key: "alarm", label: "报警终端" },
  { key: "speech", label: "对讲终端" },
  { key: "ext", label: "扩展设备" }
];

const dataCallback = (data: any) => {
  scopeNote.value = data.scopeNote ?? "";
  return { list: data.list, total: data.total, pageNum: data.pageNum, pageSize: data.pageSize };
};

const refresh = () => proTableRef.value?.getTableList();

const loadGroups = async () => {
  const { data } = await getTerminalGroupTreeApi();
  groups.value = data;
};

const selectGroup = (id: number) => {
  currentGroup.value = id;
  initParam.groupId = id;
};

const browse = (row: TerminalRow) => {
  if (!row.online) return ElMessage.warning("终端已断开");
  window.open(row.webUrl, "_blank");
};

/**
 * 统一呈现批量操作结果。
 * 后端返回的是「部分成功」：succeeded 做了，skipped 逐条带原因。
 */
const reportOp = (res: OpResult, action: string) => {
  const okCount = res.succeeded.length;
  if (!res.skipped.length) {
    ElMessage.success(`${action}成功，共 ${okCount} 台`);
  } else {
    const detail = res.skipped.map(s => `${s.name || s.id}：${s.detail}`).join("；");
    ElMessageBox.alert(detail, `${action}完成：成功 ${okCount} 台，跳过 ${res.skipped.length} 台`, {
      type: okCount ? "warning" : "error"
    });
  }
  refresh();
  loadGroups();
};

const running = async (raw: (string | number)[], start: boolean) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning("请先勾选终端");
  const { data } = await setTerminalRunningApi(ids, start);
  reportOp(data, start ? "启动" : "停止");
};

/** 「批量操作」下拉的总入口：按 command 分派到原来那几个函数 */
/*
  批量操作菜单。

  # 名称、顺序、单选/需在线 —— 照 :80

  逐条抄自 oktw 前端产物里的定义（每项带 name / single / isNetwork）。
  我们的 `ready:false` 是额外加的：那几项 ok112 里有、但新版后端还没做，
  先摆在菜单里置灰，位置和 :80 一致，不至于让熟悉旧系统的人找不到。

  # 功能实现 —— 照 ok112

  后端说的是 ok112 那套文本报文（terminal?state=N&id=...），
  不是 :80 的二进制 sdk:8877 协议。好在两边语义是一致的，例如
  ok112 的「启用终端」= state 1 + `UPDATE terminal SET devicestate=1`，
  与 :80 的 cmd 16 是同一件事。

  ⚠ 「重新注册」在 :80 上 code 就是 delete、且 isNetwork:false（离线也照删）、
    点了不确认。这里保留名字，但必须走确认框 —— 见 openReRegister()。
*/
interface BatchItem {
  cmd: string;
  label: string;
  /** 只能选一个终端（照 :80 的 single） */
  single?: boolean;
  /** 后端已实现；false 时菜单里置灰 */
  ready: boolean;
  divided?: boolean;
  /**
   * 这一项要求终端类型具备哪种能力。
   * 取值是服务端下发的 caps 里的字段名（规则在 terminal/caps.go，来自 ok112）。
   * 不填表示不限终端类型。
   *
   * ⚠ 判据是「**选中的终端里至少有一台**支持」才让点 ——
   *   不是「全都支持」。混选时按 ok112 的口径，能做的做掉，
   *   做不了的由服务端跳过并在结果里逐台说明。
   */
  cap?: keyof TerminalCaps;
}

const batchItems: BatchItem[] = [
  { cmd: "enable", label: "启用终端", ready: true },
  { cmd: "disable", label: "停用终端", ready: true },
  { cmd: "speech:1", label: "启用对讲", ready: true, cap: "speech" },
  { cmd: "speech:0", label: "关闭对讲", ready: true, cap: "speech" },
  { cmd: "reregister", label: "重新注册", ready: true },
  { cmd: "view-shortcut", label: "查看快捷键", single: true, ready: false, cap: "shortcut" },
  { cmd: "del-shortcut", label: "删除快捷键", single: true, ready: false, cap: "shortcut" },
  { cmd: "quick-task", label: "快捷任务", single: true, ready: false, cap: "quickTask" },
  { cmd: "password", label: "设置终端密码", single: true, ready: true, cap: "password" },
  { cmd: "instancy:1", label: "设置急救", ready: true, cap: "instancy" },
  { cmd: "instancy:0", label: "取消急救", ready: true, cap: "instancy" },
  { cmd: "record:1", label: "启用录音", ready: true },
  { cmd: "record:0", label: "停用录音", ready: true },
  { cmd: "volume", label: "调整音量", ready: true },
  { cmd: "auth-paging", label: "授权寻呼", single: true, ready: false, cap: "authPaging" },
  { cmd: "replace", label: "终端替换", single: true, ready: false },
  { cmd: "add-terminal", label: "增补终端", single: true, ready: false },
  // ⚠ 发言与对讲判据不同：对讲要 isdecode 与 isencode **都**为 1，
  //   发言只要不是两个都为 0。所以这里是 sponsor 不是 speech。
  { cmd: "sponsor:1", label: "启用发言", ready: true, cap: "sponsor" },
  { cmd: "sponsor:0", label: "停用发言", ready: true, cap: "sponsor" },
  { cmd: "autocheck", label: "自动寻检", ready: false, cap: "autoCheck" },
  { cmd: "synctime", label: "同步时间", ready: true },
  { cmd: "auth-terminal", label: "授权终端", single: true, ready: false, cap: "authPaging" },
  // 线路检测是 ok112 有、:80 菜单里没有的一项。它在我们这边一直能用，
  // 放在最后，不打乱上面与 :80 对齐的顺序。
  { cmd: "circuit", label: "线路检测", ready: true, cap: "circuit" }
];

/** 选中的那几台终端（从当前页数据里取，不额外发请求） */
const selectedRows = (selected: (string | number)[]) => {
  const ids = toIds(selected);
  const rows = (proTableRef.value?.tableData ?? []) as TerminalRow[];
  return rows.filter(r => ids.includes(r.id));
};

/**
 * 菜单项可点与否。
 *
 * 四道判断：后端有没有实现 → 权限 → 单选项是否恰好选一个 → 终端类型能力。
 *
 * ⚠ 类型能力按「至少一台支持」放行，不是「全都支持」。
 *   混选（比如同时选了话筒和音箱）时，ok112 的口径是能做的做掉；
 *   我们更进一步：服务端跳过不支持的那些，并在结果里逐台说明原因。
 *   要求「全都支持」的话，混选时整项都点不了，反而更难用。
 */
const itemEnabled = (it: BatchItem, selected: (string | number)[]) => {
  if (!it.ready) return false;
  if (it.cmd === "reregister") return canDelete.value;
  if (!canControl.value) return false;
  if (it.single && selected.length !== 1) return false;
  if (it.cap) {
    const rows = selectedRows(selected);
    // 拿不到行数据时不拦（比如跨页选中），交给服务端判
    if (rows.length && !rows.some(r => r.caps?.[it.cap!])) return false;
  }
  return true;
};

const onBatchCmd = (cmd: string, raw: (string | number)[]) => {
  if (cmd === "enable") return running(raw, true);
  if (cmd === "disable") return running(raw, false);
  if (cmd === "volume") return openVolume(raw);
  if (cmd === "reregister") return openReRegister(raw);
  if (cmd.includes(":")) return onToggleCmd(cmd, raw);
  return onMoreCmd(cmd, raw);
};

const onToggleCmd = async (cmd: string, raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning("请先勾选终端");
  const [key, on] = cmd.split(":");
  const { data } = await setTerminalToggleApi(ids, key as ToggleKey, on === "1");
  const label = batchItems.find(t => t.cmd === cmd)?.label ?? "设置";
  reportOp(data, label);
};

const onMoreCmd = async (cmd: string, raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning("请先勾选终端");
  if (cmd === "password") {
    pwd.ids = [...ids];
    pwd.value = "";
    pwd.visible = true;
    return;
  }
  if (cmd === "circuit") {
    const { data } = await checkTerminalCircuitApi(ids);
    return reportOp(data, "线路检测指令下发");
  }
  const { data } = await syncTerminalTimeApi(ids);
  reportOp(data, "时间同步指令下发");
};

// ---- 编辑 ----
const dlg = reactive({
  visible: false,
  saving: false,
  id: 0,
  form: { terminalname: "", typeid: 0, groupId: 0, ip: "", postion: "", volume: 50 }
});

const openEdit = async (row: TerminalRow) => {
  const { data } = await getTerminalApi(row.id);
  dlg.id = data.id;
  dlg.form = {
    terminalname: data.terminalname,
    typeid: data.typeid,
    groupId: data.groupId,
    ip: data.ip,
    postion: data.postion,
    volume: data.volume
  };
  dlg.visible = true;
};

const submitEdit = async () => {
  dlg.saving = true;
  try {
    await updateTerminalApi(dlg.id, { ...dlg.form });
    ElMessage.success("已保存并通知后台服务");
    dlg.visible = false;
    refresh();
    loadGroups();
  } finally {
    dlg.saving = false;
  }
};

// ---- 音量 ----
const vol = reactive({ visible: false, saving: false, ids: [] as number[], value: 50 });
const openVolume = (raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning("请先勾选终端");
  vol.ids = ids;
  vol.value = 50;
  vol.visible = true;
};
const submitVolume = async () => {
  vol.saving = true;
  try {
    const { data } = await setTerminalVolumeApi(vol.ids, vol.value);
    vol.visible = false;
    reportOp(data, "音量下发");
  } finally {
    vol.saving = false;
  }
};

// ---- 终端密码 ----
const pwd = reactive({ visible: false, saving: false, ids: [] as number[], value: "" });
const submitPassword = async () => {
  pwd.saving = true;
  try {
    const { data } = await setTerminalPasswordApi(pwd.ids, pwd.value);
    pwd.visible = false;
    reportOp(data, "密码下发");
  } finally {
    pwd.saving = false;
  }
};

// ---- 删除 ----
const del = reactive({ visible: false, saving: false, ids: [] as number[], preview: null as DeletePreview | null });

const openDelete = async (raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning("请先勾选终端");
  del.ids = ids;
  const { data } = await previewDeleteTerminalsApi(ids);
  del.preview = data;
  del.visible = true;
};

/*
  「重新注册」。

  ⚠ 这个名字来自 :80，但它做的事是**删除终端记录**（:80 上 code 就是 delete）。
    在线设备被删后会自行重新注册回来，所以看着像"重新注册"；
    **离线设备就是丢了**，而且 :80 上它 isNetwork:false —— 离线也照删、点了不确认。

    2026-08-27 现网因此丢过 9 台离线终端（详见缺陷清单 §19.1.8）。
    所以这里保留名字，但强制走确认框，并把「其中多少台离线、会丢」当场算出来。

  ok112 的原话也是这个意思：
    「终端重新注册后原有终端执行的任务不再执行，必须修改任务让相关的任务在终端上执行」
*/
const openReRegister = async (raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning("请先勾选终端");

  // 离线台数直接从当前列表数据里数，不额外发请求
  const rows = (proTableRef.value?.tableData ?? []) as TerminalRow[];
  const picked = rows.filter(r => ids.includes(r.id));
  const offline = picked.filter(r => r.netstate !== 1);

  await ElMessageBox.confirm(
    `将对选中的 ${ids.length} 台终端执行重新注册。\n\n` +
      "⚠ 它的实际动作是**删除终端记录**：在线设备会自动重新注册回来（但会拿到新的终端 ID），" +
      "离线设备删掉就没有了。\n\n" +
      (offline.length
        ? `选中的终端里有 ${offline.length} 台当前离线，会直接丢失：\n` +
          offline.slice(0, 10).map(r => "  · " + (r.terminalname || `终端 ${r.id}`)).join("\n") +
          (offline.length > 10 ? `\n  · …另有 ${offline.length - 10} 台` : "") +
          "\n\n"
        : "选中的终端当前全部在线。\n\n") +
      "另外：重新注册后，原来在这些终端上执行的任务不再执行，需要到任务里重新指定终端。",
    "重新注册",
    { type: "warning", confirmButtonText: "确认重新注册", cancelButtonText: "取消" }
  );

  // 复用删除那条链路：它已经处理好空分区回收、专属任务清理等连带影响
  del.ids = ids;
  const { data } = await previewDeleteTerminalsApi(ids);
  del.preview = data;
  del.visible = true;
};

const submitDelete = async () => {
  del.saving = true;
  try {
    const { data } = await deleteTerminalsApi(del.preview!.deletable.map(d => d.id));
    del.visible = false;
    const extra: string[] = [];
    if (data.deletedGroups.length) extra.push(`回收空分区 ${data.deletedGroups.length} 个`);
    if (data.deletedCallGroups.length) extra.push(`回收空呼叫组 ${data.deletedCallGroups.length} 个`);
    if (data.affectedTasks) extra.push(`删除专属任务 ${data.affectedTasks} 条`);
    ElMessage.success(`已删除 ${data.deleted.length} 台终端${extra.length ? "，" + extra.join("，") : ""}`);
    refresh();
    loadGroups();
  } finally {
    del.saving = false;
  }
};

onMounted(async () => {
  await loadGroups();
  const { data } = await getTerminalTypesApi();
  types.value = data;
});
</script>

<style scoped lang="scss">
.terminal-page {
  display: flex;
  gap: 10px;
  height: 100%;
}
.group-panel {
  display: flex;
  flex-direction: column;
  width: 210px;
  flex-shrink: 0;
  padding: 12px 0;
  background-color: var(--el-bg-color);
  border-radius: 6px;
}
.group-title {
  padding: 0 14px 10px;
  font-size: 15px;
  font-weight: 600;
}
.group-scroll {
  flex: 1;
}
.group-item {
  display: flex;
  gap: 6px;
  align-items: center;
  padding: 8px 14px;
  font-size: 13px;
  cursor: pointer;
  &:hover {
    background-color: var(--el-fill-color-light);
  }
  &.active {
    color: var(--el-color-primary);
    background-color: var(--el-color-primary-light-9);
  }
  &.virtual {
    font-weight: 500;
  }
}
.group-name {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.group-count {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.list-panel {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-width: 0;
}
.header-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  justify-content: space-between;
  width: 100%;
}
.header-left {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}
.form-tip {
  display: block;
  font-size: 12px;
  line-height: 1.6;
  color: var(--el-text-color-secondary);
}
.dlg-note {
  margin-bottom: 10px;
  font-size: 13px;
  color: var(--el-text-color-secondary);
}
.muted {
  color: var(--el-text-color-placeholder);
}
.mr4 {
  margin-right: 4px;
}
.mb12 {
  margin-bottom: 12px;
}
.mt12 {
  margin-top: 12px;
}
.fill {
  width: 100%;
}
.ok {
  color: var(--el-color-success);
}
.bad {
  color: var(--el-color-danger);
}
.on {
  color: var(--el-color-success);
}
.off {
  color: var(--el-text-color-placeholder);
}
.type-tabs {
  margin-bottom: -6px;
  :deep(.el-tabs__header) {
    margin-bottom: 8px;
  }
}
.task-names {
  margin-top: 4px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
</style>

<!--
  ⚠ 这一段**不能**加 scoped。

  el-dropdown 的下拉面板是 teleport 到 body 的，不在本组件的 DOM 子树里，
  scoped 生成的 data-v 属性选择器落不到它身上，写了等于没写。
  用 popper-class="batch-menu" 限定作用范围，避免污染别处的下拉。
-->
<style lang="scss">
.batch-menu .el-dropdown-menu {
  // 23 项竖排会顶出屏幕。改成按列铺：先填满一列再换下一列，
  // 所以从上往下读的顺序仍与 :80 一致。
  display: grid;
  grid-auto-flow: column;
  grid-template-rows: repeat(8, auto); // 8 行 × 3 列 = 24 格，正好装下 23 项
  padding: 6px;
}
.batch-menu .el-dropdown-menu__item {
  // 网格里每格自己撑开；给个下限让三列宽度一致，不至于长短不齐
  min-width: 116px;
  padding: 6px 14px;
  border-radius: 4px;
}
// 窄屏（比如笔记本 1366 宽）退回两列，免得横着又顶出去
@media (width <= 1500px) {
  .batch-menu .el-dropdown-menu {
    grid-template-rows: repeat(12, auto); // 12 行 × 2 列
  }
}
</style>
