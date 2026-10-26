USE eksplormajaku;

ALTER TABLE `wisata`
    ADD COLUMN IF NOT EXISTS `average_rating` DECIMAL(3,2) DEFAULT NULL;

UPDATE `wisata` AS w
SET w.average_rating = (
    SELECT ROUND(AVG(u.rating), 2)
    FROM `ulasan` AS u
    WHERE u.Id_wisata = w.id_wisata
);
