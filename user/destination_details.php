<?php
require_once __DIR__ . '/session_init.php'; // Initialize session management

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.html');
    exit;
}

require_once __DIR__ . '/../database/dbconfig.php';
require_once __DIR__ . '/../database/app_config.php';  // ADD THIS LINE
require_once __DIR__ . '/../database/api_config.php';  // ADD THIS LINE
require_once __DIR__ . '/../backand/image_helper.php';

// --- Input validation & load destination ---
if (!isset($_GET['id'])) {
    header("Location: ../search/search.html");
    exit();
}

$destination_id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM destinations WHERE id = ?");
$stmt->bind_param("i", $destination_id);
$stmt->execute();
$destination = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$destination) {
    header("Location: ../search/search.html");
    exit();
}

// --- Basic metadata for OG / SEO ---
$og_title = htmlspecialchars($destination['name'] . ' | TripMate');
$og_description = htmlspecialchars(mb_strimwidth(strip_tags($destination['description']), 0, 220, '...'));
$base_url = getBaseUrl();
$current_url = "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

// ============================================
// FIXED: Get images from 'images' column (not 'image_urls')
// ============================================
$coverImage = getDestinationImageUrl($destination, $base_url);
$og_image = $coverImage;

// ============================================
// FIXED: Get cuisine images from 'cuisine_images' column
// ============================================
$cuisine_images = [];
if (!empty($destination['cuisine_images'])) {
    $cuisine_data = safeJsonDecode($destination['cuisine_images']);
    foreach ($cuisine_data as $cuisine_name => $image_path) {
        if (!empty($image_path)) {
            $cuisine_images[$cuisine_name] = getCuisineImageUrl($image_path, $base_url);
        }
    }
}

function normalizeDestinationImageUrl($imageRef, $base_url)
{
    $imageRef = trim($imageRef);
    if (empty($imageRef)) {
        return $base_url . '/images/destination-placeholder.jpg';
    }
    if (preg_match('#^https?://#i', $imageRef)) {
        return $imageRef;
    }
    if (strpos($imageRef, '/') === 0) {
        return $base_url . $imageRef;
    }
    return $base_url . '/uploads/destinations/' . basename($imageRef);
}

function formatSeasonLabel($season)
{
    $season = trim($season);
    if ($season === '') {
        return 'All year round';
    }
    $parts = preg_split('/\s*[-–—\\/,;]+\s*/u', $season);
    if (count($parts) <= 1) {
        return ucwords(strtolower($season));
    }
    $parts = array_filter(array_map(function ($part) {
        return trim($part);
    }, $parts));
    return 'Best time: ' . implode(' to ', array_map(function ($part) {
        return ucwords(strtolower($part));
    }, $parts));
}

function formatTravelerLabel($people)
{
    $people = trim($people);
    if ($people === '') {
        return 'All travelers';
    }
    $items = preg_split('/\s*[;,]+\s*/u', $people);
    $labels = [];
    foreach ($items as $item) {
        $item = trim($item);
        if ($item === '') {
            continue;
        }
        $lower = strtolower($item);
        if (strpos($lower, 'family') !== false) {
            $labels[] = 'Families';
        } elseif (strpos($lower, 'couple') !== false || strpos($lower, 'honeymoon') !== false) {
            $labels[] = 'Couples';
        } elseif (strpos($lower, 'solo') !== false) {
            $labels[] = 'Solo travelers';
        } elseif (strpos($lower, 'friend') !== false || strpos($lower, 'group') !== false) {
            $labels[] = 'Friends & groups';
        } else {
            $labels[] = ucwords($item);
        }
    }
    return implode(', ', array_unique($labels));
}

// ============================================
// FIXED: Extract destination images for gallery
// ============================================
$image_filenames = [];
$primary_images = safeJsonDecode($destination['images'] ?? '[]');
if (!is_array($primary_images)) {
    $primary_images = [];
    if (!empty($destination['images'])) {
        $primary_images = [trim($destination['images'])];
    }
}
$secondary_urls = safeJsonDecode($destination['image_urls'] ?? '[]');
if (!is_array($secondary_urls)) {
    $secondary_urls = [];
    if (!empty($destination['image_urls'])) {
        $secondary_urls = [trim($destination['image_urls'])];
    }
}
$image_filenames = array_values(array_unique(array_merge($primary_images, $secondary_urls)));

$season_label = formatSeasonLabel($destination['season'] ?? '');
$people_label = formatTravelerLabel($destination['people'] ?? '');

// --- Read advanced JSON fields safely ---
$attractions_array = safeJsonDecode($destination['attractions'] ?? '[]');
$tips = safeJsonDecode($destination['tips'] ?? '[]');
$cuisines = normalizeListField($destination['cuisines'] ?? '[]');
$language = normalizeListField($destination['language'] ?? '[]');

// --- Favorite state (server-side) ---
$is_favorite = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $fav_stmt = $conn->prepare("SELECT id FROM user_history WHERE user_id = ? AND activity_type = 'favorite' AND activity_details = ?");
    $fav_stmt->bind_param("is", $user_id, $destination_id);
    $fav_stmt->execute();
    $is_favorite = $fav_stmt->get_result()->num_rows > 0;
    $fav_stmt->close();
}

// --- Ratings & reviews ---
$average_rating = 0.0;
$reviews_count = 0;
$reviews = [];
$rev_stmt = $conn->prepare("SELECT r.*, u.name as user_name FROM reviews r LEFT JOIN users u ON u.id = r.user_id WHERE r.destination_id = ? ORDER BY r.created_at DESC LIMIT 20");
$rev_stmt->bind_param("i", $destination_id);
$rev_stmt->execute();
$res = $rev_stmt->get_result();
while ($row = $res->fetch_assoc()) {
    // Process review images
    if (!empty($row['images'])) {
        $row['images_array'] = json_decode($row['images'], true);
    } else {
        $row['images_array'] = [];
    }
    $reviews[] = $row;
}
$rev_stmt->close();
if (!empty($reviews)) {
    $sum = 0;
    foreach ($reviews as $r) $sum += (float)$r['rating'];
    $average_rating = round($sum / count($reviews), 1);
    $reviews_count = count($reviews);
}

// --- Structured data with article schema ---
$structured_data = [
    "@context" => "https://schema.org",
    "@type" => "TouristDestination",
    "name" => $destination['name'],
    "description" => mb_strimwidth(strip_tags($destination['description']), 0, 300, '...'),
    "image" => $og_image,
    "geo" => [
        "@type" => "GeoCoordinates",
        "latitude" => $destination['latitude'] ?? '',
        "longitude" => $destination['longitude'] ?? ''
    ],
    "aggregateRating" => [
        "@type" => "AggregateRating",
        "ratingValue" => $average_rating ?: 0,
        "reviewCount" => $reviews_count
    ],
    "url" => $current_url
];

// Article schema for the description content
$article_schema = [
    "@context" => "https://schema.org",
    "@type" => "Article",
    "headline" => $destination['name'] . " - Complete Travel Guide",
    "description" => $og_description,
    "image" => $og_image,
    "author" => [
        "@type" => "Organization",
        "name" => "TripMate"
    ],
    "publisher" => [
        "@type" => "Organization",
        "name" => "TripMate",
        "logo" => [
            "@type" => "ImageObject",
            "url" => $base_url . "/images/logo.png"
        ]
    ],
    "datePublished" => $destination['created_at'] ?? date('Y-m-d'),
    "dateModified" => $destination['updated_at'] ?? date('Y-m-d'),
    "mainEntityOfPage" => $current_url
];

// Breadcrumb schema
$breadcrumb_schema = [
    "@context" => "https://schema.org",
    "@type" => "BreadcrumbList",
    "itemListElement" => [
        [
            "@type" => "ListItem",
            "position" => 1,
            "name" => "Home",
            "item" => $base_url . "/main/index.html"
        ],
        [
            "@type" => "ListItem",
            "position" => 2,
            "name" => "Destinations",
            "item" => $base_url . "/search/search.html"
        ],
        [
            "@type" => "ListItem",
            "position" => 3,
            "name" => $destination['name'],
            "item" => $current_url
        ]
    ]
];

