<?php
// Start the session for admin authentication
session_start();
/*
// Redirect to login if admin is not logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}
*/
// Include database configuration and connection
include '../database/dbconfig.php';

// Get admin info from session
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];
$admin_email = $_SESSION['admin_email'];

// Handle form submission for adding a new destination
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and get form inputs
    $name = $conn->real_escape_string($_POST['name']);
    $type = $conn->real_escape_string($_POST['type']);
    $description = $conn->real_escape_string($_POST['description']);
    $location = $conn->real_escape_string($_POST['location']);
    $budget = (float)$_POST['budget'];
    $map_link = $conn->real_escape_string($_POST['map_link']);
    
    // Handle multiple select for season and people
    $season = isset($_POST['season']) ? implode(',', array_map([$conn, 'real_escape_string'], $_POST['season'])) : '';
    $people = isset($_POST['people']) ? implode(',', array_map([$conn, 'real_escape_string'], $_POST['people'])) : '';
    
    // Handle image upload
    $image_urls = [];
    if (!empty($_FILES['images']['name'][0])) {
        $upload_dir = 'uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $file_name = basename($_FILES['images']['name'][$key]);
            $file_path = $upload_dir . uniqid() . '_' . $file_name;
            if (move_uploaded_file($tmp_name, $file_path)) {
                $image_urls[] = $file_path;
            }
        }
    }
    $image_urls_json = json_encode($image_urls);

    // Insert new destination
    $stmt = $conn->prepare("INSERT INTO destinations (name, type, description, location, budget, image_urls, map_link, season, people) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssdssss", $name, $type, $description, $location, $budget, $image_urls_json, $map_link, $season, $people);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Destination added successfully!";
    } else {
        $_SESSION['message'] = "Error adding destination: " . $conn->error;
    }
    
    header("Location: add_destanition_on_admin.php");
    exit();
}

// Get admin info with profile picture
$admin_id = $_SESSION['admin_id'];
$admin_query = $conn->prepare("SELECT name, email, profile_pic FROM admin WHERE id = ?");
$admin_query->bind_param("i", $admin_id);
$admin_query->execute();
$admin_result = $admin_query->get_result();
$admin = $admin_result->fetch_assoc() ?? ['name' => 'Unknown', 'email' => '', 'profile_pic' => NULL];

$admin_name = $admin['name'];
$admin_email = $admin['email'];
$admin_profile_pic = $admin['profile_pic'];

// Fetch all existing destinations
$result = $conn->query("SELECT * FROM destinations ORDER BY id DESC");
?>

<?php include 'admin_header.php'; ?>

