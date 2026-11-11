<?php
declare(strict_types=1);

$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$frontEndPosition = strpos($scriptPath, '/FrontEnd/');
$appBasePath = $frontEndPosition !== false
    ? substr($scriptPath, 0, $frontEndPosition)
    : rtrim(dirname(dirname($scriptPath)), '/.');

$pageConfig = [
    'apiDashboardUrl' => $appBasePath . '/api/admin/dashboard',
    'apiSummaryUrl' => $appBasePath . '/api/admin/dashboard/summary',
    'loginUrl' => $appBasePath . '/FrontEnd/auth/login-admin.php',
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
    <title>Dashboard | Explore Majaku Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kaushan+Script&family=Poppins:wght@400;500;600;700&family=Frank+Ruhl+Libre:wght@600;700&display=swap" rel="stylesheet">
    <link href="../CSS/admin-data-wisata.css?v=1" rel="stylesheet">
    <link href="../CSS/admin-dashboard.css?v=1" rel="stylesheet">
    <style>
        .admin-route-pending .admin-sidebar,
        .admin-route-pending .admin-mobile-bar,
        .admin-route-pending .admin-main {
            visibility: hidden;
        }

        .admin-route-error .admin-sidebar,
        .admin-route-error .admin-mobile-bar,
        .admin-route-error .admin-sidebar-backdrop,
        .admin-route-error .admin-content {
            display: none;
        }

        .admin-route-error .admin-main {
            display: grid;
            min-height: 100vh;
            margin-left: 0;
            padding: 24px;
            place-items: center;
        }

        .admin-route-error__message {
            max-width: 560px;
            margin: 0;
            color: #a32020;
            font-size: 16px;
            text-align: center;
        }
    </style>
</head>
<body class="admin-wisata-page admin-route-pending">
    <aside class="admin-sidebar" id="adminSidebar">
        <a class="admin-brand" href="<?= e($pageConfig['homeUrl']) ?>">Explore Majaku</a>
        <button class="sidebar-collapse" id="sidebarCollapse" type="button" aria-label="Ciutkan navigasi">
            <i class="bi bi-chevron-left"></i>
        </button>
        <nav class="admin-nav" aria-label="Navigasi admin">
            <a class="admin-nav__item is-active" href="admin-dashboard.php" aria-current="page"><i class="bi bi-house-door"></i><span>Dashboard</span></a>
            <a class="admin-nav__item" href="data-wisata.php"><i class="bi bi-bar-chart-fill"></i><span>Data Wisata</span></a>
            <a class="admin-nav__item is-disabled" href="#" aria-disabled="true"><i class="bi bi-grid-fill"></i><span>Kategori</span></a>
            <a class="admin-nav__item is-disabled" href="#" aria-disabled="true"><i class="bi bi-star-fill"></i><span>Fasilitas</span></a>
            <a class="admin-nav__item is-disabled" href="#" aria-disabled="true"><i class="bi bi-chat-square-text"></i><span>Ulasan</span></a>
            <a class="admin-nav__item is-disabled" href="#" aria-disabled="true"><i class="bi bi-people"></i><span>Pengguna</span></a>
        </nav>
        <div class="admin-sidebar__bottom">
            <a class="admin-nav__item is-disabled" href="#" aria-disabled="true"><i class="bi bi-person-circle"></i><span>Profil admin</span></a>
            <button class="admin-nav__item admin-logout" id="logoutButton" type="button"><i class="bi bi-box-arrow-right"></i><span>Logout</span></button>
        </div>
    </aside>

    <header class="admin-mobile-bar">
        <button class="mobile-menu-button" id="mobileMenuButton" type="button" aria-label="Buka navigasi"><i class="bi bi-list"></i></button>
        <span class="admin-mobile-brand">Explore Majaku</span>
        <button class="mobile-logout-button" id="mobileLogoutButton" type="button" aria-label="Logout"><i class="bi bi-box-arrow-right"></i></button>
    </header>

    <main class="admin-main" aria-label="Konten dashboard admin">
        <div class="admin-content">
            <div class="admin-page-heading">
                <div>
                    <h1>Dashboard</h1>
                    <p>“Selamat datang, Admin”</p>
                </div>
                <div class="admin-user-indicator"><i class="bi bi-person-circle"></i><span>Admin</span></div>
            </div>

            <section class="dashboard-summary" aria-labelledby="summaryTitle">
                <div class="dashboard-summary__heading">
                    <div><h2 id="summaryTitle">Ringkasan Data</h2><p>Statistik utama Explore Majaku</p></div>
                    <span class="dashboard-summary__status"><i class="bi bi-arrow-repeat"></i> Data terbaru</span>
                </div>
                <div class="dashboard-metrics" id="dashboardMetrics" aria-live="polite" aria-busy="true">
                    <article class="metric-card metric-card--wisata"><div class="metric-card__icon"><i class="bi bi-geo-alt-fill"></i></div><div class="metric-card__content"><p>Total Wisata</p><strong data-metric="total_wisata">—</strong><span>Destinasi terdaftar</span></div></article>
                    <article class="metric-card metric-card--kategori"><div class="metric-card__icon"><i class="bi bi-grid-fill"></i></div><div class="metric-card__content"><p>Total Kategori</p><strong data-metric="total_kategori">—</strong><span>Kategori tersedia</span></div></article>
                    <article class="metric-card metric-card--ulasan"><div class="metric-card__icon"><i class="bi bi-chat-left-text-fill"></i></div><div class="metric-card__content"><p>Total Ulasan</p><strong data-metric="total_ulasan">—</strong><span>Ulasan pengguna</span></div></article>
                    <article class="metric-card metric-card--pengguna"><div class="metric-card__icon"><i class="bi bi-people-fill"></i></div><div class="metric-card__content"><p>Total Pengguna</p><strong data-metric="total_pengguna">—</strong><span>Akun terdaftar</span></div></article>
                </div>
                <p class="dashboard-summary__message" id="summaryMessage" role="status">Memuat ringkasan dashboard...</p>
            </section>
        </div>
    </main>

    <div class="admin-sidebar-backdrop" id="sidebarBackdrop"></div>

    <script>
    (function () {
        'use strict';

        const config = <?= json_encode($pageConfig, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const tokenKey = 'admin_token';

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
            try {
                window.localStorage.removeItem(tokenKey);
            } catch (storageError) {
            }
            try {
                window.sessionStorage.removeItem(tokenKey);
            } catch (storageError) {
            }
            document.cookie = tokenKey + '=; path=/; max-age=0; SameSite=Lax';
        }

        function redirectToLogin(unauthorized) {
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

        const metricKeys = ['total_wisata', 'total_kategori', 'total_ulasan', 'total_pengguna'];

        function setSummaryMessage(message, isError) {
            const element = document.getElementById('summaryMessage');
            element.textContent = message;
            element.classList.toggle('is-error', Boolean(isError));
        }

        async function loadSummary(token) {
            const metrics = document.getElementById('dashboardMetrics');
            metrics.setAttribute('aria-busy', 'true');
            try {
                const response = await fetch(config.apiSummaryUrl, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + token }
                });
                const payload = await response.json().catch(function () { return null; });
                if (response.status === 401 || response.status === 403) {
                    clearToken();
                    redirectToLogin(true);
                    return;
                }
                if (!response.ok || !payload || payload.status !== 'success' ||
                    !payload.data || typeof payload.data !== 'object') {
                    throw new Error(payload && payload.message ? payload.message :
                        'Ringkasan dashboard gagal dimuat (HTTP ' + response.status + ').');
                }
                metricKeys.forEach(function (key) {
                    const value = Number(payload.data[key]);
                    if (!Number.isFinite(value) || value < 0) throw new Error('Format data statistik tidak valid.');
                    document.querySelector('[data-metric="' + key + '"]').textContent =
                        new Intl.NumberFormat('id-ID').format(value);
                });
                setSummaryMessage('Ringkasan berhasil diperbarui.', false);
            } catch (error) {
                setSummaryMessage(error.message || 'Tidak dapat memuat ringkasan dashboard.', true);
            } finally {
                metrics.setAttribute('aria-busy', 'false');
            }
        }

        async function protectRoute(token) {
            try {
                const response = await fetch(config.apiDashboardUrl, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + token
                    }
                });

                if (response.status === 401 || response.status === 403) {
                    clearToken();
                    redirectToLogin(true);
                    return;
                }

                const payload = await response.json().catch(function () {
                    return null;
                });
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

                document.body.classList.remove('admin-route-pending');
                loadSummary(token);
            } catch (requestError) {
                showGuardError('Tidak dapat terhubung ke server untuk memverifikasi sesi.');
            }
        }

        function logout() {
            clearToken();
            redirectToLogin(false);
        }

        function setMobileSidebar(open) {
            const button = document.getElementById('mobileMenuButton');
            document.body.classList.toggle('mobile-sidebar-open', open);
            button.setAttribute('aria-label', open ? 'Tutup navigasi' : 'Buka navigasi');
        }

        document.getElementById('logoutButton').addEventListener('click', logout);
        document.getElementById('mobileLogoutButton').addEventListener('click', logout);

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
        protectRoute(token);
    })();
    </script>
</body>
</html>