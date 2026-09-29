<?php
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
  header("Allow: GET");
  http_response_code(405);
  echo json_encode([
    "status" => "error",
    "message" => "Metode tidak diizinkan"
  ]);
  exit;
}

try {
  require_once __DIR__ . "/../../database/koneksi.php";

  $result = $conn->query(
    "SELECT id_wisata, nama_wisata, harga_tiket, wilayah FROM wisata"
  );

  $data = [];
  while ($row = $result->fetch_assoc()) {
    $row["id_wisata"] = (int) $row["id_wisata"];
    if (is_numeric($row["harga_tiket"])) {
      $row["harga_tiket"] = 0 + $row["harga_tiket"];
    }
    $data[] = $row;
  }

  echo json_encode([
    "status" => "success",
    "data" => $data
  ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
  error_log($exception->getMessage());
  http_response_code(500);
  echo json_encode([
    "status" => "error",
    "message" => "Gagal mengambil data wisata"
  ], JSON_UNESCAPED_UNICODE);
}