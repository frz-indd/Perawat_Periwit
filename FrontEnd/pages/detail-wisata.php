<?php
$destination = [
    'title' => 'Terasering Panyaweuyan',
    'rating' => '4,5 (1500k ulasan)',
    'location' => 'Kabupaten Majalengka, Jawa Barat.',
    'subtitle' => 'Temukan Informasi Terasering Panyaweuyan dengan lebih detail',
    'description' => 'Pos pengamatan puncak bukit yang menghadap sawah terasering, bukit berhutan, dan Gunung Ciremai.',
    'facilities' => [
        'Area parkir',
        'Toilet Umum',
        'Mushola',
        'Spot Foto'
    ],
    'ticket_price' => 'Rp 15.000/orang',
    'parking_price' => ['Rp 5000/Motor', 'Rp 10.000 / Mobil'],
    'hours' => '08.00 - 17.00 WIB',
    'address' => 'Terasering Panyaweuyan, Argapura, Kabupaten Majalengka, Jawa Barat',
    'maps_url' => 'https://www.google.com/maps/search/?api=1&query=Terasering+Panyaweuyan%2C+Argapura%2C+Majalengka',
    'rating_distribution' => [
        ['stars' => 5, 'percentage' => 80],
        ['stars' => 4, 'percentage' => 17],
        ['stars' => 3, 'percentage' => 7],
        ['stars' => 2, 'percentage' => 2],
        ['stars' => 1, 'percentage' => 1],
    ],
    'reviews' => [
        [
            'name' => 'Cinta',
            'rating' => 5,
            'age' => '1 Minggu yang lalu',
            'comment' => 'Pemandangannya bener-bener bagus banget, cocok untuk liburan bareng keluarga, banyak spot foto juga, parkirannya luas, fasilitasnya lengkap'
        ],
        [
            'name' => 'Aurora',
            'rating' => 5,
            'age' => '1 bulan yang lalu',
            'comment' => 'Best view in Majalengka. Sejuk dan bikin betah pokoknya'
        ],
        [
            'name' => 'Nuraisiyah',
            'rating' => 5,
            'age' => '3 Minggu yang lalu',
            'comment' => 'Puas banget! Suka banget! Seger banget! Pokoknya bikin nagih!!!!!!!'
        ]
    ],
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
</head>
<body class="destination-detail-page">
    <div class="destination-detail-shell">
        <header class="detail-header">
            <nav class="detail-navbar" aria-label="Navigasi utama">
                <a class="detail-brand" href="../exploreMajaKu_skeleton/index.html">Explore Majaku</a>
                <div class="detail-nav-links">
                    <a href="../exploreMajaKu_skeleton/index.html">Beranda</a>
                    <a href="../exploreMajaKu_skeleton/pages/kategori-alam.html">kategori</a>
                    <a href="tentang.php">Tentang</a>
                </div>
                <div class="detail-nav-icons">
                    <a href="../exploreMajaKu_skeleton/pages/hasil-pencarian.html" aria-label="Cari">
                        <svg viewBox="0 0 40 40" aria-hidden="true"><circle cx="15.5" cy="15.5" r="11"></circle><path d="m23.5 23.5 10.5 10.5"></path></svg>
                    </a>
                    <a href="profil.php" aria-label="Profil">
                        <svg viewBox="0 0 40 40" aria-hidden="true"><circle cx="20" cy="20" r="18"></circle><circle cx="20" cy="15" r="5"></circle><path d="M9 31c1.7-5 5.5-7.5 11-7.5S29.3 26 31 31"></path></svg>
                    </a>
                </div>
            </nav>
        </header>

    <main class="detail-main">
        <div class="detail-hero" role="img" aria-label="Pemandangan sawah Terasering Panyaweuyan dan Gunung Ciremai"></div>
        <div class="detail-content">
            <section class="destination-summary" aria-labelledby="destination-title">
                <div class="destination-heading">
                    <h1 id="destination-title"><?= htmlspecialchars($destination['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
                    <div class="destination-rating" aria-label="Rating 4,5 dari 5, 1500k ulasan">
                        <span class="rating-stars" aria-hidden="true">★ ★ ★ ★</span>
                        <span class="rating-score"><?= htmlspecialchars($destination['rating'], ENT_QUOTES, 'UTF-8'); ?></span>
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
                            <div class="price-entry parking-entry">
                                <div aria-hidden="true" class="parking-spacer"></div>
                                <div class="price-copy">
                                    <strong>Tiket Parkir</strong>
                                    <?php foreach ($destination['parking_price'] as $parking): ?>
                                        <span><?= htmlspecialchars($parking, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endforeach; ?>
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
                <p class="information-subtitle">Temukan lokasi terasering panyaweuyan dengan mudah di peta/maps</p>
                <div class="location-grid">
                    <div class="map-placeholder" role="img" aria-label="Peta ilustrasi lokasi Terasering Panyaweuyan">
                        <span class="map-town">Majalengka</span>
                        <span class="map-place">Panyaweuyan</span>
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
                <p class="information-subtitle">Simak pengalaman pengunjung yang sudah datang ke tempat</p>
                <div class="review-summary-card">
                    <div class="review-score">4,5</div>
                    <div class="review-overview">
                        <span class="review-stars" aria-hidden="true">★ ★ ★ ★ ★</span>
                        <span class="review-count">Dari 1500k ulasan</span>
                    </div>
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
                </div>
                <div class="review-grid">
                    <?php foreach ($destination['reviews'] as $review): ?>
                        <article class="review-card">
                            <svg class="review-avatar" viewBox="0 0 40 40" aria-hidden="true">
                                <circle cx="20" cy="20" r="17"></circle>
                                <circle cx="20" cy="15" r="5"></circle>
                                <path d="M10 30c1.5-4.5 4.8-6.8 10-6.8s8.5 2.3 10 6.8"></path>
                            </svg>
                            <div class="review-card-content">
                                <strong><?= htmlspecialchars($review['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span class="review-card-stars" aria-label="<?= (int) $review['rating']; ?> dari 5 bintang"><?= str_repeat('★ ', (int) $review['rating']); ?></span>
                                <span class="review-age"><?= htmlspecialchars($review['age'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <p><?= htmlspecialchars($review['comment'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <button class="add-review-button" type="button">Tambah Ulasan dan Rating</button>
            </section>
        </div>
    </main>
    </div>
</body>
</html>
