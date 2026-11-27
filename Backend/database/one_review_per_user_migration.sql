-- Jalankan satu kali pada database eksplormajaku.
-- Jika sudah ada ulasan ganda, pertahankan ulasan terbaru untuk tiap user/destinasi.
DELETE older
FROM ulasan AS older
INNER JOIN ulasan AS newer
    ON newer.Id_user = older.Id_user
   AND newer.Id_wisata = older.Id_wisata
   AND newer.Id_ulasan > older.Id_ulasan;

-- Tambahkan unique index hanya jika belum tersedia.
SET @has_unique_review_key = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'ulasan'
      AND index_name = 'uq_ulasan_user_wisata'
);
SET @add_unique_review_key = IF(
    @has_unique_review_key = 0,
    'ALTER TABLE ulasan ADD UNIQUE KEY uq_ulasan_user_wisata (Id_user, Id_wisata)',
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
