<?php
session_start();
/*
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}
*/
include '../database/dbconfig.php';

// Get destination ID
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    $_SESSION['message'] = "Invalid or missing destination ID";
    header("Location: admin.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM destinations WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "Destination not found";
    header("Location: admin.php");
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

    // Images: keep old ones if no new upload
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
        $_SESSION['message'] = "Destination updated successfully!";
        header("Location: admin.php");
        exit();
    } else {
        $_SESSION['message'] = "Error: " . $conn->error;
    }
}
?>

<?php include 'admin_header.php'; ?>

<!-- Main Content - same wrapper as add page -->
<div class="main-content">

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success fade-in">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['message']) ?>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- Card - same as add page -->
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
                    <small style="color: var(--accent);">Hold Ctrl/Cmd to select multiple options</small>
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
                    <small style="color: var(--accent);">Hold Ctrl/Cmd to select multiple options</small>
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
                            echo "<p style='color:#777;'>No images yet</p>";
                        endif;
                        ?>
                    </div>

                    <label for="images">Upload Images (replaces old ones)</label>
                    <input type="file" id="images" name="images[]" class="form-control" multiple accept="image/*">
                    <small style="color: var(--accent);">Select new images to replace existing ones</small>
                </div>

                <div class="form-group">
                    <label for="map_link">Google Map Link</label>
                    <input type="url" id="map_link" name="map_link" class="form-control" 
                           value="<?= htmlspecialchars($destination['map_link'] ?? '') ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Destination
                </button>

                <a href="add_destanition_on_admin.php" class="btn btn-outline" style="margin-left: 1rem;">
                    <i class="fas fa-arrow-left"></i> Back
                </a>

            </form>
        </div>
    </div>

</div>

<?php include 'admin_footer.php'; ?>


  <style>
        /* Additional Styles from admin.php for Form and Destination Cards */
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            border-top: 3px solid var(--accent);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
        }
        
        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(to right, rgba(22,3,79,0.03), white);
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
            color: var(--primary);
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,194,203,0.2);
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
            background: linear-gradient(135deg, var(--primary), #1a5276);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #1a5276, var(--primary));
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(22,3,79,0.3);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border-left: 4px solid transparent;
        }
        
        .alert-success {
            background-color: #f0fff4;
            color: #2f855a;
            border-left-color: #38a169;
        }
        
        .destination-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
        }
        
        .destination-card {
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s;
            background: white;
        }
        
        .destination-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .destination-images {
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        
        .destination-images img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .destination-card:hover .destination-images img {
            transform: scale(1.05);
        }
        
        .destination-info {
            padding: 1.5rem;
        }
        
        .destination-info h3 {
            margin-top: 0;
            margin-bottom: 0.8rem;
            color: var(--primary);
        }
        
        .destination-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }
        
        .destination-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #555;
        }
        
        .destination-meta i {
            color: var(--accent);
        }
        
        .destination-actions {
            display: flex;
            gap: 0.8rem;
            margin-top: 1.2rem;
        }
        
        .btn-sm {
            padding: 0.6rem 1.2rem;
            font-size: 0.9rem;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background: rgba(22,3,79,0.05);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #e53e3e, #c53030);
            color: white;
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #c53030, #e53e3e);
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-active {
            background: #c6f6d5;
            color: #2f855a;
        }
        
        /* Animation for new elements */
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