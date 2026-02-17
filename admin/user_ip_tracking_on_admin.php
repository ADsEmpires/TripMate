<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

include '../database/dbconfig.php';
include 'admin_header.php';

// Determine if we should show all users or only recent ones
$show_all = isset($_GET['show_all']) && $_GET['show_all'] === '1';

// Prepare SQL WHERE clause for filtering users by recent IP activity
$users = [];
$where_clause = $show_all ? '' : "WHERE EXISTS (
    SELECT 1 FROM user_ips ui
    WHERE ui.user_id = u.id
    AND ui.login_time >= DATE_SUB(NOW(), INTERVAL 5 DAY)
)";

// Fetch users and their IP count from database
$sql = "SELECT u.id, u.name, u.email, u.created_at, (SELECT COUNT(*) FROM user_ips WHERE user_id = u.id) as ip_count FROM users u " . $where_clause . " ORDER BY u.created_at DESC";
$result = $conn->query($sql);

// If query succeeded, fetch user data
if ($result) {
    $users = $result->fetch_all(MYSQLI_ASSOC);
    
    foreach ($users as &$user) {
        $user_id = $user['id'];
        $ip_sql = "SELECT ip_address, user_agent, login_time FROM user_ips WHERE user_id = $user_id ORDER BY login_time DESC LIMIT 5";
        $ip_result = $conn->query($ip_sql);

        $user['ip_addresses'] = [];
        if ($ip_result && $ip_result->num_rows > 0) {
            $user['ip_addresses'] = $ip_result->fetch_all(MYSQLI_ASSOC);
        }
    }
    unset($user);
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
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.9rem;
    }

    .show-all-btn {
        display: inline-block;
        margin-left: 10px;
        padding: 6px 12px;
        background: var(--success);
        color: white;
        text-decoration: none;
        border-radius: 4px;
        transition: background 0.3s;
        font-size: 0.9rem;
    }

    .show-all-btn:hover {
        background: #27ae60;
    }

    .user-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        padding: 10px;
    }

    .user-card {
        background: var(--muted);
        border-radius: 6px;
        padding: 15px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0,0,0,0.05);
    }

    .user-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 10px;
    }

    .user-card-header h3 {
        margin: 0;
        color: var(--dark);
        font-size: 1.2rem;
    }

    .user-email {
        color: var(--gray);
        font-size: 0.9rem;
        margin-top: 3px;
    }

    .user-meta {
        color: var(--gray);
        font-size: 0.9rem;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .ip-list {
        background: var(--card-bg);
        border-radius: 4px;
        padding: 10px;
        border: 1px solid rgba(0,0,0,0.05);
    }

    .ip-list h4 {
        margin: 0 0 10px 0;
        color: var(--dark);
        font-size: 1rem;
    }

    .ip-item {
        display: flex;
        justify-content: space-between;
        padding: 8px;
        border-bottom: 1px solid rgba(0,0,0,0.08);
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
        font-weight: 500;
        margin-bottom: 3px;
    }

    .user-agent {
        color: var(--gray);
        font-size: 0.85rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 200px;
    }

    .ip-time {
        color: var(--gray);
        font-size: 0.85rem;
        white-space: nowrap;
    }

    .show-more-btn {
        width: 100%;
        padding: 8px;
        margin-top: 10px;
        background: var(--muted);
        border: none;
        border-radius: 4px;
        color: var(--dark);
        cursor: pointer;
        transition: background 0.3s;
        font-size: 0.9rem;
    }

    .show-more-btn:hover {
        background: rgba(0,0,0,0.1);
    }

    .hidden {
        display: none;
    }

    .no-ips {
        text-align: center;
        padding: 20px;
        color: var(--gray);
        font-style: italic;
    }

    @media (max-width: 768px) {
        .user-grid {
            grid-template-columns: 1fr;
        }

        .card-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        .ip-item {
            flex-direction: column;
            gap: 5px;
        }

        .user-ip-card {
            margin: 0;
            padding: 15px;
        }
    }
</style>

<div class="main-content">
    <div class="user-ip-card">
        <div class="card-header">
            <h2><i class="fas fa-network-wired"></i> User IP Address Tracking</h2>
            <div>
                <span class="status-badge">Total Users: <?= count($users) ?></span>
                <a href="?show_all=<?= $show_all ? '0' : '1' ?>" class="show-all-btn"><?= $show_all ? 'Show Recent Users' : 'Show All Users' ?></a>
            </div>
        </div>

        <div class="user-grid">
            <?php if (empty($users)): ?>
                <div class="no-ips"><?= $show_all ? 'No users found' : 'No users with activity in the last 5 days' ?></div>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <div class="user-card">
                        <div class="user-card-header">
                            <div>
                                <h3><?= htmlspecialchars($user['name']) ?></h3>
                                <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                            </div>
                            <span class="status-badge"><?= $user['ip_count'] ?> IPs</span>
                        </div>
                        <div class="user-meta"><i class="fas fa-calendar-alt"></i> Joined: <?= date('M d, Y', strtotime($user['created_at'])) ?></div>
                        <div class="ip-list">
                            <h4>Recent IP Addresses:</h4>
                            <?php if (!empty($user['ip_addresses'])): ?>
                                <?php foreach ($user['ip_addresses'] as $index => $ip): ?>
                                    <div class="ip-item <?= $index >= 3 ? 'hidden' : '' ?>">
                                        <div>
                                            <div class="ip-address"><?= htmlspecialchars($ip['ip_address']) ?></div>
                                            <div class="user-agent" title="<?= htmlspecialchars($ip['user_agent']) ?>">
                                                <?= htmlspecialchars(substr($ip['user_agent'], 0, 30)) ?>...
                                            </div>
                                        </div>
                                        <div class="ip-time"><?= date('M d, H:i', strtotime($ip['login_time'])) ?></div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($user['ip_addresses']) > 3): ?>
                                    <button class="show-more-btn" onclick="toggleIPs(this)">Show More</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="no-ips">No IP addresses recorded</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
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
            button.textContent = 'Show Less';
        } else {
            const allIPs = card.querySelectorAll('.ip-item');
            allIPs.forEach((ip, index) => {
                if (index >= 3) {
                    ip.classList.add('hidden');
                }
            });
            button.textContent = 'Show More';
        }
    }
</script>

<?php include 'admin_footer.php'; ?>