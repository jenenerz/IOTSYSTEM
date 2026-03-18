<?php

// Supabase credentials
$SUPABASE_URL = "https://wrozfczqhmvhtpspzees.supabase.co";
$SUPABASE_KEY = "sb_publishable_e8eh3yIF9MKyptcvpVzmxQ_AgriWkaj"; // Add your anon key here

// API settings
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, apikey, Authorization');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
