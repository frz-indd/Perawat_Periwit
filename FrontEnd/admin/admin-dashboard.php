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
    'apiDashboardUrl' => $appBasePath . '/api/admin/dashboard',
    'apiLogoutUrl' => $appBasePath . '/api/admin/logout',
    'apiSummaryUrl' => $appBasePath . '/api/admin/dashboard/summary',
    'loginUrl' => $appBasePath . '/FrontEnd/auth/login-admin.php',
    'homeUrl' => $appBasePath . '/index.php',
    'appBasePath' => $appBasePath,
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
    <link href="../CSS/admin-dashboard.css?v=20261116-trend-admin" rel="stylesheet">
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

    <main class="admin-main" aria-label="Konten dashboard admin">
        <div class="admin-content">
            <div class="admin-page-heading">
                <div>
                    <h1>Dashboard</h1>
                    <p>
                        Selamat datang, Admin!
                        <svg class="admin-page-heading__icon" viewBox="0 0 32 32" aria-hidden="true">
                            <path d="M4 14.5 21 5v18L4 17.5z" fill="#f5a31a"/>
                            <path d="M4 14.5h4.5v3.2H4z" fill="#d9870a"/>
                            <path d="M8.2 18.2h4.4l1.6 6.6a1.6 1.6 0 0 1-1.2 2l-1.6.4a1.6 1.6 0 0 1-1.9-1.1z" fill="#f5a31a"/>
                            <rect x="21" y="4.5" width="3.4" height="19" rx="1.7" fill="#d9870a"/>
                            <path d="M27 8.5l3-2M27.5 14h3.6M27 19.5l3 2" stroke="#2f7fd3" stroke-width="1.8" stroke-linecap="round" fill="none"/>
                        </svg>
                    </p>
                </div>
                <div class="admin-user-indicator"><i class="bi bi-person-circle"></i><span>Admin</span></div>
            </div>

            <section class="dashboard-summary" aria-labelledby="summaryTitle">
                <h2 class="visually-hidden" id="summaryTitle">Ringkasan Data</h2>
                <div class="dashboard-metrics" id="dashboardMetrics" aria-live="polite" aria-busy="true">
                    <article class="metric-card metric-card--wisata">
                        <div class="metric-card__top">
                            <div class="metric-card__icon" aria-hidden="true">
                                <svg viewBox="0 0 44 44">
                                    <path d="M0 44V20a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v24z" fill="currentColor"/>
                                    <path d="M16 44V4a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v40z" fill="currentColor"/>
                                    <path d="M32 44V12a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v32z" fill="currentColor"/>
                                </svg>
                            </div>
                            <div class="metric-card__content"><p>Total Wisata</p><strong data-metric="total_wisata">—</strong></div>
                        </div>
                        <div class="metric-card__trend" data-trend="total_wisata"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 8h12M9 3l5 5-5 5"/></svg><span>Tetap</span></div>
                    </article>
                    <article class="metric-card metric-card--kategori">
                        <div class="metric-card__top">
                            <div class="metric-card__icon" aria-hidden="true">
                                <svg viewBox="0 0 44 44">
                                    <rect x="0" y="0" width="20" height="20" rx="5" fill="currentColor"/>
                                    <circle cx="34" cy="10" r="10" fill="currentColor"/>
                                    <circle cx="10" cy="34" r="10" fill="currentColor"/>
                                    <rect x="24" y="24" width="20" height="20" rx="5" fill="currentColor"/>
                                </svg>
                            </div>
                            <div class="metric-card__content"><p>Total Kategori</p><strong data-metric="total_kategori">—</strong></div>
                        </div>
                        <div class="metric-card__trend" data-trend="total_kategori"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 8h12M9 3l5 5-5 5"/></svg><span>Tetap</span></div>
                    </article>
                    <article class="metric-card metric-card--ulasan">
                        <div class="metric-card__top">
                            <div class="metric-card__icon" aria-hidden="true">
                                <svg viewBox="0 0 44 44" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 3h32a3.5 3.5 0 0 1 3.5 3.5v22A3.5 3.5 0 0 1 38 32H28l-8 9v-9H6a3.5 3.5 0 0 1-3.5-3.5v-22A3.5 3.5 0 0 1 6 3z"/>
                                    <path d="M10 12h6M21 12h13M10 18h24M10 24h14"/>
                                </svg>
                            </div>
                            <div class="metric-card__content"><p>Total Ulasan</p><strong data-metric="total_ulasan">—</strong></div>
                        </div>
                        <div class="metric-card__trend" data-trend="total_ulasan"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 8h12M9 3l5 5-5 5"/></svg><span>Tetap</span></div>
                    </article>
                    <article class="metric-card metric-card--pengguna">
                        <div class="metric-card__top">
                            <div class="metric-card__icon" aria-hidden="true">
                                <svg viewBox="0 0 44 44" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="22" cy="9" r="6"/>
                                    <path d="M12 38v-4a9 9 0 0 1 9-9h2a9 9 0 0 1 9 9v4z"/>
                                    <circle cx="7.5" cy="17" r="4.5"/>
                                    <path d="M2 36v-2.5a6.5 6.5 0 0 1 5.5-6.4"/>
                                    <circle cx="36.5" cy="17" r="4.5"/>
                                    <path d="M42 36v-2.5a6.5 6.5 0 0 0-5.5-6.4"/>
                                </svg>
                            </div>
                            <div class="metric-card__content"><p>Total Pengguna</p><strong data-metric="total_pengguna">—</strong></div>
                        </div>
                        <div class="metric-card__trend" data-trend="total_pengguna"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 8h12M9 3l5 5-5 5"/></svg><span>Tetap</span></div>
                    </article>
                </div>
                <p class="dashboard-summary__message" id="summaryMessage" role="status">Memuat ringkasan dashboard...</p>
            </section>

            <section class="dashboard-recent" aria-labelledby="recentTitle">
                <div class="dashboard-recent__heading"><h2 id="recentTitle">Wisata Terbaru</h2></div>
                <div class="dashboard-recent__table-wrap">
                    <table class="dashboard-recent__table">
                        <colgroup>
                            <col style="width:18%">
                            <col style="width:22%">
                            <col style="width:13%">
                            <col style="width:23%">
                            <col style="width:24%">
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
                <button class="logout-modal__button logout-modal__button--confirm" id="logoutConfirmButton" type="button">Ya, Keluar</button>
                <button class="logout-modal__button logout-modal__button--cancel" id="logoutCancelButton" type="button">Batal</button>
            </div>
        </div>
    </div>

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

        const trendIcons = {
            up: '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 14V2M3 7l5-5 5 5"/></svg>',
            down: '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v12M3 9l5 5 5-5"/></svg>',
            flat: '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 8h12M9 3l5 5-5 5"/></svg>'
        };

        function setSummaryMessage(message, isError) {
            const element = document.getElementById('summaryMessage');
            element.textContent = message;
            element.classList.toggle('is-error', Boolean(isError));
        }

        function renderTrend(key, source) {
            const element = document.querySelector('[data-trend="' + key + '"]');
            if (!element) {
                return;
            }
            const raw = source && typeof source === 'object' ? source[key] : undefined;
            const value = Number(raw);
            let icon = trendIcons.flat;
            let text = 'Tetap';
            if (raw !== undefined && raw !== null && raw !== '' && Number.isFinite(value)) {
                if (value > 0) {
                    icon = trendIcons.up;
                    text = '+' + new Intl.NumberFormat('id-ID').format(value) + ' dr bulan lalu';
                } else if (value < 0) {
                    icon = trendIcons.down;
                    text = new Intl.NumberFormat('id-ID').format(value) + ' dr bulan lalu';
                }
            }
            element.innerHTML = icon + '<span>' + escapeHtml(text) + '</span>';
        }

        function renderRecentDestinations(items) {
            const tbody = document.getElementById('recentDestinationRows');
            if (!items.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="dashboard-recent__state">Belum ada data wisata.</td></tr>';
                return;
            }
            tbody.innerHTML = items.map(function (item) {
                const photoUrl = item.foto && !/^https?:\/\//i.test(item.foto) ? config.appBasePath + '/' + String(item.foto).replace(/^\/+/, '') : item.foto;
                const photo = photoUrl ? '<img src="' + escapeHtml(photoUrl) + '" alt="Foto ' + escapeHtml(item.nama_wisata) + '" loading="lazy">' : '<span class="dashboard-recent__no-photo"><i class="bi bi-image"></i></span>';
                return '<tr><td class="dashboard-recent__photo">' + photo + '</td><td>' + escapeHtml(item.nama_wisata || '-') + '</td><td>' + escapeHtml(item.wilayah || '-') + '</td><td>' + escapeHtml(item.kategori || '-') + '</td><td>' + escapeHtml(item.tanggal_ditambahkan || '-') + '</td></tr>';
            }).join('');
        }

        function escapeHtml(value) {
            return String(value).replace(/[&<>"']/g, function (char) {
                return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char];
            });
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
                const trendSource = payload.data.tren || payload.data.perubahan || payload.data.trend || null;
                metricKeys.forEach(function (key) {
                    const value = Number(payload.data[key]);
                    if (!Number.isFinite(value) || value < 0) throw new Error('Format data statistik tidak valid.');
                    document.querySelector('[data-metric="' + key + '"]').textContent =
                        new Intl.NumberFormat('id-ID').format(value);
                    renderTrend(key, trendSource);
                });
                renderRecentDestinations(Array.isArray(payload.data.wisata_terbaru) ? payload.data.wisata_terbaru : []);
                setSummaryMessage('', false);
            } catch (error) {
                const message = error.message || 'Tidak dapat memuat ringkasan dashboard.';
                setSummaryMessage(message, true);
                const tbody = document.getElementById('recentDestinationRows');
                tbody.innerHTML = '<tr><td colspan="5" class="dashboard-recent__state dashboard-recent__state--error">Data wisata belum dapat dimuat. Periksa API dan koneksi database.</td></tr>';
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

        async function logout() {
            const token = getToken();
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
        protectRoute(token);
    })();
    </script>
<script src="../Js/api-endpoint-labels.js?v=api-tooltip-3"></script>
</body>
</html>