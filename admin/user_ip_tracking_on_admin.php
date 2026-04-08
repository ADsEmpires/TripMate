<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

include '../database/dbconfig.php';
include 'admin_header.php';

// ============================================
// FIX 1: Track admin's own IP when viewing this page
// ============================================
if (isset($_SESSION['admin_id'])) {
    include_once '../backend/ip_tracking.php';
    
    // Check if function exists, if not define it here
    if (!function_exists('trackUserIP')) {
        function trackUserIP($user_id, $conn, $user_type = 'admin') {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            // Check if user_type column exists
            $check = $conn->query("SHOW COLUMNS FROM user_ips LIKE 'user_type'");
            if ($check && $check->num_rows > 0) {
                $sql = "INSERT INTO user_ips (user_id, user_type, ip_address, user_agent, login_time) 
                        VALUES (?, 'admin', ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iss", $user_id, $ip_address, $user_agent);
                $stmt->execute();
            } else {
                // Fallback - just insert without user_type
                $sql = "INSERT INTO user_ips (user_id, ip_address, user_agent, login_time) 
                        VALUES (?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iss", $user_id, $ip_address, $user_agent);
                $stmt->execute();
            }
            return true;
        }
    }
    
    // Track admin IP
    trackUserIP($_SESSION['admin_id'], $conn, 'admin');
}

// ============================================
// FIX 2: Determine show all or recent only
// ============================================
$show_all = isset($_GET['show_all']) && $_GET['show_all'] === '1';
$filter_type = isset($_GET['filter']) ? $_GET['filter'] : 'users'; // users, admins, all

// ============================================
// FIX 3: Get USERS with their IP counts
// ============================================
$users = [];

// First, check if user_ips table has user_type column
$has_user_type = false;
$check_col = $conn->query("SHOW COLUMNS FROM user_ips LIKE 'user_type'");
if ($check_col && $check_col->num_rows > 0) {
    $has_user_type = true;
}

// Build WHERE clause for users
if ($has_user_type) {
    $where_clause = $show_all ? "WHERE 1=1" : "WHERE EXISTS (
        SELECT 1 FROM user_ips ui
        WHERE ui.user_id = u.id
        AND ui.user_type = 'user'
        AND ui.login_time >= DATE_SUB(NOW(), INTERVAL 5 DAY)
    )";
    $user_type_filter = "AND user_type = 'user'";
} else {
    $where_clause = $show_all ? "WHERE 1=1" : "WHERE EXISTS (
        SELECT 1 FROM user_ips ui
        WHERE ui.user_id = u.id
        AND ui.login_time >= DATE_SUB(NOW(), INTERVAL 5 DAY)
    )";
    $user_type_filter = "";
}

// Get users
$sql = "SELECT u.id, u.name, u.email, u.created_at, 
        (SELECT COUNT(*) FROM user_ips WHERE user_id = u.id $user_type_filter) as ip_count 
        FROM users u 
        $where_clause 
        ORDER BY u.created_at DESC";

$result = $conn->query($sql);

if ($result) {
    $users = $result->fetch_all(MYSQLI_ASSOC);
    
    // Get IP addresses for each user
    foreach ($users as &$user) {
        $user_id = $user['id'];
        
        if ($has_user_type) {
            $ip_sql = "SELECT ip_address, user_agent, login_time 
                      FROM user_ips 
                      WHERE user_id = $user_id AND user_type = 'user' 
                      ORDER BY login_time DESC LIMIT 5";
        } else {
            $ip_sql = "SELECT ip_address, user_agent, login_time 
                      FROM user_ips 
                      WHERE user_id = $user_id 
                      ORDER BY login_time DESC LIMIT 5";
        }
        
        $ip_result = $conn->query($ip_sql);
        $user['ip_addresses'] = [];
        
        if ($ip_result && $ip_result->num_rows > 0) {
            $user['ip_addresses'] = $ip_result->fetch_all(MYSQLI_ASSOC);
        }
    }
    unset($user);
}

// ============================================
// FIX 4: Get ADMINS with their IP counts
// ============================================
$admins = [];

// Check if admin table exists (your table is 'admin', not 'admins')
$admin_table_exists = false;
$check_admin_table = $conn->query("SHOW TABLES LIKE 'admin'");
if ($check_admin_table && $check_admin_table->num_rows > 0) {
    $admin_table_exists = true;
}

