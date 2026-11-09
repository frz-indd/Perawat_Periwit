<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda | Explore Majaku</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white py-3 shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.html">Explore Majaku</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" href="index.html">Beranda</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">kategori</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="kategori.html?cat=alam">Wisata Alam & pegunungan</a></li>
                            <li><a class="dropdown-item" href="kategori.html?cat=air">Wisata Air & Curug</a></li>
                            <li><a class="dropdown-item" href="kategori.html?cat=buatan">Wisata Buatan & Kuliner</a></li>
                            <li><a class="dropdown-item" href="kategori.html?cat=sejarah">Wisata Sejarah & Budaya</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="#tentang">Tentang</a></li>
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <a href="login.html" class="text-dark fs-5"><i class="bi bi-person-circle"></i></a>
                    <a href="login.html" class="btn btn-custom-primary px-4 py-2">Login</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero / Search Section -->
    <div class="container my-4">
        <div class="hero-section">
            <h2>Mau Liburan Ke mana?</h2>
            <p class="mb-4">Temukan destinasi wisata terbaik di Majalengka & Kuningan</p>
            
            <div class="w-100" style="max-width: 750px; margin: 0 auto;">
                <div class="search-container mb-0" id="searchWrapper">
                    <input type="text" class="form-control" id="searchInput" placeholder="Cari destinasi, kata kunci atau lokasi">
                    <button class="btn text-success fs-5 border-0" type="button" onclick="handleSearch()">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="pencarian.html" class="btn text-dark fs-5 border-0 ms-2" title="Buka Filter">
                        <i class="bi bi-sliders"></i>
                    </a>
                </div>
                <span id="searchError" class="search-error text-start ms-3"></span>
            </div>
        </div>
    </div>

    <!-- Rekomendasi -->
    <div class="container my-5">
        <h4 class="fw-bold mb-4">Rekomendasi Untuk kamu</h4>
        <div class="row row-cols-1 row-cols-md-4 g-4">
            <div class="col">
                <div class="card card-destination h-100">
                    <img src="https://images.unsplash.com/photo-1519681393784-d120267933ba?auto=format&fit=crop&w=500&q=80" class="card-img-top">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Cilengkrang</h6>
                        <p class="text-warning small mb-1"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> <span class="text-muted">(2,7k ulasan)</span></p>
                        <p class="text-muted small mb-2"><i class="bi bi-geo-alt text-danger"></i> Kabupaten Kuningan, Jawa Barat.</p>
                        <div class="text-end text-muted small">5.76 KM</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card card-destination h-100">
                    <img src="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=500&q=80" class="card-img-top">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Bukit Pamoroan</h6>
                        <p class="text-warning small mb-1"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> <span class="text-muted">(2,6k ulasan)</span></p>
                        <p class="text-muted small mb-2"><i class="bi bi-geo-alt text-danger"></i> Kabupaten Majalengka, Jawa Barat.</p>
                        <div class="text-end text-muted small">5.76 KM</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card card-destination h-100">
                    <img src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=500&q=80" class="card-img-top">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Arunika Garden</h6>
                        <p class="text-warning small mb-1"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> <span class="text-muted">(2,5k ulasan)</span></p>
                        <p class="text-muted small mb-2"><i class="bi bi-geo-alt text-danger"></i> Kabupaten Kuningan, Jawa Barat.</p>
                        <div class="text-end text-muted small">7.68 KM</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card card-destination h-100">
                    <img src="https://images.unsplash.com/photo-1433086966358-54859d0ed716?auto=format&fit=crop&w=500&q=80" class="card-img-top">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Curug putri palutungan</h6>
                        <p class="text-warning small mb-1"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> <span class="text-muted">(1,5k ulasan)</span></p>
                        <p class="text-muted small mb-2"><i class="bi bi-geo-alt text-danger"></i> Kabupaten Kuningan, Jawa Barat.</p>
                        <div class="text-end text-muted small">6.68 KM</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function handleSearch() {
            const input = document.getElementById('searchInput');
            const errorSpan = document.getElementById('searchError');
            const wrapper = document.getElementById('searchWrapper');
            const query = input.value.trim();

            // Validasi Universal: Minimal 2 Karakter (Endpoint API: GET /api/destinations/search?q={keyword})
            if (query.length < 2) {
                errorSpan.innerText = 'Masukkan Minimal 2 Karakter';
                wrapper.classList.add('is-invalid-search');
            } else {
                errorSpan.innerText = '';
                wrapper.classList.remove('is-invalid-search');
                window.location.href = `pencarian.html?q=${encodeURIComponent(query)}`;
            }
        }
    </script>
</body>
</html>