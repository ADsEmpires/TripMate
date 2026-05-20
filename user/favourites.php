<?php
// user/favourites.php
require_once __DIR__ . '/session_init.php'; // Initialize session management
require_once __DIR__ . '/../database/dbconfig.php';
require_once __DIR__ . '/../backand/image_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.html');
    exit();
}

$user_id = $_SESSION['user_id'];
// Dynamically detect base URL - works on any server
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$script_dir = dirname($_SERVER['SCRIPT_NAME']); // /test/tripmate/user
$base_url = $protocol . "://" . $host . str_replace('/user', '', $script_dir); // Remove /user to get /test/tripmate

// Get favorite destinations - works with both tables
$stmt = $conn->prepare("
    SELECT d.* 
    FROM (
        SELECT DISTINCT 
            CASE 
                WHEN activity_type = 'favorite' THEN activity_details 
                ELSE destination_id 
            END as dest_id
        FROM (
            SELECT activity_type, activity_details, NULL as destination_id FROM user_history 
            WHERE user_id = ? AND activity_type = 'favorite'
            UNION ALL
            SELECT 'favorite' as activity_type, NULL as activity_details, destination_id FROM favorites 
            WHERE user_id = ?
        ) as combined
    ) as favs
    JOIN destinations d ON d.id = favs.dest_id
    ORDER BY d.name
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$favorites = [];
while ($row = $result->fetch_assoc()) {
    // ============================================
    // FIXED: Process images from 'images' column (JSON array)
    // ============================================
    $image_filenames = [];
    if (!empty($row['images'])) {
        $decoded_images = json_decode($row['images'], true);
        if (is_array($decoded_images) && !empty($decoded_images)) {
            $image_filenames = $decoded_images;
        }
    }

    // Get cover image URL
    $cover_image = $base_url . '/images/no-image.jpg';
    if (!empty($image_filenames)) {
        $cover_image = $base_url . '/uploads/destinations/' . basename($image_filenames[0]);
    } elseif (!empty($row['profile_pic'])) {
        $cover_image = $base_url . '/uploads/destinations/' . basename($row['profile_pic']);
    }

    // Store processed image URL
    $row['cover_image'] = $cover_image;
    $row['image_filenames'] = $image_filenames;

    // Parse other JSON fields if needed
    if (isset($row['attractions']) && is_string($row['attractions'])) {
        $row['attractions'] = json_decode($row['attractions'], true) ?: [];
    }

    $favorites[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Favorites - TripMate</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Session Management Meta Tags -->
    <meta name="user-id" content="<?php echo htmlspecialchars($_SESSION['user_id']); ?>">
    <meta name="user-name" content="<?php echo htmlspecialchars($_SESSION['user_name']); ?>">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Session Management Scripts -->
    <script src="session-keepalive.js"></script>
    <script src="session-sync.js"></script>
    <script src="auto-logout.js"></script>

    <style>
        .nav-blue-bg {
            background-color: #1e3a8a;
        }

        .orange-button {
            background-color: #ea580c;
        }

        .orange-button:hover {
            background-color: #c2410c;
        }

        .floating-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        body {
            padding-top: 64px;
        }

        .favorite-btn i.fas.fa-heart {
            color: #ff4444;
        }

        .favorite-btn i.far.fa-heart {
            color: #666;
        }

        .destination-card {
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .destination-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .favorite-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            padding: 0.5rem;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .favorite-btn:hover {
            background: rgba(255, 68, 68, 0.1);
            transform: scale(1.1);
        }

        .favorite-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .destination-image {
            width: 100%;
            height: 200px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
    </style>
</head>

<body class="bg-gray-50 user-logged-in" data-user-id="<?php echo htmlspecialchars($_SESSION['user_id']); ?>">
    <!-- Navigation Bar -->
    <nav class="floating-nav nav-blue-bg text-white p-4 flex justify-between items-center">
        <div class="flex items-center">
            <i class="fas fa-compass text-2xl mr-2 text-orange-500"></i>
            <h1 class="text-xl font-bold">TripMate</h1>
        </div>
        <div class="flex items-center space-x-4">
            <a href="user_dashboard.php" class="hover:text-orange-300 transition">
                <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
            </a>
            <a href="../search/search.html" class="hover:text-orange-300 transition">
                <i class="fas fa-search mr-1"></i> Search
            </a>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">My Favorite Destinations</h1>
            <p class="text-gray-600 mt-2">Your saved places to visit</p>
        </div>

        <?php if (empty($favorites)): ?>
            <div class="text-center py-16 bg-white rounded-lg shadow">
                <i class="fas fa-heart text-6xl text-gray-300 mb-4"></i>
                <h2 class="text-2xl font-semibold text-gray-700 mb-2">No favorites yet</h2>
                <p class="text-gray-500 mb-6">Start exploring and save your favorite destinations!</p>
                <a href="../search/search.html" class="inline-block orange-button text-white px-6 py-3 rounded-lg font-semibold hover:bg-orange-700 transition">
                    <i class="fas fa-search mr-2"></i> Explore Destinations
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="favoritesGrid">
                <?php foreach ($favorites as $dest): ?>
                    <div class="destination-card bg-white rounded-lg shadow overflow-hidden" data-id="<?php echo $dest['id']; ?>">
                        <!-- FIXED: Using cover_image from processed data -->
                        <div class="destination-image" style="background-image: url('<?php echo htmlspecialchars($dest['cover_image']); ?>');"></div>
                        <div class="p-4">
                            <h3 class="font-semibold text-lg mb-1"><?php echo htmlspecialchars($dest['name']); ?></h3>
                            <p class="text-gray-600 text-sm mb-2">
                                <i class="fas fa-map-marker-alt text-orange-500 mr-1"></i>
                                <?php echo htmlspecialchars($dest['location']); ?>
                            </p>
                            <p class="text-gray-700 text-sm mb-3 line-clamp-2"><?php echo htmlspecialchars(substr($dest['description'], 0, 100)) . '...'; ?></p>
                            <div class="flex justify-between items-center">
                                <span class="text-orange-600 font-bold">₹<?php echo number_format($dest['budget']); ?>/day</span>
                                <div class="flex space-x-2">
                                    <button class="favorite-btn" data-destination-id="<?php echo $dest['id']; ?>" title="Remove from favorites">
                                        <i class="fas fa-heart text-red-500"></i>
                                    </button>
                                    <a href="destination_details.php?id=<?php echo $dest['id']; ?>" class="text-blue-600 hover:text-blue-800 p-2">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
        // Favorite button functionality
        document.querySelectorAll('.favorite-btn').forEach(btn => {
            btn.addEventListener('click', async function(e) {
                e.preventDefault();
                e.stopPropagation();

                const destinationId = this.dataset.destinationId;
                const heartIcon = this.querySelector('i');
                const card = this.closest('.destination-card');

                // Disable button during request
                this.disabled = true;

                try {
                    const formData = new FormData();
                    formData.append('destination_id', destinationId);
                    formData.append('action', 'remove');

                    const response = await fetch('../actions/toggle_favorite.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        // Animate and remove the card
                        card.style.transition = 'all 0.3s';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.8)';

                        setTimeout(() => {
                            card.remove();

                            // Check if no favorites left
                            if (document.querySelectorAll('.destination-card').length === 0) {
                                location.reload(); // Reload to show empty state
                            }
                        }, 300);

                        // Show success message
                        showNotification('Removed from favorites', 'success');
                    } else {
                        // Revert on error
                        heartIcon.className = 'fas fa-heart text-red-500';
                        showNotification('Could not remove favorite', 'error');
                    }
                } catch (err) {
                    heartIcon.className = 'fas fa-heart text-red-500';
                    showNotification('Network error', 'error');
                } finally {
                    this.disabled = false;
                }
            });
        });

        // Simple notification function
        function showNotification(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            } text-white`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    </script>

    <!-- Session Management Scripts - Keep session alive across all pages -->
    <?php echo getSessionManagerScripts($base_url); ?>
</body>

</html>