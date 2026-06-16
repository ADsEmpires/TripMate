<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

include '../database/dbconfig.php';

$destination_id = isset($_GET['destination_id']) && is_numeric($_GET['destination_id']) ? (int) $_GET['destination_id'] : 0;
if ($destination_id === 0) {
    $_SESSION['message'] = "Invalid destination ID";
    header("Location: add_destination_on_admin.php");
    exit();
}

$dest_query = $conn->prepare("SELECT name FROM destinations WHERE id = ?");
$dest_query->bind_param("i", $destination_id);
$dest_query->execute();
$dest_result = $dest_query->get_result();
if ($dest_result->num_rows === 0) {
    $_SESSION['message'] = "Destination not found";
    header("Location: add_destination_on_admin.php");
    exit();
}
$destination = $dest_result->fetch_assoc();
$destination_name = $destination['name'];

// ========== ADD / EDIT HOTEL ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // ---------- ADD ----------
        if ($_POST['action'] === 'add') {
            $hotel_name = $conn->real_escape_string($_POST['hotel_name']);
            $hotel_type = $conn->real_escape_string($_POST['hotel_type']);
            $price_per_night = (float) $_POST['price_per_night'];
            $hotel_rating = (float) $_POST['hotel_rating'];
            $description = $conn->real_escape_string($_POST['description']);
            $amenities = isset($_POST['amenities']) ? json_encode(array_map([$conn, 'real_escape_string'], $_POST['amenities'])) : '[]';
            $address = $conn->real_escape_string($_POST['address']);
            $contact_number = $conn->real_escape_string($_POST['contact_number']);
            $check_in_time = $_POST['check_in_time'];
            $check_out_time = $_POST['check_out_time'];
            $free_cancellation = isset($_POST['free_cancellation']) ? 1 : 0;
            $breakfast_included = isset($_POST['breakfast_included']) ? 1 : 0;

            // Image upload
            $image_url = '';
            if (!empty($_FILES['image']['name'])) {
                $full_upload_dir = __DIR__ . '/../uploads/hotels/';
                $web_upload_dir = 'uploads/hotels/';
                if (!file_exists($full_upload_dir)) mkdir($full_upload_dir, 0777, true);
                $unique = uniqid() . '_' . basename($_FILES['image']['name']);
                $full_path = $full_upload_dir . $unique;
                $web_path = $web_upload_dir . $unique;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $full_path)) {
                    $image_url = $web_path;
                }
            }

            $stars = (int)$hotel_rating;
            // Insert with city_id = destination_id (since no separate city table is used)
            $stmt = $conn->prepare("INSERT INTO hotels (destination_id, city_id, hotel_name, name, hotel_type, price_per_night, hotel_rating, stars, description, amenities, image_url, address, contact_number, check_in_time, check_out_time, free_cancellation, breakfast_included) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssddisssssssii", $destination_id, $destination_id, $hotel_name, $hotel_name, $hotel_type, $price_per_night, $hotel_rating, $stars, $description, $amenities, $image_url, $address, $contact_number, $check_in_time, $check_out_time, $free_cancellation, $breakfast_included);

            if ($stmt->execute()) {
                $_SESSION['message'] = "Hotel added successfully!";
            } else {
                $_SESSION['message'] = "Error adding hotel: " . $conn->error;
            }
        }

        // ---------- EDIT ----------
        elseif ($_POST['action'] === 'edit' && isset($_POST['hotel_id'])) {
            $hotel_id = (int) $_POST['hotel_id'];
            $hotel_name = $conn->real_escape_string($_POST['hotel_name']);
            $hotel_type = $conn->real_escape_string($_POST['hotel_type']);
            $price_per_night = (float) $_POST['price_per_night'];
            $hotel_rating = (float) $_POST['hotel_rating'];
            $description = $conn->real_escape_string($_POST['description']);
            $amenities = isset($_POST['amenities']) ? json_encode(array_map([$conn, 'real_escape_string'], $_POST['amenities'])) : '[]';
            $address = $conn->real_escape_string($_POST['address']);
            $contact_number = $conn->real_escape_string($_POST['contact_number']);
            $check_in_time = $_POST['check_in_time'];
            $check_out_time = $_POST['check_out_time'];
            $free_cancellation = isset($_POST['free_cancellation']) ? 1 : 0;
            $breakfast_included = isset($_POST['breakfast_included']) ? 1 : 0;

            // Get current image
            $img_query = $conn->prepare("SELECT image_url FROM hotels WHERE id = ?");
            $img_query->bind_param("i", $hotel_id);
            $img_query->execute();
            $img_result = $img_query->get_result();
            $current = $img_result->fetch_assoc();
            $image_url = $current['image_url'] ?? '';

            if (!empty($_FILES['image']['name'])) {
                $full_upload_dir = __DIR__ . '/../uploads/hotels/';
                $web_upload_dir = 'uploads/hotels/';
                if (!file_exists($full_upload_dir)) mkdir($full_upload_dir, 0777, true);
                $unique = uniqid() . '_' . basename($_FILES['image']['name']);
                $full_path = $full_upload_dir . $unique;
                $web_path = $web_upload_dir . $unique;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $full_path)) {
                    if (!empty($image_url)) {
                        $old_abs = __DIR__ . '/../' . $image_url;
                        if (file_exists($old_abs)) unlink($old_abs);
                    }
                    $image_url = $web_path;
                }
            }

            $stars = (int)$hotel_rating;
            $stmt = $conn->prepare("UPDATE hotels SET hotel_name = ?, name = ?, hotel_type = ?, price_per_night = ?, hotel_rating = ?, stars = ?, description = ?, amenities = ?, image_url = ?, address = ?, contact_number = ?, check_in_time = ?, check_out_time = ?, free_cancellation = ?, breakfast_included = ? WHERE id = ? AND destination_id = ?");
            $stmt->bind_param("ssddsdissssssiiii", $hotel_name, $hotel_name, $hotel_type, $price_per_night, $hotel_rating, $stars, $description, $amenities, $image_url, $address, $contact_number, $check_in_time, $check_out_time, $free_cancellation, $breakfast_included, $hotel_id, $destination_id);

            if ($stmt->execute()) {
                $_SESSION['message'] = "Hotel updated successfully!";
            } else {
                $_SESSION['message'] = "Error updating hotel: " . $conn->error;
            }
        }

        header("Location: manage_hotels.php?destination_id=" . $destination_id);
        exit();
    }
}

