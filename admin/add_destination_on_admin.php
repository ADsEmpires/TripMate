<?php
// Start the session for admin authentication
session_start();

// Redirect to login if admin is not logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Include database configuration and connection
include '../database/dbconfig.php';

// --- Breadcrumb handling (do not alter other functionality) ---
// Current page info
$current_page = ['name' => 'Add Destination', 'url' => basename($_SERVER['PHP_SELF'])];
// Determine previous page saved in session (if any)
$breadcrumb_prev = isset($_SESSION['admin_current_page']) ? $_SESSION['admin_current_page'] : ['name' => 'Dashboard', 'url' => 'admin_dasbord.php'];
// Update current page in session so the next page can read it as "previous"
$_SESSION['admin_current_page'] = $current_page;
// -------------------------------------------------------------

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

    // Handle multiple select fields
    $season = isset($_POST['season']) ? implode(',', array_map([$conn, 'real_escape_string'], $_POST['season'])) : '';
    $people = isset($_POST['people']) ? json_encode($_POST['people']) : '[]'; // Stored as JSON
    $tips = isset($_POST['tips']) ? json_encode($_POST['tips']) : '[]'; // Stored as JSON
    $language = isset($_POST['language']) ? json_encode($_POST['language']) : '[]'; // Stored as JSON
    $cuisines = isset($_POST['cuisines']) ? json_encode($_POST['cuisines']) : '[]'; // Stored as JSON

    // Handle attractions - convert from textarea (one per line) to JSON array
    $attractions_json = '[]';
    if (!empty($_POST['attractions'])) {
        $attractions_array = array_filter(array_map('trim', explode("\n", $_POST['attractions'])));
        if (!empty($attractions_array)) {
            $attractions_json = json_encode(array_values($attractions_array));
        }
    }

    // Handle image upload
    $image_urls = [];
    if (!empty($_FILES['images']['name'][0])) {
        $upload_dir = '../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $file_name = basename($_FILES['images']['name'][$key]);
            $unique_file_name = uniqid() . '_' . $file_name;
            $file_path = $upload_dir . $unique_file_name;
            if (move_uploaded_file($tmp_name, $file_path)) {
                $image_urls[] = $unique_file_name;
            }
        }
    }
    $image_urls_json = json_encode($image_urls);

    // Handle cuisine images upload
    $cuisine_images = [];
    if (isset($_FILES['cuisine_images'])) {
        $upload_dir_cuisine = '../uploads/cuisines/';
        if (!file_exists($upload_dir_cuisine)) {
            mkdir($upload_dir_cuisine, 0777, true);
        }
        foreach ($_FILES['cuisine_images']['tmp_name'] as $cuisine => $tmp_name) {
            if (!empty($tmp_name)) {
                $file_name = $_FILES['cuisine_images']['name'][$cuisine];
                $safe_filename = preg_replace("/[^a-zA-Z0-9.]/", "_", $file_name);
                $unique_file_name = uniqid() . '_' . $safe_filename;
                $file_path = $upload_dir_cuisine . $unique_file_name;
                if (move_uploaded_file($tmp_name, $file_path)) {
                    $cuisine_images[$cuisine] = $unique_file_name;
                }
            }
        }
    }
    $cuisine_images_json = json_encode($cuisine_images);

    // Prepare and execute SQL to insert new destination
    $stmt = $conn->prepare("INSERT INTO destinations (name, type, description, location, budget, image_urls, map_link, season, people, tips, cuisines, language, cuisine_images, attractions) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("ssssdsssssssss", $name, $type, $description, $location, $budget, $image_urls_json, $map_link, $season, $people, $tips, $cuisines, $language, $cuisine_images_json, $attractions_json);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Destination added successfully!";
        header("Location: add_destination_on_admin.php");
        exit();
    } else {
        die("Execute failed: " . $stmt->error);
    }
}

// Get admin info with profile picture from database
$admin_id = $_SESSION['admin_id'];
$admin_query = $conn->prepare("SELECT name, email, profile_pic FROM admin WHERE id = ?");
$admin_query->bind_param("i", $admin_id);
$admin_query->execute();
$admin_result = $admin_query->get_result();
$admin = $admin_result->fetch_assoc() ?? ['name' => 'Unknown', 'email' => '', 'profile_pic' => NULL];

// Set admin variables for display
$admin_name = $admin['name'];
$admin_email = $admin['email'];
$admin_profile_pic = $admin['profile_pic'];

