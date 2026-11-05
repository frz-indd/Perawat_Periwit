<?php
declare(strict_types=1);

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

// 1. Validasi Metode HTTP: hanya POST yang diizinkan
if (strtoupper((string) ($_SERVER["REQUEST_METHOD"] ?? "")) !== "POST") {
    header("Allow: POST");
    $respond(405, [
        "status" => "error",
        "message" => "Metode tidak diizinkan. Gunakan POST."
    ]);
}

// 2. Baca Body Request (mendukung JSON dan application/x-www-form-urlencoded / multipart/form-data)
$contentType = strtolower(trim(explode(";", (string) ($_SERVER["CONTENT_TYPE"] ?? ""))[0]));
$body = [];

if ($contentType === "application/json") {
    $rawInput = file_get_contents("php://input");
    $decodedJson = json_decode((string) $rawInput, true);
    if (is_array($decodedJson)) {
        $body = $decodedJson;
    }
} else {
    // Form data / POST standard
    $body = $_POST;
    if (empty($body)) {
        $rawInput = file_get_contents("php://input");
        $decodedJson = json_decode((string) $rawInput, true);
        if (is_array($decodedJson)) {
            $body = $decodedJson;
        }
    }
}

$username = isset($body["username"]) ? trim((string) $body["username"]) : "";
$password = isset($body["password"]) && is_string($body["password"]) ? $body["password"] : "";

// 3. Validasi input server-side (username required, password required)
if ($username === "" || $password === "") {
    $respond(422, [
        "status" => "validation_error",
        "message" => "Username dan password wajib diisi."
    ]);
}

try {
    require_once __DIR__ . "/../database/koneksi.php";
    require_once __DIR__ . "/../jwt.php";

    if (!$conn) {
        $respond(500, [
            "status" => "error",
            "message" => "Koneksi database tidak tersedia."
        ]);
    }

    // Cek apakah tabel `users` ada di database
    $hasUsersTable = false;
    $checkTable = $conn->query("SHOW TABLES LIKE 'users'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $hasUsersTable = true;
    }

    $adminUser = null;

    if ($hasUsersTable) {
        // Query tabel users tempat username = :username dan role = 'admin'
        $stmt = $conn->prepare("SELECT id, username, email, name, password, role FROM `users` WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row) {
            // Jika role !== 'admin', ditolak sesuai requirement
            if (($row["role"] ?? "") !== "admin") {
                $respond(401, [
                    "message" => "Username atau password salah."
                ]);
            }
            $adminUser = [
                "id" => (int) $row["id"],
                "name" => (string) ($row["name"] ?? $row["username"]),
                "email" => (string) ($row["email"] ?? "admin@majakusample.com"),
                "username" => (string) $row["username"],
                "role" => (string) $row["role"],
                "password_hash" => (string) $row["password"],
                "source_table" => "users"
            ];
        }
    }

    // Jika tidak ditemukan di tabel `users` (atau tabel `users` belum ada), fallback cek tabel `admin`
    if (!$adminUser) {
        $stmt = $conn->prepare("SELECT Id_admin, Nama_admin, Username, Password, " . 
            "(CASE WHEN EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin' AND COLUMN_NAME = 'Email') THEN Email ELSE NULL END) as Email, " .
            "(CASE WHEN EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin' AND COLUMN_NAME = 'role') THEN role ELSE 'admin' END) as role " .
            "FROM `admin` WHERE Username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row) {
            $userRole = !empty($row["role"]) ? (string) $row["role"] : "admin";
            if ($userRole !== "admin") {
                $respond(401, [
                    "message" => "Username atau password salah."
                ]);
            }
            $adminUser = [
                "id" => (int) $row["Id_admin"],
                "name" => (string) ($row["Nama_admin"] ?? "Administrator MajaKu"),
                "email" => !empty($row["Email"]) ? (string) $row["Email"] : "admin@majakusample.com",
                "username" => (string) $row["Username"],
                "role" => $userRole,
                "password_hash" => (string) $row["Password"],
                "source_table" => "admin"
            ];
        }
    }

    // Jika user tidak ditemukan atau role !== 'admin', kembalikan HTTP 401
    if (!$adminUser) {
        $respond(401, [
            "message" => "Username atau password salah."
        ]);
    }

    // Verifikasi password menggunakan bcrypt / password_verify
    $passwordHash = $adminUser["password_hash"];
    $passwordInfo = password_get_info($passwordHash);
    $isBcryptHash = !empty($passwordInfo["algo"]);

    $passwordMatches = $isBcryptHash
        ? password_verify($password, $passwordHash)
        : hash_equals($passwordHash, $password);

    if (!$passwordMatches) {
        $respond(401, [
            "message" => "Username atau password salah."
        ]);
    }

    // Jika password lama berbentuk plaintext, rehash menjadi bcrypt
    if (!$isBcryptHash || password_needs_rehash($passwordHash, PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        if ($newHash !== false) {
            if ($adminUser["source_table"] === "users") {
                $up = $conn->prepare("UPDATE `users` SET password = ? WHERE id = ?");
                $up->bind_param("si", $newHash, $adminUser["id"]);
                $up->execute();
                $up->close();
            } else {
                $up = $conn->prepare("UPDATE `admin` SET Password = ? WHERE Id_admin = ?");
                $up->bind_param("si", $newHash, $adminUser["id"]);
                $up->execute();
                $up->close();
            }
        }
    }

    // Generate JSON Web Token (JWT) dengan payload { id: user.id, role: user.role } dan expiration (1 hari = 86400 detik)
    $payload = [
        "id" => $adminUser["id"],
        "role" => $adminUser["role"]
    ];
    $token = generateJwt($payload, 86400);

    // Format Response HTTP 200 persis sesuai screenshot attachment:
    // {
    //   "status": "success",
    //   "message": "Login berhasil.",
    //   "token": "eyJhbGciOiJIUzI1NiIs...",
    //   "user": {
    //     "id": 1,
    //     "name": "Administrator MajaKu",
    //     "email": "admin@majakusample.com",
    //     "role": "admin"
    //   }
    // }
    $respond(200, [
        "status" => "success",
        "message" => "Login berhasil.",
        "token" => $token,
        "user" => [
            "id" => $adminUser["id"],
            "name" => $adminUser["name"],
            "email" => $adminUser["email"],
            "role" => $adminUser["role"]
        ]
    ]);

} catch (Throwable $exception) {
    error_log("Admin API Login Error: " . $exception->getMessage());
    $respond(500, [
        "status" => "error",
        "message" => "Terjadi kesalahan pada server."
    ]);
}