if ($admin_table_exists) {
    $admin_where = $show_all ? "" : "AND EXISTS (
        SELECT 1 FROM user_ips ui
        WHERE ui.user_id = a.id
        AND ui.user_type = 'admin'
        AND ui.login_time >= DATE_SUB(NOW(), INTERVAL 5 DAY)
    )";
    
    $admin_sql = "SELECT a.id, a.name, a.email, a.created_at,
                  (SELECT COUNT(*) FROM user_ips WHERE user_id = a.id AND user_type = 'admin') as ip_count
                  FROM admin a
                  WHERE 1=1 $admin_where
                  ORDER BY a.created_at DESC";
    
    $admin_result = $conn->query($admin_sql);
    
    if ($admin_result) {
        $admins = $admin_result->fetch_all(MYSQLI_ASSOC);
        
        // Get IP addresses for each admin
        foreach ($admins as &$admin) {
            $admin_id = $admin['id'];
            
            if ($has_user_type) {
                $ip_sql = "SELECT ip_address, user_agent, login_time 
                          FROM user_ips 
                          WHERE user_id = $admin_id AND user_type = 'admin' 
                          ORDER BY login_time DESC LIMIT 5";
            } else {
                $ip_sql = "SELECT ip_address, user_agent, login_time 
                          FROM user_ips 
                          WHERE user_id = $admin_id 
                          ORDER BY login_time DESC LIMIT 5";
            }
            
            $ip_result = $conn->query($ip_sql);
            $admin['ip_addresses'] = [];
            
            if ($ip_result && $ip_result->num_rows > 0) {
                $admin['ip_addresses'] = $ip_result->fetch_all(MYSQLI_ASSOC);
            }
        }
        unset($admin);
    }
}

// ============================================
// FIX 5: Debug - Check if we have any data
// ============================================
$debug_mode = isset($_GET['debug']) && $_GET['debug'] === '1';
if ($debug_mode) {
    echo "<div style='background:#f8f9fa; padding:15px; margin:20px; border-left:4px solid #007bff;'>";
    echo "<h3>Debug Information</h3>";
    echo "<p><strong>Users found:</strong> " . count($users) . "</p>";
    echo "<p><strong>Admins found:</strong> " . count($admins) . "</p>";
    echo "<p><strong>Has user_type column:</strong> " . ($has_user_type ? 'Yes' : 'No') . "</p>";
    echo "<p><strong>Admin table exists:</strong> " . ($admin_table_exists ? 'Yes' : 'No') . "</p>";
    
    // Show sample data
    if (count($users) > 0) {
        echo "<p><strong>Sample user IPs:</strong> " . count($users[0]['ip_addresses']) . "</p>";
    }
    echo "</div>";
}
?>

