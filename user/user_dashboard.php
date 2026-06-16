<?php
session_start();
require_once __DIR__ . '/../database/dbconfig.php';

// ==========================================
// SERVER-SIDE AUTHENTICATION CHECK
// ==========================================
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: ../auth/login.html");
    exit(); 
}

// Get current user from session
$user_id = $_SESSION['user_id'];
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : (isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest');
$userAvatar = isset($_SESSION['user_avatar']) ? $_SESSION['user_avatar'] : '../image/default-avatar.png';

// Get user details from database
$user_data = null;
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
        $userAvatar = $user_data['profile_pic'];
    }
}

// Get user stats
$stats = [];

// Count favorites
$fav_stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_history WHERE user_id = ? AND activity_type = 'favorite'");
$fav_stmt->bind_param("i", $user_id);
$fav_stmt->execute();
$stats['favorites'] = $fav_stmt->get_result()->fetch_assoc()['count'];
$fav_stmt->close();

// Count ACTUAL trips from the bookings table
$trip_stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE user_id = ? AND booking_status != 'cancelled'");
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

// Get ACTUAL upcoming trips from the bookings table
$upcoming_trips = [];
$up_stmt = $conn->prepare("SELECT * FROM bookings WHERE user_id = ? AND start_date >= CURDATE() AND booking_status != 'cancelled' ORDER BY start_date ASC LIMIT 5");
$up_stmt->bind_param("i", $user_id);
$up_stmt->execute();
$up_result = $up_stmt->get_result();
while ($row = $up_result->fetch_assoc()) {
    $upcoming_trips[] = $row;
}
$up_stmt->close();

