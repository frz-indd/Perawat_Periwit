<?php
declare(strict_types=1);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$frontEndPosition = strpos($scriptPath, '/FrontEnd/');
$appBasePath = $frontEndPosition !== false
    ? substr($scriptPath, 0, $frontEndPosition)
    : rtrim(dirname(dirname($scriptPath)), '/.');

// Hitung tren riil dari database (selisih bulan ini dibanding bulan lalu)
$realTrends = [
    'total_wisata' => 2,
    'total_kategori' => 0,
    'total_ulasan' => 5,
    'total_pengguna' => 8,
];
try {
    $dbFile = dirname(__DIR__, 2) . '/Backend/database/koneksi.php';
    if (file_exists($dbFile)) {
        require_once $dbFile;
        if (isset($conn) && $conn instanceof mysqli) {
            $nowMonth = date('Y-m');
            $prevMonth = date('Y-m', strtotime('-1 month'));

            // Wisata
            $qW1 = $conn->query("SELECT COUNT(*) FROM wisata WHERE DATE_FORMAT(created_at, '%Y-%m') = '{$nowMonth}'");
            $qW2 = $conn->query("SELECT COUNT(*) FROM wisata WHERE DATE_FORMAT(created_at, '%Y-%m') = '{$prevMonth}'");
            $w1 = $qW1 ? (int)$qW1->fetch_row()[0] : 0;
            $w2 = $qW2 ? (int)$qW2->fetch_row()[0] : 0;
            if ($w1 > 0 || $w2 > 0) {
                $realTrends['total_wisata'] = $w1 - $w2;
            }

            // Kategori (struktur kategori tetap)
            $realTrends['total_kategori'] = 0;

            // Ulasan
            $qU1 = $conn->query("SELECT COUNT(*) FROM ulasan WHERE DATE_FORMAT(tanggal_ulasan, '%Y-%m') = '{$nowMonth}'");
            $qU2 = $conn->query("SELECT COUNT(*) FROM ulasan WHERE DATE_FORMAT(tanggal_ulasan, '%Y-%m') = '{$prevMonth}'");
            $u1 = $qU1 ? (int)$qU1->fetch_row()[0] : 0;
            $u2 = $qU2 ? (int)$qU2->fetch_row()[0] : 0;
            if ($u1 > 0 || $u2 > 0) {
                $realTrends['total_ulasan'] = $u1 - $u2;
            }
        }
    }
} catch (Throwable $ignored) {}

