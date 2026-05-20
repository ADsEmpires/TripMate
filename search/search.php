<?php
// Session initialization with proper configuration
require_once __DIR__ . '/../user/session_init.php';

header('Content-Type: application/json');
require_once '../database/dbconfig.php';

// Start measuring response time
$startTime = microtime(true);

// Function to fetch weather data from OpenWeatherMap API
function fetchWeather($location)
{
    try {
        $apiKey = 'b4fe517a83b0e5679af65062c7fd92cd';
        $url = "https://api.openweathermap.org/data/2.5/weather?q=" . urlencode($location) . "&appid=" . $apiKey . "&units=metric";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if ($data && isset($data['main']) && isset($data['weather']) && isset($data['wind'])) {
                return [
                    'temp' => round($data['main']['temp']),
                    'condition' => $data['weather'][0]['main'],
                    'humidity' => $data['main']['humidity'],
                    'wind_speed' => round($data['wind']['speed'], 1),
                    'icon' => $data['weather'][0]['icon']
                ];
            }
        }
    } catch (Exception $e) {
        // Silently fail on weather fetch
    }
    return null;
}

function decodeJsonArray($value)
{
    if (is_array($value)) {
        return array_values($value);
    }
    if ($value === null || $value === '' || $value === 'null') {
        return [];
    }
    if (is_string($value)) {
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return array_values($decoded);
        }
        $value = trim($value);
        if ($value === '') {
            return [];
        }
        if (strpos($value, ',') !== false) {
            return array_values(array_filter(array_map('trim', explode(',', $value)), function ($item) {
                return $item !== '';
            }));
        }
        return [$value];
    }
    return [];
}

function mergeDestinationImages($row)
{
    $images = decodeJsonArray($row['images'] ?? null);
    $imageUrls = decodeJsonArray($row['image_urls'] ?? null);
    $row['images'] = $images;
    $row['image_urls'] = $imageUrls;
    $row['all_images'] = array_values(array_unique(array_filter(array_merge($images, $imageUrls), function ($item) {
        return trim((string)$item) !== '';
    })));
    if (empty($row['all_images']) && !empty($row['profile_pic'])) {
        $row['all_images'] = [trim($row['profile_pic'])];
    }
    return $row;
}

function fetchHotelNamesByDestinationIds($conn, $destinationIds)
{
    if (empty($destinationIds)) {
        return [];
    }

    $destinationIds = array_map('intval', array_unique($destinationIds));
    $idList = implode(',', $destinationIds);
    $query = "SELECT destination_id, hotel_name FROM hotels WHERE destination_id IN ($idList) ORDER BY destination_id, price_per_night ASC";
    $result = $conn->query($query);
    if (!$result) {
        return [];
    }

    $hotels = [];
    while ($row = $result->fetch_assoc()) {
        $hotels[$row['destination_id']][] = $row['hotel_name'];
    }
    return $hotels;
}

