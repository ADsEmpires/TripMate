<?php
// backend/ip_tracking.php
// Unified IP tracking for both users and admins

/**
 * Track user/admin IP address and user agent
 * @param int $user_id - The user or admin ID
 * @param PDO|mysqli $db - Database connection
 * @param string $user_type - 'user' or 'admin'
 * @return bool
 */
function trackUserIP($user_id, $db, $user_type = 'user')
{
    // Get real IP address
    $ip_address = '';
    $ip_headers = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];

    foreach ($ip_headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip_array = explode(',', $_SERVER[$header]);
            $ip_address = trim($ip_array[0]);
            break;
        }
    }

    // Validate IP
    if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    try {
        // For admins, intelligently use and auto-create the admin_ips table 
        // to avoid user_ips foreign key constraint failures!
        if ($user_type === 'admin') {
            if ($db instanceof mysqli) {
                $db->query("CREATE TABLE IF NOT EXISTS `admin_ips` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `admin_id` int(11) NOT NULL,
                  `ip_address` varchar(45) NOT NULL,
                  `user_agent` text DEFAULT NULL,
                  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

                $stmt = $db->prepare("INSERT INTO admin_ips (admin_id, ip_address, user_agent, login_time) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("iss", $user_id, $ip_address, $user_agent);
                $stmt->execute();

                // Keep only last 10 records per admin
                $cleanup = $db->prepare("DELETE FROM admin_ips WHERE admin_id = ? AND id NOT IN (SELECT id FROM (SELECT id FROM admin_ips WHERE admin_id = ? ORDER BY login_time DESC LIMIT 10) AS tmp)");
                $cleanup->bind_param("ii", $user_id, $user_id);
                $cleanup->execute();
            } else {
                // PDO version
                $db->query("CREATE TABLE IF NOT EXISTS `admin_ips` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `admin_id` int(11) NOT NULL,
                  `ip_address` varchar(45) NOT NULL,
                  `user_agent` text DEFAULT NULL,
                  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

                $stmt = $db->prepare("INSERT INTO admin_ips (admin_id, ip_address, user_agent, login_time) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$user_id, $ip_address, $user_agent]);
            }
            return true;
        }

        // For regular users, use user_ips table
        if ($db instanceof mysqli) {
            // Check if user_type exists
            $has_user_type = false;
            $check_col = $db->query("SHOW COLUMNS FROM user_ips LIKE 'user_type'");
            if ($check_col && $check_col->num_rows > 0) {
                $has_user_type = true;
            }

            $user_id_esc = $db->real_escape_string($user_id);
            $ip_address_esc = $db->real_escape_string($ip_address);
            $user_agent_esc = $db->real_escape_string($user_agent);
            $user_type_esc = $db->real_escape_string($user_type);

            // Get next ID physically since AUTO_INCREMENT might be missing in user_ips DB
            $id_query = $db->query("SELECT MAX(id) AS max_id FROM user_ips");
            $next_id = 1;
            if ($id_query && $row = $id_query->fetch_assoc()) {
                $next_id = ($row['max_id'] ?? 0) + 1;
            }

            if ($has_user_type) {
                // Insert new record
                $insert_sql = "INSERT INTO user_ips (id, user_id, user_type, ip_address, user_agent, login_time) 
                              VALUES ($next_id, '$user_id_esc', '$user_type_esc', '$ip_address_esc', '$user_agent_esc', NOW())";
                $db->query($insert_sql);

                // Keep only last 10 records per user/admin
                $cleanup_sql = "DELETE FROM user_ips 
                              WHERE user_id = '$user_id_esc' 
                              AND user_type = '$user_type_esc'
                              AND id NOT IN (
                                  SELECT id FROM (
                                      SELECT id 
                                      FROM user_ips 
                                      WHERE user_id = '$user_id_esc' 
                                      AND user_type = '$user_type_esc'
                                      ORDER BY login_time DESC 
                                      LIMIT 10
                                  ) AS recent_ips
                              )";
                $db->query($cleanup_sql);
            } else {
                $insert_sql = "INSERT INTO user_ips (id, user_id, ip_address, user_agent, login_time) 
                              VALUES ($next_id, '$user_id_esc', '$ip_address_esc', '$user_agent_esc', NOW())";
                $db->query($insert_sql);

                $cleanup_sql = "DELETE FROM user_ips 
                              WHERE user_id = '$user_id_esc' 
                              AND id NOT IN (
                                  SELECT id FROM (
                                      SELECT id 
                                      FROM user_ips 
                                      WHERE user_id = '$user_id_esc' 
                                      ORDER BY login_time DESC 
                                      LIMIT 10
                                  ) AS recent_ips
                              )";
                $db->query($cleanup_sql);
            }
        }
        else {
            // PDO version
            $id_query = $db->query("SELECT MAX(id) AS max_id FROM user_ips");
            $next_id = 1;
            if ($row = $id_query->fetch(PDO::FETCH_ASSOC)) {
                $next_id = ($row['max_id'] ?? 0) + 1;
            }

            $insert_sql = "INSERT INTO user_ips (id, user_id, user_type, ip_address, user_agent, login_time) 
                          VALUES (:next_id, :user_id, :user_type, :ip_address, :user_agent, NOW())";
            $insert_stmt = $db->prepare($insert_sql);
            $insert_stmt->execute([
                ':next_id' => $next_id,
                ':user_id' => $user_id,
                ':user_type' => $user_type,
                ':ip_address' => $ip_address,
                ':user_agent' => $user_agent
            ]);

            $cleanup_sql = "DELETE FROM user_ips 
                          WHERE user_id = :user_id 
                          AND user_type = :user_type
                          AND id NOT IN (
                              SELECT id FROM (
                                  SELECT id 
                                  FROM user_ips 
                                  WHERE user_id = :user_id2 
                                  AND user_type = :user_type2
                                  ORDER BY login_time DESC 
                                  LIMIT 10
                              ) AS recent_ips
                          )";
            $cleanup_stmt = $db->prepare($cleanup_sql);
            $cleanup_stmt->execute([
                ':user_id' => $user_id,
                ':user_type' => $user_type,
                ':user_id2' => $user_id,
                ':user_type2' => $user_type
            ]);
        }

        return true;
    }
    catch (Exception $e) {
        error_log("IP Tracking Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Track page activity (time spent, clicks)
 */
function trackPageActivity($user_id, $user_type = 'user')
{
?>
<script>
    (function () {
        let startTime = Date.now();
        let clickCount = 0;
        let pageName = window.location.pathname.split('/').pop() || 'index.php';
        let userId = <?= json_encode($user_id)?>;
        let userType = <?= json_encode($user_type)?>;

        document.addEventListener('click', () => { clickCount++; });

        function sendActivity() {
            let endTime = Date.now();
            let timeSpent = Math.round((endTime - startTime) / 1000);
            if (timeSpent < 1) timeSpent = 1;

            let data = new URLSearchParams({
                user_id: userId,
                user_type: userType,
                page_name: pageName,
                time_spent: timeSpent,
                click_count: clickCount
            });

            navigator.sendBeacon('../backand/page_activity.php', data);
        }

        window.addEventListener('beforeunload', sendActivity);
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'hidden') sendActivity();
        });
    })();
</script>
<?php
}
?>