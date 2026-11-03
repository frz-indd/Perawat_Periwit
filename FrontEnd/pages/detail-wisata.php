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

function bintangHtml(float $nilai, bool $tanpaSetengah = false): string {
    $nilai    = max(0.0, min(5.0, $nilai));
    if ($tanpaSetengah) {
        $nilai = round($nilai);
    }
    $dibulat  = round($nilai * 2) / 2;
    $penuh    = (int) floor($dibulat);
    $setengah = ($dibulat - $penuh) >= 0.5;

    $html = str_repeat('<svg viewBox="0 0 24 24"><use href="#i-bintang"/></svg>', $penuh);
    if ($setengah) {
        $html .= '<svg class="setengah" viewBox="0 0 12 24"><use href="#i-bintang" width="24" height="24"/></svg>';
    }
    return $html;
}

function koordinatValid($lat, $lng): bool {
    return is_numeric($lat) && is_numeric($lng)
        && (float) $lat >= -90 && (float) $lat <= 90
        && (float) $lng >= -180 && (float) $lng <= 180;
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

function simpanUlasan(mysqli $conn, int $userId, string $tourismId, int $rating, string $komentar): void {
    $kolom = [];
    $hasil = $conn->query('SHOW COLUMNS FROM ulasan');
    if (!$hasil) {
        throw new RuntimeException('Tabel ulasan tidak dapat dibaca.');
    }
    while ($k = $hasil->fetch_assoc()) {
        $kolom[] = $k['Field'];
    }
    $adaTanggal = in_array('tanggal_ulasan', $kolom, true);

    $stmt = $conn->prepare('SELECT Id_ulasan FROM ulasan WHERE Id_user = ? AND Id_wisata = ? ORDER BY Id_ulasan DESC LIMIT 1');
    if (!$stmt) {
        throw new RuntimeException('Query ulasan gagal disiapkan.');
    }
    $stmt->bind_param('is', $userId, $tourismId);
    $stmt->execute();
    $lama = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($lama) {
        $sql = 'UPDATE ulasan SET rating = ?, komentar = ?' . ($adaTanggal ? ', tanggal_ulasan = NOW()' : '') . ' WHERE Id_ulasan = ?';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Query ulasan gagal disiapkan.');
        }
        $idUlasan = (int) $lama['Id_ulasan'];
        $stmt->bind_param('isi', $rating, $komentar, $idUlasan);
    } else {
        $sql = $adaTanggal
            ? 'INSERT INTO ulasan (Id_user, Id_wisata, rating, komentar, tanggal_ulasan) VALUES (?, ?, ?, ?, NOW())'
            : 'INSERT INTO ulasan (Id_user, Id_wisata, rating, komentar) VALUES (?, ?, ?, ?)';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Query ulasan gagal disiapkan.');
        }
        $stmt->bind_param('isis', $userId, $tourismId, $rating, $komentar);
    }
    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Ulasan gagal disimpan.');
    }
    $stmt->close();

    $kolomRata = $conn->query("SHOW COLUMNS FROM wisata LIKE 'average_rating'");
    if ($kolomRata && $kolomRata->num_rows > 0) {
        $stmt = $conn->prepare('UPDATE wisata SET average_rating = (SELECT ROUND(AVG(rating), 2) FROM ulasan WHERE Id_wisata = ?) WHERE id_wisata = ?');
        if ($stmt) {
            $stmt->bind_param('ss', $tourismId, $tourismId);
            $stmt->execute();
            $stmt->close();
        }
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

$tourismRows = fetchTourismRows($conn, ['id_wisata' => $tourismId]);
$tourism = $tourismRows[0] ?? null;
if ($tourism === null) {
    http_response_code(404);
    exit('Destinasi tidak ditemukan.');
}

$galatSimpan   = '';
$ratingPost    = 0;
$komentarPost  = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'ulasan') {
    $tokenKirim   = (string) ($_POST['csrf'] ?? '');
    $nilaiPost    = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT);
    $komentarPost = trim((string) ($_POST['komentar'] ?? ''));
    $ratingPost   = is_int($nilaiPost) ? $nilaiPost : 0;
    $panjang      = mb_strlen($komentarPost, 'UTF-8');

    if (!$sudahLogin) {
        $galatSimpan = 'Silakan login terlebih dahulu untuk memberikan ulasan.';
    } elseif (!hash_equals((string) $_SESSION['csrf'], $tokenKirim)) {
        $galatSimpan = 'Sesi tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif ($ratingPost < 1 || $ratingPost > 5) {
        $galatSimpan = 'Pilih rating terlebih dahulu.';
    } elseif ($panjang < 1 || $panjang > 500) {
        $galatSimpan = 'Ulasan harus berisi 1 sampai 500 karakter.';
    } else {
        try {
            simpanUlasan($conn, (int) $userId, $tourismId, $ratingPost, $komentarPost);
            header('Location: detail-wisata.php?id=' . rawurlencode($tourismId) . '&ulasan=ok#judul-ulasan');
            exit;
        } catch (Throwable $e) {
            $galatSimpan = 'Ulasan gagal disimpan. Silakan coba lagi.';
        }
    }
}

