<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../middleware/auth.php';
adminOnly();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') { header('Allow: GET'); http_response_code(405); echo json_encode(['message'=>'Metode tidak diizinkan.']); exit; }
try {
    require_once __DIR__ . '/../database/koneksi.php';
    if (!$conn instanceof mysqli) throw new RuntimeException('Koneksi database tidak tersedia.');
    $result = $conn->query('SELECT Id_user AS id, Nama_user AS nama, Email AS email, No_telepon AS no_telepon FROM `user` ORDER BY Id_user DESC');
    $users = [];
    while ($row = $result->fetch_assoc()) $users[] = $row;
    echo json_encode(['status'=>'success','data'=>$users,'total'=>count($users)], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $e) {
    error_log('Admin users API error: '.$e->getMessage());
    http_response_code(500); echo json_encode(['status'=>'server_error','message'=>'Gagal memuat pengguna.']);
}
