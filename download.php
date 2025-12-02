<?php
/**
 * Download/View Water Quality Test Results
 * File: pages/download.php
 */

session_start();
require_once '../includes/db.php';

$station_id = isset($_GET['station_id']) ? (int)$_GET['station_id'] : 0;
$test_id = isset($_GET['test_id']) ? (int)$_GET['test_id'] : 0;
$download = isset($_GET['download']) && $_GET['download'] == '1';

if (!$station_id || !$test_id) {
    die("Error: Missing parameters");
}

// Fetch test data
$stmt = $conn->prepare("
    SELECT w.*, r.station_name, r.location, r.device_sensor_id
    FROM water_data w
    INNER JOIN refilling_stations r ON w.station_id = r.station_id
    WHERE w.station_id = ? AND w.waterdata_id = ?
    LIMIT 1
");

$stmt->bind_param('ii', $station_id, $test_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Error: Test data not found");
}

$data = $result->fetch_assoc();
$stmt->close();

// If download mode, generate PDF or CSV
if ($download) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="water_test_' . $test_id . '.csv"');

    echo "Water Quality Test Report\n";
    echo "Station," . $data['station_name'] . "\n";
    echo "Location," . $data['location'] . "\n";
    echo "Sensor ID," . $data['sensor_id'] . "\n";
    echo "Test Date," . $data['timestamp'] . "\n\n";
    echo "Parameter,Value,Status\n";
    echo "TDS," . $data['tds_value'] . " mg/L," . $data['tds_status'] . "\n";
    echo "pH," . $data['ph_value'] . "," . $data['ph_status'] . "\n";
    echo "Turbidity," . $data['turbidity_value'] . " NTU," . $data['turbidity_status'] . "\n";
    echo "Lead," . $data['lead_value'] . " mg/L," . $data['lead_status'] . "\n";
    echo "Color," . $data['color_value'] . " TCU," . $data['color_status'] . "\n";
    echo "Color Result," . $data['color_result'] . "\n";
    exit;
}

// Display mode - Show HTML report
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Water Quality Test Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #0e1117; color: #fff; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 800px; margin: 50px auto; background: #1f2733; padding: 30px; border-radius: 15px; }
        .header { text-align: center; margin-bottom: 30px; }
        .status-safe { color: #38c172; }
        .status-neutral { color: #ffed4a; }
        .status-warning { color: #ffa000; }
        .status-failed { color: #e3342f; }
        table { width: 100%; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #444; }
        .btn-download { background: #00c6ff; border: none; color: white; padding: 10px 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Water Quality Test Report</h2>
            <p><strong>Test ID:</strong> <?= htmlspecialchars($test_id) ?></p>
        </div>

        <table class="table table-dark">
            <tr>
                <th>Station Name</th>
                <td><?= htmlspecialchars($data['station_name']) ?></td>
            </tr>
            <tr>
                <th>Location</th>
                <td><?= htmlspecialchars($data['location']) ?></td>
            </tr>
            <tr>
                <th>Sensor ID</th>
                <td><?= htmlspecialchars($data['sensor_id']) ?></td>
            </tr>
            <tr>
                <th>Test Date</th>
                <td><?= htmlspecialchars($data['timestamp']) ?></td>
            </tr>
        </table>

        <h4>Test Results</h4>
        <table class="table table-dark">
            <thead>
                <tr>
                    <th>Parameter</th>
                    <th>Value</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>TDS</td>
                    <td><?= number_format($data['tds_value'], 1) ?> mg/L</td>
                    <td class="status-<?= strtolower($data['tds_status']) ?>"><?= htmlspecialchars($data['tds_status']) ?></td>
                </tr>
                <tr>
                    <td>pH</td>
                    <td><?= number_format($data['ph_value'], 2) ?></td>
                    <td class="status-<?= strtolower($data['ph_status']) ?>"><?= htmlspecialchars($data['ph_status']) ?></td>
                </tr>
                <tr>
                    <td>Turbidity</td>
                    <td><?= number_format($data['turbidity_value'], 2) ?> NTU</td>
                    <td class="status-<?= strtolower($data['turbidity_status']) ?>"><?= htmlspecialchars($data['turbidity_status']) ?></td>
                </tr>
                <tr>
                    <td>Lead</td>
                    <td><?= number_format($data['lead_value'], 4) ?> mg/L</td>
                    <td class="status-<?= strtolower($data['lead_status']) ?>"><?= htmlspecialchars($data['lead_status']) ?></td>
                </tr>
                <tr>
                    <td>Color</td>
                    <td><?= number_format($data['color_value'], 1) ?> TCU</td>
                    <td class="status-<?= strtolower($data['color_status']) ?>"><?= htmlspecialchars($data['color_status']) ?></td>
                </tr>
                <tr>
                    <td>Color Result</td>
                    <td colspan="2"><?= htmlspecialchars($data['color_result']) ?></td>
                </tr>
            </tbody>
        </table>

        <div class="text-center mt-4">
            <a href="?station_id=<?= $station_id ?>&test_id=<?= $test_id ?>&download=1" class="btn btn-download">
                Download as CSV
            </a>
            <a href="dashboard.php?station_id=<?= $station_id ?>" class="btn btn-secondary">
                Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
