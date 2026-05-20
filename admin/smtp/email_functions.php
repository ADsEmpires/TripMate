<?php
/**
 * Email Functions for TripMate
 * Reusable functions for sending HTML emails via SMTP
 */

// Include PHPMailer
require_once __DIR__ . '/PHPMailerAutoload.php';

// Include SMTP config (go up one level from smtp/ to admin/)
require_once dirname(__DIR__) . '/smtp_config.php';

/**
 * Send HTML email to user matching the design template
 * 
 * @param string $toEmail Recipient email address
 * @param string $toName Recipient name
 * @param string $subject Email subject
 * @param string $message Email message body
 * @param string $senderName Sender name (default: RANAJIT BARIK)
 * @return bool True on success, false on failure
 */
function sendUserEmail($toEmail, $toName, $subject, $message, $senderName = 'RANAJIT BARIK') {
    $mail = new PHPMailer(true);
    
    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = SMTP_AUTH;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        
        // Sender
        $mail->setFrom(SMTP_FROM, $senderName);
        $mail->addReplyTo(SMTP_FROM, $senderName);
        
        // Recipient
        $mail->addAddress($toEmail, $toName);
        
        // Email format
        $mail->isHTML(true);
        $mail->Subject = $subject;
        
        // HTML Email Template matching the design
        $senderInitial = strtoupper(substr($senderName, 0, 1));
        $htmlBody = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . htmlspecialchars($subject) . '</title>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                    background-color: #ffffff;
                    color: #202124;
                }
                .email-container {
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: #ffffff;
                    padding: 20px;
                }
                .email-header {
                    margin-bottom: 20px;
                }
                .email-subject {
                    font-size: 24px;
                    font-weight: 700;
                    color: #202124;
                    margin: 0 0 10px 0;
                }
                .sender-info {
                    display: flex;
                    align-items: center;
                    margin-bottom: 20px;
                    padding: 15px 0;
                    border-bottom: 1px solid #e8eaed;
                }
                .profile-picture {
                    width: 48px;
                    height: 48px;
                    border-radius: 50%;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-weight: bold;
                    font-size: 18px;
                    margin-right: 12px;
                    flex-shrink: 0;
                }
                .sender-details {
                    flex: 1;
                }
                .sender-name {
                    font-size: 14px;
                    font-weight: 600;
                    color: #202124;
                    margin: 0 0 4px 0;
                }
                .recipient-info {
                    font-size: 13px;
                    color: #5f6368;
                    margin: 0;
                }
                .email-body {
                    padding: 20px 0;
                    font-size: 14px;
                    line-height: 1.6;
                    color: #202124;
                    white-space: pre-wrap;
                }
                .email-footer {
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 1px solid #e8eaed;
                    text-align: center;
                }
                .footer-text {
                    font-size: 12px;
                    color: #5f6368;
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    <h1 class="email-subject">' . htmlspecialchars($subject) . '</h1>
                </div>
                
                <div class="sender-info">
                    <div class="profile-picture">' . htmlspecialchars($senderInitial) . '</div>
                    <div class="sender-details">
                        <div class="sender-name">' . htmlspecialchars($senderName) . '</div>
                        <div class="recipient-info">to ' . htmlspecialchars($toName) . '</div>
                    </div>
                </div>
                
                <div class="email-body">
' . nl2br(htmlspecialchars($message)) . '
                </div>
                
                <div class="email-footer">
                    <p class="footer-text">This email was sent from TripMate Admin Panel</p>
                </div>
            </div>
        </body>
        </html>';
        
        // Plain text version
        $plainText = strip_tags($message);
        
        $mail->Body = $htmlBody;
        $mail->AltBody = $plainText;
        
        // SMTP Options for local testing
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ];
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send bulk emails to multiple users
 * 
 * @param array $recipients Array of arrays with 'email' and 'name' keys
 * @param string $subject Email subject
 * @param string $message Email message body
 * @param string $senderName Sender name
 * @return array Results with 'success' and 'failed' arrays
 */
function sendBulkEmails($recipients, $subject, $message, $senderName = 'RANAJIT BARIK') {
    $results = [
        'success' => [],
        'failed' => []
    ];
    
    foreach ($recipients as $recipient) {
        $email = $recipient['email'];
        $name = isset($recipient['name']) ? $recipient['name'] : 'User';
        
        if (sendUserEmail($email, $name, $subject, $message, $senderName)) {
            $results['success'][] = $email;
        } else {
            $results['failed'][] = $email;
        }
    }
    
    return $results;
}
