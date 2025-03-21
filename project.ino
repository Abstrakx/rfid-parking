#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <MFRC522.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <WebServer.h>
#include <ESPmDNS.h>
#include <ESP32Servo.h>

// Pin configuration for MFRC522 RFID
#define RST_PIN  2
#define SS_PIN   5
MFRC522 mfrc522(SS_PIN, RST_PIN);

// LCD setup (Address 0x27 is commonly used for 16x2 LCD with I2C)
LiquidCrystal_I2C lcd(0x27, 16, 2);

// Servo & Buzzer configuration
Servo servo;  
 

int servoPin = 32;  
int buzzerPin = 12;  

// WiFi configuration
const char* ssid = "Xiaomi Mi 6";
const char* password = "00000000";

// Server URL for the PHP script
String serverURL = "http://192.168.231.200/parking/log_rfid.php";
String registerURL = "http://192.168.231.200/parking/simpan_rfid.php";

// Admin credentials
const char* adminUsername = "admin";
const char* adminPassword = "password123";

// Web server on ESP32
WebServer server(80);

// Global variables
String rfid_code = "";
bool displayScanMessage = true;
bool displayRegisterMessage = true;
bool registerMode = false;  // Default mode: RFID scanning
bool adminLoggedIn = false; // Track admin login status

String userName = ""; // To store user name during registration

void setup() {
  Serial.begin(115200);
  Wire.begin();
  lcd.init();
  lcd.backlight();

  // Connect to WiFi
  connectToWiFi();

  // Initialize RFID
  SPI.begin();
  mfrc522.PCD_Init();

  // Initialize Servo & Buzzer
  servo.attach(servoPin);
  pinMode(buzzerPin, OUTPUT);

  // Set up mDNS responder
  if (!MDNS.begin("rfidsystem")) {
    Serial.println("Error setting up MDNS responder!");
  } else {
    Serial.println("mDNS responder started");
  }

  // Start ESP32 web server
  setupWebServer();
  server.begin();
  Serial.println("HTTP server started");

  servo.write(90);
}

void loop() {
  server.handleClient();  // Handle web server requests

  if (registerMode) {
    if (displayRegisterMessage) {
      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print("Register Mode");
      lcd.setCursor(0, 1);
      lcd.print("Scan RFID");
      displayRegisterMessage = false;
    }
  } else {
    if (displayScanMessage) {
      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print("Scan RFID Card");
      displayScanMessage = false;
    }
  }

  // Look for new RFID card
  if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) {
    rfid_code = "";
    for (byte i = 0; i < mfrc522.uid.size; i++) {
      rfid_code += String(mfrc522.uid.uidByte[i], HEX);
    }
    rfid_code.toUpperCase();

    if (registerMode) {
      // Save to database
      registerRFID(userName, rfid_code);
      registerMode = false; // Exit registration mode
    } else {
      // Normal mode
      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print("RFID Detected");
      lcd.setCursor(0, 1);
      lcd.print("ID: " + rfid_code);
      Serial.println("RFID Detected: " + rfid_code);
      delay(1000);
      checkUserStatus(rfid_code);
    }
    delay(1000);
  }
}

void connectToWiFi() {
  Serial.print("Connecting to WiFi...");
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Connecting to WiFi");
  WiFi.begin(ssid, password);
  int dots = 0;
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
    lcd.setCursor(dots % 16, 1);
    lcd.print(".");
    dots++;
  }
  Serial.println("WiFi Connected!");
  Serial.println(WiFi.localIP());

  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("WiFi Connected");
  lcd.setCursor(0, 1);
  lcd.print(WiFi.localIP());
  delay(2000);
}

void checkUserStatus(String rfid) {
  if (WiFi.status() != WL_CONNECTED) {
    reconnectWiFi();
  }
  
  HTTPClient http;
  String url = serverURL + "?rfid=" + rfid;

  Serial.println("Sending HTTP GET request: " + url);
  http.begin(url);
  int httpCode = http.GET();
  
  if (httpCode > 0) {
    String payload = http.getString();
    Serial.println("Response: " + payload);
    
    if (payload == "allowed") {
      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print("Access Granted");
      lcd.setCursor(0, 1);
      lcd.print("Thank You!");
      Serial.println("Access Granted");
      
      servo.write(0);
      digitalWrite(buzzerPin, HIGH);

      delay(2000);

      servo.write(90);
      digitalWrite(buzzerPin, LOW);

    } else {
      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print("Access Denied");
      lcd.setCursor(0, 1);
      lcd.print("Unauthorized");
      Serial.println("Access Denied");
    }
    displayScanMessage = true;
  } else {
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("Server Error");
    lcd.setCursor(0, 1);
    lcd.print("Try Again!");
    Serial.println("Error contacting server.");
  }
  http.end();
}

