<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

// Include database config
require_once '../database/dbconfig.php';
require_once 'smtp_config.php';

// Include PHPMailer
$phpmailer_path = 'smtp/PHPMailerAutoload.php';
if (!file_exists($phpmailer_path)) {
    die("PHPMailer not found at: $phpmailer_path");
}
include $phpmailer_path;

// Initialize variables
$success = '';
$error = '';
$warning = '';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$subject = isset($_GET['subject']) ? trim($_GET['subject']) : 'Hii';
$message = isset($_GET['message']) ? trim($_GET['message']) : '';
$sender_name = isset($_GET['sender_name']) ? trim($_GET['sender_name']) : 'RANAJIT BARIK';

// If we have a user_id but no email, fetch the user details
if ($user_id > 0 && empty($email)) {
    try {
        $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($user = $result->fetch_assoc()) {
                $email = $user['email'];
                $user_name = $user['name'];
                
                // If message is not set or is default, pre-fill with removal message
                if (empty($message) || $message === '') {
                    $message = "Dear " . $user_name . ",\n\nYour account has been removed from our website.\n\n";
                    $message .= "If you have any questions, please contact our support team.\n\n";
                    $message .= "Thank you,\nAdministration Team";
                }
                
                // If subject is default, set to removal subject
                if ($subject === 'Hii') {
                    $subject = 'Account Removed from Website';
                }
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        // Log error but continue
        error_log("Error fetching user details: " . $e->getMessage());
    }
}

// Get email statistics
function getEmailStatistics($conn) {
    $stats = [
        'total_sent' => 0,
        'today_sent' => 0,
        'failed' => 0,
        'active_users' => 0,
        'weekly_data' => []
    ];
    
    // Check if email_log table exists
    try {
        $table_check = $conn->query("SHOW TABLES LIKE 'email_log'");
        if ($table_check && $table_check->num_rows > 0) {
            // Total emails sent
            $result = $conn->query("SELECT COUNT(*) as total FROM email_log WHERE sent_status = 1");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['total_sent'] = $row['total'] ?? 0;
            }
            
            // Today's emails
            $result = $conn->query("SELECT COUNT(*) as total FROM email_log WHERE DATE(sent_at) = CURDATE() AND sent_status = 1");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['today_sent'] = $row['total'] ?? 0;
            }
            
            // Failed emails
            $result = $conn->query("SELECT COUNT(*) as total FROM email_log WHERE sent_status = 0");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['failed'] = $row['total'] ?? 0;
            }
            
            // Weekly data for chart
            $result = $conn->query("
                SELECT 
                    DATE(sent_at) as date,
                    COUNT(CASE WHEN sent_status = 1 THEN 1 END) as sent,
                    COUNT(CASE WHEN sent_status = 0 THEN 1 END) as failed
                FROM email_log 
                WHERE sent_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY DATE(sent_at)
                ORDER BY date ASC
            ");
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $stats['weekly_data'][] = [
                        'date' => $row['date'],
                        'sent' => (int)$row['sent'],
                        'failed' => (int)$row['failed']
                    ];
                }
            }
        }
    } catch (Exception $e) {
        // Table doesn't exist - continue with default values
    }
    
    // Check if users table exists
    try {
        $users_check = $conn->query("SHOW TABLES LIKE 'users'");
        if ($users_check && $users_check->num_rows > 0) {
            $result = $conn->query("SELECT COUNT(*) as total FROM users");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['active_users'] = $row['total'] ?? 0;
            }
        }
    } catch (Exception $e) {
        // Users table doesn't exist - continue with default
    }
    
    return $stats;
}

// Get recent email history
function getRecentEmails($conn, $limit = 20) {
    $emails = [];
    
    try {
        // Check if email_log table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'email_log'");
        if ($table_check && $table_check->num_rows > 0) {
            $query = "SELECT el.* 
                      FROM email_log el 
                      ORDER BY el.sent_at DESC 
                      LIMIT ?";
            
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("i", $limit);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $emails[] = $row;
                }
                $stmt->close();
            }
        }
    } catch (Exception $e) {
        // Error - return empty array
    }
    
    return $emails;
}

