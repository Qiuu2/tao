<!--
  终端功放 / 采播管理 / 文字语音 / LED 播放 共用的页面骨架

  这四个页面在旧版是四个各写一遍的 php（terminalfunctionplay / admmanager /
  displayttsmanager / ledtaskmanager），四份几乎一样的列表 + 四份分别改坏过的搜索分支。
  它们做的是同一件事：按 tasktype 从 task 表里捞任务。所以这里做成一个组件，
  差异（列、表单里那几段、能不能建）全部由 kind 决定。

  ⚠ 三个和直觉相反的地方，改这个文件之前先看一眼：
    · projectstate  0 = 启用、1 = 停用（数据库列注释是反的）
    · 功放/采播任务**没有媒体**，启动前不检查媒体 —— 它们是「开电源」「转采播源」指令
    · 文字语音的 media 行是合成出来的（typeid='tts'），磁盘上没有对应文件
-->
<template>
  <div class="table-box">
    <!-- LED 多一层任务分组 -->
    <div v-if="kind === 'led'" class="folder-bar">
      <span class="folder-label">任务分组</span>
      <el-radio-group v-model="initParam.folderId" size="small" @change="refresh">
        <el-radio-button :label="0">全部</el-radio-button>
        <el-radio-button v-for="f in ledFolders" :key="f.id" :label="f.id">
          {{ f.name }}（{{ f.taskCount }}）
        </el-radio-button>
      </el-radio-group>
      <div class="grow"></div>
      <el-button size="small" :icon="Setting" @click="devDlg.visible = true">LED 设备</el-button>
    </div>

    <ProTable
      ref="proTableRef"
      :columns="columns"
      :request-api="listApi"
      :init-param="initParam"
      :data-callback="dataCallback"
      row-key="taskId"
    >
      <!--
        按钮对齐 :80（docs/image/oktw/页面规格.txt）：
          终端功放  添加 / 删除 / 执行 / 停止 /（设置音量）
          采播管理  添加 / 删除 / 执行 / 停止 / 启用 / 停用 /（设置音量）
          文字语音  添加 / 删除 / 执行 / 停止 / 启用 / 停用 /（设置音量）
          led播放   添加 / 删除 / 执行 / 停止 / 启用 / 停用
        添加是 primary、删除是 danger，其余是默认样式。
        「设置音量」走 /api/tasks/volume —— 这四类任务和文件广播同在 task 表里，
        批量改 defaultvolume 是同一件事，没必要再开一套接口。
      -->
      <template #tableHeader="scope">
        <div class="header-bar">
          <div class="header-left">
            <el-button type="primary" :disabled="!canEdit" @click="openCreate">添加</el-button>
            <el-button type="danger" :disabled="!canEdit || !scope.isSelected" @click="doDelete(scope.selectedListIds)">
              删除
            </el-button>
            <el-button :disabled="!canEdit || !scope.isSelected" @click="doControl('start', scope.selectedListIds)">
              执行
            </el-button>
            <el-button :disabled="!canEdit || !scope.isSelected" @click="doControl('stop', scope.selectedListIds)">
              停止
            </el-button>
            <template v-if="kind !== 'amplifier'">
              <el-button :disabled="!canEdit || !scope.isSelected" @click="doState(scope.selectedListIds, true)">
                启用
              </el-button>
              <el-button :disabled="!canEdit || !scope.isSelected" @click="doState(scope.selectedListIds, false)">
                停用
              </el-button>
            </template>
            <!-- led 播放在 :80 上没有「设置音量」这个按钮 -->
            <el-button
              v-if="kind !== 'led'"
              :disabled="!canEdit || !scope.isSelected"
              @click="openVolume(scope.selectedListIds)"
            >
              设置音量
            </el-button>
          </div>
          <div class="header-right">
            <el-tag v-if="scopeNote" type="info" size="small" effect="plain">{{ scopeNote }}</el-tag>
          </div>
        </div>
      </template>

      <template #projectstate="s">
        <el-tag :type="s.row.projectstate === 0 ? 'success' : 'info'" size="small">{{ s.row.projectText }}</el-tag>
      </template>

      <template #state="s">
        <el-tag :type="s.row.state === 1 || s.row.state === 3 ? 'warning' : 'info'" size="small" effect="plain">
          {{ s.row.stateText }}
        </el-tag>
      </template>

      <template #operation="s">
        <el-button type="primary" link :icon="EditPen" :disabled="!canEdit || !s.row.canModify" @click="openEdit(s.row)">
          修改
        </el-button>
        <el-button
          type="danger"
          link
          :icon="Delete"
          :disabled="!canEdit || !s.row.canModify"
          @click="doDelete([s.row.taskId])"
        >
          删除
        </el-button>
      </template>
    </ProTable>

    <!-- 设置音量：复用文件广播那套 /api/tasks/volume，四类任务同在 task 表里 -->
    <el-dialog v-model="vol.visible" title="设置音量" width="440px">
      <el-slider v-model="vol.value" :min="0" :max="100" show-input />
      <template #footer>
        <el-button @click="vol.visible = false">取消</el-button>
        <el-button type="primary" :loading="vol.saving" @click="submitVolume">确定</el-button>
      </template>
    </el-dialog>

    <!-- ============ 新建 / 修改 ============ -->
    <el-dialog v-model="dlg.visible" :title="dlg.title" width="860px" top="4vh">
      <!--
        分步向导，对齐 :80 的「上一步 / 下一步 / 取消」。
        ⚠ 用 v-show 而不是 v-if：各步的输入必须始终挂载着，切步骤不能把没提交的内容销毁。
        功放没有第 2 步（它既没有播报文字也没有 LED 字幕），所以那一类只有两步。
      -->
      <el-steps :active="step" finish-status="success" simple class="mb12">
        <el-step title="基本信息" />
        <el-step v-if="hasContentStep" :title="kind === 'led' ? 'LED 内容' : '播放内容'" />
        <el-step title="播放终端" />
      </el-steps>

      <el-form :model="form" label-width="110px">
        <div v-show="step === 0">
        <el-row :gutter="18">
          <!-- 表单项名称照 :80 的「添加」弹窗（docs/image/oktw/子页规格.txt） -->
          <el-col :span="12">
            <el-form-item label="任务名" required>
              <el-input v-model="form.taskName" maxlength="60" show-word-limit placeholder="请输入任务名" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="任务音量">
              <el-slider v-model="form.defaultvolume" :min="0" :max="100" show-input size="small" />
            </el-form-item>
          </el-col>

          <!--
            预开电源 / 任务等级 / 发送模式：:80 在采播、文字语音、led 三类里都有这三项。
            ⚠ 终端功放**没有**这三项，也不能有 —— 功放列表的判据带着 `prepower = 0`，
              写了非 0 值任务会直接从列表里消失（服务端 normPerKind() 里有完整说明）。
          -->
          <template v-if="kind !== 'amplifier'">
            <el-col :span="8">
              <el-form-item label="预开电源">
                <el-input-number v-model="form.prepower" :min="0" :max="3600" controls-position="right" />
                <span class="tip">秒</span>
              </el-form-item>
            </el-col>
            <el-col :span="8">
              <el-form-item label="任务等级">
                <el-input-number v-model="form.priority" :min="1" :max="13" controls-position="right" />
              </el-form-item>
            </el-col>
            <el-col :span="8">
              <el-form-item label="发送模式">
                <el-radio-group v-model="form.datasendmodel">
                  <el-radio :label="0">单播</el-radio>
                  <el-radio :label="1">组播</el-radio>
                </el-radio-group>
              </el-form-item>
            </el-col>
          </template>

          <el-col :span="12">
            <el-form-item label="开始日期" required>
              <el-date-picker
                v-model="form.range[0]"
                type="date"
                value-format="YYYY-MM-DD"
                placeholder="请选择开始日期"
                :clearable="false"
                class="fill"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="结束日期" required>
              <el-date-picker
                v-model="form.range[1]"
                type="date"
                value-format="YYYY-MM-DD"
                placeholder="请选择结束日期"
                :clearable="false"
                class="fill"
              />
            </el-form-item>
          </el-col>

          <el-col :span="12">
            <el-form-item label="播放时间" required>
              <el-time-picker v-model="form.playtime" value-format="HH:mm:ss" :clearable="false" class="fill" />
            </el-form-item>
          </el-col>

          <el-col :span="24">
            <el-form-item label="执行模式">
              <el-checkbox-group v-model="form.weekdays">
                <el-checkbox v-for="(w, i) in WEEK" :key="i" :label="i">{{ w }}</el-checkbox>
              </el-checkbox-group>
            </el-form-item>
          </el-col>

          <!-- 功放专属 -->
          <template v-if="kind === 'amplifier'">
            <el-col :span="12">
              <el-form-item label="动作">
                <el-radio-group v-model="form.switch">
                  <el-radio :label="0">打开电源</el-radio>
                  <el-radio :label="1">关闭电源</el-radio>
                </el-radio-group>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="通道号">
                <el-input-number v-model="form.channel" :min="0" :max="255" controls-position="right" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="播放时长">
                <el-input-number v-model="form.durationSec" :min="0" :max="86399" controls-position="right" />
                <span class="tip">秒</span>
              </el-form-item>
            </el-col>
          </template>

          <!--
            文字语音专属：TTS采播终端。
            ⚠ 它和采播的「采播源终端」存在同一列 task.cmd 上。现网 tasktype 15/19
              的行 cmd = 2 / 12，都是 terminal.id。这一项以前界面上没有，
              保存时被写成 0，等于把「由哪台 TTS 主机合成」抹掉了（缺陷 N-19）。
          -->
          <template v-if="kind === 'tts'">
            <el-col :span="12">
              <el-form-item label="TTS采播终端">
                <TerminalTreeSelect
                  v-model="form.sourceTerminalId"
                  :terminals="terminals"
                  placeholder="选择 TTS 主机"
                  allow-empty
                />
              </el-form-item>
            </el-col>
          </template>

          <!-- 采播专属 -->
          <template v-if="kind === 'collect'">
            <el-col :span="12">
              <el-form-item label="采播源终端" required>
                <TerminalTreeSelect
                  v-model="form.sourceTerminalId"
                  :terminals="terminals"
                  placeholder="选择音源终端"
                />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="通道号">
                <el-input-number v-model="form.channel" :min="0" :max="255" controls-position="right" />
              </el-form-item>
            </el-col>
          </template>

          <!-- 播放时长（功放之外的三类） -->
          <template v-if="kind !== 'amplifier'">
            <el-col :span="12">
              <el-form-item label="播放模式">
                <el-radio-group v-model="form.timelengthtype">
                  <el-radio :label="1">普通模式</el-radio>
                  <el-radio :label="2">循环模式</el-radio>
                </el-radio-group>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item :label="form.timelengthtype === 1 ? '播放时长' : '播放次数'">
                <el-input-number v-model="form.timelength" :min="0" :max="86399" controls-position="right" />
                <span class="tip">{{ form.timelengthtype === 1 ? "秒" : "次" }}</span>
              </el-form-item>
            </el-col>
          </template>

          <el-col :span="24">
            <el-form-item label="方案状态">
              <el-radio-group v-model="form.projectstate">
                <el-radio :label="0">启用</el-radio>
                <el-radio :label="1">停用</el-radio>
              </el-radio-group>
            </el-form-item>
          </el-col>
        </el-row>
        </div>

        <div v-show="step === 1 && hasContentStep">
        <!-- ---------- 文字语音：播报文字 ---------- -->
        <template v-if="kind === 'tts'">
          <el-divider content-position="left">播报文字</el-divider>
          <div v-for="(s, i) in form.sentences" :key="i" class="sentence">
            <div class="sentence-head">
              <span>第 {{ i + 1 }} 段</span>
              <el-button link type="danger" :icon="Delete" @click="form.sentences.splice(i, 1)">删除</el-button>
            </div>
            <el-input v-model="s.content" type="textarea" :rows="3" maxlength="466" show-word-limit />
            <div class="sentence-opts">
              <span>语速</span>
              <el-input-number v-model="s.speed" :min="-50" :max="100" size="small" controls-position="right" />
              <span>音量</span>
              <el-input-number v-model="s.volume" :min="-100" :max="100" size="small" controls-position="right" />
              <span>语调</span>
              <el-input-number v-model="s.pitch" :min="0" :max="100" size="small" controls-position="right" />
              <span>发音人</span>
              <el-radio-group v-model="s.male" size="small">
                <el-radio-button :label="0">女声</el-radio-button>
                <el-radio-button :label="1">男声</el-radio-button>
              </el-radio-group>
            </div>
          </div>
          <el-button :icon="CirclePlus" size="small" @click="addSentence">增加一段</el-button>

          <!--
            :80 的文字语音表单里也有「led播放」。打开后会额外建一条 tasktype = 30 的
            LED 子任务（sec_task_id 指回本任务），除类型外整行照抄主任务，终端也一致。
            现网 70035（挂在 70033 上）就是这么来的。关掉即删掉那条子任务。
          -->
          <el-divider content-position="left">led播放</el-divider>
          <el-form-item label="led播放">
            <el-switch v-model="ledOn" />
          </el-form-item>
          <template v-if="ledOn">
            <el-form-item label="Led字幕" required>
              <el-input
                v-model="form.led.text"
                type="textarea"
                :rows="3"
                maxlength="341"
                show-word-limit
                placeholder="请输入Led字幕内容"
              />
            </el-form-item>
            <el-row :gutter="18">
              <el-col :span="12">
                <el-form-item label="Led速度">
                  <el-input-number v-model="form.led.speed" :min="0" :max="10" controls-position="right" />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="显示模式">
                  <el-input-number v-model="form.led.ledmode" :min="0" :max="10" controls-position="right" />
                </el-form-item>
              </el-col>
            </el-row>
          </template>
        </template>

        <!-- ---------- LED：显示内容 ---------- -->
        <template v-if="kind === 'led'">
          <el-divider content-position="left">LED 显示内容</el-divider>
          <el-form-item label="任务分组" required>
            <el-select v-model="form.folderId" class="fill" placeholder="选择分组">
              <el-option v-for="f in ledFolders" :key="f.id" :label="f.name" :value="f.id" />
            </el-select>
          </el-form-item>
          <!-- 名称照 :80：Led字幕 / Led速度 -->
          <el-form-item label="Led字幕" required>
            <el-input
              v-model="form.led.text"
              type="textarea"
              :rows="3"
              maxlength="341"
              show-word-limit
              placeholder="请输入Led字幕内容"
            />
          </el-form-item>
          <el-row :gutter="18">
            <el-col :span="12">
              <el-form-item label="Led速度">
                <el-input-number v-model="form.led.speed" :min="0" :max="10" controls-position="right" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="显示模式">
                <el-input-number v-model="form.led.ledmode" :min="0" :max="10" controls-position="right" />
              </el-form-item>
            </el-col>
          </el-row>
          <el-form-item label="LED 屏">
            <el-select v-model="selectedLedDevices" multiple filterable collapse-tags collapse-tags-tooltip class="fill">
              <el-option
                v-for="d in ledDevices"
                :key="d.id"
                :label="`${d.name}（${d.ip} ${d.width}×${d.height}）`"
                :value="d.id"
              />
            </el-select>
          </el-form-item>
        </template>
        </div>

        <!-- ---------- 终端 ---------- -->
        <div v-show="step === lastStep">
        <el-divider content-position="left">播放终端</el-divider>
        <el-form-item label="终端">
          <TerminalTree
            v-model="selectedTerminals"
            :terminals="terminals"
            :loading="terminalLoading"
            height="280px"
            @search="searchTerminals"
          />
        </el-form-item>
        </div>
      </el-form>

      <template #footer>
        <el-button @click="dlg.visible = false">取消</el-button>
        <el-button :disabled="step === 0" @click="prevStep">上一步</el-button>
        <el-button v-if="step < lastStep" type="primary" @click="nextStep">下一步</el-button>
        <el-button v-else type="primary" :loading="dlg.saving" @click="submit">确定</el-button>
      </template>
    </el-dialog>

    <!-- ============ LED 设备管理 ============ -->
    <el-dialog v-model="devDlg.visible" title="LED 设备" width="900px" top="6vh">
      <div class="dev-bar">
        <el-button type="primary" :icon="CirclePlus" size="small" @click="openDev()">新建 LED 设备</el-button>
        <el-button
          type="danger"
          :icon="Delete"
          size="small"
          :disabled="!devSel.length"
          @click="deleteDevices"
        >
          删除({{ devSel.length }})
        </el-button>
      </div>
      <el-table :data="ledDevices" size="small" max-height="360" row-key="id" @selection-change="r => (devSel = r.map((x: any) => x.id))">
        <el-table-column type="selection" width="46" />
        <el-table-column prop="id" label="编号" width="70" />
        <el-table-column prop="name" label="名称" min-width="130" />
        <el-table-column prop="ip" label="IP地址" width="140" />
        <el-table-column label="尺寸" width="110">
          <template #default="{ row }">{{ row.width }} × {{ row.height }}</template>
        </el-table-column>
        <el-table-column prop="terminalname" label="所属终端" min-width="140" show-overflow-tooltip />
        <el-table-column prop="sendport" label="端口" width="80" />
        <el-table-column label="操作" width="90">
          <template #default="{ row }">
            <el-button link type="primary" @click="openDev(row)">修改</el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-dialog v-model="devForm.visible" :title="devForm.id ? '修改 LED 设备' : '新建 LED 设备'" width="560px" append-to-body>
        <el-form :model="devForm" label-width="110px">
          <el-form-item label="名称" required>
            <el-input v-model="devForm.name" maxlength="21" show-word-limit />
          </el-form-item>
          <el-form-item label="IP地址" required>
            <el-input v-model="devForm.ip" placeholder="例如 192.168.2.90" />
          </el-form-item>
          <el-form-item label="所属终端" required>
            <TerminalTreeSelect v-model="devForm.terminalId" :terminals="terminals" />
          </el-form-item>
          <el-row :gutter="14">
            <el-col :span="12">
              <el-form-item label="屏宽">
                <el-input-number v-model="devForm.width" :min="1" :max="4096" controls-position="right" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="屏高">
                <el-input-number v-model="devForm.height" :min="1" :max="4096" controls-position="right" />
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="14">
            <el-col :span="12">
              <el-form-item label="设备号">
                <el-input-number v-model="devForm.devid" :min="0" controls-position="right" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="发送端口">
                <el-input-number v-model="devForm.sendport" :min="0" :max="65535" controls-position="right" />
              </el-form-item>
            </el-col>
          </el-row>
          <el-form-item label="默认显示">
            <el-input v-model="devForm.defaulttext" type="textarea" :rows="2" placeholder="选填" />
          </el-form-item>
        </el-form>
        <template #footer>
          <el-button @click="devForm.visible = false">取消</el-button>
          <el-button type="primary" :loading="devForm.saving" @click="submitDev">确定</el-button>
        </template>
      </el-dialog>

      <template #footer>
        <el-button @click="devDlg.visible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="tsx">
