<?php
// Server-side proxy for OpenWeatherMap OneCall (to keep key secret).
// GET: lat, lng
header('Content-Type: application/json');

// Load config: create config file or environment variable with key.
$OWM_KEY = trim(file_get_contents(__DIR__ . '/../.owm_key')) ?: getenv('OPENWEATHERMAP_API_KEY');

if (!$OWM_KEY) {
    echo json_encode(['status' => 'error', 'message' => 'No weather API key configured.']);
    exit;
}

$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$lng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;

if ($lat === null || $lng === null) {
    echo json_encode(['status' => 'error', 'message' => 'Missing lat/lng']);
    exit;
}

$endpoint = "https://api.openweathermap.org/data/2.5/onecall?lat={$lat}&lon={$lng}&units=metric&exclude=minutely,hourly,alerts&appid={$OWM_KEY}";

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$result = curl_exec($ch);
$err = curl_error($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err || $httpcode !== 200) {
    http_response_code(502);
    echo json_encode(['status' => 'error', 'message' => 'Weather provider error', 'detail' => $err]);
    exit;
}

echo $result;