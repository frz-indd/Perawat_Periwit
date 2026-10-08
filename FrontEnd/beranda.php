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

    <!-- CSS -->
    <link rel="stylesheet" href="CSS/style.css">
</head>

<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

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
                kategori
            </a>

            <a href="tentang.php">
                Tentang
            </a>

        </nav>


        <!-- ICON -->
        <div class="navbar-icons">

            <a href="#" class="nav-icon">
                <i class="bi bi-search"></i>
            </a>

            <a href="auth/Login.php" class="nav-icon">
                <i class="bi bi-person-circle"></i>
            </a>

        </div>

    </div>

</header>



<!-- =====================================================
     HERO
===================================================== -->

<section class="hero">

    <div class="hero-overlay"></div>

    <div class="hero-content">

        <h1>
            Mau Liburan Ke mana?
        </h1>

        <p>
            Temukan destinasi wisata terbaik di majalengka &amp; Kuningan
        </p>


        <!-- SEARCH -->
        <div class="search-box">

            <input
                type="text"
                placeholder="Cari destinasi,kata kunci atau lokasi"
                id="searchInput"
            >

            <button type="button" id="searchButton">
                <i class="bi bi-search"></i>
            </button>

        </div>

    </div>

</section>



<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<main class="main-content">


    <!-- =================================================
         REKOMENDASI
    ================================================== -->

    <section class="destination-section">

        <h2>
            Rekomendasi Untuk kamu
        </h2>


        <div class="destination-grid">


            <!-- CARD 1 -->
            <a href="#" class="destination-card">

                <img
                    src="assets/images/wisata/cilengkrang.jpg"
                    alt="Cilengkrang"
                >

                <div class="card-content">

                    <h3>
                        Cilengkrang
                    </h3>

                    <div class="rating">

                        <span class="stars">
                            ★ ★ ★
                        </span>

                        <span>
                            (2,7k ulasan)
                        </span>

                    </div>

                    <div class="location">

                        <i class="bi bi-geo-alt-fill"></i>

                        <span>
                            Kabupaten Kuningan,<br>
                            Jawa Barat.
                        </span>

                    </div>

                    <span class="distance">
                        5.76 KM
                    </span>

                </div>

            </a>



            <!-- CARD 2 -->
            <a href="#" class="destination-card">

                <img
                    src="assets/images/wisata/bukit-pamor.jpg"
                    alt="Bukit Pamoroan"
                >

                <div class="card-content">

                    <h3>
                        Bukit Pamoroan
                    </h3>

                    <div class="rating">

                        <span class="stars">
                            ★ ★ ★
                        </span>

                        <span>
                            (2,6k ulasan)
                        </span>

                    </div>

                    <div class="location">

                        <i class="bi bi-geo-alt-fill"></i>

                        <span>
                            Kabupaten Majalengka,<br>
                            Jawa Barat.
                        </span>

                    </div>

                    <span class="distance">
                        5.76 KM
                    </span>

                </div>

            </a>



            <!-- CARD 3 -->
            <a href="#" class="destination-card">

                <img
                    src="assets/images/wisata/arunika-garden.jpg"
                    alt="Arunika Garden"
                >

                <div class="card-content">

                    <h3>
                        Arunika Garden
                    </h3>

                    <div class="rating">

                        <span class="stars">
                            ★ ★ ★
                        </span>

                        <span>
                            (2,5k ulasan)
                        </span>

                    </div>

                    <div class="location">

                        <i class="bi bi-geo-alt-fill"></i>

                        <span>
                            Kabupaten Kuningan,<br>
                            Jawa Barat.
                        </span>

                    </div>

                    <span class="distance">
                        7.68 KM
                    </span>

                </div>

            </a>



            <!-- CARD 4 -->
            <a href="#" class="destination-card">

                <img
                    src="assets/images/wisata/curug-putri.jpg"
                    alt="Curug Putri Palutungan"
                >

                <div class="card-content">

                    <h3>
                        Curug putri palutungan
                    </h3>

                    <div class="rating">

                        <span class="stars">
                            ★ ★ ★
                        </span>

                        <span>
                            (1,5k ulasan)
                        </span>

                    </div>

                    <div class="location">

                        <i class="bi bi-geo-alt-fill"></i>

                        <span>
                            Kabupaten Kuningan,<br>
                            Jawa Barat.
                        </span>

                    </div>

                    <span class="distance">
                        12.68 KM
                    </span>

                </div>

            </a>

        </div>

    </section>



    <!-- =================================================
         WISATA TERDEKAT
    ================================================== -->

    <section class="destination-section nearby-section">

        <h2>
            Wisata Terdekat dari lokasi mu
        </h2>


        <div class="destination-grid">


            <!-- CARD 1 -->
            <a href="#" class="destination-card">

                <img
                    src="assets/images/wisata/cilengkrang.jpg"
                    alt="Cilengkrang"
                >

                <div class="card-content">

                    <h3>
                        Cilengkrang
                    </h3>

                    <div class="rating">

                        <span class="stars">
                            ★ ★ ★
                        </span>

                        <span>
                            (2,7k ulasan)
                        </span>

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
                >

                <div class="card-content">

                    <h3>
                        Bukit pamoroan
                    </h3>

                    <div class="rating">

                        <span class="stars">
                            ★ ★ ★
                        </span>

                        <span>
                            (2,6k ulasan)
                        </span>

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
                >

                <div class="card-content">

                    <h3>
                        Arunika Garden
                    </h3>

                    <div class="rating">

                        <span class="stars">
                            ★ ★ ★
                        </span>

                        <span>
                            (2,5k ulasan)
                        </span>

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
                >

                <div class="card-content">

                    <h3>
                        Curug putri palutungan
                    </h3>

                    <div class="rating">

                        <span class="stars">
                            ★ ★ ★
                        </span>

                        <span>
                            (1,5k ulasan)
                        </span>

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
===================================================== -->

<script>

    const searchButton = document.getElementById('searchButton');
    const searchInput = document.getElementById('searchInput');

    searchButton.addEventListener('click', function () {

        const keyword = searchInput.value.trim();

        if (keyword === '') {
            alert('Silakan masukkan destinasi yang ingin dicari.');
            return;
        }

        console.log('Pencarian:', keyword);

    });


    searchInput.addEventListener('keypress', function (event) {

        if (event.key === 'Enter') {
            searchButton.click();
        }

    });

</script>

</body>
</html>