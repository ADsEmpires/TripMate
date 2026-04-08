<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

require_once '../database/dbconfig.php';

// Get booking ID from URL
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$booking_id) {
    header('Location: booking.php');
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
    error_log('booking_details: prepare failed (booking select): ' . $conn->error);
    die('Server error. Please check the logs.');
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
if (!$payments_stmt) {
    error_log('booking_details: prepare failed (payments select): ' . $conn->error);
    $payments = [];
} else {
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
            } else {
                error_log('booking_details: execute failed (update status): ' . $update_stmt->error);
            }
        } else {
            error_log('booking_details: prepare failed (update status): ' . $conn->error);
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
            } else {
                error_log('booking_details: execute failed (update payment): ' . $update_stmt->error);
            }
        } else {
            error_log('booking_details: prepare failed (update payment): ' . $conn->error);
        }
    }
    
    if ($action === 'add_payment') {
        $amount = $_POST['amount'] ?? 0;
        $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
        $payment_method = $_POST['payment_method'] ?? '';
        $transaction_id = $_POST['transaction_id'] ?? '';
        $receipt_url = $_POST['receipt_url'] ?? '';
        
        $insert_stmt = $conn->prepare("INSERT INTO booking_payments (booking_id, amount, payment_date, payment_method, transaction_id, receipt_url, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        if ($insert_stmt) {
            $insert_stmt->bind_param("idssss", $booking_id, $amount, $payment_date, $payment_method, $transaction_id, $receipt_url);
            if ($insert_stmt->execute()) {
                $success_message = "Payment recorded successfully!";
                // Refresh payments list
                if ($payments_stmt) {
                    $payments_stmt->execute();
                    $payments = $payments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                }
            } else {
                error_log('booking_details: execute failed (insert payment): ' . $insert_stmt->error);
            }
        } else {
            error_log('booking_details: prepare failed (insert payment): ' . $conn->error);
        }
    }
}

include 'admin_header.php';
?>

<style>
/* Booking Details Styles */
.booking-details-wrapper {
    padding: 1.5rem;
}

.booking-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid rgba(0,0,0,0.08);
}

.booking-title h1 {
    font-size: 1.75rem;
    color: var(--dark);
    margin-bottom: 0.5rem;
}

.booking-id {
    color: var(--gray);
    font-size: 0.9rem;
}

.booking-actions {
    display: flex;
    gap: 0.75rem;
}

/* Booking Layout */
.booking-layout {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
}

@media (max-width: 992px) {
    .booking-layout {
        grid-template-columns: 1fr;
    }
}

/* Booking Card */
.booking-card {
    background: var(--card-bg);
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 3px 15px rgba(0,0,0,0.05);
    margin-bottom: 1.5rem;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.08);
}

.card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

/* Info Grid */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.85rem;
    color: var(--gray);
    font-weight: 500;
}

.info-value {
    font-size: 1rem;
    color: var(--dark);
    font-weight: 600;
}

/* Status Display */
.status-display {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-confirmed {
    background: #d1fae5;
    color: #065f46;
}

.status-completed {
    background: #dbeafe;
    color: #1e40af;
}

.status-cancelled {
    background: #fee2e2;
    color: #991b1b;
}

/* Timeline */
.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 0.5rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--primary);
}

.timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.timeline-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.timeline-marker {
    position: absolute;
    left: -2rem;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    background: var(--primary);
    border: 3px solid var(--card-bg);
}

.timeline-content {
    margin-left: 1rem;
}

.timeline-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: var(--dark);
}

.timeline-time {
    font-size: 0.85rem;
    color: var(--gray);
}

/* Payment History */
.payment-table {
    width: 100%;
    border-collapse: collapse;
}

.payment-table th {
    text-align: left;
    padding: 0.75rem;
    background: var(--muted);
    font-weight: 600;
    color: var(--dark);
}

.payment-table td {
    padding: 0.75rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

/* Forms */
.update-form {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(0,0,0,0.05);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

@media (max-width: 576px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}

/* Buttons */
.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--secondary);
}

.btn-success {
    background: var(--success);
    color: white;
}

.btn-success:hover {
    background: #0d9488;
}

.btn-danger {
    background: var(--danger);
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}

.btn-secondary {
    background: var(--gray);
    color: white;
}

.btn-secondary:hover {
    background: #6b7280;
}
</style>

