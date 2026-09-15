<!--
  作息方案（打铃）

  数据模型上必须先明白一件事：**方案不是一张表**。
  一个方案 = task 表里共享同一个 info 的一组行，每行是一次打铃。
  所以这一页的主键是方案名（字符串），列表是 GROUP BY info 聚合出来的。

  相对旧版的关键修复：
    · 分页恢复（旧版整块注释掉，一次渲染全部方案）
    · 排序生效（旧版 ORDER BY '$_GET[...]' 加了引号，排的是常量）
    · 组内状态不一致时明确标出来 —— 旧版只显示多数派状态，用户看到「启用」
      而其中几节课实际是停用的，界面毫无提示
    · 创建者被删掉后方案仍在列表里（旧版逗号隐式内连接，整条消失）
    · 启停、删除都校验方案归属；删除范围补上 tasktype 过滤，
      不再误删同名的文件广播任务
    · 删除前先看影响面并二次确认
-->
<template>
  <div class="table-box">
    <ProTable
      ref="proTableRef"
      :columns="columns"
      :request-api="getBellPlanListApi"
      :init-param="initParam"
      :data-callback="dataCallback"
      row-key="planName"
      @sort-change="onSortChange"
    >
      <!--
        按钮对齐 :80（页面规格.txt「作息方案」）：
        添加方案 / 删除方案 / 启用方案 / 停用方案 / 调整音量 / 复制方案 / 智能排课，
        都作用在勾选行上（智能排课是在新建方案时用的，不需要勾选）。
      -->
      <template #tableHeader="scope">
        <div class="header-bar">
          <div class="header-left">
            <el-button type="primary" :disabled="!btn.add" @click="openCreate">{{ $t("bell.addPlan") }}</el-button>
            <el-button type="danger" :disabled="!btn.delete || !scope.isSelected" @click="batchCmd('delete', scope.selectedList)">
              {{ $t("bell.deletePlan") }}
            </el-button>
            <el-button
              type="primary"
              :disabled="!btn.control || !scope.isSelected"
              @click="batchCmd('enable', scope.selectedList)"
            >
              {{ $t("bell.enablePlan") }}
            </el-button>
            <el-button
              type="danger"
              :disabled="!btn.control || !scope.isSelected"
              @click="batchCmd('disable', scope.selectedList)"
            >
              {{ $t("bell.disablePlan") }}
            </el-button>
            <el-button type="warning" :disabled="!btn.edit || !scope.isSelected" @click="openVolume(scope.selectedList)">
              {{ $t("terminalCommon.adjustVolume") }}
            </el-button>
            <el-button
              type="primary"
              :disabled="!btn.copy || scope.selectedList.length !== 1"
              @click="batchCmd('copy', scope.selectedList)"
            >
              {{ $t("bell.copyPlan") }}
            </el-button>
            <el-button
              type="warning"
              plain
              :disabled="!btn.edit || scope.selectedList.length !== 1"
              @click="openBatch(scope.selectedList[0])"
            >
              {{ $t("bell.batchEdit") }}
            </el-button>
            <el-button
              type="primary"
              :disabled="!btn.edit || scope.selectedList.length !== 1"
              @click="openSchedule(scope.selectedList[0])"
            >
              {{ $t("bell.smartSchedule") }}
            </el-button>
          </div>
          <div class="header-right">
            <el-tag v-if="scopeNote" type="info" size="small" effect="plain">{{ scopeNote }}</el-tag>
          </div>
        </div>
      </template>

      <template #planName="scope">
        <span class="plan-name">{{ scope.row.planName }}</span>
        <!-- ⚠ 必须用可选链：el-table-column 会拿 row = {} 试跑一次插槽 -->
        <el-tooltip v-if="scope.row.duplicateTimes?.length" placement="top">
          <template #content>
            {{ $t("bell.dupTimes") }}<br />
            {{ scope.row.duplicateTimes.join("、") }}
          </template>
          <el-icon class="warn-icon"><WarningFilled /></el-icon>
        </el-tooltip>
      </template>

      <template #projectstate="scope">
        <!-- 0 = 启用、1 = 停用，与 audioserver.sql 的列注释相反 -->
        <el-tag v-if="scope.row.projectstate === 0" type="success" size="small">{{ $t("common.enable") }}</el-tag>
        <el-tag v-else type="info" size="small">{{ $t("common.disable") }}</el-tag>
        <el-tooltip v-if="scope.row.mixedState" :content="$t('bell.stateMixedTip')" placement="top">
          <el-tag type="warning" size="small" effect="plain" class="mixed-tag">{{ $t("bell.inconsistent") }}</el-tag>
        </el-tooltip>
      </template>

      <template #itemCount="scope">
        <el-tag size="small" effect="plain">{{ $t("bell.entryCount", { n: scope.row.itemCount }) }}</el-tag>
        <el-tag v-if="scope.row.powerSubTasks" size="small" type="info" effect="plain" class="mixed-tag">
          +{{ scope.row.powerSubTasks }} 功放
        </el-tag>
      </template>

      <template #ownerUserName="scope">
        <span :class="{ muted: scope.row.ownerDeleted }">{{ scope.row.ownerUserName }}</span>
      </template>

      <!--
        :80 这一列叫「终端属性」，里面只有一个「查看任务」链接。
        启停/复制/删除都搬到顶部按钮了，行内只留查看与修改。

        「修改」没有跟着删掉：方案级属性（起止日期、星期掩码、音量、优先级）
        只能改，不能靠「删掉重建」——重建会连同方案里的全部条目一起丢。
      -->
      <template #operation="scope">
        <el-button type="primary" link :icon="EditPen" :disabled="!btn.edit" @click="openEdit(scope.row)">{{
          $t("common.modify")
        }}</el-button>
      </template>
    </ProTable>

    <!-- 调整音量：整个方案改一次，功放子任务一起改 -->
    <el-dialog v-model="vol.visible" :title="$t('terminalCommon.adjustVolume')" width="440px">
      <!-- 一次能调几个方案，就得让人看清改的是哪几个 -->
      <div class="del-names mb12">
        <el-tag v-for="n in vol.planNames" :key="n" size="small" effect="plain">{{ n }}</el-tag>
      </div>
      <el-slider v-model="vol.value" :min="0" :max="100" show-input />
      <template #footer>
        <el-button @click="vol.visible = false">{{ $t("common.cancel") }}</el-button>
        <el-button type="primary" :loading="vol.saving" @click="submitVolume">{{ $t("common.confirm") }}</el-button>
      </template>
    </el-dialog>

    <!--
      智能排课：把方案里的课时按打铃时间顺序摊开，勾中若干条，统一挪到新的日期时间段。
      对应旧版「统一播放时间」页（sechotime.php）——那一页也是先按 playtime 排好序，
      勾中若干条再统一改，只不过旧版改的是星期，这里改的是起止日期。
    -->
    <el-dialog v-model="sched.visible" :title="$t('bell.smartScheduleOf', { name: sched.planName })" width="940px" top="6vh">
      <div class="sched-head">
        <div class="sched-sum">
          <span class="sched-plan">{{ sched.planName }}</span>
          <el-tag size="small" effect="plain">{{ sched.list.length }} 个课时</el-tag>
          <el-tag size="small" type="info" effect="plain">当前 {{ sched.rangeText }}</el-tag>
          <el-tag v-if="sched.mixed" size="small" type="warning" effect="plain">{{ $t("bell.datesInconsistent") }}</el-tag>
        </div>
        <div class="sched-pick">
          <span class="sched-label">{{ $t("bell.newDateRange") }}</span>
          <el-date-picker
            v-model="sched.range"
            type="daterange"
            value-format="YYYY-MM-DD"
            :range-separator="$t('common.to')"
            :start-placeholder="$t('common.startDate')"
            :end-placeholder="$t('common.endDate')"
            :clearable="false"
            style="width: 300px"
          />
          <span class="sched-note">{{ $t("bell.sameDateRangeNote") }}</span>
        </div>
        <!-- 星期照旧版 sechotime.php 那页：周日排在第一个，与 exemodel 的位序一致 -->
        <div class="sched-pick">
          <span class="sched-label">{{ $t("bell.newWeekdays") }}</span>
          <el-checkbox-group v-model="sched.weekdays">
            <el-checkbox v-for="(w, i) in weekLabels" :key="i" :value="i">{{ w }}</el-checkbox>
          </el-checkbox-group>
          <el-divider direction="vertical" />
          <el-button link type="primary" @click="sched.weekdays = [0, 1, 2, 3, 4, 5, 6]">{{
            $t("taskCommon.everyDay")
          }}</el-button>
          <el-button link type="primary" @click="sched.weekdays = [1, 2, 3, 4, 5]">{{ $t("bell.workday") }}</el-button>
          <span class="sched-note">{{ $t("bell.weekdayDefaultNote") }}</span>
        </div>
      </div>

      <el-table
        ref="schedTableRef"
        :data="sched.list"
        size="small"
        border
        stripe
        max-height="420"
        row-key="taskid"
        @selection-change="rows => (sched.checked = rows as BellItem[])"
      >
        <el-table-column type="selection" width="42" align="center" />
        <el-table-column type="index" :label="$t('common.index')" width="56" align="center" />
        <el-table-column :label="$t('bell.bellTime')" width="112" align="center">
          <template #default="{ row }">
            <span class="time-cell">{{ row.playtime }}</span>
            <el-tooltip v-if="row.duplicateTime" :content="$t('bell.sameTimeTip')" placement="top">
              <el-icon class="warn-icon"><WarningFilled /></el-icon>
            </el-tooltip>
          </template>
        </el-table-column>
        <el-table-column prop="taskname" :label="$t('bell.lessonName')" min-width="118" show-overflow-tooltip />
        <el-table-column :label="$t('bell.tone')" min-width="116">
          <template #default="{ row }">
            <span v-if="!row.media?.length" class="muted">{{ $t("bell.notSet") }}</span>
            <el-tag
              v-for="m in row.media"
              :key="m.mediaId"
              size="small"
              :type="m.deleted ? 'danger' : 'info'"
              effect="plain"
              class="media-tag"
            >
              {{ m.name }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="$t('taskCommon.playLength')" width="96" align="center">
          <template #default="{ row }">
            {{ row.timelengthtype === 1 ? lenText(row.timelength) : t("term.loopUnit", { n: row.timelength }) }}
          </template>
        </el-table-column>
        <el-table-column :label="$t('taskCommon.run')" width="148" align="center">
          <template #default="{ row }">
            <div :class="{ 'date-chg': isChecked(row) && schedMask !== row.exemodel }">
              <span v-if="row.exemodel === '1111111'" class="muted">{{ $t("taskCommon.everyDay") }}</span>
              <span v-else-if="row.exemodel === '0000000'" class="muted">{{ $t("taskCommon.manual") }}</span>
              <!--
                只画**勾上的**那几天。
                原来七天全画出来、没勾的刷成灰色 —— 一行里七个格子，灰的比亮的还多，
                真正要看的「这条到底哪几天响」反而得一个个数过去。按需求方要求，
                没勾的直接不显示。
              -->
              <template v-else>
                <template v-for="(w, i) in weekLabels" :key="i">
                  <span v-if="row.exemodel?.[i] === '1'" class="wk on">{{ w }}</span>
                </template>
              </template>
            </div>
            <!-- 新星期用文字，别再摆一排格子：一行放不下会把行撑成三行 -->
            <div v-if="isChecked(row) && schedMask !== row.exemodel" class="date-new">→ {{ schedWeekText }}</div>
          </template>
        </el-table-column>
        <el-table-column :label="$t('bell.currentRange')" width="176" align="center">
          <template #default="{ row }">
            <span :class="{ 'date-chg': isChecked(row) }">{{ row.startdate }} ~ {{ row.enddate }}</span>
            <div v-if="isChecked(row) && sched.range?.[0]" class="date-new">→ {{ sched.range[0] }} ~ {{ sched.range[1] }}</div>
          </template>
        </el-table-column>
        <template #empty
          ><span class="dlg-note">{{ $t("bell.planNoLesson") }}</span></template
        >
      </el-table>

      <template #footer>
        <span class="sched-foot">已勾选 {{ sched.checked.length }} / {{ sched.list.length }}</span>
        <el-button type="primary" :loading="sched.saving" @click="submitSchedule">{{ $t("common.confirm") }}</el-button>
        <el-button @click="schedTableRef?.toggleAllSelection()">{{ $t("common.selectAll") }}</el-button>
        <el-button @click="schedTableRef?.clearSelection()">{{ $t("common.cancel") }}</el-button>
        <el-button @click="sched.visible = false">{{ $t("bell.goBack") }}</el-button>
      </template>
    </el-dialog>

    <!-- 添加方案 / 修改方案 / 批量修改：旧版三个页面，字段几乎一样，这里合成一个对话框 -->
    <el-dialog v-model="dlg.visible" :title="dlg.title" width="860px" top="5vh" :before-close="closeDialog">
      <el-alert v-if="dlg.mixedAttrs.length" type="warning" :closable="false" class="mb12">
        方案内以下属性各条目取值不一致：{{ dlg.mixedAttrs.join("、") }}。 保存后会统一成下面填写的值。
      </el-alert>

      <!--
        表单排布一比一照 ok112 的「添加方案」（BellManager/addbelltask.html）：

          ─ 任务配置 ────────────────────────────────
          方案名称 [____]        方案任务 [添加任务]
          预开电源 [下拉]        任务级别 [下拉] (10最高)
          作息音量 [____]        发送模式 [单播/组播]
          开始日期 [____]        结束日期 [____]
          执行模式 [每天/每星期]  ← 选「每星期」才出现星期勾选
          ─ 条目表 ─────────────────────────────────
          序号 | 课时名称 | 作息时间 | 作息音乐 | 播放时长 | 操作
          ─ 终端列表 ───────────────────────────────

        ⚠ 旧版这张表里**没有**「播放模式（随机/顺序）」—— 建作息条目时
          israndomplay 是写死 0（随机）的。所以这里不摆这个输入框，
          新建时仍按 0 提交；修改时沿用方案里原有的值，不会被清掉。
      -->
      <el-form ref="planFormRef" :model="dlg.form" :rules="planRules" label-width="90px">
        <el-divider content-position="left">{{ $t("bell.taskConfig") }}</el-divider>
        <!--
          ⚠ 提前开电源 / 音量 / 任务级别 / 起止日期 / 星期这几项名义上是「方案级」，
          实际每一行 task 各存一份，可以不一致。选中下面某个课时之后，这排控件
          显示的就是**那一课时自己的**值（旧版 getonetaskterminal.js 同样如此），
          所以这行字必须摆在这儿 —— 混淆就发生在这排控件上。
        -->
        <div v-if="termScopeNote" class="dlg-note mb6">
          {{ termScopeNote }}
          <el-button v-if="termScopeIdx >= 0" link type="primary" @click="activeItem = -1">
            {{ $t("bell.backToPlanTerminals") }}
          </el-button>
        </div>

        <el-row :gutter="16">
          <el-col :span="12">
            <!-- 旧版是 maxlength="8"，按需求方要求放宽到 12 -->
            <el-form-item :label="$t('bell.planName')" prop="planName">
              <el-input
                v-model="dlg.form.planName"
                :maxlength="PLAN_NAME_MAX"
                show-word-limit
                :disabled="!!dlg.savedPlanName"
                :placeholder="$t('bell.planNamePlaceholder')"
              />
              <!-- 已经有课时入库了就锁住方案名：方案是靠名字归组的，这时改名等于另起一个方案 -->
              <span v-if="dlg.savedPlanName" class="dlg-note">{{ $t("bell.planNameLocked") }}</span>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item :label="$t('bell.planTasks')">
              <el-button type="primary" plain :icon="CirclePlus" @click="addItemRow">{{ $t("bell.addLesson") }}</el-button>
              <el-button type="danger" plain :icon="Delete" @click="removeSelectedItems">{{
                $t("taskCommon.deleteTask")
              }}</el-button>
              <span class="dlg-note ml8">{{ itemCountNote }}</span>
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="16">
          <el-col :span="12">
            <!-- prepower 的单位是秒不是分钟；选项与默认值（15 秒）都照旧版 -->
            <el-form-item :label="$t('taskCommon.prePower')">
              <el-select v-model="dlg.form.playback.prepower" class="fill">
                <el-option v-for="o in prepowerOptions" :key="o.value" :label="o.label" :value="o.value" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item :label="$t('taskCommon.priority')">
              <el-select v-model="dlg.form.playback.priority" style="width: 110px">
                <el-option v-for="p in priorityOptions" :key="p.value" :label="p.label" :value="p.value" />
              </el-select>
              <span class="dlg-note ml8">{{ $t("bell.highest10") }}</span>
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item :label="$t('bell.bellVolume')">
              <el-slider v-model="dlg.form.playback.defaultvolume" :min="0" :max="100" show-input />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item :label="$t('taskCommon.sendMode')">
              <el-radio-group v-model="dlg.form.playback.datasendmodel">
                <el-radio :value="0">{{ $t("taskCommon.unicast") }}</el-radio>
                <el-radio :value="1">{{ $t("term.multicast") }}</el-radio>
              </el-radio-group>
            </el-form-item>
          </el-col>
        </el-row>

        <!--
          LED 播放：勾上才出现字幕与速度两个输入框，与文件广播那张表单同一套做法。
          它不是主任务上的一个开关列 —— 保存时会给方案里**每个课时**各挂一条
          tasktype=30 的 LED 子任务，字幕正文写进 ledsentence，跟着课时一起播。
        -->
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item :label="$t('task.ledPlay')">
              <el-checkbox v-model="dlg.form.ledOn">{{ $t("bell.openLed") }}</el-checkbox>
            </el-form-item>
          </el-col>
          <el-col v-if="dlg.form.ledOn" :span="12">
            <el-form-item :label="$t('task.ledSpeed')">
              <el-select v-model="dlg.form.ledSpeed" style="width: 110px">
                <el-option v-for="n in [0, 1, 2, 3, 4, 5]" :key="n" :label="$t('task.levelN', { n })" :value="n" />
              </el-select>
              <span class="dlg-note ml8">{{ $t("task.levels0to5") }}</span>
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item v-if="dlg.form.ledOn" :label="$t('task.ledSubtitle')" prop="ledText">
          <!-- 多行只是为了长句子好读好改；存库时换行会被去掉（旧版 do.php 也是这么处理的） -->
          <el-input
            v-model="dlg.form.ledText"
            type="textarea"
            :rows="3"
            maxlength="200"
            show-word-limit
            :placeholder="$t('term.ledScrollText')"
            @input="planFormRef?.clearValidate('ledText')"
          />
        </el-form-item>

        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item :label="$t('common.startDate')" prop="startdate">
              <el-date-picker
                v-model="dateRange[0]"
                type="date"
                value-format="YYYY-MM-DD"
                :placeholder="$t('common.startDate')"
                class="fill"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item :label="$t('common.endDate')" prop="enddate">
              <el-date-picker
                v-model="dateRange[1]"
                type="date"
                value-format="YYYY-MM-DD"
                :placeholder="$t('common.endDate')"
                class="fill"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <!--
          执行模式是下拉「每天 / 每星期」，选每星期才展开星期勾选
          （旧版 select#exemodel 的 onChange="displayweek(this)"）。
          每天 = 七位全 1。
        -->
        <el-form-item :label="$t('taskCommon.exeMode')" prop="weekdays">
          <el-select v-model="runMode" style="width: 140px" @change="onRunModeChange">
            <el-option :label="$t('taskCommon.everyDay')" :value="1" />
            <el-option :label="$t('taskCommon.everyWeek')" :value="2" />
          </el-select>
          <el-checkbox-group v-if="runMode === 2" v-model="weekdays" class="ml8">
            <el-checkbox v-for="(w, i) in weekLabels" :key="i" :value="i">{{ w }}</el-checkbox>
          </el-checkbox-group>
        </el-form-item>

        <!--
          方案任务表，照旧版 coursetable：
          序号 / 课时名称 / 作息时间 / 作息音乐 / 播放时长 / 操作(添加·复制·删除)
          行上的「添加」和旧版一样是**真的写库**：把页头这些方案属性 + 这一行，
          直接存成一条打铃任务；存过的行按钮变「修改」，再点就是改这一条。
          「删除」同样是真删：已经入库的行连库里的任务一起删，没入库的只去掉这一行。
        -->
        <el-divider content-position="left">{{ $t("bell.planTasks") }}</el-divider>
        <!--
          批量修改专用的「统一设置」栏，位置照旧版 modifybellall.html：
          它把三样东西摆在课时表头上 —— 作息音乐、播放时长，各自带一个「启用」勾选框，
          勾了才会把这个统一值刷到所有勾中的课时上，没勾就保持各行原样。
        -->
        <div v-if="dlg.mode === 'batch'" class="batch-bar">
          <el-checkbox v-model="batch.enableMedia">{{ $t("bell.unifiedMusic") }}</el-checkbox>
          <el-select
            v-model="batch.mediaId"
            filterable
            remote
            reserve-keyword
            size="small"
            :disabled="!batch.enableMedia"
            :remote-method="searchMedia"
            :loading="mediaLoading"
            :placeholder="$t('common.searchMediaName')"
            style="width: 220px"
          >
            <el-option v-for="m in medias" :key="m.id" :label="m.name" :value="m.id" />
          </el-select>
          <el-divider direction="vertical" />
          <el-checkbox v-model="batch.enableLen">{{ $t("bell.unifiedLength") }}</el-checkbox>
          <el-select v-model="batch.lenType" size="small" :disabled="!batch.enableLen" style="width: 74px">
            <el-option :label="$t('common.duration')" :value="1" />
            <el-option :label="$t('bell.times')" :value="2" />
          </el-select>
          <el-time-picker
            v-if="batch.lenType === 1"
            v-model="batch.lenHms"
            value-format="HH:mm:ss"
            size="small"
            :disabled="!batch.enableLen"
            :placeholder="$t('bell.hhmmss')"
            style="width: 116px"
          />
          <template v-else>
            <!-- el-input-number 改 disabled 后不会更新 aria-disabled，用 key 强制重建，免得读屏软件读成禁用 -->
            <el-input-number
              :key="`len-${batch.enableLen}`"
              v-model="batch.lenTimes"
              :min="0"
              :max="99"
              :disabled="!batch.enableLen"
              size="small"
              :controls="false"
              style="width: 70px"
            />
            <span class="dlg-note">{{ $t("term.times") }}</span>
          </template>
        </div>
        <el-table
          ref="itemTableRef"
          :data="dlg.items"
          size="small"
          border
          max-height="300"
          @selection-change="onItemSelectionChange"
        >
          <el-table-column type="selection" width="40" align="center" />
          <!--
            序号是单选（旧版 modifybell.html:424 那个 `<input type=radio name="belltaskid">`）。
            选中哪一行，哪一行才可编辑，其余行锁住 —— 旧版 getonebelltaskterminal()
            干的就是这件事：把选中行的输入框 disabled=false，其它行全 true。
            这样一次只改一条，不会在满屏输入框里改错行。

            ⚠ 只在**修改方案**里这么锁。新建方案是一条条往里填，锁了就没法连着录。
            左边那列复选框留着 —— 批量修改和「删除任务」按的是它，两者不是一回事。
          -->
          <el-table-column :label="$t('common.index')" width="60" align="center">
            <template #default="{ $index }">
              <el-radio v-model="activeItem" :value="$index" class="idx-radio">{{ $index + 1 }}</el-radio>
            </template>
          </el-table-column>
          <el-table-column min-width="120">
            <template #header><span class="req-star">*</span> {{ $t("bell.lessonName") }}</template>
            <template #default="{ row, $index }">
              <el-input
                v-model="row.taskname"
                size="small"
                maxlength="12"
                :disabled="itemLocked($index)"
                :placeholder="$t('bell.lessonName')"
                :class="{ 'is-bad': itemErrors[$index]?.taskname }"
                @input="itemErrors[$index] && (itemErrors[$index].taskname = '')"
              />
              <div v-if="itemErrors[$index]?.taskname" class="cell-err">{{ itemErrors[$index].taskname }}</div>
            </template>
          </el-table-column>
          <el-table-column width="110">
            <template #header><span class="req-star">*</span> {{ $t("bell.bellTime") }}</template>
            <template #default="{ row, $index }">
              <el-time-picker
                v-model="row.playtime"
                value-format="HH:mm:ss"
                size="small"
                placeholder="00:00:00"
                :disabled="itemLocked($index)"
                class="fill"
                :class="{ 'is-bad': itemErrors[$index]?.playtime }"
                @change="itemErrors[$index] && (itemErrors[$index].playtime = '')"
              />
              <div v-if="itemErrors[$index]?.playtime" class="cell-err">{{ itemErrors[$index].playtime }}</div>
            </template>
          </el-table-column>
          <el-table-column :label="$t('bell.bellMusic')" min-width="134">
            <template #default="{ row, $index }">
              <span v-if="dlg.mode === 'batch'" :class="{ muted: !batchMediaName(row) }">{{
                batchMediaName(row) || $t("sys.notSet")
              }}</span>
              <!--
                一课时一铃声（旧版三个页面都是单选，旧表 bellid 也是单个 int）。
                老数据可能挂了不止一个，那种行在后面补一个角标说清楚。
              -->
              <div v-else class="tone-cell">
                <el-select
                  v-model="row.mediaId"
                  filterable
                  clearable
                  :disabled="itemLocked($index)"
                  remote
                  reserve-keyword
                  size="small"
                  :remote-method="searchMedia"
                  :loading="mediaLoading"
                  :placeholder="$t('common.searchMediaName')"
                  class="fill"
                >
                  <el-option v-for="m in medias" :key="m.id" :label="m.name" :value="m.id" />
                </el-select>
                <el-tag
                  v-if="row.legacyMediaCount > 1"
                  type="warning"
                  size="small"
                  effect="plain"
                  :title="$t('bell.multiToneTip', { n: row.legacyMediaCount })"
                >
                  {{ $t("bell.multiToneTag", { n: row.legacyMediaCount }) }}
                </el-tag>
              </div>
            </template>
          </el-table-column>
          <!-- 旧版「播放时长」是个弹层：选时长就是 时/分/秒 三个下拉，选次数是 00~99 -->
          <el-table-column :label="$t('taskCommon.playLength')" width="204">
            <template #default="{ row, $index }">
              <span v-if="dlg.mode === 'batch'">{{ batchLenText(row) }}</span>
              <div v-else class="len-cell">
                <el-select
                  v-model="row.timelengthtype"
                  size="small"
                  style="width: 74px"
                  :disabled="itemLocked($index)"
                  @change="onLenTypeChange(row)"
                >
                  <el-option :label="$t('common.duration')" :value="1" />
                  <el-option :label="$t('bell.times')" :value="2" />
                </el-select>
                <el-time-picker
                  v-if="row.timelengthtype === 1"
                  v-model="row.lengthhms"
                  value-format="HH:mm:ss"
                  size="small"
                  :placeholder="$t('bell.hhmmss')"
                  :disabled="itemLocked($index)"
                  style="width: 116px"
                />
                <template v-else>
                  <el-input-number
                    v-model="row.timelength"
                    :min="0"
                    :max="99"
                    size="small"
                    :controls="false"
                    :disabled="itemLocked($index)"
                    style="width: 78px"
                  />
                  <span class="dlg-note">{{ $t("term.times") }}</span>
                </template>
              </div>
            </template>
          </el-table-column>
          <!-- 旧版每行三个按钮：添加(已入库则是修改) / 复制 / 删除 -->
          <el-table-column :label="$t('common.operation')" width="152" align="center">
            <template #default="{ row, $index }">
              <el-button link type="primary" :loading="row.busy" :disabled="itemLocked($index)" @click="saveOneItem($index)">
                {{ row.taskid ? $t("common.modify") : $t("common.add") }}
              </el-button>
              <el-button v-if="dlg.mode !== 'batch'" link type="primary" @click="copyItemRow($index)">{{
                $t("common.copy")
              }}</el-button>
              <el-button link type="danger" @click="removeItemAt($index)">{{ $t("common.delete") }}</el-button>
            </template>
          </el-table-column>
          <template #empty
            ><span class="dlg-note">{{ $t("bell.noLessonYet") }}</span></template
          >
        </el-table>
        <div v-if="itemsError" class="cell-err mt6">{{ itemsError }}</div>
        <el-divider content-position="left">
          {{ $t("terminalCommon.terminalList") }}
          <el-checkbox v-if="dlg.mode === 'batch'" v-model="batch.enableTerminal" class="ml8">{{
            $t("bell.unifiedTerminals")
          }}</el-checkbox>
        </el-divider>
        <div v-if="termScopeNote" class="dlg-note mb6">
          {{ termScopeNote }}
          <!-- radio 点上了就取消不掉（旧版也一样），给条回方案级视图的路 -->
          <el-button v-if="termScopeIdx >= 0" link type="primary" @click="activeItem = -1">
            {{ $t("bell.backToPlanTerminals") }}
          </el-button>
        </div>
        <el-form-item label-width="0" prop="terminals">
          <TerminalTree
            v-model="selectedTerminalIds"
            v-model:areas="terminalAreas"
            :terminals="terminals"
            :loading="terminalLoading || termLoading"
            height="260px"
            style="width: 100%"
            @search="searchTerminals"
          />
        </el-form-item>
      </el-form>

      <template #footer>
        <template v-if="dlg.mode === 'batch'">
          <el-button type="primary" :loading="dlg.saving" @click="submitBatch">{{ $t("common.modify") }}</el-button>
          <el-button @click="selectAllItems">{{ $t("common.selectAll") }}</el-button>
          <el-button @click="clearItemSelection">{{ $t("common.cancel") }}</el-button>
        </template>
        <!--
          ⚠ 这个「确定」是补上来的。

          旧版这两个页面没有提交按钮（那段 HTML 是注释掉的），保存全靠每一行的
          「添加 / 修改」。新版一度把「一次性把没入库的行和改过的方案头全存了」
          这件事挂在**关闭时的二次确认**上（弹「保存并返回 / 直接返回」）。
          需求方要求关闭直接关，那个确认框去掉了 —— 于是这件事必须有自己的按钮，
          否则整体保存就没有入口，改了方案头不点任何一行就再也存不进去。
        -->
        <el-button v-if="dlg.mode !== 'batch'" type="primary" :loading="dlg.saving" @click="submitAll">
          {{ $t("common.confirm") }}
        </el-button>
        <el-button @click="closeDialog">{{ $t("bell.goBack") }}</el-button>
      </template>
    </el-dialog>

    <!-- 删除确认 -->
    <el-dialog v-model="del.visible" :title="$t('bell.deletePlanTitle')" width="560px">
      <el-alert type="error" :closable="false" class="mb12">
        {{ $t("bell.deleteNPlans", { n: del.planNames.length }) }}
        <div class="del-names">
          <el-tag v-for="n in del.planNames" :key="n" size="small" type="danger" effect="plain">{{ n }}</el-tag>
        </div>
      </el-alert>
      <el-descriptions :column="2" border size="small">
        <el-descriptions-item :label="$t('bell.bellItems')">{{
          $t("bell.entryCount", { n: del.impact?.items ?? 0 })
        }}</el-descriptions-item>
        <el-descriptions-item :label="$t('bell.powerSubTasks')">{{
          $t("bell.entryCount", { n: del.impact?.powerSubTasks ?? 0 })
        }}</el-descriptions-item>
        <el-descriptions-item :label="$t('bell.toneLinks')">{{ del.impact?.mediaRows ?? 0 }} 行</el-descriptions-item>
        <el-descriptions-item :label="$t('bell.terminalLinks')">{{ del.impact?.terminalRows ?? 0 }} 行</el-descriptions-item>
        <el-descriptions-item :label="$t('bell.shortcutLinks')">{{ del.impact?.keyMapRows ?? 0 }} 行</el-descriptions-item>
        <el-descriptions-item :label="$t('bell.offlineLinks')">{{ del.impact?.offlineTaskRows ?? 0 }} 行</el-descriptions-item>
      </el-descriptions>
      <el-alert v-if="del.impact?.sameNameOtherTasks" type="warning" :closable="false" class="mt12">
        {{ $t("bell.sameNameOthers", { n: del.impact.sameNameOtherTasks }) }}
        <b>{{ $t("bell.newKeepsThem") }}</b> {{ $t("bell.oldWouldDelete") }}
      </el-alert>
      <template #footer>
        <el-button @click="del.visible = false">{{ $t("common.cancel") }}</el-button>
        <el-button type="danger" :loading="del.busy" @click="confirmDelete">{{ $t("common.confirmDelete") }}</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="bellPlan">
import { useI18n } from "vue-i18n";
import { computed, nextTick, reactive, ref, watch } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import type { ElTable, FormInstance, FormRules } from "element-plus";
import { CirclePlus, Delete, EditPen, WarningFilled } from "@element-plus/icons-vue";
import ProTable from "@/components/ProTable/index.vue";
import TerminalTree from "@/components/TerminalTree/index.vue";
import { useAuthStore } from "@/stores/modules/auth";
import type { ColumnProps, ProTableInstance } from "@/components/ProTable/interface";
import {
  addBellItemApi,
  copyBellPlanApi,
  createBellPlanApi,
  deleteBellItemsApi,
  deleteBellPlanApi,
  getBellPlanApi,
  getBellPlanListApi,
  previewDeleteBellPlanApi,
  setBellPlanStateApi,
  setBellItemScheduleApi,
  setBellPlanVolumeApi,
  updateBellItemApi,
  getBellItemTerminalsApi,
  updateBellPlanApi,
  type BellDeleteImpact,
  type BellItem,
  type BellPlan
} from "@/api/modules/bell";
import {
  getTaskPriorityRangeApi,
  searchTaskMediaApi,
  searchTaskTerminalsApi,
  type MediaOption,
  type TaskTerminalOption
} from "@/api/modules/task";

// 脚本里拼的文案用 t()；模板里的 $t 不用引入
const { t } = useI18n();

const authStore = useAuthStore();
const btn = computed(() => (authStore.authButtonListGet as any)?.bell ?? {});

const proTableRef = ref<ProTableInstance>();
const scopeNote = ref("");
const initParam = reactive({ orderBy: "", order: "" });

/**
 * 星期勾选的顺序，**周日在最前**。
 *
 * ⚠ exemodel 是一个七位字符串，按下标定位，第 0 位就是这一排的第一个勾。
 *   ok112 那一排是 星期日/一/二/三/四/五/六（addbelltask.html 的 displayweek），
 *   所以第 0 位 = 星期日。这里原来写成「一…六日」，第 0 位成了星期一 ——
 *   整串**错位一天**：新 Web 里勾「周一到周五」存出来是 1111100，
 *   旧系统和后台 C 服务读到的是「周日到周四」。
 */
const weekLabels = [t("week.sun"), t("week.mon"), t("week.tue"), t("week.wed"), t("week.thu"), t("week.fri"), t("week.sat")];
/** 旧界面的提前开电源下拉：0、5、10 … 55 秒，默认选中 15 秒 */
const prepowerSeconds = Array.from({ length: 12 }, (_, i) => i * 5);
/** 0~55 秒 + 1~5 分钟；旧库里若存着别的秒数（例如 1 秒），把它也列进来 */
const prepowerOptions = computed(() => {
  const list = [
    ...prepowerSeconds.map(s => ({ value: s, label: t("term.secondUnit", { n: s }) })),
    ...[1, 2, 3, 4, 5].map(m => ({ value: m * 60, label: t("task.minutesN", { n: m }) }))
  ];
  const cur = dlg.form.playback.prepower;
  if (!list.some(o => o.value === cur)) list.push({ value: cur, label: t("term.secondUnit", { n: cur }) });
  return list.sort((a, b) => a.value - b.value);
});

const onSortChange = ({ prop, order }: { prop: string; order: string | null }) => {
  if (!order) {
    initParam.orderBy = "";
    initParam.order = "";
    return;
  }
  initParam.orderBy = prop;
  initParam.order = order === "ascending" ? "asc" : "desc";
};

// 列清单严格照 :80（页面规格.txt「作息方案」）：
// 方案名称 | 起始日期 | 结束日期 | 状态 | 任务数 | 终端属性，无搜索区。
// :80 的「终端属性」是一个「查看任务」链接，落在我们这儿就是操作列。
const columns = reactive<ColumnProps<BellPlan>[]>([
  // :80 这一页的表头有勾选框，顶部按钮作用在勾选行上 —— 加上 selection 列
  { type: "selection", fixed: "left", width: 50 },
  {
    prop: "planName",
    label: t("bell.planNameSearch"),
    minWidth: 220,
    sortable: "custom",
    search: { el: "input", key: "keyword", props: { placeholder: t("bell.searchByPlanName") } }
  },
  { prop: "startdate", label: t("common.startDate"), width: 130, sortable: "custom" },
  { prop: "enddate", label: t("common.endDate"), width: 130, sortable: "custom" },
  { prop: "projectstate", label: t("common.status"), width: 110, sortable: "custom" },
  { prop: "itemCount", label: t("bell.taskCount"), width: 110, sortable: "custom" },
  // 旧版 BellManager/bellManager_form.html 的表头里「任务数」后面还有一列「所属用户」
  { prop: "ownerUserName", label: t("taskCommon.owner"), width: 110 },
  { prop: "operation", label: t("bell.terminalAttr"), fixed: "right", width: 200 }
]);

const dataCallback = (data: any) => {
  scopeNote.value = data.scopeNote ?? "";
  return { list: data.list, total: data.total, pageNum: data.pageNum, pageSize: data.pageSize };
};

const refresh = () => proTableRef.value?.getTableList();

/* ---------------- 媒体 / 终端选择 ---------------- */

const medias = ref<MediaOption[]>([]);
const mediaLoading = ref(false);
const searchMedia = async (kw: string) => {
  mediaLoading.value = true;
  try {
    const { data } = await searchTaskMediaApi(kw ?? "");
    medias.value = data ?? [];
  } finally {
    mediaLoading.value = false;
  }
};

const terminals = ref<TaskTerminalOption[]>([]);
const terminalLoading = ref(false);
const selectedTerminalIds = ref<number[]>([]);
const terminalGroupOf = reactive<Record<number, number>>({});
/** 每台终端的分区/通道掩码（terminaloftask.area），键是终端 id */
const terminalAreas = ref<Record<number, string>>({});
const searchTerminals = async (kw: string) => {
  terminalLoading.value = true;
  try {
    const { data } = await searchTaskTerminalsApi(kw ?? "");
    terminals.value = data ?? [];
    (data ?? []).forEach(t => (terminalGroupOf[t.id] = t.groupId));
  } finally {
    terminalLoading.value = false;
  }
};

/* ---------------- 方案新建 / 修改 ---------------- */

const today = () => new Date().toISOString().slice(0, 10);
const dateRange = ref<[string, string]>([today(), today()]);
const weekdays = ref<number[]>([0, 1, 2, 3, 4, 5, 6]);
/** 执行模式：1 = 每天（七位全 1），2 = 每星期（按下面的勾选拼位） */
const runMode = ref(1);

/**
 * 任务级别下拉的取值。
 *
 * 旧版是 `for(level = $getlevel; level <= 109; level++)`，下限来自当前用户，
 * 上限写死 109。这里的上下限由后端按用户权限给（priorityMin/priorityMax），
 * 语义一致，只是范围以服务端为准。
 */
const priorityOptions = computed(() => {
  const lo = dlg.priorityMin ?? 10;
  const hi = dlg.priorityMax ?? 109;
  const list = Array.from({ length: Math.max(0, hi - lo + 1) }, (_, i) => ({ value: lo + i, label: String(lo + i) }));
  // 别人建的方案可能带着一个当前用户选不到的级别，列出来标明白，
  // 否则下拉框只显示一个数字，看不出它已经超出范围（后端也会拦下）
  const cur = dlg.form.playback.priority;
  if (!list.some(o => o.value === cur)) {
    list.push({ value: cur, label: t("bell.outOfRange", { v: cur, lo, hi }) });
    list.sort((a, b) => a.value - b.value);
  }
  return list;
});

const onRunModeChange = (v: number) => {
  // 切回「每天」就把七天全勾上 —— 旧版 getexemodel 直接写 "1111111"
  if (v === 1) weekdays.value = [0, 1, 2, 3, 4, 5, 6];
};

/* 播放时长：旧版弹层里「时长」是 时/分/秒 三个下拉，「次数」是 00~99 的下拉。
   库里存的都是 timelength 一个数（时长存秒、次数存次），所以时长模式下
   界面用 lengthhms 这个 HH:mm:ss 串来编辑，提交时再换算成秒。 */
const hmsToSec = (v: string) => {
  const [h, m, sec] = String(v || "00:00:00")
    .split(":")
    .map(n => Number(n) || 0);
  return h * 3600 + m * 60 + sec;
};
const secToHms = (n: number) => {
  const t = Math.max(0, Math.min(86399, Math.floor(n || 0)));
  const p = (x: number) => String(x).padStart(2, "0");
  return `${p(Math.floor(t / 3600))}:${p(Math.floor((t % 3600) / 60))}:${p(t % 60)}`;
};

const emptyItemRow = () => ({
  /** >0 表示这一行已经写进数据库了，按钮显示「修改」 */
  taskid: 0,
  taskname: "",
  playtime: "08:00:00",
  timelengthtype: 2,
  timelength: 1,
  lengthhms: "00:00:30",
  busy: false,
  /**
   * 一个课时只挂**一个**铃声。
   *
   * 旧版三个页面（addbelltask.html / modifybell.html / modifybellall.html）
   * 那个 `<select name="setbellname">` 都没有 multiple，旧表
   * `playbelloftask.bellid` 也是单个 int —— 一课时一铃声是这套系统本来的模型。
   * 新版一度做成了多选，是偏离，按需求方要求改回来。
   *
   * 底层 mediaoftask 仍然是能挂多行的（task 模型通用），所以老数据里可能有
   * 多于一个的情况，打开时只回填第一个并明确提示，不闷声丢掉，见 openEdit。
   */
  mediaId: undefined as number | undefined,
  /** 老数据原本挂了几个铃声。>1 时界面要说清楚保存会只留第一个 */
  legacyMediaCount: 0,
  /** 这一课时自己挂了几台终端。各课时可以不一样，见 termScopeNote */
  terminalCount: 0,
  /*
   * 这一课时自己的「方案级」属性。
   *
   * 名义上方案级、实际每行 task 各存一份，可以不一致。旧版 modifybell.html 的
   * radio 一选中就把这几项灌回上面那排控件（getonetaskterminal.js），
   * 行上的「修改」再把控件当时的值只写给这一课时。这里照做。
   */
  startdate: "",
  enddate: "",
  exemodel: "",
  prepower: 15,
  defaultvolume: 80,
  priority: 10,
  datasendmodel: 0,
  israndomplay: 0,
  /** 这一课时自己挂的字幕。字幕同样是每个课时各挂一条 tasktype=30 的子任务 */
  led: null as { text: string; speed: number } | null
});
type ItemRow = ReturnType<typeof emptyItemRow>;

/** 切换时长/次数时把另一种的默认值补上，别让输入框空着 */
const onLenTypeChange = (row: ItemRow) => {
  if (row.timelengthtype === 1) {
    if (!row.lengthhms) row.lengthhms = secToHms(row.timelength || 30);
  } else if (!row.timelength) {
    row.timelength = 1;
  }
};

const dlg = reactive({
  visible: false,
  saving: false,
  /** create = 添加方案、edit = 修改方案、batch = 批量修改（旧版三个页面） */
  mode: "create" as "create" | "edit" | "batch",
  isEdit: false,
  title: "",
  originalName: "",
  applyTerminals: false,
  mixedAttrs: [] as string[],
  priorityMin: 10,
  priorityMax: 109,
  /** 新建时：第一条课时入库后，方案就已经存在了，记住它的名字 */
  savedPlanName: "",
  form: {
    planName: "",
    playback: { defaultvolume: 80, priority: 10, prepower: 15, datasendmodel: 0, israndomplay: 0 },
    /* LED 字幕：方案级，勾上才会给每个课时挂 LED 子任务 */
    ledOn: false,
    ledText: "",
    ledSpeed: 0
  },
  items: [emptyItemRow()]
});

const addItemRow = () => {
  dlg.items.push(emptyItemRow());
  itemErrors.value.push(emptyItemError());
};
/** 复制某一行 —— 旧版每行都有的「复制」按钮 */
const copyItemRow = (idx: number) => {
  const src = dlg.items[idx];
  if (!src) return;
  // 复制出来的是**新的一行**（旧版 copyRow 插的行 belltaskid 是 -1）：
  // 不能带着原来的 taskid，否则「修改」会改到被复制的那条库记录上
  dlg.items.splice(idx + 1, 0, { ...src, taskid: 0, busy: false, legacyMediaCount: 0 });
  itemErrors.value.splice(idx + 1, 0, emptyItemError());
};
/**
 * 当前对话框对应的数据库里的方案名。
 * 修改方案时就是原方案名；新建时要等第一条课时入库、方案被建出来之后才有。
 */
const currentPlanName = computed(() => (dlg.isEdit ? dlg.originalName : dlg.savedPlanName));

/**
 * 一行 → 提交给后端的条目。时长模式下把 时:分:秒 换算成秒。
 *
 * withScope = true 时把**上面那排控件 + 终端树**当前的值一起送出去，
 * 只存给这一条目 —— 行上的「添加 / 修改」走这条路。旧版
 * modifyonebellplan.php 的 URL 里带的就是这一整套：
 * getprepower / getstartdate / getenddate / getexemodel /
 * task_default_volume / task_priority_text / sendmode / getterminalid。
 *
 * ⚠ 一度只带了终端、没带那排控件，表现就是「任务级别改成 10、点了修改，
 * 重新打开还是原来那个数」—— 改动被悄悄丢掉了。
 *
 * 整体「确定」不用带：那条路径这些字段是方案级的，由 updateBellPlanApi 统一套。
 */
const itemPayload = (it: ItemRow, withScope = false) => ({
  taskname: it.taskname.trim(),
  playtime: it.playtime,
  timelengthtype: it.timelengthtype,
  timelength: it.timelengthtype === 2 ? it.timelength : hmsToSec(it.lengthhms),
  // 一课时一铃声：没选就传空数组（后端据此把这条的 mediaoftask 清干净）
  media: it.mediaId ? [{ mediaId: it.mediaId, sort: 0 }] : [],
  ...(withScope ? { terminals: terminalsForm(), applyTerminals: true, attrs: headerAttrs() } : {})
});

/** 删掉库里的课时之后：刷新列表；如果连方案都没了，就把状态收拾干净 */
const afterItemsDeleted = (planRemoved: boolean) => {
  refresh();
  if (!planRemoved) return;
  dlg.savedPlanName = "";
  if (dlg.isEdit) {
    ElMessage.warning(t("bell.lastLessonGone"));
    dlg.visible = false;
  }
};

/**
 * 行上的「删除」—— 和旧版 modifybell.html:deleteRow() 一样：
 * 这一行已经入库（有 taskid）就连库里的任务一起删，没入库的只把这一行去掉。
 */
const removeItemAt = async (idx: number) => {
  const row = dlg.items[idx];
  if (!row) return;
  if (row.taskid && currentPlanName.value) {
    try {
      await ElMessageBox.confirm(t("bell.lessonInDbDelete", { name: row.taskname }), t("bell.deleteLesson"), {
        type: "warning"
      });
    } catch {
      return;
    }
    const res = await deleteBellItemsApi(currentPlanName.value, [row.taskid]);
    afterItemsDeleted(res.data.planRemoved);
  }
  dlg.items.splice(idx, 1);
  itemErrors.value.splice(idx, 1);
};

/* 表头上方的「删除任务」：勾几行删几行，没入库的空行和已入库的课时都能删 */
const selectedItems = ref<ItemRow[]>([]);

/**
 * 序号那一列的单选：当前在改哪一行。-1 = 还没选。
 *
 * 旧版 modifybell.html 的课时表第一格就是个 radio，选中哪一行、哪一行的输入框
 * 才 enabled（getonebelltaskterminal()）。这里照做。
 */
const activeItem = ref(-1);

/**
 * 这一行现在能不能改。
 *
 * 只有**修改方案**才锁：新建是一条条往里录，锁了就没法连着填。
 * 还没选任何一行时也不锁 —— 否则一打开修改，整张表都是灰的，像坏了。
 */
const itemLocked = (idx: number) => dlg.mode === "edit" && activeItem.value >= 0 && activeItem.value !== idx;

/* ---------------- 序号单选 ↔ 终端树 ----------------

   terminaloftask 本来就是按 taskid 存的，一课时一份。旧版 modifybell.html 里
   点中课时表第一格那个 radio，除了解锁这一行的输入框，还会
   getonebelltaskterminal() → getonetaskterminal.php?taskid=，把**这一课时自己的**
   终端清单灌进下面的终端树；行上的「修改」（modifyonebellplan.php）再把树里
   当时的选择只存给这一课时。整表提交（belltaskalonemodify）才是一份套全组。

   这里照做，并且补上旧版漏掉的一件事：功放与 LED 子任务的终端清单跟着一起重写
   （见 bell.UpdateItem）。 */

/** 方案级的那一份（打开对话框时读到的）。取消选中课时就回到它 */
const planTerminalIds = ref<number[]>([]);
const planTerminalAreas = ref<Record<number, string>>({});
const planHeader = ref("");
/** 上面那排控件 + 终端树现在显示的是谁的：-1 = 整个方案，>=0 = 第几个课时 */
const termScopeIdx = ref(-1);
const termLoading = ref(false);

/** 上面那排「方案级」控件当前的取值 */
const headerAttrs = () => ({
  startdate: dateRange.value[0],
  enddate: dateRange.value[1],
  exemodel: maskFromWeekdays(),
  prepower: dlg.form.playback.prepower,
  defaultvolume: dlg.form.playback.defaultvolume,
  priority: dlg.form.playback.priority,
  datasendmodel: dlg.form.playback.datasendmodel,
  israndomplay: dlg.form.playback.israndomplay,
  // 字幕也在这一组里 —— 漏掉它就是「改了字幕点修改，重新打开还是原来那句」
  led: ledForm()
});

/** 把一组属性灌进上面那排控件 */
const applyAttrs = (a: {
  startdate: string;
  enddate: string;
  exemodel: string;
  prepower: number;
  defaultvolume: number;
  priority: number;
  datasendmodel: number;
  israndomplay: number;
  led: { text: string; speed: number } | null;
}) => {
  if (a.startdate && a.enddate) dateRange.value = [a.startdate, a.enddate];
  if (a.exemodel) applyMask(a.exemodel);
  dlg.form.playback.prepower = a.prepower;
  dlg.form.playback.defaultvolume = a.defaultvolume;
  dlg.form.playback.priority = a.priority;
  dlg.form.playback.datasendmodel = a.datasendmodel;
  dlg.form.playback.israndomplay = a.israndomplay;
  // 字幕：没挂就把开关关掉，但**保留正文**——
  // 人刚在别的课时里敲了一段字幕，切过来一看被清空了，会以为自己白打了
  dlg.form.ledOn = !!a.led?.text;
  if (a.led?.text) {
    dlg.form.ledText = a.led.text;
    dlg.form.ledSpeed = a.led.speed;
  }
};

const snapshotPlanScope = () => {
  planTerminalIds.value = [...selectedTerminalIds.value];
  planTerminalAreas.value = { ...terminalAreas.value };
  planHeader.value = JSON.stringify(headerAttrs());
  termScopeIdx.value = -1;
};

/** 把上面那排控件和终端树一起切回方案级那一份 */
const showPlanScope = () => {
  selectedTerminalIds.value = [...planTerminalIds.value];
  terminalAreas.value = { ...planTerminalAreas.value };
  if (planHeader.value) applyAttrs(JSON.parse(planHeader.value));
  termScopeIdx.value = -1;
};

/**
 * 切到第 idx 个课时：上面那排控件换成它自己的值，终端树换成它自己的清单。
 *
 * 旧版 modifybell.html 的 radio 就干这两件事 ——
 * getonetaskterminal.php 一次把 prepower / 音量 / 起止日期 / 星期 / 任务级别
 * 和终端清单一起返回，getonetaskterminal.js 逐个灌进控件。
 *
 * 还没入库的行没有 taskid，终端读不出东西来，就先保持方案级那一份 ——
 * 它本来也就是这一行点「添加」时会存进去的清单。
 */
const showItemScope = async (idx: number) => {
  const row = dlg.items[idx];
  if (!row) return;
  // 上面那排控件：库里读回来的值存在这一行上，不用再请求一次
  if (row.startdate) applyAttrs(row);
  termScopeIdx.value = idx;
  if (!row.taskid || !currentPlanName.value) {
    selectedTerminalIds.value = [...planTerminalIds.value];
    terminalAreas.value = { ...planTerminalAreas.value };
    return;
  }
  termLoading.value = true;
  try {
    const { data } = await getBellItemTerminalsApi(currentPlanName.value, row.taskid);
    const list = data.terminals ?? [];
    list.forEach(t => (terminalGroupOf[t.terminalId] = t.groupId));
    // 已删除的终端不回填，否则保存时会被存在性校验挡下
    terminalAreas.value = Object.fromEntries(list.filter(t => !t.deleted && t.area).map(t => [t.terminalId, t.area]));
    selectedTerminalIds.value = list.filter(t => !t.deleted).map(t => t.terminalId);
    row.terminalCount = list.length;
  } catch {
    // 读不到就别把树清空 —— 那样一点「修改」就会把这节课的终端全删了
    ElMessage.warning(t("bell.termLoadFailed"));
  } finally {
    termLoading.value = false;
  }
};

// 只在「修改方案」里联动：批量修改有自己的「统一终端列表」开关，
// 新建方案是一条条往里录，还没有哪一课时可读。
watch(activeItem, idx => {
  if (dlg.mode !== "edit") return;
  if (idx < 0) showPlanScope();
  else void showItemScope(idx);
});

/** 终端树上方那行字：现在显示的是谁的终端、点哪个按钮存到哪 */
const termScopeNote = computed(() => {
  if (dlg.mode !== "edit") return "";
  const idx = termScopeIdx.value;
  if (idx < 0) return t("bell.termScopePlan");
  const row = dlg.items[idx];
  if (!row) return t("bell.termScopePlan");
  if (!row.taskid) return t("bell.termScopeNewItem", { n: idx + 1 });
  return t("bell.termScopeItem", {
    n: idx + 1,
    name: row.taskname?.trim() || row.taskname,
    c: selectedTerminalIds.value.length
  });
});
const onItemSelectionChange = (rows: ItemRow[]) => (selectedItems.value = rows);
const itemCountNote = computed(() =>
  selectedItems.value.length
    ? t("bell.totalItemsSelected", { n: dlg.items.length, sel: selectedItems.value.length })
    : t("bell.totalItems", { n: dlg.items.length })
);
const removeSelectedItems = async () => {
  if (!selectedItems.value.length) return ElMessage.warning(t("bell.pickLessonsToDelete"));
  const savedIds = selectedItems.value.filter(r => r.taskid).map(r => r.taskid);
  if (savedIds.length && currentPlanName.value) {
    try {
      await ElMessageBox.confirm(
        t("bell.someLessonsInDb", { n: selectedItems.value.length, saved: savedIds.length }),
        t("bell.deleteLesson"),
        { type: "warning" }
      );
    } catch {
      return;
    }
    const res = await deleteBellItemsApi(currentPlanName.value, savedIds);
    afterItemsDeleted(res.data.planRemoved);
  }
  const kill = new Set<unknown>(selectedItems.value);
  const keep = dlg.items.map((it, i) => (kill.has(it) ? -1 : i)).filter(i => i >= 0);
  const removed = dlg.items.length - keep.length;
  // itemErrors 是按下标对齐的，得跟着一起挑，不然红字会串行
  const items = keep.map(i => dlg.items[i]);
  const errs = keep.map(i => itemErrors.value[i] ?? emptyItemError());
  dlg.items = items;
  itemErrors.value = errs;
  selectedItems.value = [];
  // 删完之后行号全变了，原来选中的那一行已经不是同一条，清掉
  activeItem.value = -1;
  itemsError.value = "";
  ElMessage.success(t("bell.removedLessons", { n: removed }));
};

/* ---------------- 必填校验 ----------------
   旧版 addbelltask.html:checkform() 的做法：方案名称 / 日期 / 执行模式 / 课时名称
   校验不过就把对应位置的 * 或提示文字刷成红色（terminal_star 是红色样式），
   并把光标定位过去。这里用 el-form 的 rules 做同一件事，表格里的行则自己维护
   一份 itemErrors，因为它不在 el-form 的 model 里。 */

/**
 * 方案名称的长度上限。
 *
 * 旧版 addbelltask.html 是 maxlength="8"，按需求方要求放宽到 12。
 * 课时名称那一格本来就是 12（旧版 modifybell.html:425），两边现在对齐了。
 *
 * ⚠ 「只能中文/字母/数字」那条限制**去掉了**（旧版 isChinaOrNumbOrLett()）：
 * 现场的名字里本来就有「第一节(上)」「课间操-上午」这种带括号带横杠的写法，
 * 挡住只是给人添堵。真正必须挡的在后端 bell.checkPlanName —— ? & = 是下发给
 * 后台 C 服务的报文分隔符，控制字符也不行，那一层不能松。
 */
const PLAN_NAME_MAX = 12;

const emptyItemError = () => ({ taskname: "", playtime: "" });
const itemErrors = ref<ReturnType<typeof emptyItemError>[]>([]);
const itemsError = ref("");
const planFormRef = ref<FormInstance>();

const planRules: FormRules = {
  planName: [
    {
      required: true,
      trigger: ["blur", "change"],
      validator: (_r, _v, cb) => {
        const v = dlg.form.planName.trim();
        if (!v) return cb(new Error(t("bell.planNamePlaceholder")));
        if (v.length > PLAN_NAME_MAX) return cb(new Error(t("bell.planNameTooLong", { n: PLAN_NAME_MAX })));
        cb();
      }
    }
  ],
  startdate: [
    {
      required: true,
      trigger: "change",
      validator: (_r, _v, cb) => (dateRange.value?.[0] ? cb() : cb(new Error(t("bell.pickStartDate"))))
    }
  ],
  enddate: [
    {
      required: true,
      trigger: "change",
      validator: (_r, _v, cb) => {
        const [a, b] = dateRange.value ?? [];
        if (!b) return cb(new Error(t("bell.pickEndDate")));
        if (a && a > b) return cb(new Error(t("bell.startAfterEnd")));
        cb();
      }
    }
  ],
  ledText: [
    {
      required: true,
      trigger: "blur",
      validator: (_r, _v, cb) => (dlg.form.ledOn && !dlg.form.ledText.trim() ? cb(new Error(t("bell.ledContentRequired"))) : cb())
    }
  ],
  weekdays: [
    {
      required: true,
      trigger: "change",
      validator: (_r, _v, cb) => (runMode.value === 2 && !weekdays.value.length ? cb(new Error(t("bell.pickWeekday"))) : cb())
    }
  ],
  terminals: [
    {
      required: true,
      trigger: "change",
      validator: (_r, _v, cb) => (selectedTerminalIds.value.length ? cb() : cb(new Error(t("bell.atLeastOneTerminal"))))
    }
  ]
};

/** 方案头（名称 / 日期 / 执行模式 / 终端）校验，不过就在字段下方出红字 */
const validateHeader = async () =>
  await (planFormRef.value?.validate().then(
    () => true,
    () => false
  ) ?? Promise.resolve(true));

/** 校验某一行：课时名称、方案内重名、作息时间 */
const validateItemAt = (idx: number) => {
  const it = dlg.items[idx];
  if (!it) return false;
  while (itemErrors.value.length < dlg.items.length) itemErrors.value.push(emptyItemError());
  const err = emptyItemError();
  itemErrors.value[idx] = err;
  const name = (it.taskname ?? "").trim();
  if (!name) {
    err.taskname = t("bell.lessonNameRequired");
  } else {
    // 后端按 (info, taskname) 定位条目，方案内重名会互相覆盖
    const dup = dlg.items.findIndex((o, i) => i !== idx && (o.taskname ?? "").trim() === name);
    if (dup >= 0) err.taskname = t("bell.dupWithRow", { n: dup + 1 });
  }
  if (!/^\d{2}:\d{2}:\d{2}$/.test(it.playtime ?? "")) err.playtime = t("bell.pickBellTime");
  return !err.taskname && !err.playtime;
};

/** 点「确定」时校验课时表：只管还没入库的行，已入库的行由行上的「修改」单独负责 */
const validateItems = () => {
  itemsError.value = "";
  itemErrors.value = dlg.items.map(() => emptyItemError());
  if (!dlg.items.length && !currentPlanName.value) {
    itemsError.value = t("bell.atLeastOneLesson");
    return false;
  }
  let ok = true;
  dlg.items.forEach((it, i) => {
    if (!it.taskid && !validateItemAt(i)) ok = false;
  });
  return ok;
};

/**
 * 行上的「添加 / 修改」—— 旧版 addbelltask.html 的行内「添加」就是**真写库**
 * （addonebellplan.php），存过的行按钮变「修改」，再点走 modifyonebellplan.php。
 * 新建方案时第一条课时会把方案本身一起建出来，之后的课时挂到这个方案下。
 */
const saveOneItem = async (idx: number) => {
  const row = dlg.items[idx];
  if (!row || row.busy) return;
  const headerOk = await validateHeader();
  const rowOk = validateItemAt(idx);
  if (!headerOk || !rowOk) return;
  itemsError.value = "";

  row.busy = true;
  try {
    if (row.taskid && currentPlanName.value) {
      await updateBellItemApi(currentPlanName.value, row.taskid, itemPayload(row, true));
      row.terminalCount = selectedTerminalIds.value.length;
      // 这一行缓存的属性要跟着更新：不然再点一次序号，控件又被灌回旧值
      Object.assign(row, headerAttrs());
      ElMessage.success(t("bell.lessonSaved", { name: row.taskname.trim() }));
    } else if (!currentPlanName.value) {
      const res = await createBellPlanApi({
        planName: dlg.form.planName.trim(),
        schedule: scheduleForm(),
        playback: dlg.form.playback,
        terminals: terminalsForm(),
        led: ledForm(),
        items: [itemPayload(row)]
      });
      dlg.savedPlanName = res.data.planName;
      row.taskid = res.data.taskIds?.[0] ?? 0;
      ElMessage.success(t("bell.planCreatedWithLesson", { plan: res.data.planName, lesson: row.taskname.trim() }));
      (res.data.warnings ?? []).forEach(w => ElMessage.warning(w));
    } else {
      const res = await addBellItemApi(currentPlanName.value, itemPayload(row, true));
      row.taskid = res.data.taskIds?.[0] ?? 0;
      row.terminalCount = selectedTerminalIds.value.length;
      Object.assign(row, headerAttrs());
      ElMessage.success(t("bell.lessonStored", { name: row.taskname.trim() }));
      (res.data.warnings ?? []).forEach(w => ElMessage.warning(w));
    }
    refresh();
  } finally {
    row.busy = false;
  }
};

/** 打开对话框时把上一次留下的红字清掉 */
const resetPlanErrors = async () => {
  itemErrors.value = dlg.items.map(() => emptyItemError());
  itemsError.value = "";
  selectedItems.value = [];
  // 每次打开对话框都从「谁都没选」开始：一进来整张表就是灰的会像坏了
  activeItem.value = -1;
  await nextTick();
  planFormRef.value?.clearValidate();
};

/** exemodel（7 位 0/1，第 0 位是周日）→ 勾中的星期下标 */
const maskToDays = (mask: string) => {
  const days: number[] = [];
  for (let i = 0; i < 7 && i < (mask ?? "").length; i++) if (mask[i] === "1") days.push(i);
  return days;
};
const maskFromWeekdays = () => {
  const arr = Array(7).fill("0");
  weekdays.value.forEach(i => (arr[i] = "1"));
  return arr.join("");
};
const applyMask = (mask: string) => {
  weekdays.value = maskToDays(mask);
  // 七天全勾就是「每天」，否则是「每星期」
  runMode.value = weekdays.value.length === 7 ? 1 : 2;
};

/* ---------------- 调整音量 ---------------- */

const vol = reactive({ visible: false, saving: false, planNames: [] as string[], value: 80 });

const openVolume = (raw: Record<string, any>[]) => {
  const rows = (raw ?? []) as unknown as BellPlan[];
  if (!rows.length) return ElMessage.warning(t("bell.pickPlanFirst"));
  // 勾几个调几个 —— 一次把几个方案的音量统一到同一个值，是现场常干的事
  vol.planNames = rows.map(r => r.planName);
  // 列表行里没带音量（那是方案级属性，在详情里），默认给 80，用户自己拖
  vol.value = 80;
  vol.visible = true;
};

const submitVolume = async () => {
  vol.saving = true;
  try {
    // 勾几个调几个。后端按方案名一条条改，所以这里顺序发完 ——
    // 中途少发一条，表现就是「有的方案没跟着变」。
    let affected = 0;
    for (const name of vol.planNames) {
      const { data } = await setBellPlanVolumeApi(name, vol.value);
      affected += data.affectedTasks;
    }
    vol.visible = false;
    ElMessage.success(
      vol.planNames.length === 1
        ? t("bell.volumeChanged", { name: vol.planNames[0], vol: vol.value, n: affected })
        : t("bell.volumeChangedN", { plans: vol.planNames.length, vol: vol.value, n: affected })
    );
    refresh();
  } finally {
    vol.saving = false;
  }
};

/* ---------------- 智能排课：把课时统一挪到新日期段 ---------------- */

const schedTableRef = ref<InstanceType<typeof ElTable>>();
const sched = reactive({
  visible: false,
  saving: false,
  planName: "",
  list: [] as BellItem[],
  checked: [] as BellItem[],
  range: [today(), today()] as [string, string],
  weekdays: [0, 1, 2, 3, 4, 5, 6] as number[],
  /** 方案里各课时的日期段不一致时给个提醒 —— 逐条改日期本来就会造成这种局面 */
  mixed: false,
  rangeText: ""
});

const isChecked = (row: BellItem) => sched.checked.some(r => r.taskid === row.taskid);
/** 勾选框拼成 exemodel：7 位 0/1，第 0 位是周日 */
const schedMask = computed(() => {
  const arr = Array(7).fill("0");
  sched.weekdays.forEach(i => (arr[i] = "1"));
  return arr.join("");
});
const schedWeekText = computed(() => {
  if (schedMask.value === "1111111") return t("taskCommon.everyDay");
  if (!sched.weekdays.length) return t("bell.unselected");
  return [...sched.weekdays]
    .sort((a, b) => a - b)
    .map(i => weekLabels[i])
    .join("");
});
const lenText = (sec: number) => {
  const p = (n: number) => String(n).padStart(2, "0");
  return `${p(Math.floor(sec / 3600))}:${p(Math.floor((sec % 3600) / 60))}:${p(sec % 60)}`;
};

const openSchedule = async (raw: Record<string, any>) => {
  const planName = String(raw?.planName ?? "");
  if (!planName) return;
  const { data } = await getBellPlanApi(planName);
  // 后端已按 playtime 排好序，这里再排一次，免得将来接口顺序变了界面跟着乱
  const list = [...data.items].sort((a, b) => a.playtime.localeCompare(b.playtime) || a.taskid - b.taskid);
  const ranges = new Set(list.map(it => `${it.startdate}~${it.enddate}|${it.exemodel}`));
  Object.assign(sched, {
    visible: true,
    saving: false,
    planName,
    list,
    checked: [],
    range: [data.schedule.startdate || today(), data.schedule.enddate || today()],
    // 星期默认就是方案现在的值，用户不动它，点确定也不会把星期改掉
    weekdays: maskToDays(data.schedule.exemodel),
    mixed: ranges.size > 1,
    rangeText: list.length ? `${data.schedule.startdate} ~ ${data.schedule.enddate}` : "—"
  });
  await nextTick();
  schedTableRef.value?.clearSelection();
};

const submitSchedule = async () => {
  if (!sched.checked.length) return ElMessage.warning(t("bell.pickLessonsToChange"));
  const [start, end] = sched.range ?? [];
  if (!start || !end) return ElMessage.warning(t("bell.pickNewRange"));
  if (start > end) return ElMessage.warning(t("bell.startLaterThanEnd"));
  if (!sched.weekdays.length) return ElMessage.warning(t("bell.atLeastOneWeekday"));

  sched.saving = true;
  try {
    const ids = sched.checked.map(r => r.taskid);
    const { data } = await setBellItemScheduleApi(sched.planName, ids, {
      startdate: start,
      enddate: end,
      exemodel: schedMask.value
    });
    ElMessage.success(t("bell.movedLessons", { n: data.changed, start, end, week: schedWeekText.value }));
    // 就地刷新，好让「当前日期段」这一列立刻显示新值
    await openSchedule({ planName: sched.planName });
    refresh();
  } finally {
    sched.saving = false;
  }
};

const openCreate = async () => {
  // 任务级别的可选区间由用户组级别决定，新建时先问一次服务端，
  // 免得默认值落在区间外、点提交才被拒
  const { data: pr } = await getTaskPriorityRangeApi();
  Object.assign(dlg, {
    visible: true,
    saving: false,
    mode: "create",
    isEdit: false,
    title: t("bell.addPlan"),
    originalName: "",
    applyTerminals: true,
    mixedAttrs: [],
    priorityMin: pr.priorityMin ?? 10,
    priorityMax: pr.priorityMax ?? 109,
    savedPlanName: "",
    form: {
      planName: "",
      playback: {
        defaultvolume: 80,
        priority: pr.priorityMin ?? 10,
        prepower: 15,
        datasendmodel: 0,
        israndomplay: 0
      },
      ledOn: false,
      ledText: "",
      ledSpeed: 0
    },
    items: [emptyItemRow()]
  });
  dateRange.value = [today(), today()];
  weekdays.value = [0, 1, 2, 3, 4, 5, 6];
  runMode.value = 1; // 默认「每天」，与旧版下拉的第一项一致
  selectedTerminalIds.value = [];
  terminalAreas.value = {};
  snapshotPlanScope();
  markHeaderClean();
  await resetPlanErrors();
  await Promise.all([searchTerminals(""), searchMedia("")]);
};

const openEdit = async (row: BellPlan, mode: "edit" | "batch" = "edit") => {
  const { data } = await getBellPlanApi(row.planName);
  Object.assign(dlg, {
    visible: true,
    saving: false,
    mode,
    isEdit: true,
    title: `${mode === "batch" ? t("bell.batchEdit") : t("bell.editPlan")} · ${data.planName}`,
    originalName: data.planName,
    // 旧版 modifybell.html 的「确定」是连终端一起写回去的
    applyTerminals: true,
    mixedAttrs: data.mixedAttrs ?? [],
    priorityMin: data.priorityMin ?? 10,
    priorityMax: data.priorityMax ?? 109,
    savedPlanName: "",
    form: {
      planName: data.planName,
      playback: { ...data.playback },
      ledOn: !!data.led?.text,
      ledText: data.led?.text ?? "",
      ledSpeed: data.led?.speed ?? 0
    },
    items: data.items.map(it => ({
      taskid: it.taskid,
      taskname: it.taskname,
      playtime: it.playtime,
      /*
       * ⚠ 判据是 `=== 2 ? 次数 : 秒数`，**不能写成 `=== 1 ? 秒数 : 次数`**。
       *
       * 库里躺着一批 timelengthtype = 0 的存量行（现网作息条目全是 0，
       * timelength 是 30 / 210 / 1500 这种秒数）。旧版两个表单的第一个 radio
       * 「时长」都带 checked（modifybell.html:191），两个分支都不命中时选中的
       * 就是它 —— 所以 0 等同于 1。
       *
       * 这里一度按次数归一，后果是 1500 秒的大课间显示成「循环 1500 次」，
       * 再一保存就把 timelengthtype 真写成 2，数据从此坏掉。
       * 服务端也归一了一次（task.NormLengthType），这里再挡一道。
       */
      timelengthtype: it.timelengthtype === 2 ? 2 : 1,
      timelength: it.timelengthtype === 2 ? it.timelength : 1,
      lengthhms: it.timelengthtype === 2 ? "00:00:30" : secToHms(it.timelength),
      busy: false,
      // 一课时一铃声：只回填第一个。老数据可能挂了不止一个 ——
      // 记下原来有几个，界面上标出来，保存时会只剩第一个，不能闷声丢掉。
      mediaId: it.media[0]?.mediaId,
      legacyMediaCount: it.media.length,
      terminalCount: it.terminalCount ?? 0,
      startdate: it.startdate,
      enddate: it.enddate,
      exemodel: it.exemodel,
      prepower: it.prepower,
      defaultvolume: it.defaultvolume,
      priority: it.priority,
      datasendmodel: it.datasendmodel,
      israndomplay: it.israndomplay,
      led: it.led ? { text: it.led.text, speed: it.led.speed } : null
    }))
  });
  // 老数据里挂了多个铃声的，开局就说清楚 —— 等人保存完才发现少了东西就晚了
  const multi = data.items.filter(it => it.media.length > 1).length;
  if (multi > 0) {
    ElMessage.warning(t("bell.multiToneWarn", { n: multi }));
  }
  // 条目用到的媒体未必在搜索结果的前几十条里，补进下拉，免得只显示 ID
  data.items.forEach(it =>
    it.media.forEach(m => {
      if (!medias.value.some(x => x.id === m.mediaId)) {
        medias.value.push({ id: m.mediaId, name: m.name, size: m.size, timelength: 0, folderId: 0, folderName: "" });
      }
    })
  );
  dateRange.value = [data.schedule.startdate, data.schedule.enddate];
  applyMask(data.schedule.exemodel);
  data.terminals.forEach(t => (terminalGroupOf[t.terminalId] = t.groupId));
  // 把库里已有的分区掩码回填给树，改的时候才看得出原来选了哪几个分区
  terminalAreas.value = Object.fromEntries(data.terminals.filter(t => !t.deleted && t.area).map(t => [t.terminalId, t.area]));
  // 已删除的终端不回填，否则保存时会被存在性校验挡下
  selectedTerminalIds.value = data.terminals.filter(t => !t.deleted).map(t => t.terminalId);
  // 选中某个课时会把树换成那一课时自己的清单，取消选中要能换回来
  snapshotPlanScope();
  markHeaderClean();
  await resetPlanErrors();
  await searchTerminals("");
};

/** 批量修改：和修改方案同一个对话框，只是多了「统一设置」那一栏（旧版 bellmodifyall.php） */
const openBatch = async (row: Record<string, any>) => {
  Object.assign(batch, {
    enableMedia: false,
    mediaId: undefined,
    enableLen: false,
    lenType: 2,
    lenTimes: 1,
    lenHms: "00:00:30",
    enableTerminal: false
  });
  await openEdit(row as BellPlan, "batch");
};

/** LED 字幕：没勾或没填就传 null，后端据此把已有的 LED 子任务整批删掉 */
const ledForm = () =>
  dlg.form.ledOn && dlg.form.ledText.trim() ? { text: dlg.form.ledText.trim(), speed: dlg.form.ledSpeed } : null;

/** 方案头里的排期与终端清单，行内保存和整体提交共用 */
const scheduleForm = () => ({
  startdate: dateRange.value[0],
  enddate: dateRange.value[1],
  exemodel: maskFromWeekdays()
});
const terminalsForm = () =>
  selectedTerminalIds.value.map(id => ({
    terminalId: id,
    groupId: terminalGroupOf[id] ?? 0,
    // 分区/通道掩码：树上逐台勾的结果，没勾过的照后端默认（全通道）
    area: terminalAreas.value[id] ?? "11111111"
  }));

/* ----- 方案头的「脏」判断 -----
   旧版的添加/修改页底部没有提交按钮（那段 HTML 是注释掉的），方案头是跟着
   每次行内「添加/修改」一起送出去的。这里照做：行内保存前先把改过的方案头
   落一次；离开时如果还有没落的改动，明确问一句，而不是像旧版那样默默丢掉。 */
const headerSnapshot = ref("");
const headerFingerprint = () =>
  JSON.stringify({
    name: dlg.form.planName.trim(),
    playback: dlg.form.playback,
    schedule: scheduleForm(),
    terminals: [...selectedTerminalIds.value].sort((a, b) => a - b)
  });
const markHeaderClean = () => (headerSnapshot.value = headerFingerprint());
const headerDirty = computed(() => !!currentPlanName.value && headerFingerprint() !== headerSnapshot.value);

/** 把方案头写回库（方案级：整组条目一起改，含改名） */
const flushHeader = async () => {
  if (!currentPlanName.value || !headerDirty.value) return null;
  const res = await updateBellPlanApi({
    planName: currentPlanName.value,
    newPlanName: dlg.form.planName.trim(),
    schedule: scheduleForm(),
    playback: dlg.form.playback,
    terminals: terminalsForm(),
    led: ledForm(),
    applyTerminals: dlg.mode === "batch" ? batch.enableTerminal : true
  });
  if (res.data.renamed) {
    if (dlg.mode === "create") dlg.savedPlanName = res.data.planName;
    else dlg.originalName = res.data.planName;
  }
  markHeaderClean();
  return res.data;
};

/** 把还没入库的课时一次性补进去（原来的「确定」按钮做的事） */
const saveAllPending = async () => {
  const formOk = await validateHeader();
  const itemsOk = validateItems();
  if (!formOk || !itemsOk) return false;
  const pending = dlg.items.filter(it => !it.taskid);

  dlg.saving = true;
  try {
    if (currentPlanName.value) {
      for (const it of pending) {
        const res = await addBellItemApi(currentPlanName.value, itemPayload(it));
        it.taskid = res.data.taskIds?.[0] ?? 0;
        (res.data.warnings ?? []).forEach(w => ElMessage.warning(w));
      }
      const upd = await flushHeader();
      const parts: string[] = [];
      if (pending.length) parts.push(t("bell.pendingLessons", { n: pending.length }));
      if (upd?.affectedRows) parts.push(t("bell.updatedRows", { n: upd.affectedRows }));
      if (upd?.renamed) parts.push(t("bell.planRenamed"));
      ElMessage.success(parts.length ? `${parts.join("，")}` : t("bell.planSaved"));
    } else {
      const res = await createBellPlanApi({
        planName: dlg.form.planName.trim(),
        schedule: scheduleForm(),
        playback: dlg.form.playback,
        terminals: terminalsForm(),
        led: ledForm(),
        items: dlg.items.map(it => itemPayload(it))
      });
      dlg.items.forEach((it, i) => (it.taskid = res.data.taskIds?.[i] ?? 0));
      dlg.savedPlanName = res.data.planName;
      markHeaderClean();
      ElMessage.success(t("bell.createdItems", { n: res.data.createdItems }));
      (res.data.warnings ?? []).forEach(w => ElMessage.warning(w));
    }
    refresh();
    return true;
  } finally {
    dlg.saving = false;
  }
};

/** 「确定」：把没入库的行和改过的方案头一次性落库，成功就关掉 */
const submitAll = async () => {
  if (await saveAllPending()) dlg.visible = false;
};

/**
 * 「返回」—— 直接关，不再问第二遍。
 *
 * 这里原来会先数一下「方案头改过没有 / 有几行还没入库」，再弹一个
 * 「保存并返回 / 直接返回」的确认框。按需求方要求去掉：这一页的保存本来就是
 * **行内即时生效**的（每一行的「添加 / 修改」当场写库，与旧版
 * addonebellplan.php / modifyonebellplan.php 一样），底下还补了一个「确定」整体落一次，
 * 想存的人有两处能存，关窗口再拦一道纯属添堵。
 *
 * ⚠ 代价说清楚：没点过「添加 / 修改」也没点「确定」的改动，关掉就没了。
 * 这与旧版一致 —— 旧版那两个页面压根没有提交按钮，离开就是离开。
 */
const closeDialog = () => {
  dlg.visible = false;
};

/* ---------------- 批量修改（旧版 bellmodifyall.php） ---------------- */

const itemTableRef = ref<InstanceType<typeof ElTable>>();
const batch = reactive({
  enableMedia: false,
  mediaId: undefined as number | undefined,
  enableLen: false,
  lenType: 2,
  lenTimes: 1,
  lenHms: "00:00:30",
  enableTerminal: false
});

/** 批量修改时课时表里那两格是只读的：启用了统一设置就显示统一值，否则显示这一行原来的值 */
const batchMediaName = (row: ItemRow) => {
  // 统一铃声勾了但还没选，就先显示成「未设置」
  const id = batch.enableMedia ? batch.mediaId : row.mediaId;
  if (!id) return "";
  return medias.value.find(m => m.id === id)?.name ?? `#${id}`;
};
const batchLenText = (row: ItemRow) => {
  if (batch.enableLen) return batch.lenType === 1 ? batch.lenHms : t("term.loopUnit", { n: batch.lenTimes });
  return row.timelengthtype === 1 ? row.lengthhms : t("term.loopUnit", { n: row.timelength });
};

const selectAllItems = () => dlg.items.forEach(r => itemTableRef.value?.toggleRowSelection(r, true));
const clearItemSelection = () => itemTableRef.value?.clearSelection();

/**
 * 批量修改的「修改」：对**勾中的**课时统一应用
 * —— 各行自己的名称/时间 + 勾了「统一」的铃声、播放时长，
 * 外加方案头（旧版是逐行写同一份方案头，新版方案头本来就是方案级的，一次落到底）。
 */
const submitBatch = async () => {
  const formOk = await validateHeader();
  if (!formOk) return;
  const picked = selectedItems.value.filter(r => r.taskid);
  const fresh = selectedItems.value.filter(r => !r.taskid);
  if (!picked.length && !fresh.length) return ElMessage.warning(t("bell.pickLessonsToEdit"));
  if (batch.enableMedia && !batch.mediaId) return ElMessage.warning(t("bell.pickOneTone"));
  let ok = true;
  selectedItems.value.forEach(r => {
    const i = dlg.items.indexOf(r);
    if (i >= 0 && !validateItemAt(i)) ok = false;
  });
  if (!ok) return;

  dlg.saving = true;
  try {
    for (const row of selectedItems.value) {
      const payload = itemPayload(row);
      if (batch.enableMedia) payload.media = [{ mediaId: batch.mediaId!, sort: 0 }];
      if (batch.enableLen) {
        payload.timelengthtype = batch.lenType;
        payload.timelength = batch.lenType === 1 ? hmsToSec(batch.lenHms) : batch.lenTimes;
      }
      if (row.taskid) {
        await updateBellItemApi(currentPlanName.value, row.taskid, payload);
      } else {
        const res = await addBellItemApi(currentPlanName.value, payload);
        row.taskid = res.data.taskIds?.[0] ?? 0;
      }
    }
    const upd = await flushHeader();
    const parts = [t("bell.editedLessons", { n: picked.length })];
    if (fresh.length) parts.push(t("bell.freshCount", { n: fresh.length }));
    if (upd?.renamed) parts.push(t("bell.planRenamed"));
    ElMessage.success(parts.join("，"));
    dlg.visible = false;
    refresh();
  } finally {
    dlg.saving = false;
  }
};

/* ---------------- 启停 / 复制 / 删除 ---------------- */

const del = reactive({
  visible: false,
  busy: false,
  /** 这次要删的全部方案名。勾几个就是几个 —— 不是只删第一个 */
  planNames: [] as string[],
  /** 各方案影响面的合计 */
  impact: null as BellDeleteImpact | null
});

// batchCmd 把顶部按钮的「勾选行」接到既有的单行命令上。
//
// 作息方案的启停/删除/复制在后端都是**按方案名**操作的，一次只能一条，
// 所以这里就是顺序跑一遍勾选的行。复制按钮在只勾一行时才可点（批量复制没有意义）。
// rows 来自 ProTable 的 selectedList，它的类型是宽松的 Record，这里收窄一次。
const batchCmd = async (cmd: string, raw: Record<string, any>[]) => {
  const rows = (raw ?? []) as unknown as BellPlan[];
  if (!rows.length) return ElMessage.warning(t("bell.pickPlanFirst"));
  if (cmd === "copy") {
    return onMoreCmd("copy", rows[0]);
  }
  if (cmd === "delete") {
    // 勾了几个就删几个。
    //
    // ⚠ 这里原来只删 rows[0]，还弹一句「一次删一个」—— 勾两个只没了一个，
    //   剩下那个还在列表里，人以为没删掉又点一次。按需求方要求改成全删。
    //
    // 影响面还是要看，所以把每个方案的预览拉回来**合并成一屏**：
    // 连开 N 个确认框才是真的把人淹掉。
    return openDelete(rows);
  }
  for (const r of rows) await onMoreCmd(cmd, r);
};

const onMoreCmd = async (cmd: string | number | object, row: BellPlan) => {
  switch (cmd) {
    case "enable":
    case "disable": {
      const enable = cmd === "enable";
      const { data } = await setBellPlanStateApi(row.planName, enable);
      ElMessage.success(
        t("bell.stateChanged", { state: enable ? t("bell.enabledWord") : t("bell.stoppedWord"), n: data.affectedTasks })
      );
      if (data.offlineStateReset) {
        ElMessage.warning(t("bell.offlineReset"));
      }
      refresh();
      break;
    }
    case "copy": {
      const { value } = await ElMessageBox.prompt(t("bell.newPlanName"), t("bell.copyPlan"), {
        inputValue: t("bell.copySuffix", { name: row.planName }),
        inputValidator: v => (v && v.trim() ? true : t("bell.nameEmpty"))
      });
      const { data } = await copyBellPlanApi(row.planName, value.trim());
      ElMessage.success(
        t("bell.copiedItems", { items: data.copiedItems, power: data.copiedPowerSubTasks }) +
          t("bell.copiedRows", { media: data.copiedMediaRows, terms: data.copiedTerminalRows })
      );
      refresh();
      break;
    }
  }
};

/**
 * 打开删除确认：把选中的每个方案的影响面拉回来，合并成一屏。
 *
 * 逐个预览是因为后端就是按方案名一条条算的；合并展示是因为
 * 「确认 N 次」对人是折磨，而这几个数字加起来看反而更清楚。
 */
const openDelete = async (rows: BellPlan[]) => {
  del.busy = true;
  try {
    const impacts = await Promise.all(rows.map(r => previewDeleteBellPlanApi(r.planName).then(res => res.data)));
    del.planNames = rows.map(r => r.planName);
    del.impact = impacts.reduce(
      (a, b) => ({
        planName: "",
        items: a.items + b.items,
        powerSubTasks: a.powerSubTasks + b.powerSubTasks,
        mediaRows: a.mediaRows + b.mediaRows,
        terminalRows: a.terminalRows + b.terminalRows,
        keyMapRows: a.keyMapRows + b.keyMapRows,
        offlineTaskRows: a.offlineTaskRows + b.offlineTaskRows,
        offlineMediaRows: a.offlineMediaRows + b.offlineMediaRows,
        sameNameOtherTasks: a.sameNameOtherTasks + b.sameNameOtherTasks
      }),
      {
        planName: "",
        items: 0,
        powerSubTasks: 0,
        mediaRows: 0,
        terminalRows: 0,
        keyMapRows: 0,
        offlineTaskRows: 0,
        offlineMediaRows: 0,
        sameNameOtherTasks: 0
      } as BellDeleteImpact
    );
    del.visible = true;
  } finally {
    del.busy = false;
  }
};

const confirmDelete = async () => {
  del.busy = true;
  try {
    // 一条条发（后端按方案名删），但**全部发完**才收工 —— 中途少发一条，
    // 表现就是「勾了两个只删掉一个」。
    let items = 0;
    let power = 0;
    for (const name of del.planNames) {
      const { data } = await deleteBellPlanApi(name);
      items += data.items;
      power += data.powerSubTasks;
    }
    ElMessage.success(t("bell.deletedItems", { items, power }));
    del.visible = false;
    refresh();
  } finally {
    del.busy = false;
  }
};
</script>

<style scoped lang="scss">
.header-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
}
.plan-name {
  font-weight: 500;
}
.warn-icon {
  margin-left: 4px;
  vertical-align: middle;
  color: var(--el-color-warning);
}
.mixed-tag {
  margin-left: 6px;
}
.media-tag {
  margin-right: 4px;
}
.muted {
  color: var(--el-text-color-placeholder);
}
.form-tip {
  margin-left: 8px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
  &.block {
    display: block;
    margin-left: 0;
    line-height: 1.6;
  }
}
.opt-sub {
  margin-left: 8px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.fill {
  width: 100%;
}

/* 铃声那一格：选择器 + 老数据的「原有 N 个」角标 */
.tone-cell {
  display: flex;
  gap: 4px;
  align-items: center;
}

/* 序号那一列的单选：只要那个圆点和数字，不要 Element 默认的一堆左右留白 */
.idx-radio {
  height: auto;
  margin-right: 0;
  :deep(.el-radio__label) {
    padding-left: 4px;
    font-size: 12px;
  }
}
.item-row {
  display: flex;
  gap: 8px;
  align-items: center;
  margin-bottom: 8px;
}
.item-head {
  display: flex;
  gap: 8px;
  align-items: center;
  margin-bottom: 6px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.drawer-bar {
  margin-bottom: 12px;
}
.mb12 {
  margin-bottom: 12px;
}
.mt12 {
  margin-top: 12px;
}
.mt6 {
  margin-top: 6px;
}
.mb6 {
  margin-bottom: 6px;
}
.ml8 {
  margin-left: 8px;
}
.dlg-note {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.len-cell {
  display: flex;
  gap: 6px;
  align-items: center;
}

/* ---- 智能排课 ---- */
.sched-head {
  padding: 12px 14px;
  margin-bottom: 12px;
  background: var(--el-fill-color-lighter);
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 6px;
}
.sched-sum {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  padding-bottom: 10px;
  margin-bottom: 10px;
  border-bottom: 1px dashed var(--el-border-color);
}
.sched-plan {
  margin-right: 2px;
  font-size: 15px;
  font-weight: 600;
}
.sched-pick {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
}
.sched-label {
  font-weight: 500;
  color: var(--el-text-color-primary);
}
.sched-note {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.sched-foot {
  float: left;
  padding-left: 4px;
  font-size: 13px;
  line-height: 32px;
  color: var(--el-text-color-secondary);
}
.time-cell {
  font-family: ui-monospace, sfmono-regular, menlo, monospace;
  font-weight: 500;
  letter-spacing: 0.3px;
}
.wk {
  display: inline-block;
  width: 17px;
  margin-right: 1px;
  font-size: 12px;
  line-height: 17px;
  color: var(--el-text-color-placeholder);
  text-align: center;
  white-space: nowrap;
  border-radius: 3px;
  &.on {
    color: #ffffff;
    background: var(--el-color-primary);
  }
}
.date-chg {
  color: var(--el-text-color-placeholder);
  text-decoration: line-through;
}
.date-new {
  font-weight: 500;
  color: var(--el-color-primary);
}
.batch-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  padding: 8px 10px;
  margin-bottom: 8px;
  background: var(--el-fill-color-lighter);
  border-radius: 4px;
}

/* 旧版 checkform() 用红色 * 和红字提示必填项，这里照搬 */
.req-star {
  margin-right: 2px;
  color: var(--el-color-danger);
}
.cell-err {
  margin-top: 2px;
  font-size: 12px;
  line-height: 1.3;
  color: var(--el-color-danger);
}
.is-bad {
  :deep(.el-input__wrapper) {
    box-shadow: 0 0 0 1px var(--el-color-danger) inset;
  }
}
</style>
