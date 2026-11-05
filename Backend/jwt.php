<?php
declare(strict_types=1);

/**
 * Konfigurasi JWT untuk otentikasi token.
 * Kunci rahasia dapat dikonfigurasi melalui environment variable `JWT_SECRET`
 * atau ditimpa di file lokal `Backend/jwt.local.php`.
 */
function getJwtSecret(): string
{
    static $secret = null;
    if ($secret !== null) {
        return $secret;
    }

    $localPath = __DIR__ . '/jwt.local.php';
    if (is_file($localPath)) {
        $localConfig = require $localPath;
        if (is_array($localConfig) && !empty($localConfig['jwt_secret'])) {
            $secret = (string) $localConfig['jwt_secret'];
            return $secret;
        }
    }

    $envSecret = getenv('JWT_SECRET');
    if (is_string($envSecret) && trim($envSecret) !== '') {
        $secret = trim($envSecret);
        return $secret;
    }

    // Default fallback secret key untuk development / testing lokal
    $secret = 'perawat_periwit_majaku_jwt_secret_key_sprint4_2026';
    return $secret;
}

/**
 * Base64Url Encode
 */
function base64UrlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Base64Url Decode
 */
function base64UrlDecode(string $data): ?string
{
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    $decoded = base64_decode(strtr($data, '-_', '+/'), true);
    return $decoded === false ? null : $decoded;
}

/**
 * Generate JSON Web Token (HS256)
 *
 * @param array $payload Payload data (misal: id, role, sub, dll)
 * @param int $expiresIn Expiration duration in seconds (default: 86400 / 1 hari)
 * @param string|null $secret Custom secret key
 * @return string JWT token
 */
function generateJwt(array $payload, int $expiresIn = 86400, ?string $secret = null): string
{
    $secret = $secret ?? getJwtSecret();
    $now = time();

    $header = [
        'typ' => 'JWT',
        'alg' => 'HS256'
    ];

    $payload['iat'] = $payload['iat'] ?? $now;
    $payload['exp'] = $payload['exp'] ?? ($now + $expiresIn);

    $encodedHeader = base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
    $encodedPayload = base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));

    $signature = hash_hmac('sha256', "$encodedHeader.$encodedPayload", $secret, true);
    $encodedSignature = base64UrlEncode($signature);

    return "$encodedHeader.$encodedPayload.$encodedSignature";
}

/**
 * Verifikasi & decode JSON Web Token (HS256)
 *
 * @param string $token
 * @param string|null $secret
 * @return array|null Mengembalikan payload array jika valid, atau null jika tidak valid/kadaluarsa
 */
function verifyJwt(string $token, ?string $secret = null): ?array
{
    $secret = $secret ?? getJwtSecret();
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }

    [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

    $headerJson = base64UrlDecode($encodedHeader);
    $payloadJson = base64UrlDecode($encodedPayload);
    if ($headerJson === null || $payloadJson === null) {
        return null;
    }

    $header = json_decode($headerJson, true);
    $payload = json_decode($payloadJson, true);
    if (!is_array($header) || !is_array($payload)) {
        return null;
    }

    // Pastikan algoritma HS256
    if (($header['alg'] ?? '') !== 'HS256') {
        return null;
    }

    $expectedSignature = hash_hmac('sha256', "$encodedHeader.$encodedPayload", $secret, true);
    $providedSignature = base64UrlDecode($encodedSignature);
    if ($providedSignature === null || !hash_equals($expectedSignature, $providedSignature)) {
        return null;
    }

    // Periksa expiration
    $now = time();
    if (isset($payload['exp']) && is_numeric($payload['exp']) && $now > (int) $payload['exp']) {
        return null;
    }

    // Periksa not-before jika ada
    if (isset($payload['nbf']) && is_numeric($payload['nbf']) && $now < (int) $payload['nbf']) {
        return null;
    }

    return $payload;
}

