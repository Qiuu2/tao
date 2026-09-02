#!/bin/sh
###
 # @Brief: 
 # @Author: Li Jian
 # @Date: 2023-02-27 17:34:16
 # @LastEditors: Li Jian
 # @LastEditTime: 2024-02-01 15:13:14
 # @FilePath: /a9000-autoinstall/ubuntu-autoinstall-generator/a9000/home/heartbeat/ha-post.sh
 # Copyright (c) 2023 by Li Jian email: jianli508@163.com, All Rights Reserved. 
### 
route add default gw 192.168.2.1
sed -i '0,/-/s/\(-\)\(.*\)/\1 192.168.2.159\/24/' /etc/netplan/00-installer-config.yaml
systemctl start ntp.service
