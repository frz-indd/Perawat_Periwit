<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function respond(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function normalizePhone(string $phone): string
{
    return preg_replace('/[\s()-]/', '', trim($phone)) ?? '';
}

function duplicateIdentityExists(mysqli $conn, string $email, string $phone): ?string
{
    if ($email !== '') {
        $stmt = $conn->prepare(
            "SELECT Id_user FROM `user` WHERE LOWER(TRIM(Email)) = LOWER(?) LIMIT 1"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        if ($exists) return 'email';
    }

    if ($phone !== '') {
        $digits = ltrim($phone, '+');
        $local = str_starts_with($phone, '+62') ? '0' . substr($phone, 3) : $phone;
        $stmt = $conn->prepare(
            "SELECT Id_user FROM `user`
             WHERE REPLACE(REPLACE(REPLACE(REPLACE(TRIM(No_telepon), ' ', ''), '-', ''), '(', ''), ')', '')
                   IN (?, ?, ?)
             LIMIT 1"
        );
        $stmt->bind_param('sss', $phone, $digits, $local);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        if ($exists) return 'phone';
    }

    return null;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respond(405, ['status' => 'validation_error', 'message' => 'Metode tidak diizinkan.']);
}

$identity = trim((string) ($_POST['identity'] ?? ''));
$password = $_POST['password'] ?? null;
$confirmPassword = $_POST['confirmPassword'] ?? null;
$email = '';
$phone = '';

if ($identity === '') {
    respond(422, ['status' => 'validation_error', 'field' => 'identity', 'message' => 'Email/No telepon wajib di isi']);
}

if (filter_var($identity, FILTER_VALIDATE_EMAIL) !== false) {
    $email = strtolower($identity);
} else {
    $phone = normalizePhone($identity);
    if (preg_match('/^\+628[1-9][0-9]{6,11}$/', $phone) !== 1) {
        respond(422, ['status' => 'validation_error', 'field' => 'identity', 'message' => 'Nomor telepon harus menggunakan awalan +62']);
    }
}

if (!is_string($password) || trim($password) === '') {
    respond(422, ['status' => 'validation_error', 'field' => 'password', 'message' => 'Password wajib di isi']);
}
if (strlen($password) < 8) {
    respond(422, ['status' => 'validation_error', 'field' => 'password', 'message' => 'Password harus terdiri dari minimal 8 karakter']);
}
if (!is_string($confirmPassword) || trim($confirmPassword) === '') {
    respond(422, ['status' => 'validation_error', 'field' => 'confirmPassword', 'message' => 'Verifikasi password wajib di isi']);
}
if ($password !== $confirmPassword) {
    respond(422, ['status' => 'validation_error', 'field' => 'confirmPassword', 'message' => 'Konfirmasi password tidak sesuai']);
}

try {
    require_once __DIR__ . '/../database/koneksi.php';
    if (!($conn instanceof mysqli)) {
        respond(503, ['status' => 'server_error', 'message' => 'Koneksi database tidak tersedia.']);
    }

    // Baca metadata agar kolom kontak yang tidak dipakai tidak terisi string kosong.
    $columns = $conn->query('SHOW COLUMNS FROM `user`');
    $columnInfo = [];
    while ($column = $columns->fetch_assoc()) {
        $columnInfo[strtolower($column['Field'])] = $column;
    }
    $columns->free();

    $hasEmail = isset($columnInfo['email']);
    $hasPhone = isset($columnInfo['no_telepon']);
    $emailAllowsNull = $hasEmail && strtoupper((string) $columnInfo['email']['Null']) === 'YES';
    $phoneAllowsNull = $hasPhone && strtoupper((string) $columnInfo['no_telepon']['Null']) === 'YES';

    if (!$hasEmail) {
        respond(503, ['status' => 'server_error', 'message' => 'Kolom Email tidak ditemukan pada tabel user.']);
    }
    if ($phone !== '' && !$hasPhone) {
        respond(503, ['status' => 'server_error', 'message' => 'Kolom No_telepon belum tersedia pada database.']);
    }
    if ($email === '' && !$emailAllowsNull) {
        respond(503, ['status' => 'server_error', 'message' => 'Kolom Email harus memperbolehkan NULL untuk register menggunakan nomor telepon. Jalankan migrasi register.']);
    }
    if ($phone === '' && $hasPhone && !$phoneAllowsNull) {
        respond(503, ['status' => 'server_error', 'message' => 'Kolom No_telepon harus memperbolehkan NULL untuk register menggunakan email. Jalankan migrasi register.']);
    }

    $duplicate = duplicateIdentityExists($conn, $email, $phone);
    if ($duplicate === 'email') {
        respond(409, ['status' => 'email_exists', 'field' => 'identity', 'message' => 'Email sudah terdaftar. Silakan gunakan email lain.']);
    }
    if ($duplicate === 'phone') {
        respond(409, ['status' => 'phone_exists', 'field' => 'identity', 'message' => 'Nomor telepon sudah terdaftar. Silakan gunakan nomor lain.']);
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    if ($passwordHash === false) {
        throw new RuntimeException('Gagal membuat hash password.');
    }

    if ($email !== '') {
        if ($hasPhone) {
            $statement = $conn->prepare('INSERT INTO `user` (Email, No_telepon, Password) VALUES (?, NULL, ?)');
        } else {
            $statement = $conn->prepare('INSERT INTO `user` (Email, Password) VALUES (?, ?)');
        }
        $statement->bind_param('ss', $email, $passwordHash);
    } else {
        $statement = $conn->prepare('INSERT INTO `user` (Email, No_telepon, Password) VALUES (NULL, ?, ?)');
        $statement->bind_param('ss', $phone, $passwordHash);
    }

    $statement->execute();
    $userId = (int) $conn->insert_id;
    $statement->close();

    respond(201, [
        'status' => 'success',
        'message' => 'Pendaftaran berhasil.',
        'data' => [
            'id_user' => $userId,
            'email' => $email !== '' ? $email : null,
            'telepon' => $phone !== '' ? $phone : null
        ]
    ]);
} catch (mysqli_sql_exception $exception) {
    error_log('Register database error: ' . $exception->getMessage());

    // Jangan menyebut email duplikat untuk setiap error UNIQUE.
    // Identifikasi ulang kontak; benturan indeks lain dilaporkan terpisah.
    if (isset($conn) && $conn instanceof mysqli && (int) $exception->getCode() === 1062) {
        try {
            $duplicate = duplicateIdentityExists($conn, $email, $phone);
            if ($duplicate === 'email') {
                respond(409, ['status' => 'email_exists', 'field' => 'identity', 'message' => 'Email sudah terdaftar. Silakan gunakan email lain.']);
            }
            if ($duplicate === 'phone') {
                respond(409, ['status' => 'phone_exists', 'field' => 'identity', 'message' => 'Nomor telepon sudah terdaftar. Silakan gunakan nomor lain.']);
            }
        } catch (Throwable $lookupError) {
            error_log('Register duplicate lookup error: ' . $lookupError->getMessage());
        }
        respond(409, ['status' => 'duplicate_conflict', 'field' => 'identity', 'message' => 'Terjadi benturan pada data unik database. Periksa indeks tabel user; data kontak yang dimasukkan tidak ditemukan sebagai duplikat.']);
    }

    respond(500, ['status' => 'server_error', 'message' => 'Terjadi kesalahan pada server. Silakan coba lagi.']);
} catch (Throwable $exception) {
    error_log('Register error: ' . $exception->getMessage());
    respond(500, ['status' => 'server_error', 'message' => 'Terjadi kesalahan pada server. Silakan coba lagi.']);
}
