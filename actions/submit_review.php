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
    
    // Insert review
    $stmt = $conn->prepare("
        INSERT INTO reviews 
        (user_id, destination_id, rating, title, content, review_type, images, verification_hash)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $images_json = json_encode($images);
    $stmt->bind_param("iiiissss", $user_id, $destination_id, $rating, $title, $content, $review_type, $images_json, $verification_hash);
    $stmt->execute();
    
    $review_id = $conn->insert_id;
    $stmt->close();
    
    // Submit for blockchain verification (async)
    submitToBlockchain($review_id, $verification_hash);
    
    // Award tokens for review submission
    awardReviewTokens($conn, $user_id);
    
    // Update user reputation
    updateUserReputation($conn, $user_id);
    
    $conn->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Review submitted successfully. Blockchain verification in progress...',
        'review_id' => $review_id,
        'verification_hash' => $verification_hash
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();

function generateVerificationHash($user_id, $destination_id, $content) {
    // Create a cryptographic hash of the review data
    $data = $user_id . $destination_id . $content . time();
    return hash('sha256', $data);
}

function submitToBlockchain($review_id, $hash) {
    // This would integrate with a blockchain API (e.g., Ethereum, Polygon)
    // For now, we'll create a placeholder
    
    $blockchain_data = [
        'review_id' => $review_id,
        'verification_hash' => $hash,
        'timestamp' => date('Y-m-d H:i:s'),
        'network' => 'polygon' // Using Polygon for lower fees
    ];
    
    // Save to pending queue for async processing
    file_put_contents(
        '../cache/blockchain_queue_' . $review_id . '.json',
        json_encode($blockchain_data)
    );
    
    // In production, this would be processed by a separate queue worker
}

function awardReviewTokens($conn, $user_id) {
    // Award tokens for review submission
    $tokens = 10; // Base tokens for each review
    
    $stmt = $conn->prepare("
        INSERT INTO reward_tokens 
        (user_id, token_amount, token_type, expires_at)
        VALUES (?, ?, 'review_bonus', DATE_ADD(NOW(), INTERVAL 1 YEAR))
    ");
    
    $stmt->bind_param("id", $user_id, $tokens);
    $stmt->execute();
    $stmt->close();
}

function updateUserReputation($conn, $user_id) {
    $stmt = $conn->prepare("
        UPDATE user_reputation