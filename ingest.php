<?php
/**
 * Water Quality Data Ingestion API
 * File: api/ingest.php
 * 
 * Receives JSON data from ESP32 and stores in database
 * POST: http://your-ip/water_monitoring/api/ingest.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../includes/db.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

// Get JSON payload from ESP32
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Log received data for debugging
error_log("Received ESP32 data: " . $json);

if (!$data || !is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
    exit;
}

// Validate required field: sensorId
$sensorId = $data['sensorId'] ?? null;
if (!$sensorId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required field: sensorId']);
    exit;
}

// Get station_id from sensor_id (device_sensor_id in refilling_stations table)
$stmt = $conn->prepare("SELECT station_id FROM refilling_stations WHERE device_sensor_id = ? LIMIT 1");
$stmt->bind_param('s', $sensorId);
$stmt->execute();
$result = $stmt->get_result();
$station = $result->fetch_assoc();
$stmt->close();

if (!$station) {
    http_response_code(404);
    echo json_encode([
        'success' => false, 
        'message' => 'Station not found for sensor ID: ' . $sensorId,
        'hint' => 'Please add this sensor to refilling_stations table first'
    ]);
    exit;
}

$station_id = $station['station_id'];

// Extract and sanitize sensor values
$tds_val = isset($data['tds_val']) ? (float)$data['tds_val'] : null;
$ph_val = isset($data['ph_val']) ? (float)$data['ph_val'] : null;
$turbidity_val = isset($data['turbidity_val']) ? (float)$data['turbidity_val'] : null;
$lead_val = isset($data['lead_val']) ? (float)$data['lead_val'] : null;
$color_val = isset($data['color_val']) ? (float)$data['color_val'] : null;

$tds_status = $data['tds_status'] ?? 'Unknown';
$ph_status = $data['ph_status'] ?? 'Unknown';
$turbidity_status = $data['turbidity_status'] ?? 'Unknown';
$lead_status = $data['lead_status'] ?? 'Unknown';
$color_status = $data['color_status'] ?? 'Unknown';
$color_result = $data['color_result'] ?? 'Unknown';

// Insert data into water_data table
$stmt = $conn->prepare("
    INSERT INTO water_data 
    (station_id, sensor_id, tds_value, tds_status, ph_value, ph_status, 
     turbidity_value, turbidity_status, lead_value, lead_status, 
     color_value, color_status, color_result, timestamp)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
    exit;
}

$stmt->bind_param(
    'isdsdsdsdsdss',
    $station_id,
    $sensorId,
    $tds_val,
    $tds_status,
    $ph_val,
    $ph_status,
    $turbidity_val,
    $turbidity_status,
    $lead_val,
    $lead_status,
    $color_val,
    $color_status,
    $color_result
);

if ($stmt->execute()) {
    $waterdata_id = $stmt->insert_id;
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Data saved successfully',
        'waterdata_id' => $waterdata_id,
        'station_id' => $station_id,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database insert error: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
