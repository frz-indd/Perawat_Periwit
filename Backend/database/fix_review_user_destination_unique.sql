-- Perbaikan aturan ulasan:
-- Satu user boleh mengulas banyak destinasi; satu ulasan aktif per user per destinasi.
-- Jalankan satu kali pada database eksplormajaku melalui phpMyAdmin.
USE `eksplormajaku`;

DELETE older
FROM `ulasan` AS older
INNER JOIN `ulasan` AS newer
  ON newer.`Id_user` = older.`Id_user`
 AND newer.`Id_wisata` = older.`Id_wisata`
 AND newer.`Id_ulasan` > older.`Id_ulasan`;

-- Hapus UNIQUE index keliru yang hanya mengunci Id_user (jika ada).
SET @bad_user_unique = (
  SELECT s.INDEX_NAME
  FROM information_schema.STATISTICS AS s
  WHERE s.TABLE_SCHEMA = DATABASE()
    AND s.TABLE_NAME = 'ulasan'
    AND s.NON_UNIQUE = 0
    AND s.INDEX_NAME <> 'PRIMARY'
  GROUP BY s.INDEX_NAME
  HAVING COUNT(*) = 1 AND MAX(s.COLUMN_NAME) = 'Id_user'
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
SET @add_pair_unique = IF(
  @has_pair_unique = 0,
  'ALTER TABLE `ulasan` ADD UNIQUE KEY `uq_ulasan_user_wisata` (`Id_user`, `Id_wisata`)',
  'SELECT 1'
);
PREPARE stmt_add_review_key FROM @add_pair_unique;
EXECUTE stmt_add_review_key;
DEALLOCATE PREPARE stmt_add_review_key;

UPDATE `wisata` AS w
SET w.`average_rating` = (
  SELECT ROUND(AVG(u.`rating`), 2)
  FROM `ulasan` AS u
  WHERE u.`Id_wisata` = w.`id_wisata`
);