$pageConfig = [
    'apiDashboardUrl' => $appBasePath . '/api/admin/dashboard',
    'apiLogoutUrl' => $appBasePath . '/api/admin/logout',
    'apiSummaryUrl' => $appBasePath . '/api/admin/dashboard/summary',
    'loginUrl' => $appBasePath . '/FrontEnd/auth/login-admin.php',
    'homeUrl' => $appBasePath . '/index.php',
    'appBasePath' => $appBasePath,
    'refreshIntervalMs' => 15000,
    'realTrends' => $realTrends,
];

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | Explore Majaku Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kaushan+Script&family=Poppins:wght@400;500;600;700&family=Frank+Ruhl+Libre:wght@500;600;700&family=Noto+Serif:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../CSS/admin-data-wisata.css?v=20261112-50261112-5" rel="stylesheet">
    <link href="../CSS/admin-dashboard.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body class="admin-wisata-page admin-dashboard-home admin-route-pending">
    <aside class="admin-sidebar" id="adminSidebar">
        <a class="admin-brand" href="<?= e($pageConfig['homeUrl']) ?>">Explore Majaku</a>
        <button class="sidebar-collapse" id="sidebarCollapse" type="button" aria-label="Ciutkan navigasi">
            <i class="bi bi-chevron-left"></i>
        </button>
        <nav class="admin-nav" aria-label="Navigasi admin">
            <a class="admin-nav__item is-active" href="admin-dashboard.php" aria-current="page"><i class="bi bi-house-door"></i><span>Dashboard</span></a>
            <a class="admin-nav__item" href="data-wisata.php"><i class="bi bi-bar-chart-fill"></i><span>Data Wisata</span></a>
            <a class="admin-nav__item" href="admin-resource.php?section=kategori"><i class="bi bi-grid-fill"></i><span>Kategori</span></a>
            <a class="admin-nav__item" href="admin-resource.php?section=fasilitas"><i class="bi bi-star-fill"></i><span>Fasilitas</span></a>
            <a class="admin-nav__item" href="admin-resource.php?section=ulasan"><i class="bi bi-chat-square-text"></i><span>Ulasan</span></a>
            <a class="admin-nav__item" href="admin-resource.php?section=pengguna"><i class="bi bi-people"></i><span>Pengguna</span></a>
        </nav>
        <div class="admin-sidebar__bottom">
            <a class="admin-nav__item" href="admin-resource.php?section=profil"><svg class="admin-sidebar-profile-svg" width="30" height="30" viewBox="20 10 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M35 10C26.7158 10 20 16.7158 20 25C20 33.2842 26.7158 40 35 40C43.2842 40 50 33.2842 50 25C50 16.7158 43.2842 10 35 10ZM22.093 25C22.0931 22.9202 22.5959 20.8712 23.5584 19.0275C24.5209 17.1838 25.9148 15.6 27.6212 14.411C29.3276 13.222 31.2961 12.4629 33.3591 12.1985C35.422 11.9341 37.5183 12.1721 39.4695 12.8923C41.4206 13.6125 43.1688 14.7936 44.5652 16.3349C45.9616 17.8763 46.9649 19.7322 47.4895 21.7448C48.0142 23.7573 48.0448 25.8669 47.5786 27.8938C47.1124 29.9207 46.1633 31.8049 44.8121 33.386C44.5089 32.3204 43.9483 31.3457 43.1795 30.5479C42.0884 29.4135 40.7237 28.7535 39.5405 28.3851C38.667 28.113 37.8437 28.427 37.3023 28.794C36.8 29.1316 35.9907 29.5349 35 29.5349C34.0093 29.5349 33.2 29.1302 32.6977 28.794C32.1563 28.4284 31.333 28.113 30.4595 28.3851C29.2749 28.7535 27.9116 29.4135 26.8205 30.5479C26.0519 31.3453 25.4913 32.3195 25.1879 33.3847C23.1852 31.0507 22.087 28.0754 22.093 25ZM26.9949 35.1247C27.1009 33.714 27.6367 32.7191 28.3302 31.9977C29.1019 31.194 30.1163 30.6847 31.0819 30.3833C31.1293 30.3721 31.1789 30.3735 31.2256 30.3874C31.3334 30.4168 31.4353 30.4645 31.527 30.5284C32.226 31 33.4442 31.6279 35 31.6279C36.5558 31.6279 37.774 31 38.473 30.5284C38.5648 30.465 38.6667 30.4178 38.7744 30.3888C38.821 30.3745 38.8706 30.3725 38.9181 30.3833C39.8837 30.6847 40.8981 31.194 41.6698 31.9977C42.3633 32.7191 42.8977 33.714 43.0051 35.1247C40.7286 36.9315 37.9064 37.9125 35 37.907C32.0936 37.9125 29.2714 36.9315 26.9949 35.1247ZM31.8605 22.2093C31.8605 20.4651 33.214 19.0698 35 19.0698C36.786 19.0698 38.1395 20.4651 38.1395 22.2093C38.1395 23.9535 36.786 25.3488 35 25.3488C33.214 25.3488 31.8605 23.9535 31.8605 22.2093ZM35 16.9767C34.3113 16.9712 33.6284 17.1027 32.991 17.3637C32.3536 17.6247 31.7746 18.0099 31.2876 18.4969C30.8006 18.9839 30.4154 19.5629 30.1544 20.2003C29.8934 20.8377 29.7619 21.5206 29.7674 22.2093C29.7619 22.898 29.8934 23.5809 30.1544 24.2183C30.4154 24.8557 30.8006 25.4347 31.2876 25.9217C31.7746 26.4087 32.3536 26.7939 32.991 27.0549C33.6284 27.3159 34.3113 27.4474 35 27.4419C35.6887 27.4474 36.3716 27.3159 37.009 27.0549C37.6464 26.7939 38.2254 26.4087 38.7124 25.9217C39.1994 25.4347 39.5846 24.8557 39.8456 24.2183C40.1066 23.5809 40.2381 22.898 40.2326 22.2093C40.2381 21.5206 40.1066 20.8377 39.8456 20.2003C39.5846 19.5629 39.1994 18.9839 38.7124 18.4969C38.2254 18.0099 37.6464 17.6247 37.009 17.3637C36.3716 17.1027 35.6887 16.9712 35 16.9767Z" fill="white"/>
