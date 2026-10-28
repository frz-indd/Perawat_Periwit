<?php
header("Content-Type: application/json; charset=utf-8");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$respond = static function (int $code, array $data): void {
    http_response_code($code);
    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
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

$userId = filter_var($_SESSION["id_user"] ?? null, FILTER_VALIDATE_INT);
if ($userId === false || $userId < 1) {
    $respond(401, [
        "status" => "unauthenticated",
        "message" => "Silakan login untuk mengakses profil."
    ]);
}

$method = strtoupper((string) ($_SERVER["REQUEST_METHOD"] ?? ""));
if (!in_array($method, ["GET", "PUT"], true)) {
    header("Allow: GET, PUT");
    $respond(405, [
        "status" => "method_not_allowed",
        "message" => "Metode yang diizinkan: GET dan PUT."
    ]);
}

try {
    require_once __DIR__ . "/../database/koneksi.php";

    if ($method === "GET") {
        $statement = $conn->prepare(
            "SELECT Id_user, Nama_user, Email
             FROM `user`
             WHERE Id_user = ?
             LIMIT 1"
        );
        $statement->bind_param("i", $userId);
        $statement->execute();
        $statement->bind_result($id, $name, $email);

        if (!$statement->fetch()) {
            $statement->close();
            $respond(404, [
                "status" => "user_not_found",
                "message" => "Data pengguna tidak ditemukan."
            ]);
        }

        $statement->close();
        $respond(200, [
            "status" => "success",
            "data" => [
                "id_user" => (int) $id,
                "nama" => $name,
                "email" => $email
            ]
        ]);
    }

    $contentType = strtolower(trim(explode(";", (string) ($_SERVER["CONTENT_TYPE"] ?? ""))[0]));
    if ($contentType !== "application/json") {
        if ($contentType === "multipart/form-data") {
            $photo = $_FILES["foto_profil"] ?? $_FILES["foto"] ?? null;
            if (is_array($photo) && (int) ($photo["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $uploadError = (int) ($photo["error"] ?? UPLOAD_ERR_OK);
                if ($uploadError === UPLOAD_ERR_INI_SIZE ||
                    $uploadError === UPLOAD_ERR_FORM_SIZE ||
                    (int) ($photo["size"] ?? 0) > 10 * 1024 * 1024) {
                    $respond(413, [
                        "status" => "validation_error",
                        "message" => "Ukuran foto profil melebihi batas maksimal 10 MB"
                    ]);
                }
            }
            $respond(422, [
                "status" => "validation_error",
                "message" => "Payload edit profil harus berupa JSON; unggah foto profil melalui field foto_profil."
            ]);
        }
        $respond(415, [
            "status" => "unsupported_media_type",
            "message" => "Gunakan Content-Type: application/json."
        ]);
    }

    $input = json_decode((string) file_get_contents("php://input"), true);
    if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) {
        $respond(400, [
            "status" => "invalid_json",
            "message" => "Body harus berupa JSON yang valid."
        ]);
    }

    $name = $input["nama"] ?? null;
    $email = $input["email"] ?? null;
    if (!is_string($name) || !is_string($email)) {
        $respond(422, [
            "status" => "validation_error",
            "message" => "Field nama dan email wajib berupa teks."
        ]);
    }

    $name = trim($name);
    $email = strtolower(trim($email));
    $nameLength = function_exists("mb_strlen")
        ? mb_strlen($name, "UTF-8")
        : strlen($name);

    if ($name === "" || $nameLength > 255) {
        $respond(422, [
            "status" => "validation_error",
            "message" => "Nama wajib diisi dan maksimal 255 karakter."
        ]);
    }

    if (strlen($email) > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $respond(422, [
            "status" => "validation_error",
            "message" => "Masukkan alamat email yang valid (maksimal 255 karakter)."
        ]);
    }

    $statement = $conn->prepare(
        "UPDATE `user`
         SET Nama_user = ?, Email = ?
         WHERE Id_user = ?"
    );
    $statement->bind_param("ssi", $name, $email, $userId);
    $statement->execute();
    $statement->close();

    $_SESSION["nama_user"] = $name;
    $_SESSION["email"] = $email;

    $respond(200, [
        "status" => "success",
        "message" => "Profil berhasil diperbarui.",
        "data" => [
            "id_user" => (int) $userId,
            "nama" => $name,
            "email" => $email
        ]
    ]);
} catch (mysqli_sql_exception $exception) {
    error_log("User profile database error: " . $exception->getMessage());

    if ((int) $exception->getCode() === 1062) {
        $respond(409, [
            "status" => "email_exists",
            "message" => "Email sudah digunakan oleh pengguna lain."
        ]);
    }

    $respond(500, [
        "status" => "server_error",
        "message" => "Terjadi kesalahan pada server. Silakan coba lagi."
    ]);
} catch (Throwable $exception) {
    error_log("User profile error: " . $exception->getMessage());
    $respond(500, [
        "status" => "server_error",
        "message" => "Terjadi kesalahan pada server. Silakan coba lagi."
    ]);
}
