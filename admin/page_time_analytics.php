<?php
/* -------------------------------------------------------------
   PAGE TIME ANALYTICS – FINAL FIXED EDITION (2025)
   CSS Fixed • Proper Spacing • Score Removed • Table Below Chart
   ------------------------------------------------------------- */
session_start();

if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}
require_once '../database/dbconfig.php';

// === DATABASES & TABLES ===
$databases = [];
$db_query = $conn->query("SHOW DATABASES WHERE `Database` NOT IN ('information_schema','mysql','performance_schema','sys')");
if ($db_query) while ($row = $db_query->fetch_assoc()) $databases[] = $row['Database'];

$tables_found = [];
foreach ($databases as $db) {
    $sql = "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'page_time_tracking' LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('s', $db); $stmt->execute(); $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) $tables_found[] = $db;
        $stmt->close();
    }
}

// === FILTERS ===
$date_from   = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to     = $_GET['date_to']   ?? date('Y-m-d');
$page_filter = $_GET['page']      ?? '';
$db_filter   = $_GET['database']  ?? '';

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) $date_from = date('Y-m-d', strtotime('-30 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to))   $date_to   = date('Y-m-d');

// === DATA FETCH ===
$page_stats = $all_pages = $daily_data = $top_users = [];
$total_visits = $total_time = $total_clicks = 0;

if (!empty($tables_found)) {
    try {
        // Pages
        $pages_sql = '';
        foreach ($tables_found as $db) {
            $pages_sql .= ($pages_sql ? ' UNION ALL ' : '') . "SELECT DISTINCT page_name FROM `$db`.page_time_tracking";
        }
        if ($pages_sql && $res = $conn->query($pages_sql)) {
            while ($row = $res->fetch_assoc()) $all_pages[] = $row['page_name'];
        }

        // Stats
        $stats_sql = '';
        foreach ($tables_found as $db) {
            $stats_sql .= ($stats_sql ? ' UNION ALL ' : '') . "
                SELECT '$db' AS db_name, page_name,
                       COUNT(*) AS visits,
                       SUM(time_spent) AS total_time,
                       AVG(time_spent) AS avg_time,
                       SUM(click_count) AS clicks,
                       COUNT(DISTINCT user_id) AS unique_users
                FROM `$db`.page_time_tracking
                WHERE visit_date BETWEEN ? AND ?
                GROUP BY page_name
            ";
        }

        $where = $params = []; $types = '';
        if ($page_filter !== '') { $where[] = 'page_name = ?'; $params[] = $page_filter; $types .= 's'; }
        if ($db_filter !== '')   { $where[] = 'db_name = ?';    $params[] = $db_filter;   $types .= 's'; }

        $final_sql = "SELECT * FROM ($stats_sql) AS t";
        if ($where) $final_sql .= ' WHERE ' . implode(' AND ', $where);
        $final_sql .= " ORDER BY total_time DESC";

        $bind_params = []; $bind_types = str_repeat('ss', count($tables_found)) . $types;
        foreach ($tables_found as $db) { $bind_params[] = $date_from; $bind_params[] = $date_to; }
        $bind_params = array_merge($bind_params, $params);

        $stmt = $conn->prepare($final_sql);
        if ($stmt && $stmt->bind_param($bind_types, ...$bind_params)) {
            $stmt->execute(); $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $page_stats[] = $row;
                $total_visits += $row['visits'];
                $total_time   += $row['total_time'];
                $total_clicks += $row['clicks'];
            }
        }
        if ($stmt) $stmt->close();

        // Daily
        $daily_sql = '';
        foreach ($tables_found as $db) {
            $daily_sql .= ($daily_sql ? ' UNION ALL ' : '') . "
                SELECT visit_date, SUM(time_spent) AS t_time, COUNT(*) AS visits
                FROM `$db`.page_time_tracking
                WHERE visit_date BETWEEN ? AND ?
                GROUP BY visit_date
            ";
        }
        $daily_final = "SELECT visit_date, SUM(t_time) AS total_time, SUM(visits) AS visits
                        FROM ($daily_sql) AS d GROUP BY visit_date ORDER BY visit_date";

        $stmt = $conn->prepare($daily_final);
        $daily_params = []; $daily_types = str_repeat('ss', count($tables_found));
        foreach ($tables_found as $db) { $daily_params[] = $date_from; $daily_params[] = $date_to; }
        if ($stmt && $stmt->bind_param($daily_types, ...$daily_params)) {
            $stmt->execute(); $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) $daily_data[] = $row;
        }
        if ($stmt) $stmt->close();

        // Top Users
        $top_sql = '';
        foreach ($tables_found as $db) {
            $top_sql .= ($top_sql ? ' UNION ALL ' : '') . "
                SELECT u.username, u.email, COUNT(pt.id) AS visits, SUM(pt.time_spent) AS t_time
                FROM `$db`.page_time_tracking pt
                LEFT JOIN `$db`.users u ON pt.user_id = u.id
                WHERE pt.visit_date BETWEEN ? AND ? AND pt.user_id IS NOT NULL
                GROUP BY u.id
            ";
        }
        $top_final = "SELECT username, email, SUM(visits) AS visits, SUM(t_time) AS total_time
                      FROM ($top_sql) AS t GROUP BY username, email ORDER BY total_time DESC LIMIT 10";

        $stmt = $conn->prepare($top_final);
        $top_params = []; $top_types = str_repeat('ss', count($tables_found));
        foreach ($tables_found as $db) { $top_params[] = $date_from; $top_params[] = $date_to; }
        if ($stmt && $stmt->bind_param($top_types, ...$top_params)) {
            $stmt->execute(); $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) $top_users[] = $row;
        }
        if ($stmt) $stmt->close();

    } catch (Exception $e) {
        error_log("Analytics Error: " . $e->getMessage());
    }
}

