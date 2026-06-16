<?php
// actions/delete_review.php
session_start();
require_once __DIR__ . '/../database/dbconfig.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['status'=>'error','message'=>'Not logged in']); exit(); }

$data = json_decode(file_get_contents('php://input'), true);
$review_id = intval($data['review_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!$review_id) { echo json_encode(['status'=>'error','message'=>'Invalid review ID']); exit(); }

$stmt = $conn->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $review_id, $user_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['status'=>'success','message'=>'Review deleted']);
} else {
    echo json_encode(['status'=>'error','message'=>'Review not found or unauthorized']);
}
$stmt->close();
$conn->close();
?>
