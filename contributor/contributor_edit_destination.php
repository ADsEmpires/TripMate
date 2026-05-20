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

// Get the destination ID from the URL
$dest_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($dest_id === 0) {
    $_SESSION['message'] = "Invalid destination ID.";
    header("Location: contributor_add_destination.php");
    exit();
}

// Fetch the destination AND ensure it belongs to this contributor
$stmt = $conn->prepare("SELECT * FROM destinations WHERE id = ? AND contributor_id = ?");
$stmt->bind_param("ii", $dest_id, $contributor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "Destination not found or you do not have permission to edit it.";
    header("Location: contributor_add_destination.php");
    exit();
}

$dest = $result->fetch_assoc();
$current_seasons = explode(',', $dest['season'] ?? '');
$current_people = json_decode($dest['people'] ?? '[]', true);
if (!is_array($current_people)) $current_people = [];

// Handle Form Submission
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
    
    // Image Handling - Keep old images if no new ones are uploaded
    $image_urls_json = $dest['image_urls']; 
    
    if (!empty($_FILES['images']['name'][0])) {
        $image_urls = [];
        $upload_dir = '../admin/uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === 0) {
                $file_name = basename($_FILES['images']['name'][$key]);
                $file_path = 'uploads/' . uniqid() . '_' . $file_name;
                $full_path = '../admin/' . $file_path;
                if (move_uploaded_file($tmp_name, $full_path)) {
                    $image_urls[] = $file_path;
                }
            }
        }
        if (!empty($image_urls)) {
            $image_urls_json = json_encode($image_urls);
            
            // Optionally delete old images from server here if desired
        }
    }

    // Update query - Sets status back to pending for admin review
    $update_query = "UPDATE destinations SET name=?, type=?, description=?, location=?, budget=?, image_urls=?, map_link=?, season=?, people=?, submission_status='pending' WHERE id=? AND contributor_id=?";
    $update_stmt = $conn->prepare($update_query);
    
    if ($update_stmt) {
        $update_stmt->bind_param("ssssdssssii", $name, $type, $description, $location, $budget, $image_urls_json, $map_link, $season, $people, $dest_id, $contributor_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['message'] = "Destination updated successfully! It is now pending admin review again.";
            header("Location: contributor_add_destination.php");
            exit();
        } else {
            $error_message = "Error updating destination: " . $update_stmt->error;
        }
        $update_stmt->close();
    } else {
        $error_message = "Database preparation error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Destination – TripMate Contributor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
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
        
        .form-control { background-color: var(--bg-base); color: var(--text-main); border: 1px solid var(--card-border); }
        body.dark-mode .form-control { background-color: var(--bg-surface); color: var(--text-main); border-color: var(--card-border); }
        body.dark-mode select.form-control option { background-color: #18181b; color: #f8fafc; }
        body.dark-mode h2, body.dark-mode label { color: var(--text-main) !important; }

        .navbar { position: fixed; top: 20px; left: 5%; width: 90%; height: 70px; background: var(--nav-bg); backdrop-filter: blur(20px); border: 1px solid var(--card-border); border-radius: 50px; display: flex; justify-content: space-between; align-items: center; padding: 0 30px; z-index: 1000; box-shadow: 0 10px 30px var(--shadow-color); }
        .logo { display: flex; align-items: center; gap: 12px; font-size: 1.4rem; font-weight: 800; text-decoration: none; color: var(--text-main); }
        .logo i { color: var(--primary); font-size: 1.5rem; transform: rotate(-10deg); }
        .nav-badge { background: var(--glow-color); color: var(--primary); padding: 4px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; border: 1px solid var(--card-border); margin-left: 15px; }
        .nav-links { display: flex; align-items: center; gap: 18px; list-style: none; margin-left: auto; margin-right: 20px; }
        .nav-link { color: var(--text-muted); text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 6px; font-size: 0.95rem; }
        .nav-link.active { color: var(--primary); }
        .theme-toggle { background: var(--bg-surface); border: 1px solid var(--card-border); color: var(--text-main); width: 44px; height: 44px; border-radius: 50%; cursor: pointer; display: flex; justify-content: center; align-items: center; box-shadow: 0 4px 10px var(--shadow-color); }

        .page { max-width: 1200px; margin: 0 auto; padding: 0 1.5rem 2rem; position: relative; z-index: 1; }
        .widget-card { background: var(--bg-surface); border: 1px solid var(--card-border); border-radius: 24px; padding: 2rem; box-shadow: 0 15px 35px var(--shadow-color); }
        .form-group { margin-bottom: 1.8rem; }
        .form-group label { display: block; margin-bottom: 0.6rem; font-weight: 700; font-size: 0.95rem; }
        .form-control { width: 100%; padding: 0.9rem 1.2rem; border-radius: 12px; font-size: 1rem; transition: all 0.3s; font-family: inherit; }
        .form-control:focus { border-color: var(--secondary); outline: none; box-shadow: 0 0 0 4px var(--glow-color); }
        textarea.form-control { min-height: 150px; resize: vertical; line-height: 1.6; }

        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.8rem 1.8rem; border-radius: 50px; font-weight: 700; cursor: pointer; transition: all 0.3s; border: none; gap: 8px; font-size: 1rem; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; }
        .btn-outline { background: transparent; border: 2px solid var(--card-border); color: var(--text-main); }
        
        .alert { padding: 1.2rem 1.5rem; border-radius: 16px; margin-bottom: 2rem; font-weight: 600; display: flex; align-items: center; gap: 12px; }
        .alert-danger { background-color: var(--bg-surface); color: var(--danger); border: 1px solid var(--card-border); border-left: 5px solid var(--danger); }
        
        footer { text-align: center; padding: 2rem; font-size: 0.85rem; color: var(--text-muted); border-top: 1px solid var(--card-border); margin-top: 3rem; }
    </style>
</head>
<body>

<nav class="navbar" role="navigation">
    <a href="../main/index.html" class="logo">
        <i class="fa-solid fa-paper-plane"></i>
        <span style="color: var(--text-main);">Trip</span><span style="color: var(--secondary);">Mate</span>
    </a>
    <span class="nav-badge">Contributor</span>
    <ul class="nav-links">
        <a href="contributor_dashboard.php" class="nav-link"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="contributor_add_destination.php" class="nav-link active"><i class="fa-solid fa-map-marked-alt"></i> Destinations</a>
        <a href="contributor_wallet.php" class="nav-link"><i class="fa-solid fa-wallet"></i> Wallet</a>
        <a href="contributor_profile.php" class="nav-link"><i class="fa-solid fa-user"></i> Profile</a>
        <a href="contributor_logout.php" class="nav-link" style="color: var(--danger); margin-left: 10px;"><i class="fa-solid fa-right-from-bracket"></i></a>
    </ul>
    <button class="theme-toggle" id="themeToggle"><i class="fas fa-moon"></i></button>
</nav>

<div class="page">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1 style="font-size: 2.2rem; color: var(--text-main);"><i class="fas fa-edit" style="color: var(--secondary);"></i> Edit Destination</h1>
        <a href="contributor_add_destination.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error_message ?></div>
    <?php endif; ?>

    <div class="widget-card">
        <form method="POST" enctype="multipart/form-data">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label>Destination Name *</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($dest['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Destination Type *</label>
                    <select name="type" class="form-control" required>
                        <option value="beach" <?= $dest['type'] === 'beach' ? 'selected' : '' ?>>Beach</option>
                        <option value="mountain" <?= $dest['type'] === 'mountain' ? 'selected' : '' ?>>Mountain</option>
                        <option value="city" <?= $dest['type'] === 'city' ? 'selected' : '' ?>>City</option>
                        <option value="village" <?= $dest['type'] === 'village' ? 'selected' : '' ?>>Village</option>
                        <option value="historical" <?= $dest['type'] === 'historical' ? 'selected' : '' ?>>Historical</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Best Season to Visit *</label>
                    <select name="season[]" class="form-control" multiple required style="height: 120px;">
                        <option value="winter" <?= in_array('winter', $current_seasons) ? 'selected' : '' ?>>Winter</option>
                        <option value="summer" <?= in_array('summer', $current_seasons) ? 'selected' : '' ?>>Summer</option>
                        <option value="spring" <?= in_array('spring', $current_seasons) ? 'selected' : '' ?>>Spring</option>
                        <option value="autumn" <?= in_array('autumn', $current_seasons) ? 'selected' : '' ?>>Autumn</option>
                        <option value="monsoon" <?= in_array('monsoon', $current_seasons) ? 'selected' : '' ?>>Monsoon</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Recommended For *</label>
                    <select name="people[]" class="form-control" multiple required style="height: 120px;">
                        <option value="1" <?= in_array('1', $current_people) ? 'selected' : '' ?>>Solo (1)</option>
                        <option value="2" <?= in_array('2', $current_people) ? 'selected' : '' ?>>Couples (2)</option>
                        <option value="3-5" <?= in_array('3-5', $current_people) ? 'selected' : '' ?>>Small Groups (3-5)</option>
                        <option value="6-9" <?= in_array('6-9', $current_people) ? 'selected' : '' ?>>Medium Groups (6-9)</option>
                        <option value="9+" <?= in_array('9+', $current_people) ? 'selected' : '' ?>>Large Groups (9+)</option>
                    </select>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Description *</label>
                    <textarea name="description" class="form-control" required><?= htmlspecialchars($dest['description']) ?></textarea>
                </div>
                <div class="form-group">
                    <label>Location *</label>
                    <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($dest['location']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Budget (₹ per day) *</label>
                    <input type="number" name="budget" step="0.01" class="form-control" value="<?= htmlspecialchars($dest['budget']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Upload Images (Leave blank to keep existing)</label>
                    <input type="file" name="images[]" class="form-control" multiple accept="image/*" style="padding: 0.6rem;">
                </div>
                <div class="form-group">
                    <label>Google Map Link *</label>
                    <input type="url" name="map_link" class="form-control" value="<?= htmlspecialchars($dest['map_link']) ?>" required>
                </div>
            </div>

            <div style="margin-top: 2rem; text-align: right; border-top: 1px solid var(--card-border); padding-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="padding: 1rem 3rem; font-size: 1.1rem;">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<footer>© <?= date('Y') ?> TripMate · Contributor Portal</footer>

<script>
const t=document.getElementById('themeToggle'), i=t.querySelector('i');
if(localStorage.getItem('tripmate-theme')==='dark'){document.body.classList.add('dark-mode');i.className='fas fa-sun';}
t.addEventListener('click',()=>{document.body.classList.toggle('dark-mode');const d=document.body.classList.contains('dark-mode');i.className=d?'fas fa-sun':'fas fa-moon';localStorage.setItem('tripmate-theme',d?'dark':'light');});
</script>
</body>
</html>