// ========== DELETE HOTEL ==========
if (isset($_GET['delete'])) {
    $hotel_id = (int) $_GET['delete'];
    $img_query = $conn->prepare("SELECT image_url FROM hotels WHERE id = ? AND destination_id = ?");
    $img_query->bind_param("ii", $hotel_id, $destination_id);
    $img_query->execute();
    $img_result = $img_query->get_result();
    if ($img_result->num_rows > 0) {
        $hotel = $img_result->fetch_assoc();
        if (!empty($hotel['image_url'])) {
            $abs_path = __DIR__ . '/../' . $hotel['image_url'];
            if (file_exists($abs_path)) unlink($abs_path);
        }
    }
    $stmt = $conn->prepare("DELETE FROM hotels WHERE id = ? AND destination_id = ?");
    $stmt->bind_param("ii", $hotel_id, $destination_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Hotel deleted successfully!";
    } else {
        $_SESSION['message'] = "Error deleting hotel: " . $conn->error;
    }
    header("Location: manage_hotels.php?destination_id=" . $destination_id);
    exit();
}

// ========== FETCH HOTELS ==========
$hotels_query = $conn->prepare("SELECT * FROM hotels WHERE destination_id = ? ORDER BY id DESC");
$hotels_query->bind_param("i", $destination_id);
$hotels_query->execute();
$hotels_result = $hotels_query->get_result();

// Admin info
$admin_id = $_SESSION['admin_id'];
$admin_query = $conn->prepare("SELECT name, email, profile_pic FROM admin WHERE id = ?");
$admin_query->bind_param("i", $admin_id);
$admin_query->execute();
$admin_result = $admin_query->get_result();
$admin = $admin_result->fetch_assoc() ?? ['name' => 'Unknown', 'email' => '', 'profile_pic' => NULL];
?>

