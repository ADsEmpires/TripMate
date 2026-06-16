<?php
session_start();
require_once __DIR__ . '/../database/dbconfig.php';

$user_id = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? 'Guest';

// Get destinations for blog-style travel articles
$stmt = $conn->prepare("SELECT d.*, 
    (SELECT COUNT(*) FROM reviews WHERE destination_id = d.id) as review_count,
    (SELECT AVG(rating) FROM reviews WHERE destination_id = d.id) as avg_rating
    FROM destinations d 
    WHERE d.is_verified = 1 AND d.description IS NOT NULL AND LENGTH(d.description) > 50
    ORDER BY d.created_at DESC LIMIT 12");
$stmt->execute();
$result = $stmt->get_result();
$articles = [];
while ($row = $result->fetch_assoc()) {
    if (isset($row['image_urls']) && is_string($row['image_urls'])) {
        $row['image_urls'] = json_decode($row['image_urls'], true) ?: [];
    }
    $articles[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Blog - TripMate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#4f46e5; --secondary:#06b6d4; --bg:#f8fafc; --card:#fff; --text:#0f172a; --muted:#64748b; --border:#e2e8f0; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:var(--bg); color:var(--text); }
        .navbar { position:fixed; top:0; left:0; right:0; height:64px; background:linear-gradient(135deg,#16034f,#2a0a8a);
            display:flex; align-items:center; justify-content:space-between; padding:0 24px; z-index:1000; color:#fff; }
        .navbar .logo { display:flex; align-items:center; gap:10px; font-weight:800; font-size:1.3rem; text-decoration:none; color:#fff; }
        .navbar .logo i { color:#ff6600; }
        .nav-links a { color:rgba(255,255,255,0.85); text-decoration:none; font-weight:600; font-size:0.9rem; margin-left:16px; }
        .nav-links a:hover { color:#fff; }
        .hero { background:linear-gradient(135deg,#16034f 0%,#4f46e5 50%,#06b6d4 100%); padding:120px 20px 60px; text-align:center; color:#fff; }
        .hero h1 { font-size:3rem; font-weight:800; margin-bottom:12px; }
        .hero p { font-size:1.2rem; opacity:0.85; max-width:600px; margin:0 auto; }
        .main { max-width:1200px; margin:0 auto; padding:40px 20px; }
        .blog-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(350px,1fr)); gap:24px; }
        .blog-card { background:var(--card); border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.06);
            transition:transform .3s, box-shadow .3s; cursor:pointer; border:1px solid var(--border); }
        .blog-card:hover { transform:translateY(-5px); box-shadow:0 12px 40px rgba(0,0,0,0.1); }
        .blog-image { height:220px; background-size:cover; background-position:center; position:relative; }
        .blog-image .category { position:absolute; top:16px; left:16px; background:rgba(79,70,229,0.9); color:#fff;
            padding:4px 12px; border-radius:20px; font-size:0.75rem; font-weight:700; }
        .blog-body { padding:20px; }
        .blog-body h3 { font-size:1.2rem; font-weight:700; margin-bottom:8px; line-height:1.4; }
        .blog-body .excerpt { color:var(--muted); font-size:0.9rem; line-height:1.6; margin-bottom:12px;
            display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
        .blog-meta { display:flex; justify-content:space-between; align-items:center; font-size:0.8rem; color:var(--muted); }
        .blog-meta .rating { color:#fbbf24; }
        .blog-meta .reviews { display:flex; align-items:center; gap:4px; }
        .empty-state { text-align:center; padding:80px 20px; }
        .empty-state i { font-size:4rem; color:var(--border); margin-bottom:16px; }
        @media(max-width:768px) { .hero h1 { font-size:2rem; } .blog-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="../main/index.html" class="logo"><i class="fa-solid fa-paper-plane"></i> Trip<span style="color:var(--secondary)">Mate</span></a>
        <div class="nav-links">
            <a href="../main/index.html"><i class="fas fa-home"></i> Home</a>
            <a href="../search/search.html"><i class="fas fa-search"></i> Search</a>
            <?php if ($user_id): ?>
            <a href="../user/user_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="hero">
        <h1><i class="fas fa-blog"></i> Travel Blog</h1>
        <p>Discover amazing destinations, travel tips, and inspiring stories from fellow travelers</p>
    </div>

    <div class="main">
        <?php if (empty($articles)): ?>
            <div class="empty-state">
                <i class="fas fa-pen-fancy"></i>
                <h3>No articles yet</h3>
                <p style="color:var(--muted)">Check back soon for travel stories and guides!</p>
            </div>
        <?php else: ?>
            <div class="blog-grid">
                <?php foreach ($articles as $a):
                    $img = !empty($a['image_urls']) && is_array($a['image_urls']) ? $a['image_urls'][0] : '../image/placeholder.jpg';
                    $rating = round($a['avg_rating'] ?? 0, 1);
                ?>
                <div class="blog-card" onclick="window.location.href='../user/destination_details.php?id=<?php echo $a['id']; ?>'">
                    <div class="blog-image" style="background-image:url('<?php echo htmlspecialchars($img); ?>')">
                        <span class="category"><?php echo htmlspecialchars($a['type'] ?? 'Travel'); ?></span>
                    </div>
                    <div class="blog-body">
                        <h3>Exploring <?php echo htmlspecialchars($a['name']); ?></h3>
                        <div class="excerpt"><?php echo htmlspecialchars(substr($a['description'], 0, 200)); ?>...</div>
                        <div class="blog-meta">
                            <div class="reviews">
                                <i class="fas fa-map-marker-alt" style="color:var(--secondary)"></i>
                                <?php echo htmlspecialchars($a['location']); ?>
                            </div>
                            <div>
                                <span class="rating"><?php for($i=0;$i<round($rating);$i++) echo '★'; ?></span>
                                (<?php echo $a['review_count']; ?> reviews)
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
