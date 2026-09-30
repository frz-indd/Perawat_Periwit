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
  googleOauthRespond(503, [
    "status" => "error",
    "message" => "Login Google belum dikonfigurasi di server"
  ]);
}

googleOauthStartSession();
$expectedState = $_SESSION["google_oauth_state"] ?? "";
$expectedNonce = $_SESSION["google_oauth_nonce"] ?? "";
$codeVerifier = $_SESSION["google_oauth_code_verifier"] ?? "";
unset(
  $_SESSION["google_oauth_state"],
  $_SESSION["google_oauth_nonce"],
  $_SESSION["google_oauth_code_verifier"]
);

$returnedState = $_GET["state"] ?? "";
if (
  !is_string($expectedState) || $expectedState === "" ||
  !is_string($returnedState) || !hash_equals($expectedState, $returnedState)
) {
  googleOauthRespond(400, [
    "status" => "error",
    "message" => "State OAuth tidak valid atau sudah digunakan"
  ]);
}

if (isset($_GET["error"])) {
  googleOauthRespond(401, [
    "status" => "error",
    "message" => "Login Google dibatalkan"
  ]);
}

$authorizationCode = $_GET["code"] ?? "";
if (!is_string($authorizationCode) || $authorizationCode === "" || $codeVerifier === "") {
  googleOauthRespond(400, [
    "status" => "error",
    "message" => "Kode otorisasi Google tidak tersedia"
  ]);
}

try {
  [$tokenStatus, $tokens] = googleOauthRequest(
    "https://oauth2.googleapis.com/token",
    [
      "code" => $authorizationCode,
      "client_id" => $config["client_id"],
      "client_secret" => $config["client_secret"],
      "redirect_uri" => $config["redirect_uri"],
      "grant_type" => "authorization_code",
      "code_verifier" => $codeVerifier
    ]
  );

  $idToken = $tokens["id_token"] ?? "";
  if ($tokenStatus !== 200 || !is_string($idToken) || $idToken === "") {
    googleOauthRespond(401, [
      "status" => "error",
      "message" => "Google tidak menerima kode otorisasi"
    ]);
  }

  $identity = googleOauthVerifyIdToken($idToken, $config["client_id"]);
  if ($identity === null) {
    googleOauthRespond(401, [
      "status" => "error",
      "message" => "Token identitas Google tidak valid"
    ]);
  }

  $issuer = $identity["iss"] ?? "";
  $audience = $identity["aud"] ?? "";
  $expiresAt = filter_var($identity["exp"] ?? null, FILTER_VALIDATE_INT);
  $emailVerified = $identity["email_verified"] ?? false;
  $isEmailVerified = $emailVerified === true || $emailVerified === "true";
  $subject = $identity["sub"] ?? "";
  $email = strtolower(trim((string) ($identity["email"] ?? "")));
  $name = trim((string) ($identity["name"] ?? ""));
  $nonce = $identity["nonce"] ?? "";

  if (
    !in_array($issuer, ["accounts.google.com", "https://accounts.google.com"], true) ||
    $audience !== $config["client_id"] ||
    $expiresAt === false || $expiresAt <= time() ||
    !is_string($nonce) || !is_string($expectedNonce) ||
    $expectedNonce === "" || !hash_equals($expectedNonce, $nonce) ||
    !$isEmailVerified ||
    !is_string($subject) || $subject === "" || strlen($subject) > 255 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255
  ) {
    googleOauthRespond(401, [
      "status" => "error",
      "message" => "Identitas Google tidak valid atau email belum diverifikasi"
    ]);
  }

  if ($name === "") {
    $name = strstr($email, "@", true) ?: $email;
  }
  if (strlen($name) > 255) {
    $name = substr($name, 0, 255);
  }

  require_once __DIR__ . "/../database/koneksi.php";
  $conn->begin_transaction();

  try {
    $statement = $conn->prepare(
      "SELECT u.Id_user, u.Nama_user, u.Email
      FROM user_oauth_account AS account
      INNER JOIN `user` AS u ON u.Id_user = account.Id_user
      WHERE account.provider = 'google' AND account.provider_user_id = ?
      LIMIT 1 FOR UPDATE"
    );
    $statement->bind_param("s", $subject);
    $statement->execute();
    $statement->bind_result($userId, $userName, $userEmail);
    $userFound = $statement->fetch();
    $statement->close();

    if (!$userFound) {
      $statement = $conn->prepare(
        "SELECT Id_user, Nama_user, Email FROM `user` WHERE Email = ? LIMIT 1 FOR UPDATE"
      );
      $statement->bind_param("s", $email);
      $statement->execute();
      $statement->bind_result($userId, $userName, $userEmail);
      $userFound = $statement->fetch();
      $statement->close();

      if (!$userFound) {
        $statement = $conn->prepare(
          "INSERT INTO `user` (Nama_user, Email, Password) VALUES (?, ?, NULL)"
        );
        $statement->bind_param("ss", $name, $email);
        $statement->execute();
        $userId = (int) $conn->insert_id;
        $userName = $name;
        $userEmail = $email;
        $statement->close();
      }

      $provider = "google";
      $statement = $conn->prepare(
        "INSERT INTO user_oauth_account (Id_user, provider, provider_user_id, email)
        VALUES (?, ?, ?, ?)"
      );
      $statement->bind_param("isss", $userId, $provider, $subject, $email);
      $statement->execute();
      $statement->close();
    }

    $conn->commit();
  } catch (Throwable $exception) {
    $conn->rollback();
    throw $exception;
  }

  session_regenerate_id(true);
  $_SESSION["id_user"] = (int) $userId;
  $_SESSION["nama_user"] = $userName;
  $_SESSION["email"] = $userEmail;

  googleOauthRespond(200, [
    "status" => "success",
    "message" => "Login Google berhasil",
    "data" => [
      "id_user" => (int) $userId,
      "nama" => $userName,
      "email" => $userEmail
    ]
  ]);
} catch (Throwable $exception) {
  error_log($exception->getMessage());
  googleOauthRespond(500, [
    "status" => "error",
    "message" => "Login Google gagal"
  ]);
}
