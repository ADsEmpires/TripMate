<?php
// Return nearby POIs within radius (meters).
// GET params: lat, lng, radius (meters, optional default 5000)
header('Content-Type: application/json');
require_once __DIR__ . '/../database/dbconfig.php';

$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$lng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;
$radius = isset($_GET['radius']) ? intval($_GET['radius']) : 5000;

if ($lat === null || $lng === null) {
    echo json_encode(['status' => 'error', 'message' => 'Missing lat/lng']);
    exit;
}

// If table 'pois' exists, use Haversine or ST_Distance_Sphere if available.
$check = $conn->query("SHOW TABLES LIKE 'pois'");
if ($check && $check->num_rows > 0) {
    // Use Haversine formula approximate (distance in meters)
    $sql = "SELECT id, name, type, latitude, longitude,
        ( 6371000 * acos( cos( radians(?) ) * cos( radians(latitude) ) * cos( radians(longitude) - radians(?) ) + sin( radians(?) ) * sin( radians(latitude) ) ) ) AS distance
        FROM pois
        HAVING distance <= ?
        ORDER BY distance ASC
        LIMIT 100";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dddi", $lat, $lng, $lat, $radius);
    $stmt->execute();
    $res = $stmt->get_result();
    $pois = $res->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['status' => 'success', 'pois' => $pois]);
    $stmt->close();
    exit;
}

// fallback: no POIs table
echo json_encode(['status' => 'error', 'message' => 'POIs not available']);