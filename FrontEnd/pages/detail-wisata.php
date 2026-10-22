<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

function e($s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function nilaiAtauKet($nilai, string $keterangan = 'Belum Tersedia'): string {
    $teks = trim((string) $nilai);
    return $teks !== ''
        ? e($teks)
        : '<span class="belum">' . e($keterangan) . '</span>';
}

function waktuRelatif(string $tanggal): string {
    $t = strtotime($tanggal);
    if (!$t) return '';
    $hari = intdiv(max(0, time() - $t), 86400);
    if ($hari < 1)   return 'Hari ini';
    if ($hari < 7)   return $hari . ' hari yang lalu';
    if ($hari < 30)  return intdiv($hari, 7) . ' minggu yang lalu';
    if ($hari < 365) return intdiv($hari, 30) . ' bulan yang lalu';
    return intdiv($hari, 365) . ' tahun yang lalu';
}

function formatRating(float $nilai): string {
    return number_format($nilai, 1, ',', '.');
}

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

require_once __DIR__ . '/../../Backend/database/koneksi.php';
require_once __DIR__ . '/../../Backend/database/wisata_repository.php';

function fetchTourismParking(mysqli $conn, string $tourismId): array {
    try {
        $stmt = $conn->prepare('SELECT parkir_motor, parkir_mobil FROM wisata WHERE id_wisata = ? LIMIT 1');
        $stmt->bind_param('s', $tourismId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();
    } catch (Throwable $e) {
        return [];
    }
    $baris = [];
    if (!empty($row['parkir_motor'])) {
        $baris[] = 'Rp ' . number_format((int) $row['parkir_motor'], 0, ',', '.') . '/Motor';
    }
    if (!empty($row['parkir_mobil'])) {
        $baris[] = 'Rp ' . number_format((int) $row['parkir_mobil'], 0, ',', '.') . '/Mobil';
    }
    return $baris;
}

function simpanUlasan(mysqli $conn, int $userId, string $tourismId, float $rating, string $komentar): bool
{
    try {
        $stmt = $conn->prepare('SELECT Id_ulasan FROM ulasan WHERE Id_user = ? AND Id_wisata = ? LIMIT 1');
        $stmt->bind_param('is', $userId, $tourismId);
        $stmt->execute();
        $ada = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($ada) {
            $idUlasan = (int) $ada['Id_ulasan'];
            $stmt = $conn->prepare('UPDATE ulasan SET rating = ?, komentar = ?, tanggal_ulasan = CURDATE() WHERE Id_ulasan = ?');
            $stmt->bind_param('dsi', $rating, $komentar, $idUlasan);
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO ulasan (Id_user, Id_wisata, rating, komentar, tanggal_ulasan) VALUES (?, ?, ?, ?, CURDATE())'
            );
            $stmt->bind_param('isds', $userId, $tourismId, $rating, $komentar);
        }
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    } catch (Throwable $e) {
        return false;
    }
}

function perbaruiRatingWisata(mysqli $conn, string $tourismId): void
{
    try {
        $stmt = $conn->prepare(
            'UPDATE wisata SET rating = (SELECT ROUND(AVG(rating), 1) FROM ulasan WHERE Id_wisata = ?) WHERE id_wisata = ?'
        );
        $stmt->bind_param('ss', $tourismId, $tourismId);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
    }
    try {
        $stmt = $conn->prepare(
            'UPDATE wisata SET jumlah_ulasan = (SELECT COUNT(*) FROM ulasan WHERE Id_wisata = ?) WHERE id_wisata = ?'
        );
        $stmt->bind_param('ss', $tourismId, $tourismId);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
    }
}

if (!function_exists('isTourismFavorite')) {
    function isTourismFavorite(mysqli $conn, int $userId, string $tourismId): bool
    {
        $stmt = $conn->prepare('SELECT 1 FROM favorit WHERE Id_user = ? AND Id_wisata = ? LIMIT 1');
        $stmt->bind_param('is', $userId, $tourismId);
        $stmt->execute();
        $found = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $found;
    }
}

if (!function_exists('addTourismFavorite')) {
    function addTourismFavorite(mysqli $conn, int $userId, string $tourismId): void
    {
        $stmt = $conn->prepare(
            'INSERT IGNORE INTO favorit (Id_user, Id_wisata, tanggal_ditambahkan) VALUES (?, ?, CURDATE())'
        );
        $stmt->bind_param('is', $userId, $tourismId);
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('removeTourismFavorite')) {
    function removeTourismFavorite(mysqli $conn, int $userId, string $tourismId): void
    {
        $stmt = $conn->prepare('DELETE FROM favorit WHERE Id_user = ? AND Id_wisata = ?');
        $stmt->bind_param('is', $userId, $tourismId);
        $stmt->execute();
        $stmt->close();
    }
}

$tourismId = $_GET['id'] ?? '';
if (!is_string($tourismId) || preg_match('/^[KM][0-9]{5,}$/', $tourismId) !== 1) {
    http_response_code(404);
    exit('Destinasi tidak ditemukan.');
}

$userId = filter_var($_SESSION['id_user'] ?? null, FILTER_VALIDATE_INT);
$sudahLogin = $userId !== false && $userId > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'toggle_favorit') {
    if (!$sudahLogin) {
        header('Location: ../auth/Login.php');
        exit;
    }

    if (isTourismFavorite($conn, (int) $userId, $tourismId)) {
        removeTourismFavorite($conn, (int) $userId, $tourismId);
        $_SESSION['pesan'] = 'Dihapus dari destinasi favorit.';
    } else {
        addTourismFavorite($conn, (int) $userId, $tourismId);
        $_SESSION['pesan'] = 'Berhasil ditambahkan ke destinasi favorit.';
    }

    header('Location: detail-wisata.php?id=' . rawurlencode($tourismId));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'tambah_ulasan') {
    if (!$sudahLogin) {
        header('Location: ../auth/Login.php');
        exit;
    }

    $csrfOk = isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);

    $ratingMentah = str_replace(',', '.', trim((string) ($_POST['rating'] ?? '')));
    $ratingIn     = filter_var($ratingMentah, FILTER_VALIDATE_FLOAT);
    $ratingValid  = $ratingIn !== false
        && $ratingIn >= 0.5
        && $ratingIn <= 5
        && abs($ratingIn * 2 - round($ratingIn * 2)) < 0.0001;

    $komentarIn = mb_substr(trim((string) ($_POST['komentar'] ?? '')), 0, 500, 'UTF-8');

    if (!$csrfOk) {
        $_SESSION['pesan_ulasan'] = 'Sesi tidak valid. Silakan coba lagi.';
    } elseif (!$ratingValid) {
        $_SESSION['pesan_ulasan'] = 'Pilih rating 0,5 sampai 5 bintang.';
    } elseif ($komentarIn === '') {
        $_SESSION['pesan_ulasan'] = 'Ulasan tidak boleh kosong.';
    } elseif (simpanUlasan($conn, (int) $userId, $tourismId, (float) $ratingIn, $komentarIn)) {
        perbaruiRatingWisata($conn, $tourismId);
        $_SESSION['pesan_ulasan'] = 'Terima kasih! Ulasanmu berhasil disimpan.';
    } else {
        $_SESSION['pesan_ulasan'] = 'Ulasan gagal disimpan. Silakan coba lagi.';
    }

    header('Location: detail-wisata.php?id=' . rawurlencode($tourismId) . '#judul-ulasan');
    exit;
}

