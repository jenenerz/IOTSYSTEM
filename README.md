# Server Room Monitor - IoT System

An Arduino Uno R4 WiFi based temperature and humidity monitoring system with FastAPI backend and web UI.

## System Overview

```
┌─────────────┐    WiFi     ┌─────────────────┐    SQLite    ┌──────────────┐
│  Arduino    │ ──────────► │  FastAPI Server │ ◄───────────► │  Database    │
│  Uno R4     │             │  (Port 8000)    │              │  (server_room│
│  + DHT11    │             │                 │              │   .db)       │
└─────────────┘             └────────┬────────┘              └──────────────┘
                                     │
                                     ▼
                              ┌──────────────┐
                              │  Web Browser │
                              │  (Frontend)  │
                              └──────────────┘
```

## Components

| Component | Description |
|-----------|-------------|
| **Arduino Uno R4 WiFi** | Sends sensor data to server |
| **DHT11 Sensor** | Measures temperature (0-50°C) and humidity (20-90%) |
| **Red LED (Pin 13)** | Lights up when temperature exceeds threshold |
| **Piezo Buzzer (Pin 8)** | Beeps when alert is triggered |
| **FastAPI Server** | Receives and stores sensor data |
| **SQLite Database** | Stores readings and alerts |
| **Web UI** | Displays real-time data and alerts |

## Requirements

### Hardware
- Arduino Uno R4 WiFi
- DHT11 Temperature & Humidity Sensor
- Red LED
- Piezo Buzzer
- Jumper wires
- USB cable for programming

### Software
- Python 3.8+
- Arduino IDE
- Web browser

### Python Packages
```
pip install fastapi uvicorn pydantic python-multipart
```

## Pin Connections

```
Arduino Uno R4 WiFi
┌─────────────────────┐
│                     │
│   DHT11             │
│   ─────             │
│   VCC ─────► 5V     │
│   DATA ────► Pin 2  │
│   GND ─────► GND    │
│                     │
│   LED               │
│   ───               │
│   Positive ► Pin 13│
│   Negative ► GND    │
│                     │
│   Buzzer            │
│   ──────            │
│   Positive ► Pin 8 │
│   Negative ► GND   │
│                     │
└─────────────────────┘
```

## Step-by-Step Setup

### Step 1: Find Your Computer IP Address

Open Command Prompt and run:
```cmd
ipconfig
```

Look for "IPv4 Address" under your WiFi adapter:
```
Wireless LAN adapter Wi-Fi:

   IPv4 Address. . . . . . . . . . : 192.168.68.104
```

**Your IP is likely: 192.168.68.104**

### Step 2: Start the FastAPI Server

```cmd
cd c:\xampp\htdocs\IOTSYSTEM
python -m uvicorn api.main:app --host 0.0.0.0 --port 8000
```

The server will start at: **http://localhost:8000**

You can also access from other devices on your network:
**http://192.168.68.104:8000**

### Step 3: Configure the Arduino Sketch

Open [`api/arduino_sketch.cpp`](api/arduino_sketch.cpp) in Arduino IDE and update:

```cpp
// WiFi credentials
const char* ssid = "YOUR_WIFI_SSID";           // Your WiFi name
const char* password = "YOUR_WIFI_PASSWORD";  // Your WiFi password

// Server IP (from Step 1)
const char* apiServer = "http://192.168.68.104:8000";
```

### Step 4: Install Arduino Libraries

In Arduino IDE, go to **Sketch > Include Library > Manage Libraries**

Search and install:
1. **DHT sensor library** by Adafruit
2. **Adafruit Unified Sensor** by Adafruit

### Step 5: Upload Sketch to Arduino

1. Connect Arduino via USB
2. Select correct board: **Tools > Board > Arduino UNO R4 WiFi**
3. Select correct port: **Tools > Port > COMx (Arduino UNO R4 WiFi)**
4. Click **Upload**

### Step 6: Open the Web UI

Open your browser and navigate to:
```
c:\xampp\htdocs\IOTSYSTEM\server-room-monitor.html
```

Or via server (after modifying sketch to fetch data):
```
http://192.168.68.104:8000/docs
```

## How It Works

### Alert Logic
1. DHT11 reads temperature every 2 seconds
2. Arduino sends data to FastAPI server every 5 seconds
3. Server checks if temperature > threshold (default: 31°C)
4. If exceeded:
   - API returns `alert: true`
   - Arduino activates LED (Pin 13) and buzzer (Pin 8)
   - Alert is logged in database

### API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/` | GET | Health check |
| `/api/sensors` | GET | Get latest reading |
| `/api/sensors` | POST | Submit sensor data |
| `/api/settings` | GET | Get threshold |
| `/api/settings?threshold=30` | PUT | Update threshold |
| `/api/alerts` | GET | Get alert history |
| `/api/stats` | GET | Get statistics |
| `/api/history` | GET | Get data history |

### Testing Without Arduino

You can test the system using curl:

```cmd
# Normal reading (no alert)
curl -X POST http://localhost:8000/api/sensors -H "Content-Type: application/json" -d "{\"temperature\": 25.5, \"humidity\": 55.0}"

# Alert reading (triggers LED and buzzer)
curl -X POST http://localhost:8000/api/sensors -H "Content-Type: application/json" -d "{\"temperature\": 33.5, \"humidity\": 62.0}"

# Change threshold to 35°C
curl -X PUT "http://localhost:8000/api/settings?threshold=35"

# View current stats
curl http://localhost:8000/api/stats
```

## Troubleshooting

### Arduino Can't Connect to WiFi
- Verify WiFi credentials are correct
- Make sure Arduino is within WiFi range
- Check serial monitor for error messages

### Server Not Receiving Data
- Verify computer and Arduino are on same WiFi network
- Check firewall allows port 8000
- Verify IP address is correct in sketch

### DHT11 Reading Errors
- Check wiring connections
- Verify DHT library is installed
- Try different pin if issues persist

### View Serial Monitor
In Arduino IDE: **Tools > Serial Monitor** (115200 baud)

## Default Settings

| Setting | Value |
|---------|-------|
| Temperature Threshold | 31°C |
| Update Interval | 5 seconds |
| Database | server_room.db |

## File Structure

```
IOTSYSTEM/
├── server-room-monitor.html    # Web UI (open in browser)
├── README.md                   # This file
├── server_room.db             # SQLite database (auto-created)
└── api/
    ├── main.py                # FastAPI application
    ├── requirements.txt       # Python dependencies
    └── arduino_sketch.cpp     # Arduino code
```

## Security Notes

- This system is for local network use only
- The API allows CORS from all origins (`*`)
- For production, add authentication and restrict origins

## Support

Check the serial monitor in Arduino IDE for debug output.
