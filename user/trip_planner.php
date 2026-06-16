<?php
// Start session at the very beginning
session_start();

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../database/dbconfig.php';

// Block Google-only users — they must create a full account first
if (isset($_SESSION['auth_provider']) && $_SESSION['auth_provider'] === 'google') {
    $google_email = isset($_SESSION['google_email']) ? urlencode($_SESSION['google_email']) : '';
    $google_name = isset($_SESSION['user_name']) ? urlencode($_SESSION['user_name']) : '';
    header("Location: ../auth/register.html?upgrade=1&email={$google_email}&name={$google_name}");
    exit;
}

// Get user ID and name from session if available
$user_id = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User';

// Get additional user details from database
$user_query = "SELECT name, email, profile_pic, user_level FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_details = $user_result->fetch_assoc();
$user_stmt->close();

if ($user_details) {
    $userName = $user_details['name']; // Update with exact name from database
    $user_level = $user_details['user_level'] ?? 'normal';
    $user_email = $user_details['email'] ?? '';
    $user_profile_pic = $user_details['profile_pic'] ?? '../image/default-avatar.png';
}

// Fetch all destinations for dropdown
$destinations = [];
$dest_query = "SELECT id, name, location, type, image_urls, budget, best_season FROM destinations ORDER BY name";
$dest_result = $conn->query($dest_query);
while ($row = $dest_result->fetch_assoc()) {
    $destinations[] = $row;
}

