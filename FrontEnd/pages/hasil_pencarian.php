
<?php
session_start();

$keyword = isset($_GET['q']) && is_string($_GET['q'])
    ? trim($_GET['q'])
    : (isset($_GET['keyword']) && is_string($_GET['keyword'])
        ? trim($_GET['keyword'])
        : '');
$keywordLength = preg_match_all('/./us', $keyword);
$isShortKeyword = $keyword !== '' && $keywordLength !== false && $keywordLength < 2;
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestPath = is_string($requestPath) ? $requestPath : '/';
$appRoot = preg_replace(
    '~/(?:search|FrontEnd/pages/hasil_pencarian\.php)/?$~',
    '',
    $requestPath
);
$pageBasePath = rtrim($appRoot ?? '', '/') . '/FrontEnd/pages/';

$wilayahDipilih = is_array($_GET['wilayah'] ?? null) ? $_GET['wilayah'] : [];
$kategoriDipilih = is_array($_GET['kategori'] ?? null) ? $_GET['kategori'] : [];
$jarakDipilih = is_array($_GET['distance'] ?? null)
    ? array_values(array_filter($_GET['distance'], 'is_string'))
    : [];

$budgetMin = isset($_GET['min']) && $_GET['min'] !== ''
    ? max(0, (int) $_GET['min']) : 0;

$budgetMax = isset($_GET['max']) && $_GET['max'] !== ''
    ? max(0, (int) $_GET['max']) : 1000000;


// Destination cards are populated from the active database rows below.
$destinasi = [];

// Keep results empty if the database cannot be read; never show sample destinations.
$destinasi = [];
$filterCategories = [];
$regionOptions = [];
try {
    require_once __DIR__ . '/../../Backend/database/koneksi.php';
    require_once __DIR__ . '/../../Backend/database/wisata_repository.php';
    $allDatabaseTourism = fetchTourismRows($conn);
    $filterCategories = fetchActiveCategories($conn);

    foreach ($allDatabaseTourism as $row) {
        $region = stripos($row['wilayah'], 'Majalengka') !== false
            ? 'Majalengka'
            : 'Kuningan';
        $regionOptions[$region] = $region;

        $destinasi[] = [
            'id' => $row['id_wisata'],
            'nama' => $row['nama_wisata'],
            'wilayah' => $region,
            'lokasi' => $row['wilayah'],
            'kategori' => $row['kategori'],
            'id_kategori' => (string) $row['id_kategori'],
            'rating' => $row['rating'],
            'review_count' => $row['jumlah_ulasan'],
            'harga' => $row['harga_tiket'],
            'latitude' => $row['latitude'],
            'longitude' => $row['longitude'],
            'gambar' => $row['gambar']
        ];
    }
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    $destinasi = [];
}
$regionOptions = array_values($regionOptions);


// FILTER DATA DESTINASI
$hasil = array_filter($destinasi, function ($item) use (
    $keyword,
    $wilayahDipilih,
    $kategoriDipilih,
    $budgetMin,
    $budgetMax
) {
    // Pencarian nama, wilayah, dan kategori
    $cocokKeyword = true;

    if ($keyword !== '') {
        $teksCari = strtolower(
            $item['nama'] . ' ' .
            $item['wilayah'] . ' ' .
            $item['kategori']
        );

        $cocokKeyword = strpos(
            $teksCari,
            strtolower($keyword)
        ) !== false;
    }

    // Filter wilayah
    $cocokWilayah = empty($wilayahDipilih);
    foreach ($wilayahDipilih as $wilayah) {
        if (is_string($wilayah) && stripos($item['lokasi'], $wilayah) !== false) {
            $cocokWilayah = true;
        }
    }

    // Filter kategori
    $cocokKategori = empty($kategoriDipilih) ||
        in_array($item['id_kategori'], $kategoriDipilih, true);

    // Filter budget
    $cocokBudget = $item['harga'] >= $budgetMin &&
        $item['harga'] <= $budgetMax;

    return $cocokKeyword &&
        $cocokWilayah &&
        $cocokKategori &&
        $cocokBudget;
});

$jumlahHasil = count($hasil);


