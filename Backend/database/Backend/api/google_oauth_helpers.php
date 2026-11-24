<?php
function googleOauthConfig(): ?array
{
  $localConfig = [];
  $localConfigPath = dirname(__DIR__) . "/google_oauth.local.php";
  if (is_file($localConfigPath)) {
    $loadedConfig = require $localConfigPath;
    if (!is_array($loadedConfig)) {
      throw new RuntimeException("Konfigurasi OAuth Google lokal tidak valid");
    }
    $localConfig = $loadedConfig;
  }

  [$scheme, $host, $callbackPath] = googleOauthCurrentCallback();
  $isLocalHost = googleOauthIsLocalHost($host);
  $environmentClientId = getenv("GOOGLE_CLIENT_ID");
  $environmentClientSecret = getenv("GOOGLE_CLIENT_SECRET");
  $environmentRedirectUri = getenv("GOOGLE_REDIRECT_URI");
  $localClientId = $localConfig["client_id"] ?? "";
  $localClientSecret = $localConfig["client_secret"] ?? "";
  $localRedirectUri = $localConfig["redirect_uri"] ?? "";

  if ($isLocalHost) {
    $clientId = is_string($localClientId) && trim($localClientId) !== ""
      ? $localClientId
      : $environmentClientId;
    $clientSecret = is_string($localClientSecret) && trim($localClientSecret) !== ""
      ? $localClientSecret
      : $environmentClientSecret;
    $redirectUri = $scheme . "://" . $host . $callbackPath;
  } else {
    $clientId = $environmentClientId;
    $clientSecret = $environmentClientSecret;
    $redirectUri = $environmentRedirectUri;

    if (!is_string($clientId) || trim($clientId) === "") {
      $clientId = $localClientId;
    }
    if (!is_string($clientSecret) || trim($clientSecret) === "") {
      $clientSecret = $localClientSecret;
    }
    if (!is_string($redirectUri) || trim($redirectUri) === "") {
      $redirectUri = $localRedirectUri;
    }
    if (!is_string($redirectUri) || trim($redirectUri) === "") {
      $redirectUri = $scheme . "://" . $host . $callbackPath;
    }
  }

  if (!is_string($clientId) || trim($clientId) === "") {
    return null;
  }

  if (!is_string($clientSecret) || trim($clientSecret) === "") {
    return null;
  }

  return [
    "client_id" => trim($clientId),
    "client_secret" => trim($clientSecret),
    "redirect_uri" => trim($redirectUri)
  ];
}

