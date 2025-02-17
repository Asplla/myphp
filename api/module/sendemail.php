<?php
// 关闭错误报告，防止敏感信息泄露
error_reporting(0);
ini_set('display_errors', 0);

// 清除之前的输出缓冲
if (ob_get_level()) ob_end_clean();

// 加载配置文件
$smtp_config = require __DIR__ . '/../config/smtp_config.php';

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
    return_json('405', 'Method not allowed. Only POST is accepted.');
}

class SMTPClient
{
    private $smtp_host;
    private $smtp_port;
    private $smtp_user;
    private $smtp_pass;
    private $socket;
    private $error;
    private $debug = false;

    public function __construct($host, $port, $user, $pass)
    {
        $this->smtp_host = $host;
        $this->smtp_port = $port;
        $this->smtp_user = $user;
        $this->smtp_pass = $pass;
    }

    public function setDebug($debug)
    {
        $this->debug = $debug;
        return $this;
    }

    private function log($message)
    {
        if ($this->debug) {
            error_log("[SMTP] " . $message);
        }
    }

    private function connect()
    {
        $this->socket = @fsockopen("ssl://" . $this->smtp_host, $this->smtp_port, $errno, $errstr, 30);
        if (!$this->socket) {
            $this->error = "Connection failed: $errstr ($errno)";
            $this->log($this->error);
            return false;
        }
        $response = fgets($this->socket, 515);
        if (substr($response, 0, 3) != '220') {
            $this->error = "SMTP server connection error: " . $response;
            $this->log($this->error);
            return false;
        }
        return true;
    }

    private function sendCommand($command, $expectedCode)
    {
        fputs($this->socket, $command . "\r\n");
        $response = fgets($this->socket, 515);
        $this->log("Command: $command\nResponse: $response");
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

        // 构建邮件头部
        $headers = "From: {$from}\r\n";
        $headers .= "To: {$to}\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n\r\n";

        fputs($this->socket, $headers . base64_encode($body) . "\r\n.\r\n");
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

// 获取并清理POST数据
$name = trim(strip_tags($_POST['name'] ?? ''));
$email = trim(strip_tags($_POST['email'] ?? ''));
$message = trim(strip_tags($_POST['content'] ?? ''));

// 输入验证
$errors = [];
if (empty($name)) $errors[] = "Name is required";
if (empty($email)) $errors[] = "Email is required";
if (empty($message)) $errors[] = "Message is required";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
if (strlen($message) > 1000) $errors[] = "Message is too long (max 1000 characters)";

if (!empty($errors)) {
    return_json('400', 'Validation failed', ['errors' => $errors]);
}

// 获取当前时间和年份
$current_time = date('F j, Y h:i A');
$current_year = date('Y');

// 简化的HTML邮件模板
$email_template = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Contact Form Message</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1>New Contact Form Message</h1>
        <div style="margin: 20px 0;">
            <p><strong>From:</strong> {$name}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Message:</strong></p>
            <div style="background: #f5f5f5; padding: 15px; border-radius: 5px;">
                {$message}
            </div>
        </div>
        <div style="color: #666; font-size: 12px;">
            <p>Sent at: {$current_time}</p>
        </div>
    </div>
</body>
</html>
HTML;

try {
    $smtp = new SMTPClient(
        $smtp_config['host'],
        $smtp_config['port'],
        $smtp_config['username'],
        $smtp_config['password']
    );

    if ($_SERVER['SERVER_NAME'] === 'localhost') {
        $smtp->setDebug(true);
    }

    $result = $smtp->send(
        $smtp_config['from_email'],
        $smtp_config['to_email'],
        'New Contact Form Message from ' . $name,
        $email_template
    );

    if ($result) {
        return_json('200', 'Email sent successfully');
    } else {
        throw new Exception($smtp->getError());
    }
} catch (Exception $e) {
    error_log("Email sending failed: " . $e->getMessage());
    return_json('500', 'Failed to send email. Please try again later.');
}
