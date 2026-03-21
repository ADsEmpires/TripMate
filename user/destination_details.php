<?php
session_start();
include '../database/dbconfig.php';

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
$og_image = ''; // resolved below after image checks

// --- Resolve images (support JSON or CSV) ---
$base_url = '/test/tripmate'; // adjust to your deployment
function decode_images_field($field)
{
    if (empty($field)) return [];
    $trim = trim($field);
    if (strpos($trim, '[') === 0) {
        $arr = json_decode($trim, true);
        return is_array($arr) ? $arr : [];
    }
    // CSV fallback
    return array_filter(array_map('trim', explode(',', $field)));
}

$images = decode_images_field($destination['image_urls']);
function resolve_image_path($base_url, $filename, $subdir = '/uploads/')
{
    $filename = trim($filename);
    if (empty($filename)) return $base_url . '/images/no-image.jpg';
    $server_path = $_SERVER['DOCUMENT_ROOT'] . $base_url . $subdir . $filename;
    if (file_exists($server_path) && !is_dir($server_path)) {
        return $base_url . $subdir . $filename;
    }
    // try uploads root
    $server_path2 = $_SERVER['DOCUMENT_ROOT'] . $base_url . '/uploads/' . $filename;
    if (file_exists($server_path2) && !is_dir($server_path2)) {
        return $base_url . '/uploads/' . $filename;
    }
    return $base_url . '/images/no-image.jpg';
}

// --- Pick cover image and OG image ---
$coverImage = $base_url . '/images/no-image.jpg';
if (!empty($images)) {
    foreach ($images as $img) {
        $p = resolve_image_path($base_url, $img);
        if ($p !== $base_url . '/images/no-image.jpg') {
            $coverImage = $p;
            break;
        }
    }
    if ($coverImage === $base_url . '/images/no-image.jpg') {
        $coverImage = resolve_image_path($base_url, $images[0]);
    }
}
$og_image = $coverImage;

// --- Read advanced JSON fields safely ---
$tips = isset($destination['tips']) ? $destination['tips'] : '[]';
$cuisines_raw = isset($destination['cuisines']) ? $destination['cuisines'] : '[]';
$language = isset($destination['language']) ? $destination['language'] : '[]';
$cuisine_images = isset($destination['cuisine_images']) ? json_decode($destination['cuisine_images'], true) : [];

// Normalize cuisines into array (supports JSON array or CSV string)
function normalize_list_field($raw)
{
    if (empty($raw)) return [];
    $trim = trim($raw);
    if (strpos($trim, '[') === 0) {
        $arr = json_decode($trim, true);
        return is_array($arr) ? array_values($arr) : [];
    }
    // CSV fallback
    return array_values(array_filter(array_map('trim', explode(',', $raw))));
}
$cuisines = normalize_list_field($cuisines_raw);

// --- Favorite state (server-side) ---
$is_favorite = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $fav_stmt = $conn->prepare("SELECT id FROM user_history WHERE user_id = ? AND activity_type = 'favorite' AND JSON_EXTRACT(activity_details, '$.id') = ?");
    $fav_stmt->bind_param("ii", $user_id, $destination_id);
    $fav_stmt->execute();
    $is_favorite = $fav_stmt->get_result()->num_rows > 0;
    $fav_stmt->close();
}

// --- Ratings & reviews (simple aggregation) ---
// Expect a simple reviews table: reviews(id, destination_id, user_id, rating, comment, images_json, created_at)
$average_rating = 0.0;
$reviews_count = 0;
$reviews = [];
$rev_stmt = $conn->prepare("SELECT r.*, u.name as user_name FROM reviews r LEFT JOIN users u ON u.id = r.user_id WHERE r.destination_id = ? ORDER BY r.created_at DESC LIMIT 50");
$rev_stmt->bind_param("i", $destination_id);
$rev_stmt->execute();
$res = $rev_stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $reviews[] = $row;
}
$rev_stmt->close();
if (!empty($reviews)) {
    $sum = 0;
    foreach ($reviews as $r) $sum += (float)$r['rating'];
    $average_rating = round($sum / count($reviews), 1);
    $reviews_count = count($reviews);
}

// --- Structured data (JSON-LD) ---
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
    "url" => (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"
];

// --- Add CSP header for a reasonable baseline (allowing external libs used below) ---
header("Content-Security-Policy: default-src 'self' https:; script-src 'self' 'unsafe-inline' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: https:; connect-src 'self' https:");

