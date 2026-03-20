<?php

// Supabase credentials
$SUPABASE_URL = "https://wrozfczqhmvhtpspzees.supabase.co";
$SUPABASE_KEY = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Indyb3pmY3pxaG12aHRwc3B6ZWVzIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzM3NTA1MzksImV4cCI6MjA4OTMyNjUzOX0.rzn9QWXuS5woa_EzTSkWHyiZQBCJiXITDwPrFbAS178"; // Add your anon key here

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
