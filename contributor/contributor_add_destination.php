<?php
// Start the session for contributor authentication
session_start();

if (!isset($_SESSION['contributor_id'])) {
    header('Location: contributor_login.php');
    exit();
}

// Include database configuration and connection
include '../database/dbconfig.php';

$contributor_id   = $_SESSION['contributor_id'];
$contributor_name = $_SESSION['contributor_name'];
$contributor_pic  = $_SESSION['contributor_profile_pic'] ?? null;

// Catch the success message from the final hotel step
if (isset($_GET['msg']) && $_GET['msg'] === 'completed') {
    $_SESSION['message'] = "Complete package submitted successfully! It is now pending admin review.";
    header("Location: contributor_add_destination.php");
    exit();
}

// Handle clear draft request
if (isset($_GET['clear_draft']) && $_GET['clear_draft'] == 1) {
    if (isset($_SESSION['temp_destination'])) {
        $temp_images = json_decode($_SESSION['temp_destination']['image_urls'] ?? '[]', true) ?: [];
        foreach ($temp_images as $img) {
            $abs_path = __DIR__ . '/../' . $img;
            if (file_exists($abs_path)) {
                @unlink($abs_path);
            }
        }
    }
    if (isset($_SESSION['temp_hotels'])) {
        foreach ($_SESSION['temp_hotels'] as $hotel) {
            if (!empty($hotel['image_url'])) {
                $abs_path = __DIR__ . '/../' . $hotel['image_url'];
                if (file_exists($abs_path)) {
                    @unlink($abs_path);
                }
            }
        }
    }
    unset($_SESSION['temp_destination']);
    unset($_SESSION['temp_flights']);
    unset($_SESSION['temp_hotels']);
    $_SESSION['message'] = "Draft cleared successfully.";
    header("Location: contributor_add_destination.php");
    exit();
}

$temp_dest = $_SESSION['temp_destination'] ?? null;
$temp_seasons = isset($temp_dest['season']) ? explode(',', $temp_dest['season']) : [];
$temp_people = isset($temp_dest['people']) ? (str_starts_with($temp_dest['people'], '[') ? json_decode($temp_dest['people'], true) : explode(',', $temp_dest['people'])) ?: [] : [];

// Handle form submission for adding a new destination
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $type = $_POST['type'] ?? '';
    $description = $_POST['description'] ?? '';
    $location = $_POST['location'] ?? '';
    $budget = isset($_POST['budget']) ? (float)$_POST['budget'] : 0.0;
    
    $map_link = $_POST['map_link'] ?? '';
    if (strlen($map_link) > 255) {
        $map_link = substr($map_link, 0, 255);
    }
    
    $season = isset($_POST['season']) ? implode(',', $_POST['season']) : '';
    $people = isset($_POST['people']) ? json_encode($_POST['people']) : '[]';
    
    // Handle image upload
    $image_urls = [];
    if (isset($_SESSION['temp_destination']['image_urls'])) {
        $image_urls = json_decode($_SESSION['temp_destination']['image_urls'], true) ?: [];
    }
    
    $upload_dir = __DIR__ . '/../uploads/destinations/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (!empty($_FILES['images']['name'][0])) {
        $new_images = [];
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === 0) {
                $file_name = preg_replace("/[^a-zA-Z0-9.-]/", "_", basename($_FILES['images']['name'][$key]));
                $file_path = 'uploads/destinations/' . uniqid() . '_' . $file_name;
                $full_path = __DIR__ . '/../' . $file_path;
                
                if (move_uploaded_file($tmp_name, $full_path)) {
                    $new_images[] = $file_path;
                }
            }
        }
        if (!empty($new_images)) {
            // Delete old draft files
            foreach ($image_urls as $img) {
                $abs_path = __DIR__ . '/../' . $img;
                if (file_exists($abs_path)) {
                    @unlink($abs_path);
                }
            }
            $image_urls = $new_images;
        }
    }
    $image_urls_json = json_encode($image_urls);

    $_SESSION['temp_destination'] = [
        'name' => $name,
        'type' => $type,
        'description' => $description,
        'location' => $location,
        'budget' => $budget,
        'map_link' => $map_link,
        'season' => $season,
        'people' => $people,
        'image_urls' => $image_urls_json
    ];

    if (!isset($_SESSION['temp_flights'])) {
        $_SESSION['temp_flights'] = [];
    }
    if (!isset($_SESSION['temp_hotels'])) {
        $_SESSION['temp_hotels'] = [];
    }

    header("Location: contributor_manage_flights.php");
    exit();
}

