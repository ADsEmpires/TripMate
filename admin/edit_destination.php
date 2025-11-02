<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../auth/login.html');
    exit();
}

include '../database/dbconfig.php';

// --- Breadcrumb handling (do not alter other functionality) ---
$current_page = ['name' => 'Edit Destination', 'url' => basename($_SERVER['PHP_SELF'])];
$breadcrumb_prev = isset($_SESSION['admin_current_page']) ? $_SESSION['admin_current_page'] : ['name' => 'Dashboard', 'url' => 'admin_dasbord.php'];
$_SESSION['admin_current_page'] = $current_page;
// -------------------------------------------------------------

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

    // Keep existing cuisine images that are still selected
    if (isset($_POST['existing_cuisine_images'])) {
        foreach ($_POST['existing_cuisine_images'] as $cuisine => $image_name) {
            if (in_array($cuisine, $cuisines)) {
                $final_cuisine_images[$cuisine] = $image_name;
            }
        }
    }

    // Process new cuisine images
    if (isset($_FILES['cuisine_images'])) {
        $upload_dir = '../uploads/cuisines/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($_FILES['cuisine_images']['tmp_name'] as $cuisine => $tmp_name) {
            if (!empty($tmp_name) && in_array($cuisine, $cuisines)) {
                $file_name = $_FILES['cuisine_images']['name'][$cuisine];
                $safe_filename = preg_replace("/[^a-zA-Z0-9.]/", "_", $file_name);
                $unique_file_name = uniqid() . '_' . $safe_filename;
                $file_path = $upload_dir . $unique_file_name;

                if (move_uploaded_file($tmp_name, $file_path)) {
                    $final_cuisine_images[$cuisine] = $unique_file_name; // Store only the filename
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
        /* Breadcrumb styles (added) */
        :root {
            --breadcrumb-bg: linear-gradient(90deg, rgba(22,3,79,0.06), rgba(26,82,118,0.03));
            --breadcrumb-accent: #16034f;
        }
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            background: var(--breadcrumb-bg);
            padding: 10px 14px;
            border-radius: 10px;
            margin-bottom: 1.25rem;
            border: 1px solid rgba(22,3,79,0.06);
            box-shadow: 0 6px 18px rgba(22,3,79,0.03);
            width: fit-content;
        }
        .breadcrumb a {
            color: var(--breadcrumb-accent);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        .breadcrumb .sep {
            color: #999;
            font-weight: 700;
            margin: 0 4px;
        }
        .breadcrumb .current {
            color: #404040;
            font-weight: 700;
            background: linear-gradient(90deg, rgba(255,87,34,0.06), rgba(255,152,0,0.02));
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid rgba(0,0,0,0.03);
        }

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
            border-radius: 10px;
            box-shadow: 0 3px 20px rgba(0, 0, 0, 0.08);
            padding: 2rem;
        }

        h1 {
            color: #16034f;
            margin-top: 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: #16034f;
            color: white;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid #16034f;
            color: #16034f;
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
            border-radius: 4px;
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
    </style>
</head>

<body>
    <div class="edit-container">
        <h1><i class="fas fa-edit"></i> Edit Destination</h1>

        <!-- Breadcrumb (stylish, shows previous page) -->
        <div class="breadcrumb" aria-label="Breadcrumb">
            <a href="admin_dasbord.php"><i class="fas fa-home"></i> Dashboard</a>
            <?php if ($breadcrumb_prev && $breadcrumb_prev['name'] !== 'Dashboard' && $breadcrumb_prev['url'] !== $current_page['url']): ?>
                <span class="sep">›</span>
                <a href="<?= htmlspecialchars($breadcrumb_prev['url']) ?>"><i class="fas fa-arrow-left"></i> <?= htmlspecialchars($breadcrumb_prev['name']) ?></a>
            <?php endif; ?>
            <span class="sep">›</span>
            <span class="current"><i class="fas fa-edit"></i> <?= htmlspecialchars($current_page['name']) ?></span>
        </div>

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
                    <option value="3-5" <?= in_array('3-5', $selected_people) ? 'selected' :