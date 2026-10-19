<?php
require_once __DIR__ . '/../../Backend/database/koneksi.php';
require_once __DIR__ . '/../../Backend/database/wisata_repository.php';

$tourismId = $_GET['id'] ?? '';
if (!is_string($tourismId) || preg_match('/^[KM][0-9]{5,}$/', $tourismId) !== 1) {
    http_response_code(404);
    exit('Destinasi tidak ditemukan.');
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
    $roundedRating = max(1, min(5, (int) round($review['rating'])));
    $ratingCounts[$roundedRating]++;
}

$ratingDistribution = [];
foreach ([5, 4, 3, 2, 1] as $stars) {
    $ratingDistribution[] = [
        'stars' => $stars,
        'percentage' => count($reviewRows) > 0
            ? (int) round($ratingCounts[$stars] / count($reviewRows) * 100)
            : 0
    ];
}

$reviews = array_map(static function (array $review): array {
    return [
        'name' => $review['nama'],
        'rating' => (int) round($review['rating']),
        'age' => date('d M Y', strtotime($review['tanggal_ulasan'])),
        'comment' => $review['komentar']
    ];
}, $reviewRows);

$hasRating = $tourism['rating'] !== null && $tourism['jumlah_ulasan'] > 0;
$destination = [
    'id' => $tourism['id_wisata'],
    'image' => $tourism['gambar'],
    'title' => $tourism['nama_wisata'],
    'rating' => $tourism['rating'],
    'review_count' => $tourism['jumlah_ulasan'],
    'location' => $tourism['wilayah'],
    'subtitle' => $tourism['deskripsi'] ?? 'Informasi destinasi wisata.',
    'description' => $tourism['deskripsi'] ?? 'Deskripsi belum tersedia.',
    'facilities' => $facilities,
    'ticket_price' => $tourism['harga_tiket'] > 0
        ? 'Rp ' . number_format($tourism['harga_tiket'], 0, ',', '.')
        : 'Belum tersedia',
    'hours' => $tourism['jam_buka'] !== null && $tourism['jam_tutup'] !== null
        ? substr($tourism['jam_buka'], 0, 5) . ' - ' . substr($tourism['jam_tutup'], 0, 5)
        : 'Belum tersedia',
    'address' => $tourism['alamat'] ?: 'Alamat belum tersedia.',
    'maps_url' => $tourism['Maps_Wisata'],
    'rating_distribution' => $ratingDistribution,
    'reviews' => $reviews,
    'has_rating' => $hasRating
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($destination['title'], ENT_QUOTES, 'UTF-8'); ?> | Explore Majaku</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kaushan+Script&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../CSS/detail-wisata.css?v=20261014-4">
    <link rel="stylesheet" href="../CSS/site.css?v=20261019">
</head>
<body class="destination-detail-page">
    <div class="destination-detail-shell">
        <header class="detail-header">
            <nav class="detail-navbar" aria-label="Navigasi utama">
                <a class="detail-brand" href="../../index.php">Explore Majaku</a>
                <div class="detail-nav-links">
                    <a href="../../index.php">Beranda</a>
                    <a href="kategori.php?kategori=alam">kategori</a>
                    <a href="tentang.php">Tentang</a>
                </div>
                <div class="detail-nav-icons">
                    <a href="hasil_pencarian.php" aria-label="Cari">
                        <svg viewBox="0 0 40 40" aria-hidden="true"><circle cx="15.5" cy="15.5" r="11"></circle><path d="m23.5 23.5 10.5 10.5"></path></svg>
                    </a>
                    <a href="profil.php" aria-label="Profil">
                        <svg viewBox="0 0 40 40" aria-hidden="true"><circle cx="20" cy="20" r="18"></circle><circle cx="20" cy="15" r="5"></circle><path d="M9 31c1.7-5 5.5-7.5 11-7.5S29.3 26 31 31"></path></svg>
                    </a>
                </div>
            </nav>
        </header>

    <main class="detail-main">
        <div class="detail-hero" style="background-image: url('../../<?= htmlspecialchars($destination['image'] ?: 'FrontEnd/assets/images/img.png', ENT_QUOTES, 'UTF-8'); ?>')" role="img" aria-label="<?= htmlspecialchars($destination['title'], ENT_QUOTES, 'UTF-8'); ?>"></div>
        <div class="detail-content">
            <section class="destination-summary" aria-labelledby="destination-title">
                <div class="destination-heading">
                    <h1 id="destination-title"><?= htmlspecialchars($destination['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
                    <div class="destination-rating">
                        <?php if ($destination['has_rating']): ?>
                            <span class="rating-stars" aria-hidden="true">★</span>
                            <span class="rating-score"><?= number_format($destination['rating'], 1, ',', '.'); ?>/5 (<?= (int) $destination['review_count']; ?> ulasan)</span>
                        <?php else: ?>
                            <span class="rating-score">Belum ada ulasan</span>
                        <?php endif; ?>
                    </div>
                    <p class="destination-location">
                        <svg viewBox="0 0 28 28" aria-hidden="true">
                            <path class="map-pin" d="M14 24.5s8.1-7.2 8.1-13.2A8.1 8.1 0 1 0 5.9 11.3c0 6 8.1 13.2 8.1 13.2Z"></path>
                            <circle class="map-pin-center" cx="14" cy="10.2" r="3.2"></circle>
                            <path class="map-ground" d="M6.7 21.5c2.3-1 4.1-1 5.9-.2 1.8.9 3.5.9 5.9-.1"></path>
                        </svg>
                        <span><?= htmlspecialchars($destination['location'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </p>
                </div>
                <button class="favorite-button" type="button">+ Tambah ke Favorit</button>
            </section>

            <section class="tour-information" aria-labelledby="information-title">
                <h2 id="information-title">INFORMASI WISATA</h2>
                <p class="information-subtitle"><?= htmlspecialchars($destination['subtitle'], ENT_QUOTES, 'UTF-8'); ?></p>

                <div class="information-grid">
                    <article class="information-card description-card">
                        <h3>Deskripsi</h3>
                        <p><?= htmlspecialchars($destination['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </article>

                    <article class="information-card facilities-card">
                        <h3>Fasilitas</h3>
                        <ul>
                            <?php if ($destination['facilities'] !== []): ?>
                            <?php foreach ($destination['facilities'] as $facility): ?>
                                <li>
                                    <?php
                                    $icon = match ($facility) {
                                        'Area parkir' => '<span class="facility-icon facility-car" aria-hidden="true"><i class="bi bi-car-front-fill"></i><i class="bi bi-p-square-fill"></i></span>',
                                        'Toilet Umum' => '<span class="facility-icon facility-restroom" aria-hidden="true"><i class="bi bi-person-standing"></i><i class="bi bi-person-standing-dress"></i></span>',
                                        'Mushola' => '<svg class="facility-icon mosque-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 20h20v2H2zm2-1V9h3V6h2v3h2v10h-2v-6H6v6zm9 0V9.5C13 7 15.2 5 18 5s5 2 5 4.5V19h-2v-4h-5v4zm1-7h7v-2h-7zM16.2 5c.1-1.8.8-3.2 1.8-4 1 .8 1.7 2.2 1.8 4z"/></svg>',
                                        'Spot Foto' => '<i class="facility-icon bi bi-camera-fill" aria-hidden="true"></i>',
                                        default => '<i class="facility-icon bi bi-question-square-fill" aria-hidden="true"></i>'
                                    };
                                    echo $icon;
                                    ?>
                                    <span><?= htmlspecialchars($facility, ENT_QUOTES, 'UTF-8'); ?></span>
                                </li>
                            <?php endforeach; ?>
                            <?php else: ?>
                                <li>Belum ada fasilitas yang tercatat.</li>
                            <?php endif; ?>
                        </ul>
                    </article>

                    <article class="information-card price-card">
                        <h3>Harga</h3>
                        <div class="price-details">
                            <div class="price-entry">
                                <i class="ticket-icon bi bi-ticket-perforated-fill" aria-hidden="true"></i>
                                <div class="price-copy">
                                    <strong>Tiket Masuk</strong>
                                    <span><?= htmlspecialchars($destination['ticket_price'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </div>
                        </div>
                        <h3 class="hours-title">Jam Operasional</h3>
                        <div class="hours-row">
                            <i class="clock-icon bi bi-alarm" aria-hidden="true"></i>
                            <span><?= htmlspecialchars($destination['hours'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </article>
                </div>
            </section>

            <section class="tour-location" aria-labelledby="location-title">
                <h2 id="location-title">LOKASI WISATA</h2>
                <p class="information-subtitle">Lokasi destinasi berdasarkan data yang tersimpan.</p>
                <div class="location-grid">
                    <div class="map-placeholder" role="img" aria-label="Peta ilustrasi lokasi Terasering Panyaweuyan">
                        <span class="map-town"><?= htmlspecialchars($destination['location'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="map-place"><?= htmlspecialchars($destination['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <svg class="location-map-art" viewBox="0 0 600 340" aria-hidden="true">
                            <path class="map-road map-road-main" d="M-20 220C80 110 150 250 245 180S365 40 475 85 565 160 630 200"></path>
                            <path class="map-road map-road-secondary" d="M-20 330C90 255 160 320 255 265S385 245 465 270 555 285 630 235"></path>
                            <path class="map-street" d="M40-10 170 350M185-10 315 350M430-10 325 350M570-10 465 350"></path>
                            <g class="map-marker" transform="translate(300 120)">
                                <path d="M0 58C0 58-25 27-25 8a25 25 0 1 1 50 0C25 27 0 58 0 58Z"></path>
                                <circle cx="0" cy="8" r="9"></circle>
                                <circle cx="0" cy="8" r="4"></circle>
                            </g>
                        </svg>
                    </div>
                    <article class="information-card location-card">
                        <h3>Alamat</h3>
                        <p><?= htmlspecialchars($destination['address'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <a class="map-link" href="<?= htmlspecialchars($destination['maps_url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">↗ Buka di Google Maps</a>
                    </article>
                </div>
            </section>

            <section class="tour-reviews" aria-labelledby="reviews-title">
                <h2 id="reviews-title">ULASAN</h2>
                <p class="information-subtitle">Ulasan pengunjung yang tersimpan di database.</p>
                <div class="review-summary-card">
                    <div class="review-score">
                        <?= $destination['has_rating'] ? number_format($destination['rating'], 1, ',', '.') : '-'; ?>
                    </div>
                    <div class="review-overview">
                        <?php if ($destination['has_rating']): ?>
                            <span class="review-stars" aria-hidden="true">★</span>
                            <span class="review-count">Dari <?= (int) $destination['review_count']; ?> ulasan</span>
                        <?php else: ?>
                            <span class="review-count">Belum ada ulasan</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($destination['has_rating']): ?>
                    <div class="rating-distribution" aria-label="Sebaran rating pengunjung">
                        <?php foreach ($destination['rating_distribution'] as $rating): ?>
                            <div class="rating-bar-row">
                                <span class="rating-bar-label"><?= (int) $rating['stars']; ?></span>
                                <span class="rating-bar-dot" aria-hidden="true"></span>
                                <span class="rating-bar-track"><span style="width: <?= (int) $rating['percentage']; ?>%"></span></span>
                                <span class="rating-bar-percent"><?= (int) $rating['percentage']; ?>%</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="review-grid">
                    <?php if ($destination['reviews'] !== []): ?>
                    <?php foreach ($destination['reviews'] as $review): ?>
                        <article class="review-card">
                            <svg class="review-avatar" viewBox="0 0 40 40" aria-hidden="true">
                                <circle cx="20" cy="20" r="17"></circle>
                                <circle cx="20" cy="15" r="5"></circle>
                                <path d="M10 30c1.5-4.5 4.8-6.8 10-6.8s8.5 2.3 10 6.8"></path>
                            </svg>
                            <div class="review-card-content">
                                <strong><?= htmlspecialchars($review['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span class="review-card-stars" aria-label="<?= (int) $review['rating']; ?> dari 5 bintang"><?php for ($star = 0; $star < (int) $review['rating']; $star++): ?><span class="star-icon" aria-hidden="true">★</span><?php endfor; ?></span>
                                <span class="review-age"><?= htmlspecialchars($review['age'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <p><?= htmlspecialchars($review['comment'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    <?php else: ?>
                        <p>Belum ada ulasan untuk destinasi ini.</p>
                    <?php endif; ?>
                </div>
                <button class="add-review-button" type="button">Tambah Ulasan dan Rating</button>
            </section>
        </div>
    </main>
    </div>
</body>
</html>
