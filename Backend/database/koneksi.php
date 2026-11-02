<?php
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: 'eksplormajaku';

$conn = null;

try {
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_errno) {
        throw new RuntimeException($conn->connect_error);
    }
} catch (Throwable $exception) {
    error_log('Database connection failed: ' . $exception->getMessage());
    $conn = null;
}
?>