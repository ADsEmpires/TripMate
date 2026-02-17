<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

include '../database/dbconfig.php';

// Get destination ID from POST
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    $_SESSION['message'] = "Invalid destination ID!";
    header("Location: admin.php"); // Already correct, stays in admin folder
    exit();
}

// First get the destination to delete images if needed
$stmt = $conn->prepare("SELECT image_urls FROM destinations WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$destination = $result->fetch_assoc();

// Delete associated images from server (optional)
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

// Delete the destination from database
$stmt = $conn->prepare("DELETE FROM destinations WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['message'] = "Destination deleted successfully!";
} else {
    $_SESSION['message'] = "Error deleting destination: " . $conn->error;
}

$stmt->close();
$conn->close();

header("Location: admin.php"); // Already correct, stays in admin folder
exit();
?>