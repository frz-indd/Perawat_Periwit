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
$keywordLength = preg_match_all('/./us', $keyword);
$isShortKeyword = $keyword !== "" && $keywordLength !== false && $keywordLength < 2;
$selectedRegions = is_array($_GET["wilayah"] ?? null)
    ? array_values(array_filter(
        $_GET["wilayah"],
        static fn ($value) => is_string($value) && in_array($value, ["Kuningan", "Majalengka"], true)
    ))
    : [];
$selectedDistances = is_array($_GET["distance"] ?? null)
    ? array_values(array_filter(
        $_GET["distance"],
        static fn ($value) => is_string($value) && in_array($value, ["0-5", "5-10", "10-20", "20-30"], true)
    ))
    : [];
$minimumBudgetInput = is_string($_GET["min"] ?? null) ? trim($_GET["min"]) : "";
$maximumBudgetInput = is_string($_GET["max"] ?? null) ? trim($_GET["max"]) : "";
$minimumBudget = $minimumBudgetInput === ""
    ? 0
    : filter_var($minimumBudgetInput, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]]);
$maximumBudget = $maximumBudgetInput === ""
    ? 1000000
    : filter_var($maximumBudgetInput, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]]);
$hasBudgetError = $minimumBudget === false ||
    $maximumBudget === false ||
    (
        $minimumBudgetInput !== "" &&
        $maximumBudgetInput !== "" &&
        $minimumBudget !== false &&
        $maximumBudget !== false &&
        $minimumBudget > $maximumBudget
    );
$filterSubmitted = ($_GET["filter_submitted"] ?? "") === "1";
$selectedCategories = is_array($_GET["kategori_filter"] ?? null)
    ? array_values(array_filter(array_map(
        static function ($value) {
            return is_string($value) || is_int($value)
                ? filter_var($value, FILTER_VALIDATE_INT)
                : false;
        },
        $_GET["kategori_filter"]
    ), static fn ($value) => $value !== false && in_array((int) $value, $availableCategoryIds, true)))
    : [];
