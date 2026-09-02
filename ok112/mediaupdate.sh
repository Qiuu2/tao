#!/bin/bash
rsync -zavu --progress --delete --password-file=/etc/rsyncd.secrets root@192.168.2.254::media /usr/mediadata
