<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

require_once '../database/dbconfig.php';

// Handle POST actions with SECURE Prepared Statements
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $booking_id = (int)($_POST['booking_id'] ?? 0);

    if ($action === 'update_status' && $booking_id) {
        $new_status = $_POST['status'] ?? '';
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        $stmt = $conn->prepare("UPDATE bookings SET booking_status = ?, admin_notes = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_status, $admin_notes, $booking_id);
        
        if ($stmt->execute()) {
            $success_message = "Booking status updated successfully!";
            if ($new_status === 'confirmed') {
                // Fixed SQL Injection here
                $conf_stmt = $conn->prepare("UPDATE bookings SET payment_status = 'paid' WHERE id = ? AND payment_status = 'pending'");
                $conf_stmt->bind_param("i", $booking_id);
                $conf_stmt->execute();
            }
        }
    }
    
    if ($action === 'update_payment' && $booking_id) {
        $payment_status = $_POST['payment_status'] ?? '';
        $transaction_id = $_POST['transaction_id'] ?? '';
        $payment_method = $_POST['payment_method'] ?? '';
        
        $stmt = $conn->prepare("UPDATE bookings SET payment_status = ?, transaction_id = ?, payment_method = ? WHERE id = ?");
        $stmt->bind_param("sssi", $payment_status, $transaction_id, $payment_method, $booking_id);
        
        if ($stmt->execute()) {
            if ($payment_status === 'paid') {
                // Fixed SQL Injection here
                $amt_stmt = $conn->prepare("SELECT total_amount FROM bookings WHERE id = ?");
                $amt_stmt->bind_param("i", $booking_id);
                $amt_stmt->execute();
                $result = $amt_stmt->get_result();
                $amount = $result->fetch_assoc()['total_amount'] ?? 0;
                
                $insert_stmt = $conn->prepare("INSERT INTO booking_payments (booking_id, amount, payment_date, payment_method, transaction_id, created_at) VALUES (?, ?, NOW(), ?, ?, NOW())");
                $insert_stmt->bind_param("idss", $booking_id, $amount, $payment_method, $transaction_id);
                $insert_stmt->execute();
            }
            $success_message = "Payment status updated!";
        }
    }
}

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_payment = $_GET['payment'] ?? 'all';
$search_query = $_GET['search'] ?? '';

// Build query
$query = "SELECT b.*, u.name as user_name, u.email as user_email, 
          d.name as destination_name
          FROM bookings b
          LEFT JOIN users u ON b.user_id = u.id
          LEFT JOIN destinations d ON b.destination_id = d.id
          WHERE 1=1";

$params = [];
$types = "";

if ($filter_status !== 'all') { $query .= " AND b.booking_status = ?"; $params[] = $filter_status; $types .= "s"; }
if ($filter_payment !== 'all') { $query .= " AND b.payment_status = ?"; $params[] = $filter_payment; $types .= "s"; }
if (!empty($search_query)) {
    $query .= " AND (b.booking_title LIKE ? OR u.name LIKE ? OR d.name LIKE ?)";
    $search_param = "%$search_query%";
    array_push($params, $search_param, $search_param, $search_param);
    $types .= "sss";
}

$query .= " ORDER BY b.created_at DESC";
$stmt = $conn->prepare($query);
if ($types) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get Basic Stats safely
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'] ?? 0,
    'pending' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'pending'")->fetch_assoc()['count'] ?? 0,
    'revenue' => $conn->query("SELECT SUM(total_amount) as total FROM bookings WHERE payment_status = 'paid'")->fetch_assoc()['total'] ?? 0
];

include 'admin_header.php';
?>

<style>
/* Bookings Management linked to admin_header.php variables */
.booking-wrapper { padding: 2rem; max-width: 1400px; margin: 0 auto; }

/* Stat Cards */
.booking-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
.stat-card { 
    background: var(--bg-surface); border-radius: 12px; padding: 1.5rem; 
    box-shadow: 0 4px 6px -1px var(--shadow-color); text-align: center; 
    border: 1px solid var(--card-border);
    border-bottom: 4px solid var(--primary); transition: transform 0.2s; cursor: pointer; 
}
.stat-card:hover { transform: translateY(-3px); }
.stat-card.revenue { border-bottom-color: var(--success); }
.stat-card.pending { border-bottom-color: var(--warning); }
.stat-number { font-size: 2.2rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-main); }
.stat-label { color: var(--text-muted); font-size: 0.95rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }

/* Filters */
.filters-section { 
    background: var(--bg-surface); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; 
    border: 1px solid var(--card-border); box-shadow: 0 4px 6px -1px var(--shadow-color); 
}
.filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end; }
.form-control { width: 100%; padding: 0.75rem; border: 1px solid var(--card-border); border-radius: 8px; background: var(--bg-base); color: var(--text-main); font-family: inherit; }
.form-control:focus { outline: none; border-color: var(--primary); }
.btn { padding: 0.75rem 1.5rem; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.2s; color: white; background: var(--primary); }
.btn:hover { filter: brightness(110%); }

