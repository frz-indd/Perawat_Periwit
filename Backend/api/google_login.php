<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/google_oauth_helpers.php";

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
  header("Allow: GET");
  googleOauthRespond(405, [
    "status" => "error",
    "message" => "Metode tidak diizinkan"
  ]);
}

$config = googleOauthConfig();
if ($config === null) {
  header("Location: ../../FrontEnd/auth/Login.php?google=not_configured", true, 303);
  exit;
}

try {
  googleOauthStartSession();

  $state = rtrim(strtr(base64_encode(random_bytes(32)), "+/", "-_"), "=");
  $nonce = rtrim(strtr(base64_encode(random_bytes(32)), "+/", "-_"), "=");
  $codeVerifier = rtrim(strtr(base64_encode(random_bytes(64)), "+/", "-_"), "=");
  $codeChallenge = rtrim(
    strtr(base64_encode(hash("sha256", $codeVerifier, true)), "+/", "-_"),
    "="
  );

  $_SESSION["google_oauth_state"] = $state;
  $_SESSION["google_oauth_nonce"] = $nonce;
  $_SESSION["google_oauth_code_verifier"] = $codeVerifier;

  $authorizationUrl = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
    "client_id" => $config["client_id"],
    "redirect_uri" => $config["redirect_uri"],
    "response_type" => "code",
    "scope" => "openid email profile",
    "state" => $state,
    "nonce" => $nonce,
    "code_challenge" => $codeChallenge,
    "code_challenge_method" => "S256"
  ]);

  header("Location: " . $authorizationUrl, true, 302);
  exit;
} catch (Throwable $exception) {
  error_log($exception->getMessage());
  googleOauthRespond(500, [
    "status" => "error",
    "message" => "Tidak dapat memulai login Google"
  ]);
}
