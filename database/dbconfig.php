<?php

// Database connection details
$host = 'localhost';
$dbname = 'tripmate';
$username = 'root';
$password = ''; // Use your actual MySQL password

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

// Set charset
$conn->set_charset("utf8mb4");



/*
// Database connection details
$host = 'localhost';
$dbname = 'tripmate';  // Changed from 'TripMate' 
$username = 'root';
$password = ''; // use your actual MySQL password

// Create a connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
*/

/*

// Database connection details
$host = '127.0.0.1';      // Use IP instead of 'localhost' to avoid socket issues
$port = '3307';           // Custom port
$dbname = 'tripmate';     // Your database name
$username = 'root';       // Your MySQL username
$password = 'Ranajit@sql2005';           // Your MySQL password

// Create connection using MySQLi with port
$conn = new mysqli($host, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully on port 3307!";
*/
?>