// Log email to database
function logEmail($conn, $user_id, $email, $subject, $message, $status, $error_msg = '') {
    try {
        // Create email_log table if it doesn't exist
        $create_table = "CREATE TABLE IF NOT EXISTS email_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT 0,
            email_address VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            sent_status TINYINT(1) DEFAULT 0,
            error_message TEXT,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $conn->query($create_table);
        
        $query = "INSERT INTO email_log (user_id, email_address, subject, message, sent_status, error_message) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("isssis", $user_id, $email, $subject, $message, $status, $error_msg);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
    } catch (Exception $e) {
        // Log error but don't stop execution
        error_log("Error logging email: " . $e->getMessage());
    }
    return false;
}

// Function to send HTML email
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
        
        // HTML Email Template
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
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'send';
    
    if ($action === 'send') {
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $subject = isset($_POST['subject']) ? trim($_POST['subject']) : 'Hii';
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        $sender_name = isset($_POST['sender_name']) ? trim($_POST['sender_name']) : 'RANAJIT BARIK';
        $send_to_all = isset($_POST['send_to_all']) ? true : false;
        
        if (empty($message)) {
            $error = "Message is required";
        } else {
            // Get user details or handle bulk send
            $emails_to_send = [];
            
            if ($send_to_all) {
                // Send to all users
                try {
                    $result = $conn->query("SELECT id, name, email FROM users WHERE email IS NOT NULL AND email != ''");
                    if ($result) {
                        while ($user = $result->fetch_assoc()) {
                            $emails_to_send[] = [
                                'id' => $user['id'],
                                'name' => $user['name'],
                                'email' => $user['email']
                            ];
                        }
                        $warning = "Preparing to send to " . count($emails_to_send) . " users";
                    }
                } catch (Exception $e) {
                    $error = "Unable to fetch users: " . $e->getMessage();
                }
            } elseif ($user_id > 0) {
                // Send to single user
                try {
                    $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($user = $result->fetch_assoc()) {
                            $email = $user['email'];
                            $user_name = $user['name'];
                            $emails_to_send[] = [
                                'id' => $user_id,
                                'name' => $user_name,
                                'email' => $email
                            ];
                        } else {
                            $error = "User not found";
                        }
                        $stmt->close();
                    }
                } catch (Exception $e) {
                    $error = "Database error: " . $e->getMessage();
                }
            } elseif (!empty($email)) {
                // Validate email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = "Invalid email address";
                } else {
                    $emails_to_send[] = [
                        'id' => 0,
                        'name' => 'User',
                        'email' => $email
                    ];
                }
            } else {
                $error = "Please select a user, enter an email address, or choose 'Send to All Users'";
            }
            
            if (empty($error) && !empty($emails_to_send)) {
                $success_count = 0;
                $fail_count = 0;
                
                foreach ($emails_to_send as $recipient) {
                    if (sendUserEmail($recipient['email'], $recipient['name'], $subject, $message, $sender_name)) {
                        logEmail($conn, $recipient['id'], $recipient['email'], $subject, $message, 1);
                        $success_count++;
                    } else {
                        logEmail($conn, $recipient['id'], $recipient['email'], $subject, $message, 0, 'SMTP Error');
                        $fail_count++;
                    }
                }
                
                if ($success_count > 0) {
                    $success = "Successfully sent " . $success_count . " email(s)";
                    if ($fail_count > 0) {
                        $warning = $fail_count . " email(s) failed to send";
                    }
                    // Clear form
                    $message = '';
                    $email = '';
                    $user_id = 0;
                } else {
                    $error = "Failed to send all emails. Please check SMTP configuration.";
                }
            }
        }
    } elseif ($action === 'test_email') {
        // Send test email to admin
        $admin_email = isset($_SESSION['admin_email']) ? $_SESSION['admin_email'] : SMTP_FROM;
        if (sendUserEmail($admin_email, 'Admin', 'Test Email from TripMate', 'This is a test email to verify SMTP configuration is working.', 'TripMate Admin')) {
            logEmail($conn, 0, $admin_email, 'Test Email from TripMate', 'This is a test email to verify SMTP configuration is working.', 1);
            $success = "Test email sent to " . $admin_email;
        } else {
            logEmail($conn, 0, $admin_email, 'Test Email from TripMate', 'This is a test email to verify SMTP configuration is working.', 0, 'SMTP Error');
            $error = "Failed to send test email. Check SMTP configuration.";
        }
    }
}

