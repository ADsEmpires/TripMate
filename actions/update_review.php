<?php
// actions/update_review.php — Update an existing review
session_start();
require_once __DIR__ . '/../database/dbconfig.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['status'=>'error','message'=>'Not logged in']); exit(); }

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$review_id = intval($data['review_id'] ?? 0);
$rating = intval($data['rating'] ?? 0);
$title = $data['title'] ?? '';
$content = $data['content'] ?? '';

if (!$review_id || !$rating || empty($content)) {
    echo json_encode(['status'=>'error','message'=>'Missing required fields']);
    exit();
}

$stmt = $conn->prepare("UPDATE reviews SET rating = ?, title = ?, comment = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
$stmt->bind_param("issii", $rating, $title, $content, $review_id, $user_id);
$stmt->execute();

if ($stmt->affected_rows >= 0) {
    echo json_encode(['status'=>'success','message'=>'Review updated']);
} else {
    echo json_encode(['status'=>'error','message'=>'Review not found']);
}
$stmt->close();
$conn->close();
?>
