<?php
require_once '../database/dbconfig.php';
$result = $conn->query("DESCRIBE flights");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
