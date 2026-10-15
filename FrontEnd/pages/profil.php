<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil | Explore Majaku</title>
    <link rel="stylesheet" href="../CSS/profil.css">
</head>
<body>
<main class="profil">
    <header class="site-header">
        <a class="text-wrapper-6" href="../exploreMajaKu_skeleton/index.html">Explore Majaku</a>
        <nav class="header-nav" aria-label="Navigasi utama">
            <a class="text-wrapper-7" href="../exploreMajaKu_skeleton/index.html">Beranda</a>
            <a class="text-wrapper-8" href="hasil_pencarian.php">Kategori</a>
            <a class="tentang" href="tentang.php">Tentang</a>
        </nav>
        <div class="header-icons">
            <a href="hasil_pencarian.php" aria-label="Cari" class="search-icon"></a>
            <a href="profil.php" aria-label="Profil" class="account-icon">
                <svg viewBox="0 0 48 48" aria-hidden="true">
                    <circle cx="24" cy="24" r="17"></circle>
                    <circle cx="24" cy="19" r="6"></circle>
                    <path d="M13.5 34c1.2-6 5-9.5 10.5-9.5s9.3 3.5 10.5 9.5"></path>
                </svg>
            </a>
        </div>
    </header>

    <aside class="profile-sidebar" aria-label="Informasi profil">
        <div class="ellipse" aria-hidden="true">
            <svg viewBox="0 0 180 180" aria-hidden="true">
                <circle class="avatar-ring" cx="90" cy="90" r="82"></circle>
                <circle class="avatar-head" cx="90" cy="72" r="25"></circle>
                <path class="avatar-body" d="M39 144c3-20 15-32 34-38l8-3c5 5 11 8 18 8s13-3 18-8l8 3c19 6 31 18 34 38-18 17-42 27-66 27s-48-10-66-27Z"></path>
            </svg>
        </div>
        <h1 class="text-wrapper">Nama User <span class="edit-icon" aria-hidden="true">
            <svg viewBox="0 0 48 48">
                <path d="M15 10h-3.5A4.5 4.5 0 0 0 7 14.5v22A4.5 4.5 0 0 0 11.5 41h22a4.5 4.5 0 0 0 4.5-4.5V33"></path>
                <path d="m20 32 2.1-7.1L37.7 9.3a3.3 3.3 0 0 1 4.7 4.7L26.8 29.6 20 32Z"></path>
                <path d="m34.8 12.2 4.7 4.7"></path>
                <circle cx="1.5" cy="25" r="1.4"></circle>
                <circle cx="45" cy="5" r="1.4"></circle>
            </svg>
        </span></h1>
        <p class="text-wrapper-2">PerawatPeriwit124@gmail.com</p>
        <nav class="group" aria-label="Menu profil">
            <a class="text-wrapper-3" href="../exploreMajaKu_skeleton/index.html">
                <span class="menu-icon" aria-hidden="true"><svg viewBox="0 0 28 28"><path d="m2 13 12-10 12 10M5 11v14h18V11M11 25v-9h6v9"></path></svg></span>Beranda
            </a>
            <a class="text-wrapper-4" href="#favorit">
                <span class="menu-icon" aria-hidden="true"><svg viewBox="0 0 28 28"><path d="M14 24S3 17.2 3 9.8A5.8 5.8 0 0 1 14 7.2a5.8 5.8 0 0 1 11 2.6C25 17.2 14 24 14 24Z"></path></svg></span>Destinasi Favorit
            </a>
            <a class="text-wrapper-5" href="../auth/Login.php">
                <span class="menu-icon" aria-hidden="true"><svg viewBox="0 0 28 28"><path d="M11 5 3 13l8 8v-5h14v-6H11V5Z"></path></svg></span>Logout
            </a>
        </nav>
    </aside>

    <section class="profile-content" aria-labelledby="favorite-title">
        <h2 class="div" id="favorite-title">Destinasi Favorit</h2>

        <article class="favorite-card" id="favorit" aria-label="Curug Putri Palutungan" tabindex="0">
            <img class="cuplikan-layar" src="../assets/images/CurugPutri.png" alt="Curug Putri Palutungan">
            <div class="group-5">
                <h3 class="text-wrapper-9">Curug Putri palutungan</h3>
                <div class="rating-row" aria-label="Rating 3 dari 5">
                    <div class="group-6" aria-hidden="true"><span class="image">★</span><span class="image">★</span><span class="image">★</span></div>
                    <span class="text-wrapper-10">(1,8k ulasan)</span>
                </div>
                <div class="location-row">
                    <svg class="image-2" viewBox="0 0 24 30" aria-hidden="true">
                        <ellipse cx="12" cy="26" rx="10" ry="3.5" fill="#42cf71"></ellipse>
                        <path d="M12 1C6.5 1 2 5.5 2 11c0 7 10 17 10 17s10-10 10-17C22 5.5 17.5 1 12 1Z" fill="#ed342d"></path>
                        <circle cx="12" cy="11" r="3.5" fill="#fff"></circle>
                    </svg>
                    <p class="kabupaten-kuningan">Kabupaten<br>Kuningan, Jawa Barat.</p>
                </div>
            </div>
            <button class="delete-icon" type="button" aria-label="Hapus Curug Putri Palutungan">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M9 2h6l1 2h5v2H3V4h5l1-2ZM5 7h14l-1 14H6L5 7Z"></path>
                    <path d="M9 10v8m3-8v8m3-8v8"></path>
                </svg>
            </button>
        </article>

        <article class="favorite-card" aria-label="Woodland" tabindex="0">
            <img class="img" src="../assets/images/Woodland.png" alt="Woodland">
            <div class="group-7">
                <h3 class="text-wrapper-9">Woodland</h3>
                <div class="rating-row" aria-label="Rating 3 dari 5">
                    <div class="group-6" aria-hidden="true"><span class="image">★</span><span class="image">★</span><span class="image">★</span></div>
                    <span class="text-wrapper-10">(1,8k ulasan)</span>
                </div>
                <div class="location-row">
                    <svg class="image-2" viewBox="0 0 24 30" aria-hidden="true">
                        <ellipse cx="12" cy="26" rx="10" ry="3.5" fill="#42cf71"></ellipse>
                        <path d="M12 1C6.5 1 2 5.5 2 11c0 7 10 17 10 17s10-10 10-17C22 5.5 17.5 1 12 1Z" fill="#ed342d"></path>
                        <circle cx="12" cy="11" r="3.5" fill="#fff"></circle>
                    </svg>
                    <p class="kabupaten-kuningan">Kabupaten<br>Kuningan, Jawa Barat.</p>
                </div>
            </div>
            <button class="delete-icon" type="button" aria-label="Hapus Woodland">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M9 2h6l1 2h5v2H3V4h5l1-2ZM5 7h14l-1 14H6L5 7Z"></path>
                    <path d="M9 10v8m3-8v8m3-8v8"></path>
                </svg>
            </button>
        </article>

        <article class="favorite-card" aria-label="Arunika Eatry Garden" tabindex="0">
            <img class="cuplikan-layar-2" src="../assets/images/Arunika.png" alt="Arunika Eatry Garden">
            <div class="group-8">
                <h3 class="text-wrapper-9">Arunika Eatry Garden</h3>
                <div class="rating-row" aria-label="Rating 3 dari 5">
                    <div class="group-6" aria-hidden="true"><span class="image">★</span><span class="image">★</span><span class="image">★</span></div>
                    <span class="text-wrapper-10">(1,8k ulasan)</span>
                </div>
                <div class="location-row">
                    <svg class="image-2" viewBox="0 0 24 30" aria-hidden="true">
                        <ellipse cx="12" cy="26" rx="10" ry="3.5" fill="#42cf71"></ellipse>
                        <path d="M12 1C6.5 1 2 5.5 2 11c0 7 10 17 10 17s10-10 10-17C22 5.5 17.5 1 12 1Z" fill="#ed342d"></path>
                        <circle cx="12" cy="11" r="3.5" fill="#fff"></circle>
                    </svg>
                    <p class="kabupaten-kuningan">Kabupaten<br>Kuningan, Jawa Barat.</p>
                </div>
            </div>
            <button class="delete-icon" type="button" aria-label="Hapus Arunika Eatry Garden">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M9 2h6l1 2h5v2H3V4h5l1-2ZM5 7h14l-1 14H6L5 7Z"></path>
                    <path d="M9 10v8m3-8v8m3-8v8"></path>
                </svg>
            </button>
        </article>
    </section>
</main>
<script>
    document.querySelectorAll('.favorite-card').forEach((card) => {
        const selectCard = () => {
            document.querySelectorAll('.favorite-card.is-selected').forEach((selected) => {
                selected.classList.remove('is-selected');
            });
            card.classList.add('is-selected');
        };

        card.addEventListener('click', (event) => {
            if (!event.target.closest('.delete-icon')) {
                selectCard();
            }
        });

        card.addEventListener('keydown', (event) => {
            if (event.target === card && (event.key === 'Enter' || event.key === ' ')) {
                event.preventDefault();
                selectCard();
            }
        });
    });
</script>
</body>
</html>
