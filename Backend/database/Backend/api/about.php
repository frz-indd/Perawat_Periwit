<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    echo json_encode(['status' => 'method_not_allowed', 'message' => 'Metode yang diizinkan: GET.'], JSON_UNESCAPED_UNICODE);
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    require_once __DIR__ . '/../database/koneksi.php';
    if (!($conn instanceof mysqli)) {
        throw new RuntimeException('Koneksi database tidak tersedia.');
    }

    $result = $conn->query("SELECT section_key, title, content FROM about_content WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[$row['section_key']] = [
            'title' => $row['title'],
            'content' => $row['content']
        ];
    }

    foreach (['intro', 'vision', 'mission'] as $required) {
        if (!isset($data[$required])) {
            throw new RuntimeException('Konten halaman Tentang belum lengkap. Jalankan migrasi database.');
        }
    }

    echo json_encode(['status' => 'success', 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $exception) {
    error_log('About API error: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'server_error', 'message' => 'Gagal mengambil informasi Tentang. Pastikan migrasi database sudah dijalankan.'], JSON_UNESCAPED_UNICODE);
}
