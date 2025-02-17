<?php
/**
 * SMTP邮件发送类
 */
class SMTP {
    private $smtp_port;    //SMTP端口
    private $smtp_host;    //SMTP服务器
    private $smtp_user;    //SMTP用户名
    private $smtp_pass;    //SMTP密码
    private $smtp_debug = false; //是否开启调试模式
    private $smtp_conn;    //SMTP连接句柄
    private $smtp_error;   //错误信息

    /**
     * 构造函数
     * @param string $host SMTP服务器
     * @param string $user SMTP用户名
     * @param string $pass SMTP密码
     * @param int $port SMTP端口
     */
    public function __construct($host = '', $user = '', $pass = '', $port = 25) {
        $this->smtp_host = $host;
        $this->smtp_user = $user;
        $this->smtp_pass = $pass;
        $this->smtp_port = $port;
    }

    /**
     * 发送邮件
     * @param string $to 收件人
     * @param string $subject 主题
     * @param string $body 邮件内容
     * @param string $from 发件人
     * @param string $fromName 发件人名称
     * @return bool
     */
    public function send($to, $subject, $body, $from, $fromName = '') {
        if (!$this->connect()) {
            return false;
        }

        if (!$this->authenticate()) {
            return false;
        }

        if (!$this->sendFrom($from, $fromName)) {
            return false;
        }

        if (!$this->sendTo($to)) {
            return false;
        }

        if (!$this->sendData($subject, $body)) {
            return false;
        }

        $this->quit();
        return true;
    }

    /**
     * 连接SMTP服务器
     * @return bool
     */
    private function connect() {
        $this->smtp_conn = fsockopen($this->smtp_host, $this->smtp_port, $errno, $errstr, 30);
        if (!$this->smtp_conn) {
            $this->smtp_error = "连接SMTP服务器失败: $errstr ($errno)";
            return false;
        }

        $response = fgets($this->smtp_conn, 515);
        if ($this->smtp_debug) {
            echo "SERVER -> CLIENT: " . $response . "\n";
        }

        return $this->checkResponse($response, 220);
    }

    /**
     * SMTP身份验证
     * @return bool
     */
    private function authenticate() {
        // 发送HELO命令
        fputs($this->smtp_conn, "HELO " . $this->smtp_host . "\r\n");
        if (!$this->checkResponse(fgets($this->smtp_conn, 515), 250)) {
            return false;
        }

        // 发送AUTH LOGIN命令
        fputs($this->smtp_conn, "AUTH LOGIN\r\n");
        if (!$this->checkResponse(fgets($this->smtp_conn, 515), 334)) {
            return false;
        }

        // 发送用户名
        fputs($this->smtp_conn, base64_encode($this->smtp_user) . "\r\n");
        if (!$this->checkResponse(fgets($this->smtp_conn, 515), 334)) {
            return false;
        }

        // 发送密码
        fputs($this->smtp_conn, base64_encode($this->smtp_pass) . "\r\n");
        if (!$this->checkResponse(fgets($this->smtp_conn, 515), 235)) {
            return false;
        }

        return true;
    }

    /**
     * 发送发件人信息
     * @param string $from 发件人邮箱
     * @param string $fromName 发件人名称
     * @return bool
     */
    private function sendFrom($from, $fromName = '') {
        $from_str = empty($fromName) ? $from : "$fromName <$from>";
        fputs($this->smtp_conn, "MAIL FROM:<$from>\r\n");
        return $this->checkResponse(fgets($this->smtp_conn, 515), 250);
    }

    /**
     * 发送收件人信息
     * @param string $to 收件人邮箱
     * @return bool
     */
    private function sendTo($to) {
        fputs($this->smtp_conn, "RCPT TO:<$to>\r\n");
        return $this->checkResponse(fgets($this->smtp_conn, 515), 250);
    }

    /**
     * 发送邮件数据
     * @param string $subject 主题
     * @param string $body 邮件内容
     * @return bool
     */
    private function sendData($subject, $body) {
        fputs($this->smtp_conn, "DATA\r\n");
        if (!$this->checkResponse(fgets($this->smtp_conn, 515), 354)) {
            return false;
        }

        $message = "Subject: $subject\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "\r\n";
        $message .= $body;
        $message .= "\r\n.\r\n";

        fputs($this->smtp_conn, $message);
        return $this->checkResponse(fgets($this->smtp_conn, 515), 250);
    }

    /**
     * 检查服务器响应
     * @param string $response 服务器响应
     * @param int $code 预期响应代码
     * @return bool
     */
    private function checkResponse($response, $code) {
        if ($this->smtp_debug) {
            echo "SERVER -> CLIENT: " . $response . "\n";
        }

        if (substr($response, 0, 3) != $code) {
            $this->smtp_error = "SMTP错误: " . $response;
            return false;
        }
        return true;
    }

    /**
     * 断开SMTP连接
     */
    private function quit() {
        fputs($this->smtp_conn, "QUIT\r\n");
        fclose($this->smtp_conn);
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError() {
        return $this->smtp_error;
    }

    /**
     * 设置是否开启调试模式
     * @param bool $debug
     */
    public function setDebug($debug) {
        $this->smtp_debug = $debug;
    }
}
?> 