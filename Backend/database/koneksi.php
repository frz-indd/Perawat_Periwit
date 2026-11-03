<?php
$conn = null;

try {
    $localConfigPath = __DIR__ . '/database.local.php';
    $localConfig = [];

    if (is_file($localConfigPath)) {
        $loadedConfig = require $localConfigPath;
        if (!is_array($loadedConfig)) {
            throw new RuntimeException('Konfigurasi database lokal tidak valid.');
        }
        $localConfig = $loadedConfig;
    }

    $environmentValue = static function (string $name, $fallback): string {
        $value = getenv($name);
        return is_string($value) && $value !== ''
            ? $value
            : (string) $fallback;
    };

    $host = $environmentValue('DB_HOST', $localConfig['host'] ?? 'localhost');
    $user = $environmentValue('DB_USER', $localConfig['user'] ?? 'root');
    $pass = $environmentValue('DB_PASS', $localConfig['password'] ?? '');
    $db = $environmentValue('DB_NAME', $localConfig['database'] ?? 'eksplormajaku');
    $port = (int) $environmentValue('DB_PORT', $localConfig['port'] ?? 3306);

    if ($port < 1 || $port > 65535) {
        throw new RuntimeException('Port database tidak valid.');
    }

    $conn = new mysqli($host, $user, $pass, $db, $port);
    $conn->set_charset('utf8mb4');
} catch (Throwable $exception) {
    error_log('Database connection failed: ' . $exception->getMessage());
    $conn = null;
}
?>