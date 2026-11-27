-- Jalankan satu kali pada database eksplormajaku.
-- Jika sudah ada ulasan ganda, pertahankan ulasan terbaru untuk tiap user/destinasi.
USE `eksplormajaku`;

DELETE older
FROM ulasan AS older
INNER JOIN ulasan AS newer
    ON newer.Id_user = older.Id_user
   AND newer.Id_wisata = older.Id_wisata
   AND newer.Id_ulasan > older.Id_ulasan;

-- Hapus UNIQUE index lama yang membatasi satu ulasan saja untuk tiap user.
SET @bad_user_unique = (
    SELECT index_name
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'ulasan'
      AND non_unique = 0
      AND index_name <> 'PRIMARY'
    GROUP BY index_name
    HAVING COUNT(*) = 1 AND MAX(column_name) = 'Id_user'
    LIMIT 1
);
SET @drop_bad_user_unique = IF(
    @bad_user_unique IS NULL,
    'SELECT 1',
    CONCAT('ALTER TABLE `ulasan` DROP INDEX `', REPLACE(@bad_user_unique, '`', '``'), '`')
);
PREPARE stmt_drop_bad_review_key FROM @drop_bad_user_unique;
EXECUTE stmt_drop_bad_review_key;
DEALLOCATE PREPARE stmt_drop_bad_review_key;

-- Tambahkan unique index hanya jika belum tersedia.
SET @has_unique_review_key = (
    SELECT COUNT(*)
    FROM (
        SELECT index_name
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'ulasan'
          AND non_unique = 0
        GROUP BY index_name
        HAVING GROUP_CONCAT(column_name ORDER BY seq_in_index) = 'Id_user,Id_wisata'
    ) AS review_keys
);
SET @has_unique_review_key = IF(@has_unique_review_key IS NULL, 0, 1);
SET @add_unique_review_key = IF(
    @has_unique_review_key = 0,
    'ALTER TABLE `ulasan` ADD UNIQUE KEY `uq_ulasan_user_wisata` (`Id_user`, `Id_wisata`)',
    'SELECT 1'
);
PREPARE stmt_unique_review FROM @add_unique_review_key;
EXECUTE stmt_unique_review;
DEALLOCATE PREPARE stmt_unique_review;

-- Sinkronkan ulang rata-rata setelah data duplikat dibersihkan.
UPDATE wisata AS w
SET w.average_rating = (
    SELECT ROUND(AVG(u.rating), 2)
    FROM ulasan AS u
    WHERE u.Id_wisata = w.id_wisata
);
