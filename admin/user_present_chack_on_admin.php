<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

include '../database/dbconfig.php';

// Additional Logic Features

// Handle user removal POST
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_user'])) {
    // Required fields
    $remove_id = isset($_POST['remove_id']) ? (int)$_POST['remove_id'] : 0;
    $admin_password = $_POST['admin_password'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    if ($remove_id <= 0 || empty($admin_password) || empty($reason)) {
        $error = 'All fields are required to remove a user.';
    } else {
        // Ensure admin is in session
        if (!isset($_SESSION['admin_id'])) {
            $error = 'Admin session not found. Please login again.';
        } else {
            $admin_id = (int)$_SESSION['admin_id'];
            // Fetch admin password hash
            $stmt = $conn->prepare("SELECT password, name FROM admin WHERE id = ? LIMIT 1");
            $stmt->bind_param('i', $admin_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows === 1) {
                $admin = $res->fetch_assoc();
                $hash = $admin['password'];
                // verify password (supports hashed passwords)
                if (password_verify($admin_password, $hash)) {
                    // Fetch the user to remove (for logging)
                    $stmt2 = $conn->prepare("SELECT id, name, email FROM users WHERE id = ? LIMIT 1");
                    $stmt2->bind_param('i', $remove_id);
                    $stmt2->execute();
                    $res2 = $stmt2->get_result();
                    if ($res2 && $res2->num_rows === 1) {
                        $u = $res2->fetch_assoc();
                        // Delete user
                        $del = $conn->prepare("DELETE FROM users WHERE id = ?");
                        $del->bind_param('i', $remove_id);
                        if ($del->execute()) {
                            // Log removal to a logfile with reason and admin
                            $logfile = __DIR__ . '/user_removal_log.txt';
                            $line = sprintf("%s | admin_id:%d | admin_name:%s | user_id:%d | user_name:%s | user_email:%s | reason:%s | ip:%s\n",
                                date('Y-m-d H:i:s'),
                                $admin_id,
                                str_replace("\n", ' ', $admin['name']),
                                $u['id'],
                                str_replace("\n", ' ', $u['name']),
                                $u['email'],
                                str_replace(["\n","\r"], ' ', $reason),
                                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                            );
                            @file_put_contents($logfile, $line, FILE_APPEND | LOCK_EX);
                            $message = 'User removed successfully.';
                            // After deletion, refresh the page to update list (redirect to avoid repost)
                            header('Location: user_present_chack_on_admin.php?msg=removed');
                            exit();
                        } else {
                            $error = 'Failed to remove user. Database error.';
                        }
                    } else {
                        $error = 'User not found.';
                    }
                } else {
                    $error = 'Invalid admin password.';
                }
            } else {
                $error = 'Admin record not found.';
            }
        }
    }
}

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
$limit = 12; // Users per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// 5. Get total users for pagination
$count_query = "SELECT COUNT(*) as total FROM users $search_condition $date_filter";
$count_result = $conn->query($count_query);
$total_users = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);

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
$stats = $stats_result->fetch_assoc();

// Include header
include 'admin_header.php';
?>

<style>
/* User Management Styles */
.user-management-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 3px 15px rgba(0,0,0,0.05);
    margin-bottom: 2rem;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.card-header h2 {
    margin: 0;
    color: var(--primary);
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-header h2 i {
    color: var(--secondary);
}

.status-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
    background: #c6f6d5;
    color: #2f855a;
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

.user-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.user-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    border-left: 4px solid var(--accent);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
}

.user-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.12);
}

.user-card h3 {
    margin: 0 0 0.5rem 0;
    color: var(--primary);
    font-size: 1.2rem;
    font-weight: 600;
}



.user-meta i {
    color: var(--secondary);
    width: 16px;
}

.user-id {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: var(--light);
    padding: 0.3rem 0.6rem;
    border-radius: 15px;
    font-size: 0.75rem;
    color: var(--gray);
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.8rem 1.5rem;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    gap: 8px;
    background: var(--primary);
    color: white;
    margin-bottom: 1.5rem;
    transition: background 0.3s ease;
    text-decoration: none;
    font-size: 0.95rem;
}

.btn:hover {
    background: #1a5276;
}

.btn-secondary {
    background: var(--gray);
}

.btn-secondary:hover {
    background: #5a6268;
}

.btn:disabled {
    background: #e0e0e0;
    color: #a0a0a0;
    cursor: not-allowed;
}

.btn:disabled:hover {
    background: #e0e0e0;
}

.no-users {
    text-align: center;
    padding: 3rem 2rem;
    color: var(--gray);
    grid-column: 1 / -1;
}

.no-users h3 {
    margin-bottom: 0.5rem;
    color: var(--dark);
}

