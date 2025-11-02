<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

include '../database/dbconfig.php';

// Breadcrumb management
$current_page_title = 'Users';
$previous_admin_page = $_SESSION['admin_last_page'] ?? null;
$previous_page_name = $previous_admin_page['name'] ?? null;
$previous_page_url = $previous_admin_page['url'] ?? null;
$_SESSION['admin_last_page'] = ['name' => $current_page_title, 'url' => basename($_SERVER['PHP_SELF'])];

// Get admin info
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];
$admin_email = $_SESSION['admin_email'];

// Fetch all users from database
$users = [];
$result = $conn->query("SELECT id, name, email, created_at FROM users ORDER BY created_at DESC");
if ($result) {
    $users = $result->fetch_all(MYSQLI_ASSOC);
}

// Get admin info with profile picture
$admin_id = $_SESSION['admin_id'];
$admin_query = $conn->prepare("SELECT name, email, profile_pic FROM admin WHERE id = ?");
$admin_query->bind_param("i", $admin_id);
$admin_query->execute();
$admin_result = $admin_query->get_result();
$admin = $admin_result->fetch_assoc() ?? ['name' => 'Unknown', 'email' => '', 'profile_pic' => NULL];

$admin_name = $admin['name'];
$admin_email = $admin['email'];
$admin_profile_pic = $admin['profile_pic'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TripMate Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2ecc71;
            --accent: #e74c3c;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
            --warning: #f39c12;
            --danger: #dc3545;
            --success: #28a745;
            --info: #17a2b8;
            --sidebar-bg: #1A252F;
        }

        /* Breadcrumb styles */
        .breadcrumb {
            display: flex;
            gap: 0.6rem;
            align-items: center;
            background: linear-gradient(90deg, rgba(22,3,79,0.06), rgba(26,82,118,0.03));
            padding: 10px 14px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(3,7,18,0.04);
            margin-bottom: 1.25rem;
            font-size: 0.95rem;
            color: #07122b;
            font-weight: 600;
        }

        .breadcrumb a {
            color: #0f172a;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 8px;
            transition: all 0.18s ease;
        }

        .breadcrumb a:hover {
            background: linear-gradient(90deg, rgba(21,47,78,0.08), rgba(26,82,118,0.06));
            transform: translateY(-2px);
            color: var(--primary);
            box-shadow: 0 6px 18px rgba(26,82,118,0.06);
        }

        .breadcrumb .current {
            background: linear-gradient(90deg, #16034f, #1a5276);
            color: white;
            padding: 6px 12px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 6px 18px rgba(22,3,79,0.15);
        }

        .breadcrumb .sep {
            color: #9aa6b2;
            font-weight: 700;
        }

        /* rest of existing styles (kept as original) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7f9;
            color: #333;
            line-height: 1.6;
            padding-top: 80px;
        }

        /* Top Bar Styles */
        .top-bar {
            background: linear-gradient(to right, var(--primary), #1a5276);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            height: 80px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo h1 {
            font-size: 28px;
            font-weight: 700;
            margin-left: 10px;
        }

        .logo i {
            font-size: 32px;
        }

        .top-bar-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 20px;
            background-color: rgba(255, 255, 255, 0.1);
            transition: background-color 0.3s;
        }

        .user-profile:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .user-profile span {
            font-weight: 600;
            font-size: 16px;
        }

        .dropdown {
            display: none;
            position: absolute;
            right: 20px;
            top: 70px;
            background: white;
            color: var(--dark);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            min-width: 200px;
            z-index: 1001;
            overflow: hidden;
        }

        .dropdown.active {
            display: block;
        }

        .dropdown a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            color: var(--dark);
            text-decoration: none;
            transition: background 0.2s;
            border-bottom: 1px solid #f0f0f0;
        }

        .dropdown a:last-child {
            border-bottom: none;
        }

        .dropdown a:hover {
            background: var(--light);
        }

        .dropdown a i {
            color: var(--primary);
            width: 20px;
        }

        /* Main Layout */
        .container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: calc(100vh - 80px);
        }

        /* Sidebar */
        .sidebar {
            background: var(--sidebar-bg);
            color: white;
            padding: 20px 0;
            position: fixed;
            width: 250px;
            height: calc(100vh - 80px);
            overflow-y: auto;
            top: 80px;
            left: 0;
            z-index: 999;
        }

        .user-info {
            text-align: center;
            padding: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            margin-bottom: 15px;
        }

        .user-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .user-role {
            background-color: var(--warning);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
            margin-bottom: 15px;
        }

        .menu {
            list-style: none;
        }

        .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            cursor: pointer;
        }

        .menu-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .menu-item.active {
            background-color: rgba(255, 255, 255, 0.2);
            border-left: 4px solid var(--primary);
        }

        .menu-item i {
            margin-right: 10px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            grid-column: 2;
            padding: 25px;
            width: calc(100vw - 250px);
            margin-left: 0px;
        }

        /* Additional Styles from admin_users.php for User Management */
        .user-management-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
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
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header h2 i {
            color: var(--secondary);
        }

        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            background: #c6f6d5;
            color: #2f855a;
        }

        .user-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .user-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border-left: 4px solid var(--accent);
        }

        .user-card h3 {
            margin: 0 0 0.5rem 0;
            color: var(--primary);
            font-size: 1.1rem;
        }

        .user-email {
            color: var(--accent);
            font-size: 0.9rem;
            word-break: break-word;
            margin-bottom: 0.5rem;
        }

        .user-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: var(--gray);
        }

        .user-meta i {
            color: var(--secondary);
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
        }

        btn:hover {
            background: #1a5276;
        }

        .btn:disabled {
            background: #e0e0e0;
            color: #a0a0a0;
            cursor: not-allowed;
        }

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
                padding: 20px;
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
           <ul class="menu">
                <li class="menu-item "><a href="admin_dasbord.php" style="color:inherit;text-decoration:none"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li class="menu-item"><a href="add_destanition_on_admin.php" style="color:inherit;text-decoration:none"><i class="fas fa-map-marker-alt"></i> Destinations</a></li>
                <li class="menu-item active"><a href="user_present_chack_on_admin.php" style="color:inherit;text-decoration:none"><i class="fas fa-users"></i> Users</a></li>
                <li class="menu-item "><a href="user_join_analysis_on_ADMIN.php" style="color:inherit;text-decoration:none"><i class="fas fa-chart-line"></i> Analysis</a></li>
                <li class="menu-item"><a href="#" style="color:inherit;text-decoration:none"><i class="fas fa-envelope"></i> Messages</a></li>
                <li class="menu-item"><a href="#" style="color:inherit;text-decoration:none"><i class="fas fa-cog"></i> Settings</a></li>
                <li class="menu-item"><a href="user_ip_tracking_on_admin.php" style="color:inherit;text-decoration:none"><i class="fas fa-network-wired"></i> User IPs</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Breadcrumb -->
            <div class="breadcrumb" aria-label="Breadcrumb">
                <a href="admin_dasbord.php"><i class="fas fa-home"></i> Home</a>
                <?php if ($previous_page_name): ?>
                    <span class="sep"><i class="fas fa-chevron-right"></i></span>
                    <a href="<?= htmlspecialchars($previous_page_url) ?>"><i class="fas fa-arrow-left"></i> <?= htmlspecialchars($previous_page_name) ?></a>
                <?php endif; ?>
                <span class="sep"><i class="fas fa-chevron-right"></i></span>
                <span class="current"><i class="fas fa-users"></i> <?= htmlspecialchars($current_page_title) ?></span>
            </div>

            <div class="user-management-card">
                <div class="card-header">
                    <h2><i class="fas fa-users"></i> User Management</h2>
                    <span class="status-badge">Total: <?= count($users) ?></span>
                </div>

                <button class="btn" disabled>
                    <i class="fas fa-plus"></i> Add User (Coming Soon)
                </button>

                <div class="user-grid">
                    <?php foreach ($users as $user): ?>
                    <div class="user-card">
                        <h3><?= htmlspecialchars($user['name']) ?></h3>
                        <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                        <div class="user-meta">
                            <i class="fas fa-calendar-alt"></i>
                            Joined: <?= date('M d, Y', strtotime($user['created_at'])) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
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
        });
    </script>
</body>
</html>