<?php
session_start();
include '../database/dbconfig.php';

if (!isset($_GET['id'])) {
    header("Location: search.html");
    exit();
}

$destination_id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM destinations WHERE id = ?");
$stmt->bind_param("i", $destination_id);
$stmt->execute();
$destination = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$destination) {
    header("Location: search.html");
    exit();
}

// Check if this destination is favorited by the user
$is_favorite = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $fav_stmt = $conn->prepare("SELECT id FROM user_history WHERE user_id = ? AND activity_type = 'favorite' AND JSON_EXTRACT(activity_details, '$.id') = ?");
    $fav_stmt->bind_param("ii", $user_id, $destination_id);
    $fav_stmt->execute();
    $is_favorite = $fav_stmt->get_result()->num_rows > 0;
    $fav_stmt->close();
}

// Log this view in history if user is logged in
if (isset($_SESSION['user_id'])) {
    $activity_details = json_encode([
        'id' => $destination['id'],
        'name' => $destination['name'],
        'type' => $destination['type'],
        'location' => $destination['location']
    ]);
    $history_stmt = $conn->prepare("INSERT INTO user_history (user_id, activity_type, activity_details) VALUES (?, 'view', ?)");
    $history_stmt->bind_param("is", $_SESSION['user_id'], $activity_details);
    $history_stmt->execute();
    $history_stmt->close();
}

// Advanced Features
$tips = isset($destination['tips']) ? $destination['tips'] : '[]';
$cuisines = isset($destination['cuisines']) ? $destination['cuisines'] : '[]';
$language = isset($destination['language']) ? $destination['language'] : '[]';
$cuisine_images = isset($destination['cuisine_images']) ? json_decode($destination['cuisine_images'], true) : [];

