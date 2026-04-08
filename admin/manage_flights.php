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

// Handle form submission for adding/editing flight
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Add new flight
        if ($_POST['action'] === 'add') {
            $departure_city = $conn->real_escape_string($_POST['departure_city']);
            $airline = $conn->real_escape_string($_POST['airline']);
            $flight_type = $conn->real_escape_string($_POST['flight_type']);
            $price_per_person = (float)$_POST['price_per_person'];
            $duration_hours = (float)$_POST['duration_hours'];
            $stops = (int)$_POST['stops'];
            $departure_time = $conn->real_escape_string($_POST['departure_time']);
            $arrival_time = $conn->real_escape_string($_POST['arrival_time']);
            $flight_class = $conn->real_escape_string($_POST['flight_class']);
            $baggage_allowance = $conn->real_escape_string($_POST['baggage_allowance']);
            $refundable = isset($_POST['refundable']) ? 1 : 0;
            $meal_included = isset($_POST['meal_included']) ? 1 : 0;

            $stmt = $conn->prepare("INSERT INTO flights (destination_id, departure_city, airline, flight_type, price_per_person, duration_hours, stops, departure_time, arrival_time, flight_class, baggage_allowance, refundable, meal_included) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issisdississi", $destination_id, $departure_city, $airline, $flight_type, $price_per_person, $duration_hours, $stops, $departure_time, $arrival_time, $flight_class, $baggage_allowance, $refundable, $meal_included);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Flight added successfully!";
            } else {
                $_SESSION['message'] = "Error adding flight: " . $conn->error;
            }
            $stmt->close();
        }
        
        // Edit flight
        elseif ($_POST['action'] === 'edit' && isset($_POST['flight_id'])) {
            $flight_id = (int)$_POST['flight_id'];
            $departure_city = $conn->real_escape_string($_POST['departure_city']);
            $airline = $conn->real_escape_string($_POST['airline']);
            $flight_type = $conn->real_escape_string($_POST['flight_type']);
            $price_per_person = (float)$_POST['price_per_person'];
            $duration_hours = (float)$_POST['duration_hours'];
            $stops = (int)$_POST['stops'];
            $departure_time = $conn->real_escape_string($_POST['departure_time']);
            $arrival_time = $conn->real_escape_string($_POST['arrival_time']);
            $flight_class = $conn->real_escape_string($_POST['flight_class']);
            $baggage_allowance = $conn->real_escape_string($_POST['baggage_allowance']);
            $refundable = isset($_POST['refundable']) ? 1 : 0;
            $meal_included = isset($_POST['meal_included']) ? 1 : 0;

            $stmt = $conn->prepare("UPDATE flights SET departure_city = ?, airline = ?, flight_type = ?, price_per_person = ?, duration_hours = ?, stops = ?, departure_time = ?, arrival_time = ?, flight_class = ?, baggage_allowance = ?, refundable = ?, meal_included = ? WHERE id = ? AND destination_id = ?");
            $stmt->bind_param("sssdissssssiii", $departure_city, $airline, $flight_type, $price_per_person, $duration_hours, $stops, $departure_time, $arrival_time, $flight_class, $baggage_allowance, $refundable, $meal_included, $flight_id, $destination_id);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Flight updated successfully!";
            } else {
                $_SESSION['message'] = "Error updating flight: " . $conn->error;
            }
            $stmt->close();
        }
        
        header("Location: manage_flights.php?destination_id=" . $destination_id);
        exit();
    }
}

// Handle delete request
if (isset($_GET['delete'])) {
    $flight_id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("DELETE FROM flights WHERE id = ? AND destination_id = ?");
    $stmt->bind_param("ii", $flight_id, $destination_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Flight deleted successfully!";
    } else {
        $_SESSION['message'] = "Error deleting flight: " . $conn->error;
    }
    $stmt->close();
    
    header("Location: manage_flights.php?destination_id=" . $destination_id);
    exit();
}