$tourismRows = fetchTourismRows($conn, ['id_wisata' => $tourismId]);
$tourism = $tourismRows[0] ?? null;
if ($tourism === null) {
    http_response_code(404);
    exit('Destinasi tidak ditemukan.');
}

$facilities = fetchTourismFacilities($conn, $tourismId);
$reviewRows = fetchTourismReviews($conn, $tourismId);
$ratingCounts = array_fill(1, 5, 0);
foreach ($reviewRows as $review) {
    $rounded = max(1, min(5, (int) round((float) $review['rating'])));
    $ratingCounts[$rounded]++;
}

$distribution = [];
foreach ([5, 4, 3, 2, 1] as $stars) {
    $distribution[] = [
        'stars' => $stars,
        'percentage' => count($reviewRows) > 0
            ? (int) round($ratingCounts[$stars] / count($reviewRows) * 100)
            : 0,
    ];
}

$reviews = array_map(static fn(array $r): array => [
    'name'    => $r['nama'],
    'rating'  => (float) $r['rating'],
    'age'     => waktuRelatif((string) $r['tanggal_ulasan']),
    'comment' => $r['komentar'],
], array_slice($reviewRows, 0, 3));

if (count($reviewRows) > 0) {
    $total = 0.0;
    foreach ($reviewRows as $r) {
        $total += (float) $r['rating'];
    }
    $jumlahUlasan = count($reviewRows);
    $nilaiRating  = round($total / $jumlahUlasan, 1);
} else {
    $jumlahUlasan = (int) ($tourism['jumlah_ulasan'] ?? 0);
    $nilaiRating  = $tourism['rating'] !== null ? (float) $tourism['rating'] : 0.0;
}

