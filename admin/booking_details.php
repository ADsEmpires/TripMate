<?php
session_start();
// Added error reporting so you can actually see if a database column is missing!
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

require_once '../database/dbconfig.php';

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$booking_id) {
    header('Location: bookings.php');
    exit();
}

// Fetch booking details
$query = "SELECT b.*, u.name as user_name, u.email as user_email, u.phone as user_phone,
          d.name as destination_name, d.location as destination_location, d.description as destination_description,
          d.image_url as destination_image
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

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $new_status = $_POST['status'] ?? '';
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        $update_stmt = $conn->prepare("UPDATE bookings SET booking_status = ?, admin_notes = ? WHERE id = ?");
        if ($update_stmt) {
            $update_stmt->bind_param("ssi", $new_status, $admin_notes, $booking_id);
            if ($update_stmt->execute()) {
                $success_message = "Booking status updated successfully!";
                $booking['booking_status'] = $new_status;
                $booking['admin_notes'] = $admin_notes;
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
            }
        }
    }
}

include 'admin_header.php';
?>

<style>
/* Booking Details Styles linked to admin_header.php variables */
.booking-details-wrapper { padding: 2rem; max-width: 1200px; margin: 0 auto; }

/* Cards & Layout */
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

/* Text & Headers */
.booking-title h1 { font-size: 1.5rem; color: var(--text-main); margin: 0 0 0.25rem 0; }
.booking-id { color: var(--text-muted); font-size: 0.9rem; font-weight: 500; }
.card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--card-border); }
.card-title { font-size: 1.15rem; font-weight: 600; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem; margin: 0; }

/* Data Grid */
.info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; }
.info-item { display: flex; flex-direction: column; gap: 0.4rem; }
.info-label { font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
.info-value { font-size: 1rem; color: var(--text-main); font-weight: 500; }

/* Status Badges */
.status-display { display: inline-flex; align-items: center; padding: 0.4rem 1rem; border-radius: 9999px; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
.status-pending { background: rgba(245, 158, 11, 0.1); color: var(--warning); border: 1px solid var(--warning); }
.status-confirmed, .payment-paid { background: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid var(--success); }
.status-completed { background: rgba(59, 130, 246, 0.1); color: var(--info); border: 1px solid var(--info); }
.status-cancelled, .payment-cancelled { background: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid var(--danger); }
.payment-refunded { background: rgba(99, 102, 241, 0.1); color: var(--primary); border: 1px solid var(--primary); }

/* Forms & Buttons */
.btn { padding: 0.6rem 1.2rem; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.2s; text-decoration: none; font-size: 0.9rem; }
.btn-primary { background: var(--primary); color: white; }
.btn-primary:hover { filter: brightness(110%); transform: translateY(-1px); }
.btn-success { background: var(--success); color: white; }
.btn-success:hover { filter: brightness(110%); }
.btn-secondary { background: var(--text-muted); color: white; }
.btn-secondary:hover { background: var(--text-main); }

.form-control { width: 100%; padding: 0.75rem; border: 1px solid var(--card-border); border-radius: 8px; background: var(--bg-base); color: var(--text-main); margin-top: 0.25rem; font-family: inherit; }
.form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--glow-color); }
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
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Type</span>
                            <span class="info-value"><?= ucfirst(htmlspecialchars($booking['booking_type'] ?? 'Standard')) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date Placed</span>
                            <span class="info-value"><?= isset($booking['booking_date']) ? date('F j, Y', strtotime($booking['booking_date'])) : 'N/A' ?></span>
                        </div>
                        <?php if (!empty($booking['start_date'])): ?>
                        <div class="info-item">
                            <span class="info-label">Start Date</span>
                            <span class="info-value"><?= date('F j, Y', strtotime($booking['start_date'])) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <span class="info-label">Guests</span>
                            <span class="info-value"><?= htmlspecialchars($booking['number_of_people'] ?? '0') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total Amount</span>
                            <span class="info-value" style="color: var(--success); font-size: 1.25rem; font-weight: 700;">
                                $<?= number_format($booking['total_amount'] ?? 0, 2) ?>
                            </span>
                        </div>
                    </div>
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
                        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">Update Booking</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
setTimeout(() => { document.querySelectorAll('.alert').forEach(a => a.style.display = 'none'); }, 4000);
</script>

<?php include 'admin_footer.php'; ?>