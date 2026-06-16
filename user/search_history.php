<?php
// user/search_history.php — Displays user's search history
session_start();
require_once __DIR__ . '/../database/dbconfig.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.html'); exit(); }
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM search_history WHERE user_id = ? ORDER BY search_date DESC LIMIT 50");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$searches = [];
while ($row = $result->fetch_assoc()) { $searches[] = $row; }
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search History - TripMate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#4f46e5; --secondary:#06b6d4; --bg:#f1f5f9; --card:#fff; --text:#0f172a; --muted:#64748b; --border:#e2e8f0; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:var(--bg); color:var(--text); }
        .navbar { position:fixed; top:0; left:0; right:0; height:64px; background:linear-gradient(135deg,#16034f,#2a0a8a);
            display:flex; align-items:center; justify-content:space-between; padding:0 24px; z-index:1000; color:#fff; }
        .navbar .logo { display:flex; align-items:center; gap:10px; font-weight:800; font-size:1.3rem; text-decoration:none; color:#fff; }
        .navbar .logo i { color:#ff6600; }
        .nav-links a { color:rgba(255,255,255,0.85); text-decoration:none; font-weight:600; font-size:0.9rem; margin-left:16px; }
        .main { max-width:800px; margin:0 auto; padding:88px 20px 40px; }
        .page-header h1 { font-size:2rem; font-weight:800; margin-bottom:8px; display:flex; align-items:center; gap:10px; }
        .page-header h1 i { color:var(--secondary); }
        .page-header p { color:var(--muted); margin-bottom:24px; }
        .search-item { background:var(--card); border-radius:12px; padding:16px 20px; margin-bottom:10px; display:flex;
            justify-content:space-between; align-items:center; box-shadow:0 2px 10px rgba(0,0,0,0.04); border:1px solid var(--border);
            cursor:pointer; transition:all .2s; }
        .search-item:hover { transform:translateX(4px); border-color:var(--secondary); }
        .search-query { font-weight:600; display:flex; align-items:center; gap:8px; }
        .search-query i { color:var(--secondary); }
        .search-date { color:var(--muted); font-size:0.8rem; }
        .empty-state { text-align:center; padding:60px 20px; }
        .empty-state i { font-size:4rem; color:var(--border); margin-bottom:16px; }
        .btn-clear { padding:8px 20px; background:rgba(239,68,68,0.1); color:#ef4444; border:2px solid transparent;
            border-radius:8px; font-weight:600; cursor:pointer; transition:all .2s; }
        .btn-clear:hover { border-color:#ef4444; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="../main/index.html" class="logo"><i class="fa-solid fa-paper-plane"></i> Trip<span style="color:var(--secondary)">Mate</span></a>
        <div class="nav-links">
            <a href="user_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        </div>
    </nav>
    <div class="main">
        <div class="page-header">
            <h1><i class="fas fa-history"></i> Search History</h1>
            <p><?php echo count($searches); ?> recent searches</p>
        </div>
        <?php if (empty($searches)): ?>
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3>No search history</h3>
                <p style="color:var(--muted)">Your search history will appear here</p>
            </div>
        <?php else: ?>
            <?php foreach ($searches as $s): ?>
            <div class="search-item" onclick="window.location.href='../search/search.html?q=<?php echo urlencode($s['search_query']); ?>'">
                <div class="search-query"><i class="fas fa-search"></i> <?php echo htmlspecialchars($s['search_query']); ?></div>
                <div class="search-date"><?php echo date('M d, Y h:i A', strtotime($s['search_date'])); ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
