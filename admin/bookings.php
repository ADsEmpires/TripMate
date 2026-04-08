<?php
// Start session and check admin login
session_start();
if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

require_once '../database/dbconfig.php';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $booking_id = $_POST['booking_id'] ?? null;

    if ($action === 'update_status' && $booking_id) {
        $new_status = $_POST['status'] ?? '';
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        // Update booking status
        $stmt = $conn->prepare("UPDATE bookings SET booking_status = ?, admin_notes = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_status, $admin_notes, $booking_id);
        
        if ($stmt->execute()) {
            $success_message = "Booking status updated successfully!";
            
            // If status is confirmed, also update payment status if it's pending
            if ($new_status === 'confirmed') {
                $conn->query("UPDATE bookings SET payment_status = 'paid' WHERE id = $booking_id AND payment_status = 'pending'");
            }
        } else {
            $error_message = "Failed to update booking status.";
        }
    }
    
    if ($action === 'update_payment' && $booking_id) {
        $payment_status = $_POST['payment_status'] ?? '';
        $transaction_id = $_POST['transaction_id'] ?? '';
        $payment_method = $_POST['payment_method'] ?? '';
        
        $stmt = $conn->prepare("UPDATE bookings SET payment_status = ?, transaction_id = ?, payment_method = ? WHERE id = ?");
        $stmt->bind_param("sssi", $payment_status, $transaction_id, $payment_method, $booking_id);
        
        if ($stmt->execute()) {
            // Record payment if status is paid
            if ($payment_status === 'paid') {
                $amount = $conn->query("SELECT total_amount FROM bookings WHERE id = $booking_id")->fetch_assoc()['total_amount'];
                $stmt2 = $conn->prepare("INSERT INTO booking_payments (booking_id, amount, payment_date, payment_method, transaction_id, created_at) VALUES (?, ?, NOW(), ?, ?, NOW())");
                $stmt2->bind_param("idss", $booking_id, $amount, $payment_method, $transaction_id);
                $stmt2->execute();
            }
            
            $success_message = "Payment status updated successfully!";
        } else {
            $error_message = "Failed to update payment status.";
        }
    }
}

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_payment = $_GET['payment'] ?? 'all';
$search_query = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query with filters
$query = "SELECT b.*, u.name as user_name, u.email as user_email, 
          d.name as destination_name, d.location as destination_location
          FROM bookings b
          LEFT JOIN users u ON b.user_id = u.id
          LEFT JOIN destinations d ON b.destination_id = d.id
          WHERE 1=1";

$params = [];
$types = "";

if ($filter_status !== 'all') {
    $query .= " AND b.booking_status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($filter_payment !== 'all') {
    $query .= " AND b.payment_status = ?";
    $params[] = $filter_payment;
    $types .= "s";
}

if (!empty($search_query)) {
    $query .= " AND (b.booking_title LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR d.name LIKE ?)";
    $search_param = "%$search_query%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= "ssss";
}

if (!empty($date_from)) {
    $query .= " AND DATE(b.created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $query .= " AND DATE(b.created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$query .= " ORDER BY b.created_at DESC";

// Execute query
$stmt = $conn->prepare($query);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);

// Get statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'],
    'pending' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'pending'")->fetch_assoc()['count'],
    'confirmed' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'confirmed'")->fetch_assoc()['count'],
    'paid' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE payment_status = 'paid'")->fetch_assoc()['count'],
    'revenue' => $conn->query("SELECT SUM(total_amount) as total FROM bookings WHERE payment_status = 'paid'")->fetch_assoc()['total'] ?? 0
];

// Include admin header
include 'admin_header.php';
?>

<style>
/* Booking Management Styles */
.booking-wrapper {
    padding: 1.5rem;
}

/* Statistics Cards */
.booking-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--card-bg);
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 3px 15px rgba(0,0,0,0.05);
    text-align: center;
    border-top: 4px solid var(--primary);
    transition: transform 0.3s ease;
    cursor: pointer;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-card.revenue {
    border-top-color: var(--success);
}

.stat-card.pending {
    border-top-color: var(--warning);
}

