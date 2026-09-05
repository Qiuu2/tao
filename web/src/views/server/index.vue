<!--
  服务器参数

  排版参照 docs/image/1.png（服务器基本信息）与 docs/image/3.png（主备服务器配置）：
  顶部四个页签做成分段按钮，每一页的内容都是**两列、标签右对齐**，整块居中。
  四个页签共用同一个内容宽度，切换页签时版心不会跳。

  三点值得写下来：

  1. **只有地址类字段可编辑**（服务器地址 / 子网掩码 / 网关地址，带红星），
     端口与容量一律置灰只读。参考图就是这么设计的，端口不该被随手改 ——
     真要改，后端接口是支持的，把 disabled 去掉即可。

  2. **「web端口」和「sdk端口」不是数据库里的值**。它们从 Apache 的
     httpd.conf（ServerName / LISTEN）和旧版 swagger1.json 里读，现网是 80 和 99；
     数据库的 webport=8886 是后台 C 服务的 UDP 通知端口，rtspport=8091 是 RTSP，
     两者都不是这里要显示的东西。取不到文件时才回退到数据库列，并在页脚说明。
     「离线端口」倒确实是数据库列 offlineport（8901，主备同步的 rsync 端口）。
     详见 server/internal/serverparam/apache.go。

  3. **「重启服务器」是整机重启**，不是重启后台服务。
     现网实测：报文发出后 1 秒 systemd 就开始走关机流程。
     所以这个按钮要求逐字输入确认文本。
