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
                <el-icon v-if="data.canDelete" class="danger" title="删除分组" @click.stop="confirmFolderDelete(data)">
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
                按钮清单照旧版 FileTaskManager_from.html：
                添加 / 修改 / 删除 / 执行 / 停止 / 暂停 / 恢复 / 启用 / 停用 /
                紧急设置 / 取消紧急设置 / 调整音量。
                旧版没有「批量编辑」这样的下拉，所以不摆；
                「批量添加终端」在终端管理的批量操作里，那边留着。
              -->
              <el-button type="primary" :icon="CirclePlus" :disabled="!canAdd" @click="openCreate">添加</el-button>

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
          <el-button type="primary" link :icon="CopyDocument" :disabled="!canCopy" @click="openCopy(scope.row)"> 复制 </el-button>
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

    <!--
      新建 / 修改任务（对照 ok112 的 AddFileTask_form.html / ModifyFileTask_form.html）。

      旧版是一页到底的表单，分五段：
        任务属性 → 执行时间 → led字幕 + led设备列表 → 媒体文件列表 → 终端列表
      这里照它排。原先那个「基本信息 / 媒体与终端 / 时间与播放」三步向导取消了 ——
      旧版没有分步，来回翻页反而看不到全貌。

      旧版表单里没有的几列（方案状态、结束时间、当天停用、本地优先播放）不摆到表单上：
      新建时用默认值，修改时原样带回去再提交，不会被清掉。
      方案状态另有工具条上的「启用 / 停用」可改。
    -->
    <el-dialog v-model="dlg.visible" :title="dlg.title" width="960px" top="4vh">
      <el-form :model="dlg.form" label-width="110px">
        <el-divider content-position="left">任务属性</el-divider>
        <el-row :gutter="16">
          <el-col :span="12">
            <!-- 旧版 maxlength="8"：任务名称最大 8 字节 -->
            <el-form-item label="任务名称" required>
              <el-input v-model="dlg.form.taskname" maxlength="8" show-word-limit placeholder="请输入任务名称" />
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
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="预开电源">
              <el-select v-model="dlg.form.power.prepower" class="fill">
                <el-option v-for="s in prepowerSeconds" :key="s" :label="`${s} 秒`" :value="s" />
                <el-option v-for="m in [1, 2, 3, 4, 5]" :key="`m${m}`" :label="`${m} 分钟`" :value="m * 60" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="随机播放">
              <!-- 旧版是一个复选框；⚠ 取值反直觉：0 = 随机、1 = 顺序 -->
              <el-checkbox v-model="randomOn">选中歌曲将随机播放</el-checkbox>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="发送模式">
              <el-select v-model="dlg.form.power.datasendmodel" class="fill">
                <el-option label="单播" :value="0" />
                <el-option label="组播" :value="1" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="任务级别">
              <el-select v-model="dlg.form.playback.priority" style="width: 110px">
                <el-option v-for="p in priorityOptions" :key="p" :label="String(p)" :value="p" />
              </el-select>
              <span class="form-tip">10 为最高级别</span>
            </el-form-item>
          </el-col>
        </el-row>

        <!--
          播放模式照旧版 intervalmode：
            普通模式 —— 「时长(时:分:秒)」或「循环次数」二选一
            间隔时间 —— 总时长 + 间隔长度，再选「间隔时长」或「间隔次数」
          库里没有 intervalmode 这一列，它由 interval_s 是否为 0 反推。
        -->
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="播放模式">
              <el-select v-model="playMode" class="fill" @change="onPlayModeChange">
                <el-option label="普通模式" :value="0" />
                <el-option label="间隔时间" :value="1" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="led播放">
              <el-checkbox v-model="ledOn">上屏显示 led 字幕</el-checkbox>
            </el-form-item>
          </el-col>
        </el-row>

        <!-- 两行单选左对齐：不占标签列，两行的输入框起点也对齐（.len-line 里给了固定宽度） -->
        <el-form-item v-if="playMode === 0" label-width="0">
          <el-radio-group v-model="dlg.form.playback.timelengthtype" class="len-group">
            <div class="len-line">
              <el-radio :value="1">时长</el-radio>
              <HmsInput v-model="durationSec" :disabled="dlg.form.playback.timelengthtype !== 1" />
            </div>
            <div class="len-line">
              <el-radio :value="2">循环次数</el-radio>
              <!-- el-input-number 改 disabled 后不会更新 aria-disabled，用 key 强制重建 -->
              <el-input-number
                :key="`cyc-${dlg.form.playback.timelengthtype}`"
                v-model="cycleTimes"
                :min="0"
                :max="10"
                :disabled="dlg.form.playback.timelengthtype !== 2"
                :controls="false"
                style="width: 90px"
              />
              <span class="form-tip">0 是无限循环，最大 10 次</span>
            </div>
          </el-radio-group>
        </el-form-item>

        <template v-else>
          <el-form-item label="时长">
            <HmsInput v-model="durationSec" />
          </el-form-item>
          <el-form-item label="间隔长度">
            <HmsInput v-model="dlg.form.playback.interval_s" />
          </el-form-item>
          <el-form-item label-width="0">
            <el-radio-group v-model="dlg.form.playback.intplaylengthtype" class="len-group">
              <div class="len-line">
                <el-radio :value="1">间隔时长</el-radio>
                <HmsInput v-model="dlg.form.playback.intplaylength" :disabled="dlg.form.playback.intplaylengthtype !== 1" />
              </div>
              <div class="len-line">
                <el-radio :value="2">间隔次数</el-radio>
                <el-input-number
                  :key="`int-${dlg.form.playback.intplaylengthtype}`"
                  v-model="intCycleTimes"
                  :min="0"
                  :max="99"
                  :disabled="dlg.form.playback.intplaylengthtype !== 2"
                  :controls="false"
                  style="width: 90px"
                />
              </div>
            </el-radio-group>
          </el-form-item>
        </template>

        <el-divider content-position="left">执行时间</el-divider>
        <el-row :gutter="16">
          <el-col :span="8">
            <el-form-item label="播放时间" required>
              <el-time-picker v-model="dlg.form.schedule.playtime" value-format="HH:mm:ss" class="fill" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="开始日期" required>
              <el-date-picker v-model="dateRange[0]" type="date" value-format="YYYY-MM-DD" class="fill" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="结束日期" required>
              <el-date-picker v-model="dateRange[1]" type="date" value-format="YYYY-MM-DD" class="fill" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="任务音量">
          <el-slider v-model="dlg.form.playback.defaultvolume" :min="0" :max="100" show-input class="vol-slider" />
        </el-form-item>
        <el-form-item label="执行模式">
          <!-- 旧版是「手动 / 每天 / 每星期」下拉，选每星期才出现星期勾选 -->
          <el-select v-model="runMode" style="width: 140px" @change="onRunModeChange">
            <el-option label="手动" :value="0" />
            <el-option label="每天" :value="1" />
            <el-option label="每星期" :value="2" />
          </el-select>
          <el-checkbox-group v-if="runMode === 2" v-model="weekdaySel" class="ml16">
            <el-checkbox v-for="(w, i) in WEEK" :key="i" :value="i">{{ w }}</el-checkbox>
          </el-checkbox-group>
        </el-form-item>

        <template v-if="ledOn">
          <el-divider content-position="left">led字幕</el-divider>
          <!-- 名称与速度并成一行，速度排在字幕上面 -->
          <el-row :gutter="16">
            <el-col :span="12">
              <el-form-item label="LED任务名称">
                <el-input v-model="dlg.form.led.name" maxlength="8" show-word-limit placeholder="留空则与任务名相同" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Led速度">
                <el-select v-model="dlg.form.led.speed" style="width: 110px">
                  <el-option v-for="n in [0, 1, 2, 3, 4, 5]" :key="n" :label="`${n} 级`" :value="n" />
                </el-select>
                <span class="form-tip">0 ~ 5 级</span>
              </el-form-item>
            </el-col>
          </el-row>
          <el-form-item label="led字幕" required>
            <el-input
              v-model="dlg.form.led.text"
              type="textarea"
              :rows="3"
              maxlength="341"
              show-word-limit
              placeholder="请输入 led 字幕内容"
            />
          </el-form-item>
          <el-form-item label="led设备列表">
            <div class="led-dev">
              <div v-if="!ledDevices.length" class="dlg-note">
                还没有登记 LED 设备 —— 先到「云广播管理 → led播放 → LED 设备」登记，这里才能勾选要上屏的设备。
              </div>
              <el-checkbox-group v-else v-model="selectedLedDeviceIds">
                <el-checkbox v-for="d in ledDevices" :key="d.id" :value="d.id">
                  {{ d.name }}
                  <span class="opt-sub">{{ d.terminalname || `终端 ${d.terminalId}` }} · {{ d.ip }}</span>
                </el-checkbox>
              </el-checkbox-group>
            </div>
          </el-form-item>
        </template>

        <!-- 媒体与终端两棵树并排，与旧版表单左右两栏的排法一致 -->
        <el-row :gutter="16">
          <el-col :span="12">
            <el-divider content-position="left">媒体文件列表</el-divider>
            <el-form-item label-width="0">
              <!-- 旧版这里是一棵「媒体库 → 音频文件」的树，不是一条长下拉 -->
              <MediaTree v-model="selectedMediaIds" :selected-names="selectedMediaNames" height="300px" style="width: 100%" />
            </el-form-item>
            <el-form-item v-if="selectedMediaIds.length" label-width="0">
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
          </el-col>
          <el-col :span="12">
            <el-divider content-position="left">终端列表</el-divider>
            <el-form-item label-width="0">
              <TerminalTree
                v-model="selectedTerminalIds"
                v-model:areas="terminalAreas"
                :terminals="terminalOptions"
                :loading="terminalLoading"
                height="300px"
                style="width: 100%"
                @search="searchTerminals"
              />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>

      <template #footer>
        <el-button @click="dlg.visible = false">取消</el-button>
        <el-button type="primary" :loading="dlg.saving" @click="submit">提交</el-button>
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
        <el-button type="danger" :disabled="!del.preview?.deletable.length" :loading="del.saving" @click="submitDelete">
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
  getTaskPriorityRangeApi,
  previewDeleteTasksApi,
  renameTaskFolderApi,
  searchTaskMediaApi,
  searchTaskTerminalsApi,
  setTaskEmergencyApi,
  setTaskProjectStateApi,
  setTaskVolumeApi,
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
import { getLedDevicesApi, type LedDevice } from "@/api/modules/ninemod";
import ProTable from "@/components/ProTable/index.vue";
import MediaTree from "@/components/MediaTree/index.vue";
import HmsInput from "@/components/HmsInput/index.vue";
import TerminalTree from "@/components/TerminalTree/index.vue";
import { useAuthStore } from "@/stores/modules/auth";
import type { ColumnProps, ProTableInstance } from "@/components/ProTable/interface";

