#!/usr/bin/env bash
#
# 把 e2e/ 下的脚本挨个跑一遍，**每个之前都把库还原到同一个起点**。
#
# # 为什么要有这个文件
#
# 这些脚本里有几个是真的改库的：terminal-selection 会删一台终端，
# terminal-shortcut-column 会做一次终端替换（删掉目标那条记录、把源改号），
# media-upload 会往 media 表和磁盘上写两个文件。
#
# 它们各自跑完都会自己收尾，但「收尾」和「还原到起点」不是一回事 ——
# 终端替换之后 id 变了、快捷键的宿主跟着变，下一个脚本再去找「广播室主话筒
# 的快捷键有几个目标」，看到的就是上一个脚本留下的世界。
#
# 实测过一次：单独跑每个都绿，连着跑第三个直接崩。那半小时全花在查一个
# 根本不存在的 bug 上。所以顺序不能靠排，得每次都回到同一个起点。
#
# # 用法
#
#   bash e2e/run-all.sh                  # 全跑
#   bash e2e/run-all.sh terminal-grid    # 只跑某几个
#
# 前置：mariadb 起着、htweb 在 18080、vite 在 5199（见各脚本头部）。
set -u

DB=${E2E_DB:-audioserver}
DUMP=${E2E_DUMP:-/tmp/e2e-baseline.sql}
DIR="$(cd "$(dirname "$0")" && pwd)"

TESTS=("$@")
if [ ${#TESTS[@]} -eq 0 ]; then
  TESTS=(login-slider terminal-grid terminal-selection terminal-shortcut-targets terminal-shortcut-column media-upload alarm-mapping-channels remote-edit map audit-detail bell-one-tone dashboard-browse)
fi

echo "== 备份基线到 $DUMP =="
mariadb-dump -uroot --no-tablespaces "$DB" > "$DUMP" || { echo "备份失败，先把 mariadb 起起来"; exit 1; }

fail=0
for t in "${TESTS[@]}"; do
  echo
  echo "==================== $t ===================="
  mariadb -uroot "$DB" < "$DUMP"
  if node "$DIR/$t.mjs"; then :; else
    echo "!! $t 没过"
    fail=$((fail + 1))
  fi
done

echo
echo "== 收尾：把库还原回基线 =="
mariadb -uroot "$DB" < "$DUMP"

if [ "$fail" -gt 0 ]; then
  echo "✗ $fail 个脚本没过"
  exit 1
fi
echo "✓ 全部通过"
