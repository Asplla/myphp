<?php
if (!defined('IN_API')) {
    exit('Access Denied');
}

// SMTP服务器配置
$smtp_config = array(
    'host' => 'smtp.qq.com',         // SMTP服务器地址
    'port' => 465,                   // SMTP服务器端口
    'user' => '76005434@qq.com',     // SMTP用户名
    'pass' => 'hrtloecdoavfbhjd',    // SMTP密码
    'from' => '76005434@qq.com',     // 发件人邮箱
    'fromName' => '系统邮件'         // 发件人名称
); 