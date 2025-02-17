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
            body {
                background-color: #1a1a1a !important;
            }
            .container {
                color: #ffffff !important;
            }
            .title {
                color: #ffffff !important;
            }
            .table {
                border-color: #333333 !important;
            }
            .table td {
                border-color: #333333 !important;
            }
            .table-header {
                background: #2a2a2a !important;
                color: #999999 !important;
            }
            .table-content {
                color: #ffffff !important;
            }
            .divider {
                background: #333333 !important;
            }
            .footer-text {
                color: #999999 !important;
            }
            .footer-subtext {
                color: #666666 !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #ffffff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">
    <div class="container" style="max-width: 580px; margin: 0 auto; padding: 45px 30px 40px;">
        <!-- 标题 -->
        <h1 class="title" style="margin: 0 0 30px; text-align: center; color: #111111; font-size: 24px; font-weight: 500;">
            新的访客留言
        </h1>

        <!-- 内容区域 -->
        <div style="margin-bottom: 30px;">
            <table class="table" style="width: 100%; border-spacing: 0; border-collapse: separate; border: 1px solid #eaeaea; border-radius: 5px; overflow: hidden;">
                <tr>
                    <td class="table-header" style="padding: 12px 20px; background: #fafafa; font-size: 13px; color: #666666; width: 100px;">
                        访客姓名
                    </td>
                    <td class="table-content" style="padding: 12px 20px; font-size: 14px; color: #111111;">
                        {$name}
                    </td>
                </tr>
                <tr style="border-top: 1px solid #eaeaea;">
                    <td class="table-header" style="padding: 12px 20px; background: #fafafa; font-size: 13px; color: #666666; width: 100px;">
                        访客邮箱
                    </td>
                    <td class="table-content" style="padding: 12px 20px; font-size: 14px; color: #111111;">
                        {$email}
                    </td>
                </tr>
                <tr style="border-top: 1px solid #eaeaea;">
                    <td class="table-header" style="padding: 12px 20px; background: #fafafa; font-size: 13px; color: #666666; width: 100px;">
                        留言内容
                    </td>
                    <td class="table-content" style="padding: 12px 20px; font-size: 14px; color: #111111; white-space: pre-wrap; line-height: 1.6;">
                        {$content}
                    </td>
                </tr>
            </table>
        </div>

        <!-- 时间信息 -->
        <div class="footer-text" style="margin-bottom: 30px; text-align: center; font-size: 13px; color: #666666;">
            发送时间：" . date('Y-m-d H:i:s') . "
        </div>

        <!-- 分隔线 -->
        <div class="divider" style="height: 1px; background: #eaeaea; margin: 30px 0;"></div>

        <!-- 页脚 -->
        <div style="text-align: center;">
            <img src="https://wxss.fit/logo.png" alt="Logo" style="height: 20px; margin-bottom: 15px;">
            <div class="footer-text" style="font-size: 12px; color: #666666; line-height: 1.5;">
                <div>© " . date('Y') . " WXSS.FIT</div>
                <div class="footer-subtext" style="color: #999999;">此邮件由系统自动发送，请勿直接回复</div>
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
