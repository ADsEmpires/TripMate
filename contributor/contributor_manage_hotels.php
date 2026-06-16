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

if (!isset($_SESSION['temp_destination'])) {
    $_SESSION['message'] = "Please fill in the destination details first.";
    header("Location: contributor_add_destination.php");
    exit();
}

$destination_name = $_SESSION['temp_destination']['name'];

if (!isset($_SESSION['temp_hotels'])) {
    $_SESSION['temp_hotels'] = [];
}

// Handle final submit action
if (isset($_GET['action']) && $_GET['action'] === 'finalize') {
    // Start transaction
    $conn->begin_transaction();
    try {
        $dest = $_SESSION['temp_destination'];
        
        // Get next destination ID
        $id_query = $conn->query("SELECT MAX(id) AS max_id FROM destinations");
        $next_id = 1;
        if ($id_query && $row = $id_query->fetch_assoc()) {
            $next_id = ($row['max_id'] ?? 0) + 1;
        }
        
        // Insert destination
        $insert_query = "INSERT INTO destinations (id, name, type, description, location, budget, image_urls, map_link, season, people, submitted_by_type, submitted_by_id, submission_status, contributor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'contributor', ?, 'pending', ?)";
        $stmt = $conn->prepare($insert_query);
        if (!$stmt) {
            throw new Exception("Destination prepare failed: " . $conn->error);
        }
        $stmt->bind_param("issssdssssii", $next_id, $dest['name'], $dest['type'], $dest['description'], $dest['location'], $dest['budget'], $dest['image_urls'], $dest['map_link'], $dest['season'], $dest['people'], $contributor_id, $contributor_id);
        if (!$stmt->execute()) {
            throw new Exception("Destination execute failed: " . $stmt->error);
        }
        $stmt->close();
        
        // Link destination to contributor
        $link_stmt = $conn->prepare("INSERT INTO contributor_destinations (contributor_id, destination_id, status) VALUES (?, ?, 'pending')");
        if (!$link_stmt) {
            throw new Exception("Link prepare failed: " . $conn->error);
        }
        $link_stmt->bind_param("ii", $contributor_id, $next_id);
        if (!$link_stmt->execute()) {
            throw new Exception("Link execute failed: " . $link_stmt->error);
        }
        $link_stmt->close();

        // Also insert a default city in destination_cities to satisfy foreign key constraints
        $city_stmt = $conn->prepare("INSERT INTO destination_cities (id, destination_id, city_name) VALUES (?, ?, ?)");
        if (!$city_stmt) {
            throw new Exception("City prepare failed: " . $conn->error);
        }
        $city_stmt->bind_param("iis", $next_id, $next_id, $dest['name']);
        if (!$city_stmt->execute()) {
            throw new Exception("City execute failed: " . $city_stmt->error);
        }
        $city_stmt->close();
        
        // Insert flights
        if (isset($_SESSION['temp_flights']) && !empty($_SESSION['temp_flights'])) {
            $flight_stmt = $conn->prepare("INSERT INTO flights (destination_id, city_id, from_city, to_city, airline, flight_type, price, duration, stops, departure_time, arrival_time, flight_class, baggage_allowance, refundable, meal_included) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$flight_stmt) {
                throw new Exception("Flight prepare failed: " . $conn->error);
            }
            foreach ($_SESSION['temp_flights'] as $flight) {
                $price = (float)$flight['price_per_person'];
                $duration = (float)$flight['duration_hours'];
                $stops = (int)$flight['stops'];
                $refundable = (int)$flight['refundable'];
                $meal_included = (int)$flight['meal_included'];

                $types = "iissssddissssii"; 
                $flight_stmt->bind_param($types, $next_id, $next_id, $flight['departure_city'], $dest['name'], $flight['airline'], $flight['flight_type'], $price, $duration, $stops, $flight['departure_time'], $flight['arrival_time'], $flight['flight_class'], $flight['baggage_allowance'], $refundable, $meal_included);
                if (!$flight_stmt->execute()) {
                    throw new Exception("Flight execute failed: " . $flight_stmt->error);
                }
            }
            $flight_stmt->close();
        }
        
        // Insert hotels
        if (isset($_SESSION['temp_hotels']) && !empty($_SESSION['temp_hotels'])) {
            $hotel_stmt = $conn->prepare("INSERT INTO hotels (destination_id, city_id, hotel_name, name, hotel_type, price_per_night, hotel_rating, stars, description, amenities, image_url, address, contact_number, check_in_time, check_out_time, free_cancellation, breakfast_included) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$hotel_stmt) {
                throw new Exception("Hotel prepare failed: " . $conn->error);
            }
            foreach ($_SESSION['temp_hotels'] as $hotel) {
                $stars = (int)$hotel['hotel_rating'];
                $hotel_stmt->bind_param("iisssddisssssssii", $next_id, $next_id, $hotel['hotel_name'], $hotel['hotel_name'], $hotel['hotel_type'], $hotel['price_per_night'], $hotel['hotel_rating'], $stars, $hotel['description'], $hotel['amenities'], $hotel['image_url'], $hotel['address'], $hotel['contact_number'], $hotel['check_in_time'], $hotel['check_out_time'], $hotel['free_cancellation'], $hotel['breakfast_included']);
                if (!$hotel_stmt->execute()) {
                    throw new Exception("Hotel execute failed: " . $hotel_stmt->error);
                }
            }
            $hotel_stmt->close();
        }
        
        $conn->commit();
        
        unset($_SESSION['temp_destination']);
        unset($_SESSION['temp_flights']);
        unset($_SESSION['temp_hotels']);
        
        header("Location: contributor_add_destination.php?msg=completed");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = "Error finalizing package: " . $e->getMessage();
        header("Location: contributor_manage_hotels.php");
        exit();
    }
}

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
                $upload_dir = __DIR__ . '/../uploads/hotels/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $file_name = preg_replace("/[^a-zA-Z0-9.-]/", "_", basename($_FILES['image']['name']));
                $file_path = 'uploads/hotels/' . uniqid() . '_' . $file_name;
                $full_path = __DIR__ . '/../' . $file_path;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $full_path)) {
                    $image_url = $file_path;
                }
            }

            $new_hotel = [
                'id' => 'ht_' . uniqid(),
                'hotel_name' => $hotel_name,
                'hotel_type' => $hotel_type,
                'price_per_night' => $price_per_night,
                'hotel_rating' => $hotel_rating,
                'description' => $description,
                'amenities' => $amenities,
                'image_url' => $image_url,
                'address' => $address,
                'contact_number' => $contact_number,
                'check_in_time' => $check_in_time,
                'check_out_time' => $check_out_time,
                'free_cancellation' => $free_cancellation,
                'breakfast_included' => $breakfast_included
            ];

            $_SESSION['temp_hotels'][] = $new_hotel;
            $_SESSION['message'] = "Hotel added to draft successfully!";
        }
        
        // Edit hotel
        elseif ($_POST['action'] === 'edit' && isset($_POST['hotel_id'])) {
            $hotel_id = $_POST['hotel_id'];
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
            
            // Find existing image
            $image_url = '';
            foreach ($_SESSION['temp_hotels'] as $hotel) {
                if ($hotel['id'] === $hotel_id) {
                    $image_url = $hotel['image_url'];
                    break;
                }
            }
            
            // Handle new image upload
            if (!empty($_FILES['image']['name'])) {
                $upload_dir = __DIR__ . '/../uploads/hotels/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $file_name = preg_replace("/[^a-zA-Z0-9.-]/", "_", basename($_FILES['image']['name']));
                $file_path = 'uploads/hotels/' . uniqid() . '_' . $file_name;
                $full_path = __DIR__ . '/../' . $file_path;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $full_path)) {
                    // Delete old image if exists
                    if (!empty($image_url) && file_exists(__DIR__ . '/../' . $image_url)) {
                        unlink(__DIR__ . '/../' . $image_url);
                    }
                    $image_url = $file_path;
                }
            }

            foreach ($_SESSION['temp_hotels'] as $key => $hotel) {
                if ($hotel['id'] === $hotel_id) {
                    $_SESSION['temp_hotels'][$key] = [
                        'id' => $hotel_id,
                        'hotel_name' => $hotel_name,
                        'hotel_type' => $hotel_type,
                        'price_per_night' => $price_per_night,
                        'hotel_rating' => $hotel_rating,
                        'description' => $description,
                        'amenities' => $amenities,
                        'image_url' => $image_url,
                        'address' => $address,
                        'contact_number' => $contact_number,
                        'check_in_time' => $check_in_time,
                        'check_out_time' => $check_out_time,
                        'free_cancellation' => $free_cancellation,
                        'breakfast_included' => $breakfast_included
                    ];
                    $_SESSION['message'] = "Hotel updated in draft successfully!";
                    break;
                }
            }
        }
        
        header("Location: contributor_manage_hotels.php");
        exit();
    }
}

