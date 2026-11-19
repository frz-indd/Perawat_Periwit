<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$respond = static function (int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
};

// 1. Validasi Metode Request (Hanya POST)
$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? ''));
if ($method !== 'POST') {
    header('Allow: POST');
    $respond(405, [
        'status' => 'method_not_allowed',
        'message' => 'Metode tidak diizinkan. Gunakan metode POST.'
    ]);
}

// 2. Baca Body Request (Mendukung JSON dan Form Data)
$rawInput = (string)file_get_contents('php://input');
$input = [];
if ($rawInput !== '') {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
}
if (empty($input) && !empty($_POST)) {
    $input = $_POST;
}

$emailRaw = $input['email'] ?? null;
$newPassword = $input['new_password'] ?? $input['password'] ?? $input['newPassword'] ?? null;
$confirmPassword = $input['confirm_password'] ?? $input['confirmPassword'] ?? null;

// 3. Validasi Format Email
if (!is_string($emailRaw) || trim($emailRaw) === '') {
    $respond(422, [
        'status' => 'validation_error',
        'message' => 'Email wajib diisi.'
    ]);
}

$email = trim($emailRaw);
if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    $respond(422, [
        'status' => 'validation_error',
        'message' => 'Format email tidak valid.'
    ]);
}

// 4. Validasi Password Baru (jika disertakan)
if ($newPassword !== null) {
    if (!is_string($newPassword) || trim($newPassword) === '') {
        $respond(422, [
            'status' => 'validation_error',
            'message' => 'Password baru tidak boleh kosong.'
        ]);
    }

    if (strlen($newPassword) < 8) {
        $respond(422, [
            'status' => 'validation_error',
            'message' => 'Password baru minimal 8 karakter.'
        ]);
    }

    if (strlen($newPassword) > 72) {
        $respond(422, [
            'status' => 'validation_error',
            'message' => 'Password baru maksimal 72 karakter.'
        ]);
    }

    if (is_string($confirmPassword) && $confirmPassword !== '' && !hash_equals($newPassword, $confirmPassword)) {
        $respond(422, [
            'status' => 'validation_error',
            'message' => 'Konfirmasi password tidak cocok dengan password baru.'
        ]);
    }
}

// 5. Verifikasi Akun dan Role Admin di Database
try {
    require_once __DIR__ . '/../database/koneksi.php';
    if (!isset($conn) || !($conn instanceof mysqli)) {
        $respond(503, [
            'status' => 'service_unavailable',
            'message' => 'Koneksi database tidak tersedia.'
        ]);
    }
    $conn->set_charset('utf8mb4');

    $adminAccount = null;
    $sourceTable = '';

    // Cek di tabel user terlebih dahulu
    $checkUser = $conn->prepare('SELECT Id_user, Nama_user, Username, Email, Password, role FROM `user` WHERE LOWER(Email) = LOWER(?) LIMIT 1');
    $checkUser->bind_param('s', $email);
    $checkUser->execute();
    $userRow = $checkUser->get_result()->fetch_assoc();
    $checkUser->close();

    if ($userRow) {
        $adminAccount = $userRow;
        $sourceTable = 'user';
    } else {
        // Fallback: periksa tabel admin jika tabel admin memiliki kolom Email
        $colCheck = $conn->query("SHOW COLUMNS FROM `admin` LIKE 'Email'");
        if ($colCheck && $colCheck->num_rows > 0) {
            $checkAdmin = $conn->prepare('SELECT Id_admin, Nama_admin, Username, Email, Password, 1 AS role FROM `admin` WHERE LOWER(Email) = LOWER(?) LIMIT 1');
            $checkAdmin->bind_param('s', $email);
            $checkAdmin->execute();
            $adminRow = $checkAdmin->get_result()->fetch_assoc();
            $checkAdmin->close();
            if ($adminRow) {
                $adminAccount = $adminRow;
                $sourceTable = 'admin';
            }
        }
    }

    if (!$adminAccount) {
        $respond(404, [
            'status' => 'not_found',
            'message' => 'Akun admin dengan email tersebut tidak ditemukan.'
        ]);
    }

    // Pastikan akun memiliki role Admin (role = 1)
    $role = $adminAccount['role'] ?? null;
    $isAdmin = ($role === 1 || $role === '1' || strtolower((string)$role) === 'admin');

    if (!$isAdmin) {
        $respond(403, [
            'status' => 'forbidden',
            'message' => 'Akses ditolak: Akun tersebut bukan merupakan akun admin.'
        ]);
    }

    // Jika hanya memvalidasi email (new_password tidak dikirim)
    if ($newPassword === null) {
        $respond(200, [
            'status' => 'success',
            'message' => 'Email admin terverifikasi. Silakan masukkan password baru.',
            'data' => [
                'email' => $email,
                'can_reset' => true
            ]
        ]);
    }

    // 6. Hashing Password Baru
    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
    if ($newHash === false) {
        throw new RuntimeException('Gagal melakukan hashing kata sandi.');
    }

    // 7. Perbarui Data di Database
    if ($sourceTable === 'user') {
        $userId = (int)$adminAccount['Id_user'];
        $update = $conn->prepare('UPDATE `user` SET Password = ? WHERE Id_user = ?');
        $update->bind_param('si', $newHash, $userId);
        $update->execute();
        $update->close();

        // Jika username cocok dengan akun di tabel admin, sinkronkan juga
        $username = $adminAccount['Username'] ?? null;
        if (!empty($username)) {
            $syncAdmin = $conn->prepare('UPDATE `admin` SET Password = ? WHERE Username = ?');
            if ($syncAdmin !== false) {
                $syncAdmin->bind_param('ss', $newHash, $username);
                $syncAdmin->execute();
                $syncAdmin->close();
            }
        }

        // Cabut sesi aktif di user_sessions & admin_sessions
        $revoke1 = $conn->prepare('UPDATE user_sessions SET revoked_at = NOW() WHERE user_id = ? AND revoked_at IS NULL');
        if ($revoke1 !== false) {
            $revoke1->bind_param('i', $userId);
            $revoke1->execute();
            $revoke1->close();
        }
        $revoke2 = $conn->prepare('UPDATE admin_sessions SET revoked_at = NOW() WHERE admin_id = ? AND revoked_at IS NULL');
        if ($revoke2 !== false) {
            $revoke2->bind_param('i', $userId);
            $revoke2->execute();
            $revoke2->close();
        }
    } elseif ($sourceTable === 'admin') {
        $adminId = (int)$adminAccount['Id_admin'];
        $update = $conn->prepare('UPDATE `admin` SET Password = ? WHERE Id_admin = ?');
        $update->bind_param('si', $newHash, $adminId);
        $update->execute();
        $update->close();

        $revoke = $conn->prepare('UPDATE admin_sessions SET revoked_at = NOW() WHERE admin_id = ? AND revoked_at IS NULL');
        if ($revoke !== false) {
            $revoke->bind_param('i', $adminId);
            $revoke->execute();
            $revoke->close();
        }
    }

    $respond(200, [
        'status' => 'success',
        'message' => 'Password admin berhasil diperbarui. Silakan login kembali dengan password baru.'
    ]);
} catch (Throwable $e) {
    error_log('Admin forgot password error: ' . $e->getMessage());
    $respond(500, [
        'status' => 'server_error',
        'message' => 'Terjadi kesalahan pada server saat memperbarui password. Silakan coba lagi.'
    ]);
}
