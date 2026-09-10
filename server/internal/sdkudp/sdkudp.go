// Package sdkudp 按厂商 SDK 的二进制协议给后台广播服务发命令。
//
// 目前只有一条：紧急广播（cmdid=194，SDKUrgentPlay_t）。
//
// # 与 internal/notify 的区别
//
// notify 发的是纯文本报文（`task?state=3&id=7`），走 serverbaseparam.webport；
// 这里发的是**结构体的内存布局**，走 SDK 端口 8885。两套协议、两个端口，
// 不能混在一个包里 —— 混了迟早有人把文本报文发到 8885 上，
// 而 UDP 收不到任何反馈，表现是「点了没反应」。
//
// # 报文长什么样
//
// 厂商给的是 C 结构体，直接 sendto 出去，所以**字节序是发送方的本机序**
// （x86 = 小端），不是网络序。htons 只用在端口上。
//
//	偏移  类型      字段        值
//	 0    uint16    cmdheader   0xff66
//	 2    uint16    cmdid       194
//	 4    uint16    length      args 的字节数（这条命令是 16）
//	 6    int16     state       0
//	 8    int32     serialid    0
//	12    int32     sessionid   1
//	16    args[length]          见 UrgentPlay
//	16+length  byte  0x5a       结尾哨兵，C 代码里是 args[length]=0x5a
//
// 总长 = SDK_CMD_HEADER_LENGTH(16) + length + 1，这条命令是 33 字节。
//
// ⚠ 两个结构体都不需要补齐：头部 2+2+2+2+4+4 正好 16，
// args 是 4 个 int32 正好 16。C 编译器不会在中间插洞，Go 这边照着摆就行。
package sdkudp

import (
	"context"
	"encoding/binary"
	"fmt"
	"log"
	"net"
	"strconv"
	"strings"
	"time"
)

const (
	// cmdHeader 是 SDK 报文的固定头（C 代码里的 0xff66）。
	cmdHeader = 0xff66
	// cmdTail 是结尾那个字节，C 代码写的是 args[length] = 0x5a。
	cmdTail = 0x5a
	// headerLen 对应 SDK_CMD_HEADER_LENGTH。
	headerLen = 16

	// cmdUrgentPlay 紧急广播。
	cmdUrgentPlay = 194
)

// 发请求时头部这三个字段的取值。
//
// 厂商给的代码只在**回包**那一侧设过 state（=ESDKCMD_ERROR_OK），
// 请求侧没有示范。这三个值是现场确认的。
const (
	reqState     = 0
	reqSerialID  = 0
	reqSessionID = 1
)

// Sender 往 SDK 端口发命令。
type Sender struct {
	host    string
	port    int
	enabled bool
}

// New 造一个发送器。
//
// host 留空表示**跟着浏览器打开这个页面的地址走**（见 Host 方法）；
// 填了就是固定发到这个地址，不再看请求。
//
// enabled 为 false 时只记日志不真发 —— 联调期不想真让喇叭响的时候用。
func New(host string, port int, enabled bool) *Sender {
	return &Sender{host: strings.TrimSpace(host), port: port, enabled: enabled}
}

// Host 定下这一次要发到哪台机器。
//
// # 为什么默认跟着页面地址走
//
// 后台广播服务和这个 Web 服务跑在**同一台机器**上 —— 那台机器就是运维在
// 浏览器地址栏里敲的那个地址。写死 127.0.0.1 只在「人就坐在服务器前面用
// localhost 打开」时才对；从别的机器打开 `http://192.168.1.50:8080`，
// 包却发去了 Web 进程自己的回环，8885 上没人收，而 UDP 不会报任何错 ——
// 表现就是「点了没反应」，还查不出原因。
//
// 所以取请求的 Host（浏览器实际连的那个地址），去掉端口，配上 SDK 的端口。
//
// # 什么时候该在 config 里写死
//
// 后台服务和 Web 不在同一台机器上时（比如广播服务在容器里、Web 在宿主上），
// 填 sdk.host 把它钉死。填了就完全不看请求。
//
// ⚠ Host 头是**客户端发来的**，能被改。这里只拿它当地址用，
// 报文内容是固定的那 33 字节，不含任何来自请求的数据；而且这条路由本来就要
// 登录 + taskpriv。真要收紧到一个地址，就是上面那句「在 config 里写死」。
func (s *Sender) Host(reqHost string) string {
	if s.host != "" {
		return s.host
	}
	if h := hostOnly(reqHost); h != "" {
		return h
	}
	// 请求里也拿不到 —— 退回回环，至少还是个能连的地址
	return "127.0.0.1"
}

