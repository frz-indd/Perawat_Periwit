<?php
session_start();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

const MAKS_FOTO = 10 * 1024 * 1024;

$teksFoto = [
    'ukuran'        => 'Batas maksimal ukuran file adalah 10 MB',
    'format'        => 'Format file ini tidak mendukung, format harus berbentuk JPG, JPEG, PNG',
    'format_ukuran' => 'Format file ini tidak mendukung, format harus berbentuk JPG, JPEG, PNG dan batas maksimal ukuran file adalah 10 MB',
    'unggah'        => 'Foto gagal diunggah, silakan coba lagi',
];

function e($s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function post_teks(string $k): string {
    $v = $_POST[$k] ?? '';
    return is_string($v) ? trim($v) : '';
}

function srcGambar(string $path): string {
    $path = trim($path);
    if ($path === '') return '';
    if (preg_match('#^https?://#i', $path) === 1) return $path;
    return is_file(__DIR__ . '/../../' . ltrim($path, '/')) ? '../../' . ltrim($path, '/') : '';
}

require_once __DIR__ . '/../../Backend/database/koneksi.php';

$userId = filter_var($_SESSION['id_user'] ?? null, FILTER_VALIDATE_INT);
if ($userId === false || $userId <= 0) {
    header('Location: ../auth/Login.php');
    exit;
}
$userId = (int) $userId;

$basePath = rtrim(str_replace('\\', '/', dirname(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')))), '/');
if ($basePath === '.' || $basePath === '/') $basePath = '';
$apiProfil = $basePath . '/users/me';

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

function nilai_pertama(array $row, array $info, array $kandidat): string {
    foreach ($kandidat as $k) {
        if (isset($info['kolom'][$k])) {
            $v = trim((string) ($row[$info['kolom'][$k]] ?? ''));
            if ($v !== '') return $v;
        }
    }
    return '';
}

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

function validasi_foto(array $f): array {
    $mimeIzin = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    $extIzin = ['jpg', 'jpeg', 'png'];

    if (!is_string($f['name'] ?? null) || !is_string($f['tmp_name'] ?? null)) {
        return ['jenis' => 'unggah', 'ext' => ''];
    }

    $kode = (int) ($f['error'] ?? UPLOAD_ERR_NO_FILE);
    $ukuranSalah = false;
    $formatSalah = false;
    $ext = '';

    if ($kode === UPLOAD_ERR_INI_SIZE || $kode === UPLOAD_ERR_FORM_SIZE) {
        $extAsli = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        $ukuranSalah = true;
        $formatSalah = !in_array($extAsli, $extIzin, true);
    } elseif ($kode !== UPLOAD_ERR_OK) {
        return ['jenis' => 'unggah', 'ext' => ''];
    } else {
        $tmp = $f['tmp_name'];
        $ukuranSalah = (int) ($f['size'] ?? 0) > MAKS_FOTO;
        $tipe = is_uploaded_file($tmp) ? (string) @mime_content_type($tmp) : '';
        if (isset($mimeIzin[$tipe]) && @getimagesize($tmp) !== false) {
            $ext = $mimeIzin[$tipe];
        } else {
            $formatSalah = true;
        }
    }

    $jenis = '';
    if ($formatSalah && $ukuranSalah) {
        $jenis = 'format_ukuran';
    } elseif ($formatSalah) {
        $jenis = 'format';
    } elseif ($ukuranSalah) {
        $jenis = 'ukuran';
    }

    return ['jenis' => $jenis, 'ext' => $ext];
}

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

$email = trim((string) ($_SESSION['email'] ?? ($_SESSION['user']['email'] ?? ($_SESSION['no_hp'] ?? ($_SESSION['telepon'] ?? '')))));
if ($email === '' && $info) {
    $email = nilai_pertama($row, $info, ['email', 'no_hp', 'telepon']);
}

$nama = $info ? nilai_pertama($row, $info, ['nama', 'nama_lengkap', 'nama_user', 'username']) : '';
if ($nama === '') $nama = trim((string) ($_SESSION['nama'] ?? ($_SESSION['user']['nama'] ?? '')));
if ($nama === '' || strcasecmp($nama, 'Nama User') === 0) {
    $nama = filter_var($email, FILTER_VALIDATE_EMAIL) ? ucfirst((string) strstr($email, '@', true)) : '';
}

$foto = $info ? nilai_pertama($row, $info, ['foto_profil', 'foto', 'avatar']) : '';
if ($foto === '') $foto = trim((string) ($_SESSION['foto_profil'] ?? ($_SESSION['foto'] ?? ($_SESSION['user']['foto_profil'] ?? ''))));

$fotoEmail = trim((string) (
    $_SESSION['foto_google']
    ?? ($_SESSION['picture']
    ?? ($_SESSION['avatar_url']
    ?? ($_SESSION['user']['foto_google']
    ?? ($_SESSION['user']['picture'] ?? ''))))
));

$gravatar = filter_var($email, FILTER_VALIDATE_EMAIL)
    ? 'https://www.gravatar.com/avatar/' . hash('sha256', strtolower($email)) . '?s=440&d=404'
    : '';

$srcTampil = srcGambar($foto);
if ($srcTampil === '' && preg_match('#^https://#i', $fotoEmail) === 1) {
    $srcTampil = $fotoEmail;
}
if ($srcTampil === '') {
    $srcTampil = $gravatar;
}

$modal = null;

$flashOk = $_SESSION['flash_profil'] ?? null;
$flashGagal = $_SESSION['flash_gagal'] ?? null;
unset($_SESSION['flash_profil'], $_SESSION['flash_gagal']);

if (!empty($flashOk)) {
    $fotoSaja = $flashOk === 'foto';
    $modal = [
        'tipe'   => 'sukses',
        'judul'  => $fotoSaja ? 'Edit Foto Profil Berhasil!' : 'Edit Profil Berhasil!',
        'isi'    => $fotoSaja ? 'Perubahan foto profil berhasil disimpan' : 'Perubahan profil berhasil disimpan',
        'detail' => '',
        'lebar'  => false,
        'aksi'   => 'OK',
        'href'   => 'profil.php',
    ];
} elseif (is_array($flashGagal)) {
    $jenis = (string) ($flashGagal['jenis'] ?? 'umum');
    if (isset($teksFoto[$jenis])) {
        $modal = [
            'tipe'   => 'gagal',
            'judul'  => 'Edit Foto Profil Gagal!',
            'isi'    => $teksFoto[$jenis],
            'detail' => '',
            'lebar'  => in_array($jenis, ['format', 'format_ukuran'], true),
            'aksi'   => 'Coba Lagi',
            'href'   => 'edit-profil.php',
        ];
    } else {
        $modal = [
            'tipe'   => 'gagal',
            'judul'  => 'Edit Profil Gagal!',
            'isi'    => 'Perubahan profil gagal disimpan',
            'detail' => (string) ($flashGagal['detail'] ?? ''),
            'lebar'  => false,
            'aksi'   => 'Coba Lagi',
            'href'   => 'edit-profil.php',
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors     = [];
    $jenisGagal = '';
    $hasilFoto  = null;
    $fotoBaru   = $foto;
    $fileBaru   = null;

    $melebihiPost = empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
    $adaFoto = !$melebihiPost
        && isset($_FILES['foto'])
        && (int) ($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if ($melebihiPost) {
        $jenisGagal = 'ukuran';
    } elseif ($adaFoto) {
        $hasilFoto = validasi_foto($_FILES['foto']);
        $jenisGagal = $hasilFoto['jenis'];
    }

    $namaBaru = post_teks('nama_lengkap');
    $namaAwal = post_teks('nama_awal');
    if ($namaAwal === '') $namaAwal = $nama;

    if ($namaBaru === '') {
        $errors['nama'] = 'Nama lengkap wajib diisi.';
    } elseif (mb_strlen($namaBaru) > 100) {
        $errors['nama'] = 'Nama lengkap maksimal 100 karakter.';
    }

    if (post_teks('api_status') === 'gagal') {
        $pesanApi = post_teks('api_pesan');
        $errors['api'] = $pesanApi !== '' ? mb_substr($pesanApi, 0, 150) : 'Profil gagal diperbarui.';
    }

    if ($jenisGagal === '' && !$errors && $hasilFoto !== null) {
        $folder = __DIR__ . '/../../uploads/profil/';
        if (!is_dir($folder)) @mkdir($folder, 0755, true);
        $namaFile = uniqid('profil_', true) . '.' . $hasilFoto['ext'];
        if (is_dir($folder) && move_uploaded_file($_FILES['foto']['tmp_name'], $folder . $namaFile)) {
            $fotoBaru = 'uploads/profil/' . $namaFile;
            $fileBaru = $folder . $namaFile;
        } else {
            $jenisGagal = 'unggah';
        }
    }

    $berhasil = false;
    if ($jenisGagal === '' && !$errors && $info) {
        $berhasil = simpan_profil_db($conn, $info, $userId, $namaBaru, $fotoBaru);
    }

    if ($berhasil) {
        if ($fileBaru && strpos($foto, 'uploads/profil/') === 0) {
            $lama = __DIR__ . '/../../uploads/profil/' . basename($foto);
            if (is_file($lama)) @unlink($lama);
        }
        $_SESSION['nama'] = $namaBaru;
        $_SESSION['foto'] = $fotoBaru;
        $_SESSION['foto_profil'] = $fotoBaru;
        if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
            $_SESSION['user']['nama'] = $namaBaru;
            $_SESSION['user']['foto_profil'] = $fotoBaru;
        }
        $_SESSION['flash_profil'] = ($fileBaru !== null && $namaBaru === $namaAwal) ? 'foto' : 'profil';
        header('Location: edit-profil.php');
        exit;
    }

    if ($fileBaru && is_file($fileBaru)) @unlink($fileBaru);
    $_SESSION['flash_gagal'] = [
        'jenis'  => $jenisGagal !== '' ? $jenisGagal : 'umum',
        'detail' => ($jenisGagal === '' && $errors) ? (string) reset($errors) : '',
    ];
    header('Location: edit-profil.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Profil | Explore Majaku</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Kaushan+Script&family=Noto+Serif:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/edit-profil.css?v=20261060">
  <link rel="stylesheet" href="../CSS/theme.css?v=20261019-2">
</head>
<body class="halaman-edit-profil">

  <svg width="0" height="0" style="position:absolute" aria-hidden="true">
    <defs>
      <clipPath id="klip-dalam"><circle cx="110" cy="110" r="103"/></clipPath>
      <symbol id="ikon-profil" viewBox="0 0 220 220">
        <circle cx="110" cy="110" r="102" fill="none" stroke="#166d5c" stroke-width="15"/>
        <circle cx="110" cy="91" r="26" fill="none" stroke="#166d5c" stroke-width="13"/>
        <path d="M30 222C30 176 56 146 92 140C99 150 121 150 128 140C164 146 190 176 190 222" fill="none" stroke="#166d5c" stroke-width="13" stroke-linecap="round" stroke-linejoin="round" clip-path="url(#klip-dalam)"/>
      </symbol>
      <symbol id="i-pensil" viewBox="0 0 45 44">
        <path d="M19 9.7H7.4A5.7 5.7 0 0 0 1.7 15.4V36.3A5.7 5.7 0 0 0 7.4 42H28.8A5.7 5.7 0 0 0 34.5 36.3V25" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
        <g transform="translate(33.2 11.3) rotate(45)" fill="currentColor">
          <path d="M-5.3 0H5.3V26C5.3 30 2.4 34.6 0.9 37Q0 38.3-0.9 37C-2.4 34.6-5.3 30-5.3 26Z"/>
          <path d="M-5.3-3.5V-8.1A5.3 5.3 0 0 1 5.3-8.1V-3.5Z"/>
        </g>
      </symbol>
    </defs>
  </svg>

  <header class="ep-navbar">
    <div class="ep-navbar__inner">
      <a class="ep-logo" href="../../index.php">Explore Majaku</a>

      <nav class="ep-menu" aria-label="Menu utama">
        <a class="ep-menu__beranda" href="../../index.php">Beranda</a>
        <a class="ep-menu__kategori" href="#kategori" id="kategori-toggle"
           role="button" aria-haspopup="true" aria-expanded="false" aria-controls="kategori-menu">Kategori</a>
        <a class="ep-menu__tentang" href="tentang.php">Tentang</a>
      </nav>

      <div class="ep-aksi">
        <a class="ep-ikon ep-ikon--cari" href="hasil_pencarian.php" aria-label="Cari">
          <svg viewBox="0 0 36 36" fill="none" stroke="currentColor" stroke-width="3.6" stroke-linecap="round" aria-hidden="true">
            <circle cx="13.5" cy="13.5" r="11.7"/>
            <line x1="22.3" y1="22.3" x2="33.2" y2="33.2"/>
          </svg>
        </a>

        <a class="ep-ikon ep-ikon--profil" href="profil.php" aria-label="Profil">
          <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="20" cy="20" r="18.2"/>
            <circle cx="20" cy="16.2" r="5.8"/>
            <path d="M9 33.5C9.7 29.2 12.2 26 15.2 25.2C17 26.6 23 26.6 24.8 25.2C27.8 26 30.3 29.2 31 33.5"/>
          </svg>
        </a>
      </div>
    </div>
  </header>

  <ul class="dd-kategori" id="kategori-menu" role="menu" aria-label="Kategori wisata">
    <li role="none"><a class="dd-kategori__item" role="menuitem" href="kategori.php?jenis=alam">Wisata Alam &amp; pegunungan</a></li>
    <li role="none"><a class="dd-kategori__item" role="menuitem" href="kategori.php?jenis=air-curug">Wisata Air &amp; Curug</a></li>
    <li role="none"><a class="dd-kategori__item" role="menuitem" href="kategori.php?jenis=buatan-kuliner">Wisata Buatan &amp; Kuliner</a></li>
    <li role="none"><a class="dd-kategori__item" role="menuitem" href="kategori.php?jenis=sejarah-budaya">Wisata Sejarah &amp; Budaya</a></li>
  </ul>

  <form id="form-profil" action="" method="post" enctype="multipart/form-data" novalidate
        data-api="<?= e($apiProfil) ?>"
        data-csrf="<?= e($_SESSION['csrf']) ?>"
        data-email="<?= e($email) ?>">
    <input type="hidden" name="api_status" id="api-status" value="">
    <input type="hidden" name="api_pesan" id="api-pesan" value="">
    <input type="hidden" name="nama_awal" id="nama-awal" value="<?= e($nama) ?>">

    <main class="ep-stage">

      <input class="ep-foto__input" type="file" id="foto" name="foto" accept=".jpg,.jpeg,.png,image/jpeg,image/png">

      <div class="ep-foto__lingkaran" id="foto-tampil">
        <svg viewBox="0 0 220 220" id="avatar-default" aria-hidden="true">
          <circle cx="110" cy="110" r="110" fill="#d9d9d9"/>
          <use href="#ikon-profil"/>
        </svg>
        <img id="preview-foto" alt="Foto profil" referrerpolicy="no-referrer" hidden<?= $srcTampil !== '' ? ' src="' . e($srcTampil) . '"' : '' ?>>
      </div>

      <div class="ep-foto__label">
        <span class="ep-foto__grup">
          <span class="ep-foto__teks">Ubah Foto Profil</span>
          <button type="button" class="ep-foto__edit" id="btn-foto" aria-label="Ubah foto profil">
            <svg viewBox="0 0 45 44" aria-hidden="true"><use href="#i-pensil"/></svg>
          </button>
        </span>
      </div>

      <label class="ep-label ep-label--nama" for="nama_lengkap">Nama Lengkap</label>
      <input class="ep-input" type="text" id="nama_lengkap" name="nama_lengkap"
             value="<?= e($nama) ?>" placeholder="Nama User" maxlength="100" autocomplete="name" required>

      <span class="ep-label ep-label--email">Email</span>
      <p class="ep-email" id="email-tampil"><?= e($email) ?></p>
      <p class="ep-catatan">Email tidak dapat diubah karena digunakan sebagai identitas akun.</p>

      <button type="submit" class="ep-btn ep-btn--utama">Simpan Perubahan</button>
      <a href="profil.php" class="ep-btn ep-btn--batal">Batal</a>
    </main>
  </form>

  <div class="ep-modal" id="modal-validasi" role="alertdialog" aria-modal="true"
       aria-labelledby="validasi-judul" aria-describedby="validasi-isi"
       data-pesan="<?= e(json_encode($teksFoto, JSON_UNESCAPED_UNICODE)) ?>" hidden>
    <div class="ep-modal__kotak ep-modal__kotak--gagal" id="validasi-kotak">
      <div class="ep-modal__ikon ep-modal__ikon--gagal">
        <svg viewBox="0 0 176 176" aria-hidden="true">
          <path d="M47 47l82 82M129 47l-82 82" fill="none" stroke="currentColor" stroke-width="12" stroke-linecap="round"/>
        </svg>
      </div>
      <h2 class="ep-modal__judul ep-modal__judul--gagal" id="validasi-judul">Edit Foto Profil Gagal!</h2>
      <p class="ep-modal__isi ep-modal__isi--gagal" id="validasi-isi"></p>
      <button type="button" class="ep-modal__ok" id="validasi-tutup">Coba Lagi</button>
    </div>
  </div>

  <?php if ($modal !== null): ?>
    <?php $gagalModal = $modal['tipe'] === 'gagal'; ?>
    <div class="ep-modal" role="<?= $gagalModal ? 'alertdialog' : 'dialog' ?>" aria-modal="true" aria-labelledby="modal-judul" aria-describedby="modal-isi">
      <div class="ep-modal__kotak<?= $gagalModal ? ' ep-modal__kotak--gagal' : '' ?><?= $modal['lebar'] ? ' ep-modal__kotak--lebar' : '' ?>">
        <div class="ep-modal__ikon<?= $gagalModal ? ' ep-modal__ikon--gagal' : '' ?>">
          <svg viewBox="0 0 176 176" aria-hidden="true">
            <?php if ($gagalModal): ?>
              <path d="M47 47l82 82M129 47l-82 82" fill="none" stroke="currentColor" stroke-width="12" stroke-linecap="round"/>
            <?php else: ?>
              <path d="M41 86l32 35 59-68" fill="none" stroke="currentColor" stroke-width="14" stroke-linecap="round" stroke-linejoin="round"/>
            <?php endif; ?>
          </svg>
        </div>
        <h2 class="ep-modal__judul<?= $gagalModal ? ' ep-modal__judul--gagal' : '' ?>" id="modal-judul"><?= e($modal['judul']) ?></h2>
        <p class="ep-modal__isi<?= $gagalModal ? ' ep-modal__isi--gagal' : '' ?><?= $modal['lebar'] ? ' ep-modal__isi--lebar' : '' ?>" id="modal-isi"><?= e($modal['isi']) ?></p>
        <?php if ($modal['detail'] !== ''): ?>
          <p class="ep-modal__detail"><?= e($modal['detail']) ?></p>
        <?php endif; ?>
        <a class="ep-modal__ok" href="<?= e($modal['href']) ?>" id="modal-aksi"><?= e($modal['aksi']) ?></a>
      </div>
    </div>
  <?php endif; ?>

  <script>
    (function () {
      var toggle = document.getElementById('kategori-toggle');
      var menu = document.getElementById('kategori-menu');
      var header = document.querySelector('.ep-navbar');
      if (!toggle || !menu) return;

      document.body.appendChild(menu);

      function terbuka() {
        return menu.classList.contains('show');
      }

      function posisi() {
        var r = toggle.getBoundingClientRect();
        var dasar = header ? header.getBoundingClientRect().bottom : r.bottom;
        var lebar = menu.offsetWidth;
        var kiri;
        if (window.innerWidth <= 900) {
          kiri = (window.innerWidth - lebar) / 2;
        } else {
          kiri = r.left + r.width * 0.15;
        }
        kiri = Math.max(8, Math.min(kiri, window.innerWidth - lebar - 8));
        menu.style.top = Math.round(dasar + 6) + 'px';
        menu.style.left = Math.round(kiri) + 'px';
      }

      function buka() {
        menu.classList.add('show');
        menu.style.setProperty('display', 'block', 'important');
        menu.style.setProperty('visibility', 'visible', 'important');
        menu.style.setProperty('opacity', '1', 'important');
        posisi();
        toggle.classList.add('aktif');
        toggle.setAttribute('aria-expanded', 'true');
      }

      function tutup() {
        menu.classList.remove('show');
        menu.style.removeProperty('display');
        menu.style.removeProperty('visibility');
        menu.style.removeProperty('opacity');
        toggle.classList.remove('aktif');
        toggle.setAttribute('aria-expanded', 'false');
      }

      toggle.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (terbuka()) { tutup(); } else { buka(); }
      });

      document.addEventListener('click', function (e) {
        if (terbuka() && !menu.contains(e.target) && !toggle.contains(e.target)) tutup();
      });

      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && terbuka()) {
          tutup();
          toggle.focus();
        }
      });

      window.addEventListener('resize', function () {
        if (terbuka()) posisi();
      });
    })();

    (function () {
      var form          = document.getElementById('form-profil');
      var input         = document.getElementById('foto');
      var preview       = document.getElementById('preview-foto');
      var avatarDefault = document.getElementById('avatar-default');
      var inpNama       = document.getElementById('nama_lengkap');
      var namaAwal      = document.getElementById('nama-awal');
      var emailEl       = document.getElementById('email-tampil');
      var fStatus       = document.getElementById('api-status');
      var fPesan        = document.getElementById('api-pesan');
      var modal         = document.getElementById('modal-validasi');
      var kotak         = document.getElementById('validasi-kotak');
      var isiEl         = document.getElementById('validasi-isi');
      var tutupBtn      = document.getElementById('validasi-tutup');
      var apiUrl        = form.getAttribute('data-api');
      var csrf          = form.getAttribute('data-csrf') || '';
      var polaEmail     = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      var maks          = 10 * 1024 * 1024;
      var srcAwal       = preview.getAttribute('src') || '';
      var urlLokal      = null;
      var sedangKirim   = false;
      var pesan         = {};

      try {
        pesan = JSON.parse(modal.getAttribute('data-pesan') || '{}');
      } catch (err) {
        pesan = {};
      }

      function tampilkanFoto() {
        preview.hidden = false;
        avatarDefault.style.display = 'none';
      }

      function tampilkanDefault() {
        preview.hidden = true;
        avatarDefault.style.display = '';
      }

      function pulihkan() {
        if (!urlLokal) return;
        URL.revokeObjectURL(urlLokal);
        urlLokal = null;
        if (srcAwal) {
          preview.src = srcAwal;
        } else {
          preview.removeAttribute('src');
          tampilkanDefault();
        }
      }

      function cekFoto(file) {
        var namaFile    = (file.name || '').toLowerCase();
        var ekstensiOk  = /\.(jpe?g|png)$/.test(namaFile);
        var tipeOk      = file.type === '' || file.type === 'image/jpeg' || file.type === 'image/png';
        var formatSalah = !(ekstensiOk && tipeOk);
        var ukuranSalah = file.size > maks;
        if (formatSalah && ukuranSalah) return 'format_ukuran';
        if (formatSalah) return 'format';
        if (ukuranSalah) return 'ukuran';
        return '';
      }

      function tampilGagal(jenis) {
        var lebar = jenis === 'format' || jenis === 'format_ukuran';
        isiEl.textContent = pesan[jenis] || '';
        isiEl.classList.toggle('ep-modal__isi--lebar', lebar);
        kotak.classList.toggle('ep-modal__kotak--lebar', lebar);
        modal.hidden = false;
        tutupBtn.focus();
      }

      function tutupGagal(ulang) {
        modal.hidden = true;
        if (ulang) bukaPemilih();
      }

      preview.addEventListener('load', tampilkanFoto);
      preview.addEventListener('error', tampilkanDefault);
      if (srcAwal && preview.complete) {
        if (preview.naturalWidth > 0) { tampilkanFoto(); } else { tampilkanDefault(); }
      }

      tutupBtn.addEventListener('click', function () { tutupGagal(true); });
      modal.addEventListener('click', function (e) { if (e.target === modal) tutupGagal(false); });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) tutupGagal(false);
      });

      function terimaFile(file) {
        if (!file) {
          pulihkan();
          return;
        }
        var jenis = cekFoto(file);
        if (jenis) {
          input.value = '';
          pulihkan();
          tampilGagal(jenis);
          return;
        }
        if (urlLokal) URL.revokeObjectURL(urlLokal);
        urlLokal = URL.createObjectURL(file);
        preview.src = urlLokal;
        tampilkanFoto();
      }

      function bukaPemilih() {
        if (typeof window.showOpenFilePicker !== 'function' || !window.isSecureContext) {
          input.click();
          return;
        }
        window.showOpenFilePicker({
          multiple: false,
          excludeAcceptAllOption: true,
          types: [{
            description: 'Foto JPG, JPEG, PNG',
            accept: { 'image/jpeg': ['.jpg', '.jpeg'], 'image/png': ['.png'] }
          }]
        })
          .then(function (daftar) { return daftar[0].getFile(); })
          .then(function (file) {
            try {
              var dt = new DataTransfer();
              dt.items.add(file);
              input.files = dt.files;
            } catch (err) {
              input.click();
              return;
            }
            terimaFile(file);
          })
          .catch(function (err) {
            if (err && err.name === 'AbortError') return;
            input.click();
          });
      }

      document.getElementById('btn-foto').addEventListener('click', bukaPemilih);

      input.addEventListener('change', function () {
        terimaFile(input.files[0]);
      });

      inpNama.addEventListener('input', function () { inpNama.setAttribute('data-diubah', '1'); });

      fetch(apiUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (j) {
          if (!j || j.status !== 'success' || !j.data) return;
          if (j.data.nama && !inpNama.getAttribute('data-diubah')) {
            inpNama.value = j.data.nama;
            namaAwal.value = j.data.nama;
          }
          if (j.data.email) {
            emailEl.textContent = j.data.email;
            form.setAttribute('data-email', j.data.email);
          }
        })
        .catch(function () {});

      form.addEventListener('submit', function (e) {
        if (sedangKirim) {
          e.preventDefault();
          return;
        }

        var file = input.files[0];
        if (file) {
          var jenis = cekFoto(file);
          if (jenis) {
            e.preventDefault();
            input.value = '';
            pulihkan();
            tampilGagal(jenis);
            return;
          }
        }

        var nama  = inpNama.value.trim();
        var email = form.getAttribute('data-email') || '';

        if (nama === '' || nama.length > 100 || !polaEmail.test(email)) return;

        e.preventDefault();
        sedangKirim = true;

        function lanjut(status, pesanApi) {
          fStatus.value = status;
          fPesan.value = pesanApi || '';
          form.submit();
        }

        fetch(apiUrl, {
          method: 'PUT',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-Token': csrf
          },
          body: JSON.stringify({ nama: nama, email: email })
        })
          .then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (j) {
              if (r.ok && j.status === 'success') {
                lanjut('ok', '');
              } else {
                lanjut('gagal', (j && j.message) || 'Profil gagal diperbarui.');
              }
            });
          })
          .catch(function () {
            lanjut('gagal', 'Tidak dapat menghubungi server.');
          });
      });
    })();

    (function () {
      var aksi = document.getElementById('modal-aksi');
      if (aksi) aksi.focus();
    })();
  </script>
</body>
</html>