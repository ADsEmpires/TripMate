<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}
if (isset($_POST['theme']) && in_array($_POST['theme'], ['light', 'dark'])) {
    $_SESSION['admin_theme'] = $_POST['theme'];
    // try to persist to admin table if column exists
    include '../database/dbconfig.php';
    if (isset($_SESSION['admin_id'])) {
        $stmt = $conn->prepare("UPDATE admin SET theme = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('si', $_POST['theme'], $_SESSION['admin_id']);
            @$stmt->execute();
        }
    }
    // redirect back to settings page
    header('Location: admin_settings.php?section=system&msg=theme_saved');
    exit();
} else {
    header('Location: admin_settings.php?section=system&msg=invalid_theme');
    exit();
}
?>