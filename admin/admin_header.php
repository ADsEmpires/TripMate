<?php
// Start session only if none exists to avoid "session already active" notices
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
    'messages.php' => ['Messages'],
    'admin_settings.php' => ['Settings'],
    'system_anylises.php' => ['System','Analytics'],
    'save_theme.php' => ['Settings','Theme'],
    'demo_error.php' => ['Demo','Error'],
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ALL THE EXACT SAME CSS FROM THE STATIC CODE */
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #ffffff;
            --muted: #f8fafc;
            --card-bg: #ffffff;
            --text: #333333;
            --dark: #0b1220;
            --sidebar-width: 220px; /* reduced width for smaller left bar */
            --header-height: 64px; /* slightly shorter header */
        }

        /* Dark theme variables applied when body has .dark-theme */
        body.dark-theme {
            --primary: #2b59d9;
            --secondary: #1f3f97;
            --success: #16a34a;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --light: #0b1220; /* main bg */
            --muted: #0f1724; /* sidebar/bg muted */
            --card-bg: #071028; /* card backgrounds */
            --text: #e6eef8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--light);
            color: var(--text);
            line-height: 1.6;
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: var(--card-bg);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
            z-index: 1000;
        }

        .header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 100%;
            padding: 0 1.25rem; /* reduced padding */
            max-width: 100%;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo-orb {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .logo-text {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .logo-primary {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--dark);
        }

        .logo-secondary {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--primary);
            letter-spacing: 1px;
        }

        .search-bar {
            position: relative;
            width: 320px; /* slightly smaller */
        }

        .search-input {
            width: 100%;
            padding: 0.65rem 0.9rem 0.65rem 3rem;
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: 10px;
            background: var(--muted);
            color: var(--text);
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
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
            width: 38px;
            height: 38px;
            border: none;
            border-radius: 8px;
            background: var(--muted);
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .action-btn:hover {
            background: var(--primary);
            color: white;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .user-profile:hover {
            background: #f8fafc;
        }

        .profile-avatar {
            position: relative;
        }

        .profile-avatar img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e2e8f0;
        }

        .status-indicator {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid #fff;
        }

        .status-indicator.online {
            background: var(--success);
        }

        .profile-info {
            display: flex;
            flex-direction: column;
        }

        .profile-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--dark);
        }

        .profile-role {
            font-size: 0.75rem;
            color: #64748b;
        }

        .profile-dropdown {
            color: #64748b;
            transition: transform 0.3s ease;
        }

        .user-profile:hover .profile-dropdown {
            transform: rotate(180deg);
        }

        .profile-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            width: 280px;
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: all 0.18s ease;
            z-index: 1001;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .user-profile:hover .profile-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(5px);
        }

        .dropdown-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-avatar img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .header-info h4 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .header-info p {
            font-size: 0.8rem;
            color: #64748b;
        }

        .dropdown-section {
            padding: 0.75rem 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: #475569;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background: #f8fafc;
            color: var(--primary);
        }

        .dropdown-item i {
            width: 20px;
            text-align: center;
        }

        .dropdown-footer {
            padding: 0.75rem 0;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: var(--danger);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #fef2f2;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: var(--header-height);
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            background: var(--card-bg);
            border-right: 1px solid rgba(0,0,0,0.04);
            overflow-y: auto;
            z-index: 999;
        }

        .sidebar-content {
            padding: 1.5rem 0;
        }

        .sidebar-nav {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 0.25rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.25rem;
            color: #475569;
            text-decoration: none;
            transition: all 0.18s ease;
            position: relative;
        }

        .nav-link:hover {
            background: var(--muted);
            color: var(--primary);
        }

        .nav-item.active .nav-link {
            background: #4361ee0d;
            color: var(--primary);
            border-right: 3px solid var(--primary);
        }

        .nav-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
        }

        .nav-text {
            font-weight: 500;
            font-size: 0.9rem;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: calc(var(--header-height) + 0.5rem);
            padding: 1.5rem;
            min-height: calc(100vh - var(--header-height));
            transition: margin-left 0.18s ease;
            width: calc(100% - var(--sidebar-width));
            max-width: 100%;
        }

            .mobile-sidebar-toggle {
                display: none;
            }

        @media (max-width: 1024px) {
            .search-bar {
                width: 300px;
            }
        }

        @media (max-width: 768px) {
                .mobile-sidebar-toggle {
                    display: flex !important;
                }

            .search-bar {
                display: none;
            }
            
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .header-container {
                padding: 0 1rem;
            }

            .logo-secondary {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .profile-info {
                display: none;
            }

            .action-buttons {
                gap: 0.25rem;
            }

            .header-container {
                padding: 0 0.5rem;
            }
        }
        /* Breadcrumb styles */
        .admin-breadcrumb {
            margin-bottom: 1rem;
            padding: 0.5rem 0.75rem;
            background: var(--muted);
            border-radius: 6px;
            color: var(--text);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(0,0,0,0.04);
        }
        .admin-breadcrumb a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .admin-breadcrumb .sep { color: #9aa3b2; }
        .admin-breadcrumb .last { color: #6b7280; font-weight: 700; }
    </style>
</head>
<body>
    <!-- Header - Using static design but with dynamic data -->
    <header class="header">
        <div class="header-container">
            <!-- Left Section -->
            <div class="header-left">
                <!-- Logo -->
                <div class="logo">
                    <div class="logo-orb">
                        <i class="fas fa-compass"></i>
                    </div>
                    <div class="logo-text">
                        <span class="logo-primary">TripMate</span>
                        <span class="logo-secondary">ADMIN PANEL</span>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="search-bar">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search...">
                </div>
            </div>

            <!-- Right Section -->
            <div class="header-right">
                <!-- Quick Actions -->
                <div class="action-buttons">
                        <button class="action-btn mobile-sidebar-toggle d-none" id="sidebarToggle">
                            <i class="fas fa-bars"></i>
                        </button>
                        <button class="action-btn theme-toggle" id="themeToggle">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>

                <!-- User Profile - Using dynamic data -->
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

                    <!-- Profile Dropdown - Using dynamic data -->
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
                            <a href="admin_settings.php?section=profile" class="dropdown-item">
                                <i class="fas fa-user-cog"></i>
                                <span>Edit Profile</span>
                            </a>
                            <a href="admin_settings.php" class="dropdown-item">
                                <i class="fas fa-cog"></i>
                                <span>Settings</span>
                            </a>
                        </div>

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

    <!-- Sidebar - Using static design but with dynamic navigation -->
    <nav class="sidebar">
        <div class="sidebar-content">
            <!-- Main Navigation -->
            <ul class="sidebar-nav">
                <?php
                // Define current page for active menu highlighting
                $current_page = basename($_SERVER['PHP_SELF']);
                ?>
                <li class="nav-item <?= $current_page == 'admin_dasbord.php' ? 'active' : '' ?>">
                    <a href="admin_dasbord.php" class="nav-link">
                        <div class="nav-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item <?= $current_page == 'add_destanition_on_admin.php' ? 'active' : '' ?>">
                    <a href="add_destanition_on_admin.php" class="nav-link">
                        <div class="nav-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <span class="nav-text">Destinations</span>
                    </a>
                </li>
                <li class="nav-item <?= $current_page == 'user_present_chack_on_admin.php' ? 'active' : '' ?>">
                    <a href="user_present_chack_on_admin.php" class="nav-link">
                        <div class="nav-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <span class="nav-text">Users</span>
                    </a>
                </li>
                <li class="nav-item <?= $current_page == 'user_join_analysis_on_ADMIN.php' ? 'active' : '' ?>">
                    <a href="user_join_analysis_on_ADMIN.php" class="nav-link">
                        <div class="nav-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <span class="nav-text">Analysis</span>
                    </a>
                </li>
                <li class="nav-item <?= $current_page == 'messages.php' ? 'active' : '' ?>">
                    <a href="messages.php" class="nav-link">
                        <div class="nav-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <span class="nav-text">Messages</span>
                    </a>
                </li>
               
                <li class="nav-item <?= $current_page == 'user_ip_tracking_on_admin.php' ? 'active' : '' ?>">
                    <a href="user_ip_tracking_on_admin.php" class="nav-link">
                        <div class="nav-icon">
                            <i class="fas fa-network-wired"></i>
                        </div>
                        <span class="nav-text">User IPs</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content is provided by individual pages -->

    <script>
        // Theme toggle functionality with persistence
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

            // Initialize theme from localStorage
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

        // Sidebar toggle functionality
        (function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.querySelector('.sidebar');

            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    sidebar.classList.toggle('active');
                });

                // Close sidebar when clicking outside on small screens
                document.addEventListener('click', function(e) {
                    if (!sidebar.contains(e.target) &&
                        !sidebarToggle.contains(e.target) &&
                        window.innerWidth <= 768) {
                        sidebar.classList.remove('active');
                    }
                });
            }
        })();
        // Inject breadcrumb into pages that include .main-content
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
                        if (!isLast) html += '<span class="sep">›</span>';
                    });
                    div.innerHTML = html;
                    container.insertBefore(div, container.firstChild);
                } catch (e) { console.error(e); }
            });

            function escapeHtml(str) {
                return String(str).replace(/[&<>"']/g, function(m) { return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]; });
            }
        })();
    </script>
</body>
</html>