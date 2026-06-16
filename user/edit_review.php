<?php
session_start();
require_once __DIR__ . '/../database/dbconfig.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.html'); exit(); }
$user_id = $_SESSION['user_id'];

$review_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$review_id) { header('Location: my_reviews.php'); exit(); }

$stmt = $conn->prepare("SELECT r.*, d.name as destination_name FROM reviews r LEFT JOIN destinations d ON r.destination_id = d.id WHERE r.id = ? AND r.user_id = ?");
$stmt->bind_param("ii", $review_id, $user_id);
$stmt->execute();
$review = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$review) { header('Location: my_reviews.php'); exit(); }
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Review - TripMate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#4f46e5; --secondary:#06b6d4; --bg:#f1f5f9; --card:#fff; --text:#0f172a; --muted:#64748b; --border:#e2e8f0; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:var(--bg); color:var(--text); }
        .navbar { position:fixed; top:0; left:0; right:0; height:64px; background:linear-gradient(135deg,#16034f,#2a0a8a);
            display:flex; align-items:center; padding:0 24px; z-index:1000; }
        .navbar .logo { display:flex; align-items:center; gap:10px; font-weight:800; font-size:1.3rem; text-decoration:none; color:#fff; }
        .navbar .logo i { color:#ff6600; }
        .main { max-width:700px; margin:0 auto; padding:88px 20px 40px; }
        .back-link { color:var(--primary); text-decoration:none; font-weight:600; display:inline-flex; align-items:center; gap:6px; margin-bottom:20px; }
        .form-card { background:var(--card); border-radius:16px; padding:32px; box-shadow:0 4px 20px rgba(0,0,0,0.06); }
        .form-card h1 { font-size:1.5rem; font-weight:800; margin-bottom:8px; }
        .form-card .subtitle { color:var(--muted); margin-bottom:24px; }
        .form-group { margin-bottom:20px; }
        .form-group label { display:block; font-weight:600; margin-bottom:8px; font-size:0.9rem; }
        .form-group input, .form-group textarea, .form-group select { width:100%; padding:12px 16px; border:2px solid var(--border);
            border-radius:10px; font-family:inherit; font-size:0.95rem; transition:border-color .2s; }
        .form-group input:focus, .form-group textarea:focus { outline:none; border-color:var(--secondary); }
        .stars-input { display:flex; gap:8px; font-size:1.8rem; cursor:pointer; color:#e2e8f0; }
        .stars-input i.active { color:#fbbf24; }
        .btn-save { padding:12px 32px; background:linear-gradient(135deg,var(--primary),var(--secondary)); color:#fff; border:none;
            border-radius:10px; font-weight:700; cursor:pointer; font-size:1rem; width:100%; transition:all .2s; }
        .btn-save:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(79,70,229,0.3); }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="../main/index.html" class="logo"><i class="fa-solid fa-paper-plane"></i> Trip<span style="color:var(--secondary)">Mate</span></a>
    </nav>
    <div class="main">
        <a href="my_reviews.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to My Reviews</a>
        <div class="form-card">
            <h1>Edit Review</h1>
            <p class="subtitle">For <?php echo htmlspecialchars($review['destination_name'] ?? 'Destination'); ?></p>
            <form id="editForm" onsubmit="saveReview(event)">
                <input type="hidden" name="review_id" value="<?php echo $review_id; ?>">
                <div class="form-group">
                    <label>Rating</label>
                    <div class="stars-input" id="starsInput">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'active' : ''; ?>" onclick="setRating(<?php echo $i; ?>)"></i>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="ratingInput" value="<?php echo $review['rating']; ?>">
                </div>
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($review['title'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Your Review</label>
                    <textarea name="content" rows="6" required><?php echo htmlspecialchars($review['comment'] ?? $review['content'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
            </form>
        </div>
    </div>
    <script>
    let currentRating = <?php echo $review['rating']; ?>;
    function setRating(n) {
        currentRating = n;
        document.getElementById('ratingInput').value = n;
        document.querySelectorAll('#starsInput i').forEach((s, i) => {
            s.classList.toggle('active', i < n);
        });
    }
    function saveReview(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        fetch('../actions/update_review.php', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify(data)
        }).then(r => r.json()).then(d => {
            if (d.status === 'success') { alert('Review updated!'); window.location.href = 'my_reviews.php'; }
            else alert(d.message || 'Failed to update');
        }).catch(() => alert('Error'));
    }
    </script>
</body>
</html>
