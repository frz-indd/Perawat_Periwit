<?php
session_start();

$isLoggedIn = isset($_SESSION["id_user"]) &&
    (int) $_SESSION["id_user"] > 0 &&
    ($_SESSION["role"] ?? "user") === "user";

require_once __DIR__ . "/Backend/database/koneksi.php";
require_once __DIR__ . "/Backend/database/wisata_repository.php";

$wisata = $conn !== null ? fetchTourismRows($conn) : [];
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

    <link href="https://fonts.googleapis.com/css2?family=Fragment+Mono:ital@0;1&family=Frank+Ruhl+Libre:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=Kaushan+Script&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- CSS Beranda -->
    <link rel="stylesheet" href="FrontEnd/CSS/style.css?v=home-reference-4">
    <link rel="stylesheet" href="FrontEnd/CSS/theme.css?v=home-reference-4">
    <link rel="stylesheet" href="FrontEnd/CSS/navbar-common.css?v=4">
</head>

<body class="home-page site-navbar-page">

    <header class="ep-navbar site-navbar">
        <div class="ep-navbar__inner">
            <a class="ep-logo" href="index.php">Explore Majaku</a>
            <nav class="ep-menu" aria-label="Menu utama">
                <a class="ep-menu__beranda" href="index.php" aria-current="page">Beranda</a>
                <details class="category-dropdown ep-menu__kategori">
                    <summary>
                        <span>Kategori</span>
                        <span class="ep-menu__panah" aria-hidden="true"></span>
                    </summary>
                    <div class="category-menu">
                        <a href="FrontEnd/pages/kategori.php?kategori=alam">Wisata Alam &amp; Pegunungan</a>
                        <a href="FrontEnd/pages/kategori.php?kategori=air-curug">Wisata Air &amp; Curug</a>
                        <a href="FrontEnd/pages/kategori.php?kategori=buatan-kuliner">Wisata Buatan &amp; Kuliner</a>
                        <a href="FrontEnd/pages/kategori.php?kategori=sejarah-budaya">Wisata Sejarah &amp; Budaya</a>
                    </div>
                </details>
                <a class="ep-menu__tentang" href="FrontEnd/pages/tentang.php">Tentang</a>
            </nav>
            <div class="ep-aksi">
                <a class="ep-ikon ep-ikon--profil" href="FrontEnd/pages/profil.php" aria-label="Profil">
                    <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="20" cy="20" r="18.2"/>
                        <circle cx="20" cy="16.2" r="5.8"/>
                        <path d="M9 33.5C9.7 29.2 12.2 26 15.2 25.2C17 26.6 23 26.6 24.8 25.2C27.8 26 30.3 29.2 31 33.5"/>
                    </svg>
                </a>
                <?php if (!$isLoggedIn): ?>
                    <a class="login-button" href="FrontEnd/auth/Login.php">Login</a>
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

            <div class="hero-search-actions">
                <form
                    class="search-box"
                    action="<?= $isLoggedIn ? 'search' : 'FrontEnd/auth/Login.php' ?>"
                    method="GET"
                    id="searchForm"
                >
                    <input
                        type="search"
                        name="q"
                        id="searchInput"
                        placeholder="Cari destinasi,kata kunci atau lokasi"
                        aria-label="Cari destinasi wisata"
                        minlength="2"
                        required
                    >

                    <?php if (!$isLoggedIn): ?>
                        <input type="hidden" name="next" value="search">
                    <?php endif; ?>

                    <button type="submit" aria-label="Cari wisata">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </button>
                </form>

                <a
                    class="search-filter-button"
                    href="<?= $isLoggedIn ? 'search' : 'FrontEnd/auth/Login.php?next=search' ?>"
                    aria-label="Buka filter pencarian wisata"
                >
                    <svg width="70" height="66" viewBox="0 0 70 66" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <rect width="70" height="66" rx="20" fill="#D9D9D9"/>
                        <path d="M21.2692 28.0476H49.7308V30.9047H21.2692V28.0476ZM27.6731 37.0953H43.3269V39.9524H27.6731V37.0953ZM31.9423 46.1429H39.0577V49H31.9423V46.1429ZM17 19H54V21.8571H17V19Z" fill="#166C5C"/>
                    </svg>
                </a>
            </div>

        </div>
    </section>

    <!-- KONTEN UTAMA -->
    <main class="main-content">

        <!-- DESTINASI UTAMA -->
        <section class="destination-section">

            <h2>Rekomendasi Untuk kamu</h2>

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
                                <svg class="map-pin-icon" viewBox="0 0 40 44" aria-hidden="true">
                                    <ellipse class="map-pin-ground" cx="20" cy="37" rx="14" ry="5"></ellipse>
                                    <path class="map-pin-ground-right" d="M20 32a14 5 0 0 1 0 10z"></path>
                                    <path class="map-pin-body" d="M20 3C13.4 3 9 7.9 9 13.8c0 7.2 8.6 14.6 11 19.2 2.4-4.6 11-12 11-19.2C31 7.9 26.6 3 20 3z"></path>
                                    <circle class="map-pin-hole" cx="20" cy="13.5" r="4.8"></circle>
                                </svg>
                                <span><?= htmlspecialchars($destination['wilayah'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <span
                                class="distance"
                                data-latitude="<?= htmlspecialchars((string) ($destination['latitude'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                data-longitude="<?= htmlspecialchars((string) ($destination['longitude'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                Menghitung jarak...
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>


        <!-- DESTINASI LAINNYA -->
        <section class="destination-section nearby-section">

            <h2>Wisata Terdekat dari lokasi mu</h2>

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
                                <svg class="map-pin-icon" viewBox="0 0 40 44" aria-hidden="true">
                                    <ellipse class="map-pin-ground" cx="20" cy="37" rx="14" ry="5"></ellipse>
                                    <path class="map-pin-ground-right" d="M20 32a14 5 0 0 1 0 10z"></path>
                                    <path class="map-pin-body" d="M20 3C13.4 3 9 7.9 9 13.8c0 7.2 8.6 14.6 11 19.2 2.4-4.6 11-12 11-19.2C31 7.9 26.6 3 20 3z"></path>
                                    <circle class="map-pin-hole" cx="20" cy="13.5" r="4.8"></circle>
                                </svg>
                                <span><?= htmlspecialchars($destination['wilayah'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <span
                                class="distance"
                                data-latitude="<?= htmlspecialchars((string) ($destination['latitude'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                data-longitude="<?= htmlspecialchars((string) ($destination['longitude'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                Menghitung jarak...
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
        const isLoggedIn = <?= json_encode($isLoggedIn); ?>;

        searchForm.addEventListener('submit', function (event) {
            const query = searchInput.value.trim();
            if (Array.from(query).length < 2) {
                event.preventDefault();
                searchInput.setCustomValidity('Masukkan minimal 2 karakter untuk mencari.');
                searchInput.reportValidity();
                searchInput.focus();
                return;
            }

            if (!isLoggedIn) {
                event.preventDefault();
                const loginUrl = new URL('FrontEnd/auth/Login.php', window.location.href);
                loginUrl.searchParams.set('next', 'search');
                loginUrl.searchParams.set('q', query);
                window.location.assign(loginUrl.href);
            }
        });
        searchInput.addEventListener('input', function () {
            searchInput.setCustomValidity('');
        });

        const distanceElements = Array.from(
            document.querySelectorAll('.distance[data-latitude][data-longitude]')
        );
        const destinationsWithCoordinates = distanceElements
            .map(element => ({
                element,
                latitude: Number(element.dataset.latitude),
                longitude: Number(element.dataset.longitude)
            }))
            .filter(destination =>
                elementHasCoordinates(destination.element) &&
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

        function elementHasCoordinates(element) {
            return element.dataset.latitude.trim() !== '' &&
                element.dataset.longitude.trim() !== '';
        }

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