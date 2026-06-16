<?php
session_start();
require_once __DIR__ . '/../database/dbconfig.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.html'); exit(); }
$user_id = $_SESSION['user_id'];

// Get reviews
$stmt = $conn->prepare("
    SELECT r.*, d.name as destination_name, d.location, d.image_urls 
    FROM reviews r 
    LEFT JOIN destinations d ON r.destination_id = d.id 
    WHERE r.user_id = ? 
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$reviews = [];
while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews - TripMate</title>
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
        .main { max-width:900px; margin:0 auto; padding:88px 20px 40px; }
        .page-header { margin-bottom:24px; }
        .page-header h1 { font-size:2rem; font-weight:800; display:flex; align-items:center; gap:10px; }
        .page-header h1 i { color:var(--secondary); }
        .page-header p { color:var(--muted); margin-top:6px; }
        .review-card { background:var(--card); border-radius:16px; padding:24px; box-shadow:0 4px 20px rgba(0,0,0,0.06);
            margin-bottom:16px; border:1px solid var(--border); transition:transform .2s; }
        .review-card:hover { transform:translateY(-2px); }
        .review-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px; }
        .review-dest { font-weight:700; font-size:1.1rem; }
        .review-date { color:var(--muted); font-size:0.8rem; }
        .stars { color:#fbbf24; font-size:1rem; margin-bottom:8px; }
        .review-title { font-weight:600; margin-bottom:6px; }
        .review-content { color:var(--muted); font-size:0.9rem; line-height:1.6; }
        .review-actions { display:flex; gap:8px; margin-top:12px; }
        .btn-sm { padding:6px 14px; border-radius:8px; border:none; font-weight:600; font-size:0.8rem; cursor:pointer; transition:all .2s; }
        .btn-edit { background:rgba(79,70,229,0.1); color:var(--primary); }
        .btn-edit:hover { background:rgba(79,70,229,0.2); }
        .btn-delete { background:rgba(239,68,68,0.1); color:#ef4444; }
        .btn-delete:hover { background:rgba(239,68,68,0.2); }
        .empty-state { text-align:center; padding:60px 20px; }
        .empty-state i { font-size:4rem; color:var(--border); margin-bottom:16px; }
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
            <h1><i class="fas fa-star"></i> My Reviews</h1>
            <p>You've written <?php echo count($reviews); ?> review(s)</p>
        </div>
        <?php if (empty($reviews)): ?>
            <div class="empty-state">
                <i class="fas fa-comment-dots"></i>
                <h3>No reviews yet</h3>
                <p style="color:var(--muted)">Visit destinations and share your experience!</p>
            </div>
        <?php else: ?>
            <?php foreach ($reviews as $r): ?>
            <div class="review-card" id="review-<?php echo $r['id']; ?>">
                <div class="review-header">
                    <div>
                        <div class="review-dest"><?php echo htmlspecialchars($r['destination_name'] ?? 'Destination'); ?></div>
                        <div class="stars"><?php for ($i=1; $i<=5; $i++) echo '<i class="fas fa-star'.($i<=$r['rating']?'':' opacity-25').'"></i> '; ?></div>
                    </div>
                    <div class="review-date"><?php echo date('M d, Y', strtotime($r['created_at'])); ?></div>
                </div>
                <?php if (!empty($r['title'])): ?><div class="review-title"><?php echo htmlspecialchars($r['title']); ?></div><?php endif; ?>
                <div class="review-content"><?php echo htmlspecialchars($r['comment'] ?? $r['content'] ?? ''); ?></div>
                <div class="review-actions">
                    <button class="btn-sm btn-edit" onclick="editReview(<?php echo $r['id']; ?>)"><i class="fas fa-edit"></i> Edit</button>
                    <button class="btn-sm btn-delete" onclick="deleteReview(<?php echo $r['id']; ?>)"><i class="fas fa-trash"></i> Delete</button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <script>
    function editReview(id) { window.location.href = 'edit_review.php?id=' + id; }
    function deleteReview(id) {
        if (!confirm('Delete this review?')) return;
        fetch('../actions/delete_review.php', {
            method: 'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({review_id: id})
        }).then(r=>r.json()).then(d => {
            if (d.status === 'success') document.getElementById('review-'+id).remove();
            else alert(d.message || 'Failed');
        }).catch(() => alert('Error'));
    }
    </script>
</body>
</html>
