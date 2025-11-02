<?php
// Handle review form submission
// POST: destination_id, rating, comment, optional images[]
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../search/search.html');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    // redirect to login or return error
    $_SESSION['flash_error'] = 'You must be signed in to add a review.';
    header('Location: ../user/destination_details.php?id=' . intval($_POST['destination_id']));
    exit;
}

require_once __DIR__ . '/../database/dbconfig.php';

$user_id = intval($_SESSION['user_id']);
$destination_id = isset($_POST['destination_id']) ? intval($_POST['destination_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if (!$destination_id || $rating < 1 || $rating > 5) {
    $_SESSION['flash_error'] = 'Invalid review data.';
    header('Location: ../user/destination_details.php?id=' . $destination_id);
    exit;
}

// handle images
$uploaded_files = [];
$upload_dir = __DIR__ . '/../uploads/reviews/';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0755, true);
}
if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
    for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
        if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
        $tmp = $_FILES['images']['tmp_name'][$i];
        $orig = basename($_FILES['images']['name'][$i]);
        // sanitize file name
        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $safe = bin2hex(random_bytes(8)) . '.' . preg_replace('/[^a-zA-Z0-9]/', '', $ext);
        $dest = $upload_dir . $safe;
        if (move_uploaded_file($tmp, $dest)) {
            $uploaded_files[] = 'uploads/reviews/' . $safe;
        }
    }
}

$images_json = !empty($uploaded_files) ? json_encode($uploaded_files) : null;

try {
    // Ensure reviews table exists
    $check = $conn->query("SHOW TABLES LIKE 'reviews'");
    if ($check === false || $check->num_rows === 0) {
        // Create reviews table quickly if missing (best to use migrations)
        $create = "CREATE TABLE IF NOT EXISTS `reviews` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `destination_id` INT NOT NULL,
            `user_id` INT DEFAULT NULL,
            `rating` TINYINT NOT NULL,
            `comment` TEXT DEFAULT NULL,
            `images_json` JSON DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $conn->query($create);
    }

    $stmt = $conn->prepare("INSERT INTO reviews (destination_id, user_id, rating, comment, images_json) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiss", $destination_id, $user_id, $rating, $comment, $images_json);
    $stmt->execute();
    $stmt->close();

    $_SESSION['flash_success'] = 'Review submitted successfully.';
} catch (Exception $e) {
    $_SESSION['flash_error'] = 'Failed to submit review: ' . $e->getMessage();
}

// Redirect back to destination page
header('Location: ../user/destination_details.php?id=' . $destination_id);
exit;