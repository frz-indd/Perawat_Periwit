<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

set_exception_handler(static function (Throwable $exception): void {
    error_log('Admin facilities API error: ' . $exception->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['message' => 'Terjadi kesalahan pada server fasilitas. Periksa struktur tabel fasilitas dan wisata_fasilitas.'], JSON_UNESCAPED_UNICODE);
});

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../database/koneksi.php';

adminOnly();

if (!$conn instanceof mysqli) {
    http_response_code(500);
    echo json_encode(['message' => 'Koneksi database tidak tersedia.'], JSON_UNESCAPED_UNICODE);
    exit;
}

function facilityResponse(int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function facilityPayload(): array {
    $payload = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($payload)) facilityResponse(400, ['message' => 'Body request harus berupa JSON yang valid.']);
    return $payload;
}
function facilityId($id): ?string {
    if (!is_string($id) && !is_int($id)) return null;
    $id = trim((string)$id);
    return $id !== '' && strlen($id) <= 16 ? $id : null;
}
function facilityCodes(): array {
    return [
        'parking' => 'Area Parkir',
        'camera' => 'Spot Foto',
        'toilet' => 'Toilet',
        'mosque' => 'Mushola',
        'wifi' => 'WiFi'
    ];
}
function normalizeFacilityCodes($value): array {
    if (!is_string($value) || trim($value) === '') return [];
    $allowed = facilityCodes();
    $codes = array_values(array_unique(array_filter(array_map('trim', preg_split('/[;,|]/', $value) ?: []))));
    foreach ($codes as $code) {
        if (!isset($allowed[$code])) facilityResponse(422, ['message' => 'Jenis fasilitas tidak dikenal: ' . $code]);
    }
    return $codes;
}
function findTourism(mysqli $conn, string $name): ?array {
    $stmt = $conn->prepare('SELECT id_wisata, nama_wisata, domisili FROM wisata WHERE nama_wisata = ? AND aktif = 1 LIMIT 1');
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}
function getFacilityRows(mysqli $conn, ?string $id = null): array {
    $sql = 'SELECT w.id_wisata, w.nama_wisata, w.domisili, f.nama_fasilitas
            FROM wisata w
            INNER JOIN wisata_fasilitas wf ON wf.Id_wisata = w.id_wisata
            INNER JOIN fasilitas f ON f.Id_fasilitas = wf.Id_fasilitas';
    if ($id !== null) {
        $sql .= ' WHERE w.id_wisata = ?';
        $stmt = $conn->prepare($sql . ' ORDER BY f.nama_fasilitas');
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql . ' ORDER BY w.nama_wisata, f.nama_fasilitas');
    }
    $map = [];
    $nameToCode = array_flip(facilityCodes());
    while ($row = $result->fetch_assoc()) {
        $key = (string)$row['id_wisata'];
        if (!isset($map[$key])) {
            $map[$key] = [
                'id' => $key,
                'name' => $row['nama_wisata'],
                'tourism_name' => $row['nama_wisata'],
                'region' => $row['domisili'],
                'icon_code' => ''
            ];
        }
        $code = $nameToCode[$row['nama_fasilitas']] ?? null;
        if ($code !== null) $map[$key]['icon_code'] .= ($map[$key]['icon_code'] === '' ? '' : ',') . $code;
    }
    return array_values($map);
}
function saveTourismFacilities(mysqli $conn, string $tourismId, array $codes): void {
    $conn->begin_transaction();
    try {
        $delete = $conn->prepare('DELETE FROM wisata_fasilitas WHERE Id_wisata = ?');
        $delete->bind_param('s', $tourismId);
        $delete->execute();

        $definitions = facilityCodes();
        foreach ($codes as $code) {
            $name = $definitions[$code];
            $find = $conn->prepare('SELECT Id_fasilitas FROM fasilitas WHERE nama_fasilitas = ? LIMIT 1');
            $find->bind_param('s', $name);
            $find->execute();
            $existing = $find->get_result()->fetch_assoc();
            if ($existing) {
                $facilityId = (int)$existing['Id_fasilitas'];
            } else {
                $insert = $conn->prepare('INSERT INTO fasilitas (nama_fasilitas) VALUES (?)');
                $insert->bind_param('s', $name);
                $insert->execute();
                $facilityId = (int)$insert->insert_id;
            }
            $link = $conn->prepare('INSERT INTO wisata_fasilitas (Id_wisata, Id_fasilitas) VALUES (?, ?)');
            $link->bind_param('si', $tourismId, $facilityId);
            $link->execute();
        }
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id = facilityId($_GET['id'] ?? null);

switch ($method) {
    case 'GET':
        if (isset($_GET['id']) && $id === null) facilityResponse(400, ['message' => 'ID wisata tidak valid.']);
        $rows = getFacilityRows($conn, $id);
        if (isset($_GET['id'])) {
            if (!$rows) facilityResponse(404, ['message' => 'Data fasilitas wisata tidak ditemukan.']);
            facilityResponse(200, ['status' => 'success', 'data' => $rows[0]]);
        }
        facilityResponse(200, ['status' => 'success', 'data' => $rows]);
        break;

    case 'POST':
    case 'PUT':
        $payload = facilityPayload();
        $name = isset($payload['name']) && is_string($payload['name']) ? trim($payload['name']) : '';
        if ($name === '' || mb_strlen($name, 'UTF-8') > 255) facilityResponse(422, ['message' => 'Nama wisata wajib diisi dan maksimal 255 karakter.']);
        $tourism = findTourism($conn, $name);
        if (!$tourism) facilityResponse(422, ['message' => 'Nama wisata tidak ditemukan. Pilih nama wisata dari saran database.']);
        $codes = normalizeFacilityCodes($payload['icon_code'] ?? '');
        if (!$codes) facilityResponse(422, ['message' => 'Pilih minimal satu fasilitas.']);

        if ($method === 'PUT') {
            if ($id === null) facilityResponse(400, ['message' => 'ID wisata untuk perubahan tidak valid.']);
            $check = $conn->prepare('SELECT id_wisata FROM wisata WHERE id_wisata = ? AND aktif = 1 LIMIT 1');
            $check->bind_param('s', $id);
            $check->execute();
            if (!$check->get_result()->fetch_assoc()) facilityResponse(404, ['message' => 'Data wisata tidak ditemukan.']);
            $tourismId = $id;
        } else {
            $tourismId = (string)$tourism['id_wisata'];
            $existing = getFacilityRows($conn, $tourismId);
            if ($existing) facilityResponse(409, ['message' => 'Fasilitas untuk destinasi wisata ini sudah tersedia. Gunakan Edit untuk mengubahnya.']);
        }

        saveTourismFacilities($conn, $tourismId, $codes);
        facilityResponse($method === 'POST' ? 201 : 200, [
            'status' => 'success',
            'message' => $method === 'POST' ? 'Fasilitas wisata berhasil ditambahkan.' : 'Fasilitas wisata berhasil diperbarui.',
            'data' => [
                'id' => $tourismId,
                'name' => $tourism['nama_wisata'],
                'tourism_name' => $tourism['nama_wisata'],
                'region' => $tourism['domisili'],
                'icon_code' => implode(',', $codes)
            ]
        ]);
        break;

    case 'DELETE':
        if ($id === null) facilityResponse(400, ['message' => 'ID wisata tidak valid.']);
        $stmt = $conn->prepare('DELETE FROM wisata_fasilitas WHERE Id_wisata = ?');
        $stmt->bind_param('s', $id);
        $stmt->execute();
        if ($stmt->affected_rows < 1) facilityResponse(404, ['message' => 'Relasi fasilitas wisata tidak ditemukan.']);
        facilityResponse(200, ['status' => 'success', 'message' => 'Fasilitas wisata berhasil dihapus.']);
        break;

    default:
        header('Allow: GET, POST, PUT, DELETE');
        facilityResponse(405, ['message' => 'Metode tidak diizinkan.']);
}
