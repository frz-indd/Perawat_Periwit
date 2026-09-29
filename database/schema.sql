CREATE DATABASE IF NOT EXISTS explore_majaku
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE explore_majaku;

CREATE TABLE IF NOT EXISTS wisata (
    id_wisata INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_wisata VARCHAR(150) NOT NULL,
    harga_tiket DECIMAL(10, 2) UNSIGNED NOT NULL DEFAULT 0,
    wilayah VARCHAR(100) NOT NULL
);

INSERT INTO wisata (nama_wisata, harga_tiket, wilayah)
SELECT 'Terasering Panyaweuyan', 15000, 'Majalengka'
WHERE NOT EXISTS (SELECT 1 FROM wisata);