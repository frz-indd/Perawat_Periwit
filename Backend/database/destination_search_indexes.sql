-- Jalankan sekali pada database eksplormajaku untuk mendukung pencarian destinasi.
-- Prefix 191 menjaga kompatibilitas dengan batas index InnoDB/utf8mb4 lama.
ALTER TABLE `wisata`
    ADD INDEX `idx_wisata_nama_pencarian` (`nama_wisata`),
    ADD INDEX `idx_wisata_deskripsi_pencarian` (`deskripsi`(191));
