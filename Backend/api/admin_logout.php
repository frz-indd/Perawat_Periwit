<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// Include adminOnly middleware (already checks JWT and session validity)
require_once __DIR__ . '/../middleware/auth.php';

// Protect endpoint – hanya admin yang boleh mengakses
$admin = adminOnly(); // will exit on failure

// Ambil token dari header Authorization (sama seperti adminOnly menggunakannya)
$token = extractBearerToken();
if ($token === null) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Token tidak ditemukan.']);
    exit;
}

// Hash token untuk pencocokan di tabel sesi
$tokenHash = hash('sha256', $token);

// Pastikan koneksi DB tersedia (adminOnly sudah memuat $conn secara global)
global $conn;
require_once __DIR__ . '/../database/koneksi.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'Koneksi database tidak tersedia.']);
    exit;
}

// Cabut token admin dari tabel sesi admin.
$now = date('Y-m-d H:i:s');
try {
    $revokeAdminStmt = $conn->prepare(
        'UPDATE admin_sessions
         SET revoked_at = ?
         WHERE token_hash = ? AND revoked_at IS NULL'
    );
    $revokeAdminStmt->bind_param('ss', $now, $tokenHash);
    $revokeAdminStmt->execute();
    $revokeAdminStmt->close();
} catch (mysqli_sql_exception $e) {
    error_log('Admin logout session update failed: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'status' => 'service_unavailable',
        'message' => 'Tabel sesi admin belum tersedia.'
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

// Hapus cookie HTTP‑Only (jika ada). Nama cookie biasanya 'jwt' atau 'token'.
// Karena implementasi tidak diketahui pasti, coba hapus cookie dengan nama umum.
if (isset($_COOKIE['jwt'])) {
    setcookie('jwt', '', time() - 3600, '/', '', true, true);
}
if (isset($_COOKIE['token'])) {
    setcookie('token', '', time() - 3600, '/', '', true, true);
}

http_response_code(200);
echo json_encode([
    'status'  => 'success',
    'message' => 'Sesi logout berhasil diakhiri.'
]);
?>