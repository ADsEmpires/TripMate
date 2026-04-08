<?php
// user/get_user_reviews.php
session_start();
require_once __DIR__ . '/../database/dbconfig.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

$query = "SELECT 
    r.*,
    d.name as destination_name,
    d.image_urls as destination_images,
    u.name as user_name,
    u.profile_pic
FROM reviews r
JOIN destinations d ON r.destination_id = d.id
JOIN users u ON r.user_id = u.id
WHERE r.user_id = ?
ORDER BY r.created_at DESC
LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $user_id, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$reviews = [];
while ($row = $result->fetch_assoc()) {
    // Parse images
    if ($row['images']) {
        $row['images'] = json_decode($row['images'], true);
    }
    // Parse destination images
    if ($row['destination_images'] && is_string($row['destination_images'])) {
        $row['destination_images'] = json_decode($row['destination_images'], true);
        $row['destination_image'] = is_array($row['destination_images']) && !empty($row['destination_images']) ? $row['destination_images'][0] : null;
    }
    
    // Calculate time ago
    $created = new DateTime($row['created_at']);
    $now = new DateTime();
    $diff = $now->diff($created);
    
    if ($diff->days > 365) {
        $time_ago = floor($diff->days / 365) . ' year' . (floor($diff->days / 365) > 1 ? 's' : '') . ' ago';
    } elseif ($diff->days > 30) {
        $time_ago = floor($diff->days / 30) . ' month' . (floor($diff->days / 30) > 1 ? 's' : '') . ' ago';
    } elseif ($diff->days > 0) {
        $time_ago = $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
    } elseif ($diff->h > 0) {
        $time_ago = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    } else {
        $time_ago = $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    }
    
    $row['time_ago'] = $time_ago;
    $reviews[] = $row;
}

$stmt->close();

// Get total count
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM reviews WHERE user_id = ?");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total = $count_result->fetch_assoc()['total'];
$count_stmt->close();

$conn->close();

echo json_encode(['status' => 'success', 'reviews' => $reviews, 'total' => $total]);
?>