function googleOauthCurrentCallback(): array
{
  $scheme = ((!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") || ($_SERVER["SERVER_PORT"] ?? null) == 443)
    ? "https"
    : "http";
  $host = $_SERVER["HTTP_HOST"] ?? "localhost";
  $scriptName = str_replace("\\", "/", (string) ($_SERVER["SCRIPT_NAME"] ?? "/Backend/api/google_login.php"));
  $callbackPath = rtrim(dirname($scriptName), "/") . "/google_callback.php";

  return [$scheme, $host, $callbackPath];
}

function googleOauthIsLocalHost(string $host): bool
{
  $hostname = strtolower($host);
  if (str_starts_with($hostname, "[")) {
    $end = strpos($hostname, "]");
    $hostname = $end === false ? $hostname : substr($hostname, 1, $end - 1);
  } else {
    $colon = strrpos($hostname, ":");
    $port = $colon === false ? "" : substr($hostname, $colon + 1);
    if ($colon !== false && ctype_digit($port)) {
      $hostname = substr($hostname, 0, $colon);
    }
  }

  return in_array($hostname, ["localhost", "127.0.0.1", "::1"], true);
}

function googleOauthBase64UrlDecode(string $value): string|false
{
  $remainder = strlen($value) % 4;
  if ($remainder > 0) {
    $value .= str_repeat("=", 4 - $remainder);
  }

  return base64_decode(strtr($value, "-_", "+/"), true);
}

function googleOauthDerLength(string $value): string
{
  $length = strlen($value);
  if ($length < 128) {
    return chr($length) . $value;
  }

  $lengthBytes = ltrim(pack("N", $length), "\x00");
  return chr(0x80 | strlen($lengthBytes)) . $lengthBytes . $value;
}

function googleOauthDerInteger(string $bytes): string
{
  if ($bytes === "") {
    $bytes = "\x00";
  }
  if ((ord($bytes[0]) & 0x80) === 0x80) {
    $bytes = "\x00" . $bytes;
  }

  return "\x02" . googleOauthDerLength($bytes);
}

function googleOauthJwkToPem(array $jwk): ?string
{
  $modulusEncoded = $jwk["n"] ?? "";
  $exponentEncoded = $jwk["e"] ?? "";
  if (!is_string($modulusEncoded) || !is_string($exponentEncoded) || $modulusEncoded === "" || $exponentEncoded === "") {
    return null;
  }

  $modulus = googleOauthBase64UrlDecode($modulusEncoded);
  $exponent = googleOauthBase64UrlDecode($exponentEncoded);
  if ($modulus === false || $exponent === false) {
    return null;
  }

  $rsaKey = googleOauthDerInteger($modulus) . googleOauthDerInteger($exponent);
  $rsaSequence = "\x30" . googleOauthDerLength($rsaKey);
  $bitString = "\x03" . googleOauthDerLength("\x00" . $rsaSequence);
  $algorithm = hex2bin("300d06092a864886f70d0101010500");
  if ($algorithm === false) {
    return null;
  }

  $der = "\x30" . googleOauthDerLength($algorithm . $bitString);
  return "-----BEGIN PUBLIC KEY-----\n" .
    chunk_split(base64_encode($der), 64, "\n") .
    "-----END PUBLIC KEY-----\n";
}

function googleOauthVerifyIdToken(string $idToken, string $clientId): ?array
{
  $autoloadPath = __DIR__ . "/../vendor/autoload.php";
  if (is_file($autoloadPath)) {
    require_once $autoloadPath;
    $client = new Google\Client(["client_id" => $clientId]);
    $identity = $client->verifyIdToken($idToken);
    if (is_array($identity)) {
      return $identity;
    }
  }

  $parts = explode(".", $idToken);
  if (count($parts) !== 3) {
    return null;
  }

  $headerJson = googleOauthBase64UrlDecode($parts[0]);
  $payloadJson = googleOauthBase64UrlDecode($parts[1]);
  $signature = googleOauthBase64UrlDecode($parts[2]);
  if ($headerJson === false || $payloadJson === false || $signature === false) {
    return null;
  }

  $header = json_decode($headerJson, true);
  $payload = json_decode($payloadJson, true);
  if (!is_array($header) || !is_array($payload)) {
    return null;
  }

  $algorithm = $header["alg"] ?? "";
  $keyId = $header["kid"] ?? "";
  if ($algorithm !== "RS256" || !is_string($keyId) || $keyId === "") {
    return null;
  }

  [$status, $jwks] = googleOauthRequest("https://www.googleapis.com/oauth2/v3/certs");
  $keys = $jwks["keys"] ?? [];
  if ($status !== 200 || !is_array($keys)) {
    return null;
  }

  $matchingKey = null;
  foreach ($keys as $key) {
    if (is_array($key) && ($key["kid"] ?? "") === $keyId) {
      $matchingKey = $key;
      break;
    }
  }
  if ($matchingKey === null) {
    return null;
  }

  $pem = googleOauthJwkToPem($matchingKey);
  if ($pem === null) {
    return null;
  }

  $verified = openssl_verify(
    $parts[0] . "." . $parts[1],
    $signature,
    $pem,
    OPENSSL_ALGO_SHA256
  );
  if ($verified !== 1) {
    return null;
  }

  return $payload;
}

function googleOauthRespond(int $statusCode, array $payload): void
{
  http_response_code($statusCode);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

function googleOauthStartSession(): void
{
  if (session_status() === PHP_SESSION_ACTIVE) {
    return;
  }

  session_set_cookie_params([
    "lifetime" => 0,
    "path" => "/",
    "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
    "httponly" => true,
    "samesite" => "Lax"
  ]);
  session_start();
}

function googleOauthRequest(string $url, ?array $form = null): array
{
  $curl = curl_init($url);
  if ($curl === false) {
    throw new RuntimeException("Tidak dapat memulai koneksi OAuth Google");
  }

  $options = [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2
  ];

  if ($form !== null) {
    $options[CURLOPT_POST] = true;
    $options[CURLOPT_POSTFIELDS] = http_build_query($form);
    $options[CURLOPT_HTTPHEADER] = [
      "Content-Type: application/x-www-form-urlencoded"
    ];
  }

  curl_setopt_array($curl, $options);
  $response = curl_exec($curl);
  $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
  $error = curl_error($curl);
  curl_close($curl);

  if ($response === false) {
    throw new RuntimeException("Koneksi ke layanan OAuth Google gagal: " . $error);
  }

  $payload = json_decode($response, true);
  if (!is_array($payload)) {
    throw new RuntimeException("Respons OAuth Google tidak valid");
  }

  return [$statusCode, $payload];
}
