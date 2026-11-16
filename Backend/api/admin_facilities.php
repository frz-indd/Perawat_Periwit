<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
set_exception_handler(static function (Throwable $exception): void {
    error_log('Admin facilities API error: ' . $exception->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['message' => 'Terjadi kesalahan pada server.'], JSON_UNESCAPED_UNICODE);
});

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../database/koneksi.php';

adminOnly();

if (!$conn instanceof mysqli) {
    http_response_code(500);
    echo json_encode(['message' => 'Koneksi database tidak tersedia.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id = $_GET['id'] ?? null;

function facilityResponse(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function facilityPayload(): array
{
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        facilityResponse(400, ['message' => 'Body request harus berupa JSON yang valid.']);
    }
    return $payload;
}

function facilityNameExists(mysqli $conn, string $name, ?int $exceptId = null): bool
{
    if ($exceptId === null) {
        $stmt = $conn->prepare('SELECT id FROM facilities WHERE name = ? LIMIT 1');
        $stmt->bind_param('s', $name);
    } else {
        $stmt = $conn->prepare('SELECT id FROM facilities WHERE name = ? AND id <> ? LIMIT 1');
        $stmt->bind_param('si', $name, $exceptId);
    }
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function validFacilityId($id): ?int
{
    if (!is_string($id) && !is_int($id)) {
        return null;
    }
    $value = (string) $id;
    if ($value === '' || !ctype_digit($value) || (int) $value < 1) {
        return null;
    }
    return (int) $value;
}

switch ($method) {
    case 'GET':
        if ($id !== null) {
            $facilityId = validFacilityId($id);
            if ($facilityId === null) {
                facilityResponse(400, ['message' => 'ID fasilitas tidak valid.']);
            }
            $stmt = $conn->prepare('SELECT id, name, icon_code, description, created_at FROM facilities WHERE id = ? LIMIT 1');
            $stmt->bind_param('i', $facilityId);
            $stmt->execute();
            $facility = $stmt->get_result()->fetch_assoc();
            if (!$facility) {
                facilityResponse(404, ['message' => 'Fasilitas tidak ditemukan.']);
            }
            $facility['id'] = (int) $facility['id'];
            facilityResponse(200, ['data' => $facility]);
        }

        $result = $conn->query('SELECT id, name, icon_code, description, created_at FROM facilities ORDER BY name ASC');
        $facilities = [];
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int) $row['id'];
            $facilities[] = $row;
        }
        facilityResponse(200, ['data' => $facilities]);
        break;

    case 'POST':
        $payload = facilityPayload();
        $name = isset($payload['name']) && is_string($payload['name']) ? trim($payload['name']) : '';
        $iconCode = isset($payload['icon_code']) && $payload['icon_code'] !== '' ? trim((string) $payload['icon_code']) : null;
        $description = isset($payload['description']) && $payload['description'] !== '' ? trim((string) $payload['description']) : null;

        if ($name === '' || mb_strlen($name, 'UTF-8') > 150) {
            facilityResponse(422, ['message' => 'Nama fasilitas wajib diisi dan maksimal 150 karakter.']);
        }
        if ($iconCode !== null && mb_strlen($iconCode, 'UTF-8') > 100) {
            facilityResponse(422, ['message' => 'Kode ikon maksimal 100 karakter.']);
        }
        if (facilityNameExists($conn, $name)) {
            facilityResponse(422, ['message' => 'Nama fasilitas sudah ada di sistem.']);
        }

        try {
            $stmt = $conn->prepare('INSERT INTO facilities (name, icon_code, description) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $name, $iconCode, $description);
            $stmt->execute();
        } catch (mysqli_sql_exception $exception) {
            if ((int) $exception->getCode() === 1062) {
                facilityResponse(422, ['message' => 'Nama fasilitas sudah ada di sistem.']);
            }
            throw $exception;
        }
        facilityResponse(201, [
            'message' => 'Fasilitas berhasil ditambahkan.',
            'data' => ['id' => (int) $stmt->insert_id, 'name' => $name, 'icon_code' => $iconCode, 'description' => $description]
        ]);
        break;

    case 'PUT':
        $facilityId = validFacilityId($id);
        if ($facilityId === null) {
            facilityResponse(400, ['message' => 'ID fasilitas tidak valid.']);
        }
        $payload = facilityPayload();
        $check = $conn->prepare('SELECT id FROM facilities WHERE id = ? LIMIT 1');
        $check->bind_param('i', $facilityId);
        $check->execute();
        if (!$check->get_result()->fetch_assoc()) {
            facilityResponse(404, ['message' => 'Fasilitas tidak ditemukan.']);
        }

        $fields = [];
        $types = '';
        $values = [];
        if (array_key_exists('name', $payload)) {
            $name = is_string($payload['name']) ? trim($payload['name']) : '';
            if ($name === '' || mb_strlen($name, 'UTF-8') > 150) {
                facilityResponse(422, ['message' => 'Nama fasilitas wajib diisi dan maksimal 150 karakter.']);
            }
            if (facilityNameExists($conn, $name, $facilityId)) {
                facilityResponse(422, ['message' => 'Nama fasilitas sudah ada di sistem.']);
            }
            $fields[] = 'name = ?';
            $types .= 's';
            $values[] = $name;
        }
        if (array_key_exists('icon_code', $payload)) {
            $iconCode = $payload['icon_code'] === null || $payload['icon_code'] === '' ? null : trim((string) $payload['icon_code']);
            if ($iconCode !== null && mb_strlen($iconCode, 'UTF-8') > 100) {
                facilityResponse(422, ['message' => 'Kode ikon maksimal 100 karakter.']);
            }
            $fields[] = 'icon_code = ?';
            $types .= 's';
            $values[] = $iconCode;
        }
        if (array_key_exists('description', $payload)) {
            $description = $payload['description'] === null || $payload['description'] === '' ? null : trim((string) $payload['description']);
            $fields[] = 'description = ?';
            $types .= 's';
            $values[] = $description;
        }
        if (!$fields) {
            facilityResponse(422, ['message' => 'Tidak ada data yang diperbarui.']);
        }

        $types .= 'i';
        $values[] = $facilityId;
        $stmt = $conn->prepare('UPDATE facilities SET ' . implode(', ', $fields) . ' WHERE id = ?');
        $stmt->bind_param($types, ...$values);
        try {
            $stmt->execute();
        } catch (mysqli_sql_exception $exception) {
            if ((int) $exception->getCode() === 1062) {
                facilityResponse(422, ['message' => 'Nama fasilitas sudah ada di sistem.']);
            }
            throw $exception;
        }
        facilityResponse(200, ['message' => 'Fasilitas berhasil diperbarui.']);
        break;

    case 'DELETE':
        $facilityId = validFacilityId($id);
        if ($facilityId === null) {
            facilityResponse(400, ['message' => 'ID fasilitas tidak valid.']);
        }
        $stmt = $conn->prepare('DELETE FROM facilities WHERE id = ?');
        $stmt->bind_param('i', $facilityId);
        $stmt->execute();
        if ($stmt->affected_rows === 0) {
            facilityResponse(404, ['message' => 'Fasilitas tidak ditemukan.']);
        }
        facilityResponse(200, ['message' => 'Fasilitas berhasil dihapus.']);
        break;

    default:
        header('Allow: GET, POST, PUT, DELETE');
        facilityResponse(405, ['message' => 'Metode tidak diizinkan.']);
}
