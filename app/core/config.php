<?php 

if($_SERVER["SERVER_NAME"] == "localhost"){
    define('ROOT', 'http://localhost/HiveSense_Website');
    
    define('DBSERVER', 'localhost');
    define('DBUSER', 'root');
    define('DBPASS', '');
    define('DBNAME', 'hivesense_db');
} else {
    define('ROOT', 'https://hivesensev2.infinityfree.io');
    
    define('DBSERVER', 'sql307.infinityfree.com');
    define('DBUSER', 'if0_42129862');
    define('DBPASS', '59OPwgRtesruz');
    define('DBNAME', 'if0_42129862_hivesense_db');
}

// Timezone — keep PHP's date()/time() in sync with the Philippines (UTC+8),
// and with MySQL's NOW()/CURDATE() (set per-connection in Base_Model).
date_default_timezone_set('Asia/Manila');

// Error reporting — log, don't print (printed errors break JSON responses)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
?>