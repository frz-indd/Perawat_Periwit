<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/../middleware/auth.php';
$adminClaims = adminOnly();
$adminId = filter_var($adminClaims['id'] ?? $adminClaims['sub'] ?? null, FILTER_VALIDATE_INT);

$respond = static function (int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
};

if ($adminId === false || $adminId < 1) $respond(403, ['status' => 'forbidden', 'message' => 'Identitas admin pada token tidak valid.']);
if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) !== 'PUT') {
    header('Allow: PUT');
    $respond(405, ['status' => 'method_not_allowed', 'message' => 'Gunakan metode PUT.']);
}
$contentType = strtolower(trim(explode(';', (string)($_SERVER['CONTENT_TYPE'] ?? ''))[0]));
if ($contentType !== 'application/json') $respond(415, ['status' => 'unsupported_media_type', 'message' => 'Gunakan Content-Type: application/json.']);
$input = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) $respond(400, ['status' => 'invalid_json', 'message' => 'Body harus berupa JSON yang valid.']);

$currentPassword = $input['current_password'] ?? null;
$newPassword = $input['new_password'] ?? null;
if (!is_string($currentPassword) || $currentPassword === '' || !is_string($newPassword)) {
    $respond(422, ['status' => 'validation_error', 'message' => 'current_password dan new_password wajib diisi.']);
}
// Bcrypt hanya memproses maksimal 72 byte; batasi agar tidak terjadi pemotongan diam-diam.
if (strlen($newPassword) > 72 || preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $newPassword) !== 1) {
    $respond(422, ['status' => 'validation_error', 'message' => 'Kata sandi baru minimal 8 karakter dan harus memuat huruf besar, huruf kecil, angka, serta karakter khusus (maksimal 72 byte).']);
}
if (hash_equals($currentPassword, $newPassword)) {
    $respond(422, ['status' => 'validation_error', 'message' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.']);
}

try {
    global $conn;
    if (!$conn instanceof mysqli) $respond(503, ['status' => 'service_unavailable', 'message' => 'Koneksi database tidak tersedia.']);
    $conn->set_charset('utf8mb4');

    $table = null;
    foreach (['admin', 'users'] as $candidate) {
        $stmt = $conn->prepare('SELECT COUNT(*) total FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $stmt->bind_param('s', $candidate);
        $stmt->execute();
        $exists = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0) > 0;
        $stmt->close();
        if ($exists) { $table = $candidate; break; }
    }
    if ($table === null) throw new RuntimeException('Tabel admin tidak ditemukan.');
    $idCol = $table === 'admin' ? 'Id_admin' : 'id';
    $passwordCol = $table === 'admin' ? 'Password' : 'password';

    $stmt = $conn->prepare("SELECT `$passwordCol` FROM `$table` WHERE `$idCol` = ? LIMIT 1");
    $id = (int)$adminId;
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) $respond(404, ['status' => 'not_found', 'message' => 'Data admin tidak ditemukan.']);

    $storedHash = (string)$row[$passwordCol];
    // password_verify setara bcrypt.compare untuk hash bcrypt di PHP.
    if (!password_verify($currentPassword, $storedHash)) {
        $respond(400, ['message' => 'Kata sandi saat ini yang Anda masukkan salah.']);
    }

    $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10]);
    if ($newHash === false) throw new RuntimeException('Gagal membuat bcrypt hash.');
    $update = $conn->prepare("UPDATE `$table` SET `$passwordCol` = ? WHERE `$idCol` = ?");
    $update->bind_param('si', $newHash, $id);
    $update->execute();
    $update->close();

    // Cabut semua sesi admin agar token lama/tercuri tidak tetap bisa dipakai.
    $revoke = $conn->prepare('UPDATE admin_sessions SET revoked_at = NOW() WHERE admin_id = ? AND revoked_at IS NULL');
    $revoke->bind_param('i', $id);
    $revoke->execute();
    $revoke->close();

    $respond(200, ['message' => 'Kata sandi berhasil diperbarui. Silakan login kembali.']);
} catch (Throwable $e) {
    error_log('Admin change password error: ' . $e->getMessage());
    $respond(500, ['status' => 'server_error', 'message' => 'Gagal memperbarui kata sandi. Silakan coba lagi.']);
}
