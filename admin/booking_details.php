<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

require_once '../database/dbconfig.php';
require_once 'smtp_config.php'; // <-- ADDED: Use your centralized SMTP config

// Ensure $conn is available
if (!isset($conn)) {
    if (isset($mysqli)) {
        $conn = $mysqli;
    } elseif (isset($db)) {
        $conn = $db;
    } elseif (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die("Database connection failed: " . $conn->connect_error);
        }
    } else {
        die("Database connection variable \$conn not found in dbconfig.php.");
    }
}

// PDO CONNECTION (Same as messages.php)
$host = 'localhost';
$dbname = 'tripmate';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$booking_id) {
    header('Location: bookings.php');
    exit();
}

// Fetch booking details - FIXED: NULL as user_phone
$query = "SELECT b.*, u.name as user_name, u.email as user_email, NULL as user_phone,
          d.name as destination_name, d.location as destination_location, d.description as destination_description,
          d.image_urls as destination_image
          FROM bookings b
          LEFT JOIN users u ON b.user_id = u.id
          LEFT JOIN destinations d ON b.destination_id = d.id
          WHERE b.id = ?";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Database Error in booking selection: " . $conn->error);
}
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: bookings.php');
    exit();
}

$booking = $result->fetch_assoc();

// Fetch payment history
$payments_query = "SELECT * FROM booking_payments WHERE booking_id = ? ORDER BY payment_date DESC";
$payments_stmt = $conn->prepare($payments_query);
$payments = [];
if ($payments_stmt) {
    $payments_stmt->bind_param("i", $booking_id);
    $payments_stmt->execute();
    $payments = $payments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ============================================
// EMAIL SENDING FUNCTION - USES YOUR smtp_config.php
// ============================================
function sendBookingStatusEmail($booking, $oldStatus, $newStatus, $pdo) {
    $to_email = $booking['user_email'];
    $to_name = $booking['user_name'] ?? 'Valued Customer';
    $user_id = $booking['user_id'] ?? null;

    if (empty($to_email)) {
        return ['sent' => false, 'error' => 'User email not found'];
    }

    // Parse booking details JSON
    $details = [];
    if (!empty($booking['booking_details'])) {
        $details = json_decode($booking['booking_details'], true) ?? [];
    }

    // Determine email subject based on status
    $statusLabels = [
        'pending' => 'Your Booking is Pending',
        'confirmed' => 'Your Booking Has Been Confirmed!',
        'completed' => 'Your Trip Has Been Completed!',
        'cancelled' => 'Your Booking Has Been Cancelled'
    ];
    $subject = $statusLabels[$newStatus] ?? 'Booking Status Update';

    // Build HTML Email Body
    $htmlBody = buildBookingEmailHTML($booking, $details, $newStatus, $oldStatus);

    // Build Plain Text Alternative
    $plainBody = buildBookingEmailPlain($booking, $details, $newStatus, $oldStatus);

    $emailSent = false;
    $errorMsg = '';

    // ============================================
    // METHOD 1: Use PHPMailer with smtp_config.php constants
    // ============================================
    $phpmailerPaths = [
        __DIR__ . '/smtp/PHPMailerAutoload.php',
        __DIR__ . '/smtp/class.phpmailer.php',
    ];

    foreach ($phpmailerPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }

    if (class_exists('PHPMailer')) {
        try {
            $mail = new PHPMailer(true);

            // USE SMTP CONFIG FROM smtp_config.php
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;        // From smtp_config.php
            $mail->SMTPAuth   = SMTP_AUTH;        // From smtp_config.php
            $mail->Username   = SMTP_USERNAME;    // From smtp_config.php
            $mail->Password   = SMTP_PASSWORD;    // From smtp_config.php
            $mail->SMTPSecure = SMTP_SECURE;      // From smtp_config.php
            $mail->Port       = SMTP_PORT;        // From smtp_config.php
            $mail->CharSet    = 'UTF-8';

            // Disable SSL verification for local XAMPP testing
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true
                ]
            ];

            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);  // From smtp_config.php
            $mail->addReplyTo(SMTP_REPLY_TO, SMTP_FROM_NAME);
            $mail->addAddress($to_email, $to_name);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $plainBody;

            $emailSent = $mail->send();
        } catch (phpmailerException $e) {
            $errorMsg = $e->errorMessage();
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
        }
    }

    // ============================================
    // METHOD 2: Fallback to PHP mail()
    // ============================================
    if (!$emailSent) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">" . "\r\n";
        $headers .= "Reply-To: " . SMTP_REPLY_TO . "\r\n";

        $emailSent = @mail($to_email, $subject, $htmlBody, $headers);
        if (!$emailSent) {
            $errorMsg = 'PHP mail() failed. Check SMTP configuration in smtp_config.php';
        }
    }

    // ============================================
    // LOG EMAIL TO MESSAGES TABLE
    // ============================================
    if ($user_id && $pdo) {
        try {
            $log_stmt = $pdo->prepare("INSERT INTO messages (user_id, subject, message, message_type, status, created_at) VALUES (?, ?, ?, 'outgoing', 'sent', NOW())");
            $log_stmt->execute([$user_id, $subject, $plainBody]);
        } catch (PDOException $e) {
            // Silently fail logging
        }
    }

    return ['sent' => $emailSent, 'error' => $errorMsg];
}

