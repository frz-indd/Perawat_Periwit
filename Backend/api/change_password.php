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
        "lifetime" => 0, "path" => "/",
        "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
        "httponly" => true, "samesite" => "Lax"
    ]);
    session_start();
}

$userId = filter_var($_SESSION["id_user"] ?? null, FILTER_VALIDATE_INT);
if ($userId === false || $userId < 1) {
    $respond(401, ["status" => "unauthenticated", "message" => "Silakan login untuk mengubah password."]);
}
if (($_SERVER["REQUEST_METHOD"] ?? "") !== "PUT") {
    header("Allow: PUT");
    $respond(405, ["status" => "method_not_allowed", "message" => "Metode yang diizinkan: PUT."]);
}

$contentType = strtolower(trim(explode(";", (string) ($_SERVER["CONTENT_TYPE"] ?? ""))[0]));
if ($contentType !== "application/json") {
    $respond(415, ["status" => "unsupported_media_type", "message" => "Gunakan Content-Type: application/json."]);
}
$input = json_decode((string) file_get_contents("php://input"), true);
if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) {
    $respond(400, ["status" => "invalid_json", "message" => "Body harus berupa JSON yang valid."]);
}
$currentPassword = $input["currentPassword"] ?? null;
$newPassword = $input["newPassword"] ?? null;
if (!is_string($currentPassword) || $currentPassword === "" || !is_string($newPassword)) {
    $respond(422, ["status" => "validation_error", "message" => "Password lama dan password baru wajib diisi."]);
}
if (strlen($newPassword) > 72 || preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $newPassword) !== 1) {
    $respond(422, ["status" => "validation_error", "message" => "Password baru minimal 8 karakter dan harus memuat huruf besar, huruf kecil, angka, serta karakter khusus."]);
}

try {
    require_once __DIR__ . "/../database/koneksi.php";
    $statement = $conn->prepare("SELECT Password FROM `user` WHERE Id_user = ? LIMIT 1");
    $statement->bind_param("i", $userId);
    $statement->execute();
    $statement->bind_result($passwordHash);
    if (!$statement->fetch()) {
        $statement->close();
        $respond(404, ["status" => "user_not_found", "message" => "Data pengguna tidak ditemukan."]);
    }
    $statement->close();
    if (!is_string($passwordHash) || !password_verify($currentPassword, $passwordHash)) {
        $respond(422, ["status" => "invalid_current_password", "message" => "Password lama tidak sesuai."]);
    }
    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
    if ($newHash === false) {
        throw new RuntimeException("Gagal membuat hash password.");
    }
    $update = $conn->prepare("UPDATE `user` SET Password = ? WHERE Id_user = ?");
    $update->bind_param("si", $newHash, $userId);
    $update->execute();
    $update->close();
    $respond(200, ["status" => "success", "message" => "Password berhasil diubah."]);
} catch (Throwable $exception) {
    error_log("Change password error: " . $exception->getMessage());
    $respond(500, ["status" => "server_error", "message" => "Terjadi kesalahan pada server. Silakan coba lagi."]);
}