.no-users p {
    color: var(--gray);
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

/* Responsive adjustments */
@media (max-width: 768px) {
    .user-grid {
        grid-template-columns: 1fr;
    }
    
    .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .user-management-card {
        padding: 1rem;
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
}
</style>

<!-- Main Content -->
<div class="main-content">
    <div class="user-management-card">
        <div class="card-header">
            <h2><i class="fas fa-users"></i> User Management</h2>
            <span class="status-badge">Total: <?= $total_users ?> Users</span>
        </div>

        <?php if (!empty($_GET['msg']) && $_GET['msg'] === 'removed'): ?>
            <div style="padding:10px;border-radius:6px;background:#e6ffed;color:#1f7a3a;margin-bottom:1rem;">User removed successfully.</div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div style="padding:10px;border-radius:6px;background:#e6ffed;color:#1f7a3a;margin-bottom:1rem;"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div style="padding:10px;border-radius:6px;background:#ffecec;color:#9b2b2b;margin-bottom:1rem;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php
        // If remove_id is present in GET, fetch that user and show confirmation form
        $selected_user = null;
        if (isset($_GET['remove_id']) && is_numeric($_GET['remove_id'])) {
            $rid = (int)$_GET['remove_id'];
            $sstmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ? LIMIT 1");
            $sstmt->bind_param('i', $rid);
            $sstmt->execute();
            $sres = $sstmt->get_result();
            if ($sres && $sres->num_rows === 1) {
                $selected_user = $sres->fetch_assoc();
            }
        }
        ?>

        <?php if ($selected_user): ?>
        <div style="background:#fff3f2;border:1px solid #ffd6d2;padding:1rem;border-radius:8px;margin-bottom:1rem;">
            <h3 style="margin-top:0;color:#b02a2a;">Confirm removal of user "<?= htmlspecialchars($selected_user['name']) ?>" (ID: <?= $selected_user['id'] ?>)</h3>
            <form method="POST" style="display:block;gap:.5rem;">
                <input type="hidden" name="remove_id" value="<?= $selected_user['id'] ?>">
                <div style="margin-bottom:.5rem;">
                    <label for="reason" style="font-weight:600;display:block;margin-bottom:.25rem;">Reason for removal</label>
                    <textarea name="reason" id="reason" rows="3" required style="width:100%;padding:.5rem;border:1px solid #ddd;border-radius:4px;"></textarea>
                </div>
                <div style="margin-bottom:.5rem;">
                    <label for="admin_password" style="font-weight:600;display:block;margin-bottom:.25rem;">Confirm with admin password</label>
                    <input type="password" name="admin_password" id="admin_password" required style="width:100%;padding:.5rem;border:1px solid #ddd;border-radius:4px;">
                </div>
                <div style="display:flex;gap:.5rem;">
                    <button type="submit" name="remove_user" class="btn" style="background:#b02a2a;">Remove User</button>
                    <a href="user_present_chack_on_admin.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

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

        <button class="btn" disabled>
            <i class="fas fa-plus"></i> Add User (Coming Soon)
        </button>

        <div class="user-grid">
            <?php if (empty($users)): ?>
                <div class="no-users">
                    <i class="fas fa-users-slash" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                    <h3>No Users Found</h3>
                    <p><?= $search || isset($_GET['date_from']) ? 'Try adjusting your search filters.' : 'There are no users registered in the system yet.' ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                <div class="user-card">
                    <span class="user-id">ID: <?= $user['id'] ?></span>
                    <h3><?= htmlspecialchars($user['name']) ?></h3>
                    <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                    <div class="user-meta">
                        <i class="fas fa-calendar-alt"></i>
                        Joined: <?= date('M d, Y', strtotime($user['created_at'])) ?>
                    </div>
                    <div class="user-meta">
                        <i class="fas fa-clock"></i>
                        <?= date('h:i A', strtotime($user['created_at'])) ?>
                    </div>
                    <div style="margin-top:1rem;display:flex;gap:.5rem;">
                        <a href="?remove_id=<?= $user['id'] ?>" class="btn btn-secondary" style="background:#d9534f;border-color:#d43f3a;">Remove</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page-1 ?>&search=<?= $search ?>&sort=<?= $sort ?>&date_from=<?= $_GET['date_from'] ?? '' ?>&date_to=<?= $_GET['date_to'] ?? '' ?>">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php endif; ?>

            <span>Page <?= $page ?> of <?= $total_pages ?></span>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page+1 ?>&search=<?= $search ?>&sort=<?= $sort ?>&date_from=<?= $_GET['date_from'] ?? '' ?>&date_to=<?= $_GET['date_to'] ?? '' ?>">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include 'admin_footer.php';
?>