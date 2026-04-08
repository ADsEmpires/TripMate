<?php
// user/add_review.php
session_start();
require_once __DIR__ . '/../database/dbconfig.php';

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
if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
    $upload_dir = __DIR__ . '/../uploads/reviews/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['images']['error'][$key] === 0) {
            $filename = 'review_' . $user_id . '_' . time() . '_' . $key . '.jpg';
            $destination = $upload_dir . $filename;
            
            if (move_uploaded_file($tmp_name, $destination)) {
                $uploaded_images[] = '../uploads/reviews/' . $filename;
            }
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
?>