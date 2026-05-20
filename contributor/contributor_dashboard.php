<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['contributor_id'])) {
    header('Location: contributor_login.php');
    exit();
}

require_once '../database/dbconfig.php';

$contributor_id   = $_SESSION['contributor_id'];
$contributor_name = $_SESSION['contributor_name'];
$contributor_pic  = $_SESSION['contributor_profile_pic'] ?? null;

// Ensure we are checking for the exact column the admin updates
$cols = [];
$col_check = $conn->query("SHOW COLUMNS FROM destinations");
if ($col_check) {
    while ($c = $col_check->fetch_assoc()) $cols[] = $c['Field'];
}
$has_contributor = in_array('contributor_id', $cols);
$has_status      = in_array('submission_status', $cols); // Aligned with admin schema

$total = $approved = $pending = 0;
$total_earned = 0.00;
$latest_result = null;

if ($has_contributor) {
    $q = $conn->prepare("SELECT COUNT(*) AS cnt FROM destinations WHERE contributor_id = ?");
    $q->bind_param("i", $contributor_id);
    $q->execute();
    $total = $q->get_result()->fetch_assoc()['cnt'] ?? 0;
    $q->close();

    if ($has_status) {
        // Querying based on submission_status
        $q = $conn->prepare("SELECT COUNT(*) AS cnt FROM destinations WHERE contributor_id = ? AND submission_status = 'approved'");
        $q->bind_param("i", $contributor_id);
        $q->execute();
        $approved = $q->get_result()->fetch_assoc()['cnt'] ?? 0;
        $q->close();

        $q = $conn->prepare("SELECT COUNT(*) AS cnt FROM destinations WHERE contributor_id = ? AND submission_status = 'pending'");
        $q->bind_param("i", $contributor_id);
        $q->execute();
        $pending = $q->get_result()->fetch_assoc()['cnt'] ?? 0;
        $q->close();

        $total_earned = $approved * 15.00; 

        // Fetching submission_status
        $latest = $conn->prepare("SELECT name, type, location, submission_status as status, created_at FROM destinations WHERE contributor_id = ? ORDER BY id DESC LIMIT 5");
    } else {
        $latest = $conn->prepare("SELECT name, type, location, created_at FROM destinations WHERE contributor_id = ? ORDER BY id DESC LIMIT 5");
    }
    
    $latest->bind_param("i", $contributor_id);
    $latest->execute();
    $latest_result = $latest->get_result();
    $latest->close();
}