<div class="main-content">
    <div class="booking-details-wrapper">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem;">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <!-- Booking Header -->
        <div class="booking-header">
            <div class="booking-title">
                <h1><?= htmlspecialchars($booking['booking_title']) ?></h1>
                <div class="booking-id">Booking ID: #<?= $booking['id'] ?></div>
            </div>
            <div class="booking-actions">
                <a href="bookings.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Bookings
                </a>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>

        <!-- Booking Layout -->
        <div class="booking-layout">
            <!-- Left Column -->
            <div>
                <!-- Booking Information -->
                <div class="booking-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle"></i> Booking Information
                        </h3>
                        <span class="status-display status-<?= $booking['booking_status'] ?>">
                            <?= ucfirst($booking['booking_status']) ?>
                        </span>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Booking Type</span>
                            <span class="info-value"><?= ucfirst($booking['booking_type']) ?></span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Booking Date</span>
                            <span class="info-value"><?= date('F j, Y', strtotime($booking['booking_date'])) ?></span>
                        </div>
                        
                        <?php if ($booking['start_date']): ?>
                        <div class="info-item">
                            <span class="info-label">Start Date</span>
                            <span class="info-value"><?= date('F j, Y', strtotime($booking['start_date'])) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($booking['end_date']): ?>
                        <div class="info-item">
                            <span class="info-label">End Date</span>
                            <span class="info-value"><?= date('F j, Y', strtotime($booking['end_date'])) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="info-item">
                            <span class="info-label">Number of People</span>
                            <span class="info-value"><?= $booking['number_of_people'] ?></span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Total Amount</span>
                            <span class="info-value" style="color: var(--success); font-size: 1.25rem;">
                                $<?= number_format($booking['total_amount'], 2) ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($booking['booking_details']): ?>
                    <div style="margin-top: 1.5rem;">
                        <h4 style="margin-bottom: 0.5rem; color: var(--dark);">Booking Details</h4>
                        <div style="background: var(--muted); padding: 1rem; border-radius: 6px;">
                            <?= nl2br(htmlspecialchars($booking['booking_details'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Customer Information -->
                <div class="booking-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user"></i> Customer Information
                        </h3>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Customer Name</span>
                            <span class="info-value"><?= htmlspecialchars($booking['user_name']) ?></span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?= htmlspecialchars($booking['user_email']) ?></span>
                        </div>
                        
                        <?php if ($booking['user_phone']): ?>
                        <div class="info-item">
                            <span class="info-label">Phone</span>
                            <span class="info-value"><?= htmlspecialchars($booking['user_phone']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Destination Information -->
                <?php if ($booking['destination_name']): ?>
                <div class="booking-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-map-marker-alt"></i> Destination Information
                        </h3>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Destination</span>
                            <span class="info-value"><?= htmlspecialchars($booking['destination_name']) ?></span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Location</span>
                            <span class="info-value"><?= htmlspecialchars($booking['destination_location']) ?></span>
                        </div>
                    </div>
                    
                    <?php if ($booking['destination_description']): ?>
                    <div style="margin-top: 1.5rem;">
                        <h4 style="margin-bottom: 0.5rem; color: var(--dark);">Description</h4>
                        <div style="color: var(--text); line-height: 1.6;">
                            <?= nl2br(htmlspecialchars(substr($booking['destination_description'], 0, 300))) ?>
                            <?= strlen($booking['destination_description']) > 300 ? '...' : '' ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column -->
            <div>
                <!-- Payment Information -->
                <div class="booking-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-credit-card"></i> Payment Information
                        </h3>
                        <span class="status-display payment-<?= $booking['payment_status'] ?>">
                            <?= ucfirst($booking['payment_status']) ?>
                        </span>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Payment Status</span>
                            <span class="info-value"><?= ucfirst($booking['payment_status']) ?></span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Payment Method</span>
                            <span class="info-value"><?= $booking['payment_method'] ?: 'Not specified' ?></span>
                        </div>
                        
                        <?php if ($booking['transaction_id']): ?>
                        <div class="info-item">
                            <span class="info-label">Transaction ID</span>
                            <span class="info-value"><?= htmlspecialchars($booking['transaction_id']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Update Payment Form -->
                    <div class="update-form">
                        <h4 style="margin-bottom: 1rem; color: var(--dark);">Update Payment Status</h4>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_payment">
                            
                            <div class="form-row">
                                <div>
                                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Payment Status</label>
                                    <select name="payment_status" style="width: 100%; padding: 0.75rem; border: 1px solid rgba(0,0,0,0.1); border-radius: 6px; background: var(--muted); color: var(--text);">
                                        <option value="pending" <?= $booking['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="paid" <?= $booking['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                                        <option value="cancelled" <?= $booking['payment_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        <option value="refunded" <?= $booking['payment_status'] === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Payment Method</label>
                                    <input type="text" name="payment_method" value="<?= htmlspecialchars($booking['payment_method'] ?? '') ?>" style="width: 100%; padding: 0.75rem; border: 1px solid rgba(0,0,0,0.1); border-radius: 6px; background: var(--muted); color: var(--text);">
                                </div>
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Transaction ID</label>
                                <input type="text" name="transaction_id" value="<?= htmlspecialchars($booking['transaction_id'] ?? '') ?>" style="width: 100%; padding: 0.75rem; border: 1px solid rgba(0,0,0,0.1); border-radius: 6px; background: var(--muted); color: var(--text);">
                            </div>
                            
                            <button type="submit" class="btn btn-success" style="width: 100%;">
                                <i class="fas fa-save"></i> Update Payment
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Booking Timeline -->
                <div class="booking-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-history"></i> Booking Timeline
                        </h3>
                    </div>
                    
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <div class="timeline-title">Booking Created</div>
                                <div class="timeline-time"><?= date('F j, Y g:i A', strtotime($booking['created_at'])) ?></div>
                            </div>
                        </div>
                        
                        <?php if ($booking['updated_at'] && $booking['updated_at'] !== $booking['created_at']): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <div class="timeline-title">Last Updated</div>
                                <div class="timeline-time"><?= date('F j, Y g:i A', strtotime($booking['updated_at'])) ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Admin Actions -->
                <div class="booking-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-cogs"></i> Admin Actions
                        </h3>
                    </div>
                    
                    <!-- Update Booking Status Form -->
                    <form method="POST" style="margin-bottom: 1.5rem;">
                        <input type="hidden" name="action" value="update_status">
                        
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Update Booking Status</label>
                            <select name="status" style="width: 100%; padding: 0.75rem; border: 1px solid rgba(0,0,0,0.1); border-radius: 6px; background: var(--muted); color: var(--text); margin-bottom: 1rem;">
                                <option value="pending" <?= $booking['booking_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="confirmed" <?= $booking['booking_status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                <option value="completed" <?= $booking['booking_status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= $booking['booking_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Admin Notes</label>
                            <textarea name="admin_notes" style="width: 100%; padding: 0.75rem; border: 1px solid rgba(0,0,0,0.1); border-radius: 6px; background: var(--muted); color: var(--text); min-height: 100px;"><?= htmlspecialchars($booking['admin_notes'] ?? '') ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-save"></i> Update Booking Status
                        </button>
                    </form>
                    
                    <!-- Quick Actions -->
                    <div style="display: flex; gap: 0.5rem;">
                        <button onclick="sendConfirmationEmail(<?= $booking['id'] ?>)" class="btn btn-success" style="flex: 1;">
                            <i class="fas fa-envelope"></i> Send Confirmation
                        </button>
                        <button onclick="generateInvoice(<?= $booking['id'] ?>)" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-file-invoice"></i> Generate Invoice
                        </button>
                    </div>
                </div>

                <!-- Payment History -->
                <?php if (!empty($payments)): ?>
                <div class="booking-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-receipt"></i> Payment History
                        </h3>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table class="payment-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Transaction ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= date('M j, Y', strtotime($payment['payment_date'])) ?></td>
                                    <td style="color: var(--success); font-weight: 600;">$<?= number_format($payment['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($payment['payment_method']) ?></td>
                                    <td><?= htmlspecialchars($payment['transaction_id']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function sendConfirmationEmail(bookingId) {
    if (confirm('Send confirmation email to customer?')) {
        fetch('send_confirmation.php?id=' + bookingId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Confirmation email sent successfully!');
                } else {
                    alert('Failed to send email: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error sending email');
                console.error('Error:', error);
            });
    }
}

function generateInvoice(bookingId) {
    window.open('generate_invoice.php?id=' + bookingId, '_blank');
}

// Auto-hide success messages
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.display = 'none';
    });
}, 5000);
</script>

<?php include 'admin_footer.php'; ?>