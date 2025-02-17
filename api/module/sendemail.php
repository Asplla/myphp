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

// 在正式环境中关闭调试模式
$smtp->setDebug(false);

// 接收GET数据
$name = isset($_GET['name']) ? trim($_GET['name']) : '';
$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$content = isset($_GET['content']) ? trim($_GET['content']) : '';

// 分别验证每个字段
if (empty($name)) {
    return_json('400', '请填写姓名');
}

if (empty($email)) {
    return_json('400', '请填写邮箱');
}

if (empty($content)) {
    return_json('400', '请填写留言内容');
}

// 验证邮箱格式
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return_json('400', '邮箱格式不正确');
}

// 构建邮件内容
$subject = "新的访客留言 - {$name}";
$body = <<<EOT
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>访客留言通知</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif; background-color: #f6f6f6;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: white; border-radius: 5px; padding: 20px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
            <!-- Logo 区域 -->
            <div style="text-align: center; margin-bottom: 20px;">
                <img src="https://wxss.fit/logo.png" alt="Logo" style="height: 40px;">
            </div>
            
            <!-- 标题 -->
            <h1 style="color: #000; font-size: 24px; font-weight: 600; margin: 0 0 20px;">新的访客留言</h1>
            
            <!-- 内容区域 -->
            <div style="background: #f9f9f9; border-radius: 5px; padding: 20px; margin-bottom: 20px;">
                <div style="margin-bottom: 15px;">
                    <p style="color: #666; font-size: 14px; margin: 0 0 5px;">访客姓名</p>
                    <p style="color: #000; font-size: 16px; margin: 0;">{$name}</p>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <p style="color: #666; font-size: 14px; margin: 0 0 5px;">访客邮箱</p>
                    <p style="color: #000; font-size: 16px; margin: 0;">{$email}</p>
                </div>
                
                <div>
                    <p style="color: #666; font-size: 14px; margin: 0 0 5px;">留言内容</p>
                    <p style="color: #000; font-size: 16px; margin: 0; white-space: pre-wrap;">{$content}</p>
                </div>
            </div>
            
            <!-- 时间戳 -->
            <p style="color: #666; font-size: 14px; margin: 0; text-align: center;">
                此邮件由系统自动发送于 " . date('Y-m-d H:i:s') . "
            </p>
        </div>
        
        <!-- 页脚 -->
        <div style="text-align: center; margin-top: 20px; color: #666; font-size: 12px;">
            <p style="margin: 5px 0;">© " . date('Y') . " WXSS.FIT. All rights reserved.</p>
            <p style="margin: 5px 0;">此邮件由系统自动发送，请勿直接回复</p>
        </div>
    </div>
</body>
</html>
EOT;

// 发送邮件
$result = $smtp->send(
    'wangxu_cn@icloud.com',    // 收件人
    $subject,                  // 主题
    $body,                     // 内容
    $smtp_config['from'],      // 发件人
    $smtp_config['fromName']   // 发件人名称
);

// 返回结果
if ($result) {
    return_json('200', '留言已成功发送');
} else {
    return_json('500', '发送失败：' . $smtp->getError());
}