<style>
    .user-ip-card {
        background: var(--card-bg);
        border-radius: 8px;
        box-shadow: var(--shadow);
        margin: 0;
        padding: 20px;
        border: 1px solid rgba(0,0,0,0.08);
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid rgba(0,0,0,0.08);
        flex-wrap: wrap;
    }

    .card-header h2 {
        color: var(--dark);
        font-size: 1.5rem;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .card-header h2 i {
        color: var(--primary);
    }

    .status-badge {
        background: var(--primary);
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.9rem;
        display: inline-block;
        margin: 0 5px;
    }

    .admin-badge {
        background: #ff9800;
        color: white;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        margin-left: 8px;
        display: inline-block;
    }

    .show-all-btn {
        display: inline-block;
        margin-left: 10px;
        padding: 6px 15px;
        background: var(--success);
        color: white;
        text-decoration: none;
        border-radius: 20px;
        transition: background 0.3s;
        font-size: 0.9rem;
        border: none;
        cursor: pointer;
    }

    .show-all-btn:hover {
        background: #27ae60;
    }

    .filter-buttons {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }

    .filter-btn {
        padding: 5px 12px;
        background: var(--muted);
        color: var(--dark);
        text-decoration: none;
        border-radius: 20px;
        font-size: 0.85rem;
        border: 1px solid rgba(0,0,0,0.1);
    }

    .filter-btn.active {
        background: var(--primary);
        color: white;
    }

    .user-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
        padding: 10px 0;
    }

    .user-card {
        background: var(--muted);
        border-radius: 8px;
        padding: 18px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(0,0,0,0.05);
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .user-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .user-card.admin-card {
        border-left: 4px solid #ff9800;
    }

    .user-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
    }

    .user-card-header h3 {
        margin: 0;
        color: var(--dark);
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
    }

    .user-email {
        color: var(--gray);
        font-size: 0.85rem;
        margin-top: 4px;
        word-break: break-all;
    }

    .user-meta {
        color: var(--gray);
        font-size: 0.85rem;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(0,0,0,0.02);
        padding: 8px 12px;
        border-radius: 20px;
    }

    .ip-list {
        background: var(--card-bg);
        border-radius: 6px;
        padding: 12px;
        border: 1px solid rgba(0,0,0,0.05);
    }

    .ip-list h4 {
        margin: 0 0 12px 0;
        color: var(--dark);
        font-size: 0.95rem;
        font-weight: 600;
    }

    .ip-item {
        display: flex;
        justify-content: space-between;
        padding: 10px;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        transition: background-color 0.2s;
    }

    .ip-item:last-child {
        border-bottom: none;
    }

    .ip-item:hover {
        background-color: var(--muted);
    }

    .ip-address {
        color: var(--dark);
        font-weight: 600;
        margin-bottom: 4px;
        font-size: 0.95rem;
    }

    .user-agent {
        color: var(--gray);
        font-size: 0.8rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 200px;
    }

    .ip-time {
        color: var(--gray);
        font-size: 0.8rem;
        white-space: nowrap;
        background: rgba(0,0,0,0.03);
        padding: 3px 8px;
        border-radius: 12px;
        height: fit-content;
    }

    .show-more-btn {
        width: 100%;
        padding: 10px;
        margin-top: 12px;
        background: var(--muted);
        border: 1px solid rgba(0,0,0,0.1);
        border-radius: 20px;
        color: var(--dark);
        cursor: pointer;
        transition: all 0.3s;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .show-more-btn:hover {
        background: var(--primary);
        color: white;
    }

    .hidden {
        display: none;
    }

    .no-ips {
        text-align: center;
        padding: 30px;
        color: var(--gray);
        font-style: italic;
        background: var(--muted);
        border-radius: 8px;
    }

    .section-title {
        margin: 30px 0 15px 0;
        padding-bottom: 10px;
        border-bottom: 2px solid var(--primary);
        color: var(--dark);
        font-size: 1.3rem;
    }

    @media (max-width: 768px) {
        .user-grid {
            grid-template-columns: 1fr;
        }

        .card-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }

        .ip-item {
            flex-direction: column;
            gap: 8px;
        }

        .user-ip-card {
            padding: 15px;
        }
        
        .user-agent {
            max-width: 100%;
        }
    }
</style>

