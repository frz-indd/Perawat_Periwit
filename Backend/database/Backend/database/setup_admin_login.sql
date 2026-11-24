-- Jalankan satu kali di database eksplormajaku melalui phpMyAdmin.
-- Membuat / menyiapkan akun admin yang bisa dipakai untuk login.
USE `eksplormajaku`;

CREATE TABLE IF NOT EXISTS `admin` (
  `Id_admin` int(12) NOT NULL AUTO_INCREMENT,
  `Nama_admin` varchar(255) NOT NULL,
  `Username` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  PRIMARY KEY (`Id_admin`),
  UNIQUE KEY `uq_admin_username` (`Username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Kolom ini diperlukan untuk database lama dari file eksport proyek.
ALTER TABLE `admin`
  ADD COLUMN `Email` varchar(255) DEFAULT NULL,
  ADD COLUMN `role` varchar(50) NOT NULL DEFAULT 'admin';

-- Akun awal: username = admin, password = admin123.
-- Hash bcrypt valid; password akan di-hash ulang otomatis oleh endpoint saat login.
INSERT INTO `admin` (`Nama_admin`, `Username`, `Password`, `Email`, `role`)
VALUES (
  'Administrator Explore Majaku',
  'admin',
  '$2y$12$oTHl3qf4LDjWBXXkedh2gerU/Dv6x7pxY8oiRihh0ouaQbIIjQcyy',
  'admin@exploremajaku.local',
  'admin'
)
ON DUPLICATE KEY UPDATE
  `Nama_admin` = VALUES(`Nama_admin`),
  `Password` = VALUES(`Password`),
  `Email` = VALUES(`Email`),
  `role` = 'admin';
