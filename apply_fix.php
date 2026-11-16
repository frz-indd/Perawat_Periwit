<?php
declare(strict_types=1);

require_once __DIR__ . '/Backend/database/koneksi.php';
if (!$conn instanceof mysqli) {
    echo "Gagal koneksi ke database.\n";
    exit(1);
}

echo "Memulai migrasi database...\n";

// 1. Buat tabel facilities jika belum ada
$sqlFacilities = "
CREATE TABLE IF NOT EXISTS `facilities` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `icon_code` VARCHAR(100) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_facilities_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
if (!$conn->query($sqlFacilities)) {
    echo "Error tabel facilities: " . $conn->error . "\n";
} else {
    echo "Tabel facilities siap.\n";
}

// 2. Tambah kolom average_rating dan rating pada wisata
$colsWisata = [];
$res = $conn->query("SHOW COLUMNS FROM `wisata`");
while ($r = $res->fetch_assoc()) {
    $colsWisata[] = strtolower($r['Field']);
}

if (!in_array('average_rating', $colsWisata, true)) {
    $conn->query("ALTER TABLE `wisata` ADD COLUMN `average_rating` DECIMAL(3,2) DEFAULT NULL");
    echo "Kolom wisata.average_rating ditambahkan.\n";
}
if (!in_array('rating', $colsWisata, true)) {
    $conn->query("ALTER TABLE `wisata` ADD COLUMN `rating` DECIMAL(3,2) DEFAULT NULL");
    echo "Kolom wisata.rating ditambahkan.\n";
}

$conn->query("
    UPDATE `wisata` AS w
    SET 
      w.average_rating = (SELECT ROUND(AVG(u.rating), 2) FROM `ulasan` AS u WHERE u.Id_wisata = w.id_wisata),
      w.rating = (SELECT ROUND(AVG(u.rating), 2) FROM `ulasan` AS u WHERE u.Id_wisata = w.id_wisata)
");
echo "Data rating wisata diperbarui.\n";

// 3. Tambah kolom Username dan role pada tabel user
$colsUser = [];
$res = $conn->query("SHOW COLUMNS FROM `user`");
while ($r = $res->fetch_assoc()) {
    $colsUser[] = strtolower($r['Field']);
}

if (!in_array('username', $colsUser, true)) {
    $conn->query("ALTER TABLE `user` ADD COLUMN `Username` VARCHAR(100) DEFAULT NULL AFTER `Nama_user`");
    echo "Kolom user.Username ditambahkan.\n";
}
if (!in_array('role', $colsUser, true)) {
    $conn->query("ALTER TABLE `user` ADD COLUMN `role` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0: user, 1: admin'");
    echo "Kolom user.role ditambahkan.\n";
}

// 4. Migrasikan akun dari tabel admin ke user dengan role = 1
$resAdmin = $conn->query("SELECT * FROM admin");
if ($resAdmin) {
    while ($admin = $resAdmin->fetch_assoc()) {
        $username = $admin['Username'] ?? '';
        $nama = $admin['Nama_admin'] ?? 'Admin';
        $password = $admin['Password'] ?? '';
        
        $check = $conn->prepare("SELECT Id_user FROM `user` WHERE Username = ? OR Email = ? LIMIT 1");
        $check->bind_param("ss", $username, $username);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        $check->close();

        if ($existing) {
            $update = $conn->prepare("UPDATE `user` SET role = 1, Password = ? WHERE Id_user = ?");
            $update->bind_param("si", $password, $existing['Id_user']);
            $update->execute();
            $update->close();
            echo "Akun user ID {$existing['Id_user']} ($username) diupdate menjadi role = 1 (admin).\n";
        } else {
            $insert = $conn->prepare("INSERT INTO `user` (Nama_user, Username, Password, role) VALUES (?, ?, ?, 1)");
            $insert->bind_param("sss", $nama, $username, $password);
            $insert->execute();
            $insert->close();
            echo "Akun admin '$username' berhasil disalin ke tabel user dengan role = 1.\n";
        }
    }
}

// 5. Buat tabel user_sessions
$sqlSessions = "
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT(12) NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `revoked_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_sessions_hash` (`token_hash`),
  INDEX `idx_user_sessions_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
if (!$conn->query($sqlSessions)) {
    echo "Error tabel user_sessions: " . $conn->error . "\n";
} else {
    echo "Tabel user_sessions siap.\n";
}

// Salin sesi aktif dari admin_sessions ke user_sessions agar login saat ini tidak hilang
$conn->query("
    INSERT IGNORE INTO `user_sessions` (id, user_id, token_hash, expires_at, revoked_at, created_at)
    SELECT id, admin_id, token_hash, expires_at, revoked_at, created_at FROM `admin_sessions`
");
echo "Sinkronisasi sesi selesai.\n";

// 6. Buat View `users` agar kompatibel jika query menggunakan `users`
$conn->query("CREATE OR REPLACE VIEW `users` AS SELECT * FROM `user`");
echo "View `users` berhasil dibuat.\n";

echo "Semua migrasi database berhasil diselesaikan!\n";
