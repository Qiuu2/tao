<!--
  终端功放 / 采播管理 / 文字语音 / led播放 共用的页面骨架

  这四个页面在旧版是四个各写一遍的 php：
    终端功放  terminalfunctionplay.php + terminalfunctionplayadd/modify.php
    采播管理  admmanager.php            + addadmtask.php / admmodify.php
    文字语音  displayttsmanager.php     + taskttsadd.php / taskttsmodify.php
    led播放   ledtaskmanager.php        + ledtaskadd.php / ledtaskmodify.php
  四份几乎一样的列表 + 四份分别改坏过的搜索分支。它们做的是同一件事：
  按 tasktype 从 task 表里捞任务。所以这里做成一个组件，
  差异（列、工具栏、表单里那几段）全部由 kind 决定。

  表单的分栏与字段顺序**照 ok112 的那四张表单**：
    任务属性  →  执行时间  →  [音频设置 / 文字语音内容 / led字幕+led设备列表]  →  终端列表
  旧版是一页到底的表单加一个「提交」按钮，不是分步向导。

  ⚠ 三个和直觉相反的地方，改这个文件之前先看一眼：
    · projectstate  0 = 启用、1 = 停用（数据库列注释是反的）
    · 功放/采播任务**没有媒体**，启动前不检查媒体 —— 它们是「开电源」「转采播源」指令
    · 文字语音的 media 行是合成出来的（typeid='tts'），磁盘上没有对应文件