// ============================================
// BUILD HTML EMAIL TEMPLATE - String concatenation (no HEREDOC issues)
// ============================================
function buildBookingEmailHTML($booking, $details, $status, $oldStatus) {
    $statusColors = [
        'pending' => '#f59e0b',
        'confirmed' => '#10b981',
        'completed' => '#3b82f6',
        'cancelled' => '#ef4444'
    ];
    $statusColor = $statusColors[$status] ?? '#6b7280';
    $statusLabel = ucfirst($status);

    $bookingRef = htmlspecialchars($booking['id']);
    $userName = htmlspecialchars($booking['user_name'] ?? 'Valued Customer');
    $destination = htmlspecialchars($booking['destination_name'] ?? ($details['destination_name'] ?? 'N/A'));
    $location = htmlspecialchars($booking['destination_location'] ?? 'N/A');

    $checkin = !empty($booking['start_date']) ? date('F j, Y', strtotime($booking['start_date'])) :
               (!empty($details['checkin_date']) ? date('F j, Y', strtotime($details['checkin_date'])) : 'N/A');
    $checkout = !empty($booking['end_date']) ? date('F j, Y', strtotime($booking['end_date'])) :
                (!empty($details['checkout_date']) ? date('F j, Y', strtotime($details['checkout_date'])) : 'N/A');

    $travelers = htmlspecialchars($booking['number_of_people'] ?? ($details['travelers'] ?? 'N/A'));
    $rooms = isset($details['rooms']) ? htmlspecialchars($details['rooms']) : 'N/A';
    $totalAmount = number_format($booking['total_amount'] ?? 0, 2);

    $flightId = isset($details['flight_id']) && $details['flight_id'] > 0 ? '#' . htmlspecialchars($details['flight_id']) : 'N/A';
    $hotelId = isset($details['hotel_id']) && $details['hotel_id'] > 0 ? '#' . htmlspecialchars($details['hotel_id']) : 'N/A';

    $paymentStatus = ucfirst(htmlspecialchars($booking['payment_status'] ?? 'Pending'));
    $paymentColor = ($booking['payment_status'] ?? '') === 'paid' ? '#10b981' : '#f59e0b';
    $transactionId = !empty($booking['transaction_id']) ? htmlspecialchars($booking['transaction_id']) : 'N/A';
    $paymentMethod = !empty($booking['payment_method']) ? htmlspecialchars($booking['payment_method']) : 'N/A';

    $bookingType = ucfirst(htmlspecialchars($booking['booking_type'] ?? 'Standard'));
    $createdAt = isset($booking['created_at']) ? date('F j, Y', strtotime($booking['created_at'])) : 'N/A';

    $statusMessages = [
        'pending' => 'Your booking is currently under review. We will update you once it is confirmed.',
        'confirmed' => 'Great news! Your booking has been confirmed. Please prepare for your upcoming trip!',
        'completed' => 'Thank you for traveling with us! We hope you had a wonderful experience.',
        'cancelled' => 'We regret to inform you that your booking has been cancelled. Please contact us for more details.'
    ];
    $statusMessage = $statusMessages[$status] ?? 'Your booking status has been updated.';

    return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Status Update</title>
</head>
<body style="margin: 0; padding: 0; font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif; background-color: #f3f4f6;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f3f4f6; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">TripMate</h1>
                            <p style="color: rgba(255,255,255,0.9); margin: 5px 0 0 0; font-size: 14px;">Your Travel Partner</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: ' . $statusColor . '; padding: 20px; text-align: center;">
                            <h2 style="color: #ffffff; margin: 0; font-size: 20px;">Booking ' . $statusLabel . '</h2>
                            <p style="color: rgba(255,255,255,0.9); margin: 5px 0 0 0; font-size: 14px;">' . $statusMessage . '</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 30px 30px 10px 30px;">
                            <p style="color: #374151; font-size: 16px; margin: 0;">Dear <strong>' . $userName . '</strong>,</p>
                            <p style="color: #6b7280; font-size: 14px; margin-top: 10px; line-height: 1.6;">
                                Your booking status has been updated from <strong style="color: #6b7280;">' . $oldStatus . '</strong> to <strong style="color: ' . $statusColor . ';">' . $statusLabel . '</strong>.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 30px;">
                            <div style="background-color: #f9fafb; border-left: 4px solid ' . $statusColor . '; padding: 15px; border-radius: 8px;">
                                <p style="margin: 0; color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Booking Reference</p>
                                <p style="margin: 5px 0 0 0; color: #111827; font-size: 24px; font-weight: bold;">#' . $bookingRef . '</p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 30px;">
                            <h3 style="color: #111827; font-size: 16px; margin: 0 0 15px 0; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;">📋 Booking Details</h3>
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr><td style="padding: 8px 0; width: 40%; color: #6b7280; font-size: 14px;">Destination</td><td style="padding: 8px 0; color: #111827; font-size: 14px; font-weight: 600;">' . $destination . '</td></tr>
                                <tr><td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Location</td><td style="padding: 8px 0; color: #111827; font-size: 14px;">' . $location . '</td></tr>
                                <tr><td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Booking Type</td><td style="padding: 8px 0; color: #111827; font-size: 14px;">' . $bookingType . '</td></tr>
                                <tr><td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Check-in Date</td><td style="padding: 8px 0; color: #111827; font-size: 14px; font-weight: 600;">' . $checkin . '</td></tr>
                                <tr><td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Check-out Date</td><td style="padding: 8px 0; color: #111827; font-size: 14px; font-weight: 600;">' . $checkout . '</td></tr>
                                <tr><td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Travelers / Guests</td><td style="padding: 8px 0; color: #111827; font-size: 14px;">' . $travelers . ' person(s)</td></tr>
                                <tr><td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Rooms Booked</td><td style="padding: 8px 0; color: #111827; font-size: 14px;">' . $rooms . '</td></tr>
                                <tr><td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Flight ID</td><td style="padding: 8px 0; color: #111827; font-size: 14px;">' . $flightId . '</td></tr>
                                <tr><td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Hotel ID</td><td style="padding: 8px 0; color: #111827; font-size: 14px;">' . $hotelId . '</td></tr>
                                <tr><td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Booked On</td><td style="padding: 8px 0; color: #111827; font-size: 14px;">' . $createdAt . '</td></tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 30px 20px 30px;">
                            <h3 style="color: #111827; font-size: 16px; margin: 0 0 15px 0; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;">💳 Payment Details</h3>
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="padding: 8px 0; width: 40%; color: #6b7280; font-size: 14px;">Payment Status</td>
                                    <td style="padding: 8px 0;">
                                        <span style="background-color: ' . $paymentColor . '20; color: ' . $paymentColor . '; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; border: 1px solid ' . $paymentColor . ';">' . $paymentStatus . '</span>
                                    </td>
                                </tr>
                                <tr><td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Payment Method</td><td style="padding: 8px 0; color: #111827; font-size: 14px;">' . $paymentMethod . '</td></tr>
                                <tr><td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Transaction ID</td><td style="padding: 8px 0; color: #111827; font-size: 14px; font-family: monospace;">' . $transactionId . '</td></tr>
                                <tr><td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Total Amount</td><td style="padding: 8px 0; color: #10b981; font-size: 20px; font-weight: bold;">$' . $totalAmount . '</td></tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 30px 20px 30px;">
                            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 12px; text-align: center;">
                                <p style="margin: 0; color: rgba(255,255,255,0.8); font-size: 14px;">Total Amount</p>
                                <p style="margin: 5px 0 0 0; color: #ffffff; font-size: 28px; font-weight: bold;">$' . $totalAmount . '</p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="color: #6b7280; font-size: 12px; margin: 0;">
                                If you have any questions, please contact our support team.<br>
                                <strong>Email:</strong> support@tripmate.com | <strong>Phone:</strong> +1 (555) 123-4567
                            </p>
                            <p style="color: #9ca3af; font-size: 11px; margin-top: 15px;">
                                This is an automated email from TripMate Booking System.<br>
                                Please do not reply to this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

// ============================================
// BUILD PLAIN TEXT EMAIL
// ============================================
function buildBookingEmailPlain($booking, $details, $status, $oldStatus) {
    $userName = $booking['user_name'] ?? 'Valued Customer';
    $destination = $booking['destination_name'] ?? ($details['destination_name'] ?? 'N/A');
    $checkin = !empty($booking['start_date']) ? date('F j, Y', strtotime($booking['start_date'])) :
               (!empty($details['checkin_date']) ? date('F j, Y', strtotime($details['checkin_date'])) : 'N/A');
    $checkout = !empty($booking['end_date']) ? date('F j, Y', strtotime($booking['end_date'])) :
                (!empty($details['checkout_date']) ? date('F j, Y', strtotime($details['checkout_date'])) : 'N/A');
    $totalAmount = number_format($booking['total_amount'] ?? 0, 2);
    $paymentStatus = ucfirst($booking['payment_status'] ?? 'Pending');
    $paymentMethod = $booking['payment_method'] ?? 'N/A';
    $transactionId = $booking['transaction_id'] ?? 'N/A';

    return "Dear {$userName},\n\n"
        . "Your booking status has been updated from {$oldStatus} to " . ucfirst($status) . ".\n\n"
        . "BOOKING REFERENCE: #{$booking['id']}\n"
        . "Destination: {$destination}\n"
        . "Check-in: {$checkin}\n"
        . "Check-out: {$checkout}\n"
        . "Travelers: " . ($booking['number_of_people'] ?? 'N/A') . "\n"
        . "Flight ID: " . (isset($details['flight_id']) ? '#' . $details['flight_id'] : 'N/A') . "\n"
        . "Hotel ID: " . (isset($details['hotel_id']) ? '#' . $details['hotel_id'] : 'N/A') . "\n"
        . "Payment Status: {$paymentStatus}\n"
        . "Payment Method: {$paymentMethod}\n"
        . "Transaction ID: {$transactionId}\n"
        . "Total Amount: \${$totalAmount}\n\n"
        . "Thank you for choosing TripMate!\n"
        . "For support, contact: support@tripmate.com";
}

// ============================================
// HANDLE STATUS UPDATES WITH EMAIL NOTIFICATION
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $new_status = $_POST['status'] ?? '';
        $admin_notes = $_POST['admin_notes'] ?? '';
        $old_status = $booking['booking_status'] ?? 'pending';

        $update_stmt = $conn->prepare("UPDATE bookings SET booking_status = ?, admin_notes = ? WHERE id = ?");
        if ($update_stmt) {
            $update_stmt->bind_param("ssi", $new_status, $admin_notes, $booking_id);
            if ($update_stmt->execute()) {
                $success_message = "Booking status updated successfully!";
                $booking['booking_status'] = $new_status;
                $booking['admin_notes'] = $admin_notes;

                // Send email notification
                if (!empty($booking['user_email'])) {
                    $emailResult = sendBookingStatusEmail($booking, $old_status, $new_status, $pdo);
                    if ($emailResult['sent']) {
                        $success_message .= " Email notification sent to " . htmlspecialchars($booking['user_email']) . ".";
                    } else {
                        $success_message .= " (Email failed: " . htmlspecialchars($emailResult['error']) . ")";
                    }
                }
            }
        }
    }

    if ($action === 'update_payment') {
        $payment_status = $_POST['payment_status'] ?? '';
        $transaction_id = $_POST['transaction_id'] ?? '';
        $payment_method = $_POST['payment_method'] ?? '';

        $update_stmt = $conn->prepare("UPDATE bookings SET payment_status = ?, transaction_id = ?, payment_method = ? WHERE id = ?");
        if ($update_stmt) {
            $update_stmt->bind_param("sssi", $payment_status, $transaction_id, $payment_method, $booking_id);
            if ($update_stmt->execute()) {
                $success_message = "Payment status updated successfully!";
                $booking['payment_status'] = $payment_status;
                $booking['transaction_id'] = $transaction_id;
                $booking['payment_method'] = $payment_method;

                // Send email notification for payment update
                if (!empty($booking['user_email'])) {
                    $emailResult = sendBookingStatusEmail($booking, $booking['booking_status'] ?? 'pending', $booking['booking_status'] ?? 'pending', $pdo);
                    if ($emailResult['sent']) {
                        $success_message .= " Payment email sent to " . htmlspecialchars($booking['user_email']) . ".";
                    }
                }
            }
        }
    }
}

