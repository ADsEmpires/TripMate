<?php
// Start the session for admin authentication
session_start();

$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_contributor = isset($_SESSION['contributor_id']);

// Include database configuration and connection
include '../database/dbconfig.php';

// Ensure $conn exists
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            error_log('Database connection error: ' . $conn->connect_error);
            die("Database connection error.");
        }
    } else {
        $conn = null;
    }
}

// Get admin info from session
$admin_id = $_SESSION['admin_id'] ?? 0;
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_email = $_SESSION['admin_email'] ?? '';

// Handle form submission for adding a new destination
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form inputs
    $name = $_POST['name'] ?? '';
    $type = $_POST['type'] ?? '';
    $description = $_POST['description'] ?? '';
    $location = $_POST['location'] ?? '';
    $budget = isset($_POST['budget']) ? (float)$_POST['budget'] : 0.0;
    
    $map_link = $_POST['map_link'] ?? '';
    if (strlen($map_link) > 255) {
        $map_link = substr($map_link, 0, 255);
    }
    
    $season = isset($_POST['season']) ? implode(',', $_POST['season']) : '';
    $people = isset($_POST['people']) ? json_encode($_POST['people']) : '[]';
    
    // Handle image upload
    $image_urls = [];
    $upload_errors = [];

    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        // Filesystem absolute path for uploads
        $full_upload_dir = __DIR__ . '/../uploads/destinations/';
        // Web‑relative path to store in DB (root‑relative)
        $web_upload_dir = 'uploads/destinations/';
        
        if (!file_exists($full_upload_dir)) {
            if (!mkdir($full_upload_dir, 0777, true) && !is_dir($full_upload_dir)) {
                $upload_errors[] = "Failed to create upload directory: {$full_upload_dir}";
            }
        }
        if (!is_writable($full_upload_dir)) {
            @chmod($full_upload_dir, 0775);
            if (!is_writable($full_upload_dir)) {
                $upload_errors[] = "Upload directory not writable: {$full_upload_dir}";
            }
        }

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if (empty($_FILES['images']['name'][$key])) continue;

            if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) {
                $upload_errors[] = "File {$_FILES['images']['name'][$key]} error code: " . $_FILES['images']['error'][$key];
                continue;
            }
            $file_name = basename($_FILES['images']['name'][$key]);
            $allowed = ['jpg','jpeg','png','webp'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                $upload_errors[] = "Only JPG JPEG PNG WEBP allowed for $file_name";
                continue;
            }
            $imageInfo = getimagesize($tmp_name);
            if ($imageInfo === false) {
                $upload_errors[] = "$file_name is not a valid image";
                continue;
            }
            if ($_FILES['images']['size'][$key] > 10 * 1024 * 1024) {
                $upload_errors[] = "$file_name exceeds 10MB";
                continue;
            }
            
            $unique_name = uniqid() . '_' . time() . '.' . $ext;
            $full_path = $full_upload_dir . $unique_name;
            $web_path = $web_upload_dir . $unique_name; // stored in DB

            if (move_uploaded_file($tmp_name, $full_path)) {
                $image_urls[] = $web_path;
            } else {
                $err = error_get_last();
                $upload_errors[] = "Failed to move uploaded file: {$file_name}. PHP error: " . ($err['message'] ?? 'unknown');
            }
        }
    }
    
    if (!empty($upload_errors)) {
        $_SESSION['message'] = "Warning: " . implode(", ", $upload_errors);
    }
    
    $image_urls_json = json_encode($image_urls);
    
    // Get next ID
    $id_query = $conn->query("SELECT MAX(id) AS max_id FROM destinations");
    $next_id = 1;
    if ($id_query && $row = $id_query->fetch_assoc()) {
        $next_id = ($row['max_id'] ?? 0) + 1;
    }
    
    $insert_query = "INSERT INTO destinations (id, name, type, description, location, budget, image_urls, map_link, season, people) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = (isset($conn) && is_object($conn)) ? $conn->prepare($insert_query) : false;
    
    if ($stmt) {
        $stmt->bind_param("issssdssss", $next_id, $name, $type, $description, $location, $budget, $image_urls_json, $map_link, $season, $people);
        
        if ($stmt->execute()) {
            // Also insert a default city in destination_cities to satisfy foreign key constraints
            $city_stmt = $conn->prepare("INSERT INTO destination_cities (id, destination_id, city_name) VALUES (?, ?, ?)");
            if ($city_stmt) {
                $city_stmt->bind_param("iis", $next_id, $next_id, $name);
                $city_stmt->execute();
                $city_stmt->close();
            }

            if (empty($upload_errors)) {
                $_SESSION['message'] = "Destination added successfully!";
            } else {
                $_SESSION['message'] = "Destination added but some images failed to upload.";
            }
        } else {
            $_SESSION['message'] = "Error adding destination: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $dbError = (isset($conn) && is_object($conn)) ? ($conn->error ?? $conn->connect_error ?? 'Unknown DB error') : 'No database connection';
        $_SESSION['message'] = "Database preparation error: " . $dbError;
    }
    
    header("Location: add_destination_on_admin.php");
    exit();
}

