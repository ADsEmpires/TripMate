<?php
session_start();
include '../database/dbconfig.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$user_id = $_SESSION['user_id'];
$destination_id = $data['destination_id'] ?? null;
$alert_type = $data['alert_type'] ?? 'both';
$travel_dates_from = $data['travel_dates_from'] ?? null;
$travel_dates_to = $data['travel_dates_to'] ?? null;
$max_price = $data['max_price'] ?? null;
$alert_frequency = $data['alert_frequency'] ?? 'daily';

if (!$destination_id) {
    echo json_encode(['status' => 'error', 'message' => 'Destination ID required']);
    exit();
}

try {
    // Create price alert
    $stmt = $conn->prepare("
        INSERT INTO price_alerts 
        (user_id, alert_type, destination_id, travel_dates_from, travel_dates_to, max_price, alert_frequency)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("isisdds", $user_id, $alert_type, $destination_id, $travel_dates_from, $travel_dates_to, $max_price, $alert_frequency);
    $stmt->execute();
    
    $alert_id = $conn->insert_id;
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Price alert created successfully',
        'alert_id' => $alert_id
    ]);
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>