include 'admin_header.php';
?>

<style>
.booking-details-wrapper { padding: 2rem; max-width: 1200px; margin: 0 auto; }
.booking-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 2rem; padding: 1.5rem; border-radius: 12px;
    background: var(--bg-surface);
    border: 1px solid var(--card-border);
    box-shadow: 0 4px 6px -1px var(--shadow-color);
}
.booking-card {
    background: var(--bg-surface);
    border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem;
    border: 1px solid var(--card-border);
    box-shadow: 0 4px 6px -1px var(--shadow-color);
}
.booking-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; }
@media (max-width: 992px) { .booking-layout { grid-template-columns: 1fr; } }
.booking-title h1 { font-size: 1.5rem; color: var(--text-main); margin: 0 0 0.25rem 0; }
.booking-id { color: var(--text-muted); font-size: 0.9rem; font-weight: 500; }
.card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--card-border); }
.card-title { font-size: 1.15rem; font-weight: 600; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem; margin: 0; }
.info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; }
.info-item { display: flex; flex-direction: column; gap: 0.4rem; }
.info-label { font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
.info-value { font-size: 1rem; color: var(--text-main); font-weight: 500; }
.status-display { display: inline-flex; align-items: center; padding: 0.4rem 1rem; border-radius: 9999px; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
.status-pending { background: rgba(245, 158, 11, 0.1); color: var(--warning); border: 1px solid var(--warning); }
.status-confirmed, .payment-paid { background: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid var(--success); }
.status-completed { background: rgba(59, 130, 246, 0.1); color: var(--info); border: 1px solid var(--info); }
.status-cancelled, .payment-cancelled { background: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid var(--danger); }
.payment-refunded { background: rgba(99, 102, 241, 0.1); color: var(--primary); border: 1px solid var(--primary); }
.btn { padding: 0.6rem 1.2rem; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.2s; text-decoration: none; font-size: 0.9rem; }
.btn-primary { background: var(--primary); color: white; }
.btn-primary:hover { filter: brightness(110%); transform: translateY(-1px); }
.btn-success { background: var(--success); color: white; }
.btn-success:hover { filter: brightness(110%); }
.btn-secondary { background: var(--text-muted); color: white; }
.btn-secondary:hover { background: var(--text-main); }
.form-control { width: 100%; padding: 0.75rem; border: 1px solid var(--card-border); border-radius: 8px; background: var(--bg-base); color: var(--text-main); margin-top: 0.25rem; font-family: inherit; }
.form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--glow-color); }
.email-preview-modal {
    display: none;
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 9999;
    justify-content: center;
    align-items: center;
    padding: 20px;
}
.email-preview-modal.active { display: flex; }
.email-preview-content {
    background: white;
    border-radius: 12px;
    width: 100%;
    max-width: 700px;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
}
.email-preview-close {
    position: absolute;
    top: 15px; right: 15px;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 50%;
    width: 32px; height: 32px;
    cursor: pointer;
    font-size: 18px;
    z-index: 10;
}
.email-preview-header {
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px 12px 0 0;
}
.email-preview-body { padding: 20px; }
</style>

