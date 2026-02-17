<?php
// Start the session for admin authentication
session_start();

// Redirect to login if admin is not logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

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
    
    // Handle travel tips, local cuisines, and language (new fields)
    $tips = isset($_POST['tips']) ? json_encode($_POST['tips']) : '[]';
    $cuisines = isset($_POST['cuisines']) ? json_encode($_POST['cuisines']) : '[]';
    $language = isset($_POST['language']) ? json_encode($_POST['language']) : '[]';

    // Handle attractions - convert from textarea (one per line) to JSON array
    $attractions_json = '[]';
    if (!empty($_POST['attractions'])) {
        $attractions_array = array_filter(array_map('trim', explode("\n", $_POST['attractions'])));
        if (!empty($attractions_array)) {
            $attractions_json = json_encode(array_values($attractions_array));
        }
    }

    // Handle image upload
    $image_urls = [];
    if (!empty($_FILES['images']['name'][0])) {
        $upload_dir = 'uploads/';
        // Create upload directory if not exists
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        // Loop through uploaded images and save them
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $file_name = basename($_FILES['images']['name'][$key]);
            $file_path = $upload_dir . uniqid() . '_' . $file_name;
            // Move uploaded file to destination folder
            if (move_uploaded_file($tmp_name, $file_path)) {
                $image_urls[] = $file_path;
            }
        }
    }
    // Encode image URLs as JSON for database storage
    $image_urls_json = json_encode($image_urls);

    // Handle cuisine images upload
    $cuisine_images = [];
    if (isset($_FILES['cuisine_images'])) {
        $upload_dir_cuisine = 'uploads/cuisines/';
        if (!file_exists($upload_dir_cuisine)) {
            mkdir($upload_dir_cuisine, 0777, true);
        }
        foreach ($_FILES['cuisine_images']['tmp_name'] as $cuisine => $tmp_name) {
            if (!empty($tmp_name)) {
                $file_name = $_FILES['cuisine_images']['name'][$cuisine];
                $safe_filename = preg_replace("/[^a-zA-Z0-9.]/", "_", $file_name);
                $unique_file_name = uniqid() . '_' . $safe_filename;
                $file_path = $upload_dir_cuisine . $unique_file_name;
                if (move_uploaded_file($tmp_name, $file_path)) {
                    $cuisine_images[$cuisine] = $unique_file_name;
                }
            }
        }
    }
    $cuisine_images_json = json_encode($cuisine_images);

    // Prepare and execute SQL to insert new destination
    $stmt = $conn->prepare("INSERT INTO destinations (name, type, description, location, budget, image_urls, map_link, season, people, tips, cuisines, language, cuisine_images, attractions) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssdsssssssss", $name, $type, $description, $location, $budget, $image_urls_json, $map_link, $season, $people, $tips, $cuisines, $language, $cuisine_images_json, $attractions_json);
    $stmt->execute();
    
    // Set success message and redirect to admin page
    $_SESSION['message'] = "Destination added successfully!";
    header("Location: admin.php");
    exit();
}

// Get admin info with profile picture from database
$admin_id = $_SESSION['admin_id'];
$admin_query = $conn->prepare("SELECT name, email, profile_pic FROM admin WHERE id = ?");
$admin_query->bind_param("i", $admin_id);
$admin_query->execute();
$admin_result = $admin_query->get_result();
$admin = $admin_result->fetch_assoc() ?? ['name' => 'Unknown', 'email' => '', 'profile_pic' => NULL];

// Set admin variables for display
$admin_name = $admin['name'];
$admin_email = $admin['email'];
$admin_profile_pic = $admin['profile_pic'];

// Fetch all existing destinations from database
$result = $conn->query("SELECT * FROM destinations ORDER BY id DESC");
?>