// exemodel 是周日打头的 7 位掩码（旧站表单也是「日一二三四五六」的排法）
const WEEK = ["日", "一", "二", "三", "四", "五", "六"];

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
  // 列名逐个照 ok112 的 FileAd/FileTaskManager_from.html + language/chinese.php：
  // 文件广播任务|播放周期|开始日期|结束日期|执行时间|播放时长|状态|播放模式|音量|任务级别|所属用户|正在播放|终端属性
  // 「终端属性」在旧版是一个「浏览」链接，这里已经做成操作列里的「终端(N)」，不再单列一列。
  { prop: "taskname", label: "文件广播任务", minWidth: 160, sortable: "custom", search: { el: "input", key: "keyword" } },
  { prop: "weekdays", label: "播放周期", minWidth: 130 },
  { prop: "startdate", label: "开始日期", width: 115 },
  { prop: "enddate", label: "结束日期", width: 115 },
  { prop: "playtime", label: "执行时间", width: 100, sortable: "custom" },
  { prop: "timelengthText", label: "播放时长", width: 110 },
  { prop: "projectstate", label: "状态", width: 80, sortable: "custom" },
  { prop: "playModeText", label: "播放模式", width: 95 },
  { prop: "defaultvolume", label: "音量", width: 70 },
  { prop: "priority", label: "任务级别", width: 90, sortable: "custom" },
  { prop: "ownerUserName", label: "所属用户", width: 110 },
  { prop: "state", label: "正在播放", width: 110, sortable: "custom" },
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
/** 每台终端的分区/通道掩码（terminaloftask.area），键是终端 id */
const terminalAreas = ref<Record<number, string>>({});

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
  led: { name: "", text: "", speed: 0, ledmode: 0 }
});

