
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['id_user'])) {
    header("Location: ../../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Explore Majaku</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../CSS/login.css">
</head>
<body>

<header class="header">
    <a href="../../index.php" class="brand">Explore Majaku</a>
</header>

<main class="auth-container">

    <section class="auth-image">
        <div class="image-overlay">
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
        </div>
    </section>

    <section class="auth-form-section">

        <div class="form-wrapper">

            <h2>LOGIN</h2>

            <div id="alertBox" class="alert-box" role="alert" aria-live="polite"></div>

            <form id="loginForm" novalidate>

                <div class="form-group">
                    <label for="identity">Email atau No telepon</label>

                    <input
                        type="text"
                        id="identity"
                        name="identity"
                        placeholder="Masukkan Email or No Telephon"
                        autocomplete="username"
                    >

                    <small class="error-message" id="identityError"></small>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>

                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Masukkan Password"
                            autocomplete="current-password"
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            data-target="password"
                            aria-label="Tampilkan password"
                        >
                            <i class="eye-icon"></i>
                        </button>
                    </div>

                    <small class="error-message" id="passwordError"></small>
                </div>

                <button type="submit" id="loginButton" class="submit-button">
                    <span class="button-text">Login</span>
                    <span class="button-loader"></span>
                </button>

            </form>

            <div class="divider">
                <span></span>
                <p>Atau</p>
                <span></span>
            </div>

            <a href="../../Backend/api/google_login.php" class="google-button">
                <img
                    src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg"
                    alt="Google"
                >
                <span>Login dengan Google</span>
            </a>

            <p class="register-text">
                Belum Punya Akun?
                <a href="Register.php">REGISTER</a>
            </p>

        </div>
    </section>

</main>

<script src="../Js/login.js"></script>
<!-- ================= SUCCESS MODAL ================= -->
<div
    class="auth-success-modal"
    id="authSuccessModal"
    hidden
>
    <section
        class="auth-success-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="authSuccessTitle"
        aria-describedby="authSuccessDescription"
    >
        <span class="auth-success-icon" aria-hidden="true"></span>
        <h2 id="authSuccessTitle"></h2>
        <p id="authSuccessDescription"></p>
        <button type="button" class="auth-success-button" id="authSuccessConfirm">
            OK
        </button>
    </section>
</div>
</body>
</html>
