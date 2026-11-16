<?php
require_once __DIR__ . '/Backend/database/koneksi.php';
$res = $conn->query("SELECT Id_user, Nama_user, Email, No_telepon FROM user");
while ($row = $res->fetch_assoc()) {
    echo "User: ID=" . $row['Id_user'] . ", Name=" . $row['Nama_user'] . ", Email=" . $row['Email'] . "\n";
}
