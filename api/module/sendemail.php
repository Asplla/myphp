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

// Get GET data for testing
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
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>New Message Notification</title>
    <style>
        :root {
            color-scheme: light dark;
            supported-color-schemes: light dark;
        }
        
        @media (prefers-color-scheme: dark) {
            .body { 
                background-color: #000000 !important;
                color: #ffffff !important;
            }
            .email-wrapper { 
                background-color: #000000 !important;
                color: #ffffff !important;
            }
            .email-content { 
                background-color: #1c1c1e !important; 
                box-shadow: 0 2px 5px rgba(0,0,0,0.2) !important;
                color: #ffffff !important;
            }
            .title { 
                color: #ffffff !important;
            }
            .text-primary { 
                color: #ffffff !important;
            }
            .text-secondary { 
                color: #98989d !important;
            }
            .message-box { 
                background-color: #2c2c2e !important;
                border: 1px solid #333333 !important;
                color: #ffffff !important;
            }
            .divider { 
                border-color: #333333 !important;
            }
            .vercel-logo { 
                color: #ffffff !important;
                fill: #ffffff !important;
            }
            a { 
                color: #0a84ff !important;
            }
            [data-ogsc] .body { 
                background-color: #000000 !important;
                color: #ffffff !important;
            }
            [data-ogsc] .email-wrapper { 
                background-color: #000000 !important;
            }
            [data-ogsc] .email-content { 
                background-color: #1c1c1e !important;
            }
            [data-ogsc] .title { 
                color: #ffffff !important;
            }
            [data-ogsc] .text-primary { 
                color: #ffffff !important;
            }
            [data-ogsc] .text-secondary { 
                color: #98989d !important;
            }
            [data-ogsc] .message-box { 
                background-color: #2c2c2e !important;
                border-color: #333333 !important;
            }
            [data-ogsc] .divider { 
                border-color: #333333 !important;
            }
            [data-ogsc] .vercel-logo { 
                color: #ffffff !important;
                fill: #ffffff !important;
            }
            [data-ogsc] a { 
                color: #0a84ff !important;
            }
        }
    </style>
</head>
<body class="body" style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #fafafa;">
    <div class="email-wrapper" style="max-width: 600px;margin: 0 auto;padding: 45px 20px;">
        <div style="margin-bottom: 30px; text-align: left;">
            <svg class="vercel-logo" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 47.917 7.848" fill="currentColor">
                <path d="M13.405 7.848q-1.645 0-2.425-.762t-.78-2.202q0-1.428.787-2.196.785-.768 2.418-.768 1.643 0 2.43.768.785.768.786 2.196 0 1.44-.78 2.202-.782.762-2.436.762m0-1.512q.67 0 .96-.336.287-.336.288-1.116 0-.78-.288-1.116-.29-.336-.96-.336-.66 0-.949.336t-.288 1.116T12.456 6t.949.336M2.268 7.728v-6.24H0V0h6.468v1.488H4.2v6.24zm5.004 0v-4.26h-.84L6.6 2.04h2.604v5.688zM7.104 0h2.101v1.489H7.104zm12.841 7.848q-1.056 0-1.554-.54t-.498-1.488V3.504h-.769V2.04h.769V.852l1.932-.516V2.04h1.38l-.084 1.464h-1.296v2.184q0 .408.204.57t.612.162q.348 0 .708-.12v1.307q-.565.24-1.404.24m5.195.001q-1.487 0-2.387-.739-.9-.736-.9-2.225 0-1.356.744-2.16.743-.804 2.232-.804 1.355 0 2.1.696.744.696.744 1.872v1.02H23.64q.132.564.617.786.487.222 1.327.222.468 0 .954-.084t.81-.216v1.26a3.6 3.6 0 0 1-.972.282 8 8 0 0 1-1.236.09m-1.5-3.456h2.353V4.14q0-.42-.253-.666-.252-.246-.851-.246-.696 0-.972.282c-.276.282-.276.482-.276.882m8.016 3.456q-1.525 0-2.382-.744-.859-.745-.858-2.22 0-1.5.894-2.232t2.382-.732q.623 0 1.086.09.46.09.893.294v1.368q-.671-.324-1.571-.324-.84 0-1.278.336t-.439 1.2q0 .804.408 1.17.409.366 1.296.366.877 0 1.597-.36v1.427q-.433.194-.93.277a5.4 5.4 0 0 1-1.098.084m3.011-.12V2.04h1.8l.06.492q.348-.24.877-.426a3.4 3.4 0 0 1 1.128-.186q1.103 0 1.607.528c.504.528.504.896.504 1.632v3.648h-1.932V4.236q0-.48-.204-.684t-.731-.204q-.314 0-.637.144a1.8 1.8 0 0 0-.54.36v3.876zm10.032.12q-1.643 0-2.424-.762t-.78-2.202q0-1.428.787-2.196.785-.768 2.418-.768 1.643 0 2.43.768.785.768.786 2.196 0 1.44-.78 2.202-.781.762-2.436.762m0-1.512q.673 0 .96-.336.288-.336.289-1.116 0-.78-.288-1.116-.289-.336-.96-.336-.66 0-.948.336-.289.336-.288 1.116 0 .78.288 1.116.286.336.948.336"/>
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
            <p class="text-secondary" style="margin: 0; font-size: 12px; color: #666;">© {$current_year} Tiotecno. All rights reserved.</p>
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
