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
}
?>

