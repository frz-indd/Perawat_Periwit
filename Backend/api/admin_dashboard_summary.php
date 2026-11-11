<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../middleware/auth.php";
adminOnly();

if (($_SERVER["REQUEST_METHOD"] ?? "") !== "GET") {
    header("Allow: GET");
    http_response_code(405);
    echo json_encode(["message" => "Metode tidak diizinkan."]);
    exit;
}

try {
    require_once __DIR__ . "/../database/koneksi.php";
    if (!$conn instanceof mysqli) {
        throw new RuntimeException("Koneksi database tidak tersedia.");
    }

    $sql = "
        SELECT
            (SELECT COUNT(*) FROM wisata) AS total_wisata,
            (SELECT COUNT(*) FROM kategori) AS total_kategori,
            (SELECT COUNT(*) FROM user) AS total_pengguna,
            (SELECT COUNT(*) FROM ulasan) AS total_ulasan
    ";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    echo json_encode([
        "status" => "success",
        "message" => "Ringkasan dashboard berhasil diambil.",
        "data" => [
            "total_wisata" => (int) $row["total_wisata"],
            "total_kategori" => (int) $row["total_kategori"],
            "total_pengguna" => (int) $row["total_pengguna"],
            "total_ulasan" => (int) $row["total_ulasan"]
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $exception) {
    error_log("Admin dashboard summary error: " . $exception->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "server_error",
        "message" => "Gagal mengambil ringkasan dashboard."
    ], JSON_UNESCAPED_UNICODE);
}
