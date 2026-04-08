<?php
session_start();
include '../database/dbconfig.php';

header('Content-Type: application/json');

$destination_id = $_GET['destination_id'] ?? null;
$check_in_date = $_GET['check_in_date'] ?? null;
$check_out_date = $_GET['check_out_date'] ?? null;

// If no destination_id, return statistics
if (!$destination_id) {
    try {
        $user_id = $_SESSION['user_id'] ?? null;

        // Get active alerts count
        $alerts_query = "SELECT COUNT(*) as count FROM price_alerts WHERE status = 'active'";
        $alerts_result = $conn->query($alerts_query);
        $active_alerts = $alerts_result->fetch_assoc()['count'] ?? 0;

        // Get prices down count
        $prices_down_query = "SELECT COUNT(*) as count FROM price_alerts WHERE price_dropped = 1";
        $prices_down_result = $conn->query($prices_down_query);
        $prices_down = $prices_down_result->fetch_assoc()['count'] ?? 0;

        // Get potential savings
        $savings_query = "SELECT SUM(CAST(max_price AS DECIMAL) - CAST(current_price AS DECIMAL)) as total FROM price_alerts WHERE current_price IS NOT NULL";
        $savings_result = $conn->query($savings_query);
        $potential_savings = $savings_result->fetch_assoc()['total'] ?? 0;

        // Get user alerts
        $user_alerts_query = "SELECT * FROM price_alerts WHERE status = 'active' ORDER BY created_at DESC";
        $user_alerts_result = $conn->query($user_alerts_query);
        $alerts = $user_alerts_result->fetch_all(MYSQLI_ASSOC) ?? [];

        echo json_encode([
            'status' => 'success',
            'data' => [
                'active_alerts' => intval($active_alerts),
                'prices_down' => intval($prices_down),
                'potential_savings' => floatval($potential_savings),
                'best_time' => 'Check Soon',
                'alerts' => $alerts,
                'deals' => []
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    $conn->close();
    exit();
}

// Original functionality - fetch flights and hotels for specific destination

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
