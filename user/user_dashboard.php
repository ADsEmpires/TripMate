<?php
// user/user_dashboard.php
session_start();
require_once __DIR__ . '/../database/dbconfig.php';
require_once __DIR__ . '/../backand/image_helper.php';

// Get current user from session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : (isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest');

// Redirect if not logged in
if (!$user_id) {
    header('Location: ../auth/login.html');
    exit();
}

// Get user details from database
$user_data = null;
$profile_pic = '../image/default-avatar.png';

$user_stmt = $conn->prepare("SELECT id, name, email, profile_pic, created_at FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_stmt->close();

if ($user_data) {
    $userName = $user_data['name'];
    $_SESSION['user_name'] = $user_data['name'];
    if (!empty($user_data['profile_pic'])) {
        $profile_pic = $user_data['profile_pic'];
    }
}

// Get user stats
$stats = [
    'favorites' => 0,
    'trips' => 0,
    'reviews' => 0
];

// Count favorites
$fav_stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_history WHERE user_id = ? AND activity_type = 'favorite'");
$fav_stmt->bind_param("i", $user_id);
$fav_stmt->execute();
$stats['favorites'] = $fav_stmt->get_result()->fetch_assoc()['count'];
$fav_stmt->close();

// Count trips
$trip_stmt = $conn->prepare("SELECT COUNT(*) as count FROM upcoming_trips WHERE user_id = ? AND status = 'upcoming'");
$trip_stmt->bind_param("i", $user_id);
$trip_stmt->execute();
$stats['trips'] = $trip_stmt->get_result()->fetch_assoc()['count'];
$trip_stmt->close();

// Count reviews
$review_stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE user_id = ?");
$review_stmt->bind_param("i", $user_id);
$review_stmt->execute();
$stats['reviews'] = $review_stmt->get_result()->fetch_assoc()['count'];
$review_stmt->close();

// Get recent searches for quick suggestions
$recent_searches = [];
$search_stmt = $conn->prepare("SELECT DISTINCT search_query FROM user_search_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$search_stmt->bind_param("i", $user_id);
$search_stmt->execute();
$search_result = $search_stmt->get_result();
while ($row = $search_result->fetch_assoc()) {
    $recent_searches[] = $row['search_query'];
}
$search_stmt->close();

// Get all destinations for dropdown
$destinations = [];
$dest_stmt = $conn->prepare("SELECT id, name, location, type, budget FROM destinations ORDER BY name");
$dest_stmt->execute();
$dest_result = $dest_stmt->get_result();
while ($row = $dest_result->fetch_assoc()) {
    $destinations[] = $row;
}
$dest_stmt->close();

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
    <!-- Flatpickr for date picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!-- User Dashboard CSS -->
    <link rel="stylesheet" href="user_dashboard.css">

    <style>
        /* Additional styles for dashboard */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .dashboard-grid-full {
            grid-column: span 2;
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-grid-full {
                grid-column: span 1;
            }
        }

        .activity-timeline {
            max-height: 400px;
            overflow-y: auto;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid var(--card-border);
            transition: background 0.2s;
        }

        .activity-item:hover {
            background: var(--bg-hover);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-base);
            color: var(--secondary);
            flex-shrink: 0;
        }

        .activity-content {
            flex: 1;
        }

        .activity-text {
            color: var(--text-main);
            font-size: 0.95rem;
            margin-bottom: 0.25rem;
        }

        .activity-time {
            color: var(--text-muted);
            font-size: 0.75rem;
        }

        .review-card {
            background: var(--bg-base);
            border-radius: 1rem;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid var(--card-border);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }

        .review-destination {
            font-weight: 700;
            color: var(--text-main);
        }

        .review-stars {
            color: #fbbf24;
            font-size: 0.9rem;
        }

        .review-comment {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }

        .review-images {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
            flex-wrap: wrap;
        }

        .review-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .review-image:hover {
            transform: scale(1.05);
        }

        .trip-card {
            background: var(--bg-base);
            border-radius: 1rem;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid var(--card-border);
            transition: all 0.2s;
        }

        .trip-card.urgent {
            border-left: 4px solid #ef4444;
            background: rgba(239, 68, 68, 0.05);
        }

        .trip-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .trip-name {
            font-weight: 700;
            color: var(--text-main);
            font-size: 1rem;
        }

        .countdown {
            font-size: 0.8rem;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            background: var(--secondary);
            color: white;
        }

        .countdown.urgent {
            background: #ef4444;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        .trip-dates {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }

        .trip-details {
            display: flex;
            gap: 1rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .recommendation-card {
            background: var(--bg-surface);
            border-radius: 1rem;
            overflow: hidden;
            transition: all 0.3s;
            cursor: pointer;
            border: 1px solid var(--card-border);
        }

        .recommendation-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--secondary);
        }

        .recommendation-image {
            height: 160px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .recommendation-content {
            padding: 1rem;
        }

        .recommendation-name {
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.25rem;
        }

        .recommendation-location {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .recommendation-price {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--secondary);
        }

        .blog-card {
            background: var(--bg-base);
            border-radius: 1rem;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid var(--card-border);
        }

        .blog-title {
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }

        .blog-meta {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 0.75rem;
            display: flex;
            gap: 1rem;
        }

        .blog-content-preview {
            color: var(--text-muted);
            font-size: 0.85rem;
            line-height: 1.5;
            margin-bottom: 0.75rem;
        }

        .blog-images {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
            flex-wrap: wrap;
        }

        .blog-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 0.5rem;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--bg-card);
            border-radius: 1.5rem;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
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
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--card-border);
            border-radius: 0.75rem;
            background: var(--bg-base);
            color: var(--text-main);
            font-family: inherit;
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .rating-input input {
            display: none;
        }

        .rating-input label {
            font-size: 2rem;
            color: #cbd5e1;
            cursor: pointer;
            transition: color 0.2s;
        }

        .rating-input label:hover,
        .rating-input label:hover~label,
        .rating-input input:checked~label {
            color: #fbbf24;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background: var(--bg-base);
            color: var(--text-main);
            border: 1px solid var(--card-border);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            background: var(--bg-hover);
            border-color: var(--secondary);
        }

        .view-all-btn {
            background: none;
            border: none;
            color: var(--secondary);
            cursor: pointer;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .view-all-btn:hover {
            text-decoration: underline;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
        }

        .flash-popup {
            position: fixed;
            top: 100px;
            right: 20px;
            background: #ef4444;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            z-index: 1001;
            animation: slideInRight 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
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

        .close-flash {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 1rem;
        }
    </style>
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

            <button class="theme-toggle" id="themeToggle" aria-label="Switch dark/light mode">
                <i class="fas fa-moon"></i>
            </button>

            <!-- Profile Menu -->
            <div class="profile-menu" id="userMenu">
                <button onclick="toggleUserMenu()" class="profile-btn">
                    <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" onerror="this.src='../image/default-avatar.png'">
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

    <!-- Flash Popup for Urgent Trips -->
    <div id="flashPopup" class="flash-popup" style="display: none;">
        <i class="fas fa-bell"></i>
        <span id="flashMessage"></span>
        <button class="close-flash" onclick="document.getElementById('flashPopup').style.display='none'">×</button>
    </div>

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
                    <button onclick="openPlanTripModal()" class="btn-action">
                        <i class="fas fa-magic"></i>
                        Plan a Trip
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="text-3xl font-bold" id="favCount"><?php echo $stats['favorites']; ?></div>
                <div>Favorite Destinations</div>
                <a href="favourites.php" class="stat-link">View all <i class="fas fa-arrow-right"></i></a>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-suitcase"></i>
                </div>
                <div class="text-3xl font-bold" id="tripCount"><?php echo $stats['trips']; ?></div>
                <div>Upcoming Trips</div>
                <button onclick="scrollToUpcomingTrips()" class="stat-link">View all <i class="fas fa-arrow-right"></i></button>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="text-3xl font-bold" id="reviewCount"><?php echo $stats['reviews']; ?></div>
                <div>Reviews Written</div>
                <button onclick="scrollToReviews()" class="stat-link">View all <i class="fas fa-arrow-right"></i></button>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="text-3xl font-bold" id="searchCount">-</div>
                <div>Recent Searches</div>
                <button onclick="scrollToActivity()" class="stat-link">View all <i class="fas fa-arrow-right"></i></button>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Quick Search Section -->
            <div class="glass-card">
                <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                    <i class="fas fa-search" style="color: var(--secondary);"></i>
                    Quick Search
                </h2>

                <div class="relative mb-4">
                    <input type="text"
                        id="quickSearchInput"
                        placeholder="Where do you want to go?"
                        class="form-input pl-12"
                        onkeypress="handleSearchKeyPress(event)">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2" style="color: var(--text-muted);"></i>
                </div>

                <h3 class="text-sm font-semibold mb-3">Quick Suggestions</h3>
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
                </div>

                <h3 class="text-sm font-semibold mb-3 flex items-center gap-2">
                    <i class="fas fa-history"></i>
                    Recent Searches
                </h3>
                <div class="flex flex-wrap gap-2" id="recentSearchesContainer">
                    <?php if (!empty($recent_searches)): ?>
                        <?php foreach ($recent_searches as $search): ?>
                            <span class="search-suggestion" onclick="quickSearch('<?php echo htmlspecialchars($search); ?>')">
                                <i class="fas fa-clock"></i>
                                <?php echo htmlspecialchars($search); ?>
                            </span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No recent searches</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Travel Map -->
            <div class="glass-card">
                <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                    <i class="fas fa-map-marked-alt" style="color: var(--secondary);"></i>
                    Your Travel Map
                </h2>
                <div id="travel-map" style="height: 300px; border-radius: 1rem;"></div>
                <div class="mt-3 flex gap-3 text-sm">
                    <div class="flex items-center gap-1">
                        <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                        <span>Visited</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="w-3 h-3 bg-yellow-500 rounded-full"></span>
                        <span>Favorites</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="w-3 h-3 bg-blue-500 rounded-full"></span>
                        <span>Planned</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Recent Activity -->
            <div class="glass-card">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold flex items-center gap-2">
                        <i class="fas fa-history" style="color: var(--secondary);"></i>
                        Recent Activity
                    </h2>
                    <button class="view-all-btn" onclick="loadAllActivity()">
                        View all <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
                <div class="activity-timeline" id="activityTimeline">
                    <div class="loading">Loading activity...</div>
                </div>
            </div>

            <!-- Personalized Recommendations -->
            <div class="glass-card">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold flex items-center gap-2">
                        <i class="fas fa-star" style="color: var(--secondary);"></i>
                        Personalized For You
                    </h2>
                    <button onclick="refreshRecommendations()" class="view-all-btn">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
                <div id="recommendationsContainer" class="grid grid-cols-1 gap-4">
                    <div class="loading">Loading recommendations...</div>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- My Recent Reviews -->
            <div class="glass-card">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold flex items-center gap-2">
                        <i class="fas fa-star" style="color: var(--secondary);"></i>
                        My Recent Reviews
                    </h2>
                    <button class="view-all-btn" onclick="loadAllReviews()">
                        View all <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
                <div id="reviewsContainer">
                    <div class="loading">Loading reviews...</div>
                </div>
            </div>

            <!-- Upcoming Trips -->
            <div class="glass-card">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold flex items-center gap-2">
                        <i class="fas fa-calendar-alt" style="color: var(--secondary);"></i>
                        Upcoming Trips
                    </h2>
                    <button class="view-all-btn" onclick="openPlanTripModal()">
                        <i class="fas fa-plus"></i> Plan Trip
                    </button>
                </div>
                <div id="upcomingTripsContainer">
                    <div class="loading">Loading trips...</div>
                </div>
            </div>
        </div>

        <!-- Travel Blog Section -->
        <div class="glass-card">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold flex items-center gap-2">
                    <i class="fas fa-blog" style="color: var(--secondary);"></i>
                    Travel Blog
                </h2>
                <button class="view-all-btn" onclick="openBlogModal()">
                    <i class="fas fa-plus"></i> Write Post
                </button>
            </div>
            <div id="blogPostsContainer">
                <div class="loading">Loading blog posts...</div>
            </div>
        </div>
    </main>

    <!-- Plan Trip Modal -->
    <div id="planTripModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closePlanTripModal()">&times;</button>
            <h2 class="text-2xl font-bold mb-4">Plan Your Trip</h2>
            <form id="planTripForm" onsubmit="submitPlanTrip(event)">
                <div class="form-group">
                    <label class="form-label">Destination</label>
                    <select id="tripDestination" class="form-select" required>
                        <option value="">Select destination</option>
                        <?php foreach ($destinations as $dest): ?>
                            <option value="<?php echo $dest['id']; ?>" data-name="<?php echo htmlspecialchars($dest['name']); ?>">
                                <?php echo htmlspecialchars($dest['name'] . ' - ' . $dest['location']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Start Date</label>
                    <input type="date" id="tripStartDate" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">End Date</label>
                    <input type="date" id="tripEndDate" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Number of Travelers</label>
                    <input type="number" id="tripTravelers" min="1" value="2" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Budget (₹)</label>
                    <input type="number" id="tripBudget" min="0" class="form-input" placeholder="Optional">
                </div>
                <div class="form-group">
                    <label class="form-label">Notes (Optional)</label>
                    <textarea id="tripNotes" class="form-textarea" placeholder="Any special requirements or notes..."></textarea>
                </div>
                <button type="submit" class="btn-primary w-full">Save Trip</button>
            </form>
        </div>
    </div>

    <!-- Add Review Modal -->
    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeReviewModal()">&times;</button>
            <h2 class="text-2xl font-bold mb-4">Write a Review</h2>
            <form id="reviewForm" onsubmit="submitReview(event)" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Destination</label>
                    <select id="reviewDestination" class="form-select" required>
                        <option value="">Select destination</option>
                        <?php foreach ($destinations as $dest): ?>
                            <option value="<?php echo $dest['id']; ?>"><?php echo htmlspecialchars($dest['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Rating</label>
                    <div class="rating-input" id="ratingStars">
                        <input type="radio" name="rating" value="5" id="star5"><label for="star5">★</label>
                        <input type="radio" name="rating" value="4" id="star4"><label for="star4">★</label>
                        <input type="radio" name="rating" value="3" id="star3"><label for="star3">★</label>
                        <input type="radio" name="rating" value="2" id="star2"><label for="star2">★</label>
                        <input type="radio" name="rating" value="1" id="star1"><label for="star1">★</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Title (Optional)</label>
                    <input type="text" id="reviewTitle" class="form-input" placeholder="Summarize your experience">
                </div>
                <div class="form-group">
                    <label class="form-label">Your Review</label>
                    <textarea id="reviewComment" class="form-textarea" placeholder="Share your experience..." required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Photos (Optional)</label>
                    <input type="file" id="reviewImages" name="images[]" multiple accept="image/*" class="form-input">
                </div>
                <button type="submit" class="btn-primary w-full">Submit Review</button>
            </form>
        </div>
    </div>

    <!-- Blog Post Modal -->
    <div id="blogModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeBlogModal()">&times;</button>
            <h2 class="text-2xl font-bold mb-4">Share Your Travel Story</h2>
            <form id="blogForm" onsubmit="submitBlogPost(event)" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" id="blogTitle" class="form-input" placeholder="Catchy title for your post" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select id="blogCategory" class="form-select">
                        <option value="travel">Travel Tips</option>
                        <option value="story">Travel Story</option>
                        <option value="review">Destination Review</option>
                        <option value="food">Food & Cuisine</option>
                        <option value="adventure">Adventure</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Content</label>
                    <textarea id="blogContent" class="form-textarea" placeholder="Share your experience, tips, or memories..." required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Tags (comma separated)</label>
                    <input type="text" id="blogTags" class="form-input" placeholder="e.g., adventure, budget, family">
                </div>
                <div class="form-group">
                    <label class="form-label">Photos (Optional)</label>
                    <input type="file" id="blogImages" name="images[]" multiple accept="image/*" class="form-input">
                </div>
                <button type="submit" class="btn-primary w-full">Publish Post</button>
            </form>
        </div>
    </div>

    <script>
        // Global variables
        let map;
        let mapMarkers = [];
        let allReviews = [];
        let allTrips = [];

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeMap();
            loadActivity();
            loadRecommendations();
            loadReviews();
            loadUpcomingTrips();
            loadBlogPosts();
            checkForUrgentTrips();

            // Set up date pickers
            flatpickr("#tripStartDate", {
                minDate: "today",
                dateFormat: "Y-m-d",
                onChange: function(selectedDates, dateStr) {
                    flatpickr("#tripEndDate", {
                        minDate: dateStr,
                        dateFormat: "Y-m-d"
                    });
                }
            });

            flatpickr("#tripEndDate", {
                minDate: "today",
                dateFormat: "Y-m-d"
            });
        });

        // ============== MAP FUNCTIONS ==============
        function initializeMap() {
            if (document.getElementById('travel-map')) {
                map = L.map('travel-map', {
                    zoomControl: false
                }).setView([20, 0], 2);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);
            }
        }

        // ============== ACTIVITY FUNCTIONS ==============
        function loadActivity() {
            const container = document.getElementById('activityTimeline');

            fetch('get_activity.php?limit=10')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.activities.length > 0) {
                        container.innerHTML = data.activities.map(activity => `
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas ${activity.icon}"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-text">${escapeHtml(activity.text)}</div>
                                    <div class="activity-time">${activity.time}</div>
                                </div>
                            </div>
                        `).join('');
                        document.getElementById('searchCount').textContent = data.activities.filter(a => a.icon === 'fa-search').length;
                    } else {
                        container.innerHTML = '<div class="empty-state">No recent activity</div>';
                        document.getElementById('searchCount').textContent = '0';
                    }
                })
                .catch(error => {
                    console.error('Error loading activity:', error);
                    container.innerHTML = '<div class="empty-state">Error loading activity</div>';
                });
        }

        function loadAllActivity() {
            // Scroll to activity section and reload with more items
            document.querySelector('.activity-timeline').scrollIntoView({
                behavior: 'smooth'
            });
            loadActivity();
        }

        // ============== RECOMMENDATIONS FUNCTIONS ==============
        function loadRecommendations() {
            const container = document.getElementById('recommendationsContainer');

            fetch('get_recommendations_ai.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.recommendations.length > 0) {
                        container.innerHTML = data.recommendations.map(dest => {
                            const imageUrl = dest.image_urls && dest.image_urls.length > 0 ?
                                dest.image_urls[0] :
                                '../image/placeholder.jpg';
                            return `
                                <div class="recommendation-card" onclick="viewDestination(${dest.id})">
                                    <div class="recommendation-image" style="background-image: url('${escapeHtml(imageUrl)}')"></div>
                                    <div class="recommendation-content">
                                        <div class="recommendation-name">${escapeHtml(dest.name)}</div>
                                        <div class="recommendation-location">
                                            <i class="fas fa-map-marker-alt"></i> ${escapeHtml(dest.location)}
                                        </div>
                                        <div class="recommendation-price">₹${Number(dest.budget).toLocaleString()}/day</div>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    } else {
                        container.innerHTML = '<div class="empty-state">No recommendations available</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading recommendations:', error);
                    container.innerHTML = '<div class="empty-state">Error loading recommendations</div>';
                });
        }

        function refreshRecommendations() {
            const container = document.getElementById('recommendationsContainer');
            container.innerHTML = '<div class="loading">Refreshing recommendations...</div>';
            loadRecommendations();
        }

        // ============== REVIEWS FUNCTIONS ==============
        function loadReviews(limit = 3) {
            const container = document.getElementById('reviewsContainer');

            fetch(`get_user_reviews.php?limit=${limit}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        allReviews = data.reviews;
                        if (data.reviews.length > 0) {
                            container.innerHTML = data.reviews.map(review => `
                                <div class="review-card">
                                    <div class="review-header">
                                        <div>
                                            <div class="review-destination">${escapeHtml(review.destination_name)}</div>
                                            <div class="review-stars">
                                                ${'★'.repeat(review.rating)}${'☆'.repeat(5 - review.rating)}
                                            </div>
                                        </div>
                                        <div class="activity-time">${review.time_ago}</div>
                                    </div>
                                    ${review.title ? `<div class="font-semibold mb-1">${escapeHtml(review.title)}</div>` : ''}
                                    <div class="review-comment">${escapeHtml(review.comment)}</div>
                                    ${review.images && review.images.length ? `
                                        <div class="review-images">
                                            ${review.images.map(img => `<img src="${img}" class="review-image" onclick="viewImage('${img}')">`).join('')}
                                        </div>
                                    ` : ''}
                                </div>
                            `).join('');
                            document.getElementById('reviewCount').textContent = data.total;
                        } else {
                            container.innerHTML = '<div class="empty-state">No reviews yet. Write your first review!</div>';
                        }
                    } else {
                        container.innerHTML = '<div class="empty-state">Error loading reviews</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading reviews:', error);
                    container.innerHTML = '<div class="empty-state">Error loading reviews</div>';
                });
        }

        function loadAllReviews() {
            openReviewModalForAll();
        }

        function openReviewModal(destinationId = null) {
            const modal = document.getElementById('reviewModal');
            if (destinationId) {
                document.getElementById('reviewDestination').value = destinationId;
            }
            modal.classList.add('active');
        }

        function openReviewModalForAll() {
            // Load all reviews in a modal or redirect to reviews page
            window.location.href = 'my_reviews.php';
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').classList.remove('active');
            document.getElementById('reviewForm').reset();
        }

        function submitReview(event) {
            event.preventDefault();

            const formData = new FormData();
            formData.append('destination_id', document.getElementById('reviewDestination').value);
            formData.append('rating', document.querySelector('input[name="rating"]:checked')?.value || 0);
            formData.append('title', document.getElementById('reviewTitle').value);
            formData.append('comment', document.getElementById('reviewComment').value);

            const imagesInput = document.getElementById('reviewImages');
            if (imagesInput.files) {
                for (let i = 0; i < imagesInput.files.length; i++) {
                    formData.append('images[]', imagesInput.files[i]);
                }
            }

            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Submitting...';
            submitBtn.disabled = true;

            fetch('add_review.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast(data.message, 'success');
                        closeReviewModal();
                        loadReviews();
                    } else {
                        showToast(data.message || 'Failed to submit review', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Network error', 'error');
                })
                .finally(() => {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                });
        }

        // ============== UPCOMING TRIPS FUNCTIONS ==============
        function loadUpcomingTrips() {
            const container = document.getElementById('upcomingTripsContainer');

            fetch('get_upcoming_trips.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        allTrips = data.trips;
                        if (data.trips.length > 0) {
                            container.innerHTML = data.trips.map(trip => `
                                <div class="trip-card ${trip.is_urgent ? 'urgent' : ''}">
                                    <div class="trip-header">
                                        <div class="trip-name">${escapeHtml(trip.destination_name)}</div>
                                        <div class="countdown ${trip.is_urgent ? 'urgent' : ''}">
                                            <i class="fas fa-hourglass-half"></i> ${trip.countdown_text}
                                        </div>
                                    </div>
                                    <div class="trip-dates">
                                        <i class="fas fa-calendar"></i> ${trip.start_date_formatted} - ${trip.end_date_formatted}
                                    </div>
                                    <div class="trip-details">
                                        <span><i class="fas fa-users"></i> ${trip.travelers} traveler${trip.travelers > 1 ? 's' : ''}</span>
                                        ${trip.budget ? `<span><i class="fas fa-wallet"></i> ₹${Number(trip.budget).toLocaleString()}</span>` : ''}
                                    </div>
                                </div>
                            `).join('');
                            document.getElementById('tripCount').textContent = data.count;
                        } else {
                            container.innerHTML = '<div class="empty-state">No upcoming trips. Plan your next adventure!</div>';
                        }
                    } else {
                        container.innerHTML = '<div class="empty-state">Error loading trips</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading trips:', error);
                    container.innerHTML = '<div class="empty-state">Error loading trips</div>';
                });
        }

        function checkForUrgentTrips() {
            fetch('get_upcoming_trips.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.has_urgent) {
                        const flashPopup = document.getElementById('flashPopup');
                        const flashMessage = document.getElementById('flashMessage');
                        const urgentCount = data.urgent_trips.length;
                        const tripNames = data.urgent_trips.map(t => t.destination_name).join(', ');
                        flashMessage.innerHTML = `<strong>⚠️ Trip Reminder!</strong> Your trip to ${tripNames} starts in less than 2 days!`;
                        flashPopup.style.display = 'flex';

                        // Auto-hide after 10 seconds
                        setTimeout(() => {
                            flashPopup.style.display = 'none';
                        }, 10000);
                    }
                })
                .catch(error => console.error('Error checking urgent trips:', error));
        }

        function openPlanTripModal() {
            document.getElementById('planTripModal').classList.add('active');
        }

        function closePlanTripModal() {
            document.getElementById('planTripModal').classList.remove('active');
            document.getElementById('planTripForm').reset();
        }

        function submitPlanTrip(event) {
            event.preventDefault();

            const destinationSelect = document.getElementById('tripDestination');
            const destinationId = destinationSelect.value;
            const destinationName = destinationSelect.options[destinationSelect.selectedIndex]?.getAttribute('data-name') || '';
            const startDate = document.getElementById('tripStartDate').value;
            const endDate = document.getElementById('tripEndDate').value;
            const travelers = document.getElementById('tripTravelers').value;
            const budget = document.getElementById('tripBudget').value;
            const notes = document.getElementById('tripNotes').value;

            if (!destinationId || !startDate || !endDate) {
                showToast('Please fill all required fields', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('destination_id', destinationId);
            formData.append('destination_name', destinationName);
            formData.append('start_date', startDate);
            formData.append('end_date', endDate);
            formData.append('travelers', travelers);
            formData.append('budget', budget);
            formData.append('notes', notes);

            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Saving...';
            submitBtn.disabled = true;

            fetch('add_upcoming_trip.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast(data.message, 'success');
                        closePlanTripModal();
                        loadUpcomingTrips();
                    } else {
                        showToast(data.message || 'Failed to save trip', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Network error', 'error');
                })
                .finally(() => {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                });
        }

        function scrollToUpcomingTrips() {
            document.getElementById('upcomingTripsContainer').scrollIntoView({
                behavior: 'smooth'
            });
        }

        function scrollToReviews() {
            document.getElementById('reviewsContainer').scrollIntoView({
                behavior: 'smooth'
            });
        }

        function scrollToActivity() {
            document.getElementById('activityTimeline').scrollIntoView({
                behavior: 'smooth'
            });
        }

        // ============== BLOG FUNCTIONS ==============
        function loadBlogPosts() {
            const container = document.getElementById('blogPostsContainer');

            fetch('get_blog_posts.php?limit=5')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.posts.length > 0) {
                        container.innerHTML = data.posts.map(post => `
                            <div class="blog-card">
                                <div class="blog-title">${escapeHtml(post.title)}</div>
                                <div class="blog-meta">
                                    <span><i class="fas fa-user"></i> ${escapeHtml(post.author_name)}</span>
                                    <span><i class="fas fa-clock"></i> ${post.time_ago}</span>
                                    <span><i class="fas fa-comment"></i> ${post.comments_count} comments</span>
                                </div>
                                <div class="blog-content-preview">${escapeHtml(post.content.substring(0, 200))}${post.content.length > 200 ? '...' : ''}</div>
                                ${post.images && post.images.length ? `
                                    <div class="blog-images">
                                        ${post.images.slice(0, 3).map(img => `<img src="${img}" class="blog-image" onclick="viewImage('${img}')">`).join('')}
                                    </div>
                                ` : ''}
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<div class="empty-state">No blog posts yet. Be the first to share!</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading blog posts:', error);
                    container.innerHTML = '<div class="empty-state">Error loading blog posts</div>';
                });
        }

        function openBlogModal() {
            document.getElementById('blogModal').classList.add('active');
        }

        function closeBlogModal() {
            document.getElementById('blogModal').classList.remove('active');
            document.getElementById('blogForm').reset();
        }

        function submitBlogPost(event) {
            event.preventDefault();

            const formData = new FormData();
            formData.append('title', document.getElementById('blogTitle').value);
            formData.append('category', document.getElementById('blogCategory').value);
            formData.append('content', document.getElementById('blogContent').value);
            formData.append('tags', document.getElementById('blogTags').value);

            const imagesInput = document.getElementById('blogImages');
            if (imagesInput.files) {
                for (let i = 0; i < imagesInput.files.length; i++) {
                    formData.append('images[]', imagesInput.files[i]);
                }
            }

            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Publishing...';
            submitBtn.disabled = true;

            fetch('add_blog_post.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast(data.message, 'success');
                        closeBlogModal();
                        loadBlogPosts();
                    } else {
                        showToast(data.message || 'Failed to publish post', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Network error', 'error');
                })
                .finally(() => {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                });
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

            // Save search to history
            fetch('save_search_history.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'query=' + encodeURIComponent(query) + '&type=destination'
            }).catch(error => console.error('Error saving search:', error));

            // Redirect to search page
            window.location.href = '../search/search.html?q=' + encodeURIComponent(query);
        }

        // ============== DESTINATION FUNCTIONS ==============
        function viewDestination(destinationId) {
            window.location.href = '../destination/destination_details.php?id=' + destinationId;
        }

        // ============== UTILITY FUNCTIONS ==============
        function viewImage(imageUrl) {
            const modal = document.createElement('div');
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.width = '100%';
            modal.style.height = '100%';
            modal.style.background = 'rgba(0,0,0,0.9)';
            modal.style.zIndex = '10000';
            modal.style.display = 'flex';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';
            modal.style.cursor = 'pointer';

            const img = document.createElement('img');
            img.src = imageUrl;
            img.style.maxWidth = '90%';
            img.style.maxHeight = '90%';
            img.style.borderRadius = '1rem';

            modal.appendChild(img);
            modal.onclick = () => modal.remove();
            document.body.appendChild(modal);
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
                <span>${message}</span>
            `;

            const container = document.getElementById('toastContainer');
            if (!container) return;

            container.appendChild(toast);
            setTimeout(() => toast.classList.add('show'), 10);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // ============== USER MENU FUNCTIONS ==============
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('active');
        }

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

        // Close modals when clicking outside
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }

            // Close user dropdown if clicked outside
            const userDropdown = document.getElementById('userDropdown');
            const userMenu = document.getElementById('userMenu');
            if (userDropdown && !userDropdown.contains(event.target) && !userMenu?.contains(event.target)) {
                userDropdown.classList.remove('active');
            }
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

        // Scroll Progress Bar
        window.addEventListener('scroll', () => {
            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = height > 0 ? (winScroll / height) * 100 : 0;
            const topBar = document.getElementById("scrollBar");
            if (topBar) topBar.style.width = scrolled + "%";
        });

        // Auto-refresh data every 5 minutes
        setInterval(() => {
            loadActivity();
            loadRecommendations();
            loadUpcomingTrips();
            loadBlogPosts();
        }, 300000);
    </script>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>
</body>

</html>