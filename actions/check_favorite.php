<?php
// actions/check_favorite.php
session_start();
include '../database/dbconfig.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['is_favorite' => false]);
    exit();
}

if (!isset($_GET['destination_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Destination ID missing']);
    exit();
}

$destinationId = intval($_GET['destination_id']);
$user_id = intval($_SESSION['user_id']);
$is_favorite = false;

// Check in user_history first (search.php approach)
$stmt = $conn->prepare("SELECT id FROM user_history WHERE user_id = ? AND activity_type = 'favorite' AND activity_details = ?");
$stmt->bind_param("is", $user_id, $destinationId);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $is_favorite = true;
} else {
    // Fallback to favorites table
    $fav_stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND destination_id = ?");
    $fav_stmt->bind_param("ii", $user_id, $destinationId);
    $fav_stmt->execute();
    $fav_stmt->store_result();
    
    if ($fav_stmt->num_rows > 0) {
        $is_favorite = true;
    }
    $fav_stmt->close();
}

$stmt->close();
$conn->close();

echo json_encode(['is_favorite' => $is_favorite]);
?>