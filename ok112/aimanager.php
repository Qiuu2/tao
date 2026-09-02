<?php
if (!session_id()) session_start();

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');
//验证是否失效
require_once("verify_user_sessionin_valid.php");
$smarty->display("ai_Manager/aipeopletree.html");

?>