function fetchDestinations($conn, $params)
{
    $sql = "SELECT * FROM destinations WHERE 1";
    $values = [];

    if (!empty($params['search'])) {
        $sql .= " AND (name LIKE ? OR location LIKE ?)";
        $like = "%" . $params['search'] . "%";
        $values[] = $like;
        $values[] = $like;
    }
    if (!empty($params['type'])) {
        $sql .= " AND type = ?";
        $values[] = $params['type'];
    }
    if (!empty($params['budget'])) {
        $sql .= " AND budget <= ?";
        $values[] = $params['budget'];
    }
    if (!empty($params['people'])) {
        $sql .= " AND people LIKE ?";
        $values[] = '%' . $params['people'] . '%';
    }
    if (!empty($params['season'])) {
        $sql .= " AND (season LIKE ? OR best_season LIKE ?)";
        $values[] = '%' . $params['season'] . '%';
        $values[] = '%' . $params['season'] . '%';
    }

    $stmt = $conn->prepare($sql);

    // SAFETY CHECK: Prevents the Javascript from crashing if DB has an error
    if (!$stmt) {
        return [];
    }

    if ($values) {
        $types = str_repeat('s', count($values));
        $stmt->bind_param($types, ...$values);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $destinations = [];
    $destinationIds = [];
    while ($row = $result->fetch_assoc()) {
        foreach (['image_urls', 'images', 'attractions', 'hotels', 'people', 'tips', 'cuisines', 'cuisine_images', 'language'] as $jcol) {
            if (isset($row[$jcol]) && $row[$jcol] !== null) {
                $decoded = json_decode($row[$jcol], true);
                $row[$jcol] = $decoded === null ? $row[$jcol] : $decoded;
            }
        }
        $row = mergeDestinationImages($row);
        $destinations[] = $row;
        $destinationIds[] = intval($row['id']);
    }

    $hotelsByDestination = fetchHotelNamesByDestinationIds($conn, $destinationIds);
    foreach ($destinations as &$destination) {
        if (!empty($hotelsByDestination[$destination['id']])) {
            $destination['hotels'] = $hotelsByDestination[$destination['id']];
        }
    }
    unset($destination);

    return $destinations;
}

// Helper to read user id from GET/POST
function readUserIdFromRequest()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!empty($_POST['user_id'])) return $_POST['user_id'];
        if (!empty($_POST['userid'])) return $_POST['userid'];
    } else {
        if (!empty($_GET['user_id'])) return $_GET['user_id'];
        if (!empty($_GET['userid'])) return $_GET['userid'];
    }
    return null;
}

// Handle favorites list
if (isset($_GET['favorites']) && $_GET['favorites'] == 1) {
    $userid = !empty($_GET['user_id']) ? $_GET['user_id'] : (isset($_GET['userid']) ? $_GET['userid'] : null);
    if (!$userid) {
        echo json_encode(['favorites' => []]);
        exit;
    }
    $sql = "SELECT activity_details FROM user_history WHERE user_id=? AND activity_type='favorite'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $favorites = [];
    while ($row = $result->fetch_assoc()) {
        $favorites[] = $row['activity_details'];
    }
    echo json_encode(['favorites' => $favorites]);
    exit;
}

// Handle add/remove favorite
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['favorite_action'])) {
    $userid = !empty($_POST['user_id']) ? $_POST['user_id'] : (isset($_POST['userid']) ? $_POST['userid'] : null);
    $destination_id = isset($_POST['destination_id']) ? $_POST['destination_id'] : null;
    $action = $_POST['favorite_action'];

    if (!$userid || !$destination_id) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }

    if ($action === 'add') {
        $check = $conn->prepare("SELECT * FROM user_history WHERE user_id=? AND activity_type='favorite' AND activity_details=?");
        $check->bind_param("ss", $userid, $destination_id);
        $check->execute();
        $res = $check->get_result();
        if ($res->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO user_history (user_id, activity_type, activity_details, created_at) VALUES (?, 'favorite', ?, NOW())");
            $stmt->bind_param("ss", $userid, $destination_id);
            $stmt->execute();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => true, 'message' => 'Already favorited']);
        }
        exit;
    } elseif ($action === 'remove') {
        $stmt = $conn->prepare("DELETE FROM user_history WHERE user_id=? AND activity_type='favorite' AND activity_details=?");
        $stmt->bind_param("ss", $userid, $destination_id);
        $stmt->execute();
        echo json_encode(['success' => true]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }
}

// Default: search
$params = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$destinations = fetchDestinations($conn, $params);

// Calculate response time
$endTime = microtime(true);
$responseTime = round($endTime - $startTime, 4);

// Return destinations with response time
echo json_encode([
    'destinations' => $destinations,
    'response_time' => $responseTime,
    'server_time' => date('Y-m-d H:i:s'),
    'count' => count($destinations)
]);
