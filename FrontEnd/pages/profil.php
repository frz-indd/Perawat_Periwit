<?php
session_start();
require_once __DIR__ . "/../../Backend/database/koneksi.php";
require_once __DIR__ . "/../../Backend/database/wisata_repository.php";

$userId = filter_var($_SESSION["id_user"] ?? null, FILTER_VALIDATE_INT);
$favorites = $userId !== false && $userId > 0
    ? fetchUserTourismFavorites($conn, (int) $userId)
    : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil | Explore Majaku</title>
    <link rel="stylesheet" href="../CSS/profil.css">
    <link rel="stylesheet" href="../CSS/site.css?v=20261019">
</head>
<body>
<main class="profil">
    <header class="site-header">
        <a class="text-wrapper-6" href="../../index.php">Explore Majaku</a>
        <nav class="header-nav" aria-label="Navigasi utama">
            <a class="text-wrapper-7" href="../../index.php">Beranda</a>
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
        <h1 class="text-wrapper"><span id="profile-name">Memuat profil...</span> <a class="edit-icon" href="edit-profil.php" aria-label="Edit profil">
            <svg viewBox="0 0 48 48" aria-hidden="true">
                <path d="M15 10h-3.5A4.5 4.5 0 0 0 7 14.5v22A4.5 4.5 0 0 0 11.5 41h22a4.5 4.5 0 0 0 4.5-4.5V33"></path>
                <path d="m20 32 2.1-7.1L37.7 9.3a3.3 3.3 0 0 1 4.7 4.7L26.8 29.6 20 32Z"></path>
                <path d="m34.8 12.2 4.7 4.7"></path>
                <circle cx="1.5" cy="25" r="1.4"></circle>
                <circle cx="45" cy="5" r="1.4"></circle>
            </svg>
        </a></h1>
        <p class="text-wrapper-2" id="profile-email"></p>
        <nav class="group" aria-label="Menu profil">
            <a class="text-wrapper-3" href="../../index.php">
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

        <?php if ($favorites !== []): ?>
            <?php foreach ($favorites as $favoriteIndex => $favorite): ?>
                <article class="favorite-card" id="<?= $favoriteIndex === 0 ? 'favorit' : 'favorite-' . (int) $favoriteIndex; ?>" aria-label="<?= htmlspecialchars($favorite['nama_wisata'], ENT_QUOTES, 'UTF-8'); ?>" tabindex="0">
                    <img class="cuplikan-layar" src="../../<?= htmlspecialchars($favorite['gambar'] ?? 'FrontEnd/assets/images/img.png', ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($favorite['nama_wisata'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="group-5">
                        <h3 class="text-wrapper-9"><?= htmlspecialchars($favorite['nama_wisata'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <div class="location-row">
                            <p class="kabupaten-kuningan"><?= htmlspecialchars($favorite['wilayah'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Belum ada destinasi favorit.</p>
        <?php endif; ?>
    </section>
</main>
<script>
    const profileName = document.getElementById('profile-name');
    const profileEmail = document.getElementById('profile-email');

    fetch(new URL('../../users/me', window.location.href), {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' }
    })
        .then(async (response) => {
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Profil gagal dimuat.');
            }

            profileName.textContent = result.data.nama;
            profileEmail.textContent = result.data.email;
        })
        .catch(() => {
            profileName.textContent = 'Profil tidak tersedia';
            profileEmail.textContent = '';
        });

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
