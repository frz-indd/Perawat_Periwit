<?php
declare(strict_types=1);
/**
 * End‑to‑end test script for admin review API.
 * Writes each request/response pair to JSON files in the same folder.
 */
$base   = 'http://localhost:8000/api';
$folder = __DIR__;
function request(string $method, string $url, array $headers = [], $body = null): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $out   = curl_exec($ch);
    $info  = curl_getinfo($ch);
    $error = curl_error($ch);
    curl_close($ch);
    return ['code' => $info['http_code'], 'body' => $out, 'error' => $error];
}
function save(string $name, $data): void {
    global $folder; file_put_contents("$folder/$name.json", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
// 1. Login admin
$login = request('POST', "$base/admin_login.php", ['Content-Type: application/json','Accept: application/json'], json_encode(['username'=>'admin@example.com','password'=>'rahasia']));
save('login_response',$login);
$loginData = json_decode($login['body'], true);
if (($loginData['status'] ?? '') !== 'success') { echo "Login gagal, cek login_response.json\n"; exit(1); }
$token = $loginData['token'] ?? '';
$authHeader = "Authorization: Bearer $token";
// 2. GET reviews
$get = request('GET', "$base/admin_reviews.php", [$authHeader,'Accept: application/json']);
save('get_reviews',$get);
$revData = json_decode($get['body'], true);
if (($revData['status'] ?? '') !== 'success' || empty($revData['data'])) {
    echo "GET reviews gagal atau kosong, cek get_reviews.json\n"; exit(1);
}
$first = $revData['data'][0];
$reviewId = $first['id_ulasan'] ?? null;
$destId   = $first['id_wisata'] ?? null;
// 3. DELETE review
$delUrl = "$base/admin_reviews.php?id=$reviewId";
$del    = request('DELETE', $delUrl, [$authHeader,'Accept: application/json']);
save('delete_review',$del);
// 4. OPTIONAL: GET destination after delete (if endpoint exists)
if ($destId) {
    $dest = request('GET', "$base/admin_destinations.php?id=$destId", [$authHeader,'Accept: application/json']);
    save('dest_after_delete',$dest);
}

echo "✅ Semua request selesai – file JSON berada di folder postman.\n";
?>
