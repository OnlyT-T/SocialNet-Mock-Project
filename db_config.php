<?php
// db_config.php — shared database connection
define('DB_HOST', 'localhost');
define('DB_USER', 'socialnet');
define('DB_PASS', '123');
define('DB_NAME', 'socialnet');

function get_db(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
