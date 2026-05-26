<?php
/**
 * Database Configuration
 * Reads credentials from Railway environment variables.
 */

define('DB_HOST', getenv('MYSQLHOST')     ?: 'mysql.railway.internal');
define('DB_PORT', (int)(getenv('MYSQLPORT') ?: 3306));
define('DB_USER', getenv('MYSQLUSER')     ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: 'AmAVIGyPIvtAgYNISmJZGYAynrhTngaL');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'inventory_db');

function getDBConnection(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

    if ($conn->connect_error) {
        die(json_encode([
            'success' => false,
            'message' => 'Database connection failed: ' . $conn->connect_error
        ]));
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}