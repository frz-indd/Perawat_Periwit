
<?php
session_start();

// Jika halaman hasil pencarian wajib login, aktifkan kode berikut.
// if (!isset($_SESSION['id_user'])) {
//     header('Location: auth/Login.php');
//     exit;
// }

$keyword = trim($_GET['keyword'] ?? '');

$jarakDipilih = $_GET['jarak'] ?? [];
$wilayahDipilih = $_GET['wilayah'] ?? [];
$kategoriDipilih = $_GET['kategori'] ?? [];

$budgetMin = isset($_GET['min']) && $_GET['min'] !== ''
    ? max(0, (int) $_GET['min']) : 0;

$budgetMax = isset($_GET['max']) && $_GET['max'] !== ''
    ? max(0, (int) $_GET['max']) : 1000000;


// Destinasi yang ditampilkan di website tetap dapat dicari meski belum ada di database.
$destinasi = [
    [
        'id' => 1,
        'nama' => 'Wonderland',
        'wilayah' => 'Kuningan',
        'kategori' => 'Wisata Buatan/Kuliner',
        'jarak' => 5.79,
        'rating' => 1.8,
        'harga' => 15000,
        'gambar' => 'assets/images/wisata/wonderland.jpg'
    ],
    [
        'id' => 2,
        'nama' => 'Cilengkrang',
        'wilayah' => 'Kuningan',
        'kategori' => 'Wisata Alam & Pegunungan',
        'jarak' => 7.09,
        'rating' => 2.7,
        'harga' => 15000,
        'gambar' => 'assets/images/wisata/cilengkrang.jpg'
    ],
    [
        'id' => 3,
        'nama' => 'Arunika Garden',
        'wilayah' => 'Kuningan',
        'kategori' => 'Wisata Buatan/Kuliner',
        'jarak' => 5.29,
        'rating' => 2.5,
        'harga' => 25000,
        'gambar' => 'assets/images/wisata/arunika-garden.jpg'
    ],
    [
        'id' => 4,
        'nama' => 'Cibuntu',
        'wilayah' => 'Kuningan',
        'kategori' => 'Wisata Alam & Pegunungan',
        'jarak' => 8.20,
        'rating' => 1.7,
        'harga' => 10000,
        'gambar' => 'assets/images/wisata/cibuntu.jpg'
    ],
    [
        'id' => 5,
        'nama' => 'Curug Putri Palutungan',
        'wilayah' => 'Kuningan',
        'kategori' => 'Wisata Air & Curug',
        'jarak' => 12.68,
        'rating' => 1.5,
        'harga' => 20000,
        'gambar' => 'assets/images/wisata/curug-putri.jpg'
    ],
    [
        'id' => 6,
        'nama' => 'Bukit Pamoroan',
        'wilayah' => 'Majalengka',
        'kategori' => 'Wisata Alam & Pegunungan',
        'jarak' => 5.76,
        'rating' => 2.6,
        'harga' => 10000,
        'gambar' => 'assets/images/wisata/bukit-pamor.jpg'
    ],
    [
        'id' => 7,
        'nama' => 'Telaga Biru',
        'wilayah' => 'Majalengka',
        'kategori' => 'Wisata Air & Curug',
        'jarak' => 15.00,
        'rating' => 1.9,
        'harga' => 10000,
        'gambar' => 'assets/images/wisata/telaga-biru.jpg'
    ],
    [
        'id' => 8,
        'nama' => 'Gunung Ciwaru',
        'wilayah' => 'Majalengka',
        'kategori' => 'Wisata Alam & Pegunungan',
        'jarak' => 20.00,
        'rating' => 2.1,
        'harga' => 15000,
        'gambar' => 'assets/images/wisata/gunung-ciwaru.jpg'
    ],
    [
        'id' => 9,
        'nama' => 'Bumi Pakuwon',
        'wilayah' => 'Majalengka',
        'kategori' => 'Wisata Buatan/Kuliner',
        'jarak' => 10.00,
        'rating' => 1.6,
        'harga' => 20000,
        'gambar' => 'assets/images/wisata/bumi-pakuwon.jpg'
    ],
];