// Handle delete request
if (isset($_GET['delete'])) {
    $hotel_id = $_GET['delete'];
    
    foreach ($_SESSION['temp_hotels'] as $key => $hotel) {
        if ($hotel['id'] === $hotel_id) {
            // Delete image file
            if (!empty($hotel['image_url']) && file_exists(__DIR__ . '/../' . $hotel['image_url'])) {
                unlink(__DIR__ . '/../' . $hotel['image_url']);
            }
            unset($_SESSION['temp_hotels'][$key]);
            $_SESSION['temp_hotels'] = array_values($_SESSION['temp_hotels']);
            $_SESSION['message'] = "Hotel removed from draft successfully!";
            break;
        }
    }
    
    header("Location: contributor_manage_hotels.php");
    exit();
}

$hotels_list = $_SESSION['temp_hotels'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Hotels – TripMate Contributor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #f8fafc; --bg-surface: #ffffff; --text-main: #0f172a; --text-muted: #475569;
            --primary: #4f46e5; --secondary: #06b6d4; --nav-bg: rgba(255, 255, 255, 0.75);
            --card-border: rgba(79, 70, 229, 0.15); --shadow-color: rgba(15, 23, 42, 0.08); --glow-color: rgba(6, 182, 212, 0.4);
            --danger: #ef4444; --success: #10b981; --warning: #f59e0b; --accent: #f59e0b;
        }
        body.dark-mode {
            --bg-base: #09090b; --bg-surface: #18181b; --text-main: #f8fafc; --text-muted: #cbd5e1;
            --primary: #818cf8; --secondary: #22d3ee; --nav-bg: rgba(24, 24, 27, 0.75);
            --card-border: rgba(255, 255, 255, 0.1); --shadow-color: rgba(0, 0, 0, 0.6); --glow-color: rgba(34, 211, 238, 0.3);
            --danger: #f87171; --success: #34d399; --warning: #fbbf24;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-base); color: var(--text-main); min-height: 100vh; overflow-x: hidden; transition: background 0.4s, color 0.4s; padding-top: 120px; }
        body::before { content: ''; position: fixed; top: -10%; left: -10%; width: 50vw; height: 50vw; background: radial-gradient(circle, var(--glow-color) 0%, transparent 60%); opacity: 0.5; pointer-events: none; }

        .navbar { position: fixed; top: 20px; left: 5%; width: 90%; height: 70px; background: var(--nav-bg); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid var(--card-border); border-radius: 50px; display: flex; justify-content: space-between; align-items: center; padding: 0 30px; z-index: 1000; box-shadow: 0 10px 30px var(--shadow-color); }
        .logo { display: flex; align-items: center; gap: 12px; font-size: 1.4rem; font-weight: 800; text-decoration: none; color: var(--text-main); }
        .logo i { color: var(--primary); font-size: 1.5rem; transform: rotate(-10deg); transition: transform 0.3s; }
        .logo:hover i { transform: rotate(0deg) scale(1.1); }
        .brand-text .trip { color: var(--text-main); } .brand-text .mate { color: var(--secondary); }
        .nav-badge { background: var(--glow-color); color: var(--primary); padding: 4px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; border: 1px solid var(--card-border); margin-left: 15px; }
        .nav-links { display: flex; align-items: center; gap: 18px; list-style: none; margin-left: auto; margin-right: 20px; }
        .nav-link { color: var(--text-muted); text-decoration: none; font-weight: 600; transition: color 0.2s; display: flex; align-items: center; gap: 6px; font-size: 0.95rem; }
        .nav-link:hover { color: var(--secondary); } .nav-link.active { color: var(--primary); }
        .theme-toggle { background: var(--bg-surface); border: 1px solid var(--card-border); color: var(--text-main); width: 44px; height: 44px; border-radius: 50%; cursor: pointer; display: flex; justify-content: center; align-items: center; transition: all 0.3s; box-shadow: 0 4px 10px var(--shadow-color); flex-shrink: 0; }
        .theme-toggle:hover { transform: rotate(20deg) scale(1.1); color: var(--primary); border-color: var(--primary); }

        .page { max-width: 1200px; margin: 0 auto; padding: 0 1.5rem 2rem; position: relative; z-index: 1; }
        .widget-card { background: var(--bg-surface); border: 1px solid var(--card-border); border-radius: 24px; padding: 2rem; box-shadow: 0 15px 35px var(--shadow-color); }
        .form-group { margin-bottom: 1.8rem; }
        .form-group label { display: block; margin-bottom: 0.6rem; font-weight: 700; color: var(--text-main); font-size: 0.95rem; }
        
        .form-control { width: 100%; padding: 0.9rem 1.2rem; border: 1px solid var(--card-border); border-radius: 12px; font-size: 1rem; background: var(--bg-base); color: var(--text-main); transition: all 0.3s; font-family: inherit; }
        .form-control:focus { border-color: var(--secondary); outline: none; box-shadow: 0 0 0 4px var(--glow-color); background: var(--bg-surface); }
        body.dark-mode .form-control { background-color: var(--bg-surface); color: var(--text-main); }
        body.dark-mode select.form-control option { background: var(--bg-surface); color: var(--text-main); }
        textarea.form-control { min-height: 100px; resize: vertical; line-height: 1.6; }

        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.8rem 1.8rem; border-radius: 12px; font-weight: 700; cursor: pointer; transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1); border: none; gap: 8px; font-size: 1rem; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(245, 158, 11, 0.4); }
        .btn-outline { background: transparent; border: 2px solid var(--card-border); color: var(--text-main); }
        .btn-outline:hover { background: var(--bg-base); border-color: var(--primary); color: var(--primary); }
        .btn-danger { background: transparent; border: 2px solid rgba(239, 68, 68, 0.3); color: var(--danger); }
        .btn-danger:hover { background: var(--danger); color: white; border-color: var(--danger); }

        .alert { padding: 1.2rem 1.5rem; border-radius: 16px; margin-bottom: 2rem; border-left: 5px solid transparent; font-weight: 600; display: flex; align-items: center; gap: 12px; }
        .alert-success { background-color: var(--bg-surface); color: var(--success); border: 1px solid var(--card-border); border-left-color: var(--success); }
        .alert-danger { background-color: var(--bg-surface); color: var(--danger); border: 1px solid var(--card-border); border-left-color: var(--danger); }

        .status-badge { display: inline-flex; align-items: center; padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.9rem; font-weight: 700; }
        .status-active { background: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.2); }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.6s cubic-bezier(0.25, 1, 0.5, 1) forwards; }

        .hotel-card { transition: transform 0.3s, box-shadow 0.3s; }
        .hotel-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px var(--shadow-color); }

        footer { text-align: center; padding: 2rem; font-size: 0.85rem; color: var(--text-muted); border-top: 1px solid var(--card-border); margin-top: 3rem; position: relative; z-index: 1; }
    </style>
