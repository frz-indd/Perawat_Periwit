
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
        href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../CSS/hasil_pencarian.css">
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
                <i class="bi bi-person-circle"></i>
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
                                <i class="bi bi-geo-alt-fill"></i>

                                <span>
                                    <?= htmlspecialchars($item['lokasi'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>

                            <span class="distance">
                                <?= $item['harga'] > 0
                                    ? 'Rp ' . number_format($item['harga'], 0, ',', '.')
                                    : 'Harga belum tersedia' ?>
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
</script>

</body>
</html>