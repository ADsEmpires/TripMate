<?php
session_start();
include '../database/dbconfig.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$user_id = $_SESSION['user_id'];
$destination_id = $data['destination_id'] ?? null;
$rating = $data['rating'] ?? null;
$title = $data['title'] ?? null;
$content = $data['content'] ?? null;
$review_type = $data['review_type'] ?? 'general';
$images = $data['images'] ?? null;

if (!$destination_id || !$rating || !$title || !$content) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit();
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['status' => 'error', 'message' => 'Rating must be between 1-5']);
    exit();
}

try {
    $conn->begin_transaction();
    
    // Generate verification hash
    $verification_hash = generateVerificationHash($user_id, $destination_id, $content);
    
    // Insert review — fixed bind_param types: i=int, i=int, i=int, s=string, s=string, s=string, s=string, s=string
    $stmt = $conn->prepare("
        INSERT INTO reviews 
        (user_id, destination_id, rating, title, content, review_type, images_json, verification_hash)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $images_json = json_encode($images);
    // FIX: $title is a string, bound as 's' not 'i'
    $stmt->bind_param("iiisssss", $user_id, $destination_id, $rating, $title, $content, $review_type, $images_json, $verification_hash);
    $stmt->execute();
    
    $review_id = $conn->insert_id;
    $stmt->close();
    
    // Log blockchain verification intent (async placeholder)
    logBlockchainVerification($review_id, $verification_hash);
    
    $conn->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Review submitted successfully!',
        'review_id' => $review_id,
        'verification_hash' => $verification_hash
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Failed to submit review: ' . $e->getMessage()]);
}

$conn->close();

function generateVerificationHash($user_id, $destination_id, $content) {
    // Create a cryptographic hash of the review data
    $data = $user_id . $destination_id . $content . time();
    return hash('sha256', $data);
}

function logBlockchainVerification($review_id, $hash) {
    // Placeholder for blockchain verification
    // In production, this would integrate with a blockchain API
    $blockchain_data = [
        'review_id' => $review_id,
        'verification_hash' => $hash,
        'timestamp' => date('Y-m-d H:i:s'),
        'network' => 'polygon'
    ];
    
    // Save to a log file instead of a non-existent cache directory
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    
    @file_put_contents(
        $log_dir . '/blockchain_queue.log',
        date('Y-m-d H:i:s') . ' | Review #' . $review_id . ' | Hash: ' . $hash . "\n",
        FILE_APPEND
    );
}
?>
