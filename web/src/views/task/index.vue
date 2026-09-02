<!--
  任务管理（文件广播）

  相对旧版的关键修复：
    · 恢复服务端分页与白名单排序。旧版分页整块被注释掉，排序写成
      ORDER BY '<常量>' 双重失效，一次渲染全部任务。
    · 媒体/终端清单改为两条批量查询。旧版每个任务额外跑 2 条 SQL，
      100 个任务就是 201 次查询。
    · 媒体选择改为按需搜索的远程下拉。旧版把整张 media 表读进内存。
    · 启动前置条件（方案已启用 / 有媒体 / 有终端）由服务端判定，
      每行直接显示能不能启动、不能的原因是什么。
    · 媒体与终端以对象数组提交，不再是两条按下标对齐的逗号串。
-->
<template>
  <div class="task-page">
    <!-- 左：任务分组树 -->
    <div class="card tree-panel">
      <div class="tree-head">
        <span class="tree-title">任务分组</span>
        <el-button v-if="canFolder" type="primary" link :icon="FolderAdd" @click="openFolderCreate">新建</el-button>
      </div>
      <el-scrollbar>
        <el-tree
          ref="treeRef"
          :data="folders"
          node-key="id"
          :props="{ label: 'name', children: 'children' }"
          :current-node-key="currentFolder"
          :expand-on-click-node="false"
          default-expand-all
          highlight-current
          @current-change="onFolderChange"
        >
          <template #default="{ data }">
            <span class="tree-node">
              <span class="tree-name">{{ data.name }}</span>
              <span class="tree-count">{{ data.taskCount }}</span>
              <span v-if="canFolder" class="tree-ops">
                <el-icon title="重命名" @click.stop="openFolderRename(data)"><EditPen /></el-icon>
                <el-icon
                  v-if="data.canDelete"
                  class="danger"
                  title="删除分组"
                  @click.stop="confirmFolderDelete(data)"
                >
                  <Delete />
                </el-icon>
              </span>
            </span>
          </template>
        </el-tree>
      </el-scrollbar>
    </div>

    <!-- 右：任务列表 -->
    <div class="table-box list-panel">
      <ProTable
        ref="proTableRef"
        :columns="columns"
        :request-api="getTaskListApi"
        :init-param="initParam"
        :data-callback="dataCallback"
        row-key="taskid"
        @sort-change="onSortChange"
      >
        <template #tableHeader="scope">
          <div class="header-bar">
            <div class="header-left">
              <!--
                按钮清单照 :80（页面规格.txt「文件广播」）：
                添加 / 批量编辑 / 删除 / 执行 / 停止 / 暂停 / 恢复 / 启用 / 停用 /
                紧急设置 / 取消紧急设置 / 设置音量。
                「批量添加终端」是我们多出来的一个，收在「批量编辑」下拉里。
              -->
              <el-button type="primary" :icon="CirclePlus" :disabled="!canAdd" @click="openCreate">添加</el-button>

              <el-dropdown :disabled="!canEdit || !scope.isSelected" @command="c => onMoreCmd(c, scope.selectedListIds)">
                <el-button type="primary" :disabled="!canEdit || !scope.isSelected">
                  批量编辑<el-icon class="el-icon--right"><ArrowDown /></el-icon>
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item command="volume">设置音量</el-dropdown-item>
                    <el-dropdown-item command="sync">批量添加终端</el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>

              <el-button
                type="danger"
                :icon="Delete"
                :disabled="!canDelete || !scope.isSelected"
                @click="openDelete(scope.selectedListIds)"
              >
                删除{{ scope.selectedListIds.length ? `(${scope.selectedListIds.length})` : "" }}
              </el-button>

              <el-button
                :icon="VideoPlay"
                :disabled="!canControl || !scope.isSelected"
                @click="control('start', scope.selectedListIds)"
              >
                执行
              </el-button>
              <el-button
                :icon="VideoPause"
                :disabled="!canControl || !scope.isSelected"
                @click="control('stop', scope.selectedListIds)"
              >
                停止
              </el-button>
              <el-button :disabled="!canControl || !scope.isSelected" @click="control('pause', scope.selectedListIds)">
                暂停
              </el-button>
              <el-button :disabled="!canControl || !scope.isSelected" @click="control('resume', scope.selectedListIds)">
                恢复
              </el-button>
              <el-button :disabled="!canControl || !scope.isSelected" @click="onMoreCmd('enable', scope.selectedListIds)">
                启用
              </el-button>
              <el-button :disabled="!canControl || !scope.isSelected" @click="onMoreCmd('disable', scope.selectedListIds)">
                停用
              </el-button>

              <!--
                ⚠ 紧急任务是**换任务类型**：设为紧急 = tasktype 2 → 7，取消 = 7 → 2。
                  全系统同时只能有一条，服务端会挡住第二条并说清是哪条占着。
              -->
              <el-button :disabled="!canEdit || !scope.isSelected" @click="setEmergency(scope.selectedListIds)">
                紧急设置
              </el-button>
              <el-button :disabled="!canEdit" @click="cancelEmergency">取消紧急设置</el-button>
              <el-button :disabled="!canEdit || !scope.isSelected" @click="openVolume(scope.selectedListIds)">
                设置音量
              </el-button>
            </div>
            <div class="header-right">
              <el-tag v-if="scopeNote" type="info" size="small" effect="plain">{{ scopeNote }}</el-tag>
            </div>
          </div>
        </template>

        <!--
          ⚠ 这里所有 scope.row 的嵌套访问都必须带可选链。
          el-table-column 会用 row = {} 试跑一次插槽（探测子列），
          写成 scope.row.media.length 会抛异常，整张表只剩最后一列。
        -->
        <template #expand="scope">
          <div class="expand">
            <div class="expand-col">
              <div class="expand-title">媒体清单（{{ scope.row.media?.length ?? 0 }}）</div>
              <div v-if="!scope.row.media?.length" class="muted">未选择媒体</div>
              <el-tag
                v-for="m in scope.row.media"
                :key="m.mediaId"
                size="small"
                class="mr4 mb4"
                :type="m.deleted ? 'danger' : 'info'"
                effect="plain"
              >
                {{ m.name }}
              </el-tag>
            </div>
            <div class="expand-col">
              <div class="expand-title">终端清单（{{ scope.row.terminals?.length ?? 0 }}）</div>
              <div v-if="!scope.row.terminals?.length" class="muted">未选择终端</div>
              <el-tag
                v-for="t in scope.row.terminals"
                :key="t.terminalId"
                size="small"
                class="mr4 mb4"
                :type="t.deleted ? 'danger' : t.netstate === 1 ? 'success' : 'info'"
                effect="plain"
              >
                {{ t.terminalname }}
              </el-tag>
            </div>
          </div>
        </template>

        <!--
          projectstate：0 = 启用、1 = 停用，与 audioserver.sql 的列注释相反。
          旧模板 BellManager/bellManager_form.html 就是 `== 0` 渲染 Enabled。
        -->
        <template #projectstate="scope">
          <el-tag v-if="scope.row.projectstate === 0" type="success" size="small">启用</el-tag>
          <el-tag v-else type="info" size="small">停用</el-tag>
        </template>

        <template #state="scope">
          <el-tag :type="stateTagType(scope.row.state)" size="small" effect="plain">{{ scope.row.stateText }}</el-tag>
          <el-tooltip v-if="!scope.row.startable" :content="scope.row.blockReason" placement="top">
            <el-icon class="warn-icon"><WarningFilled /></el-icon>
          </el-tooltip>
        </template>

        <template #weekdays="scope">
          <span v-if="scope.row.exemodel === '1111111'">每天</span>
          <span v-else-if="!scope.row.weekdays?.length" class="muted">手动</span>
          <span v-else>{{ scope.row.weekdays.map((d: number) => WEEK[d - 1]).join(" ") }}</span>
        </template>

        <!--
          :80 的操作列是「编辑 / 终端 / 媒体」三个链接，后两个开只读弹窗。
          展开行仍然保留（点行首箭头），两种看法并存。
          复制是我们多的一个（:80 没有，但去掉就丢功能）。
        -->
        <template #operation="scope">
          <el-button type="primary" link :icon="EditPen" :disabled="!canEdit" @click="openEdit(scope.row)">编辑</el-button>
          <el-button type="primary" link @click="openTerminals(scope.row)">
            终端<span class="cnt">({{ scope.row.terminals?.length ?? 0 }})</span>
          </el-button>
          <el-button type="primary" link @click="openMedia(scope.row)">
            媒体<span class="cnt">({{ scope.row.media?.length ?? 0 }})</span>
          </el-button>
          <el-button type="primary" link :icon="CopyDocument" :disabled="!canCopy" @click="openCopy(scope.row)">
            复制
          </el-button>
        </template>
      </ProTable>
    </div>

    <!-- 行内「终端」链接：列名照 :80 的同名弹窗 -->
    <el-dialog v-model="tm.visible" :title="tm.title" width="760px" top="6vh">
      <el-table :data="tm.list" size="small" max-height="420">
        <el-table-column prop="terminalname" label="终端名称" min-width="180" show-overflow-tooltip>
          <template #default="{ row }">
            {{ row.terminalname }}
            <el-tag v-if="row.deleted" type="danger" size="small" effect="plain" class="ml6">已删除</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="typeName" label="终端类型" width="140" show-overflow-tooltip />
        <el-table-column label="网络状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.netstate === 1 ? 'success' : 'info'" size="small">
              {{ row.netstate === 1 ? "在线" : "离线" }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="设备状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.taskstate === 1 ? 'warning' : 'info'" size="small" effect="plain">
              {{ row.taskstate === 1 ? "播放中" : "空闲" }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="ip" label="终端IP" width="140" />
        <el-table-column prop="volume" label="音量" width="80" />
      </el-table>
      <div v-if="!tm.list.length" class="empty-note">这条任务还没有选终端。</div>
      <template #footer>
        <el-button @click="tm.visible = false">关闭</el-button>
      </template>
    </el-dialog>

    <!-- 行内「媒体」链接 -->
    <el-dialog v-model="md.visible" :title="md.title" width="640px" top="6vh">
      <el-table :data="md.list" size="small" max-height="420">
        <el-table-column prop="sort" label="序号" width="70" />
        <el-table-column prop="name" label="媒体名称" min-width="240" show-overflow-tooltip>
          <template #default="{ row }">
            {{ row.name }}
            <el-tag v-if="row.deleted" type="danger" size="small" effect="plain" class="ml6">已删除</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="媒体大小" width="120">
          <template #default="{ row }">{{ humanSize(row.size) }}</template>
        </el-table-column>
      </el-table>
      <div v-if="!md.list.length" class="empty-note">这条任务还没有选媒体。</div>
      <template #footer>
        <el-button @click="md.visible = false">关闭</el-button>
      </template>
    </el-dialog>

    <!-- 设置音量（:80 的「设置音量」按钮） -->
    <el-dialog v-model="vol.visible" title="设置音量" width="440px">
      <p class="dlg-note">将对选中的 {{ vol.ids.length }} 条任务生效，功放/LED 子任务一并同步。</p>
      <el-slider v-model="vol.value" :min="0" :max="100" show-input />
      <template #footer>
        <el-button @click="vol.visible = false">取消</el-button>
        <el-button type="primary" :loading="vol.saving" @click="submitVolume">确定</el-button>
      </template>
    </el-dialog>

    <!-- 新建 / 编辑任务 -->
    <el-dialog v-model="dlg.visible" :title="dlg.title" width="900px" top="4vh">
      <!--
        分步向导，对齐 :80 的「上一步 / 下一步 / 取消」。
        ⚠ 用 v-show 而不是 v-if：各步的输入必须始终挂载着，
          否则切步骤会把没提交的内容连同组件一起销毁。
      -->
      <el-steps :active="step" finish-status="success" simple class="mb12">
        <el-step title="基本信息" />
        <el-step title="媒体与终端" />
        <el-step title="时间与播放" />
      </el-steps>

      <el-form :model="dlg.form" label-width="120px">
        <div v-show="step === 0">
        <el-divider content-position="left">基本信息</el-divider>
        <el-row :gutter="12">
          <el-col :span="12">
            <!-- 表单项名称照 :80 的「添加」弹窗（docs/image/oktw/子页规格.txt） -->
            <el-form-item label="任务名" required>
              <el-input v-model="dlg.form.taskname" maxlength="85" show-word-limit placeholder="请输入任务名" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="所属分组" required>
              <el-select v-model="dlg.form.folderId" class="fill">
                <el-option v-for="f in flatFolders" :key="f.id" :label="f.name" :value="f.id" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="12">
          <el-col :span="12">
            <el-form-item label="方案状态">
              <!-- 0 = 启用、1 = 停用，与列注释相反，见列表里的说明 -->
              <el-radio-group v-model="dlg.form.projectstate">
                <el-radio :value="0">启用</el-radio>
                <el-radio :value="1">停用</el-radio>
              </el-radio-group>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="随机播放">
              <!-- 取值反直觉：0 = 随机，1 = 顺序。旧库如此，不能顺手对调 -->
              <el-radio-group v-model="dlg.form.israndomplay">
                <el-radio :value="1">顺序</el-radio>
                <el-radio :value="0">随机</el-radio>
              </el-radio-group>
            </el-form-item>
          </el-col>
        </el-row>

        </div>

        <div v-show="step === 1">
        <el-divider content-position="left">媒体清单</el-divider>
        <el-form-item label="选择媒体">
          <el-select
            v-model="selectedMediaIds"
            multiple
            filterable
            remote
            reserve-keyword
            collapse-tags
            collapse-tags-tooltip
            :remote-method="searchMedia"
            :loading="mediaLoading"
            placeholder="输入关键字搜索媒体"
            class="fill"
          >
            <el-option v-for="m in mediaOptions" :key="m.id" :label="m.name" :value="m.id">
              <span>{{ m.name }}</span>
              <span class="opt-sub">{{ m.folderName }} · {{ fmtDuration(m.timelength) }}</span>
            </el-option>
          </el-select>
        </el-form-item>
        <el-form-item v-if="selectedMediaIds.length" label="播放顺序">
          <div class="sortable">
            <div v-for="(id, i) in selectedMediaIds" :key="id" class="sort-item">
              <span class="sort-idx">{{ i + 1 }}</span>
              <span class="sort-name">{{ mediaLabel(id) }}</span>
              <el-button link :icon="ArrowUp" :disabled="i === 0" @click="moveMedia(i, -1)" />
              <el-button link :icon="ArrowDown" :disabled="i === selectedMediaIds.length - 1" @click="moveMedia(i, 1)" />
              <el-button link type="danger" :icon="Close" @click="selectedMediaIds.splice(i, 1)" />
            </div>
          </div>
        </el-form-item>

        <el-divider content-position="left">终端清单</el-divider>
        <el-form-item label="选择终端">
          <TerminalTree
            v-model="selectedTerminalIds"
            :terminals="terminalOptions"
            :loading="terminalLoading"
            height="260px"
            @search="searchTerminals"
          />
        </el-form-item>

        </div>

        <div v-show="step === 2">
        <el-divider content-position="left">时间安排</el-divider>
        <el-row :gutter="12">
          <el-col :span="6">
            <el-form-item label="开始日期" required>
              <el-date-picker
                v-model="dateRange[0]"
                type="date"
                value-format="YYYY-MM-DD"
                placeholder="请选择开始日期"
                class="fill"
              />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="结束日期" required>
              <el-date-picker
                v-model="dateRange[1]"
                type="date"
                value-format="YYYY-MM-DD"
                placeholder="请选择结束日期"
                class="fill"
              />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="播放时间" required>
              <el-time-picker v-model="dlg.form.schedule.playtime" value-format="HH:mm:ss" class="fill" />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="结束时间">
              <el-time-picker v-model="dlg.form.schedule.endtime" value-format="HH:mm:ss" class="fill" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="执行模式">
          <el-checkbox-group v-model="weekdaySel">
            <el-checkbox v-for="(w, i) in WEEK" :key="i" :value="i">{{ w }}</el-checkbox>
          </el-checkbox-group>
        </el-form-item>
        <el-form-item label="当天停用">
          <el-date-picker
            v-model="dlg.form.schedule.disableday"
            type="date"
            value-format="YYYY-MM-DD"
            placeholder="选填，指定当天不执行"
            clearable
          />
        </el-form-item>

        <el-divider content-position="left">播放参数</el-divider>
        <el-row :gutter="12">
          <el-col :span="8">
            <el-form-item label="播放模式">
              <el-select v-model="dlg.form.playback.timelengthtype" class="fill">
                <el-option label="普通模式" :value="1" />
                <el-option label="循环模式" :value="2" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item :label="dlg.form.playback.timelengthtype === 1 ? '播放时长' : '播放次数'">
              <el-input-number v-model="dlg.form.playback.timelength" :min="0" :max="86400" class="fill" />
              <span class="form-tip">{{ dlg.form.playback.timelengthtype === 1 ? "秒" : "次" }}</span>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="任务音量">
              <el-slider v-model="dlg.form.playback.defaultvolume" :min="0" :max="100" show-input />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="12">
          <el-col :span="8">
            <el-form-item label="任务等级">
              <el-input-number
                v-model="dlg.form.playback.priority"
                :min="priorityRange.min"
                :max="priorityRange.max"
                class="fill"
              />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="本地优先播放">
              <el-switch v-model="localPlayOn" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="发送模式">
              <el-select v-model="dlg.form.power.datasendmodel" class="fill">
                <el-option label="单播" :value="0" />
                <el-option label="组播" :value="1" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="12">
          <el-col :span="8">
            <el-form-item label="间隔时间(秒)">
              <el-input-number v-model="dlg.form.playback.interval_s" :min="0" class="fill" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="间隔播放模式">
              <el-select v-model="dlg.form.playback.intplaylengthtype" class="fill">
                <el-option label="按时间" :value="1" />
                <el-option label="按循环" :value="2" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="间隔播放时长">
              <el-input-number v-model="dlg.form.playback.intplaylength" :min="0" class="fill" />
            </el-form-item>
          </el-col>
        </el-row>
        <!--
          prepower 的单位是「秒」不是分钟：旧界面下拉就是 0/5/…/55 秒 + 1~5 分钟，
          默认 15 秒。这里沿用同一组取值，避免用户填出旧版下拉给不出的数。
        -->
        <el-form-item label="预开电源">
          <el-select v-model="dlg.form.power.prepower" style="width: 120px">
            <el-option v-for="s in prepowerSeconds" :key="s" :label="`${s} 秒`" :value="s" />
            <el-option v-for="m in [1, 2, 3, 4, 5]" :key="`m${m}`" :label="`${m} 分钟`" :value="m * 60" />
          </el-select>
        </el-form-item>

        <!--
          :80 表单里的「led播放 / Led字幕 / Led速度」。
          打开后会额外建一条 tasktype = 30 的 LED 子任务（sec_task_id 指回本任务），
          除任务名外整行照抄主任务，终端也跟主任务一致。关掉即删掉那条子任务。
        -->
        <el-divider content-position="left">led播放</el-divider>
        <el-form-item label="led播放">
          <el-switch v-model="ledOn" />
        </el-form-item>
        <template v-if="ledOn">
          <el-form-item label="LED 任务名称">
            <el-input v-model="dlg.form.led.name" maxlength="85" show-word-limit placeholder="留空则与任务名相同" />
          </el-form-item>
          <el-form-item label="Led字幕" required>
            <el-input
              v-model="dlg.form.led.text"
              type="textarea"
              :rows="3"
              maxlength="341"
              show-word-limit
              placeholder="请输入Led字幕内容"
            />
          </el-form-item>
          <el-row :gutter="12">
            <el-col :span="8">
              <el-form-item label="Led速度">
                <el-input-number v-model="dlg.form.led.speed" :min="0" :max="10" class="fill" />
              </el-form-item>
            </el-col>
            <el-col :span="8">
              <el-form-item label="显示模式">
                <el-input-number v-model="dlg.form.led.ledmode" :min="0" :max="10" class="fill" />
              </el-form-item>
            </el-col>
          </el-row>
        </template>
        </div>
      </el-form>

      <template #footer>
        <el-button @click="dlg.visible = false">取消</el-button>
        <el-button :disabled="step === 0" @click="step--">上一步</el-button>
        <el-button v-if="step < 2" type="primary" @click="step++">下一步</el-button>
        <el-button v-else type="primary" :loading="dlg.saving" @click="submit">确定</el-button>
      </template>
    </el-dialog>

    <!-- 复制任务 -->
    <el-dialog v-model="cp.visible" title="复制任务" width="480px">
      <el-form label-width="100px">
        <el-form-item label="新任务名称" required>
          <el-input v-model="cp.name" maxlength="85" show-word-limit />
        </el-form-item>
        <el-form-item label="目标分组" required>
          <el-select v-model="cp.folderId" class="fill">
            <el-option v-for="f in flatFolders" :key="f.id" :label="f.name" :value="f.id" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="cp.visible = false">取消</el-button>
        <el-button type="primary" :loading="cp.saving" @click="submitCopy">确定</el-button>
      </template>
    </el-dialog>

    <!-- 批量添加终端 -->
    <el-dialog v-model="sync.visible" title="批量添加终端" width="560px">
      <el-alert type="info" :closable="false" show-icon class="mb12">
        把选中的终端追加到 {{ sync.taskIds.length }} 条任务上；已在清单里的终端会跳过。
      </el-alert>
      <TerminalTree
        v-model="sync.terminalIds"
        :terminals="terminalOptions"
        :loading="terminalLoading"
        height="300px"
        @search="searchTerminals"
      />
      <template #footer>
        <el-button @click="sync.visible = false">取消</el-button>
        <el-button type="primary" :disabled="!sync.terminalIds.length" :loading="sync.saving" @click="submitSync">
          确定
        </el-button>
      </template>
    </el-dialog>

    <!-- 删除影响面 -->
    <el-dialog v-model="del.visible" title="删除任务" width="640px">
      <el-alert type="error" :closable="false" show-icon class="mb12">
        删除任务会一并清掉它的媒体清单、终端清单、快捷键映射与离线记录，且<b>不可恢复</b>。
      </el-alert>

      <el-table v-if="del.preview?.deletable.length" :data="del.preview.deletable" size="small" max-height="300">
        <el-table-column prop="taskname" label="任务" min-width="140" />
        <el-table-column label="影响面" min-width="330">
          <template #default="{ row }">
            <el-tag v-if="row.impact?.media" size="small" class="mr4">媒体 {{ row.impact?.media }}</el-tag>
            <el-tag v-if="row.impact?.terminals" size="small" class="mr4">终端 {{ row.impact?.terminals }}</el-tag>
            <el-tag v-if="row.impact?.shortcutKeys" size="small" class="mr4">快捷键 {{ row.impact?.shortcutKeys }}</el-tag>
            <el-tag v-if="row.impact?.offlineTasks" size="small" class="mr4">离线任务 {{ row.impact?.offlineTasks }}</el-tag>
            <el-tag v-if="row.impact?.offlineMedia" size="small" class="mr4">离线媒体 {{ row.impact?.offlineMedia }}</el-tag>
            <el-tag v-if="row.impact?.powerTaskId" type="warning" size="small" class="mr4">
              功放子任务 {{ row.impact?.powerTaskId }} 一并删除
            </el-tag>
            <el-tag v-if="row.impact?.ledTaskId" type="warning" size="small" class="mr4">
              LED 子任务 {{ row.impact?.ledTaskId }} 一并删除
            </el-tag>
            <el-tag v-if="row.impact?.otherLinked" type="danger" size="small" effect="plain">
              另有 {{ row.impact?.otherLinked }} 条任务关联着它，不会被删除，关联会失效
            </el-tag>
          </template>
        </el-table-column>
      </el-table>

      <el-alert v-if="del.preview?.blocked.length" type="warning" :closable="false" class="mt12">
        以下任务不会被删除：
        <div v-for="b in del.preview.blocked" :key="b.id">· {{ b.name || b.id }}：{{ b.detail }}</div>
      </el-alert>

      <template #footer>
        <el-button @click="del.visible = false">取消</el-button>
        <el-button
          type="danger"
          :disabled="!del.preview?.deletable.length"
          :loading="del.saving"
          @click="submitDelete"
        >
          确认删除
        </el-button>
      </template>
    </el-dialog>

    <!-- 分组新建 / 重命名 -->
    <el-dialog v-model="fd.visible" :title="fd.title" width="440px">
      <el-form label-width="90px">
        <el-form-item label="分组名称" required>
          <el-input v-model="fd.name" maxlength="16" show-word-limit />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="fd.visible = false">取消</el-button>
        <el-button type="primary" :loading="fd.saving" @click="submitFolder">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="tsx" name="taskList">
import {
  ArrowDown,
  ArrowUp,
  CirclePlus,
  Close,
  CopyDocument,
  Delete,
  EditPen,
  FolderAdd,
  VideoPause,
  VideoPlay,
  WarningFilled
} from "@element-plus/icons-vue";
import { ElMessage, ElMessageBox, ElNotification } from "element-plus";
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";

import {
  cancelTaskEmergencyApi,
  controlTaskApi,
  copyTaskApi,
  createTaskApi,
  createTaskFolderApi,
  deleteTaskFolderApi,
  deleteTasksApi,
  getTaskApi,
  getTaskFolderTreeApi,
  getTaskListApi,
  previewDeleteTasksApi,
  renameTaskFolderApi,
  searchTaskMediaApi,
  searchTaskTerminalsApi,
  setTaskEmergencyApi,
  setTaskProjectStateApi,
  setTaskVolumeApi,
  syncTaskTerminalsApi,
  updateTaskApi
} from "@/api/modules/task";
import type {
  MediaOption,
  TaskAction,
  TaskControlResult,
  TaskDeletePreview,
  TaskFolderNode,
  TaskRow,
  TaskTerminalOption
} from "@/api/modules/task";
import ProTable from "@/components/ProTable/index.vue";
import TerminalTree from "@/components/TerminalTree/index.vue";
import { useAuthStore } from "@/stores/modules/auth";
import type { ColumnProps, ProTableInstance } from "@/components/ProTable/interface";

const WEEK = ["一", "二", "三", "四", "五", "六", "日"];

const route = useRoute();
const router = useRouter();

const authStore = useAuthStore();
const btn = computed(() => (authStore.authButtonListGet as any)?.task ?? {});
const canAdd = computed(() => !!btn.value.add);
const canEdit = computed(() => !!btn.value.edit);
const canDelete = computed(() => !!btn.value.delete);
const canControl = computed(() => !!btn.value.control);
const canCopy = computed(() => !!btn.value.copy);
const canFolder = computed(() => !!btn.value.folder);

/** ProTable 的 selectedListIds 是 string[]，统一转成数字 id */
const toIds = (raw: (string | number)[]) => (raw ?? []).map(Number).filter(n => Number.isFinite(n) && n > 0);

const proTableRef = ref<ProTableInstance>();
const treeRef = ref();
const folders = ref<TaskFolderNode[]>([]);
const currentFolder = ref<number>(0);
const scopeNote = ref("");
const initParam = reactive({ folderId: 0, orderBy: "", order: "" });

const onSortChange = ({ prop, order }: { prop: string; order: string | null }) => {
  if (!order) {
    initParam.orderBy = "";
    initParam.order = "";
    return;
  }
  initParam.orderBy = prop;
  initParam.order = order === "ascending" ? "asc" : "desc";
};

/** 把分组树摊平成下拉用的一维数组，名字带层级缩进 */
const flatFolders = computed(() => {
  const out: { id: number; name: string }[] = [];
  const walk = (nodes: TaskFolderNode[], depth: number) => {
    nodes.forEach(n => {
      out.push({ id: n.id, name: "　".repeat(depth) + n.name });
      if (n.children?.length) walk(n.children, depth + 1);
    });
  };
  walk(folders.value, 0);
  return out;
});

const columns = reactive<ColumnProps<TaskRow>[]>([
  { type: "selection", fixed: "left", width: 50 },
  { type: "expand", width: 40 },
  // 列清单严格照 :80（页面规格.txt「文件广播」12 列）：
  // 广播任务名称 | 执行模式 | 开始日期 | 结束日期 | 执行时间 | 播放时长 |
  // 状态 | 播放模式 | 音量 | 任务级别 | 播放状态 | 操作
  //
  // 原来的「起止日期」合成列拆成开始/结束两列；「清单」「创建者」两列去掉 ——
  // 清单内容在展开行里能看全，创建者在编辑弹窗里。
  { prop: "taskname", label: "广播任务名称", minWidth: 160, sortable: "custom", search: { el: "input", key: "keyword" } },
  { prop: "weekdays", label: "执行模式", minWidth: 130 },
  { prop: "startdate", label: "开始日期", width: 115 },
  { prop: "enddate", label: "结束日期", width: 115 },
  { prop: "playtime", label: "执行时间", width: 100, sortable: "custom" },
  { prop: "timelengthText", label: "播放时长", width: 110 },
  { prop: "projectstate", label: "状态", width: 80, sortable: "custom" },
  { prop: "playModeText", label: "播放模式", width: 95 },
  { prop: "defaultvolume", label: "音量", width: 70 },
  { prop: "priority", label: "任务级别", width: 90, sortable: "custom" },
  { prop: "state", label: "播放状态", width: 110, sortable: "custom" },
  { prop: "operation", label: "操作", fixed: "right", width: 190 }
]);

const stateTagType = (s: number) => (s === 1 ? "warning" : s === 3 ? "success" : "info");

const dataCallback = (data: any) => {
  scopeNote.value = data.scopeNote ?? "";
  return { list: data.list, total: data.total, pageNum: data.pageNum, pageSize: data.pageSize };
};

const refresh = () => proTableRef.value?.getTableList();

const loadFolders = async () => {
  const { data } = await getTaskFolderTreeApi();
  folders.value = data ?? [];
  if (!currentFolder.value && folders.value.length) {
    selectFolder(folders.value[0].id);
  }
};

const selectFolder = (id: number) => {
  currentFolder.value = id;
  initParam.folderId = id;
};

const onFolderChange = (node: TaskFolderNode) => {
  if (node?.id) selectFolder(node.id);
};

const fmtDuration = (sec: number) => {
  if (!sec) return "—";
  const m = Math.floor(sec / 60);
  const s = sec % 60;
  return `${m}:${String(s).padStart(2, "0")}`;
};

/* ---------------- 批量操作 ---------------- */

/** 统一呈现「部分成功」结果：能做的做掉，做不了的逐条列原因 */
const reportControl = (res: TaskControlResult, action: string) => {
  const ok = res.succeeded.length;
  if (!res.blocked.length) {
    ElMessage.success(`${action}成功，共 ${ok} 条`);
  } else {
    const detail = res.blocked.map(b => `${b.name || b.id}：${b.detail}`).join("；");
    ElMessageBox.alert(detail, `${action}完成：成功 ${ok} 条，跳过 ${res.blocked.length} 条`, {
      type: ok ? "warning" : "error"
    });
  }
  refresh();
  loadFolders();
};

/* 操作列的「终端 / 媒体」两个只读弹窗。数据列表接口已经带回来了，不再多请求一次。 */
const tm = reactive({ visible: false, title: "", list: [] as TaskRow["terminals"] });
const md = reactive({ visible: false, title: "", list: [] as TaskRow["media"] });

const openTerminals = (row: TaskRow) => {
  tm.list = row.terminals ?? [];
  tm.title = `「${row.taskname}」的终端`;
  tm.visible = true;
};

const openMedia = (row: TaskRow) => {
  md.list = row.media ?? [];
  md.title = `「${row.taskname}」的媒体`;
  md.visible = true;
};

const humanSize = (n: number) => {
  if (!n) return "0";
  if (n < 1024) return `${n} KB`;
  return `${(n / 1024).toFixed(1)} MB`;
};

const control = async (action: TaskAction, raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning("请先勾选任务");
  const label = { start: "启动", stop: "停止", pause: "暂停", resume: "恢复" }[action];
  const { data } = await controlTaskApi(action, ids);
  reportControl(data, label);
};

const onMoreCmd = async (cmd: string, raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning("请先勾选任务");
  if (cmd === "pause" || cmd === "resume") return control(cmd as TaskAction, raw);
  if (cmd === "volume") return openVolume(raw);
  if (cmd === "sync") {
    sync.taskIds = ids;
    sync.terminalIds = [];
    sync.visible = true;
    await searchTerminals("");
    return;
  }
  const enable = cmd === "enable";
  const { data } = await setTaskProjectStateApi(ids, enable);
  reportControl(data, enable ? "启用方案" : "停用方案");
};

/* ---------------- 紧急任务 / 设置音量 ---------------- */

const setEmergency = async (raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning("请先勾选任务");
  if (ids.length > 1) return ElMessage.warning("紧急任务全系统只能有一条，请只勾选一条");
  const { data } = await setTaskEmergencyApi(ids[0]);
  ElMessage.success(`已把「${data.taskName}」设为紧急任务`);
  refresh();
};

const cancelEmergency = async () => {
  const { data } = await cancelTaskEmergencyApi();
  ElMessage.success(`已取消紧急任务「${data.taskName}」`);
  refresh();
};

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
    reportControl(data, "设置音量");
  } finally {
    vol.saving = false;
  }
};

