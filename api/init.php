<?php

echo "=== Server Room Monitor - Supabase Setup ===<br><br>";

echo "Go to Supabase Dashboard → SQL Editor and run:<br><br>";

echo "<pre style='background:#f5f5f5;padding:15px;border:1px solid #ccc;'>";
echo htmlspecialchars('-- 1. Create sensor_data table
CREATE TABLE sensor_data (
    id SERIAL PRIMARY KEY,
    temperature DECIMAL(5,2) NOT NULL,
    humidity DECIMAL(5,2) NOT NULL,
    led_status BOOLEAN DEFAULT FALSE,
    buzzer_status BOOLEAN DEFAULT FALSE,
    device_id VARCHAR(100) DEFAULT \'arduino-r4-001\',
    threshold DECIMAL(5,2) DEFAULT 31.0,
    alert_triggered BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Create alerts table
CREATE TABLE alerts (
    id SERIAL PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    temperature DECIMAL(5,2),
    threshold DECIMAL(5,2),
    device_id VARCHAR(100),
    acknowledged BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Create settings table
CREATE TABLE settings (
    id SERIAL PRIMARY KEY,
    key VARCHAR(100) UNIQUE NOT NULL,
    value DECIMAL(5,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Insert default threshold
INSERT INTO settings (key, value) VALUES (\'temperature_threshold\', 31.0);

-- 5. Disable RLS (for easy access)
ALTER TABLE sensor_data DISABLE ROW LEVEL SECURITY;
ALTER TABLE alerts DISABLE ROW LEVEL SECURITY;
ALTER TABLE settings DISABLE ROW LEVEL SECURITY;
');
echo "</pre>";

echo "<br>=== Next Steps ===<br><br>";
echo "1. Add your Supabase anon key to api/config.php<br>";
echo "2. Open XAMPP, start Apache<br>";
echo "3. Go to: <a href='http://localhost/IOTSYSTEM/'>http://localhost/IOTSYSTEM/</a><br>";
