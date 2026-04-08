<?php
require_once '../database/dbconfig.php';
header('Content-Type: application/json');

$destination_id = isset($_GET['destination_id']) ? intval($_GET['destination_id']) : 0;
$budget_type = isset($_GET['budget_type']) ? $_GET['budget_type'] : 'medium';
$departure_city = isset($_GET['departure_city']) ? $_GET['departure_city'] : '';

if (!$destination_id) {
    echo json_encode(['error' => 'Destination ID required']);
    exit;
}

// Fetch hotels
$hotel_stmt = $conn->prepare("SELECT * FROM hotels WHERE destination_id = ? AND hotel_type = ? ORDER BY price_per_night ASC");
$hotel_stmt->bind_param("is", $destination_id, $budget_type);
$hotel_stmt->execute();
$hotels = $hotel_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch flights
$flight_stmt = $conn->prepare("SELECT * FROM flights WHERE destination_id = ? AND flight_type = ?" . 
    ($departure_city ? " AND departure_city = ?" : "") . " ORDER BY price_per_person ASC");
if ($departure_city) {
    $flight_stmt->bind_param("iss", $destination_id, $budget_type, $departure_city);
} else {
    $flight_stmt->bind_param("is", $destination_id, $budget_type);
}
$flight_stmt->execute();
$flights = $flight_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'hotels' => $hotels,
    'flights' => $flights,
    'destination_id' => $destination_id,
    'budget_type' => $budget_type
]);
?>