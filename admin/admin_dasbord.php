<?php
session_start();
include '../database/dbconfig.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// --- Breadcrumb handling (do not alter other functionality) ---
$current_page = ['name' => 'Dashboard', 'url' => basename($_SERVER['PHP_SELF'])];
$breadcrumb_prev = isset($_SESSION['admin_current_page']) ? $_SESSION['admin_current_page'] : ['name' => 'Dashboard', 'url' => 'admin_dasbord.php'];
// Update session current page
$_SESSION['admin_current_page'] = $current_page;
// -------------------------------------------------------------

// Function to safely execute queries and handle errors
function safe_query($conn, $sql) {
    $result = $conn->query($sql);
    if (!$result) {
        error_log("Query failed: " . $conn->error . " | Query: " . $sql);
        return false;
    }
    return $result;
}

// Check if user_levels table exists, if not create it
$table_check = safe_query($conn, "SHOW TABLES LIKE 'user_levels'");
if (!$table_check || $table_check->num_rows === 0) {
    $create_table = safe_query($conn, "CREATE TABLE user_levels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        level ENUM('normal', 'high') DEFAULT 'normal',
        achievements TEXT,
        destinations_added INT DEFAULT 0,
        tasks_completed INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    if (!$create_table) {
        error_log("Failed to create user_levels table: " . $conn->error);
    }
}

// Check if current admin is a high-level user
$is_high_level_user = false;
$user_level_data = null;

// First try to get from user_levels table
$level_query = safe_query($conn, "SELECT * FROM user_levels WHERE user_id = " . $_SESSION['admin_id']);
if ($level_query && $level_query->num_rows > 0) {
    $user_level_data = $level_query->fetch_assoc();
    $is_high_level_user = ($user_level_data['level'] == 'high');
} else {
    // Fallback to check user_level column in users table if it exists
    $column_check = safe_query($conn, "SHOW COLUMNS FROM users LIKE 'user_level'");
    if ($column_check && $column_check->num_rows > 0) {
        $user_check = $conn->prepare("SELECT user_level FROM users WHERE id = ?");
        if ($user_check) {
            $user_check->bind_param("i", $_SESSION['admin_id']);
            $user_check->execute();
            $user_result = $user_check->get_result();
            if ($user_result->num_rows > 0) {
                $user_data = $user_result->fetch_assoc();
                $is_high_level_user = ($user_data['user_level'] == 'high');
            }
            $user_check->close();
        }
    }
}

// Set custom error handler
set_error_handler('log_error');

// Error logging function
function log_error($errno, $errstr, $errfile, $errline) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    }
    $user_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : NULL;
    $message = "Error [$errno] $errstr in $errfile on line $errline";
    $table_check = safe_query($conn, "SHOW TABLES LIKE 'errors'");
    if ($table_check && $table_check->num_rows === 0) {
        error_log("Errors table does not exist: $message");
        return false;
    }
    $stmt = $conn->prepare("INSERT INTO errors (message, ip_address, user_id, created_at) VALUES (?, ?, ?, NOW())");
    if ($stmt === false) {
        error_log("Failed to prepare statement: " . $conn->error);
        return false;
    }
    $stmt->bind_param("ssi", $message, $ip, $user_id);
    if (!$stmt->execute()) {
        error_log("Failed to log error: " . $stmt->error);
    }
    $stmt->close();
    return false;
}

// Use prepared statements to prevent SQL injection
$admin_query = $conn->prepare("SELECT name, email, profile_pic FROM admin WHERE id = ?");
if ($admin_query) {
    $admin_query->bind_param("i", $_SESSION['admin_id']);
    $admin_query->execute();
    $admin_result = $admin_query->get_result();
    $admin = $admin_result->fetch_assoc() ?? ['name' => 'Unknown', 'email' => '', 'profile_pic' => NULL];
    $admin_query->close();
} else {
    $admin = ['name' => 'Unknown', 'email' => '', 'profile_pic' => NULL];
}

// Get dynamic statistics from database
$total_users_result = safe_query($conn, "SELECT COUNT(*) as total FROM users");
$total_users = $total_users_result ? $total_users_result->fetch_assoc()['total'] : 0;

$total_admins_result = safe_query($conn, "SELECT COUNT(*) as total FROM admin");
$total_admins = $total_admins_result ? $total_admins_result->fetch_assoc()['total'] : 0;

$total_destinations_result = safe_query($conn, "SELECT COUNT(*) as total FROM destinations");
$total_destinations = $total_destinations_result ? $total_destinations_result->fetch_assoc()['total'] : 0;

// Check if errors table exists before querying it
$errors_table_exists = safe_query($conn, "SHOW TABLES LIKE 'errors'");
$errors_table_exists = $errors_table_exists && $errors_table_exists->num_rows > 0;

