package openapi

import (
	"context"

	"htweb/internal/i18n"
)

// Localize 把整份目录里**给人看的**字段按请求语言翻一遍。
//
// # 为什么是走一遍结构，而不是在每个字面量上包一层 i18n.TC
//
// 这份目录有六百多条文案，散在 groupQuery / groupTask / … 几个构造函数里。
// 逐条包 TC 要给每个构造函数都加 ctx，改动面大、还得靠人记得新加的那条
// 也要包 —— 漏一条不会报错，只会在英文界面上留一句中文。
//
// 走一遍结构的写法只有一处，新增接口时**不用做任何事**就跟着翻。
//
// # 哪些字段不翻
//
// Path / Method / ID / Type / Example / Body / CodeValue.Value / CodeTable.Field
// —— 它们是代码、路径、JSON 和字段名，翻了就是错的。
func Localize(ctx context.Context, s *Spec) {
	if i18n.From(ctx) == i18n.ZH {
		return
	}
	tr := func(v *string) { *v = i18n.TC(ctx, *v) }

	tr(&s.Title)
	for gi := range s.Groups {
		g := &s.Groups[gi]
		tr(&g.Name)
		tr(&g.Desc)
		for ei := range g.Endpoints {
			ep := &g.Endpoints[ei]
			tr(&ep.Summary)
			tr(&ep.Desc)
			tr(&ep.Right)
			// 响应示例里的**演示名字**（媒体名、终端名、任务名）不该翻 ——
			// 它们是用户的数据长什么样的示例。但里面还混着系统词汇
			// （终端型号、状态文字、报错原文），那几个不翻就是在骗人：
			// 英文模式下接口真的会返回英文。所以整段示例作为一条词条走字典 ——
			// 只有含系统词汇的那几段配了译文，其余的查不到、原样留着。
			tr(&ep.Sample)
			for i := range ep.Params {
				tr(&ep.Params[i].Desc)
			}
			for i := range ep.Fields {
				tr(&ep.Fields[i].Desc)
			}
			for i := range ep.Returns {
				tr(&ep.Returns[i].Desc)
			}
			for i := range ep.Examples {
				tr(&ep.Examples[i].Title)
				tr(&ep.Examples[i].Desc)
			}
			for i := range ep.Notes {
				tr(&ep.Notes[i])
			}
		}
	}
	for ci := range s.Codes {
		c := &s.Codes[ci]
		tr(&c.Title)
		tr(&c.Desc)
		for i := range c.Values {
			tr(&c.Values[i].Means)
		}
	}
}
