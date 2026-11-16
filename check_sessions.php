<?php
require_once __DIR__ . '/Backend/database/koneksi.php';
$res = $conn->query("SELECT * FROM admin_sessions ORDER BY id DESC LIMIT 5");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