// Fetch all existing destinations from database
$result = $conn->query("SELECT * FROM destinations ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TripMate Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2ecc71;
            --accent: #e74c3c;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
            --warning: #f39c12;
            --danger: #dc3545;
            --success: #28a745;
            --info: #17a2b8;
            --sidebar-bg: #1A252F;
            --breadcrumb-bg: linear-gradient(90deg, rgba(22,3,79,0.08), rgba(26,82,118,0.03));
            --breadcrumb-accent: #16034f;
        }

        /* Breadcrumb styles (added, do not alter other styles) */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            background: var(--breadcrumb-bg);
            padding: 10px 14px;
            border-radius: 10px;
            margin-bottom: 1.25rem;
            border: 1px solid rgba(22, 3, 79, 0.06);
            box-shadow: 0 6px 18px rgba(22,3,79,0.04);
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

        /* Rest of original styles follow unchanged... */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7f9;
            color: #333;
            line-height: 1.6;
            padding-top: 80px;
        }

        /* Top Bar Styles */
        .top-bar {
            background: linear-gradient(to right, #16034f, #1a5276);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            height: 80px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo h1 {
            font-size: 28px;
            font-weight: 700;
            margin-left: 10px;
        }

        .logo i {
            font-size: 32px;
        }

        .top-bar-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 20px;
            background-color: rgba(255, 255, 255, 0.1);
            transition: background-color 0.3s;
        }

        .user-profile:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .user-profile span {
            font-weight: 600;
            font-size: 16px;
        }

        .dropdown {
            display: none;
            position: absolute;
            right: 20px;
            top: 70px;
            background: white;
            color: var(--dark);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            min-width: 200px;
            z-index: 1001;
            overflow: hidden;
        }

        .dropdown.active {
            display: block;
        }

        .dropdown a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            color: var(--dark);
            text-decoration: none;
            transition: background 0.2s;
            border-bottom: 1px solid #f0f0f0;
        }

        .dropdown a:last-child {
            border-bottom: none;
        }

        .dropdown a:hover {
            background: var(--light);
        }

        .dropdown a i {
            color: #16034f;
            width: 20px;
        }

        /* Main Layout */
        .container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: calc(100vh - 80px);
        }

        /* Sidebar */
        .sidebar {
            background: var(--sidebar-bg);
            color: white;
            padding: 20px 0;
            position: fixed;
            width: 250px;
            height: calc(100vh - 80px);
            overflow-y: auto;
            top: 80px;
            left: 0;
            z-index: 999;
        }

        .user-info {
            text-align: center;
            padding: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            margin-bottom: 15px;
        }

        .user-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .user-role {
            background-color: var(--warning);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
            margin-bottom: 15px;
        }

        .menu {
            list-style: none;
        }

        .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            cursor: pointer;
        }

        .menu-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .menu-item.active {
            background-color: rgba(255, 255, 255, 0.2);
            border-left: 4px solid var(--primary);
        }

        .menu-item i {
            margin-right: 10px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            grid-column: 2;
            padding: 25px;
            width: calc(100vw - 250px);
            margin-left: 0px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                top: 0;
            }

            .main-content {
                grid-column: 1;
                width: 100%;
                margin-left: 0;
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .logo h1 {
                font-size: 24px;
            }

            .top-bar {
                height: 70px;
            }

            body {
                padding-top: 70px;
            }

            .user-profile span {
                display: none;
            }
        }

        /* Additional Styles for Form and Destination Cards */
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            border-top: 3px solid #16034f;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(to right, rgba(22, 3, 79, 0.03), white);
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.4rem;
            color: #16034f;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header h2 i {
            color: #ff5722;
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
            color: #16034f;
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
            border-color: #ff5722;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 87, 34, 0.2);
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
            background: linear-gradient(135deg, #16034f, #1a5276);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #1a5276, #16034f);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(22, 3, 79, 0.3);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid #16034f;
            color: #16034f;
        }

        .btn-outline:hover {
            background: rgba(22, 3, 79, 0.05);
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

        /* --- New/Improved Destination Card Styles --- */
        .destination-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .destination-card {
            position: relative;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            padding-bottom: 65px;
            overflow: hidden;
        }

        .destination-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .destination-images {
            height: 200px;
            overflow: hidden;
        }

        .destination-images img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .destination-card:hover .destination-images img {
            transform: scale(1.1);
        }

        .destination-info {
            padding: 20px;
        }

        .destination-info h3 {
            font-size: 1.5rem;
            color: #16034f;
            margin-bottom: 15px;
            font-weight: 600;
            border-bottom: 2px solid #ff5722;
            padding-bottom: 8px;
            display: inline-block;
        }

        .detail-item {
            display: flex;
            align-items: center;
            margin: 12px 0;
            padding: 8px 12px;
            background: linear-gradient(to right, rgba(22, 3, 79, 0.05), transparent);
            border-radius: 8px;
            transition: transform 0.2s ease;
        }

        .detail-item:hover {
            transform: translateX(5px);
        }

        .detail-item i {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #16034f;
            color: white;
            border-radius: 50%;
            margin-right: 12px;
            font-size: 0.9rem;
        }

        .detail-item .label {
            font-weight: 500;
            color: #16034f;
            margin-right: 8px;
            min-width: 70px;
        }

        .detail-item .value {
            color: #555;
        }

        .meta-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 15px 0;
            padding: 15px 0;
            border-top: 1px dashed #ddd;
        }

        .meta-tag {
            background: linear-gradient(135deg, #16034f, #1a5276);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .meta-tag i {
            font-size: 0.8rem;
        }

        .price-tag {
            background: linear-gradient(135deg, #ff5722, #ff9800);
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 1.2rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 15px;
            box-shadow: 0 2px 10px rgba(255, 87, 34, 0.2);
        }

        .card-buttons {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 12px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            background: #f8f9fa;
            border-top: 1px solid #eee;
        }

        .card-buttons .btn {
            flex: 1;
            padding: 8px 15px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
    </style>
</head>

<body>
    <div class="top-bar">
        <div class="logo">
            <i class="fas fa-globe-americas"></i>
            <h1>TripMate Admin</h1>
        </div>

        <div class="top-bar-actions">
            <div class="user-profile" id="userProfile">
                <img src="<?= $admin_profile_pic ? $admin_profile_pic : 'https://via.placeholder.com/40' ?>" alt="Admin">
                <span><?= htmlspecialchars($admin_name) ?></span>
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>

        <div class="dropdown" id="profileDropdown">
            <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="sidebar">
            <ul class="menu">
                <li class="menu-item "><a href="admin_dasbord.php" style="color:inherit;text-decoration:none"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li class="menu-item active"><a href="add_destination_on_admin.php" style="color:inherit;text-decoration:none"><i class="fas fa-map-marker-alt"></i> Destinations</a></li>
                <li class="menu-item"><a href="user_present_chack_on_admin.php" style="color:inherit;text-decoration:none"><i class="fas fa-users"></i> Users</a></li>
                <li class="menu-item"><a href="user_join_analysis_on_ADMIN.php" style="color:inherit;text-decoration:none"><i class="fas fa-chart-line"></i> Analysis</a></li>
                <li class="menu-item"><a href="#" style="color:inherit;text-decoration:none"><i class="fas fa-envelope"></i> Messages</a></li>
                <li class="menu-item"><a href="#" style="color:inherit;text-decoration:none"><i class="fas fa-cog"></i> Settings</a></li>
                <li class="menu-item"><a href="user_ip_tracking_on_admin.php" style="color:inherit;text-decoration:none"><i class="fas fa-network-wired"></i> User IPs</a></li>
            </ul>
        </div>

        <div class="main-content">
            <!-- Breadcrumb (stylish, shows previous page) -->
            <div class="breadcrumb" aria-label="Breadcrumb">
                <a href="admin_dasbord.php"><i class="fas fa-home"></i> Dashboard</a>
                <?php if ($breadcrumb_prev && $breadcrumb_prev['name'] !== 'Dashboard' && $breadcrumb_prev['url'] !== $current_page['url']): ?>
                    <span class="sep">›</span>
                    <a href="<?= htmlspecialchars($breadcrumb_prev['url']) ?>"><i class="fas fa-arrow-left"></i> <?= htmlspecialchars($breadcrumb_prev['name']) ?></a>
                <?php endif; ?>
                <span class="sep">›</span>
                <span class="current"><i class="fas fa-plus-circle"></i> <?= htmlspecialchars($current_page['name']) ?></span>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success fade-in">
                    <i class="fas fa-check-circle"></i> <?= $_SESSION['message'] ?>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <div class="card fade-in">
                <div class="card-header">
                    <h2><i class="fas fa-plus-circle"></i> Add New Destination</h2>
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
                            <select id="season" name="season[]" class="form-control" multiple required>
                                <option value="winter">Winter</option>
                                <option value="summer">Summer</option>
                                <option value="spring">Spring</option>
                                <option value="autumn">Autumn</option>
                                <option value="monsoon">Monsoon</option>
                            </select>
                            <small class="accent">Hold Ctrl/Cmd to select multiple options</small>
                        </div>
                        <div class="form-group">
                            <label for="people">Recommended For</label>
                            <select id="people" name="people[]" class="form-control" multiple required>
                                <option value="1">Solo (1)</option>
                                <option value="2">Couples (2)</option>
                                <option value="3-5">Small Groups (3-5)</option>
                                <option value="6-9">Medium Groups (6-9)</option>
                                <option value="9+">Large Groups (9+)</option>
                            </select>
                            <small class="accent">Hold Ctrl/Cmd to select multiple options</small>
                        </div>
                        <div class="form-group">
                            <label for="tips">Travel Tips</label>
                            <select id="tips" name="tips[]" class="form-control" multiple>
                                <option value="Best time to visit: Early morning">Best time to visit: Early morning</option>
                                <option value="Carry cash">Carry cash</option>
                                <option value="Book tickets in advance">Book tickets in advance</option>
                                <option value="Hire a local guide">Hire a local guide</option>
                                <option value="Use public transport">Use public transport</option>
                                <option value="Try local food">Try local food</option>
                                <option value="Respect local customs">Respect local customs</option>
                                <option value="Wear appropriate clothing">Wear appropriate clothing</option>
                                <option value="Carry water bottles">Carry water bottles</option>
                                <option value="Learn basic local phrases">Learn basic local phrases</option>
                            </select>
                            <small class="accent">Hold Ctrl/Cmd to select multiple tips</small>
                        </div>

                        <div class="form-group">
                            <label for="cuisines">Local Cuisines</label>
                            <div class="cuisine-container">
                                <select id="cuisines" name="cuisines[]" class="form-control" multiple></select>
                                <button type="button" class="btn btn-outline" onclick="addNewCuisine()">
                                    <i class="fas fa-plus"></i> Add New Cuisine
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Cuisine Images</label>
                            <div id="cuisine-images-container"></div>
                            <small>Upload images for the selected cuisines.</small>
                        </div>

                        <div class="form-group">
                            <label for="attractions">Top Attractions (one per line)</label>
                            <textarea id="attractions" name="attractions" class="form-control" rows="4"></textarea>
                            <small class="accent">Enter each attraction on a new line.</small>
                        </div>

                        <div class="form-group">
                            <label for="language">Local Languages</label>
                            <select id="language" name="language[]" class="form-control" multiple>
                                <option value="Hindi">Hindi</option>
                                <option value="Bengali">Bengali</option>
                                <option value="Telugu">Telugu</option>
                                <option value="Marathi">Marathi</option>
                                <option value="Tamil">Tamil</option>
                                <option value="Urdu">Urdu</option>
                                <option value="Gujarati">Gujarati</option>
                                <option value="Kannada">Kannada</option>
                                <option value="Odia">Odia</option>
                                <option value="Punjabi">Punjabi</option>
                                <option value="Malayalam">Malayalam</option>
                                <option value="Assamese">Assamese</option>
                                <option value="Maithili">Maithili</option>
                                <option value="Sanskrit">Sanskrit</option>
                                <option value="English">English</option>
                            </select>
                            <small class="accent">Hold Ctrl/Cmd to select multiple languages</small>
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
                            <label for="budget">Approximate Budget (per person)</label>
                            <input type="number" id="budget" name="budget" step="0.01" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="map_link">Google Map Link</label>
                            <input type="url" id="map_link" name="map_link" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="images">Upload Images</label>
                            <input type="file" id="images" name="images[]" class="form-control" multiple accept="image/*">
                            <small>Select Your images</small>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Destination
                        </button>
                    </form>
                </div>
            </div>

            <div class="card fade-in">
                <div class="card-header">
                    <h2><i class="fas fa-map-marked-alt"></i> Existing Destinations</h2>
                    <span class="status-badge status-active">Active: <?= $result->num_rows ?></span>
                </div>
                <div class="card-body">
                    <div class="destination-grid">
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <div class="destination-card">
                                <div class="destination-images">
                                    <?php
                                    $images = json_decode($row['image_urls'], true);
                                    if (!empty($images)) {
                                        echo '<img src="../uploads/' . htmlspecialchars($images[0]) . '" alt="' . htmlspecialchars($row['name']) . '">';
                                    }
                                    ?>
                                </div>
                                <div class="destination-info">
                                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>

                                    <div class="detail-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span class="label">Location:</span>
                                        <span class="value"><?php echo htmlspecialchars($row['location']); ?></span>
                                    </div>

                                    <div class="detail-item">
                                        <i class="fas fa-tag"></i>
                                        <span class="label">Type:</span>
                                        <span class="value"><?php echo htmlspecialchars($row['type']); ?></span>
                                    </div>

                                    <div class="meta-tags">
                                        <?php
                                        $seasons = explode(',', $row['season']);
                                        foreach ($seasons as $season): ?>
                                            <span class="meta-tag">
                                                <i class="fas fa-calendar"></i>
                                                <?php echo htmlspecialchars(trim($season)); ?>
                                            </span>
                                        <?php endforeach; ?>

                                        <?php
                                        $people = json_decode($row['people'], true);
                                        foreach ($people as $person): ?>
                                            <span class="meta-tag">
                                                <i class="fas fa-users"></i>
                                                <?php echo htmlspecialchars($person); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="price-tag">
                                        <i class="fas fa-rupee-sign"></i>
                                        <?php echo number_format($row['budget']); ?>
                                    </div>
                                </div>
                                <div class="card-buttons">
                                    <a href="edit_destination.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="delete_destination.php?id=<?php echo $row['id']; ?>"
                                        class="btn btn-danger"
                                        onclick="return confirm('Are you sure you want to delete this destination?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userProfile = document.getElementById('userProfile');
            const dropdown = document.getElementById('profileDropdown');

            // Toggle dropdown on profile click
            userProfile.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.classList.toggle('active');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!userProfile.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.remove('active');
                }
            });

            // Close dropdown on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    dropdown.classList.remove('active');
                }
            });

            // Dynamic form logic for Cuisines
            const availableCuisines = [
                "Biryani", "Butter Chicken", "Paneer Tikka", "Masala Dosa", "Chole Bhature",
                "Rogan Josh", "Dhokla", "Fish Curry", "Vada Pav", "Idli Sambhar",
                "Pav Bhaji", "Samosa", "Pani Puri", "Tandoori Chicken", "Dal Makhani",
                "Rajma Chawal", "Aloo Paratha", "Kathi Roll", "Pongal", "Litti Chokha",
                "Kulfi", "Jalebi", "Gulab Jamun", "Rasgulla", "Barfi"
            ];

            const cuisinesSelect = document.getElementById('cuisines');
            const cuisineImagesContainer = document.getElementById('cuisine-images-container');

            function initCuisinesSelect() {
                cuisinesSelect.innerHTML = '';
                availableCuisines.forEach(cuisine => {
                    const option = document.createElement('option');
                    option.value = cuisine;
                    option.textContent = cuisine;
                    cuisinesSelect.appendChild(option);
                });
                updateCuisineImagesContainer();
            }

            function addNewCuisine() {
                const newCuisine = prompt('Enter the name of the new cuisine:');
                if (newCuisine && !availableCuisines.includes(newCuisine)) {
                    availableCuisines.push(newCuisine);
                    initCuisinesSelect();
                    alert('New cuisine added. Please select it from the list.');
                } else if (newCuisine) {
                    alert('This cuisine already exists!');
                }
            }

            function updateCuisineImagesContainer() {
                cuisineImagesContainer.innerHTML = '';
                const selectedOptions = Array.from(cuisinesSelect.selectedOptions);
                selectedOptions.forEach(option => {
                    const cuisine = option.value;
                    const cuisineGroup = document.createElement('div');
                    cuisineGroup.className = 'cuisine-image-group';
                    cuisineGroup.innerHTML = `
                        <label>Upload image for ${cuisine}:</label>
                        <input type="file" name="cuisine_images[${cuisine}]" accept="image/*" class="form-control">
                    `;
                    cuisineImagesContainer.appendChild(cuisineGroup);
                });
            }

            cuisinesSelect.addEventListener('change', updateCuisineImagesContainer);
            window.addNewCuisine = addNewCuisine; // Make function globally accessible
            initCuisinesSelect();
        });
    </script>
</body>

</html>