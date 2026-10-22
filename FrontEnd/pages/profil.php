<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

$modePreview = false;

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

function e($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function bintangHtml(float $nilai): string
{
    $nilai    = max(0.0, min(5.0, $nilai));
    $dibulat  = round($nilai * 2) / 2;
    $penuh    = (int) floor($dibulat);
    $setengah = ($dibulat - $penuh) >= 0.5;
    $html = str_repeat('<svg viewBox="0 0 24 24"><use href="#i-bintang"/></svg>', $penuh);
    if ($setengah) {
        $html .= '<svg class="setengah" viewBox="0 0 12 24"><use href="#i-bintang" width="24" height="24"/></svg>';
    }
    return $html;
}

function formatJumlah(int $n): string
{
    if ($n < 1000) {
        return (string) $n;
    }
    $k    = $n / 1000;
    $teks = number_format($k, $k < 10 ? 1 : 0, ',', '');
    return preg_replace('/,0$/', '', $teks) . 'k';
}

function wilayahHtml(string $wilayah): string
{
    $aman = e(trim($wilayah));
    return preg_replace('/^(Kabupaten|Kota)\s+/u', '$1<br>', $aman, 1);
}

function srcGambar(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }
    return is_file(__DIR__ . '/../../' . $path) ? '../../' . $path : '';
}

$pesan      = '';
$favorites  = [];
$fotoProfil = '';

if ($modePreview) {
    $namaUser       = 'Nama User';
    $emailUser      = 'PerawatPeriwit124@gmail.com';
    $identitasLogin = $emailUser;
    $favorites = [
        ['id_wisata' => 'K00001', 'nama_wisata' => 'Curug Putri palutungan', 'wilayah' => 'Kabupaten Kuningan, Jawa Barat.', 'gambar' => 'FrontEnd/assets/images/CurugPutri.png', 'rating' => 4.5, 'jumlah_ulasan' => 1800],
        ['id_wisata' => 'K00002', 'nama_wisata' => 'Woodland', 'wilayah' => 'Kabupaten Kuningan, Jawa Barat.', 'gambar' => 'FrontEnd/assets/images/Woodland.png', 'rating' => 4.0, 'jumlah_ulasan' => 1800],
        ['id_wisata' => 'K00003', 'nama_wisata' => 'Arunika Eatry Garden', 'wilayah' => 'Kabupaten Kuningan, Jawa Barat.', 'gambar' => 'FrontEnd/assets/images/Arunika.png', 'rating' => 3.5, 'jumlah_ulasan' => 1800],
    ];
} else {
    require_once __DIR__ . '/../../Backend/database/koneksi.php';
    require_once __DIR__ . '/../../Backend/database/wisata_repository.php';

    $userId = filter_var($_SESSION['id_user'] ?? null, FILTER_VALIDATE_INT);
    if ($userId === false || $userId <= 0) {
        header('Location: ../auth/Login.php');
        exit;
    }
    $userId = (int) $userId;

    if (!function_exists('removeTourismFavorite')) {
        function removeTourismFavorite(mysqli $conn, int $userId, string $tourismId): void
        {
            $stmt = $conn->prepare('DELETE FROM favorit WHERE Id_user = ? AND Id_wisata = ?');
            $stmt->bind_param('is', $userId, $tourismId);
            $stmt->execute();
            $stmt->close();
        }
    }

    function hitungRatingWisata(mysqli $conn, string $tourismId): array
    {
        $reviewRows = fetchTourismReviews($conn, $tourismId);
        if (count($reviewRows) > 0) {
            $total = 0.0;
            foreach ($reviewRows as $r) {
                $total += (float) $r['rating'];
            }
            $jumlah = count($reviewRows);
            return [round($total / $jumlah, 1), $jumlah];
        }
        $rows = fetchTourismRows($conn, ['id_wisata' => $tourismId]);
        $t    = $rows[0] ?? null;
        if ($t === null) {
            return [0.0, 0];
        }
        return [
            ($t['rating'] ?? null) !== null ? (float) $t['rating'] : 0.0,
            (int) ($t['jumlah_ulasan'] ?? 0),
        ];
    }

    function ambilFavoritUser(mysqli $conn, int $userId): array
    {
        $sql = 'SELECT w.id_wisata, w.nama_wisata, w.wilayah, w.gambar
                FROM favorit f
                JOIN wisata w ON w.id_wisata = f.Id_wisata
                WHERE f.Id_user = ?
                ORDER BY f.tanggal_ditambahkan DESC, w.nama_wisata ASC';
        $rows = null;
        try {
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            }
        } catch (Throwable $e) {
            $rows = null;
        }
        if ($rows === null) {
            $rows = function_exists('fetchUserTourismFavorites') ? fetchUserTourismFavorites($conn, $userId) : [];
        }
        foreach ($rows as $i => $row) {
            [$nilai, $jumlah] = hitungRatingWisata($conn, (string) ($row['id_wisata'] ?? ''));
            $rows[$i]['rating']        = $nilai;
            $rows[$i]['jumlah_ulasan'] = $jumlah;
        }
        return $rows;
    }

    function ambilKolomUser(mysqli $conn, int $userId, array $kolomKandidat): string
    {
        foreach (['user', 'users', 'pengguna'] as $tabel) {
            foreach ($kolomKandidat as $kolom) {
                try {
                    $stmt = $conn->prepare("SELECT `$kolom` AS nilai FROM `$tabel` WHERE Id_user = ? LIMIT 1");
                    if (!$stmt) {
                        continue;
                    }
                    $stmt->bind_param('i', $userId);
                    $stmt->execute();
                    $row = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    if ($row && trim((string) $row['nilai']) !== '') {
                        return trim((string) $row['nilai']);
                    }
                } catch (Throwable $e) {
                    continue;
                }
            }
        }
        return '';
    }

    function ambilFotoProfil(mysqli $conn, int $userId): string
    {
        foreach (['foto_profil', 'foto'] as $kunci) {
            if (!empty($_SESSION[$kunci]) && is_string($_SESSION[$kunci])) {
                return $_SESSION[$kunci];
            }
        }
        if (!empty($_SESSION['user']['foto_profil']) && is_string($_SESSION['user']['foto_profil'])) {
            return $_SESSION['user']['foto_profil'];
        }
        return ambilKolomUser($conn, $userId, ['foto_profil', 'foto', 'avatar']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus_favorit') {
        $csrfOk   = isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
        $idWisata = (string) ($_POST['id_wisata'] ?? '');
        if (!$csrfOk) {
            $_SESSION['pesan'] = 'Sesi tidak valid. Silakan coba lagi.';
        } elseif (preg_match('/^[KM][0-9]{5,}$/', $idWisata) !== 1) {
            $_SESSION['pesan'] = 'Destinasi tidak valid.';
        } else {
            removeTourismFavorite($conn, $userId, $idWisata);
            $_SESSION['pesan'] = 'Dihapus dari destinasi favorit.';
        }
        header('Location: profil.php');
        exit;
    }

    $identitasLogin = trim((string) ($_SESSION['email'] ?? ($_SESSION['user']['email'] ?? ($_SESSION['no_hp'] ?? ($_SESSION['telepon'] ?? '')))));
    $namaSesi       = trim((string) ($_SESSION['nama'] ?? ($_SESSION['user']['nama'] ?? '')));
    if (strcasecmp($namaSesi, 'Nama User') === 0) {
        $namaSesi = '';
    }

    $namaUser = ambilKolomUser($conn, $userId, ['nama', 'nama_lengkap', 'nama_user', 'username'])
        ?: ($namaSesi !== '' ? $namaSesi : 'Pengguna');
    $emailUser = $identitasLogin !== '' ? $identitasLogin : ambilKolomUser($conn, $userId, ['email', 'no_hp', 'telepon']);

    $favorites  = ambilFavoritUser($conn, $userId);
    $fotoProfil = ambilFotoProfil($conn, $userId);
}

