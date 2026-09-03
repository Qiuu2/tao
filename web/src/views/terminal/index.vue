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

    <!--
      查看快捷键（ok112 的 view_terminal_shotcut_mapping.php）。

      那个页面是一套完整的增删改查，四个动作逐条对应到这里：

        setshotcut()            设置快捷键 → 底部「设置快捷键」按钮
        modifyshotcut(id)       修改快捷键 → 每行「修改」
        del_terminal_shotcut(id) 删除映射   → 每行「删除」
        view_terminal(id)       查看终端   → 「映射终端」列直接把目标列出来

      ⚠ 只有最后一项做了合并：ok112 是跳到 displayterminal.php 去看目标终端，
        这里目标本来就在列表里，再跳一次页只是多一步。动作本身没有少。
    -->
    <el-dialog v-model="sk.visible" :title="`快捷键 · ${sk.name}`" width="820px" top="8vh">
      <el-alert type="info" :closable="false" show-icon class="mb12">
        在这台终端上按下某个键，去寻呼下面列出的目标终端。
      </el-alert>
      <el-table :data="sk.rows" size="small" border max-height="46vh">
        <el-table-column type="index" label="序号" width="60" align="center" />
        <el-table-column prop="name" label="快捷键名称" min-width="130" show-overflow-tooltip />
        <el-table-column prop="keyLabel" label="快捷键" width="110" />
        <el-table-column label="映射终端" min-width="230">
          <template #default="s">
            <span v-if="!s.row.targets.length" class="muted">未指定</span>
            <el-tag
              v-for="t in s.row.targets"
              :key="t.terminalId"
              size="small"
              class="tag-gap"
              :type="t.deleted ? 'danger' : 'info'"
              effect="plain"
            >
              {{ t.deleted ? `已删除 #${t.terminalId}` : t.terminalname }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="120" align="center">
          <template #default="s">
            <el-button type="primary" link :disabled="!canControl" @click="openShortcutEdit(s.row)">修改</el-button>
            <el-button type="danger" link :disabled="!canControl" @click="removeShortcut(s.row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <p v-if="!sk.rows.length" class="dlg-note">这台终端还没有配置快捷键。</p>
      <template #footer>
        <div class="dlg-foot">
          <el-button type="primary" :disabled="!canControl" @click="openShortcutEdit(null)">设置快捷键</el-button>
          <el-button @click="sk.visible = false">关闭</el-button>
        </div>
      </template>
    </el-dialog>

    <!--
      设置 / 修改快捷键（ok112 的 setterminalkeyoption.php 与
      modifyterminalkeyoption.php）。两个页面字段完全相同，只差回填，
      所以合成一个对话框，由 skEdit.id 区分是新建还是修改。

      ⚠ ok112 的表单还有一组「分区」复选框，按每台目标终端的通道数渲染，
        写进 terminalkeymap.area。新 Web 目前在任何地方都没有暴露 area 编辑
        （任务页也是写死默认值），这里保持一致，用服务端默认值。
        要做 area 编辑就该三处一起做，只在这一个对话框里冒出来反而更乱。
    -->
    <el-dialog v-model="skEdit.visible" :title="skEdit.id ? '修改快捷键' : '设置快捷键'" width="620px" top="8vh" append-to-body>
      <el-form :model="skEdit" label-width="100px">
        <el-form-item label="快捷键名称" required>
          <el-input v-model="skEdit.name" maxlength="45" show-word-limit placeholder="例如：呼叫A栋一层" />
        </el-form-item>
        <el-form-item label="快捷键" required>
          <el-select v-model="skEdit.key" placeholder="选择键值" class="fill">
            <el-option v-for="k in skEdit.keyOptions" :key="k.value" :label="k.label" :value="k.value" />
          </el-select>
        </el-form-item>
        <el-form-item label="映射终端">
          <TerminalTree v-model="skEdit.targetIds" :terminals="skEdit.candidates" :loading="skEdit.loading" height="260px" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="skEdit.visible = false">取消</el-button>
        <el-button
          type="primary"
          :disabled="!skEdit.name || skEdit.key === undefined"
          :loading="skEdit.saving"
          @click="submitShortcutEdit"
        >
          确定
        </el-button>
      </template>
    </el-dialog>

    <!--
      快捷任务（ok112 的 view_quickplay.php）。

      那个页面的四个动作逐条对应到这里：

        setshotcut(1)            添加快捷任务 → 底部「添加快捷任务」
        setshotcut(2)            修改快捷任务 → 底部「修改快捷任务」（限选一条）
        del_terminal_shotcut()   删除快捷任务 → 底部「删除快捷任务」（可多选）
        view_terminal(id)        查看终端     → 目标终端在编辑对话框里可见

      ⚠ 它是复选框 + 底部按钮的操作方式，不是行内按钮 —— 照 ok112。
        列也照 view_quickplay_from.html：
        任务名称 / 时长 / 优先级 / 音量 / 快捷键 / 终端名称 / 任务ID。
    -->
    <el-dialog v-model="qt.visible" :title="`快捷任务 · ${qt.name}`" width="900px" top="7vh">
      <el-alert type="info" :closable="false" show-icon class="mb12">
        在这台终端上按下某个键，执行一条<b>专属于这台终端</b>的任务。任务在这里新建，不是从已有任务里挑。
      </el-alert>
      <el-table :data="qt.rows" size="small" border max-height="42vh" @selection-change="onQtSelect">
        <el-table-column type="selection" width="44" />
        <el-table-column prop="taskName" label="任务名称" min-width="140" show-overflow-tooltip />
        <el-table-column label="任务时长" width="110">
          <template #default="s">{{ quickLengthText(s.row) }}</template>
        </el-table-column>
        <el-table-column prop="priority" label="优先级" width="80" align="center" />
        <el-table-column prop="volume" label="音量" width="70" align="center" />
        <el-table-column prop="keyLabel" label="快捷键" width="100" />
        <el-table-column label="类型" width="140">
          <template #default="s">
            <el-tag size="small" effect="plain" :type="s.row.taskType === 20 ? 'info' : 'warning'">
              {{ s.row.typeText }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="terminalName" label="终端名称" min-width="120" show-overflow-tooltip />
        <el-table-column prop="taskId" label="任务ID" width="90" align="center" />
      </el-table>
      <p v-if="!qt.rows.length" class="dlg-note">这台终端还没有快捷任务。</p>
      <template #footer>
        <div class="dlg-foot">
          <el-button type="primary" :disabled="!canControl" @click="openQuickEdit(null)">添加快捷任务</el-button>
          <el-button
            :disabled="!canControl || qt.selected.length !== 1"
            :title="qt.selected.length !== 1 ? '修改时只能选中一条' : ''"
            @click="openQuickEditSelected"
          >
            修改快捷任务
          </el-button>
          <el-button type="danger" :disabled="!canControl || !qt.selected.length" @click="removeQuickTasks">
            删除快捷任务{{ qt.selected.length ? `（${qt.selected.length}）` : "" }}
          </el-button>
          <el-button @click="qt.visible = false">关闭</el-button>
        </div>
      </template>
    </el-dialog>

    <!--
      添加 / 修改快捷任务（ok112 的 setquickplay.php 与 modifyquickplay.php）。
      两个页面字段相同、只差回填，合成一个对话框，由 qtEdit.taskId 区分。

      勾「文字播报」后表单切到 TTS 那一套：内容 / 语速 / 男女声 / 音源。
      音源选「服务器」时任务类型走 29，语速由服务端 ×10 —— 这里填的、
      回填读到的都是原始语速，换算不在前端做。
    -->
    <el-dialog
      v-model="qtEdit.visible"
      :title="qtEdit.taskId ? '修改快捷任务' : '添加快捷任务'"
      width="980px"
      top="6vh"
      append-to-body
    >
      <el-form :model="qtEdit" label-width="92px">
        <!--
          两列排布：左列是「这条任务是什么」（快捷键、优先级、时长），
          右列是「怎么放」（随机、发送模式、音量）。旧版是个 table 布局，
          同样把成对的属性摆在一行里。
        -->
        <el-form-item label="任务名称" required>
          <el-input v-model="qtEdit.taskName" maxlength="8" show-word-limit placeholder="最多 8 个字" />
        </el-form-item>

        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="快捷键" required>
              <el-select v-model="qtEdit.key" placeholder="选择键值" class="fill">
                <el-option v-for="k in qtEdit.keyOptions" :key="k.value" :label="k.label" :value="k.value" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="随机播放">
              <el-checkbox v-model="qtEdit.isRandom" />
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="优先级">
              <!-- 旧版是下拉，从当前用户组的 level 起到 109 -->
              <el-select v-model="qtEdit.priority" class="fill">
                <el-option v-for="p in priorityOptions" :key="p" :label="p" :value="p" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="发送模式">
              <el-select v-model="qtEdit.dataSendMode" class="fill">
                <el-option label="单播" :value="0" />
                <el-option label="多播" :value="1" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <el-form-item label="播放时长" required>
          <el-radio-group v-model="qtEdit.timeLengthType">
            <el-radio :value="1">时长</el-radio>
            <el-radio :value="2">循环次数</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-if="qtEdit.timeLengthType === 1" label=" ">
          <!-- 旧版这里是三个 select，不是数字输入框。范围同旧版：时 0~23、分秒 0~59 -->
          <div class="qt-hms">
            <el-select v-model="qtEdit.hour" class="qt-hms-sel">
              <el-option v-for="h in 24" :key="h - 1" :label="h - 1" :value="h - 1" />
            </el-select>
            <span>时</span>
            <el-select v-model="qtEdit.minute" class="qt-hms-sel">
              <el-option v-for="m in 60" :key="m - 1" :label="m - 1" :value="m - 1" />
            </el-select>
            <span>分</span>
            <el-select v-model="qtEdit.second" class="qt-hms-sel">
              <el-option v-for="sc in 60" :key="sc - 1" :label="sc - 1" :value="sc - 1" />
            </el-select>
            <span>秒</span>
          </div>
        </el-form-item>
        <el-form-item v-else label=" ">
          <el-input-number v-model="qtEdit.circleTime" :min="1" :max="999" />
          <span class="dlg-note inline">次</span>
        </el-form-item>

        <el-form-item label="音量">
          <el-slider v-model="qtEdit.volume" :min="0" :max="100" show-input />
        </el-form-item>
        <el-form-item label="播放方式">
          <!-- 旧版把 TTS 与 LED 两个开关并排放在这里 -->
          <el-checkbox v-model="qtEdit.ttsOn">文字播报</el-checkbox>
          <el-checkbox v-model="qtEdit.ledOn" class="ml12">LED 播放</el-checkbox>
        </el-form-item>

        <!-- LED 字幕在媒体之前，与旧版一致 -->
        <template v-if="qtEdit.ledOn">
          <el-divider content-position="left">LED 字幕</el-divider>
          <el-form-item label="上屏文字" required>
            <el-input v-model="qtEdit.ledText" maxlength="120" show-word-limit placeholder="要在 LED 屏上滚动的文字" />
          </el-form-item>
        </template>

        <!-- 文字播报时没有媒体可选，这一段整体让位给播报设置 -->
        <template v-if="qtEdit.ttsOn">
          <el-divider content-position="left">播报内容</el-divider>
          <el-form-item label="播报文字" required>
            <el-input v-model="qtEdit.ttsText" type="textarea" :rows="3" maxlength="500" show-word-limit />
          </el-form-item>
          <el-row :gutter="16">
            <el-col :span="12">
              <el-form-item label="音源" required>
                <el-select v-model="qtEdit.ttsSource" class="fill">
                  <el-option v-for="a in qtEdit.audioSources" :key="a.id" :label="a.name" :value="a.isServer ? 0 : a.id" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="语速">
                <el-select v-model="qtEdit.ttsSpeed" class="qt-narrow">
                  <el-option v-for="sp in 10" :key="sp" :label="sp" :value="sp" />
                </el-select>
                <el-radio-group v-model="qtEdit.ttsMale" class="ml12">
                  <el-radio :value="0">女声</el-radio>
                  <el-radio :value="1">男声</el-radio>
                </el-radio-group>
              </el-form-item>
            </el-col>
          </el-row>
          <el-divider content-position="left">目标终端</el-divider>
          <el-form-item label="播放到" required>
            <TerminalTree v-model="qtEdit.terminalIds" :terminals="qtEdit.candidates" :loading="qtEdit.loading" height="240px" />
          </el-form-item>
        </template>

        <!--
          媒体文件与目标终端左右并排 —— 旧版 set_task_quickplay.html 的
          753/754 两个 <td> 就是这么摆的（Media_File_List | Terminal_list）。
        -->
        <template v-else>
          <el-divider content-position="left">播放内容与目标</el-divider>
          <!--
            这两列不套 el-form-item —— 表单的 label 是竖排在左侧的，
            两棵树各自已经有标题，再套一层会出现两个重复的竖排标签。
          -->
          <el-row :gutter="16" class="qt-cols">
            <el-col :span="12">
              <div class="qt-col-title">媒体文件</div>
              <MediaTree v-model="qtEdit.mediaIds" :selected-names="qtEdit.selectedMedia" height="260px" />
            </el-col>
            <el-col :span="12">
              <div class="qt-col-title">目标终端</div>
              <TerminalTree
                v-model="qtEdit.terminalIds"
                :terminals="qtEdit.candidates"
                :loading="qtEdit.loading"
                height="260px"
              />
            </el-col>
          </el-row>
        </template>
      </el-form>
      <template #footer>
        <el-button @click="qtEdit.visible = false">取消</el-button>
        <el-button type="primary" :loading="qtEdit.saving" @click="submitQuickEdit">确定</el-button>
      </template>
    </el-dialog>

    <!--
      授权寻呼 / 授权终端（ok112 的 view_terminal_call_group.php?flag=1
      与 dirstreammanager.php?flag=2）。

      ⚠ 一台终端可以有**多个**寻呼分区 —— 旧版这一页是一张带搜索、排序、
        「添加分区 / 修改分区 / 删除分区 / 浏览终端」的列表，不是一份名单。
        所以这里也是「列表 + 三个子页」：

          主页  分区列表（本对话框）
          子页  添加分区 / 修改分区（cgEdit，名称 + 终端树）
          子页  浏览终端（cgView，成员表，列对齐 view_terminal_call.php）
    -->
    <el-dialog v-model="cg.visible" :title="`${cg.title} · ${cg.name}`" width="760px" top="8vh">
      <el-alert :type="cg.list.length ? 'warning' : 'info'" :closable="false" show-icon class="mb12">
        <template v-if="cg.list.length">
          这台终端只能寻呼下列分区里的终端。<b>把分区全部删掉</b>即回到「可寻呼所有在线终端」。
        </template>
        <template v-else> 当前一个寻呼分区都没有，这台终端可以寻呼<b>所有在线终端</b>。添加分区后即变为白名单。 </template>
      </el-alert>

      <div class="st-bar">
        <el-input
          v-model="cg.keyword"
          placeholder="搜索分区名称"
          clearable
          size="small"
          :prefix-icon="Search"
          @input="loadCallGroups"
        />
        <el-button size="small" link @click="cg.orderBy = cg.orderBy === 'name' ? '' : 'name'">
          {{ cg.orderBy === "name" ? "按名称排序 ✓" : "按名称排序" }}
        </el-button>
      </div>

      <el-table
        ref="cgTableRef"
        v-loading="cg.loading"
        :data="cg.list"
        size="small"
        max-height="320"
        @selection-change="rows => (cg.checked = rows.map((r: any) => r.id))"
      >
        <el-table-column type="selection" width="44" />
        <el-table-column type="index" label="序号" width="64" align="center" />
        <el-table-column prop="name" label="分区名称" min-width="220" show-overflow-tooltip />
        <el-table-column label="终端数" width="90" align="center">
          <template #default="{ row }">{{ row.memberCount }}</template>
        </el-table-column>
        <el-table-column label="操作" width="180" align="center">
          <template #default="{ row }">
            <el-button link type="primary" @click="openCallGroupView(row)">浏览终端</el-button>
            <el-button link type="primary" @click="openCallGroupEdit(row)">修改</el-button>
          </template>
        </el-table-column>
        <template #empty><span class="dlg-note">还没有寻呼分区</span></template>
      </el-table>

      <template #footer>
        <el-button @click="cg.visible = false">关闭</el-button>
        <!-- ok112 的 call_group_form.html：flag == 2 时才多出「目录修改」这一项 -->
        <el-button v-if="cg.byFolder" @click="openFolderManager">目录修改</el-button>
        <el-button :disabled="!cg.checked.length" :loading="cg.deleting" @click="deleteCallGroups">
          删除分区{{ cg.checked.length ? `（${cg.checked.length}）` : "" }}
        </el-button>
        <el-button type="primary" @click="openCallGroupEdit(null)">添加分区</el-button>
      </template>
    </el-dialog>

    <!--
      子页：目录管理（ok112 的 dirstreammanager.php，两个 frame）。
      左边目录树（terminalfolder，每台宿主终端各一套），右边是选中目录里的
      终端（terminaloffolder）。底部动作照 dirarea_terminal.html：
      创建目录 / 修改目录 / 删除目录 / 添加终端 / 移出选中终端。
    -->
    <el-dialog v-model="fm.visible" :title="`目录管理 · ${cg.name}`" width="1000px" top="6vh" append-to-body>
      <div class="fm-body">
        <div class="fm-side">
          <div class="fm-side-title">目录</div>
          <el-tree
            v-loading="fm.loading"
            class="fm-tree"
            :data="fm.tree"
            :props="{ label: 'name', children: 'children' }"
            node-key="id"
            highlight-current
            :current-node-key="fm.folderId"
            :default-expand-all="true"
            :expand-on-click-node="false"
            @node-click="onFolderPick"
          >
            <template #default="{ data }">
              <span class="fm-node">
                <el-icon><Folder /></el-icon>
                <span class="fm-name">{{ data.name }}</span>
                <span v-if="data.count" class="st-count">{{ data.count }}</span>
              </span>
            </template>
          </el-tree>
          <p v-if="!fm.loading && !fm.tree.length" class="dlg-note fm-empty">还没有目录，点「创建目录」新建一个</p>
        </div>

        <div class="fm-main">
          <div class="st-bar">
            <el-input
              v-model="fm.keyword"
              placeholder="搜索终端名称"
              clearable
              size="small"
              :prefix-icon="Search"
              :disabled="!fm.folderId"
              @input="loadFolderTerminals"
            />
            <span class="dlg-note">{{ fm.folderName ? `当前目录：${fm.folderName}` : "请先在左边选一个目录" }}</span>
          </div>
          <el-table
            v-loading="fm.listLoading"
            :data="fm.terminals"
            size="small"
            height="360"
            @selection-change="rows => (fm.checked = rows.map((r: any) => r.id))"
          >
            <el-table-column type="selection" width="44" />
            <el-table-column prop="terminalname" label="终端名称" min-width="130" show-overflow-tooltip />
            <el-table-column prop="typeName" label="终端类型" min-width="100" show-overflow-tooltip />
            <el-table-column label="任务状态" width="100" align="center">
              <template #default="{ row }">{{ taskStateText(row.netstate, row.taskstate) }}</template>
            </el-table-column>
            <el-table-column label="网络状态" width="94" align="center">
              <template #default="{ row }">
                <el-tag :type="row.netstate === 1 ? 'success' : 'info'" size="small" effect="plain">
                  {{ netStateText(row.netstate) }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="ip" label="IP地址" width="126" />
            <el-table-column prop="volume" label="音量" width="70" align="center" />
            <template #empty>
              <span class="dlg-note">{{ fm.folderId ? "这个目录里还没有终端" : "请先在左边选一个目录" }}</span>
            </template>
          </el-table>
        </div>
      </div>

      <template #footer>
        <el-button @click="fm.visible = false">关闭</el-button>
        <el-button :disabled="!fm.folderId" @click="openFolderEdit('rename')">修改目录</el-button>
        <el-button :disabled="!fm.folderId || fm.isRoot" @click="deleteFolder">删除目录</el-button>
        <el-button :disabled="!fm.checked.length" :loading="fm.saving" @click="removeFolderTerminals">
          移出终端{{ fm.checked.length ? `（${fm.checked.length}）` : "" }}
        </el-button>
        <el-button :disabled="!fm.folderId" @click="openFolderPicker">添加终端</el-button>
        <el-button type="primary" @click="openFolderEdit('create')">创建目录</el-button>
      </template>
    </el-dialog>

    <!-- 子页：创建 / 修改目录（ok112 的 dirareaadd.php / dirareamodify.php） -->
    <el-dialog
      v-model="fe.visible"
      :title="fe.mode === 'create' ? '创建目录' : '修改目录'"
      width="440px"
      top="16vh"
      append-to-body
    >
      <el-form label-width="90px">
        <el-form-item v-if="fe.mode === 'create'" label="上级目录">
          <span class="dlg-note">{{ fm.folderName || "根目录" }}</span>
        </el-form-item>
        <el-form-item label="目录名称" required>
          <el-input v-model="fe.name" maxlength="32" show-word-limit placeholder="仅数字 / 字母 / 汉字" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="fe.visible = false">取消</el-button>
        <el-button type="primary" :loading="fe.saving" @click="submitFolder">确定</el-button>
      </template>
    </el-dialog>

    <!-- 子页：往目录里加终端（ok112 的 dir_area_add.php） -->
    <el-dialog v-model="fp.visible" :title="`添加终端到「${fm.folderName}」`" width="560px" top="10vh" append-to-body>
      <TerminalTree v-model="fp.ids" :terminals="fp.candidates" :loading="fp.loading" height="360px" />
      <template #footer>
        <el-button @click="fp.visible = false">取消</el-button>
        <el-button type="primary" :disabled="!fp.ids.length" :loading="fp.saving" @click="submitFolderTerminals">
          添加{{ fp.ids.length ? `（${fp.ids.length}）` : "" }}
        </el-button>
      </template>
    </el-dialog>

    <!--
      子页：添加 / 修改寻呼分区（ok112 的 call_group_add.php / call_group_edit.php）。
      两个页面的表单一模一样 —— 分区名称 + 一棵终端树，只是修改时带回填，
      所以合成一个对话框，靠 cgEdit.id 区分。
    -->
    <el-dialog
      v-model="cgEdit.visible"
      :title="cgEdit.id ? '修改寻呼分区' : '添加寻呼分区'"
      width="620px"
      top="10vh"
      append-to-body
    >
      <el-form label-width="110px">
        <el-form-item label="分区名称" required>
          <el-input v-model="cgEdit.name" maxlength="32" show-word-limit placeholder="仅数字 / 字母 / 汉字" />
        </el-form-item>
        <el-form-item label="选择分区终端" required>
          <!--
            ok112 这里是 dhtmlxtree：分区 → 终端，外加「无分区终端」。
            候选范围由后端按 get_terminal_type(3) 过好（isdecode=1 且排除
            一串型号），并且不含宿主终端自己。
          -->
          <TerminalTree
            v-model="cgEdit.terminalIds"
            :terminals="cgEdit.candidates"
            :loading="cgEdit.loading"
            :groups="cg.byFolder ? cgEdit.folders : undefined"
            :ungrouped-label="cg.byFolder ? '未归目录' : '无分区终端'"
            height="300px"
            style="width: 100%"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="cgEdit.visible = false">取消</el-button>
        <el-button type="primary" :loading="cgEdit.saving" @click="submitCallGroup">确定</el-button>
      </template>
    </el-dialog>

    <!--
      子页：浏览终端（ok112 的 view_terminal_call.php）。
      列与旧版逐列对齐：序号 / 终端名称 / 终端类型 / 网络状态 / 设备状态 /
      任务状态 / IP地址 / 音量。
    -->
    <el-dialog v-model="cgView.visible" :title="`浏览终端 · ${cgView.name}`" width="900px" top="10vh" append-to-body>
      <el-table v-loading="cgView.loading" :data="cgView.members" size="small" max-height="420">
        <el-table-column type="index" label="序号" width="64" align="center" />
        <el-table-column prop="name" label="终端名称" min-width="140" show-overflow-tooltip>
          <template #default="{ row }">
            <span v-if="row.missing" class="bad">#{{ row.id }}（终端已删除）</span>
            <span v-else>{{ row.name }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="typeName" label="终端类型" min-width="110" show-overflow-tooltip />
        <el-table-column label="网络状态" width="94" align="center">
          <template #default="{ row }">
            <el-tag :type="row.netstate === 1 ? 'success' : 'info'" size="small" effect="plain">
              {{ netStateText(row.netstate) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="设备状态" width="94" align="center">
          <template #default="{ row }">{{ deviceStateText(row.netstate, row.devicestate) }}</template>
        </el-table-column>
        <el-table-column label="任务状态" width="110" align="center">
          <template #default="{ row }">{{ taskStateText(row.netstate, row.taskstate) }}</template>
        </el-table-column>
        <el-table-column prop="ip" label="IP地址" width="130" />
        <el-table-column prop="volume" label="音量" width="72" align="center" />
        <template #empty><span class="dlg-note">这个分区里还没有终端</span></template>
      </el-table>
      <template #footer>
        <el-button @click="cgView.visible = false">关闭</el-button>
      </template>
    </el-dialog>

    <!--
      终端替换（ok112 的 getterminalid.php）。
      现场换了新硬件，让它接管旧记录的 ID，旧 ID 上的任务 / 分区 / 快捷键绑定就继续生效。
    -->
    <el-dialog v-model="rp.visible" title="终端替换" width="560px">
      <el-alert type="warning" :closable="false" show-icon class="mb12">
        把 <b>{{ rp.name }} · 当前 ID {{ rp.sourceId }}</b> 的编号改成下面填写的目标 ID。
        <br />
        目标 ID 若已被占用，要求两台<b>型号相同</b>且目标<b>处于离线</b>；原记录会被删除，
        它的任务、分区、快捷键绑定由这台终端接管。
      </el-alert>
      <el-input-number v-model="rp.targetId" :min="1" :controls="false" placeholder="目标终端 ID" style="width: 100%" />
      <template #footer>
        <el-button @click="rp.visible = false">取消</el-button>
        <el-button type="primary" :disabled="!rp.targetId" :loading="rp.saving" @click="submitReplace">替换</el-button>
      </template>
    </el-dialog>

    <!--
      增补终端（ok112 的 set_synch_task.php）。
      把选中的这些终端，补加到选中的那些任务的下发列表里。
      ⚠ 手册说它是「把一个任务的配置同步到其他任务」，与代码完全不符，以代码为准。
    -->
    <el-dialog v-model="st.visible" title="增补终端到任务" width="680px" top="8vh">
      <el-alert type="info" :closable="false" show-icon class="mb12">
        把选中的 <b>{{ st.ids.length }}</b> 台终端补加到下列任务的下发列表里。已在列表中的不会重复添加。
      </el-alert>
      <!--
        任务按类别分支，与 ok112 的 set_synch_task.php 一一对应：作息方案
        （再按方案名分一层）、文件广播、采播管理、文字语音、终端功放，外加
        新 Web 的 LED 播放。扁平列成一条长下拉分不清哪条属于哪一类、
        哪个方案，所以做成树。取值范围与差异见 loadSyncTree 上方的注释。
      -->
      <div class="st-bar">
        <el-input
          v-model="st.keyword"
          placeholder="搜索任务名称"
          clearable
          size="small"
          :prefix-icon="Search"
          @input="onSyncSearch"
        />
        <el-button size="small" link @click="clearSyncTasks">清空</el-button>
      </div>
      <el-tree
        ref="stTreeRef"
        v-loading="st.loading"
        class="st-tree"
        :data="st.tree"
        :props="{ label: 'label', children: 'children' }"
        node-key="key"
        show-checkbox
        check-on-click-node
        :expand-on-click-node="false"
        :filter-node-method="filterSyncNode"
        @check="onSyncCheck"
      >
        <template #default="{ data }">
          <span class="st-node">
            <span class="st-label">{{ data.label }}</span>
            <span v-if="data.time" class="st-time">{{ data.time }}</span>
            <span v-if="data.count !== undefined" class="st-count">{{ data.count }}</span>
          </span>
        </template>
      </el-tree>
      <el-empty v-if="!st.loading && !st.tree.length" description="没有可增补的任务" :image-size="72" />
      <p class="dlg-note st-sum">已选 {{ st.taskIds.length }} 个任务</p>
      <template #footer>
        <el-button @click="st.visible = false">取消</el-button>
        <el-button type="primary" :disabled="!st.taskIds.length" :loading="st.saving" @click="submitSyncTerminals">
          增补{{ st.taskIds.length ? `（${st.taskIds.length}）` : "" }}
        </el-button>
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
              <el-tag v-if="row.impact?.tasks" type="danger" size="small" class="mr4"> 关联任务 {{ row.impact?.tasks }} </el-tag>
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
                }}<template v-if="row.impact?.tasks > row.impact?.taskNames.length"> 等 {{ row.impact?.tasks }} 条 </template>
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
        <el-button type="danger" :loading="del.saving" :disabled="!del.preview?.deletable.length" @click="submitDelete">
          确认删除 {{ del.preview?.deletable.length || 0 }} 台
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="terminalManage">
import { ArrowDown, EditPen, Folder, Link, Menu, Search } from "@element-plus/icons-vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { computed, onMounted, reactive, ref, watch } from "vue";

import { getBellPlanApi, getBellPlanListApi } from "@/api/modules/bell";
import { getTypedListApi, type TypedKind } from "@/api/modules/ninemod";
import { getTaskListApi, syncTaskTerminalsApi } from "@/api/modules/task";
import {
  checkTerminalCircuitApi,
  createQuickTaskApi,
  createShortcutKeyApi,
  deleteQuickTasksApi,
  addFolderTerminalsApi,
  deleteCallGroupsApi,
  deleteShortcutKeysApi,
  deleteTerminalFolderApi,
  deleteTerminalsApi,
  getCallGroupApi,
  getCallGroupCandidatesApi,
  getCallGroupsApi,
  getFolderCandidatesApi,
  getFolderTerminalsApi,
  getQuickAudioSourcesApi,
  getQuickTaskDetailApi,
  getQuickTasksApi,
  getShortcutKeyOptionsApi,
  getShortcutKeysApi,
  getTerminalApi,
  getTerminalFoldersApi,
  getTerminalGroupTreeApi,
  getTerminalListApi,
  getTerminalTypesApi,
  previewDeleteTerminalsApi,
  replaceTerminalApi,
  removeFolderTerminalsApi,
  saveCallGroupApi,
  saveTerminalFolderApi,
  setTerminalPasswordApi,
  setTerminalRunningApi,
  setTerminalToggleApi,
  setTerminalVolumeApi,
  syncTerminalTimeApi,
  updateQuickTaskApi,
  updateShortcutKeyApi,
  updateTerminalApi
} from "@/api/modules/terminal";
import type {
  CallGroup,
  CallGroupCandidate,
  CallGroupMember,
  DeletePreview,
  FolderTerminal,
  OpResult,
  QuickAudioSource,
  QuickTask,
  QuickTaskDetail,
  QuickTaskForm,
  ShortcutKey,
  ShortcutKeyOption,
  TerminalCaps,
  TerminalFolder,
  TerminalGroupNode,
  TerminalRow,
  TerminalType,
  ToggleKey
} from "@/api/modules/terminal";
import MediaTree from "@/components/MediaTree/index.vue";
import ProTable from "@/components/ProTable/index.vue";
import TerminalTree from "@/components/TerminalTree/index.vue";
import { useAuthStore } from "@/stores/modules/auth";
import { useUserStore } from "@/stores/modules/user";
import type { ColumnProps, ProTableInstance } from "@/components/ProTable/interface";

const authStore = useAuthStore();
// 优先级下拉的下限取自登录用户所在用户组的 level（见 priorityOptions）
const userStore = useUserStore();
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
  { cmd: "view-shortcut", label: "查看快捷键", single: true, ready: true, cap: "shortcut" },
  { cmd: "del-shortcut", label: "删除快捷键", single: true, ready: true, cap: "shortcut" },
  { cmd: "quick-task", label: "快捷任务", single: true, ready: true, cap: "quickTask" },
  { cmd: "password", label: "设置终端密码", single: true, ready: true, cap: "password" },
  { cmd: "instancy:1", label: "设置急救", ready: true, cap: "instancy" },
  { cmd: "instancy:0", label: "取消急救", ready: true, cap: "instancy" },
  { cmd: "record:1", label: "启用录音", ready: true },
  { cmd: "record:0", label: "停用录音", ready: true },
  { cmd: "volume", label: "调整音量", ready: true },
  { cmd: "auth-paging", label: "授权寻呼", single: true, ready: true, cap: "authPaging" },
  { cmd: "replace", label: "终端替换", single: true, ready: true },
  // ⚠ 增补终端在 ok112 里是**多选**（set_synchtask 用的是 getCheckboxItem，
  //   拿的是整串勾选 id），不是单选。菜单原先标了 single，是照 :80 的字段抄的，
  //   与 ok112 实际行为不符 —— 以 ok112 为准。
  { cmd: "add-terminal", label: "增补终端", ready: true },
  // ⚠ 发言与对讲判据不同：对讲要 isdecode 与 isencode **都**为 1，
  //   发言只要不是两个都为 0。所以这里是 sponsor 不是 speech。
  { cmd: "sponsor:1", label: "启用发言", ready: true, cap: "sponsor" },
  { cmd: "sponsor:0", label: "停用发言", ready: true, cap: "sponsor" },
  // ⚠ 「自动寻检」和原先单列的「线路检测」是**同一个功能**：
  //   ok112 的 check_state() 走 do.php?act=check_circuit_state，
  //   最终发的是 send_socket_circuit("terminal", 27, ids)；
  //   我们的 PUT /api/terminals/circuit-check 发的也是 notify.TermCircuit = 27。
  //   caps 里 AutoCheck 与 Circuit 的判据同样都是 anyCodec。
  //   原来把它们当成两项，于是菜单里一项能用、一项永远置灰。
  //   这里合并成 ok112 的叫法「自动寻检」，多出来的那条已删除。
  { cmd: "autocheck", label: "自动寻检", ready: true, cap: "autoCheck" },
  { cmd: "synctime", label: "同步时间", ready: true },
  { cmd: "auth-terminal", label: "授权终端", single: true, ready: true, cap: "authPaging" }
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
  // 「自动寻检」就是 ok112 的 check_state()，下发的是线路检测报文（state 27）
  if (cmd === "autocheck") {
    const { data } = await checkTerminalCircuitApi(ids);
    return reportOp(data, "自动寻检指令下发");
  }
  if (cmd === "view-shortcut") return openShortcut(ids[0]);
  if (cmd === "del-shortcut") return clearShortcuts(ids[0]);
  if (cmd === "quick-task") return openQuickTask(ids[0]);
  if (cmd === "auth-paging") return openCallGroup(ids[0], "授权寻呼");
  if (cmd === "auth-terminal") return openCallGroup(ids[0], "授权终端", true);
  if (cmd === "replace") return openReplace(ids[0]);
  if (cmd === "add-terminal") return openSyncTerminals(ids);
  const { data } = await syncTerminalTimeApi(ids);
  reportOp(data, "时间同步指令下发");
};

/* ─────────────── 快捷键：查看 + 删除 ───────────────
 *
 * ok112 是两个入口（view_terminal_shotcut_mapping.php 与
 * do.php?act=cancel_terminal_shotcut）跳两个页面。这里合成一个对话框：
 * 「删除快捷键」也先把清单列出来再勾选，而不是像旧版那样一按就把整台终端的
 * 快捷键全清掉 —— 旧版那个行为没有任何确认粒度，误操作无法挽回。
 */
const sk = reactive({
  visible: false,
  saving: false,
  id: 0,
  name: "",
  rows: [] as ShortcutKey[]
});

const openShortcut = async (id: number) => {
  const row = selectedRows([id])[0];
  sk.id = id;
  sk.name = row?.terminalname ?? `#${id}`;
  const { data } = await getShortcutKeysApi(id);
  sk.rows = data;
  sk.visible = true;
};

const refreshShortcuts = async () => {
  const { data } = await getShortcutKeysApi(sk.id);
  sk.rows = data;
};

/** 列表内每行的「删除」—— 对应 ok112 的 del_terminal_shotcut(id)，只删这一条 */
const removeShortcut = async (row: ShortcutKey) => {
  await ElMessageBox.confirm(`确定删除快捷键「${row.name}」（键 ${row.keyLabel}）？`, "删除快捷键", { type: "warning" });
  const { data } = await deleteShortcutKeysApi([row.id]);
  ElMessage.success(`已删除 ${data.deleted} 个快捷键`);
  await refreshShortcuts();
  proTableRef.value?.getTableList();
};

/*
  菜单里的「删除快捷键」—— 对应 ok112 的 cancel_terminal_shotcut，
  按 terminal_id 清空这台终端的**全部**快捷键，不是删某一条。

  ⚠ 别把它和列表里每行的「删除」搞混：那个是 del_terminal_shotcut，只删一条。
    两者在 ok112 里就是两个不同的入口，这里保持一致。
*/
const clearShortcuts = async (id: number) => {
  const row = selectedRows([id])[0];
  const name = row?.terminalname ?? `#${id}`;
  const { data: list } = await getShortcutKeysApi(id);
  if (!list.length) return ElMessage.info(`终端「${name}」没有配置快捷键`);
  await ElMessageBox.confirm(`将清空终端「${name}」的全部 ${list.length} 个快捷键，且不可恢复。确定继续？`, "删除快捷键", {
    type: "warning"
  });
  const { data } = await deleteShortcutKeysApi(list.map(k => k.id));
  ElMessage.success(`已删除 ${data.deleted} 个快捷键`);
  proTableRef.value?.getTableList();
};

/* 设置 / 修改快捷键（ok112 的 setterminalkeyoption / modifyterminalkeyoption） */
const skEdit = reactive({
  visible: false,
  saving: false,
  loading: false,
  /** 0 = 新建；非 0 = 修改这条快捷键 */
  id: 0,
  name: "",
  key: undefined as number | undefined,
  emergency: false,
  targetIds: [] as number[],
  keyOptions: [] as ShortcutKeyOption[],
  candidates: [] as TerminalRow[]
});

const openShortcutEdit = async (row: ShortcutKey | null) => {
  skEdit.id = row?.id ?? 0;
  skEdit.name = row?.name ?? "";
  skEdit.key = row?.key;
  skEdit.emergency = row?.emergency ?? false;
  skEdit.targetIds = row ? row.targets.filter(t => !t.deleted).map(t => t.terminalId) : [];
  skEdit.visible = true;
  skEdit.loading = true;
  try {
    const [keys, all] = await Promise.all([
      // 可选键值按型号算，急救与普通快捷键的可选集不同，改的时候要沿用原来那一套
      getShortcutKeyOptionsApi(sk.id, skEdit.emergency),
      getTerminalListApi({ pageNum: 1, pageSize: 500 })
    ]);
    skEdit.keyOptions = keys.data;
    // 自己不能是自己的寻呼目标
    skEdit.candidates = (all.data.list as TerminalRow[]).filter(t => t.id !== sk.id);
  } finally {
    skEdit.loading = false;
  }
};

const submitShortcutEdit = async () => {
  if (!skEdit.name || skEdit.key === undefined) return;
  skEdit.saving = true;
  try {
    const payload = {
      name: skEdit.name,
      key: skEdit.key,
      emergency: skEdit.emergency,
      targetIds: skEdit.targetIds
    };
    if (skEdit.id) {
      await updateShortcutKeyApi(skEdit.id, payload);
      ElMessage.success("已修改");
    } else {
      await createShortcutKeyApi(sk.id, payload);
      ElMessage.success("已添加");
    }
    skEdit.visible = false;
    await refreshShortcuts();
    proTableRef.value?.getTableList();
  } finally {
    skEdit.saving = false;
  }
};

/* ─────────────── 快捷任务 ───────────────
 *
 * ⚠ 它是「为这台终端**新建**一条专属任务并绑到键上」，不是「把已有任务绑到键上」。
 *   ok112 的列表只认 tasktype IN (20,21,29) 且 cmdargs 指向本终端的任务。
 *   操作方式也照 ok112：复选框选中 + 底部按钮（添加 / 修改 / 删除），
 *   修改限恰好选一条，删除可多选。
 */
const qt = reactive({
  visible: false,
  id: 0,
  name: "",
  rows: [] as QuickTask[],
  selected: [] as number[]
});

/** 列表里「任务时长」那一列：类型 1 是秒数拆成时分秒，类型 2 是循环次数 */
const quickLengthText = (row: QuickTask) => {
  if (row.timeLengthType === 2) return `${row.timeLength} 次`;
  const h = Math.floor(row.timeLength / 3600);
  const m = Math.floor((row.timeLength % 3600) / 60);
  const sec = row.timeLength % 60;
  return [h ? `${h}时` : "", m ? `${m}分` : "", sec ? `${sec}秒` : ""].join("") || "0秒";
};

const openQuickTask = async (id: number) => {
  const row = selectedRows([id])[0];
  qt.id = id;
  qt.name = row?.terminalname ?? `#${id}`;
  qt.selected = [];
  const { data } = await getQuickTasksApi(id);
  qt.rows = data;
  qt.visible = true;
};

const onQtSelect = (rows: QuickTask[]) => {
  qt.selected = rows.map(r => r.taskId);
};

const refreshQuickTasks = async () => {
  const { data } = await getQuickTasksApi(qt.id);
  qt.rows = data;
  qt.selected = [];
};

/** 底部「删除快捷任务」—— ok112 的 del_terminal_shotcut()，可多选 */
const removeQuickTasks = async () => {
  if (!qt.selected.length) return;
  await ElMessageBox.confirm(
    `将删除选中的 ${qt.selected.length} 条快捷任务，连同它们的媒体、目标终端与按键绑定，且不可恢复。确定继续？`,
    "删除快捷任务",
    { type: "warning" }
  );
  const { data } = await deleteQuickTasksApi(qt.id, qt.selected);
  ElMessage.success(`已删除 ${data.deleted} 条快捷任务`);
  await refreshQuickTasks();
};

/* 添加 / 修改快捷任务（ok112 的 setquickplay / modifyquickplay） */
const qtEdit = reactive({
  visible: false,
  saving: false,
  loading: false,
  /** 0 = 新建；非 0 = 修改这条任务 */
  taskId: 0,
  taskName: "",
  key: undefined as number | undefined,
  timeLengthType: 1,
  hour: 0,
  minute: 0,
  second: 30,
  circleTime: 1,
  priority: 13,
  volume: 80,
  dataSendMode: 0,
  isRandom: false,
  ttsOn: false,
  ledOn: false,
  ttsText: "",
  ttsSpeed: 5,
  ttsMale: 0,
  ttsSource: 0,
  mediaIds: [] as number[],
  /** 回填时把已选媒体的名字带给 MediaTree —— 树是懒加载的，光有 id 显示不出名字 */
  selectedMedia: [] as { mediaId: number; name: string }[],
  terminalIds: [] as number[],
  ledText: "",
  keyOptions: [] as ShortcutKeyOption[],
  audioSources: [] as QuickAudioSource[],
  candidates: [] as TerminalRow[]
});

/*
  优先级下拉。旧版是 `for(level = <当前用户组 level>; level <= 109; level++)`，
  也就是不能设得比自己所在用户组更高。level 取自登录用户，109 是旧版写死的上限。
*/
const priorityOptions = computed(() => {
  const from = Number(userStore.userInfo?.level ?? 0);
  return Array.from({ length: Math.max(0, 109 - from + 1) }, (_, i) => from + i);
});

const openQuickEdit = async (detail: QuickTaskDetail | null) => {
  qtEdit.taskId = detail?.taskId ?? 0;
  qtEdit.taskName = detail?.taskName ?? "";
  qtEdit.key = detail?.key;
  qtEdit.timeLengthType = detail?.timeLengthType ?? 1;
  if (detail && detail.timeLengthType === 2) {
    qtEdit.circleTime = detail.timeLength || 1;
    qtEdit.hour = qtEdit.minute = 0;
    qtEdit.second = 30;
  } else {
    const t = detail?.timeLength ?? 30;
    qtEdit.hour = Math.floor(t / 3600);
    qtEdit.minute = Math.floor((t % 3600) / 60);
    qtEdit.second = t % 60;
    qtEdit.circleTime = 1;
  }
  qtEdit.priority = detail?.priority ?? Math.max(13, Number(userStore.userInfo?.level ?? 0));
  qtEdit.volume = detail?.volume ?? 80;
  qtEdit.dataSendMode = detail?.dataSendMode ?? 0;
  qtEdit.isRandom = (detail?.isRandomPlay ?? 0) === 1;
  qtEdit.ttsOn = !!detail?.tts;
  qtEdit.ledOn = !!detail?.led?.text;
  qtEdit.ttsText = detail?.tts?.text ?? "";
  qtEdit.ttsSpeed = detail?.tts?.speed ?? 5;
  qtEdit.ttsMale = detail?.tts?.musicMode ?? 0;
  qtEdit.ttsSource = detail?.tts?.audioSource ?? 0;
  qtEdit.mediaIds = detail?.mediaIds ? [...detail.mediaIds] : [];
  qtEdit.selectedMedia = detail?.media ? detail.media.map(m => ({ mediaId: m.mediaId, name: m.name })) : [];
  qtEdit.terminalIds = detail?.terminalIds ? [...detail.terminalIds] : [];
  qtEdit.ledText = detail?.led?.text ?? "";
  qtEdit.visible = true;
  qtEdit.loading = true;
  try {
    const [keys, sources, all] = await Promise.all([
      getShortcutKeyOptionsApi(qt.id),
      getQuickAudioSourcesApi(),
      getTerminalListApi({ pageNum: 1, pageSize: 500 })
    ]);
    qtEdit.keyOptions = keys.data;
    qtEdit.audioSources = sources.data;
    qtEdit.candidates = all.data.list as TerminalRow[];
  } finally {
    qtEdit.loading = false;
  }
};

/** 底部「修改快捷任务」—— ok112 的 setshotcut(2)，限恰好选一条 */
const openQuickEditSelected = async () => {
  if (qt.selected.length !== 1) return ElMessage.warning("修改时只能选中一条快捷任务");
  const { data } = await getQuickTaskDetailApi(qt.id, qt.selected[0]);
  await openQuickEdit(data);
};

const submitQuickEdit = async () => {
  if (!qtEdit.taskName.trim()) return ElMessage.warning("请填写任务名称");
  if (qtEdit.key === undefined) return ElMessage.warning("请选择快捷键");
  const timeLength = qtEdit.timeLengthType === 2 ? qtEdit.circleTime : qtEdit.hour * 3600 + qtEdit.minute * 60 + qtEdit.second;
  if (timeLength <= 0) return ElMessage.warning("播放时长必须大于 0");
  if (qtEdit.ttsOn && !qtEdit.ttsText.trim()) return ElMessage.warning("请填写播报内容");
  if (qtEdit.ledOn && !qtEdit.ledText.trim()) return ElMessage.warning("请填写 LED 上屏文字");
  if (!qtEdit.ttsOn && !qtEdit.mediaIds.length) return ElMessage.warning("请选择要播放的媒体文件");
  if (!qtEdit.terminalIds.length) return ElMessage.warning("请选择要播放到哪些终端");

  const form: QuickTaskForm = {
    taskName: qtEdit.taskName.trim(),
    key: qtEdit.key,
    isRandomPlay: qtEdit.isRandom ? 1 : 0,
    volume: qtEdit.volume,
    priority: qtEdit.priority,
    timeLengthType: qtEdit.timeLengthType,
    timeLength,
    dataSendMode: qtEdit.dataSendMode,
    mediaIds: qtEdit.ttsOn ? [] : qtEdit.mediaIds,
    terminalIds: qtEdit.terminalIds,
    tts: qtEdit.ttsOn
      ? { text: qtEdit.ttsText, speed: qtEdit.ttsSpeed, musicMode: qtEdit.ttsMale, audioSource: qtEdit.ttsSource }
      : null,
    led: qtEdit.ledText.trim() ? { text: qtEdit.ledText.trim(), speed: 5, ledmode: 1, terminalIds: qtEdit.terminalIds } : null
  };

  qtEdit.saving = true;
  try {
    if (qtEdit.taskId) {
      await updateQuickTaskApi(qt.id, qtEdit.taskId, form);
      ElMessage.success("已修改");
    } else {
      await createQuickTaskApi(qt.id, form);
      ElMessage.success("已添加");
    }
    qtEdit.visible = false;
    await refreshQuickTasks();
  } finally {
    qtEdit.saving = false;
  }
};

/* ─────────────── 授权寻呼 / 授权终端 ───────────────
 *
 * ok112 的 view_terminal_call_group.php?flag=1（授权寻呼）与
 * dirstreammanager.php?flag=2（授权终端）。
 *
 * ⚠ 一台终端可以有**多个**寻呼分区。旧版这一页的主查询是
 *     SELECT * FROM callgroup WHERE terminalid = $terminal_id ORDER BY id DESC
 *   带分页、按 name 搜索与排序，底下四个动作是「添加分区 / 修改分区 /
 *   删除分区 / 浏览终端」—— 是一张列表，不是一份名单。
 *
 * ⚠ 一个分区都没有 = 可寻呼所有在线终端，**不是**「谁都不能呼」。
 *   对话框里的提示文案专门写清楚了这一点，别改成「删光即禁止」。
 *
 * 三个子页：
 *   添加分区 / 修改分区  call_group_add.php / call_group_edit.php → cgEdit
 *   浏览终端            view_terminal_call.php                   → cgView
 */

/* ok112 view_terminal_call.html 里那三列的状态文案，逐条照搬。
   ⚠ 设备状态和任务状态都先看 netstate：断网时不管库里存的是什么，
     一律显示「断开」——旧版就是这么判的，库里的状态是上次在线时留下的。*/
const netStateText = (net: number) => (net === 1 ? "已连接" : "断开");

const deviceStateText = (net: number, dev: number) => {
  if (net !== 1) return "断开";
  return dev === 1 ? "运行" : "空闲";
};

const TASK_STATE_TEXT = [
  "准备就绪", // 0
  "定时播放", // 1
  "正在对讲", // 2
  "点播", // 3
  "选播", // 4
  "寻呼", // 5
  "准备对讲", // 6
  "本地扩音", // 7
  "USB 播放", // 8
  "请求对讲", // 9
  "被请求对讲", // 10
  "播放寻呼", // 11
  "定时播放" // 12（旧版 12 与 1 同文案）
];

const taskStateText = (net: number, task: number) => {
  if (net !== 1) return "断开";
  return TASK_STATE_TEXT[task] ?? `状态 ${task}`;
};

/* ---- 主页：分区列表 ---- */
const cgTableRef = ref();
const cg = reactive({
  visible: false,
  loading: false,
  deleting: false,
  id: 0,
  name: "",
  title: "授权寻呼",
  /** true = 从「授权终端」进来（ok112 flag=2）：树按目录分组，底部多一个「目录修改」 */
  byFolder: false,
  keyword: "",
  orderBy: "",
  list: [] as CallGroup[],
  checked: [] as number[]
});

const loadCallGroups = async () => {
  cg.loading = true;
  try {
    const { data } = await getCallGroupsApi(cg.id, { keyword: cg.keyword, orderBy: cg.orderBy });
    cg.list = data.list ?? [];
    cg.checked = [];
  } finally {
    cg.loading = false;
  }
};

watch(() => cg.orderBy, loadCallGroups);

const openCallGroup = async (id: number, title: string, byFolder = false) => {
  const row = selectedRows([id])[0];
  cg.id = id;
  cg.title = title;
  cg.byFolder = byFolder;
  cg.name = row?.terminalname ?? `#${id}`;
  cg.keyword = "";
  cg.orderBy = "";
  cg.list = [];
  cg.checked = [];
  cg.visible = true;
  await loadCallGroups();
};

const deleteCallGroups = async () => {
  if (!cg.checked.length) return;
  const names = cg.list.filter(g => cg.checked.includes(g.id)).map(g => g.name);
  await ElMessageBox.confirm(
    `确定删除 ${names.length} 个寻呼分区（${names.join("、")}）？` +
      (names.length === cg.list.length ? "\n删光后这台终端将恢复为「可寻呼所有在线终端」。" : ""),
    "删除分区",
    { type: "warning" }
  );
  cg.deleting = true;
  try {
    const { data } = await deleteCallGroupsApi(cg.id, cg.checked);
    ElMessage.success(`已删除 ${data.deleted} 个寻呼分区`);
    await loadCallGroups();
  } finally {
    cg.deleting = false;
  }
};

/* ---- 子页：添加 / 修改分区 ---- */
const cgEdit = reactive({
  visible: false,
  loading: false,
  saving: false,
  /** 0 = 添加；> 0 = 修改这个分区 */
  id: 0,
  name: "",
  terminalIds: [] as number[],
  candidates: [] as CallGroupCandidate[],
  /**
   * 「授权终端」时树的骨架 —— 本机目录拍平成一层。
   *
   * ⚠ TerminalTree 的分组骨架**来自 groups 而不是从终端归纳**（见组件里的
   *   注释）。不传的话它自己去拉 /api/zones/options 拿终端分区，而候选终端
   *   身上带的 groupId 是**目录** id，两边对不上，所有终端都会掉进
   *   「无分区终端」—— 树看着还在，分组全没了。
   */
  folders: [] as { id: number; name: string; parentId: number }[]
});

/**
 * 目录树拍成 TerminalTree 要的 {id, name, parentId} 清单。
 *
 * 顺序是「父目录紧跟着它的子目录」，并且带上 parentId —— TerminalTree 见到
 * parentId 就会把分组本身也摆成层级，不再按名字排序，目录的父子关系才留得住。
 */
const flattenFolders = (list: TerminalFolder[]): { id: number; name: string; parentId: number }[] =>
  (list ?? []).flatMap(f => [{ id: f.id, name: f.name, parentId: f.parentid }, ...flattenFolders(f.children ?? [])]);

const openCallGroupEdit = async (row: CallGroup | null) => {
  cgEdit.id = row?.id ?? 0;
  cgEdit.name = row?.name ?? "";
  cgEdit.terminalIds = [];
  cgEdit.visible = true;
  cgEdit.loading = true;
  try {
    // 候选终端每次重新拉：型号、分区、在线状态都可能在这期间变过
    const [cand, detail, folders] = await Promise.all([
      getCallGroupCandidatesApi(cg.id, cg.byFolder),
      row ? getCallGroupApi(row.id) : Promise.resolve(null),
      cg.byFolder ? getTerminalFoldersApi(cg.id) : Promise.resolve(null)
    ]);
    cgEdit.candidates = cand.data ?? [];
    cgEdit.folders = folders ? flattenFolders(folders.data.tree ?? []) : [];
    // 回填时跳过指向已删除终端的脏行 —— 树上没有它们，勾不上，
    // 留着只会让「已选 N 台」和树上勾的对不上。
    cgEdit.terminalIds = (detail?.data.members ?? []).filter(m => !m.missing).map(m => m.id);
  } finally {
    cgEdit.loading = false;
  }
};

const submitCallGroup = async () => {
  const name = cgEdit.name.trim();
  if (!name) return ElMessage.warning("请填写分区名称");
  // ok112 的 checkform() 用 isChinaOrNumbOrLett 拦，这里同样的口径
  if (!/^[\u4e00-\u9fa5A-Za-z0-9]+$/.test(name)) return ElMessage.warning("分区名称仅能由数字 / 字母 / 汉字组成");
  if (!cgEdit.terminalIds.length) return ElMessage.warning("请至少选择一台终端");

  cgEdit.saving = true;
  try {
    await saveCallGroupApi(cg.id, cgEdit.id, name, cgEdit.terminalIds);
    ElMessage.success(cgEdit.id ? "已修改寻呼分区" : "已添加寻呼分区");
    cgEdit.visible = false;
    await loadCallGroups();
  } finally {
    cgEdit.saving = false;
  }
};

/* ---- 子页：目录管理（只有「授权终端」有）----
 *
 * ok112 的 dirstreammanager.php 是两个 frame：左边 dir_stream_tree.php 的
 * 目录树，右边 dir_terminal_area.php 里该目录下的终端。这里做成一个对话框
 * 的左右两栏，动作照 dirarea_terminal.html 底部那一排。
 *
 * ⚠ 旧版底部还有一个「复制主目录到主终端」，指向 do.php?act=dirareacopy_msg
 *   —— 但 do.php 里根本没有这个 case（全文件 0 处匹配），是条死链。不搬。
 */
const fm = reactive({
  visible: false,
  loading: false,
  listLoading: false,
  saving: false,
  tree: [] as TerminalFolder[],
  folderId: 0,
  folderName: "",
  /** 根目录不能删（ok112 的 `AND parentid > 0`） */
  isRoot: false,
  keyword: "",
  terminals: [] as FolderTerminal[],
  checked: [] as number[]
});

const loadFolderTree = async () => {
  fm.loading = true;
  try {
    const { data } = await getTerminalFoldersApi(cg.id);
    fm.tree = data.tree ?? [];
    // 选中的目录可能刚被删掉，重新对一遍；没选过就默认落在根目录上
    const found = fm.folderId ? findFolder(fm.tree, fm.folderId) : null;
    if (found) {
      fm.folderName = found.name;
      fm.isRoot = found.parentid === 0;
    } else {
      const root = fm.tree[0];
      fm.folderId = root?.id ?? 0;
      fm.folderName = root?.name ?? "";
      fm.isRoot = !!root && root.parentid === 0;
    }
    await loadFolderTerminals();
  } finally {
    fm.loading = false;
  }
};

const findFolder = (list: TerminalFolder[], id: number): TerminalFolder | null => {
  for (const f of list) {
    if (f.id === id) return f;
    const hit = findFolder(f.children ?? [], id);
    if (hit) return hit;
  }
  return null;
};

const loadFolderTerminals = async () => {
  if (!fm.folderId) {
    fm.terminals = [];
    return;
  }
  fm.listLoading = true;
  try {
    const { data } = await getFolderTerminalsApi(cg.id, fm.folderId, fm.keyword);
    fm.terminals = data.list ?? [];
    fm.checked = [];
  } finally {
    fm.listLoading = false;
  }
};

const onFolderPick = (node: TerminalFolder) => {
  fm.folderId = node.id;
  fm.folderName = node.name;
  fm.isRoot = node.parentid === 0;
  fm.keyword = "";
  loadFolderTerminals();
};

const openFolderManager = async () => {
  fm.folderId = 0;
  fm.folderName = "";
  fm.keyword = "";
  fm.terminals = [];
  fm.checked = [];
  fm.visible = true;
  await loadFolderTree();
};

const removeFolderTerminals = async () => {
  if (!fm.checked.length) return;
  await ElMessageBox.confirm(`确定把选中的 ${fm.checked.length} 台终端移出「${fm.folderName}」？`, "移出目录", {
    type: "warning"
  });
  fm.saving = true;
  try {
    const { data } = await removeFolderTerminalsApi(cg.id, fm.folderId, fm.checked);
    ElMessage.success(`已移出 ${data.affected} 台终端`);
    await loadFolderTree();
  } finally {
    fm.saving = false;
  }
};

const deleteFolder = async () => {
  await ElMessageBox.confirm(
    `确定删除目录「${fm.folderName}」？它下面的子目录、以及这些目录里的终端归属会一并清掉（终端本身不受影响）。`,
    "删除目录",
    { type: "warning" }
  );
  const { data } = await deleteTerminalFolderApi(cg.id, fm.folderId);
  ElMessage.success(`已删除 ${data.deleted} 个目录`);
  fm.folderId = 0;
  await loadFolderTree();
};

/* ---- 子页：创建 / 修改目录 ---- */
const fe = reactive({ visible: false, saving: false, mode: "create" as "create" | "rename", name: "" });

const openFolderEdit = (mode: "create" | "rename") => {
  fe.mode = mode;
  fe.name = mode === "rename" ? fm.folderName : "";
  fe.visible = true;
};

const submitFolder = async () => {
  const name = fe.name.trim();
  if (!name) return ElMessage.warning("请填写目录名称");
  if (!/^[\u4e00-\u9fa5A-Za-z0-9]+$/.test(name)) return ElMessage.warning("目录名称仅能由数字 / 字母 / 汉字组成");
  fe.saving = true;
  try {
    // 创建时挂在当前选中的目录下；一个目录都还没有时传 0，后端会先补出根目录
    await saveTerminalFolderApi(cg.id, fe.mode === "rename" ? fm.folderId : 0, fe.mode === "create" ? fm.folderId : 0, name);
    ElMessage.success(fe.mode === "rename" ? "已修改目录" : "已创建目录");
    fe.visible = false;
    await loadFolderTree();
  } finally {
    fe.saving = false;
  }
};

/* ---- 子页：往目录里加终端 ---- */
const fp = reactive({
  visible: false,
  loading: false,
  saving: false,
  ids: [] as number[],
  candidates: [] as CallGroupCandidate[]
});

const openFolderPicker = async () => {
  fp.ids = [];
  fp.visible = true;
  fp.loading = true;
  try {
    const { data } = await getFolderCandidatesApi(cg.id, fm.folderId);
    fp.candidates = data ?? [];
  } finally {
    fp.loading = false;
  }
};

const submitFolderTerminals = async () => {
  if (!fp.ids.length) return;
  fp.saving = true;
  try {
    const { data } = await addFolderTerminalsApi(cg.id, fm.folderId, fp.ids);
    ElMessage.success(`已添加 ${data.affected} 台终端`);
    fp.visible = false;
    await loadFolderTree();
  } finally {
    fp.saving = false;
  }
};

/* ---- 子页：浏览终端 ---- */
const cgView = reactive({
  visible: false,
  loading: false,
  name: "",
  members: [] as CallGroupMember[]
});

const openCallGroupView = async (row: CallGroup) => {
  cgView.name = row.name;
  cgView.members = [];
  cgView.visible = true;
  cgView.loading = true;
  try {
    const { data } = await getCallGroupApi(row.id);
    cgView.members = data.members ?? [];
  } finally {
    cgView.loading = false;
  }
};

/* ─────────────── 终端替换 ─────────────── */
const rp = reactive({
  visible: false,
  saving: false,
  sourceId: 0,
  name: "",
  targetId: undefined as number | undefined
});

const openReplace = (id: number) => {
  const row = selectedRows([id])[0];
  rp.sourceId = id;
  rp.name = row?.terminalname ?? `#${id}`;
  rp.targetId = undefined;
  rp.visible = true;
};

const submitReplace = async () => {
  if (!rp.targetId) return;
  await ElMessageBox.confirm(
    `确定把「${rp.name}」的 ID 从 ${rp.sourceId} 改为 ${rp.targetId}？若目标 ID 已被占用，原记录将被删除。`,
    "终端替换",
    { type: "warning" }
  );
  rp.saving = true;
  try {
    const { data } = await replaceTerminalApi(rp.sourceId, rp.targetId);
    ElMessage.success(
      data.mode === "takeover"
        ? `已顶替「${data.targetName}」，接管其绑定；清理源终端关联 ${data.affected} 条`
        : `已改号为 ${data.targetId}，迁移关联 ${data.affected} 条`
    );
    rp.visible = false;
    proTableRef.value?.getTableList();
  } finally {
    rp.saving = false;
  }
};

/* ─────────────── 增补终端到任务 ───────────────
 *
 * 任务按类别分支，逐条对齐 ok112 的 set_synch_task.php：
 *
 *   ok112 的分组                  它写死的 SQL                          这里的来源
 *   ─────────────────────────────────────────────────────────────────────────────
 *   作息方案                      tasktype = 1，按 info 再分方案         /api/bell-plans
 *   文件广播                      tasktype = 2                          /api/tasks
 *   采播管理                      tasktype = 3                          typed-tasks/collect
 *   网络电台                      tasktype = 10                         —— 见下
 *   文字语音                      tasktype IN (17,19)                   typed-tasks/tts
 *   终端功放                      tasktype = 5 且 sec_task_id=0、prepower=0   typed-tasks/amplifier
 *   文字语音（第二次）            tasktype = 17 且 sec_task_id = 0       —— 见下
 *   LED 播放                      （ok112 没有这一组）                   typed-tasks/led
 *
 * 三处与 ok112 不一致，都是有意的：
 *
 * 1. ok112 把「文字语音」列了**两遍** —— 一组是 tasktype IN(17,19)，另一组是
 *    tasktype=17 AND sec_task_id=0。17 号任务同时落进两组，树上会出现两条一模一样
 *    的记录，勾哪条都是同一个 taskid。这是旧版的重复，不照搬。这里只列一次，
 *    范围取新 Web 文字语音页的口径 tasktype IN (15,17,19) AND sec_task_id = 0
 *    —— 比 ok112 多一个 15。15 就是「播一段 TTS 合成出来的文件」，
 *    displayttsmanager.php 自己也把它当文字语音列，ok112 这里漏了。
 *
 * 2. 网络电台（tasktype = 10）没有列。不是漏掉：新 Web 根本没有网络电台这个模块，
 *    没有页面能建、能看、能删这类任务，单在这里放一支树只会指向一堆管不了的任务。
 *    等网络电台移植过来再补这一组。
 *
 * 3. LED 播放是 ok112 没有的。LED 任务（tasktype 24/30 且 sec_task_id = 0）
 *    照样有自己的 terminaloftask 下发列表，增补终端对它成立，所以补上。
 *    ⚠ 只列独立 LED 任务；sec_task_id ≠ 0 的是附在别的任务下面的 LED 子任务，
 *      终端跟着主任务走，单独增补没有意义 —— typed-tasks/led 本身就已经把它们滤掉了。
 *
 * 空的分组不显示（ok112 每组外面都套了 `if(mysqli_num_rows() > 0)`）。
 *
 * ⚠ 取值只认叶子（getCheckedNodes(true) 且必须带 taskId）—— 分类节点与
 *   方案节点都是虚拟的，勾上父节点是「全选它下面的任务」，本身不该被提交。
 */

/** 走 typed-tasks 的四支，顺序照 ok112（LED 是新增的，排最后） */
const SYNC_TYPED: { kind: TypedKind; label: string }[] = [
  { kind: "collect", label: "采播管理" },
  { kind: "tts", label: "文字语音" },
  { kind: "amplifier", label: "终端功放" },
  { kind: "led", label: "LED 播放" }
];
interface SyncNode {
  key: string;
  label: string;
  taskId?: number;
  time?: string;
  count?: number;
  children?: SyncNode[];
}

const stTreeRef = ref();
const st = reactive({
  visible: false,
  saving: false,
  loading: false,
  keyword: "",
  ids: [] as number[],
  taskIds: [] as number[],
  tree: [] as SyncNode[]
});

const loadSyncTree = async () => {
  st.loading = true;
  try {
    const [files, plans, ...typed] = await Promise.all([
      getTaskListApi({ pageNum: 1, pageSize: 500 }),
      getBellPlanListApi({ pageNum: 1, pageSize: 200 }),
      ...SYNC_TYPED.map(g => getTypedListApi(g.kind, { folderId: 0, pageNum: 1, pageSize: 500 }).catch(() => null))
    ]);

    const fileNodes: SyncNode[] = (files.data.list as { taskid: number; taskname: string }[]).map(t => ({
      key: `t:${t.taskid}`,
      label: t.taskname,
      taskId: t.taskid
    }));

    // 方案下的条目要逐个方案去取（列表接口只给方案名与条目数）
    const planList = plans.data.list as { planName: string; itemCount: number }[];
    const details = await Promise.all(planList.map(p => getBellPlanApi(p.planName).catch(() => null)));
    const planNodes: SyncNode[] = planList.map((p, i) => {
      const items = (details[i]?.data.items ?? []) as { taskid: number; taskname: string; playtime: string }[];
      return {
        key: `p:${p.planName}`,
        label: p.planName,
        count: items.length,
        children: items.map(it => ({
          key: `t:${it.taskid}`,
          label: it.taskname,
          taskId: it.taskid,
          time: it.playtime
        }))
      };
    });

    const groups: SyncNode[] = [
      { key: "g:bell", label: "作息方案", count: planNodes.reduce((n, p) => n + (p.count ?? 0), 0), children: planNodes },
      { key: "g:file", label: "文件广播", count: fileNodes.length, children: fileNodes },
      ...SYNC_TYPED.map((g, i) => {
        const list = (typed[i]?.data.list ?? []) as { taskId: number; taskName: string }[];
        return {
          key: `g:${g.kind}`,
          label: g.label,
          count: list.length,
          children: list.map(t => ({ key: `t:${t.taskId}`, label: t.taskName, taskId: t.taskId }))
        };
      })
    ];

    // 空分组不显示 —— ok112 每组外面都套了 if(mysqli_num_rows() > 0)
    st.tree = groups.filter(g => (g.children?.length ?? 0) > 0);
  } finally {
    st.loading = false;
  }
};

const onSyncCheck = () => {
  const picked = (stTreeRef.value?.getCheckedNodes(true) ?? []) as SyncNode[];
  st.taskIds = picked.filter(n => n.taskId !== undefined).map(n => n.taskId as number);
};

const clearSyncTasks = () => {
  stTreeRef.value?.setCheckedKeys([], true);
  st.taskIds = [];
};

const filterSyncNode = (value: string, data: any) => {
  if (!value) return true;
  // 分类节点与方案节点自己不参与匹配，一律先判 false。
  // el-tree 过滤完子节点后会回头把「还有子节点可见」的父节点重新点亮，
  // 所以搜到的任务照样有落脚的枝，而空掉的那几类会整支收起来 ——
  // 这里要是直接 return true，六个分类会全部留在屏幕上，其中五个是空的。
  if (data.taskId === undefined) return false;
  return String(data.label ?? "")
    .toLowerCase()
    .includes(value.toLowerCase());
};

const onSyncSearch = () => stTreeRef.value?.filter(st.keyword);

const openSyncTerminals = async (ids: number[]) => {
  st.ids = [...ids];
  st.taskIds = [];
  st.keyword = "";
  st.visible = true;
  await loadSyncTree();
};

const submitSyncTerminals = async () => {
  if (!st.taskIds.length) return;
  st.saving = true;
  try {
    const { data } = await syncTaskTerminalsApi(st.taskIds, st.ids);
    const blocked = data.blocked ?? [];
    if (blocked.length) {
      ElMessage.warning(`增补 ${data.added} 条关联；${blocked.length} 个任务被跳过：${blocked[0].detail}`);
    } else {
      ElMessage.success(`已增补 ${data.added} 条终端关联`);
    }
    st.visible = false;
  } finally {
    st.saving = false;
  }
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
//
// ⚠ 菜单里**没有**独立的「删除终端」项，这是刻意的：删除终端就是「重新注册」，
//   两者是同一个动作的两个说法（ok112 的 delterminal 也是名为重新注册、
//   实为 terminaldel_msg）。删除的入口只有 openReRegister 一个，
//   下面这个对话框由它打开。别再加第二个入口。
const del = reactive({ visible: false, saving: false, ids: [] as number[], preview: null as DeletePreview | null });

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
          offline
            .slice(0, 10)
            .map(r => "  · " + (r.terminalname || `终端 ${r.id}`))
            .join("\n") +
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
.ml12 {
  margin-left: 12px;
}
/* 对话框底部：ok112 的按钮是左对齐一排，这里保持同样的排布 */
.dlg-foot {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}
/* 说明文字跟在控件后面时不该再顶一行下边距 */
.dlg-note.inline {
  margin-bottom: 0;
  margin-left: 12px;
}
/* 时 / 分 / 秒三个下拉排一行，宽度收窄，别撑满 */
.qt-hms {
  display: flex;
  gap: 6px;
  align-items: center;
  font-size: 13px;
  color: var(--el-text-color-secondary);
}
.qt-hms-sel {
  width: 76px;
}
/* 发送模式 / 优先级 / 语速这类短下拉不该跟输入框一样宽 */
.qt-narrow {
  width: 110px;
}
/* 两棵树并排的那一行：与上面的表单项拉开一点距离 */
.qt-cols {
  margin-bottom: 18px;
}
.qt-col-title {
  margin-bottom: 6px;
  font-size: 13px;
  color: var(--el-text-color-regular);
}
.st-bar {
  display: flex;
  gap: 8px;
  align-items: center;
  padding: 6px 8px;
  border: 1px solid var(--el-border-color-light);
  border-bottom: 0;
  border-radius: 4px 4px 0 0;
}
.fm-body {
  display: flex;
  gap: 12px;
}
.fm-side {
  display: flex;
  flex: none;
  flex-direction: column;
  width: 240px;
  border: 1px solid var(--el-border-color-light);
  border-radius: 4px;
}
.fm-side-title {
  padding: 6px 10px;
  font-size: 13px;
  color: var(--el-text-color-regular);
  border-bottom: 1px solid var(--el-border-color-lighter);
}
.fm-tree {
  flex: 1;
  overflow: auto;
  min-height: 0;
}
.fm-empty {
  padding: 10px;
}
.fm-node {
  display: flex;
  gap: 5px;
  align-items: center;
  min-width: 0;
}
.fm-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.fm-main {
  flex: 1;
  min-width: 0;
}
.st-tree {
  /* 分组从两支涨到六支，320px 一屏只装得下作息方案，往下全靠滚。
     用 vh 让它跟着窗口长，同时留住底部的「已选 N 个任务」和按钮。 */
  height: 460px;
  max-height: 58vh;
  overflow: auto;
  border: 1px solid var(--el-border-color-light);
}
.st-node {
  display: flex;
  gap: 8px;
  align-items: center;
  min-width: 0;
}
.st-label {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.st-time {
  font-family: var(--el-font-family-monospace, monospace);
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.st-count {
  flex: none;
  padding: 0 5px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
  background: var(--el-fill-color-light);
  border-radius: 8px;
}
.st-sum {
  margin: 6px 0 0;
}
.qt-col-title::before {
  margin-right: 4px;
  color: var(--el-color-danger);
  content: "*";
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