void registerRFID(String name, String rfid) {
  if (WiFi.status() != WL_CONNECTED) {
    reconnectWiFi();
  }
  
  HTTPClient http;
  http.begin(registerURL);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  String postData = "nama=" + name + "&rfid=" + rfid;
  int httpCode = http.POST(postData);

  if (httpCode > 0) {
    String payload = http.getString();
    Serial.println("Register Response: " + payload);
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print(payload == "success" ? "User Registered" : "Error Registering");
    lcd.setCursor(0, 1);
    lcd.print(payload == "success" ? "Successfully" : "Try Again");
    
    registerMode = false;
    digitalWrite(buzzerPin, HIGH);
    delay(2000);
    digitalWrite(buzzerPin, LOW);
    
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("Scan RFID Card");
  } else {
    Serial.println("Error sending data.");
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("Server Error");
    lcd.setCursor(0, 1);
    lcd.print("Try Again!");
  }
  http.end();
}

void reconnectWiFi() {
  Serial.println("Reconnecting to WiFi...");
  WiFi.disconnect();
  delay(1000);
  WiFi.begin(ssid, password);
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);
    Serial.print(".");
    attempts++;
  }
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("WiFi Reconnected!");
  } else {
    Serial.println("Failed to reconnect WiFi");
  }
}

void setupWebServer() {
  // Root page - Login form
  server.on("/", []() {
    if (adminLoggedIn) {
      sendRegistrationPage();
    } else {
      sendLoginPage();
    }
  });

  // Login handler
  server.on("/login", HTTP_POST, []() {
    String username = server.arg("username");
    String password = server.arg("password");
    
    if (username == adminUsername && password == adminPassword) {
      adminLoggedIn = true;
      sendRegistrationPage();
    } else {
      server.send(200, "text/html", getHTML("Login Failed", "<p>Invalid username or password</p><a href='/'>Try Again</a>"));
    }
  });

  // Registration handler
  server.on("/register", HTTP_POST, []() {
    if (!adminLoggedIn) {
      server.send(403, "text/html", getHTML("Access Denied", "<p>Please login first</p><a href='/'>Login</a>"));
      return;
    }
    
    if (server.hasArg("name")) {
      userName = server.arg("name");
      registerMode = true;
      server.send(200, "text/html", getHTML("Scan RFID", "<p>Please scan your RFID card now...</p><div id='status'>Waiting for scan...</div><script>function checkStatus(){fetch('/status').then(response=>response.json()).then(data=>{if(data.registered){document.getElementById('status').innerHTML='<p class=\"success\">Registration successful!</p>';setTimeout(()=>{window.location.href='/';},3000);}else{setTimeout(checkStatus,1000);}});} checkStatus();</script>"));
    } else {
      server.send(400, "text/html", getHTML("Error", "<p>Invalid request. Name is required.</p><a href='/'>Try Again</a>"));
    }
  });

  // Status endpoint for AJAX polling
  server.on("/status", HTTP_GET, []() {
    String statusJson = "{\"registered\": " + String(!registerMode) + "}";
    server.send(200, "application/json", statusJson);
  });

  // Logout handler
  server.on("/logout", []() {
    adminLoggedIn = false;
    server.send(200, "text/html", getHTML("Logged Out", "<p>You have been logged out</p><a href='/'>Login Again</a>"));
  });

  // Register for captive portal
  server.onNotFound([]() {
    server.send(404, "text/html", getHTML("404 Not Found", "<p>Page not found</p><a href='/'>Go Home</a>"));
  });
}

void sendLoginPage() {
  String html = getHTML("RFID System Login", 
    "<div class='login-container'>"
    "<h2>Admin Login</h2>"
    "<form action='/login' method='POST'>"
    "<div class='input-group'><label>Username:</label><input type='text' name='username' required></div>"
    "<div class='input-group'><label>Password:</label><input type='password' name='password' required></div>"
    "<button type='submit'>Login</button>"
    "</form>"
    "</div>");
  server.send(200, "text/html", html);
}

void sendRegistrationPage() {
  String html = getHTML("RFID Registration", 
    "<div class='container'>"
    "<h2>Register New RFID User</h2>"
    "<form action='/register' method='POST'>"
    "<div class='input-group'><label>Name:</label><input type='text' name='name' required></div>"
    "<button type='submit'>Register</button>"
    "</form>"
    "<div class='logout'><a href='/logout'>Logout</a></div>"
    "</div>");
  server.send(200, "text/html", html);
}

String getHTML(String title, String content) {
  String html = "<!DOCTYPE html><html><head>";
  html += "<meta name='viewport' content='width=device-width, initial-scale=1'>";
  html += "<title>" + title + "</title>";
  html += "<style>";
  html += "body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }";
  html += ".container, .login-container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }";
  html += "h2 { color: #333; margin-top: 0; text-align: center; }";
  html += ".input-group { margin-bottom: 15px; }";
  html += "label { display: block; margin-bottom: 5px; font-weight: bold; }";
  html += "input[type='text'], input[type='password'] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; }";
  html += "button { background-color: #4CAF50; color: white; border: none; padding: 10px 15px; border-radius: 3px; cursor: pointer; width: 100%; }";
  html += "button:hover { background-color: #45a049; }";
  html += ".logout { text-align: center; margin-top: 20px; }";
  html += ".logout a { color: #ff6b6b; text-decoration: none; }";
  html += ".success { color: #4CAF50; font-weight: bold; }";
  html += ".error { color: #ff6b6b; font-weight: bold; }";
  html += "</style></head><body>";
  html += content;
  html += "</body></html>";
  return html;
}