// Tambahkan destinasi database dan prioritaskan datanya jika nama juga ada di website.
try {
    require_once __DIR__ . '/../../Backend/database/koneksi.php';

    $sql = "SELECT
                w.id_wisata AS id,
                w.nama_wisata AS nama,
                w.domisili AS wilayah,
                w.harga_tiket AS harga,
                k.nama_kategori AS kategori
            FROM wisata AS w
            LEFT JOIN kategori AS k ON k.Id_kategori = w.id_kategori";
    $parameters = [];
    $parameterTypes = '';

    if ($keyword !== '') {
        $sql .= " WHERE w.nama_wisata LIKE ?
                    OR w.domisili LIKE ?
                    OR w.alamat LIKE ?
                    OR w.deskripsi LIKE ?
                    OR k.nama_kategori LIKE ?";
        $searchTerm = '%' . $keyword . '%';
        $parameters = array_fill(0, 5, $searchTerm);
        $parameterTypes = 'sssss';
    }

    $sql .= ' ORDER BY w.nama_wisata';
    $statement = $conn->prepare($sql);

    if ($parameters !== []) {
        $statement->bind_param($parameterTypes, ...$parameters);
    }

    $statement->execute();
    $databaseRows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    $databaseDestinasi = [];
    $databaseNames = [];

    foreach ($databaseRows as $row) {
        $name = trim((string) ($row['nama'] ?? ''));
        if ($name === '') {
            continue;
        }

        $category = trim((string) ($row['kategori'] ?? ''));
        $categoryKey = strtolower($category);
        if (str_contains($categoryKey, 'alam')) {
            $category = 'Wisata Alam & Pegunungan';
        } elseif (str_contains($categoryKey, 'budaya') || str_contains($categoryKey, 'sejarah')) {
            $category = 'Wisata Sejarah & Budaya';
        } elseif (str_contains($categoryKey, 'air') || str_contains($categoryKey, 'curug')) {
            $category = 'Wisata Air & Curug';
        } elseif (str_contains($categoryKey, 'kuliner') || str_contains($categoryKey, 'buatan')) {
            $category = 'Wisata Buatan/Kuliner';
        }

        $row['nama'] = $name;
        $row['wilayah'] = trim((string) $row['wilayah']);
        $row['kategori'] = $category;
        $row['jarak'] = null;
        $row['rating'] = 0;
        $row['harga'] = (float) $row['harga'];
        $row['gambar'] = '../assets/images/img.png';

        $databaseDestinasi[] = $row;
        $databaseNames[strtolower($name)] = true;
    }

    $destinasi = array_merge(
        $databaseDestinasi,
        array_filter($destinasi, static function ($item) use ($databaseNames) {
            return !isset($databaseNames[strtolower($item['nama'])]);
        })
    );
} catch (Throwable $exception) {
    error_log($exception->getMessage());
}