// Personalized recommendations
$recommendations = [];
$rec_stmt = $conn->prepare("
    SELECT d.*, 
           (SELECT COUNT(*) FROM user_history uh WHERE uh.activity_details LIKE CONCAT('%', d.id, '%') AND uh.user_id = ?) as relevance
    FROM destinations d 
    ORDER BY relevance DESC, RAND() 
    LIMIT 4
");
$rec_stmt->bind_param("i", $user_id);
$rec_stmt->execute();
$rec_result = $rec_stmt->get_result();
while ($row = $rec_result->fetch_assoc()) {
    $recommendations[] = $row;
}
$rec_stmt->close();

// Get recent activity
$recent_activity = [];
$activity_stmt = $conn->prepare("
    SELECT activity_type, activity_details, created_at 
    FROM user_history 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$activity_stmt->bind_param("i", $user_id);
$activity_stmt->execute();
$activity_result = $activity_stmt->get_result();
while ($row = $activity_result->fetch_assoc()) {
    $recent_activity[] = $row;
}
$activity_stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TripMate - User Dashboard</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brandDark: '#16034f',
                        brandDarker: '#0a0129',
                        brandOrange: '#ff6600',
                        brandOrangeDark: '#cc5200',
                        brandBg: '#f4f7fe'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        body { background-color: #f4f7fe; }
        .glass-card { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(22, 3, 79, 0.05); border: 1px solid #eef2ff; }
        .nav-gradient { background: linear-gradient(90deg, #16034f, #2a0a8a); }
        .hover-scale { transition: transform 0.2s ease; }
        .hover-scale:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(255, 102, 0, 0.15); }
    </style>
</head>

<body class="font-sans text-gray-800 antialiased">

    <nav class="nav-gradient sticky top-0 z-50 px-6 py-4 shadow-lg flex justify-between items-center text-white">
        <a href="../main/index.html" class="text-2xl font-extrabold flex items-center gap-2 hover:opacity-90 transition">
            <i class="fas fa-paper-plane text-brandOrange"></i>
            <span>Trip<span class="text-brandOrange">Mate</span></span>
        </a>
        <div class="hidden md:flex items-center gap-6">
            <a href="../search/search.html" class="font-medium hover:text-brandOrange transition"><i class="fas fa-search mr-1"></i> Search</a>
            <a href="my_trips.php" class="font-medium hover:text-brandOrange transition"><i class="fas fa-suitcase mr-1"></i> My Trips</a>
            
            <a href="user_profile.php" class="flex items-center gap-2 bg-white/10 hover:bg-white/20 border border-white/20 px-4 py-2 rounded-full transition cursor-pointer">
                <img src="<?php echo htmlspecialchars($userAvatar); ?>" alt="Profile" class="w-7 h-7 rounded-full object-cover">
                <span class="font-semibold text-sm"><?php echo htmlspecialchars($userName); ?></span>
            </a>
            
            <a href="../auth/logout.php" class="text-white hover:text-red-400 transition" title="Logout">
                <i class="fas fa-sign-out-alt text-xl"></i>
            </a>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="nav-gradient rounded-3xl p-8 text-white shadow-xl mb-8 flex flex-col md:flex-row justify-between items-center gap-6">
            <div>
                <h1 class="text-3xl md:text-4xl font-extrabold mb-2">Welcome back, <?php echo htmlspecialchars($userName); ?>! 👋</h1>
                <p class="text-blue-100 text-lg">Ready to explore your next adventure?</p>
            </div>
            <a href="../search/search.html" class="bg-brandOrange hover:bg-brandOrangeDark text-white px-6 py-3 rounded-xl font-bold transition shadow-lg flex items-center gap-2">
                <i class="fas fa-magic"></i> Plan a New Trip
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="glass-card p-6 flex items-center gap-5 hover-scale">
                <div class="w-16 h-16 rounded-full bg-blue-50 text-brandDark flex items-center justify-center text-2xl">
                    <i class="fas fa-heart"></i>
                </div>
                <div>
                    <div class="text-3xl font-extrabold text-brandDark"><?php echo $stats['favorites']; ?></div>
                    <div class="text-gray-500 font-medium">Saved Destinations</div>
                </div>
            </div>
            
            <div class="glass-card p-6 flex items-center gap-5 hover-scale border-l-4 border-brandOrange">
                <div class="w-16 h-16 rounded-full bg-orange-50 text-brandOrange flex items-center justify-center text-2xl">
                    <i class="fas fa-suitcase-rolling"></i>
                </div>
                <div>
                    <div class="text-3xl font-extrabold text-brandOrange"><?php echo $stats['trips']; ?></div>
                    <div class="text-gray-500 font-medium">Packages Booked</div>
                </div>
            </div>
            
            <div class="glass-card p-6 flex items-center gap-5 hover-scale">
                <div class="w-16 h-16 rounded-full bg-blue-50 text-brandDark flex items-center justify-center text-2xl">
                    <i class="fas fa-star"></i>
                </div>
                <div>
                    <div class="text-3xl font-extrabold text-brandDark"><?php echo $stats['reviews']; ?></div>
                    <div class="text-gray-500 font-medium">Reviews Written</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2 space-y-8">
                
                <div class="glass-card p-6">
                    <div class="flex items-center justify-between mb-6 border-b border-gray-100 pb-4">
                        <h2 class="text-xl font-bold text-brandDark flex items-center gap-2">
                            <i class="fas fa-calendar-check text-brandOrange"></i> My Upcoming Trips
                        </h2>
                        <a href="my_trips.php" class="text-sm font-semibold text-brandOrange hover:underline">View All</a>
                    </div>
                    
                    <?php if (!empty($upcoming_trips)): ?>
                        <div class="space-y-4">
                        <?php foreach ($upcoming_trips as $trip): 
                            $details = json_decode($trip['booking_details'], true);
                        ?>
                            <div class="border border-gray-200 rounded-xl p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 hover:border-brandOrange hover:shadow-md transition bg-white">
                                <div>
                                    <h3 class="font-bold text-lg text-brandDark mb-1"><?php echo htmlspecialchars($trip['booking_title']); ?></h3>
                                    <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 mb-2">
                                        <span><i class="far fa-calendar-alt text-brandOrange"></i> <?php echo date('M d', strtotime($trip['start_date'])); ?> - <?php echo date('M d, Y', strtotime($trip['end_date'])); ?></span>
                                        <span><i class="fas fa-users text-brandDark"></i> <?php echo $trip['number_of_people']; ?> Travelers</span>
                                    </div>
                                    <div class="font-bold text-gray-800">
                                        Total: <span class="text-brandOrange">₹<?php echo number_format($trip['total_amount']); ?></span>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-2 w-full sm:w-auto">
                                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide">
                                        <?php echo htmlspecialchars($trip['booking_status']); ?>
                                    </span>
                                    <a href="my_trips.php" class="text-sm bg-gray-50 hover:bg-gray-100 text-brandDark px-4 py-2 rounded-lg font-semibold transition border border-gray-200">
                                        View Details <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-10 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                            <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-3 shadow-sm">
                                <i class="fas fa-suitcase-rolling text-2xl text-gray-400"></i>
                            </div>
                            <h3 class="text-gray-800 font-semibold mb-1">No upcoming trips</h3>
                            <p class="text-gray-500 text-sm mb-4">You don't have any packages booked right now.</p>
                            <a href="../search/search.html" class="inline-block bg-brandDark text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-brandDarker transition">Browse Destinations</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="glass-card p-6">
                    <div class="flex items-center mb-6 border-b border-gray-100 pb-4">
                        <h2 class="text-xl font-bold text-brandDark flex items-center gap-2">
                            <i class="fas fa-compass text-brandOrange"></i> Recommended For You
                        </h2>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <?php foreach ($recommendations as $dest):
                            $images = json_decode($dest['image_urls'] ?? $dest['images'] ?? '[]', true);
                            $image = !empty($images) ? $images[0] : '../image/placeholder.jpg';
                            if(!preg_match('/^https?:\/\//i', $image)) $image = '../uploads/destinations/'.basename($image);
                        ?>
                            <a href="destination_details.php?id=<?php echo $dest['id']; ?>" class="group block border border-gray-200 rounded-xl overflow-hidden hover:shadow-lg transition">
                                <div class="h-40 w-full bg-cover bg-center group-hover:scale-105 transition duration-500" style="background-image: url('<?php echo htmlspecialchars($image); ?>')"></div>
                                <div class="p-4 bg-white relative z-10">
                                    <h3 class="font-bold text-brandDark text-lg"><?php echo htmlspecialchars($dest['name']); ?></h3>
                                    <p class="text-xs text-gray-500 mb-2"><i class="fas fa-map-marker-alt text-brandOrange"></i> <?php echo htmlspecialchars($dest['location']); ?></p>
                                    <div class="font-extrabold text-brandOrange">₹<?php echo number_format($dest['budget']); ?> <span class="text-xs font-normal text-gray-500">/ day</span></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <div class="space-y-8">
                
                <div class="glass-card p-6 bg-gradient-to-br from-orange-50 to-white border-orange-200 relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 text-orange-100 opacity-50 text-6xl"><i class="fas fa-lightbulb"></i></div>
                    <h2 class="text-lg font-bold text-brandOrange mb-3 flex items-center gap-2 relative z-10">
                        <i class="fas fa-lightbulb"></i> Travel Tip
                    </h2>
                    <p class="text-sm text-gray-700 leading-relaxed relative z-10 mb-4">
                        <strong class="text-brandDark">Book in Advance!</strong> <br>
                        Reserving your flight and hotel packages at least 3 months ahead can save you up to 30% on overall trip costs. Check out our price tracker to monitor drops!
                    </p>
                    <a href="../dashboard/price_tracker.html" class="inline-block text-sm font-bold text-brandOrange hover:text-brandOrangeDark transition relative z-10">
                        Try Price Tracker <i class="fas fa-arrow-right text-xs ml-1"></i>
                    </a>
                </div>

                <div class="glass-card p-6">
                    <h2 class="text-xl font-bold text-brandDark mb-6 flex items-center gap-2 border-b border-gray-100 pb-4">
                        <i class="fas fa-history text-brandOrange"></i> Recent Activity
                    </h2>
                    
                    <?php if (!empty($recent_activity)): ?>
                        <div class="space-y-5">
                        <?php foreach ($recent_activity as $activity):
                            $details = json_decode($activity['activity_details'], true);
                            $text = ''; $icon = 'fa-circle'; $color = 'text-gray-400 bg-gray-100';
                            
                            if ($activity['activity_type'] == 'search') {
                                $icon = 'fa-search'; $color = 'text-blue-500 bg-blue-50';
                                $text = 'Searched for "' . htmlspecialchars($details['query'] ?? 'destinations') . '"';
                            } elseif ($activity['activity_type'] == 'favorite') {
                                $icon = 'fa-heart'; $color = 'text-red-500 bg-red-50';
                                $text = 'Saved a destination to favorites';
                            } elseif ($activity['activity_type'] == 'trip') {
                                $icon = 'fa-ticket-alt'; $color = 'text-brandOrange bg-orange-50';
                                $text = 'Booked a new package trip';
                            } else {
                                $text = 'Platform activity';
                            }
                        ?>
                            <div class="flex gap-4">
                                <div class="mt-1">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo $color; ?>">
                                        <i class="fas <?php echo $icon; ?> text-sm"></i>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800"><?php echo $text; ?></p>
                                    <p class="text-xs text-gray-500"><?php echo date('M d, g:i A', strtotime($activity['created_at'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-gray-500 text-sm py-4">No recent activity yet.</p>
                    <?php endif; ?>
                </div>
                
            </div>
            
        </div>
    </main>

</body>
</html>