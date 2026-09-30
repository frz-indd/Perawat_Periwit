<?php
function googleOauthConfig(): ?array
{
  $clientId = getenv("GOOGLE_CLIENT_ID");
  $clientSecret = getenv("GOOGLE_CLIENT_SECRET");
  $redirectUri = getenv("GOOGLE_REDIRECT_URI");

  if (
    !is_string($clientId) || trim($clientId) === "" ||
    !is_string($clientSecret) || trim($clientSecret) === "" ||
    !is_string($redirectUri) || trim($redirectUri) === ""
  ) {
    return null;
  }

  return [
    "client_id" => trim($clientId),
    "client_secret" => trim($clientSecret),
    "redirect_uri" => trim($redirectUri)
  ];
}

function googleOauthVerifyIdToken(string $idToken, string $clientId): ?array
{
  $autoloadPath = __DIR__ . "/../vendor/autoload.php";
  if (!is_file($autoloadPath)) {
    throw new RuntimeException("Dependensi Google API Client belum terpasang");
  }

  require_once $autoloadPath;
  $client = new Google\Client(["client_id" => $clientId]);
  $identity = $client->verifyIdToken($idToken);

  return is_array($identity) ? $identity : null;
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