-->
<template>
  <div class="table-box">
    <!-- led播放 多一层任务目录，工具栏上还有创建/修改/删除/复制目录 -->
    <div v-if="kind === 'led'" class="folder-bar">
      <span class="folder-label">任务目录</span>
      <el-radio-group v-model="initParam.folderId" size="small" @change="refresh">
        <el-radio-button :label="0">全部</el-radio-button>
        <el-radio-button v-for="f in ledFolders" :key="f.id" :label="f.id"> {{ f.name }}（{{ f.taskCount }}） </el-radio-button>
      </el-radio-group>
      <div class="grow"></div>
      <el-button size="small" :icon="FolderAdd" :disabled="!canEdit" @click="openFolderCreate">创建目录</el-button>
      <el-button size="small" :icon="EditPen" :disabled="!canEdit || !initParam.folderId" @click="openFolderRename">
        修改目录
      </el-button>
      <el-button size="small" :icon="Delete" :disabled="!canEdit || !initParam.folderId" @click="doFolderDelete">
        删除目录
      </el-button>
      <el-button size="small" :icon="CopyDocument" :disabled="!canEdit || ledFolders.length < 2" @click="openFolderCopy">
        复制目录
      </el-button>
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
        工具栏照 ok112 的那四个列表页（全选/取消由 ProTable 的复选框代劳）：
          终端功放  执行 停止 添加 修改 删除 任务音量
          采播管理  执行 停止 添加 修改 删除 启用 停用 音量
          文字语音  执行 停止 添加 修改 删除 启用 停用 音量
          led播放   执行 停止 暂停 恢复 添加 修改 删除 紧急设置 紧急取消 启用 停用 调整音量
        「修改」旧版是「选中一条再点」，这里保留成行内按钮，语义一致但更省一步。
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
            <template v-if="kind === 'led'">
              <el-button :disabled="!canEdit || !scope.isSelected" @click="doControl('pause', scope.selectedListIds)">
                暂停
              </el-button>
              <el-button :disabled="!canEdit || !scope.isSelected" @click="doControl('resume', scope.selectedListIds)">
                恢复
              </el-button>
            </template>
            <template v-if="kind !== 'amplifier'">
              <el-button :disabled="!canEdit || !scope.isSelected" @click="doState(scope.selectedListIds, true)">
                启用
              </el-button>
              <el-button :disabled="!canEdit || !scope.isSelected" @click="doState(scope.selectedListIds, false)">
                停用
              </el-button>
            </template>
            <el-button :disabled="!canEdit || !scope.isSelected" @click="openVolume(scope.selectedListIds)">
              {{ kind === "led" ? "调整音量" : "任务音量" }}
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

      <!-- 旧版的「终端属性 → 浏览终端」 -->
      <template #terminals="s">
        <el-button link type="primary" @click="openTerminals(s.row)">终端({{ s.row.terminalCount }})</el-button>
      </template>

      <template #operation="s">
        <el-button type="primary" link :icon="EditPen" :disabled="!canEdit || !s.row.canModify" @click="openEdit(s.row)">
          修改
        </el-button>
        <el-button type="danger" link :icon="Delete" :disabled="!canEdit || !s.row.canModify" @click="doDelete([s.row.taskId])">
          删除
        </el-button>
      </template>
    </ProTable>

    <!-- 任务音量：这四类任务和文件广播同在 task 表里，直接用 /api/tasks/volume -->
    <el-dialog v-model="vol.visible" title="任务音量" width="440px">
      <el-slider v-model="vol.value" :min="0" :max="100" show-input />
      <template #footer>
        <el-button @click="vol.visible = false">取消</el-button>
        <el-button type="primary" :loading="vol.saving" @click="submitVolume">确定</el-button>
      </template>
    </el-dialog>

    <!-- 浏览终端：旧版 displayterminal.php 那张表 -->
    <el-dialog v-model="termDlg.visible" :title="termDlg.title" width="820px" top="8vh">
      <el-table :data="termDlg.list" size="small" max-height="420">
        <el-table-column prop="terminalname" label="终端名称" min-width="160" show-overflow-tooltip />
        <el-table-column prop="typeName" label="终端类型" width="140" show-overflow-tooltip />
        <el-table-column label="网络状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.netstate === 1 ? 'success' : 'info'" size="small">
              {{ row.netstate === 1 ? "在线" : "离线" }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="设备状态" width="100">
          <template #default="{ row }">{{ row.devicestate === 1 ? "正常" : "空闲" }}</template>
        </el-table-column>
        <el-table-column prop="ip" label="终端IP" width="150" />
        <el-table-column prop="volume" label="音量" width="80" />
      </el-table>
      <template #footer>
        <el-button @click="termDlg.visible = false">关闭</el-button>
      </template>
    </el-dialog>

    <!-- ============ 添加 / 修改 ============ -->
    <el-dialog v-model="dlg.visible" :title="dlg.title" width="900px" top="4vh">
      <el-form :model="form" label-width="110px">
        <!-- ---------- 任务属性 ---------- -->
        <el-divider content-position="left">任务属性</el-divider>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="任务名称" required>
              <el-input v-model="form.taskName" maxlength="60" show-word-limit placeholder="请输入任务名称" />
              <div v-if="err.taskName" class="err">{{ err.taskName }}</div>
            </el-form-item>
          </el-col>

          <!--
            预开电源 / 任务级别 / 发送模式：ok112 在采播、文字语音、led 三张表单里都有。
            ⚠ 终端功放**没有**预开电源，也不能有 —— 功放列表的判据带着 `prepower = 0`，
              写了非 0 值任务会直接从列表里消失（服务端 normPerKind() 里有完整说明）。
          -->
          <el-col v-if="kind !== 'amplifier'" :span="12">
            <el-form-item label="预开电源">
              <el-select v-model="form.prepower" class="fill">
                <el-option v-for="o in prepowerOptions" :key="o.value" :label="o.label" :value="o.value" />
              </el-select>
            </el-form-item>
          </el-col>

          <el-col :span="12">
            <el-form-item label="任务级别">
              <el-select v-model="form.priority" class="fill">
                <el-option v-for="p in priorityOptions" :key="p" :label="String(p)" :value="p" />
              </el-select>
            </el-form-item>
          </el-col>

          <el-col v-if="kind !== 'amplifier'" :span="12">
            <el-form-item label="发送模式">
              <el-select v-model="form.datasendmodel" class="fill">
                <el-option label="单 播" :value="0" />
                <el-option label="组 播" :value="1" />
              </el-select>
            </el-form-item>
          </el-col>

          <!-- 文字语音专属：声音模式 / 播放速率 / tts终端 / 提示音 -->
          <template v-if="kind === 'tts'">
            <el-col :span="12">
              <el-form-item label="声音模式">
                <el-select v-model="form.musicmode" class="fill">
                  <el-option label="女声" :value="0" />
                  <el-option label="男声" :value="1" />
                  <el-option label="英语男声" :value="2" />
                  <el-option label="英语女声" :value="3" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="播放速率">
                <el-slider v-model="form.ttsSpeed" :min="0" :max="100" show-input size="small" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="tts终端">
                <el-select v-model="form.sourceTerminalId" class="fill" placeholder="请选择tts终端" @change="onSourceChange">
                  <el-option :label="'请选择tts终端'" :value="0" />
                  <el-option v-for="t in sourceTerminals" :key="t.id" :label="t.terminalname" :value="t.id" />
                </el-select>
              </el-form-item>
            </el-col>
            <!-- 旧版只有在 tts终端 选到服务器本机（typeid = 0）时才出现提示音 -->
            <el-col v-if="sourceIsServer" :span="12">
              <el-form-item label="提示音">
                <el-select v-model="form.promptId" class="fill" placeholder="请选择提示音">
                  <el-option label="请选择提示音" :value="0" />
                  <el-option v-for="m in promptList" :key="m.id" :label="m.name" :value="m.id" />
                </el-select>
              </el-form-item>
            </el-col>
          </template>

          <!-- 采播专属：采播终端在「音频设置」里，这里只有播放时长 -->

          <!-- 播放模式：文字语音与 led播放 有「普通模式 / 间隔时间」 -->
          <el-col v-if="hasIntervalMode" :span="12">
            <el-form-item label="播放模式">
              <el-select v-model="form.intervalMode" class="fill" @change="onIntervalModeChange">
                <el-option label="普通模式" :value="0" />
                <el-option label="间隔时间" :value="1" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <!-- 普通模式：循环次数 + 播放时长；间隔模式：间隔时长 + 间隔时长/每次循环次数 -->
        <template v-if="!hasIntervalMode || form.intervalMode === 0">
          <el-form-item v-if="hasCycleTimes" label="循环次数">
            <el-input-number v-model="form.timelength" :min="0" :max="10" controls-position="right" />
            <span class="tip">0 是无限循环，最大 10 次</span>
          </el-form-item>
          <el-form-item label="播放时长">
            <HmsInput v-model="form.durationSec" />
          </el-form-item>
        </template>
        <template v-else>
          <el-form-item label="间隔时长">
            <HmsInput v-model="form.intervalS" />
          </el-form-item>
          <el-form-item label="间隔方式">
            <el-radio-group v-model="form.intPlayLenTy" class="len-group">
              <div class="len-line">
                <el-radio :value="1">间隔时长</el-radio>
                <HmsInput v-model="form.intPlayLenSec" :disabled="form.intPlayLenTy !== 1" />
              </div>
              <div class="len-line">
                <el-radio :value="2">每次循环次数</el-radio>
                <el-input-number
                  :key="`cnt-${form.intPlayLenTy}`"
                  v-model="form.intPlayLenCount"
                  :min="1"
                  :max="999"
                  :disabled="form.intPlayLenTy !== 2"
                  controls-position="right"
                />
              </div>
            </el-radio-group>
          </el-form-item>
        </template>

        <el-form-item label="任务音量">
          <el-slider v-model="form.defaultvolume" :min="0" :max="100" show-input size="small" />
        </el-form-item>

        <!-- ---------- 执行时间 ---------- -->
        <el-divider content-position="left">执行时间</el-divider>
        <el-row :gutter="16">
          <el-col :span="8">
            <el-form-item label="播放时间" required>
              <el-time-picker v-model="form.playtime" value-format="HH:mm:ss" :clearable="false" class="fill" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="开始日期" required>
              <el-date-picker
                v-model="form.range[0]"
                type="date"
                value-format="YYYY-MM-DD"
                placeholder="请选择开始日期"
                :clearable="false"
                class="fill"
              />
              <div v-if="err.startdate" class="err">{{ err.startdate }}</div>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="结束日期" required>
              <el-date-picker
                v-model="form.range[1]"
                type="date"
                value-format="YYYY-MM-DD"
                placeholder="请选择结束日期"
                :clearable="false"
                class="fill"
              />
              <div v-if="err.enddate" class="err">{{ err.enddate }}</div>
            </el-form-item>
          </el-col>
        </el-row>
        <!-- 执行模式：每天 / 每星期 / 手动，选「每星期」才出现星期复选框（照旧版 displayweek） -->
        <el-form-item label="执行模式">
          <el-select v-model="form.runMode" style="width: 140px" @change="onRunModeChange">
            <el-option label="每天" :value="1" />
            <el-option label="每星期" :value="2" />
            <el-option label="手动" :value="3" />
          </el-select>
          <el-checkbox-group v-if="form.runMode === 2" v-model="form.weekdays" class="wk-group">
            <el-checkbox v-for="(w, i) in WEEK" :key="i" :value="i">{{ w }}</el-checkbox>
          </el-checkbox-group>
        </el-form-item>

        <!-- ---------- 音频设置（采播管理） ---------- -->
        <template v-if="kind === 'collect'">
          <el-divider content-position="left">音频设置</el-divider>
          <el-row :gutter="16">
            <el-col :span="12">
              <el-form-item label="采播终端" required>
                <el-select v-model="form.sourceTerminalId" class="fill" placeholder="请选择采播终端" @change="onSourceChange">
                  <el-option :label="'请选择采播终端'" :value="0" />
                  <el-option v-for="t in sourceTerminals" :key="t.id" :label="t.terminalname" :value="t.id" />
                </el-select>
                <div v-if="err.source" class="err">{{ err.source }}</div>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="采样率">
                <el-select v-model="form.samplerate" class="fill">
                  <el-option v-for="r in SAMPLE_RATES" :key="r" :label="`${r}Hz`" :value="r" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="终端通道">
                <!-- 选项条数 = 所选采播终端型号的 switchcount，没选采播终端时是空的（同旧版） -->
                <el-select
                  v-model="form.channel"
                  class="fill"
                  :placeholder="form.sourceTerminalId ? '请选择终端通道' : '请先选择采播终端'"
                >
                  <el-option v-for="c in channelOptions" :key="c.value" :label="c.label" :value="c.value" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="比特率">
                <el-select v-model="form.bandrate" class="fill">
                  <el-option v-for="b in BAND_RATES" :key="b" :label="`${b}Kbp/s`" :value="b" />
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>
        </template>

        <!-- ---------- 文字语音内容 ---------- -->
        <template v-if="kind === 'tts'">
          <el-divider content-position="left">文字语音内容</el-divider>
          <el-form-item label-width="0" required>
            <el-input
              v-model="form.text"
              type="textarea"
              :rows="8"
              maxlength="2000"
              show-word-limit
              placeholder="请输入播放文字"
            />
            <div v-if="err.text" class="err">{{ err.text }}</div>
          </el-form-item>
        </template>

        <!-- ---------- led字幕 + led设备列表 ---------- -->
        <template v-if="kind === 'led'">
          <el-divider content-position="left">led字幕</el-divider>
          <el-row :gutter="16">
            <el-col :span="12">
              <el-form-item label="任务目录" required>
                <el-select v-model="form.folderId" class="fill" placeholder="选择任务目录">
                  <el-option v-for="f in ledFolders" :key="f.id" :label="f.name" :value="f.id" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Led速度">
                <el-select v-model="form.led.speed" style="width: 110px">
                  <el-option v-for="n in [0, 1, 2, 3, 4, 5]" :key="n" :label="`${n} 级`" :value="n" />
                </el-select>
                <span class="tip">0 ~ 5 级</span>
              </el-form-item>
            </el-col>
          </el-row>
          <el-form-item label-width="0" required>
            <el-input
              v-model="form.led.text"
              type="textarea"
              :rows="8"
              maxlength="2000"
              show-word-limit
              placeholder="请输入Led字幕内容"
            />
            <div v-if="err.ledText" class="err">{{ err.ledText }}</div>
          </el-form-item>

          <el-divider content-position="left">led设备列表</el-divider>
          <el-form-item label-width="0">
            <div class="led-dev">
              <div v-if="!ledDevices.length" class="dlg-note">
                还没有登记 LED 设备 —— 先点上面的「LED 设备」登记，这里才能勾选要上屏的设备。
              </div>
              <el-checkbox-group v-else v-model="selectedLedDevices">
                <el-checkbox v-for="d in ledDevices" :key="d.id" :value="d.id">
                  {{ d.name }}
                  <span class="opt-sub">{{ d.terminalname || `终端 ${d.terminalId}` }} · {{ d.ip }}</span>
                </el-checkbox>
              </el-checkbox-group>
            </div>
          </el-form-item>
        </template>

        <!-- ---------- 终端列表 ---------- -->
        <el-divider content-position="left">终端列表</el-divider>
        <el-form-item label-width="0">
          <TerminalTree
            v-model="selectedTerminals"
            v-model:areas="terminalAreas"
            :terminals="terminals"
            :loading="terminalLoading"
            height="280px"
            style="width: 100%"
            @search="searchTerminals"
          />
          <div v-if="err.terminals" class="err">{{ err.terminals }}</div>
        </el-form-item>
      </el-form>

      <!-- 旧版这几张表单底下只有「提交」（终端功放还多一个「取消」） -->
      <template #footer>
        <el-button @click="dlg.visible = false">取消</el-button>
        <el-button type="primary" :loading="dlg.saving" @click="submit">提交</el-button>
      </template>
    </el-dialog>

    <!-- ============ LED 目录：创建 / 修改 / 复制 ============ -->
    <el-dialog v-model="folderDlg.visible" :title="folderDlg.title" width="420px">
      <el-form label-width="90px">
        <el-form-item label="目录名称" required>
          <el-input v-model="folderDlg.name" maxlength="60" show-word-limit placeholder="请输入目录名称" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="folderDlg.visible = false">取消</el-button>
        <el-button type="primary" :loading="folderDlg.saving" @click="submitFolder">确定</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="copyDlg.visible" title="复制目录" width="460px">
      <el-form label-width="90px">
        <el-form-item label="源目录" required>
          <el-select v-model="copyDlg.fromId" class="fill">
            <el-option v-for="f in ledFolders" :key="f.id" :label="`${f.name}（${f.taskCount}）`" :value="f.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="目标目录" required>
          <el-select v-model="copyDlg.toId" class="fill">
            <el-option v-for="f in ledFolders" :key="f.id" :label="`${f.name}（${f.taskCount}）`" :value="f.id" />
          </el-select>
        </el-form-item>
      </el-form>
      <div class="dlg-note">源目录里的任务会整条复制到目标目录，任务名后面补「-副本」。</div>
      <template #footer>
        <el-button @click="copyDlg.visible = false">取消</el-button>
        <el-button type="primary" :loading="copyDlg.saving" @click="submitCopy">确定</el-button>
      </template>
    </el-dialog>

    <!-- ============ LED 设备管理 ============ -->
    <el-dialog v-model="devDlg.visible" title="LED 设备" width="900px" top="6vh">
      <div class="dev-bar">
        <el-button type="primary" :icon="CirclePlus" size="small" @click="openDev()">新建 LED 设备</el-button>
        <el-button type="danger" :icon="Delete" size="small" :disabled="!devSel.length" @click="deleteDevices">
          删除({{ devSel.length }})
        </el-button>
      </div>
      <el-table
        :data="ledDevices"
        size="small"
        max-height="360"
        row-key="id"
        @selection-change="r => (devSel = r.map((x: any) => x.id))"
      >
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
import { CirclePlus, CopyDocument, Delete, EditPen, FolderAdd, Setting } from "@element-plus/icons-vue";
import { ElMessage, ElMessageBox, ElNotification } from "element-plus";
import { computed, onMounted, reactive, ref, watch } from "vue";

import {
  controlTypedApi,
  copyLedFolderApi,
  createLedDeviceApi,
  createLedFolderApi,
  createTypedApi,
  deleteLedDevicesApi,
  deleteLedFolderApi,
  deleteTypedApi,
  getLedDevicesApi,
  getLedFoldersApi,
  getPromptMediaApi,
  getTypedApi,
  getTypedListApi,
  getTypedSourcesApi,
  getTypedTerminalsApi,
  KIND_TITLE,
  renameLedFolderApi,
  setTypedStateApi,
  updateLedDeviceApi,
  updateTypedApi
} from "@/api/modules/ninemod";
import type {
  LedDevice,
  LedFolder,
  PromptMedia,
  TypedAction,
  TypedKind,
  TypedTask,
  TypedTerminal,
  TypedTerminalOption
} from "@/api/modules/ninemod";
import { getTaskPriorityRangeApi, setTaskVolumeApi } from "@/api/modules/task";
import HmsInput from "@/components/HmsInput/index.vue";
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

// exemodel 是周日打头的 7 位掩码，标签顺序要跟位次一致
const WEEK = ["周日", "周一", "周二", "周三", "周四", "周五", "周六"];

// 采样率 / 比特率的取值照 ok112 的 AddAdmManger.html 那两个下拉
const SAMPLE_RATES = [8000, 11025, 16000, 44100, 48000, 64000, 88200, 96000, 128000, 256000, 320000];
const BAND_RATES = [8, 16, 32, 64, 128];

// 预开电源：ok112 是 0~55 秒每 5 秒一档，再加 1~5 分钟，默认 2 分钟
const prepowerOptions = (() => {
  const out: { label: string; value: number }[] = [];
  for (let i = 0; i < 60; i += 5) out.push({ label: `${i}秒`, value: i });
  for (let m = 1; m <= 5; m++) out.push({ label: `${m}分`, value: m * 60 });
  return out;
})();

const proTableRef = ref<ProTableInstance>();
const scopeNote = ref("");
const initParam = reactive({ orderBy: "", order: "", folderId: 0 });

const listApi = (params: any) => getTypedListApi(props.kind, params);

const dataCallback = (data: any) => {
  scopeNote.value = data.scopeNote ?? "";
  return { list: data.list, total: data.total, pageNum: data.pageNum, pageSize: data.pageSize };
};

//
// 列清单**照 ok112 的四张列表页**（模板 + language/chinese.php 里的列名）：
//
//   终端功放 终端功放任务|播放周期|起始日期|结束日期|执行时间|播放时长|任务音量|状态|所属用户|终端属性
//   采播管理 采播任务|播放周期|采播时长|起始日期|结束日期|执行时间|状态|通道|采样率|比特率|任务级别|采播音量|所属用户|终端属性
//   文字语音 文字语音任务|播放周期|开始日期|结束日期|执行时间|播放时长|状态|播放模式|音量|任务级别|任务类型|所属用户|终端属性
//   led播放  LED广播任务|播放周期|开始日期|结束日期|执行时间|播放时长|状态|播放模式|音量|任务级别|所属用户|正在播放|终端属性
//
// 四页都**没有搜索区**（旧版 admmanager.php 里那段搜索是死代码，页面上没有入口）。
//
const COLS: Record<TypedKind, ColumnProps<TypedTask>[]> = {
  amplifier: [
    { prop: "taskName", label: "终端功放任务", minWidth: 180 },
    { prop: "cycleText", label: "播放周期", width: 130 },
    { prop: "startdate", label: "起始日期", width: 110 },
    { prop: "enddate", label: "结束日期", width: 110 },
    { prop: "playtime", label: "执行时间", width: 100 },
    { prop: "lengthText", label: "播放时长", width: 110 },
    { prop: "defaultvolume", label: "任务音量", width: 90 },
    { prop: "projectstate", label: "状态", width: 80 },
    { prop: "userName", label: "所属用户", width: 120 },
    { prop: "terminals", label: "终端属性", width: 110 }
  ],
  collect: [
    { prop: "taskName", label: "采播任务", minWidth: 150 },
    { prop: "cycleText", label: "播放周期", width: 130 },
    { prop: "lengthText", label: "采播时长", width: 110 },
    { prop: "startdate", label: "起始日期", width: 110 },
    { prop: "enddate", label: "结束日期", width: 110 },
    { prop: "playtime", label: "执行时间", width: 100 },
    { prop: "projectstate", label: "状态", width: 80 },
    { prop: "cmdargs", label: "通道", width: 80 },
    { prop: "samplerate", label: "采样率", width: 90 },
    { prop: "bandrate", label: "比特率", width: 90 },
    { prop: "priority", label: "任务级别", width: 90 },
    { prop: "defaultvolume", label: "采播音量", width: 90 },
    { prop: "userName", label: "所属用户", width: 110 },
    { prop: "terminals", label: "终端属性", width: 110 }
  ],
  tts: [
    { prop: "taskName", label: "文字语音任务", minWidth: 160 },
    { prop: "cycleText", label: "播放周期", width: 130 },
    { prop: "startdate", label: "开始日期", width: 110 },
    { prop: "enddate", label: "结束日期", width: 110 },
    { prop: "playtime", label: "执行时间", width: 100 },
    { prop: "lengthText", label: "播放时长", width: 110 },
    { prop: "projectstate", label: "状态", width: 80 },
    { prop: "playModeText", label: "播放模式", width: 100 },
    { prop: "defaultvolume", label: "音量", width: 80 },
    { prop: "priority", label: "任务级别", width: 90 },
    { prop: "typeText", label: "任务类型", width: 100 },
    { prop: "userName", label: "所属用户", width: 110 },
    { prop: "terminals", label: "终端属性", width: 110 }
  ],
  led: [
    { prop: "taskName", label: "LED广播任务", minWidth: 170 },
    { prop: "cycleText", label: "播放周期", width: 130 },
    { prop: "startdate", label: "开始日期", width: 110 },
    { prop: "enddate", label: "结束日期", width: 110 },
    { prop: "playtime", label: "执行时间", width: 100 },
    { prop: "lengthText", label: "播放时长", width: 110 },
    { prop: "projectstate", label: "状态", width: 80 },
    { prop: "playModeText", label: "播放模式", width: 100 },
    { prop: "defaultvolume", label: "音量", width: 80 },
    { prop: "priority", label: "任务级别", width: 90 },
    { prop: "userName", label: "所属用户", width: 110 },
    { prop: "playfileid", label: "正在播放", width: 100 },
    { prop: "terminals", label: "终端属性", width: 110 }
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
  async () => {
    initParam.folderId = 0;
    scopeNote.value = "";
    await loadSources();
    await loadLED();
    refresh();
  }
);

/* ---------------- 终端 / 采播源 / LED 资源 ---------------- */

const terminals = ref<TypedTerminalOption[]>([]);
const terminalLoading = ref(false);
const selectedTerminals = ref<number[]>([]);
/** 每台终端的分区/通道掩码（terminaloftask.area），键是终端 id */
const terminalAreas = ref<Record<number, string>>({});
const sourceTerminals = ref<TypedTerminalOption[]>([]);
const promptList = ref<PromptMedia[]>([]);
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

// 采播终端 / tts终端 是按 terminal.typeid 筛出来的一小批，与终端树不是一回事
const loadSources = async () => {
  if (props.kind !== "collect" && props.kind !== "tts") {
    sourceTerminals.value = [];
    return;
  }
  const { data } = await getTypedSourcesApi(props.kind);
  sourceTerminals.value = data ?? [];
  if (props.kind === "tts" && !promptList.value.length) {
    const res = await getPromptMediaApi();
    promptList.value = res.data ?? [];
  }
};

const loadLED = async () => {
  if (props.kind !== "led") return;
  const [f, d] = await Promise.all([getLedFoldersApi(), getLedDevicesApi()]);
  ledFolders.value = f.data ?? [];
  ledDevices.value = d.data ?? [];
};

/* ---------------- 添加 / 修改 ---------------- */

const blankForm = () => ({
  taskName: "",
  // 两个独立的日期选择器共用这个数组：[0] 开始日期、[1] 结束日期
  range: ["", ""] as string[],
  prepower: 0,
  priority: 10,
  datasendmodel: 0,
  playtime: "08:00:00",
  // 执行模式：1 每天、2 每星期、3 手动（照旧版 exemodel 下拉）
  runMode: 1,
  weekdays: [0, 1, 2, 3, 4, 5, 6] as number[],
  // 播放时长（秒）。功放用它算 endtime，其余三类落到 timelength（timelengthtype=1）
  durationSec: 0,
  // 循环次数（文字语音的「循环次数」，0 = 无限循环）
  timelength: 0,
  defaultvolume: 80,
  folderId: 0,
  channel: 0,
  sourceTerminalId: 0,
  samplerate: 8000,
  bandrate: 8,
  // 播放模式：0 普通、1 间隔
  intervalMode: 0,
  intervalS: 0,
  intPlayLenTy: 1,
  intPlayLenSec: 0,
  intPlayLenCount: 1,
  // 文字语音
  text: "",
  musicmode: 0,
  ttsSpeed: 50,
  promptId: 0,
  led: { text: "", speed: 0, ledmode: 0 }
});

const form = reactive(blankForm());
const err = reactive({ taskName: "", startdate: "", enddate: "", source: "", text: "", ledText: "", terminals: "" });
const clearErr = () => Object.keys(err).forEach(k => ((err as any)[k] = ""));

const dlg = reactive({ visible: false, saving: false, isEdit: false, title: "", id: 0 });

// 任务级别的可选区间由用户组级别决定（与文件广播同一套口径）
// 兜底值照旧版下拉的口径：下限 10、上限 109（服务端会给准确区间）
const priorityRange = reactive({ min: 10, max: 109 });
const priorityOptions = computed(() => {
  const lo = priorityRange.min ?? 0;
  const hi = priorityRange.max ?? 99;
  return Array.from({ length: Math.max(0, hi - lo + 1) }, (_, i) => lo + i);
});

// 「播放模式：普通 / 间隔时间」只有文字语音与 led播放 有
const hasIntervalMode = computed(() => props.kind === "tts" || props.kind === "led");
// 「循环次数」只有文字语音有（旧版 TtsAddFileTask_form 的 circleTime）
const hasCycleTimes = computed(() => props.kind === "tts");
// 提示音只在 tts终端 选到服务器本机（typeid = 0）时出现
const sourceIsServer = computed(() => sourceTerminals.value.find(t => t.id === form.sourceTerminalId)?.typeid === 0);

/*
  终端通道。照旧版 AddAdmManger.html / AdmModify.html 的 changeselect1()：

      var url = "get_changeselect1.php?id=" + 采播终端id;   // 返回 terminaltype.switchcount
      for (i = 0; i < ret; i++) {
        oOption.innerHTML = (i == 0) ? "mp3" : i;
        oOption.value = i + 1;
      }

  也就是说选项条数 = **所选采播终端**那台设备型号的 switchcount，
  第 0 项叫「mp3」值为 1，其余项显示 i、值为 i+1。
  没选采播终端时这个下拉是空的（旧版也是空的）。

  switchcount 已经随 /api/typed-tasks/{kind}/sources 一起下发，
  不用再为它单开一个接口。

  ⚠ 旧版 AdmModify.html 里有处自相矛盾：页面初次渲染那段把第 0 项写成「mp3」，
    而换采播终端时走的 changeselect1() 却把它写成「1」。添加页两处都是「mp3」，
    这里统一用「mp3」。
*/
const sourceSwitchCount = computed(() => sourceTerminals.value.find(t => t.id === form.sourceTerminalId)?.switchCount ?? 0);

const channelOptions = computed(() =>
  Array.from({ length: sourceSwitchCount.value }, (_, i) => ({
    label: i === 0 ? "mp3" : String(i),
    value: i + 1
  }))
);

const onSourceChange = () => {
  if (!sourceIsServer.value) form.promptId = 0;
  // 换了采播终端就重建通道列表：旧版是先清空再按新的 switchcount 重建，
  // 原来选中的值不会保留。这里同理 —— 落在新范围外就回到第一项。
  if (!channelOptions.value.some(c => c.value === form.channel)) {
    form.channel = channelOptions.value[0]?.value ?? 0;
  }
};
const onRunModeChange = (v: number) => {
  if (v === 1) form.weekdays = [0, 1, 2, 3, 4, 5, 6];
  if (v === 3) form.weekdays = [];
};
const onIntervalModeChange = (v: number) => {
  if (v === 0) {
    form.intervalS = 0;
    form.intPlayLenTy = 1;
    form.intPlayLenSec = 0;
    form.intPlayLenCount = 1;
  }
};

const resetForm = () => Object.assign(form, blankForm());

const openCreate = async () => {
  resetForm();
  clearErr();
  const today = new Date().toISOString().slice(0, 10);
  form.range = [today, today];
  // 任务级别的可选区间由用户组级别决定，新建时先问一次服务端，
  // 免得默认值落在区间外、点提交才被拒。
  const { data: pr } = await getTaskPriorityRangeApi();
  priorityRange.min = pr.priorityMin ?? 0;
  priorityRange.max = pr.priorityMax ?? 99;
  form.priority = priorityRange.min;
  await loadSources();
  if (props.kind === "led") {
    await loadLED();
    form.folderId = initParam.folderId || ledFolders.value[0]?.id || 0;
  }
  if (props.kind !== "amplifier") form.prepower = 120;
  selectedTerminals.value = [];
  terminalAreas.value = {};
  selectedLedDevices.value = [];
  Object.assign(dlg, { visible: true, saving: false, isEdit: false, title: `添加${title.value}`, id: 0 });
  await searchTerminals("");
};

const openEdit = async (row: TypedTask) => {
  const { data } = await getTypedApi(props.kind, row.taskId);
  resetForm();
  clearErr();
  await loadSources();
  form.taskName = data.taskName;
  form.range = [data.startdate, data.enddate];
  form.playtime = data.playtime;
  form.weekdays = (data.exemodel || "").split("").reduce<number[]>((acc, c, i) => (c === "1" ? [...acc, i] : acc), []);
  // 执行模式反推：全 1 是每天、全 0 是手动、其余是每星期
  form.runMode = form.weekdays.length === 7 ? 1 : form.weekdays.length === 0 ? 3 : 2;
  form.timelength = data.timelength;
  form.defaultvolume = data.defaultvolume;
  form.prepower = data.prepower ?? 0;
  form.priority = data.priority || 3;
  form.datasendmodel = data.datasendmodel ?? 0;
  form.folderId = data.parentid;
  form.channel = Number(data.cmdargs) || 0;
  form.samplerate = data.samplerate || 8000;
  form.bandrate = data.bandrate || 8;
  priorityRange.min = data.priorityMin ?? 10;
  priorityRange.max = data.priorityMax ?? 109;
  // 播放时长在库里是 timelength（timelengthtype=1）；功放没有这一列，靠 endtime - playtime 反算
  form.durationSec = props.kind === "amplifier" ? diffSec(data.playtime, data.endtime) : data.timelength;
  if (props.kind === "tts") form.durationSec = data.timelengthtype === 1 ? data.timelength : 0;
  // 间隔播放
  form.intervalMode = data.intplaylengthtype ? 1 : 0;
  form.intervalS = data.interval_s ?? 0;
  form.intPlayLenTy = data.intplaylengthtype || 1;
  form.intPlayLenSec = data.intplaylengthtype === 1 ? data.intplaylength : 0;
  form.intPlayLenCount = data.intplaylengthtype === 2 ? data.intplaylength : 1;
  // 采播与文字语音的 cmd 都是终端 id，回填方式相同
  if (props.kind === "collect" || props.kind === "tts") form.sourceTerminalId = Number(data.cmd) || 0;
  if (props.kind === "tts") {
    form.text = data.ttsText ?? "";
    form.musicmode = data.musicmode ?? 0;
    form.ttsSpeed = data.ttsSpeed ?? 50;
    form.promptId = data.promptId ?? 0;
  }
  if (props.kind === "led") {
    await loadLED();
    form.led = { text: data.led?.text ?? "", speed: data.led?.speed ?? 0, ledmode: data.led?.ledmode ?? 0 };
    selectedLedDevices.value = (data.led?.devices ?? []).filter(d => !d.deleted).map(d => d.deviceId);
  }
  // 把库里已有的分区掩码回填给树，改的时候才看得出原来选了哪几个分区
  terminalAreas.value = Object.fromEntries(data.terminals.filter(t => !t.deleted && t.area).map(t => [t.terminalId, t.area]));
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
  const interval = form.intervalMode === 1;
  return {
    taskName: form.taskName.trim(),
    startdate: form.range[0],
    enddate: form.range[1],
    // 手动（exemodel 全 0）时旧版把播放时间也归零
    playtime: form.runMode === 3 ? "00:00:00" : form.playtime,
    durationSec: form.durationSec,
    // 这四类任务的 timelengthtype 旧版一律写 1（按时间）；
    // 文字语音的「循环次数」落在 timelength 上，播放时长落在 endtime 上。
    timelengthtype: 1,
    timelength: props.kind === "tts" ? form.timelength : form.durationSec,
    exemodel: mask,
    defaultvolume: form.defaultvolume,
    // 这四张表单里都没有「方案状态」，新建一律启用；修改时服务端会用传来的值，
    // 所以这里回传 0（启用），启停由列表上的「启用 / 停用」按钮管。
    projectstate: 0,
    prepower: form.prepower,
    priority: form.priority,
    datasendmodel: form.datasendmodel,
    folderId: props.kind === "led" ? form.folderId : 0,
    switch: 0,
    channel: form.channel,
    sourceTerminalId: form.sourceTerminalId,
    samplerate: props.kind === "collect" ? form.samplerate : 0,
    bandrate: props.kind === "collect" ? form.bandrate : 0,
    interval_s: interval ? form.intervalS : 0,
    intplaylength: interval ? (form.intPlayLenTy === 1 ? form.intPlayLenSec : form.intPlayLenCount) : 0,
    intplaylengthtype: interval ? form.intPlayLenTy : 0,
    text: props.kind === "tts" ? form.text : "",
    musicmode: form.musicmode,
    ttsSpeed: form.ttsSpeed,
    promptId: sourceIsServer.value ? form.promptId : 0,
    terminals: selectedTerminals.value.map(id => ({
      terminalId: id,
      // 分区/通道掩码：树上逐台勾的结果，没勾过的照后端默认（全通道）
      area: terminalAreas.value[id] ?? "11111111",
      groupId: terminals.value.find(t => t.id === id)?.groupId ?? 0
    })),
    led: props.kind === "led" ? { ...form.led, devices: ledDevs } : null
  };
};

const submit = async () => {
  clearErr();
  let bad = false;
  if (!form.taskName.trim()) {
    err.taskName = "请输入任务名称";
    bad = true;
  }
  if (!form.range?.[0]) {
    err.startdate = "请选择开始日期";
    bad = true;
  }
  if (!form.range?.[1]) {
    err.enddate = "请选择结束日期";
    bad = true;
  }
  if (props.kind === "collect" && !form.sourceTerminalId) {
    err.source = "请选择采播终端";
    bad = true;
  }
  if (props.kind === "tts" && !form.text.trim()) {
    err.text = "请输入播放文字";
    bad = true;
  }
  if (props.kind === "led" && !form.led.text.trim()) {
    err.ledText = "请输入Led字幕内容";
    bad = true;
  }
  if (!selectedTerminals.value.length) {
    err.terminals = "请选择任务终端";
    bad = true;
  }
  if (bad) return ElMessage.warning("带 * 的项还没填完");

  dlg.saving = true;
  try {
    const body = buildBody() as any;
    if (dlg.isEdit) await updateTypedApi(props.kind, dlg.id, body);
    else await createTypedApi(props.kind, body);
    ElMessage.success("保存成功");
    dlg.visible = false;
    if (props.kind === "led") await loadLED();
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

const ACTION_TEXT: Record<TypedAction, string> = {
  start: "执行",
  stop: "停止",
  pause: "暂停",
  resume: "恢复"
};

const doControl = async (action: TypedAction, raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning("请先勾选任务");
  const { data } = await controlTypedApi(props.kind, action, ids);
  reportBlocked(data.blocked, data.succeeded.length, ACTION_TEXT[action]);
  refresh();
};

const doState = async (raw: (string | number)[], enable: boolean) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning("请先勾选任务");
  const { data } = await setTypedStateApi(props.kind, ids, enable);
  reportBlocked(data.blocked, data.succeeded.length, enable ? "启用" : "停用");
  refresh();
};

/* 任务音量：这四类任务和文件广播同在 task 表，直接用 /api/tasks/volume */
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
  if (props.kind === "led") await loadLED();
  refresh();
};

/* ---------------- 浏览终端 ---------------- */

const termDlg = reactive({ visible: false, title: "", list: [] as TypedTerminal[] });

const openTerminals = async (row: TypedTask) => {
  const { data } = await getTypedApi(props.kind, row.taskId);
  termDlg.title = `${row.taskName} 的终端`;
  termDlg.list = data.terminals ?? [];
  termDlg.visible = true;
};

/* ---------------- LED 任务目录 ---------------- */

const folderDlg = reactive({ visible: false, saving: false, title: "", name: "", id: 0 });

const openFolderCreate = () => {
  Object.assign(folderDlg, { visible: true, saving: false, title: "创建目录", name: "", id: 0 });
};

const openFolderRename = () => {
  const f = ledFolders.value.find(x => x.id === initParam.folderId);
  if (!f) return ElMessage.warning("请先在上面选中一个目录");
  Object.assign(folderDlg, { visible: true, saving: false, title: "修改目录", name: f.name, id: f.id });
};

const submitFolder = async () => {
  if (!folderDlg.name.trim()) return ElMessage.warning("请输入目录名称");
  folderDlg.saving = true;
  try {
    if (folderDlg.id) await renameLedFolderApi(folderDlg.id, folderDlg.name.trim());
    else await createLedFolderApi(folderDlg.name.trim());
    ElMessage.success("保存成功");
    folderDlg.visible = false;
    await loadLED();
    refresh();
  } finally {
    folderDlg.saving = false;
  }
};

const doFolderDelete = async () => {
  const f = ledFolders.value.find(x => x.id === initParam.folderId);
  if (!f) return ElMessage.warning("请先在上面选中一个目录");
  await ElMessageBox.confirm(`确认删除目录「${f.name}」？目录里的 ${f.taskCount} 条任务会一起被删除，不可恢复。`, "删除目录", {
    type: "warning",
    confirmButtonText: "确认删除"
  });
  await deleteLedFolderApi(f.id);
  ElMessage.success("已删除");
  initParam.folderId = 0;
  await loadLED();
  refresh();
};

const copyDlg = reactive({ visible: false, saving: false, fromId: 0, toId: 0 });

const openFolderCopy = () => {
  copyDlg.fromId = initParam.folderId || ledFolders.value[0]?.id || 0;
  copyDlg.toId = ledFolders.value.find(f => f.id !== copyDlg.fromId)?.id ?? 0;
  copyDlg.saving = false;
  copyDlg.visible = true;
};

const submitCopy = async () => {
  if (!copyDlg.fromId || !copyDlg.toId) return ElMessage.warning("请选择源目录与目标目录");
  if (copyDlg.fromId === copyDlg.toId) return ElMessage.warning("源目录与目标目录不能是同一个");
  copyDlg.saving = true;
  try {
    const { data } = await copyLedFolderApi(copyDlg.fromId, copyDlg.toId);
    ElMessage.success(`已复制 ${data.copied} 条任务`);
    copyDlg.visible = false;
    await loadLED();
    refresh();
  } finally {
    copyDlg.saving = false;
  }
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
  await ElMessageBox.confirm(`确认删除选中的 ${devSel.value.length} 块 LED 屏？会连带清掉它们在任务里的绑定。`, "删除 LED 设备", {
    type: "warning",
    confirmButtonText: "确认删除"
  });
  const { data } = await deleteLedDevicesApi(devSel.value);
  ElMessage.success(`已删除 ${data.deleted} 块`);
  devSel.value = [];
  await loadLED();
};

onMounted(async () => {
  await searchTerminals("");
  await loadSources();
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
.tip {
  margin-left: 8px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.err {
  margin-top: 2px;
  font-size: 12px;
  line-height: 1.5;
  color: var(--el-color-danger);
}
.opt-sub {
  margin-left: 10px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.fill {
  width: 100%;
}
.wk-group {
  margin-left: 14px;
}
/* el-radio-group 自带 align-items: center，两行会被压成居中，这里改成左对齐 */
.len-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: flex-start;
}
.len-line {
  display: flex;
  gap: 10px;
  align-items: center;
  :deep(.el-radio) {
    width: 120px;
    margin-right: 0;
  }
}
.led-dev {
  width: 100%;
  :deep(.el-checkbox) {
    display: flex;
    width: 100%;
    margin-right: 0;
  }
}
.dlg-note {
  font-size: 12px;
  line-height: 1.7;
  color: var(--el-text-color-secondary);
}
.dev-bar {
  display: flex;
  gap: 8px;
  margin-bottom: 10px;
}
</style>
