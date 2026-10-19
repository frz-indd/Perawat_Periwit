<?php
// tentang.php - Explore Majaku
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami | Explore Majaku</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fragment+Mono:ital@0;1&family=Frank+Ruhl+Libre:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=Kaushan+Script&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Bootstrap CSS & Icons (untuk ikon konten) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- CSS utama (urutan penting: tentang.css paling akhir) -->
    <link rel="stylesheet" href="../CSS/edit-profil.css?v=font-map-2">
    <link rel="stylesheet" href="../CSS/tentang.css?v=font-map-2">
    <link rel="stylesheet" href="../CSS/theme.css?v=profile-icon-1">
</head>
<body class="about-page halaman-tentang">

    <!-- Sprite ikon profil (sama seperti Edit Profil) -->
    <svg width="0" height="0" style="position:absolute" aria-hidden="true">
        <defs>
            <clipPath id="klip-avatar"><circle cx="110" cy="110" r="110"/></clipPath>
            <symbol id="ikon-profil" viewBox="0 0 220 220">
                <circle cx="110" cy="110" r="93.5" fill="none" stroke="#166d5c" stroke-width="11"/>
                <circle cx="110" cy="82.5" r="27.5" fill="none" stroke="#166d5c" stroke-width="11"/>
                <path d="M49.5 170.5c9.35-27.5 30.25-41.25 60.5-41.25s51.15 13.75 60.5 41.25" fill="none" stroke="#166d5c" stroke-width="11" stroke-linecap="round" stroke-linejoin="round"/>
            </symbol>
        </defs>
    </svg>

    <header class="ep-navbar">
        <div class="ep-navbar__inner">
            <a class="ep-logo" href="../../index.php">Explore Majaku</a>

            <nav class="ep-menu" aria-label="Menu utama">
                <a class="ep-menu__beranda" href="../../index.php">Beranda</a>
                <a class="ep-menu__kategori" href="#" id="kategori-toggle"
                   aria-haspopup="true" aria-expanded="false" aria-controls="kategori-menu">kategori</a>
                <a class="ep-menu__tentang" href="tentang.php" style="color: var(--hijau);">Tentang</a>
            </nav>

            <!-- Ikon cari (sama persis dengan Edit Profil) -->
            <a class="ep-ikon ep-ikon--cari" href="hasil_pencarian.php" aria-label="Cari">
                <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round">
                    <circle cx="16" cy="16" r="12"/>
                    <line x1="25" y1="25" x2="37" y2="37"/>
                </svg>
            </a>

            <!-- Ikon profil (sama persis dengan Edit Profil) -->
            <a class="ep-ikon ep-ikon--profil" href="profil.php" aria-label="Profil">
                <svg viewBox="0 0 220 220"><use href="#ikon-profil"/></svg>
            </a>
        </div>

        <!-- Daftar kategori: sengaja di luar .ep-menu -->
        <ul class="kat-menu" id="kategori-menu">
            <li><a class="kat-item" href="kategori.php?kategori=alam">Wisata Alam &amp; Pegunungan</a></li>
            <li><a class="kat-item" href="kategori.php?kategori=air-curug">Wisata Air &amp; Curug</a></li>
            <li><a class="kat-item" href="kategori.php?kategori=buatan-kuliner">Wisata Buatan &amp; Kuliner</a></li>
            <li><a class="kat-item" href="kategori.php?kategori=sejarah-budaya">Wisata Sejarah &amp; Budaya</a></li>
        </ul>
    </header>

    <!-- Konten Utama Halaman Tentang -->
    <main>
        <section class="about-hero" aria-label="Tentang Explore Majaku">
            <div class="about-hero-images" aria-hidden="true">
                <img src="../assets/images/telaga.png" alt="Telaga">
                <img src="../assets/images/makanan.png" alt="Makanan">
                <img src="../assets/images/pamoroan.jpg" alt="Pamoroan">
            </div>
            <div class="about-hero-shade"></div>
            <div class="about-hero-copy">
                <h1>Explore MajaKu</h1>
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

            // Menempel tepat di bawah navbar, rata tengah di bawah tulisan "kategori"
            function posisikan() {
                var r = toggle.getBoundingClientRect();
                var bawahNavbar = header.getBoundingClientRect().bottom;
                menu.style.top = bawahNavbar + 'px';
                menu.style.left = (r.left + r.width / 2 - menu.offsetWidth / 2) + 'px';
            }

            function buka() {
                menu.classList.add('show'); // tampilkan dulu supaya lebar bisa diukur
                posisikan();
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