-->
<template>
  <div class="sp-page" v-loading="loading">
    <div class="sp-card">
      <el-tabs v-model="tab" class="sp-tabs">
        <!-- ============ 服务器基本信息 ============ -->
        <el-tab-pane label="服务器基本信息" name="basic">
          <template v-if="p">
            <el-divider content-position="center" class="wide">服务器信息</el-divider>

            <!-- 服务器信息：两列，标签宽度与下面的表单一致，两段才对得齐 -->
            <div class="two-col">
              <el-row :gutter="24">
                <el-col :span="12">
                  <!--
                    旧版这里是一张图：workstate=0 显示 stop.gif（服务器终止），
                    =1 显示 start.gif（服务器运行）。直接把 0/1 摆出来没人看得懂，
                    这里换成同样含义的文字标签，措辞用旧版 language 里的原文。
                  -->
                  <div class="kv">
                    <span class="kv-l">服务器状态：</span>
                    <el-tag :type="p.readonly.workstate === 1 ? 'success' : 'info'" size="small" effect="plain">
                      {{ workstateText(p.readonly.workstate) }}
                    </el-tag>
                  </div>
                </el-col>
                <el-col :span="12">
                  <div class="kv kv-act">
                    <el-button type="warning" :loading="rb.busy" @click="openReboot">重启服务器</el-button>
                  </div>
                </el-col>
                <el-col :span="12">
                  <div class="kv"><span class="kv-l">系统连接数：</span>{{ p.readonly.currectconnectcount }}</div>
                </el-col>
                <el-col :span="12">
                  <div class="kv"><span class="kv-l">运行任务数：</span>{{ p.readonly.taskcount }}</div>
                </el-col>
                <el-col :span="12">
                  <div class="kv"><span class="kv-l">当前带宽：</span>{{ p.readonly.currentbandwidth }}</div>
                </el-col>
                <el-col :span="12">
                  <div class="kv"><span class="kv-l">版本号：</span>{{ p.misc.version || "—" }}</div>
                </el-col>
              </el-row>
            </div>

            <el-divider content-position="center" class="wide">服务器配置</el-divider>

            <!--
              备机模式下这一页**仍然可以改**。
              全站其余模块是只读的（备机上的业务数据由主机同步过来，改了没意义），
              但主备模式的开关就在这一页上 —— 跟着一起锁死就再也切不回主服务器了。
            -->
            <el-alert v-if="readonlyMode" type="warning" :closable="false" class="mb12 two-col">
              当前是<b>备份服务器模式</b>（<code>model = 2</code>）：其余模块只读， <b>本页照常可改</b> ——
              要切回主服务器请到「主备服务器配置」页。
            </el-alert>

            <!-- 服务器配置：两列。左右交替沿用参考图的字段顺序（地址一列、端口一列） -->
            <el-form :model="p" label-width="110px" class="sp-form two-col">
              <el-row :gutter="24">
                <el-col :span="12">
                  <el-form-item label="服务器地址" required>
                    <el-input v-model="p.network.ip" />
                  </el-form-item>
                </el-col>
                <el-col :span="12">
                  <el-form-item label="数据端口">
                    <el-input :model-value="p.ports.port" disabled />
                  </el-form-item>
                </el-col>

                <el-col :span="12">
                  <el-form-item label="子网掩码" required>
                    <el-input v-model="p.network.subnetmask" />
                  </el-form-item>
                </el-col>
                <el-col :span="12">
                  <el-form-item label="控制端口">
                    <el-input :model-value="p.ports.udpport" disabled />
                  </el-form-item>
                </el-col>

                <el-col :span="12">
                  <el-form-item label="网关地址" required>
                    <el-input v-model="p.network.gateway" />
                  </el-form-item>
                </el-col>
                <el-col :span="12">
                  <el-form-item label="数据端口2">
                    <el-input :model-value="p.ports.dataport" disabled />
                  </el-form-item>
                </el-col>

                <el-col :span="12">
                  <el-form-item label="最大带宽">
                    <el-input :model-value="p.capacity.maxbandwidth" disabled />
                  </el-form-item>
                </el-col>
                <el-col :span="12">
                  <el-form-item label="最大连接数">
                    <el-input :model-value="p.capacity.maxhttpconnections" disabled />
                  </el-form-item>
                </el-col>

                <el-col :span="12">
                  <el-form-item label="web端口">
                    <el-input :model-value="webPort" disabled />
                  </el-form-item>
                </el-col>
                <el-col :span="12">
                  <el-form-item label="离线端口">
                    <el-input :model-value="p.ports.offlineport" disabled />
                  </el-form-item>
                </el-col>

                <el-col :span="12">
                  <el-form-item label="sdk端口">
                    <el-input :model-value="sdkPort" disabled />
                  </el-form-item>
                </el-col>
              </el-row>

              <el-form-item class="form-actions">
                <el-button type="primary" :loading="saving" @click="save"> 确 定 </el-button>
                <el-button @click="load">取 消</el-button>
              </el-form-item>
            </el-form>
          </template>
        </el-tab-pane>

        <!-- ============ 主备服务器配置 ============ -->
        <el-tab-pane label="主备服务器配置" name="ha">
          <template v-if="p">
            <el-divider content-position="center" class="wide">主备服务器配置</el-divider>

            <!--
              版式与字段名参照 docs/image/3.png：两列、标签右对齐、必填项带红星。
              那张图同时确认了一件事 ——「主服务器名称」就是 serverbaseparam.name
              （图上的值 ha515h 与现网库里一致），它在旧版就是可改的。
            -->
            <el-form :model="p" label-width="125px" class="sp-form two-col">
              <el-row :gutter="24">
                <el-col :span="12">
                  <el-form-item label="主服务器地址" required>
                    <el-input v-model="p.ha.masterip" />
                  </el-form-item>
                </el-col>
                <el-col :span="12">
                  <el-form-item label="备份服务器地址" required>
                    <el-input v-model="p.ha.slaveip" />
                  </el-form-item>
                </el-col>

                <el-col :span="12">
                  <el-form-item label="主服务器名称" required>
                    <el-input v-model="p.ha.name" maxlength="85" />
                  </el-form-item>
                </el-col>
                <el-col :span="12">
                  <el-form-item label="备份服务器名称">
                    <el-input v-model="p.ha.slavename" maxlength="85" />
                  </el-form-item>
                </el-col>

                <el-col :span="12">
                  <el-form-item label="主备模式" required>
                    <el-radio-group v-model="p.ha.model" class="stack">
                      <el-radio :label="1">主服务器</el-radio>
                      <el-radio :label="2">备份服务器</el-radio>
                    </el-radio-group>
                  </el-form-item>
                </el-col>
                <el-col :span="12">
                  <el-form-item label="子网掩码" required>
                    <el-input v-model="p.network.subnetmask" />
                  </el-form-item>
                </el-col>

                <el-col :span="12">
                  <el-form-item label="服务开关">
                    <el-switch
                      v-model="p.ha.backup"
                      :active-value="1"
                      :inactive-value="0"
                      inactive-text="关闭"
                      active-text="开启"
                    />
                  </el-form-item>
                </el-col>
              </el-row>

              <el-form-item class="form-actions">
                <el-button type="primary" :loading="saving" @click="save"> 确 定 </el-button>
                <el-button @click="load">取 消</el-button>
              </el-form-item>
            </el-form>
          </template>
        </el-tab-pane>

        <!-- ============ 服务设置 ============ -->
        <el-tab-pane label="服务设置" name="service">
          <template v-if="p">
            <!--
              字段与顺序照 :80 的「服务设置」页签（docs/image/4.png）：
              噪声设置 / 重启设置 / 重启时间 / 关机设置 / 关机时间。

              ⚠ 这四项**都不在 serverbaseparam 里**，而且落在**同一条** task 记录上
                （tasktype=13、taskname='reset'，现网 taskid 70000）：

                  重启启用          projectstate=0, playtime=重启时间, cmdargs='0'
                  重启停用+关机启用  projectstate=0, playtime=关机时间, cmdargs='shutdown'
                  两个都停用        projectstate=1

                依据是旧版 ok112/do - 副本.php 第 6006~6046 行。详见 serverparam/autorestart.go。

              ⚠ 由此，重启和关机是**互斥**的：只有一个 playtime，同时只能开一个。
                所以下面两个开关联动 —— 开其中一个会把另一个置成停用。
                :80 上关机那两项是灰的（旧系统界面没放开），但存储确实存在，这里放开了。
            -->
            <!-- 四个页签统一：每段内容上面都有一条居中的分隔标题（与前两页一致） -->
            <el-divider content-position="center" class="wide">服务设置</el-divider>
            <el-form :model="p" label-width="110px" class="sp-form srv-form srv-form--svc">
              <el-form-item label="噪声设置" required>
                <el-radio-group v-model="p.misc.sounddetect">
                  <el-radio-button :value="1">启用</el-radio-button>
                  <el-radio-button :value="0">停用</el-radio-button>
                </el-radio-group>
              </el-form-item>

              <el-form-item label="重启设置" required>
                <el-radio-group :model-value="ar.mode === 'reboot'" @change="pickMode('reboot', $event)">
                  <el-radio-button :value="true">启用</el-radio-button>
                  <el-radio-button :value="false">停用</el-radio-button>
                </el-radio-group>
              </el-form-item>

              <el-form-item label="重启时间">
                <el-time-picker
                  v-model="ar.rebootTime"
                  value-format="HH:mm:ss"
                  placeholder="选择重启时间"
                  :clearable="false"
                  :disabled="ar.mode !== 'reboot'"
                  class="fill"
                />
              </el-form-item>

              <el-form-item label="关机设置" required>
                <el-radio-group :model-value="ar.mode === 'shutdown'" @change="pickMode('shutdown', $event)">
                  <el-radio-button :value="true">启用</el-radio-button>
                  <el-radio-button :value="false">停用</el-radio-button>
                </el-radio-group>
              </el-form-item>

              <el-form-item label="关机时间">
                <el-time-picker
                  v-model="ar.shutdownTime"
                  value-format="HH:mm:ss"
                  placeholder="选择关机时间"
                  :clearable="false"
                  :disabled="ar.mode !== 'shutdown'"
                  class="fill"
                />
              </el-form-item>

              <el-form-item class="form-actions">
                <el-button type="primary" :loading="saving" @click="save">提 交</el-button>
                <el-button @click="load">重 置</el-button>
              </el-form-item>
            </el-form>
          </template>
        </el-tab-pane>

        <!-- ============ 版本设置 ============ -->
        <el-tab-pane label="版本设置" name="version">
          <template v-if="p">
            <!--
              照 docs/image/5.png：一个「* 版本」下拉（占位「请选择版本」）+ 一个「提交」。

              ⚠ 这一页**不写数据库**。下拉里那五项对应 :80 的
                POST server/serverversion（id 1..5），它做的是解开
                sounds/audioserver 下的版本包、再重建 a9000_audioserver 容器。
                serverbaseparam.version 是后台服务上报的字符串
                （现网 TW_V1.0.0.0-Jul 20 2026[…]），跟包名不是一回事，
                这里既不拿它当下拉的值，也不会去覆盖它。详见 serverparam/version.go。

              ⚠ 提交 = 换掉正在跑的音频引擎，广播会中断，所以要二次确认。
                机器上换不动时（sudo 要密码），canSwitch=false，按钮直接禁掉并说明原因。
            -->
            <!-- 四个页签统一：每段内容上面都有一条居中的分隔标题（与前两页一致） -->
            <el-divider content-position="center" class="wide">版本设置</el-divider>
            <el-form label-width="110px" class="sp-form srv-form srv-form--ver">
              <el-form-item label="版本" required>
                <el-select v-model="ver.pick" placeholder="请选择版本" class="fill" clearable>
                  <el-option v-for="o in ver.options" :key="o.id" :label="o.name" :value="o.id" :disabled="!o.available" />
                </el-select>
              </el-form-item>
              <el-form-item class="form-actions">
                <el-button type="primary" :loading="ver.busy" :disabled="!ver.pick || !ver.canSwitch" @click="openSwitchVersion">
                  提 交
                </el-button>
              </el-form-item>
            </el-form>
          </template>
        </el-tab-pane>
      </el-tabs>
    </div>

    <!-- 重启服务器确认 -->
    <el-dialog v-model="rb.visible" title="重启服务器" width="560px">
      <el-alert type="error" :closable="false" class="mb12">
        <template #title>这会重启整台服务器，不是重启后台服务</template>
        <div class="alert-body">
          现网实测：指令发出后 1 秒系统就开始走关机流程，约 30 秒后服务恢复。
          期间广播完全中断，正在播放的任务会被打断。请避开上下课等打铃时段。
        </div>
      </el-alert>
      <el-input v-model="rb.confirmText" placeholder="逐字输入：重启服务器" />
      <template #footer>
        <el-button @click="rb.visible = false">取消</el-button>
        <el-button type="danger" :loading="rb.busy" :disabled="rb.confirmText !== '重启服务器'" @click="doReboot">
          确认重启
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="serverParam">
import { computed, onMounted, reactive, ref } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import {
  getAutoRestartApi,
  getServerParamsApi,
  getServerVersionApi,
  rebootServerApi,
  saveAutoRestartApi,
  saveServerParamsApi,
  switchServerVersionApi,
  type ServerParams,
  type VersionOption
} from "@/api/modules/serverparam";