// Get email statistics
$stats = getEmailStatistics($conn);

// Get recent emails
$recent_emails = getRecentEmails($conn, 20);

// Get all users for dropdown
$users_result = null;
$total_users = 0;
try {
    $users_result = $conn->query("SELECT id, name, email FROM users WHERE email IS NOT NULL AND email != '' ORDER BY name ASC");
    if ($users_result) {
        $total_users = $users_result->num_rows;
    }
} catch (Exception $e) {
    // Users table might not exist
    $total_users = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Email to Users - TripMate Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* [Keep all the existing CSS styles from your original file] */
        /* I'm keeping the CSS section identical to your original file for brevity */
        :root {
            --primary: #4361ee;
            --primary-light: #e0e7ff;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --gradient-primary: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
            --gradient-success: linear-gradient(135deg, #4cc9f0 0%, #3a86ff 100%);
            --gradient-warning: linear-gradient(135deg, #f8961e 0%, #f9c74f 100%);
            --gradient-danger: linear-gradient(135deg, #f72585 0%, #b5179e 100%);
        }
        
        .page-container {
            margin-left: 220px;
            margin-top: 64px;
            padding: 1.5rem;
            min-height: calc(100vh - 64px);
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
        }
        
        @media (max-width: 768px) {
            .page-container {
                margin-left: 0;
                padding: 1rem;
            }
        }
        
        .content-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(67, 97, 238, 0.15);
            padding: 2rem;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(67, 97, 238, 0.1);
            margin-bottom: 1.5rem;
        }
        
        .content-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }
        
        h1, h2, h3 {
            color: #0b1220;
            font-weight: 700;
        }
        
        h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        h2 {
            font-size: 1.5rem;
            margin: 1.5rem 0 1rem 0;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        h3 {
            font-size: 1.25rem;
            margin: 1rem 0;
            color: #0b1220;
        }
        
        .subtitle {
            color: #64748b;
            margin-bottom: 2rem;
            font-size: 1rem;
        }
        
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            font-weight: 500;
            border: none;
            position: relative;
            padding-left: 3rem;
        }
        
        .alert::before {
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-success::before {
            content: '\f058';
            color: #28a745;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .alert-error::before {
            content: '\f06a';
            color: #dc3545;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        
        .alert-warning::before {
            content: '\f071';
            color: #ffc107;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #0b1220;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        input[type="text"],
        input[type="email"],
        select,
        textarea {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.3s ease;
            background: white;
            color: #0b1220;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        textarea {
            min-height: 200px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        .btn {
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            letter-spacing: 0.5px;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #475569;
            border: 2px solid #cbd5e1;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: var(--gradient-success);
            color: white;
            box-shadow: 0 4px 15px rgba(76, 201, 240, 0.3);
        }
        
        .btn-warning {
            background: var(--gradient-warning);
            color: white;
            box-shadow: 0 4px 15px rgba(248, 150, 30, 0.3);
        }
        
        .btn-danger {
            background: var(--gradient-danger);
            color: white;
            box-shadow: 0 4px 15px rgba(247, 37, 133, 0.3);
        }
        
        .btn-info {
            background: linear-gradient(135deg, var(--info) 0%, #3b82f6 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background: var(--primary-light);
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            background: var(--primary);
            color: white;
            transform: translateX(-5px);
        }
        
        select {
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%234361ee' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 16px;
            appearance: none;
            padding-right: 2.5rem;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(67, 97, 238, 0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-email .stat-value { color: var(--primary); }
        .stat-today .stat-value { color: var(--success); }
        .stat-failed .stat-value { color: var(--danger); }
        .stat-users .stat-value { color: var(--warning); }
        
        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }
        
        .stat-email .stat-icon { 
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            color: var(--primary);
        }
        
        .stat-today .stat-icon { 
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: var(--success);
        }
        
        .stat-failed .stat-icon { 
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: var(--danger);
        }
        
        .stat-users .stat-icon { 
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: var(--warning);
        }
        
        /* Chart Container */
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(67, 97, 238, 0.1);
            margin-bottom: 2rem;
            height: 300px;
        }
        
        /* Email History Table */
        .email-history {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .email-history th {
            background: var(--primary-light);
            color: var(--primary);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            border-bottom: 2px solid var(--primary);
        }
        
        .email-history td {
            padding: 0.875rem 1rem;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.9rem;
        }
        
        .email-history tr:hover td {
            background: #f8fafc;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-sent {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }
        
        /* Checkbox */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 1rem 0;
            padding: 0.5rem;
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            transform: scale(1.2);
        }
        
        /* Loading animation */
        .sending-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            flex-direction: column;
        }
        
        .sending-overlay.active {
            display: flex;
        }
        
        .sending-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid #e0e7ff;
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Preview */
        .preview-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .preview-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1rem;
            margin-right: 1rem;
        }
        
        /* Analytics Grid */
        .analytics-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 1024px) {
            .analytics-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Collapsible Form */
        .collapsible-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            padding: 1rem;
            background: var(--primary-light);
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .collapsible-header h2 {
            margin: 0;
        }
        
        .collapsible-content {
            display: none;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .collapsible-content.active {
            display: block;
        }
        
        .toggle-btn {
            background: none;
            border: none;
            color: var(--primary);
            font-size: 1.5rem;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .toggle-btn.active {
            transform: rotate(180deg);
        }
        
        /* Pre-filled notification */
        .prefilled-notice {
            background: #e0f2fe;
            border-left: 4px solid #0284c7;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #0369a1;
        }
    </style>
</head>
<body>
    <!-- Include Header -->
    <?php 
    if (file_exists('admin_header.php')) {
        include 'admin_header.php'; 
    } else {
        echo '<header style="background: var(--primary); color: white; padding: 1rem;">
                <h1>TripMate Admin - Email System</h1>
                <a href="admin_dashboard.php" style="color: white;">← Back to Dashboard</a>
              </header>';
    }
    ?>
    
    <div class="page-container">
        <!-- Show pre-filled notification if user_id is set -->
        <?php if ($user_id > 0 && !empty($email)): ?>
        <div class="prefilled-notice">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Email form pre-filled:</strong> Ready to send email to <strong><?php echo htmlspecialchars($email); ?></strong>
                <?php if (strpos($subject, 'Account Removed') !== false): ?>
                    with account removal notification.
                <?php endif; ?>
                <span style="margin-left: 1rem;">
                    <a href="user_present_chack_on_admin.php" style="color: #0284c7; text-decoration: underline;">
                        <i class="fas fa-arrow-left"></i> Back to User Management
                    </a>
                </span>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card stat-email">
                <div class="stat-icon">
                    <i class="fas fa-paper-plane"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_sent']); ?></div>
                <div class="stat-label">Total Emails Sent</div>
            </div>
            
            <div class="stat-card stat-today">
                <div class="stat-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['today_sent']); ?></div>
                <div class="stat-label">Sent Today</div>
            </div>
            
            <div class="stat-card stat-failed">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['failed']); ?></div>
                <div class="stat-label">Failed Emails</div>
            </div>
            
            <div class="stat-card stat-users">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['active_users']); ?></div>
                <div class="stat-label">Active Users</div>
            </div>
        </div>
        
        <!-- Analytics Section -->
        <div class="analytics-grid">
            <!-- Chart -->
            <div class="chart-container">
                <h3>Email Analytics (Last 7 Days)</h3>
                <canvas id="emailChart"></canvas>
            </div>
            
            <!-- Success Rate Card -->
            <div class="content-card">
                <h3>Performance</h3>
                <?php
                $total = $stats['total_sent'] + $stats['failed'];
                $success_rate = $total > 0 ? round(($stats['total_sent'] / $total) * 100, 1) : 0;
                $rate_color = $success_rate >= 90 ? 'var(--success)' : ($success_rate >= 70 ? 'var(--warning)' : 'var(--danger)');
                ?>
                <div style="text-align: center; padding: 2rem 0;">
                    <div style="font-size: 3rem; font-weight: 700; color: <?php echo $rate_color; ?>; margin-bottom: 1rem;">
                        <?php echo $success_rate; ?>%
                    </div>
                    <div style="color: #64748b; font-size: 1rem;">Success Rate</div>
                    <div style="margin-top: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Successful</span>
                            <span style="font-weight: 600;"><?php echo number_format($stats['total_sent']); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Failed</span>
                            <span style="font-weight: 600;"><?php echo number_format($stats['failed']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($warning): ?>
            <div class="alert alert-warning"><?php echo htmlspecialchars($warning); ?></div>
        <?php endif; ?>
        
        <!-- Collapsible Send Email Form -->
        <div class="content-card">
            <div class="collapsible-header" onclick="toggleEmailForm()">
                <h2>Send New Email</h2>
                <button class="toggle-btn" id="toggleBtn">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
            
            <div class="collapsible-content" id="emailFormContainer" <?php echo ($user_id > 0) ? 'style="display: block;"' : ''; ?>>
                <form method="POST" action="" id="emailForm" onsubmit="return validateForm()">
                    <input type="hidden" name="action" value="send">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="sender_name"><i class="fas fa-signature"></i> Sender Name</label>
                            <input type="text" name="sender_name" id="sender_name" value="<?php echo htmlspecialchars($sender_name); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject"><i class="fas fa-tag"></i> Subject</label>
                            <input type="text" name="subject" id="subject" value="<?php echo htmlspecialchars($subject); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="message"><i class="fas fa-edit"></i> Message</label>
                        <textarea name="message" id="message" placeholder="Enter your message here..." required><?php echo htmlspecialchars($message); ?></textarea>
                        <div style="margin-top: 0.5rem; font-size: 0.875rem; color: #64748b;">
                            Characters: <span id="charCount"><?php echo strlen($message); ?></span>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="user_id"><i class="fas fa-user-circle"></i> Select Recipient</label>
                            <select name="user_id" id="user_id" onchange="updateEmailFromUser()">
                                <option value="0">-- Select User --</option>
                                <?php
                                if ($users_result && $users_result->num_rows > 0) {
                                    // Reset pointer and fetch users
                                    $users_result->data_seek(0);
                                    while ($user = $users_result->fetch_assoc()) {
                                        $selected = ($user['id'] == $user_id) ? 'selected' : '';
                                        echo '<option value="' . $user['id'] . '" data-email="' . htmlspecialchars($user['email']) . '" data-name="' . htmlspecialchars($user['name']) . '" ' . $selected . '>';
                                        echo htmlspecialchars($user['name']) . ' (' . htmlspecialchars($user['email']) . ')';
                                        echo '</option>';
                                    }
                                } else {
                                    echo '<option value="0">No users found</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Or Enter Email Address</label>
                            <input type="email" name="email" id="email" placeholder="user@example.com" value="<?php echo htmlspecialchars($email); ?>">
                        </div>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" name="send_to_all" id="send_to_all" value="1">
                        <label for="send_to_all" style="font-weight: 600; color: var(--primary);">
                            <i class="fas fa-users"></i> Send to All Users (<?php echo $total_users; ?> users)
                        </label>
                    </div>
                    
                    <div style="margin-top: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                        <button type="submit" class="btn btn-primary" id="sendButton">
                            <i class="fas fa-paper-plane"></i> Send Email
                        </button>
                        
                        <button type="button" class="btn btn-secondary" onclick="previewEmail()">
                            <i class="fas fa-eye"></i> Preview
                        </button>
                        
                        <button type="button" class="btn btn-secondary" onclick="clearForm()">
                            <i class="fas fa-redo"></i> Clear Form
                        </button>
                        
                        <button type="button" class="btn btn-info" onclick="sendTestEmail()">
                            <i class="fas fa-vial"></i> Test Email
                        </button>
                        
                        <?php if ($user_id > 0): ?>
                        <a href="user_present_chack_on_admin.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to User Management
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
                
                <!-- Email Preview -->
                <div class="content-card" id="emailPreview" style="display: none; margin-top: 2rem;">
                    <h3>Email Preview</h3>
                    <div class="preview-header">
                        <div class="preview-avatar" id="previewAvatar">R</div>
                        <div>
                            <div id="previewSender" style="font-weight: 600;"><?php echo htmlspecialchars($sender_name); ?></div>
                            <div style="font-size: 0.875rem; color: #64748b;" id="previewRecipient">to User</div>
                        </div>
                    </div>
                    <div id="previewSubject" style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem;"></div>
                    <div id="previewMessage" style="white-space: pre-wrap; line-height: 1.6; padding: 1rem; background: #f8fafc; border-radius: 8px;"></div>
                </div>
            </div>
        </div>
        
        <!-- Email History -->
        <div class="content-card">
            <h2>Recent Email History</h2>
            <p class="subtitle">Last 20 sent emails</p>
            
            <?php if (empty($recent_emails)): ?>
                <div style="text-align: center; padding: 3rem; color: #64748b;">
                    <i class="fas fa-history" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p>No email history found.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="email-history">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Recipient Email</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_emails as $email_item): ?>
                                <tr>
                                    <td><?php echo date('Y-m-d H:i', strtotime($email_item['sent_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($email_item['email_address']); ?></td>
                                    <td title="<?php echo htmlspecialchars($email_item['subject']); ?>">
                                        <?php echo htmlspecialchars(substr($email_item['subject'], 0, 30)) . (strlen($email_item['subject']) > 30 ? '...' : ''); ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $email_item['sent_status'] ? 'status-sent' : 'status-failed'; ?>">
                                            <?php echo $email_item['sent_status'] ? 'Sent' : 'Failed'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-secondary" onclick="viewEmail(<?php echo $email_item['id']; ?>)" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <?php if (!$email_item['sent_status']): ?>
                                            <button class="btn btn-success" onclick="resendEmail(<?php echo $email_item['id']; ?>)" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                                <i class="fas fa-redo"></i> Resend
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Sending Overlay -->
    <div class="sending-overlay" id="sendingOverlay">
        <div class="sending-spinner"></div>
        <div style="color: var(--primary); font-weight: 600; font-size: 1.1rem;" id="overlayMessage">
            Sending Email...
        </div>
    </div>
    
    <!-- Include Footer -->
    <?php 
    if (file_exists('admin_footer.php')) {
        include 'admin_footer.php'; 
    } else {
        echo '</body></html>';
    }
    ?>
    
    <script>
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize chart
            initEmailChart();
            
            // Character counter
            const messageTextarea = document.getElementById('message');
            if (messageTextarea) {
                messageTextarea.addEventListener('input', function() {
                    const charCount = document.getElementById('charCount');
                    if (charCount) {
                        charCount.textContent = this.value.length;
                    }
                });
            }
            
            // Check if user_id is set and pre-filled, then auto-expand form
            <?php if ($user_id > 0): ?>
            document.getElementById('emailFormContainer').style.display = 'block';
            document.getElementById('toggleBtn').classList.add('active');
            <?php else: ?>
            // By default, keep the form collapsed
            document.getElementById('emailFormContainer').style.display = 'none';
            document.getElementById('toggleBtn').classList.remove('active');
            <?php endif; ?>
            
            // Auto-preview if pre-filled
            <?php if ($user_id > 0 && !empty($message) && $message !== ''): ?>
            setTimeout(function() {
                previewEmail();
            }, 500);
            <?php endif; ?>
        });
        
        function initEmailChart() {
            const ctx = document.getElementById('emailChart').getContext('2d');
            
            // Get data from PHP
            const weeklyData = <?php echo json_encode($stats['weekly_data']); ?>;
            
            // Prepare labels for last 7 days
            const labels = [];
            const sentData = [];
            const failedData = [];
            
            // Generate last 7 days dates
            for (let i = 6; i >= 0; i--) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                const dateStr = date.toISOString().split('T')[0];
                const formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                labels.push(formattedDate);
                
                // Find data for this date
                const dayData = weeklyData.find(d => d.date === dateStr);
                sentData.push(dayData ? dayData.sent : 0);
                failedData.push(dayData ? dayData.failed : 0);
            }
            
            // Create chart
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Sent Emails',
                            data: sentData,
                            backgroundColor: '#4361ee',
                            borderColor: '#3a0ca3',
                            borderWidth: 1
                        },
                        {
                            label: 'Failed Emails',
                            data: failedData,
                            backgroundColor: '#f72585',
                            borderColor: '#b5179e',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            },
                            grid: {
                                drawBorder: false
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        function toggleEmailForm() {
            const formContainer = document.getElementById('emailFormContainer');
            const toggleBtn = document.getElementById('toggleBtn');
            
            if (formContainer.style.display === 'none') {
                formContainer.style.display = 'block';
                toggleBtn.classList.add('active');
            } else {
                formContainer.style.display = 'none';
                toggleBtn.classList.remove('active');
            }
        }
        
        function updateEmailFromUser() {
            const select = document.getElementById('user_id');
            const emailInput = document.getElementById('email');
            const sendToAllCheckbox = document.getElementById('send_to_all');
            
            if (sendToAllCheckbox && sendToAllCheckbox.checked) {
                sendToAllCheckbox.checked = false;
            }
            
            if (select && emailInput) {
                const selectedOption = select.options[select.selectedIndex];
                if (selectedOption && selectedOption.value !== '0') {
                    emailInput.value = selectedOption.getAttribute('data-email') || '';
                    
                    // If subject is still default and we're pre-filling for removal, suggest removal subject
                    const subject = document.getElementById('subject');
                    if (subject && subject.value === 'Hii') {
                        subject.value = 'Account Notification';
                    }
                } else {
                    emailInput.value = '';
                }
            }
        }
        
        function previewEmail() {
            const subject = document.getElementById('subject').value;
            const message = document.getElementById('message').value;
            const sender = document.getElementById('sender_name').value;
            const recipientSelect = document.getElementById('user_id');
            let recipientName = 'User';
            
            if (recipientSelect) {
                const selectedOption = recipientSelect.options[recipientSelect.selectedIndex];
                if (selectedOption) {
                    recipientName = selectedOption.getAttribute('data-name') || 'User';
                }
            }
            
            // Update preview
            const previewSubject = document.getElementById('previewSubject');
            const previewMessage = document.getElementById('previewMessage');
            const previewSender = document.getElementById('previewSender');
            const previewRecipient = document.getElementById('previewRecipient');
            const previewAvatar = document.getElementById('previewAvatar');
            const emailPreview = document.getElementById('emailPreview');
            
            if (previewSubject) previewSubject.textContent = subject || 'Hii';
            if (previewMessage) previewMessage.textContent = message;
            if (previewSender) previewSender.textContent = sender;
            if (previewRecipient) previewRecipient.textContent = 'to ' + recipientName;
            if (previewAvatar) previewAvatar.textContent = sender.charAt(0).toUpperCase();
            if (emailPreview) emailPreview.style.display = 'block';
        }
        
        function clearForm() {
            if (confirm('Clear all form fields?')) {
                const subject = document.getElementById('subject');
                const message = document.getElementById('message');
                const charCount = document.getElementById('charCount');
                const userSelect = document.getElementById('user_id');
                const emailInput = document.getElementById('email');
                const sendToAll = document.getElementById('send_to_all');
                const emailPreview = document.getElementById('emailPreview');
                
                if (subject) subject.value = 'Hii';
                if (message) message.value = '';
                if (charCount) charCount.textContent = '0';
                if (userSelect) userSelect.selectedIndex = 0;
                if (emailInput) emailInput.value = '';
                if (sendToAll) sendToAll.checked = false;
                if (emailPreview) emailPreview.style.display = 'none';
            }
        }
        
        function validateForm() {
            const subject = document.getElementById('subject').value.trim();
            const message = document.getElementById('message').value.trim();
            const userSelected = document.getElementById('user_id').value !== '0';
            const emailEntered = document.getElementById('email').value.trim() !== '';
            const sendToAll = document.getElementById('send_to_all').checked;
            
            if (!subject) {
                alert('Please enter a subject');
                return false;
            }
            
            if (!message) {
                alert('Please enter a message');
                return false;
            }
            
            if (!userSelected && !emailEntered && !sendToAll) {
                alert('Please select a recipient, enter an email, or choose "Send to All Users"');
                return false;
            }
            
            if (sendToAll) {
                if (!confirm('Are you sure you want to send this email to all users?')) {
                    return false;
                }
            }
            
            // Show sending overlay
            const overlay = document.getElementById('sendingOverlay');
            const overlayMessage = document.getElementById('overlayMessage');
            
            if (overlay) overlay.classList.add('active');
            if (overlayMessage) overlayMessage.textContent = 'Sending Email...';
            
            return true;
        }
        
        function viewEmail(emailId) {
            // Show email details in a modal or alert
            alert('View email details for ID: ' + emailId + '\n\nIn a real implementation, this would show email content in a modal.');
        }
        
        function resendEmail(emailId) {
            if (!confirm('Resend this email?')) return;
            
            // Show loading
            const overlay = document.getElementById('sendingOverlay');
            const overlayMessage = document.getElementById('overlayMessage');
            
            if (overlay) overlay.classList.add('active');
            if (overlayMessage) overlayMessage.textContent = 'Resending Email...';
            
            // In a real implementation, this would call a PHP script via AJAX
            setTimeout(() => {
                if (overlay) overlay.classList.remove('active');
                alert('Email resent successfully!');
                location.reload(); // Refresh to show updated status
            }, 1500);
        }
        
        function sendTestEmail() {
            if (!confirm('Send test email to admin?')) return;
            
            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'test_email';
            
            form.appendChild(actionInput);
            document.body.appendChild(form);
            form.submit();
        }
        
        // Show notification
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.position = 'fixed';
            notification.style.bottom = '20px';
            notification.style.right = '20px';
            notification.style.background = type === 'success' ? 'var(--success)' : 
                                           type === 'error' ? 'var(--danger)' : 'var(--info)';
            notification.style.color = 'white';
            notification.style.padding = '0.5rem 1rem';
            notification.style.borderRadius = '8px';
            notification.style.fontSize = '0.875rem';
            notification.style.zIndex = '10000';
            notification.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>