// ---------- Output HTML ----------
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo $og_title; ?></title>
    <meta name="description" content="<?php echo $og_description; ?>">

    <!-- Open Graph tags (dynamic) -->
    <meta property="og:title" content="<?php echo $og_title; ?>">
    <meta property="og:description" content="<?php echo $og_description; ?>">
    <meta property="og:image" content="<?php echo $og_image; ?>">
    <meta property="og:type" content="website">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $og_title; ?>">
    <meta name="twitter:description" content="<?php echo $og_description; ?>">
    <meta name="twitter:image" content="<?php echo $og_image; ?>">

    <!-- Icons & libs -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <!-- Swiper (carousel) -->
    <link rel="stylesheet" href="https://unpkg.com/swiper@9/swiper-bundle.min.css" />

    <!-- PhotoSwipe (lightbox) v5 (modern) -->
    <link rel="stylesheet" href="https://unpkg.com/photoswipe@5/dist/photoswipe.css">

    <!-- User Profile CSS -->
    <link rel="stylesheet" href="../user/user-profile.css">

    <!-- Basic styles & Ken Burns / layout -->
    <style>
        :root {
            --accent: #ff6600;
            --primary: #16034f;
            --muted: #6b7280;
            --card: #fff;
        }

        * {
            box-sizing: border-box
        }

        html,
        body {
            height: 100%
        }

        body {
            margin: 0;
            font-family: Inter, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #f7f8fc 55%, #e9f0fb 100%);
            color: #222;
            line-height: 1.45
        }

        a {
            color: var(--accent);
            text-decoration: none
        }

        /* Make room for fixed navbar; use safe spacing that adapts to small screens */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: calc(64px + 24px) 16px 48px;
            /* top padding includes navbar height */
        }

        /* Navbar */
        .navbar {
            position: fixed;
            left: 0;
            right: 0;
            top: 0;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            background: linear-gradient(90deg, rgba(22, 3, 79, 0.96), rgba(16, 2, 82, 0.96));
            color: #fff;
            z-index: 1200
        }

        .navbar .logo {
            display: flex;
            gap: 10px;
            align-items: center;
            font-weight: 700
        }

        .navbar a.btn {
            background: var(--accent);
            padding: 8px 12px;
            border-radius: 8px;
            color: #fff;
            font-weight: 600
        }

        /* Hero / cover with Ken Burns (slow zoom) */
        .hero {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 14px 44px rgba(16, 2, 82, 0.12)
        }

        .hero .media {
            position: relative;
            height: clamp(220px, 40vh, 520px);
            overflow: hidden
        }

        .hero .media img,
        .hero .media video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transform-origin: center center;
            will-change: transform;
            transition: transform 0.3s ease;
            border-radius: 12px
        }

        .hero .kenburns {
            animation: kenburns 20s ease-in-out infinite alternate;
        }

        @keyframes kenburns {
            0% {
                transform: scale(1) translateY(0px);
                filter: brightness(.96);
            }

            100% {
                transform: scale(1.06) translateY(-4px);
                filter: brightness(.92);
            }
        }

        .hero .overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(6, 6, 24, 0.12) 0%, rgba(6, 6, 24, 0.28) 40%, rgba(6, 6, 24, 0.45) 100%);
            pointer-events: none;
        }

        .hero .badges {
            position: absolute;
            left: 14px;
            top: 14px;
            display: flex;
            gap: 8px;
            z-index: 6
        }

        .badge {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            padding: 6px 10px;
            border-radius: 10px;
            backdrop-filter: blur(6px);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            border: 1px solid rgba(255, 255, 255, 0.06);
            font-size: 0.9rem
        }

        .hero .hero-title {
            position: absolute;
            left: 14px;
            bottom: 14px;
            color: #fff;
            z-index: 6;
            max-width: 75%
        }

        .hero .hero-title h1 {
            margin: 0;
            font-size: clamp(1.1rem, 2.1vw, 2rem);
            letter-spacing: 0.4px
        }

        .hero .hero-title p {
            margin: 6px 0 0 0;
            color: rgba(255, 255, 255, 0.95);
            font-size: 0.95rem
        }

        .mini-card {
            position: sticky;
            top: 78px;
            margin-top: -30px;
            margin-left: auto;
            margin-right: auto;
            max-width: min(1100px, calc(100% - 32px));
            background: linear-gradient(180deg, #fff, #fbfbff);
            padding: 12px 14px;
            border-radius: 10px;
            box-shadow: 0 8px 28px rgba(16, 2, 82, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            z-index: 1100;
            border: 1px solid rgba(16, 2, 82, 0.04);
        }

        .mini-left {
            display: flex;
            gap: 12px;
            align-items: center;
            min-width: 0
        }

        .mini-left img {
            width: 56px;
            height: 56px;
            object-fit: cover;
            border-radius: 8px;
            flex-shrink: 0
        }

        .rating {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--primary);
            font-weight: 700
        }

        .actions {
            display: flex;
            gap: 8px;
            align-items: center
        }

        .btn-small {
            padding: 8px 10px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.95rem
        }

        .btn-fav {
            background: transparent;
            border: 1px solid var(--accent);
            color: var(--accent)
        }

        .btn-book {
            background: var(--accent);
            color: #fff
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 20px;
            margin-top: 16px;
        }

        @media (max-width:1100px) {
            .grid {
                grid-template-columns: 1fr 300px
            }
        }

        @media (max-width:980px) {
            .grid {
                grid-template-columns: 1fr
            }
        }

        .card {
            background: #fff;
            padding: 16px;
            border-radius: 10px;
            box-shadow: 0 8px 30px rgba(16, 2, 82, 0.06)
        }

        .section-title {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px
        }

        .cuisines-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center
        }

        .cuisine-chip {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid #eef4ff;
            box-shadow: 0 6px 18px rgba(22, 3, 79, 0.03);
            font-weight: 600;
            color: var(--primary)
        }

        .cuisine-chip img {
            width: 28px;
            height: 28px;
            object-fit: cover;
            border-radius: 6px;
            flex-shrink: 0
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px
        }

        .gallery-grid img {
            width: 100%;
            height: clamp(88px, 14vw, 140px);
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform .18s ease, box-shadow .18s ease;
            filter: blur(6px);
            transform: scale(1.01)
        }

        .gallery-grid img[data-ready="1"] {
            filter: none;
            transform: none
        }

        .gallery-grid img:hover {
            transform: translateY(-6px);
            box-shadow: 0 10px 30px rgba(16, 2, 82, 0.12)
        }

        @media (max-width:980px) {
            .gallery-grid {
                grid-template-columns: repeat(2, 1fr)
            }
        }

        .swiper {
            width: 100%;
            border-radius: 8px;
            overflow: hidden
        }

        .swiper .swiper-slide img {
            width: 100%;
            height: clamp(200px, 28vh, 360px);
            object-fit: cover;
            display: block
        }

        #map {
            height: 300px;
            border-radius: 8px;
            overflow: hidden
        }

        .map-container {
            width: 100%;
            height: 300px;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }

        .google-map-frame {
            width: 100%;
            height: 100%;
            border: 0;
        }

        @media (max-width:640px) {
            #map {
                height: 220px
            }
        }

        .review {
            border-radius: 8px;
            padding: 10px;
            background: #fbfdff;
            border: 1px solid #eef6ff;
            display: flex;
            gap: 10px;
            align-items: flex-start
        }

        .review .meta {
            font-weight: 700;
            color: var(--primary)
        }

        .review .text {
            color: #333
        }

        .collapsible {
            max-height: 120px;
            overflow: hidden;
            transition: max-height .28s ease
        }

        .collapsible.open {
            max-height: 1200px
        }

        .toc {
            position: sticky;
            top: 110px;
            padding: 10px;
            border-radius: 8px;
            background: #fff;
            border: 1px solid #f1f2f8
        }

        .toc a {
            display: block;
            margin-bottom: 8px;
            color: var(--muted);
            font-weight: 600
        }

        /* Weather Widget Enhanced Styles */
        .weather-widget {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 16px;
            color: white;
            margin-top: 16px;
        }

        .weather-current {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .weather-temp {
            font-size: 2.5rem;
            font-weight: 700;
        }

        .weather-icon {
            font-size: 3rem;
        }

        .weather-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .weather-detail-item {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 10px;
            text-align: center;
        }

        .weather-detail-item i {
            font-size: 1.2rem;
            margin-bottom: 4px;
        }

        .weather-detail-item span {
            display: block;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .weather-detail-item strong {
            display: block;
            font-size: 1.2rem;
            margin-top: 4px;
        }

        .weather-forecast {
            display: flex;
            overflow-x: auto;
            gap: 12px;
            padding: 8px 0;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.5) transparent;
        }

        .weather-forecast::-webkit-scrollbar {
            height: 4px;
        }

        .weather-forecast::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.5);
            border-radius: 4px;
        }

        .forecast-day {
            min-width: 80px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 10px;
            text-align: center;
        }

        .forecast-day .day {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .forecast-day .temp {
            font-size: 1rem;
            font-weight: 700;
        }

        .forecast-day .condition {
            font-size: 0.8rem;
            opacity: 0.9;
        }

        .forecast-day i {
            font-size: 1.5rem;
            margin: 4px 0;
        }

        .weather-loading,
        .weather-error {
            text-align: center;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 8px;
            color: #666;
        }

        /* Google Images Gallery */
        .google-images-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-top: 16px;
        }

        .google-images-grid img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 6px;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .google-images-grid img:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .image-source-badge {
            font-size: 0.7rem;
            padding: 2px 6px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 4px;
            position: absolute;
            bottom: 4px;
            right: 4px;
            color: #666;
        }

        .gallery-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            border-bottom: 2px solid #eef4ff;
            padding-bottom: 10px;
        }

        .gallery-tab {
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .gallery-tab.active {
            background: var(--accent);
            color: white;
        }

        .gallery-tab:not(.active):hover {
            background: #eef4ff;
        }

        .gallery-section {
            display: none;
        }

        .gallery-section.active {
            display: block;
        }

        @media (max-width: 768px) {
            .google-images-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 480px) {
            .google-images-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

                /* Budget Package Section Styles */
        .budget-section-wrapper {
            margin-top: 2rem;
        }

        .budget-container {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(22, 3, 79, 0.1);
            padding: 1.5rem;
        }

        .budget-section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #16034f;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .budget-section-icon {
            color: #ff6600;
        }

        .budget-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .budget-btn {
            flex: 1;
            min-width: 150px;
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 700;
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
        }

        .budget-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .budget-btn-low {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .budget-btn-medium {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .budget-btn-high {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .budget-btn-price {
            font-size: 0.875rem;
            opacity: 0.9;
            display: block;
        }

        .budget-btn.ring-4 {
            box-shadow: 0 0 0 4px #ff6600;
            transform: scale(1.05);
        }

        /* Trip Details Form */
        .trip-details-form {
            background: #f9fafb;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 2px solid #ff6600;
            transition: all 0.3s ease;
        }

        .trip-details-form.hidden {
            display: none;
        }

        .trip-details-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #16034f;
            margin-bottom: 1rem;
        }

        .trip-details-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        @media (min-width: 768px) {
            .trip-details-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .trip-detail-field {
            display: flex;
            flex-direction: column;
        }

        .trip-detail-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #4b5563;
            margin-bottom: 0.5rem;
        }

        .trip-detail-input {
            width: 100%;
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
            color: #111827;
            background: white;
        }

        .trip-detail-input:focus {
            outline: none;
            border-color: #ff6600;
            box-shadow: 0 0 0 2px rgba(255, 102, 0, 0.1);
        }

        .calculate-btn {
            background: #ff6600;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 0.5rem;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .calculate-btn:hover {
            background: #e65c00;
        }

        /* Package Details */
        .package-details {
            display: block;
        }

        .package-details.hidden {
            display: none;
        }

        .budget-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            color: white;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .package-section {
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .package-section-header {
            padding: 0.75rem 1.5rem;
            font-weight: 700;
            color: white;
        }

        .package-section-header-blue {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }

        .package-section-header-purple {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }

        .package-grid {
            padding: 1rem;
            background: #f9fafb;
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        @media (min-width: 768px) {
            .package-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Hotel and Flight Cards */
        .package-grid > div {
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1rem;
            background: white;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .package-grid > div:hover {
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .package-grid > div.border-4 {
            border: 4px solid #ff6600;
            background: #fff7f0;
        }

        .hotel-card {
            display: flex;
            gap: 1rem;
        }

        .hotel-card-image {
            width: 6rem;
            height: 6rem;
            background: #e5e7eb;
            border-radius: 0.5rem;
            overflow: hidden;
            flex-shrink: 0;
        }

        .hotel-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hotel-card-content {
            flex: 1;
        }

        .hotel-card-title {
            font-weight: 700;
            color: #16034f;
            margin: 0 0 0.25rem 0;
        }

        .hotel-card-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.25rem;
        }

        .hotel-card-stars {
            color: #fbbf24;
        }

        .hotel-card-price {
            font-size: 0.875rem;
            color: #4b5563;
            margin-top: 0.5rem;
        }

        .hotel-card-badge {
            font-size: 0.75rem;
            background: #d1fae5;
            color: #059669;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            display: inline-block;
            margin-top: 0.5rem;
        }

        /* Flight Card */
        .flight-card {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }

        .flight-card-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .flight-card-icon {
            width: 3rem;
            height: 3rem;
            background: #e5e7eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .flight-card-icon i {
            font-size: 1.5rem;
            color: #ff6600;
        }

        .flight-card-info h4 {
            font-weight: 700;
            margin: 0;
        }

        .flight-card-info p {
            font-size: 0.875rem;
            color: #4b5563;
            margin: 0.25rem 0 0 0;
        }

        .flight-card-price {
            text-align: right;
        }

        .flight-card-price-amount {
            font-weight: 700;
            color: #ff6600;
            margin: 0;
        }

        .flight-card-price-details {
            font-size: 0.875rem;
            color: #4b5563;
            margin: 0.25rem 0 0 0;
        }

        .flight-card-tags {
            margin-top: 0.75rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .flight-card-tag {
            background: #f3f4f6;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
        }

        /* Total Cost Section */
        .total-cost-section {
            background: linear-gradient(135deg, #16034f, #2a0a8a);
            border-radius: 0.75rem;
            padding: 1.5rem;
        }

        .total-cost-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0 0 1rem 0;
            color: white;
        }

        .cost-breakdown {
            margin-bottom: 1rem;
        }

        .cost-breakdown > div {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .cost-breakdown span:first-child {
            color: #e5e7eb;
        }

        .cost-breakdown .font-bold {
            color: white;
            font-weight: 700;
        }

        .total-cost-amount {
            font-size: 1.875rem;
            font-weight: 700;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #818cf8;
            color: white;
        }

        .total-cost-amount .text-sm {
            font-size: 0.875rem;
            font-weight: 400;
            display: block;
            margin-top: 0.25rem;
            color: #fecaca;
        }

        .book-package-btn {
            width: 100%;
            background: #ff6600;
            color: white;
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .book-package-btn:hover {
            background: #e65c00;
            transform: scale(1.05);
        }
        /* Rest of your existing styles remain the same */
        /* ... (keep all your existing styles from line 348 to the end) ... */
    </style>
</head>

<body <?php echo (isset($_SESSION['user_id']) ? 'class="user-logged-in"' : ''); ?>>

    <!-- NAV -->
    <header class="navbar">
        <div class="logo"><i class="fas fa-compass"></i> <strong style="margin-left:6px">TripMate</strong></div>
        <div>
            <a class="btn" href="../search/search.html">Back to Search</a>
            <a class="btn" href="../user/user_dashboard.php">Dashboard</a>
        </div>
    </header>

    <main class="container" role="main">

        <!-- Hero / cover with Ken Burns -->
        <article class="hero" aria-labelledby="dest-title">
            <div class="media">
                <?php
                // Prefer video if provided (field 'promo_video' hypothetical), else show image
                $promo_video = $destination['promo_video'] ?? '';
                if (!empty($promo_video)) {
                    $video_path = resolve_image_path($base_url, $promo_video, '/uploads/videos/');
                    echo '<video class="kenburns" autoplay muted loop playsinline src="' . htmlspecialchars($video_path) . '" poster="' . htmlspecialchars($coverImage) . '"></video>';
                } else {
                    echo '<img class="kenburns" src="' . htmlspecialchars($coverImage) . '" alt="' . htmlspecialchars($destination['name']) . ' cover image" loading="eager">';
                }
                ?>
                <div class="overlay" aria-hidden="true"></div>

                <!-- CTA badges -->
                <div class="badges" aria-hidden="true">
                    <div class="badge"><i class="fas fa-star"></i> <?php echo $average_rating ? $average_rating : 'New'; ?> </div>
                    <div class="badge"><i class="fas fa-wallet"></i> ₹<?php echo number_format($destination['budget'] ?? 0); ?>/day</div>
                    <?php if (!empty($destination['travel_time'])): ?>
                        <div class="badge"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($destination['travel_time']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="hero-title">
                    <h1 id="dest-title"><?php echo htmlspecialchars($destination['name']); ?></h1>
                    <p><?php echo htmlspecialchars($destination['location']); ?></p>
                </div>
            </div>
        </article>

        <!-- Sticky mini-card with quick actions (optimistic favorites & share) -->
        <div class="mini-card" role="region" aria-label="Quick info">
            <div class="mini-left">
                <img src="<?php echo htmlspecialchars($coverImage); ?>" alt="<?php echo htmlspecialchars($destination['name']); ?> thumbnail" loading="lazy">
                <div style="min-width:0;overflow:hidden">
                    <div style="font-weight:800;white-space:nowrap;text-overflow:ellipsis;overflow:hidden"><?php echo htmlspecialchars($destination['name']); ?></div>
                    <div style="color:var(--muted);font-size:0.95rem;white-space:nowrap;text-overflow:ellipsis;overflow:hidden"><?php echo htmlspecialchars($destination['location']); ?></div>
                    <div style="margin-top:6px;display:flex;gap:8px;align-items:center">
                        <div class="rating"><i class="fas fa-star"></i> <?php echo $average_rating ? $average_rating : '—'; ?></div>
                        <div style="color:var(--muted)">• <?php echo $reviews_count; ?> reviews</div>
                    </div>
                </div>
            </div>
            <div class="actions" role="toolbar" aria-label="Actions">
                <button id="favBtnMini" class="btn-small btn-fav" aria-pressed="<?php echo $is_favorite ? 'true' : 'false'; ?>"><i class="fa<?php echo $is_favorite ? 's' : 'r'; ?> fa-heart"></i> <span id="favLabel"><?php echo $is_favorite ? 'Favorited' : 'Save'; ?></span></button>
                <button id="shareBtnMini" class="btn-small" title="Share"><i class="fas fa-share-alt"></i> Share</button>
                <button id="planBtn" class="btn-small btn-book"><i class="fas fa-calendar-plus"></i> Save to Trip</button>
                <a class="btn-small btn-book" href="../bookings/booking_page.php?destination_id=<?php echo $destination_id; ?>" style="text-decoration:none;display:inline-flex;align-items:center;gap:8px"><i class="fas fa-ticket-alt"></i> Book Now</a>
            </div>
        </div>

        <!-- Fetch budget data for this destination -->
        <?php
        // Fetch budget data for this destination
        $hotel_stmt = $conn->prepare("SELECT * FROM hotels WHERE destination_id = ? ORDER BY 
        CASE hotel_type 
            WHEN 'low' THEN 1 
            WHEN 'medium' THEN 2 
            WHEN 'high' THEN 3 
        END, price_per_night ASC");
        $hotel_stmt->bind_param("i", $destination_id);
        $hotel_stmt->execute();
        $hotels = $hotel_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $flight_stmt = $conn->prepare("SELECT * FROM flights WHERE destination_id = ? ORDER BY 
        CASE flight_type 
            WHEN 'low' THEN 1 
            WHEN 'medium' THEN 2 
            WHEN 'high' THEN 3 
        END, price_per_person ASC");
        $flight_stmt->bind_param("i", $destination_id);
        $flight_stmt->execute();
        $flights = $flight_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Group by type
        $hotels_by_type = ['low' => [], 'medium' => [], 'high' => []];
        foreach ($hotels as $hotel) {
            $hotels_by_type[$hotel['hotel_type']][] = $hotel;
        }

        $flights_by_type = ['low' => [], 'medium' => [], 'high' => []];
        foreach ($flights as $flight) {
            $flights_by_type[$flight['flight_type']][] = $flight;
        }

        // Get departure cities for dropdown
        $departure_cities = array_unique(array_column($flights, 'departure_city'));
        sort($departure_cities);

        // Google API Key (store this in environment variables for security)
        $google_api_key = 'AIzaSyAUxNjg6qu6UCn_C0sm4Oo9lwAfnglis7g'; // Replace with your actual API key
        ?>

        <!-- Main layout grid: content + sidebar (TOC, map, weather, currency) -->
        <div class="grid" id="content-grid">

            <section>
                <!-- About / collapsible -->
                <div class="card" id="details">
                    <div class="section-title">About <?php echo htmlspecialchars($destination['name']); ?></div>
                    <div id="desc" class="collapsible"><?php echo nl2br(htmlspecialchars($destination['description'])); ?></div>
                    <div style="margin-top:10px">
                        <button id="readMoreBtn" class="btn-small">Read more</button>
                    </div>
                </div>

                <!-- Details cards -->
                <div class="card" style="margin-top:18px" id="quick-details">
                    <div class="section-title">Quick Details</div>
                    <div style="display:flex;gap:12px;flex-wrap:wrap">
                        <div style="min-width:110px">
                            <strong>Type</strong>
                            <div class="muted"><?php echo htmlspecialchars($destination['type']); ?></div>
                        </div>
                        <div style="min-width:110px">
                            <strong>Budget</strong>
                            <div class="muted">₹<?php echo number_format($destination['budget']); ?> / day</div>
                        </div>
                        <div style="min-width:110px">
                            <strong>Best Season</strong>
                            <div class="muted"><?php echo htmlspecialchars($destination['season']); ?></div>
                        </div>
                        <div style="min-width:110px">
                            <strong>Max Travelers</strong>
                            <div class="muted">
                                <?php
                                $peopleArray = json_decode($destination['people'], true);
                                if ($peopleArray && is_array($peopleArray)):
                                    $formattedPeople = [];
                                    foreach ($peopleArray as $group) {
                                        switch ($group) {
                                            case '1':
                                                $formattedPeople[] = 'Solo';
                                                break;
                                            case '2':
                                                $formattedPeople[] = 'Couples';
                                                break;
                                            case '3-5':
                                                $formattedPeople[] = 'Small Groups';
                                                break;
                                            case '6-9':
                                                $formattedPeople[] = 'Medium Groups';
                                                break;
                                            case '9+':
                                                $formattedPeople[] = 'Large Groups';
                                                break;
                                            default:
                                                $formattedPeople[] = $group . ' people';
                                        }
                                    }
                                    echo implode(', ', $formattedPeople);
                                else:
                                    echo !empty($destination['people']) ? htmlspecialchars($destination['people']) : 'Not specified';
                                endif;
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cuisines section (new) -->
                <div class="card" style="margin-top:18px" id="cuisines">
                    <div class="section-title">Local Cuisines</div>
                    <?php if (!empty($cuisines)): ?>
                        <div class="cuisines-grid" aria-label="Local cuisines">
                            <?php
                            // For each cuisine show a chip and, if available, an image from $cuisine_images mapping
                            foreach ($cuisines as $c) {
                                $c_safe = htmlspecialchars($c);
                                $img_src = '';
                                // try to find an image in cuisine_images mapping (keys may be cuisine names)
                                if (is_array($cuisine_images) && !empty($cuisine_images)) {
                                    // normalized key lookup (case-insensitive)
                                    foreach ($cuisine_images as $key => $val) {
                                        if (strtolower(trim($key)) === strtolower(trim($c))) {
                                            // resolve path if looks like filename
                                            $img_candidate = $val;
                                            // if value looks like filename, resolve; else assume absolute URL
                                            if (preg_match('/^https?:\\/\\//', $img_candidate)) {
                                                $img_src = $img_candidate;
                                            } else {
                                                $img_src = resolve_image_path($base_url, $img_candidate, '/uploads/cuisines/');
                                            }
                                            break;
                                        }
                                    }
                                }
                                // fallback to small placeholder if no image
                                if (empty($img_src)) $img_src = $base_url . '/images/cuisine-placeholder.png';
                                echo '<div class="cuisine-chip" title="' . $c_safe . '">';
                                echo '<img src="' . htmlspecialchars($img_src) . '" alt="' . $c_safe . '">';
                                echo '<span>' . $c_safe . '</span>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    <?php else: ?>
                        <div class="muted">No cuisine information available.</div>
                    <?php endif; ?>
                </div>

                <!-- Gallery with lightbox & lazy loading + Swiper carousel for autoplay -->
                <div class="card" style="margin-top:18px" id="gallery">
                    <div class="section-title">Gallery</div>

                    <!-- Gallery Tabs -->
                    <div class="gallery-tabs">
                        <div class="gallery-tab active" onclick="switchGalleryTab('local')">Local Images</div>
                        <div class="gallery-tab" onclick="switchGalleryTab('google')">Google Images</div>
                    </div>

                    <!-- Local Images Gallery Section -->
                    <div id="localGallerySection" class="gallery-section active">
                        <!-- Main carousel (Swiper) -->
                        <div class="swiper" id="mainSwiper" style="height:auto">
                            <div class="swiper-wrapper">
                                <?php
                                if (!empty($images)) {
                                    foreach ($images as $img) {
                                        $imgPath = resolve_image_path($base_url, $img);
                                        echo '<div class="swiper-slide"><img src="' . htmlspecialchars($imgPath) . '" alt="' . htmlspecialchars($destination['name']) . '" loading="lazy" data-src="' . htmlspecialchars($imgPath) . '"></div>';
                                    }
                                } else {
                                    echo '<div class="swiper-slide"><img src="' . htmlspecialchars($coverImage) . '" alt="No images" loading="lazy"></div>';
                                }
                                ?>
                            </div>
                            <div class="swiper-button-next"></div>
                            <div class="swiper-button-prev"></div>
                            <div class="swiper-pagination"></div>
                        </div>

                        <!-- Thumbnail grid (opens PhotoSwipe) -->
                        <div style="margin-top:12px" class="gallery-grid" id="thumbGrid">
                            <?php
                            if (!empty($images)) {
                                foreach ($images as $img) {
                                    $imgPath = resolve_image_path($base_url, $img);
                                    $small = $imgPath;
                                    echo '<img src="' . htmlspecialchars($small) . '" data-full="' . htmlspecialchars($imgPath) . '" alt="' . htmlspecialchars($destination['name']) . '" loading="lazy" data-ready="0">';
                                }
                            } else {
                                echo '<div class="muted">No images available</div>';
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Google Images Gallery Section -->
                    <div id="googleGallerySection" class="gallery-section">
                        <div id="googleImagesContainer" class="google-images-grid">
                            <div class="weather-loading">Loading Google Images...</div>
                        </div>
                    </div>
                </div>

                <!-- Attractions -->
                <div class="card" style="margin-top:18px" id="attractions">
                    <div class="section-title">Top Attractions</div>
                    <?php
                    if (!empty($destination['attractions'])):
                        $attractionsArray = json_decode($destination['attractions'], true);
                        if ($attractionsArray && is_array($attractionsArray)): ?>
                            <div style="display:flex;flex-direction:column;gap:10px">
                                <?php foreach ($attractionsArray as $attraction): ?>
                                    <div class="review">
                                        <div style="min-width:10px"><i class="fas fa-map-pin"></i></div>
                                        <div>
                                            <div style="font-weight:700"><?php echo htmlspecialchars($attraction); ?></div>
                                            <div class="muted">Search more • <a href="https://www.google.com/search?q=<?php echo urlencode($attraction . ' ' . $destination['name']); ?>" target="_blank">Open</a></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="muted"><?php echo nl2br(htmlspecialchars($destination['attractions'])); ?></div>
                    <?php endif;
                    else:
                        echo '<div class="muted">No attraction details available.</div>';
                    endif;
                    ?>
                </div>

                <!-- Hotels Section - Interactive -->
                <div class="card" style="margin-top:18px" id="hotels">
                    <div class="section-title">Hotels & Accommodations</div>

                    <?php
                    // Fetch hotels for this destination
                    $hotel_list_stmt = $conn->prepare("SELECT * FROM hotels WHERE destination_id = ? ORDER BY 
                        CASE hotel_type 
                            WHEN 'low' THEN 1 
                            WHEN 'medium' THEN 2 
                            WHEN 'high' THEN 3 
                        END, price_per_night ASC");
                    $hotel_list_stmt->bind_param("i", $destination_id);
                    $hotel_list_stmt->execute();
                    $all_hotels = $hotel_list_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $hotel_list_stmt->close();

                    if (!empty($all_hotels)):
                    ?>
                        <div style="display:flex;flex-direction:column;gap:12px">
                            <?php foreach ($all_hotels as $hotel):
                                $hotel_id = 'hotel_' . $hotel['id'];
                                $amenities = json_decode($hotel['amenities'], true);
                            ?>
                                <div class="hotel-item" style="border:1px solid #eef4ff;border-radius:10px;overflow:hidden">
                                    <!-- Hotel Header (Clickable) -->
                                    <div class="hotel-header"
                                        onclick="toggleHotelDetails('<?php echo $hotel_id; ?>')"
                                        style="padding:14px;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid transparent;transition:all 0.2s ease"
                                        onmouseover="this.style.backgroundColor='#f8f9ff'"
                                        onmouseout="this.style.backgroundColor='#fff'">

                                        <div style="display:flex;align-items:center;gap:15px;flex-wrap:wrap;flex:1">
                                            <!-- Hotel Image -->
                                            <div style="width:70px;height:70px;border-radius:8px;overflow:hidden;flex-shrink:0">
                                                <img src="<?php
                                                            $hotel_img = !empty($hotel['image_url']) ? $hotel['image_url'] : $base_url . '/images/hotel-placeholder.jpg';
                                                            if (strpos($hotel_img, 'http') !== 0) {
                                                                $hotel_img = $base_url . $hotel_img;
                                                            }
                                                            echo htmlspecialchars($hotel_img);
                                                            ?>" alt="<?php echo htmlspecialchars($hotel['hotel_name']); ?>" style="width:100%;height:100%;object-fit:cover">
                                            </div>

                                            <!-- Basic Info -->
                                            <div style="flex:1">
                                                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                                                    <h4 style="margin:0;font-size:1.1rem;font-weight:700;color:#16034f">
                                                        <?php echo htmlspecialchars($hotel['hotel_name']); ?>
                                                    </h4>
                                                    <span class="badge" style="background:<?php
                                                                                            echo $hotel['hotel_type'] == 'low' ? '#10b981' : ($hotel['hotel_type'] == 'medium' ? '#f59e0b' : '#ef4444');
                                                                                            ?>;color:white;padding:4px 10px;border-radius:20px;font-size:0.8rem">
                                                        <?php echo ucfirst($hotel['hotel_type']); ?> Budget
                                                    </span>
                                                </div>

                                                <div style="display:flex;align-items:center;gap:15px;margin-top:6px;flex-wrap:wrap">
                                                    <div style="display:flex;align-items:center;gap:5px">
                                                        <i class="fas fa-star" style="color:#fbbf24"></i>
                                                        <span><?php echo $hotel['hotel_rating']; ?> Rating</span>
                                                    </div>
                                                    <div style="display:flex;align-items:center;gap:5px">
                                                        <i class="fas fa-rupee-sign" style="color:#16034f"></i>
                                                        <span><strong>₹<?php echo number_format($hotel['price_per_night']); ?></strong>/night</span>
                                                    </div>
                                                    <?php if ($hotel['free_cancellation']): ?>
                                                        <span style="color:#10b981;font-size:0.9rem">
                                                            <i class="fas fa-check-circle"></i> Free Cancellation
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Expand/Collapse Icon -->
                                        <i class="fas fa-chevron-down" id="<?php echo $hotel_id; ?>_icon" style="color:#16034f;transition:transform 0.3s ease;margin-left:10px"></i>
                                    </div>

                                    <!-- Hotel Details (Hidden by Default) -->
                                    <div id="<?php echo $hotel_id; ?>_details" class="hotel-details" style="display:none;padding:20px;background:#f9fafc;border-top:1px solid #eef4ff">
                                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px">
                                            <!-- Left Column: Description & Amenities -->
                                            <div>
                                                <h5 style="color:#16034f;margin:0 0 10px 0;font-size:1rem">Description</h5>
                                                <p style="color:#4b5563;line-height:1.6;margin-bottom:15px">
                                                    <?php echo !empty($hotel['description']) ? nl2br(htmlspecialchars($hotel['description'])) : 'No description available.'; ?>
                                                </p>

                                                <h5 style="color:#16034f;margin:15px 0 10px 0;font-size:1rem">Amenities</h5>
                                                <?php if (!empty($amenities) && is_array($amenities)): ?>
                                                    <div style="display:flex;flex-wrap:wrap;gap:8px">
                                                        <?php foreach ($amenities as $amenity): ?>
                                                            <span style="background:#eef2ff;color:#16034f;padding:5px 12px;border-radius:20px;font-size:0.9rem">
                                                                <i class="fas fa-check" style="color:#10b981;margin-right:5px"></i>
                                                                <?php echo htmlspecialchars($amenity); ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <p class="muted">No amenities information available.</p>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Right Column: Address & Contact -->
                                            <div>
                                                <h5 style="color:#16034f;margin:0 0 10px 0;font-size:1rem">Location & Contact</h5>
                                                <?php if (!empty($hotel['address'])): ?>
                                                    <p style="color:#4b5563;margin-bottom:8px">
                                                        <i class="fas fa-map-marker-alt" style="color:#ff6600;margin-right:8px"></i>
                                                        <?php echo htmlspecialchars($hotel['address']); ?>
                                                    </p>
                                                <?php endif; ?>

                                                <?php if (!empty($hotel['contact_number'])): ?>
                                                    <p style="color:#4b5563;margin-bottom:8px">
                                                        <i class="fas fa-phone" style="color:#ff6600;margin-right:8px"></i>
                                                        <?php echo htmlspecialchars($hotel['contact_number']); ?>
                                                    </p>
                                                <?php endif; ?>

                                                <div style="display:flex;gap:15px;margin:15px 0">
                                                    <?php if (!empty($hotel['check_in_time'])): ?>
                                                        <div>
                                                            <span style="color:#6b7280;font-size:0.9rem">Check-in</span>
                                                            <div style="font-weight:600;color:#16034f">
                                                                <?php echo date('h:i A', strtotime($hotel['check_in_time'])); ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($hotel['check_out_time'])): ?>
                                                        <div>
                                                            <span style="color:#6b7280;font-size:0.9rem">Check-out</span>
                                                            <div style="font-weight:600;color:#16034f">
                                                                <?php echo date('h:i A', strtotime($hotel['check_out_time'])); ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Additional Features -->
                                                <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:15px">
                                                    <?php if ($hotel['breakfast_included']): ?>
                                                        <span style="background:#d1fae5;color:#065f46;padding:5px 10px;border-radius:20px;font-size:0.9rem">
                                                            <i class="fas fa-utensils"></i> Breakfast Included
                                                        </span>
                                                    <?php endif; ?>

                                                    <?php if ($hotel['free_cancellation']): ?>
                                                        <span style="background:#dbeafe;color:#1e40af;padding:5px 10px;border-radius:20px;font-size:0.9rem">
                                                            <i class="fas fa-calendar-times"></i> Free Cancellation
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Action Buttons -->
                                                <div style="display:flex;gap:10px;margin-top:20px">
                                                    <a href="book_hotel.php?hotel_id=<?php echo $hotel['id']; ?>&destination_id=<?php echo $destination_id; ?>"
                                                        class="btn-small btn-book"
                                                        style="background:#ff6600;color:white;padding:10px 20px;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;gap:8px">
                                                        <i class="fas fa-hotel"></i> Book This Hotel
                                                    </a>

                                                    <?php if (!empty($hotel['address'])): ?>
                                                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($hotel['address']); ?>"
                                                            target="_blank"
                                                            class="btn-small"
                                                            style="border:1px solid #ff6600;color:#ff6600;padding:10px 20px;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;gap:8px">
                                                            <i class="fas fa-directions"></i> Directions
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="muted">No hotels available for this destination yet.</div>
                    <?php endif; ?>
                </div>

            </section>

            <!-- Sidebar: TOC, map, weather, currency -->
            <aside>
                <div class="toc card" role="navigation" aria-label="On-page navigation">
                    <div style="font-weight:800;margin-bottom:12px;font-size:1.1rem;color:#16034f;border-bottom:2px solid #ff6600;padding-bottom:10px">Jump to</div>
                    <div style="display:flex;flex-direction:column;gap:8px">
                        <a href="#details" style="color:#16034f;text-decoration:none;padding:8px 12px;border-radius:6px;transition:all 0.2s ease;border-left:3px solid transparent;display:block" onmouseover="this.style.backgroundColor='#f0f4ff';this.style.borderLeftColor='#ff6600';this.style.paddingLeft='16px'" onmouseout="this.style.backgroundColor='transparent';this.style.borderLeftColor='transparent';this.style.paddingLeft='12px'">
                            <i class="fas fa-info-circle" style="color:#ff6600;margin-right:8px"></i>Details
                        </a>
                        <a href="#cuisines" style="color:#16034f;text-decoration:none;padding:8px 12px;border-radius:6px;transition:all 0.2s ease;border-left:3px solid transparent;display:block" onmouseover="this.style.backgroundColor='#f0f4ff';this.style.borderLeftColor='#ff6600';this.style.paddingLeft='16px'" onmouseout="this.style.backgroundColor='transparent';this.style.borderLeftColor='transparent';this.style.paddingLeft='12px'">
                            <i class="fas fa-utensils" style="color:#ff6600;margin-right:8px"></i>Cuisines
                        </a>
                        <a href="#gallery" style="color:#16034f;text-decoration:none;padding:8px 12px;border-radius:6px;transition:all 0.2s ease;border-left:3px solid transparent;display:block" onmouseover="this.style.backgroundColor='#f0f4ff';this.style.borderLeftColor='#ff6600';this.style.paddingLeft='16px'" onmouseout="this.style.backgroundColor='transparent';this.style.borderLeftColor='transparent';this.style.paddingLeft='12px'">
                            <i class="fas fa-images" style="color:#ff6600;margin-right:8px"></i>Gallery
                        </a>
                        <a href="#attractions" style="color:#16034f;text-decoration:none;padding:8px 12px;border-radius:6px;transition:all 0.2s ease;border-left:3px solid transparent;display:block" onmouseover="this.style.backgroundColor='#f0f4ff';this.style.borderLeftColor='#ff6600';this.style.paddingLeft='16px'" onmouseout="this.style.backgroundColor='transparent';this.style.borderLeftColor='transparent';this.style.paddingLeft='12px'">
                            <i class="fas fa-map-pin" style="color:#ff6600;margin-right:8px"></i>Attractions
                        </a>
                        <a href="#hotels" style="color:#16034f;text-decoration:none;padding:8px 12px;border-radius:6px;transition:all 0.2s ease;border-left:3px solid transparent;display:block" onmouseover="this.style.backgroundColor='#f0f4ff';this.style.borderLeftColor='#ff6600';this.style.paddingLeft='16px'" onmouseout="this.style.backgroundColor='transparent';this.style.borderLeftColor='transparent';this.style.paddingLeft='12px'">
                            <i class="fas fa-hotel" style="color:#ff6600;margin-right:8px"></i>Hotels
                        </a>
                        <a href="#map-section" style="color:#16034f;text-decoration:none;padding:8px 12px;border-radius:6px;transition:all 0.2s ease;border-left:3px solid transparent;display:block" onmouseover="this.style.backgroundColor='#f0f4ff';this.style.borderLeftColor='#ff6600';this.style.paddingLeft='16px'" onmouseout="this.style.backgroundColor='transparent';this.style.borderLeftColor='transparent';this.style.paddingLeft='12px'">
                            <i class="fas fa-map" style="color:#ff6600;margin-right:8px"></i>Map
                        </a>
                    </div>
                </div>

                <div class="card" id="map-section">
                    <div class="section-title">Location</div>
                    <?php if (!empty($destination['map_link'])): ?>
                        <div class="map-container">
                            <iframe
                                class="google-map-frame"
                                src="<?php echo htmlspecialchars($destination['map_link']); ?>&output=embed"
                                allowfullscreen
                                loading="lazy">
                            </iframe>
                        </div>
                        <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap">
                            <a id="directionsBtn" class="btn-small" href="<?php echo htmlspecialchars($destination['map_link']); ?>" target="_blank">Open in Google Maps</a>
                        </div>
                    <?php else: ?>
                        <div class="muted">No map available for this location.</div>
                    <?php endif; ?>
                </div>

                <!-- Weather widget (OpenWeatherMap) -->
                <div class="card" id="weatherWidget">
                    <div class="section-title">Weather</div>
                    <div id="weatherContent">
                        <div class="weather-loading">
                            <i class="fas fa-spinner fa-spin"></i> Loading weather data...
                        </div>
                    </div>
                </div>

                <!-- Currency & local time -->
                <div class="card">
                    <div class="section-title">Local Info</div>
                    <div><strong>Local Time:</strong> <span id="localTime">--:--</span></div>
                    <div style="margin-top:8px"><strong>Currency:</strong> <span id="currencyVal">Loading...</span></div>
                </div>
            </aside>

        </div> <!-- This closes the grid div -->

        <!-- Budget Selection Section - Moved to bottom -->
        <div class="budget-section-wrapper" id="budgetSection">
            <div class="budget-container">
                <h2 class="budget-section-title">
                    <i class="fas fa-wallet budget-section-icon"></i>
                    Plan Your Budget Package
                </h2>

                <!-- Budget Type Buttons -->
                <div class="budget-buttons">
                    <button onclick="selectBudget('low')" id="budgetLowBtn" class="budget-btn budget-btn-low">
                        <i class="fas fa-coins"></i>
                        Low Budget
                        <span class="budget-btn-price">₹<?php echo number_format($destination['budget'] * 0.7); ?>/day est.</span>
                    </button>
                    <button onclick="selectBudget('medium')" id="budgetMediumBtn" class="budget-btn budget-btn-medium">
                        <i class="fas fa-wallet"></i>
                        Medium Budget
                        <span class="budget-btn-price">₹<?php echo number_format($destination['budget']); ?>/day est.</span>
                    </button>
                    <button onclick="selectBudget('high')" id="budgetHighBtn" class="budget-btn budget-btn-high">
                        <i class="fas fa-crown"></i>
                        High Budget
                        <span class="budget-btn-price">₹<?php echo number_format($destination['budget'] * 1.5); ?>/day est.</span>
                    </button>
                </div>

                <!-- Trip Details Form (Hidden initially) -->
                <div id="tripDetailsForm" class="trip-details-form hidden">
                    <h3 class="trip-details-title">Tell us about your trip</h3>
                    <div class="trip-details-grid">
                        <div class="trip-detail-field">
                            <label class="trip-detail-label">Number of Travelers</label>
                            <input type="number" id="travelers" min="1" value="2" class="trip-detail-input">
                        </div>
                        <div class="trip-detail-field">
                            <label class="trip-detail-label">Number of Days</label>
                            <input type="number" id="days" min="1" value="5" class="trip-detail-input">
                        </div>
                        <div class="trip-detail-field">
                            <label class="trip-detail-label">Departure City</label>
                            <select id="departureCity" class="trip-detail-input">
                                <option value="">Select City</option>
                                <?php foreach ($departure_cities as $city): ?>
                                    <option value="<?php echo htmlspecialchars($city); ?>"><?php echo htmlspecialchars($city); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button onclick="calculateTotal()" class="calculate-btn">
                        <i class="fas fa-calculator"></i>
                        Calculate Total
                    </button>
                </div>

                <!-- Package Details Container (Hidden initially) -->
                <div id="packageDetails" class="package-details hidden">
                    <!-- Selected Budget Indicator -->
                    <div id="selectedBudgetBadge" class="budget-badge"></div>

                    <!-- Hotels Section -->
                    <div class="package-section">
                        <div class="package-section-header package-section-header-blue">
                            <i class="fas fa-hotel"></i> Recommended Hotels
                        </div>
                        <div id="hotelsContainer" class="package-grid"></div>
                    </div>

                    <!-- Flights Section -->
                    <div class="package-section">
                        <div class="package-section-header package-section-header-purple">
                            <i class="fas fa-plane"></i> Available Flights
                        </div>
                        <div id="flightsContainer" class="package-grid"></div>
                    </div>

                    <!-- Total Cost Summary -->
                    <div class="total-cost-section">
                        <h3 class="total-cost-title">Total Package Cost</h3>
                        <div id="costBreakdown" class="cost-breakdown"></div>
                        <div id="totalCost" class="total-cost-amount"></div>
                        <button onclick="bookPackage()" class="book-package-btn">
                            <i class="fas fa-ticket-alt"></i>
                            Book This Package
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </main> <!-- This closes the main container -->

    <!-- PhotoSwipe root element (v5) -->
    <div class="pswp" id="pswp" role="dialog" aria-hidden="true"></div>

    <!-- Scripts: Leaflet, Swiper, PhotoSwipe -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/swiper@9/swiper-bundle.min.js"></script>
    <script src="https://unpkg.com/photoswipe@5/dist/photoswipe.min.js"></script>

    <!-- User Session Scripts -->
    <script src="../user/session-sync.js"></script>
    <script src="../user/user-profile.js"></script>
    <script src="../user/auto-logout.js"></script>

    <script>
        // ---------- Helper data from PHP ----------
        const DEST_ID = <?php echo (int)$destination_id; ?>;
        const DEST_NAME = "<?php echo addslashes($destination['name']); ?>";
        const DEST_LOCATION = "<?php echo addslashes($destination['location']); ?>";
        const DEST_LAT = <?php echo (!empty($destination['latitude']) ? (float)$destination['latitude'] : 'null'); ?>;
        const DEST_LNG = <?php echo (!empty($destination['longitude']) ? (float)$destination['longitude'] : 'null'); ?>;
        const IS_LOGGED_IN = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
        const SERVER_BASE = "<?php echo $base_url; ?>";
        const GOOGLE_API_KEY = "<?php echo $google_api_key; ?>";
        const DEST_MAP_LINK = "<?php echo addslashes($destination['map_link']); ?>";

        // ---------- Ken Burns images already animated via CSS (class .kenburns) ----------

        // ---------- Read more collapse ----------
        const readMoreBtn = document.getElementById('readMoreBtn');
        const desc = document.getElementById('desc');
        readMoreBtn.addEventListener('click', () => {
            desc.classList.toggle('open');
            readMoreBtn.textContent = desc.classList.contains('open') ? 'Read less' : 'Read more';
        });

        // ---------- Swiper initialization (carousel autoplay) ----------
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
            },
            effect: 'slide'
        });

        // ---------- Thumbnails -> Photoswipe (basic) ----------
        const thumbs = document.querySelectorAll('#thumbGrid img');
        const pswp = window.PhotoSwipe;
        thumbs.forEach(img => {
            const full = img.dataset.full || img.getAttribute('data-full') || img.src;
            // Lazy unblur placeholder: load actual image then mark ready
            const real = new Image();
            real.src = full;
            real.onload = () => img.setAttribute('data-ready', '1');

            img.addEventListener('click', () => {
                const items = [{
                    src: full,
                    w: real.naturalWidth || 1200,
                    h: real.naturalHeight || 800,
                    title: DEST_NAME
                }];
                const options = {
                    index: 0
                };
                const gallery = pswp.create({
                    dataSource: items
                }, options);
                gallery.init();
            });
        });

        // ---------- Optimistic favorites (persist for guests via localStorage) ----------
        const favBtn = document.getElementById('favBtnMini');
        const favLabel = document.getElementById('favLabel');
        const localFavKey = 'tripmate_favs';

        function setFavUI(active) {
            favBtn.setAttribute('aria-pressed', active ? 'true' : 'false');
            favBtn.querySelector('i').className = active ? 'fas fa-heart' : 'far fa-heart';
            favLabel.textContent = active ? 'Favorited' : 'Save';
        }
        let serverFav = <?php echo $is_favorite ? 'true' : 'false'; ?>;
        let optimistic = serverFav;
        setFavUI(optimistic);

        favBtn.addEventListener('click', () => {
            // If not logged in, persist locally and show message
            if (!IS_LOGGED_IN) {
                // Toggle local
                let local = JSON.parse(localStorage.getItem(localFavKey) || '[]');
                const idx = local.indexOf(DEST_ID);
                if (idx === -1) {
                    local.push(DEST_ID);
                    optimistic = true;
                } else {
                    local.splice(idx, 1);
                    optimistic = false;
                }
                localStorage.setItem(localFavKey, JSON.stringify(local));
                setFavUI(optimistic);
                favBtn.animate([{
                    transform: 'scale(1)'
                }, {
                    transform: 'scale(1.04)'
                }, {
                    transform: 'scale(1)'
                }], {
                    duration: 200
                });
                return;
            }

            // logged-in: optimistic UI update and POST to server
            optimistic = !optimistic;
            setFavUI(optimistic);
            favBtn.disabled = true;
            const action = optimistic ? 'add' : 'remove';
            fetch('actions/toggle_favorite.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `destination_id=${encodeURIComponent(DEST_ID)}&action=${encodeURIComponent(action)}`
                }).then(r => r.json())
                .then(data => {
                    if (data.status !== 'success') {
                        // rollback
                        optimistic = !optimistic;
                        setFavUI(optimistic);
                        alert('Could not update favorite. Try again.');
                    }
                })
                .catch(() => {
                    optimistic = !optimistic;
                    setFavUI(optimistic);
                    alert('Network error');
                })
                .finally(() => favBtn.disabled = false);
        });

        // On page load, if guest and there is localStorage, reflect it
        if (!IS_LOGGED_IN) {
            let local = JSON.parse(localStorage.getItem(localFavKey) || '[]');
            if (local.indexOf(DEST_ID) !== -1) {
                optimistic = true;
                setFavUI(true);
            }
        }

        // ---------- Share button (Web Share API + fallback) ----------
        const shareBtn = document.getElementById('shareBtnMini');
        shareBtn.addEventListener('click', async () => {
            const shareData = {
                title: DEST_NAME + ' | TripMate',
                text: DEST_LOCATION,
                url: window.location.href
            };
            if (navigator.share) {
                try {
                    await navigator.share(shareData);
                } catch (e) {}
                return;
            }
            // fallback copy
            try {
                await navigator.clipboard.writeText(window.location.href);
                shareBtn.textContent = 'Link copied';
                setTimeout(() => shareBtn.innerHTML = '<i class="fas fa-share-alt"></i> Share', 1400);
            } catch (e) {
                prompt('Copy this link:', window.location.href);
            }
        });

        // ---------- Weather widget (OpenWeatherMap) with 7-day forecast ----------
        (function loadWeather() {
            const weatherEl = document.getElementById('weatherContent');

            // Using OpenWeatherMap API (free tier)
            const API_KEY = '2c5b9d6d17097085842456fbd26ac081'; // Replace with your actual API key

            if (!API_KEY || DEST_LAT === null || DEST_LNG === null) {
                weatherEl.innerHTML = '<div class="weather-error">Weather data not available</div>';
                return;
            }

            // Fetch current weather and 7-day forecast
            fetch(`https://api.openweathermap.org/data/2.5/onecall?lat=${DEST_LAT}&lon=${DEST_LNG}&units=metric&exclude=minutely,alerts&appid=${API_KEY}`)
                .then(r => r.json())
                .then(data => {
                    if (data && data.current) {
                        const current = data.current;
                        const daily = data.daily;

                        // Get weather icon
                        const iconCode = current.weather[0].icon;
                        const iconUrl = `https://openweathermap.org/img/wn/${iconCode}@2x.png`;

                        // Build weather HTML
                        let weatherHTML = `
                            <div class="weather-widget">
                                <div class="weather-current">
                                    <div>
                                        <div class="weather-temp">${Math.round(current.temp)}°C</div>
                                        <div style="font-size:1rem;text-transform:capitalize">${current.weather[0].description}</div>
                                    </div>
                                    <img class="weather-icon" src="${iconUrl}" alt="${current.weather[0].description}">
                                </div>
                                
                                <div class="weather-details">
                                    <div class="weather-detail-item">
                                        <i class="fas fa-temperature-high"></i>
                                        <span>Feels Like</span>
                                        <strong>${Math.round(current.feels_like)}°C</strong>
                                    </div>
                                    <div class="weather-detail-item">
                                        <i class="fas fa-tint"></i>
                                        <span>Humidity</span>
                                        <strong>${current.humidity}%</strong>
                                    </div>
                                    <div class="weather-detail-item">
                                        <i class="fas fa-wind"></i>
                                        <span>Wind</span>
                                        <strong>${Math.round(current.wind_speed * 3.6)} km/h</strong>
                                    </div>
                                    <div class="weather-detail-item">
                                        <i class="fas fa-cloud"></i>
                                        <span>Clouds</span>
                                        <strong>${current.clouds}%</strong>
                                    </div>
                                    <div class="weather-detail-item">
                                        <i class="fas fa-sun"></i>
                                        <span>UV Index</span>
                                        <strong>${Math.round(current.uvi)}</strong>
                                    </div>
                                    <div class="weather-detail-item">
                                        <i class="fas fa-eye"></i>
                                        <span>Visibility</span>
                                        <strong>${(current.visibility / 1000).toFixed(1)} km</strong>
                                    </div>
                                </div>
                                
                                <div style="margin-top:15px">
                                    <div style="font-weight:600;margin-bottom:10px">7-Day Forecast</div>
                                    <div class="weather-forecast">
                        `;

                        // Add 7-day forecast
                        daily.slice(1, 8).forEach(day => {
                            const date = new Date(day.dt * 1000);
                            const dayName = date.toLocaleDateString('en-US', {
                                weekday: 'short'
                            });
                            const icon = day.weather[0].icon;

                            weatherHTML += `
                                <div class="forecast-day">
                                    <div class="day">${dayName}</div>
                                    <img src="https://openweathermap.org/img/wn/${icon}.png" alt="${day.weather[0].description}" style="width:40px;height:40px">
                                    <div class="temp">${Math.round(day.temp.day)}°C</div>
                                    <div class="condition">${day.weather[0].description}</div>
                                    <div style="font-size:0.8rem;opacity:0.8">${Math.round(day.pop * 100)}% rain</div>
                                </div>
                            `;
                        });

                        weatherHTML += `
                                    </div>
                                </div>
                            </div>
                        `;

                        weatherEl.innerHTML = weatherHTML;
                    } else {
                        weatherEl.innerHTML = '<div class="weather-error">Unable to load weather data</div>';
                    }
                })
                .catch(err => {
                    console.warn('Weather API error:', err);
                    weatherEl.innerHTML = '<div class="weather-error">Weather service temporarily unavailable</div>';
                });
        })();

        // ---------- Google Images Gallery ----------
        function loadGoogleImages() {
            const container = document.getElementById('googleImagesContainer');

            if (!GOOGLE_API_KEY || GOOGLE_API_KEY === 'YOUR_GOOGLE_API_KEY') {
                container.innerHTML = '<div class="weather-error">Google Images API key not configured</div>';
                return;
            }

            // Using Google Custom Search JSON API
            const searchEngineId = '55d60d886e17d46b2'; // You need to create a Custom Search Engine
            const query = encodeURIComponent(`${DEST_NAME} ${DEST_LOCATION} travel destination`);

            fetch(`https://www.googleapis.com/customsearch/v1?q=${query}&cx=${searchEngineId}&key=${GOOGLE_API_KEY}&searchType=image&num=10`)
                .then(r => r.json())
                .then(data => {
                    if (data.items && data.items.length > 0) {
                        let imagesHTML = '';
                        data.items.forEach(item => {
                            imagesHTML += `
                                <div style="position:relative">
                                    <img src="${item.link}" 
                                         alt="${item.title}" 
                                         onclick="openGoogleImage('${item.link}', '${item.title.replace(/'/g, "\\'")}')"
                                         loading="lazy"
                                         onerror="this.parentElement.style.display='none'">
                                    <span class="image-source-badge">Google</span>
                                </div>
                            `;
                        });
                        container.innerHTML = imagesHTML;
                    } else {
                        container.innerHTML = '<div class="weather-error">No Google images found</div>';
                    }
                })
                .catch(err => {
                    console.warn('Google Images API error:', err);
                    container.innerHTML = '<div class="weather-error">Unable to load Google images</div>';
                });
        }

        function openGoogleImage(src, title) {
            const items = [{
                src: src,
                w: 1200,
                h: 800,
                title: title || DEST_NAME
            }];
            const options = {
                index: 0
            };
            const gallery = pswp.create({
                dataSource: items
            }, options);
            gallery.init();
        }

        // ---------- Gallery Tab Switching ----------
        window.switchGalleryTab = function(tab) {
            const tabs = document.querySelectorAll('.gallery-tab');
            const sections = document.querySelectorAll('.gallery-section');

            tabs.forEach(t => t.classList.remove('active'));
            sections.forEach(s => s.classList.remove('active'));

            if (tab === 'local') {
                tabs[0].classList.add('active');
                document.getElementById('localGallerySection').classList.add('active');
            } else {
                tabs[1].classList.add('active');
                document.getElementById('googleGallerySection').classList.add('active');
                if (!document.getElementById('googleImagesContainer').children.length ||
                    document.getElementById('googleImagesContainer').children[0].classList.contains('weather-loading')) {
                    loadGoogleImages();
                }
            }
        };

        // ---------- Currency converter (public API example) ----------
        (function loadCurrency() {
            const el = document.getElementById('currencyVal');
            // For demo, use exchangerate.host (free) to convert to INR if needed; destination currency unknown -> assume localCurrency in DB
            const localCurrency = '<?php echo addslashes($destination['currency'] ?? 'INR'); ?>';
            if (!localCurrency) {
                el.textContent = '—';
                return;
            }
            fetch(`https://api.exchangerate.host/latest?base=USD&symbols=${localCurrency},INR`)
                .then(r => r.json())
                .then(data => {
                    // show 1 USD to localCurrency
                    if (data && data.rates && data.rates[localCurrency]) {
                        el.textContent = `1 USD = ${data.rates[localCurrency].toFixed(2)} ${localCurrency}`;
                    } else el.textContent = localCurrency;
                }).catch(() => el.textContent = localCurrency);
        })();

        // ---------- Local time display ----------
        (function localTime() {
            const el = document.getElementById('localTime');
            // If timezone stored in DB use that, else derive from coords via Intl or a timezone API (not free reliably)
            const tz = '<?php echo addslashes($destination['timezone'] ?? ''); ?>';

            function update() {
                try {
                    let now;
                    if (tz) {
                        now = new Date().toLocaleString('en-US', {
                            timeZone: tz
                        });
                        el.textContent = new Date(now).toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    } else if (DEST_LAT !== null && DEST_LNG !== null && Intl && Intl.DateTimeFormat) {
                        // approximate using timezone lookup would be better; fallback to user's current time
                        el.textContent = new Date().toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    } else {
                        el.textContent = '--:--';
                    }
                } catch (e) {
                    el.textContent = '--:--';
                }
            }
            update();
            setInterval(update, 60000);
        })();

        // ---------- Accessibility: ensure images lazy-load real image and remove blur */
        document.querySelectorAll('.gallery-grid img').forEach(img => {
            const full = img.dataset.full || img.src;
            const loadImg = new Image();
            loadImg.src = full;
            loadImg.onload = function() {
                img.src = full;
                img.dataset.ready = '1';
            };
        });

        // ---------- Simple "Save to Trip" local itinerary (persist in localStorage, sync server on login) ----------
        document.getElementById('planBtn').addEventListener('click', () => {
            let trips = JSON.parse(localStorage.getItem('tripmate_itinerary') || '[]');
            if (!trips.includes(DEST_ID)) {
                trips.push(DEST_ID);
                localStorage.setItem('tripmate_itinerary', JSON.stringify(trips));
                alert('Saved to your trip planner (local). Sign in to persist across devices.');
            } else {
                alert('Already in your trip planner.');
            }
        });

        // ---------- Hotel Details Toggle Function ----------
        function toggleHotelDetails(hotelId) {
            const detailsDiv = document.getElementById(hotelId + '_details');
            const icon = document.getElementById(hotelId + '_icon');

            if (detailsDiv.style.display === 'none' || detailsDiv.style.display === '') {
                // Close any other open hotel details first
                document.querySelectorAll('.hotel-details').forEach(div => {
                    div.style.display = 'none';
                });
                document.querySelectorAll('[id$="_icon"]').forEach(ic => {
                    ic.style.transform = 'rotate(0deg)';
                });

                // Open this one
                detailsDiv.style.display = 'block';
                icon.style.transform = 'rotate(180deg)';

                // Smooth scroll to this hotel
                setTimeout(() => {
                    detailsDiv.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest'
                    });
                }, 100);
            } else {
                // Close this one
                detailsDiv.style.display = 'none';
                icon.style.transform = 'rotate(0deg)';
            }
        }

        // ---------- Budget Package Section ----------
        // Budget selection data from PHP
        const hotelsByType = <?php echo json_encode($hotels_by_type); ?>;
        const flightsByType = <?php echo json_encode($flights_by_type); ?>;
        const destinationBudget = <?php echo $destination['budget']; ?>;
        const destinationName = "<?php echo addslashes($destination['name']); ?>";

        let selectedBudget = null;
        let selectedHotel = null;
        let selectedFlight = null;

        function selectBudget(budget) {
            selectedBudget = budget;

            // Update button styles
            document.querySelectorAll('.budget-btn').forEach(btn => {
                btn.classList.remove('ring-4', 'ring-[#ff6600]', 'scale-105');
            });
            document.getElementById(`budget${budget.charAt(0).toUpperCase() + budget.slice(1)}Btn`).classList.add('ring-4', 'ring-[#ff6600]', 'scale-105');

            // Show trip details form
            document.getElementById('tripDetailsForm').classList.remove('hidden');
            document.getElementById('packageDetails').classList.add('hidden');

            // Reset selections
            selectedHotel = null;
            selectedFlight = null;
        }

        function calculateTotal() {
            const travelers = parseInt(document.getElementById('travelers').value) || 1;
            const days = parseInt(document.getElementById('days').value) || 1;
            const departureCity = document.getElementById('departureCity').value;

            if (!selectedBudget) {
                alert('Please select a budget type first');
                return;
            }

            if (!departureCity) {
                alert('Please select your departure city');
                return;
            }

            // Get first available hotel and flight of selected type
            const availableHotels = hotelsByType[selectedBudget] || [];
            const availableFlights = flightsByType[selectedBudget]?.filter(f => f.departure_city === departureCity) || [];

            if (availableHotels.length === 0) {
                alert('No hotels available for this budget category');
                return;
            }

            if (availableFlights.length === 0) {
                alert('No flights available from your selected city for this budget category');
                return;
            }

            selectedHotel = availableHotels[0];
            selectedFlight = availableFlights[0];

            displayPackageDetails(travelers, days);
        }

        function displayPackageDetails(travelers, days) {
            // Update badge
            const badge = document.getElementById('selectedBudgetBadge');
            badge.className = `inline-block px-4 py-2 rounded-full text-white font-bold mb-4 ${
        selectedBudget === 'low' ? 'bg-green-600' : 
        selectedBudget === 'medium' ? 'bg-yellow-600' : 'bg-red-600'
    }`;
            badge.innerHTML = `<i class="fas fa-${
        selectedBudget === 'low' ? 'coins' : 
        selectedBudget === 'medium' ? 'wallet' : 'crown'
    } mr-2"></i>${
        selectedBudget.charAt(0).toUpperCase() + selectedBudget.slice(1)
    } Budget Package`;

            // Display hotels
            const hotelsContainer = document.getElementById('hotelsContainer');
            hotelsContainer.innerHTML = hotelsByType[selectedBudget].map(hotel => `
        <div class="border rounded-lg p-4 ${selectedHotel && selectedHotel.id === hotel.id ? 'border-4 border-[#ff6600] bg-orange-50' : 'hover:shadow-lg'} transition cursor-pointer" 
             onclick="selectHotel(${hotel.id})">
            <div class="flex gap-4">
                <div class="w-24 h-24 bg-gray-200 rounded-lg overflow-hidden flex-shrink-0">
                    <img src="${hotel.image_url || '/images/hotel-placeholder.jpg'}" alt="${hotel.hotel_name}" class="w-full h-full object-cover">
                </div>
                <div class="flex-1">
                    <h4 class="font-bold text-[#16034f]">${hotel.hotel_name}</h4>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-yellow-400">${'★'.repeat(Math.round(hotel.hotel_rating))}</span>
                        <span class="text-sm text-gray-600">${hotel.hotel_rating}</span>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">₹${hotel.price_per_night}/night</p>
                    ${hotel.breakfast_included ? '<span class="text-xs bg-green-100 text-green-600 px-2 py-1 rounded mt-2 inline-block">Breakfast Included</span>' : ''}
                </div>
            </div>
        </div>
    `).join('');

            // Display flights
            const flightsContainer = document.getElementById('flightsContainer');
            flightsContainer.innerHTML = flightsByType[selectedBudget]
                .filter(f => f.departure_city === document.getElementById('departureCity').value)
                .map(flight => `
            <div class="border rounded-lg p-4 ${selectedFlight && selectedFlight.id === flight.id ? 'border-4 border-[#ff6600] bg-orange-50' : 'hover:shadow-lg'} transition cursor-pointer"
                 onclick="selectFlight(${flight.id})">
                <div class="flex flex-wrap items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center">
                            <i class="fas fa-plane text-2xl text-[#ff6600]"></i>
                        </div>
                        <div>
                            <h4 class="font-bold">${flight.airline}</h4>
                            <p class="text-sm text-gray-600">${flight.departure_city} → ${destinationName}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-[#ff6600]">₹${flight.price_per_person}</p>
                        <p class="text-sm text-gray-600">${flight.duration_hours}h • ${flight.stops === 0 ? 'Non-stop' : flight.stops + ' stop(s)'}</p>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-3 text-sm">
                    <span class="bg-gray-100 px-2 py-1 rounded">${flight.departure_time} - ${flight.arrival_time}</span>
                    <span class="bg-gray-100 px-2 py-1 rounded">${flight.flight_class}</span>
                    <span class="bg-gray-100 px-2 py-1 rounded">${flight.baggage_allowance}</span>
                    ${flight.refundable ? '<span class="bg-green-100 text-green-600 px-2 py-1 rounded">Refundable</span>' : ''}
                </div>
            </div>
        `).join('');

            // Calculate and display total cost
            calculateAndDisplayTotal(travelers, days);

            // Show package details
            document.getElementById('packageDetails').classList.remove('hidden');
        }

        function selectHotel(hotelId) {
            selectedHotel = hotelsByType[selectedBudget].find(h => h.id === hotelId);
            const travelers = parseInt(document.getElementById('travelers').value) || 1;
            const days = parseInt(document.getElementById('days').value) || 1;
            calculateAndDisplayTotal(travelers, days);
            displayPackageDetails(travelers, days); // Re-render to update selection highlight
        }

        function selectFlight(flightId) {
            selectedFlight = flightsByType[selectedBudget].find(f => f.id === flightId);
            const travelers = parseInt(document.getElementById('travelers').value) || 1;
            const days = parseInt(document.getElementById('days').value) || 1;
            calculateAndDisplayTotal(travelers, days);
            displayPackageDetails(travelers, days); // Re-render to update selection highlight
        }

        function calculateAndDisplayTotal(travelers, days) {
            if (!selectedHotel || !selectedFlight) return;

            const hotelCost = selectedHotel.price_per_night * days;
            const flightCost = selectedFlight.price_per_person * travelers;
            const total = (hotelCost * travelers) + flightCost;
            const perPersonPerDay = (selectedHotel.price_per_night + (selectedFlight.price_per_person / days));

            document.getElementById('costBreakdown').innerHTML = `
        <div class="flex justify-between">
            <span style="color: #1f2937;">Hotel (${days} nights × ${travelers} travelers):</span>
            <span class="font-bold" style="color: #111827;">₹${(hotelCost * travelers).toLocaleString()}</span>
        </div>
        <div class="flex justify-between">
            <span style="color: #1f2937;">Flights (${travelers} travelers):</span>
            <span class="font-bold" style="color: #111827;">₹${flightCost.toLocaleString()}</span>
        </div>
        <div class="flex justify-between text-sm">
            <span style="color: #1f2937;">Per person per day estimate in accomodation:</span>
            <span style="color: #111827;">₹${Math.round(perPersonPerDay).toLocaleString()}</span>
        </div>
    `;

            document.getElementById('totalCost').innerHTML = `
        <span style="color: #1b50cb;">Total: ₹${total.toLocaleString()}</span>
        <span class="text-sm block font-normal mt-1" style="color: #951313;">Includes accommodation & flights only</span>
    `;
        }

        function bookPackage() {
            if (!selectedHotel || !selectedFlight) {
                alert('Please select a hotel and flight first');
                return;
            }

            const travelers = parseInt(document.getElementById('travelers').value) || 1;
            const days = parseInt(document.getElementById('days').value) || 1;

            // Check if user is logged in
            <?php if (!isset($_SESSION['user_id'])): ?>
                alert('Please sign in to book a package');
                return;
            <?php endif; ?>

            // Redirect to booking page with parameters
            const bookingUrl = `../booking/package_booking.php?destination_id=<?php echo $destination_id; ?>&hotel_id=${selectedHotel.id}&flight_id=${selectedFlight.id}&travelers=${travelers}&days=${days}&budget_type=${selectedBudget}`;
            window.location.href = bookingUrl;
        }

        // Initialize with default values if needed
        document.addEventListener('DOMContentLoaded', function() {
            // Add any initialization code here

            // Pre-load Google Images when tab is clicked (handled in switchGalleryTab)

            // Set up user session if logged in
            if (IS_LOGGED_IN) {
                document.body.classList.add('user-logged-in');
            }
        });
    </script>

    <!-- Structured data JSON-LD -->
    <script type="application/ld+json">
        <?php echo json_encode($structured_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
    </script>

    <!-- Page Time Tracker -->
    <script src="../main/page_time_tracker.js"></script>
</body>

</html>