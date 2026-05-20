<?php
require '../database/dbconfig.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;
    $page_name = $_POST['page_name'] ?? 'unknown';
    $page_url = $_POST['page_url'] ?? $_SERVER['HTTP_REFERER'] ?? '';
    $time_spent = (int)($_POST['time_spent'] ?? 0);
    $click_count = (int)($_POST['click_count'] ?? 0);
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $session_id = session_id() ?: null;
    $visit_date = date('Y-m-d');
    
    // Only save if time_spent is greater than 0
    if ($time_spent > 0) {
        $stmt = $conn->prepare("
            INSERT INTO page_time_tracking 
            (user_id, page_name, page_url, time_spent, click_count, ip_address, user_agent, session_id, visit_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt) {
            $stmt->bind_param("issiissss", 
                $user_id, 
                $page_name, 
                $page_url, 
                $time_spent, 
                $click_count, 
                $ip_address, 
                $user_agent, 
                $session_id, 
                $visit_date
            );
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Time tracked successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Error preparing statement: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Time spent is 0, not saving']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
