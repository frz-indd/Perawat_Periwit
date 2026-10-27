<?php
session_start();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

function e($s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function kirim_json(array $data, int $kode = 200) {
    http_response_code($kode);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function batas_terlampaui(): bool {
    $d = $_SESSION['pw_coba'] ?? null;
    if (!is_array($d) || (time() - (int) ($d['t'] ?? 0)) > 600) {
        return false;
    }
    return (int) ($d['n'] ?? 0) >= 30;
}

function catat_gagal(): void {
    $d = $_SESSION['pw_coba'] ?? null;
    if (!is_array($d) || (time() - (int) ($d['t'] ?? 0)) > 600) {
        $_SESSION['pw_coba'] = ['n' => 1, 't' => time()];
    } else {
        $_SESSION['pw_coba']['n'] = (int) ($d['n'] ?? 0) + 1;
    }
}

$userId = filter_var($_SESSION['id_user'] ?? null, FILTER_VALIDATE_INT);
if ($userId === false || $userId === null || $userId <= 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        kirim_json(['status' => 'gagal', 'errors' => [], 'login' => false], 401);
    }
    header('Location: ../auth/Login.php');
    exit;
}
$userId = (int) $userId;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi  = (string) ($_POST['aksi'] ?? '');
    $token = (string) ($_POST['csrf'] ?? '');

    if (!hash_equals((string) $_SESSION['csrf'], $token)) {
        kirim_json(['status' => 'gagal', 'errors' => []], 403);
    }

    if ($aksi !== 'cek_lama' && $aksi !== 'simpan') {
        kirim_json(['status' => 'gagal', 'errors' => []], 400);
    }

    $lama = (string) ($_POST['password_lama'] ?? '');
    $baru = (string) ($_POST['password_baru'] ?? '');
    $konf = (string) ($_POST['konfirmasi_password'] ?? '');

    if ($aksi === 'simpan') {
        $errors = [];

        if ($lama === '') {
            $errors['lama'] = 'Password lama wajib diisi';
        }

        if ($baru === '') {
            $errors['baru'] = 'Password baru wajib diisi';
        } elseif (mb_strlen($baru) < 8) {
            $errors['baru'] = 'Password baru minimal 8 karakter';
        } elseif (mb_strlen($baru) > 255) {
            $errors['baru'] = 'Password baru maksimal 255 karakter';
        } elseif ($lama !== '' && hash_equals($lama, $baru)) {
            $errors['baru'] = 'Password baru harus berbeda dari password lama';
        }

        if ($konf === '') {
            $errors['konfirmasi'] = 'Konfirmasi password wajib diisi';
        } elseif (!hash_equals($baru, $konf)) {
            $errors['konfirmasi'] = 'Konfirmasi password tidak sesuai';
        }

        if ($errors) {
            kirim_json(['status' => 'gagal', 'errors' => $errors]);
        }
    } elseif ($lama === '') {
        kirim_json(['status' => 'gagal', 'errors' => ['lama' => 'Password lama wajib diisi']]);
    }

    if (batas_terlampaui()) {
        kirim_json([
            'status' => 'gagal',
            'errors' => ['lama' => 'Terlalu banyak percobaan. Coba lagi beberapa menit lagi']
        ], 429);
    }

    $hasil = ['status' => 'gagal', 'errors' => []];

    try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        require_once __DIR__ . '/../../Backend/database/koneksi.php';

        $stmt = $conn->prepare('SELECT Password FROM `user` WHERE Id_user = ? LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->bind_result($hashTersimpan);
        $ada = $stmt->fetch();
        $stmt->close();

        if (!$ada) {
            $hasil['errors']['lama'] = 'Akun tidak ditemukan';
        } elseif (!is_string($hashTersimpan) || $hashTersimpan === '') {
            $hasil['errors']['lama'] = 'Akun ini masuk dengan Google dan belum memiliki password';
        } elseif (strlen($lama) > 1024 || !password_verify($lama, $hashTersimpan)) {
            catat_gagal();
            $hasil['errors']['lama'] = 'Password lama tidak cocok';
        } elseif ($aksi === 'cek_lama') {
            unset($_SESSION['pw_coba']);
            $hasil = ['status' => 'ok'];
        } else {
            $hashBaru = password_hash($baru, PASSWORD_DEFAULT);
            if ($hashBaru === false) {
                throw new RuntimeException('hash');
            }
            $upd = $conn->prepare('UPDATE `user` SET Password = ? WHERE Id_user = ?');
            $upd->bind_param('si', $hashBaru, $userId);
            $upd->execute();
            $upd->close();

            session_regenerate_id(true);
            unset($_SESSION['pw_coba']);
            $hasil = ['status' => 'ok'];
        }
    } catch (Throwable $ex) {
        error_log('Edit password error: ' . $ex->getMessage());
        $hasil = ['status' => 'gagal', 'errors' => []];
    }

    kirim_json($hasil);
}