import { CirclePlus, Delete, EditPen, Setting } from "@element-plus/icons-vue";
import { ElMessage, ElMessageBox, ElNotification } from "element-plus";
import { computed, onMounted, reactive, ref, watch } from "vue";

import {
  controlTypedApi,
  createLedDeviceApi,
  createTypedApi,
  deleteLedDevicesApi,
  deleteTypedApi,
  getLedDevicesApi,
  getLedFoldersApi,
  getTypedApi,
  getTypedListApi,
  getTypedTerminalsApi,
  KIND_TITLE,
  setTypedStateApi,
  updateLedDeviceApi,
  updateTypedApi
} from "@/api/modules/ninemod";
import type { LedDevice, LedFolder, TypedKind, TypedTask, TypedTerminalOption } from "@/api/modules/ninemod";
import { setTaskVolumeApi } from "@/api/modules/task";
import ProTable from "@/components/ProTable/index.vue";
import TerminalTree from "@/components/TerminalTree/index.vue";
import TerminalTreeSelect from "@/components/TerminalTree/Select.vue";
import { useAuthStore } from "@/stores/modules/auth";
import type { ColumnProps, ProTableInstance } from "@/components/ProTable/interface";

const props = defineProps<{ kind: TypedKind }>();

const title = computed(() => KIND_TITLE[props.kind]);
const authStore = useAuthStore();
const canEdit = computed(() => !!(authStore.authButtonListGet as any)?.task?.edit);

