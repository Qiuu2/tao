<!--
  时间设置

  版式**严格照 docs/image/6.png**，整页只有三块：

    时间表单（年份/月份/日期 + 小时/分钟/秒 六个下拉）→ 设置服务器时间
    本地当前时间 → 同步当前时间
    北斗校时（采集器终端下拉）→ 北斗校时 / 不校时

  ⚠ 按图去掉的两块（需要时从 git 历史里取回）：
    · NTP 服务器（serverbaseparam.ntpserver）—— 去掉后这一列没有界面能改
    · 终端校时（下发 terminal state=30 给选中终端）—— 整个功能没有入口了
    两处后端接口都还在（PUT /api/time/ntp、POST /api/time/sync），只是前端不再暴露。

  ⚠ adjusttime 不是开关，是终端 ID。
  旧版 setgpsterminal.php 写的是下拉框里选中的那台终端的 id，0 表示不启用。
  把它当 0/1 会把一台 id=7 的终端写成 1，指向另一台完全无关的设备。

  ⚠ 旧版 setgpsterminal.php?gpsselects=-1 会 `sudo reboot` 整台机器。
  一个「取消 GPS 校时」的链接顺手重启整栋楼的广播主机，没有任何确认。
  新版不提供这条路径：「不校时」就是把 adjusttime 写回 0。

  ⚠ 页面上不放任何说明文字。按钮不可用时也不解释 ——
  原因仍然由服务端在点击后的报错里给出（见 timeset/clock.go）。
-->
<template>
  <div class="ts-page" v-loading="loading">
    <div class="card ts-card">
      <div class="ts-title">{{ $t("time.timeForm") }}</div>

      <el-form label-width="70px" class="clock-form">
        <el-row :gutter="16">
          <el-col :span="8">
            <el-form-item :label='$t("time.year")' required>
              <el-select v-model="cf.year" class="fill">
                <el-option v-for="y in years" :key="y" :label='$t("time.yearN", { n: y })' :value="y" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item :label='$t("time.month")' required>
              <el-select v-model="cf.month" class="fill">
                <el-option v-for="m in 12" :key="m" :label='$t("time.monthN", { n: m })' :value="m" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item :label='$t("time.day")' required>
              <el-select v-model="cf.day" class="fill">
                <el-option v-for="d in daysInMonth" :key="d" :label='$t("time.dayN", { n: d })' :value="d" />
              </el-select>
            </el-form-item>
          </el-col>

          <el-col :span="8">
            <el-form-item :label='$t("time.hour")' required>
              <el-select v-model="cf.hour" class="fill">
                <el-option v-for="h in 24" :key="h" :label='$t("time.hourN", { n: h - 1 })' :value="h - 1" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item :label='$t("time.minute")' required>
              <el-select v-model="cf.minute" class="fill">
                <el-option v-for="m in 60" :key="m" :label='$t("time.minuteN", { n: m - 1 })' :value="m - 1" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item :label='$t("time.second")' required>
              <el-select v-model="cf.second" class="fill">
                <el-option v-for="s in 60" :key="s" :label='$t("time.secondN", { n: s - 1 })' :value="s - 1" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <div class="clock-actions">
          <el-button type="primary" :loading="clockBusy" :disabled="!canSetClock" @click="setClock('manual')">
            {{ $t("time.setServerTime") }}
          </el-button>
        </div>

        <!-- 按钮为什么是灰的，以前只有后端知道，页面上一个字都没有 -->
        <el-alert v-if="st && !st.canSetClock && st.clockBlockReason" type="warning" :closable="false" class="mt8">
          {{ st.clockBlockReason }}
        </el-alert>
        <el-alert v-else-if="st?.ntpWarning" type="info" :closable="false" class="mt8">
          {{ st.ntpWarning }}
        </el-alert>
      </el-form>

      <el-divider />

      <!-- 下面两行的标签在 6.png 里是右对齐到同一条竖线的 -->
      <div class="line">
        <span class="lbl">{{ $t("time.localNow") }}</span>
        <span class="local-time">{{ browserTime }}</span>
        <el-button :loading="clockBusy" :disabled="!canSetClock" @click="setClock('browser')"> {{ $t("time.syncNow") }} </el-button>
      </div>

      <div class="line">
        <span class="lbl">{{ $t("time.beidouSyncLabel") }}</span>
        <!--
          下拉里是按终端分区分组的树，和全站其它选终端的地方一致。

          ⚠ 列表已经由后端筛过：只有网络音频采集器（typeid=31）和采样终端（typeid=8）
            带授时模块，别的型号选了也收不到星历。筛选的权威在服务端
            （timeset.GPSTerminalTypes），这里不重复判断。
        -->
        <div class="gps-select">
          <TerminalTreeSelect
            v-model="gps"
            :terminals="terminals"
            :placeholder='$t("time.pickCollector")'
            :disabled="!canConfig || !!st?.readOnly"
          />
        </div>
        <el-button :loading="saving.gps" :disabled="!canConfig || !!st?.readOnly" @click="saveGps"> {{ $t("time.beidouSync") }} </el-button>
        <el-button :loading="saving.gps" :disabled="!canConfig || !!st?.readOnly" @click="clearGps"> {{ $t("time.noSync") }} </el-button>
      </div>

      <!--
        一个筛过的下拉最坏的样子是「空的，而且不说为什么」——
        这个现场要是一台带授时模块的终端都没有，运维只会看到一个点不出东西的框。
      -->
      <div v-if="!terminals.length" class="line gps-empty">
        <span class="lbl"></span>
        <el-text type="info" size="small">{{ $t("time.noGpsTerminal") }}</el-text>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts" name="timeSetting">
