#!/bin/bash
###

# 网络连接名称和网卡名称
CONNECTION_NAME="有线连接 1"  # 连接名称，通过 `nmcli connection show` 查看
INTERFACE_NAME="eth0"                # 网卡名称，通过 `ip a` 查看

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

if [ $FLAG -ge 1 ]; then
	MASTERIPMASK="$MASTERIP"/"$SUBNETMASK"
else
   MASTERIPMASK="$SLAVEIP"/"$SUBNETMASK"
fi
echo "ipnetmask: $MASTERIPMASK"
   nmcli connection modify "$CONNECTION_NAME" ipv4.method manual ipv4.addresses "$MASTERIPMASK"
  # nmcli connection modify "$CONNECTION_NAME" ipv4.method manual ipv4.addresses "$MASTERIPMASK" ipv4.gateway "$GATEWAY" 
# 重启网络连接以应用更改
#nmcli connection down "$CONNECTION_NAME"
#nmcli connection up "$CONNECTION_NAME"

echo "网络配置已成功更新。"
