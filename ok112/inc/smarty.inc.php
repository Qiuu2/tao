<?php
//****************************
	/*smarty 配置*/
//****************************
require_once("smarty/libs/Smarty.class.php");

$smarty	=	new Smarty();


$smarty->setTemplateDir('smarty/templates/')
		->setCompileDir('smarty/templates_c/')
		->setPluginsDir('smarty/libs/plugins/')
		->setCacheDir('smarty/cache/')
		->setConfigDir('smarty/inc/');
		
$smarty->left_delimiter	=	"<{";
$smarty->right_delimiter	=	"}>";
$smarty->caching	=false;
$smarty->cache_lifetime	=	3600;
?>