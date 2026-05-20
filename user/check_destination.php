<?php
// user/check_destination.php
require_once __DIR__ . '/session_init.php'; // Initialize session management
require_once __DIR__ . '/../database/dbconfig.php';

header('Content-Type: application/json');

// Get the destination name from the request
$destination_name = isset($_GET['name']) ? trim($_GET['name']) : '';

if (empty($destination_name)) {
    echo json_encode(['status' => 'error', 'message' => 'No destination name provided']);
    exit();
}

// Search for destination by name (case-insensitive)
$stmt = $conn->prepare("SELECT id, name FROM destinations WHERE name LIKE ? OR name = ? LIMIT 1");
$search_term = "%{$destination_name}%";
$stmt->bind_param("ss", $search_term, $destination_name);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $destination = $result->fetch_assoc();
    echo json_encode([
        'status' => 'success',
        'destination_id' => $destination['id'],
        'destination_name' => $destination['name']
    ]);
} else {
    echo json_encode(['status' => 'not_found', 'message' => 'Destination not found']);
}

$stmt->close();
$conn->close();
