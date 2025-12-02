-- =====================================================
-- Water Quality Monitoring System - Database Schema
-- For XAMPP/MySQL
-- =====================================================

CREATE DATABASE IF NOT EXISTS water_monitoring;
USE water_monitoring;

-- =====================================================
-- Table: users (optional - for authentication)
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
  user_id INT(11) PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(100),
  full_name VARCHAR(100),
  profile_pic VARCHAR(255) DEFAULT 'https://cdn-icons-png.flaticon.com/512/847/847969.png',
  role ENUM('admin', 'user', 'viewer') DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_login TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, email, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@waterquality.local', 'System Administrator', 'admin');

-- =====================================================
-- Table: refilling_stations
-- Stores information about water testing stations/devices
-- =====================================================
CREATE TABLE IF NOT EXISTS refilling_stations (
  station_id INT(11) PRIMARY KEY AUTO_INCREMENT,
  station_name VARCHAR(255) NOT NULL,
  name VARCHAR(255),  -- Display name (can be same as station_name)
  device_sensor_id VARCHAR(100) NOT NULL UNIQUE,
  location VARCHAR(255),
  address TEXT,
  latitude DECIMAL(10, 8),
  longitude DECIMAL(11, 8),
  status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
  installation_date DATE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample station
INSERT INTO refilling_stations (station_name, name, device_sensor_id, location, status) VALUES
('Test Station 1', 'Main Lab Station', 'ISUIT-WQTAMS-0001', 'Laboratory Building - Room 101', 'active'),
('Test Station 2', 'Field Station', 'ISUIT-WQTAMS-0002', 'Field Site - Area A', 'active');

-- =====================================================
-- Table: water_data
-- Stores all water quality test results from ESP32
-- =====================================================
CREATE TABLE IF NOT EXISTS water_data (
  waterdata_id INT(11) PRIMARY KEY AUTO_INCREMENT,
  station_id INT(11) NOT NULL,
  sensor_id VARCHAR(100) NOT NULL,
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  -- TDS (Total Dissolved Solids)
  tds_value FLOAT,
  tds_status VARCHAR(20),

  -- pH Level
  ph_value FLOAT,
  ph_status VARCHAR(20),

  -- Turbidity
  turbidity_value FLOAT,
  turbidity_status VARCHAR(20),

  -- Lead Content
  lead_value FLOAT,
  lead_status VARCHAR(20),

  -- Color Analysis
  color_value FLOAT,
  color_status VARCHAR(20),
  color_result VARCHAR(20),

  -- Additional metadata
  test_duration_seconds INT,
  notes TEXT,

  FOREIGN KEY (station_id) REFERENCES refilling_stations(station_id) ON DELETE CASCADE,
  INDEX idx_station_timestamp (station_id, timestamp),
  INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Table: station_commands
-- Queue for remote commands (e.g., START_TEST from dashboard)
-- =====================================================
CREATE TABLE IF NOT EXISTS station_commands (
  id INT(11) PRIMARY KEY AUTO_INCREMENT,
  station_id INT(11) NOT NULL,
  command VARCHAR(50) NOT NULL,
  parameters JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  executed_at TIMESTAMP NULL DEFAULT NULL,
  status ENUM('pending', 'executed', 'failed', 'cancelled') DEFAULT 'pending',
  response TEXT,

  FOREIGN KEY (station_id) REFERENCES refilling_stations(station_id) ON DELETE CASCADE,
  INDEX idx_station_pending (station_id, executed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Table: station_autotest_settings
-- Configuration for automatic/scheduled testing
-- =====================================================
CREATE TABLE IF NOT EXISTS station_autotest_settings (
  id INT(11) PRIMARY KEY AUTO_INCREMENT,
  station_id INT(11) UNIQUE NOT NULL,
  mode VARCHAR(20) DEFAULT 'hourly',  -- 'hourly', 'daily', 'monthly'
  interval_hours INT(11),
  interval_days INT(11),
  interval_months INT(11),
  day_of_month INT(11),
  time_of_day TIME,
  enabled TINYINT(1) DEFAULT 0,
  last_run TIMESTAMP NULL,
  next_run TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (station_id) REFERENCES refilling_stations(station_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Table: system_logs
-- Optional: Track system events and errors
-- =====================================================
CREATE TABLE IF NOT EXISTS system_logs (
  log_id INT(11) PRIMARY KEY AUTO_INCREMENT,
  log_type ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
  station_id INT(11),
  message TEXT NOT NULL,
  details JSON,
  ip_address VARCHAR(45),
  user_id INT(11),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (station_id) REFERENCES refilling_stations(station_id) ON DELETE SET NULL,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
  INDEX idx_created_at (created_at),
  INDEX idx_log_type (log_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Table: alerts
-- Store water quality alerts/notifications
-- =====================================================
CREATE TABLE IF NOT EXISTS alerts (
  alert_id INT(11) PRIMARY KEY AUTO_INCREMENT,
  station_id INT(11) NOT NULL,
  waterdata_id INT(11),
  alert_type ENUM('warning', 'critical', 'info') DEFAULT 'info',
  parameter VARCHAR(50),  -- 'tds', 'ph', 'turbidity', 'lead', 'color'
  value FLOAT,
  threshold FLOAT,
  message TEXT,
  acknowledged TINYINT(1) DEFAULT 0,
  acknowledged_by INT(11),
  acknowledged_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (station_id) REFERENCES refilling_stations(station_id) ON DELETE CASCADE,
  FOREIGN KEY (waterdata_id) REFERENCES water_data(waterdata_id) ON DELETE SET NULL,
  FOREIGN KEY (acknowledged_by) REFERENCES users(user_id) ON DELETE SET NULL,
  INDEX idx_station_unack (station_id, acknowledged)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Views for easier querying
-- =====================================================

-- Latest reading per station
CREATE OR REPLACE VIEW latest_readings AS
SELECT 
    w.*,
    r.station_name,
    r.location,
    r.device_sensor_id
FROM water_data w
INNER JOIN refilling_stations r ON w.station_id = r.station_id
INNER JOIN (
    SELECT station_id, MAX(timestamp) as max_timestamp
    FROM water_data
    GROUP BY station_id
) latest ON w.station_id = latest.station_id AND w.timestamp = latest.max_timestamp;

-- Station status summary
CREATE OR REPLACE VIEW station_status AS
SELECT 
    r.station_id,
    r.station_name,
    r.location,
    r.device_sensor_id,
    r.status as station_status,
    COUNT(w.waterdata_id) as total_tests,
    MAX(w.timestamp) as last_test_time,
    CASE 
        WHEN MAX(w.timestamp) > DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 'online'
        WHEN MAX(w.timestamp) > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 'idle'
        ELSE 'offline'
    END as connection_status
FROM refilling_stations r
LEFT JOIN water_data w ON r.station_id = w.station_id
GROUP BY r.station_id;

-- =====================================================
-- Sample data for testing (optional)
-- =====================================================

-- Insert sample water quality data
INSERT INTO water_data (station_id, sensor_id, tds_value, tds_status, ph_value, ph_status, 
                        turbidity_value, turbidity_status, lead_value, lead_status, 
                        color_value, color_status, color_result) VALUES
(1, 'ISUIT-WQTAMS-0001', 8.5, 'Safe', 6.8, 'Neutral', 2.3, 'Safe', 0.008, 'Safe', 7.5, 'Safe', 'Clear'),
(1, 'ISUIT-WQTAMS-0001', 9.2, 'Safe', 6.5, 'Safe', 3.8, 'Neutral', 0.009, 'Neutral', 9.5, 'Neutral', 'Clear'),
(1, 'ISUIT-WQTAMS-0001', 10.5, 'Warning', 7.0, 'Warning', 5.2, 'Failed', 0.011, 'Failed', 11.0, 'Failed', 'Cloudy');

-- =====================================================
-- Stored Procedures (optional helpers)
-- =====================================================

DELIMITER $$

-- Get latest reading for a station
CREATE PROCEDURE IF NOT EXISTS get_latest_reading(IN p_station_id INT)
BEGIN
    SELECT * FROM water_data 
    WHERE station_id = p_station_id 
    ORDER BY timestamp DESC 
    LIMIT 1;
END$$

-- Queue a test command
CREATE PROCEDURE IF NOT EXISTS queue_test_command(IN p_station_id INT, IN p_command VARCHAR(50))
BEGIN
    INSERT INTO station_commands (station_id, command, status) 
    VALUES (p_station_id, p_command, 'pending');
    SELECT LAST_INSERT_ID() as command_id;
END$$

DELIMITER ;

-- =====================================================
-- Database ready!
-- =====================================================
SELECT 'Database setup complete!' as status;
