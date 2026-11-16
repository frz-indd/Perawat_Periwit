<?php
require_once __DIR__ . '/Backend/database/koneksi.php';
if (!$conn) {
    echo "Connection failed\n";
    exit(1);
}
echo "Connected successfully to database.\n";

$tables = [];
$res = $conn->query("SHOW TABLES");
while ($row = $res->fetch_array()) {
    $tables[] = $row[0];
}
echo "Tables: " . implode(", ", $tables) . "\n";

foreach (['user', 'users', 'wisata', 'ulasan', 'fasilitas', 'facilities', 'admin', 'user_sessions', 'admin_sessions'] as $t) {
    if (in_array($t, $tables)) {
        echo "\nColumns in $t:\n";
        $cres = $conn->query("SHOW COLUMNS FROM `$t`");
        while ($crow = $cres->fetch_assoc()) {
            echo " - {$crow['Field']} ({$crow['Type']})\n";
        }
    }
}
