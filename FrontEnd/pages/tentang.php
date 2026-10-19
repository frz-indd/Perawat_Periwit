<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id" class="html-tentang">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami | Explore Majaku</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kaushan+Script&family=Noto+Serif:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&family=Roboto+Mono:wght@400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../CSS/edit-profil.css?v=20261018">
    <link rel="stylesheet" href="../CSS/theme.css?v=20261019-2">
    <link rel="stylesheet" href="../CSS/tentang.css?v=20261032">
</head>
<body class="about-page halaman-tentang">

    <header class="ep-navbar">
        <div class="ep-navbar__inner">
            <a class="ep-logo" href="../../index.php">Explore Majaku</a>
            <nav class="ep-menu" aria-label="Menu utama">
                <a class="ep-menu__beranda" href="../../index.php">Beranda</a>
                <a class="ep-menu__kategori" href="#" id="kategori-toggle"
                   aria-haspopup="true" aria-expanded="false" aria-controls="kategori-menu">kategori</a>
                <a class="ep-menu__tentang" href="tentang.php" aria-current="page">Tentang</a>
            </nav>
            <a class="ep-ikon ep-ikon--cari" href="hasil_pencarian.php" aria-label="Cari">
                <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" aria-hidden="true">
                    <circle cx="16.5" cy="16.5" r="11.5"/>
                    <line x1="25" y1="25" x2="36" y2="36"/>
                </svg>
            </a>
            <a class="ep-ikon ep-ikon--profil" href="profil.php" aria-label="Profil">
                <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="20" cy="20" r="17.5"/>
                    <circle cx="20" cy="16" r="5.8"/>
                    <path d="M9 31.5c1.5-5.8 5.6-8.8 11-8.8s9.5 3 11 8.8"/>
                </svg>
            </a>
        </div>
        <ul class="kat-menu" id="kategori-menu">
            <li><a class="kat-item" href="kategori.php?jenis=alam">Wisata Alam &amp; pegunungan</a></li>
            <li><a class="kat-item" href="kategori.php?jenis=air-curug">Wisata Air &amp; Curug</a></li>
            <li><a class="kat-item" href="kategori.php?jenis=buatan-kuliner">Wisata Buatan &amp; Kuliner</a></li>
            <li><a class="kat-item" href="kategori.php?jenis=sejarah-budaya">Wisata Sejarah &amp; Budaya</a></li>
        </ul>
    </header>

    <main>
        <section class="about-hero" aria-label="Tentang Explore Majaku">
            <div class="about-hero-images" aria-hidden="true">
                <img src="../assets/images/telaga.png" alt="Telaga">
                <img src="../assets/images/makanan.png" alt="Makanan">
                <img src="../assets/images/pamoroan.jpg" alt="Pamoroan">
            </div>
            <div class="about-hero-shade"></div>
            <div class="about-hero-copy">
                <h1>EXPLORE MAJAKU</h1>
                <p>Solusi Terbaik untuk menemukan destinasi wisata menarik di wilayah<br class="br-d">majalengka dan kuningan</p>
            </div>
        </section>

        <section class="about-content" aria-label="Informasi tentang Explore Majaku">
            <article class="about-card about-intro">
                <h2>Tentang Kami</h2>
                <p>Explore MajaKu adalah platform informasi wisata yang membantu pengguna menemukan dan<br class="br-d">mengenal berbagai destinasi wisata di Majalengka dan Kuningan. Kami menyediakan informasi<br class="br-d">destinasi, lokasi, fasilitas, harga tiket, jam operasional, dan ulasan untuk memudahkan wisatawan</p>
            </article>

            <div class="about-purpose">
                <article class="about-card about-visi">
                    <h2>Visi</h2>
                    <p>Menjadi platform wisata terpercaya yang<br class="br-d">mendukung pengembangan pariwisata wilayah<br class="br-d">majalengka kuningan</p>
                </article>
                <article class="about-card about-misi">
                    <h2>Misi</h2>
                    <p>Menyediakan informasi wisata yang akurat<br class="br-d">dan terkini, Memudahkan wisatawan dalam<br class="br-d">merencanakan perjalanan,</p>
                </article>
            </div>

            <section class="about-benefits">
                <h2>Kenapa Memilih Explore MajaKu?</h2>
                <div class="benefit-grid">
                    <article class="benefit-card bc-1">
                        <i class="bi bi-clipboard-check-fill" aria-hidden="true"></i>
                        <div class="benefit-text">
                            <strong class="benefit-title">Informasi Lengkap</strong>
                            <span class="benefit-desc">Semua Kebutuhan Wisata<br>dalam satu platform</span>
                        </div>
                    </article>
                    <article class="benefit-card bc-2">
                        <svg class="benefit-thumb-icon" viewBox="1 1 22 20" preserveAspectRatio="none" aria-hidden="true">
                            <path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z"></path>
                        </svg>
                        <div class="benefit-text">
                            <strong class="benefit-title">Mudah diakses</strong>
                            <span class="benefit-desc">Tampilan sederhana<br>dan User-friendly</span>
                        </div>
                    </article>
                    <article class="benefit-card bc-3">
                        <svg class="benefit-trust-icon" viewBox="0 0 56 56" aria-hidden="true">
                            <path d="M4 4H52V42H36L28 52L20 42H4Z"></path>
                            <path d="M14 22L24 32L42 14"></path>
                        </svg>
                        <div class="benefit-text">
                            <strong class="benefit-title">Terpercaya</strong>
                            <span class="benefit-desc">Informasi Valid dan<br>update</span>
                        </div>
                    </article>
                </div>
            </section>
        </section>
    </main>

    <script>
        (function () {
            var toggle = document.getElementById('kategori-toggle');
            var menu = document.getElementById('kategori-menu');
            var header = document.querySelector('.ep-navbar');

            function buka() {
                menu.classList.add('show');
                var r = toggle.getBoundingClientRect();
                menu.style.top = (header.getBoundingClientRect().bottom + 6) + 'px';
                menu.style.left = (r.left + r.width * 0.15) + 'px';
                toggle.classList.add('aktif');
                toggle.setAttribute('aria-expanded', 'true');
            }

            function tutup() {
                menu.classList.remove('show');
                toggle.classList.remove('aktif');
                toggle.setAttribute('aria-expanded', 'false');
            }

            toggle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                menu.classList.contains('show') ? tutup() : buka();
            });
            document.addEventListener('click', function (e) {
                if (!menu.contains(e.target)) tutup();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') tutup();
            });
            window.addEventListener('resize', tutup);
            window.addEventListener('scroll', tutup, { passive: true });
        })();
    </script>
</body>
</html>