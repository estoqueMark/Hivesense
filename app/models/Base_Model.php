<?php
require_once __DIR__ . "/../core/config.php";

class Base_Model {
    public $connection;

    public function __construct() {
        $this->connection = mysqli_init();
        if (!$this->connection->real_connect(DBSERVER, DBUSER, DBPASS, DBNAME, null, null, MYSQLI_CLIENT_FOUND_ROWS)) {
            die("Connection failed: " . mysqli_connect_error());
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