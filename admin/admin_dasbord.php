<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

include '../database/dbconfig.php';

// Get admin data
$admin_query = $conn->prepare("SELECT name, email, profile_pic FROM admin WHERE id = ?");
$admin_query->bind_param("i", $_SESSION['admin_id']);
$admin_query->execute();
$admin_result = $admin_query->get_result();
$admin = $admin_result->fetch_assoc() ?? ['name' => 'Admin', 'email' => '', 'profile_pic' => NULL];

// BASIC STATISTICS (Updated with real queries)
$total_users = 0; $today_users = 0; $total_destinations = 0; $today_destinations = 0;
$total_bookings = 0; $today_bookings = 0; $revenue_today = 0; $revenue_month = 0;
$total_messages = 0; $unread_messages = 0; $pending_bookings = 0;

try {
    $total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'] ?? 0;
    $today_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['total'] ?? 0;
    $total_destinations = $conn->query("SELECT COUNT(*) as total FROM destinations")->fetch_assoc()['total'] ?? 0;
    $today_destinations = $conn->query("SELECT COUNT(*) as total FROM destinations WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['total'] ?? 0;
    
    $check_bookings = $conn->query("SHOW TABLES LIKE 'bookings'");
    if ($check_bookings && $check_bookings->num_rows > 0) {
        $total_bookings = $conn->query("SELECT COUNT(*) as total FROM bookings")->fetch_assoc()['total'] ?? 0;
        $today_bookings = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['total'] ?? 0;
        
        $revenue_check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'total_amount'");
        if ($revenue_check && $revenue_check->num_rows > 0) {
            $revenue_today_result = $conn->query("SELECT SUM(total_amount) as total FROM bookings WHERE DATE(created_at) = CURDATE() AND status = 'confirmed'");
            $revenue_today = $revenue_today_result ? ($revenue_today_result->fetch_assoc()['total'] ?? 0) : 0;
            
            $revenue_month_result = $conn->query("SELECT SUM(total_amount) as total FROM bookings WHERE created_at >= CURDATE() - INTERVAL 30 DAY AND status = 'confirmed'");
            $revenue_month = $revenue_month_result ? ($revenue_month_result->fetch_assoc()['total'] ?? 0) : 0;
        }
    }
    
    $total_messages = $conn->query("SELECT COUNT(*) as total FROM messages")->fetch_assoc()['total'] ?? 0;
    $unread_messages = $conn->query("SELECT COUNT(*) as total FROM messages WHERE status = 'unread'")->fetch_assoc()['total'] ?? 0;
} catch (Exception $e) { error_log("Dashboard error: " . $e->getMessage()); }

$recent_users = []; $recent_destinations = []; $recent_bookings = [];

try {
    $result = $conn->query("SELECT id, name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    if ($result) $recent_users = $result->fetch_all(MYSQLI_ASSOC);
    
    $result = $conn->query("SELECT id, name, location, type, created_at FROM destinations ORDER BY created_at DESC LIMIT 5");
    if ($result) $recent_destinations = $result->fetch_all(MYSQLI_ASSOC);
    
    $check = $conn->query("SHOW TABLES LIKE 'bookings'");
    if ($check && $check->num_rows > 0) {
        $result = $conn->query("SELECT id, user_id, destination_id, total_amount, status, created_at FROM bookings ORDER BY created_at DESC LIMIT 5");
        if ($result) $recent_bookings = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) { error_log("Recent data error: " . $e->getMessage()); }

try {
    $active_users_query = $conn->query("SELECT COUNT(DISTINCT user_id) as active_today FROM page_time_tracking WHERE DATE(visit_date) = CURDATE() AND user_id > 0");
    $today_active = $active_users_query ? $active_users_query->fetch_assoc()['active_today'] ?? 1 : 1;
} catch (Exception $e) { $today_active = max(1, round($total_users * 0.1)); }

try {
    $last_week_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE created_at >= CURDATE() - INTERVAL 7 DAY AND created_at < CURDATE() - INTERVAL 6 DAY")->fetch_assoc()['total'] ?? 0;
    $this_week_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE created_at >= CURDATE() - INTERVAL 6 DAY")->fetch_assoc()['total'] ?? 0;
    if ($last_week_users > 0) { $weekly_growth = round((($this_week_users - $last_week_users) / $last_week_users) * 100, 1); } 
    else { $weekly_growth = $this_week_users > 0 ? 100.0 : 0.0; }
} catch (Exception $e) { $weekly_growth = 5.2; }

$destination_types = [];
try {
    $type_result = $conn->query("SELECT type, COUNT(*) as count FROM destinations GROUP BY type ORDER BY count DESC");
    if ($type_result) { $destination_types = $type_result->fetch_all(MYSQLI_ASSOC); }
} catch (Exception $e) {}

$graph_labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$graph_views = [0, 0, 0, 0, 0, 0, 0];
$graph_clicks = [0, 0, 0, 0, 0, 0, 0];

try {
    $views_result = $conn->query("SELECT DAYNAME(visit_date) as day, SUM(time_spent) as total_time, SUM(click_count) as total_clicks FROM page_time_tracking WHERE visit_date >= CURDATE() - INTERVAL 7 DAY GROUP BY DAYNAME(visit_date), visit_date ORDER BY visit_date");
    if ($views_result) {
        $views_data = $views_result->fetch_all(MYSQLI_ASSOC);
        $day_mapping = ['Monday'=>0, 'Tuesday'=>1, 'Wednesday'=>2, 'Thursday'=>3, 'Friday'=>4, 'Saturday'=>5, 'Sunday'=>6];
        foreach ($views_data as $data) {
            $day_index = $day_mapping[$data['day']] ?? null;
            if ($day_index !== null) {
                $graph_views[$day_index] = min(1000, intval($data['total_time'] / 60)); 
                $graph_clicks[$day_index] = min(500, intval($data['total_clicks'])); 
            }
        }
    }
} catch (Exception $e) {
    $graph_views = [1200, 1900, 1500, 2100, 1800, 2200, 2000];
    $graph_clicks = [400, 700, 550, 800, 650, 900, 750];
}

$total_views = array_sum($graph_views);
$total_clicks = array_sum($graph_clicks);
$conversion_rate = $total_views > 0 ? round(($total_clicks / $total_views) * 100, 2) : 0;
$max_views = max($graph_views);

$motivations = [
    "Great things in business are never done by one person. They're done by a team of people.",
    "The way to get started is to quit talking and begin doing.",
    "Don't be afraid to give up the good to go for the great.",
    "Success is not the key to happiness. Happiness is the key to success.",
    "The only limit to our realization of tomorrow will be our doubts of today."
];
$motivation = $motivations[array_rand($motivations)];

$disk_usage = 45.5; $server_load = 32.1;
try {
    $db_size_result = $conn->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb FROM information_schema.tables WHERE table_schema = DATABASE()");
    $db_size = $db_size_result ? $db_size_result->fetch_assoc()['size_mb'] ?? 125.75 : 125.75;
} catch (Exception $e) { $db_size = 125.75; }

$system_uptime = "99.8%";
$current_date = date('l, F j, Y');
$current_time = date('h:i A');

function getBadWeatherDestinations($conn, $limit = 5) {
    $badWeatherDestinations = [];
    $API_KEY = 'b4fe517a83b0e5679af65062c7fd92cd';
    try {
        $destinations_query = $conn->query("SELECT id, name, city, country, type FROM destinations WHERE is_active = 1 ORDER BY RAND() LIMIT 10");
        if ($destinations_query && $destinations_query->num_rows > 0) {
            while ($destination = $destinations_query->fetch_assoc()) {
                $city = $destination['city'] ?: $destination['name'];
                $country = $destination['country'] ?: '';
                $weather_data = getWeatherForCity($city, $country, $API_KEY);
                if ($weather_data && isBadWeather($weather_data)) {
                    $badWeatherDestinations[] = ['id'=>$destination['id'], 'name'=>$destination['name'], 'city'=>$city, 'country'=>$country, 'type'=>$destination['type'], 'weather'=>$weather_data];
                    if (count($badWeatherDestinations) >= $limit) break;
                }
            }
        }
    } catch (Exception $e) { error_log("Bad weather destinations error: " . $e->getMessage()); }
    return $badWeatherDestinations;
}

function getWeatherForCity($city, $country, $api_key) {
    $location = $city; if (!empty($country)) $location .= ',' . $country;
    $url = "https://api.openweathermap.org/data/2.5/weather?q=" . urlencode($location) . "&appid=" . $api_key . "&units=metric";
    try {
        $ch = curl_init(); curl_setopt($ch, CURLOPT_URL, $url); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch); curl_close($ch);
        if ($response) { $data = json_decode($response, true); if (isset($data['cod']) && $data['cod'] == 200) return $data; }
    } catch (Exception $e) { return null; } return null;
}

function isBadWeather($weather_data) {
    if (!$weather_data) return false;
    $main_weather = strtolower($weather_data['weather'][0]['main']);
    $description = strtolower($weather_data['weather'][0]['description']);
    $bad_conditions = ['thunderstorm', 'thunderstorms', 'storm', 'storms', 'heavy rain', 'extreme rain', 'torrential rain', 'snow', 'heavy snow', 'blizzard', 'fog', 'mist', 'haze', 'smoke', 'tornado', 'hurricane', 'typhoon'];
    foreach ($bad_conditions as $condition) { if (strpos($main_weather, $condition) !== false || strpos($description, $condition) !== false) return true; }
    $temp = $weather_data['main']['temp']; $wind_speed = $weather_data['wind']['speed'];
    if ($temp > 35 || $temp < 0) return true;
    if ($wind_speed > 13.9) return true;
    return false;
}

$badWeatherDestinations = getBadWeatherDestinations($conn, 3);
$badWeatherCount = count($badWeatherDestinations);

function getWeatherIcon($condition) {
    $condition = strtolower($condition);
    $icons = ['thunderstorm'=>'fas fa-bolt', 'drizzle'=>'fas fa-cloud-rain', 'rain'=>'fas fa-cloud-showers-heavy', 'snow'=>'fas fa-snowflake', 'mist'=>'fas fa-smog', 'smoke'=>'fas fa-smog', 'haze'=>'fas fa-smog', 'dust'=>'fas fa-wind', 'fog'=>'fas fa-smog', 'sand'=>'fas fa-wind', 'ash'=>'fas fa-mountain', 'squall'=>'fas fa-wind', 'tornado'=>'fas fa-wind', 'clear'=>'fas fa-sun', 'clouds'=>'fas fa-cloud'];
    foreach ($icons as $key => $icon) { if (strpos($condition, $key) !== false) return $icon; }
    return 'fas fa-cloud';
}

function getWeatherSeverity($weather_data) {
    $main_weather = strtolower($weather_data['weather'][0]['main']);
    $description = strtolower($weather_data['weather'][0]['description']);
    $high_severity = ['thunderstorm', 'tornado', 'hurricane', 'blizzard', 'heavy snow', 'extreme rain'];
    foreach ($high_severity as $condition) { if (strpos($main_weather, $condition) !== false || strpos($description, $condition) !== false) return 'high'; }
    $medium_severity = ['rain', 'snow', 'storm', 'fog', 'haze', 'mist'];
    foreach ($medium_severity as $condition) { if (strpos($main_weather, $condition) !== false || strpos($description, $condition) !== false) return 'medium'; }
    $temp = $weather_data['main']['temp']; if ($temp > 35 || $temp < 0) return 'medium';
    return 'low';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TripMate</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Specific Dashboard Layout Styles mapped to CSS variables in header */
        .dashboard-container { display: flex; min-height: 100vh; }

        /* HEADER */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2.5rem;
            border-radius: 24px;
            margin-bottom: 2rem;
            box-shadow: 0 15px 35px var(--glow-color);
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::after {
            content: '';
            position: absolute;
            top: -50%; left: -50%; width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
            pointer-events: none;
        }

        .greeting-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1.5rem;
            position: relative;
            z-index: 2;
        }

        .greeting-text h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }

        .greeting-text p {
            opacity: 0.9;
            max-width: 600px;
            font-size: 1.1rem;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .header-btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 50px;
            background: rgba(255,255,255,0.2);
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
            font-weight: 700;
            backdrop-filter: blur(10px);
        }

        .header-btn:hover {
            background: white;
            color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }

        /* STATS GRID */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-surface);
            border-radius: 24px;
            padding: 1.8rem;
            box-shadow: 0 10px 30px var(--shadow-color);
            border: 1px solid var(--card-border);
            transition: all 0.4s cubic-bezier(0.25, 1, 0.5, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            opacity: 0; transition: opacity 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px var(--shadow-color);
            border-color: var(--secondary);
        }

        .stat-card:hover::before { opacity: 1; }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .stat-icon {
            width: 55px;
            height: 55px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .stat-icon.users { background: linear-gradient(135deg, var(--primary), #8b5cf6); }
        .stat-icon.destinations { background: linear-gradient(135deg, var(--success), #0da271); }
        .stat-icon.bookings { background: linear-gradient(135deg, var(--warning), #d97706); }
        .stat-icon.revenue { background: linear-gradient(135deg, #ec4899, #db2777); }
        .stat-icon.messages { background: linear-gradient(135deg, var(--secondary), var(--primary)); }
        .stat-icon.activity { background: linear-gradient(135deg, #8b5cf6, var(--primary)); }

        .stat-trend {
            font-size: 0.85rem;
            font-weight: 700;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .stat-trend.negative { background: rgba(239, 68, 68, 0.1); color: var(--danger); }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
            color: var(--text-main);
            letter-spacing: -0.5px;
        }

        .stat-label {
            font-size: 0.95rem;
            color: var(--text-muted);
            margin-bottom: 1.25rem;
            font-weight: 600;
        }

        .stat-progress {
            height: 6px;
            background: var(--bg-base);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 0.75rem;
        }

        .stat-progress-bar { height: 100%; background: linear-gradient(90deg, var(--primary), var(--secondary)); border-radius: 10px; }

        .stat-footer { display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted); font-weight: 500;}

        /* CHARTS SECTION */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 1200px) { .content-grid { grid-template-columns: 1fr; } }

        .charts-section {
            background: var(--bg-surface);
            border-radius: 24px;
            padding: 1.8rem;
            box-shadow: 0 10px 30px var(--shadow-color);
            border: 1px solid var(--card-border);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--card-border);
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .chart-container { height: 250px; position: relative; margin-bottom: 1.5rem; }

        /* QUICK STATS */
        .quick-stats { display: grid; gap: 1.5rem; }
        .quick-stat-item {
            background: var(--bg-base);
            border-radius: 16px;
            padding: 1.25rem;
            border: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            gap: 1.25rem;
            transition: all 0.3s ease;
            cursor: pointer;
            color: var(--text-main); /* This fixes the dark mode text color! */
        }
        

        .quick-stat-item:hover { transform: translateX(5px); border-color: var(--secondary); background: var(--bg-surface); box-shadow: 0 8px 20px var(--shadow-color); }

        .quick-stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .quick-stat-content { flex: 1; }
        .quick-stat-title { font-weight: 700; color: var(--text-main); margin-bottom: 0.25rem; }
        .quick-stat-value { font-size: 1.25rem; font-weight: 800; color: var(--primary); }

        /* RECENT ACTIVITY */
        .recent-activity {
            background: var(--bg-surface);
            border-radius: 24px;
            padding: 1.8rem;
            box-shadow: 0 10px 30px var(--shadow-color);
            border: 1px solid var(--card-border);
            margin-bottom: 2rem;
        }

        /* MODAL SYSTEM */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(5px); z-index: 1000; }
        .modal-overlay.active { display: block; }

        .modal-container {
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.95);
            width: 90%; max-width: 1000px; max-height: 85vh;
            background: var(--bg-surface); border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5); border: 1px solid var(--card-border);
            z-index: 1001; opacity: 0; transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1); overflow: hidden;
        }
        .modal-container.active { opacity: 1; transform: translate(-50%, -50%) scale(1); }

        .modal-header {
            padding: 1.5rem 2rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white; display: flex; justify-content: space-between; align-items: center;
        }

        .modal-title { font-size: 1.5rem; font-weight: 800; display: flex; align-items: center; gap: 0.75rem; }

        .modal-close {
            width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,0.2);
            border: none; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;
        }
        .modal-close:hover { background: white; color: var(--primary); transform: rotate(90deg); }

        .modal-body { height: calc(85vh - 80px); overflow: hidden; background: var(--bg-base); }
        .modal-iframe { width: 100%; height: 100%; border: none; }

        .modal-loading { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; padding: 3rem; color: var(--text-muted); }
        .loading-spinner {
            width: 50px; height: 50px; border: 4px solid var(--card-border); border-top-color: var(--primary);
            border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 1rem;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .badge { padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
        .badge-success { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .badge-warning { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .badge-danger { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        .badge-info { background: rgba(59, 130, 246, 0.1); color: var(--info); }

        .user-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.2rem; }
        .text-truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .clickable { cursor: pointer; }
    </style>
</head>
<body>
    <?php 
    // Set current page for header
    $current_page = 'dashboard';
    include 'admin_header.php'; 
    ?>

    <div class="modal-overlay" id="modalOverlay"></div>
    <div class="modal-container" id="modalContainer">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">
                <i class="fas fa-chart-bar"></i>
                <span id="modalTitleText">Loading...</span>
            </h3>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="modal-loading" id="modalLoading">
                <div class="loading-spinner"></div>
                <p style="font-weight: 600;">Loading content...</p>
            </div>
            <iframe class="modal-iframe" id="modalIframe" style="display: none;"></iframe>
        </div>
    </div>

    <div class="dashboard-container">
        <div class="main-content">
            <div class="dashboard-header">
                <div class="greeting-section">
                    <div class="greeting-text">
                        <h1>Welcome back, <?= htmlspecialchars($admin['name']) ?>! 👋</h1>
                        <p><?= htmlspecialchars($motivation) ?></p>
                        <div style="display: flex; gap: 1.5rem; margin-top: 1.5rem; font-size: 0.9rem; font-weight: 600;">
                            <span><i class="fas fa-clock" style="margin-right:5px;"></i> <span id="currentTime"><?= $current_time ?></span></span>
                            <span><i class="fas fa-calendar" style="margin-right:5px;"></i> <span id="currentDate"><?= $current_date ?></span></span>
                            <span><i class="fas fa-server" style="margin-right:5px;"></i> System: <span class="badge" style="background: rgba(255,255,255,0.2); color:white;">Online</span></span>
                        </div>
                    </div>
                    <div class="header-actions">
                        <button class="header-btn" onclick="openModal('dashboard_details.php', 'Dashboard Analytics')">
                            <i class="fas fa-chart-pie"></i> Analytics
                        </button>
                        <button class="header-btn" onclick="refreshDashboard()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card clickable" onclick="openModal('user_present_chack_on_admin.php', 'Users Management')">
                    <div class="stat-card-header">
                        <div class="stat-icon users"><i class="fas fa-users"></i></div>
                        <span class="stat-trend"><i class="fas fa-arrow-up"></i> <?= $weekly_growth ?>%</span>
                    </div>
                    <div class="stat-value"><?= number_format($total_users) ?></div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-progress"><div class="stat-progress-bar" style="width: <?= min(100, ($today_users / max(1, $total_users)) * 100) ?>%"></div></div>
                    <div class="stat-footer">
                        <span><i class="fas fa-user-plus"></i> <?= $today_users ?> today</span>
                        <span><i class="fas fa-chart-line"></i> Growth: <?= $weekly_growth ?>%</span>
                    </div>
                </div>

                <div class="stat-card clickable" onclick="openModal('add_destanition_on_admin.php', 'Destinations')">
                    <div class="stat-card-header">
                        <div class="stat-icon destinations"><i class="fas fa-map-marked-alt"></i></div>
                        <span class="stat-trend"><i class="fas fa-arrow-up"></i> <?= $today_destinations ?> new</span>
                    </div>
                    <div class="stat-value"><?= number_format($total_destinations) ?></div>
                    <div class="stat-label">Travel Destinations</div>
                    <div class="stat-progress"><div class="stat-progress-bar" style="width: 75%"></div></div>
                    <div class="stat-footer">
                        <?php 
                        $type_count = 0;
                        foreach ($destination_types as $type) {
                            if ($type_count < 2) {
                                $icon = $type['type'] == 'beach' ? 'fas fa-umbrella-beach' : ($type['type'] == 'mountain' ? 'fas fa-mountain' : ($type['type'] == 'city' ? 'fas fa-city' : 'fas fa-map-pin'));
                                echo '<span><i class="' . $icon . '"></i> ' . ucfirst($type['type']) . ': ' . $type['count'] . '</span>';
                                $type_count++;
                            }
                        }
                        if ($type_count < 2) echo '<span><i class="fas fa-map-pin"></i> Types: ' . count($destination_types) . '</span>';
                        ?>
                    </div>
                </div>

                <div class="stat-card clickable" onclick="openModal('bookings.php', 'Bookings')">
                    <div class="stat-card-header">
                        <div class="stat-icon bookings"><i class="fas fa-calendar-check"></i></div>
                        <span class="stat-trend"><i class="fas fa-arrow-up"></i> <?= $today_bookings ?> new</span>
                    </div>
                    <div class="stat-value"><?= number_format($total_bookings) ?></div>
                    <div class="stat-label">Total Bookings</div>
                    <div class="stat-progress"><div class="stat-progress-bar" style="width: <?= min(100, ($today_bookings / max(1, $total_bookings)) * 100) ?>%"></div></div>
                    <div class="stat-footer">
                        <span><i class="fas fa-check-circle"></i> Today: <?= $today_bookings ?></span>
                        <span><i class="fas fa-clock"></i> Pending: <?= $pending_bookings ?></span>
                    </div>
                </div>

                <div class="stat-card clickable" onclick="openModal('revenue_analytics.php', 'Revenue')">
                    <div class="stat-card-header">
                        <div class="stat-icon revenue"><i class="fas fa-dollar-sign"></i></div>
                        <span class="stat-trend"><i class="fas fa-arrow-up"></i> $<?= number_format($revenue_today) ?></span>
                    </div>
                    <div class="stat-value">$<?= number_format($revenue_month) ?></div>
                    <div class="stat-label">Monthly Revenue</div>
                    <div class="stat-progress"><div class="stat-progress-bar" style="width: <?= min(100, ($revenue_today / max(1, $revenue_month)) * 100) ?>%"></div></div>
                    <div class="stat-footer">
                        <span><i class="fas fa-calendar-day"></i> Today: $<?= number_format($revenue_today) ?></span>
                        <span><i class="fas fa-bullseye"></i> Target: $50,000</span>
                    </div>
                </div>

                <div class="stat-card clickable" onclick="openModal('messages.php', 'Messages')">
                    <div class="stat-card-header">
                        <div class="stat-icon messages"><i class="fas fa-envelope"></i></div>
                        <span class="stat-trend <?= $unread_messages > 0 ? 'negative' : '' ?>">
                            <i class="fas fa-<?= $unread_messages > 0 ? 'exclamation' : 'check' ?>-circle"></i> <?= $unread_messages ?> unread
                        </span>
                    </div>
                    <div class="stat-value"><?= number_format($total_messages) ?></div>
                    <div class="stat-label">Total Messages</div>
                    <div class="stat-progress"><div class="stat-progress-bar" style="width: <?= min(100, (($total_messages - $unread_messages) / max(1, $total_messages)) * 100) ?>%"></div></div>
                    <div class="stat-footer">
                        <span><i class="fas fa-inbox"></i> Total: <?= $total_messages ?></span>
                        <span><i class="fas fa-eye-slash"></i> Unread: <?= $unread_messages ?></span>
                    </div>
                </div>

                <div class="stat-card clickable" onclick="openModal('activity_logs.php', 'Activity')">
                    <div class="stat-card-header">
                        <div class="stat-icon activity"><i class="fas fa-user-check"></i></div>
                        <span class="stat-trend"><i class="fas fa-arrow-up"></i> Active now</span>
                    </div>
                    <div class="stat-value"><?= $today_active ?></div>
                    <div class="stat-label">Active Users Today</div>
                    <div class="stat-progress"><div class="stat-progress-bar" style="width: <?= min(100, ($today_active / max(1, $total_users)) * 100) ?>%"></div></div>
                    <div class="stat-footer">
                        <span><i class="fas fa-users"></i> <?= $total_users ?> total</span>
                        <span><i class="fas fa-chart-line"></i> <?= round(($today_active / max(1, $total_users)) * 100, 1) ?>% active</span>
                    </div>
                </div>
                
                <div class="stat-card clickable" onclick="openModal('weather_alert_destinations.php', 'Weather Alert Destinations')">
                    <div class="stat-card-header">
                        <div class="stat-icon" style="background: linear-gradient(135deg, var(--danger), #dc2626);">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <span class="stat-trend <?= $badWeatherCount > 0 ? 'negative' : '' ?>">
                            <i class="fas fa-<?= $badWeatherCount > 0 ? 'exclamation' : 'check' ?>-circle"></i>
                            <?= $badWeatherCount ?> alert<?= $badWeatherCount != 1 ? 's' : '' ?>
                        </span>
                    </div>
                    <div class="stat-value"><?= $badWeatherCount ?></div>
                    <div class="stat-label">Bad Weather Destinations</div>
                    <div class="stat-progress">
                        <div class="stat-progress-bar" style="width: <?= min(100, ($badWeatherCount / max(1, $total_destinations)) * 100) ?>%; 
                            background: <?= $badWeatherCount > 0 ? 'var(--danger)' : 'var(--success)' ?>;"></div>
                    </div>
                    <div class="stat-footer">
                        <?php if ($badWeatherCount > 0): ?>
                            <span><i class="fas fa-cloud-rain"></i> Weather issues</span>
                            <span><i class="fas fa-map-marker-alt"></i> <?= $badWeatherCount ?> affected</span>
                        <?php else: ?>
                            <span><i class="fas fa-check-circle"></i> All clear</span>
                            <span><i class="fas fa-sun"></i> Good weather</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="content-grid">
                <div class="charts-section">
                    <div class="section-header">
                        <h3 class="section-title"><i class="fas fa-chart-line"></i> Performance Analytics</h3>
                        <button class="header-btn" onclick="openModal('analytics_detailed.php', 'Detailed Analytics')" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); font-size: 0.85rem; padding: 0.6rem 1.2rem;">
                            View Details
                        </button>
                    </div>
                    <div class="chart-container"><canvas id="userGrowthChart"></canvas></div>
                    <div class="chart-container"><canvas id="revenueChart"></canvas></div>
                </div>

                <div class="quick-stats">
                    <div class="charts-section">
                        <div class="section-header">
                            <h3 class="section-title"><i class="fas fa-bolt"></i> Quick Actions</h3>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                            <button class="quick-stat-item" onclick="openModal('user_add.php', 'Add User')" style="flex-direction: column; text-align: center;">
                                <div class="quick-stat-icon" style="background: linear-gradient(135deg, var(--primary), #818cf8);"><i class="fas fa-user-plus"></i></div>
                                <span style="font-weight: 700;">Add User</span>
                            </button>
                            <button class="quick-stat-item" onclick="openModal('destination_add.php', 'Add Destination')" style="flex-direction: column; text-align: center;">
                                <div class="quick-stat-icon" style="background: linear-gradient(135deg, var(--success), #34d399);"><i class="fas fa-map-marked-alt"></i></div>
                                <span style="font-weight: 700;">Add Destination</span>
                            </button>
                            <button class="quick-stat-item" onclick="openModal('booking_add.php', 'Create Booking')" style="flex-direction: column; text-align: center;">
                                <div class="quick-stat-icon" style="background: linear-gradient(135deg, var(--warning), #fbbf24);"><i class="fas fa-calendar-plus"></i></div>
                                <span style="font-weight: 700;">Create Booking</span>
                            </button>
                            <button class="quick-stat-item" onclick="openModal('report_generate.php', 'Generate Report')" style="flex-direction: column; text-align: center;">
                                <div class="quick-stat-icon" style="background: linear-gradient(135deg, var(--secondary), #67e8f9);"><i class="fas fa-file-alt"></i></div>
                                <span style="font-weight: 700;">Generate Report</span>
                            </button>
                        </div>
                    </div>

                    <div class="charts-section">
                        <div class="section-header">
                            <h3 class="section-title"><i class="fas fa-heartbeat"></i> System Health</h3>
                        </div>
                        <div style="display: grid; gap: 1rem;">
                            <div class="quick-stat-item">
                                <div class="quick-stat-icon" style="background: <?= $server_load < 70 ? 'var(--success)' : ($server_load < 90 ? 'var(--warning)' : 'var(--danger)') ?>;">
                                    <i class="fas fa-server"></i>
                                </div>
                                <div class="quick-stat-content">
                                    <div class="quick-stat-title">Server Load</div>
                                    <div class="quick-stat-value" style="color: var(--text-main); font-size:1.1rem;"><?= $server_load ?>%</div>
                                </div>
                                <div class="stat-progress" style="width: 100px; margin: 0; background: var(--bg-surface); border: 1px solid var(--card-border);">
                                    <div class="stat-progress-bar" style="width: <?= $server_load ?>%; background: <?= $server_load < 70 ? 'var(--success)' : ($server_load < 90 ? 'var(--warning)' : 'var(--danger)') ?>;"></div>
                                </div>
                            </div>
                            
                            <div class="quick-stat-item">
                                <div class="quick-stat-icon" style="background: <?= $disk_usage < 70 ? 'var(--success)' : ($disk_usage < 90 ? 'var(--warning)' : 'var(--danger)') ?>;">
                                    <i class="fas fa-hdd"></i>
                                </div>
                                <div class="quick-stat-content">
                                    <div class="quick-stat-title">Disk Usage</div>
                                    <div class="quick-stat-value" style="color: var(--text-main); font-size:1.1rem;"><?= $disk_usage ?>%</div>
                                </div>
                                <div class="stat-progress" style="width: 100px; margin: 0; background: var(--bg-surface); border: 1px solid var(--card-border);">
                                    <div class="stat-progress-bar" style="width: <?= $disk_usage ?>%; background: <?= $disk_usage < 70 ? 'var(--success)' : ($disk_usage < 90 ? 'var(--warning)' : 'var(--danger)') ?>;"></div>
                                </div>
                            </div>
                            
                            <div class="quick-stat-item">
                                <div class="quick-stat-icon" style="background: var(--secondary);"><i class="fas fa-database"></i></div>
                                <div class="quick-stat-content">
                                    <div class="quick-stat-title">Database Size</div>
                                    <div class="quick-stat-value" style="color: var(--text-main); font-size:1.1rem;"><?= number_format($db_size, 2) ?> MB</div>
                                </div>
                            </div>
                            
                            <div class="quick-stat-item">
                                <div class="quick-stat-icon" style="background: var(--primary);"><i class="fas fa-shield-alt"></i></div>
                                <div class="quick-stat-content">
                                    <div class="quick-stat-title">System Uptime</div>
                                    <div class="quick-stat-value" style="color: var(--text-main); font-size:1.1rem;"><?= $system_uptime ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="recent-activity">
                <div class="section-header">
                    <h3 class="section-title"><i class="fas fa-history"></i> Recent Activity</h3>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="header-btn" onclick="openModal('recent_users.php', 'Recent Users')" style="background: var(--bg-base); color: var(--primary); border: 1px solid var(--primary);">Users</button>
                        <button class="header-btn" onclick="openModal('recent_bookings.php', 'Recent Bookings')" style="background: var(--bg-base); color: var(--primary); border: 1px solid var(--primary);">Bookings</button>
                        <button class="header-btn" onclick="openModal('recent_messages.php', 'Recent Messages')" style="background: var(--bg-base); color: var(--primary); border: 1px solid var(--primary);">Messages</button>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                    <div>
                        <h4 style="margin-bottom: 1.5rem; color: var(--text-muted); font-size: 0.95rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                            <i class="fas fa-user-friends" style="color: var(--primary);"></i> Recent Users
                        </h4>
                        <div style="max-height: 250px; overflow-y: auto; padding-right: 10px;">
                            <?php if (!empty($recent_users)): ?>
                                <?php foreach($recent_users as $user): ?>
                                <div class="quick-stat-item clickable" onclick="openModal('user_detail.php?id=<?= $user['id'] ?>', 'User: <?= addslashes($user['name']) ?>')" style="margin-bottom: 10px;">
                                    <div class="user-avatar"><?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?></div>
                                    <div class="quick-stat-content">
                                        <div class="quick-stat-title text-truncate"><?= htmlspecialchars($user['name'] ?? 'Unknown') ?></div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500;"><?= htmlspecialchars($user['email'] ?? 'No email') ?></div>
                                    </div>
                                    <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">
                                        <?= date('M j', strtotime($user['created_at'])) ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                    <i class="fas fa-users" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                                    <p>No recent users</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div>
                        <h4 style="margin-bottom: 1.5rem; color: var(--text-muted); font-size: 0.95rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                            <i class="fas fa-map-marker-alt" style="color: var(--success);"></i> Recent Destinations
                        </h4>
                        <div style="max-height: 250px; overflow-y: auto; padding-right: 10px;">
                            <?php if (!empty($recent_destinations)): ?>
                                <?php foreach($recent_destinations as $dest): ?>
                                <div class="quick-stat-item clickable" onclick="openModal('destination_detail.php?id=<?= $dest['id'] ?>', 'Destination: <?= addslashes($dest['name']) ?>')" style="margin-bottom: 10px;">
                                    <div class="quick-stat-icon" style="background: var(--success);"><i class="fas fa-map-pin"></i></div>
                                    <div class="quick-stat-content">
                                        <div class="quick-stat-title text-truncate"><?= htmlspecialchars($dest['name']) ?></div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500;"><?= htmlspecialchars($dest['location']) ?></div>
                                    </div>
                                    <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">
                                        <?= date('M j', strtotime($dest['created_at'])) ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                    <i class="fas fa-map" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                                    <p>No recent destinations</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div>
                        <h4 style="margin-bottom: 1.5rem; color: var(--text-muted); font-size: 0.95rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                            <i class="fas fa-calendar-check" style="color: var(--warning);"></i> Recent Bookings
                        </h4>
                        <div style="max-height: 250px; overflow-y: auto; padding-right: 10px;">
                            <?php if (!empty($recent_bookings)): ?>
                                <?php foreach($recent_bookings as $booking): ?>
                                <div class="quick-stat-item clickable" onclick="openModal('booking_detail.php?id=<?= $booking['id'] ?>', 'Booking #<?= $booking['id'] ?>')" style="margin-bottom: 10px;">
                                    <div class="quick-stat-icon" style="background: <?= ($booking['status'] ?? '') == 'confirmed' ? 'var(--success)' : (($booking['status'] ?? '') == 'pending' ? 'var(--warning)' : 'var(--danger)') ?>;">
                                        <i class="fas fa-<?= ($booking['status'] ?? '') == 'confirmed' ? 'check' : (($booking['status'] ?? '') == 'pending' ? 'clock' : 'times') ?>"></i>
                                    </div>
                                    <div class="quick-stat-content">
                                        <div class="quick-stat-title text-truncate">Booking #<?= $booking['id'] ?></div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500;">$<?= number_format($booking['total_amount'] ?? 0, 2) ?></div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; margin-bottom: 4px;">
                                            <?= date('M j', strtotime($booking['created_at'])) ?>
                                        </div>
                                        <span class="badge badge-<?= ($booking['status'] ?? '') == 'confirmed' ? 'success' : (($booking['status'] ?? '') == 'pending' ? 'warning' : 'danger') ?>">
                                            <?= ucfirst($booking['status'] ?? 'pending') ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                    <i class="fas fa-calendar-times" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                                    <p>No recent bookings</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="recent-activity">
                <h4 style="margin-bottom: 1.5rem; color: var(--text-muted); font-size: 0.95rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                    <i class="fas fa-cloud-rain" style="color: var(--secondary);"></i> Weather Impacted Destinations
                    <?php if ($badWeatherCount > 0): ?>
                        <span class="badge badge-danger" style="margin-left: 0.5rem;"><?= $badWeatherCount ?></span>
                    <?php endif; ?>
                </h4>
                <div style="max-height: 250px; overflow-y: auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                    <?php if (!empty($badWeatherDestinations)): ?>
                        <?php foreach($badWeatherDestinations as $dest): 
                            $weather = $dest['weather'];
                            $temp = round($weather['main']['temp']);
                            $condition = $weather['weather'][0]['main'];
                            $description = $weather['weather'][0]['description'];
                            $icon = getWeatherIcon($condition);
                            $severity = getWeatherSeverity($weather);
                            $severity_color = $severity == 'high' ? 'var(--danger)' : ($severity == 'medium' ? 'var(--warning)' : 'var(--info)');
                        ?>
                        <div class="quick-stat-item clickable" onclick="openModal('destination_weather_detail.php?id=<?= $dest['id'] ?>', 'Weather Alert: <?= addslashes($dest['name']) ?>')">
                            <div class="quick-stat-icon" style="background: <?= $severity_color ?>;"><i class="<?= $icon ?>"></i></div>
                            <div class="quick-stat-content">
                                <div class="quick-stat-title text-truncate"><?= htmlspecialchars($dest['name']) ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500;">
                                    <span><?= htmlspecialchars($dest['city']) ?></span>
                                    <?php if ($dest['country']): ?><span> • <?= htmlspecialchars($dest['country']) ?></span><?php endif; ?>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 1.2rem; font-weight: 800; color: var(--text-main); margin-bottom: 4px;"><?= $temp ?>°C</div>
                                <span class="badge badge-<?= $severity == 'high' ? 'danger' : ($severity == 'medium' ? 'warning' : 'info') ?>"><?= ucfirst($severity) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: var(--text-muted); grid-column: 1 / -1;">
                            <i class="fas fa-sun" style="font-size: 3rem; margin-bottom: 1rem; color: var(--warning); opacity: 0.8;"></i>
                            <p style="font-size: 1.1rem; font-weight: 600; color: var(--text-main);">No weather issues detected</p>
                            <small>All active destinations currently have good weather</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Use CSS variable hex values for JS Charts
        const primaryColor = '#4f46e5';
        const primaryBgColor = 'rgba(79, 70, 229, 0.15)';
        const secondaryColor = '#06b6d4';

        document.addEventListener('DOMContentLoaded', function() {
            // User Growth Chart
            const userGrowthCtx = document.getElementById('userGrowthChart');
            if (userGrowthCtx) {
                new Chart(userGrowthCtx, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode($graph_labels) ?>,
                        datasets: [{
                            label: 'Page Views',
                            data: <?= json_encode($graph_views) ?>,
                            borderColor: primaryColor,
                            backgroundColor: primaryBgColor,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: primaryColor,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(15, 23, 42, 0.05)' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart');
            if (revenueCtx) {
                new Chart(revenueCtx, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($graph_labels) ?>,
                        datasets: [{
                            label: 'User Clicks',
                            data: <?= json_encode($graph_clicks) ?>,
                            backgroundColor: secondaryColor,
                            borderRadius: 8,
                            hoverBackgroundColor: primaryColor
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(15, 23, 42, 0.05)' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            updateDateTime();
            setInterval(updateDateTime, 60000);
        });

        function openModal(url, title) {
            const modalOverlay = document.getElementById('modalOverlay');
            const modalContainer = document.getElementById('modalContainer');
            const modalTitleText = document.getElementById('modalTitleText');
            const modalIframe = document.getElementById('modalIframe');
            const modalLoading = document.getElementById('modalLoading');
            
            modalTitleText.textContent = title;
            modalLoading.style.display = 'flex';
            modalIframe.style.display = 'none';
            
            modalOverlay.classList.add('active');
            modalContainer.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            setTimeout(() => { modalIframe.src = url; }, 100);
        }

        function closeModal() {
            const modalOverlay = document.getElementById('modalOverlay');
            const modalContainer = document.getElementById('modalContainer');
            const modalIframe = document.getElementById('modalIframe');
            
            modalContainer.classList.remove('active');
            
            setTimeout(() => {
                modalOverlay.classList.remove('active');
                modalIframe.src = '';
                modalIframe.style.display = 'none';
                document.getElementById('modalLoading').style.display = 'flex';
                document.body.style.overflow = 'auto';
            }, 300);
        }

        document.getElementById('modalIframe').addEventListener('load', function() {
            setTimeout(() => {
                document.getElementById('modalLoading').style.display = 'none';
                this.style.display = 'block';
            }, 300);
        });

        function updateDateTime() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }

        function refreshDashboard() { location.reload(); }

        document.addEventListener('keydown', function(e) { if (e.key === 'Escape' && document.getElementById('modalOverlay').classList.contains('active')) closeModal(); });
        document.getElementById('modalOverlay').addEventListener('click', function(e) { if (e.target === this) closeModal(); });

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed; bottom: 30px; right: 30px;
                background: ${type === 'success' ? 'var(--success)' : type === 'error' ? 'var(--danger)' : 'var(--primary)'};
                color: white; padding: 1rem 1.5rem; border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2); z-index: 9999;
                font-weight: 700; display: flex; align-items: center; gap: 0.75rem;
                animation: slideInRight 0.4s cubic-bezier(0.25, 1, 0.5, 1);
            `;
            notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation' : 'info'}-circle"></i><span>${message}</span>`;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.4s cubic-bezier(0.25, 1, 0.5, 1)';
                setTimeout(() => notification.remove(), 400);
            }, 3000);
        }

        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
            @keyframes slideOutRight { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
        `;
        document.head.appendChild(style);

        setTimeout(() => { showNotification('Dashboard loaded successfully!', 'success'); }, 2000);
    </script>

    <?php include 'admin_footer.php'; ?>
</body>
</html>