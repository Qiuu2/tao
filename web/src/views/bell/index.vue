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
            <el-button type="primary" :disabled="!btn.add" @click="openCreate">添加方案</el-button>
            <el-button type="danger" :disabled="!btn.delete || !scope.isSelected" @click="batchCmd('delete', scope.selectedList)">
              删除方案
            </el-button>
            <el-button
              type="primary"
              :disabled="!btn.control || !scope.isSelected"
              @click="batchCmd('enable', scope.selectedList)"
            >
              启用方案
            </el-button>
            <el-button
              type="danger"
              :disabled="!btn.control || !scope.isSelected"
              @click="batchCmd('disable', scope.selectedList)"
            >
              停用方案
            </el-button>
            <el-button
              type="warning"
              :disabled="!btn.edit || scope.selectedList.length !== 1"
              @click="openVolume(scope.selectedList)"
            >
              调整音量
            </el-button>
            <el-button
              type="primary"
              :disabled="!btn.copy || scope.selectedList.length !== 1"
              @click="batchCmd('copy', scope.selectedList)"
            >
              复制方案
            </el-button>
            <el-button type="primary" :disabled="!btn.add" @click="openSmart">智能排课</el-button>
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
            以下时刻有多个条目同时打铃：<br />
            {{ scope.row.duplicateTimes.join("、") }}
          </template>
          <el-icon class="warn-icon"><WarningFilled /></el-icon>
        </el-tooltip>
      </template>

      <template #projectstate="scope">
        <!-- 0 = 启用、1 = 停用，与 audioserver.sql 的列注释相反 -->
        <el-tag v-if="scope.row.projectstate === 0" type="success" size="small">启用</el-tag>
        <el-tag v-else type="info" size="small">停用</el-tag>
        <el-tooltip v-if="scope.row.mixedState" content="方案内各条目的启停状态不一致，这里显示的是多数派" placement="top">
          <el-tag type="warning" size="small" effect="plain" class="mixed-tag">不一致</el-tag>
        </el-tooltip>
      </template>

      <template #itemCount="scope">
        <el-tag size="small" effect="plain">{{ scope.row.itemCount }} 条</el-tag>
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
        <el-button type="primary" link :icon="View" @click="openItems(scope.row)">查看任务</el-button>
        <el-button type="primary" link :icon="EditPen" :disabled="!btn.edit" @click="openEdit(scope.row)">修改</el-button>
      </template>
    </ProTable>

    <!-- 调整音量：整个方案改一次，功放子任务一起改 -->
    <el-dialog v-model="vol.visible" title="调整音量" width="440px">
      <el-slider v-model="vol.value" :min="0" :max="100" show-input />
      <template #footer>
        <el-button @click="vol.visible = false">取消</el-button>
        <el-button type="primary" :loading="vol.saving" @click="submitVolume">确定</el-button>
      </template>
    </el-dialog>

    <!--
      智能排课：按「第一节开始时间 + 单节时长 + 课间时长 + 节数」推算出各节的打铃时刻，
      一次生成整套打铃条目，省得一条条填。
      ⚠ 它只是把条目**填进下面那张表**，并不直接落库 —— 生成后还能改，
        点「确定」保存方案时才写库，和手工添加走完全同一条路径。
    -->
    <el-dialog v-model="smart.visible" title="智能排课" width="560px">
      <el-form :model="smart" label-width="120px">
        <el-form-item label="第一节开始">
          <el-time-picker v-model="smart.firstTime" value-format="HH:mm:ss" :clearable="false" class="fill" />
        </el-form-item>
        <el-row :gutter="12">
          <el-col :span="12">
            <el-form-item label="单节时长">
              <el-input-number v-model="smart.lessonMin" :min="1" :max="240" class="fill" />
              <span class="form-tip">分钟</span>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="课间时长">
              <el-input-number v-model="smart.breakMin" :min="0" :max="240" class="fill" />
              <span class="form-tip">分钟</span>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="12">
          <el-col :span="12">
            <el-form-item label="节数">
              <el-input-number v-model="smart.count" :min="1" :max="30" class="fill" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="名称前缀">
              <el-input v-model="smart.prefix" maxlength="20" placeholder="例如：第" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="生成内容">
          <el-checkbox v-model="smart.withStart">上课铃</el-checkbox>
          <el-checkbox v-model="smart.withEnd">下课铃</el-checkbox>
        </el-form-item>
        <el-form-item label="铃声">
          <el-select
            v-model="smart.mediaIds"
            multiple
            filterable
            remote
            reserve-keyword
            collapse-tags
            :remote-method="searchMedia"
            :loading="mediaLoading"
            placeholder="媒体名称搜索"
            class="fill"
          >
            <el-option v-for="m in medias" :key="m.id" :label="m.name" :value="m.id" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="smart.visible = false">取消</el-button>
        <el-button type="primary" @click="applySmart">生成条目</el-button>
      </template>
    </el-dialog>

    <!-- 新建方案 / 修改方案级属性 -->
    <el-dialog v-model="dlg.visible" :title="dlg.title" width="860px" top="5vh">
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
        <el-divider content-position="left">任务配置</el-divider>

        <el-row :gutter="16">
          <el-col :span="12">
            <!-- 旧版 maxlength="8" -->
            <el-form-item label="方案名称" prop="planName">
              <el-input
                v-model="dlg.form.planName"
                maxlength="8"
                show-word-limit
                :disabled="!!dlg.savedPlanName"
                placeholder="请输入方案名称"
              />
              <!-- 已经有课时入库了就锁住方案名：方案是靠名字归组的，这时改名等于另起一个方案 -->
              <span v-if="dlg.savedPlanName" class="dlg-note">已有课时入库，方案名不能再改</span>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="方案任务">
              <el-button type="primary" plain :icon="CirclePlus" @click="addItemRow">添加任务</el-button>
              <el-button type="danger" plain :icon="Delete" @click="removeSelectedItems">删除任务</el-button>
              <span class="dlg-note ml8">{{ itemCountNote }}</span>
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="16">
          <el-col :span="12">
            <!-- prepower 的单位是秒不是分钟；选项与默认值（15 秒）都照旧版 -->
            <el-form-item label="预开电源">
              <el-select v-model="dlg.form.playback.prepower" class="fill">
                <el-option v-for="o in prepowerOptions" :key="o.value" :label="o.label" :value="o.value" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="任务级别">
              <el-select v-model="dlg.form.playback.priority" style="width: 110px">
                <el-option v-for="p in priorityOptions" :key="p" :label="String(p)" :value="p" />
              </el-select>
              <span class="dlg-note ml8">（10最高）</span>
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="作息音量">
              <el-slider v-model="dlg.form.playback.defaultvolume" :min="0" :max="100" show-input />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="发送模式">
              <el-radio-group v-model="dlg.form.playback.datasendmodel">
                <el-radio :value="0">单播</el-radio>
                <el-radio :value="1">组播</el-radio>
              </el-radio-group>
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="开始日期" prop="startdate">
              <el-date-picker v-model="dateRange[0]" type="date" value-format="YYYY-MM-DD" placeholder="开始日期" class="fill" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="结束日期" prop="enddate">
              <el-date-picker v-model="dateRange[1]" type="date" value-format="YYYY-MM-DD" placeholder="结束日期" class="fill" />
            </el-form-item>
          </el-col>
        </el-row>

        <!--
          执行模式是下拉「每天 / 每星期」，选每星期才展开星期勾选
          （旧版 select#exemodel 的 onChange="displayweek(this)"）。
          每天 = 七位全 1。
        -->
        <el-form-item label="执行模式" prop="weekdays">
          <el-select v-model="runMode" style="width: 140px" @change="onRunModeChange">
            <el-option label="每天" :value="1" />
            <el-option label="每星期" :value="2" />
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
        <el-divider content-position="left">方案任务</el-divider>
        <el-table :data="dlg.items" size="small" border max-height="300" @selection-change="onItemSelectionChange">
          <el-table-column type="selection" width="40" align="center" />
          <el-table-column type="index" label="序号" width="46" align="center" />
          <el-table-column min-width="120">
            <template #header><span class="req-star">*</span> 课时名称</template>
            <template #default="{ row, $index }">
              <el-input
                v-model="row.taskname"
                size="small"
                maxlength="12"
                placeholder="课时名称"
                :class="{ 'is-bad': itemErrors[$index]?.taskname }"
                @input="itemErrors[$index] && (itemErrors[$index].taskname = '')"
              />
              <div v-if="itemErrors[$index]?.taskname" class="cell-err">{{ itemErrors[$index].taskname }}</div>
            </template>
          </el-table-column>
          <el-table-column width="110">
            <template #header><span class="req-star">*</span> 作息时间</template>
            <template #default="{ row, $index }">
              <el-time-picker
                v-model="row.playtime"
                value-format="HH:mm:ss"
                size="small"
                placeholder="00:00:00"
                class="fill"
                :class="{ 'is-bad': itemErrors[$index]?.playtime }"
                @change="itemErrors[$index] && (itemErrors[$index].playtime = '')"
              />
              <div v-if="itemErrors[$index]?.playtime" class="cell-err">{{ itemErrors[$index].playtime }}</div>
            </template>
          </el-table-column>
          <el-table-column label="作息音乐" min-width="134">
            <template #default="{ row }">
              <el-select
                v-model="row.mediaIds"
                multiple
                filterable
                remote
                reserve-keyword
                collapse-tags
                collapse-tags-tooltip
                size="small"
                :remote-method="searchMedia"
                :loading="mediaLoading"
                placeholder="媒体名称搜索"
                class="fill"
              >
                <el-option v-for="m in medias" :key="m.id" :label="m.name" :value="m.id" />
              </el-select>
            </template>
          </el-table-column>
          <!-- 旧版「播放时长」是个弹层：选时长就是 时/分/秒 三个下拉，选次数是 00~99 -->
          <el-table-column label="播放时长" width="204">
            <template #default="{ row }">
              <div class="len-cell">
                <el-select v-model="row.timelengthtype" size="small" style="width: 74px" @change="onLenTypeChange(row)">
                  <el-option label="时长" :value="1" />
                  <el-option label="次数" :value="2" />
                </el-select>
                <el-time-picker
                  v-if="row.timelengthtype === 1"
                  v-model="row.lengthhms"
                  value-format="HH:mm:ss"
                  size="small"
                  placeholder="时:分:秒"
                  style="width: 116px"
                />
                <template v-else>
                  <el-input-number
                    v-model="row.timelength"
                    :min="0"
                    :max="99"
                    size="small"
                    :controls="false"
                    style="width: 78px"
                  />
                  <span class="dlg-note">次</span>
                </template>
              </div>
            </template>
          </el-table-column>
          <!-- 旧版每行三个按钮：添加(已入库则是修改) / 复制 / 删除 -->
          <el-table-column label="操作" width="152" align="center">
            <template #default="{ row, $index }">
              <el-button link type="primary" :loading="row.busy" @click="saveOneItem($index)">
                {{ row.taskid ? "修改" : "添加" }}
              </el-button>
              <el-button link type="primary" @click="copyItemRow($index)">复制</el-button>
              <el-button link type="danger" @click="removeItemAt($index)">删除</el-button>
            </template>
          </el-table-column>
          <template #empty><span class="dlg-note">还没有课时，点「添加任务」加一条</span></template>
        </el-table>
        <div v-if="itemsError" class="cell-err mt6">{{ itemsError }}</div>
        <el-divider content-position="left">终端列表</el-divider>
        <el-form-item label-width="0" prop="terminals">
          <TerminalTree
            v-model="selectedTerminalIds"
            :terminals="terminals"
            :loading="terminalLoading"
            height="260px"
            style="width: 100%"
            @search="searchTerminals"
          />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dlg.visible = false">取消</el-button>
        <el-button type="primary" :loading="dlg.saving" @click="submitPlan">确定</el-button>
      </template>
    </el-dialog>

    <!-- 条目管理 -->
    <el-drawer v-model="items.visible" :title="`打铃条目 —— ${items.planName}`" size="60%">
      <div class="drawer-bar">
        <!-- 按钮与列名照 :80 的「查看任务」弹窗 -->
        <el-button type="primary" :icon="CirclePlus" :disabled="!btn.item" @click="openItemEdit(null)"> 添加任务 </el-button>
        <el-button type="danger" :icon="Delete" :disabled="!btn.item || !items.checked.length" @click="removeItems">
          删除任务{{ items.checked.length ? `(${items.checked.length})` : "" }}
        </el-button>
      </div>
      <el-table :data="items.list" row-key="taskid" @selection-change="rows => (items.checked = rows.map(r => r.taskid))">
        <el-table-column type="selection" width="46" />
        <el-table-column prop="taskname" label="任务名称" min-width="140" />
        <el-table-column label="播放时间" width="130">
          <template #default="{ row }">
            {{ row.playtime }}
            <el-tooltip v-if="row.duplicateTime" content="方案内还有别的条目排在同一时刻" placement="top">
              <el-icon class="warn-icon"><WarningFilled /></el-icon>
            </el-tooltip>
          </template>
        </el-table-column>
        <el-table-column label="播放时长" width="120">
          <template #default="{ row }">
            {{ row.timelengthtype === 1 ? `${row.timelength} 秒` : `${row.timelength} 次` }}
          </template>
        </el-table-column>
        <el-table-column label="铃声名称" min-width="180">
          <template #default="{ row }">
            <span v-if="!row.media?.length" class="muted">未设置</span>
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
        <el-table-column label="功放子任务" width="120">
          <template #default="{ row }">
            <span v-if="row.powerTaskId">{{ row.powerPlayTime }}</span>
            <span v-else class="muted">—</span>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="90" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link :icon="EditPen" :disabled="!btn.item" @click="openItemEdit(row)"> 修改 </el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-drawer>

    <!-- 单条条目编辑 -->
    <el-dialog v-model="itemDlg.visible" :title="itemDlg.title" width="620px">
      <el-form :model="itemDlg.form" label-width="100px">
        <el-form-item label="任务名称" required>
          <el-input v-model="itemDlg.form.taskname" maxlength="80" show-word-limit placeholder="请输入任务名称" />
        </el-form-item>
        <el-form-item label="播放时间" required>
          <el-time-picker v-model="itemDlg.form.playtime" value-format="HH:mm:ss" class="fill" />
        </el-form-item>
        <el-form-item label="播放类型">
          <el-radio-group v-model="itemDlg.form.timelengthtype">
            <el-radio :value="1">按时长播放</el-radio>
            <el-radio :value="2">按次数播放</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="播放时长/次数">
          <el-input-number v-model="itemDlg.form.timelength" :min="0" :max="86400" />
        </el-form-item>
        <el-form-item label="作息音乐">
          <el-select
            v-model="itemDlg.form.mediaIds"
            multiple
            filterable
            remote
            reserve-keyword
            :remote-method="searchMedia"
            :loading="mediaLoading"
            placeholder="媒体名称搜索"
            class="fill"
          >
            <el-option v-for="m in medias" :key="m.id" :label="m.name" :value="m.id" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="itemDlg.visible = false">取消</el-button>
        <el-button type="primary" :loading="itemDlg.saving" @click="submitItem">确定</el-button>
      </template>
    </el-dialog>

    <!-- 删除确认 -->
    <el-dialog v-model="del.visible" title="删除作息方案" width="560px">
      <el-alert type="error" :closable="false" class="mb12"> 将删除方案「{{ del.planName }}」的全部内容，不可恢复。 </el-alert>
      <el-descriptions :column="2" border size="small">
        <el-descriptions-item label="打铃条目">{{ del.impact?.items ?? 0 }} 条</el-descriptions-item>
        <el-descriptions-item label="功放子任务">{{ del.impact?.powerSubTasks ?? 0 }} 条</el-descriptions-item>
        <el-descriptions-item label="铃声关联">{{ del.impact?.mediaRows ?? 0 }} 行</el-descriptions-item>
        <el-descriptions-item label="终端关联">{{ del.impact?.terminalRows ?? 0 }} 行</el-descriptions-item>
        <el-descriptions-item label="快捷键关联">{{ del.impact?.keyMapRows ?? 0 }} 行</el-descriptions-item>
        <el-descriptions-item label="离线任务关联">{{ del.impact?.offlineTaskRows ?? 0 }} 行</el-descriptions-item>
      </el-descriptions>
      <el-alert v-if="del.impact?.sameNameOtherTasks" type="warning" :closable="false" class="mt12">
        库里还有 {{ del.impact.sameNameOtherTasks }} 条任务的名称也叫「{{ del.planName }}」，但它们不属于本方案。
        <b>新版不会删除它们</b> —— 旧版会连它们一起删掉。
      </el-alert>
      <template #footer>
        <el-button @click="del.visible = false">取消</el-button>
        <el-button type="danger" :loading="del.busy" @click="confirmDelete">确认删除</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="bellPlan">
