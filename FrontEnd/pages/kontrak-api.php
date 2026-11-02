<?php
$scriptName = str_replace("\\", "/", (string) ($_SERVER["SCRIPT_NAME"] ?? ""));
$appRoot = dirname(dirname(dirname($scriptName)));
$appRoot = $appRoot === "/" || $appRoot === "." ? "" : rtrim($appRoot, "/");
$scheme = (
    (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ||
    (int) ($_SERVER["SERVER_PORT"] ?? 0) === 443
) ? "https" : "http";
$host = (string) ($_SERVER["HTTP_HOST"] ?? "localhost");
$baseUrl = $scheme . "://" . $host . $appRoot;

$endpoints = [
    ["POST", "/Backend/api/fungsi_login.php", "Login pengguna atau admin.", true],
    ["POST", "/Backend/api/fungsi_register.php", "Mendaftarkan akun pengguna.", true],
    ["GET", "/Backend/api/google_login.php", "Memulai login Google.", true],
    ["GET", "/Backend/api/google_login.php?intent=register", "Memulai pendaftaran Google.", true],
    ["GET", "/Backend/api/google_callback.php", "Menyelesaikan proses login Google.", true],
    ["GET", "/users/me", "Mengambil profil pengguna yang login.", true],
    ["PUT", "/users/me", "Mengubah nama dan email pengguna.", true],
    ["GET", "/Backend/api/wisata.php", "Mengambil daftar wisata dan informasi lokasinya.", true],
    ["GET", "/categories", "Mengambil daftar kategori aktif.", true],
    ["GET", "/categories/{id}/wisata", "Mengambil wisata dalam kategori aktif.", true],
    ["GET", "/api/destinations/search?q={kata-kunci}", "Mencari destinasi melalui API JSON.", true],
    ["GET", "/api/destinations/{id}/location", "Mengambil lokasi satu destinasi.", true],
    ["PUT", "/api/destinations/{id}/location", "Mengubah lokasi destinasi oleh admin.", true],
    ["POST", "/api/destinations/{id}/reviews", "Menambahkan ulasan destinasi.", true],
    ["POST", "/api/favorites", "Menambah atau menghapus favorit destinasi.", true],
    ["PUT", "/api/user/change-password", "Mengubah password pengguna yang login.", true],
    ["POST", "/Backend/api/logout.php", "Mengakhiri session pengguna atau admin.", true]
];

$escape = static fn (string $value): string => htmlspecialchars(
    $value,
    ENT_QUOTES | ENT_SUBSTITUTE,
    "UTF-8"
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Endpoint API | Explore Majaku</title>
    <link rel="stylesheet" href="../CSS/kontrak-api.css">
</head>
<body>
    <main class="api-page">
        <a class="back-link" href="../../index.php">Kembali ke Beranda</a>
        <header class="page-header">
            <p class="eyebrow">EXPLORE MAJAKU</p>
            <h1>Endpoint API</h1>
            <p>Daftar alamat API yang digunakan aplikasi.</p>
            <div class="base-url">
                <span>Base URL</span>
                <code><?= $escape($baseUrl) ?></code>
            </div>
        </header>

        <section class="endpoint-list" aria-label="Daftar endpoint API">
            <?php foreach ($endpoints as [$method, $path, $description, $available]): ?>
                <article class="endpoint-card">
                    <div class="endpoint-heading">
                        <span class="method method-<?= strtolower($method) ?>"><?= $escape($method) ?></span>
                        <span class="availability <?= $available ? "available" : "unavailable" ?>">
                            <?= $available ? "Tersedia" : "Belum tersedia" ?>
                        </span>
                    </div>
                    <code class="endpoint-url"><?= $escape($baseUrl . $path) ?></code>
                    <p><?= $escape($description) ?></p>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
