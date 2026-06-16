<?php
session_start();
require_once __DIR__ . '/../database/dbconfig.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.html');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: ../auth/login.html');
    exit();
}

// Get stats
$stats_stmt = $conn->prepare("SELECT 
    (SELECT COUNT(*) FROM bookings WHERE user_id = ?) as bookings,
    (SELECT COUNT(*) FROM reviews WHERE user_id = ?) as reviews,
    (SELECT COUNT(*) FROM user_history WHERE user_id = ? AND activity_type = 'favorite') as favorites
");
$stats_stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();
$conn->close();

$member_since = date('F Y', strtotime($user['created_at']));
$profile_pic = $user['profile_pic'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=4f46e5&color=fff&size=120';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['name']); ?> - Profile | TripMate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#4f46e5; --secondary:#06b6d4; --bg:#f1f5f9; --card:#fff; --text:#0f172a; --muted:#64748b; --border:#e2e8f0; --accent:#ff6600; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:var(--bg); color:var(--text); }
        .navbar { position:fixed; top:0; left:0; right:0; height:64px; background:linear-gradient(135deg,#16034f,#2a0a8a);
            display:flex; align-items:center; justify-content:space-between; padding:0 24px; z-index:1000; color:#fff; }
        .navbar .logo { display:flex; align-items:center; gap:10px; font-weight:800; font-size:1.3rem; text-decoration:none; color:#fff; }
        .navbar .logo i { color:var(--accent); }
        .nav-links { display:flex; gap:16px; }
        .nav-links a { color:rgba(255,255,255,0.85); text-decoration:none; font-weight:600; font-size:0.9rem; }
        .nav-links a:hover { color:#fff; }
        .main { max-width:900px; margin:0 auto; padding:88px 20px 40px; }
        .profile-header { background:var(--card); border-radius:20px; padding:40px; text-align:center; box-shadow:0 4px 20px rgba(0,0,0,0.06);
            margin-bottom:24px; position:relative; overflow:hidden; }
        .profile-header::before { content:''; position:absolute; top:0; left:0; right:0; height:120px;
            background:linear-gradient(135deg,var(--primary),var(--secondary)); }
        .avatar { width:120px; height:120px; border-radius:50%; border:4px solid #fff; object-fit:cover; position:relative;
            margin-bottom:16px; box-shadow:0 4px 20px rgba(0,0,0,0.15); }
        .profile-header h1 { font-size:1.8rem; font-weight:800; margin-bottom:4px; position:relative; }
        .profile-header .email { color:var(--muted); font-size:0.95rem; position:relative; }
        .profile-header .member-since { color:var(--muted); font-size:0.85rem; margin-top:8px; position:relative; }
        .stats-row { display:flex; justify-content:center; gap:40px; margin-top:20px; position:relative; }
        .stat { text-align:center; }
        .stat .num { font-size:1.5rem; font-weight:800; color:var(--primary); }
        .stat .label { font-size:0.8rem; color:var(--muted); }
        .section { background:var(--card); border-radius:16px; padding:24px; box-shadow:0 4px 20px rgba(0,0,0,0.06); margin-bottom:20px; }
        .section h2 { font-size:1.2rem; font-weight:700; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
        .section h2 i { color:var(--secondary); }
        .form-group { margin-bottom:16px; }
        .form-group label { display:block; font-weight:600; margin-bottom:6px; font-size:0.9rem; }
        .form-group input { width:100%; padding:12px 16px; border:2px solid var(--border); border-radius:10px; font-family:inherit;
            font-size:0.95rem; transition:border-color .2s; }
        .form-group input:focus { outline:none; border-color:var(--secondary); }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .btn-save { padding:12px 32px; background:linear-gradient(135deg,var(--primary),var(--secondary)); color:#fff; border:none;
            border-radius:10px; font-weight:700; cursor:pointer; font-size:0.95rem; transition:all .2s; }
        .btn-save:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(79,70,229,0.3); }
        .btn-danger { padding:10px 20px; background:rgba(239,68,68,0.1); color:#ef4444; border:2px solid #ef4444; border-radius:10px;
            font-weight:600; cursor:pointer; transition:all .2s; }
        .btn-danger:hover { background:#ef4444; color:#fff; }
        .badge-provider { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:20px;
            font-size:0.8rem; font-weight:600; position:relative; }
        .badge-manual { background:rgba(79,70,229,0.1); color:var(--primary); }
        .badge-google { background:rgba(234,67,53,0.1); color:#EA4335; }
        @media(max-width:640px) { .form-row { grid-template-columns:1fr; } .stats-row { gap:20px; } }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="../main/index.html" class="logo"><i class="fa-solid fa-paper-plane"></i> Trip<span style="color:var(--secondary)">Mate</span></a>
        <div class="nav-links">
            <a href="user_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="my_trips.php"><i class="fas fa-suitcase"></i> My Trips</a>
        </div>
    </nav>

    <div class="main">
        <div class="profile-header">
            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" class="avatar">
            <h1><?php echo htmlspecialchars($user['name']); ?></h1>
            <div class="email"><?php echo htmlspecialchars($user['email']); ?></div>
            <span class="badge-provider badge-<?php echo $user['auth_provider']; ?>">
                <i class="fas fa-<?php echo $user['auth_provider'] === 'google' ? 'google' : 'envelope'; ?>"></i>
                <?php echo ucfirst($user['auth_provider']); ?> Account
            </span>
            <div class="member-since"><i class="fas fa-calendar-alt"></i> Member since <?php echo $member_since; ?></div>
            <div class="stats-row">
                <div class="stat"><div class="num"><?php echo $stats['bookings']; ?></div><div class="label">Bookings</div></div>
                <div class="stat"><div class="num"><?php echo $stats['reviews']; ?></div><div class="label">Reviews</div></div>
                <div class="stat"><div class="num"><?php echo $stats['favorites']; ?></div><div class="label">Favorites</div></div>
            </div>
        </div>

        <div class="section">
            <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>
            <form id="profileForm" onsubmit="saveProfile(event)">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly style="background:var(--bg);">
                    </div>
                </div>
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
            </form>
        </div>

        <div class="section">
            <h2><i class="fas fa-lock"></i> Change Password</h2>
            <form id="passwordForm" onsubmit="changePassword(event)">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" minlength="6" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" minlength="6" required>
                    </div>
                </div>
                <button type="submit" class="btn-save"><i class="fas fa-key"></i> Update Password</button>
            </form>
        </div>
    </div>

    <script>
    function saveProfile(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        fetch('../actions/update_profile.php', { method:'POST', body:formData })
            .then(r => r.json())
            .then(d => { alert(d.message || 'Profile updated!'); if(d.status==='success') location.reload(); })
            .catch(() => alert('Failed to update profile'));
    }
    function changePassword(e) {
        e.preventDefault();
        const form = e.target;
        if (form.new_password.value !== form.confirm_password.value) { alert('Passwords do not match'); return; }
        const formData = new FormData(form);
        fetch('../actions/change_password.php', { method:'POST', body:formData })
            .then(r => r.json())
            .then(d => { alert(d.message || 'Password updated!'); if(d.status==='success') form.reset(); })
            .catch(() => alert('Failed to change password'));
    }
    </script>
</body>
</html>
