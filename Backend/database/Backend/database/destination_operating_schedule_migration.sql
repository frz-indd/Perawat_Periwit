-- Tambahan kolom untuk hari dan jam operasional pada form Data Wisata.
ALTER TABLE wisata
    ADD COLUMN hari_operasional VARCHAR(120) NULL AFTER deskripsi,
    ADD COLUMN jam_operasional VARCHAR(120) NULL AFTER hari_operasional;