const toIds = (raw: (string | number)[]) => (raw ?? []).map(Number).filter(n => Number.isFinite(n) && n > 0);

const WEEK = ["周一", "周二", "周三", "周四", "周五", "周六", "周日"];

const proTableRef = ref<ProTableInstance>();
const scopeNote = ref("");
const initParam = reactive({ orderBy: "", order: "", folderId: 0 });

const listApi = (params: any) => getTypedListApi(props.kind, params);

const dataCallback = (data: any) => {
  scopeNote.value = data.scopeNote ?? "";
  return { list: data.list, total: data.total, pageNum: data.pageNum, pageSize: data.pageSize };
};

// 对齐 :80 后这四页都没有可排序列，排序走后端默认（启用的排前面、再按执行时间）。
// initParam 留着是为了 LED 的分组筛选。


// 列清单对齐 :80（docs/image/oktw/页面规格.txt），四类各自的首列名字不同：
//   终端功放  终端功放任务名称 | 播放周期 | 开始日期 | 结束日期 | 执行时间 | 播放时长 | 状态 | 音量 | 操作
//   采播管理  采播任务 | 播放周期 | 播放时长 | 开始日期 | 结束日期 | 执行时间 | 状态 | …… | 音量 | 操作
//   文字语音  文字语音任务 | 播放周期 | 开始日期 | 结束日期 | 执行时间 | 播放时长 | 状态 | 音量 | 任务级别 | 任务状态
//   led播放   LED广播任务名称 | 同上
// 四页都**没有搜索区**。
// 「动作/通道」「采播源」「播报文字/显示文字」是各自类型的关键信息，:80 放在弹窗里，
// 我们留在列表上 —— 一眼看不出这条功放是开还是关，列表就没有意义。
// 列清单**严格照 :80**（docs/image/oktw/页面规格.txt），四类各不相同：
//
//   终端功放 终端功放任务名称|播放周期|开始日期|结束日期|执行时间|播放时长|状态|音量|操作
//   采播管理 采播任务|播放周期|播放时长|开始日期|结束日期|执行时间|状态|正在播放|通道|采样率|比特率|任务级别|音量|操作
//   文字语音 文字语音任务|播放周期|开始日期|结束日期|执行时间|播放时长|状态|音量|任务级别|任务状态|操作
//   led播放  LED广播任务名称|（同文字语音）
//
// 采播那几列在我们这儿基本是常量（采样率/比特率恒 0、任务级别恒 3），
// 但那是 :80 本来就有的列，按「严格一致」照列。
const COLS: Record<TypedKind, ColumnProps<TypedTask>[]> = {
  amplifier: [
    { prop: "taskName", label: "终端功放任务名称", minWidth: 200 },
    { prop: "cycleText", label: "播放周期", width: 110 },
    { prop: "startdate", label: "开始日期", width: 120 },
    { prop: "enddate", label: "结束日期", width: 120 },
    { prop: "playtime", label: "执行时间", width: 110 },
    { prop: "lengthText", label: "播放时长", width: 120 },
    { prop: "projectstate", label: "状态", width: 90 },
    { prop: "defaultvolume", label: "音量", width: 80 }
  ],
  collect: [
    { prop: "taskName", label: "采播任务", minWidth: 160 },
    { prop: "cycleText", label: "播放周期", width: 100 },
    { prop: "lengthText", label: "播放时长", width: 110 },
    { prop: "startdate", label: "开始日期", width: 110 },
    { prop: "enddate", label: "结束日期", width: 110 },
    { prop: "playtime", label: "执行时间", width: 100 },
    { prop: "projectstate", label: "状态", width: 80 },
    { prop: "playfileid", label: "正在播放", width: 100 },
    { prop: "cmdargs", label: "通道", width: 80 },
    { prop: "samplerate", label: "采样率", width: 90 },
    { prop: "bandrate", label: "比特率", width: 90 },
    { prop: "priority", label: "任务级别", width: 100 },
    { prop: "defaultvolume", label: "音量", width: 70 }
  ],
  tts: [
    { prop: "taskName", label: "文字语音任务", minWidth: 180 },
    { prop: "cycleText", label: "播放周期", width: 100 },
    { prop: "startdate", label: "开始日期", width: 110 },
    { prop: "enddate", label: "结束日期", width: 110 },
    { prop: "playtime", label: "执行时间", width: 100 },
    { prop: "lengthText", label: "播放时长", width: 110 },
    { prop: "projectstate", label: "状态", width: 80 },
    { prop: "defaultvolume", label: "音量", width: 70 },
    { prop: "priority", label: "任务级别", width: 100 },
    { prop: "state", label: "任务状态", width: 100 }
  ],
  led: [
    { prop: "taskName", label: "LED广播任务名称", minWidth: 190 },
    { prop: "cycleText", label: "播放周期", width: 100 },
    { prop: "startdate", label: "开始日期", width: 110 },
    { prop: "enddate", label: "结束日期", width: 110 },
    { prop: "playtime", label: "执行时间", width: 100 },
    { prop: "lengthText", label: "播放时长", width: 110 },
    { prop: "projectstate", label: "状态", width: 80 },
    { prop: "defaultvolume", label: "音量", width: 70 },
    { prop: "priority", label: "任务级别", width: 100 },
    { prop: "state", label: "任务状态", width: 100 }
  ]
};

