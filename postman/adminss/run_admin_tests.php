<?php
declare(strict_types=1);

/**
 * CLI Test Runner untuk Collection API Admin Perawat_Periwit
 * Menjalankan request endpoint Admin dan memvalidasi respons status code & payload.
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
echo "   RUNNER PENGUJIAN API ADMIN (POSTMAN ADMINSS)\n";
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

$adminUser = !empty($vars['adminUsername']) ? $vars['adminUsername'] : 'wowok';
$adminPass = !empty($vars['adminPassword']) ? $vars['adminPassword'] : 'admin123';

// 1. Uji Login Admin & Dapatkan JWT
echo "1. Pengujian Autentikasi Admin...\n";
$login = sendReq('POST', "$baseUrl/api/admin/login", ['Content-Type' => 'application/json', 'Accept' => 'application/json'], [
    'username' => $adminUser,
    'password' => $adminPass
]);
$token = $login['body']['token'] ?? '';
report("POST /api/admin/login (HTTP 200)", $login['code'] === 200 && !empty($token), "HTTP {$login['code']}");

if (empty($token)) {
    // Generate fallback token if DB password differs
    require_once __DIR__ . '/../../Backend/jwt.php';
    $token = generateJwt(['id' => 1, 'sub' => 1, 'username' => 'wowok', 'role' => 1, 'role_name' => 'admin'], 86400);
}
$auth = "Bearer $token";

// 2. Uji Dashboard & RBAC
echo "\n2. Pengujian Dashboard & RBAC...\n";
$dash = sendReq('GET', "$baseUrl/api/admin/dashboard", ['Authorization' => $auth, 'Accept' => 'application/json']);
report("GET /api/admin/dashboard (HTTP 200)", $dash['code'] === 200, "HTTP {$dash['code']}");

$summary = sendReq('GET', "$baseUrl/api/admin/dashboard/summary", ['Authorization' => $auth, 'Accept' => 'application/json']);
report("GET /api/admin/dashboard/summary (HTTP 200)", $summary['code'] === 200, "HTTP {$summary['code']}");

// 3. Uji CRUD Master Fasilitas
echo "\n3. Pengujian Master Fasilitas...\n";
$facList = sendReq('GET', "$baseUrl/api/admin/facilities", ['Authorization' => $auth, 'Accept' => 'application/json']);
report("GET /api/admin/facilities (HTTP 200)", $facList['code'] === 200, "HTTP {$facList['code']}");

$facCreate = sendReq('POST', "$baseUrl/api/admin/facilities", ['Authorization' => $auth, 'Content-Type' => 'application/json'], [
    'name' => 'Test Fasilitas ' . time(),
    'icon_code' => 'fa-wifi',
    'description' => 'Fasilitas koneksi internet gratis'
]);
$newFacId = $facCreate['body']['data']['id'] ?? null;
report("POST /api/admin/facilities (HTTP 201)", $facCreate['code'] === 201 && $newFacId !== null, "HTTP {$facCreate['code']}");

if ($newFacId) {
    $facGet = sendReq('GET', "$baseUrl/api/admin/facilities/$newFacId", ['Authorization' => $auth, 'Accept' => 'application/json']);
    report("GET /api/admin/facilities/{id} (HTTP 200)", $facGet['code'] === 200, "HTTP {$facGet['code']}");

    $facPut = sendReq('PUT', "$baseUrl/api/admin/facilities/$newFacId", ['Authorization' => $auth, 'Content-Type' => 'application/json'], [
        'description' => 'Fasilitas internet diperbarui'
    ]);
    report("PUT /api/admin/facilities/{id} (HTTP 200)", $facPut['code'] === 200, "HTTP {$facPut['code']}");

    $facDel = sendReq('DELETE', "$baseUrl/api/admin/facilities/$newFacId", ['Authorization' => $auth, 'Accept' => 'application/json']);
    report("DELETE /api/admin/facilities/{id} (HTTP 200)", $facDel['code'] === 200, "HTTP {$facDel['code']}");
}

// 4. Uji Pengelolaan Ulasan Admin
echo "\n4. Pengujian Moderasi Ulasan...\n";
$revList = sendReq('GET', "$baseUrl/api/admin/reviews", ['Authorization' => $auth, 'Accept' => 'application/json']);
report("GET /api/admin/reviews (HTTP 200)", $revList['code'] === 200, "HTTP {$revList['code']}");

// 5. Uji Pengelolaan Pengguna (User Management)
echo "\n5. Pengujian Admin Users...\n";
$usersList = sendReq('GET', "$baseUrl/api/admin/users", ['Authorization' => $auth, 'Accept' => 'application/json']);
report("GET /api/admin/users (HTTP 200)", $usersList['code'] === 200, "HTTP {$usersList['code']}");

// 6. Uji Lupa Password Admin (Forgot Password)
echo "\n6. Pengujian Forgot Password Admin...\n";
$fpEmptyUser = sendReq('POST', "$baseUrl/api/admin/forgot-password", ['Content-Type' => 'application/json'], [
    'username' => '',
    'new_password' => 'PasswordBaru123#'
]);
report("POST /api/admin/forgot-password Username Kosong (HTTP 422)", $fpEmptyUser['code'] === 422, "HTTP {$fpEmptyUser['code']}");

$fpNotFound = sendReq('POST', "$baseUrl/api/admin/forgot-password", ['Content-Type' => 'application/json'], [
    'username' => 'admin.tidak.ditemukan.999',
    'new_password' => 'PasswordBaru123#'
]);
report("POST /api/admin/forgot-password Username Tidak Terdaftar (HTTP 404)", $fpNotFound['code'] === 404, "HTTP {$fpNotFound['code']}");

$fpMismatch = sendReq('POST', "$baseUrl/api/admin/forgot-password", ['Content-Type' => 'application/json'], [
    'username' => $adminUser,
    'new_password' => 'PasswordBaru123#',
    'confirm_password' => 'PasswordBeda123#'
]);
report("POST /api/admin/forgot-password Konfirmasi Beda (HTTP 422)", $fpMismatch['code'] === 422, "HTTP {$fpMismatch['code']}");

$fpVerify = sendReq('POST', "$baseUrl/api/admin/forgot-password", ['Content-Type' => 'application/json'], [
    'username' => $adminUser
]);
report("POST /api/admin/forgot-password Verifikasi Username Admin (HTTP 200)", $fpVerify['code'] === 200, "HTTP {$fpVerify['code']}");

$fpSuccess = sendReq('POST', "$baseUrl/api/admin/forgot-password", ['Content-Type' => 'application/json'], [
    'username' => $adminUser,
    'new_password' => $adminPass,
    'confirm_password' => $adminPass
]);
report("POST /api/admin/forgot-password Reset Password Admin Berhasil (HTTP 200)", $fpSuccess['code'] === 200, "HTTP {$fpSuccess['code']}");

// 7. Uji Logout Admin
echo "\n7. Pengujian Logout Admin...\n";
$logout = sendReq('POST', "$baseUrl/api/admin/logout", ['Authorization' => $auth, 'Accept' => 'application/json']);
report("POST /api/admin/logout (HTTP 200)", $logout['code'] === 200, "HTTP {$logout['code']}");

echo "\n========================================================\n";
echo "   HASIL PENGUJIAN API ADMIN:\n";
echo "   Total Berhasil: $pass\n";
echo "   Total Gagal   : $fail\n";
echo "========================================================\n";
