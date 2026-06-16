<?php
session_start();

$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_contributor = isset($_SESSION['contributor_id']);

if (!$is_admin && !$is_contributor) {
    header('Location: admin_login.php');
    exit();
}

$back_url = $is_contributor ? '../Contributor/contributor_add_destination.php' : 'add_destination_on_admin.php';

include '../database/dbconfig.php';

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    $_SESSION['message'] = "Invalid or missing destination ID";
    header("Location: add_destination_on_admin.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM destinations WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "Destination not found";
    header("Location: add_destination_on_admin.php");
    exit();
}

$destination = $result->fetch_assoc();

$selected_seasons = array_filter(explode(',', $destination['season'] ?? ''));
$selected_people  = array_filter(explode(',', $destination['people'] ?? ''));

// Handle form submission (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = $conn->real_escape_string(trim($_POST['name']));
    $type        = $conn->real_escape_string($_POST['type']);
    $description = $conn->real_escape_string(trim($_POST['description']));
    $location    = $conn->real_escape_string(trim($_POST['location']));
    $budget      = (float)$_POST['budget'];
    $map_link    = $conn->real_escape_string(trim($_POST['map_link']));

    $season = !empty($_POST['season']) ? implode(',', array_map([$conn, 'real_escape_string'], $_POST['season'])) : '';
    $people = !empty($_POST['people']) ? implode(',', array_map([$conn, 'real_escape_string'], $_POST['people'])) : '';

    $image_urls = json_decode($destination['image_urls'] ?? '[]', true) ?: [];

    if (!empty($_FILES['images']['name'][0])) {
        $upload_dir = 'uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === 0) {
                $file_name = basename($_FILES['images']['name'][$key]);
                $file_path = $upload_dir . uniqid() . '_' . $file_name;
                if (move_uploaded_file($tmp_name, $file_path)) {
                    $image_urls[] = $file_path;
                }
            }
        }
    }

    $image_urls_json = json_encode($image_urls);

    $update_stmt = $conn->prepare("
        UPDATE destinations 
        SET name = ?, type = ?, description = ?, location = ?, budget = ?, 
            image_urls = ?, map_link = ?, season = ?, people = ?
        WHERE id = ?
    ");

    $update_stmt->bind_param("ssssdssssi", 
        $name, $type, $description, $location, $budget, 
        $image_urls_json, $map_link, $season, $people, $id
    );

    if ($update_stmt->execute()) {
        // Update destination city name if the destination was updated
        $city_update = $conn->prepare("UPDATE destination_cities SET city_name = ? WHERE id = ?");
        if ($city_update) {
            $city_update->bind_param("si", $name, $id);
            $city_update->execute();
            $city_update->close();
        }
        $_SESSION['message'] = "Destination updated successfully!";
        header("Location: " . $back_url);
        exit();
    } else {
        $_SESSION['message'] = "Error: " . $conn->error;
    }
}
?>

