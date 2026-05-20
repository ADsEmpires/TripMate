<?php
// user/add_blog_post.php
require_once __DIR__ . '/session_init.php'; // Initialize session management
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

// ============================================
// FIXED: CSRF Protection
// ============================================
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    echo json_encode(['status' => 'error', 'message' => 'Security validation failed. Please refresh and try again.']);
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

// Validate title length
if (strlen($title) < 3 || strlen($title) > 255) {
    echo json_encode(['status' => 'error', 'message' => 'Title must be between 3 and 255 characters']);
    exit();
}

// Validate content length
if (strlen($content) < 10) {
    echo json_encode(['status' => 'error', 'message' => 'Content must be at least 10 characters']);
    exit();
}

// Handle tags
$tags_array = !empty($tags) ? array_map('trim', explode(',', $tags)) : [];
$tags_array = array_slice($tags_array, 0, 10); // Limit to 10 tags
$tags_json = !empty($tags_array) ? json_encode($tags_array) : null;

// ============================================
// FIXED: Secure image upload with MIME validation
// ============================================
$uploaded_images = [];
$allowed_mime_types = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp'
];
$max_file_size = 5 * 1024 * 1024; // 5MB
$max_images = 10; // Maximum 10 images per post

if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
    // Check if too many files
    if (count($_FILES['images']['name']) > $max_images) {
        echo json_encode(['status' => 'error', 'message' => "Maximum {$max_images} images allowed"]);
        exit();
    }

    $upload_dir = __DIR__ . '/../uploads/blogs/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true); // 0755 permissions, not 0777
    }

    // Create .htaccess to prevent script execution in uploads directory
    $htaccess_path = $upload_dir . '.htaccess';
    if (!file_exists($htaccess_path)) {
        file_put_contents($htaccess_path, "php_flag engine off\nOptions -Indexes");
    }

    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        // Check for upload errors
        if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
                UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
                UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
            ];
            $error_msg = $error_messages[$_FILES['images']['error'][$key]] ?? 'Unknown error';
            echo json_encode(['status' => 'error', 'message' => "Image upload failed: {$error_msg}"]);
            exit();
        }

        // Check file size
        if ($_FILES['images']['size'][$key] > $max_file_size) {
            echo json_encode(['status' => 'error', 'message' => 'Image exceeds 5MB limit']);
            exit();
        }

        // ============================================
        // CRITICAL: Validate MIME type using finfo (not just extension)
        // ============================================
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $tmp_name);
        finfo_close($finfo);

        if (!isset($allowed_mime_types[$mime_type])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid image type. Only JPG, PNG, GIF, and WebP are allowed.']);
            exit();
        }

        // Additional validation: try to load image as GD resource
        $image_resource = null;
        switch ($mime_type) {
            case 'image/jpeg':
                $image_resource = imagecreatefromjpeg($tmp_name);
                break;
            case 'image/png':
                $image_resource = imagecreatefrompng($tmp_name);
                break;
            case 'image/gif':
                $image_resource = imagecreatefromgif($tmp_name);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $image_resource = imagecreatefromwebp($tmp_name);
                }
                break;
        }

        if ($image_resource === false) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid or corrupted image file']);
            exit();
        }

        // Clean up GD resource
        imagedestroy($image_resource);

        // Generate secure filename
        $extension = $allowed_mime_types[$mime_type];
        $filename = 'blog_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = $upload_dir . $filename;

        // Move uploaded file
        if (move_uploaded_file($tmp_name, $destination)) {
            // Set proper permissions
            chmod($destination, 0644);
            $uploaded_images[] = '../uploads/blogs/' . $filename;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save uploaded image']);
            exit();
        }
    }
}

$images_json = !empty($uploaded_images) ? json_encode($uploaded_images) : null;

// Insert blog post
$insert_stmt = $conn->prepare("INSERT INTO blog_posts (user_id, title, content, category, tags, images, status) VALUES (?, ?, ?, ?, ?, ?, 'published')");
$insert_stmt->bind_param("isssss", $user_id, $title, $content, $category, $tags_json, $images_json);

if ($insert_stmt->execute()) {
    // Record in user_history
    $history_stmt = $conn->prepare("INSERT INTO user_history (user_id, activity_type, activity_details) VALUES (?, 'blog_post', ?)");
    $history_details = json_encode(['post_id' => $insert_stmt->insert_id, 'title' => $title]);
    $history_stmt->bind_param("is", $user_id, $history_details);
    $history_stmt->execute();
    $history_stmt->close();

    echo json_encode(['status' => 'success', 'message' => 'Blog post published successfully', 'post_id' => $insert_stmt->insert_id]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to publish blog post']);
}

$insert_stmt->close();
$conn->close();