// Define a base URL for your project
$base_url = '/test/tripmate'; // Adjust this if your project is in a different subfolder of htdocs

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($destination['name']); ?> | TripMate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/user-profile.css">
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            width: 100vw;
            min-width: 100vw;
            min-height: 100vh;
            overflow-x: hidden;
        }

        body {
            background: linear-gradient(135deg, #f7f8fc 60%, #e9f0fb 100%);
            color: #222;
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            min-height: 100vh;
            width: 100vw;
            box-sizing: border-box;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 5%;
            background: rgba(22, 3, 79, 0.95);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            box-sizing: border-box;
            z-index: 999;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar .logo {
            display: flex;
            align-items: center;
            color: #ffffff;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .navbar .logo i {
            font-size: 1.8rem;
            margin-right: 10px;
            color: #ffffff;
        }

        .navbar .nav-right {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .navbar .nav-right a {
            background: #ff6600;
            color: white;
            padding: 8px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .navbar .nav-right a:hover {
            background: #ff7733;
            transform: translateY(-2px);
        }

        /* Top cover image */
        .cover-image {
            width: 100%;
            height: 550px;
            object-fit: cover;
            position: relative;
            z-index: 1;
            box-shadow: 0 8px 30px rgba(22, 3, 79, 0.15);
            border-bottom-left-radius: 36px;
            border-bottom-right-radius: 36px;
            filter: brightness(0.85);
        }

        .destination-header-advanced {
            margin-top: -120px;
            padding: 40px 5%;
            text-align: center;
            background: linear-gradient(to bottom, rgba(255, 255, 255, 0.92), rgba(255, 255, 255, 0.98));
            position: relative;
            z-index: 2;
            border-radius: 36px;
            box-shadow: 0 2px 16px rgba(22, 3, 79, 0.09);
            width: 90%;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            backdrop-filter: blur(10px);
        }

        .destination-header-advanced h1 {
            font-size: 3.5rem;
            margin-bottom: 15px;
            font-weight: 800;
            letter-spacing: 1px;
            color: #16034f;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .destination-header-advanced p {
            font-size: 1.4rem;
            margin-bottom: 25px;
            opacity: 0.9;
            color: #333;
            font-weight: 500;
        }

        .favorite-btn {
            background: <?php echo $is_favorite ? '#ff6600' : 'rgba(255,255,255,0.9)'; ?>;
            color: <?php echo $is_favorite ? 'white' : '#ff6600'; ?>;
            border: 2px solid #ff6600;
            padding: 12px 32px;
            border-radius: 30px;
            cursor: pointer;
            font-size: 1.15rem;
            margin-top: 20px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(255, 102, 0, 0.15);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .favorite-btn:hover {
            background: #ff6600;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 102, 0, 0.2);
        }

        .favorite-btn i {
            font-size: 1.2rem;
        }

        .destination-content-advanced {
            padding: 60px 5%;
            max-width: 1200px;
            margin: 40px auto 0;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 4px 32px rgba(0, 0, 0, 0.11);
            position: relative;
            z-index: 2;
            width: 90%;
            box-sizing: border-box;
        }

        .destination-section {
            margin-bottom: 55px;
        }

        .destination-section h2 {
            font-size: 1.55rem;
            color: #16034f;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 8px;
            display: inline-block;
            font-weight: 600;
        }

        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 22px;
            margin-top: 19px;
        }

        .gallery img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(22, 3, 79, 0.11);
        }

        .card-list {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        .card {
            background: #f7f8fc;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 20px 30px;
            min-width: 190px;
            box-shadow: 0 2px 10px rgba(160, 160, 160, 0.08);
        }

        .card h3 {
            font-size: 1.1rem;
            color: #ff6600;
            margin-bottom: 8px;
        }

        .map-section {
            width: 100%;
            height: 340px;
            background: #f5f5f5;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(22, 3, 79, 0.09);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 14px;
        }

        .tips-list {
            margin: 0;
            padding-left: 18px;
            color: #444;
        }

        .tips-list li {
            margin-bottom: 12px;
            line-height: 1.5;
            color: #444;
        }

        .cuisine-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .cuisine-card {
            background: #fff;
            border-radius: 15px;
            padding: 15px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
        }

        .cuisine-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .cuisine-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .cuisine-card h3 {
            color: #16034f;
            font-size: 1.2rem;
            margin: 10px 0;
            font-weight: 600;
        }

        .language-badge {
            display: inline-block;
            background: #16034f;
            color: #fff;
            padding: 6px 12px;
            border-radius: 20px;
            margin: 4px;
            font-size: 0.9rem;
        }

        .map-link-btn {
            display: inline-block;
            margin-top: 18px;
            padding: 10px 22px;
            background: #16034f;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
        }

        .map-link-btn:hover {
            background: #0f0252;
        }

        /* Attraction List Styles */
        .attractions-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
        }

        .attraction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background-color: #f9faff;
            border-radius: 10px;
            border: 1px solid #eef2f8;
            transition: box-shadow 0.3s ease;
        }

        .attraction-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        .attraction-name {
            font-size: 1.1rem;
            font-weight: 500;
            color: #333;
        }

        .visit-btn {
            padding: 8px 18px;
            background-color: #16034f;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: background-color 0.3s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .visit-btn:hover {
            background-color: #ff6600;
        }

        /* Responsive Styles */
        @media (max-width:1600px) {

            .cover-image,
            .navbar,
            .destination-header-advanced {
                max-width: 100vw;
                min-width: 100vw;
            }

            .destination-content-advanced {
                width: 98vw;
            }
        }

        @media (max-width:1200px) {
            .destination-content-advanced {
                max-width: 99vw;
                width: 99vw;
            }
        }

        @media (max-width:900px) {
            .destination-content-advanced {
                padding: 28px 2%;
            }

            .gallery {
                grid-template-columns: 1fr 1fr;
            }

            .cuisine-list {
                justify-content: center;
            }

            .cover-image {
                height: 400px;
            }
        }

        @media (max-width:600px) {
            .navbar {
                padding: 0.7rem 1rem;
                min-width: 100vw;
            }

            .cover-image {
                height: 300px;
            }

            .destination-header-advanced {
                padding: 25px 4%;
                margin-top: -80px;
                width: 92%;
            }

            .destination-content-advanced {
                margin-top: 25px;
                padding: 30px 4%;
                width: 92%;
            }

            .gallery {
                grid-template-columns: 1fr;
            }

            .cuisine-list {
                gap: 12px;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 12px 4%;
            }

            .navbar .logo {
                font-size: 1.3rem;
            }

            .navbar .logo i {
                font-size: 1.6rem;
            }

            .navbar .nav-right a {
                padding: 6px 15px;
                font-size: 0.9rem;
            }

            .cover-image {
                height: 400px;
            }

            .destination-header-advanced {
                margin-top: -100px;
                padding: 30px 5%;
                width: 95%;
            }

            .destination-content-advanced {
                margin-top: 30px;
                width: 95%;
            }
        }

        @media (max-width: 480px) {
            .navbar {
                padding: 10px 3%;
            }

            .navbar .logo {
                font-size: 1.2rem;
            }

            .navbar .logo i {
                font-size: 1.4rem;
                margin-right: 8px;
            }

            .navbar .nav-right {
                gap: 8px;
            }

            .navbar .nav-right a {
                padding: 6px 12px;
                font-size: 0.85rem;
            }

            .cover-image {
                height: 300px;
            }

            .destination-header-advanced {
                margin-top: -80px;
                padding: 25px 4%;
                width: 92%;
            }

            .destination-content-advanced {
                margin-top: 25px;
                padding: 30px 4%;
                width: 92%;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-compass"></i>
            <span>TripMate</span>
        </div>
        <div class="nav-right">
            <a href="../search/search.html" class="back-btn">Back to Search</a>
            <a href="user_dashboard.php" class="back-btn">Dashboard</a>
        </div>
    </nav>
    <!-- Top cover image -->
    <?php
    // Use first image as cover
    $images = [];
    if (!empty($destination['image_urls'])) {
        // Handle both JSON array and comma-separated strings
        if (strpos(trim($destination['image_urls']), '[') === 0) {
            $images = json_decode($destination['image_urls'], true);
        } else {
            $images = array_map('trim', explode(',', $destination['image_urls']));
        }
    }

    $coverImage = $base_url . '/images/no-image.jpg'; // Default fallback image
    if (!empty($images) && is_array($images)) {
        $potentialImage = trim($images[0]);
        // Construct the server file path to check if the image exists
        $image_server_path = $_SERVER['DOCUMENT_ROOT'] . $base_url . '/uploads/' . $potentialImage;

        // Check if the file exists and is not a directory
        if (file_exists($image_server_path) && !is_dir($image_server_path)) {
            // If it exists, set the correct web path
            $coverImage = $base_url . '/uploads/' . $potentialImage;
        }
    }
    ?>
    <img src="<?php echo htmlspecialchars($coverImage); ?>"
        alt="<?php echo htmlspecialchars($destination['name']); ?>"

        class="cover-image"
        onerror="this.onerror=null; this.src='<?php echo $base_url; ?>/images/no-image.jpg';">
    <div class="destination-header-advanced">
        <h1><?php echo htmlspecialchars($destination['name']); ?></h1>
        <p><?php echo htmlspecialchars($destination['location']); ?></p>
        <button class="favorite-btn" id="favoriteBtn">
            <i class="fas fa-heart"></i> <?php echo $is_favorite ? 'Favorited' : 'Add to Favorites'; ?>
        </button>
    </div>
    <div class="destination-content-advanced">
        <div class="destination-section">
            <h2>About <?php echo htmlspecialchars($destination['name']); ?></h2>
            <p><?php echo nl2br(htmlspecialchars($destination['description'])); ?></p>
        </div>
        <div class="destination-section">
            <h2>Details</h2>
            <div class="card-list">
                <div class="card">
                    <h3>Type</h3>
                    <p><?php echo htmlspecialchars($destination['type']); ?></p>
                </div>
                <div class="card">
                    <h3>Budget</h3>
                    <p>₹<?php echo number_format($destination['budget']); ?> per day</p>
                </div>
                <div class="card">
                    <h3>Best Season</h3>
                    <p><?php echo htmlspecialchars($destination['season']); ?></p>
                </div>
                <div class="card">
                    <h3>Max Travelers</h3>
                    <?php
                    $peopleArray = json_decode($destination['people'], true);
                    if ($peopleArray && is_array($peopleArray)): ?>
                        <p>
                            <?php
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
                            ?>
                        </p>
                    <?php else: ?>
                        <p><?php echo !empty($destination['people']) ? htmlspecialchars($destination['people']) : 'Not specified'; ?></p>
                    <?php endif; ?>
                </div>
                <div class="card">
                    <h3>Local Language</h3>
                    <?php
                    $languages = json_decode($language, true);
                    if ($languages && is_array($languages)):
                        foreach ($languages as $lang): ?>
                            <span class="language-badge"><?php echo htmlspecialchars($lang); ?></span>
                        <?php endforeach;
                    else: ?>
                        <span class="language-badge"><?php echo !empty($language) ? htmlspecialchars($language) : 'No info'; ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="destination-section">
            <h2>Tips for Visitors</h2>
            <?php if (!empty($tips)):
                $tipsArray = json_decode($tips, true);
                if ($tipsArray && is_array($tipsArray)): ?>
                    <ul class="tips-list">
                        <?php foreach ($tipsArray as $tip): ?>
                            <li><?php echo htmlspecialchars($tip); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <ul class="tips-list">
                        <li><?php echo htmlspecialchars($tips); ?></li>
                    </ul>
                <?php endif; ?>
            <?php else: ?>
                <div>No tips available for this destination.</div>
            <?php endif; ?>
        </div>
        <div class="destination-section">
            <h2>Local Cuisines</h2>
            <?php if (!empty($cuisines)):
                $cuisineArray = json_decode($cuisines, true);
                $cuisineImages = json_decode($destination['cuisine_images'], true) ?: [];
                if ($cuisineArray && is_array($cuisineArray)): ?>
                    <div class="cuisine-list">
                        <?php foreach ($cuisineArray as $cuisine): ?>
                            <div class="cuisine-card">
                                <?php
                                $cuisine_image_path = $base_url . '/images/no-cuisine.jpg';
                                if (isset($cuisineImages[$cuisine])) {
                                    $image_file = $cuisineImages[$cuisine];
                                    $server_path = $_SERVER['DOCUMENT_ROOT'] . $base_url . '/uploads/cuisines/' . $image_file;
                                    if (file_exists($server_path)) {
                                        $cuisine_image_path = $base_url . '/uploads/cuisines/' . $image_file;
                                    }
                                }
                                ?>
                                <img class="cuisine-img"
                                    src="<?php echo htmlspecialchars($cuisine_image_path); ?>"
                                    alt="<?php echo htmlspecialchars($cuisine); ?>"
                                    onerror="this.src='<?php echo $base_url; ?>/images/no-cuisine.jpg'">
                                <h3><?php echo htmlspecialchars($cuisine); ?></h3>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div>No cuisine information available.</div>
                <?php endif; ?>
            <?php else: ?>
                <div>No local cuisines information available.</div>
            <?php endif; ?>
        </div>
        <?php if (!empty($destination['attractions'])): ?>
            <div class="destination-section">
                <h2>Top Attractions</h2>
                <?php
                // Attempt to decode attractions as JSON
                $attractionsArray = json_decode($destination['attractions'], true);
                if ($attractionsArray && is_array($attractionsArray)):
                ?>
                    <div class="attractions-list">
                        <?php foreach ($attractionsArray as $attraction): ?>
                            <div class="attraction-item">
                                <span class="attraction-name"><?php echo htmlspecialchars($attraction); ?></span>
                                <a href="https://www.google.com/search?q=<?php echo urlencode($attraction . ' ' . $destination['name']); ?>" target="_blank" class="visit-btn">
                                    Visit <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>

                    <p><?php echo nl2br(htmlspecialchars($destination['attractions'])); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($destination['hotels'])): ?>
            <div class="destination-section">
                <h2>Recommended Hotels</h2>
                <p><?php echo nl2br(htmlspecialchars($destination['hotels'])); ?></p>
            </div>
        <?php endif; ?>
        <div class="destination-section">
            <h2>Gallery</h2>
            <div class="gallery">
                <?php
                foreach ($images as $image):
                    if (!empty(trim($image))):
                        $gallery_image_path = $base_url . '/uploads/' . trim($image);
                ?>
                        <img src="<?php echo htmlspecialchars($gallery_image_path); ?>"
                            alt="<?php echo htmlspecialchars($destination['name']); ?>"
                            onerror="this.onerror=null; this.src='<?php echo $base_url; ?>/images/no-image.jpg';">
                <?php
                    endif;
                endforeach;
                ?>
            </div>
        </div>
        <div class="destination-section">
            <h2>Location</h2>
            <div class="map-section">
                <p>
                    Map would be displayed here with coordinates:
                    <strong><?php echo htmlspecialchars($destination['latitude'] ?? ''); ?>, <?php echo htmlspecialchars($destination['longitude'] ?? ''); ?></strong>
                </p>
            </div>
            <a href="<?php echo htmlspecialchars($destination['map_link']); ?>" target="_blank" class="map-link-btn">View on Google Maps</a>
        </div>
    </div>
    <script src="js/user-profile.js"></script>
    <script>
        document.getElementById('favoriteBtn').addEventListener('click', function() {
            const isFavorite = this.classList.contains('active');
            const action = isFavorite ? 'remove' : 'add';
            const destinationId = <?php echo $destination['id']; ?>;
            fetch('actions/toggle_favorite.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `destination_id=${destinationId}&action=${action}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        if (data.action === 'added' || data.action === 'exists') {
                            this.innerHTML = '<i class="fas fa-heart"></i> Favorited';
                            this.style.background = '#ff6600';
                            this.style.color = 'white';
                            this.classList.add('active');
                        } else {
                            this.innerHTML = '<i class="far fa-heart"></i> Add to Favorites';
                            this.style.background = 'transparent';
                            this.style.color = '#ff6600';
                            this.classList.remove('active');
                        }
                    }
                });
        });
    </script>
</body>

</html>