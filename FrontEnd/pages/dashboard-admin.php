<?php
$appName = 'Explore Majaku';
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$frontEndPosition = strpos($scriptPath, '/FrontEnd/');
$appBasePath = $frontEndPosition !== false
    ? substr($scriptPath, 0, $frontEndPosition)
    : rtrim(dirname(dirname($scriptPath)), '/.');
$loginUrl = $appBasePath . '/FrontEnd/auth/login-admin.php';
$dashboardUrl = $appBasePath . '/FrontEnd/pages/dashboard-admin.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Kaushan+Script&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../CSS/theme.css?v=admin-design-1">
    <link rel="stylesheet" href="../CSS/admin-dashboard.css?v=admin-dashboard-2">
</head>
<body class="admin-dashboard-page admin-route-pending">
    <aside class="admin-sidebar" id="adminSidebar" aria-label="Sidebar admin">
        <a class="admin-brand" href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></a>
        <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Tutup menu" aria-expanded="true">
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
        </button>
        <nav class="admin-nav" aria-label="Navigasi admin">
            <a class="admin-nav__item is-active" href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>" aria-current="page"><i class="bi bi-house-door" aria-hidden="true"></i><span>Dashboard</span></a>
            <span class="admin-nav__item is-disabled" aria-disabled="true"><i class="bi bi-bar-chart-fill" aria-hidden="true"></i><span>Data Wisata</span></span>
            <span class="admin-nav__item is-disabled" aria-disabled="true"><i class="bi bi-grid-fill" aria-hidden="true"></i><span>Kategori</span></span>
            <span class="admin-nav__item is-disabled" aria-disabled="true"><i class="bi bi-star-fill" aria-hidden="true"></i><span>Fasilitas</span></span>
            <span class="admin-nav__item is-disabled" aria-disabled="true"><i class="bi bi-chat-square-text" aria-hidden="true"></i><span>Ulasan</span></span>
            <span class="admin-nav__item is-disabled" aria-disabled="true"><i class="bi bi-people" aria-hidden="true"></i><span>Pengguna</span></span>
            <div class="admin-nav__divider" aria-hidden="true"></div>
            <span class="admin-nav__item is-disabled" aria-disabled="true"><i class="bi bi-person-circle" aria-hidden="true"></i><span>Profil admin</span></span>
            <button class="admin-nav__item admin-nav__logout" id="logoutButton" type="button"><i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>Logout</span></button>
        </nav>
    </aside>

    <div class="mobile-topbar">
        <button class="mobile-menu-toggle" id="mobileMenuToggle" type="button" aria-label="Buka menu" aria-expanded="false">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>
        <span class="mobile-brand"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></span>
        <button class="mobile-logout" id="mobileLogoutButton" type="button" aria-label="Logout">
            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
        </button>
    </div>

    <main class="admin-main" aria-label="Konten dashboard admin"></main>

    <script>
    (function () {
        'use strict';

        const tokenKey = 'admin_token';
        const loginUrl = <?= json_encode($loginUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const dashboardApiUrl = <?= json_encode($appBasePath . '/api/admin/dashboard', JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

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
                // Continue clearing other available session storage.
            }
            try {
                window.sessionStorage.removeItem(tokenKey);
            } catch (storageError) {
                // Continue clearing the browser cookie.
            }
            document.cookie = tokenKey + '=; path=/; max-age=0; SameSite=Lax';
        }

        function redirectToLogin(unauthorized) {
            window.location.replace(unauthorized ? loginUrl + '?status=401' : loginUrl);
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

        async function protectRoute(token) {
            try {
                const response = await fetch(dashboardApiUrl, {
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
                        ? payload.message
                        : 'Sesi admin tidak dapat diverifikasi. Silakan coba lagi.');
                    return;
                }
                if (!payload || payload.status !== 'success' ||
                    !payload.auth || payload.auth.role !== 'admin') {
                    showGuardError('Respons verifikasi sesi admin tidak valid.');
                    return;
                }

                document.body.classList.remove('admin-route-pending');
            } catch (requestError) {
                showGuardError('Tidak dapat terhubung ke server untuk memverifikasi sesi.');
            }
        }

        function logout() {
            clearToken();
            redirectToLogin(false);
        }

        document.getElementById('logoutButton').addEventListener('click', logout);
        document.getElementById('mobileLogoutButton').addEventListener('click', logout);
        document.getElementById('sidebarToggle').addEventListener('click', function () {
            const collapsed = document.body.classList.toggle('sidebar-collapsed');
            this.setAttribute('aria-expanded', String(!collapsed));
            this.setAttribute('aria-label', collapsed ? 'Buka menu' : 'Tutup menu');
        });
        document.getElementById('mobileMenuToggle').addEventListener('click', function () {
            const open = document.body.classList.toggle('mobile-sidebar-open');
            this.setAttribute('aria-expanded', String(open));
            this.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
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
