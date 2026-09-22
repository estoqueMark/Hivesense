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

// Error reporting — log everywhere; only print to the browser on localhost.
// On production, printing errors both leaks internals and breaks JSON
// responses from the API endpoints, so it stays off there unconditionally.
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('display_errors', ($_SERVER["SERVER_NAME"] == "localhost") ? 1 : 0);
?>