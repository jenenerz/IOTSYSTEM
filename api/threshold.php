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

// GET: Get current threshold
if ($method === 'GET') {
    $result = supabaseRequest("settings?key=eq.temperature_threshold&select=*");
    
    if ($result['data'] && !empty($result['data'])) {
        $threshold = floatval($result['data'][0]['value']);
        $updatedAt = $result['data'][0]['updated_at'];
    } else {
        // Create default
        supabaseRequest("settings", "POST", [
            "key" => "temperature_threshold",
            "value" => 31.0
        ]);
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
    
    if ($threshold < 20 || $threshold > 45) {
        http_response_code(400);
        echo json_encode(["error" => "Threshold must be between 20 and 45"]);
        exit;
    }
    
    // Check if exists
    $check = supabaseRequest("settings?key=eq.temperature_threshold&select=id");
    
    if ($check['data'] && !empty($check['data'])) {
        $id = $check['data'][0]['id'];
        $result = supabaseRequest("settings?id=eq.$id", "PATCH", [
            "value" => $threshold,
            "updated_at" => date('Y-m-d H:i:s')
        ]);
    } else {
        $result = supabaseRequest("settings", "POST", [
            "key" => "temperature_threshold",
            "value" => $threshold
        ]);
    }
    
    if ($result['http_code'] >= 200 && $result['http_code'] < 300) {
        echo json_encode([
            "success" => true,
            "threshold" => $threshold,
            "message" => "Threshold updated to $threshold°C"
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to update threshold"]);
    }
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);