<div class="main-content">

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert <?= strpos($_SESSION['message'], 'Error') === false ? 'alert-success' : 'alert-danger' ?> fade-in">
            <i class="fas fa-<?= strpos($_SESSION['message'], 'Error') === false ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= $_SESSION['message'] ?>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <div class="add-button-wrapper" style="margin: 2rem 0; text-align: center;">
        <button id="showAddFormBtn" class="btn btn-primary" style="font-size: 1.3rem; padding: 1rem 2.5rem; border-radius: 50px; box-shadow: 0 10px 20px var(--shadow-color);">
            <i class="fas fa-plus-circle"></i> Add New Destination
        </button>
    </div>

    <div id="addFormContainer" class="widget-card fade-in" style="display: none; overflow: hidden; transition: max-height 0.5s ease, opacity 0.4s ease; max-height: 0; opacity: 0; margin-bottom: 2rem;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--card-border); padding-bottom: 1rem;">
            <h2 style="font-size: 1.8rem; color: var(--text-main);"><i class="fas fa-plus-circle" style="color: var(--primary);"></i> Add New Destination</h2>
            <button type="button" id="closeFormBtn" class="btn btn-outline" style="border-radius: 50px;">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
        <div class="card-body">
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
                    <button type="submit" class="btn btn-primary" style="border-radius: 50px;">
                        <i class="fas fa-save"></i> Save Destination
                    </button>
                    <button type="button" id="cancelFormBtn" class="btn btn-outline" style="border-radius: 50px;">
                        <i class="fas fa-ban"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="widget-card fade-in" style="margin-bottom: 2rem;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--card-border); padding-bottom: 1rem;">
            <h2 style="font-size: 1.8rem; color: var(--text-main); margin: 0;"><i class="fas fa-map-marked-alt" style="color: var(--secondary);"></i> Existing Destinations</h2>
            <span class="status-badge status-active">Active: <?= $result->num_rows ?></span>
        </div>
        <div class="card-body">
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
                Showing all <?= $result->num_rows ?> destinations
            </div>
            
            <div class="destination-grid" id="destinationsContainer">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="destination-card widget-card" style="padding: 0; overflow: hidden;" data-destination='<?= htmlspecialchars(json_encode([
                        'id' => $row['id'],
                        'name' => $row['name'],
                        'type' => $row['type'],
                        'location' => $row['location'],
                        'budget' => $row['budget'],
                        'created' => $row['created_at'] ?? ''
                    ]), ENT_QUOTES, 'UTF-8') ?>'>
                        <div class="destination-images">
                            <?php 
                            if (!empty($row['image_urls'])): 
                                $images = json_decode($row['image_urls'], true);
                                if (is_array($images) && !empty($images[0])): ?>
                                    <img src="<?= htmlspecialchars($images[0]) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                                <?php else: ?>
                                    <img src="Uploads/default.jpg" alt="Default destination image">
                                <?php endif; ?>
                            <?php else: ?>
                                <img src="Uploads/default.jpg" alt="Default destination image">
                            <?php endif; ?>
                            <div class="destination-type-badge">
                                <i class="fas fa-tag"></i> <?= ucfirst($row['type']) ?>
                            </div>
                        </div>
                        <div class="destination-info">
                            <h3 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 1rem; color: var(--text-main);"><?= htmlspecialchars($row['name']) ?></h3>
                            
                            <div class="destination-meta" style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; margin-bottom: 1.5rem;">
                                <span style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: var(--text-muted);">
                                    <i class="fas fa-calendar-alt" style="color: var(--primary); width: 16px; text-align: center;"></i> 
                                    <span class="text-truncate" title="<?= str_replace(',', ', ', ucwords($row['season'] ?? '', ',')) ?>"><?= str_replace(',', ', ', ucwords($row['season'] ?? '', ',')) ?></span>
                                </span>
                                <span style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: var(--text-muted);">
                                    <i class="fas fa-user-friends" style="color: var(--secondary); width: 16px; text-align: center;"></i> 
                                    <?= str_replace(',', ', ', $row['people'] ?? '') ?>
                                </span>
                                <span style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: var(--text-muted);">
                                    <i class="fas fa-rupee-sign" style="color: var(--primary); width: 16px; text-align: center;"></i> 
                                    <span style="font-weight: 700; color: var(--text-main);"><?= number_format($row['budget']) ?></span>/day
                                </span>
                                <span style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: var(--text-muted);">
                                    <i class="fas fa-map-marker-alt" style="color: var(--danger); width: 16px; text-align: center;"></i> 
                                    <span class="text-truncate" title="<?= htmlspecialchars($row['location']) ?>"><?= htmlspecialchars($row['location']) ?></span>
                                </span>
                            </div>
                            
                            <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.5; margin-bottom: 1.5rem; height: 4.5em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                                <?= htmlspecialchars($row['description'] ?? '') ?>
                            </p>
                            
                            <div class="destination-actions" style="display: flex; gap: 0.5rem; border-top: 1px solid var(--card-border); padding-top: 1.25rem; flex-wrap: wrap;">
                                <a href="edit_destination.php?id=<?= $row['id'] ?>" class="btn btn-outline" style="flex: 1; border-radius: 50px; font-size: 0.9rem; padding: 0.6rem;">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form action="delete_destination.php" method="POST" style="flex: 1;">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn btn-danger" style="width: 100%; border-radius: 50px; font-size: 0.9rem; padding: 0.6rem;" onclick="return confirm('Are you sure you want to delete this destination?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                                <a href="manage_hotels.php?destination_id=<?= $row['id'] ?>" class="btn btn-primary" style="flex: 1; border-radius: 50px; font-size: 0.9rem; padding: 0.6rem; background: linear-gradient(135deg, #f59e0b, #d97706);">
                                    <i class="fas fa-hotel"></i> Hotels
                                </a>
                                <a href="manage_flights.php?destination_id=<?= $row['id'] ?>" class="btn btn-primary" style="flex: 1; border-radius: 50px; font-size: 0.9rem; padding: 0.6rem; background: linear-gradient(135deg, #3b82f6, #2563eb);">
                                    <i class="fas fa-plane"></i> Flights
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <div id="noResults" style="display: none; text-align: center; padding: 4rem 2rem; background: var(--bg-base); border-radius: 24px; border: 1px dashed var(--card-border); margin-top: 2rem;">
                <i class="fas fa-search" style="font-size: 4rem; margin-bottom: 1.5rem; color: var(--text-muted); opacity: 0.5;"></i>
                <h3 style="color: var(--text-main); font-size: 1.5rem; margin-bottom: 0.5rem;">No destinations found</h3>
                <p style="color: var(--text-muted);">Try adjusting your search or filter terms</p>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const showBtn       = document.getElementById('showAddFormBtn');
    const container     = document.getElementById('addFormContainer');
    const closeBtn      = document.getElementById('closeFormBtn');
    const cancelBtn     = document.getElementById('cancelFormBtn');
    const searchInput   = document.getElementById('destinationSearch');
    const sortSelect    = document.getElementById('sortOrder');
    const clearBtn      = document.getElementById('clearSearch');
    const destContainer = document.getElementById('destinationsContainer');
    const noResultsMsg  = document.getElementById('noResults');
    const resultsCount  = document.getElementById('resultsCount');

    // Store original destination cards
    const originalDestinations = Array.from(destContainer.children);
    let currentDestinations = [...originalDestinations];

    function openForm() {
        container.style.display = 'block';
        // Small delay to trigger transition
        setTimeout(() => {
            container.style.maxHeight = container.scrollHeight + 500 + 'px'; // Add extra space for dropdowns
            container.style.opacity = '1';
        }, 10);
        showBtn.style.display = 'none';
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function closeForm() {
        container.style.maxHeight = '0';
        container.style.opacity = '0';
        setTimeout(() => {
            container.style.display = 'none';
        }, 500); // match transition duration
        showBtn.style.display = 'inline-flex';
    }

    // Search function
    function performSearch() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        
        if (searchTerm === '') {
            currentDestinations = [...originalDestinations];
        } else {
            currentDestinations = originalDestinations.filter(card => {
                const data = JSON.parse(card.getAttribute('data-destination'));
                const name = data.name.toLowerCase();
                const type = data.type.toLowerCase();
                const location = data.location.toLowerCase();
                
                return name.includes(searchTerm) || 
                       type.includes(searchTerm) || 
                       location.includes(searchTerm);
            });
        }
        
        sortDestinations(currentDestinations);
        updateDestinationDisplay();
    }

    // Sort function
    function sortDestinations(destinations) {
        const sortValue = sortSelect.value;
        
        destinations.sort((a, b) => {
            const dataA = JSON.parse(a.getAttribute('data-destination'));
            const dataB = JSON.parse(b.getAttribute('data-destination'));
            
            switch(sortValue) {
                case 'newest':
                    return new Date(dataB.created) - new Date(dataA.created);
                case 'oldest':
                    return new Date(dataA.created) - new Date(dataB.created);
                case 'name-asc':
                    return dataA.name.localeCompare(dataB.name);
                case 'name-desc':
                    return dataB.name.localeCompare(dataA.name);
                case 'budget-low':
                    return parseFloat(dataA.budget) - parseFloat(dataB.budget);
                case 'budget-high':
                    return parseFloat(dataB.budget) - parseFloat(dataA.budget);
                default:
                    return 0;
            }
        });
    }

    // Update display of destinations
    function updateDestinationDisplay() {
        destContainer.innerHTML = '';
        
        if (currentDestinations.length === 0) {
            noResultsMsg.style.display = 'block';
            destContainer.style.display = 'none';
            resultsCount.textContent = 'No destinations found';
        } else {
            noResultsMsg.style.display = 'none';
            destContainer.style.display = 'grid';
            currentDestinations.forEach(card => {
                destContainer.appendChild(card.cloneNode(true));
            });
            resultsCount.textContent = `Showing ${currentDestinations.length} of ${originalDestinations.length} destinations`;
        }
    }

    // Clear search
    function clearSearch() {
        searchInput.value = '';
        currentDestinations = [...originalDestinations];
        sortSelect.value = 'newest';
        updateDestinationDisplay();
    }

    // Event listeners
    showBtn.addEventListener('click', openForm);
    closeBtn.addEventListener('click', closeForm);
    cancelBtn.addEventListener('click', closeForm);
    
    searchInput.addEventListener('input', performSearch);
    sortSelect.addEventListener('change', () => {
        sortDestinations(currentDestinations);
        updateDestinationDisplay();
    });
    clearBtn.addEventListener('click', clearSearch);

    // Initialize display
    updateDestinationDisplay();

});
</script>