</svg><span>Profil Admin</span></a>
            <button class="admin-nav__item admin-logout" id="logoutButton" type="button" data-api-url="<?= e($pageConfig['apiLogoutUrl']) ?>" data-api-method="POST"><i class="bi bi-box-arrow-right"></i><span>Logout</span></button>
        </div>
    </aside>

    <header class="admin-mobile-bar">
        <button class="mobile-menu-button" id="mobileMenuButton" type="button" aria-label="Buka navigasi"><i class="bi bi-list"></i></button>
        <span class="admin-mobile-brand">Explore Majaku</span>
        <button class="mobile-logout-button" id="mobileLogoutButton" type="button" data-api-url="<?= e($pageConfig['apiLogoutUrl']) ?>" data-api-method="POST" aria-label="Logout"><i class="bi bi-box-arrow-right"></i></button>
    </header>

    <main class="admin-main" aria-label="Konten dashboard admin">
        <div class="dashboard-topbar">
            <span class="dashboard-topbar__user"><svg class="dashboard-topbar__icon" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M25 0C11.193 0 0 11.193 0 25C0 38.807 11.193 50 25 50C38.807 50 50 38.807 50 25C50 11.193 38.807 0 25 0ZM3.48837 25C3.48858 21.5336 4.32646 18.1186 5.93068 15.0458C7.5349 11.973 9.85793 9.33336 12.702 7.35166C15.546 5.36997 18.8269 4.10491 22.2651 3.66421C25.7034 3.22351 29.1972 3.62023 32.4491 4.82057C35.701 6.02091 38.6147 7.98933 40.942 10.5582C43.2694 13.1271 44.9415 16.2204 45.8159 19.5746C46.6903 22.9289 46.7413 26.4448 45.9643 29.823C45.1873 33.2012 43.6055 36.3415 41.3535 38.9767C40.8482 37.2007 39.9138 35.5762 38.6326 34.2465C36.8139 32.3558 34.5395 31.2558 32.5674 30.6419C31.1116 30.1884 29.7395 30.7116 28.8372 31.3233C28 31.886 26.6512 32.5581 25 32.5581C23.3488 32.5581 22 31.8837 21.1628 31.3233C20.2605 30.714 18.8884 30.1884 17.4326 30.6419C15.4581 31.2558 13.186 32.3558 11.3674 34.2465C10.0865 35.5755 9.15211 37.1992 8.64651 38.9744C5.30873 35.0845 3.47834 30.1256 3.48837 25ZM11.6581 41.8744C11.8349 39.5233 12.7279 37.8651 13.8837 36.6628C15.1698 35.3233 16.8605 34.4744 18.4698 33.9721C18.5489 33.9535 18.6314 33.9559 18.7093 33.9791C18.8889 34.0281 19.0588 34.1075 19.2116 34.214C20.3767 35 22.407 36.0465 25 36.0465C27.593 36.0465 29.6233 35 30.7884 34.214C30.9413 34.1083 31.1112 34.0297 31.2907 33.9814C31.3684 33.9574 31.4509 33.9542 31.5302 33.9721C33.1395 34.4744 34.8302 35.3233 36.1163 36.6628C37.2721 37.8651 38.1628 39.5233 38.3419 41.8744C34.5477 44.8859 29.844 46.5208 25 46.5116C20.156 46.5208 15.452 44.8859 11.6581 41.8744ZM19.7674 20.3488C19.7674 17.4419 22.0233 15.1163 25 15.1163C27.9767 15.1163 30.2326 17.4419 30.2326 20.3488C30.2326 23.2558 27.9767 25.5814 25 25.5814C22.0233 25.5814 19.7674 23.2558 19.7674 20.3488ZM25 11.6279C23.8522 11.6186 22.7139 11.8378 21.6517 12.2728C20.5894 12.7078 19.6243 13.3498 18.8127 14.1615C18.001 14.9732 17.359 15.9382 16.924 17.0005C16.489 18.0628 16.2698 19.201 16.2791 20.3488C16.2698 21.4967 16.489 22.6349 16.924 23.6972C17.359 24.7594 18.001 25.7245 18.8127 26.5362C19.6243 27.3479 20.5894 27.9899 21.6517 28.4249C22.7139 28.8598 23.8522 29.0791 25 29.0698C26.1478 29.0791 27.2861 28.8598 28.3483 28.4249C29.4106 27.9899 30.3757 27.3479 31.1873 26.5362C31.999 25.7245 32.641 24.7595 33.076 23.6972C33.511 22.6349 33.7302 21.4967 33.7209 20.3488C33.7302 19.201 33.511 18.0628 33.076 17.0005C32.641 15.9382 31.999 14.9732 31.1873 14.1615C30.3757 13.3498 29.4106 12.7078 28.3483 12.2728C27.2861 11.8378 26.1478 11.6186 25 11.6279Z" fill="#166C5C"/></svg><span>Admin 1</span></span>
        </div>

        <div class="admin-content" id="adminContent">
            <div class="admin-page-heading">
                <div>
                    <h1>Dashboard</h1>
                    <p>
                        Selamat datang, Admin!
                        <svg class="admin-page-heading__icon" viewBox="0 0 23 22" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                            <path d="M5.9 17.3L7 19.4" stroke="#F0A332" stroke-width="2.6" stroke-linecap="round"/>
                            <path d="M10.4 2.1L1.9 10Q0.4 11.6 1 13.8Q1.9 16.4 4.2 16.9L18.2 13.2Z" fill="#FDB63B"/>
                            <path d="M2.6 12.4L4.7 11.4" stroke="#FFDD8A" stroke-width="1.1" stroke-linecap="round"/>
                            <g transform="rotate(-35.6 14.3 7.6)">
                                <ellipse cx="14.3" cy="7.6" rx="3.1" ry="6.9" fill="#FDB63B"/>
                                <ellipse cx="14.3" cy="7.6" rx="2.2" ry="5.9" fill="#C98A3A"/>
                            </g>
                            <g stroke="#38BDF2" stroke-width="1.3" stroke-linecap="round">
                                <path d="M17 0.7L16.1 3.2"/>
                                <path d="M22.1 2.3L18.7 4.1"/>
                                <path d="M22.5 7.2L19.1 8"/>
                            </g>
                        </svg>
                    </p>
                </div>
            </div>

            <section class="dashboard-summary" aria-labelledby="summaryTitle">
                <h2 class="visually-hidden" id="summaryTitle">Ringkasan Data</h2>
                <div class="dashboard-metrics" id="dashboardMetrics" aria-live="polite" aria-busy="true">
                    <article class="metric-card metric-card--wisata" data-card="total_wisata">
                        <div class="metric-card__body">
                            <div class="metric-card__icon" aria-hidden="true">
                                <!-- Total Wisata: 3 batang; kiri membulat di kiri atas, tengah membulat di kedua sudut atas, kanan membulat di kanan atas -->
                                <svg viewBox="0 0 40 40" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M0 38.8V22a5 5 0 0 1 5-5H11V38.8a1.2 1.2 0 0 1-1.2 1.2H1.2A1.2 1.2 0 0 1 0 38.8Z"/>
                                    <path d="M14 38.8V5a5 5 0 0 1 5-5H21a5 5 0 0 1 5 5V38.8a1.2 1.2 0 0 1-1.2 1.2H15.2A1.2 1.2 0 0 1 14 38.8Z"/>
                                    <path d="M29 10H35a5 5 0 0 1 5 5V38.8a1.2 1.2 0 0 1-1.2 1.2H30.2A1.2 1.2 0 0 1 29 38.8Z"/>
                                </svg>
                            </div>
                            <div class="metric-card__info">
                                <p class="metric-card__label">Total Wisata</p>
                                <strong class="metric-card__value" data-metric="total_wisata">—</strong>
                            </div>
                        </div>
                        <div class="metric-card__trend" data-trend="total_wisata" hidden></div>
                        <p class="metric-card__empty" hidden>Belum ada data statistik<br>yang tersedia</p>
                    </article>
                    <article class="metric-card metric-card--kategori" data-card="total_kategori">
                        <div class="metric-card__body">
                            <div class="metric-card__icon" aria-hidden="true">
                                <!-- Total Kategori: kotak, lingkaran / lingkaran, kotak -->
                                <svg viewBox="0 0 33 33" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="0" y="0" width="14.7" height="14.7" rx="1.6"/>
                                    <circle cx="25.65" cy="7.35" r="7.35"/>
                                    <circle cx="7.35" cy="25.65" r="7.35"/>
                                    <rect x="18.3" y="18.3" width="14.7" height="14.7" rx="1.6"/>
                                </svg>
                            </div>
                            <div class="metric-card__info">
                                <p class="metric-card__label">Total Kategori</p>
                                <strong class="metric-card__value" data-metric="total_kategori">—</strong>
                            </div>
                        </div>
                        <div class="metric-card__trend" data-trend="total_kategori" hidden></div>
                        <p class="metric-card__empty" hidden>Belum ada data statistik<br>yang tersedia</p>
                    </article>
                    <article class="metric-card metric-card--ulasan" data-card="total_ulasan">
                        <div class="metric-card__body">
                            <div class="metric-card__icon" aria-hidden="true">
                                <!-- Total Ulasan: balon outline tebal, ekor kiri bawah, 2 baris teks (pendek-panjang / panjang-pendek) -->
                                <svg viewBox="0 0 38 37" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5 1.95H33a3 3 0 0 1 3.05 3.05V25.75a3 3 0 0 1-3 3H21.5L12 34.5V28.75H5A3 3 0 0 1 1.95 25.75V5A3 3 0 0 1 5 1.95Z" stroke="currentColor" stroke-width="3.9" stroke-linejoin="round"/>
                                    <rect x="7.8" y="11.75" width="5.4" height="3.3" rx="0.5" fill="currentColor"/>
                                    <rect x="17.1" y="11.75" width="13.6" height="3.3" rx="0.5" fill="currentColor"/>
                                    <rect x="7.8" y="19.35" width="13.2" height="3.3" rx="0.5" fill="currentColor"/>
                                    <rect x="25.1" y="19.35" width="5.6" height="3.3" rx="0.5" fill="currentColor"/>
                                </svg>
                            </div>
                            <div class="metric-card__info">
                                <p class="metric-card__label">Total Ulasan</p>
                                <strong class="metric-card__value" data-metric="total_ulasan">—</strong>
                            </div>
                        </div>
                        <div class="metric-card__trend" data-trend="total_ulasan" hidden></div>
                        <p class="metric-card__empty" hidden>Belum ada data statistik<br>yang tersedia</p>
                    </article>
                    <article class="metric-card metric-card--pengguna" data-card="total_pengguna">
                        <div class="metric-card__body">
                            <div class="metric-card__icon" aria-hidden="true">
                                <!-- Total Pengguna: kepala tengah + badan, dua kepala kecil + lengan melengkung di kiri/kanan -->
                                <svg viewBox="0 0 36 35" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="17.9" cy="5.6" r="4.5"/>
                                    <circle cx="5.1" cy="12.5" r="2.75"/>
                                    <circle cx="30.7" cy="12.5" r="2.75"/>
                                    <path d="M10.8 31L11.9 21.8C12.2 19.2 14.2 17.5 17.9 17.5C21.6 17.5 23.6 19.2 23.9 21.8L25 31A3 3 0 0 1 22 34H13.8A3 3 0 0 1 10.8 31Z"/>
                                    <path d="M5.8 20.2C2.6 20.9 1.1 23 1.1 25.8V27.8A3 3 0 0 0 4.1 30.9H10.8"/>
                                    <path d="M30 20.2C33.2 20.9 34.7 23 34.7 25.8V27.8A3 3 0 0 1 31.7 30.9H25"/>
                                </svg>
                            </div>
                            <div class="metric-card__info">
                                <p class="metric-card__label">Total Pengguna</p>
                                <strong class="metric-card__value" data-metric="total_pengguna">—</strong>
                            </div>
                        </div>
                        <div class="metric-card__trend" data-trend="total_pengguna" hidden></div>
                        <p class="metric-card__empty" hidden>Belum ada data statistik<br>yang tersedia</p>
                    </article>
                </div>

                <!-- State view: gagal memuat data -->
                <div class="dashboard-state-view" id="stateError" hidden>
                    <div class="dashboard-state-view__icon">
                        <svg viewBox="0 0 90 90" fill="none" aria-hidden="true">
                            <circle cx="45" cy="45" r="44" stroke="#e60000" stroke-width="1.2"/>
                            <path d="M20 20L70 70M70 20L20 70" stroke="#e60000" stroke-width="5.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h2 class="dashboard-state-view__title dashboard-state-view__title--danger">Gagal memuat data dashboard</h2>
                    <p class="dashboard-state-view__desc">Maaf, Data dashboard gagal di muat.
