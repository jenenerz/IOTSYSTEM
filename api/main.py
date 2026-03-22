"""
Server Room Monitor API
FastAPI backend for Arduino Uno R4 WiFi DHT11 sensor monitoring
"""

from fastapi import FastAPI, HTTPException, Query
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional, List
from datetime import datetime
import sqlite3
import os

# Database configuration
DATABASE_PATH = "server_room.db"

app = FastAPI(
    title="Server Room Monitor API",
    description="API for Arduino DHT11 temperature and humidity monitoring",
    version="1.0.0"
)

# CORS middleware to allow frontend access
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


# Database initialization
def init_db():
    """Initialize SQLite database with required tables"""
    conn = sqlite3.connect(DATABASE_PATH)
    cursor = conn.cursor()
    
    # Sensor readings table
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS sensor_readings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            temperature REAL NOT NULL,
            humidity REAL NOT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ''')
    
    # Settings table
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY CHECK (id = 1),
            threshold REAL DEFAULT 31.0,
            last_updated DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ''')
    
    # Insert default settings if not exists
    cursor.execute('SELECT COUNT(*) FROM settings')
    if cursor.fetchone()[0] == 0:
        cursor.execute('INSERT INTO settings (id, threshold) VALUES (1, 31.0)')
    
    # Alerts table for history
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS alerts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            temperature REAL NOT NULL,
            threshold REAL NOT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            acknowledged INTEGER DEFAULT 0
        )
    ''')
    
    conn.commit()
    conn.close()


def get_db_connection():
    """Get database connection"""
    conn = sqlite3.connect(DATABASE_PATH)
    conn.row_factory = sqlite3.Row
    return conn


# Pydantic models
class SensorData(BaseModel):
    """Model for incoming sensor data from Arduino"""
    temperature: float
    humidity: float


class SensorReading(BaseModel):
    """Model for sensor reading response"""
    id: int
    temperature: float
    humidity: float
    timestamp: str


class Settings(BaseModel):
    """Model for settings response"""
    threshold: float
    last_updated: str


class Alert(BaseModel):
    """Model for alert response"""
    id: int
    temperature: float
    threshold: float
    timestamp: str
    acknowledged: bool


# Initialize database on startup
@app.on_event("startup")
def startup_event():
    init_db()


# Health check endpoint
@app.get("/")
def root():
    return {"status": "online", "message": "Server Room Monitor API is running"}


# Get current sensor data (latest reading)
@app.get("/api/sensors")
def get_sensors():
    """Get the latest sensor readings"""
    conn = get_db_connection()
    cursor = conn.cursor()
    
    cursor.execute('''
        SELECT id, temperature, humidity, timestamp 
        FROM sensor_readings 
        ORDER BY timestamp DESC 
        LIMIT 1
    ''')
    row = cursor.fetchone()
    conn.close()
    
    if row:
        return {
            "id": row["id"],
            "temperature": row["temperature"],
            "humidity": row["humidity"],
            "timestamp": row["timestamp"],
            "threshold": get_current_threshold(),
            "alert": row["temperature"] > get_current_threshold()
        }
    else:
        return {
            "temperature": None,
            "humidity": None,
            "timestamp": None,
            "threshold": get_current_threshold(),
            "alert": False,
            "message": "No sensor data available"
        }


def get_current_threshold():
    """Get current threshold from database"""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute('SELECT threshold FROM settings WHERE id = 1')
    row = cursor.fetchone()
    conn.close()
    return row["threshold"] if row else 31.0


# Receive sensor data from Arduino
@app.post("/api/sensors")
def post_sensor_data(data: SensorData):
    """Receive sensor data from Arduino and check for alerts"""
    threshold = get_current_threshold()
    alert_triggered = data.temperature > threshold
    
    conn = get_db_connection()
    cursor = conn.cursor()
    
    # Insert sensor reading
    cursor.execute('''
        INSERT INTO sensor_readings (temperature, humidity)
        VALUES (?, ?)
    ''', (data.temperature, data.humidity))
    
    reading_id = cursor.lastrowid
    
    # If alert triggered, log it
    if alert_triggered:
        cursor.execute('''
            INSERT INTO alerts (temperature, threshold)
            VALUES (?, ?)
        ''', (data.temperature, threshold))
    
    conn.commit()
    conn.close()
    
    return {
        "success": True,
        "reading_id": reading_id,
        "alert": alert_triggered,
        "threshold": threshold,
        "message": "Alert triggered - buzzer and LED activated" if alert_triggered else "Reading saved"
    }


# Get sensor history
@app.get("/api/history")
def get_history(limit: int = Query(60, ge=1, le=1000)):
    """Get historical sensor readings"""
    conn = get_db_connection()
    cursor = conn.cursor()
    
    cursor.execute('''
        SELECT id, temperature, humidity, timestamp 
        FROM sensor_readings 
        ORDER BY timestamp DESC 
        LIMIT ?
    ''', (limit,))
    
    rows = cursor.fetchall()
    conn.close()
    
    history = [
        {
            "id": row["id"],
            "temperature": row["temperature"],
            "humidity": row["humidity"],
            "timestamp": row["timestamp"]
        }
        for row in rows
    ]
    
    return {"history": history, "count": len(history)}


# Get settings
@app.get("/api/settings")
def get_settings():
    """Get current threshold settings"""
    conn = get_db_connection()
    cursor = conn.cursor()
    
    cursor.execute('SELECT threshold, last_updated FROM settings WHERE id = 1')
    row = cursor.fetchone()
    conn.close()
    
    if row:
        return {
            "threshold": row["threshold"],
            "last_updated": row["last_updated"]
        }
    else:
        return {"threshold": 31.0, "last_updated": None}


# Update threshold
@app.put("/api/settings")
def update_settings(threshold: float = Query(..., ge=20, le=45)):
    """Update temperature threshold"""
    conn = get_db_connection()
    cursor = conn.cursor()
    
    cursor.execute('''
        UPDATE settings 
        SET threshold = ?, last_updated = CURRENT_TIMESTAMP
        WHERE id = 1
    ''', (threshold,))
    
    conn.commit()
    conn.close()
    
    return {
        "success": True,
        "threshold": threshold,
        "message": f"Threshold updated to {threshold}°C"
    }


# Get alerts
@app.get("/api/alerts")
def get_alerts(limit: int = Query(10, ge=1, le=100)):
    """Get recent alerts"""
    conn = get_db_connection()
    cursor = conn.cursor()
    
    cursor.execute('''
        SELECT id, temperature, threshold, timestamp, acknowledged
        FROM alerts
        ORDER BY timestamp DESC
        LIMIT ?
    ''', (limit,))
    
    rows = cursor.fetchall()
    conn.close()
    
    alerts = [
        {
            "id": row["id"],
            "temperature": row["temperature"],
            "threshold": row["threshold"],
            "timestamp": row["timestamp"],
            "acknowledged": bool(row["acknowledged"])
        }
        for row in rows
    ]
    
    return {"alerts": alerts, "count": len(alerts)}


# Acknowledge alert
@app.post("/api/alerts/{alert_id}/acknowledge")
def acknowledge_alert(alert_id: int):
    """Acknowledge an alert"""
    conn = get_db_connection()
    cursor = conn.cursor()
    
    cursor.execute('UPDATE alerts SET acknowledged = 1 WHERE id = ?', (alert_id,))
    
    if cursor.rowcount == 0:
        conn.close()
        raise HTTPException(status_code=404, detail="Alert not found")
    
    conn.commit()
    conn.close()
    
    return {"success": True, "message": f"Alert {alert_id} acknowledged"}


# Get statistics
@app.get("/api/stats")
def get_stats():
    """Get sensor statistics"""
    conn = get_db_connection()
    cursor = conn.cursor()
    
    # Get count of readings
    cursor.execute('SELECT COUNT(*) as count FROM sensor_readings')
    readings_count = cursor.fetchone()["count"]
    
    # Get alert count
    cursor.execute('SELECT COUNT(*) as count FROM alerts')
    alerts_count = cursor.fetchone()["count"]
    
    # Get max/min temperature
    cursor.execute('SELECT MAX(temperature) as max_temp, MIN(temperature) as min_temp FROM sensor_readings')
    temp_stats = cursor.fetchone()
    
    conn.close()
    
    return {
        "total_readings": readings_count,
        "total_alerts": alerts_count,
        "max_temperature": temp_stats["max_temp"],
        "min_temperature": temp_stats["min_temp"],
        "threshold": get_current_threshold()
    }


# Clear all data (for testing/reset)
@app.delete("/api/data")
def clear_data():
    """Clear all sensor readings and alerts"""
    conn = get_db_connection()
    cursor = conn.cursor()
    
    cursor.execute('DELETE FROM sensor_readings')
    cursor.execute('DELETE FROM alerts')
    
    conn.commit()
    conn.close()
    
    return {"success": True, "message": "All data cleared"}


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
