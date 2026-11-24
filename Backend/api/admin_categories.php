<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../middleware/auth.php'; // adminOnly() defined here
require_once __DIR__ . '/../database/koneksi.php';

$method = $_SERVER['REQUEST_METHOD'] ?? '';
if ($method === 'POST') {
    $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? ($_POST['_method'] ?? '');
    if (in_array(strtoupper($override), ['PUT', 'DELETE'], true)) {
        $method = strtoupper($override);
    }
}
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
}
?>

