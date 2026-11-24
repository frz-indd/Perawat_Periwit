<?php
header("Content-Type: application/json; charset=utf-8");

$respond = static function (int $code, array $data): void {
    http_response_code($code);
    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
};

if (($_SERVER["REQUEST_METHOD"] ?? "") !== "GET") {
    header("Allow: GET");
    $respond(405, [
        "status" => "method_not_allowed",
        "message" => "Metode yang diizinkan: GET."
    ]);
}

$categoryId = filter_var(
    $_GET["id_kategori"] ?? null,
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
);

if ($categoryId === false) {
    $respond(400, [
        "status" => "invalid_category_id",
        "message" => "ID kategori harus berupa bilangan bulat positif."
    ]);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require_once __DIR__ . "/../database/koneksi.php";

    $categoryStatement = $conn->prepare(
        "SELECT Id_kategori, nama_kategori, gambar
         FROM kategori
         WHERE Id_kategori = ? AND aktif = 1
         LIMIT 1"
    );
    $categoryStatement->bind_param("i", $categoryId);
    $categoryStatement->execute();
    $categoryStatement->bind_result($id, $name, $image);

    if (!$categoryStatement->fetch()) {
        $categoryStatement->close();
        $respond(404, [
            "status" => "category_not_found",
            "message" => "Kategori tidak ditemukan atau tidak aktif."
        ]);
    }

    $categoryStatement->close();

    $tourismStatement = $conn->prepare(
        "SELECT w.id_wisata, w.nama_wisata,
            (
                SELECT f.link_foto
                FROM foto_wisata AS f
                WHERE f.id_wisata = w.id_wisata
                ORDER BY f.Id_foto
                LIMIT 1
            ) AS gambar,
            w.deskripsi, w.alamat, w.domisili,
            w.harga_tiket, w.jam_buka, w.jam_tutup,
            w.latitude, w.longitude
         FROM wisata AS w
         WHERE w.id_kategori = ?
         ORDER BY w.nama_wisata"
    );
    $tourismStatement->bind_param("i", $categoryId);
    $tourismStatement->execute();
    $tourismStatement->bind_result(
        $tourismId,
        $tourismName,
        $tourismImage,
        $description,
        $address,
        $region,
        $ticketPrice,
        $openingTime,
        $closingTime,
        $latitude,
        $longitude
    );

    $tourism = [];
    while ($tourismStatement->fetch()) {
        $numericLatitude = $latitude === null ? null : (float) $latitude;
        $numericLongitude = $longitude === null ? null : (float) $longitude;
        $coordinatesComplete = $numericLatitude !== null && $numericLongitude !== null
            && is_finite($numericLatitude) && is_finite($numericLongitude)
            && $numericLatitude >= -90 && $numericLatitude <= 90
            && $numericLongitude >= -180 && $numericLongitude <= 180;
        $tourism[] = [
            "id_wisata" => $tourismId,
            "id_kategori" => (int) $categoryId,
            "nama_wisata" => $tourismName,
            "gambar" => $tourismImage,
            "deskripsi" => $description,
            "alamat" => $address,
            "wilayah" => $region,
            "harga_tiket" => (float) $ticketPrice,
            "jam_buka" => $openingTime,
            "jam_tutup" => $closingTime,
            "latitude" => $coordinatesComplete ? $numericLatitude : null,
            "longitude" => $coordinatesComplete ? $numericLongitude : null,
            "lokasi" => [
                "alamat" => $address,
                "wilayah" => $region,
                "latitude" => $coordinatesComplete ? $numericLatitude : null,
                "longitude" => $coordinatesComplete ? $numericLongitude : null,
                "koordinat_lengkap" => $coordinatesComplete
            ]
        ];
    }

    $tourismStatement->close();

    $respond(200, [
        "status" => "success",
        "data" => [
            "kategori" => [
                "id_kategori" => (int) $id,
                "nama" => $name,
                "gambar" => $image
            ],
            "wisata" => $tourism
        ]
    ]);
} catch (Throwable $exception) {
    error_log("Tourism by category error: " . $exception->getMessage());
    $respond(500, [
        "status" => "server_error",
        "message" => "Gagal mengambil data wisata berdasarkan kategori."
    ]);
}
