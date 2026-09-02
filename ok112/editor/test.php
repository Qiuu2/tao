<?php

// I18N 程序范例开始
define('PACKAGE', 'test'); // 定义要用的mo文件名称，常规来说，我们都把PACKAGE的名称定义和程序名称相同。
putenv('LANG=zh_CN');
setlocale(LC_ALL, 'zh_CN'); // 指定要用的语系，如：en_US、zh_CN、zh_TW
bindtextdomain(PACKAGE, 'L:/locallanguage');
textdomain(PACKAGE);
// The .mo file searched is:
// e:/phpbulo.com/language/zh_CN/LC_MESSAGES/hello.mo
echo gettext("Hello World!");
?>
