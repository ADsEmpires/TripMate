<?php
// Start the session for admin authentication
session_start();
/*
// Redirect to login if admin is not logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}
*/
// Include database configuration and connection
include '../database/dbconfig.php';

// Get admin info from session
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];
$admin_email = $_SESSION['admin_email'];

// Handle form submission for adding a new destination
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and get form inputs
    $name = $conn->real_escape_string($_POST['name']);
    $type = $conn->real_escape_string($_POST['type']);
    $description = $conn->real_escape_string($_POST['description']);
    $location = $conn->real_escape_string($_POST['location']);
    $budget = (float)$_POST['budget'];
    $map_link = $conn->real_escape_string($_POST['map_link']);
    
    // Handle multiple select for season and people
    $season = isset($_POST['season']) ? implode(',', array_map([$conn, 'real_escape_string'], $_POST['season'])) : '';
    $people_json = isset($_POST['people']) ? json_encode($_POST['people']) : '[]';
    
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

    // Handle destination image upload
    $image_urls = [];
    if (!empty($_FILES['images']['name'][0])) {
        $upload_dir = '../uploads/destinations/';
        // Create upload directory if it doesn't exist
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
                    // Store relative path from uploads folder (e.g., "destinations/unique_filename.jpg")
                    $image_urls[] = 'destinations/' . $unique_file_name;
                }
            }
        }
    }
    $image_urls_json = json_encode($image_urls);

    // Handle cuisine images upload
    $cuisine_images = [];
    $cuisines = isset($_POST['cuisines']) ? $_POST['cuisines'] : [];
    
    if (isset($_FILES['cuisine_images']) && is_array($_FILES['cuisine_images']['name'])) {
        $upload_dir_cuisine = '../uploads/cuisines/';
        if (!file_exists($upload_dir_cuisine)) {
            mkdir($upload_dir_cuisine, 0777, true);
        }
        
        foreach ($_FILES['cuisine_images']['tmp_name'] as $cuisine => $tmp_name) {
            if (!empty($tmp_name) && $_FILES['cuisine_images']['error'][$cuisine] === UPLOAD_ERR_OK) {
                $file_name = $_FILES['cuisine_images']['name'][$cuisine];
                // Sanitize filename
                $safe_filename = preg_replace("/[^a-zA-Z0-9.-]/", "_", $file_name);
                $unique_file_name = uniqid() . '_' . $safe_filename;
                $file_path = $upload_dir_cuisine . $unique_file_name;
                
                if (move_uploaded_file($tmp_name, $file_path)) {
                    // Map cuisine name to relative path from uploads folder
                    if (isset($cuisines[$cuisine])) {
                        $cuisine_name = $cuisines[$cuisine];
                        $cuisine_images[$cuisine_name] = 'cuisines/' . $unique_file_name;
                    }
                }
            }
        }
    }
    $cuisines_json = json_encode($cuisines);
    $cuisine_images_json = json_encode($cuisine_images);

    // Prepare and execute SQL to insert new destination
    $stmt = $conn->prepare("INSERT INTO destinations (name, type, description, location, budget, image_urls, map_link, season, people, tips, cuisines, language, cuisine_images, attractions) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }
    
    $stmt->bind_param("ssssdsssssssss", $name, $type, $description, $location, $budget, $image_urls_json, $map_link, $season, $people_json, $tips, $cuisines_json, $language, $cuisine_images_json, $attractions_json);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Destination added successfully!";
        header("Location: add_destination_on_admin.php");
        exit();
    } else {
        $_SESSION['message'] = "Error adding destination: " . $conn->error;
    }
}

// Get admin info with profile picture
$admin_id = $_SESSION['admin_id'];
$admin_query = $conn->prepare("SELECT name, email, profile_pic FROM admin WHERE id = ?");
$admin_query->bind_param("i", $admin_id);
$admin_query->execute();
$admin_result = $admin_query->get_result();
$admin = $admin_result->fetch_assoc() ?? ['name' => 'Unknown', 'email' => '', 'profile_pic' => NULL];

$admin_name = $admin['name'];
$admin_email = $admin['email'];
$admin_profile_pic = $admin['profile_pic'];