// Helper untuk mempertahankan pilihan filter di URL.
function terpilih($daftar, $nilai)
{
    return in_array($nilai, $daftar, true) ? 'checked' : '';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= htmlspecialchars($pageBasePath, ENT_QUOTES, 'UTF-8') ?>">

    <title>Hasil Pencarian | Explore Majaku</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Fragment+Mono:ital@0;1&family=Frank+Ruhl+Libre:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=Kaushan+Script&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../CSS/theme.css?v=shared-layout-4">
    <link rel="stylesheet" href="../CSS/hasil_pencarian.css?v=font-map-3">
</head>

<body>

<!-- NAVBAR -->
<header class="navbar">
    <div class="navbar-container">

        <a href="../../index.php" class="navbar-logo">
            Explore Majaku
        </a>

        <nav class="navbar-menu">
            <a href="../../index.php">Beranda</a>
            <a href="kategori.php?kategori=alam">kategori</a>
            <a href="tentang.php">Tentang</a>
        </nav>

        <div class="navbar-icons">
            <a href="hasil_pencarian.php" class="nav-icon" aria-label="Pencarian">
                <i class="bi bi-search"></i>
            </a>

            <a href="../auth/Login.php" class="nav-icon" aria-label="Akun">
                <svg class="profile-outline-icon" viewBox="0 0 40 40" aria-hidden="true">
                    <circle cx="20" cy="20" r="17"></circle>
                    <circle cx="20" cy="15" r="5"></circle>
                    <path d="M9 31c1.7-5 5.5-7.5 11-7.5S29.3 26 31 31"></path>
                </svg>
            </a>
        </div>

    </div>
</header>


<!-- SEARCH BAR -->
<section class="search-header<?= $isShortKeyword ? ' has-validation' : '' ?>">
    <form action="../../search" method="GET" class="search-box" novalidate>

        <input
            type="search"
            name="q"
            placeholder="Cari destinasi,kata kunci atau lokasi"
            value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
            aria-label="Cari destinasi"
            aria-describedby="searchValidation"
            aria-invalid="<?= $isShortKeyword ? 'true' : 'false' ?>"
        >

        <button type="submit" aria-label="Cari">
            <i class="bi bi-search"></i>
        </button>

    </form>

    <p
        class="search-validation"
        id="searchValidation"
        role="alert"
        <?= $isShortKeyword ? '' : 'hidden' ?>
    >
        <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
        Masukkan Minimal 2 Karakter
    </p>
</section>


<!-- CONTENT -->
<main class="search-layout">

    <!-- FILTER SIDEBAR -->
    <aside class="filter-sidebar">

        <form action="../../search" method="GET" id="filterForm">

            <input
                type="hidden"
                name="q"
                value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
            >

            <h2 class="filter-title">Filter</h2>

            <!-- DISTANCE -->
            <div class="filter-group distance-filter">
                <h3>Jarak</h3>
                <?php foreach ([
                    '0-5' => '5 Km',
                    '5-10' => '5 - 10 Km',
                    '10-20' => '10 - 20 Km',
                    '20-30' => '20 - 30 Km'
                ] as $distanceValue => $distanceLabel): ?>
                    <label class="filter-option">
                        <input type="checkbox" name="distance[]" value="<?= $distanceValue ?>"
                            <?= terpilih($jarakDipilih, $distanceValue) ?>>
                        <span><?= htmlspecialchars($distanceLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- WILAYAH -->
            <div class="filter-group">
                <h3>Wilayah</h3>
                <?php foreach ($regionOptions as $region): ?>
                    <label class="filter-option">
                        <input type="checkbox" name="wilayah[]" value="<?= htmlspecialchars($region, ENT_QUOTES, 'UTF-8') ?>"
                            <?= terpilih($wilayahDipilih, $region) ?>>
                        <span><?= htmlspecialchars($region, ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                <?php endforeach; ?>
            </div>


            <!-- KATEGORI -->
            <div class="filter-group">
                <h3>Kategori</h3>
                <?php foreach ($filterCategories as $filterCategory): ?>
                    <?php $categoryValue = (string) $filterCategory['Id_kategori']; ?>
                    <label class="filter-option">
                        <input type="checkbox" name="kategori[]" value="<?= $categoryValue ?>"
                            <?= terpilih($kategoriDipilih, $categoryValue) ?>>
                        <span><?= htmlspecialchars($filterCategory['nama_kategori'], ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                <?php endforeach; ?>
            </div>


            <!-- BUDGET -->
            <div class="filter-group budget-group">
                <h3>Budget</h3>

                <div class="budget-field">
                    <label for="minBudget">Minimum Rp</label>
                    <input
                        type="number"
                        id="minBudget"
                        name="min"
                        min="0"
                        placeholder="contoh : 10.000"
                        value="<?= $budgetMin > 0 ? $budgetMin : '' ?>"
                    >
                </div>

                <div class="budget-field">
                    <label for="maxBudget">Maximum Rp</label>
                    <input
                        type="number"
                        id="maxBudget"
                        name="max"
                        min="0"
                        placeholder="contoh : 10.000"
                        value="<?= isset($_GET['max']) ? $budgetMax : '' ?>"
                    >
                </div>
            </div>

            <button type="submit" class="apply-filter">
                Terapkan Filter
            </button>

            <a href="../../search" class="reset-filter">
                Reset Filter
            </a>

        </form>

    </aside>


    <!-- SEARCH RESULTS -->
    <section class="results-section">

        <div class="results-heading">
            <h1>Hasil Pencarian</h1>

            <p>
                <?= $jumlahHasil ?> Destinasi Ditemukan
            </p>
        </div>


        <div class="destination-grid">

            <?php if ($jumlahHasil > 0): ?>

                <?php foreach ($hasil as $item): ?>

                    <a
                        href="detail-wisata.php?id=<?= rawurlencode($item['id']) ?>"
                        class="destination-card"
                    >

                        <img
                            src="../../<?= htmlspecialchars($item['gambar'] ?? 'FrontEnd/assets/images/img.png', ENT_QUOTES, 'UTF-8') ?>"
                            alt="<?= htmlspecialchars($item['nama'], ENT_QUOTES, 'UTF-8') ?>"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='../assets/images/img.png';"
                        >

                        <div class="card-content">

                            <h2>
                                <?= htmlspecialchars($item['nama'], ENT_QUOTES, 'UTF-8') ?>
                            </h2>

                            <div class="rating">
                                <?php if ($item['rating'] !== null && $item['review_count'] > 0): ?>
                                    <span class="stars">★ <?= number_format($item['rating'], 1, ',', '.') ?>/5</span>
                                    <span class="review-count">(<?= (int) $item['review_count'] ?> ulasan)</span>
                                <?php else: ?>
                                    <span class="review-count">Belum ada ulasan</span>
                                <?php endif; ?>
                            </div>

                            <div class="location">
                                <svg class="map-pin-icon" viewBox="0 0 40 44" aria-hidden="true">
                                    <ellipse class="map-pin-ground" cx="20" cy="37" rx="14" ry="5"></ellipse>
                                    <path class="map-pin-ground-right" d="M20 32a14 5 0 0 1 0 10z"></path>
                                    <path class="map-pin-body" d="M20 3C13.4 3 9 7.9 9 13.8c0 7.2 8.6 14.6 11 19.2 2.4-4.6 11-12 11-19.2C31 7.9 26.6 3 20 3z"></path>
                                    <circle class="map-pin-hole" cx="20" cy="13.5" r="4.8"></circle>
                                </svg>

                                <span>
                                    <?= htmlspecialchars($item['lokasi'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>

                            <span
                                class="distance"
                                data-latitude="<?= htmlspecialchars((string) $item['latitude'], ENT_QUOTES, 'UTF-8') ?>"
                                data-longitude="<?= htmlspecialchars((string) $item['longitude'], ENT_QUOTES, 'UTF-8') ?>"
                            >
                                Menghitung jarak...
                            </span>

                        </div>

                    </a>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="empty-results">
                    <span class="empty-search-icon" aria-hidden="true">
                        <i class="bi bi-search"></i>
                        <span>×</span>
                    </span>

                    <h2>Wisata Tidak Di Temukan</h2>

                    <p>
                        Maaf, tidak ada destinasi wisata yang sesuai<br>
                        dengan kata kunci pencarianmu
                    </p>

                    <a href="../../search">Cari yang lain</a>
                </div>

            <?php endif; ?>

        </div>

    </section>

</main>

<script>
    const filterForm = document.getElementById('filterForm');
    const searchForm = document.querySelector('.search-box');
    const searchInput = searchForm.querySelector('input[name="q"]');
    const resultsGrid = document.querySelector('.destination-grid');
    const resultsCount = document.querySelector('.results-heading p');
    const searchHeader = document.querySelector('.search-header');
    const searchValidation = document.getElementById('searchValidation');
    const selectedDistanceRanges = <?= json_encode($jarakDipilih, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function updateSearchValidation() {
        const isInvalid = Array.from(searchInput.value.trim()).length === 1;
        searchValidation.hidden = !isInvalid;
        searchInput.setAttribute('aria-invalid', String(isInvalid));
        searchHeader.classList.toggle('has-validation', isInvalid);
        return isInvalid;
    }

    searchForm.addEventListener('submit', function (event) {
        if (updateSearchValidation()) {
            event.preventDefault();
        }
    });
    searchInput.addEventListener('input', function () {
        updateSearchValidation();
    });

    filterForm.addEventListener('submit', function (event) {
        const min = document.getElementById('minBudget').value;
        const max = document.getElementById('maxBudget').value;

        if (min !== '' && max !== '' && Number(min) > Number(max)) {
            event.preventDefault();
            alert('Budget minimum tidak boleh lebih besar dari budget maksimum.');
        }
    });

    function updateDistances() {
        const distanceElements = Array.from(
            resultsGrid.querySelectorAll('.distance[data-latitude][data-longitude]')
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

        if (destinationsWithCoordinates.length === 0) {
            applyDistanceFilters();
            return;
        }
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
    }

    function showEmptyState(message, title = 'Wisata Tidak Di Temukan') {
        const emptyState = document.createElement('div');
        emptyState.className = 'empty-results';

        const iconWrapper = document.createElement('span');
        iconWrapper.className = 'empty-search-icon';
        iconWrapper.setAttribute('aria-hidden', 'true');

        const icon = document.createElement('i');
        icon.className = 'bi bi-search';
        const cross = document.createElement('span');
        cross.textContent = '×';
        iconWrapper.append(icon, cross);

        const heading = document.createElement('h2');
        heading.textContent = title;

        const description = document.createElement('p');
        description.textContent = message;

        const resetLink = document.createElement('a');
        resetLink.href = '../../search';
        resetLink.textContent = 'Cari yang lain';

        emptyState.append(iconWrapper, heading, description, resetLink);
        resultsGrid.replaceChildren(emptyState);
    }

    function createDestinationCard(destination) {
        const card = document.createElement('a');
        card.href = `detail-wisata.php?id=${encodeURIComponent(destination.id_wisata)}`;
        card.className = 'destination-card';

        const image = document.createElement('img');
        image.src = destination.gambar
            ? `../../${destination.gambar}`
            : '../../FrontEnd/assets/images/img.png';
        image.alt = destination.nama_wisata;
        image.loading = 'lazy';
        image.addEventListener('error', function () {
            image.onerror = null;
            image.src = '../assets/images/img.png';
        });
        card.append(image);

        const content = document.createElement('div');
        content.className = 'card-content';

        const name = document.createElement('h2');
        name.textContent = destination.nama_wisata;
        content.append(name);

        const rating = document.createElement('div');
        rating.className = 'rating';
        if (destination.rating !== null && Number(destination.jumlah_ulasan) > 0) {
            const stars = document.createElement('span');
            stars.className = 'stars';
            stars.textContent = `★ ${Number(destination.rating).toFixed(1).replace('.', ',')}/5`;

            const reviewCount = document.createElement('span');
            reviewCount.className = 'review-count';
            reviewCount.textContent = `(${Number(destination.jumlah_ulasan)} ulasan)`;
            rating.append(stars, reviewCount);
        } else {
            const noReviews = document.createElement('span');
            noReviews.className = 'review-count';
            noReviews.textContent = 'Belum ada ulasan';
            rating.append(noReviews);
        }
        content.append(rating);

        const location = document.createElement('div');
        location.className = 'location';
        const mapPin = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        mapPin.setAttribute('class', 'map-pin-icon');
        mapPin.setAttribute('viewBox', '0 0 40 44');
        mapPin.setAttribute('aria-hidden', 'true');

        const ground = document.createElementNS('http://www.w3.org/2000/svg', 'ellipse');
        ground.setAttribute('class', 'map-pin-ground');
        ground.setAttribute('cx', '20');
        ground.setAttribute('cy', '37');
        ground.setAttribute('rx', '14');
        ground.setAttribute('ry', '5');

        const groundShade = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        groundShade.setAttribute('class', 'map-pin-ground-right');
        groundShade.setAttribute('d', 'M20 32a14 5 0 0 1 0 10z');

        const pinBody = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        pinBody.setAttribute('class', 'map-pin-body');
        pinBody.setAttribute('d', 'M20 3C13.4 3 9 7.9 9 13.8c0 7.2 8.6 14.6 11 19.2 2.4-4.6 11-12 11-19.2C31 7.9 26.6 3 20 3z');

        const pinHole = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        pinHole.setAttribute('class', 'map-pin-hole');
        pinHole.setAttribute('cx', '20');
        pinHole.setAttribute('cy', '13.5');
        pinHole.setAttribute('r', '4.8');

        mapPin.append(ground, groundShade, pinBody, pinHole);
        location.append(mapPin);
        const locationText = document.createElement('span');
        locationText.textContent = destination.wilayah || '';
        location.append(locationText);
        content.append(location);

        const distance = document.createElement('span');
        distance.className = 'distance';
        distance.dataset.latitude = destination.latitude ?? '';
        distance.dataset.longitude = destination.longitude ?? '';
        distance.textContent = 'Menghitung jarak...';
        content.append(distance);

        card.append(content);
        return card;
    }

    function filterDestinations(destinations) {
        const selectedRegions = Array.from(
            filterForm.querySelectorAll('input[name="wilayah[]"]:checked')
        ).map(input => input.value);
        const selectedCategories = Array.from(
            filterForm.querySelectorAll('input[name="kategori[]"]:checked')
        ).map(input => input.value);
        const minimum = Number(document.getElementById('minBudget').value || 0);
        const maximum = Number(document.getElementById('maxBudget').value || 1000000);

        return destinations.filter(destination => {
            const mappedRegion = (destination.wilayah || '').toLowerCase().includes('majalengka')
                ? 'Majalengka'
                : 'Kuningan';
            const matchesRegion = selectedRegions.length === 0 ||
                selectedRegions.includes(mappedRegion);
            const matchesCategory = selectedCategories.length === 0 ||
                selectedCategories.includes(String(destination.id_kategori));
            const price = Number(destination.harga_tiket);
            const matchesBudget = price >= minimum && price <= maximum;

            return matchesRegion && matchesCategory && matchesBudget;
        });
    }

    function applyDistanceFilters() {
        if (selectedDistanceRanges.length === 0) {
            return;
        }

        const cards = Array.from(resultsGrid.querySelectorAll('.destination-card'));
        let visibleCount = 0;
        cards.forEach(card => {
            const distance = Number(card.querySelector('.distance')?.dataset.distanceKm);
            const matches = !Number.isFinite(distance) || selectedDistanceRanges.some(range => {
                const [minimum, maximum] = range.split('-').map(Number);
                return Number.isFinite(distance) && distance >= minimum && distance <= maximum;
            });
            card.hidden = !matches;
            if (matches) {
                visibleCount++;
            }
        });

        resultsCount.textContent = `${visibleCount} Destinasi Ditemukan`;
        if (visibleCount === 0) {
            showEmptyState('Maaf, tidak ada destinasi wisata yang sesuai dengan kata kunci pencarianmu');
        }
    }

    async function loadSearchResults() {
        const query = searchInput.value.trim();
        if (query === '') {
            updateDistances();
            return;
        }

        if (Array.from(query).length < 2) {
            return;
        }

        try {
            const response = await fetch(
                `../../api/destinations/search?q=${encodeURIComponent(query)}`,
                { headers: { Accept: 'application/json' } }
            );
            const payload = await response.json();
            if (!response.ok || payload.status !== 'success' || !Array.isArray(payload.data)) {
                throw new Error(payload.message || 'Respons pencarian tidak valid.');
            }

            const filteredResults = filterDestinations(payload.data);
            resultsCount.textContent = `${filteredResults.length} Destinasi Ditemukan`;
            if (filteredResults.length === 0) {
                showEmptyState('Coba ubah kata kunci atau filter pencarianmu.');
                return;
            }

            resultsGrid.replaceChildren(
                ...filteredResults.map(createDestinationCard)
            );
            updateDistances();
        } catch (error) {
            console.error('Gagal memuat hasil pencarian:', error);
            resultsCount.textContent = 'Hasil pencarian gagal dimuat.';
            showEmptyState('Terjadi masalah saat mengambil data. Silakan muat ulang halaman.', 'Pencarian gagal');
        }
    }

    updateSearchValidation();
    loadSearchResults();
</script>

</body>
</html>