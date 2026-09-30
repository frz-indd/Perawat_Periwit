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

$hasLatitude = array_key_exists("latitude", $_GET);
$hasLongitude = array_key_exists("longitude", $_GET);
$userLatitude = null;
$userLongitude = null;

if ($hasLatitude !== $hasLongitude) {
  http_response_code(422);
  echo json_encode([
    "status" => "error",
    "message" => "Latitude dan longitude harus dikirim bersamaan"
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($hasLatitude && $hasLongitude) {
  if (!is_numeric($_GET["latitude"]) || !is_numeric($_GET["longitude"])) {
    http_response_code(422);
    echo json_encode([
      "status" => "error",
      "message" => "Koordinat harus berupa angka"
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $userLatitude = (float) $_GET["latitude"];
  $userLongitude = (float) $_GET["longitude"];
  if (
    !is_finite($userLatitude) ||
    !is_finite($userLongitude) ||
    $userLatitude < -90 ||
    $userLatitude > 90 ||
    $userLongitude < -180 ||
    $userLongitude > 180
  ) {
    http_response_code(422);
    echo json_encode([
      "status" => "error",
      "message" => "Koordinat berada di luar rentang yang valid"
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }
}

$calculateDistanceKm = static function (
  float $latitude1,
  float $longitude1,
  float $latitude2,
  float $longitude2
): float {
  $latitudeDelta = deg2rad($latitude2 - $latitude1);
  $longitudeDelta = deg2rad($longitude2 - $longitude1);
  $haversine = sin($latitudeDelta / 2) ** 2
    + cos(deg2rad($latitude1))
    * cos(deg2rad($latitude2))
    * sin($longitudeDelta / 2) ** 2;

  return 6371.0088 * 2 * atan2(
    sqrt(min(1, $haversine)),
    sqrt(max(0, 1 - $haversine))
  );
};

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
    $row["jarak_km"] = null;
    if (
      $userLatitude !== null &&
      $userLongitude !== null &&
      $row["lokasi"]["latitude"] !== null &&
      $row["lokasi"]["longitude"] !== null
    ) {
      $row["jarak_km"] = round($calculateDistanceKm(
        $userLatitude,
        $userLongitude,
        $row["lokasi"]["latitude"],
        $row["lokasi"]["longitude"]
      ), 2);
    }

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
