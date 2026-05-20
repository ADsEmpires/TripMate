<?php
require_once __DIR__ . '/session_init.php'; // Initialize session management
require_once __DIR__ . '/../database/dbconfig.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
if (!isset($_FILES['profile_pic']) || $_FILES['profile_pic']['error'] != 0) {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded']);
    exit();
}

// MIME type validation
$allowed_mime_types = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp'
];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $_FILES['profile_pic']['tmp_name']);
finfo_close($finfo);

if (!isset($allowed_mime_types[$mime_type])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid file type']);
    exit();
}

$ext = $allowed_mime_types[$mime_type];
$upload_dir = __DIR__ . '/../uploads/profiles/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
$destination = $upload_dir . $filename;

if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destination)) {
    echo json_encode(['status' => 'error', 'message' => 'Upload failed']);
    exit();
}

chmod($destination, 0644);

// Update database
$stmt = $conn->prepare("UPDATE users SET profile_pic=? WHERE id=?");
$stored_filename = '../uploads/profiles/' . $filename;
$stmt->bind_param("si", $stored_filename, $user_id);
if ($stmt->execute()) {
    $_SESSION['profile_pic'] = $stored_filename;
    $_SESSION['user_pic'] = $stored_filename;
    echo json_encode(['status' => 'success', 'url' => $stored_filename]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
}
$stmt->close();
$conn->close();
