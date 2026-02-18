<?php
session_start();
require_once __DIR__ . '/../database/dbconfig.php';

// Simulate user data - in a real application, this would come from a database or session
$userData = [
  'name' => "Argha Akhuli",
  'shortName' => "Argha",
  'lastLogin' => "2 days ago"
];

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
if (isset($_SESSION['user_id'])) {
  $history_stmt = $conn->prepare("SELECT activity_type, activity_details, created_at FROM user_history WHERE user_id = ? AND activity_type = 'trip' ORDER BY created_at DESC LIMIT 5");
  $history_stmt->bind_param("i", $_SESSION['user_id']);
  $history_stmt->execute();
  $history_result = $history_stmt->get_result();
  while ($row = $history_result->fetch_assoc()) {
    $travel_history[] = $row;
  }
  $history_stmt->close();
}

// Get search history
$search_history = [];
if (isset($_SESSION['user_id'])) {
  $search_stmt = $conn->prepare("SELECT * FROM search_history WHERE user_id = ? ORDER BY search_date DESC LIMIT 5");
  $search_stmt->bind_param("i", $_SESSION['user_id']);
  $search_stmt->execute();
  $search_result = $search_stmt->get_result();
  while ($row = $search_result->fetch_assoc()) {
    // Normalize possible column names to the keys used by the template
    $query = $row['search_query'] ?? $row['query'] ?? $row['search_term'] ?? $row['term'] ?? '';
    $date  = $row['search_date']  ?? $row['created_at'] ?? $row['date'] ?? null;

    $search_history[] = [
      'search_query' => $query,
      'search_date'  => $date
    ];
  }
  $search_stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Tripmate — User Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <!-- Leaflet JS -->
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
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

    .floating-sidebar {
      position: fixed;
      top: 64px;
      /* Height of the floating nav */
      bottom: 0;
      left: 0;
      z-index: 900;
      overflow-y: auto;
    }

    body {
      padding-top: 64px;
      /* Height of the floating nav */
      padding-left: 256px;
      /* Width of the sidebar */
    }

    .main-content {
      margin-left: 0;
    }

    .map-container {
      height: 320px;
    }

    .trip-card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .trip-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    }

    .details-panel {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.5s ease;
    }

    .details-panel.open {
      max-height: 500px;
    }

    .place-item {
      transition: transform 0.2s ease;
    }

    .place-item:hover {
      transform: translateX(5px);
    }

    .favorite-btn {
      transition: all 0.3s ease;
    }

    .favorite-btn.active {
      color: #ef4444;
    }

    .review-stars {
      display: inline-flex;
      direction: row;
    }

    .review-stars input {
      display: none;
    }

    .review-stars label {
      cursor: pointer;
      font-size: 1.5rem;
      color: #d1d5db;
      transition: color 0.2s;
    }

    .review-stars input:checked~label,
    .review-stars label:hover,
    .review-stars label:hover~label {
      color: #f59e0b;
    }

    @media (max-width: 1024px) {
      body {
        padding-left: 0;
      }

      .floating-sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
      }

      .floating-sidebar.open {
        transform: translateX(0);
      }

      .main-content {
        margin-left: 0;
      }

      .sidebar-toggle {
        display: block;
      }
    }

    /* Hide zoom controls on map */
    .leaflet-control-zoom {
      display: none !important;
    }
  </style>
</head>

