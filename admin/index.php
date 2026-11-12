<?php
// Admin entry point
$scriptPath = str_replace('\\\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$frontEndPos = strpos($scriptPath, '/admin/');
$appBasePath = $frontEndPos !== false ? substr($scriptPath, 0, $frontEndPos) : rtrim(dirname(dirname($scriptPath)), '/.');
$dashboardUrl = $appBasePath . '/FrontEnd/admin/admin-dashboard.php';
$loginUrl = $appBasePath . '/FrontEnd/auth/login-admin.php';
$tokenKey = 'admin_token';
$hasToken = false;
try {
    $hasToken = (!empty($_COOKIE[$tokenKey]) ||
        (!empty($_SESSION[$tokenKey])));
} catch (\Exception $e) {}
if ($hasToken) {
    header('Location: ' . $dashboardUrl);
    exit;
}
header('Location: ' . $loginUrl);
exit;
?>