/**
 * 服务器状态。旧版 servermanager_form.html 用两张图表示：
 *   workstate = 0 → stop.gif，alt「服务器终止」
 *   workstate = 1 → start.gif，alt「服务器运行」
 * 其余取值旧版不画图（页面上那一格是空的），这里如实标出来。
 */
const workstateText = (v: number) => (v === 1 ? "服务器运行" : v === 0 ? "服务器终止" : `未知状态 ${v}`);

const tab = ref("basic");
const loading = ref(false);
const saving = ref(false);
const p = ref<ServerParams | null>(null);

const readonlyMode = computed(() => p.value?.ha.model === 2);

// web端口 / sdk端口 来自 Apache 配置而不是数据库（见文件头说明）。
// 读不到文件时退回数据库列。
const webPort = computed(() => p.value?.apache?.webPort || p.value?.ports.webport);
const sdkPort = computed(() => p.value?.apache?.sdkPort || p.value?.ports.rtspport);

/*
  定时重启 / 定时关机。

  ⚠ 两者不在 serverbaseparam 里，而是 task 表那条 tasktype=13 的系统任务，
    而且是**同一条**：只有一个 playtime，靠 cmdargs 区分是重启还是关机。
    所以它们**互斥**，用一个 mode 表示，而不是两个独立的布尔开关。
    详见 serverparam/autorestart.go。

  ⚠ 界面上没有星期选择框（docs/image/4.png 上就没有），
    保存时 exemodel 不传，由后端保留库里已有的掩码
    （现网 0001000 = 只在周四）；只有掩码全 0 且要启用时后端才补成 1111111。
*/
const ar = reactive({
  mode: "off" as "off" | "reboot" | "shutdown",
  // 两个时间在库里共用 playtime，前端各留一份，切换开关时不至于把对方的值冲掉
  rebootTime: "04:00:00",
  shutdownTime: "22:00:00",
  exemodel: "0000000"
});

