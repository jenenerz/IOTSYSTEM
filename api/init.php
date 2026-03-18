<?php

// Database setup script - run this once to create tables

$servername = "localhost";
$username = "root";
$password = "";

echo "=== Server Room Monitor - Database Setup ===<br><br>";

// Connect without database first
$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS serverroom_monitor";
if ($conn->query($sql) === TRUE) {
    echo "✓ Database created<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

$conn->select_db("serverroom_monitor");

// Create sensor_data table
$sql1 = "CREATE TABLE IF NOT EXISTS sensor_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    temperature DECIMAL(5,2) NOT NULL,
    humidity DECIMAL(5,2) NOT NULL,
    led_status TINYINT(1) DEFAULT 0,
    buzzer_status TINYINT(1) DEFAULT 0,
    device_id VARCHAR(100) DEFAULT 'arduino-r4-001',
    threshold DECIMAL(5,2) DEFAULT 31.0,
    alert_triggered TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql1) === TRUE) {
    echo "✓ sensor_data table created<br>";
} else {
    echo "Error: " . $conn->error . "<br>";
}

// Create alerts table
$sql2 = "CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    temperature DECIMAL(5,2),
    threshold DECIMAL(5,2),
    device_id VARCHAR(100),
    acknowledged TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql2) === TRUE) {
    echo "✓ alerts table created<br>";
} else {
    echo "Error: " . $conn->error . "<br>";
}

// Create settings table
$sql3 = "CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) UNIQUE NOT NULL,
    value DECIMAL(5,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql3) === TRUE) {
    echo "✓ settings table created<br>";
} else {
    echo "Error: " . $conn->error . "<br>";
}

// Insert default threshold
$sql4 = "INSERT IGNORE INTO settings (`key`, value) VALUES ('temperature_threshold', 31.0)";
$conn->query($sql4);

$conn->close();

echo "<br>=== Setup Complete! ===<br>";
echo "Now start XAMPP Apache and go to: <a href='http://localhost/IOTSYSTEM/'>http://localhost/IOTSYSTEM/</a>";
