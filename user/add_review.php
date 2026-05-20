<?php
// user/add_review.php
require_once __DIR__ . '/session_init.php'; // Initialize session management
require_once __DIR__ . '/../database/dbconfig.php';
require_once __DIR__ . '/../database/app_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

// ============================================
// CSRF Protection
// ============================================
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    echo json_encode(['status' => 'error', 'message' => 'Security validation failed. Please refresh and try again.']);
    exit();
}

$destination_id = isset($_POST['destination_id']) ? intval($_POST['destination_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
$title = isset($_POST['title']) ? trim($_POST['title']) : '';

if (!$destination_id || !$rating || !$comment) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit();
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid rating']);
    exit();
}

// Handle image uploads
$uploaded_images = [];
$allowed_mime_types = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp'
];
$max_file_size = 5 * 1024 * 1024; // 5MB
$max_images = 5; // Maximum 5 images per review

if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
    if (count($_FILES['images']['name']) > $max_images) {
        echo json_encode(['status' => 'error', 'message' => "Maximum {$max_images} images allowed"]);
        exit();
    }

    $upload_dir = __DIR__ . '/../uploads/reviews/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Create .htaccess to prevent script execution when served from Apache
    $htaccess_path = $upload_dir . '.htaccess';
    if (!file_exists($htaccess_path)) {
        file_put_contents($htaccess_path, "php_flag engine off\nOptions -Indexes");
    }

    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) {
            continue;
        }

        if ($_FILES['images']['size'][$key] > $max_file_size) {
            echo json_encode(['status' => 'error', 'message' => 'Image exceeds 5MB limit']);
            exit();
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $tmp_name);
        finfo_close($finfo);

        if (!isset($allowed_mime_types[$mime_type])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid image type']);
            exit();
        }

        $extension = $allowed_mime_types[$mime_type];
        $filename = 'review_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $destination = $upload_dir . $filename;

        if (move_uploaded_file($tmp_name, $destination)) {
            chmod($destination, 0644);
            $uploaded_images[] = '../uploads/reviews/' . $filename;
        }
    }
}

$images_json = !empty($uploaded_images) ? json_encode($uploaded_images) : null;

// Check if review already exists
$check_stmt = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND destination_id = ?");
$check_stmt->bind_param("ii", $user_id, $destination_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    // Update existing review
    $update_stmt = $conn->prepare("UPDATE reviews SET rating = ?, title = ?, comment = ?, images = ?, updated_at = NOW() WHERE user_id = ? AND destination_id = ?");
    $update_stmt->bind_param("isssii", $rating, $title, $comment, $images_json, $user_id, $destination_id);

    if ($update_stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Review updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update review']);
    }
    $update_stmt->close();
} else {
    // Insert new review
    $insert_stmt = $conn->prepare("INSERT INTO reviews (user_id, destination_id, rating, title, comment, images) VALUES (?, ?, ?, ?, ?, ?)");
    $insert_stmt->bind_param("iiisss", $user_id, $destination_id, $rating, $title, $comment, $images_json);

    if ($insert_stmt->execute()) {
        // Also record in user_history
        $history_stmt = $conn->prepare("INSERT INTO user_history (user_id, activity_type, activity_details) VALUES (?, 'review', ?)");
        $history_details = json_encode(['destination_id' => $destination_id, 'rating' => $rating]);
        $history_stmt->bind_param("is", $user_id, $history_details);
        $history_stmt->execute();
        $history_stmt->close();

        echo json_encode(['status' => 'success', 'message' => 'Review submitted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to submit review']);
    }
    $insert_stmt->close();
}

$check_stmt->close();
$conn->close();