<style>
    /* Styles aligned with index.html theme */
    
    .form-group {
        margin-bottom: 1.8rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.6rem;
        font-weight: 700;
        color: var(--text-main);
        font-size: 0.95rem;
    }
    
    .form-control {
        width: 100%;
        padding: 0.9rem 1.2rem;
        border: 1px solid var(--card-border);
        border-radius: 12px;
        font-size: 1rem;
        background: var(--bg-base);
        color: var(--text-main);
        transition: all 0.3s;
        font-family: inherit;
    }
    
    .form-control:focus {
        border-color: var(--secondary);
        outline: none;
        box-shadow: 0 0 0 4px var(--glow-color);
        background: var(--bg-surface);
    }

    select.form-control option {
        padding: 0.5rem;
        background: var(--bg-surface);
        color: var(--text-main);
    }
    
    textarea.form-control {
        min-height: 150px;
        resize: vertical;
        line-height: 1.6;
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.8rem 1.8rem;
        border-radius: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
        border: none;
        gap: 8px;
        font-size: 1rem;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px var(--glow-color);
    }
    
    .btn-outline {
        background: transparent;
        border: 2px solid var(--card-border);
        color: var(--text-main);
    }
    
    .btn-outline:hover {
        background: var(--bg-base);
        border-color: var(--primary);
        color: var(--primary);
    }
    
    .btn-danger {
        background: transparent;
        border: 2px solid rgba(239, 68, 68, 0.3);
        color: var(--danger);
    }
    
    .btn-danger:hover {
        background: var(--danger);
        color: white;
        border-color: var(--danger);
        box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
    }
    
    .alert {
        padding: 1.2rem 1.5rem;
        border-radius: 16px;
        margin-bottom: 2rem;
        border-left: 5px solid transparent;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 10px 20px var(--shadow-color);
    }
    
    .alert-success {
        background-color: var(--bg-surface);
        color: var(--success);
        border: 1px solid var(--card-border);
        border-left-color: var(--success);
    }
    
    .alert-danger {
        background-color: var(--bg-surface);
        color: var(--danger);
        border: 1px solid var(--card-border);
        border-left-color: var(--danger);
    }
    
    .destination-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 2rem;
    }
    
    .destination-images {
        height: 220px;
        overflow: hidden;
        position: relative;
    }
    
    .destination-images img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s cubic-bezier(0.25, 1, 0.5, 1);
    }
    
    .destination-card:hover .destination-images img {
        transform: scale(1.08);
    }
    
    .destination-type-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        color: white;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }
    
    .destination-info {
        padding: 1.8rem;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 700;
    }
    
    .status-active {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success);
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .fade-in {
        animation: fadeIn 0.6s cubic-bezier(0.25, 1, 0.5, 1) forwards;
    }

    .destination-actions {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
    }

    .destination-actions .btn {
        margin: 0;
        padding: 0.6rem;
        font-size: 0.85rem;
    }

    .destination-actions .btn-primary:first-of-type {
        grid-column: span 2;
        margin-bottom: 0.25rem;
    }

    .destination-actions .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
    }

    .destination-actions .btn-primary:hover {
        transform: translateY(-2px);
    }
</style>

<?php include 'admin_footer.php'; ?>