const columns = computed<ColumnProps<TypedTask>[]>(() => [
  { type: "selection", fixed: "left", width: 50 },
  ...COLS[props.kind],
  { prop: "operation", label: "操作", fixed: "right", width: 130 }
]);

const refresh = () => proTableRef.value?.getTableList();

// 切换类别时（路由复用同一组件）把状态清干净
watch(
  () => props.kind,
  () => {
    initParam.folderId = 0;
    scopeNote.value = "";
    refresh();
  }
);

/* ---------------- 终端 / LED 资源 ---------------- */

const terminals = ref<TypedTerminalOption[]>([]);
const terminalLoading = ref(false);
const selectedTerminals = ref<number[]>([]);
const ledFolders = ref<LedFolder[]>([]);
const ledDevices = ref<LedDevice[]>([]);
const selectedLedDevices = ref<number[]>([]);

const searchTerminals = async (kw: string) => {
  terminalLoading.value = true;
  try {
    const { data } = await getTypedTerminalsApi(kw ?? "");
    terminals.value = data ?? [];
  } finally {
    terminalLoading.value = false;
  }
};

const loadLED = async () => {
  if (props.kind !== "led") return;
  const [f, d] = await Promise.all([getLedFoldersApi(), getLedDevicesApi()]);
  ledFolders.value = f.data ?? [];
  ledDevices.value = d.data ?? [];
};