// Get admin info with profile picture
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            error_log('Database connection error: ' . $conn->connect_error);
            die("Database connection error.");
        }
    } else {
        die("No database connection available.");
    }
}

$admin_query = $conn->prepare("SELECT name, email, profile_pic FROM admin WHERE id = ?");
if ($admin_query) {
    $admin_query->bind_param("i", $admin_id);
    $admin_query->execute();
    $admin_result = $admin_query->get_result();
    $admin = $admin_result->fetch_assoc() ?? ['name' => 'Unknown', 'email' => '', 'profile_pic' => NULL];
    $admin_query->close();
} else {
    error_log('Failed to prepare admin query: ' . ($conn->error ?? 'unknown'));
    $admin = ['name' => 'Unknown', 'email' => '', 'profile_pic' => NULL];
}

$admin_name = $admin['name'];
$admin_email = $admin['email'];
$admin_profile_pic = $admin['profile_pic'];

// Fetch all existing destinations
$result = $conn->query("SELECT * FROM destinations ORDER BY id DESC");

// Prepare destinations data for JavaScript
$destinations_data = [];
while ($row = $result->fetch_assoc()) {
    // Process image URLs
    $images = [];
    if (!empty($row['image_urls'])) {
        $decoded = json_decode($row['image_urls'], true);
        if (is_array($decoded)) {
            $images = $decoded;
        }
    }
    
    // Determine first image – fix for admin subfolder
    $first_image = '../image/no-image.jpg'; // default
    if (!empty($images) && isset($images[0])) {
        $stored_path = $images[0]; // e.g. "uploads/destinations/xyz.jpg"
        // Absolute filesystem path to check if file exists
        $abs_path = __DIR__ . '/../' . $stored_path;
        if (file_exists($abs_path)) {
            // For admin pages, we need to go one level up from /admin/
            $first_image = '../' . $stored_path;
        } else {
            error_log("Missing image file: {$stored_path} (checked: {$abs_path})");
        }
    }
    
    // Process people (JSON or plain string)
    $people_display = $row['people'];
    if ($row['people'] && strpos($row['people'], '[') === 0) {
        $people_arr = json_decode($row['people'], true);
        if (is_array($people_arr)) {
            $people_display = implode(', ', $people_arr);
        }
    }
    
    $destinations_data[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'type' => $row['type'],
        'description' => $row['description'],
        'location' => $row['location'],
        'budget' => (float)$row['budget'],
        'map_link' => $row['map_link'],
        'season' => $row['season'],
        'people' => $people_display,
        'people_raw' => $row['people'],
        'image_urls' => $images,
        'first_image' => $first_image,   // already prepended with '../' if exists
        'created_at' => $row['created_at'] ?? date('Y-m-d H:i:s')
    ];
}
?>