$pesan = $_SESSION['pesan'] ?? '';
unset($_SESSION['pesan']);

$namaUser   = (string) $namaUser;
$emailUser  = trim((string) $emailUser);
$loginEmail = filter_var($identitasLogin, FILTER_VALIDATE_EMAIL) !== false;
$inisial    = '';
$srcFoto    = '';

if ($loginEmail) {
    $bagianLokal = (string) strstr($identitasLogin, '@', true);
    if (preg_match('/[\p{L}\p{N}]/u', $bagianLokal, $cocok) === 1) {
        $inisial = mb_strtoupper($cocok[0], 'UTF-8');
    }
    $srcFoto = srcGambar((string) $fotoProfil);
    if ($srcFoto === '') {
        $srcFoto = 'https://www.gravatar.com/avatar/' . hash('sha256', strtolower($identitasLogin)) . '?s=440&d=404';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil | Explore Majaku</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fragment+Mono:ital@0;1&family=Frank+Ruhl+Libre:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=Kaushan+Script&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/edit-profil.css?v=shared-layout-4">
    <link rel="stylesheet" href="../CSS/profil.css?v=shared-layout-4">
    <link rel="stylesheet" href="../CSS/theme.css?v=shared-layout-4">
</head>
<body class="halaman-profil">
    <svg class="sprite" aria-hidden="true">
        <defs>
            <clipPath id="klip-dalam"><circle cx="110" cy="110" r="103"/></clipPath>
            <symbol id="ikon-profil" viewBox="0 0 220 220">
                <circle cx="110" cy="110" r="93.5" fill="none" stroke="#166d5c" stroke-width="11"/>
                <circle cx="110" cy="82.5" r="27.5" fill="none" stroke="#166d5c" stroke-width="11"/>
                <path d="M49.5 170.5c9.35-27.5 30.25-41.25 60.5-41.25s51.15 13.75 60.5 41.25" fill="none" stroke="#166d5c" stroke-width="11" stroke-linecap="round" stroke-linejoin="round"/>
            </symbol>
            <symbol id="i-bintang" viewBox="0 0 24 24">
                <path d="M12 1.8l3.1 6.6 7.2.9-5.3 4.9 1.4 7.1L12 17.7l-6.4 3.6 1.4-7.1L1.7 9.3l7.2-.9L12 1.8z"/>
            </symbol>
            <symbol id="i-lokasi" viewBox="0 0 40 44">
                <ellipse cx="20" cy="37" rx="14" ry="5" fill="#45d84f"/>
                <path d="M20 32a14 5 0 0 1 0 10z" fill="#10b981"/>
                <path d="M20 3C13.4 3 9 7.9 9 13.8c0 7.2 8.6 14.6 11 19.2 2.4-4.6 11-12 11-19.2C31 7.9 26.6 3 20 3z" fill="#ed2118"/>
                <circle cx="20" cy="13.5" r="4.8" fill="#fff"/>
            </symbol>
            <symbol id="i-pensil" viewBox="0 0 48 48">
                <path d="M23 7H12A6 6 0 0 0 6 13V36A6 6 0 0 0 12 42H35A6 6 0 0 0 41 36V26" fill="none" stroke="currentColor" stroke-width="4.4" stroke-linecap="round" stroke-linejoin="round"/>
                <g transform="translate(27.5 20.5) rotate(45)">
                    <path d="M-5 -19a5 5 0 0 1 10 0V15L0 26-5 15Z" fill="currentColor" stroke="#fff" stroke-width="3.4" stroke-linejoin="round" paint-order="stroke"/>
                    <path d="M-5 -12H5" fill="none" stroke="#fff" stroke-width="2.2"/>
                </g>
            </symbol>
            <symbol id="i-sampah" viewBox="0 0 32 32">
                <rect x="11.5" y="1" width="9" height="3.6" rx="1.2" fill="currentColor"/>
                <rect x="2.5" y="5" width="27" height="4.6" rx="1.3" fill="currentColor"/>
                <path d="M5.5 11.3h21v16.2a3 3 0 0 1-3 3h-15a3 3 0 0 1-3-3z" fill="currentColor"/>
                <path d="M12 15v11.5M20 15v11.5" fill="none" stroke="#fff" stroke-width="2.4"/>
            </symbol>
            <symbol id="i-foto" viewBox="0 0 64 56">
                <rect x="3" y="3" width="58" height="50" rx="8" fill="none" stroke="currentColor" stroke-width="6"/>
                <circle cx="22" cy="20" r="5.5" fill="currentColor"/>
                <path d="M9 47 24 30 33 40 43 27 55 47Z" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
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
                <a class="ep-menu__tentang" href="tentang.php">Tentang</a>
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
            <li><a class="kat-item" href="kategori.php?kategori=alam">Wisata Alam &amp; Pegunungan</a></li>
            <li><a class="kat-item" href="kategori.php?kategori=air-curug">Wisata Air &amp; Curug</a></li>
            <li><a class="kat-item" href="kategori.php?kategori=buatan-kuliner">Wisata Buatan &amp; Kuliner</a></li>
            <li><a class="kat-item" href="kategori.php?kategori=sejarah-budaya">Wisata Sejarah &amp; Budaya</a></li>
        </ul>
    </header>

    <div class="halaman">
        <aside class="sidebar" aria-label="Informasi profil">
            <div class="avatar" aria-hidden="true">
                <svg viewBox="0 0 220 220">
                    <circle cx="110" cy="110" r="110" fill="#d9d9d9"/>
                    <?php if ($loginEmail && $inisial !== ''): ?>
                        <circle cx="110" cy="110" r="102.5" fill="none" stroke="currentColor" stroke-width="15"/>
                        <text class="avatar__inisial" x="110" y="113" text-anchor="middle" dominant-baseline="central" fill="currentColor"><?= e($inisial) ?></text>
                    <?php else: ?>
                        <use href="#ikon-profil"/>
                    <?php endif; ?>
                </svg>
                <?php if ($loginEmail && $srcFoto !== ''): ?>
                    <img class="avatar__foto" src="<?= e($srcFoto) ?>" alt="" referrerpolicy="no-referrer" onerror="this.remove()">
                <?php endif; ?>
            </div>
            <h1 class="nama">
                <span class="nama__teks" id="profile-name"><?= e($namaUser) ?></span>
                <a class="nama__edit" href="edit-profil.php" aria-label="Edit profil" title="Edit profil">
                    <svg viewBox="0 0 48 48" aria-hidden="true"><use href="#i-pensil"/></svg>
                </a>
            </h1>
            <p class="email" id="profile-email"><?= e($emailUser) ?></p>
            <nav class="menu-profil" aria-label="Menu profil">
                <div class="menu-profil__item">
                    <a class="menu-profil__ikon" href="../../index.php" aria-label="Beranda" title="Beranda">
                        <svg viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m2 13 12-10 12 10M5 11v14h18V11M11 25v-9h6v9"/>
                        </svg>
                    </a>
                    <span>Beranda</span>
                </div>
                <div class="menu-profil__item menu-profil__item--logout">
                    <a class="menu-profil__ikon" href="../auth/Login.php" aria-label="Logout" title="Logout">
                        <svg viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 3 2 14l10 11v-6.5h14v-9H12V3Z"/>
                        </svg>
                    </a>
                    <span>Logout</span>
                </div>
            </nav>
        </aside>

        <section class="konten" aria-labelledby="judul-favorit" id="favorit">
            <h2 class="konten__judul" id="judul-favorit">Destinasi Favorit</h2>
            <?php if ($pesan !== ''): ?>
                <div class="notif" role="status"><?= e($pesan) ?></div>
            <?php endif; ?>
            <?php if ($favorites !== []): ?>
                <?php foreach ($favorites as $f):
                    $idWisata  = (string) ($f['id_wisata'] ?? '');
                    $nama      = (string) ($f['nama_wisata'] ?? '');
                    $srcKartu  = srcGambar((string) ($f['gambar'] ?? ''));
                    $rating    = (float) ($f['rating'] ?? 0);
                    $jumlah    = (int) ($f['jumlah_ulasan'] ?? 0);
                    $urlDetail = $idWisata !== '' ? 'detail-wisata.php?id=' . rawurlencode($idWisata) : '';
                ?>
                    <article class="kartu" aria-label="<?= e($nama) ?>">
                        <span class="kartu__tautan">
                            <?php if ($srcKartu !== ''): ?>
                                <img class="kartu__foto" src="<?= e($srcKartu) ?>" alt="<?= e($nama) ?>">
                            <?php else: ?>
                                <span class="kartu__foto kartu__foto--kosong" role="img" aria-label="Foto tidak tersedia">
                                    <svg viewBox="0 0 64 56" aria-hidden="true"><use href="#i-foto"/></svg>
                                    <span>Foto Tidak Tersedia</span>
                                </span>
                            <?php endif; ?>
                        </span>
                        <div class="kartu__isi">
                            <h3 class="kartu__judul">
                                <?php if ($urlDetail !== ''): ?>
                                    <a href="<?= e($urlDetail) ?>"><?= e($nama) ?></a>
                                <?php else: ?>
                                    <?= e($nama) ?>
                                <?php endif; ?>
                            </h3>
                            <div class="rating">
                                <?php if ($rating > 0): ?>
                                    <span class="bintang" role="img" aria-label="Rating <?= e(number_format($rating, 1, ',', '.')) ?> dari 5"><?= bintangHtml($rating) ?></span>
                                    <span class="rating__ulasan">(<?= e(formatJumlah($jumlah)) ?> ulasan)</span>
                                <?php else: ?>
                                    <span class="rating__ulasan">Belum ada ulasan</span>
                                <?php endif; ?>
                            </div>
                            <div class="lokasi">
                                <svg class="lokasi__ikon" viewBox="0 0 40 44" aria-hidden="true"><use href="#i-lokasi"/></svg>
                                <p><?= wilayahHtml((string) ($f['wilayah'] ?? '')) ?></p>
                            </div>
                        </div>
                        <form class="hapus-form" method="post" action="profil.php" data-nama="<?= e($nama) ?>">
                            <input type="hidden" name="aksi" value="hapus_favorit">
                            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                            <input type="hidden" name="id_wisata" value="<?= e($idWisata) ?>">
                            <button type="submit" class="hapus" aria-label="Hapus <?= e($nama) ?> dari favorit" title="Hapus dari favorit">
                                <svg viewBox="0 0 32 32" aria-hidden="true"><use href="#i-sampah"/></svg>
                            </button>
                        </form>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="kosong">Belum ada destinasi favorit.</p>
            <?php endif; ?>
        </section>
    </div>

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
        })();

        document.querySelectorAll('.hapus-form').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                var nama = form.getAttribute('data-nama') || 'destinasi ini';
                if (!window.confirm('Hapus "' + nama + '" dari destinasi favorit?')) {
                    e.preventDefault();
                }
            });
        });

        document.querySelectorAll('.notif').forEach(function (n) {
            setTimeout(function () { n.classList.add('hilang'); }, 4000);
        });
    </script>
</body>
</html>