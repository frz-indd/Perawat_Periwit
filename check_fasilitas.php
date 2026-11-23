<?php
require_once __DIR__ . '/Backend/database/koneksi.php';

echo "--- DESCRIBE fasilitas ---\n";
$res = $conn->query("DESCRIBE fasilitas");
while ($r = $res->fetch_assoc()) {
    echo "{$r['Field']} | {$r['Type']} | {$r['Null']} | {$r['Key']}\n";
}

echo "\n--- SELECT * FROM fasilitas ---\n";
$res = $conn->query("SELECT * FROM fasilitas");
while ($r = $res->fetch_assoc()) {
    print_r($r);
}

echo "\n--- SELECT * FROM wisata_fasilitas LIMIT 10 ---\n";
$res = $conn->query("SELECT * FROM wisata_fasilitas LIMIT 10");
while ($r = $res->fetch_assoc()) {
    print_r($r);
}
