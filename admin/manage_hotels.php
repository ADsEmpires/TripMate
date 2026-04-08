<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

include '../database/dbconfig.php';

// Get destination ID from URL
$destination_id = isset($_GET['destination_id']) && is_numeric($_GET['destination_id']) ? (int)$_GET['destination_id'] : 0;

if ($destination_id === 0) {
    $_SESSION['message'] = "Invalid destination ID";
    header("Location: add_destanition_on_admin.php");
    exit();
}

// Get destination info
$dest_query = $conn->prepare("SELECT name FROM destinations WHERE id = ?");
$dest_query->bind_param("i", $destination_id);
$dest_query->execute();
$dest_result = $dest_query->get_result();

if ($dest_result->num_rows === 0) {
    $_SESSION['message'] = "Destination not found";
    header("Location: add_destanition_on_admin.php");
    exit();
}

$destination = $dest_result->fetch_assoc();
$destination_name = $destination['name'];

// Handle form submission for adding/editing hotel
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Add new hotel
        if ($_POST['action'] === 'add') {
            $hotel_name = $conn->real_escape_string($_POST['hotel_name']);
            $hotel_type = $conn->real_escape_string($_POST['hotel_type']);
            $price_per_night = (float)$_POST['price_per_night'];
            $hotel_rating = (float)$_POST['hotel_rating'];
            $description = $conn->real_escape_string($_POST['description']);
            $amenities = isset($_POST['amenities']) ? json_encode(array_map([$conn, 'real_escape_string'], $_POST['amenities'])) : '[]';
            $address = $conn->real_escape_string($_POST['address']);
            $contact_number = $conn->real_escape_string($_POST['contact_number']);
            $check_in_time = $_POST['check_in_time'];
            $check_out_time = $_POST['check_out_time'];
            $free_cancellation = isset($_POST['free_cancellation']) ? 1 : 0;
            $breakfast_included = isset($_POST['breakfast_included']) ? 1 : 0;
            
            // Handle image upload
            $image_url = '';
            if (!empty($_FILES['image']['name'])) {
                $upload_dir = '../uploads/hotels/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $file_name = basename($_FILES['image']['name']);
                $file_path = $upload_dir . uniqid() . '_' . $file_name;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                    $image_url = str_replace('../', '', $file_path);
                }
            }

            $stmt = $conn->prepare("INSERT INTO hotels (destination_id, hotel_name, hotel_type, price_per_night, hotel_rating, description, amenities, image_url, address, contact_number, check_in_time, check_out_time, free_cancellation, breakfast_included) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issdssssssssii", $destination_id, $hotel_name, $hotel_type, $price_per_night, $hotel_rating, $description, $amenities, $image_url, $address, $contact_number, $check_in_time, $check_out_time, $free_cancellation, $breakfast_included);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Hotel added successfully!";
            } else {
                $_SESSION['message'] = "Error adding hotel: " . $conn->error;
            }
            $stmt->close();
        }
        
        // Edit hotel
        elseif ($_POST['action'] === 'edit' && isset($_POST['hotel_id'])) {
            $hotel_id = (int)$_POST['hotel_id'];
            $hotel_name = $conn->real_escape_string($_POST['hotel_name']);
            $hotel_type = $conn->real_escape_string($_POST['hotel_type']);
            $price_per_night = (float)$_POST['price_per_night'];
            $hotel_rating = (float)$_POST['hotel_rating'];
            $description = $conn->real_escape_string($_POST['description']);
            $amenities = isset($_POST['amenities']) ? json_encode(array_map([$conn, 'real_escape_string'], $_POST['amenities'])) : '[]';
            $address = $conn->real_escape_string($_POST['address']);
            $contact_number = $conn->real_escape_string($_POST['contact_number']);
            $check_in_time = $_POST['check_in_time'];
            $check_out_time = $_POST['check_out_time'];
            $free_cancellation = isset($_POST['free_cancellation']) ? 1 : 0;
            $breakfast_included = isset($_POST['breakfast_included']) ? 1 : 0;
            
            // Get current image
            $img_query = $conn->prepare("SELECT image_url FROM hotels WHERE id = ?");
            $img_query->bind_param("i", $hotel_id);
            $img_query->execute();
            $img_result = $img_query->get_result();
            $current = $img_result->fetch_assoc();
            $image_url = $current['image_url'];
            $img_query->close();
            
            // Handle new image upload
            if (!empty($_FILES['image']['name'])) {
                $upload_dir = '../uploads/hotels/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $file_name = basename($_FILES['image']['name']);
                $file_path = $upload_dir . uniqid() . '_' . $file_name;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                    // Delete old image if exists
                    if (!empty($image_url) && file_exists('../' . $image_url)) {
                        unlink('../' . $image_url);
                    }
                    $image_url = str_replace('../', '', $file_path);
                }
            }

            $stmt = $conn->prepare("UPDATE hotels SET hotel_name = ?, hotel_type = ?, price_per_night = ?, hotel_rating = ?, description = ?, amenities = ?, image_url = ?, address = ?, contact_number = ?, check_in_time = ?, check_out_time = ?, free_cancellation = ?, breakfast_included = ? WHERE id = ? AND destination_id = ?");
            $stmt->bind_param("ssdssssssssiiii", $hotel_name, $hotel_type, $price_per_night, $hotel_rating, $description, $amenities, $image_url, $address, $contact_number, $check_in_time, $check_out_time, $free_cancellation, $breakfast_included, $hotel_id, $destination_id);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Hotel updated successfully!";
            } else {
                $_SESSION['message'] = "Error updating hotel: " . $conn->error;
            }
            $stmt->close();
        }
        
        header("Location: manage_hotels.php?destination_id=" . $destination_id);
        exit();
    }
}

