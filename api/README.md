# Server Room Monitor - Backend Setup (Supabase)

## Prerequisites

1. **PHP 8.1+**
2. **Supabase Account** (free)
3. **Web Browser** for Supabase Dashboard

## Supabase Setup

### Step 1: Create Supabase Project

1. Go to [supabase.com](https://supabase.com) and sign in
2. Click **"New Project"**
3. Enter details:
   - **Name**: `server-room-monitor`
   - **Database Password**: Create a strong password (save it!)
   - **Region**: Select **Singapore** (closest to you)
4. Click **"Create new project"** (wait 1-2 minutes)

### Step 2: Get API Credentials

1. In Supabase dashboard, go to **Settings** (⚙️) → **API**
2. Copy:
   - **Project URL**: `https://xxxxxx.supabase.co`
   - **anon public** key (under Project API keys)

### Step 3: Create Database Tables

1. Go to **SQL Editor** in Supabase sidebar
2. Copy and run this SQL:

```sql
-- Create sensor_data table
CREATE TABLE IF NOT EXISTS sensor_data (
    id SERIAL PRIMARY KEY,
    temperature DECIMAL(5,2) NOT NULL,
    humidity DECIMAL(5,2) NOT NULL,
    led_status BOOLEAN DEFAULT FALSE,
    buzzer_status BOOLEAN DEFAULT FALSE,
    device_id VARCHAR(100) DEFAULT 'arduino-r4-001',
    threshold DECIMAL(5,2) DEFAULT 31.0,
    alert_triggered BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create alerts table
CREATE TABLE IF NOT EXISTS alerts (
    id SERIAL PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    temperature DECIMAL(5,2),
    threshold DECIMAL(5,2),
    device_id VARCHAR(100),
    acknowledged BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create settings table
CREATE TABLE IF NOT EXISTS settings (
    id SERIAL PRIMARY KEY,
    key VARCHAR(100) UNIQUE NOT NULL,
    value DECIMAL(5,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default threshold
INSERT INTO settings (key, value, description)
VALUES ('temperature_threshold', 31.0, 'Temperature threshold for alert triggering')
ON CONFLICT (key) DO NOTHING;

-- Enable RLS (optional)
ALTER TABLE sensor_data ENABLE ROW LEVEL SECURITY;
ALTER TABLE alerts ENABLE ROW LEVEL SECURITY;
ALTER TABLE settings ENABLE ROW LEVEL SECURITY;

-- Create public access policies
CREATE POLICY "public_read_sensor" ON sensor_data FOR SELECT USING (true);
CREATE POLICY "public_insert_sensor" ON sensor_data FOR INSERT WITH CHECK (true);
CREATE POLICY "public_read_alerts" ON alerts FOR SELECT USING (true);
CREATE POLICY "public_insert_alerts" ON alerts FOR INSERT WITH CHECK (true);
CREATE POLICY "public_read_settings" ON settings FOR SELECT USING (true);
CREATE POLICY "public_update_settings" ON settings FOR UPDATE USING (true);

-- Create indexes
CREATE INDEX idx_sensor_created ON sensor_data(created_at DESC);
CREATE INDEX idx_alerts_created ON alerts(created_at DESC);
```

### Step 4: Configure Backend

```bash
cd api
cp .env.example .env
```

Edit `.env`:
```env
SUPABASE_URL=https://wrozfczqhmvhtpspzees.supabase.co
SUPABASE_KEY=sb_publishable_e8eh3yIF9MKyptcvpVzmxQ_AgriWkaj
```

### Step 5: Start Server

```bash
php -S localhost:8000
```

### Step 6: Access Dashboard

Open: http://localhost:8000

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/sensors.php` | Fetch sensor readings |
| POST | `/api/sensors.php` | Receive Arduino data |
| GET | `/api/threshold.php` | Get current threshold |
| POST | `/api/threshold.php` | Update threshold |

## Arduino Configuration

In your Arduino sketch, update:

```cpp
const char* serverUrl = "http://YOUR_SERVER_IP:8000/api/sensors.php";
```

Replace `YOUR_SERVER_IP` with your computer's local IP address.

## Testing

```bash
# Test GET
curl http://localhost:8000/api/sensors.php

# Test POST
curl -X POST http://localhost:8000/api/sensors.php \
  -H "Content-Type: application/json" \
  -d '{"temperature": 25.5, "humidity": 60, "device_id": "test-001"}'
```
