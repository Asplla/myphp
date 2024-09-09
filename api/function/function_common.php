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

?>