// Package templates 装着作息方案模板。
//
// # 从哪来
//
// 由 nlu/golden/gen_schedule_templates.py 从原实现的
// backend/default_data/{schedule_template_catalog.json, schedule_templates/}
// **蒸馏**出来的：原文件 396K，绝大部分是 SDK 那边的字段
// （taskid、terminalids、offlinestate、state…），在这套库里一个也用不上。
//
// 留下的只有建一个作息方案真正需要的五样：条目名、时刻、时长、星期、媒体名。
// 终端由建方案时按"全部播放终端"另填；媒体**按名字**在本库的 media 表里找 ——
// 原库的 mediaid 在这里指向的是完全不同的东西，照抄会挂上一首错的歌。
//
// 蒸馏之后 44K，而且是人看得懂的：模板不对时可以直接改这个文件。
package templates

import (
	_ "embed"
	"encoding/json"
	"fmt"
	"strings"
	"sync"
)

//go:embed schedules.json
var raw []byte

// Item 是模板里的一个打铃条目。
type Item struct {
	Name     string   `json:"name"`
	Start    string   `json:"start"`
	Seconds  int      `json:"seconds"`
	Weekdays []string `json:"weekdays"`
	Media    string   `json:"media"`
	Volume   int      `json:"volume"`
}

// Template 是一套模板。
type Template struct {
	Label string `json:"label"`
	Items []Item `json:"items"`
}

type catalog struct {
	KindAliases   map[string]string             `json:"kindAliases"`
	SeasonAliases map[string]string             `json:"seasonAliases"`
	Default       struct{ Kind, Season string } `json:"default"`
	Templates     map[string]Template           `json:"templates"`
}

var (
	once    sync.Once
	loaded  catalog
	loadErr error
)

func load() (catalog, error) {
	once.Do(func() {
		loadErr = json.Unmarshal(raw, &loaded)
	})
	return loaded, loadErr
}

// NormalizeKind / NormalizeSeason 把「初中」「暑期」这类说法归一。
// 别名表取自原实现的 manifest。
func NormalizeKind(v string) string { return normalize(v, true) }

// NormalizeSeason 同上，作用于季节。
func NormalizeSeason(v string) string { return normalize(v, false) }

func normalize(v string, kind bool) string {
	v = strings.TrimSpace(v)
	if v == "" {
		return ""
	}
	c, err := load()
	if err != nil {
		return v
	}
	table := c.SeasonAliases
	if kind {
		table = c.KindAliases
	}
	if mapped, ok := table[v]; ok {
		return mapped
	}
	return v
}

// Kinds 列出所有支持的学校类型，界面上的下拉用它。
func Kinds() []string {
	c, err := load()
	if err != nil {
		return nil
	}
	seen := map[string]bool{}
	var out []string
	for key := range c.Templates {
		k, _, ok := splitKey(key)
		if ok && !seen[k] {
			seen[k] = true
			out = append(out, k)
		}
	}
	sortInPlace(out)
	return out
}

// Seasons 列出所有支持的季节。
func Seasons() []string {
	c, err := load()
	if err != nil {
		return nil
	}
	seen := map[string]bool{}
	var out []string
	for key := range c.Templates {
		_, s, ok := splitKey(key)
		if ok && !seen[s] {
			seen[s] = true
			out = append(out, s)
		}
	}
	sortInPlace(out)
	return out
}

// Lookup 按学校类型与季节取一套模板。
func Lookup(kind, season string) (Template, error) {
	c, err := load()
	if err != nil {
		return Template{}, fmt.Errorf("读取作息模板: %w", err)
	}
	k := NormalizeKind(kind)
	s := NormalizeSeason(season)
	t, ok := c.Templates[k+"/"+s]
	if !ok {
		return Template{}, fmt.Errorf("没有「%s%s」的作息模板", k, s)
	}
	return t, nil
}

func splitKey(key string) (string, string, bool) {
	i := strings.Index(key, "/")
	if i <= 0 || i == len(key)-1 {
		return "", "", false
	}
	return key[:i], key[i+1:], true
}

func sortInPlace(list []string) {
	for i := 1; i < len(list); i++ {
		for j := i; j > 0 && list[j] < list[j-1]; j-- {
			list[j], list[j-1] = list[j-1], list[j]
		}
	}
}
