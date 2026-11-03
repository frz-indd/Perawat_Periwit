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

function formatReviewCount(int $count): string
{
    return $count >= 1000
        ? number_format($count / 1000, 1, ',', '.') . 'k'
        : (string) $count;
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
    <link rel="stylesheet" href="../CSS/kategori.css?v=category-reference-2">
    <link rel="stylesheet" href="../CSS/theme.css?v=dropdown-active-6">
    <link rel="stylesheet" href="../CSS/navbar-common.css?v=4">
</head>
<body class="site-navbar-page category-page">
    <header class="ep-navbar site-navbar">
        <div class="ep-navbar__inner">
            <a class="ep-logo" href="../../index.php">Explore Majaku</a>
            <nav class="ep-menu" aria-label="Menu utama">
                <a class="ep-menu__beranda" href="../../index.php">Beranda</a>
                <details class="category-dropdown ep-menu__kategori" aria-current="page">
                    <summary>
                        <span>Kategori</span>
                        <span class="ep-menu__panah" aria-hidden="true"></span>
                    </summary>
                    <div class="category-menu">
                        <a href="kategori.php?kategori=alam"<?= $categoryKey === 'alam' ? ' aria-current="page"' : ''; ?>>Wisata Alam &amp; Pegunungan</a>
                        <a href="kategori.php?kategori=air-curug"<?= $categoryKey === 'air-curug' ? ' aria-current="page"' : ''; ?>>Wisata Air &amp; Curug</a>
                        <a href="kategori.php?kategori=buatan-kuliner"<?= $categoryKey === 'buatan-kuliner' ? ' aria-current="page"' : ''; ?>>Wisata Buatan &amp; Kuliner</a>
                        <a href="kategori.php?kategori=sejarah-budaya"<?= $categoryKey === 'sejarah-budaya' ? ' aria-current="page"' : ''; ?>>Wisata Sejarah &amp; Budaya</a>
                    </div>
                </details>
                <a class="ep-menu__tentang" href="tentang.php">Tentang</a>
            </nav>
            <div class="ep-aksi">
                <a class="ep-ikon ep-ikon--profil" href="profil.php" aria-label="Profil">
                    <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="20" cy="20" r="18.2"/>
                        <circle cx="20" cy="16.2" r="5.8"/>
                        <path d="M9 33.5C9.7 29.2 12.2 26 15.2 25.2C17 26.6 23 26.6 24.8 25.2C27.8 26 30.3 29.2 31 33.5"/>
                    </svg>
                </a>
            </div>
        </div>
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
                        <a class="destination-card" href="detail-wisata.php?id=<?= rawurlencode((string) $destination['id_wisata']); ?>" aria-label="Lihat detail <?= escapeHtml($destination['nama_wisata']); ?>">
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
                                        <span class="stars" aria-label="Rating <?= number_format($destination['rating'], 1, ',', '.'); ?> dari 5">★★★</span>
                                        <small>(<?= escapeHtml(formatReviewCount((int) $destination['jumlah_ulasan'])); ?> ulasan)</small>
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
                                    <span><?= escapeHtml((string) $destination['wilayah']); ?></span>
                                </p>
                                <small
                                    class="distance"
                                    data-latitude="<?= escapeHtml((string) ($destination['latitude'] ?? '')); ?>"
                                    data-longitude="<?= escapeHtml((string) ($destination['longitude'] ?? '')); ?>"
                                >Menghitung jarak...</small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="category-empty-results" role="status">
                    <span class="empty-search-icon" aria-hidden="true">
                        <i class="bi bi-search"></i>
                        <span>×</span>
                    </span>
                    <h2>Wisata Tidak Di Temukan</h2>
                    <p>Maaf, tidak ada destinasi wisata yang sesuai dengan kata kunci pencarianmu dalam kategori ini</p>
                    <a href="kategori.php?kategori=<?= escapeHtml($categoryKey); ?>">Lihat semua wisata</a>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <script>
        const distanceElements = Array.from(
            document.querySelectorAll('.distance[data-latitude][data-longitude]')
        );
        const destinationsWithCoordinates = distanceElements
            .map(element => ({
                element,
                latitudeValue: element.dataset.latitude.trim(),
                longitudeValue: element.dataset.longitude.trim(),
                latitude: Number(element.dataset.latitude),
                longitude: Number(element.dataset.longitude)
            }))
            .filter(destination =>
                destination.latitudeValue !== '' &&
                destination.longitudeValue !== '' &&
                Number.isFinite(destination.latitude) &&
                destination.latitude >= -90 && destination.latitude <= 90 &&
                Number.isFinite(destination.longitude) &&
                destination.longitude >= -180 && destination.longitude <= 180
            );

        distanceElements.forEach(element => {
            if (!destinationsWithCoordinates.some(destination => destination.element === element)) {
                element.textContent = 'Jarak tidak tersedia';
            }
        });

        if (destinationsWithCoordinates.length > 0) {
            if (!navigator.geolocation) {
                destinationsWithCoordinates.forEach(({ element }) => {
                    element.textContent = 'Lokasi tidak didukung';
                });
            } else {
                navigator.geolocation.getCurrentPosition(
                    position => {
                        const toRadians = degrees => degrees * Math.PI / 180;
                        const originLatitude = toRadians(position.coords.latitude);
                        const originLongitude = toRadians(position.coords.longitude);

                        destinationsWithCoordinates.forEach(destination => {
                            const latitudeDifference =
                                toRadians(destination.latitude) - originLatitude;
                            const longitudeDifference =
                                toRadians(destination.longitude) - originLongitude;
                            const haversine =
                                Math.sin(latitudeDifference / 2) ** 2 +
                                Math.cos(originLatitude) *
                                Math.cos(toRadians(destination.latitude)) *
                                Math.sin(longitudeDifference / 2) ** 2;
                            const distance = 6371 * 2 * Math.atan2(
                                Math.sqrt(haversine),
                                Math.sqrt(1 - haversine)
                            );

                            destination.element.textContent = `${distance.toFixed(2)} KM`;
                        });
                    },
                    () => {
                        destinationsWithCoordinates.forEach(({ element }) => {
                            element.textContent = 'Jarak tidak tersedia';
                        });
                    },
                    { maximumAge: 300000, timeout: 10000 }
                );
            }
        }
    </script>
    <script>
        console.info('[Server-rendered Data] kategori.php', {
            category: {
                id: <?= (int) $categoryId; ?>,
                name: <?= json_encode(
                    $categoryName,
                    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT |
                    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
                ); ?>,
                keyword: <?= json_encode(
                    $keyword,
                    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT |
                    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
                ); ?>
            },
            destinationCount: <?= count($destinations); ?>
        });
    </script>
</body>
</html>