const dlg = reactive({
  visible: false,
  saving: false,
  isEdit: false,
  title: "",
  id: 0,
  form: emptyForm()
});

// 兜底值照旧版下拉的口径：下限 10、上限 109（服务端会给准确区间）
const priorityRange = reactive({ min: 10, max: 109 });

/* 旧版是一页到底的表单，没有分步；这里几个 ref 是把界面上的开关映射到库里的列。 */

/** 随机播放复选框。⚠ 取值反直觉：0 = 随机、1 = 顺序 */
const randomOn = computed({
  get: () => dlg.form.israndomplay === 0,
  set: v => (dlg.form.israndomplay = v ? 0 : 1)
});

/** 播放模式：0 普通、1 间隔时间。库里没有这一列，用 interval_s 是否为 0 反推 */
const playMode = ref(0);
const onPlayModeChange = (v: number) => {
  if (v === 0) {
    // 回到普通模式就把间隔那几项清掉，免得留着旧值继续生效
    dlg.form.playback.interval_s = 0;
    dlg.form.playback.intplaylength = 0;
    dlg.form.playback.intplaylengthtype = 1;
  } else {
    dlg.form.playback.timelengthtype = 1;
    if (!dlg.form.playback.interval_s) dlg.form.playback.interval_s = 60;
    if (!dlg.form.playback.intplaylength) dlg.form.playback.intplaylength = 60;
  }
};

