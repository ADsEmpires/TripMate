<?php
// Function to track user's IP address and user agent
function trackUserIP($user_id, $conn) {
    // Get the user's IP address from server variables
    $ip_address = $_SERVER['REMOTE_ADDR'];
    // Get the user's browser user agent string
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    // Prepare SQL to insert new IP record for the user
    $insert_sql = "INSERT INTO user_ips (user_id, ip_address, user_agent) VALUES (?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql); // Prepare the statement
    $insert_stmt->bind_param("iss", $user_id, $ip_address, $user_agent); // Bind parameters
    $insert_stmt->execute(); // Execute the insert
    
    // Prepare SQL to delete older IP records, keeping only the last 5 for this user
    $cleanup_sql = "DELETE FROM user_ips 
                    WHERE user_id = ? 
                    AND id NOT IN (
                        SELECT id FROM (
                            SELECT id 
                            FROM user_ips 
                            WHERE user_id = ? 
                            ORDER BY login_time DESC 
                            LIMIT 5
                        ) AS recent_ips
                    )";
    $cleanup_stmt = $conn->prepare($cleanup_sql); // Prepare the statement
    $cleanup_stmt->bind_param("ii", $user_id, $user_id); // Bind parameters
    $cleanup_stmt->execute(); // Execute the cleanup
    
    return true; // Return success
}
?>