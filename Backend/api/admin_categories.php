<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../middleware/auth.php'; // adminOnly() defined here
require_once __DIR__ . '/../database/koneksi.php';

$method = $_SERVER['REQUEST_METHOD'] ?? '';
$adminPayload = adminOnly(); // ensures admin role, exits on failure

switch ($method) {
    case 'GET':
        // List categories with total destinations
        $stmt = $conn->prepare(
            "SELECT k.Id_kategori AS id, k.nama_kategori AS name, k.sort_order, \n                COUNT(w.id_wisata) AS total_destinations\n                FROM kategori k\n                LEFT JOIN wisata w ON w.id_kategori = k.Id_kategori\n                WHERE k.aktif = 1\n                GROUP BY k.Id_kategori\n                ORDER BY k.sort_order ASC"
        );
        $stmt->execute();
        $result = $stmt->get_result();
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'sort_order' => (int) $row['sort_order'],
                'total_destinations' => (int) $row['total_destinations'],
            ];
        }
        echo json_encode([
            'status' => 'success',
            'data' => $categories,
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'POST':
        $payload = json_decode(file_get_contents('php://input'), true);
        $name = trim($payload['name'] ?? '');
        $sortOrder = isset($payload['sort_order']) ? (int) $payload['sort_order'] : 0;

        if ($name === '') {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Semua field wajib diisi dengan benar.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        // Check duplicate name
        $dup = $conn->prepare('SELECT 1 FROM kategori WHERE nama_kategori = ?');
        $dup->bind_param('s', $name);
        $dup->execute();
        if ($dup->get_result()->fetch_row()) {
            http_response_code(409);
            echo json_encode([
                'status' => 'error',
                'message' => 'Nama kategori sudah ada.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $stmt = $conn->prepare('INSERT INTO kategori (nama_kategori, sort_order, aktif) VALUES (?, ?, 1)');
        $stmt->bind_param('si', $name, $sortOrder);
        $stmt->execute();
        $newId = $stmt->insert_id;
        http_response_code(201);
        echo json_encode([
            'status' => 'success',
            'message' => 'Kategori berhasil dibuat.',
            'data' => ['id' => $newId]
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'PUT':
        // expecting /api/admin/categories/{id}
        $id = $_GET['id'] ?? null;
        if (!$id || !ctype_digit((string)$id)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID kategori tidak valid.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $payload = json_decode(file_get_contents('php://input'), true);
        $name = trim($payload['name'] ?? '');
        $sortOrder = isset($payload['sort_order']) ? (int) $payload['sort_order'] : null;
        if ($name === '' && $sortOrder === null) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada perubahan yang diberikan.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        // Verify existence
        $check = $conn->prepare('SELECT 1 FROM kategori WHERE Id_kategori = ?');
        $check->bind_param('i', $id);
        $check->execute();
        if (!$check->get_result()->fetch_row()) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Kategori tidak ditemukan.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        // Build dynamic query
        $fields = [];
        $types = '';
        $params = [];
        if ($name !== '') {
            $fields[] = 'nama_kategori = ?';
            $types .= 's';
            $params[] = $name;
        }
        if ($sortOrder !== null) {
            $fields[] = 'sort_order = ?';
            $types .= 'i';
            $params[] = $sortOrder;
        }
        $params[] = $id;
        $types .= 'i';
        $sql = 'UPDATE kategori SET ' . implode(', ', $fields) . ' WHERE Id_kategori = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        echo json_encode(['status' => 'success', 'message' => 'Kategori berhasil diubah.'], JSON_UNESCAPED_UNICODE);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id || !ctype_digit((string)$id)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID kategori tidak valid.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        // Check linked destinations
        $cntStmt = $conn->prepare('SELECT COUNT(*) FROM wisata WHERE id_kategori = ?');
        $cntStmt->bind_param('i', $id);
        $cntStmt->execute();
        $cntStmt->bind_result($linked);
        $cntStmt->fetch();
        if ($linked > 0) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Kategori tidak dapat dihapus karena masih terikat dengan data destinasi wisata.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $delStmt = $conn->prepare('DELETE FROM kategori WHERE Id_kategori = ?');
        $delStmt->bind_param('i', $id);
        $delStmt->execute();
        echo json_encode(['status' => 'success', 'message' => 'Kategori berhasil dihapus.'], JSON_UNESCAPED_UNICODE);
        break;

    default:
        http_response_code(405);
        header('Allow: GET, POST, PUT, DELETE');
        echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan.'], JSON_UNESCAPED_UNICODE);
        break;
}
?>