<?php include 'admin_header.php'; ?>

<div class="main-content page">
    <!-- alert messages (unchanged) -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert <?= strpos($_SESSION['message'], 'Error') === false && strpos($_SESSION['message'], 'Warning') === false ? 'alert-success' : 'alert-danger' ?> fade-in">
            <i class="fas fa-<?= (strpos($_SESSION['message'], 'Error') === false && strpos($_SESSION['message'], 'Warning') === false) ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($_SESSION['message']) ?>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- "Create New Destination" button & form (unchanged HTML) -->
    <div class="add-button-wrapper" style="margin: 2rem 0; text-align: center;">
        <button id="showAddFormBtn" class="btn btn-primary" style="font-size: 1.3rem; padding: 1rem 2.5rem; border-radius: 50px; box-shadow: 0 10px 20px var(--shadow-color);">
            <i class="fas fa-plus-circle"></i> Create New Destination Package
        </button>
    </div>

    <div id="addFormContainer" class="widget-card fade-in" style="display: none; margin-bottom: 2rem; background: var(--bg-surface); border: 1px solid var(--card-border); border-radius: 16px;">
        <!-- same form HTML as before, no changes needed -->
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--card-border); padding: 1.5rem;">
            <h2 style="font-size: 1.8rem; color: var(--text-main);"><span style="color: var(--primary);">Step 1:</span> Destination Details</h2>
            <button type="button" id="closeFormBtn" class="btn btn-outline" style="border-radius: 50px;">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
        <div class="card-body" style="padding: 1.5rem;">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Destination Name</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="type">Destination Type</label>
                    <select id="type" name="type" class="form-control" required>
                        <option value="beach">Beach</option>
                        <option value="mountain">Mountain</option>
                        <option value="city">City</option>
                        <option value="village">Village</option>
                        <option value="historical">Historical</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="season">Best Season to Visit</label>
                    <select id="season" name="season[]" class="form-control" multiple required style="height: auto; min-height: 120px;">
                        <option value="winter">Winter</option>
                        <option value="summer">Summer</option>
                        <option value="spring">Spring</option>
                        <option value="autumn">Autumn</option>
                        <option value="monsoon">Monsoon</option>
                    </select>
                    <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;"><i class="fas fa-info-circle"></i> Hold Ctrl/Cmd to select multiple options</small>
                </div>
                <div class="form-group">
                    <label for="people">Recommended For</label>
                    <select id="people" name="people[]" class="form-control" multiple required style="height: auto; min-height: 120px;">
                        <option value="1">Solo (1)</option>
                        <option value="2">Couples (2)</option>
                        <option value="3-5">Small Groups (3-5)</option>
                        <option value="6-9">Medium Groups (6-9)</option>
                        <option value="9+">Large Groups (9+)</option>
                    </select>
                    <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;"><i class="fas fa-info-circle"></i> Hold Ctrl/Cmd to select multiple options</small>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" required></textarea>
                </div>
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="budget">Budget (₹ per day)</label>
                    <input type="number" id="budget" name="budget" step="0.01" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="images">Upload Images</label>
                    <input type="file" id="images" name="images[]" class="form-control" multiple accept="image/*" style="padding: 0.6rem;">
                    <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;"><i class="fas fa-image"></i> Select Your images</small>
                </div>
                <div class="form-group">
                    <label for="map_link">Google Map Link</label>
                    <input type="url" id="map_link" name="map_link" class="form-control" required>
                </div>

                <div style="margin-top: 2.5rem; display: flex; gap: 1rem; border-top: 1px solid var(--card-border); padding-top: 1.5rem;">
                    <button type="submit" class="btn btn-flight" style="border-radius: 50px;">
                        Save & Continue to Step 2 (Flights) <i class="fas fa-arrow-right"></i>
                    </button>
                    <button type="button" id="cancelFormBtn" class="btn btn-outline" style="border-radius: 50px;">
                        <i class="fas fa-ban"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Existing Destinations section -->
    <div class="widget-card fade-in" style="margin-bottom: 2rem; background: var(--bg-surface); border: 1px solid var(--card-border); border-radius: 16px;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--card-border); padding: 1.5rem;">
            <h2 style="font-size: 1.8rem; color: var(--text-main); margin: 0;"><i class="fas fa-map-marked-alt" style="color: var(--secondary);"></i> Existing Destinations</h2>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <div class="view-toggle" style="display: flex; gap: 0.5rem; background: var(--bg-base); padding: 0.3rem; border-radius: 50px; border: 1px solid var(--card-border);">
                    <button type="button" id="cardViewBtn" class="view-toggle-btn active" data-view="card" title="Card View">
                        <i class="fas fa-th-large"></i>
                    </button>
                    <button type="button" id="lineViewBtn" class="view-toggle-btn" data-view="line" title="Line View">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                <span class="status-badge status-active" id="totalDestinationsCount">Active: <?= count($destinations_data) ?></span>
            </div>
        </div>
        <div class="card-body" style="padding: 1.5rem;">
            <!-- search/sort controls (unchanged) -->
            <div class="search-sort-container" style="margin-bottom: 2rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; background: var(--bg-base); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--card-border);">
                <div class="search-box" style="flex-grow: 1;">
                    <input type="text" id="destinationSearch" placeholder="Search destinations by name, type, or location..." 
                           class="form-control" style="padding: 0.8rem 1.2rem; border-radius: 50px; background: var(--bg-surface);">
                </div>
                <div class="sort-controls" style="display: flex; gap: 1rem; align-items: center;">
                    <label for="sortOrder" style="font-weight: 600; color: var(--text-main); margin: 0;">Sort by:</label>
                    <select id="sortOrder" class="form-control" style="width: auto; padding: 0.8rem 1.2rem; border-radius: 50px; background: var(--bg-surface);">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="name-asc">Name (A-Z)</option>
                        <option value="name-desc">Name (Z-A)</option>
                        <option value="budget-low">Budget (Low to High)</option>
                        <option value="budget-high">Budget (High to Low)</option>
                    </select>
                </div>
                <button id="clearSearch" class="btn btn-outline" style="border-radius: 50px;">
                    <i class="fas fa-times"></i> Clear
                </button>
            </div>
            
            <div id="resultsCount" style="margin-bottom: 1.5rem; color: var(--text-muted); font-weight: 600; font-size: 1.1rem;">
                Showing all <?= count($destinations_data) ?> destinations
            </div>
            
            <div id="destinationsContainer" class="destinations-view destinations-card-view"></div>
            <div id="noResults" style="display: none; text-align: center; padding: 4rem 2rem; background: var(--bg-base); border-radius: 24px; border: 1px dashed var(--card-border); margin-top: 2rem;">
                <i class="fas fa-search" style="font-size: 4rem; margin-bottom: 1.5rem; color: var(--text-muted); opacity: 0.5;"></i>
                <h3 style="color: var(--text-main); font-size: 1.5rem; margin-bottom: 0.5rem;">No destinations found</h3>
                <p style="color: var(--text-muted);">Try adjusting your search or filter terms</p>
            </div>
        </div>
    </div>
