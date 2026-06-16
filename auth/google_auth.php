<?php
session_start();
header('Content-Type: application/json');
include '../database/dbconfig.php';

// Support both JSON body (from login.html) and FormData (from login.php)
$data = json_decode(file_get_contents('php://input'), true);
$id_token = '';

if (!empty($data['id_token'])) {
    $id_token = $data['id_token'];
} elseif (!empty($data['credential'])) {
    $id_token = $data['credential'];
} elseif (!empty($_POST['id_token'])) {
    $id_token = $_POST['id_token'];
} elseif (!empty($_POST['credential'])) {
    $id_token = $_POST['credential'];
}

if (empty($id_token)) {
    echo json_encode(['status' => 'error', 'message' => 'No ID token provided']);
    exit;
}

// Verify token with Google
$url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $id_token;
$response = @file_get_contents($url);

if ($response === FALSE) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to verify Google token']);
    exit;
}

$payload = json_decode($response, true);

if (isset($payload['error']) || !isset($payload['email'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Google token']);
    exit;
}

$email = $payload['email'];
$name = $payload['name'] ?? 'Google User';
$profile_pic = $payload['picture'] ?? null;

// Check if user exists in the main database
$stmt = $conn->prepare("SELECT id, name, auth_provider FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

$user_id = null;
$user_name = null;
$user_auth_provider = 'google';

$provider_id = $payload['sub'] ?? '';

if ($result->num_rows > 0) {
    // User exists in main table, login normally
    $user = $result->fetch_assoc();
    $user_id = $user['id'];
    $user_name = $user['name'];
    $user_auth_provider = $user['auth_provider'] ?? 'manual';

    // Merge the Google profile photo into their existing account in the database
    $update_stmt = $conn->prepare("UPDATE users SET provider_id = ?, profile_pic = COALESCE(profile_pic, ?) WHERE id = ?");
    $update_stmt->bind_param("ssi", $provider_id, $profile_pic, $user_id);
    $update_stmt->execute();
    $update_stmt->close();
} else {
    // User NOT in main table, check users_google table
    $stmt_g = $conn->prepare("SELECT id, name FROM users_google WHERE email = ?");
    $stmt_g->bind_param("s", $email);
    $stmt_g->execute();
    $result_g = $stmt_g->get_result();

    if ($result_g->num_rows > 0) {
        // Exists in users_google, log them in
        $user_g = $result_g->fetch_assoc();
        $user_id = $user_g['id'];
        $user_name = $user_g['name'];
        $user_auth_provider = 'google';

        $update_g = $conn->prepare("UPDATE users_google SET provider_id = ?, profile_pic = COALESCE(profile_pic, ?) WHERE id = ?");
        $update_g->bind_param("ssi", $provider_id, $profile_pic, $user_id);
        $update_g->execute();
        $update_g->close();
    } else {
        // Register new user in users_google
        $auth_provider = 'google';
        $insert_stmt = $conn->prepare("INSERT INTO users_google (name, email, auth_provider, provider_id, profile_pic) VALUES (?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("sssss", $name, $email, $auth_provider, $provider_id, $profile_pic);

        if ($insert_stmt->execute()) {
            $user_id = $insert_stmt->insert_id;
            $user_name = $name;
            $user_auth_provider = 'google';
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error during Google registration']);
            exit;
        }
        $insert_stmt->close();
    }
    $stmt_g->close();
}
$stmt->close();

// Set PHP Session
$_SESSION['user_id'] = $user_id;
$_SESSION['user_name'] = $user_name;
$_SESSION['auth_provider'] = $user_auth_provider;

// Track User IP
if (file_exists('../admin/ip_tracking.php')) {
    require_once '../admin/ip_tracking.php';
    if (function_exists('trackUserIP')) {
        trackUserIP($user_id, $conn, 'user');
    }
}

// Return success to frontend with profile pic and email attached!
echo json_encode([
    'status' => 'success',
    'user_id' => $user_id,
    'user_name' => $user_name,
    'email' => $email,
    'profile_pic' => $profile_pic,
    'auth_provider' => $user_auth_provider
]);
$conn->close();
?>