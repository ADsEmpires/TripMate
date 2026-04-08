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
$selected_people  = json_decode($destination['people'] ?? '[]', true) ?: [];
$selected_cuisines = json_decode($destination['cuisines'] ?? '[]', true) ?: [];
$cuisine_images_from_db = json_decode($destination['cuisine_images'] ?? '{}', true) ?: [];

// Handle form submission (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = $conn->real_escape_string(trim($_POST['name']));
    $type        = $conn->real_escape_string($_POST['type']);
    $description = $conn->real_escape_string(trim($_POST['description']));
    $location    = $conn->real_escape_string(trim($_POST['location']));
    $budget      = (float)$_POST['budget'];
    $map_link    = $conn->real_escape_string(trim($_POST['map_link']));

    $season = !empty($_POST['season']) ? implode(',', array_map([$conn, 'real_escape_string'], $_POST['season'])) : '';
    $people_json = isset($_POST['people']) ? json_encode($_POST['people']) : '[]';

    // Images: keep old ones if no new upload
    $image_urls = json_decode($destination['image_urls'] ?? '[]', true) ?: [];

    if (!empty($_FILES['images']['name'][0])) {
        $upload_dir = '../uploads/destinations/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $file_name = basename($_FILES['images']['name'][$key]);
                // Sanitize filename
                $safe_filename = preg_replace("/[^a-zA-Z0-9.-]/", "_", $file_name);
                $unique_file_name = uniqid() . '_' . $safe_filename;
                $file_path = $upload_dir . $unique_file_name;
                
                if (move_uploaded_file($tmp_name, $file_path)) {
                    // Store relative path from uploads folder
                    $image_urls[] = 'destinations/' . $unique_file_name;
                }
            }
        }
    }

    $image_urls_json = json_encode($image_urls);

    // Handle cuisine images
    $final_cuisine_images = [];
    $cuisines = isset($_POST['cuisines']) ? $_POST['cuisines'] : [];
    
    if (isset($_FILES['cuisine_images']) && is_array($_FILES['cuisine_images']['name'])) {
        $upload_dir_cuisine = '../uploads/cuisines/';
        if (!file_exists($upload_dir_cuisine)) {
            mkdir($upload_dir_cuisine, 0777, true);
        }

        foreach ($_FILES['cuisine_images']['tmp_name'] as $cuisine_index => $tmp_name) {
            if (!empty($tmp_name) && $_FILES['cuisine_images']['error'][$cuisine_index] === UPLOAD_ERR_OK) {
                $file_name = $_FILES['cuisine_images']['name'][$cuisine_index];
                // Sanitize filename
                $safe_filename = preg_replace("/[^a-zA-Z0-9.-]/", "_", $file_name);
                $unique_file_name = uniqid() . '_' . $safe_filename;
                $file_path = $upload_dir_cuisine . $unique_file_name;

                if (move_uploaded_file($tmp_name, $file_path)) {
                    // Map cuisine name to relative path from uploads folder
                    if (isset($cuisines[$cuisine_index])) {
                        $cuisine_name = $cuisines[$cuisine_index];
                        $final_cuisine_images[$cuisine_name] = 'cuisines/' . $unique_file_name;
                    }
                }
            } else {
                // Keep existing image if no new upload for this cuisine
                if (isset($cuisines[$cuisine_index])) {
                    $cuisine_name = $cuisines[$cuisine_index];
                    if (isset($cuisine_images_from_db[$cuisine_name])) {
                        $final_cuisine_images[$cuisine_name] = $cuisine_images_from_db[$cuisine_name];
                    }
                }
            }
        }
    } else {
        // If no cuisine images uploaded, keep existing ones
        foreach ($cuisines as $cuisine_name) {
            if (isset($cuisine_images_from_db[$cuisine_name])) {
                $final_cuisine_images[$cuisine_name] = $cuisine_images_from_db[$cuisine_name];
            }
        }
    }

    $cuisines_json = json_encode($cuisines);
    $cuisine_images_json = json_encode($final_cuisine_images);

    // Handle tips and language if provided
    $tips = isset($_POST['tips']) ? json_encode($_POST['tips']) : '[]';
    $language = isset($_POST['language']) ? json_encode($_POST['language']) : '[]';

    // Handle attractions if provided
    $attractions_json = '[]';
    if (!empty($_POST['attractions'])) {
        $attractions_array = array_filter(array_map('trim', explode("\n", $_POST['attractions'])));
        if (!empty($attractions_array)) {
            $attractions_json = json_encode(array_values($attractions_array));
        }
    }

    $update_stmt = $conn->prepare("
        UPDATE destinations 
        SET name = ?, type = ?, description = ?, location = ?, budget = ?, 
            image_urls = ?, map_link = ?, season = ?, people = ?, 
            tips = ?, cuisines = ?, language = ?, cuisine_images = ?, attractions = ?
        WHERE id = ?
    ");

    $update_stmt->bind_param("ssssdsssssssssi", 
        $name, $type, $description, $location, $budget, 
        $image_urls_json, $map_link, $season, $people_json,
        $tips, $cuisines_json, $language, $cuisine_images_json, $attractions_json, $id
    );

    if ($update_stmt->execute()) {
        $_SESSION['message'] = "Destination updated successfully!";
        header("Location: add_destination_on_admin.php");
        exit();
    } else {
        $_SESSION['message'] = "Error: " . $conn->error;
    }
}
?>

