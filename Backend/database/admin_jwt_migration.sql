-- Migration untuk memastikan tabel admin/users siap mendukung autentikasi admin & JWT
USE `eksplormajaku`;

-- 1. Pastikan tabel users memiliki role jika ingin menggunakan tabel users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL UNIQUE,
  `email` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Pastikan tabel admin juga tersedia
CREATE TABLE IF NOT EXISTS `admin` (
  `Id_admin` int(12) NOT NULL AUTO_INCREMENT,
  `Nama_admin` varchar(255) NOT NULL,
  `Username` varchar(255) NOT NULL UNIQUE,
  `Password` varchar(255) NOT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'admin',
  PRIMARY KEY (`Id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Pastikan kolom role dan email ada di tabel admin jika tabel sudah ada sebelumnya
ALTER TABLE `admin` 
  ADD COLUMN IF NOT EXISTS `Email` varchar(255) DEFAULT NULL AFTER `Username`,
  ADD COLUMN IF NOT EXISTS `role` varchar(50) NOT NULL DEFAULT 'admin' AFTER `Password`;

-- 4. Seed sample admin jika belum ada
-- Password default: 'admin123' (hash bcrypt: $2y$10$wH6QO0qPq4k6z7mQ8D5S..G9N4yQ1P0Mv3Nn4zT8.yZgP)
INSERT INTO `users` (`id`, `username`, `email`, `name`, `password`, `role`)
SELECT 1, 'admin', 'admin@majakusample.com', 'Administrator MajaKu', '$2y$10$wH6QO0qPq4k6z7mQ8D5S..G9N4yQ1P0Mv3Nn4zT8.yZgP', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `username` = 'admin');

INSERT INTO `admin` (`Id_admin`, `Nama_admin`, `Username`, `Email`, `Password`, `role`)
SELECT 1, 'Administrator MajaKu', 'admin', 'admin@majakusample.com', '$2y$10$wH6QO0qPq4k6z7mQ8D5S..G9N4yQ1P0Mv3Nn4zT8.yZgP', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM `admin` WHERE `Username` = 'admin');

