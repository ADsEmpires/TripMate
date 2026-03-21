<?php
session_start();
require_once __DIR__ . '/../database/dbconfig.php';

// Get current user from session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : (isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest');
$userAvatar = isset($_SESSION['user_avatar']) ? $_SESSION['user_avatar'] : 'default-avatar.png';

// Get user details from database
$user_data = null;
if ($user_id) {
    $user_stmt = $conn->prepare("SELECT id, name, email, profile_pic, created_at FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_data = $user_result->fetch_assoc();
    $user_stmt->close();

    // Always update user name from database
    if ($user_data) {
        $userName = $user_data['name'];
        $_SESSION['user_name'] = $user_data['name'];
    }
}

// Get user stats
$stats = [
    'favorites' => 0,
    'trips' => 0,
    'reviews' => 0
];

if ($user_id) {
    // Count favorites
    $fav_stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_history WHERE user_id = ? AND activity_type = 'favorite'");
    $fav_stmt->bind_param("i", $user_id);
    $fav_stmt->execute();
    $fav_result = $fav_stmt->get_result();
    $stats['favorites'] = $fav_result->fetch_assoc()['count'];
    $fav_stmt->close();

    // Count trips planned
    $trip_stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_history WHERE user_id = ? AND activity_type = 'trip'");
    $trip_stmt->bind_param("i", $user_id);
    $trip_stmt->execute();
    $trip_result = $trip_stmt->get_result();
    $stats['trips'] = $trip_result->fetch_assoc()['count'];
    $trip_stmt->close();

    // Count reviews
    $review_stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE user_id = ?");
    $review_stmt->bind_param("i", $user_id);
    $review_stmt->execute();
    $review_result = $review_stmt->get_result();
    $stats['reviews'] = $review_result->fetch_assoc()['count'];
    $review_stmt->close();
}

// Get personalized recommendations based on user's search history and favorites
$recommendations = [];
$rec_stmt = $conn->prepare("
    SELECT d.*, 
           (SELECT COUNT(*) FROM user_history uh WHERE uh.activity_details LIKE CONCAT('%', d.id, '%') AND uh.user_id = ?) as relevance
    FROM destinations d 
    ORDER BY relevance DESC, RAND() 
    LIMIT 8
");
$rec_stmt->bind_param("i", $user_id);
$rec_stmt->execute();
$rec_result = $rec_stmt->get_result();
while ($row = $rec_result->fetch_assoc()) {
    $recommendations[] = $row;
}
$rec_stmt->close();

// Get user's favorites with full details
$favorites = [];
if ($user_id) {
    $fav_detail_stmt = $conn->prepare("
        SELECT d.*, uh.created_at as favorited_date 
        FROM user_history uh 
        JOIN destinations d ON JSON_EXTRACT(uh.activity_details, '$.id') = d.id
        WHERE uh.user_id = ? AND uh.activity_type = 'favorite'
        ORDER BY uh.created_at DESC
    ");
    $fav_detail_stmt->bind_param("i", $user_id);
    $fav_detail_stmt->execute();
    $fav_detail_result = $fav_detail_stmt->get_result();
    while ($row = $fav_detail_result->fetch_assoc()) {
        $favorites[] = $row;
    }
    $fav_detail_stmt->close();
}

// Get user's search history
$search_history = [];
if ($user_id) {
    $search_stmt = $conn->prepare("
        SELECT * FROM search_history 
        WHERE user_id = ? 
        ORDER BY search_date DESC 
        LIMIT 10
    ");
    $search_stmt->bind_param("i", $user_id);
    $search_stmt->execute();
    $search_result = $search_stmt->get_result();
    while ($row = $search_result->fetch_assoc()) {
        $search_history[] = $row;
    }
    $search_stmt->close();
}

// Get user's reviews
$user_reviews = [];
if ($user_id) {
    $user_review_stmt = $conn->prepare("
        SELECT r.*, d.name as destination_name, d.image_urls 
        FROM reviews r 
        JOIN destinations d ON r.destination_id = d.id 
        WHERE r.user_id = ? 
        ORDER BY r.created_at DESC 
        LIMIT 5
    ");
    $user_review_stmt->bind_param("i", $user_id);
    $user_review_stmt->execute();
    $user_review_result = $user_review_stmt->get_result();
    while ($row = $user_review_result->fetch_assoc()) {
        $user_reviews[] = $row;
    }
    $user_review_stmt->close();
}

// Get recent activity
$recent_activity = [];
if ($user_id) {
    $activity_stmt = $conn->prepare("
        SELECT activity_type, activity_details, created_at 
        FROM user_history 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $activity_stmt->bind_param("i", $user_id);
    $activity_stmt->execute();
    $activity_result = $activity_stmt->get_result();
    while ($row = $activity_result->fetch_assoc()) {
        $recent_activity[] = $row;
    }
    $activity_stmt->close();
}

// Get active price alerts
$price_alerts = [];
if ($user_id) {
    $alert_stmt = $conn->prepare("
        SELECT * FROM price_alerts 
        WHERE user_id = ? AND is_active = 1 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $alert_stmt->bind_param("i", $user_id);
    $alert_stmt->execute();
    $alert_result = $alert_stmt->get_result();
    while ($row = $alert_result->fetch_assoc()) {
        $price_alerts[] = $row;
    }
    $alert_stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TripMate - User Dashboard</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- User Dashboard CSS -->
    <link rel="stylesheet" href="user_dashboard.css">
</head>

<body>
    <!-- Scroll Progress Bar -->
    <div class="scroll-progress-bar" id="scrollBar"></div>

    <!-- Navigation -->
    <nav class="navbar">
        <a href="../main/index.html" class="logo">
            <i class="fa-solid fa-paper-plane"></i>
            <span class="brand-text"><span class="trip">Trip</span><span class="mate">Mate</span></span>
        </a>

        <div class="nav-right">
            <a href="../search/search.html" class="nav-link"><i class="fas fa-search"></i> Search</a>
            <a href="my_trips.php" class="nav-link"><i class="fas fa-suitcase"></i> My Trips</a>
            <a href="favourites.php" class="nav-link"><i class="fas fa-heart"></i> Favorites</a>
            <a href="../dashboard/price_tracker.html" class="nav-link"><i class="fas fa-tag"></i> Price Tracker</a>

            <button class="theme-toggle" id="themeToggle" aria-label="Switch dark/light mode">
                <i class="fas fa-moon"></i>
            </button>

            <!-- Profile Menu -->
            <div class="profile-menu" id="userMenu">
                <button onclick="toggleUserMenu()" class="profile-btn">
                    <i class="fas fa-user-circle"></i>
                    <span class="hidden md:inline"><?php echo htmlspecialchars($userName); ?></span>
                    <i class="fas fa-chevron-down text-xs"></i>
                </button>

                <div id="userDropdown" class="profile-dropdown">
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
    <main class="main-content">
        <!-- Welcome Header -->
        <div class="welcome-card">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">
                        Welcome back, <?php echo htmlspecialchars($userName); ?>! 👋
                    </h1>
                    <p>Ready to explore your next adventure?</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <button onclick="window.location.href='trip_planner.php'" class="btn-action">
                        <i class="fas fa-magic"></i>
                        Plan a Trip
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card animate-fadeInUp" style="animation-delay: 0.1s">
                <div class="stat-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="text-3xl font-bold" style="color: var(--text-main);"><?php echo $stats['favorites']; ?></div>
                <div style="color: var(--text-muted);">Favorite Destinations</div>
                <a href="favourites.php" class="stat-link">
                    View all <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="stat-card animate-fadeInUp" style="animation-delay: 0.2s">
                <div class="stat-icon">
                    <i class="fas fa-suitcase"></i>
                </div>
                <div class="text-3xl font-bold" style="color: var(--text-main);"><?php echo $stats['trips']; ?></div>
                <div style="color: var(--text-muted);">Trips Planned</div>
                <a href="my_trips.php" class="stat-link">
                    View all <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="stat-card animate-fadeInUp" style="animation-delay: 0.3s">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="text-3xl font-bold" style="color: var(--text-main);"><?php echo $stats['reviews']; ?></div>
                <div style="color: var(--text-muted);">Reviews Written</div>
                <button onclick="showMyReviews()" class="stat-link">
                    View all <i class="fas fa-arrow-right"></i>
                </button>
            </div>

            <div class="stat-card animate-fadeInUp" style="animation-delay: 0.4s">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="text-3xl font-bold" style="color: var(--text-main);"><?php echo count($search_history); ?></div>
                <div style="color: var(--text-muted);">Recent Searches</div>
                <button onclick="showSearchHistory()" class="stat-link">
                    View all <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex flex-wrap gap-3 mb-8">
            <button onclick="window.location.href='../search/search.html'" class="btn-primary">
                <i class="fas fa-search"></i>
                Search Destinations
            </button>
            <button onclick="window.location.href='../tools/budget_planner.php'" class="btn-primary" style="background: linear-gradient(135deg, #10b981, #059669);">
                <i class="fas fa-calculator"></i>
                Budget Planner
            </button>
            <button onclick="window.location.href='../dashboard/price_tracker.html'" class="btn-primary" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <i class="fas fa-tag"></i>
                Price Tracker
            </button>
            <button onclick="window.location.href='../blog/blog.php'" class="btn-primary" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                <i class="fas fa-blog"></i>
                Travel Blog
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column - 2/3 width on large screens -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Quick Search & Recent Searches -->
                <div class="glass-card">
                    <h2 class="text-xl font-bold mb-4 flex items-center gap-2" style="color: var(--text-main);">
                        <i class="fas fa-search" style="color: var(--secondary);"></i>
                        Quick Search
                    </h2>

                    <!-- Search Input -->
                    <div class="relative mb-6">
                        <input type="text"
                            id="quickSearchInput"
                            placeholder="Where do you want to go?"
                            class="form-input pl-12"
                            onkeypress="handleSearchKeyPress(event)">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2" style="color: var(--text-muted);"></i>
                    </div>

                    <!-- Quick Suggestions -->
                    <h3 class="text-sm font-semibold mb-3" style="color: var(--text-muted);">Quick Suggestions</h3>
                    <div class="flex flex-wrap gap-2 mb-6">
                        <span class="search-suggestion" onclick="quickSearch('Beach vacation')">
                            <i class="fas fa-umbrella-beach"></i> Beach
                        </span>
                        <span class="search-suggestion" onclick="quickSearch('Mountain trek')">
                            <i class="fas fa-mountain"></i> Mountain
                        </span>
                        <span class="search-suggestion" onclick="quickSearch('City break')">
                            <i class="fas fa-city"></i> City
                        </span>
                        <span class="search-suggestion" onclick="quickSearch('Cultural tour')">
                            <i class="fas fa-landmark"></i> Cultural
                        </span>
                        <span class="search-suggestion" onclick="quickSearch('Adventure sports')">
                            <i class="fas fa-bicycle"></i> Adventure
                        </span>
                        <span class="search-suggestion" onclick="quickSearch('Romantic getaway')">
                            <i class="fas fa-heart"></i> Romantic
                        </span>
                    </div>

                    <!-- Recent Searches -->
                    <h3 class="text-sm font-semibold mb-3 flex items-center gap-2" style="color: var(--text-muted);">
                        <i class="fas fa-history" style="color: var(--secondary);"></i>
                        Recent Searches
                    </h3>
                    <div class="flex flex-wrap gap-2" id="recentSearchesContainer">
                        <?php if (!empty($search_history)): ?>
                            <?php foreach ($search_history as $search): ?>
                                <span class="search-suggestion" onclick="quickSearch('<?php echo htmlspecialchars($search['search_query']); ?>')">
                                    <i class="fas fa-clock"></i>
                                    <?php echo htmlspecialchars($search['search_query']); ?>
                                </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: var(--text-muted);">No recent searches</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Personalized Recommendations -->
                <div class="glass-card">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold flex items-center gap-2" style="color: var(--text-main);">
                            <i class="fas fa-star" style="color: var(--secondary);"></i>
                            Personalized For You
                        </h2>
                        <button onclick="refreshRecommendations()" class="flex items-center gap-1" style="color: var(--secondary); background: none; border: none; cursor: pointer;">
                            <i class="fas fa-sync-alt"></i>
                            Refresh
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="recommendationsContainer">
                        <?php if (!empty($recommendations)): ?>
                            <?php foreach ($recommendations as $dest):
                                $images = json_decode($dest['image_urls'] ?? '[]', true);
                                $image = !empty($images) ? $images[0] : '../image/placeholder.jpg';

                                // Check if this destination is in favorites
                                $is_favorite = false;
                                foreach ($favorites as $fav) {
                                    if ($fav['id'] == $dest['id']) {
                                        $is_favorite = true;
                                        break;
                                    }
                                }
                            ?>
                                <div class="destination-card group" onclick="viewDestination(<?php echo $dest['id']; ?>)">
                                    <div class="destination-image" style="background-image: url('<?php echo htmlspecialchars($image); ?>')">
                                        <div class="destination-overlay <?php echo $is_favorite ? 'favorite-active' : ''; ?>"
                                            onclick="event.stopPropagation(); toggleFavorite(<?php echo $dest['id']; ?>, this)"
                                            data-destination-id="<?php echo $dest['id']; ?>">
                                            <i class="<?php echo $is_favorite ? 'fas' : 'far'; ?> fa-heart"></i>
                                        </div>
                                    </div>
                                    <div class="destination-content">
                                        <h3 class="destination-name"><?php echo htmlspecialchars($dest['name']); ?></h3>
                                        <p class="destination-location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($dest['location']); ?>
                                        </p>
                                        <div class="flex justify-between items-center">
                                            <span class="destination-price">
                                                ₹<?php echo number_format($dest['budget']); ?>
                                            </span>
                                            <span class="text-xs" style="background: var(--bg-base); color: var(--text-muted); padding: 4px 8px; border-radius: 12px;">
                                                <?php echo htmlspecialchars($dest['type']); ?>
                                            </span>
                                        </div>
                                        <div class="card-actions">
                                            <button onclick="event.stopPropagation(); quickPlanTrip(<?php echo $dest['id']; ?>)" class="btn-primary btn-small">
                                                <i class="fas fa-plus"></i> Plan Trip
                                            </button>
                                            <button onclick="event.stopPropagation(); showReviewModal(<?php echo $dest['id']; ?>, '<?php echo htmlspecialchars(addslashes($dest['name'])); ?>')" class="btn-secondary btn-small">
                                                <i class="fas fa-star"></i> Review
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-span-2 text-center py-8">
                                <p style="color: var(--text-muted);">No recommendations available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activity Timeline -->
                <div class="glass-card">
                    <h2 class="text-xl font-bold mb-6 flex items-center gap-2" style="color: var(--text-main);">
                        <i class="fas fa-history" style="color: var(--secondary);"></i>
                        Recent Activity
                    </h2>

                    <div class="space-y-4" id="activityTimeline">
                        <?php if (!empty($recent_activity)): ?>
                            <?php foreach ($recent_activity as $activity):
                                $icon = 'fa-circle';
                                $details = json_decode($activity['activity_details'], true);
                                $activity_text = '';

                                switch ($activity['activity_type']) {
                                    case 'search':
                                        $icon = 'fa-search';
                                        $activity_text = 'Searched for "' . ($details['query'] ?? 'destinations') . '"';
                                        break;
                                    case 'favorite':
                                        $icon = 'fa-heart';
                                        $activity_text = 'Added ' . ($details['name'] ?? 'a destination') . ' to favorites';
                                        break;
                                    case 'trip':
                                        $icon = 'fa-suitcase';
                                        $activity_text = 'Planned a trip to ' . ($details['name'] ?? 'a destination');
                                        break;
                                    case 'review':
                                        $icon = 'fa-star';
                                        $activity_text = 'Reviewed ' . ($details['name'] ?? 'a destination');
                                        break;
                                    default:
                                        $activity_text = $activity['activity_type'] . ' activity';
                                }
                            ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot"></div>
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-full bg-[var(--bg-base)] flex items-center justify-center" style="color: var(--secondary);">
                                            <i class="fas <?php echo $icon; ?>"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm" style="color: var(--text-main);">
                                                <?php echo htmlspecialchars($activity_text); ?>
                                            </p>
                                            <p class="text-xs" style="color: var(--text-muted);">
                                                <?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center py-4" style="color: var(--text-muted);">No recent activity</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- My Reviews -->
                <div class="glass-card">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold flex items-center gap-2" style="color: var(--text-main);">
                            <i class="fas fa-star" style="color: var(--secondary);"></i>
                            My Recent Reviews
                        </h2>
                        <a href="my_reviews.php" class="flex items-center gap-1" style="color: var(--secondary); text-decoration: none;">
                            View all <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>

                    <div class="space-y-4" id="reviewsContainer">
                        <?php if (!empty($user_reviews)): ?>
                            <?php foreach ($user_reviews as $review): ?>
                                <div class="border-b pb-4 last:border-0 last:pb-0" style="border-color: var(--card-border);">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h4 class="font-semibold" style="color: var(--text-main);"><?php echo htmlspecialchars($review['destination_name']); ?></h4>
                                            <div class="flex items-center gap-1" style="color: #ffd700;">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? '' : 'opacity-30'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <span class="text-xs" style="color: var(--text-muted);">
                                            <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm" style="color: var(--text-main);"><?php echo htmlspecialchars($review['comment']); ?></p>
                                    <div class="mt-2 flex gap-2">
                                        <button onclick="editReview(<?php echo $review['id']; ?>)" class="text-xs" style="color: var(--secondary); background: none; border: none; cursor: pointer;">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button onclick="deleteReview(<?php echo $review['id']; ?>)" class="text-xs" style="color: var(--danger); background: none; border: none; cursor: pointer;">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center py-4" style="color: var(--text-muted);">No reviews yet</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column - 1/3 width -->
            <div class="space-y-8">
                <!-- Travel Map -->
                <div class="glass-card">
                    <h2 class="text-xl font-bold mb-4 flex items-center gap-2" style="color: var(--text-main);">
                        <i class="fas fa-map-marked-alt" style="color: var(--secondary);"></i>
                        Your Travel Map
                    </h2>
                    <div id="travel-map"></div>
                    <div class="mt-3 flex gap-3 text-sm">
                        <div class="flex items-center gap-1">
                            <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                            <span style="color: var(--text-muted);">Visited</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="w-3 h-3 bg-yellow-500 rounded-full"></span>
                            <span style="color: var(--text-muted);">Favorites</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="w-3 h-3 bg-blue-500 rounded-full"></span>
                            <span style="color: var(--text-muted);">Planned</span>
                        </div>
                    </div>
                </div>

                <!-- Price Alerts Widget -->
                <div class="glass-card">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold flex items-center gap-2" style="color: var(--text-main);">
                            <i class="fas fa-bell" style="color: var(--secondary);"></i>
                            Price Alerts
                        </h2>
                        <a href="../dashboard/price_tracker.html" style="color: var(--secondary); text-decoration: none;">
                            Manage
                        </a>
                    </div>

                    <div id="priceAlertsContainer">
                        <?php if (!empty($price_alerts)): ?>
                            <?php foreach ($price_alerts as $alert): ?>
                                <div class="price-alert-item">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-semibold text-sm" style="color: var(--text-main);"><?php echo htmlspecialchars($alert['destination']); ?></h4>
                                            <p class="text-xs" style="color: var(--text-muted);">
                                                Target: ₹<?php echo number_format($alert['target_price']); ?>
                                            </p>
                                        </div>
                                        <span class="price-alert-badge">
                                            Active
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <a href="../dashboard/price_tracker.html" class="block text-center mt-3" style="color: var(--secondary); text-decoration: none;">
                                Manage Alerts
                            </a>
                        <?php else: ?>
                            <p class="text-center py-4" style="color: var(--text-muted);">
                                No active price alerts
                            </p>
                            <a href="../dashboard/price_tracker.html" class="block text-center" style="color: var(--secondary); text-decoration: none;">
                                Set up price alerts
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upcoming Trips -->
                <div class="glass-card">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold flex items-center gap-2" style="color: var(--text-main);">
                            <i class="fas fa-calendar-alt" style="color: var(--secondary);"></i>
                            Upcoming Trips
                        </h2>
                        <a href="my_trips.php" style="color: var(--secondary); text-decoration: none;">
                            View all
                        </a>
                    </div>

                    <div id="upcomingTripsContainer">
                        <?php if ($stats['trips'] > 0): ?>
                            <p class="text-center py-4" style="color: var(--text-muted);">
                                You have <?php echo $stats['trips']; ?> trip(s) planned
                            </p>
                            <button onclick="window.location.href='my_trips.php'" class="btn-primary w-full">
                                View My Trips
                            </button>
                        <?php else: ?>
                            <p class="text-center py-4" style="color: var(--text-muted);">
                                No upcoming trips
                            </p>
                            <button onclick="quickPlanTrip()" class="btn-primary w-full">
                                Plan a Trip
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Travel Tips -->
                <div class="glass-card" style="background: linear-gradient(135deg, var(--primary), var(--secondary));">
                    <h2 class="text-xl font-bold mb-4 flex items-center gap-2 text-white">
                        <i class="fas fa-lightbulb"></i>
                        Travel Tips
                    </h2>
                    <div class="space-y-3">
                        <div class="flex items-start gap-3 text-white/90">
                            <i class="fas fa-check-circle mt-1"></i>
                            <p class="text-sm">Book flights 3-4 months in advance for best prices</p>
                        </div>
                        <div class="flex items-start gap-3 text-white/90">
                            <i class="fas fa-check-circle mt-1"></i>
                            <p class="text-sm">Use our price tracker to monitor fare changes</p>
                        </div>
                        <div class="flex items-start gap-3 text-white/90">
                            <i class="fas fa-check-circle mt-1"></i>
                            <p class="text-sm">Create a budget plan before booking</p>
                        </div>
                        <div class="flex items-start gap-3 text-white/90">
                            <i class="fas fa-check-circle mt-1"></i>
                            <p class="text-sm">Check travel advisories for your destination</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Review Modal -->
    <div class="modal-overlay" id="reviewModal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2 class="text-2xl font-bold mb-4" id="reviewModalTitle" style="color: var(--text-main);">Write a Review</h2>
            <form id="reviewForm" onsubmit="submitReview(event)">
                <input type="hidden" id="reviewDestinationId" name="destination_id">

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2" style="color: var(--text-main);">Rating</label>
                    <div class="flex gap-2 text-2xl" id="ratingStars">
                        <i class="far fa-star cursor-pointer" onclick="setRating(1)"></i>
                        <i class="far fa-star cursor-pointer" onclick="setRating(2)"></i>
                        <i class="far fa-star cursor-pointer" onclick="setRating(3)"></i>
                        <i class="far fa-star cursor-pointer" onclick="setRating(4)"></i>
                        <i class="far fa-star cursor-pointer" onclick="setRating(5)"></i>
                    </div>
                    <input type="hidden" id="reviewRating" name="rating" value="0">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2" style="color: var(--text-main);">Your Review</label>
                    <textarea name="comment" rows="4" class="form-input" placeholder="Share your experience..." required></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2" style="color: var(--text-main);">Photos (Optional)</label>
                    <input type="file" name="images[]" multiple accept="image/*" class="form-input">
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn-primary">Submit Review</button>
                    <button type="button" onclick="closeReviewModal()" class="btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Plan Trip Modal -->
    <div class="modal-overlay" id="quickPlanModal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2 class="text-2xl font-bold mb-4" style="color: var(--text-main);">Quick Plan Your Trip</h2>
            <form id="quickPlanForm" onsubmit="submitQuickPlan(event)">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2" style="color: var(--text-main);">Destination</label>
                    <input type="text" id="planDestination" name="destination" class="form-input" placeholder="Where do you want to go?" required>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium mb-2" style="color: var(--text-main);">Start Date</label>
                        <input type="date" name="start_date" class="form-input" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2" style="color: var(--text-main);">End Date</label>
                        <input type="date" name="end_date" class="form-input" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2" style="color: var(--text-main);">Budget (₹)</label>
                    <input type="number" name="budget" class="form-input" placeholder="Your budget" required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2" style="color: var(--text-main);">Travelers</label>
                    <select name="travelers" class="form-input">
                        <option value="1">Just me</option>
                        <option value="2">2 people</option>
                        <option value="3">3 people</option>
                        <option value="4">4 people</option>
                        <option value="5">5+ people</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn-primary">Plan Trip</button>
                    <button type="button" onclick="closeQuickPlanModal()" class="btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notifications Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
        // Global variables
        let map;
        let mapMarkers = [];
        let currentRating = 0;

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeMap();
            checkFavoriteStatuses();
            loadPriceAlerts();
        });

        // ============== MAP FUNCTIONS ==============
        function initializeMap() {
            if (document.getElementById('travel-map')) {
                map = L.map('travel-map', {
                    zoomControl: false
                }).setView([20, 0], 2);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);

                // Add markers for visited places (example coordinates - replace with actual data)
                const visitedPlaces = [{
                        name: 'Paris',
                        coords: [48.8566, 2.3522],
                        type: 'visited'
                    },
                    {
                        name: 'Tokyo',
                        coords: [35.6762, 139.6503],
                        type: 'visited'
                    },
                    {
                        name: 'New York',
                        coords: [40.7128, -74.0060],
                        type: 'favorite'
                    },
                    {
                        name: 'Sydney',
                        coords: [-33.8688, 151.2093],
                        type: 'planned'
                    }
                ];

                visitedPlaces.forEach(place => {
                    const color = place.type === 'visited' ? 'green' :
                        place.type === 'favorite' ? 'gold' : 'blue';

                    const marker = L.circleMarker(place.coords, {
                        color: color,
                        radius: 8,
                        fillOpacity: 0.7
                    }).addTo(map).bindPopup(place.name);

                    mapMarkers.push(marker);
                });
            }
        }

        // ============== USER MENU FUNCTIONS ==============
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            if (dropdown) {
                dropdown.classList.toggle('active');
            }
        }

        // ============== SEARCH FUNCTIONS ==============
        function handleSearchKeyPress(event) {
            if (event.key === 'Enter') {
                const query = document.getElementById('quickSearchInput').value.trim();
                if (query) {
                    quickSearch(query);
                }
            }
        }

        function quickSearch(query) {
            if (!query) return;

            // Save search to history via AJAX
            fetch('../actions/save_search.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'query=' + encodeURIComponent(query)
            }).catch(error => console.error('Error saving search:', error));

            // Redirect to search page with query
            window.location.href = '../search/search.html?q=' + encodeURIComponent(query);
        }

        // ============== FAVORITE FUNCTIONS ==============
        function toggleFavorite(destinationId, element) {
            const icon = element.querySelector('i');
            const isFavorite = icon.classList.contains('fas');

            // Optimistic UI update
            if (isFavorite) {
                icon.classList.remove('fas');
                icon.classList.add('far');
                element.classList.remove('favorite-active');
            } else {
                icon.classList.remove('far');
                icon.classList.add('fas');
                element.classList.add('favorite-active');
            }

            // Send AJAX request
            fetch('../actions/toggle_favorite.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'destination_id=' + destinationId + '&action=' + (isFavorite ? 'remove' : 'add')
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast(isFavorite ? 'Removed from favorites' : 'Added to favorites', 'success');
                    } else {
                        // Revert on error
                        if (isFavorite) {
                            icon.classList.remove('far');
                            icon.classList.add('fas');
                            element.classList.add('favorite-active');
                        } else {
                            icon.classList.remove('fas');
                            icon.classList.add('far');
                            element.classList.remove('favorite-active');
                        }
                        showToast('Failed to update favorite', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Revert on error
                    if (isFavorite) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        element.classList.add('favorite-active');
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        element.classList.remove('favorite-active');
                    }
                    showToast('Network error', 'error');
                });
        }

        function checkFavoriteStatuses() {
            document.querySelectorAll('.destination-overlay').forEach(element => {
                const destinationId = element.dataset.destinationId;
                if (destinationId) {
                    fetch('../actions/check_favorite.php?destination_id=' + destinationId)
                        .then(response => response.json())
                        .then(data => {
                            const icon = element.querySelector('i');
                            if (data.is_favorite) {
                                icon.classList.remove('far');
                                icon.classList.add('fas');
                                element.classList.add('favorite-active');
                            }
                        })
                        .catch(error => console.error('Error checking favorite:', error));
                }
            });
        }

        // ============== DESTINATION FUNCTIONS ==============
        function viewDestination(destinationId) {
            window.location.href = '../destinations/destination_details.php?id=' + destinationId;
        }

        // ============== REVIEW FUNCTIONS ==============
        function showReviewModal(destinationId, destinationName) {
            document.getElementById('reviewDestinationId').value = destinationId;
            document.getElementById('reviewModalTitle').textContent = 'Review: ' + destinationName;
            document.getElementById('reviewModal').classList.add('active');
            resetRatingStars();
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').classList.remove('active');
            document.getElementById('reviewForm').reset();
            resetRatingStars();
        }

        function setRating(rating) {
            currentRating = rating;
            document.getElementById('reviewRating').value = rating;

            const stars = document.getElementById('ratingStars').children;
            for (let i = 0; i < stars.length; i++) {
                if (i < rating) {
                    stars[i].classList.remove('far');
                    stars[i].classList.add('fas');
                    stars[i].style.color = '#ffd700';
                } else {
                    stars[i].classList.remove('fas');
                    stars[i].classList.add('far');
                    stars[i].style.color = '';
                }
            }
        }

        function resetRatingStars() {
            currentRating = 0;
            document.getElementById('reviewRating').value = '0';
            const stars = document.getElementById('ratingStars').children;
            for (let i = 0; i < stars.length; i++) {
                stars[i].classList.remove('fas');
                stars[i].classList.add('far');
                stars[i].style.color = '';
            }
        }

        function submitReview(event) {
            event.preventDefault();

            const formData = new FormData(event.target);

            if (currentRating === 0) {
                showToast('Please select a rating', 'warning');
                return;
            }

            fetch('../actions/add_review.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast('Review submitted successfully!', 'success');
                        closeReviewModal();
                        // Reload reviews section
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showToast(data.message || 'Failed to submit review', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Network error', 'error');
                });
        }

        function editReview(reviewId) {
            window.location.href = 'edit_review.php?id=' + reviewId;
        }

        function deleteReview(reviewId) {
            if (confirm('Are you sure you want to delete this review?')) {
                fetch('../actions/delete_review.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'review_id=' + reviewId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showToast('Review deleted', 'success');
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            showToast('Failed to delete review', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Network error', 'error');
                    });
            }
        }

        // ============== TRIP PLANNING FUNCTIONS ==============
        function quickPlanTrip(destinationId = null) {
            if (destinationId) {
                // Fetch destination details and pre-fill
                fetch('../actions/get_destination.php?id=' + destinationId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            document.getElementById('planDestination').value = data.destination.name;
                        }
                    })
                    .catch(error => console.error('Error fetching destination:', error));
            }
            document.getElementById('quickPlanModal').classList.add('active');
        }

        function closeQuickPlanModal() {
            document.getElementById('quickPlanModal').classList.remove('active');
            document.getElementById('quickPlanForm').reset();
        }

        function submitQuickPlan(event) {
            event.preventDefault();

            const formData = new FormData(event.target);

            fetch('../actions/save_trip.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast('Trip planned successfully!', 'success');
                        closeQuickPlanModal();
                        // Update stats
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showToast(data.message || 'Failed to plan trip', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Network error', 'error');
                });
        }

        // ============== RECOMMENDATIONS FUNCTIONS ==============
        function refreshRecommendations() {
            const container = document.getElementById('recommendationsContainer');
            container.innerHTML = '<div class="col-span-2 text-center py-8"><i class="fas fa-spinner fa-spin text-2xl" style="color: var(--secondary);"></i><p class="mt-2" style="color: var(--text-muted);">Loading new recommendations...</p></div>';

            fetch('../actions/get_recommendations.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        renderRecommendations(data.recommendations);
                    } else {
                        container.innerHTML = '<div class="col-span-2 text-center py-8" style="color: var(--danger);">Failed to load recommendations</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    container.innerHTML = '<div class="col-span-2 text-center py-8" style="color: var(--danger);">Error loading recommendations</div>';
                });
        }

        function renderRecommendations(recommendations) {
            const container = document.getElementById('recommendationsContainer');
            container.innerHTML = '';

            recommendations.forEach(dest => {
                const images = dest.image_urls ? JSON.parse(dest.image_urls) : [];
                const image = images.length > 0 ? images[0] : '../image/placeholder.jpg';

                const card = document.createElement('div');
                card.className = 'destination-card group';
                card.onclick = () => viewDestination(dest.id);

                card.innerHTML = `
                    <div class="destination-image" style="background-image: url('${escapeHtml(image)}')">
                        <div class="destination-overlay" onclick="event.stopPropagation(); toggleFavorite(${dest.id}, this)" data-destination-id="${dest.id}">
                            <i class="far fa-heart"></i>
                        </div>
                    </div>
                    <div class="destination-content">
                        <h3 class="destination-name">${escapeHtml(dest.name)}</h3>
                        <p class="destination-location">
                            <i class="fas fa-map-marker-alt"></i>
                            ${escapeHtml(dest.location)}
                        </p>
                        <div class="flex justify-between items-center">
                            <span class="destination-price">
                                ₹${Number(dest.budget).toLocaleString()}
                            </span>
                            <span class="text-xs" style="background: var(--bg-base); color: var(--text-muted); padding: 4px 8px; border-radius: 12px;">
                                ${escapeHtml(dest.type)}
                            </span>
                        </div>
                        <div class="card-actions">
                            <button onclick="event.stopPropagation(); quickPlanTrip(${dest.id})" 
                                    class="btn-primary btn-small">
                                <i class="fas fa-plus"></i> Plan Trip
                            </button>
                            <button onclick="event.stopPropagation(); showReviewModal(${dest.id}, '${escapeHtml(dest.name)}')" 
                                    class="btn-secondary btn-small">
                                <i class="fas fa-star"></i> Review
                            </button>
                        </div>
                    </div>
                `;

                container.appendChild(card);
            });

            // Check favorite statuses for new cards
            setTimeout(checkFavoriteStatuses, 500);
        }

        // ============== PRICE ALERTS FUNCTIONS ==============
        function loadPriceAlerts() {
            fetch('../actions/get_price_alerts.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.alerts.length > 0) {
                        updatePriceAlertsUI(data.alerts);
                    }
                })
                .catch(error => console.error('Error loading price alerts:', error));
        }

        function updatePriceAlertsUI(alerts) {
            const container = document.getElementById('priceAlertsContainer');
            let html = '';

            alerts.forEach(alert => {
                html += `
                    <div class="price-alert-item">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-semibold text-sm" style="color: var(--text-main);">${escapeHtml(alert.destination)}</h4>
                                <p class="text-xs" style="color: var(--text-muted);">
                                    Target: ₹${Number(alert.target_price).toLocaleString()}
                                </p>
                            </div>
                            <span class="price-alert-badge">
                                Active
                            </span>
                        </div>
                    </div>
                `;
            });

            html += `<a href="../dashboard/price_tracker.html" class="block text-center mt-3" style="color: var(--secondary); text-decoration: none;">Manage Alerts</a>`;
            container.innerHTML = html;
        }

        // ============== HISTORY FUNCTIONS ==============
        function showMyReviews() {
            window.location.href = 'my_reviews.php';
        }

        function showSearchHistory() {
            window.location.href = 'search_history.php';
        }

        // ============== TOAST NOTIFICATIONS ==============
        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
                <span>${message}</span>
            `;

            container.appendChild(toast);

            // Trigger animation
            setTimeout(() => toast.classList.add('show'), 10);

            // Remove after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // ============== LOGOUT FUNCTION ==============
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

        // ============== UTILITY FUNCTIONS ==============
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Close modals when clicking outside
        document.addEventListener('click', function(event) {
            // Close user dropdown if clicked outside
            const userDropdown = document.getElementById('userDropdown');
            const userMenu = document.getElementById('userMenu');
            if (userDropdown && !userDropdown.contains(event.target) && !userMenu?.contains(event.target)) {
                userDropdown.classList.remove('active');
            }

            // Close modals if clicked on overlay
            if (event.target.classList.contains('modal-overlay')) {
                event.target.classList.remove('active');
            }
        });

        // Close button for modals
        document.querySelectorAll('.close-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const modal = this.closest('.modal-overlay');
                if (modal) {
                    modal.classList.remove('active');
                }
            });
        });

        // Auto-refresh data every 5 minutes
        setInterval(() => {
            refreshRecommendations();
            loadPriceAlerts();
        }, 300000);

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
    </script>
</body>
</html>