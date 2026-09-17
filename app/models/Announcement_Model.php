<?php
require_once __DIR__ . "/Base_Model.php";

class Announcement_Model extends Base_Model {

    public function __construct() {
        parent::__construct();
    }

    /** Get all active announcements — newest first */
    public function getActive(): array {
        $result = $this->connection->query(
            'SELECT a.*, u.full_name AS author
             FROM hs_announcements a
             LEFT JOIN hs_users u ON u.user_id = a.created_by
             WHERE a.is_active = 1
             ORDER BY a.created_at DESC'
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /** Get all announcements for admin management */
    public function getAll(): array {
        $result = $this->connection->query(
            'SELECT a.*, u.full_name AS author
             FROM hs_announcements a
             LEFT JOIN hs_users u ON u.user_id = a.created_by
             ORDER BY a.created_at DESC'
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getById(int $id): ?array {
        $stmt = $this->connection->prepare(
            'SELECT * FROM hs_announcements WHERE id = ? LIMIT 1'
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function create(string $title, string $body, string $type, int $createdBy): int {
        $stmt = $this->connection->prepare(
            'INSERT INTO hs_announcements (title, body, type, is_active, created_by)
             VALUES (?, ?, ?, 1, ?)'
        );
        $stmt->bind_param("sssi", $title, $body, $type, $createdBy);
        $stmt->execute();
        return (int)$this->connection->insert_id;
    }

    public function update(int $id, string $title, string $body, string $type, int $isActive): bool {
        $stmt = $this->connection->prepare(
            'UPDATE hs_announcements SET title = ?, body = ?, type = ?, is_active = ?
             WHERE id = ?'
        );
        $stmt->bind_param("sssii", $title, $body, $type, $isActive, $id);
        return $stmt->execute();
    }

    public function delete(int $id): bool {
        $stmt = $this->connection->prepare('DELETE FROM hs_announcements WHERE id = ?');
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function toggleActive(int $id): bool {
        $stmt = $this->connection->prepare(
            'UPDATE hs_announcements SET is_active = IF(is_active=1,0,1) WHERE id = ?'
        );
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
?>
