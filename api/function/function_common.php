<?php
/**
 *	Powered by wxss.fit
 *  Email:minbbs@qq.com
 */

if(!defined('IN_API')) {
	exit('Access Denied');
}

// 输出 Json
function return_json($code, $msg, $data = '') {
  //if($code != 0){
  //  header('HTTP/1.1 400 Bad Request');
  //}
  $json_arr = array(
    'code' => $code,
    'msg' => $msg
  );
  if($data){
    $json_arr['data'] = $data;
  }
  echo json_encode($json_arr);
  exit;
}

// post
function curl_post($url, $postData) {
  // 初始化cURL会话
  $ch = curl_init();
  
  // 设置cURL选项
  curl_setopt($ch, CURLOPT_URL, $url); // URL
  curl_setopt($log, CURLOPT_POST, true); // POST请求
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData)); // POST数据
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 返回响应，而不直接输出

  // 执行cURL会话
  $response = curl_exec($ch);
  
  // 关闭cURL会话
  curl_close($ch);
  
  // 返回响应内容
  return $response;
}
// get
function curl_get($url) {
  // 初始化cURL会话
  $ch = curl_init(); 

  // 设置cURL选项
  curl_setopt($ch, CURLOPT_URL, $url); // URL
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 将响应作为字符串返回
  curl_setopt($ch, CURLOPT_HEADER, false); // 不需要头部信息

  // 执行GET请求，获取响应内容
  $response = curl_exec($ch);

  // 关闭cURL会话
  curl_close($ch);

  // 返回获取的内容
  return $response;
}
?>