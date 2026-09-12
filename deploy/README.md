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

# 装包（⚠ 下面这些命令假设你在**仓库根目录**，不是在 web/ 里）
cd ..
P=/tmp/htwebpkg; rm -rf $P; mkdir -p $P
cp deploy/install.sh deploy/install-sudoers.sh deploy/htweb.sudoers.in \
   deploy/htweb.service.in deploy/config.yaml.example deploy/htweb-ha-apply $P/
cp /tmp/htweb $P/htweb
(cd web/dist && tar -czf $P/dist.tgz .)
tar -czf /tmp/htwebpkg.tgz -C /tmp htwebpkg
```

⚠ `htweb-ha-apply` 别漏。少了它 `install.sh` 不会报错，只会打一行
「主备服务器配置将只写数据库」—— 那一页就会整组跳过。

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

### 只换代码的最短路径

只改了 Go / Vue 代码、没动 sudoers 和 systemd 单元时，也可以不走装包，
直接换那两样东西：

```bash
# 开发机
cd server && CGO_ENABLED=0 GOOS=linux GOARCH=amd64 go build -trimpath -ldflags='-s -w' -o /tmp/htweb ./cmd/htweb
cd ../web  && npx vue-tsc --noEmit --skipLibCheck && npx vite build --mode production
tar -czf /tmp/dist.tgz -C dist .
scp /tmp/htweb /tmp/dist.tgz <账号>@<服务器>:/tmp/

# 服务器
sudo systemctl stop htweb                       # 正在跑的可执行文件直接盖会 ETXTBSY
sudo install -o tw -g tw -m 755 /tmp/htweb /opt/apps/a9000/htweb/htweb
sudo rm -rf /opt/apps/a9000/html/htweb.prev
sudo mv /opt/apps/a9000/html/htweb /opt/apps/a9000/html/htweb.prev
sudo mkdir -p /opt/apps/a9000/html/htweb
sudo tar -xzf /tmp/dist.tgz -C /opt/apps/a9000/html/htweb
sudo chown -R tw:tw /opt/apps/a9000/html/htweb
sudo systemctl start htweb
```

**改了 `deploy/` 下任何东西（sudoers 模板、service 模板、htweb-ha-apply）
就别走这条**，老老实实走第 1、2 步 —— 那几样只有 `install.sh` 会装。

### 装到哪儿了：一张路径表

| 路径 | 是什么 | 升级时会不会被换 |
|---|---|---|
| `/opt/apps/a9000/htweb/htweb` | 后端二进制 | ✅ 每次都换 |
| `/opt/apps/a9000/htweb/config.yaml` | 配置（含数据库口令、JWT 密钥） | ❌ **已存在就不覆盖** |
| `/opt/apps/a9000/htweb/logs/htweb.log` | 运行日志（systemd 直接追加写这里） | — |
| `/opt/apps/a9000/htweb/htweb-ha-apply` | 主备配置写 /etc 那几个文件的小脚本（root:root 755） | ✅ 带了就换 |
| `/opt/apps/a9000/html/htweb/` | **前端产物**（Apache 的 DocumentRoot 下） | ✅ 每次都换，上一版留在 `html/htweb.prev` |
| `/opt/apps/a9000/html/ok112/` | 旧系统，**不动** | ❌ |
| `/etc/systemd/system/htweb.service` | systemd 单元 | 内容变了才换 |
| `/etc/sudoers.d/htweb` | 免密 sudo 规则 | 内容变了才换 |

⚠ 注意后端在 `a9000/htweb/`，前端在 `a9000/html/htweb/` —— **一个在 html 下，
一个不在**。写路径时最容易搞混的就是这两个。

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
