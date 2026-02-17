<?php
// track_click.php
session_start();
include 'track_activity.php';

if (isset($_GET['button'])) {
    $button_name = $_GET['button'];
    $page_type = 'general'; // You can make this dynamic
    
    trackButtonClick($button_name, $page_type);
    echo json_encode(['status' => 'success', 'button' => $button_name]);
}
?>