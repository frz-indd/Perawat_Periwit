<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Beranda | Explore Majaku</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <!-- CSS Beranda -->
    <link rel="stylesheet" href="CSS/beranda.css?v=20261013-1">
</head>

<body>

    <!-- =====================================================
         NAVBAR
    ====================================================== -->

    <header class="navbar">

        <div class="navbar-container">

            <!-- LOGO -->
            <a href="index.php" class="navbar-logo">
                Explore Majaku
            </a>

            <!-- MENU -->
            <nav class="navbar-menu">

                <a href="index.php" class="active">
                    Beranda
                </a>

                <a href="kategori/kategori-alam.php">
                    Kategori
                </a>

                <a href="pages/tentang.php">
                    Tentang
                </a>

            </nav>

            <!-- ICON DAN TOMBOL LOGIN -->
            <div class="navbar-icons">

                <!-- Pencarian -->
                <a
                    href="hasil-pencarian.php"
                    class="nav-icon"
                    aria-label="Cari wisata"
                    title="Cari wisata"
                >
                    <i class="bi bi-search"></i>
                </a>

                <!-- Profil -->
                <a
                    href="auth/Login.php"
                    class="nav-icon"
                    aria-label="Profil pengguna"
                    title="Profil pengguna"
                >
                    <i class="bi bi-person-circle"></i>
                </a>

                <!-- Tombol Login -->
                <a href="auth/Login.php" class="login-button">
                    Login
                </a>

            </div>

        </div>

    </header>


    <!-- =====================================================
         HERO
    ====================================================== -->

    <section class="hero">

        <div class="hero-overlay"></div>

        <div class="hero-content">

            <h1>
                Mau Liburan Ke Mana?
            </h1>

            <p>
                Temukan destinasi wisata terbaik di Majalengka &amp; Kuningan
            </p>

            <!-- SEARCH -->
            <form
                class="search-box"
                id="searchForm"
                action="hasil-pencarian.php"
                method="GET"
            >

                <input
                    type="text"
                    name="keyword"
                    placeholder="Cari destinasi, kata kunci atau lokasi"
                    id="searchInput"
                    aria-label="Cari destinasi wisata"
                    required
                >

                <button
                    type="submit"
                    id="searchButton"
                    aria-label="Cari"
                >
                    <i class="bi bi-search"></i>
                </button>

            </form>

        </div>

    </section>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main-content">

        <!-- REKOMENDASI -->
        <section class="destination-section">

            <h2>
                Rekomendasi Untuk Kamu
            </h2>

            <div class="destination-grid">

                <!-- CARD 1 -->
                <a href="#" class="destination-card">

                    <img
                        src="assets/images/wisata/cilengkrang.jpg"
                        alt="Cilengkrang"
                        loading="lazy"
                    >

                    <div class="card-content">

                        <h3>Cilengkrang</h3>

                        <div class="rating">
                            <span class="stars">★ ★ ★</span>
                            <span>(2,7k ulasan)</span>
                        </div>

                        <div class="location">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span>
                                Kabupaten Kuningan,<br>
                                Jawa Barat.
                            </span>
                        </div>

                        <span class="distance">5.76 KM</span>

                    </div>

                </a>


                <!-- CARD 2 -->
                <a href="#" class="destination-card">

                    <img
                        src="assets/images/wisata/bukit-pamor.jpg"
                        alt="Bukit Pamoroan"
                        loading="lazy"
                    >

                    <div class="card-content">

                        <h3>Bukit Pamoroan</h3>

                        <div class="rating">
                            <span class="stars">★ ★ ★</span>
                            <span>(2,6k ulasan)</span>
                        </div>

                        <div class="location">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span>
                                Kabupaten Majalengka,<br>
                                Jawa Barat.
                            </span>
                        </div>

                        <span class="distance">5.76 KM</span>

                    </div>

                </a>


                <!-- CARD 3 -->
                <a href="#" class="destination-card">

                    <img
                        src="assets/images/wisata/arunika-garden.jpg"
                        alt="Arunika Garden"
                        loading="lazy"
                    >

                    <div class="card-content">

                        <h3>Arunika Garden</h3>

                        <div class="rating">
                            <span class="stars">★ ★ ★</span>
                            <span>(2,5k ulasan)</span>
                        </div>

                        <div class="location">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span>
                                Kabupaten Kuningan,<br>
                                Jawa Barat.
                            </span>
                        </div>

                        <span class="distance">7.68 KM</span>

                    </div>

                </a>


                <!-- CARD 4 -->
                <a href="#" class="destination-card">

                    <img
                        src="assets/images/wisata/curug-putri.jpg"
                        alt="Curug Putri Palutungan"
                        loading="lazy"
                    >

                    <div class="card-content">

                        <h3>Curug Putri Palutungan</h3>

                        <div class="rating">
                            <span class="stars">★ ★ ★</span>
                            <span>(1,5k ulasan)</span>
                        </div>

                        <div class="location">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span>
                                Kabupaten Kuningan,<br>
                                Jawa Barat.
                            </span>
                        </div>

                        <span class="distance">12.68 KM</span>

                    </div>

                </a>

            </div>

        </section>


        <!-- WISATA TERDEKAT -->
        <section class="destination-section nearby-section">

            <h2>
                Wisata Terdekat dari Lokasimu
            </h2>

            <div class="destination-grid">

                <!-- CARD 1 -->
                <a href="#" class="destination-card">

                    <img
                        src="assets/images/wisata/cilengkrang.jpg"
                        alt="Cilengkrang"
                        loading="lazy"
                    >

                    <div class="card-content">

                        <h3>Cilengkrang</h3>

                        <div class="rating">
                            <span class="stars">★ ★ ★</span>
                            <span>(2,7k ulasan)</span>
                        </div>

                        <div class="location">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span>
                                Kabupaten Kuningan,<br>
                                Jawa Barat.
                            </span>
                        </div>

                    </div>

                </a>


                <!-- CARD 2 -->
                <a href="#" class="destination-card">

                    <img
                        src="assets/images/wisata/bukit-pamor.jpg"
                        alt="Bukit Pamoroan"
                        loading="lazy"
                    >

                    <div class="card-content">

                        <h3>Bukit Pamoroan</h3>

                        <div class="rating">
                            <span class="stars">★ ★ ★</span>
                            <span>(2,6k ulasan)</span>
                        </div>

                        <div class="location">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span>
                                Kabupaten Majalengka,<br>
                                Jawa Barat.
                            </span>
                        </div>

                    </div>

                </a>


                <!-- CARD 3 -->
                <a href="#" class="destination-card">

                    <img
                        src="assets/images/wisata/arunika-garden.jpg"
                        alt="Arunika Garden"
                        loading="lazy"
                    >

                    <div class="card-content">

                        <h3>Arunika Garden</h3>

                        <div class="rating">
                            <span class="stars">★ ★ ★</span>
                            <span>(2,5k ulasan)</span>
                        </div>

                        <div class="location">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span>
                                Kabupaten Kuningan,<br>
                                Jawa Barat.
                            </span>
                        </div>

                    </div>

                </a>


                <!-- CARD 4 -->
                <a href="#" class="destination-card">

                    <img
                        src="assets/images/wisata/curug-putri.jpg"
                        alt="Curug Putri Palutungan"
                        loading="lazy"
                    >

                    <div class="card-content">

                        <h3>Curug Putri Palutungan</h3>

                        <div class="rating">
                            <span class="stars">★ ★ ★</span>
                            <span>(1,5k ulasan)</span>
                        </div>

                        <div class="location">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span>
                                Kabupaten Kuningan,<br>
                                Jawa Barat.
                            </span>
                        </div>

                    </div>

                </a>

            </div>

        </section>

    </main>


    <!-- =====================================================
         JAVASCRIPT
    ====================================================== -->

    <script>
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');

        searchForm.addEventListener('submit', function (event) {
            const keyword = searchInput.value.trim();

            if (keyword === '') {
                event.preventDefault();
                alert('Silakan masukkan destinasi yang ingin dicari.');
                searchInput.focus();
            }
        });
    </script>

</body>
</html>