</div>

<script>
// Destinations data from PHP (first_image already has correct admin path)
const destinationsData = <?= json_encode($destinations_data) ?>;

// Global state
let currentViewMode = 'card';
let currentFilteredData = [...destinationsData];
let currentSearchTerm = '';
let currentSortValue = 'newest';

// DOM elements
const destinationsContainer = document.getElementById('destinationsContainer');
const searchInput = document.getElementById('destinationSearch');
const sortSelect = document.getElementById('sortOrder');
const clearBtn = document.getElementById('clearSearch');
const resultsCountSpan = document.getElementById('resultsCount');
const noResultsDiv = document.getElementById('noResults');
const cardViewBtn = document.getElementById('cardViewBtn');
const lineViewBtn = document.getElementById('lineViewBtn');
const totalDestinationsCountSpan = document.getElementById('totalDestinationsCount');

function sortDestinations(data) {
    const sorted = [...data];
    switch(currentSortValue) {
        case 'newest':
            sorted.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
            break;
        case 'oldest':
            sorted.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
            break;
        case 'name-asc':
            sorted.sort((a, b) => a.name.localeCompare(b.name));
            break;
        case 'name-desc':
            sorted.sort((a, b) => b.name.localeCompare(a.name));
            break;
        case 'budget-low':
            sorted.sort((a, b) => a.budget - b.budget);
            break;
        case 'budget-high':
            sorted.sort((a, b) => b.budget - a.budget);
            break;
        default: break;
    }
    return sorted;
}

