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
        $_SESSION['nama'] = htmlspecialchars($nama_baru);
        $pesan = "Perubahan berhasil disimpan!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<meta charset="utf-8" />
<title>Edit Profil - Explore Majaku</title>
<link rel="stylesheet" href="../CSS/edit-profil.css">
</head>
<body>
<form action="" method="POST" class="edit-profil">
    <?php if (!empty($pesan)): ?>
        <div style="position: absolute; top: 90px; left: 124px; color: #166C5C; font-weight: bold; font-family: 'Poppins', sans-serif; z-index: 999;">
            <?php echo $pesan; ?>
        </div>
    <?php endif; ?>

    <div class="group-2">
        <div class="group-3">
            <div class="rectangle-2"></div>
            <div class="text-wrapper-7">Explore Majaku</div>
            <a href="index.php"><div class="img"></div></a>
            <div class="vector-2"></div>
        </div>
        <div class="text-wrapper-8">Beranda</div>
        <div class="text-wrapper-9">kategori</div>
        <div class="tentang">Tentang</div>
    </div>

    <a href="profil.php" class="frame">
        <div class="eva-arrow-back-fill"></div>
        <div class="text-wrapper-11">Back</div>
    </a>

    <div class="group">
        <div class="ellipse"></div>
        <div class="vector"></div>
    </div>
    <div class="text-wrapper-3">Ubah Foto Profil</div>

    <div class="text-wrapper-4">Nama Lengkap</div>
    <div class="form-email">
        <input type="text" name="nama_lengkap" class="email-or-no" value="<?php echo htmlspecialchars($_SESSION['nama']); ?>" required>
    </div>

    <div class="text-wrapper-5">Email</div>
    <div class="text-wrapper-6"><?php echo $_SESSION['email']; ?></div>
    <p class="p">Email tidak dapat diubah karena digunakan sebagai identitas akun.</p>

    <button type="submit" name="simpan" class="rectangle"></button>
    <div class="text-wrapper">Simpan Perubahan</div>

    <a href="profil.php" class="div"></a>
    <div class="text-wrapper-2">Batal</div>
</form>
</body>
</html>