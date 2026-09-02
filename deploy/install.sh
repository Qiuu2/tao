#!/bin/bash
#
# htweb 一键安装 / 升级。在**目标服务器**上执行：
#
#     sudo bash install.sh
#
# 同目录下需要这几个文件（打包时一起带上）：
#
#     install.sh            本脚本
#     install-sudoers.sh    免密 sudo 规则安装器
#     htweb.sudoers.in      规则模板
#     htweb.service.in      systemd 单元模板
#     config.yaml.example   配置样例
#     htweb                 Go 二进制（linux/amd64）
#     dist.tgz              前端产物
#
# 可以反复执行：已经装好的部分不会被重复折腾，config.yaml 存在时**不覆盖**。
#
# ─────────────────────────────────────────────────────────────
# 这个脚本存在的理由
#
# 之前部署全靠手工 scp + systemctl，其中「免密 sudo 规则」这一步最容易漏 ——
# 漏了的表现不是报错，而是「服务器信息 → 版本设置」和「时间设置」上的三个按钮
# 默默变灰，换台机器就得重新排查一遍。把它固化在这里，换服务器时自动配好。

set -euo pipefail

SERVICE_USER="${HTWEB_USER:-tw}"
A9000_ROOT="${HTWEB_A9000_ROOT:-/opt/apps/a9000}"
APP_DIR="$A9000_ROOT/htweb"
WEB_DIR="$A9000_ROOT/html/htweb"
UNIT=/etc/systemd/system/htweb.service
SRC_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

die() { echo "✗ $*" >&2; exit 1; }
step() { echo; echo "── $* ──"; }

[ "$(id -u)" -eq 0 ] || die "请用 root 运行：sudo bash $0"
id "$SERVICE_USER" >/dev/null 2>&1 || die "账号 $SERVICE_USER 不存在，先建账号或用 HTWEB_USER=xxx 指定"
[ -d "$A9000_ROOT" ] || die "找不到 $A9000_ROOT —— 这台机器上没装旧系统？可用 HTWEB_A9000_ROOT=xxx 指定"

step "目录"
install -d -o "$SERVICE_USER" -g "$SERVICE_USER" -m 755 \
  "$APP_DIR" "$APP_DIR/logs" "$APP_DIR/backups" "$WEB_DIR"
echo "✓ $APP_DIR / $WEB_DIR"

step "后端二进制"
[ -f "$SRC_DIR/htweb" ] || die "同目录下没有 htweb 二进制"
# 先停服务再覆盖：正在运行的可执行文件直接 cp 会 ETXTBSY
if systemctl is-active --quiet htweb 2>/dev/null; then
  systemctl stop htweb
  STOPPED=1
fi
install -o "$SERVICE_USER" -g "$SERVICE_USER" -m 755 "$SRC_DIR/htweb" "$APP_DIR/htweb"
echo "✓ $APP_DIR/htweb  ($(md5sum "$APP_DIR/htweb" | cut -c1-32))"

step "前端"
if [ -f "$SRC_DIR/dist.tgz" ]; then
  TMPW="$(mktemp -d)"
  tar -xzf "$SRC_DIR/dist.tgz" -C "$TMPW"
  # 先搬旧的再落新的：中途出错至少还留着上一版，能手工搬回来
  rm -rf "$WEB_DIR.prev"
  [ -d "$WEB_DIR" ] && mv "$WEB_DIR" "$WEB_DIR.prev"
  mv "$TMPW" "$WEB_DIR"
  chown -R "$SERVICE_USER:$SERVICE_USER" "$WEB_DIR"
  chmod 755 "$WEB_DIR"
  echo "✓ $WEB_DIR（$(find "$WEB_DIR" -type f | wc -l) 个文件，上一版留在 $WEB_DIR.prev）"
else
  echo "· 没带 dist.tgz，跳过前端"
fi

step "配置"
if [ -f "$APP_DIR/config.yaml" ]; then
  echo "· $APP_DIR/config.yaml 已存在，**不覆盖**（里面有数据库密码和 JWT 密钥）"
else
  [ -f "$SRC_DIR/config.yaml.example" ] || die "首次安装需要 config.yaml.example"
  install -o "$SERVICE_USER" -g "$SERVICE_USER" -m 600 \
    "$SRC_DIR/config.yaml.example" "$APP_DIR/config.yaml"
  echo "✓ 已生成 $APP_DIR/config.yaml（权限 600）"
  echo "⚠ 首次安装：请改掉里面的数据库账号密码和 auth.secret，然后重跑本脚本或手动重启 htweb"
fi

step "systemd 单元"
[ -f "$SRC_DIR/htweb.service.in" ] || die "同目录下没有 htweb.service.in"
TMPU="$(mktemp)"
sed -e "s|@SERVICE_USER@|$SERVICE_USER|g" -e "s|@APP_DIR@|$APP_DIR|g" \
  "$SRC_DIR/htweb.service.in" > "$TMPU"
if [ -f "$UNIT" ] && cmp -s "$TMPU" "$UNIT"; then
  rm -f "$TMPU"
  echo "· $UNIT 已是最新"
else
  install -m 644 "$TMPU" "$UNIT"
  rm -f "$TMPU"
  systemctl daemon-reload
  echo "✓ $UNIT"
fi

step "免密 sudo 规则"
# ⚠ 这一步就是本脚本存在的主要理由，别跳过。
#   缺了它，「版本设置」的提交和「时间设置」的设置/同步时间三个按钮会静静地变灰。
bash "$SRC_DIR/install-sudoers.sh" "$SERVICE_USER" "$A9000_ROOT"

step "启动"
systemctl enable htweb >/dev/null 2>&1 || true
systemctl restart htweb
sleep 8
if systemctl is-active --quiet htweb; then
  echo "✓ htweb 已启动"
else
  echo "✗ htweb 没起来，最近日志："
  journalctl -u htweb -n 30 --no-pager || tail -n 30 "$APP_DIR/logs/htweb.log"
  exit 1
fi

PORT=$(grep -oP '(?<=listen:\s")[^"]+' "$APP_DIR/config.yaml" 2>/dev/null | head -1 || true)
echo
echo "完成。访问 http://<本机地址>${PORT:-:8080}/"
[ -n "${STOPPED:-}" ] && echo "（升级前已停过一次服务，期间 Web 短暂不可用；后台广播服务不受影响）"
exit 0
