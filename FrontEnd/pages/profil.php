<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
// true  = data contoh (tanpa database & tanpa login), hanya untuk melihat tampilan
// false = data asli: favorit diambil dari user yang sedang login
$modePreview = false;
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
function e($s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
/** Rating desimal -> bintang penuh + setengah bintang (dibulatkan ke 0,5 terdekat). */
function bintangHtml(float $nilai): string {
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
/** 1800 -> "1,8k" ; 250 -> "250" (seperti desain). */
function formatJumlah(int $n): string {
    if ($n < 1000) {
        return (string) $n;
    }
    $k = $n / 1000;
    $teks = number_format($k, $k < 10 ? 1 : 0, ',', '');
    return preg_replace('/,0$/', '', $teks) . 'k';
}
/** Alamat gambar yang bisa dipakai di <img>, atau '' jika file tidak ada. Dasar: folder proyek (../../). */
function srcGambar(string $path): string {
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }
    return is_file(__DIR__ . '/../../' . $path) ? '../../' . $path : '';
}
$pesan = '';
$favorites = [];
$fotoProfil = '';
if ($modePreview) {
    $namaUser  = 'Nama User';
    $emailUser = 'PerawatPeriwit124@gmail.com';
    $identitasLogin = $emailUser;
    $favorites = [
        ['id_wisata' => 'K00001', 'nama_wisata' => 'Curug Putri palutungan', 'wilayah' => 'Kabupaten Kuningan, Jawa Barat.', 'gambar' => 'FrontEnd/assets/images/CurugPutri.png', 'rating' => 4.5, 'jumlah_ulasan' => 1800],
        ['id_wisata' => 'K00002', 'nama_wisata' => 'Woodland',              'wilayah' => 'Kabupaten Kuningan, Jawa Barat.', 'gambar' => 'FrontEnd/assets/images/Woodland.png',   'rating' => 4.0, 'jumlah_ulasan' => 1800],
        ['id_wisata' => 'K00003', 'nama_wisata' => 'Arunika Eatry Garden',   'wilayah' => 'Kabupaten Kuningan, Jawa Barat.', 'gambar' => 'FrontEnd/assets/images/Arunika.png',   'rating' => 3.5, 'jumlah_ulasan' => 1800],
    ];
} else {
    require_once __DIR__ . '/../../Backend/database/koneksi.php';
    require_once __DIR__ . '/../../Backend/database/wisata_repository.php';
    /* ----- hanya untuk user yang sudah login ----- */
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
    /** Jalankan satu query favorit; null jika gagal (mis. kolom tidak ada). */
    function jalankanFavorit(mysqli $conn, string $sql, int $userId): ?array {
        try {
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return null;
            }
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            return null;
        }
    }
    /**
     * Destinasi favorit milik user ini (tabel `favorit` diisi tombol "Simpan Favorit" di detail-wisata.php).
     * Rating = rata-rata ulasan (desimal); jika belum ada ulasan memakai nilai di tabel wisata.
     */
    function ambilFavoritUser(mysqli $conn, int $userId): array {
        $dasar = 'FROM favorit f
                  JOIN wisata w ON w.id_wisata = f.Id_wisata
                  LEFT JOIN (SELECT Id_wisata, ROUND(AVG(rating), 1) AS rata, COUNT(*) AS jml
                             FROM ulasan GROUP BY Id_wisata) r ON r.Id_wisata = w.id_wisata
                  WHERE f.Id_user = ?
                  ORDER BY f.tanggal_ditambahkan DESC, w.nama_wisata ASC';
        $sqlA = 'SELECT w.id_wisata, w.nama_wisata, w.wilayah, w.gambar,
                        COALESCE(r.rata, w.rating, 0) AS rating,
                        IF(r.jml IS NULL, COALESCE(w.jumlah_ulasan, 0), r.jml) AS jumlah_ulasan ' . $dasar;
        $sqlB = 'SELECT w.id_wisata, w.nama_wisata, w.wilayah, w.gambar,
                        COALESCE(r.rata, 0) AS rating,
                        COALESCE(r.jml, 0) AS jumlah_ulasan ' . $dasar;
        $rows = jalankanFavorit($conn, $sqlA, $userId) ?? jalankanFavorit($conn, $sqlB, $userId);
        if ($rows !== null) {
            return $rows;
        }
        // cadangan terakhir: fungsi bawaan repository
        return function_exists('fetchUserTourismFavorites') ? fetchUserTourismFavorites($conn, $userId) : [];
    }
    /** Ambil satu kolom dari tabel user; mencoba beberapa nama tabel/kolom umum. '' jika tidak ketemu. */
    function ambilKolomUser(mysqli $conn, int $userId, array $kolomKandidat): string {
        foreach (['user', 'users', 'pengguna'] as $tabel) {
            foreach ($kolomKandidat as $kolom) {
                try {
                    $stmt = $conn->prepare("SELECT `$kolom` AS nilai FROM `$tabel` WHERE Id_user = ? LIMIT 1");
                    if (!$stmt) {
                        continue;
                    }
                    $stmt->bind_param('i',$userId);
                    $stmt->execute();$row = $stmt->get_result()->fetch_assoc();$stmt->close();
                    if ($row && trim((string)$row['nilai']) !== '') {
                        return trim((string) $row['nilai']);
                    }
                } catch (Throwable $e) {
                    continue;
                }
            }
        }
        return '';
    }
    /** Foto profil tersimpan: dari session, atau kolom foto_profil di tabel user. */
    function ambilFotoProfil(mysqli $conn, int$userId): string {
        foreach (['foto_profil', 'foto'] as $kunci) {
            if (!empty($_SESSION[$kunci]) && is_string($_SESSION[$kunci])) {
                return $_SESSION[$kunci];
            }
        }
        if (!empty($_SESSION['user']['foto_profil']) && is_string($_SESSION['user']['foto_profil'])) {
            return $_SESSION['user']['foto_profil'];
        }
        return ambilKolomUser($conn,$userId, ['foto_profil', 'foto', 'avatar']);
    }
    /* ----- klik ikon sampah: hapus dari destinasi favorit ----- */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus_favorit') {$csrfOk = isset($_POST['csrf'],$_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
        $idWisata = (string) ($_POST['id_wisata'] ?? '');
        if (!$csrfOk) {$_SESSION['pesan'] = 'Sesi tidak valid. Silakan coba lagi.';
        } elseif (preg_match('/^[KM][0-9]{5,}$/', $idWisata) !== 1) {$_SESSION['pesan'] = 'Destinasi tidak valid.';
        } else {
            removeTourismFavorite($conn,$userId, $idWisata);$_SESSION['pesan'] = 'Dihapus dari destinasi favorit.';
        }
        header('Location: profil.php');
        exit;
    }
    // Identitas saat login: email atau nomor HP.
    $identitasLogin = trim((string) ($_SESSION['email'] ?? ($_SESSION['user']['email'] ?? ($_SESSION['no_hp'] ?? ($_SESSION['telepon'] ?? '')))));$namaSesi = trim((string) ($_SESSION['nama'] ?? ($_SESSION['user']['nama'] ?? '')));
    if (strcasecmp($namaSesi, 'Nama User') === 0) {$namaSesi = '';
    }
    // Nama user diambil langsung dari database agar selalu sinkron dengan edit-profil.php
    $namaUser = ambilKolomUser($conn,$userId, ['nama', 'nama_lengkap', 'nama_user', 'username'])
        ?: ($namaSesi !== '' ? $namaSesi : 'Pengguna');$emailUser = $identitasLogin !== '' ?$identitasLogin : ambilKolomUser($conn,$userId, ['email', 'no_hp', 'telepon']);
    $favorites = ambilFavoritUser($conn, $userId);$fotoProfil = ambilFotoProfil($conn,$userId);
}
$pesan =$_SESSION['pesan'] ?? '';
unset($_SESSION['pesan']);