/* ---------------- 新建 / 修改 ---------------- */

const blankForm = () => ({
  taskName: "",
  // 两个独立的日期选择器共用这个数组：[0] 开始日期、[1] 结束日期
  range: ["", ""] as string[],
  // 预开电源 / 任务等级 / 发送模式。功放不显示这三项，服务端也会强制归零。
  prepower: 0,
  priority: 3,
  datasendmodel: 0,
  playtime: "08:00:00",
  weekdays: [0, 1, 2, 3, 4, 5, 6] as number[],
  durationSec: 0,
  timelengthtype: 1,
  timelength: 60,
  defaultvolume: 80,
  projectstate: 0,
  folderId: 0,
  switch: 0,
  channel: 0,
  sourceTerminalId: 0,
  sentences: [] as { content: string; speed: number; volume: number; male: number; pitch: number }[],
  led: { text: "", speed: 1, ledmode: 0 }
});

const form = reactive(blankForm());

/*
  分步向导。
  · 功放没有「播放内容」那一步（既没有播报文字也没有 LED 字幕），只有两步。
  · 采播同理 —— 它的音源是终端，不是文字内容。
  所以中间那一步只对 tts / led 存在，步号用 lastStep 统一表达。
*/
const step = ref(0);
const hasContentStep = computed(() => props.kind === "tts" || props.kind === "led");
const lastStep = computed(() => (hasContentStep.value ? 2 : 1));
const nextStep = () => {
  step.value = step.value === 0 && !hasContentStep.value ? lastStep.value : step.value + 1;
};
const prevStep = () => {
  step.value = step.value === lastStep.value && !hasContentStep.value ? 0 : step.value - 1;
};