// Fetch all departure cities from flights for location dropdown
$locations = [];
$loc_query = "SELECT DISTINCT from_city AS departure_city FROM flights WHERE from_city IS NOT NULL AND from_city != '' ORDER BY from_city";
$loc_result = $conn->query($loc_query);
if ($loc_result) {
    while ($row = $loc_result->fetch_assoc()) {
        $locations[] = $row['departure_city'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TripMate - Plan Your Trip</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <!-- Flatpickr for date picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <style>
        /* ========================================================
           ULTRA-PREMIUM "INDIGO & NEON CYAN" THEME
           ======================================================== */
        :root {
            --bg-base: #f1f5f9;
            --bg-surface: rgba(255, 255, 255, 0.85);
            --text-main: #0f172a !important;
            --text-muted: #475569 !important;
            --primary: #4f46e5;
            --secondary: #06b6d4;
            --nav-bg: rgba(255, 255, 255, 0.85);
            --card-border: rgba(79, 70, 229, 0.15);
            --shadow-color: rgba(15, 23, 42, 0.08);
            --glow-color: rgba(6, 182, 212, 0.4);
            --danger: #ef4444;
            --success: #10b981;
        }

        body.dark-mode {
            --bg-base: #020617;
            --bg-surface: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc !important;
            --text-muted: #94a3b8 !important;
            --primary: #818cf8;
            --secondary: #22d3ee;
            --nav-bg: rgba(15, 23, 42, 0.85);
            --card-border: rgba(255, 255, 255, 0.1);
            --shadow-color: rgba(0, 0, 0, 0.6);
            --glow-color: rgba(34, 211, 238, 0.3);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-base);
            background-image: radial-gradient(circle at top right, rgba(6, 182, 212, 0.05), transparent 40%),
                radial-gradient(circle at bottom left, rgba(79, 70, 229, 0.05), transparent 40%);
            color: var(--text-main);
            transition: background-color 0.4s ease, color 0.4s ease;
            line-height: 1.6;
            min-height: 100vh;
            padding-top: 90px;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            color: var(--text-main);
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        p {
            color: var(--text-muted);
        }

        /* Scroll Progress Bar */
        .scroll-progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 4px;
            width: 0%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            z-index: 9999;
            transition: width 0.1s ease-out;
            box-shadow: 0 0 10px var(--glow-color);
        }

        /* --- FLOATING PILL NAVBAR --- */
        .navbar {
            position: fixed;
            top: 20px;
            left: 5%;
            width: 90%;
            height: 70px;
            background: var(--nav-bg) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
            z-index: 1000;
            box-shadow: 0 10px 30px var(--shadow-color);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.4rem;
            font-weight: 800;
            text-decoration: none;
        }

        .logo i {
            color: var(--primary);
            font-size: 1.5rem;
            transform: rotate(-10deg);
            transition: transform 0.3s;
        }

        .logo:hover i {
            transform: rotate(0deg) scale(1.1);
        }

        .brand-text .trip {
            color: var(--text-main);
        }

        .brand-text .mate {
            color: var(--secondary);
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-link {
            color: var(--text-main);
            text-decoration: none;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 30px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link:hover {
            color: var(--primary);
            background: var(--bg-surface);
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
            justify-content: center;
            align-items: center;
            transition: all 0.3s;
            box-shadow: 0 4px 10px var(--shadow-color);
        }

        .theme-toggle:hover {
            transform: rotate(20deg) scale(1.1);
            color: var(--primary);
            border-color: var(--primary);
        }

        /* Profile Menu */
        .profile-menu {
            position: relative;
        }

        .profile-btn {
            background: var(--bg-surface);
            border: 1px solid var(--card-border);
            color: var(--text-main);
            padding: 10px 20px;
            border-radius: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .profile-btn:hover {
            border-color: var(--secondary);
            color: var(--secondary);
        }

        .profile-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 10px;
            width: 220px;
            background: var(--bg-surface);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 15px;
            box-shadow: 0 20px 40px var(--shadow-color);
            backdrop-filter: blur(20px);
            display: none;
            z-index: 1001;
        }

        .profile-dropdown.active {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .profile-dropdown a,
        .profile-dropdown button {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            color: var(--text-main);
            text-decoration: none;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.2s;
            width: 100%;
            text-align: left;
            border: none;
            background: none;
            cursor: pointer;
        }

        .profile-dropdown a:hover,
        .profile-dropdown button:hover {
            background: var(--bg-base);
            color: var(--primary);
        }

        .profile-dropdown a i,
        .profile-dropdown button i {
            width: 20px;
            color: var(--secondary);
        }

        .profile-dropdown hr {
            margin: 10px 0;
            border: none;
            border-top: 1px solid var(--card-border);
        }

        /* Main Content */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Glass Card Effect */
        .glass-card {
            background: var(--bg-surface);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 50px var(--shadow-color);
            backdrop-filter: blur(20px);
            margin-bottom: 30px;
        }

        /* Form Input Styles */
        .form-input {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid var(--card-border);
            border-radius: 16px;
            background: var(--bg-surface);
            color: var(--text-main);
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.3s;
        }

        /* Fix for select dropdown options - BLACK BACKGROUND WITH WHITE TEXT */
        select.form-input option {
            background-color: #000000 !important;
            color: #ffffff !important;
            padding: 12px !important;
            font-size: 1rem !important;
        }

        /* For dark mode consistency */
        body.dark-mode select.form-input option {
            background-color: #000000 !important;
            color: #ffffff !important;
        }

        /* Focus state */
        .form-input:focus {
            outline: none;
            border-color: var(--secondary);
            background: rgba(6, 182, 212, 0.05);
            box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.1);
        }

        select.form-input {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
        }

        /* Range Slider */
        .range-slider {
            -webkit-appearance: none;
            width: 100%;
            height: 8px;
            border-radius: 5px;
            background: linear-gradient(to right, var(--secondary) 0%, var(--secondary) 50%, var(--card-border) 50%, var(--card-border) 100%);
            outline: none;
        }

        .range-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: white;
            border: 2px solid var(--secondary);
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px var(--glow-color);
        }

        .range-slider::-webkit-slider-thumb:hover {
            transform: scale(1.2);
            background: var(--secondary);
        }

        /* Submit Button - Improved */
        .submit-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 18px 32px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 800;
            width: auto;
            min-width: 260px;
            font-size: 1.2rem;
            transition: all 0.3s;
            box-shadow: 0 15px 30px rgba(79, 70, 229, 0.4);
            display: flex;
            justify-content: center;
            gap: 12px;
            align-items: center;
            margin: 20px 0 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .submit-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(79, 70, 229, 0.5);
        }

        /* Result Cards */
        .result-card {
            background: var(--bg-surface);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.25, 1, 0.5, 1);
            backdrop-filter: blur(10px);
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .result-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            opacity: 0.8;
            transition: opacity 0.3s;
            z-index: 2;
        }

        .result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px var(--shadow-color);
            border-color: var(--secondary);
        }

        .result-card:hover::before {
            opacity: 1;
        }

        .result-card.selected {
            border: 2px solid var(--secondary);
            box-shadow: 0 0 0 2px var(--glow-color);
        }

        .hotel-image,
        .flight-image {
            height: 160px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .hotel-badge,
        .flight-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-low {
            background: var(--success);
            color: white;
        }

        .badge-medium {
            background: #f59e0b;
            color: white;
        }

        .badge-high {
            background: var(--danger);
            color: white;
        }

        /* Summary Card */
        .summary-card {
            background: linear-gradient(135deg, var(--primary), #2a0a8a);
            border-radius: 24px;
            padding: 30px;
            color: white;
            box-shadow: 0 20px 40px rgba(79, 70, 229, 0.3);
            margin-bottom: 30px;
        }

        .summary-card h3,
        .summary-card p {
            color: white;
        }

        /* Price Display */
        .price-tag {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--secondary);
            line-height: 1.2;
        }

        .price-label {
            font-size: 1rem;
            color: var(--text-muted);
            margin-left: 4px;
        }

        /* Tab Buttons - Improved */
        .tab-button {
            padding: 14px 32px;
            font-weight: 700;
            font-size: 1.1rem;
            border-radius: 50px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid var(--card-border);
            background: var(--bg-surface);
            color: var(--text-main);
            min-width: 140px;
            text-align: center;
        }

        .tab-button.active {
            background: var(--secondary);
            color: white;
            border-color: var(--secondary);
            box-shadow: 0 8px 16px rgba(6, 182, 212, 0.3);
        }

        .tab-button:not(.active):hover {
            border-color: var(--secondary);
            color: var(--secondary);
            transform: translateY(-2px);
        }

        /* ===== FIXED BUTTON STYLES ===== */
        /* Select Button - Improved sizing and positioning */
        .select-btn {
            background: var(--secondary);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 14px 24px;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.3px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            max-width: 220px;
            margin: 15px auto 5px;
            display: block;
            text-align: center;
            box-shadow: 0 8px 16px rgba(6, 182, 212, 0.3);
            text-transform: uppercase;
        }

        .select-btn:hover {
            background: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 12px 24px rgba(79, 70, 229, 0.4);
        }

        .select-btn.selected {
            background: var(--success);
            box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);
        }

        .select-btn:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
            opacity: 0.6;
        }

        /* Button container utility */
        .button-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin: 25px 0 15px;
        }

        /* Result cards grid spacing */
        #hotelsResults,
        #flightsResults {
            gap: 30px;
            margin-top: 30px;
        }

        /* Card content spacing */
        .result-card .p-5 {
            padding: 24px 20px 20px;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .result-card .border-t {
            margin-top: auto;
            padding-top: 20px;
        }

        /* Selected Items Summary improvements */
        .selected-items {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            padding: 24px;
            margin-top: 25px;
            backdrop-filter: blur(10px);
        }

        .selected-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            font-size: 1.1rem;
        }

        .selected-item:last-child {
            border-bottom: none;
        }

        /* Loading Spinner */
        .loading-spinner {
            border: 3px solid var(--card-border);
            border-top: 3px solid var(--secondary);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 30px;
            right: 30px;
            z-index: 3000;
        }

        .toast {
            background: var(--bg-surface);
            border-radius: 16px;
            padding: 18px 24px;
            margin-top: 10px;
            box-shadow: 0 15px 40px var(--shadow-color);
            border-left: 8px solid var(--primary);
            color: var(--text-main);
            font-weight: 700;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            border: 1px solid var(--card-border);
            backdrop-filter: blur(10px);
            min-width: 300px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .toast.toast-success {
            border-left-color: var(--success);
        }

        .toast.toast-error {
            border-left-color: var(--danger);
        }

        .toast.toast-warning {
            border-left-color: #f59e0b;
        }

        /* Session restore message */
        .session-restore {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--bg-surface);
            border-radius: 16px;
            padding: 16px 24px;
            box-shadow: 0 15px 40px var(--shadow-color);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 9999;
            animation: slideInRight 0.3s ease;
            border-left: 4px solid var(--secondary);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(10px);
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                width: 95%;
                left: 2.5%;
                padding: 0 20px;
            }

            .glass-card {
                padding: 20px;
            }

            .grid {
                grid-template-columns: 1fr;
            }
        }

        .hidden {
            display: none !important;
        }

        /* Utility classes */
        .ml-2 {
            margin-left: 8px;
        }

        .mr-2 {
            margin-right: 8px;
        }

        .mb-4 {
            margin-bottom: 16px;
        }

        .mt-4 {
            margin-top: 16px;
        }

        .p-4 {
            padding: 16px;
        }

        .text-lg {
            font-size: 1.125rem;
        }

        .font-bold {
            font-weight: 700;
        }
    </style>
