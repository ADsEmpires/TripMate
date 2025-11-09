<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../auth/login.html');
    exit();
}

include '../database/dbconfig.php';

// Get destination ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch destination data
$stmt = $conn->prepare("SELECT * FROM destinations WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$destination = $result->fetch_assoc();

// Parse the stored data
$selected_seasons = explode(',', $destination['season']);
$selected_people = json_decode($destination['people'], true) ?: [];
$selected_people = is_array($selected_people) ? $selected_people : [];
$selected_tips = json_decode($destination['tips'], true) ?: [];
$selected_cuisines = json_decode($destination['cuisines'], true) ?: [];
$cuisine_images = json_decode($destination['cuisine_images'], true) ?: [];
$selected_languages = json_decode($destination['language'], true) ?: [];

if (!$destination) {
    $_SESSION['message'] = "Destination not found!";
    header("Location: add_destination_on_admin.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $type = $conn->real_escape_string($_POST['type']);
    $description = $conn->real_escape_string($_POST['description']);
    $location = $conn->real_escape_string($_POST['location']);
    $budget = (float)$_POST['budget'];
    $map_link = $conn->real_escape_string($_POST['map_link']);
    $season = isset($_POST['season']) ? implode(',', array_map([$conn, 'real_escape_string'], $_POST['season'])) : '';
    $people_array = isset($_POST['people']) ? $_POST['people'] : [];
    $people_json = json_encode($people_array);

    // Handle attractions - convert from textarea (one per line) to JSON array
    $attractions_json = '[]';
    if (!empty($_POST['attractions'])) {
        // Split the textarea content by new lines, trim whitespace, and remove empty lines
        $attractions_array = array_filter(array_map('trim', explode("\n", $_POST['attractions'])));
        if (!empty($attractions_array)) {
            $attractions_json = json_encode(array_values($attractions_array)); // Re-index array
        }
    }

    // Handle image update if new images are uploaded
    $image_urls = json_decode($destination['image_urls'], true) ?: [];

    if (!empty($_FILES['images']['name'][0])) {
        $upload_dir = '../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Remove old images from the filesystem
        foreach ($image_urls as $old_image) {
            $old_image_path = $upload_dir . $old_image;
            if (file_exists($old_image_path)) {
                unlink($old_image_path);
            }
        }

        $image_urls = []; // Reset array to store new image names

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $file_name = basename($_FILES['images']['name'][$key]);
            $unique_file_name = uniqid() . '_' . $file_name;
            $file_path = $upload_dir . $unique_file_name;

            if (move_uploaded_file($tmp_name, $file_path)) {
                $image_urls[] = $unique_file_name; // Store only the filename
            }
        }
    } else {
        // Keep existing images if no new images uploaded
        $image_urls = json_decode($destination['image_urls'], true) ?: [];
    }

    $image_urls_json = json_encode($image_urls);

    // Handle tips selection
    $tips = isset($_POST['tips']) ? json_encode($_POST['tips']) : '[]';

    // Handle language selection
    $language = isset($_POST['language']) ? json_encode($_POST['language']) : '[]';

    // Handle cuisines
    $cuisines = isset($_POST['cuisines']) ? $_POST['cuisines'] : [];
    $cuisines_json = json_encode($cuisines);

    // Handle cuisine images
    $cuisine_images_from_db = json_decode($destination['cuisine_images'], true) ?: [];
    $final_cuisine_images = [];

    // Process cuisine images
    if (isset($_POST['cuisine_names']) && is_array($_POST['cuisine_names'])) {
        $upload_dir = '../uploads/cuisines/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($_POST['cuisine_names'] as $index => $cuisine_name) {
            if (!empty(trim($cuisine_name))) {
                $cuisine_name_clean = $conn->real_escape_string(trim($cuisine_name));

                // Check if there's a new image uploaded for this cuisine
                if (isset($_FILES['cuisine_images']['name'][$index]) && !empty($_FILES['cuisine_images']['name'][$index])) {
                    $file_name = $_FILES['cuisine_images']['name'][$index];
                    $tmp_name = $_FILES['cuisine_images']['tmp_name'][$index];

                    $safe_filename = preg_replace("/[^a-zA-Z0-9.]/", "_", $file_name);
                    $unique_file_name = uniqid() . '_' . $safe_filename;
                    $file_path = $upload_dir . $unique_file_name;

                    if (move_uploaded_file($tmp_name, $file_path)) {
                        $final_cuisine_images[$cuisine_name_clean] = $unique_file_name;
                    }
                } else {
                    // Keep existing image if available and no new image uploaded
                    $existing_image_found = false;
                    foreach ($cuisine_images_from_db as $existing_cuisine => $existing_image) {
                        if ($existing_cuisine === $cuisine_name_clean) {
                            $final_cuisine_images[$cuisine_name_clean] = $existing_image;
                            $existing_image_found = true;
                            break;
                        }
                    }
                    // If no existing image found and cuisine name matches one from previous data, try to find it
                    if (!$existing_image_found) {
                        foreach ($cuisine_images_from_db as $existing_cuisine => $existing_image) {
                            if (strtolower(trim($existing_cuisine)) === strtolower(trim($cuisine_name_clean))) {
                                $final_cuisine_images[$cuisine_name_clean] = $existing_image;
                                break;
                            }
                        }
                    }
                }
            }
        }
    }

    $cuisine_images_json = json_encode($final_cuisine_images);

    // Update the SQL query with proper error handling
    $stmt = $conn->prepare("UPDATE destinations SET 
        name = ?, type = ?, description = ?, location = ?, budget = ?, 
        image_urls = ?, map_link = ?, season = ?, people = ?, 
        tips = ?, cuisines = ?, language = ?, cuisine_images = ?, attractions = ?
        WHERE id = ?");

    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }

    $stmt->bind_param(
        "ssssdsssssssssi",
        $name,
        $type,
        $description,
        $location,
        $budget,
        $image_urls_json,
        $map_link,
        $season,
        $people_json,
        $tips,
        $cuisines_json,
        $language,
        $cuisine_images_json,
        $attractions_json,
        $id
    );

    if ($stmt->execute()) {
        $_SESSION['message'] = "Destination updated successfully!";
        header("Location: add_destination_on_admin.php");
        exit();
    } else {
        die("Execute failed: " . $stmt->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Destination | TripMate Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Use the same styles as admin.php or create a separate CSS file */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 2rem;
        }

        .edit-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            padding: 2.5rem;
            border: 1px solid #e1e5e9;
        }

        h1 {
            color: #16034f;
            margin-top: 0;
            margin-bottom: 1.5rem;
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .form-group {
            margin-bottom: 1.75rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: #2d3748;
            font-size: 1rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #fff;
        }

        .form-control:focus {
            outline: none;
            border-color: #16034f;
            box-shadow: 0 0 0 3px rgba(22, 3, 79, 0.1);
        }

        /* Improved Button Styles */
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 160px;
            justify-content: center;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #16034f, #2d1a69);
            color: white;
            box-shadow: 0 4px 15px rgba(22, 3, 79, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #2d1a69, #16034f);
            box-shadow: 0 6px 20px rgba(22, 3, 79, 0.4);
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #16034f;
            color: #16034f;
            box-shadow: 0 2px 8px rgba(22, 3, 79, 0.1);
        }

        .btn-outline:hover {
            background: #16034f;
            color: white;
            box-shadow: 0 4px 15px rgba(22, 3, 79, 0.2);
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 0.6rem 1.2rem;
            min-width: auto;
            font-size: 0.9rem;
        }

        .current-images {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .current-images img {
            width: 150px;
            height: 100px;
            object-fit: cover;
            border-radius: 6px;
            border: 2px solid #e2e8f0;
        }

        .cuisine-container {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .cuisine-image-group {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
        }

        .cuisine-image-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #16034f;
            font-weight: 600;
        }

        .cuisine-image-group img {
            max-width: 100px;
            border-radius: 4px;
            margin-top: 10px;
        }

        .existing-image {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        #cuisine-images-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        small {
            color: #666;
            font-style: italic;
        }

        .budget-display {
            margin-top: 0.5rem;
            padding: 0.75rem;
            background: #f7fafc;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            font-weight: 600;
            color: #2d3748;
        }

        .image-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .preview-image {
            position: relative;
            cursor: pointer;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .preview-image:hover {
            transform: scale(1.05);
        }

        .preview-image img {
            width: 150px;
            height: 100px;
            object-fit: cover;
        }

        .image-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.5rem;
            text-align: center;
            font-size: 0.8rem;
        }

        .file-upload {
            border: 2px dashed #cbd5e0;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .file-upload:hover {
            border-color: #16034f;
            background: #f0f4f8;
        }

        /* Cuisine Section Styles */
        .cuisine-section {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-bottom: 1.5rem;
        }

        .cuisine-item {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-bottom: 1rem;
        }

        .cuisine-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .cuisine-preview {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
        }

        .cuisine-preview img {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            border: 2px solid #e2e8f0;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
        }

        .modal-content {
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90%;
            margin-top: 2%;
        }

        .close {
            position: absolute;
            top: 20px;
            right: 35px;
            color: #fff;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="edit-container">
        <h1><i class="fas fa-edit"></i> Edit Destination</h1>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Destination Name</label>
                <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($destination['name']) ?>" required>
            </div>

            <div class="form-group">
                <label for="type">Destination Type</label>
                <select id="type" name="type" class="form-control" required>
                    <option value="beach" <?= $destination['type'] === 'beach' ? 'selected' : '' ?>>Beach</option>
                    <option value="mountain" <?= $destination['type'] === 'mountain' ? 'selected' : '' ?>>Mountain</option>
                    <option value="city" <?= $destination['type'] === 'city' ? 'selected' : '' ?>>City</option>
                    <option value="village" <?= $destination['type'] === 'village' ? 'selected' : '' ?>>Village</option>
                    <option value="historical" <?= $destination['type'] === 'historical' ? 'selected' : '' ?>>Historical</option>
                </select>
            </div>

            <div class="form-group">
                <label for="season">Best Season to Visit</label>
                <select id="season" name="season[]" class="form-control" multiple required>
                    <option value="winter" <?= in_array('winter', $selected_seasons) ? 'selected' : '' ?>>Winter</option>
                    <option value="summer" <?= in_array('summer', $selected_seasons) ? 'selected' : '' ?>>Summer</option>
                    <option value="spring" <?= in_array('spring', $selected_seasons) ? 'selected' : '' ?>>Spring</option>
                    <option value="autumn" <?= in_array('autumn', $selected_seasons) ? 'selected' : '' ?>>Autumn</option>
                    <option value="monsoon" <?= in_array('monsoon', $selected_seasons) ? 'selected' : '' ?>>Monsoon</option>
                </select>
                <small>Hold Ctrl/Cmd to select multiple options</small>
            </div>

            <div class="form-group">
                <label for="people">Recommended For</label>
                <select id="people" name="people[]" class="form-control" multiple required>
                    <option value="1" <?= in_array('1', $selected_people) ? 'selected' : '' ?>>Solo (1)</option>
                    <option value="2" <?= in_array('2', $selected_people) ? 'selected' : '' ?>>Couples (2)</option>
                    <option value="3-5" <?= in_array('3-5', $selected_people) ? 'selected' : '' ?>>Small Groups (3-5)</option>
                    <option value="6-9" <?= in_array('6-9', $selected_people) ? 'selected' : '' ?>>Medium Groups (6-9)</option>
                    <option value="9+" <?= in_array('9+', $selected_people) ? 'selected' : '' ?>>Large Groups (9+)</option>
                </select>
                <small style="color: #666; margin-top: 0.5rem; display: block;">
                    <i class="fas fa-info-circle"></i> Hold Ctrl/Cmd to select multiple options
                </small>
            </div>

            <div class="form-group">
                <label for="description"><i class="fas fa-file-alt"></i> Description</label>
                <textarea id="description" name="description" class="form-control" required><?= htmlspecialchars($destination['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="location"><i class="fas fa-location-dot"></i> Location</label>
                <input type="text" id="location" name="location" class="form-control" value="<?= htmlspecialchars($destination['location']) ?>" required>
            </div>

            <div class="form-group">
                <label for="budget"><i class="fas fa-indian-rupee-sign"></i> Budget (per day)</label>
                <input type="number" id="budget" name="budget" step="0.01" class="form-control" value="<?= htmlspecialchars($destination['budget']) ?>" required>
                <div class="budget-display">₹<?= number_format($destination['budget']) ?> / day</div>
            </div>

            <div class="form-group">
                <div class="image-preview-section">
                    <label><i class="fas fa-images"></i> Current Images</label>
                    <div class="image-preview">
                        <?php
                        $images = json_decode($destination['image_urls'], true);
                        if (is_array($images) && !empty($images)):
                            foreach ($images as $index => $image):
                                if (!empty($image)):
                                    $image_path = '../uploads/' . $image;
                                    if (file_exists($image_path)): ?>
                                        <div class="preview-image" onclick="openModal('<?= $image_path ?>')">
                                            <img src="<?= $image_path ?>" alt="Destination image">
                                            <div class="image-overlay">Click to view full size</div>
                                        </div>
                                    <?php else: ?>
                                        <div class="preview-image">
                                            <img src="../uploads/placeholder.jpg" alt="Image not found">
                                            <div class="image-overlay">Image not found</div>
                                        </div>
                            <?php endif;
                                endif;
                            endforeach;
                        else: ?>
                            <p style="color: #666; font-style: italic;">No images uploaded yet.</p>
                        <?php endif; ?>
                    </div>

                    <label for="images"><i class="fas fa-upload"></i> Upload New Images</label>
                    <div class="file-upload">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h4>Click to upload new images</h4>
                        <p>PNG, JPG, JPEG up to 5MB</p>
                        <input type="file" id="images" name="images[]" multiple accept="image/*">
                    </div>
                    <small style="color: #666; margin-top: 0.5rem; display: block;">
                        <i class="fas fa-info-circle"></i> Leave empty to keep current images
                    </small>
                </div>
            </div>

            <!-- Cuisines Section -->
            <div class="form-group cuisine-section">
                <label><i class="fas fa-utensils"></i> Local Cuisines</label>
                <small style="color: #666; margin-bottom: 1rem; display: block;">
                    Add local cuisines with their images. You can add multiple cuisines.
                </small>

                <div id="cuisines-container">
                    <?php
                    $cuisine_counter = 0;
                    if (!empty($cuisine_images) && is_array($cuisine_images)):
                        foreach ($cuisine_images as $cuisine_name => $cuisine_image):
                            if (!empty(trim($cuisine_name))): ?>
                                <div class="cuisine-item" data-index="<?= $cuisine_counter ?>">
                                    <div class="form-group">
                                        <label for="cuisine_name_<?= $cuisine_counter ?>">Cuisine Name</label>
                                        <input type="text" id="cuisine_name_<?= $cuisine_counter ?>"
                                            name="cuisine_names[]" class="form-control"
                                            value="<?= htmlspecialchars($cuisine_name) ?>"
                                            placeholder="e.g., Butter Chicken, Masala Dosa">
                                    </div>

                                    <div class="form-group">
                                        <label for="cuisine_image_<?= $cuisine_counter ?>">Cuisine Image</label>
                                        <input type="file" id="cuisine_image_<?= $cuisine_counter ?>"
                                            name="cuisine_images[]" class="form-control"
                                            accept="image/*">
                                        <small style="color: #666; margin-top: 0.5rem; display: block;">
                                            Leave empty to keep current image
                                        </small>

                                        <?php
                                        $cuisine_image_path = '../uploads/cuisines/' . $cuisine_image;
                                        if (file_exists($cuisine_image_path)): ?>
                                            <div class="cuisine-preview">
                                                <img src="<?= $cuisine_image_path ?>" alt="<?= htmlspecialchars($cuisine_name) ?>">
                                                <span>Current image: <?= htmlspecialchars($cuisine_image) ?></span>
                                            </div>
                                        <?php else: ?>
                                            <div class="cuisine-preview">
                                                <img src="../uploads/placeholder.jpg" alt="Image not found">
                                                <span>Image not found: <?= htmlspecialchars($cuisine_image) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <button type="button" class="btn btn-outline btn-sm" onclick="removeCuisine(this)">
                                        <i class="fas fa-trash"></i> Remove Cuisine
                                    </button>
                                </div>
                    <?php
                                $cuisine_counter++;
                            endif;
                        endforeach;
                    endif; ?>

                    <!-- Empty template for new cuisine -->
                    <div class="cuisine-item" data-index="<?= $cuisine_counter ?>" style="display: none;">
                        <div class="form-group">
                            <label>Cuisine Name</label>
                            <input type="text" name="cuisine_names[]" class="form-control"
                                placeholder="e.g., Butter Chicken, Masala Dosa">
                        </div>

                        <div class="form-group">
                            <label>Cuisine Image</label>
                            <input type="file" name="cuisine_images[]" class="form-control"
                                accept="image/*">
                            <small style="color: #666; margin-top: 0.5rem; display: block;">
                                Upload an image for this cuisine
                            </small>
                        </div>

                        <button type="button" class="btn btn-outline btn-sm" onclick="removeCuisine(this)">
                            <i class="fas fa-trash"></i> Remove Cuisine
                        </button>
                    </div>
                </div>

                <button type="button" class="btn btn-outline" onclick="addCuisine()">
                    <i class="fas fa-plus"></i> Add Another Cuisine
                </button>
            </div>

            <div class="form-group">
                <label for="map_link"><i class="fas fa-map"></i> Google Map Link</label>
                <input type="url" id="map_link" name="map_link" class="form-control" value="<?= htmlspecialchars($destination['map_link']) ?>" required>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="window.location.href='add_destination_on_admin.php'">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Destination
                </button>
            </div>
        </form>
    </div>

    <!-- Modal for full-size image preview -->
    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <script>
        // Real-time budget display update
        document.getElementById('budget').addEventListener('input', function() {
            const budgetDisplay = document.querySelector('.budget-display');
            const value = new Intl.NumberFormat('en-IN').format(this.value);
            budgetDisplay.textContent = `₹${value} / day`;
        });

        // File upload preview
        document.getElementById('images').addEventListener('change', function(e) {
            const fileUpload = document.querySelector('.file-upload');
            if (this.files.length > 0) {
                fileUpload.innerHTML = `
                    <i class="fas fa-check-circle" style="color: #10b981;"></i>
                    <h4>${this.files.length} file(s) selected</h4>
                    <p>Ready to upload</p>
                    <input type="file" id="images" name="images[]" multiple accept="image/*">
                `;
                // Re-attach event listener to new input
                document.getElementById('images').addEventListener('change', arguments.callee);
            }
        });

        // Cuisine management
        function addCuisine() {
            const container = document.getElementById('cuisines-container');
            const template = container.querySelector('.cuisine-item[style*="display: none"]');
            const newCuisine = template.cloneNode(true);

            // Remove the display none style
            newCuisine.style.display = 'block';

            // Insert before the template
            container.insertBefore(newCuisine, template);
        }

        function removeCuisine(button) {
            const cuisineItem = button.closest('.cuisine-item');
            cuisineItem.remove();
        }

        // Image modal functions
        function openModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('imageModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });

        // Initialize with at least one cuisine if none exist
        document.addEventListener('DOMContentLoaded', function() {
            const existingCuisines = document.querySelectorAll('.cuisine-item[data-index]');
            if (existingCuisines.length === 0) {
                addCuisine();
            }
        });
    </script>
</body>

</html>