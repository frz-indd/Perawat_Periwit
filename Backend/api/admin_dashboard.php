<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../middleware/auth.php";

// Jalankan middleware otorisasi adminOnly
$adminPayload = adminOnly();

// Jika lolos verifikasi, data admin berhasil diperoleh
http_response_code(200);
echo json_encode([
    "status" => "success",
    "message" => "Otorisasi admin berhasil. Akses rute khusus admin diizinkan.",
    "auth" => $adminPayload
], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

