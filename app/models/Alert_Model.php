<?php
require_once __DIR__ . "/Base_Model.php";

class Alert_Model extends Base_Model {

    // Thresholds — matches your existing dashboard guide cards
    const TEMP_COLD      = 32;
    const TEMP_HOT        = 38;
    const HUM_DRY          = 50;
    const HUM_VERY_HUMID = 85;
    const CO2_HIGH        = 1500;
    const CO2_DANGER      = 3000;
    const FOOD_LOW        = 25;
    const FOOD_CRITICAL   = 0;

    public function __construct() {
        parent::__construct();
    }

    /** Call after every new reading. Returns newly created/escalated alerts. */
    public function evaluateReading(?int $sensorId, float $temperature, float $humidity, ?float $co2, ?float $foodLevel, ?int $readingId): array {
        $triggered = [];

        if ($temperature < self::TEMP_COLD) {
            $triggered[] = ['type' => 'temperature', 'severity' => 'warning', 'value' => $temperature,
                'message' => "Temperature dropped to {$temperature}°C — colony at risk of cold stress."];
        } elseif ($temperature > self::TEMP_HOT) {
            $triggered[] = ['type' => 'temperature', 'severity' => 'critical', 'value' => $temperature,
                'message' => "Temperature spiked to {$temperature}°C — check ventilation."];
        }

        if ($humidity < self::HUM_DRY) {
            $triggered[] = ['type' => 'humidity', 'severity' => 'warning', 'value' => $humidity,
                'message' => "Humidity dropped to {$humidity}% — risk of comb damage."];
        } elseif ($humidity > self::HUM_VERY_HUMID) {
            $triggered[] = ['type' => 'humidity', 'severity' => 'critical', 'value' => $humidity,
                'message' => "Humidity spiked to {$humidity}% — act now to prevent mold."];
        }

        if ($co2 !== null) {
            if ($co2 > self::CO2_DANGER) {
                $triggered[] = ['type' => 'co2', 'severity' => 'critical', 'value' => $co2,
                    'message' => "CO₂ reached {$co2} ppm — critical, act immediately."];
            } elseif ($co2 > self::CO2_HIGH) {
                $triggered[] = ['type' => 'co2', 'severity' => 'warning', 'value' => $co2,
                    'message' => "CO₂ reached {$co2} ppm — check ventilation."];
            }
        }

        if ($foodLevel !== null) {
            if ($foodLevel <= self::FOOD_CRITICAL) {
                $triggered[] = ['type' => 'food', 'severity' => 'critical', 'value' => $foodLevel,
                    'message' => "Food store is empty ({$foodLevel}%) — feed the colony now."];
            } elseif ($foodLevel <= self::FOOD_LOW) {
                $triggered[] = ['type' => 'food', 'severity' => 'warning', 'value' => $foodLevel,
                    'message' => "Food store is low ({$foodLevel}%) — plan feeding soon."];
            }
        }

        $breachedTypes = array_column($triggered, 'type');

        foreach (['temperature', 'humidity', 'co2', 'food'] as $type) {
            if (!in_array($type, $breachedTypes)) {
                $this->resolveActiveAlert($sensorId, $type);
            }
        }

        $newOrEscalated = [];
        foreach ($triggered as $alert) {
            $existing = $this->getActiveAlert($sensorId, $alert['type']);
            if ($existing) {
                if ($existing['severity'] !== $alert['severity']) {
                    $this->updateAlert((int)$existing['alert_id'], $alert['severity'], $alert['value'], $alert['message'], $readingId);
                    $alert['alert_id'] = $existing['alert_id'];
                    $newOrEscalated[] = $alert;
                }
                continue;
            }
            $id = $this->createAlert($sensorId, $alert['type'], $alert['severity'], $alert['value'], $alert['message'], $readingId);
            $alert['alert_id'] = $id;
            $newOrEscalated[] = $alert;
        }

        return $newOrEscalated;
    }

    private function getActiveAlert(?int $sensorId, string $type): ?array {
        $stmt = $this->connection->prepare(
            "SELECT * FROM hs_alerts WHERE alert_type = ? AND status = 'active' AND sensor_id <=> ? LIMIT 1"
        );
        $stmt->bind_param("si", $type, $sensorId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    private function createAlert(?int $sensorId, string $type, string $severity, float $value, string $message, ?int $readingId): int {
        $stmt = $this->connection->prepare(
            "INSERT INTO hs_alerts (sensor_id, alert_type, severity, metric_value, message, reading_id, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())"
        );
        $stmt->bind_param("issdsi", $sensorId, $type, $severity, $value, $message, $readingId);
        $stmt->execute();
        return (int)$this->connection->insert_id;
    }

    private function updateAlert(int $alertId, string $severity, float $value, string $message, ?int $readingId): bool {
        $stmt = $this->connection->prepare(
            "UPDATE hs_alerts SET severity = ?, metric_value = ?, message = ?, reading_id = ?, created_at = NOW(),
             acknowledged_by = NULL, acknowledged_at = NULL
             WHERE alert_id = ?"
        );
        $stmt->bind_param("sdsii", $severity, $value, $message, $readingId, $alertId);
        return $stmt->execute();
    }

    private function resolveActiveAlert(?int $sensorId, string $type): bool {
        $stmt = $this->connection->prepare(
            "UPDATE hs_alerts SET status = 'resolved', resolved_at = NOW()
             WHERE alert_type = ? AND status = 'active' AND sensor_id <=> ?"
        );
        $stmt->bind_param("si", $type, $sensorId);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function getActiveAlerts(?int $sensorId = null): array {
        if ($sensorId) {
            $stmt = $this->connection->prepare(
                "SELECT a.*, s.hive_name FROM hs_alerts a
                 LEFT JOIN hs_sensors s ON s.sensor_id = a.sensor_id
                 WHERE a.status = 'active' AND a.acknowledged_at IS NULL AND a.sensor_id = ?
                 ORDER BY a.created_at DESC"
            );
            $stmt->bind_param("i", $sensorId);
        } else {
            $stmt = $this->connection->prepare(
                "SELECT a.*, s.hive_name FROM hs_alerts a
                 LEFT JOIN hs_sensors s ON s.sensor_id = a.sensor_id
                 WHERE a.status = 'active' AND a.acknowledged_at IS NULL
                 ORDER BY a.created_at DESC"
            );
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Full history — both active and resolved, newest first, optional hive filter + limit */
    public function getAlertHistory(?int $sensorId = null, int $limit = 100): array {
        if ($sensorId) {
            $stmt = $this->connection->prepare(
                "SELECT a.*, s.hive_name FROM hs_alerts a
                 LEFT JOIN hs_sensors s ON s.sensor_id = a.sensor_id
                 WHERE a.sensor_id = ?
                 ORDER BY a.created_at DESC
                 LIMIT ?"
            );
            $stmt->bind_param("ii", $sensorId, $limit);
        } else {
            $stmt = $this->connection->prepare(
                "SELECT a.*, s.hive_name FROM hs_alerts a
                 LEFT JOIN hs_sensors s ON s.sensor_id = a.sensor_id
                 ORDER BY a.created_at DESC
                 LIMIT ?"
            );
            $stmt->bind_param("i", $limit);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function acknowledgeAlert(int $alertId, int $userId): bool {
        $stmt = $this->connection->prepare(
            "UPDATE hs_alerts SET acknowledged_by = ?, acknowledged_at = NOW() WHERE alert_id = ?"
        );
        $stmt->bind_param("ii", $userId, $alertId);
        return $stmt->execute();
    }
}
?>