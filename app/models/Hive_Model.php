<?php
require_once __DIR__ . "/Base_Model.php";

class Hive_Model extends Base_Model {

    public function __construct(){
        parent::__construct();
    }

    public function getAllHives(): array {
        $result = $this->connection->query(
            'SELECT sensor_id, hive_name, location, is_active, created_at
             FROM hs_sensors
             ORDER BY hive_name ASC'
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getActiveHives(): array {
        $result = $this->connection->query(
            'SELECT sensor_id, hive_name, location
             FROM hs_sensors
             WHERE is_active = 1
             ORDER BY hive_name ASC'
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getHiveById(int $id): ?array {
        $stmt = $this->connection->prepare(
            'SELECT sensor_id, hive_name, location, is_active
             FROM hs_sensors
             WHERE sensor_id = ?'
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function createHive(string $hiveName, string $location): int {
        $stmt = $this->connection->prepare(
            'INSERT INTO hs_sensors (hive_name, location, is_active, created_at)
             VALUES (?, ?, 1, NOW())'
        );
        $stmt->bind_param("ss", $hiveName, $location);
        $stmt->execute();
        return $this->connection->insert_id;
    }
    
    public function hiveNameExists(string $hiveName, int $excludeId = 0): bool {
    $stmt = $this->connection->prepare(
        'SELECT 1 FROM hs_sensors WHERE hive_name = ? AND sensor_id != ? LIMIT 1'
    );
    $stmt->bind_param("si", $hiveName, $excludeId);
    $stmt->execute();
    return (bool)$stmt->get_result()->fetch_assoc();
}

    public function updateHive(int $id, string $hiveName, string $location): bool {
        $stmt = $this->connection->prepare(
            'UPDATE hs_sensors
             SET hive_name = ?, location = ?
             WHERE sensor_id = ?'
        );
        $stmt->bind_param("ssi", $hiveName, $location, $id);
        return $stmt->execute();
    }

    public function deleteHive(int $id): bool {
        $stmt = $this->connection->prepare(
            'UPDATE hs_sensors SET is_active = 0 WHERE sensor_id = ?'
        );
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function restoreHive(int $id): bool {
        $stmt = $this->connection->prepare(
            'UPDATE hs_sensors SET is_active = 1 WHERE sensor_id = ?'
        );
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function permanentDeleteHive(int $id): bool {
        $stmt = $this->connection->prepare('DELETE FROM hs_sensors WHERE sensor_id = ?');
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
?>