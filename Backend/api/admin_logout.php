<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../middleware/auth.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST'); http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Gunakan metode POST.']); exit;
}
$token = extractBearerToken();
$payload = adminOnly(); // Memastikan token masih aktif sebelum dicabut.
require_once __DIR__ . '/../database/koneksi.php';
$hash = hash('sha256', (string)$token);
$stmt = $conn->prepare('UPDATE admin_sessions SET revoked_at = NOW() WHERE token_hash = ? AND revoked_at IS NULL');
$stmt->bind_param('s', $hash); $stmt->execute(); $stmt->close();
http_response_code(200);
echo json_encode(['status' => 'success', 'message' => 'Sesi admin berhasil diakhiri.']);
