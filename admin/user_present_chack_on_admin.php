<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

include '../database/dbconfig.php';

///////////////////////////////////////////////////////////////////////////////
// Developer helper: enable errors on localhost while debugging (remove in prod)
if (php_sapi_name() === 'cli' || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'])) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Ensure $conn from dbconfig exists
if (!isset($conn) || !$conn) {
    die('Database connection not established. Check ../database/dbconfig.php');
}
///////////////////////////////////////////////////////////////////////////////

// 1. Search functionality
$search = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $search_condition = "WHERE name LIKE '%$search%' OR email LIKE '%$search%'";
} else {
    $search_condition = "";
}

// 2. Filter by date range
$date_filter = '';
if (isset($_GET['date_from']) && !empty($_GET['date_from']) && isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $date_from = $conn->real_escape_string($_GET['date_from']);
    $date_to = $conn->real_escape_string($_GET['date_to']);
    $date_filter = empty($search_condition) ? "WHERE" : "AND";
    $date_filter .= " created_at BETWEEN '$date_from' AND '$date_to 23:59:59'";
}

// 3. Sort functionality
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$sort_condition = "ORDER BY ";
switch($sort) {
    case 'oldest':
        $sort_condition .= "created_at ASC";
        break;
    case 'name_asc':
        $sort_condition .= "name ASC";
        break;
    case 'name_desc':
        $sort_condition .= "name DESC";
        break;
    default:
        $sort_condition .= "created_at DESC";
}

// 4. Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// 5. Get total users for pagination
$count_query = "SELECT COUNT(*) as total FROM users $search_condition $date_filter";
$count_result = $conn->query($count_query);
if ($count_result && ($row = $count_result->fetch_assoc())) {
    $total_users = (int)$row['total'];
} else {
    $total_users = 0;
}
$total_pages = $limit > 0 ? ceil($total_users / $limit) : 1;

// 6. Fetch users with all conditions
$users_query = "SELECT id, name, email, created_at FROM users $search_condition $date_filter $sort_condition LIMIT $limit OFFSET $offset";
$result = $conn->query($users_query);
$users = [];
if ($result) {
    $users = $result->fetch_all(MYSQLI_ASSOC);
}

// 7. User statistics
$stats_query = "SELECT 
    COUNT(*) as total_users,
    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_users,
    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_users,
    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as month_users
FROM users";
$stats_result = $conn->query($stats_query);
$stats = [
    'total_users' => 0,
    'today_users' => 0,
    'week_users'  => 0,
    'month_users' => 0
];
if ($stats_result && ($srow = $stats_result->fetch_assoc())) {
    $stats = $srow;
}

// Include header
include 'admin_header.php';
?>

<style>
/* User Management Styles - Table Layout */
.user-management-container {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 3px 15px rgba(0,0,0,0.05);
    margin-bottom: 2rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.page-header h1 {
    margin: 0;
    color: var(--primary);
    font-size: 1.8rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.page-header h1 i {
    color: var(--secondary);
}

.total-badge {
    background: var(--primary);
    color: white;
    padding: 0.5rem 1.2rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

/* Statistics Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    text-align: center;
    border-left: 4px solid var(--primary);
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: var(--primary);
    margin-bottom: 0.5rem;
}

.stat-label {
    color: var(--gray);
    font-size: 0.9rem;
}

/* Filters Section */
.filters-section {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    border: 1px solid #e9ecef;
}

.filter-group {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: end;
}

.filter-item {
    flex: 1;
    min-width: 200px;
}

.filter-item label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--dark);
}

.filter-item input, .filter-item select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.9rem;
}

/* Users Table */
.users-table-container {
    overflow-x: auto;
    margin-top: 1.5rem;
}

.users-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

.users-table thead {
    background: var(--primary);
    color: white;
}

.users-table th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    border: none;
}

.users-table tbody tr {
    border-bottom: 1px solid #eee;
    transition: background 0.2s ease;
}

.users-table tbody tr:hover {
    background: #f9f9f9;
}

