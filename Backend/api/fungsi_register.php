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

$name = trim((string) ($input["nama"] ?? $input["Nama_user"] ?? ""));
$email = strtolower(trim((string) ($input["email"] ?? $input["Email"] ?? "")));
$password = $input["password"] ?? $input["Password"] ?? null;

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
  $respond(422, [
    "status" => "error",
    "message" => "Alamat email tidak valid"
  ]);
}

if ($name === "") {
  $emailName = strstr($email, "@", true);
  $name = is_string($emailName) ? $emailName : "Pengguna";
}

if (strlen($name) > 255) {
  $respond(422, [
    "status" => "error",
    "message" => "Nama maksimal 255 karakter"
  ]);
}

if (!is_string($password) || strlen($password) < 8) {
  $respond(422, [
    "status" => "error",
    "message" => "Kata sandi minimal 8 karakter"
  ]);
}

try {
  require_once __DIR__ . "/../database/koneksi.php";

  $passwordHash = password_hash($password, PASSWORD_DEFAULT);
  $statement = $conn->prepare(
    "INSERT INTO `user` (Nama_user, Email, Password) VALUES (?, ?, ?)"
  );
  $statement->bind_param("sss", $name, $email, $passwordHash);
  $statement->execute();

  $userId = (int) $conn->insert_id;
  $statement->close();

  $respond(201, [
    "status" => "success",
    "message" => "Pendaftaran berhasil",
    "data" => [
      "id_user" => $userId,
      "nama" => $name,
      "email" => $email
    ]
  ]);
} catch (mysqli_sql_exception $exception) {
  error_log($exception->getMessage());
  if ($exception->getCode() === 1062) {
    $respond(409, [
      "status" => "error",
      "message" => "Email sudah terdaftar"
    ]);
  }

  $respond(500, [
    "status" => "error",
    "message" => "Pendaftaran gagal"
  ]);
} catch (Throwable $exception) {
  error_log($exception->getMessage());
  $respond(500, [
    "status" => "error",
    "message" => "Pendaftaran gagal"
  ]);
}
