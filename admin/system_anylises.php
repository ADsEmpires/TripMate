<?php
/* -------------------------------------------------------------
   system_anylises.php – Full Admin System Performance & Analytics
   ------------------------------------------------------------- */
require_once 'admin_header.php';
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Start timer
$start_time = microtime(true);

// ------------------------------------------------------------------
// 1. CPU Load – Windows & Linux compatible
// ------------------------------------------------------------------
$load = [0, 0, 0];
if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
    // Linux / macOS
    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
    }
} else {
    // Windows – use WMIC
    $wmic = shell_exec('wmic cpu get LoadPercentage /format:value 2>nul');
    if ($wmic && preg_match('/LoadPercentage=(\d+)/', $wmic, $m)) {
        $load = [$m[1] / 100, $m[1] / 100, $m[1] / 100]; // repeat current
    }
}

// ------------------------------------------------------------------
// 2. Disk Usage (project drive)
// ------------------------------------------------------------------
$disk_path = '/'; // Works on Windows via XAMPP drive mapping
$total_space = disk_total_space($disk_path) ?: 0;
$free_space  = disk_free_space($disk_path) ?: 0;
$used_space  = $total_space - $free_space;

$disk_percent_used = $total_space > 0 ? ($used_space / $total_space) * 100 : 0;
$total_space_gb    = $total_space / (1024**3);
$used_space_gb     = $used_space  / (1024**3);
$free_space_gb     = $free_space  / (1024**3);

// ------------------------------------------------------------------
// 3. PHP Memory
// ------------------------------------------------------------------
$php_mem_used_mb = memory_get_usage(true) / (1024 * 1024);

// ------------------------------------------------------------------
// 4. Database Size
// ------------------------------------------------------------------
$db_size_sql = "SELECT SUM(data_length + index_length) / (1024*1024) AS size_mb 
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE()";
$db_size_result = $conn->query($db_size_sql);
$db_size_mb = $db_size_result ? round($db_size_result->fetch_assoc()['size_mb'], 2) : 0;

// ------------------------------------------------------------------
// 5. Application Metrics
// ------------------------------------------------------------------
$total_users        = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_destinations = $conn->query("SELECT COUNT(*) FROM destinations")->fetch_row()[0];
$total_errors       = $conn->query("SELECT COUNT(*) FROM errors")->fetch_row()[0];
$total_events       = $conn->query("SELECT COUNT(*) FROM calendar_events")->fetch_row()[0];

$active_users_sql = "SELECT COUNT(DISTINCT user_id) FROM user_ips WHERE login_time > DATE_SUB(NOW(), INTERVAL 30 DAY)";
$active_users = $conn->query($active_users_sql)->fetch_row()[0];

// Recent errors
$recent_errors = [];
$res = $conn->query("SELECT * FROM errors ORDER BY created_at DESC LIMIT 5");
if ($res && $res->num_rows) {
    $recent_errors = $res->fetch_all(MYSQLI_ASSOC);
}

// Destinations by type
$dest_types = [];
$res = $conn->query("SELECT type, COUNT(*) AS c FROM destinations GROUP BY type");
if ($res) { $dest_types = $res->fetch_all(MYSQLI_ASSOC); }

// User levels
$user_levels = [];
$res = $conn->query("SELECT level, COUNT(*) AS c FROM user_levels GROUP BY level");
if ($res) { $user_levels = $res->fetch_all(MYSQLI_ASSOC); }

// Page load time
$execution_time_ms = round((microtime(true) - $start_time) * 1000, 2);
?>