/* ---------------- 媒体 / 终端选择 ---------------- */

const mediaOptions = ref<MediaOption[]>([]);
const mediaLoading = ref(false);
const selectedMediaIds = ref<number[]>([]);
/** 已选媒体的名字缓存：搜索换了关键字后选项列表会被替换，靠它保住已选项的显示名 */
const mediaNames = reactive<Record<number, string>>({});

const searchMedia = async (kw: string) => {
  mediaLoading.value = true;
  try {
    const { data } = await searchTaskMediaApi(kw ?? "");
    mediaOptions.value = data ?? [];
    (data ?? []).forEach(m => (mediaNames[m.id] = m.name));
  } finally {
    mediaLoading.value = false;
  }
};

const mediaLabel = (id: number) => mediaNames[id] ?? `媒体 ${id}`;

const moveMedia = (i: number, delta: number) => {
  const arr = selectedMediaIds.value;
  const j = i + delta;
  if (j < 0 || j >= arr.length) return;
  [arr[i], arr[j]] = [arr[j], arr[i]];
};

const terminalOptions = ref<TaskTerminalOption[]>([]);
const terminalLoading = ref(false);
const selectedTerminalIds = ref<number[]>([]);
const terminalGroupOf = reactive<Record<number, number>>({});

const searchTerminals = async (kw: string) => {
  terminalLoading.value = true;
  try {
    const { data } = await searchTaskTerminalsApi(kw ?? "");
    terminalOptions.value = data ?? [];
    (data ?? []).forEach(t => (terminalGroupOf[t.id] = t.groupId));
  } finally {
    terminalLoading.value = false;
  }
};