header('Cache-Control: no-store');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ubah Password | Explore Majaku</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Kaushan+Script&family=Noto+Serif:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/edit-profil.css?v=20261058">
  <link rel="stylesheet" href="../CSS/theme.css?v=20261019-2">
  <link rel="stylesheet" href="../CSS/Edit-Password.css?v=20261029">
</head>
<body class="halaman-edit-profil halaman-edit-password">

  <svg width="0" height="0" style="position:absolute" aria-hidden="true">
    <defs>
      <symbol id="i-pensil" viewBox="0 0 45 44">
        <path d="M19 9.7H7.4A5.7 5.7 0 0 0 1.7 15.4V36.3A5.7 5.7 0 0 0 7.4 42H28.8A5.7 5.7 0 0 0 34.5 36.3V25" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
        <g transform="translate(33.2 11.3) rotate(45)" fill="currentColor">
          <path d="M-5.3 0H5.3V26C5.3 30 2.4 34.6 0.9 37Q0 38.3-0.9 37C-2.4 34.6-5.3 30-5.3 26Z"/>
          <path d="M-5.3-3.5V-8.1A5.3 5.3 0 0 1 5.3-8.1V-3.5Z"/>
        </g>
      </symbol>
      <symbol id="i-mata" viewBox="0 0 22 14">
        <path fill="currentColor" fill-rule="evenodd" d="M0 7C4 2 7.4 0 11 0S18 2 22 7C18 12 14.6 14 11 14S4 12 0 7ZM11 3.4a3.6 3.6 0 1 0 0 7.2a3.6 3.6 0 1 0 0-7.2Z"/>
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

  <form id="form-password" action="" method="post" novalidate autocomplete="off" data-csrf="<?= e($_SESSION['csrf']) ?>">

    <main class="pw-stage">
      <svg class="pw-kunci" viewBox="0 0 95 118" fill="none" stroke="currentColor" stroke-width="9" aria-hidden="true">
        <rect x="4.5" y="48.5" width="86" height="64" rx="7"/>
        <path d="M23 48.5V29.6A24.5 24.5 0 0 1 72 29.6V48.5"/>
        <path d="M47.5 72.5V88" stroke-linecap="round"/>
      </svg>

      <h1 class="pw-judul">
        <span class="pw-judul__teks">Ubah Password</span>
        <svg class="pw-judul__ikon" viewBox="0 0 45 44" aria-hidden="true"><use href="#i-pensil"/></svg>
      </h1>

      <div class="pw-grup" id="grup-lama">
        <label class="pw-label" for="password_lama">Password Lama</label>
        <div class="pw-kolom">
          <input class="pw-input pw-input--bintang" type="text" id="password_lama"
                 placeholder="Masukkan Password Lama" autocomplete="off" autocapitalize="off"
                 autocorrect="off" spellcheck="false" aria-describedby="error-lama">
          <button type="button" class="pw-mata" aria-label="Tampilkan password" aria-pressed="false" data-target="password_lama">
            <svg viewBox="0 0 22 14" aria-hidden="true">
              <use href="#i-mata"/>
              <path class="pw-mata__coret" d="M2.5 13.2L19.5 0.8" fill="none" stroke="#fff" stroke-width="4.4" stroke-linecap="round"/>
              <path class="pw-mata__coret" d="M2.5 13.2L19.5 0.8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>
        <p class="pw-error" id="error-lama" role="alert" hidden></p>
      </div>

      <div class="pw-grup" id="grup-baru">
        <label class="pw-label" for="password_baru">Password Baru</label>
        <div class="pw-kolom">
          <input class="pw-input pw-input--bintang" type="text" id="password_baru"
                 placeholder="Masukkan Password Baru" autocomplete="off" autocapitalize="off"
                 autocorrect="off" spellcheck="false" aria-describedby="error-baru">
          <button type="button" class="pw-mata" aria-label="Tampilkan password" aria-pressed="false" data-target="password_baru">
            <svg viewBox="0 0 22 14" aria-hidden="true">
              <use href="#i-mata"/>
              <path class="pw-mata__coret" d="M2.5 13.2L19.5 0.8" fill="none" stroke="#fff" stroke-width="4.4" stroke-linecap="round"/>
              <path class="pw-mata__coret" d="M2.5 13.2L19.5 0.8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>
        <p class="pw-error" id="error-baru" role="alert" hidden></p>
      </div>

      <div class="pw-grup pw-grup--terakhir" id="grup-konfirmasi">
        <label class="pw-label" for="konfirmasi_password">Konfirmasi Password Baru</label>
        <div class="pw-kolom">
          <input class="pw-input pw-input--bintang" type="text" id="konfirmasi_password"
                 placeholder="Konfirmasi Password Baru" autocomplete="off" autocapitalize="off"
                 autocorrect="off" spellcheck="false" aria-describedby="error-konfirmasi">
          <button type="button" class="pw-mata" aria-label="Tampilkan password" aria-pressed="false" data-target="konfirmasi_password">
            <svg viewBox="0 0 22 14" aria-hidden="true">
              <use href="#i-mata"/>
              <path class="pw-mata__coret" d="M2.5 13.2L19.5 0.8" fill="none" stroke="#fff" stroke-width="4.4" stroke-linecap="round"/>
              <path class="pw-mata__coret" d="M2.5 13.2L19.5 0.8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>
        <p class="pw-error" id="error-konfirmasi" role="alert" hidden></p>
      </div>

      <button type="submit" class="pw-btn pw-btn--utama" id="btn-simpan">Simpan Perubahan</button>
      <a href="profil.php" class="pw-btn pw-btn--batal">Batal</a>
    </main>
  </form>

  <div class="pw-modal" id="modal-sukses" role="dialog" aria-modal="true" aria-labelledby="judul-sukses" aria-describedby="isi-sukses" hidden>
    <div class="pw-modal__kotak">
      <div class="pw-modal__ikon">
        <svg viewBox="0 0 176 176" aria-hidden="true">
          <path d="M42 87l32 34 60-68" fill="none" stroke="currentColor" stroke-width="10" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <h2 class="pw-modal__judul" id="judul-sukses">Edit Profil Berhasil!</h2>
      <p class="pw-modal__isi" id="isi-sukses">Perubahan profil berhasil disimpan</p>
      <a class="pw-modal__ok" href="profil.php" id="aksi-ok">OK</a>
    </div>
  </div>

  <div class="pw-modal" id="modal-gagal" role="alertdialog" aria-modal="true" aria-labelledby="judul-gagal" aria-describedby="isi-gagal" hidden>
    <div class="pw-modal__kotak pw-modal__kotak--gagal">
      <div class="pw-modal__ikon pw-modal__ikon--gagal">
        <svg viewBox="0 0 176 176" aria-hidden="true">
          <path d="M53 53l70 70M123 53l-70 70" fill="none" stroke="currentColor" stroke-width="10" stroke-linecap="round"/>
        </svg>
      </div>
      <h2 class="pw-modal__judul pw-modal__judul--gagal" id="judul-gagal">Edit Profil Gagal!</h2>
      <p class="pw-modal__isi pw-modal__isi--gagal" id="isi-gagal">Perubahan profil gagal disimpan</p>
      <a class="pw-modal__ok" href="Edit-Password.php" id="aksi-coba">Coba Lagi</a>
    </div>
  </div>

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
      var form = document.getElementById('form-password');
      var simpan = document.getElementById('btn-simpan');
      var csrf = form.getAttribute('data-csrf') || '';
      var urlAksi = window.location.pathname + window.location.search;
      var urutan = ['lama', 'baru', 'konfirmasi'];
      var JEDA = 600;

      var pesanEl = {
        lama: document.getElementById('error-lama'),
        baru: document.getElementById('error-baru'),
        konfirmasi: document.getElementById('error-konfirmasi')
      };

      var modalSukses = document.getElementById('modal-sukses');
      var modalGagal = document.getElementById('modal-gagal');

      var siap = { lama: false, baru: false, konfirmasi: false };
      var timer = { lama: null, baru: null, konfirmasi: null };
      var pesanLama = '';
      var nomor = 0;
      var sedangKirim = false;

      function panjang(s) {
        return Array.from(s).length;
      }

      function Masker(id, saatUbah) {
        var vis = document.getElementById(id);
        var btn = document.querySelector('.pw-mata[data-target="' + id + '"]');
        var asli = '';
        var prev = '';
        var tampil = false;
        var sel = null;

        function bintang(n) {
          return new Array(n + 1).join('*');
        }

        function render(caret) {
          vis.value = tampil ? asli : bintang(asli.length);
          prev = vis.value;
          if (caret !== null && caret !== undefined) {
            try { vis.setSelectionRange(caret, caret); } catch (err) {}
          }
        }

        vis.addEventListener('beforeinput', function () {
          sel = { s: vis.selectionStart, e: vis.selectionEnd };
        });

        vis.addEventListener('input', function () {
          var now = vis.value;
          var c = vis.selectionStart;
          var p, tambah, hapus, k;

          if (tampil) {
            asli = now;
            prev = now;
            sel = null;
            saatUbah();
            return;
          }

          if (sel && sel.e > sel.s) {
            p = sel.s;
            hapus = sel.e - sel.s;
            tambah = now.substring(p, c);
          } else if (now.length > prev.length) {
            k = now.length - prev.length;
            p = sel ? sel.s : c - k;
            hapus = 0;
            tambah = now.substring(p, p + k);
          } else if (now.length < prev.length) {
            p = c;
            hapus = prev.length - now.length;
            tambah = '';
          } else {
            render(c);
            sel = null;
            return;
          }

          asli = asli.substring(0, p) + tambah + asli.substring(p + hapus);
          render(p + tambah.length);
          sel = null;
          saatUbah();
        });

        btn.addEventListener('click', function () {
          var c = vis.selectionStart;
          tampil = !tampil;
          btn.setAttribute('aria-pressed', tampil ? 'true' : 'false');
          btn.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
          vis.classList.toggle('pw-input--bintang', !tampil);
          vis.focus();
          render(c);
        });

        return {
          el: vis,
          nilai: function () { return asli; },
          reset: function () {
            asli = '';
            tampil = false;
            btn.setAttribute('aria-pressed', 'false');
            btn.setAttribute('aria-label', 'Tampilkan password');
            vis.classList.add('pw-input--bintang');
            render(0);
          }
        };
      }

      function tampilkan(kunci, pesan) {
        var p = pesanEl[kunci];
        var inp = kunci === 'lama' ? lama.el : (kunci === 'baru' ? baru.el : konf.el);
        if (pesan) {
          p.textContent = pesan;
          p.hidden = false;
          inp.setAttribute('aria-invalid', 'true');
        } else {
          p.textContent = '';
          p.hidden = true;
          inp.removeAttribute('aria-invalid');
        }
      }

      function galatLokal(paksa) {
        var g = {};
        var l = lama.nilai();
        var b = baru.nilai();
        var k = konf.nilai();
        var pb = panjang(b);

        if (l === '') {
          if (paksa) g.lama = 'Password lama wajib diisi';
        } else if (pesanLama !== '') {
          g.lama = pesanLama;
        }

        if (b === '') {
          if (paksa) g.baru = 'Password baru wajib diisi';
        } else if (pb < 8) {
          g.baru = 'Password baru minimal 8 karakter';
        } else if (pb > 255) {
          g.baru = 'Password baru maksimal 255 karakter';
        } else if (l !== '' && b === l) {
          g.baru = 'Password baru harus berbeda dari password lama';
        }

        if (k === '') {
          if (paksa) g.konfirmasi = 'Konfirmasi password wajib diisi';
        } else if (k !== b) {
          g.konfirmasi = 'Konfirmasi password tidak sesuai';
        }

        return g;
      }

      function segarkan(paksa) {
        var g = galatLokal(paksa);
        urutan.forEach(function (kunci) {
          var tampilPesan = paksa || siap[kunci];
          tampilkan(kunci, tampilPesan ? (g[kunci] || '') : '');
        });
      }

      function kirim(aksi, nilaiLama) {
        var data = new URLSearchParams();
        data.append('aksi', aksi);
        data.append('csrf', csrf);
        data.append('password_lama', nilaiLama);
        if (aksi === 'simpan') {
          data.append('password_baru', baru.nilai());
          data.append('konfirmasi_password', konf.nilai());
        }
        return fetch(urlAksi, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: data
        })
          .then(function (r) {
            return r.json().catch(function () { return null; });
          })
          .then(function (j) {
            if (j && j.login === false) {
              window.location.href = '../auth/Login.php';
              return null;
            }
            return j;
          })
          .catch(function () { return null; });
      }

      function cekLama() {
        var nilai = lama.nilai();
        if (nilai === '') return;
        var id = ++nomor;
        kirim('cek_lama', nilai).then(function (j) {
          if (id !== nomor || lama.nilai() !== nilai) return;
          if (j && j.status === 'ok') {
            pesanLama = '';
          } else if (j && j.errors && j.errors.lama) {
            pesanLama = j.errors.lama;
          } else {
            return;
          }
          segarkan(false);
        });
      }

      function selesai(kunci) {
        clearTimeout(timer[kunci]);
        timer[kunci] = null;
        if (kunci === 'lama') {
          if (lama.nilai() === '') {
            siap.lama = false;
            segarkan(false);
            return;
          }
          siap.lama = true;
          if (baru.nilai() !== '') siap.baru = true;
          segarkan(false);
          cekLama();
          return;
        }
        if (kunci === 'baru') {
          if (baru.nilai() === '') {
            siap.baru = false;
          } else {
            siap.baru = true;
            if (konf.nilai() !== '') siap.konfirmasi = true;
          }
          segarkan(false);
          return;
        }
        siap.konfirmasi = konf.nilai() !== '';
        segarkan(false);
      }

      function saatUbah(kunci) {
        clearTimeout(timer[kunci]);
        timer[kunci] = null;
        siap[kunci] = false;

        if (kunci === 'lama') {
          pesanLama = '';
          nomor++;
          if (baru.nilai() !== '') siap.baru = false;
        }
        if (kunci === 'baru' && konf.nilai() !== '') {
          siap.konfirmasi = false;
        }

        if (
          (kunci === 'lama' && lama.nilai() === '') ||
          (kunci === 'baru' && baru.nilai() === '') ||
          (kunci === 'konfirmasi' && konf.nilai() === '')
        ) {
          segarkan(false);
          return;
        }

        segarkan(false);
        timer[kunci] = setTimeout(function () { selesai(kunci); }, JEDA);
      }

      var lama = Masker('password_lama', function () { saatUbah('lama'); });
      var baru = Masker('password_baru', function () { saatUbah('baru'); });
      var konf = Masker('konfirmasi_password', function () { saatUbah('konfirmasi'); });

      lama.el.addEventListener('blur', function () {
        if (!siap.lama && lama.nilai() !== '') selesai('lama');
      });
      baru.el.addEventListener('blur', function () {
        if (!siap.baru && baru.nilai() !== '') selesai('baru');
      });
      konf.el.addEventListener('blur', function () {
        if (!siap.konfirmasi && konf.nilai() !== '') selesai('konfirmasi');
      });

      function tampilSukses() {
        modalSukses.hidden = false;
        document.getElementById('aksi-ok').focus();
      }

      function tampilGagal() {
        modalGagal.hidden = false;
        document.getElementById('aksi-coba').focus();
      }

      function tutupGagal() {
        modalGagal.hidden = true;
        var fokus = null;
        urutan.forEach(function (kunci) {
          if (!fokus && !pesanEl[kunci].hidden) fokus = kunci;
        });
        if (fokus === 'baru') baru.el.focus();
        else if (fokus === 'konfirmasi') konf.el.focus();
        else lama.el.focus();
      }

      document.getElementById('aksi-coba').addEventListener('click', function (e) {
        e.preventDefault();
        tutupGagal();
      });

      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modalGagal.hidden) tutupGagal();
      });

      form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (sedangKirim) return;

        urutan.forEach(function (kunci) {
          clearTimeout(timer[kunci]);
          siap[kunci] = true;
        });
        nomor++;

        var g = galatLokal(true);
        if (Object.keys(g).length) {
          segarkan(true);
          tampilGagal();
          return;
        }

        sedangKirim = true;
        simpan.disabled = true;

        kirim('simpan', lama.nilai()).then(function (j) {
          sedangKirim = false;
          simpan.disabled = false;

          if (j && j.status === 'ok') {
            tampilSukses();
            return;
          }

          if (j && j.errors && j.errors.lama) {
            pesanLama = j.errors.lama;
          }
          segarkan(true);
          tampilGagal();
        });
      });

      window.addEventListener('pageshow', function (ev) {
        sedangKirim = false;
        simpan.disabled = false;
        if (ev.persisted) {
          lama.reset();
          baru.reset();
          konf.reset();
          siap = { lama: false, baru: false, konfirmasi: false };
          pesanLama = '';
          modalSukses.hidden = true;
          modalGagal.hidden = true;
          segarkan(false);
        }
      });
    })();
  </script>
</body>
</html>