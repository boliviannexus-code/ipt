-- MySQL dump 10.13  Distrib 8.4.9, for Linux (x86_64)
--
-- Host: localhost    Database: laravel
-- ------------------------------------------------------
-- Server version	8.4.9

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `audits`
--

DROP TABLE IF EXISTS `audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned DEFAULT NULL,
  `user_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `auditable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `auditable_id` bigint unsigned NOT NULL,
  `old_values` text COLLATE utf8mb4_unicode_ci,
  `new_values` text COLLATE utf8mb4_unicode_ci,
  `url` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(1023) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tags` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audits_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  KEY `audits_user_id_user_type_index` (`user_id`,`user_type`),
  KEY `audits_company_id_index` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audits`
--

LOCK TABLES `audits` WRITE;
/*!40000 ALTER TABLE `audits` DISABLE KEYS */;
INSERT INTO `audits` VALUES (1,1,'App\\Models\\User',1,'updated','App\\Models\\Product',111,'{\"sale_price\":\"20.00\"}','{\"sale_price\":\"22.00\"}','http://localhost/products/111','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','company:1,Product','2026-05-22 17:30:53','2026-05-22 17:30:53'),(2,1,'App\\Models\\User',1,'created','App\\Models\\InventoryMovement',51,'[]','{\"product_id\":109,\"presentation_id\":6,\"presentation_name\":\"Unidad\",\"warehouse_id\":4,\"user_id\":1,\"type\":\"adjustment_in\",\"quantity\":1,\"package_quantity\":1,\"units_per_package\":1,\"reference_id\":16,\"reference_type\":\"sale_void\",\"notes\":\"Anulacion de venta 3-4-000001-000002. Motivo: pruebas sistema\",\"id\":51}','http://localhost/sales/16/void','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','company:1,InventoryMovement','2026-05-22 17:56:19','2026-05-22 17:56:19'),(3,1,'App\\Models\\User',1,'updated','App\\Models\\Sale',16,'{\"status\":\"completed\",\"notes\":null}','{\"status\":\"voided\",\"notes\":\"Anulacion de venta 3-4-000001-000002. Motivo: pruebas sistema\"}','http://localhost/sales/16/void','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','company:1,Sale','2026-05-22 17:56:19','2026-05-22 17:56:19'),(4,1,'App\\Models\\User',1,'updated','App\\Models\\User',1,'{\"company_id\":1}','{\"company_id\":null}','http://localhost/users/1','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','company:1,User','2026-05-22 20:42:26','2026-05-22 20:42:26'),(5,2,'App\\Models\\User',1,'updated','App\\Models\\User',1,'{\"company_id\":null}','{\"company_id\":\"2\"}','http://localhost/users/1','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','company:2,User','2026-05-22 20:43:08','2026-05-22 20:43:08'),(6,2,'App\\Models\\User',1,'updated','App\\Models\\User',1,'{\"company_id\":2}','{\"company_id\":null}','http://localhost/users/1','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','company:2,User','2026-05-22 20:43:21','2026-05-22 20:43:21'),(7,1,'App\\Models\\User',1,'updated','App\\Models\\User',1,'{\"company_id\":null}','{\"company_id\":\"1\"}','http://localhost/users/1','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','company:1,User','2026-05-22 20:43:30','2026-05-22 20:43:30');
/*!40000 ALTER TABLE `audits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_code_unique` (`code`),
  KEY `branches_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `branches_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (1,NULL,'CIUDAD SATELITE','SAL1','876876678','AV DEL POLICIA',1,'2026-05-20 00:54:10','2026-05-20 00:54:10',NULL),(2,NULL,'VILLA FATIMA','2','23423','PLAZ VILLAROEL',1,'2026-05-20 00:54:34','2026-05-20 00:54:34',NULL),(3,1,'Cochabamba','1','7626727','av cochabmaba ciudad satelite',1,'2026-05-22 14:13:01','2026-05-22 14:13:01',NULL),(4,1,'Fatima','3','7763327','villa fatima',1,'2026-05-22 14:13:28','2026-05-22 14:13:28',NULL);
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('laravel-cache-spatie.permission.cache','a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:72:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:14:\"dashboard.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;i:6;i:7;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:15:\"categories.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;i:6;i:7;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:17:\"categories.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:17:\"categories.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:17:\"categories.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:13:\"products.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;i:6;i:7;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:15:\"products.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;i:4;i:7;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:15:\"products.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;i:4;i:7;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:15:\"products.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:14:\"purchases.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:6;i:3;i:7;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:10:\"sales.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:6;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:14:\"inventory.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;i:4;i:7;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:12:\"reports.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;i:4;i:6;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:12:\"users.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:12:\"roles.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:10:\"users.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:6;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:12:\"users.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:10:\"users.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:12:\"users.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:13:\"users.restore\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:21:\"users.change-password\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:18:\"users.assign-roles\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:22;a:4:{s:1:\"a\";i:23;s:1:\"b\";s:10:\"roles.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:23;a:4:{s:1:\"a\";i:24;s:1:\"b\";s:12:\"roles.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:24;a:4:{s:1:\"a\";i:25;s:1:\"b\";s:10:\"roles.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:25;a:4:{s:1:\"a\";i:26;s:1:\"b\";s:12:\"roles.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:26;a:4:{s:1:\"a\";i:27;s:1:\"b\";s:24:\"roles.assign-permissions\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:27;a:4:{s:1:\"a\";i:28;s:1:\"b\";s:16:\"permissions.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:28;a:4:{s:1:\"a\";i:29;s:1:\"b\";s:18:\"permissions.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:29;a:4:{s:1:\"a\";i:30;s:1:\"b\";s:16:\"permissions.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:30;a:4:{s:1:\"a\";i:31;s:1:\"b\";s:18:\"permissions.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:31;a:4:{s:1:\"a\";i:32;s:1:\"b\";s:13:\"products.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;i:4;i:7;}}i:32;a:4:{s:1:\"a\";i:33;s:1:\"b\";s:15:\"categories.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;}}i:33;a:4:{s:1:\"a\";i:34;s:1:\"b\";s:19:\"inventory.movements\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:7;}}i:34;a:4:{s:1:\"a\";i:35;s:1:\"b\";s:16:\"purchases.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:6;i:3;i:7;}}i:35;a:4:{s:1:\"a\";i:36;s:1:\"b\";s:12:\"sales.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:4;}}i:36;a:4:{s:1:\"a\";i:37;s:1:\"b\";s:10:\"pos.access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:4;}}i:37;a:4:{s:1:\"a\";i:38;s:1:\"b\";s:13:\"branches.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;i:6;i:7;}}i:38;a:4:{s:1:\"a\";i:39;s:1:\"b\";s:15:\"branches.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;}}i:39;a:4:{s:1:\"a\";i:40;s:1:\"b\";s:15:\"branches.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;}}i:40;a:4:{s:1:\"a\";i:41;s:1:\"b\";s:15:\"branches.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:41;a:4:{s:1:\"a\";i:42;s:1:\"b\";s:15:\"warehouses.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;i:6;i:7;}}i:42;a:4:{s:1:\"a\";i:43;s:1:\"b\";s:17:\"warehouses.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;i:4;i:7;}}i:43;a:4:{s:1:\"a\";i:44;s:1:\"b\";s:17:\"warehouses.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;i:4;i:7;}}i:44;a:4:{s:1:\"a\";i:45;s:1:\"b\";s:17:\"warehouses.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:45;a:4:{s:1:\"a\";i:46;s:1:\"b\";s:14:\"suppliers.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;i:4;i:7;}}i:46;a:4:{s:1:\"a\";i:47;s:1:\"b\";s:16:\"suppliers.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:47;a:4:{s:1:\"a\";i:48;s:1:\"b\";s:16:\"suppliers.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:48;a:4:{s:1:\"a\";i:49;s:1:\"b\";s:16:\"suppliers.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:49;a:4:{s:1:\"a\";i:50;s:1:\"b\";s:22:\"measurement-units.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;i:6;i:7;}}i:50;a:4:{s:1:\"a\";i:51;s:1:\"b\";s:24:\"measurement-units.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;}}i:51;a:4:{s:1:\"a\";i:52;s:1:\"b\";s:24:\"measurement-units.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;}}i:52;a:4:{s:1:\"a\";i:53;s:1:\"b\";s:24:\"measurement-units.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:53;a:4:{s:1:\"a\";i:54;s:1:\"b\";s:26:\"product-presentations.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:6;i:5;i:7;}}i:54;a:4:{s:1:\"a\";i:55;s:1:\"b\";s:28:\"product-presentations.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;i:4;i:7;}}i:55;a:4:{s:1:\"a\";i:56;s:1:\"b\";s:28:\"product-presentations.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;i:4;i:7;}}i:56;a:4:{s:1:\"a\";i:57;s:1:\"b\";s:28:\"product-presentations.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:57;a:4:{s:1:\"a\";i:58;s:1:\"b\";s:19:\"point-of-sales.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;i:6;i:7;}}i:58;a:4:{s:1:\"a\";i:59;s:1:\"b\";s:21:\"point-of-sales.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;i:4;i:7;}}i:59;a:4:{s:1:\"a\";i:60;s:1:\"b\";s:21:\"point-of-sales.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;i:4;i:7;}}i:60;a:4:{s:1:\"a\";i:61;s:1:\"b\";s:21:\"point-of-sales.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:61;a:4:{s:1:\"a\";i:62;s:1:\"b\";s:20:\"payment-methods.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:6;}}i:62;a:4:{s:1:\"a\";i:63;s:1:\"b\";s:22:\"payment-methods.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:6;}}i:63;a:4:{s:1:\"a\";i:64;s:1:\"b\";s:22:\"payment-methods.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:6;}}i:64;a:4:{s:1:\"a\";i:65;s:1:\"b\";s:22:\"payment-methods.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:6;}}i:65;a:4:{s:1:\"a\";i:66;s:1:\"b\";s:14:\"companies.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:66;a:4:{s:1:\"a\";i:67;s:1:\"b\";s:16:\"companies.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:67;a:4:{s:1:\"a\";i:68;s:1:\"b\";s:16:\"companies.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:68;a:4:{s:1:\"a\";i:69;s:1:\"b\";s:16:\"companies.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:69;a:4:{s:1:\"a\";i:70;s:1:\"b\";s:11:\"audits.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:70;a:4:{s:1:\"a\";i:71;s:1:\"b\";s:14:\"purchases.void\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:6;}}i:71;a:4:{s:1:\"a\";i:72;s:1:\"b\";s:10:\"sales.void\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:6;}}}s:5:\"roles\";a:7:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:11:\"super_admin\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:5:\"admin\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:17:\"inventory_manager\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:7:\"cashier\";s:1:\"c\";s:3:\"web\";}i:4;a:3:{s:1:\"a\";i:5;s:1:\"b\";s:6:\"viewer\";s:1:\"c\";s:3:\"web\";}i:5;a:3:{s:1:\"a\";i:6;s:1:\"b\";s:7:\"manager\";s:1:\"c\";s:3:\"web\";}i:6;a:3:{s:1:\"a\";i:7;s:1:\"b\";s:9:\"warehouse\";s:1:\"c\";s:3:\"web\";}}}',1779558744);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_register_expenses`
--