<div class="main-content">
    <div class="user-ip-card">
        <div class="card-header">
            <h2>
                <i class="fas fa-network-wired"></i> 
                IP Address Tracking
            </h2>
            <div>
                <span class="status-badge">
                    <i class="fas fa-users"></i> Users: <?= count($users) ?>
                </span>
                <?php if (count($admins) > 0): ?>
                <span class="status-badge" style="background: #ff9800;">
                    <i class="fas fa-user-shield"></i> Admins: <?= count($admins) ?>
                </span>
                <?php endif; ?>
                <a href="?show_all=<?= $show_all ? '0' : '1' ?><?= isset($_GET['filter']) ? '&filter='.$_GET['filter'] : '' ?>" 
                   class="show-all-btn">
                    <?= $show_all ? 'Show Recent (5 Days)' : 'Show All Users' ?>
                </a>
                <?php if ($debug_mode): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['debug' => '0'])) ?>" 
                   class="show-all-btn" style="background: #6c757d;">
                    Hide Debug
                </a>
                <?php else: ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['debug' => '1'])) ?>" 
                   class="show-all-btn" style="background: #17a2b8;">
                    Debug
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- FIX 6: Show message if no data found -->
        <?php if (empty($users) && empty($admins)): ?>
            <div class="no-ips">
                <i class="fas fa-info-circle" style="font-size: 48px; color: var(--gray); margin-bottom: 15px;"></i>
                <h3>No IP tracking data found</h3>
                <p style="margin-top: 15px;">
                    <?php if ($show_all): ?>
                        No users or admins found in the database.
                    <?php else: ?>
                        No users with activity in the last 5 days.
                    <?php endif; ?>
                </p>
                <div style="margin-top: 20px;">
                    <a href="?show_all=1" class="show-all-btn" style="background: var(--primary);">
                        Show All Users
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- FIX 7: Display USERS section -->
        <?php if (!empty($users)): ?>
            <h3 class="section-title">
                <i class="fas fa-users" style="color: var(--primary);"></i> 
                Regular Users
            </h3>
            <div class="user-grid">
                <?php foreach ($users as $user): ?>
                    <div class="user-card">
                        <div class="user-card-header">
                            <div>
                                <h3><?= htmlspecialchars($user['name'] ?: 'No Name') ?></h3>
                                <div class="user-email">
                                    <i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?>
                                </div>
                            </div>
                            <span class="status-badge">
                                <i class="fas fa-ip"></i> <?= $user['ip_count'] ?> IPs
                            </span>
                        </div>
                        <div class="user-meta">
                            <i class="fas fa-calendar-alt"></i> 
                            Joined: <?= date('M d, Y', strtotime($user['created_at'])) ?>
                            <i class="fas fa-id-card" style="margin-left: 10px;"></i> 
                            ID: <?= $user['id'] ?>
                        </div>
                        <div class="ip-list">
                            <h4>
                                <i class="fas fa-history"></i> 
                                Recent IP Addresses (Last 5):
                            </h4>
                            <?php if (!empty($user['ip_addresses'])): ?>
                                <?php foreach ($user['ip_addresses'] as $index => $ip): ?>
                                    <div class="ip-item <?= $index >= 3 ? 'hidden' : '' ?>">
                                        <div style="flex: 1;">
                                            <div class="ip-address">
                                                <i class="fas fa-network-wired"></i> 
                                                <?= htmlspecialchars($ip['ip_address']) ?>
                                            </div>
                                            <div class="user-agent" title="<?= htmlspecialchars($ip['user_agent'] ?? '') ?>">
                                                <i class="fas fa-globe"></i> 
                                                <?= htmlspecialchars(substr($ip['user_agent'] ?? 'Unknown', 0, 40)) ?>...
                                            </div>
                                        </div>
                                        <div class="ip-time">
                                            <i class="fas fa-clock"></i> 
                                            <?= date('M d, H:i', strtotime($ip['login_time'])) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($user['ip_addresses']) > 3): ?>
                                    <button class="show-more-btn" onclick="toggleIPs(this)">
                                        <i class="fas fa-chevron-down"></i> Show More
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="no-ips" style="padding: 15px;">
                                    <i class="fas fa-exclamation-circle"></i> 
                                    No IP addresses recorded yet
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- FIX 8: Display ADMINS section -->
        <?php if (!empty($admins)): ?>
            <h3 class="section-title" style="border-bottom-color: #ff9800;">
                <i class="fas fa-user-shield" style="color: #ff9800;"></i> 
                Admin Users
            </h3>
            <div class="user-grid">
                <?php foreach ($admins as $admin): ?>
                    <div class="user-card admin-card">
                        <div class="user-card-header">
                            <div>
                                <h3>
                                    <?= htmlspecialchars($admin['name'] ?: 'Admin') ?>
                                    <span class="admin-badge">
                                        <i class="fas fa-shield-alt"></i> Admin
                                    </span>
                                </h3>
                                <div class="user-email">
                                    <i class="fas fa-envelope"></i> <?= htmlspecialchars($admin['email']) ?>
                                </div>
                            </div>
                            <span class="status-badge" style="background: #ff9800;">
                                <i class="fas fa-ip"></i> <?= $admin['ip_count'] ?> IPs
                            </span>
                        </div>
                        <div class="user-meta">
                            <i class="fas fa-calendar-alt"></i> 
                            Joined: <?= date('M d, Y', strtotime($admin['created_at'])) ?>
                            <i class="fas fa-id-card" style="margin-left: 10px;"></i> 
                            ID: <?= $admin['id'] ?>
                        </div>
                        <div class="ip-list">
                            <h4>
                                <i class="fas fa-history"></i> 
                                Recent IP Addresses (Last 5):
                            </h4>
                            <?php if (!empty($admin['ip_addresses'])): ?>
                                <?php foreach ($admin['ip_addresses'] as $index => $ip): ?>
                                    <div class="ip-item <?= $index >= 3 ? 'hidden' : '' ?>">
                                        <div style="flex: 1;">
                                            <div class="ip-address">
                                                <i class="fas fa-network-wired"></i> 
                                                <?= htmlspecialchars($ip['ip_address']) ?>
                                            </div>
                                            <div class="user-agent" title="<?= htmlspecialchars($ip['user_agent'] ?? '') ?>">
                                                <i class="fas fa-globe"></i> 
                                                <?= htmlspecialchars(substr($ip['user_agent'] ?? 'Unknown', 0, 40)) ?>...
                                            </div>
                                        </div>
                                        <div class="ip-time">
                                            <i class="fas fa-clock"></i> 
                                            <?= date('M d, H:i', strtotime($ip['login_time'])) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($admin['ip_addresses']) > 3): ?>
                                    <button class="show-more-btn" onclick="toggleIPs(this)">
                                        <i class="fas fa-chevron-down"></i> Show More
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="no-ips" style="padding: 15px;">
                                    <i class="fas fa-exclamation-circle"></i> 
                                    No IP addresses recorded yet
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- FIX 9: Show sample data if absolutely nothing found -->
        <?php if (empty($users) && empty($admins) && $debug_mode): ?>
        <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
            <h4><i class="fas fa-database"></i> Database Troubleshooting</h4>
            <p>Your database has users but no IP tracking data. To add sample data, run:</p>
            <pre style="background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto;">
