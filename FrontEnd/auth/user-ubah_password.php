<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Referrer-Policy: no-referrer');
$p = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$i = strpos($p, '/FrontEnd/');
$base = $i !== false ? substr($p, 0, $i) : rtrim(dirname(dirname($p)), '/.');
$verify = $base . '/api/user/reset-password/verify';
$reset = $base . '/api/user/reset-password';
$login = 'Login.php';
$lupa = 'user-lupa_password.php';
$e = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

function mata(string $id): void
{
    echo '<button type="button" class="ub-mata" aria-label="Tampilkan password" aria-pressed="false" data-target="' . $id . '">'
       . '<svg viewBox="0 0 22 14" aria-hidden="true">'
       . '<path fill="currentColor" fill-rule="evenodd" d="M0 7C4 2 7.4 0 11 0S18 2 22 7C18 12 14.6 14 11 14S4 12 0 7ZM11 3.4a3.6 3.6 0 1 0 0 7.2a3.6 3.6 0 1 0 0-7.2Z"/>'
       . '<path class="ub-mata__coret" d="M2.5 13.2L19.5 0.8" fill="none" stroke="#fff" stroke-width="4.4" stroke-linecap="round"/>'
       . '<path class="ub-mata__coret" d="M2.5 13.2L19.5 0.8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>'
       . '</svg></button>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <meta name="referrer" content="no-referrer">
  <title>Buat Password Baru Pengguna | Explore Majaku</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&family=Noto+Serif:wght@400;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/admin-ubah_password.css?v=20261120">
</head>
<body data-api-verify="<?= $e($verify) ?>">

  <main class="ub-stage ub-stage--kedaluwarsa" id="state-tidak-valid" hidden>
    <svg class="ub-jam" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="6.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M91.3 35A44 44 0 1 0 65 91.3"/>
      <path d="M50 28V48L63 61"/>
      <path d="M87 60V80"/>
      <circle cx="87" cy="94" r="1.6" fill="currentColor"/>
    </svg>
    <h1 class="ub-kedaluwarsa__judul" id="judul-tidak-valid">Tautan Reset Kadaluarsa</h1>
    <p class="ub-kedaluwarsa__info" id="info-tidak-valid">Maaf, tautan reset password sudah tidak berlaku atau telah melewati batas waktu penggunaan.</p>
    <a href="<?= $e($lupa) ?>" class="ub-btn ub-btn--utama" id="aksi-minta-baru">Minta Tautan Reset Baru</a>
    <a href="<?= $e($login) ?>" class="ub-btn ub-btn--batal ub-btn--kembali">
      <svg viewBox="0 0 28 22" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M27 11H2M11 2L2 11l9 9"/></svg>
      <span>Kembali ke login</span>
    </a>
  </main>

  <form id="form-password" method="post" novalidate autocomplete="off" data-api-url="<?= $e($reset) ?>" data-api-method="POST" hidden>
    <main class="ub-stage">
      <svg class="ub-kunci" viewBox="0 0 95 118" fill="none" stroke="currentColor" stroke-width="9" aria-hidden="true">
        <rect x="4.5" y="48.5" width="86" height="64" rx="7"/>
        <path d="M23 48.5V29.6A24.5 24.5 0 0 1 72 29.6V48.5"/>
        <path d="M47.5 72.5V88" stroke-linecap="round"/>
      </svg>
      <h1 class="ub-judul">
        <span>Buat Password Baru</span>
        <svg class="ub-judul__ikon" viewBox="0 0 45 44" aria-hidden="true">
          <path d="M19 9.7H7.4A5.7 5.7 0 0 0 1.7 15.4V36.3A5.7 5.7 0 0 0 7.4 42H28.8A5.7 5.7 0 0 0 34.5 36.3V25" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
          <g transform="translate(33.2 11.3) rotate(45)" fill="currentColor">
            <path d="M-5.3 0H5.3V26C5.3 30 2.4 34.6 0.9 37Q0 38.3-0.9 37C-2.4 34.6-5.3 30-5.3 26Z"/>
            <path d="M-5.3-3.5V-8.1A5.3 5.3 0 0 1 5.3-8.1V-3.5Z"/>
          </g>
        </svg>
      </h1>

      <div class="ub-grup ub-grup--baru">
        <label class="ub-label" for="password_baru">Password Baru</label>
        <div class="ub-kolom">
          <input class="ub-input" type="password" id="password_baru" placeholder="Masukkan Password Baru"
                 autocomplete="new-password" aria-describedby="error-baru syarat-password">
          <?php mata('password_baru'); ?>
        </div>
        <p class="ub-error" id="error-baru" role="alert" hidden></p>
        <div class="ub-syarat" id="syarat-password">
          <p class="ub-syarat__judul">Syarat Password</p>
          <p class="ub-syarat__isi">Password minimal 8 karakter</p>
        </div>
      </div>

      <div class="ub-grup ub-grup--konfirmasi">
        <label class="ub-label" for="konfirmasi_password">Konfirmasi Password Baru</label>
        <div class="ub-kolom">
          <input class="ub-input" type="password" id="konfirmasi_password" placeholder="Konfirmasi Password Baru"
                 autocomplete="new-password" aria-describedby="error-konfirmasi">
          <?php mata('konfirmasi_password'); ?>
        </div>
        <p class="ub-error" id="error-konfirmasi" role="alert" hidden></p>
      </div>

      <button type="submit" class="ub-btn ub-btn--utama" id="btn-simpan">Simpan Password Baru</button>
      <a href="<?= $e($login) ?>" class="ub-btn ub-btn--batal">Batal</a>
    </main>
  </form>

  <div class="ub-modal" id="modal-sukses" role="dialog" aria-modal="true" aria-labelledby="judul-sukses" hidden>
    <div class="ub-modal__kotak">
      <div class="ub-modal__ikon">
        <svg viewBox="0 0 176 176" aria-hidden="true"><path d="M42 87l32 34 60-68" fill="none" stroke="currentColor" stroke-width="11" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <h2 class="ub-modal__judul" id="judul-sukses">Berhasil Disimpan</h2>
      <p class="ub-modal__isi">Password baru akun pengguna berhasil disimpan, silakan login kembali dengan password baru.</p>
      <a class="ub-modal__ok" href="<?= $e($login) ?>" id="aksi-ok">Login Sekarang</a>
    </div>
  </div>

  <div class="ub-modal" id="modal-gagal" role="alertdialog" aria-modal="true" aria-labelledby="judul-gagal" hidden>
    <div class="ub-modal__kotak">
      <div class="ub-modal__ikon ub-modal__ikon--gagal">
        <svg viewBox="0 0 176 176" aria-hidden="true"><path d="M44 44l88 88M132 44l-88 88" fill="none" stroke="currentColor" stroke-width="10"/></svg>
      </div>
      <h2 class="ub-modal__judul ub-modal__judul--gagal" id="judul-gagal">Gagal Disimpan!</h2>
      <p class="ub-modal__isi" id="teks-gagal-ubah">Password baru gagal disimpan, silakan coba lagi</p>
      <button type="button" class="ub-modal__ok" id="aksi-coba">Coba Lagi</button>
    </div>
  </div>

  <script src="../Js/user-ubah_password.js?v=<?= time() ?>"></script>
</body>
</html>
