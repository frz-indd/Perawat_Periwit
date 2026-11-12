USE eksplormajaku;

-- Menambahkan waktu pencatatan destinasi agar tabel Wisata Terbaru dapat
-- mengurutkan data terbaru dan menampilkan tanggal penambahan.
ALTER TABLE `wisata`
    ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

CREATE INDEX IF NOT EXISTS `idx_wisata_created_at` ON `wisata` (`created_at`);

-- Kolom urutan dipakai halaman administrasi kategori.
ALTER TABLE `kategori`
    ADD COLUMN IF NOT EXISTS `sort_order` INT NOT NULL DEFAULT 0;
UPDATE `kategori` SET `sort_order` = `Id_kategori` WHERE `sort_order` = 0;