function filterDestinations() {
    const term = currentSearchTerm.toLowerCase().trim();
    if (!term) {
        currentFilteredData = [...destinationsData];
    } else {
        currentFilteredData = destinationsData.filter(dest => 
            dest.name.toLowerCase().includes(term) ||
            dest.type.toLowerCase().includes(term) ||
            dest.location.toLowerCase().includes(term)
        );
    }
    currentFilteredData = sortDestinations(currentFilteredData);
    updateResultsCount();
    renderCurrentView();
}

function updateResultsCount() {
    const total = currentFilteredData.length;
    const originalTotal = destinationsData.length;
    if (total === 0) {
        resultsCountSpan.textContent = 'No destinations found';
        noResultsDiv.style.display = 'block';
        destinationsContainer.style.display = 'none';
    } else {
        resultsCountSpan.textContent = `Showing ${total} of ${originalTotal} destinations`;
        noResultsDiv.style.display = 'none';
        destinationsContainer.style.display = 'block';
    }
    totalDestinationsCountSpan.textContent = `Active: ${originalTotal}`;
}

function renderCardView() {
    if (currentFilteredData.length === 0) {
        destinationsContainer.innerHTML = '';
        return;
    }
    
    let html = '<div class="destination-grid">';
    for (const dest of currentFilteredData) {
        let seasonDisplay = dest.season ? dest.season.replace(/,/g, ', ') : 'N/A';
        let peopleDisplay = dest.people || 'N/A';
        
        html += `
            <div class="destination-card widget-card" style="padding: 0; overflow: hidden; background: var(--bg-surface); border: 1px solid var(--card-border);">
                <div class="destination-images">
                    <img src="${escapeHtml(dest.first_image)}" alt="${escapeHtml(dest.name)}" onerror="this.src='../image/no-image.jpg'">
                    <div class="destination-type-badge">
                        <i class="fas fa-tag"></i> ${escapeHtml(dest.type.charAt(0).toUpperCase() + dest.type.slice(1))}
                    </div>
                </div>
                <div class="destination-info">
                    <h3 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 1rem; color: var(--text-main);">${escapeHtml(dest.name)}</h3>
                    <div class="destination-meta" style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; margin-bottom: 1.5rem;">
                        <span style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: var(--text-muted);">
                            <i class="fas fa-calendar-alt" style="color: var(--primary); width: 16px; text-align: center;"></i> 
                            <span class="text-truncate" title="${escapeHtml(seasonDisplay)}">${escapeHtml(seasonDisplay)}</span>
                        </span>
                        <span style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: var(--text-muted);">
                            <i class="fas fa-user-friends" style="color: var(--secondary); width: 16px; text-align: center;"></i> 
                            ${escapeHtml(peopleDisplay)}
                        </span>
                        <span style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: var(--text-muted);">
                            <i class="fas fa-rupee-sign" style="color: var(--primary); width: 16px; text-align: center;"></i> 
                            <span style="font-weight: 700; color: var(--text-main);">${numberFormat(dest.budget)}</span>/day
                        </span>
                        <span style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: var(--text-muted);">
                            <i class="fas fa-map-marker-alt" style="color: var(--danger); width: 16px; text-align: center;"></i> 
                            <span class="text-truncate" title="${escapeHtml(dest.location)}">${escapeHtml(dest.location)}</span>
                        </span>
                    </div>
                    <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.5; margin-bottom: 1.5rem; height: 4.5em; overflow: hidden; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 3; line-clamp: 3;">
                        ${escapeHtml(dest.description.substring(0, 150))}${dest.description.length > 150 ? '...' : ''}
                    </p>
                    <div class="destination-actions" style="display: flex; gap: 0.5rem; border-top: 1px solid var(--card-border); padding-top: 1.25rem; flex-wrap: wrap;">
                        <a href="edit_destination.php?id=${dest.id}" class="btn btn-outline" style="flex: 1; border-radius: 50px; font-size: 0.9rem; padding: 0.6rem;">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <form action="delete_destination.php" method="POST" style="flex: 1;" onsubmit="return confirm('Are you sure you want to delete this destination?')">
                            <input type="hidden" name="id" value="${dest.id}">
                            <button type="submit" class="btn btn-danger" style="width: 100%; border-radius: 50px; font-size: 0.9rem; padding: 0.6rem;">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                        <a href="manage_hotels.php?destination_id=${dest.id}" class="btn btn-primary" style="flex: 1; border-radius: 50px; font-size: 0.9rem; padding: 0.6rem; background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">
                            <i class="fas fa-hotel"></i> Hotels
                        </a>
                        <a href="manage_flights.php?destination_id=${dest.id}" class="btn btn-primary" style="flex: 1; border-radius: 50px; font-size: 0.9rem; padding: 0.6rem; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;">
                            <i class="fas fa-plane"></i> Flights
                        </a>
                    </div>
                </div>
            </div>
        `;
    }
    html += '</div>';
    destinationsContainer.innerHTML = html;
}