import { computed, nextTick, reactive, ref } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import type { FormInstance, FormRules } from "element-plus";
import { CirclePlus, Delete, EditPen, View, WarningFilled } from "@element-plus/icons-vue";
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
  setBellPlanVolumeApi,
  updateBellItemApi,
  updateBellPlanApi,
  type BellDeleteImpact,
  type BellItem,
  type BellPlan
} from "@/api/modules/bell";
import { searchTaskMediaApi, searchTaskTerminalsApi, type MediaOption, type TaskTerminalOption } from "@/api/modules/task";

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
const weekLabels = ["日", "一", "二", "三", "四", "五", "六"];
/** 旧界面的提前开电源下拉：0、5、10 … 55 秒，默认选中 15 秒 */
const prepowerSeconds = Array.from({ length: 12 }, (_, i) => i * 5);
/** 0~55 秒 + 1~5 分钟；旧库里若存着别的秒数（例如 1 秒），把它也列进来 */
const prepowerOptions = computed(() => {
  const list = [
    ...prepowerSeconds.map(s => ({ value: s, label: `${s} 秒` })),
    ...[1, 2, 3, 4, 5].map(m => ({ value: m * 60, label: `${m} 分钟` }))
  ];
  const cur = dlg.form.playback.prepower;
  if (!list.some(o => o.value === cur)) list.push({ value: cur, label: `${cur} 秒` });
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
  { prop: "planName", label: "方案名称", minWidth: 220, sortable: "custom" },
  { prop: "startdate", label: "起始日期", width: 130, sortable: "custom" },
  { prop: "enddate", label: "结束日期", width: 130, sortable: "custom" },
  { prop: "projectstate", label: "状态", width: 110, sortable: "custom" },
  { prop: "itemCount", label: "任务数", width: 110, sortable: "custom" },
  { prop: "operation", label: "终端属性", fixed: "right", width: 200 }
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
  const lo = dlg.priorityMin ?? 0;
  const hi = dlg.priorityMax ?? 99;
  return Array.from({ length: Math.max(0, hi - lo + 1) }, (_, i) => lo + i);
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
  mediaIds: [] as number[]
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
  isEdit: false,
  title: "",
  originalName: "",
  applyTerminals: false,
  mixedAttrs: [] as string[],
  priorityMin: 0,
  priorityMax: 99,
  /** 新建时：第一条课时入库后，方案就已经存在了，记住它的名字 */
  savedPlanName: "",
  form: {
    planName: "",
    playback: { defaultvolume: 80, priority: 10, prepower: 15, datasendmodel: 0, israndomplay: 0 }
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
  dlg.items.splice(idx + 1, 0, { ...src, taskid: 0, busy: false, mediaIds: [...(src.mediaIds ?? [])] });
  itemErrors.value.splice(idx + 1, 0, emptyItemError());
};
/**
 * 当前对话框对应的数据库里的方案名。
 * 修改方案时就是原方案名；新建时要等第一条课时入库、方案被建出来之后才有。
 */
const currentPlanName = computed(() => (dlg.isEdit ? dlg.originalName : dlg.savedPlanName));

/** 一行 → 提交给后端的条目。时长模式下把 时:分:秒 换算成秒 */
const itemPayload = (it: ItemRow) => ({
  taskname: it.taskname.trim(),
  playtime: it.playtime,
  timelengthtype: it.timelengthtype,
  timelength: it.timelengthtype === 1 ? hmsToSec(it.lengthhms) : it.timelength,
  media: it.mediaIds.map((id, i) => ({ mediaId: id, sort: i }))
});

/** 删掉库里的课时之后：刷新列表；如果连方案都没了，就把状态收拾干净 */
const afterItemsDeleted = (planRemoved: boolean) => {
  refresh();
  if (!planRemoved) return;
  dlg.savedPlanName = "";
  if (dlg.isEdit) {
    ElMessage.warning("方案的最后一个课时已删除，方案随之不存在了");
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
      await ElMessageBox.confirm(`「${row.taskname}」已经存进数据库，删除会把这个课时从库里一并删掉。`, "删除课时", {
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
const onItemSelectionChange = (rows: ItemRow[]) => (selectedItems.value = rows);
const itemCountNote = computed(() =>
  selectedItems.value.length ? `共 ${dlg.items.length} 条，已勾选 ${selectedItems.value.length} 条` : `共 ${dlg.items.length} 条`
);
const removeSelectedItems = async () => {
  if (!selectedItems.value.length) return ElMessage.warning("请先勾选要删除的课时");
  const savedIds = selectedItems.value.filter(r => r.taskid).map(r => r.taskid);
  if (savedIds.length && currentPlanName.value) {
    try {
      await ElMessageBox.confirm(
        `勾选的 ${selectedItems.value.length} 个课时里有 ${savedIds.length} 个已经存进数据库，删除会把它们从库里一并删掉。`,
        "删除课时",
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
  itemsError.value = "";
  ElMessage.success(`已删除 ${removed} 个课时`);
};

/* ---------------- 必填校验 ----------------
   旧版 addbelltask.html:checkform() 的做法：方案名称 / 日期 / 执行模式 / 课时名称
   校验不过就把对应位置的 * 或提示文字刷成红色（terminal_star 是红色样式），
   并把光标定位过去。这里用 el-form 的 rules 做同一件事，表格里的行则自己维护
   一份 itemErrors，因为它不在 el-form 的 model 里。 */

/** 旧版 isChinaOrNumbOrLett()：只允许中文、字母、数字 */
const isNameOk = (v: string) => /^[\u4e00-\u9fa5A-Za-z0-9]+$/.test(v);

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
        if (!v) return cb(new Error("请输入方案名称"));
        if (!isNameOk(v)) return cb(new Error("方案名称只能是中文、字母或数字"));
        cb();
      }
    }
  ],
  startdate: [
    {
      required: true,
      trigger: "change",
      validator: (_r, _v, cb) => (dateRange.value?.[0] ? cb() : cb(new Error("请选择开始日期")))
    }
  ],
  enddate: [
    {
      required: true,
      trigger: "change",
      validator: (_r, _v, cb) => {
        const [a, b] = dateRange.value ?? [];
        if (!b) return cb(new Error("请选择结束日期"));
        if (a && a > b) return cb(new Error("开始日期不能大于结束日期"));
        cb();
      }
    }
  ],
  weekdays: [
    {
      required: true,
      trigger: "change",
      validator: (_r, _v, cb) => (runMode.value === 2 && !weekdays.value.length ? cb(new Error("请选择星期")) : cb())
    }
  ],
  terminals: [
    {
      required: true,
      trigger: "change",
      validator: (_r, _v, cb) => (selectedTerminalIds.value.length ? cb() : cb(new Error("请至少选择一个终端")))
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
    err.taskname = "请输入课时名称";
  } else if (!isNameOk(name)) {
    err.taskname = "只能是中文、字母或数字";
  } else {
    // 后端按 (info, taskname) 定位条目，方案内重名会互相覆盖
    const dup = dlg.items.findIndex((o, i) => i !== idx && (o.taskname ?? "").trim() === name);
    if (dup >= 0) err.taskname = `与第 ${dup + 1} 行重名`;
  }
  if (!/^\d{2}:\d{2}:\d{2}$/.test(it.playtime ?? "")) err.playtime = "请选择作息时间";
  return !err.taskname && !err.playtime;
};

/** 点「确定」时校验课时表：只管还没入库的行，已入库的行由行上的「修改」单独负责 */
const validateItems = () => {
  itemsError.value = "";
  itemErrors.value = dlg.items.map(() => emptyItemError());
  if (!dlg.items.length && !currentPlanName.value) {
    itemsError.value = "请至少添加一个课时";
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
      await updateBellItemApi(currentPlanName.value, row.taskid, itemPayload(row));
      ElMessage.success(`课时「${row.taskname.trim()}」已保存`);
    } else if (!currentPlanName.value) {
      const res = await createBellPlanApi({
        planName: dlg.form.planName.trim(),
        schedule: scheduleForm(),
        playback: dlg.form.playback,
        terminals: terminalsForm(),
        items: [itemPayload(row)]
      });
      dlg.savedPlanName = res.data.planName;
      row.taskid = res.data.taskIds?.[0] ?? 0;
      ElMessage.success(`方案「${res.data.planName}」已创建，课时「${row.taskname.trim()}」已入库`);
      (res.data.warnings ?? []).forEach(w => ElMessage.warning(w));
    } else {
      const res = await addBellItemApi(currentPlanName.value, itemPayload(row));
      row.taskid = res.data.taskIds?.[0] ?? 0;
      ElMessage.success(`课时「${row.taskname.trim()}」已入库`);
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
  await nextTick();
  planFormRef.value?.clearValidate();
};

const maskFromWeekdays = () => {
  const arr = Array(7).fill("0");
  weekdays.value.forEach(i => (arr[i] = "1"));
  return arr.join("");
};
const applyMask = (mask: string) => {
  weekdays.value = [];
  for (let i = 0; i < 7 && i < mask.length; i++) if (mask[i] === "1") weekdays.value.push(i);
  // 七天全勾就是「每天」，否则是「每星期」
  runMode.value = weekdays.value.length === 7 ? 1 : 2;
};

/* ---------------- 调整音量 ---------------- */

const vol = reactive({ visible: false, saving: false, planName: "", value: 80 });

const openVolume = (raw: Record<string, any>[]) => {
  const rows = (raw ?? []) as unknown as BellPlan[];
  if (rows.length !== 1) return ElMessage.warning("请只勾选一个方案");
  vol.planName = rows[0].planName;
  // 列表行里没带音量（那是方案级属性，在详情里），默认给 80，用户自己拖
  vol.value = 80;
  vol.visible = true;
};

const submitVolume = async () => {
  vol.saving = true;
  try {
    const { data } = await setBellPlanVolumeApi(vol.planName, vol.value);
    vol.visible = false;
    ElMessage.success(`已把「${data.planName}」的音量改成 ${data.volume}，影响 ${data.affectedTasks} 条`);
    refresh();
  } finally {
    vol.saving = false;
  }
};

/* ---------------- 智能排课 ---------------- */

const smart = reactive({
  visible: false,
  firstTime: "08:00:00",
  lessonMin: 45,
  breakMin: 10,
  count: 8,
  prefix: "第",
  withStart: true,
  withEnd: true,
  mediaIds: [] as number[]
});

/** 打开智能排课。它是在「添加方案」弹窗之上用的，所以先确保那张表单开着。 */
const openSmart = async () => {
  if (!dlg.visible || dlg.isEdit) await openCreate();
  smart.visible = true;
  if (!medias.value.length) await searchMedia("");
};

/** 把 "HH:mm:ss" 加上若干分钟，跨零点绕回当天 */
const addMinutes = (t: string, min: number) => {
  const [h, m, s] = t.split(":").map(Number);
  const total = (h * 3600 + m * 60 + s + min * 60 + 86400) % 86400;
  const p = (n: number) => String(n).padStart(2, "0");
  return `${p(Math.floor(total / 3600))}:${p(Math.floor((total % 3600) / 60))}:${p(total % 60)}`;
};

const applySmart = () => {
  if (!smart.withStart && !smart.withEnd) return ElMessage.warning("请至少勾一种铃（上课铃 / 下课铃）");
  const rows: ReturnType<typeof emptyItemRow>[] = [];
  let t = smart.firstTime;
  for (let i = 1; i <= smart.count; i++) {
    if (smart.withStart) {
      rows.push({ ...emptyItemRow(), taskname: `${smart.prefix}${i}节上课`, playtime: t, mediaIds: [...smart.mediaIds] });
    }
    const end = addMinutes(t, smart.lessonMin);
    if (smart.withEnd) {
      rows.push({ ...emptyItemRow(), taskname: `${smart.prefix}${i}节下课`, playtime: end, mediaIds: [...smart.mediaIds] });
    }
    t = addMinutes(end, smart.breakMin);
  }
  // 覆盖而不是追加：排课是「重排整套」，追加只会和已有条目撞时间
  dlg.items = rows;
  itemErrors.value = rows.map(() => emptyItemError());
  itemsError.value = "";
  selectedItems.value = [];
  smart.visible = false;
  ElMessage.success(`已生成 ${rows.length} 条打铃条目，可继续逐条调整，点「确定」才保存`);
};

const openCreate = async () => {
  Object.assign(dlg, {
    visible: true,
    saving: false,
    isEdit: false,
    title: "添加方案",
    originalName: "",
    applyTerminals: true,
    mixedAttrs: [],
    priorityMin: 0,
    priorityMax: 99,
    savedPlanName: "",
    form: { planName: "", playback: { defaultvolume: 80, priority: 10, prepower: 15, datasendmodel: 0, israndomplay: 0 } },
    items: [emptyItemRow()]
  });
  dateRange.value = [today(), today()];
  weekdays.value = [0, 1, 2, 3, 4, 5, 6];
  runMode.value = 1; // 默认「每天」，与旧版下拉的第一项一致
  selectedTerminalIds.value = [];
  await resetPlanErrors();
  await Promise.all([searchTerminals(""), searchMedia("")]);
};

const openEdit = async (row: BellPlan) => {
  const { data } = await getBellPlanApi(row.planName);
  Object.assign(dlg, {
    visible: true,
    saving: false,
    isEdit: true,
    title: `修改方案：${data.planName}`,
    originalName: data.planName,
    // 旧版 modifybell.html 的「确定」是连终端一起写回去的
    applyTerminals: true,
    mixedAttrs: data.mixedAttrs ?? [],
    priorityMin: data.priorityMin,
    priorityMax: data.priorityMax,
    savedPlanName: "",
    form: { planName: data.planName, playback: { ...data.playback } },
    items: data.items.map(it => ({
      taskid: it.taskid,
      taskname: it.taskname,
      playtime: it.playtime,
      // 旧库里存在 timelengthtype=0 的历史行，界面只有「时长/次数」两种，按次数归一
      timelengthtype: it.timelengthtype === 1 ? 1 : 2,
      timelength: it.timelengthtype === 1 ? 1 : it.timelength,
      lengthhms: it.timelengthtype === 1 ? secToHms(it.timelength) : "00:00:30",
      busy: false,
      mediaIds: it.media.map(m => m.mediaId)
    }))
  });
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
  // 已删除的终端不回填，否则保存时会被存在性校验挡下
  selectedTerminalIds.value = data.terminals.filter(t => !t.deleted).map(t => t.terminalId);
  await resetPlanErrors();
  await searchTerminals("");
};

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
    area: "11111111"
  }));

/**
 * 「确定」：把还没入库的课时补进去，再落方案级属性。
 *
 * 旧版 checkform() 会跳过已经用行内「添加」存过的行，只提交剩下的，这里同理。
 * 方案还不存在时才走一次性创建；已经存在（修改方案，或新建时行内已经存过）
 * 就先补课时、再用页面上的方案头统一刷一遍，免得页头改了却没生效。
 */
const submitPlan = async () => {
  // 两边都跑一遍再判断，好让所有没填的 * 项一次性全标红，而不是修一个冒一个
  const formOk = await validateHeader();
  const itemsOk = validateItems();
  if (!formOk || !itemsOk) return;

  const schedule = scheduleForm();
  const terminals = terminalsForm();
  const pending = dlg.items.filter(it => !it.taskid);

  dlg.saving = true;
  try {
    if (currentPlanName.value) {
      for (const it of pending) {
        const res = await addBellItemApi(currentPlanName.value, itemPayload(it));
        it.taskid = res.data.taskIds?.[0] ?? 0;
        (res.data.warnings ?? []).forEach(w => ElMessage.warning(w));
      }
      const res = await updateBellPlanApi({
        planName: currentPlanName.value,
        newPlanName: dlg.form.planName.trim(),
        schedule,
        playback: dlg.form.playback,
        terminals,
        applyTerminals: dlg.applyTerminals
      });
      const parts: string[] = [];
      if (pending.length) parts.push(`新增 ${pending.length} 个课时`);
      if (res.data.affectedRows) parts.push(`更新 ${res.data.affectedRows} 行`);
      if (res.data.renamed) parts.push("方案已改名");
      ElMessage.success(parts.length ? `已${parts.join("，")}` : "方案已保存");
    } else {
      const res = await createBellPlanApi({
        planName: dlg.form.planName.trim(),
        schedule,
        playback: dlg.form.playback,
        terminals,
        items: dlg.items.map(itemPayload)
      });
      dlg.items.forEach((it, i) => (it.taskid = res.data.taskIds?.[i] ?? 0));
      dlg.savedPlanName = res.data.planName;
      ElMessage.success(`已创建 ${res.data.createdItems} 个条目`);
      (res.data.warnings ?? []).forEach(w => ElMessage.warning(w));
    }
    dlg.visible = false;
    refresh();
  } finally {
    dlg.saving = false;
  }
};

/* ---------------- 条目管理 ---------------- */

const items = reactive({
  visible: false,
  planName: "",
  list: [] as BellItem[],
  checked: [] as number[]
});

const loadItems = async (planName: string) => {
  const { data } = await getBellPlanApi(planName);
  items.planName = planName;
  items.list = data.items ?? [];
  items.checked = [];
};

const openItems = async (row: BellPlan) => {
  await loadItems(row.planName);
  await searchMedia("");
  items.visible = true;
};

const itemDlg = reactive({
  visible: false,
  saving: false,
  title: "",
  taskid: 0,
  form: { taskname: "", playtime: "08:00:00", timelengthtype: 2, timelength: 1, mediaIds: [] as number[] }
});

const openItemEdit = (row: BellItem | null) => {
  itemDlg.visible = true;
  itemDlg.saving = false;
  if (!row) {
    itemDlg.title = "添加打铃条目";
    itemDlg.taskid = 0;
    itemDlg.form = { taskname: "", playtime: "08:00:00", timelengthtype: 2, timelength: 1, mediaIds: [] };
    return;
  }
  itemDlg.title = `修改条目：${row.taskname}`;
  itemDlg.taskid = row.taskid;
  itemDlg.form = {
    taskname: row.taskname,
    playtime: row.playtime,
    timelengthtype: row.timelengthtype,
    timelength: row.timelength,
    mediaIds: row.media.filter(m => !m.deleted).map(m => m.mediaId)
  };
};

const submitItem = async () => {
  if (!itemDlg.form.taskname.trim()) return ElMessage.warning("请输入条目名称");
  const payload = {
    taskname: itemDlg.form.taskname.trim(),
    playtime: itemDlg.form.playtime,
    timelengthtype: itemDlg.form.timelengthtype,
    timelength: itemDlg.form.timelength,
    media: itemDlg.form.mediaIds.map((id, i) => ({ mediaId: id, sort: i }))
  };
  itemDlg.saving = true;
  try {
    if (itemDlg.taskid) {
      await updateBellItemApi(items.planName, itemDlg.taskid, payload);
    } else {
      await addBellItemApi(items.planName, payload);
    }
    ElMessage.success("保存成功");
    itemDlg.visible = false;
    await loadItems(items.planName);
    refresh();
  } finally {
    itemDlg.saving = false;
  }
};

const removeItems = async () => {
  if (!items.checked.length) return;
  await ElMessageBox.confirm(`确定删除选中的 ${items.checked.length} 个条目？`, "提示", { type: "warning" });
  const { data } = await deleteBellItemsApi(items.planName, items.checked);
  ElMessage.success(`已删除 ${data.deleted} 个条目`);
  if (data.planRemoved) {
    ElMessage.warning("最后一个条目已删除，该方案随之消失");
    items.visible = false;
  } else {
    await loadItems(items.planName);
  }
  refresh();
};

/* ---------------- 启停 / 复制 / 删除 ---------------- */

const del = reactive({
  visible: false,
  busy: false,
  planName: "",
  impact: null as BellDeleteImpact | null
});

// batchCmd 把顶部按钮的「勾选行」接到既有的单行命令上。
//
// 作息方案的启停/删除/复制在后端都是**按方案名**操作的，一次只能一条，
// 所以这里就是顺序跑一遍勾选的行。复制按钮在只勾一行时才可点（批量复制没有意义）。
// rows 来自 ProTable 的 selectedList，它的类型是宽松的 Record，这里收窄一次。
const batchCmd = async (cmd: string, raw: Record<string, any>[]) => {
  const rows = (raw ?? []) as unknown as BellPlan[];
  if (!rows.length) return ElMessage.warning("请先勾选方案");
  if (cmd === "copy") {
    return onMoreCmd("copy", rows[0]);
  }
  if (cmd === "delete") {
    // 删除要看影响面弹窗，一次处理一条；多选时只对第一条打开，
    // 免得连开一串确认框把人淹掉。
    if (rows.length > 1) {
      await ElMessageBox.confirm(`一次只能删一个方案。先处理「${rows[0].planName}」，其余的删完再来。`, "逐个删除", {
        type: "warning",
        confirmButtonText: "继续"
      });
    }
    return onMoreCmd("delete", rows[0]);
  }
  for (const r of rows) await onMoreCmd(cmd, r);
};

const onMoreCmd = async (cmd: string | number | object, row: BellPlan) => {
  switch (cmd) {
    case "enable":
    case "disable": {
      const enable = cmd === "enable";
      const { data } = await setBellPlanStateApi(row.planName, enable);
      ElMessage.success(`${enable ? "已启用" : "已停止"}，影响 ${data.affectedTasks} 行`);
      if (data.offlineStateReset) {
        ElMessage.warning("该方案原本有条目正在离线传输，启停已把离线状态一并复位");
      }
      refresh();
      break;
    }
    case "copy": {
      const { value } = await ElMessageBox.prompt("新方案名称", "复制方案", {
        inputValue: `${row.planName}-副本`,
        inputValidator: v => (v && v.trim() ? true : "名称不能为空")
      });
      const { data } = await copyBellPlanApi(row.planName, value.trim());
      ElMessage.success(
        `已复制 ${data.copiedItems} 个条目、${data.copiedPowerSubTasks} 条功放子任务、` +
          `${data.copiedMediaRows} 条铃声、${data.copiedTerminalRows} 条终端关联`
      );
      refresh();
      break;
    }
    case "delete": {
      const { data } = await previewDeleteBellPlanApi(row.planName);
      del.planName = row.planName;
      del.impact = data;
      del.busy = false;
      del.visible = true;
      break;
    }
  }
};

const confirmDelete = async () => {
  del.busy = true;
  try {
    const { data } = await deleteBellPlanApi(del.planName);
    ElMessage.success(`已删除 ${data.items} 个条目、${data.powerSubTasks} 条功放子任务`);
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
  color: var(--el-color-warning);
  vertical-align: middle;
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