include 'admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Time Analytics Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/luxon@3.0.1/build/global/luxon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon"></script>
</head>
<body class="light-mode">

<style>
    :root {
        --bg: #f5f7ff; --card: rgba(255,255,255,0.9); --text: #1a1a2e; --primary: #4361ee; --success: #28a745; --warning: #ffc107; --danger: #dc3545;
        --shadow: 0 8px 32px rgba(0,0,0,0.08); --border: rgba(255,255,255,0.2);
    }
    .dark-mode {
        --bg: #0f0f1a; --card: rgba(20,20,40,0.8); --text: #e0e0ff; --primary: #5d7aff; --shadow: 0 8px 32px rgba(0,0,0,0.3);
    }
    * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
    body { background: var(--bg); color: var(--text); transition: all 0.4s ease; }

    /* Fixed Layout – Proper Spacing */
    .analytics-wrapper {
        margin-left: 220px;
        width: calc(100% - 250px);
        min-height: 100vh;
        padding: 70px 20px 50px; /* Increased top padding */
        transition: all 0.3s;
    }
    .analytics-container { max-width: 1600px; margin: 0 auto; }

    /* Header */
    .analytics-header {
        background: linear-gradient(120deg, #4361ee, #7c3aed, #ec4899);
        background-size: 300%; color: white; padding: 30px 30px; border-radius: 20px;
        margin-bottom: 35px; position: relative; overflow: hidden; animation: gradient 8s ease infinite;
        backdrop-filter: blur(10px); box-shadow: var(--shadow);
    }
    @keyframes gradient { 0%,100%{background-position:0% 50%} 50%{background-position:100% 50%} }
    .analytics-header h1 { font-size: 36px; font-weight: 800; display: flex; align-items: center; gap: 15px; }
    .analytics-header p { font-size: 17px; opacity: 0.9; margin-top: 8px; }

    /* Dark Mode Toggle */
    .dark-toggle {
        position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2);
        width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: all 0.3s; backdrop-filter: blur(10px);
    }
    .dark-toggle:hover { background: rgba(255,255,255,0.3); transform: scale(1.1); }

    /* Filters */
    .filters-card {
        background: var(--card); backdrop-filter: blur(12px); border: 1px solid var(--border);
        padding: 10px; border-radius: 16px; margin-bottom: 35px; box-shadow: var(--shadow);
    }
    .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; align-items: end; }
    .filter-item label { font-weight: 600; font-size: 14px; color: var(--text); margin-bottom: 8px; display: block; }
    .filter-item input, .filter-item select {
        width: 100%; padding: 12px 16px; border: 1px solid rgba(0,0,0,0.1); border-radius: 10px;
        background: rgba(255,255,255,0.7); font-size: 14px; transition: all 0.3s;
    }
    .filter-item input:focus, .filter-item select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67,97,238,0.15); }
    .filter-btn {
        padding: 12px 24px; background: var(--primary); color: white; border: none; border-radius: 10px;
        font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s;
    }
    .filter-btn:hover { background: #3056c7; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(67,97,238,0.3); }

    /* Stats Cards */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 40px; }
    .stat-card {
        background: var(--card); backdrop-filter: blur(12px); border: 1px solid var(--border);
        padding: 28px 24px; border-radius: 16px; text-align: center; box-shadow: var(--shadow);
        transition: all 0.4s; position: relative; overflow: hidden;
    }
    .stat-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 5px; background: var(--primary); }
    .stat-card.visits::before { background: var(--primary); }
    .stat-card.time::before   { background: var(--success); }
    .stat-card.clicks::before { background: var(--warning); }
    .stat-card.dbs::before    { background: var(--danger); }
    .stat-card:hover { transform: translateY(-8px); box-shadow: 0 16px 40px rgba(0,0,0,0.15); }
    .stat-card h3 { font-size: 14px; color: #777; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; }
    .stat-value { font-size: 36px; font-weight: 800; color: var(--text); }
    .stat-unit { font-size: 14px; color: #888; margin-top: 6px; }

    /* Chart */
    .chart-container {
        background: var(--card); backdrop-filter: blur(12px); border: 1px solid var(--border);
        border-radius: 16px; overflow: hidden; box-shadow: var(--shadow); margin-bottom: 40px;
        padding: 28px;
    }
    .chart-title { font-size: 20px; font-weight: 700; color: var(--text); display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
    .chart-wrapper { position: relative; height: 360px; }

    /* Table */
    .table-container {
        background: var(--card); backdrop-filter: blur(12px); border: 1px solid var(--border);
        border-radius: 16px; overflow: hidden; box-shadow: var(--shadow); margin-bottom: 40px;
        position: relative; /* Added for scroll button positioning */
    }
    .table-header {
        padding: 20px 28px; background: rgba(67,97,238,0.05); display: flex; justify-content: space-between; align-items: center;
    }
    .table-title { font-size: 20px; font-weight: 700; color: var(--text); display: flex; align-items: center; gap: 10px; }
    .table-actions button {
        background: transparent; border: none; color: var(--text); font-size: 16px; cursor: pointer; padding: 8px; border-radius: 8px; transition: all 0.3s;
    }
    .table-actions button:hover { background: rgba(0,0,0,0.05); }

    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th { padding: 16px 20px; text-align: left; font-weight: 600; color: #555; background: #f8f9fa; cursor: pointer; }
    td { padding: 16px 20px; border-bottom: 1px solid rgba(0,0,0,0.05); }
    tr:hover { background: rgba(67,97,238,0.03); }
    .badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
    .badge-primary { background: var(--primary); color: white; }
    .badge-success { background: var(--success); color: white; }

    /* Scroll Buttons - Fixed */
    .scroll-nav {
        position: absolute; top: 15px; right: 15px; display: flex; gap: 8px;
        z-index: 10; /* Ensure buttons are above other content */
    }
    .scroll-btn {
        width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,0.7);
        display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 16px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: all 0.3s; backdrop-filter: blur(8px);
        border: 1px solid rgba(0,0,0,0.1); /* Added border for better visibility */
    }
    .scroll-btn:hover { background: white; transform: translateY(-3px); box-shadow: 0 6px 16px rgba(0,0,0,0.15); }

    /* No Data */
    .no-data { text-align: center; padding: 60px 20px; color: #6c757d; font-size: 16px; }
    .no-data i { font-size: 64px; opacity: 0.2; margin-bottom: 20px; display: block; }

    /* Responsive */
    @media (max-width: 1200px) {
        .analytics-wrapper { margin-left: 0; width: 100%; padding: 25px 15px 40px; }
    }
    @media (max-width: 768px) {
        .filter-grid { grid-template-columns: 1fr; }
        .stats-grid { grid-template-columns: 1fr; }
        .analytics-header h1 { font-size: 28px; }
        .chart-wrapper { height: 280px; }
        .analytics-wrapper { padding: 20px 10px 40px; }
        .scroll-nav { top: 10px; right: 10px; }
        .scroll-btn { width: 35px; height: 35px; font-size: 14px; }
    }
    body.sidebar-collapsed .analytics-wrapper { margin-left: 70px; width: calc(100% - 70px); }
</style>

<!-- HTML -->
<div class="analytics-wrapper">
    <div class="analytics-container">

        <!-- Header -->
        <div class="analytics-header">
            <h1>Page Time Analytics Pro</h1>
            <p>Real-time insights • Interactive • Dark Mode • Export Ready</p>
            <div class="dark-toggle" onclick="toggleDarkMode()">
                <i class="fas fa-moon" id="modeIcon"></i>
            </div>
        </div>

        <?php if (empty($tables_found)): ?>
            <div class="table-container">
                <div class="no-data">
                    <i class="fas fa-database"></i>
                    <h3>No Tracking Tables Found</h3>
                    <p>Enable tracking in your databases using <code>page_time_tracking.sql</code></p>
                </div>
            </div>
        <?php else: ?>

            <!-- Filters -->
            <div class="filters-card">
                    <div class="filter-item">
                        <label>Page</label>
                        <select name="page"><option value="">All Pages</option>
                            <?php foreach ($all_pages as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>" <?= $page_filter===$p?'selected':'' ?>><?= htmlspecialchars($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label>Database</label>
                        <select name="database"><option value="">All DBs</option>
                            <?php foreach ($tables_found as $db): ?>
                                <option value="<?= htmlspecialchars($db) ?>" <?= $db_filter===$db?'selected':'' ?>><?= htmlspecialchars($db) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-item">
                        <button type="submit" class="filter-btn">Apply</button>
                    </div>
                </form>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card visits">
                    <h3>Total Visits</h3>
                    <div class="stat-value" data-target="<?= $total_visits ?>">0</div>
                    <div class="stat-unit">page views</div>
                </div>
                <div class="stat-card time">
                    <h3>Total Time</h3>
                    <div class="stat-value" data-target="<?= $total_time ?>">0</div>
                    <div class="stat-unit">seconds</div>
                </div>
                <div class="stat-card clicks">
                    <h3>Total Clicks</h3>
                    <div class="stat-value" data-target="<?= $total_clicks ?>">0</div>
                    <div class="stat-unit">interactions</div>
                </div>
                <div class="stat-card dbs">
                    <h3>Active DBs</h3>
                    <div class="stat-value" data-target="<?= count($tables_found) ?>">0</div>
                    <div class="stat-unit">tracking enabled</div>
                </div>
            </div>

            <!-- Daily Trend Chart -->
            <?php if (!empty($daily_data)): ?>
            <div class="chart-container">
                <div class="chart-title">Daily Activity Trend</div>
                <div class="chart-wrapper"><canvas id="dailyChart"></canvas></div>
            </div>
            <?php else: ?>
            <div class="chart-container">
                <div class="no-data">
                    <p>No daily data available</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Page Performance Table (Moved Below Chart) -->
            <div class="table-container" id="pagePerformanceSection">
                <div class="table-header">
                    <div class="table-title">Page Performance</div>
                    <div class="table-actions">
                        <button onclick="exportCSV()">CSV</button>
                        <button onclick="window.print()">Print</button>
                    </div>
                </div>
                <div class="scroll-nav">
                    <div class="scroll-btn" onclick="scrollToSection('pagePerformanceSection', 'up')" title="Scroll Up">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="scroll-btn" onclick="scrollToSection('topUsersSection', 'down')" title="Scroll Down">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                </div>
                <?php if (!empty($page_stats)): ?>
                <div style="overflow-x:auto; padding:0 28px 28px;">
                    <table id="pageTable">
                        <thead>
                            <tr>
                                <th onclick="sortTable(0)">Page</th>
                                <th>DB</th>
                                <th onclick="sortTable(2)">Visits</th>
                                <th onclick="sortTable(3)">Avg Time</th>
                                <th onclick="sortTable(4)">Total Time</th>
                                <th onclick="sortTable(5)">Clicks</th>
                                <th onclick="sortTable(6)">Users</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($page_stats as $s): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($s['page_name']) ?></strong></td>
                                <td><span class="badge badge-primary"><?= htmlspecialchars($s['db_name']) ?></span></td>
                                <td><?= number_format($s['visits']) ?></td>
                                <td><?= number_format($s['avg_time'], 2) ?>s</td>
                                <td><?= number_format($s['total_time']) ?>s</td>
                                <td><?= number_format($s['clicks']) ?></td>
                                <td><?= number_format($s['unique_users']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="no-data">No page data</div>
                <?php endif; ?>
            </div>

            <!-- Top Users -->
            <div class="table-container" id="topUsersSection">
                <div class="table-header">
                    <div class="table-title">Top 10 Users</div>
                </div>
                <div class="scroll-nav">
                    <div class="scroll-btn" onclick="scrollToSection('pagePerformanceSection', 'up')" title="Scroll Up">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="scroll-btn" onclick="scrollToTop()" title="Scroll to Top">
                        <i class="fas fa-home"></i>
                    </div>
                </div>
                <?php if (!empty($top_users)): ?>
                <div style="overflow-x:auto; padding:0 28px 28px;">
                    <table>
                        <thead><tr><th>Rank</th><th>User</th><th>Email</th><th>Visits</th><th>Time</th></tr></thead>
                        <tbody>
                            <?php foreach ($top_users as $i => $u): ?>
                            <tr>
                                <td><span class="badge badge-success">#<?= $i+1 ?></span></td>
                                <td><strong><?= htmlspecialchars($u['username'] ?? 'N/A') ?></strong></td>
                                <td><?= htmlspecialchars($u['email'] ?? 'N/A') ?></td>
                                <td><?= number_format($u['visits']) ?></td>
                                <td><?= number_format($u['total_time']) ?>s</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="no-data">No user data</div>
                <?php endif; ?>
            </div>

        <?php endif; ?>
    </div>
</div>

<script>
// Dark Mode
function toggleDarkMode() {
    const body = document.body;
    const icon = document.getElementById('modeIcon');
    body.classList.toggle('dark-mode');
    icon.classList.toggle('fa-moon');
    icon.classList.toggle('fa-sun');
    localStorage.setItem('darkMode', body.classList.contains('dark-mode'));
}
if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
    document.getElementById('modeIcon').classList.replace('fa-moon', 'fa-sun');
}

// Counter Animation
document.querySelectorAll('.stat-value').forEach(el => {
    const target = +el.getAttribute('data-target');
    const increment = target / 100;
    let current = 0;
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            el.textContent = target.toLocaleString();
            clearInterval(timer);
        } else {
            el.textContent = Math.floor(current).toLocaleString();
        }
    }, 20);
});

// Improved Scroll Function
function scrollToSection(id, direction) {
    const el = document.getElementById(id);
    if (el) {
        const offset = 100;
        let pos;
        
        if (direction === 'up') {
            // Scroll up to the previous section
            const currentSection = document.querySelector('.table-container:target, .table-container.active') || el;
            const prevSection = currentSection.previousElementSibling;
            if (prevSection && prevSection.classList.contains('table-container')) {
                pos = prevSection.getBoundingClientRect().top + window.pageYOffset - offset;
            } else {
                pos = 0; // Scroll to top if no previous section
            }
        } else {
            // Scroll down to the next section
            pos = el.getBoundingClientRect().top + window.pageYOffset - offset;
        }
        
        window.scrollTo({ top: pos, behavior: 'smooth' });
    }
}

// Scroll to top function
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Table Sort
function sortTable(n) {
    const table = document.getElementById("pageTable");
    let switching = true, dir = "asc", switchcount = 0;
    while (switching) {
        switching = false; const rows = table.rows;
        for (let i = 1; i < (rows.length - 1); i++) {
            let shouldSwitch = false;
            const x = rows[i].getElementsByTagName("TD")[n];
            const y = rows[i + 1].getElementsByTagName("TD")[n];
            const a = isNaN(x.innerHTML) ? x.innerHTML.toLowerCase() : +x.innerHTML;
            const b = isNaN(y.innerHTML) ? y.innerHTML.toLowerCase() : +y.innerHTML;
            if (dir == "asc") { if (a > b) { shouldSwitch = true; break; } }
            else if (dir == "desc") { if (a < b) { shouldSwitch = true; break; } }
        }
        if (shouldSwitch) {
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]); switching = true; switchcount++;
        } else if (switchcount == 0 && dir == "asc") { dir = "desc"; switching = true; }
    }
}

