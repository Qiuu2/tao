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
      <div class="ts-title">时间表单</div>

      <el-form label-width="70px" class="clock-form">
        <el-row :gutter="16">
          <el-col :span="8">
            <el-form-item label="年份" required>
              <el-select v-model="cf.year" class="fill">
                <el-option v-for="y in years" :key="y" :label="`${y}年`" :value="y" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="月份" required>
              <el-select v-model="cf.month" class="fill">
                <el-option v-for="m in 12" :key="m" :label="`${m}月`" :value="m" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="日期" required>
              <el-select v-model="cf.day" class="fill">
                <el-option v-for="d in daysInMonth" :key="d" :label="`${d}日`" :value="d" />
              </el-select>
            </el-form-item>
          </el-col>

          <el-col :span="8">
            <el-form-item label="小时" required>
              <el-select v-model="cf.hour" class="fill">
                <el-option v-for="h in 24" :key="h" :label="`${h - 1}时`" :value="h - 1" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="分钟" required>
              <el-select v-model="cf.minute" class="fill">
                <el-option v-for="m in 60" :key="m" :label="`${m - 1}分`" :value="m - 1" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="秒" required>
              <el-select v-model="cf.second" class="fill">
                <el-option v-for="s in 60" :key="s" :label="`${s - 1}秒`" :value="s - 1" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <div class="clock-actions">
          <el-button type="primary" :loading="clockBusy" :disabled="!canSetClock" @click="setClock('manual')">
            设置服务器时间
          </el-button>
        </div>
      </el-form>

      <el-divider />

      <!-- 下面两行的标签在 6.png 里是右对齐到同一条竖线的 -->
      <div class="line">
        <span class="lbl">本地当前时间：</span>
        <span class="local-time">{{ browserTime }}</span>
        <el-button :loading="clockBusy" :disabled="!canSetClock" @click="setClock('browser')"> 同步当前时间 </el-button>
      </div>

      <div class="line">
        <span class="lbl">北斗校时：</span>
        <!-- 下拉里是按终端分区分组的树，和全站其它选终端的地方一致 -->
        <div class="gps-select">
          <TerminalTreeSelect
            v-model="gps"
            :terminals="terminals"
            placeholder="请选择采集器终端"
            :disabled="!canConfig || !!st?.readOnly"
          />
        </div>
        <el-button :loading="saving.gps" :disabled="!canConfig || !!st?.readOnly" @click="saveGps"> 北斗校时 </el-button>
        <el-button :loading="saving.gps" :disabled="!canConfig || !!st?.readOnly" @click="clearGps"> 不校时 </el-button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts" name="timeSetting">
import { ElMessage, ElMessageBox } from "element-plus";
import { computed, onMounted, onUnmounted, ref, reactive } from "vue";

import TerminalTreeSelect from "@/components/TerminalTree/Select.vue";

import { getTimeStateApi, getTimeTerminalsApi, setGpsTerminalApi, setServerClockApi } from "@/api/modules/basecfg";
import type { TimeState, TimeTerminal } from "@/api/modules/basecfg";
import { useAuthStore } from "@/stores/modules/auth";

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
  if (!gps.value) return ElMessage.warning("请先选择采集器终端，或点「不校时」");
  saving.gps = true;
  try {
    await setGpsTerminalApi(gps.value);
    ElMessage.success("已设置北斗校时终端");
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
    ElMessage.success("已停用北斗校时");
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

/** 服务端没有改时钟的能力时按钮置灰；能力由 /api/time 的 canSetClock 告知 */
const canSetClock = computed(() => !!st.value?.canSetClock && !st.value?.readOnly);

const setClock = async (from: "manual" | "browser") => {
  const t = from === "browser" ? new Date() : new Date(cf.year, cf.month - 1, cf.day, cf.hour, cf.minute, cf.second);
  const p = (n: number) => String(n).padStart(2, "0");
  const text =
    `${t.getFullYear()}-${p(t.getMonth() + 1)}-${p(t.getDate())} ` +
    `${p(t.getHours())}:${p(t.getMinutes())}:${p(t.getSeconds())}`;

  // ⚠ 这一句留着：拨动系统时间会让按时刻表打铃的任务瞬间集体触发或整批哑掉。
  //   它是**破坏性操作的确认**，不是页面说明。
  await ElMessageBox.confirm(
    `将把服务器系统时间设置为 ${text}。\n\n` +
      "⚠ 这台机器正按时刻表打铃：拨动系统时间会让一批任务瞬间集体触发或整批哑掉，请避开上下课时段。",
    "设置服务器时间",
    { type: "warning", confirmButtonText: "确认设置" }
  );

  clockBusy.value = true;
  try {
    // 第二个参数是「同时关闭自动校时」。6.png 上没有这个勾选框，所以固定不关 ——
    // 关掉一个系统服务不该是某个按钮的隐藏副作用。
    await setServerClockApi(text, false);
    ElMessage.success("已设置服务器时间");
    await load();
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
.clock-actions {
  display: flex;
  justify-content: center;
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
