<?php
// Include admin session check - handles login protection and auto-logout
// include 'admin_session_check.php';

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
$admin_profile_pic = $admin['profile_pic'];
// Current page and breadcrumb mapping
$current_page = basename($_SERVER['PHP_SELF']);

// Map filenames to breadcrumb segments. Each value can be an array of segment labels
$page_breadcrumb_map = [
    'admin_dasbord.php' => ['Dashboard'],
    'add_destanition_on_admin.php' => ['Destinations','Add Destination'],
    'edit_destination.php' => ['Destinations','Edit Destination'],
    'delete_destination.php' => ['Destinations','Delete Destination'],
    'user_present_chack_on_admin.php' => ['Users','List Users'],
    'user_join_analysis_on_ADMIN.php' => ['Analysis','User Join Analysis'],
    'user_ip_tracking_on_admin.php' => ['Users','IP Tracking'],
    'send_user_email.php' => ['Messages', 'Send Email'],
    'admin_settings.php' => ['Settings'],
    'page_time_analytics.php' => ['Analytics','Page Time Tracking'],
    'save_theme.php' => ['Settings','Theme'],
    'demo_error.php' => ['Demo','Error'],
    'bookings.php' => ['Bookings', 'Manage Bookings'],
    'booking_details.php' => ['Bookings', 'Booking Details'],
    'weather_dashboard.php' => ['Weather', 'Dashboard'],
    'weather_alert_destinations.php' => ['Weather', 'Alerts'],
    'weather_settings.php' => ['Weather', 'Settings'],
    'sql.php' => ['add_user'],
    // add more mappings as needed
];

