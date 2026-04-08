<?php
// user/get_upcoming_trips.php
session_start();
require_once __DIR__ . '/../database/dbconfig.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

$query = "SELECT * FROM upcoming_trips WHERE user_id = ? AND status = 'upcoming' AND start_date >= CURDATE() ORDER BY start_date ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$trips = [];
$today = new DateTime();
$upcoming_trips_count = 0;

while ($row = $result->fetch_assoc()) {
    $start = new DateTime($row['start_date']);
    $diff = $today->diff($start);
    
    $row['days_until'] = $diff->days;
    $row['is_urgent'] = $diff->days <= 2 && $diff->days >= 0;
    $row['start_date_formatted'] = $start->format('M d, Y');
    $row['end_date_formatted'] = (new DateTime($row['end_date']))->format('M d, Y');
    
    // Calculate countdown
    if ($diff->days > 0) {
        $row['countdown_text'] = $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' to go';
    } elseif ($diff->h > 0) {
        $row['countdown_text'] = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' to go';
    } else {
        $row['countdown_text'] = 'Starting soon!';
    }
    
    $upcoming_trips_count++;
    $trips[] = $row;
}

$stmt->close();

// Check for urgent trips (within 2 days)
$urgent_trips = array_filter($trips, function($trip) {
    return $trip['is_urgent'];
});

$conn->close();

echo json_encode([
    'status' => 'success', 
    'trips' => $trips, 
    'count' => $upcoming_trips_count,
    'has_urgent' => !empty($urgent_trips),
    'urgent_trips' => array_values($urgent_trips)
]);
?>