import { useI18n } from "vue-i18n";
import { ElMessage, ElMessageBox } from "element-plus";
import { computed, onMounted, onUnmounted, ref, reactive } from "vue";

import TerminalTreeSelect from "@/components/TerminalTree/Select.vue";

import { getTimeStateApi, getTimeTerminalsApi, setGpsTerminalApi, setServerClockApi } from "@/api/modules/basecfg";
import type { TimeState, TimeTerminal } from "@/api/modules/basecfg";
import { useAuthStore } from "@/stores/modules/auth";
import { notifyServerClockChanged } from "@/utils/serverClockBus";

// 脚本里拼的文案用 t()；模板里的 $t 不用引入
const { t } = useI18n();

const authStore = useAuthStore();
const btn = computed(() => (authStore.authButtonListGet as any)?.time ?? {});
const canConfig = computed(() => !!btn.value.config);

const loading = ref(false);
const st = ref<TimeState | null>(null);
/**
 * 选中的采集器终端 id。
 *
 * ⚠ 用 undefined 而不是 0 表示「没选」。
 *   adjusttime = 0 是「不校时」，但下拉里**没有** 0 这个选项
 *   （6.png 上的占位符就是「请选择采集器终端」）。
 *   直接把 0 塞进 v-model，el-select 找不到匹配项，就会把裸的「0」显示出来。
 */
const gps = ref<number | undefined>(undefined);
const saving = reactive({ gps: false });

/* ---------------- 服务器时钟基准 ----------------
   页面上不再显示服务器时钟，但时间表单要默认填成**服务器**当前时间
   （而不是浏览器时间），所以基准还得维护。
   每 60 秒重对一次，免得本地时钟漂移越积越多。 */

const baseServer = ref(0); // 服务器时间的毫秒时间戳
const baseLocal = ref(0); // 取到它时的本地毫秒时间戳
let ticker: number | undefined;
let resync: number | undefined;

const load = async () => {
  loading.value = true;
  try {
    const { data } = await getTimeStateApi();
    st.value = data;
    gps.value = data.gpsTerminalId > 0 ? data.gpsTerminalId : undefined;
    // serverTime 是 "YYYY-MM-DD HH:mm:ss"。Safari 不认带空格的格式，换成 T 再解析。
    const parsed = Date.parse(data.serverTime.replace(" ", "T"));
    if (!Number.isNaN(parsed)) {
      baseServer.value = parsed;
      baseLocal.value = Date.now();
    }
  } finally {
    loading.value = false;
  }
};

/* ---------------- 采集器终端 ---------------- */

const terminals = ref<TimeTerminal[]>([]);
const terminalLoading = ref(false);

