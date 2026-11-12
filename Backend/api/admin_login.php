<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$respond = static function (int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    $respond(405, ['status' => 'error', 'message' => 'Gunakan metode POST.']);
}

$raw = file_get_contents('php://input');
$body = json_decode((string)$raw, true);
if (!is_array($body)) $body = $_POST;
$username = trim((string)($body['username'] ?? ''));
$password = (string)($body['password'] ?? '');
if ($username === '' || $password === '') {
    $respond(422, ['status' => 'validation_error', 'message' => 'Username dan password wajib diisi.']);
}

try {
    require_once __DIR__ . '/../database/koneksi.php';
    require_once __DIR__ . '/../jwt.php';
    if (!$conn instanceof mysqli) {
        $respond(503, ['status' => 'error', 'message' => 'Database tidak dapat dihubungi. Periksa Backend/database/database.local.php.']);
    }

    // Rate limit per kombinasi IP + username. Tabel dibuat melalui migration keamanan.
    $clientIp = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    if (filter_var($clientIp, FILTER_VALIDATE_IP) === false) $clientIp = 'unknown';
    $ipKey = hash('sha256', $clientIp);
    $usernameKey = hash('sha256', strtolower($username));
    $rate = $conn->prepare('SELECT failed_attempts, locked_until FROM admin_login_attempts WHERE ip_hash = ? AND username_hash = ? LIMIT 1');
    $rate->bind_param('ss', $ipKey, $usernameKey);
    $rate->execute();
    $rateRow = $rate->get_result()->fetch_assoc();
    $rate->close();
    if ($rateRow && !empty($rateRow['locked_until']) && strtotime((string)$rateRow['locked_until']) > time()) {
        header('Retry-After: ' . max(1, strtotime((string)$rateRow['locked_until']) - time()));
        $respond(429, ['status' => 'rate_limited', 'message' => 'Terlalu banyak percobaan login. Coba lagi beberapa menit kemudian.']);
    }

    $tableExists = static function (mysqli $db, string $table): bool {
        $stmt = $db->prepare('SELECT COUNT(*) total FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $stmt->bind_param('s', $table); $stmt->execute();
        $ok = (int)$stmt->get_result()->fetch_assoc()['total'] > 0; $stmt->close(); return $ok;
    };
    $columnExists = static function (mysqli $db, string $table, string $column): bool {
        $stmt = $db->prepare('SELECT COUNT(*) total FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
        $stmt->bind_param('ss', $table, $column); $stmt->execute();
        $ok = (int)$stmt->get_result()->fetch_assoc()['total'] > 0; $stmt->close(); return $ok;
    };

    $admin = null;
    $sourceTable = '';
    // Skema dump proyek memakai tabel admin dengan kolom Id_admin, Nama_admin, Username, Password.
    if ($tableExists($conn, 'admin')) {
        $email = $columnExists($conn, 'admin', 'Email') ? '`Email`' : "''";
        $role = $columnExists($conn, 'admin', 'role') ? '`role`' : "'admin'";
        $stmt = $conn->prepare("SELECT Id_admin id, Nama_admin name, Username username, Password password_hash, $email email, $role role FROM admin WHERE Username = ? LIMIT 1");
        $stmt->bind_param('s', $username); $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc() ?: null; $stmt->close();
        if ($admin) $sourceTable = 'admin';
    }
    // Alternatif jika instalasi menyimpan akun admin di tabel users.
    if (!$admin && $tableExists($conn, 'users')) {
        foreach (['id','username','password','role'] as $col) {
            if (!$columnExists($conn, 'users', $col)) throw new RuntimeException("Kolom users.$col belum tersedia.");
        }
        $name = $columnExists($conn, 'users', 'name') ? '`name`' : '`username`';
        $email = $columnExists($conn, 'users', 'email') ? '`email`' : "''";
        $stmt = $conn->prepare("SELECT id, $name name, username, password password_hash, $email email, role FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username); $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc() ?: null; $stmt->close();
        if ($admin) $sourceTable = 'users';
    }

    $hash = (string)($admin['password_hash'] ?? '');
    $hashed = $hash !== '' && !empty(password_get_info($hash)['algo']);
    // Kompatibilitas migrasi: akun plaintext lama hanya diterima sekali lalu langsung di-hash.
    // Tetap gunakan respons generik agar keberadaan username tidak dapat ditebak.
    $passwordMatches = $hashed ? password_verify($password, $hash) : ($hash !== '' && hash_equals($hash, $password));
    if (!$admin) {
        // Dummy bcrypt verification membantu meratakan waktu respons untuk username yang tidak ada.
        password_verify($password, '$2y$10$49DQUmYPm5.oKJk8jJL08OdQKKmnCoLnpv.VDrH3L9fikW8UPq1OW');
    }
    $valid = $admin && strtolower((string)($admin['role'] ?? 'admin')) === 'admin' && $passwordMatches;
    if (!$valid) {
        $fail = $conn->prepare('INSERT INTO admin_login_attempts (ip_hash, username_hash, failed_attempts, first_failed_at, last_failed_at, locked_until) VALUES (?, ?, 1, NOW(), NOW(), NULL) ON DUPLICATE KEY UPDATE failed_attempts = IF(first_failed_at < NOW() - INTERVAL 15 MINUTE, 1, failed_attempts + 1), first_failed_at = IF(first_failed_at < NOW() - INTERVAL 15 MINUTE, NOW(), first_failed_at), last_failed_at = NOW(), locked_until = IF(IF(first_failed_at < NOW() - INTERVAL 15 MINUTE, 1, failed_attempts + 1) >= 5, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NULL)');
        $fail->bind_param('ss', $ipKey, $usernameKey); $fail->execute(); $fail->close();
        $respond(401, ['status' => 'error', 'message' => 'Username atau password salah.']);
    }
    $clear = $conn->prepare('DELETE FROM admin_login_attempts WHERE ip_hash = ? AND username_hash = ?');
    $clear->bind_param('ss', $ipKey, $usernameKey); $clear->execute(); $clear->close();

    // Migrasikan otomatis password plaintext / hash lama ke password_hash().
    if (!$hashed || password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        if ($newHash !== false) {
            $id = (int)$admin['id'];
            if ($sourceTable === 'admin') {
                $up = $conn->prepare('UPDATE admin SET Password = ? WHERE Id_admin = ?');
            } else {
                $up = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
            }
            $up->bind_param('si', $newHash, $id); $up->execute(); $up->close();
        }
    }

    $id = (int)$admin['id'];
    $expiresAt = time() + 86400;
    $token = generateJwt(['id' => $id, 'sub' => $id, 'username' => (string)$admin['username'], 'role' => 'admin'], 86400);
    // Migrasi admin_sessions wajib diterapkan agar logout dapat mencabut JWT di server.
    $tokenHash = hash('sha256', $token);
    $sessionInsert = $conn->prepare('INSERT INTO admin_sessions (admin_id, token_hash, expires_at) VALUES (?, ?, FROM_UNIXTIME(?))');
    $sessionInsert->bind_param('isi', $id, $tokenHash, $expiresAt);
    $sessionInsert->execute();
    $sessionInsert->close();
    $respond(200, [
        'status' => 'success', 'message' => 'Login berhasil.', 'token' => $token,
        'user' => ['id' => $id, 'name' => (string)($admin['name'] ?? $admin['username']), 'email' => (string)($admin['email'] ?? ''), 'username' => (string)$admin['username'], 'role' => 'admin']
    ]);
} catch (Throwable $e) {
    error_log('Admin login error: ' . $e->getMessage());
    $respond(500, ['status' => 'error', 'message' => 'Terjadi kesalahan server saat login admin. Periksa log PHP dan struktur database.']);
}