/**
 * 「时长」与「循环次数」在库里共用 timelength 一列（按 timelengthtype 区分），
 * 但界面上是两个各自独立的输入框（旧版 lenghtHour/Min/Senc 与 circleTime 也是分开的）。
 * 分开存，提交时再按选中的类型合成，免得切换类型时把秒数当次数显示。
 */
const durationSec = ref(60);
const cycleTimes = ref(1);
const intDurationSec = ref(60);
const intCycleTimes = ref(1);

/** 把库里的 timelength / intplaylength 拆回两个输入框 */
const splitLengths = (pb: { timelengthtype: number; timelength: number; intplaylengthtype: number; intplaylength: number }) => {
  durationSec.value = pb.timelengthtype === 1 ? pb.timelength : 60;
  cycleTimes.value = pb.timelengthtype === 2 ? pb.timelength : 1;
  intDurationSec.value = pb.intplaylengthtype === 1 ? pb.intplaylength : 60;
  intCycleTimes.value = pb.intplaylengthtype === 2 ? pb.intplaylength : 1;
};

/** 任务级别下拉的取值范围由后端按用户组给 */
const priorityOptions = computed(() => {
  const lo = priorityRange.min ?? 10;
  const hi = priorityRange.max ?? 109;
  return Array.from({ length: Math.max(0, hi - lo + 1) }, (_, i) => lo + i);
});

/** 执行模式：0 手动（0000000）、1 每天（1111111）、2 每星期（自己勾） */
const runMode = ref(1);
const onRunModeChange = (v: number) => {
  if (v === 0) weekdaySel.value = [];
  if (v === 1) weekdaySel.value = [0, 1, 2, 3, 4, 5, 6];
};

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

/** led播放 开关。关掉时提交 led: null，后端会把已有的 LED 子任务删掉 */
const ledOn = ref(false);

/* led设备列表（旧版表单里的 ledlists）：勾中的屏写进 ledoftask */
const ledDevices = ref<LedDevice[]>([]);
const selectedLedDeviceIds = ref<number[]>([]);
const loadLedDevices = async () => {
  const { data } = await getLedDevicesApi("");
  ledDevices.value = data ?? [];
};

/** MediaTree 是懒加载的，回填时要把已选媒体的名字一并给它，否则只显示 id */
const selectedMediaNames = computed(() => selectedMediaIds.value.map(id => ({ mediaId: id, name: mediaLabel(id) })));

const applyMask = (mask: string) => {
  weekdaySel.value = mask.split("").reduce<number[]>((acc, c, i) => (c === "1" ? [...acc, i] : acc), []);
};

const openCreate = async () => {
  if (!currentFolder.value) return ElMessage.warning("请先在左侧选择一个任务分组");
  Object.assign(dlg, { visible: true, isEdit: false, title: "添加任务", id: 0, saving: false, form: emptyForm() });
  dateRange.value = [dlg.form.schedule.startdate, dlg.form.schedule.enddate];
  applyMask(dlg.form.schedule.exemodel);
  runMode.value = 1;
  playMode.value = 0;
  splitLengths(dlg.form.playback);
  ledOn.value = false;
  selectedLedDeviceIds.value = [];
  selectedMediaIds.value = [];
  selectedTerminalIds.value = [];
  terminalAreas.value = {};
  // 任务级别的可选区间由用户组级别决定，新建时先问一次服务端，
  // 免得默认值落在区间外、点提交才被拒
  const { data: pr } = await getTaskPriorityRangeApi();
  priorityRange.min = pr.priorityMin ?? 10;
  priorityRange.max = pr.priorityMax ?? 109;
  dlg.form.playback.priority = priorityRange.min;
  await Promise.all([searchMedia(""), searchTerminals(""), loadLedDevices()]);
};

