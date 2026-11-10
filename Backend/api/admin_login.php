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

if (strtoupper((string) ($_SERVER["REQUEST_METHOD"] ?? "")) !== "POST") {
    header("Allow: POST");
    $respond(405, [
        "status" => "error",
        "message" => "Metode tidak diizinkan. Gunakan POST."
    ]);
}

$contentType = strtolower(trim(explode(";", (string) ($_SERVER["CONTENT_TYPE"] ?? ""))[0]));
$body = [];

if ($contentType === "application/json") {
    $rawInput = file_get_contents("php://input");
    $decodedJson = json_decode((string) $rawInput, true);
    if (is_array($decodedJson)) {
        $body = $decodedJson;
    }
} else {
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

    $hasUsersTable = false;
    $checkTable = $conn->query("SHOW TABLES LIKE 'users'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $hasUsersTable = true;
    }

    $passwordMatchesAnyAdmin = static function () use ($conn, $hasUsersTable, $password): bool {
        $hashes = [];

        if ($hasUsersTable) {
            $result = $conn->query("SELECT password FROM `users` WHERE role = 'admin'");
            while ($result && ($row = $result->fetch_assoc())) {
                $hashes[] = (string) $row["password"];
            }
        }

        $result = $conn->query("SELECT Password FROM `admin`");
        while ($result && ($row = $result->fetch_assoc())) {
            $hashes[] = (string) $row["Password"];
        }

        foreach ($hashes as $hash) {
            $info = password_get_info($hash);
            $matches = !empty($info["algo"])
                ? password_verify($password, $hash)
                : hash_equals($hash, $password);
            if ($matches) {
                return true;
            }
        }

        return false;
    };

    $rejectUsername = static function () use ($respond, $passwordMatchesAnyAdmin): void {
        if ($passwordMatchesAnyAdmin()) {
            $respond(401, [
                "field" => "username",
                "message" => "Username yang anda masukkan salah"
            ]);
        }

        $respond(401, [
            "message" => "Username dan password yang anda masukkan salah",
            "errors" => [
                "username" => "Username yang anda masukkan salah",
                "password" => "Password yang anda masukkan salah"
            ]
        ]);
    };

    $adminUser = null;

    if ($hasUsersTable) {
        $stmt = $conn->prepare("SELECT id, username, email, name, password, role FROM `users` WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row) {
            if (($row["role"] ?? "") !== "admin") {
                $rejectUsername();
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
                $rejectUsername();
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

    if (!$adminUser) {
        $rejectUsername();
    }

    $passwordHash = $adminUser["password_hash"];
    $passwordInfo = password_get_info($passwordHash);
    $isBcryptHash = !empty($passwordInfo["algo"]);

    $passwordMatches = $isBcryptHash
        ? password_verify($password, $passwordHash)
        : hash_equals($passwordHash, $password);

    if (!$passwordMatches) {
        $respond(401, [
            "field" => "password",
            "message" => "Password yang anda masukkan salah"
        ]);
    }

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

    $payload = [
        "id" => $adminUser["id"],
        "role" => $adminUser["role"]
    ];
    $token = generateJwt($payload, 86400);

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