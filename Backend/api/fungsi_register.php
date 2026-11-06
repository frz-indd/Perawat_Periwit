
<?php

header("Content-Type: application/json; charset=utf-8");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function respond(int $code, array $data): void
{
    http_response_code($code);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

function normalizePhone(string $phone): string
{
    return preg_replace('/[\s()-]/', '', $phone) ?? "";
}

// Hanya menerima POST
if (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST") {
    header("Allow: POST");

    respond(405, [
        "status" => "validation_error",
        "message" => "Metode tidak diizinkan."
    ]);
}

// Ambil data dari form
$identity = trim((string) ($_POST["identity"] ?? ""));

$password = $_POST["password"] ?? null;

$confirmPassword = $_POST["confirmPassword"] ?? null;

$email = "";
$phone = "";

// =====================================
// VALIDASI EMAIL / NO TELEPON
// =====================================

if ($identity === "") {
    respond(422, [
        "status" => "validation_error",
        "field" => "identity",
        "message" => "Email/No telepon wajib di isi"
    ]);
}

// Jika input berupa email
if (filter_var($identity, FILTER_VALIDATE_EMAIL) !== false) {

    $email = strtolower($identity);

} else {

    // Jika bukan email, anggap sebagai nomor telepon
    $phone = normalizePhone($identity);

    if (
        preg_match('/^\+628[1-9][0-9]{6,11}$/', $phone) !== 1
    ) {
        respond(422, [
            "status" => "validation_error",
            "field" => "identity",
            "message" => "Nomor telepon harus menggunakan awalan +62"
        ]);
    }
}

// =====================================
// VALIDASI PASSWORD
// =====================================

if (!is_string($password) || trim($password) === "") {
    respond(422, [
        "status" => "validation_error",
        "field" => "password",
        "message" => "Password wajib di isi"
    ]);
}

// Password minimal 8 karakter saja
if (strlen($password) < 8) {
    respond(422, [
        "status" => "validation_error",
        "field" => "password",
        "message" => "Password harus terdiri dari minimal 8 karakter"
    ]);
}

// =====================================
// VALIDASI VERIFIKASI PASSWORD
// =====================================

if (
    !is_string($confirmPassword) ||
    trim($confirmPassword) === ""
) {
    respond(422, [
        "status" => "validation_error",
        "field" => "confirmPassword",
        "message" => "Verifikasi password wajib di isi"
    ]);
}

if ($password !== $confirmPassword) {
    respond(422, [
        "status" => "validation_error",
        "field" => "confirmPassword",
        "message" => "Konfirmasi password tidak sesuai"
    ]);
}

// =====================================
// PROSES DATABASE
// =====================================

try {

    require_once __DIR__ . "/../database/koneksi.php";

    if (!($conn instanceof mysqli)) {
        throw new RuntimeException("Koneksi database tidak tersedia.");
    }

    // Periksa kolom tabel user
    $columns = $conn->query("SHOW COLUMNS FROM `user`");

    $emailAllowsNull = false;
    $phoneColumnExists = false;

    while ($column = $columns->fetch_assoc()) {

        if (strcasecmp($column["Field"], "Email") === 0) {
            $emailAllowsNull =
                strtoupper($column["Null"]) === "YES";
        }

        if (strcasecmp($column["Field"], "No_telepon") === 0) {
            $phoneColumnExists = true;
        }
    }

    $columns->free();

    // Pastikan kolom nomor telepon tersedia
    if ($phone !== "" && !$phoneColumnExists) {
        respond(503, [
            "status" => "server_error",
            "message" => "Kolom No_telepon belum tersedia pada database."
        ]);
    }

    if ($email === "" && !$emailAllowsNull) {
        respond(503, [
            "status" => "server_error",
            "message" => "Kolom Email harus memperbolehkan NULL."
        ]);
    }

    // Cek email sudah terdaftar
    if ($email !== "") {

        $checkEmail = $conn->prepare(
            "SELECT Id_user FROM `user` WHERE Email = ? LIMIT 1"
        );

        $checkEmail->bind_param("s", $email);

        $checkEmail->execute();
        $checkEmail->store_result();

        $emailExists = $checkEmail->num_rows > 0;

        $checkEmail->close();

        if ($emailExists) {
            respond(409, [
                "status" => "email_exists",
                "field" => "identity",
                "message" => "Email sudah terdaftar. Silakan gunakan email lain."
            ]);
        }
    }

    // Cek nomor telepon sudah terdaftar
    if ($phone !== "") {

        $checkPhone = $conn->prepare(
            "SELECT Id_user FROM `user` WHERE No_telepon = ? LIMIT 1"
        );

        $checkPhone->bind_param("s", $phone);

        $checkPhone->execute();
        $checkPhone->store_result();

        $phoneExists = $checkPhone->num_rows > 0;

        $checkPhone->close();

        if ($phoneExists) {
            respond(409, [
                "status" => "phone_exists",
                "field" => "identity",
                "message" => "Nomor telepon sudah terdaftar. Silakan gunakan nomor lain."
            ]);
        }
    }

    // Hash password
    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    if ($passwordHash === false) {
        throw new RuntimeException("Gagal membuat hash password.");
    }

    // Simpan akun
    if ($phone === "") {

        $statement = $conn->prepare(
            "INSERT INTO `user` (Email, Password)
             VALUES (?, ?)"
        );

        $statement->bind_param(
            "ss",
            $email,
            $passwordHash
        );

    } elseif ($email === "") {

        $statement = $conn->prepare(
            "INSERT INTO `user` (Email, No_telepon, Password)
             VALUES (NULL, ?, ?)"
        );

        $statement->bind_param(
            "ss",
            $phone,
            $passwordHash
        );
    }

    $statement->execute();

    $userId = (int) $conn->insert_id;

    $statement->close();

    // Berhasil
    respond(201, [
        "status" => "success",
        "message" => "Pendaftaran berhasil.",
        "data" => [
            "id_user" => $userId,
            "email" => $email !== "" ? $email : null,
            "telepon" => $phone !== "" ? $phone : null
        ]
    ]);

} catch (mysqli_sql_exception $exception) {

    error_log("Register database error: " . $exception->getMessage());

    if ((int) $exception->getCode() === 1062) {
        respond(409, [
            "status" => "email_exists",
            "field" => "identity",
            "message" => "Email atau nomor telepon sudah terdaftar."
        ]);
    }

    respond(500, [
        "status" => "server_error",
        "message" => "Terjadi kesalahan pada server. Silakan coba lagi."
    ]);

} catch (Throwable $exception) {

    error_log("Register error: " . $exception->getMessage());

    respond(500, [
        "status" => "server_error",
        "message" => "Terjadi kesalahan pada server. Silakan coba lagi."
    ]);
}
