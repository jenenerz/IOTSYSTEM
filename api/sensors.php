<?php

include 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

// Helper function to call Supabase
function supabaseRequest($endpoint, $method = 'GET', $body = null) {
    global $SUPABASE_URL, $SUPABASE_KEY;
    
    $url = "$SUPABASE_URL/rest/v1/$endpoint";
    $ch = curl_init($url);
    
    $headers = [
        "apikey: $SUPABASE_KEY",
        "Authorization: Bearer $SUPABASE_KEY",
        "Content-Type: application/json",
        "Prefer: return=representation"
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    if ($method === 'POST' || $method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($body) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'http_code' => $httpCode,
        'data' => $httpCode >= 200 && $httpCode < 300 ? json_decode($response, true) : null,
        'response' => $response
    ];
}

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
    $led_status = isset($input['led_status']) && $input['led_status'] ? true : false;
    $buzzer_status = isset($input['buzzer_status']) && $input['buzzer_status'] ? true : false;
    $device_id = isset($input['device_id']) ? $input['device_id'] : 'arduino-r4-001';
    
    // Get threshold
    $thresholdResult = supabaseRequest("settings?key=eq.temperature_threshold&select=value");
    $threshold = 31.0;
    if ($thresholdResult['data'] && !empty($thresholdResult['data'])) {
        $threshold = floatval($thresholdResult['data'][0]['value']);
    }
    
    $alert = $temperature >= $threshold;
    
    // Insert sensor data
    $result = supabaseRequest("sensor_data", "POST", [
        "temperature" => $temperature,
        "humidity" => $humidity,
        "led_status" => $led_status,
        "buzzer_status" => $buzzer_status,
        "device_id" => $device_id,
        "threshold" => $threshold,
        "alert_triggered" => $alert
    ]);
    
    if ($result['http_code'] >= 200 && $result['http_code'] < 300) {
        if ($alert) {
            supabaseRequest("alerts", "POST", [
                "type" => "temperature_threshold",
                "temperature" => $temperature,
                "threshold" => $threshold,
                "device_id" => $device_id
            ]);
        }
        
        echo json_encode([
            "success" => true,
            "alert" => $alert,
            "threshold" => $threshold,
            "message" => $alert ? "Temperature threshold exceeded!" : "Data recorded"
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to insert data", "details" => $result['response']]);
    }
    exit;
}

// GET: Fetch readings for dashboard
if ($method === 'GET') {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 60;
    $limit = min(max($limit, 1), 1000);
    
    // Get readings
    $result = supabaseRequest("sensor_data?select=*&order=created_at.desc&limit=$limit");
    $readings = [];
    
    if ($result['data']) {
        foreach ($result['data'] as $row) {
            $readings[] = [
                "id" => $row['id'],
                "timestamp" => $row['created_at'],
                "temperature" => $row['temperature'],
                "humidity" => $row['humidity'],
                "led_status" => $row['led_status'],
                "buzzer_status" => $row['buzzer_status'],
                "device_id" => $row['device_id'],
                "alert_triggered" => $row['alert_triggered']
            ];
        }
    }
    
    // Get stats
    $statsResult = supabaseRequest("sensor_data?select=temperature,humidity,alert_triggered");
    $temps = [];
    $humidities = [];
    $alertCount = 0;
    
    if ($statsResult['data']) {
        foreach ($statsResult['data'] as $row) {
            $temps[] = $row['temperature'];
            $humidities[] = $row['humidity'];
            if ($row['alert_triggered']) $alertCount++;
        }
    }
    
    $stats = [
        "maxTemp" => !empty($temps) ? max($temps) : null,
        "minTemp" => !empty($temps) ? min($temps) : null,
        "avgTemp" => !empty($temps) ? array_sum($temps) / count($temps) : null,
        "maxHumidity" => !empty($humidities) ? max($humidities) : null,
        "minHumidity" => !empty($humidities) ? min($humidities) : null,
        "avgHumidity" => !empty($humidities) ? array_sum($humidities) / count($humidities) : null,
        "totalReadings" => count($temps),
        "totalAlerts" => $alertCount
    ];
    
    // Get threshold
    $thresholdResult = supabaseRequest("settings?key=eq.temperature_threshold&select=value");
    $currentThreshold = 31.0;
    if ($thresholdResult['data'] && !empty($thresholdResult['data'])) {
        $currentThreshold = floatval($thresholdResult['data'][0]['value']);
    }
    
    echo json_encode([
        "success" => true,
        "readings" => $readings,
        "stats" => $stats,
        "threshold" => $currentThreshold,
        "count" => count($readings)
    ]);
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);