$parkir = fetchTourismParking($conn, $tourismId);

$alamat         = trim((string) ($tourism['alamat'] ?? ''));
$lat            = $tourism['latitude'] ?? null;
$lng            = $tourism['longitude'] ?? null;
$punyaKoordinat = is_numeric($lat) && is_numeric($lng);
$linkMaps       = trim((string) ($tourism['Maps_Wisata'] ?? ''));
$linkMapsValid  = preg_match('#^https?://#i', $linkMaps) === 1;

if ($punyaKoordinat) {
    $mapQuery = $lat . ',' . $lng;
} elseif ($alamat !== '') {
    $mapQuery = $tourism['nama_wisata'] . ', ' . $alamat;
} else {
    $mapQuery = $tourism['nama_wisata'] . ', ' . $tourism['wilayah'];
}
$petaTersedia = $punyaKoordinat || $alamat !== '' || $linkMapsValid;
$mapsUrl = $linkMapsValid
    ? $linkMaps
    : 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($tourism['nama_wisata'] . ', ' . $tourism['wilayah']);

$gambar  = trim((string) ($tourism['gambar'] ?? ''));
$adaFoto = $gambar !== '' && is_file(__DIR__ . '/../../' . $gambar);

$destination = [
    'id'           => $tourismId,
    'gambar'       => $gambar,
    'has_photo'    => $adaFoto,
    'title'        => $tourism['nama_wisata'],
    'review_label' => number_format($jumlahUlasan, 0, ',', '.'),
    'review_count' => $jumlahUlasan,
    'rating'       => $nilaiRating,
    'has_rating'   => $nilaiRating > 0,
    'location'     => $tourism['wilayah'],
    'subtitle'     => 'Temukan Informasi ' . $tourism['nama_wisata'] . ' dengan lebih detail',
    'description'  => trim((string) ($tourism['deskripsi'] ?? '')),
    'facilities'   => $facilities,
    'ticket_price' => $tourism['harga_tiket'] > 0
        ? 'Rp ' . number_format($tourism['harga_tiket'], 0, ',', '.') . '/orang'
        : '',
    'parking'      => $parkir,
    'hours'        => $tourism['jam_buka'] !== null && $tourism['jam_tutup'] !== null
        ? str_replace(':', '.', substr($tourism['jam_buka'], 0, 5)) . ' - ' . str_replace(':', '.', substr($tourism['jam_tutup'], 0, 5)) . ' WIB'
        : '',
    'has_address'  => $alamat !== '',
    'address'      => $alamat,
    'maps_url'     => $mapsUrl,
    'map_query'    => $mapQuery,
    'map_ready'    => $petaTersedia,
    'distribution' => $distribution,
    'reviews'      => $reviews,
];

