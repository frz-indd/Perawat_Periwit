<?php
session_start();

function e($s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

require_once __DIR__ . '/../../Backend/database/koneksi.php'; // menghasilkan $conn

/* ----- hanya untuk user yang sudah login (sama seperti profil.php) ----- */
$userId = filter_var($_SESSION['id_user'] ?? null, FILTER_VALIDATE_INT);
if ($userId === false || $userId <= 0) {
    header('Location: ../auth/Login.php');
    exit;
}
$userId = (int) $userId;

/** Cari tabel user beserta daftar kolomnya. */
function info_tabel_user(mysqli $conn): ?array {
    foreach (['user', 'users', 'pengguna'] as $t) {
        try {
            $res = $conn->query("SHOW COLUMNS FROM `$t`");
        } catch (Throwable $ex) {
            continue;
        }
        if (!$res) continue;
        $kol = [];
        while ($r = $res->fetch_assoc()) {
            $kol[strtolower($r['Field'])] = $r['Field'];
        }
        if (isset($kol['id_user'])) return ['tabel' => $t, 'kolom' => $kol];
    }
    return null;
}

/** Ambil nilai pertama yang terisi dari daftar kolom kandidat. */
function nilai_pertama(array $row, array $info, array $kandidat): string {
    foreach ($kandidat as $k) {
        if (isset($info['kolom'][$k])) {
            $v = trim((string) ($row[$info['kolom'][$k]] ?? ''));
            if ($v !== '') return $v;
        }
    }
    return '';
}

/** Simpan nama & foto ke database. Kolom foto dibuat otomatis jika belum ada. */
function simpan_profil_db(mysqli $conn, array $info, int $userId, string $nama, string $foto): bool {
    try {
        $t = $info['tabel'];
        $kol = $info['kolom'];

        $kolNama = array_values(array_filter(
            array_map(fn($k) => $kol[$k] ?? null, ['nama', 'nama_lengkap', 'nama_user'])
        ));
        if (!$kolNama) {
            $conn->query("ALTER TABLE `$t` ADD COLUMN `nama` VARCHAR(100) NULL");
            $kolNama = ['nama'];
        }

        $kolFoto = array_values(array_filter(
            array_map(fn($k) => $kol[$k] ?? null, ['foto_profil', 'foto', 'avatar'])
        ));
        if (!$kolFoto) {
            $conn->query("ALTER TABLE `$t` ADD COLUMN `foto_profil` VARCHAR(255) NULL");
            $kolFoto = ['foto_profil'];
        }

        $set = [];
        $val = [];
        foreach ($kolNama as $k) { $set[] = "`$k` = ?"; $val[] = $nama; }
        foreach ($kolFoto as $k) { $set[] = "`$k` = ?"; $val[] = $foto; }
        $val[] = $userId;

        $stmt = $conn->prepare("UPDATE `$t` SET " . implode(', ', $set) . " WHERE Id_user = ?");
        if (!$stmt) return false;
        $stmt->bind_param(str_repeat('s', count($val) - 1) . 'i', ...$val);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    } catch (Throwable $ex) {
        return false;
    }
}

/* ----- data user saat ini (dari database, sama seperti profil.php) ----- */
$info = info_tabel_user($conn);
$row = [];
if ($info) {
    try {
        $stmt = $conn->prepare("SELECT * FROM `{$info['tabel']}` WHERE Id_user = ? LIMIT 1");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();
    } catch (Throwable $ex) {
        $row = [];
    }
}

$email = trim((string) ($_SESSION['email'] ?? ($_SESSION['user']['email'] ?? '')));
if ($email === '' && $info) {
    $email = nilai_pertama($row, $info, ['email', 'no_hp', 'telepon']);
}

$nama = $info ? nilai_pertama($row, $info, ['nama', 'nama_lengkap', 'nama_user', 'username']) : '';
if ($nama === '') $nama = trim((string) ($_SESSION['nama'] ?? ($_SESSION['user']['nama'] ?? '')));
if ($nama === '' || strcasecmp($nama, 'Nama User') === 0) {
    $nama = filter_var($email, FILTER_VALIDATE_EMAIL) ? ucfirst((string) strstr($email, '@', true)) : '';
}

$foto = $info ? nilai_pertama($row, $info, ['foto_profil', 'foto', 'avatar']) : '';
if ($foto === '') $foto = trim((string) ($_SESSION['foto_profil'] ?? ($_SESSION['foto'] ?? '')));

// Foto bawaan dari email login (Gravatar), sama seperti profil.php
$gravatar = filter_var($email, FILTER_VALIDATE_EMAIL)
    ? 'https://www.gravatar.com/avatar/' . hash('sha256', strtolower($email)) . '?s=440&d=404'
    : '';

$errors = [];
$sukses = false;
$gagal  = '';

if (!empty($_SESSION['flash_profil'])) {
    $sukses = true;
    unset($_SESSION['flash_profil']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaBaru = trim($_POST['nama_lengkap'] ?? '');
    $fotoBaru = $foto;
    $fileBaru = null;

    if ($namaBaru === '') {
        $errors['nama'] = 'Nama lengkap wajib diisi.';
    } elseif (mb_strlen($namaBaru) > 100) {
        $errors['nama'] = 'Nama lengkap maksimal 100 karakter.';
    }

    if (!empty($_FILES['foto']['name'])) {
        $izin = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $errors['foto'] = 'Foto gagal diunggah. Coba lagi.';
        } else {
            $tipe = mime_content_type($_FILES['foto']['tmp_name']);
            if (!isset($izin[$tipe])) {
                $errors['foto'] = 'Format foto harus JPG, PNG, atau WEBP.';
            } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
                $errors['foto'] = 'Ukuran foto maksimal 2 MB.';
            } else {
                // Disimpan di root proyek (../../uploads) karena profil.php membaca dari sana
                $folder = __DIR__ . '/../../uploads/profil/';
                if (!is_dir($folder)) mkdir($folder, 0755, true);
                $namaFile = uniqid('profil_', true) . '.' . $izin[$tipe];
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $folder . $namaFile)) {
                    $fotoBaru = 'uploads/profil/' . $namaFile;
                    $fileBaru = $folder . $namaFile;
                } else {
                    $errors['foto'] = 'Foto gagal disimpan.';
                }
            }
        }
    }

    if (!$errors) {
        if ($info && simpan_profil_db($conn, $info, $userId, $namaBaru, $fotoBaru)) {
            // hapus foto lama yang ada di uploads/profil
            if ($fileBaru && strpos($foto, 'uploads/profil/') === 0) {
                $lama = __DIR__ . '/../../uploads/profil/' . basename($foto);
                if (is_file($lama)) @unlink($lama);
            }
            // samakan session supaya langsung terbaca di halaman lain
            $_SESSION['nama'] = $namaBaru;
            $_SESSION['foto'] = $fotoBaru;
            $_SESSION['foto_profil'] = $fotoBaru;
            if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
                $_SESSION['user']['nama'] = $namaBaru;
                $_SESSION['user']['foto_profil'] = $fotoBaru;
            }
            $_SESSION['flash_profil'] = 1;
            header('Location: edit-profil.php');
            exit;
        }
        if ($fileBaru && is_file($fileBaru)) @unlink($fileBaru);
        $gagal = 'Perubahan gagal disimpan karena terjadi kesalahan pada server. Silakan coba lagi.';
    } else {
        if ($fileBaru && is_file($fileBaru)) @unlink($fileBaru);
        $gagal = 'Perubahan gagal disimpan. Periksa kembali isian Anda.';
    }

    $nama = $namaBaru;
}

