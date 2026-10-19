<?php
if (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST") {
    header("Allow: POST");
    http_response_code(405);
    exit("Metode tidak diizinkan.");
}

session_start();

$submittedCsrf = $_POST["csrf"] ?? "";
$sessionCsrf = $_SESSION["csrf"] ?? "";
if (
    !is_string($submittedCsrf) ||
    !is_string($sessionCsrf) ||
    $sessionCsrf === "" ||
    !hash_equals($sessionCsrf, $submittedCsrf)
) {
    http_response_code(403);
    exit("Sesi tidak valid. Silakan coba lagi.");
}

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $cookie = session_get_cookie_params();
    setcookie(session_name(), "", [
        "expires" => time() - 42000,
        "path" => $cookie["path"],
        "domain" => $cookie["domain"],
        "secure" => $cookie["secure"],
        "httponly" => $cookie["httponly"],
        "samesite" => $cookie["samesite"] ?? "Lax"
    ]);
}

session_destroy();

header("Location: ../../FrontEnd/auth/Login.php", true, 303);
exit;