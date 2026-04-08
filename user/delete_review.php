<?php
// user/delete_review.php
session_start();
require_once __DIR__ . '/../database/dbconfig.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$review_id = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;

if (!$review_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid review ID']);
    exit();
}

// Verify ownership
$check_stmt = $conn->prepare("SELECT images FROM reviews WHERE id = ? AND user_id = ?");
$check_stmt->bind_param("ii", $review_id, $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Review not found or unauthorized']);
    $check_stmt->close();
    $conn->close();
    exit();
}

$review = $result->fetch_assoc();
$check_stmt->close();

// Delete associated images
if ($review['images']) {
    $images = json_decode($review['images'], true);
    if (is_array($images)) {
        foreach ($images as $image_path) {
            $full_path = __DIR__ . '/..' . $image_path;
            if (file_exists($full_path)) {
                unlink($full_path);
            }
        }
    }
}

// Delete review
$delete_stmt = $conn->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ?");
$delete_stmt->bind_param("ii", $review_id, $user_id);

if ($delete_stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Review deleted successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete review']);
}

$delete_stmt->close();
$conn->close();
?>