const searchTerminals = async (kw: string) => {
  terminalLoading.value = true;
  try {
    const { data } = await getTimeTerminalsApi(kw ?? "");
    terminals.value = data ?? [];
  } finally {
    terminalLoading.value = false;
  }
};

const saveGps = async () => {
  if (!gps.value) return ElMessage.warning(t("time.pickCollectorOrNone"));
  saving.gps = true;
  try {
    await setGpsTerminalApi(gps.value);
    ElMessage.success(t("time.beidouSet"));
    await load();
  } finally {
    saving.gps = false;
  }
};

/** 「不校时」：把 adjusttime 清成 0。⚠ 不走旧版那条 gpsselects=-1 —— 那条会 sudo reboot 整机 */
const clearGps = async () => {
  saving.gps = true;
  try {
    await setGpsTerminalApi(0);
    gps.value = undefined;
    ElMessage.success(t("time.beidouDisabled"));
    await load();
  } finally {
    saving.gps = false;
  }
};

/* ---------------- 时间表单 ---------------- */

const YEAR_SPAN = 6;
const cf = reactive({ year: 2026, month: 1, day: 1, hour: 0, minute: 0, second: 0 });
const clockBusy = ref(false);
const browserTime = ref("");

const years = computed(() => {
  const y = new Date().getFullYear();
  return Array.from({ length: YEAR_SPAN * 2 + 1 }, (_, i) => y - YEAR_SPAN + i);
});
const daysInMonth = computed(() => new Date(cf.year, cf.month, 0).getDate());

/*
  「设置服务器时间」「同步当前时间」这两个按钮能不能点。

  权威在服务端的 canSetClock 一个字段上，前端不自己叠条件 ——
  它已经把三种情况折进去了：

    · 没有 timedatectl / 缺免密 sudo        → 装 install-sudoers.sh
    · **已经选了北斗校时终端**（adjusttime > 0）→ 手工拨的值会被它拨回来，
                                              先点「不校时」才谈得上手工设置
    · 备机模式（readOnly 另算，见下）

  为什么不在前端写 `&& !st.gpsTerminalId`：那样界面和接口就成了两套判断，
  哪天改一处忘一处，界面上写着「因为 X 不能点」而接口报的是另一回事 ——
  这是最让人不信任一个系统的那种不一致。服务端 SetClock 里拦的也是同一个条件。

  按钮为什么是灰的，下面那条 alert 会照着 clockBlockReason 原样说明。
*/
const canSetClock = computed(() => !!st.value?.canSetClock && !st.value?.readOnly);

const setClock = async (from: "manual" | "browser") => {
  // 变量名不能叫 t —— i18n 的 t 在这一页也要用。
  const when = from === "browser" ? new Date() : new Date(cf.year, cf.month - 1, cf.day, cf.hour, cf.minute, cf.second);
  const p = (n: number) => String(n).padStart(2, "0");
  const text =
    `${when.getFullYear()}-${p(when.getMonth() + 1)}-${p(when.getDate())} ` +
    `${p(when.getHours())}:${p(when.getMinutes())}:${p(when.getSeconds())}`;

  /*
    ⚠ 这一句留着：拨动系统时间会让按时刻表打铃的任务瞬间集体触发或整批哑掉。
      它是**破坏性操作的确认**，不是页面说明。

    后面那句讲自动校时：界面上没有「同时关闭自动校时」这个勾了
    （接口固定传 true），但**关掉一个系统服务不能不告诉人**，
    所以挪到这个本来就要点「确定」的框里说明白。
    不关是不行的 —— systemd 在自动校时开着时直接拒绝拨表，
    那正是这个按钮之前「点了没反应」的原因。
  */
  await ElMessageBox.confirm(
    t("time.confirmSetTime", { text }) + "\n\n" + t("time.bellWarn") + "\n\n" + t("time.ntpWillStop"),
    t("time.setServerTime"),
    { type: "warning", confirmButtonText: t("time.confirmSetTitle") }
  );

  clockBusy.value = true;
  try {
    const { data } = await setServerClockApi(text, true);

    /*
      ⚠ 不能只弹一句「设置成功」就完事。

      拨表这件事有一种很坏的失败方式：命令返回 0，两秒后时间又被别的东西
      拨回去了 —— 页面上绿条弹过，运维去看服务器却一点没变，
      然后来问「为什么没用」。后端为此会读回来核对一次，对不上就填 drifted。
    */
    // 顶栏那个服务器时钟平时 3 分钟才对一次时 —— 刚拨完表得让它立刻重对，
    // 否则运维改完抬头一看还是旧时间，以为没设置成功。
    notifyServerClockChanged();

    if (data.drifted) {
      await ElMessageBox.alert(data.drifted, t("time.timeNotKept"), { confirmButtonText: t("time.gotIt") });
    } else {
      ElMessage.success(t("time.serverTimeSetAt", { time: data.serverTime }));
    }
    // 停掉了哪几个服务、下一次还会不会被拨回来 —— 都是他要知道的
    if (data.note) ElMessage.info(data.note);
    await load();
    /*
      ⚠ 这里必须重填时间表单。

        load() 只更新了「服务器当前时间」那个走秒的基准，上面年/月/日/时/分/秒
        那六个下拉还停在按下按钮之前的值 —— 「同步当前时间」尤其明显：
        时间明明已经拨成浏览器的了，表单里却还是旧的，看着像没生效。

        每分钟那次 resync 刻意**不**重填（用户可能正在下拉里选值，不能冲掉他），
        但刚点完按钮这一次是他自己要的，重填才对。
    */
    fillClockForm();
  } finally {
    clockBusy.value = false;
  }
};