<?php include 'admin_header.php'; ?>

<!-- THE REST OF YOUR HTML (unchanged) - keep exactly as before -->
<div class="main-content page">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 2.2rem; color: var(--text-main); margin-bottom: 0.5rem;">
                <i class="fas fa-hotel" style="color: #f59e0b;"></i> Manage Hotels
            </h1>
            <p style="color: var(--text-muted); font-size: 1.1rem;">
                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($destination_name) ?>
            </p>
        </div>
        <a href="add_destination_on_admin.php" class="btn btn-outline" style="border-radius: 50px;">
            <i class="fas fa-arrow-left"></i> Back to Destinations
        </a>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert <?= strpos($_SESSION['message'], 'Error') === false ? 'alert-success' : 'alert-danger' ?> fade-in">
            <i class="fas fa-<?= strpos($_SESSION['message'], 'Error') === false ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= $_SESSION['message'] ?>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- Add Hotel Form (same HTML as before) -->
    <div class="widget-card fade-in" style="margin-bottom: 2rem; background: var(--bg-surface); border: 1px solid var(--card-border); border-radius: 16px;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--card-border); padding: 1.5rem;">
            <h2 style="font-size: 1.8rem; color: var(--text-main);"><span style="color: #f59e0b;">Step 3:</span> Add New Hotel</h2>
        </div>
        <div class="card-body" style="padding: 1.5rem;">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label for="hotel_name">Hotel Name *</label>
                        <input type="text" id="hotel_name" name="hotel_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="hotel_type">Hotel Type *</label>
                        <select id="hotel_type" name="hotel_type" class="form-control" required>
                            <option value="low">Low Budget</option>
                            <option value="medium">Medium Budget</option>
                            <option value="high">High Budget / Luxury</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="price_per_night">Price Per Night (₹) *</label>
                        <input type="number" id="price_per_night" name="price_per_night" step="0.01" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="hotel_rating">Hotel Rating (0-5)</label>
                        <input type="number" id="hotel_rating" name="hotel_rating" step="0.1" min="0" max="5" class="form-control" value="0">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label for="amenities">Amenities (Select multiple)</label>
                        <select id="amenities" name="amenities[]" class="form-control" multiple style="height: auto; min-height: 120px;">
                            <option value="Free WiFi">Free WiFi</option>
                            <option value="Swimming Pool">Swimming Pool</option>
                            <option value="Restaurant">Restaurant</option>
                            <option value="Room Service">Room Service</option>
                            <option value="Gym">Gym</option>
                            <option value="Spa">Spa</option>
                            <option value="Parking">Parking</option>
                            <option value="Air Conditioning">Air Conditioning</option>
                            <option value="TV">TV</option>
                            <option value="Shared Kitchen">Shared Kitchen</option>
                            <option value="Lockers">Lockers</option>
                            <option value="Lounge Area">Lounge Area</option>
                        </select>
                        <small style="color: var(--text-muted); margin-top: 5px;">Hold Ctrl/Cmd to select multiple</small>
                    </div>
                    <div class="form-group">
                        <label for="image">Hotel Image</label>
                        <input type="file" id="image" name="image" class="form-control" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="text" id="contact_number" name="contact_number" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="check_in_time">Check-in Time</label>
                        <input type="time" id="check_in_time" name="check_in_time" class="form-control" value="12:00">
                    </div>
                    <div class="form-group">
                        <label for="check_out_time">Check-out Time</label>
                        <input type="time" id="check_out_time" name="check_out_time" class="form-control" value="11:00">
                    </div>
                    <div class="form-group" style="grid-column: span 2; display: flex; gap: 2rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-main); font-weight: 600;">
                            <input type="checkbox" name="free_cancellation" value="1" checked> Free Cancellation
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-main); font-weight: 600;">
                            <input type="checkbox" name="breakfast_included" value="1"> Breakfast Included
                        </label>
                    </div>
                </div>
                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary" style="border-radius: 50px; background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">
                        <i class="fas fa-save"></i> Add Hotel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Existing Hotels (same HTML as before) -->
    <div class="widget-card fade-in" style="background: var(--bg-surface); border: 1px solid var(--card-border); border-radius: 16px;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--card-border); padding: 1.5rem;">
            <h2 style="font-size: 1.8rem; color: var(--text-main);"><i class="fas fa-list"></i> Existing Hotels</h2>
            <span class="status-badge status-active">Total: <?= $hotels_result->num_rows ?></span>
        </div>
        <div class="card-body" style="padding: 1.5rem;">
            <?php if ($hotels_result->num_rows > 0): ?>
                <div class="hotels-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 2rem;">
                    <?php while ($hotel = $hotels_result->fetch_assoc()):
                        $amenities_array = json_decode($hotel['amenities'] ?? '[]', true);
                        $image_src = $hotel['image_url'];
                        if (!empty($image_src) && !str_starts_with($image_src, '../')) {
                            $image_src = '../' . $image_src;
                        }
                    ?>
                        <div class="hotel-card widget-card" style="padding: 0; overflow: hidden; background: var(--bg-surface); border: 1px solid var(--card-border); border-radius: 12px;">
                            <div class="hotel-image" style="height: 200px; overflow: hidden; position: relative;">
                                <?php if (!empty($image_src) && file_exists(__DIR__ . '/../' . $hotel['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($image_src) ?>" alt="<?= htmlspecialchars($hotel['hotel_name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; background: rgba(245, 158, 11, 0.1); display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-hotel" style="font-size: 3rem; color: rgba(245, 158, 11, 0.4);"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="hotel-type-badge" style="position: absolute; top: 15px; right: 15px; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(5px); color: white; padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 700;">
                                    <?= ucfirst($hotel['hotel_type']) ?> Budget
                                </div>
                            </div>
                            <div class="hotel-info" style="padding: 1.5rem;">
                                <h3 style="font-size: 1.3rem; margin-bottom: 0.5rem; color: var(--text-main);"><?= htmlspecialchars($hotel['hotel_name']) ?></h3>
                                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                    <span style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; padding: 4px 10px; border-radius: 20px; font-size: 0.9rem; font-weight: 600;">
                                        <i class="fas fa-star"></i> <?= number_format($hotel['hotel_rating'], 1) ?>
                                    </span>
                                    <span style="font-weight: 700; color: var(--text-main); font-size: 1.2rem;">
                                        ₹<?= number_format($hotel['price_per_night']) ?>
                                        <small style="font-size: 0.8rem; color: var(--text-muted);">/night</small>
                                    </span>
                                </div>
                                <?php if (!empty($hotel['address'])): ?>
                                    <p style="margin-bottom: 0.5rem; color: var(--text-muted); font-size: 0.9rem;">
                                        <i class="fas fa-map-marker-alt" style="color: #f59e0b;"></i> <?= htmlspecialchars($hotel['address']) ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($hotel['contact_number'])): ?>
                                    <p style="margin-bottom: 1rem; color: var(--text-muted); font-size: 0.9rem;">
                                        <i class="fas fa-phone" style="color: #f59e0b;"></i> <?= htmlspecialchars($hotel['contact_number']) ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($amenities_array) && is_array($amenities_array)): ?>
                                    <div style="margin-bottom: 1rem;">
                                        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                            <?php foreach (array_slice($amenities_array, 0, 4) as $amenity): ?>
                                                <span style="background: var(--bg-base); padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; color: var(--text-muted); border: 1px solid var(--card-border);">
                                                    <?= htmlspecialchars($amenity) ?>
                                                </span>
                                            <?php endforeach; ?>
                                            <?php if (count($amenities_array) > 4): ?>
                                                <span style="background: var(--bg-base); padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; color: var(--text-muted); border: 1px solid var(--card-border);">
                                                    +<?= count($amenities_array) - 4 ?> more
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div style="display: flex; gap: 0.8rem; margin-bottom: 1rem;">
                                    <span style="display: flex; align-items: center; gap: 5px; font-size: 0.85rem; color: var(--text-muted);">
                                        <i class="fas fa-clock"></i> Check-in: <?= substr($hotel['check_in_time'] ?? '12:00:00', 0, 5) ?>
                                    </span>
                                    <span style="display: flex; align-items: center; gap: 5px; font-size: 0.85rem; color: var(--text-muted);">
                                        <i class="fas fa-clock"></i> Check-out: <?= substr($hotel['check_out_time'] ?? '11:00:00', 0, 5) ?>
                                    </span>
                                </div>
                                <div style="display: flex; gap: 0.5rem; margin-top: 1.5rem; border-top: 1px solid var(--card-border); padding-top: 1.5rem;">
                                    <button onclick="editHotel(<?= htmlspecialchars(json_encode($hotel)) ?>)" class="btn btn-outline" style="flex: 1; border-radius: 50px;">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="?destination_id=<?= $destination_id ?>&delete=<?= $hotel['id'] ?>" class="btn btn-danger" style="flex: 1; border-radius: 50px;" onclick="return confirm('Are you sure you want to delete this hotel?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 4rem 2rem;">
                    <i class="fas fa-hotel" style="font-size: 4rem; margin-bottom: 1.5rem; color: var(--text-muted); opacity: 0.5;"></i>
                    <h3 style="color: var(--text-main); font-size: 1.5rem; margin-bottom: 0.5rem;">No hotels added yet</h3>
                    <p style="color: var(--text-muted);">Use the form above to add your first hotel</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Hotel Modal (unchanged) -->
<div id="editHotelModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: var(--bg-surface); max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto; border-radius: 24px; padding: 2rem; position: relative; border: 1px solid var(--card-border);">
        <button onclick="closeEditModal()" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">
            <i class="fas fa-times"></i>
        </button>
        <h2 style="font-size: 1.8rem; margin-bottom: 2rem; color: var(--text-main);">
            <i class="fas fa-edit" style="color: #f59e0b;"></i> Edit Hotel
        </h2>
        <form method="POST" enctype="multipart/form-data" id="editHotelForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="hotel_id" id="edit_hotel_id">
            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label for="edit_hotel_name">Hotel Name *</label>
                    <input type="text" id="edit_hotel_name" name="hotel_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_hotel_type">Hotel Type *</label>
                    <select id="edit_hotel_type" name="hotel_type" class="form-control" required>
                        <option value="low">Low Budget</option>
                        <option value="medium">Medium Budget</option>
                        <option value="high">High Budget / Luxury</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_price_per_night">Price Per Night (₹) *</label>
                    <input type="number" id="edit_price_per_night" name="price_per_night" step="0.01" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_hotel_rating">Hotel Rating (0-5)</label>
                    <input type="number" id="edit_hotel_rating" name="hotel_rating" step="0.1" min="0" max="5" class="form-control">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label for="edit_amenities">Amenities</label>
                    <select id="edit_amenities" name="amenities[]" class="form-control" multiple style="height: auto; min-height: 120px;">
                        <option value="Free WiFi">Free WiFi</option>
                        <option value="Swimming Pool">Swimming Pool</option>
                        <option value="Restaurant">Restaurant</option>
                        <option value="Room Service">Room Service</option>
                        <option value="Gym">Gym</option>
                        <option value="Spa">Spa</option>
                        <option value="Parking">Parking</option>
                        <option value="Air Conditioning">Air Conditioning</option>
                        <option value="TV">TV</option>
                        <option value="Shared Kitchen">Shared Kitchen</option>
                        <option value="Lockers">Lockers</option>
                        <option value="Lounge Area">Lounge Area</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_image">Hotel Image (Leave empty to keep current)</label>
                    <input type="file" id="edit_image" name="image" class="form-control" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="edit_address">Address</label>
                    <input type="text" id="edit_address" name="address" class="form-control">
                </div>
                <div class="form-group">
                    <label for="edit_contact_number">Contact Number</label>
                    <input type="text" id="edit_contact_number" name="contact_number" class="form-control">
                </div>
                <div class="form-group">
                    <label for="edit_check_in_time">Check-in Time</label>
                    <input type="time" id="edit_check_in_time" name="check_in_time" class="form-control">
                </div>
                <div class="form-group">
                    <label for="edit_check_out_time">Check-out Time</label>
                    <input type="time" id="edit_check_out_time" name="check_out_time" class="form-control">
                </div>
                <div class="form-group" style="grid-column: span 2; display: flex; gap: 2rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-main); font-weight: 600;">
                        <input type="checkbox" name="free_cancellation" id="edit_free_cancellation" value="1"> Free Cancellation
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-main); font-weight: 600;">
                        <input type="checkbox" name="breakfast_included" id="edit_breakfast_included" value="1"> Breakfast Included
                    </label>
                </div>
            </div>
            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary" style="border-radius: 50px; background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">
                    <i class="fas fa-save"></i> Update Hotel
                </button>
                <button type="button" onclick="closeEditModal()" class="btn btn-outline" style="border-radius: 50px;">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editHotel(hotel) {
    document.getElementById('edit_hotel_id').value = hotel.id;
    document.getElementById('edit_hotel_name').value = hotel.hotel_name;
    document.getElementById('edit_hotel_type').value = hotel.hotel_type;
    document.getElementById('edit_price_per_night').value = hotel.price_per_night;
    document.getElementById('edit_hotel_rating').value = hotel.hotel_rating;
    document.getElementById('edit_description').value = hotel.description || '';
    document.getElementById('edit_address').value = hotel.address || '';
    document.getElementById('edit_contact_number').value = hotel.contact_number || '';
    document.getElementById('edit_check_in_time').value = hotel.check_in_time ? hotel.check_in_time.substring(0,5) : '12:00';
    document.getElementById('edit_check_out_time').value = hotel.check_out_time ? hotel.check_out_time.substring(0,5) : '11:00';
    
    document.getElementById('edit_free_cancellation').checked = hotel.free_cancellation == 1;
    document.getElementById('edit_breakfast_included').checked = hotel.breakfast_included == 1;
    
    const amenities = JSON.parse(hotel.amenities || '[]');
    const select = document.getElementById('edit_amenities');
    for (let option of select.options) {
        option.selected = amenities.includes(option.value);
    }
    
    document.getElementById('editHotelModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editHotelModal').style.display = 'none';
}

