<?php
session_start();
include '../database/dbconfig.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status'=>'error','message'=>'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
if (!isset($_FILES['profile_pic']) || $_FILES['profile_pic']['error'] != 0) {
    echo json_encode(['status'=>'error','message'=>'No file uploaded']);
    exit();
}

$allowed = ['jpg','jpeg','png','gif','webp'];
$ext = strtolower(pathinfo($_FILES['profile_pic']['name'],PATHINFO_EXTENSION));
if (!in_array($ext,$allowed)) {
    echo json_encode(['status'=>'error','message'=>'Invalid file type']);
    exit();
}

$filename = 'uploads/profile_' . $user_id . '_' . time() . '.' . $ext;
if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'],$filename)) {
    echo json_encode(['status'=>'error','message'=>'Upload failed']);
    exit();
}

// Update database
$stmt = $conn->prepare("UPDATE users SET profile_pic=? WHERE id=?");
$stmt->bind_param("si",$filename,$user_id);
if ($stmt->execute()) {
    $_SESSION['profile_pic'] = $filename;
    echo json_encode(['status'=>'success','url'=>$filename]);
} else {
    echo json_encode(['status'=>'error','message'=>'Database update failed']);
}
$stmt->close();
$conn->close();
?>