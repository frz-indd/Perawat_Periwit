-- Jalankan satu kali pada database Explore MajaKu sebelum menggunakan login admin.
-- Identitas IP dan username disimpan sebagai SHA-256, bukan nilai mentah.
CREATE TABLE IF NOT EXISTS admin_login_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip_hash CHAR(64) NOT NULL,
    username_hash CHAR(64) NOT NULL,
    failed_attempts INT UNSIGNED NOT NULL DEFAULT 0,
    first_failed_at DATETIME NOT NULL,
    last_failed_at DATETIME NOT NULL,
    locked_until DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_login_ip_username (ip_hash, username_hash),
    KEY idx_admin_login_locked_until (locked_until),
    KEY idx_admin_login_last_failed (last_failed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
