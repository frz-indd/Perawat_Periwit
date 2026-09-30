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
  require_once __DIR__ . "/../database/koneksi.php";

  $result = $conn->query(
    "SELECT
      w.id_wisata,
      w.nama_wisata,
      w.deskripsi,
      w.harga_tiket,
      w.domisili AS wilayah,
      w.alamat,
      w.jam_buka,
      w.jam_tutup,
      w.latitude,
      w.longitude,
      k.Id_kategori AS id_kategori,
      k.nama_kategori,
      k.deskripsi AS deskripsi_kategori
    FROM wisata AS w
    LEFT JOIN kategori AS k ON k.Id_kategori = w.id_kategori
    ORDER BY w.nama_wisata"
  );

  $data = [];
  while ($row = $result->fetch_assoc()) {
    $row["id_wisata"] = (int) $row["id_wisata"];
    if (is_numeric($row["harga_tiket"])) {
      $row["harga_tiket"] = 0 + $row["harga_tiket"];
    }

    $row["kategori"] = $row["id_kategori"] === null ? null : [
      "id_kategori" => (int) $row["id_kategori"],
      "nama" => $row["nama_kategori"],
      "deskripsi" => $row["deskripsi_kategori"]
    ];
    $row["lokasi"] = [
      "alamat" => $row["alamat"],
      "domisili" => $row["wilayah"],
      "latitude" => $row["latitude"] === null ? null : (float) $row["latitude"],
      "longitude" => $row["longitude"] === null ? null : (float) $row["longitude"]
    ];

    unset(
      $row["id_kategori"],
      $row["nama_kategori"],
      $row["deskripsi_kategori"],
      $row["alamat"],
      $row["latitude"],
      $row["longitude"]
    );
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