/* ---------------- 新建 / 编辑 ---------------- */

const today = () => new Date().toISOString().slice(0, 10);

/** 旧界面的提前开电源下拉：0、5、10 … 55 秒 */
const prepowerSeconds = Array.from({ length: 12 }, (_, i) => i * 5);

const emptyForm = () => ({
  taskname: "",
  folderId: currentFolder.value || 0,
  // 0 = 启用，与旧版 INSERT 的默认值一致（新建出来就是启用的）
  projectstate: 0,
  israndomplay: 1,
  media: [] as { mediaId: number; sort: number }[],
  terminals: [] as { terminalId: number; groupId: number; area: string }[],
  schedule: {
    startdate: today(),
    enddate: today(),
    playtime: "08:00:00",
    endtime: "00:00:00",
    exemodel: "1111111",
    disableday: ""
  },
  playback: {
    timelengthtype: 2,
    timelength: 1,
    interval_s: 0,
    intplaylength: 1,
    intplaylengthtype: 2,
    defaultvolume: 80,
    priority: 10,
    localplay: 0
  },
  power: { prepower: 0, datasendmodel: 0 },
  // LED 字幕子任务。ledOn 关掉时不提交这一段（后端据此删掉已有的子任务）。
  led: { name: "", text: "", speed: 1, ledmode: 0 }
});

