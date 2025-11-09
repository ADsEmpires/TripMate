<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

include '../database/dbconfig.php';

// Get today's date
$today = date('Y-m-d');

// Prepare SQL to get today's user signup count
$today_sql = "SELECT 
                COUNT(*) AS total_count,
                DATE(created_at) AS day_date
              FROM users 
              WHERE DATE(created_at) = ?
              GROUP BY day_date";

$stmt = $conn->prepare($today_sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$today_result = $stmt->get_result();
$today_data = $today_result->fetch_assoc();

$today_growth = $today_data ? (int)$today_data['total_count'] : 0;

// Prepare SQL to get last 30 days user signup data
$thirty_day_sql = "SELECT 
                    DATE(created_at) AS join_date, 
                    COUNT(*) AS daily_count
                   FROM users 
                   WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                   GROUP BY join_date 
                   ORDER BY join_date";

$result = $conn->query($thirty_day_sql);

$dates = [];
$counts = [];
$total_users = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $dates[] = $row['join_date'];
        $counts[] = (int)$row['daily_count'];
    }
    $total_users = array_sum($counts);
}

// Ensure today's count in the chart matches the card
if (!empty($dates)) {
    $today_index = array_search($today, $dates);
    if ($today_index !== false) {
        $counts[$today_index] = $today_growth;
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

// NEW: Get weekly and monthly growth data
$weekly_sql = "SELECT 
                COUNT(*) as weekly_count 
               FROM users 
               WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
$weekly_result = $conn->query($weekly_sql);
$weekly_growth = $weekly_result ? $weekly_result->fetch_assoc()['weekly_count'] : 0;

$monthly_sql = "SELECT 
                COUNT(*) as monthly_count 
               FROM users 
               WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
$monthly_result = $conn->query($monthly_sql);
$monthly_growth = $monthly_result ? $monthly_result->fetch_assoc()['monthly_count'] : 0;

// NEW: Get growth rate compared to previous period
$prev_week_sql = "SELECT 
                    COUNT(*) as prev_weekly_count 
                  FROM users 
                  WHERE created_at BETWEEN DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
$prev_week_result = $conn->query($prev_week_sql);
$prev_weekly_growth = $prev_week_result ? $prev_week_result->fetch_assoc()['prev_weekly_count'] : 0;

$prev_month_sql = "SELECT 
                    COUNT(*) as prev_monthly_count 
                  FROM users 
                  WHERE created_at BETWEEN DATE_SUB(CURDATE(), INTERVAL 60 DAY) AND DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
$prev_month_result = $conn->query($prev_month_sql);
$prev_monthly_growth = $prev_month_result ? $prev_month_result->fetch_assoc()['prev_monthly_count'] : 0;

// NEW: Calculate growth percentages
$weekly_growth_rate = $prev_weekly_growth > 0 ? round((($weekly_growth - $prev_weekly_growth) / $prev_weekly_growth) * 100, 2) : 0;
$monthly_growth_rate = $prev_monthly_growth > 0 ? round((($monthly_growth - $prev_monthly_growth) / $prev_monthly_growth) * 100, 2) : 0;

// NEW: Get user demographics data
$demographics_sql = "SELECT 
                        COUNT(*) as total,
                        CASE 
                            WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 25 THEN '18-25'
                            WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 26 AND 35 THEN '26-35'
                            WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 36 AND 45 THEN '36-45'
                            WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) > 45 THEN '45+'
                            ELSE 'Unknown'
                        END as age_group
                     FROM users 
                     WHERE date_of_birth IS NOT NULL
                     GROUP BY age_group 
                     ORDER BY FIELD(age_group, '18-25', '26-35', '36-45', '45+', 'Unknown')";

$demographics_result = $conn->query($demographics_sql);
$age_groups = [];
$age_counts = [];

if ($demographics_result && $demographics_result->num_rows > 0) {
    while ($row = $demographics_result->fetch_assoc()) {
        $age_groups[] = $row['age_group'];
        $age_counts[] = (int)$row['total'];
    }
}

// NEW: Get user locations data
$locations_sql = "SELECT 
                    location,
                    COUNT(*) as user_count
                  FROM users 
                  WHERE location IS NOT NULL AND location != ''
                  GROUP BY location 
                  ORDER BY user_count DESC 
                  LIMIT 10";

$locations_result = $conn->query($locations_sql);
$locations = [];
$location_counts = [];

if ($locations_result && $locations_result->num_rows > 0) {
    while ($row = $locations_result->fetch_assoc()) {
        $locations[] = $row['location'];
        $location_counts[] = (int)$row['user_count'];
    }
}
?>

<?php include 'admin_header.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <h1 class="section-title"><i class="fas fa-user-chart"></i> User Growth Analytics</h1>
            
            <div class="date-range-info">
                <i class="fas fa-calendar-alt"></i> Showing data for last 30 days
            </div>
            
            <!-- Enhanced Stats Overview Cards -->
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
                
                <!-- NEW: Additional Stats Cards -->
                <div class="stat-card">
                    <h3>Weekly Growth</h3>
                    <p class="value"><?= number_format($weekly_growth) ?></p>
                    <small style="color: <?= $weekly_growth_rate >= 0 ? 'var(--success)' : 'var(--danger)' ?>;">
                        <i class="fas fa-arrow-<?= $weekly_growth_rate >= 0 ? 'up' : 'down' ?>"></i>
                        <?= abs($weekly_growth_rate) ?>%
                    </small>
                </div>
                <div class="stat-card">
                    <h3>Monthly Growth</h3>
                    <p class="value"><?= number_format($monthly_growth) ?></p>
                    <small style="color: <?= $monthly_growth_rate >= 0 ? 'var(--success)' : 'var(--danger)' ?>;">
                        <i class="fas fa-arrow-<?= $monthly_growth_rate >= 0 ? 'up' : 'down' ?>"></i>
                        <?= abs($monthly_growth_rate) ?>%
                    </small>
                </div>
                <div class="stat-card">
                    <h3>Peak Day</h3>
                    <p class="value"><?= number_format($max_users) ?></p>
                    <small><?= $max_date ?></small>
                </div>
                <div class="stat-card">
                    <h3>Active Days</h3>
                    <p class="value"><?= count(array_filter($counts)) ?></p>
                    <small>Days with signups</small>
                </div>
            </div>

            <?php if (empty($dates)): ?>
                <div class="card no-data">
                    <i class="fas fa-chart-pie fa-3x" style="margin-bottom: 1rem;"></i>
                    <p>No user data available for the last 30 days</p>
                </div>
            <?php else: ?>
                <!-- Main Charts Section -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-chart-line"></i> User Growth Over Time (30 days)</h2>
                        <span class="status-badge" style="background:#c6f6d5;color:#2f855a;">Current Data</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="growthChart"></canvas>
                    </div>
                </div>

                <div class="chart-grid">
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-chart-pie"></i> Signups Distribution</h2>
                            <span class="status-badge" style="background:#bee3f8;color:#2b6cb0;">Last 30 days</span>
                        </div>
                        <div class="chart-container">
                            <canvas id="pieChart"></canvas>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-chart-bar"></i> Daily Signups</h2>
                            <span class="status-badge" style="background:#fed7d7;color:#c53030;">Details</span>
                        </div>
                        <div class="chart-container">
                            <canvas id="barChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- NEW: Additional Analytics Sections -->
                <?php if (!empty($age_groups)): ?>
                <div class="chart-grid">
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-users"></i> User Age Demographics</h2>
                            <span class="status-badge" style="background:#e9d8fd;color:#6b46c1;">Age Groups</span>
                        </div>
                        <div class="chart-container">
                            <canvas id="ageChart"></canvas>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-map-marker-alt"></i> Top User Locations</h2>
                            <span class="status-badge" style="background:#fed7d7;color:#c53030;">Top 10</span>
                        </div>
                        <div class="chart-container">
                            <canvas id="locationChart"></canvas>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- NEW: Data Summary Table -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-table"></i> Growth Summary</h2>
                        <span class="status-badge" style="background:#c6f6d5;color:#2f855a;">Key Metrics</span>
                    </div>
                    <div style="padding: 1.5rem;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div style="text-align: center; padding: 1rem; background: #f7fafc; border-radius: 8px;">
                                <h3 style="color: var(--primary); margin: 0 0 0.5rem 0;">Best Day</h3>
                                <p style="font-size: 1.2rem; font-weight: bold; margin: 0;"><?= $max_date ?></p>
                                <small><?= $max_users ?> users</small>
                            </div>
                            <div style="text-align: center; padding: 1rem; background: #f7fafc; border-radius: 8px;">
                                <h3 style="color: var(--success); margin: 0 0 0.5rem 0;">Weekly Trend</h3>
                                <p style="font-size: 1.2rem; font-weight: bold; margin: 0;">
                                    <?= $weekly_growth_rate >= 0 ? '+' : '' ?><?= $weekly_growth_rate ?>%
                                </p>
                                <small>vs previous week</small>
                            </div>
                            <div style="text-align: center; padding: 1rem; background: #f7fafc; border-radius: 8px;">
                                <h3 style="color: var(--info); margin: 0 0 0.5rem 0;">Monthly Trend</h3>
                                <p style="font-size: 1.2rem; font-weight: bold; margin: 0;">
                                    <?= $monthly_growth_rate >= 0 ? '+' : '' ?><?= $monthly_growth_rate ?>%
                                </p>
                                <small>vs previous month</small>
                            </div>
                            <div style="text-align: center; padding: 1rem; background: #f7fafc; border-radius: 8px;">
                                <h3 style="color: var(--warning); margin: 0 0 0.5rem 0;">Active Rate</h3>
                                <p style="font-size: 1.2rem; font-weight: bold; margin: 0;">
                                    <?= count($dates) > 0 ? round((count(array_filter($counts)) / count($dates)) * 100, 1) : 0 ?>%
                                </p>
                                <small>days with signups</small>
                            </div>
                        </div>
                    </div>
                </div>

                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                    // Data from PHP for charts
                    const dates = <?= json_encode($dates) ?>;
                    const counts = <?= json_encode($counts) ?>;
                    const percentages = <?= json_encode($percentages) ?>;
                    const ageGroups = <?= json_encode($age_groups) ?>;
                    const ageCounts = <?= json_encode($age_counts) ?>;
                    const locations = <?= json_encode($locations) ?>;
                    const locationCounts = <?= json_encode($location_counts) ?>;
                    
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
                                borderColor: '#3498db',
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

                    // NEW: Age Demographics Chart
                    <?php if (!empty($age_groups)): ?>
                    const ageCtx = document.getElementById('ageChart').getContext('2d');
                    new Chart(ageCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ageGroups,
                            datasets: [{
                                data: ageCounts,
                                backgroundColor: ['#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6'],
                                borderColor: '#fff',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right'
                                }
                            }
                        }
                    });

                    // NEW: Location Chart
                    const locationCtx = document.getElementById('locationChart').getContext('2d');
                    new Chart(locationCtx, {
                        type: 'bar',
                        data: {
                            labels: locations,
                            datasets: [{
                                label: 'Users by Location',
                                data: locationCounts,
                                backgroundColor: 'rgba(52, 152, 219, 0.7)',
                                borderColor: '#3498db',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            indexAxis: 'y',
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Number of Users'
                                    }
                                }
                            }
                        }
                    });
                    <?php endif; ?>

                    // Debug console output for chart data
                    console.log('Chart Data Loaded:', {
                        dates: dates,
                        counts: counts,
                        percentages: percentages,
                        todayGrowth: <?= $today_growth ?>,
                        weeklyGrowth: <?= $weekly_growth ?>,
                        monthlyGrowth: <?= $monthly_growth ?>
                    });
                </script>
            <?php endif; ?>
        </div>

    <style>
        /* Additional Styles for enhanced design */
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

<?php include 'admin_footer.php'; ?>