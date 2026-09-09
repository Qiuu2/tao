package i18n

import (
	"os"
	"path/filepath"
	"regexp"
	"strings"
	"testing"
)

// 字典里配的每一条中文，都得**真的在代码里出现过**。
//
// # 为什么需要这条
//
// 字典用中文原文当键，好处是调用点一个字都不用改；代价是原文改了
// （改错别字、改语气）字典就静默失配 —— 那条提示从此永远是中文，
// 而且没有任何症状。这个测试把「失配」变成一件当场能发现的事。
//
// 反过来（代码里有、字典里没有）不算错：翻译是一批一批补的，
// 没补的原样显示中文，看得见、能补。
// stringJoint 匹配 Go 里两个相邻字符串字面量之间的拼接点。
var stringJoint = regexp.MustCompile(`"\s*\+\s*\n\s*"`)

func TestDictKeysStillExistInCode(t *testing.T) {
	// 包在 internal/i18n 下，服务端根目录要往上两级 ——
	// 写成 ".." 只会扫到 internal/，cmd/htweb 里那一半提示全漏掉，
	// 于是测试会把它们统统报成「代码里找不到」。
	root := "../.."
	var all strings.Builder
	err := filepath.Walk(root, func(p string, info os.FileInfo, err error) error {
		if err != nil || info.IsDir() {
			return nil
		}
		if !strings.HasSuffix(p, ".go") || strings.HasSuffix(p, "_test.go") {
			return nil
		}
		// 字典本身不算数据源，否则每一条都能自己证明自己
		if strings.HasSuffix(p, "i18n/dict.go") {
			return nil
		}
		b, e := os.ReadFile(p)
		if e == nil {
			all.Write(b)
		}
		return nil
	})
	if err != nil {
		t.Fatalf("扫描源码失败: %v", err)
	}
	// Go 源码里长文案常写成 `"前半" +\n\t"后半"`，
	// 拼接点在文件里是一串 `" + 换行 缩进 "`。不抹掉它，
	// 整句的字典键就永远匹配不上 —— 那不是失配，是排版。
	src := stringJoint.ReplaceAllString(all.String(), "")
	if len(src) < 10000 {
		t.Fatalf("只扫到 %d 字节源码，路径大概不对 —— 这个测试就白跑了", len(src))
	}

	// 带引号的文案在 Go 源码里是转义写法（\" 而不是 "），
	// 直接拿字典键去比会全部落空 —— 那不是失配，是转义。
	// 所以两种形态都试一次。
	escaped := strings.NewReplacer(`\`, `\\`, `"`, `\"`)

	var orphan []string
	for zh := range codeDict {
		if strings.Contains(src, zh) || strings.Contains(src, escaped.Replace(zh)) {
			continue
		}
		orphan = append(orphan, zh)
	}
	if len(orphan) > 0 {
		t.Errorf("字典里这 %d 条中文在代码里已经找不到了 —— "+
			"多半是原文改了而字典没跟上，改完之后那条提示会永远是中文：\n  %s",
			len(orphan), strings.Join(orphan, "\n  "))
	}
}

// 翻译不能是空串，也不能原样照抄中文。
//
// 空串会让界面上那一处什么都不显示；照抄中文说明这一条根本没翻，
// 却占着位置让人以为已经翻过了。
func TestDictValuesAreRealTranslations(t *testing.T) {
	for zh, en := range dict {
		if strings.TrimSpace(en) == "" {
			t.Errorf("%q 的英文是空串 —— 界面上那一处会什么都不显示", zh)
		}
		if en == zh {
			t.Errorf("%q 的英文和中文一样 —— 这条没翻，却占着位置", zh)
		}
	}
}
