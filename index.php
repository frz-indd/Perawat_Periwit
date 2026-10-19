<?php
session_start();

$isLoggedIn = isset($_SESSION["id_user"]);

require_once __DIR__ . "/Backend/database/koneksi.php";
require_once __DIR__ . "/Backend/database/wisata_repository.php";

$wisata = fetchTourismRows($conn);
$destinasiUtama = array_slice($wisata, 0, 4);
$destinasiLain = array_slice($wisata, 4, 4);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Beranda | Explore Majaku</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- CSS Beranda -->
    <link rel="stylesheet" href="FrontEnd/CSS/style.css?v=20261016">
    <link rel="stylesheet" href="FrontEnd/CSS/site.css?v=20261019">
</head>

<body>

    <!-- HEADER -->
    <header class="navbar">
        <div class="navbar-container">

            <a href="index.php" class="navbar-logo">
                Explore Majaku
            </a>

            <nav class="navbar-menu">
                <a href="index.php" class="active">Beranda</a>
                <details class="category-dropdown">
                    <summary>Kategori <i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
                    <div class="category-menu">
                        <a href="FrontEnd/pages/kategori.php?kategori=alam">Alam</a>
                        <a href="FrontEnd/pages/kategori.php?kategori=air-curug">Air &amp; Curug</a>
                        <a href="FrontEnd/pages/kategori.php?kategori=buatan-kuliner">Buatan &amp; Kuliner</a>
                        <a href="FrontEnd/pages/kategori.php?kategori=sejarah-budaya">Sejarah &amp; Budaya</a>
                    </div>
                </details>
                <a href="FrontEnd/pages/tentang.php">Tentang</a>
            </nav>

            <div class="navbar-icons">

                <a href="<?= $isLoggedIn ? 'FrontEnd/pages/profil.php' : 'FrontEnd/auth/Login.php' ?>"
                   class="profile-icon"
                   aria-label="<?= $isLoggedIn ? 'Profil' : 'Login' ?>">
                    <i class="bi bi-person-circle"></i>
                </a>

                <?php if (!$isLoggedIn): ?>
                    <a href="FrontEnd/auth/Login.php" class="login-button">
                        Login
                    </a>
                <?php endif; ?>

            </div>

        </div>
    </header>


    <!-- HERO -->
    <section class="hero">

        <div class="hero-overlay"></div>

        <div class="hero-content">

            <h1>Mau Liburan Ke mana?</h1>

            <p>
                Temukan destinasi wisata terbaik di majalengka &amp; Kuningan
            </p>

            <form
                class="search-box"
                action="FrontEnd/pages/hasil_pencarian.php"
                method="GET"
                id="searchForm"
            >

                <input
                    type="text"
                    name="keyword"
                    id="searchInput"
                    placeholder="Cari destinasi,kata kunci atau lokasi"
                    aria-label="Cari destinasi wisata"
                    required
                >

                <button type="submit" aria-label="Cari wisata">
                    <i class="bi bi-search"></i>
                </button>

            </form>

        </div>
    </section>


    <!-- KONTEN UTAMA -->
    <main class="main-content">

        <!-- DESTINASI UTAMA -->
        <section class="destination-section">

            <h2>Destinasi Wisata</h2>

            <div class="destination-grid">
                <?php foreach ($destinasiUtama as $destination): ?>
                    <a href="FrontEnd/pages/detail-wisata.php?id=<?= rawurlencode($destination['id_wisata']); ?>" class="destination-card">
                        <img src="<?= htmlspecialchars($destination['gambar'] ?? 'FrontEnd/assets/images/img.png', ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($destination['nama_wisata'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
                        <div class="card-content">
                            <h3><?= htmlspecialchars($destination['nama_wisata'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <div class="rating">
                                <?php if ($destination['rating'] !== null): ?>
                                    <span class="stars">★ <?= number_format($destination['rating'], 1, ',', '.'); ?>/5</span>
                                    <span class="review">(<?= (int) $destination['jumlah_ulasan']; ?> ulasan)</span>
                                <?php else: ?>
                                    <span class="review">Belum ada ulasan</span>
                                <?php endif; ?>
                            </div>
                            <div class="location">
                                <i class="bi bi-geo-alt-fill"></i>
                                <span><?= htmlspecialchars($destination['wilayah'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <span class="distance">
                                <?= $destination['harga_tiket'] > 0
                                    ? 'Rp ' . number_format($destination['harga_tiket'], 0, ',', '.')
                                    : 'Harga belum tersedia'; ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>


        <!-- DESTINASI LAINNYA -->
        <section class="destination-section nearby-section">

            <h2>Destinasi Lainnya</h2>

            <div class="destination-grid">
                <?php foreach ($destinasiLain as $destination): ?>
                    <a href="FrontEnd/pages/detail-wisata.php?id=<?= rawurlencode($destination['id_wisata']); ?>" class="destination-card">
                        <img src="<?= htmlspecialchars($destination['gambar'] ?? 'FrontEnd/assets/images/img.png', ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($destination['nama_wisata'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
                        <div class="card-content">
                            <h3><?= htmlspecialchars($destination['nama_wisata'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <div class="rating">
                                <?php if ($destination['rating'] !== null): ?>
                                    <span class="stars">★ <?= number_format($destination['rating'], 1, ',', '.'); ?>/5</span>
                                    <span class="review">(<?= (int) $destination['jumlah_ulasan']; ?> ulasan)</span>
                                <?php else: ?>
                                    <span class="review">Belum ada ulasan</span>
                                <?php endif; ?>
                            </div>
                            <div class="location">
                                <i class="bi bi-geo-alt-fill"></i>
                                <span><?= htmlspecialchars($destination['wilayah'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <span class="distance">
                                <?= $destination['harga_tiket'] > 0
                                    ? 'Rp ' . number_format($destination['harga_tiket'], 0, ',', '.')
                                    : 'Harga belum tersedia'; ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

    </main>

    <!-- VALIDASI PENCARIAN -->
    <script>
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');

        searchForm.addEventListener('submit', function (event) {
            if (searchInput.value.trim() === '') {
                event.preventDefault();
                alert('Silakan masukkan destinasi yang ingin dicari.');
                searchInput.focus();
            }
        });
    </script>

</body>
</html>