function renderLineView() {
    if (currentFilteredData.length === 0) {
        destinationsContainer.innerHTML = '';
        return;
    }
    
    let html = '<div class="line-view-container">';
    for (const dest of currentFilteredData) {
        let seasonDisplay = dest.season ? dest.season.replace(/,/g, ', ') : 'N/A';
        let peopleDisplay = dest.people || 'N/A';
        
        html += `
            <div class="destination-line-item" style="display: flex; flex-wrap: wrap; gap: 1.5rem; padding: 1.5rem; border-bottom: 1px solid var(--card-border); align-items: center; transition: background 0.2s;">
                <div class="line-item-image" style="flex: 0 0 100px; height: 80px; border-radius: 12px; overflow: hidden; background: var(--bg-base);">
                    <img src="${escapeHtml(dest.first_image)}" alt="${escapeHtml(dest.name)}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../image/no-image.jpg'">
                </div>
                <div class="line-item-details" style="flex: 3; min-width: 200px;">
                    <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-main);">${escapeHtml(dest.name)}</h4>
                    <div style="display: flex; flex-wrap: wrap; gap: 1rem; font-size: 0.85rem; color: var(--text-muted);">
                        <span><i class="fas fa-tag"></i> ${escapeHtml(dest.type)}</span>
                        <span><i class="fas fa-map-marker-alt"></i> ${escapeHtml(dest.location)}</span>
                        <span><i class="fas fa-rupee-sign"></i> ₹${numberFormat(dest.budget)}/day</span>
                        <span><i class="fas fa-calendar-alt"></i> ${escapeHtml(seasonDisplay)}</span>
                        <span><i class="fas fa-user-friends"></i> ${escapeHtml(peopleDisplay)}</span>
                    </div>
                    <div class="line-item-description" style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        ${escapeHtml(dest.description.substring(0, 100))}${dest.description.length > 100 ? '...' : ''}
                    </div>
                </div>
                <div class="line-item-actions" style="flex: 1; display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: flex-end;">
                    <a href="edit_destination.php?id=${dest.id}" class="btn btn-outline" style="padding: 0.4rem 1rem; font-size: 0.8rem; border-radius: 50px;">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <form action="delete_destination.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this destination?')">
                        <input type="hidden" name="id" value="${dest.id}">
                        <button type="submit" class="btn btn-danger" style="padding: 0.4rem 1rem; font-size: 0.8rem; border-radius: 50px;">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                    <a href="manage_hotels.php?destination_id=${dest.id}" class="btn btn-primary" style="padding: 0.4rem 1rem; font-size: 0.8rem; border-radius: 50px; background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">
                        <i class="fas fa-hotel"></i> Hotels
                    </a>
                    <a href="manage_flights.php?destination_id=${dest.id}" class="btn btn-primary" style="padding: 0.4rem 1rem; font-size: 0.8rem; border-radius: 50px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;">
                        <i class="fas fa-plane"></i> Flights
                    </a>
                </div>
            </div>
        `;
    }
    html += '</div>';
    destinationsContainer.innerHTML = html;
}

