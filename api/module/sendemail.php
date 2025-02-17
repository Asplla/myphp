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
        $response = '';

        // 读取所有响应
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            // 如果行以 <CRLF> 结束，说明响应结束
            if (substr($line, -2) === "\r\n") {
                break;
            }
        }

        // 保存最后的响应用于调试
        $this->error = "Command: $command\nResponse: $response";

        // 获取响应码（前3个字符）
        $code = substr($response, 0, 3);
        return $code == $expectedCode;
    }

    public function send($from, $to, $subject, $body)
    {
        if (!$this->connect()) {
            return false;
        }

        // EHLO command - 读取所有响应直到结束
        fputs($this->socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
        $response = '';
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        if (substr($response, 0, 3) !== '250') {
            $this->error = "EHLO command failed: " . $response;
            return false;
        }

        // AUTH LOGIN
        if (!$this->sendCommand("AUTH LOGIN", '334')) {
            $this->error = "AUTH LOGIN failed: " . $this->error;
            return false;
        }

        // Send username
        if (!$this->sendCommand(base64_encode($this->smtp_user), '334')) {
            $this->error = "Username verification failed: " . $this->error;
            return false;
        }

        // Send password
        if (!$this->sendCommand(base64_encode($this->smtp_pass), '235')) {
            $this->error = "Password verification failed: " . $this->error;
            return false;
        }

        // MAIL FROM
        if (!$this->sendCommand("MAIL FROM:<{$from}>", '250')) {
            $this->error = "MAIL FROM command failed";
            return false;
        }

        // RCPT TO
        if (!$this->sendCommand("RCPT TO:<{$to}>", '250')) {
            $this->error = "RCPT TO command failed";
            return false;
        }

        // DATA
        if (!$this->sendCommand("DATA", '354')) {
            $this->error = "DATA command failed";
            return false;
        }

        // 构建邮件头部
        $headers = "From: {$from}\r\n";
        $headers .= "To: {$to}\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n\r\n";

        // 发送邮件内容
        fputs($this->socket, $headers . base64_encode($body) . "\r\n.\r\n");
        $response = fgets($this->socket, 515);

        // QUIT
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
$message = trim(strip_tags($_POST['message'] ?? ''));

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

// HTML邮件模板
$email_template = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New form submission on Order</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif; background-color: #f6f6f6;">
    <div style="max-width: 600px; margin: 0 auto; padding: 45px 20px;">
        <div style="margin-bottom: 30px; text-align: left;">
            <svg height="20" viewBox="0 0 284 65" fill="var(--geist-foreground)" style="color: #000;">
                <path d="M141.68 16.25c-11.04 0-19 7.2-19 18s8.96 18 20 18c6.67 0 12.55-2.64 16.19-7.09l-7.65-4.42c-2.02 2.21-5.09 3.5-8.54 3.5-4.79 0-8.86-2.5-10.37-6.5h28.02c.22-1.12.35-2.28.35-3.5 0-10.79-7.96-17.99-19-17.99zm-9.46 14.5c1.25-3.99 4.67-6.5 9.45-6.5 4.79 0 8.21 2.51 9.45 6.5h-18.9zm117.14-14.5c-11.04 0-19 7.2-19 18s8.96 18 20 18c6.67 0 12.55-2.64 16.19-7.09l-7.65-4.42c-2.02 2.21-5.09 3.5-8.54 3.5-4.79 0-8.86-2.5-10.37-6.5h28.02c.22-1.12.35-2.28.35-3.5 0-10.79-7.96-17.99-19-17.99zm-9.45 14.5c1.25-3.99 4.67-6.5 9.45-6.5 4.79 0 8.21 2.51 9.45 6.5h-18.9zm-39.03 3.5c0 6 3.92 10 10 10 4.12 0 7.21-1.87 8.8-4.92l7.68 4.43c-3.18 5.3-9.14 8.49-16.48 8.49-11.05 0-19-7.2-19-18s7.96-18 19-18c7.34 0 13.29 3.19 16.48 8.49l-7.68 4.43c-1.59-3.05-4.68-4.92-8.8-4.92-6.07 0-10 4-10 10zm82.48-29v46h-9v-46h9zM37.59.25l36.95 64H.64l36.95-64zm92.38 5l-27.71 48-27.71-48h10.39l17.32 30 17.32-30h10.39zm58.91 12v9.69c-1-.29-2.06-.49-3.2-.49-5.81 0-10 4-10 10v14.8h-9v-34h9v9.2c0-5.08 5.91-9.2 13.2-9.2z"></path>
            </svg>
        </div>
        <div style="background: #fff; border-radius: 5px; padding: 40px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h1 style="margin: 0 0 30px; font-size: 24px; font-weight: 600; color: #000;">New Contact Form Submission</h1>
            
            <div style="margin: 0 0 30px;">
                <p style="margin: 0 0 5px; font-size: 14px; color: #666;">FROM</p>
                <p style="margin: 0; font-size: 16px; color: #000;">{$name}</p>
            </div>
            
            <div style="margin: 0 0 30px;">
                <p style="margin: 0 0 5px; font-size: 14px; color: #666;">EMAIL</p>
                <p style="margin: 0; font-size: 16px; color: #000;">{$email}</p>
            </div>
            
            <div style="margin: 0 0 30px;">
                <p style="margin: 0 0 5px; font-size: 14px; color: #666;">MESSAGE</p>
                <div style="margin: 0; padding: 15px; background: #f6f6f6; border-radius: 5px;">
                    <p style="margin: 0; font-size: 16px; color: #000; white-space: pre-line; line-height: 1.6;">{$message}</p>
                </div>
            </div>
            
            <div style="margin: 40px 0 0; padding-top: 20px; border-top: 1px solid #eaeaea;">
                <p style="margin: 0; font-size: 12px; color: #666;">This email was sent from your contact form. Please do not reply directly to this email.</p>
                <p style="margin: 10px 0 0; font-size: 12px; color: #666;">Sent at: {$current_time}</p>
            </div>
        </div>
        <div style="margin-top: 25px; text-align: center; font-size: 12px; color: #666;">
            <p style="margin: 0;">© {$current_year} Vercel. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;

try {
    // 创建SMTP客户端
    $smtp = new SMTPClient(
        $smtp_config['host'],
        $smtp_config['port'],
        $smtp_config['username'],
        $smtp_config['password']
    );

    // 开发环境启用调试
    if (in_array($origin, ['http://localhost:3000', 'http://localhost'])) {
        $smtp->setDebug(true);
    }

    // 发送邮件
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
