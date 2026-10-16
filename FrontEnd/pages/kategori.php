<?php
require_once __DIR__ . "/../../Backend/database/koneksi.php";
require_once __DIR__ . "/../../Backend/database/wisata_repository.php";

$categoryRoutes = [
    "alam" => 1,
    "air-curug" => 4,
    "buatan-kuliner" => 3,
    "sejarah-budaya" => 2
];
$categorySlugs = array_flip($categoryRoutes);
$categoryKey = $_GET["kategori"] ?? "alam";
if (!is_string($categoryKey) || !isset($categoryRoutes[$categoryKey])) {
    $categoryKey = "alam";
}

$categories = fetchActiveCategories($conn);
$categoryId = $categoryRoutes[$categoryKey];
$category = null;
foreach ($categories as $availableCategory) {
    if ($availableCategory["Id_kategori"] === $categoryId) {
        $category = $availableCategory;
        break;
    }
}

if ($category === null) {
    http_response_code(404);
    $destinations = [];
    $category = [
        "Id_kategori" => $categoryId,
        "nama_kategori" => "Kategori tidak tersedia",
        "deskripsi" => "Kategori ini belum tersedia."
    ];
} else {
    $keyword = isset($_GET["keyword"]) && is_string($_GET["keyword"])
        ? trim($_GET["keyword"])
        : "";
    $destinations = fetchTourismRows($conn, [
        "id_kategori" => $categoryId,
        "keyword" => $keyword
    ]);
}

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escapeHtml($category['nama_kategori']); ?> | Explore Majaku</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../CSS/kategori.css">
</head>
<body>
    <header class="site-header">
        <nav class="main-nav" aria-label="Navigasi utama">
            <a class="brand" href="../../index.php">Explore Majaku</a>
            <div class="nav-links">
                <a href="../../index.php">Beranda</a>
                <a class="active category-trigger" href="kategori.php?kategori=alam" aria-current="page">kategori</a>
                <a href="tentang.php">Tentang</a>
            </div>
            <div class="nav-icons">
                <a href="#search" aria-label="Cari destinasi"><i class="bi bi-search" aria-hidden="true"></i></a>
                <a href="../auth/Login.php" aria-label="Profil"><svg class="profile-outline-icon" viewBox="0 0 40 40" aria-hidden="true"><circle cx="20" cy="20" r="17"></circle><circle cx="20" cy="15" r="5"></circle><path d="M9 31c1.7-5 5.5-7.5 11-7.5S29.3 26 31 31"></path></svg></a>
            </div>
        </nav>
    </header>

    <nav class="category-nav" aria-label="Pilih kategori wisata">
        <?php foreach ($categories as $navigationCategory): ?>
            <?php $slug = $categorySlugs[$navigationCategory["Id_kategori"]] ?? null; ?>
            <?php if ($slug !== null): ?>
                <a href="kategori.php?kategori=<?= escapeHtml($slug); ?>"<?= $categoryKey === $slug ? ' aria-current="page"' : ''; ?>>
                    <?= escapeHtml($navigationCategory["nama_kategori"]); ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <main>
        <section class="hero" aria-label="<?= escapeHtml($category['nama_kategori']); ?>">
            <div class="hero-images" aria-hidden="true">
                <img src="../../<?= escapeHtml($category['gambar'] ?? 'FrontEnd/assets/images/img.png'); ?>" alt="">
            </div>
            <div class="hero-shade"></div>
            <div class="hero-copy">
                <h1><?= escapeHtml(mb_strtoupper($category['nama_kategori'], 'UTF-8')); ?></h1>
                <p><?= escapeHtml($category['deskripsi'] ?? ''); ?></p>
            </div>
        </section>

        <section class="category-content">
            <form class="search-box" id="search" action="kategori.php" method="get" role="search">
                <input type="hidden" name="kategori" value="<?= escapeHtml($categoryKey); ?>">
                <label class="visually-hidden" for="destination-search">Cari destinasi atau lokasi</label>
                <input
                    id="destination-search"
                    type="search"
                    name="keyword"
                    placeholder="Cari destinasi alam atau lokasi"
                    value="<?= escapeHtml($keyword); ?>"
                >
                <button type="submit" aria-label="Cari"><i class="bi bi-search" aria-hidden="true"></i></button>
            </form>

            <h2><?= escapeHtml($category['nama_kategori']); ?></h2>
            <?php if ($destinations !== []): ?>
                <div class="destination-grid">
                    <?php foreach ($destinations as $destination): ?>
                        <article class="destination-card">
                            <img
                                class="destination-image"
                                src="../../<?= escapeHtml($destination['gambar'] ?? 'FrontEnd/assets/images/img.png'); ?>"
                                alt="<?= escapeHtml($destination['nama_wisata']); ?>"
                                loading="lazy"
                            >
                            <div class="card-content">
                                <h3><?= escapeHtml($destination['nama_wisata']); ?></h3>
                                <div class="rating">
                                    <?php if ($destination['rating'] !== null): ?>
                                        <span class="stars" aria-label="Rating <?= number_format($destination['rating'], 1, ',', '.'); ?> dari 5">★ <?= number_format($destination['rating'], 1, ',', '.'); ?>/5</span>
                                        <small>(<?= (int) $destination['jumlah_ulasan']; ?> ulasan)</small>
                                    <?php else: ?>
                                        <small>Belum ada ulasan</small>
                                    <?php endif; ?>
                                </div>
                                <p class="location">
                                    <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
                                    <span><?= escapeHtml($destination['wilayah']); ?></span>
                                </p>
                                <small class="distance">
                                    <?= $destination['harga_tiket'] > 0
                                        ? 'Rp ' . number_format($destination['harga_tiket'], 0, ',', '.')
                                        : 'Harga belum tersedia'; ?>
                                </small>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-state">Destinasi tidak ditemukan. Coba kata pencarian yang lain.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
