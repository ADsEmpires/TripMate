<?php
session_start();
require_once '../database/dbconfig.php';

// Block Google-only users — they must create a full account first
if (isset($_SESSION['auth_provider']) && $_SESSION['auth_provider'] === 'google') {
    $google_email = isset($_SESSION['google_email']) ? urlencode($_SESSION['google_email']) : '';
    $google_name = isset($_SESSION['user_name']) ? urlencode($_SESSION['user_name']) : '';
    header("Location: ../auth/register.html?upgrade=1&email={$google_email}&name={$google_name}");
    exit;
}

// Check if destination_id is provided
$destination_id = isset($_GET['destination_id']) ? intval($_GET['destination_id']) : null;
$hotel_id = isset($_GET['hotel_id']) ? intval($_GET['hotel_id']) : null;
$flight_id = isset($_GET['flight_id']) ? intval($_GET['flight_id']) : null;

// Fetch destination details if ID is provided
$destination = null;
if ($destination_id) {
    $stmt = $conn->prepare("SELECT * FROM destinations WHERE id = ?");
    $stmt->bind_param("i", $destination_id);
    $stmt->execute();
    $destination = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Fetch all destinations for dropdown
$destinations = [];
$dest_query = "SELECT id, name, location FROM destinations ORDER BY name";
$dest_result = $conn->query($dest_query);
if ($dest_result) {
    while ($row = $dest_result->fetch_assoc()) {
        $destinations[] = $row;
    }
}

// Fetch departure cities from flights table
$departure_cities = [];
$city_query = "SELECT DISTINCT from_city AS departure_city FROM flights ORDER BY from_city";
$city_result = $conn->query($city_query);
if ($city_result) {
    while ($row = $city_result->fetch_assoc()) {
        $departure_cities[] = $row['departure_city'];
    }
}

// Fallback: if no departure cities found in flights table, use default cities
if (empty($departure_cities)) {
    $departure_cities = [
        'Ahmedabad',
        'Bangalore',
        'Bhubaneswar',
        'Chennai',
        'Cochin',
        'Delhi',
        'Goa',
        'Guwahati',
        'Hyderabad',
        'Jaipur',
        'Kolkata',
        'Lucknow',
        'Mumbai',
        'Patna',
        'Pune'
    ];
}

// Get seasonal pricing data
$seasonal_pricing = [];
$season_query = "SELECT * FROM seasonal_pricing WHERE is_active = 1";
$season_result = $conn->query($season_query);
if ($season_result) {
    while ($row = $season_result->fetch_assoc()) {
        $seasonal_pricing[] = $row;
    }
}

// Get current month for seasonal pricing
$current_month = date('n');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Your Trip - TripMate</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Flatpickr for date picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <style>
        :root {
            --primary: #16034f;
            --secondary: #2a0a8a;
            --accent: #ff6600;
            --accent-light: #ff8533;
            --accent-dark: #cc5200;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --background: #f9fafc;
            --white: #ffffff;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 20px rgba(0,0,0,0.08);
            --shadow-lg: 0 8px 30px rgba(0,0,0,0.12);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--background);
            color: var(--text-dark);
            line-height: 1.5;
        }

        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-sm);
            z-index: 1000;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo i {
            color: var(--accent);
            font-size: 2rem;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--accent);
        }

        .nav-links .btn {
            background: var(--accent);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 30px;
            font-weight: 600;
        }

        .nav-links .btn:hover {
            background: var(--accent-dark);
            color: white;
        }

        /* Main Content */
        .main-content {
            margin-top: 80px;
            min-height: calc(100vh - 80px);
        }

        /* Hero Section */
        .hero {
            position: relative;
            height: 600px;
            background: linear-gradient(135deg, rgba(22,3,79,0.9) 0%, rgba(42,10,138,0.8) 100%);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
            filter: brightness(0.7);
        }

        .hero-content {
            text-align: center;
            color: white;
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .hero-content p {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        /* Search Form */
        .search-container {
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            max-width: 1000px;
            margin: -80px auto 40px;
            position: relative;
            z-index: 10;
            overflow: hidden;
        }

        .search-tabs {
            display: flex;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
        }

        .search-tab {
            flex: 1;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-light);
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }

        .search-tab.active {
            color: var(--accent);
            border-bottom-color: var(--accent);
            background: white;
        }

        .search-tab i {
            margin-right: 0.5rem;
        }

        .search-form {
            padding: 2rem;
        }

        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group select {
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255,102,0,0.1);
        }

        .form-group input[readonly] {
            background-color: #f9fafb;
            cursor: pointer;
        }

        .trip-type {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .trip-type label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            font-weight: 500;
        }

        .trip-type input[type="radio"] {
            accent-color: var(--accent);
            width: 18px;
            height: 18px;
        }

        .search-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255,102,0,0.3);
        }

        .search-btn i {
            font-size: 1.2rem;
        }

        /* Results Section */
        .results-section {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .results-header h2 {
            color: var(--primary);
            font-size: 1.8rem;
            font-weight: 700;
        }

        .results-header h2 i {
            color: var(--accent);
            margin-right: 0.5rem;
        }

        .filter-sort {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .filter-sort select {
            padding: 0.5rem 2rem 0.5rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            background: white;
        }

        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .result-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s;
            border: 1px solid #eef4ff;
            cursor: pointer;
        }

        .result-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--accent);
        }

        .result-card.selected {
            border: 3px solid var(--accent);
            background: #fff7ed;
        }

        .card-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .card-header h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .card-header .price {
            font-size: 2rem;
            font-weight: 800;
        }

        .card-header .price small {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .card-body {
            padding: 1.5rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px dashed #eef4ff;
        }

        .detail-label {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .detail-value {
            font-weight: 600;
            color: var(--primary);
        }

        .amenities {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .amenity-tag {
            background: #f0f4ff;
            color: var(--primary);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .book-btn {
            width: 100%;
            padding: 0.75rem;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1rem;
            font-size: 1rem;
        }

        .book-btn:hover {
            background: var(--accent-dark);
        }

        /* Package Summary */
        .package-summary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }

        .package-summary h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .package-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .package-item {
            background: rgba(255,255,255,0.1);
            padding: 1rem;
            border-radius: 8px;
        }

        .package-item .label {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .package-item .value {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .total-price {
            font-size: 2rem;
            font-weight: 800;
            text-align: right;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid rgba(255,255,255,0.2);
        }

        /* Featured Offers */
        .featured-offers {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 2rem;
        }

        .section-title {
            color: var(--primary);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2rem;
            text-align: center;
        }

        .offers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .offer-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s;
            position: relative;
        }

        .offer-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .offer-image {
            height: 160px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .offer-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--accent);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .offer-content {
            padding: 1.5rem;
        }

        .offer-content h4 {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .offer-price {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--accent);
            margin: 0.5rem 0;
        }

        .offer-price small {
            font-size: 0.9rem;
            color: var(--text-light);
            font-weight: normal;
        }

        /* Why Book With Us */
        .why-book {
            max-width: 1200px;
            margin: 4rem auto;
            padding: 0 2rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .feature {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s;
        }

        .feature:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .feature i {
            font-size: 3rem;
            color: var(--accent);
            margin-bottom: 1rem;
        }

        .feature h3 {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .feature p {
            color: var(--text-light);
        }

        /* Footer */
        .footer {
            background: var(--primary);
            color: white;
            padding: 3rem 2rem 1.5rem;
            margin-top: 4rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
        }

        .footer-section h4 {
            margin-bottom: 1rem;
            color: var(--accent);
        }

        .footer-section a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            display: block;
            margin-bottom: 0.5rem;
            transition: color 0.3s;
        }

        .footer-section a:hover {
            color: var(--accent);
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .social-links a:hover {
            background: var(--accent);
            transform: translateY(-2px);
        }

        .footer-bottom {
            max-width: 1200px;
            margin: 2rem auto 0;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            text-align: center;
            color: rgba(255,255,255,0.6);
        }

        /* Loading Spinner */
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .loading-spinner.active {
            display: block;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--accent);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* No Results */
        .no-results {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 12px;
        }

        .no-results i {
            font-size: 4rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
            }

            .logo span {
                display: none;
            }

            .hero-content h1 {
                font-size: 2rem;
            }

            .search-container {
                margin: -40px 1rem 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .results-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .results-grid {
                grid-template-columns: 1fr;
            }

            .package-details {
                grid-template-columns: 1fr;
            }

            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .social-links {
                justify-content: center;
            }
        }

        /* Utility Classes */
        .hidden {
            display: none !important;
        }

        .text-accent {
            color: var(--accent);
        }

        .bg-gradient {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }

        /* Shopping Bag */
        .shopping-bag {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 320px;
            z-index: 1000;
            overflow: hidden;
            display: none;
            transition: all 0.3s;
        }

        .shopping-bag.active {
            display: block;
            animation: slideUp 0.3s ease;
        }

        .bag-header {
            background: var(--primary);
            color: white;
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 600;
        }

        .bag-items {
            padding: 1rem;
            max-height: 300px;
            overflow-y: auto;
        }

        .bag-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 0.75rem;
            margin-bottom: 0.75rem;
            border-bottom: 1px dashed #eef4ff;
        }

        .bag-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .bag-item-details h5 {
            color: var(--primary);
            margin-bottom: 0.25rem;
        }

        .bag-item-details p {
            font-size: 0.8rem;
            color: var(--text-light);
        }

        .bag-item-price {
            font-weight: 700;
            color: var(--accent);
        }

        .bag-item-remove {
            color: #ef4444;
            cursor: pointer;
            margin-left: 0.5rem;
        }

        .bag-footer {
            padding: 1rem;
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
        }
        
        .btn-added {
            background: #10b981 !important;
            color: white !important;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-compass"></i>
            <span>TripMate</span>
        </div>
        <div class="nav-links">
            <a href="../main/index.html">Home</a>
            <a href="../search/search.html">Destinations</a>
            <a href="#" class="btn">Support</a>
        </div>
    </nav>

    <main class="main-content">
        <!-- Hero Section -->
        <section class="hero">
            <img src="https://images.unsplash.com/photo-1436491865332-7a61a109cc05?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80" alt="Hero Background" class="hero-bg">
            <div class="hero-content">
                <h1>Your Journey Begins Here</h1>
                <p>Discover amazing deals on flights, hotels, and exclusive packages</p>
            </div>
        </section>

        <!-- Search Container -->
        <div class="search-container">
            <div class="search-tabs">
                <div class="search-tab active" data-tab="flights">
                    <i class="fas fa-plane"></i> Flights
                </div>
                <div class="search-tab" data-tab="hotels">
                    <i class="fas fa-hotel"></i> Hotels
                </div>
                <div class="search-tab" data-tab="packages">
                    <i class="fas fa-gem"></i> Flight + Hotel
                </div>
            </div>

            <div class="search-form">
                <!-- Flights Form -->
                <div class="form-section active" id="flights-form">
                    <div class="trip-type">
                        <label>
                            <input type="radio" name="trip-type" value="roundtrip" checked> Round Trip
                        </label>
                        <label>
                            <input type="radio" name="trip-type" value="oneway"> One Way
                        </label>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-plane-departure"></i> From</label>
                            <select id="flight-from">
                                <option value="">Select Departure City</option>
                                <?php foreach ($departure_cities as $city): ?>
                                    <option value="<?php echo htmlspecialchars($city); ?>"><?php echo htmlspecialchars($city); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-plane-arrival"></i> To</label>
                            <select id="flight-to">
                                <option value="">Select Destination</option>
                                <?php foreach ($destinations as $dest): ?>
                                    <option value="<?php echo $dest['id']; ?>" <?php echo ($destination_id == $dest['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dest['name'] . ' - ' . $dest['location']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Depart</label>
                            <input type="text" id="flight-depart" class="datepicker" placeholder="Select date" readonly>
                        </div>
                        <div class="form-group" id="return-date-group">
                            <label><i class="fas fa-calendar-check"></i> Return</label>
                            <input type="text" id="flight-return" class="datepicker" placeholder="Select date" readonly>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-users"></i> Passengers</label>
                            <select id="flight-passengers">
                                <option value="1">1 Passenger</option>
                                <option value="2" selected>2 Passengers</option>
                                <option value="3">3 Passengers</option>
                                <option value="4">4 Passengers</option>
                                <option value="5">5 Passengers</option>
                                <option value="6">6+ Passengers</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-chair"></i> Cabin Class</label>
                            <select id="flight-class">
                                <option value="economy">Economy</option>
                                <option value="premium">Premium Economy</option>
                                <option value="business">Business</option>
                                <option value="first">First Class</option>
                            </select>
                        </div>
                    </div>

                    <button class="search-btn" onclick="searchFlights()">
                        <i class="fas fa-search"></i> Search Flights
                    </button>
                </div>

                <!-- Hotels Form -->
                <div class="form-section" id="hotels-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Destination</label>
                            <select id="hotel-destination">
                                <option value="">Select Destination</option>
                                <?php foreach ($destinations as $dest): ?>
                                    <option value="<?php echo $dest['id']; ?>" <?php echo ($destination_id == $dest['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dest['name'] . ' - ' . $dest['location']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Check-in</label>
                            <input type="text" id="hotel-checkin" class="datepicker" placeholder="Select date" readonly>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-check"></i> Check-out</label>
                            <input type="text" id="hotel-checkout" class="datepicker" placeholder="Select date" readonly>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-bed"></i> Rooms</label>
                            <select id="hotel-rooms">
                                <option value="1">1 Room</option>
                                <option value="2">2 Rooms</option>
                                <option value="3">3 Rooms</option>
                                <option value="4">4+ Rooms</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-users"></i> Guests</label>
                            <select id="hotel-guests">
                                <option value="1">1 Guest</option>
                                <option value="2" selected>2 Guests</option>
                                <option value="3">3 Guests</option>
                                <option value="4">4 Guests</option>
                                <option value="5">5+ Guests</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-star"></i> Hotel Type</label>
                            <select id="hotel-type">
                                <option value="all">All Types</option>
                                <option value="low">Low Budget</option>
                                <option value="medium">Medium Budget</option>
                                <option value="high">High Budget</option>
                            </select>
                        </div>
                    </div>

                    <button class="search-btn" onclick="searchHotels()">
                        <i class="fas fa-search"></i> Search Hotels
                    </button>
                </div>

                <!-- Packages Form -->
                <div class="form-section" id="packages-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-plane-departure"></i> From</label>
                            <select id="package-from">
                                <option value="">Select Departure City</option>
                                <?php foreach ($departure_cities as $city): ?>
                                    <option value="<?php echo htmlspecialchars($city); ?>"><?php echo htmlspecialchars($city); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Destination</label>
                            <select id="package-destination">
                                <option value="">Select Destination</option>
                                <?php foreach ($destinations as $dest): ?>
                                    <option value="<?php echo $dest['id']; ?>" <?php echo ($destination_id == $dest['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dest['name'] . ' - ' . $dest['location']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Check-in</label>
                            <input type="text" id="package-checkin" class="datepicker" placeholder="Select date" readonly>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-check"></i> Check-out</label>
                            <input type="text" id="package-checkout" class="datepicker" placeholder="Select date" readonly>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-users"></i> Travelers</label>
                            <select id="package-travelers">
                                <option value="1">1 Traveler</option>
                                <option value="2" selected>2 Travelers</option>
                                <option value="3">3 Travelers</option>
                                <option value="4">4 Travelers</option>
                                <option value="5">5+ Travelers</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-wallet"></i> Budget Type</label>
                            <select id="package-budget">
                                <option value="all">All Budgets</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-chair"></i> Flight Class</label>
                            <select id="package-flight-class">
                                <option value="all">All Classes</option>
                                <option value="economy">Economy</option>
                                <option value="business">Business</option>
                            </select>
                        </div>
                    </div>

                    <button class="search-btn" onclick="searchPackages()">
                        <i class="fas fa-search"></i> Search Packages
                    </button>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div class="results-section">
            <div class="results-header">
                <h2><i class="fas fa-search"></i> <span id="results-title">Search Results</span></h2>
                <div class="filter-sort">
                    <select id="sort-by">
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                        <option value="rating">Rating</option>
                    </select>
                </div>
            </div>

            <!-- Loading Spinner -->
            <div class="loading-spinner" id="loading-spinner">
                <div class="spinner"></div>
                <p style="margin-top: 1rem; color: var(--primary);">Searching for best options...</p>
            </div>

            <!-- Results Grid -->
            <div id="results-grid" class="results-grid"></div>

            <!-- Package Summary (shown only for package bookings) -->
            <div id="package-summary" class="package-summary hidden">
                <h3><i class="fas fa-gem"></i> Your Package</h3>
                <div class="package-details" id="package-details"></div>
                <div class="total-price" id="package-total"></div>
                <button class="book-btn" onclick="bookPackage()" style="margin-top: 1rem;">
                    <i class="fas fa-check-circle"></i> Confirm Package Booking
                </button>
            </div>

            <!-- No Results -->
            <div id="no-results" class="no-results hidden">
                <i class="fas fa-search"></i>
                <h3>No Results Found</h3>
                <p>Try adjusting your search criteria</p>
            </div>
        </div>

        <!-- Featured Offers -->
        <section class="featured-offers">
            <h2 class="section-title">Featured Offers</h2>
            <div class="offers-grid">
                <div class="offer-card">
                    <div class="offer-image" style="background-image: url('https://images.unsplash.com/photo-1502602898657-3e91760cbb34?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&q=80')">
                        <span class="offer-badge">-20%</span>
                    </div>
                    <div class="offer-content">
                        <h4>Paris Getaway</h4>
                        <p>Round-trip flight + 4 nights</p>
                        <div class="offer-price">$599 <small>pp</small></div>
                        <button class="book-btn" onclick="quickSelect('Paris')">View Deal</button>
                    </div>
                </div>

                <div class="offer-card">
                    <div class="offer-image" style="background-image: url('https://images.unsplash.com/photo-1582719508461-905c673771fd?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&q=80')">
                        <span class="offer-badge">-30%</span>
                    </div>
                    <div class="offer-content">
                        <h4>Maldives Resort</h4>
                        <p>5-star beachfront villa</p>
                        <div class="offer-price">$899 <small>night</small></div>
                        <button class="book-btn" onclick="quickSelect('Maldives')">View Deal</button>
                    </div>
                </div>

                <div class="offer-card">
                    <div class="offer-image" style="background-image: url('https://images.unsplash.com/photo-1533106497176-45ae19e68ba2?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&q=80')">
                        <span class="offer-badge">-15%</span>
                    </div>
                    <div class="offer-content">
                        <h4>Tokyo Adventure</h4>
                        <p>Flight + 5 nights + tours</p>
                        <div class="offer-price">$1,299 <small>pp</small></div>
                        <button class="book-btn" onclick="quickSelect('Tokyo')">View Deal</button>
                    </div>
                </div>

                <div class="offer-card">
                    <div class="offer-image" style="background-image: url('https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&q=80')">
                        <span class="offer-badge">-25%</span>
                    </div>
                    <div class="offer-content">
                        <h4>Dubai Luxury</h4>
                        <p>Business class + 5-star hotel</p>
                        <div class="offer-price">$1,999 <small>pp</small></div>
                        <button class="book-btn" onclick="quickSelect('Dubai')">View Deal</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Why Book With Us -->
        <section class="why-book">
            <h2 class="section-title">Why Book With TripMate?</h2>
            <div class="features-grid">
                <div class="feature">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Best Price Guarantee</h3>
                    <p>We match any lower price you find</p>
                </div>
                <div class="feature">
                    <i class="fas fa-headset"></i>
                    <h3>24/7 Customer Support</h3>
                    <p>We're here to help, anytime</p>
                </div>
                <div class="feature">
                    <i class="fas fa-lock"></i>
                    <h3>Secure Payments</h3>
                    <p>Your data is always protected</p>
                </div>
                <div class="feature">
                    <i class="fas fa-calendar-check"></i>
                    <h3>Flexible Cancellation</h3>
                    <p>Free cancellation on most bookings</p>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>TripMate</h4>
                    <p>Your trusted travel companion for unforgettable journeys.</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <a href="#">About Us</a>
                    <a href="#">Contact</a>
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                </div>
                <div class="footer-section">
                    <h4>Support</h4>
                    <a href="#">Help Center</a>
                    <a href="#">FAQ</a>
                    <a href="#">Cancellation Policy</a>
                    <a href="#">Payment Options</a>
                </div>
                <div class="footer-section">
                    <h4>Connect With Us</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 TripMate. All rights reserved.</p>
            </div>
        </footer>
    </main>

    <!-- Shopping Bag -->
    <div id="shopping-bag" class="shopping-bag">
        <div class="bag-header">
            <span><i class="fas fa-shopping-bag"></i> Your Trip</span>
            <span id="bag-total-items">0 items</span>
        </div>
        <div class="bag-items" id="bag-items">
            <!-- Items injected by JS -->
        </div>
        <div class="bag-footer">
            <button class="book-btn" onclick="checkoutBag()" id="checkout-btn" style="margin-top:0;">
                Proceed to Checkout
            </button>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="../user/session-sync.js"></script>
    <script src="../user/auto-logout.js"></script>
    
    <script>
        // Initialize date pickers
        flatpickr(".datepicker", {
            minDate: "today",
            dateFormat: "Y-m-d",
            clickOpens: true,        // Safari: ensure picker opens on click even on readonly inputs
            allowInput: false,       // Safari: prevent keyboard input conflicts
            disableMobile: true,     // Force Flatpickr UI instead of native date picker (cross-browser consistency)
            onChange: function(selectedDates, dateStr, instance) {
                // If this is a checkout/return date, ensure it's after checkin
                if (instance.input.id === 'hotel-checkout' || instance.input.id === 'flight-return' || instance.input.id === 'package-checkout') {
                    const checkinId = instance.input.id.replace('checkout', 'checkin').replace('return', 'depart');
                    const checkin = document.getElementById(checkinId);
                    if (checkin && checkin.value && dateStr < checkin.value) {
                        alert('Check-out date must be after check-in date');
                        instance.clear();
                    }
                }
            }
        });

        // Tab switching
        document.querySelectorAll('.search-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.search-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
                
                this.classList.add('active');
                document.getElementById(this.dataset.tab + '-form').classList.add('active');
                
                // Clear results when switching tabs
                document.getElementById('results-grid').innerHTML = '';
                document.getElementById('package-summary').classList.add('hidden');
                document.getElementById('no-results').classList.add('hidden');
            });
        });

        // Trip type toggle (show/hide return date)
        document.querySelectorAll('input[name="trip-type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const returnGroup = document.getElementById('return-date-group');
                if (this.value === 'oneway') {
                    returnGroup.style.display = 'none';
                } else {
                    returnGroup.style.display = 'block';
                }
            });
        });

        // Auto-sync destination from Flight to Hotel and Packages
        const flightToSelect = document.getElementById('flight-to');
        if (flightToSelect) {
            flightToSelect.addEventListener('change', function() {
                const destValue = this.value;
                const hotelDest = document.getElementById('hotel-destination');
                const packageDest = document.getElementById('package-destination');
                
                if (hotelDest) hotelDest.value = destValue;
                if (packageDest) packageDest.value = destValue;
            });
        }

        // Initialize with any pre-selected destination from URL
        <?php if ($destination_id): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // If we came from destination details, pre-fill dates
            const today = new Date();
            const nextWeek = new Date(today);
            nextWeek.setDate(nextWeek.getDate() + 7);
            
            const formatDate = (date) => {
                return date.toISOString().split('T')[0];
            };
            
            // Pre-fill package form
            document.getElementById('package-checkin').value = formatDate(today);
            document.getElementById('package-checkout').value = formatDate(nextWeek);
            
            // Auto-search packages
            setTimeout(searchPackages, 500);
        });
        <?php endif; ?>

        // Search Functions
        function searchFlights() {
            const from = document.getElementById('flight-from').value;
            const to = document.getElementById('flight-to').value;
            const depart = document.getElementById('flight-depart').value;
            const tripType = document.querySelector('input[name="trip-type"]:checked').value;
            const passengers = document.getElementById('flight-passengers').value;
            const flightClass = document.getElementById('flight-class').value;

            if (!from || !to || !depart) {
                alert('Please fill in all required fields');
                return;
            }

            if (tripType === 'roundtrip' && !document.getElementById('flight-return').value) {
                alert('Please select return date for round trip');
                return;
            }

            showLoading();

            // Simulate API call - in production, this would be an AJAX request to your server
            setTimeout(() => {
                // Fetch flights from database via AJAX
                fetch('get_flights.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        from: from,
                        to: to,
                        depart: depart,
                        passengers: passengers,
                        class: flightClass,
                        trip_type: tripType
                    })
                })
                .then(response => response.json())
                .then(data => {
                    displayFlightResults(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    displayFlightResults([]); // Show mock data if API fails
                });
            }, 1500);
        }

        function searchHotels() {
            const destination = document.getElementById('hotel-destination').value;
            const checkin = document.getElementById('hotel-checkin').value;
            const checkout = document.getElementById('hotel-checkout').value;
            const rooms = document.getElementById('hotel-rooms').value;
            const guests = document.getElementById('hotel-guests').value;

            if (!destination || !checkin || !checkout) {
                alert('Please fill in all required fields');
                return;
            }

            showLoading();

            setTimeout(() => {
                fetch('get_hotels_search.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        destination: destination,
                        checkin: checkin,
                        checkout: checkout,
                        rooms: rooms,
                        guests: guests
                    })
                })
                .then(response => response.json())
                .then(data => {
                    displayHotelResults(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    displayHotelResults([]);
                });
            }, 1500);
        }

        function searchPackages() {
            const from = document.getElementById('package-from').value;
            const destination = document.getElementById('package-destination').value;
            const checkin = document.getElementById('package-checkin').value;
            const checkout = document.getElementById('package-checkout').value;
            const travelers = document.getElementById('package-travelers').value;
            const budget = document.getElementById('package-budget').value;
            const flightClass = document.getElementById('package-flight-class').value;

            if (!destination || !checkin || !checkout) {
                alert('Please fill in all required fields');
                return;
            }

            showLoading();

            setTimeout(() => {
                fetch('get_packages.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        from: from,
                        destination: destination,
                        checkin: checkin,
                        checkout: checkout,
                        travelers: travelers,
                        budget: budget,
                        flight_class: flightClass
                    })
                })
                .then(response => response.json())
                .then(data => {
                    displayPackageResults(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    displayPackageResults([]);
                });
            }, 1500);
        }

        function showLoading() {
            document.getElementById('loading-spinner').classList.add('active');
            document.getElementById('results-grid').innerHTML = '';
            document.getElementById('package-summary').classList.add('hidden');
            document.getElementById('no-results').classList.add('hidden');
        }

        function displayFlightResults(flights) {
            document.getElementById('loading-spinner').classList.remove('active');
            document.getElementById('results-title').textContent = 'Available Flights';
            
            const grid = document.getElementById('results-grid');
            
            window.currentFlights = flights;
            
            if (!flights || flights.length === 0) {
                document.getElementById('no-results').classList.remove('hidden');
                return;
            }

            document.getElementById('no-results').classList.add('hidden');
            
            grid.innerHTML = flights.map(flight => `
                <div class="result-card" onclick="selectFlight(${flight.id})" data-flight-id="${flight.id}">
                    <div class="card-header">
                        <h3>${flight.airline}</h3>
                        <div class="price">$${flight.price_per_person} <small>per person</small></div>
                    </div>
                    <div class="card-body">
                        <div class="detail-row">
                            <span class="detail-label">From</span>
                            <span class="detail-value">${flight.departure_city}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Duration</span>
                            <span class="detail-value">${flight.duration_hours} hours</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Stops</span>
                            <span class="detail-value">${flight.stops === 0 ? 'Non-stop' : flight.stops + ' stop(s)'}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Departure</span>
                            <span class="detail-value">${flight.departure_time}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Arrival</span>
                            <span class="detail-value">${flight.arrival_time}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Class</span>
                            <span class="detail-value">${flight.flight_class}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Baggage</span>
                            <span class="detail-value">${flight.baggage_allowance}</span>
                        </div>
                        ${flight.refundable ? '<div class="detail-row"><span class="detail-label">Refundable</span><span class="detail-value text-accent"><i class="fas fa-check"></i> Yes</span></div>' : ''}
                        ${flight.meal_included ? '<div class="detail-row"><span class="detail-label">Meals</span><span class="detail-value text-accent"><i class="fas fa-utensils"></i> Included</span></div>' : ''}
                        <button class="book-btn" onclick="bookItem('flight', ${flight.id}, this); event.stopPropagation();">
                            <i class="fas fa-ticket-alt"></i> Select Flight
                        </button>
                    </div>
                </div>
            `).join('');
        }

        function displayHotelResults(hotels) {
            document.getElementById('loading-spinner').classList.remove('active');
            document.getElementById('results-title').textContent = 'Available Hotels';
            
            const grid = document.getElementById('results-grid');
            
            window.currentHotels = hotels;
            
            if (!hotels || hotels.length === 0) {
                document.getElementById('no-results').classList.remove('hidden');
                return;
            }

            document.getElementById('no-results').classList.add('hidden');
            
            grid.innerHTML = hotels.map(hotel => {
                const amenities = hotel.amenities ? JSON.parse(hotel.amenities) : [];
                return `
                    <div class="result-card" onclick="selectHotel(${hotel.id})" data-hotel-id="${hotel.id}">
                        <div class="card-header">
                            <h3>${hotel.hotel_name}</h3>
                            <div class="price">$${hotel.price_per_night} <small>per night</small></div>
                        </div>
                        <div class="card-body">
                            <div class="detail-row">
                                <span class="detail-label">Rating</span>
                                <span class="detail-value">${hotel.hotel_rating} <i class="fas fa-star" style="color: #fbbf24;"></i></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Type</span>
                                <span class="detail-value">${hotel.hotel_type.charAt(0).toUpperCase() + hotel.hotel_type.slice(1)} Budget</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Check-in</span>
                                <span class="detail-value">${hotel.check_in_time}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Check-out</span>
                                <span class="detail-value">${hotel.check_out_time}</span>
                            </div>
                            ${hotel.free_cancellation ? '<div class="detail-row"><span class="detail-label">Cancellation</span><span class="detail-value text-accent"><i class="fas fa-check"></i> Free</span></div>' : ''}
                            ${hotel.breakfast_included ? '<div class="detail-row"><span class="detail-label">Breakfast</span><span class="detail-value text-accent"><i class="fas fa-utensils"></i> Included</span></div>' : ''}
                            <div class="amenities">
                                ${amenities.map(a => `<span class="amenity-tag">${a}</span>`).join('')}
                            </div>
                            <button class="book-btn" onclick="bookItem('hotel', ${hotel.id}, this); event.stopPropagation();">
                                <i class="fas fa-hotel"></i> Select Hotel
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function displayPackageResults(packages) {
            document.getElementById('loading-spinner').classList.remove('active');
            document.getElementById('results-title').textContent = 'Available Packages';
            
            const grid = document.getElementById('results-grid');
            
            if (!packages || packages.length === 0) {
                document.getElementById('no-results').classList.remove('hidden');
                return;
            }

            document.getElementById('no-results').classList.add('hidden');
            
            // Store packages data for later use
            window.currentPackages = packages;
            
            grid.innerHTML = packages.map((pkg, index) => `
                <div class="result-card" onclick="selectPackage(${index})" data-package-index="${index}">
                    <div class="card-header">
                        <h3>${pkg.hotel.hotel_name} + Flight</h3>
                        <div class="price">$${pkg.total_price} <small>total</small></div>
                    </div>
                    <div class="card-body">
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-plane"></i> Airline</span>
                            <span class="detail-value">${pkg.flight.airline}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-hotel"></i> Hotel</span>
                            <span class="detail-value">${pkg.hotel.hotel_rating} ★ Hotel</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Duration</span>
                            <span class="detail-value">${pkg.duration} nights</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Flight Class</span>
                            <span class="detail-value">${pkg.flight.flight_class}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Budget Type</span>
                            <span class="detail-value">${pkg.budget_type.charAt(0).toUpperCase() + pkg.budget_type.slice(1)}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Savings</span>
                            <span class="detail-value" style="color: #10b981;">Save $${pkg.savings}</span>
                        </div>
                        <button class="book-btn" onclick="viewPackageDetails(${index}); event.stopPropagation();">
                            <i class="fas fa-gem"></i> View Package
                        </button>
                    </div>
                </div>
            `).join('');
        }

        function selectPackage(index) {
            const pkg = window.currentPackages[index];
            window.selectedPackage = pkg;
            
            // Highlight selected card
            document.querySelectorAll('.result-card').forEach(card => {
                card.classList.remove('selected');
            });
            document.querySelector(`[data-package-index="${index}"]`).classList.add('selected');
            
            // Show package summary
            const summary = document.getElementById('package-summary');
            summary.classList.remove('hidden');
            
            const travelers = document.getElementById('package-travelers').value || 2;
            const checkin = document.getElementById('package-checkin').value;
            const checkout = document.getElementById('package-checkout').value;
            const nights = Math.round((new Date(checkout) - new Date(checkin)) / (1000 * 60 * 60 * 24));
            
            document.getElementById('package-details').innerHTML = `
                <div class="package-item">
                    <div class="label">Flight</div>
                    <div class="value">${pkg.flight.airline}</div>
                    <div style="font-size: 0.9rem;">${pkg.flight.departure_city} → ${pkg.destination_name}</div>
                </div>
                <div class="package-item">
                    <div class="label">Hotel</div>
                    <div class="value">${pkg.hotel.hotel_name}</div>
                    <div style="font-size: 0.9rem;">${nights} nights • ${travelers} travelers</div>
                </div>
                <div class="package-item">
                    <div class="label">Budget Type</div>
                    <div class="value">${pkg.budget_type.charAt(0).toUpperCase() + pkg.budget_type.slice(1)}</div>
                </div>
            `;
            
            document.getElementById('package-total').innerHTML = `$${pkg.total_price}`;
        }

        function viewPackageDetails(index) {
            selectPackage(index);
            // Smooth scroll to package summary
            document.getElementById('package-summary').scrollIntoView({ behavior: 'smooth' });
        }

        function selectFlight(id) {
            // Implement flight selection logic
            console.log('Selected flight:', id);
        }

        function selectHotel(id) {
            // Implement hotel selection logic
            console.log('Selected hotel:', id);
        }

        // Shopping Bag Logic
        let tripBag = {
            flight: null,
            hotel: null,
            travelers: 2,
            checkin: '',
            checkout: '',
            from: ''
        };

        function updateBagUI() {
            const bagEl = document.getElementById('shopping-bag');
            const itemsEl = document.getElementById('bag-items');
            
            let count = 0;
            let html = '';
            
            if (tripBag.flight) {
                count++;
                html += `
                <div class="bag-item">
                    <div class="bag-item-details">
                        <h5><i class="fas fa-plane"></i> ${tripBag.flight.airline}</h5>
                        <p>${tripBag.flight.departure_city} to ${tripBag.flight.destination_name || 'Destination'}</p>
                    </div>
                    <div>
                        <span class="bag-item-price">$${tripBag.flight.price_per_person}</span>
                        <i class="fas fa-times bag-item-remove" onclick="removeFromBag('flight')"></i>
                    </div>
                </div>`;
            }
            
            if (tripBag.hotel) {
                count++;
                html += `
                <div class="bag-item">
                    <div class="bag-item-details">
                        <h5><i class="fas fa-hotel"></i> ${tripBag.hotel.hotel_name}</h5>
                        <p>${tripBag.hotel.hotel_rating} ★ Hotel</p>
                    </div>
                    <div>
                        <span class="bag-item-price">$${tripBag.hotel.price_per_night}/nt</span>
                        <i class="fas fa-times bag-item-remove" onclick="removeFromBag('hotel')"></i>
                    </div>
                </div>`;
            }
            
            if (count > 0) {
                bagEl.classList.add('active');
                document.getElementById('bag-total-items').textContent = count + (count === 1 ? ' item' : ' items');
                itemsEl.innerHTML = html;
            } else {
                bagEl.classList.remove('active');
            }
        }

        function removeFromBag(type) {
            tripBag[type] = null;
            updateBagUI();
            
            // Restore original button appearance if in DOM
            const btn = document.querySelector(`.btn-${type}-added`);
            if (btn) {
                btn.classList.remove('btn-added', `btn-${type}-added`);
                btn.innerHTML = type === 'flight' ? '<i class="fas fa-ticket-alt"></i> Select Flight' : '<i class="fas fa-hotel"></i> Select Hotel';
            }
        }

        function bookItem(type, id, btnElement) {
            <?php if (!isset($_SESSION['user_id'])): ?>
                if (confirm('Please sign in to book. Go to login page?')) {
                    window.location.href = '../auth/login.html';
                }
                return;
            <?php endif; ?>

            if (type === 'flight') {
                const flight = window.currentFlights.find(f => f.id == id);
                if (flight) {
                    tripBag.flight = flight;
                    tripBag.travelers = document.getElementById('flight-passengers').value || 2;
                    tripBag.from = flight.departure_city;
                    // Try to grab checkin/checkout from hotel tab if it exists
                    if (!tripBag.checkin) tripBag.checkin = document.getElementById('flight-depart').value;
                    if (!tripBag.checkout) tripBag.checkout = document.getElementById('flight-return').value || tripBag.checkin;
                }
            } else if (type === 'hotel') {
                const hotel = window.currentHotels.find(h => h.id == id);
                if (hotel) {
                    tripBag.hotel = hotel;
                    tripBag.travelers = document.getElementById('hotel-guests').value || 2;
                    tripBag.checkin = document.getElementById('hotel-checkin').value;
                    tripBag.checkout = document.getElementById('hotel-checkout').value;
                }
            }
            
            // Visual feedback on button
            if (btnElement) {
                btnElement.classList.add('btn-added', `btn-${type}-added`);
                btnElement.innerHTML = '<i class="fas fa-check"></i> Added to Bag';
            }
            
            updateBagUI();
        }

        function submitBooking(data) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'package_booking_confirmation.php';
            
            for (const key in data) {
                if (data.hasOwnProperty(key)) {
                    const hiddenField = document.createElement('input');
                    hiddenField.type = 'hidden';
                    hiddenField.name = key;
                    hiddenField.value = data[key];
                    form.appendChild(hiddenField);
                }
            }
            
            document.body.appendChild(form);
            form.submit();
        }

        function checkoutBag() {
            if (!tripBag.flight && !tripBag.hotel) {
                alert('Your bag is empty.');
                return;
            }
            
            const data = {
                flight_id: tripBag.flight ? tripBag.flight.id : 0,
                hotel_id: tripBag.hotel ? tripBag.hotel.id : 0,
                travelers: tripBag.travelers || 2,
                checkin: tripBag.checkin || '',
                checkout: tripBag.checkout || '',
                from: tripBag.from || (tripBag.flight ? tripBag.flight.departure_city : '')
            };
            
            if (tripBag.flight) {
                data.airline = tripBag.flight.airline;
                data.flight_price = tripBag.flight.price_per_person || tripBag.flight.price;
                data.departure_city = tripBag.flight.departure_city;
                data.flight_class = tripBag.flight.flight_class;
                data.destination_id = tripBag.flight.destination_id;
            }
            
            if (tripBag.hotel) {
                data.hotel_name = tripBag.hotel.hotel_name;
                data.hotel_rating = tripBag.hotel.hotel_rating;
                data.hotel_price = tripBag.hotel.price_per_night;
                data.hotel_type = tripBag.hotel.hotel_type;
                data.destination_id = tripBag.hotel.destination_id;
            }
            
            submitBooking(data);
        }

        function bookPackage() {
            if (!window.selectedPackage) {
                alert('Please select a package first');
                return;
            }

            <?php if (!isset($_SESSION['user_id'])): ?>
                if (confirm('Please sign in to book. Go to login page?')) {
                    window.location.href = '../auth/login.html';
                }
                return;
            <?php endif; ?>

            const pkg = window.selectedPackage;
            const travelers = document.getElementById('package-travelers').value || 2;
            const checkin = document.getElementById('package-checkin').value;
            const checkout = document.getElementById('package-checkout').value;
            const from = document.getElementById('package-from').value;

            const data = {
                hotel_id: pkg.hotel.id,
                flight_id: pkg.flight.id,
                travelers: travelers,
                checkin: checkin,
                checkout: checkout,
                from: from,
                destination_id: pkg.flight.destination_id || pkg.hotel.destination_id || document.getElementById('package-destination').value,
                
                hotel_name: pkg.hotel.hotel_name,
                hotel_rating: pkg.hotel.hotel_rating,
                hotel_price: pkg.hotel.price_per_night,
                hotel_type: pkg.hotel.hotel_type,
                
                airline: pkg.flight.airline,
                flight_price: pkg.flight.price_per_person || pkg.flight.price,
                departure_city: pkg.flight.departure_city,
                flight_class: pkg.flight.flight_class
            };
            
            submitBooking(data);
        }

        function quickSelect(destination) {
            // Quick select from featured offers
            const destSelects = ['hotel-destination', 'package-destination'];
            destSelects.forEach(id => {
                const select = document.getElementById(id);
                if (select) {
                    // Find option containing the destination name
                    for (let option of select.options) {
                        if (option.text.includes(destination)) {
                            select.value = option.value;
                            break;
                        }
                    }
                }
            });
            
            // Switch to packages tab and search
            document.querySelector('[data-tab="packages"]').click();
            setTimeout(searchPackages, 500);
        }

        // Sort functionality
        document.getElementById('sort-by').addEventListener('change', function() {
            const sortBy = this.value;
            const grid = document.getElementById('results-grid');
            const cards = Array.from(grid.children);
            
            cards.sort((a, b) => {
                if (sortBy === 'price_asc') {
                    const priceA = parseFloat(a.querySelector('.price').textContent.replace(/[^0-9.-]+/g, ''));
                    const priceB = parseFloat(b.querySelector('.price').textContent.replace(/[^0-9.-]+/g, ''));
                    return priceA - priceB;
                } else if (sortBy === 'price_desc') {
                    const priceA = parseFloat(a.querySelector('.price').textContent.replace(/[^0-9.-]+/g, ''));
                    const priceB = parseFloat(b.querySelector('.price').textContent.replace(/[^0-9.-]+/g, ''));
                    return priceB - priceA;
                } else if (sortBy === 'rating') {
                    const ratingA = parseFloat(a.querySelector('.detail-row .detail-value')?.textContent) || 0;
                    const ratingB = parseFloat(b.querySelector('.detail-row .detail-value')?.textContent) || 0;
                    return ratingB - ratingA;
                }
            });
            
            grid.innerHTML = '';
            cards.forEach(card => grid.appendChild(card));
        });
    </script>
</body>
</html>