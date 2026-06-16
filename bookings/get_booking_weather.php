<?php
// bookings/get_booking_weather.php — Returns weather forecast for booking destination
session_start();
require_once __DIR__ . '/../database/dbconfig.php';

header('Content-Type: application/json');

$destination_id = intval($_GET['destination_id'] ?? 0);
if (!$destination_id) { echo json_encode(['error'=>'No destination']); exit(); }

// Get destination coords
$stmt = $conn->prepare("SELECT name, latitude, longitude FROM destinations WHERE id = ?");
$stmt->bind_param("i", $destination_id);
$stmt->execute();
$dest = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$dest || !$dest['latitude'] || !$dest['longitude']) {
    echo json_encode(['error'=>'Destination not found or missing coordinates']);
    exit();
}

// Try to get weather from OpenWeatherMap
require_once __DIR__ . '/../database/weather_config.php';
$api_key = defined('WEATHER_API_KEY') ? WEATHER_API_KEY : '';

if (empty($api_key)) {
    echo json_encode([
        'destination' => $dest['name'],
        'weather' => 'Weather data unavailable (no API key)',
        'temp' => 'N/A'
    ]);
    exit();
}

$url = "https://api.openweathermap.org/data/2.5/weather?lat={$dest['latitude']}&lon={$dest['longitude']}&units=metric&appid={$api_key}";
$response = @file_get_contents($url);

if ($response) {
    $weather = json_decode($response, true);
    echo json_encode([
        'destination' => $dest['name'],
        'weather' => $weather['weather'][0]['description'] ?? 'N/A',
        'temp' => round($weather['main']['temp'] ?? 0),
        'humidity' => $weather['main']['humidity'] ?? 0,
        'wind' => $weather['wind']['speed'] ?? 0,
        'icon' => $weather['weather'][0]['icon'] ?? '01d'
    ]);
} else {
    echo json_encode(['destination' => $dest['name'], 'weather' => 'Unable to fetch weather', 'temp' => 'N/A']);
}
$conn->close();
?>
