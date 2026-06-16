<?php
require_once '../database/dbconfig.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Ensure $conn is defined (use existing $mysqli/$db variables or defined constants, or create a new mysqli connection)
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        $conn = $mysqli;
    } elseif (isset($db) && $db instanceof mysqli) {
        $conn = $db;
    } elseif (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_errno) {
            throw new Exception('Database connection failed: ' . $conn->connect_error);
        }
    } elseif (isset($db_host, $db_user, $db_pass, $db_name)) {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($conn->connect_errno) {
            throw new Exception('Database connection failed: ' . $conn->connect_error);
        }
    } else {
        // Fallback — adjust credentials to match your environment
        $conn = new mysqli('localhost', 'root', '', 'your_database');
        if ($conn->connect_errno) {
            throw new Exception('Database connection failed: ' . $conn->connect_error);
        }
    }
}

try {
    // Disable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS=0");

    // Clear existing to avoid duplicate IDs if any
    $conn->query("TRUNCATE TABLE hotels");

    // Force Seed Hotels
    echo "Seeding hotels...\n";
    $destinations = [
        ['id' => 1, 'name' => 'Bali'],
        ['id' => 2, 'name' => 'Paris'],
        ['id' => 3, 'name' => 'Kyoto'],
        ['id' => 4, 'name' => 'New York City'],
        ['id' => 5, 'name' => 'Santorini'],
        ['id' => 7, 'name' => 'Agra']
    ];
    $hotel_types = ['low', 'medium', 'high'];
    $hotel_names = [
        'low' => ['Budget Inn', 'Backpacker Hostel', 'Economy Lodge'],
        'medium' => ['Comfort Suites', 'City Center Hotel', 'Holiday Inn'],
        'high' => ['Grand Palace', 'Luxury Resort & Spa', 'Ritz Carlton']
    ];

    $hotel_id = 1;
    foreach ($destinations as $dest) {
        foreach ($hotel_types as $type) {
            foreach ($hotel_names[$type] as $name) {
                $hotel_name = $name . " " . $dest['name'];
                $stars = ($type == 'low') ? rand(2,3) : (($type == 'medium') ? 4 : 5);
                $price = ($type == 'low') ? rand(1500, 3000) : (($type == 'medium') ? rand(4000, 8000) : rand(12000, 35000));
                $amenities = json_encode(($type == 'low') ? ['WiFi', 'AC'] : (($type == 'medium') ? ['WiFi', 'AC', 'Pool', 'Breakfast'] : ['WiFi', 'AC', 'Pool', 'Spa', 'Gym', 'Breakfast']));
                
                $stmt = $conn->prepare("INSERT INTO hotels (id, city_id, destination_id, name, hotel_name, stars, hotel_rating, price_per_night, amenities, hotel_type) VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iissiddss", $hotel_id, $dest['id'], $hotel_name, $hotel_name, $stars, $stars, $price, $amenities, $type);
                $stmt->execute();
                $hotel_id++;
            }
        }
    }
    echo "Hotels seeded.\n";

    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS=1");

    echo "Database seeded successfully!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
