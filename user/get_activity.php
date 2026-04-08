<?php
// user/get_activity.php
session_start();
require_once __DIR__ . '/../database/dbconfig.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

// Fetch combined activity from multiple sources
$activities = [];

// 1. Fetch from user_history
$history_query = "SELECT 
    uh.activity_type,
    uh.activity_details,
    uh.created_at,
    CASE 
        WHEN uh.activity_type = 'search' THEN 'search'
        WHEN uh.activity_type = 'favorite' THEN 'favorite'
        WHEN uh.activity_type = 'destination_view' THEN 'view'
        WHEN uh.activity_type = 'trip_plan' THEN 'trip_plan'
        ELSE uh.activity_type
    END as type_display,
    CASE 
        WHEN uh.activity_type = 'favorite' THEN (SELECT name FROM destinations WHERE id = uh.activity_details)
        WHEN uh.activity_type = 'search' THEN uh.activity_details
        ELSE NULL
    END as display_name
FROM user_history uh
WHERE uh.user_id = ?
UNION ALL
-- 2. Fetch from search history
SELECT 
    'search' as activity_type,
    search_query as activity_details,
    search_date as created_at,
    'search' as type_display,
    search_query as display_name
FROM user_search_history
WHERE user_id = ?
UNION ALL
-- 3. Fetch upcoming trips as activity
SELECT 
    'trip_plan' as activity_type,
    CONCAT('Planned trip to ', destination_name) as activity_details,
    created_at,
    'trip_plan' as type_display,
    destination_name as display_name
FROM upcoming_trips
WHERE user_id = ? AND status = 'upcoming'
ORDER BY created_at DESC
LIMIT ?";

$stmt = $conn->prepare($history_query);
$stmt->bind_param("iiii", $user_id, $user_id, $user_id, $limit);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $icon = 'fa-circle';
    $activity_text = '';
    
    switch ($row['type_display']) {
        case 'search':
            $icon = 'fa-search';
            $activity_text = 'Searched for "' . htmlspecialchars($row['display_name'] ?? $row['activity_details']) . '"';
            break;
        case 'favorite':
            $icon = 'fa-heart';
            $activity_text = 'Added ' . htmlspecialchars($row['display_name'] ?? 'a destination') . ' to favorites';
            break;
        case 'view':
            $icon = 'fa-eye';
            $activity_text = 'Viewed ' . htmlspecialchars($row['display_name'] ?? 'a destination');
            break;
        case 'trip_plan':
            $icon = 'fa-suitcase';
            $activity_text = htmlspecialchars($row['activity_details']);
            break;
        default:
            $icon = 'fa-info-circle';
            $activity_text = htmlspecialchars($row['activity_details'] ?? 'Activity recorded');
    }
    
    $activities[] = [
        'icon' => $icon,
        'text' => $activity_text,
        'time' => date('M d, Y H:i', strtotime($row['created_at'])),
        'timestamp' => $row['created_at']
    ];
}

$stmt->close();
$conn->close();

echo json_encode(['status' => 'success', 'activities' => $activities]);
?>