// Fetch all flights for this destination
$flights_query = $conn->prepare("SELECT * FROM flights WHERE destination_id = ? ORDER BY id DESC");
$flights_query->bind_param("i", $destination_id);
$flights_query->execute();
$flights_result = $flights_query->get_result();

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
    <title>Manage Flights - <?= htmlspecialchars($destination_name) ?></title>
    
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

        /* Flights Grid */
        .flights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }

        .flight-card {
            background: var(--bg-surface);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 1.5rem;
            transition: all 0.3s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .flight-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px var(--shadow-color);
            border-color: var(--secondary);
        }

        .flight-type-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            color: white;
        }

        .badge-low {
            background: var(--success);
        }

        .badge-medium {
            background: var(--warning);
        }

        .badge-high {
            background: var(--danger);
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
            
            .flights-grid {
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
                <h1><i class="fas fa-plane" style="color: var(--secondary);"></i> Manage Flights</h1>
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

        <!-- Add Flight Form -->
        <div class="widget-card fade-in">
            <div class="card-header">
                <h2><i class="fas fa-plus-circle" style="color: var(--secondary);"></i> Add New Flight</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="departure_city">Departure City *</label>
                            <input type="text" id="departure_city" name="departure_city" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="airline">Airline *</label>
                            <input type="text" id="airline" name="airline" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="flight_type">Flight Type *</label>
                            <select id="flight_type" name="flight_type" class="form-control" required>
                                <option value="low">Low Budget</option>
                                <option value="medium">Medium Budget</option>
                                <option value="high">High Budget / Luxury</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="price_per_person">Price Per Person (₹) *</label>
                            <input type="number" id="price_per_person" name="price_per_person" step="0.01" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="duration_hours">Duration (hours)</label>
                            <input type="number" id="duration_hours" name="duration_hours" step="0.1" min="0" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="stops">Number of Stops</label>
                            <input type="number" id="stops" name="stops" min="0" class="form-control" value="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="departure_time">Departure Time</label>
                            <input type="text" id="departure_time" name="departure_time" class="form-control" placeholder="e.g., 10:30 AM">
                        </div>
                        
                        <div class="form-group">
                            <label for="arrival_time">Arrival Time</label>
                            <input type="text" id="arrival_time" name="arrival_time" class="form-control" placeholder="e.g., 02:45 PM">
                        </div>
                        
                        <div class="form-group">
                            <label for="flight_class">Flight Class</label>
                            <select id="flight_class" name="flight_class" class="form-control">
                                <option value="Economy">Economy</option>
                                <option value="Premium Economy">Premium Economy</option>
                                <option value="Business">Business Class</option>
                                <option value="First">First Class</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="baggage_allowance">Baggage Allowance</label>
                            <input type="text" id="baggage_allowance" name="baggage_allowance" class="form-control" placeholder="e.g., 15kg check-in + 7kg cabin">
                        </div>
                        
                        <div class="form-group" style="grid-column: span 2; display: flex; gap: 2rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="refundable" value="1"> Refundable
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="meal_included" value="1"> Meal Included
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Flight
                    </button>
                </form>
            </div>
        </div>

        <!-- Existing Flights -->
        <div class="widget-card fade-in">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Existing Flights</h2>
                <span class="status-badge status-active">Total: <?= $flights_result->num_rows ?></span>
            </div>
            <div class="card-body">
                <?php if ($flights_result->num_rows > 0): ?>
                    <div class="flights-grid">
                        <?php while ($flight = $flights_result->fetch_assoc()): 
                            $badgeClass = $flight['flight_type'] === 'low' ? 'badge-low' : ($flight['flight_type'] === 'medium' ? 'badge-medium' : 'badge-high');
                        ?>
                            <div class="flight-card">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                    <h3 style="font-size: 1.3rem; color: var(--text-main); margin: 0;"><?= htmlspecialchars($flight['airline']) ?></h3>
                                    <span class="flight-type-badge <?= $badgeClass ?>">
                                        <?= ucfirst($flight['flight_type']) ?>
                                    </span>
                                </div>
                                
                                <div style="margin-bottom: 1rem;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 1.1rem; font-weight: 600; color: var(--text-main);">
                                        <i class="fas fa-city" style="color: var(--secondary);"></i>
                                        <?= htmlspecialchars($flight['departure_city']) ?>
                                    </div>
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin: 0.5rem 0;">
                                        <span style="font-size: 0.9rem; color: var(--text-muted);">
                                            <i class="fas fa-clock"></i> <?= htmlspecialchars($flight['departure_time'] ?? 'N/A') ?>
                                        </span>
                                        <i class="fas fa-long-arrow-alt-right" style="color: var(--text-muted);"></i>
                                        <span style="font-size: 0.9rem; color: var(--text-muted);">
                                            <i class="fas fa-clock"></i> <?= htmlspecialchars($flight['arrival_time'] ?? 'N/A') ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; margin-bottom: 1rem; padding: 1rem 0; border-top: 1px solid var(--card-border); border-bottom: 1px solid var(--card-border);">
                                    <span style="display: flex; align-items: center; gap: 5px; font-size: 0.9rem; color: var(--text-muted);">
                                        <i class="fas fa-tag"></i> Class: <?= htmlspecialchars($flight['flight_class'] ?? 'Economy') ?>
                                    </span>
                                    <span style="display: flex; align-items: center; gap: 5px; font-size: 0.9rem; color: var(--text-muted);">
                                        <i class="fas fa-clock"></i> <?= number_format($flight['duration_hours'] ?? 0, 1) ?> hrs
                                    </span>
                                    <span style="display: flex; align-items: center; gap: 5px; font-size: 0.9rem; color: var(--text-muted);">
                                        <i class="fas fa-map-marker-alt"></i> <?= $flight['stops'] ?> stop(s)
                                    </span>
                                    <span style="display: flex; align-items: center; gap: 5px; font-size: 0.9rem; color: var(--text-muted);">
                                        <i class="fas fa-suitcase"></i> <?= htmlspecialchars($flight['baggage_allowance'] ?? 'N/A') ?>
                                    </span>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                    <span style="font-size: 1.4rem; font-weight: 700; color: var(--text-main);">
                                        ₹<?= number_format($flight['price_per_person']) ?>
                                        <small style="font-size: 0.8rem; color: var(--text-muted);">/person</small>
                                    </span>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <?php if ($flight['refundable']): ?>
                                            <span style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 4px 8px; border-radius: 20px; font-size: 0.7rem;">Refundable</span>
                                        <?php endif; ?>
                                        <?php if ($flight['meal_included']): ?>
                                            <span style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; padding: 4px 8px; border-radius: 20px; font-size: 0.7rem;">Meal</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div style="display: flex; gap: 0.5rem; margin-top: auto; border-top: 1px solid var(--card-border); padding-top: 1rem;">
                                    <button onclick='editFlight(<?= json_encode($flight) ?>)' class="btn btn-outline" style="flex: 1;">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="?destination_id=<?= $destination_id ?>&delete=<?= $flight['id'] ?>" class="btn btn-danger" style="flex: 1;" onclick="return confirm('Are you sure you want to delete this flight?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center" style="padding: 4rem 2rem;">
                        <i class="fas fa-plane" style="font-size: 4rem; margin-bottom: 1.5rem; color: var(--text-muted); opacity: 0.5;"></i>
                        <h3 style="color: var(--text-main); font-size: 1.5rem; margin-bottom: 0.5rem;">No flights added yet</h3>
                        <p style="color: var(--text-muted);">Use the form above to add your first flight</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Flight Modal -->
    <div class="modal" id="editFlightModal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
            
            <h2 style="font-size: 1.8rem; margin-bottom: 2rem; color: var(--text-main);">
                <i class="fas fa-edit" style="color: var(--secondary);"></i> Edit Flight
            </h2>
            
            <form method="POST" id="editFlightForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="flight_id" id="edit_flight_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_departure_city">Departure City *</label>
                        <input type="text" id="edit_departure_city" name="departure_city" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_airline">Airline *</label>
                        <input type="text" id="edit_airline" name="airline" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_flight_type">Flight Type *</label>
                        <select id="edit_flight_type" name="flight_type" class="form-control" required>
                            <option value="low">Low Budget</option>
                            <option value="medium">Medium Budget</option>
                            <option value="high">High Budget / Luxury</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_price_per_person">Price Per Person (₹) *</label>
                        <input type="number" id="edit_price_per_person" name="price_per_person" step="0.01" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_duration_hours">Duration (hours)</label>
                        <input type="number" id="edit_duration_hours" name="duration_hours" step="0.1" min="0" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_stops">Number of Stops</label>
                        <input type="number" id="edit_stops" name="stops" min="0" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_departure_time">Departure Time</label>
                        <input type="text" id="edit_departure_time" name="departure_time" class="form-control" placeholder="e.g., 10:30 AM">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_arrival_time">Arrival Time</label>
                        <input type="text" id="edit_arrival_time" name="arrival_time" class="form-control" placeholder="e.g., 02:45 PM">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_flight_class">Flight Class</label>
                        <select id="edit_flight_class" name="flight_class" class="form-control">
                            <option value="Economy">Economy</option>
                            <option value="Premium Economy">Premium Economy</option>
                            <option value="Business">Business Class</option>
                            <option value="First">First Class</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_baggage_allowance">Baggage Allowance</label>
                        <input type="text" id="edit_baggage_allowance" name="baggage_allowance" class="form-control" placeholder="e.g., 15kg check-in + 7kg cabin">
                    </div>
                    
                    <div class="form-group" style="grid-column: span 2; display: flex; gap: 2rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" name="refundable" id="edit_refundable" value="1"> Refundable
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" name="meal_included" id="edit_meal_included" value="1"> Meal Included
                        </label>
                    </div>
                </div>
                
                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Flight
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

        // Edit Flight Function
        function editFlight(flight) {
            document.getElementById('edit_flight_id').value = flight.id;
            document.getElementById('edit_departure_city').value = flight.departure_city;
            document.getElementById('edit_airline').value = flight.airline;
            document.getElementById('edit_flight_type').value = flight.flight_type;
            document.getElementById('edit_price_per_person').value = flight.price_per_person;
            document.getElementById('edit_duration_hours').value = flight.duration_hours || '';
            document.getElementById('edit_stops').value = flight.stops || 0;
            document.getElementById('edit_departure_time').value = flight.departure_time || '';
            document.getElementById('edit_arrival_time').value = flight.arrival_time || '';
            document.getElementById('edit_flight_class').value = flight.flight_class || 'Economy';
            document.getElementById('edit_baggage_allowance').value = flight.baggage_allowance || '';
            
            document.getElementById('edit_refundable').checked = flight.refundable == 1;
            document.getElementById('edit_meal_included').checked = flight.meal_included == 1;
            
            document.getElementById('editFlightModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editFlightModal').classList.remove('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editFlightModal');
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