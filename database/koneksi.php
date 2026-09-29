<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "explore_majaku";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Koneksi gagal"]));
}
?>