const dlg = reactive({
  visible: false,
  saving: false,
  isEdit: false,
  title: "",
  id: 0,
  form: emptyForm()
});

const priorityRange = reactive({ min: 0, max: 99 });

/** 新建/修改弹窗的当前步骤（0 基本信息 / 1 媒体与终端 / 2 时间与播放） */
const step = ref(0);

// 开始/结束日期是两个独立的选择器（照 :80），共用这个数组：[0] 开始、[1] 结束。
// ⚠ 必须 deep —— v-model 改的是数组元素，不是数组本身，浅层 watch 不会触发。
const dateRange = ref<[string, string]>([today(), today()]);
watch(
  dateRange,
  v => {
    dlg.form.schedule.startdate = v?.[0] ?? "";
    dlg.form.schedule.enddate = v?.[1] ?? "";
  },
  { deep: true }
);

// 星期掩码 <-> 复选框
const weekdaySel = ref<number[]>([0, 1, 2, 3, 4, 5, 6]);
watch(weekdaySel, v => {
  dlg.form.schedule.exemodel = Array.from({ length: 7 }, (_, i) => (v.includes(i) ? "1" : "0")).join("");
});

const localPlayOn = ref(false);
watch(localPlayOn, v => (dlg.form.playback.localplay = v ? 1 : 0));

