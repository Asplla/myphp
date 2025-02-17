<?php

/**
 *	Powered by wxss.fit
 *  Email:minbbs@qq.com
 */

if (!defined('IN_API')) {
    exit('Access Denied');
}

// 设置CORS - 允许所有来源
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Vary: Origin');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 获取并清理GET数据
$name = trim(strip_tags($_GET['name'] ?? ''));
$email = trim(strip_tags($_GET['email'] ?? ''));
$content = trim(strip_tags($_GET['content'] ?? ''));

// 输入验证
if (empty($name)) {
    return_json(400, '请输入姓名');
}

if (empty($email)) {
    return_json(400, '请输入邮箱');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return_json(400, '邮箱格式不正确');
}

if (empty($content)) {
    return_json(400, '请输入留言内容');
}

if (strlen($content) > 1000) {
    return_json(400, '留言内容请限制在1000字以内');
}

// QQ邮箱SMTP配置
$smtp = [
    'host' => 'smtp.qq.com',
    'port' => 465,
    'username' => '76005434@qq.com', // 替换成你的QQ邮箱
    'password' => 'ptrpywfowdxzbgec',  // 替换成你的QQ邮箱SMTP授权码
    'from_email' => '76005434@qq.com', // 替换成你的QQ邮箱
    'to_email' => 'wangxu_cn@icloud.com'
];

try {
    $mail = new SMTPClient(
        $smtp['host'],
        $smtp['port'],
        $smtp['username'],
        $smtp['password']
    );

    $result = $mail->send(
        $smtp['from_email'],
        $smtp['to_email'],
        "新的联系表单消息 - 来自 {$name}",
        "姓名: {$name}\n邮箱: {$email}\n内容: \n{$content}\n\n发送时间: " . date('Y-m-d H:i:s')
    );

    if (!$result) {
        throw new Exception($mail->getError() ?: '邮件发送失败');
    }

    return_json(200, '邮件发送成功');
} catch (Exception $e) {
    return_json(500, '邮件发送失败：' . $e->getMessage());
}

class SMTPClient
{
    private $smtp_host;
    private $smtp_port;
    private $smtp_user;
    private $smtp_pass;
    private $socket;
    private $error;
    private $debug = true;  // 开启调试模式

    public function __construct($host, $port, $user, $pass)
    {
        $this->smtp_host = $host;
        $this->smtp_port = $port;
        $this->smtp_user = $user;
        $this->smtp_pass = $pass;
    }

    private function log($message)
    {
        if ($this->debug) {
            error_log("[SMTP Debug] " . $message);
        }
    }

    private function connect()
    {
        $this->socket = @fsockopen("ssl://" . $this->smtp_host, $this->smtp_port, $errno, $errstr, 30);
        if (!$this->socket) {
            $this->error = "连接失败: $errstr ($errno)";
            $this->log($this->error);
            return false;
        }
        $response = fgets($this->socket, 515);
        $this->log("连接响应: " . $response);

        if (substr($response, 0, 3) != '220') {
            $this->error = "SMTP服务器连接错误: " . $response;
            $this->log($this->error);
            return false;
        }
        return true;
    }

    private function sendCommand($command, $expectedCode)
    {
        $this->log("发送命令: " . $command);
        fputs($this->socket, $command . "\r\n");
        $response = fgets($this->socket, 515);
        $this->log("服务器响应: " . $response);

        if (substr($response, 0, 3) != $expectedCode) {
            $this->error = "命令失败 ($command): " . $response;
            return false;
        }
        return true;
    }

    public function send($from, $to, $subject, $body)
    {
        if (!$this->connect()) {
            return false;
        }

        // 记录发送过程
        $this->log("开始发送邮件...");
        $this->log("发件人: " . $from);
        $this->log("收件人: " . $to);

        if (
            !$this->sendCommand("EHLO " . $_SERVER['HTTP_HOST'], '250') ||
            !$this->sendCommand("AUTH LOGIN", '334') ||
            !$this->sendCommand(base64_encode($this->smtp_user), '334') ||
            !$this->sendCommand(base64_encode($this->smtp_pass), '235') ||
            !$this->sendCommand("MAIL FROM:<{$from}>", '250') ||
            !$this->sendCommand("RCPT TO:<{$to}>", '250') ||
            !$this->sendCommand("DATA", '354')
        ) {
            $this->log("SMTP命令序列失败");
            return false;
        }

        $headers = "From: {$from}\r\n";
        $headers .= "To: {$to}\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";

        $this->log("发送邮件内容...");
        fputs($this->socket, $headers . $body . "\r\n.\r\n");
        $response = fgets($this->socket, 515);
        $this->log("发送完成响应: " . $response);

        $this->sendCommand("QUIT", '221');
        fclose($this->socket);

        if (substr($response, 0, 3) != '250') {
            $this->error = "发送失败: " . $response;
            return false;
        }

        $this->log("邮件发送成功");
        return true;
    }

    public function getError()
    {
        return $this->error ?: '未知错误';
    }
}