// hostOnly 从 "192.168.1.50:8080" 这样的串里取出主机名。
// 没带端口的原样返回；IPv6 的 "[::1]:8080" 由 net.SplitHostPort 处理。
func hostOnly(v string) string {
	v = strings.TrimSpace(v)
	if v == "" {
		return ""
	}
	if h, _, err := net.SplitHostPort(v); err == nil {
		return h
	}
	return v
}

// UrgentPlay 是紧急广播命令的 args（C 里的 SDKUrgentPlay_t）。
type UrgentPlay struct {
	// ChannelID 0=地震 1=疏散 2=警戒 3=消防
	ChannelID int32
	// KeyID 1000=地震 1001=疏散 1002=警戒 1003=消防
	KeyID int32
	// TerminalID 目标终端。**0 表示全部终端都播**。
	TerminalID int32
	// IsStop 0=执行 1=停止
	IsStop int32
}

// encode 把命令拼成要发出去的那串字节。单独拎出来是为了能测 ——
// 这个协议一个字节错位就静默失效，UDP 那头不会告诉你任何事。
func encode(cmdID uint16, args []byte) []byte {
	buf := make([]byte, headerLen+len(args)+1)
	binary.LittleEndian.PutUint16(buf[0:], cmdHeader)
	binary.LittleEndian.PutUint16(buf[2:], cmdID)
	binary.LittleEndian.PutUint16(buf[4:], uint16(len(args)))
	binary.LittleEndian.PutUint16(buf[6:], uint16(reqState))
	binary.LittleEndian.PutUint32(buf[8:], uint32(reqSerialID))
	binary.LittleEndian.PutUint32(buf[12:], uint32(reqSessionID))
	copy(buf[headerLen:], args)
	buf[headerLen+len(args)] = cmdTail
	return buf
}

func (p UrgentPlay) encodeArgs() []byte {
	b := make([]byte, 16)
	binary.LittleEndian.PutUint32(b[0:], uint32(p.ChannelID))
	binary.LittleEndian.PutUint32(b[4:], uint32(p.KeyID))
	binary.LittleEndian.PutUint32(b[8:], uint32(p.TerminalID))
	binary.LittleEndian.PutUint32(b[12:], uint32(p.IsStop))
	return b
}

// SendUrgentPlay 发一条紧急广播命令。
//
// ⚠ UDP 是无连接的：**发出去不代表播出来**。这里能报的错只有本地那几种
// （地址写错、端口不通），后台服务收没收到、认不认这条命令，这一侧看不见。
// 所以界面上的提示只能说「已下发」，不能说「已播放」。
// reqHost 是浏览器打开这个页面用的地址（http.Request.Host），
// 决定这一包发到哪台机器 —— 见 Sender.Host。
func (s *Sender) SendUrgentPlay(ctx context.Context, reqHost string, p UrgentPlay) error {
	return s.send(ctx, reqHost, cmdUrgentPlay, p.encodeArgs(),
		fmt.Sprintf("紧急广播 channel=%d key=%d terminal=%d stop=%d",
			p.ChannelID, p.KeyID, p.TerminalID, p.IsStop))
}

func (s *Sender) send(ctx context.Context, reqHost string, cmdID uint16, args []byte, desc string) error {
	payload := encode(cmdID, args)
	addr := net.JoinHostPort(s.Host(reqHost), strconv.Itoa(s.port))

	if !s.enabled {
		log.Printf("sdkudp(已禁用，仅记录) -> %s : %s [% x]", addr, desc, payload)
		return nil
	}

	var d net.Dialer
	conn, err := d.DialContext(ctx, "udp", addr)
	if err != nil {
		return fmt.Errorf("连接 %s: %w", addr, err)
	}
	defer conn.Close()

	_ = conn.SetWriteDeadline(time.Now().Add(2 * time.Second))
	if _, err := conn.Write(payload); err != nil {
		return fmt.Errorf("发送到 %s: %w", addr, err)
	}
	log.Printf("sdkudp -> %s : %s [% x]", addr, desc, payload)
	return nil
}