/** led播放 开关。关掉时提交 led: null，后端会把已有的 LED 子任务删掉 */
const ledOn = ref(false);

const applyMask = (mask: string) => {
  weekdaySel.value = mask.split("").reduce<number[]>((acc, c, i) => (c === "1" ? [...acc, i] : acc), []);
};

const openCreate = async () => {
  if (!currentFolder.value) return ElMessage.warning("请先在左侧选择一个任务分组");
  step.value = 0;
  Object.assign(dlg, { visible: true, isEdit: false, title: "添加", id: 0, saving: false, form: emptyForm() });
  dateRange.value = [dlg.form.schedule.startdate, dlg.form.schedule.enddate];
  applyMask(dlg.form.schedule.exemodel);
  localPlayOn.value = false;
  selectedMediaIds.value = [];
  selectedTerminalIds.value = [];
  priorityRange.min = 0;
  priorityRange.max = 99;
  await Promise.all([searchMedia(""), searchTerminals("")]);
};

const openEdit = async (row: TaskRow) => {
  const { data } = await getTaskApi(row.taskid);
  step.value = 0;
  Object.assign(dlg, {
    visible: true,
    isEdit: true,
    title: `修改任务：${data.taskname}`,
    id: data.taskid,
    saving: false,
    form: {
      taskname: data.taskname,
      folderId: data.folderId,
      projectstate: data.projectstate,
      israndomplay: data.israndomplay,
      media: [],
      terminals: [],
      schedule: {
        startdate: data.startdate,
        enddate: data.enddate,
        playtime: data.playtime,
        endtime: data.endtime,
        exemodel: data.exemodel,
        // 旧库用 0000-00-00 表示「没有设置」，界面上要显示成空
        disableday: data.disableday && data.disableday !== "0000-00-00" ? data.disableday : ""
      },
      playback: {
        timelengthtype: data.timelengthtype,
        timelength: data.timelength,
        interval_s: data.interval_s,
        intplaylength: data.intplaylength,
        intplaylengthtype: data.intplaylengthtype,
        defaultvolume: data.defaultvolume,
        priority: data.priority,
        localplay: data.localplay
      },
      power: { prepower: data.prepower, datasendmodel: data.datasendmodel },
      led: {
        name: data.led?.name ?? "",
        text: data.led?.text ?? "",
        speed: data.led?.speed ?? 1,
        ledmode: data.led?.ledmode ?? 0
      }
    }
  });
  dateRange.value = [data.startdate, data.enddate];
  applyMask(data.exemodel);
  localPlayOn.value = data.localplay === 1;
  // 有 LED 子任务就把开关打开
  ledOn.value = !!data.led;
  priorityRange.min = data.priorityMin;
  priorityRange.max = data.priorityMax;

  // 先把已有清单的名字灌进缓存，再拉一次候选，避免已选项显示成裸 id
  data.media.forEach(m => (mediaNames[m.mediaId] = m.name));
  data.terminals.forEach(t => (terminalGroupOf[t.terminalId] = t.groupId));
  selectedMediaIds.value = data.media.map(m => m.mediaId);
  selectedTerminalIds.value = data.terminals.map(t => t.terminalId);
  await Promise.all([searchMedia(""), searchTerminals("")]);
};

