<?php
// Database MySQLi Connection Helper
// Used for MySQLi prepared statements and transactions

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'studenthub_db';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli($host, $user, $pass, $db);
    $mysqli->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die("MySQLi Connection Error: " . htmlspecialchars($e->getMessage()));
}
