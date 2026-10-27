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
    <link href="https://fonts.googleapis.com/css2?family=Fragment+Mono:ital@0;1&family=Kaushan+Script&family=Noto+Serif:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/edit-profil.css?v=20261031">
    <link rel="stylesheet" href="../CSS/theme.css?v=20261019-2">
    <link rel="stylesheet" href="../CSS/tentang.css?v=shared-layout-13">
</head>
<body class="about-page halaman-tentang">

    <header class="ep-navbar">
        <div class="ep-navbar__inner">
            <a class="ep-logo" href="../../index.php">Explore Majaku</a>

            <nav class="ep-menu" aria-label="Menu utama">
                <a class="ep-menu__beranda" href="../../index.php">Beranda</a>
                <a class="ep-menu__kategori" href="#kategori" id="kategori-toggle"
                   role="button" aria-haspopup="true" aria-expanded="false" aria-controls="kategori-menu">Kategori</a>
                <a class="ep-menu__tentang" href="tentang.php">Tentang</a>
            </nav>

            <div class="ep-aksi">
                <a class="ep-ikon ep-ikon--cari" href="hasil_pencarian.php" aria-label="Cari">
                    <svg viewBox="0 0 36 36" fill="none" stroke="currentColor" stroke-width="3.6" stroke-linecap="round" aria-hidden="true">
                        <circle cx="13.5" cy="13.5" r="11.7"/>
                        <line x1="22.3" y1="22.3" x2="33.2" y2="33.2"/>
                    </svg>
                </a>

                <a class="ep-ikon ep-ikon--profil" href="profil.php" aria-label="Profil">
                    <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="20" cy="20" r="18.2"/>
                        <circle cx="20" cy="16.2" r="5.8"/>
                        <path d="M9 33.5C9.7 29.2 12.2 26 15.2 25.2C17 26.6 23 26.6 24.8 25.2C27.8 26 30.3 29.2 31 33.5"/>
                    </svg>
                </a>
            </div>
        </div>
    </header>

    <ul class="dd-kategori" id="kategori-menu" role="menu" aria-label="Kategori wisata">
        <li role="none"><a class="dd-kategori__item" role="menuitem" href="kategori.php?jenis=alam">Wisata Alam &amp; pegunungan</a></li>
        <li role="none"><a class="dd-kategori__item" role="menuitem" href="kategori.php?jenis=air-curug">Wisata Air &amp; Curug</a></li>
        <li role="none"><a class="dd-kategori__item" role="menuitem" href="kategori.php?jenis=buatan-kuliner">Wisata Buatan &amp; Kuliner</a></li>
        <li role="none"><a class="dd-kategori__item" role="menuitem" href="kategori.php?jenis=sejarah-budaya">Wisata Sejarah &amp; Budaya</a></li>
    </ul>

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
                        <svg class="benefit-clip-icon" viewBox="0 0 44 53" aria-hidden="true">
                            <rect x="0" y="6" width="44" height="47" rx="8" fill="currentColor"></rect>
                            <rect x="13" y="1.5" width="18" height="12" rx="4.5" fill="currentColor" stroke="#eef2ee" stroke-width="3" paint-order="stroke"></rect>
                            <path d="M11.5 33.5L19.5 41.5L33 26" fill="none" stroke="#eef2ee" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                        <div class="benefit-text">
                            <strong class="benefit-title">Informasi Lengkap</strong>
                            <span class="benefit-desc">Semua Kebutuhan Wisata<br>dalam satu platform</span>
                        </div>
                    </article>
                    <article class="benefit-card bc-2">
                        <svg class="benefit-thumb-icon" viewBox="50 56 249 235" aria-hidden="true">
                            <path d="M50 157H126V82Q126 62 140 58Q146 56 150 57C163 90 185 140 220 160V262L150 290H95C65 262 50 215 50 157Z"></path>
                            <path d="M245 142H298V282H245Q235 282 235 272V152Q235 142 245 142Z"></path>
                        </svg>
                        <div class="benefit-text">
                            <strong class="benefit-title">Mudah diakses</strong>
                            <span class="benefit-desc">Tampilan sederhana<br>dan User-friendly</span>
                        </div>
                    </article>
                    <article class="benefit-card bc-3">
                        <svg class="benefit-trust-icon" viewBox="0 0 54 58" aria-hidden="true">
                            <path d="M3 3H51V42H35L27 53L19 42H3Z"></path>
                            <path d="M15 22L24 31L39 14"></path>
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
            if (!toggle || !menu) return;

            document.body.appendChild(menu);

            function terbuka() {
                return menu.classList.contains('show');
            }

            function posisi() {
                var r = toggle.getBoundingClientRect();
                var dasar = header ? header.getBoundingClientRect().bottom : r.bottom;
                var lebar = menu.offsetWidth;
                var kiri;
                if (window.innerWidth <= 900) {
                    kiri = (window.innerWidth - lebar) / 2;
                } else {
                    kiri = r.left + r.width * 0.15;
                }
                kiri = Math.max(8, Math.min(kiri, window.innerWidth - lebar - 8));
                menu.style.top = Math.round(dasar + 6) + 'px';
                menu.style.left = Math.round(kiri) + 'px';
            }

            function buka() {
                menu.classList.add('show');
                menu.style.setProperty('display', 'block', 'important');
                menu.style.setProperty('visibility', 'visible', 'important');
                menu.style.setProperty('opacity', '1', 'important');
                posisi();
                toggle.classList.add('aktif');
                toggle.setAttribute('aria-expanded', 'true');
            }

            function tutup() {
                menu.classList.remove('show');
                menu.style.removeProperty('display');
                menu.style.removeProperty('visibility');
                menu.style.removeProperty('opacity');
                toggle.classList.remove('aktif');
                toggle.setAttribute('aria-expanded', 'false');
            }

            toggle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (terbuka()) { tutup(); } else { buka(); }
            });

            document.addEventListener('click', function (e) {
                if (terbuka() && !menu.contains(e.target) && !toggle.contains(e.target)) tutup();
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && terbuka()) {
                    tutup();
                    toggle.focus();
                }
            });

            window.addEventListener('resize', function () {
                if (terbuka()) posisi();
            });
        })();
    </script>
</body>
</html>