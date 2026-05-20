<?php
// user/trip_route.php
// ============================================
// FIXED: Secure version with proper API key handling
// ============================================

require_once __DIR__ . '/../database/dbconfig.php';
require_once __DIR__ . '/../database/api_config.php';

// Validate and sanitize input
$destination_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($destination_id <= 0) {
    die('Invalid destination ID');
}

// Use prepared statement to prevent SQL injection
$query = $conn->prepare("SELECT name, latitude, longitude FROM destinations WHERE id = ? AND latitude IS NOT NULL AND longitude IS NOT NULL");
$query->bind_param("i", $destination_id);
$query->execute();
$result = $query->get_result();

$spots = [];

while ($row = $result->fetch_assoc()) {
    // Ensure coordinates are valid numbers
    $row['latitude'] = floatval($row['latitude']);
    $row['longitude'] = floatval($row['longitude']);
    $spots[] = $row;
}

if (empty($spots)) {
    die('No coordinates available for this destination');
}

$query->close();
$conn->close();

// Get Google Maps API key from secure config
$google_maps_key = getApiKey('GOOGLE_MAPS_API_KEY', '');
$has_valid_key = !empty($google_maps_key) && $google_maps_key !== 'YOUR_GOOGLE_MAPS_API_KEY';

?>
<!DOCTYPE html>
<html>

<head>
    <title>Trip Route Map - <?php echo htmlspecialchars($spots[0]['name']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f4f6fb;
            margin: 0;
        }

        .header {
            background: linear-gradient(135deg, #E55437, #C43A1F);
            color: white;
            padding: 15px 25px;
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .header a {
            color: white;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .header a:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        #map {
            height: calc(100vh - 70px);
            width: 100%;
        }

        .error-message {
            text-align: center;
            padding: 50px;
            color: #666;
        }

        @media (max-width: 768px) {
            .header {
                padding: 12px 15px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>

    <div class="header">
        <span>
            <i class="fas fa-route" style="margin-right: 8px;"></i>
            Trip Route: <?php echo htmlspecialchars($spots[0]['name']); ?>
        </span>
        <a href="destination_details.php?id=<?php echo $destination_id; ?>">
            <i class="fas fa-arrow-left"></i> Back to Destination
        </a>
    </div>

    <div id="map"></div>

    <?php if (!$has_valid_key): ?>
        <div class="error-message">
            <i class="fas fa-map-marker-alt" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
            <h3>Google Maps API key not configured</h3>
            <p>Please configure your Google Maps API key in database/api_config.php to view the route map.</p>
        </div>
    <?php endif; ?>

    <script>
        let spots = <?php echo json_encode($spots); ?>;
        let hasValidKey = <?php echo $has_valid_key ? 'true' : 'false'; ?>;

        function initMap() {
            if (!hasValidKey || spots.length === 0) {
                return;
            }

            // Validate coordinates
            if (!spots[0].latitude || !spots[0].longitude) {
                document.getElementById('map').innerHTML = '<div class="error-message">Coordinates not available for this destination.</div>';
                return;
            }

            let map = new google.maps.Map(document.getElementById("map"), {
                zoom: 12,
                center: {
                    lat: spots[0].latitude,
                    lng: spots[0].longitude
                },
                styles: [{
                    featureType: "poi",
                    elementType: "labels",
                    stylers: [{
                        visibility: "off"
                    }]
                }]
            });

            // If only one spot, just add a marker
            if (spots.length === 1) {
                new google.maps.Marker({
                    position: {
                        lat: spots[0].latitude,
                        lng: spots[0].longitude
                    },
                    map: map,
                    title: spots[0].name,
                    animation: google.maps.Animation.DROP
                });
                return;
            }

            // Multiple spots - create route
            let directionsService = new google.maps.DirectionsService();
            let directionsRenderer = new google.maps.DirectionsRenderer({
                polylineOptions: {
                    strokeColor: "#E55437",
                    strokeWeight: 5,
                    strokeOpacity: 0.8
                },
                suppressMarkers: false
            });

            directionsRenderer.setMap(map);

            let waypoints = [];

            for (let i = 1; i < spots.length - 1; i++) {
                if (spots[i].latitude && spots[i].longitude) {
                    waypoints.push({
                        location: {
                            lat: spots[i].latitude,
                            lng: spots[i].longitude
                        },
                        stopover: true
                    });
                }
            }

            let request = {
                origin: {
                    lat: spots[0].latitude,
                    lng: spots[0].longitude
                },
                destination: {
                    lat: spots[spots.length - 1].latitude,
                    lng: spots[spots.length - 1].longitude
                },
                waypoints: waypoints,
                travelMode: google.maps.TravelMode.DRIVING,
                optimizeWaypoints: true
            };

            directionsService.route(request, function(result, status) {
                if (status === "OK") {
                    directionsRenderer.setDirections(result);
                } else {
                    console.warn("Directions request failed:", status);
                    // Fallback: just add markers
                    spots.forEach(function(spot) {
                        if (spot.latitude && spot.longitude) {
                            new google.maps.Marker({
                                position: {
                                    lat: spot.latitude,
                                    lng: spot.longitude
                                },
                                map: map,
                                title: spot.name
                            });
                        }
                    });
                }
            });
        }

        // Load Google Maps API only if key is available
        if (hasValidKey) {
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(<?php echo json_encode($google_maps_key); ?>)}&callback=initMap`;
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        }
    </script>

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</body>

</html>