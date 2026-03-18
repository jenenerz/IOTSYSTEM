<?php

include 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET: Get current threshold
if ($method === 'GET') {
    $result = $conn->query("SELECT * FROM settings WHERE `key` = 'temperature_threshold'");
    
    if ($result && $row = $result->fetch_assoc()) {
        $threshold = floatval($row['value']);
        $updatedAt = $row['updated_at'];
    } else {
        // Insert default threshold
        $conn->query("INSERT INTO settings (`key`, value) VALUES ('temperature_threshold', 31.0)");
        $threshold = 31.0;
        $updatedAt = null;
    }
    
    echo json_encode([
        "success" => true,
        "threshold" => $threshold,
        "updated_at" => $updatedAt,
        "min_threshold" => 20,
        "max_threshold" => 45
    ]);
    exit;
}

// POST: Update threshold
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['threshold'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing threshold value"]);
        exit;
    }
    
    $threshold = floatval($input['threshold']);
    
    // Validate range
    if ($threshold < 20 || $threshold > 45) {
        http_response_code(400);
        echo json_encode(["error" => "Threshold must be between 20 and 45"]);
        exit;
    }
    
    // Check if exists
    $check = $conn->query("SELECT id FROM settings WHERE `key` = 'temperature_threshold'");
    
    if ($check && $check->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE settings SET value = ?, updated_at = NOW() WHERE `key` = 'temperature_threshold'");
        $stmt->bind_param("d", $threshold);
    } else {
        $stmt = $conn->prepare("INSERT INTO settings (`key`, value) VALUES ('temperature_threshold', ?)");
        $stmt->bind_param("d", $threshold);
    }
    
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "threshold" => $threshold,
            "message" => "Threshold updated to $threshold°C"
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to update threshold"]);
    }
    
    $stmt->close();
    exit;
}

// Method not allowed
http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);

$conn->close();