if (!$filterSubmitted) {
    $selectedCategories = [$categoryId];
}
if ($selectedCategories !== []) {
    $selectedCategories = array_values(array_unique(array_map("intval", $selectedCategories)));
}
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
    $categoryDestinations = fetchTourismRows($conn, [
        "id_kategori" => $categoryId
    ]);
    $destinationFilters = [];
    if (!$isShortKeyword && $keyword !== "") {
        $destinationFilters["keyword"] = $keyword;
    }
    if ($selectedCategories !== []) {
        $destinationFilters["id_kategori_in"] = $selectedCategories;
    }
    if (!$hasBudgetError && $minimumBudgetInput !== "") {
        $destinationFilters["min_price"] = $minimumBudget;
    }
    if (!$hasBudgetError && $maximumBudgetInput !== "") {
        $destinationFilters["max_price"] = $maximumBudget;
    }
    $destinations = $isShortKeyword || $hasBudgetError
        ? []
        : fetchTourismRows($conn, $destinationFilters);
    if ($selectedRegions !== []) {
        $destinations = array_values(array_filter(
            $destinations,
            static function ($destination) use ($selectedRegions): bool {
                $region = stripos($destination["wilayah"], "Majalengka") !== false
                    ? "Majalengka"
                    : "Kuningan";
                return in_array($region, $selectedRegions, true);
            }
        ));
    }
}
$categoryName = $categoryDisplayNames[$categoryId] ?? $category["nama_kategori"];
$categoryHeroName = $categoryHeroNames[$categoryId] ?? $categoryName;
$resultsTitle = $categoryName;
if ($filterSubmitted) {
    if ($selectedCategories === []) {
        $resultsTitle = "Semua Wisata";
    } elseif (count($selectedCategories) > 1 || $selectedCategories[0] !== $categoryId) {
        $resultsTitle = "Hasil Filter Wisata";
    }
}

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
    <link rel="stylesheet" href="../CSS/kategori.css?v=category-reference-3">
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
            <form class="search-box<?= $isShortKeyword ? ' has-validation' : ''; ?>" id="search" action="kategori.php" method="get" role="search" novalidate>
                <input type="hidden" name="kategori" value="<?= escapeHtml($categoryKey); ?>">
                <label class="visually-hidden" for="destination-search">Cari nama destinasi</label>
                <input
                    id="destination-search"
                    type="search"
                    name="keyword"
                    placeholder="Cari nama destinasi wisata"
                    value="<?= escapeHtml($keyword); ?>"
                    minlength="2"
                    aria-describedby="categorySearchValidation"
                    aria-invalid="<?= $isShortKeyword ? 'true' : 'false'; ?>"
                >
                <button type="submit" aria-label="Cari"><i class="bi bi-search" aria-hidden="true"></i></button>
            </form>
            <p
                class="category-search-validation"
                id="categorySearchValidation"
                role="alert"
                <?= $isShortKeyword ? '' : 'hidden'; ?>
            >
                <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                Masukkan Minimal 2 Karakter
            </p>

            <form class="category-filter-form" id="categoryFilterForm" action="kategori.php" method="get">
                <input type="hidden" name="kategori" value="<?= escapeHtml($categoryKey); ?>">
                <input type="hidden" name="keyword" id="filterKeyword" value="<?= escapeHtml($keyword); ?>">
                <input type="hidden" name="filter_submitted" value="1">

                <h2 class="category-filter-title">Filter Pencarian</h2>
                <div class="category-filter-groups">
                    <fieldset class="category-filter-group">
                        <legend>Jarak</legend>
                        <?php foreach ([
                            '0-5' => '5 Km',
                            '5-10' => '5 - 10 Km',
                            '10-20' => '10 - 20 Km',
                            '20-30' => '20 - 30 Km'
                        ] as $distanceValue => $distanceLabel): ?>
                            <label class="category-filter-option">
                                <input type="checkbox" name="distance[]" value="<?= $distanceValue; ?>"
                                    <?= in_array($distanceValue, $selectedDistances, true) ? 'checked' : ''; ?>>
                                <span><?= escapeHtml($distanceLabel); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>

                    <fieldset class="category-filter-group">
                        <legend>Wilayah</legend>
                        <?php foreach (['Kuningan', 'Majalengka'] as $region): ?>
                            <label class="category-filter-option">
                                <input type="checkbox" name="wilayah[]" value="<?= escapeHtml($region); ?>"
                                    <?= in_array($region, $selectedRegions, true) ? 'checked' : ''; ?>>
                                <span><?= escapeHtml($region); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>

                    <fieldset class="category-filter-group">
                        <legend>Kategori</legend>
                        <?php foreach ($categories as $filterCategory): ?>
                            <?php $filterCategoryId = (int) $filterCategory['Id_kategori']; ?>
                            <label class="category-filter-option">
                                <input type="checkbox" name="kategori_filter[]" value="<?= $filterCategoryId; ?>"
                                    <?= in_array($filterCategoryId, $selectedCategories, true) ? 'checked' : ''; ?>>
                                <span><?= escapeHtml(
                                    $categoryDisplayNames[$filterCategoryId] ?? $filterCategory['nama_kategori']
                                ); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>

                    <fieldset class="category-filter-group category-budget-group">
                        <legend>Budget</legend>
                        <label for="categoryMinBudget">Minimum Rp</label>
                        <input
                            type="number"
                            id="categoryMinBudget"
                            name="min"
                            min="0"
                            value="<?= escapeHtml($minimumBudgetInput); ?>"
                            aria-invalid="<?= $hasBudgetError ? 'true' : 'false'; ?>"
                        >
                        <label for="categoryMaxBudget">Maximum Rp</label>
                        <input
                            type="number"
                            id="categoryMaxBudget"
                            name="max"
                            min="0"
                            value="<?= escapeHtml($maximumBudgetInput); ?>"
                            aria-invalid="<?= $hasBudgetError ? 'true' : 'false'; ?>"
                        >
                    </fieldset>
                </div>
                <p class="category-filter-validation" id="categoryBudgetValidation" role="alert"
                    <?= $hasBudgetError ? '' : 'hidden'; ?>>
                    Minimum budget harus berupa angka valid dan tidak boleh lebih besar dari maksimum.
                </p>
                <div class="category-filter-actions">
                    <button type="submit">Terapkan Filter</button>
                    <a href="kategori.php?kategori=<?= rawurlencode($categoryKey); ?><?= $keyword !== '' ? '&amp;keyword=' . rawurlencode($keyword) : ''; ?>">Reset Filter</a>
                </div>
            </form>

            <h2><?= escapeHtml($resultsTitle); ?></h2>
            <p class="category-result-count" id="categoryResultCount"><?= count($destinations); ?> Destinasi Ditemukan</p>
            <?php if ($destinations !== []): ?>
                <div class="destination-grid" id="categoryResults">
                    <?php foreach ($destinations as $destination): ?>
                        <a
                            class="destination-card"
                            href="detail-wisata.php?id=<?= rawurlencode((string) $destination['id_wisata']); ?>"
                            aria-label="Lihat detail <?= escapeHtml($destination['nama_wisata']); ?>"
                            data-category="<?= (int) $destination['id_kategori']; ?>"
                        >
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
        const categorySearchForm = document.getElementById('search');
        const categorySearchInput = document.getElementById('destination-search');
        const categorySearchValidation = document.getElementById('categorySearchValidation');
        const categoryFilterForm = document.getElementById('categoryFilterForm');
        const filterKeywordInput = document.getElementById('filterKeyword');
        const minimumBudgetInput = document.getElementById('categoryMinBudget');
        const maximumBudgetInput = document.getElementById('categoryMaxBudget');
        const budgetValidation = document.getElementById('categoryBudgetValidation');
        const resultCount = document.getElementById('categoryResultCount');
        const selectedDistanceRanges = <?= json_encode(
            $selectedDistances,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        ); ?>;

        function updateCategorySearchValidation() {
            const isShort = Array.from(categorySearchInput.value.trim()).length === 1;
            categorySearchValidation.hidden = !isShort;
            categorySearchInput.setAttribute('aria-invalid', String(isShort));
            categorySearchForm.classList.toggle('has-validation', isShort);
            return isShort;
        }

        categorySearchForm.addEventListener('submit', function (event) {
            if (updateCategorySearchValidation()) {
                event.preventDefault();
                categorySearchInput.focus();
            }
        });
        categorySearchInput.addEventListener('input', function () {
            filterKeywordInput.value = categorySearchInput.value.trim();
            updateCategorySearchValidation();
        });

        categoryFilterForm.addEventListener('submit', function (event) {
            const minimum = minimumBudgetInput.value;
            const maximum = maximumBudgetInput.value;
            if (minimum !== '' && maximum !== '' && Number(minimum) > Number(maximum)) {
                event.preventDefault();
                budgetValidation.hidden = false;
                minimumBudgetInput.setAttribute('aria-invalid', 'true');
                maximumBudgetInput.setAttribute('aria-invalid', 'true');
                minimumBudgetInput.focus();
                return;
            }

            budgetValidation.hidden = true;
            minimumBudgetInput.setAttribute('aria-invalid', 'false');
            maximumBudgetInput.setAttribute('aria-invalid', 'false');
        });

        const distanceElements = Array.from(
            document.querySelectorAll('.distance[data-latitude][data-longitude]')
        );
        const destinationGrid = document.getElementById('categoryResults');

        function applyDistanceFilters() {
            if (selectedDistanceRanges.length === 0 || !destinationGrid) {
                return;
            }

            const cards = Array.from(destinationGrid.querySelectorAll('.destination-card'));
            let visibleCount = 0;
            cards.forEach(card => {
                const distanceElement = card.querySelector('.distance');
                const distance = Number(distanceElement?.dataset.distanceKm);
                const matches = !Number.isFinite(distance) || selectedDistanceRanges.some(range => {
                    const [minimum, maximum] = range.split('-').map(Number);
                    return Number.isFinite(distance) && distance >= minimum && distance <= maximum;
                });
                card.hidden = !matches;
                if (matches) {
                    visibleCount++;
                }
            });

            resultCount.textContent = `${visibleCount} Destinasi Ditemukan`;
            let emptyState = document.querySelector('.category-empty-results');
            if (visibleCount === 0 && !emptyState) {
                emptyState = document.createElement('div');
                emptyState.className = 'category-empty-results';
                emptyState.setAttribute('role', 'status');
                const message = document.createElement('p');
                message.textContent = 'Maaf, tidak ada destinasi wisata yang sesuai dengan filter jarak.';
                emptyState.append(message);
                destinationGrid.after(emptyState);
            } else if (visibleCount > 0 && emptyState) {
                emptyState.remove();
            }
        }

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
                applyDistanceFilters();
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
                            destination.element.dataset.distanceKm = String(distance);
                        });
                        applyDistanceFilters();
                    },
                    () => {
                        destinationsWithCoordinates.forEach(({ element }) => {
                            element.textContent = 'Jarak tidak tersedia';
                        });
                        applyDistanceFilters();
                    },
                    { maximumAge: 300000, timeout: 10000 }
                );
            }
        } else {
            applyDistanceFilters();
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
