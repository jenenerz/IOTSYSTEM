/*
 * Server Room Monitor - Arduino Uno R4 WiFi
 * 
 * Board: Arduino UNO R4 WiFi
 * Libraries: WiFiS3, ArduinoHttpClient, DHT
 * 
 * Hardware Setup:
 * - DHT11 Data -> Pin 2
 * - Red LED -> Pin 13 (built-in LED)
 * - Piezo Buzzer -> Pin 8
 * 
 * Libraries Required (Install from Arduino Library Manager):
 * - WiFiS3 (built-in with UNO R4 WiFi board)
 * - ArduinoHttpClient by Arduino
 * - DHT sensor library by Adafruit
 * - Adafruit Unified Sensor by Adafruit
 */

#include <WiFiS3.h>
#include <ArduinoHttpClient.h>
#include <DHT.h>

// ==================== WiFi CONFIGURATION ====================
// UPDATE THESE WITH YOUR WiFi CREDENTIALS
const char* ssid = "YOUR_WIFI_SSID";           // Replace with your WiFi network name
const char* password = "YOUR_WIFI_PASSWORD";   // Replace with your WiFi password

// ==================== SERVER CONFIGURATION ====================
// UPDATE THIS WITH YOUR COMPUTER'S IP ADDRESS
// To find: open cmd and run "ipconfig" - look for IPv4 Address
// Example: const char* serverAddress = "192.168.1.100";
// const int serverPort = 8000;
const char* serverAddress = "192.168.68.104";
const int serverPort = 8000;

// ==================== HARDWARE PINS ====================
#define DHTPIN 2         // DHT11 data pin
#define DHTTYPE DHT11    // DHT11 sensor type
#define LED_PIN 13       // Red LED (built-in)
#define PIEZO_PIN 8      // Piezo buzzer

// ==================== SETTINGS ====================
float temperatureThreshold = 31.0;  // Alert threshold in °C

// ==================== GLOBAL VARIABLES ====================
DHT dht(DHTPIN, DHTTYPE);
WiFiClient wifiClient;
HttpClient client = HttpClient(wifiClient, serverAddress, serverPort);

float temperature = 0.0;
float humidity = 0.0;
bool alertActive = false;
unsigned long lastUpdate = 0;
const unsigned long updateInterval = 5000;  // Send data every 5 seconds

void setup() {
  Serial.begin(115200);
  delay(1000);
  
  Serial.println("========================================");
  Serial.println("  Server Room Monitor - Uno R4 WiFi   ");
  Serial.println("========================================");
  
  // Initialize pins
  pinMode(LED_PIN, OUTPUT);
  pinMode(PIEZO_PIN, OUTPUT);
  digitalWrite(LED_PIN, LOW);
  digitalWrite(PIEZO_PIN, LOW);
  
  // Initialize DHT sensor
  dht.begin();
  delay(500);
  Serial.println("DHT11 Sensor initialized");
  
  // Connect to WiFi
  connectToWiFi();
  
  Serial.println("\n>>> Setup complete! Starting main loop...");
}

void loop() {
  // Check WiFi connection
  if (WiFi.status() != WL_CONNECTED) {
    connectToWiFi();
  }
  
  // Read DHT11 sensor
  readDHT11();
  
  // Check for alert condition
  checkAlert();
  
  // Send data to server periodically
  if (millis() - lastUpdate >= updateInterval) {
    if (WiFi.status() == WL_CONNECTED) {
      sendSensorData();
    }
    lastUpdate = millis();
  }
  
  delay(100);
}

// ==================== FUNCTIONS ====================

void connectToWiFi() {
  Serial.print("Connecting to WiFi: ");
  Serial.println(ssid);
  
  // Check if WiFi shield is present
  if (WiFi.status() == WL_NO_SHIELD) {
    Serial.println("WiFi shield not found");
    return;
  }
  
  // Attempt connection
  int status = WL_IDLE_STATUS;
  while (status != WL_CONNECTED) {
    status = WiFi.begin(ssid, password);
    Serial.print(".");
    delay(500);
  }
  
  Serial.println("\nWiFi connected!");
  Serial.print("IP Address: ");
  Serial.println(WiFi.localIP());
}

void readDHT11() {
  // Read temperature and humidity
  float t = dht.readTemperature();
  float h = dht.readHumidity();
  
  // Check if reading is valid
  if (isnan(t) || isnan(h)) {
    static unsigned long lastError = 0;
    if (millis() - lastError > 10000) {
      Serial.println("Error: Failed to read from DHT sensor!");
      lastError = millis();
    }
    temperature = 0;
    humidity = 0;
    return;
  }
  
  temperature = t;
  humidity = h;
  
  Serial.print("DHT11 -> Temp: ");
  Serial.print(temperature, 1);
  Serial.print("C, Humidity: ");
  Serial.print(humidity, 1);
  Serial.println("%");
}

void checkAlert() {
  // Check if temperature exceeds threshold
  bool shouldAlert = (temperature > temperatureThreshold && temperature > 0);
  
  if (shouldAlert && !alertActive) {
    alertActive = true;
    digitalWrite(LED_PIN, HIGH);
    Serial.println("ALERT: Temperature exceeds threshold!");
  } else if (!shouldAlert && alertActive) {
    alertActive = false;
    digitalWrite(LED_PIN, LOW);
    noTone(PIEZO_PIN);
    Serial.println("OK: Temperature normalized");
  }
  
  // Make buzzer pulse when alerting
  if (alertActive) {
    static unsigned long lastBuzz = 0;
    if (millis() - lastBuzz >= 400) {
      static bool buzzState = false;
      buzzState = !buzzState;
      if (buzzState) {
        tone(PIEZO_PIN, 1000);  // 1000Hz beep
      } else {
        noTone(PIEZO_PIN);
      }
      lastBuzz = millis();
    }
  } else {
    noTone(PIEZO_PIN);
  }
}

void sendSensorData() {
  // Create JSON payload
  String jsonStr = "{\"temperature\":" + String(temperature, 1) + 
                   ",\"humidity\":" + String(humidity, 1) + "}";
  
  Serial.print("Sending to server: ");
  Serial.println(jsonStr);
  
  // Make POST request
  client.beginRequest();
  client.post("/api/sensors");
  client.sendHeader("Content-Type", "application/json");
  client.sendHeader("Content-Length", jsonStr.length());
  client.print(jsonStr);
  
  int statusCode = client.responseStatusCode();
  String response = client.responseBody();
  
  Serial.print("Status: ");
  Serial.println(statusCode);
  Serial.print("Response: ");
  Serial.println(response);
  
  client.endRequest();
}
