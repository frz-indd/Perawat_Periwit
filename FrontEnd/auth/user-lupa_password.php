<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (isset($_GET['action']) && $_GET['action'] === 'forgot') {
    header('Content-Type: application/json; charset=utf-8');
    $raw = file_get_contents('php://input');
    $body = json_decode((string)$raw, true) ?: $_POST;
    $email = trim((string)($body['email'] ?? ''));

    if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        http_response_code(422);
        echo json_encode(['status' => 'validation_error', 'message' => 'Email tidak valid/tidak terdaftar']);
        exit;
    }

    try {
        require_once __DIR__ . '/../../Backend/database/koneksi.php';
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->set_charset('utf8mb4');

            // Cek apakah email terdaftar di tabel user
            $stmt = $conn->prepare('SELECT Id_user, Email, role FROM `user` WHERE LOWER(Email) = LOWER(?) LIMIT 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$user) {
                http_response_code(404);
                echo json_encode(['status' => 'not_found', 'message' => 'Email tidak valid/tidak terdaftar']);
                exit;
            }

            // Pastikan akun user biasa (bukan akun admin)
            $role = $user['role'] ?? null;
            if ($role === 1 || $role === '1' || strtolower((string)$role) === 'admin') {
                http_response_code(403);
                echo json_encode(['status' => 'forbidden', 'message' => 'Email tidak valid/tidak terdaftar']);
                exit;
            }

            // Siapkan tabel user_password_resets jika belum ada
            $conn->query("CREATE TABLE IF NOT EXISTS `user_password_resets` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` BIGINT UNSIGNED NOT NULL,
                `email` VARCHAR(255) NOT NULL,
                `token_hash` CHAR(64) NOT NULL,
                `expires_at` DATETIME NOT NULL,
                `used_at` DATETIME NULL DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_user_token_hash` (`token_hash`),
                KEY `idx_user_resets_email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);
            $userId = (int)$user['Id_user'];

            $ins = $conn->prepare('INSERT INTO `user_password_resets` (user_id, email, token_hash, expires_at) VALUES (?, ?, ?, ?)');
            $ins->bind_param('isss', $userId, $email, $tokenHash, $expiresAt);
            $ins->execute();
            $ins->close();

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Tautan reset berhasil dikirim.',
                'token' => $token,
                'reset_url' => 'user-ubah_password.php?token=' . $token
            ]);
            exit;
        }
    } catch (Throwable $e) {}

    http_response_code(500);
    echo json_encode(['status' => 'server_error', 'message' => 'Maaf, tautan reset password belum berhasil dikirim. Silakan periksa koneksi internet atau coba beberapa saat lagi.']);
    exit;
}

$login = 'Login.php';
$api = 'user-lupa_password.php?action=forgot';
$e = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Lupa Password | Explore Majaku</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Kaushan+Script&family=Inter:wght@400;500&family=Noto+Serif:wght@400;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/admin-lupa_password.css?v=<?= time() ?>">
</head>
<body>
  <form id="form-lupa" method="post" novalidate data-api-url="<?= $e($api) ?>" data-api-method="POST">
    <main class="lp-stage">
      <svg class="lp-ikon" viewBox="0 0 120 90" fill="none" stroke="currentColor" stroke-width="10" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="5" y="5" width="110" height="80" rx="10"/>
        <path d="M12 14L60 50L108 14" stroke-width="9"/>
      </svg>
      <h1 class="lp-judul">Lupa Password?</h1>
      <p class="lp-sub">Masukkan alamat email yang terdaftar untuk menerima tautan reset password.</p>

      <div class="lp-grup">
        <label class="lp-label" for="email">Alamat Email</label>
        <input class="lp-input" type="email" id="email" name="email" placeholder="Masukkan email anda"
               autocomplete="email" inputmode="email" autocapitalize="off" spellcheck="false" maxlength="254"
               aria-describedby="error-email">
        <p class="lp-error" id="error-email" role="alert" hidden></p>
      </div>

      <button type="submit" class="lp-btn lp-btn--utama" id="btn-kirim">Kirim Tautan Reset</button>
      <a class="lp-btn lp-btn--kembali" href="<?= $e($login) ?>">
        <svg viewBox="0 0 28 22" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M27 11H2M11 2L2 11l9 9"/></svg>
        <span>Kembali ke login</span>
      </a>
    </main>
  </form>

  <div class="lp-modal" id="modal-sukses" role="dialog" aria-modal="true" aria-labelledby="judul-sukses" hidden>
    <div class="lp-modal__kotak">
      <div class="lp-modal__ikon">
        <svg viewBox="0 0 96 96" fill="none" stroke="currentColor" stroke-width="5.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M52 70H22a5 5 0 0 1-5-5V31a5 5 0 0 1 5-5h52a5 5 0 0 1 5 5v14M19 30l29 22 29-22M57 63l7 7 14-15"/>
        </svg>
      </div>
      <h2 class="lp-modal__judul" id="judul-sukses">Tautan Reset Berhasil Dikirim!</h2>
      <p class="lp-modal__isi" id="teks-sukses">Kami telah mengirimkan tautan untuk mereset kata sandi ke alamat email anda. Silahkan periksa kotak masuk email anda</p>
      <a class="lp-modal__ok" href="<?= $e($login) ?>" id="aksi-login">Kembali ke login</a>
    </div>
  </div>

  <div class="lp-modal" id="modal-gagal" role="alertdialog" aria-modal="true" aria-labelledby="judul-gagal" hidden>
    <div class="lp-modal__kotak">
      <div class="lp-modal__ikon lp-modal__ikon--gagal">
        <svg viewBox="0 0 96 96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M52 70H22a5 5 0 0 1-5-5V31a5 5 0 0 1 5-5h52a5 5 0 0 1 5 5v14M19 30l29 22 29-22" stroke-width="5.5"/>
          <path d="M58 61l15 15M73 61L58 76" stroke-width="3"/>
        </svg>
      </div>
      <h2 class="lp-modal__judul lp-modal__judul--gagal" id="judul-gagal">Tautan Reset Gagal Dikirim!</h2>
      <p class="lp-modal__isi" id="teks-gagal">Maaf, tautan reset password belum berhasil dikirim. Silakan periksa koneksi internet atau coba beberapa saat lagi.</p>
      <button type="button" class="lp-modal__ok" id="aksi-coba">Coba lagi</button>
    </div>
  </div>

  <script src="../Js/user-lupa_password.js?v=<?= time() ?>"></script>
</body>
</html>