<?php include 'admin_header.php'; ?>
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
            --card-bg: #ffffff;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f7f9; color: #333; padding-top: 80px; }

        /* Reusing your existing layout classes */
        .main-content { padding: 2rem; width: calc(100vw - 260px); margin-left: 1px; }

        .user-ip-card {
            background: var(--card-bg);
            border-radius: 0.75rem;
            padding: 1.75rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .card-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1.75rem; padding-bottom: 1.25rem; border-bottom: 1px solid #e5e7eb;
        }

        .card-header h2 {
            margin: 0; color: var(--primary); font-size: 1.5rem; font-weight: 600;
            display: flex; align-items: center; gap: 0.75rem;
        }

        .card-header h2 i { color: var(--secondary); }

        .status-badge {
            padding: 0.4rem 1rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 500;
            background: #dbeafe; color: var(--primary);
        }

        .user-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 1.75rem;
        }

        .user-card {
            background: var(--card-bg);
            border-radius: 0.5rem;
            padding: 1.75rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
        }

        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
        }

        .user-card-header h3 {
            margin: 0 0 1rem 0; color: var(--primary); font-size: 1.25rem; font-weight: 600;
        }

        .ip-list { margin-top: 1rem; border-top: 1px solid #e5e7eb; padding-top: 1rem; }

        .ip-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 0.75rem 0; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem;
        }

        .ip-item:last-child { border-bottom: none; }

        .ip-item .ip-time {
            color: var(--gray); font-weight: 500; font-family: 'JetBrains Mono', monospace;
        }

        .ip-address { font-family: 'JetBrains Mono', monospace; background: #f1f5f9; padding: 0.3rem 0.6rem; border-radius: 0.25rem; font-size: 0.875rem; }
        .user-agent { font-size: 0.75rem; color: var(--gray); margin-top: 0.3rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px; }

        .no-ips { color: var(--gray); font-style: italic; text-align: center; padding: 1.25rem; }

        .progress-bar {
            height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden; margin-top: 0.5rem;
        }

        .progress-fill {
            height: 100%; background: var(--primary); border-radius: 4px; transition: width 0.4s ease;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .main-content { width: 100%; margin-left: 0; padding: 1.5rem; }
            .user-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="main-content">

    <div class="user-ip-card">
        <div class="card-header">
            <h2><i class="fas fa-chart-line"></i> System Performance & Analytics</h2>
            <div>
                <span class="status-badge">Updated: <?= date('M d, Y H:i') ?></span>
            </div>
        </div>

        <div class="user-grid">

            <!-- Server Performance -->
            <div class="user-card">
                <div class="user-card-header"><h3>Server Performance</h3></div>
                <div class="ip-list">
                    <div class="ip-item">
                        <div>CPU Load (1/5/15 min)</div>
                        <div class="ip-time">
                            <?= number_format($load[0], 2) ?> /
                            <?= number_format($load[1], 2) ?> /
                            <?= number_format($load[2], 2) ?>
                        </div>
                    </div>
                    <div class="ip-item">
                        <div>PHP Memory Used</div>
                        <div class="ip-time"><?= number_format($php_mem_used_mb, 2) ?> MB</div>
                    </div>
                    <div class="ip-item">
                        <div>Disk Usage</div>
                        <div class="ip-time">
                            <?= number_format($used_space_gb, 2) ?> / <?= number_format($total_space_gb, 2) ?> GB
                            <div class="progress-bar"><div class="progress-fill" style="width: <?= $disk_percent_used ?>%"></div></div>
                        </div>
                    </div>
                    <div class="ip-item">
                        <div>Free Space</div>
                        <div class="ip-time"><?= number_format($free_space_gb, 2) ?> GB</div>
                    </div>
                    <div class="ip-item">
                        <div>Page Response Time</div>
                        <div class="ip-time"><?= $execution_time_ms ?> ms</div>
                    </div>
                </div>
            </div>

            <!-- Database Metrics -->
            <div class="user-card">
                <div class="user-card-header"><h3>Database Metrics</h3></div>
                <div class="ip-list">
                    <div class="ip-item"><div>DB Size</div><div class="ip-time"><?= $db_size_mb ?> MB</div></div>
                    <div class="ip-item"><div>Total Users</div><div class="ip-time"><?= $total_users ?></div></div>
                    <div class="ip-item"><div>Active (30d)</div><div class="ip-time"><?= $active_users ?></div></div>
                    <div class="ip-item"><div>Destinations</div><div class="ip-time"><?= $total_destinations ?></div></div>
                    <div class="ip-item"><div>Events</div><div class="ip-time"><?= $total_events ?></div></div>
                    <div class="ip-item"><div>Errors Logged</div><div class="ip-time"><?= $total_errors ?></div></div>
                </div>
            </div>

            <!-- Destinations by Type -->
            <div class="user-card">
                <div class="user-card-header"><h3>Destinations by Type</h3></div>
                <div class="ip-list">
                    <?php if ($dest_types): ?>
                        <?php foreach ($dest_types as $t): ?>
                            <div class="ip-item">
                                <div><?= ucfirst($t['type']) ?></div>
                                <div class="ip-time"><?= $t['c'] ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-ips">No data</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- User Levels -->
            <div class="user-card">
                <div class="user-card-header"><h3>User Levels</h3></div>
                <div class="ip-list">
                    <?php if ($user_levels): ?>
                        <?php foreach ($user_levels as $l): ?>
                            <div class="ip-item">
                                <div><?= ucfirst($l['level']) ?></div>
                                <div class="ip-time"><?= $l['c'] ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-ips">No data</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Errors -->
            <div class="user-card">
                <div class="user-card-header"><h3>Recent Errors (Last 5)</h3></div>
                <div class="ip-list">
                    <?php if ($recent_errors): ?>
                        <?php foreach ($recent_errors as $e): ?>
                            <div class="ip-item">
                                <div>
                                    <div class="ip-address"><?= htmlspecialchars(substr($e['message'], 0, 80)) ?><?= strlen($e['message']) > 80 ? '...' : '' ?></div>
                                    <div class="user-agent">
                                        IP: <?= htmlspecialchars($e['ip_address']) ?> |
                                        User: <?= $e['user_id'] ?? '—' ?>
                                    </div>
                                </div>
                                <div class="ip-time"><?= date('M d, H:i', strtotime($e['created_at'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-ips">No recent errors</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>