DROP TABLE IF EXISTS `cash_register_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_register_expenses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cash_register_id` bigint unsigned NOT NULL,
  `point_of_sale_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `responsible_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `detail` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `spent_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cash_register_expenses_user_id_foreign` (`user_id`),
  KEY `cash_register_expenses_cash_register_id_spent_at_index` (`cash_register_id`,`spent_at`),
  KEY `cash_register_expenses_point_of_sale_id_spent_at_index` (`point_of_sale_id`,`spent_at`),
  CONSTRAINT `cash_register_expenses_cash_register_id_foreign` FOREIGN KEY (`cash_register_id`) REFERENCES `cash_registers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `cash_register_expenses_point_of_sale_id_foreign` FOREIGN KEY (`point_of_sale_id`) REFERENCES `point_of_sales` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `cash_register_expenses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_register_expenses`
--

LOCK TABLES `cash_register_expenses` WRITE;
/*!40000 ALTER TABLE `cash_register_expenses` DISABLE KEYS */;
INSERT INTO `cash_register_expenses` VALUES (1,1,2,1,'Administradord','compra de bolsas',25.00,'2026-05-21 21:15:31','2026-05-21 21:15:31','2026-05-21 21:15:31');
/*!40000 ALTER TABLE `cash_register_expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_registers`
--

DROP TABLE IF EXISTS `cash_registers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_registers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `point_of_sale_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `opening_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `closing_amount` decimal(12,2) DEFAULT NULL,
  `opened_at` timestamp NOT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cash_registers_branch_id_foreign` (`branch_id`),
  KEY `cash_registers_point_status_index` (`point_of_sale_id`,`status`),
  KEY `cash_registers_user_status_index` (`user_id`,`status`),
  CONSTRAINT `cash_registers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `cash_registers_point_of_sale_id_foreign` FOREIGN KEY (`point_of_sale_id`) REFERENCES `point_of_sales` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `cash_registers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_registers`
--