$first_name = explode(' ', $contributor_name)[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – TripMate Contributor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #f1f5f9; 
            --bg-surface: #ffffff; 
            --text-main: #0f172a; 
            --text-muted: #64748b;
            --primary: #4f46e5; 
            --primary-hover: #4338ca;
            --secondary: #0ea5e9; 
            --nav-bg: rgba(255, 255, 255, 0.85);
            --card-border: #e2e8f0; 
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 10px 25px -5px rgba(0,0,0,0.05);
            --shadow-lg: 0 20px 40px -10px rgba(79, 70, 229, 0.1);
            --danger: #ef4444; --danger-bg: #fef2f2;
            --success: #10b981; --success-bg: #ecfdf5;
            --warning: #f59e0b; --warning-bg: #fffbeb;
            --earnings: #8b5cf6; --earnings-bg: #f5f3ff;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body.dark-mode {
            --bg-base: #0f172a; 
            --bg-surface: #1e293b; 
            --text-main: #f8fafc; 
            --text-muted: #94a3b8;
            --primary: #6366f1; 
            --primary-hover: #818cf8;
            --secondary: #38bdf8; 
            --nav-bg: rgba(30, 41, 59, 0.85);
            --card-border: #334155; 
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.3);
            --shadow-md: 0 10px 25px -5px rgba(0,0,0,0.4);
            --shadow-lg: 0 20px 40px -10px rgba(0,0,0,0.5);
            --danger: #f87171; --danger-bg: rgba(239, 68, 68, 0.15);
            --success: #34d399; --success-bg: rgba(16, 185, 129, 0.15);
            --warning: #fbbf24; --warning-bg: rgba(245, 158, 11, 0.15);
            --earnings: #a78bfa; --earnings-bg: rgba(139, 92, 246, 0.15);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg-base); 
            color: var(--text-main); 
            min-height: 100vh; 
            padding-top: 130px; 
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        .navbar { 
            position: fixed; top: 20px; left: 50%; transform: translateX(-50%); 
            width: 90%; max-width: 1200px; height: 75px; 
            background: var(--nav-bg); 
            backdrop-filter: blur(16px); 
            border: 1px solid var(--card-border); 
            border-radius: 20px; 
            display: flex; justify-content: space-between; align-items: center; 
            padding: 0 30px; z-index: 1000; 
            box-shadow: var(--shadow-md); 
        }
        
        .logo { display: flex; align-items: center; gap: 12px; font-size: 1.5rem; font-weight: 800; text-decoration: none; color: var(--text-main); }
        .logo i { color: var(--primary); }
        .brand-text .trip { color: var(--text-main); } .brand-text .mate { color: var(--secondary); }
        
        .nav-badge { background: var(--earnings-bg); color: var(--primary); padding: 5px 14px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; border: 1px solid var(--card-border); margin-left: 15px; text-transform: uppercase; }
        
        .nav-links { display: flex; align-items: center; gap: 24px; list-style: none; margin-left: auto; margin-right: 24px; }
        .nav-link { color: var(--text-muted); text-decoration: none; font-weight: 600; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; transition: var(--transition); }
        .nav-link:hover { color: var(--primary); }
        .nav-link.danger:hover { color: var(--danger); }
        
        .user-profile { display: flex; align-items: center; gap: 12px; padding-left: 20px; border-left: 2px solid var(--card-border); }
        .nav-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--secondary)); display: flex; align-items: center; justify-content: center; font-weight: 700; color: #fff; }
        
        .theme-toggle { background: var(--bg-surface); border: 1px solid var(--card-border); color: var(--text-main); width: 42px; height: 42px; border-radius: 12px; cursor: pointer; display: flex; justify-content: center; align-items: center; transition: var(--transition); }
        .theme-toggle:hover { background: var(--primary); color: white; }

        .page { max-width: 1200px; margin: 0 auto; padding: 0 1.5rem 3rem; }

        .hero {
            background: linear-gradient(to right, var(--bg-surface), var(--bg-surface));
            border: 1px solid var(--card-border);
            border-radius: 24px; padding: 3rem; margin-bottom: 2.5rem;
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 2rem;
            box-shadow: var(--shadow-lg);
            position: relative; overflow: hidden;
        }
        .hero::before { content: ''; position: absolute; top: 0; left: 0; width: 6px; height: 100%; background: linear-gradient(to bottom, var(--primary), var(--secondary)); }
        
        .hero h1 { font-size: 2.2rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.5rem; }
        .hero p { color: var(--text-muted); font-size: 1.05rem; }
        
        .hero-btns { display: flex; gap: 1rem; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 0.9rem 1.8rem; border-radius: 14px; text-decoration: none; font-size: 0.95rem; font-weight: 600; transition: var(--transition); }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--primary-hover)); color: white; }
        .btn-outline { background: transparent; border: 2px solid var(--card-border); color: var(--text-main); }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); background: var(--bg-surface); }

        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 3rem; }
        .stat { background: var(--bg-surface); border: 1px solid var(--card-border); border-radius: 20px; padding: 1.8rem; display: flex; align-items: center; gap: 1.2rem; box-shadow: var(--shadow-md); transition: var(--transition); }
        .stat:hover { transform: translateY(-5px); border-color: var(--primary); box-shadow: var(--shadow-lg); }
        
        .stat-icon { width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .stat-icon.blue   { background: rgba(79, 70, 229, 0.1); color: var(--primary); }
        .stat-icon.green  { background: var(--success-bg); color: var(--success); }
        .stat-icon.amber  { background: var(--warning-bg); color: var(--warning); }
        .stat-icon.purple { background: var(--earnings-bg); color: var(--earnings); }
        
        .stat-num { font-size: 2.2rem; font-weight: 800; color: var(--text-main); margin-bottom: 6px; }
        .stat-label { font-size: 0.9rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; }

        .table-card { background: var(--bg-surface); border: 1px solid var(--card-border); border-radius: 24px; box-shadow: var(--shadow-md); overflow: hidden; }
        .table-header { padding: 1.8rem 2.2rem; border-bottom: 1px solid var(--card-border); display: flex; align-items: center; justify-content: space-between; }
        .table-header h2 { font-size: 1.2rem; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 12px; }
        
        .table-responsive { overflow-x: auto; width: 100%; }
        table { width: 100%; border-collapse: collapse; white-space: nowrap; }
        th { padding: 1.2rem 2.2rem; text-align: left; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; border-bottom: 2px solid var(--card-border); }
        td { padding: 1.2rem 2.2rem; font-size: 0.95rem; border-bottom: 1px solid var(--card-border); color: var(--text-main); }
        
        .badge-status { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 50px; font-size: 0.8rem; font-weight: 700; }
        .badge-pending  { background: var(--warning-bg); color: var(--warning); border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-approved { background: var(--success-bg); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-rejected { background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.3); }
        
        .type-pill { background: var(--bg-base); border: 1px solid var(--card-border); color: var(--text-main); padding: 5px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; }
    </style>
</head>
<body>

<nav class="navbar" role="navigation">
    <a href="../main/index.html" class="logo">
        <i class="fa-solid fa-paper-plane"></i>
        <span class="brand-text"><span class="trip">Trip</span><span class="mate">Mate</span></span>
    </a>
    <span class="nav-badge">Contributor</span>

    <ul class="nav-links">
        <a href="contributor_wallet.php" class="nav-link"><i class="fa-solid fa-wallet"></i> Wallet</a>
        <a href="contributor_profile.php" class="nav-link"><i class="fa-solid fa-user"></i> Profile</a>
        <a href="contributor_logout.php" class="nav-link danger"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </ul>
    
    <div style="display: flex; align-items: center; gap: 15px;">
        <div class="user-profile">
            <div class="nav-avatar">
                <?php if ($contributor_pic): ?>
                    <img src="<?= htmlspecialchars($contributor_pic) ?>" alt="Profile" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
                <?php else: ?>
                    <?= strtoupper(substr($contributor_name, 0, 1)) ?>
                <?php endif; ?>
            </div>
        </div>
        <button class="theme-toggle" id="themeToggle" aria-label="Switch mode">
            <i class="fas fa-moon"></i>
        </button>
    </div>
</nav>

<div class="page">
    <div class="hero">
        <div>
            <h1>Hey, <?= htmlspecialchars($first_name) ?>! 👋</h1>
            <p>Track your submissions, monitor your earnings, and expand the map with new destinations.</p>
        </div>
        <div class="hero-btns">
            <a href="contributor_add_destination.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Destination</a>
        </div>
    </div>

    <div class="stats">
        <div class="stat">
            <div class="stat-icon blue"><i class="fa-solid fa-map-location-dot"></i></div>
            <div>
                <div class="stat-num"><?= $total ?></div>
                <div class="stat-label">Total Submitted</div>
            </div>
        </div>
        <div class="stat">
            <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
            <div>
                <div class="stat-num"><?= $approved ?></div>
                <div class="stat-label">Approved</div>
            </div>
        </div>
        <div class="stat">
            <div class="stat-icon amber"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <div>
                <div class="stat-num"><?= $pending ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
        </div>
        <div class="stat">
            <div class="stat-icon purple"><i class="fa-solid fa-wallet"></i></div>
            <div>
                <div class="stat-num">$<?= number_format($total_earned, 2) ?></div>
                <div class="stat-label">Total Earned</div>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-header">
            <h2><i class="fa-solid fa-list-check"></i> Recent Submissions</h2>
        </div>

        <?php if ($latest_result && $latest_result->num_rows > 0): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Destination Name</th>
                        <th>Type</th>
                        <th>Location</th>
                        <?php if ($has_status): ?><th>Status</th><?php endif; ?>
                        <th>Date Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $latest_result->fetch_assoc()):
                        $s    = $row['status'] ?? 'pending';
                        $icon = $s === 'approved' ? 'check' : ($s === 'rejected' ? 'xmark' : 'clock-rotate-left');
                    ?>
                    <tr>
                        <td style="font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($row['name']) ?></td>
                        <td><span class="type-pill"><?= ucfirst(htmlspecialchars($row['type'])) ?></span></td>
                        <td style="color: var(--text-muted);"><i class="fa-solid fa-location-dot" style="color:var(--secondary);"></i> <?= htmlspecialchars($row['location']) ?></td>
                        <?php if ($has_status): ?>
                        <td>
                            <span class="badge-status badge-<?= $s ?>">
                                <i class="fa-solid fa-<?= $icon ?>"></i> <?= ucfirst($s) ?>
                            </span>
                        </td>
                        <?php endif; ?>
                        <td style="color:var(--text-muted);">
                            <i class="fa-regular fa-calendar"></i> <?= !empty($row['created_at']) ? date('M d, Y', strtotime($row['created_at'])) : '—' ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 4rem;">
            <p style="color: var(--text-muted);">You haven't submitted any destinations yet.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    const toggleBtn = document.getElementById('themeToggle');
    const icon = toggleBtn.querySelector('i');

    if (localStorage.getItem('tripmate-theme') === 'dark') {
        document.body.classList.add('dark-mode');
        icon.className = 'fas fa-sun';
    }

    toggleBtn.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
        localStorage.setItem('tripmate-theme', isDark ? 'dark' : 'light');
    });
</script>
</body>
</html>