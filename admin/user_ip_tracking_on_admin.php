<?php
session_start(); // Start session for authentication
if (!isset($_SESSION['admin_logged_in'])) { // Check if admin is logged in
    header('Location: login.php'); // Redirect to login page if not logged in
    exit(); // Stop further execution
}

include '../database/dbconfig.php'; // Include database configuration
include 'ip_tracking.php'; // Include IP tracking functions

// Get admin info from session
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];
$admin_email = $_SESSION['admin_email'];

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
$result = $conn->query("
    SELECT u.id, u.name, u.email, u.created_at,
           (SELECT COUNT(*) FROM user_ips WHERE user_id = u.id) as ip_count
    FROM users u 
    $where_clause
    ORDER BY u.created_at DESC
");

// If query succeeded, fetch user data
if ($result) {
    $users = $result->fetch_all(MYSQLI_ASSOC);
    
    // For each user, fetch their recent IP addresses
    foreach ($users as &$user) {
        $user_id = $user['id'];
        $ip_result = $conn->query("
            SELECT ip_address, user_agent, login_time 
            FROM user_ips 
            WHERE user_id = $user_id 
            ORDER BY login_time DESC
            LIMIT 5
        ");
        
        $user['ip_addresses'] = [];
        if ($ip_result && $ip_result->num_rows > 0) {
            $user['ip_addresses'] = $ip_result->fetch_all(MYSQLI_ASSOC);
        }
    }
    unset($user); // Break reference for safety
}

// Get admin info with profile picture from database
$admin_id = $_SESSION['admin_id'];
$admin_query = $conn->prepare("SELECT name, email, profile_pic FROM admin WHERE id = ?");
$admin_query->bind_param("i", $admin_id);
$admin_query->execute();
$admin_result = $admin_query->get_result();
$admin = $admin_result->fetch_assoc() ?? ['name' => 'Unknown', 'email' => '', 'profile_pic' => NULL];

// Set admin variables for display
$admin_name = $admin['name'];
$admin_email = $admin['email'];
$admin_profile_pic = $admin['profile_pic'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TripMate Admin - User IP Tracking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb; /* Modern blue */
            --secondary: #10b981; /* Vibrant green */
            --accent: #f43f5e; /* Modern red */
            --light: #f8fafc; /* Light background */
            --dark: #1e293b; /* Dark slate */
            --gray: #64748b; /* Neutral gray */
            --warning: #f59e0b; /* Amber */
            --danger: #dc2626; /* Red */
            --success: #16a34a; /* Green */
            --info: #0891b2; /* Cyan */
            --sidebar-bg: #0f172a; /* Darker slate for sidebar */
            --card-bg: #ffffff;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        body {
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.5;
            padding-top: 80px;
        }
        
        /* Top Bar Styles */
        .top-bar {
            background: linear-gradient(135deg, var(--primary) 0%, #3b82f6 100%);
            color: white;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            height: 80px;
            box-shadow: var(--shadow);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .logo h1 {
            font-size: 1.75rem;
            font-weight: 700;
        }
        
        .logo i {
            font-size: 2rem;
        }
        
        .top-bar-actions {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            background-color: rgba(255, 255, 255, 0.15);
            transition: all 0.3s ease;
        }
        
        .user-profile:hover {
            background-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
        }
        
        .user-profile img {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.4);
        }
        
        .user-profile span {
            font-weight: 600;
            font-size: 1rem;
        }
        
        .dropdown {
            display: none;
            position: absolute;
            right: 1.5rem;
            top: 5rem;
            background: var(--card-bg);
            color: var(--dark);
            box-shadow: var(--shadow);
            border-radius: 0.5rem;
            min-width: 12rem;
            z-index: 1001;
            overflow: hidden;
        }
        
        .dropdown.active {
            display: block;
        }
        
        .dropdown a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--dark);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .dropdown a:hover {
            background: var(--light);
            color: var(--primary);
        }
        
        .dropdown a i {
            color: var(--primary);
            width: 1.25rem;
        }
        
        /* Main Layout */
        .container {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: calc(100vh - 80px);
        }
        
        /* Sidebar */
        .sidebar {
            background: var(--sidebar-bg);
            color: white;
            padding: 1.5rem 0;
            position: fixed;
            width: 260px;
            height: calc(100vh - 80px);
            overflow-y: auto;
            top: 80px;
            left: 0;
            z-index: 999;
        }
        
        .user-info {
            text-align: center;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .user-avatar {
            width: 5rem;
            height: 5rem;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }
        
        .user-avatar:hover {
            transform: scale(1.05);
        }
        
        .user-name {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .user-role {
            background: var(--warning);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .menu {
            list-style: none;
        }
        
        .menu-item {
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .menu-item:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateX(4px);
        }
        
        .menu-item.active {
            background: linear-gradient(to right, rgba(255, 255, 255, 0.1), transparent);
            border-left: 4px solid var(--primary);
        }
        
        .menu-item i {
            margin-right: 0.75rem;
            font-size: 1.125rem;
            width: 1.5rem;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            grid-column: 2;
            padding: 2rem;
            width: calc(100vw - 260px);
            margin-left: 1px;
        }
        
        /* User IP Tracking Specific Styles */
        .user-ip-card {
            background: var(--card-bg);
            border-radius: 0.75rem;
            padding: 1.75rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            transition: transform 0.3s ease;
        }

        .user-ip-card:hover {
            transform: translateY(-2px);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.75rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .card-header h2 {
            margin: 0;
            color: var(--primary);
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-header h2 i {
            color: var(--secondary);
            font-size: 1.25rem;
        }

        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
            background: #dbeafe;
            color: var(--primary);
        }

        .show-all-btn {
            padding: 0.6rem 1.25rem;
            background: var(--secondary);
            color: white;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-left: 1rem;
        }

        .show-all-btn:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .show-more-btn {
            padding: 0.5rem 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            margin-top: 1rem;
            display: block;
            margin-left: auto;
            transition: all 0.3s ease;
        }

        .show-more-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .user-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 1.75rem;
        }

        .user-card {
            background: var(--card-bg);
            border-radius: 0.5rem;
            padding: 1.75rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
        }

        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .user-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.25rem;
        }

        .user-card h3 {
            margin: 0 0 0.5rem 0;
            color: var(--primary);
            font-size: 1.25rem;
            font-weight: 600;
        }

        .user-email {
            color: var(--accent);
            font-size: 0.875rem;
            word-break: break-word;
            margin-bottom: 0.75rem;
        }

        .user-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--gray);
            margin-bottom: 1.25rem;
        }

        .user-meta i {
            color: var(--secondary);
        }

        .ip-list {
            margin-top: 1.25rem;
            border-top: 1px solid #e5e7eb;
            padding-top: 1.25rem;
        }

        .ip-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s ease;
        }

        .ip-item:hover {
            background: #f8fafc;
        }

        .ip-item:last-child {
            border-bottom: none;
        }

        .ip-address {
            font-family: 'JetBrains Mono', monospace;
            background: #f1f5f9;
            padding: 0.3rem 0.6rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
        }

        .ip-time {
            color: var(--gray);
            font-size: 0.875rem;
            text-align: right;
            font-weight: 500;
        }

        .no-ips {
            color: var(--gray);
            font-style: italic;
            text-align: center;
            padding: 1.25rem;
            font-size: 0.875rem;
        }

        .user-agent {
            font-size: 0.75rem;
            color: var(--gray);
            margin-top: 0.3rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }

        .ip-item.hidden {
            display: none;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                top: 0;
            }
            
            .main-content {
                grid-column: 1;
                width: 100%;
                margin-left: 0;
                padding: 1.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .logo h1 {
                font-size: 1.5rem;
            }
            
            .top-bar {
                height: 70px;
            }
            
            body {
                padding-top: 70px;
            }
            
            .user-profile span {
                display: none;
            }

            .user-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="logo">
            <i class="fas fa-globe-americas"></i>
            <h1>TripMate Admin</h1>
        </div>
        
        <div class="top-bar-actions">
            <div class="user-profile" id="userProfile">
                <img src="<?= $admin_profile_pic ? $admin_profile_pic : 'https://via.placeholder.com/40' ?>" alt="Admin">
                <span><?= htmlspecialchars($admin_name) ?></span>
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>
        
        <div class="dropdown" id="profileDropdown">
            <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <!-- Main Layout -->
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <!-- <div class="user-info">
                <img src="<?= $admin_profile_pic ? $admin_profile_pic : 'https://via.placeholder.com/80' ?>" alt="Admin" class="user-avatar">
                <div class="user-name"><?= htmlspecialchars($admin_name) ?></div>
                <div class="user-role">Administrator</div>
            </div> -->
            <ul class="menu">
                <li class="menu-item "><a href="admin_dasbord.php" style="color:inherit;text-decoration:none"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li class="menu-item"><a href="add_destination_on_admin.php" style="color:inherit;text-decoration:none"><i class="fas fa-map-marker-alt"></i> Destinations</a></li>
                <li class="menu-item"><a href="user_present_chack_on_admin.php" style="color:inherit;text-decoration:none"><i class="fas fa-users"></i> Users</a></li>
                <li class="menu-item"><a href="user_join_analysis_on_ADMIN.php" style="color:inherit;text-decoration:none"><i class="fas fa-chart-line"></i> Analysis</a></li>
                <li class="menu-item"><a href="#" style="color:inherit;text-decoration:none"><i class="fas fa-envelope"></i> Messages</a></li>
                <li class="menu-item"><a href="#" style="color:inherit;text-decoration:none"><i class="fas fa-cog"></i> Settings</a></li>
                <li class="menu-item active"><a href="user_ip_tracking_on_admin.php" style="color:inherit;text-decoration:none"><i class="fas fa-network-wired"></i> User IPs</a></li>
                <!-- <li class="menu-item"><a href="../auth/logout.php" style="color:inherit;text-decoration:none"><i class="fas fa-sign-out-alt"></i> Logout</a></li> -->
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="user-ip-card">
                <div class="card-header">
                    <h2><i class="fas fa-network-wired"></i> User IP Address Tracking</h2>
                    <div>
                        <span class="status-badge">Total Users: <?= count($users) ?></span>
                        <a href="?show_all=<?= $show_all ? '0' : '1' ?>" class="show-all-btn">
                            <?= $show_all ? 'Show Recent Users' : 'Show All Users' ?>
                        </a>
                    </div>
                </div>

                <div class="user-grid">
                    <?php if (empty($users)): ?>
                        <div class="no-ips">
                            <?= $show_all ? 'No users found' : 'No users with activity in the last 5 days' ?>
                        </div>
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
                            
                            <div class="user-meta">
                                <i class="fas fa-calendar-alt"></i>
                                Joined: <?= date('M d, Y', strtotime($user['created_at'])) ?>
                            </div>

                            <div class="ip-list">
                                <h4>Recent IP Addresses:</h4>
                                <?php if (!empty($user['ip_addresses'])): ?>
                                    <?php foreach ($user['ip_addresses'] as $index => $ip): ?>
                                    <div class="ip-item <?= $index > 0 ? 'hidden' : '' ?>" data-user-id="<?= $user['id'] ?>">
                                        <div>
                                            <div class="ip-address"><?= htmlspecialchars($ip['ip_address']) ?></div>
                                            <div class="user-agent" title="<?= htmlspecialchars($ip['user_agent']) ?>">
                                                <?= htmlspecialchars($ip['user_agent']) ?>
                                            </div>
                                        </div>
                                        <div class="ip-time"><?= date('M d, Y H:i', strtotime($ip['login_time'])) ?></div>
                                    </div>
                                    <?php endforeach; ?>
                                    <?php if (count($user['ip_addresses']) > 1): ?>
                                        <button class="show-more-btn" data-user-id="<?= $user['id'] ?>">
                                            Show More IPs
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="no-ips">No IP addresses recorded yet</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Dropdown toggle JS
        document.addEventListener('DOMContentLoaded', function() {
            const userProfile = document.getElementById('userProfile');
            const dropdown = document.getElementById('profileDropdown');

            userProfile.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.classList.toggle('active');
            });

            // Close when clicking outside
            document.addEventListener('click', function(e) {
                if (!userProfile.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.remove('active');
                }
            });

            // Close on Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    dropdown.classList.remove('active');
                }
            });

            // Show More IPs button functionality
            document.querySelectorAll('.show-more-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    const ipItems = document.querySelectorAll(`.ip-item[data-user-id="${userId}"]`);
                    const isShowingMore = this.textContent === 'Show More IPs';
                    
                    ipItems.forEach((item, index) => {
                        if (index > 0) {
                            item.classList.toggle('hidden', !isShowingMore);
                        }
                    });
                    
                    this.textContent = isShowingMore ? 'Show Less IPs' : 'Show More IPs';
                });
            });
        });
    </script>
</body>
</html>