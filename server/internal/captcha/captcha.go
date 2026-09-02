// Package captcha 提供轻量图形验证码。
//
// 刻意不引入第三方验证码库：服务器有软件包签名策略，依赖越少越好；
// 这里用标准库 image/png 自绘 5x7 点阵数字，足以阻挡脚本化的暴力登录。
//
// 对应业务规则 BR-76（验证码长度上限 10）与 BR-78（旧系统的万能验证码
// htjy123 已彻底移除，本包不存在任何后门分支）。
package captcha

import (
	"bytes"
	"crypto/rand"
	"encoding/base64"
	"image"
	"image/color"
	"image/png"
	"math/big"
	"strings"
	"sync"
	"time"
)

// 5x7 点阵字形。只用数字，避免 0/O、1/I 之类的混淆。
var glyphs = map[byte][7]string{
	'0': {"01110", "10001", "10011", "10101", "11001", "10001", "01110"},
	'1': {"00100", "01100", "00100", "00100", "00100", "00100", "01110"},
	'2': {"01110", "10001", "00001", "00010", "00100", "01000", "11111"},
	'3': {"11110", "00001", "00001", "01110", "00001", "00001", "11110"},
	'4': {"00010", "00110", "01010", "10010", "11111", "00010", "00010"},
	'5': {"11111", "10000", "11110", "00001", "00001", "10001", "01110"},
	'6': {"00110", "01000", "10000", "11110", "10001", "10001", "01110"},
	'7': {"11111", "00001", "00010", "00100", "01000", "01000", "01000"},
	'8': {"01110", "10001", "10001", "01110", "10001", "10001", "01110"},
	'9': {"01110", "10001", "10001", "01111", "00001", "00010", "01100"},
}

const (
	codeLen = 4
	scale   = 6
	padX    = 10
	padY    = 10
)

type entry struct {
	code      string
	expiresAt time.Time
}

// Store 保存待校验的验证码。单实例内存存储即可满足本项目规模。
type Store struct {
	mu  sync.Mutex
	m   map[string]entry
	ttl time.Duration
}

func NewStore(ttl time.Duration) *Store {
	if ttl <= 0 {
		ttl = 3 * time.Minute
	}
	s := &Store{m: make(map[string]entry), ttl: ttl}
	go s.gc()
	return s
}

func (s *Store) gc() {
	t := time.NewTicker(time.Minute)
	defer t.Stop()
	for range t.C {
		now := time.Now()
		s.mu.Lock()
		for k, v := range s.m {
			if now.After(v.expiresAt) {
				delete(s.m, k)
			}
		}
		s.mu.Unlock()
	}
}

// Generate 产出验证码 ID 与 PNG 的 data URI。
func (s *Store) Generate() (id string, dataURI string, err error) {
	code := randomDigits(codeLen)
	id = randomID()

	img := render(code)
	var buf bytes.Buffer
	if err = png.Encode(&buf, img); err != nil {
		return "", "", err
	}

	s.mu.Lock()
	s.m[id] = entry{code: code, expiresAt: time.Now().Add(s.ttl)}
	s.mu.Unlock()

	return id, "data:image/png;base64," + base64.StdEncoding.EncodeToString(buf.Bytes()), nil
}

// Verify 校验并立即作废该验证码（一次性使用，防重放）。
func (s *Store) Verify(id, input string) bool {
	if id == "" || input == "" {
		return false
	}
	s.mu.Lock()
	e, ok := s.m[id]
	delete(s.m, id) // 无论对错都作废
	s.mu.Unlock()

	if !ok || time.Now().After(e.expiresAt) {
		return false
	}
	return strings.EqualFold(strings.TrimSpace(input), e.code)
}

func render(code string) image.Image {
	w := padX*2 + len(code)*(5*scale+scale)
	h := padY*2 + 7*scale
	img := image.NewRGBA(image.Rect(0, 0, w, h))

	bg := color.RGBA{R: 245, G: 247, B: 250, A: 255}
	for y := 0; y < h; y++ {
		for x := 0; x < w; x++ {
			img.Set(x, y, bg)
		}
	}

	// 干扰线
	for i := 0; i < 5; i++ {
		c := color.RGBA{R: uint8(160 + n(60)), G: uint8(160 + n(60)), B: uint8(180 + n(50)), A: 255}
		y0, y1 := n(h), n(h)
		for x := 0; x < w; x++ {
			y := y0 + (y1-y0)*x/w
			img.Set(x, y, c)
			img.Set(x, y+1, c)
		}
	}

	for i := 0; i < len(code); i++ {
		g, ok := glyphs[code[i]]
		if !ok {
			continue
		}
		fg := color.RGBA{R: uint8(20 + n(60)), G: uint8(40 + n(60)), B: uint8(90 + n(80)), A: 255}
		ox := padX + i*(5*scale+scale)
		oy := padY + n(5) - 2 // 轻微上下抖动
		for ry := 0; ry < 7; ry++ {
			row := g[ry]
			for rx := 0; rx < 5; rx++ {
				if row[rx] != '1' {
					continue
				}
				for dy := 0; dy < scale; dy++ {
					for dx := 0; dx < scale; dx++ {
						img.Set(ox+rx*scale+dx, oy+ry*scale+dy, fg)
					}
				}
			}
		}
	}
	return img
}

func randomDigits(n int) string {
	var sb strings.Builder
	for i := 0; i < n; i++ {
		sb.WriteByte(byte('0' + nInt(10)))
	}
	return sb.String()
}

func randomID() string {
	b := make([]byte, 12)
	_, _ = rand.Read(b)
	return base64.RawURLEncoding.EncodeToString(b)
}

func n(max int) int { return nInt(max) }
func nInt(max int) int {
	if max <= 0 {
		return 0
	}
	v, err := rand.Int(rand.Reader, big.NewInt(int64(max)))
	if err != nil {
		return 0
	}
	return int(v.Int64())
}
