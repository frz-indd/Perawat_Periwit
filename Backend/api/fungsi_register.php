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

$normalizePhone = static function (string $phone): string {
    $normalized = preg_replace('/[\s()-]/', '', $phone);
    return is_string($normalized) ? $normalized : "";
};

$isValidPhone = static function (string $phone) use ($normalizePhone): bool {
    return preg_match(
        '/^\+628[1-9][0-9]{6,11}$/',
        $normalizePhone($phone)
    ) === 1;
};

if (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST") {
    header("Allow: POST");
    $respond(405, [
        "status" => "validation_error",
        "message" => "Metode tidak diizinkan."
    ]);
}

$name = trim((string) ($_POST["nama"] ?? $_POST["Nama_user"] ?? ""));
$identity = trim((string) (
    $_POST["identity"] ??
    $_POST["email"] ??
    $_POST["Email"] ??
    ""
));
$phoneInput = trim((string) ($_POST["telepon"] ?? $_POST["no_telepon"] ?? ""));
$password = $_POST["password"] ?? $_POST["Password"] ?? null;
$confirmPassword = $_POST["confirmPassword"] ?? $_POST["confirm_password"] ?? null;

$email = "";
$phone = "";

if ($identity !== "") {
    if (filter_var($identity, FILTER_VALIDATE_EMAIL) !== false) {
        if (strlen($identity) > 255) {
            $respond(422, [
                "status" => "validation_error",
                "message" => "Alamat email maksimal 255 karakter."
            ]);
        }
        $email = strtolower($identity);
    } elseif (strpos($identity, "@") !== false) {
        $respond(422, [
            "status" => "validation_error",
            "message" => "Masukkan alamat email yang valid atau nomor telepon berawalan +62."
        ]);
    } else {
        $phone = $identity;
    }
}

if ($phoneInput !== "") {
    if ($phone !== "") {
        $respond(422, [
            "status" => "validation_error",
            "message" => "Masukkan satu nomor telepon saja."
        ]);
    }
    $phone = $phoneInput;
}

if ($email === "" && $phone === "") {
    $respond(422, [
        "status" => "validation_error",
        "message" => "Email atau nomor telepon wajib diisi."
    ]);
}

if ($phone !== "" && !$isValidPhone($phone)) {
    $respond(422, [
        "status" => "validation_error",
        "message" => "Nomor telepon harus berawalan +62, contoh: +6281234567890."
    ]);
}

if ($phone !== "") {
    $phone = $normalizePhone($phone);
}

if (!is_string($password) || trim($password) === "") {
    $respond(422, [
        "status" => "validation_error",
        "message" => "Password wajib diisi."
    ]);
}

if (
    strlen($password) > 72 ||
    preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password) !== 1
) {
    $respond(422, [
        "status" => "validation_error",
        "message" => "Password minimal 8 karakter dan harus memuat huruf besar, huruf kecil, angka, serta karakter khusus."
    ]);
}

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

if ($name === "") {
    $emailName = $email !== "" ? strstr($email, "@", true) : false;
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

try {
    require_once __DIR__ . "/../database/koneksi.php";
    if (!($conn instanceof mysqli)) {
        throw new RuntimeException("Database connection is unavailable.");
    }

    $columns = $conn->query("SHOW COLUMNS FROM `user`");
    $emailAllowsNull = false;
    $phoneColumnExists = false;

    while ($column = $columns->fetch_assoc()) {
        if (strcasecmp((string) $column["Field"], "Email") === 0) {
            $emailAllowsNull = strtoupper((string) $column["Null"]) === "YES";
        } elseif (strcasecmp((string) $column["Field"], "No_telepon") === 0) {
            $phoneColumnExists = true;
        }
    }
    $columns->free();

    if ($phone !== "" && !$phoneColumnExists) {
        $respond(503, [
            "status" => "phone_not_configured",
            "message" => "Kolom No_telepon belum tersedia. Jalankan Backend/database/phone_login_migration.sql."
        ]);
    }

    if ($email === "" && !$emailAllowsNull) {
        $respond(503, [
            "status" => "phone_not_configured",
            "message" => "Registrasi nomor telepon saja membutuhkan kolom Email yang boleh NULL. Jalankan Backend/database/phone_only_registration_migration.sql."
        ]);
    }

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
            $respond(409, [
                "status" => "email_exists",
                "message" => "Email sudah terdaftar. Silakan gunakan email lain."
            ]);
        }
    }

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
            $respond(409, [
                "status" => "phone_exists",
                "message" => "Nomor telepon sudah terdaftar. Silakan gunakan nomor lain."
            ]);
        }
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    if ($passwordHash === false) {
        throw new RuntimeException("Gagal membuat hash password.");
    }

    if ($phone === "") {
        $statement = $conn->prepare(
            "INSERT INTO `user` (Nama_user, Email, Password) VALUES (?, ?, ?)"
        );
        $statement->bind_param("sss", $name, $email, $passwordHash);
    } elseif ($email === "") {
        $statement = $conn->prepare(
            "INSERT INTO `user` (Nama_user, Email, No_telepon, Password) VALUES (?, NULL, ?, ?)"
        );
        $statement->bind_param("sss", $name, $phone, $passwordHash);
    } else {
        $statement = $conn->prepare(
            "INSERT INTO `user` (Nama_user, Email, No_telepon, Password) VALUES (?, ?, ?, ?)"
        );
        $statement->bind_param("ssss", $name, $email, $phone, $passwordHash);
    }
    $statement->execute();
    $userId = (int) $conn->insert_id;
    $statement->close();

    $respond(201, [
        "status" => "success",
        "message" => "Pendaftaran berhasil.",
        "data" => [
            "id_user" => $userId,
            "nama" => $name,
            "email" => $email !== "" ? $email : null,
            "telepon" => $phone !== "" ? $phone : null
        ]
    ]);
} catch (mysqli_sql_exception $exception) {
    error_log("Register database error: " . $exception->getMessage());

    if ((int) $exception->getCode() === 1062) {
        $duplicateEmail = $email !== "" &&
            stripos($exception->getMessage(), "email") !== false;
        $respond(409, [
            "status" => $duplicateEmail ? "email_exists" : "phone_exists",
            "message" => $duplicateEmail
                ? "Email sudah terdaftar. Silakan gunakan email lain."
                : "Nomor telepon sudah terdaftar. Silakan gunakan nomor lain."
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
