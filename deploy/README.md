# 部署到一台新服务器

## 一句话

在开发机打好包，传到目标机器，`sudo bash install.sh`，完事。
免密 sudo 规则、systemd 单元这些容易漏的东西都在脚本里，不需要记。

---

## 1. 在开发机打包

```bash
# 后端（在 WSL / Linux 上交叉编译，目标机器不需要装 Go）
cd server
CGO_ENABLED=0 GOOS=linux GOARCH=amd64 go build -trimpath -ldflags='-s -w' -o /tmp/htweb ./cmd/htweb

# 前端（目标机器不需要装 node）
cd ../web
npx vue-tsc --noEmit --skipLibCheck && npx vite build --mode production

# 装包
P=/tmp/htwebpkg; rm -rf $P; mkdir -p $P
cp deploy/install.sh deploy/install-sudoers.sh deploy/htweb.sudoers.in \
   deploy/htweb.service.in deploy/config.yaml.example $P/
cp /tmp/htweb $P/htweb
(cd web/dist && tar -czf $P/dist.tgz .)
tar -czf /tmp/htwebpkg.tgz -C /tmp htwebpkg
```

## 2. 在目标机器上装

```bash
scp /tmp/htwebpkg.tgz <账号>@<新服务器>:/tmp/
ssh <账号>@<新服务器>
tar -xzf /tmp/htwebpkg.tgz -C /tmp
sudo bash /tmp/htwebpkg/install.sh
```

装完会打印每一步的结果，包括免密规则的**当场验证**。
两行 `✓ tw 可以免密执行 …` 就是这次部署成没成的判据。

**首次安装**还要改 `config.yaml`（脚本会提示）：

```bash
sudo vi /opt/apps/a9000/htweb/config.yaml   # 改 database.pass 和 auth.secret
sudo systemctl restart htweb
```

数据库账号要**只有** DML、**没有任何 DDL** 权限：

```sql
CREATE USER 'htweb'@'%' IDENTIFIED BY '<强口令>';
GRANT SELECT, INSERT, UPDATE, DELETE ON `audioserver`.* TO 'htweb'@'%';
```

零 DDL 是这套系统与旧库共存的前提。账号本身没有 DDL 权限，
等于给「不小心改了表结构」加了最后一道锁。

## 3. 换了账号或路径？

```bash
sudo HTWEB_USER=someone HTWEB_A9000_ROOT=/srv/a9000 bash install.sh
```

sudoers 规则、systemd 单元里的路径都会跟着变。

## 4. 升级（同一台机器）

重复第 1、2 步即可。脚本是幂等的：

- `config.yaml` 已存在就**不覆盖**
- 前端上一版留在 `html/htweb.prev`，出问题能立刻搬回来
- sudoers、systemd 单元内容没变就不动

---

## 这套脚本到底在解决什么

「服务器信息 → 版本设置」的提交，和「时间设置」的两个按钮，
都需要以 root 跑几条具体的命令。htweb 以普通账号运行（也应该如此），
所以要一条受限的免密 sudo 规则。

这一步以前是手工做的，而且**漏了不报错** —— 表现只是按钮静静地变灰。
换台机器就得重新排查一遍。所以固化成脚本。

装的是什么（`/etc/sudoers.d/htweb`，0440 root:root）：

| 命令 | 给谁用 |
|---|---|
| `timedatectl set-time *` | 时间设置 → 设置服务器时间 / 同步当前时间 |
| `timedatectl set-ntp *` | 同上，勾了「同时关闭自动校时」时才会用到 |
| `<a9000根>/script/cmd/update_audioserver.sh` | 服务器信息 → 版本设置 → 提交 |

除此之外一律不给。装完可以自己验一遍：

```bash
sudo -n /bin/cat /etc/shadow            # 应该被拒
sudo -n timedatectl set-timezone UTC    # 应该被拒（只放开了 set-time / set-ntp）
```

### ⚠ systemd 单元里不能有 `NoNewPrivileges=true`

这条曾经在单元里，看着像加固，实际上把上面三个功能全弄死了：
`NoNewPrivileges` 会禁掉整个 setuid 提权路径，而 sudo 正是 setuid 程序。
于是 sudoers 写得再对，服务里的 `sudo -n` 也一律失败，
而且报的是「服务账号没有免密权限」—— 指向一个明明已经配好了的地方。

`htweb.service.in` 里把这行注释掉了并写明了原因。真正的安全边界在 sudoers 那一侧。

### 装完不用重启 htweb

能力是每次请求现探的（`GET /api/time` 的 `canSetClock`、
`GET /api/server/version` 的 `canSwitch`），刷新页面按钮就亮了。

### 探不通时程序怎么表现

不会去猜密码，也不会假装成功。按钮置灰，接口把「缺哪一条」原样返回。

⚠ 但**页面上不再显示这条原因** —— 按产品要求，所有模块都不放说明文字。
所以按钮灰了而没有任何提示时，去接口上看：

```
GET /api/time            → canSetClock / clockBlockReason
GET /api/server/version  → canSwitch / reason
```

`POST /api/server/version` 更是在动手之前就挡住 ——
不会出现「包解开了、容器没重建」这种半途而废的状态。
