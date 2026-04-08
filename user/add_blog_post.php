<?php
// user/add_blog_post.php
session_start();
require_once __DIR__ . '/../database/dbconfig.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$category = isset($_POST['category']) ? trim($_POST['category']) : 'travel';
$tags = isset($_POST['tags']) ? $_POST['tags'] : '';

if (!$title || !$content) {
    echo json_encode(['status' => 'error', 'message' => 'Title and content are required']);
    exit();
}

// Handle tags
$tags_array = !empty($tags) ? array_map('trim', explode(',', $tags)) : [];
$tags_json = !empty($tags_array) ? json_encode($tags_array) : null;

// Handle image uploads
$uploaded_images = [];
if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
    $upload_dir = __DIR__ . '/../uploads/blogs/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['images']['error'][$key] === 0) {
            $ext = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
            $filename = 'blog_' . $user_id . '_' . time() . '_' . $key . '.' . $ext;
            $destination = $upload_dir . $filename;
            
            if (move_uploaded_file($tmp_name, $destination)) {
                $uploaded_images[] = '../uploads/blogs/' . $filename;
            }
        }
    }
}

$images_json = !empty($uploaded_images) ? json_encode($uploaded_images) : null;

// Insert blog post
$insert_stmt = $conn->prepare("INSERT INTO blog_posts (user_id, title, content, category, tags, images) VALUES (?, ?, ?, ?, ?, ?)");
$insert_stmt->bind_param("isssss", $user_id, $title, $content, $category, $tags_json, $images_json);

if ($insert_stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Blog post published successfully', 'post_id' => $insert_stmt->insert_id]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to publish blog post']);
}

$insert_stmt->close();
$conn->close();
?>