/**
 * led播放 开关。
 * · kind = 'led'：这一页本身就是 LED 任务，恒为 true，不显示开关。
 * · kind = 'tts'：可选，关掉时提交 led: null，后端会删掉已有的 LED 子任务。
 */
const ledOn = ref(false);
const dlg = reactive({ visible: false, saving: false, isEdit: false, title: "", id: 0 });

const addSentence = () => form.sentences.push({ content: "", speed: 50, volume: 80, male: 0, pitch: 50 });

const resetForm = () => Object.assign(form, blankForm());

const openCreate = async () => {
  resetForm();
  const today = new Date().toISOString().slice(0, 10);
  form.range = [today, today];
  if (props.kind === "tts") addSentence();
  if (props.kind === "led") {
    await loadLED();
    form.folderId = ledFolders.value[0]?.id ?? 0;
  }
  // 新建时 led播放 默认关闭；led 页本身恒为「开」，但那一页不显示这个开关
  ledOn.value = props.kind === "led";
  selectedTerminals.value = [];
  selectedLedDevices.value = [];
  step.value = 0;
  Object.assign(dlg, { visible: true, saving: false, isEdit: false, title: "添加", id: 0 });
  await searchTerminals("");
};

const openEdit = async (row: TypedTask) => {
  const { data } = await getTypedApi(props.kind, row.taskId);
  resetForm();
  step.value = 0;
  form.taskName = data.taskName;
  form.range = [data.startdate, data.enddate];
  form.playtime = data.playtime;
  form.weekdays = (data.exemodel || "").split("").reduce<number[]>((acc, c, i) => (c === "1" ? [...acc, i] : acc), []);
  form.timelengthtype = data.timelengthtype || 1;
  form.timelength = data.timelength;
  form.defaultvolume = data.defaultvolume;
  form.projectstate = data.projectstate;
  form.prepower = data.prepower ?? 0;
  form.priority = data.priority || 3;
  form.datasendmodel = data.datasendmodel ?? 0;
  form.folderId = data.parentid;
  form.channel = Number(data.cmdargs) || 0;
  if (props.kind === "amplifier") {
    form.switch = Number(data.cmd) === 1 ? 1 : 0;
    // 功放的持续时长在库里体现为 endtime - playtime，回填时反算出来
    form.durationSec = diffSec(data.playtime, data.endtime);
  }
  // 采播与文字语音的 cmd 都是终端 id，回填方式相同
  if (props.kind === "collect" || props.kind === "tts") form.sourceTerminalId = Number(data.cmd) || 0;
  if (props.kind === "tts") {
    form.sentences = (data.sentences ?? []).map(s => ({
      content: s.content,
      speed: s.speed,
      volume: s.volume,
      male: s.male,
      pitch: s.pitch
    }));
    if (!form.sentences.length) addSentence();
  }
  if (props.kind === "led") {
    await loadLED();
  }
  // led 页与 tts 页都可能带回 led 段：前者是任务本体，后者是挂上去的字幕子任务
  if (props.kind === "led" || props.kind === "tts") {
    form.led = { text: data.led?.text ?? "", speed: data.led?.speed ?? 1, ledmode: data.led?.ledmode ?? 0 };
    selectedLedDevices.value = (data.led?.devices ?? []).filter(d => !d.deleted).map(d => d.deviceId);
    ledOn.value = props.kind === "led" || !!data.led;
  }
  // 已删除的终端不回填，否则保存时会被服务端的存在性校验挡下来
  selectedTerminals.value = data.terminals.filter(t => !t.deleted).map(t => t.terminalId);
  const dropped = data.terminals.filter(t => t.deleted).length;
  Object.assign(dlg, {
    visible: true,
    saving: false,
    isEdit: true,
    title: `修改${title.value}：${data.taskName}`,
    id: data.taskId
  });
  await searchTerminals("");
  if (dropped) ElMessage.warning(`该任务里有 ${dropped} 台终端已被删除，已自动移除`);
};