const submit = async () => {
  const f = dlg.form;
  // 校验不通过时把步骤切到出问题的那一步，否则用户看不见是哪一项没填
  if (!f.taskname.trim()) {
    step.value = 0;
    return ElMessage.warning("请输入任务名");
  }
  if (!f.folderId) {
    step.value = 0;
    return ElMessage.warning("请选择所属分组");
  }
  if (ledOn.value && !f.led.text.trim()) {
    step.value = 2;
    return ElMessage.warning("请输入Led字幕内容");
  }

  const payload = {
    ...f,
    // 关掉 led播放 就传 null —— 服务端据此删掉已有的 LED 子任务
    led: ledOn.value ? { ...f.led, name: f.led.name.trim(), text: f.led.text.trim() } : null,
    // sort 按数组下标给，服务端还会再规整一次
    media: selectedMediaIds.value.map((id, i) => ({ mediaId: id, sort: i })),
    terminals: selectedTerminalIds.value.map(id => ({
      terminalId: id,
      groupId: terminalGroupOf[id] ?? 0,
      area: "11111111"
    }))
  };

  dlg.saving = true;
  try {
    const { data } = dlg.isEdit ? await updateTaskApi(dlg.id, payload) : await createTaskApi(payload);
    const parts = [`媒体 ${data.mediaCount} 条`, `终端 ${data.terminalCount} 台`];
    if (data.powerTaskId) parts.push(`功放子任务 ${data.powerTaskId}`);
    if (data.ledTaskId) parts.push(`LED 子任务 ${data.ledTaskId}`);
    ElNotification({
      title: dlg.isEdit ? "修改成功" : "创建成功",
      message: parts.join("，"),
      type: "success"
    });
    dlg.visible = false;
    refresh();
    loadFolders();
  } finally {
    dlg.saving = false;
  }
};

