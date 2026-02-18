<?php
// Simulate user data - in a real application, this would come from a database or session
$userData = [
    'name' => "Argha Akhuli",
    'shortName' => "Argha",
    'lastLogin' => "2 days ago"
];
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
      top: 64px; /* Height of the floating nav */
      bottom: 0;
      left: 0;
      z-index: 900;
      overflow-y: auto;
    }
    body {
      padding-top: 64px; /* Height of the floating nav */
      padding-left: 256px; /* Width of the sidebar */
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
      <a href="index.php" class="hover:text-orange-300 transition flex items-center">
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
          <a href="favourites.php" class="flex items-center hover:text-orange-300 transition">
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
        </button>
      </div>
    </div>

    <!-- Suggested Trips -->
    <div class="flex flex-wrap gap-2">
      <span class="px-4 py-2 bg-gray-100 rounded-full cursor-pointer hover:bg-blue-100">Romantic 3-day trip in Paris</span>
      <span class="px-4 py-2 bg-gray-100 rounded-full cursor-pointer hover:bg-blue-100">I have 5 days in Japan</span>
      <span class="px-4 py-2 bg-gray-100 rounded-full cursor-pointer hover:bg-blue-100">I want a 7-day trip to New Zealand</span>
    </div>

    <!-- Recommended Destinations -->
    <div>
      <h3 class="text-lg font-semibold mb-6">Where you should go next</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Kyoto Card -->
        <div class="bg-white rounded-2xl shadow hover:shadow-lg transition overflow-hidden trip-card">
          <img src="https://images.unsplash.com/photo-1504893524553-b8553fbb1d2d" class="h-40 w-full object-cover">
          <div class="p-4">
            <p class="font-semibold">Kyoto</p>
            <p class="text-sm text-gray-500">Cherry blossoms in full bloom this week!</p>
            <p class="text-sm mt-2">₹70,000 – ₹95,000</p>
            <p class="text-xs text-gray-400">Now – Apr 5</p>
          </div>
        </div>
        <!-- Bali Card -->
        <div class="bg-white rounded-2xl shadow hover:shadow-lg transition overflow-hidden trip-card">
          <img src="https://images.unsplash.com/photo-1518548419970-58e3b4079ab2" class="h-40 w-full object-cover">
          <div class="p-4">
            <p class="font-semibold">Bali</p>
            <p class="text-sm text-gray-500">Perfect tropical getaway</p>
            <p class="text-sm mt-2">₹50,000 – ₹75,000</p>
            <p class="text-xs text-gray-400">Year-round destination</p>
          </div>
        </div>
        <!-- Paris Card -->
        <div class="bg-white rounded-2xl shadow hover:shadow-lg transition overflow-hidden trip-card">
          <img src="https://images.unsplash.com/photo-1502602898657-3e91760cbb34" class="h-40 w-full object-cover">
          <div class="p-4">
            <p class="font-semibold">Paris</p>
            <p class="text-sm text-gray-500">The city of love and lights</p>
            <p class="text-sm mt-2">₹85,000 – ₹1,20,000</p>
            <p class="text-xs text-gray-400">Best in Spring</p>
          </div>
        </div>
        <!-- New Zealand Card -->
        <div class="bg-white rounded-2xl shadow hover:shadow-lg transition overflow-hidden trip-card">
          <img src="https://images.unsplash.com/photo-1507699622108-4be3abd695ad" class="h-40 w-full object-cover">
          <div class="p-4">
            <p class="font-semibold">New Zealand</p>
            <p class="text-sm text-gray-500">Adventure awaits</p>
            <p class="text-sm mt-2">₹1,20,000 – ₹1,80,000</p>
            <p class="text-xs text-gray-400">Nov - Mar</p>
          </div>
        </div>
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
      <div class="space-y-6">
        <!-- Trip 1 -->
        <div class="border-b pb-6">
          <div class="flex items-start">
            <img src="https://images.unsplash.com/photo-1549144511-f3a4c0e012ae" class="w-24 h-24 rounded-lg object-cover mr-4">
            <div class="flex-1">
              <h4 class="font-semibold">Bali Adventure</h4>
              <p class="text-sm text-gray-500">January 15-22, 2023</p>
              <p class="text-sm mt-2">Explored Ubud, Seminyak, and Nusa Penida. Stayed at the beautiful Ayana Resort.</p>
              <div class="flex mt-2">
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mr-2">Beach</span>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mr-2">Culture</span>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">Adventure</span>
              </div>
            </div>
            <div class="flex flex-col items-end space-y-2">
              <button class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-edit"></i>
              </button>
              <button class="view-details-btn orange-button text-white px-3 py-1 rounded text-sm" data-trip="bali">
                View Details
              </button>
            </div>
          </div>
          
          <!-- Details Panel for Bali -->
          <div id="bali-details" class="details-panel mt-4 pl-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h5 class="font-semibold text-blue-700 mb-2"><i class="fas fa-hotel mr-2"></i>Accommodation</h5>
                <ul class="space-y-2">
                  <li class="place-item flex items-center">
                    <i class="fas fa-star text-yellow-400 mr-2"></i>
                    <div>
                      <p class="font-medium">Ayana Resort</p>
                      <p class="text-xs text-gray-500">5-star luxury resort with ocean view</p>
                    </div>
                  </li>
                  <li class="place-item flex items-center">
                    <i class="fas fa-moon text-purple-500 mr-2"></i>
                    <div>
                      <p class="font-medium">Ubud Rainforest Retreat</p>
                      <p class="text-xs text-gray-500">3 nights in a private villa</p>
                    </div>
                  </li>
                </ul>
              </div>
              <div>
                <h5 class="font-semibold text-blue-700 mb-2"><i class="fas fa-map-marked-alt mr-2"></i>Places Visited</h5>
                <ul class="space-y-2">
                  <li class="place-item flex items-center">
                    <i class="fas fa-umbrella-beach text-blue-400 mr-2"></i>
                    <span>Seminyak Beach</span>
                  </li>
                  <li class="place-item flex items-center">
                    <i class="fas fa-water text-blue-300 mr-2"></i>
                    <span>Tegenungan Waterfall</span>
                  </li>
                  <li class="place-item flex items-center">
                    <i class="fas fa-tree text-green-500 mr-2"></i>
                    <span>Sacred Monkey Forest</span>
                  </li>
                  <li class="place-item flex items-center">
                    <i class="fas fa-mountain text-brown-500 mr-2"></i>
                    <span>Mount Batur Sunrise</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Trip 2 -->
        <div class="border-b pb-6">
          <div class="flex items-start">
            <img src="https://images.unsplash.com/photo-1524473994769-c47bb6f24cf4" class="w-24 h-24 rounded-lg object-cover mr-4">
            <div class="flex-1">
              <h4 class="font-semibold">Japanese Cultural Journey</h4>
              <p class="text-sm text-gray-500">March 28 - April 10, 2023</p>
              <p class="text-sm mt-2">Visited Tokyo, Kyoto, and Osaka during cherry blossom season. Experienced traditional tea ceremonies.</p>
              <div class="flex mt-2">
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mr-2">Culture</span>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mr-2">Food</span>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">Nature</span>
              </div>
            </div>
            <div class="flex flex-col items-end space-y-2">
              <button class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-edit"></i>
              </button>
              <button class="view-details-btn orange-button text-white px-3 py-1 rounded text-sm" data-trip="japan">
                View Details
              </button>
            </div>
          </div>
          
          <!-- Details Panel for Japan -->
          <div id="japan-details" class="details-panel mt-4 pl-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h5 class="font-semibold text-blue-700 mb-2"><i class="fas fa-hotel mr-2"></i>Accommodation</h5>
                <ul class="space-y-2">
                  <li class="place-item flex items-center">
                    <i class="fas fa-building text-gray-600 mr-2"></i>
                    <div>
                      <p class="font-medium">Park Hotel Tokyo</p>
                      <p class="text-xs text-gray-500">4-star hotel in Shiodome district</p>
                    </div>
                  </li>
                  <li class="place-item flex items-center">
                    <i class="fas fa-bed text-brown-400 mr-2"></i>
                    <div>
                      <p class="font-medium">Traditional Ryokan</p>
                      <p class="text-xs text-gray-500">2 nights in Kyoto with kaiseki meals</p>
                    </div>
                  </li>
                </ul>
              </div>
              <div>
                <h5 class="font-semibold text-blue-700 mb-2"><i class="fas fa-map-marked-alt mr-2"></i>Places Visited</h5>
                <ul class="space-y-2">
                  <li class="place-item flex items-center">
                    <i class="fas fa-torii-gate text-red-500 mr-2"></i>
                    <span>Fushimi Inari Shrine</span>
                  </li>
                  <li class="place-item flex items-center">
                    <i class="fas fa-monument text-gray-700 mr-2"></i>
                    <span>Tokyo Imperial Palace</span>
                  </li>
                  <li class="place-item flex items-center">
                    <i class="fas fa-shopping-cart text-pink-500 mr-2"></i>
                    <span>Shibuya Crossing</span>
                  </li>
                  <li class="place-item flex items-center">
                    <i class="fas fa-utensils text-orange-400 mr-2"></i>
                    <span>Dotonbori Food Street</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Trip 3 -->
        <div>
          <div class="flex items-start">
            <img src="https://images.unsplash.com/photo-1503919545889-aef636e10ad4" class="w-24 h-24 rounded-lg object-cover mr-4">
            <div class="flex-1">
              <h4 class="font-semibold">European Adventure</h4>
              <p class="text-sm text-gray-500">June 10-25, 2023</p>
              <p class="text-sm mt-2">Explored Paris, Rome, and Barcelona. Tasted local cuisines and visited historical landmarks.</p>
              <div class="flex mt-2">
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mr-2">History</span>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mr-2">Architecture</span>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">Food</span>
              </div>
            </div>
            <div class="flex flex-col items-end space-y-2">
              <button class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-edit"></i>
              </button>
              <button class="view-details-btn orange-button text-white px-3 py-1 rounded text-sm" data-trip="europe">
                View Details
              </button>
            </div>
          </div>
          
          <!-- Details Panel for Europe -->
          <div id="europe-details" class="details-panel mt-4 pl-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h5 class="font-semibold text-blue-700 mb-2"><i class="fas fa-hotel mr-2"></i>Accommodation</h5>
                <ul class="space-y-2">
                  <li class="place-item flex items-center">
                    <i class="fas fa-hotel text-purple-500 mr-2"></i>
                    <div>
                      <p class="font-medium">Hôtel Plaza Athénée</p>
                      <p class="text-xs text-gray-500">5-star luxury hotel in Paris</p>
                    </div>
                  </li>
                  <li class="place-item flex items-center">
                    <i class="fas fa-building text-yellow-500 mr-2"></i>
                    <div>
                      <p class="font-medium">Trastevere Apartment</p>
                      <p class="text-xs text-gray-500">Historic district in Rome</p>
                    </div>
                  </li>
                </ul>
              </div>
              <div>
                <h5 class="font-semibold text-blue-700 mb-2"><i class="fas fa-map-marked-alt mr-2"></i>Places Visited</h5>
                <ul class="space-y-2">
                  <li class="place-item flex items-center">
                    <i class="fas fa-archway text-brown-500 mr-2"></i>
                    <span>Arc de Triomphe</span>
                  </li>
                  <li class="place-item flex items-center">
                    <i class="fas fa-church text-gray-600 mr-2"></i>
                    <span>Sagrada Família</span>
                  </li>
                  <li class="place-item flex items-center">
                    <i class="fas fa-history text-yellow-600 mr-2"></i>
                    <span>Colosseum</span>
                  </li>
                  <li class="place-item flex items-center">
                    <i class="fas fa-utensils text-red-400 mr-2"></i>
                    <span>Tapas bars in Barcelona</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

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
    var visited = [
      { name: "Japan", coords: [36.2, 138.2] },
      { name: "France", coords: [46.2, 2.2] },
      { name: "Indonesia", coords: [-0.8, 113.9] },
      { name: "Italy", coords: [41.9, 12.5] },
      { name: "Spain", coords: [40.4, -3.7] }
    ];
    visited.forEach(place => {
      L.circleMarker(place.coords, {
        color: "green",
        radius: 8,
        fillOpacity: 0.7
      }).addTo(map).bindPopup("Visited: " + place.name);
    });

    // Example favorites (gold)
    var favorites = [
      { name: "New Zealand", coords: [-40.9, 174.9] },
      { name: "Greece", coords: [39.1, 21.8] }
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
  </script>
</body>
</html>