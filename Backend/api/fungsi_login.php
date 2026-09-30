<?php
header("Content-Type: application/json; charset=utf-8");

$respond = static function (int $statusCode, array $payload): void {
  http_response_code($statusCode);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
};

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Allow: POST");
  $respond(405, [
    "status" => "error",
    "message" => "Metode tidak diizinkan"
  ]);
}

$contentType = $_SERVER["CONTENT_TYPE"] ?? "";
$input = $_POST;
if (stripos($contentType, "application/json") !== false) {
  $input = json_decode(file_get_contents("php://input"), true);
  if (!is_array($input)) {
    $respond(400, [
      "status" => "error",
      "message" => "Format JSON tidak valid"
    ]);
  }
}

$email = strtolower(trim((string) ($input["email"] ?? $input["Email"] ?? "")));
$password = $input["password"] ?? $input["Password"] ?? null;

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !is_string($password) || $password === "") {
  $respond(422, [
    "status" => "error",
    "message" => "Email dan kata sandi wajib diisi"
  ]);
}

try {
  require_once __DIR__ . "/../database/koneksi.php";

  $statement = $conn->prepare(
    "SELECT Id_user, Nama_user, Email, Password FROM `user` WHERE Email = ? LIMIT 1"
  );
  $statement->bind_param("s", $email);
  $statement->execute();
  $statement->bind_result($userId, $name, $storedEmail, $passwordHash);
  $foundUser = $statement->fetch();
  $statement->close();

  if (
    !$foundUser ||
    !is_string($passwordHash) ||
    $passwordHash === "" ||
    !password_verify($password, $passwordHash)
  ) {
    $respond(401, [
      "status" => "error",
      "message" => "Email atau kata sandi salah"
    ]);
  }

  session_set_cookie_params([
    "lifetime" => 0,
    "path" => "/",
    "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
    "httponly" => true,
    "samesite" => "Lax"
  ]);
  session_start();
  session_regenerate_id(true);
  $_SESSION["id_user"] = (int) $userId;
  $_SESSION["nama_user"] = $name;
  $_SESSION["email"] = $storedEmail;

  $respond(200, [
    "status" => "success",
    "message" => "Login berhasil",
    "data" => [
      "id_user" => (int) $userId,
      "nama" => $name,
      "email" => $storedEmail
    ]
  ]);
} catch (Throwable $exception) {
  error_log($exception->getMessage());
  $respond(500, [
    "status" => "error",
    "message" => "Login gagal"
  ]);
}
