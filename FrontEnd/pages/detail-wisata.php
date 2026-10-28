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

function formatJam($jam): string {
    return str_replace(':', '.', substr((string) $jam, 0, 5));
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

function jenisFasilitas(string $nama): string {
    $n = mb_strtolower($nama, 'UTF-8');
    if (str_contains($n, 'parkir')) return 'parkir';
    if (preg_match('/toilet|wc|kamar mandi/u', $n) === 1) return 'toilet';
    if (preg_match('/mushola|musholla|musala|masjid|ibadah/u', $n) === 1) return 'masjid';
    if (preg_match('/foto|kamera|selfie/u', $n) === 1) return 'kamera';
    return 'umum';
}

require_once __DIR__ . '/../../Backend/database/koneksi.php';
require_once __DIR__ . '/../../Backend/database/wisata_repository.php';

function fetchTourismExtra(mysqli $conn, string $tourismId): array {
    $diinginkan = ['parkir_motor', 'parkir_mobil', 'hari_operasional', 'hari_buka', 'hari'];
    try {
        $kolomAda = [];
        $hasil = $conn->query('SHOW COLUMNS FROM wisata');
        while ($k = $hasil->fetch_assoc()) {
            $kolomAda[] = $k['Field'];
        }
        $pilih = array_values(array_intersect($diinginkan, $kolomAda));
        if ($pilih === []) {
            return [];
        }
        $sql  = 'SELECT `' . implode('`, `', $pilih) . '` FROM wisata WHERE id_wisata = ? LIMIT 1';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $tourismId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();
        return $row;
    } catch (Throwable $e) {
        return [];
    }
}

function fetchUlasanSaya(mysqli $conn, int $userId, string $tourismId): array {
    try {
        $stmt = $conn->prepare('SELECT rating, komentar FROM ulasan WHERE Id_user = ? AND Id_wisata = ? ORDER BY Id_ulasan DESC LIMIT 1');
        $stmt->bind_param('is', $userId, $tourismId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();
        return $row;
    } catch (Throwable $e) {
        return [];
    }
}

function simpanUlasan(mysqli $conn, int $userId, string $tourismId, int $rating, string $komentar): bool
{
    try {
        $stmt = $conn->prepare('SELECT Id_ulasan FROM ulasan WHERE Id_user = ? AND Id_wisata = ? ORDER BY Id_ulasan DESC LIMIT 1');
        $stmt->bind_param('is', $userId, $tourismId);
        $stmt->execute();
        $ada = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($ada) {
            $idUlasan = (int) $ada['Id_ulasan'];
            $stmt = $conn->prepare('UPDATE ulasan SET rating = ?, komentar = ?, tanggal_ulasan = CURDATE() WHERE Id_ulasan = ?');
            $stmt->bind_param('isi', $rating, $komentar, $idUlasan);
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO ulasan (Id_user, Id_wisata, rating, komentar, tanggal_ulasan) VALUES (?, ?, ?, ?, CURDATE())'
            );
            $stmt->bind_param('isis', $userId, $tourismId, $rating, $komentar);
        }
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    } catch (Throwable $e) {
        error_log('ulasan: ' . $e->getMessage());
        return false;
    }
}

function perbaruiRatingWisata(mysqli $conn, string $tourismId): void
{
    try {
        $stmt = $conn->prepare(
            'UPDATE wisata SET average_rating = (SELECT ROUND(AVG(rating), 2) FROM ulasan WHERE Id_wisata = ?) WHERE id_wisata = ?'
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

$tourismId = $_GET['id'] ?? '';
if (!is_string($tourismId) || preg_match('/^[KM][0-9]{5,}$/', $tourismId) !== 1) {
    http_response_code(404);
    exit('Destinasi tidak ditemukan.');
}

$userId = filter_var($_SESSION['id_user'] ?? null, FILTER_VALIDATE_INT);
$sudahLogin = $userId !== false && $userId > 0;
if ($sudahLogin && empty($_SESSION['role'])) {
    $_SESSION['role'] = 'user';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'tambah_ulasan') {
    if (!$sudahLogin) {
        header('Location: ../auth/Login.php');
        exit;
    }

    $csrfOk = isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);

    $ratingIn = filter_var(
        trim((string) ($_POST['rating'] ?? '')),
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => 5]]
    );
    $ratingValid = $ratingIn !== false;

    $komentarIn = mb_substr(trim((string) ($_POST['komentar'] ?? '')), 0, 500, 'UTF-8');

    if (!$csrfOk) {
        $_SESSION['pesan_ulasan'] = 'Sesi tidak valid. Silakan coba lagi.';
    } elseif (!$ratingValid) {
        $_SESSION['pesan_ulasan'] = 'Pilih rating 1 sampai 5 bintang.';
    } elseif ($komentarIn === '') {
        $_SESSION['pesan_ulasan'] = 'Ulasan tidak boleh kosong.';
    } elseif (simpanUlasan($conn, (int) $userId, $tourismId, (int) $ratingIn, $komentarIn)) {
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

$urutanFasilitas = ['parkir' => 1, 'toilet' => 2, 'masjid' => 3, 'kamera' => 4, 'umum' => 5];
$fasilitasDb = [];
try {
    $fasilitasDb = fetchTourismFacilities($conn, $tourismId);
} catch (Throwable $e) {
    $fasilitasDb = [];
}
$fasilitasTampil = [];
foreach ($fasilitasDb as $namaFasilitas) {
    $namaFasilitas = trim((string) $namaFasilitas);
    if ($namaFasilitas === '') continue;
    $fasilitasTampil[] = ['nama' => $namaFasilitas, 'ikon' => jenisFasilitas($namaFasilitas)];
}
usort($fasilitasTampil, static fn(array $a, array $b): int => $urutanFasilitas[$a['ikon']] <=> $urutanFasilitas[$b['ikon']]);

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
    $nilaiRating  = ($tourism['rating'] ?? null) !== null ? (float) $tourism['rating'] : 0.0;
}

$extra = fetchTourismExtra($conn, $tourismId);
$parkir = [];
if (!empty($extra['parkir_motor'])) {
    $parkir[] = 'Rp ' . number_format((int) $extra['parkir_motor'], 0, ',', '.') . '/Motor';
}
if (!empty($extra['parkir_mobil'])) {
    $parkir[] = 'Rp ' . number_format((int) $extra['parkir_mobil'], 0, ',', '.') . '/Mobil';
}

$namaWisata     = (string) $tourism['nama_wisata'];
$alamat         = trim((string) ($tourism['alamat'] ?? ''));
$alamatTampil   = $alamat;
if ($alamat !== '' && mb_stripos($alamat, $namaWisata, 0, 'UTF-8') !== 0) {
    $alamatTampil = $namaWisata . "\n" . $alamat;
}
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
$petaTersedia = $punyaKoordinat || $alamat !== '';
$mapsUrl = $linkMapsValid
    ? $linkMaps
    : 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($tourism['nama_wisata'] . ', ' . $tourism['wilayah']);

$gambar  = trim((string) ($tourism['gambar'] ?? ''));
$adaFoto = $gambar !== '' && is_file(__DIR__ . '/../../' . $gambar);

$jamBuka  = trim((string) ($tourism['jam_buka'] ?? ''));
$jamTutup = trim((string) ($tourism['jam_tutup'] ?? ''));
$hariBuka = '';
foreach (['hari_operasional', 'hari_buka', 'hari'] as $kolomHari) {
    $nilaiHari = trim((string) ($extra[$kolomHari] ?? ''));
    if ($nilaiHari !== '') {
        $hariBuka = $nilaiHari;
        break;
    }
}
$jamOperasional = '';
if ($jamBuka !== '' && $jamTutup !== '') {
    $jamOperasional = formatJam($jamBuka) . ' - ' . formatJam($jamTutup) . ' WIB';
    if ($hariBuka !== '') {
        $jamOperasional = $hariBuka . ', ' . $jamOperasional;
    }
}

$destination = [
    'id'           => $tourismId,
    'gambar'       => $gambar,
    'has_photo'    => $adaFoto,
    'title'        => $tourism['nama_wisata'],
    'review_label' => number_format($jumlahUlasan, 0, ',', '.'),
    'review_count' => $jumlahUlasan,
    'rating'       => $nilaiRating,
    'has_rating'   => $nilaiRating > 0 && $jumlahUlasan > 0,
    'location'     => $tourism['wilayah'],
    'subtitle'     => 'Temukan Informasi ' . $tourism['nama_wisata'] . ' dengan lebih detail',
    'description'  => trim((string) ($tourism['deskripsi'] ?? '')),
    'ticket_price' => ($tourism['harga_tiket'] ?? 0) > 0
        ? 'Rp ' . number_format((float) $tourism['harga_tiket'], 0, ',', '.') . '/orang'
        : '',
    'parking'      => $parkir,
    'hours'        => $jamOperasional,
    'has_address'  => $alamat !== '',
    'address'      => $alamatTampil,
    'maps_url'     => $mapsUrl,
    'map_query'    => $mapQuery,
    'map_ready'    => $petaTersedia,
    'distribution' => $distribution,
    'reviews'      => $reviews,
];

$sudahFavorit = $sudahLogin && isTourismFavorite($conn, (int) $userId, $tourismId);
$ulasanSaya   = $sudahLogin ? fetchUlasanSaya($conn, (int) $userId, $tourismId) : [];
$ratingSaya   = isset($ulasanSaya['rating']) ? (int) round((float) $ulasanSaya['rating']) : 0;
$komentarSaya = (string) ($ulasanSaya['komentar'] ?? '');

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
    <link rel="stylesheet" href="../CSS/detail-wisata.css?v=shared-layout-17">
</head>
<body class="halaman-detail">

    <svg class="sprite" aria-hidden="true">
        <defs>
            <symbol id="ikon-profil" viewBox="0 0 220 220">
                <circle cx="110" cy="110" r="93.5" fill="none" stroke="#166d5c" stroke-width="11"/>
                <circle cx="110" cy="82.5" r="27.5" fill="none" stroke="#166d5c" stroke-width="11"/>
                <path d="M49.5 170.5c9.35-27.5 30.25-41.25 60.5-41.25s51.15 13.75 60.5 41.25" fill="none" stroke="#166d5c" stroke-width="11" stroke-linecap="round" stroke-linejoin="round"/>
            </symbol>

            <symbol id="i-bintang" viewBox="0 0 24 24">
                <path d="M12 1.8l3.1 6.6 7.2.9-5.3 4.9 1.4 7.1L12 17.7l-6.4 3.6 1.4-7.1L1.7 9.3l7.2-.9L12 1.8z"/>
            </symbol>

            <symbol id="i-ulang" viewBox="0 0 24 24">
                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                <path d="M3 3v5h5"/>
            </symbol>

            <symbol id="i-lokasi" viewBox="0 0 40 44">
                <ellipse cx="20" cy="37" rx="14" ry="5" fill="#45d84f"/>
                <path d="M20 32a14 5 0 0 1 0 10z" fill="#10b981"/>
                <path d="M20 3C13.4 3 9 7.9 9 13.8c0 7.2 8.6 14.6 11 19.2 2.4-4.6 11-12 11-19.2C31 7.9 26.6 3 20 3z" fill="#ed2118"/>
                <circle cx="20" cy="13.5" r="4.8" fill="#fff"/>
            </symbol>

            <symbol id="i-pin-hijau" viewBox="0 0 40 46">
                <path d="M20 2.5C13.2 2.5 8 7.8 8 14.4c0 7.2 8.2 13.6 12 18.6 3.8-5 12-11.4 12-18.6C32 7.8 26.8 2.5 20 2.5Z" fill="none" stroke="currentColor" stroke-width="3.4" stroke-linejoin="round"/>
                <circle cx="20" cy="14" r="3.2" fill="currentColor"/>
                <path d="M11.5 30.5C6.6 31.6 3.8 33.6 3.8 36.2c0 3.8 7.2 6.6 16.2 6.6s16.2-2.8 16.2-6.6c0-2.6-2.8-4.6-7.7-5.7" fill="none" stroke="currentColor" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"/>
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
                <circle cx="12" cy="12" r="11" fill="#41c19e"/>
                <path transform="translate(5.2 5.2) scale(.57)" fill="#fff" d="M12 1.8l3.1 6.6 7.2.9-5.3 4.9 1.4 7.1L12 17.7l-6.4 3.6 1.4-7.1L1.7 9.3l7.2-.9L12 1.8z"/>
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

            <symbol id="i-gembok" viewBox="0 0 64 64">
                <rect x="10" y="27" width="44" height="31" rx="7" fill="none" stroke="currentColor" stroke-width="5"/>
                <path d="M19 27V19a13 13 0 0 1 26 0v8" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
                <circle cx="32" cy="41" r="3.2" fill="currentColor"/>
                <path d="M32 43v6" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
            </symbol>

            <symbol id="i-hati" viewBox="0 0 64 56">
                <path d="M32 54C14 40 3 30 3 18.5 3 9.9 9.6 3 18 3c5.6 0 10.6 3 14 8 3.4-5 8.4-8 14-8 8.4 0 15 6.9 15 15.5C61 30 50 40 32 54Z" fill="currentColor"/>
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
        <li role="none"><a class="dd-kategori__item" role="menuitem" href="kategori.php?kategori=alam">Wisata Alam &amp; pegunungan</a></li>
        <li role="none"><a class="dd-kategori__item" role="menuitem" href="kategori.php?kategori=air-curug">Wisata Air &amp; Curug</a></li>
        <li role="none"><a class="dd-kategori__item" role="menuitem" href="kategori.php?kategori=buatan-kuliner">Wisata Buatan &amp; Kuliner</a></li>
        <li role="none"><a class="dd-kategori__item" role="menuitem" href="kategori.php?kategori=sejarah-budaya">Wisata Sejarah &amp; Budaya</a></li>
    </ul>

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

        <div class="notif notif--favorit" id="notif-favorit" role="status" hidden></div>

        <section class="ringkasan" aria-labelledby="judul-wisata">
            <div class="ringkasan__teks">
                <h1 id="judul-wisata"><?= e($destination['title']) ?></h1>

                <div class="ringkasan__rating">
                    <?php if ($destination['has_rating']): ?>
                        <span class="bintang" aria-hidden="true"><?= bintangHtml($destination['rating']) ?></span>
                        <span class="ringkasan__skor"><?= e($ratingText) ?> (<?= e($destination['review_label']) ?> ulasan)</span>
                    <?php else: ?>
                        <span class="ringkasan__skor">(belum ada ulasan)</span>
                    <?php endif; ?>
                </div>

                <p class="ringkasan__lokasi">
                    <svg viewBox="0 0 40 44" aria-hidden="true"><use href="#i-lokasi"/></svg>
                    <span><?= e($destination['location']) ?></span>
                </p>
            </div>

            <div class="ringkasan__aksi">
                <button class="tombol-favorit<?= $sudahFavorit ? ' aktif' : '' ?>" type="button" id="tombol-favorit"
                        data-id="<?= e($destination['id']) ?>"
                        data-csrf="<?= e($_SESSION['csrf']) ?>"
                        data-login="<?= $sudahLogin ? '1' : '0' ?>"
                        aria-pressed="<?= $sudahFavorit ? 'true' : 'false' ?>">
                    <i class="bi <?= $sudahFavorit ? 'bi-heart-fill' : 'bi-heart' ?>" aria-hidden="true"></i>
                    <span><?= $sudahFavorit ? 'Tersimpan Di Favorit' : 'Simpan Favorit' ?></span>
                </button>
            </div>
        </section>

        <section class="blok" aria-labelledby="judul-info">
            <h2 class="blok__judul" id="judul-info">INFORMASI WISATA</h2>
            <p class="blok__sub"><?= e($destination['subtitle']) ?></p>

            <div class="info-grid">
                <article class="kartu kartu--deskripsi<?= $destination['description'] === '' ? ' kartu--kosong' : '' ?>">
                    <h3>Deskripsi</h3>
                    <p><?= nilaiAtauKet($destination['description'], 'Deskripsi destinasi ini belum tersedia') ?></p>
                </article>

                <article class="kartu kartu--fasilitas<?= $fasilitasTampil === [] ? ' kartu--kosong' : '' ?>">
                    <h3>Fasilitas</h3>
                    <?php if ($fasilitasTampil !== []): ?>
                        <ul>
                            <?php foreach ($fasilitasTampil as $fasilitas): ?>
                                <li>
                                    <?php
                                    echo match ($fasilitas['ikon']) {
                                        'parkir' => '<svg class="ikon-fas ikon-parkir" viewBox="0 0 32 28" aria-hidden="true"><use href="#i-parkir"/></svg>',
                                        'toilet' => '<svg class="ikon-fas ikon-toilet" viewBox="0 0 32 28" aria-hidden="true"><use href="#i-toilet"/></svg>',
                                        'masjid' => '<svg class="ikon-fas ikon-masjid" viewBox="0 0 28 28" aria-hidden="true"><use href="#i-masjid"/></svg>',
                                        'kamera' => '<span class="ikon-fas ikon-kamera" aria-hidden="true"><i class="bi bi-camera-fill"></i></span>',
                                        default  => '<span class="ikon-fas ikon-umum" aria-hidden="true"><i class="bi bi-check-circle-fill"></i></span>',
                                    };
                                    ?>
                                    <span class="fasilitas__nama"><?= e($fasilitas['nama']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="fasilitas__kosong">Informasi fasilitas ini belum tersedia</p>
                    <?php endif; ?>
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

            <div class="lokasi-grid<?= $destination['map_ready'] ? '' : ' lokasi-grid--kosong' ?>">
                <?php if ($destination['map_ready']): ?>
                    <div class="peta">
                        <iframe
                            title="Peta lokasi <?= e($destination['title']) ?>"
                            src="https://maps.google.com/maps?q=<?= rawurlencode($destination['map_query']) ?>&amp;hl=id&amp;z=15&amp;output=embed"
                            allowfullscreen
                            referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                <?php else: ?>
                    <div class="peta peta--kosong" role="img" aria-label="Peta lokasi belum tersedia">
                        <svg class="peta__ikon" viewBox="0 0 40 46" aria-hidden="true"><use href="#i-pin-coret"/></svg>
                        <h3>Koordinat Wisata Tidak Tersedia</h3>
                        <p>Peta belum dapat ditampilkan karena koordinat lokasi belum tersedia</p>
                    </div>
                <?php endif; ?>

                <article class="kartu-alamat<?= $destination['has_address'] ? '' : ' kartu-alamat--kosong' ?>">
                    <svg class="kartu-alamat__ikon" viewBox="0 0 40 46" aria-hidden="true"><use href="#i-pin-hijau"/></svg>
                    <h3><?= $destination['has_address'] ? 'Alamat tersedia' : 'Alamat Belum tersedia' ?></h3>
                    <?php if ($destination['has_address']): ?>
                        <p><?= nl2br(e($destination['address'])) ?></p>
                    <?php else: ?>
                        <p class="belum">Lokasi Akan Di perbarui Segera</p>
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

            <?php if ($destination['review_count'] > 0): ?>
            <div class="ringkasan-ulasan">
                <div class="ringkasan-ulasan__skor"><?= e($ratingText) ?></div>

                <div class="ringkasan-ulasan__info">
                    <?php if ($destination['has_rating']): ?>
                        <span class="bintang bintang--besar" aria-hidden="true"><?= bintangHtml($destination['rating']) ?></span>
                    <?php endif; ?>
                    <span class="ringkasan-ulasan__jumlah">Dari <?= e($destination['review_label']) ?> ulasan</span>
                </div>

                <?php if ($destination['has_rating'] && $reviewRows !== []): ?>
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
            </div>

            <?php else: ?>
            <div class="ulasan-kosong"><p>Belum ada ulasan</p></div>
            <?php endif; ?>

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
        <form method="post" id="form-ulasan" novalidate action="detail-wisata.php?id=<?= e(rawurlencode($destination['id'])) ?>">
            <input type="hidden" name="aksi" value="tambah_ulasan">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

            <h3 id="judul-dialog">Tambah Ulasan dan Rating</h3>
            <p class="dialog__sub">Bagikan pengalamanmu di <?= e($destination['title']) ?></p>

            <fieldset class="bintang-input">
                <legend class="sr-only">Rating</legend>
                <div class="bintang-input__rate">
                    <div class="bintang-input__baris">
                        <?php for ($n = 5; $n >= 1; $n--): ?>
                            <input type="radio" name="rating" id="rating-<?= $n ?>" value="<?= $n ?>"<?= $ratingSaya === $n ? ' checked' : '' ?>>
                            <label for="rating-<?= $n ?>" title="<?= $n ?> bintang">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-bintang"/></svg>
                                <span class="sr-only"><?= $n ?> bintang</span>
                            </label>
                        <?php endfor; ?>
                    </div>
                    <span class="bintang-input__nilai" id="nilai-rating" aria-live="polite">
                        <span class="nilai__angka">Rate</span><span class="nilai__skala" hidden>/5</span>
                    </span>
                    <button type="button" class="bintang-input__reset" id="reset-rating" aria-label="Atur ulang rating" hidden>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-ulang"/></svg>
                    </button>
                </div>
            </fieldset>

            <label class="dialog__label" for="komentar">Ulasan</label>
            <textarea id="komentar" name="komentar" rows="5" maxlength="500"
                      placeholder="Ceritakan pengalamanmu di sini..."><?= e($komentarSaya) ?></textarea>
            <p class="dialog__catatan">Jika kamu sudah pernah mengulas destinasi ini, ulasan lamamu akan di perbarui.</p>

            <p class="dialog__error" id="error-ulasan" role="alert" hidden></p>

            <div class="dialog__aksi">
                <button type="button" class="btn-batal" id="batal-ulasan">Batal</button>
                <button type="submit" class="btn-kirim">Kirim Ulasan</button>
            </div>
        </form>
    </dialog>

    <dialog class="popup" id="popup-login" aria-labelledby="popup-login-judul" aria-describedby="popup-login-isi">
        <div class="popup__ikon popup__ikon--gembok" aria-hidden="true">
            <svg viewBox="0 0 64 64"><use href="#i-gembok"/></svg>
        </div>
        <h2 class="popup__judul popup__judul--merah" id="popup-login-judul">Login untuk Memberikan Rating dan Ulasan</h2>
        <p class="popup__isi" id="popup-login-isi">Bagikan pengalaman wisatamu setelah login. Silakan login terlebih dahulu untuk memberikan rating dan ulasan wisata ini</p>
        <div class="popup__aksi">
            <a class="popup__tombol popup__tombol--isi" href="../auth/Login.php" id="popup-login-ya">Login Sekarang</a>
            <button class="popup__tombol popup__tombol--garis" type="button" id="popup-login-nanti" autofocus>Nanti saja</button>
        </div>
    </dialog>

    <dialog class="popup" id="popup-sukses" aria-labelledby="popup-sukses-judul" aria-describedby="popup-sukses-isi">
        <div class="popup__ikon popup__ikon--hati" aria-hidden="true">
            <svg viewBox="0 0 64 56"><use href="#i-hati"/></svg>
        </div>
        <h2 class="popup__judul popup__judul--hijau" id="popup-sukses-judul">Berhasil di simpan!</h2>
        <p class="popup__isi" id="popup-sukses-isi">Wisata favorit mu berhasil tersimpan. Kamu bisa melihat wisata favoritmu di halaman profil.</p>
        <div class="popup__aksi popup__aksi--tunggal">
            <button class="popup__tombol popup__tombol--isi" type="button" id="popup-sukses-ok" autofocus>OK</button>
        </div>
    </dialog>

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

        (function () {
            document.querySelectorAll('.notif--ulasan').forEach(function (n) {
                setTimeout(function () { n.classList.add('hilang'); }, 4000);
                setTimeout(function () { n.style.display = 'none'; }, 4500);
            });
        })();

        var popupLogin = (function () {
            var dialog = document.getElementById('popup-login');
            var judul = document.getElementById('popup-login-judul');
            var isi = document.getElementById('popup-login-isi');
            var nanti = document.getElementById('popup-login-nanti');
            if (!dialog) return { buka: function () {} };

            var teks = {
                favorit: {
                    judul: 'Login untuk Menyimpan Destinasi Favorit',
                    isi: 'Simpan wisata favoritmu setelah login. Silakan login terlebih dahulu untuk menyimpan destinasi favorit wisata ini'
                },
                ulasan: {
                    judul: 'Login untuk Memberikan Rating dan Ulasan',
                    isi: 'Bagikan pengalaman wisatamu setelah login. Silakan login terlebih dahulu untuk memberikan rating dan ulasan wisata ini'
                }
            };

            function tutup() {
                if (typeof dialog.close === 'function') {
                    dialog.close();
                } else {
                    dialog.removeAttribute('open');
                }
            }

            nanti.addEventListener('click', tutup);
            dialog.addEventListener('click', function (e) {
                if (e.target === dialog) tutup();
            });

            return {
                buka: function (jenis) {
                    var t = teks[jenis] || teks.ulasan;
                    judul.textContent = t.judul;
                    isi.textContent = t.isi;
                    if (typeof dialog.showModal === 'function') {
                        dialog.showModal();
                    } else {
                        dialog.setAttribute('open', '');
                    }
                }
            };
        })();

        var popupSukses = (function () {
            var dialog = document.getElementById('popup-sukses');
            var ok = document.getElementById('popup-sukses-ok');
            if (!dialog || !ok) return { buka: function () {} };

            function tutup() {
                if (typeof dialog.close === 'function') {
                    dialog.close();
                } else {
                    dialog.removeAttribute('open');
                }
            }

            ok.addEventListener('click', tutup);
            dialog.addEventListener('click', function (e) {
                if (e.target === dialog) tutup();
            });

            return {
                buka: function () {
                    if (typeof dialog.showModal === 'function') {
                        dialog.showModal();
                    } else {
                        dialog.setAttribute('open', '');
                    }
                }
            };
        })();

        (function () {
            var tombol = document.getElementById('tombol-ulasan');
            var dialog = document.getElementById('dialog-ulasan');
            var batal = document.getElementById('batal-ulasan');
            if (!tombol || !dialog) return;

            tombol.addEventListener('click', function () {
                if (tombol.getAttribute('data-login') !== '1') {
                    popupLogin.buka('ulasan');
                    return;
                }
                if (typeof dialog.showModal === 'function') {
                    dialog.showModal();
                } else {
                    dialog.setAttribute('open', '');
                }
            });

            batal.addEventListener('click', function () {
                if (typeof dialog.close === 'function') {
                    dialog.close();
                } else {
                    dialog.removeAttribute('open');
                }
            });

            dialog.addEventListener('click', function (e) {
                if (e.target === dialog && typeof dialog.close === 'function') dialog.close();
            });
        })();

        (function () {
            var info = document.getElementById('nilai-rating');
            var reset = document.getElementById('reset-rating');
            var baris = document.querySelector('.bintang-input__baris');
            if (!info || !reset || !baris) return;

            var angka = info.querySelector('.nilai__angka');
            var skala = info.querySelector('.nilai__skala');
            var radios = document.querySelectorAll('.bintang-input input[type="radio"]');
            var bisaHover = window.matchMedia && window.matchMedia('(hover: hover)').matches;

            function terpilih() {
                var r = document.querySelector('.bintang-input input[type="radio"]:checked');
                return r ? r.value : '';
            }

            function tampil(nilai, pratinjau) {
                var ada = nilai !== '';
                angka.textContent = ada ? nilai : 'Rate';
                skala.hidden = !ada;
                info.classList.toggle('terpilih', ada && !pratinjau);
                info.classList.toggle('pratinjau', ada && pratinjau);
            }

            function segarkan() {
                var nilai = terpilih();
                tampil(nilai, false);
                reset.hidden = nilai === '';
            }

            radios.forEach(function (r) {
                r.addEventListener('change', segarkan);
            });

            reset.addEventListener('click', function () {
                radios.forEach(function (r) { r.checked = false; });
                segarkan();
            });

            if (bisaHover) {
                baris.querySelectorAll('label').forEach(function (l) {
                    l.addEventListener('mouseenter', function () {
                        tampil(l.getAttribute('for').replace('rating-', ''), true);
                    });
                });
                baris.addEventListener('mouseleave', segarkan);
            }

            segarkan();
        })();

        (function () {
            var form = document.getElementById('form-ulasan');
            var galat = document.getElementById('error-ulasan');
            if (!form) return;
            form.addEventListener('submit', function (e) {
                var rating = form.querySelector('input[name="rating"]:checked');
                var komentar = form.querySelector('textarea[name="komentar"]');
                var pesan = '';
                if (!rating) {
                    pesan = 'Pilih rating terlebih dahulu.';
                } else if (komentar.value.trim() === '') {
                    pesan = 'Ulasan tidak boleh kosong.';
                }
                if (pesan !== '') {
                    e.preventDefault();
                    galat.textContent = pesan;
                    galat.hidden = false;
                    return;
                }
                galat.hidden = true;
                var kirim = form.querySelector('.btn-kirim');
                if (kirim) kirim.disabled = true;
            });
        })();

        (function () {
            var tombol = document.getElementById('tombol-favorit');
            var notif = document.getElementById('notif-favorit');
            if (!tombol) return;
            var sibuk = false;
            var timer = null;

            function tampilkan(tersimpan) {
                tombol.classList.toggle('aktif', tersimpan);
                tombol.setAttribute('aria-pressed', tersimpan ? 'true' : 'false');
                tombol.querySelector('i').className = 'bi ' + (tersimpan ? 'bi-heart-fill' : 'bi-heart');
                tombol.querySelector('span').textContent = tersimpan ? 'Tersimpan Di Favorit' : 'Simpan Favorit';
            }

            function kabar(teks) {
                if (!notif) return;
                notif.textContent = teks;
                notif.hidden = false;
                notif.classList.remove('hilang');
                clearTimeout(timer);
                timer = setTimeout(function () {
                    notif.classList.add('hilang');
                    setTimeout(function () { notif.hidden = true; }, 400);
                }, 4000);
            }

            tombol.addEventListener('click', function () {
                if (tombol.getAttribute('data-login') !== '1') {
                    popupLogin.buka('favorit');
                    return;
                }
                if (sibuk) return;
                sibuk = true;
                tombol.disabled = true;

                var aksi = tombol.classList.contains('aktif') ? 'remove' : 'add';
                var status = 0;

                fetch('../../Backend/api/favorites.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': tombol.getAttribute('data-csrf')
                    },
                    body: JSON.stringify({
                        destination_id: tombol.getAttribute('data-id'),
                        action: aksi
                    })
                })
                    .then(function (r) {
                        status = r.status;
                        return r.json();
                    })
                    .then(function (d) {
                        sibuk = false;
                        tombol.disabled = false;
                        if (status === 401) {
                            tombol.setAttribute('data-login', '0');
                            popupLogin.buka('favorit');
                        } else if (d && d.status === 'success' && d.data) {
                            tampilkan(d.data.is_favorite === true);
                            if (d.data.is_favorite === true) popupSukses.buka();
                        } else {
                            kabar((d && d.message) || 'Gagal memperbarui destinasi favorit.');
                        }
                    })
                    .catch(function () {
                        sibuk = false;
                        tombol.disabled = false;
                        kabar('Gagal memperbarui destinasi favorit.');
                    });
            });
        })();
    </script>
</body>
</html>