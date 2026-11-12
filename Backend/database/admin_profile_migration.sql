-- ADMIN-08-BE: jalankan satu kali pada database Explore MajaKu.
-- Kolom ini menyimpan URL foto profil admin (bukan file upload).
ALTER TABLE `admin`
    ADD COLUMN `Foto_admin` VARCHAR(2048) NULL AFTER `Nama_admin`;
