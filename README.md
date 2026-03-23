# Server Room Monitor - IoT System

A real-time temperature and humidity monitoring system for server rooms using Arduino UNO R4 WiFi, DHT11 sensor, and a FastAPI backend with web dashboard.

## Features

- **Real-time Monitoring**: DHT11 sensor reads temperature and humidity every 5 seconds
- **Temperature Alerts**: Visual (red LED) and audio (piezo buzzer) alerts when temperature exceeds threshold
- **Web Dashboard**: Interactive dashboard with live data, charts, and statistics
- **RESTful API**: FastAPI backend with SQLite database for data persistence
- **Responsive Design**: Works on desktop and mobile devices
- **Retro UI**: Pixel-perfect, brutalist web interface

## Hardware Requirements

- **Arduino UNO R4 WiFi**
- **DHT11 Temperature/Humidity Sensor**
- **Red LED** (with 220Ω resistor)
- **Piezo Buzzer** (5V)
- **Jumper Wires**

### Pin Configuration

| Component | Arduino Pin |
|-----------|------------|
| DHT11 Data | Pin 2 |
| Red LED | Pin 13 |
| Piezo Buzzer | Pin 8 |

## Software Requirements

- Python 3.9+
- Arduino IDE with WiFiS3 and DHT libraries

## Setup Instructions

### 1. Backend Setup

```bash
# Navigate to project directory
cd IOTSYSTEM

# Create virtual environment
python -m venv venv

# Activate virtual environment
# On Windows:
venv\Scripts\Activate.ps1
# On macOS/Linux:
source venv/bin/activate

# Install dependencies
pip install -r api/requirements.txt
```

### 2. Start the Backend Server

```bash
# Navigate to api folder
cd api

# Start FastAPI server (accessible from network)
python -m uvicorn main:app --reload --host 0.0.0.0 --port 8000
```

You should see:
```
INFO:     Uvicorn running on http://0.0.0.0:8000
```

### 3. Arduino Setup

1. **Update WiFi Credentials** in the sketch:
   ```cpp
   const char* ssid = "YOUR_WIFI_SSID";
   const char* password = "YOUR_WIFI_PASSWORD";
   ```

2. **Update Server IP Address**:
   ```cpp
   const char* serverAddress = "192.168.1.18";  // Your computer's IP
   const int serverPort = 8000;
   ```

3. **Find Your IP Address**:
   ```powershell
   ipconfig
   ```
   Look for **IPv4 Address** on your WiFi adapter

4. **Upload the Sketch** to Arduino UNO R4 WiFi

### 4. Open Frontend

Open `server-room-monitor.html` in your web browser or navigate to:
```
http://192.168.1.18:8000/docs
```

## API Endpoints

### Get Latest Sensor Reading
```
GET /api/sensors
```

Response:
```json
{
  "id": 1,
  "temperature": 29.7,
  "humidity": 51.0,
  "timestamp": "2026-03-23 16:34:05",
  "threshold": 31.0,
  "alert": false
}
```

### Post Sensor Data (Arduino)
```
POST /api/sensors
Content-Type: application/json

{
  "temperature": 29.7,
  "humidity": 51.0
}
```

Response:
```json
{
  "success": true,
  "reading_id": 1,
  "alert": false,
  "threshold": 31.0,
  "message": "Reading saved"
}
```

### Get Historical Data
```
GET /api/history?limit=60
```

### Get Settings
```
GET /api/settings
```

### Update Threshold
```
PUT /api/settings
Content-Type: application/json

{
  "threshold": 32.5
}
```

## Troubleshooting

### Arduino Can't Connect to Server

**Problem**: `ERROR: Connection failed!`

**Solutions**:
1. **Check IP Address**: Ensure server address matches your computer's IP
   ```powershell
   ipconfig
   ```

2. **Start Server on All Interfaces**:
   ```bash
   python -m uvicorn main:app --reload --host 0.0.0.0 --port 8000
   ```

3. **Open Windows Firewall**:
   ```powershell
   netsh advfirewall firewall add rule name="FastAPI Port 8000" dir=in action=allow protocol=tcp localport=8000 profile=any
   ```

4. **Test Connection**:
   ```powershell
   curl http://192.168.1.18:8000
   ```

### Arduino WiFi Won't Connect

**Problem**: `ERROR: Failed to connect to WiFi!`

**Solutions**:
1. Verify SSID and password are correct
2. Check that 2.4GHz WiFi is available (UNO R4 doesn't support 5GHz)
3. Move Arduino closer to router
4. Restart Arduino

### Web Dashboard Not Showing Data

**Problem**: "Waiting for data..."

**Solutions**:
1. Ensure backend is running: `http://192.168.1.18:8000`
2. Check Arduino serial monitor for connection status
3. Verify API endpoint: `http://192.168.1.18:8000/api/sensors`

## Database

The backend uses SQLite (`server_room.db`) with tables:
- `sensor_readings` - Temperature and humidity readings
- `settings` - Threshold configuration
- `alerts` - Alert history

## Project Structure

```
IOTSYSTEM/
├── server-room-monitor.html    # Frontend dashboard
├── api/
│   ├── main.py                 # FastAPI backend
│   └── requirements.txt         # Python dependencies
├── venv/                        # Virtual environment
├── server_room.db               # SQLite database
└── README.md                    # This file
```

## Arduino Sketch Features

- Auto-reconnect to WiFi if connection drops
- 10-second connection timeout
- PHT11 sensor validation
- Temperature threshold monitoring
- Pulsing buzzer when alert active
- Detailed serial logging

## Performance Notes

- Data sent every 5 seconds
- Server processes data in real-time
- Database keeps full history
- Web dashboard updates via polling
- Threshold adjustable via API or frontend

## Development

To modify the API:
1. Edit `api/main.py`
2. Server reloads automatically (with `--reload` flag)

To modify the frontend:
1. Edit `server-room-monitor.html`
2. Refresh browser to see changes

## License

Open source project. Free to use and modify.

## Support

For issues or questions, check:
1. **Arduino Serial Monitor** - See connection and sensor status
2. **Server Logs** - Terminal output when running `uvicorn`
3. **Browser Developer Console** - Frontend errors (F12)