/**
 * 点其中一个开关。
 *
 * 因为库里只有一条任务，开「关机」就等于关掉「重启」，反之亦然 ——
 * 这里显式做这件事，免得界面上出现两个都显示「启用」、保存后却只生效一个的情况。
 */
const pickMode = (which: "reboot" | "shutdown", on: any) => {
  ar.mode = on ? which : "off";
};

const loadAutoRestart = async () => {
  const { data } = await getAutoRestartApi();
  ar.mode = data.mode || "off";
  ar.exemodel = data.exemodel || "0000000";
  // playtime 只属于当前生效的那一边，另一边保持前端默认值
  const t = data.time || "04:00:00";
  if (data.mode === "shutdown") ar.shutdownTime = t;
  else ar.rebootTime = t;
};

const load = async () => {
  loading.value = true;
  try {
    const { data } = await getServerParamsApi();
    p.value = data;
    await loadAutoRestart();
    await loadVersion();
  } finally {
    loading.value = false;
  }
};

const save = async () => {
  if (!p.value) return;
  saving.value = true;
  try {
    const { data } = await saveServerParamsApi({
      network: p.value.network,
      ports: p.value.ports,
      capacity: p.value.capacity,
      multicast: p.value.multicast,
      ha: p.value.ha,
      misc: p.value.misc
    });
    // 定时重启/关机落在 task 表上，跟服务器参数分开写；两者都在这一下「提交」里完成。
    // exemodel 不传 —— 界面上没有星期这一项，取值规则在后端。
    await saveAutoRestartApi({
      mode: ar.mode,
      time: ar.mode === "shutdown" ? ar.shutdownTime : ar.rebootTime
    });
    ElMessage.success("已保存");
    if (data.requiresRestart) {
      await ElMessageBox.alert(
        `以下改动需要额外操作才会真正生效：\n\n${data.restartReason.join("\n\n")}`,
        "保存成功，但还需要处理",
        { confirmButtonText: "知道了" }
      );
    }
    load();
  } finally {
    saving.value = false;
  }
};