document.addEventListener('click', function(event) {
    const modal = document.getElementById('editHotelModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeEditModal();
    }
});
</script>

<style>
/* Your existing CSS – unchanged */
.hotel-card { transition: transform 0.3s, box-shadow 0.3s; }
.hotel-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px var(--shadow-color); }
.form-row { margin-bottom: 1rem; }
.form-group { margin-bottom: 1.5rem; display: flex; flex-direction: column; }
.form-group label { margin-bottom: 0.6rem; font-weight: 700; color: var(--text-main); font-size: 0.95rem; }
.form-control { box-sizing: border-box; width: 100%; padding: 0.85rem 1.2rem; border: 1px solid var(--card-border); border-radius: 10px; font-size: 1rem; background-color: var(--bg-base); color: var(--text-main); transition: all 0.3s ease; font-family: inherit; }
.form-control:focus { background-color: var(--bg-surface); border-color: #f59e0b; outline: none; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.3); }
select.form-control { appearance: auto; }
select.form-control option { padding: 0.5rem; background-color: var(--bg-surface); color: var(--text-main); }
.status-badge { display: inline-flex; align-items: center; padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.9rem; font-weight: 700; }
.status-active { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
.alert { padding: 1.2rem 1.5rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 600; display: flex; align-items: center; gap: 12px; }
.alert-success { background-color: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
.alert-danger { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
.btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.8rem 1.8rem; border-radius: 12px; font-weight: 700; cursor: pointer; transition: all 0.3s; border: none; gap: 8px; font-size: 1rem; }
.btn-outline { background: transparent; border: 2px solid var(--card-border); color: var(--text-main); }
.btn-outline:hover { background: var(--bg-base); border-color: #f59e0b; color: #f59e0b; }
.btn-danger { background: transparent; border: 2px solid rgba(239, 68, 68, 0.3); color: #ef4444; }
.btn-danger:hover { background: #ef4444; color: white; border-color: #ef4444; }
.page { max-width: 1200px; }
</style>

<?php include 'admin_footer.php'; ?>