<body class="bg-gray-50">

  <!-- Floating Navigation Bar -->
  <nav class="floating-nav nav-blue-bg text-white p-4 flex justify-between items-center">
    <!-- Logo and Brand -->
    <div class="flex items-center">
      <i class="fas fa-compass text-2xl mr-2 text-orange-500"></i>
      <h1 class="text-xl font-bold">Tripmate</h1>
    </div>

    <!-- Navigation Links -->
    <div class="hidden md:flex items-center space-x-6">
      <a href="../main/index.php" class="hover:text-orange-300 transition flex items-center">
        <i class="fas fa-home mr-1"></i> Home
      </a>
      <a href="search.php" class="hover:text-orange-300 transition flex items-center">
        <i class="fas fa-search mr-1"></i> Search
      </a>
      <a href="#" class="hover:text-orange-300 transition flex items-center">
        <i class="fas fa-suitcase mr-1"></i> My Trips
      </a>
    </div>

    <!-- Mobile Menu Button -->
    <button class="md:hidden sidebar-toggle text-white" onclick="toggleSidebar()">
      <i class="fas fa-bars text-xl"></i>
    </button>

    <!-- Profile Section -->
    <div class="flex items-center space-x-4">
      <!-- Profile Dropdown -->
      <div class="relative">
        <button id="profileBtn" class="orange-button text-white rounded-full px-4 py-2 font-medium hover:bg-orange-700 transition flex items-center">
          <i class="fas fa-user-circle mr-2"></i>
          <span id="username" class="hidden md:inline"><?php echo $userData['name']; ?></span>
        </button>

        <!-- Dropdown -->
        <div id="profileMenu" class="hidden absolute right-0 mt-2 w-40 bg-white border rounded shadow-md z-10">
          <a href="#" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
            <i class="fas fa-user mr-2"></i> My Profile
          </a>
          <button id="logoutBtn" class="w-full text-left px-4 py-2 text-gray-800 hover:bg-gray-100">
            <i class="fas fa-sign-out-alt mr-2"></i> Logout
          </button>
        </div>
      </div>
    </div>
  </nav>

  <!-- Sidebar -->
  <aside class="floating-sidebar w-64 nav-blue-bg text-white shadow-md flex flex-col">
    <div class="px-6 py-4 border-b border-blue-700">
      <!-- Logo removed from sidebar as requested -->
    </div>
    <nav class="flex-1 px-4 py-6 space-y-4">
      <button class="orange-button w-full text-left font-medium py-2 px-4 rounded-lg flex items-center">
        <i class="fas fa-robot mr-2"></i> Travel Assistant
      </button>
      <ul class="space-y-3">
        <li>
          <a href="my_trips.php" class="flex items-center hover:text-orange-300 transition">
            <i class="fas fa-suitcase mr-3"></i> My Trips
          </a>
        </li>
        <li>
          <a href="#" class="flex items-center hover:text-orange-300 transition">
            <i class="fas fa-blog mr-3"></i> Blog
          </a>
        </li>
        <li>
          <a href="../actions/get_favourites.php" class="flex items-center hover:text-orange-300 transition">
            <i class="fas fa-heart mr-3"></i> Favourites
          </a>
        </li>
        <li>
          <a href="#" class="flex items-center hover:text-orange-300 transition">
            <i class="fas fa-calculator mr-3"></i> Budget Planner
          </a>
        </li>
      </ul>
    </nav>
    <div class="px-6 py-4 border-t border-blue-700">
      <p class="text-sm font-medium"><?php echo $userData['name']; ?></p>
      <span class="text-xs text-orange-300">Free Account</span>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="main-content flex-1 p-8 space-y-10">

    <!-- Welcome Notice -->
    <div class="bg-gradient-to-r from-blue-500 to-blue-700 text-white p-6 rounded-2xl shadow-lg">
      <h2 class="text-2xl font-bold mb-2">Welcome back, <span id="welcomeUsername"><?php echo $userData['shortName']; ?></span>!</h2>
      <p class="text-blue-100">We've missed you! Your last login was <span id="lastLogin"><?php echo $userData['lastLogin']; ?></span>. Ready to plan your next adventure?</p>
    </div>

    <!-- Header -->
    <div class="flex justify-between items-center relative">
      <div>
        <h2 class="text-lg font-semibold">Your travel dashboard</h2>
        <p class="text-2xl font-bold">Tell us your <span class="text-blue-600">Travel Plan</span> in one sentence</p>
      </div>
      <div class="flex items-center space-x-4 text-sm">

        <!-- Language -->
        <select class="border rounded px-2 py-1">
          <option>English (US)</option>
          <option>English (IN)</option>
        </select>

        <!-- Currency -->
        <select class="border rounded px-2 py-1">
          <option>INR (₹)</option>
          <option>USD ($)</option>
          <option>EUR (€)</option>
        </select>

        <!-- Notification -->
        <button class="relative p-2 rounded-full hover:bg-gray-200 transition">
          <i class="fas fa-bell"></i>
          <?php if (count($search_history) > 0): ?>
            <span class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center">
              <?php echo count($search_history); ?>
            </span>
          <?php endif; ?>
        </button>
      </div>
    </div>

    <!-- Quick Search & Recent Searches -->
    <div class="bg-white rounded-2xl shadow p-6">
      <h3 class="text-lg font-semibold mb-4">Quick Search & Recent Searches</h3>

      <!-- Suggested Trips -->
      <div class="mb-6">
        <h4 class="font-medium text-gray-700 mb-3">Quick Suggestions</h4>
        <div class="flex flex-wrap gap-2">
          <span class="px-4 py-2 bg-gray-100 rounded-full cursor-pointer hover:bg-blue-100">Romantic 3-day trip in Paris</span>
          <span class="px-4 py-2 bg-gray-100 rounded-full cursor-pointer hover:bg-blue-100">I have 5 days in Japan</span>
          <span class="px-4 py-2 bg-gray-100 rounded-full cursor-pointer hover:bg-blue-100">I want a 7-day trip to New Zealand</span>
        </div>
      </div>

      <!-- Recent Searches -->
      <?php if (!empty($search_history)): ?>
        <div>
          <h4 class="font-medium text-gray-700 mb-3">Recent Searches</h4>
          <div class="flex flex-wrap gap-2">
            <?php foreach ($search_history as $search): ?>
              <span class="px-4 py-2 bg-blue-50 text-blue-700 rounded-full cursor-pointer hover:bg-blue-100 transition">
                <?php echo htmlspecialchars($search['search_query']); ?>
                <span class="text-xs text-blue-500 ml-1">
                  (<?php echo date('M j', strtotime($search['search_date'])); ?>)
                </span>
              </span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Recommended Destinations -->
    <div>
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold">Personalized Recommendations</h3>
        <button onclick="refreshRecommendations()" class="text-blue-600 hover:text-blue-800 text-sm flex items-center">
          <i class="fas fa-sync-alt mr-1"></i> Refresh
        </button>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" id="recommendations-container">
        <?php foreach ($recommendations as $destination):
          $image_urls = json_decode($destination['image_urls'] ?? '[]', true);
          $main_image = !empty($image_urls) ? $image_urls[0] : 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34';
        ?>
          <div class="bg-white rounded-2xl shadow hover:shadow-lg transition overflow-hidden trip-card relative">
            <button class="favorite-btn absolute top-3 right-3 bg-white p-2 rounded-full shadow-md z-10"
              data-destination-id="<?php echo $destination['id']; ?>">
              <i class="far fa-heart text-gray-400"></i>
            </button>
            <img src="<?php echo $main_image; ?>" class="h-40 w-full object-cover">
            <div class="p-4">
              <p class="font-semibold"><?php echo htmlspecialchars($destination['name']); ?></p>
              <p class="text-sm text-gray-500"><?php echo htmlspecialchars($destination['type']); ?> • <?php echo htmlspecialchars($destination['location']); ?></p>
              <p class="text-sm mt-2">₹<?php echo number_format($destination['budget']); ?></p>
              <div class="flex justify-between items-center mt-3">
                <button class="text-blue-600 hover:text-blue-800 text-sm" onclick="showReviewModal(<?php echo $destination['id']; ?>, '<?php echo htmlspecialchars($destination['name']); ?>')">
                  <i class="fas fa-star mr-1"></i> Review
                </button>
                <button class="text-orange-600 hover:text-orange-800 text-sm">
                  <i class="fas fa-plus mr-1"></i> Plan Trip
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- 🌍 Travel Map -->
    <div>
      <h3 class="text-lg font-semibold mb-4">Your Travel Map</h3>
      <div id="map" class="w-full map-container rounded-2xl shadow"></div>
    </div>

    <!-- Travel History -->
    <div class="bg-white rounded-2xl shadow p-6">
      <h3 class="text-lg font-semibold mb-6">Your Travel History</h3>
      <div class="space-y-6" id="travel-history-container">
        <?php if (!empty($travel_history)): ?>
          <?php foreach ($travel_history as $trip):
            $details = json_decode($trip['activity_details'], true);
          ?>
            <div class="border-b pb-6">
              <div class="flex items-start">
                <img src="<?php echo $details['image'] ?? 'https://images.unsplash.com/photo-1503919545889-aef636e10ad4'; ?>" class="w-24 h-24 rounded-lg object-cover mr-4">
                <div class="flex-1">
                  <h4 class="font-semibold"><?php echo htmlspecialchars($details['title'] ?? 'Trip'); ?></h4>
                  <p class="text-sm text-gray-500"><?php echo date('F j, Y', strtotime($trip['created_at'])); ?></p>
                  <p class="text-sm mt-2"><?php echo htmlspecialchars($details['description'] ?? 'No description available.'); ?></p>
                  <?php if (isset($details['tags'])): ?>
                    <div class="flex mt-2">
                      <?php foreach ($details['tags'] as $tag): ?>
                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mr-2"><?php echo htmlspecialchars($tag); ?></span>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="flex flex-col items-end space-y-2">
                  <button class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="view-details-btn orange-button text-white px-3 py-1 rounded text-sm">
                    View Details
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-center py-8 text-gray-500">
            <i class="fas fa-suitcase text-4xl mb-4"></i>
            <p>No travel history yet. Start planning your first trip!</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <!-- Review Modal -->
  <div id="reviewModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 shadow-lg w-96 max-w-full mx-4">
      <h2 class="text-lg font-semibold mb-4" id="reviewDestinationName">Add Review</h2>
      <form id="reviewForm">
        <input type="hidden" id="reviewDestinationId" name="destination_id">

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Your Rating</label>
          <div class="review-stars">
            <input type="radio" id="star5" name="rating" value="5">
            <label for="star5">★</label>
            <input type="radio" id="star4" name="rating" value="4">
            <label for="star4">★</label>
            <input type="radio" id="star3" name="rating" value="3">
            <label for="star3">★</label>
            <input type="radio" id="star2" name="rating" value="2">
            <label for="star2">★</label>
            <input type="radio" id="star1" name="rating" value="1">
            <label for="star1">★</label>
          </div>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Your Review</label>
          <textarea name="comment" rows="4" class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Share your experience..."></textarea>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Add Photos (Optional)</label>
          <input type="file" name="images[]" multiple accept="image/*" class="w-full border rounded-lg p-2">
        </div>

        <div class="flex justify-end space-x-3">
          <button type="button" onclick="closeReviewModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
          <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Submit Review</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Logout Confirmation Modal -->
  <div id="logoutModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 shadow-lg w-80 text-center">
      <h2 class="text-lg font-semibold mb-4">Are you sure you want to logout?</h2>
      <div class="flex justify-around">
        <button id="confirmLogout" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Yes</button>
        <button id="cancelLogout" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">No</button>
      </div>
    </div>
  </div>

  <!-- Map Script -->
  <script>
    // Initialize map
    var map = L.map('map', {
      zoomControl: false // This removes the + and - buttons
    }).setView([20, 0], 2);

    // Tile Layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Example visited places (green)
    var visited = [{
        name: "Japan",
        coords: [36.2, 138.2]
      },
      {
        name: "France",
        coords: [46.2, 2.2]
      },
      {
        name: "Indonesia",
        coords: [-0.8, 113.9]
      },
      {
        name: "Italy",
        coords: [41.9, 12.5]
      },
      {
        name: "Spain",
        coords: [40.4, -3.7]
      }
    ];
    visited.forEach(place => {
      L.circleMarker(place.coords, {
        color: "green",
        radius: 8,
        fillOpacity: 0.7
      }).addTo(map).bindPopup("Visited: " + place.name);
    });

    // Example favorites (gold)
    var favorites = [{
        name: "New Zealand",
        coords: [-40.9, 174.9]
      },
      {
        name: "Greece",
        coords: [39.1, 21.8]
      }
    ];
    favorites.forEach(place => {
      L.circleMarker(place.coords, {
        color: "gold",
        radius: 8,
        fillOpacity: 0.7
      }).addTo(map).bindPopup("Favorite: " + place.name);
    });

    // Profile Dropdown Toggle
    const profileBtn = document.getElementById("profileBtn");
    const profileMenu = document.getElementById("profileMenu");
    profileBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      profileMenu.classList.toggle("hidden");
    });

    // Close dropdown when clicking elsewhere
    document.addEventListener('click', (e) => {
      if (!profileBtn.contains(e.target) && !profileMenu.contains(e.target)) {
        profileMenu.classList.add('hidden');
      }
    });

    // Logout Confirmation
    const logoutBtn = document.getElementById("logoutBtn");
    const logoutModal = document.getElementById("logoutModal");
    const confirmLogout = document.getElementById("confirmLogout");
    const cancelLogout = document.getElementById("cancelLogout");

    logoutBtn.addEventListener("click", () => {
      logoutModal.classList.remove("hidden");
    });

    cancelLogout.addEventListener("click", () => {
      logoutModal.classList.add("hidden");
    });

    confirmLogout.addEventListener("click", () => {
      // Redirect to sign in login page
      window.location.href = "signin.php";
    });

    // Toggle sidebar on mobile
    function toggleSidebar() {
      document.querySelector('.floating-sidebar').classList.toggle('open');
    }

    // View details functionality
    document.querySelectorAll('.view-details-btn').forEach(button => {
      button.addEventListener('click', function() {
        const tripId = this.getAttribute('data-trip');
        const detailsPanel = document.getElementById(`${tripId}-details`);

        // Close all other open panels
        document.querySelectorAll('.details-panel').forEach(panel => {
          if (panel.id !== `${tripId}-details`) {
            panel.classList.remove('open');
          }
        });

        // Toggle current panel
        detailsPanel.classList.toggle('open');

        // Update button text
        if (detailsPanel.classList.contains('open')) {
          this.textContent = 'Hide Details';
        } else {
          this.textContent = 'View Details';
        }
      });
    });

    // Favorite functionality
    document.querySelectorAll('.favorite-btn').forEach(button => {
      button.addEventListener('click', function() {
        const destinationId = this.getAttribute('data-destination-id');
        const heartIcon = this.querySelector('i');
        const isActive = heartIcon.classList.contains('fas');

        // Toggle visual state immediately for better UX
        if (isActive) {
          heartIcon.classList.replace('fas', 'far');
          heartIcon.classList.replace('text-red-500', 'text-gray-400');
        } else {
          heartIcon.classList.replace('far', 'fas');
          heartIcon.classList.replace('text-gray-400', 'text-red-500');
        }

        // Send AJAX request to toggle favorite
        toggleFavorite(destinationId, !isActive);
      });
    });

    // Toggle favorite via AJAX
    function toggleFavorite(destinationId, isAdding) {
      const formData = new FormData();
      formData.append('destination_id', destinationId);
      formData.append('action', isAdding ? 'add' : 'remove');

      fetch('toggle_favorite.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.status !== 'success') {
            // Revert visual state if failed
            const button = document.querySelector(`.favorite-btn[data-destination-id="${destinationId}"]`);
            const heartIcon = button.querySelector('i');
            if (isAdding) {
              heartIcon.classList.replace('fas', 'far');
              heartIcon.classList.replace('text-red-500', 'text-gray-400');
            } else {
              heartIcon.classList.replace('far', 'fas');
              heartIcon.classList.replace('text-gray-400', 'text-red-500');
            }
            console.error('Failed to update favorite:', data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
        });
    }

    // Check favorite status on page load
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.favorite-btn').forEach(button => {
        const destinationId = button.getAttribute('data-destination-id');
        checkFavoriteStatus(destinationId, button);
      });
    });

    function checkFavoriteStatus(destinationId, buttonElement) {
      fetch(`check_favorite.php?destination_id=${destinationId}`)
        .then(response => response.json())
        .then(data => {
          const heartIcon = buttonElement.querySelector('i');
          if (data.is_favorite) {
            heartIcon.classList.replace('far', 'fas');
            heartIcon.classList.replace('text-gray-400', 'text-red-500');
          }
        })
        .catch(error => {
          console.error('Error checking favorite status:', error);
        });
    }

    // Review modal functions
    function showReviewModal(destinationId, destinationName) {
      document.getElementById('reviewDestinationId').value = destinationId;
      document.getElementById('reviewDestinationName').textContent = `Review: ${destinationName}`;
      document.getElementById('reviewModal').classList.remove('hidden');
    }

    function closeReviewModal() {
      document.getElementById('reviewModal').classList.add('hidden');
      document.getElementById('reviewForm').reset();
    }

    // Handle review form submission
    document.getElementById('reviewForm').addEventListener('submit', function(e) {
      e.preventDefault();

      const formData = new FormData(this);

      fetch('add_review.php', {
          method: 'POST',
          body: formData
        })
        .then(response => {
          if (response.ok) {
            closeReviewModal();
            alert('Review submitted successfully!');
          } else {
            alert('Failed to submit review. Please try again.');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred. Please try again.');
        });
    });

    // Refresh recommendations
    function refreshRecommendations() {
      const container = document.getElementById('recommendations-container');
      container.innerHTML = '<div class="col-span-4 text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-blue-500"></i><p class="mt-2 text-gray-600">Loading new recommendations...</p></div>';

      fetch('get_recommendations.php')
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            let html = '';
            data.recommendations.forEach(destination => {
              const image_urls = JSON.parse(destination.image_urls || '[]');
              const main_image = image_urls.length > 0 ? image_urls[0] : 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34';

              html += `
                <div class="bg-white rounded-2xl shadow hover:shadow-lg transition overflow-hidden trip-card relative">
                  <button class="favorite-btn absolute top-3 right-3 bg-white p-2 rounded-full shadow-md z-10" 
                          data-destination-id="${destination.id}">
                    <i class="far fa-heart text-gray-400"></i>
                  </button>
                  <img src="${main_image}" class="h-40 w-full object-cover">
                  <div class="p-4">
                    <p class="font-semibold">${destination.name}</p>
                    <p class="text-sm text-gray-500">${destination.type} • ${destination.location}</p>
                    <p class="text-sm mt-2">₹${destination.budget.toLocaleString()}</p>
                    <div class="flex justify-between items-center mt-3">
                      <button class="text-blue-600 hover:text-blue-800 text-sm" onclick="showReviewModal(${destination.id}, '${destination.name}')">
                        <i class="fas fa-star mr-1"></i> Review
                      </button>
                      <button class="text-orange-600 hover:text-orange-800 text-sm">
                        <i class="fas fa-plus mr-1"></i> Plan Trip
                      </button>
                    </div>
                  </div>
                </div>
              `;
            });
            container.innerHTML = html;

            // Re-attach favorite button event listeners
            document.querySelectorAll('.favorite-btn').forEach(button => {
              button.addEventListener('click', function() {
                const destinationId = this.getAttribute('data-destination-id');
                const heartIcon = this.querySelector('i');
                const isActive = heartIcon.classList.contains('fas');

                if (isActive) {
                  heartIcon.classList.replace('fas', 'far');
                  heartIcon.classList.replace('text-red-500', 'text-gray-400');
                } else {
                  heartIcon.classList.replace('far', 'fas');
                  heartIcon.classList.replace('text-gray-400', 'text-red-500');
                }

                toggleFavorite(destinationId, !isActive);
              });

              // Check favorite status for new items
              const destinationId = button.getAttribute('data-destination-id');
              checkFavoriteStatus(destinationId, button);
            });
          }
        })
        .catch(error => {
          console.error('Error:', error);
          container.innerHTML = '<div class="col-span-4 text-center py-8 text-red-500"><p>Failed to load recommendations. Please try again.</p></div>';
        });
    }

    // Close modals when clicking outside
    document.addEventListener('click', function(e) {
      if (e.target.id === 'reviewModal') {
        closeReviewModal();
      }
      if (e.target.id === 'logoutModal') {
        logoutModal.classList.add('hidden');
      }
    });
  </script>
</body>

</html>