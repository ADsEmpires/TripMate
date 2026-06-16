<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit();
}

include '../database/dbconfig.php';

// Get destination ID from POST
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id <= 0) {
    $_SESSION['message'] = "Invalid destination ID!";
    header("Location: add_destination_on_admin.php"); // Fixed redirect
    exit();
}

// 1. Get the destination to delete images if needed
$stmt = $conn->prepare("SELECT image_urls FROM destinations WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$destination = $result->fetch_assoc();

// Delete associated images from server
if ($destination && !empty($destination['image_urls'])) {
    $images = json_decode($destination['image_urls'], true);
    if (is_array($images)) {
        foreach ($images as $image) {
            if (file_exists($image)) {
                unlink($image);
            }
        }
    }
}

// 2. IMPORTANT: Delete associated Flights and Hotels first to prevent Foreign Key constraint errors
$del_flights = $conn->prepare("DELETE FROM flights WHERE destination_id = ?");
$del_flights->bind_param("i", $id);
$del_flights->execute();
$del_flights->close();

$del_hotels = $conn->prepare("DELETE FROM hotels WHERE destination_id = ?");
$del_hotels->bind_param("i", $id);
$del_hotels->execute();
$del_hotels->close();


// 3. Delete the destination from database
$stmt = $conn->prepare("DELETE FROM destinations WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['message'] = "Destination deleted successfully!";
} else {
    $_SESSION['message'] = "Error deleting destination: " . $conn->error;
}

$stmt->close();
$conn->close();

// Fixed redirect to point back to the destinations page
header("Location: add_destination_on_admin.php"); 
exit();
?>