$total_errors = 0;
if ($errors_table_exists) {
    $total_errors_result = safe_query($conn, "SELECT COUNT(*) as total FROM errors");
    $total_errors = $total_errors_result ? $total_errors_result->fetch_assoc()['total'] : 0;
}

$error_rate = ($total_users > 0) ? round(($total_errors / $total_users) * 100, 2) : 0;

// Check if user_activity table exists
$user_activity_exists = safe_query($conn, "SHOW TABLES LIKE 'user_activity'");
$user_activity_exists = $user_activity_exists && $user_activity_exists->num_rows > 0;

$avg_daily_users = 0;
if ($user_activity_exists) {
    $avg_daily_users_result = safe_query($conn, "SELECT COUNT(DISTINCT user_id) / 7 as avg FROM user_activity WHERE activity_date >= CURDATE() - INTERVAL 7 DAY");
    $avg_daily_users = $avg_daily_users_result ? round($avg_daily_users_result->fetch_assoc()['avg'], 2) : 0;
}

$recent_errors_count = 0;
if ($errors_table_exists) {
    $recent_errors_result = safe_query($conn, "SELECT COUNT(*) as count FROM errors WHERE created_at >= CURDATE() - INTERVAL 7 DAY");
    $recent_errors_count = $recent_errors_result ? $recent_errors_result->fetch_assoc()['count'] : 0;
}