// CSV Export
function exportCSV() {
    const rows = [['Page','DB','Visits','Avg Time','Total Time','Clicks','Users']];
    <?php foreach ($page_stats as $s): ?>
    rows.push(["<?= addslashes($s['page_name']) ?>","<?= addslashes($s['db_name']) ?>","<?= $s['visits'] ?>","<?= number_format($s['avg_time'],2) ?>","<?= $s['total_time'] ?>","<?= $s['clicks'] ?>","<?= $s['unique_users'] ?>"]);
    <?php endforeach; ?>
    const csv = rows.map(r => r.join(',')).join('\n');
    const blob = new Blob([csv], {type: 'text/csv'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a'); a.href = url; a.download = 'analytics_<?= date('Y-m-d') ?>.csv'; a.click();
}

// Chart
<?php if (!empty($daily_data)): ?>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('dailyChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($daily_data, 'visit_date')) ?>,
            datasets: [
                { label: 'Time (s)', data: <?= json_encode(array_column($daily_data, 'total_time')) ?>, borderColor: '#4361ee', backgroundColor: 'rgba(67,97,238,0.2)', fill: true, tension: 0.4, borderWidth: 3, pointRadius: 4 },
                { label: 'Visits', data: <?= json_encode(array_column($daily_data, 'visits')) ?>, borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,0.2)', fill: true, tension: 0.4, borderWidth: 3, pointRadius: 4 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: { x: { type: 'time', time: { unit: 'day' } } },
            animation: { duration: 2000 }
        }
    });
});
<?php endif; ?>
</script>

<?php include 'admin_footer.php'; ?>
</body>
</html>