// diffSec 算两个 HH:mm:ss 之间的秒差，跨零点按当天回绕处理（与后端 endTimeOf 对称）
const diffSec = (from: string, to: string) => {
  const p = (s: string) => {
    const [h, m, sec] = (s || "00:00:00").split(":").map(Number);
    return (h || 0) * 3600 + (m || 0) * 60 + (sec || 0);
  };
  const d = p(to) - p(from);
  return d < 0 ? d + 86400 : d;
};

const buildBody = () => {
  const mask = Array.from({ length: 7 }, (_, i) => (form.weekdays.includes(i) ? "1" : "0")).join("");
  const ledDevs = selectedLedDevices.value.map(id => {
    const d = ledDevices.value.find(x => x.id === id);
    return { terminalId: d?.terminalId ?? 0, deviceId: id };
  });
  return {
    taskName: form.taskName.trim(),
    startdate: form.range[0],
    enddate: form.range[1],
    playtime: form.playtime,
    durationSec: form.durationSec,
    timelengthtype: form.timelengthtype,
    timelength: form.timelength,
    exemodel: mask,
    defaultvolume: form.defaultvolume,
    projectstate: form.projectstate,
    // 功放这三项服务端会强制归零，这里照发不误，省得再分支
    prepower: form.prepower,
    priority: form.priority,
    datasendmodel: form.datasendmodel,
    folderId: props.kind === "led" ? form.folderId : 0,
    switch: form.switch,
    channel: form.channel,
    sourceTerminalId: form.sourceTerminalId,
    terminals: selectedTerminals.value.map(id => ({
      terminalId: id,
      area: "11111111",
      groupId: terminals.value.find(t => t.id === id)?.groupId ?? 0
    })),
    sentences: props.kind === "tts" ? form.sentences.map(s => ({ ...s, content: s.content.trim() })) : [],
    // led 页：这条任务本身就是 LED 任务，内容必填。
    // tts 页：可选的 LED 字幕子任务，关掉就传 null（后端据此删掉已有的子任务）。
    led:
      props.kind === "led" || (props.kind === "tts" && ledOn.value)
        ? { ...form.led, devices: ledDevs }
        : null
  };
};

const submit = async () => {
  // 校验不通过时把步骤切回出问题的那一步，否则用户看不见是哪一项没填
  if (!form.taskName.trim()) {
    step.value = 0;
    return ElMessage.warning("请输入任务名");
  }
  if (!form.range?.[0]) {
    step.value = 0;
    return ElMessage.warning("请选择开始日期");
  }
  if (!form.range?.[1]) {
    step.value = 0;
    return ElMessage.warning("请选择结束日期");
  }
  if (props.kind === "tts" && ledOn.value && !form.led.text.trim()) {
    step.value = 1;
    return ElMessage.warning("请输入Led字幕内容");
  }
  dlg.saving = true;
  try {
    const body = buildBody();
    if (dlg.isEdit) await updateTypedApi(props.kind, dlg.id, body);
    else await createTypedApi(props.kind, body);
    ElMessage.success("保存成功");
    dlg.visible = false;
    refresh();
  } finally {
    dlg.saving = false;
  }
};

/* ---------------- 启停 / 状态 / 删除 ---------------- */

const reportBlocked = (blocked: any[], okCount: number, what: string) => {
  if (!blocked?.length) {
    ElMessage.success(`${what}成功（${okCount} 条）`);
    return;
  }
  ElNotification({
    title: `${what}：${okCount} 条成功，${blocked.length} 条被拦下`,
    message: blocked.map(b => `· ${b.name || b.id}：${b.detail}`).join("\n"),
    type: okCount ? "warning" : "error",
    duration: 8000,
    customClass: "pre-line"
  });
};

