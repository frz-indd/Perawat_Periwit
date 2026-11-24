<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../database/koneksi.php';

adminOnly();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode([
        'status' => 'error',
        'message' => 'Metode tidak diizinkan.'
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (!$conn instanceof mysqli) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Koneksi database tidak tersedia.'
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

$result = $conn->query('SELECT id_kabupaten AS id, nama_kabupaten AS name FROM kabupaten ORDER BY nama_kabupaten');
$regions = [];
while ($row = $result->fetch_assoc()) {
    $regions[] = [
        'id' => (int) $row['id'],
        'name' => $row['name']
    ];
}

echo json_encode([
    'status' => 'success',
    'data' => $regions
], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
