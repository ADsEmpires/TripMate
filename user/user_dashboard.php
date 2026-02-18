<?php
session_start();
require_once __DIR__ . '/../database/dbconfig.php';

// Get current user from session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : (isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest');
$userAvatar = isset($_SESSION['user_avatar']) ? $_SESSION['user_avatar'] : 'default-avatar.png';

// Get user recommendations
$recommendations = [];
$rec_stmt = $conn->prepare("SELECT id, name, type, location, budget, image_urls FROM destinations ORDER BY RAND() LIMIT 4");
if ($rec_stmt) {
  $rec_stmt->execute();
  $rec_result = $rec_stmt->get_result();
  while ($row = $rec_result->fetch_assoc()) {
    $recommendations[] = $row;
  }
  $rec_stmt->close();
}

// Get user travel history
$travel_history = [];
if ($user_id) {
  $history_stmt = $conn->prepare("SELECT activity_type, activity_details, created_at FROM user_history WHERE user_id = ? AND activity_type = 'trip' ORDER BY created_at DESC LIMIT 5");
  $history_stmt->bind_param("i", $user_id);
  $history_stmt->execute();
  $history_result = $history_stmt->get_result();
  while ($row = $history_result->fetch_assoc()) {
    $travel_history[] = $row;
  }
  $history_stmt->close();
}

// Get search history
$search_history = [];
if ($user_id) {
  $search_stmt = $conn->prepare("SELECT * FROM search_history WHERE user_id = ? ORDER BY search_date DESC LIMIT 5");
  $search_stmt->bind_param("i", $user_id);
  $search_stmt->execute();
  $search_result = $search_stmt->get_result();
  while ($row = $search_result->fetch_assoc()) {
    $query = $row['search_query'] ?? $row['query'] ?? $row['search_term'] ?? $row['term'] ?? '';
    $date  = $row['search_date']  ?? $row['created_at'] ?? $row['date'] ?? null;

    $search_history[] = [
      'search_query' => $query,
      'search_date'  => $date
    ];
  }
  $search_stmt->close();
}

// Get active price alerts count
$active_alerts = 0;
if ($user_id) {
  $alert_stmt = $conn->prepare("SELECT COUNT(*) as count FROM price_alerts WHERE user_id = ? AND is_active = 1");
  $alert_stmt->bind_param("i", $user_id);
  $alert_stmt->execute();
  $alert_result = $alert_stmt->get_result();
  if ($row = $alert_result->fetch_assoc()) {
    $active_alerts = $row['count'];
  }
  $alert_stmt->close();
}

