<?php
/**
 *	Powered by wxss.fit
 *  Email:minbbs@qq.com
 */

define('IN_API', true);
require_once "function/function_common.php";

$mod = trim($_GET['mod'] ? $_GET['mod'] : '');
$mod = in_array($mod, array('user')) ? $mod : "index";

require_once "module/".$mod.".php";

?>