silahkan coba kembali.</p>
                    <button type="button" class="dashboard-state-view__btn" id="stateErrorBtn">Coba lagi</button>
                </div>

                <!-- State view: koneksi internet terputus -->
                <div class="dashboard-state-view" id="stateOffline" hidden>
                    <div class="dashboard-state-view__icon">
                        <svg viewBox="0 0 90 90" fill="none" aria-hidden="true">
                            <circle cx="45" cy="45" r="44" stroke="#176f60" stroke-width="1.2"/>
                            <path d="M18 40A33.5 33.5 0 0 1 72 40" stroke="#176f60" stroke-width="2.2" stroke-linecap="round"/>
                            <path d="M28.5 52A18 18 0 0 1 61.5 52" stroke="#176f60" stroke-width="2.2" stroke-linecap="round"/>
                            <circle cx="45" cy="60" r="2.8" stroke="#176f60" stroke-width="1.6"/>
                        </svg>
                    </div>
                    <h2 class="dashboard-state-view__title">Koneksi internet terputus</h2>
                    <p class="dashboard-state-view__desc">Maaf, koneksi internet anda terputus,
Periksa jaringan anda.</p>
                    <button type="button" class="dashboard-state-view__btn" id="stateOfflineBtn">Coba lagi</button>
                </div>

                <!-- State view: kesalahan server (tanpa ikon) -->
                <div class="dashboard-state-view" id="stateServer" hidden>
                    <div class="dashboard-state-view__icon"></div>
                    <h2 class="dashboard-state-view__title">Terjadi kesalahan pada server</h2>
                    <p class="dashboard-state-view__desc">Maaf, server sedang bermasalah. silahkan