</head>
<body>

<nav class="navbar" role="navigation">
    <a href="../main/index.html" class="logo">
        <i class="fa-solid fa-paper-plane"></i>
        <span class="brand-text"><span class="trip">Trip</span><span class="mate">Mate</span></span>
    </a>
    <span class="nav-badge">Contributor</span>
    <ul class="nav-links">
        <a href="contributor_dashboard.php" class="nav-link"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="contributor_add_destination.php" class="nav-link active"><i class="fa-solid fa-plus"></i> Destinations</a>
        <a href="contributor_wallet.php" class="nav-link"><i class="fa-solid fa-wallet"></i> Wallet</a>
        <a href="contributor_profile.php" class="nav-link"><i class="fa-solid fa-user"></i> Profile</a>
        <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--secondary)); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; color: #fff; overflow: hidden; border: 2px solid var(--bg-surface); margin-left: 10px;">
            <?php if ($contributor_pic): ?>
                <img src="<?= htmlspecialchars($contributor_pic) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
            <?php else: ?>
                <?= strtoupper(substr($contributor_name, 0, 1)) ?>
            <?php endif; ?>
        </div>
        <span style="font-size: 0.9rem; font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($contributor_name) ?></span>
        <a href="contributor_logout.php" class="nav-link" style="margin-left: 5px; color: var(--danger);"><i class="fa-solid fa-right-from-bracket"></i></a>
    </ul>
    <button class="theme-toggle" id="themeToggle" aria-label="Switch mode"><i class="fas fa-moon"></i></button>
