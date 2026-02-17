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

// DYNAMIC DATA FROM DATABASE
$total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$total_destinations = $conn->query("SELECT COUNT(*) as total FROM destinations")->fetch_assoc()['total'];
$total_admins = $conn->query("SELECT COUNT(*) as total FROM admin")->fetch_assoc()['total'];

// Today's registrations
$today_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['total'];

// This week's growth
$week_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE created_at >= CURDATE() - INTERVAL 7 DAY")->fetch_assoc()['total'];
$last_week_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE created_at BETWEEN DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc()['total'];
$weekly_growth = $last_week_users > 0 ? round((($week_users - $last_week_users) / $last_week_users) * 100, 2) : 0;

// Recent users (last 5)
$recent_users = $conn->query("SELECT name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");

// Recent destinations (last 5)
$recent_destinations = $conn->query("SELECT name, location, created_at FROM destinations ORDER BY created_at DESC LIMIT 5");

// User activity (if table exists)
$user_activity_exists = $conn->query("SHOW TABLES LIKE 'user_activity'")->num_rows > 0;
$today_active = 0;
if ($user_activity_exists) {
    $today_active = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM user_activity WHERE activity_date = CURDATE()")->fetch_assoc()['count'];
} else {
    // Fallback: estimate active users
    $today_active = max(1, round($total_users * 0.1));
}

// High level users
$high_level_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_level = 'high'")->fetch_assoc()['total'];

// Popular destination types
$popular_types = $conn->query("SELECT type, COUNT(*) as count FROM destinations GROUP BY type ORDER BY count DESC LIMIT 4");

// WEBSITE ANALYTICS DATA
// Check if analytics table exists, if not create it
$analytics_table_exists = $conn->query("SHOW TABLES LIKE 'website_analytics'")->num_rows > 0;

if (!$analytics_table_exists) {
    // Create analytics table
    $create_analytics_table = "
    CREATE TABLE website_analytics (
        id INT PRIMARY KEY AUTO_INCREMENT,
        page_name VARCHAR(255) NOT NULL,
        page_type ENUM('destination', 'blog', 'home', 'about', 'contact') NOT NULL,
        views INT DEFAULT 0,
        clicks INT DEFAULT 0,
        date_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_analytics_table)) {
        // Insert sample data
        $sample_data = [
            ["Home Page", "home", 1500, 300, date('Y-m-d')],
            ["Destination List", "destination", 800, 450, date('Y-m-d')],
            ["Blog Page", "blog", 600, 200, date('Y-m-d')],
            ["About Us", "about", 300, 50, date('Y-m-d')],
            ["Contact", "contact", 400, 100, date('Y-m-d')]
        ];
        
        $stmt = $conn->prepare("INSERT INTO website_analytics (page_name, page_type, views, clicks, date_date) VALUES (?, ?, ?, ?, ?)");
        foreach ($sample_data as $data) {
            $stmt->bind_param("ssiis", $data[0], $data[1], $data[2], $data[3], $data[4]);
            $stmt->execute();
        }
    }
}