$sudahFavorit = $sudahLogin && isTourismFavorite($conn, (int) $userId, $tourismId);
$pesan = $_SESSION['pesan'] ?? '';
unset($_SESSION['pesan']);
$pesanUlasan = $_SESSION['pesan_ulasan'] ?? '';
unset($_SESSION['pesan_ulasan']);

$ratingText = $destination['has_rating'] ? formatRating($destination['rating']) : '-';
?>
<!DOCTYPE html>
<html lang="id" class="html-detail">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($destination['title']) ?> | Explore Majaku</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fragment+Mono:ital@0;1&family=Frank+Ruhl+Libre:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=Kaushan+Script&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../CSS/edit-profil.css?v=shared-layout-4">
    <link rel="stylesheet" href="../CSS/detail-wisata.css?v=shared-layout-4">
    <link rel="stylesheet" href="../CSS/theme.css?v=shared-layout-4">
</head>
<body class="halaman-detail">

    <svg class="sprite" aria-hidden="true">
        <defs>
            <clipPath id="klip-avatar"><circle cx="110" cy="110" r="110"/></clipPath>
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

            <symbol id="i-hati" viewBox="0 0 28 28">
                <path d="M14 24S3 17.2 3 9.8A5.8 5.8 0 0 1 14 7.2a5.8 5.8 0 0 1 11 2.6C25 17.2 14 24 14 24Z"
                      stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            </symbol>

            <symbol id="i-pin-hijau" viewBox="0 0 40 46">
                <path d="M20 3C12.3 3 6.5 8.8 6.5 16c0 8 13.5 20 13.5 20s13.5-12 13.5-20C33.5 8.8 27.7 3 20 3Z" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="20" cy="16" r="5" fill="none" stroke="currentColor" stroke-width="3.2"/>
                <path d="M10 31.5C5 33 2.5 35.5 4 38.5c3 5 29 5 32 0 1.5-3-1-5.5-6-7" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
            </symbol>

            <symbol id="i-pin-coret" viewBox="0 0 40 46">
                <path d="M20 3C12.3 3 6.5 8.8 6.5 16c0 8 13.5 20 13.5 20s13.5-12 13.5-20C33.5 8.8 27.7 3 20 3Z" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="20" cy="16" r="5" fill="none" stroke="currentColor" stroke-width="3.2"/>
                <path d="M6 5 34 40" fill="none" stroke="currentColor" stroke-width="3.6" stroke-linecap="round"/>
            </symbol>

            <symbol id="i-foto" viewBox="0 0 64 56">
                <rect x="3" y="3" width="58" height="50" rx="8" fill="none" stroke="currentColor" stroke-width="6"/>
                <circle cx="22" cy="20" r="5.5" fill="currentColor"/>
                <path d="M9 47 24 30 33 40 43 27 55 47Z" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
            </symbol>

            <symbol id="i-titik" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="7.5" fill="#fff" stroke="currentColor" stroke-width="3.6"/>
            </symbol>

            <symbol id="i-parkir" viewBox="0 0 32 28">
                <path d="M3.2 14.2 5.6 8.6C6.1 7.4 7.2 6.7 8.5 6.7h9c1.3 0 2.4.7 2.9 1.9l2.4 5.6c1.4.3 2.2 1.4 2.2 2.8v4.4c0 .8-.6 1.4-1.4 1.4H22v1.6c0 .8-.6 1.4-1.4 1.4h-1.8c-.8 0-1.4-.6-1.4-1.4v-1.6H9.6v1.6c0 .8-.6 1.4-1.4 1.4H6.4c-.8 0-1.4-.6-1.4-1.4v-1.6H3.4c-.8 0-1.4-.6-1.4-1.4V17c0-1.4.8-2.5 1.2-2.8z"/>
                <path d="M7.6 9.2h9.8l1.6 4.2H6z" fill="#eef2ee"/>
                <circle cx="6.2" cy="18.2" r="1.5" fill="#eef2ee"/>
                <circle cx="19.8" cy="18.2" r="1.5" fill="#eef2ee"/>
                <rect x="27" y="8" width="1.6" height="16" rx=".5"/>
                <rect x="24.2" y="1.5" width="7.2" height="7.2" rx="1.2"/>
                <path d="M26.6 7.3V2.9h1.7a1.3 1.3 0 0 1 0 2.6h-1.7" fill="none" stroke="#eef2ee" stroke-width="1"/>
            </symbol>

            <symbol id="i-toilet" viewBox="0 0 32 28">
                <circle cx="8.5" cy="4.2" r="2.8"/>
                <path d="M5.4 7.2h6.2c1.2 0 2.2 1 2.2 2.2V17h-2.4v9H8.9v-8.4h-.8V26H5.6v-9H3.2V9.4c0-1.2 1-2.2 2.2-2.2z"/>
                <circle cx="23.5" cy="4.2" r="2.8"/>
                <path d="M23.5 7.2c-2.3 0-4 1.5-4.5 3.7L17.2 18h2.9v8h2.6v-5.5h1.6V26h2.6v-8h2.9L28 10.9c-.5-2.2-2.2-3.7-4.5-3.7z"/>
            </symbol>

            <symbol id="i-masjid" viewBox="0 0 28 28">
                <path d="M2 26V13.2l6.5-5.7 6.5 5.7V26z"/>
                <path d="M6.6 26v-5.3a1.9 1.9 0 0 1 3.8 0V26z" fill="#eef2ee"/>
                <path d="M16.8 26V9.6L19.6 3l2.8 6.6V26z"/>
                <rect x="18.7" y="12.6" width="1.8" height="3" fill="#eef2ee"/>
            </symbol>

            <symbol id="i-tiket" viewBox="0 0 32 24">
                <g transform="rotate(-10 16 12)">
                    <path d="M2.5 5h27v4.4a2.6 2.6 0 0 0 0 5.2V19h-27v-4.4a2.6 2.6 0 0 0 0-5.2z"/>
                    <rect x="11" y="9.5" width="10" height="5" rx="1" fill="#eef2ee"/>
                </g>
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

    <div class="hero-wisata<?= $destination['has_photo'] ? '' : ' tanpa-foto' ?>" id="hero-wisata">
        <?php if ($destination['has_photo']): ?>
            <img src="../../<?= e($destination['gambar']) ?>"
                 alt="<?= e($destination['title']) ?>"
                 onerror="this.parentNode.classList.add('tanpa-foto')">
        <?php endif; ?>
        <div class="hero-wisata__kosong" role="img" aria-label="Foto tidak tersedia">
            <svg viewBox="0 0 64 56" aria-hidden="true"><use href="#i-foto"/></svg>
            <span>Foto Tidak Tersedia</span>
        </div>
    </div>

    <main class="detail">

        <?php if ($pesan !== ''): ?>
            <div class="notif" role="status"><?= e($pesan) ?></div>
        <?php endif; ?>

        <section class="ringkasan" aria-labelledby="judul-wisata">
            <div class="ringkasan__teks">
                <h1 id="judul-wisata"><?= e($destination['title']) ?></h1>

                <div class="ringkasan__rating">
                    <?php if ($destination['has_rating']): ?>
                        <span class="bintang" aria-hidden="true"><?= bintangHtml($destination['rating']) ?></span>
                        <span class="ringkasan__skor"><?= e($ratingText) ?> (<?= e($destination['review_label']) ?> ulasan)</span>
                    <?php else: ?>
                        <span class="ringkasan__skor">Belum ada ulasan</span>
                    <?php endif; ?>
                </div>

                <p class="ringkasan__lokasi">
                    <svg viewBox="0 0 40 44" aria-hidden="true"><use href="#i-lokasi"/></svg>
                    <span><?= e($destination['location']) ?></span>
                </p>
            </div>

            <form method="post" action="detail-wisata.php?id=<?= e(rawurlencode($destination['id'])) ?>">
                <input type="hidden" name="aksi" value="toggle_favorit">
                <button class="tombol-favorit<?= $sudahFavorit ? ' aktif' : '' ?>" type="submit" id="tombol-favorit"
                        aria-pressed="<?= $sudahFavorit ? 'true' : 'false' ?>">
                    <svg viewBox="0 0 28 28" aria-hidden="true"><use href="#i-hati"/></svg>
                    <span><?= $sudahFavorit ? 'Tersimpan Di Favorit' : 'Simpan Favorit' ?></span>
                </button>
            </form>
        </section>

        <section class="blok" aria-labelledby="judul-info">
            <h2 class="blok__judul" id="judul-info">INFORMASI WISATA</h2>
            <p class="blok__sub"><?= e($destination['subtitle']) ?></p>

            <div class="info-grid">
                <article class="kartu kartu--deskripsi">
                    <h3>Deskripsi</h3>
                    <p><?= nilaiAtauKet($destination['description'], 'Deskripsi destinasi ini belum tersedia.') ?></p>
                </article>

                <article class="kartu kartu--fasilitas">
                    <h3>Fasilitas</h3>
                    <ul>
                        <?php if ($destination['facilities'] !== []): ?>
                            <?php foreach ($destination['facilities'] as $fasilitas): ?>
                                <li>
                                    <?php
                                    echo match ($fasilitas) {
                                        'Area parkir' => '<svg class="ikon-fas ikon-parkir" viewBox="0 0 32 28" aria-hidden="true"><use href="#i-parkir"/></svg>',
                                        'Toilet Umum' => '<svg class="ikon-fas ikon-toilet" viewBox="0 0 32 28" aria-hidden="true"><use href="#i-toilet"/></svg>',
                                        'Mushola'     => '<svg class="ikon-fas ikon-masjid" viewBox="0 0 28 28" aria-hidden="true"><use href="#i-masjid"/></svg>',
                                        'Spot Foto'   => '<span class="ikon-fas" aria-hidden="true"><i class="bi bi-camera-fill"></i></span>',
                                        default       => '<span class="ikon-fas" aria-hidden="true"><i class="bi bi-check-square-fill"></i></span>',
                                    };
                                    ?>
                                    <span class="fasilitas__nama"><?= e($fasilitas) ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="fasilitas__kosong"><span class="belum">Informasi fasilitas belum tersedia.</span></li>
                        <?php endif; ?>
                    </ul>
                </article>

                <article class="kartu kartu--harga">
                    <h3>Harga</h3>
                    <div class="harga-baris">
                        <div class="harga-item">
                            <svg class="ikon-tiket" viewBox="0 0 32 24" aria-hidden="true"><use href="#i-tiket"/></svg>
                            <div>
                                <strong>Tiket Masuk</strong>
                                <span><?= nilaiAtauKet($destination['ticket_price']) ?></span>
                            </div>
                        </div>
                        <div class="harga-item harga-item--parkir">
                            <div>
                                <strong>Tiket Parkir</strong>
                                <?php if ($destination['parking'] !== []): ?>
                                    <?php foreach ($destination['parking'] as $baris): ?>
                                        <span><?= e($baris) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="belum">Belum Tersedia</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <h3 class="jam-judul">Jam Operasional</h3>
                    <div class="jam-baris">
                        <i class="bi bi-alarm" aria-hidden="true"></i>
                        <span><?= nilaiAtauKet($destination['hours']) ?></span>
                    </div>
                </article>
            </div>
        </section>

        <section class="blok blok--lokasi" aria-labelledby="judul-lokasi">
            <h2 class="blok__judul" id="judul-lokasi">LOKASI WISATA</h2>
            <p class="blok__sub">Temukan lokasi <?= e(mb_strtolower($destination['title'], 'UTF-8')) ?> dengan mudah di peta/maps</p>

            <div class="lokasi-grid">
                <?php if ($destination['map_ready']): ?>
                    <div class="peta">
                        <iframe
                            title="Peta lokasi <?= e($destination['title']) ?>"
                            src="https://maps.google.com/maps?q=<?= rawurlencode($destination['map_query']) ?>&amp;hl=id&amp;z=15&amp;output=embed"
                            loading="lazy"
                            allowfullscreen
                            referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                <?php else: ?>
                    <div class="peta peta--kosong" role="img" aria-label="Peta lokasi belum tersedia">
                        <svg class="peta__ikon" viewBox="0 0 40 46" aria-hidden="true"><use href="#i-pin-coret"/></svg>
                        <h3>Koordinat Wisata Tidak Tersedia</h3>
                        <p>Peta belum dapat ditampilkan karena koordinat lokasi belum tersedia.</p>
                    </div>
                <?php endif; ?>

                <article class="kartu-alamat">
                    <svg class="kartu-alamat__ikon" viewBox="0 0 40 46" aria-hidden="true"><use href="#i-pin-hijau"/></svg>
                    <h3><?= $destination['has_address'] ? 'Alamat tersedia' : 'Alamat belum tersedia' ?></h3>
                    <?php if ($destination['has_address']): ?>
                        <p><?= e($destination['address']) ?></p>
                    <?php else: ?>
                        <p class="belum">Lokasi akan diperbarui segera.</p>
                    <?php endif; ?>
                    <a class="tombol-maps" href="<?= e($destination['maps_url']) ?>" target="_blank" rel="noopener noreferrer">
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                        <span>Buka di Google Maps</span>
                    </a>
                </article>
            </div>
        </section>

        <section class="blok blok--ulasan" aria-labelledby="judul-ulasan">
            <h2 class="blok__judul" id="judul-ulasan">ULASAN</h2>
            <p class="blok__sub">Simak Pengalaman Pengunjung yang sudah datang ke tempat</p>

            <div class="ringkasan-ulasan">
                <div class="ringkasan-ulasan__skor"><?= e($ratingText) ?></div>

                <div class="ringkasan-ulasan__info">
                    <?php if ($destination['has_rating']): ?>
                        <span class="bintang bintang--besar" aria-hidden="true"><?= bintangHtml($destination['rating']) ?></span>
                    <?php endif; ?>
                    <span class="ringkasan-ulasan__jumlah">
                        <?= $destination['review_count'] > 0
                            ? 'Dari ' . e($destination['review_label']) . ' ulasan'
                            : 'Belum ada ulasan' ?>
                    </span>
                </div>

                <?php if ($destination['has_rating']): ?>
                    <div class="sebaran" aria-label="Sebaran rating pengunjung">
                        <?php foreach ($destination['distribution'] as $baris): ?>
                            <div class="sebaran__baris">
                                <span class="sebaran__angka"><?= (int) $baris['stars'] ?></span>
                                <svg class="sebaran__titik" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-titik"/></svg>
                                <span class="sebaran__track"><span style="width: <?= (int) $baris['percentage'] ?>%"></span></span>
                                <span class="sebaran__persen"><?= (int) $baris['percentage'] ?>%</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="ulasan-grid">
                <?php if ($destination['reviews'] !== []): ?>
                    <?php foreach ($destination['reviews'] as $ulasan): ?>
                        <article class="ulasan">
                            <div class="ulasan__kepala">
                                <svg class="ulasan__avatar" viewBox="0 0 220 220" aria-hidden="true"><use href="#ikon-profil"/></svg>
                                <div class="ulasan__meta">
                                    <strong><?= e($ulasan['name']) ?></strong>
                                    <span class="bintang bintang--kecil" role="img"
                                          aria-label="<?= e(formatRating((float) $ulasan['rating'])) ?> dari 5 bintang">
                                        <?= bintangHtml((float) $ulasan['rating']) ?>
                                    </span>
                                    <span class="ulasan__waktu"><?= e($ulasan['age']) ?></span>
                                </div>
                            </div>
                            <p class="ulasan__komentar"><?= e($ulasan['comment']) ?></p>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="kosong">Belum ada ulasan untuk destinasi ini. Jadilah yang pertama memberikan ulasan.</p>
                <?php endif; ?>
            </div>

            <?php if ($pesanUlasan !== ''): ?>
                <div class="notif notif--ulasan" role="status"><?= e($pesanUlasan) ?></div>
            <?php endif; ?>

            <button class="tombol-ulasan" type="button" id="tombol-ulasan"
                    data-login="<?= $sudahLogin ? '1' : '0' ?>" aria-haspopup="dialog">
                Tambah Ulasan dan Rating
            </button>
        </section>
    </main>

    <dialog class="dialog-ulasan" id="dialog-ulasan" aria-labelledby="judul-dialog">
        <form method="post" action="detail-wisata.php?id=<?= e(rawurlencode($destination['id'])) ?>">
            <input type="hidden" name="aksi" value="tambah_ulasan">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

            <h3 id="judul-dialog">Tambah Ulasan dan Rating</h3>
            <p class="dialog__sub">Bagikan pengalamanmu di <?= e($destination['title']) ?></p>

            <fieldset class="bintang-input">
                <legend>Rating</legend>
                <div class="bintang-input__baris">
                    <?php for ($n = 10; $n >= 1; $n--):
                        $nilai = $n / 2;
                        $teks  = str_replace('.', ',', (string) $nilai);
                        $sisi  = $n % 2 === 1 ? 'sisi-kiri' : 'sisi-kanan';
                    ?>
                        <input type="radio" name="rating" id="rating-<?= $n ?>" value="<?= e((string) $nilai) ?>" required>
                        <label for="rating-<?= $n ?>" class="<?= $sisi ?>" title="<?= e($teks) ?> bintang">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-bintang"/></svg>
                            <span class="sr-only"><?= e($teks) ?> bintang</span>
                        </label>
                    <?php endfor; ?>
                </div>
                <p class="bintang-input__nilai" id="nilai-rating" aria-live="polite">Pilih rating</p>
            </fieldset>

            <label class="dialog__label" for="komentar">Ulasan</label>
            <textarea id="komentar" name="komentar" rows="5" maxlength="500" required
                      placeholder="Ceritakan pengalamanmu di sini..."></textarea>
            <p class="dialog__catatan">Jika kamu sudah pernah mengulas destinasi ini, ulasan lamamu akan diperbarui.</p>

            <div class="dialog__aksi">
                <button type="button" class="btn-batal" id="batal-ulasan">Batal</button>
                <button type="submit" class="btn-kirim">Kirim Ulasan</button>
            </div>
        </form>
    </dialog>

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

        (function () {
            var daftar = document.querySelectorAll('.notif');
            daftar.forEach(function (n) {
                setTimeout(function () { n.classList.add('hilang'); }, 4000);
            });
        })();

        (function () {
            var tombol = document.getElementById('tombol-ulasan');
            var dialog = document.getElementById('dialog-ulasan');
            var batal = document.getElementById('batal-ulasan');
            if (!tombol || !dialog) return;

            tombol.addEventListener('click', function () {
                if (tombol.getAttribute('data-login') !== '1') {
                    window.location.href = '../auth/Login.php';
                    return;
                }
                if (typeof dialog.showModal === 'function') {
                    dialog.showModal();
                } else {
                    dialog.setAttribute('open', '');
                }
            });

            batal.addEventListener('click', function () { dialog.close(); });

            dialog.addEventListener('click', function (e) {
                if (e.target === dialog) dialog.close();
            });
        })();

        (function () {
            var info = document.getElementById('nilai-rating');
            var radios = document.querySelectorAll('.bintang-input input[type="radio"]');
            if (!info) return;
            radios.forEach(function (r) {
                r.addEventListener('change', function () {
                    info.textContent = r.value.replace('.', ',') + ' dari 5';
                });
            });
        })();
    </script>
</body>
</html>