$fotoSrc = '';
if ($foto !== '') {
    if (preg_match('#^https?://#i', $foto)) {
        $fotoSrc = $foto;
    } elseif (is_file(__DIR__ . '/../../' . $foto)) {
        $fotoSrc = '../../' . ltrim($foto, '/');
    }
}
$srcTampil = $fotoSrc ?: $gravatar;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Profil | Explore Majaku</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Kaushan+Script&family=Noto+Serif:wght@400;500;600&family=Poppins:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/edit-profil.css?v=20261030">
  <link rel="stylesheet" href="../CSS/theme.css?v=20261019-2">
</head>
<body class="halaman-edit-profil">

  <svg width="0" height="0" style="position:absolute" aria-hidden="true">
    <defs>
      <clipPath id="klip-avatar"><circle cx="110" cy="110" r="110"/></clipPath>
      <symbol id="ikon-profil" viewBox="0 0 220 220">
        <circle cx="110" cy="110" r="102.5" fill="none" stroke="#166d5c" stroke-width="15"/>
        <circle cx="110" cy="83" r="31" fill="#166d5c"/>
        <path d="M38 211c2-39 23-66 57-75 9 9 19 13 30 13s21-4 30-13c34 9 55 36 57 75z" fill="#166d5c"/>
      </symbol>
    </defs>
  </svg>

  <header class="ep-navbar">
    <div class="ep-navbar__inner">
      <a class="ep-logo" href="../../index.php">Explore Majaku</a>

      <nav class="ep-menu" aria-label="Menu utama">
        <a class="ep-menu__beranda" href="../../index.php">Beranda</a>
        <a class="ep-menu__kategori" href="#" id="kategori-toggle"
           aria-haspopup="true" aria-expanded="false" aria-controls="kategori-menu">kategori</a>
        <a class="ep-menu__tentang" href="tentang.php">Tentang</a>
      </nav>

      <a class="ep-ikon ep-ikon--cari" href="hasil_pencarian.php" aria-label="Cari">
        <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round">
          <circle cx="16" cy="16" r="12"/>
          <line x1="25" y1="25" x2="37" y2="37"/>
        </svg>
      </a>
      <a class="ep-ikon ep-ikon--profil" href="profil.php" aria-label="Profil">
        <svg viewBox="0 0 220 220"><use href="#ikon-profil"/></svg>
      </a>
    </div>

    <ul class="kat-menu" id="kategori-menu">
      <li><a class="kat-item" href="kategori.php?jenis=alam">Wisata Alam &amp; pegunungan</a></li>
      <li><a class="kat-item" href="kategori.php?jenis=air-curug">Wisata Air &amp; Curug</a></li>
      <li><a class="kat-item" href="kategori.php?jenis=buatan-kuliner">Wisata Buatan &amp; Kuliner</a></li>
      <li><a class="kat-item" href="kategori.php?jenis=sejarah-budaya">Wisata Sejarah &amp; Budaya</a></li>
    </ul>
  </header>

  <form action="" method="post" enctype="multipart/form-data" novalidate>
    <main class="ep-stage">
      <!-- Hanya ikon panah yang bisa diklik -->
      <div class="ep-back">
        <a class="ep-back__ikon" href="profil.php" aria-label="Kembali ke profil">
          <svg viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="26" y1="14" x2="2" y2="14"/>
            <polyline points="13,3 2,14 13,25"/>
          </svg>
        </a>
        <span class="ep-back__teks">Back</span>
      </div>

      <?php if ($sukses): ?>
        <div class="ep-alert ep-alert--sukses" role="status">Perubahan profil berhasil disimpan.</div>
      <?php elseif ($gagal !== ''): ?>
        <div class="ep-alert ep-alert--gagal" role="alert"><?= e($gagal) ?></div>
      <?php endif; ?>

      <input class="ep-foto__input" type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp">
      <button type="button" class="ep-foto__lingkaran" id="btn-foto" aria-label="Ubah foto profil">
        <svg viewBox="0 0 220 220" id="avatar-default" aria-hidden="true"<?= $srcTampil ? ' style="display:none"' : '' ?>>
          <circle cx="110" cy="110" r="110" fill="#d9d9d9"/>
          <use href="#ikon-profil" style="color:#166d5c"/>
        </svg>
        <img id="preview-foto" alt="Foto profil"
             src="<?= e($srcTampil) ?>"<?= $fotoSrc ? '' : ' hidden' ?>>
      </button>
      <label class="ep-foto__label" for="foto">Ubah Foto Profil</label>
      <?php if (isset($errors['foto'])): ?>
        <p class="ep-error ep-error--foto"><?= e($errors['foto']) ?></p>
      <?php endif; ?>

      <label class="ep-label ep-label--nama" for="nama_lengkap">Nama Lengkap</label>
      <input class="ep-input" type="text" id="nama_lengkap" name="nama_lengkap"
             value="<?= e($nama) ?>" placeholder="Nama User" maxlength="100" autocomplete="name" required>
      <?php if (isset($errors['nama'])): ?>
        <p class="ep-error ep-error--nama"><?= e($errors['nama']) ?></p>
      <?php endif; ?>

      <span class="ep-label ep-label--email">Email</span>
      <p class="ep-email"><?= e($email) ?></p>
      <p class="ep-catatan">Email tidak dapat diubah karena digunakan sebagai identitas akun.</p>

      <button type="submit" class="ep-btn ep-btn--utama">Simpan Perubahan</button>
      <a href="profil.php" class="ep-btn ep-btn--batal">Batal</a>
    </main>
  </form>

  <script>
    // Dropdown kategori
    (function () {
      var toggle = document.getElementById('kategori-toggle');
      var menu = document.getElementById('kategori-menu');
      var header = document.querySelector('.ep-navbar');

      function buka() {
        menu.classList.add('show');
        var r = toggle.getBoundingClientRect();
        menu.style.top = header.getBoundingClientRect().bottom + 'px';
        menu.style.left = (r.left + r.width / 2 - menu.offsetWidth / 2) + 'px';
        toggle.classList.add('aktif');
        toggle.setAttribute('aria-expanded', 'true');
      }
      function tutup() {
        menu.classList.remove('show');
        toggle.classList.remove('aktif');
        toggle.setAttribute('aria-expanded', 'false');
      }

      toggle.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        menu.classList.contains('show') ? tutup() : buka();
      });
      document.addEventListener('click', function (e) {
        if (!menu.contains(e.target)) tutup();
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') tutup();
      });
      window.addEventListener('resize', tutup);
    })();

    // Foto profil
    const input = document.getElementById('foto');
    const preview = document.getElementById('preview-foto');
    const avatarDefault = document.getElementById('avatar-default');

    function tampilkanFoto() {
      preview.hidden = false;
      avatarDefault.style.display = 'none';
    }

    if (preview.getAttribute('src')) {
      if (preview.complete && preview.naturalWidth > 0) {
        tampilkanFoto();
      } else {
        preview.addEventListener('load', tampilkanFoto, { once: true });
        preview.addEventListener('error', () => {
          preview.hidden = true;
          avatarDefault.style.display = '';
        }, { once: true });
      }
    }

    document.getElementById('btn-foto').addEventListener('click', () => input.click());
    input.addEventListener('change', () => {
      const file = input.files[0];
      if (!file) return;
      preview.src = URL.createObjectURL(file);
      tampilkanFoto();
    });
  </script>
</body>
</html>