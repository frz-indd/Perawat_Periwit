<?php
declare(strict_types=1);

/**
 * CLI Test Runner untuk Collection API User Perawat_Periwit
 * Menjalankan request endpoint User & Public serta memvalidasi status respons.
 */

$envFile = __DIR__ . '/Perawat_Periwit_LOCAL.postman_environment.json';
$env = json_decode(file_get_contents($envFile), true);
$vars = [];
foreach ($env['values'] ?? [] as $v) {
    if ($v['enabled'] ?? true) {
        $vars[$v['key']] = $v['value'];
    }
}

$baseUrl = rtrim($vars['baseUrl'] ?? 'http://localhost/Perawat_Periwit', '/');
echo "========================================================\n";
echo "   RUNNER PENGUJIAN API USER (POSTMAN USERSS)\n";
echo "   Base URL: $baseUrl\n";
echo "========================================================\n\n";

function sendReq(string $method, string $url, array $headers = [], ?array $body = null): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $h = [];
    foreach ($headers as $k => $v) $h[] = "$k: $v";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return ['code' => $code, 'body' => json_decode((string)$res, true) ?: $res, 'raw' => $res, 'error' => $err];
}

$pass = 0;
$fail = 0;

function report(string $name, bool $ok, string $detail = '') {
    global $pass, $fail;
    if ($ok) {
        $pass++;
        echo "  [PASS] $name\n";
    } else {
        $fail++;
        echo "  [FAIL] $name" . ($detail ? " ($detail)" : "") . "\n";
    }
}

// 1. Endpoint Publik
echo "1. Pengujian Endpoint Publik...\n";
$cat = sendReq('GET', "$baseUrl/categories", ['Accept' => 'application/json']);
report("GET /categories (HTTP 200)", $cat['code'] === 200, "HTTP {$cat['code']}");

$wisataCat = sendReq('GET', "$baseUrl/categories/1/wisata", ['Accept' => 'application/json']);
report("GET /categories/1/wisata (HTTP 200)", $wisataCat['code'] === 200, "HTTP {$wisataCat['code']}");

$wisataCatInvalid = sendReq('GET', "$baseUrl/categories/not-a-number/wisata", ['Accept' => 'application/json']);
report("GET /categories/not-a-number/wisata (HTTP 400)", $wisataCatInvalid['code'] === 400, "HTTP {$wisataCatInvalid['code']}");

$wisataAll = sendReq('GET', "$baseUrl/Backend/api/wisata.php", ['Accept' => 'application/json']);
report("GET /Backend/api/wisata.php (HTTP 200)", $wisataAll['code'] === 200, "HTTP {$wisataAll['code']}");

$search = sendReq('GET', "$baseUrl/api/destinations/search?q=curug", ['Accept' => 'application/json']);
report("GET /api/destinations/search (HTTP 200)", $search['code'] === 200, "HTTP {$search['code']}");

$location = sendReq('GET', "$baseUrl/api/destinations/M00001/location", ['Accept' => 'application/json']);
report("GET /api/destinations/{id}/location (HTTP 200)", $location['code'] === 200, "HTTP {$location['code']}");

// 2. Endpoint User Auth & Profile
echo "\n2. Pengujian Proteksi Akun User...\n";
$unauthProfile = sendReq('GET', "$baseUrl/users/me", ['Accept' => 'application/json']);
report("GET /users/me tanpa login (HTTP 401)", $unauthProfile['code'] === 401, "HTTP {$unauthProfile['code']}");

$unauthChangePw = sendReq('PUT', "$baseUrl/api/user/change-password", ['Content-Type' => 'application/json'], [
    'currentPassword' => 'Lama123!',
    'newPassword' => 'Baru123#'
]);
report("PUT /api/user/change-password tanpa login (HTTP 401)", $unauthChangePw['code'] === 401, "HTTP {$unauthChangePw['code']}");

// 3. Validasi Review & Registrasi
echo "\n3. Pengujian Validasi User...\n";
$invalidReg = sendReq('POST', "$baseUrl/Backend/api/fungsi_register.php", ['Content-Type' => 'application/json'], [
    'nama_user' => 'Pengguna Baru',
    'email' => 'format-salah',
    'password' => '123'
]);
report("POST registrasi validasi format salah (HTTP 400/422)", in_array($invalidReg['code'], [400, 422], true), "HTTP {$invalidReg['code']}");

$invalidReview = sendReq('POST', "$baseUrl/api/destinations/M00001/reviews", ['Content-Type' => 'application/json'], [
    'rating' => 10,
    'komentar' => 'Rating tidak valid'
]);
report("POST review rating di luar batas (HTTP 401/422)", in_array($invalidReview['code'], [401, 422], true), "HTTP {$invalidReview['code']}");

echo "\n========================================================\n";
echo "   HASIL PENGUJIAN API USER:\n";
echo "   Total Berhasil: $pass\n";
echo "   Total Gagal   : $fail\n";
echo "========================================================\n";
