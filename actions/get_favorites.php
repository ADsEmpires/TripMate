<?php
// actions/get_favorites.php
session_start();
include '../database/dbconfig.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get favorite destinations with details - works with both tables
$stmt = $conn->prepare("
    SELECT d.id, d.name, d.type, d.location, d.budget, d.image_urls, d.description
    FROM (
        SELECT DISTINCT 
            CASE 
                WHEN activity_type = 'favorite' THEN activity_details 
                ELSE destination_id 
            END as dest_id
        FROM (
            SELECT activity_type, activity_details, NULL as destination_id FROM user_history 
            WHERE user_id = ? AND activity_type = 'favorite'
            UNION ALL
            SELECT 'favorite' as activity_type, NULL as activity_details, destination_id FROM favorites 
            WHERE user_id = ?
        ) as combined
    ) as favs
    JOIN destinations d ON d.id = favs.dest_id
    ORDER BY d.name
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$favorites = [];
while ($row = $result->fetch_assoc()) {
    // Parse image URLs if stored as JSON
    if (isset($row['image_urls']) && is_string($row['image_urls'])) {
        $decoded = json_decode($row['image_urls'], true);
        $row['image_urls'] = $decoded ?: [$row['image_urls']];
    }
    $favorites[] = $row;
}

echo json_encode(['status' => 'success', 'favorites' => $favorites]);
$stmt->close();
$conn->close();
?>