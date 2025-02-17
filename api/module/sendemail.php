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
    <style>
        @media (prefers-color-scheme: dark) {
            .email-wrapper { background-color: #000000 !important; }
            .email-content { background-color: #1c1c1e !important; }
            .text-primary { color: #ffffff !important; }
            .text-secondary { color: #98989d !important; }
            .message-box { background-color: #2c2c2e !important; border-color: #2c2c2e !important; }
            .message-content { color: #ffffff !important; }
            .divider { background-color: #2c2c2e !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">
    <div class="email-wrapper" style="background-color: #f5f5f7; min-height: 100vh; padding: 20px;">
        <div class="email-content" style="max-width: 520px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; padding: 40px; box-sizing: border-box;">
            <!-- Logo -->
            <div style="text-align: center; margin-bottom: 32px;">
                <img src="https://wxss.fit/logo.png" alt="Logo" style="height: 24px;">
            </div>

            <!-- 标题 -->
            <h1 class="text-primary" style="margin: 0 0 24px; color: #1d1d1f; font-size: 20px; font-weight: 600; text-align: center;">
                收到新的访客留言
            </h1>

            <!-- 留言信息 -->
            <div class="message-box" style="background-color: #f5f5f7; border-radius: 8px; padding: 24px; margin-bottom: 24px;">
                <div style="margin-bottom: 16px;">
                    <div class="text-secondary" style="font-size: 13px; color: #86868b; margin-bottom: 4px;">访客姓名</div>
                    <div class="text-primary message-content" style="font-size: 15px; color: #1d1d1f;">{$name}</div>
                </div>
                
                <div style="margin-bottom: 16px;">
                    <div class="text-secondary" style="font-size: 13px; color: #86868b; margin-bottom: 4px;">访客邮箱</div>
                    <div class="text-primary message-content" style="font-size: 15px; color: #1d1d1f;">{$email}</div>
                </div>

                <div>
                    <div class="text-secondary" style="font-size: 13px; color: #86868b; margin-bottom: 4px;">留言内容</div>
                    <div class="text-primary message-content" style="font-size: 15px; color: #1d1d1f; line-height: 1.6; white-space: pre-wrap;">{$content}</div>
                </div>
            </div>

            <!-- 时间信息 -->
            <div class="text-secondary" style="text-align: center; font-size: 13px; color: #86868b; margin-bottom: 24px;">
                发送时间：" . date('Y-m-d H:i:s') . "
            </div>

            <!-- 分隔线 -->
            <div class="divider" style="height: 1px; background-color: #f5f5f7; margin: 24px 0;"></div>

            <!-- 页脚 -->
            <div style="text-align: center;">
                <div class="text-secondary" style="font-size: 12px; color: #86868b; line-height: 1.5;">
                    <div>© " . date('Y') . " WXSS.FIT</div>
                    <div style="color: #98989d;">此邮件由系统自动发送，请勿直接回复</div>
                </div>
            </div>
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
