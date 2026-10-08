<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | Explore Majaku</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <!-- CSS -->
    <link rel="stylesheet" href="../CSS/auth.css?v=20261008-2">
</head>

<body>

    <!-- HEADER -->
    <header class="auth-header">
        <a href="../index.html" class="brand-name">
            Explore Majaku
        </a>
    </header>


    <!-- MAIN -->
    <main class="auth-wrapper">

        <!-- LEFT IMAGE -->
        <section class="auth-image-section">

            <div class="image-overlay"></div>

            <div class="image-content">

                <h1>
                    Jelajahi<br>
                    majalengka dan<br>
                    kuningan dengan<br>
                    Mudah
                </h1>

                <p>
                    Temukan Rekomendasi<br>
                    Wisata dan jadikan wisata<br>
                    favoritmu!
                </p>

            </div>

        </section>


        <!-- RIGHT FORM -->
        <section class="auth-form-section">

            <div class="auth-form-container">

                <h2 class="auth-title">
                    LOGIN
                </h2>
                <form id="loginForm" action="../../Backend/api/fungsi_login.php" method="post" novalidate>
                    <!-- EMAIL -->
                    <div class="form-group">

                        <label for="loginIdentity">
                            Email atau No telephone
                        </label>

                        <input
                            type="text"
                            id="loginIdentity"
                            name="email"
                            class="auth-input"
                            placeholder="Masukkan email/ No telephone anda"
                            autocomplete="username"
                            required
                        >

                        <div class="invalid-feedback">
                            Email atau nomor telephone wajib diisi.
                        </div>

                    </div>


                    <!-- PASSWORD -->
                    <div class="form-group">

                        <label for="loginPassword">
                            Password
                        </label>

                        <div class="password-wrapper">

                            <input
                                type="password"
                                id="loginPassword"
                                name="password"
                                class="auth-input"
                                placeholder="Masukkan email anda"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                data-target="loginPassword"
                                aria-label="Tampilkan password"
                            >
                                <i class="bi bi-eye-slash"></i>
                            </button>

                        </div>

                        <div class="invalid-feedback">
                            Password wajib diisi.
                        </div>

                        <div id="loginMessage" class="login-password-error d-none" role="alert"></div>
                        <div id="loginPasswordError" class="login-password-error d-none" role="alert"></div>

                    </div>


                    <!-- REMEMBER + FORGOT -->
                    <div class="login-options">

                        <div class="remember-me">

                            <input
                                type="checkbox"
                                id="rememberMe"
                            >

                            <label for="rememberMe">
                                Ingat Saya
                            </label>

                        </div>

                        <a
                            href="#"
                            class="forgot-password"
                        >
                            Lupa Kata sandi?
                        </a>

                    </div>


                    <!-- LOGIN BUTTON -->
                    <button
                        type="submit"
                        class="auth-button"
                    >
                        Login
                    </button>

                </form>


                <!-- DIVIDER -->
                <div class="divider">

                    <span></span>

                    <p>Atau</p>

                    <span></span>

                </div>



                    <button type="button" class="social-button" id="googleLogin">
    <span class="google-icon">
        <svg viewBox="0 0 24 24" width="28" height="28">
            <path fill="#4285F4" d="M21.35 12.27c0-.79-.07-1.55-.22-2.27H12v4.3h5.24a4.48 4.48 0 0 1-1.94 2.94v2.45h3.14c1.84-1.69 2.91-4.18 2.91-7.42z"/>
            <path fill="#34A853" d="M12 21.5c2.63 0 4.84-.87 6.45-2.36l-3.14-2.45c-.87.58-1.98.92-3.31.92-2.54 0-4.69-1.72-5.46-4.03H3.3v2.52A9.74 9.74 0 0 0 12 21.5z"/>
            <path fill="#FBBC05" d="M6.54 13.58A5.85 5.85 0 0 1 6.23 12c0-.55.1-1.09.31-1.58V7.9H3.3A9.74 9.74 0 0 0 2.25 12c0 1.57.38 3.05 1.05 4.1l3.24-2.52z"/>
            <path fill="#EA4335" d="M12 6.39c1.43 0 2.71.49 3.72 1.45l2.79-2.79C16.84 3.43 14.63 2.5 12 2.5a9.74 9.74 0 0 0-8.7 5.4l3.24 2.52C7.31 8.11 9.46 6.39 12 6.39z"/>
        </svg>
    </span>

    <span>Masuk dengan Google</span>
</button>



                <!-- REGISTER LINK -->
                <div class="switch-auth">

                    <span>
                        Belum punya akun?
                    </span>

                    <a href="Register.php">
                        DAFTAR
                    </a>

                </div>

            </div>

        </section>

    </main>


    <!-- JAVASCRIPT -->
    <script src="../js/auth.js?v=20261008-3"></script>

</body>
</html>