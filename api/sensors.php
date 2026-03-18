<?php

include 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

// POST: Receive data from Arduino
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['temperature']) || !isset($input['humidity'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing temperature or humidity"]);
        exit;
    }
    
    $temperature = floatval($input['temperature']);
    $humidity = floatval($input['humidity']);
    $led_status = isset($input['led_status']) ? ($input['led_status'] ? 1 : 0) : 0;
    $buzzer_status = isset($input['buzzer_status']) ? ($input['buzzer_status'] ? 1 : 0) : 0;
    $device_id = isset($input['device_id']) ? $input['device_id'] : 'arduino-r4-001';
    
    // Get threshold from database
    $result = $conn->query("SELECT value FROM settings WHERE `key` = 'temperature_threshold'");
    if ($result && $row = $result->fetch_assoc()) {
        $threshold = floatval($row['value']);
    }
    
    $alert = $temperature >= $threshold ? 1 : 0;
    
    // Insert sensor data
    $stmt = $conn->prepare("INSERT INTO sensor_data (temperature, humidity, led_status, buzzer_status, device_id, threshold, alert_triggered) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ddiissd", $temperature, $humidity, $led_status, $buzzer_status, $device_id, $threshold, $alert);
    
    if ($stmt->execute()) {
        // Log alert if threshold exceeded
        if ($alert) {
            $alertStmt = $conn->prepare("INSERT INTO alerts (type, temperature, threshold, device_id) VALUES (?, ?, ?, ?)");
            $alertStmt->bind_param("sdds", $type, $temperature, $threshold, $device_id);
            $type = 'temperature_threshold';
            $alertStmt->execute();
            $alertStmt->close();
        }
        
        echo json_encode([
            "success" => true,
            "alert" => $alert,
            "threshold" => $threshold,
            "message" => $alert ? "Temperature threshold exceeded!" : "Data recorded"
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to insert data"]);
    }
    
    $stmt->close();
    exit;
}

// GET: Fetch readings for dashboard
if ($method === 'GET') {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 60;
    $limit = min(max($limit, 1), 1000);
    
    // Get readings
    $result = $conn->query("SELECT * FROM sensor_data ORDER BY created_at DESC LIMIT $limit");
    $readings = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $readings[] = [
                "id" => $row['id'],
                "timestamp" => $row['created_at'],
                "temperature" => $row['temperature'],
                "humidity" => $row['humidity'],
                "led_status" => (bool)$row['led_status'],
                "buzzer_status" => (bool)$row['buzzer_status'],
                "device_id" => $row['device_id'],
                "alert_triggered" => (bool)$row['alert_triggered']
            ];
        }
    }
    
    // Get stats
    $statsResult = $conn->query("SELECT 
        MAX(temperature) as maxTemp, 
        MIN(temperature) as minTemp, 
        AVG(temperature) as avgTemp,
        MAX(humidity) as maxHumidity,
        MIN(humidity) as minHumidity,
        AVG(humidity) as avgHumidity,
        COUNT(*) as totalReadings,
        SUM(alert_triggered) as totalAlerts
        FROM sensor_data");
    
    $stats = $statsResult ? $statsResult->fetch_assoc() : null;
    
    // Get threshold
    $thresholdResult = $conn->query("SELECT value FROM settings WHERE `key` = 'temperature_threshold'");
    $currentThreshold = $thresholdResult && $row = $thresholdResult->fetch_assoc() ? floatval($row['value']) : 31.0;
    
    echo json_encode([
        "success" => true,
        "readings" => $readings,
        "stats" => $stats,
        "threshold" => $currentThreshold,
        "count" => count($readings)
    ]);
    exit;
}

// Method not allowed
http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);

$conn->close();
