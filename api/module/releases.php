<?php
/**
 *	Powered by wxss.fit
 *  Email:minbbs@qq.com
 */

if(!defined('IN_API')) {
	exit('Access Denied');
}

$user = $_GET['user'];
$repo = $_GET['repo'];

if(!$user) {
  return_json('401', 'Please Enter Github Username');
}

if(!$repo) {
  return_json('401', 'Please Enter Github Repository');
}

?>