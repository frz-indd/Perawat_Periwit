<?php
header("Content-Type: application/json; charset=utf-8");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$respond = static function (int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
};

$method = strtoupper((string) ($_SERVER["REQUEST_METHOD"] ?? ""));
if (!in_array($method, ["GET", "PUT"], true)) {
    header("Allow: GET, PUT");
    $respond(405, ["status" => "method_not_allowed", "message" => "Metode yang diizinkan: GET dan PUT."]);
}

$destinationId = $_GET["id"] ?? "";
if (!is_string($destinationId) || !preg_match('/^[A-Za-z0-9_-]{1,16}$/', $destinationId)) {
    $respond(400, ["status" => "invalid_destination_id", "message" => "ID destinasi tidak valid."]);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
        "httponly" => true,
        "samesite" => "Lax"
    ]);
    session_start();
}

if ($method === "PUT") {
    if (($_SESSION["role"] ?? "") !== "admin" || (int) ($_SESSION["id_admin"] ?? 0) < 1) {
        $respond(401, ["status" => "unauthenticated", "message" => "Login sebagai admin diperlukan untuk mengubah lokasi."]);
    }

    $csrfToken = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";
    $sessionToken = $_SESSION["csrf"] ?? "";
    if (!is_string($csrfToken) || !is_string($sessionToken) || $sessionToken === "" || !hash_equals($sessionToken, $csrfToken)) {
        $respond(403, ["status" => "csrf_invalid", "message" => "Token CSRF tidak valid."]);
    }

    $contentType = strtolower(trim(explode(";", (string) ($_SERVER["CONTENT_TYPE"] ?? ""))[0]));
    if ($contentType !== "application/json") {
        $respond(415, ["status" => "unsupported_media_type", "message" => "Gunakan Content-Type: application/json."]);
    }

    $input = json_decode((string) file_get_contents("php://input"), true);
    if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) {
        $respond(400, ["status" => "invalid_json", "message" => "Body harus berupa JSON yang valid."]);
    }
    if (!array_key_exists("alamat", $input) || !array_key_exists("latitude", $input) || !array_key_exists("longitude", $input)) {
        $respond(422, ["status" => "validation_error", "message" => "Field alamat, latitude, dan longitude wajib dikirim."]);
    }

    $address = $input["alamat"];
    if (!is_string($address)) {
        $respond(422, ["status" => "validation_error", "message" => "Alamat harus berupa teks."]);
    }
    $address = trim($address);
    $addressLength = function_exists("mb_strlen") ? mb_strlen($address, "UTF-8") : strlen($address);
    if ($addressLength > 255) {
        $respond(422, ["status" => "validation_error", "message" => "Alamat maksimal 255 karakter."]);
    }

    $latitudeValue = $input["latitude"];
    $longitudeValue = $input["longitude"];
    $bothCoordinatesMissing = ($latitudeValue === null || $latitudeValue === "")
        && ($longitudeValue === null || $longitudeValue === "");
    if (!$bothCoordinatesMissing && ($latitudeValue === null || $latitudeValue === "" || $longitudeValue === null || $longitudeValue === "")) {
        $respond(422, ["status" => "incomplete_coordinates", "message" => "Latitude dan longitude harus diisi bersama atau keduanya null."]);
    }

    $latitude = null;
    $longitude = null;
    if (!$bothCoordinatesMissing) {
        if (!is_numeric($latitudeValue) || !is_numeric($longitudeValue)) {
            $respond(422, ["status" => "invalid_coordinates", "message" => "Latitude dan longitude harus berupa angka."]);
        }
        $latitude = (float) $latitudeValue;
        $longitude = (float) $longitudeValue;
        if (!is_finite($latitude) || !is_finite($longitude) || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            $respond(422, ["status" => "invalid_coordinates", "message" => "Latitude harus antara -90 dan 90, longitude antara -180 dan 180."]);
        }
    }
}

try {
    require_once __DIR__ . "/../database/koneksi.php";

    if ($method === "GET") {
        $statement = $conn->prepare(
            "SELECT id_wisata, nama_wisata, alamat, latitude, longitude
             FROM wisata WHERE id_wisata = ? AND aktif = 1 LIMIT 1"
        );
        $statement->bind_param("s", $destinationId);
        $statement->execute();
        $result = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!$result) {
            $respond(404, ["status" => "destination_not_found", "message" => "Destinasi tidak ditemukan."]);
        }

        $lat = $result["latitude"] === null ? null : (float) $result["latitude"];
        $lng = $result["longitude"] === null ? null : (float) $result["longitude"];
        $validPair = $lat !== null && $lng !== null && is_finite($lat) && is_finite($lng)
            && $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
        $respond(200, [
            "status" => "success",
            "data" => [
                "id_wisata" => (string) $result["id_wisata"],
                "nama_wisata" => $result["nama_wisata"],
                "alamat" => $result["alamat"],
                "latitude" => $validPair ? $lat : null,
                "longitude" => $validPair ? $lng : null,
                "koordinat_lengkap" => $validPair
            ]
        ]);
    }

    $statement = $conn->prepare(
        "UPDATE wisata SET alamat = ?, latitude = ?, longitude = ? WHERE id_wisata = ?"
    );
    $statement->bind_param("sdds", $address, $latitude, $longitude, $destinationId);
    $statement->execute();
    $affected = $statement->affected_rows;
    $statement->close();

    if ($affected === 0) {
        $exists = $conn->prepare("SELECT id_wisata FROM wisata WHERE id_wisata = ? LIMIT 1");
        $exists->bind_param("s", $destinationId);
        $exists->execute();
        $exists->store_result();
        $found = $exists->num_rows > 0;
        $exists->close();
        if (!$found) {
            $respond(404, ["status" => "destination_not_found", "message" => "Destinasi tidak ditemukan."]);
        }
    }

    $respond(200, [
        "status" => "success",
        "message" => "Data lokasi berhasil diperbarui.",
        "data" => [
            "id_wisata" => $destinationId,
            "alamat" => $address,
            "latitude" => $latitude,
            "longitude" => $longitude,
            "koordinat_lengkap" => !$bothCoordinatesMissing
        ]
    ]);
} catch (Throwable $exception) {
    error_log("Destination location error: " . $exception->getMessage());
    $respond(500, ["status" => "server_error", "message" => "Gagal memproses data lokasi."]);
}
