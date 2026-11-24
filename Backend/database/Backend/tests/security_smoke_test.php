<?php
declare(strict_types=1);

require_once __DIR__ . '/../jwt.php';

function check(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "PASS: {$message}\n";
}

putenv('JWT_SECRET');
try {
    getJwtSecret();
    check(false, 'missing JWT_SECRET must fail closed');
} catch (RuntimeException $e) {
    check(true, 'missing JWT_SECRET must fail closed');
}

$secret = bin2hex(random_bytes(32));
putenv('JWT_SECRET=' . $secret);
$valid = generateJwt(['id' => 9, 'role' => 'admin'], 60);
check(verifyJwt($valid) !== null, 'valid JWT accepted');

$expired = generateJwt(['id' => 9, 'role' => 'admin', 'exp' => time() - 5]);
check(verifyJwt($expired) === null, 'expired JWT rejected');

$header = base64UrlEncode((string)json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
$payload = base64UrlEncode((string)json_encode(['id' => 9, 'role' => 'admin']));
$signature = base64UrlEncode(hash_hmac('sha256', $header . '.' . $payload, getJwtSecret(), true));
check(verifyJwt($header . '.' . $payload . '.' . $signature) === null, 'JWT without exp rejected');

$tampered = substr($valid, 0, -1) . (substr($valid, -1) === 'a' ? 'b' : 'a');
check(verifyJwt($tampered) === null, 'tampered JWT rejected');
