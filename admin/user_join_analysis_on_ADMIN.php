<?php
session_start(); // Start session for authentication
if (!isset($_SESSION['admin_logged_in'])) { // Check if admin is logged in
    header('Location: login.php'); // Redirect to login page if not logged in
    exit(); // Stop further execution
}

include '../database/dbconfig.php'; // Include database configuration

// --- Breadcrumb handling (added, no changes to other logic) ---
$current_page = ['name' => 'Analysis', 'url' => basename($_SERVER['PHP_SELF'])];
$breadcrumb_prev = isset($_SESSION['admin_current_page']) ? $_SESSION['admin_current_page'] : ['name' => 'Dashboard', 'url' => 'admin_dasbord.php'];
// Update session current page
$_SESSION['admin_current_page'] = $current_page;
// -------------------------------------------------------------

// Get admin info from session
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];
$admin_email = $_SESSION['admin_email'];

// Get today's date
$today = date('Y-m-d');

// Prepare SQL to get today's user signup count
$today_sql = "SELECT 
                COUNT(*) AS total_count,
                DATE(created_at) AS day_date
              FROM users 
              WHERE DATE(created_at) = ?
              GROUP BY day_date";

$stmt = $conn->prepare($today_sql); // Prepare statement
$stmt->bind_param("s", $today); // Bind today's date
$stmt->execute(); // Execute statement
$today_result = $stmt->get_result(); // Get result
$today_data = $today_result->fetch_assoc(); // Fetch today's data

$today_growth = $today_data ? (int)$today_data['total_count'] : 0; // Today's signup count

// Prepare SQL to get last 30 days user signup data
$thirty_day_sql = "SELECT 
                    DATE(created_at) AS join_date, 
                    COUNT(*) AS daily_count
                   FROM users 
                   WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                   GROUP BY join_date 
                   ORDER BY join_date";

$result = $conn->query($thirty_day_sql); // Execute query

$dates = []; // Array for dates
$counts = []; // Array for daily signup counts
$total_users = 0; // Total users in 30 days

if ($result && $result->num_rows > 0) { // If query returns rows
    while ($row = $result->fetch_assoc()) { // Loop through results
        $dates[] = $row['join_date']; // Add date
        $counts[] = (int)$row['daily_count']; // Add count
    }
    $total_users = array_sum($counts); // Sum total users
}

// Ensure today's count in the chart matches the card
if (!empty($dates)) {
    $today_index = array_search($today, $dates); // Find today's index
    if ($today_index !== false) {
        $counts[$today_index] = $today_growth; // Set today's count
    }
}

// Calculate average per day, max users, and max date
$average_per_day = count($dates) > 0 ? round($total_users / count($dates), 2) : 0;
$max_users = !empty($counts) ? max($counts) : 0;
$max_date = !empty($counts) ? $dates[array_search($max_users, $counts)] : 'N/A';

// Calculate percentages for pie chart
$percentages = [];
if ($total_users > 0) {
    foreach ($counts as $count) {
        $percentages[] = round(($count / $total_users) * 100, 2);
    }
}

