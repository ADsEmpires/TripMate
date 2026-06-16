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

if (!isset($_SESSION['temp_flights'])) {
    $_SESSION['temp_flights'] = [];
}

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

            $new_flight = [
                'id' => 'fl_' . uniqid(),
                'departure_city' => $departure_city,
                'airline' => $airline,
                'flight_type' => $flight_type,
                'price_per_person' => $price_per_person,
                'duration_hours' => $duration_hours,
                'stops' => $stops,
                'departure_time' => $departure_time,
                'arrival_time' => $arrival_time,
                'flight_class' => $flight_class,
                'baggage_allowance' => $baggage_allowance,
                'refundable' => $refundable,
                'meal_included' => $meal_included
            ];

            $_SESSION['temp_flights'][] = $new_flight;
            $_SESSION['message'] = "Flight added to draft successfully!";
        }
        
        // Edit flight
        elseif ($_POST['action'] === 'edit' && isset($_POST['flight_id'])) {
            $flight_id = $_POST['flight_id'];
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

            foreach ($_SESSION['temp_flights'] as $key => $flight) {
                if ($flight['id'] === $flight_id) {
                    $_SESSION['temp_flights'][$key] = [
                        'id' => $flight_id,
                        'departure_city' => $departure_city,
                        'airline' => $airline,
                        'flight_type' => $flight_type,
                        'price_per_person' => $price_per_person,
                        'duration_hours' => $duration_hours,
                        'stops' => $stops,
                        'departure_time' => $departure_time,
                        'arrival_time' => $arrival_time,
                        'flight_class' => $flight_class,
                        'baggage_allowance' => $baggage_allowance,
                        'refundable' => $refundable,
                        'meal_included' => $meal_included
                    ];
                    $_SESSION['message'] = "Flight updated in draft successfully!";
                    break;
                }
            }
        }
        
        header("Location: contributor_manage_flights.php");
        exit();
    }
}

// Handle delete request
if (isset($_GET['delete'])) {
    $flight_id = $_GET['delete'];
    
    foreach ($_SESSION['temp_flights'] as $key => $flight) {
        if ($flight['id'] === $flight_id) {
            unset($_SESSION['temp_flights'][$key]);
            $_SESSION['temp_flights'] = array_values($_SESSION['temp_flights']);
            $_SESSION['message'] = "Flight removed from draft successfully!";
            break;
        }
    }
    
    header("Location: contributor_manage_flights.php");
    exit();
}