function renderCurrentView() {
    if (currentViewMode === 'card') {
        destinationsContainer.className = 'destinations-view destinations-card-view';
        renderCardView();
    } else {
        destinationsContainer.className = 'destinations-view destinations-line-view';
        renderLineView();
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function numberFormat(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function handleSearch() {
    currentSearchTerm = searchInput.value;
    filterDestinations();
}

function handleSort() {
    currentSortValue = sortSelect.value;
    currentFilteredData = sortDestinations(currentFilteredData);
    renderCurrentView();
}

function handleClearSearch() {
    searchInput.value = '';
    currentSearchTerm = '';
    currentSortValue = 'newest';
    sortSelect.value = 'newest';
    filterDestinations();
}

function setViewMode(mode) {
    currentViewMode = mode;
    if (mode === 'card') {
        cardViewBtn.classList.add('active');
        lineViewBtn.classList.remove('active');
    } else {
        lineViewBtn.classList.add('active');
        cardViewBtn.classList.remove('active');
    }
    renderCurrentView();
}

document.addEventListener('DOMContentLoaded', function() {
    setViewMode('card');
    updateResultsCount();
    
    searchInput.addEventListener('input', handleSearch);
    sortSelect.addEventListener('change', handleSort);
    clearBtn.addEventListener('click', handleClearSearch);
    cardViewBtn.addEventListener('click', () => setViewMode('card'));
    lineViewBtn.addEventListener('click', () => setViewMode('line'));
    
    const showBtn = document.getElementById('showAddFormBtn');
    const container = document.getElementById('addFormContainer');
    const closeBtn = document.getElementById('closeFormBtn');
    const cancelBtn = document.getElementById('cancelFormBtn');
    
    function openForm() {
        if (container) {
            container.style.display = 'block';
            if (showBtn) showBtn.style.display = 'none';
            container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
    function closeForm() {
        if (container) {
            container.style.display = 'none';
            if (showBtn) showBtn.style.display = 'inline-flex';
        }
    }
    if (showBtn) showBtn.addEventListener('click', openForm);
    if (closeBtn) closeBtn.addEventListener('click', closeForm);
    if (cancelBtn) cancelBtn.addEventListener('click', closeForm);
});
</script>

<style>
/* Your existing styles remain exactly as they were – no changes needed */
.form-group { margin-bottom: 1.8rem; }
.form-group label { display: block; margin-bottom: 0.6rem; font-weight: 700; color: var(--text-main); font-size: 0.95rem; }
.form-control { width: 100%; padding: 0.9rem 1.2rem; border: 1px solid var(--card-border); border-radius: 12px; font-size: 1rem; background: var(--bg-base); color: var(--text-main); transition: all 0.3s; font-family: inherit; }
.form-control:focus { border-color: var(--secondary); outline: none; box-shadow: 0 0 0 4px var(--glow-color); background: var(--bg-surface); }
select.form-control option { padding: 0.5rem; background: var(--bg-surface); color: var(--text-main); }
textarea.form-control { min-height: 150px; resize: vertical; line-height: 1.6; }
.btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.8rem 1.8rem; border-radius: 12px; font-weight: 700; cursor: pointer; transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1); border: none; gap: 8px; font-size: 1rem; }
.btn-primary { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; }
.btn-flight { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
.btn-primary:hover { transform: translateY(-3px); box-shadow: 0 10px 25px var(--glow-color); }
.btn-outline { background: transparent; border: 2px solid var(--card-border); color: var(--text-main); }
.btn-outline:hover { background: var(--bg-base); border-color: var(--primary); color: var(--primary); }
.btn-danger { background: transparent; border: 2px solid rgba(239, 68, 68, 0.3); color: var(--danger); }
.btn-danger:hover { background: var(--danger); color: white; border-color: var(--danger); box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3); }
.alert { padding: 1.2rem 1.5rem; border-radius: 16px; margin-bottom: 2rem; border-left: 5px solid transparent; font-weight: 600; display: flex; align-items: center; gap: 12px; box-shadow: 0 10px 20px var(--shadow-color); }
.alert-success { background-color: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid var(--card-border); border-left-color: var(--success); }
.alert-danger { background-color: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid var(--card-border); border-left-color: var(--danger); }
.destination-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 2rem; }
@media (max-width: 1200px) { .destination-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 900px) { .destination-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 600px) { .destination-grid { grid-template-columns: 1fr; } }
.destination-images { height: 220px; overflow: hidden; position: relative; }
.destination-images img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s cubic-bezier(0.25, 1, 0.5, 1); }
.destination-card:hover .destination-images img { transform: scale(1.08); }
.destination-type-badge { position: absolute; top: 15px; right: 15px; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); color: white; padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; border: 1px solid rgba(255, 255, 255, 0.2); box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
.destination-info { padding: 1.8rem; }
.status-badge { display: inline-flex; align-items: center; padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.9rem; font-weight: 700; }
.status-active { background: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.2); }
@keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.fade-in { animation: fadeIn 0.6s cubic-bezier(0.25, 1, 0.5, 1) forwards; }
.destination-actions { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; }
.destination-actions .btn { margin: 0; padding: 0.6rem; font-size: 0.85rem; }
.destination-actions .btn-primary:first-of-type { grid-column: span 2; margin-bottom: 0.25rem; }
.destination-actions .btn-primary { background: linear-gradient(135deg, var(--primary), var(--secondary)); }
.destination-actions .btn-primary:hover { transform: translateY(-2px); }
.view-toggle { display: flex; gap: 0.5rem; background: var(--bg-base); padding: 0.3rem; border-radius: 50px; border: 1px solid var(--card-border); }
.view-toggle-btn { background: transparent; border: none; padding: 0.5rem 1rem; border-radius: 50px; cursor: pointer; transition: all 0.2s; color: var(--text-muted); font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem; }
.view-toggle-btn.active { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
.view-toggle-btn:hover:not(.active) { background: var(--bg-surface); color: var(--text-main); }
.line-view-container { display: flex; flex-direction: column; gap: 0; }
.destination-line-item:hover { background: var(--bg-base); border-radius: 12px; }
@media (max-width: 768px) { .destination-line-item { flex-direction: column; align-items: flex-start !important; } .line-item-actions { justify-content: flex-start !important; width: 100%; } .destination-grid { grid-template-columns: 1fr; } }
.page { max-width: 1400px; margin: 40 auto; padding: 1rem; }
.text-truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px; }
</style>

<?php include 'admin_footer.php'; ?>