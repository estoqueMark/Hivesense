<?php
require_once __DIR__ . "/../core/Base_Controller.php";

class Ingest extends Base_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'POST only.']);
            exit();
        }

        $body = json_decode(file_get_contents('php://input'), true);

        if (!$body) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON.']);
            exit();
        }
        $apiKey      = trim($body['api_key']  ?? '');
        $sensorIdRaw = $body['sensor_id']    ?? null;
        $temperature = $body['temperature']  ?? null;
        $humidity    = $body['humidity']     ?? null;
        $co2         = $body['co2']          ?? null; 
        $foodLevel   = $body['food_level']   ?? null;  // in development

        if (!$apiKey) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'API key required.']);
            exit();
        }

        if (!isset($body['sensor_id']) || !is_numeric($body['sensor_id']) || (int)$body['sensor_id'] <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'A valid sensor_id is required.']);
            exit();
        }

        if ($temperature === null || $humidity === null) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'temperature and humidity are required.']);
            exit();
        }

        if (!is_numeric($temperature) || !is_numeric($humidity)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'temperature and humidity must be numeric.']);
            exit();
        }

        if ($sensorIdRaw !== null && !is_numeric($sensorIdRaw)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'sensor_id must be numeric.']);
            exit();
        }
        $sensorId = $sensorIdRaw !== null ? (int) $sensorIdRaw : null;

        $temperature = (float) $temperature;
        $humidity    = (float) $humidity;

        if ($temperature < -40 || $temperature > 80) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Temperature out of range: {$temperature}"]);
            exit();
        }

        if ($humidity < 0 || $humidity > 100) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Humidity out of range: {$humidity}"]);
            exit();
        }

        // CO2 is optional — validate only when provided
        $co2Float = null;
        if ($co2 !== null) {
            if (!is_numeric($co2)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'co2 must be numeric.']);
                exit();
            }
            $co2Float = (float) $co2;
            // Typical indoor CO2 range: 300–10000 ppm
            if ($co2Float < 300 || $co2Float > 10000) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "CO2 out of range (300–10000 ppm): {$co2Float}"]);
                exit();
            }
        }

        // Food level is optional — validate only when provided
        $foodFloat = null;
        if ($foodLevel !== null) {
            if (!is_numeric($foodLevel)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'food_level must be numeric.']);
                exit();
            }
            $foodFloat = (float) $foodLevel;
            if ($foodFloat < 0 || $foodFloat > 100) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Food level out of range (0-100): {$foodFloat}"]);
                exit();
            }
        }

        // Load Base_Model
        require_once __DIR__ . "/../models/Base_Model.php";
        $db         = new Base_Model();
        $connection = $db->connection;

        // Verify API key
        $stmt = $connection->prepare('SELECT key_id FROM hs_api_keys WHERE api_key = ? AND is_active = 1 LIMIT 1');
        $stmt->bind_param("s", $apiKey);
        $stmt->execute();
        $key = $stmt->get_result()->fetch_assoc();

        if (!$key) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid or inactive API key.']);
            exit();
        }

        // Verify sensor_id exists (if provided) — avoids orphaned readings for a
        // typo'd or deleted hive
        if ($sensorId !== null) {
            $stmt = $connection->prepare('SELECT sensor_id FROM hs_sensors WHERE sensor_id = ? LIMIT 1');
            $stmt->bind_param("i", $sensorId);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Unknown sensor_id: {$sensorId}"]);
                exit();
            }
        }

        // Insert reading
        $stmt = $connection->prepare(
            'INSERT INTO hs_readings (sensor_id, temperature, humidity, co2, food_level, timestamp)
             VALUES (?, ?, ?, ?, ?, NOW())'
        );
        $stmt->bind_param("idddd", $sensorId, $temperature, $humidity, $co2Float, $foodFloat);

        if (!$stmt->execute()) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database insert failed: ' . $stmt->error]);
            exit();
        }

        $readingId = $connection->insert_id;


        // Check thresholds and log/resolve alerts
        require_once __DIR__ . "/../models/Alert_Model.php";
        $alertModel = new Alert_Model();
        $alertModel->evaluateReading($sensorId, $temperature, $humidity, $co2Float, $foodFloat, $readingId);


        // Update last used timestamp
        $stmt = $connection->prepare('UPDATE hs_api_keys SET last_used_at = NOW() WHERE api_key = ?');
        $stmt->bind_param("s", $apiKey);
        $stmt->execute();

        echo json_encode([
            'success'     => true,
            'reading_id'  => $readingId,
            'temperature' => $temperature,
            'humidity'    => $humidity,
            'co2'         => $co2Float,
            'food_level'  => $foodFloat,
            'timestamp'   => date('Y-m-d H:i:s'),
        ]);
    }
}
?>