$current_month_users = safe_query($conn, "SELECT COUNT(*) as total FROM users WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$current_month = $current_month_users ? $current_month_users->fetch_assoc()['total'] : 0;

$previous_month_users = safe_query($conn, "SELECT COUNT(*) as total FROM users WHERE MONTH(created_at) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(created_at) = YEAR(CURDATE() - INTERVAL 1 MONTH)");
$previous_month = $previous_month_users ? $previous_month_users->fetch_assoc()['total'] : 0;

$growth_rate = ($previous_month > 0) ? round((($current_month - $previous_month) / $previous_month) * 100, 2) : 0;

$avg_monthly_users_result = safe_query($conn, "SELECT AVG(user_count) as avg FROM (SELECT COUNT(*) as user_count FROM users WHERE created_at >= CURDATE() - INTERVAL 6 MONTH GROUP BY MONTH(created_at)) as monthly_counts");
$avg_monthly_users = $avg_monthly_users_result ? round($avg_monthly_users_result->fetch_assoc()['avg'], 2) : 0;

// Get high-level users
$high_level_users = safe_query($conn, "
    SELECT u.name, u.email, u.created_at, ul.achievements, ul.destinations_added, ul.tasks_completed 
    FROM users u 
    LEFT JOIN user_levels ul ON u.id = ul.user_id 
    WHERE ul.level = 'high' OR u.user_level = 'high'
    ORDER BY u.created_at DESC 
    LIMIT 5
");

// Get recent destinations - FIXED: using 'name' instead of 'title'
$recent_destinations = safe_query($conn, "SELECT name, location, created_at FROM destinations ORDER BY created_at DESC LIMIT 5");

// Get recent errors - with error handling for missing table
$recent_errors = false;
if ($errors_table_exists) {
    $recent_errors = safe_query($conn, "SELECT message, ip_address, user_id, created_at FROM errors ORDER BY created_at DESC LIMIT 5");
}

// Get top error-prone IPs - with error handling for missing table
$top_error_ips = false;
if ($errors_table_exists) {
    $top_error_ips = safe_query($conn, "SELECT ip_address, COUNT(*) as error_count FROM errors GROUP BY ip_address ORDER BY error_count DESC LIMIT 5");
}

// Get growth data
$growth_data = safe_query($conn, "SELECT MONTHNAME(created_at) as month, COUNT(*) as user_count FROM users WHERE created_at >= CURDATE() - INTERVAL 6 MONTH GROUP BY MONTH(created_at)");

// Check if payments table exists
$payments_table_exists = safe_query($conn, "SHOW TABLES LIKE 'payments'");
$payments_table_exists = $payments_table_exists && $payments_table_exists->num_rows > 0;

$total_revenue = 0;
if ($payments_table_exists) {
    $revenue_result = safe_query($conn, "SELECT SUM(amount) as total FROM payments WHERE status = 'completed'");
    $total_revenue = $revenue_result ? $revenue_result->fetch_assoc()['total'] : 0;
}

// Check if sessions table exists
$sessions_table_exists = safe_query($conn, "SHOW TABLES LIKE 'sessions'");
$sessions_table_exists = $sessions_table_exists && $sessions_table_exists->num_rows > 0;

$conversion_rate = 0;
if ($sessions_table_exists) {
    $conversion_result = safe_query($conn, "
        SELECT 
            (COUNT(DISTINCT u.id) / GREATEST(COUNT(DISTINCT s.id), 1)) * 100 as rate 
        FROM sessions s 
        LEFT JOIN users u ON s.user_id = u.id
    ");
    $conversion_rate = $conversion_result ? round($conversion_result->fetch_assoc()['rate'], 1) : 0;
}

// Calculate daily growth
$today_users = safe_query($conn, "SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()");
$yesterday_users = safe_query($conn, "SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
$today_count = $today_users ? $today_users->fetch_assoc()['count'] : 0;
$yesterday_count = $yesterday_users ? $yesterday_users->fetch_assoc()['count'] : 0;
$daily_growth = ($yesterday_count > 0) ? round((($today_count - $yesterday_count) / $yesterday_count) * 100, 2) : 0;

// Weekly stats
$weekly_users = safe_query($conn, "SELECT COUNT(*) as count FROM users WHERE created_at >= CURDATE() - INTERVAL 7 DAY");
$weekly_users_count = $weekly_users ? $weekly_users->fetch_assoc()['count'] : 0;

$last_week_users = safe_query($conn, "SELECT COUNT(*) as count FROM users WHERE created_at BETWEEN DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$last_week_count = $last_week_users ? $last_week_users->fetch_assoc()['count'] : 0;
$weekly_growth = ($last_week_count > 0) ? round((($weekly_users_count - $last_week_count) / $last_week_count) * 100, 2) : 0;

// Monthly stats
$monthly_users = safe_query($conn, "SELECT COUNT(*) as count FROM users WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$monthly_users_count = $monthly_users ? $monthly_users->fetch_assoc()['count'] : 0;

$last_month_users = safe_query($conn, "SELECT COUNT(*) as count FROM users WHERE MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))");
$last_month_count = $last_month_users ? $last_month_users->fetch_assoc()['count'] : 0;
$monthly_growth = ($last_month_count > 0) ? round((($monthly_users_count - $last_month_count) / $last_month_count) * 100, 2) : 0;

// Check if bookings and destinations tables exist for these queries
$bookings_table_exists = safe_query($conn, "SHOW TABLES LIKE 'bookings'");
$bookings_table_exists = $bookings_table_exists && $bookings_table_exists->num_rows > 0;

$winning_trips = 0;
if ($bookings_table_exists) {
    $winning_trips_result = safe_query($conn, "
        SELECT d.name, COUNT(b.id) as bookings 
        FROM destinations d 
        LEFT JOIN bookings b ON d.id = b.destination_id 
        WHERE b.status = 'confirmed'
        GROUP BY d.id 
        ORDER BY bookings DESC 
        LIMIT 1
    ");
    $winning_trips = $winning_trips_result ? $winning_trips_result->fetch_assoc()['bookings'] : 0;
}

// Next trip information
$next_trip_destination = "No upcoming trips";
$trip_participants = 0;
$days_until_trip = 0;

if ($bookings_table_exists) {
    $next_trip_result = safe_query($conn, "
        SELECT d.name, d.start_date, COUNT(b.id) as participants 
        FROM destinations d 
        LEFT JOIN bookings b ON d.id = b.destination_id 
        WHERE d.start_date > CURDATE() AND b.status = 'confirmed'
        GROUP BY d.id 
        ORDER BY d.start_date ASC 
        LIMIT 1
    ");

    if ($next_trip_result && $next_trip_result->num_rows > 0) {
        $next_trip = $next_trip_result->fetch_assoc();
        $next_trip_destination = $next_trip['name'];
        $trip_participants = $next_trip['participants'];
        
        $start_date = new DateTime($next_trip['start_date']);
        $current_date = new DateTime();
        $days_until_trip = $current_date->diff($start_date)->days;
    }
}

// Start value and change value (simulated)
$start_value = $monthly_users_count * 1.5; // Simulated value
$change_value = round($start_value * ($monthly_growth / 100), 2);
$change_percentage = $monthly_growth;

// Last week decline (simulated)
$last_week_decline = 0.8; // This would come from your database in a real application
$last_week_users_value = $weekly_users_count * 0.9; // Simulated value

// Winning growth (simulated)
$winning_growth = 2.5; // This would come from your database
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TripMate Admin Dashboard</title>
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
            --breadcrumb-bg: linear-gradient(90deg, rgba(37,98,235,0.06), rgba(16,185,129,0.03));
            --breadcrumb-accent: #2563eb;
        }
        
        /* Breadcrumb styles (added) */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            background: var(--breadcrumb-bg);
            padding: 10px 14px;
            border-radius: 10px;
            margin-bottom: 1.25rem;
            border: 1px solid rgba(37,98,235,0.06);
            box-shadow: 0 6px 18px rgba(37,98,235,0.03);
            width: fit-content;
        }
        .breadcrumb a {
            color: var(--breadcrumb-accent);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        .breadcrumb .sep {
            color: #999;
            font-weight: 700;
            margin: 0 4px;
        }
        .breadcrumb .current {
            color: #404040;
            font-weight: 700;
            background: linear-gradient(90deg, rgba(16,185,129,0.06), rgba(244,63,94,0.02));
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid rgba(0,0,0,0.03);
        }

        /* Rest of original styles unchanged... */
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
        
        .search-bar {
            display: flex;
            align-items: center;
            background-color: rgba(255, 255, 255, 0.15);
            border-radius: 9999px;
            padding: 0.5rem 1rem;
            width: 300px;
            transition: all 0.3s ease;
        }
        
        .search-bar:hover {
            background-color: rgba(255, 255, 255, 0.25);
        }
        
        .search-bar input {
            border: none;
            background: transparent;
            padding: 0.5rem;
            width: 100%;
            outline: none;
            color: white;
        }
        
        .search-bar input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .user-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
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
            transform: translateY(-1px);
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
            margin-left: 0px;
        }
        
        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background: var(--card-bg);
            border-radius: 0.75rem;
            padding: 1.75rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        /* Trek Header */
        .trek-header {
            background: linear-gradient(120deg, var(--primary), #3b82f6);
            color: white;
            padding: 1.75rem;
            border-radius: 0.75rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }
        
        .trek-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(30deg);
        }
        
        .greeting {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        
        .date-display {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 1.25rem;
        }
        
        .trek-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1.25rem;
        }
        
        .trek-stat {
            background: rgba(255, 255, 255, 0.15);
            padding: 1rem;
            border-radius: 0.5rem;
            backdrop-filter: blur(5px);
        }
        
        .trek-stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .trek-stat-label {
            font-size: 0.875rem;
            opacity: 0.8;
        }
        
        /* Performance Metrics */
        .performance-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .metric-card {
            background: var(--card-bg);
            padding: 1.25rem;
            border-radius: 0.5rem;
            box-shadow: var(--shadow);
        }
        
        .metric-title {
            font-size: 0.875rem;
            color: var(--gray);
            margin-bottom: 0.75rem;
        }
        
        .metric-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .metric-change {
            font-size: 0.875rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        
        .metric-change.positive {
            color: var(--success);
        }
        
        .metric-change.negative {
            color: var(--danger);
        }
        
        /* Dashboard Sections */
        .dashboard-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.75rem;
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--primary);
        }
        
        .section-title i {
            color: var(--secondary);
            margin-right: 0.5rem;
        }
        
        /* Calendar Widget */
        .calendar-widget {
            background: var(--card-bg);
            padding: 1.75rem;
            border-radius: 0.75rem;
            box-shadow: var(--shadow);
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
        }
        
        .calendar-day {
            text-align: center;
            padding: 0.5rem;
            border-radius: 0.25rem;
            font-weight: 500;
            cursor: pointer;
        }
        
        .calendar-day.header {
            font-weight: 600;
            color: var(--primary);
            font-size: 0.75rem;
        }
        
        .calendar-day.today {
            background-color: var(--primary);
            color: white;
        }
        
        .calendar-day.other-month {
            color: #ccc;
        }
        
        .calendar-day:hover:not(.header):not(.other-month) {
            background-color: #f0f0f0;
        }
        
        /* Countdown Widget */
        .countdown-widget {
            background: linear-gradient(120deg, var(--secondary), var(--accent));
            color: white;
            padding: 1.75rem;
            border-radius: 0.75rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .countdown-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        
        .countdown-days {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .countdown-destination {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
        }
        
        th {
            background: var(--primary);
            color: white;
            font-weight: 600;
        }
        
        tr:hover {
            background: #f8fafc;
        }
        
        .high-level-badge {
            background: var(--success);
            color: white;
            padding: 0.3rem 0.6rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        
        .achievement-badge {
            background: var(--warning);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            margin-right: 0.5rem;
            display: inline-block;
            font-weight: 600;
        }
        
      .user-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 1.75rem;
    max-height: 600px; /* Fixed height */
    overflow-y: auto; /* Add vertical scroll */
    padding-right: 5px; /* Space for scrollbar */
}

/* Custom scrollbar for user grid */
.user-grid::-webkit-scrollbar {
    width: 6px;
}

.user-grid::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.user-grid::-webkit-scrollbar-thumb {
    background: var(--primary);
    border-radius: 10px;
}

.user-grid::-webkit-scrollbar-thumb:hover {
    background: #1d4ed8;
}

/* Ensure all user cards have same height */
.user-card {
    background: var(--card-bg);
    border-radius: 0.5rem;
    padding: 1.75rem;
    box-shadow: var(--shadow);
    border-left: 4px solid var(--primary);
    transition: all 0.3s ease;
    height: fit-content; /* Adjust height to content */
    min-height: 250px; /* Minimum height for consistency */
}

/* Event list card - Set same fixed height */
.event-list-container {
    max-height: 600px; /* Same height as user grid */
    overflow-y: auto; /* Scroll if needed */
    padding-right: 5px;
}

/* Make sure both columns have proper layout */
.dashboard-section > div {
    display: flex;
    flex-direction: column;
}

.dashboard-section > div > .card {
    flex: 1; /* Take available space */
    display: flex;
    flex-direction: column;
}

.dashboard-section > div > .card .user-grid,
.dashboard-section > div > .card .event-list {
    flex: 1; /* Take remaining space */
}
        
        .ip-list {
            margin-top: 1.25rem;
            border-top: 1px solid #e5e7eb;
            padding-top: 1.25rem;
        }
        
        .no-ips {
            color: var(--gray);
            font-style: italic;
            text-align: center;
            padding: 1.25rem;
            font-size: 0.875rem;
        }
        
        /* Event List */
        .event-list {
            display: grid;
            gap: 1rem;
        }
        
        .event-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s ease;
        }
        
        .event-item:last-child {
            border-bottom: none;
        }
        
        .event-item:hover {
            background: #f8fafc;
        }
        
        .event-title {
            font-weight: 600;
            color: var(--primary);
        }
        
        .event-date {
            color: var(--gray);
            font-size: 0.875rem;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1002;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: var(--card-bg);
            padding: 1.75rem;
            border-radius: 0.75rem;
            width: 90%;
            max-width: 500px;
            box-shadow: var(--shadow);
        }
        
        .modal-content h2 {
            margin-bottom: 1.25rem;
            color: var(--dark);
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .modal-content form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .modal-content input,
        .modal-content button,
        .modal-content textarea {
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            font-size: 1rem;
        }
        
        .modal-content input:focus {
            border-color: var(--primary);
            outline: none;
        }
        
        .modal-content button {
            background: var(--primary);
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        
        .modal-content button:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }
        
        .modal-content .close {
            background: var(--danger);
            margin-top: 0.75rem;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .dashboard-cards {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
            
            .trek-header .greeting {
                font-size: 1.5rem;
            }
            
            .trek-stat-value {
                font-size: 1.25rem;
            }
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
                padding: 1.5rem;
            }
            
            .dashboard-section {
                grid-template-columns: 1fr;
            }
            
            .dashboard-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .top-bar {
                padding: 0 1.25rem;
            }
            
            .search-bar {
                width: 200px;
            }
            
            .user-profile span {
                display: none;
            }
            
            .trek-stats {
                grid-template-columns: 1fr;
            }
            
            .section-title {
                font-size: 1.25rem;
            }
            
            .user-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
            
            .performance-metrics {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .search-bar {
                display: none;
            }
            
            .logo h1 {
                font-size: 1.5rem;
            }
            
            .top-bar {
                height: 70px;
            }
            
            .body {
                padding-top: 70px;
            }
            
            .section-title {
                font-size: 1.125rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }
        }
        
        @media (max-width: 576px) {
            .performance-metrics {
                grid-template-columns: 1fr;
            }
            
            .card {
                padding: 1rem;
            }
            
            .section-title {
                font-size: 1rem;
            }
            
            .trek-header {
                padding: 1rem;
            }
            
            .trek-stat {
                padding: 0.75rem;
            }
            
            .main-content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="logo">
            <i class="fas fa-mountain"></i>
            <h1>TripMate Admin</h1>
        </div>
        
        <div class="top-bar-actions">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search users, destinations...">
            </div>
            
            <div class="user-actions">
                <div class="notification-bell">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge" id="notification-count"><?= $total_errors > 0 ? ($total_errors > 9 ? '9+' : $total_errors) : '0' ?></span>
                </div>
                
                <div class="user-profile" id="userProfile">
                    <img src="<?= $admin['profile_pic'] ? $admin['profile_pic'] : 'https://via.placeholder.com/40' ?>" alt="Admin">
                    <span><?= htmlspecialchars($admin['name']) ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </div>
        
        <div class="dropdown" id="profileDropdown">
            <a href="admin_profile.php" id="editProfileBtn"><i class="fas fa-user"></i> Edit Profile</a>
            <a href="admin_settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="admin_help.php"><i class="fas fa-question-circle"></i> Help</a>
            <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <!-- Main Layout -->
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="user-info">
                <img src="<?= $admin['profile_pic'] ? $admin['profile_pic'] : 'https://via.placeholder.com/100' ?>" alt="Admin" class="user-avatar">
                <h3 class="user-name"><?= htmlspecialchars($admin['name']) ?></h3>
                <?php if ($is_high_level_user): ?>
                    <span class="user-role">High Level User</span>
                <?php else: ?>
                    <span class="user-role">Admin User</span>
                <?php endif; ?>
                <p><?= htmlspecialchars($admin['email']) ?></p>
            </div>
            
            <ul class="menu">
                <li class="menu-item active"><a href="admin_dasbord.php" style="color:inherit;text-decoration:none"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li class="menu-item"><a href="add_destination_on_admin.php" style="color:inherit;text-decoration:none"><i class="fas fa-map-marker-alt"></i> Destinations</a></li>
                <li class="menu-item"><a href="user_present_chack_on_admin.php" style="color:inherit;text-decoration:none"><i class="fas fa-users"></i> Users</a></li>
                <li class="menu-item"><a href="user_join_analysis_on_ADMIN.php" style="color:inherit;text-decoration:none"><i class="fas fa-chart-line"></i> Analysis</a></li>
                <li class="menu-item"><a href="#" style="color:inherit;text-decoration:none"><i class="fas fa-envelope"></i> Messages</a></li>
                <li class="menu-item"><a href="#" style="color:inherit;text-decoration:none"><i class="fas fa-cog"></i> Settings</a></li>
                <li class="menu-item"><a href="user_ip_tracking_on_admin.php" style="color:inherit;text-decoration:none"><i class="fas fa-network-wired"></i> User IPs</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Breadcrumb (stylish, shows previous page) -->
            <div class="breadcrumb" aria-label="Breadcrumb">
                 <a href="admin_dasbord.php"><i class="fas fa-home"></i> Home</a>
                <?php if ($breadcrumb_prev && $breadcrumb_prev['name'] !== 'Dashboard' && $breadcrumb_prev['url'] !== $current_page['url']): ?>
                    <span class="sep">›</span>
                    <a href="<?= htmlspecialchars($breadcrumb_prev['url']) ?>"><i class="fas fa-arrow-left"></i> <?= htmlspecialchars($breadcrumb_prev['name']) ?></a>
                <?php endif; ?>
                <span class="sep">›</span>
                <span class="current"><i class="fas fa-chart-line"></i> <?= htmlspecialchars($current_page['name']) ?></span>
            </div>

            <!-- Adventure Trek Header -->
            <div class="trek-header">
                <h2 class="greeting" id="greeting">Hello, <?= htmlspecialchars($admin['name']) ?></h2>
                <p class="date-display" id="date-display">Loading date...</p>
                
                <div class="trek-stats">
                    <div class="trek-stat">
                        <div class="trek-stat-value" id="revenue-value">$<?= number_format($total_revenue, 2) ?></div>
                        <div class="trek-stat-label">Revenue</div>
                    </div>
                    <div class="trek-stat">
                        <div class="trek-stat-value" id="conversion-value"><?= $conversion_rate ?>%</div>
                        <div class="trek-stat-label">Conversion Rate</div>
                    </div>
                    <div class="trek-stat">
                        <div class="trek-stat-value" id="current-time">--:--</div>
                        <div class="trek-stat-label">Current Time</div>
                    </div>
                    <div class="trek-stat">
                        <div class="trek-stat-value" id="growth-value">+<?= $daily_growth ?>%</div>
                        <div class="trek-stat-label">Today's Growth</div>
                    </div>
                </div>
            </div>
            
            <!-- Performance Metrics -->
            <div class="performance-metrics">
                <div class="metric-card">
                    <div class="metric-title">This Week</div>
                    <div class="metric-value" id="week-value"><?= number_format($weekly_users_count) ?></div>
                    <div class="metric-change <?= $weekly_growth >= 0 ? 'positive' : 'negative' ?>"><?= $weekly_growth >= 0 ? '+' : '' ?><?= $weekly_growth ?>%</div>
                </div>
                <div class="metric-card">
                    <div class="metric-title">This Month</div>
                    <div class="metric-value" id="month-value"><?= number_format($monthly_users_count) ?></div>
                    <div class="metric-change <?= $monthly_growth >= 0 ? 'positive' : 'negative' ?>"><?= $monthly_growth >= 0 ? '+' : '' ?><?= $monthly_growth ?>%</div>
                </div>
                <div class="metric-card">
                    <div class="metric-title">Winning</div>
                    <div class="metric-value" id="winning-value"><?= number_format($winning_trips) ?></div>
                    <div class="metric-change positive">+ <?= $winning_growth ?>%</div>
                </div>
                <div class="metric-card">
                    <div class="metric-title">Last Week</div>
                    <div class="metric-value" id="lastweek-value"><?= number_format($last_week_count, 2) ?></div>
                    <div class="metric-change negative">- <?= $last_week_decline ?>%</div>
                </div>
            </div>
            
            <!-- Dashboard Sections -->
            <div class="dashboard-section">
                <!-- Left Column -->
                <div>
                    <!-- Trip Countdown -->
                    <div class="countdown-widget">
                        <div class="countdown-title">COUNTDOWN TO NEXT ADVENTURE</div>
                        <div class="countdown-days" id="countdown-days"><?= $days_until_trip ?> days</div>
                        <div class="countdown-destination">to trip to <?= $next_trip_destination ?></div>
                    </div>
                    
                    <!-- High Level Users -->
                    <div class="card" style="margin-top: 2rem;">
                        <h3 class="section-title">
                            <i class="fas fa-crown"></i> High Level Users
                        </h3>
                        
                        <div class="user-grid">
                            <?php if ($high_level_users && $high_level_users->num_rows > 0): ?>
                                <?php while ($user = $high_level_users->fetch_assoc()): ?>
                                <div class="user-card">
                                    <div class="user-card-header">
                                        <div>
                                            <h3><?= htmlspecialchars($user['name']) ?> <span class="high-level-badge">High Level</span></h3>
                                            <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                                        </div>
                                        <span class="status-badge"><?= $user['tasks_completed'] ?> Tasks</span>
                                    </div>
                                    
                                    <div class="user-meta">
                                        <i class="fas fa-calendar-alt"></i>
                                        Joined: <?= date('M d, Y', strtotime($user['created_at'])) ?>
                                    </div>
                                    
                                    <div class="ip-list">
                                        <h4>Achievements:</h4>
                                        <?php if ($user['achievements']): ?>
                                            <span class="achievement-badge"><?= htmlspecialchars($user['achievements']) ?></span>
                                        <?php else: ?>
                                            <div class="no-ips">No achievements recorded</div>
                                        <?php endif; ?>
                                        <div class="user-meta">
                                            <i class="fas fa-map-pin"></i>
                                            Destinations Added: <?= $user['destinations_added'] ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="no-ips">No high-level users found</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Recent Destinations -->
                    <div class="card" style="margin-top: 2rem;">
                        <h3 class="section-title">
                            <i class="fas fa-map-marker-alt"></i> Recent Destinations
                        </h3>
                        
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Location</th>
                                    <th>Added</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_destinations && $recent_destinations->num_rows > 0): ?>
                                    <?php while ($destination = $recent_destinations->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($destination['name']) ?></td>
                                            <td><?= htmlspecialchars($destination['location']) ?></td>
                                            <td><?= date('M j, Y', strtotime($destination['created_at'])) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center;">No destinations available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div>
                    <!-- Calendar Widget -->
                    <div class="calendar-widget">
                        <h3 class="section-title">
                            <i class="fas fa-calendar"></i> Calendar
                        </h3>
                        <div id="mini-calendar">
                            <!-- Calendar will be populated by JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Upcoming Events -->
                    <div class="card" style="margin-top: 2rem;">
                        <h3 class="section-title">
                            <i class="fas fa-calendar-check"></i> Upcoming Events
                        </h3>
                        <div class="event-list">
                            <div class="event-item">
                                <div>
                                    <div class="event-title">Team Meeting</div>
                                    <div class="event-date"><?= date('M j', strtotime('+1 week')) ?></div>
                                </div>
                            </div>
                            <div class="event-item">
                                <div>
                                    <div class="event-title">Performance Review</div>
                                    <div class="event-date"><?= date('M j', strtotime('-1 week')) ?></div>
                                </div>
                            </div>
                            <div class="event-item">
                                <div>
                                    <div class="event-title">Quarterly Planning</div>
                                    <div class="event-date"><?= date('M j', strtotime('+1 month')) ?></div>
                                </div>
                            </div>
                            <div class="event-item">
                                <div>
                                    <div class="event-title">Annual Retreat</div>
                                    <div class="event-date"><?= date('M j', strtotime('-1 month')) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Growth -->
            <div class="card" style="margin-top: 2rem;">
                <h3 class="section-title">
                    <i class="fas fa-chart-line"></i> User Growth (Last 6 Months)
                </h3>
                
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>User Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($growth_data && $growth_data->num_rows > 0): ?>
                            <?php while ($growth = $growth_data->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $growth['month'] ?></td>
                                    <td><?= $growth['user_count'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" style="text-align: center;">No growth data available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Recent Errors -->
            <div class="card" style="margin-top: 2rem;">
                <h3 class="section-title">
                    <i class="fas fa-exclamation-triangle"></i> Recent Errors
                </h3>
                
                <table>
                    <thead>
                        <tr>
                            <th>Message</th>
                            <th>IP Address</th>
                            <th>User ID</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_errors && $recent_errors->num_rows > 0): ?>
                            <?php while ($error = $recent_errors->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars(substr($error['message'], 0, 50)) . (strlen($error['message']) > 50 ? '...' : '') ?></td>
                                    <td><?= $error['ip_address'] ?></td>
                                    <td><?= $error['user_id'] ? $error['user_id'] : 'N/A' ?></td>
                                    <td><?= date('M j, Y H:i', strtotime($error['created_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">
                                    <?= $errors_table_exists ? 'No errors logged' : 'Errors table not available' ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Top Error-Prone IPs -->
            <div class="card" style="margin-top: 2rem;">
                <h3 class="section-title">
                    <i class="fas fa-network-wired"></i> Top Error-Prone IPs
                </h3>
                
                <table>
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th>Error Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($top_error_ips && $top_error_ips->num_rows > 0): ?>
                            <?php while ($ip = $top_error_ips->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $ip['ip_address'] ?></td>
                                    <td><?= $ip['error_count'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" style="text-align: center;">
                                    <?= $errors_table_exists ? 'No error data available' : 'Errors table not available' ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal" id="editProfileModal">
        <div class="modal-content">
            <h2>Edit Profile</h2>
            <form action="update_profile.php" method="POST" enctype="multipart/form-data">
                <input type="text" name="name" value="<?= htmlspecialchars($admin['name']) ?>" required placeholder="Name">
                <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required placeholder="Email">
                <input type="password" name="password" placeholder="New Password (leave blank to keep current)">
                <input type="file" name="profile_pic" accept="image/*">
                <button type="submit">Save Changes</button>
                <button type="button" class="close">Close</button>
            </form>
        </div>
    </div>

    <script>
        // Real-time clock and date
        function updateDateTime() {
            const now = new Date();
            const hours = now.getHours();
            
            // Set greeting based on time of day
            let greeting = "Good Morning";
            if (hours >= 12 && hours < 17) {
                greeting = "Good Afternoon";
            } else if (hours >= 17) {
                greeting = "Good Evening";
            }
            
            document.getElementById('greeting').textContent = `${greeting}, <?= htmlspecialchars($admin['name']) ?>`;            
            // Format date
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const dateStr = now.toLocaleDateString('en-US', options);
            const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            
            document.getElementById('date-display').textContent = `${dateStr} | ${timeStr}`;
            document.getElementById('current-time').textContent = timeStr;
        }
        
        // Update clock immediately and every minute
        updateDateTime();
        setInterval(updateDateTime, 60000);
        
        // Mini calendar
        function renderMiniCalendar() {
            const now = new Date();
            const year = now.getFullYear();
            const month = now.getMonth();
            
            // Get first day of month and number of days
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            
            // Day names abbreviations
            const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            
            let calendarHTML = '';
            
            // Create header with month/year
            calendarHTML += `
                <div class="calendar-header">
                    <span><strong>${now.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}</strong></span>
                </div>
                <div class="calendar-grid">
            `;
            
            // Add day headers
            for (let i = 0; i < 7; i++) {
                calendarHTML += `<div class="calendar-day header">${dayNames[i]}</div>`;
            }
            
            // Add empty cells for days before the first day of month
            for (let i = 0; i < firstDay; i++) {
                calendarHTML += `<div class="calendar-day other-month"></div>`;
            }
            
            // Add days of the month
            for (let i = 1; i <= daysInMonth; i++) {
                const isToday = i === now.getDate() && month === now.getMonth() && year === now.getFullYear();
                calendarHTML += `<div class="calendar-day ${isToday ? 'today' : ''}">${i}</div>`;
            }
            
            calendarHTML += `</div>`;
            
            document.getElementById('mini-calendar').innerHTML = calendarHTML;
        }
        
        // Render calendar on page load
        renderMiniCalendar();
        
        // Dropdown menu functionality
        const userProfile = document.getElementById('userProfile');
        const profileDropdown = document.getElementById('profileDropdown');
        
        userProfile.addEventListener('click', () => {
            profileDropdown.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        window.addEventListener('click', (e) => {
            if (!userProfile.contains(e.target) && !profileDropdown.contains(e.target)) {
                profileDropdown.classList.remove('active');
            }
        });
        
        // Modal functionality
        const modal = document.getElementById('editProfileModal');
        const closeBtn = modal.querySelector('.close');
        const editProfileBtn = document.getElementById('editProfileBtn');
        
        editProfileBtn.addEventListener('click', () => {
            modal.style.display = 'flex';
            profileDropdown.classList.remove('active');
        });
        
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });
        
        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        // Menu item active state
        const menuItems = document.querySelectorAll('.menu-item');
        
        menuItems.forEach(item => {
            item.addEventListener('click', () => {
                menuItems.forEach(i => i.classList.remove('active'));
                item.classList.add('active');
            });
        });
        
        // Update notification count
        document.getElementById('notification-count').textContent = <?= $total_errors > 0 ? ($total_errors > 9 ? '9+' : $total_errors) : '0' ?>;
    </script>
</body>
</html>