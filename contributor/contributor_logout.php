<?php
session_start();

// Clear only contributor session data
unset(
    $_SESSION['contributor_id'],
    $_SESSION['contributor_name'],
    $_SESSION['contributor_email'],
    $_SESSION['contributor_profile_pic']
);

// If no other sessions active, destroy completely
if (empty($_SESSION)) {
    session_destroy();
}

header('Location: contributor_login.php');
exit();
?>