LOCK TABLES `cash_registers` WRITE;
/*!40000 ALTER TABLE `cash_registers` DISABLE KEYS */;
INSERT INTO `cash_registers` VALUES (1,2,2,1,0.00,55.70,'2026-05-20 20:20:32','2026-05-21 21:36:36','closed','2026-05-20 20:20:32','2026-05-21 21:36:36'),(2,2,2,1,100.00,114.90,'2026-05-21 21:36:45','2026-05-22 15:34:22','closed','2026-05-21 21:36:45','2026-05-22 15:34:22'),(3,3,3,1,0.00,NULL,'2026-05-22 15:34:28',NULL,'open','2026-05-22 15:34:28','2026-05-22 15:34:28');
/*!40000 ALTER TABLE `cash_registers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_company_id_name_unique` (`company_id`,`name`),
  KEY `categories_company_id_index` (`company_id`),
  CONSTRAINT `categories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,NULL,'Alimentos','Productos alimenticios de consumo general.',1,'2026-05-19 20:19:39','2026-05-19 20:19:39',NULL),(2,NULL,'Bebidas','Bebidas frias, calientes y embotelladas.',1,'2026-05-19 20:19:39','2026-05-19 20:19:39',NULL),(3,NULL,'Limpieza','Articulos de limpieza y cuidado del hogar.',1,'2026-05-19 20:19:39','2026-05-19 20:19:39',NULL),(4,NULL,'Tecnologia','Equipos, accesorios y consumibles tecnologicos.',1,'2026-05-19 20:19:39','2026-05-19 20:19:39',NULL),(5,NULL,'General','Categoria general para productos iniciales.',1,'2026-05-19 20:19:39','2026-05-19 20:19:39',NULL),(6,NULL,'Categoria AJAX 1779223737','Creada via prueba AJAX',1,'2026-05-19 20:48:57','2026-05-19 20:49:13','2026-05-19 20:49:13'),(7,NULL,'Abarrotes','Categoria de supermercado.',1,'2026-05-20 01:24:27','2026-05-20 01:24:27',NULL),(8,NULL,'Lacteos','Categoria de supermercado.',1,'2026-05-20 01:24:27','2026-05-20 01:24:27',NULL),(9,NULL,'Cuidado personal','Categoria de supermercado.',1,'2026-05-20 01:24:27','2026-05-20 01:24:27',NULL),(10,NULL,'Carnes y embutidos','Categoria de supermercado.',1,'2026-05-20 01:24:27','2026-05-20 01:24:27',NULL),(11,NULL,'Frutas y verduras','Categoria de supermercado.',1,'2026-05-20 01:24:27','2026-05-20 01:24:27',NULL),(12,NULL,'Panaderia','Categoria de supermercado.',1,'2026-05-20 01:24:27','2026-05-20 01:24:27',NULL),(13,NULL,'Congelados','Categoria de supermercado.',1,'2026-05-20 01:24:27','2026-05-20 01:24:27',NULL),(14,NULL,'Mascotas','Categoria de supermercado.',1,'2026-05-20 01:24:27','2026-05-20 01:24:27',NULL),(15,NULL,'Bebes','Categoria de supermercado.',1,'2026-05-20 01:24:27','2026-05-20 01:24:27',NULL),(16,NULL,'Snacks','Categoria de supermercado.',1,'2026-05-20 01:24:27','2026-05-20 01:24:27',NULL),(17,1,'Comida',NULL,1,'2026-05-22 14:01:34','2026-05-22 14:02:12',NULL),(18,1,'Bebidas',NULL,1,'2026-05-22 14:01:44','2026-05-22 14:01:44',NULL),(19,1,'Postres',NULL,1,'2026-05-22 14:01:51','2026-05-22 14:01:51',NULL);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `legal_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `report_footer` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (1,'TOY BURGER','MACARIO MAMANI','87832874237','8377547578','LATRO@EFSJKFSD.COM','AV COCHABAMABA','EL ALTO','Bolivia','companies/logos/mU9jNI5nWAWAmPBa7JYSrFgWhLVlWP3zbG4KfBKY.png','LA PAZ BOLIVIA',1,'2026-05-21 22:21:57','2026-05-21 22:21:57'),(2,'Tienda de Juguetes','JYA','9128128','3234','algo@afsf.com','villa fatima','la paz','Bolivia','companies/logos/z2QFJuJGipzGdAr3eqaxVB5Nxbe2SPl3yKv7TZhl.jpg',NULL,1,'2026-05-22 14:18:11','2026-05-22 14:18:11');
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customers_document_number_index` (`document_number`),
  KEY `customers_company_id_index` (`company_id`),
  CONSTRAINT `customers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,NULL,'pacheco alvaro','8324984',NULL,NULL,NULL,1,'2026-05-21 13:24:23','2026-05-21 14:07:39',NULL),(2,NULL,'Danilo Moreno','832498',NULL,NULL,NULL,1,'2026-05-21 14:15:19','2026-05-21 14:15:19',NULL);
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_movements`
--

DROP TABLE IF EXISTS `inventory_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_movements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `presentation_id` bigint unsigned DEFAULT NULL,
  `presentation_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `package_quantity` int DEFAULT NULL,
  `units_per_package` int unsigned NOT NULL DEFAULT '1',
  `reference_id` bigint unsigned DEFAULT NULL,
  `reference_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_movements_warehouse_id_foreign` (`warehouse_id`),
  KEY `inventory_movements_user_id_foreign` (`user_id`),
  KEY `inventory_movements_product_id_warehouse_id_type_index` (`product_id`,`warehouse_id`,`type`),
  KEY `inventory_movements_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `inventory_product_warehouse_presentation_index` (`product_id`,`warehouse_id`),
  KEY `inventory_movements_presentation_id_foreign` (`presentation_id`),
  KEY `inventory_product_warehouse_presentation_universal_index` (`product_id`,`warehouse_id`,`presentation_id`),
  CONSTRAINT `inventory_movements_presentation_id_foreign` FOREIGN KEY (`presentation_id`) REFERENCES `presentations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `inventory_movements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_movements_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_movements`
--

LOCK TABLES `inventory_movements` WRITE;
/*!40000 ALTER TABLE `inventory_movements` DISABLE KEYS */;
INSERT INTO `inventory_movements` VALUES (1,2,NULL,NULL,2,1,'adjustment_in',6,NULL,1,NULL,'manual_adjustment',NULL,'2026-05-20 01:03:23','2026-05-20 01:03:23'),(2,1,NULL,NULL,2,1,'adjustment_in',9,NULL,1,NULL,'manual_adjustment',NULL,'2026-05-20 01:03:23','2026-05-20 01:03:23'),(3,1,NULL,NULL,1,1,'adjustment_in',1,NULL,1,NULL,'manual_adjustment',NULL,'2026-05-20 01:10:54','2026-05-20 01:10:54'),(4,2,NULL,NULL,1,1,'adjustment_in',1,NULL,1,NULL,'manual_adjustment',NULL,'2026-05-20 01:10:54','2026-05-20 01:10:54'),(5,2,NULL,NULL,1,1,'adjustment_in',2,NULL,1,NULL,'manual_adjustment',NULL,'2026-05-20 01:12:04','2026-05-20 01:12:04'),(6,1,NULL,NULL,1,1,'adjustment_in',3,NULL,1,NULL,'manual_adjustment',NULL,'2026-05-20 01:12:04','2026-05-20 01:12:04'),(7,2,NULL,NULL,2,1,'transfer_out',-4,NULL,1,260520011841816,'warehouse_transfer',NULL,'2026-05-20 01:18:41','2026-05-20 01:18:41'),(8,2,NULL,NULL,1,1,'transfer_in',4,NULL,1,260520011841816,'warehouse_transfer',NULL,'2026-05-20 01:18:41','2026-05-20 01:18:41'),(9,103,5,'Paquete x 12',1,1,'purchase',24,2,12,1,'purchase',NULL,'2026-05-20 15:20:29','2026-05-20 15:20:29'),(10,6,1,'Unidad',1,1,'purchase',6,6,1,2,'purchase',NULL,'2026-05-20 15:24:43','2026-05-20 15:24:43'),(11,6,2,'Caja x 10',1,1,'purchase',20,2,10,2,'purchase',NULL,'2026-05-20 15:24:43','2026-05-20 15:24:43'),(12,86,1,'Unidad',2,1,'purchase',100,100,1,3,'purchase',NULL,'2026-05-20 19:29:48','2026-05-20 19:29:48'),(13,6,1,'Unidad',2,1,'purchase',200,200,1,4,'purchase',NULL,'2026-05-20 20:37:11','2026-05-20 20:37:11'),(14,6,2,'Caja x 10',2,1,'purchase',100,10,10,4,'purchase',NULL,'2026-05-20 20:37:11','2026-05-20 20:37:11'),(15,6,1,'Unidad',2,1,'sale',-5,-5,1,1,'sale',NULL,'2026-05-20 20:41:30','2026-05-20 20:41:30'),(16,6,2,'Caja x 10',2,1,'sale',-10,-1,10,1,'sale',NULL,'2026-05-20 20:41:30','2026-05-20 20:41:30'),(17,6,2,'Caja x 10',2,1,'sale',-50,-5,10,2,'sale',NULL,'2026-05-20 20:56:24','2026-05-20 20:56:24'),(18,6,1,'Unidad',2,1,'sale',-195,-195,1,3,'sale',NULL,'2026-05-21 02:11:53','2026-05-21 02:11:53'),(19,6,2,'Caja x 10',2,1,'defragment_out',-20,-2,10,260521124320380,'stock_defragmentation','para vender por raleo | Desfragmentacion controlada: 2 Caja x 10 de Aceite vegetal 900 ml en TIANDA.','2026-05-21 12:43:20','2026-05-21 12:43:20'),(20,6,1,'Unidad',2,1,'defragment_in',20,20,1,260521124320380,'stock_defragmentation','para vender por raleo | Desfragmentacion controlada: 2 Caja x 10 de Aceite vegetal 900 ml en TIANDA.','2026-05-21 12:43:20','2026-05-21 12:43:20'),(21,6,1,'Unidad',2,1,'sale',-1,-1,1,4,'sale',NULL,'2026-05-21 12:44:31','2026-05-21 12:44:31'),(22,6,1,'Unidad',2,1,'sale',-1,-1,1,5,'sale',NULL,'2026-05-21 13:24:04','2026-05-21 13:24:04'),(23,6,1,'Unidad',2,1,'sale',-1,-1,1,6,'sale',NULL,'2026-05-21 13:24:23','2026-05-21 13:24:23'),(24,6,1,'Unidad',2,1,'sale',-1,-1,1,7,'sale',NULL,'2026-05-21 14:07:39','2026-05-21 14:07:39'),(25,6,1,'Unidad',2,1,'sale',-1,-1,1,8,'sale',NULL,'2026-05-21 14:08:53','2026-05-21 14:08:53'),(26,6,1,'Unidad',2,1,'sale',-1,-1,1,9,'sale',NULL,'2026-05-21 14:13:36','2026-05-21 14:13:36'),(27,6,1,'Unidad',2,1,'sale',-1,-1,1,10,'sale',NULL,'2026-05-21 14:15:19','2026-05-21 14:15:19'),(28,6,1,'Unidad',2,1,'sale',-1,-1,1,11,'sale',NULL,'2026-05-21 14:28:25','2026-05-21 14:28:25'),(29,6,1,'Unidad',2,1,'sale',-1,-1,1,12,'sale',NULL,'2026-05-21 14:28:38','2026-05-21 14:28:38'),(30,6,1,'Unidad',2,1,'sale',-1,-1,1,13,'sale',NULL,'2026-05-21 14:29:04','2026-05-21 14:29:04'),(31,6,1,'Unidad',2,1,'sale',-1,-1,1,14,'sale',NULL,'2026-05-21 21:37:04','2026-05-21 21:37:04'),(32,32,4,'Paquete x 6',2,1,'purchase',6,1,6,5,'purchase',NULL,'2026-05-21 21:59:44','2026-05-21 21:59:44'),(33,109,6,'Unidad',4,1,'purchase',6,6,1,6,'purchase',NULL,'2026-05-22 15:26:37','2026-05-22 15:26:37'),(34,109,6,'Unidad',4,1,'transfer_out',-1,-1,1,260522153407373,'warehouse_transfer',NULL,'2026-05-22 15:34:07','2026-05-22 15:34:07'),(35,109,6,'Unidad',5,1,'transfer_in',1,1,1,260522153407373,'warehouse_transfer',NULL,'2026-05-22 15:34:07','2026-05-22 15:34:07'),(36,109,6,'Unidad',4,1,'sale',-1,-1,1,15,'sale',NULL,'2026-05-22 15:34:44','2026-05-22 15:34:44'),(37,109,6,'Unidad',4,1,'transfer_out',-1,-1,1,260522155754548,'warehouse_transfer',NULL,'2026-05-22 15:57:54','2026-05-22 15:57:54'),(38,109,6,'Unidad',5,1,'transfer_in',1,1,1,260522155754548,'warehouse_transfer',NULL,'2026-05-22 15:57:54','2026-05-22 15:57:54'),(39,109,6,'Unidad',4,1,'transfer_out',-2,-2,1,260522160308484,'warehouse_transfer',NULL,'2026-05-22 16:03:08','2026-05-22 16:03:08'),(40,109,6,'Unidad',5,1,'transfer_in',2,2,1,260522160308484,'warehouse_transfer',NULL,'2026-05-22 16:03:08','2026-05-22 16:03:08'),(41,109,6,'Unidad',4,1,'sale',-1,-1,1,16,'sale',NULL,'2026-05-22 16:03:49','2026-05-22 16:03:49'),(42,108,6,'Unidad',5,1,'purchase',100,100,1,7,'purchase',NULL,'2026-05-22 16:51:26','2026-05-22 16:51:26'),(43,108,6,'Unidad',5,1,'adjustment_out',-2,-2,1,NULL,'stock_recount','Motivo: Perdida. Reajuste de stock: conteo fisico 98 frente a sistema 100.','2026-05-22 16:53:36','2026-05-22 16:53:36'),(44,108,7,'paquete de 6',5,1,'purchase',12,2,6,8,'purchase',NULL,'2026-05-22 16:55:12','2026-05-22 16:55:12'),(45,108,6,'Unidad',5,1,'transfer_out',-12,-12,1,260522170004578,'warehouse_transfer',NULL,'2026-05-22 17:00:04','2026-05-22 17:00:04'),(46,108,6,'Unidad',4,1,'transfer_in',12,12,1,260522170004578,'warehouse_transfer',NULL,'2026-05-22 17:00:04','2026-05-22 17:00:04'),(47,108,7,'paquete de 6',5,1,'transfer_out',-6,-1,6,260522170110150,'warehouse_transfer',NULL,'2026-05-22 17:01:10','2026-05-22 17:01:10'),(48,108,7,'paquete de 6',4,1,'transfer_in',6,1,6,260522170110150,'warehouse_transfer',NULL,'2026-05-22 17:01:10','2026-05-22 17:01:10'),(49,108,7,'paquete de 6',4,1,'adjustment_in',6,1,6,NULL,'stock_recount','Motivo: Conteo fisico. Reajuste de stock: conteo fisico 2 frente a sistema 1.','2026-05-22 17:01:42','2026-05-22 17:01:42'),(50,108,7,'paquete de 6',4,1,'adjustment_in',6,1,6,NULL,'stock_recount','Motivo: Conteo fisico. Reajuste de stock: conteo fisico 3 frente a sistema 2.','2026-05-22 17:02:07','2026-05-22 17:02:07'),(51,109,6,'Unidad',4,1,'adjustment_in',1,1,1,16,'sale_void','Anulacion de venta 3-4-000001-000002. Motivo: pruebas sistema','2026-05-22 17:56:19','2026-05-22 17:56:19');
/*!40000 ALTER TABLE `inventory_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `measurement_units`
--

DROP TABLE IF EXISTS `measurement_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `measurement_units` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abbreviation` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `measurement_units_company_id_name_unique` (`company_id`,`name`),
  UNIQUE KEY `measurement_units_company_id_abbreviation_unique` (`company_id`,`abbreviation`),
  KEY `measurement_units_company_id_index` (`company_id`),
  CONSTRAINT `measurement_units_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `measurement_units`
--

LOCK TABLES `measurement_units` WRITE;
/*!40000 ALTER TABLE `measurement_units` DISABLE KEYS */;
INSERT INTO `measurement_units` VALUES (1,NULL,'Unidad','un',1,'2026-05-20 14:18:37','2026-05-20 14:21:10',NULL),(2,NULL,'Caja','cja',1,'2026-05-20 14:19:42','2026-05-20 14:19:42',NULL),(3,NULL,'Paquete','paq',1,'2026-05-20 14:19:42','2026-05-20 14:19:42',NULL),(4,NULL,'Kilogramo','kg',1,'2026-05-20 14:19:42','2026-05-20 14:19:42',NULL),(5,NULL,'Gramo','g',1,'2026-05-20 14:19:42','2026-05-20 14:19:42',NULL),(6,NULL,'Litro','l',1,'2026-05-20 14:19:42','2026-05-20 14:19:42',NULL),(7,NULL,'Metro','m',1,'2026-05-20 14:19:42','2026-05-20 14:19:42',NULL),(8,1,'Unidad','Un',1,'2026-05-22 14:01:02','2026-05-22 14:01:02',NULL);
/*!40000 ALTER TABLE `measurement_units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `media`
--

DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disk` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conversions_disk` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint unsigned NOT NULL,
  `manipulations` json NOT NULL,
  `custom_properties` json NOT NULL,
  `generated_conversions` json NOT NULL,
  `responsive_images` json NOT NULL,
  `order_column` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_uuid_unique` (`uuid`),
  KEY `media_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `media_order_column_index` (`order_column`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `media`
--

LOCK TABLES `media` WRITE;
/*!40000 ALTER TABLE `media` DISABLE KEYS */;
INSERT INTO `media` VALUES (3,'App\\Models\\Product',105,'b7a5c64a-ea62-4564-bf7a-76ec7a9ce6c9','images','Pollo a la Broaster','pollo.jpg','image/jpeg','public','public',5354,'[]','[]','{\"optimized\": true}','[]',1,'2026-05-22 14:04:28','2026-05-22 14:04:29'),(4,'App\\Models\\Product',106,'1fb3a374-ff35-4e44-842f-38d31cf720ef','images','Silpancho Especial','silpancho.jpg','image/jpeg','public','public',6680,'[]','[]','{\"optimized\": true}','[]',1,'2026-05-22 14:05:15','2026-05-22 14:05:15'),(5,'App\\Models\\Product',107,'f7a58509-5d49-4be8-aaab-b23c3ae54d0c','images','Milanesa de Pollo','milaneza.jpg','image/jpeg','public','public',6738,'[]','[]','{\"optimized\": true}','[]',1,'2026-05-22 14:06:10','2026-05-22 14:06:11'),(6,'App\\Models\\Product',108,'81a2f92a-dcfc-4595-8d11-e92569e21eda','images','Coca Cola 2 Litros','coca-2-l.jpg','image/jpeg','public','public',6217,'[]','[]','{\"optimized\": true}','[]',1,'2026-05-22 14:08:56','2026-05-22 14:08:56'),(7,'App\\Models\\Product',109,'f1fc1371-b931-4911-b939-16528d3a4e0c','images','Coca Cola 1 1/2 l','coca-1-12.jpg','image/jpeg','public','public',7260,'[]','[]','{\"optimized\": true}','[]',1,'2026-05-22 14:09:22','2026-05-22 14:09:22'),(8,'App\\Models\\Product',110,'5f85bc85-02a4-48c6-8f91-afbd89892f73','images','Coca cola 500 ml','coca-500ml.jpg','image/jpeg','public','public',4666,'[]','[]','{\"optimized\": true}','[]',1,'2026-05-22 14:09:41','2026-05-22 14:09:41'),(9,'App\\Models\\Product',111,'259ae179-c69e-4722-a5dc-179b35dc1701','images','Jugo del Valle 2l','valle-2l.jpg','image/jpeg','public','public',3280,'[]','[]','{\"optimized\": true}','[]',1,'2026-05-22 14:10:04','2026-05-22 14:10:04');
/*!40000 ALTER TABLE `media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_05_19_201139_create_personal_access_tokens_table',2),(5,'2026_05_19_201140_create_permission_tables',2),(6,'2026_05_19_201200_create_categories_table',2),(7,'2026_05_19_201210_create_products_table',2),(8,'2026_05_19_201220_create_inventory_foundation_tables',2),(9,'2026_05_19_201300_add_status_and_soft_deletes_to_users_table',3),(10,'2026_05_19_201400_add_branch_and_warehouse_permissions',4),(11,'2026_05_19_201500_add_supplier_permissions',5),(12,'2026_05_20_091200_rename_supplier_document_number_to_company_name',6),(13,'2026_05_20_092000_create_measurement_units_table',7),(14,'2026_05_20_092100_add_measurement_unit_id_to_products_table',7),(15,'2026_05_20_092200_add_measurement_unit_permissions',7),(16,'2026_05_20_093000_create_product_presentations_table',8),(17,'2026_05_20_093100_add_presentation_snapshot_to_inventory_movements_table',8),(18,'2026_05_20_093200_add_product_presentation_permissions',8),(19,'2026_05_20_094000_create_universal_presentations_table',9),(20,'2026_05_20_094100_swap_inventory_movements_to_universal_presentations',9),(21,'2026_05_20_095000_enhance_purchases_for_presentations',10),(22,'2026_05_20_100000_create_point_of_sales_table',11),(23,'2026_05_20_100100_require_warehouse_and_autocode_for_point_of_sales',12),(24,'2026_05_20_100200_create_point_of_sale_user_table',13),(25,'2026_05_20_100300_link_cash_registers_to_point_of_sales',14),(26,'2026_05_20_100400_enhance_sales_for_pos_presentations',15),(27,'2026_05_20_100500_add_image_path_to_products_table',16),(28,'2026_05_21_020009_create_media_table',17),(29,'2026_05_21_120000_add_customer_snapshot_to_sales_table',18),(30,'2026_05_21_130000_create_sale_payments_table',19),(31,'2026_05_21_130100_add_payment_method_permissions',19),(32,'2026_05_21_140000_add_cash_received_to_sale_payments_table',20),(33,'2026_05_21_150000_create_cash_register_expenses_table',21),(34,'2026_05_21_160000_create_companies_and_link_users',22),(35,'2026_05_21_170000_add_company_to_operation_locations',23),(36,'2026_05_21_180000_add_company_to_catalogs',24),(37,'2026_05_22_171224_create_audits_table',25),(38,'2026_05_22_171225_add_company_id_to_audits_table',25),(39,'2026_05_22_171226_add_audit_permission',25),(40,'2026_05_22_171227_add_purchase_void_permission',26),(41,'2026_05_22_171228_add_sale_void_permission',27);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1),(5,'App\\Models\\User',2),(2,'App\\Models\\User',3);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_methods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_methods_company_id_name_unique` (`company_id`,`name`),
  KEY `payment_methods_company_id_index` (`company_id`),
  CONSTRAINT `payment_methods_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_methods`
--

LOCK TABLES `payment_methods` WRITE;
/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
INSERT INTO `payment_methods` VALUES (1,NULL,'Efectivo',1,'2026-05-21 13:43:22','2026-05-21 13:43:22'),(2,NULL,'QR',1,'2026-05-21 13:43:22','2026-05-21 13:43:22'),(3,NULL,'Transferencia bancaria',0,'2026-05-21 13:43:22','2026-05-21 14:31:55'),(4,NULL,'Tarjeta',1,'2026-05-21 13:43:22','2026-05-21 13:43:22'),(5,1,'Efectivo',1,'2026-05-22 14:01:14','2026-05-22 14:01:14'),(6,1,'QR',1,'2026-05-22 14:01:19','2026-05-22 14:01:19');
/*!40000 ALTER TABLE `payment_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'dashboard.view','web','2026-05-19 20:19:39','2026-05-19 20:19:39'),(2,'categories.view','web','2026-05-19 20:19:39','2026-05-19 20:19:39'),(3,'categories.create','web','2026-05-19 20:19:39','2026-05-19 20:19:39'),(4,'categories.update','web','2026-05-19 20:19:39','2026-05-19 20:19:39'),(5,'categories.delete','web','2026-05-19 20:19:39','2026-05-19 20:19:39'),(6,'products.view','web','2026-05-19 20:19:39','2026-05-19 20:19:39'),(7,'products.create','web','2026-05-19 20:19:39','2026-05-19 20:19:39'),(8,'products.update','web','2026-05-19 20:19:39','2026-05-19 20:19:39'),(9,'products.delete','web','2026-05-19 20:19:39','2026-05-19 20:19:39'),(10,'purchases.view','web','2026-05-19 20:19:39','2026-05-19 20:19:39'),(11,'sales.view','web','2026-05-19 20:19:39','2026-05-19 20:19:39'),(12,'inventory.view','web','2026-05-19 20:19:39','2026-05-19 20:19:39'),(13,'reports.view','web','2026-05-19 20:19:39','2026-05-19 20:19:39'),(14,'users.manage','web','2026-05-19 20:19:39','2026-05-19 20:19:39'),(15,'roles.manage','web','2026-05-19 20:19:39','2026-05-19 20:19:39'),(16,'users.view','web','2026-05-19 20:59:37','2026-05-19 20:59:37'),(17,'users.create','web','2026-05-19 20:59:37','2026-05-19 20:59:37'),(18,'users.edit','web','2026-05-19 20:59:37','2026-05-19 20:59:37'),(19,'users.delete','web','2026-05-19 20:59:37','2026-05-19 20:59:37'),(20,'users.restore','web','2026-05-19 20:59:37','2026-05-19 20:59:37'),(21,'users.change-password','web','2026-05-19 20:59:37','2026-05-19 20:59:37'),(22,'users.assign-roles','web','2026-05-19 20:59:37','2026-05-19 20:59:37'),(23,'roles.view','web','2026-05-19 21:11:51','2026-05-19 21:11:51'),(24,'roles.create','web','2026-05-19 21:11:51','2026-05-19 21:11:51'),(25,'roles.edit','web','2026-05-19 21:11:51','2026-05-19 21:11:51'),(26,'roles.delete','web','2026-05-19 21:11:51','2026-05-19 21:11:51'),(27,'roles.assign-permissions','web','2026-05-19 21:11:51','2026-05-19 21:11:51'),(28,'permissions.view','web','2026-05-19 21:11:51','2026-05-19 21:11:51'),(29,'permissions.create','web','2026-05-19 21:11:51','2026-05-19 21:11:51'),(30,'permissions.edit','web','2026-05-19 21:11:51','2026-05-19 21:11:51'),(31,'permissions.delete','web','2026-05-19 21:11:51','2026-05-19 21:11:51'),(32,'products.edit','web','2026-05-19 21:11:51','2026-05-19 21:11:51'),(33,'categories.edit','web','2026-05-19 21:11:51','2026-05-19 21:11:51'),(34,'inventory.movements','web','2026-05-19 21:11:51','2026-05-19 21:11:51'),(35,'purchases.create','web','2026-05-19 21:11:52','2026-05-19 21:11:52'),(36,'sales.create','web','2026-05-19 21:11:52','2026-05-19 21:11:52'),(37,'pos.access','web','2026-05-19 21:11:52','2026-05-19 21:11:52'),(38,'branches.view','web','2026-05-20 00:52:23','2026-05-20 00:52:23'),(39,'branches.create','web','2026-05-20 00:52:23','2026-05-20 00:52:23'),(40,'branches.update','web','2026-05-20 00:52:23','2026-05-20 00:52:23'),(41,'branches.delete','web','2026-05-20 00:52:24','2026-05-20 00:52:24'),(42,'warehouses.view','web','2026-05-20 00:52:24','2026-05-20 00:52:24'),(43,'warehouses.create','web','2026-05-20 00:52:24','2026-05-20 00:52:24'),(44,'warehouses.update','web','2026-05-20 00:52:24','2026-05-20 00:52:24'),(45,'warehouses.delete','web','2026-05-20 00:52:24','2026-05-20 00:52:24'),(46,'suppliers.view','web','2026-05-20 01:38:23','2026-05-20 01:38:23'),(47,'suppliers.create','web','2026-05-20 01:38:24','2026-05-20 01:38:24'),(48,'suppliers.update','web','2026-05-20 01:38:24','2026-05-20 01:38:24'),(49,'suppliers.delete','web','2026-05-20 01:38:24','2026-05-20 01:38:24'),(50,'measurement-units.view','web','2026-05-20 14:18:38','2026-05-20 14:18:38'),(51,'measurement-units.create','web','2026-05-20 14:18:38','2026-05-20 14:18:38'),(52,'measurement-units.update','web','2026-05-20 14:18:38','2026-05-20 14:18:38'),(53,'measurement-units.delete','web','2026-05-20 14:18:38','2026-05-20 14:18:38'),(54,'product-presentations.view','web','2026-05-20 14:43:52','2026-05-20 14:43:52'),(55,'product-presentations.create','web','2026-05-20 14:43:53','2026-05-20 14:43:53'),(56,'product-presentations.update','web','2026-05-20 14:43:53','2026-05-20 14:43:53'),(57,'product-presentations.delete','web','2026-05-20 14:43:53','2026-05-20 14:43:53'),(58,'point-of-sales.view','web','2026-05-20 19:39:45','2026-05-20 19:39:45'),(59,'point-of-sales.create','web','2026-05-20 19:39:45','2026-05-20 19:39:45'),(60,'point-of-sales.update','web','2026-05-20 19:39:45','2026-05-20 19:39:45'),(61,'point-of-sales.delete','web','2026-05-20 19:39:45','2026-05-20 19:39:45'),(62,'payment-methods.view','web','2026-05-21 13:43:22','2026-05-21 13:43:22'),(63,'payment-methods.create','web','2026-05-21 13:43:23','2026-05-21 13:43:23'),(64,'payment-methods.update','web','2026-05-21 13:43:23','2026-05-21 13:43:23'),(65,'payment-methods.delete','web','2026-05-21 13:43:23','2026-05-21 13:43:23'),(66,'companies.view','web','2026-05-21 22:16:51','2026-05-21 22:16:51'),(67,'companies.create','web','2026-05-21 22:16:51','2026-05-21 22:16:51'),(68,'companies.update','web','2026-05-21 22:16:51','2026-05-21 22:16:51'),(69,'companies.delete','web','2026-05-21 22:16:52','2026-05-21 22:16:52'),(70,'audits.view','web','2026-05-22 17:29:25','2026-05-22 17:29:25'),(71,'purchases.void','web','2026-05-22 17:36:50','2026-05-22 17:36:50'),(72,'sales.void','web','2026-05-22 17:50:14','2026-05-22 17:50:14');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\User',1,'api-token','0d1eaa6f27c9928663af9999efbe9f84d7a048cbbb792ed4f63ed44b46ff5073','[\"*\"]',NULL,NULL,'2026-05-19 20:21:15','2026-05-19 20:21:15'),(2,'App\\Models\\User',1,'api-token','90451b3c94b7ceb7a9212c17c0b31017a09c5db4f13e8c276f8d5256e7089739','[\"*\"]','2026-05-19 20:21:20',NULL,'2026-05-19 20:21:20','2026-05-19 20:21:20'),(3,'App\\Models\\User',1,'api-token','1bad8b83b79427970f6a8ba9dcde76a026b47fbed1f5b8582cd2f8d4d56bfa59','[\"*\"]','2026-05-19 21:01:50',NULL,'2026-05-19 21:01:50','2026-05-19 21:01:50'),(4,'App\\Models\\User',1,'api-token','956537919eb0f8509d0c391c4f9cd2eef242a3ed44ad9e2d8a64b18230acfef0','[\"*\"]','2026-05-19 21:13:09',NULL,'2026-05-19 21:13:08','2026-05-19 21:13:09'),(5,'App\\Models\\User',1,'api-token','9a618428fbcecded273c0eb4bcf14843136b98b4fbc6be570a1021ee6e13376a','[\"*\"]','2026-05-19 21:13:36',NULL,'2026-05-19 21:13:36','2026-05-19 21:13:36'),(6,'App\\Models\\User',1,'api-token','8429a717094595c92b20ed46f3f76b38d9c406b38ed3a0362369f511e851630c','[\"*\"]','2026-05-19 21:14:01',NULL,'2026-05-19 21:14:01','2026-05-19 21:14:01'),(7,'App\\Models\\User',1,'api-token','28e86bb2aacdc6f090bc0335b96069e2296f71d811681511f6a77afd7a9f7df7','[\"*\"]','2026-05-19 21:14:10',NULL,'2026-05-19 21:14:10','2026-05-19 21:14:10'),(8,'App\\Models\\User',1,'api-token','c90b52df476a8ffa74be32c5b12b03a631e437756fd235169495149ea3fb55ec','[\"*\"]','2026-05-19 21:15:06',NULL,'2026-05-19 21:15:06','2026-05-19 21:15:06'),(9,'App\\Models\\User',1,'api-token','2f65358546e820752022007d4616f4e44da758fd5c20bb66d4e5b0e0f78e5ade','[\"*\"]','2026-05-19 21:15:48',NULL,'2026-05-19 21:15:48','2026-05-19 21:15:48'),(10,'App\\Models\\User',1,'api-token','363097114285362521c202474838b5c23772dc50f08e9e73f6ed4466441f839d','[\"*\"]','2026-05-19 21:16:21',NULL,'2026-05-19 21:16:20','2026-05-19 21:16:21');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `point_of_sale_user`
--

DROP TABLE IF EXISTS `point_of_sale_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `point_of_sale_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `point_of_sale_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `point_of_sale_user_unique` (`point_of_sale_id`,`user_id`),
  KEY `point_of_sale_user_user_id_foreign` (`user_id`),
  CONSTRAINT `point_of_sale_user_point_of_sale_id_foreign` FOREIGN KEY (`point_of_sale_id`) REFERENCES `point_of_sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `point_of_sale_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `point_of_sale_user`
--

LOCK TABLES `point_of_sale_user` WRITE;
/*!40000 ALTER TABLE `point_of_sale_user` DISABLE KEYS */;
INSERT INTO `point_of_sale_user` VALUES (3,2,1,'2026-05-20 20:13:58','2026-05-20 20:13:58'),(4,3,1,'2026-05-22 14:14:53','2026-05-22 14:14:53');
/*!40000 ALTER TABLE `point_of_sale_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `point_of_sales`
--

DROP TABLE IF EXISTS `point_of_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `point_of_sales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sequence_number` int unsigned NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `point_of_sales_code_unique` (`code`),
  UNIQUE KEY `point_of_sales_warehouse_unique` (`warehouse_id`),
  KEY `point_of_sales_branch_id_foreign` (`branch_id`),
  KEY `point_of_sales_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `point_of_sales_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `point_of_sales_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `point_of_sales_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `point_of_sales`
--

LOCK TABLES `point_of_sales` WRITE;
/*!40000 ALTER TABLE `point_of_sales` DISABLE KEYS */;
INSERT INTO `point_of_sales` VALUES (1,NULL,1,1,'PUNTO VENTA SATELITE','1-1-000001',1,1,'2026-05-20 20:01:29','2026-05-20 20:01:29',NULL),(2,NULL,2,2,'TIENDA FATIMA','2-2-000001',1,1,'2026-05-20 20:13:58','2026-05-20 20:13:58',NULL),(3,1,3,4,'POS','3-4-000001',1,1,'2026-05-22 14:14:53','2026-05-22 14:14:53',NULL);
/*!40000 ALTER TABLE `point_of_sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `presentations`
--

DROP TABLE IF EXISTS `presentations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `presentations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `units_per_package` int unsigned NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `presentations_company_id_name_unique` (`company_id`,`name`),
  KEY `presentations_company_id_index` (`company_id`),
  CONSTRAINT `presentations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `presentations`
--

LOCK TABLES `presentations` WRITE;
/*!40000 ALTER TABLE `presentations` DISABLE KEYS */;
INSERT INTO `presentations` VALUES (1,NULL,'Unidad',1,1,'2026-05-20 15:03:32','2026-05-20 15:03:32',NULL),(2,NULL,'Caja x 10',10,1,'2026-05-20 15:03:32','2026-05-20 15:03:32',NULL),(3,NULL,'Caja x 20',20,1,'2026-05-20 15:03:32','2026-05-20 15:03:32',NULL),(4,NULL,'Paquete x 6',6,1,'2026-05-20 15:03:32','2026-05-20 15:03:32',NULL),(5,NULL,'Paquete x 12',12,1,'2026-05-20 15:03:33','2026-05-20 15:03:33',NULL),(6,1,'Unidad',1,1,'2026-05-22 15:26:10','2026-05-22 15:26:10',NULL),(7,1,'paquete de 6',6,1,'2026-05-22 16:52:33','2026-05-22 16:52:33',NULL);
/*!40000 ALTER TABLE `presentations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_presentations`
--

DROP TABLE IF EXISTS `product_presentations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_presentations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `units_per_package` int unsigned NOT NULL DEFAULT '1',
  `barcode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_price` decimal(12,2) DEFAULT NULL,
  `sale_price` decimal(12,2) DEFAULT NULL,
  `is_default_purchase` tinyint(1) NOT NULL DEFAULT '0',
  `is_default_sale` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_presentations_product_id_name_unique` (`product_id`,`name`),
  UNIQUE KEY `product_presentations_barcode_unique` (`barcode`),
  KEY `product_presentations_product_id_is_active_index` (`product_id`,`is_active`),
  CONSTRAINT `product_presentations_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_presentations`
--

LOCK TABLES `product_presentations` WRITE;
/*!40000 ALTER TABLE `product_presentations` DISABLE KEYS */;
INSERT INTO `product_presentations` VALUES (1,43,'Caja de 10',10,NULL,NULL,NULL,1,1,1,'2026-05-20 14:47:02','2026-05-20 14:56:05','2026-05-20 14:56:05');
/*!40000 ALTER TABLE `product_presentations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `barcode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` bigint unsigned NOT NULL,
  `measurement_unit_id` bigint unsigned NOT NULL DEFAULT '1',
  `description` text COLLATE utf8mb4_unicode_ci,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `sale_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `minimum_stock` int unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_company_id_barcode_unique` (`company_id`,`barcode`),
  KEY `products_category_id_is_active_index` (`category_id`,`is_active`),
  KEY `products_measurement_unit_id_foreign` (`measurement_unit_id`),
  KEY `products_company_id_index` (`company_id`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `products_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_measurement_unit_id_foreign` FOREIGN KEY (`measurement_unit_id`) REFERENCES `measurement_units` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=112 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,NULL,'Arroz grano largo 1 kg','7800000000001',7,1,'Producto de ejemplo para supermercado.',NULL,6.50,8.90,11,1,'2026-05-19 20:19:39','2026-05-20 01:24:27',NULL),(2,NULL,'Azucar blanca 1 kg','7800000000002',7,1,'Producto de ejemplo para supermercado.',NULL,5.20,7.40,8,1,'2026-05-19 20:27:02','2026-05-20 01:24:27',NULL),(3,NULL,'Producto AJAX prueba','91779223744',1,1,'Creado via prueba AJAX',NULL,10.00,15.00,2,1,'2026-05-19 20:49:04','2026-05-19 20:49:13','2026-05-19 20:49:13'),(4,NULL,'Fideo espagueti 400 g','7800000000003',7,1,'Producto de ejemplo para supermercado.',NULL,3.10,4.80,7,1,'2026-05-20 01:22:19','2026-05-20 01:24:27',NULL),(5,NULL,'Fideo macarron 400 g','7800000000004',7,1,'Producto de ejemplo para supermercado.',NULL,3.00,4.70,20,1,'2026-05-20 01:22:19','2026-05-20 01:24:27',NULL),(6,NULL,'Aceite vegetal 900 ml','7800000000005',7,1,'Producto de ejemplo para supermercado.',NULL,10.50,14.90,25,1,'2026-05-20 01:22:19','2026-05-20 01:24:27',NULL),(7,NULL,'Harina de trigo 1 kg','7800000000006',7,1,'Producto de ejemplo para supermercado.',NULL,4.80,6.90,20,1,'2026-05-20 01:22:19','2026-05-20 01:24:27',NULL),(8,NULL,'Sal yodada 1 kg','7800000000007',7,1,'Producto de ejemplo para supermercado.',NULL,1.80,2.90,24,1,'2026-05-20 01:22:19','2026-05-20 01:24:27',NULL),(9,NULL,'Lenteja 500 g','7800000000008',7,1,'Producto de ejemplo para supermercado.',NULL,5.40,7.90,25,1,'2026-05-20 01:22:19','2026-05-20 01:24:27',NULL),(10,NULL,'Quinua real 500 g','7800000000009',7,1,'Producto de ejemplo para supermercado.',NULL,12.00,16.90,6,1,'2026-05-20 01:22:19','2026-05-20 01:24:27',NULL),(11,NULL,'Avena instantanea 400 g','7800000000010',7,1,'Producto de ejemplo para supermercado.',NULL,5.60,8.20,17,1,'2026-05-20 01:22:19','2026-05-20 01:24:27',NULL),(12,NULL,'Cafe instantaneo 170 g','7800000000011',7,1,'Producto de ejemplo para supermercado.',NULL,18.00,24.90,21,1,'2026-05-20 01:22:19','2026-05-20 01:24:27',NULL),(13,NULL,'Te negro caja 100 sobres','7800000000012',7,1,'Producto de ejemplo para supermercado.',NULL,9.20,13.50,24,1,'2026-05-20 01:22:19','2026-05-20 01:24:27',NULL),(14,NULL,'Mayonesa 350 g','7800000000013',7,1,'Producto de ejemplo para supermercado.',NULL,6.90,9.90,10,1,'2026-05-20 01:22:19','2026-05-20 01:24:27',NULL),(15,NULL,'Ketchup 400 g','7800000000014',7,1,'Producto de ejemplo para supermercado.',NULL,5.90,8.90,22,1,'2026-05-20 01:22:19','2026-05-20 01:24:27',NULL),(16,NULL,'Sardina en tomate 170 g','7800000000015',7,1,'Producto de ejemplo para supermercado.',NULL,6.50,9.20,15,1,'2026-05-20 01:22:19','2026-05-20 01:24:27',NULL),(17,NULL,'Atun en aceite 170 g','7800000000016',7,1,'Producto de ejemplo para supermercado.',NULL,9.80,13.90,15,1,'2026-05-20 01:22:19','2026-05-20 01:24:27',NULL),(18,NULL,'Mermelada de frutilla 450 g','7800000000017',7,1,'Producto de ejemplo para supermercado.',NULL,8.50,12.50,20,1,'2026-05-20 01:22:19','2026-05-20 01:24:27',NULL),(19,NULL,'Galletas de agua 250 g','7800000000018',7,1,'Producto de ejemplo para supermercado.',NULL,4.10,6.20,9,1,'2026-05-20 01:22:19','2026-05-20 01:24:27',NULL),(20,NULL,'Cereal de maiz 300 g','7800000000019',7,1,'Producto de ejemplo para supermercado.',NULL,11.00,15.90,22,1,'2026-05-20 01:22:19','2026-05-20 01:24:27',NULL),(21,NULL,'Chocolate en polvo 400 g','7800000000020',7,1,'Producto de ejemplo para supermercado.',NULL,12.50,17.90,5,1,'2026-05-20 01:22:19','2026-05-20 01:24:27',NULL),(22,NULL,'Leche entera 1 L','7800000000021',8,1,'Producto de ejemplo para supermercado.',NULL,5.60,7.20,10,1,'2026-05-20 01:22:19','2026-05-20 01:24:27',NULL),(23,NULL,'Leche deslactosada 1 L','7800000000022',8,1,'Producto de ejemplo para supermercado.',NULL,6.10,8.00,7,1,'2026-05-20 01:22:19','2026-05-20 01:24:27',NULL),(24,NULL,'Yogurt frutilla 1 L','7800000000023',8,1,'Producto de ejemplo para supermercado.',NULL,8.50,11.50,17,1,'2026-05-20 01:22:19','2026-05-20 01:24:27',NULL),(25,NULL,'Yogurt natural 1 L','7800000000024',8,1,'Producto de ejemplo para supermercado.',NULL,8.20,11.20,20,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(26,NULL,'Queso fresco 500 g','7800000000025',8,1,'Producto de ejemplo para supermercado.',NULL,15.00,21.90,21,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(27,NULL,'Queso mozzarella 250 g','7800000000026',8,1,'Producto de ejemplo para supermercado.',NULL,12.50,18.50,18,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(28,NULL,'Mantequilla 200 g','7800000000027',8,1,'Producto de ejemplo para supermercado.',NULL,9.80,14.50,10,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(29,NULL,'Margarina 250 g','7800000000028',8,1,'Producto de ejemplo para supermercado.',NULL,6.40,9.40,16,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(30,NULL,'Crema de leche 200 ml','7800000000029',8,1,'Producto de ejemplo para supermercado.',NULL,6.90,9.90,25,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(31,NULL,'Huevos maple 30 unidades','7800000000030',8,1,'Producto de ejemplo para supermercado.',NULL,21.00,29.90,6,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(32,NULL,'Agua mineral sin gas 2 L','7800000000031',2,1,'Producto de ejemplo para supermercado.',NULL,3.60,5.50,21,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(33,NULL,'Agua mineral con gas 2 L','7800000000032',2,1,'Producto de ejemplo para supermercado.',NULL,3.80,5.80,9,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(34,NULL,'Gaseosa cola 2 L','7800000000033',2,1,'Producto de ejemplo para supermercado.',NULL,8.20,11.50,18,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(35,NULL,'Gaseosa naranja 2 L','7800000000034',2,1,'Producto de ejemplo para supermercado.',NULL,7.90,11.20,25,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(36,NULL,'Jugo de durazno 1 L','7800000000035',2,1,'Producto de ejemplo para supermercado.',NULL,6.50,9.40,16,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(37,NULL,'Jugo de manzana 1 L','7800000000036',2,1,'Producto de ejemplo para supermercado.',NULL,6.30,9.20,12,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(38,NULL,'Bebida energizante 473 ml','7800000000037',2,1,'Producto de ejemplo para supermercado.',NULL,8.90,12.90,5,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(39,NULL,'Cerveza lata 355 ml','7800000000038',2,1,'Producto de ejemplo para supermercado.',NULL,6.50,9.00,14,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(40,NULL,'Mate cocido listo 500 ml','7800000000039',2,1,'Producto de ejemplo para supermercado.',NULL,4.20,6.50,14,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(41,NULL,'Soda limon 1.5 L','7800000000040',2,1,'Producto de ejemplo para supermercado.',NULL,6.50,9.30,21,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(42,NULL,'Detergente en polvo 1 kg','7800000000041',3,1,'Producto de ejemplo para supermercado.',NULL,13.00,18.90,12,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(43,NULL,'Lavandina 1 L','7800000000042',3,1,'Producto de ejemplo para supermercado.',NULL,4.50,6.90,18,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(44,NULL,'Suavizante ropa 900 ml','7800000000043',3,1,'Producto de ejemplo para supermercado.',NULL,9.80,14.20,15,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(45,NULL,'Lavavajillas liquido 750 ml','7800000000044',3,1,'Producto de ejemplo para supermercado.',NULL,8.20,12.20,5,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(46,NULL,'Desinfectante pino 1 L','7800000000045',3,1,'Producto de ejemplo para supermercado.',NULL,7.50,10.90,8,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(47,NULL,'Limpiavidrios 500 ml','7800000000046',3,1,'Producto de ejemplo para supermercado.',NULL,6.80,9.90,8,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(48,NULL,'Esponja multiuso pack 3','7800000000047',3,1,'Producto de ejemplo para supermercado.',NULL,3.20,5.20,10,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(49,NULL,'Bolsa de basura 50 L pack 10','7800000000048',3,1,'Producto de ejemplo para supermercado.',NULL,8.50,12.90,10,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(50,NULL,'Papel higienico 12 rollos','7800000000049',3,1,'Producto de ejemplo para supermercado.',NULL,18.00,26.90,20,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(51,NULL,'Servilletas 200 unidades','7800000000050',3,1,'Producto de ejemplo para supermercado.',NULL,6.80,9.80,25,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(52,NULL,'Shampoo familiar 750 ml','7800000000051',9,1,'Producto de ejemplo para supermercado.',NULL,16.00,23.90,25,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(53,NULL,'Acondicionador 750 ml','7800000000052',9,1,'Producto de ejemplo para supermercado.',NULL,16.00,23.90,9,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(54,NULL,'Jabon de tocador pack 3','7800000000053',9,1,'Producto de ejemplo para supermercado.',NULL,7.20,10.90,22,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(55,NULL,'Pasta dental 90 g','7800000000054',9,1,'Producto de ejemplo para supermercado.',NULL,6.40,9.40,9,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(56,NULL,'Cepillo dental mediano','7800000000055',9,1,'Producto de ejemplo para supermercado.',NULL,4.50,7.20,12,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(57,NULL,'Desodorante aerosol 150 ml','7800000000056',9,1,'Producto de ejemplo para supermercado.',NULL,13.50,19.90,25,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(58,NULL,'Toallas humedas 80 unidades','7800000000057',9,1,'Producto de ejemplo para supermercado.',NULL,12.00,17.50,25,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(59,NULL,'Papel facial caja 100 unidades','7800000000058',9,1,'Producto de ejemplo para supermercado.',NULL,7.80,11.50,24,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(60,NULL,'Pollo entero por kg','7800000000059',10,1,'Producto de ejemplo para supermercado.',NULL,12.00,16.90,11,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(61,NULL,'Pechuga de pollo por kg','7800000000060',10,1,'Producto de ejemplo para supermercado.',NULL,18.00,25.90,24,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(62,NULL,'Carne molida por kg','7800000000061',10,1,'Producto de ejemplo para supermercado.',NULL,28.00,39.90,15,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(63,NULL,'Bife de res por kg','7800000000062',10,1,'Producto de ejemplo para supermercado.',NULL,35.00,49.90,10,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(64,NULL,'Chuleta de cerdo por kg','7800000000063',10,1,'Producto de ejemplo para supermercado.',NULL,24.00,34.90,19,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(65,NULL,'Salchicha viena 500 g','7800000000064',10,1,'Producto de ejemplo para supermercado.',NULL,10.50,15.50,23,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(66,NULL,'Jamon sandwichero 200 g','7800000000065',10,1,'Producto de ejemplo para supermercado.',NULL,9.80,14.80,15,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(67,NULL,'Mortadela 250 g','7800000000066',10,1,'Producto de ejemplo para supermercado.',NULL,6.50,9.90,21,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(68,NULL,'Papa imilla por kg','7800000000067',11,1,'Producto de ejemplo para supermercado.',NULL,3.20,4.80,16,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(69,NULL,'Cebolla por kg','7800000000068',11,1,'Producto de ejemplo para supermercado.',NULL,2.80,4.30,21,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(70,NULL,'Tomate por kg','7800000000069',11,1,'Producto de ejemplo para supermercado.',NULL,4.20,6.50,25,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(71,NULL,'Zanahoria por kg','7800000000070',11,1,'Producto de ejemplo para supermercado.',NULL,3.00,4.60,11,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(72,NULL,'Lechuga unidad','7800000000071',11,1,'Producto de ejemplo para supermercado.',NULL,2.50,4.00,8,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(73,NULL,'Platano por kg','7800000000072',11,1,'Producto de ejemplo para supermercado.',NULL,4.50,6.90,22,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(74,NULL,'Manzana roja por kg','7800000000073',11,1,'Producto de ejemplo para supermercado.',NULL,8.00,11.90,7,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(75,NULL,'Naranja por kg','7800000000074',11,1,'Producto de ejemplo para supermercado.',NULL,5.00,7.50,5,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(76,NULL,'Pan molde blanco 600 g','7800000000075',12,1,'Producto de ejemplo para supermercado.',NULL,8.20,12.00,19,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(77,NULL,'Pan integral 600 g','7800000000076',12,1,'Producto de ejemplo para supermercado.',NULL,9.50,13.90,15,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(78,NULL,'Tortillas de trigo pack 10','7800000000077',12,1,'Producto de ejemplo para supermercado.',NULL,7.80,11.50,14,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(79,NULL,'Queque familiar vainilla','7800000000078',12,1,'Producto de ejemplo para supermercado.',NULL,12.00,17.90,17,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(80,NULL,'Galletas dulces surtidas 400 g','7800000000079',12,1,'Producto de ejemplo para supermercado.',NULL,7.50,10.90,7,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(81,NULL,'Hamburguesas congeladas pack 4','7800000000080',13,1,'Producto de ejemplo para supermercado.',NULL,18.00,26.90,14,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(82,NULL,'Papas prefritas 1 kg','7800000000081',13,1,'Producto de ejemplo para supermercado.',NULL,15.00,22.50,25,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(83,NULL,'Verduras mixtas congeladas 500 g','7800000000082',13,1,'Producto de ejemplo para supermercado.',NULL,10.80,15.90,5,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(84,NULL,'Helado familiar 1 L','7800000000083',13,1,'Producto de ejemplo para supermercado.',NULL,14.50,21.90,6,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(85,NULL,'Nuggets de pollo 500 g','7800000000084',13,1,'Producto de ejemplo para supermercado.',NULL,17.00,24.90,6,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(86,NULL,'Alimento perro adulto 2 kg','7800000000085',14,1,'Producto de ejemplo para supermercado.',NULL,25.00,36.90,7,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(87,NULL,'Alimento gato adulto 1 kg','7800000000086',14,1,'Producto de ejemplo para supermercado.',NULL,18.00,26.90,23,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(88,NULL,'Arena sanitaria gato 4 kg','7800000000087',14,1,'Producto de ejemplo para supermercado.',NULL,20.00,29.90,14,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(89,NULL,'Snack para perro 100 g','7800000000088',14,1,'Producto de ejemplo para supermercado.',NULL,8.50,12.50,16,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(90,NULL,'Pañales talla M pack 30','7800000000089',15,1,'Producto de ejemplo para supermercado.',NULL,45.00,64.90,6,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(91,NULL,'Pañales talla G pack 30','7800000000090',15,1,'Producto de ejemplo para supermercado.',NULL,48.00,68.90,21,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(92,NULL,'Formula infantil 800 g','7800000000091',15,1,'Producto de ejemplo para supermercado.',NULL,85.00,119.90,9,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(93,NULL,'Papilla cereal arroz 200 g','7800000000092',15,1,'Producto de ejemplo para supermercado.',NULL,13.00,18.90,9,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(94,NULL,'Shampoo bebe 400 ml','7800000000093',15,1,'Producto de ejemplo para supermercado.',NULL,14.00,20.90,18,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(95,NULL,'Pilas alcalinas AA pack 4','7800000000094',4,1,'Producto de ejemplo para supermercado.',NULL,11.00,16.90,11,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(96,NULL,'Pilas alcalinas AAA pack 4','7800000000095',4,1,'Producto de ejemplo para supermercado.',NULL,11.00,16.90,24,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(97,NULL,'Foco LED 9W luz blanca','7800000000096',4,1,'Producto de ejemplo para supermercado.',NULL,9.00,13.90,14,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(98,NULL,'Cable USB-C 1 m','7800000000097',4,1,'Producto de ejemplo para supermercado.',NULL,13.00,19.90,8,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(99,NULL,'Cargador pared USB 2A','7800000000098',4,1,'Producto de ejemplo para supermercado.',NULL,18.00,26.90,8,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(100,NULL,'Papas fritas 150 g','7800000000099',16,1,'Producto de ejemplo para supermercado.',NULL,6.80,9.90,25,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(101,NULL,'Mani salado 200 g','7800000000100',16,1,'Producto de ejemplo para supermercado.',NULL,7.50,11.00,24,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(102,NULL,'Chocolate barra 100 g','7800000000101',16,1,'Producto de ejemplo para supermercado.',NULL,5.80,8.50,25,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(103,NULL,'Caramelos surtidos 500 g','7800000000102',16,1,'Producto de ejemplo para supermercado.',NULL,10.00,14.90,25,1,'2026-05-20 01:22:19','2026-05-20 01:24:28',NULL),(104,NULL,'MANTEQUILLA',NULL,1,1,NULL,NULL,25.00,35.00,15,1,'2026-05-20 01:40:54','2026-05-20 01:40:54',NULL),(105,1,'Pollo a la Broaster',NULL,17,8,NULL,NULL,10.00,18.00,10,1,'2026-05-22 14:04:28','2026-05-22 14:04:28',NULL),(106,1,'Silpancho Especial',NULL,17,8,NULL,NULL,10.00,18.00,10,1,'2026-05-22 14:05:15','2026-05-22 14:06:17',NULL),(107,1,'Milanesa de Pollo',NULL,17,8,NULL,NULL,10.00,18.00,10,1,'2026-05-22 14:06:10','2026-05-22 14:06:10',NULL),(108,1,'Coca Cola 2 Litros',NULL,18,8,NULL,NULL,12.00,15.00,20,1,'2026-05-22 14:08:56','2026-05-22 14:08:56',NULL),(109,1,'Coca Cola 1 1/2 l',NULL,18,8,NULL,NULL,8.00,12.00,12,1,'2026-05-22 14:09:22','2026-05-22 14:10:11',NULL),(110,1,'Coca cola 500 ml',NULL,18,8,NULL,NULL,5.00,8.00,12,1,'2026-05-22 14:09:41','2026-05-22 14:10:16',NULL),(111,1,'Jugo del Valle 2l',NULL,18,8,NULL,NULL,13.00,22.00,20,1,'2026-05-22 14:10:04','2026-05-22 17:30:53',NULL);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_details`
--

DROP TABLE IF EXISTS `purchase_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `presentation_id` bigint unsigned DEFAULT NULL,
  `presentation_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `package_quantity` int unsigned NOT NULL DEFAULT '1',
  `units_per_package` int unsigned NOT NULL DEFAULT '1',
  `quantity` int unsigned NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_details_purchase_id_foreign` (`purchase_id`),
  KEY `purchase_details_product_id_foreign` (`product_id`),
  KEY `purchase_details_presentation_id_foreign` (`presentation_id`),
  CONSTRAINT `purchase_details_presentation_id_foreign` FOREIGN KEY (`presentation_id`) REFERENCES `presentations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_details_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `purchase_details_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_details`
--

LOCK TABLES `purchase_details` WRITE;
/*!40000 ALTER TABLE `purchase_details` DISABLE KEYS */;
INSERT INTO `purchase_details` VALUES (1,1,103,5,'Paquete x 12',2,12,24,100.00,200.00,'2026-05-20 15:20:29','2026-05-20 15:20:29'),(2,2,6,1,'Unidad',6,1,6,10.50,63.00,'2026-05-20 15:24:43','2026-05-20 15:24:43'),(3,2,6,2,'Caja x 10',2,10,20,150.00,300.00,'2026-05-20 15:24:43','2026-05-20 15:24:43'),(4,3,86,1,'Unidad',100,1,100,25.00,2500.00,'2026-05-20 19:29:48','2026-05-20 19:29:48'),(5,4,6,1,'Unidad',200,1,200,10.50,2100.00,'2026-05-20 20:37:11','2026-05-20 20:37:11'),(6,4,6,2,'Caja x 10',10,10,100,150.00,1500.00,'2026-05-20 20:37:11','2026-05-20 20:37:11'),(7,5,32,4,'Paquete x 6',1,6,6,21.60,21.60,'2026-05-21 21:59:44','2026-05-21 21:59:44'),(8,6,109,6,'Unidad',6,1,6,8.00,48.00,'2026-05-22 15:26:37','2026-05-22 15:26:37'),(9,7,108,6,'Unidad',100,1,100,12.00,1200.00,'2026-05-22 16:51:26','2026-05-22 16:51:26'),(10,8,108,7,'paquete de 6',2,6,12,72.00,144.00,'2026-05-22 16:55:12','2026-05-22 16:55:12');
/*!40000 ALTER TABLE `purchase_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchases`
--

DROP TABLE IF EXISTS `purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint unsigned DEFAULT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sequence_number` int unsigned DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchases_warehouse_sequence_unique` (`warehouse_id`,`sequence_number`),
  KEY `purchases_supplier_id_foreign` (`supplier_id`),
  KEY `purchases_user_id_foreign` (`user_id`),
  KEY `purchases_reference_index` (`reference`),
  CONSTRAINT `purchases_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchases_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `purchases_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchases`
--

LOCK TABLES `purchases` WRITE;
/*!40000 ALTER TABLE `purchases` DISABLE KEYS */;
INSERT INTO `purchases` VALUES (1,NULL,1,1,'1-1-000001',1,'2026-05-20',200.00,0.00,200.00,'completed',NULL,'2026-05-20 15:20:29','2026-05-20 15:20:29',NULL),(2,NULL,1,1,'1-1-000002',2,'2026-05-20',363.00,0.00,363.00,'completed',NULL,'2026-05-20 15:24:43','2026-05-20 15:24:43',NULL),(3,1,2,1,'2-2-000001',1,'2026-05-20',2500.00,0.00,2500.00,'completed',NULL,'2026-05-20 19:29:48','2026-05-20 19:29:48',NULL),(4,3,2,1,'2-2-000002',2,'2026-05-20',3600.00,0.00,3600.00,'completed',NULL,'2026-05-20 20:37:11','2026-05-20 20:37:11',NULL),(5,1,2,1,'2-2-000003',3,'2026-05-21',21.60,0.00,21.60,'completed',NULL,'2026-05-21 21:59:44','2026-05-21 21:59:44',NULL),(6,NULL,4,1,'3-4-000001',1,'2026-05-22',48.00,0.00,48.00,'completed',NULL,'2026-05-22 15:26:37','2026-05-22 15:26:37',NULL),(7,NULL,5,1,'4-5-000001',1,'2026-05-22',1200.00,0.00,1200.00,'completed',NULL,'2026-05-22 16:51:26','2026-05-22 16:51:26',NULL),(8,NULL,5,1,'4-5-000002',2,'2026-05-22',144.00,0.00,144.00,'completed',NULL,'2026-05-22 16:55:12','2026-05-22 16:55:12',NULL);
/*!40000 ALTER TABLE `purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(9,1),(10,1),(11,1),(12,1),(13,1),(14,1),(15,1),(16,1),(17,1),(18,1),(19,1),(20,1),(21,1),(22,1),(23,1),(24,1),(25,1),(26,1),(27,1),(28,1),(29,1),(30,1),(31,1),(32,1),(33,1),(34,1),(35,1),(36,1),(37,1),(38,1),(39,1),(40,1),(41,1),(42,1),(43,1),(44,1),(45,1),(46,1),(47,1),(48,1),(49,1),(50,1),(51,1),(52,1),(53,1),(54,1),(55,1),(56,1),(57,1),(58,1),(59,1),(60,1),(61,1),(62,1),(63,1),(64,1),(65,1),(66,1),(67,1),(68,1),(69,1),(70,1),(71,1),(72,1),(1,2),(2,2),(3,2),(4,2),(5,2),(6,2),(7,2),(8,2),(9,2),(10,2),(11,2),(12,2),(13,2),(14,2),(15,2),(16,2),(17,2),(18,2),(19,2),(20,2),(21,2),(22,2),(23,2),(24,2),(25,2),(26,2),(27,2),(28,2),(29,2),(30,2),(31,2),(32,2),(33,2),(34,2),(35,2),(36,2),(37,2),(38,2),(39,2),(40,2),(41,2),(42,2),(43,2),(44,2),(45,2),(46,2),(47,2),(48,2),(49,2),(50,2),(51,2),(52,2),(53,2),(54,2),(55,2),(56,2),(57,2),(58,2),(59,2),(60,2),(61,2),(62,2),(63,2),(64,2),(65,2),(70,2),(71,2),(72,2),(1,3),(2,3),(3,3),(4,3),(6,3),(7,3),(8,3),(12,3),(13,3),(32,3),(33,3),(34,3),(38,3),(39,3),(40,3),(42,3),(43,3),(44,3),(46,3),(50,3),(51,3),(52,3),(54,3),(55,3),(56,3),(58,3),(59,3),(60,3),(1,4),(2,4),(6,4),(11,4),(36,4),(37,4),(38,4),(42,4),(50,4),(54,4),(58,4),(62,4),(1,5),(2,5),(6,5),(13,5),(38,5),(42,5),(50,5),(58,5),(1,6),(2,6),(3,6),(4,6),(6,6),(7,6),(8,6),(10,6),(11,6),(12,6),(13,6),(16,6),(32,6),(33,6),(35,6),(38,6),(39,6),(40,6),(42,6),(43,6),(44,6),(46,6),(50,6),(51,6),(52,6),(54,6),(55,6),(56,6),(58,6),(59,6),(60,6),(62,6),(63,6),(64,6),(65,6),(71,6),(72,6),(1,7),(2,7),(6,7),(7,7),(8,7),(10,7),(12,7),(32,7),(34,7),(35,7),(38,7),(42,7),(43,7),(44,7),(46,7),(50,7),(54,7),(55,7),(56,7),(58,7),(59,7),(60,7);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'super_admin','web','2026-05-19 20:19:39','2026-05-19 20:19:39'),(2,'admin','web','2026-05-19 20:19:39','2026-05-19 20:19:39'),(3,'inventory_manager','web','2026-05-19 20:19:39','2026-05-19 20:19:39'),(4,'cashier','web','2026-05-19 20:19:39','2026-05-19 20:19:39'),(5,'viewer','web','2026-05-19 20:19:39','2026-05-19 20:19:39'),(6,'manager','web','2026-05-19 21:11:52','2026-05-19 21:11:52'),(7,'warehouse','web','2026-05-19 21:11:52','2026-05-19 21:11:52');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_details`
--

DROP TABLE IF EXISTS `sale_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `presentation_id` bigint unsigned DEFAULT NULL,
  `presentation_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `package_quantity` int unsigned NOT NULL DEFAULT '1',
  `units_per_package` int unsigned NOT NULL DEFAULT '1',
  `quantity` int unsigned NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `discount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_details_sale_id_foreign` (`sale_id`),
  KEY `sale_details_product_id_foreign` (`product_id`),
  KEY `sale_details_presentation_id_foreign` (`presentation_id`),
  CONSTRAINT `sale_details_presentation_id_foreign` FOREIGN KEY (`presentation_id`) REFERENCES `presentations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sale_details_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `sale_details_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_details`
--

LOCK TABLES `sale_details` WRITE;
/*!40000 ALTER TABLE `sale_details` DISABLE KEYS */;
INSERT INTO `sale_details` VALUES (1,1,6,1,'Unidad',5,1,5,14.90,0.00,74.50,'2026-05-20 20:41:30','2026-05-20 20:41:30'),(2,1,6,2,'Caja x 10',1,10,10,149.00,0.00,149.00,'2026-05-20 20:41:30','2026-05-20 20:41:30'),(3,2,6,2,'Caja x 10',5,10,50,149.00,0.00,745.00,'2026-05-20 20:56:24','2026-05-20 20:56:24'),(4,3,6,1,'Unidad',195,1,195,14.90,0.00,2905.50,'2026-05-21 02:11:53','2026-05-21 02:11:53'),(5,4,6,1,'Unidad',1,1,1,14.90,0.00,14.90,'2026-05-21 12:44:31','2026-05-21 12:44:31'),(6,5,6,1,'Unidad',1,1,1,14.90,0.00,14.90,'2026-05-21 13:24:04','2026-05-21 13:24:04'),(7,6,6,1,'Unidad',1,1,1,14.90,0.00,14.90,'2026-05-21 13:24:23','2026-05-21 13:24:23'),(8,7,6,1,'Unidad',1,1,1,14.90,0.00,14.90,'2026-05-21 14:07:39','2026-05-21 14:07:39'),(9,8,6,1,'Unidad',1,1,1,14.90,0.00,14.90,'2026-05-21 14:08:53','2026-05-21 14:08:53'),(10,9,6,1,'Unidad',1,1,1,14.90,0.00,14.90,'2026-05-21 14:13:36','2026-05-21 14:13:36'),(11,10,6,1,'Unidad',1,1,1,14.90,0.00,14.90,'2026-05-21 14:15:19','2026-05-21 14:15:19'),(12,11,6,1,'Unidad',1,1,1,14.90,0.00,14.90,'2026-05-21 14:28:25','2026-05-21 14:28:25'),(13,12,6,1,'Unidad',1,1,1,14.90,0.00,14.90,'2026-05-21 14:28:38','2026-05-21 14:28:38'),(14,13,6,1,'Unidad',1,1,1,14.90,0.00,14.90,'2026-05-21 14:29:04','2026-05-21 14:29:04'),(15,14,6,1,'Unidad',1,1,1,14.90,0.00,14.90,'2026-05-21 21:37:04','2026-05-21 21:37:04'),(16,15,109,6,'Unidad',1,1,1,12.00,0.00,12.00,'2026-05-22 15:34:44','2026-05-22 15:34:44'),(17,16,109,6,'Unidad',1,1,1,12.00,2.00,10.00,'2026-05-22 16:03:49','2026-05-22 16:03:49');
/*!40000 ALTER TABLE `sale_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_payments`
--

DROP TABLE IF EXISTS `sale_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint unsigned NOT NULL,
  `payment_method_id` bigint unsigned DEFAULT NULL,
  `payment_method_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `received_amount` decimal(12,2) DEFAULT NULL,
  `change_amount` decimal(12,2) DEFAULT NULL,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_payments_payment_method_id_foreign` (`payment_method_id`),
  KEY `sale_payments_sale_id_payment_method_id_index` (`sale_id`,`payment_method_id`),
  CONSTRAINT `sale_payments_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sale_payments_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_payments`
--

LOCK TABLES `sale_payments` WRITE;
/*!40000 ALTER TABLE `sale_payments` DISABLE KEYS */;
INSERT INTO `sale_payments` VALUES (1,7,1,'Efectivo',14.90,NULL,NULL,NULL,'2026-05-21 14:07:39','2026-05-21 14:07:39'),(2,8,1,'Efectivo',12.00,NULL,NULL,NULL,'2026-05-21 14:08:53','2026-05-21 14:08:53'),(3,8,2,'QR',2.90,NULL,NULL,NULL,'2026-05-21 14:08:53','2026-05-21 14:08:53'),(4,9,1,'Efectivo',12.00,NULL,NULL,NULL,'2026-05-21 14:13:36','2026-05-21 14:13:36'),(5,9,2,'QR',2.90,NULL,NULL,NULL,'2026-05-21 14:13:36','2026-05-21 14:13:36'),(6,10,1,'Efectivo',14.90,NULL,NULL,NULL,'2026-05-21 14:15:19','2026-05-21 14:15:19'),(7,11,1,'Efectivo',14.90,100.00,85.10,NULL,'2026-05-21 14:28:25','2026-05-21 14:28:25'),(8,12,2,'QR',14.90,NULL,NULL,NULL,'2026-05-21 14:28:38','2026-05-21 14:28:38'),(9,13,1,'Efectivo',12.00,NULL,NULL,NULL,'2026-05-21 14:29:04','2026-05-21 14:29:04'),(10,13,2,'QR',2.90,NULL,NULL,NULL,'2026-05-21 14:29:04','2026-05-21 14:29:04'),(11,14,1,'Efectivo',14.90,15.00,0.10,NULL,'2026-05-21 21:37:04','2026-05-21 21:37:04'),(12,15,5,'Efectivo',12.00,12.00,0.00,NULL,'2026-05-22 15:34:44','2026-05-22 15:34:44'),(13,16,5,'Efectivo',10.00,11.00,1.00,NULL,'2026-05-22 16:03:49','2026-05-22 16:03:49');
/*!40000 ALTER TABLE `sale_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_document_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `cash_register_id` bigint unsigned DEFAULT NULL,
  `point_of_sale_id` bigint unsigned DEFAULT NULL,
  `receipt_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sequence_number` int unsigned DEFAULT NULL,
  `sale_date` datetime NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'completed',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_receipt_number_unique` (`receipt_number`),
  KEY `sales_customer_id_foreign` (`customer_id`),
  KEY `sales_branch_id_foreign` (`branch_id`),
  KEY `sales_warehouse_id_foreign` (`warehouse_id`),
  KEY `sales_user_id_foreign` (`user_id`),
  KEY `sales_cash_register_id_foreign` (`cash_register_id`),
  KEY `sales_point_sequence_index` (`point_of_sale_id`,`sequence_number`),
  CONSTRAINT `sales_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `sales_cash_register_id_foreign` FOREIGN KEY (`cash_register_id`) REFERENCES `cash_registers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_point_of_sale_id_foreign` FOREIGN KEY (`point_of_sale_id`) REFERENCES `point_of_sales` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `sales_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES (1,NULL,NULL,NULL,2,2,1,1,2,'2-2-000001-000001',1,'2026-05-20 20:41:30',223.50,0.00,0.00,223.50,'completed',NULL,'2026-05-20 20:41:30','2026-05-20 20:41:30',NULL),(2,NULL,NULL,NULL,2,2,1,1,2,'2-2-000001-000002',2,'2026-05-20 20:56:24',745.00,0.00,0.00,745.00,'completed',NULL,'2026-05-20 20:56:24','2026-05-20 20:56:24',NULL),(3,NULL,NULL,NULL,2,2,1,1,2,'2-2-000001-000003',3,'2026-05-21 02:11:53',2905.50,0.00,0.00,2905.50,'completed',NULL,'2026-05-21 02:11:53','2026-05-21 02:11:53',NULL),(4,NULL,NULL,NULL,2,2,1,1,2,'2-2-000001-000004',4,'2026-05-21 12:44:31',14.90,0.00,0.00,14.90,'completed',NULL,'2026-05-21 12:44:31','2026-05-21 12:44:31',NULL),(5,NULL,'pacheco',NULL,2,2,1,1,2,'2-2-000001-000005',5,'2026-05-21 13:24:04',14.90,0.00,0.00,14.90,'completed',NULL,'2026-05-21 13:24:04','2026-05-21 13:24:04',NULL),(6,1,'pacheco','8324984',2,2,1,1,2,'2-2-000001-000006',6,'2026-05-21 13:24:23',14.90,0.00,0.00,14.90,'completed',NULL,'2026-05-21 13:24:23','2026-05-21 13:24:23',NULL),(7,1,'pacheco alvaro','8324984',2,2,1,1,2,'2-2-000001-000007',7,'2026-05-21 14:07:39',14.90,0.00,0.00,14.90,'completed',NULL,'2026-05-21 14:07:39','2026-05-21 14:07:39',NULL),(8,1,'pacheco alvaro','8324984',2,2,1,1,2,'2-2-000001-000008',8,'2026-05-21 14:08:53',14.90,0.00,0.00,14.90,'completed',NULL,'2026-05-21 14:08:53','2026-05-21 14:08:53',NULL),(9,1,'pacheco alvaro','8324984',2,2,1,1,2,'2-2-000001-000009',9,'2026-05-21 14:13:36',14.90,0.00,0.00,14.90,'completed',NULL,'2026-05-21 14:13:36','2026-05-21 14:13:36',NULL),(10,2,'Danilo Moreno','832498',2,2,1,1,2,'2-2-000001-000010',10,'2026-05-21 14:15:19',14.90,0.00,0.00,14.90,'completed',NULL,'2026-05-21 14:15:19','2026-05-21 14:15:19',NULL),(11,2,'Danilo Moreno','832498',2,2,1,1,2,'2-2-000001-000011',11,'2026-05-21 14:28:25',14.90,0.00,0.00,14.90,'completed',NULL,'2026-05-21 14:28:25','2026-05-21 14:28:25',NULL),(12,NULL,NULL,NULL,2,2,1,1,2,'2-2-000001-000012',12,'2026-05-21 14:28:38',14.90,0.00,0.00,14.90,'completed',NULL,'2026-05-21 14:28:38','2026-05-21 14:28:38',NULL),(13,NULL,NULL,NULL,2,2,1,1,2,'2-2-000001-000013',13,'2026-05-21 14:29:04',14.90,0.00,0.00,14.90,'completed',NULL,'2026-05-21 14:29:04','2026-05-21 14:29:04',NULL),(14,NULL,NULL,NULL,2,2,1,2,2,'2-2-000001-000014',14,'2026-05-21 21:37:04',14.90,0.00,0.00,14.90,'completed',NULL,'2026-05-21 21:37:04','2026-05-21 21:37:04',NULL),(15,NULL,NULL,NULL,3,4,1,3,3,'3-4-000001-000001',1,'2026-05-22 15:34:44',12.00,0.00,0.00,12.00,'completed',NULL,'2026-05-22 15:34:44','2026-05-22 15:34:44',NULL),(16,NULL,NULL,NULL,3,4,1,3,3,'3-4-000001-000002',2,'2026-05-22 16:03:49',12.00,2.00,0.00,10.00,'voided','Anulacion de venta 3-4-000001-000002. Motivo: pruebas sistema','2026-05-22 16:03:49','2026-05-22 17:56:19',NULL);
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('3SxogEeSGBphD18dRokQS0OTlGUo6P0hVJVqsKGV',1,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJjMThjNXY3WXRkQTNNZ1NXU2x2T25BNmJiUThpNDd4dzlVU2wzc2JWIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvbG9jYWxob3N0XC9wb3MiLCJyb3V0ZSI6InBvcy5pbmRleCJ9LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MX0=',1779484691);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `suppliers_document_number_index` (`company_name`),
  KEY `suppliers_company_id_index` (`company_id`),
  CONSTRAINT `suppliers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,NULL,'JUAN APAZA MAMANi','EMBOL',NULL,'87636376',NULL,1,'2026-05-20 13:50:45','2026-05-20 13:50:56',NULL),(2,NULL,'Emma Orozco','Pil',NULL,NULL,NULL,1,'2026-05-20 13:59:09','2026-05-20 13:59:09',NULL),(3,NULL,'Israel Diablo','la mary Juana',NULL,'77512675',NULL,1,'2026-05-20 13:59:31','2026-05-20 13:59:43',NULL);
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,'Administrador','alvaro@pacheco.com',NULL,'$2y$12$EMvmwwJ3.KAKDPRGmXDJhePa0Q3lUOd.J5yw816BRPG7T1fRk6crO',1,'ZohrgNM3zG1JRNsHrvpu10ZmrzlBP6QepQzJCCJkqVocIb4bFPfYe4ewp0px','2026-05-19 20:19:40','2026-05-22 20:43:30',NULL),(3,2,'Emma Celene Orozco Pantoja','boliviannexus@gmail.com',NULL,'$2y$12$emspZDSHqzlltonTR.NcJ.yrV6vZlR6l7nOIn.q41hvAb3WajIMp2',1,NULL,'2026-05-19 21:41:54','2026-05-22 14:41:25',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouses_code_unique` (`code`),
  KEY `warehouses_branch_id_foreign` (`branch_id`),
  KEY `warehouses_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `warehouses_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `warehouses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouses`
--

LOCK TABLES `warehouses` WRITE;
/*!40000 ALTER TABLE `warehouses` DISABLE KEYS */;
INSERT INTO `warehouses` VALUES (1,NULL,1,'ALMACEN 1','1',1,'2026-05-20 00:54:49','2026-05-20 00:54:49',NULL),(2,NULL,2,'TIENDA','2',1,'2026-05-20 00:54:59','2026-05-21 20:35:07',NULL),(3,NULL,1,'ALMACEN FATIMA','3',1,'2026-05-20 20:00:59','2026-05-20 20:01:06',NULL),(4,1,3,'Almacen Cochabamba','123',1,'2026-05-22 14:13:51','2026-05-22 15:40:57',NULL),(5,1,4,'Almacen Fatima','42342',1,'2026-05-22 14:14:03','2026-05-22 15:40:49',NULL);
/*!40000 ALTER TABLE `warehouses` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-22 21:55:58
