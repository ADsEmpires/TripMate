<?php
session_start();
include '../database/dbconfig.php';

header('Content-Type: application/json');

$destination_id = $_GET['destination_id'] ?? null;
$check_in_date = $_GET['check_in_date'] ?? null;
$check_out_date = $_GET['check_out_date'] ?? null;

if (!$destination_id) {
    echo json_encode(['status' => 'error', 'message' => 'Destination ID required']);
    exit();
}

try {
    $response = [
        'flights' => [],
        'hotels' => [],
        'price_trends' => []
    ];
    
    // Fetch flights
    $flight_query = "SELECT * FROM flights WHERE destination_id = ?";
    if ($check_in_date) {
        $flight_query .= " AND departure_date >= ?";
    }
    $flight_query .= " ORDER BY price ASC";
    
    $stmt = $conn->prepare($flight_query);
    if ($check_in_date) {
        $stmt->bind_param("is", $destination_id, $check_in_date);
    } else {
        $stmt->bind_param("i", $destination_id);
    }
    $stmt->execute();
    $response['flights'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Fetch hotels
    $hotel_query = "SELECT * FROM hotel_prices WHERE destination_id = ?";
    if ($check_in_date && $check_out_date) {
        $hotel_query .= " AND check_in_date >= ? AND check_out_date <= ?";
    }
    $hotel_query .= " ORDER BY price_per_night ASC";
    
    $stmt = $conn->prepare($hotel_query);
    if ($check_in_date && $check_out_date) {
        $stmt->bind_param("iss", $destination_id, $check_in_date, $check_out_date);
    } else {
        $stmt->bind_param("i", $destination_id);
    }
    $stmt->execute();
    $response['hotels'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Fetch price trends
    $trend_stmt = $conn->prepare("
        SELECT * FROM price_trends 
        WHERE destination_id = ? 
        ORDER BY date DESC 
        LIMIT 30
    ");
    $trend_stmt->bind_param("i", $destination_id);
    $trend_stmt->execute();
    $response['price_trends'] = $trend_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $trend_stmt->close();
    
    echo json_encode([
        'status' => 'success',
        'data' => $response
    ]);
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>