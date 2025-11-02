<?php
session_start();
include 'dbconfig.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get favorites count
$fav_stmt = $conn->prepare("SELECT COUNT(*) FROM user_history WHERE user_id = ? AND activity_type = 'favorite'");
$fav_stmt->bind_param("i", $user_id);
$fav_stmt->execute();
$fav_stmt->bind_result($favorites);
$fav_stmt->fetch();
$fav_stmt->close();

// Get searches count
$search_stmt = $conn->prepare("SELECT COUNT(*) FROM user_history WHERE user_id = ? AND activity_type = 'search'");
$search_stmt->bind_param("i", $user_id);
$search_stmt->execute();
$search_stmt->bind_result($searches);
$search_stmt->fetch();
$search_stmt->close();

// Get total destinations count (for demo purposes)
$dest_stmt = $conn->prepare("SELECT COUNT(*) FROM destinations");
$dest_stmt->execute();
$dest_stmt->bind_result($destinations);
$dest_stmt->fetch();
$dest_stmt->close();

echo json_encode([
    'status' => 'success',
    'favorites' => $favorites,
    'searches' => $searches,
    'destinations' => $destinations
]);

$conn->close();
?>