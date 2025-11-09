<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

include '../database/dbconfig.php';

// Get admin info
$admin_id = $_SESSION['admin_id'];
$admin_query = $conn->prepare("SELECT name, email, profile_pic FROM admin WHERE id = ?");
$admin_query->bind_param("i", $admin_id);
$admin_query->execute();
$admin_result = $admin_query->get_result();
$admin = $admin_result->fetch_assoc() ?? ['name' => 'Unknown', 'email' => '', 'profile_pic' => NULL];

$admin_name = $admin['name'];
$admin_email = $admin['email'];
$admin_profile_pic = $admin['profile_pic'] ?: 'https://via.placeholder.com/100';
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

        /* Search Bar */
        .search-bar {
            display: flex;
            align-items: center;
            background-color: rgba(255, 255, 255, 0.15);
            border-radius: 25px;
            padding: 8px 15px;
            width: 300px;
            transition: all 0.3s ease;
        }

        .search-bar:hover {
            background-color: rgba(255, 255, 255, 0.25);
        }

        .search-bar input {
            border: none;
            background: transparent;
            padding: 5px 10px;
            width: 100%;
            outline: none;
            color: white;
            font-size: 14px;
        }

        .search-bar input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .search-bar i {
            color: rgba(255, 255, 255, 0.8);
        }

        /* Notification Bell */
        .notification-bell {
            position: relative;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .notification-bell:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--accent);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 8px 15px;
            border-radius: 25px;
            background-color: rgba(255, 255, 255, 0.1);
            transition: background-color 0.3s;
            position: relative;
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

        .user-profile i {
            font-size: 12px;
            transition: transform 0.3s;
        }

        .user-profile.active i {
            transform: rotate(180deg);
        }

        .dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 60px;
            background: white;
            color: var(--dark);
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
            border-radius: 10px;
            min-width: 200px;
            z-index: 1001;
            overflow: hidden;
            border: 1px solid #e0e0e0;
        }

        .dropdown.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown a {
            display: flex;
            align-items: center;
            gap: 12px;
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
            color: var(--primary);
        }

        .dropdown a i {
            color: var(--primary);
            width: 20px;
            text-align: center;
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

        /* .user-info {
            text-align: center;
            padding: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
         */
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

        .user-email {
            font-size: 12px;
            opacity: 0.8;
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
            border-left: 4px solid transparent;
        }

        .menu-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: var(--primary);
        }

        .menu-item.active {
            background-color: rgba(255, 255, 255, 0.2);
            border-left: 4px solid var(--primary);
        }

        .menu-item i {
            margin-right: 00px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .menu-item a {
            color: inherit;
            text-decoration: none;
            display: flex;
            align-items: center;
            width: 100%;
        }

        /* Main Content */
        .main-content {
            grid-column: 2;
            padding: 25px;
            width: calc(100vw - 250px);
            margin-left: 0px;
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
                padding: 20px;
            }

            .search-bar {
                width: 200px;
            }
        }

        @media (max-width: 768px) {
            .logo h1 {
                font-size: 24px;
            }

            .top-bar {
                height: 70px;
                padding: 15px 20px;
            }

            body {
                padding-top: 70px;
            }

            .user-profile span {
                display: none;
            }

            .search-bar {
                width: 150px;
            }

            .search-bar input::placeholder {
                font-size: 12px;
            }
        }

        @media (max-width: 576px) {
            .search-bar {
                display: none;
            }

            .logo h1 {
                font-size: 20px;
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
            <!-- Search Bar -->
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search users, destinations...">
            </div>

            <!-- Notification Bell -->
            <div class="notification-bell">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">3</span>
            </div>

            <!-- User Profile -->
            <div class="user-profile" id="userProfile">
                <img src="<?= htmlspecialchars($admin_profile_pic) ?>" alt="Admin">
                <span><?= htmlspecialchars($admin_name) ?></span>
                <i class="fas fa-chevron-down"></i>

                <!-- Dropdown Menu -->
                <div class="dropdown" id="profileDropdown">
                    <a href="../admin/admin_settings.php">
                        <i class="fas fa-user-cog"></i> Edit Profile
                    </a>

                    <a href="../auth/logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Layout -->
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <!-- <div class="user-info">
                <img src="<?= htmlspecialchars($admin_profile_pic) ?>" alt="Admin" class="user-avatar">
                <h3 class="user-name"><?= htmlspecialchars($admin_name) ?></h3>
                <span class="user-role">Admin User</span>
                <p class="user-email"><?= htmlspecialchars($admin_email) ?></p>
            </div> -->

            <ul class="menu">
                <?php
                // Define current page for active menu highlighting
                $current_page = basename($_SERVER['PHP_SELF']);
                ?>
                <li class="menu-item <?= $current_page == 'admin_dasbord.php' ? 'active' : '' ?>">
                    <a href="../admin/admin_dasbord.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="menu-item <?= $current_page == 'add_destination_on_admin.php' ? 'active' : '' ?>">
                    <a href="add_destination_on_admin.php">
                        <i class="fas fa-map-marker-alt"></i> Destinations
                    </a>
                </li>
                <li class="menu-item <?= $current_page == 'user_present_chack_on_admin.php' ? 'active' : '' ?>">
                    <a href="../admin/user_present_chack_on_admin.php">
                        <i class="fas fa-users"></i> Users
                    </a>
                </li>
                <li class="menu-item <?= $current_page == 'user_join_analysis_on_ADMIN.php' ? 'active' : '' ?>">
                    <a href="user_join_analysis_on_ADMIN.php">
                        <i class="fas fa-chart-line"></i> Analysis
                    </a>
                </li>
                <li class="menu-item <?= $current_page == 'messages.php' ? 'active' : '' ?>">
                    <a href="../admin/messages.php">
                        <i class="fas fa-envelope"></i> Messages
                    </a>
                </li>

                <li class="menu-item <?= $current_page == 'user_ip_tracking_on_admin.php' ? 'active' : '' ?>">
                    <a href="../admin/user_ip_tracking_on_admin.php">
                        <i class="fas fa-network-wired"></i> User IPs
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const userProfile = document.getElementById('userProfile');
                    const profileDropdown = document.getElementById('profileDropdown');

                    // Toggle dropdown when clicking on profile
                    userProfile.addEventListener('click', function(e) {
                        e.stopPropagation();
                        profileDropdown.classList.toggle('active');
                        userProfile.classList.toggle('active'); // Rotate chevron
                    });

                    // Close dropdown when clicking outside
                    document.addEventListener('click', function(e) {
                        if (!userProfile.contains(e.target)) {
                            profileDropdown.classList.remove('active');
                            userProfile.classList.remove('active');
                        }
                    });

                    // Prevent dropdown from closing when clicking inside it
                    profileDropdown.addEventListener('click', function(e) {
                        e.stopPropagation();
                    });
                });
            </script>