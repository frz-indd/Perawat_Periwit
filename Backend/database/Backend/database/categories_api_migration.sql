USE eksplormajaku;

ALTER TABLE `kategori`
	ADD COLUMN IF NOT EXISTS `gambar` varchar(600) DEFAULT NULL,
	ADD COLUMN IF NOT EXISTS `aktif` tinyint(1) NOT NULL DEFAULT 1;

UPDATE `kategori`
SET `gambar` = CASE LOWER(`nama_kategori`)
	WHEN 'alam' THEN 'FrontEnd/assets/images/gununglandscape.jpg'
	WHEN 'budaya' THEN 'FrontEnd/assets/images/TeraseringPanyawean.jpg'
	WHEN 'kuliner' THEN 'FrontEnd/assets/images/makanan.png'
	WHEN 'air & curug' THEN 'FrontEnd/assets/images/CurugPutri.png'
	ELSE `gambar`
END
WHERE `gambar` IS NULL OR TRIM(`gambar`) = '';

INSERT INTO `kategori` (`Id_kategori`, `nama_kategori`, `deskripsi`, `gambar`, `aktif`)
SELECT 4, 'Air & Curug', 'Destinasi air dan curug', 'FrontEnd/assets/images/CurugPutri.png', 1
WHERE NOT EXISTS (
	SELECT 1 FROM `kategori` WHERE `Id_kategori` = 4 OR `nama_kategori` = 'Air & Curug'
);