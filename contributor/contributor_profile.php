<?php
session_start();

if (!isset($_SESSION['contributor_id'])) {
    header('Location: contributor_login.php');
    exit();
}

include '../database/dbconfig.php';

$contributor_id = $_SESSION['contributor_id'];

// Fetch user data
$user = [];
$stmt = $conn->prepare("SELECT * FROM contributors WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $contributor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
    }
    $stmt->close();
}

$first_name = explode(' ', $user['name'] ?? 'User')[0];
$profile_pic = $user['profile_pic'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile – TripMate Contributor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        /* TripMate Global Design System */
        :root {
            --bg-base: #f8fafc; --bg-surface: #ffffff; --text-main: #0f172a; --text-muted: #475569;
            --primary: #4f46e5; --secondary: #06b6d4; --nav-bg: rgba(255, 255, 255, 0.75);
            --card-border: rgba(79, 70, 229, 0.15); --shadow-color: rgba(15, 23, 42, 0.08); --glow-color: rgba(6, 182, 212, 0.4);
        }
        body.dark-mode {
            --bg-base: #09090b; --bg-surface: #18181b; --text-main: #f8fafc; --text-muted: #cbd5e1;
            --primary: #818cf8; --secondary: #22d3ee; --nav-bg: rgba(24, 24, 27, 0.75);
            --card-border: rgba(255, 255, 255, 0.1); --shadow-color: rgba(0, 0, 0, 0.6); --glow-color: rgba(34, 211, 238, 0.3);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); min-height: 100vh; position: relative; overflow-x: hidden; transition: background-color 0.4s ease, color 0.4s ease; padding-top: 120px; }
        body::before { content: ''; position: fixed; top: -10%; left: -10%; width: 50vw; height: 50vw; background: radial-gradient(circle, var(--glow-color) 0%, transparent 60%); opacity: 0.5; pointer-events: none; }
        
        /* Navbar */
        .navbar { position: fixed; top: 20px; left: 5%; width: 90%; height: 70px; background: var(--nav-bg); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid var(--card-border); border-radius: 50px; display: flex; justify-content: space-between; align-items: center; padding: 0 30px; z-index: 1000; box-shadow: 0 10px 30px var(--shadow-color); }
        .logo { display: flex; align-items: center; gap: 12px; font-size: 1.4rem; font-weight: 800; text-decoration: none; color: var(--text-main); }
        .logo i { color: var(--primary); font-size: 1.5rem; transform: rotate(-10deg); transition: transform 0.3s; }
        
        .nav-links { display: flex; align-items: center; gap: 18px; list-style: none; margin-left: auto; margin-right: 20px; }
        .nav-link { color: var(--text-muted); text-decoration: none; font-weight: 600; transition: color 0.2s; display: flex; align-items: center; gap: 6px; font-size: 0.95rem; }
        .nav-link:hover, .nav-link.active { color: var(--primary); }
        
        .theme-toggle { background: var(--bg-surface); border: 1px solid var(--card-border); color: var(--text-main); width: 44px; height: 44px; border-radius: 50%; cursor: pointer; display: flex; justify-content: center; align-items: center; transition: all 0.3s; box-shadow: 0 4px 10px var(--shadow-color); flex-shrink: 0; }
        .theme-toggle:hover { transform: rotate(20deg) scale(1.1); color: var(--primary); border-color: var(--primary); }

        /* Profile Layout */
        .page { max-width: 1100px; margin: 0 auto; padding: 0 1.5rem 2rem; position: relative; z-index: 1; display: grid; grid-template-columns: 300px 1fr; gap: 2rem; }
        @media (max-width: 850px) { .page { grid-template-columns: 1fr; } }

        /* Left Side: Avatar Card */
        .profile-card { background: var(--bg-surface); border: 1px solid var(--card-border); border-radius: 24px; padding: 2.5rem 2rem; text-align: center; box-shadow: 0 15px 35px var(--shadow-color); height: fit-content; position: relative; overflow: hidden; }
        .profile-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100px; background: linear-gradient(135deg, var(--primary), var(--secondary)); opacity: 0.1; }
        
        .avatar-lg { width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--secondary)); display: inline-flex; align-items: center; justify-content: center; font-size: 3rem; font-weight: 700; color: #fff; border: 4px solid var(--bg-surface); box-shadow: 0 10px 20px var(--shadow-color); position: relative; z-index: 2; margin-bottom: 1rem; overflow: hidden; }
        .avatar-lg img { width: 100%; height: 100%; object-fit: cover; }
        
        .profile-card h2 { font-size: 1.4rem; font-weight: 800; color: var(--text-main); margin-bottom: 5px; }
        .profile-card p { font-size: 0.95rem; color: var(--text-muted); margin-bottom: 20px; }
        .role-badge { display: inline-block; background: rgba(6, 182, 212, 0.1); color: var(--secondary); padding: 5px 15px; border-radius: 50px; font-size: 0.8rem; font-weight: 700; border: 1px solid rgba(6, 182, 212, 0.2); margin-bottom: 20px; }
        
        .edit-pic-btn { width: 100%; background: var(--bg-base); border: 1px solid var(--card-border); color: var(--text-main); padding: 0.8rem; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .edit-pic-btn:hover { border-color: var(--primary); color: var(--primary); }

        /* Right Side: Details Form */
        .details-card { background: var(--bg-surface); border: 1px solid var(--card-border); border-radius: 24px; padding: 2.5rem; box-shadow: 0 15px 35px var(--shadow-color); }
        .section-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--text-main); display: flex; align-items: center; gap: 10px; border-bottom: 1px solid var(--card-border); padding-bottom: 10px; }
        .section-title i { color: var(--primary); }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
        @media (max-width: 600px) { .form-grid { grid-template-columns: 1fr; } }

        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; }
        .input-wrap { position: relative; }
        .input-wrap i.icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 1rem; }
        .input-wrap textarea ~ i.icon { top: 15px; transform: none; }
        
        .read-only-input { width: 100%; background: var(--bg-base); color: var(--text-main); border: 1px solid var(--card-border); padding: 12px 15px 12px 42px; border-radius: 12px; font-size: 0.95rem; font-family: 'Inter', sans-serif; cursor: not-allowed; opacity: 0.8; }
        textarea.read-only-input { min-height: 80px; resize: none; }

        .btn-update { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: #fff; border: none; padding: 1rem 2rem; border-radius: 12px; font-size: 0.95rem; font-weight: 700; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-update:hover { transform: translateY(-2px); box-shadow: 0 10px 20px var(--glow-color); }
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
        <a href="contributor_wallet.php" class="nav-link"><i class="fa-solid fa-wallet"></i> Wallet</a>
        <a href="contributor_profile.php" class="nav-link active"><i class="fa-solid fa-user"></i> Profile</a>
    </ul>
    <button class="theme-toggle" id="themeToggle"><i class="fas fa-moon"></i></button>
</nav>

<div class="page">
    
    <div class="profile-card">
        <div class="avatar-lg">
            <?php if ($profile_pic): ?>
                <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile">
            <?php else: ?>
                <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
            <?php endif; ?>
        </div>
        <h2><?= htmlspecialchars($user['name'] ?? 'User') ?></h2>
        <p>@<?= htmlspecialchars($user['username'] ?? '') ?></p>
        <span class="role-badge"><i class="fa-solid fa-globe"></i> Global Contributor</span>
        
        <button class="edit-pic-btn" onclick="alert('Profile picture upload feature coming soon.')">
            <i class="fa-solid fa-camera"></i> Change Avatar
        </button>
    </div>

    <div class="details-card">
        <h2 class="section-title"><i class="fa-solid fa-address-card"></i> Personal Information</h2>
        <div class="form-grid">
            <div class="form-group">
                <label>Full Name</label>
                <div class="input-wrap">
                    <input type="text" class="read-only-input" value="<?= htmlspecialchars($user['name'] ?? '') ?>" readonly>
                    <i class="fa-solid fa-user icon"></i>
                </div>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <div class="input-wrap">
                    <input type="text" class="read-only-input" value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly>
                    <i class="fa-solid fa-envelope icon"></i>
                </div>
            </div>
            <div class="form-group">
                <label>Mobile Number</label>
                <div class="input-wrap">
                    <input type="text" class="read-only-input" value="<?= htmlspecialchars($user['mobile'] ?? 'Not provided') ?>" readonly>
                    <i class="fa-solid fa-phone icon"></i>
                </div>
            </div>
            <div class="form-group">
                <label>Date of Birth</label>
                <div class="input-wrap">
                    <input type="text" class="read-only-input" value="<?= !empty($user['dob']) ? date('d M Y', strtotime($user['dob'])) : 'Not provided' ?>" readonly>
                    <i class="fa-solid fa-calendar icon"></i>
                </div>
            </div>
        </div>

        <h2 class="section-title"><i class="fa-solid fa-map-location-dot"></i> Location Details</h2>
        <div class="form-grid">
            <div class="form-group">
                <label>Country</label>
                <div class="input-wrap">
                    <input type="text" class="read-only-input" value="<?= htmlspecialchars($user['country'] ?? 'Not provided') ?>" readonly>
                    <i class="fa-solid fa-earth-americas icon"></i>
                </div>
            </div>
            <div class="form-group">
                <label>State / Region</label>
                <div class="input-wrap">
                    <input type="text" class="read-only-input" value="<?= htmlspecialchars($user['state_region'] ?? 'Not provided') ?>" readonly>
                    <i class="fa-solid fa-map icon"></i>
                </div>
            </div>
            <div class="form-group">
                <label>City</label>
                <div class="input-wrap">
                    <input type="text" class="read-only-input" value="<?= htmlspecialchars($user['city'] ?? 'Not provided') ?>" readonly>
                    <i class="fa-solid fa-city icon"></i>
                </div>
            </div>
        </div>

        <h2 class="section-title"><i class="fa-solid fa-camera-retro"></i> Public Profile</h2>
        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label>Social Link</label>
            <div class="input-wrap">
                <input type="text" class="read-only-input" value="<?= htmlspecialchars($user['social_link'] ?? 'Not provided') ?>" readonly>
                <i class="fa-solid fa-link icon"></i>
            </div>
        </div>
        <div class="form-group" style="margin-bottom: 2rem;">
            <label>Bio</label>
            <div class="input-wrap">
                <textarea class="read-only-input" readonly><?= htmlspecialchars($user['bio'] ?? 'No bio provided.') ?></textarea>
                <i class="fa-solid fa-pen-nib icon"></i>
            </div>
        </div>

        <button class="btn-update" onclick="alert('Profile editing functionality coming soon.')">
            <i class="fa-solid fa-pen-to-square"></i> Edit Profile Information
        </button>
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