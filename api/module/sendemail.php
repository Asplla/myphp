<?php
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
        $this->socket = fsockopen("ssl://" . $this->smtp_host, $this->smtp_port, $errno, $errstr, 30);
        if (!$this->socket) {
            $this->error = "连接失败: $errstr ($errno)";
            return false;
        }
        $response = fgets($this->socket, 515);
        if (substr($response, 0, 3) != '220') {
            $this->error = "SMTP服务器连接错误: " . $response;
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
        $this->error = "命令: $command\n响应: $response";

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
            $this->error = "EHLO 命令失败: " . $response;
            return false;
        }

        // AUTH LOGIN
        if (!$this->sendCommand("AUTH LOGIN", '334')) {
            $this->error = "AUTH LOGIN 失败: " . $this->error;
            return false;
        }

        // Send username
        if (!$this->sendCommand(base64_encode($this->smtp_user), '334')) {
            $this->error = "用户名验证失败: " . $this->error;
            return false;
        }

        // Send password
        if (!$this->sendCommand(base64_encode($this->smtp_pass), '235')) {
            $this->error = "密码验证失败: " . $this->error;
            return false;
        }

        // MAIL FROM
        if (!$this->sendCommand("MAIL FROM:<{$from}>", '250')) {
            $this->error = "MAIL FROM 命令失败";
            return false;
        }

        // RCPT TO
        if (!$this->sendCommand("RCPT TO:<{$to}>", '250')) {
            $this->error = "RCPT TO 命令失败";
            return false;
        }

        // DATA
        if (!$this->sendCommand("DATA", '354')) {
            $this->error = "DATA 命令失败";
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

// 检查是否是GET请求
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    // 获取URL参数数据
    $name = $_GET['name'] ?? '';
    $email = $_GET['email'] ?? '';
    $message = $_GET['message'] ?? '';

    // HTML邮件模板
    $email_template = <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>新的联系表单消息</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; border: 1px solid #ddd;">
            <h2 style="color: #007bff; margin-bottom: 20px;">新的联系表单消息</h2>
            
            <div style="margin-bottom: 15px;">
                <strong style="color: #495057;">发送者姓名：</strong>
                <p style="margin: 5px 0;">{$name}</p>
            </div>
            
            <div style="margin-bottom: 15px;">
                <strong style="color: #495057;">发送者邮箱：</strong>
                <p style="margin: 5px 0;">{$email}</p>
            </div>
            
            <div style="margin-bottom: 15px;">
                <strong style="color: #495057;">消息内容：</strong>
                <p style="margin: 5px 0; white-space: pre-line; background-color: #fff; padding: 10px; border-radius: 3px;">{$message}</p>
            </div>
            
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #6c757d;">
                <p>此邮件由系统自动发送，请勿直接回复。</p>
                <p>发送时间：" . date('Y-m-d H:i:s') . "</p>
            </div>
        </div>
    </body>
    </html>
    HTML;

    try {
        // SMTP配置
        $smtp = new SMTPClient(
            'smtp.qq.com',  // SMTP服务器地址
            465,                  // SMTP端口
            '76005434@qq.com',  // SMTP用户名
            'axwwaahdiatxbihh'      // SMTP密码
        );

        // 发送邮件
        $result = $smtp->send(
            '76005434@qq.com',     // 发件人
            'wangxu_cn@icloud.com',       // 收件人
            '新的联系表单消息来自: ' . $name,  // 主题
            $email_template                // 内容
        );

        if ($result) {
            echo json_encode([
                'status' => 'success',
                'message' => '邮件发送成功！'
            ]);
        } else {
            throw new Exception($smtp->getError());
        }
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => "邮件发送失败: " . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => '无效的请求方法'
    ]);
}
