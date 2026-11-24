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
if ($userId === false || $userId < 1 || ($_SESSION["role"] ?? "") !== "user") {
    $respond(401, ["status" => "unauthenticated", "message" => "Silakan login sebagai pengguna untuk mengelola favorit."]);
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

$destinationId = $input["destination_id"] ?? null;
$action = $input["action"] ?? null;
if (!is_string($destinationId) || preg_match('/^[A-Za-z0-9_-]{1,16}$/', $destinationId) !== 1) {
    $respond(422, ["status" => "validation_error", "message" => "destination_id wajib berupa ID destinasi yang valid."]);
}
if (!is_string($action) || !in_array($action, ["add", "remove"], true)) {
    $respond(422, ["status" => "validation_error", "message" => "action harus bernilai add atau remove."]);
}

try {
    require_once __DIR__ . "/../database/koneksi.php";
    $destination = $conn->prepare("SELECT id_wisata FROM wisata WHERE id_wisata = ? AND aktif = 1 LIMIT 1");
    $destination->bind_param("s", $destinationId);
    $destination->execute();
    $destination->store_result();
    $exists = $destination->num_rows === 1;
    $destination->close();
    if (!$exists) {
        $respond(404, ["status" => "destination_not_found", "message" => "Destinasi tidak ditemukan."]);
    }

    if ($action === "add") {
        $statement = $conn->prepare(
            "INSERT IGNORE INTO favorit (Id_user, Id_wisata, tanggal_ditambahkan) VALUES (?, ?, CURDATE())"
        );
        $statement->bind_param("is", $userId, $destinationId);
    } else {
        $statement = $conn->prepare("DELETE FROM favorit WHERE Id_user = ? AND Id_wisata = ?");
        $statement->bind_param("is", $userId, $destinationId);
    }
    $statement->execute();
    $changed = $statement->affected_rows > 0;
    $statement->close();

    $respond(200, [
        "status" => "success",
        "message" => $action === "add" ? "Destinasi ditambahkan ke favorit." : "Destinasi dihapus dari favorit.",
        "data" => [
            "destination_id" => $destinationId,
            "is_favorite" => $action === "add",
            "changed" => $changed
        ]
    ]);
} catch (Throwable $exception) {
    error_log("Favorites API error: " . $exception->getMessage());
    $respond(500, ["status" => "server_error", "message" => "Gagal memperbarui favorit."]);
}
