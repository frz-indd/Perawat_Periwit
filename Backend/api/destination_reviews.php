<?php
header("Content-Type: application/json; charset=utf-8");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$respond = static function (int $code, array $data): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
};

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

if (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST") {
    header("Allow: POST");
    $respond(405, ["status" => "method_not_allowed", "message" => "Metode yang diizinkan: POST."]);
}

$userId = filter_var($_SESSION["id_user"] ?? null, FILTER_VALIDATE_INT);
if ($userId === false || $userId < 1 || ($_SESSION["role"] ?? "user") !== "user") {
    $respond(401, ["status" => "unauthenticated", "message" => "Silakan login sebagai pengguna untuk mengirim ulasan."]);
}

$csrfToken = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";
$sessionToken = $_SESSION["csrf"] ?? "";
if (!is_string($csrfToken) || !is_string($sessionToken) || $sessionToken === "" || !hash_equals($sessionToken, $csrfToken)) {
    $respond(403, ["status" => "csrf_invalid", "message" => "Token keamanan tidak valid. Muat ulang halaman lalu coba lagi."]);
}

$contentType = strtolower(trim(explode(";", (string) ($_SERVER["CONTENT_TYPE"] ?? ""))[0]));
if ($contentType !== "application/json") {
    $respond(415, ["status" => "unsupported_media_type", "message" => "Gunakan Content-Type: application/json."]);
}

$input = json_decode((string) file_get_contents("php://input"), true);
if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) {
    $respond(400, ["status" => "invalid_json", "message" => "Body harus berupa JSON yang valid."]);
}

$rating = $input["rating"] ?? null;
if (!(is_int($rating) || (is_string($rating) && preg_match('/^[1-5]$/', $rating) === 1)) || (int) $rating < 1 || (int) $rating > 5) {
    $respond(422, ["status" => "validation_error", "message" => "Rating harus berupa bilangan bulat 1 sampai 5."]);
}
$rating = (int) $rating;

$reviewText = $input["text"] ?? $input["komentar"] ?? null;
if (!is_string($reviewText)) {
    $respond(422, ["status" => "validation_error", "message" => "Field text wajib berupa teks."]);
}
$reviewText = trim($reviewText);
$textLength = function_exists("mb_strlen") ? mb_strlen($reviewText, "UTF-8") : strlen($reviewText);
if ($reviewText === "" || $textLength > 500) {
    $respond(422, ["status" => "validation_error", "message" => "Teks ulasan wajib diisi dan maksimal 500 karakter."]);
}

$destinationId = $_GET["id"] ?? "";
if (!is_string($destinationId) || !preg_match('/^[A-Za-z0-9_-]{1,16}$/', $destinationId)) {
    $respond(400, ["status" => "invalid_destination_id", "message" => "ID destinasi tidak valid."]);
}

try {
    require_once __DIR__ . "/../database/koneksi.php";
    $conn->begin_transaction();

    $destination = $conn->prepare("SELECT id_wisata FROM wisata WHERE id_wisata = ? FOR UPDATE");
    $destination->bind_param("s", $destinationId);
    $destination->execute();
    $destination->store_result();
    if ($destination->num_rows !== 1) {
        $destination->close();
        $conn->rollback();
        $respond(404, ["status" => "destination_not_found", "message" => "Destinasi tidak ditemukan."]);
    }
    $destination->close();

    $insert = $conn->prepare(
        "INSERT INTO ulasan (Id_user, Id_wisata, rating, komentar, tanggal_ulasan)
         VALUES (?, ?, ?, ?, CURDATE())"
    );
    $insert->bind_param("isis", $userId, $destinationId, $rating, $reviewText);
    $insert->execute();
    $reviewId = (int) $conn->insert_id;
    $insert->close();

    $update = $conn->prepare(
        "UPDATE wisata
         SET average_rating = (SELECT ROUND(AVG(rating), 2) FROM ulasan WHERE Id_wisata = ?)
         WHERE id_wisata = ?"
    );
    $update->bind_param("ss", $destinationId, $destinationId);
    $update->execute();
    $update->close();

    $averageQuery = $conn->prepare(
        "SELECT average_rating,
                (SELECT COUNT(*) FROM ulasan WHERE Id_wisata = ?) AS review_count
         FROM wisata WHERE id_wisata = ?"
    );
    $averageQuery->bind_param("ss", $destinationId, $destinationId);
    $averageQuery->execute();
    $averageQuery->bind_result($averageRating, $reviewCount);
    $averageQuery->fetch();
    $averageQuery->close();

    $conn->commit();
    $respond(201, [
        "status" => "success",
        "message" => "Ulasan berhasil disimpan.",
        "data" => [
            "id_review" => $reviewId,
            "destination_id" => $destinationId,
            "rating" => $rating,
            "text" => $reviewText,
            "average_rating" => $averageRating === null ? null : (float) $averageRating,
            "review_count" => (int) $reviewCount
        ]
    ]);
} catch (Throwable $exception) {
    if (isset($conn) && $conn instanceof mysqli) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
    }
    error_log("Destination review error: " . $exception->getMessage());
    $respond(500, ["status" => "server_error", "message" => "Gagal menyimpan ulasan."]);
}
