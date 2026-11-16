<?php
require_once __DIR__ . '/Backend/database/koneksi.php';
$res = $conn->query("SELECT * FROM admin");
while ($row = $res->fetch_assoc()) {
    // print without showing full password hash for security, but show id and username
    echo "Admin: ID=" . $row['Id_admin'] . ", User=" . ($row['Username'] ?? '') . ", Name=" . ($row['Nama_admin'] ?? '') . "\n";
}
