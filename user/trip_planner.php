<?php
// Start session at the very beginning
session_start();

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../database/dbconfig.php';

// Get user ID and name from session if available
$user_id = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User';
$user_email = '';
$user_level = 'normal';
$user_profile_pic = '../image/default-avatar.png';

// Get additional user details from database if user is logged in
if ($user_id) {
    $user_query = "SELECT name, email, profile_pic, user_level FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_query);
    if ($user_stmt) {
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
    }
}

// Fetch all destinations for dropdown
$destinations = [];
$dest_query = "SELECT id, name, location, type, image_urls, budget, best_season FROM destinations ORDER BY name";
$dest_result = $conn->query($dest_query);
if ($dest_result) {
    while ($row = $dest_result->fetch_assoc()) {
        $destinations[] = $row;
    }
}

// Fetch all departure cities from flights for location dropdown
$locations = [];
$loc_query = "SELECT DISTINCT departure_city FROM flights WHERE departure_city IS NOT NULL AND departure_city != '' ORDER BY departure_city";
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
    <title>TripMate - Plan Your Perfect Trip</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts Inter & Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Flatpickr for date picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <style>
        /* ========================================================
           TRIPMATE PREMIUM THEME - NAVY & TEAL
           ======================================================== */

        /* ----- CSS Variables for Light/Dark Theme ----- */
        :root {
            /* Light theme (default) */
            --primary: #1A2E40;      /* Navy */
            --secondary: #30BCED;     /* Teal */
            --accent: #F59E0B;        /* Amber accent */
            --danger: #EF4444;        /* Red for errors */
            --success: #10B981;       /* Green for success */
            --warning: #F59E0B;       /* Amber for warnings */
            
            /* Background & surfaces */
            --bg-body: #F5F7FA;       /* Soft off-white background */
            --bg-card: #FFFFFF;       /* White cards */
            --bg-base: #F9FAFC;       /* Base background */
            --bg-hover: #F0F3F8;      /* Hover state */
            --bg-surface: rgba(255, 255, 255, 0.95); /* Glass effect */
            
            /* Text colors */
            --text-main: #1E2A3A;     /* Dark text */
            --text-muted: #64748B;    /* Muted text */
            --text-light: #94A3B8;    /* Light text */
            
            /* Borders & shadows */
            --card-border: #E2E8F0;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.02), 0 1px 2px rgba(0,0,0,0.03);
            --shadow-md: 0 4px 20px -2px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            --shadow-lg: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
            --shadow-hover: 0 20px 30px -10px rgba(0,0,0,0.15), 0 8px 15px -6px rgba(0,0,0,0.1);
            
            /* Spacing */
            --space-xs: 0.25rem;
            --space-sm: 0.5rem;
            --space-md: 1rem;
            --space-lg: 1.5rem;
            --space-xl: 2rem;
            --space-2xl: 3rem;
            
            /* Border radius */
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
            --radius-full: 9999px;
            
            /* Transitions */
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-base: 250ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 350ms cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Z-index layers */
            --z-nav: 100;
            --z-modal: 200;
            --z-toast: 300;
            --z-progress: 400;
            
            /* Gradients */
            --gradient-primary: linear-gradient(135deg, var(--primary), #2C3E50);
            --gradient-accent: linear-gradient(135deg, var(--secondary), #4DC9FF);
            --gradient-mixed: linear-gradient(135deg, var(--primary), var(--secondary));
        }

        /* Dark theme variables */
        .dark-mode {
            --primary: #2C3E50;       /* Lighter navy for dark mode */
            --secondary: #4DC9FF;     /* Brighter teal */
            
            /* Background & surfaces */
            --bg-body: #0F172A;       /* Dark navy background */
            --bg-card: #1E293B;       /* Dark blue-gray cards */
            --bg-base: #0F172A;       /* Dark background */
            --bg-hover: #2D3A4F;      /* Hover state */
            --bg-surface: rgba(30, 41, 59, 0.95); /* Dark glass */
            
            /* Text colors */
            --text-main: #F1F5F9;     /* Light text */
            --text-muted: #94A3B8;    /* Muted text for dark mode */
            --text-light: #64748B;    /* Even lighter muted */
            
            /* Borders & shadows */
            --card-border: #334155;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.3);
            --shadow-md: 0 4px 20px -2px rgba(0,0,0,0.5);
            --shadow-lg: 0 20px 25px -5px rgba(0,0,0,0.5);
            --shadow-hover: 0 20px 30px -10px rgba(0,0,0,0.6);
        }

        /* ===== Base Styles ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            background-image: 
                radial-gradient(circle at top right, rgba(48, 188, 237, 0.05), transparent 40%),
                radial-gradient(circle at bottom left, rgba(26, 46, 64, 0.05), transparent 40%);
            color: var(--text-main);
            transition: background-color var(--transition-base), color var(--transition-base);
            line-height: 1.6;
            min-height: 100vh;
            padding-top: 100px;
        }

        /* Headings with Poppins */
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text-main);
        }

        p {
            color: var(--text-muted);
            line-height: 1.7;
        }

        /* ===== Scroll Progress Bar ===== */
        .scroll-progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 4px;
            width: 0%;
            background: var(--gradient-mixed);
            z-index: var(--z-progress);
            transition: width var(--transition-fast);
            box-shadow: 0 0 10px var(--secondary);
        }

        /* ===== Floating Navigation Bar ===== */
        .navbar {
            position: fixed;
            top: 20px;
            left: 5%;
            width: 90%;
            height: 80px;
            background: var(--bg-surface);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-full);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
            z-index: var(--z-nav);
            box-shadow: var(--shadow-md);
            transition: all var(--transition-base);
            animation: slideDown 0.6s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .navbar:hover {
            box-shadow: var(--shadow-lg);
            border-color: var(--secondary);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            font-size: 1.5rem;
            font-weight: 800;
            text-decoration: none;
            color: var(--text-main);
        }

        .logo i {
            color: var(--secondary);
            font-size: 1.8rem;
            transform: rotate(-15deg);
            transition: transform var(--transition-base);
        }

        .logo:hover i {
            transform: rotate(0deg) scale(1.1);
        }

        .brand-text {
            font-family: 'Poppins', sans-serif;
        }

        .trip {
            color: var(--primary);
        }

        .mate {
            color: var(--secondary);
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: var(--space-lg);
        }

        .nav-link {
            color: var(--text-main);
            text-decoration: none;
            font-weight: 600;
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-full);
            transition: all var(--transition-fast);
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        .nav-link i {
            color: var(--secondary);
            font-size: 1.1rem;
            transition: transform var(--transition-fast);
        }

        .nav-link:hover {
            background: var(--bg-hover);
            color: var(--secondary);
            transform: translateY(-2px);
        }

        .nav-link:hover i {
            transform: translateY(-2px);
        }

        /* ===== Theme Toggle Button ===== */
        .theme-toggle {
            background: var(--bg-hover);
            border: none;
            width: 44px;
            height: 44px;
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-main);
            transition: all var(--transition-base);
            position: relative;
            overflow: hidden;
        }

        .theme-toggle i {
            font-size: 1.2rem;
            transition: transform var(--transition-base);
        }

        .theme-toggle:hover {
            background: var(--secondary);
            color: white;
            transform: rotate(15deg);
        }

        .theme-toggle:hover i {
            transform: scale(1.1);
        }

        /* ===== Profile Menu ===== */
        .profile-menu {
            position: relative;
        }

        .profile-btn {
            background: var(--bg-hover);
            border: 1px solid var(--card-border);
            color: var(--text-main);
            padding: var(--space-sm) var(--space-lg);
            border-radius: var(--radius-full);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            font-weight: 600;
            transition: all var(--transition-base);
        }

        .profile-btn:hover {
            border-color: var(--secondary);
            color: var(--secondary);
            transform: translateY(-2px);
        }

        .profile-btn img {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--secondary);
        }

        .profile-dropdown {
            position: absolute;
            top: calc(100% + var(--space-md));
            right: 0;
            width: 260px;
            background: var(--bg-surface);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-lg);
            padding: var(--space-md);
            box-shadow: var(--shadow-lg);
            backdrop-filter: blur(20px);
            display: none;
            z-index: calc(var(--z-nav) + 1);
            animation: slideDown 0.3s ease;
        }

        .profile-dropdown.active {
            display: block;
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

        .profile-header {
            background: var(--gradient-mixed);
            border-radius: var(--radius-md);
            padding: var(--space-md);
            margin-bottom: var(--space-md);
            color: white;
        }

        .profile-header .font-bold {
            font-size: 1.1rem;
            margin-bottom: var(--space-xs);
        }

        .profile-header .text-xs {
            opacity: 0.9;
        }

        .premium-badge {
            display: inline-block;
            margin-top: var(--space-xs);
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: var(--radius-full);
            font-size: 0.7rem;
            font-weight: 600;
        }

        .profile-dropdown a,
        .profile-dropdown button {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            padding: var(--space-md);
            color: var(--text-main);
            text-decoration: none;
            font-weight: 500;
            border-radius: var(--radius-md);
            transition: all var(--transition-fast);
            width: 100%;
            text-align: left;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .profile-dropdown a:hover,
        .profile-dropdown button:hover {
            background: var(--bg-hover);
            color: var(--secondary);
            padding-left: var(--space-lg);
        }

        .profile-dropdown a i,
        .profile-dropdown button i {
            width: 20px;
            color: var(--secondary);
            font-size: 1rem;
        }

        .profile-dropdown hr {
            margin: var(--space-sm) 0;
            border: none;
            border-top: 1px solid var(--card-border);
        }

        /* ===== Main Container ===== */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 var(--space-xl);
        }

        /* ===== Hero Section ===== */
        .hero-section {
            text-align: center;
            margin-bottom: var(--space-2xl);
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: var(--space-md);
            line-height: 1.2;
        }

        .hero-title span {
            color: var(--secondary);
            position: relative;
            display: inline-block;
        }

        .hero-title span::after {
            content: '';
            position: absolute;
            bottom: 5px;
            left: 0;
            width: 100%;
            height: 8px;
            background: var(--secondary);
            opacity: 0.2;
            border-radius: var(--radius-full);
            z-index: -1;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
            color: var(--text-muted);
        }

        /* ===== Glass Card Effect ===== */
        .glass-card {
            background: var(--bg-surface);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-xl);
            padding: var(--space-2xl);
            box-shadow: var(--shadow-md);
            backdrop-filter: blur(20px);
            margin-bottom: var(--space-2xl);
            transition: all var(--transition-base);
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .glass-card:hover {
            box-shadow: var(--shadow-hover);
            border-color: var(--secondary);
        }

        /* ===== Form Elements ===== */
        .form-label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: var(--space-sm);
            color: var(--text-main);
        }

        .form-label i {
            color: var(--secondary);
            margin-right: var(--space-sm);
        }

        .form-input {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid var(--card-border);
            border-radius: var(--radius-md);
            background: var(--bg-base);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            transition: all var(--transition-fast);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--secondary);
            background: var(--bg-card);
            box-shadow: 0 0 0 4px rgba(48, 188, 237, 0.1);
        }

        .form-input::placeholder {
            color: var(--text-light);
        }

        select.form-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.2em;
            padding-right: 2.5rem;
        }

        select.form-input option {
            background: var(--bg-card);
            color: var(--text-main);
            padding: var(--space-md);
        }

        /* ===== Checkbox Styles ===== */
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            padding: var(--space-md);
            background: var(--bg-base);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all var(--transition-fast);
            border: 1px solid transparent;
        }

        .checkbox-wrapper:hover {
            background: var(--bg-hover);
            border-color: var(--secondary);
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: var(--secondary);
            cursor: pointer;
        }

        .checkbox-wrapper span {
            color: var(--text-main);
            font-size: 0.95rem;
            font-weight: 500;
        }

        /* ===== Range Slider ===== */
        .range-slider-container {
            background: var(--bg-base);
            border-radius: var(--radius-lg);
            padding: var(--space-xl);
        }

        .range-slider {
            -webkit-appearance: none;
            width: 100%;
            height: 8px;
            border-radius: var(--radius-full);
            background: linear-gradient(to right, var(--secondary) 0%, var(--secondary) 50%, var(--card-border) 50%, var(--card-border) 100%);
            outline: none;
            margin: var(--space-lg) 0;
        }

        .range-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: white;
            border: 3px solid var(--secondary);
            cursor: pointer;
            transition: all var(--transition-fast);
            box-shadow: 0 2px 10px var(--secondary);
        }

        .range-slider::-webkit-slider-thumb:hover {
            transform: scale(1.2);
            background: var(--secondary);
        }

        .range-values {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .range-value-display {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--secondary);
        }

        /* ===== Submit Button ===== */
        .submit-btn {
            background: var(--gradient-mixed);
            color: white;
            border: none;
            padding: 18px 40px;
            border-radius: var(--radius-full);
            cursor: pointer;
            font-weight: 700;
            font-size: 1.2rem;
            transition: all var(--transition-base);
            box-shadow: 0 10px 20px rgba(48, 188, 237, 0.3);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-md);
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
            min-width: 280px;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .submit-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 30px rgba(48, 188, 237, 0.4);
        }

        .submit-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .submit-btn i {
            transition: transform var(--transition-fast);
        }

        .submit-btn:hover i:last-child {
            transform: translateX(5px);
        }

        /* ===== Summary Card ===== */
        .summary-card {
            background: var(--gradient-mixed);
            border-radius: var(--radius-xl);
            padding: var(--space-2xl);
            color: white;
            box-shadow: var(--shadow-lg);
            margin-bottom: var(--space-2xl);
            position: relative;
            overflow: hidden;
            animation: slideInRight 0.6s ease-out;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .summary-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .summary-card h3,
        .summary-card p {
            color: white;
            position: relative;
            z-index: 1;
        }

        .total-budget {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        /* ===== Selected Items ===== */
        .selected-items {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: var(--radius-lg);
            padding: var(--space-xl);
            margin-top: var(--space-xl);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .selected-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--space-md) 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .selected-item:last-child {
            border-bottom: none;
        }

        .selected-item span:first-child {
            opacity: 0.9;
        }

        .selected-item span:last-child {
            font-weight: 600;
            color: var(--secondary);
        }

        /* ===== Tab Buttons ===== */
        .tab-container {
            display: flex;
            gap: var(--space-sm);
            margin-bottom: var(--space-xl);
            flex-wrap: wrap;
        }

        .tab-button {
            padding: 14px 32px;
            font-weight: 600;
            font-size: 1rem;
            border-radius: var(--radius-full);
            transition: all var(--transition-base);
            cursor: pointer;
            border: 2px solid var(--card-border);
            background: var(--bg-surface);
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            position: relative;
            overflow: hidden;
        }

        .tab-button i {
            color: var(--secondary);
            transition: all var(--transition-fast);
        }

        .tab-button.active {
            background: var(--secondary);
            color: white;
            border-color: var(--secondary);
            box-shadow: 0 10px 20px rgba(48, 188, 237, 0.3);
        }

        .tab-button.active i {
            color: white;
        }

        .tab-button:not(.active):hover {
            border-color: var(--secondary);
            color: var(--secondary);
            transform: translateY(-2px);
        }

        .tab-button:not(.active):hover i {
            color: var(--secondary);
        }

        /* ===== Result Cards ===== */
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: var(--space-xl);
            margin-top: var(--space-xl);
        }

        .result-card {
            background: var(--bg-card);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-xl);
            overflow: hidden;
            transition: all var(--transition-base);
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
            animation: scaleIn 0.5s ease-out;
            box-shadow: var(--shadow-sm);
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .result-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-hover);
            border-color: var(--secondary);
        }

        .result-card.selected {
            border: 2px solid var(--secondary);
            box-shadow: 0 0 0 4px rgba(48, 188, 237, 0.2);
        }

        .card-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
            transition: transform var(--transition-base);
        }

        .result-card:hover .card-image {
            transform: scale(1.05);
        }

        .card-badge {
            position: absolute;
            top: var(--space-md);
            right: var(--space-md);
            padding: 6px 12px;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            z-index: 2;
        }

        .badge-low {
            background: var(--success);
            color: white;
        }

        .badge-medium {
            background: var(--warning);
            color: white;
        }

        .badge-high {
            background: var(--danger);
            color: white;
        }

        .card-content {
            padding: var(--space-xl);
            flex-grow: 1;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: var(--space-xs);
            color: var(--text-main);
        }

        .card-rating {
            display: flex;
            align-items: center;
            gap: 2px;
            margin-bottom: var(--space-sm);
        }

        .card-rating i {
            color: #f59e0b;
            font-size: 0.9rem;
        }

        .card-rating span {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-left: var(--space-xs);
        }

        .card-description {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: var(--space-md);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-amenities {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-sm);
            margin-bottom: var(--space-lg);
        }

        .amenity-item {
            display: flex;
            align-items: center;
            gap: var(--space-xs);
            font-size: 0.85rem;
            color: var(--text-muted);
            background: var(--bg-base);
            padding: 4px 10px;
            border-radius: var(--radius-full);
        }

        .amenity-item i {
            color: var(--secondary);
            font-size: 0.8rem;
        }

        .price-section {
            border-top: 1px solid var(--card-border);
            padding-top: var(--space-lg);
            margin-top: auto;
        }

        .price-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: var(--space-md);
        }

        .price-tag {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--secondary);
            line-height: 1.2;
        }

        .price-label {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .total-price {
            text-align: right;
        }

        .total-price .amount {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--secondary);
        }

        .total-price .period {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* ===== Select Button ===== */
        .select-btn {
            background: var(--gradient-mixed);
            color: white;
            border: none;
            border-radius: var(--radius-full);
            padding: 14px 24px;
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all var(--transition-base);
            width: 100%;
            text-transform: uppercase;
            position: relative;
            overflow: hidden;
        }

        .select-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .select-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(48, 188, 237, 0.3);
        }

        .select-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .select-btn.selected {
            background: var(--success);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }

        .select-btn:disabled {
            background: var(--text-muted);
            cursor: not-allowed;
            opacity: 0.6;
            transform: none;
            box-shadow: none;
        }

        /* ===== Flight Card Specific ===== */
        .flight-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--space-md);
            margin-bottom: var(--space-md);
        }

        .flight-info-item {
            text-align: center;
            padding: var(--space-sm);
            background: var(--bg-base);
            border-radius: var(--radius-md);
        }

        .flight-info-item .label {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .flight-info-item .value {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-main);
            margin-top: 2px;
        }

        /* ===== Loading Spinner ===== */
        .loading-spinner {
            text-align: center;
            padding: var(--space-2xl) 0;
        }

        .spinner {
            border: 4px solid var(--card-border);
            border-top: 4px solid var(--secondary);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
            margin: 0 auto var(--space-lg);
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* ===== Toast Notifications ===== */
        .toast-container {
            position: fixed;
            top: 30px;
            right: 30px;
            z-index: var(--z-toast);
        }

        .toast {
            background: var(--bg-surface);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-lg);
            padding: 16px 24px;
            margin-top: var(--space-sm);
            box-shadow: var(--shadow-lg);
            border-left: 6px solid;
            color: var(--text-main);
            font-weight: 600;
            transform: translateX(400px);
            transition: transform var(--transition-base);
            border: 1px solid var(--card-border);
            min-width: 320px;
            display: flex;
            align-items: center;
            gap: var(--space-md);
            animation: slideInRight 0.3s ease;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast i {
            font-size: 1.3rem;
        }

        .toast-success {
            border-left-color: var(--success);
        }

        .toast-success i {
            color: var(--success);
        }

        .toast-error {
            border-left-color: var(--danger);
        }

        .toast-error i {
            color: var(--danger);
        }

        .toast-warning {
            border-left-color: var(--warning);
        }

        .toast-warning i {
            color: var(--warning);
        }

        .toast-info {
            border-left-color: var(--secondary);
        }

        .toast-info i {
            color: var(--secondary);
        }

        /* ===== Session Restore Message ===== */
        .session-restore {
            position: fixed;
            top: 100px;
            right: 30px;
            background: var(--bg-surface);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-lg);
            padding: 16px 24px;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: var(--space-md);
            z-index: calc(var(--z-toast) + 1);
            animation: slideInRight 0.3s ease;
            border-left: 4px solid var(--secondary);
            border: 1px solid var(--card-border);
        }

        .session-restore i {
            color: var(--secondary);
            animation: spin 1s linear infinite;
        }

        /* ===== Utility Classes ===== */
        .hidden {
            display: none !important;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .flex {
            display: flex;
        }

        .flex-col {
            flex-direction: column;
        }

        .items-center {
            align-items: center;
        }

        .items-start {
            align-items: flex-start;
        }

        .justify-between {
            justify-content: space-between;
        }

        .justify-center {
            justify-content: center;
        }

        .justify-end {
            justify-content: flex-end;
        }

        .gap-1 { gap: var(--space-xs); }
        .gap-2 { gap: var(--space-sm); }
        .gap-3 { gap: var(--space-md); }
        .gap-4 { gap: var(--space-lg); }
        .gap-6 { gap: var(--space-xl); }

        .mt-1 { margin-top: var(--space-xs); }
        .mt-2 { margin-top: var(--space-sm); }
        .mt-3 { margin-top: var(--space-md); }
        .mt-4 { margin-top: var(--space-lg); }
        .mt-6 { margin-top: var(--space-xl); }
        .mt-8 { margin-top: var(--space-2xl); }

        .mb-1 { margin-bottom: var(--space-xs); }
        .mb-2 { margin-bottom: var(--space-sm); }
        .mb-3 { margin-bottom: var(--space-md); }
        .mb-4 { margin-bottom: var(--space-lg); }
        .mb-6 { margin-bottom: var(--space-xl); }
        .mb-8 { margin-bottom: var(--space-2xl); }

        .ml-1 { margin-left: var(--space-xs); }
        .ml-2 { margin-left: var(--space-sm); }
        .mr-1 { margin-right: var(--space-xs); }
        .mr-2 { margin-right: var(--space-sm); }

        .p-3 { padding: var(--space-md); }
        .p-4 { padding: var(--space-lg); }
        .p-5 { padding: var(--space-xl); }

        .text-xs { font-size: 0.75rem; }
        .text-sm { font-size: 0.875rem; }
        .text-base { font-size: 1rem; }
        .text-lg { font-size: 1.125rem; }
        .text-xl { font-size: 1.25rem; }
        .text-2xl { font-size: 1.5rem; }
        .text-3xl { font-size: 1.875rem; }
        .text-4xl { font-size: 2.25rem; }
        .text-5xl { font-size: 3rem; }

        .font-normal { font-weight: 400; }
        .font-medium { font-weight: 500; }
        .font-semibold { font-weight: 600; }
        .font-bold { font-weight: 700; }
        .font-extrabold { font-weight: 800; }

        .text-white { color: white; }
        .text-white\/80 { color: rgba(255, 255, 255, 0.8); }

        .w-full { width: 100%; }
        .max-w-2xl { max-width: 42rem; }
        .mx-auto { margin-left: auto; margin-right: auto; }

        .relative { position: relative; }
        .absolute { position: absolute; }
        .inset-y-0 { top: 0; bottom: 0; }
        .right-0 { right: 0; }
        .left-4 { left: 1rem; }
        .top-1\/2 { top: 50%; }
        .-translate-y-1\/2 { transform: translateY(-50%); }
        .pr-3 { padding-right: 0.75rem; }
        .pointer-events-none { pointer-events: none; }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .border-t {
            border-top: 1px solid var(--card-border);
        }

        .pt-4 {
            padding-top: var(--space-lg);
        }

        .pb-2 {
            padding-bottom: var(--space-sm);
        }

        .opacity-80 {
            opacity: 0.8;
        }

        /* ===== Responsive Design ===== */
        @media (max-width: 1024px) {
            .navbar {
                width: 95%;
                left: 2.5%;
                padding: 0 20px;
            }

            .nav-link span:not(.fa) {
                display: none;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .results-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            body {
                padding-top: 80px;
            }

            .navbar {
                height: 70px;
                padding: 0 15px;
            }

            .logo .brand-text {
                display: none;
            }

            .logo i {
                font-size: 1.5rem;
            }

            .nav-right {
                gap: var(--space-sm);
            }

            .nav-link {
                padding: var(--space-xs);
            }

            .nav-link i {
                font-size: 1.2rem;
            }

            .profile-btn span:not(.fa) {
                display: none;
            }

            .profile-btn i:last-child {
                display: none;
            }

            .hero-title {
                font-size: 2rem;
            }

            .hero-subtitle {
                font-size: 1rem;
                padding: 0 var(--space-md);
            }

            .glass-card {
                padding: var(--space-xl);
            }

            .container {
                padding: 0 var(--space-md);
            }

            .results-grid {
                grid-template-columns: 1fr;
            }

            .submit-btn {
                width: 100%;
                min-width: auto;
                padding: 16px 20px;
                font-size: 1rem;
            }

            .tab-container {
                justify-content: center;
            }

            .tab-button {
                padding: 12px 24px;
                font-size: 0.9rem;
            }

            .toast-container {
                left: 20px;
                right: 20px;
            }

            .toast {
                min-width: auto;
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .hero-title {
                font-size: 1.75rem;
            }

            .glass-card {
                padding: var(--space-lg);
            }

            .range-slider-container {
                padding: var(--space-lg);
            }

            .checkbox-wrapper {
                padding: var(--space-sm);
            }

            .checkbox-wrapper span {
                font-size: 0.85rem;
            }

            .tab-button {
                padding: 10px 16px;
                font-size: 0.85rem;
            }

            .card-content {
                padding: var(--space-lg);
            }

            .price-tag {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>

    <div class="scroll-progress-bar" id="scrollBar"></div>

    <!-- Session restore notification -->
    <div id="sessionRestoreMsg" class="session-restore" style="display: none;">
        <i class="fas fa-sync-alt fa-spin"></i>
        <span>Restoring your session...</span>
    </div>

    <!-- Navigation -->
    <nav class="navbar" role="navigation" aria-label="Main navigation">
        <a href="../main/index.html" class="logo">
            <i class="fa-solid fa-paper-plane"></i>
            <span class="brand-text"><span class="trip">Trip</span><span class="mate">Mate</span></span>
        </a>

        <div class="nav-right">
            <a href="../main/index.html" class="nav-link"><i class="fas fa-home"></i> <span>Home</span></a>
            <a href="../search/search.html" class="nav-link"><i class="fas fa-search"></i> <span>Search</span></a>
            <a href="user_dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <button class="theme-toggle" id="themeToggle" aria-label="Switch dark/light mode">
                <i class="fas fa-moon"></i>
            </button>

            <!-- Profile Menu -->
            <div class="profile-menu">
                <button class="profile-btn" id="profileBtn">
                    <?php if (!empty($user_profile_pic) && $user_profile_pic != '../image/default-avatar.png'): ?>
                        <img src="<?php echo htmlspecialchars($user_profile_pic); ?>" alt="Profile">
                    <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($userName); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>

                <div class="profile-dropdown" id="userDropdown">
                    <div class="profile-header">
                        <div class="font-bold"><?php echo htmlspecialchars($userName); ?></div>
                        <div class="text-xs"><?php echo htmlspecialchars($user_email); ?></div>
                        <?php if (isset($user_level) && $user_level == 'high'): ?>
                            <span class="premium-badge">✨ Premium Member</span>
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
        <!-- Hero Section -->
        <div class="hero-section">
            <h1 class="hero-title">
                Plan Your <span>Perfect Trip</span>
            </h1>
            <p class="hero-subtitle">
                Customize every aspect of your journey with our intelligent trip planner
            </p>
        </div>

        <!-- Main Form Card -->
        <div class="glass-card">
            <form id="tripPlannerForm" onsubmit="event.preventDefault(); planTrip();">
                <!-- Destination Selection -->
                <div class="mb-6">
                    <label class="form-label">
                        <i class="fas fa-map-marker-alt"></i>Select Destination
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
                    <label class="form-label">
                        <i class="fas fa-plane-departure"></i>Your Departure City
                    </label>
                    <select id="departureCity" name="departure_city" required class="form-input">
                        <option value="">Select your departure city</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo htmlspecialchars($loc); ?>"><?php echo htmlspecialchars($loc); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid gap-6" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); margin-bottom: var(--space-xl);">
                    <!-- Hotel Budget Slider -->
                    <div class="range-slider-container">
                        <label class="form-label">
                            <i class="fas fa-hotel"></i>Hotel Budget (per night)
                        </label>
                        <input type="range" id="hotelBudget" min="0" max="10000" value="5000" step="100" class="range-slider w-full">
                        <div class="range-values">
                            <span>Min: ₹0</span>
                            <span class="range-value-display" id="hotelBudgetValue">₹5,000</span>
                            <span>Max: ₹10,000</span>
                        </div>
                    </div>

                    <!-- Flight Budget Slider -->
                    <div class="range-slider-container">
                        <label class="form-label">
                            <i class="fas fa-plane"></i>Flight Budget (per person)
                        </label>
                        <input type="range" id="flightBudget" min="1000" max="50000" value="25000" step="500" class="range-slider w-full">
                        <div class="range-values">
                            <span>Min: ₹1,000</span>
                            <span class="range-value-display" id="flightBudgetValue">₹25,000</span>
                            <span>Max: ₹50,000</span>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: var(--space-xl);">
                    <!-- Travel Dates -->
                    <div>
                        <label class="form-label">
                            <i class="fas fa-calendar-alt"></i>Start Date
                        </label>
                        <input type="text" id="startDate" class="form-input" placeholder="Select start date" required>
                    </div>
                    <div>
                        <label class="form-label">
                            <i class="fas fa-calendar-check"></i>End Date
                        </label>
                        <input type="text" id="endDate" class="form-input" placeholder="Select end date" required>
                    </div>
                    <div>
                        <label class="form-label">
                            <i class="fas fa-users"></i>Number of Travelers
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
                <div class="grid gap-4" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: var(--space-xl);">
                    <label class="checkbox-wrapper">
                        <input type="checkbox" id="freeCancellation">
                        <span>Free Cancellation</span>
                    </label>
                    <label class="checkbox-wrapper">
                        <input type="checkbox" id="breakfastIncluded">
                        <span>Breakfast Included</span>
                    </label>
                    <label class="checkbox-wrapper">
                        <input type="checkbox" id="refundableFlights">
                        <span>Refundable Flights</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <div class="text-center">
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-magic"></i>
                        <span>Search Options</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- Results Section (Initially Hidden) -->
        <div id="resultsSection" class="hidden">
            <!-- Trip Summary -->
            <div class="summary-card">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h3 class="text-2xl font-bold mb-2">Your Trip Summary</h3>
                        <p class="text-white/80" id="tripSummaryText"></p>
                    </div>
                    <div class="text-right">
                        <div class="total-budget" id="totalBudget">₹0</div>
                        <div class="text-white/80 text-sm">Estimated Total Budget</div>
                    </div>
                </div>

                <!-- Selected Items Summary -->
                <div class="selected-items" id="selectedItemsSummary" style="display: none;">
                    <h4 class="font-semibold mb-3 text-white">Your Selections:</h4>
                    <div id="selectedHotelInfo" class="selected-item">
                        <span>Hotel:</span>
                        <span class="font-semibold" id="selectedHotelName">None selected</span>
                    </div>
                    <div id="selectedFlightInfo" class="selected-item">
                        <span>Flight:</span>
                        <span class="font-semibold" id="selectedFlightName">None selected</span>
                    </div>
                </div>
            </div>

            <!-- Tabs for Hotels and Flights -->
            <div class="tab-container">
                <button onclick="switchTab('hotels')" id="hotelsTab" class="tab-button active">
                    <i class="fas fa-hotel"></i>Hotels <span id="hotelCount">(0)</span>
                </button>
                <button onclick="switchTab('flights')" id="flightsTab" class="tab-button">
                    <i class="fas fa-plane"></i>Flights <span id="flightCount">(0)</span>
                </button>
            </div>

            <!-- Hotels Results -->
            <div id="hotelsResults" class="results-grid">
                <!-- Results will be dynamically inserted here -->
            </div>

            <!-- Flights Results -->
            <div id="flightsResults" class="results-grid hidden">
                <!-- Results will be dynamically inserted here -->
            </div>

            <!-- Loading Spinner -->
            <div id="loadingSpinner" class="loading-spinner hidden">
                <div class="spinner"></div>
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
            onChange: function(selectedDates, dateStr) {
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
                <i class="fas ${icon}"></i>
                <span>${message}</span>
            `;

            container.appendChild(toast);

            setTimeout(() => toast.classList.add('show'), 10);

            setTimeout(() => {
                toast.classList.remove('show');
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

            // Simulate API call with sample data
            setTimeout(() => {
                // Sample hotels data
                const sampleHotels = [
                    {
                        id: 1,
                        hotel_name: 'Grand Luxury Hotel',
                        hotel_type: 'high',
                        price_per_night: 8500,
                        hotel_rating: 4.8,
                        description: 'Experience ultimate luxury with ocean view rooms and premium amenities',
                        image_url: '../image/hotel-luxury.jpg',
                        breakfast_included: true,
                        free_cancellation: true,
                        check_in_time: '14:00',
                        amenities: ['Free WiFi', 'Swimming Pool', 'Spa', 'Restaurant']
                    },
                    {
                        id: 2,
                        hotel_name: 'Comfort Inn',
                        hotel_type: 'medium',
                        price_per_night: 4500,
                        hotel_rating: 4.2,
                        description: 'Modern comfort with excellent location and friendly service',
                        image_url: '../image/hotel-comfort.jpg',
                        breakfast_included: true,
                        free_cancellation: true,
                        check_in_time: '13:00',
                        amenities: ['Free WiFi', 'Restaurant', 'Parking']
                    },
                    {
                        id: 3,
                        hotel_name: 'Budget Stay',
                        hotel_type: 'low',
                        price_per_night: 2200,
                        hotel_rating: 3.9,
                        description: 'Clean, basic accommodation perfect for budget travelers',
                        image_url: '../image/hotel-budget.jpg',
                        breakfast_included: false,
                        free_cancellation: false,
                        check_in_time: '12:00',
                        amenities: ['Free WiFi', 'Shared Kitchen']
                    }
                ];

                // Sample flights data
                const sampleFlights = [
                    {
                        id: 1,
                        airline: 'Emirates',
                        flight_type: 'high',
                        price_per_person: 45000,
                        departure_city: departureCity,
                        departure_time: '10:30 AM',
                        arrival_time: '08:45 PM',
                        duration_hours: 14.5,
                        stops: 1,
                        flight_class: 'Business',
                        baggage_allowance: '30kg',
                        refundable: true,
                        meal_included: true
                    },
                    {
                        id: 2,
                        airline: 'Qatar Airways',
                        flight_type: 'medium',
                        price_per_person: 32000,
                        departure_city: departureCity,
                        departure_time: '11:45 PM',
                        arrival_time: '09:30 AM',
                        duration_hours: 16,
                        stops: 1,
                        flight_class: 'Economy',
                        baggage_allowance: '25kg',
                        refundable: true,
                        meal_included: true
                    },
                    {
                        id: 3,
                        airline: 'Air India',
                        flight_type: 'low',
                        price_per_person: 18500,
                        departure_city: departureCity,
                        departure_time: '06:00 AM',
                        arrival_time: '08:30 PM',
                        duration_hours: 18,
                        stops: 2,
                        flight_class: 'Economy',
                        baggage_allowance: '15kg',
                        refundable: false,
                        meal_included: true
                    }
                ];

                currentHotels = sampleHotels;
                currentFlights = sampleFlights;

                const data = {
                    status: 'success',
                    hotels: sampleHotels,
                    flights: sampleFlights
                };

                displayResults(data, currentNights);
                showToast('Trip options loaded successfully!', 'success');
                document.getElementById('loadingSpinner').classList.add('hidden');
            }, 1500);
        }

        // Display results
        function displayResults(data, nights) {
            const hotelsResults = document.getElementById('hotelsResults');
            const flightsResults = document.getElementById('flightsResults');
            const hotelCount = document.getElementById('hotelCount');
            const flightCount = document.getElementById('flightCount');
            const tripSummaryText = document.getElementById('tripSummaryText');

            hotelCount.textContent = `(${data.hotels?.length || 0})`;
            flightCount.textContent = `(${data.flights?.length || 0})`;

            const destSelect = document.getElementById('destination');
            const destName = destSelect.options[destSelect.selectedIndex]?.text.split(' - ')[0] || 'Selected Destination';
            const departureCity = document.getElementById('departureCity').value;
            const travelers = document.getElementById('travelers').value;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            tripSummaryText.innerHTML = `
                <i class="fas fa-map-marker-alt mr-2"></i>${destName}<br>
                <i class="fas fa-plane-departure mr-2"></i>From: ${departureCity}<br>
                <i class="fas fa-calendar mr-2"></i>${nights} ${nights === 1 ? 'night' : 'nights'} (${startDate} to ${endDate})<br>
                <i class="fas fa-users mr-2"></i>${travelers} ${travelers == 1 ? 'traveler' : 'travelers'}
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
            const amenitiesList = hotel.amenities || ['Free WiFi', 'Restaurant'];

            return `
                <div class="result-card ${isSelected ? 'selected' : ''}" data-hotel-id="${hotel.id}">
                    <div class="card-image" style="background-image: url('${hotel.image_url || '../image/hotel-placeholder.jpg'}')">
                        <span class="card-badge ${badgeClass}">${badgeText}</span>
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">${escapeHtml(hotel.hotel_name)}</h3>
                        <div class="card-rating">
                            ${Array(5).fill(0).map((_, i) => 
                                `<i class="fas fa-star" style="color: ${i < Math.floor(hotel.hotel_rating) ? '#f59e0b' : 'var(--card-border)'};"></i>`
                            ).join('')}
                            <span>${hotel.hotel_rating} / 5</span>
                        </div>
                        <p class="card-description">${escapeHtml(hotel.description || 'No description available')}</p>
                        <div class="card-amenities">
                            ${amenitiesList.slice(0, 3).map(amenity => 
                                `<span class="amenity-item"><i class="fas fa-check"></i> ${amenity}</span>`
                            ).join('')}
                        </div>
                        <div class="price-section">
                            <div class="price-wrapper">
                                <div>
                                    <span class="price-tag">₹${Number(hotel.price_per_night).toLocaleString()}</span>
                                    <span class="price-label">/night</span>
                                </div>
                                <div class="total-price">
                                    <div class="amount">₹${totalPrice.toLocaleString()}</div>
                                    <div class="period">for ${nights} nights</div>
                                </div>
                            </div>
                            <button onclick="selectHotel(${hotel.id})" class="select-btn ${isSelected ? 'selected' : ''}">
                                ${isSelected ? '✓ SELECTED' : 'SELECT THIS HOTEL'}
                            </button>
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
                    <div class="card-image" style="background-image: url('../image/flight-bg.jpg')">
                        <span class="card-badge ${badgeClass}">${badgeText}</span>
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">${escapeHtml(flight.airline)}</h3>
                        <p class="card-description">
                            <i class="fas fa-map-marker-alt" style="color: var(--secondary); margin-right: 4px;"></i>
                            ${flight.departure_city} → Destination
                        </p>
                        <div class="flight-info-grid">
                            <div class="flight-info-item">
                                <div class="label">Departure</div>
                                <div class="value">${flight.departure_time || 'N/A'}</div>
                            </div>
                            <div class="flight-info-item">
                                <div class="label">Arrival</div>
                                <div class="value">${flight.arrival_time || 'N/A'}</div>
                            </div>
                            <div class="flight-info-item">
                                <div class="label">Duration</div>
                                <div class="value">${flight.duration_hours || 'N/A'} hrs</div>
                            </div>
                            <div class="flight-info-item">
                                <div class="label">Stops</div>
                                <div class="value">${flight.stops || '0'}</div>
                            </div>
                        </div>
                        <div class="card-amenities">
                            <span class="amenity-item"><i class="fas fa-briefcase"></i> ${flight.baggage_allowance || '15kg'}</span>
                            <span class="amenity-item"><i class="fas fa-utensils"></i> ${flight.meal_included ? 'Meal' : 'No Meal'}</span>
                            <span class="amenity-item"><i class="fas fa-exchange-alt"></i> ${flight.refundable ? 'Refundable' : 'Non-refundable'}</span>
                        </div>
                        <div class="price-section">
                            <div class="price-wrapper">
                                <div>
                                    <span class="price-tag">₹${Number(flight.price_per_person).toLocaleString()}</span>
                                    <span class="price-label">/person</span>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs" style="color: var(--text-muted);">${flight.flight_class || 'Economy'} Class</div>
                                </div>
                            </div>
                            <button onclick="selectFlight(${flight.id})" class="select-btn ${isSelected ? 'selected' : ''}">
                                ${isSelected ? '✓ SELECTED' : 'SELECT THIS FLIGHT'}
                            </button>
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
                        localStorage.removeItem('tripmate-theme');
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

                    // Simulate session restore
                    setTimeout(() => {
                        document.getElementById('sessionRestoreMsg').style.display = 'none';
                        sessionStorage.setItem('user_id', storedUserId);
                        sessionStorage.setItem('user_name', storedUserName);
                        document.body.classList.add('user-logged-in');
                        showToast('Session restored!', 'success');
                    }, 1500);
                }
            <?php endif; ?>
        });
    </script>
</body>

</html>