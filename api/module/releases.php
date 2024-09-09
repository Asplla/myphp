<?php
/**
 *	Powered by wxss.fit
 *  Email:minbbs@qq.com
 */

if(!defined('IN_API')) {
	exit('Access Denied');
}

$user = trim($_GET['user'] ?? '');
$repo = trim($_GET['repo'] ?? '');

if(!$user) {
  return_json('401', 'Please Enter Github Username');
}

if(!$repo) {
  return_json('401', 'Please Enter Github Repository');
}

$url = "https://api.github.com/repos/".$user."/".$repo."/releases/latest";

$response = curl_get($url);
$data = json_decode($response, true);

$info = array(
  'version' => $data['tag_name'],
);
return_json('200', 'success', $info);

?>