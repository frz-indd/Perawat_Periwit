USE eksplormajaku;

ALTER TABLE `wisata`
    ADD COLUMN IF NOT EXISTS `alamat` varchar(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `latitude` decimal(10,7) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `longitude` decimal(10,7) DEFAULT NULL;

-- Pair coordinates so a half-filled or out-of-range pair is treated as unavailable.
UPDATE `wisata`
SET `latitude` = NULL, `longitude` = NULL
WHERE `latitude` IS NULL
   OR `longitude` IS NULL
   OR `latitude` < -90 OR `latitude` > 90
   OR `longitude` < -180 OR `longitude` > 180;