</nav>

<div class="page">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 2.2rem; color: var(--text-main); margin-bottom: 0.5rem;">
                <i class="fas fa-hotel" style="color: #f59e0b;"></i> Manage Hotels
            </h1>
            <p style="color: var(--text-muted); font-size: 1.1rem;">
                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($destination_name) ?>
            </p>
        </div>
        <a href="contributor_add_destination.php" class="btn btn-outline" style="border-radius: 50px;">
            <i class="fas fa-arrow-left"></i> Back to Destinations
        </a>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert <?= strpos($_SESSION['message'], 'Error') === false ? 'alert-success' : 'alert-danger' ?> fade-in">
            <i class="fas fa-<?= strpos($_SESSION['message'], 'Error') === false ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= $_SESSION['message'] ?>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <div class="widget-card fade-in" style="margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--card-border); padding-bottom: 1rem;">
            <h2 style="font-size: 1.8rem; color: var(--text-main);"><i class="fas fa-plus-circle" style="color: #f59e0b;"></i> Add New Hotel</h2>
        </div>
        <div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
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
                        <select id="amenities" name="amenities[]" class="form-control" multiple style="height: auto; min-height: 120px;">
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
                        <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;"><i class="fas fa-info-circle"></i> Hold Ctrl/Cmd to select multiple</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Hotel Image</label>
                        <input type="file" id="image" name="image" class="form-control" accept="image/*" style="padding: 0.6rem;">
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
                    
                    <div class="form-group" style="display: flex; gap: 2rem; align-items: center;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0;">
                            <input type="checkbox" name="free_cancellation" value="1" checked> Free Cancellation
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0;">
                            <input type="checkbox" name="breakfast_included" value="1"> Breakfast Included
                        </label>
                    </div>
                </div>
                
                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary" style="border-radius: 50px;">
                        <i class="fas fa-save"></i> Add Hotel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="widget-card fade-in">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--card-border); padding-bottom: 1rem;">
            <h2 style="font-size: 1.8rem; color: var(--text-main);"><i class="fas fa-list"></i> Existing Hotels</h2>
            <span class="status-badge status-active">Total: <?= count($hotels_list) ?></span>
        </div>
        <div>
            <?php if (count($hotels_list) > 0): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 2rem;">
                    <?php foreach ($hotels_list as $hotel): 
                        $amenities_array = json_decode($hotel['amenities'] ?? '[]', true);
                    ?>
                        <div class="hotel-card widget-card" style="padding: 0; overflow: hidden;">
                            <div style="height: 200px; overflow: hidden; position: relative;">
                                <?php if (!empty($hotel['image_url'])): ?>
                                    <img src="../<?= htmlspecialchars($hotel['image_url']) ?>" alt="<?= htmlspecialchars($hotel['hotel_name']) ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #f59e0b20, #d9770620); display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-hotel" style="font-size: 3rem; color: #f59e0b40;"></i>
                                    </div>
                                <?php endif; ?>
                                <div style="position: absolute; top: 15px; right: 15px; background: rgba(0,0,0,0.7); color: white; padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 700;">
                                    <?= ucfirst($hotel['hotel_type']) ?> Budget
                                </div>
                            </div>
                            
                            <div style="padding: 1.5rem;">
                                <h3 style="font-size: 1.3rem; margin-bottom: 0.5rem; color: var(--text-main);"><?= htmlspecialchars($hotel['hotel_name']) ?></h3>
                                
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
                                        <i class="fas fa-map-marker-alt" style="color: #f59e0b;"></i> <?= htmlspecialchars($hotel['address']) ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if (!empty($hotel['contact_number'])): ?>
                                    <p style="margin-bottom: 1rem; color: var(--text-muted); font-size: 0.9rem;">
                                        <i class="fas fa-phone" style="color: #f59e0b;"></i> <?= htmlspecialchars($hotel['contact_number']) ?>
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
                                        <i class="fas fa-clock"></i> Check-in: <?= substr($hotel['check_in_time'] ?? '12:00:00', 0, 5) ?>
                                    </span>
                                    <span style="display: flex; align-items: center; gap: 5px; font-size: 0.85rem; color: var(--text-muted);">
                                        <i class="fas fa-clock"></i> Check-out: <?= substr($hotel['check_out_time'] ?? '11:00:00', 0, 5) ?>
                                    </span>
                                </div>
                                
                                <div style="display: flex; gap: 0.5rem; margin-top: 1.5rem; border-top: 1px solid var(--card-border); padding-top: 1.5rem;">
                                    <button onclick="editHotel(<?= htmlspecialchars(json_encode($hotel)) ?>)" class="btn btn-outline" style="flex: 1; border-radius: 50px;">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="?delete=<?= $hotel['id'] ?>" class="btn btn-danger" style="flex: 1; border-radius: 50px;" onclick="return confirm('Are you sure you want to delete this hotel?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 4rem 2rem;">
                    <i class="fas fa-hotel" style="font-size: 4rem; margin-bottom: 1.5rem; color: var(--text-muted); opacity: 0.5;"></i>
                    <h3 style="color: var(--text-main); font-size: 1.5rem; margin-bottom: 0.5rem;">No hotels added yet</h3>
                    <p style="color: var(--text-muted);">Use the form above to add your first hotel</p>
                </div>
            <?php endif; ?>
        </div>

        <div style="margin-top: 3rem; text-align: right; border-top: 1px solid var(--card-border); padding-top: 2rem;">
            <a href="?action=finalize" class="btn" style="background: linear-gradient(135deg, #10b981, #059669); color: white; border-radius: 50px; font-size: 1.1rem; padding: 1rem 2rem; text-decoration: none;" onclick="return confirm('Are you sure you want to submit this complete package (Destination, Flights, and Hotels) for review?')">
                <i class="fas fa-check-circle"></i> Finalize & Submit Package
            </a>
        </div>

    </div>