// Fetch all existing destinations
$result = $conn->query("SELECT * FROM destinations ORDER BY id DESC");
?>

<?php include 'admin_header.php'; ?>

<div class="main-content">

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert <?= strpos($_SESSION['message'], 'Error') === false ? 'alert-success' : 'alert-danger' ?> fade-in">
            <i class="fas fa-<?= strpos($_SESSION['message'], 'Error') === false ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= $_SESSION['message'] ?>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <div class="add-button-wrapper" style="margin: 2rem 0; text-align: center;">
        <button id="showAddFormBtn" class="btn btn-primary" style="font-size: 1.3rem; padding: 1rem 2.5rem; border-radius: 50px; box-shadow: 0 10px 20px var(--shadow-color);">
            <i class="fas fa-plus-circle"></i> Add New Destination
        </button>
    </div>

    <div id="addFormContainer" class="widget-card fade-in" style="display: none; overflow: hidden; transition: max-height 0.5s ease, opacity 0.4s ease; max-height: 0; opacity: 0; margin-bottom: 2rem;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--card-border); padding-bottom: 1rem;">
            <h2 style="font-size: 1.8rem; color: var(--text-main);"><i class="fas fa-plus-circle" style="color: var(--primary);"></i> Add New Destination</h2>
            <button type="button" id="closeFormBtn" class="btn btn-outline" style="border-radius: 50px;">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Destination Name</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="type">Destination Type</label>
                    <select id="type" name="type" class="form-control" required>
                        <option value="beach">Beach</option>
                        <option value="mountain">Mountain</option>
                        <option value="city">City</option>
                        <option value="village">Village</option>
                        <option value="historical">Historical</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="season">Best Season to Visit</label>
                    <select id="season" name="season[]" class="form-control" multiple required style="height: auto; min-height: 120px;">
                        <option value="winter">Winter</option>
                        <option value="summer">Summer</option>
                        <option value="spring">Spring</option>
                        <option value="autumn">Autumn</option>
                        <option value="monsoon">Monsoon</option>
                    </select>
                    <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;"><i class="fas fa-info-circle"></i> Hold Ctrl/Cmd to select multiple options</small>
                </div>
                
                <div class="form-group">
                    <label for="people">Recommended For</label>
                    <select id="people" name="people[]" class="form-control" multiple required style="height: auto; min-height: 120px;">
                        <option value="1">Solo (1)</option>
                        <option value="2">Couples (2)</option>
                        <option value="3-5">Small Groups (3-5)</option>
                        <option value="6-9">Medium Groups (6-9)</option>
                        <option value="9+">Large Groups (9+)</option>
                    </select>
                    <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;"><i class="fas fa-info-circle"></i> Hold Ctrl/Cmd to select multiple options</small>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="budget">Budget (₹ per day)</label>
                    <input type="number" id="budget" name="budget" step="0.01" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="images">Upload Destination Images</label>
                    <input type="file" id="images" name="images[]" class="form-control" multiple accept="image/*" style="padding: 0.6rem;">
                    <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;"><i class="fas fa-image"></i> Select your destination images</small>
                </div>
                
                <div class="form-group">
                    <label for="cuisines">Local Cuisines</label>
                    <select id="cuisines" name="cuisines[]" class="form-control" multiple style="height: auto; min-height: 120px;">
                        <option value="Biryani">Biryani</option>
                        <option value="Butter Chicken">Butter Chicken</option>
                        <option value="Paneer Tikka">Paneer Tikka</option>
                        <option value="Masala Dosa">Masala Dosa</option>
                        <option value="Chole Bhature">Chole Bhature</option>
                        <option value="Rogan Josh">Rogan Josh</option>
                        <option value="Dal Makhani">Dal Makhani</option>
                        <option value="Tandoori Chicken">Tandoori Chicken</option>
                    </select>
                    <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;"><i class="fas fa-info-circle"></i> Hold Ctrl/Cmd to select multiple cuisines</small>
                </div>
                
                <div class="form-group">
                    <label for="cuisine_images">Cuisine Images</label>
                    <small style="color: var(--text-muted); display: block; margin-bottom: 0.5rem;"><i class="fas fa-image"></i> Upload images for each selected cuisine in the same order</small>
                    <input type="file" id="cuisine_images" name="cuisine_images[]" class="form-control" multiple accept="image/*" style="padding: 0.6rem;">
                </div>
                
                <div class="form-group">
                    <label for="map_link">Google Map Link</label>
                    <input type="url" id="map_link" name="map_link" class="form-control" required>
                </div>

                <div style="margin-top: 2.5rem; display: flex; gap: 1rem; border-top: 1px solid var(--card-border); padding-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary" style="border-radius: 50px;">
                        <i class="fas fa-save"></i> Save Destination
                    </button>
                    <button type="button" id="cancelFormBtn" class="btn btn-outline" style="border-radius: 50px;">
                        <i class="fas fa-ban"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="widget-card fade-in" style="margin-bottom: 2rem;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--card-border); padding-bottom: 1rem;">
            <h2 style="font-size: 1.8rem; color: var(--text-main); margin: 0;"><i class="fas fa-map-marked-alt" style="color: var(--secondary);"></i> Existing Destinations</h2>
            <span class="status-badge status-active">Active: <?= $result->num_rows ?></span>
        </div>
        <div class="card-body">
            <div class="destination-grid" id="destinationsContainer">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="destination-card widget-card" style="padding: 0; overflow: hidden;">
                        <div class="destination-images">
                            <?php 
                            if (!empty($row['image_urls'])): 
                                $images = json_decode($row['image_urls'], true);
                                if (is_array($images) && !empty($images[0])): 
                                    $image_path = '../uploads/' . $images[0];
                                ?>
                                    <img src="<?= htmlspecialchars($image_path) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                                <?php else: ?>
                                    <img src="../image/placeholder.png" alt="Default destination image">
                                <?php endif; ?>
                            <?php else: ?>
                                <img src="../image/placeholder.png" alt="Default destination image">
                            <?php endif; ?>
                            <div class="destination-type-badge">
                                <i class="fas fa-tag"></i> <?= ucfirst($row['type']) ?>
                            </div>
                        </div>
                        <div class="destination-info">
                            <h3 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 1rem; color: var(--text-main);"><?= htmlspecialchars($row['name']) ?></h3>
                            
                            <div class="destination-meta" style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; margin-bottom: 1.5rem;">
                                <span style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: var(--text-muted);">
                                    <i class="fas fa-calendar-alt" style="color: var(--primary); width: 16px; text-align: center;"></i> 
                                    <span class="text-truncate" title="<?= str_replace(',', ', ', ucwords($row['season'] ?? '', ',')) ?>"><?= str_replace(',', ', ', ucwords($row['season'] ?? '', ',')) ?></span>
                                </span>
                                <span style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: var(--text-muted);">
                                    <i class="fas fa-user-friends" style="color: var(--secondary); width: 16px; text-align: center;"></i> 
                                    <?php 
                                    $people = json_decode($row['people'] ?? '[]', true);
                                    echo is_array($people) ? htmlspecialchars(implode(', ', $people)) : htmlspecialchars($row['people'] ?? '');
                                    ?>
                                </span>
                                <span style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: var(--text-muted);">
                                    <i class="fas fa-rupee-sign" style="color: var(--primary); width: 16px; text-align: center;"></i> 
                                    <span style="font-weight: 700; color: var(--text-main);"><?= number_format($row['budget']) ?></span>/day
                                </span>
                                <span style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: var(--text-muted);">
                                    <i class="fas fa-map-marker-alt" style="color: var(--danger); width: 16px; text-align: center;"></i> 
                                    <span class="text-truncate" title="<?= htmlspecialchars($row['location']) ?>"><?= htmlspecialchars($row['location']) ?></span>
                                </span>
                            </div>
                            
                            <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.5; margin-bottom: 1.5rem; height: 4.5em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                                <?= htmlspecialchars($row['description'] ?? '') ?>
                            </p>
                            
                            <div class="destination-actions" style="display: flex; gap: 0.5rem; border-top: 1px solid var(--card-border); padding-top: 1.25rem; flex-wrap: wrap;">
                                <a href="edit_destination.php?id=<?= $row['id'] ?>" class="btn btn-outline" style="flex: 1; border-radius: 50px; font-size: 0.9rem; padding: 0.6rem;">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form action="delete_destination.php" method="POST" style="flex: 1;">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn btn-danger" style="width: 100%; border-radius: 50px; font-size: 0.9rem; padding: 0.6rem;" onclick="return confirm('Are you sure you want to delete this destination?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const showBtn       = document.getElementById('showAddFormBtn');
    const container     = document.getElementById('addFormContainer');
    const closeBtn      = document.getElementById('closeFormBtn');
    const cancelBtn     = document.getElementById('cancelFormBtn');

    function openForm() {
        container.style.display = 'block';
        setTimeout(() => {
            container.style.maxHeight = container.scrollHeight + 500 + 'px';
            container.style.opacity = '1';
        }, 10);
        showBtn.style.display = 'none';
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function closeForm() {
        container.style.maxHeight = '0';
        container.style.opacity = '0';
        setTimeout(() => {
            container.style.display = 'none';
        }, 500);
        showBtn.style.display = 'inline-flex';
    }

    showBtn.addEventListener('click', openForm);
    closeBtn.addEventListener('click', closeForm);
    cancelBtn.addEventListener('click', closeForm);
});
</script>

