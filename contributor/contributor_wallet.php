<?php
session_start();

if (!isset($_SESSION['contributor_id'])) {
    header('Location: contributor_login.php');
    exit();
}

include '../database/dbconfig.php';

$contributor_id   = $_SESSION['contributor_id'];
$contributor_name = $_SESSION['contributor_name'];
$contributor_pic  = $_SESSION['contributor_profile_pic'] ?? null;

// Mock Earnings Calculation (Matches the logic added to your dashboard)
$approved_count = 0;
$q = $conn->prepare("SELECT COUNT(*) AS cnt FROM destinations WHERE contributor_id = ? AND status = 'approved'");
if ($q) {
    $q->bind_param("i", $contributor_id);
    $q->execute();
    $approved_count = $q->get_result()->fetch_assoc()['cnt'] ?? 0;
    $q->close();
}

$balance = $approved_count * 15.00; // $15 per approved destination
$pending_clearance = 0.00; // Mock value
$total_withdrawn = 0.00; // Mock value

// Fetch latest approved destinations as "Transaction History"
$transactions = [];
$t_query = $conn->prepare("SELECT name, created_at FROM destinations WHERE contributor_id = ? AND status = 'approved' ORDER BY id DESC LIMIT 10");
if ($t_query) {
    $t_query->bind_param("i", $contributor_id);
    $t_query->execute();
    $res = $t_query->get_result();
    while ($row = $res->fetch_assoc()) {
        $transactions[] = $row;
    }
    $t_query->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet – TripMate Contributor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        /* TripMate Global Design System */
        :root {
            --bg-base: #f8fafc; --bg-surface: #ffffff; --text-main: #0f172a; --text-muted: #475569;
            --primary: #4f46e5; --secondary: #06b6d4; --nav-bg: rgba(255, 255, 255, 0.75);
            --card-border: rgba(79, 70, 229, 0.15); --shadow-color: rgba(15, 23, 42, 0.08); --glow-color: rgba(6, 182, 212, 0.4);
            --success: #10b981; --success-bg: rgba(16, 185, 129, 0.1);
            --earnings: #8b5cf6; --earnings-bg: rgba(139, 92, 246, 0.1); 
        }
        body.dark-mode {
            --bg-base: #09090b; --bg-surface: #18181b; --text-main: #f8fafc; --text-muted: #cbd5e1;
            --primary: #818cf8; --secondary: #22d3ee; --nav-bg: rgba(24, 24, 27, 0.75);
            --card-border: rgba(255, 255, 255, 0.1); --shadow-color: rgba(0, 0, 0, 0.6); --glow-color: rgba(34, 211, 238, 0.3);
            --success: #34d399; --success-bg: rgba(52, 211, 153, 0.1);
            --earnings: #a78bfa; --earnings-bg: rgba(167, 139, 250, 0.15);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); min-height: 100vh; position: relative; overflow-x: hidden; transition: background-color 0.4s ease, color 0.4s ease; padding-top: 120px; }
        body::before { content: ''; position: fixed; top: -10%; left: -10%; width: 50vw; height: 50vw; background: radial-gradient(circle, rgba(139, 92, 246, 0.2) 0%, transparent 60%); opacity: 0.5; pointer-events: none; }
        
        /* Navbar */
        .navbar { position: fixed; top: 20px; left: 5%; width: 90%; height: 70px; background: var(--nav-bg); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid var(--card-border); border-radius: 50px; display: flex; justify-content: space-between; align-items: center; padding: 0 30px; z-index: 1000; box-shadow: 0 10px 30px var(--shadow-color); }
        .logo { display: flex; align-items: center; gap: 12px; font-size: 1.4rem; font-weight: 800; text-decoration: none; color: var(--text-main); }
        .logo i { color: var(--primary); font-size: 1.5rem; transform: rotate(-10deg); transition: transform 0.3s; }
        .logo:hover i { transform: rotate(0deg) scale(1.1); }
        .brand-text .trip { color: var(--text-main); } .brand-text .mate { color: var(--secondary); }
        .nav-links { display: flex; align-items: center; gap: 18px; list-style: none; margin-left: auto; margin-right: 20px; }
        .nav-link { color: var(--text-muted); text-decoration: none; font-weight: 600; transition: color 0.2s; display: flex; align-items: center; gap: 6px; font-size: 0.95rem; }
        .nav-link:hover { color: var(--secondary); }
        .nav-link.active { color: var(--earnings); }
        .nav-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--secondary)); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; color: #fff; overflow: hidden; border: 2px solid var(--bg-surface); }
        .nav-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .theme-toggle { background: var(--bg-surface); border: 1px solid var(--card-border); color: var(--text-main); width: 44px; height: 44px; border-radius: 50%; cursor: pointer; display: flex; justify-content: center; align-items: center; transition: all 0.3s; box-shadow: 0 4px 10px var(--shadow-color); flex-shrink: 0; }
        .theme-toggle:hover { transform: rotate(20deg) scale(1.1); color: var(--primary); border-color: var(--primary); }

        /* Page Content */
        .page { max-width: 1000px; margin: 0 auto; padding: 0 1.5rem 2rem; position: relative; z-index: 1; }

        /* Wallet Hero */
        .wallet-hero { display: grid; grid-template-columns: 1.5fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
        @media (max-width: 768px) { .wallet-hero { grid-template-columns: 1fr; } }

        .balance-card { background: linear-gradient(135deg, #6d28d9, #9333ea); border-radius: 24px; padding: 2.5rem; color: #fff; position: relative; overflow: hidden; box-shadow: 0 15px 35px rgba(109, 40, 217, 0.3); }
        .balance-card::after { content: '\f555'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; right: -20px; bottom: -30px; font-size: 10rem; opacity: 0.1; transform: rotate(-15deg); pointer-events: none; }
        .balance-label { font-size: 1rem; font-weight: 600; opacity: 0.9; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px; }
        .balance-amount { font-size: 3.5rem; font-weight: 800; letter-spacing: -1px; margin-bottom: 1.5rem; line-height: 1; }
        .withdraw-btn { background: #fff; color: #6d28d9; border: none; padding: 0.8rem 1.5rem; border-radius: 12px; font-size: 0.95rem; font-weight: 700; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .withdraw-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.2); }

        .stats-grid { display: grid; grid-template-rows: 1fr 1fr; gap: 1rem; }
        .stat-box { background: var(--bg-surface); border: 1px solid var(--card-border); border-radius: 20px; padding: 1.5rem; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 10px 20px var(--shadow-color); }
        .stat-info h3 { font-size: 0.85rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
        .stat-info p { font-size: 1.5rem; font-weight: 800; color: var(--text-main); }
        .stat-icon { width: 45px; height: 45px; background: var(--earnings-bg); color: var(--earnings); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .stat-icon.green { background: var(--success-bg); color: var(--success); }

        /* Transactions Table */
        .table-card { background: var(--bg-surface); border: 1px solid var(--card-border); border-radius: 24px; overflow: hidden; box-shadow: 0 15px 35px var(--shadow-color); }
        .table-header { padding: 1.5rem 2rem; border-bottom: 1px solid var(--card-border); }
        .table-header h2 { font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 10px; color: var(--text-main); }
        .table-header h2 i { color: var(--earnings); }
        
        table { width: 100%; border-collapse: collapse; }
        th { padding: 1rem 2rem; text-align: left; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; background: var(--bg-base); }
        td { padding: 1.2rem 2rem; font-size: 0.9rem; border-top: 1px solid var(--card-border); color: var(--text-main); }
        tr:hover td { background: var(--bg-base); }
        
        .badge-success { background: var(--success-bg); color: var(--success); padding: 4px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; }
        .amount-text { font-weight: 800; color: var(--success); }

        .empty { text-align: center; padding: 4rem 2rem; color: var(--text-muted); }
        .empty i { font-size: 3rem; color: var(--card-border); display: block; margin-bottom: 1rem; }
    </style>
</head>
<body>

<nav class="navbar" role="navigation">
    <a href="../main/index.html" class="logo">
        <i class="fa-solid fa-paper-plane"></i>
        <span class="brand-text"><span class="trip">Trip</span><span class="mate">Mate</span></span>
    </a>
    <ul class="nav-links">
        <a href="contributor_dashboard.php" class="nav-link"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="contributor_wallet.php" class="nav-link active"><i class="fa-solid fa-wallet"></i> Wallet</a>
        <a href="contributor_profile.php" class="nav-link"><i class="fa-solid fa-user"></i> Profile</a>
        
        <div class="nav-avatar" style="margin-left: 10px;">
            <?php if ($contributor_pic): ?>
                <img src="<?= htmlspecialchars($contributor_pic) ?>" alt="">
            <?php else: ?>
                <?= strtoupper(substr($contributor_name, 0, 1)) ?>
            <?php endif; ?>
        </div>
    </ul>
    <button class="theme-toggle" id="themeToggle"><i class="fas fa-moon"></i></button>
</nav>

<div class="page">
    <div class="wallet-hero">
        <div class="balance-card">
            <div class="balance-label"><i class="fa-solid fa-vault"></i> Available Balance</div>
            <div class="balance-amount">$<?= number_format($balance, 2) ?></div>
            <button class="withdraw-btn" onclick="alert('Withdrawal system coming soon!')">
                <i class="fa-solid fa-building-columns"></i> Withdraw Funds
            </button>
        </div>
        
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-info">
                    <h3>Pending Clearance</h3>
                    <p>$<?= number_format($pending_clearance, 2) ?></p>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
            </div>
            <div class="stat-box">
                <div class="stat-info">
                    <h3>Total Withdrawn</h3>
                    <p>$<?= number_format($total_withdrawn, 2) ?></p>
                </div>
                <div class="stat-icon green"><i class="fa-solid fa-money-bill-trend-up"></i></div>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-header">
            <h2><i class="fa-solid fa-money-check-dollar"></i> Earning History</h2>
        </div>

        <?php if (!empty($transactions)): ?>
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $t): ?>
                <tr>
                    <td style="font-weight:600">Reward: <?= htmlspecialchars($t['name']) ?></td>
                    <td style="color:var(--text-muted); font-size:0.85rem; font-weight: 500;">
                        <i class="fa-regular fa-calendar"></i> <?= date('d M Y', strtotime($t['created_at'])) ?>
                    </td>
                    <td>
                        <span class="badge-success"><i class="fa-solid fa-check-circle"></i> Credited</span>
                    </td>
                    <td class="amount-text">+$15.00</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty">
            <i class="fa-solid fa-receipt"></i>
            <p>No earnings yet. Add destinations to start earning!</p>
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