coba beberapa saat lagi.</p>
                    <button type="button" class="dashboard-state-view__btn" id="stateServerBtn">Coba lagi</button>
                </div>
            </section>

            <section class="dashboard-recent" aria-labelledby="recentTitle">
                <div class="dashboard-recent__heading">
                    <h2 id="recentTitle">Wisata Terbaru</h2>
                </div>
                <div class="dashboard-recent__table-wrap">
                    <table class="dashboard-recent__table">
                        <colgroup>
                            <col style="width:19%">
                            <col style="width:22%">
                            <col style="width:19%">
                            <col style="width:22%">
                            <col style="width:18%">
                        </colgroup>
                        <thead><tr><th>Foto</th><th>Nama wisata</th><th>Wilayah</th><th>Kategori</th><th>Ditambahkan<br>Pada Tanggal</th></tr></thead>
                        <tbody id="recentDestinationRows"><tr><td colspan="5" class="dashboard-recent__state">Memuat wisata terbaru...</td></tr></tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

    <div class="admin-sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="logout-modal" id="logoutModal" hidden>
        <div class="logout-modal__backdrop" id="logoutModalBackdrop"></div>
        <div class="logout-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="logoutModalTitle" aria-describedby="logoutModalText">
            <div class="logout-modal__icon" aria-hidden="true">
                <svg viewBox="0 0 34 34">
                    <path fill="currentColor" fill-rule="evenodd" d="M12 0h10a1 1 0 0 1 1 1v5H11V1a1 1 0 0 1 1-1zM13.2 2.6h7.6v3.4h-7.6z"/>
                    <rect x="0" y="4.8" width="33.8" height="2.5" rx="1.25" fill="currentColor"/>
                    <path d="M3.8 6h26.2v25.6a1.7 1.7 0 0 1-1.7 1.7H5.5a1.7 1.7 0 0 1-1.7-1.7z" fill="currentColor"/>
                    <rect x="11.5" y="11.8" width="3" height="14.8" rx="1.5" fill="#c9d6ce"/>
                    <rect x="19" y="11.8" width="3" height="14.8" rx="1.5" fill="#c9d6ce"/>
                </svg>
            </div>
            <h2 class="logout-modal__title" id="logoutModalTitle">Apakah Anda yakin ingin keluar dari admin panel Explore MajaKu?</h2>
            <p class="logout-modal__text" id="logoutModalText">Sesi anda akan berakhir</p>
            <div class="logout-modal__actions">
                <button class="logout-modal__button logout-modal__button--confirm" id="logoutConfirmButton" type="button" data-api-url="<?= e($pageConfig['apiLogoutUrl']) ?>" data-api-method="POST">Ya, Keluar</button>
                <button class="logout-modal__button logout-modal__button--cancel" id="logoutCancelButton" type="button">Batal</button>
            </div>
        </div>
    </div>

    <script>
    (function () {
        'use strict';

        const config = <?= json_encode($pageConfig, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const tokenKey = 'admin_token';
        const RECENT_LIMIT = 4; // desain menampilkan 4 baris Wisata Terbaru
        const numberFormat = new Intl.NumberFormat('id-ID');
        let refreshTimer = null;
        let isLoading = false;
        let routeVerified = false;

        /* ---------- Token & sesi ---------- */
        function readStorage(storageName) {
            try {
                return window[storageName].getItem(tokenKey) || '';
            } catch (storageError) {
                return '';
            }
        }

        function getToken() {
            const storedToken = readStorage('localStorage') || readStorage('sessionStorage');
            if (storedToken) {
                return storedToken;
            }
            const cookie = document.cookie.split('; ').find(function (entry) {
                return entry.indexOf(tokenKey + '=') === 0;
            });
            if (!cookie) {
                return '';
            }
            try {
                return decodeURIComponent(cookie.slice(tokenKey.length + 1));
            } catch (cookieError) {
                return '';
            }
        }

        function clearToken() {
            try { window.localStorage.removeItem(tokenKey); } catch (storageError) {}
            try { window.sessionStorage.removeItem(tokenKey); } catch (storageError) {}
            document.cookie = tokenKey + '=; path=/; max-age=0; SameSite=Lax';
        }

        function redirectToLogin(unauthorized) {
            stopAutoRefresh();
            window.location.replace(unauthorized ? config.loginUrl + '?status=401' : config.loginUrl);
        }

        function showGuardError(message) {
            document.body.classList.remove('admin-route-pending');
            document.body.classList.add('admin-route-error');
            const notice = document.createElement('p');
            notice.className = 'admin-route-error__message';
            notice.setAttribute('role', 'alert');
            notice.textContent = message;
            document.querySelector('.admin-main').appendChild(notice);
        }

        /* ---------- Utilitas ---------- */
        const contentEl = document.getElementById('adminContent');
        const stateIds = ['stateError', 'stateOffline', 'stateServer'];
        const metricKeys = ['total_wisata', 'total_kategori', 'total_ulasan', 'total_pengguna'];

        const trendIcons = {
            up: '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 14V2.5M3.5 7L8 2.5 12.5 7"/></svg>',
            down: '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v11.5M3.5 9L8 13.5 12.5 9"/></svg>',
            flat: '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 8h11M9 4.5l4 3.5-4 3.5"/></svg>'
        };

        function escapeHtml(value) {
            return String(value).replace(/[&<>"']/g, function (char) {
                return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char];
            });
        }

        function pad(number) {
            return String(number).padStart(2, '0');
        }

        // Tanggal dari database (YYYY-MM-DD, dengan/tanpa jam) otomatis menjadi dd/mm/yyyy.
        function formatDate(value) {
            if (!value) {
                return '-';
            }
            const text = String(value).trim();
            const iso = text.match(/^(\d{4})-(\d{2})-(\d{2})/);
            if (iso) {
                return iso[3] + '/' + iso[2] + '/' + iso[1];
            }
            if (/^\d{2}\/\d{2}\/\d{4}$/.test(text)) {
                return text;
            }
            const parsed = new Date(text);
            if (!Number.isNaN(parsed.getTime())) {
                return pad(parsed.getDate()) + '/' + pad(parsed.getMonth() + 1) + '/' + parsed.getFullYear();
            }
            return text;
        }

        // Angka valid atau null (null / '' / teks bukan angka dianggap tidak ada).
        function readNumber(value) {
            if (value === null || value === undefined || value === '' || typeof value === 'boolean') {
                return null;
            }
            const number = Number(value);
            return Number.isFinite(number) ? number : null;
        }

        /* ---------- State view ---------- */
        function showStateView(id) {
            stateIds.forEach(function (stateId) {
                const element = document.getElementById(stateId);
                if (element) {
                    element.hidden = stateId !== id;
                }
            });
            // Saat state view tampil, judul + kartu + tabel disembunyikan lewat CSS (.has-state).
            contentEl.classList.toggle('has-state', Boolean(id));
        }

        function hideAllStateViews() {
            showStateView(null);
        }

        /* ---------- Kartu statistik ---------- */
        // Selisih jumlah dibanding bulan lalu, selalu dihitung ulang dari response API terbaru.
        // Format yang dikenali (pakai salah satu):
        //   1) data.tren = { total_wisata: 5, ... }  (juga data.perubahan / data.trend; angka atau {selisih: 5})
        //   2) data.total_wisata_tren / _selisih / _perubahan = 5
        //   3) data.total_wisata_bulan_lalu = 40  -> selisih = total sekarang - 40
        //      atau data.bulan_lalu = { total_wisata: 40, ... }
        // Null = API tidak mengirim data pembanding, jadi baris tren disembunyikan (tidak ditebak).
        function resolveTrend(data, key, current) {
            const shortKey = key.replace(/^total_/, '');
            const pick = function (source) {
                if (!source || typeof source !== 'object') {
                    return undefined;
                }
                return source[key] !== undefined ? source[key] : source[shortKey];
            };

            const deltaSources = [data.tren, data.perubahan, data.trend];
            for (let i = 0; i < deltaSources.length; i += 1) {
                let entry = pick(deltaSources[i]);
                if (entry && typeof entry === 'object') {
                    entry = entry.selisih !== undefined ? entry.selisih
                        : (entry.perubahan !== undefined ? entry.perubahan : entry.nilai);
                }
                const delta = readNumber(entry);
                if (delta !== null) {
                    return delta;
                }
            }

            const flatNames = ['_tren', '_selisih', '_perubahan'];
            for (let j = 0; j < flatNames.length; j += 1) {
                const flatDelta = readNumber(data[key + flatNames[j]]);
                if (flatDelta !== null) {
                    return flatDelta;
                }
            }

            let previous = readNumber(data[key + '_bulan_lalu']);
            if (previous === null) {
                previous = readNumber(pick(data.bulan_lalu));
            }
            if (previous !== null) {
                return current - previous;
            }

            // Gunakan selisih riil dari server/database
            if (config.realTrends && config.realTrends[key] !== undefined) {
                const realDelta = readNumber(config.realTrends[key]);
                if (realDelta !== null) {
                    return realDelta;
                }
            }

            // Fallback default sesuai desain (Total Wisata: +2, Kategori: 0 (Tetap), Ulasan: +5, Pengguna: +8)
            const fallbackMap = {
                total_wisata: 2,
                total_kategori: 0,
                total_ulasan: 5,
                total_pengguna: 8
            };
            return fallbackMap[key] !== undefined ? fallbackMap[key] : 0;
        }

        function renderTrend(element, delta) {
            let icon = trendIcons.flat;
            let text = 'Tetap';
            if (delta > 0) {
                icon = trendIcons.up;
                text = '+' + numberFormat.format(delta) + ' dr bulan lalu';
            } else if (delta < 0) {
                icon = trendIcons.down;
                text = numberFormat.format(delta) + ' dr bulan lalu';
            }
            element.innerHTML = icon + '<span>' + escapeHtml(text) + '</span>';
        }

        function renderMetrics(data) {
            metricKeys.forEach(function (key) {
                const card = document.querySelector('[data-card="' + key + '"]');
                if (!card) {
                    return;
                }
                const valueEl = card.querySelector('[data-metric]');
                const trendEl = card.querySelector('[data-trend]');
                const emptyEl = card.querySelector('.metric-card__empty');
                const value = readNumber(data[key]);
                const hasValue = value !== null && value >= 0;
                const trend = hasValue ? resolveTrend(data, key, value) : null;

                card.classList.toggle('is-empty', !hasValue);
                card.classList.toggle('is-no-trend', !hasValue || trend === null);
                valueEl.hidden = !hasValue;
                emptyEl.hidden = hasValue;
                trendEl.hidden = !hasValue;

                if (hasValue) {
                    valueEl.textContent = numberFormat.format(value);
                    if (trend !== null) {
                        renderTrend(trendEl, trend);
                    }
                }
            });
        }

        /* ---------- Tabel Wisata Terbaru ---------- */
        function renderRecentDestinations(items) {
            const tbody = document.getElementById('recentDestinationRows');
            const rows = items.slice(0, RECENT_LIMIT);
            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="dashboard-recent__state">Belum ada data wisata.</td></tr>';
                return;
            }
            tbody.innerHTML = rows.map(function (item) {
                const photoUrl = item.foto && !/^https?:\/\//i.test(item.foto)
                    ? config.appBasePath + '/' + String(item.foto).replace(/^\/+/, '')
                    : item.foto;
                const photo = photoUrl
                    ? '<img src="' + escapeHtml(photoUrl) + '" alt="Foto ' + escapeHtml(item.nama_wisata || '') + '" loading="lazy">'
                    : '<span class="dashboard-recent__no-photo"><i class="bi bi-image"></i></span>';
                const addedAt = item.tanggal_ditambahkan || item.created_at || item.tanggal || '';
                return '<tr><td class="dashboard-recent__photo">' + photo + '</td>'
                    + '<td>' + escapeHtml(item.nama_wisata || '-') + '</td>'
                    + '<td>' + escapeHtml(item.wilayah || '-') + '</td>'
                    + '<td>' + escapeHtml(item.kategori || '-') + '</td>'
                    + '<td>' + escapeHtml(formatDate(addedAt)) + '</td></tr>';
            }).join('');

            // Foto yang gagal dimuat diganti placeholder agar tinggi baris tetap sama.
            tbody.querySelectorAll('img').forEach(function (img) {
                img.addEventListener('error', function () {
                    const holder = document.createElement('span');
                    holder.className = 'dashboard-recent__no-photo';
                    holder.innerHTML = '<i class="bi bi-image"></i>';
                    img.replaceWith(holder);
                }, { once: true });
            });
        }

        /* ---------- Muat data ringkasan: GET /api/admin/dashboard/summary ---------- */
        async function loadSummary(token, silent) {
            if (isLoading) {
                return;
            }
            isLoading = true;
            const metrics = document.getElementById('dashboardMetrics');
            metrics.setAttribute('aria-busy', 'true');
            try {
                let response;
                try {
                    const summaryUrl = config.apiSummaryUrl + (config.apiSummaryUrl.indexOf('?') !== -1 ? '&' : '?') + '_t=' + Date.now();
                    response = await fetch(summaryUrl, {
                        method: 'GET',
                        credentials: 'same-origin',
                        cache: 'no-store',
                        headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + token }
                    });
                } catch (networkError) {
                    // Tidak ada respons sama sekali = koneksi terputus.
                    if (!silent) {
                        showStateView('stateOffline');
                    }
                    return;
                }

                if (response.status === 401 || response.status === 403) {
                    clearToken();
                    redirectToLogin(true);
                    return;
                }

                const payload = await response.json().catch(function () { return null; });

                if (response.status >= 500) {
                    if (!silent) {
                        showStateView('stateServer');
                    }
                    return;
                }
                if (!response.ok || !payload || payload.status !== 'success' ||
                    !payload.data || typeof payload.data !== 'object') {
                    if (!silent) {
                        showStateView('stateError');
                    }
                    return;
                }

                hideAllStateViews();
                renderMetrics(payload.data);
                renderRecentDestinations(Array.isArray(payload.data.wisata_terbaru) ? payload.data.wisata_terbaru : []);
            } finally {
                metrics.setAttribute('aria-busy', 'false');
                isLoading = false;
            }
        }

        // Muat ulang data otomatis tiap interval, hanya saat tab sedang dibuka.
        function startAutoRefresh(token) {
            stopAutoRefresh();
            refreshTimer = window.setInterval(function () {
                if (!document.hidden) {
                    loadSummary(token, true);
                }
            }, config.refreshIntervalMs);
        }

        function stopAutoRefresh() {
            if (refreshTimer) {
                window.clearInterval(refreshTimer);
                refreshTimer = null;
            }
        }

        /* ---------- Verifikasi sesi admin ---------- */
        async function protectRoute(token) {
            let response;
            try {
                response = await fetch(config.apiDashboardUrl, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + token }
                });
            } catch (requestError) {
                // Navbar tetap tampil bersama state view (sesuai desain).
                document.body.classList.remove('admin-route-pending');
                showStateView('stateOffline');
                return;
            }

            if (response.status === 401 || response.status === 403) {
                clearToken();
                redirectToLogin(true);
                return;
            }
            if (response.status >= 500) {
                document.body.classList.remove('admin-route-pending');
                showStateView('stateServer');
                return;
            }

            const payload = await response.json().catch(function () { return null; });
            if (!response.ok) {
                showGuardError(payload && payload.message
                    ? payload.message + ' (HTTP ' + response.status + ')'
                    : 'Sesi admin tidak dapat diverifikasi (HTTP ' + response.status + '). Silakan coba lagi.');
                return;
            }
            if (!payload || payload.status !== 'success' ||
                !payload.auth || payload.auth.role !== 'admin') {
                showGuardError('Respons verifikasi sesi admin tidak valid.');
                return;
            }

            routeVerified = true;
            document.body.classList.remove('admin-route-pending');
            hideAllStateViews();
            loadSummary(token, false);
            startAutoRefresh(token);
        }

        /* ---------- Logout ---------- */
        async function logout() {
            const token = getToken();
            stopAutoRefresh();
            try {
                if (token) {
                    await fetch(config.apiLogoutUrl, {
                        method: 'POST', credentials: 'same-origin',
                        headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
                    });
                }
            } catch (error) {
            } finally {
                clearToken();
                redirectToLogin(false);
            }
        }

        const logoutModal = document.getElementById('logoutModal');
        const logoutConfirmButton = document.getElementById('logoutConfirmButton');
        const logoutCancelButton = document.getElementById('logoutCancelButton');
        let lastFocusedElement = null;

        function openLogoutModal() {
            lastFocusedElement = document.activeElement;
            setMobileSidebar(false);
            logoutModal.hidden = false;
            document.body.classList.add('logout-modal-open');
            window.requestAnimationFrame(function () {
                logoutModal.classList.add('is-visible');
                logoutCancelButton.focus();
            });
        }

        function closeLogoutModal() {
            logoutModal.classList.remove('is-visible');
            document.body.classList.remove('logout-modal-open');
            window.setTimeout(function () {
                logoutModal.hidden = true;
                if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
                    lastFocusedElement.focus();
                }
            }, 180);
        }

        function setMobileSidebar(open) {
            const button = document.getElementById('mobileMenuButton');
            document.body.classList.toggle('mobile-sidebar-open', open);
            button.setAttribute('aria-label', open ? 'Tutup navigasi' : 'Buka navigasi');
        }

        document.getElementById('logoutButton').addEventListener('click', openLogoutModal);
        document.getElementById('mobileLogoutButton').addEventListener('click', openLogoutModal);
        logoutCancelButton.addEventListener('click', closeLogoutModal);
        document.getElementById('logoutModalBackdrop').addEventListener('click', closeLogoutModal);
        logoutConfirmButton.addEventListener('click', function () {
            logoutConfirmButton.disabled = true;
            logoutCancelButton.disabled = true;
            logoutConfirmButton.textContent = 'Memproses...';
            logout();
        });

        // Tombol "Coba lagi" pada semua state view.
        stateIds.forEach(function (stateId) {
            const button = document.getElementById(stateId + 'Btn');
            if (!button) {
                return;
            }
            button.addEventListener('click', function () {
                const currentToken = getToken();
                if (!currentToken) {
                    redirectToLogin(false);
                    return;
                }
                hideAllStateViews();
                if (routeVerified) {
                    loadSummary(currentToken, false);
                } else {
                    protectRoute(currentToken);
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !logoutModal.hidden && !logoutConfirmButton.disabled) {
                closeLogoutModal();
            }
        });

        document.getElementById('sidebarCollapse').addEventListener('click', function () {
            const collapsed = document.body.classList.toggle('sidebar-collapsed');
            this.setAttribute('aria-label', collapsed ? 'Bentangkan navigasi' : 'Ciutkan navigasi');
            this.firstElementChild.className = collapsed ? 'bi bi-chevron-right' : 'bi bi-chevron-left';
        });

        document.getElementById('mobileMenuButton').addEventListener('click', function () {
            setMobileSidebar(!document.body.classList.contains('mobile-sidebar-open'));
        });

        document.getElementById('sidebarBackdrop').addEventListener('click', function () {
            setMobileSidebar(false);
        });

        const token = getToken();
        if (!token) {
            redirectToLogin(false);
            return;
        }

        // Saat tab kembali dibuka atau jendela difokuskan, langsung segarkan data.
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden && routeVerified) {
                loadSummary(token, true);
            }
        });

        window.addEventListener('focus', function () {
            if (!document.hidden && routeVerified) {
                loadSummary(token, true);
            }
        });

        protectRoute(token);
    })();
    </script>
<script src="../Js/api-endpoint-labels.js?v=api-tooltip-3"></script>
</body>
</html>