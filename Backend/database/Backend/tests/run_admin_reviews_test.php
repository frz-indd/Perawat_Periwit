<?php
declare(strict_types=1);

/**
 * Simple end‑to‑end test script for admin review moderation.
 * Uses only built‑in PHP cURL – no external libraries required.
 */

$baseUrl = 'http://localhost:8000/api';
$adminEmail = 'admin@example.com'; // sesuaikan dengan data admin Anda
$adminPass  = 'rahasia';

function curlRequest(string $method, string $url, array $headers = [], $body = null)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    // Disable SSL verification for local http (harmless here)
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    $err = curl_error($ch);
    curl_close($ch);
    return ['status' => $info['http_code'], 'body' => $response, 'error' => $err];
}

function jsonDecode(string $s)
{
    $data = json_decode($s, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    return $data;
}

function output(string $msg, $data = null)
{
    echo $msg . "\n";
    if ($data !== null) {
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
}

// 1. Login admin to get JWT token
$loginUrl = $baseUrl . '/admin_login.php';
$loginHeaders = ['Content-Type: application/json', 'Accept: application/json'];
$loginBody = json_encode(['email' => $adminEmail, 'password' => $adminPass]);
$loginResp = curlRequest('POST', $loginUrl, $loginHeaders, $loginBody);
if ($loginResp['status'] !== 200) {
    output('❌ Gagal login admin', ['status' => $loginResp['status'], 'error' => $loginResp['error']]);
    exit(1);
}
$loginData = jsonDecode($loginResp['body']);
if (!$loginData || ($loginData['status'] ?? '') !== 'success') {
    output('❌ Respons login tidak successful', $loginData);
    exit(1);
}
$token = $loginData['data']['token'] ?? '';
output('✅ Login berhasil, token didapatkan');

$authHeader = "Authorization: Bearer $token";
$commonHeaders = [$authHeader, 'Accept: application/json'];

// 2. GET all reviews
$reviewsUrl = $baseUrl . '/admin_reviews.php';
$reviewsResp = curlRequest('GET', $reviewsUrl, $commonHeaders);
if ($reviewsResp['status'] !== 200) {
    output('❌ GET reviews gagal', ['status' => $reviewsResp['status']]);
    exit(1);
}
$reviewsData = jsonDecode($reviewsResp['body']);
if (!$reviewsData || ($reviewsData['status'] ?? '') !== 'success') {
    output('❌ Respons GET reviews tidak success', $reviewsData);
    exit(1);
}
$reviews = $reviewsData['data'] ?? [];
output('✅ GET reviews berhasil, total: ' . count($reviews));

if (empty($reviews)) {
    output('⚠️ Tidak ada review di database – tidak ada yang dapat di‑delete. Test selesai.');
    exit(0);
}

$first = $reviews[0];
$reviewId = $first['id_ulasan'] ?? null;
$destId   = $first['id_wisata'] ?? null;
if (!$reviewId || !$destId) {
    output('❌ Data review tidak lengkap (id atau destination missing)');
    exit(1);
}
output('🔎 Review terpilih untuk delete', ['id_ulasan' => $reviewId, 'id_wisata' => $destId]);

// 3. DELETE selected review
$deleteUrl = $baseUrl . '/admin_reviews.php?id=' . $reviewId;
$deleteResp = curlRequest('DELETE', $deleteUrl, $commonHeaders);
if ($deleteResp['status'] !== 200) {
    output('❌ DELETE review gagal', ['status' => $deleteResp['status']]);
    exit(1);
}
$deleteData = jsonDecode($deleteResp['body']);
if (!$deleteData || ($deleteData['status'] ?? '') !== 'success') {
    output('❌ Respons DELETE tidak success', $deleteData);
    exit(1);
}
output('✅ Review di‑delete, pesan:', $deleteData);

// 4. Verify destination rating updated (optional)
$destUrl = $baseUrl . '/admin_destinations.php?id=' . $destId;
$destResp = curlRequest('GET', $destUrl, $commonHeaders);
if ($destResp['status'] === 200) {
    $destData = jsonDecode($destResp['body']);
    if ($destData && ($destData['status'] ?? '') === 'success') {
        $rating = $destData['data']['rating'] ?? null;
        output('✅ Rating destinasi setelah delete', ['rating' => $rating]);
    } else {
        output('⚠️ Tidak dapat membaca rating destinasi (unexpected response)', $destData);
    }
} else {
    output('⚠️ Endpoint destinasi tidak tersedia atau tidak support GET dengan id', ['status' => $destResp['status']]);
}

output('🎉 Semua langkah selesai');
?>

