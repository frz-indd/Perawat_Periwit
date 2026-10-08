<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami | Explore Majaku</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/tentang.css?v=20261008-12">
</head>
<body class="about-page">
    <header class="site-header">
        <nav class="navbar navbar-expand-md">
            <div class="container">
                <a class="brand" href="../exploreMajaKu_skeleton/index.html">Explore Majaku</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Buka navigasi">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="mainNav">
                    <ul class="navbar-nav mx-auto gap-lg-5">
                        <li class="nav-item"><a class="nav-link" href="../exploreMajaKu_skeleton/index.html">Beranda</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="../exploreMajaKu_skeleton/pages/kategori-alam.html" role="button" data-bs-toggle="dropdown" aria-expanded="false">kategori</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="../exploreMajaKu_skeleton/pages/kategori-alam.html">Alam</a></li>
                                <li><a class="dropdown-item" href="../exploreMajaKu_skeleton/pages/kategori-air-curug.html">Air &amp; Curug</a></li>
                                <li><a class="dropdown-item" href="../exploreMajaKu_skeleton/pages/kategori-buatan-kuliner.html">Buatan &amp; Kuliner</a></li>
                                <li><a class="dropdown-item" href="../exploreMajaKu_skeleton/pages/kategori-sejarah-budaya.html">Sejarah &amp; Budaya</a></li>
                            </ul>
                        </li>
                        <li class="nav-item"><a class="nav-link active" href="tentang.php" aria-current="page">Tentang</a></li>
                    </ul>
                    <div class="nav-icons">
                        <a href="../exploreMajaKu_skeleton/pages/hasil-pencarian.html" aria-label="Cari"><i class="bi bi-search"></i></a>
                        <a href="../exploreMajaKu_skeleton/pages/profil.html" aria-label="Profil"><i class="bi bi-person-circle"></i></a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <section class="about-hero" aria-label="Tentang Explore Majaku">
            <div class="about-hero-images" aria-hidden="true">
                <img src="../assets/images/telaga.png" alt="">
                <img src="../assets/images/makanan.png" alt="">
                <img src="../assets/images/pamoroan.jpg" alt="">
            </div>
            <div class="about-hero-shade"></div>
            <div class="about-hero-copy">
                <h1>EXPLORE MAJAKU</h1>
                <p>Solusi Terbaik untuk menemukan destinasi wisata menarik di wilayah<br class="desktop-break"> majalengka dan kuningan</p>
            </div>
        </section>

        <section class="about-content" aria-label="Informasi tentang Explore Majaku">
            <article class="about-card about-intro">
                <h2>Tentang Kami</h2>
                <p>Explore MajaKu adalah platform informasi wisata yang membantu pengguna menemukan dan mengenal berbagai destinasi wisata di Majalengka dan Kuningan. Kami menyediakan informasi destinasi, lokasi, fasilitas, harga tiket, jam operasional, dan ulasan untuk memudahkan wisatawan</p>
            </article>

            <div class="about-purpose">
                <article class="about-card">
                    <h2>Visi</h2>
                    <p>Menjadi platform wisata terpercaya yang mendukung pengembangan pariwisata wilayah majalengka kuningan</p>
                </article>
                <article class="about-card">
                    <h2>Misi</h2>
                    <p>Menyediakan informasi wisata yang akurat dan terkini, Memudahkan wisatawan dalam merencanakan perjalanan.</p>
                </article>
            </div>

            <section class="about-benefits">
                <h2>Kenapa Memilih Explore MajaKu?</h2>
                <div class="benefit-grid">
                    <article class="benefit-card">
                        <i class="bi bi-clipboard-check-fill" aria-hidden="true"></i>
                        <p><strong>Informasi Lengkap</strong><br>Semua Kebutuhan Wisata dalam satu platform</p>
                    </article>
                    <article class="benefit-card">
                        <svg class="benefit-thumb-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z"></path>
                        </svg>
                        <p><strong>Mudah diakses</strong><br>Tampilan sederhana dan User-friendly</p>
                    </article>
                    <article class="benefit-card">
                        <svg class="benefit-trust-icon" viewBox="0 0 40 40" aria-hidden="true">
                            <path d="M5 5.5h30v24H21l-7 6v-6H5z"></path>
                            <path d="m12 17 5.5 5.5L28 12"></path>
                        </svg>
                        <p><strong>Terpercaya</strong><br>Informasi Valid dan update</p>
                    </article>
                </div>
            </section>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