<?php include 'admin_header.php'; ?>

<!-- Main Content -->
<div class="main-content">

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success fade-in">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['message']) ?>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- Card -->
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
                    <select id="people" name="people[]" class="form-control" multiple required style="height: auto; min-height: 120px;">
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
                    <label>Current Destination Images</label>
                    <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 1rem;">
                        <?php
                        $images = json_decode($destination['image_urls'] ?? '[]', true);
                        if (is_array($images) && !empty($images)):
                            foreach ($images as $img):
                                $image_path = '../uploads/' . $img;
                        ?>
                            <img src="<?= htmlspecialchars($image_path) ?>" alt="Current image" 
                                 style="max-width: 180px; height: auto; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <?php
                            endforeach;
                        else:
                            echo "<p style='color:#777;'>No images yet</p>";
                        endif;
                        ?>
                    </div>

                    <label for="images">Upload New Destination Images (adds to existing)</label>
                    <input type="file" id="images" name="images[]" class="form-control" multiple accept="image/*">
                    <small style="color: var(--accent);">Select new images to add to existing ones</small>
                </div>

                <div class="form-group">
                    <label for="cuisines">Local Cuisines</label>
                    <select id="cuisines" name="cuisines[]" class="form-control" multiple style="height: auto; min-height: 120px;">
                        <option value="Biryani" <?= in_array('Biryani', $selected_cuisines) ? 'selected' : '' ?>>Biryani</option>
                        <option value="Butter Chicken" <?= in_array('Butter Chicken', $selected_cuisines) ? 'selected' : '' ?>>Butter Chicken</option>
                        <option value="Paneer Tikka" <?= in_array('Paneer Tikka', $selected_cuisines) ? 'selected' : '' ?>>Paneer Tikka</option>
                        <option value="Masala Dosa" <?= in_array('Masala Dosa', $selected_cuisines) ? 'selected' : '' ?>>Masala Dosa</option>
                        <option value="Chole Bhature" <?= in_array('Chole Bhature', $selected_cuisines) ? 'selected' : '' ?>>Chole Bhature</option>
                        <option value="Rogan Josh" <?= in_array('Rogan Josh', $selected_cuisines) ? 'selected' : '' ?>>Rogan Josh</option>
                        <option value="Dal Makhani" <?= in_array('Dal Makhani', $selected_cuisines) ? 'selected' : '' ?>>Dal Makhani</option>
                        <option value="Tandoori Chicken" <?= in_array('Tandoori Chicken', $selected_cuisines) ? 'selected' : '' ?>>Tandoori Chicken</option>
                    </select>
                    <small style="color: var(--accent);">Hold Ctrl/Cmd to select multiple cuisines</small>
                </div>

                <div class="form-group">
                    <label>Current Cuisine Images</label>
                    <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 1rem;">
                        <?php
                        if (is_array($cuisine_images_from_db) && !empty($cuisine_images_from_db)):
                            foreach ($cuisine_images_from_db as $cuisine_name => $img):
                                $image_path = '../uploads/' . $img;
                        ?>
                            <div style="display: flex; flex-direction: column; align-items: center;">
                                <img src="<?= htmlspecialchars($image_path) ?>" alt="<?= htmlspecialchars($cuisine_name) ?>" 
                                     style="max-width: 120px; height: 120px; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); object-fit: cover;">
                                <small style="margin-top: 0.5rem; text-align: center;"><?= htmlspecialchars($cuisine_name) ?></small>
                            </div>
                        <?php
                            endforeach;
                        else:
                            echo "<p style='color:#777;'>No cuisine images yet</p>";
                        endif;
                        ?>
                    </div>

                    <label for="cuisine_images">Upload Cuisine Images</label>
                    <small style="color: var(--text-muted); display: block; margin-bottom: 0.5rem;"><i class="fas fa-image"></i> Upload images for each selected cuisine in the same order</small>
                    <input type="file" id="cuisine_images" name="cuisine_images[]" class="form-control" multiple accept="image/*">
                </div>

                <div class="form-group">
                    <label for="map_link">Google Map Link</label>
                    <input type="url" id="map_link" name="map_link" class="form-control" 
                           value="<?= htmlspecialchars($destination['map_link'] ?? '') ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Destination
                </button>

                <a href="add_destination_on_admin.php" class="btn btn-outline" style="margin-left: 1rem;">
                    <i class="fas fa-arrow-left"></i> Back
                </a>

            </form>
        </div>
    </div>

</div>

<?php include 'admin_footer.php'; ?>

<style>
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
    
    .btn-outline {
        background: transparent;
        border: 2px solid var(--primary);
        color: var(--primary);
    }
    
    .btn-outline:hover {
        background: rgba(22,3,79,0.05);
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