$fasilitasTampil = [
    ['nama' => 'Area parkir', 'ikon' => 'parkir'],
    ['nama' => 'Toilet Umum', 'ikon' => 'toilet'],
    ['nama' => 'Mushola',     'ikon' => 'masjid'],
    ['nama' => 'Spot Foto',   'ikon' => 'kamera'],
];

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

$namaWisata   = (string) $tourism['nama_wisata'];
$alamat       = trim((string) ($tourism['alamat'] ?? ''));
$wilayah      = trim((string) ($tourism['wilayah'] ?? ''));
$lokasiTampil = $alamat !== '' ? $alamat : $wilayah;
$adaLokasi    = $lokasiTampil !== '';
$alamatTampil = $lokasiTampil;
if ($adaLokasi && mb_stripos($lokasiTampil, $namaWisata, 0, 'UTF-8') !== 0) {
    $alamatTampil = $namaWisata . "\n" . $lokasiTampil;
}

$lat = $tourism['latitude'] ?? null;
$lng = $tourism['longitude'] ?? null;
$koordinatTersedia = koordinatValid($lat, $lng);
$petaTersedia = $koordinatTersedia || $adaLokasi;
$petaSrc = $petaTersedia
    ? 'https://maps.google.com/maps?q=' . rawurlencode(
        $koordinatTersedia ? $lat . ',' . $lng : $namaWisata . ', ' . $lokasiTampil
    ) . '&hl=id&z=15&output=embed'
    : '';

$linkMaps = trim((string) ($tourism['Maps_Wisata'] ?? ''));
if (preg_match('#^https?://#i', $linkMaps) === 1) {
    $mapsUrlTersimpan = $linkMaps;
    $mapsUrl = $mapsUrlTersimpan;
} elseif ($koordinatTersedia) {
    $mapsUrlTersimpan = '';
    $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($lat . ',' . $lng);
} elseif ($adaLokasi) {
    $mapsUrlTersimpan = '';
    $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($namaWisata . ', ' . $lokasiTampil);
} else {
    $mapsUrlTersimpan = '';
    $mapsUrl = '';
}
$bisaMaps = $mapsUrl !== '';

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
    'title'        => $namaWisata,
    'review_label' => number_format($jumlahUlasan, 0, ',', '.'),
    'review_count' => $jumlahUlasan,
    'rating'       => $nilaiRating,
    'has_rating'   => $nilaiRating > 0 && $jumlahUlasan > 0,
    'location'     => $tourism['wilayah'],
    'subtitle'     => 'Temukan Informasi ' . $namaWisata . ' dengan lebih detail',
    'description'  => trim((string) ($tourism['deskripsi'] ?? '')),
    'ticket_price' => ($tourism['harga_tiket'] ?? 0) > 0
        ? 'Rp ' . number_format((float) $tourism['harga_tiket'], 0, ',', '.') . '/orang'
        : '',
    'parking'      => $parkir,
    'hours'        => $jamOperasional,
    'distribution' => $distribution,
    'reviews'      => $reviews,
];

$sudahFavorit = $sudahLogin && isTourismFavorite($conn, (int) $userId, $tourismId);
$ulasanSaya   = $sudahLogin ? fetchUlasanSaya($conn, (int) $userId, $tourismId) : [];
$ratingSaya   = isset($ulasanSaya['rating']) ? (int) round((float) $ulasanSaya['rating']) : 0;
$komentarSaya = '';
if ($galatSimpan !== '') {
    $ratingSaya   = $ratingPost;
    $komentarSaya = $komentarPost;
}

$apiFavorit       = '../../api/favorites';
$apiUlasan        = '../../api/destinations/' . rawurlencode($tourismId) . '/reviews';
$apiLokasi        = '../../api/destinations/' . rawurlencode($tourismId) . '/location';
$urlSelesaiUlasan = 'detail-wisata.php?id=' . rawurlencode($tourismId) . '&ulasan=ok#judul-ulasan';

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
    <link href="https://fonts.googleapis.com/css2?family=Fragment+Mono:ital@0;1&family=Frank+Ruhl+Libre:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=Kaushan+Script&family=Noto+Serif:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../CSS/edit-profil.css?v=20261031">
    <link rel="stylesheet" href="../CSS/theme.css?v=20261019-2">
    <link rel="stylesheet" href="../CSS/detail-wisata.css?v=final-22">
    <link rel="stylesheet" href="../CSS/navbar-common.css?v=4">
