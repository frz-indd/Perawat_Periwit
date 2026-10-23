<?php
header("Content-Type: application/json; charset=utf-8");

$respond = static function (int $statusCode, array $payload): void {
    http_response_code($statusCode);
    echo json_encode(
        $payload,
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

$query = $_GET["q"] ?? null;
if (!is_string($query)) {
    $respond(400, [
        "status" => "invalid_query",
        "message" => "Parameter q harus berupa teks."
    ]);
}

$query = trim($query);
if (mb_strlen($query, "UTF-8") < 2) {
    $respond(422, [
        "status" => "query_too_short",
        "message" => "Kata kunci pencarian minimal 2 karakter."
    ]);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require_once __DIR__ . "/../database/koneksi.php";
    require_once __DIR__ . "/../database/wisata_repository.php";

    $destinations = fetchTourismRows($conn, ["keyword" => $query]);
    $respond(200, [
        "status" => "success",
        "data" => $destinations
    ]);
} catch (Throwable $exception) {
    error_log("Destination search error: " . $exception->getMessage());
    $respond(500, [
        "status" => "server_error",
        "message" => "Gagal mencari destinasi."
    ]);
}