/* ===== Logika Penentuan Avatar =====
   - Jika login pakai EMAIL: Tampilkan foto profil tersimpan atau inisial huruf pertama dari email.
   - Jika login pakai NOMOR HP: Gunakan ikon siluet profil anonim bawaan (tanpa inisial huruf). */
$namaUser   = (string)$namaUser;
$emailUser  = trim((string)$emailUser);
$loginEmail = filter_var($identitasLogin, FILTER_VALIDATE_EMAIL) !== false;
$inisial    = '';$srcFoto    = '';
if ($loginEmail) {
    $bagianLokal = (string) strstr($identitasLogin, '@', true);
    if (preg_match('/[\p{L}\p{N}]/u', $bagianLokal,$cocok) === 1) {
        $inisial = mb_strtoupper($cocok[0], 'UTF-8');
    }
    $srcFoto = srcGambar((string)$fotoProfil);
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
    <link href="https://fonts.googleapis.com/css2?family=Kaushan+Script&family=Noto+Serif:wght@400;500;600;700&family=Courier+Prime:wght@400;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/edit-profil.css?v=20261018">
    <link rel="stylesheet" href="../CSS/profil.css?v=11">
    <link rel="stylesheet" href="../CSS/theme.css?v=20261019-2">
</head>
<body class="halaman-profil">
    <!-- Sprite ikon -->
    <svg class="sprite" aria-hidden="true">
        <defs>
            <clipPath id="klip-avatar"><circle cx="110" cy="110" r="110"/></clipPath>
            <symbol id="ikon-profil" viewBox="0 0 220 220">
                <circle cx="110" cy="110" r="102.5" fill="none" stroke="#166d5c" stroke-width="15"/>
                <circle cx="110" cy="83" r="31" fill="#166d5c"/>
                <path d="M38 211c2-39 23-66 57-75 9 9 19 13 30 13s21-4 30-13c34 9 55 36 57 75z" fill="#166d5c"/>
            </symbol>
            <symbol id="i-bintang" viewBox="0 0 24 24">
                <path d="M12 1.8l3.1 6.6 7.2.9-5.3 4.9 1.4 7.1L12 17.7l-6.4 3.6 1.4-7.1L1.7 9.3l7.2-.9L12 1.8z"/>
            </symbol>
            <symbol id="i-lokasi" viewBox="0 0 40 44">
                <ellipse cx="20" cy="36" rx="13" ry="5" fill="#2fbf6b"/>
                <path d="M20 3C13.4 3 9 7.9 9 13.8c0 7.2 8.6 14.6 11 19.2 2.4-4.6 11-12 11-19.2C31 7.9 26.6 3 20 3z" fill="#f2301d"/>
                <circle cx="20" cy="13.5" r="4.8" fill="#fff"/>
            </symbol>
            <symbol id="i-pensil" viewBox="0 0 512 512">
                <path fill="currentColor" d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160V416c0 53 43 96 96 96H352c53 0 96-43 96-96V320c0-17.7-14.3-32-32-32s-32 14.3-32 32v96c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h96c17.7 0 32-14.3 32-32s-14.3-32-32-32H96z"/>
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
    <!-- Navbar -->
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
                <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round">
                    <circle cx="16" cy="16" r="12"/>
                    <line x1="25" y1="25" x2="37" y2="37"/>
                </svg>
            </a>
            <a class="ep-ikon ep-ikon--profil" href="profil.php" aria-label="Profil">
                <svg viewBox="0 0 220 220"><use href="#ikon-profil"/></svg>
            </a>
        </div>
        <ul class="kat-menu" id="kategori-menu">
            <li><a class="kat-item" href="kategori.php?jenis=alam">Alam</a></li>
            <li><a class="kat-item" href="kategori.php?jenis=air-curug">Air & Curug</a></li>
            <li><a class="kat-item" href="kategori.php?jenis=buatan-kuliner">Buatan & Kuliner</a></li>
            <li><a class="kat-item" href="kategori.php?jenis=sejarah-budaya">Sejarah & Budaya</a></li>
        </ul>
    </header>
    <div class="halaman">
        <!-- Sidebar -->
        <aside class="sidebar" aria-label="Informasi profil">
            <div class="avatar" aria-hidden="true">
                <svg viewBox="0 0 220 220">
                    <circle cx="110" cy="110" r="110" fill="#d9d9d9"/>
                    <?php if ($loginEmail &&$inisial !== ''): ?>
                        <circle cx="110" cy="110" r="102.5" fill="none" stroke="currentColor" stroke-width="15"/>
                        <text class="avatar__inisial" x="110" y="113" text-anchor="middle" dominant-baseline="central" fill="currentColor"><?= e($inisial) ?></text>
                    <?php else: ?>
                        <use href="#ikon-profil"/>
                    <?php endif; ?>
                </svg>
                <?php if ($loginEmail &&$srcFoto !== ''): ?>
                    <img class="avatar__foto" src="<?= e($srcFoto) ?>" alt=""
                         referrerpolicy="no-referrer" onerror="this.remove()">
                <?php endif; ?>
            </div>
            <h1 class="nama">
                <span id="profile-name"><?= e($namaUser) ?></span>
                <a class="nama__edit" href="edit-profil.php" aria-label="Edit profil" title="Edit profil">
                    <svg viewBox="0 0 48 48"><use href="#i-pensil"/></svg>
                </a>
            </h1>
            <p class="email" id="profile-email"><?= e($emailUser) ?></p>
            <nav class="menu-profil" aria-label="Menu profil">
                <a href="../../index.php">
                    <svg viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m2 13 12-10 12 10M5 11v14h18V11M11 25v-9h6v9"/>
                    </svg>
                    <span>Beranda</span>
                </a>
                <a href="#favorit">
                    <svg viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 24S3 17.2 3 9.8A5.8 5.8 0 0 1 14 7.2a5.8 5.8 0 0 1 11 2.6C25 17.2 14 24 14 24Z"/>
                    </svg>
                    <span>Destinasi Favorit</span>
                </a>
                <a href="../auth/Login.php">
                    <svg viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M11 5 3 13l8 8v-5h14v-6H11V5Z"/>
                    </svg>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>
        <!-- Konten: daftar favorit -->
        <section class="konten" aria-labelledby="judul-favorit" id="favorit">
            <h2 class="konten__judul" id="judul-favorit">Destinasi Favorit</h2>
            <?php if ($pesan !== ''): ?>
                <div class="notif" role="status"><?= e($pesan) ?></div>
            <?php endif; ?>
            <?php if ($favorites !== []): ?>
                <?php foreach ($favorites as$i => $f):$idWisata  = (string) ($f['id_wisata'] ?? '');$nama      = (string) ($f['nama_wisata'] ?? '');$srcKartu  = srcGambar((string) ($f['gambar'] ?? ''));$rating    = (float) ($f['rating'] ?? 0);$jumlah    = (int) ($f['jumlah_ulasan'] ?? 0);$urlDetail = $idWisata !== '' ? 'detail-wisata.php?id=' . rawurlencode($idWisata) : '';
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
                                <?php if ($rating > 0 &&$jumlah > 0): ?>
                                    <span class="bintang" role="img"
                                          aria-label="Rating <?= e(number_format($rating, 1, ',', '.')) ?> dari 5"><?= bintangHtml($rating) ?></span>
                                    <span class="rating__ulasan">(<?= e(formatJumlah($jumlah)) ?> ulasan)</span>
                                <?php else: ?>
                                    <span class="rating__ulasan">Belum ada ulasan</span>
                                <?php endif; ?>
                            </div>
                            <div class="lokasi">
                                <svg class="lokasi__ikon" viewBox="0 0 40 44" aria-hidden="true"><use href="#i-lokasi"/></svg>
                                <p><?= e($f['wilayah'] ?? '') ?></p>
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
        // Dropdown kategori
        (function () {
            var toggle = document.getElementById('kategori-toggle');
            var menu = document.getElementById('kategori-menu');
            function buka() {
                var r = toggle.getBoundingClientRect();
                menu.style.top = (r.bottom + 12) + 'px';
                menu.style.left = r.left + 'px';
                menu.classList.add('show');
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
        // Konfirmasi sebelum menghapus dari favorit
        document.querySelectorAll('.hapus-form').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                var nama = form.getAttribute('data-nama') || 'destinasi ini';
                if (!window.confirm('Hapus "' + nama + '" dari destinasi favorit?')) {
                    e.preventDefault();
                }
            });
        });
        // Sembunyikan notifikasi setelah beberapa detik
        document.querySelectorAll('.notif').forEach(function (n) {
            setTimeout(function () { n.classList.add('hilang'); }, 4000);
        });
    </script>
</body>
</html>