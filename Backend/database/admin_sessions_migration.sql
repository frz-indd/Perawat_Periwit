-- Jalankan satu kali pada database Explore Majaku.
-- Menyimpan hash JWT (bukan token mentah) agar token admin dapat dicabut di server.
CREATE TABLE IF NOT EXISTS admin_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_sessions_token_hash (token_hash),
    KEY idx_admin_sessions_admin_id (admin_id),
    KEY idx_admin_sessions_expiry (expires_at),
    KEY idx_admin_sessions_revoked (revoked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