/* ---------------- 版本设置 ---------------- */
/*
  ⚠ 这一块一列数据库都不写。下拉的五项对应 :80 的 server/serverversion（id 1..5），
    提交做的是解开版本包 + 重建 audioserver 容器。详见 serverparam/version.go。
*/
const ver = reactive({
  pick: undefined as number | undefined,
  options: [] as VersionOption[],
  canSwitch: false,
  reason: "",
  busy: false
});

const loadVersion = async () => {
  const { data } = await getServerVersionApi();
  ver.options = data.options || [];
  ver.canSwitch = data.canSwitch;
  ver.reason = data.reason;
};

const openSwitchVersion = async () => {
  const opt = ver.options.find(o => o.id === ver.pick);
  if (!opt) return;
  await ElMessageBox.confirm(
    `将把后台音频引擎切换到「${opt.name}」。\n\n` +
      "这会解开对应的版本包并重建 audioserver 容器，期间广播完全中断、" +
      "正在播放的任务会被打断。请避开上下课等打铃时段。\n\n确定继续？",
    "切换服务器版本",
    { type: "warning", confirmButtonText: "确认切换", cancelButtonText: "取消" }
  );
  ver.busy = true;
  try {
    const { data } = await switchServerVersionApi(opt.id);
    ElMessage.warning(data.note);
    await loadVersion();
  } finally {
    ver.busy = false;
  }
};

