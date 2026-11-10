<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../middleware/auth.php'; // adminOnly()
require_once __DIR__ . '/../database/koneksi.php';

$method = $_SERVER['REQUEST_METHOD'] ?? '';
if ($method === 'POST' && ($_POST['_method'] ?? '') === 'PUT') {
    $method = 'PUT';
}
$adminPayload = adminOnly(); // ensure admin

// Helper to send JSON response
function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

switch ($method) {
    case 'GET':
        // Filters: search, kabupaten, category, page
        $search = isset($_GET['search']) && is_string($_GET['search']) ? $_GET['search'] : null;
        $kabupaten = isset($_GET['kabupaten']) && is_string($_GET['kabupaten']) ? $_GET['kabupaten'] : null;
        $category = isset($_GET['category']) && is_string($_GET['category']) ? $_GET['category'] : null;
        $id = $_GET['id'] ?? null;
        $page = isset($_GET['page'])
            && is_string($_GET['page'])
            && ctype_digit($_GET['page'])
            && (int) $_GET['page'] > 0
            ? (int)$_GET['page']
            : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];
        $types = '';
        if ($search !== null && $search !== '') {
            $where[] = 'w.nama_wisata LIKE ?';
            $params[] = "%$search%";
            $types .= 's';
        }
        if ($kabupaten !== null && ctype_digit((string)$kabupaten)) {
            $where[] = 'w.id_kabupaten = ?';
            $params[] = (int)$kabupaten;
            $types .= 'i';
        }
        if ($category !== null && ctype_digit((string)$category)) {
            $where[] = 'w.id_kategori = ?';
            $params[] = (int)$category;
            $types .= 'i';
        }
        if (is_string($id) && preg_match('/^[A-Za-z0-9_-]{1,16}$/', $id) === 1) {
            $where[] = 'w.id_wisata = ?';
            $params[] = $id;
            $types .= 's';
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM wisata w $whereSql");
        if ($types !== '') {
            $countStmt->bind_param($types, ...$params);
        }
        $countStmt->execute();
        $total = (int) $countStmt->get_result()->fetch_assoc()['total'];

        $sql = "SELECT w.id_wisata, w.nama_wisata, w.gambar, w.maps_url, w.deskripsi, w.harga_tiket, w.domisili AS wilayah, w.alamat, w.jam_buka, w.jam_tutup, w.latitude, w.longitude,
                       (SELECT AVG(ul.rating) FROM ulasan ul WHERE ul.Id_wisata = w.id_wisata) AS rating,
                       k.Id_kategori, k.nama_kategori, kb.id_kabupaten, kb.nama_kabupaten
                FROM wisata w
                LEFT JOIN kategori k ON k.Id_kategori = w.id_kategori
                LEFT JOIN kabupaten kb ON kb.id_kabupaten = w.id_kabupaten
                $whereSql
                ORDER BY w.nama_wisata
                LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        // bind dynamic params + limit/offset
        $bindTypes = $types . 'ii';
        $bindValues = array_merge($params, [$limit, $offset]);
        $stmt->bind_param($bindTypes, ...$bindValues);
        $stmt->execute();
        $result = $stmt->get_result();
        $destinations = [];
        while ($row = $result->fetch_assoc()) {
            $destinations[] = [
                'id' => (string) $row['id_wisata'],
                'name' => $row['nama_wisata'],
                'image' => $row['gambar'],
                'maps_url' => $row['maps_url'],
                'description' => $row['deskripsi'],
                'price' => $row['harga_tiket'] !== null ? (float) $row['harga_tiket'] : null,
                'rating' => $row['rating'] !== null ? (float) $row['rating'] : null,
                'region' => $row['wilayah'],
                'address' => $row['alamat'],
                'open_time' => $row['jam_buka'],
                'close_time' => $row['jam_tutup'],
                'latitude' => $row['latitude'] !== null ? (float) $row['latitude'] : null,
                'longitude' => $row['longitude'] !== null ? (float) $row['longitude'] : null,
                'category' => $row['Id_kategori'] ? [
                    'id' => (int) $row['Id_kategori'],
                    'name' => $row['nama_kategori']
                ] : null,
                'kabupaten' => $row['id_kabupaten'] ? [
                    'id' => (int) $row['id_kabupaten'],
                    'name' => $row['nama_kabupaten']
                ] : null,
            ];
        }
        jsonResponse([
            'status' => 'success',
            'data' => $destinations,
            'total' => $total,
            'page' => $page,
            'per_page' => $limit,
        ]);
        break;

    case 'POST':
        // Expect multipart/form-data
        $name = trim($_POST['name'] ?? '');
        $categoryId = isset($_POST['category_id']) && ctype_digit($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $kabupatenId = isset($_POST['kabupaten_id']) && ctype_digit($_POST['kabupaten_id']) ? (int)$_POST['kabupaten_id'] : null;
        $mapsUrl = trim($_POST['maps_url'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = isset($_POST['price']) ? (float)$_POST['price'] : null;
        $address = trim($_POST['address'] ?? '');
        $openTime = trim($_POST['open_time'] ?? '');
        $closeTime = trim($_POST['close_time'] ?? '');
        $latitude = isset($_POST['latitude']) && is_numeric($_POST['latitude']) ? (float)$_POST['latitude'] : null;
        $longitude = isset($_POST['longitude']) && is_numeric($_POST['longitude']) ? (float)$_POST['longitude'] : null;

        // Basic validation
        if ($name === '' || $categoryId === null || $kabupatenId === null) {
            jsonResponse(['status' => 'error', 'message' => 'Semua field wajib diisi dengan benar.'], 400);
        }
        // Validate image
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(['status' => 'error', 'message' => 'Gambar wajib diunggah.'], 400);
        }
        $file = $_FILES['image'];
        if ($file['size'] > 10 * 1024 * 1024) {
            jsonResponse(['status' => 'error', 'message' => 'Ukuran file gambar melebihi 10MB.'], 400);
        }
        $allowed = ['image/jpeg' => '.jpg', 'image/png' => '.png'];
        $mime = mime_content_type($file['tmp_name']);
        if (!array_key_exists($mime, $allowed)) {
            jsonResponse(['status' => 'error', 'message' => 'Format gambar tidak didukung. Hanya JPG atau PNG.'], 400);
        }
        $uploadDir = __DIR__ . '/../../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename = uniqid('dest_', true) . $allowed[$mime];
        $destPath = $uploadDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            jsonResponse(['status' => 'error', 'message' => 'Gagal menyimpan file gambar.'], 500);
        }
        // Insert record
        $newId = 'DEST' . strtoupper(bin2hex(random_bytes(6)));
        $stmt = $conn->prepare('INSERT INTO wisata (id_wisata, nama_wisata, id_kategori, id_kabupaten, gambar, maps_url, deskripsi, harga_tiket, alamat, jam_buka, jam_tutup, latitude, longitude, aktif) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
        $stmt->bind_param('ssiisssdsssdd', $newId, $name, $categoryId, $kabupatenId, $filename, $mapsUrl, $description, $price, $address, $openTime, $closeTime, $latitude, $longitude);
        $stmt->execute();
        jsonResponse(['status' => 'success', 'message' => 'Destinasi berhasil dibuat.', 'data' => ['id' => (string) $newId]], 201);
        break;

    case 'PUT':
        // Expect id in query string
        $id = $_GET['id'] ?? null;
        if (!is_string($id) || preg_match('/^[A-Za-z0-9_-]{1,16}$/', $id) !== 1) {
            jsonResponse(['status' => 'error', 'message' => 'ID destinasi tidak valid.'], 400);
        }
        // Fetch existing record for image handling
        $sel = $conn->prepare('SELECT gambar FROM wisata WHERE id_wisata = ?');
        $sel->bind_param('s', $id);
        $sel->execute();
        $sel->bind_result($oldImage);
        if (!$sel->fetch()) {
            jsonResponse(['status' => 'error', 'message' => 'Destinasi tidak ditemukan.'], 404);
        }
        $sel->close();

        // Parse input (allow both multipart and json)
        $payload = [];
        if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
            $payload = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            $payload = $_POST;
        }
        $fields = [];
        $types = '';
        $params = [];
        // Optional fields
        if (isset($payload['name'])) { $fields[] = 'nama_wisata = ?'; $types .= 's'; $params[] = trim($payload['name']); }
        if (isset($payload['category_id']) && ctype_digit((string)$payload['category_id'])) { $fields[] = 'id_kategori = ?'; $types .= 'i'; $params[] = (int)$payload['category_id']; }
        if (isset($payload['kabupaten_id']) && ctype_digit((string)$payload['kabupaten_id'])) { $fields[] = 'id_kabupaten = ?'; $types .= 'i'; $params[] = (int)$payload['kabupaten_id']; }
        if (isset($payload['maps_url'])) { $fields[] = 'maps_url = ?'; $types .= 's'; $params[] = trim($payload['maps_url']); }
        if (isset($payload['description'])) { $fields[] = 'deskripsi = ?'; $types .= 's'; $params[] = trim($payload['description']); }
        if (isset($payload['price'])) { $fields[] = 'harga_tiket = ?'; $types .= 'd'; $params[] = (float)$payload['price']; }
        if (isset($payload['address'])) { $fields[] = 'alamat = ?'; $types .= 's'; $params[] = trim($payload['address']); }
        if (isset($payload['open_time'])) { $fields[] = 'jam_buka = ?'; $types .= 's'; $params[] = trim($payload['open_time']); }
        if (isset($payload['close_time'])) { $fields[] = 'jam_tutup = ?'; $types .= 's'; $params[] = trim($payload['close_time']); }
        if (isset($payload['latitude'])) { $fields[] = 'latitude = ?'; $types .= 'd'; $params[] = (float)$payload['latitude']; }
        if (isset($payload['longitude'])) { $fields[] = 'longitude = ?'; $types .= 'd'; $params[] = (float)$payload['longitude']; }
        // Handle image replacement if a file is uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            if ($file['size'] > 10 * 1024 * 1024) {
                jsonResponse(['status' => 'error', 'message' => 'Ukuran file gambar melebihi 10MB.'], 400);
            }
            $allowed = ['image/jpeg' => '.jpg', 'image/png' => '.png'];
            $mime = mime_content_type($file['tmp_name']);
            if (!array_key_exists($mime, $allowed)) {
                jsonResponse(['status' => 'error', 'message' => 'Format gambar tidak didukung.'], 400);
            }
            $uploadDir = __DIR__ . '/../../uploads/';
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
            $newFilename = uniqid('dest_', true) . $allowed[$mime];
            $destPath = $uploadDir . $newFilename;
            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                jsonResponse(['status' => 'error', 'message' => 'Gagal menyimpan file gambar baru.'], 500);
            }
            // schedule old file deletion
            if ($oldImage && file_exists($uploadDir . $oldImage)) {
                @unlink($uploadDir . $oldImage);
            }
            $fields[] = 'gambar = ?';
            $types .= 's';
            $params[] = $newFilename;
        }
        if (empty($fields)) {
            jsonResponse(['status' => 'error', 'message' => 'Tidak ada data yang diberikan untuk diubah.'], 400);
        }
        $params[] = (string) $id; // for WHERE
        $types .= 's';
        $sql = 'UPDATE wisata SET ' . implode(', ', $fields) . ' WHERE id_wisata = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        jsonResponse(['status' => 'success', 'message' => 'Destinasi berhasil diubah.']);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!is_string($id) || preg_match('/^[A-Za-z0-9_-]{1,16}$/', $id) !== 1) {
            jsonResponse(['status' => 'error', 'message' => 'ID destinasi tidak valid.'], 400);
        }
        // Retrieve image filename for deletion
        $sel = $conn->prepare('SELECT gambar FROM wisata WHERE id_wisata = ?');
        $sel->bind_param('s', $id);
        $sel->execute();
        $sel->bind_result($oldImage);
        if (!$sel->fetch()) {
            jsonResponse(['status' => 'error', 'message' => 'Destinasi tidak ditemukan.'], 404);
        }
        $sel->close();
        // Delete record
        $del = $conn->prepare('DELETE FROM wisata WHERE id_wisata = ?');
        $del->bind_param('s', $id);
        $del->execute();
        // Delete image file if exists
        $uploadDir = __DIR__ . '/../../uploads/';
        if ($oldImage && file_exists($uploadDir . $oldImage)) {
            @unlink($uploadDir . $oldImage);
        }
        jsonResponse(['status' => 'success', 'message' => 'Destinasi wisata berhasil dihapus.']);
        break;

    default:
        http_response_code(405);
        header('Allow: GET, POST, PUT, DELETE');
        jsonResponse(['status' => 'error', 'message' => 'Metode tidak diizinkan.']);
}
?>
