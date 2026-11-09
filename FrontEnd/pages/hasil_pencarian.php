<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pencarian | Explore Majaku</title>
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
                    <li class="nav-item"><a class="nav-link" href="index.html">Beranda</a></li>
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
                </div>
            </div>
        </div>
    </nav>

    <!-- Search Bar Atas -->
    <div class="container my-4">
        <div style="max-width: 100%;">
            <div class="search-container mb-0" style="max-width: 100%;" id="searchWrapper">
                <input type="text" class="form-control" id="searchInput" placeholder="Cari destinasi, kata kunci atau lokasi">
                <button class="btn text-success fs-5 border-0" type="button" onclick="handleSearch()">
                    <i class="bi bi-search"></i>
                </button>
            </div>
            <span id="searchError" class="search-error text-start ms-3"></span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mb-5">
        <div class="row">
            <!-- Filter Samping -->
            <div class="col-lg-3 mb-4">
                <h5 class="fw-bold mb-3">Filter</h5>
                
                <div class="mb-4">
                    <h6 class="fw-bold text-secondary small">Jarak</h6>
                    <div class="form-check"><input class="form-check-input" type="checkbox" id="j1"><label class="form-check-label small" for="j1">5 Km</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" id="j2"><label class="form-check-label small" for="j2">5 - 10 Km</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" id="j3"><label class="form-check-label small" for="j3">10 - 20 Km</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" id="j4"><label class="form-check-label small" for="j4">20 - 30 Km</label></div>
                </div>

                <div class="mb-4">
                    <h6 class="fw-bold text-secondary small">Wilayah</h6>
                    <div class="form-check"><input class="form-check-input" type="checkbox" id="w1"><label class="form-check-label small" for="w1">Kuningan</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" id="w2"><label class="form-check-label small" for="w2">Majalengka</label></div>
                </div>

                <div class="mb-4">
                    <h6 class="fw-bold text-secondary small">Kategori</h6>
                    <div class="form-check"><input class="form-check-input" type="checkbox" id="k1"><label class="form-check-label small" for="k1">Wisata Alam & Pegunungan</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" id="k2"><label class="form-check-label small" for="k2">Wisata Air & Curug</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" id="k3"><label class="form-check-label small" for="k3">Wisata Buatan/Kuliner</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" id="k4"><label class="form-check-label small" for="k4">Wisata Sejarah & Budaya</label></div>
                </div>

                <div class="mb-4">
                    <h6 class="fw-bold text-secondary small">Budget</h6>
                    <label class="text-muted small">Minimum Rp</label>
                    <input type="text" class="form-control form-control-sm mb-2" placeholder="contoh : 10.000">
                    <label class="text-muted small">Maximum Rp</label>
                    <input type="text" class="form-control form-control-sm mb-3" placeholder="contoh : 10.000">
                </div>

                <button class="btn btn-custom-primary w-100 py-2">Terapkan Filter</button>
            </div>

            <!-- Hasil Pencarian -->
            <div class="col-lg-9">
                <h4 class="fw-bold">Hasil Pencarian</h4>
                <p class="text-muted small mb-4" id="resultCount">9 Destinasi Ditemukan</p>

                <div class="row row-cols-1 row-cols-md-3 g-4" id="destinationList">
                    <div class="col">
                        <div class="card card-destination h-100">
                            <img src="https://images.unsplash.com/photo-1519681393784-d120267933ba?auto=format&fit=crop&w=500&q=80" class="card-img-top">
                            <div class="card-body">
                                <h6 class="fw-bold mb-1">Wonderland</h6>
                                <p class="text-warning small mb-1"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> <span class="text-muted">(1,8k ulasan)</span></p>
                                <p class="text-muted small mb-2"><i class="bi bi-geo-alt text-danger"></i> Kabupaten Kuningan, Jawa Barat.</p>
                                <div class="text-end text-muted small">5.79 KM</div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card card-destination h-100">
                            <img src="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=500&q=80" class="card-img-top">
                            <div class="card-body">
                                <h6 class="fw-bold mb-1">Cilengkrang</h6>
                                <p class="text-warning small mb-1"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> <span class="text-muted">(2,7k ulasan)</span></p>
                                <p class="text-muted small mb-2"><i class="bi bi-geo-alt text-danger"></i> Kabupaten Kuningan, Jawa Barat.</p>
                                <div class="text-end text-muted small">7.09 KM</div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card card-destination h-100">
                            <img src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=500&q=80" class="card-img-top">
                            <div class="card-body">
                                <h6 class="fw-bold mb-1">Arunika Garden</h6>
                                <p class="text-warning small mb-1"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> <span class="text-muted">(2,5k ulasan)</span></p>
                                <p class="text-muted small mb-2"><i class="bi bi-geo-alt text-danger"></i> Kabupaten Kuningan, Jawa Barat.</p>
                                <div class="text-end text-muted small">5.29 KM</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tangkap parameter pencarian dari URL jika ada
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const q = urlParams.get('q');
            if (q) {
                document.getElementById('searchInput').value = q;
                if (q.length < 2) {
                    triggerEmptySearchState();
                } else {
                    // Integrasi API: GET /api/destinations/search?q={keyword}
                    fetchDestinations(q);
                }
            }
        });

        async function fetchDestinations(keyword) {
            try {
                const response = await fetch(`/api/destinations/search?q=${encodeURIComponent(keyword)}`);
                if (response.status === 200) {
                    const result = await response.json();
                    // Render hasil API ke dalam elemen #destinationList
                } else if (response.status === 422 || response.status === 400) {
                    triggerEmptySearchState();
                }
            } catch (err) {
                console.log('Menggunakan tampilan statis/fallback');
            }
        }

        function handleSearch() {
            const input = document.getElementById('searchInput');
            const errorSpan = document.getElementById('searchError');
            const wrapper = document.getElementById('searchWrapper');
            const query = input.value.trim();

            if (query.length < 2) {
                errorSpan.innerText = 'Masukkan Minimal 2 Karakter';
                wrapper.classList.add('is-invalid-search');
                triggerEmptySearchState();
            } else {
                errorSpan.innerText = '';
                wrapper.classList.remove('is-invalid-search');
                window.location.href = `pencarian.html?q=${encodeURIComponent(query)}`;
            }
        }

        function triggerEmptySearchState() {
            document.getElementById('resultCount').innerText = '0 Destinasi Ditemukan';
            document.getElementById('destinationList').innerHTML = `
                <div class="col-12 text-center py-5">
                    <div class="mb-3 text-success" style="font-size: 3.5rem;">
                        <i class="bi bi-search" style="position: relative;">
                            <i class="bi bi-x text-danger" style="position: absolute; bottom: 0; right: 0; font-size: 2rem;"></i>
                        </i>
                    </div>
                    <h3 class="fw-bold text-success mb-2" style="color: #0d5c46 !important;">Wisata Tidak Di Temukan</h3>
                    <p class="text-muted mb-4 small">Maaf, tidak ada destinasi wisata yang sesuai<br>dengan kata kunci pencarianmu</p>
                    <button class="btn btn-custom-primary px-4 py-2" onclick="resetSearch()">Cari yang lain</button>
                </div>
            `;
        }

        function resetSearch() {
            document.getElementById('searchInput').value = '';
            document.getElementById('searchError').innerText = '';
            document.getElementById('searchWrapper').classList.remove('is-invalid-search');
            window.location.href = 'pencarian.html';
        }
    </script>
</body>
</html>