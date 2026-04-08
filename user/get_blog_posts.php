<?php
// user/get_blog_posts.php
session_start();
require_once __DIR__ . '/../database/dbconfig.php';

header('Content-Type: application/json');

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$user_only = isset($_GET['user_only']) && $_GET['user_only'] == '1';

$query = "SELECT 
    bp.*,
    u.name as author_name,
    u.profile_pic,
    (SELECT COUNT(*) FROM blog_comments WHERE blog_post_id = bp.id) as comments_count
FROM blog_posts bp
JOIN users u ON bp.user_id = u.id
WHERE bp.status = 'published'";

$params = [];
$types = "";

if ($user_only && isset($_SESSION['user_id'])) {
    $query .= " AND bp.user_id = ?";
    $params[] = $_SESSION['user_id'];
    $types .= "i";
}

$query .= " ORDER BY bp.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$posts = [];
while ($row = $result->fetch_assoc()) {
    // Parse images
    if ($row['images']) {
        $row['images'] = json_decode($row['images'], true);
        $row['featured_image'] = is_array($row['images']) && !empty($row['images']) ? $row['images'][0] : null;
    }
    
    // Parse tags
    if ($row['tags']) {
        $row['tags'] = json_decode($row['tags'], true);
    }
    
    // Format date
    $row['date_formatted'] = date('M d, Y', strtotime($row['created_at']));
    $row['time_ago'] = timeAgo(strtotime($row['created_at']));
    
    $posts[] = $row;
}

$stmt->close();

// Get total count
$count_query = "SELECT COUNT(*) as total FROM blog_posts WHERE status = 'published'";
if ($user_only && isset($_SESSION['user_id'])) {
    $count_query .= " AND user_id = " . $_SESSION['user_id'];
}
$count_result = $conn->query($count_query);
$total = $count_result->fetch_assoc()['total'];

$conn->close();

function timeAgo($timestamp) {
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return $diff . ' seconds ago';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 2592000) {
        return floor($diff / 86400) . ' days ago';
    } elseif ($diff < 31536000) {
        return floor($diff / 2592000) . ' months ago';
    } else {
        return floor($diff / 31536000) . ' years ago';
    }
}

echo json_encode(['status' => 'success', 'posts' => $posts, 'total' => $total]);
?>