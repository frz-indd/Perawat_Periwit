USE eksplormajaku;

ALTER TABLE `user`
	ADD UNIQUE KEY uq_user_email (Email);

ALTER TABLE wisata
	MODIFY COLUMN harga_tiket DECIMAL(10, 2) UNSIGNED NOT NULL DEFAULT 0,
	MODIFY COLUMN latitude DECIMAL(10, 7) NULL,
	CHANGE COLUMN longtitude longitude DECIMAL(10, 7) NULL;

ALTER TABLE favorit
	CHANGE COLUMN Id_fasilitas Id_favorit INT(12) NOT NULL AUTO_INCREMENT,
	ADD UNIQUE KEY uq_favorit_user_wisata (Id_user, Id_wisata);

ALTER TABLE wisata_fasilitas
	ADD PRIMARY KEY (Id_wisata, Id_fasilitas);

ALTER TABLE wisata
	ADD CONSTRAINT fk_wisata_kategori
		FOREIGN KEY (id_kategori) REFERENCES kategori(Id_kategori)
		ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE foto_wisata
	ADD CONSTRAINT fk_foto_wisata
		FOREIGN KEY (id_wisata) REFERENCES wisata(id_wisata)
		ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE favorit
	ADD CONSTRAINT fk_favorit_user
		FOREIGN KEY (Id_user) REFERENCES `user`(Id_user)
		ON UPDATE CASCADE ON DELETE CASCADE,
	ADD CONSTRAINT fk_favorit_wisata
		FOREIGN KEY (Id_wisata) REFERENCES wisata(id_wisata)
		ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ulasan
	ADD CONSTRAINT fk_ulasan_user
		FOREIGN KEY (Id_user) REFERENCES `user`(Id_user)
		ON UPDATE CASCADE ON DELETE CASCADE,
	ADD CONSTRAINT fk_ulasan_wisata
		FOREIGN KEY (Id_wisata) REFERENCES wisata(id_wisata)
		ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE wisata_fasilitas
	ADD CONSTRAINT fk_wisata_fasilitas_wisata
		FOREIGN KEY (Id_wisata) REFERENCES wisata(id_wisata)
		ON UPDATE CASCADE ON DELETE CASCADE,
	ADD CONSTRAINT fk_wisata_fasilitas_fasilitas
		FOREIGN KEY (Id_fasilitas) REFERENCES fasilitas(Id_fasilitas)
		ON UPDATE CASCADE ON DELETE CASCADE;

INSERT INTO kategori (nama_kategori, deskripsi)
SELECT 'Alam', 'Destinasi alam dan ruang terbuka'
WHERE NOT EXISTS (
	SELECT 1 FROM kategori WHERE nama_kategori = 'Alam'
);

INSERT INTO kategori (nama_kategori, deskripsi)
SELECT 'Budaya', 'Destinasi budaya dan sejarah'
WHERE NOT EXISTS (
	SELECT 1 FROM kategori WHERE nama_kategori = 'Budaya'
);

INSERT INTO kategori (nama_kategori, deskripsi)
SELECT 'Kuliner', 'Destinasi kuliner dan makanan lokal'
WHERE NOT EXISTS (
	SELECT 1 FROM kategori WHERE nama_kategori = 'Kuliner'
);
