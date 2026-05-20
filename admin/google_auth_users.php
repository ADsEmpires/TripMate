<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

include '../database/dbconfig.php';

// Get total count of Google auth users
$total_google_users = $conn->query("SELECT COUNT(*) as total FROM users_google")->fetch_assoc()['total'] ?? 0;

// Get detailed list of Google auth users
$google_users_query = $conn->query("
    SELECT 
        id,
        name,
        email,
        profile_pic,
        provider_id,
        user_level,
        created_at
    FROM users_google
    ORDER BY created_at DESC
");

$google_users = [];
if ($google_users_query && $google_users_query->num_rows > 0) {
    $google_users = $google_users_query->fetch_all(MYSQLI_ASSOC);
}

// Get statistics
$today_google_users = $conn->query("SELECT COUNT(*) as total FROM users_google WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['total'] ?? 0;
$this_week_google_users = $conn->query("SELECT COUNT(*) as total FROM users_google WHERE created_at >= CURDATE() - INTERVAL 6 DAY")->fetch_assoc()['total'] ?? 0;
$this_month_google_users = $conn->query("SELECT COUNT(*) as total FROM users_google WHERE created_at >= CURDATE() - INTERVAL 29 DAY")->fetch_assoc()['total'] ?? 0;

// Get user level distribution
$user_level_stats = $conn->query("SELECT user_level, COUNT(*) as count FROM users_google GROUP BY user_level")->fetch_all(MYSQLI_ASSOC);

// Get total users for percentage calculation
$total_all_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'] ?? 0;
$google_percentage = $total_all_users > 0 ? round(($total_google_users / $total_all_users) * 100, 1) : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Auth Users - TripMate Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #f8fafc;
            --bg-surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #475569;
            --primary: #4f46e5;
            --secondary: #06b6d4;
            --card-border: rgba(79, 70, 229, 0.15);
            --shadow-color: rgba(15, 23, 42, 0.08);
            --glow-color: rgba(6, 182, 212, 0.4);
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
        }

        body.dark-theme {
            --bg-base: #09090b;
            --bg-surface: #18181b;
            --text-main: #f8fafc;
            --text-muted: #cbd5e1;
            --primary: #818cf8;
            --secondary: #22d3ee;
            --card-border: rgba(255, 255, 255, 0.1);
            --shadow-color: rgba(0, 0, 0, 0.6);
            --glow-color: rgba(34, 211, 238, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-base);
            color: var(--text-main);
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header-section {
            background: linear-gradient(135deg, #4285F4, #34A853);
            color: white;
            padding: 2.5rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            box-shadow: 0 15px 35px rgba(66, 133, 244, 0.3);
        }

        .header-section h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-section p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            background: var(--bg-surface);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px var(--shadow-color);
            transition: all 0.3s ease;
        }

        .stat-box:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 40px var(--shadow-color);
            border-color: var(--secondary);
        }

        .stat-box-label {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .stat-box-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-box-subtext {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .stat-box-icon {
            font-size: 2rem;
            color: #4285F4;
            margin-bottom: 1rem;
        }

        .users-section {
            background: var(--bg-surface);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px var(--shadow-color);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table thead {
            background-color: var(--card-border);
            border-bottom: 2px solid var(--card-border);
        }

        .users-table th {
            padding: 1.2rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-main);
            font-size: 0.95rem;
        }

        .users-table tbody tr {
            border-bottom: 1px solid var(--card-border);
            transition: background-color 0.2s ease;
        }

        .users-table tbody tr:hover {
            background-color: var(--card-border);
        }

        .users-table td {
            padding: 1.2rem;
            color: var(--text-main);
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4285F4, #34A853);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
            overflow: hidden;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-details h4 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 0.25rem;
        }

        .user-details p {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.15);
            color: var(--success);
        }

        .badge-info {
            background: rgba(59, 130, 246, 0.15);
            color: var(--info);
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.15);
            color: var(--warning);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .search-box {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 1rem;
            border: 1px solid var(--card-border);
            border-radius: 12px;
            font-size: 1rem;
            background: var(--bg-surface);
            color: var(--text-main);
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--glow-color);
        }

        .search-box i {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .level-distribution {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .level-card {
            background: var(--bg-base);
            border: 2px solid var(--card-border);
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
        }

        .level-card-label {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            font-weight: 600;
        }

        .level-card-count {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .header-section h1 {
                font-size: 1.8rem;
            }

            .users-table {
                font-size: 0.85rem;
            }

            .users-table th,
            .users-table td {
                padding: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header-section">
            <h1>
                <i class="fab fa-google"></i>
                Google Authenticated Users
            </h1>
            <p>View and manage all users who logged in via Google OAuth</p>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-box-icon">
                    <i class="fab fa-google"></i>
                </div>
                <div class="stat-box-label">Total Google Users</div>
                <div class="stat-box-value"><?= number_format($total_google_users) ?></div>
                <div class="stat-box-subtext"><?= $google_percentage ?>% of all users</div>
            </div>

            <div class="stat-box">
                <div class="stat-box-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-box-label">Today's Registrations</div>
                <div class="stat-box-value"><?= number_format($today_google_users) ?></div>
                <div class="stat-box-subtext">New Google auth users</div>
            </div>

            <div class="stat-box">
                <div class="stat-box-icon">
                    <i class="fas fa-calendar-week"></i>
                </div>
                <div class="stat-box-label">This Week</div>
                <div class="stat-box-value"><?= number_format($this_week_google_users) ?></div>
                <div class="stat-box-subtext">Last 7 days</div>
            </div>

            <div class="stat-box">
                <div class="stat-box-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-box-label">This Month</div>
                <div class="stat-box-value"><?= number_format($this_month_google_users) ?></div>
                <div class="stat-box-subtext">Last 30 days</div>
            </div>
        </div>

        <!-- User Level Distribution -->
        <?php if (!empty($user_level_stats)): ?>
        <div style="margin-bottom: 2rem;">
            <h3 class="section-title">
                <i class="fas fa-chart-pie"></i>
                User Level Distribution
            </h3>
            <div class="level-distribution">
                <?php foreach ($user_level_stats as $level): ?>
                <div class="level-card">
                    <div class="level-card-label"><?= ucfirst($level['user_level']) ?></div>
                    <div class="level-card-count"><?= number_format($level['count']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Users Section -->
        <div class="users-section">
            <h3 class="section-title">
                <i class="fas fa-users"></i>
                Google Auth Users List (<?= count($google_users) ?> users)
            </h3>

            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search by name or email..." onkeyup="filterTable()">
            </div>

            <?php if (!empty($google_users)): ?>
            <div style="overflow-x: auto;">
                <table class="users-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Level</th>
                            <th>Provider ID</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($google_users as $user): ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar">
                                        <?php if (!empty($user['profile_pic'])): ?>
                                            <img src="<?= htmlspecialchars($user['profile_pic']) ?>" alt="<?= htmlspecialchars($user['name']) ?>">
                                        <?php else: ?>
                                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="user-details">
                                        <h4><?= htmlspecialchars($user['name']) ?></h4>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <span class="badge badge-<?= $user['user_level'] == 'high' ? 'warning' : 'success' ?>">
                                    <?= ucfirst($user['user_level']) ?>
                                </span>
                            </td>
                            <td>
                                <small style="color: var(--text-muted); font-family: 'Courier New', monospace;">
                                    <?= substr($user['provider_id'], 0, 10) ?>...
                                </small>
                            </td>
                            <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fab fa-google"></i>
                <h3>No Google Auth Users Found</h3>
                <p>There are no users who have registered using Google authentication yet.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function filterTable() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const table = document.getElementById('usersTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            Array.from(rows).forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(searchInput) ? '' : 'none';
            });
        }

        // Apply dark theme if set
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-theme');
        }
    </script>
</body>
</html>
