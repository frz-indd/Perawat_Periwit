-- MIGRASI ATURAN ULASAN UNTUK HOSTING
-- Pilih database hosting terlebih dahulu di phpMyAdmin.
-- Tujuan: satu user boleh mengulas banyak wisata.
-- Untuk wisata yang sama, satu ulasan per user; pengiriman ulang memperbarui ulasan itu.
-- File ini TIDAK menghapus data ulasan.

-- 1. Periksa apakah ada duplikasi pada pasangan user + wisata.
SELECT Id_user, Id_wisata, COUNT(*) AS total
FROM ulasan
GROUP BY Id_user, Id_wisata
HAVING COUNT(*) > 1;

-- Jika query di atas menghasilkan baris, rapikan duplikasi secara manual dahulu.
-- 2. Hapus UNIQUE index yang hanya berisi Id_user (jika ada).
SET @bad_user_unique = (
    SELECT INDEX_NAME
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'ulasan'
      AND NON_UNIQUE = 0
      AND INDEX_NAME <> 'PRIMARY'
    GROUP BY INDEX_NAME
    HAVING COUNT(*) = 1 AND MAX(COLUMN_NAME) = 'Id_user'
    LIMIT 1
);
SET @drop_sql = IF(
    @bad_user_unique IS NULL,
    'SELECT ''Tidak ada UNIQUE index khusus Id_user'' AS info',
    CONCAT('ALTER TABLE `ulasan` DROP INDEX `', REPLACE(@bad_user_unique, '`', '``'), '`')
);
PREPARE stmt_drop_user_unique FROM @drop_sql;
EXECUTE stmt_drop_user_unique;
DEALLOCATE PREPARE stmt_drop_user_unique;

-- 3. Pastikan UNIQUE komposit (Id_user, Id_wisata) tersedia.
SET @has_pair_unique = (
    SELECT COUNT(*)
    FROM (
        SELECT INDEX_NAME
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'ulasan'
          AND NON_UNIQUE = 0
        GROUP BY INDEX_NAME
        HAVING GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) = 'Id_user,Id_wisata'
    ) AS keys_found
);
SET @add_pair_sql = IF(
    @has_pair_unique = 0,
    'ALTER TABLE `ulasan` ADD UNIQUE KEY `uq_ulasan_user_wisata` (`Id_user`, `Id_wisata`)',
    'SELECT ''UNIQUE pasangan user + wisata sudah tersedia'' AS info'
);
PREPARE stmt_add_pair FROM @add_pair_sql;
EXECUTE stmt_add_pair;
DEALLOCATE PREPARE stmt_add_pair;