// Fetch ONLY this contributor's destinations
$result = $conn->prepare("SELECT * FROM destinations WHERE contributor_id = ? ORDER BY id DESC");
$result->bind_param("i", $contributor_id);
$result->execute();
$dest_result = $result->get_result();
$dest_count = $dest_result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Destinations – TripMate Contributor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        /* TripMate Global Design System */
        :root {
            --bg-base: #f8fafc; --bg-surface: #ffffff; --text-main: #0f172a; --text-muted: #475569;
            --primary: #4f46e5; --secondary: #06b6d4; --nav-bg: rgba(255, 255, 255, 0.75);
            --card-border: rgba(79, 70, 229, 0.15); --shadow-color: rgba(15, 23, 42, 0.08); --glow-color: rgba(6, 182, 212, 0.4);
            --danger: #ef4444; --success: #10b981; --warning: #f59e0b;
        }
        body.dark-mode {
            --bg-base: #09090b; --bg-surface: #18181b; --text-main: #f8fafc; --text-muted: #cbd5e1;
            --primary: #818cf8; --secondary: #22d3ee; --nav-bg: rgba(24, 24, 27, 0.75);
            --card-border: rgba(255, 255, 255, 0.1); --shadow-color: rgba(0, 0, 0, 0.6); --glow-color: rgba(34, 211, 238, 0.3);
            --danger: #f87171; --success: #34d399; --warning: #fbbf24;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); min-height: 100vh; position: relative; overflow-x: hidden; transition: background-color 0.4s ease, color 0.4s ease; padding-top: 120px; }
        
        body.dark-mode .form-control { background-color: var(--bg-surface); color: var(--text-main); border-color: var(--card-border); }
        body.dark-mode h2, body.dark-mode h3, body.dark-mode label { color: var(--text-main) !important; }

        .navbar { position: fixed; top: 20px; left: 5%; width: 90%; height: 70px; background: var(--nav-bg); backdrop-filter: blur(20px); border: 1px solid var(--card-border); border-radius: 50px; display: flex; justify-content: space-between; align-items: center; padding: 0 30px; z-index: 1000; box-shadow: 0 10px 30px var(--shadow-color); }
        .logo { display: flex; align-items: center; gap: 12px; font-size: 1.4rem; font-weight: 800; text-decoration: none; color: var(--text-main); }
        .logo i { color: var(--primary); transform: rotate(-10deg); }
        .nav-badge { background: var(--glow-color); color: var(--primary); padding: 4px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; border: 1px solid var(--card-border); margin-left: 15px; }
        .nav-links { display: flex; align-items: center; gap: 18px; list-style: none; margin-left: auto; margin-right: 20px; }
        .nav-link { color: var(--text-muted); text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 6px; font-size: 0.95rem; }
        .nav-link.active { color: var(--primary); }
        .nav-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--secondary)); display: flex; align-items: center; justify-content: center; font-weight: 700; color: #fff; overflow: hidden; border: 2px solid var(--bg-surface); }
        .theme-toggle { background: var(--bg-surface); border: 1px solid var(--card-border); color: var(--text-main); width: 44px; height: 44px; border-radius: 50%; cursor: pointer; display: flex; justify-content: center; align-items: center; box-shadow: 0 4px 10px var(--shadow-color); }

        .page { max-width: 1200px; margin: 0 auto; padding: 0 1.5rem 2rem; }
        .widget-card { background: var(--bg-surface); border: 1px solid var(--card-border); border-radius: 24px; padding: 2rem; box-shadow: 0 15px 35px var(--shadow-color); }
        
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.6rem; font-weight: 700; color: var(--text-main); font-size: 0.95rem; }
        .form-control { width: 100%; padding: 0.9rem 1.2rem; border: 1px solid var(--card-border); border-radius: 12px; font-size: 1rem; background: var(--bg-base); color: var(--text-main); border-color: var(--card-border); }
        textarea.form-control { min-height: 120px; resize: vertical; }

        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.8rem 1.8rem; border-radius: 12px; font-weight: 700; cursor: pointer; border: none; gap: 8px; font-size: 1rem; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; }
        .btn-flight { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
        .btn-hotel { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .btn-outline { background: transparent; border: 2px solid var(--card-border); color: var(--text-main); }

        .alert { padding: 1.2rem 1.5rem; border-radius: 16px; margin-bottom: 2rem; font-weight: 600; display: flex; align-items: center; gap: 12px; }
        .alert-success { background-color: var(--bg-surface); color: var(--success); border: 1px solid var(--card-border); border-left: 5px solid var(--success); }
        .alert-danger { background-color: var(--bg-surface); color: var(--danger); border: 1px solid var(--card-border); border-left: 5px solid var(--danger); }

        .destination-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 2rem; }
        .destination-images { height: 220px; overflow: hidden; position: relative; }
        .destination-images img { width: 100%; height: 100%; object-fit: cover; }
        .destination-type-badge { position: absolute; top: 15px; right: 15px; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(10px); color: white; padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; }
        .destination-status-badge { position: absolute; top: 15px; left: 15px; padding: 6px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; display: flex; align-items: center; gap: 5px; }
        .badge-pending { background: rgba(245, 158, 11, 0.85); color: #fff; }
        .badge-approved { background: rgba(16, 185, 129, 0.85); color: #fff; }
        .badge-rejected { background: rgba(239, 68, 68, 0.85); color: #fff; }
        .destination-info { padding: 1.8rem; }
        .status-active { background: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.2); padding: 0.5rem 1rem; border-radius: 50px; font-weight: 700; }
    </style>
</head>
<body>

<nav class="navbar" role="navigation">
    <a href="../main/index.html" class="logo">
        <i class="fa-solid fa-paper-plane"></i> <span style="color: var(--text-main);">Trip</span><span style="color: var(--secondary);">Mate</span>
    </a>
    <span class="nav-badge">Contributor</span>
    <ul class="nav-links">
        <a href="contributor_dashboard.php" class="nav-link"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="contributor_add_destination.php" class="nav-link active"><i class="fa-solid fa-plus"></i> Add Destination</a>
        <a href="contributor_wallet.php" class="nav-link"><i class="fa-solid fa-wallet"></i> Wallet</a>
        <a href="contributor_profile.php" class="nav-link"><i class="fa-solid fa-user"></i> Profile</a>
        <div class="nav-avatar" style="margin-left: 10px;">
            <?php if ($contributor_pic): ?><img src="<?= htmlspecialchars($contributor_pic) ?>"><?php else: ?><?= strtoupper(substr($contributor_name, 0, 1)) ?><?php endif; ?>
        </div>
        <span style="font-size: 0.9rem; font-weight: 600;"><?= htmlspecialchars($contributor_name) ?></span>
    </ul>
    <button class="theme-toggle" id="themeToggle"><i class="fas fa-moon"></i></button>
</nav>

<div class="page">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert <?= strpos($_SESSION['message'], 'Error') === false ? 'alert-success' : 'alert-danger' ?>">
            <i class="fas fa-<?= strpos($_SESSION['message'], 'Error') === false ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= $_SESSION['message'] ?>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['temp_destination'])): ?>
        <div style="margin: 2rem 0; text-align: center; display: flex; justify-content: center; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <button id="showAddFormBtn" class="btn btn-primary" style="font-size: 1.3rem; padding: 1rem 2.5rem; border-radius: 50px;">
                <i class="fas fa-edit"></i> Edit Destination Details
            </button>
            <a href="contributor_manage_flights.php" class="btn btn-flight" style="font-size: 1.3rem; padding: 1rem 2.5rem; border-radius: 50px; text-decoration: none;">
                Resume Flights & Hotels <i class="fas fa-arrow-right"></i>
            </a>
            <a href="?clear_draft=1" class="btn btn-danger" style="font-size: 1.3rem; padding: 1rem 2.5rem; border-radius: 50px; text-decoration: none;" onclick="return confirm('Are you sure you want to clear your current draft?')">
                <i class="fas fa-trash"></i> Clear Draft
            </a>
        </div>
    <?php else: ?>
        <div style="margin: 2rem 0; text-align: center;">
            <button id="showAddFormBtn" class="btn btn-primary" style="font-size: 1.3rem; padding: 1rem 2.5rem; border-radius: 50px;">
                <i class="fas fa-plus-circle"></i> Create New Destination Package
            </button>
        </div>
    <?php endif; ?>

    <div id="addFormContainer" class="widget-card" style="display: none; margin-bottom: 3rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--card-border); padding-bottom: 1rem;">
            <h2 style="font-size: 1.8rem; color: var(--text-main);"><span style="color: var(--primary);">Step 1:</span> Destination Details</h2>
            <button type="button" id="closeFormBtn" class="btn btn-outline" style="border-radius: 50px;"><i class="fas fa-times"></i> Close</button>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group"><label>Destination Name *</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($temp_dest['name'] ?? '') ?>" required></div>
                <div class="form-group"><label>Destination Type *</label>
                    <select name="type" class="form-control" required>
                        <option value="beach" <?= ($temp_dest['type'] ?? '') === 'beach' ? 'selected' : '' ?>>Beach</option>
                        <option value="mountain" <?= ($temp_dest['type'] ?? '') === 'mountain' ? 'selected' : '' ?>>Mountain</option>
                        <option value="city" <?= ($temp_dest['type'] ?? '') === 'city' ? 'selected' : '' ?>>City</option>
                        <option value="historical" <?= ($temp_dest['type'] ?? '') === 'historical' ? 'selected' : '' ?>>Historical</option>
                    </select>
                </div>
                <div class="form-group"><label>Best Season to Visit *</label>
                    <select name="season[]" class="form-control" multiple required style="height: 120px;">
                        <option value="winter" <?= in_array('winter', $temp_seasons) ? 'selected' : '' ?>>Winter</option>
                        <option value="summer" <?= in_array('summer', $temp_seasons) ? 'selected' : '' ?>>Summer</option>
                        <option value="spring" <?= in_array('spring', $temp_seasons) ? 'selected' : '' ?>>Spring</option>
                        <option value="autumn" <?= in_array('autumn', $temp_seasons) ? 'selected' : '' ?>>Autumn</option>
                    </select>
                </div>
                <div class="form-group"><label>Recommended For *</label>
                    <select name="people[]" class="form-control" multiple required style="height: 120px;">
                        <option value="1" <?= in_array('1', $temp_people) ? 'selected' : '' ?>>Solo (1)</option>
                        <option value="2" <?= in_array('2', $temp_people) ? 'selected' : '' ?>>Couples (2)</option>
                        <option value="3-5" <?= in_array('3-5', $temp_people) ? 'selected' : '' ?>>Small Groups (3-5)</option>
                        <option value="9+" <?= in_array('9+', $temp_people) ? 'selected' : '' ?>>Large Groups (9+)</option>
                    </select>
                </div>
                <div class="form-group" style="grid-column: span 2;"><label>Description *</label><textarea name="description" class="form-control" required><?= htmlspecialchars($temp_dest['description'] ?? '') ?></textarea></div>
                <div class="form-group"><label>Location *</label><input type="text" name="location" class="form-control" value="<?= htmlspecialchars($temp_dest['location'] ?? '') ?>" required></div>
                <div class="form-group"><label>Budget (₹ per day) *</label><input type="number" name="budget" step="0.01" class="form-control" value="<?= htmlspecialchars($temp_dest['budget'] ?? '') ?>" required></div>
                <div class="form-group">
                    <label>Upload Destination Images *</label>
                    <?php if (!empty($temp_dest['image_urls'])): 
                        $imgs = json_decode($temp_dest['image_urls'], true) ?: [];
                        if (!empty($imgs)):
                    ?>
                        <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                            <?php foreach ($imgs as $img): ?>
                                <img src="../<?= htmlspecialchars($img) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; endif; ?>
                    <input type="file" name="images[]" class="form-control" multiple accept="image/*" style="padding: 0.6rem;" <?= isset($_SESSION['temp_destination']) ? '' : 'required' ?>>
                </div>
                <div class="form-group"><label>Google Map Link *</label><input type="url" name="map_link" class="form-control" value="<?= htmlspecialchars($temp_dest['map_link'] ?? '') ?>" required></div>
            </div>

            <div style="margin-top: 2rem; border-top: 1px solid var(--card-border); padding-top: 2rem; text-align: right;">
                <button type="submit" class="btn btn-flight" style="border-radius: 50px; font-size: 1.1rem; padding: 1rem 2rem;">
                    Save & Continue to Step 2 (Flights) <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </form>
    </div>

    <div class="widget-card" style="margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--card-border); padding-bottom: 1rem;">
            <h2 style="font-size: 1.8rem; margin: 0;"><i class="fas fa-list" style="color: var(--secondary);"></i> My Packages</h2>
            <span class="status-active">Total: <?= $dest_count ?></span>
        </div>
        <div class="card-body">
            <div class="destination-grid">
                <?php while ($row = $dest_result->fetch_assoc()): $status = $row['submission_status'] ?? 'pending'; ?>
                    <div class="destination-card widget-card" style="padding: 0; overflow: hidden;">
                        <div class="destination-images">
                            <?php $images = !empty($row['image_urls']) ? json_decode($row['image_urls'], true) : []; ?>
                            <img src="../<?= htmlspecialchars($images[0] ?? 'image/no-image.jpg') ?>">
                            <div class="destination-type-badge"><i class="fas fa-tag"></i> <?= ucfirst($row['type']) ?></div>
                            <div class="destination-status-badge badge-<?= $status ?>"><i class="fas fa-clock"></i> <?= ucfirst($status) ?></div>
                        </div>
                        <div class="destination-info">
                            <h3 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 1rem;"><?= htmlspecialchars($row['name']) ?></h3>
                            <div style="display: flex; gap: 0.5rem; border-top: 1px solid var(--card-border); padding-top: 1.25rem;">
                            <a href="contributor_edit_destination.php?id=<?= $row['id'] ?>" class="btn btn-outline" style="flex: 1; border-radius: 50px; font-size: 0.9rem; padding: 0.6rem ;"><i class="fas fa-edit"></i> edit details</a>                           
                            <a href="contributor_manage_flights.php?destination_id=<?= $row['id'] ?>" class="btn btn-flight" style="flex: 1; border-radius: 50px; font-size: 0.9rem; padding: 0.6rem;"><i class="fas fa-plane"></i> Flights</a>
                            <a href="contributor_manage_hotels.php?destination_id=<?= $row['id'] ?>" class="btn btn-hotel" style="flex: 1; border-radius: 50px; font-size: 0.9rem; padding: 0.6rem;"><i class="fas fa-hotel"></i> Hotels</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

<script>
const toggleBtn = document.getElementById('themeToggle'), icon = toggleBtn.querySelector('i');
if (localStorage.getItem('tripmate-theme') === 'dark') { document.body.classList.add('dark-mode'); icon.className = 'fas fa-sun'; }
toggleBtn.addEventListener('click', () => {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
    localStorage.setItem('tripmate-theme', isDark ? 'dark' : 'light');
});

const showBtn = document.getElementById('showAddFormBtn'), formContainer = document.getElementById('addFormContainer');
showBtn.addEventListener('click', () => { formContainer.style.display = 'block'; showBtn.style.display = 'none'; formContainer.scrollIntoView({behavior:'smooth'}); });
document.getElementById('closeFormBtn').addEventListener('click', () => { formContainer.style.display = 'none'; showBtn.style.display = 'inline-flex'; });
</script>
</body>
</html>