// --- Fetch budget data for this destination ---
$hotel_stmt = $conn->prepare("SELECT * FROM hotels WHERE destination_id = ? ORDER BY 
    CASE hotel_type 
        WHEN 'low' THEN 1 
        WHEN 'medium' THEN 2 
        WHEN 'high' THEN 3 
    END, price_per_night ASC");
$hotel_stmt->bind_param("i", $destination_id);
$hotel_stmt->execute();
$hotels = $hotel_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$all_hotels = $hotels;

// ============================================
// FIXED: Process hotel images properly
// ============================================
foreach ($all_hotels as &$hotel) {
    if (!empty($hotel['image_url'])) {
        $image_path = $hotel['image_url'];
        if (preg_match('/^https?:\/\//', $image_path)) {
            $hotel['image_url_clean'] = $image_path;
        } elseif (strpos($image_path, '/uploads/') === 0) {
            $hotel['image_url_clean'] = $base_url . $image_path;
        } else {
            $hotel['image_url_clean'] = $base_url . '/uploads/hotels/' . basename($image_path);
        }
    } else {
        $hotel['image_url_clean'] = $base_url . '/images/hotel-placeholder.jpg';
    }
}

$flight_stmt = $conn->prepare("SELECT * FROM flights WHERE destination_id = ? ORDER BY 
    CASE flight_type 
        WHEN 'low' THEN 1 
        WHEN 'medium' THEN 2 
        WHEN 'high' THEN 3 
    END, price_per_person ASC");
$flight_stmt->bind_param("i", $destination_id);
$flight_stmt->execute();
$flights = $flight_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$hotels_by_type = ['low' => [], 'medium' => [], 'high' => []];
foreach ($hotels as $hotel) {
    $hotels_by_type[$hotel['hotel_type']][] = $hotel;
}

$flights_by_type = ['low' => [], 'medium' => [], 'high' => []];
foreach ($flights as $flight) {
    $flights_by_type[$flight['flight_type']][] = $flight;
}

$departure_cities = array_unique(array_column($flights, 'departure_city'));
sort($departure_cities);

// ============================================
// FIXED: Load API keys from secure config
// ============================================
require_once __DIR__ . '/../database/api_config.php';

$google_api_key = getApiKey('GOOGLE_CUSTOM_SEARCH_API_KEY', '');
$google_cse_id = getApiKey('GOOGLE_CUSTOM_SEARCH_ENGINE_ID', '');
$weather_api_key = getApiKey('OPENWEATHER_API_KEY', '');

// If API keys are missing, log error but continue (features will be disabled)
if (empty($google_api_key) || empty($google_cse_id)) {
    error_log("Google Custom Search API keys not configured");
}

// Weather API - use the constant from weather_config.php if available
if (defined('WEATHER_API_KEY') && WEATHER_API_KEY !== 'YOUR_API_KEY_HERE') {
    $weather_api_key = WEATHER_API_KEY;
}

// Get user name for profile
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : (isset($_SESSION['username']) ? $_SESSION['username'] : '');
$profile_pic = '../image/default-avatar.png';
if (isset($_SESSION['user_id'])) {
    $user_stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $_SESSION['user_id']);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_data = $user_result->fetch_assoc();
    if ($user_data && !empty($user_data['profile_pic'])) {
        $profile_pic = $user_data['profile_pic'];
    }
    $user_stmt->close();
}

// Attractions
$attractions_array = json_decode($destination['attractions'] ?? '[]', true);
if (!is_array($attractions_array)) {
    $attractions_array = [];
}

// Get similar destinations (same type or nearby)
$similar_destinations = [];
$similar_stmt = $conn->prepare("SELECT id, name, location, type, budget, images, profile_pic FROM destinations WHERE (type = ? OR location LIKE ?) AND id != ? LIMIT 6");
$location_like = '%' . substr($destination['location'], 0, strpos($destination['location'], ',') ?: strlen($destination['location'])) . '%';
$similar_stmt->bind_param("ssi", $destination['type'], $location_like, $destination_id);
$similar_stmt->execute();
$similar_result = $similar_stmt->get_result();
while ($row = $similar_result->fetch_assoc()) {
    // Process image for similar destination
    $similar_cover = $base_url . '/images/no-image.jpg';
    if (!empty($row['images'])) {
        $sim_images = json_decode($row['images'], true);
        if (is_array($sim_images) && !empty($sim_images)) {
            $similar_cover = $base_url . '/uploads/destinations/' . basename($sim_images[0]);
        }
    } elseif (!empty($row['profile_pic'])) {
        $similar_cover = $base_url . '/uploads/destinations/' . basename($row['profile_pic']);
    }
    $row['cover_image'] = $similar_cover;
    $similar_destinations[] = $row;
}
$similar_stmt->close();

// Weather API using destination name - 5-day forecast
include '../database/weather_config.php';
$weather_api_key = WEATHER_API_KEY;
$destination_name_for_weather = urlencode($destination['name'] . ',' . $destination['location']);
$weather_api_url = WEATHER_API_URL . "?q={$destination_name_for_weather}&units=metric&appid={$weather_api_key}";
$forecast_api_url = "https://api.openweathermap.org/data/2.5/forecast?q={$destination_name_for_weather}&units=metric&appid={$weather_api_key}&cnt=40";

// Fetch weather data from API (server-side fallback)
$weather_data = null;
$forecast_data = null;
$weather_error = null;
$weather_json = @file_get_contents($weather_api_url);
if ($weather_json !== false) {
    $weather_data = json_decode($weather_json, true);
    if (!isset($weather_data['main'])) {
        $weather_error = "Weather data not available";
        $weather_data = null;
    }
}
// Fetch 5-day forecast
$forecast_json = @file_get_contents($forecast_api_url);
if ($forecast_json !== false) {
    $forecast_all = json_decode($forecast_json, true);
    if (isset($forecast_all['list']) && is_array($forecast_all['list'])) {
        // Group by day and get one reading per day (around noon)
        $daily_forecast = [];
        foreach ($forecast_all['list'] as $reading) {
            $date = date('Y-m-d', $reading['dt']);
            $hour = date('H', $reading['dt']);
            if (!isset($daily_forecast[$date]) && $hour >= 11 && $hour <= 14) {
                $daily_forecast[$date] = $reading;
            }
            if (count($daily_forecast) >= 5) break;
        }
        $forecast_data = array_values($daily_forecast);
    }
}

// Exchange rates (INR as base)
$exchange_rates = [];
$exchange_json = @file_get_contents('https://api.exchangerate.host/latest?base=INR&symbols=USD,EUR,GBP');
if ($exchange_json !== false) {
    $exchange_data = json_decode($exchange_json, true);
    if (isset($exchange_data['rates'])) {
        $exchange_rates = $exchange_data['rates'];
    }
}
// Fallback rates
if (empty($exchange_rates)) {
    $exchange_rates = ['USD' => 0.012, 'EUR' => 0.011, 'GBP' => 0.0095];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $og_title; ?></title>
    <meta name="description" content="<?php echo $og_description; ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo $current_url; ?>">
    <meta name="user-id" content="<?php echo htmlspecialchars($_SESSION['user_id']); ?>">
    <meta name="user-name" content="<?php echo htmlspecialchars($_SESSION['user_name']); ?>">

    <!-- Session Management Scripts -->
    <script src="session-keepalive.js"></script>
    <script src="session-sync.js"></script>
    <script src="auto-logout.js"></script>

    <!-- Open Graph tags -->
    <meta property="og:title" content="<?php echo $og_title; ?>">
    <meta property="og:description" content="<?php echo $og_description; ?>">
    <meta property="og:image" content="<?php echo $og_image; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $current_url; ?>">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $og_title; ?>">
    <meta name="twitter:description" content="<?php echo $og_description; ?>">
    <meta name="twitter:image" content="<?php echo $og_image; ?>">

    <!-- Preconnect to external APIs for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://unpkg.com">
    <link rel="preconnect" href="https://api.openweathermap.org">
    <link rel="dns-prefetch" href="https://api.exchangerate.host">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Swiper -->
    <link rel="stylesheet" href="https://unpkg.com/swiper@9/swiper-bundle.min.css" />
    <!-- PhotoSwipe -->
    <link rel="stylesheet" href="https://unpkg.com/photoswipe@5/dist/photoswipe.css">
    <!-- User Profile CSS -->
    <link rel="stylesheet" href="../user/user-profile.css">

    <style>
        /* ============================================
           COLOR THEME: #E55437, #E1CF79, #B4B4B4, #3097BF
           ============================================ */
        :root {
            --primary: #E55437;
            --primary-dark: #C43A1F;
            --primary-light: #F06E54;
            --secondary: #3097BF;
            --secondary-dark: #237A9C;
            --secondary-light: #5BAFD1;
            --accent: #E1CF79;
            --accent-dark: #D1BD5A;
            --accent-light: #F0E3A0;
            --neutral: #B4B4B4;
            --neutral-dark: #8A8A8A;
            --neutral-light: #D1D1D1;

            --bg-body: #F8F9FA;
            --bg-card: #FFFFFF;
            --bg-surface: rgba(255, 255, 255, 0.95);
            --bg-surface-rgb: 255, 255, 255;
            --text-main: #1A1A2E;
            --text-muted: #6C757D;
            --border-color: #E9ECEF;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 12px 30px rgba(0, 0, 0, 0.12);
        }

        .dark-mode {
            --primary: #E55437;
            --primary-dark: #C43A1F;
            --secondary: #3097BF;
            --accent: #E1CF79;

            --bg-body: #121212;
            --bg-card: #1E1E2E;
            --bg-surface: rgba(30, 30, 46, 0.95);
            --bg-surface-rgb: 30, 30, 46;
            --text-main: #F1F3F5;
            --text-muted: #A0A0B0;
            --border-color: #2D2D3A;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            --shadow-hover: 0 12px 30px rgba(0, 0, 0, 0.4);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
            line-height: 1.5;
            transition: background 0.3s, color 0.3s;
            overflow-x: hidden;
        }

        /* Scroll Progress Bar */
        .scroll-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            z-index: 1001;
            transition: width 0.1s;
        }

        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 99;
            box-shadow: var(--shadow);
            border: none;
        }

        .back-to-top.show {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            color: var(--text-main);
            align-items: center;
            justify-content: center;
        }

        /* Navbar - Floating & Rounded */
        .navbar {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 40px);
            max-width: 1200px;
            background: var(--bg-surface);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 60px;
            padding: 0.5rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 1003;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            top: 10px;
            background: rgba(var(--bg-surface-rgb, 255, 255, 255), 0.98);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-size: 1.3rem;
            font-weight: 800;
        }

        .logo i {
            color: var(--primary);
            font-size: 1.5rem;
        }

        .brand-text .trip {
            color: var(--primary);
        }

        .brand-text .mate {
            color: var(--secondary);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .nav-links>* {
            flex-shrink: 0;
        }

        .nav-btn {
            background: var(--bg-card);
            color: var(--text-main);
            border: 1px solid var(--border-color);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .nav-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .theme-toggle {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            color: var(--text-main);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .theme-toggle:hover {
            background: var(--primary);
            color: white;
            transform: rotate(15deg);
        }

        /* Profile menu in navbar */
        .profile-menu {
            position: relative;
        }

        .profile-btn-nav {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            padding: 0.4rem 1rem;
            border-radius: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            font-size: 0.85rem;
            white-space: nowrap;
            min-width: 160px;
            justify-content: center;
        }

        .profile-btn-nav:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .profile-btn-nav img {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
        }

        .profile-btn-nav .profile-name {
            display: inline;
        }

        .profile-dropdown-nav {
            position: absolute;
            top: calc(100% + 0.5rem);
            right: 0;
            min-width: 220px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            box-shadow: var(--shadow);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s;
            z-index: 1001;
            overflow: hidden;
        }

        .profile-dropdown-nav.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .profile-dropdown-nav a,
        .profile-dropdown-nav button {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--text-main);
            text-decoration: none;
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .profile-dropdown-nav a:hover,
        .profile-dropdown-nav button:hover {
            background: var(--bg-body);
            color: var(--primary);
        }

        .profile-dropdown-nav i {
            width: 20px;
            color: var(--primary);
        }

        .profile-dropdown-nav hr {
            margin: 0.5rem 0;
            border: none;
            border-top: 1px solid var(--border-color);
        }

        .profile-header-nav {
            padding: 0.75rem 1rem;
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            color: white;
        }

        /* Social Login Icons in Dropdown */
        .social-login-icons {
            display: flex;
            gap: 12px;
            padding: 12px 16px;
            border-top: 1px solid var(--border-color);
            margin-top: 8px;
        }

        .social-login-icons a {
            flex: 1;
            justify-content: center;
            background: var(--bg-body);
            border-radius: 30px;
            padding: 8px;
        }

        .social-login-icons a i {
            margin: 0;
            font-size: 1.1rem;
        }

        .social-login-icons a .fa-google {
            color: #DB4437;
        }

        .social-login-icons a .fa-facebook-f {
            color: #4267B2;
        }

        .social-login-icons a .fa-instagram {
            color: #E4405F;
        }

        /* Main Container - Compact */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 100px 20px 40px;
            display: flex;
            gap: 2rem;
            position: relative;
            width: 100%;
            overflow-x: hidden;
        }

        /* Fixed Left Panel (Jump To Section) */
        .fixed-left-panel {
            position: fixed;
            left: calc((100% - 1400px) / 2 + 20px);
            top: 120px;
            width: 240px;
            background: var(--bg-card);
            border-radius: 20px;
            padding: 1.25rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            z-index: 80;
            transition: all 0.3s ease;
        }

        /* Mobile: Hide left panel by default, show via hamburger */
        @media (max-width: 992px) {
            .fixed-left-panel {
                position: fixed;
                left: -280px;
                top: 100px;
                width: 260px;
                height: auto;
                z-index: 100;
                transition: left 0.3s ease;
            }

            .fixed-left-panel.open {
                left: 20px;
            }

            .mobile-menu-toggle {
                display: flex;
                position: fixed;
                bottom: 80px;
                right: 20px;
                z-index: 100;
                background: var(--primary);
                color: white;
                border: none;
                box-shadow: var(--shadow);
            }

            .mobile-menu-toggle:hover {
                background: var(--primary-dark);
            }

            .navbar {
                left: 0;
                width: 100%;
                max-width: 100%;
                border-radius: 0;
                padding: 0.75rem 1rem;
            }

            .nav-links {
                width: 100%;
                justify-content: space-between;
                gap: 0.5rem;
            }

            .profile-btn-nav {
                min-width: auto;
                width: auto;
            }
        }

        .fixed-left-panel .panel-title {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            border-left: 3px solid var(--primary);
            padding-left: 12px;
        }

        .toc-nav {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .toc-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.6rem 0.75rem;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.2s;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .toc-link:hover,
        .toc-link.active {
            background: linear-gradient(135deg, rgba(229, 84, 55, 0.1), rgba(48, 151, 191, 0.05));
            color: var(--primary);
        }

        .toc-link i {
            width: 22px;
            color: var(--primary);
            font-size: 0.9rem;
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            margin-left: 280px;
            width: 100%;
            max-width: calc(100% - 320px);
            overflow-x: hidden;
        }

        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                max-width: 100%;
            }
        }

        /* Hero Section - Responsive with viewport-relative height */
        .hero {
            position: relative;
            border-radius: 24px;
            overflow: hidden;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 100%;
        }

        .hero-media {
            position: relative;
            width: 100%;
            /* Use viewport-relative height for responsive scaling */
            height: 50vh;
            min-height: 280px;
            max-height: 600px;
            overflow: hidden;
        }

        /* Slightly taller hero on larger screens */
        @media (min-width: 1600px) {
            .hero-media {
                height: 55vh;
                max-height: 650px;
            }
        }

        /* Maintain good proportions on tablets */
        @media (min-width: 768px) and (max-width: 1024px) {
            .hero-media {
                height: 45vh;
                min-height: 300px;
                max-height: 500px;
            }
        }

        /* On mobile, ensure it doesn't get too small */
        @media (max-width: 767px) {
            .hero-media {
                height: 40vh;
                min-height: 240px;
                max-height: 400px;
            }
        }

        /* On very short screens, cap the height to avoid excessive */
        @media (max-height: 600px) {
            .hero-media {
                height: 55vh;
                min-height: 220px;
            }
        }

        .hero-media img,
        .hero-media video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.2) 0%, rgba(0, 0, 0, 0.6) 100%);
        }

        .hero-badges {
            position: absolute;
            top: 20px;
            left: 20px;
            display: flex;
            gap: 10px;
            z-index: 2;
            flex-wrap: wrap;
        }

        .hero-badge {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            padding: 6px 14px;
            border-radius: 30px;
            color: white;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .hero-title {
            position: absolute;
            bottom: 30px;
            left: 30px;
            color: white;
            z-index: 2;
        }

        .hero-title h1 {
            font-size: 2.5rem;
            margin-bottom: 5px;
            font-weight: 800;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        @media (max-width: 768px) {
            .hero-title h1 {
                font-size: 1.5rem;
            }

            .hero-title p {
                font-size: 0.85rem;
            }

            .hero-badges {
                top: 12px;
                left: 12px;
            }

            .hero-badge {
                padding: 4px 10px;
                font-size: 0.7rem;
            }
        }

        .hero-title p {
            font-size: 1rem;
            opacity: 0.9;
            text-shadow: 0 1px 5px rgba(0, 0, 0, 0.3);
        }

        /* Sticky Info Bar */
        .info-bar {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            position: sticky;
            top: 85px;
            z-index: 85;
            backdrop-filter: blur(10px);
            width: 100%;
            overflow-x: hidden;
        }

        .info-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .info-thumb {
            width: 55px;
            height: 55px;
            border-radius: 16px;
            object-fit: cover;
        }

        .info-details h2 {
            font-size: 1.2rem;
            margin-bottom: 4px;
        }

        .info-details p {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .rating-badge {
            display: flex;
            align-items: center;
            gap: 4px;
            color: var(--primary);
            font-weight: 700;
        }

        .info-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-icon {
            padding: 8px 18px;
            border-radius: 40px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
        }

        .btn-fav {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-fav.active {
            background: var(--primary);
            color: white;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--secondary);
            color: white;
            border: none;
        }

        .btn-secondary:hover {
            background: var(--secondary-dark);
        }

        /* Cards */
        .card {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
            overflow-x: hidden;
            word-wrap: break-word;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .card:hover {
            box-shadow: var(--shadow-hover);
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid var(--primary);
            padding-left: 14px;
        }

        .card-title i {
            color: var(--primary);
        }

        /* Loading Skeleton */
        .skeleton {
            background: linear-gradient(90deg, var(--border-color) 25%, var(--bg-body) 50%, var(--border-color) 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
            border-radius: 8px;
        }

        @keyframes skeleton-loading {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        .skeleton-text {
            height: 16px;
            margin-bottom: 8px;
        }

        .skeleton-title {
            height: 24px;
            width: 60%;
            margin-bottom: 12px;
        }

        .skeleton-card {
            padding: 16px;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            margin-bottom: 12px;
        }

        /* Cuisine Grid - Square with curved edges and names below */
        .cuisine-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            justify-content: flex-start;
            max-width: 100%;
            overflow-x: hidden;
        }

        .cuisine-item {
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s;
            width: 100px;
        }

        .cuisine-item:hover {
            transform: translateY(-5px);
        }

        .cuisine-img {
            width: 100px;
            height: 100px;
            border-radius: 20px;
            object-fit: cover;
            box-shadow: var(--shadow);
            transition: all 0.2s;
            border: 2px solid transparent;
        }

        .cuisine-img:hover {
            border-color: var(--primary);
            box-shadow: 0 8px 25px rgba(229, 84, 55, 0.2);
        }

        .cuisine-name {
            margin-top: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-main);
        }

        /* Modal for enlarged cuisine image */
        .cuisine-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }

        .cuisine-modal.active {
            display: flex;
        }

        .cuisine-modal img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 16px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.5);
        }

        /* Gallery */
        .gallery-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 8px;
        }

        .gallery-tab {
            padding: 6px 18px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .gallery-tab.active {
            background: var(--primary);
            color: white;
        }

        .gallery-section {
            display: none;
        }

        .gallery-section.active {
            display: block;
        }

        .swiper {
            border-radius: 16px;
            overflow: hidden;
            width: 100%;
            max-width: 100%;
        }

        .swiper-slide img {
            width: 100%;
            height: auto;
            min-height: 250px;
            max-height: 450px;
            aspect-ratio: 16 / 10;
            object-fit: cover;
        }

        @media (max-width: 768px) {
            .swiper-slide img {
                min-height: 200px;
                max-height: 350px;
            }
        }

        .thumb-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 12px;
            max-width: 100%;
            overflow-x: auto;
        }

        .thumb-grid img {
            width: 100%;
            height: auto;
            min-height: 70px;
            max-height: 100px;
            aspect-ratio: 1;
            object-fit: cover;
            border-radius: 12px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        @media (max-width: 768px) {
            .thumb-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .thumb-grid img:hover {
            transform: scale(1.02);
        }

        /* Google Images Grid */
        .google-images-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 12px;
            max-width: 100%;
            overflow-x: hidden;
        }

        .google-images-grid img {
            width: 100%;
            height: auto;
            min-height: 100px;
            max-height: 150px;
            aspect-ratio: 1;
            object-fit: cover;
            border-radius: 12px;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .google-images-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Attractions List */
        .attraction-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.2s;
        }

        .attraction-item:hover {
            background: var(--bg-body);
            padding-left: 8px;
            border-radius: 12px;
        }

        .attraction-item i {
            color: var(--primary);
            width: 24px;
        }

        .attraction-item a {
            color: var(--text-main);
            text-decoration: none;
            flex: 1;
            transition: color 0.2s;
        }

        .attraction-item a:hover {
            color: var(--primary);
            text-decoration: underline;
        }

        .attraction-item .search-link {
            color: var(--secondary);
            font-size: 0.75rem;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .attraction-item .search-link:hover {
            opacity: 1;
        }

        /* Quick Details Grid */
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            max-width: 100%;
        }

        @media (max-width: 768px) {
            .details-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
        }

        .detail-item {
            padding: 10px;
            background: var(--bg-body);
            border-radius: 12px;
        }

        .detail-item strong {
            display: block;
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .detail-item span {
            font-weight: 700;
        }

        /* Review Section */
        .review-card {
            background: var(--bg-body);
            border-radius: 16px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid var(--border-color);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .review-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .review-user i {
            font-size: 2rem;
            color: var(--primary);
        }

        .review-stars {
            color: #fbbf24;
            font-size: 0.9rem;
        }

        .review-comment {
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 0.75rem;
        }

        .review-images {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 10px;
            max-width: 100%;
            overflow-x: hidden;
        }

        .review-images img {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            cursor: pointer;
        }

        .write-review-btn {
            margin-top: 1rem;
            width: 100%;
        }

        /* Exchange Rate Converter */
        .exchange-converter {
            margin-top: 1rem;
        }

        .exchange-row {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
            max-width: 100%;
            overflow-x: hidden;
        }

        .exchange-row input {
            flex: 1;
            min-width: 100px;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: var(--bg-card);
            color: var(--text-main);
        }

        .exchange-row select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: var(--bg-card);
            color: var(--text-main);
            max-width: 100%;
        }

        .exchange-result {
            font-weight: 700;
            color: var(--primary);
            margin-top: 8px;
            text-align: center;
        }

        /* 5-Day Weather Forecast */
        .forecast-grid {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding: 10px 0;
            width: 100%;
            max-width: 100%;
            -webkit-overflow-scrolling: touch;
        }

        .forecast-day {
            flex-shrink: 0;
            min-width: 100px;
            max-width: 120px;
            text-align: center;
            background: rgba(48, 151, 191, 0.1);
            border-radius: 12px;
            padding: 10px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .forecast-day .day {
            font-weight: 700;
            font-size: 0.85rem;
        }

        .forecast-day .temp {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .forecast-day .condition {
            font-size: 0.75rem;
        }

        /* Hotel Filters & Sorting */
        .hotel-filters {
            display: flex;
            gap: 12px;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            max-width: 100%;
            overflow-x: hidden;
        }

        .filter-input {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: var(--bg-card);
            color: var(--text-main);
            min-width: 150px;
            max-width: 100%;
        }

        .sort-select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: var(--bg-card);
            color: var(--text-main);
            max-width: 100%;
        }

        /* Hotel Items */
        .hotel-item {
            border: 1px solid var(--border-color);
            border-radius: 16px;
            margin-bottom: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        .hotel-item:hover {
            box-shadow: var(--shadow-hover);
        }

        .hotel-header {
            padding: 16px;
            background: var(--bg-card);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: background 0.2s;
        }

        .hotel-header:hover {
            background: var(--bg-body);
        }

        .hotel-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
            flex-wrap: wrap;
        }

        .hotel-thumb {
            width: 70px;
            height: 70px;
            border-radius: 14px;
            object-fit: cover;
        }

        .hotel-name {
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 5px;
            color: var(--text-main);
        }

        .hotel-price {
            color: var(--primary);
            font-weight: 700;
            font-size: 1rem;
        }

        .hotel-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 4px;
            font-size: 0.8rem;
        }

        .hotel-rating i {
            color: #fbbf24;
        }

        .hotel-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            color: white;
        }

        .hotel-badge.low {
            background: #10b981;
        }

        .hotel-badge.medium {
            background: #f59e0b;
        }

        .hotel-badge.high {
            background: #ef4444;
        }

        .hotel-details {
            display: none;
            padding: 20px;
            background: var(--bg-body);
            border-top: 1px solid var(--border-color);
        }

        .hotel-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            max-width: 100%;
            overflow-x: hidden;
        }

        @media (max-width: 768px) {
            .hotel-details-grid {
                grid-template-columns: 1fr;
            }
        }

        .hotel-detail-section {
            background: var(--bg-card);
            border-radius: 14px;
            padding: 15px;
        }

        .hotel-detail-section h4 {
            color: var(--primary);
            margin-bottom: 12px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .hotel-detail-section p {
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 12px;
        }

        .amenities-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            max-width: 100%;
            overflow-x: hidden;
        }

        .amenity-tag {
            background: var(--bg-body);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            color: var(--text-main);
            border: 1px solid var(--border-color);
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-row i {
            width: 24px;
            color: var(--primary);
        }

        .hotel-features {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 15px 0;
            max-width: 100%;
            overflow-x: hidden;
        }

        .feature-badge {
            background: rgba(229, 84, 55, 0.1);
            color: var(--primary);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .hotel-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
            flex-wrap: wrap;
            max-width: 100%;
            overflow-x: hidden;
        }

        .hotel-actions .btn-icon {
            flex: 1;
            justify-content: center;
        }

        /* Sidebar Elements */
        .right-sidebar {
            width: 320px;
            flex-shrink: 0;
            max-width: 100%;
        }

        @media (max-width: 992px) {
            .right-sidebar {
                width: 100%;
                max-width: 100%;
            }
        }

        .map-container {
            height: 220px;
            border-radius: 16px;
            overflow: hidden;
            width: 100%;
            max-width: 100%;
        }

        .map-container iframe {
            width: 100%;
            height: 100%;
            border: 0;
        }

        @media (max-width: 768px) {
            .map-container {
                height: 250px;
            }
        }

        /* Weather Widget */
        .weather-widget {
            background: linear-gradient(135deg, var(--secondary), var(--secondary-dark));
            border-radius: 16px;
            padding: 18px;
            color: white;
        }

        .weather-current {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .weather-temp {
            font-size: 2rem;
            font-weight: 700;
        }

        .weather-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 15px;
            max-width: 100%;
            overflow-x: hidden;
        }

        @media (max-width: 480px) {
            .weather-details {
                grid-template-columns: 1fr;
            }
        }

        .weather-detail {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            padding: 8px;
            text-align: center;
        }

        /* Similar Destinations Carousel */
        .similar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
            max-width: 100%;
            overflow-x: hidden;
        }

        @media (max-width: 768px) {
            .similar-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .similar-grid {
                grid-template-columns: 1fr;
            }
        }

        .similar-card {
            background: var(--bg-card);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            transition: all 0.3s;
            cursor: pointer;
        }

        .similar-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .similar-image {
            height: auto;
            min-height: 120px;
            max-height: 180px;
            aspect-ratio: 1;
            background-size: cover;
            background-position: center;
        }

        .similar-content {
            padding: 12px;
        }

        .similar-name {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .similar-location {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .similar-price {
            color: var(--primary);
            font-weight: 700;
            font-size: 0.85rem;
            margin-top: 6px;
        }

        /* Budget Section */
        .budget-section {
            background: var(--bg-card);
            border-radius: 24px;
            padding: 1.5rem;
            margin-top: 2rem;
            border: 1px solid var(--border-color);
            clear: both;
            position: relative;
        }

        .budget-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .budget-btn {
            flex: 1;
            min-width: 120px;
            padding: 1rem;
            border-radius: 14px;
            font-weight: 700;
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .budget-btn.low {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .budget-btn.medium {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .budget-btn.high {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .budget-btn.selected {
            box-shadow: 0 0 0 3px var(--primary);
            transform: scale(1.02);
        }

        .trip-form {
            background: var(--bg-body);
            border-radius: 14px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: none;
        }

        .trip-form.visible {
            display: block;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .form-field label {
            display: block;
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .form-field input,
        .form-field select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: var(--bg-card);
            color: var(--text-main);
        }

        .package-details {
            display: none;
        }

        .package-details.visible {
            display: block;
        }

        .hotel-grid,
        .flight-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {

            .hotel-grid,
            .flight-grid {
                grid-template-columns: 1fr;
            }
        }

        .hotel-option,
        .flight-option {
            border: 2px solid var(--border-color);
            border-radius: 14px;
            padding: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .hotel-option.selected,
        .flight-option.selected {
            border-color: var(--primary);
            background: rgba(229, 84, 55, 0.05);
        }

        .total-cost {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 14px;
            padding: 1rem;
            color: white;
            margin-top: 1rem;
        }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--bg-card);
            border-left: 4px solid var(--primary);
            padding: 12px 20px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            z-index: 1002;
            transform: translateX(120%);
            transition: transform 0.3s;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast.success {
            border-left-color: #10b981;
        }

        .toast.error {
            border-left-color: #ef4444;
        }

        .toast.warning {
            border-left-color: #f59e0b;
        }

        /* Loading Spinner */
        .loading-spinner {
            text-align: center;
            padding: 20px;
            color: var(--text-muted);
        }

        .loading-spinner i {
            font-size: 2rem;
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

        /* Responsive */
        @media (max-width: 1200px) {
            .fixed-left-panel {
                left: 20px;
                width: 220px;
            }

            .main-content {
                margin-left: 260px;
            }
        }

        @media (max-width: 992px) {
            .container {
                flex-direction: column;
            }

            .main-content {
                margin-left: 0;
            }

            .right-sidebar {
                width: 100%;
            }

            .navbar {
                width: calc(100% - 20px);
                top: 10px;
                padding: 0.4rem 1rem;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 90px 15px 30px;
            }

            .info-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .info-actions {
                justify-content: flex-start;
            }

            .thumb-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .nav-links {
                gap: 0.5rem;
            }

            .nav-btn span {
                display: none;
            }

            .nav-btn i {
                margin: 0;
            }

            .profile-btn-nav .profile-name {
                display: none;
            }

            .google-images-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .cuisine-item {
                width: 80px;
            }

            .cuisine-img {
                width: 80px;
                height: 80px;
            }

            .similar-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .hidden {
            display: none;
        }

        .text-muted {
            color: var(--text-muted);
        }

        .collapsible {
            max-height: 100px;
            overflow: hidden;
            transition: max-height 0.3s;
            word-wrap: break-word;
            word-break: break-word;
            white-space: normal;
            overflow-wrap: break-word;
        }

        .collapsible.open {
            max-height: 1000px;
        }

        .read-more-btn {
            background: none;
            border: none;
            color: var(--primary);
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
        }
    </style>
</head>

<body class="user-logged-in" data-user-id="<?php echo htmlspecialchars($_SESSION['user_id']); ?>">

    <div class="scroll-progress" id="scrollProgress"></div>
    <button class="back-to-top" id="backToTop" aria-label="Back to top">
        <i class="fas fa-arrow-up"></i>
    </button>
    <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Menu">
        <i class="fas fa-bars"></i>
    </button>

    <nav class="navbar" id="navbar">
        <a href="../main/index.html" class="logo">
            <i class="fa-solid fa-paper-plane"></i>
            <span class="brand-text"><span class="trip">Trip</span><span class="mate">Mate</span></span>
        </a>
        <div class="nav-links">
            <a href="../search/search.html" class="nav-btn"><i class="fas fa-search"></i> <span>Search</span></a>
            <button class="theme-toggle" id="themeToggle" aria-label="Switch dark/light mode"><i class="fas fa-moon"></i></button>

            <!-- Profile Menu in Navbar -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="profile-menu">
                    <button class="profile-btn-nav" id="profileBtnNav" aria-label="Profile menu">
                        <span class="profile-name"><?php echo htmlspecialchars($userName); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="profile-dropdown-nav" id="profileDropdownNav">
                        <div class="profile-header-nav">
                            <div class="font-bold"><?php echo htmlspecialchars($userName); ?></div>
                            <div class="text-sm">Traveler</div>
                        </div>
                        <a href="../user/user_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        <a href="../user/user_profile.php"><i class="fas fa-user"></i> My Profile</a>
                        <a href="../user/favourites.php"><i class="fas fa-heart"></i> Favorites</a>
                        <hr>
                        <button id="logoutBtnNav"><i class="fas fa-sign-out-alt"></i> Logout</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <main class="container">
        <!-- Fixed Left Panel (Jump To Section) -->
        <div class="fixed-left-panel" id="leftPanel">
            <div class="panel-title"><i class="fas fa-compass"></i> Jump to</div>
            <div class="toc-nav">
                <a href="#about" class="toc-link"><i class="fas fa-info-circle"></i> About</a>
                <a href="#cuisines" class="toc-link"><i class="fas fa-utensils"></i> Cuisines</a>
                <a href="#gallery" class="toc-link"><i class="fas fa-images"></i> Gallery</a>
                <a href="#attractions" class="toc-link"><i class="fas fa-map-pin"></i> Attractions</a>
                <a href="#reviews" class="toc-link"><i class="fas fa-star"></i> Reviews</a>
                <a href="#hotels" class="toc-link"><i class="fas fa-hotel"></i> Hotels</a>
                <a href="#map" class="toc-link"><i class="fas fa-map"></i> Map</a>
                <a href="#budget" class="toc-link"><i class="fas fa-wallet"></i> Budget Package</a>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">
            <!-- Hero Section with Responsive Viewport Height -->
            <div class="hero">
                <div class="hero-media">
                    <?php if (!empty($destination['promo_video'])):
                        $video_path = $base_url . '/uploads/destinations/' . basename($destination['promo_video']);
                    ?>
                        <video autoplay muted loop playsinline poster="<?php echo htmlspecialchars($coverImage); ?>">
                            <source src="<?php echo htmlspecialchars($video_path); ?>" type="video/mp4">
                        </video>
                    <?php else: ?>
                        <img src="<?php echo htmlspecialchars($coverImage); ?>" alt="<?php echo htmlspecialchars($destination['name']); ?>" loading="eager">
                    <?php endif; ?>
                    <div class="hero-overlay"></div>
                </div>
                <div class="hero-badges">
                    <div class="hero-badge"><i class="fas fa-star"></i> <?php echo $average_rating ?: 'New'; ?></div>
                    <div class="hero-badge"><i class="fas fa-wallet"></i> ₹<?php echo number_format($destination['budget'] ?? 0); ?>/day</div>
                    <?php if (!empty($destination['travel_time'])): ?>
                        <div class="hero-badge"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($destination['travel_time']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="hero-title">
                    <h1><?php echo htmlspecialchars($destination['name']); ?></h1>
                    <p><?php echo htmlspecialchars($destination['location']); ?></p>
                </div>
            </div>

            <!-- Sticky Info Bar -->
            <div class="info-bar">
                <div class="info-left">
                    <img src="<?php echo htmlspecialchars($coverImage); ?>" alt="Thumb" class="info-thumb">
                    <div class="info-details">
                        <h2><?php echo htmlspecialchars($destination['name']); ?></h2>
                        <p><?php echo htmlspecialchars($destination['location']); ?></p>
                        <div class="rating-badge">
                            <i class="fas fa-star"></i> <?php echo $average_rating ?: '—'; ?>
                            <span class="text-muted">• <?php echo $reviews_count; ?> reviews</span>
                        </div>
                    </div>
                </div>
                <div class="info-actions">
                    <button id="favBtn" class="btn-icon btn-fav <?php echo $is_favorite ? 'active' : ''; ?>" aria-label="<?php echo $is_favorite ? 'Remove from favorites' : 'Add to favorites'; ?>">
                        <i class="<?php echo $is_favorite ? 'fas' : 'far'; ?> fa-heart"></i>
                        <span><?php echo $is_favorite ? 'Favorited' : 'Save'; ?></span>
                    </button>
                    <button id="shareBtn" class="btn-icon btn-primary" aria-label="Share this destination"><i class="fas fa-share-alt"></i> Share</button>
                    <button id="planBtn" class="btn-icon btn-secondary" aria-label="Plan a trip"><i class="fas fa-calendar-plus"></i> Plan Trip</button>
                    <a href="../bookings/booking_page.php?destination_id=<?php echo $destination_id; ?>" class="btn-icon btn-primary" style="text-decoration: none;" aria-label="Book now"><i class="fas fa-ticket-alt"></i> Book</a>
                </div>
            </div>

            <!-- About Section -->
            <div class="card" id="about">
                <div class="card-title"><i class="fas fa-info-circle"></i> About <?php echo htmlspecialchars($destination['name']); ?></div>
                <div id="desc" class="collapsible"><?php echo nl2br(htmlspecialchars($destination['description'])); ?></div>
                <button class="read-more-btn" id="readMoreBtn" aria-label="Read more about this destination">Read more</button>
            </div>

            <!-- Quick Details -->
            <div class="card">
                <div class="card-title"><i class="fas fa-chart-simple"></i> Quick Details</div>
                <div class="details-grid">
                    <div class="detail-item"><strong>Type</strong><span><?php echo htmlspecialchars($destination['type']); ?></span></div>
                    <div class="detail-item"><strong>Budget</strong><span>₹<?php echo number_format($destination['budget']); ?>/day</span></div>
                    <div class="detail-item"><strong>Best Season</strong><span><?php echo htmlspecialchars($season_label); ?></span></div>
                    <div class="detail-item"><strong>Travelers</strong><span><?php echo htmlspecialchars($people_label); ?></span></div>
                </div>
            </div>

            <!-- Cuisines -->
            <div class="card" id="cuisines">
                <div class="card-title"><i class="fas fa-utensils"></i> Local Cuisines</div>
                <?php if (!empty($cuisines)): ?>
                    <div class="cuisine-grid">
                        <?php foreach ($cuisines as $c):
                            $img_src = isset($cuisine_images[$c]) ? $cuisine_images[$c] : $base_url . '/images/cuisine-placeholder.png';
                        ?>
                            <div class="cuisine-item" onclick="openCuisineModal('<?php echo htmlspecialchars($img_src); ?>')" role="button" tabindex="0" onkeydown="if(event.key==='Enter'||event.key===' ') openCuisineModal('<?php echo htmlspecialchars($img_src); ?>')" aria-label="Enlarge image of <?php echo htmlspecialchars($c); ?>">
                                <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($c); ?> cuisine" class="cuisine-img" loading="lazy">
                                <div class="cuisine-name"><?php echo htmlspecialchars($c); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No cuisine information available.</p>
                <?php endif; ?>
            </div>

            <!-- Gallery -->
            <div class="card" id="gallery">
                <div class="card-title"><i class="fas fa-images"></i> Gallery</div>
                <div class="gallery-tabs">
                    <div class="gallery-tab active" onclick="switchTab('local')" role="button" tabindex="0">Local</div>
                    <div class="gallery-tab" onclick="switchTab('google')" role="button" tabindex="0">Google Images</div>
                </div>

                <div id="localGallery" class="gallery-section active">
                    <div class="swiper" id="mainSwiper">
                        <div class="swiper-wrapper">
                            <?php if (!empty($image_filenames)): ?>
                                <?php foreach ($image_filenames as $filename): ?>
                                    <div class="swiper-slide"><img src="<?php echo normalizeDestinationImageUrl($filename, $base_url); ?>" alt="<?php echo htmlspecialchars($destination['name']); ?> gallery image" loading="lazy"></div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="swiper-slide"><img src="<?php echo htmlspecialchars($coverImage); ?>" alt="No images available"></div>
                            <?php endif; ?>
                        </div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                        <div class="swiper-pagination"></div>
                    </div>
                    <div class="thumb-grid" id="thumbGrid">
                        <?php if (!empty($image_filenames)): ?>
                            <?php foreach ($image_filenames as $filename): ?>
                                <img src="<?php echo normalizeDestinationImageUrl($filename, $base_url); ?>" data-full="<?php echo normalizeDestinationImageUrl($filename, $base_url); ?>" alt="Thumbnail" loading="lazy">
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- All Images Table/Grid Display -->
                    <?php if (!empty($image_filenames)): ?>
                        <div style="margin-top: 2rem; border-top: 2px solid var(--border-color); padding-top: 1.5rem;">
                            <h3 style="margin-bottom: 1rem; font-size: 1rem; font-weight: 700; color: var(--text-main);">
                                <i class="fas fa-table"></i> All Images (<?php echo count($image_filenames); ?> total)
                            </h3>
                            <div class="gallery-images-table" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; width: 100%;">
                                <?php foreach ($image_filenames as $index => $filename): ?>
                                    <div style="position: relative; border-radius: 12px; overflow: hidden; box-shadow: var(--shadow); cursor: pointer; transition: all 0.3s ease; background: var(--bg-body);"
                                        onclick="openPhotoSwipe(<?php echo $index; ?>)">
                                        <img src="<?php echo normalizeDestinationImageUrl($filename, $base_url); ?>"
                                            alt="Gallery image <?php echo $index + 1; ?>"
                                            style="width: 100%; height: 150px; object-fit: cover; display: block; transition: transform 0.3s ease;"
                                            loading="lazy"
                                            onmouseover="this.style.transform='scale(1.05)'"
                                            onmouseout="this.style.transform='scale(1)'">
                                        <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.4)); color: white; padding: 8px; font-size: 0.75rem; font-weight: 600;">
                                            <?php echo $index + 1; ?>/<?php echo count($image_filenames); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <p style="margin-top: 1rem; font-size: 0.85rem; color: var(--text-muted); text-align: center;">
                                <i class="fas fa-info-circle"></i> Click any image to view in full screen
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="googleGallery" class="gallery-section">
                    <div id="googleImages" class="google-images-grid">
                        <div class="loading-spinner">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Loading Google Images...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attractions -->
            <div class="card" id="attractions">
                <div class="card-title"><i class="fas fa-map-pin"></i> Top Attractions</div>
                <?php if (!empty($attractions_array) && is_array($attractions_array)): ?>
                    <?php foreach ($attractions_array as $attraction): ?>
                        <div class="attraction-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <a href="https://www.google.com/search?q=<?php echo urlencode($attraction . ' ' . $destination['name']); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                title="Search on Google">
                                <?php echo htmlspecialchars($attraction); ?>
                            </a>
                            <a href="https://www.google.com/search?q=<?php echo urlencode($attraction . ' ' . $destination['name']); ?>"
                                target="_blank"
                                class="search-link"
                                title="Search on Google">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No attraction details available.</p>
                <?php endif; ?>
            </div>

            <!-- Reviews Section -->
            <div class="card" id="reviews">
                <div class="card-title"><i class="fas fa-star"></i> Traveler Reviews</div>
                <?php if (!empty($reviews)): ?>
                    <div class="reviews-list">
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <div class="review-user">
                                        <i class="fas fa-user-circle"></i>
                                        <div>
                                            <strong><?php echo htmlspecialchars($review['user_name'] ?? 'Anonymous'); ?></strong>
                                            <div class="review-stars">
                                                <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                    </div>
                                </div>
                                <?php if (!empty($review['title'])): ?>
                                    <div class="font-bold mb-1"><?php echo htmlspecialchars($review['title']); ?></div>
                                <?php endif; ?>
                                <div class="review-comment"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></div>
                                <?php if (!empty($review['images_array'])): ?>
                                    <div class="review-images">
                                        <?php foreach ($review['images_array'] as $img): ?>
                                            <img src="<?php echo htmlspecialchars($img); ?>" alt="Review image" onclick="openImage('<?php echo htmlspecialchars($img); ?>')" loading="lazy">
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No reviews yet. Be the first to share your experience!</p>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <button class="btn-icon btn-primary write-review-btn" onclick="openWriteReviewModal()" aria-label="Write a review">
                        <i class="fas fa-pen"></i> Write a Review
                    </button>
                <?php else: ?>
                    <button class="btn-icon btn-secondary write-review-btn" onclick="alert('Please login to write a review')" aria-label="Login to write a review">
                        <i class="fas fa-lock"></i> Login to Write Review
                    </button>
                <?php endif; ?>
            </div>

            <!-- Hotels & Accommodations with Filters and Sorting -->
            <div class="card" id="hotels">
                <div class="card-title"><i class="fas fa-hotel"></i> Hotels & Accommodations</div>

                <!-- Hotel Filters -->
                <div class="hotel-filters">
                    <input type="number" id="priceMin" class="filter-input" placeholder="Min price ₹" style="width: 100px;">
                    <input type="number" id="priceMax" class="filter-input" placeholder="Max price ₹" style="width: 100px;">
                    <select id="hotelTypeFilter" class="filter-input">
                        <option value="all">All Budgets</option>
                        <option value="low">Low Budget</option>
                        <option value="medium">Medium Budget</option>
                        <option value="high">High Budget</option>
                    </select>
                    <select id="hotelSortBy" class="sort-select">
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                        <option value="rating_desc">Rating: High to Low</option>
                    </select>
                </div>

                <div id="hotelsContainer">
                    <!-- Loading Skeleton -->
                    <div class="skeleton-card">
                        <div class="skeleton skeleton-title" style="width: 60%;"></div>
                        <div class="skeleton skeleton-text" style="width: 80%;"></div>
                        <div class="skeleton skeleton-text" style="width: 50%;"></div>
                    </div>
                    <div class="skeleton-card">
                        <div class="skeleton skeleton-title" style="width: 50%;"></div>
                        <div class="skeleton skeleton-text" style="width: 70%;"></div>
                        <div class="skeleton skeleton-text" style="width: 40%;"></div>
                    </div>
                </div>

                <?php if (!empty($all_hotels)): ?>
                    <script>
                        // Store hotels data for filtering
                        window.allHotels = <?php echo json_encode($all_hotels); ?>;
                    </script>
                <?php else: ?>
                    <p class="text-muted">No hotels available for this destination yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column (Sidebar) -->
        <div class="right-sidebar">
            <!-- Map -->
            <div class="card" id="map">
                <div class="card-title"><i class="fas fa-map-marked-alt"></i> Location</div>
                <?php if (!empty($destination['latitude']) && !empty($destination['longitude'])): ?>
                    <div class="map-container">
                        <div id="destinationMap" style="width: 100%; height: 100%;"></div>
                    </div>
                    <div class="map-caption" style="margin-top: 12px; color: var(--text-muted); font-size: 0.95rem;">
                        Coordinates: <?php echo number_format((float)$destination['latitude'], 5); ?>, <?php echo number_format((float)$destination['longitude'], 5); ?>
                    </div>
                    <?php if (!empty($destination['map_link'])): ?>
                        <a href="<?php echo htmlspecialchars($destination['map_link']); ?>" target="_blank" class="btn-icon btn-secondary" style="display: inline-block; margin-top: 12px; text-decoration: none;" aria-label="Open in Maps">Open in Maps <i class="fas fa-external-link-alt"></i></a>
                    <?php endif; ?>
                <?php elseif (!empty($destination['map_link'])): ?>
                    <div class="map-container">
                        <iframe src="<?php echo htmlspecialchars($destination['map_link']); ?>&output=embed" allowfullscreen loading="lazy"></iframe>
                    </div>
                    <a href="<?php echo htmlspecialchars($destination['map_link']); ?>" target="_blank" class="btn-icon btn-secondary" style="display: inline-block; margin-top: 12px; text-decoration: none;" aria-label="Open in Google Maps">Open in Maps <i class="fas fa-external-link-alt"></i></a>
                <?php else: ?>
                    <p class="text-muted">No map available.</p>
                <?php endif; ?>
            </div>

            <!-- Weather - Current + 5-Day Forecast -->
            <div class="card">
                <div class="card-title"><i class="fas fa-cloud-sun"></i> Weather</div>
                <div id="weatherContent">
                    <?php if ($weather_data && isset($weather_data['main'])): ?>
                        <div class="weather-widget">
                            <div class="weather-current">
                                <div>
                                    <div class="weather-temp"><?php echo round($weather_data['main']['temp']); ?>°C</div>
                                    <div><?php echo ucfirst($weather_data['weather'][0]['description']); ?></div>
                                </div>
                                <div style="font-size: 2rem;">
                                    <?php
                                    $icon_code = $weather_data['weather'][0]['icon'];
                                    if ($icon_code == '01d') echo '☀️';
                                    elseif ($icon_code == '02d') echo '⛅';
                                    elseif ($icon_code == '03d' || $icon_code == '04d') echo '☁️';
                                    elseif ($icon_code == '09d' || $icon_code == '10d') echo '🌧️';
                                    elseif ($icon_code == '11d') echo '⛈️';
                                    elseif ($icon_code == '13d') echo '❄️';
                                    else echo '🌡️';
                                    ?>
                                </div>
                            </div>
                            <div class="weather-details">
                                <div class="weather-detail">💧 <?php echo $weather_data['main']['humidity']; ?>%</div>
                                <div class="weather-detail">💨 <?php echo round($weather_data['wind']['speed']); ?> km/h</div>
                                <div class="weather-detail">🌡️ Feels like <?php echo round($weather_data['main']['feels_like']); ?>°C</div>
                                <div class="weather-detail">📍 <?php echo htmlspecialchars($destination['name']); ?></div>
                            </div>
                        </div>
                        <!-- 5-Day Forecast -->
                        <?php if (!empty($forecast_data)): ?>
                            <div class="forecast-grid" style="margin-top: 15px;">
                                <?php foreach ($forecast_data as $day): ?>
                                    <div class="forecast-day">
                                        <div class="day"><?php echo date('D', $day['dt']); ?></div>
                                        <div class="temp"><?php echo round($day['main']['temp']); ?>°C</div>
                                        <div class="condition"><?php echo ucfirst($day['weather'][0]['description']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-muted" id="weatherFallback">Loading live weather...</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Exchange Rate Converter -->
            <div class="card">
                <div class="card-title"><i class="fas fa-exchange-alt"></i> Currency Converter</div>
                <div class="exchange-converter" id="exchangeConverter">
                    <div class="exchange-row">
                        <input type="number" id="amountInput" value="100" step="1" aria-label="Amount in INR">
                        <span>INR</span>
                    </div>
                    <div class="exchange-row">
                        <select id="currencySelect" aria-label="Select target currency">
                            <option value="USD">USD ($)</option>
                            <option value="EUR">EUR (€)</option>
                            <option value="GBP">GBP (£)</option>
                        </select>
                        <span id="convertedResult" class="exchange-result">≈ $0.00</span>
                    </div>
                </div>
            </div>

            <!-- Local Info -->
            <div class="card">
                <div class="card-title"><i class="fas fa-clock"></i> Local Info</div>
                <div><strong>Local Time:</strong> <span id="localTime">--:--</span></div>
            </div>
        </div>
    </main>

    <!-- Similar Destinations Section -->
    <div class="budget-section" id="similar" style="max-width: 1400px; margin: 2rem auto 0 auto; padding: 1.5rem;">
        <div class="card-title"><i class="fas fa-compass"></i> Similar Destinations</div>
        <?php if (!empty($similar_destinations)): ?>
            <div class="similar-grid">
                <?php foreach ($similar_destinations as $sim): ?>
                    <div class="similar-card" onclick="window.location.href='destination_details.php?id=<?php echo $sim['id']; ?>'">
                        <div class="similar-image" style="background-image: url('<?php echo htmlspecialchars($sim['cover_image']); ?>');"></div>
                        <div class="similar-content">
                            <div class="similar-name"><?php echo htmlspecialchars($sim['name']); ?></div>
                            <div class="similar-location"><?php echo htmlspecialchars($sim['location']); ?></div>
                            <div class="similar-price">₹<?php echo number_format($sim['budget']); ?>/day</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">No similar destinations found.</p>
        <?php endif; ?>
    </div>

    <!-- Budget Package Section -->
    <div class="budget-section" id="budget" style="max-width: 1400px; margin: 2rem auto 2rem auto; padding: 1.5rem;">
        <div class="card-title"><i class="fas fa-wallet"></i> Plan Your Budget Package</div>

        <div class="budget-buttons">
            <button onclick="selectBudget('low')" id="budgetLowBtn" class="budget-btn low" aria-label="Select low budget option">Low Budget</button>
            <button onclick="selectBudget('medium')" id="budgetMediumBtn" class="budget-btn medium" aria-label="Select medium budget option">Medium Budget</button>
            <button onclick="selectBudget('high')" id="budgetHighBtn" class="budget-btn high" aria-label="Select high budget option">High Budget</button>
        </div>

        <div id="tripForm" class="trip-form">
            <div class="form-row">
                <div class="form-field">
                    <label>Travelers</label>
                    <input type="number" id="travelers" min="1" value="2">
                </div>
                <div class="form-field">
                    <label>Days</label>
                    <input type="number" id="days" min="1" value="3">
                </div>
                <div class="form-field">
                    <label>Departure City</label>
                    <select id="departureCity">
                        <option value="">Select</option>
                        <?php foreach ($departure_cities as $city): ?>
                            <option value="<?php echo htmlspecialchars($city); ?>"><?php echo htmlspecialchars($city); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button class="btn-icon btn-primary" onclick="calculateTotal()" aria-label="Calculate package price">Calculate Package <i class="fas fa-calculator"></i></button>
        </div>

        <div id="packageDetails" class="package-details">
            <h4 style="margin-bottom: 1rem;">Recommended Hotels</h4>
            <div id="hotelsContainer" class="hotel-grid"></div>

            <h4 style="margin: 1rem 0;">Available Flights</h4>
            <div id="flightsContainer" class="flight-grid"></div>

            <div id="totalCost" class="total-cost"></div>
            <button class="btn-icon btn-primary" onclick="bookPackage()" style="width: 100%; margin-top: 1rem;" aria-label="Book this package">Book Package <i class="fas fa-ticket-alt"></i></button>
        </div>
    </div>

    <!-- Write Review Modal -->
    <div id="writeReviewModal" class="cuisine-modal" style="display: none; z-index: 2000;">
        <div class="modal-content" style="background: var(--bg-card); max-width: 500px; width: 90%; border-radius: 20px; padding: 2rem;">
            <button onclick="closeWriteReviewModal()" style="position: absolute; top: 15px; right: 20px; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">&times;</button>
            <h3 class="card-title" style="margin-bottom: 1.5rem;">Write Your Review</h3>
            <form id="reviewSubmitForm" enctype="multipart/form-data">
                <input type="hidden" name="destination_id" value="<?php echo $destination_id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <div class="form-field" style="margin-bottom: 1rem;">
                    <label>Rating</label>
                    <div class="review-stars" style="display: flex; flex-direction: row; justify-content: flex-start; gap: 5px;">
                        <input type="radio" name="rating" value="5" id="star5"><label for="star5">★</label>
                        <input type="radio" name="rating" value="4" id="star4"><label for="star4">★</label>
                        <input type="radio" name="rating" value="3" id="star3"><label for="star3">★</label>
                        <input type="radio" name="rating" value="2" id="star2"><label for="star2">★</label>
                        <input type="radio" name="rating" value="1" id="star1"><label for="star1">★</label>
                    </div>
                </div>
                <div class="form-field" style="margin-bottom: 1rem;">
                    <label>Title (Optional)</label>
                    <input type="text" name="title" class="filter-input" style="width: 100%;" placeholder="Summarize your experience">
                </div>
                <div class="form-field" style="margin-bottom: 1rem;">
                    <label>Your Review</label>
                    <textarea name="comment" rows="4" class="filter-input" style="width: 100%;" placeholder="Share your experience..." required></textarea>
                </div>
                <div class="form-field" style="margin-bottom: 1rem;">
                    <label>Photos (Optional)</label>
                    <input type="file" name="images[]" multiple accept="image/*" class="filter-input" style="width: 100%;">
                </div>
                <button type="submit" class="btn-icon btn-primary" style="width: 100%;">Submit Review <i class="fas fa-paper-plane"></i></button>
            </form>
        </div>
    </div>

    <!-- Cuisine Modal for enlarged image -->
    <div id="cuisineModal" class="cuisine-modal" onclick="closeCuisineModal()">
        <img id="cuisineModalImg" src="" alt="Enlarged Cuisine">
    </div>

    <!-- PhotoSwipe Root -->
    <div class="pswp" id="pswp" role="dialog" aria-hidden="true"></div>

    <!-- Toast Container -->
    <div id="toastContainer" style="position: fixed; bottom: 20px; right: 20px; z-index: 1002;"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/swiper@9/swiper-bundle.min.js"></script>
    <script src="https://unpkg.com/photoswipe@5/dist/photoswipe.min.js"></script>

    <!-- User Session Scripts -->
    <script src="../user/session-sync.js"></script>
    <script src="../user/user-profile.js"></script>
    <script src="../user/auto-logout.js"></script>

    <script>
        // PHP Data
        const DEST_ID = <?php echo $destination_id; ?>;
        const DEST_NAME = "<?php echo addslashes($destination['name']); ?>";
        const DEST_LOCATION = "<?php echo addslashes($destination['location']); ?>";
        const DEST_LAT = <?php echo !empty($destination['latitude']) ? (float)$destination['latitude'] : 'null'; ?>;
        const DEST_LNG = <?php echo !empty($destination['longitude']) ? (float)$destination['longitude'] : 'null'; ?>;
        const IS_LOGGED_IN = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
        const BASE_URL = "<?php echo $base_url; ?>";
        const GOOGLE_API_KEY = "<?php echo $google_api_key; ?>";
        const GOOGLE_CSE_ID = "<?php echo $google_cse_id; ?>";
        const EXCHANGE_RATES = <?php echo json_encode($exchange_rates); ?>;

        function initDestinationMap() {
            if (DEST_LAT === null || DEST_LNG === null || typeof L === 'undefined') {
                return;
            }
            const mapElement = document.getElementById('destinationMap');
            if (!mapElement) {
                return;
            }
            const map = L.map(mapElement).setView([DEST_LAT, DEST_LNG], 11);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 18,
            }).addTo(map);
            L.marker([DEST_LAT, DEST_LNG]).addTo(map)
                .bindPopup(`<strong>${DEST_NAME}</strong><br>${DEST_LOCATION}`)
                .openPopup();
        }
        initDestinationMap();

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

        // Debounced scroll handler
        let scrollTimeout;

        function debounceScroll(callback, delay = 50) {
            return function() {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(callback, delay);
            };
        }

        // Navbar scroll effect and Back to Top
        window.addEventListener('scroll', debounceScroll(() => {
            const navbar = document.getElementById('navbar');
            const backToTop = document.getElementById('backToTop');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
                backToTop.classList.add('show');
            } else {
                navbar.classList.remove('scrolled');
                backToTop.classList.remove('show');
            }
        }));

        // Back to Top functionality
        document.getElementById('backToTop').addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Mobile menu toggle
        const mobileToggle = document.getElementById('mobileMenuToggle');
        const leftPanel = document.getElementById('leftPanel');
        if (mobileToggle && leftPanel) {
            mobileToggle.addEventListener('click', () => {
                leftPanel.classList.toggle('open');
            });
            // Close panel when clicking outside
            document.addEventListener('click', (e) => {
                if (window.innerWidth <= 992 && leftPanel.classList.contains('open') &&
                    !leftPanel.contains(e.target) && !mobileToggle.contains(e.target)) {
                    leftPanel.classList.remove('open');
                }
            });
        }

        // Scroll Progress - debounced
        window.addEventListener('scroll', debounceScroll(() => {
            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = height > 0 ? (winScroll / height) * 100 : 0;
            document.getElementById('scrollProgress').style.width = scrolled + '%';
        }));

        // Read More
        const readMoreBtn = document.getElementById('readMoreBtn');
        const desc = document.getElementById('desc');
        if (readMoreBtn) {
            readMoreBtn.addEventListener('click', () => {
                desc.classList.toggle('open');
                readMoreBtn.textContent = desc.classList.contains('open') ? 'Read less' : 'Read more';
            });
        }

        // Swiper
        const swiper = new Swiper("#mainSwiper", {
            loop: true,
            autoplay: {
                delay: 3500,
                disableOnInteraction: false
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev'
            }
        });

        // PhotoSwipe for Thumbnails
        const thumbs = document.querySelectorAll('#thumbGrid img');
        thumbs.forEach(img => {
            img.addEventListener('click', () => {
                const items = [{
                    src: img.dataset.full || img.src,
                    w: 1200,
                    h: 800,
                    title: DEST_NAME
                }];
                const gallery = new PhotoSwipe({
                    dataSource: items
                });
                gallery.init();
            });
        });

        // PhotoSwipe for All Gallery Images (from table)
        function openPhotoSwipe(imageIndex) {
            const allImages = document.querySelectorAll('.gallery-images-table img');
            const items = Array.from(allImages).map((img, idx) => ({
                src: img.src,
                w: 1200,
                h: 800,
                title: DEST_NAME + ' - Image ' + (idx + 1)
            }));

            const gallery = new PhotoSwipe({
                dataSource: items,
                index: imageIndex
            });
            gallery.init();
        }

        // Cuisine Modal Functions with ESC key support
        function openCuisineModal(imgSrc) {
            const modal = document.getElementById('cuisineModal');
            const modalImg = document.getElementById('cuisineModalImg');
            modalImg.src = imgSrc;
            modal.classList.add('active');
            document.addEventListener('keydown', closeOnEsc);
        }

        function closeCuisineModal() {
            document.getElementById('cuisineModal').classList.remove('active');
            document.removeEventListener('keydown', closeOnEsc);
        }

        function closeOnEsc(e) {
            if (e.key === 'Escape') closeCuisineModal();
        }

        // Review Modal
        function openWriteReviewModal() {
            document.getElementById('writeReviewModal').style.display = 'flex';
        }

        function closeWriteReviewModal() {
            document.getElementById('writeReviewModal').style.display = 'none';
        }
        document.getElementById('reviewSubmitForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const response = await fetch('../user/add_review.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    closeWriteReviewModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.message || 'Failed to submit review', 'error');
                }
            } catch (err) {
                showToast('Network error. Please try again.', 'error');
            }
        });

        // Toast notification function
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i> ${message}`;
            container.appendChild(toast);
            setTimeout(() => toast.classList.add('show'), 10);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Favorite Button
        const favBtn = document.getElementById('favBtn');
        let isFavorite = <?php echo $is_favorite ? 'true' : 'false'; ?>;

        function updateFavUI() {
            favBtn.classList.toggle('active', isFavorite);
            favBtn.querySelector('i').className = isFavorite ? 'fas fa-heart' : 'far fa-heart';
            favBtn.querySelector('span').textContent = isFavorite ? 'Favorited' : 'Save';
            favBtn.setAttribute('aria-label', isFavorite ? 'Remove from favorites' : 'Add to favorites');
        }

        favBtn.addEventListener('click', async () => {
            if (!IS_LOGGED_IN) {
                showToast('Please login to save favorites', 'warning');
                return;
            }
            const action = isFavorite ? 'remove' : 'add';
            const response = await fetch('../actions/toggle_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `destination_id=${DEST_ID}&action=${action}`
            });
            const data = await response.json();
            if (data.status === 'success') {
                isFavorite = !isFavorite;
                updateFavUI();
                showToast(isFavorite ? 'Added to favorites!' : 'Removed from favorites', 'success');
            } else {
                showToast(data.message || 'Failed to update favorite', 'error');
            }
        });

        // Share Button
        document.getElementById('shareBtn').addEventListener('click', async () => {
            if (navigator.share) {
                await navigator.share({
                    title: DEST_NAME,
                    url: window.location.href
                });
            } else {
                navigator.clipboard.writeText(window.location.href);
                showToast('Link copied to clipboard!', 'success');
            }
        });

        // Plan Trip
        document.getElementById('planBtn').addEventListener('click', () => {
            let trips = JSON.parse(localStorage.getItem('tripmate_itinerary') || '[]');
            if (!trips.includes(DEST_ID)) {
                trips.push(DEST_ID);
                localStorage.setItem('tripmate_itinerary', JSON.stringify(trips));
                showToast('Added to your trip planner!', 'success');
            } else {
                showToast('Already in your trip planner.', 'info');
            }
        });

        // Exchange Rate Converter
        const amountInput = document.getElementById('amountInput');
        const currencySelect = document.getElementById('currencySelect');
        const convertedResult = document.getElementById('convertedResult');

        function updateExchangeRate() {
            const amount = parseFloat(amountInput.value) || 0;
            const currency = currencySelect.value;
            const rate = EXCHANGE_RATES[currency] || 0.012;
            const converted = amount * rate;
            const symbol = currency === 'USD' ? '$' : currency === 'EUR' ? '€' : '£';
            convertedResult.textContent = `≈ ${symbol} ${converted.toFixed(2)}`;
        }
        amountInput?.addEventListener('input', updateExchangeRate);
        currencySelect?.addEventListener('change', updateExchangeRate);
        updateExchangeRate();

        // Weather fallback
        async function loadWeatherFallback() {
            if (!DEST_LAT || !DEST_LNG) return;
            const container = document.getElementById('weatherContent');
            const existingWidget = container.querySelector('.weather-widget');
            if (existingWidget) return;
            try {
                const res = await fetch(`https://api.openweathermap.org/data/2.5/weather?lat=${DEST_LAT}&lon=${DEST_LNG}&units=metric&appid=b4fe517a83b0e5679af65062c7fd92cd`);
                const data = await res.json();
                if (data.main) {
                    container.innerHTML = `
                        <div class="weather-widget">
                            <div class="weather-current">
                                <div><div class="weather-temp">${Math.round(data.main.temp)}°C</div><div>${data.weather[0].description}</div></div>
                                <div style="font-size: 2rem;">${data.weather[0].icon === '01d' ? '☀️' : data.weather[0].icon === '02d' ? '⛅' : '☁️'}</div>
                            </div>
                            <div class="weather-details">
                                <div class="weather-detail">💧 ${data.main.humidity}%</div>
                                <div class="weather-detail">💨 ${Math.round(data.wind.speed)} km/h</div>
                            </div>
                        </div>
                    `;
                }
            } catch (e) {
                if (container.innerHTML.includes('Loading live weather')) {
                    container.innerHTML = '<p class="text-muted">Weather unavailable</p>';
                }
            }
        }
        if (!document.querySelector('#weatherContent .weather-widget')) loadWeatherFallback();

        // Local Time
        function updateTime() {
            document.getElementById('localTime').textContent = new Date().toLocaleTimeString();
        }
        updateTime();
        setInterval(updateTime, 1000);

        // Gallery Tabs
        window.switchTab = function(tab) {
            document.querySelectorAll('.gallery-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.gallery-section').forEach(s => s.classList.remove('active'));
            if (tab === 'local') {
                document.querySelector('.gallery-tab:first-child').classList.add('active');
                document.getElementById('localGallery').classList.add('active');
            } else {
                document.querySelector('.gallery-tab:last-child').classList.add('active');
                document.getElementById('googleGallery').classList.add('active');
                if (!window.googleImagesLoaded) loadGoogleImages();
            }
        };

        async function loadGoogleImages() {
            const container = document.getElementById('googleImages');
            if (!GOOGLE_API_KEY || !GOOGLE_CSE_ID) {
                container.innerHTML = '<p class="text-muted"><i class="fas fa-exclamation-triangle"></i> Google Images not configured.</p>';
                return;
            }
            container.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i><p>Fetching images...</p></div>';
            try {
                const response = await fetch(`https://www.googleapis.com/customsearch/v1?q=${encodeURIComponent(DEST_NAME + ' ' + DEST_LOCATION + ' travel')}&cx=${GOOGLE_CSE_ID}&key=${GOOGLE_API_KEY}&searchType=image&num=12`);
                const data = await response.json();
                if (data.items && data.items.length > 0) {
                    container.innerHTML = data.items.map(item => `<img src="${item.link}" alt="${item.title || DEST_NAME}" onclick="openImage('${item.link}')" loading="lazy">`).join('');
                    window.googleImagesLoaded = true;
                } else {
                    container.innerHTML = `<div style="text-align: center; padding: 20px;"><i class="fas fa-google" style="font-size: 2rem; color: var(--primary);"></i><p class="text-muted">No images found</p><a href="https://www.google.com/search?q=${encodeURIComponent(DEST_NAME + ' travel')}&tbm=isch" target="_blank" class="btn-icon btn-primary" style="margin-top: 10px;">Search on Google Images</a></div>`;
                }
            } catch (error) {
                console.error('Google Images error:', error);
                container.innerHTML = `<div style="text-align: center; padding: 20px;"><i class="fas fa-image" style="font-size: 2rem;"></i><p class="text-muted">Unable to load images</p><a href="https://www.google.com/search?q=${encodeURIComponent(DEST_NAME + ' travel')}&tbm=isch" target="_blank" class="btn-icon btn-primary" style="margin-top: 10px;">Search on Google Images</a></div>`;
            }
        }

        window.openImage = (src) => {
            const items = [{
                src: src,
                w: 1200,
                h: 800
            }];
            const gallery = new PhotoSwipe({
                dataSource: items
            });
            gallery.init();
        };

        // Hotel Filters and Sorting
        let currentHotels = <?php echo json_encode($all_hotels); ?>;

        function filterAndSortHotels() {
            let filtered = [...currentHotels];
            const priceMin = parseFloat(document.getElementById('priceMin')?.value) || 0;
            const priceMax = parseFloat(document.getElementById('priceMax')?.value) || Infinity;
            const typeFilter = document.getElementById('hotelTypeFilter')?.value || 'all';
            const sortBy = document.getElementById('hotelSortBy')?.value || 'price_asc';

            filtered = filtered.filter(h => h.price_per_night >= priceMin && h.price_per_night <= priceMax);
            if (typeFilter !== 'all') filtered = filtered.filter(h => h.hotel_type === typeFilter);

            if (sortBy === 'price_asc') filtered.sort((a, b) => a.price_per_night - b.price_per_night);
            else if (sortBy === 'price_desc') filtered.sort((a, b) => b.price_per_night - a.price_per_night);
            else if (sortBy === 'rating_desc') filtered.sort((a, b) => b.hotel_rating - a.hotel_rating);

            renderHotels(filtered);
        }

        function renderHotels(hotels) {
            const container = document.getElementById('hotelsContainer');
            if (!container) return;
            if (!hotels.length) {
                container.innerHTML = '<p class="text-muted">No hotels match your filters.</p>';
                return;
            }
            container.innerHTML = hotels.map(hotel => `
                <div class="hotel-item">
                    <div class="hotel-header" onclick="toggleHotel(${hotel.id})">
                        <div class="hotel-info">
                            <img src="${hotel.image_url_clean}" class="hotel-thumb" onerror="this.src='${BASE_URL}/images/hotel-placeholder.jpg'" loading="lazy">
                            <div>
                                <div class="hotel-name">${escapeHtml(hotel.hotel_name)}</div>
                                <div class="hotel-price">₹${Number(hotel.price_per_night).toLocaleString()} <span style="font-size: 0.75rem;">/ night</span></div>
                                <div class="hotel-rating"><i class="fas fa-star"></i> ${hotel.hotel_rating} <span class="text-muted" style="font-size: 0.7rem;">(Excellent)</span></div>
                            </div>
                        </div>
                        <div><span class="hotel-badge ${hotel.hotel_type}">${hotel.hotel_type.charAt(0).toUpperCase() + hotel.hotel_type.slice(1)} Budget</span><i class="fas fa-chevron-down" id="hotelIcon_${hotel.id}" style="margin-left: 12px;"></i></div>
                    </div>
                    <div class="hotel-details" id="hotelDetails_${hotel.id}">
                        <div class="hotel-details-grid">
                            <div class="hotel-detail-section">
                                <h4><i class="fas fa-info-circle"></i> About This Hotel</h4>
                                <p>${hotel.description ? escapeHtml(hotel.description) : 'No description available.'}</p>
                                <h4><i class="fas fa-concierge-bell"></i> Amenities</h4>
                                <div class="amenities-list">${(hotel.amenities ? JSON.parse(hotel.amenities) : []).map(a => `<span class="amenity-tag"><i class="fas fa-check" style="color: #10b981; font-size: 0.7rem;"></i> ${escapeHtml(a)}</span>`).join('') || '<p class="text-muted">No amenities listed.</p>'}</div>
                            </div>
                            <div class="hotel-detail-section">
                                <h4><i class="fas fa-location-dot"></i> Location & Contact</h4>
                                ${hotel.address ? `<div class="info-row"><i class="fas fa-map-marker-alt"></i><span>${escapeHtml(hotel.address)}</span></div>` : ''}
                                ${hotel.contact_number ? `<div class="info-row"><i class="fas fa-phone"></i><span>${escapeHtml(hotel.contact_number)}</span></div>` : ''}
                                <div class="info-row"><i class="fas fa-clock"></i><span>Check-in: ${hotel.check_in_time ? hotel.check_in_time.substring(0,5) : '12:00'} | Check-out: ${hotel.check_out_time ? hotel.check_out_time.substring(0,5) : '11:00'}</span></div>
                                <div class="hotel-actions">
                                    <a href="book_hotel.php?hotel_id=${hotel.id}&destination_id=${DEST_ID}" class="btn-icon btn-primary" style="text-decoration: none;"><i class="fas fa-calendar-check"></i> Book Now</a>
                                    ${hotel.address ? `<a href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(hotel.address)}" target="_blank" class="btn-icon btn-secondary" style="text-decoration: none;"><i class="fas fa-directions"></i> Directions</a>` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        window.toggleHotel = (id) => {
            const details = document.getElementById(`hotelDetails_${id}`);
            const icon = document.getElementById(`hotelIcon_${id}`);
            if (details.style.display === 'none' || details.style.display === '') {
                document.querySelectorAll('.hotel-details').forEach(div => div.style.display = 'none');
                document.querySelectorAll('[id$="_icon"]').forEach(ic => ic.style.transform = 'rotate(0deg)');
                details.style.display = 'block';
                icon.style.transform = 'rotate(180deg)';
            } else {
                details.style.display = 'none';
                icon.style.transform = 'rotate(0deg)';
            }
        };

        // Initialize hotel filters if hotels exist
        if (currentHotels && currentHotels.length) {
            document.getElementById('priceMin')?.addEventListener('input', filterAndSortHotels);
            document.getElementById('priceMax')?.addEventListener('input', filterAndSortHotels);
            document.getElementById('hotelTypeFilter')?.addEventListener('change', filterAndSortHotels);
            document.getElementById('hotelSortBy')?.addEventListener('change', filterAndSortHotels);
            filterAndSortHotels();
        } else {
            const container = document.getElementById('hotelsContainer');
            if (container) container.innerHTML = '<p class="text-muted">No hotels available for this destination yet.</p>';
        }

        // Budget Package
        const hotelsByType = <?php echo json_encode($hotels_by_type); ?>;
        const flightsByType = <?php echo json_encode($flights_by_type); ?>;
        let selectedBudget = null;
        let selectedHotel = null;
        let selectedFlight = null;

        window.selectBudget = (budget) => {
            selectedBudget = budget;
            document.querySelectorAll('.budget-btn').forEach(btn => btn.classList.remove('selected'));
            document.getElementById(`budget${budget.charAt(0).toUpperCase() + budget.slice(1)}Btn`).classList.add('selected');
            document.getElementById('tripForm').classList.add('visible');
            document.getElementById('packageDetails').classList.remove('visible');
        };

        window.calculateTotal = () => {
            const travelers = parseInt(document.getElementById('travelers').value) || 1;
            const days = parseInt(document.getElementById('days').value) || 1;
            const departureCity = document.getElementById('departureCity').value;
            if (!selectedBudget) {
                showToast('Select a budget first', 'warning');
                return;
            }
            if (!departureCity) {
                showToast('Select departure city', 'warning');
                return;
            }
            const availableHotels = hotelsByType[selectedBudget] || [];
            const availableFlights = flightsByType[selectedBudget]?.filter(f => f.departure_city === departureCity) || [];
            if (!availableHotels.length) {
                showToast('No hotels available', 'warning');
                return;
            }
            if (!availableFlights.length) {
                showToast('No flights available', 'warning');
                return;
            }
            selectedHotel = availableHotels[0];
            selectedFlight = availableFlights[0];
            displayPackage(travelers, days);
        };

        function displayPackage(travelers, days) {
            const hotelsContainer = document.getElementById('hotelsContainer');
            if (hotelsContainer) {
                hotelsContainer.innerHTML = (hotelsByType[selectedBudget] || []).map(hotel => `
                    <div class="hotel-option ${selectedHotel && selectedHotel.id === hotel.id ? 'selected' : ''}" onclick="selectHotelOption(${hotel.id})">
                        <strong>${escapeHtml(hotel.hotel_name)}</strong><br>
                        ₹${Number(hotel.price_per_night).toLocaleString()}/night<br>
                        <small>⭐ ${hotel.hotel_rating}</small>
                    </div>
                `).join('');
            }
            const flightsContainer = document.getElementById('flightsContainer');
            if (flightsContainer) {
                flightsContainer.innerHTML = (flightsByType[selectedBudget] || []).filter(f => f.departure_city === document.getElementById('departureCity').value).map(flight => `
                    <div class="flight-option ${selectedFlight && selectedFlight.id === flight.id ? 'selected' : ''}" onclick="selectFlightOption(${flight.id})">
                        <strong>${escapeHtml(flight.airline)}</strong><br>
                        ₹${Number(flight.price_per_person).toLocaleString()}<br>
                        <small>${flight.duration_hours}h • ${flight.stops} stops</small>
                    </div>
                `).join('');
            }
            updateTotal(travelers, days);
            document.getElementById('packageDetails').classList.add('visible');
        }

        window.selectHotelOption = (id) => {
            selectedHotel = hotelsByType[selectedBudget].find(h => h.id === id);
            displayPackage(parseInt(document.getElementById('travelers').value) || 1, parseInt(document.getElementById('days').value) || 1);
        };
        window.selectFlightOption = (id) => {
            selectedFlight = flightsByType[selectedBudget].find(f => f.id === id);
            displayPackage(parseInt(document.getElementById('travelers').value) || 1, parseInt(document.getElementById('days').value) || 1);
        };

        function updateTotal(travelers, days) {
            if (!selectedHotel || !selectedFlight) return;
            const hotelCost = selectedHotel.price_per_night * days;
            const flightCost = selectedFlight.price_per_person * travelers;
            const total = (hotelCost * travelers) + flightCost;
            const totalEl = document.getElementById('totalCost');
            if (totalEl) totalEl.innerHTML = `<strong>Total Package Cost</strong><br>Hotel: ₹${(hotelCost * travelers).toLocaleString()}<br>Flights: ₹${flightCost.toLocaleString()}<br><span style="font-size: 1.2rem;">Total: ₹${total.toLocaleString()}</span>`;
        }

        window.bookPackage = () => {
            if (!selectedHotel || !selectedFlight) {
                showToast('Select hotel and flight', 'warning');
                return;
            }
            if (!IS_LOGGED_IN) {
                showToast('Please login to book', 'warning');
                return;
            }
            const travelers = document.getElementById('travelers').value;
            const days = document.getElementById('days').value;
            window.location.href = `../booking/package_booking.php?destination_id=${DEST_ID}&hotel_id=${selectedHotel.id}&flight_id=${selectedFlight.id}&travelers=${travelers}&days=${days}`;
        };

        // Smooth scroll for TOC
        document.querySelectorAll('.toc-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(link.getAttribute('href'));
                if (target) target.scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Active TOC highlight on scroll - debounced
        const sections = document.querySelectorAll('.card[id], .budget-section[id]');
        const navLinks = document.querySelectorAll('.toc-link');
        window.addEventListener('scroll', debounceScroll(() => {
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 150;
                const sectionBottom = sectionTop + section.offsetHeight;
                if (window.scrollY >= sectionTop && window.scrollY < sectionBottom) current = section.getAttribute('id');
            });
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}`) link.classList.add('active');
            });
        }));

        // Profile dropdown
        const profileBtnNav = document.getElementById('profileBtnNav');
        const profileDropdownNav = document.getElementById('profileDropdownNav');
        if (profileBtnNav && profileDropdownNav) {
            profileBtnNav.addEventListener('click', (e) => {
                e.stopPropagation();
                profileDropdownNav.classList.toggle('active');
            });
            document.addEventListener('click', () => profileDropdownNav.classList.remove('active'));
            profileDropdownNav.addEventListener('click', (e) => e.stopPropagation());
        }

        // Logout
        const logoutBtnNav = document.getElementById('logoutBtnNav');
        if (logoutBtnNav) {
            logoutBtnNav.addEventListener('click', () => {
                if (confirm('Are you sure you want to logout?')) {
                    sessionStorage.clear();
                    localStorage.removeItem('tripmate_active_user_id');
                    localStorage.removeItem('tripmate_active_user_name');
                    fetch('../auth/logout.php', {
                        method: 'POST',
                        keepalive: true
                    }).catch(console.error);
                    window.location.href = '../main/index.html';
                }
            });
        }
    </script>

    <script type="application/ld+json">
        <?php echo json_encode($structured_data, JSON_UNESCAPED_SLASHES); ?>
    </script>
    <script type="application/ld+json">
        <?php echo json_encode($article_schema, JSON_UNESCAPED_SLASHES); ?>
    </script>
    <script type="application/ld+json">
        <?php echo json_encode($breadcrumb_schema, JSON_UNESCAPED_SLASHES); ?>
    </script>
</body>

</html>