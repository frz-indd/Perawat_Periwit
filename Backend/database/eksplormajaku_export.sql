-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: eksplormajaku
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `eksplormajaku`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `eksplormajaku` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `eksplormajaku`;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `Id_admin` int(12) NOT NULL AUTO_INCREMENT,
  `Nama_admin` varchar(255) NOT NULL,
  `Username` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  PRIMARY KEY (`Id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fasilitas`
--

DROP TABLE IF EXISTS `fasilitas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fasilitas` (
  `Id_fasilitas` int(12) NOT NULL AUTO_INCREMENT,
  `nama_fasilitas` varchar(255) NOT NULL,
  PRIMARY KEY (`Id_fasilitas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fasilitas`
--

LOCK TABLES `fasilitas` WRITE;
/*!40000 ALTER TABLE `fasilitas` DISABLE KEYS */;
/*!40000 ALTER TABLE `fasilitas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `favorit`
--

DROP TABLE IF EXISTS `favorit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `favorit` (
  `Id_favorit` int(12) NOT NULL AUTO_INCREMENT,
  `Id_user` int(12) NOT NULL,
  `Id_wisata` int(12) NOT NULL,
  `tanggal_ditambahkan` date NOT NULL,
  PRIMARY KEY (`Id_favorit`),
  UNIQUE KEY `uq_favorit_user_wisata` (`Id_user`,`Id_wisata`),
  KEY `fk_favorit_wisata` (`Id_wisata`),
  CONSTRAINT `fk_favorit_user` FOREIGN KEY (`Id_user`) REFERENCES `user` (`Id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_favorit_wisata` FOREIGN KEY (`Id_wisata`) REFERENCES `wisata` (`id_wisata`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `favorit`
--

LOCK TABLES `favorit` WRITE;
/*!40000 ALTER TABLE `favorit` DISABLE KEYS */;
/*!40000 ALTER TABLE `favorit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `foto_wisata`
--

DROP TABLE IF EXISTS `foto_wisata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `foto_wisata` (
  `Id_foto` int(12) NOT NULL AUTO_INCREMENT,
  `id_wisata` int(12) NOT NULL,
  `Nama_Foto` varchar(255) NOT NULL,
  PRIMARY KEY (`Id_foto`),
  KEY `fk_foto_wisata` (`id_wisata`),
  CONSTRAINT `fk_foto_wisata` FOREIGN KEY (`id_wisata`) REFERENCES `wisata` (`id_wisata`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `foto_wisata`
--

LOCK TABLES `foto_wisata` WRITE;
/*!40000 ALTER TABLE `foto_wisata` DISABLE KEYS */;
/*!40000 ALTER TABLE `foto_wisata` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kategori`
--

DROP TABLE IF EXISTS `kategori`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kategori` (
  `Id_kategori` int(12) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  PRIMARY KEY (`Id_kategori`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kategori`
--

LOCK TABLES `kategori` WRITE;
/*!40000 ALTER TABLE `kategori` DISABLE KEYS */;
INSERT INTO `kategori` VALUES (1,'Alam','Destinasi alam dan ruang terbuka'),(2,'Budaya','Destinasi budaya dan sejarah'),(3,'Kuliner','Destinasi kuliner dan makanan lokal');
/*!40000 ALTER TABLE `kategori` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ulasan`
--

DROP TABLE IF EXISTS `ulasan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ulasan` (
  `Id_ulasan` int(12) NOT NULL AUTO_INCREMENT,
  `Id_user` int(12) NOT NULL,
  `Id_wisata` int(12) NOT NULL,
  `rating` float NOT NULL,
  `komentar` text NOT NULL,
  `tanggal_ulasan` date NOT NULL,
  PRIMARY KEY (`Id_ulasan`),
  KEY `fk_ulasan_user` (`Id_user`),
  KEY `fk_ulasan_wisata` (`Id_wisata`),
  CONSTRAINT `fk_ulasan_user` FOREIGN KEY (`Id_user`) REFERENCES `user` (`Id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ulasan_wisata` FOREIGN KEY (`Id_wisata`) REFERENCES `wisata` (`id_wisata`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ulasan`
--

LOCK TABLES `ulasan` WRITE;
/*!40000 ALTER TABLE `ulasan` DISABLE KEYS */;
/*!40000 ALTER TABLE `ulasan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `Id_user` int(12) NOT NULL AUTO_INCREMENT,
  `Nama_user` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Id_user`),
  UNIQUE KEY `uq_user_email` (`Email`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (7,'faris rachman','farisrachman@gmail.com','$2y$10$Zj..xVs7KTURCK39rg7JXu8vN10LFOKbrA6Bat/qWWfqi0ZskhnXW'),(8,'faris rachman','farisrachman213@gmail.com','$2y$10$b0Mexmr5YfzdKLHCs3PYzuU1SFu1QwOUZ5B.HQu4KFmDoMF9khxg2');
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_oauth_account`
--

DROP TABLE IF EXISTS `user_oauth_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_oauth_account` (
  `Id_oauth_account` int(12) NOT NULL AUTO_INCREMENT,
  `Id_user` int(12) NOT NULL,
  `provider` varchar(30) NOT NULL,
  `provider_user_id` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Id_oauth_account`),
  UNIQUE KEY `uq_oauth_provider_subject` (`provider`,`provider_user_id`),
  KEY `idx_oauth_user` (`Id_user`),
  CONSTRAINT `fk_oauth_user` FOREIGN KEY (`Id_user`) REFERENCES `user` (`Id_user`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_oauth_account`
--

LOCK TABLES `user_oauth_account` WRITE;
/*!40000 ALTER TABLE `user_oauth_account` DISABLE KEYS */;
INSERT INTO `user_oauth_account` VALUES (1,8,'google','112001657926365342324','farisrachman213@gmail.com','2026-10-02 05:43:57');
/*!40000 ALTER TABLE `user_oauth_account` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wisata`
--

DROP TABLE IF EXISTS `wisata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wisata` (
  `id_wisata` int(12) NOT NULL,
  `id_kategori` int(12) DEFAULT NULL,
  `nama_wisata` varchar(255) DEFAULT NULL,
  `deskripsi` varchar(1000) DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `harga_tiket` decimal(10,2) unsigned NOT NULL DEFAULT 0.00,
  `jam_buka` time DEFAULT NULL,
  `jam_tutup` time DEFAULT NULL,
  `domisili` varchar(255) NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  PRIMARY KEY (`id_wisata`),
  KEY `fk_wisata_kategori` (`id_kategori`),
  CONSTRAINT `fk_wisata_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`Id_kategori`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wisata`
--

LOCK TABLES `wisata` WRITE;
/*!40000 ALTER TABLE `wisata` DISABLE KEYS */;
/*!40000 ALTER TABLE `wisata` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wisata_fasilitas`
--

DROP TABLE IF EXISTS `wisata_fasilitas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wisata_fasilitas` (
  `Id_wisata` int(12) NOT NULL,
  `Id_fasilitas` int(12) NOT NULL,
  PRIMARY KEY (`Id_wisata`,`Id_fasilitas`),
  KEY `fk_wisata_fasilitas_fasilitas` (`Id_fasilitas`),
  CONSTRAINT `fk_wisata_fasilitas_fasilitas` FOREIGN KEY (`Id_fasilitas`) REFERENCES `fasilitas` (`Id_fasilitas`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_wisata_fasilitas_wisata` FOREIGN KEY (`Id_wisata`) REFERENCES `wisata` (`id_wisata`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wisata_fasilitas`
--

LOCK TABLES `wisata_fasilitas` WRITE;
/*!40000 ALTER TABLE `wisata_fasilitas` DISABLE KEYS */;
/*!40000 ALTER TABLE `wisata_fasilitas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'eksplormajaku'
--

--
-- Dumping routines for database 'eksplormajaku'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-05 19:46:43