$flights_list = $_SESSION['temp_flights'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Flights – TripMate Contributor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #f8fafc; --bg-surface: #ffffff; --text-main: #0f172a; --text-muted: #475569;
            --primary: #4f46e5; --secondary: #06b6d4; --nav-bg: rgba(255, 255, 255, 0.75);
            --card-border: rgba(79, 70, 229, 0.15); --shadow-color: rgba(15, 23, 42, 0.08); --glow-color: rgba(6, 182, 212, 0.4);
            --danger: #ef4444; --success: #10b981; --warning: #f59e0b; --accent: #3b82f6;
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
        
        /* Dark Mode Text Fix for Inputs */
        .form-control { width: 100%; padding: 0.9rem 1.2rem; border: 1px solid var(--card-border); border-radius: 12px; font-size: 1rem; background: var(--bg-base); color: var(--text-main); transition: all 0.3s; font-family: inherit; }
        .form-control:focus { border-color: var(--secondary); outline: none; box-shadow: 0 0 0 4px var(--glow-color); background: var(--bg-surface); }
        body.dark-mode .form-control { background-color: var(--bg-surface); color: var(--text-main); }
        body.dark-mode select.form-control option { background: var(--bg-surface); color: var(--text-main); }

        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.8rem 1.8rem; border-radius: 12px; font-weight: 700; cursor: pointer; transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1); border: none; gap: 8px; font-size: 1rem; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4); }
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

        .flight-card { transition: transform 0.3s, box-shadow 0.3s; }
        .flight-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px var(--shadow-color); }

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
                <i class="fas fa-plane" style="color: #3b82f6;"></i> Manage Flights
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
            <h2 style="font-size: 1.8rem; color: var(--text-main);"><i class="fas fa-plus-circle" style="color: #3b82f6;"></i> Add New Flight</h2>
        </div>
        <div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
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
                            <option value="Business Class">Business Class</option>
                            <option value="First Class">First Class</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="baggage_allowance">Baggage Allowance</label>
                        <input type="text" id="baggage_allowance" name="baggage_allowance" class="form-control" placeholder="e.g., 15kg check-in + 7kg cabin">
                    </div>
                    <div class="form-group" style="grid-column: span 2; display: flex; gap: 2rem; align-items: center;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0;">
                            <input type="checkbox" name="refundable" value="1"> Refundable
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0;">
                            <input type="checkbox" name="meal_included" value="1"> Meal Included
                        </label>
                    </div>
                </div>
                
                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary" style="border-radius: 50px;">
                        <i class="fas fa-save"></i> Add Flight
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="widget-card fade-in">
         <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--card-border); padding-bottom: 1rem;">
             <h2 style="font-size: 1.8rem; color: var(--text-main);"><i class="fas fa-list"></i> Existing Flights</h2>
             <span class="status-badge status-active">Total: <?= count($flights_list) ?></span>
         </div>
         <div>
             <?php if (count($flights_list) > 0): ?>
                 <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 2rem;">
                     <?php foreach ($flights_list as $flight): ?>
                         <div class="flight-card widget-card" style="padding: 1.5rem;">
                             <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                 <h3 style="font-size: 1.3rem; color: var(--text-main); margin: 0;"><?= htmlspecialchars($flight['airline']) ?></h3>
                                 <span style="background: <?= $flight['flight_type'] === 'low' ? '#10b981' : ($flight['flight_type'] === 'medium' ? '#f59e0b' : '#ef4444') ?>; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700;">
                                     <?= ucfirst($flight['flight_type']) ?>
                                 </span>
                             </div>
                             
                             <div style="margin-bottom: 1rem;">
                                 <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 1.1rem; font-weight: 600; color: var(--text-main);">
                                     <i class="fas fa-city" style="color: #3b82f6;"></i>
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
                             
                             <div style="display: flex; gap: 0.5rem; margin-top: 1rem; border-top: 1px solid var(--card-border); padding-top: 1rem;">
                                 <button onclick="editFlight(<?= htmlspecialchars(json_encode($flight)) ?>)" class="btn btn-outline" style="flex: 1; border-radius: 50px;">
                                     <i class="fas fa-edit"></i> Edit
                                 </button>
                                 <a href="?delete=<?= $flight['id'] ?>" class="btn btn-danger" style="flex: 1; border-radius: 50px;" onclick="return confirm('Are you sure you want to delete this flight?')">
                                     <i class="fas fa-trash"></i> Delete
                                 </a>
                             </div>
                         </div>
                     <?php endforeach; ?>
                 </div>
             <?php else: ?>
                 <div style="text-align: center; padding: 4rem 2rem;">
                     <i class="fas fa-plane" style="font-size: 4rem; margin-bottom: 1.5rem; color: var(--text-muted); opacity: 0.5;"></i>
                     <h3 style="color: var(--text-main); font-size: 1.5rem; margin-bottom: 0.5rem;">No flights added yet</h3>
                     <p style="color: var(--text-muted);">Use the form above to add your first flight</p>
                 </div>
             <?php endif; ?>
         </div>

         <div style="margin-top: 3rem; text-align: right; border-top: 1px solid var(--card-border); padding-top: 2rem;">
             <a href="contributor_manage_hotels.php" class="btn" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; border-radius: 50px; font-size: 1.1rem; padding: 1rem 2rem; text-decoration: none;">
                 Continue to Step 3 (Hotels) <i class="fas fa-arrow-right"></i>
             </a>
         </div>

     </div>
 </div>

<div id="editFlightModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); z-index: 1100; align-items: center; justify-content: center;">
    <div style="background: var(--bg-surface); max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto; border-radius: 24px; padding: 2rem; position: relative;">
        <button onclick="closeEditModal()" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">
            <i class="fas fa-times"></i>
        </button>
        
        <h2 style="font-size: 1.8rem; margin-bottom: 2rem; color: var(--text-main);">
            <i class="fas fa-edit" style="color: #3b82f6;"></i> Edit Flight
        </h2>
        
        <form method="POST" id="editFlightForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="flight_id" id="edit_flight_id">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
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
                        <option value="Business Class">Business Class</option>
                        <option value="First Class">First Class</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_baggage_allowance">Baggage Allowance</label>
                    <input type="text" id="edit_baggage_allowance" name="baggage_allowance" class="form-control">
                </div>
                <div class="form-group" style="grid-column: span 2; display: flex; gap: 2rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0;">
                        <input type="checkbox" name="refundable" id="edit_refundable" value="1"> Refundable
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0;">
                        <input type="checkbox" name="meal_included" id="edit_meal_included" value="1"> Meal Included
                    </label>
                </div>
            </div>
            
            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary" style="border-radius: 50px;">
                    <i class="fas fa-save"></i> Update Flight
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
    
    document.getElementById('editFlightModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editFlightModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('editFlightModal');
    if (event.target == modal) { modal.style.display = 'none'; }
}
</script>
</body>
</html>