/* ---------------- 重启服务器 ---------------- */

const rb = reactive({ visible: false, busy: false, confirmText: "" });

const openReboot = () => {
  rb.confirmText = "";
  rb.busy = false;
  rb.visible = true;
};

const doReboot = async () => {
  rb.busy = true;
  try {
    const { data } = await rebootServerApi(rb.confirmText);
    rb.visible = false;
    ElMessage.warning(data.note);
  } finally {
    rb.busy = false;
  }
};

/*
  ⚠ 恢复出厂的界面已按要求删除（连同「其它服务参数」那一整块折叠区）。
    后端接口仍在：
      GET  /api/server/factory-reset/preview
      POST /api/server/factory-reset
    要恢复界面，从 git 历史里取回这一段与对应的模板即可。
*/

onMounted(load);
</script>

<style scoped lang="scss">
.sp-page {
  height: 100%;
  padding: 0;
  overflow: auto;
}
.sp-card {
  min-height: 100%;
  padding: 16px 0 40px;
  background: var(--el-bg-color);
}
.sp-tabs {
  :deep(.el-tabs__header) {
    display: flex;
    justify-content: center;
  }
  :deep(.el-tabs__nav-wrap::after) {
    display: none;
  }
  // 参照图里页签是分段按钮的样子，不是默认下划线
  :deep(.el-tabs__item) {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 34px;
    line-height: 34px;
    border: 1px solid var(--el-border-color);
    border-right: none;
    &:first-child {
      border-radius: 4px 0 0 4px;
    }
    &:last-child {
      border-right: 1px solid var(--el-border-color);
      border-radius: 0 4px 4px 0;
    }
    &.is-active {
      color: #fff;
      background: var(--el-color-primary);
      border-color: var(--el-color-primary);
    }
  }
  // ⚠ 必须显式盖掉首尾两个页签的内边距。
  //   Element Plus 自己有这么两条（theme-chalk/src/tabs.scss）：
  //     .el-tabs--top .el-tabs__item.is-top:nth-child(2)   { padding-left: 0 }
  //     .el-tabs--top .el-tabs__item.is-top:last-child     { padding-right: 0 }
  //   默认的下划线页签靠它把第一项与容器左边缘对齐，但我们把页签做成了分段按钮，
  //   于是「服务器基本信息」的文字贴死在左边框上、「版本设置」的文字顶出右边框 ——
  //   看上去就是这几个字没在框里居中。
  //
  //   ⚠ 别写成 `:deep(.el-tabs--top .el-tabs__item…)`：`.el-tabs--top` 就长在
  //     .sp-tabs 这个元素**自己**身上，不是它的后代，那样写选择器一个都匹配不上
  //     （实测 padding-left 仍是 0px）。带上 .is-top:nth-child(2) / :last-child
  //     就已经比 EP 那两条更具体了，够压住。
  :deep(.el-tabs__item.is-top:nth-child(2)),
  :deep(.el-tabs__item.is-top:last-child),
  :deep(.el-tabs__item.is-top) {
    padding: 0 18px;
  }
  :deep(.el-tabs__active-bar) {
    display: none;
  }
}

// 全页统一的版心宽度。四个页签都用它，切换页签时版心不会跳。
// 780px 是照着 docs/image/3.png 量的：两列、每列一个 125px 标签 + 一个约 200px 输入框。
$content-width: 780px;