// Get favorite destinations count
$favorites_count = 0;
if ($user_id) {
  $fav_stmt = $conn->prepare("SELECT COUNT(*) as count FROM favorites WHERE user_id = ?");
  $fav_stmt->bind_param("i", $user_id);
  $fav_stmt->execute();
  $fav_result = $fav_stmt->get_result();
  if ($row = $fav_result->fetch_assoc()) {
    $favorites_count = $row['count'];
  }
  $fav_stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>TripMate — User Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <!-- Leaflet JS -->
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <!-- Chart.js for price trends -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
  
  <style>
    :root {
      --primary: #16034f;
      --secondary: #ff6600;
      --accent: #00c2cb;
      --light: #f5f7fa;
      --dark: #333;
      --white: #fff;
      --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      --nav-height: 70px;
      --sidebar-width: 256px;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: var(--light);
      padding-top: var(--nav-height);
      padding-left: var(--sidebar-width);
      min-height: 100vh;
    }

    /* Navigation Bar */
    .floating-nav {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      height: var(--nav-height);
      background: linear-gradient(135deg, var(--primary), #1a0840);
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 2rem;
      z-index: 1000;
      box-shadow: var(--shadow);
    }

    .nav-logo {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 1.5rem;
      font-weight: 700;
    }

    .nav-logo i {
      color: var(--secondary);
      font-size: 1.8rem;
    }

    .nav-links {
      display: flex;
      gap: 2rem;
      list-style: none;
    }

    .nav-links a {
      color: white;
      text-decoration: none;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      border-radius: 6px;
    }

    .nav-links a:hover {
      background: rgba(255, 255, 255, 0.1);
      color: var(--secondary);
    }

    /* Sidebar */
    .floating-sidebar {
      position: fixed;
      left: 0;
      top: var(--nav-height);
      width: var(--sidebar-width);
      height: calc(100vh - var(--nav-height));
      background: linear-gradient(180deg, var(--primary), #1a0840);
      color: white;
      padding: 1.5rem 0;
      overflow-y: auto;
      z-index: 900;
      box-shadow: var(--shadow);
    }

    .sidebar-header {
      padding: 1.5rem;
      border-bottom: 2px solid rgba(255, 255, 255, 0.1);
      margin-bottom: 1rem;
    }

    .sidebar-nav {
      padding: 0 1rem;
    }

    .nav-section {
      margin-bottom: 2rem;
    }

    .nav-section-title {
      font-size: 0.75rem;
      text-transform: uppercase;
      color: rgba(255, 255, 255, 0.5);
      padding: 1rem 1rem 0.5rem;
      font-weight: 600;
      letter-spacing: 1px;
    }

    .nav-item {
      list-style: none;
      margin-bottom: 0.5rem;
    }

    .nav-item a {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.75rem 1rem;
      color: rgba(255, 255, 255, 0.8);
      text-decoration: none;
      border-radius: 6px;
      transition: all 0.3s;
      font-size: 0.95rem;
    }

    .nav-item a:hover,
    .nav-item a.active {
      background: var(--secondary);
      color: white;
      padding-left: 1.25rem;
    }

    .nav-item i {
      width: 1.25rem;
      text-align: center;
    }

    .sidebar-alert-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 1.5rem;
      height: 1.5rem;
      background: var(--secondary);
      color: white;
      border-radius: 50%;
      font-size: 0.7rem;
      font-weight: 700;
      margin-left: auto;
    }

    .sidebar-user {
      padding: 1.5rem;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      margin-top: auto;
    }

    .sidebar-user-name {
      font-weight: 600;
      font-size: 0.95rem;
    }

    .sidebar-user-level {
      font-size: 0.8rem;
      color: var(--secondary);
      margin-top: 0.25rem;
    }

    /* Profile Button */
    .profile-button {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      background: var(--secondary);
      color: white;
      border: none;
      padding: 0.5rem 1.25rem;
      border-radius: 50px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s;
    }

    .profile-button:hover {
      background: #e65c00;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 102, 0, 0.3);
    }

    .profile-menu {
      position: absolute;
      top: 100%;
      right: 0;
      background: white;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      min-width: 200px;
      margin-top: 0.5rem;
      display: none;
      z-index: 10;
      overflow: hidden;
    }

    .profile-menu.show {
      display: block;
    }

    .profile-menu a,
    .profile-menu button {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      width: 100%;
      padding: 0.75rem 1.25rem;
      border: none;
      background: none;
      text-align: left;
      cursor: pointer;
      color: var(--dark);
      text-decoration: none;
      transition: all 0.3s;
      font-size: 0.95rem;
    }

    .profile-menu a:hover,
    .profile-menu button:hover {
      background: var(--light);
      color: var(--secondary);
    }

    .profile-menu hr {
      margin: 0.5rem 0;
      border: none;
      border-top: 1px solid var(--light);
    }

    /* Main Content */
    .main-content {
      flex: 1;
      padding: 2rem;
    }

    .page-header {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      padding: 2rem;
      border-radius: 12px;
      margin-bottom: 2rem;
      box-shadow: var(--shadow);
    }

    .page-header h2 {
      font-size: 1.8rem;
      margin-bottom: 0.5rem;
    }

    .page-header p {
      opacity: 0.9;
      font-size: 0.95rem;
    }

    /* Stats Cards */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      background: white;
      padding: 1.5rem;
      border-radius: 12px;
      box-shadow: var(--shadow);
      border-left: 4px solid var(--secondary);
      transition: all 0.3s;
    }

    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
    }

    .stat-icon {
      font-size: 2rem;
      margin-bottom: 0.75rem;
    }

    .stat-value {
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--secondary);
      margin: 0.5rem 0;
    }

    .stat-label {
      color: #666;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* Cards */
    .card {
      background: white;
      border-radius: 12px;
      padding: 2rem;
      box-shadow: var(--shadow);
      margin-bottom: 2rem;
    }

    .card h3 {
      color: var(--primary);
      font-size: 1.4rem;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    /* Recommendations Grid */
    .recommendations-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 1.5rem;
    }

    .destination-card {
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: var(--shadow);
      transition: all 0.3s;
      cursor: pointer;
    }

    .destination-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    }

    .destination-image {
      width: 100%;
      height: 180px;
      background: #f0f0f0;
      object-fit: cover;
    }

    .destination-content {
      padding: 1.25rem;
    }

    .destination-name {
      font-weight: 700;
      color: var(--primary);
      margin-bottom: 0.5rem;
      font-size: 1.1rem;
    }

    .destination-location {
      color: #999;
      font-size: 0.85rem;
      margin-bottom: 1rem;
    }

    .destination-price {
      color: var(--secondary);
      font-weight: 700;
      font-size: 1.2rem;
    }

    /* Price Tracker Widget */
    .price-tracker-widget {
      background: linear-gradient(135deg, #fff9f0, #fff);
      border: 2px solid var(--secondary);
      border-radius: 12px;
      padding: 1.5rem;
      margin-bottom: 2rem;
    }

    .price-tracker-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1rem;
    }

    .price-tracker-header h3 {
      margin: 0;
      color: var(--primary);
      font-size: 1.3rem;
    }

    .price-tracker-badge {
      background: var(--secondary);
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 50px;
      font-weight: 600;
      font-size: 0.85rem;
    }

    .price-tracker-link {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: var(--secondary);
      color: white;
      padding: 0.75rem 1.5rem;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s;
      margin-top: 1rem;
    }

    .price-tracker-link:hover {
      background: #e65c00;
      transform: translateX(4px);
    }

    /* Map */
    #map {
      height: 320px;
      border-radius: 12px;
      overflow: hidden;
    }

    /* Responsive */
    @media (max-width: 1024px) {
      body {
        padding-left: 0;
      }

      .floating-sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s;
      }

      .floating-sidebar.open {
        transform: translateX(0);
      }

      .nav-links {
        display: none;
      }

      .sidebar-toggle {
        display: block;
      }
    }

    @media (max-width: 768px) {
      .floating-nav {
        padding: 0 1rem;
      }

      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .recommendations-grid {
        grid-template-columns: 1fr;
      }

      .page-header h2 {
        font-size: 1.4rem;
      }
    }

    .leaflet-control-zoom {
      display: none !important;
    }

    .empty-state {
      text-align: center;
      padding: 3rem 1rem;
      color: #999;
    }

    .empty-state i {
      font-size: 3rem;
      margin-bottom: 1rem;
      opacity: 0.5;
    }

    .loading-spinner {
      display: inline-block;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
</head>

<body class="bg-gray-50">
  <!-- Navigation Bar -->
  <nav class="floating-nav">
    <div class="nav-logo">
      <i class="fas fa-compass"></i>
      <span>TripMate</span>
    </div>

    <ul class="nav-links" style="display: none;">
      <li><a href="../main/index.html"><i class="fas fa-home"></i> Home</a></li>
      <li><a href="../search/search.html"><i class="fas fa-search"></i> Search</a></li>
      <li><a href="../user/my_trips.html"><i class="fas fa-suitcase"></i> My Trips</a></li>
    </ul>

    <div style="display: flex; align-items: center; gap: 1.5rem;">
      <button class="sidebar-toggle" onclick="toggleSidebar()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; display: none;">
        <i class="fas fa-bars"></i>
      </button>

      <div style="position: relative;">
        <button class="profile-button" id="profileBtn" onclick="toggleProfileMenu()">
          <i class="fas fa-user-circle"></i>
          <span id="profileName" style="display: none;"><?php echo htmlspecialchars($userName); ?></span>
        </button>

        <div class="profile-menu" id="profileMenu">
          <a href="../user/user_profile.php">
            <i class="fas fa-user"></i>
            My Profile
          </a>
          <a href="../user/favourites.html">
            <i class="fas fa-heart"></i>
            Favorites
          </a>
          <a href="../dashboard/price_tracker.html">
            <i class="fas fa-tag"></i>
            Price Tracker
          </a>
          <hr>
          <button onclick="logout()" style="color: #c33;">
            <i class="fas fa-sign-out-alt"></i>
            Logout
          </button>
        </div>
      </div>
    </div>
  </nav>

  <!-- Sidebar -->
  <aside class="floating-sidebar">
    <div class="sidebar-header">
      <h3 style="margin: 0; font-size: 1.1rem;">Navigation</h3>
    </div>

    <nav class="sidebar-nav">
      <!-- Main Section -->
      <div class="nav-section">
        <div class="nav-section-title">Main</div>
        <ul>
          <li class="nav-item">
            <a href="user_dashboard.php" class="active">
              <i class="fas fa-th-large"></i>
              Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a href="../search/search.html">
              <i class="fas fa-search"></i>
              Search Destinations
            </a>
          </li>
        </ul>
      </div>

      <!-- Travel Section -->
      <div class="nav-section">
        <div class="nav-section-title">Travel</div>
        <ul>
          <li class="nav-item">
            <a href="my_trips.html">
              <i class="fas fa-suitcase"></i>
              My Trips
            </a>
          </li>
          <li class="nav-item">
            <a href="favourites.html">
              <i class="fas fa-heart"></i>
              Favorites
              <?php if ($favorites_count > 0): ?>
                <span class="sidebar-alert-badge"><?php echo $favorites_count; ?></span>
              <?php endif; ?>
            </a>
          </li>
        </ul>
      </div>

      <!-- Planning Section -->
      <div class="nav-section">
        <div class="nav-section-title">Planning</div>
        <ul>
          <li class="nav-item">
            <a href="../dashboard/price_tracker.html">
              <i class="fas fa-tag"></i>
              💰 Price Tracker
              <?php if ($active_alerts > 0): ?>
                <span class="sidebar-alert-badge"><?php echo $active_alerts; ?></span>
              <?php endif; ?>
            </a>
          </li>
          <li class="nav-item">
            <a href="../tools/budget_planner.html">
              <i class="fas fa-calculator"></i>
              Budget Planner
            </a>
          </li>
          <li class="nav-item">
            <a href="user_profile.php">
              <i class="fas fa-user"></i>
              My Profile
            </a>
          </li>
        </ul>
      </div>

      <!-- More Section -->
      <div class="nav-section">
        <div class="nav-section-title">More</div>
        <ul>
          <li class="nav-item">
            <a href="../blog/blog.html">
              <i class="fas fa-blog"></i>
              Blog & Tips
            </a>
          </li>
          <li class="nav-item">
            <a href="../main/index.html">
              <i class="fas fa-home"></i>
              Back to Home
            </a>
          </li>
        </ul>
      </div>
    </nav>

    <div class="sidebar-user">
      <div class="sidebar-user-name" id="sidebarUserName"><?php echo htmlspecialchars($userName); ?></div>
      <div class="sidebar-user-level" id="sidebarUserLevel">Member Account</div>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="main-content">
    <!-- Page Header -->
    <div class="page-header">
      <h2>Welcome back, <span id="welcomeName"><?php echo htmlspecialchars($userName); ?></span>! 👋</h2>
      <p>Here's what's happening with your travels today.</p>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-label">Active Price Alerts</div>
        <div class="stat-value"><?php echo $active_alerts; ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">❤️</div>
        <div class="stat-label">Favorite Places</div>
        <div class="stat-value"><?php echo $favorites_count; ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🗺️</div>
        <div class="stat-label">Destinations Explored</div>
        <div class="stat-value"><?php echo count($travel_history); ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🔍</div>
        <div class="stat-label">Recent Searches</div>
        <div class="stat-value"><?php echo count($search_history); ?></div>
      </div>
    </div>

    <!-- Price Tracker Widget -->
    <div class="price-tracker-widget">
      <div class="price-tracker-header">
        <h3>💰 Price Tracker</h3>
        <span class="price-tracker-badge"><?php echo $active_alerts; ?> Active Alerts</span>
      </div>
      <p style="color: #666; margin: 0 0 1rem 0;">
        Monitor flight and hotel prices for your favorite destinations. Get instant alerts when prices drop!
      </p>
      <a href="../dashboard/price_tracker.html" class="price-tracker-link">
        <i class="fas fa-arrow-right"></i>
        Go to Price Tracker
      </a>
    </div>

    <!-- Personalized Recommendations -->
    <div class="card">
      <h3>
        <i class="fas fa-star"></i>
        Personalized Recommendations
      </h3>
      <?php if (count($recommendations) > 0): ?>
        <div class="recommendations-grid">
          <?php foreach ($recommendations as $dest): ?>
            <div class="destination-card" onclick="window.location.href='../search/search.html?dest=<?php echo urlencode($dest['name']); ?>'">
              <?php 
                $image_url = 'placeholder.png';
                if (!empty($dest['image_urls'])) {
                  try {
                    $images = json_decode($dest['image_urls'], true);
                    if (is_array($images) && count($images) > 0) {
                      $image_url = $images[0];
                    }
                  } catch (Exception $e) {}
                }
              ?>
              <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($dest['name']); ?>" class="destination-image" onerror="this.src='../image/placeholder.png'">
              <div class="destination-content">
                <div class="destination-name"><?php echo htmlspecialchars($dest['name']); ?></div>
                <div class="destination-location">
                  <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($dest['location']); ?>
                </div>
                <div class="destination-price">
                  ₹<?php echo number_format($dest['budget']); ?>/person
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <i class="fas fa-inbox"></i>
          <p>No recommendations available yet. Try searching for destinations!</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Travel Map -->
    <div class="card">
      <h3>
        <i class="fas fa-map"></i>
        Your Travel Map
      </h3>
      <div id="map"></div>
    </div>

    <!-- Travel History -->
    <div class="card">
      <h3>
        <i class="fas fa-history"></i>
        Recent Travel History
      </h3>
      <?php if (count($travel_history) > 0): ?>
        <div style="space-y: 1rem;">
          <?php foreach ($travel_history as $history): ?>
            <div style="padding: 1rem; border-bottom: 1px solid #eee; display: flex; justify-content: space-between;">
              <div>
                <div style="font-weight: 600; color: var(--primary);"><?php echo htmlspecialchars($history['activity_type']); ?></div>
                <div style="font-size: 0.85rem; color: #999; margin-top: 0.25rem;">
                  <?php echo htmlspecialchars($history['activity_details'] ?? 'Trip logged'); ?>
                </div>
              </div>
              <div style="color: #999; font-size: 0.85rem;">
                <?php echo date('M d, Y', strtotime($history['created_at'])); ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <i class="fas fa-suitcase"></i>
          <p>No travel history yet. Start exploring destinations!</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Recent Searches -->
    <div class="card">
      <h3>
        <i class="fas fa-search"></i>
        Recent Searches
      </h3>
      <?php if (count($search_history) > 0): ?>
        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
          <?php foreach ($search_history as $search): ?>
            <a href="../search/search.html?q=<?php echo urlencode($search['search_query']); ?>" 
               style="display: inline-block; padding: 0.5rem 1.25rem; background: var(--light); color: var(--primary); border-radius: 50px; text-decoration: none; font-size: 0.9rem; transition: all 0.3s;" 
               onmouseover="this.style.background='var(--secondary)'; this.style.color='white';" 
               onmouseout="this.style.background='var(--light)'; this.style.color='var(--primary)';">
              <?php echo htmlspecialchars($search['search_query']); ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <i class="fas fa-search"></i>
          <p>No search history. Try searching for your next destination!</p>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <script>
    // Initialize Leaflet Map
    const map = L.map('map', { zoomControl: false }).setView([20, 0], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
      maxZoom: 19
    }).addTo(map);

    // Toggle Profile Menu
    function toggleProfileMenu() {
      const menu = document.getElementById('profileMenu');
      menu.classList.toggle('show');
    }

    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
      const menu = document.getElementById('profileMenu');
      const btn = document.getElementById('profileBtn');
      if (!menu.contains(event.target) && !btn.contains(event.target)) {
        menu.classList.remove('show');
      }
    });

    // Toggle Sidebar
    function toggleSidebar() {
      const sidebar = document.querySelector('.floating-sidebar');
      sidebar.classList.toggle('open');
    }

    // Logout
    function logout() {
      if (confirm('Are you sure you want to logout?')) {
        fetch('../auth/logout.php').then(() => {
          window.location.href = '../main/index.html';
        });
      }
    }

    // Show profile name on larger screens
    window.addEventListener('resize', function() {
      const span = document.querySelector('.profile-button span');
      if (window.innerWidth > 768) {
        span.style.display = 'inline';
      } else {
        span.style.display = 'none';
      }
    });

    // Initialize on load
    document.addEventListener('DOMContentLoaded', function() {
      // Set sidebar toggle button visibility
      const toggle = document.querySelector('.sidebar-toggle');
      if (window.innerWidth <= 1024) {
        toggle.style.display = 'block';
      }
    });
  </script>
</body>

</html>