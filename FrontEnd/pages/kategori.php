<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Wisata | Explore Majaku</title>
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
                        <a class="nav-link dropdown-toggle active" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">kategori</a>
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

    <!-- Header Kategori -->
    <div class="container my-4">
        <div class="hero-section" id="categoryHeader">
            <h2 id="catTitle">WISATA ALAM DAN PEGUNUNGAN</h2>
            <p id="catDesc" class="mb-4">Nikmati keindahan alam Majalengka dan Kuningan dari perbukitan, hingga panorama menakjubkan</p>
            
            <div class="w-100" style="max-width: 750px; margin: 0 auto;">
                <div class="search-container mb-0" id="searchWrapper">
                    <input type="text" class="form-control" id="searchInput" placeholder="Cari destinasi, kata kunci atau lokasi">
                    <button class="btn text-success fs-5 border-0" type="button" onclick="handleCategorySearch()">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="pencarian.html" class="btn text-dark fs-5 border-0 ms-2"><i class="bi bi-sliders"></i></a>
                </div>
                <span id="searchError" class="search-error text-start ms-3"></span>
            </div>
        </div>
    </div>

    <!-- Konten Destinasi Kategori -->
    <div class="container my-5">
        <h4 id="catSubTitle" class="fw-bold mb-4">Wisata Alam & Pegunungan</h4>
        <div class="row row-cols-1 row-cols-md-4 g-4" id="categoryItems">
            <div class="col">
                <div class="card card-destination h-100">
                    <img src="https://images.unsplash.com/photo-1519681393784-d120267933ba?auto=format&fit=crop&w=500&q=80" class="card-img-top">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Cibuntu</h6>
                        <p class="text-warning small mb-1"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> <span class="text-muted">(1,7k ulasan)</span></p>
                        <p class="text-muted small mb-2"><i class="bi bi-geo-alt text-danger"></i> Kabupaten Kuningan, Jawa Barat.</p>
                        <div class="text-end text-muted small">5.29 KM</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card card-destination h-100">
                    <img src="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=500&q=80" class="card-img-top">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Lembah Cilengkrang</h6>
                        <p class="text-warning small mb-1"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> <span class="text-muted">(2,7k ulasan)</span></p>
                        <p class="text-muted small mb-2"><i class="bi bi-geo-alt text-danger"></i> Kabupaten Kuningan, Jawa Barat.</p>
                        <div class="text-end text-muted small">5.70 KM</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card card-destination h-100">
                    <img src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=500&q=80" class="card-img-top">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Bukit Pamoroan</h6>
                        <p class="text-warning small mb-1"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> <span class="text-muted">(2,6k ulasan)</span></p>
                        <p class="text-muted small mb-2"><i class="bi bi-geo-alt text-danger"></i> Kabupaten Majalengka, Jawa Barat.</p>
                        <div class="text-end text-muted small">8.70 KM</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card card-destination h-100">
                    <img src="https://images.unsplash.com/photo-1433086966358-54859d0ed716?auto=format&fit=crop&w=500&q=80" class="card-img-top">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Bukit Panyaweuyan</h6>
                        <p class="text-warning small mb-1"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> <span class="text-muted">(2,5k ulasan)</span></p>
                        <p class="text-muted small mb-2"><i class="bi bi-geo-alt text-danger"></i> Kabupaten Kuningan, Jawa Barat.</p>
                        <div class="text-end text-muted small">8.79 KM</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const cat = urlParams.get('cat') || 'alam';
        const categoryId = urlParams.get('id') || 1;

        const categoriesData = {
            alam: { title: "WISATA ALAM DAN PEGUNUNGAN", desc: "Nikmati keindahan alam Majalengka dan Kuningan dari perbukitan, hingga panorama menakjubkan", subtitle: "Wisata Alam & Pegunungan" },
            air: { title: "WISATA AIR DAN CURUG", desc: "Nikmati kesegaran wisata air Majalengka dan Kuningan, dari curug yang asri hingga pemandangan alam yang menyegarkan.", subtitle: "Wisata Air & Curug" },
            buatan: { title: "WISATA BUATAN DAN KULINER", desc: "Jelajahi beragam destinasi buatan dan nikmati kuliner khas Majalengka dan Kuningan yang menggugah selera.", subtitle: "Wisata Buatan & Kuliner" },
            sejarah: { title: "WISATA SEJARAH DAN BUDAYA", desc: "Jelajahi kekayaan budaya dan jejak sejarah Majalengka dan Kuningan yang penuh cerita dan makna.", subtitle: "Wisata Sejarah & Budaya" }
        };

        if(categoriesData[cat]) {
            document.getElementById('catTitle').innerText = categoriesData[cat].title;
            document.getElementById('catDesc').innerText = categoriesData[cat].desc;
            document.getElementById('catSubTitle').innerText = categoriesData[cat].subtitle;
        }

        // Integrasi API: GET /categories/{id}/wisata
        window.addEventListener('DOMContentLoaded', async () => {
            try {
                const response = await fetch(`/categories/${categoryId}/wisata`);
                if (response.status === 200) {
                    const resData = await response.json();
                    document.getElementById('catTitle').innerText = resData.data.kategori.nama;
                    document.getElementById('catSubTitle').innerText = resData.data.kategori.nama;
                    // Render resData.data.wisata dinamis ke #categoryItems jika diperlukan
                }
            } catch (err) {
                console.log('Menggunakan data statis kategori');
            }
        });

        function handleCategorySearch() {
            const input = document.getElementById('searchInput');
            const errorSpan = document.getElementById('searchError');
            const wrapper = document.getElementById('searchWrapper');
            const query = input.value.trim();

            if (query.length < 2) {
                errorSpan.innerText = 'Masukkan Minimal 2 Karakter';
                wrapper.classList.add('is-invalid-search');
                
                document.getElementById('categoryItems').innerHTML = `
                    <div class="col-12 text-center py-5">
                        <div class="mb-3 text-success" style="font-size: 3.5rem;">
                            <i class="bi bi-search" style="position: relative;">
                                <i class="bi bi-x text-danger" style="position: absolute; bottom: 0; right: 0; font-size: 2rem;"></i>
                            </i>
                        </div>
                        <h3 class="fw-bold text-success mb-2" style="color: #0d5c46 !important;">Wisata Tidak Di Temukan</h3>
                        <p class="text-muted mb-4 small">Maaf, tidak ada destinasi wisata yang sesuai<br>dengan kata kunci pencarianmu dalam kategori ini</p>
                        <button class="btn btn-custom-primary px-4 py-2" onclick="location.reload()">Lihat semua wisata</button>
                    </div>
                `;
            } else {
                errorSpan.innerText = '';
                wrapper.classList.remove('is-invalid-search');
                window.location.href = `pencarian.html?q=${encodeURIComponent(query)}`;
            }
        }
    </script>
</body>
</html>