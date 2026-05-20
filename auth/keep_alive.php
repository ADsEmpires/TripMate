<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 0);
    ini_set('session.gc_maxlifetime', 86400);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    // Session is valid - update activity and return success
    $_SESSION['last_activity'] = time();
    echo json_encode(['success' => true, 'user_id' => $_SESSION['user_id']]);
} else {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No active session']);
}
