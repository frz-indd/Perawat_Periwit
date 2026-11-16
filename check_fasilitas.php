<?php
require_once __DIR__ . '/Backend/database/koneksi.php';
$res = $conn->query("SELECT * FROM fasilitas");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