<div class="main-content">
    <div class="booking-details-wrapper">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <div class="booking-header">
            <div class="booking-title">
                <h1><?= htmlspecialchars($booking['booking_title'] ?? 'N/A') ?></h1>
                <div class="booking-id">Booking Ref: #<?= htmlspecialchars($booking['id']) ?></div>
            </div>
            <div class="booking-actions">
                <a href="bookings.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print
                </button>
                <button onclick="previewEmail()" class="btn btn-success" style="background: #8b5cf6;">
                    <i class="fas fa-envelope"></i> Preview Email
                </button>
            </div>
        </div>

        <div class="booking-layout">
            <div>
                <div class="booking-card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle"></i> Booking Details</h3>
                        <span class="status-display status-<?= htmlspecialchars($booking['booking_status'] ?? 'pending') ?>">
                            <?= ucfirst(htmlspecialchars($booking['booking_status'] ?? 'Pending')) ?>
                        </span>
                    </div>

                    <?php
                    $details = [];
                    if (!empty($booking['booking_details'])) {
                        $details = json_decode($booking['booking_details'], true) ?? [];
                    }
                    ?>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Type</span>
                            <span class="info-value"><?= ucfirst(htmlspecialchars($booking['booking_type'] ?? 'Standard')) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date Placed</span>
                            <span class="info-value"><?= isset($booking['created_at']) ? date('F j, Y', strtotime($booking['created_at'])) : 'N/A' ?></span>
                        </div>
                        <?php if (!empty($booking['start_date'])): ?>
                        <div class="info-item">
                            <span class="info-label">Check-in Date</span>
                            <span class="info-value"><?= date('F j, Y', strtotime($booking['start_date'])) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($booking['end_date'])): ?>
                        <div class="info-item">
                            <span class="info-label">Check-out Date</span>
                            <span class="info-value"><?= date('F j, Y', strtotime($booking['end_date'])) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <span class="info-label">Travelers/Guests</span>
                            <span class="info-value"><?= htmlspecialchars($booking['number_of_people'] ?? '0') ?></span>
                        </div>
                        <?php if (isset($details['rooms']) && $details['rooms'] > 0): ?>
                        <div class="info-item">
                            <span class="info-label">Rooms Booked</span>
                            <span class="info-value"><?= htmlspecialchars($details['rooms']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <span class="info-label">Total Amount</span>
                            <span class="info-value" style="color: var(--success); font-size: 1.25rem; font-weight: 700;">
                                $<?= number_format($booking['total_amount'] ?? 0, 2) ?>
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($details)): ?>
                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--card-border);">
                        <h4 style="color: var(--text-main); margin-bottom: 1rem; font-size: 0.95rem; font-weight: 600;">Confirmation Details:</h4>
                        <div style="background: var(--bg-base); padding: 1rem; border-radius: 8px; font-size: 0.9rem;">
                            <?php if (isset($details['destination_name'])): ?>
                                <div><strong>Destination:</strong> <?= htmlspecialchars($details['destination_name']) ?></div>
                            <?php endif; ?>
                            <?php if (isset($details['flight_id']) && $details['flight_id'] > 0): ?>
                                <div><strong>Flight ID:</strong> #<?= htmlspecialchars($details['flight_id']) ?></div>
                            <?php endif; ?>
                            <?php if (isset($details['hotel_id']) && $details['hotel_id'] > 0): ?>
                                <div><strong>Hotel ID:</strong> #<?= htmlspecialchars($details['hotel_id']) ?></div>
                            <?php endif; ?>
                            <?php if (isset($details['checkin_date'])): ?>
                                <div><strong>Check-in:</strong> <?= date('M j, Y', strtotime($details['checkin_date'])) ?></div>
                            <?php endif; ?>
                            <?php if (isset($details['checkout_date'])): ?>
                                <div><strong>Check-out:</strong> <?= date('M j, Y', strtotime($details['checkout_date'])) ?></div>
                            <?php endif; ?>
                            <?php if (isset($details['travelers'])): ?>
                                <div><strong>Travelers:</strong> <?= htmlspecialchars($details['travelers']) ?></div>
                            <?php endif; ?>
                            <?php if (isset($details['booking_timestamp'])): ?>
                                <div><strong>Confirmed At:</strong> <?= date('M j, Y H:i', strtotime($details['booking_timestamp'])) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="booking-card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user"></i> Customer Info</h3>
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Name</span>
                            <span class="info-value"><?= htmlspecialchars($booking['user_name'] ?? 'Unknown') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?= htmlspecialchars($booking['user_email'] ?? 'N/A') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phone</span>
                            <span class="info-value"><?= htmlspecialchars($booking['user_phone'] ?? 'N/A') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="booking-card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-credit-card"></i> Payment Status</h3>
                        <span class="status-display payment-<?= htmlspecialchars($booking['payment_status'] ?? 'pending') ?>">
                            <?= ucfirst(htmlspecialchars($booking['payment_status'] ?? 'Pending')) ?>
                        </span>
                    </div>

                    <form method="POST" style="margin-top: 1rem;">
                        <input type="hidden" name="action" value="update_payment">
                        <div style="margin-bottom: 1rem;">
                            <label class="info-label">Update Status</label>
                            <select name="payment_status" class="form-control">
                                <option value="pending" <?= ($booking['payment_status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="paid" <?= ($booking['payment_status'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                                <option value="cancelled" <?= ($booking['payment_status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <label class="info-label">Transaction ID</label>
                            <input type="text" name="transaction_id" class="form-control" value="<?= htmlspecialchars($booking['transaction_id'] ?? '') ?>">
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <label class="info-label">Payment Method</label>
                            <input type="text" name="payment_method" class="form-control" value="<?= htmlspecialchars($booking['payment_method'] ?? '') ?>" placeholder="e.g., Credit Card, PayPal, Bank Transfer">
                        </div>
                        <button type="submit" class="btn btn-success" style="width: 100%; justify-content: center;">Save Payment</button>
                    </form>
                </div>

                <div class="booking-card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-cogs"></i> Manage Booking</h3>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_status">
                        <div style="margin-bottom: 1rem;">
                            <label class="info-label">Booking Status</label>
                            <select name="status" class="form-control">
                                <option value="pending" <?= ($booking['booking_status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="confirmed" <?= ($booking['booking_status'] ?? '') === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                <option value="completed" <?= ($booking['booking_status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= ($booking['booking_status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <label class="info-label">Admin Notes</label>
                            <textarea name="admin_notes" class="form-control" rows="3"><?= htmlspecialchars($booking['admin_notes'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                            <i class="fas fa-save"></i> Update & Send Email
                        </button>
                    </form>
                </div>

                <div class="booking-card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-envelope-open-text"></i> Email Notifications</h3>
                    </div>
                    <div style="font-size: 0.9rem; color: var(--text-muted);">
                        <p><i class="fas fa-info-circle"></i> When you update the booking status, an automatic email will be sent to:</p>
                        <p style="font-weight: 600; color: var(--text-main);"><i class="fas fa-user"></i> <?= htmlspecialchars($booking['user_name'] ?? 'N/A') ?></p>
                        <p style="font-weight: 600; color: var(--primary);"><i class="fas fa-at"></i> <?= htmlspecialchars($booking['user_email'] ?? 'N/A') ?></p>
                        <hr style="border: none; border-top: 1px solid var(--card-border); margin: 1rem 0;">
                        <p style="font-size: 0.8rem;">The email includes: Destination, Flight, Hotel, Dates, Payment Status, and Total Amount.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Email Preview Modal -->
<div class="email-preview-modal" id="emailPreviewModal">
    <div class="email-preview-content">
        <button class="email-preview-close" onclick="closePreview()">×</button>
        <div class="email-preview-header">
            <h3 style="margin: 0;"><i class="fas fa-envelope"></i> Email Preview</h3>
            <p style="margin: 5px 0 0 0; opacity: 0.9;">This is how the email will look to the user</p>
        </div>
        <div class="email-preview-body">
            <?php
            $previewDetails = [];
            if (!empty($booking['booking_details'])) {
                $previewDetails = json_decode($booking['booking_details'], true) ?? [];
            }
            echo buildBookingEmailHTML($booking, $previewDetails, $booking['booking_status'] ?? 'pending', 'pending');
            ?>
        </div>
    </div>
</div>

<script>
setTimeout(() => { document.querySelectorAll('.alert').forEach(a => a.style.display = 'none'); }, 4000);

function previewEmail() {
    document.getElementById('emailPreviewModal').classList.add('active');
}

function closePreview() {
    document.getElementById('emailPreviewModal').classList.remove('active');
}

document.getElementById('emailPreviewModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePreview();
    }
});
</script>

<?php include 'admin_footer.php'; ?>