</head>
<body class="halaman-detail site-navbar-page">

    <svg class="sprite" aria-hidden="true">
        <defs>
            <symbol id="ikon-profil" viewBox="0 0 40 40">
                <circle cx="20" cy="20" r="18.2" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="20" cy="16.2" r="5.8" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M9 33.5C9.7 29.2 12.2 26 15.2 25.2C17 26.6 23 26.6 24.8 25.2C27.8 26 30.3 29.2 31 33.5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </symbol>

            <symbol id="i-bintang" viewBox="0 0 24 24">
                <path d="M12 1.8l3.1 6.6 7.2.9-5.3 4.9 1.4 7.1L12 17.7l-6.4 3.6 1.4-7.1L1.7 9.3l7.2-.9L12 1.8z"/>
            </symbol>

            <symbol id="i-ulang" viewBox="0 0 24 24">
                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                <path d="M3 3v5h5"/>
            </symbol>

            <symbol id="i-lokasi" viewBox="0 0 30 40">
                <path d="M15 26.5A15 6.5 0 0 0 15 39.5Z" fill="#59d65a"/>
                <path d="M15 26.5A15 6.5 0 0 1 15 39.5Z" fill="#1fc27d"/>
                <path d="M15 36C10 30 3.5 22.5 3.5 13A11.5 11.5 0 0 1 15 1.5Z" fill="#ff4a26"/>
                <path d="M15 36C20 30 26.5 22.5 26.5 13A11.5 11.5 0 0 0 15 1.5Z" fill="#e5301c"/>
                <circle cx="15" cy="13" r="4.8" fill="#fff"/>
            </symbol>

            <symbol id="i-pin-hijau" viewBox="0 0 39.31 50.5">
                <path fill="currentColor" fill-rule="evenodd" d="M0 40.19 0.19 42.19 1 43.88 2.31 45.38 5 47.25 7.88 48.56 11.5 49.62 17.38 50.44 21.62 50.44 26 49.94 30.19 48.94 32.81 47.94 35.5 46.44 37.38 44.88 38.69 43 39.25 40.88 38.88 39.06 37.56 36.94 35 34.88 32.5 33.69 31.31 33.88 30.88 34.38 30.81 35.19 33.94 38.31 34.25 39.25 34.06 40.69 33.25 41.88 31.75 43.06 27.12 44.88 23.38 45.5 18.44 45.75 12.06 44.88 7.75 43.25 5.81 41.75 4.94 39.81 5.56 37.81 8.12 35.25 7.94 34.19 7.06 33.69 4.94 34.38 2.38 36.19 0.69 38.25ZM18.94 9.62 16.88 10.38 15.94 11.19 15.06 12.75 14.75 14.69 15.5 16.75 16.69 18.06 18.06 18.81 20.31 19 21.94 18.44 23.56 16.94 24.25 15.56 24.38 13.69 23.69 11.81 22.5 10.5 20.62 9.69ZM17.94 0 15.31 0.5 12.56 1.62 10.44 3 8.5 4.81 6.75 7.25 5.75 9.44 5 12.25 4.88 15.06 6.19 20.75 9.69 27.75 15.62 36.19 18.69 39.69 19.88 40 21.12 39.06 25.94 33 31 25.06 33.69 18.5 34.25 15.88 34.31 13.25 33.88 10.81 32.38 7.12 30.69 4.81 29.12 3.31 26.38 1.5 23.44 0.38 21.38 0ZM19 4.69 22.5 5.06 25.94 6.94 28.19 9.62 29.12 11.81 29.38 13.38 28.94 17.56 26.44 23.38 21.69 30.75 20.44 32.19 19.75 32.5 18.88 32.25 17.94 31.25 12.69 23.19 10.62 18.69 9.81 15.06 10.19 11.5 11 9.62 12.12 8.06 14.06 6.31 15.69 5.44Z"/>
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
                <path d="M1.5 14.2 4 8.4C4.5 7 5.6 6.2 7 6.2h9c1.4 0 2.5.8 3 2.2l2.5 5.8h.5c1 0 1.5.8 1.5 1.8v6.2c0 1-.6 1.6-1.6 1.6H20v1.4c0 .7-.6 1.2-1.2 1.2h-2c-.7 0-1.2-.5-1.2-1.2v-1.4H8.4v1.4c0 .7-.6 1.2-1.2 1.2h-2c-.7 0-1.2-.5-1.2-1.2v-1.4H2.6C1.6 24.2 1 23.6 1 22.6V16c0-1 .5-1.8 1.5-1.8z"/>
                <path d="M6.6 8.4h10.8l1.8 5.4H4.8z" fill="#eef2ee"/>
                <circle cx="5.2" cy="18.6" r="1.7" fill="#eef2ee"/>
                <circle cx="18.8" cy="18.6" r="1.7" fill="#eef2ee"/>
                <rect x="9" y="19" width="6" height="1.4" fill="#eef2ee"/>
                <rect x="26" y="7" width="1.4" height="20"/>
                <rect x="23.2" y="0" width="7" height="7.6" rx="1.2"/>
                <path d="M25.7 6.4V1.8h1.7a1.5 1.5 0 0 1 0 3h-1.7" fill="none" stroke="#eef2ee" stroke-width="1.1"/>
            </symbol>

            <symbol id="i-toilet" viewBox="0 0 32 28">
                <circle cx="6.6" cy="3.2" r="2.7"/>
                <path d="M3.4 7H9.8c1.2 0 2.2 1 2.2 2.2V16.4H10V26.4H7.6V17.4H5.6V26.4H3.2V16.4H1.2V9.2C1.2 8 2.2 7 3.4 7z"/>
                <rect x="15.2" y="0.5" width="1.2" height="26.5"/>
                <circle cx="25" cy="3.2" r="2.7"/>
                <path d="M25 7c-1.8 0-3 1.1-3.4 2.8L19.4 17.6H22.2V26.4H24.2V20.2H25.8V26.4H27.8V17.6H30.6L28.4 9.8C28 8.1 26.8 7 25 7z"/>
            </symbol>

            <symbol id="i-masjid" viewBox="0 0 28 28">
                <path d="M1.5 15.2C1.5 9.6 6 7 9 1.8C12 7 16.5 9.6 16.5 15.2z"/>
                <rect x="1" y="14.4" width="16" height="12.6"/>
                <rect x="1" y="14.4" width="16" height="1.3" fill="#eef2ee"/>
                <path d="M7.2 27V22.6a1.8 1.8 0 0 1 3.6 0V27z" fill="#eef2ee"/>
                <path d="M19.2 27V8.6L21.5 3.4 23.8 8.6V27z"/>
                <rect x="22.2" y="9.4" width="0.9" height="17" fill="#eef2ee" opacity=".55"/>
                <rect x="0" y="26.2" width="25" height="1.6"/>
            </symbol>

            <symbol id="i-tiket" viewBox="0 0 32 24">
                <path d="M5.6 6.6 22.4 1.2Q24 .8 24.2 2.6L24.6 6.6z"/>
                <path d="M3.8 7.4H28.2Q30 7.4 30 9.2V11.6a2.2 2.2 0 0 0 0 4.4V21.2Q30 23 28.2 23H3.8Q2 23 2 21.2V16a2.2 2.2 0 0 0 0-4.4V9.2Q2 7.4 3.8 7.4z"/>
                <rect x="7" y="12" width="6" height="1.2" fill="#eef2ee"/>
                <rect x="7" y="15" width="9.5" height="1.5" fill="#eef2ee"/>
                <rect x="20.2" y="7.4" width="1" height="15.6" fill="#eef2ee" opacity=".55"/>
            </symbol>

            <symbol id="i-alarm" viewBox="0 0 32 32">
                <circle cx="16" cy="17.5" r="10.2" fill="none" stroke="currentColor" stroke-width="3"/>
                <path d="M16 11.6v6.4l4.2 2.4" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M3.6 10.2A7.2 7.2 0 0 1 10 3.8M28.4 10.2A7.2 7.2 0 0 0 22 3.8" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round"/>
                <path d="M7.4 27.2 5 30M24.6 27.2 27 30" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
            </symbol>

            <symbol id="i-gembok" viewBox="0 0 42 46">
                <rect x="3" y="19.5" width="36" height="23.5" rx="5" fill="none" stroke="currentColor" stroke-width="3.2"/>
                <path d="M10 19.5V14.5a11 11 0 0 1 22 0v5" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round"/>
                <path d="M21 27.5v8" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round"/>
            </symbol>

            <symbol id="i-hati" viewBox="0 0 64 56">
                <path d="M32 54C14 40 3 30 3 18.5 3 9.9 9.6 3 18 3c5.6 0 10.6 3 14 8 3.4-5 8.4-8 14-8 8.4 0 15 6.9 15 15.5C61 30 50 40 32 54Z" fill="currentColor"/>
            </symbol>

            <symbol id="i-sampah" viewBox="0 0 34 34">
                <path fill="currentColor" fill-rule="evenodd" d="M12 0h10a1 1 0 0 1 1 1v5H11V1a1 1 0 0 1 1-1zM13.2 2.6h7.6v3.4h-7.6z"/>
                <rect x="0" y="4.8" width="33.8" height="2.5" rx="1.25" fill="currentColor"/>
                <path d="M3.8 6h26.2v25.6a1.7 1.7 0 0 1-1.7 1.7H5.5a1.7 1.7 0 0 1-1.7-1.7z" fill="currentColor"/>
                <rect x="11.5" y="11.8" width="3" height="14.8" rx="1.5" style="fill: var(--lubang, #fff)"/>
                <rect x="19" y="11.8" width="3" height="14.8" rx="1.5" style="fill: var(--lubang, #fff)"/>
            </symbol>
        </defs>
    </svg>

    <div class="bagian-atas">
    <header class="ep-navbar site-navbar">
        <div class="ep-navbar__inner">
            <a class="ep-logo" href="../../index.php">Explore Majaku</a>
            <nav class="ep-menu" aria-label="Menu utama">
                <a class="ep-menu__beranda" href="../../index.php">Beranda</a>
                <details class="category-dropdown ep-menu__kategori">
                    <summary>
                        <span>Kategori</span>
                        <span class="ep-menu__panah" aria-hidden="true"></span>
                    </summary>
                    <div class="category-menu">
                        <a href="kategori.php?kategori=alam">Wisata Alam &amp; Pegunungan</a>
                        <a href="kategori.php?kategori=air-curug">Wisata Air &amp; Curug</a>
                        <a href="kategori.php?kategori=buatan-kuliner">Wisata Buatan &amp; Kuliner</a>
                        <a href="kategori.php?kategori=sejarah-budaya">Wisata Sejarah &amp; Budaya</a>
                    </div>
                </details>
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
    </div>

    <main class="detail">

        <div class="notif notif--favorit" id="notif-favorit" role="status" hidden></div>

        <section class="ringkasan" aria-labelledby="judul-wisata">
            <div class="ringkasan__teks">
                <h1 id="judul-wisata"><?= e($destination['title']) ?></h1>

                <div class="ringkasan__rating">
                    <?php if ($destination['has_rating']): ?>
                        <span class="bintang" aria-hidden="true"><?= bintangHtml($destination['rating'], true) ?></span>
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
                        data-endpoint="<?= e($apiFavorit) ?>"
                        data-api-url="<?= e($apiFavorit) ?>"
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

                <article class="kartu kartu--fasilitas">
                    <h3>Fasilitas</h3>
                    <ul>
                        <?php foreach ($fasilitasTampil as $fasilitas): ?>
                            <li>
                                <?php
                                echo match ($fasilitas['ikon']) {
                                    'parkir' => '<svg class="ikon-fas ikon-parkir" viewBox="0 0 32 28" aria-hidden="true"><use href="#i-parkir"/></svg>',
                                    'toilet' => '<svg class="ikon-fas ikon-toilet" viewBox="0 0 32 28" aria-hidden="true"><use href="#i-toilet"/></svg>',
                                    'masjid' => '<svg class="ikon-fas ikon-masjid" viewBox="0 0 28 28" aria-hidden="true"><use href="#i-masjid"/></svg>',
                                    default  => '<span class="ikon-fas ikon-kamera" aria-hidden="true"><i class="bi bi-camera-fill"></i></span>',
                                };
                                ?>
                                <span class="fasilitas__nama"><?= e($fasilitas['nama']) ?></span>
                            </li>
                        <?php endforeach; ?>
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
                        <i class="bi bi-alarm ikon-jam" aria-hidden="true"></i>
                        <span><?= nilaiAtauKet($destination['hours']) ?></span>
                    </div>
                </article>
            </div>
        </section>

        <section class="blok blok--lokasi" aria-labelledby="judul-lokasi">
            <h2 class="blok__judul" id="judul-lokasi">LOKASI WISATA</h2>
            <p class="blok__sub">Temukan lokasi <?= e(mb_strtolower($destination['title'], 'UTF-8')) ?> dengan mudah di peta/maps</p>

            <div class="lokasi-grid<?= $petaTersedia ? '' : ' lokasi-grid--kosong' ?>" id="lokasi-grid"
                 data-endpoint="<?= e($apiLokasi) ?>"
                 data-nama="<?= e($namaWisata) ?>"
                 data-wilayah="<?= e($wilayah) ?>"
                 data-map-url="<?= e($mapsUrlTersimpan) ?>">
                <div class="peta" id="peta"<?= $petaTersedia ? '' : ' hidden' ?>>
                    <iframe id="peta-frame"
                            title="Peta lokasi <?= e($destination['title']) ?>"
                            <?= $petaTersedia ? 'src="' . e($petaSrc) . '"' : '' ?>
                            allowfullscreen
                            referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>

                <div class="peta peta--kosong" id="peta-kosong" role="img" aria-label="Peta lokasi belum tersedia"<?= $petaTersedia ? ' hidden' : '' ?>>
                    <svg class="peta__ikon" viewBox="0 0 40 46" aria-hidden="true"><use href="#i-pin-coret"/></svg>
                    <h3>Koordinat Wisata Tidak Tersedia</h3>
                    <p>Peta belum dapat ditampilkan karena koordinat lokasi belum tersedia</p>
                </div>

                <article class="kartu-alamat<?= $adaLokasi ? '' : ' kartu-alamat--kosong' ?>" id="kartu-alamat">
                    <svg class="kartu-alamat__ikon" viewBox="0 0 39.31 50.5" aria-hidden="true"><use href="#i-pin-hijau"/></svg>
                    <h3 id="alamat-judul">Alamat tersedia</h3>
                    <p id="alamat-teks"<?= $adaLokasi ? '' : ' class="belum"' ?>><?= $adaLokasi ? e($alamatTampil) : 'Lokasi Akan Di perbarui Segera' ?></p>
                    <a class="tombol-maps" id="tombol-maps"
                       <?= $bisaMaps ? 'href="' . e($mapsUrl) . '" target="_blank" rel="noopener noreferrer"' : 'aria-disabled="true" tabindex="-1"' ?>>
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
                        <span class="bintang bintang--besar" aria-hidden="true"><?= bintangHtml($destination['rating'], true) ?></span>
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
                            <svg class="ulasan__avatar" viewBox="0 0 40 40" aria-hidden="true"><use href="#ikon-profil"/></svg>
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

            <button class="tombol-ulasan" type="button" id="tombol-ulasan"
                    data-login="<?= $sudahLogin ? '1' : '0' ?>" aria-haspopup="dialog"
                    data-api-url="<?= e($apiUlasan) ?>">
                Tambah Ulasan dan Rating
            </button>
        </section>
    </main>

    <dialog class="dialog-ulasan" id="dialog-ulasan" aria-labelledby="judul-dialog">
        <form id="form-ulasan" method="post" action="detail-wisata.php?id=<?= e(rawurlencode($tourismId)) ?>" novalidate
              data-endpoint="<?= e($apiUlasan) ?>"
              data-csrf="<?= e($_SESSION['csrf']) ?>"
              data-sukses="<?= e($urlSelesaiUlasan) ?>">

            <input type="hidden" name="aksi" value="ulasan">
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
                      placeholder="Ceritakan Pengalamanmu di sini..."><?= e($komentarSaya) ?></textarea>
            <p class="dialog__catatan">Jika kamu sudah pernah mengulas, ulasan yang kamu kirim akan menggantikan ulasan sebelumnya.</p>

            <p class="dialog__error" id="error-ulasan" role="alert"<?= $galatSimpan === '' ? ' hidden' : '' ?>><?= e($galatSimpan) ?></p>

            <div class="dialog__aksi">
                <button type="button" class="btn-batal" id="batal-ulasan">Batal</button>
                <button type="submit" class="btn-kirim">Kirim Ulasan</button>
            </div>
        </form>
    </dialog>

    <dialog class="popup" id="popup-login" aria-labelledby="popup-login-judul" aria-describedby="popup-login-isi">
        <div class="popup__ikon popup__ikon--gembok" aria-hidden="true">
            <svg viewBox="0 0 42 46"><use href="#i-gembok"/></svg>
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

    <dialog class="popup" id="popup-terhapus" aria-labelledby="popup-terhapus-judul" aria-describedby="popup-terhapus-isi">
        <div class="popup__ikon popup__ikon--sampah" aria-hidden="true">
            <svg viewBox="0 0 48 56"><use href="#i-sampah"/></svg>
        </div>
        <h2 class="popup__judul popup__judul--hijau" id="popup-terhapus-judul">Berhasil di Hapus!</h2>
        <p class="popup__isi" id="popup-terhapus-isi">Wisata favorit mu berhasil dihapus dari destinasi favorit</p>
        <div class="popup__aksi popup__aksi--tunggal">
            <button class="popup__tombol popup__tombol--isi" type="button" id="popup-terhapus-ok" autofocus>OK</button>
        </div>
    </dialog>

    <dialog class="popup" id="popup-konfirmasi" aria-labelledby="popup-konfirmasi-judul" aria-describedby="popup-konfirmasi-isi">
        <div class="popup__ikon popup__ikon--sampah" aria-hidden="true">
            <svg viewBox="0 0 48 56"><use href="#i-sampah"/></svg>
        </div>
        <h2 class="popup__judul popup__judul--hijau popup__judul--tanya" id="popup-konfirmasi-judul">Apakah Anda yakin ingin menghapus destinasi ini dari daftar favorit?</h2>
        <p class="popup__isi" id="popup-konfirmasi-isi">Wisata favorit mu akan di hapus dari daftar destinasi favorit</p>
        <div class="popup__aksi">
            <button class="popup__tombol popup__tombol--isi" type="button" id="popup-konfirmasi-hapus">Hapus</button>
            <button class="popup__tombol popup__tombol--garis" type="button" id="popup-konfirmasi-batal" autofocus>Batal</button>
        </div>
    </dialog>

    <script>
        (function () {
            var nav = document.querySelector('.ep-navbar');
            var hero = document.getElementById('hero-wisata');
            if (!nav || !hero) return;
            var tutup = 24;

            function rapat() {
                var foto = hero.querySelector('img');
                var el = (!foto || hero.classList.contains('tanpa-foto'))
                    ? hero.querySelector('.hero-wisata__kosong')
                    : foto;
                if (!el) return;
                hero.style.removeProperty('margin-top');
                var selisih = el.getBoundingClientRect().top - nav.getBoundingClientRect().bottom + tutup;
                if (Math.abs(selisih) > 0.5) {
                    hero.style.setProperty('margin-top', (-tutup - selisih) + 'px', 'important');
                }
            }

            rapat();
            window.addEventListener('resize', rapat);
            window.addEventListener('load', rapat);
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(rapat);
            }
            if (window.ResizeObserver) {
                new ResizeObserver(rapat).observe(nav);
            }
            var gambar = hero.querySelector('img');
            if (gambar) gambar.addEventListener('load', rapat);
        })();

        function buatDialog(id) {
            var el = document.getElementById(id);
            var api = {
                el: el,
                buka: function () {
                    if (!el) return;
                    if (typeof el.showModal === 'function') {
                        if (!el.open) el.showModal();
                    } else {
                        el.setAttribute('open', '');
                    }
                },
                tutup: function () {
                    if (!el) return;
                    if (typeof el.close === 'function') {
                        el.close();
                    } else {
                        el.removeAttribute('open');
                    }
                }
            };
            if (el) {
                el.addEventListener('click', function (e) {
                    if (e.target !== el) return;
                    var r = el.getBoundingClientRect();
                    var dalam = e.clientX >= r.left && e.clientX <= r.right &&
                                e.clientY >= r.top && e.clientY <= r.bottom;
                    if (!dalam) api.tutup();
                });
            }
            return api;
        }

        (function () {
            if (window.history && typeof history.replaceState === 'function' && /[?&]ulasan=/.test(location.search)) {
                var u = new URL(location.href);
                u.searchParams.delete('ulasan');
                history.replaceState(null, '', u.pathname + u.search + u.hash);
            }
        })();

        (function () {
            var grid = document.getElementById('lokasi-grid');
            if (!grid) return;

            var peta = document.getElementById('peta');
            var petaKosong = document.getElementById('peta-kosong');
            var frame = document.getElementById('peta-frame');
            var kartu = document.getElementById('kartu-alamat');
            var judul = document.getElementById('alamat-judul');
            var teks = document.getElementById('alamat-teks');
            var maps = document.getElementById('tombol-maps');
            var nama = grid.getAttribute('data-nama') || '';
            var wilayah = grid.getAttribute('data-wilayah') || '';
            var mapsUrlAwal = grid.getAttribute('data-map-url') || '';
            var endpoint = grid.getAttribute('data-endpoint');

            function koordinatValid(lat, lng) {
                if (lat === null || lng === null || lat === '' || lng === '') return false;
                lat = Number(lat);
                lng = Number(lng);
                return isFinite(lat) && isFinite(lng) &&
                       lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180;
            }

            function terapkan(d) {
                var alamat = String(d.alamat === null || d.alamat === undefined ? '' : d.alamat).trim();
                var lokasiTampil = alamat !== '' ? alamat : wilayah;
                var koordinatAda = d.koordinat_lengkap === true && koordinatValid(d.latitude, d.longitude);
                var adaLokasi = lokasiTampil !== '';
                var adaPeta = koordinatAda || adaLokasi;

                grid.classList.toggle('lokasi-grid--kosong', !adaPeta);
                peta.hidden = !adaPeta;
                petaKosong.hidden = adaPeta;

                if (adaPeta) {
                    var query = koordinatAda
                        ? d.latitude + ',' + d.longitude
                        : nama + ', ' + lokasiTampil;
                    var src = 'https://maps.google.com/maps?q=' +
                        encodeURIComponent(query) +
                        '&hl=id&z=15&output=embed';
                    if (frame.getAttribute('src') !== src) frame.setAttribute('src', src);
                }

                kartu.classList.toggle('kartu-alamat--kosong', !adaLokasi);
                judul.textContent = 'Alamat tersedia';
                teks.classList.toggle('belum', !adaLokasi);
                if (!adaLokasi) {
                    teks.textContent = 'Lokasi Akan Di perbarui Segera';
                } else {
                    teks.textContent = lokasiTampil.toLowerCase().indexOf(nama.toLowerCase()) === 0
                        ? lokasiTampil
                        : nama + '\n' + lokasiTampil;
                }

                var mapsUrl = String(d.Maps_Wisata || d.maps_url || '').trim();
                if (!/^https?:\/\//i.test(mapsUrl)) {
                    mapsUrl = mapsUrlAwal;
                }
                if (!/^https?:\/\//i.test(mapsUrl)) {
                    mapsUrl = koordinatAda
                        ? 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(d.latitude + ',' + d.longitude)
                        : (adaLokasi
                            ? 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(nama + ', ' + lokasiTampil)
                            : '');
                }
                if (mapsUrl !== '') {
                    maps.setAttribute('href', mapsUrl);
                    maps.setAttribute('target', '_blank');
                    maps.setAttribute('rel', 'noopener noreferrer');
                    maps.removeAttribute('aria-disabled');
                    maps.removeAttribute('tabindex');
                } else {
                    maps.removeAttribute('href');
                    maps.removeAttribute('target');
                    maps.removeAttribute('rel');
                    maps.setAttribute('aria-disabled', 'true');
                    maps.setAttribute('tabindex', '-1');
                }
            }

            fetch(endpoint, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            })
                .then(function (r) {
                    if (!r.ok) throw new Error('Lokasi wisata gagal dimuat (' + r.status + ').');
                    return r.json();
                })
                .then(function (j) {
                    if (!j || j.status !== 'success' || !j.data) {
                        throw new Error('Respons lokasi wisata tidak valid.');
                    }
                    terapkan(j.data);
                })
                .catch(function (error) {
                    console.error('Gagal memperbarui alamat dan peta wisata:', error);
                });
        })();

        var popupLogin = (function () {
            var dlg = buatDialog('popup-login');
            var judul = document.getElementById('popup-login-judul');
            var isi = document.getElementById('popup-login-isi');
            var nanti = document.getElementById('popup-login-nanti');
            if (!dlg.el) return { buka: function () {}, tutup: function () {} };

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

            if (nanti) nanti.addEventListener('click', dlg.tutup);

            return {
                tutup: dlg.tutup,
                buka: function (jenis) {
                    var t = teks[jenis] || teks.ulasan;
                    judul.textContent = t.judul;
                    isi.textContent = t.isi;
                    dlg.buka();
                }
            };
        })();

        var popupSukses = (function () {
            var dlg = buatDialog('popup-sukses');
            var ok = document.getElementById('popup-sukses-ok');
            if (ok) ok.addEventListener('click', dlg.tutup);
            return { buka: dlg.buka, tutup: dlg.tutup };
        })();

        var popupTerhapus = (function () {
            var dlg = buatDialog('popup-terhapus');
            var ok = document.getElementById('popup-terhapus-ok');
            if (ok) ok.addEventListener('click', dlg.tutup);
            return { buka: dlg.buka, tutup: dlg.tutup };
        })();

        var popupKonfirmasi = (function () {
            var dlg = buatDialog('popup-konfirmasi');
            var hapus = document.getElementById('popup-konfirmasi-hapus');
            var batal = document.getElementById('popup-konfirmasi-batal');
            var saatHapus = function () {};

            if (batal) batal.addEventListener('click', dlg.tutup);
            if (hapus) {
                hapus.addEventListener('click', function () {
                    dlg.tutup();
                    saatHapus();
                });
            }

            return {
                buka: dlg.buka,
                tutup: dlg.tutup,
                setelahKonfirmasi: function (fn) { saatHapus = fn; }
            };
        })();

        var dialogUlasan = buatDialog('dialog-ulasan');

        (function () {
            var tombol = document.getElementById('tombol-ulasan');
            var batal = document.getElementById('batal-ulasan');
            if (!tombol || !dialogUlasan.el) return;

            tombol.addEventListener('click', function () {
                if (tombol.getAttribute('data-login') !== '1') {
                    popupLogin.buka('ulasan');
                    return;
                }
                dialogUlasan.buka();
            });

            if (batal) batal.addEventListener('click', dialogUlasan.tutup);
            var galatAwal = document.getElementById('error-ulasan');
            if (galatAwal && !galatAwal.hidden && tombol.getAttribute('data-login') === '1') {
                dialogUlasan.buka();
            }
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
                baris.classList.toggle('terkunci', nilai !== '');
                radios.forEach(function (r) {
                    r.disabled = nilai !== '' && r.value !== nilai;
                });
            }

            radios.forEach(function (r) {
                r.addEventListener('change', segarkan);
                r.addEventListener('click', segarkan);
            });

            reset.addEventListener('click', function () {
                radios.forEach(function (r) { r.checked = false; });
                segarkan();
            });

            if (bisaHover) {
                baris.querySelectorAll('label').forEach(function (l) {
                    l.addEventListener('mouseenter', function () {
                        if (!terpilih()) {
                            tampil(l.getAttribute('for').replace('rating-', ''), true);
                        }
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
            var sibuk = false;

            function tampilGalat(pesan) {
                galat.textContent = pesan;
                galat.hidden = false;
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (sibuk) return;

                var rating = form.querySelector('input[name="rating"]:checked');
                var komentar = form.querySelector('textarea[name="komentar"]');
                var teks = komentar.value.trim();

                if (!rating) {
                    tampilGalat('Pilih rating terlebih dahulu.');
                    return;
                }
                if (teks === '') {
                    tampilGalat('Ulasan tidak boleh kosong.');
                    return;
                }

                galat.hidden = true;
                sibuk = true;
                var kirim = form.querySelector('.btn-kirim');
                if (kirim) kirim.disabled = true;

                function selesai() {
                    sibuk = false;
                    if (kirim) kirim.disabled = false;
                }

                fetch(form.getAttribute('data-endpoint'), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-Token': form.getAttribute('data-csrf')
                    },
                    body: JSON.stringify({
                        rating: parseInt(rating.value, 10),
                        text: teks.slice(0, 500)
                    })
                })
                    .then(function (r) {
                        status = r.status;
                        return r.json().catch(function () { return null; });
                    })
                    .then(function (d) {
                        if (d && d.status === 'success' && status >= 200 && status < 300) {
                            window.location.href = form.getAttribute('data-sukses');
                            return;
                        }
                        if (status === 401) {
                            selesai();
                            dialogUlasan.tutup();
                            popupLogin.buka('ulasan');
                            return;
                        }
                        if (status === 422 && d && d.message) {
                            selesai();
                            tampilGalat(d.message);
                            return;
                        }
                        form.submit();
                    })
                    .catch(function () {
                        form.submit();
                    });
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

            function kirim(aksi) {
                if (sibuk) return;
                sibuk = true;
                tombol.disabled = true;
                var status = 0;

                fetch(tombol.getAttribute('data-endpoint'), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
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
                            return;
                        }

                        if (d && d.status === 'success' && d.data) {
                            var tersimpan = d.data.is_favorite === true;
                            tampilkan(tersimpan);
                            if (aksi === 'add' && tersimpan) {
                                popupSukses.buka();
                            } else if (aksi === 'remove' && !tersimpan) {
                                popupTerhapus.buka();
                            }
                            return;
                        }

                        kabar((d && d.message) || 'Gagal memperbarui destinasi favorit.');
                    })
                    .catch(function () {
                        sibuk = false;
                        tombol.disabled = false;
                        kabar('Gagal memperbarui destinasi favorit.');
                    });
            }

            popupKonfirmasi.setelahKonfirmasi(function () {
                kirim('remove');
            });

            tombol.addEventListener('click', function () {
                if (tombol.getAttribute('data-login') !== '1') {
                    popupLogin.buka('favorit');
                    return;
                }
                if (sibuk) return;
                if (tombol.classList.contains('aktif')) {
                    popupKonfirmasi.buka();
                    return;
                }
                kirim('add');
            });
        })();
    </script>
</body>
</html>