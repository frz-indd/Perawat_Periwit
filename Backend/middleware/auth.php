<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/jwt.php';

/**
 * Ekstrak token JWT dari header Authorization (Bearer <token>).
 *
 * @return string|null Token string jika ditemukan, atau null.
 */
function extractBearerToken(): ?string
{
    $authHeader = '';

    // Hosting Apache/FastCGI tertentu tidak meneruskan Authorization secara otomatis.
    foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $serverKey) {
        if (!empty($_SERVER[$serverKey])) {
            $authHeader = trim((string) $_SERVER[$serverKey]);
            break;
        }
    }

    // Fallback header untuk variasi konfigurasi Apache/proxy.
    if ($authHeader === '' && function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            foreach ($headers as $key => $value) {
                if (strcasecmp((string) $key, 'Authorization') === 0) {
                    $authHeader = trim((string) $value);
                    break;
                }
            }
        }
    }
    if ($authHeader === '' && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (is_array($headers)) {
            foreach ($headers as $key => $value) {
                if (strcasecmp((string) $key, 'Authorization') === 0) {
                    $authHeader = trim((string) $value);
                    break;
                }
            }
        }
    }

    if (preg_match('/^\s*Bearer\s+(.+)$/i', $authHeader, $matches)) {
        return trim($matches[1]);
    }

    // Fallback cookie same-origin jika hosting menghapus Authorization.
    $cookieToken = $_COOKIE['admin_token'] ?? '';
    if (is_string($cookieToken) && trim($cookieToken) !== '') {
        return trim($cookieToken);
    }

    return null;
}

/**
 * Middleware otorisasi khusus Admin (RBAC).
 *
 * Mengambil token dari header Authorization: Bearer <token>,
 * memverifikasi token dengan secret key,
 * dan memastikan decoded role adalah 'admin'.
 *
 * Jika tidak valid atau bukan admin:
 * Mengembalikan HTTP 403 Forbidden dengan payload:
 * {"message": "Akses ditolak: Anda tidak memiliki wewenang admin."}
 *
 * @return array Payload JWT yang sudah didecode (id, role, dll.)
 */
function adminOnly(): array
{
    $token = extractBearerToken();

    if ($token === null || $token === '') {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'message' => 'Akses ditolak: Anda tidak memiliki wewenang admin.'
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    try {
        $decoded = verifyJwt($token);
    } catch (Throwable $e) {
        error_log('JWT verification configuration error: ' . $e->getMessage());
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['message' => 'Layanan autentikasi belum dikonfigurasi dengan benar.']);
        exit;
    }

    $role = $decoded['role'] ?? null;
    $isAdmin = ($role === 'admin' || $role === 1 || $role === '1');
    if ($decoded === null || !$isAdmin) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'message' => 'Akses ditolak: Anda tidak memiliki wewenang admin.'
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    // JWT hanya sah selama hash-nya tercatat aktif di database.
    // Penting: koneksi harus berada di scope global agar endpoint yang
    // memanggil adminOnly() dapat memakai kembali variabel $conn.
    global $conn;
    require_once dirname(__DIR__) . '/database/koneksi.php';
    if (!isset($conn) || !($conn instanceof mysqli)) {
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['message' => 'Layanan autentikasi admin tidak tersedia.']);
        exit;
    }
    $tokenHash = hash('sha256', $token);
    $activeSession = null;

    // Login admin mencatat token di admin_sessions.
    // Jangan mengakses user_sessions karena tabel itu bukan bagian dari
    // skema admin standar proyek ini.
    try {
        $checkAdminSession = $conn->prepare(
            'SELECT id FROM admin_sessions
             WHERE token_hash = ?
               AND revoked_at IS NULL
               AND expires_at > NOW()
             LIMIT 1'
        );
        $checkAdminSession->bind_param('s', $tokenHash);
        $checkAdminSession->execute();
        $activeSession = $checkAdminSession->get_result()->fetch_assoc();
        $checkAdminSession->close();
    } catch (mysqli_sql_exception $e) {
        error_log('Admin session lookup failed: ' . $e->getMessage());
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'service_unavailable',
            'message' => 'Tabel sesi admin belum tersedia. Jalankan Backend/database/admin_sessions_migration.sql.'
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    if (!$activeSession) {
        $checkAdminSession = $conn->prepare('SELECT id FROM admin_sessions WHERE token_hash = ? AND revoked_at IS NULL AND expires_at > NOW() LIMIT 1');
        if ($checkAdminSession !== false) {
            $checkAdminSession->bind_param('s', $tokenHash);
            $checkAdminSession->execute();
            $activeSession = $checkAdminSession->get_result()->fetch_assoc();
            $checkAdminSession->close();
        }
    }

    if (!$activeSession) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['message' => 'Sesi admin sudah berakhir. Silakan login kembali.']);
        exit;
    }

    // Pastikan payload memiliki role yang kompatibel dengan UI frontend dan API
    $decoded['role_id'] = 1;
    $decoded['role'] = 'admin';

    return $decoded;
}