<style>
    .form-group {
        margin-bottom: 1.8rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.6rem;
        font-weight: 700;
        color: var(--text-main);
        font-size: 0.95rem;
    }
    
    .form-control {
        width: 100%;
        padding: 0.9rem 1.2rem;
        border: 1px solid var(--card-border);
        border-radius: 12px;
        font-size: 1rem;
        background: var(--bg-base);
        color: var(--text-main);
        transition: all 0.3s;
        font-family: inherit;
    }
    
    .form-control:focus {
        border-color: var(--secondary);
        outline: none;
        box-shadow: 0 0 0 4px var(--glow-color);
        background: var(--bg-surface);
    }

    select.form-control option {
        padding: 0.5rem;
        background: var(--bg-surface);
        color: var(--text-main);
    }
    
    textarea.form-control {
        min-height: 150px;
        resize: vertical;
        line-height: 1.6;
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.8rem 1.8rem;
        border-radius: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
        border: none;
        gap: 8px;
        font-size: 1rem;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px var(--glow-color);
    }
    
    .btn-outline {
        background: transparent;
        border: 2px solid var(--card-border);
        color: var(--text-main);
    }
    
    .btn-outline:hover {
        background: var(--bg-base);
        border-color: var(--primary);
        color: var(--primary);
    }
    
    .btn-danger {
        background: transparent;
        border: 2px solid rgba(239, 68, 68, 0.3);
        color: var(--danger);
    }
    
    .btn-danger:hover {
        background: var(--danger);
        color: white;
        border-color: var(--danger);
        box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
    }
    
    .alert {
        padding: 1.2rem 1.5rem;
        border-radius: 16px;
        margin-bottom: 2rem;
        border-left: 5px solid transparent;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 10px 20px var(--shadow-color);
    }
    
    .alert-success {
        background-color: var(--bg-surface);
        color: var(--success);
        border: 1px solid var(--card-border);
        border-left-color: var(--success);
    }
    
    .alert-danger {
        background-color: var(--bg-surface);
        color: var(--danger);
        border: 1px solid var(--card-border);
        border-left-color: var(--danger);
    }
    
    .destination-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 2rem;
    }
    
    .destination-images {
        height: 220px;
        overflow: hidden;
        position: relative;
    }
    
    .destination-images img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s cubic-bezier(0.25, 1, 0.5, 1);
    }
    
    .destination-card:hover .destination-images img {
        transform: scale(1.08);
    }
    
    .destination-type-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        color: white;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }
    
    .destination-info {
        padding: 1.8rem;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 700;
    }
    
    .status-active {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success);
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .fade-in {
        animation: fadeIn 0.6s cubic-bezier(0.25, 1, 0.5, 1) forwards;
    }

    .destination-actions {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
    }

    .destination-actions .btn {
        margin: 0;
        padding: 0.6rem;
        font-size: 0.85rem;
    }
</style>

<?php include 'admin_footer.php'; ?>