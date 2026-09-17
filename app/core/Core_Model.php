<?php
require_once __DIR__ . "/config.php";

class Base_Model {
    public $connection;

    public function __construct() {
        $this->connection = new mysqli(DBSERVER, DBUSER, DBPASS, DBNAME);
        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
    }
}