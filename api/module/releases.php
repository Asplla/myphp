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

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  return_json('401', $err);
}

$data = json_decode($response, true);
return_json('200', 'success', $data);

?>