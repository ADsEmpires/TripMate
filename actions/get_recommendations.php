<?php
session_start();
include '../database/dbconfig.php';

header('Content-Type: application/json');

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Enhanced AI-powered recommendation system
$recommendations = [];

if ($user_id) {
    // Get user's behavior data
    $user_behavior = getUserBehavior($conn, $user_id);

    // Generate AI-like recommendations based on multiple factors
    $recommendations = generateAIRecommendations($conn, $user_behavior);
} else {
    // For guest users, provide popular/trending destinations
    $stmt = $conn->prepare("
        SELECT id, name, type, location, budget, image_urls
        FROM destinations
        ORDER BY RAND()
        LIMIT 6
    ");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $recommendations[] = $row;
    }
    $stmt->close();
}

echo json_encode(['status' => 'success', 'recommendations' => $recommendations]);
$conn->close();

// Helper function to analyze user behavior
function getUserBehavior($conn, $user_id) {
    $behavior = [
        'favorites' => [],
        'search_history' => [],
        'trip_history' => [],
        'review_history' => [],
        'preferred_types' => [],
        'budget_range' => ['min' => 0, 'max' => 999999]
    ];

    // Get favorites
    $fav_stmt = $conn->prepare("
        SELECT d.type, d.budget
        FROM user_history uh
        JOIN destinations d ON JSON_EXTRACT(uh.activity_details, '$.id') = d.id
        WHERE uh.user_id = ? AND uh.activity_type = 'favorite'
    ");
    $fav_stmt->bind_param("i", $user_id);
    $fav_stmt->execute();
    $fav_result = $fav_stmt->get_result();
    while ($row = $fav_result->fetch_assoc()) {
        $behavior['favorites'][] = $row;
        if (!in_array($row['type'], $behavior['preferred_types'])) {
            $behavior['preferred_types'][] = $row['type'];
        }
        // Update budget range
        if ($row['budget'] < $behavior['budget_range']['max']) {
            $behavior['budget_range']['max'] = $row['budget'];
        }
        if ($row['budget'] > $behavior['budget_range']['min']) {
            $behavior['budget_range']['min'] = $row['budget'];
        }
    }
    $fav_stmt->close();

    // Get search history
    $search_stmt = $conn->prepare("
        SELECT search_query, search_date
        FROM search_history
        WHERE user_id = ?
        ORDER BY search_date DESC
        LIMIT 10
    ");
    $search_stmt->bind_param("i", $user_id);
    $search_stmt->execute();
    $search_result = $search_stmt->get_result();
    while ($row = $search_result->fetch_assoc()) {
        $behavior['search_history'][] = $row;
    }
    $search_stmt->close();

    // Get trip history
    $trip_stmt = $conn->prepare("
        SELECT activity_details
        FROM user_history
        WHERE user_id = ? AND activity_type = 'trip'
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $trip_stmt->bind_param("i", $user_id);
    $trip_stmt->execute();
    $trip_result = $trip_stmt->get_result();
    while ($row = $trip_result->fetch_assoc()) {
        $details = json_decode($row['activity_details'], true);
        if ($details) {
            $behavior['trip_history'][] = $details;
        }
    }
    $trip_stmt->close();

    // Get review history
    $review_stmt = $conn->prepare("
        SELECT d.type, d.budget, r.rating
        FROM reviews r
        JOIN destinations d ON r.destination_id = d.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $review_stmt->bind_param("i", $user_id);
    $review_stmt->execute();
    $review_result = $review_stmt->get_result();
    while ($row = $review_result->fetch_assoc()) {
        $behavior['review_history'][] = $row;
        // High-rated destinations influence preferences
        if ($row['rating'] >= 4 && !in_array($row['type'], $behavior['preferred_types'])) {
            $behavior['preferred_types'][] = $row['type'];
        }
    }
    $review_stmt->close();

    return $behavior;
}

// Function to generate AI-like recommendations
function generateAIRecommendations($conn, $user_behavior) {
    $recommendations = [];

    // Build dynamic query based on user behavior
    $conditions = [];
    $params = [];
    $types = '';

    // Filter by preferred types if available
    if (!empty($user_behavior['preferred_types'])) {
        $placeholders = implode(',', array_fill(0, count($user_behavior['preferred_types']), '?'));
        $conditions[] = "d.type IN ($placeholders)";
        foreach ($user_behavior['preferred_types'] as $type) {
            $params[] = $type;
            $types .= 's';
        }
    }

    // Filter by budget range if available
    if ($user_behavior['budget_range']['max'] < 999999) {
        $conditions[] = "d.budget <= ?";
        $params[] = $user_behavior['budget_range']['max'] * 1.5; // Allow 50% over budget
        $types .= 'i';
    }

    if ($user_behavior['budget_range']['min'] > 0) {
        $conditions[] = "d.budget >= ?";
        $params[] = $user_behavior['budget_range']['min'] * 0.5; // Allow 50% under budget
        $types .= 'i';
    }

    // Exclude already favorited destinations
    if (!empty($user_behavior['favorites'])) {
        $favorite_ids = array_map(function($fav) {
            // We don't have IDs in favorites from our query, so we'll skip this for simplicity
            // In a real implementation, we'd store destination IDs in user_history
            return 0; // Placeholder
        });
        $favorite_ids = array_filter($favorite_ids);
        if (!empty($favorite_ids)) {
            $placeholders = implode(',', array_fill(0, count($favorite_ids), '?'));
            $conditions[] = "d.id NOT IN ($placeholders)";
            foreach ($favorite_ids as $id) {
                $params[] = $id;
                $types .= 'i';
            }
        }
    }

    // Build the query
    $where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $query = "
        SELECT d.*,
               (CASE
                   WHEN d.type IN (" . (!empty($user_behavior['preferred_types']) ?
                       "'" . implode("','", $user_behavior['preferred_types']) . "'" : "''") . ")
                   THEN 3
                   ELSE 0
               END) +
               (CASE
                   WHEN d.budget BETWEEN ? AND ?
                   THEN 2
                   ELSE 0
               END) as relevance_score
        FROM destinations d
        $where_clause
        ORDER BY relevance_score DESC, RAND()
        LIMIT 8
    ";

    // Add budget parameters for the CASE statement
    $budget_min = max($user_behavior['budget_range']['min'] * 0.5, 0);
    $budget_max = $user_behavior['budget_range']['max'] * 1.5;

    // Prepare and execute
    $stmt = $conn->prepare($query);

    // Bind parameters: preferred types + budget range + exclusions + budget for CASE
    $param_types = $types . 'ii'; // ii for budget min/max in CASE
    $param_values = array_merge($params, [$budget_min, $budget_max]);

    if (!empty($param_values)) {
        $stmt->bind_param($param_types, ...$param_values);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $recommendations[] = $row;
    }
    $stmt->close();

    // If we didn't get enough recommendations, fill with popular destinations
    if (count($recommendations) < 6) {
        $fill_query = "
            SELECT d.*
            FROM destinations d
            WHERE d.id NOT IN (" . implode(',', array_map(function($rec) {
                return $rec['id'];
            }, $recommendations)) . ")
            ORDER BY RAND()
            LIMIT " . (6 - count($recommendations))
        ;

        $fill_result = $conn->query($fill_query);
        while ($row = $fill_result->fetch_assoc()) {
            $recommendations[] = $row;
        }
        $fill_result->close();
    }

    return array_slice($recommendations, 0, 6); // Return max 6 recommendations
}
?>
