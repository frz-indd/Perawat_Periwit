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

if ($adminId === false || $adminId < 1) {
    $respond(403, ['status' => 'forbidden', 'message' => 'Identitas admin pada token tidak valid.']);
}
$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!in_array($method, ['GET', 'PUT'], true)) {
    header('Allow: GET, PUT');
    $respond(405, ['status' => 'method_not_allowed', 'message' => 'Gunakan GET atau PUT.']);
}

function adminProfileColumnExists(mysqli $db, string $table, string $column): bool
{
    $stmt = $db->prepare('SELECT COUNT(*) total FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $exists = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0) > 0;
    $stmt->close();
    return $exists;
}

try {
    global $conn;
    if (!$conn instanceof mysqli) {
        $respond(503, ['status' => 'service_unavailable', 'message' => 'Koneksi database tidak tersedia.']);
    }
    $conn->set_charset('utf8mb4');

    // Login admin proyek memakai tabel `admin`; `users` didukung sebagai fallback.
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

    $legacy = $table === 'admin';
    $idCol = $legacy ? 'Id_admin' : 'id';
    $nameCol = $legacy ? 'Nama_admin' : 'name';
    $usernameCol = 'username';
    $imageCol = $legacy
        ? (adminProfileColumnExists($conn, $table, 'Foto_admin') ? 'Foto_admin' : (adminProfileColumnExists($conn, $table, 'image_url') ? 'image_url' : null))
        : (adminProfileColumnExists($conn, $table, 'image_url') ? 'image_url' : (adminProfileColumnExists($conn, $table, 'profile_image') ? 'profile_image' : null));

    if (!adminProfileColumnExists($conn, $table, $idCol) || !adminProfileColumnExists($conn, $table, $nameCol) || !adminProfileColumnExists($conn, $table, $usernameCol)) {
        throw new RuntimeException('Kolom profil admin tidak lengkap.');
    }
    if ($method === 'PUT' && $imageCol === null) {
        $respond(409, ['status' => 'migration_required', 'message' => 'Kolom foto profil belum tersedia. Jalankan migration admin_profile_migration.sql.']);
    }

    $selectImage = $imageCol !== null ? '`' . $imageCol . '`' : 'NULL';
    if ($method === 'GET') {
        $sql = "SELECT `$idCol` id, `$nameCol` name, `$usernameCol` username, $selectImage image_url FROM `$table` WHERE `$idCol` = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $id = (int)$adminId;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) $respond(404, ['status' => 'not_found', 'message' => 'Data admin tidak ditemukan.']);
        $respond(200, ['status' => 'success', 'message' => 'Profil admin berhasil diambil.', 'data' => [
            'id' => (int)$row['id'], 'name' => (string)$row['name'], 'username' => (string)$row['username'],
            'image_url' => $row['image_url'] !== null && $row['image_url'] !== '' ? (string)$row['image_url'] : null
        ]]);
    }

    $contentType = strtolower(trim(explode(';', (string)($_SERVER['CONTENT_TYPE'] ?? ''))[0]));
    if ($contentType !== 'application/json') $respond(415, ['status' => 'unsupported_media_type', 'message' => 'Gunakan Content-Type: application/json.']);
    $input = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) $respond(400, ['status' => 'invalid_json', 'message' => 'Body harus berupa JSON yang valid.']);

    $name = $input['name'] ?? $input['nama'] ?? null;
    $imageProvided = array_key_exists('image_url', $input);
    $imageUrl = $imageProvided ? $input['image_url'] : null;
    if (!is_string($name) || ($imageProvided && $imageUrl !== null && !is_string($imageUrl))) {
        $respond(422, ['status' => 'validation_error', 'message' => 'Nama wajib berupa teks dan image_url (jika dikirim) harus berupa URL teks atau null.']);
    }
    $name = trim($name);
    $nameLength = function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name);
    if ($name === '' || $nameLength > 255) $respond(422, ['status' => 'validation_error', 'message' => 'Nama wajib diisi dan maksimal 255 karakter.']);
    if ($imageProvided && $imageUrl !== null) {
        $imageUrl = trim($imageUrl);
        if ($imageUrl === '') $imageUrl = null;
        elseif (strlen($imageUrl) > 2048 || filter_var($imageUrl, FILTER_VALIDATE_URL) === false || !in_array(strtolower((string)parse_url($imageUrl, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            $respond(422, ['status' => 'validation_error', 'message' => 'image_url harus berupa URL HTTP atau HTTPS yang valid (maksimal 2048 karakter).']);
        }
    }

    $id = (int)$adminId;
    if ($imageProvided) {
        $sql = "UPDATE `$table` SET `$nameCol` = ?, `$imageCol` = ? WHERE `$idCol` = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssi', $name, $imageUrl, $id);
    } else {
        $sql = "UPDATE `$table` SET `$nameCol` = ? WHERE `$idCol` = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $name, $id);
    }
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    // affected_rows=0 can mean unchanged values, so confirm the row still exists.
    $check = $conn->prepare("SELECT `$idCol` FROM `$table` WHERE `$idCol` = ? LIMIT 1");
    $check->bind_param('i', $id);
    $check->execute();
    $exists = (bool)$check->get_result()->fetch_assoc();
    $check->close();
    if (!$exists) $respond(404, ['status' => 'not_found', 'message' => 'Data admin tidak ditemukan.']);

    $savedImage = $imageUrl;
    if (!$imageProvided && $imageCol !== null) {
        $photoStmt = $conn->prepare("SELECT `$imageCol` image_url FROM `$table` WHERE `$idCol` = ? LIMIT 1");
        $photoStmt->bind_param('i', $id);
        $photoStmt->execute();
        $photoRow = $photoStmt->get_result()->fetch_assoc();
        $photoStmt->close();
        $savedImage = $photoRow['image_url'] ?? null;
    }
    $respond(200, ['status' => 'success', 'message' => 'Profil admin berhasil diperbarui.', 'data' => [
        'id' => $id, 'name' => $name, 'image_url' => $savedImage
    ]]);
} catch (Throwable $e) {
    error_log('Admin profile API error: ' . $e->getMessage());
    $respond(500, ['status' => 'server_error', 'message' => 'Gagal memproses profil admin.']);
}
