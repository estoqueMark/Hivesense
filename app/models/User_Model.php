<?php
require_once __DIR__ . "/Base_Model.php";

class User_Model extends Base_Model {

    public function __construct() {
        parent::__construct();
    }

    // ── Auth ──────────────────────────────────────────────────

    public function findByUsernameOrEmail(string $login): ?array {
        $stmt = $this->connection->prepare(
            'SELECT * FROM hs_users
             WHERE (username = ? OR email = ?) AND is_active = 1
             LIMIT 1'
        );
        $stmt->bind_param("ss", $login, $login);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function attemptLogin(string $login, string $password): ?array {
        $user = $this->findByUsernameOrEmail($login);
        if (!$user) return null;
        if (!password_verify($password, $user['password_hash'])) return null;
        $this->updateLastLogin((int)$user['user_id']);
        return $user;
    }

    public function updateLastLogin(int $userId): void {
        $stmt = $this->connection->prepare(
            'UPDATE hs_users SET last_login_at = NOW() WHERE user_id = ?'
        );
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }

    // ── User Management ───────────────────────────────────────

    public function getAllUsers(): array {
        $result = $this->connection->query(
            'SELECT user_id, username, email, full_name, role, is_active, last_login_at, created_at
             FROM hs_users ORDER BY created_at DESC'
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getUserById(int $id): ?array {
        $stmt = $this->connection->prepare(
            'SELECT user_id, username, email, full_name, role, is_active
             FROM hs_users WHERE user_id = ? LIMIT 1'
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function createUser(string $username, string $email, string $password,
                               string $fullName, string $role = 'viewer'): int {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->connection->prepare(
            'INSERT INTO hs_users (username, email, password_hash, full_name, role)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param("sssss", $username, $email, $hash, $fullName, $role);
        $stmt->execute();
        return $this->connection->insert_id;
    }

    public function updateUser(int $id, string $username, string $email,
                               string $fullName, string $role): bool {
        $stmt = $this->connection->prepare(
            'UPDATE hs_users SET username = ?, email = ?, full_name = ?, role = ?
             WHERE user_id = ?'
        );
        $stmt->bind_param("ssssi", $username, $email, $fullName, $role, $id);
        return $stmt->execute();
    }

    public function updatePassword(int $id, string $newPassword): bool {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->connection->prepare(
            'UPDATE hs_users SET password_hash = ? WHERE user_id = ?'
        );
        $stmt->bind_param("si", $hash, $id);
        return $stmt->execute();
    }

    public function toggleActive(int $id): bool {
        $stmt = $this->connection->prepare(
            'UPDATE hs_users SET is_active = IF(is_active=1,0,1) WHERE user_id = ?'
        );
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function deleteUser(int $id): bool {
        $stmt = $this->connection->prepare('DELETE FROM hs_users WHERE user_id = ?');
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function usernameExists(string $username, int $excludeId = 0): bool {
        $stmt = $this->connection->prepare(
            'SELECT 1 FROM hs_users WHERE username = ? AND user_id != ? LIMIT 1'
        );
        $stmt->bind_param("si", $username, $excludeId);
        $stmt->execute();
        return (bool)$stmt->get_result()->fetch_assoc();
    }

    public function emailExists(string $email, int $excludeId = 0): bool {
        $stmt = $this->connection->prepare(
            'SELECT 1 FROM hs_users WHERE email = ? AND user_id != ? LIMIT 1'
        );
        $stmt->bind_param("si", $email, $excludeId);
        $stmt->execute();
        return (bool)$stmt->get_result()->fetch_assoc();
    }
}
?>
