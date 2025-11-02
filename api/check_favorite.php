<?php
// MultipleFiles/check_favorite.php
session_start();
include '../database/dbconfig.php'; // Include your database configuration

header('Content-Type: application/json');

// Simulate database connection and user ID for demonstration
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Use a dummy user ID if not logged in for testing

if (!isset($_GET['destination_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Destination ID missing.']);
    exit();
}

$destinationId = intval($_GET['destination_id']);
$is_favorite = false;

// Simulate checking favorite status in a database
// In a real application:
// $stmt = $conn->prepare("SELECT COUNT(*) FROM user_favorites WHERE user_id = ? AND destination_id = ?");
// $stmt->bind_param("ii", $user_id, $destinationId);
// $stmt->execute();
// $stmt->bind_result($count);
// $stmt->fetch();
// $stmt->close();
// if ($count > 0) {
//     $is_favorite = true;
// }

// For demonstration, let's randomly set some as favorite or use a simple logic
// e.g., if destination ID is even, it's a favorite
if ($destinationId % 2 == 0) {
    $is_favorite = true;
}

echo json_encode(['is_favorite' => $is_favorite]);
?>
