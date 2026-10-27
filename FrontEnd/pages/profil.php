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

function formatRating(float $nilai): string
{
    return number_format($nilai, 1, ',', '.');
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
    return is_file(__DIR__ . '/../../' . ltrim($path, '/')) ? '../../' . ltrim($path, '/') : '';
}

$pesan      = '';
$favorites  = [];
$fotoProfil = '';

$fotoEmail = trim((string) (
    $_SESSION['foto_google']
    ?? ($_SESSION['picture']
    ?? ($_SESSION['avatar_url']
    ?? ($_SESSION['user']['foto_google']
    ?? ($_SESSION['user']['picture'] ?? ''))))
));

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
        $nilai  = $t['average_rating'] ?? ($t['rating'] ?? null);
        $jumlah = $t['review_count'] ?? ($t['jumlah_ulasan'] ?? 0);
        return [
            $nilai !== null ? (float) $nilai : 0.0,
            (int) $jumlah,
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
        $dariDb = ambilKolomUser($conn, $userId, ['foto_profil', 'foto', 'avatar']);
        if ($dariDb !== '') {
            return $dariDb;
        }
        foreach (['foto_profil', 'foto'] as $kunci) {
            if (!empty($_SESSION[$kunci]) && is_string($_SESSION[$kunci])) {
                return trim($_SESSION[$kunci]);
            }
        }
        if (!empty($_SESSION['user']['foto_profil']) && is_string($_SESSION['user']['foto_profil'])) {
            return trim($_SESSION['user']['foto_profil']);
        }
        return '';
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
$emailFoto  = filter_var($emailUser, FILTER_VALIDATE_EMAIL) !== false ? $emailUser : (filter_var($identitasLogin, FILTER_VALIDATE_EMAIL) !== false ? $identitasLogin : '');

$srcFoto = srcGambar((string) $fotoProfil);
if ($srcFoto === '' && preg_match('#^https://#i', $fotoEmail) === 1) {
    $srcFoto = $fotoEmail;
}
if ($srcFoto === '' && $emailFoto !== '') {
    $srcFoto = 'https://www.gravatar.com/avatar/' . hash('sha256', strtolower($emailFoto)) . '?s=440&d=404';
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
    <link href="https://fonts.googleapis.com/css2?family=Kaushan+Script&family=Noto+Serif:wght@400;500;600;700&family=Courier+Prime:wght@400;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/edit-profil.css?v=20261031">
    <link rel="stylesheet" href="../CSS/theme.css?v=20261019-2">
    <link rel="stylesheet" href="../CSS/profil.css?v=25">
</head>
<body class="halaman-profil">
    <svg class="sprite" aria-hidden="true">
        <defs>
            <clipPath id="klip-dalam"><circle cx="110" cy="110" r="103"/></clipPath>
            <symbol id="ikon-profil" viewBox="0 0 220 220">
                <circle cx="110" cy="110" r="102" fill="none" stroke="#166d5c" stroke-width="15"/>
                <circle cx="110" cy="91" r="26" fill="none" stroke="#166d5c" stroke-width="13"/>
                <path d="M30 222C30 176 56 146 92 140C99 150 121 150 128 140C164 146 190 176 190 222" fill="none" stroke="#166d5c" stroke-width="13" stroke-linecap="round" stroke-linejoin="round" clip-path="url(#klip-dalam)"/>
            </symbol>
            <symbol id="i-bintang" viewBox="0 0 24 24">
                <path d="M12 1.8l3.1 6.6 7.2.9-5.3 4.9 1.4 7.1L12 17.7l-6.4 3.6 1.4-7.1L1.7 9.3l7.2-.9L12 1.8z"/>
            </symbol>
            <symbol id="i-lokasi" viewBox="0 0 30 34">
                <ellipse cx="15" cy="28.2" rx="15" ry="5.5" fill="#61de56"/>
                <path d="M15 22.7A15 5.5 0 0 1 15 33.7Z" fill="#13c37b"/>
                <path d="M15 30.7 5.87 17.14A11 11 0 1 1 24.13 17.14Z" fill="#fd3018"/>
                <path d="M15 0A11 11 0 0 1 24.13 17.14L15 30.7Z" fill="#e61e14"/>
                <circle cx="15" cy="11" r="5" fill="#fff"/>
            </symbol>
            <symbol id="i-pensil" viewBox="0 0 45 44">
                <path d="M19 9.7H7.4A5.7 5.7 0 0 0 1.7 15.4V36.3A5.7 5.7 0 0 0 7.4 42H28.8A5.7 5.7 0 0 0 34.5 36.3V25" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                <g transform="translate(33.2 11.3) rotate(45)" fill="currentColor">
                    <path d="M-5.3 0H5.3V26C5.3 30 2.4 34.6 0.9 37Q0 38.3-0.9 37C-2.4 34.6-5.3 30-5.3 26Z"/>
                    <path d="M-5.3-3.5V-8.1A5.3 5.3 0 0 1 5.3-8.1V-3.5Z"/>
                </g>
            </symbol>
            <symbol id="i-sampah" viewBox="0 0 34 34">
                <path fill="currentColor" fill-rule="evenodd" d="M12 0h10a1 1 0 0 1 1 1v5H11V1a1 1 0 0 1 1-1zM13.2 2.6h7.6v3.4h-7.6z"/>
                <rect x="0" y="4.8" width="33.8" height="2.5" rx="1.25" fill="currentColor"/>
                <path d="M3.8 6h26.2v25.6a1.7 1.7 0 0 1-1.7 1.7H5.5a1.7 1.7 0 0 1-1.7-1.7z" fill="currentColor"/>
                <rect x="11.5" y="11.8" width="3" height="14.8" rx="1.5" fill="#fff"/>
                <rect x="19" y="11.8" width="3" height="14.8" rx="1.5" fill="#fff"/>
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

    <div class="halaman">
        <aside class="sidebar" aria-label="Informasi profil">
            <div class="avatar" aria-hidden="true">
                <svg id="avatar-default" viewBox="0 0 220 220">
                    <circle cx="110" cy="110" r="110" fill="#d9d9d9"/>
                    <use href="#ikon-profil"/>
                </svg>
                <?php if ($srcFoto !== ''): ?>
                    <img class="avatar__foto" src="<?= e($srcFoto) ?>" alt="" referrerpolicy="no-referrer" onerror="this.remove()">
                <?php endif; ?>
            </div>
            <h1 class="nama">
                <span class="nama__teks" id="profile-name"><?= e($namaUser) ?></span>
                <a class="nama__edit" href="edit-profil.php" aria-label="Edit profil" title="Edit profil">
                    <svg viewBox="0 0 45 44" aria-hidden="true"><use href="#i-pensil"/></svg>
                </a>
            </h1>
            <p class="email" id="profile-email"><?= e($emailUser) ?></p>
            <nav class="menu-profil" aria-label="Menu profil">
                <div class="menu-profil__item">
                    <a class="menu-profil__ikon" href="../../index.php" aria-label="Beranda" title="Beranda">
                        <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="miter" aria-hidden="true">
                            <path d="M2.2 20.6 20 2.8 37.8 20.6"/>
                            <path d="M4.6 18.2V38.4H35.6V18.2"/>
                            <path d="M16.9 38.4V26H23.3V38.4"/>
                        </svg>
                    </a>
                    <span>Beranda</span>
                </div>
                <div class="menu-profil__item">
                    <a class="menu-profil__ikon" href="Edit-Password.php" aria-label="Ubah Password" title="Ubah Password">
                        <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="6.45" y="19.6" width="27.1" height="17.5" rx="3.8"/>
                            <path d="M11.2 19.6V11.7A8.8 8.8 0 0 1 28.8 11.7V19.6"/>
                            <path d="M20 25.3V31.4"/>
                        </svg>
                    </a>
                    <span>Ubah Password</span>
                </div>
                <form class="menu-profil__item menu-profil__item--logout" method="post" action="../../Backend/api/logout.php">
                    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                    <button type="submit" class="menu-profil__ikon" aria-label="Logout" title="Logout">
                        <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3.5 19.4 17.7 5.5V13.2H35.8Q36.6 13.2 36.6 14V24.3Q36.6 25.1 35.8 25.1H17.7V33.8Z"/>
                        </svg>
                    </button>
                    <span>Logout</span>
                </form>
            </nav>
        </aside>

        <section class="konten" aria-labelledby="judul-favorit" id="favorit">
            <h2 class="konten__judul" id="judul-favorit">Destinasi Favorit</h2>
            <?php if ($pesan !== ''): ?>
                <div class="notif" role="status"><?= e($pesan) ?></div>
            <?php endif; ?>
            <?php if ($favorites !== []): ?>
                <?php foreach ($favorites as $f):
                    $idWisata    = (string) ($f['id_wisata'] ?? '');
                    $nama        = (string) ($f['nama_wisata'] ?? '');
                    $srcKartu    = srcGambar((string) ($f['gambar'] ?? ''));
                    $rating      = (float) ($f['rating'] ?? 0);
                    $jumlah      = (int) ($f['jumlah_ulasan'] ?? 0);
                    $jumlahLabel = number_format($jumlah, 0, ',', '.');
                    $urlDetail   = $idWisata !== '' ? 'detail-wisata.php?id=' . rawurlencode($idWisata) : '';
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
                                <?php if ($jumlah > 0 && $rating > 0): ?>
                                    <span class="bintang" aria-hidden="true"><?= bintangHtml($rating) ?></span>
                                    <span class="rating__ulasan"><?= e(formatRating($rating)) ?> (<?= e($jumlahLabel) ?> ulasan)</span>
                                <?php elseif ($jumlah > 0): ?>
                                    <span class="rating__ulasan">(<?= e($jumlahLabel) ?> ulasan)</span>
                                <?php else: ?>
                                    <span class="rating__ulasan">Belum ada ulasan</span>
                                <?php endif; ?>
                            </div>
                            <div class="lokasi">
                                <svg class="lokasi__ikon" viewBox="0 0 30 34" aria-hidden="true"><use href="#i-lokasi"/></svg>
                                <p><?= wilayahHtml((string) ($f['wilayah'] ?? '')) ?></p>
                            </div>
                        </div>
                        <form class="hapus-form" method="post" action="profil.php" data-nama="<?= e($nama) ?>">
                            <input type="hidden" name="aksi" value="hapus_favorit">
                            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                            <input type="hidden" name="id_wisata" value="<?= e($idWisata) ?>">
                            <button type="submit" class="hapus" aria-label="Hapus <?= e($nama) ?> dari favorit" title="Hapus dari favorit">
                                <svg viewBox="0 0 34 34" aria-hidden="true"><use href="#i-sampah"/></svg>
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