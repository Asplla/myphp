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
$subject = "来自 {$name} 的留言";
$body = <<<EOT
<html>
<body>
<h2>访客留言信息：</h2>
<p><strong>姓名：</strong>{$name}</p>
<p><strong>邮箱：</strong>{$email}</p>
<p><strong>留言内容：</strong></p>
<p>{$content}</p>
<hr>
<p><small>此邮件由系统自动发送，请勿直接回复。</small></p>
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
