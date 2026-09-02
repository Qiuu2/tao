#!/bin/bash
###

# 网络连接名称和网卡名称
#CONNECTION_NAME="有线连接 1"  # 连接名称，通过 `nmcli connection show` 查看
#INTERFACE_NAME="eth0"                # 网卡名称，通过 `ip a` 查看

# 查找 eth0 的相关连接名称
CONNECTION_NAME=$(nmcli -t -f NAME,TYPE,STATE con show | grep -i "ethernet" | grep ":activated" | cut -d: -f1 | head -n1)
if [ -z "$CONNECTION_NAME" ]; then
    echo "错误：未找到活动的有线连接！"
    exit 1
fi
	echo "找到 eth0 的连接名称: $CONNECTION_NAME"


# 解析查询结果
SUBNETMASK=$1  #0-32
MASTERIP=$2
SLAVEIP=$3
GATEWAY=$5
FLAG=$4
# 检查是否获取到有效的 IP 地址
if [ -z "$MASTERIP" ]; then
  echo "错误：未获取到有效的 IP 地址。"
  exit 1
fi

# 修改 NetworkManager 配置
echo "正在更新网络配置："
echo "IP 地址: $MASTERIP"
echo "网关: $GATEWAY"
echo "netmask: $SUBNETMASK"
echo "FLAG: $FLAG"
# 调用函数并输出结果

if [ "$FLAG" -eq 1 ]; then
	MASTERIPMASK="$MASTERIP"/"$SUBNETMASK"
else
   MASTERIPMASK="$SLAVEIP"/"$SUBNETMASK"
fi
echo "ipnetmask: $MASTERIPMASK"
   #nmcli connection modify "$CONNECTION_NAME" ipv4.method manual ipv4.addresses "$MASTERIPMASK"
    nmcli connection modify "$CONNECTION_NAME" ipv4.method manual ipv4.addresses "$MASTERIPMASK" ipv4.gateway "$GATEWAY" 
# 重启网络连接以应用更改
nmcli connection down "$CONNECTION_NAME"
nmcli connection up "$CONNECTION_NAME"

echo "网络配置已成功更新。"
