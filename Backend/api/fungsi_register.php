
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

/* METODE REQUEST */

if (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST") {
    header("Allow: POST");

    $respond(405, [
        "status" => "validation_error",
        "message" => "Metode tidak diizinkan."
    ]);
}

/* INPUT */

$name = trim((string) ($_POST["nama"] ?? $_POST["Nama_user"] ?? ""));

$email = strtolower(
    trim((string) ($_POST["email"] ?? $_POST["Email"] ?? ""))
);

$password = $_POST["password"] ?? $_POST["Password"] ?? null;

$confirmPassword =
    $_POST["confirmPassword"] ??
    $_POST["confirm_password"] ??
    null;

/* VALIDASI EMAIL */

if ($email === "") {
    $respond(422, [
        "status" => "validation_error",
        "message" => "Email wajib diisi."
    ]);
}

if (strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $respond(422, [
        "status" => "validation_error",
        "message" => "Masukkan alamat email yang valid."
    ]);
}

/* VALIDASI PASSWORD */

if (!is_string($password) || trim($password) === "") {
    $respond(422, [
        "status" => "validation_error",
        "message" => "Password wajib diisi."
    ]);
}

if (strlen($password) < 8) {
    $respond(422, [
        "status" => "validation_error",
        "message" => "Password harus terdiri dari minimal 8 karakter."
    ]);
}

/* VALIDASI KONFIRMASI */

if (!is_string($confirmPassword) || trim($confirmPassword) === "") {
    $respond(422, [
        "status" => "validation_error",
        "message" => "Verifikasi password wajib diisi."
    ]);
}

if ($password !== $confirmPassword) {
    $respond(422, [
        "status" => "validation_error",
        "message" => "Konfirmasi password tidak sesuai."
    ]);
}

/* NAMA DEFAULT
   Form Register saat ini tidak memiliki input nama.
*/

if ($name === "") {
    $emailName = strstr($email, "@", true);

    $name = is_string($emailName) && $emailName !== ""
        ? $emailName
        : "Pengguna";
}

if (strlen($name) > 255) {
    $respond(422, [
        "status" => "validation_error",
        "message" => "Nama maksimal 255 karakter."
    ]);
}

/* SIMPAN KE DATABASE */

try {
    require_once __DIR__ . "/../database/koneksi.php";

    /* CEK EMAIL SUDAH TERDAFTAR */

    $check = $conn->prepare(
        "SELECT Id_user
         FROM `user`
         WHERE Email = ?
         LIMIT 1"
    );

    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    $emailExists = $check->num_rows > 0;
    $check->close();

    if ($emailExists) {
        $respond(409, [
            "status" => "email_exists",
            "message" => "Email sudah terdaftar. Silakan gunakan email lain."
        ]);
    }

    /* HASH PASSWORD */

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    if ($passwordHash === false) {
        throw new RuntimeException("Gagal membuat hash password.");
    }

    /* INSERT USER */

    $statement = $conn->prepare(
        "INSERT INTO `user` (Nama_user, Email, Password)
         VALUES (?, ?, ?)"
    );

    $statement->bind_param("sss", $name, $email, $passwordHash);
    $statement->execute();

    $userId = (int) $conn->insert_id;

    $statement->close();

    /* REGISTRASI BERHASIL */

    $respond(201, [
        "status" => "success",
        "message" => "Pendaftaran berhasil.",
        "data" => [
            "id_user" => $userId,
            "nama" => $name,
            "email" => $email
        ]
    ]);

} catch (mysqli_sql_exception $exception) {
    error_log("Register database error: " . $exception->getMessage());

    if ((int) $exception->getCode() === 1062) {
        $respond(409, [
            "status" => "email_exists",
            "message" => "Email sudah terdaftar. Silakan gunakan email lain."
        ]);
    }

    $respond(500, [
        "status" => "server_error",
        "message" => "Terjadi kesalahan pada server. Silakan coba lagi."
    ]);

} catch (Throwable $exception) {
    error_log("Register error: " . $exception->getMessage());

    $respond(500, [
        "status" => "server_error",
        "message" => "Terjadi kesalahan pada server. Silakan coba lagi."
    ]);
}