// FILTER DATA DESTINASI
$hasil = array_filter($destinasi, function ($item) use (
    $keyword,
    $jarakDipilih,
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

    // Filter jarak
    $cocokJarak = empty($jarakDipilih);

    foreach ($jarakDipilih as $jarak) {
        if (
            (is_numeric($item['jarak']) && $jarak === '5' && $item['jarak'] <= 5) ||
            (is_numeric($item['jarak']) && $jarak === '5-10' && $item['jarak'] > 5 && $item['jarak'] <= 10) ||
            (is_numeric($item['jarak']) && $jarak === '10-20' && $item['jarak'] > 10 && $item['jarak'] <= 20) ||
            (is_numeric($item['jarak']) && $jarak === '20-30' && $item['jarak'] > 20 && $item['jarak'] <= 30)
        ) {
            $cocokJarak = true;
        }
    }

    // Filter wilayah
    $cocokWilayah = empty($wilayahDipilih) ||
        in_array($item['wilayah'], $wilayahDipilih, true);

    // Filter kategori
    $cocokKategori = empty($kategoriDipilih) ||
        in_array($item['kategori'], $kategoriDipilih, true);

    // Filter budget
    $cocokBudget = $item['harga'] >= $budgetMin &&
        $item['harga'] <= $budgetMax;

    return $cocokKeyword &&
        $cocokJarak &&
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

        <a href="index.php" class="navbar-logo">
            Explore Majaku
        </a>

        <nav class="navbar-menu">
            <a href="dashboard.php">Beranda</a>
            <a href="kategori/kategori-alam.php">kategori</a>
            <a href="pages/tentang.php">Tentang</a>
        </nav>

        <div class="navbar-icons">
            <a href="hasil_pencarian.php" class="nav-icon" aria-label="Pencarian">
                <i class="bi bi-search"></i>
            </a>

            <a href="auth/Login.php" class="nav-icon" aria-label="Akun">
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

            <!-- JARAK -->
            <div class="filter-group">
                <h3>Jarak</h3>

                <label class="filter-option">
                    <input type="checkbox" name="jarak[]" value="5"
                        <?= terpilih($jarakDipilih, '5') ?>>
                    <span>5 Km</span>
                </label>

                <label class="filter-option">
                    <input type="checkbox" name="jarak[]" value="5-10"
                        <?= terpilih($jarakDipilih, '5-10') ?>>
                    <span>5 - 10 Km</span>
                </label>

                <label class="filter-option">
                    <input type="checkbox" name="jarak[]" value="10-20"
                        <?= terpilih($jarakDipilih, '10-20') ?>>
                    <span>10 - 20 Km</span>
                </label>

                <label class="filter-option">
                    <input type="checkbox" name="jarak[]" value="20-30"
                        <?= terpilih($jarakDipilih, '20-30') ?>>
                    <span>20 - 30 Km</span>
                </label>
            </div>


            <!-- WILAYAH -->
            <div class="filter-group">
                <h3>Wilayah</h3>

                <label class="filter-option">
                    <input type="checkbox" name="wilayah[]" value="Kuningan"
                        <?= terpilih($wilayahDipilih, 'Kuningan') ?>>
                    <span>Kuningan</span>
                </label>

                <label class="filter-option">
                    <input type="checkbox" name="wilayah[]" value="Majalengka"
                        <?= terpilih($wilayahDipilih, 'Majalengka') ?>>
                    <span>Majalengka</span>
                </label>
            </div>


            <!-- KATEGORI -->
            <div class="filter-group">
                <h3>Kategori</h3>

                <label class="filter-option">
                    <input type="checkbox" name="kategori[]"
                        value="Wisata Alam & Pegunungan"
                        <?= terpilih($kategoriDipilih, 'Wisata Alam & Pegunungan') ?>>
                    <span>Wisata Alam &amp; Pegunungan</span>
                </label>

                <label class="filter-option">
                    <input type="checkbox" name="kategori[]"
                        value="Wisata Air & Curug"
                        <?= terpilih($kategoriDipilih, 'Wisata Air & Curug') ?>>
                    <span>Wisata Air &amp; Curug</span>
                </label>

                <label class="filter-option">
                    <input type="checkbox" name="kategori[]"
                        value="Wisata Buatan/Kuliner"
                        <?= terpilih($kategoriDipilih, 'Wisata Buatan/Kuliner') ?>>
                    <span>Wisata Buatan/Kuliner</span>
                </label>

                <label class="filter-option">
                    <input type="checkbox" name="kategori[]"
                        value="Wisata Sejarah & Budaya"
                        <?= terpilih($kategoriDipilih, 'Wisata Sejarah & Budaya') ?>>
                    <span>Wisata Sejarah &amp; Budaya</span>
                </label>
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
                        href="pages/detail-wisata.php?id=<?= (int) $item['id'] ?>"
                        class="destination-card"
                    >

                        <img
                            src="<?= htmlspecialchars($item['gambar'], ENT_QUOTES, 'UTF-8') ?>"
                            alt="<?= htmlspecialchars($item['nama'], ENT_QUOTES, 'UTF-8') ?>"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='../assets/images/img.png';"
                        >

                        <div class="card-content">

                            <h2>
                                <?= htmlspecialchars($item['nama'], ENT_QUOTES, 'UTF-8') ?>
                            </h2>

                            <div class="rating">
                                <span class="stars">★ ★ ★</span>

                                <span class="review-count">
                                    (<?= number_format($item['rating'], 1, ',', '.') ?>k ulasan)
                                </span>
                            </div>

                            <div class="location">
                                <i class="bi bi-geo-alt-fill"></i>

                                <span>
                                    Kabupaten <?= htmlspecialchars($item['wilayah'], ENT_QUOTES, 'UTF-8') ?>,
                                    <br>Jawa Barat.
                                </span>
                            </div>

                            <span class="distance">
                                <?= number_format($item['jarak'], 2, '.', '') ?> KM
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