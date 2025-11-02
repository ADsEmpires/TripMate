<?php
// Database connection
session_start();

// Create database connection directly (since dbconfig.php is not found)
$host = 'localhost';
$dbname = 'tripmate';
$username = 'root';
$password = '';

// Create a connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in, otherwise redirect to login
// NOTE: keep behavior as before — if there's no server session, redirect to login form
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Get destinations count
$destinations_query = "SELECT COUNT(*) as count FROM destinations";
$destinations_result = $conn->query($destinations_query);
$destinations_count = $destinations_result->fetch_assoc()['count'] ?? 0;

// Get user-specific statistics
// Since user_trips table doesn't exist, we'll use a default value
$trips_completed = 8; // Default value

// For demo purposes, we'll use some static stats
$recent_views = 142;
$hotels_booked = 12;

// Get user level information
$user_level_query = "SELECT * FROM user_levels WHERE user_id = ?";
$stmt = $conn->prepare($user_level_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_level_result = $stmt->get_result();
$user_level = $user_level_result->fetch_assoc();

// Set default level if not exists
if (!$user_level) {
    $user_level = [
        'level' => 'normal',
        'destinations_added' => 0,
        'tasks_completed' => 0,
        'achievements' => 'New Explorer'
    ];
}

// Calculate progress percentage (example calculation)
$progress_percentage = min(100, ($user_level['tasks_completed'] / 20) * 100);

// Get destinations data
$destinations_query = "SELECT * FROM destinations ORDER BY created_at DESC LIMIT 4";
$destinations_result = $conn->query($destinations_query);

// Get trending destinations
$trending_query = "SELECT * FROM destinations ORDER BY created_at DESC LIMIT 3";
$trending_result = $conn->query($trending_query);

// Get user's visited destinations for the map
// Since user_destinations table doesn't exist, we'll use sample data
$user_destinations = [
    ['name' => 'Darjeeling Tea Gardens', 'location' => 'Darjeeling, West Bengal', 'status' => 'visited'],
    ['name' => 'Bali', 'location' => 'Bali, Indonesia', 'status' => 'planned'],
    ['name' => 'Taj Mahal', 'location' => 'Agra, India', 'status' => 'visited']
];

// Map of location names to coordinates
$location_coordinates = [
    'Darjeeling, West Bengal' => ['lat' => 27.041, 'lng' => 88.266],
    'Bali, Indonesia' => ['lat' => -8.409, 'lng' => 115.188],
    'Agra, India' => ['lat' => 27.176, 'lng' => 78.042],
    'KOLKATA' => ['lat' => 22.572, 'lng' => 88.363],
    'hdfhg' => ['lat' => 20.593, 'lng' => 78.962],
    'bali' => ['lat' => -8.409, 'lng' => 115.188]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TripMate - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2ecc71;
            --accent: #e74c3c;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
            --warning: #f39c12;
            --danger: #dc3545;
            --success: #28a745;
            --info: #17a2b8;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        body { background-color: #f5f7f9; color: #333; line-height: 1.6; }

        /* Top navbar (keeps Sign In on top-right for unauthenticated views) */
        .top-navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            background: #fff;
            border-bottom: 1px solid #e6e6e6;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .top-navbar .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            color: var(--primary);
            font-size: 1.25rem;
        }
        .top-navbar .logo i { font-size: 1.2rem; }
        .top-navbar .nav-right {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .top-navbar .sign-in {
            padding: 8px 14px;
            border-radius: 20px;
            background: var(--primary);
            color: #fff;
            text-decoration: none;
            font-weight: 600;
        }
        .top-navbar .sign-in:hover { background: #2b82c9; }

        .container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: calc(100vh - 56px);
        }

        /* Sidebar */
        .sidebar {
            background: linear-gradient(to bottom, var(--primary), #1a5276);
            color: white;
            padding: 20px 0;
            position: sticky;
            top: 56px;
            width: 250px;
            height: calc(100vh - 56px);
            overflow-y: auto;
        }

        .brand { text-align: center; padding: 20px 0; border-bottom: 1px solid rgba(255,255,255,0.08); margin-bottom: 20px; }
        .brand h1 { font-size: 22px; display: flex; align-items: center; justify-content: center; gap: 8px; }

        .user-profile {
            text-align: center;
            padding: 18px;
            margin-bottom: 16px;
        }
        .user-avatar { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 4px solid white; margin-bottom: 12px; }
        .user-name { font-size: 18px; font-weight: 600; margin-bottom: 6px; }
        .user-level { background-color: var(--warning); color: white; padding: 4px 10px; border-radius: 20px; font-size: 12px; display: inline-block; margin-bottom: 8px; }

        .menu { list-style: none; padding: 10px 0; }
        .menu-item { padding: 12px 20px; display: flex; align-items: center; gap: 10px; cursor: pointer; color: white; transition: all 0.2s; }
        .menu-item i { width: 20px; text-align: center; }
        .menu-item.active { background: rgba(255,255,255,0.08); border-left: 4px solid rgba(255,255,255,0.12); }

        /* Main Content */
        .main-content {
            grid-column: 2;
            padding: 20px;
        }

        .header {
            display: flex;
            gap: 20px;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            margin-bottom: 20px;
        }

        .back-btn { display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 20px; background: #f0f2f5; cursor: pointer; }
        .search-bar { display: flex; align-items: center; gap: 10px; background: #f0f2f5; padding: 8px 12px; border-radius: 20px; width: 300px; }
        .search-bar input { border: none; background: transparent; outline: none; width: 100%; }

        .user-actions { display: flex; gap: 12px; align-items: center; }

        .notification-bell { position: relative; cursor: pointer; }
        .notification-badge { position: absolute; top: -6px; right: -6px; background: var(--danger); color: white; width: 18px; height: 18px; border-radius: 50%; font-size: 12px; display:flex; align-items:center; justify-content:center; }

        /* Dashboard Cards */
        .dashboard-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .card { background: white; border-radius: 8px; padding: 18px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
        .stat-card { display: flex; align-items: center; gap: 12px; }
        .stat-icon { width: 60px; height: 60px; border-radius: 10px; display:flex; align-items:center; justify-content:center; font-size: 22px; }
        .stat-info h3 { font-size: 22px; margin-bottom: 6px; }
        .stat-info p { color: var(--gray); font-size: 14px; }

        /* Map and other components simplified */
        .map-container { height: 400px; border-radius: 8px; overflow: hidden; position: relative; background-color: #e8f4fc; background-image: url('https://upload.wikimedia.org/wikipedia/commons/8/80/World_map_-_low_resolution.svg'); background-size: cover; background-position: center; }
        .map-overlay { position: absolute; top:0; left:0; right:0; bottom:0; }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .container { grid-template-columns: 1fr; }
            .sidebar { position: relative; width: 100%; height: auto; top: 0; }
            .main-content { grid-column: 1; padding: 12px; }
            .map-container { height: 300px; }
        }

        /* small helpers */
        a { color: inherit; }
        .muted { color: var(--gray); font-size: 0.95rem; }
    </style>
</head>
<body>
    <!-- Top navbar: shows Sign In link when not logged in, shows user avatar/name when logged in -->
    <header class="top-navbar">
        <div class="logo"><i class="fas fa-compass"></i><span>TripMate</span></div>
        <div class="nav-right">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="../auth/login.html" class="sign-in">Sign In</a>
            <?php else: ?>
                <div style="display:flex;align-items:center;gap:12px;">
                    <div class="muted">Hello, <?php echo htmlspecialchars($user['name'] ?? 'User'); ?></div>
                    <a href="../auth/logout.php" onclick="return confirm('Are you sure you want to logout?')" style="padding:8px 12px;border-radius:8px;background:#f0f2f5;text-decoration:none;color:#333;">Logout</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="brand">
                <h1><i class="fas fa-globe-americas"></i> TripMate</h1>
            </div>

            <div class="user-profile">
                <img src="<?php echo isset($user['profile_pic']) && !empty($user['profile_pic']) ? htmlspecialchars($user['profile_pic']) : 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=200&q=80'; ?>" alt="User Avatar" class="user-avatar">
                <h2 class="user-name"><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></h2>
                <span class="user-level"><?php echo ucfirst($user_level['level']); ?> Level</span>
                <p class="muted">Member since: <?php echo isset($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : 'N/A'; ?></p>
            </div>

            <ul class="menu">
                <li class="menu-item active"><i class="fas fa-home"></i> Dashboard</li>
                <li class="menu-item"><i class="fas fa-map-marked-alt"></i> Destinations</li>
                <li class="menu-item"><i class="fas fa-suitcase"></i> Trips</li>
                <li class="menu-item"><i class="fas fa-heart"></i> Wishlist</li>
                <li class="menu-item"><i class="fas fa-cog"></i> Settings</li>
                <li class="menu-item"><i class="fas fa-question-circle"></i> Help</li>
                <li class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <a href="#" onclick="handleLogout(event)" style="color: inherit; text-decoration: none;">Logout</a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div class="back-btn" onclick="goBack()">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back</span>
                    </div>
                </div>

                <div style="display:flex;align-items:center;gap:12px;">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search destinations, trips, etc...">
                    </div>

                    <div class="user-actions">
                        <div class="notification-bell">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge">3</span>
                        </div>
                        <img src="<?php echo isset($user['profile_pic']) && !empty($user['profile_pic']) ? htmlspecialchars($user['profile_pic']) : 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=40&q=80'; ?>" alt="User" style="width:40px;height:40px;border-radius:50%;">
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <section class="dashboard-cards" aria-label="Dashboard statistics">
                <div class="card stat-card">
                    <div class="stat-icon" style="background: rgba(52,152,219,0.12); color: var(--primary);">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo (int)$destinations_count; ?></h3>
                        <p>Destinations</p>
                    </div>
                </div>

                <div class="card stat-card">
                    <div class="stat-icon" style="background: rgba(46,204,113,0.12); color: var(--success);">
                        <i class="fas fa-suitcase"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo (int)$trips_completed; ?></h3>
                        <p>Trips Completed</p>
                    </div>
                </div>

                <div class="card stat-card">
                    <div class="stat-icon" style="background: rgba(243,156,18,0.12); color: var(--warning);">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo (int)$recent_views; ?></h3>
                        <p>Recent Views</p>
                    </div>
                </div>

                <div class="card stat-card">
                    <div class="stat-icon" style="background: rgba(231,76,60,0.12); color: var(--accent);">
                        <i class="fas fa-hotel"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo (int)$hotels_booked; ?></h3>
                        <p>Hotels Booked</p>
                    </div>
                </div>
            </section>

            <!-- Map and Recent Items -->
            <section>
                <div class="card" style="margin-bottom:20px;">
                    <h3 style="margin-bottom:12px;">Your Travel Map</h3>
                    <div class="map-container">
                        <div class="map-overlay" id="world-map"></div>
                    </div>
                </div>

                <div class="card">
                    <h3 style="margin-bottom:12px;">Recent Destinations</h3>
                    <ul style="list-style:none;">
                        <?php
                        if ($destinations_result && $destinations_result->num_rows > 0) {
                            $i = 0;
                            while ($destination = $destinations_result->fetch_assoc()) {
                                $name = htmlspecialchars($destination['name']);
                                echo "<li style='padding:10px 0;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;'>
                                        <div>
                                            <strong>{$name}</strong><div class='muted'>Location: ".htmlspecialchars($destination['location'] ?? 'N/A')."</div>
                                        </div>
                                        <div class='muted'>".date('M d, Y', strtotime($destination['created_at'] ?? 'now'))."</div>
                                      </li>";
                                $i++;
                            }
                        } else {
                            echo "<li class='muted'>No destinations found</li>";
                        }
                        ?>
                    </ul>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Initialize world map with destinations sample
        function initWorldMap() {
            const mapContainer = document.getElementById('world-map');
            if (!mapContainer) return;

            const userDestinations = <?php echo json_encode($user_destinations); ?>;
            const locationCoordinates = <?php echo json_encode($location_coordinates); ?>;

            function convertLatLngToCoords(lat, lng) {
                const width = mapContainer.offsetWidth;
                const height = mapContainer.offsetHeight;
                const x = (lng + 180) * (width / 360);
                const y = (90 - lat) * (height / 180);
                return { x, y };
            }

            userDestinations.forEach(dest => {
                let coords = locationCoordinates[dest.location] || { lat: 20, lng: 0 };
                const pixel = convertLatLngToCoords(coords.lat, coords.lng);

                const marker = document.createElement('div');
                marker.style.position = 'absolute';
                marker.style.left = pixel.x + 'px';
                marker.style.top = pixel.y + 'px';
                marker.style.width = '18px';
                marker.style.height = '18px';
                marker.style.borderRadius = '50%';
                marker.style.background = dest.status === 'visited' ? 'var(--success)' : 'var(--primary)';
                marker.title = dest.name;
                mapContainer.appendChild(marker);
            });
        }

        window.addEventListener('load', initWorldMap);

        function handleLogout(event) {
            event.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                sessionStorage.clear();
                localStorage.removeItem('tripmate_active_user_id');
                localStorage.removeItem('tripmate_active_user_name');
                window.location.href = "../auth/logout.php";
            }
        }

        function goBack() {
            window.location.href = '../main/index.html';
        }

        // initialize progress bar (if used)
        (function updateProgress(){
            try {
                const progress = <?php echo (int)$progress_percentage; ?>;
                const bar = document.querySelector('.progress-fill');
                if (bar) bar.style.width = progress + '%';
            } catch(e){}
        })();
    </script>
</body>
</html>