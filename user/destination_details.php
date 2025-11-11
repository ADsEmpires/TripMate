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
        /* Use viewport-based heights but clamp to reasonable min/max to fit various screens */
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

        /* responsive hero height */
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

        /* translucent gradient overlay */
        .hero .overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(6, 6, 24, 0.12) 0%, rgba(6, 6, 24, 0.28) 40%, rgba(6, 6, 24, 0.45) 100%);
            pointer-events: none;
        }

        /* CTA badges on hero */
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

        /* sticky mini-card under hero */
        /* Use width constraints so it doesn't overflow on small screens */
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

        /* layout sections */
        /* Use responsive grid with a better minmax for the sidebar */
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

        /* Cards */
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

        /* Cuisines: small chips with optional images */
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

        /* gallery */
        /* let thumbnails adapt to container width: use auto rows */
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

        /* Swiper - ensure it adapts height gracefully */
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

        /* map */
        #map {
            height: 300px;
            border-radius: 8px;
            overflow: hidden
        }

        @media (max-width:640px) {
            #map {
                height: 220px
            }
        }

        /* reviews */
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

        /* collapsible description */
        .collapsible {
            max-height: 120px;
            overflow: hidden;
            transition: max-height .28s ease
        }

        .collapsible.open {
            max-height: 1200px
        }

        /* TOC sticky */
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

        /* responsive */
        @media(max-width:640px) {
            .container {
                padding: calc(64px + 16px) 12px 40px
            }

            .navbar a.btn {
                padding: 6px 8px;
                font-size: 0.9rem
            }

            .mini-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
                padding: 10px
            }

            .mini-left img {
                width: 48px;
                height: 48px
            }

            .hero .hero-title {
                left: 12px;
                bottom: 12px;
                max-width: 85%
            }

            .badge {
                font-size: 0.85rem;
                padding: 6px 8px
            }

            .swiper .swiper-slide img {
                height: clamp(160px, 30vw, 260px)
            }

            .cuisine-chip img {
                width: 24px;
                height: 24px
            }
        }
    </style>
    <!-- Auto-Logout System 
