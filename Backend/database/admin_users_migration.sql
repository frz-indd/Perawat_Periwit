-- Migration untuk tanggal pendaftaran pengguna pada endpoint Admin User Management.
-- Baris pengguna lama dibiarkan NULL agar tanggal historis tidak dikarang.
-- Pengguna yang didaftarkan setelah migration otomatis mendapat CURRENT_TIMESTAMP.

ALTER TABLE `user`
    ADD COLUMN IF NOT EXISTS `created_at` DATETIME NULL DEFAULT NULL;

ALTER TABLE `user`
    ALTER COLUMN `created_at` SET DEFAULT CURRENT_TIMESTAMP;

CREATE INDEX IF NOT EXISTS `idx_user_created_at` ON `user` (`created_at`);
