<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../middleware/auth.php';

// Ambil token dari header Authorization (jika ada)
$token = extractBearerToken();

if ($token !== null && $token !== '') {
    $tokenHash = hash('sha256', $token);
    global $conn;
    require_once __DIR__ . '/../database/koneksi.php';
    if (isset($conn) && $conn instanceof mysqli) {
        $now = date('Y-m-d H:i:s');
        try {
            $revokeAdminStmt = $conn->prepare(
                'UPDATE admin_sessions
                 SET revoked_at = ?
                 WHERE token_hash = ? AND revoked_at IS NULL'
            );
            if ($revokeAdminStmt !== false) {
                $revokeAdminStmt->bind_param('ss', $now, $tokenHash);
                $revokeAdminStmt->execute();
                $revokeAdminStmt->close();
            }
        } catch (Throwable $e) {
            error_log('Admin logout session revoke: ' . $e->getMessage());
        }
    }
}

// Hapus cookie sesi admin (jika ada).
if (isset($_COOKIE['admin_token'])) {
    setcookie('admin_token', '', time() - 3600, '/', '', false, false);
}
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
], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
exit;