// Build breadcrumb items (first item is Admin linking to dashboard)
$breadcrumb_items = [];
$breadcrumb_items[] = ['label' => 'Admin', 'url' => 'admin_dasbord.php'];
$segments = $page_breadcrumb_map[$current_page] ?? [preg_replace(['/\.php$/','/_/'], ['',' '], ucfirst($current_page))];
foreach ($segments as $i => $seg) {
    // last segment should not have URL
    $is_last = ($i === array_key_last($segments));
    $breadcrumb_items[] = [
        'label' => $seg,
        'url' => $is_last ? '' : '#'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TravelGuide - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ADVANCED THEME VARIABLES */
        :root {
            /* Light Mode Colors from index.html */
            --bg-base: #f8fafc;
            --bg-surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #475569;
            --primary: #4f46e5; 
            --secondary: #06b6d4; 
            --card-border: rgba(79, 70, 229, 0.15);
            --shadow-color: rgba(15, 23, 42, 0.08);
            --glow-color: rgba(6, 182, 212, 0.4);
            
            /* Status Colors */
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;

            --sidebar-width: 250px;
            --header-height: 70px;
        }

        /* Dark theme variables */
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
            line-height: 1.6;
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        /* HEADER */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: var(--bg-surface);
            box-shadow: 0 4px 20px var(--shadow-color);
            z-index: 1000;
            border-bottom: 1px solid var(--card-border);
            transition: background-color 0.4s ease, border-color 0.4s ease;
        }

        .header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 100%;
            padding: 0 1.5rem;
            max-width: 100%;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        /* NEW LOGO STYLING */
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.4rem;
            font-weight: 800;
        }

        .logo i {
            color: var(--primary);
            font-size: 1.5rem;
            transform: rotate(-10deg);
            transition: transform 0.3s;
        }

        .logo:hover i {
            transform: rotate(0deg) scale(1.1);
        }

        .brand-text .trip { color: var(--text-main); }
        .brand-text .mate { color: var(--secondary); }

        .search-bar {
            position: relative;
            width: 320px;
        }

        .search-input {
            width: 100%;
            padding: 0.65rem 0.9rem 0.65rem 3rem;
            border: 1px solid var(--card-border);
            border-radius: 50px;
            background: var(--bg-base);
            color: var(--text-main);
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--bg-surface);
            box-shadow: 0 0 0 3px var(--glow-color);
        }

        .search-icon {
            position: absolute;
            left: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            width: 44px;
            height: 44px;
            border: 1px solid var(--card-border);
            border-radius: 50%;
            background: var(--bg-surface);
            color: var(--text-main);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px var(--shadow-color);
        }

        .action-btn:hover {
            transform: translateY(-2px);
            color: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 8px 15px var(--glow-color);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            background: var(--bg-surface);
            border: 1px solid var(--card-border);
            box-shadow: 0 4px 10px var(--shadow-color);
        }

        .user-profile:hover {
            border-color: var(--secondary);
            box-shadow: 0 8px 15px var(--glow-color);
        }

        .profile-avatar { position: relative; }
        .profile-avatar img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--bg-base);
        }

        .status-indicator {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            border: 2px solid var(--bg-surface);
        }

        .status-indicator.online { background: var(--success); }

        .profile-info { display: flex; flex-direction: column; }
        .profile-name { font-weight: 700; font-size: 0.85rem; color: var(--text-main); }
        .profile-role { font-size: 0.7rem; color: var(--text-muted); font-weight: 500; }

        .profile-dropdown { color: var(--text-muted); transition: transform 0.3s ease; }
        .user-profile:hover .profile-dropdown { transform: rotate(180deg); color: var(--primary); }

        .profile-dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 280px;
            background: var(--bg-surface);
            border-radius: 24px;
            box-shadow: 0 20px 40px var(--shadow-color);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
            z-index: 1001;
            border: 1px solid var(--card-border);
        }

        .user-profile:hover .profile-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-avatar img { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); }
        .header-info h4 { font-size: 1rem; font-weight: 700; margin-bottom: 0.25rem; color: var(--text-main); }
        .header-info p { font-size: 0.8rem; color: var(--text-muted); }

        .dropdown-section { padding: 0.75rem 0; border-bottom: 1px solid var(--card-border); }
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .dropdown-item:hover {
            background: var(--bg-base);
            color: var(--primary);
            padding-left: 2rem;
        }

        .dropdown-item i { width: 20px; text-align: center; }

        .dropdown-footer { padding: 0.75rem 0; }
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: var(--danger);
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .logout-btn:hover { background: rgba(239, 68, 68, 0.1); padding-left: 2rem; }

        /* SIDEBAR */
        .sidebar {
            position: fixed;
            left: 0;
            top: var(--header-height);
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            background: var(--bg-surface);
            border-right: 1px solid var(--card-border);
            overflow-y: auto;
            z-index: 999;
            transition: background-color 0.4s ease, border-color 0.4s ease;
        }

        .sidebar-content { padding: 1.5rem 0; }
        .sidebar-nav { list-style: none; padding: 0 1rem; }
        .nav-item { margin-bottom: 0.5rem; }
        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.85rem 1.25rem;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            border-radius: 16px;
            font-weight: 600;
        }

        .nav-link:hover {
            background: var(--bg-base);
            color: var(--primary);
            transform: translateX(5px);
        }

        .nav-item.active .nav-link {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(6, 182, 212, 0.1));
            color: var(--primary);
            border: 1px solid var(--card-border);
            box-shadow: 0 4px 15px var(--shadow-color);
        }

        .nav-icon { width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; margin-right: 0.75rem; font-size: 1.1rem; }

        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: calc(var(--header-height));
            padding: 2rem;
            min-height: calc(100vh - var(--header-height));
            transition: margin-left 0.18s ease;
            width: calc(100% - var(--sidebar-width));
            max-width: 100%;
        }

        .mobile-sidebar-toggle { display: none; }

        @media (max-width: 1024px) { .search-bar { width: 300px; } }
        @media (max-width: 768px) {
            .mobile-sidebar-toggle { display: flex !important; }
            .search-bar { display: none; }
            .sidebar { transform: translateX(-100%); transition: transform 0.3s cubic-bezier(0.25, 1, 0.5, 1); }
            .main-content { margin-left: 0; width: 100%; }
            .sidebar.active { transform: translateX(0); }
            .header-container { padding: 0 1rem; }
            .logo .brand-text { display: none; }
        }
        @media (max-width: 480px) {
            .profile-info { display: none; }
            .action-buttons { gap: 0.25rem; }
            .header-container { padding: 0 0.5rem; }
        }

        /* Breadcrumb */
        .admin-breadcrumb {
            margin-bottom: 2rem;
            padding: 1rem 1.5rem;
            background: var(--bg-surface);
            border-radius: 50px;
            color: var(--text-main);
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid var(--card-border);
            box-shadow: 0 10px 20px var(--shadow-color);
        }
        .admin-breadcrumb a { color: var(--primary); text-decoration: none; font-weight: 700; transition: color 0.2s;}
        .admin-breadcrumb a:hover { color: var(--secondary); }
        .admin-breadcrumb .sep { color: var(--text-muted); }
        .admin-breadcrumb .last { color: var(--text-main); font-weight: 800; }
        
        /* Weather Widget Styles */
        .weather-widget {
            position: fixed; top: 90px; right: 20px; width: 340px;
            background: var(--bg-surface); border-radius: 24px;
            box-shadow: 0 20px 40px var(--shadow-color); z-index: 1000;
            overflow: hidden; display: none; border: 1px solid var(--card-border);
        }
        .weather-widget.active { display: block; animation: slideInUp 0.4s cubic-bezier(0.25, 1, 0.5, 1); }
        @keyframes slideInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        .weather-widget-header {
            padding: 1.25rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white; display: flex; justify-content: space-between; align-items: center;
        }
        .weather-widget-header span { font-weight: 700; display: flex; align-items: center; gap: 0.75rem; }
        .weather-widget-content { padding: 1.5rem; color: var(--text-main); }
        .weather-location { font-size: 1.2rem; font-weight: 800; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; }
        .weather-temp {
            font-size: 2.8rem; font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            margin: 0.25rem 0; line-height: 1;
        }
        .weather-condition { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .weather-condition-text { font-size: 1rem; color: var(--text-muted); text-transform: capitalize; font-weight: 500;}
        .weather-feasibility { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 700; }
        .feasible { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .challenging { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .not-feasible { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        .weather-details-row { display: flex; justify-content: space-between; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--card-border); }
        .weather-detail-item { display: flex; flex-direction: column; align-items: center; gap: 0.25rem; }
        .weather-detail-label { font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;}
        .weather-detail-value { font-weight: 800; font-size: 0.95rem; color: var(--text-main); }
        .weather-updated { font-size: 0.75rem; color: var(--text-muted); margin-top: 1.25rem; text-align: right; font-weight: 500;}
        .weather-widget-footer { padding: 1rem 1.5rem; background: var(--bg-base); border-top: 1px solid var(--card-border); display: flex; justify-content: flex-end; }
        .weather-widget-footer a { color: var(--primary); text-decoration: none; font-size: 0.9rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; transition: color 0.3s; }
        .weather-widget-footer a:hover { color: var(--secondary); }
        .weather-loading, .weather-error { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2.5rem; color: var(--text-muted); text-align: center;}
        .weather-spinner { width: 40px; height: 40px; border: 3px solid var(--card-border); border-top-color: var(--primary); border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 1rem; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <div class="header-left">
                <div class="logo">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span class="brand-text"><span class="trip">Trip</span><span class="mate">Mate</span> <span style="font-size: 0.7rem; color: var(--text-muted); letter-spacing: 1px; vertical-align: middle;">ADMIN</span></span>
                </div>
                <div class="search-bar">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search destinations, bookings..." id="globalSearch">
                </div>
            </div>

            <div class="header-right">
                <div class="action-buttons">
                    <button class="action-btn mobile-sidebar-toggle d-none" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <button class="action-btn theme-toggle" id="themeToggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    <button class="action-btn" id="weatherToggle" title="Weather Info">
                        <i class="fas fa-cloud-sun"></i>
                    </button>
                </div>

                <div class="user-profile">
                    <div class="profile-avatar">
                        <img src="<?= $admin_profile_pic ? $admin_profile_pic : 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&h=150&fit=crop&crop=face' ?>" alt="Admin">
                        <div class="status-indicator online"></div>
                    </div>
                    <div class="profile-info">
                        <span class="profile-name"><?= htmlspecialchars($admin_name) ?></span>
                        <span class="profile-role">Administrator</span>
                    </div>
                    <i class="fas fa-chevron-down profile-dropdown"></i>

                    <div class="profile-dropdown-menu">
                        <div class="dropdown-header">
                            <div class="header-avatar">
                                <img src="<?= $admin_profile_pic ? $admin_profile_pic : 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=80&h=80&fit=crop&crop=face' ?>" alt="Admin">
                            </div>
                            <div class="header-info">
                                <h4><?= htmlspecialchars($admin_name) ?></h4>
                                <p><?= htmlspecialchars($admin_email) ?></p>
                            </div>
                        </div>
                        <div class="dropdown-section">
                            <a href="admin_settings.php" class="dropdown-item">
                                <i class="fas fa-cog"></i>
                                <span>Settings</span>
                            </a>
                        </div>
                        <li class="nav-item <?= $current_page == 'sql.php' ? 'active' : '' ?>" style="list-style: none;">
                            <a href="sql.php" class="dropdown-item">
                                <i class="fa-solid fa-user-secret"></i>
                                <span>insert_user_data</span>
                            </a>
                        </li>
                        <div class="dropdown-footer">
                            <a href="../auth/logout.php" class="logout-btn">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <nav class="sidebar">
        <div class="sidebar-content">
            <ul class="sidebar-nav">
                <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
                <li class="nav-item <?= $current_page == 'admin_dasbord.php' ? 'active' : '' ?>">
                    <a href="admin_dasbord.php" class="nav-link">
                        <div class="nav-icon"><i class="fas fa-home"></i></div>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item <?= $current_page == 'add_destanition_on_admin.php' ? 'active' : '' ?>">
                    <a href="add_destanition_on_admin.php" class="nav-link">
                        <div class="nav-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <span class="nav-text">Destinations</span>
                    </a>
                </li>
                <li class="nav-item <?= $current_page == 'user_present_chack_on_admin.php' ? 'active' : '' ?>">
                    <a href="user_present_chack_on_admin.php" class="nav-link">
                        <div class="nav-icon"><i class="fas fa-users"></i></div>
                        <span class="nav-text">Users</span>
                    </a>
                </li>
                <li class="nav-item <?= $current_page == 'user_join_analysis_on_ADMIN.php' ? 'active' : '' ?>">
                    <a href="user_join_analysis_on_ADMIN.php" class="nav-link">
                        <div class="nav-icon"><i class="fas fa-chart-line"></i></div>
                        <span class="nav-text">Analysis</span>
                    </a>
                </li>
                <li class="nav-item <?= $current_page == 'send_user_email.php' ? 'active' : '' ?>">
                    <a href="send_user_email.php" class="nav-link">
                        <div class="nav-icon"><i class="fas fa-paper-plane"></i></div>
                        <span class="nav-text">Send Email</span>
                    </a>
                </li>
                <li class="nav-item <?= $current_page == 'user_ip_tracking_on_admin.php' ? 'active' : '' ?>">
                    <a href="user_ip_tracking_on_admin.php" class="nav-link">
                        <div class="nav-icon"><i class="fas fa-network-wired"></i></div>
                        <span class="nav-text">User IPs</span>
                    </a>
                </li>
                <li class="nav-item <?= $current_page == 'page_time_analytics.php' ? 'active' : '' ?>">
                    <a href="page_time_analytics.php" class="nav-link">
                        <div class="nav-icon"><i class="fas fa-clock"></i></div>
                        <span class="nav-text">Page Time Analytics</span>
                    </a>
                </li>
                <li class="nav-item <?= $current_page == 'bookings.php' ? 'active' : '' ?>">
                    <a href="bookings.php" class="nav-link">
                        <div class="nav-icon"><i class="fas fa-calendar-check"></i></div>
                        <span class="nav-text">Bookings</span>
                    </a>
                </li>

                <li class="nav-section-header" style="padding: 1.5rem 1.25rem 0.5rem; font-size: 0.75rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">
                    Weather Module
                </li>
                <li class="nav-item <?= in_array($current_page, ['weather_dashboard.php', 'weather_monitor.php']) ? 'active' : '' ?>">
                    <a href="weather_dashboard.php" class="nav-link">
                        <div class="nav-icon"><i class="fas fa-cloud-sun"></i></div>
                        <span class="nav-text">Weather Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="weather-widget" id="weatherWidget">
        <div class="weather-widget-header">
            <span><i class="fas fa-cloud-sun"></i> Current Weather</span>
            <button class="action-btn" onclick="closeWeatherWidget()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; min-height: 32px; box-shadow: none;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="weather-widget-content" id="weatherWidgetContent">
            <div class="weather-loading">
                <div class="weather-spinner"></div>
                <span>Loading weather data...</span>
            </div>
        </div>
        <div class="weather-widget-footer">
            <a href="weather_dashboard.php"><i class="fas fa-external-link-alt"></i> Full Dashboard</a>
        </div>
    </div>

    <script>
        (function() {
            const toggle = document.getElementById('themeToggle');
            function setIcon(isDark) {
                if (!toggle) return;
                const icon = toggle.querySelector('i');
                if (!icon) return;
                icon.classList.toggle('fa-sun', isDark);
                icon.classList.toggle('fa-moon', !isDark);
            }

            function applyTheme(theme) {
                if (theme === 'dark') {
                    document.body.classList.add('dark-theme');
                    setIcon(true);
                    localStorage.setItem('adminTheme', 'dark');
                } else {
                    document.body.classList.remove('dark-theme');
                    setIcon(false);
                    localStorage.setItem('adminTheme', 'light');
                }
            }

            const saved = localStorage.getItem('adminTheme');
            if (saved === 'dark') {
                applyTheme('dark');
            } else {
                applyTheme('light');
            }

            if (toggle) {
                toggle.addEventListener('click', function() {
                    const isDark = document.body.classList.toggle('dark-theme');
                    setIcon(isDark);
                    localStorage.setItem('adminTheme', isDark ? 'dark' : 'light');
                });
            }
        })();

        (function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.querySelector('.sidebar');
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    sidebar.classList.toggle('active');
                });
                document.addEventListener('click', function(e) {
                    if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target) && window.innerWidth <= 768) {
                        sidebar.classList.remove('active');
                    }
                });
            }
        })();
        
        (function() {
            const bc = <?php echo json_encode($breadcrumb_items); ?>;
            document.addEventListener('DOMContentLoaded', function() {
                try {
                    var container = document.querySelector('.main-content');
                    if (!container) return;
                    var div = document.createElement('div');
                    div.className = 'admin-breadcrumb';
                    var html = '';
                    bc.forEach(function(item, idx) {
                        var isLast = (idx === bc.length - 1);
                        if (item.url && !isLast) {
                            html += '<a href="' + item.url + '">' + escapeHtml(item.label) + '</a>';
                        } else {
                            html += '<span class="last">' + escapeHtml(item.label) + '</span>';
                        }
                        if (!isLast) html += '<span class="sep"><i class="fas fa-chevron-right" style="font-size:0.75rem;"></i></span>';
                    });
                    div.innerHTML = html;
                    container.insertBefore(div, container.firstChild);
                } catch (e) { console.error(e); }
            });
            function escapeHtml(str) { return String(str).replace(/[&<>"']/g, function(m) { return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]; }); }
        })();
        
        const weatherWidget = document.getElementById('weatherWidget');
        const weatherToggle = document.getElementById('weatherToggle');
        const OPENWEATHER_API_KEY = 'b4fe517a83b0e5679af65062c7fd92cd';
        let defaultWeatherCity = 'Bishnupur';
        
        const weatherIcons = {
            '01d': 'fa-sun', '01n': 'fa-moon', '02d': 'fa-cloud-sun', '02n': 'fa-cloud-moon',
            '03d': 'fa-cloud', '03n': 'fa-cloud', '04d': 'fa-cloud', '04n': 'fa-cloud',
            '09d': 'fa-cloud-rain', '09n': 'fa-cloud-rain', '10d': 'fa-cloud-sun-rain', '10n': 'fa-cloud-moon-rain',
            '11d': 'fa-bolt', '11n': 'fa-bolt', '13d': 'fa-snowflake', '13n': 'fa-snowflake', '50d': 'fa-smog', '50n': 'fa-smog'
        };
        
        if (weatherToggle) {
            weatherToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                weatherWidget.classList.toggle('active');
                if (weatherWidget.classList.contains('active')) { loadWeatherData(defaultWeatherCity); }
            });
        }
        function closeWeatherWidget() { if (weatherWidget) { weatherWidget.classList.remove('active'); } }
        
        async function loadWeatherData(city = 'Bishnupur') {
            const contentEl = document.getElementById('weatherWidgetContent');
            if (!contentEl) return;
            contentEl.innerHTML = `<div class="weather-loading"><div class="weather-spinner"></div><span>Fetching weather for ${city}...</span></div>`;
            try {
                const response = await fetch(`https://api.openweathermap.org/data/2.5/weather?q=${encodeURIComponent(city)}&appid=${OPENWEATHER_API_KEY}&units=metric&lang=en`);
                if (!response.ok) throw new Error('City not found');
                const data = await response.json();
                
                const temp = Math.round(data.main.temp);
                const feelsLike = Math.round(data.main.feels_like);
                const humidity = data.main.humidity;
                const windSpeed = (data.wind.speed * 3.6).toFixed(1);
                const condition = data.weather[0].description;
                const iconCode = data.weather[0].icon;
                const iconClass = weatherIcons[iconCode] || 'fa-cloud';
                const location = `${data.name}, ${data.sys.country}`;
                
                let feasibility = 'feasible', feasibilityText = 'Good', feasibilityClass = 'feasible';
                const mainCondition = data.weather[0].main.toLowerCase();
                if (mainCondition.includes('thunderstorm') || mainCondition.includes('tornado') || temp > 35 || temp < 0) {
                    feasibility = 'not-feasible'; feasibilityText = 'Not Advisable'; feasibilityClass = 'not-feasible';
                } else if (mainCondition.includes('rain') || mainCondition.includes('snow') || data.wind.speed > 10) {
                    feasibility = 'challenging'; feasibilityText = 'Challenging'; feasibilityClass = 'challenging';
                }
                
                contentEl.innerHTML = `
                    <div>
                        <div class="weather-location"><i class="fas fa-map-marker-alt" style="color: var(--primary);"></i> ${location}</div>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div class="weather-temp">${temp}°C</div>
                            <div style="font-size: 2.5rem; color: var(--primary);"><i class="fas ${iconClass}"></i></div>
                        </div>
                        <div class="weather-condition">
                            <span class="weather-condition-text"><i class="fas fa-circle" style="font-size: 0.5rem; vertical-align: middle; color: var(--primary);"></i> ${condition}</span>
                            <span class="weather-feasibility ${feasibilityClass}">${feasibilityText}</span>
                        </div>
                        <div class="weather-details-row">
                            <div class="weather-detail-item"><span class="weather-detail-label">Feels Like</span><span class="weather-detail-value">${feelsLike}°C</span></div>
                            <div class="weather-detail-item"><span class="weather-detail-label">Humidity</span><span class="weather-detail-value">${humidity}%</span></div>
                            <div class="weather-detail-item"><span class="weather-detail-label">Wind</span><span class="weather-detail-value">${windSpeed} km/h</span></div>
                        </div>
                        <div class="weather-updated"><i class="fas fa-clock"></i> Updated just now</div>
                    </div>
                `;
            } catch (error) {
                contentEl.innerHTML = `
                    <div class="weather-error">
                        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 0.75rem; color: var(--danger);"></i>
                        <p style="margin-bottom: 1rem;">Could not load weather for "${city}"</p>
                        <div style="display: flex; gap: 0.5rem; justify-content: center;">
                            <button onclick="loadWeatherData('Bishnupur')" style="background: var(--primary); color: white; border: none; padding: 0.5rem 1rem; border-radius: 50px; cursor: pointer; font-weight: 600;"><i class="fas fa-redo"></i> Retry</button>
                        </div>
                    </div>
                `;
            }
        }
        
        const globalSearch = document.getElementById('globalSearch');
        if (globalSearch) {
            globalSearch.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && this.value.trim()) {
                    window.location.href = `../search/search.html?search=${encodeURIComponent(this.value.trim())}`;
                }
            });
        }
        
        document.addEventListener('click', function(e) {
            if (weatherWidget && weatherToggle && !weatherWidget.contains(e.target) && !weatherToggle.contains(e.target)) {
                closeWeatherWidget();
            }
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('destination')) defaultWeatherCity = urlParams.get('destination');
            setTimeout(() => { fetch(`https://api.openweathermap.org/data/2.5/weather?q=Bishnupur&appid=${OPENWEATHER_API_KEY}&units=metric`).catch(()=>{}); }, 1000);
        });
    </script>
<?php ?>