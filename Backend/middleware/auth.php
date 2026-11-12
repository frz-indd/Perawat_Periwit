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

    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = (string) $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (is_array($headers)) {
            foreach ($headers as $key => $value) {
                if (strcasecmp($key, 'Authorization') === 0) {
                    $authHeader = (string) $value;
                    break;
                }
            }
        }
    }

    if (preg_match('/^\s*Bearer\s+(.+)$/i', $authHeader, $matches)) {
        return trim($matches[1]);
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

    $decoded = verifyJwt($token);

    if ($decoded === null || ($decoded['role'] ?? '') !== 'admin') {
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
    $check = $conn->prepare('SELECT id FROM admin_sessions WHERE token_hash = ? AND revoked_at IS NULL AND expires_at > NOW() LIMIT 1');
    $check->bind_param('s', $tokenHash);
    $check->execute();
    $activeSession = $check->get_result()->fetch_assoc();
    $check->close();
    if (!$activeSession) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['message' => 'Sesi admin sudah berakhir. Silakan login kembali.']);
        exit;
    }

    return $decoded;
}
