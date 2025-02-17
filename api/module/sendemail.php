<?php
require_once(dirname(dirname(__FILE__)) . '/class/smtp.class.php');
require_once(dirname(dirname(__FILE__)) . '/config/smtp.config.php');

/**
 *	Powered by wxss.fit
 *  Email:minbbs@qq.com
 */

if (!defined('IN_API')) {
    exit('Access Denied');
}

// 初始化SMTP类
$smtp = new SMTP(
    $smtp_config['host'],
    $smtp_config['user'],
    $smtp_config['pass'],
    $smtp_config['port']
);

// 开启调试模式（正式环境可以关闭）
$smtp->setDebug(true);

// 测试发送一封邮件来验证连接
$test_result = $smtp->send(
    $smtp_config['user'],          // 测试发送给自己
    'SMTP连接测试',                // 测试主题
    '如果您收到这封邮件，说明SMTP配置正确。', // 测试内容
    $smtp_config['from'],          // 发件人
    $smtp_config['fromName']       // 发件人名称
);

if ($test_result) {
    echo "SMTP连接成功，邮件发送测试通过！";
} else {
    echo "SMTP连接失败，错误信息：" . $smtp->getError();
}
