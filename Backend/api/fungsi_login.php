
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

$isValidPhone = static function (string $phone): bool {
    $phone = preg_replace('/[\s()-]/', '', $phone);

    return is_string($phone)
        && preg_match('/^(?:\+62|62|0)8[1-9][0-9]{6,11}$/', $phone) === 1;
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

$identity = trim((string) ($_POST["email"] ?? $_POST["Email"] ?? ""));
$password = $_POST["password"] ?? $_POST["Password"] ?? null;

if ($identity === "" && (!is_string($password) || trim($password) === "")) {
    $respond(422, [
        "status" => "validation_error",
        "message" => "Email atau nomor telepon wajib diisi. Password wajib diisi."
    ]);
}

if ($identity === "") {
    $respond(422, [
        "status" => "validation_error",
        "message" => "Email atau nomor telepon wajib diisi."
    ]);
}

if (!is_string($password) || trim($password) === "") {
    $respond(422, [
        "status" => "validation_error",
        "message" => "Password wajib diisi."
    ]);
}

/* FORMAT IDENTITAS */

$isEmail = filter_var($identity, FILTER_VALIDATE_EMAIL) !== false;

if ($isEmail) {
    if (strlen($identity) > 255) {
        $respond(422, [
            "status" => "invalid_email",
            "message" => "Masukkan alamat email yang valid."
        ]);
    }

    $identity = strtolower($identity);
} elseif (!$isValidPhone($identity)) {
    $respond(422, [
        "status" => "invalid_phone",
        "message" => "Masukkan nomor telepon yang valid."
    ]);
}

/* AUTENTIKASI */

try {
    require_once __DIR__ . "/../database/koneksi.php";

    if ($isEmail) {
        $statement = $conn->prepare(
            "SELECT Id_user, Nama_user, Email, Password
             FROM `user`
             WHERE Email = ?
             LIMIT 1"
        );

        $statement->bind_param("s", $identity);
    } else {
        /*
         * Nomor telepon hanya dapat digunakan jika kolomnya
         * sudah tersedia dan berisi nomor milik pengguna.
         */
        $possibleColumns = [
            "No_telepon",
            "No_telephone",
            "Nomor_telepon",
            "Nomor_telephone",
            "No_hp",
            "No_HP",
            "Telepon",
            "Telephone",
            "Nomor_HP"
        ];

        $columnResult = $conn->query("SHOW COLUMNS FROM `user`");
        $availableColumns = [];

        while ($column = $columnResult->fetch_assoc()) {
            $availableColumns[] = strtolower($column["Field"]);
        }

        $phoneColumn = null;

        foreach ($possibleColumns as $candidate) {
            if (in_array(strtolower($candidate), $availableColumns, true)) {
                $phoneColumn = $candidate;
                break;
            }
        }

        if ($phoneColumn === null) {
            $respond(404, [
                "status" => "account_not_found",
                "message" => "Akun tidak ditemukan. Silakan daftar terlebih dahulu."
            ]);
        }

        $sql = "SELECT Id_user, Nama_user, Email, Password
                FROM `user`
                WHERE `$phoneColumn` = ?
                LIMIT 1";

        $statement = $conn->prepare($sql);
        $statement->bind_param("s", $identity);
    }

    $statement->execute();

    $statement->bind_result(
        $userId,
        $name,
        $storedEmail,
        $passwordHash
    );

    $foundUser = $statement->fetch();
    $statement->close();

    if (!$foundUser) {
        $respond(404, [
            "status" => "account_not_found",
            "message" => "Akun tidak ditemukan. Silakan daftar terlebih dahulu."
        ]);
    }

    if (
        !is_string($passwordHash) ||
        $passwordHash === "" ||
        !password_verify($password, $passwordHash)
    ) {
        $respond(401, [
            "status" => "wrong_password",
            "message" => "Password yang kamu masukkan salah. Silakan coba lagi."
        ]);
    }

    /* SESSION LOGIN */

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params([
            "lifetime" => 0,
            "path" => "/",
            "secure" => (
                !empty($_SERVER["HTTPS"]) &&
                $_SERVER["HTTPS"] !== "off"
            ),
            "httponly" => true,
            "samesite" => "Lax"
        ]);

        session_start();
    }

    session_regenerate_id(true);

    $_SESSION["id_user"] = (int) $userId;
    $_SESSION["nama_user"] = $name;
    $_SESSION["email"] = $storedEmail;

    $respond(200, [
        "status" => "success",
        "message" => "Login berhasil.",
        "redirect" => "../index.html",
        "data" => [
            "id_user" => (int) $userId,
            "nama" => $name,
            "email" => $storedEmail
        ]
    ]);

} catch (Throwable $exception) {
    error_log("Login error: " . $exception->getMessage());

    $respond(500, [
        "status" => "server_error",
        "message" => "Terjadi kesalahan pada server. Silakan coba lagi."
    ]);
}