/* ---------------- 复制 ---------------- */

const cp = reactive({ visible: false, saving: false, id: 0, name: "", folderId: 0 });

const openCopy = (row: TaskRow) => {
  Object.assign(cp, {
    visible: true,
    saving: false,
    id: row.taskid,
    name: `${row.taskname}-副本`,
    folderId: row.folderId || currentFolder.value
  });
};

const submitCopy = async () => {
  if (!cp.name.trim()) return ElMessage.warning("请输入新任务名称");
  if (!cp.folderId) return ElMessage.warning("请选择目标分组");
  cp.saving = true;
  try {
    const { data } = await copyTaskApi(cp.id, cp.folderId, cp.name.trim());
    ElMessage.success(`已复制为任务 ${data.taskid}`);
    cp.visible = false;
    refresh();
    loadFolders();
  } finally {
    cp.saving = false;
  }
};

/* ---------------- 批量添加终端 ---------------- */

const sync = reactive({ visible: false, saving: false, taskIds: [] as number[], terminalIds: [] as number[] });

const submitSync = async () => {
  sync.saving = true;
  try {
    const { data } = await syncTaskTerminalsApi(sync.taskIds, sync.terminalIds);
    sync.visible = false;
    ElMessage.success(`已新增 ${data.added} 条终端关联`);
    refresh();
  } finally {
    sync.saving = false;
  }
};

