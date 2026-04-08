<?php
// user/get_recommendations_ai.php
session_start();
require_once __DIR__ . '/../database/dbconfig.php';
require_once __DIR__ . '/../backand/image_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Analyze user preferences based on search history and favorites
$analysis_query = "
    WITH user_keywords AS (
        -- Extract keywords from searches
        SELECT 
            search_query as keyword,
            COUNT(*) as frequency,
            'search' as source
        FROM user_search_history
        WHERE user_id = ?
        GROUP BY search_query
        
        UNION ALL
        
        -- Get destination types from favorites
        SELECT 
            d.type as keyword,
            COUNT(*) as frequency,
            'favorite' as source
        FROM user_history uh
        JOIN destinations d ON d.id = uh.activity_details
        WHERE uh.user_id = ? AND uh.activity_type = 'favorite'
        GROUP BY d.type
        
        UNION ALL
        
        -- Get destination locations from viewed destinations
        SELECT 
            d.location as keyword,
            COUNT(*) as frequency,
            'view' as source
        FROM user_history uh
        JOIN destinations d ON d.id = uh.activity_details
        WHERE uh.user_id = ? AND uh.activity_type = 'destination_view'
        GROUP BY d.location
    ),
    weighted_keywords AS (
        SELECT 
            keyword,
            SUM(CASE 
                WHEN source = 'search' THEN frequency * 1.0
                WHEN source = 'favorite' THEN frequency * 2.0
                WHEN source = 'view' THEN frequency * 0.8
                ELSE frequency
            END) as weight
        FROM user_keywords
        GROUP BY keyword
        ORDER BY weight DESC
        LIMIT 10
    )
    SELECT keyword FROM weighted_keywords
";

$stmt = $conn->prepare($analysis_query);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$keywords_result = $stmt->get_result();

$keywords = [];
while ($row = $keywords_result->fetch_assoc()) {
    $keywords[] = $row['keyword'];
}
$stmt->close();

// Build recommendation query based on keywords
$recommendations = [];

if (!empty($keywords)) {
    // Build dynamic WHERE clause
    $where_conditions = [];
    $params = [];
    $types = "";

    foreach ($keywords as $index => $keyword) {
        $where_conditions[] = "(name LIKE ? OR type LIKE ? OR location LIKE ?)";
        $like = "%$keyword%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= "sss";
    }

    // Exclude already favorited destinations
    $exclude_query = "SELECT activity_details FROM user_history WHERE user_id = ? AND activity_type = 'favorite'";
    $exclude_stmt = $conn->prepare($exclude_query);
    $exclude_stmt->bind_param("i", $user_id);
    $exclude_stmt->execute();
    $exclude_result = $exclude_stmt->get_result();
    $excluded_ids = [];
    while ($row = $exclude_result->fetch_assoc()) {
        $excluded_ids[] = $row['activity_details'];
    }
    $exclude_stmt->close();

    $exclude_condition = !empty($excluded_ids) ? " AND id NOT IN (" . implode(',', array_fill(0, count($excluded_ids), '?')) . ")" : "";
    foreach ($excluded_ids as $id) {
        $params[] = $id;
        $types .= "i";
    }

    $recommendation_query = "SELECT * FROM destinations WHERE (" . implode(" OR ", $where_conditions) . ")" . $exclude_condition . " LIMIT 8";

    $stmt = $conn->prepare($recommendation_query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            // Parse image URLs
            if (isset($row['image_urls']) && is_string($row['image_urls'])) {
                $images = json_decode($row['image_urls'], true);
                $row['image_urls'] = is_array($images) ? $images : [$row['image_urls']];
            } else if (isset($row['image_urls']) && !is_array($row['image_urls'])) {
                $row['image_urls'] = [$row['image_urls']];
            } else {
                $row['image_urls'] = [];
            }

            // Process first image URL for display
            if (!empty($row['image_urls'])) {
                $row['image_urls'][0] = getImageUrlForDisplay($row['image_urls'][0]);
            }

            $recommendations[] = $row;
        }
        $stmt->close();
    }
}

// If not enough recommendations, fill with popular destinations
if (count($recommendations) < 4) {
    $popular_query = "SELECT * FROM destinations WHERE id NOT IN (SELECT COALESCE(activity_details, 0) FROM user_history WHERE user_id = ? AND activity_type = 'favorite') LIMIT ?";
    $remaining = 8 - count($recommendations);
    $stmt = $conn->prepare($popular_query);
    $stmt->bind_param("ii", $user_id, $remaining);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if (isset($row['image_urls']) && is_string($row['image_urls'])) {
            $images = json_decode($row['image_urls'], true);
            $row['image_urls'] = is_array($images) ? $images : [$row['image_urls']];
        } else if (isset($row['image_urls']) && !is_array($row['image_urls'])) {
            $row['image_urls'] = [$row['image_urls']];
        } else {
            $row['image_urls'] = [];
        }

        // Process first image URL for display
        if (!empty($row['image_urls'])) {
            $row['image_urls'][0] = getImageUrlForDisplay($row['image_urls'][0]);
        }

        $recommendations[] = $row;
    }
    $stmt->close();
}

$conn->close();

echo json_encode(['status' => 'success', 'recommendations' => $recommendations]);
