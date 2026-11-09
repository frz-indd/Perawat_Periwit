<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Explore Majaku</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="container-fluid p-0">
        <div class="row g-0 auth-wrapper">
            <div class="col-lg-6 auth-banner d-none d-lg-flex">
                <h1 class="display-5 fw-bold mb-3">Jelajahi majalengka dan kuningan dengan Mudah</h1>
                <p class="lead">Temukan Rekomendasi Wisata dan jadikan wisata favoritmu!</p>
            </div>
            
            <div class="col-lg-6 d-flex flex-column justify-content-between p-4 p-md-5 bg-white">
                <div><h4 class="text-center fw-serif fst-italic text-success mb-4">Explore Majaku</h4></div>
                
                <div class="w-100 mx-auto" style="max-width: 400px;">
                    <h3 class="fw-bold mb-4 text-center">LOGIN</h3>
                    <form id="loginForm" novalidate>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Email atau No telepon</label>
                            <input type="text" class="form-control py-2" id="loginEmailInput" placeholder="Masukkan Email atau No Telepon">
                            <span id="loginEmailError" class="error-message"></span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control py-2" id="loginPasswordInput" placeholder="Masukkan Password">
                                <span class="input-group-text bg-white" id="toggleLoginPassword" style="cursor: pointer;"><i class="bi bi-eye" id="eyeLoginPass"></i></span>
                            </div>
                            <span id="loginPasswordError" class="error-message"></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-4 small">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">Ingat Saya</label>
                            </div>
                            <a href="#" class="text-decoration-none text-muted">Lupa password?</a>
                        </div>
                        <button type="submit" class="btn btn-custom-primary w-100 py-2 mb-3">Login</button>
                        
                        <div class="text-center text-muted my-3 position-relative">
                            <hr><span class="position-absolute top-50 start-50 translate-middle bg-white px-3 small">Atau</span>
                        </div>

                        <!-- Endpoint API: GET /Backend/api/google_login.php -->
                        <a href="/Backend/api/google_login.php" class="btn btn-outline-dark w-100 py-2 mb-4 d-flex align-items-center justify-content-center gap-2 text-decoration-none">
                            <i class="bi bi-google text-danger"></i> Masuk dengan Google
                        </a>

                        <p class="text-center small text-muted">
                            Belum punya akun? <a href="register.html" class="text-success fw-bold text-decoration-none">REGISTER</a>
                        </p>
                    </form>
                </div>
                <div></div>
            </div>
        </div>
    </div>

    <!-- Modal Sukses Login -->
    <div class="modal fade" id="loginSuccessModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-4 text-center border-0 shadow-lg rounded-4">
                <div class="modal-body">
                    <div class="success-icon-box"><i class="bi bi-check-lg"></i></div>
                    <h4 class="fw-bold text-success mb-3" style="color: #0d5c46 !important;">Login Berhasil!</h4>
                    <p class="text-muted mb-4 small px-3">Selamat datang kembali di Explore Majaku</p>
                    <button type="button" class="btn btn-custom-primary w-100 py-2 fw-medium" onclick="redirectToHome()">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="login.js"></script>
</body>
</html>