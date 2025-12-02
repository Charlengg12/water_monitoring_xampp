# Water Quality Monitoring System - XAMPP Setup Guide

## 📋 Overview
Complete XAMPP configuration for ESP32-based water quality monitoring system with real-time dashboard.

## 📁 Folder Structure

```
C:\xampp\htdocs\water_monitoring\
├── includes/
│   ├── db.php                  (Database connection)
│   └── fetch_user.php          (User authentication)
├── pages/
│   └── dashboard.php           (Main dashboard - paste.txt content)
├── api/
│   └── ingest.php              (ESP32 data endpoint)
└── README.md
```

## 🔧 Installation Steps

### 1. Install XAMPP
- Download from: https://www.apachefriends.org/
- Install Apache and MySQL

### 2. Create Database
1. Open **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Import **database.sql** file (included)
3. Database will be created with all tables and sample data

### 3. Setup Files
1. Extract all files to `C:\xampp\htdocs\water_monitoring\`
2. Copy **dashboard.php** from paste.txt to `pages/`
3. Make sure folder structure matches above

### 4. Configure ESP32

#### Find Your PC's IP Address:
```bash
# Windows
ipconfig

# Look for "IPv4 Address" (e.g., 192.168.1.10)
```

#### Update ESP32 Code (esp32_fixed3_UPDATED.ino):

```cpp
// --- WIFI CREDENTIALS ---
#define WIFI_SSID "YOUR_WIFI_NAME"
#define WIFI_PASSWORD "YOUR_PASSWORD"

// --- STATIC IP CONFIGURATION ---
IPAddress staticIP(192, 168, 1, 100);     // ESP32's static IP
IPAddress gateway(192, 168, 1, 1);         // Your router IP
IPAddress subnet(255, 255, 255, 0);

// --- API ENDPOINT ---
const char* apiUrl = "http://YOUR_PC_IP/water_monitoring/api/ingest.php";
```

**Replace:**
- `YOUR_WIFI_NAME` → Your WiFi network name
- `YOUR_PASSWORD` → Your WiFi password  
- `192.168.1.100` → Desired ESP32 IP (must be in your network range)
- `192.168.1.1` → Your router's IP
- `YOUR_PC_IP` → Your computer's IP address (from ipconfig)

### 5. Dashboard Configuration

The dashboard (paste.txt) connects to ESP32 at IP: **192.168.68.250**

To change this, edit **dashboard.php** line 732:
```javascript
const ESP32_IP = "http://192.168.68.250";  // Change to match your ESP32 IP
```

## 🌐 Access URLs

- **Dashboard**: `http://localhost/water_monitoring/pages/dashboard.php?station_id=1`
- **API Endpoint**: `http://YOUR_PC_IP/water_monitoring/api/ingest.php`
- **ESP32 Web Server**: `http://ESP32_IP/readings`

## 🗄️ Database Tables

- `refilling_stations` - Station/device information
- `water_data` - Sensor readings history
- `station_commands` - Remote control queue
- `station_autotest_settings` - Auto-test configuration
- `users` - User authentication
- `system_logs` - System events
- `alerts` - Water quality alerts

## 📊 Default Credentials

**Database:**
- Host: `localhost`
- User: `root`
- Password: *(empty)*
- Database: `water_monitoring`

**Admin User:**
- Username: `admin`
- Password: `admin123`

## 🔬 Testing

### Test ESP32 Connection:
```bash
# Ping ESP32
ping 192.168.68.250

# Check if web server responds
curl http://192.168.68.250/readings
```

### Test Data Upload:
```bash
curl -X POST http://YOUR_PC_IP/water_monitoring/api/ingest.php \
  -H "Content-Type: application/json" \
  -d '{
    "sensorId": "ISUIT-WQTAMS-0001",
    "tds_val": 8.5,
    "ph_val": 6.8,
    "turbidity_val": 2.3,
    "lead_val": 0.008,
    "color_val": 7.5,
    "tds_status": "Safe",
    "ph_status": "Neutral",
    "turbidity_status": "Safe",
    "lead_status": "Safe",
    "color_status": "Safe",
    "color_result": "Clear"
  }'
```

## 🐛 Troubleshooting

### ESP32 Not Connecting to WiFi
- Check WiFi credentials
- Ensure ESP32 is in range
- Verify router allows static IP

### Dashboard Shows "OFFLINE"
- Check ESP32 IP address matches dashboard configuration
- Ensure ESP32 web server is running
- Verify both devices on same network

### Database Connection Error
- Start MySQL in XAMPP Control Panel
- Check db.php credentials
- Verify database exists

### 404 Not Found
- Check file paths match folder structure
- Ensure XAMPP Apache is running
- Verify files in correct directories

## 📱 Features

- ✅ Real-time sensor monitoring (5-second polling)
- ✅ Animated gauge displays
- ✅ Remote test triggering
- ✅ Auto-test scheduling (hourly/daily/monthly)
- ✅ Historical data with date filtering
- ✅ Online/Offline status detection
- ✅ Mobile responsive design

## 📝 Notes

1. **First Time Setup**: After importing database, verify station exists:
   ```sql
   SELECT * FROM refilling_stations WHERE station_id = 1;
   ```

2. **ESP32 Sensor ID**: Must match database:
   ```sql
   UPDATE refilling_stations 
   SET device_sensor_id = 'ISUIT-WQTAMS-0001' 
   WHERE station_id = 1;
   ```

3. **Network Requirements**:
   - ESP32 and PC must be on same local network
   - Firewall must allow incoming connections on port 80
   - Static IP recommended for both devices

## 🔒 Security Recommendations

For production deployment:

1. Change database password
2. Update admin user credentials  
3. Enable HTTPS/SSL
4. Add authentication to API endpoint
5. Implement rate limiting
6. Use environment variables for sensitive data

## 📞 Support

If you encounter issues:
1. Check Apache/MySQL error logs in XAMPP
2. View browser console for JavaScript errors  
3. Monitor ESP32 Serial Monitor output
4. Verify network connectivity with ping

## 📄 License

This project is provided as-is for educational purposes.

---

**Version**: 1.0  
**Date**: December 2025  
**Platform**: XAMPP + ESP32