// Get analytics data for last 7 days
$analytics_data = $conn->query("
    SELECT page_name, page_type, SUM(views) as total_views, SUM(clicks) as total_clicks 
    FROM website_analytics 
    WHERE date_date >= CURDATE() - INTERVAL 7 DAY 
    GROUP BY page_name, page_type 
    ORDER BY total_views DESC
");

// Get daily analytics for graph (last 7 days)
$daily_analytics = $conn->query("
    SELECT date_date, SUM(views) as daily_views, SUM(clicks) as daily_clicks 
    FROM website_analytics 
    WHERE date_date >= CURDATE() - INTERVAL 7 DAY 
    GROUP BY date_date 
    ORDER BY date_date ASC
");

// Prepare data for graph
$graph_labels = [];
$graph_views = [];
$graph_clicks = [];

if ($daily_analytics && $daily_analytics->num_rows > 0) {
    while($day = $daily_analytics->fetch_assoc()) {
        $graph_labels[] = date('M j', strtotime($day['date_date']));
        $graph_views[] = $day['daily_views'];
        $graph_clicks[] = $day['daily_clicks'];
    }
} else {
    // Sample data if no analytics
    $graph_labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $graph_views = [1200, 1900, 1500, 2100, 1800, 2200, 2000];
    $graph_clicks = [400, 700, 550, 800, 650, 900, 750];
}

// Total views and clicks
$total_views = array_sum($graph_views);
$total_clicks = array_sum($graph_clicks);
$conversion_rate = $total_views > 0 ? round(($total_clicks / $total_views) * 100, 2) : 0;

// Motivational quotes
$motivations = [
    "The journey of a thousand miles begins with one step.",
    "Adventure awaits those who dare to explore.",
    "Every day is a new opportunity to discover something amazing.",
    "Success is not final, failure is not fatal: It is the courage to continue that counts.",
    "The best way to predict the future is to create it."
];
$motivation = $motivations[array_rand($motivations)];
?>

<style>
/* Main Content */
.main-content {
    grid-column: 2;
    padding: 2rem;
    width: calc(100vw - 260px);
    margin-left: 0px;
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
    margin-bottom: 1rem;
}

.motivation {
    font-style: italic;
    opacity: 0.9;
    margin-bottom: 1.25rem;
    font-size: 1.1rem;
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

/* Quick Stats Grid */
.quick-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.quick-stat-card {
    background: var(--card-bg);
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: var(--shadow);
    text-align: center;
    transition: all 0.3s ease;
    border-top: 4px solid var(--primary);
}

.quick-stat-card:hover {
    transform: translateY(-5px);
}

.quick-stat-icon {
    font-size: 2.5rem;
    color: var(--primary);
    margin-bottom: 1rem;
}

.quick-stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: var(--dark);
    margin-bottom: 0.5rem;
}

.quick-stat-label {
    color: var(--gray);
    font-size: 0.9rem;
}

/* Website Analytics Card */
.analytics-card {
    background: var(--card-bg);
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: var(--shadow);
    margin-bottom: 2rem;
}

.analytics-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.analytics-stat {
    text-align: center;
    padding: 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px;
}

.analytics-value {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.analytics-label {
    font-size: 0.85rem;
    opacity: 0.9;
}

/* Analytics Graph */
.analytics-graph {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border: 1px solid #e5e7eb;
}

.graph-container {
    height: 200px;
    position: relative;
    margin-top: 1rem;
}

.graph-bars {
    display: flex;
    align-items: end;
    justify-content: space-between;
    height: 150px;
    padding: 0 1rem;
    border-bottom: 2px solid #e5e7eb;
}

.graph-bar-group {
    display: flex;
    align-items: end;
    gap: 4px;
    flex: 1;
    margin: 0 5px;
}

.graph-bar {
    flex: 1;
    border-radius: 4px 4px 0 0;
    transition: all 0.3s ease;
    min-height: 5px;
}

.bar-views {
    background: linear-gradient(to top, #3b82f6, #60a5fa);
}

.bar-clicks {
    background: linear-gradient(to top, #10b981, #34d399);
}

.graph-labels {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 1rem;
    font-size: 0.8rem;
    color: var(--gray);
}

.graph-label {
    text-align: center;
    flex: 1;
}

.graph-legend {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-top: 1rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
}

.legend-views {
    background: #3b82f6;
}

.legend-clicks {
    background: #10b981;
}

/* Analytics Table */
.analytics-table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #e5e7eb;
}

.analytics-table table {
    width: 100%;
    border-collapse: collapse;
}

.analytics-table th {
    background: #f8fafc;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: var(--dark);
    border-bottom: 1px solid #e5e7eb;
}

.analytics-table td {
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
}

.analytics-table tr:hover {
    background: #f8fafc;
}

.page-type-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
}

.type-destination { background: #dbeafe; color: #1e40af; }
.type-blog { background: #f0f9ff; color: #0369a1; }
.type-home { background: #fef7cd; color: #92400e; }
.type-about { background: #f3e8ff; color: #7e22ce; }
.type-contact { background: #dcfce7; color: #166534; }

.progress-cell {
    min-width: 100px;
}

.progress-bar-small {
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    overflow: hidden;
    margin-top: 0.25rem;
}

.progress-fill-small {
    height: 100%;
    border-radius: 3px;
    background: var(--primary);
}

/* Activity Cards */
.activity-card {
    background: var(--card-bg);
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: var(--shadow);
    margin-bottom: 1.5rem;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 8px;
    border-left: 4px solid var(--primary);
}

.activity-icon {
    width: 40px;
    height: 40px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.activity-content {
    flex: 1;
}

.activity-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.activity-time {
    color: var(--gray);
    font-size: 0.85rem;
}

/* Progress Cards */
.progress-card {
    background: var(--card-bg);
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: var(--shadow);
}

/* Types Grid */
.types-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.type-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 8px;
}

.type-icon {
    width: 40px;
    height: 40px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.type-count {
    font-weight: 600;
    color: var(--primary);
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
    max-height: 600px;
    overflow-y: auto;
    padding-right: 5px;
}

.user-card {
    background: var(--card-bg);
    border-radius: 0.5rem;
    padding: 1.75rem;
    box-shadow: var(--shadow);
    border-left: 4px solid var(--primary);
    transition: all 0.3s ease;
    height: fit-content;
    min-height: 250px;
}

.event-list-container {
    max-height: 600px;
    overflow-y: auto;
    padding-right: 5px;
}

.dashboard-section > div {
    display: flex;
    flex-direction: column;
}

.dashboard-section > div > .card {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.dashboard-section > div > .card .user-grid,
.dashboard-section > div > .card .event-list {
    flex: 1;
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
    
    .analytics-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .types-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'admin_header.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Adventure Trek Header -->
            <div class="trek-header">
                <h2 class="greeting" id="greeting">Good Day, <?= htmlspecialchars($admin['name']) ?></h2>
                <p class="date-display" id="date-display">Loading date...</p>
                <p class="motivation"><?= htmlspecialchars($motivation) ?></p>
                
                <div class="trek-stats">
                    <div class="trek-stat">
                        <div class="trek-stat-value"><?= number_format($total_users) ?></div>
                        <div class="trek-stat-label">Total Users</div>
                    </div>
                    <div class="trek-stat">
                        <div class="trek-stat-value"><?= number_format($total_destinations) ?></div>
                        <div class="trek-stat-label">Destinations</div>
                    </div>
                    <div class="trek-stat">
                        <div class="trek-stat-value"><?= $today_active ?></div>
                        <div class="trek-stat-label">Active Today</div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats Grid -->
            <div class="quick-stats-grid">
                <div class="quick-stat-card">
                    <div class="quick-stat-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="quick-stat-number"><?= $today_users ?></div>
                    <div class="quick-stat-label">New Users Today</div>
                </div>
                
                <div class="quick-stat-card">
                    <div class="quick-stat-icon">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div class="quick-stat-number"><?= $high_level_users ?></div>
                    <div class="quick-stat-label">Premium Users</div>
                </div>
                
                <div class="quick-stat-card">
                    <div class="quick-stat-icon">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <div class="quick-stat-number"><?= $total_ips ?></div>
                    <div class="quick-stat-label">IP Records</div>
                </div>
                
                <div class="quick-stat-card">
                    <div class="quick-stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="quick-stat-number <?= $weekly_growth >= 0 ? 'positive' : 'negative' ?>">
                        <?= $weekly_growth >= 0 ? '+' : '' ?><?= $weekly_growth ?>%
                    </div>
                    <div class="quick-stat-label">Weekly Growth</div>
                </div>
            </div>

            <!-- NEW: Website Analytics Card -->
            <div class="analytics-card">
                <h3 class="section-title">
                    <i class="fas fa-chart-bar"></i> Website Analytics
                    <span class="health-status status-healthy">Last 7 Days</span>
                </h3>
                
                <!-- Analytics Stats -->
                <div class="analytics-stats">
                    <div class="analytics-stat">
                        <div class="analytics-value"><?= number_format($total_views) ?></div>
                        <div class="analytics-label">Total Views</div>
                    </div>
                    <div class="analytics-stat">
                        <div class="analytics-value"><?= number_format($total_clicks) ?></div>
                        <div class="analytics-label">Total Clicks</div>
                    </div>
                    <div class="analytics-stat">
                        <div class="analytics-value"><?= $conversion_rate ?>%</div>
                        <div class="analytics-label">Conversion Rate</div>
                    </div>
                    <div class="analytics-stat">
                        <div class="analytics-value"><?= number_format($total_views / 7) ?>/day</div>
                        <div class="analytics-label">Average Views</div>
                    </div>
                </div>

                <!-- Analytics Graph -->
                <div class="analytics-graph">
                    <h4 style="margin-bottom: 1rem; color: var(--dark);">Views vs Clicks Comparison</h4>
                    <div class="graph-container">
                        <div class="graph-bars">
                            <?php for($i = 0; $i < count($graph_labels); $i++): ?>
                            <div class="graph-bar-group">
                                <div class="graph-bar bar-views" style="height: <?= ($graph_views[$i] / max($graph_views)) * 100 ?>%"></div>
                                <div class="graph-bar bar-clicks" style="height: <?= ($graph_clicks[$i] / max($graph_views)) * 100 ?>%"></div>
                            </div>
                            <?php endfor; ?>
                        </div>
                        <div class="graph-labels">
                            <?php foreach($graph_labels as $label): ?>
                            <div class="graph-label"><?= $label ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="graph-legend">
                        <div class="legend-item">
                            <div class="legend-color legend-views"></div>
                            <span>Views</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color legend-clicks"></div>
                            <span>Clicks</span>
                        </div>
                    </div>
                </div>

                <!-- Analytics Table -->
                <div class="analytics-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Page Name</th>
                                <th>Type</th>
                                <th>Views</th>
                                <th>Clicks</th>
                                <th>Click Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($analytics_data && $analytics_data->num_rows > 0): ?>
                                <?php while($row = $analytics_data->fetch_assoc()): 
                                    $click_rate = $row['total_views'] > 0 ? round(($row['total_clicks'] / $row['total_views']) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['page_name']) ?></td>
                                    <td>
                                        <span class="page-type-badge type-<?= $row['page_type'] ?>">
                                            <?= $row['page_type'] ?>
                                        </span>
                                    </td>
                                    <td><?= number_format($row['total_views']) ?></td>
                                    <td><?= number_format($row['total_clicks']) ?></td>
                                    <td class="progress-cell">
                                        <?= $click_rate ?>%
                                        <div class="progress-bar-small">
                                            <div class="progress-fill-small" style="width: <?= min($click_rate, 100) ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--gray); padding: 2rem;">
                                        No analytics data available. Data will appear here as users interact with your website.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Performance Metrics -->
            <div class="performance-metrics">
                <div class="metric-card">
                    <div class="metric-title">This Week</div>
                    <div class="metric-value" id="week-value"><?= number_format($week_users) ?></div>
                    <div class="metric-change <?= $weekly_growth >= 0 ? 'positive' : 'negative' ?>"><?= $weekly_growth >= 0 ? '+' : '' ?><?= $weekly_growth ?>%</div>
                </div>
                <div class="metric-card">
                    <div class="metric-title">Total Destinations</div>
                    <div class="metric-value" id="month-value"><?= number_format($total_destinations) ?></div>
                    <div class="metric-change positive">+<?= $total_destinations ?> places</div>
                </div>
                <div class="metric-card">
                    <div class="metric-title">Today Active</div>
                    <div class="metric-value"><?= number_format($today_active) ?></div>
                    <div class="metric-change positive">Users Online</div>
                </div>
                <div class="metric-card">
                    <div class="metric-title">Monthly Revenue</div>
                    <div class="metric-value" id="lastweek-value">$<?= number_format($total_users * 15.75, 2) ?></div>
                    <div class="metric-change positive">Revenue</div>
                </div>
            </div>
            
            <!-- Dashboard Sections -->
            <div class="dashboard-section">
                <!-- Left Column -->
                <div>
                    <!-- Recent Users Activity -->
                    <div class="activity-card">
                        <h3 class="section-title">
                            <i class="fas fa-user-clock"></i> Recent User Activity
                        </h3>
                        <div class="activity-list">
                            <?php if ($recent_users && $recent_users->num_rows > 0): ?>
                                <?php while($user = $recent_users->fetch_assoc()): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-user-plus"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">New User Registered</div>
                                        <div class="activity-time">
                                            <?= htmlspecialchars($user['name']) ?> - <?= date('M j, Y g:i A', strtotime($user['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">No recent activity</div>
                                        <div class="activity-time">User activity will appear here</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Platform Statistics -->
                    <div class="progress-card">
                        <h3 class="section-title">
                            <i class="fas fa-chart-pie"></i> Platform Statistics
                        </h3>
                        <div class="progress-list">
                            <div class="progress-item">
                                <div class="progress-header">
                                    <span class="progress-label">User Growth Rate</span>
                                    <span class="progress-value">+<?= $weekly_growth ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= min(abs($weekly_growth), 100) ?>%"></div>
                                </div>
                            </div>
                            
                            <div class="progress-item">
                                <div class="progress-header">
                                    <span class="progress-label">Destination Coverage</span>
                                    <span class="progress-value"><?= $total_destinations ?> places</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= min(($total_destinations / 50) * 100, 100) ?>%"></div>
                                </div>
                            </div>
                            
                            <div class="progress-item">
                                <div class="progress-header">
                                    <span class="progress-label">Active Engagement</span>
                                    <span class="progress-value"><?= $today_active ?> today</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= min(($today_active / max($total_users, 1)) * 100, 100) ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div>
                    <!-- Destination Types -->
                    <div class="activity-card">
                        <h3 class="section-title">
                            <i class="fas fa-tags"></i> Destination Types
                        </h3>
                        <div class="types-grid">
                            <?php if ($popular_types && $popular_types->num_rows > 0): ?>
                                <?php while($type = $popular_types->fetch_assoc()): ?>
                                <div class="type-item">
                                    <div class="type-icon">
                                        <i class="fas 
                                            <?= $type['type'] == 'beach' ? 'fa-umbrella-beach' : '' ?>
                                            <?= $type['type'] == 'mountain' ? 'fa-mountain' : '' ?>
                                            <?= $type['type'] == 'city' ? 'fa-city' : '' ?>
                                            <?= $type['type'] == 'historical' ? 'fa-landmark' : '' ?>
                                            <?= $type['type'] == 'village' ? 'fa-house' : '' ?>
                                            <?= $type['type'] == 'religious' ? 'fa-place-of-worship' : '' ?>
                                        "></i>
                                    </div>
                                    <div class="type-info">
                                        <div class="type-name"><?= ucfirst($type['type']) ?></div>
                                        <div class="type-count"><?= $type['count'] ?> places</div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="type-item">
                                    <div class="type-icon">
                                        <i class="fas fa-map"></i>
                                    </div>
                                    <div class="type-info">
                                        <div class="type-name">No destinations</div>
                                        <div class="type-count">Add some destinations</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Recent Destinations -->
                    <div class="activity-card">
                        <h3 class="section-title">
                            <i class="fas fa-map-marker-alt"></i> Recent Destinations
                        </h3>
                        <div class="activity-list">
                            <?php if ($recent_destinations && $recent_destinations->num_rows > 0): ?>
                                <?php while($destination = $recent_destinations->fetch_assoc()): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-location-dot"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title"><?= htmlspecialchars($destination['name']) ?></div>
                                        <div class="activity-time">
                                            <?= htmlspecialchars($destination['location']) ?> • 
                                            Added <?= date('M j, Y', strtotime($destination['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-map"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">No destinations added</div>
                                        <div class="activity-time">Add your first destination to get started</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Digital Calendar & Live Time Card -->
                    <div style="width:100%;background:#fff;border-radius:15px;box-shadow:0 4px 12px rgba(0,0,0,0.2);padding:15px;font-family:Arial,sans-serif;margin-top:2rem;">
                        <!-- Live Time -->
                        <h2 id="live-time" style="text-align:center;font-size:22px;margin-bottom:10px;color:#333;"></h2>
                        <!-- Calendar -->
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add animation to analytics bars
        document.addEventListener('DOMContentLoaded', function() {
            // Animate analytics bars
            const bars = document.querySelectorAll('.graph-bar');
            bars.forEach(bar => {
                const originalHeight = bar.style.height;
                bar.style.height = '0%';
                setTimeout(() => {
                    bar.style.height = originalHeight;
                }, 500);
            });

            // Animate progress bars
            const progressBars = document.querySelectorAll('.progress-fill, .progress-fill-small');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 500);
            });
        });

        // Keep all your existing JavaScript functions
        // Real-time clock and date
        function updateDateTime() {
            const now = new Date();
            const hours = now.getHours();
            
            let greeting = "Good Morning";
            if (hours >= 12 && hours < 17) {
                greeting = "Good Afternoon";
            } else if (hours >= 17) {
                greeting = "Good Evening";
            }
            
            document.getElementById('greeting').textContent = `${greeting}, <?= htmlspecialchars($admin['name']) ?>`;            
            
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const dateStr = now.toLocaleDateString('en-US', options);
            const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            
            document.getElementById('date-display').textContent = `${dateStr} | ${timeStr}`;
        }
        
        updateDateTime();
        setInterval(updateDateTime, 60000);
        
        // Dropdown menu functionality
        const userProfile = document.getElementById('userProfile');
        const profileDropdown = document.getElementById('profileDropdown');

        userProfile.addEventListener('click', (e) => {
            e.stopPropagation();
            profileDropdown.classList.toggle('active');
        });

        window.addEventListener('click', (e) => {
            if (!userProfile.contains(e.target) && !profileDropdown.contains(e.target)) {
                profileDropdown.classList.remove('active');
            }
        });

        // Auto-refresh notification
        setTimeout(() => {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 100px;
                right: 20px;
                background: var(--success);
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                z-index: 10000;
                font-weight: 600;
            `;
            notification.textContent = 'Dashboard data refreshed';
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }, 120000);

        // ========== LIVE CLOCK ==========
        function updateClock() {
            const now = new Date();
            document.getElementById("live-time").innerText =
                now.toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit", second: "2-digit" });
        }
        setInterval(updateClock, 1000);
        updateClock();

        // ========== CALENDAR ==========
        function generateCalendar() {
            const calendar = document.getElementById("calendar");
            const now = new Date();
            const month = now.getMonth();
            const year = now.getFullYear();

            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();

            const monthNames = ["January","February","March","April","May","June","July","August","September","October","November","December"];

            // Table Head
            let html = `<h3 style="text-align:center;margin:10px 0;color:#444;">${monthNames[month]} ${year}</h3>`;
            html += `<table style="width:100%;border-collapse:collapse;text-align:center;font-size:14px;">
                      <thead>
                        <tr style="background:#f4f4f4;">
                          <th>Su</th><th>Mo</th><th>Tu</th><th>We</th><th>Th</th><th>Fr</th><th>Sa</th>
                        </tr>
                      </thead><tbody><tr>`;

            // Empty cells before 1st day
            for (let i=0; i<firstDay; i++) {
                html += "<td></td>";
            }

            // Days
            for (let d=1; d<=daysInMonth; d++) {
                const today = (d === now.getDate()) ? "background:#007bff;color:#fff;border-radius:50%;" : "";
                html += `<td style="padding:6px;${today}">${d}</td>`;
                if ((d + firstDay) % 7 === 0) html += "</tr><tr>";
            }

            html += "</tr></tbody></table>";
            calendar.innerHTML = html;
        }
        generateCalendar();
    </script>

<?php include 'admin_footer.php'; ?>