-- Add sample IP data for users
INSERT INTO user_ips (user_id, ip_address, user_agent, login_time) 
SELECT id, '127.0.0.1', 'Sample User Agent', DATE_SUB(NOW(), INTERVAL RAND()*30 DAY) 
FROM users LIMIT 20;

-- Add sample IP data for admin
INSERT INTO user_ips (user_id, ip_address, user_agent, login_time) 
SELECT id, '127.0.0.1', 'Sample Admin Agent', NOW() 
FROM admin WHERE id = 1;
            </pre>
            <a href="?show_all=1&debug=1&add_sample=1" class="show-all-btn" style="background: #ffc107; color: #000; margin-top: 10px;">
                <i class="fas fa-vial"></i> Add Sample Data
            </a>
        </div>
        <?php endif; ?>
        
        <!-- FIX 10: Handle sample data addition -->
        <?php if (isset($_GET['add_sample']) && $_GET['add_sample'] == '1'): ?>
        <?php
        // Add sample IP data for users
        $sample_count = 0;
        $user_result = $conn->query("SELECT id FROM users LIMIT 10");
        if ($user_result) {
            while ($row = $user_result->fetch_assoc()) {
                $ip = rand(1,255) . '.' . rand(1,255) . '.' . rand(1,255) . '.' . rand(1,255);
                $days_ago = rand(0, 10);
                $conn->query("INSERT INTO user_ips (user_id, ip_address, user_agent, login_time) 
                             VALUES ({$row['id']}, '$ip', 'Mozilla/5.0 (Sample Browser)', DATE_SUB(NOW(), INTERVAL $days_ago DAY))");
                $sample_count++;
            }
        }
        
        // Add sample IP for admin
        $admin_result = $conn->query("SELECT id FROM admin LIMIT 1");
        if ($admin_result && $admin_row = $admin_result->fetch_assoc()) {
            $conn->query("INSERT INTO user_ips (user_id, ip_address, user_agent, login_time) 
                         VALUES ({$admin_row['id']}, '192.168.1.1', 'Admin Chrome Browser', NOW())");
            $sample_count++;
        }
        
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 20px; border-radius: 4px;'>
                <i class='fas fa-check-circle'></i> Added $sample_count sample IP records! 
                <a href='?show_all=1' style='margin-left: 15px; color: #155724; font-weight: bold;'>Refresh Page</a>
              </div>";
        ?>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleIPs(button) {
    const card = button.closest('.user-card');
    const hiddenIPs = card.querySelectorAll('.ip-item.hidden');
    
    if (hiddenIPs.length > 0) {
        hiddenIPs.forEach(ip => {
            ip.classList.remove('hidden');
        });
        button.innerHTML = '<i class="fas fa-chevron-up"></i> Show Less';
    } else {
        const allIPs = card.querySelectorAll('.ip-item');
        allIPs.forEach((ip, index) => {
            if (index >= 3) {
                ip.classList.add('hidden');
            }
        });
        button.innerHTML = '<i class="fas fa-chevron-down"></i> Show More';
    }
}

// Auto-refresh debug info
document.addEventListener('DOMContentLoaded', function() {
    // Check if URL has debug parameter
    if (window.location.href.includes('debug=1')) {
        console.log('Debug mode enabled');
    }
});
</script>

<?php include 'admin_footer.php'; ?>