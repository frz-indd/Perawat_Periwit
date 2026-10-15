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
    <link rel="stylesheet" href="CSS/style.css?v=20261014">
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
                <a href="kategori/kategori-alam.php">kategori</a>
                <a href="pages/tentang.php">Tentang</a>
            </nav>

            <div class="navbar-icons">

                <a href="auth/Login.php"
                   class="profile-icon"
                   aria-label="Login atau profil">
                    <i class="bi bi-person-circle"></i>
                </a>

                <a href="auth/Login.php" class="login-button">
                    Login
                </a>

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
                action="hasil_pencarian.php"
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

        <!-- REKOMENDASI -->
        <section class="destination-section">

            <h2>Rekomendasi Untuk kamu</h2>

            <div class="destination-grid">

                <!-- CILENGKRANG -->
                <a href="#" class="destination-card">

                    <img
                        src="assets/images/wisata/cilengkrang.jpg"
                        alt="Cilengkrang"
                    >

                    <div class="card-content">

                        <h3>Cilengkrang</h3>

                        <div class="rating">
                            <span class="stars">★ ★ ★</span>
                            <span class="review">(2,7k ulasan)</span>
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


                <!-- BUKIT PAMOROAN -->
                <a href="#" class="destination-card">

                    <img
                        src="assets/images/wisata/bukit-pamor.jpg"
                        alt="Bukit Pamoroan"
                    >

                    <div class="card-content">

                        <h3>Bukit Pamoroan</h3>

                        <div class="rating">
                            <span class="stars">★ ★ ★</span>
                            <span class="review">(2,6k ulasan)</span>
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


                <!-- ARUNIKA GARDEN -->
                <a href="#" class="destination-card">

                    <img
                        src="assets/images/wisata/arunika-garden.jpg"
                        alt="Arunika Garden"
                    >

                    <div class="card-content">

                        <h3>Arunika Garden</h3>

                        <div class="rating">
                            <span class="stars">★ ★ ★</span>
                            <span class="review">(2,5k ulasan)</span>
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


                <!-- CURUG PUTRI -->
                <a href="#" class="destination-card">

                    <img
                        src="assets/images/wisata/curug-putri.jpg"
                        alt="Curug Putri Palutungan"
                    >

                    <div class="card-content">

                        <h3>Curug putri palutungan</h3>

                        <div class="rating">
                            <span class="stars">★ ★ ★</span>
                            <span class="review">(1,5k ulasan)</span>
                        </div>

                        <div class="location">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span>
                                Kabupaten Kuningan,<br>
                                Jawa Barat.
                            </span>
                        </div>

                        <span class="distance">6.68 KM</span>

                    </div>
                </a>

            </div>
        </section>


        <!-- WISATA TERDEKAT -->
        <section class="destination-section nearby-section">

            <h2>Wisata Terdekat dari lokasi mu</h2>

            <div class="destination-grid">

                <!-- CILENGKRANG -->
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
                            <span class="review">(2,7k ulasan)</span>
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


                <!-- BUKIT PAMOROAN -->
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
                            <span class="review">(2,6k ulasan)</span>
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


                <!-- ARUNIKA GARDEN -->
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
                            <span class="review">(2,5k ulasan)</span>
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


                <!-- CURUG PUTRI -->
                <a href="#" class="destination-card">

                    <img
                        src="assets/images/wisata/curug-putri.jpg"
                        alt="Curug Putri Palutungan"
                        loading="lazy"
                    >

                    <div class="card-content">

                        <h3>Curug putri palutungan</h3>

                        <div class="rating">
                            <span class="stars">★ ★ ★</span>
                            <span class="review">(1,5k ulasan)</span>
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