const doControl = async (action: "start" | "stop", raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning("请先勾选任务");
  const { data } = await controlTypedApi(props.kind, action, ids);
  reportBlocked(data.blocked, data.succeeded.length, action === "start" ? "启动" : "停止");
  refresh();
};

const doState = async (raw: (string | number)[], enable: boolean) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning("请先勾选任务");
  const { data } = await setTypedStateApi(props.kind, ids, enable);
  reportBlocked(data.blocked, data.succeeded.length, enable ? "启用方案" : "停用方案");
  refresh();
};

/* 设置音量：这四类任务和文件广播同在 task 表，直接用 /api/tasks/volume */
const vol = reactive({ visible: false, saving: false, ids: [] as number[], value: 80 });

const openVolume = (raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning("请先勾选任务");
  vol.ids = ids;
  vol.value = 80;
  vol.visible = true;
};

const submitVolume = async () => {
  vol.saving = true;
  try {
    const { data } = await setTaskVolumeApi(vol.ids, vol.value);
    vol.visible = false;
    reportBlocked(data.blocked, data.succeeded.length, "设置音量");
    refresh();
  } finally {
    vol.saving = false;
  }
};

const doDelete = async (raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning("请先勾选任务");
  await ElMessageBox.confirm(
    `确认删除选中的 ${ids.length} 条${title.value}任务？会连带清掉终端绑定、遥控绑定与离线副本，不可恢复。`,
    "删除任务",
    { type: "warning", confirmButtonText: "确认删除" }
  );
  const { data } = await deleteTypedApi(props.kind, ids);
  reportBlocked(data.blocked, data.deleted.length, "删除");
  refresh();
};

/* ---------------- LED 设备 ---------------- */

const devDlg = reactive({ visible: false });
const devSel = ref<number[]>([]);
const devForm = reactive({
  visible: false,
  saving: false,
  id: 0,
  name: "",
  ip: "",
  terminalId: 0,
  devid: 0,
  width: 64,
  height: 32,
  sendport: 0,
  mac: "",
  defaulttext: ""
});

const openDev = (row?: LedDevice) => {
  Object.assign(devForm, {
    visible: true,
    saving: false,
    id: row?.id ?? 0,
    name: row?.name ?? "",
    ip: row?.ip ?? "",
    terminalId: row?.terminalId ?? 0,
    devid: row?.devid ?? 0,
    width: row?.width ?? 64,
    height: row?.height ?? 32,
    sendport: row?.sendport ?? 0,
    mac: row?.mac ?? "",
    defaulttext: row?.defaulttext ?? ""
  });
};

const submitDev = async () => {
  devForm.saving = true;
  try {
    const body = {
      name: devForm.name.trim(),
      ip: devForm.ip.trim(),
      terminalId: devForm.terminalId,
      devid: devForm.devid,
      width: devForm.width,
      height: devForm.height,
      sendport: devForm.sendport,
      mac: devForm.mac.trim(),
      defaulttext: devForm.defaulttext.trim()
    };
    if (devForm.id) await updateLedDeviceApi(devForm.id, body);
    else await createLedDeviceApi(body);
    ElMessage.success("保存成功");
    devForm.visible = false;
    await loadLED();
  } finally {
    devForm.saving = false;
  }
};

const deleteDevices = async () => {
  await ElMessageBox.confirm(
    `确认删除选中的 ${devSel.value.length} 块 LED 屏？会连带清掉它们在任务里的绑定。`,
    "删除 LED 设备",
    { type: "warning", confirmButtonText: "确认删除" }
  );
  const { data } = await deleteLedDevicesApi(devSel.value);
  ElMessage.success(`已删除 ${data.deleted} 块`);
  devSel.value = [];
  await loadLED();
};

onMounted(async () => {
  await searchTerminals("");
  await loadLED();
});
</script>

<style scoped lang="scss">
.folder-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: center;
  padding: 10px 14px;
  margin-bottom: 10px;
  background: var(--el-bg-color);
  border-radius: 6px;
}
.folder-label {
  font-size: 13px;
  color: var(--el-text-color-secondary);
}
.grow {
  flex: 1;
}
.header-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
  justify-content: space-between;
  width: 100%;
}
.header-left,
.header-right {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}
.warn {
  color: var(--el-color-warning);
}
.ellipsis {
  display: inline-block;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.tip {
  margin-left: 8px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
  &.block {
    display: block;
    margin-left: 0;
    line-height: 1.7;
  }
}
.opt-sub {
  margin-left: 10px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.mb12 {
  margin-bottom: 12px;
}
.fill {
  width: 100%;
}
.sentence {
  padding: 10px 12px;
  margin-bottom: 10px;
  border: 1px solid var(--el-border-color-light);
  border-radius: 6px;
}
.sentence-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 6px;
  font-size: 13px;
  color: var(--el-text-color-regular);
}
.sentence-opts {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  margin-top: 8px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.dev-bar {
  display: flex;
  gap: 8px;
  margin-bottom: 10px;
}
code {
  padding: 0 4px;
  font-size: 12px;
  background: var(--el-fill-color-light);
  border-radius: 3px;
}
</style>