<?php include 'admin_header.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Show success message if set -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success fade-in">
                    <i class="fas fa-check-circle"></i> <?= $_SESSION['message'] ?>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <!-- Card for adding new destination -->
            <div class="card fade-in">
                <div class="card-header">
                    <h2><i class="fas fa-plus-circle"></i> Add New Destination</h2>
                </div>
                <div class="card-body">
                    <!-- Form for adding destination -->
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
                            <select id="season" name="season[]" class="form-control" multiple required>
                                <option value="winter">Winter</option>
                                <option value="summer">Summer</option>
                                <option value="spring">Spring</option>
                                <option value="autumn">Autumn</option>
                                <option value="monsoon">Monsoon</option>
                            </select>
                            <small style="color: var(--accent);">Hold Ctrl/Cmd to select multiple options</small>
                        </div>
                        <div class="form-group">
                            <label for="people">Recommended For</label>
                            <select id="people" name="people[]" class="form-control" multiple required>
                                <option value="1">Solo (1)</option>
                                <option value="2">Couples (2)</option>
                                <option value="3-5">Small Groups (3-5)</option>
                                <option value="6-9">Medium Groups (6-9)</option>
                                <option value="9+">Large Groups (9+)</option>
                            </select>
                            <small style="color: var(--accent);">Hold Ctrl/Cmd to select multiple options</small>
                        </div>
                        <div class="form-group">
                            <label for="tips">Travel Tips</label>
                            <select id="tips" name="tips[]" class="form-control" multiple>
                                <option value="Best time to visit: Early morning">Best time to visit: Early morning</option>
                                <option value="Carry cash">Carry cash</option>
                                <option value="Book tickets in advance">Book tickets in advance</option>
                                <option value="Hire a local guide">Hire a local guide</option>
                                <option value="Use public transport">Use public transport</option>
                                <option value="Try local food">Try local food</option>
                                <option value="Respect local customs">Respect local customs</option>
                                <option value="Wear appropriate clothing">Wear appropriate clothing</option>
                                <option value="Carry water bottles">Carry water bottles</option>
                                <option value="Learn basic local phrases">Learn basic local phrases</option>
                            </select>
                            <small style="color: var(--accent);">Hold Ctrl/Cmd to select multiple tips</small>
                        </div>

                        <div class="form-group">
                            <label for="cuisines">Local Cuisines</label>
                            <div class="cuisine-container">
                                <select id="cuisines" name="cuisines[]" class="form-control" multiple></select>
                                <button type="button" class="btn btn-outline btn-sm" onclick="addNewCuisine()">
                                    <i class="fas fa-plus"></i> Add New Cuisine
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Cuisine Images</label>
                            <div id="cuisine-images-container"></div>
                            <small style="color: var(--accent);">Upload images for the selected cuisines.</small>
                        </div>

                        <div class="form-group">
                            <label for="attractions">Top Attractions (one per line)</label>
                            <textarea id="attractions" name="attractions" class="form-control" rows="4"></textarea>
                            <small style="color: var(--accent);">Enter each attraction on a new line.</small>
                        </div>

                        <div class="form-group">
                            <label for="language">Local Languages</label>
                            <select id="language" name="language[]" class="form-control" multiple>
                                <option value="Hindi">Hindi</option>
                                <option value="Bengali">Bengali</option>
                                <option value="Telugu">Telugu</option>
                                <option value="Marathi">Marathi</option>
                                <option value="Tamil">Tamil</option>
                                <option value="Urdu">Urdu</option>
                                <option value="Gujarati">Gujarati</option>
                                <option value="Kannada">Kannada</option>
                                <option value="Odia">Odia</option>
                                <option value="Punjabi">Punjabi</option>
                                <option value="Malayalam">Malayalam</option>
                                <option value="Assamese">Assamese</option>
                                <option value="Maithili">Maithili</option>
                                <option value="Sanskrit">Sanskrit</option>
                                <option value="English">English</option>
                            </select>
                            <small style="color: var(--accent);">Hold Ctrl/Cmd to select multiple languages</small>
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
                            <input type="file" id="images" name="images[]" class="form-control" multiple accept="image/*">
                            <small style="color: var(--accent);">Select Your images</small>
                        </div>
                        <div class="form-group">
                            <label for="map_link">Google Map Link</label>
                            <input type="url" id="map_link" name="map_link" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Destination
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Card for displaying existing destinations -->
            <div class="card fade-in">
                <div class="card-header">
                    <h2><i class="fas fa-map-marked-alt"></i> Existing Destinations</h2>
                    <span class="status-badge status-active">Active: <?= $result->num_rows ?></span>
                </div>
                <div class="card-body">
                    <div class="destination-grid">
                        <!-- Loop through destinations and display each card -->
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <div class="destination-card">
                                <div class="destination-images">
                                    <?php if (!empty($row['image_urls'])): 
                                        $images = json_decode($row['image_urls'], true);
                                        if (is_array($images) && !empty($images[0])): ?>
                                            <img src="<?= htmlspecialchars($images[0]) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                                        <?php else: ?>
                                            <img src="Uploads/default.jpg" alt="Default destination image">
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <img src="Uploads/default.jpg" alt="Default destination image">
                                    <?php endif; ?>
                                </div>
                                <div class="destination-info">
                                    <h3><?= htmlspecialchars($row['name']) ?></h3>

                                    <div class="detail-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span class="label">Location:</span>
                                        <span class="value"><?= htmlspecialchars($row['location']) ?></span>
                                    </div>

                                    <div class="detail-item">
                                        <i class="fas fa-tag"></i>
                                        <span class="label">Type:</span>
                                        <span class="value"><?= htmlspecialchars($row['type']) ?></span>
                                    </div>

                                    <div class="meta-tags">
                                        <?php
                                        $seasons = explode(',', $row['season']);
                                        foreach ($seasons as $season): ?>
                                            <span class="meta-tag">
                                                <i class="fas fa-calendar"></i>
                                                <?= htmlspecialchars(trim($season)) ?>
                                            </span>
                                        <?php endforeach; ?>

                                        <?php
                                        $people = json_decode($row['people'], true);
                                        if (is_array($people)) {
                                            foreach ($people as $person): ?>
                                                <span class="meta-tag">
                                                    <i class="fas fa-users"></i>
                                                    <?= htmlspecialchars($person) ?>
                                                </span>
                                            <?php endforeach;
                                        } else {
                                            $people_array = explode(',', $row['people']);
                                            foreach ($people_array as $person): ?>
                                                <span class="meta-tag">
                                                    <i class="fas fa-users"></i>
                                                    <?= htmlspecialchars(trim($person)) ?>
                                                </span>
                                            <?php endforeach;
                                        } ?>
                                    </div>

                                    <div class="price-tag">
                                        <i class="fas fa-rupee-sign"></i>
                                        <?= number_format($row['budget']) ?>
                                    </div>
                                </div>
                                <div class="card-buttons">
                                    <!-- Edit button for destination -->
                                    <a href="edit_destination.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <!-- Delete button for destination -->
                                    <form action="delete_destination.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this destination?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>

    <style>
        /* Additional Styles from admin.php for Form and Destination Cards */
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            border-top: 3px solid var(--accent);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
        }
        
        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(to right, rgba(22,3,79,0.03), white);
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 1.4rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-header h2 i {
            color: var(--secondary);
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--primary);
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,194,203,0.2);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.8rem 1.8rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            gap: 10px;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), #1a5276);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #1a5276, var(--primary));
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(22,3,79,0.3);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background: rgba(22,3,79,0.05);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #e53e3e, #c53030);
            color: white;
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #c53030, #e53e3e);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border-left: 4px solid transparent;
        }
        
        .alert-success {
            background-color: #f0fff4;
            color: #2f855a;
            border-left-color: #38a169;
        }
        
        /* --- New/Improved Destination Card Styles --- */
        .destination-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .destination-card {
            position: relative;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            padding-bottom: 65px;
            overflow: hidden;
        }
        
        .destination-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }
        
        .destination-images {
            height: 200px;
            overflow: hidden;
        }
        
        .destination-images img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .destination-card:hover .destination-images img {
            transform: scale(1.1);
        }
        
        .destination-info {
            padding: 20px;
        }
        
        .destination-info h3 {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 15px;
            font-weight: 600;
            border-bottom: 2px solid var(--accent);
            padding-bottom: 8px;
            display: inline-block;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            margin: 12px 0;
            padding: 8px 12px;
            background: linear-gradient(to right, rgba(22, 3, 79, 0.05), transparent);
            border-radius: 8px;
            transition: transform 0.2s ease;
        }
        
        .detail-item:hover {
            transform: translateX(5px);
        }
        
        .detail-item i {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            margin-right: 12px;
            font-size: 0.9rem;
        }
        
        .detail-item .label {
            font-weight: 500;
            color: var(--primary);
            margin-right: 8px;
            min-width: 70px;
        }
        
        .detail-item .value {
            color: #555;
        }
        
        .meta-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 15px 0;
            padding: 15px 0;
            border-top: 1px dashed #ddd;
        }
        
        .meta-tag {
            background: linear-gradient(135deg, var(--primary), #1a5276);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .meta-tag i {
            font-size: 0.8rem;
        }
        
        .price-tag {
            background: linear-gradient(135deg, var(--accent), #ff9800);
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 1.2rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 15px;
            box-shadow: 0 2px 10px rgba(0, 194, 203, 0.2);
        }
        
        .card-buttons {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 12px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            background: #f8f9fa;
            border-top: 1px solid #eee;
        }
        
        .card-buttons .btn {
            flex: 1;
            padding: 8px 15px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-active {
            background: #c6f6d5;
            color: #2f855a;
        }
        
        /* Animation for new elements */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dynamic form logic for Cuisines
            const availableCuisines = [
                "Biryani", "Butter Chicken", "Paneer Tikka", "Masala Dosa", "Chole Bhature",
                "Rogan Josh", "Dhokla", "Fish Curry", "Vada Pav", "Idli Sambhar",
                "Pav Bhaji", "Samosa", "Pani Puri", "Tandoori Chicken", "Dal Makhani",
                "Rajma Chawal", "Aloo Paratha", "Kathi Roll", "Pongal", "Litti Chokha",
                "Kulfi", "Jalebi", "Gulab Jamun", "Rasgulla", "Barfi"
            ];

            const cuisinesSelect = document.getElementById('cuisines');
            const cuisineImagesContainer = document.getElementById('cuisine-images-container');

            function initCuisinesSelect() {
                cuisinesSelect.innerHTML = '';
                availableCuisines.forEach(cuisine => {
                    const option = document.createElement('option');
                    option.value = cuisine;
                    option.textContent = cuisine;
                    cuisinesSelect.appendChild(option);
                });
                updateCuisineImagesContainer();
            }

            function addNewCuisine() {
                const newCuisine = prompt('Enter the name of the new cuisine:');
                if (newCuisine && !availableCuisines.includes(newCuisine)) {
                    availableCuisines.push(newCuisine);
                    initCuisinesSelect();
                    alert('New cuisine added. Please select it from the list.');
                } else if (newCuisine) {
                    alert('This cuisine already exists!');
                }
            }

            function updateCuisineImagesContainer() {
                cuisineImagesContainer.innerHTML = '';
                const selectedOptions = Array.from(cuisinesSelect.selectedOptions);
                selectedOptions.forEach(option => {
                    const cuisine = option.value;
                    const cuisineGroup = document.createElement('div');
                    cuisineGroup.className = 'cuisine-image-group';
                    cuisineGroup.innerHTML = `
                        <label>Upload image for ${cuisine}:</label>
                        <input type="file" name="cuisine_images[${cuisine}]" accept="image/*" class="form-control">
                    `;
                    cuisineImagesContainer.appendChild(cuisineGroup);
                });
            }

            cuisinesSelect.addEventListener('change', updateCuisineImagesContainer);
            window.addNewCuisine = addNewCuisine; // Make function globally accessible
            initCuisinesSelect();
        });
    </script>

<?php include 'admin_footer.php'; ?>