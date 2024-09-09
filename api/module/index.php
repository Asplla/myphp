<?php
/**
 *	Powered by wxss.fit
 *  Email:minbbs@qq.com
 */

if(!defined('IN_API')) {
	exit('Access Denied');
}

$mod = 
$json = array(
  'time' => time(),
  'date' => date('Y-m-d'),
  'tech' => 'wxhub api'
);
echo json_encode($json);

?>