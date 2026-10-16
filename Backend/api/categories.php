<?php
header("Content-Type: application/json; charset=utf-8");

if (($_SERVER["REQUEST_METHOD"] ?? "") !== "GET") {
    header("Allow: GET");
    http_response_code(405);
    echo json_encode([
        "status" => "method_not_allowed",
        "message" => "Metode yang diizinkan: GET."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require_once __DIR__ . "/../database/koneksi.php";

    $result = $conn->query(
        "SELECT Id_kategori, nama_kategori, gambar
         FROM kategori
         WHERE aktif = 1
         ORDER BY nama_kategori"
    );

    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = [
            "id_kategori" => (int) $row["Id_kategori"],
            "nama" => $row["nama_kategori"],
            "gambar" => $row["gambar"]
        ];
    }

    echo json_encode([
        "status" => "success",
        "data" => $categories
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $exception) {
    error_log("Category list error: " . $exception->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "server_error",
        "message" => "Gagal mengambil data kategori."
    ], JSON_UNESCAPED_UNICODE);
}