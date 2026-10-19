
<?php
session_start();

$keyword = isset($_GET['keyword']) && is_string($_GET['keyword'])
    ? trim($_GET['keyword'])
    : '';

$wilayahDipilih = is_array($_GET['wilayah'] ?? null) ? $_GET['wilayah'] : [];
$kategoriDipilih = is_array($_GET['kategori'] ?? null) ? $_GET['kategori'] : [];

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
    $databaseTourism = fetchTourismRows($conn, ['keyword' => $keyword]);
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

    <link rel="stylesheet" href="../CSS/hasil_pencarian.css?v=font-map-2">
    <link rel="stylesheet" href="../CSS/theme.css?v=shared-layout-3">
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
<section class="search-header">
    <form action="hasil_pencarian.php" method="GET" class="search-box">

        <input
            type="text"
            name="keyword"
            placeholder="Cari destinasi,kata kunci atau lokasi"
            value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
            aria-label="Cari destinasi"
        >

        <button type="submit" aria-label="Cari">
            <i class="bi bi-search"></i>
        </button>

    </form>
</section>


<!-- CONTENT -->
<main class="search-layout">

    <!-- FILTER SIDEBAR -->
    <aside class="filter-sidebar">

        <form action="hasil_pencarian.php" method="GET" id="filterForm">

            <input
                type="hidden"
                name="keyword"
                value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
            >

            <h2 class="filter-title">Filter</h2>

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

                <div class="budget-labels">
                    <label for="minBudget">Min</label>
                    <label for="maxBudget">Max</label>
                </div>

                <div class="budget-inputs">
                    <input
                        type="number"
                        id="minBudget"
                        name="min"
                        min="0"
                        placeholder="0"
                        value="<?= $budgetMin > 0 ? $budgetMin : '' ?>"
                    >

                    <span>-</span>

                    <input
                        type="number"
                        id="maxBudget"
                        name="max"
                        min="0"
                        placeholder="1000000"
                        value="<?= isset($_GET['max']) ? $budgetMax : '' ?>"
                    >
                </div>

                <p class="budget-hint">Harga tiket masuk (Rp)</p>
            </div>

            <button type="submit" class="apply-filter">
                Terapkan Filter
            </button>

            <a href="hasil_pencarian.php" class="reset-filter">
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
                    <i class="bi bi-search"></i>

                    <h2>Destinasi tidak ditemukan</h2>

                    <p>
                        Coba ubah kata kunci atau filter pencarianmu.
                    </p>

                    <a href="hasil_pencarian.php">
                        Tampilkan Semua Destinasi
                    </a>
                </div>

            <?php endif; ?>

        </div>

    </section>

</main>

<script>
    // Filter diterapkan saat tombol Terapkan Filter diklik.
    // Enter pada kolom pencarian juga menjalankan pencarian.
    document.getElementById('filterForm').addEventListener('submit', function (event) {
        const min = document.getElementById('minBudget').value;
        const max = document.getElementById('maxBudget').value;

        if (min !== '' && max !== '' && Number(min) > Number(max)) {
            event.preventDefault();
            alert('Budget minimum tidak boleh lebih besar dari budget maksimum.');
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