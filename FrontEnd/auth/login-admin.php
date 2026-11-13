<?php
$appName = 'Explore Majaku';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$frontEndPosition = strpos($scriptPath, '/FrontEnd/');
$appBasePath = $frontEndPosition !== false
    ? substr($scriptPath, 0, $frontEndPosition)
    : rtrim(dirname(dirname($scriptPath)), '/.');
$apiLoginUrl = $appBasePath . '/api/admin/login';
$loginUrl = $appBasePath . '/FrontEnd/auth/login-admin.php';
$dashboardUrl = $appBasePath . '/FrontEnd/admin/admin-dashboard.php';
$forgotPasswordUrl = '#';
$homeUrl = $appBasePath . '/index.php';
$isUnauthorized = ($_GET['status'] ?? '') === '401';
$pageTitle = $isUnauthorized ? 'Akses Ditolak' : 'Login Admin';

$clientConfig = [
    'apiLoginUrl' => $apiLoginUrl,
    'apiLogoutUrl' => $appBasePath . '/api/admin/logout',
    'dashboardUrl' => $dashboardUrl,
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($pageTitle) ?> | <?= e($appName) ?></title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Fragment+Mono:ital@0;1&family=Frank+Ruhl+Libre:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=Kaushan+Script&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../CSS/theme.css?v=admin-design-1">
    <link rel="stylesheet" href="../CSS/login-admin.css?v=admin-design-8">
</head>

<body class="admin-auth-page <?= $isUnauthorized ? 'page-unauthorized' : 'page-login' ?>">

    <header class="auth-header">
        <a href="<?= e($homeUrl) ?>" class="brand-name">
            <?= e($appName) ?>
        </a>
    </header>

    <?php if ($isUnauthorized): ?>

    <main class="unauthorized">
        <svg class="unauthorized__icon" viewBox="0 0 142 141" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M29 52V35a28 28 0 0 1 56 0v17" stroke="#166C5C" stroke-width="14" stroke-linecap="round"/>
            <rect x="7" y="59" width="100" height="75" rx="17" stroke="#166C5C" stroke-width="14"/>
            <path d="M56 87v18" stroke="#166C5C" stroke-width="10" stroke-linecap="round"/>
            <path d="M108.500 85 139 138H78Z" fill="#F70B0B" stroke="#F70B0B" stroke-width="6" stroke-linejoin="round"/>
            <path d="M108.500 100v16" stroke="#166C5C" stroke-width="6" stroke-linecap="round"/>
            <circle cx="108.500" cy="127" r="3.500" fill="#166C5C"/>
        </svg>

        <h1 class="unauthorized__title">Akses ditolak (401 Unauthorized)</h1>

        <p class="unauthorized__text">
            Anda tidak memiliki hak akses untuk membuka halaman ini. Silakan masuk menggunakan akun admin.
        </p>

        <a class="btn-back" href="<?= e($loginUrl) ?>">Kembali ke Login</a>
    </main>

    <?php else: ?>

    <main class="login-page">

        <section class="login-card" aria-labelledby="loginTitle">

            <h1 class="login-card__title" id="loginTitle">LOGIN ADMIN</h1>

            <form id="loginForm" method="post" novalidate data-api-url="<?= e($apiLoginUrl) ?>" data-api-method="POST">

                <div class="field field--username">
                    <label class="field__label" for="username">Username</label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="field__input"
                        placeholder="Masukkan Username"
                        autocomplete="username"
                        autocapitalize="none"
                        spellcheck="false"
                        maxlength="255"
                        aria-describedby="usernameError"
                    >

                    <p class="field__error" id="usernameError" role="alert" hidden></p>
                </div>

                <div class="field field--password">
                    <label class="field__label" for="password">Password</label>

                    <div class="field__control">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="field__input field__input--password"
                            placeholder="Masukkan Password"
                            autocomplete="current-password"
                            aria-describedby="passwordError"
                        >

                        <button
                            type="button"
                            class="field__toggle"
                            id="passwordToggle"
                            aria-label="Tampilkan password"
                            aria-pressed="false"
                        >
                            <i class="bi bi-eye-slash-fill" aria-hidden="true"></i>
                        </button>
                    </div>

                    <p class="field__error" id="passwordError" role="alert" hidden></p>
                </div>

                <p class="login-error" id="loginError" role="alert" hidden></p>

                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Ingat Saya</label>
                    </div>

                    <a href="<?= e($forgotPasswordUrl) ?>" class="forgot-password">
                        Lupa password?
                    </a>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-login" id="submitButton" data-api-url="<?= e($apiLoginUrl) ?>" data-api-method="POST">
                        <span class="btn-login__spinner" aria-hidden="true"></span>
                        <span class="btn-login__label">Login</span>
                    </button>
                </div>

            </form>

        </section>

        <section class="login-hero">
            <h2 class="login-hero__title">
                <span>Kelola wisata,</span>
                <span>Kenalkan pesona</span>
                <span>Majalengka dan</span>
                <span>Kuningan</span>
            </h2>

            <p class="login-hero__text">
                Platform pengelolaan informasi destinasi wisata untuk menghadirkan informasi yang mudah diakses dan selalu diperbarui.
            </p>
        </section>

    </main>

    <script>
    (function () {
        'use strict';

        const config = <?= json_encode($clientConfig, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        // Masuk kembali ke halaman login berarti mengakhiri sesi admin yang masih tersimpan.
        const TOKEN_KEY = 'admin_token';
        const COOKIE_MAX_AGE = 60 * 60 * 24;
        const REMEMBER_ADMIN_USERNAME_KEY = 'explore_majaku_admin_last_username_v1';

        function readExistingToken() {
            try {
                const stored = window.localStorage.getItem(TOKEN_KEY) || window.sessionStorage.getItem(TOKEN_KEY);
                if (stored) return stored;
            } catch (error) {}
            const cookie = document.cookie.split('; ').find(function (entry) { return entry.indexOf(TOKEN_KEY + '=') === 0; });
            return cookie ? decodeURIComponent(cookie.slice(TOKEN_KEY.length + 1)) : '';
        }
        function revokeExistingToken() {
            const oldToken = readExistingToken();
            if (oldToken) {
                fetch(config.apiLogoutUrl, {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Authorization': 'Bearer ' + oldToken, 'Accept': 'application/json' },
                    keepalive: true
                }).catch(function () {});
            }
            safely(function () { window.localStorage.removeItem(TOKEN_KEY); });
            safely(function () { window.sessionStorage.removeItem(TOKEN_KEY); });
            document.cookie = TOKEN_KEY + '=; path=/; max-age=0; SameSite=Lax';
        }

        const MESSAGES = {
            usernameRequired: 'Username wajib di isi',
            passwordRequired: 'Password wajib di isi',
            usernameInvalid: 'Username yang anda masukkan salah',
            passwordInvalid: 'Password yang anda masukkan salah',
            requestFailed: 'Login tidak dapat diproses. Silakan coba lagi.',
            connectionFailed: 'Tidak dapat terhubung ke server. Silakan coba lagi.',
            tokenMissing: 'Token login tidak diterima. Silakan coba lagi.',
            storageFailed: 'Sesi tidak dapat disimpan. Aktifkan penyimpanan browser lalu coba lagi.',
            loading: 'Memproses...',
            idle: 'Login'
        };

        revokeExistingToken();
        window.addEventListener('pageshow', function (event) { if (event.persisted) revokeExistingToken(); });

        const form = document.getElementById('loginForm');
        const elements = {
            username: document.getElementById('username'),
            password: document.getElementById('password'),
            remember: document.getElementById('remember'),
            usernameError: document.getElementById('usernameError'),
            passwordError: document.getElementById('passwordError'),
            loginError: document.getElementById('loginError'),
            submit: document.getElementById('submitButton'),
            submitLabel: document.querySelector('#submitButton .btn-login__label'),
            toggle: document.getElementById('passwordToggle'),
            toggleIcon: document.querySelector('#passwordToggle i')
        };

        try {
            const rememberedUsername = window.localStorage.getItem(REMEMBER_ADMIN_USERNAME_KEY);
            if (rememberedUsername) {
                elements.username.value = rememberedUsername;
                elements.remember.checked = true;
            }
        } catch (storageError) {
            // Penyimpanan browser dapat dinonaktifkan; login tetap dapat digunakan.
        }

        const state = {
            username: elements.username.value || '',
            password: '',
            remember: false,
            errorMessage: '',
            usernameError: '',
            passwordError: '',
            isLoading: false
        };

        function safely(callback) {
            try {
                callback();
                return true;
            } catch (storageError) {
                return false;
            }
        }

        function clearToken() {
            safely(function () { window.localStorage.removeItem(TOKEN_KEY); });
            safely(function () { window.sessionStorage.removeItem(TOKEN_KEY); });
            document.cookie = TOKEN_KEY + '=; path=/; max-age=0; SameSite=Lax';
        }

        function saveToken(token, remember) {
            clearToken();

            const storage = remember ? 'localStorage' : 'sessionStorage';
            const stored = safely(function () {
                window[storage].setItem(TOKEN_KEY, token);
            });

            let cookie = TOKEN_KEY + '=' + encodeURIComponent(token) + '; path=/; SameSite=Lax';
            if (remember) {
                cookie += '; max-age=' + COOKIE_MAX_AGE;
            }
            if (window.location.protocol === 'https:') {
                cookie += '; Secure';
            }
            document.cookie = cookie;
            const cookieStored = document.cookie.split('; ').some(function (entry) {
                return entry.indexOf(TOKEN_KEY + '=') === 0;
            });
            if (!stored && !cookieStored) {
                throw new Error('Token could not be persisted.');
            }
        }

        function setState(patch) {
            Object.keys(patch).forEach(function (key) {
                state[key] = patch[key];
            });
            render();
        }

        function renderFieldError(input, output, message) {
            const field = input.closest('.field');

            output.textContent = message;
            output.hidden = !message;

            if (field) {
                field.classList.toggle('has-error', Boolean(message));
            }

            if (message) {
                input.setAttribute('aria-invalid', 'true');
            } else {
                input.removeAttribute('aria-invalid');
            }
        }

        function render() {
            renderFieldError(elements.username, elements.usernameError, state.usernameError);
            renderFieldError(elements.password, elements.passwordError, state.passwordError);
            elements.loginError.textContent = state.errorMessage;
            elements.loginError.hidden = !state.errorMessage;

            elements.submit.disabled = state.isLoading;
            elements.submit.classList.toggle('is-loading', state.isLoading);
            elements.submit.setAttribute('aria-busy', String(state.isLoading));
            elements.submitLabel.textContent = state.isLoading ? MESSAGES.loading : MESSAGES.idle;
        }

        function validate() {
            return {
                usernameError: state.username.trim() ? '' : MESSAGES.usernameRequired,
                passwordError: state.password ? '' : MESSAGES.passwordRequired
            };
        }

        function extractToken(payload) {
            if (!payload || typeof payload !== 'object') {
                return null;
            }
            const source = payload.data && typeof payload.data === 'object' ? payload.data : payload;
            const token = payload.token || source.token || source.admin_token || payload.admin_token;
            return typeof token === 'string' && token.trim() ? token : null;
        }

        function parseJson(response) {
            return response.json().catch(function () {
                return null;
            });
        }

        function detectField(payload) {
            if (!payload || typeof payload !== 'object') {
                return '';
            }
            const source = payload.data && typeof payload.data === 'object'
                ? payload.data
                : payload;
            if (source.field === 'username' || source.field === 'password') {
                return source.field;
            }

            const errors = source.errors;
            if (errors && typeof errors === 'object') {
                const hasUsername = Object.prototype.hasOwnProperty.call(errors, 'username');
                const hasPassword = Object.prototype.hasOwnProperty.call(errors, 'password');
                if (hasUsername !== hasPassword) {
                    return hasUsername ? 'username' : 'password';
                }
            }

            const text = String(source.message || source.error || '').toLowerCase();
            const hasUsername = text.indexOf('username') !== -1;
            const hasPassword = text.indexOf('password') !== -1;

            if (hasUsername && !hasPassword) {
                return 'username';
            }
            if (hasPassword && !hasUsername) {
                return 'password';
            }
            return '';
        }

        function fieldMessage(payload, field, fallback) {
            const source = payload && payload.data && typeof payload.data === 'object'
                ? payload.data
                : payload;
            if (!source || typeof source !== 'object') {
                return fallback;
            }

            const errors = source.errors;
            if (errors && typeof errors[field] === 'string' && errors[field].trim()) {
                return errors[field];
            }
            return fallback;
        }

        function handleUnauthorized(payload) {
            const field = detectField(payload);
            if (field === 'username') {
                setState({
                    isLoading: false,
                    errorMessage: '',
                    usernameError: fieldMessage(payload, field, MESSAGES.usernameInvalid),
                    passwordError: ''
                });
                elements.username.focus();
                return;
            }

            if (field === 'password') {
                setState({
                    isLoading: false,
                    errorMessage: '',
                    usernameError: '',
                    passwordError: fieldMessage(payload, field, MESSAGES.passwordInvalid)
                });
                elements.password.focus();
                return;
            }

            setState({
                isLoading: false,
                errorMessage: '',
                usernameError: MESSAGES.usernameInvalid,
                passwordError: MESSAGES.passwordInvalid
            });
        }

        function handleValidationError(payload) {
            const validation = validate();
            const field = detectField(payload);
            setState({
                isLoading: false,
                errorMessage: '',
                usernameError: validation.usernameError || (
                    field === 'username' ? fieldMessage(payload, field, MESSAGES.usernameInvalid) : ''
                ),
                passwordError: validation.passwordError || (
                    field === 'password' ? fieldMessage(payload, field, MESSAGES.passwordInvalid) : ''
                )
            });
            if (validation.usernameError || validation.passwordError || field) {
                (validation.usernameError || field === 'username'
                    ? elements.username
                    : elements.password).focus();
            } else if (payload && typeof payload.message === 'string') {
                setState({ errorMessage: payload.message });
            }
        }

        async function handleSubmit(event) {
            event.preventDefault();

            if (state.isLoading) {
                return;
            }

            state.username = elements.username.value;
            state.password = elements.password.value;
            const validation = validate();
            if (validation.usernameError || validation.passwordError) {
                setState({
                    errorMessage: '',
                    usernameError: validation.usernameError,
                    passwordError: validation.passwordError
                });
                (validation.usernameError ? elements.username : elements.password).focus();
                return;
            }

            setState({
                isLoading: true,
                errorMessage: '',
                usernameError: '',
                passwordError: ''
            });

            try {
                const response = await fetch(config.apiLoginUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        username: state.username.trim(),
                        password: state.password
                    })
                });

                const payload = await parseJson(response);

                if (response.ok) {
                    const token = extractToken(payload);
                    if (!token) {
                        setState({ isLoading: false, errorMessage: MESSAGES.tokenMissing });
                        return;
                    }
                    try {
                        saveToken(token, state.remember);
                        if (state.remember) {
                            window.localStorage.setItem(REMEMBER_ADMIN_USERNAME_KEY, state.username.trim());
                        } else {
                            window.localStorage.removeItem(REMEMBER_ADMIN_USERNAME_KEY);
                        }
                    } catch (storageError) {
                        setState({ isLoading: false, errorMessage: MESSAGES.storageFailed });
                        return;
                    }
                    window.location.href = config.dashboardUrl;
                    return;
                }

                if (response.status === 401) {
                    handleUnauthorized(payload);
                    return;
                }

                if (response.status === 422) {
                    handleValidationError(payload);
                    return;
                }

                setState({
                    isLoading: false,
                    errorMessage: payload && typeof payload.message === 'string'
                        ? payload.message
                        : MESSAGES.requestFailed
                });
            } catch (error) {
                setState({ isLoading: false, errorMessage: MESSAGES.connectionFailed });
            }
        }

        function handlePasswordToggle() {
            const isHidden = elements.password.type === 'password';
            elements.password.type = isHidden ? 'text' : 'password';
            elements.toggleIcon.className = isHidden ? 'bi bi-eye-fill' : 'bi bi-eye-slash-fill';
            elements.toggle.setAttribute('aria-pressed', String(isHidden));
            elements.toggle.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Tampilkan password');
        }

        elements.username.addEventListener('input', function (event) {
            setState({ username: event.target.value, usernameError: '', errorMessage: '' });
        });

        elements.password.addEventListener('input', function (event) {
            setState({ password: event.target.value, passwordError: '', errorMessage: '' });
        });

        elements.remember.addEventListener('change', function (event) {
            setState({ remember: event.target.checked });
            if (!event.target.checked) {
                try { window.localStorage.removeItem(REMEMBER_ADMIN_USERNAME_KEY); } catch (storageError) {}
            }
        });

        elements.toggle.addEventListener('click', handlePasswordToggle);
        form.addEventListener('submit', handleSubmit);

        render();
    })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="../Js/api-endpoint-labels.js?v=api-tooltip-2"></script>

    <?php endif; ?>

</body>
</html>