// Handle delete request
if (isset($_GET['delete'])) {
    $hotel_id = (int)$_GET['delete'];
    
    // Get image to delete
    $img_query = $conn->prepare("SELECT image_url FROM hotels WHERE id = ? AND destination_id = ?");
    $img_query->bind_param("ii", $hotel_id, $destination_id);
    $img_query->execute();
    $img_result = $img_query->get_result();
    if ($img_result->num_rows > 0) {
        $hotel = $img_result->fetch_assoc();
        if (!empty($hotel['image_url']) && file_exists('../' . $hotel['image_url'])) {
            unlink('../' . $hotel['image_url']);
        }
    }
    $img_query->close();
    
    $stmt = $conn->prepare("DELETE FROM hotels WHERE id = ? AND destination_id = ?");
    $stmt->bind_param("ii", $hotel_id, $destination_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Hotel deleted successfully!";
    } else {
        $_SESSION['message'] = "Error deleting hotel: " . $conn->error;
    }
    $stmt->close();
    
    header("Location: manage_hotels.php?destination_id=" . $destination_id);
    exit();
}

// Fetch all hotels for this destination
$hotels_query = $conn->prepare("SELECT * FROM hotels WHERE destination_id = ? ORDER BY id DESC");
$hotels_query->bind_param("i", $destination_id);
$hotels_query->execute();
$hotels_result = $hotels_query->get_result();