</div>

<div id="editHotelModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); z-index: 1100; align-items: center; justify-content: center;">
    <div style="background: var(--bg-surface); max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto; border-radius: 24px; padding: 2rem; position: relative;">
        <button onclick="closeEditModal()" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">
            <i class="fas fa-times"></i>
        </button>
        
        <h2 style="font-size: 1.8rem; margin-bottom: 2rem; color: var(--text-main);">
            <i class="fas fa-edit" style="color: #f59e0b;"></i> Edit Hotel
        </h2>
        
        <form method="POST" enctype="multipart/form-data" id="editHotelForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="hotel_id" id="edit_hotel_id">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
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
                    <select id="edit_amenities" name="amenities[]" class="form-control" multiple style="height: auto; min-height: 120px;">
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
                    <input type="file" id="edit_image" name="image" class="form-control" accept="image/*" style="padding: 0.6rem;">
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
                    <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0;">
                        <input type="checkbox" name="free_cancellation" id="edit_free_cancellation" value="1"> Free Cancellation
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0;">
                        <input type="checkbox" name="breakfast_included" id="edit_breakfast_included" value="1"> Breakfast Included
                    </label>
                </div>
            </div>
            
            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary" style="border-radius: 50px;">
                    <i class="fas fa-save"></i> Update Hotel
                </button>
                <button type="button" onclick="closeEditModal()" class="btn btn-outline" style="border-radius: 50px;">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<footer>© <?= date('Y') ?> TripMate · Contributor Portal</footer>

<script>
// Theme toggle
(function(){
    const t=document.getElementById('themeToggle'), i=t.querySelector('i');
    if(localStorage.getItem('tripmate-theme')==='dark'){document.body.classList.add('dark-mode');i.className='fas fa-sun';}
    t.addEventListener('click',()=>{document.body.classList.toggle('dark-mode');const d=document.body.classList.contains('dark-mode');i.className=d?'fas fa-sun':'fas fa-moon';localStorage.setItem('tripmate-theme',d?'dark':'light');});
})();

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
    document.getElementById('edit_free_cancellation').checked = hotel.free_cancellation == 1;
    document.getElementById('edit_breakfast_included').checked = hotel.breakfast_included == 1;
    
    const amenities = JSON.parse(hotel.amenities || '[]');
    const select = document.getElementById('edit_amenities');
    for (let option of select.options) {
        option.selected = amenities.includes(option.value);
    }
    
    document.getElementById('editHotelModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editHotelModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('editHotelModal');
    if (event.target == modal) { modal.style.display = 'none'; }
}
</script>
</body>
</html>