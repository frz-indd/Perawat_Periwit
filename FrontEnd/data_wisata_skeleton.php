
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Wisata - Explore Majaku</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f7fb;
            color: #172337;
        }

        /* SIDEBAR */
        .sidebar {
            width: 220px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: linear-gradient(180deg, #075b42, #064b39);
            color: white;
            padding: 25px 12px;
            z-index: 100;
        }

        .brand {
            text-align: center;
            margin-bottom: 45px;
        }

        .brand i {
            font-size: 38px;
        }

        .brand h4 {
            font-size: 19px;
            font-weight: 700;
            margin-top: 8px;
        }

        .brand p {
            font-size: 12px;
            font-style: italic;
        }

        .menu {
            list-style: none;
            padding: 0;
        }

        .menu li {
            margin-bottom: 7px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            color: white;
            text-decoration: none;
            font-size: 13px;
            border-radius: 8px;
            transition: .3s;
        }

        .menu a:hover,
        .menu a.active {
            background: #239b69;
        }

        .menu i {
            font-size: 17px;
        }

        .menu-divider {
            border-top: 1px solid rgba(255,255,255,.25);
            margin: 25px 10px;
        }

        /* MAIN CONTENT */
        .main-content {
            margin-left: 220px;
            min-height: 100vh;
            padding: 25px 30px;
            position: relative;
            overflow: hidden;
        }

        .main-content::before {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 500px;
            height: 200px;
            background: linear-gradient(135deg, transparent, #dff4e9);
            clip-path: polygon(0 100%, 25% 40%, 40% 65%, 65% 10%, 85% 55%, 100% 25%, 100% 100%);
            opacity: .7;
            pointer-events: none;
        }

        /* HEADER */
        .topbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 500;
        }

        .admin-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #dce2e9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7c8796;
            font-size: 20px;
        }

        .page-heading {
            margin-bottom: 25px;
            position: relative;
            z-index: 1;
        }

        .page-heading h2 {
            font-size: 27px;
            font-weight: 700;
        }

        .page-heading p {
            color: #64748b;
            font-size: 13px;
            margin-top: 5px;
        }

        /* SKELETON ANIMATION */
        .skeleton {
            background: linear-gradient(
                90deg,
                #e1e5eb 25%,
                #f1f3f6 50%,
                #e1e5eb 75%
            );
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 7px;
        }

        @keyframes loading {
            0% {
                background-position: 200% 0;
            }
            100% {
                background-position: -200% 0;
            }
        }

        /* FILTER */
        .filter-container {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }

        .filter-item {
            height: 48px;
            background: white;
            border: 1px solid #e0e5ec;
            border-radius: 10px;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            box-shadow: 0 2px 6px rgba(0,0,0,.04);
        }

        .search-filter {
            flex: 1.5;
        }

        .filter-item i {
            color: #64748b;
            font-size: 18px;
        }

        .filter-text {
            height: 15px;
            width: 75%;
        }

        .dropdown-text {
            height: 15px;
            width: 65%;
        }

        .add-button {
            width: 145px;
            height: 48px;
            background: #8d969f;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .add-button i {
            color: white;
            font-size: 20px;
        }

        .add-button .skeleton {
            width: 75px;
            height: 15px;
            background: rgba(255,255,255,.35);
        }

        /* TABLE CARD */
        .table-card {
            background: white;
            padding: 17px 15px;
            border-radius: 13px;
            box-shadow: 0 3px 12px rgba(0,0,0,.07);
            border: 1px solid #e4e8ef;
            position: relative;
            z-index: 1;
        }

        .table-header {
            display: grid;
            grid-template-columns: .45fr .85fr 1.5fr 1fr 1.1fr 1.1fr .7fr .9fr;
            gap: 15px;
            align-items: center;
            padding: 17px 15px;
            background: #f1f3f6;
            border-radius: 9px;
            margin-bottom: 5px;
        }

        .table-row {
            display: grid;
            grid-template-columns: .45fr .85fr 1.5fr 1fr 1.1fr 1.1fr .7fr .9fr;
            gap: 15px;
            align-items: center;
            min-height: 72px;
            padding: 10px 15px;
            border-bottom: 1px solid #e7eaf0;
        }

        .table-row:last-of-type {
            border-bottom: none;
        }

        .header-line {
            width: 65px;
            height: 13px;
        }

        .row-line {
            width: 85%;
            height: 15px;
        }

        .photo-skeleton {
            width: 90px;
            height: 52px;
            border-radius: 8px;
        }

        .action-group {
            display: flex;
            gap: 8px;
        }

        .action-skeleton {
            width: 32px;
            height: 32px;
            border-radius: 7px;
        }

        /* PAGINATION */
        .table-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 8px 0;
        }

        .footer-line {
            width: 180px;
            height: 15px;
        }

        .pagination-skeleton {
            display: flex;
            gap: 8px;
        }

        .page-box {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            border: 1px solid #dce2e9;
            background: #f5f7fa;
        }

        /* RESPONSIVE */
        @media (max-width: 992px) {
            .sidebar {
                width: 75px;
            }

            .brand h4,
            .brand p,
            .menu span {
                display: none;
            }

            .brand i {
                font-size: 28px;
            }

            .menu a {
                justify-content: center;
            }

            .main-content {
                margin-left: 75px;
                padding: 20px;
            }

            .filter-container {
                flex-wrap: wrap;
            }

            .table-card {
                overflow-x: auto;
            }

            .table-header,
            .table-row {
                min-width: 900px;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px;
            }

            .page-heading h2 {
                font-size: 22px;
            }

            .filter-item {
                flex: 1 1 100%;
            }

            .add-button {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="brand">
            <i class="bi bi-mountains"></i>
            <h4>Explore Majaku</h4>
            <p>Admin Panel</p>
        </div>

        <ul class="menu">

            <li>
                <a href="#">
                    <i class="bi bi-house-door-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li>
                <a href="#" class="active">
                    <i class="bi bi-geo-alt-fill"></i>
                    <span>Data Wisata</span>
                </a>
            </li>

            <li>
                <a href="#">
                    <i class="bi bi-grid-fill"></i>
                    <span>Kategori Wisata</span>
                </a>
            </li>

            <li>
                <a href="#">
                    <i class="bi bi-star-fill"></i>
                    <span>Fasilitas Wisata</span>
                </a>
            </li>

            <li>
                <a href="#">
                    <i class="bi bi-chat-left-text-fill"></i>
                    <span>Ulasan</span>
                </a>
            </li>

            <li>
                <a href="#">
                    <i class="bi bi-people-fill"></i>
                    <span>Pengguna</span>
                </a>
            </li>

            <div class="menu-divider"></div>

            <li>
                <a href="#">
                    <i class="bi bi-person-fill"></i>
                    <span>Profil Admin</span>
                </a>
            </li>

            <li>
                <a href="#">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </li>

        </ul>
    </aside>


    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- TOPBAR -->
        <div class="topbar">
            <i class="bi bi-bell fs-5"></i>

            <div class="admin-profile">
                <div class="admin-avatar">
                    <i class="bi bi-person-fill"></i>
                </div>
                <span>admin</span>
                <i class="bi bi-chevron-down"></i>
            </div>
        </div>


        <!-- PAGE HEADING -->
        <div class="page-heading">
            <h2>Data Wisata</h2>
            <p>Kelola semua data destinasi wisata</p>
        </div>


        <!-- FILTER SECTION -->
        <div class="filter-container">

            <!-- Search -->
            <div class="filter-item search-filter">
                <i class="bi bi-search"></i>
                <div class="skeleton filter-text"></div>
            </div>

            <!-- Kabupaten -->
            <div class="filter-item">
                <div class="skeleton dropdown-text"></div>
                <i class="bi bi-chevron-down ms-auto"></i>
            </div>

            <!-- Kategori -->
            <div class="filter-item">
                <div class="skeleton dropdown-text"></div>
                <i class="bi bi-chevron-down ms-auto"></i>
            </div>

            <!-- Tombol Tambah -->
            <div class="add-button">
                <i class="bi bi-plus-lg"></i>
                <div class="skeleton"></div>
            </div>

        </div>


        <!-- TABLE SECTION -->
        <div class="table-card">

            <!-- TABLE HEADER -->
            <div class="table-header">

                <div class="skeleton header-line"></div>
                <div class="skeleton header-line"></div>
                <div class="skeleton header-line"></div>
                <div class="skeleton header-line"></div>
                <div class="skeleton header-line"></div>
                <div class="skeleton header-line"></div>
                <div class="skeleton header-line"></div>
                <div class="skeleton header-line"></div>

            </div>


            <!-- TABLE ROWS -->
            <?php for ($i = 0; $i < 8; $i++) : ?>

                <div class="table-row">

                    <!-- Nomor -->
                    <div class="skeleton row-line"></div>

                    <!-- Foto Wisata -->
                    <div class="skeleton photo-skeleton"></div>

                    <!-- Nama Wisata -->
                    <div class="skeleton row-line"></div>

                    <!-- Kabupaten -->
                    <div class="skeleton row-line"></div>

                    <!-- Kategori -->
                    <div class="skeleton row-line"></div>

                    <!-- Harga Tiket -->
                    <div class="skeleton row-line"></div>

                    <!-- Rating -->
                    <div class="skeleton row-line"></div>

                    <!-- Aksi -->
                    <div class="action-group">
                        <div class="skeleton action-skeleton"></div>
                        <div class="skeleton action-skeleton"></div>
                        <div class="skeleton action-skeleton"></div>
                    </div>

                </div>

            <?php endfor; ?>


            <!-- TABLE FOOTER -->
            <div class="table-footer">

                <div class="skeleton footer-line"></div>

                <div class="pagination-skeleton">
                    <div class="page-box"></div>
                    <div class="skeleton page-box"></div>
                    <div class="skeleton page-box"></div>
                    <div class="skeleton page-box"></div>
                    <div class="page-box"></div>
                </div>

            </div>

        </div>

    </main>

</body>
</html>
