<?php
require_once __DIR__ . "/../core/config.php";

class Base_Model {
    public $connection;

    public function __construct() {
        $this->connection = new mysqli(DBSERVER, DBUSER, DBPASS, DBNAME);
        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
        $this->connection->set_charset("utf8mb4");

        // Match PHP's Asia/Manila timezone so NOW()/CURDATE() in queries
        // agree with PHP's date()/time() calls (e.g. Ingest.php's date('Y-m-d H:i:s')).
        $this->connection->query("SET time_zone = '+08:00'");
    }

    public function __destruct() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}
?>