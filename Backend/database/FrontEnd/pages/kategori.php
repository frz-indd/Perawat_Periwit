<?php
require_once __DIR__ . "/../../Backend/database/koneksi.php";
require_once __DIR__ . "/../../Backend/database/wisata_repository.php";
require_once __DIR__ . "/navbar-profile-session.php";

$categoryRoutes = [
    "alam" => 1,
    "air-curug" => 4,
    "buatan-kuliner" => 3,
    "sejarah-budaya" => 2
];
$categoryDisplayNames = [
    1 => "Wisata Alam & pegunungan",
    4 => "Wisata Air & Curug",
    3 => "Wisata Buatan & Kuliner",
    2 => "Wisata Sejarah & Budaya"
];
$categoryHeroNames = [
    1 => "Wisata Alam & pegunungan",
    4 => "Wisata Air & Curug",
    3 => "Wisata Buatan & Kuliner",
    2 => "Wisata Sejarah & Budaya"
];
$categoryHeroDescriptions = [
    1 => "Nikmati keindahan alam Majalengka dan Kuningan dari perbukitan, hingga panorama menakjubkan",
    4 => "Nikmati kesegaran wisata air Majalengka dan Kuningan, dari curug yang asri hingga pemandangan alam yang menyegarkan.",
    3 => "Jelajahi beragam destinasi buatan dan nikmati kuliner khas Majalengka dan Kuningan yang menggugah selera.",
    2 => "Jelajahi kekayaan budaya dan jejak sejarah Majalengka dan Kuningan yang penuh cerita dan makna"
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
$keywordTooShort = $keyword !== "" && mb_strlen($keyword, "UTF-8") < 2;
$searchKeyword = $keywordTooShort ? "" : $keyword;
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
    $destinations = $keywordTooShort ? [] : fetchTourismRows($conn, [
        "id_kategori" => $categoryId,
        "keyword" => $searchKeyword
    ]);
    $categoryDestinations = fetchTourismRows($conn, [
        "id_kategori" => $categoryId
    ]);

    // Urutan kartu mengikuti urutan destinasi pada desain kategori yang diberikan.
    $preferredDestinationOrder = [
        4 => ["curug putri", "telaga biru", "curug bangkong", "curug payung"],
        1 => ["cibuntu", "lembah cilengkrang", "bukit pamoroan", "bukit panyaweyan"],
        3 => ["wonderland", "arunika entry garden", "botanika", "pasar bumi pakuwon"],
        2 => ["museum linggarjati", "situs purbakala", "gua maria fatima sawer rahmat"]
    ];
    if (isset($preferredDestinationOrder[$categoryId])) {
        $preferredNames = $preferredDestinationOrder[$categoryId];
        $sortByReferenceOrder = static function (array &$rows) use ($preferredNames): void {
            usort($rows, static function (array $left, array $right) use ($preferredNames): int {
                $rank = static function (string $name) use ($preferredNames): int {
                    $normalized = mb_strtolower($name, "UTF-8");
                    foreach ($preferredNames as $index => $preferred) {
                        if (str_contains($normalized, $preferred)) return $index;
                    }
                    return count($preferredNames) + 1;
                };
                return $rank((string) $left["nama_wisata"]) <=> $rank((string) $right["nama_wisata"])
                    ?: strcasecmp((string) $left["nama_wisata"], (string) $right["nama_wisata"]);
            });
        };
        $sortByReferenceOrder($categoryDestinations);
        if (!$keywordTooShort) $sortByReferenceOrder($destinations);
    }
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

function compactReviewCount(int $count): string
{
    if ($count >= 1000) {
        return number_format($count / 1000, 1, ',', '.') . 'k';
    }
    return number_format($count, 0, ',', '.');
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
    <link rel="stylesheet" href="../CSS/kategori.css?v=category-filter-svg-exact-1">
    <link rel="stylesheet" href="../CSS/theme.css?v=nav-solid-green-8">
    <link rel="stylesheet" href="../CSS/navbar-common.css?v=nav-category-selected-green-4">
</head>
<body class="category-page site-navbar-page">
    <header class="ep-navbar site-navbar">
        <div class="ep-navbar__inner">
            <a class="ep-logo" href="../../index.php">Explore Majaku</a>
            <nav class="ep-menu" aria-label="Menu utama">
                <a class="ep-menu__beranda" href="../../index.php">Beranda</a>
                <details name="site-category-menu" class="category-dropdown ep-menu__kategori" aria-current="page">
                    <summary aria-current="page">
                        <span>Kategori</span>
                        <span class="ep-menu__panah" aria-hidden="true"></span>
                    </summary>
                    <div class="category-menu">
                        <?php foreach ($categoryRoutes as $slug => $navigationCategoryId): ?>
                            <?php if (in_array($navigationCategoryId, $availableCategoryIds, true)): ?>
                                <a href="kategori.php?kategori=<?= escapeHtml($slug); ?>"<?= $categoryKey === $slug ? ' aria-current="page"' : ''; ?>>
                                    <?= escapeHtml($categoryDisplayNames[$navigationCategoryId]); ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </details>
                <a class="ep-menu__tentang" href="tentang.php">Tentang</a>
            </nav>
            <div class="ep-aksi">
                <a class="ep-ikon ep-ikon--profil<?= fotoNavbarClass() ?>" href="profil.php" aria-label="Profil">
                    <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="20" cy="20" r="18.2"/><circle cx="20" cy="16.2" r="5.8"/><path d="M9 33.5C9.7 29.2 12.2 26 15.2 25.2C17 26.6 23 26.6 24.8 25.2C27.8 26 30.3 29.2 31 33.5"/></svg>
                    <?= fotoNavbarMarkup() ?>
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
                <p><?= escapeHtml($categoryHeroDescriptions[$categoryId] ?? ($category['deskripsi'] ?? '')); ?></p>
            </div>
        </section>

        <section class="category-content">
            <form class="search-controls" id="search" action="kategori.php" method="get" role="search" novalidate>
                <div class="search-box">
                <input type="hidden" name="kategori" value="<?= escapeHtml($categoryKey); ?>">
                <label class="visually-hidden" for="destination-search">Cari destinasi atau lokasi</label>
                <input
                    id="destination-search"
                    type="search"
                    name="keyword"
                    placeholder="Cari destinasi,kata kunci atau lokasi"
                    value="<?= escapeHtml($keyword); ?>"
                >
                    <button type="submit" aria-label="Cari"><i class="bi bi-search" aria-hidden="true"></i></button>
                </div>
                <button class="filter-button" type="button" aria-label="Filter" title="Filter">
                    <svg class="filter-button__icon" width="70" height="66" viewBox="0 0 70 66" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <rect width="70" height="66" rx="20" fill="#D9D9D9"/>
                        <path d="M21.2692 28.0476H49.7308V30.9047H21.2692V28.0476ZM27.6731 37.0953H43.3269V39.9524H27.6731V37.0953ZM31.9423 46.1429H39.0577V49H31.9423V46.1429ZM17 19H54V21.8571H17V19Z" fill="#166C5C"/>
                    </svg>
                </button>
            </form>
            <p class="search-validation" id="search-validation" role="alert"<?= $keywordTooShort ? '' : ' hidden' ?>>ⓘ Masukkan Minimal 2 Karakter</p>

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
                                    <span class="stars" aria-label="Rating <?= $destination['rating'] === null ? 'belum tersedia' : number_format($destination['rating'], 1, ',', '.') . ' dari 5'; ?>">★ ★ ★</span>
                                    <small>(<?= compactReviewCount((int) $destination['jumlah_ulasan']); ?> ulasan)</small>
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
                <div class="empty-state">
                    <div class="empty-state__icon" aria-hidden="true"><i class="bi bi-search"></i><span>×</span></div>
                    <h3>Wisata Tidak Di Temukan</h3>
                    <p>Maaf, tidak ada destinasi wisata yang sesuai<br class="desktop-break"> dengan kata kunci pencarianmu dalam kategori ini</p>
                    <a class="empty-state__button" href="kategori.php?kategori=<?= escapeHtml($categoryKey); ?>">Lihat semua wisata</a>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <script src="../Js/api-endpoint-labels.js"></script>
    <script>
        const categorySearchForm = document.getElementById('search');
        const categorySearchInput = document.getElementById('destination-search');
        const categorySearchValidation = document.getElementById('search-validation');
        categorySearchForm?.addEventListener('submit', event => {
            const term = categorySearchInput.value.trim();
            if (term.length > 0 && Array.from(term).length < 2) {
                categorySearchValidation.hidden = false;
                categorySearchInput.setAttribute('aria-invalid', 'true');
                return;
            }
            categorySearchValidation.hidden = true;
            categorySearchInput.removeAttribute('aria-invalid');
        });
        categorySearchInput?.addEventListener('input', () => {
            const term = categorySearchInput.value.trim();
            if (term.length === 0 || Array.from(term).length >= 2) {
                categorySearchValidation.hidden = true;
                categorySearchInput.removeAttribute('aria-invalid');
            }
        });

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
</body>
</html>
