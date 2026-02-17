<?php
// track_activity.php - Simple and reliable tracking function
function trackUserActivity($page_name, $page_type, $activity_type = 'view') {
    // Database connection
    $conn = new mysqli("localhost", "root", "", "tripmate");
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        return false;
    }
    
    // Get user data
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $session_id = session_id();
    $activity_date = date('Y-m-d');
    
    // Insert activity
    $stmt = $conn->prepare("
        INSERT INTO user_activity (user_id, page_name, page_type, activity_type, ip_address, user_agent, session_id, activity_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        $conn->close();
        return false;
    }
    
    $stmt->bind_param("isssssss", $user_id, $page_name, $page_type, $activity_type, $ip_address, $user_agent, $session_id, $activity_date);
    
    $result = $stmt->execute();
    
    if (!$result) {
        error_log("Activity tracking failed: " . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Simple function to track page views
function trackPageView($page_name, $page_type) {
    return trackUserActivity($page_name, $page_type, 'view');
}

// Simple function to track button clicks
function trackButtonClick($button_name, $page_type) {
    return trackUserActivity($button_name, $page_type, 'click');
}

// Update analytics table
function updateWebsiteAnalytics() {
    $conn = new mysqli("localhost", "root", "", "tripmate");
    
    if ($conn->connect_error) {
        return false;
    }
    
    // Clear old analytics data (last 7 days)
    $conn->query("DELETE FROM website_analytics WHERE date_date >= CURDATE() - INTERVAL 7 DAY");
    
    // Insert updated analytics data
    $update_sql = "
    INSERT INTO website_analytics (page_name, page_type, views, clicks, logged_in_views, logged_in_clicks, guest_views, guest_clicks, date_date)
    SELECT 
        page_name,
        page_type,
        COUNT(CASE WHEN activity_type = 'view' THEN 1 END) as views,
        COUNT(CASE WHEN activity_type = 'click' THEN 1 END) as clicks,
        COUNT(CASE WHEN activity_type = 'view' AND user_id IS NOT NULL THEN 1 END) as logged_in_views,
        COUNT(CASE WHEN activity_type = 'click' AND user_id IS NOT NULL THEN 1 END) as logged_in_clicks,
        COUNT(CASE WHEN activity_type = 'view' AND user_id IS NULL THEN 1 END) as guest_views,
        COUNT(CASE WHEN activity_type = 'click' AND user_id IS NULL THEN 1 END) as guest_clicks,
        activity_date as date_date
    FROM user_activity 
    WHERE activity_date >= CURDATE() - INTERVAL 7 DAY
    GROUP BY page_name, page_type, activity_date
    ORDER BY date_date DESC
    ";
    
    $result = $conn->query($update_sql);
    $conn->close();
    
    return $result;
}
?>