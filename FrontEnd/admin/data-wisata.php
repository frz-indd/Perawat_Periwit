<?php
declare(strict_types=1);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$frontEndPosition = strpos($scriptPath, '/FrontEnd/');
$appBasePath = $frontEndPosition !== false
    ? substr($scriptPath, 0, $frontEndPosition)
    : rtrim(dirname(dirname($scriptPath)), '/.');

$pageConfig = [
    'apiDestinationsUrl' => $appBasePath . '/api/admin/destinations',
    'apiCategoriesUrl' => $appBasePath . '/api/admin/categories',
    'apiRegionsUrl' => $appBasePath . '/api/admin/regions',
    'apiLogoutUrl' => $appBasePath . '/api/admin/logout',
    'loginUrl' => $appBasePath . '/FrontEnd/auth/login-admin.php',
    'formUrl' => $appBasePath . '/FrontEnd/admin/form-wisata.php',
    'uploadBaseUrl' => $appBasePath . '/uploads/',
    'homeUrl' => $appBasePath . '/index.php',
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
    <title>Data Wisata | Explore Majaku Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kaushan+Script&family=Poppins:wght@400;500;600;700&family=Frank+Ruhl+Libre:wght@600;700&display=swap" rel="stylesheet">
    <link href="../CSS/admin-data-wisata.css?v=2" rel="stylesheet">
</head>
<body class="admin-wisata-page">
    <aside class="admin-sidebar" id="adminSidebar">
        <a class="admin-brand" href="<?= e($pageConfig['homeUrl']) ?>">Explore Majaku</a>
        <button class="sidebar-collapse" id="sidebarCollapse" type="button" aria-label="Ciutkan navigasi">
            <i class="bi bi-chevron-left"></i>
        </button>
        <nav class="admin-nav" aria-label="Navigasi admin">
            <a class="admin-nav__item" href="admin-dashboard.php"><i class="bi bi-house-door"></i><span>Dashboard</span></a>
            <a class="admin-nav__item is-active" href="data-wisata.php" aria-current="page"><i class="bi bi-bar-chart-fill"></i><span>Data Wisata</span></a>
            <a class="admin-nav__item" href="admin-resource.php?section=kategori"><i class="bi bi-grid-fill"></i><span>Kategori</span></a>
            <a class="admin-nav__item" href="admin-resource.php?section=fasilitas"><i class="bi bi-star-fill"></i><span>Fasilitas</span></a>
            <a class="admin-nav__item" href="admin-resource.php?section=ulasan"><i class="bi bi-chat-square-text"></i><span>Ulasan</span></a>
            <a class="admin-nav__item" href="admin-resource.php?section=pengguna"><i class="bi bi-people"></i><span>Pengguna</span></a>
        </nav>
        <div class="admin-sidebar__bottom">
            <a class="admin-nav__item" href="admin-resource.php?section=profil"><i class="bi bi-person-circle"></i><span>Profil admin</span></a>
            <button class="admin-nav__item admin-logout" id="logoutButton" type="button" data-api-url="<?= e($pageConfig['apiLogoutUrl']) ?>" data-api-method="POST"><i class="bi bi-box-arrow-right"></i><span>Logout</span></button>
        </div>
    </aside>

    <header class="admin-mobile-bar">
        <button class="mobile-menu-button" id="mobileMenuButton" type="button" aria-label="Buka navigasi"><i class="bi bi-list"></i></button>
        <span class="admin-mobile-brand">Explore Majaku</span>
        <button class="mobile-logout-button" id="mobileLogoutButton" type="button" data-api-url="<?= e($pageConfig['apiLogoutUrl']) ?>" data-api-method="POST" aria-label="Logout"><i class="bi bi-box-arrow-right"></i></button>
    </header>

    <header class="admin-topbar">
        <span><i class="bi bi-person-circle" aria-hidden="true"></i> Admin 1</span>
    </header>

    <main class="admin-main">
        <div class="admin-content">
            <div class="admin-page-heading">
                <div>
                    <h1>Data Wisata</h1>
                    <p>kelola semua data destinasi wisata</p>
                </div>
                <a class="btn admin-add-button" href="<?= e($pageConfig['formUrl']) ?>">
                    <i class="bi bi-plus-circle"></i><span>Tambah wisata</span>
                </a>
            </div>

            <div class="admin-filters" role="search">
                <label class="filter-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input id="searchInput" type="search" placeholder="Cari nama wisata..." aria-label="Cari nama wisata">
                </label>
                <select id="regionFilter" class="form-select" aria-label="Filter wilayah">
                    <option value="">Semua wilayah</option>
                </select>
                <select id="categoryFilter" class="form-select" aria-label="Filter kategori">
                    <option value="">Semua Kategori</option>
                </select>
                <button id="searchButton" class="btn admin-search-button" type="button" data-api-url="<?= e($pageConfig['apiDestinationsUrl']) ?>" data-api-method="GET"><i class="bi bi-search"></i><span>Cari</span></button>
            </div>

            <div id="pageMessage" class="alert admin-alert d-none" role="status"></div>
            <div class="table-responsive admin-table-wrap">
                <table class="table admin-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">No</th>
                            <th scope="col">Foto</th>
                            <th scope="col">Nama wisata</th>
                            <th scope="col">wilayah</th>
                            <th scope="col">Kategori</th>
                            <th scope="col">Harga Tiket<br>masuk</th>
                            <th scope="col">Rating</th>
                            <th scope="col">Lokasi</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="destinationRows">
                        <tr><td class="table-state" colspan="9"><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memuat data wisata...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="admin-table-footer">
                <p id="resultSummary" class="mb-0" aria-live="polite"></p>
                <nav aria-label="Halaman data wisata">
<ul id="paginationLinks" class="pagination pagination-sm mb-0">
    <li class="page-item disabled">
        <button
            id="previousPage"
            class="page-link"
            type="button"
            data-api-url="<?= e($pageConfig['apiDestinationsUrl']) ?>"
            data-api-method="GET"
            aria-label="Halaman sebelumnya"
            disabled
        >
            <i class="bi bi-chevron-left"></i>
        </button>
    </li>

    <li class="page-item active" aria-current="page">
        <button class="page-link" type="button">1</button>
    </li>

    <li class="page-item">
        <button
            id="nextPage"
            class="page-link"
            type="button"
            data-api-url="<?= e($pageConfig['apiDestinationsUrl']) ?>"
            data-api-method="GET"
            aria-label="Halaman berikutnya"
        >
            <i class="bi bi-chevron-right"></i>
        </button>
    </li>
</ul>
                    </ul>
                </nav>
            </div>
        </div>
    </main>

    <div class="admin-sidebar-backdrop" id="sidebarBackdrop"></div>
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0">
                    <h2 class="modal-title fs-5" id="deleteConfirmTitle">Hapus data wisata?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">Data destinasi wisata ini akan dihapus secara permanen.</div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteButton" data-api-url="<?= e($pageConfig['apiDestinationsUrl']) ?>" data-api-method="DELETE">Hapus data</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.adminWisataConfig = <?= json_encode($pageConfig, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Js/admin-data-wisata.js?v=1"></script>
<script src="../Js/api-endpoint-labels.js?v=api-tooltip-3"></script>
</body>
</html>
