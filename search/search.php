<?php
header('Content-Type: application/json');
require_once '../database/dbconfig.php'; // Adjust path if needed

function fetchDestinations($conn, $params) {
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
        $values[] = '%'.$params['people'].'%';
    }
    if (!empty($params['season'])) {
        $sql .= " AND (season LIKE ? OR best_season LIKE ?)";
        $values[] = '%'.$params['season'].'%';
        $values[] = '%'.$params['season'].'%';
    }

    $stmt = $conn->prepare($sql);
    if ($values) {
        // All fields are bound as strings for simplicity
        $types = str_repeat('s', count($values));
        $stmt->bind_param($types, ...$values);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $destinations = [];
    while ($row = $result->fetch_assoc()) {
        foreach (['image_urls', 'attractions', 'hotels', 'people', 'tips', 'cuisines', 'cuisine_images', 'language'] as $jcol) {
            if (isset($row[$jcol]) && $row[$jcol] !== null) {
                $decoded = json_decode($row[$jcol], true);
                $row[$jcol] = $decoded === null ? $row[$jcol] : $decoded;
            }
        }
        $destinations[] = $row;
    }
    return $destinations;
}

// Helper to read user id from GET/POST supporting both 'userid' and 'user_id'
function readUserIdFromRequest() {
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
    // accept either user_id or userid
    $userid = !empty($_POST['user_id']) ? $_POST['user_id'] : (isset($_POST['userid']) ? $_POST['userid'] : null);
    $destination_id = isset($_POST['destination_id']) ? $_POST['destination_id'] : null;
    $action = $_POST['favorite_action'];

    if (!$userid || !$destination_id) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }

    if ($action === 'add') {
        // Prevent duplicates
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
echo json_encode(['destinations' => $destinations]);
?>