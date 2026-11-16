<?php
// Simple migration runner using existing DB config (Backend/database/koneksi.php)
require_once __DIR__ . '/Backend/database/koneksi.php';
if ($conn === null) {
    die("Cannot connect to database.\n");
}
$argv = $_SERVER['argv'];
if (count($argv) < 2) {
    die("Usage: php run_migration.php <path-to-sql-file>\n");
}
$sqlFile = $argv[1];
if (!is_file($sqlFile)) {
    die("SQL file not found: $sqlFile\n");
}
$sql = file_get_contents($sqlFile);
// Split by semicolon that ends statements (naïve split, works for simple scripts)
$statements = array_filter(array_map('trim', explode(";\n", $sql)));
foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if ($stmt === '') continue;
    if (!$conn->query($stmt)) {
        echo "Error executing: $stmt\n";
        echo $conn->error . "\n";
        exit(1);
    }
}
echo "Migration completed successfully.\n";
?>