/* ---------------- 删除 ---------------- */

const del = reactive({ visible: false, saving: false, preview: null as TaskDeletePreview | null });

const openDelete = async (raw: (string | number)[]) => {
  const ids = toIds(raw);
  if (!ids.length) return ElMessage.warning("请先勾选任务");
  const { data } = await previewDeleteTasksApi(ids);
  del.preview = data;
  del.visible = true;
};

const submitDelete = async () => {
  del.saving = true;
  try {
    const ids = del.preview!.deletable.map(d => d.taskid);
    const { data } = await deleteTasksApi(ids);
    del.visible = false;
    const extra = data.deletedSubTasks.length ? `，连带子任务 ${data.deletedSubTasks.length} 条` : "";
    ElMessage.success(`已删除 ${data.deleted.length} 条任务${extra}`);
    refresh();
    loadFolders();
  } finally {
    del.saving = false;
  }
};

/* ---------------- 分组 ---------------- */

const fd = reactive({ visible: false, saving: false, title: "", id: 0, name: "", parentId: 0 });

const openFolderCreate = () => {
  Object.assign(fd, {
    visible: true,
    saving: false,
    title: "新建任务分组",
    id: 0,
    name: "",
    parentId: currentFolder.value || 0
  });
};

const openFolderRename = (node: TaskFolderNode) => {
  Object.assign(fd, { visible: true, saving: false, title: "重命名分组", id: node.id, name: node.name, parentId: 0 });
};

const submitFolder = async () => {
  if (!fd.name.trim()) return ElMessage.warning("请输入分组名称");
  fd.saving = true;
  try {
    if (fd.id) {
      await renameTaskFolderApi(fd.id, fd.name.trim());
    } else {
      await createTaskFolderApi({ name: fd.name.trim(), parentId: fd.parentId });
    }
    fd.visible = false;
    ElMessage.success("保存成功");
    loadFolders();
  } finally {
    fd.saving = false;
  }
};

const confirmFolderDelete = async (node: TaskFolderNode) => {
  await ElMessageBox.confirm(
    `删除分组「${node.name}」会同时删除它<b>整棵子树</b>下的全部任务，不可恢复。是否继续？`,
    "删除任务分组",
    { type: "warning", dangerouslyUseHTMLString: true, confirmButtonText: "确定删除" }
  );
  const { data } = await deleteTaskFolderApi(node.id);
  ElNotification({
    title: "删除完成",
    message: `分组 ${data.deletedFolders.length} 个、任务 ${data.deletedTasks.length} 条、子任务 ${data.deletedSubTasks.length} 条`,
    type: "success"
  });
  if (currentFolder.value === node.id) {
    currentFolder.value = 0;
    initParam.folderId = 0;
  }
  loadFolders();
  refresh();
};

onMounted(async () => {
  await loadFolders();
  // 首页的「新增文件广播」跳过来时带着 action=create，直接把新建弹窗打开。
  // 打开后立刻把 query 抹掉，免得刷新页面又弹一次。
  if (route.query.action === "create" && canAdd.value) {
    await openCreate();
    router.replace({ path: "/task" });
  }
});
</script>

<style scoped lang="scss">
.task-page {
  display: flex;
  gap: 10px;
  height: 100%;
}
.tree-panel {
  display: flex;
  flex: 0 0 240px;
  flex-direction: column;
  padding: 10px;
  background: var(--el-bg-color);
  border-radius: 6px;
}
.list-panel {
  flex: 1;
  min-width: 0;
}
.tree-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 8px;
  margin-bottom: 6px;
  border-bottom: 1px solid var(--el-border-color-lighter);
}
.tree-title {
  font-weight: 600;
}
.tree-node {
  display: flex;
  flex: 1;
  gap: 6px;
  align-items: center;
  padding-right: 6px;
  overflow: hidden;
}
.tree-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.tree-count {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.tree-ops {
  display: flex;
  gap: 6px;
  margin-left: auto;
  .el-icon {
    color: var(--el-color-primary);
    &.danger {
      color: var(--el-color-danger);
    }
  }
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
  gap: 8px;
  align-items: center;
}
.expand {
  display: flex;
  gap: 24px;
  padding: 8px 40px;
}
.expand-col {
  flex: 1;
  min-width: 0;
}
.expand-title {
  margin-bottom: 6px;
  font-size: 12px;
  font-weight: 600;
  color: var(--el-text-color-secondary);
}
.sortable {
  width: 100%;
}
.sort-item {
  display: flex;
  gap: 8px;
  align-items: center;
  padding: 2px 0;
}
.sort-idx {
  width: 22px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
  text-align: right;
}
.sort-name {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.opt-sub {
  margin-left: 10px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.form-tip {
  margin-left: 10px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
  &.block {
    display: block;
    margin-left: 0;
    line-height: 1.6;
  }
}
.warn-icon {
  margin-left: 4px;
  color: var(--el-color-warning);
  vertical-align: middle;
}
.fill {
  width: 100%;
}
.muted {
  color: var(--el-text-color-secondary);
}
.ml6 {
  margin-left: 6px;
}
.empty-note {
  padding: 18px 0;
  font-size: 13px;
  color: var(--el-text-color-secondary);
  text-align: center;
}
.cnt {
  font-size: 12px;
}
.mr4 {
  margin-right: 4px;
}
.mb4 {
  margin-bottom: 4px;
}
.mb12 {
  margin-bottom: 12px;
}
.mt12 {
  margin-top: 12px;
}
</style>
