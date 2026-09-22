<?php
require_once __DIR__ . "/Base_Model.php";

class Measurement_Model extends Base_Model {

    public function __construct(){
        parent::__construct();
    }

    // Get latest reading — now scoped to a specific hive
    public function getLatest(?int $sensorId = null): ?array {
        if ($sensorId) {
            $stmt = $this->connection->prepare(
                'SELECT temperature, humidity, co2, food_level,
                        DATE(timestamp) AS measurement_date,
                        TIME(timestamp) AS measurement_time
                 FROM hs_readings
                 WHERE sensor_id = ?
                 ORDER BY timestamp DESC
                 LIMIT 1'
            );
            $stmt->bind_param("i", $sensorId);
        } else {
            $stmt = $this->connection->prepare(
                'SELECT temperature, humidity, co2, food_level,
                        DATE(timestamp) AS measurement_date,
                        TIME(timestamp) AS measurement_time
                 FROM hs_readings
                 WHERE sensor_id IS NULL
                 ORDER BY timestamp DESC
                 LIMIT 1'
            );
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc() ?: null;
    }

    // Get individual raw readings — now scoped to a specific hive
    public function getReadings(int $hours = 24, int $limit = 500, ?int $sensorId = null): array {
        if ($limit <= 0) {
            if      ($hours <= 1)   $limit = 120;
            else if ($hours <= 6)   $limit = 720;
            else if ($hours <= 24)  $limit = 1000;
            else if ($hours <= 72)  $limit = 1500;
            else                    $limit = 2000;
        }

        if ($sensorId) {
            $stmt = $this->connection->prepare(
                'SELECT ROUND(temperature, 1) AS temperature,
                        ROUND(humidity, 1)    AS humidity,
                        ROUND(co2, 1)         AS co2,
                        ROUND(food_level, 1)  AS food_level,
                        DATE(timestamp)       AS measurement_date,
                        TIME(timestamp)       AS measurement_time,
                        timestamp
                 FROM hs_readings
                 WHERE timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                   AND sensor_id = ?
                 ORDER BY timestamp ASC
                 LIMIT ?'
            );
            $stmt->bind_param("iii", $hours, $sensorId, $limit);
        } else {
            $stmt = $this->connection->prepare(
                'SELECT ROUND(temperature, 1) AS temperature,
                        ROUND(humidity, 1)    AS humidity,
                        ROUND(co2, 1)         AS co2,
                        ROUND(food_level, 1)  AS food_level,
                        DATE(timestamp)       AS measurement_date,
                        TIME(timestamp)       AS measurement_time,
                        timestamp
                 FROM hs_readings
                 WHERE timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                   AND sensor_id IS NULL
                 ORDER BY timestamp ASC
                 LIMIT ?'
            );
            $stmt->bind_param("ii", $hours, $limit);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Get daily statistics — now scoped to a specific hive
    public function getDailyStats(int $limit = 30, ?int $sensorId = null): array {
        if ($sensorId) {
            $stmt = $this->connection->prepare(
                'SELECT DATE(timestamp) AS measurement_date,
                        ROUND(AVG(temperature), 1) AS avg_temperature,
                        ROUND(AVG(humidity), 1)    AS avg_humidity,
                        ROUND(AVG(co2), 1)         AS avg_co2,
                        ROUND(MAX(temperature), 1) AS max_temperature,
                        ROUND(MIN(temperature), 1) AS min_temperature,
                        ROUND(MAX(co2), 1)         AS max_co2,
                        ROUND(MIN(co2), 1)         AS min_co2,
                        ROUND(AVG(food_level), 1)  AS avg_food_level,
                        ROUND(MIN(food_level), 1)  AS min_food_level,
                        COUNT(*) AS reading_count
                 FROM hs_readings
                 WHERE sensor_id = ?
                 GROUP BY DATE(timestamp)
                 ORDER BY measurement_date DESC
                 LIMIT ?'
            );
            $stmt->bind_param("ii", $sensorId, $limit);
        } else {
            $stmt = $this->connection->prepare(
                'SELECT DATE(timestamp) AS measurement_date,
                        ROUND(AVG(temperature), 1) AS avg_temperature,
                        ROUND(AVG(humidity), 1)    AS avg_humidity,
                        ROUND(AVG(co2), 1)         AS avg_co2,
                        ROUND(MAX(temperature), 1) AS max_temperature,
                        ROUND(MIN(temperature), 1) AS min_temperature,
                        ROUND(MAX(co2), 1)         AS max_co2,
                        ROUND(MIN(co2), 1)         AS min_co2,
                        ROUND(AVG(food_level), 1)  AS avg_food_level,
                        ROUND(MIN(food_level), 1)  AS min_food_level,
                        COUNT(*) AS reading_count
                 FROM hs_readings
                 WHERE sensor_id IS NULL
                 GROUP BY DATE(timestamp)
                 ORDER BY measurement_date DESC
                 LIMIT ?'
            );
            $stmt->bind_param("i", $limit);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Get history for chart — now scoped to a specific hive
    public function getHistory(int $days = 7, ?int $sensorId = null): array {
        if ($sensorId) {
            $stmt = $this->connection->prepare(
                'SELECT DATE(timestamp) AS measurement_date,
                        ROUND(AVG(temperature), 1) AS avg_temp,
                        ROUND(AVG(humidity), 1)    AS avg_humidity,
                        ROUND(AVG(co2), 1)         AS avg_co2,
                        ROUND(AVG(food_level), 1)  AS avg_food_level
                 FROM hs_readings
                 WHERE timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)
                   AND sensor_id = ?
                 GROUP BY DATE(timestamp)
                 ORDER BY measurement_date ASC'
            );
            $stmt->bind_param("ii", $days, $sensorId);
        } else {
            $stmt = $this->connection->prepare(
                'SELECT DATE(timestamp) AS measurement_date,
                        ROUND(AVG(temperature), 1) AS avg_temp,
                        ROUND(AVG(humidity), 1)    AS avg_humidity,
                        ROUND(AVG(co2), 1)         AS avg_co2,
                        ROUND(AVG(food_level), 1)  AS avg_food_level
                 FROM hs_readings
                 WHERE timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)
                   AND sensor_id IS NULL
                 GROUP BY DATE(timestamp)
                 ORDER BY measurement_date ASC'
            );
            $stmt->bind_param("i", $days);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Get today's min/max — now scoped to a specific hive
    public function getTodayMinMax(?int $sensorId = null): ?array {
        if ($sensorId) {
            $stmt = $this->connection->prepare(
                'SELECT ROUND(MIN(temperature), 1) AS min_temperature,
                        ROUND(MAX(temperature), 1) AS max_temperature,
                        ROUND(MIN(co2), 1)         AS min_co2,
                        ROUND(MAX(co2), 1)         AS max_co2,
                        ROUND(MIN(food_level), 1)  AS min_food_level,
                        ROUND(MAX(food_level), 1)  AS max_food_level
                 FROM hs_readings
                 WHERE DATE(timestamp) = CURDATE()
                   AND sensor_id = ?'
            );
            $stmt->bind_param("i", $sensorId);
        } else {
            $stmt = $this->connection->prepare(
                'SELECT ROUND(MIN(temperature), 1) AS min_temperature,
                        ROUND(MAX(temperature), 1) AS max_temperature,
                        ROUND(MIN(co2), 1)         AS min_co2,
                        ROUND(MAX(co2), 1)         AS max_co2,
                        ROUND(MIN(food_level), 1)  AS min_food_level,
                        ROUND(MAX(food_level), 1)  AS max_food_level
                 FROM hs_readings
                 WHERE DATE(timestamp) = CURDATE()
                   AND sensor_id IS NULL'
            );
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Get total count — unchanged, global stat, not hive-specific
    public function getTotalCount(): int {
        $result = $this->connection->query('SELECT COUNT(*) as count FROM hs_readings');
        $row = $result->fetch_assoc();
        return $row['count'];
    }
}
?>