<?php if ($is_admin): ?>
<?php include 'admin_header.php'; ?>
<?php else: ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Destination – TripMate Contributor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --bg-base: #f8fafc; --bg-surface: #ffffff; --text-main: #0f172a; --text-muted: #475569; --primary: #4f46e5; --secondary: #06b6d4; --nav-bg: rgba(255,255,255,0.75); --card-border: rgba(79,70,229,0.15); --shadow-color: rgba(15,23,42,0.08); --glow-color: rgba(6,182,212,0.4); --danger: #ef4444; --success: #10b981; --accent: var(--secondary); }
        body.dark-mode { --bg-base: #09090b; --bg-surface: #18181b; --text-main: #f8fafc; --text-muted: #cbd5e1; --primary: #818cf8; --secondary: #22d3ee; --nav-bg: rgba(24,24,27,0.75); --card-border: rgba(255,255,255,0.1); --shadow-color: rgba(0,0,0,0.6); --glow-color: rgba(34,211,238,0.3); }
        * { margin: 0; padding: 0; box-sizing: border-box; } body { font-family: 'Inter', sans-serif; background: var(--bg-base); color: var(--text-main); padding-top: 120px; transition: background 0.4s, color 0.4s; }
        body::before { content: ''; position: fixed; top: -10%; left: -10%; width: 50vw; height: 50vw; background: radial-gradient(circle, var(--glow-color) 0%, transparent 60%); opacity: 0.5; pointer-events: none; }
        .navbar { position: fixed; top: 20px; left: 5%; width: 90%; height: 70px; background: var(--nav-bg); backdrop-filter: blur(20px); border: 1px solid var(--card-border); border-radius: 50px; display: flex; justify-content: space-between; align-items: center; padding: 0 30px; z-index: 1000; box-shadow: 0 10px 30px var(--shadow-color); }
        .logo { display: flex; align-items: center; gap: 12px; font-size: 1.4rem; font-weight: 800; text-decoration: none; color: var(--text-main); } .logo i { color: var(--primary); } .brand-text .trip { color: var(--text-main); } .brand-text .mate { color: var(--secondary); }
        .nav-badge { background: var(--glow-color); color: var(--primary); padding: 4px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; border: 1px solid var(--card-border); margin-left: 15px; }
        .nav-links { display: flex; align-items: center; gap: 18px; list-style: none; margin-left: auto; margin-right: 20px; } .nav-link { color: var(--text-muted); text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 6px; font-size: 0.95rem; transition: color 0.2s; } .nav-link:hover { color: var(--secondary); } .nav-link.active { color: var(--primary); }
        .theme-toggle { background: var(--bg-surface); border: 1px solid var(--card-border); color: var(--text-main); width: 44px; height: 44px; border-radius: 50%; cursor: pointer; display: flex; justify-content: center; align-items: center; transition: all 0.3s; box-shadow: 0 4px 10px var(--shadow-color); flex-shrink: 0; } .theme-toggle:hover { transform: rotate(20deg) scale(1.1); color: var(--primary); }
        .main-content { max-width: 900px; margin: 0 auto; padding: 0 1.5rem 3rem; position: relative; z-index: 1; }
        @media (max-width: 900px) { .navbar { width: 95%; left: 2.5%; } .nav-links { display: none; } .nav-badge { display: none; } }
    </style>
</head>
<body>
<nav class="navbar">
    <a href="../main/index.html" class="logo"><i class="fa-solid fa-paper-plane"></i><span class="brand-text"><span class="trip">Trip</span><span class="mate">Mate</span></span></a>
    <span class="nav-badge">Contributor</span>
    <ul class="nav-links">
        <a href="../Contributor/contributor_dashboard.php" class="nav-link"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="../Contributor/contributor_add_destination.php" class="nav-link active"><i class="fa-solid fa-plus"></i> Destinations</a>
        <a href="../Contributor/contributor_wallet.php" class="nav-link"><i class="fa-solid fa-wallet"></i> Wallet</a>
        <a href="../Contributor/contributor_profile.php" class="nav-link"><i class="fa-solid fa-user"></i> Profile</a>
        <a href="../Contributor/contributor_logout.php" class="nav-link" style="color:var(--danger);"><i class="fa-solid fa-right-from-bracket"></i></a>
    </ul>
    <button class="theme-toggle" id="themeToggle"><i class="fas fa-moon"></i></button>
</nav>
<script>
(function(){
    const t=document.getElementById('themeToggle'),i=t.querySelector('i');
    if(localStorage.getItem('tripmate-theme')==='dark'){document.body.classList.add('dark-mode');i.className='fas fa-sun';}
    t.addEventListener('click',()=>{document.body.classList.toggle('dark-mode');const d=document.body.classList.contains('dark-mode');i.className=d?'fas fa-sun':'fas fa-moon';localStorage.setItem('tripmate-theme',d?'dark':'light');});
})();
</script>
<?php endif; ?>

<div class="main-content">

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success fade-in">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['message']) ?>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <div class="card fade-in">
        <div class="card-header">
            <h2><i class="fas fa-edit"></i> Edit Destination</h2>
        </div>
        <div class="card-body">

            <form method="POST" enctype="multipart/form-data">

                <div class="form-group">
                    <label for="name">Destination Name</label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?= htmlspecialchars($destination['name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="type">Destination Type</label>
                    <select id="type" name="type" class="form-control" required>
                        <option value="beach"    <?= ($destination['type'] ?? '') === 'beach'    ? 'selected' : '' ?>>Beach</option>
                        <option value="mountain" <?= ($destination['type'] ?? '') === 'mountain' ? 'selected' : '' ?>>Mountain</option>
                        <option value="city"     <?= ($destination['type'] ?? '') === 'city'     ? 'selected' : '' ?>>City</option>
                        <option value="village"  <?= ($destination['type'] ?? '') === 'village'  ? 'selected' : '' ?>>Village</option>
                        <option value="historical" <?= ($destination['type'] ?? '') === 'historical' ? 'selected' : '' ?>>Historical</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="season">Best Season to Visit</label>
                    <select id="season" name="season[]" class="form-control" multiple required>
                        <option value="winter"  <?= in_array('winter',  $selected_seasons) ? 'selected' : '' ?>>Winter</option>
                        <option value="summer"  <?= in_array('summer',  $selected_seasons) ? 'selected' : '' ?>>Summer</option>
                        <option value="spring"  <?= in_array('spring',  $selected_seasons) ? 'selected' : '' ?>>Spring</option>
                        <option value="autumn"  <?= in_array('autumn',  $selected_seasons) ? 'selected' : '' ?>>Autumn</option>
                        <option value="monsoon" <?= in_array('monsoon', $selected_seasons) ? 'selected' : '' ?>>Monsoon</option>
                    </select>
                    <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">Hold Ctrl/Cmd to select multiple options</small>
                </div>

                <div class="form-group">
                    <label for="people">Recommended For</label>
                    <select id="people" name="people[]" class="form-control" multiple required>
                        <option value="1"   <?= in_array('1',   $selected_people) ? 'selected' : '' ?>>Solo (1)</option>
                        <option value="2"   <?= in_array('2',   $selected_people) ? 'selected' : '' ?>>Couples (2)</option>
                        <option value="3-5" <?= in_array('3-5', $selected_people) ? 'selected' : '' ?>>Small Groups (3-5)</option>
                        <option value="6-9" <?= in_array('6-9', $selected_people) ? 'selected' : '' ?>>Medium Groups (6-9)</option>
                        <option value="9+"  <?= in_array('9+',  $selected_people) ? 'selected' : '' ?>>Large Groups (9+)</option>
                    </select>
                    <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">Hold Ctrl/Cmd to select multiple options</small>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" required><?= htmlspecialchars($destination['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" class="form-control" 
                           value="<?= htmlspecialchars($destination['location'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="budget">Budget (₹ per day)</label>
                    <input type="number" id="budget" name="budget" step="0.01" class="form-control" 
                           value="<?= htmlspecialchars($destination['budget'] ?? '0') ?>" required>
                </div>

                <div class="form-group">
                    <label>Current Images</label>
                    <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 1rem;">
                        <?php
                        $images = json_decode($destination['image_urls'] ?? '[]', true);
                        if (is_array($images) && !empty($images)):
                            foreach ($images as $img):
                        ?>
                            <img src="<?= htmlspecialchars($img) ?>" alt="Current image" 
                                 style="max-width: 180px; height: auto; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <?php
                            endforeach;
                        else:
                            echo "<p style='color: var(--text-muted);'>No images yet</p>";
                        endif;
                        ?>
                    </div>

                    <label for="images">Upload Images (replaces old ones)</label>
                    <input type="file" id="images" name="images[]" class="form-control" multiple accept="image/*">
                    <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">Select new images to replace existing ones</small>
                </div>

                <div class="form-group">
                    <label for="map_link">Google Map Link</label>
                    <input type="url" id="map_link" name="map_link" class="form-control" 
                           value="<?= htmlspecialchars($destination['map_link'] ?? '') ?>" required>
                </div>

                <button type="submit" class="btn btn-primary" style="color: white;">
                    <i class="fas fa-save"></i> Update Destination
                </button>

                <a href="<?= $back_url ?>" class="btn btn-outline" style="margin-left: 1rem;">
                    <i class="fas fa-arrow-left"></i> Back
                </a>

            </form>
        </div>
    </div>

</div>

<?php if ($is_admin): ?>
<?php include 'admin_footer.php'; ?>
<?php else: ?>
</body></html>
<?php endif; ?>


<style>
    .card {
        background: var(--bg-surface);
        border-radius: 10px;
        box-shadow: 0 3px 20px var(--shadow-color);
        margin-bottom: 2rem;
        overflow: hidden;
        transition: transform 0.3s, box-shadow 0.3s, background 0.3s;
        border: 1px solid var(--card-border);
        border-top: 3px solid var(--accent);
    }
    
    .card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--card-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--bg-surface);
    }
    
    .card-header h2 {
        margin: 0;
        font-size: 1.4rem;
        color: var(--primary);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .card-header h2 i {
        color: var(--secondary);
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--text-main);
    }
    
    /* ADDED BOX-SIZING SO INPUTS DON'T OVERFLOW, EXPLICIT COLORS */
    .form-control {
        box-sizing: border-box;
        width: 100%;
        padding: 0.8rem;
        border: 1px solid var(--card-border);
        border-radius: 6px;
        font-size: 1rem;
        background-color: var(--bg-base, #f8fafc);
        color: var(--text-main, #0f172a);
        transition: all 0.3s;
    }
    
    .form-control:focus {
        border-color: var(--accent);
        outline: none;
        box-shadow: 0 0 0 3px var(--glow-color);
        background-color: var(--bg-surface, #ffffff);
    }
    
    select.form-control option {
        padding: 0.5rem;
        background-color: var(--bg-surface, #ffffff);
        color: var(--text-main, #0f172a);
    }
    
    textarea.form-control {
        min-height: 120px;
        resize: vertical;
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.8rem 1.8rem;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        border: none;
        gap: 10px;
        font-size: 1rem;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, var(--secondary), var(--primary));
        transform: translateY(-3px);
        box-shadow: 0 5px 15px var(--glow-color);
    }
    
    .btn-outline {
        background: transparent;
        border: 2px solid var(--primary);
        color: var(--text-main);
    }
    
    .btn-outline:hover {
        background: rgba(79, 70, 229, 0.1);
        color: var(--primary);
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .fade-in {
        animation: fadeIn 0.5s ease-out;
    }
</style>
<script>
let startTime = Date.now();
let clickCount = 0;

document.addEventListener('click', () => { clickCount++; });

window.addEventListener('beforeunload', () => {
  const endTime = Date.now();
  const timeSpent = Math.round((endTime - startTime) / 1000);
  const pageName = window.location.pathname.split('/').pop();

  navigator.sendBeacon('../backend/page_activity.php', 
    new URLSearchParams({
      page_name: pageName,
      time_spent: timeSpent,
      click_count: clickCount
    })
  );
});
</script>