.el-divider {
  width: 420px;
  margin: 22px auto 18px;
  &.wide {
    width: $content-width;
  }
  :deep(.el-divider__text) {
    font-size: 14px;
    color: var(--el-text-color-primary);
    background: var(--el-bg-color);
  }
}
.sp-form {
  width: 470px;
  margin: 0 auto;
  :deep(.el-form-item__label) {
    justify-content: flex-end;
  }
}
// 服务设置 / 版本设置这两页在 :80 上是**单列**的（docs/image/4.png、5.png）：
// 标签右对齐、控件左对齐，整块偏左而不是居中 —— 图里内容大致从版心左侧起排。
// 这里给一个比两列窄的固定宽度，并跟着两列版心的左边缘对齐。
// 服务设置 / 版本设置这两页在 :80 上是**单列**的（docs/image/4.png、5.png）。
//
// ⚠ 块宽必须等于**可见内容**的宽度，否则块居中了、内容看着还是偏左。
//   踩过两次：
//     ① 最早是「780px 居中 + padding-right: 310px」，内容被钉在左半边，偏左 155px
//     ② 改成 470px 居中后仍偏左 70px —— 因为服务设置那几行的实际内容只有 330px 宽
//        （label 110 + 最宽的控件 220，即时间选择器），(470-330)/2 = 70
//   实测数据：卡片内容区 [223,1572] 中线 898，可见内容 [663,993] 中线 828。
.sp-page .srv-form {
  box-sizing: border-box;
  margin-right: auto;
  margin-left: auto;
}
// 服务设置：110（label）+ 220（最宽控件，时间选择器）
.sp-page .srv-form--svc {
  width: 330px;
}
// 版本设置：版本下拉是 width:100% 的，会把整块撑满，所以块宽就是可见宽度，
// 按 5.png 给一个宽一点的值。
.sp-page .srv-form--ver {
  width: 470px;
}
// 4.png / 5.png 里「提交 / 重置」是**左对齐**在控件那一列下面的，
// 不是像其它页那样居中。.form-actions 默认 justify-content: center，这里改回来。
.sp-page .srv-form :deep(.el-form-item.form-actions .el-form-item__content) {
  justify-content: flex-start;
}
// 4.png 里「重启时间」和「关机时间」两个输入框一样宽，都比整列窄一截。
// 不写死的话时间选择器会缩到 220px、下面的禁用输入框却撑满，看着两行错开。
.sp-page .srv-form :deep(.el-date-editor),
.sp-page .srv-form :deep(.el-input) {
  width: 220px;
}
// 版本下拉在 5.png 里是撑满整列的，别跟上面两个时间框一起被收窄
.sp-page .srv-form :deep(.el-select) {
  width: 100%;
  .el-input {
    width: 100%;
  }
}
// 两列版式：整页共用同一个宽度，居中。
// 多写一层 .sp-page 提高优先级，这样不管 .sp-form 等的单列宽度
// 写在样式表的哪个位置，都由这里说了算。
.sp-page .two-col {
  width: $content-width;
  margin-right: auto;
  margin-left: auto;
}
.kv {
  display: flex;
  align-items: center;
  min-height: 32px;
  font-size: 14px;
  line-height: 32px;
  color: var(--el-text-color-regular);
}
// 标签列宽度必须**正好**等于服务器配置表单的 label-width（110px），
// 两段的取值区才会落在同一条竖线上。这里显式写 border-box：
// 项目里没有全局的 * { box-sizing: border-box }，不写的话
// 12px 的 padding 会加在 110px 之外，比表单整整错开 12px。
.kv-l {
  box-sizing: border-box;
  flex: 0 0 110px;
  padding-right: 12px;
  text-align: right;
  color: var(--el-text-color-primary);
}
.kv-act {
  padding-left: 110px; // 按钮左边缘对齐右列输入框
}
// 主备模式那两个单选按 3.png 是竖排的
.stack {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  :deep(.el-radio) {
    height: 28px;
    margin-right: 0;
  }
}
.form-actions {
  margin-top: 4px;
  :deep(.el-form-item__content) {
    justify-content: center;
    margin-left: 0 !important;
  }
}
.fill {
  width: 100%;
}
.alert-body {
  margin-top: 4px;
  font-size: 13px;
  line-height: 1.6;
}
.warn {
  color: var(--el-color-warning);
}
code {
  padding: 0 4px;
  font-size: 12px;
  background: var(--el-fill-color-light);
  border-radius: 3px;
}
.mb12 {
  margin-bottom: 12px;
}
</style>