// Get admin info with profile picture from database
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
    <meta charset="UTF-8"> <!-- Set character encoding -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Responsive viewport -->
    <title>TripMate Admin</title> <!-- Page title -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> <!-- Font Awesome icons -->
    <style>
        :root {
            /* CSS variables for colors */
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
            --breadcrumb-bg: linear-gradient(90deg, rgba(22,3,79,0.08), rgba(26,82,118,0.03));
            --breadcrumb-accent: #16034f;
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
            border: 1px solid rgba(22,3,79,0.06);
            box-shadow: 0 6px 18px rgba(22,3,79,0.03);
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
            background: linear-gradient(90deg, rgba(255,87,34,0.06), rgba(255,152,0,0.02));
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid rgba(0,0,0,0.03);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7f9; /* Page background */
            color: #333; /* Default text color */
            line-height: 1.6;
            padding-top: 80px; /* Space for top bar */
        }
        
        /* Top Bar Styles */
        .top-bar {
            background: linear-gradient(to right, var(--primary), #1a5276); /* Gradient background */
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
        
        /* Responsive styles for mobile/tablet */
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
        
        @media (max-width: 768px) {
            .logo h1 {
                font-size: 24px;
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
        }
        
        /* Additional Styles for cards, charts, etc. */
        .section-title {
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
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
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border-left: 4px solid var(--accent);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-card h3 {
            margin: 0 0 0.5rem 0;
            color: var(--gray);
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .stat-card .value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
        }

        .today-growth {
            background: linear-gradient(135deg, var(--primary), #1a5276);
            color: white;
            border-left: 4px solid var(--secondary) !important;
        }

        .today-growth h3,
        .today-growth .value {
            color: white !important;
        } 

        .chart-container {
            width: 100%;
            margin: auto;
            height: 400px;
        }

        .chart-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .date-range-info {
            background: rgba(22, 3, 79, 0.05);
            padding: 0.8rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            display: inline-block;
            font-size: 0.9rem;
        }

        .no-data {
            text-align: center;
            padding: 2rem;
            color: var(--gray);
            font-size: 1.2rem;
        }

        @media (max-width: 1200px) {
            .chart-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="logo">
            <i class="fas fa-globe-americas"></i> <!-- Logo icon -->
            <h1>TripMate Admin</h1> <!-- Logo text -->
        </div>
        
        <div class="top-bar-actions">
           <div class="user-profile" id="userProfile">
                <img src="<?= $admin_profile_pic ? $admin_profile_pic : 'https://via.placeholder.com/40' ?>" alt="Admin"> <!-- Admin profile picture -->
                <span><?= htmlspecialchars($admin_name) ?></span> <!-- Admin name -->
                <i class="fas fa-chevron-down"></i> <!-- Dropdown icon -->
            </div>
        </div>
        
        <div class="dropdown" id="profileDropdown">
            <!-- Dropdown menu for logout -->
            <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <!-- Main Layout -->
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <ul class="menu">
                <!-- Sidebar navigation links -->
                <li class="menu-item "><a href="admin_dasbord.php" style="color:inherit;text-decoration:none"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li class="menu-item"><a href="add_destanition_on_admin.php" style="color:inherit;text-decoration:none"><i class="fas fa-map-marker-alt"></i> Destinations</a></li>
                <li class="menu-item"><a href="user_present_chack_on_admin.php" style="color:inherit;text-decoration:none"><i class="fas fa-users"></i> Users</a></li>
                <li class="menu-item active"><a href="user_join_analysis_on_ADMIN.php" style="color:inherit;text-decoration:none"><i class="fas fa-chart-line"></i> Analysis</a></li>
                <li class="menu-item"><a href="#" style="color:inherit;text-decoration:none"><i class="fas fa-envelope"></i> Messages</a></li>
                <li class="menu-item"><a href="#" style="color:inherit;text-decoration:none"><i class="fas fa-cog"></i> Settings</a></li>
                <li class="menu-item"><a href="user_ip_tracking_on_admin.php" style="color:inherit;text-decoration:none"><i class="fas fa-network-wired"></i> User IPs</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Breadcrumb (stylish, shows previous page) -->
            <div class="breadcrumb" aria-label="Breadcrumb">
                <a href="admin_dasbord.php"><i class="fas fa-home"></i> Dashboard</a>
                <?php if ($breadcrumb_prev && $breadcrumb_prev['name'] !== 'Dashboard' && $breadcrumb_prev['url'] !== $current_page['url']): ?>
                    <span class="sep">›</span>
                    <a href="<?= htmlspecialchars($breadcrumb_prev['url']) ?>"><i class="fas fa-arrow-left"></i> <?= htmlspecialchars($breadcrumb_prev['name']) ?></a>
                <?php endif; ?>
                <span class="sep">›</span>
                <span class="current"><i class="fas fa-chart-line"></i> <?= htmlspecialchars($current_page['name']) ?></span>
            </div>

            <h1 class="section-title"><i class="fas fa-user-chart"></i> User Growth Analytics</h1> <!-- Section title -->
            
            <div class="date-range-info">
                <i class="fas fa-calendar-alt"></i> Showing data for last 30 days <!-- Date range info -->
            </div>
            
            <!-- Stats Overview Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Users (30 days)</h3>
                    <p class="value"><?= number_format($total_users) ?></p>
                </div>
                <div class="stat-card">
                    <h3>Signup Days Tracked</h3>
                    <p class="value"><?= count($dates) ?></p>
                </div>
                <div class="stat-card">
                    <h3>Average Per Day</h3>
                    <p class="value"><?= $average_per_day ?></p>
                </div>
                <div class="stat-card today-growth">
                    <h3>Today's Growth</h3>
                    <p class="value"><?= $today_growth ?></p>
                </div> 
            </div>

            <?php if (empty($dates)): ?>
                <!-- Show message if no data available -->
                <div class="card no-data">
                    <i class="fas fa-chart-pie fa-3x" style="margin-bottom: 1rem;"></i>
                    <p>No user data available for the last 30 days</p>
                </div>
            <?php else: ?>
                <!-- Charts Section -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-chart-line"></i> User Growth Over Time (30 days)</h2>
                        <span class="status-badge" style="background:#c6f6d5;color:#2f855a;">Current Data</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="growthChart"></canvas> <!-- Line chart canvas -->
                    </div>
                </div>

                <div class="chart-grid">
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-chart-pie"></i> Signups Distribution</h2>
                            <span class="status-badge" style="background:#bee3f8;color:#2b6cb0;">Last 30 days</span>
                        </div>
                        <div class="chart-container">
                            <canvas id="pieChart"></canvas> <!-- Pie chart canvas -->
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-chart-bar"></i> Daily Signups</h2>
                            <span class="status-badge" style="background:#fed7d7;color:#c53030;">Details</span>
                        </div>
                        <div class="chart-container">
                            <canvas id="barChart"></canvas> <!-- Bar chart canvas -->
                        </div>
                    </div>
                </div>

                <!-- Chart.js library for charts -->
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                    // Data from PHP for charts
                    const dates = <?= json_encode($dates) ?>;
                    const counts = <?= json_encode($counts) ?>;
                    const percentages = <?= json_encode($percentages) ?>;
                    
                    // Generate consistent colors using HSL
                    const generateColors = (count, opacity = 1) => {
                        const colors = [];
                        const hueStep = 360 / count;
                        for (let i = 0; i < count; i++) {
                            const hue = i * hueStep;
                            colors.push(`hsla(${hue}, 70%, 60%, ${opacity})`);
                        }
                        return colors;
                    };

                    // Common tooltip formatter for charts
                    const tooltipFormatter = (context) => {
                        const label = context.label || '';
                        const value = context.parsed?.y || context.raw;
                        const percent = percentages[context.dataIndex];
                        return `${label}: ${value} users (${percent}%)`;
                    };

                    // Line Chart - Growth Over Time
                    const growthCtx = document.getElementById('growthChart').getContext('2d');
                    new Chart(growthCtx, {
                        type: 'line',
                        data: {
                            labels: dates,
                            datasets: [{
                                label: 'Daily New Users',
                                data: counts,
                                borderColor: '<?php echo '#3498db'; ?>',
                                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top'
                                },
                                tooltip: {
                                    callbacks: {
                                        label: tooltipFormatter
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Number of Users'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Date'
                                    }
                                }
                            }
                        }
                    });

                    // Pie Chart - Distribution
                    const pieCtx = document.getElementById('pieChart').getContext('2d');
                    new Chart(pieCtx, {
                        type: 'pie',
                        data: {
                            labels: dates.map((date, i) => `${date} (${percentages[i]}%)`),
                            datasets: [{
                                data: counts,
                                backgroundColor: generateColors(dates.length),
                                borderColor: '#fff',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                    labels: {
                                        boxWidth: 12,
                                        padding: 20,
                                        font: {
                                            size: 11
                                        }
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: tooltipFormatter
                                    }
                                }
                            }
                        }
                    });

                    // Bar Chart - Daily Signups
                    const barCtx = document.getElementById('barChart').getContext('2d');
                    new Chart(barCtx, {
                        type: 'bar',
                        data: {
                            labels: dates,
                            datasets: [{
                                label: 'Daily Signups',
                                data: counts,
                                backgroundColor: generateColors(dates.length, 0.7),
                                borderColor: generateColors(dates.length),
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: tooltipFormatter
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Number of Users'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Date'
                                    },
                                    grid: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });

                    // Debug console output for chart data
                    console.log('Chart Data Loaded:', {
                        dates: dates,
                        counts: counts,
                        percentages: percentages,
                        todayGrowth: <?= $today_growth ?>
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Dropdown toggle JS for user profile
        document.addEventListener('DOMContentLoaded', function() {
            const userProfile = document.getElementById('userProfile');
            const dropdown = document.getElementById('profileDropdown');

            userProfile.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.classList.toggle('active');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!userProfile.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.remove('active');
                }
            });

            // Close dropdown on Escape key
            document.addEventListener ('keydown', function(e) {
                if (e.key === 'Escape') {
                    dropdown.classList.remove('active');
                }
            });
        });
        
    </script>
</body>
</html>