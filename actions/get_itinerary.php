<?php
session_start();
include '../database/dbconfig.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated']);
    exit();
}

$itinerary_id = $_GET['itinerary_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$itinerary_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing itinerary ID']);
    exit();
}

try {
    // Fetch itinerary
    $stmt = $conn->prepare("
        SELECT i.*, d.name as destination_name 
        FROM itineraries i
        JOIN destinations d ON i.destination_id = d.id
        WHERE i.id = ? AND i.user_id = ?
    ");
    $stmt->bind_param("ii", $itinerary_id, $user_id);
    $stmt->execute();
    $itinerary = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$itinerary) {
        echo json_encode(['status' => 'error', 'message' => 'Itinerary not found']);
        exit();
    }
    
    // Fetch days
    $days_stmt = $conn->prepare("
        SELECT * FROM itinerary_days 
        WHERE itinerary_id = ? 
        ORDER BY day_number ASC
    ");
    $days_stmt->bind_param("i", $itinerary_id);
    $days_stmt->execute();
    $days_result = $days_stmt->get_result();
    
    $itinerary['days'] = [];
    while ($day = $days_result->fetch_assoc()) {
        // Fetch activities for the day
        $activities_stmt = $conn->prepare("
            SELECT * FROM activity_suggestions 
            WHERE itinerary_day_id = ? 
            ORDER BY time_of_day, priority DESC
        ");
        $activities_stmt->bind_param("i", $day['id']);
        $activities_stmt->execute();
        $day['activities'] = $activities_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $activities_stmt->close();
        
        $itinerary['days'][] = $day;
    }
    $days_stmt->close();
    
    echo json_encode([
        'status' => 'success',
        'itinerary' => $itinerary
    ]);
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>