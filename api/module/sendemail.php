<?php
require_once __DIR__ . '/../function/function_common.php';  // 引入公共函数文件

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

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return_json(405, 'Method not allowed. Only POST is accepted.');
}

// 获取并清理POST数据
$name = trim(strip_tags($_POST['name'] ?? ''));
$email = trim(strip_tags($_POST['email'] ?? ''));
$content = trim(strip_tags($_POST['content'] ?? ''));

// 输入验证
$errors = [];
if (empty($name)) $errors[] = "姓名不能为空";
if (empty($email)) $errors[] = "邮箱不能为空";
if (empty($content)) $errors[] = "内容不能为空";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "邮箱格式不正确";

if (!empty($errors)) {
    return_json(400, '验证失败', ['errors' => $errors]);
}

// QQ邮箱SMTP配置
$smtp = [
    'host' => 'smtp.qq.com',
    'port' => 465,
    'username' => '你的QQ邮箱@qq.com', // 替换成你的QQ邮箱
    'password' => '你的授权码',  // 替换成你的QQ邮箱SMTP授权码
    'from_email' => '你的QQ邮箱@qq.com', // 替换成你的QQ邮箱
    'to_email' => 'wangxu_cn@icloud.com'
];

// 构建邮件内容
$subject = "新的联系表单消息 - 来自 {$name}";
$message = "
姓名: {$name}
邮箱: {$email}
内容: 
{$content}

发送时间: " . date('Y-m-d H:i:s');

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
        $subject,
        $message
    );

    if ($result) {
        return_json(200, '邮件发送成功');
    } else {
        throw new Exception($mail->getError());
    }
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

    public function __construct($host, $port, $user, $pass)
    {
        $this->smtp_host = $host;
        $this->smtp_port = $port;
        $this->smtp_user = $user;
        $this->smtp_pass = $pass;
    }

    private function connect()
    {
        $this->socket = @fsockopen("ssl://" . $this->smtp_host, $this->smtp_port, $errno, $errstr, 30);
        if (!$this->socket) {
            $this->error = "连接失败: $errstr ($errno)";
            return false;
        }
        $response = fgets($this->socket, 515);
        return substr($response, 0, 3) == '220';
    }

    private function sendCommand($command, $expectedCode)
    {
        fputs($this->socket, $command . "\r\n");
        $response = fgets($this->socket, 515);
        return substr($response, 0, 3) == $expectedCode;
    }

    public function send($from, $to, $subject, $body)
    {
        if (!$this->connect()) {
            return false;
        }

        if (
            !$this->sendCommand("EHLO " . $_SERVER['HTTP_HOST'], '250') ||
            !$this->sendCommand("AUTH LOGIN", '334') ||
            !$this->sendCommand(base64_encode($this->smtp_user), '334') ||
            !$this->sendCommand(base64_encode($this->smtp_pass), '235') ||
            !$this->sendCommand("MAIL FROM:<{$from}>", '250') ||
            !$this->sendCommand("RCPT TO:<{$to}>", '250') ||
            !$this->sendCommand("DATA", '354')
        ) {
            return false;
        }

        $headers = "From: {$from}\r\n";
        $headers .= "To: {$to}\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";

        fputs($this->socket, $headers . $body . "\r\n.\r\n");
        $response = fgets($this->socket, 515);

        $this->sendCommand("QUIT", '221');
        fclose($this->socket);

        return substr($response, 0, 3) == '250';
    }

    public function getError()
    {
        return $this->error;
    }
}
