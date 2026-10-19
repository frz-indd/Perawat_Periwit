<?php
session_start();

if (!isset($_SESSION['nama'])) {
    $_SESSION['nama'] = "Nama User";
    $_SESSION['email'] = "PerawatPeriwit124@gmail.com";
}

$pesan = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan'])) {
    $nama_baru = trim($_POST['nama_lengkap']);
    if (!empty($nama_baru)) {
        $_SESSION['nama'] = $nama_baru;
        $pesan = "Perubahan berhasil disimpan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>Edit Profil - Explore Majaku</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../CSS/edit-profil.css">
    <link rel="stylesheet" href="../CSS/site.css?v=20261019">
</head>
<body class="edit-profile-page">
    <header class="site-header edit-header">
        <nav class="main-nav" aria-label="Navigasi utama">
            <a class="edit-brand" href="../../index.php">Explore Majaku</a>
            <div class="nav-links">
                <a href="../../index.php">Beranda</a>
                <a href="kategori.php?kategori=alam">Kategori</a>
                <a href="tentang.php">Tentang</a>
            </div>
            <div class="nav-icons">
                <a href="hasil_pencarian.php" aria-label="Cari"><i class="bi bi-search" aria-hidden="true"></i></a>
                <a href="profil.php" aria-label="Profil"><i class="bi bi-person-circle" aria-hidden="true"></i></a>
            </div>
        </nav>
    </header>

    <main class="edit-main">
        <a href="profil.php" class="edit-back"><i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke profil</a>
        <form action="" method="POST" class="edit-card">
            <div class="edit-avatar" aria-hidden="true"><i class="bi bi-person"></i></div>
            <h1>Edit Profil</h1>

            <?php if (!empty($pesan)): ?>
                <p class="edit-message" role="status"><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <div class="edit-field">
                <label for="full-name">Nama lengkap</label>
                <input id="full-name" type="text" name="nama_lengkap" value="<?= htmlspecialchars($_SESSION['nama'], ENT_QUOTES, 'UTF-8'); ?>" autocomplete="name" required>
            </div>

            <div class="edit-field">
                <span>Email</span>
                <div class="edit-email"><?= htmlspecialchars($_SESSION['email'], ENT_QUOTES, 'UTF-8'); ?></div>
                <p class="edit-hint">Email tidak dapat diubah karena digunakan sebagai identitas akun.</p>
            </div>

            <div class="edit-actions">
                <a href="profil.php">Batal</a>
                <button type="submit" name="simpan">Simpan perubahan</button>
            </div>
        </form>
    </main>
</body>
</html>