<?php
// user/add_upcoming_trip.php
require_once __DIR__ . '/session_init.php'; // Initialize session management
require_once __DIR__ . '/../database/dbconfig.php';
require_once __DIR__ . '/../database/app_config.php';

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

// CSRF Validation
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    echo json_encode(['status' => 'error', 'message' => 'Security validation failed. Please refresh and try again.']);
    exit();
}

// ============================================
// FIXED: Input validation with sanitization
// ============================================
$destination_id = isset($_POST['destination_id']) ? intval($_POST['destination_id']) : 0;
$destination_name = isset($_POST['destination_name']) ? trim($_POST['destination_name']) : '';
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
$travelers = isset($_POST['travelers']) ? intval($_POST['travelers']) : 1;
$budget = isset($_POST['budget']) ? floatval($_POST['budget']) : 0;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

if (!$destination_name || !$start_date || !$end_date) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit();
}

// Validate date format and range
$start_timestamp = strtotime($start_date);
$end_timestamp = strtotime($end_date);

if ($start_timestamp === false || $end_timestamp === false) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid date format']);
    exit();
}

if ($start_timestamp < strtotime('today')) {
    echo json_encode(['status' => 'error', 'message' => 'Start date cannot be in the past']);
    exit();
}

if ($end_timestamp <= $start_timestamp) {
    echo json_encode(['status' => 'error', 'message' => 'End date must be after start date']);
    exit();
}

if ($travelers < 1 || $travelers > 50) {
    echo json_encode(['status' => 'error', 'message' => 'Number of travelers must be between 1 and 50']);
    exit();
}

// ============================================
// FIXED: Verify destination exists in destinations table
// ============================================
$canonical_destination_name = $destination_name;
$verified_destination_id = $destination_id;

if ($destination_id > 0) {
    // Verify by ID first
    $verify_stmt = $conn->prepare("SELECT id, name FROM destinations WHERE id = ?");
    $verify_stmt->bind_param("i", $destination_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();

    if ($verify_result->num_rows > 0) {
        $dest_data = $verify_result->fetch_assoc();
        $verified_destination_id = $dest_data['id'];
        $canonical_destination_name = $dest_data['name']; // Use canonical name from DB
    } else {
        // Destination ID not found - try to find by name
        $verify_stmt->close();
        $verify_stmt = $conn->prepare("SELECT id, name FROM destinations WHERE name = ?");
        $verify_stmt->bind_param("s", $destination_name);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();

        if ($verify_result->num_rows > 0) {
            $dest_data = $verify_result->fetch_assoc();
            $verified_destination_id = $dest_data['id'];
            $canonical_destination_name = $dest_data['name'];
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Destination not found in our database. Please select a valid destination.']);
            $verify_stmt->close();
            $conn->close();
            exit();
        }
    }
    $verify_stmt->close();
} else {
    // No destination ID provided - try to find by name
    $verify_stmt = $conn->prepare("SELECT id, name FROM destinations WHERE name = ?");
    $verify_stmt->bind_param("s", $destination_name);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();

    if ($verify_result->num_rows > 0) {
        $dest_data = $verify_result->fetch_assoc();
        $verified_destination_id = $dest_data['id'];
        $canonical_destination_name = $dest_data['name'];
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Destination not found in our database. Please select a valid destination.']);
        $verify_stmt->close();
        $conn->close();
        exit();
    }
    $verify_stmt->close();
}

// ============================================
// Check if trip already exists (prevent duplicates)
// ============================================
$check_stmt = $conn->prepare("SELECT id FROM upcoming_trips WHERE user_id = ? AND destination_id = ? AND start_date = ? AND status = 'upcoming'");
$check_stmt->bind_param("iis", $user_id, $verified_destination_id, $start_date);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'This trip already exists in your planner']);
    $check_stmt->close();
    $conn->close();
    exit();
}
$check_stmt->close();

// ============================================
// Insert trip with verified destination ID and canonical name
// ============================================
$insert_stmt = $conn->prepare("INSERT INTO upcoming_trips (user_id, destination_id, destination_name, start_date, end_date, travelers, budget, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'upcoming')");
$insert_stmt->bind_param("iissiids", $user_id, $verified_destination_id, $canonical_destination_name, $start_date, $end_date, $travelers, $budget, $notes);

if ($insert_stmt->execute()) {
    // Record in user_history with proper activity details
    $history_stmt = $conn->prepare("INSERT INTO user_history (user_id, activity_type, activity_details) VALUES (?, 'trip_plan', ?)");
    $history_details = json_encode([
        'destination_id' => $verified_destination_id,
        'destination_name' => $canonical_destination_name,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'travelers' => $travelers
    ]);
    $history_stmt->bind_param("is", $user_id, $history_details);
    $history_stmt->execute();
    $history_stmt->close();

    echo json_encode(['status' => 'success', 'message' => 'Trip added successfully', 'trip_id' => $insert_stmt->insert_id]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to add trip: ' . $conn->error]);
}

$insert_stmt->close();
$conn->close();
