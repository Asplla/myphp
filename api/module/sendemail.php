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

// Initialize SMTP class
$smtp = new SMTP(
    $smtp_config['host'],
    $smtp_config['user'],
    $smtp_config['pass'],
    $smtp_config['port']
);

// Disable debug mode in production
$smtp->setDebug(false);

// Get GET data
$name = isset($_GET['name']) ? trim($_GET['name']) : '';
$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$content = isset($_GET['content']) ? trim($_GET['content']) : '';

// Validate each field
if (empty($name)) {
    return_json('400', 'Please enter your name');
}

if (empty($email)) {
    return_json('400', 'Please enter your email');
}

if (empty($content)) {
    return_json('400', 'Please enter your message');
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return_json('400', 'Invalid email format');
}

// Get current time
$current_time = date('Y-m-d H:i:s');
$current_year = date('Y');

// Build email content
$subject = "New Message from {$name}";
$body = <<<EOT
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Message Notification</title>
    <style>
        @media (prefers-color-scheme: dark) {
            body { background: #000 !important; }
            .email-wrapper { background: #000 !important; }
            .email-content { 
                background: #111 !important; 
                box-shadow: 0 2px 5px rgba(0,0,0,0.2) !important;
            }
            .title { color: #fff !important; }
            .text-primary { color: #fff !important; }
            .text-secondary { color: #888 !important; }
            .message-box { 
                background: #1a1a1a !important;
                border: 1px solid #333 !important;
            }
            .divider { border-color: #333 !important; }
            .vercel-logo { color: #fff !important; }
            a { color: #3291ff !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #fafafa;">
    <div class="email-wrapper" style="max-width: 600px;margin: 0 auto;padding: 45px 20px;">
        <div style="margin-bottom: 30px; text-align: left;">
            <svg class="vercel-logo" height="20" viewBox="0 0 284 65" fill="currentColor" style="color: #000;">
                <path d="M141.68 16.25c-11.04 0-19 7.2-19 18s8.96 18 20 18c6.67 0 12.55-2.64 16.19-7.09l-7.65-4.42c-2.02 2.21-5.09 3.5-8.54 3.5-4.79 0-8.86-2.5-10.37-6.5h28.02c.22-1.12.35-2.28.35-3.5 0-10.79-7.96-17.99-19-17.99zm-9.46 14.5c1.25-3.99 4.67-6.5 9.45-6.5 4.79 0 8.21 2.51 9.45 6.5h-18.9zm117.14-14.5c-11.04 0-19 7.2-19 18s8.96 18 20 18c6.67 0 12.55-2.64 16.19-7.09l-7.65-4.42c-2.02 2.21-5.09 3.5-8.54 3.5-4.79 0-8.86-2.5-10.37-6.5h28.02c.22-1.12.35-2.28.35-3.5 0-10.79-7.96-17.99-19-17.99zm-9.45 14.5c1.25-3.99 4.67-6.5 9.45-6.5 4.79 0 8.21 2.51 9.45 6.5h-18.9zm-39.03 3.5c0 6 3.92 10 10 10 4.12 0 7.21-1.87 8.8-4.92l7.68 4.43c-3.18 5.3-9.14 8.49-16.48 8.49-11.05 0-19-7.2-19-18s7.96-18 19-18c7.34 0 13.29 3.19 16.48 8.49l-7.68 4.43c-1.59-3.05-4.68-4.92-8.8-4.92-6.07 0-10 4-10 10zm82.48-29v46h-9v-46h9zM37.59.25l36.95 64H.64l36.95-64zm92.38 5l-27.71 48-27.71-48h10.39l17.32 30 17.32-30h10.39zm58.91 12v9.69c-1-.29-2.06-.49-3.2-.49-5.81 0-10 4-10 10v14.8h-9v-34h9v9.2c0-5.08 5.91-9.2 13.2-9.2z"></path>
            </svg>
        </div>
        <div class="email-content" style="background: #fff; border-radius: 5px; padding: 40px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h1 class="title" style="margin: 0 0 30px; font-size: 24px; font-weight: 600; color: #000;">New Contact Form Submission</h1>
            
            <div style="margin: 0 0 30px;">
                <p class="text-secondary" style="margin: 0 0 5px; font-size: 14px; color: #666;">FROM</p>
                <p class="text-primary" style="margin: 0; font-size: 16px; color: #000;">{$name}</p>
            </div>
            
            <div style="margin: 0 0 30px;">
                <p class="text-secondary" style="margin: 0 0 5px; font-size: 14px; color: #666;">EMAIL</p>
                <p class="text-primary" style="margin: 0; font-size: 16px; color: #000;"><a href="mailto:{$email}" rel="noopener" target="_blank">{$email}</a></p>
            </div>
            
            <div style="margin: 0 0 30px;">
                <p class="text-secondary" style="margin: 0 0 5px; font-size: 14px; color: #666;">MESSAGE</p>
                <div class="message-box" style="margin: 0; padding: 15px; background: #f6f6f6; border-radius: 5px;">
                    <p class="text-primary" style="margin: 0; font-size: 16px; color: #000; white-space: pre-line; line-height: 1.6;">{$content}</p>
                </div>
            </div>
            
            <div style="margin: 40px 0 0; padding-top: 20px; border-top: 1px solid #eaeaea;" class="divider">
                <p class="text-secondary" style="margin: 0; font-size: 12px; color: #666;">This email was sent from your contact form. Please do not reply directly to this email.</p>
                <p class="text-secondary" style="margin: 10px 0 0; font-size: 12px; color: #666;">Sent at: {$current_time}</p>
            </div>
        </div>
        <div style="margin-top: 25px; text-align: center;">
            <p class="text-secondary" style="margin: 0; font-size: 12px; color: #666;">© {$current_year} WXSS.FIT. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
EOT;

// Send email
$result = $smtp->send(
    'wangxu_cn@icloud.com',    // recipient
    $subject,                  // subject
    $body,                     // content
    $smtp_config['from'],      // sender
    $smtp_config['fromName']   // sender name
);

// Return result
if ($result) {
    return_json('200', 'Message sent successfully');
} else {
    return_json('500', 'Failed to send: ' . $smtp->getError());
}