</head>

<body>

    <div class="scroll-progress-bar" id="scrollBar"></div>

    <!-- Session restore notification -->
    <div id="sessionRestoreMsg" class="session-restore" style="display: none;">
        <i class="fas fa-sync-alt fa-spin" style="color: var(--secondary);"></i>
        <span>Restoring your session...</span>
    </div>

    <!-- Navigation -->
    <nav class="navbar" role="navigation" aria-label="Main navigation">
        <a href="../main/index.html" class="logo" style="text-decoration:none;">
            <i class="fa-solid fa-paper-plane"></i>
            <span class="brand-text"><span class="trip">Trip</span><span class="mate">Mate</span></span>
        </a>

        <div class="nav-right">
            <a href="../main/index.html" class="nav-link"><i class="fas fa-home"></i> Home</a>
            <a href="../search/search.html" class="nav-link"><i class="fas fa-search"></i> Search</a>
            <a href="user_dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <button class="theme-toggle" id="themeToggle" aria-label="Switch dark/light mode"><i class="fas fa-moon"></i></button>

            <!-- Profile Menu -->
            <div class="profile-menu">
                <button class="profile-btn" id="profileBtn">
                    <?php if (!empty($user_profile_pic) && $user_profile_pic != '../image/default-avatar.png'): ?>
                        <img src="<?php echo htmlspecialchars($user_profile_pic); ?>" alt="Profile" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                    <?php endif; ?>
                    <span class="hidden sm:inline"><?php echo htmlspecialchars($userName); ?></span>
                    <i class="fas fa-chevron-down text-xs"></i>
                </button>

                <div class="profile-dropdown" id="userDropdown">
                    <div class="p-3 mb-2" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); border-radius: 12px; color: white;">
                        <div class="font-bold"><?php echo htmlspecialchars($userName); ?></div>
                        <div class="text-xs opacity-80"><?php echo htmlspecialchars($user_email); ?></div>
                        <?php if (isset($user_level) && $user_level == 'high'): ?>
                            <span class="inline-block mt-2 text-xs bg-white/20 px-2 py-1 rounded-full">Premium Member</span>
                        <?php endif; ?>
                    </div>
                    <a href="user_profile.php"><i class="fas fa-user"></i> My Profile</a>
                    <a href="favourites.php"><i class="fas fa-heart"></i> Favorites</a>
                    <a href="my_trips.php"><i class="fas fa-suitcase"></i> My Trips</a>
                    <hr>
                    <button onclick="logout()"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <!-- Header -->
        <div class="text-center mb-8 animate-fadeInUp">
            <h1 class="text-4xl md:text-5xl font-extrabold mb-4" style="color: var(--text-main);">
                Plan Your <span style="color: var(--secondary);">Perfect Trip</span>
            </h1>
            <p class="text-lg max-w-2xl mx-auto" style="color: var(--text-muted);">
                Customize every aspect of your journey with our intelligent trip planner
            </p>
        </div>

        <!-- Main Form Card -->
        <div class="glass-card">
            <form id="tripPlannerForm" onsubmit="event.preventDefault(); planTrip();">
                <!-- Destination Selection -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold mb-2" style="color: var(--text-main);">
                        <i class="fas fa-map-marker-alt" style="color: var(--secondary); mr-2"></i>Select Destination
                    </label>
                    <select id="destination" name="destination_id" required class="form-input">
                        <option value="">Choose your dream destination</option>
                        <?php foreach ($destinations as $dest):
                            $image = json_decode($dest['image_urls'] ?? '[]', true);
                            $thumb = !empty($image) ? $image[0] : '../image/placeholder.jpg';
                        ?>
                            <option value="<?php echo $dest['id']; ?>" data-budget="<?php echo $dest['budget']; ?>" data-location="<?php echo htmlspecialchars($dest['location']); ?>">
                                <?php echo htmlspecialchars($dest['name'] . ' - ' . $dest['location'] . ' (₹' . number_format($dest['budget']) . '/day)'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Departure Location -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold mb-2" style="color: var(--text-main);">
                        <i class="fas fa-plane-departure" style="color: var(--secondary); mr-2"></i>Your Departure City
                    </label>
                    <select id="departureCity" name="departure_city" required class="form-input">
                        <option value="">Select your departure city</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo htmlspecialchars($loc); ?>"><?php echo htmlspecialchars($loc); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Hotel Budget Slider -->
                    <div class="p-5" style="background: var(--bg-base); border-radius: 16px;">
                        <label class="block text-sm font-semibold mb-4" style="color: var(--text-main);">
                            <i class="fas fa-hotel" style="color: var(--secondary); mr-2"></i>Hotel Budget (per night)
                        </label>
                        <div class="space-y-3">
                            <input type="range" id="hotelBudget" min="0" max="10000" value="5000" step="100" class="range-slider w-full">
                            <div class="flex justify-between items-center">
                                <span class="text-sm" style="color: var(--text-muted);">Min: ₹0</span>
                                <span class="text-lg font-bold" style="color: var(--secondary);" id="hotelBudgetValue">₹5,000</span>
                                <span class="text-sm" style="color: var(--text-muted);">Max: ₹10,000</span>
                            </div>
                        </div>
                    </div>

                    <!-- Flight Budget Slider -->
                    <div class="p-5" style="background: var(--bg-base); border-radius: 16px;">
                        <label class="block text-sm font-semibold mb-4" style="color: var(--text-main);">
                            <i class="fas fa-plane" style="color: var(--secondary); mr-2"></i>Flight Budget (per person)
                        </label>
                        <div class="space-y-3">
                            <input type="range" id="flightBudget" min="1000" max="50000" value="25000" step="500" class="range-slider w-full">
                            <div class="flex justify-between items-center">
                                <span class="text-sm" style="color: var(--text-muted);">Min: ₹1,000</span>
                                <span class="text-lg font-bold" style="color: var(--secondary);" id="flightBudgetValue">₹25,000</span>
                                <span class="text-sm" style="color: var(--text-muted);">Max: ₹50,000</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- Travel Dates -->
                    <div>
                        <label class="block text-sm font-semibold mb-2" style="color: var(--text-main);">
                            <i class="fas fa-calendar-alt" style="color: var(--secondary); mr-2"></i>Start Date
                        </label>
                        <input type="text" id="startDate" class="form-input" placeholder="Select start date" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2" style="color: var(--text-main);">
                            <i class="fas fa-calendar-check" style="color: var(--secondary); mr-2"></i>End Date
                        </label>
                        <input type="text" id="endDate" class="form-input" placeholder="Select end date" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2" style="color: var(--text-main);">
                            <i class="fas fa-users" style="color: var(--secondary); mr-2"></i>Number of Travelers
                        </label>
                        <div class="relative">
                            <input type="number" id="travelers" min="1" max="10" value="2" class="form-input">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <i class="fas fa-user" style="color: var(--text-muted);"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Preferences -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <label class="flex items-center space-x-3 p-3 rounded-lg cursor-pointer hover:bg-[var(--bg-base)] transition" style="background: var(--bg-base);">
                        <input type="checkbox" id="freeCancellation" class="form-checkbox h-5 w-5" style="color: var(--secondary);">
                        <span class="text-sm" style="color: var(--text-main);">Free Cancellation</span>
                    </label>
                    <label class="flex items-center space-x-3 p-3 rounded-lg cursor-pointer hover:bg-[var(--bg-base)] transition" style="background: var(--bg-base);">
                        <input type="checkbox" id="breakfastIncluded" class="form-checkbox h-5 w-5" style="color: var(--secondary);">
                        <span class="text-sm" style="color: var(--text-main);">Breakfast Included</span>
                    </label>
                    <label class="flex items-center space-x-3 p-3 rounded-lg cursor-pointer hover:bg-[var(--bg-base)] transition" style="background: var(--bg-base);">
                        <input type="checkbox" id="refundableFlights" class="form-checkbox h-5 w-5" style="color: var(--secondary);">
                        <span class="text-sm" style="color: var(--text-main);">Refundable Flights</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="submit-btn">
                    <i class="fas fa-magic"></i>
                    <span>Search Options</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
        </div>

        <!-- Results Section (Initially Hidden) -->
        <div id="resultsSection" class="hidden space-y-8">
            <!-- Trip Summary -->
            <div class="summary-card">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h3 class="text-2xl font-bold mb-2">Your Trip Summary</h3>
                        <p class="text-white/80" id="tripSummaryText"></p>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold" style="color: var(--secondary);" id="totalBudget">₹0</div>
                        <div class="text-white/80 text-sm">Estimated Total Budget</div>
                    </div>
                </div>

                <!-- Selected Items Summary -->
                <div class="selected-items" id="selectedItemsSummary" style="display: none;">
                    <h4 class="font-semibold mb-2 text-white">Your Selections:</h4>
                    <div id="selectedHotelInfo" class="selected-item">
                        <span>Hotel:</span>
                        <span class="font-semibold" style="color: var(--secondary);" id="selectedHotelName">None selected</span>
                    </div>
                    <div id="selectedFlightInfo" class="selected-item">
                        <span>Flight:</span>
                        <span class="font-semibold" style="color: var(--secondary);" id="selectedFlightName">None selected</span>
                    </div>
                </div>
            </div>

            <!-- Tabs for Hotels and Flights -->
            <div class="flex space-x-2 pb-2">
                <button onclick="switchTab('hotels')" id="hotelsTab" class="tab-button active">
                    <i class="fas fa-hotel mr-2"></i>Hotels <span id="hotelCount" class="ml-1 text-sm">(0)</span>
                </button>
                <button onclick="switchTab('flights')" id="flightsTab" class="tab-button">
                    <i class="fas fa-plane mr-2"></i>Flights <span id="flightCount" class="ml-1 text-sm">(0)</span>
                </button>
            </div>

            <!-- Hotels Results -->
            <div id="hotelsResults" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Results will be dynamically inserted here -->
            </div>

            <!-- Flights Results -->
            <div id="flightsResults" class="hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Results will be dynamically inserted here -->
            </div>

            <!-- Loading Spinner -->
            <div id="loadingSpinner" class="hidden text-center py-12">
                <div class="loading-spinner mx-auto mb-4"></div>
                <p style="color: var(--text-muted);">Finding the best options for you...</p>
            </div>
        </div>
    </div>

    <!-- Toast Notifications Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
        // Scroll Progress Bar
        window.addEventListener('scroll', () => {
            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = height > 0 ? (winScroll / height) * 100 : 0;

            const topBar = document.getElementById("scrollBar");
            if (topBar) topBar.style.width = scrolled + "%";
        });

        // Theme Toggle
        const toggleBtn = document.getElementById('themeToggle');
        const body = document.body;
        const icon = toggleBtn.querySelector('i');

        if (localStorage.getItem('tripmate-theme') === 'dark') {
            body.classList.add('dark-mode');
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        }

        toggleBtn.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            if (body.classList.contains('dark-mode')) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
                localStorage.setItem('tripmate-theme', 'dark');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
                localStorage.setItem('tripmate-theme', 'light');
            }
        });

        // Profile dropdown toggle
        const profileBtn = document.getElementById('profileBtn');
        const userDropdown = document.getElementById('userDropdown');

        if (profileBtn && userDropdown) {
            profileBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                userDropdown.classList.toggle('active');
            });

            document.addEventListener('click', (e) => {
                if (!e.target.closest('#profileBtn') && !e.target.closest('#userDropdown')) {
                    userDropdown.classList.remove('active');
                }
            });
        }

        // Global variables
        let currentHotels = [];
        let currentFlights = [];
        let currentNights = 0;
        let selectedHotel = null;
        let selectedFlight = null;

        // Initialize Flatpickr
        flatpickr("#startDate", {
            minDate: "today",
            dateFormat: "Y-m-d",
            onChange: function(selectedDates, dateStr, instance) {
                flatpickr("#endDate", {
                    minDate: dateStr,
                    dateFormat: "Y-m-d"
                });
            }
        });

        flatpickr("#endDate", {
            minDate: "today",
            dateFormat: "Y-m-d"
        });

        // Update slider values
        document.getElementById('hotelBudget').addEventListener('input', function(e) {
            document.getElementById('hotelBudgetValue').textContent = '₹' + Number(e.target.value).toLocaleString();
        });

        document.getElementById('flightBudget').addEventListener('input', function(e) {
            document.getElementById('flightBudgetValue').textContent = '₹' + Number(e.target.value).toLocaleString();
        });

        // Tab switching
        function switchTab(tab) {
            const hotelsTab = document.getElementById('hotelsTab');
            const flightsTab = document.getElementById('flightsTab');
            const hotelsResults = document.getElementById('hotelsResults');
            const flightsResults = document.getElementById('flightsResults');

            if (tab === 'hotels') {
                hotelsTab.classList.add('active');
                flightsTab.classList.remove('active');
                hotelsResults.classList.remove('hidden');
                flightsResults.classList.add('hidden');
            } else {
                flightsTab.classList.add('active');
                hotelsTab.classList.remove('active');
                flightsResults.classList.remove('hidden');
                hotelsResults.classList.add('hidden');
            }
        }

        // Select hotel
        function selectHotel(hotelId) {
            const hotel = currentHotels.find(h => h.id == hotelId);
            if (!hotel) return;

            document.querySelectorAll('#hotelsResults .result-card').forEach(card => {
                card.classList.remove('selected');
            });

            const selectedCard = document.querySelector(`#hotelsResults .result-card[data-hotel-id="${hotelId}"]`);
            if (selectedCard) {
                selectedCard.classList.add('selected');
            }

            selectedHotel = hotel;
            updateSelectedItems();
            updateTotalBudget();
        }

        // Select flight
        function selectFlight(flightId) {
            const flight = currentFlights.find(f => f.id == flightId);
            if (!flight) return;

            document.querySelectorAll('#flightsResults .result-card').forEach(card => {
                card.classList.remove('selected');
            });

            const selectedCard = document.querySelector(`#flightsResults .result-card[data-flight-id="${flightId}"]`);
            if (selectedCard) {
                selectedCard.classList.add('selected');
            }

            selectedFlight = flight;
            updateSelectedItems();
            updateTotalBudget();
        }

        // Update selected items summary
        function updateSelectedItems() {
            const summaryDiv = document.getElementById('selectedItemsSummary');
            const selectedHotelName = document.getElementById('selectedHotelName');
            const selectedFlightName = document.getElementById('selectedFlightName');

            if (selectedHotel || selectedFlight) {
                summaryDiv.style.display = 'block';

                if (selectedHotel) {
                    selectedHotelName.textContent = `${selectedHotel.hotel_name} - ₹${Number(selectedHotel.price_per_night).toLocaleString()}/night`;
                } else {
                    selectedHotelName.textContent = 'None selected';
                }

                if (selectedFlight) {
                    selectedFlightName.textContent = `${selectedFlight.airline} (${selectedFlight.departure_city}) - ₹${Number(selectedFlight.price_per_person).toLocaleString()}`;
                } else {
                    selectedFlightName.textContent = 'None selected';
                }
            } else {
                summaryDiv.style.display = 'none';
            }
        }

        // Update total budget
        function updateTotalBudget() {
            const travelers = parseInt(document.getElementById('travelers').value) || 1;
            const totalBudgetEl = document.getElementById('totalBudget');

            let hotelTotal = 0;
            let flightTotal = 0;

            if (selectedHotel) {
                hotelTotal = selectedHotel.price_per_night * currentNights * travelers;
            }

            if (selectedFlight) {
                flightTotal = selectedFlight.price_per_person * travelers;
            }

            const total = hotelTotal + flightTotal;
            totalBudgetEl.textContent = '₹' + total.toLocaleString();
        }

        // Check if user is logged in
        function isUserLoggedIn() {
            return document.body.classList.contains('user-logged-in') ||
                sessionStorage.getItem('user_id') ||
                sessionStorage.getItem('userid') ||
                localStorage.getItem('tripmate_active_user_id');
        }

        // Toast notification function
        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;

            const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
            toast.innerHTML = `
                <i class="fas ${icon}" style="color: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#f59e0b'};"></i>
                <span>${message}</span>
            `;

            container.appendChild(toast);

            setTimeout(() => toast.style.transform = 'translateX(0)', 10);

            setTimeout(() => {
                toast.style.transform = 'translateX(400px)';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Escape HTML helper
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Main planning function
        async function planTrip() {
            if (!isUserLoggedIn()) {
                showToast('Please log in to plan a trip', 'warning');
                setTimeout(() => {
                    window.location.href = '../auth/login.php';
                }, 2000);
                return;
            }

            const departureCity = document.getElementById('departureCity').value;
            if (!departureCity) {
                showToast('Please select your departure city', 'warning');
                return;
            }

            document.getElementById('loadingSpinner').classList.remove('hidden');
            document.getElementById('resultsSection').classList.add('hidden');

            selectedHotel = null;
            selectedFlight = null;
            document.getElementById('selectedItemsSummary').style.display = 'none';

            const formData = {
                destination_id: document.getElementById('destination').value,
                departure_city: departureCity,
                hotel_budget: parseFloat(document.getElementById('hotelBudget').value),
                flight_budget: parseFloat(document.getElementById('flightBudget').value),
                start_date: document.getElementById('startDate').value,
                end_date: document.getElementById('endDate').value,
                travelers: parseInt(document.getElementById('travelers').value) || 1,
                free_cancellation: document.getElementById('freeCancellation').checked,
                breakfast_included: document.getElementById('breakfastIncluded').checked,
                refundable_flights: document.getElementById('refundableFlights').checked
            };

            if (!formData.destination_id || !formData.start_date || !formData.end_date) {
                showToast('Please fill in all required fields', 'warning');
                document.getElementById('loadingSpinner').classList.add('hidden');
                return;
            }

            const start = new Date(formData.start_date);
            const end = new Date(formData.end_date);
            currentNights = Math.ceil((end - start) / (1000 * 60 * 60 * 24));

            if (currentNights <= 0) {
                showToast('End date must be after start date', 'warning');
                document.getElementById('loadingSpinner').classList.add('hidden');
                return;
            }

            try {
                const response = await fetch('../actions/plan_trip.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });

                const raw = await response.text();
                let data = null;
                try { data = raw ? JSON.parse(raw) : null; } catch (e) {
                    console.error('Non-JSON response from plan_trip.php:', raw);
                    showToast(raw || 'Server returned an unexpected response. Please try again.', 'error');
                    document.getElementById('loadingSpinner').classList.add('hidden');
                    return;
                }

                if (!response.ok) {
                    showToast((data && data.message) ? data.message : 'Network response was not ok', 'error');
                    document.getElementById('loadingSpinner').classList.add('hidden');
                    return;
                }

                if (data && data.status === 'success') {
                    currentHotels = data.hotels || [];
                    currentFlights = data.flights || [];
                    displayResults(data, currentNights);
                    showToast('Trip options loaded successfully!', 'success');
                } else {
                    showToast(data.message || 'Error planning trip', 'error');

                    if (data.message && data.message.includes('login')) {
                        setTimeout(() => {
                            window.location.href = '../auth/login.php';
                        }, 2000);
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error. Please try again.', 'error');
            } finally {
                document.getElementById('loadingSpinner').classList.add('hidden');
            }
        }

        // Display results
        function displayResults(data, nights) {
            const hotelsResults = document.getElementById('hotelsResults');
            const flightsResults = document.getElementById('flightsResults');
            const hotelCount = document.getElementById('hotelCount');
            const flightCount = document.getElementById('flightCount');
            const tripSummaryText = document.getElementById('tripSummaryText');

            hotelCount.textContent = `(${(data.hotels && data.hotels.length) || 0})`;
            flightCount.textContent = `(${(data.flights && data.flights.length) || 0})`;

            const destSelect = document.getElementById('destination');
            const destName = destSelect.options[destSelect.selectedIndex].text.split(' - ')[0];
            const departureCity = document.getElementById('departureCity').value;
            const travelers = document.getElementById('travelers').value;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            tripSummaryText.innerHTML = `
                <i class="fas fa-map-marker-alt mr-2" style="color: var(--secondary);"></i>${destName}<br>
                <i class="fas fa-plane-departure mr-2" style="color: var(--secondary);"></i>From: ${departureCity}<br>
                <i class="fas fa-calendar mr-2" style="color: var(--secondary);"></i>${nights} ${nights === 1 ? 'night' : 'nights'} (${startDate} to ${endDate})<br>
                <i class="fas fa-users mr-2" style="color: var(--secondary);"></i>${travelers} ${travelers == 1 ? 'traveler' : 'travelers'}
            `;

            if (data.hotels && data.hotels.length > 0) {
                hotelsResults.innerHTML = data.hotels.map(hotel => createHotelCard(hotel, nights)).join('');
            } else {
                hotelsResults.innerHTML = '<div class="col-span-full text-center py-8" style="background: var(--bg-surface); border-radius: 24px;"><p style="color: var(--text-muted);">No hotels found matching your criteria</p></div>';
            }

            if (data.flights && data.flights.length > 0) {
                flightsResults.innerHTML = data.flights.map(flight => createFlightCard(flight)).join('');
            } else {
                flightsResults.innerHTML = '<div class="col-span-full text-center py-8" style="background: var(--bg-surface); border-radius: 24px;"><p style="color: var(--text-muted);">No flights found matching your criteria</p></div>';
            }

            document.getElementById('totalBudget').textContent = '₹0';
            document.getElementById('resultsSection').classList.remove('hidden');
        }

        // Create hotel card
        function createHotelCard(hotel, nights) {
            const totalPrice = hotel.price_per_night * nights;
            const badgeClass = hotel.hotel_type === 'low' ? 'badge-low' : (hotel.hotel_type === 'medium' ? 'badge-medium' : 'badge-high');
            const badgeText = hotel.hotel_type.charAt(0).toUpperCase() + hotel.hotel_type.slice(1) + ' Budget';
            const isSelected = selectedHotel && selectedHotel.id == hotel.id;

            return `
                <div class="result-card ${isSelected ? 'selected' : ''}" data-hotel-id="${hotel.id}">
                    <div class="hotel-image" style="background-image: url('${hotel.image_url || '../image/hotel-placeholder.jpg'}')">
                        <span class="hotel-badge ${badgeClass}">${badgeText}</span>
                    </div>
                    <div class="p-5 flex-grow">
                        <h3 class="font-bold text-lg mb-1" style="color: var(--text-main);">${escapeHtml(hotel.hotel_name)}</h3>
                        <div class="flex items-center text-sm mb-2" style="color: var(--text-muted);">
                            <i class="fas fa-star" style="color: #f59e0b; mr-1"></i>
                            <span>${hotel.hotel_rating} / 5</span>
                        </div>
                        <p class="text-sm mb-3 line-clamp-2" style="color: var(--text-muted);">${escapeHtml(hotel.description || 'No description available')}</p>
                        <div class="space-y-2 mb-3">
                            <div class="flex items-center text-sm">
                                <i class="fas fa-wifi" style="color: var(--secondary); width: 20px;"></i>
                                <span style="color: var(--text-main);">Free WiFi</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <i class="fas fa-utensils" style="color: var(--secondary); width: 20px;"></i>
                                <span style="color: var(--text-main);">${hotel.breakfast_included ? 'Breakfast Included' : 'Breakfast not included'}</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <i class="fas fa-clock" style="color: var(--secondary); width: 20px;"></i>
                                <span style="color: var(--text-main);">Check-in: ${hotel.check_in_time || '12:00'}</span>
                            </div>
                        </div>
                        <div class="border-t pt-4" style="border-color: var(--card-border);">
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <span class="price-tag">₹${Number(hotel.price_per_night).toLocaleString()}</span>
                                    <span class="price-label">/night</span>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold" style="color: var(--secondary);">Total: ₹${totalPrice.toLocaleString()}</div>
                                    <div class="text-sm" style="color: var(--text-muted);">for ${nights} nights</div>
                                </div>
                            </div>
                            <div class="button-container">
                                <button onclick="selectHotel(${hotel.id})" class="select-btn ${isSelected ? 'selected' : ''}">
                                    ${isSelected ? '✓ SELECTED' : 'SELECT THIS HOTEL'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Create flight card
        function createFlightCard(flight) {
            const badgeClass = flight.flight_type === 'low' ? 'badge-low' : (flight.flight_type === 'medium' ? 'badge-medium' : 'badge-high');
            const badgeText = flight.flight_type.charAt(0).toUpperCase() + flight.flight_type.slice(1) + ' Budget';
            const isSelected = selectedFlight && selectedFlight.id == flight.id;

            return `
                <div class="result-card ${isSelected ? 'selected' : ''}" data-flight-id="${flight.id}">
                    <div class="flight-image" style="background-image: url('../image/flight-bg.jpg')">
                        <span class="flight-badge ${badgeClass}">${badgeText}</span>
                    </div>
                    <div class="p-5 flex-grow">
                        <h3 class="font-bold text-lg mb-1" style="color: var(--text-main);">${escapeHtml(flight.airline)}</h3>
                        <p class="text-sm mb-2" style="color: var(--text-muted);">
                            <i class="fas fa-map-marker-alt" style="color: var(--secondary); mr-1"></i>
                            ${flight.departure_city} → Destination
                        </p>
                        <div class="grid grid-cols-2 gap-2 mb-3">
                            <div class="text-sm">
                                <span style="color: var(--text-muted);">Departure</span><br>
                                <span class="font-semibold" style="color: var(--text-main);">${flight.departure_time || 'N/A'}</span>
                            </div>
                            <div class="text-sm">
                                <span style="color: var(--text-muted);">Arrival</span><br>
                                <span class="font-semibold" style="color: var(--text-main);">${flight.arrival_time || 'N/A'}</span>
                            </div>
                            <div class="text-sm">
                                <span style="color: var(--text-muted);">Duration</span><br>
                                <span class="font-semibold" style="color: var(--text-main);">${flight.duration_hours || 'N/A'} hrs</span>
                            </div>
                            <div class="text-sm">
                                <span style="color: var(--text-muted);">Stops</span><br>
                                <span class="font-semibold" style="color: var(--text-main);">${flight.stops || '0'}</span>
                            </div>
                        </div>
                        <div class="space-y-1 mb-3">
                            <div class="flex items-center text-sm">
                                <i class="fas fa-briefcase" style="color: var(--secondary); width: 20px;"></i>
                                <span style="color: var(--text-main);">${flight.baggage_allowance || '15kg'}</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <i class="fas fa-utensils" style="color: var(--secondary); width: 20px;"></i>
                                <span style="color: var(--text-main);">${flight.meal_included ? 'Meal Included' : 'Meal not included'}</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <i class="fas fa-exchange-alt" style="color: var(--secondary); width: 20px;"></i>
                                <span style="color: var(--text-main);">${flight.refundable ? 'Refundable' : 'Non-refundable'}</span>
                            </div>
                        </div>
                        <div class="border-t pt-4" style="border-color: var(--card-border);">
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <span class="price-tag">₹${Number(flight.price_per_person).toLocaleString()}</span>
                                    <span class="price-label">/person</span>
                                </div>
                                <div class="text-xs" style="color: var(--text-muted);">
                                    ${flight.flight_class || 'Economy'} Class
                                </div>
                            </div>
                            <div class="button-container">
                                <button onclick="selectFlight(${flight.id})" class="select-btn ${isSelected ? 'selected' : ''}">
                                    ${isSelected ? '✓ SELECTED' : 'SELECT THIS FLIGHT'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Logout function
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                fetch('../auth/logout.php')
                    .then(() => {
                        sessionStorage.clear();
                        localStorage.removeItem('tripmate_active_user_id');
                        localStorage.removeItem('tripmate_active_user_name');
                        window.location.href = '../main/index.html';
                    })
                    .catch(error => {
                        console.error('Logout error:', error);
                        window.location.href = '../main/index.html';
                    });
            }
        }

        // Initialize on DOM load
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($user_id) && $user_id): ?>
                sessionStorage.setItem('user_id', '<?php echo $user_id; ?>');
                sessionStorage.setItem('user_name', '<?php echo htmlspecialchars($userName); ?>');
                sessionStorage.setItem('userid', '<?php echo $user_id; ?>');
                sessionStorage.setItem('username', '<?php echo htmlspecialchars($userName); ?>');

                localStorage.setItem('tripmate_active_user_id', '<?php echo $user_id; ?>');
                localStorage.setItem('tripmate_active_user_name', '<?php echo htmlspecialchars($userName); ?>');

                document.body.classList.add('user-logged-in');
            <?php else: ?>
                const storedUserId = localStorage.getItem('tripmate_active_user_id');
                const storedUserName = localStorage.getItem('tripmate_active_user_name');

                if (storedUserId && storedUserName) {
                    document.getElementById('sessionRestoreMsg').style.display = 'flex';

                    fetch('../auth/restore_session.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                user_id: storedUserId,
                                user_name: storedUserName
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                window.location.reload();
                            } else {
                                document.getElementById('sessionRestoreMsg').style.display = 'none';
                                showToast('Please log in to continue', 'warning');
                            }
                        })
                        .catch(() => {
                            document.getElementById('sessionRestoreMsg').style.display = 'none';
                        });
                }
            <?php endif; ?>
        });
    </script>
</body>

</html>