.users-table td {
    padding: 1rem;
    vertical-align: middle;
}

.user-id {
    color: var(--gray);
    font-family: monospace;
    font-size: 0.9rem;
}

.user-name {
    font-weight: 600;
    color: var(--dark);
}

.user-email {
    color: var(--gray);
    font-size: 0.9rem;
}

.user-date {
    font-size: 0.9rem;
    color: #666;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.6rem 1rem;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    gap: 6px;
    background: var(--primary);
    color: white;
    transition: all 0.3s ease;
    text-decoration: none;
    font-size: 0.85rem;
    white-space: nowrap;
}

.btn:hover {
    background: #1a5276;
    transform: translateY(-1px);
}

.btn-danger {
    background: #dc3545;
}

.btn-danger:hover {
    background: #c82333;
}

.btn-secondary {
    background: #6c757d;
}

.btn-secondary:hover {
    background: #5a6268;
}

.btn-info {
    background: #17a2b8;
}

.btn-info:hover {
    background: #138496;
}

/* Confirmation Dialog */
.confirm-dialog {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.confirm-content {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    max-width: 400px;
    text-align: center;
    border-top: 4px solid #dc3545;
}

.confirm-content i {
    font-size: 3rem;
    color: #dc3545;
    margin-bottom: 1rem;
}

.confirm-buttons {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    justify-content: center;
}

/* No Users Message */
.no-users {
    text-align: center;
    padding: 3rem 2rem;
    color: var(--gray);
    background: #f9f9f9;
    border-radius: 8px;
    margin-top: 1.5rem;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #eee;
}

.pagination a, .pagination span {
    padding: 0.5rem 1rem;
    border: 1px solid #ddd;
    border-radius: 5px;
    text-decoration: none;
    color: var(--dark);
}

.pagination a:hover {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.pagination .current {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .filter-group {
        flex-direction: column;
    }
    
    .filter-item {
        min-width: 100%;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<!-- Main Content -->
<div class="main-content">
    <div class="user-management-container">
        <div class="page-header">
            <h1><i class="fas fa-users"></i> User Management</h1>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <a href="send_user_email.php" class="btn btn-info" style="text-decoration: none;">
                    <i class="fas fa-envelope"></i> Send Email to Users
                </a>
                <a href="user_removal_log_viewer.php" class="btn btn-secondary" style="text-decoration: none;">
                    <i class="fas fa-history"></i> View Removal Log
                </a>
                <div class="total-badge">Total: <?= $total_users ?> Users</div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_users'] ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['today_users'] ?></div>
                <div class="stat-label">Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['week_users'] ?></div>
                <div class="stat-label">This Week</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['month_users'] ?></div>
                <div class="stat-label">This Month</div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <form method="GET" action="">
                <div class="filter-group">
                    <div class="filter-item">
                        <label for="search"><i class="fas fa-search"></i> Search Users</label>
                        <input type="text" id="search" name="search" placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="filter-item">
                        <label for="date_from"><i class="fas fa-calendar"></i> Date From</label>
                        <input type="date" id="date_from" name="date_from" value="<?= $_GET['date_from'] ?? '' ?>">
                    </div>
                    <div class="filter-item">
                        <label for="date_to"><i class="fas fa-calendar"></i> Date To</label>
                        <input type="date" id="date_to" name="date_to" value="<?= $_GET['date_to'] ?? '' ?>">
                    </div>
                    <div class="filter-item">
                        <label for="sort"><i class="fas fa-sort"></i> Sort By</label>
                        <select id="sort" name="sort">
                            <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Newest First</option>
                            <option value="oldest" <?= $sort == 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                            <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Name A-Z</option>
                            <option value="name_desc" <?= $sort == 'name_desc' ? 'selected' : '' ?>>Name Z-A</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <button type="submit" class="btn"><i class="fas fa-filter"></i> Apply Filters</button>
                        <a href="user_present_chack_on_admin.php" class="btn btn-secondary"><i class="fas fa-times"></i> Clear</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="users-table-container">
            <?php if (empty($users)): ?>
                <div class="no-users">
                    <i class="fas fa-users-slash" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                    <h3>No Users Found</h3>
                    <p><?= $search || isset($_GET['date_from']) ? 'Try adjusting your search filters.' : 'There are no users registered in the system yet.' ?></p>
                </div>
            <?php else: ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="user-id">#<?= $user['id'] ?></td>
                            <td class="user-name"><?= htmlspecialchars($user['name']) ?></td>
                            <td class="user-email"><?= htmlspecialchars($user['email']) ?></td>
                            <td class="user-date">
                                <i class="fas fa-calendar-alt"></i> <?= date('M d, Y', strtotime($user['created_at'])) ?>
                                <br>
                                <small><i class="fas fa-clock"></i> <?= date('h:i A', strtotime($user['created_at'])) ?></small>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button onclick="confirmRemove(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['name'])) ?>', '<?= htmlspecialchars(addslashes($user['email'])) ?>')" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                    <a href="send_user_email.php?user_id=<?= $user['id'] ?>&email=<?= urlencode($user['email']) ?>&subject=<?= urlencode('Account Notification') ?>&sender_name=TRIPMATE%20ADMIN" class="btn btn-info">
                                        <i class="fas fa-envelope"></i> Email
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>&date_from=<?= urlencode($_GET['date_from'] ?? '') ?>&date_to=<?= urlencode($_GET['date_to'] ?? '') ?>">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php endif; ?>

            <span>Page <?= $page ?> of <?= $total_pages ?></span>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>&date_from=<?= urlencode($_GET['date_from'] ?? '') ?>&date_to=<?= urlencode($_GET['date_to'] ?? '') ?>">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Simple Confirmation Dialog -->
<div id="confirmDialog" class="confirm-dialog">
    <div class="confirm-content">
        <i class="fas fa-exclamation-triangle"></i>
        <h3 style="margin-bottom: 0.5rem;">Remove User?</h3>
        <p id="confirmUserInfo" style="color: #666; margin-bottom: 1rem;"></p>
        <p style="color: #dc3545; font-size: 0.9rem;">This will redirect to email page. User will be deleted after sending email.</p>
        <div class="confirm-buttons">
            <button class="btn btn-secondary" onclick="closeConfirm()">Cancel</button>
            <button class="btn btn-danger" id="confirmRemoveBtn">Continue</button>
        </div>
    </div>
</div>

<script>
// Simple confirmation function
let currentUserId = null;
let currentUserEmail = null;
let currentUserName = null;

function confirmRemove(userId, userName, userEmail) {
    currentUserId = userId;
    currentUserName = userName;
    currentUserEmail = userEmail;
    
    document.getElementById('confirmUserInfo').innerHTML = 
        '<strong>' + userName + '</strong><br>' + userEmail;
    
    document.getElementById('confirmDialog').style.display = 'flex';
}

function closeConfirm() {
    document.getElementById('confirmDialog').style.display = 'none';
}

// Handle confirm button
document.getElementById('confirmRemoveBtn').addEventListener('click', function() {
    if (currentUserId && currentUserEmail) {
        // Redirect to email page with pre-filled data
        const redirectUrl = 'send_user_email.php?' + 
            'user_id=' + encodeURIComponent(currentUserId) + 
            '&email=' + encodeURIComponent(currentUserEmail) + 
            '&subject=' + encodeURIComponent('Removed from Website') + 
            '&sender_name=' + encodeURIComponent('TRIPMATE ADMIN');
        
        window.location.href = redirectUrl;
    }
    closeConfirm();
});

// Close dialog when clicking outside
window.onclick = function(event) {
    const dialog = document.getElementById('confirmDialog');
    if (event.target == dialog) {
        closeConfirm();
    }
}

// Escape key to close
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeConfirm();
    }
});
</script>

<?php
// Include footer
include 'admin_footer.php';
?>