// Get admin info
$admin_id = $_SESSION['admin_id'];
$admin_query = $conn->prepare("SELECT name, email, profile_pic FROM admin WHERE id = ?");
$admin_query->bind_param("i", $admin_id);
$admin_query->execute();
$admin_result = $admin_query->get_result();
$admin = $admin_result->fetch_assoc() ?? ['name' => 'Unknown', 'email' => '', 'profile_pic' => NULL];
$admin_query->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Hotels - <?= htmlspecialchars($destination_name) ?></title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-base: #f1f5f9;
            --bg-surface: rgba(255, 255, 255, 0.95);
            --text-main: #0f172a;
            --text-muted: #475569;
            --primary: #4f46e5;
            --secondary: #06b6d4;
            --nav-bg: rgba(255, 255, 255, 0.95);
            --card-border: rgba(79, 70, 229, 0.15);
            --shadow-color: rgba(15, 23, 42, 0.08);
            --glow-color: rgba(6, 182, 212, 0.4);
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
        }

        body.dark-mode {
            --bg-base: #020617;
            --bg-surface: rgba(30, 41, 59, 0.9);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --primary: #818cf8;
            --secondary: #22d3ee;
            --nav-bg: rgba(15, 23, 42, 0.95);
            --card-border: rgba(255, 255, 255, 0.1);
            --shadow-color: rgba(0, 0, 0, 0.6);
            --glow-color: rgba(34, 211, 238, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: var(--bg-base);
            color: var(--text-main);
            line-height: 1.6;
            min-height: 100vh;
            transition: background-color 0.3s ease;
        }

        /* Navbar */
        .navbar {
            background: var(--nav-bg);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--card-border);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: 800;
            text-decoration: none;
            color: var(--text-main);
        }

        .logo i {
            color: var(--primary);
            transform: rotate(-10deg);
        }

        .brand-text .trip { color: var(--text-main); }
        .brand-text .mate { color: var(--secondary); }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .theme-toggle {
            background: var(--bg-surface);
            border: 1px solid var(--card-border);
            color: var(--text-main);
            width: 44px;
            height: 44px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .theme-toggle:hover {
            border-color: var(--secondary);
            color: var(--secondary);
            transform: rotate(20deg);
        }

        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2.2rem;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--card-border);
            color: var(--text-main);
        }

        .btn-outline:hover {
            border-color: var(--secondary);
            color: var(--secondary);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-left: 4px solid var(--success);
            color: var(--success);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border-left: 4px solid var(--danger);
            color: var(--danger);
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Widget Card */
        .widget-card {
            background: var(--bg-surface);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 20px 40px var(--shadow-color);
            backdrop-filter: blur(10px);
            margin-bottom: 2rem;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--card-border);
            padding-bottom: 1rem;
        }

        .card-header h2 {
            font-size: 1.8rem;
            color: var(--text-main);
        }

        /* Forms */
        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--card-border);
            border-radius: 12px;
            background: var(--bg-surface);
            color: var(--text-main);
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 4px var(--glow-color);
        }

        select.form-control option {
            background: var(--bg-surface);
            color: var(--text-main);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        /* Status Badge */
        .status-badge {
            background: var(--bg-base);
            color: var(--text-main);
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
        }

        .status-active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        /* Hotels Grid */
        .hotels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }

        .hotel-card {
            background: var(--bg-surface);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            overflow: hidden;
            transition: all 0.3s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .hotel-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px var(--shadow-color);
            border-color: var(--secondary);
        }

        .hotel-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }

        .hotel-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .hotel-card:hover .hotel-image img {
            transform: scale(1.05);
        }

        .hotel-type-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .hotel-info {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .hotel-info h3 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            color: var(--text-main);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--bg-surface);
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 24px;
            padding: 2rem;
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .modal-close:hover {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        /* Utilities */
        .fade-in {
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .text-muted {
            color: var(--text-muted);
        }

        .mb-4 {
            margin-bottom: 1rem;
        }

        .mt-4 {
            margin-top: 1rem;
        }

        .w-100 {
            width: 100%;
        }

        .text-center {
            text-align: center;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 0 1rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .hotels-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="logo">
                <i class="fa-solid fa-paper-plane"></i>
                <span class="brand-text"><span class="trip">Trip</span><span class="mate">Mate</span></span>
            </a>
            
            <div class="nav-right">
                <span style="color: var(--text-muted);"><?= htmlspecialchars($admin['name'] ?? 'Admin') ?></span>
                <button class="theme-toggle" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1><i class="fas fa-hotel" style="color: var(--secondary);"></i> Manage Hotels</h1>
                <p class="text-muted"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($destination_name) ?></p>
            </div>
            <a href="add_destanition_on_admin.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Destinations
            </a>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert <?= strpos($_SESSION['message'], 'Error') === false ? 'alert-success' : 'alert-danger' ?> fade-in">
                <i class="fas fa-<?= strpos($_SESSION['message'], 'Error') === false ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= $_SESSION['message'] ?>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <!-- Add Hotel Form -->
        <div class="widget-card fade-in">
            <div class="card-header">
                <h2><i class="fas fa-plus-circle" style="color: var(--secondary);"></i> Add New Hotel</h2>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="hotel_name">Hotel Name *</label>
                            <input type="text" id="hotel_name" name="hotel_name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="hotel_type">Hotel Type *</label>
                            <select id="hotel_type" name="hotel_type" class="form-control" required>
                                <option value="low">Low Budget</option>
                                <option value="medium">Medium Budget</option>
                                <option value="high">High Budget / Luxury</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="price_per_night">Price Per Night (₹) *</label>
                            <input type="number" id="price_per_night" name="price_per_night" step="0.01" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="hotel_rating">Hotel Rating (0-5)</label>
                            <input type="number" id="hotel_rating" name="hotel_rating" step="0.1" min="0" max="5" class="form-control" value="0">
                        </div>
                        
                        <div class="form-group" style="grid-column: span 2;">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group" style="grid-column: span 2;">
                            <label for="amenities">Amenities (Select multiple)</label>
                            <select id="amenities" name="amenities[]" class="form-control" multiple style="min-height: 120px;">
                                <option value="Free WiFi">Free WiFi</option>
                                <option value="Swimming Pool">Swimming Pool</option>
                                <option value="Restaurant">Restaurant</option>
                                <option value="Room Service">Room Service</option>
                                <option value="Gym">Gym</option>
                                <option value="Spa">Spa</option>
                                <option value="Parking">Parking</option>
                                <option value="Air Conditioning">Air Conditioning</option>
                                <option value="TV">TV</option>
                                <option value="Shared Kitchen">Shared Kitchen</option>
                                <option value="Lockers">Lockers</option>
                                <option value="Lounge Area">Lounge Area</option>
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Hotel Image</label>
                            <input type="file" id="image" name="image" class="form-control" accept="image/*">
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="contact_number">Contact Number</label>
                            <input type="text" id="contact_number" name="contact_number" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="check_in_time">Check-in Time</label>
                            <input type="time" id="check_in_time" name="check_in_time" class="form-control" value="12:00">
                        </div>
                        
                        <div class="form-group">
                            <label for="check_out_time">Check-out Time</label>
                            <input type="time" id="check_out_time" name="check_out_time" class="form-control" value="11:00">
                        </div>
                        
                        <div class="form-group" style="grid-column: span 2; display: flex; gap: 2rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="free_cancellation" value="1" checked> Free Cancellation
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="breakfast_included" value="1"> Breakfast Included
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Hotel
                    </button>
                </form>
            </div>
        </div>

        <!-- Existing Hotels -->
        <div class="widget-card fade-in">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Existing Hotels</h2>
                <span class="status-badge status-active">Total: <?= $hotels_result->num_rows ?></span>
            </div>
            <div class="card-body">
                <?php if ($hotels_result->num_rows > 0): ?>
                    <div class="hotels-grid">
                        <?php while ($hotel = $hotels_result->fetch_assoc()): 
                            $amenities_array = json_decode($hotel['amenities'] ?? '[]', true);
                        ?>
                            <div class="hotel-card">
                                <div class="hotel-image">
                                    <?php if (!empty($hotel['image_url']) && file_exists('../' . $hotel['image_url'])): ?>
                                        <img src="../<?= htmlspecialchars($hotel['image_url']) ?>" alt="<?= htmlspecialchars($hotel['hotel_name']) ?>">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, var(--primary)20, var(--secondary)20); display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-hotel" style="font-size: 3rem; color: var(--secondary)40;"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="hotel-type-badge">
                                        <?= ucfirst($hotel['hotel_type']) ?> Budget
                                    </div>
                                </div>
                                
                                <div class="hotel-info">
                                    <h3><?= htmlspecialchars($hotel['hotel_name']) ?></h3>
                                    
                                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                        <span style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; padding: 4px 10px; border-radius: 20px; font-size: 0.9rem; font-weight: 600;">
                                            <i class="fas fa-star"></i> <?= number_format($hotel['hotel_rating'], 1) ?>
                                        </span>
                                        <span style="font-weight: 700; color: var(--text-main); font-size: 1.2rem;">
                                            ₹<?= number_format($hotel['price_per_night']) ?>
                                            <small style="font-size: 0.8rem; color: var(--text-muted);">/night</small>
                                        </span>
                                    </div>
                                    
                                    <?php if (!empty($hotel['address'])): ?>
                                        <p style="margin-bottom: 0.5rem; color: var(--text-muted); font-size: 0.9rem;">
                                            <i class="fas fa-map-marker-alt" style="color: var(--secondary);"></i> <?= htmlspecialchars($hotel['address']) ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($hotel['contact_number'])): ?>
                                        <p style="margin-bottom: 1rem; color: var(--text-muted); font-size: 0.9rem;">
                                            <i class="fas fa-phone" style="color: var(--secondary);"></i> <?= htmlspecialchars($hotel['contact_number']) ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($amenities_array) && is_array($amenities_array)): ?>
                                        <div style="margin-bottom: 1rem;">
                                            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                                <?php foreach (array_slice($amenities_array, 0, 4) as $amenity): ?>
                                                    <span style="background: var(--bg-base); padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; color: var(--text-muted);">
                                                        <?= htmlspecialchars($amenity) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                                <?php if (count($amenities_array) > 4): ?>
                                                    <span style="background: var(--bg-base); padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; color: var(--text-muted);">
                                                        +<?= count($amenities_array) - 4 ?> more
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div style="display: flex; gap: 0.8rem; margin-bottom: 1rem;">
                                        <span style="display: flex; align-items: center; gap: 5px; font-size: 0.85rem; color: var(--text-muted);">
                                            <i class="fas fa-clock"></i> Check-in: <?= substr($hotel['check_in_time'] ?? '12:00', 0, 5) ?>
                                        </span>
                                        <span style="display: flex; align-items: center; gap: 5px; font-size: 0.85rem; color: var(--text-muted);">
                                            <i class="fas fa-clock"></i> Check-out: <?= substr($hotel['check_out_time'] ?? '11:00', 0, 5) ?>
                                        </span>
                                    </div>
                                    
                                    <div style="display: flex; gap: 0.5rem; margin-top: auto; border-top: 1px solid var(--card-border); padding-top: 1.5rem;">
                                        <button onclick='editHotel(<?= json_encode($hotel) ?>)' class="btn btn-outline" style="flex: 1;">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <a href="?destination_id=<?= $destination_id ?>&delete=<?= $hotel['id'] ?>" class="btn btn-danger" style="flex: 1;" onclick="return confirm('Are you sure you want to delete this hotel?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center" style="padding: 4rem 2rem;">
                        <i class="fas fa-hotel" style="font-size: 4rem; margin-bottom: 1.5rem; color: var(--text-muted); opacity: 0.5;"></i>
                        <h3 style="color: var(--text-main); font-size: 1.5rem; margin-bottom: 0.5rem;">No hotels added yet</h3>
                        <p style="color: var(--text-muted);">Use the form above to add your first hotel</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Hotel Modal -->
    <div class="modal" id="editHotelModal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
            
            <h2 style="font-size: 1.8rem; margin-bottom: 2rem; color: var(--text-main);">
                <i class="fas fa-edit" style="color: var(--secondary);"></i> Edit Hotel
            </h2>
            
            <form method="POST" enctype="multipart/form-data" id="editHotelForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="hotel_id" id="edit_hotel_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_hotel_name">Hotel Name *</label>
                        <input type="text" id="edit_hotel_name" name="hotel_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_hotel_type">Hotel Type *</label>
                        <select id="edit_hotel_type" name="hotel_type" class="form-control" required>
                            <option value="low">Low Budget</option>
                            <option value="medium">Medium Budget</option>
                            <option value="high">High Budget / Luxury</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_price_per_night">Price Per Night (₹) *</label>
                        <input type="number" id="edit_price_per_night" name="price_per_night" step="0.01" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_hotel_rating">Hotel Rating (0-5)</label>
                        <input type="number" id="edit_hotel_rating" name="hotel_rating" step="0.1" min="0" max="5" class="form-control">
                    </div>
                    
                    <div class="form-group" style="grid-column: span 2;">
                        <label for="edit_description">Description</label>
                        <textarea id="edit_description" name="description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group" style="grid-column: span 2;">
                        <label for="edit_amenities">Amenities</label>
                        <select id="edit_amenities" name="amenities[]" class="form-control" multiple style="min-height: 120px;">
                            <option value="Free WiFi">Free WiFi</option>
                            <option value="Swimming Pool">Swimming Pool</option>
                            <option value="Restaurant">Restaurant</option>
                            <option value="Room Service">Room Service</option>
                            <option value="Gym">Gym</option>
                            <option value="Spa">Spa</option>
                            <option value="Parking">Parking</option>
                            <option value="Air Conditioning">Air Conditioning</option>
                            <option value="TV">TV</option>
                            <option value="Shared Kitchen">Shared Kitchen</option>
                            <option value="Lockers">Lockers</option>
                            <option value="Lounge Area">Lounge Area</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_image">Hotel Image (Leave empty to keep current)</label>
                        <input type="file" id="edit_image" name="image" class="form-control" accept="image/*">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_address">Address</label>
                        <input type="text" id="edit_address" name="address" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_contact_number">Contact Number</label>
                        <input type="text" id="edit_contact_number" name="contact_number" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_check_in_time">Check-in Time</label>
                        <input type="time" id="edit_check_in_time" name="check_in_time" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_check_out_time">Check-out Time</label>
                        <input type="time" id="edit_check_out_time" name="check_out_time" class="form-control">
                    </div>
                    
                    <div class="form-group" style="grid-column: span 2; display: flex; gap: 2rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" name="free_cancellation" id="edit_free_cancellation" value="1"> Free Cancellation
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" name="breakfast_included" id="edit_breakfast_included" value="1"> Breakfast Included
                        </label>
                    </div>
                </div>
                
                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Hotel
                    </button>
                    <button type="button" onclick="closeEditModal()" class="btn btn-outline">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;
        const icon = themeToggle.querySelector('i');

        // Check for saved theme preference
        if (localStorage.getItem('admin-theme') === 'dark') {
            body.classList.add('dark-mode');
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        }

        themeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            if (body.classList.contains('dark-mode')) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
                localStorage.setItem('admin-theme', 'dark');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
                localStorage.setItem('admin-theme', 'light');
            }
        });

        // Edit Hotel Function
        function editHotel(hotel) {
            document.getElementById('edit_hotel_id').value = hotel.id;
            document.getElementById('edit_hotel_name').value = hotel.hotel_name;
            document.getElementById('edit_hotel_type').value = hotel.hotel_type;
            document.getElementById('edit_price_per_night').value = hotel.price_per_night;
            document.getElementById('edit_hotel_rating').value = hotel.hotel_rating;
            document.getElementById('edit_description').value = hotel.description || '';
            document.getElementById('edit_address').value = hotel.address || '';
            document.getElementById('edit_contact_number').value = hotel.contact_number || '';
            document.getElementById('edit_check_in_time').value = hotel.check_in_time ? hotel.check_in_time.substring(0,5) : '12:00';
            document.getElementById('edit_check_out_time').value = hotel.check_out_time ? hotel.check_out_time.substring(0,5) : '11:00';
            
            // Set checkboxes
            document.getElementById('edit_free_cancellation').checked = hotel.free_cancellation == 1;
            document.getElementById('edit_breakfast_included').checked = hotel.breakfast_included == 1;
            
            // Set amenities
            const amenities = JSON.parse(hotel.amenities || '[]');
            const select = document.getElementById('edit_amenities');
            for (let option of select.options) {
                option.selected = amenities.includes(option.value);
            }
            
            document.getElementById('editHotelModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editHotelModal').classList.remove('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editHotelModal');
            if (event.target == modal) {
                modal.classList.remove('active');
            }
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
<?php
$conn->close();
?>