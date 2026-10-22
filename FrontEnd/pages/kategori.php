<?php
require_once __DIR__ . "/../../Backend/database/koneksi.php";
require_once __DIR__ . "/../../Backend/database/wisata_repository.php";

$categoryRoutes = [
    "alam" => 1,
    "air-curug" => 4,
    "buatan-kuliner" => 3,
    "sejarah-budaya" => 2
];
$categoryDisplayNames = [
    1 => "Wisata Alam & Pegunungan",
    4 => "Wisata Air & Curug",
    3 => "Wisata Buatan & Kuliner",
    2 => "Wisata Sejarah & Budaya"
];
$categoryHeroNames = [
    1 => "Wisata Alam dan Pegunungan",
    4 => "Wisata Air dan Curug",
    3 => "Wisata Buatan dan Kuliner",
    2 => "Wisata Sejarah dan Budaya"
];
$categoryKey = $_GET["kategori"] ?? "alam";
if (!is_string($categoryKey) || !isset($categoryRoutes[$categoryKey])) {
    $categoryKey = "alam";
}

$categories = fetchActiveCategories($conn);
$availableCategoryIds = array_map('intval', array_column($categories, "Id_kategori"));
$categoryId = $categoryRoutes[$categoryKey];
$category = null;
$keyword = isset($_GET["keyword"]) && is_string($_GET["keyword"])
    ? trim($_GET["keyword"])
    : "";
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
    $categoryDestinations = [];
} else {
    $destinations = fetchTourismRows($conn, [
        "id_kategori" => $categoryId,
        "keyword" => $keyword
    ]);
    $categoryDestinations = fetchTourismRows($conn, [
        "id_kategori" => $categoryId
    ]);
}
$categoryName = $categoryDisplayNames[$categoryId] ?? $category["nama_kategori"];
$categoryHeroName = $categoryHeroNames[$categoryId] ?? $categoryName;

$heroImageFallbacks = [
    1 => [
        "FrontEnd/assets/images/pamoroan.jpg",
        "FrontEnd/assets/images/TeraseringPanyawean.jpg",
        "FrontEnd/assets/images/gununglandscape.jpg"
    ],
    2 => [
        "FrontEnd/assets/images/Arunika.png",
        "FrontEnd/assets/images/Woodland.png",
        "FrontEnd/assets/images/gununglandscape.jpg"
    ],
    3 => [
        "FrontEnd/assets/images/Woodland.png",
        "FrontEnd/assets/images/Arunika.png",
        "FrontEnd/assets/images/makanan.png"
    ],
    4 => [
        "FrontEnd/assets/images/CurugPutri.png",
        "FrontEnd/assets/images/telaga.png",
        "FrontEnd/assets/images/img.png"
    ]
];
$heroImages = [];
$heroImageCandidates = array_merge(
    array_column($categoryDestinations, "gambar"),
    [$category["gambar"] ?? null],
    $heroImageFallbacks[$categoryId]
);
foreach ($heroImageCandidates as $image) {
    if (is_string($image) && $image !== "" && !in_array($image, $heroImages, true)) {
        $heroImages[] = $image;
        if (count($heroImages) === 3) {
            break;
        }
    }
}
while (count($heroImages) < 3) {
    $heroImages[] = $heroImages[0] ?? "FrontEnd/assets/images/img.png";
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
    <title><?= escapeHtml($categoryName); ?> | Explore Majaku</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fragment+Mono:ital@0;1&family=Frank+Ruhl+Libre:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=Kaushan+Script&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../CSS/kategori.css?v=category-fonts-2">
    <link rel="stylesheet" href="../CSS/theme.css?v=shared-layout-4">
</head>
<body>
    <header class="site-header">
        <nav class="main-nav" aria-label="Navigasi utama">
            <a class="brand" href="../../index.php">Explore Majaku</a>
            <div class="nav-links">
                <a href="../../index.php">Beranda</a>
                <details class="category-dropdown">
                    <summary class="active">kategori <i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
                    <div class="category-nav">
                        <?php foreach ($categoryRoutes as $slug => $navigationCategoryId): ?>
                            <?php if (in_array($navigationCategoryId, $availableCategoryIds, true)): ?>
                                <a href="kategori.php?kategori=<?= escapeHtml($slug); ?>"<?= $categoryKey === $slug ? ' aria-current="page"' : ''; ?>>
                                    <?= escapeHtml($categoryDisplayNames[$navigationCategoryId]); ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </details>
                <a href="tentang.php">Tentang</a>
            </div>
            <div class="nav-icons">
                <a href="#search" aria-label="Cari destinasi"><i class="bi bi-search" aria-hidden="true"></i></a>
                <a href="../auth/Login.php" aria-label="Profil"><svg class="profile-outline-icon" viewBox="0 0 40 40" aria-hidden="true"><circle cx="20" cy="20" r="17"></circle><circle cx="20" cy="15" r="5"></circle><path d="M9 31c1.7-5 5.5-7.5 11-7.5S29.3 26 31 31"></path></svg></a>
            </div>
        </nav>
    </header>

    <main>
        <section class="hero" aria-label="<?= escapeHtml($categoryName); ?>">
            <div class="hero-images" aria-hidden="true">
                <?php foreach ($heroImages as $heroImage): ?>
                    <img src="../../<?= escapeHtml($heroImage); ?>" alt="">
                <?php endforeach; ?>
            </div>
            <div class="hero-shade"></div>
            <div class="hero-copy">
                <h1><?= escapeHtml(mb_strtoupper($categoryHeroName, 'UTF-8')); ?></h1>
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

            <h2><?= escapeHtml($categoryName); ?></h2>
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
                                    <svg class="map-pin-icon" viewBox="0 0 40 44" aria-hidden="true">
                                        <ellipse class="map-pin-ground" cx="20" cy="37" rx="14" ry="5"></ellipse>
                                        <path class="map-pin-ground-right" d="M20 32a14 5 0 0 1 0 10z"></path>
                                        <path class="map-pin-body" d="M20 3C13.4 3 9 7.9 9 13.8c0 7.2 8.6 14.6 11 19.2 2.4-4.6 11-12 11-19.2C31 7.9 26.6 3 20 3z"></path>
                                        <circle class="map-pin-hole" cx="20" cy="13.5" r="4.8"></circle>
                                    </svg>
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