<script src="../user/auto-logout.js"></script>

    Structured Data JSON-LD 
    <script type="application/ld+json">
        <?php echo json_encode($structured_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
    </script> -->
</head>

<body>

    <!-- NAV -->
    <header class="navbar">
        <div class="logo"><i class="fas fa-compass"></i> <strong style="margin-left:6px">TripMate</strong></div>
        <div>
            <a class="btn" href="../search/search.html">Back to Search</a>
            <a class="btn" href="user_dashboard.php">Dashboard</a>
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
            </div>
        </div>

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

                    <!-- Main carousel (Swiper) -->
                    <div class="swiper" id="mainSwiper" style="height:auto">
                        <div class="swiper-wrapper">
                            <?php
                            if (!empty($images)) {
                                foreach ($images as $img) {
                                    $imgPath = resolve_image_path($base_url, $img);
                                    // Provide srcset for responsive; creating small names would usually be server-side. Here we point same file.
                                    echo '<div class="swiper-slide"><img src="' . htmlspecialchars($imgPath) . '" alt="' . htmlspecialchars($destination['name']) . '" loading="lazy" data-src="' . htmlspecialchars($imgPath) . '"></div>';
                                }
                            } else {
                                echo '<div class="swiper-slide"><img src="' . htmlspecialchars($coverImage) . '" alt="No images" loading="lazy"></div>';
                            }
                            ?>
                        </div>
                        <!-- navigation -->
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
                                $small = $imgPath; // ideally point to smaller variant
                                echo '<img src="' . htmlspecialchars($small) . '" data-full="' . htmlspecialchars($imgPath) . '" alt="' . htmlspecialchars($destination['name']) . '" loading="lazy" data-ready="0">';
                            }
                        } else {
                            echo '<div class="muted">No images available</div>';
                        }
                        ?>
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

                <!-- Reviews & add review -->
                <div class="card" style="margin-top:18px" id="reviews">
                    <div class="section-title">Ratings & Reviews <small style="color:var(--muted)"> (<?php echo $reviews_count; ?>)</small></div>
                    <div style="display:flex;gap:10px;align-items:center;margin-bottom:12px;flex-wrap:wrap">
                        <div style="font-size:1.6rem;font-weight:800"><?php echo $average_rating ? $average_rating : '—'; ?></div>
                        <div style="color:var(--muted)"><?php echo $reviews_count; ?> reviews</div>
                    </div>

                    <?php if (!empty($reviews)): foreach ($reviews as $r): ?>
                            <div class="review" style="margin-bottom:10px">
                                <div style="width:44px;height:44px;border-radius:8px;background:#f3f4f6;display:flex;align-items:center;justify-content:center"><?php echo strtoupper(substr($r['user_name'] ?? 'U', 0, 1)); ?></div>
                                <div>
                                    <div class="meta"><?php echo htmlspecialchars($r['user_name'] ?? 'User'); ?> • <small style="color:var(--muted)"><?php echo htmlspecialchars($r['rating']); ?> ★</small></div>
                                    <div class="text"><?php echo nl2br(htmlspecialchars($r['comment'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach;
                    else: ?>
                        <div class="muted">No reviews yet. Be the first to review!</div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form id="reviewForm" method="post" action="actions/add_review.php" enctype="multipart/form-data" style="margin-top:12px">
                            <input type="hidden" name="destination_id" value="<?php echo $destination_id; ?>">
                            <div style="display:flex;gap:8px;flex-wrap:wrap">
                                <select name="rating" required>
                                    <option value="">Rating</option>
                                    <option value="5">5 - Excellent</option>
                                    <option value="4">4 - Very good</option>
                                    <option value="3">3 - Good</option>
                                    <option value="2">2 - Fair</option>
                                    <option value="1">1 - Poor</option>
                                </select>
                                <input type="file" name="images[]" accept="image/*" multiple>
                            </div>
                            <div style="margin-top:8px">
                                <textarea name="comment" rows="3" style="width:100%" placeholder="Share your experience..."></textarea>
                            </div>
                            <div style="margin-top:8px">
                                <button class="btn-small btn-book" type="submit">Submit Review</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div style="margin-top:8px" class="muted">Sign in to add a review.</div>
                    <?php endif; ?>
                </div>

            </section>

            <!-- Sidebar: TOC, map, weather, currency -->
            <aside>
                <div class="toc card" role="navigation" aria-label="On-page navigation">
                    <div style="font-weight:800;margin-bottom:8px">Jump to</div>
                    <a href="#details">Details</a>
                    <a href="#cuisines">Cuisines</a>
                    <a href="#gallery">Gallery</a>
                    <a href="#attractions">Attractions</a>
                    <a href="#reviews">Reviews</a>
                    <a href="#map-section">Map</a>
                </div>

                <div class="card" id="map-section" style="margin-top:16px">
                    <div class="section-title">Location</div>
                    <?php if (!empty($destination['latitude']) && !empty($destination['longitude'])): ?>
                        <div id="map"></div>
                        <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap">
                            <a id="directionsBtn" class="btn-small" href="https://www.google.com/maps/dir/?api=1&destination=<?php echo urlencode($destination['latitude'] . ',' . $destination['longitude']); ?>" target="_blank">Open Directions</a>
                            <button id="routeFromBtn" class="btn-small">Route from my location</button>
                        </div>
                    <?php else: ?>
                        <div class="muted">No coordinates available. <a href="<?php echo htmlspecialchars($destination['map_link']); ?>" target="_blank">Open map link</a></div>
                    <?php endif; ?>
                </div>

                <!-- Weather widget (OpenWeatherMap) -->
                <div class="card" style="margin-top:16px" id="weatherWidget">
                    <div class="section-title">Weather</div>
                    <div id="weatherContent" class="muted">Loading weather...</div>
                </div>

                <!-- Currency & local time -->
                <div class="card" style="margin-top:16px">
                    <div class="section-title">Local Info</div>
                    <div><strong>Local Time:</strong> <span id="localTime">--:--</span></div>
                    <div style="margin-top:8px"><strong>Currency:</strong> <span id="currencyVal">Loading...</span></div>
                </div>
            </aside>

        </div>

        <!-- <div style="text-align: center; margin-top: 16px; color: #6b7280; font-size: 14px;">
                    Photos provided by <a href="https://unsplash.com?utm_source=TripMate&utm_medium=referral" target="_blank" style="color: #16034f; font-weight: 600;">Unsplash</a>
                </div> -->
    </main>

    <!-- PhotoSwipe root element (v5) -->
    <div class="pswp" id="pswp" role="dialog" aria-hidden="true"></div>

    <!-- Scripts: Leaflet, Swiper, PhotoSwipe -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/swiper@9/swiper-bundle.min.js"></script>
    <script src="https://unpkg.com/photoswipe@5/dist/photoswipe.min.js"></script>

    <script>
        // ---------- Helper data from PHP ----------
        const DEST_ID = <?php echo (int)$destination_id; ?>;
        const DEST_NAME = "<?php echo addslashes($destination['name']); ?>";
        const DEST_LOCATION = "<?php echo addslashes($destination['location']); ?>";
        const DEST_LAT = <?php echo (!empty($destination['latitude']) ? (float)$destination['latitude'] : 'null'); ?>;
        const DEST_LNG = <?php echo (!empty($destination['longitude']) ? (float)$destination['longitude'] : 'null'); ?>;
        const IS_LOGGED_IN = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
        const SERVER_BASE = "<?php echo $base_url; ?>";

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

        // ---------- Leaflet map with marker(s) and directions ----------
        if (DEST_LAT !== null && DEST_LNG !== null) {
            try {
                const map = L.map('map', {
                    scrollWheelZoom: false
                }).setView([DEST_LAT, DEST_LNG], 12);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);
                const mainMarker = L.marker([DEST_LAT, DEST_LNG]).addTo(map).bindPopup(DEST_NAME);
                // Optionally: show nearby sample POIs (mocked or fetched from server)
                // Example: fetch('actions/nearby_pois.php?lat=...&lng=...').then...
                // For demo we add placeholder markers
                <?php
                // If you have attractions with coordinates saved in DB, you can embed them here.
                // We'll attempt to use a hypothetical JSON field 'poi_points' (array of {name,lat,lng,type})
                if (!empty($destination['poi_points'])) {
                    $poi_points = json_decode($destination['poi_points'], true);
                    if ($poi_points && is_array($poi_points)) {
                        echo "const pois = " . json_encode($poi_points) . ";\n";
                        echo "pois.forEach(p => { L.marker([p.lat, p.lng]).addTo(map).bindPopup('<strong>'+p.name+'</strong><br/>'+p.type); });\n";
                    }
                }
                ?>
                // Route from current location (simple)
                document.getElementById('routeFromBtn')?.addEventListener('click', () => {
                    if (!navigator.geolocation) {
                        alert('Geolocation not supported');
                        return;
                    }
                    navigator.geolocation.getCurrentPosition(pos => {
                        const fromLat = pos.coords.latitude;
                        const fromLng = pos.coords.longitude;
                        // Open Google Maps directions
                        window.open(`https://www.google.com/maps/dir/?api=1&origin=${fromLat},${fromLng}&destination=${DEST_LAT},${DEST_LNG}`);
                    }, () => alert('Permission denied or error getting location'));
                });
            } catch (e) {
                document.getElementById('map').innerHTML = '<div class="muted">Map could not be loaded.</div>';
                console.warn('Map init', e);
            }
        }

        // ---------- Weather widget (OpenWeatherMap) ----------
        (function loadWeather() {
            const weatherEl = document.getElementById('weatherContent');
            const apiKey = 'YOUR_OPENWEATHERMAP_API_KEY'; // <<<< Replace with server-side stored key or proxy action for security
            if (!apiKey || DEST_LAT === null || DEST_LNG === null) {
                weatherEl.textContent = 'Weather not available';
                return;
            }
            fetch(`https://api.openweathermap.org/data/2.5/onecall?lat=${DEST_LAT}&lon=${DEST_LNG}&units=metric&exclude=minutely,hourly,alerts&appid=${apiKey}`)
                .then(r => r.json())
                .then(d => {
                    if (d && d.current) {
                        weatherEl.innerHTML = `<div><strong>${Math.round(d.current.temp)}°C</strong> • ${d.current.weather[0].description}</div>
                <div style="margin-top:8px;color:var(--muted)">3-day: ${Math.round(d.daily[1].temp.day)}°/${Math.round(d.daily[1].temp.night)}° • ${Math.round(d.daily[2].temp.day)}°/${Math.round(d.daily[2].temp.night)}°</div>`;
                    } else {
                        weatherEl.textContent = 'No weather data';
                    }
                }).catch(err => {
                    weatherEl.textContent = 'Weather load error';
                    console.warn(err);
                });
        })();

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

        // ---------- Dynamic Unsplash Gallery Integration ----------
        const FETCH_DYNAMIC_IMAGES = true; // Set to false to use database images only

        async function loadDynamicGallery() {
            if (!FETCH_DYNAMIC_IMAGES) return;

            const galleryGrid = document.getElementById('thumbGrid');
            const mainSwiper = document.getElementById('mainSwiper');

            if (!galleryGrid || !mainSwiper) return;

            try {
                // Show loading state
                galleryGrid.innerHTML = '<div class="col-span-3 text-center py-8 text-gray-500"><i class="fas fa-spinner fa-spin text-2xl"></i><p class="mt-2">Loading gallery...</p></div>';

                // Build search query from destination name and location
                const searchQuery = `${DEST_NAME} ${DEST_LOCATION} landmarks tourism`;

                // Fetch images from Unsplash
                const response = await fetch(`actions/fetch_images.php?query=${encodeURIComponent(searchQuery)}&count=12`);
                const data = await response.json();

                if (data.error) {
                    console.error('Image fetch error:', data.error);
                    // Fallback to database images
                    return;
                }

                if (data.success && data.images && data.images.length > 0) {
                    // Update main swiper
                    const swiperWrapper = mainSwiper.querySelector('.swiper-wrapper');
                    swiperWrapper.innerHTML = '';

                    data.images.forEach((img, index) => {
                        // Add to main carousel
                        const slide = document.createElement('div');
                        slide.className = 'swiper-slide';
                        slide.innerHTML = `<img src="${img.urls.regular}" alt="${img.description || DEST_NAME}" loading="${index === 0 ? 'eager' : 'lazy'}">`;
                        swiperWrapper.appendChild(slide);
                    });

                    // Reinitialize swiper
                    if (typeof swiper !== 'undefined') {
                        swiper.update();
                    }

                    // Update thumbnail grid
                    galleryGrid.innerHTML = '';
                    data.images.forEach(img => {
                        const thumb = document.createElement('img');
                        thumb.src = img.urls.small;
                        thumb.setAttribute('data-full', img.urls.regular);
                        thumb.setAttribute('data-download', img.download_location);
                        thumb.alt = img.description || DEST_NAME;
                        thumb.loading = 'lazy';
                        thumb.setAttribute('data-ready', '0');

                        // Add photographer credit
                        thumb.title = `Photo by ${img.photographer.name} on Unsplash`;

                        // Lazy load handler
                        const fullImg = new Image();
                        fullImg.src = img.urls.regular;
                        fullImg.onload = () => {
                            thumb.setAttribute('data-ready', '1');

                            // Trigger download tracking (required by Unsplash API)
                            fetch('actions/fetch_images.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: `trigger_download=1&download_location=${encodeURIComponent(img.download_location)}`
                            });
                        };

                        // Add click handler for lightbox
                        thumb.addEventListener('click', () => {
                            const items = [{
                                src: img.urls.full,
                                w: img.dimensions.width,
                                h: img.dimensions.height,
                                title: `${img.description || DEST_NAME}<br><small>Photo by <a href="${img.photographer.url}?utm_source=TripMate&utm_medium=referral" target="_blank">${img.photographer.name}</a> on <a href="https://unsplash.com?utm_source=TripMate&utm_medium=referral" target="_blank">Unsplash</a></small>`
                            }];
                            const options = {
                                index: 0
                            };
                            const gallery = window.PhotoSwipe.create({
                                dataSource: items
                            }, options);
                            gallery.init();
                        });

                        galleryGrid.appendChild(thumb);
                    });

                    console.log(`Loaded ${data.images.length} images from Unsplash`);
                }

            } catch (error) {
                console.error('Failed to load dynamic gallery:', error);
                // Keep database images as fallback
            }
        }

        // Load dynamic gallery after page loads
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadDynamicGallery);
        } else {
            loadDynamicGallery();
        }
    </script>

    <!-- Structured data JSON-LD -->
    <script type="application/ld+json">
        <?php echo json_encode($structured_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
    </script>

</body>

</html>