.stat-card.confirmed {
    border-top-color: var(--info);
}

.stat-card.paid {
    border-top-color: var(--success);
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.stat-number.total {
    color: var(--primary);
}

.stat-number.revenue {
    color: var(--success);
}

.stat-number.pending {
    color: var(--warning);
}

.stat-number.confirmed {
    color: var(--info);
}

.stat-number.paid {
    color: var(--success);
}

.stat-label {
    color: var(--gray);
    font-size: 0.9rem;
}

/* Filters Section */
.filters-section {
    background: var(--muted);
    padding: 1.5rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    border: 1px solid rgba(0,0,0,0.08);
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-label {
    font-weight: 600;
    color: var(--dark);
    font-size: 0.9rem;
}

.filter-select, .filter-input {
    padding: 0.75rem;
    border: 1px solid rgba(0,0,0,0.1);
    border-radius: 6px;
    background: var(--card-bg);
    color: var(--text);
    font-size: 0.9rem;
    width: 100%;
}

.filter-button {
    align-self: flex-end;
    padding: 0.75rem 1.5rem;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.3s ease;
}

.filter-button:hover {
    background: var(--secondary);
}

/* Bookings Table */
.bookings-table {
    background: var(--card-bg);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 15px rgba(0,0,0,0.05);
}

.table-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(0,0,0,0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.table-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.table-actions {
    display: flex;
    gap: 1rem;
}

.table-container {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: var(--muted);
}

th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: var(--dark);
    border-bottom: 1px solid rgba(0,0,0,0.08);
    white-space: nowrap;
}

td {
    padding: 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

tbody tr:hover {
    background: rgba(67, 97, 238, 0.03);
}

/* Status Badges */
.status-badge {
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-block;
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

.payment-pending {
    background: #fef3c7;
    color: #92400e;
}

.payment-paid {
    background: #d1fae5;
    color: #065f46;
}

.payment-cancelled {
    background: #fee2e2;
    color: #991b1b;
}

.payment-refunded {
    background: #e0e7ff;
    color: #3730a3;
}

/* Booking Actions */
.booking-actions {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    padding: 0.5rem 0.75rem;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.8rem;
    font-weight: 600;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.btn-view {
    background: var(--primary);
    color: white;
}

.btn-view:hover {
    background: var(--secondary);
}

.btn-edit {
    background: var(--info);
    color: white;
}

.btn-edit:hover {
    background: #0ea5e9;
}

.btn-delete {
    background: var(--danger);
    color: white;
}

.btn-delete:hover {
    background: #dc2626;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: var(--card-bg);
    border-radius: 10px;
    width: 90%;
    max-width: 500px;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark);
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--gray);
}

.modal-body {
    padding: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--dark);
}

.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid rgba(0,0,0,0.1);
    border-radius: 6px;
    background: var(--muted);
    color: var(--text);
    font-size: 0.9rem;
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
}

.btn-primary {
    padding: 0.75rem 1.5rem;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}

.btn-secondary {
    padding: 0.75rem 1.5rem;
    background: var(--gray);
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}

/* No bookings message */
.no-bookings {
    text-align: center;
    padding: 3rem;
    color: var(--gray);
}

.no-bookings i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Responsive */
@media (max-width: 768px) {
    .booking-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .filter-grid {
        grid-template-columns: 1fr;
    }
    
    .table-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .booking-actions {
        flex-wrap: wrap;
    }
    
    th, td {
        padding: 0.75rem 0.5rem;
    }
}

@media (max-width: 576px) {
    .booking-stats {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        padding: 1rem;
    }
}
</style>

<div class="main-content">
    <div class="booking-wrapper">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem;">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error" style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem;">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="booking-stats">
            <div class="stat-card" onclick="filterBookings('all', 'all')">
                <div class="stat-number total"><?= number_format($stats['total']) ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card pending" onclick="filterBookings('pending', 'all')">
                <div class="stat-number pending"><?= number_format($stats['pending']) ?></div>
                <div class="stat-label">Pending Confirmation</div>
            </div>
            <div class="stat-card confirmed" onclick="filterBookings('confirmed', 'all')">
                <div class="stat-number confirmed"><?= number_format($stats['confirmed']) ?></div>
                <div class="stat-label">Confirmed</div>
            </div>
            <div class="stat-card paid" onclick="filterBookings('all', 'paid')">
                <div class="stat-number paid"><?= number_format($stats['paid']) ?></div>
                <div class="stat-label">Paid Bookings</div>
            </div>
            <div class="stat-card revenue">
                <div class="stat-number revenue">$<?= number_format($stats['revenue'], 2) ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label class="filter-label">Booking Status</label>
                        <select name="status" class="filter-select">
                            <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>All Status</option>
                            <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="confirmed" <?= $filter_status === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="completed" <?= $filter_status === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $filter_status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Payment Status</label>
                        <select name="payment" class="filter-select">
                            <option value="all" <?= $filter_payment === 'all' ? 'selected' : '' ?>>All Payments</option>
                            <option value="pending" <?= $filter_payment === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="paid" <?= $filter_payment === 'paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="cancelled" <?= $filter_payment === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            <option value="refunded" <?= $filter_payment === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Date From</label>
                        <input type="date" name="date_from" class="filter-input" value="<?= htmlspecialchars($date_from) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Date To</label>
                        <input type="date" name="date_to" class="filter-input" value="<?= htmlspecialchars($date_to) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Search</label>
                        <input type="text" name="search" class="filter-input" placeholder="Search bookings..." value="<?= htmlspecialchars($search_query) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">&nbsp;</label>
                        <button type="submit" class="filter-button">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Bookings Table -->
        <div class="bookings-table">
            <div class="table-header">
                <h3 class="table-title">
                    <i class="fas fa-calendar-check"></i> Bookings
                    <span style="font-size: 0.9rem; color: var(--gray); font-weight: normal;">
                        (<?= count($bookings) ?> found)
                    </span>
                </h3>
                <div class="table-actions">
                    <button class="action-btn btn-view" onclick="exportBookings()">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
            
            <div class="table-container">
                <?php if (empty($bookings)): ?>
                    <div class="no-bookings">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No bookings found</h3>
                        <p><?= $search_query || $filter_status !== 'all' || $filter_payment !== 'all' ? 'Try adjusting your filters' : 'No bookings have been made yet' ?></p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Booking Title</th>
                                <th>Dates</th>
                                <th>Amount</th>
                                <th>Booking Status</th>
                                <th>Payment Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td>#<?= $booking['id'] ?></td>
                                    <td>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($booking['user_name']) ?></div>
                                        <div style="font-size: 0.8rem; color: var(--gray);"><?= htmlspecialchars($booking['user_email']) ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($booking['booking_title']) ?></div>
                                        <?php if ($booking['destination_name']): ?>
                                            <div style="font-size: 0.8rem; color: var(--gray);">
                                                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($booking['destination_name']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($booking['start_date']): ?>
                                            <div><?= date('M j, Y', strtotime($booking['start_date'])) ?></div>
                                            <?php if ($booking['end_date']): ?>
                                                <div style="font-size: 0.8rem; color: var(--gray);">
                                                    to <?= date('M j, Y', strtotime($booking['end_date'])) ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div><?= date('M j, Y', strtotime($booking['booking_date'])) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--success);">$<?= number_format($booking['total_amount'], 2) ?></div>
                                        <div style="font-size: 0.8rem; color: var(--gray);">
                                            <?= $booking['number_of_people'] ?> person<?= $booking['number_of_people'] > 1 ? 's' : '' ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $booking['booking_status'] ?>">
                                            <?= ucfirst($booking['booking_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge payment-<?= $booking['payment_status'] ?>">
                                            <?= ucfirst($booking['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div><?= date('M j, Y', strtotime($booking['created_at'])) ?></div>
                                        <div style="font-size: 0.8rem; color: var(--gray);">
                                            <?= date('g:i A', strtotime($booking['created_at'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="booking-actions">
                                            <button class="action-btn btn-view" onclick="viewBooking(<?= $booking['id'] ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="action-btn btn-edit" onclick="editBooking(<?= $booking['id'] ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Booking Modal -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Edit Booking</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editForm" method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="booking_id" id="editBookingId">
                
                <div class="form-group">
                    <label class="form-label">Booking Status</label>
                    <select name="status" class="form-select" id="bookingStatus">
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Admin Notes</label>
                    <textarea name="admin_notes" class="form-textarea" id="adminNotes" placeholder="Add notes about this booking..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Update Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Payment Modal -->
<div class="modal" id="paymentModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Update Payment</h3>
            <button class="modal-close" onclick="closeModal('paymentModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="paymentForm" method="POST">
                <input type="hidden" name="action" value="update_payment">
                <input type="hidden" name="booking_id" id="paymentBookingId">
                
                <div class="form-group">
                    <label class="form-label">Payment Status</label>
                    <select name="payment_status" class="form-select" id="paymentStatus">
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Payment Method</label>
                    <input type="text" name="payment_method" class="form-input" id="paymentMethod" placeholder="e.g., Credit Card, PayPal, Cash">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Transaction ID</label>
                    <input type="text" name="transaction_id" class="form-input" id="transactionId" placeholder="Enter transaction ID">
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('paymentModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Update Payment</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Weather Alerts for Bookings -->
<div class="booking-card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-cloud-sun"></i> Destination Weather Alerts
        </h3>
    </div>
    <div id="weatherAlerts">
        <p style="text-align: center; color: var(--gray); padding: 1rem;">
            <i class="fas fa-spinner fa-spin"></i> Loading weather information for upcoming bookings...
        </p>
    </div>
</div>
</div>

<script>
// Filter bookings by status
function filterBookings(status, payment) {
    let url = 'bookings.php';
    let params = [];
    
    if (status !== 'all') params.push(`status=${status}`);
    if (payment !== 'all') params.push(`payment=${payment}`);
    
    if (params.length > 0) {
        url += '?' + params.join('&');
    }
    
    window.location.href = url;
}

// View booking details
function viewBooking(bookingId) {
    window.location.href = 'booking_details.php?id=' + bookingId;
}

// Edit booking
function editBooking(bookingId) {
    // Fetch booking details via AJAX
    fetch('get_booking.php?id=' + bookingId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('editBookingId').value = bookingId;
                document.getElementById('bookingStatus').value = data.booking_status;
                document.getElementById('adminNotes').value = data.admin_notes || '';
                openModal('editModal');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load booking details');
        });
}

// Edit payment
function editPayment(bookingId) {
    document.getElementById('paymentBookingId').value = bookingId;
    openModal('paymentModal');
}

// Export bookings
function exportBookings() {
    const params = new URLSearchParams(window.location.search);
    window.location.href = 'export_bookings.php?' + params.toString();
}

// Modal functions
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modals when clicking outside
window.addEventListener('click', function(event) {
    const modals = ['editModal', 'paymentModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal && event.target === modal) {
            closeModal(modalId);
        }
    });
});

// Auto-hide success messages
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.display = 'none';
    });
}, 5000);
// Weather Alerts Functions
function loadWeatherAlerts() {
    fetch('get_booking_weather.php')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('weatherAlertsContainer');
            const countElement = document.getElementById('weatherAlertsCount');
            
            if (data.alerts && data.alerts.length > 0) {
                countElement.innerHTML = `(${data.count} alert${data.count !== 1 ? 's' : ''})`;
                
                let html = '';
                data.alerts.forEach(alert => {
                    const alertClass = alert.feasibility === 'not_feasible' ? 'danger' : 
                                     alert.feasibility === 'challenging' ? 'warning' : 'success';
                    const borderColor = alertClass === 'danger' ? '#dc2626' : 
                                       alertClass === 'warning' ? '#d97706' : '#16a34a';
                    const bgColor = alertClass === 'danger' ? '#fee2e2' : 
                                   alertClass === 'warning' ? '#fef3c7' : '#d1fae5';
                    const textColor = alertClass === 'danger' ? '#991b1b' : 
                                     alertClass === 'warning' ? '#92400e' : '#065f46';
                    
                    html += `
                        <div class="weather-alert" style="padding: 1rem; margin-bottom: 0.75rem; 
                              background: ${bgColor}; 
                              border-radius: 8px; border-left: 4px solid ${borderColor};
                              border: 1px solid ${borderColor}20;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div style="flex: 1;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                        <strong style="font-size: 1rem;">${alert.destination}</strong>
                                        <span class="status-badge status-${alert.status}" style="font-size: 0.75rem;">
                                            ${alert.status}
                                        </span>
                                    </div>
                                    <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem;">
                                        <div><i class="fas fa-calendar"></i> ${formatDate(alert.booking_date)}</div>
                                        <div><i class="fas fa-location-dot"></i> ${alert.location}</div>
                                    </div>
                                    <div style="display: flex; gap: 1rem; font-size: 0.9rem;">
                                        <div><i class="fas fa-temperature-half"></i> ${alert.temperature}°C</div>
                                        <div><i class="fas fa-cloud"></i> ${alert.weather}</div>
                                        <div>Booking #${alert.booking_id}</div>
                                    </div>
                                </div>
                                <div style="margin-left: 1rem;">
                                    <span style="padding: 0.35rem 0.75rem; border-radius: 20px; 
                                          background: ${borderColor}; 
                                          color: white; font-size: 0.85rem; font-weight: 600; white-space: nowrap;">
                                        <i class="fas ${alertClass === 'danger' ? 'fa-triangle-exclamation' : 
                                                       alertClass === 'warning' ? 'fa-exclamation-triangle' : 
                                                       'fa-check-circle'}"></i>
                                        ${alert.feasibility === 'not_feasible' ? 'Not Advisable' : 
                                          alert.feasibility === 'challenging' ? 'Challenging' : 'Good to Go'}
                                    </span>
                                </div>
                            </div>
                            <div style="margin-top: 0.75rem; padding: 0.75rem; background: rgba(255,255,255,0.7); 
                                 border-radius: 6px; color: ${textColor}; font-size: 0.9rem; border: 1px solid ${borderColor}30;">
                                <i class="fas fa-info-circle"></i> <strong>Travel Advice:</strong> ${alert.message}
                            </div>
                            <div style="margin-top: 0.75rem; display: flex; gap: 0.5rem;">
                                <button onclick="viewBooking(${alert.booking_id})" class="action-btn btn-view" style="padding: 0.5rem 1rem;">
                                    <i class="fas fa-eye"></i> View Booking
                                </button>
                                <button onclick="sendWeatherAlert(${alert.booking_id})" class="action-btn btn-edit" style="padding: 0.5rem 1rem;">
                                    <i class="fas fa-envelope"></i> Notify Customer
                                </button>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            } else {
                countElement.innerHTML = '(0 alerts)';
                container.innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: var(--gray);">
                        <i class="fas fa-sun" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                        <p>No weather alerts for upcoming bookings.</p>
                        <p style="font-size: 0.9rem;">Weather alerts appear for bookings within the next 7 days.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading weather alerts:', error);
            document.getElementById('weatherAlertsContainer').innerHTML = `
                <div style="text-align: center; padding: 2rem; color: var(--gray);">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>Unable to load weather alerts.</p>
                    <p style="font-size: 0.9rem;">Please check your weather API configuration.</p>
                </div>
            `;
            document.getElementById('weatherAlertsCount').innerHTML = '(Error)';
        });
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function sendWeatherAlert(bookingId) {
    if (confirm('Send weather alert to customer?')) {
        // In a real implementation, this would call an API endpoint
        alert('Weather alert notification sent to customer for booking #' + bookingId);
    }
}

// Load weather alerts when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Existing code...
    
    // Load weather alerts
    loadWeatherAlerts();
    
    // Refresh weather alerts every 5 minutes
    setInterval(loadWeatherAlerts, 300000);
});
// Call this function when page loads
document.addEventListener('DOMContentLoaded', loadWeatherAlerts);
</script>

<?php include 'admin_footer.php'; ?>