/** 时间表单默认填成服务器当前时间 */
const fillClockForm = () => {
  const t = baseServer.value ? new Date(baseServer.value + (Date.now() - baseLocal.value)) : new Date();
  Object.assign(cf, {
    year: t.getFullYear(),
    month: t.getMonth() + 1,
    day: t.getDate(),
    hour: t.getHours(),
    minute: t.getMinutes(),
    second: t.getSeconds()
  });
};

const tickBrowser = () => {
  const t = new Date();
  const p = (n: number) => String(n).padStart(2, "0");
  browserTime.value =
    `${t.getFullYear()}-${p(t.getMonth() + 1)}-${p(t.getDate())} ` +
    `${p(t.getHours())}:${p(t.getMinutes())}:${p(t.getSeconds())}`;
};

onMounted(async () => {
  await load();
  fillClockForm();
  tickBrowser();
  await searchTerminals("");
  ticker = window.setInterval(tickBrowser, 1000);
  // 每分钟重新对一次基准。
  // ⚠ 只对基准，不重填时间表单 —— 用户正在那几个下拉里选值，别把他选好的冲掉。
  resync = window.setInterval(load, 60000);
});

onUnmounted(() => {
  if (ticker) window.clearInterval(ticker);
  if (resync) window.clearInterval(resync);
});
</script>

<style scoped lang="scss">
.ts-page {
  height: 100%;
  padding: 0;
  overflow: auto;
}
.ts-card {
  margin-bottom: 14px;
}
// 6.png 的标题是居中的粗体，比卡片标题大一号
.ts-title {
  margin-bottom: 22px;
  font-size: 17px;
  font-weight: 600;
  color: var(--el-text-color-primary);
  text-align: center;
}
.clock-form {
  max-width: 760px;
  margin: 0 auto;
  :deep(.el-form-item__label) {
    justify-content: flex-end;
  }
}
.gps-empty {
  margin-top: -8px;
}
.clock-actions {
  display: flex;
  gap: 14px;
  align-items: center;
  justify-content: center;
}
.mt8 {
  max-width: 760px;
  margin: 10px auto 0;
}
// 「本地当前时间」「北斗校时」两行：标签右对齐到同一条竖线（照 6.png）
.line {
  display: flex;
  gap: 10px;
  align-items: center;
  max-width: 760px;
  margin: 0 auto 14px;
  padding-left: 60px;
  font-size: 14px;
}
.lbl {
  box-sizing: border-box;
  flex: 0 0 120px;
  padding-right: 8px;
  color: var(--el-text-color-primary);
  text-align: right;
}
.local-time {
  margin-right: 6px;
  font-family: Consolas, Menlo, monospace;
}
.gps-select {
  width: 200px;
}
.fill {
  width: 100%;
}
</style>