/* Table */
.bookings-table-wrapper { 
    background: var(--bg-surface); border-radius: 12px; overflow: hidden; 
    border: 1px solid var(--card-border); box-shadow: 0 4px 6px -1px var(--shadow-color); 
}
table { width: 100%; border-collapse: collapse; }
th { background: var(--bg-base); padding: 1.2rem 1rem; text-align: left; font-weight: 600; color: var(--text-muted); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; border-bottom: 1px solid var(--card-border); }
td { padding: 1.2rem 1rem; border-bottom: 1px solid var(--card-border); vertical-align: middle; color: var(--text-main); }
tr:hover { background: var(--bg-base); }

/* Badges */
.badge { padding: 0.4rem 0.8rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; }
.badge.pending { background: rgba(245, 158, 11, 0.1); color: var(--warning); border: 1px solid var(--warning); }
.badge.confirmed, .badge.paid { background: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid var(--success); }
.badge.cancelled { background: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid var(--danger); }

.action-btn { padding: 0.5rem 0.8rem; border-radius: 6px; border: none; cursor: pointer; color: white; margin-right: 0.25rem; font-size: 0.9rem; font-weight: 600; text-decoration: none; }
.btn-view { background: var(--primary); }
.btn-view:hover { filter: brightness(110%); }
</style>

<div class="main-content">
    <div class="booking-wrapper">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <div class="booking-stats">
            <div class="stat-card" onclick="window.location='bookings.php'">
                <div class="stat-number"><?= number_format($stats['total']) ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card pending" onclick="window.location='bookings.php?status=pending'">
                <div class="stat-number" style="color: var(--warning)"><?= number_format($stats['pending']) ?></div>
                <div class="stat-label">Pending Action</div>
            </div>
            <div class="stat-card revenue">
                <div class="stat-number" style="color: var(--success)">$<?= number_format($stats['revenue'], 2) ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <div class="filters-section">
            <form method="GET">
                <div class="filter-grid">
                    <div>
                        <label style="font-size: 0.85rem; font-weight: 600; color: var(--gray);">Status</label>
                        <select name="status" class="form-control">
                            <option value="all">All</option>
                            <option value="pending" <?= $filter_status == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="confirmed" <?= $filter_status == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 0.85rem; font-weight: 600; color: var(--gray);">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Name or ID..." value="<?= htmlspecialchars($search_query) ?>">
                    </div>
                    <div><button type="submit" class="btn" style="width: 100%;">Filter</button></div>
                </div>
            </form>
        </div>

        <div class="bookings-table-wrapper">
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Destination</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr><td colspan="7" style="text-align: center; padding: 3rem; color: var(--gray);">No bookings found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td><strong>#<?= $b['id'] ?></strong></td>
                                <td>
                                    <div style="font-weight: 600; color: var(--dark);"><?= htmlspecialchars($b['user_name'] ?? 'N/A') ?></div>
                                    <div style="font-size: 0.85rem; color: var(--gray);"><?= date('M j, Y', strtotime($b['created_at'])) ?></div>
                                </td>
                                <td><?= htmlspecialchars($b['destination_name'] ?? 'General') ?></td>
                                <td><strong>$<?= number_format($b['total_amount'] ?? 0, 2) ?></strong></td>
                                <td><span class="badge <?= $b['booking_status'] ?? 'pending' ?>"><?= $b['booking_status'] ?? 'Pending' ?></span></td>
                                <td><span class="badge <?= $b['payment_status'] ?? 'pending' ?>"><?= $b['payment_status'] ?? 'Pending' ?></span></td>
                                <td>
                                    <a href="booking_details.php?id=<?= $b['id'] ?>" class="action-btn btn-view"><i class="fas fa-eye"></i> View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="bookings-table-wrapper" style="margin-top: 2rem; padding: 1.5rem;">
             <h3 style="margin-top: 0;"><i class="fas fa-cloud-sun"></i> Destination Weather Alerts</h3>
             <div id="weatherAlertsContainer">
                <p style="color: var(--gray);"><i class="fas fa-spinner fa-spin"></i> Loading weather alerts for upcoming destinations...</p>
             </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function loadWeatherAlerts() {
        fetch('get_booking_weather.php')
            .then(res => {
                if(!res.ok) throw new Error("Network not ok");
                return res.json();
            })
            .then(data => {
                const container = document.getElementById('weatherAlertsContainer');
                if (data.alerts && data.alerts.length > 0) {
                    container.innerHTML = '<div class="alert alert-info" style="background: #e0e7ff; color: #3730a3; padding: 1rem; border-radius: 8px;">' + data.alerts.length + ' active weather alerts found. View API for details.</div>';
                } else {
                    container.innerHTML = '<p style="color: var(--gray);">No extreme weather alerts for upcoming destinations.</p>';
                }
            }).catch(e => {
                document.getElementById('weatherAlertsContainer').innerHTML = '<p style="color: var(--danger);">Weather API not configured or unreachable.</p>';
            });
    }
    loadWeatherAlerts();
});
</script>

<?php include 'admin_footer.php'; ?>