const openEdit = async (row: TaskRow) => {
  const { data } = await getTaskApi(row.taskid);
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
        speed: data.led?.speed ?? 0,
        ledmode: data.led?.ledmode ?? 0
      }
    }
  });
  dateRange.value = [data.startdate, data.enddate];
  applyMask(data.exemodel);
  // 执行模式：全 0 = 手动、全 1 = 每天、其余 = 每星期
  runMode.value = data.exemodel === "0000000" ? 0 : data.exemodel === "1111111" ? 1 : 2;
  // 有间隔长度就是「间隔时间」模式
  playMode.value = data.interval_s > 0 ? 1 : 0;
  splitLengths(data);
  // 有 LED 子任务就把开关打开
  ledOn.value = !!data.led;
  selectedLedDeviceIds.value = (data.led?.devices ?? []).map(d => d.deviceId);
  priorityRange.min = data.priorityMin ?? 10;
  priorityRange.max = data.priorityMax ?? 109;

  // 先把已有清单的名字灌进缓存，再拉一次候选，避免已选项显示成裸 id
  data.media.forEach(m => (mediaNames[m.mediaId] = m.name));
  data.terminals.forEach(t => (terminalGroupOf[t.terminalId] = t.groupId));
  // 把库里已有的分区掩码回填给树，改的时候才看得出原来选了哪几个分区
  terminalAreas.value = Object.fromEntries(data.terminals.filter(t => t.area).map(t => [t.terminalId, t.area]));
  selectedMediaIds.value = data.media.map(m => m.mediaId);
  selectedTerminalIds.value = data.terminals.map(t => t.terminalId);
  await Promise.all([searchMedia(""), searchTerminals(""), loadLedDevices()]);
};

const submit = async () => {
  const f = dlg.form;
  if (!f.taskname.trim()) return ElMessage.warning("请输入任务名称");
  if (!f.folderId) return ElMessage.warning("请选择所属分组");
  if (ledOn.value && !f.led.text.trim()) return ElMessage.warning("请输入 led 字幕内容");
  if (!selectedMediaIds.value.length) return ElMessage.warning("请在媒体文件列表里选择要播放的媒体");
  if (!selectedTerminalIds.value.length) return ElMessage.warning("请在终端列表里选择终端");

  const pb = f.playback;
  const payload = {
    ...f,
    playback: {
      ...pb,
      // 时长 / 循环次数按选中的类型合成成 timelength 这一列
      timelength: pb.timelengthtype === 1 ? durationSec.value : cycleTimes.value,
      // 普通模式下不写间隔那几项
      interval_s: playMode.value === 1 ? pb.interval_s : 0,
      intplaylength: playMode.value === 1 ? (pb.intplaylengthtype === 1 ? intDurationSec.value : intCycleTimes.value) : 0
    },
    // 关掉 led播放 就传 null —— 服务端据此删掉已有的 LED 子任务
    led: ledOn.value
      ? {
          ...f.led,
          name: f.led.name.trim(),
          text: f.led.text.trim(),
          // 勾中的 LED 屏连同它挂着的终端一起提交，服务端写进 ledoftask
          devices: selectedLedDeviceIds.value.map(id => ({
            deviceId: id,
            terminalId: ledDevices.value.find(d => d.id === id)?.terminalId ?? 0
          }))
        }
      : null,
    // sort 按数组下标给，服务端还会再规整一次
    media: selectedMediaIds.value.map((id, i) => ({ mediaId: id, sort: i })),
    terminals: selectedTerminalIds.value.map(id => ({
      terminalId: id,
      groupId: terminalGroupOf[id] ?? 0,
      // 分区/通道掩码：树上逐台勾的结果，没勾过的照后端默认（全通道）
      area: terminalAreas.value[id] ?? "11111111"
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
/* 「时长 / 循环次数」两行竖排，与旧版那两行单选一致 */
.len-group {
  display: flex;
  flex-direction: column;
  /* el-radio-group 自带 align-items: center，竖排时会把两行居中，
     两行的起点因此对不齐，这里改回左对齐 */
  align-items: flex-start;
  gap: 8px;
  width: 100%;
}
.len-line {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  /* 「时长」两个字和「循环次数」四个字宽度不同，给单选一个固定宽度，
     两行的输入框才在同一条竖线上 */
  :deep(.el-radio) {
    width: 88px;
    margin-right: 0;
  }
}
.led-dev {
  width: 100%;
  padding: 6px 10px;
  background: var(--el-fill-color-lighter);
  border-radius: 4px;
}
.vol-slider {
  width: 420px;
}
.ml16 {
  margin-left: 16px;
}
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
