<?php

// DATABASE credentials (XAMPP MySQL)
$servername = "localhost";
$username = "root";
$password = "";
$database = "serverroom_monitor";

// Default threshold
$threshold = 31.0;

// API settings
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Connect to database
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}
