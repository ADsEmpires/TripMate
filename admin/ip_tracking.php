<?php
// Function to track user's IP address and user agent using PDO
function trackUserIP($user_id, $pdo) {
    // Get the user's real IP address checking various headers
    $ip_address = '';
    $ip_headers = array(
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    );
    
    foreach ($ip_headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip_array = explode(',', $_SERVER[$header]);
            $ip_address = trim($ip_array[0]);
            break;
        }
    }
    
    // Validate IP address format
    if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; // Fallback to REMOTE_ADDR if invalid
    }
    
    // Get the user's browser user agent string
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    try {
        // Prepare SQL to insert new IP record for the user
        $insert_sql = "INSERT INTO user_ips (user_id, ip_address, user_agent, login_time) VALUES (:user_id, :ip_address, :user_agent, NOW())";
        $insert_stmt = $pdo->prepare($insert_sql);
        $insert_stmt->execute([
            ':user_id' => $user_id,
            ':ip_address' => $ip_address,
            ':user_agent' => $user_agent
        ]);
        
        // Prepare SQL to delete older IP records, keeping only the last 5 for this user
        $cleanup_sql = "DELETE FROM user_ips 
                        WHERE user_id = :user_id 
                        AND id NOT IN (
                            SELECT id FROM (
                                SELECT id 
                                FROM user_ips 
                                WHERE user_id = :user_id2 
                                ORDER BY login_time DESC 
                                LIMIT 5
                            ) AS recent_ips
                        )";
        $cleanup_stmt = $pdo->prepare($cleanup_sql);
        $cleanup_stmt->execute([
            ':user_id' => $user_id,
            ':user_id2' => $user_id
        ]);
        
        return true;
    } catch (PDOException $e) {
        // Log error but don't break the application
        error_log("IP Tracking Error: " . $e->getMessage());
        return false;
    }
}
?>