#!/bin/bash
#
# 安装 htweb 的免密 sudo 规则。
#
#   sudo bash install-sudoers.sh [服务账号] [a9000根目录]
#
# 默认：服务账号 tw，根目录 /opt/apps/a9000。
#
# 这个脚本可以**反复执行**：内容没变就什么都不做，变了才覆盖。
# install.sh 会调它，也可以单独跑（比如老服务器补装）。
#
# ⚠ 写 /etc/sudoers.d 是有可能把整台机器的 sudo 弄坏的操作。
#   所以这里全程按「先在临时文件里做好、visudo -c 校验通过、再原子替换」来做，
#   任何一步不对就退出，绝不留一个半成品在 sudoers.d 里。

set -euo pipefail

SERVICE_USER="${1:-tw}"
A9000_ROOT="${2:-/opt/apps/a9000}"
TARGET=/etc/sudoers.d/htweb
SRC_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TEMPLATE="$SRC_DIR/htweb.sudoers.in"

die() { echo "✗ $*" >&2; exit 1; }
note() { echo "  $*"; }

[ "$(id -u)" -eq 0 ] || die "请用 root 运行：sudo bash $0"
[ -f "$TEMPLATE" ] || die "找不到模板 $TEMPLATE"
command -v visudo >/dev/null 2>&1 || [ -x /usr/sbin/visudo ] || die "系统里没有 visudo，不敢写 sudoers"
VISUDO="$(command -v visudo 2>/dev/null || echo /usr/sbin/visudo)"

id "$SERVICE_USER" >/dev/null 2>&1 || die "账号 $SERVICE_USER 不存在"

# timedatectl 的路径各发行版不完全一样，探一下再写进规则。
# sudoers 里必须是绝对路径，不能靠 PATH。
TIMEDATECTL=""
for p in /usr/bin/timedatectl /bin/timedatectl; do
  [ -x "$p" ] && TIMEDATECTL="$p" && break
done
[ -n "$TIMEDATECTL" ] || die "找不到 timedatectl，这台机器没法通过 Web 设置系统时间"

UPDATE_SH="$A9000_ROOT/script/cmd/update_audioserver.sh"
if [ ! -f "$UPDATE_SH" ]; then
  # 不是致命错误：有些机器不带版本包。规则照样写（路径固定），
  # 但要说清楚现在还没有这个文件，免得以后有人对着页面上的报错找半天。
  echo "⚠ 未找到 $UPDATE_SH —— 版本设置页在装上这个脚本之前仍然不可用"
else
  # ⚠ 这条规则等于把 root 交给了这个脚本文件。
  #   如果它能被服务账号之外的普通用户改写，那就等于把 root 给了那个用户。
  owner=$(stat -c '%U' "$UPDATE_SH")
  perm=$(stat -c '%a' "$UPDATE_SH")
  case "$perm" in
    *[2367]) die "$UPDATE_SH 对同组或其他用户可写（权限 $perm），
   给它免密 root 等于把 root 交出去。请先 chmod go-w 再重跑。" ;;
  esac
  note "update_audioserver.sh 属主 $owner 权限 $perm —— 可以"
fi

TMP="$(mktemp /tmp/htweb-sudoers.XXXXXX)"
trap 'rm -f "$TMP"' EXIT

sed -e "s|@SERVICE_USER@|$SERVICE_USER|g" \
    -e "s|@A9000_ROOT@|$A9000_ROOT|g" \
    -e "s|@TIMEDATECTL@|$TIMEDATECTL|g" \
    "$TEMPLATE" > "$TMP"

# 语法校验。-c 检查、-f 指定文件；不通过就地退出，目标文件一个字节都不动。
"$VISUDO" -c -f "$TMP" >/dev/null || die "生成的 sudoers 语法不对，已放弃写入"

if [ -f "$TARGET" ] && cmp -s "$TMP" "$TARGET"; then
  echo "✓ $TARGET 已是最新，无需改动"
else
  chmod 0440 "$TMP"
  chown root:root "$TMP"
  # 同一文件系统内的 mv 是原子的：要么旧的、要么新的，不会出现半个文件
  mv -f "$TMP" "$TARGET"
  trap - EXIT
  echo "✓ 已安装 $TARGET"
fi

# 装完立刻以服务账号的身份验一遍，别只相信语法检查
echo "── 验证 ──"
if sudo -u "$SERVICE_USER" sudo -n -l "$TIMEDATECTL" set-time '2000-01-01 00:00:00' >/dev/null 2>&1; then
  echo "✓ $SERVICE_USER 可以免密执行 timedatectl set-time"
else
  echo "✗ $SERVICE_USER 仍然不能免密执行 timedatectl set-time —— 请把上面的输出发给开发"
fi
if [ -f "$UPDATE_SH" ]; then
  if sudo -u "$SERVICE_USER" sudo -n -l "$UPDATE_SH" >/dev/null 2>&1; then
    echo "✓ $SERVICE_USER 可以免密执行 update_audioserver.sh"
  else
    echo "✗ $SERVICE_USER 仍然不能免密执行 update_audioserver.sh"
  fi
fi

echo
echo "htweb 不需要重启：能力是每次请求现探的，刷新页面即可看到按钮变亮。"
