
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

// Ambil input
$identity = trim((string) (
    $_POST["identity"] ??
    $_POST["email"] ??
    $_POST["Email"] ??
    ""
));

$password = $_POST["password"] ?? $_POST["Password"] ?? null;

// Validasi identitas
if ($identity === "") {

    respond(422, [
        "status" => "validation_error",
        "message" => "Email/No telepon wajib di isi"
    ]);
}

// Validasi password kosong
if (!is_string($password) || trim($password) === "") {

    respond(422, [
        "status" => "validation_error",
        "message" => "Password wajib di isi"
    ]);
}

// Validasi minimal 8 karakter
if (strlen($password) < 8) {

    respond(422, [
        "status" => "validation_error",
        "message" => "Password harus terdiri dari minimal 8 karakter"
    ]);
}

// Identifikasi email atau nomor telepon
$isEmail = filter_var(
    $identity,
    FILTER_VALIDATE_EMAIL
) !== false;

if ($isEmail) {

    if (strlen($identity) > 255) {

        respond(422, [
            "status" => "validation_error",
            "message" => "Format email tidak valid."
        ]);
    }

    $identity = strtolower($identity);

} else {

    if (strpos($identity, "@") !== false) {

        respond(422, [
            "status" => "validation_error",
            "message" => "Format email tidak valid."
        ]);
    }

    $identity = normalizePhone($identity);

    if (
        preg_match('/^\+628[1-9][0-9]{6,11}$/', $identity) !== 1
    ) {

        respond(422, [
            "status" => "validation_error",
            "message" => "Format nomor telepon tidak valid."
        ]);
    }
}

try {

    require_once __DIR__ . "/../database/koneksi.php";

    if (!($conn instanceof mysqli)) {
        throw new RuntimeException(
            "Database connection is unavailable."
        );
    }

    // Tentukan pencarian berdasarkan email atau nomor telepon
    if ($isEmail) {

        $sql = "
            SELECT Id_user, Nama_user, Email, Password
            FROM `user`
            WHERE Email = ?
            LIMIT 1
        ";

    } else {

        // Pastikan kolom nomor telepon tersedia
        $columns = $conn->query("SHOW COLUMNS FROM `user`");

        $phoneColumnExists = false;

        while ($column = $columns->fetch_assoc()) {

            if (
                strcasecmp($column["Field"], "No_telepon") === 0
            ) {
                $phoneColumnExists = true;
                break;
            }
        }

        $columns->free();

        if (!$phoneColumnExists) {

            respond(503, [
                "status" => "server_error",
                "message" => "Fitur login nomor telepon belum tersedia."
            ]);
        }

        $sql = "
            SELECT Id_user, Nama_user, Email, Password
            FROM `user`
            WHERE No_telepon = ?
            LIMIT 1
        ";
    }

    // Eksekusi pencarian
    $statement = $conn->prepare($sql);

    $statement->bind_param("s", $identity);

    $statement->execute();

    $result = $statement->get_result();

    $user = $result->fetch_assoc();

    $statement->close();

    // Akun tidak ditemukan
    if (!$user) {

        respond(404, [
            "status" => "account_not_found",
            "message" => "Akun tidak ditemukan. Silakan daftar terlebih dahulu."
        ]);
    }

    // Periksa password
    if (!password_verify($password, $user["Password"])) {

        respond(401, [
            "status" => "wrong_password",
            "message" => "Password yang kamu masukkan salah. Silakan coba lagi."
        ]);
    }

    // Buat session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    session_regenerate_id(true);

    $_SESSION["id_user"] = (int) $user["Id_user"];
    $_SESSION["nama_user"] = $user["Nama_user"];
    $_SESSION["email"] = $user["Email"];

    // Login berhasil
    respond(200, [
        "status" => "success",
        "message" => "Login Berhasil! Selamat datang di Explore Majaku.",
        "redirect" => "../../index.php",
        "data" => [
            "id_user" => (int) $user["Id_user"],
            "nama" => $user["Nama_user"],
            "email" => $user["Email"]
        ]
    ]);

} catch (mysqli_sql_exception $exception) {

    error_log(
        "Login database error: " . $exception->getMessage()
    );

    respond(500, [
        "status" => "server_error",
        "message" => "Terjadi kesalahan pada server. Silakan coba lagi."
    ]);

} catch (Throwable $exception) {

    error_log(
        "Login error: " . $exception->getMessage()
    );

    respond(500, [
        "status" => "server_error",
        "message" => "Terjadi kesalahan pada server. Silakan coba lagi."
    ]);
}
