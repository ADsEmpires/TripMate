<?php
require_once '../database/dbconfig.php';
$res = $conn->query('SELECT id FROM destination_cities LIMIT 1'); 
if($row=$res->fetch_assoc()){ echo 'City ID: '.$row['id']."\n"; } else { echo "No cities found in destination_cities.\n"; }

$res2 = $conn->query('SELECT id FROM destinations LIMIT 1'); 
if($row=$res2->fetch_assoc()){ echo 'Dest ID: '.$row['id']."\n"; } else { echo "No destinations found.\n"; }
?>
