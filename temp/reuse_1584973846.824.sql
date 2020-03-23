-- MySQL dump 10.13  Distrib 5.7.9, for osx10.9 (x86_64)
--
-- Host: 127.0.0.1    Database: reuse
-- ------------------------------------------------------
-- Server version	5.7.9

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `reuse`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `reuse` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `reuse`;

--
-- Table structure for table `attendee`
--

DROP TABLE IF EXISTS `attendee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendee` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) DEFAULT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `is_confirmed` tinyint(1) NOT NULL,
  `type` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_1150D56771F7E88B` (`event_id`),
  KEY `IDX_1150D567E7A1254A` (`contact_id`),
  KEY `IDX_1150D567DE12AB56` (`created_by`),
  CONSTRAINT `FK_1150D56771F7E88B` FOREIGN KEY (`event_id`) REFERENCES `event` (`id`),
  CONSTRAINT `FK_1150D567DE12AB56` FOREIGN KEY (`created_by`) REFERENCES `contact` (`id`),
  CONSTRAINT `FK_1150D567E7A1254A` FOREIGN KEY (`contact_id`) REFERENCES `contact` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendee`
--

LOCK TABLES `attendee` WRITE;
/*!40000 ALTER TABLE `attendee` DISABLE KEYS */;
INSERT INTO `attendee` VALUES (1,4,1,1,'2020-02-13 14:07:13',0,'organiser',0.00),(2,6,1,1,'2020-02-25 15:37:07',0,'organiser',0.00),(3,7,1,1,'2020-02-25 15:38:20',0,'organiser',0.00),(5,7,7,1,'2020-02-25 15:53:52',1,'attendee',10.00),(6,7,5,1,'2020-02-25 15:56:17',1,'attendee',0.00),(7,7,40,40,'2020-03-02 13:27:02',1,'attendee',10.00);
/*!40000 ALTER TABLE `attendee` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `check_in_prompt`
--

DROP TABLE IF EXISTS `check_in_prompt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `check_in_prompt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` int(11) DEFAULT NULL,
  `default_on` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_7FFF77DD5E237E06` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `check_in_prompt`
--

LOCK TABLES `check_in_prompt` WRITE;
/*!40000 ALTER TABLE `check_in_prompt` DISABLE KEYS */;
INSERT INTO `check_in_prompt` VALUES (1,'Please confirm components are all here',NULL,1);
/*!40000 ALTER TABLE `check_in_prompt` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `check_out_prompt`
--

DROP TABLE IF EXISTS `check_out_prompt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `check_out_prompt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` int(11) DEFAULT NULL,
  `default_on` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_CC365FBC5E237E06` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `check_out_prompt`
--

LOCK TABLES `check_out_prompt` WRITE;
/*!40000 ALTER TABLE `check_out_prompt` DISABLE KEYS */;
INSERT INTO `check_out_prompt` VALUES (1,'User has been given safety brief',NULL,0),(2,'Check blades are tight',NULL,0);
/*!40000 ALTER TABLE `check_out_prompt` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `child`
--

DROP TABLE IF EXISTS `child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `child` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contact_id` int(11) DEFAULT NULL,
  `name` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `gender` varchar(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_22B35429E7A1254A` (`contact_id`),
  CONSTRAINT `FK_22B35429E7A1254A` FOREIGN KEY (`contact_id`) REFERENCES `contact` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `child`
--

LOCK TABLES `child` WRITE;
/*!40000 ALTER TABLE `child` DISABLE KEYS */;
/*!40000 ALTER TABLE `child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact`
--

DROP TABLE IF EXISTS `contact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_by` int(11) DEFAULT NULL,
  `active_membership` int(11) DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL,
  `salt` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `confirmation_token` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_requested_at` datetime DEFAULT NULL,
  `roles` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:array)',
  `first_name` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_3` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_4` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_iso_code` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `longitude` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` varchar(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `balance` decimal(10,2) NOT NULL,
  `stripe_customer_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subscriber` tinyint(1) NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_canonical` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username_canonical` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active_site` int(11) DEFAULT NULL,
  `created_at_site` int(11) DEFAULT NULL,
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `membership_number` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secure_access_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_4C62E638A75DB073` (`active_membership`),
  UNIQUE KEY `UNIQ_4C62E638C05FB297` (`confirmation_token`),
  KEY `IDX_4C62E638DE12AB56` (`created_by`),
  KEY `IDX_4C62E63887239B34` (`active_site`),
  KEY `IDX_4C62E63894304B5C` (`created_at_site`),
  CONSTRAINT `FK_4C62E63887239B34` FOREIGN KEY (`active_site`) REFERENCES `site` (`id`),
  CONSTRAINT `FK_4C62E63894304B5C` FOREIGN KEY (`created_at_site`) REFERENCES `site` (`id`),
  CONSTRAINT `FK_4C62E638A75DB073` FOREIGN KEY (`active_membership`) REFERENCES `membership` (`id`),
  CONSTRAINT `FK_4C62E638DE12AB56` FOREIGN KEY (`created_by`) REFERENCES `contact` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact`
--

LOCK TABLES `contact` WRITE;
/*!40000 ALTER TABLE `contact` DISABLE KEYS */;
INSERT INTO `contact` VALUES (1,NULL,7,1,NULL,'$2y$13$GBfsef.VXz6BM2Y7Xxt2X.cagZTGogm4fkg8FlJwUXgm5frzH.V9W','2020-03-23 07:35:21',NULL,NULL,'a:2:{i:0;s:10:\"ROLE_ADMIN\";i:1;s:15:\"ROLE_SUPER_USER\";}','Primary','Admin',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-11-11 14:21:32',15.00,NULL,0,'hello@lend-engine.com','hello@lend-engine.com','hello@lend-engine.com','hello@lend-engine.com',4,NULL,'en',1,NULL,NULL),(2,NULL,1,1,NULL,'$2y$13$n9lVEBFGXiEr.6TLs3is..AxCdvoFDhs7MS5d61BHFBu7SYirxMLS','2019-11-14 11:10:18',NULL,NULL,'a:0:{}','Demo','Member','08450038935','1st Floor','New Bond House',NULL,NULL,'GB',NULL,NULL,NULL,'2019-11-11 14:21:33',0.00,'cus_GB2KMPk22RnAzF',0,'demo@lend-engine.com','demo@lend-engine.com','demo@lend-engine.com','demo@lend-engine.com',NULL,NULL,'en',1,NULL,'5dceca071b47f'),(3,NULL,NULL,1,NULL,'$2y$13$HBMkrV73PEFI8HRPwuv.WemitRrfYM0aQr8qZsylAZDpWxMTHQH3m',NULL,NULL,NULL,'a:0:{}','Emily','Edwardson',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-11-11 14:21:33',0.00,NULL,0,'contact@email.com','contact@email.com','contact@email.com','contact@email.com',NULL,NULL,'en',1,NULL,NULL),(4,1,8,1,NULL,'$2y$13$kLJ4MHXc2bFn4ZbEvNyQyePiRkIATz.03JsorJP2Bq7w11fvkDdIW','2020-03-16 09:57:50',NULL,NULL,'a:0:{}','Chris','Tanner',NULL,NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,'2019-11-11 14:59:52',0.00,'cus_G9yMvwbMO1pYnb',0,'chris.tanner@brightpearl.com','chris.tanner@brightpearl.com','chris.tanner@brightpearl.com','chris.tanner@brightpearl.com',NULL,NULL,'en',1,NULL,'5dcec5f8c43a5'),(5,1,NULL,1,NULL,'$2y$13$bgNiLJAQXAm6ngKMT7ZCJO5RSsq8tHfs9nxK2im0KWakuFwTO6KJy',NULL,NULL,NULL,'a:1:{i:0;s:10:\"ROLE_ADMIN\";}','Joe','Electrician',NULL,NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,'2019-11-11 15:24:17',0.00,NULL,0,'chris+electrics@brightpearl.com','chris+electrics@brightpearl.com','chris+electrics@brightpearl.com','chris+electrics@brightpearl.com',NULL,NULL,'en',1,NULL,'5dc97d5492d89'),(6,NULL,3,1,NULL,'$2y$13$0./n20TOy17TMbGuq6JFr.wtZj9rZsHl034IkP.ztFhiYJD3GsF2K','2020-02-13 09:19:13',NULL,NULL,'a:0:{}','Chris','Distributed','07718','Heol Las Fawr','Ceredigion','Wales','SA43 1QA','GB','51.6711131','-3.8837336000000278',NULL,'2019-11-14 10:57:44',-16.50,'cus_GB28Hw2B1yXbgU',0,'chris+disto@brightpearl.com','chris+disto@brightpearl.com','chris+disto@brightpearl.com','chris+disto@brightpearl.com',NULL,NULL,'en',1,NULL,'5dceca1826724'),(7,NULL,6,1,NULL,'$2y$13$pYNT0ZMQLit3hz0s78GLOOlVKnoRtIkiYS5fT3Tna/P1.raL98uMS','2020-02-18 12:14:35',NULL,NULL,'a:0:{}','Katy','Did',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-11-14 14:33:56',0.00,NULL,0,'katy@demo.com','katy@demo.com','katy@demo.com','katy@demo.com',NULL,NULL,'en',1,NULL,'5e412b9e97aef'),(8,1,5,1,NULL,'$2y$13$fb1pu2Fd71qxLszu3zj2Ouje.cs6PfA61ob5D.KEBaxCs9dT98OOO',NULL,NULL,NULL,'a:0:{}','Kelly','Banks',NULL,'Heol Las Fawr','Ferwig',NULL,'SA431QA','GB','51.6711131','-3.8837336000000278',NULL,'2019-11-15 11:29:50',0.00,NULL,0,NULL,NULL,NULL,NULL,NULL,2,'en',1,NULL,'5dde47c8a82a0'),(9,1,NULL,0,NULL,'$2y$13$8q2XnW/6bSac9YJ1KLgHourodFs.SZLmXAtILgbT9tw/2ap4kPNKK',NULL,NULL,NULL,'a:0:{}','DEleted',NULL,NULL,NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,'2019-11-18 09:50:53',0.00,NULL,0,'','','','',NULL,2,'en',0,NULL,NULL),(10,NULL,NULL,1,NULL,'$2y$13$F/RwAcPTnnIaqWmji3D69elxint9kSPlaNVdVGdidLBTALUrDvuki','2020-01-28 16:19:19',NULL,NULL,'a:0:{}','Edward','Smith','+447718991320','Heol Las Fawr','CEREDIGION','test','SA431QA','GB',NULL,NULL,NULL,'2020-01-28 16:19:12',0.00,NULL,0,'chris+ed@brightpearl.com','chris+ed@brightpearl.com','chris+ed@brightpearl.com','chris+ed@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(11,NULL,NULL,0,NULL,'$2y$13$RFFdzu.ceUotrbAn0UhxZud287U8J9YLErrsWwqN7Gluogm/WQRDm',NULL,'pyLQgghceOGo5VWkAttSy61g1GE93QCteisFgrFo-ZI',NULL,'a:0:{}','One','Two','07718991320','Dunmore St','Ceredigion','tee','BS4','GB',NULL,NULL,NULL,'2020-02-13 22:30:32',0.00,NULL,0,'chris+tww@brightpearl.com','chris+tww@brightpearl.com','chris+tww@brightpearl.com','chris+tww@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(12,NULL,NULL,0,NULL,'$2y$13$NZRzZGj7bVU0utJyTjwGXecNwX0YZLevMqo6A1sHhzcFfrHeF9dlu',NULL,'yF2P1wmzK9G6mO8vTFx-VTOVFB9E2gJ6ZdSTOOCRz20',NULL,'a:0:{}','Joe','Biden','08450038935','1st Floor, New Bond House','Bristol','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-13 22:32:24',0.00,NULL,0,'chris+jj@brightpearl.com','chris+jj@brightpearl.com','chris+jj@brightpearl.com','chris+jj@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(13,NULL,10,0,NULL,'$2y$13$.0XN6S1hog/ZhIv24JUsnu20Eyz7kW3xf.KIHz8hKV3Rgge8SPkC.',NULL,'EMQSZmCQtG3wQhc-V3ZmQF3EtM-B7iee0bT0xMMvRCo',NULL,'a:0:{}','Jon','Parker','08450038935','1st Floor, New Bond House','Bristol','Test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-13 22:42:12',0.00,NULL,0,'jon@parker.com','jon@parker.com','jon@parker.com','jon@parker.com',NULL,NULL,'en',1,NULL,NULL),(14,NULL,NULL,1,NULL,'$2y$13$yQ33dq11TyqgrcvWsXBqCuM5EEdT1/XJi3Wp6/Iyogbl4IQj5U886','2020-02-13 22:50:17',NULL,NULL,'a:0:{}','Paul','Testa','08450038935','1st Floor, New Bond House','Bristol','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-13 22:50:17',0.00,NULL,0,'chris+paul@brightpearl.com','chris+paul@brightpearl.com','chris+paul@brightpearl.com','chris+paul@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(15,NULL,NULL,0,NULL,'$2y$13$QGMosNsAWr0QaHTPVnC6Jeg6TDnIDkAfaH98OnrkwCCO5WTY0ChsG',NULL,'vyrKu4i208irjmgsS53fW59IYrmDYXF47uFz-3timEM',NULL,'a:0:{}','Kyle','Smith','08450038935','1st Floor, New Bond House','Bristol','Test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-13 23:06:00',0.00,NULL,0,'chris+kwer@brightpearl.com','chris+kwer@brightpearl.com','chris+kwer@brightpearl.com','chris+kwer@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(16,NULL,NULL,0,NULL,'$2y$13$LzkCdK7Kd2AKnlDIJoVUmu1X5/rXUq.j1yNXVtcJUE545r1m7V6Ey',NULL,'3Qwu56crbpv-e7D9BtSNITPEcqnc9gIPkZvhGxuBKdk',NULL,'a:0:{}','Joe','Man','08450038935','1st Floor, New Bond House','Bristol','Test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-13 23:08:55',0.00,NULL,0,'chris+iuwetr@brightpearl.com','chris+iuwetr@brightpearl.com','chris+iuwetr@brightpearl.com','chris+iuwetr@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(17,NULL,NULL,0,NULL,'$2y$13$DcJYls8nOHNvlTAoeIUJJuQUN/WPWKheNWQQqqCfSfusSuYCgEUO.',NULL,'WYTrWHYT_e3Z3ghuuZEOu7GuuXxopKs1OsVrGzd3Xmc',NULL,'a:0:{}','Joe','Dan','08450038935','1st Floor, New Bond House','Bristol','Test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-13 23:14:42',0.00,NULL,0,'chris+9e8rf@brightpearl.com','chris+9e8rf@brightpearl.com','chris+9e8rf@brightpearl.com','chris+9e8rf@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(18,NULL,13,0,NULL,'$2y$13$CIizWXspTVfsKvZxHDgXVuuO8h2TjE1pR6Ie9XNBJjMGqKPfv/kau',NULL,'P9830lOpaDLiWCHOn4hRtPUEeuxUR7SZd5hiTkStCrA',NULL,'a:0:{}','Bill','Smith','08450038935','1st Floor, New Bond House','Bristol','Test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-13 23:18:41',100.00,NULL,0,'chris+oierct@brightpearl.com','chris+oierct@brightpearl.com','chris+oierct@brightpearl.com','chris+oierct@brightpearl.com',NULL,NULL,'en',1,NULL,'5e623205974fc'),(19,NULL,NULL,0,NULL,'$2y$13$MJ0CxFlPgSxomlDdgYLxTevqkNkVyoyxQRarI4/1rh/cBWfJDZ.F2',NULL,'r6Pr6-bFlbFFCJB7gCjNDrdY9Oq_Jv2nk2DYpJ_G5mQ',NULL,'a:0:{}','Ed','vhris','08450038935','1st Floor, New Bond House','Bristol','Test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-13 23:23:49',0.00,NULL,0,'chris+0uenct@brightpearl.com','chris+0uenct@brightpearl.com','chris+0uenct@brightpearl.com','chris+0uenct@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(20,NULL,NULL,0,NULL,'$2y$13$gUApr8uUX5RXREt3aNW8tONkQjhfq.hkatfTdNUjpjLHvOynHGOGC',NULL,'4xC2ktSZtPL_lv8KPGOpoE6-GCNMQTFxnrMhHeGc2Is',NULL,'a:0:{}','Test','test','08450038935','1st Floor, New Bond House','Bristol','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-13 23:27:57',0.00,NULL,0,'chris+ouyct@brightpearl.com','chris+ouyct@brightpearl.com','chris+ouyct@brightpearl.com','chris+ouyct@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(21,NULL,NULL,0,NULL,'$2y$13$cBQcuTnkfD1dselcQiZqL.O2vgvvba2YeGmbZu6BGXQ5BxnStsUV6',NULL,'mqGh8vf_UKhja1u6EppC90AxQhcp3XmZlgo0Klhcn-I',NULL,'a:0:{}','Test','test','08450038935','1st Floor, New Bond House','Bristol','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-13 23:28:34',0.00,NULL,0,'chris+ouyecntr@brightpearl.com','chris+ouyecntr@brightpearl.com','chris+ouyecntr@brightpearl.com','chris+ouyecntr@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(22,NULL,NULL,0,NULL,'$2y$13$iWb1u6WZhL25wxVWIcsVoO6n3bafQwx6eQQvwNm9LpewIVFf9sikC',NULL,'CwnehSnh4WjqlgjJF8EgBGaoK0ARA3EOsY9anD9jgz8',NULL,'a:0:{}','Joe','Devier','08450038935','1st Floor, New Bond House','Bristol','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-13 23:33:36',0.00,NULL,0,'chris+jjoie@brightpearl.com','chris+jjoie@brightpearl.com','chris+jjoie@brightpearl.com','chris+jjoie@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(23,NULL,NULL,1,NULL,'$2y$13$oKGxz3G6lvjnmvoJJVGHT.vakmMfbNeQJw7YilL2giZ4CN9/XkaSm','2020-02-13 23:39:45',NULL,NULL,'a:0:{}','Hello','Prank','08450038935','1st Floor, New Bond House','Bristol','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-13 23:39:45',0.00,NULL,0,'chris+hh@brightpearl.com','chris+hh@brightpearl.com','chris+hh@brightpearl.com','chris+hh@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(24,NULL,NULL,1,NULL,'$2y$13$dELPrqBnWsxpqDQaCH5IAu/oq7IMoutBp9I0kf6VWqnYZrr.PJDFC','2020-02-13 23:42:52',NULL,NULL,'a:0:{}','one','two','08450038935','1st Floor, New Bond House','Bristol','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-13 23:42:52',0.00,NULL,0,'chris+7894@brightpearl.com','chris+7894@brightpearl.com','chris+7894@brightpearl.com','chris+7894@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(25,NULL,NULL,1,NULL,'$2y$13$aYaGIbS9KtTpHu0eiNLuquvMYfK6jmYLeXm40TkzQhCD9tRy3Pili','2020-02-13 23:46:09',NULL,NULL,'a:0:{}','Jon','Jon','08450038935','1st Floor, New Bond House','Bristol','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-13 23:46:09',0.00,NULL,0,'chris+u98y9@brightpearl.com','chris+u98y9@brightpearl.com','chris+u98y9@brightpearl.com','chris+u98y9@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(26,NULL,NULL,1,NULL,'$2y$13$rF7GNKXeebmTIEcWBDzyGeGsqveb119pXgQOaYTJeTCQPUF6ufUYC','2020-02-13 23:54:45',NULL,NULL,'a:0:{}','Ben','Ben','08450038935','1st Floor, New Bond House','Bristol','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-13 23:54:45',0.00,NULL,0,'chris+ben@brightpearl.com','chris+ben@brightpearl.com','chris+ben@brightpearl.com','chris+ben@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(27,NULL,NULL,1,NULL,'$2y$13$MfQexEEGfMzNJJ9RVt.Hieu1VHZ5q91DOdR7qzMgf0ySLMprleVVu','2020-02-13 23:55:57',NULL,NULL,'a:0:{}','One','One','08450038935','1st Floor, New Bond House','Bristol','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-13 23:55:57',0.00,NULL,0,'chris+one@brightpearl.com','chris+one@brightpearl.com','chris+one@brightpearl.com','chris+one@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(28,NULL,NULL,1,NULL,'$2y$13$UCry/ZsyKvpinMnwTXjCKuYTMZ//elQjPEDzldkkiMSHd8x.lF6rm','2020-02-14 00:05:23',NULL,NULL,'a:0:{}','One','Three','08450038935','1st Floor, New Bond House','Bristol','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-14 00:05:23',0.00,NULL,0,'chris+87w6br@brightpearl.com','chris+87w6br@brightpearl.com','chris+87w6br@brightpearl.com','chris+87w6br@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(29,NULL,NULL,1,NULL,'$2y$13$PPcwVAB/YmkW1QPGzofWju3WVcc94RgUFpc1jR.UgY6Bq.kocoxNq','2020-02-14 00:07:42',NULL,NULL,'a:0:{}','Actiuon','neeeded','08450038935','1st Floor, New Bond House','Bristol','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-14 00:06:21',16.50,NULL,0,'chris+acoiun@brightpearl.com','chris+acoiun@brightpearl.com','chris+acoiun@brightpearl.com','chris+acoiun@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(30,NULL,NULL,1,NULL,'$2y$13$jy9W5HCmkU0Og8jdKzhQxOVuWHrha2L.BiNRNXC3dKScDS59noBPq','2020-02-14 00:14:10',NULL,NULL,'a:0:{}','Action','Required','08450038935','1st Floor, New Bond House','Bristol','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-14 00:13:46',0.00,NULL,0,'chris+action@brightpearl.com','chris+action@brightpearl.com','chris+action@brightpearl.com','chris+action@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(31,NULL,NULL,1,NULL,'$2y$13$59tteKI0ZspenDgoTesRDeS0T/Hnnml1isaUeD6xhng40I6UXxgXG','2020-02-14 00:20:01',NULL,NULL,'a:0:{}','Check','emil','08450038935','1st Floor, New Bond House','Bristol','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-14 00:19:13',0.00,NULL,0,'chris+c8syen@brightpearl.com','chris+c8syen@brightpearl.com','chris+c8syen@brightpearl.com','chris+c8syen@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(32,NULL,NULL,1,NULL,'$2y$13$Ld4B2NB82D2qh6Shf1FAFeBpGKRaqLVMk9Yij.lWArGiHAdQAbURG','2020-02-17 08:28:23',NULL,NULL,'a:0:{}','No','Action','08450038935','1st Floor, New Bond House','Bristol','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-14 00:20:35',0.00,NULL,0,'chris+none@brightpearl.com','chris+none@brightpearl.com','chris+none@brightpearl.com','chris+none@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(33,NULL,NULL,1,NULL,'$2y$13$dnJ8kMe/kkpPQUUcTQ48Be1d2pN7S.ScURuliQn6Z2hF7C0nDK21i','2020-02-17 08:30:45',NULL,NULL,'a:0:{}','Polly','Parker','08450038935','1st Floor, New Bond House','Bristol','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-17 08:30:16',0.00,NULL,0,'chris+polly@brightpearl.com','chris+polly@brightpearl.com','chris+polly@brightpearl.com','chris+polly@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(34,NULL,NULL,1,NULL,'$2y$13$lM385nvCPZF2HSEnJuq3w.hdndi6/jAbG1OrqRXLN9E/T89qxuEky','2020-02-17 08:32:45',NULL,NULL,'a:0:{}','James','Jones','08450038935','1st Floor, New Bond House','Bristol','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-17 08:32:45',0.00,NULL,0,'chris+james8u4@brightpearl.com','chris+james8u4@brightpearl.com','chris+james8u4@brightpearl.com','chris+james8u4@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(35,NULL,NULL,1,NULL,'$2y$13$6bzAVdiNMQZ01gEVcvBUxeclcYujOHLnGyPV01hIb6m/cnR3C9tIi','2020-02-17 08:48:15',NULL,NULL,'a:0:{}','Emma','Tanner','08450038935','1st Floor, New Bond House','Bristol','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-17 08:48:15',0.00,NULL,0,'chris+346@brightpearl.com','chris+346@brightpearl.com','chris+346@brightpearl.com','chris+346@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(36,NULL,NULL,1,NULL,'$2y$13$dHVPB.jH.XO837u3dR6O3..wQ6i6huxqPcfyVg/uGydzi9NNnX.zS','2020-02-17 08:52:30',NULL,NULL,'a:0:{}','Email','Confirmed','08450038935','1st Floor, New Bond House','Bristol','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-17 08:52:08',0.00,NULL,0,'chris+emaiwo8ur@brightpearl.com','chris+emaiwo8ur@brightpearl.com','chris+emaiwo8ur@brightpearl.com','chris+emaiwo8ur@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(37,NULL,NULL,0,NULL,'$2y$13$aQ2okQWK365Mqqn6bDx5GuguNpKf1IzElcpgJRRw4VRoOyEDYrHIe',NULL,'8XZCUumO8U6HcMhk9AqpJ3xR4RyOY-UecBb90h18e_M',NULL,'a:0:{}','please','confirm','08450038935','1st Floor, New Bond House','Bristol','ets','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-17 08:55:27',0.00,NULL,0,'chris+oieucr@brightpearl.com','chris+oieucr@brightpearl.com','chris+oieucr@brightpearl.com','chris+oieucr@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(38,NULL,9,1,NULL,'$2y$13$SnGFlPh5RRafQj.LnLW1V.kY2m9PVFlEG5OGWC5skRuJS6NxNFWaC','2020-02-18 12:18:57',NULL,NULL,'a:0:{}','Parker','Pin','08450038935','1st Floor, New Bond House','Bristol','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-02-18 12:18:57',0.00,NULL,0,'chris+pp@brightpearl.com','chris+pp@brightpearl.com','chris+pp@brightpearl.com','chris+pp@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(39,1,NULL,1,NULL,'$2y$13$gPdx9HYa922fG7hooa1Ft.aDgpnK47BLMqgfUMX94gNU0rDahlE4a',NULL,NULL,NULL,'a:0:{}','Jeery','Joe',NULL,NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,'2020-03-02 13:15:29',0.00,NULL,0,'chris+joesy@brightpearl.com','chris+joesy@brightpearl.com','chris+joesy@brightpearl.com','chris+joesy@brightpearl.com',NULL,3,'en',1,NULL,NULL),(40,NULL,11,1,NULL,'$2y$13$VDbW.BPEBIpZK6fr6g/Pju3MY5miPdSL80NLNWFo863YVs1wttvO2','2020-03-02 13:38:45',NULL,NULL,'a:0:{}','Hap','Ness','07718991320','Dunmore St','test','test','BS4','GB',NULL,NULL,NULL,'2020-03-02 13:23:34',0.00,'cus_Gpu6u8c9ULG5gZ',0,'chris+hap@brightpearl.com','chris+hap@brightpearl.com','chris+hap@brightpearl.com','chris+hap@brightpearl.com',NULL,NULL,'en',1,NULL,'5e5d0c4506396'),(41,NULL,12,1,NULL,'$2y$13$.ltklMc3FPZJtPas1.aP7.XwD8Uk73EquzsKNDq5tw5pKq4N0moOy','2020-03-02 16:43:46',NULL,NULL,'a:0:{}','jamie','parker','08450038935','1st Floor, New Bond House','Bristol','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-03-02 16:43:46',41.50,'cus_GpxJR7dscIw147',0,'chris+jouwer@brightpearl.com','chris+jouwer@brightpearl.com','chris+jouwer@brightpearl.com','chris+jouwer@brightpearl.com',NULL,NULL,'en',1,NULL,'5e6219d923532'),(42,NULL,NULL,1,NULL,'$2y$13$hmRFGa21gLObndgarnM7teQITzGqLLciWNK0cfe6WTaJIJUTNcKCy','2020-03-05 12:43:21',NULL,NULL,'a:0:{}','Test','Tester','07718991320','Dunmore St','test','test','BS4','GB',NULL,NULL,NULL,'2020-03-05 12:43:21',0.00,NULL,0,'chris*test.com','chris*test.com','chris*test.com','chris*test.com',NULL,NULL,'en',1,NULL,NULL),(43,NULL,NULL,1,NULL,'$2y$13$/xs/6/kpmVKFH75F3u9wqOxv7fB8R8uPUmi7F994z71z6q.MLhnxi','2020-03-05 12:50:49',NULL,NULL,'a:0:{}','one','two','08450038935','1st Floor','New Bond House','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-03-05 12:50:49',0.00,NULL,0,'test@one.com','test@one.com','test@one.com','test@one.com',NULL,NULL,'en',1,NULL,NULL),(44,NULL,NULL,1,NULL,'$2y$13$EE7VxWB1bUkEmq8o6fg41uvaFrcD8mdyBT0nwZA/KXsgjNAZKK5pG','2020-03-05 13:17:23',NULL,NULL,'a:0:{}','Chris','Tanner','08450038935','1st Floor','New Bond House','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-03-05 13:17:23',0.00,NULL,0,'chrisq3rq@brightpearl.com','chrisq3rq@brightpearl.com','chrisq3rq@brightpearl.com','chrisq3rq@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(45,NULL,NULL,1,NULL,'$2y$13$ywBtmQwePzihnyCPSj3vIOAk0BZDGMgUnc9O7DIvstoDTAFHwhcr6','2020-03-05 13:17:57',NULL,NULL,'a:0:{}','Chris','Tanner','08450038935','1st Floor','New Bond House','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-03-05 13:17:57',0.00,NULL,0,'chrisqr+qwe@@brightpearl.com','chrisqr+qwe@@brightpearl.com','chrisqr+qwe@@brightpearl.com','chrisqr+qwe@@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(46,NULL,NULL,1,NULL,'$2y$13$7s./L5BUG9dNqOnB7kFU9eOF0hbuOAtHLdoXwvlBG3NBBVhUo1boa','2020-03-05 13:22:42',NULL,NULL,'a:0:{}','Chris','Tanner','07718991320','Dunmore St','test','test','BS4','GB',NULL,NULL,NULL,'2020-03-05 13:22:42',0.00,NULL,0,'chris@@lend-engine.com','chris@@lend-engine.com','chris@@lend-engine.com','chris@@lend-engine.com',NULL,NULL,'en',1,NULL,NULL),(47,NULL,NULL,1,NULL,'$2y$13$2jvbKsRk2MzwYQrz1yadHePwHEYPPr9WusnlYMhzzoYj0GWFZkIdW','2020-03-05 13:24:00','swyC07MlttRnSNqR_RW4uIrKNgDPNv1NyaTF-KVL8Lc','2020-03-12 18:37:44','a:0:{}','Chris','Tanner','08450038935','1st Floor','New Bond House','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-03-05 13:24:00',0.00,NULL,0,'chris@brightpearl.com','chris@brightpearl.com','chris@brightpearl.com','chris@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(48,NULL,NULL,1,NULL,'$2y$13$Fba9lQV49Pr2PJzU.SAypOCQvPfCXUOo0cE5kBVJMff6wwzquu5S6','2020-03-05 13:26:07',NULL,NULL,'a:0:{}','Chris','Tanner','08450038935','1st Floor','New Bond House','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-03-05 13:26:07',0.00,NULL,0,'chris@brightpearl.com@','chris@brightpearl.com@','chris@brightpearl.com@','chris@brightpearl.com@',NULL,NULL,'en',1,NULL,NULL),(49,NULL,NULL,1,NULL,'$2y$13$QnP1FiwgtK/evK5BfgBkWeTkyH1FEVyQTPmDusEH4p2xzhbdv6qPe','2020-03-05 13:30:09',NULL,NULL,'a:0:{}','Chris','Tanner','08450038935','1st Floor','New Bond House','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-03-05 13:30:09',0.00,NULL,0,'chriswresfer@brightpearl.com','chriswresfer@brightpearl.com','chriswresfer@brightpearl.com','chriswresfer@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(50,NULL,NULL,1,NULL,'$2y$13$P8Rhth4rDHXLRmeUgO6JseorquVrKWfpVVUWRQJTMQgP01wPvUgUi','2020-03-05 13:32:54',NULL,NULL,'a:0:{}','Chris','Tanner','08450038935','1st Floor','New Bond House','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-03-05 13:32:54',0.00,NULL,0,'chriwecfs@.@brightpearl.com','chriwecfs@.@brightpearl.com','chriwecfs@.@brightpearl.com','chriwecfs@.@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(51,NULL,NULL,1,NULL,'$2y$13$en4y7cXDHAxzZei0qAKLU.19FVAMBoHcPdUDIU/Wuq70Rvkaw6aTW','2020-03-05 13:33:26',NULL,NULL,'a:0:{}','Chris','Tanner','08450038935','1st Floor','New Bond House','test','BS1 4QH','GB',NULL,NULL,NULL,'2020-03-05 13:33:26',0.00,NULL,0,'chrisset@brightpearl.com','chrisset@brightpearl.com','chrisset@brightpearl.com','chrisset@brightpearl.com',NULL,NULL,'en',1,NULL,NULL),(52,1,NULL,1,NULL,'$2y$13$ADr3BW1bXB091e5jtFqHFeEFa15UxIRXuapYNhnUKRdrJH61v00vK',NULL,NULL,NULL,'a:0:{}','Carl','Tans',NULL,NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,'2020-03-12 14:09:32',0.00,NULL,0,'chris+carl@brightpearl.com','chris+carl@brightpearl.com','chris+carl@brightpearl.com','chris+carl@brightpearl.com',NULL,2,'en',1,NULL,NULL),(53,NULL,14,1,NULL,'$2y$13$Ujd6aFWdLTd.VYTXjtN/xePR26sp5aiBjLVb5k2DKuoZ17v7g5ICS','2020-03-18 23:25:13',NULL,NULL,'a:0:{}','Rose','Tanner','07718991320','Dunmore St','Bristol','Avon','BS4','GB',NULL,NULL,NULL,'2020-03-18 22:22:44',0.00,'cus_Gw3PfGELLm0Xat',0,'rose@rosesanderson.com','rose@rosesanderson.com','rose@rosesanderson.com','rose@rosesanderson.com',NULL,NULL,'en',1,NULL,'5e72a8a78c70f');
/*!40000 ALTER TABLE `contact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_field`
--

DROP TABLE IF EXISTS `contact_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_field` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `required` tinyint(1) NOT NULL,
  `show_on_contact_list` tinyint(1) NOT NULL DEFAULT '0',
  `sort` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_field`
--

LOCK TABLES `contact_field` WRITE;
/*!40000 ALTER TABLE `contact_field` DISABLE KEYS */;
INSERT INTO `contact_field` VALUES (1,'Checkbox','checkbox',0,0,0),(2,'ID approved','choice',1,1,0),(3,'Text box','text',0,0,0),(4,'Required checkbox','checkbox',0,0,0),(5,'Textarea','textarea',0,0,0),(6,'Multi select','multiselect',0,0,0);
/*!40000 ALTER TABLE `contact_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_field_select_option`
--

DROP TABLE IF EXISTS `contact_field_select_option`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_field_select_option` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contact_field_id` int(11) DEFAULT NULL,
  `option_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_671A0B61DE129B27` (`contact_field_id`),
  CONSTRAINT `FK_671A0B61DE129B27` FOREIGN KEY (`contact_field_id`) REFERENCES `contact_field` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_field_select_option`
--

LOCK TABLES `contact_field_select_option` WRITE;
/*!40000 ALTER TABLE `contact_field_select_option` DISABLE KEYS */;
INSERT INTO `contact_field_select_option` VALUES (1,2,'Yes',0),(2,2,'Not yet',0),(3,6,'One',0),(4,6,'Two',0);
/*!40000 ALTER TABLE `contact_field_select_option` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_field_value`
--

DROP TABLE IF EXISTS `contact_field_value`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_field_value` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contact_field_id` int(11) DEFAULT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `field_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_587C7171DE129B27` (`contact_field_id`),
  KEY `IDX_587C7171E7A1254A` (`contact_id`),
  CONSTRAINT `FK_587C7171DE129B27` FOREIGN KEY (`contact_field_id`) REFERENCES `contact_field` (`id`),
  CONSTRAINT `FK_587C7171E7A1254A` FOREIGN KEY (`contact_id`) REFERENCES `contact` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_field_value`
--

LOCK TABLES `contact_field_value` WRITE;
/*!40000 ALTER TABLE `contact_field_value` DISABLE KEYS */;
INSERT INTO `contact_field_value` VALUES (1,1,4,''),(2,2,4,'1'),(3,1,8,'1'),(4,2,8,'2'),(5,3,8,'Filled'),(6,1,6,''),(7,2,6,'2'),(8,3,6,'Filled'),(9,1,5,''),(10,2,5,'1'),(11,6,5,'3'),(12,4,5,'1'),(13,3,5,'Filled'),(14,5,5,'tested'),(15,1,3,''),(16,2,3,'2'),(17,6,3,'3'),(18,4,3,'1'),(19,3,3,'Filled'),(20,5,3,'test'),(21,6,6,''),(22,4,6,''),(23,5,6,NULL),(24,1,7,''),(25,2,7,'1'),(26,6,7,''),(27,4,7,''),(28,3,7,NULL),(29,5,7,NULL),(30,1,2,''),(31,2,2,'1'),(32,6,2,''),(33,4,2,''),(34,3,2,NULL),(35,5,2,NULL),(36,6,4,''),(37,4,4,''),(38,3,4,NULL),(39,5,4,NULL),(40,1,1,''),(41,2,1,'1'),(42,6,1,''),(43,4,1,''),(44,3,1,NULL),(45,5,1,NULL),(46,1,39,''),(47,2,39,NULL),(48,6,39,''),(49,4,39,''),(50,3,39,NULL),(51,5,39,NULL),(52,1,40,''),(53,2,40,'1'),(54,6,40,''),(55,4,40,''),(56,3,40,NULL),(57,5,40,NULL),(58,1,41,''),(59,2,41,'1'),(60,6,41,''),(61,4,41,''),(62,3,41,NULL),(63,5,41,NULL),(64,1,18,''),(65,2,18,'1'),(66,6,18,''),(67,4,18,''),(68,3,18,NULL),(69,5,18,NULL),(70,1,52,''),(71,2,52,NULL),(72,6,52,''),(73,4,52,''),(74,3,52,NULL),(75,5,52,NULL),(76,1,53,''),(77,2,53,'1'),(78,6,53,''),(79,4,53,''),(80,3,53,NULL),(81,5,53,NULL);
/*!40000 ALTER TABLE `contact_field_value` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deposit`
--

DROP TABLE IF EXISTS `deposit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deposit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_by` int(11) DEFAULT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `loan_row_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_95DB9D3978219C8F` (`loan_row_id`),
  KEY `IDX_95DB9D39DE12AB56` (`created_by`),
  KEY `IDX_95DB9D39E7A1254A` (`contact_id`),
  CONSTRAINT `FK_95DB9D3978219C8F` FOREIGN KEY (`loan_row_id`) REFERENCES `loan_row` (`id`),
  CONSTRAINT `FK_95DB9D39DE12AB56` FOREIGN KEY (`created_by`) REFERENCES `contact` (`id`),
  CONSTRAINT `FK_95DB9D39E7A1254A` FOREIGN KEY (`contact_id`) REFERENCES `contact` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deposit`
--

LOCK TABLES `deposit` WRITE;
/*!40000 ALTER TABLE `deposit` DISABLE KEYS */;
INSERT INTO `deposit` VALUES (1,4,4,1,'2019-11-11 15:05:42',20.00,20.00),(2,1,4,37,'2019-11-27 09:52:42',5.00,0.00),(3,1,8,39,'2019-11-27 09:54:48',5.00,0.00),(4,1,4,45,'2019-12-02 13:15:16',5.00,0.00),(5,1,4,92,'2020-02-26 10:05:43',20.00,0.00),(6,1,41,120,'2020-03-11 09:21:44',10.00,0.00),(7,1,6,126,'2020-03-16 10:11:28',10.00,0.00),(8,1,4,162,'2020-03-19 19:08:21',5.00,0.00);
/*!40000 ALTER TABLE `deposit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event`
--

DROP TABLE IF EXISTS `event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT NULL,
  `date` date NOT NULL,
  `time_from` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time_to` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time_changeover` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `title` varchar(256) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facebook_url` varchar(256) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `max_attendees` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `is_bookable` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_EA741FC3F6BD1646` (`site_id`),
  KEY `IDX_EA741FC3DE12AB56` (`created_by`),
  CONSTRAINT `FK_EA741FC3DE12AB56` FOREIGN KEY (`created_by`) REFERENCES `contact` (`id`),
  CONSTRAINT `FK_EA741FC3F6BD1646` FOREIGN KEY (`site_id`) REFERENCES `site` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event`
--

LOCK TABLES `event` WRITE;
/*!40000 ALTER TABLE `event` DISABLE KEYS */;
INSERT INTO `event` VALUES (4,1,'2020-02-15','0700','0900','o',NULL,1,'Demo event',NULL,'PUBLISHED',NULL,0.00,0,'2020-02-13 14:07:13',1),(6,1,'2020-02-13','1300','1600','o',NULL,1,'Launch party',NULL,'PUBLISHED',NULL,0.00,0,'2020-02-25 15:37:07',1),(7,3,'2020-02-19','0900','1000','e',NULL,1,'Power tools induction',NULL,'PUBLISHED','In this session we\'ll show you how to use the all the power tools in the workshop.\r\nBring your student ID.',10.00,10,'2020-02-25 15:38:20',1),(8,2,'2020-03-10','1000','1200','o',NULL,1,NULL,NULL,'',NULL,0.00,0,'2020-03-06 11:15:50',1),(9,2,'2020-03-14','0700','0900','o',NULL,1,NULL,NULL,'',NULL,0.00,0,'2020-03-06 11:16:14',1);
/*!40000 ALTER TABLE `event` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ext_translations`
--

DROP TABLE IF EXISTS `ext_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ext_translations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locale` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `object_class` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `field` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `foreign_key` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lookup_unique_idx` (`locale`,`object_class`,`field`,`foreign_key`),
  KEY `translations_lookup_idx` (`locale`,`object_class`,`foreign_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ext_translations`
--

LOCK TABLES `ext_translations` WRITE;
/*!40000 ALTER TABLE `ext_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `ext_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `file_attachment`
--

DROP TABLE IF EXISTS `file_attachment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `file_attachment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) DEFAULT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `file_name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int(11) NOT NULL,
  `send_to_member` int(11) NOT NULL,
  `maintenance_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C0B7020D126F525E` (`item_id`),
  KEY `IDX_C0B7020DE7A1254A` (`contact_id`),
  KEY `IDX_C0B7020DF6C202BC` (`maintenance_id`),
  CONSTRAINT `FK_C0B7020D126F525E` FOREIGN KEY (`item_id`) REFERENCES `inventory_item` (`id`),
  CONSTRAINT `FK_C0B7020DE7A1254A` FOREIGN KEY (`contact_id`) REFERENCES `contact` (`id`),
  CONSTRAINT `FK_C0B7020DF6C202BC` FOREIGN KEY (`maintenance_id`) REFERENCES `maintenance` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `file_attachment`
--

LOCK TABLES `file_attachment` WRITE;
/*!40000 ALTER TABLE `file_attachment` DISABLE KEYS */;
INSERT INTO `file_attachment` VALUES (1,1014,NULL,'5dcd3cfb9e9a8-66projectorlcdvivibrightgp90up.pdf',2325554,1,NULL),(2,1015,NULL,'5dcd3cfb9e9a8-66projectorlcdvivibrightgp90up.pdf',2325554,1,NULL),(3,NULL,NULL,'5e552b6988347-pat-983745.pdf',155246,0,14),(4,1007,NULL,'5e564c6c8bc18-mrhh350wfltmanleng.pdf',4079315,1,NULL),(5,1004,NULL,'5e5cec4bd6584-mrhh350wfltmanleng.pdf',4079315,0,NULL);
/*!40000 ALTER TABLE `file_attachment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `image`
--

DROP TABLE IF EXISTS `image`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `image` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inventory_item_id` int(11) DEFAULT NULL,
  `image_name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C53D045F536BF4A2` (`inventory_item_id`),
  CONSTRAINT `FK_C53D045F536BF4A2` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_item` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `image`
--

LOCK TABLES `image` WRITE;
/*!40000 ALTER TABLE `image` DISABLE KEYS */;
INSERT INTO `image` VALUES (1,1004,'5dc96f5822b0f.jpg'),(2,1004,'5dc96f5ebd85a.jpg'),(3,1004,'5dc96f6532008.jpg'),(4,1004,'5dc96f6b7f851.jpg'),(5,1004,'5dc96f7218ae5.jpg'),(6,1010,'5dcc0e98c06bc.jpg'),(7,1011,'5dcc0ec8e2064.jpg'),(8,1014,'5dce95f8f1689.jpg'),(9,1015,'5dce95f8f1689.jpg'),(10,1016,'5dcc0ec8e2064.jpg'),(11,1006,'5de512e9cb117.jpg'),(13,1017,'5e31794150528.jpg'),(14,1018,'5e32ac8078080.jpg'),(15,1022,'5e4cf8f3b7fbb.jpg'),(16,1026,'5dcc0e98c06bc.jpg'),(17,1027,'5dcc0e98c06bc.jpg'),(18,1028,'5dcc0e98c06bc.jpg'),(19,1013,'5e5527c2d7b3e.jpg'),(20,1007,'5e5632fb0a13b.jpg'),(21,1008,'5e5d182cc5d9e.jpg'),(22,1029,'5e5d18cb5e10f.jpg'),(23,1030,'5e5d18e6bd308.jpg'),(24,1031,'5e5d190782eee.jpg'),(25,1005,'5e5d1946f09e2.jpg'),(26,1032,'5e5d18e6bd308.jpg'),(27,1033,'5e5d18e6bd308.jpg'),(28,1034,'5e4cf8f3b7fbb.jpg');
/*!40000 ALTER TABLE `image` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_item`
--

DROP TABLE IF EXISTS `inventory_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_by` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `current_location_id` int(11) DEFAULT NULL,
  `item_condition` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keywords` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `brand` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `care_information` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `component_information` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `loan_fee` decimal(10,2) DEFAULT NULL,
  `max_loan_days` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `show_on_website` tinyint(1) NOT NULL DEFAULT '1',
  `serial` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price_cost` decimal(10,2) DEFAULT NULL,
  `price_sell` decimal(10,2) DEFAULT NULL,
  `image_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `short_url` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_sector` int(11) DEFAULT NULL,
  `is_reservable` tinyint(1) NOT NULL DEFAULT '1',
  `deposit_amount` decimal(10,2) DEFAULT NULL,
  `item_type` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `donated_by` int(11) DEFAULT NULL,
  `owned_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_55BDEA30DE12AB56` (`created_by`),
  KEY `IDX_55BDEA3089EEAF91` (`assigned_to`),
  KEY `IDX_55BDEA30B8998A57` (`current_location_id`),
  KEY `IDX_55BDEA30B10C9EB3` (`item_condition`),
  KEY `IDX_55BDEA30CD5AAC27` (`item_sector`),
  KEY `IDX_55BDEA303C2B095F` (`donated_by`),
  KEY `IDX_55BDEA308BBCDCA8` (`owned_by`),
  CONSTRAINT `FK_55BDEA303C2B095F` FOREIGN KEY (`donated_by`) REFERENCES `contact` (`id`),
  CONSTRAINT `FK_55BDEA3089EEAF91` FOREIGN KEY (`assigned_to`) REFERENCES `contact` (`id`),
  CONSTRAINT `FK_55BDEA308BBCDCA8` FOREIGN KEY (`owned_by`) REFERENCES `contact` (`id`),
  CONSTRAINT `FK_55BDEA30B10C9EB3` FOREIGN KEY (`item_condition`) REFERENCES `item_condition` (`id`),
  CONSTRAINT `FK_55BDEA30B8998A57` FOREIGN KEY (`current_location_id`) REFERENCES `inventory_location` (`id`),
  CONSTRAINT `FK_55BDEA30DE12AB56` FOREIGN KEY (`created_by`) REFERENCES `contact` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1046 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_item`
--

LOCK TABLES `inventory_item` WRITE;
/*!40000 ALTER TABLE `inventory_item` DISABLE KEYS */;
INSERT INTO `inventory_item` VALUES (1000,NULL,NULL,4,NULL,'2019-11-11 14:21:34','2019-11-11 15:23:06','Test item',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,'loan',NULL,NULL),(1001,1,NULL,2,1,'2019-11-11 14:21:37','2019-11-11 15:23:06','Test item name','SKU-HERE','','Comma, separated, keywords','Sony','','',1.50,4,0,1,'7978134691348','Short description',1.99,2.99,NULL,NULL,33,1,NULL,'loan',NULL,NULL),(1002,1,NULL,2,1,'2019-11-11 14:21:38','2019-11-11 15:23:06','CopyItem','SKU-617212840','','Comma, separated, keywords','DEWALT','','',1.50,4,0,1,'',NULL,1.99,2.99,NULL,NULL,33,1,0.00,'loan',NULL,NULL),(1003,1,NULL,2,1,'2019-11-11 14:21:39','2019-11-11 15:23:06','CopyItem','SKU-617212840','','Comma, separated, keywords','DEWALT','','',1.50,4,0,1,'',NULL,1.99,2.99,NULL,NULL,33,1,0.00,'loan',NULL,NULL),(1004,1,NULL,2,1,'2019-11-11 14:25:17','2020-03-02 11:22:11','Petzl Fall Arrest Kit','PT837645','',NULL,'','','',10.00,7,1,1,'',NULL,NULL,NULL,'5dc96f5822b0f.jpg',NULL,8,1,20.00,'loan',NULL,NULL),(1005,1,NULL,4,1,'2019-11-11 15:20:56','2020-03-02 14:33:58','Camera tripod','TRIPOD','',NULL,'','','',NULL,NULL,1,1,'',NULL,NULL,NULL,'5e5d1946f09e2.jpg',NULL,1279,0,NULL,'loan',NULL,NULL),(1006,1,NULL,4,1,'2019-11-11 15:21:10','2020-03-12 12:58:46','Kit part B','KIT-B','',NULL,'','','',NULL,NULL,1,1,'',NULL,NULL,NULL,'5de512e9cb117.jpg',NULL,33,1,NULL,'loan',5,NULL),(1007,1,NULL,4,1,'2019-11-11 15:24:54','2020-03-12 13:04:57','Carpet cleaner','CC01','',NULL,'Karcher','Ensure fluid is emptied before storage.','- cleaner\r\n- mains extension cord\r\n- 3 head attachments',1.00,7,1,1,'9824572497',NULL,NULL,NULL,'5e5632fb0a13b.jpg',NULL,500081,1,NULL,'loan',NULL,NULL),(1008,1,NULL,4,1,'2019-11-11 16:39:10','2020-03-02 14:29:14','Beginners photography kit','KIT','',NULL,'Canon','','',NULL,NULL,1,1,'',NULL,NULL,NULL,'5e5d182cc5d9e.jpg',NULL,33,1,NULL,'kit',NULL,NULL),(1009,1,NULL,6,2,'2019-11-13 10:00:41','2020-03-02 12:55:07','Olaf 4-man tent','TT-234','Full description here',NULL,'Olaf','Care information here','Components here',NULL,NULL,1,1,'','Don\'t forget the pegs',NULL,NULL,NULL,NULL,1022,1,NULL,'loan',NULL,NULL),(1010,1,NULL,4,1,'2019-11-13 13:54:15','2020-02-25 14:03:27','DEWALT 18V XR BRUSHLESS CORDLESS COMBI DRILL','DCD778M2T-SFGB','',NULL,'','','',NULL,NULL,1,1,'',NULL,NULL,NULL,'5dcc0e98c06bc.jpg',NULL,3974,1,5.00,'loan',NULL,NULL),(1011,1,NULL,NULL,1,'2019-11-13 14:10:13','2020-01-30 13:49:31','DEWALT 18V XR BRUSHLESS 1/4\" ROUTER','DCW604NT-XJ','',NULL,'','','',NULL,NULL,0,1,'',NULL,NULL,NULL,'5dcc0ec8e2064.jpg',NULL,3673,1,NULL,'stock',NULL,NULL),(1012,1,NULL,4,1,'2019-11-14 11:29:37','2020-01-29 11:46:19','Carpet dryer','','',NULL,'','','',NULL,NULL,0,1,'',NULL,NULL,NULL,NULL,NULL,235920,1,NULL,'loan',NULL,NULL),(1013,1,NULL,4,1,'2019-11-14 11:32:16','2020-03-12 13:00:35','Mower','','',NULL,'','','',10.00,NULL,1,1,'',NULL,500.00,600.00,'5e5527c2d7b3e.jpg',NULL,694,1,20.00,'loan',NULL,NULL),(1014,1,NULL,NULL,2,'2019-11-14 11:39:23','2020-03-11 14:15:44','Acer P1150 Projector (sold)','EKS0002','',NULL,'','','',0.00,NULL,1,0,'234468679873426',NULL,10.00,100.00,'5dce95f8f1689.jpg',NULL,396,1,NULL,'stock',NULL,NULL),(1015,1,NULL,1,1,'2019-11-19 10:34:28','2020-03-11 14:15:31','Acer P1150 Projector','EKS0001','',NULL,'','','',0.00,NULL,1,1,'',NULL,5.00,100.00,'5dce95f8f1689.jpg',NULL,396,1,NULL,'stock',NULL,NULL),(1016,1,NULL,3,1,'2019-11-19 10:35:19','2020-02-25 14:09:32','DEWALT 18V XR BRUSHLESS 1/4\" ROUTER','DCW604NT-XJ','',NULL,'','','',NULL,NULL,1,1,'',NULL,NULL,NULL,'5dcc0ec8e2064.jpg',NULL,3673,1,NULL,'loan',NULL,NULL),(1017,1,NULL,2,1,'2020-01-29 11:32:32','2020-01-29 15:50:46','Grinding disc','SKU0034','A thin grinding disc',NULL,'','','',NULL,NULL,1,1,'','Admindescription',2.00,5.00,'5e31794150528.jpg',NULL,3478,1,NULL,'stock',NULL,NULL),(1018,1,NULL,2,1,'2020-01-30 10:14:19','2020-01-30 13:44:25','Carpet cleaning fluid','','',NULL,'','','',NULL,NULL,1,1,'',NULL,NULL,11.99,'5e32ac8078080.jpg',NULL,33,1,NULL,'stock',NULL,NULL),(1019,1,NULL,2,1,'2020-01-30 10:23:37','2020-01-30 10:23:37','Sandpaper 120','','',NULL,'','','',NULL,NULL,1,1,'',NULL,NULL,1.50,NULL,NULL,33,1,NULL,'stock',NULL,NULL),(1020,1,NULL,NULL,1,'2020-01-30 13:24:40','2020-01-30 13:25:19','Loan item (sold)','','',NULL,'','','',NULL,NULL,1,1,'',NULL,NULL,NULL,NULL,NULL,187,1,NULL,'stock',NULL,NULL),(1021,1,NULL,NULL,1,'2020-01-30 13:33:01','2020-01-30 13:33:24','Vest (sold)','','',NULL,'','','',NULL,NULL,1,0,'',NULL,NULL,NULL,NULL,NULL,2389,1,NULL,'stock',NULL,NULL),(1022,1,NULL,4,1,'2020-01-30 14:13:32','2020-03-12 13:13:04','Ball','EKS0003','',NULL,'','','',NULL,NULL,1,1,'',NULL,NULL,NULL,'5e4cf8f3b7fbb.jpg',NULL,33,1,NULL,'loan',3,NULL),(1023,1,NULL,4,1,'2020-01-30 15:21:44','2020-03-18 15:07:02','Uke','','',NULL,'','','',NULL,NULL,1,1,'',NULL,NULL,NULL,NULL,NULL,77,1,NULL,'loan',NULL,NULL),(1024,1,NULL,4,1,'2020-01-30 15:25:47','2020-03-12 13:05:26','Scope','','',NULL,'','','',NULL,NULL,1,1,'',NULL,NULL,NULL,NULL,NULL,4040,1,NULL,'loan',NULL,NULL),(1025,1,NULL,2,1,'2020-02-13 09:27:10','2020-02-13 09:27:10','Non borrowable item','','',NULL,'','','',5.00,NULL,1,1,'',NULL,NULL,NULL,NULL,NULL,1484,0,NULL,'loan',NULL,NULL),(1026,1,NULL,2,1,'2020-02-25 13:30:57','2020-02-25 14:03:27','DEWALT 18V XR BRUSHLESS CORDLESS COMBI DRILL','DCD778M2T-SFGB','',NULL,'','','',NULL,NULL,1,1,'',NULL,NULL,NULL,'5dcc0e98c06bc.jpg',NULL,3974,1,5.00,'loan',NULL,NULL),(1027,1,NULL,5,3,'2020-02-25 13:30:57','2020-02-25 14:03:27','DEWALT 18V XR BRUSHLESS CORDLESS COMBI DRILL','DCD778M2T-SFGB','',NULL,'','','',NULL,NULL,1,1,'',NULL,NULL,NULL,'5dcc0e98c06bc.jpg',NULL,3974,1,5.00,'loan',NULL,NULL),(1028,1,NULL,2,1,'2020-02-25 13:30:57','2020-02-25 14:03:27','DEWALT 18V XR BRUSHLESS CORDLESS COMBI DRILL','DCD778M2T-SFGB','',NULL,'','','',NULL,NULL,1,1,'',NULL,NULL,NULL,'5dcc0e98c06bc.jpg',NULL,3974,1,5.00,'loan',NULL,NULL),(1029,1,NULL,1,2,'2020-03-02 14:31:35','2020-03-19 10:20:49','Canon EOS body','EKS0006','Full description',NULL,'','Care information','Component list',NULL,NULL,1,1,'',NULL,NULL,NULL,'5e5d18cb5e10f.jpg',NULL,33,1,NULL,'loan',NULL,NULL),(1030,1,NULL,5,1,'2020-03-02 14:32:02','2020-03-02 14:37:16','Camera bag','','',NULL,'','','',NULL,NULL,1,1,'',NULL,NULL,NULL,'5e5d18e6bd308.jpg',NULL,166,1,NULL,'loan',NULL,NULL),(1031,1,NULL,4,1,'2020-03-02 14:32:35','2020-03-18 15:08:52','Canon lens kit','EKS0007','',NULL,'Canon','','',NULL,NULL,1,1,'',NULL,NULL,NULL,'5e5d190782eee.jpg',NULL,4432,1,NULL,'loan',NULL,NULL),(1032,1,NULL,4,1,'2020-03-02 14:36:14','2020-03-18 15:06:25','Camera bag','','',NULL,'','','',NULL,NULL,1,1,'',NULL,NULL,NULL,'5e5d18e6bd308.jpg',NULL,166,1,NULL,'loan',NULL,NULL),(1033,1,NULL,3,1,'2020-03-02 14:36:14','2020-03-02 14:36:23','Camera bag','','',NULL,'','','',NULL,NULL,1,1,'',NULL,NULL,NULL,'5e5d18e6bd308.jpg',NULL,166,1,NULL,'loan',NULL,NULL),(1034,1,NULL,2,1,'2020-03-11 14:16:01','2020-03-11 14:16:01','Ball','EKS0004','',NULL,'','','',NULL,NULL,1,1,'',NULL,NULL,NULL,'5e4cf8f3b7fbb.jpg',NULL,33,1,NULL,'loan',3,NULL),(1035,1,NULL,4,1,'2020-03-12 11:38:04','2020-03-12 11:47:00','Juggling kit','EKS0005','',NULL,'','','',NULL,NULL,1,1,'',NULL,NULL,NULL,NULL,NULL,33,0,NULL,'kit',NULL,NULL),(1036,1,NULL,NULL,1,'2020-03-18 15:14:05','2020-03-18 21:15:19','exrfgergrf','EKS0008','',NULL,'','','',NULL,NULL,0,1,'',NULL,NULL,NULL,NULL,NULL,187,1,NULL,'service',NULL,NULL),(1037,1,NULL,4,1,'2020-03-18 16:18:48','2020-03-18 16:18:48','Consultancy session','EKS0009','',NULL,'','','',NULL,NULL,1,1,'',NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,'service',NULL,NULL),(1038,NULL,NULL,NULL,NULL,'2020-03-18 21:10:34','2020-03-18 21:15:28','xfefdf',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,'service',NULL,NULL),(1039,NULL,NULL,NULL,NULL,'2020-03-18 21:10:38','2020-03-18 21:10:38','ecrfxfd',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,'service',NULL,NULL),(1040,NULL,NULL,NULL,1,'2020-03-18 21:21:14','2020-03-18 21:31:28','Checked','EKS0010','',NULL,'','','',NULL,NULL,1,1,'',NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,'service',NULL,NULL),(1041,NULL,NULL,NULL,NULL,'2020-03-18 21:34:54','2020-03-18 21:35:31','ecrfgfgdxf',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,'service',NULL,NULL),(1042,NULL,NULL,NULL,NULL,'2020-03-18 21:37:48','2020-03-18 21:37:48','exfsdf',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'service',NULL,NULL),(1043,NULL,NULL,NULL,NULL,'2020-03-18 21:38:15','2020-03-18 21:38:15','sdfxsdfd',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'service',NULL,NULL),(1044,NULL,NULL,NULL,1,'2020-03-18 21:41:20','2020-03-18 23:24:51','Shipping','EKS0011','',NULL,'','','',NULL,NULL,1,0,'',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'service',NULL,NULL),(1045,1,NULL,7,1,'2020-03-19 18:49:14','2020-03-19 19:09:24','Deposit item','EKS0012','',NULL,'GoPro','','',1.00,NULL,1,1,'DEPO9837453',NULL,NULL,NULL,NULL,NULL,33,1,5.00,'loan',NULL,NULL);
/*!40000 ALTER TABLE `inventory_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_item_check_in_prompt`
--

DROP TABLE IF EXISTS `inventory_item_check_in_prompt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_item_check_in_prompt` (
  `inventory_item_id` int(11) NOT NULL,
  `check_in_prompt_id` int(11) NOT NULL,
  PRIMARY KEY (`inventory_item_id`,`check_in_prompt_id`),
  KEY `IDX_137042F3536BF4A2` (`inventory_item_id`),
  KEY `IDX_137042F3AE6C6390` (`check_in_prompt_id`),
  CONSTRAINT `FK_137042F3536BF4A2` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_item` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_137042F3AE6C6390` FOREIGN KEY (`check_in_prompt_id`) REFERENCES `check_in_prompt` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_item_check_in_prompt`
--

LOCK TABLES `inventory_item_check_in_prompt` WRITE;
/*!40000 ALTER TABLE `inventory_item_check_in_prompt` DISABLE KEYS */;
INSERT INTO `inventory_item_check_in_prompt` VALUES (1000,1),(1001,1),(1002,1),(1003,1),(1004,1),(1005,1),(1006,1),(1007,1),(1008,1),(1009,1),(1010,1),(1011,1),(1012,1),(1013,1),(1014,1),(1015,1),(1016,1),(1017,1),(1018,1),(1019,1),(1020,1),(1021,1),(1022,1),(1023,1),(1024,1),(1025,1),(1026,1),(1027,1),(1028,1),(1029,1),(1030,1),(1031,1),(1032,1),(1033,1),(1034,1),(1035,1),(1036,1),(1037,1),(1045,1);
/*!40000 ALTER TABLE `inventory_item_check_in_prompt` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_item_check_out_prompt`
--

DROP TABLE IF EXISTS `inventory_item_check_out_prompt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_item_check_out_prompt` (
  `inventory_item_id` int(11) NOT NULL,
  `check_out_prompt_id` int(11) NOT NULL,
  PRIMARY KEY (`inventory_item_id`,`check_out_prompt_id`),
  KEY `IDX_108CDD46536BF4A2` (`inventory_item_id`),
  KEY `IDX_108CDD46589C168A` (`check_out_prompt_id`),
  CONSTRAINT `FK_108CDD46536BF4A2` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_item` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_108CDD46589C168A` FOREIGN KEY (`check_out_prompt_id`) REFERENCES `check_out_prompt` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_item_check_out_prompt`
--

LOCK TABLES `inventory_item_check_out_prompt` WRITE;
/*!40000 ALTER TABLE `inventory_item_check_out_prompt` DISABLE KEYS */;
INSERT INTO `inventory_item_check_out_prompt` VALUES (1004,1),(1013,2);
/*!40000 ALTER TABLE `inventory_item_check_out_prompt` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_item_maintenance_plan`
--

DROP TABLE IF EXISTS `inventory_item_maintenance_plan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_item_maintenance_plan` (
  `inventory_item_id` int(11) NOT NULL,
  `maintenance_plan_id` int(11) NOT NULL,
  PRIMARY KEY (`inventory_item_id`,`maintenance_plan_id`),
  KEY `IDX_CEF3B94B536BF4A2` (`inventory_item_id`),
  KEY `IDX_CEF3B94B916F4709` (`maintenance_plan_id`),
  CONSTRAINT `FK_CEF3B94B536BF4A2` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_item` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_CEF3B94B916F4709` FOREIGN KEY (`maintenance_plan_id`) REFERENCES `maintenance_plan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_item_maintenance_plan`
--

LOCK TABLES `inventory_item_maintenance_plan` WRITE;
/*!40000 ALTER TABLE `inventory_item_maintenance_plan` DISABLE KEYS */;
INSERT INTO `inventory_item_maintenance_plan` VALUES (1007,1),(1010,1),(1012,1),(1013,3),(1014,2),(1016,1),(1022,2),(1026,1),(1029,4),(1034,2);
/*!40000 ALTER TABLE `inventory_item_maintenance_plan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_item_product_tag`
--

DROP TABLE IF EXISTS `inventory_item_product_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_item_product_tag` (
  `inventory_item_id` int(11) NOT NULL,
  `product_tag_id` int(11) NOT NULL,
  PRIMARY KEY (`inventory_item_id`,`product_tag_id`),
  KEY `IDX_2F6598F5536BF4A2` (`inventory_item_id`),
  KEY `IDX_2F6598F5D8AE22B5` (`product_tag_id`),
  CONSTRAINT `FK_2F6598F5536BF4A2` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_item` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2F6598F5D8AE22B5` FOREIGN KEY (`product_tag_id`) REFERENCES `product_tag` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_item_product_tag`
--

LOCK TABLES `inventory_item_product_tag` WRITE;
/*!40000 ALTER TABLE `inventory_item_product_tag` DISABLE KEYS */;
INSERT INTO `inventory_item_product_tag` VALUES (1007,3),(1009,5),(1010,1),(1010,8),(1011,1),(1016,1),(1017,1),(1026,1),(1026,8),(1027,1),(1027,8),(1028,1),(1028,8),(1037,9),(1045,5);
/*!40000 ALTER TABLE `inventory_item_product_tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_item_site`
--

DROP TABLE IF EXISTS `inventory_item_site`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_item_site` (
  `inventory_item_id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  PRIMARY KEY (`inventory_item_id`,`site_id`),
  KEY `IDX_26CFA477536BF4A2` (`inventory_item_id`),
  KEY `IDX_26CFA477F6BD1646` (`site_id`),
  CONSTRAINT `FK_26CFA477536BF4A2` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_item` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_26CFA477F6BD1646` FOREIGN KEY (`site_id`) REFERENCES `site` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_item_site`
--

LOCK TABLES `inventory_item_site` WRITE;
/*!40000 ALTER TABLE `inventory_item_site` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_item_site` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_location`
--

DROP TABLE IF EXISTS `inventory_location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_location` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `barcode` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL,
  `is_available` tinyint(1) NOT NULL,
  `site` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_site_unique` (`name`,`site`),
  KEY `IDX_EAD4335A694309E4` (`site`),
  CONSTRAINT `FK_EAD4335A694309E4` FOREIGN KEY (`site`) REFERENCES `site` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_location`
--

LOCK TABLES `inventory_location` WRITE;
/*!40000 ALTER TABLE `inventory_location` DISABLE KEYS */;
INSERT INTO `inventory_location` VALUES (1,'On loan',NULL,1,0,1),(2,'In stock',NULL,1,1,1),(3,'Repair',NULL,1,0,1),(4,'In stock',NULL,1,1,2),(5,'Repair',NULL,1,0,2),(6,'In stock',NULL,1,1,3),(7,'In stock',NULL,1,1,4);
/*!40000 ALTER TABLE `inventory_location` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `item_condition`
--

DROP TABLE IF EXISTS `item_condition`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item_condition` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `item_condition`
--

LOCK TABLES `item_condition` WRITE;
/*!40000 ALTER TABLE `item_condition` DISABLE KEYS */;
INSERT INTO `item_condition` VALUES (1,'A - As new',1),(2,'B - Fair',2),(3,'C - Poor',3);
/*!40000 ALTER TABLE `item_condition` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `item_movement`
--

DROP TABLE IF EXISTS `item_movement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item_movement` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_by` int(11) DEFAULT NULL,
  `inventory_item_id` int(11) NOT NULL,
  `inventory_location_id` int(11) NOT NULL,
  `loan_row_id` int(11) DEFAULT NULL,
  `assigned_to_contact_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_98D05D3C78219C8F` (`loan_row_id`),
  KEY `IDX_98D05D3CDE12AB56` (`created_by`),
  KEY `IDX_98D05D3C536BF4A2` (`inventory_item_id`),
  KEY `IDX_98D05D3C72BF1D41` (`inventory_location_id`),
  KEY `IDX_98D05D3C7AA06E72` (`assigned_to_contact_id`),
  CONSTRAINT `FK_98D05D3C536BF4A2` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_item` (`id`),
  CONSTRAINT `FK_98D05D3C72BF1D41` FOREIGN KEY (`inventory_location_id`) REFERENCES `inventory_location` (`id`),
  CONSTRAINT `FK_98D05D3C78219C8F` FOREIGN KEY (`loan_row_id`) REFERENCES `loan_row` (`id`),
  CONSTRAINT `FK_98D05D3C7AA06E72` FOREIGN KEY (`assigned_to_contact_id`) REFERENCES `contact` (`id`),
  CONSTRAINT `FK_98D05D3CDE12AB56` FOREIGN KEY (`created_by`) REFERENCES `contact` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=217 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `item_movement`
--

LOCK TABLES `item_movement` WRITE;
/*!40000 ALTER TABLE `item_movement` DISABLE KEYS */;
INSERT INTO `item_movement` VALUES (1,NULL,1000,4,NULL,NULL,'2019-11-11 14:21:34',NULL),(2,1,1001,3,NULL,NULL,'2019-11-11 14:21:37',NULL),(3,1,1002,2,NULL,NULL,'2019-11-11 14:21:38',NULL),(4,1,1003,2,NULL,NULL,'2019-11-11 14:21:39',NULL),(5,1,1001,2,NULL,NULL,'2019-11-11 14:21:41',NULL),(6,1,1002,2,NULL,NULL,'2019-11-11 14:23:03',NULL),(7,1,1003,2,NULL,NULL,'2019-11-11 14:23:03',NULL),(8,1,1000,4,NULL,NULL,'2019-11-11 14:23:03',NULL),(9,1,1001,2,NULL,NULL,'2019-11-11 14:23:03',NULL),(10,1,1004,2,NULL,NULL,'2019-11-11 14:25:17',NULL),(11,4,1004,1,1,NULL,'2019-11-11 15:05:42',NULL),(12,4,1004,2,NULL,NULL,'2019-11-11 15:17:57',NULL),(13,1,1005,2,NULL,NULL,'2019-11-11 15:20:56',NULL),(14,1,1006,2,NULL,NULL,'2019-11-11 15:21:10',NULL),(15,1,1007,2,NULL,NULL,'2019-11-11 15:24:54',NULL),(16,1,1008,4,NULL,NULL,'2019-11-11 16:39:10',NULL),(17,1,1009,2,NULL,NULL,'2019-11-13 10:00:41',NULL),(18,1,1010,2,NULL,NULL,'2019-11-13 13:54:15',NULL),(19,1,1011,2,NULL,NULL,'2019-11-13 14:10:13',NULL),(20,1,1011,1,3,NULL,'2019-11-14 10:45:51',NULL),(21,1,1009,1,4,NULL,'2019-11-14 11:09:17',NULL),(22,1,1012,4,NULL,NULL,'2019-11-14 11:29:37',NULL),(23,1,1004,3,NULL,NULL,'2019-11-14 11:31:38',NULL),(24,1,1013,4,NULL,NULL,'2019-11-14 11:32:16',NULL),(25,1,1013,1,5,NULL,'2019-11-14 11:32:40',NULL),(26,1,1013,4,NULL,NULL,'2019-11-14 11:33:15',NULL),(27,1,1013,1,6,NULL,'2019-11-14 11:35:08',NULL),(28,1,1013,4,NULL,NULL,'2019-11-14 11:35:19',NULL),(29,1,1014,4,NULL,NULL,'2019-11-14 11:39:23',NULL),(30,1,1014,1,7,NULL,'2019-11-14 11:40:21',NULL),(31,1,1013,1,8,NULL,'2019-11-14 11:48:07',NULL),(32,1,1013,4,NULL,NULL,'2019-11-14 11:48:13',NULL),(33,1,1013,4,NULL,NULL,'2019-11-14 11:48:58',NULL),(34,1,1013,1,9,NULL,'2019-11-14 12:09:45',NULL),(35,1,1014,4,NULL,NULL,'2019-11-14 12:20:16',NULL),(36,1,1013,4,NULL,NULL,'2019-11-14 12:20:24',NULL),(37,1,1014,1,10,NULL,'2019-11-14 12:21:03',NULL),(38,1,1012,1,11,NULL,'2019-11-14 12:21:03',NULL),(39,1,1009,4,NULL,NULL,'2019-11-14 12:45:26',NULL),(40,1,1014,4,NULL,NULL,'2019-11-14 13:06:40',NULL),(41,1,1014,1,13,NULL,'2019-11-15 13:58:19',NULL),(42,1,1015,2,NULL,NULL,'2019-11-19 10:34:28',NULL),(43,1,1016,2,NULL,NULL,'2019-11-19 10:35:19',NULL),(44,1,1016,1,20,NULL,'2019-11-20 11:49:47',NULL),(45,1,1016,4,NULL,NULL,'2019-11-20 11:49:53',NULL),(46,1,1016,1,19,NULL,'2019-11-20 11:50:00',NULL),(47,1,1016,4,NULL,NULL,'2019-11-20 11:50:06',NULL),(48,1,1016,1,21,NULL,'2019-11-20 11:50:29',NULL),(49,1,1015,1,22,NULL,'2019-11-20 11:54:14',NULL),(50,1,1005,1,24,NULL,'2019-11-20 11:54:14',NULL),(51,1,1015,4,NULL,NULL,'2019-11-20 11:54:40',NULL),(52,1,1005,4,NULL,NULL,'2019-11-20 11:54:44',NULL),(53,1,1015,4,NULL,NULL,'2019-11-26 10:13:29',NULL),(54,1,1015,4,NULL,NULL,'2019-11-26 10:29:23',NULL),(55,1,1016,4,NULL,NULL,'2019-11-26 15:28:18',NULL),(56,1,1012,4,NULL,NULL,'2019-11-26 15:40:37',NULL),(57,1,1011,4,NULL,NULL,'2019-11-26 15:41:59',NULL),(58,1,1012,1,27,NULL,'2019-11-26 16:47:08',NULL),(59,1,1012,4,NULL,NULL,'2019-11-26 16:47:17',NULL),(60,1,1015,1,28,NULL,'2019-11-26 16:47:43',NULL),(61,1,1015,4,NULL,NULL,'2019-11-26 16:47:52',NULL),(62,1,1015,1,29,NULL,'2019-11-26 16:56:01',NULL),(63,1,1015,4,NULL,NULL,'2019-11-26 16:56:06',NULL),(64,1,1015,1,30,NULL,'2019-11-26 16:56:36',NULL),(65,1,1015,4,NULL,NULL,'2019-11-26 16:56:43',NULL),(66,1,1013,1,31,NULL,'2019-11-27 09:23:16',NULL),(67,1,1011,1,32,NULL,'2019-11-27 09:23:45',NULL),(68,1,1011,4,NULL,NULL,'2019-11-27 09:23:52',NULL),(69,1,1011,1,33,NULL,'2019-11-27 09:34:47',NULL),(70,1,1011,4,NULL,NULL,'2019-11-27 09:36:25',NULL),(71,1,1013,4,NULL,NULL,'2019-11-27 09:37:32',NULL),(72,1,1010,1,34,NULL,'2019-11-27 09:42:22',NULL),(73,1,1011,1,35,NULL,'2019-11-27 09:42:22',NULL),(74,1,1010,4,NULL,NULL,'2019-11-27 09:43:12',NULL),(75,1,1011,4,NULL,NULL,'2019-11-27 09:43:51',NULL),(76,1,1011,1,36,NULL,'2019-11-27 09:44:12',NULL),(77,1,1016,1,38,NULL,'2019-11-27 09:52:03',NULL),(78,1,1010,1,37,NULL,'2019-11-27 09:52:42',NULL),(79,1,1010,4,NULL,NULL,'2019-11-27 09:53:21',NULL),(80,1,1010,1,39,NULL,'2019-11-27 09:54:48',NULL),(81,1,1010,4,NULL,NULL,'2019-11-27 09:54:59',NULL),(82,1,1016,4,NULL,NULL,'2019-11-27 10:13:22',NULL),(83,1,1016,1,41,NULL,'2019-11-29 11:11:21',NULL),(84,1,1016,4,NULL,NULL,'2019-11-29 11:12:41',NULL),(85,1,1015,1,40,NULL,'2019-11-29 11:13:00',NULL),(86,1,1015,4,NULL,NULL,'2019-11-29 11:14:28',NULL),(87,1,1015,1,42,NULL,'2019-11-29 11:14:42',NULL),(88,1,1015,4,NULL,NULL,'2019-11-29 11:15:46',NULL),(89,1,1015,1,43,NULL,'2019-11-29 11:16:22',NULL),(90,1,1015,4,NULL,NULL,'2019-11-29 11:18:12',NULL),(91,1,1015,1,44,NULL,'2019-11-29 11:19:26',NULL),(92,1,1010,1,45,NULL,'2019-12-02 13:15:16',NULL),(93,1,1010,4,NULL,NULL,'2019-12-02 13:23:58',NULL),(94,1,1006,1,46,NULL,'2019-12-02 13:25:05',NULL),(95,1,1006,4,NULL,NULL,'2019-12-02 13:28:04',NULL),(96,1,1006,1,47,NULL,'2019-12-02 13:28:31',NULL),(97,1,1006,4,NULL,NULL,'2019-12-02 13:30:23',NULL),(98,1,1006,1,48,NULL,'2019-12-02 13:30:52',NULL),(99,1,1015,4,NULL,NULL,'2019-12-10 10:05:00',NULL),(100,1,1006,4,NULL,NULL,'2019-12-16 11:20:05',NULL),(101,1,1011,4,NULL,NULL,'2019-12-16 11:20:33',NULL),(102,1,1011,1,49,NULL,'2019-12-16 11:22:14',NULL),(103,1,1011,4,NULL,NULL,'2019-12-16 11:22:45',NULL),(104,1,1011,1,50,NULL,'2019-12-16 11:24:37',NULL),(105,1,1011,4,NULL,NULL,'2019-12-16 11:25:03',NULL),(106,1,1011,1,51,NULL,'2019-12-16 11:25:44',NULL),(107,1,1011,4,NULL,NULL,'2019-12-16 11:26:09',NULL),(108,1,1011,1,52,NULL,'2019-12-16 11:26:32',NULL),(109,1,1011,4,NULL,NULL,'2019-12-16 11:28:00',NULL),(110,1,1011,1,53,NULL,'2019-12-16 11:28:39',NULL),(111,1,1011,4,NULL,NULL,'2019-12-16 11:28:57',NULL),(112,1,1011,1,54,NULL,'2019-12-16 11:29:24',NULL),(113,1,1011,4,NULL,NULL,'2019-12-16 11:29:37',NULL),(114,1,1017,2,NULL,NULL,'2020-01-29 11:32:32',4),(115,1,1017,4,NULL,NULL,'2020-01-29 11:39:22',2),(116,1,1012,4,NULL,NULL,'2020-01-29 11:46:19',NULL),(117,1,1017,2,55,NULL,'2020-01-29 13:38:27',-1),(118,1,1017,4,NULL,NULL,'2020-01-29 14:22:05',2),(119,1,1017,2,NULL,NULL,'2020-01-29 14:22:41',-1),(120,1,1017,5,NULL,NULL,'2020-01-29 14:33:40',12),(121,1,1017,2,NULL,NULL,'2020-01-29 14:59:47',1),(122,1,1017,2,NULL,NULL,'2020-01-29 15:03:16',1),(123,1,1017,4,NULL,NULL,'2020-01-29 15:03:16',-1),(124,1,1017,6,NULL,NULL,'2020-01-29 15:09:31',2),(125,1,1017,6,NULL,NULL,'2020-01-29 15:09:50',-2),(126,1,1017,5,NULL,NULL,'2020-01-29 15:15:53',-2),(127,1,1017,4,56,NULL,'2020-01-29 15:49:38',-2),(128,1,1017,4,57,NULL,'2020-01-29 16:20:42',-1),(129,1,1017,5,58,NULL,'2020-01-29 16:20:42',-2),(130,1,1017,5,59,NULL,'2020-01-29 16:29:50',-2),(131,1,1017,2,60,NULL,'2020-01-29 16:34:46',-1),(132,1,1017,5,61,NULL,'2020-01-29 16:36:28',-1),(133,1,1017,5,62,NULL,'2020-01-29 16:42:33',-1),(134,1,1015,1,63,NULL,'2020-01-29 16:42:33',NULL),(135,1,1015,2,NULL,NULL,'2020-01-29 16:43:23',NULL),(136,1,1011,1,65,NULL,'2020-01-30 10:10:26',NULL),(137,1,1017,5,66,NULL,'2020-01-30 10:10:26',-2),(138,1,1018,2,NULL,NULL,'2020-01-30 10:14:19',NULL),(139,1,1018,2,NULL,NULL,'2020-01-30 10:18:21',10),(140,1,1015,1,72,NULL,'2020-01-30 13:05:56',NULL),(141,1,1020,2,NULL,NULL,'2020-01-30 13:24:40',NULL),(142,1,1020,1,73,NULL,'2020-01-30 13:25:02',NULL),(143,1,1021,2,NULL,NULL,'2020-01-30 13:33:01',NULL),(144,1,1021,1,74,NULL,'2020-01-30 13:33:17',NULL),(145,1,1022,2,NULL,NULL,'2020-01-30 14:13:32',NULL),(146,1,1022,1,75,NULL,'2020-01-30 14:13:46',NULL),(147,1,1015,4,NULL,NULL,'2020-01-30 15:08:05',3),(148,1,1015,6,NULL,NULL,'2020-01-30 15:08:17',3),(149,1,1015,5,NULL,NULL,'2020-01-30 15:08:31',3),(150,1,1015,3,NULL,NULL,'2020-01-30 15:09:23',13),(151,1,1015,3,NULL,NULL,'2020-01-30 15:09:29',-1),(152,1,1009,1,76,NULL,'2020-01-30 15:13:18',NULL),(153,1,1009,2,NULL,NULL,'2020-01-30 15:13:26',NULL),(154,1,1023,2,NULL,NULL,'2020-01-30 15:21:44',NULL),(155,1,1023,1,77,NULL,'2020-01-30 15:21:59',NULL),(156,1,1023,2,NULL,NULL,'2020-01-30 15:23:54',NULL),(157,1,1024,2,NULL,NULL,'2020-01-30 15:25:47',NULL),(158,1,1024,1,78,NULL,'2020-01-30 15:26:04',NULL),(159,1,1024,2,NULL,NULL,'2020-01-30 15:26:25',NULL),(160,1,1023,1,81,NULL,'2020-01-31 10:20:50',NULL),(161,4,1018,2,83,NULL,'2020-02-03 19:27:40',-1),(162,1,1025,2,NULL,NULL,'2020-02-13 09:27:10',NULL),(163,1,1024,1,87,NULL,'2020-02-25 13:15:34',NULL),(164,1,1026,2,NULL,NULL,'2020-02-25 13:30:57',NULL),(165,1,1027,2,NULL,NULL,'2020-02-25 13:30:57',NULL),(166,1,1028,2,NULL,NULL,'2020-02-25 13:30:57',NULL),(167,1,1027,5,NULL,NULL,'2020-02-25 13:31:21',NULL),(168,1,1016,3,NULL,NULL,'2020-02-25 14:09:32',NULL),(169,1,1013,1,89,NULL,'2020-02-25 14:21:47',NULL),(170,1,1013,6,NULL,NULL,'2020-02-26 10:03:19',NULL),(171,1,1013,1,92,NULL,'2020-02-26 10:05:43',NULL),(172,1,1004,2,NULL,NULL,'2020-02-26 10:18:03',NULL),(173,1,1018,2,90,NULL,'2020-02-26 10:47:10',-1),(174,1,1007,1,91,NULL,'2020-02-26 10:47:10',NULL),(175,1,1009,1,94,NULL,'2020-03-02 11:50:37',NULL),(176,1,1009,6,NULL,NULL,'2020-03-02 12:55:07',NULL),(177,1,1004,2,NULL,NULL,'2020-03-02 12:58:12',NULL),(178,40,1006,1,95,NULL,'2020-03-02 13:38:13',NULL),(179,1,1029,4,NULL,NULL,'2020-03-02 14:31:35',NULL),(180,1,1030,4,NULL,NULL,'2020-03-02 14:32:02',NULL),(181,1,1031,4,NULL,NULL,'2020-03-02 14:32:35',NULL),(182,1,1032,2,NULL,NULL,'2020-03-02 14:36:14',NULL),(183,1,1033,2,NULL,NULL,'2020-03-02 14:36:14',NULL),(184,1,1033,3,NULL,NULL,'2020-03-02 14:36:23',NULL),(185,1,1030,5,NULL,NULL,'2020-03-02 14:37:16',NULL),(186,1,1032,1,119,NULL,'2020-03-06 11:21:37',NULL),(187,1,1031,1,120,NULL,'2020-03-11 09:21:44',NULL),(188,1,1031,4,NULL,NULL,'2020-03-11 09:22:42',NULL),(189,1,1034,2,NULL,NULL,'2020-03-11 14:16:01',NULL),(190,1,1032,4,NULL,NULL,'2020-03-12 12:54:34',NULL),(191,1,1006,4,NULL,NULL,'2020-03-12 12:58:46',NULL),(192,1,1013,4,NULL,NULL,'2020-03-12 13:00:35',NULL),(193,1,1007,4,NULL,NULL,'2020-03-12 13:04:57',NULL),(194,1,1024,4,NULL,NULL,'2020-03-12 13:05:26',NULL),(195,1,1023,4,NULL,NULL,'2020-03-12 13:05:47',NULL),(196,1,1022,4,NULL,NULL,'2020-03-12 13:13:04',NULL),(197,1,1031,1,121,NULL,'2020-03-12 14:08:24',NULL),(198,1,1031,4,NULL,NULL,'2020-03-12 14:09:06',NULL),(199,1,1032,1,118,NULL,'2020-03-12 14:18:15',NULL),(200,1,1023,1,110,NULL,'2020-03-12 14:19:19',NULL),(201,1,1017,2,111,NULL,'2020-03-12 14:19:19',-1),(202,1,1031,1,126,NULL,'2020-03-16 10:11:28',NULL),(203,1,1029,1,127,NULL,'2020-03-17 10:32:46',NULL),(204,1,1029,5,NULL,NULL,'2020-03-17 10:33:26',NULL),(205,1,1029,4,NULL,NULL,'2020-03-17 10:34:33',NULL),(206,1,1032,4,NULL,NULL,'2020-03-18 13:43:13',NULL),(207,1,1032,1,122,NULL,'2020-03-18 13:43:31',NULL),(208,1,1032,4,NULL,NULL,'2020-03-18 13:46:55',NULL),(209,1,1032,1,115,NULL,'2020-03-18 14:54:00',NULL),(210,1,1032,4,NULL,NULL,'2020-03-18 15:06:25',NULL),(211,1,1023,4,NULL,NULL,'2020-03-18 15:07:02',NULL),(212,1,1031,4,NULL,NULL,'2020-03-18 15:07:22',NULL),(213,53,1029,1,154,NULL,'2020-03-18 23:28:16',NULL),(214,1,1045,7,NULL,NULL,'2020-03-19 18:49:14',NULL),(215,1,1045,1,162,NULL,'2020-03-19 19:08:21',NULL),(216,1,1045,7,NULL,NULL,'2020-03-19 19:09:24',NULL);
/*!40000 ALTER TABLE `item_movement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kit_component`
--

DROP TABLE IF EXISTS `kit_component`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kit_component` (
  `item_id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL,
  `component_quantity` int(11) NOT NULL,
  PRIMARY KEY (`item_id`,`component_id`),
  KEY `IDX_FAA63CAB126F525E` (`item_id`),
  KEY `IDX_FAA63CABE2ABAFFF` (`component_id`),
  CONSTRAINT `FK_FAA63CAB126F525E` FOREIGN KEY (`item_id`) REFERENCES `inventory_item` (`id`),
  CONSTRAINT `FK_FAA63CABE2ABAFFF` FOREIGN KEY (`component_id`) REFERENCES `inventory_item` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kit_component`
--

LOCK TABLES `kit_component` WRITE;
/*!40000 ALTER TABLE `kit_component` DISABLE KEYS */;
INSERT INTO `kit_component` VALUES (1008,1005,1),(1008,1029,1),(1008,1030,1),(1008,1031,1),(1035,1022,1);
/*!40000 ALTER TABLE `kit_component` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan`
--

DROP TABLE IF EXISTS `loan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contact_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `datetime_out` datetime NOT NULL,
  `datetime_in` datetime NOT NULL,
  `reference` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_fee` decimal(10,2) NOT NULL,
  `created_at_site` int(11) DEFAULT NULL,
  `collect_from` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C5D30D03E7A1254A` (`contact_id`),
  KEY `IDX_C5D30D03DE12AB56` (`created_by`),
  KEY `IDX_C5D30D0394304B5C` (`created_at_site`),
  CONSTRAINT `FK_C5D30D0394304B5C` FOREIGN KEY (`created_at_site`) REFERENCES `site` (`id`),
  CONSTRAINT `FK_C5D30D03DE12AB56` FOREIGN KEY (`created_by`) REFERENCES `contact` (`id`),
  CONSTRAINT `FK_C5D30D03E7A1254A` FOREIGN KEY (`contact_id`) REFERENCES `contact` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan`
--

LOCK TABLES `loan` WRITE;
/*!40000 ALTER TABLE `loan` DISABLE KEYS */;
INSERT INTO `loan` VALUES (1000,4,4,'CLOSED','2019-11-11 15:03:38','2019-11-11 15:05:42','2019-11-11 15:17:57',NULL,10.00,NULL,NULL),(1001,2,2,'CANCELLED','2019-11-14 09:20:33','2019-11-14 15:20:00','2019-11-21 23:00:00',NULL,0.50,NULL,NULL),(1002,2,1,'CLOSED','2019-11-14 10:45:35','2019-11-14 10:45:51','2019-11-26 15:42:00',NULL,1.00,NULL,NULL),(1003,6,6,'CLOSED','2019-11-14 11:03:51','2019-11-14 11:09:17','2019-11-14 12:45:26',NULL,0.50,NULL,NULL),(1004,2,1,'CLOSED','2019-11-14 11:32:35','2019-11-14 11:32:40','2019-11-14 11:33:15',NULL,1.00,NULL,NULL),(1005,2,1,'CLOSED','2019-11-14 11:34:47','2019-11-14 11:35:08','2019-11-14 11:35:19',NULL,1.00,NULL,NULL),(1006,6,1,'CLOSED','2019-11-14 11:40:15','2019-11-14 11:40:21','2019-11-14 12:20:16',NULL,1.00,NULL,NULL),(1007,6,1,'CLOSED','2019-11-14 11:47:58','2019-11-14 11:48:07','2019-11-14 11:48:13',NULL,1.00,NULL,NULL),(1008,6,1,'CLOSED','2019-11-14 11:59:59','2019-11-14 12:09:45','2019-11-14 12:20:24',NULL,2.00,NULL,NULL),(1009,6,1,'OVERDUE','2019-11-14 12:20:57','2019-11-14 12:21:03','2019-11-26 23:00:00',NULL,2.71,NULL,NULL),(1010,3,1,'CANCELLED','2019-11-14 12:45:49','2019-11-18 20:00:00','2019-11-27 23:00:00',NULL,1.29,NULL,NULL),(1011,2,1,'CLOSED','2019-11-15 12:11:58','2019-11-15 13:58:19','2019-11-22 23:00:00',NULL,0.00,NULL,NULL),(1014,6,1,'CANCELLED','2019-11-20 11:16:33','2019-11-20 17:16:00','2019-11-27 23:00:00',NULL,1.00,NULL,NULL),(1015,6,1,'CANCELLED','2019-11-20 11:28:55','2019-11-20 17:28:00','2019-11-20 23:00:00',NULL,0.00,NULL,NULL),(1016,6,1,'CANCELLED','2019-11-20 11:33:29','2019-11-20 17:33:00','2019-11-27 23:00:00',NULL,1.00,NULL,NULL),(1017,6,1,'CLOSED','2019-11-20 11:40:41','2019-11-20 11:50:00','2019-11-20 11:50:06',NULL,1.00,NULL,NULL),(1018,6,1,'CLOSED','2019-11-20 11:49:38','2019-11-20 11:49:47','2019-11-20 11:49:53',NULL,1.00,NULL,NULL),(1019,6,1,'CLOSED','2019-11-20 11:50:22','2019-11-20 11:50:29','2019-11-26 15:28:19',NULL,1.00,NULL,NULL),(1020,6,1,'CLOSED','2019-11-20 11:54:02','2019-11-20 11:54:14','2019-11-20 11:54:44',NULL,5.00,NULL,NULL),(1021,2,1,'CANCELLED','2019-11-26 12:01:33','2019-11-26 18:01:00','2019-11-28 23:00:00',NULL,0.29,NULL,NULL),(1022,2,1,'CANCELLED','2019-11-26 12:03:08','2019-11-26 18:03:00','2019-11-29 23:00:00',NULL,0.43,NULL,NULL),(1023,2,1,'CLOSED','2019-11-26 16:47:05','2019-11-26 16:47:08','2019-11-26 16:47:18',NULL,0.29,NULL,NULL),(1024,4,1,'CLOSED','2019-11-26 16:47:40','2019-11-26 16:47:43','2019-11-26 16:47:53',NULL,0.00,NULL,NULL),(1025,4,1,'OVERDUE','2019-11-26 16:55:58','2019-11-26 16:56:01','2019-11-28 17:00:00',NULL,0.00,NULL,NULL),(1026,4,1,'CLOSED','2019-11-26 16:56:33','2019-11-26 16:56:36','2019-11-26 16:56:44',NULL,0.00,NULL,NULL),(1027,6,1,'CLOSED','2019-11-27 09:23:05','2019-11-27 09:23:16','2019-11-27 09:37:33',NULL,0.29,NULL,NULL),(1028,4,1,'CLOSED','2019-11-27 09:23:43','2019-11-27 09:23:45','2019-11-27 09:23:52',NULL,0.00,NULL,NULL),(1029,4,1,'CLOSED','2019-11-27 09:34:41','2019-11-27 09:34:47','2019-11-27 09:36:26',NULL,0.00,NULL,NULL),(1030,4,1,'CLOSED','2019-11-27 09:42:06','2019-11-27 09:42:22','2019-11-27 09:43:52',NULL,1.58,NULL,NULL),(1031,4,1,'CLOSED','2019-11-27 09:44:06','2019-11-27 09:44:12','2019-12-16 11:20:34',NULL,0.00,NULL,NULL),(1032,4,1,'CLOSED','2019-11-27 09:45:37','2019-11-27 09:52:42','2019-11-27 09:53:23',NULL,1.00,NULL,NULL),(1033,4,1,'CLOSED','2019-11-27 09:49:46','2019-11-27 09:52:03','2019-11-27 10:13:23',NULL,1.00,NULL,NULL),(1034,8,1,'CLOSED','2019-11-27 09:54:16','2019-11-27 09:54:48','2019-11-27 09:54:59',NULL,1.00,NULL,NULL),(1035,4,1,'CLOSED','2019-11-28 09:51:22','2019-11-29 11:13:00','2019-11-29 11:14:29',NULL,0.00,NULL,NULL),(1036,4,1,'CLOSED','2019-11-29 11:08:20','2019-11-29 11:11:21','2019-11-29 11:12:42',NULL,1.00,NULL,NULL),(1037,4,1,'CLOSED','2019-11-29 11:14:40','2019-11-29 11:14:42','2019-11-29 11:15:46',NULL,0.00,NULL,NULL),(1038,4,1,'CLOSED','2019-11-29 11:16:20','2019-11-29 11:16:22','2019-11-29 11:18:21',NULL,0.00,NULL,NULL),(1039,4,1,'CLOSED','2019-11-29 11:19:06','2019-11-29 11:19:26','2019-12-10 10:05:00',NULL,0.00,NULL,NULL),(1040,4,1,'CLOSED','2019-12-02 13:14:40','2019-12-02 13:15:16','2019-12-02 13:23:59',NULL,0.57,NULL,NULL),(1041,4,1,'CLOSED','2019-12-02 13:24:32','2019-12-02 13:25:05','2019-12-02 13:28:05',NULL,0.71,NULL,NULL),(1042,4,1,'CLOSED','2019-12-02 13:28:21','2019-12-02 13:28:31','2019-12-02 13:30:24',NULL,1.00,NULL,NULL),(1043,4,1,'CLOSED','2019-12-02 13:30:46','2019-12-02 13:30:52','2019-12-16 11:20:06',NULL,1.57,NULL,NULL),(1044,4,1,'CLOSED','2019-12-16 11:22:10','2019-12-16 11:22:14','2019-12-16 11:22:46',NULL,1.43,NULL,NULL),(1045,4,1,'CLOSED','2019-12-16 11:24:31','2019-12-16 11:24:37','2019-12-16 11:25:04',NULL,0.29,NULL,NULL),(1046,4,1,'CLOSED','2019-12-16 11:25:39','2019-12-16 11:25:44','2019-12-16 11:26:10',NULL,0.43,NULL,NULL),(1047,4,1,'OVERDUE','2019-12-16 11:26:28','2019-12-16 11:26:32','2019-12-19 23:00:00',NULL,0.43,NULL,NULL),(1048,4,1,'CLOSED','2019-12-16 11:28:31','2019-12-16 11:28:39','2019-12-16 11:28:58',NULL,0.43,NULL,NULL),(1049,4,1,'CLOSED','2019-12-16 11:29:20','2019-12-16 11:29:24','2019-12-16 11:29:38',NULL,0.57,NULL,NULL),(1050,4,1,'CLOSED','2020-01-29 13:27:59','2020-01-29 01:12:51','2020-01-29 01:12:51',NULL,4.00,NULL,NULL),(1051,4,1,'CLOSED','2020-01-29 15:45:46','2020-01-29 15:45:46','2020-01-29 15:45:46',NULL,5.00,NULL,NULL),(1052,4,1,'CLOSED','2020-01-29 15:54:20','2020-01-29 15:54:20','2020-01-29 15:54:20',NULL,12.00,NULL,NULL),(1053,4,1,'CLOSED','2020-01-29 16:29:32','2020-01-29 10:28:22','2020-01-29 10:28:22',NULL,6.00,NULL,NULL),(1054,4,1,'CLOSED','2020-01-29 16:34:40','2020-01-29 16:34:40','2020-01-29 16:34:40',NULL,5.00,NULL,NULL),(1055,4,1,'CLOSED','2020-01-29 16:36:17','2020-01-29 16:36:17','2020-01-29 16:36:17',NULL,3.00,NULL,NULL),(1056,4,1,'CLOSED','2020-01-29 16:40:54','2020-01-29 16:42:33','2020-01-29 16:43:25',NULL,6.00,NULL,NULL),(1057,4,1,'CANCELLED','2020-01-29 16:49:25','2020-01-29 22:49:00','2020-02-07 23:00:00',NULL,1.29,NULL,NULL),(1058,4,1,'OVERDUE','2020-01-30 10:10:04','2020-01-30 10:10:26','2020-01-31 23:00:00',NULL,9.00,NULL,NULL),(1059,4,1,'PENDING','2020-01-30 12:20:21','2020-01-30 12:20:21','2020-01-30 12:20:21',NULL,23.98,NULL,NULL),(1060,4,1,'OVERDUE','2020-01-30 13:05:41','2020-01-30 13:05:56','2020-01-31 23:00:00',NULL,0.00,NULL,NULL),(1061,4,1,'CLOSED','2020-01-30 13:24:55','2020-01-30 13:25:02','2020-01-31 23:00:00',NULL,0.14,NULL,NULL),(1062,4,1,'CLOSED','2020-01-30 13:33:14','2020-01-30 13:33:17','2020-01-31 23:00:00',NULL,0.00,NULL,NULL),(1063,6,1,'CLOSED','2020-01-30 14:13:42','2020-01-30 14:13:46','2020-03-12 13:13:05',NULL,0.14,NULL,NULL),(1064,6,1,'CLOSED','2020-01-30 15:13:13','2020-01-30 15:13:18','2020-01-30 15:13:27',NULL,0.14,NULL,NULL),(1065,6,1,'CLOSED','2020-01-30 15:21:55','2020-01-30 15:21:59','2020-01-30 15:23:56',NULL,0.14,NULL,NULL),(1066,6,1,'CLOSED','2020-01-30 15:26:01','2020-01-30 15:26:04','2020-01-30 15:26:27',NULL,0.00,NULL,NULL),(1067,6,1,'RESERVED','2020-01-30 16:31:54','2020-01-30 22:31:00','2020-02-05 23:00:00',NULL,0.86,NULL,NULL),(1068,6,1,'RESERVED','2020-01-30 16:34:06','2020-01-29 09:00:00','2020-02-05 09:00:00',NULL,1.00,NULL,NULL),(1069,6,1,'CLOSED','2020-01-31 10:20:41','2020-01-31 10:20:50','2020-03-12 13:05:47',NULL,1.00,NULL,NULL),(1070,4,4,'RESERVED','2020-02-03 19:25:43','2020-02-06 09:00:00','2020-02-13 09:00:00',NULL,0.50,NULL,NULL),(1071,4,4,'CLOSED','2020-02-03 19:27:21','2020-02-03 19:27:21','2020-02-03 19:27:21',NULL,11.99,NULL,NULL),(1072,4,4,'PENDING','2020-02-03 19:28:38','2020-02-03 19:28:38','2020-02-03 19:28:38',NULL,10.00,NULL,NULL),(1073,7,1,'RESERVED','2020-02-10 10:08:30','2020-02-10 10:08:00','2020-02-17 17:00:00',NULL,15.00,NULL,NULL),(1074,7,7,'RESERVED','2020-02-10 10:12:15','2020-02-18 09:00:00','2020-02-25 09:00:00',NULL,15.00,NULL,NULL),(1075,7,7,'CLOSED','2020-02-13 10:00:57','2020-02-25 13:15:34','2020-03-12 13:05:27',NULL,15.00,NULL,NULL),(1076,7,1,'RESERVED','2020-02-24 16:13:18','2020-02-24 15:59:00','2020-02-26 17:00:00',NULL,0.00,NULL,NULL),(1077,4,1,'CLOSED','2020-02-25 14:21:33','2020-02-25 14:21:47','2020-02-26 10:03:21',NULL,7.50,NULL,NULL),(1078,2,1,'CLOSED','2020-02-26 08:58:15','2020-02-26 10:47:10','2020-03-12 13:04:58',NULL,9.99,NULL,NULL),(1079,4,1,'CLOSED','2020-02-26 10:03:59','2020-02-26 10:05:43','2020-03-12 13:00:36',NULL,0.00,NULL,NULL),(1080,4,1,'PENDING','2020-02-26 10:16:42','2020-02-26 10:16:00','2020-02-27 17:00:00',NULL,10.00,NULL,NULL),(1081,2,1,'CLOSED','2020-03-02 11:47:38','2020-03-02 11:50:37','2020-03-02 12:55:09',NULL,7.50,NULL,NULL),(1082,40,40,'CLOSED','2020-03-02 13:37:58','2020-03-02 13:38:13','2020-03-12 12:58:46',NULL,7.50,NULL,NULL),(1084,40,1,'CLOSED','2020-03-02 14:51:35','2020-03-12 14:19:19','2020-03-18 15:07:03',NULL,0.00,NULL,NULL),(1085,40,1,'PENDING','2020-03-02 15:12:52','2020-03-02 15:04:00','2020-03-05 17:00:00',NULL,7.50,NULL,NULL),(1086,41,41,'PENDING','2020-03-02 16:46:54','2020-03-04 09:00:00','2020-03-11 09:00:00',NULL,15.00,NULL,'1'),(1087,4,1,'RESERVED','2020-03-05 14:54:58','2020-03-06 09:00:00','2020-03-13 09:00:00',NULL,0.00,NULL,NULL),(1088,4,1,'CLOSED','2020-03-06 09:36:39','2020-03-18 14:54:00','2020-03-18 15:06:25',NULL,0.00,NULL,NULL),(1089,41,1,'CANCELLED','2020-03-06 09:37:29','2020-03-18 10:00:00','2020-03-18 11:00:00',NULL,0.00,NULL,NULL),(1090,41,1,'RESERVED','2020-03-06 11:19:25','2020-03-05 10:00:00','2020-03-05 14:00:00',NULL,0.00,NULL,NULL),(1091,18,1,'CLOSED','2020-03-06 11:20:37','2020-03-12 14:18:15','2020-03-18 13:43:14',NULL,7.50,NULL,NULL),(1092,18,1,'CLOSED','2020-03-06 11:21:08','2020-03-06 11:21:37','2020-03-12 12:54:35',NULL,0.00,NULL,NULL),(1093,41,1,'CLOSED','2020-03-11 09:21:21','2020-03-11 09:21:44','2020-03-11 09:22:44',NULL,15.00,NULL,NULL),(1094,4,1,'CLOSED','2020-03-12 14:08:12','2020-03-12 14:08:24','2020-03-12 14:09:06',NULL,0.00,NULL,NULL),(1095,4,1,'CLOSED','2020-03-12 14:18:02','2020-03-18 13:43:31','2020-03-18 13:46:55',NULL,5.00,NULL,NULL),(1096,4,1,'CANCELLED','2020-03-13 09:46:20','2020-03-13 09:39:00','2020-03-20 17:00:00',NULL,7.50,NULL,NULL),(1097,4,1,'CANCELLED','2020-03-13 09:46:48','2020-03-13 09:46:00','2020-03-20 17:00:00',NULL,15.00,NULL,NULL),(1098,4,1,'CANCELLED','2020-03-13 09:56:24','2020-03-13 09:47:00','2020-03-20 17:00:00',NULL,15.00,NULL,NULL),(1099,6,1,'CLOSED','2020-03-16 10:09:35','2020-03-16 10:11:28','2020-03-18 15:07:23',NULL,7.50,NULL,NULL),(1100,6,1,'CLOSED','2020-03-16 10:10:05','2020-03-17 10:32:46','2020-03-17 10:33:26',NULL,7.50,NULL,NULL),(1101,6,1,'CANCELLED','2020-03-16 10:12:42','2020-03-16 10:12:00','2020-03-17 17:00:00',NULL,7.50,NULL,NULL),(1104,6,1,'CANCELLED','2020-03-18 12:03:10','2020-03-26 09:00:00','2020-04-02 09:00:00',NULL,15.00,NULL,'post'),(1105,41,1,'CANCELLED','2020-03-18 15:04:10','2020-03-18 12:14:00','2020-03-19 17:00:00',NULL,3.50,NULL,'2'),(1106,6,1,'PENDING','2020-03-18 15:08:34','2020-03-18 15:07:00','2020-03-19 17:00:00',NULL,7.50,NULL,'post'),(1107,6,1,'CANCELLED','2020-03-18 15:42:54','2020-03-18 15:41:46','2020-03-19 17:00:00',NULL,9.50,NULL,'2'),(1109,18,1,'RESERVED','2020-03-18 15:50:12','2020-03-18 15:46:00','2020-03-19 17:00:00',NULL,9.00,NULL,'post'),(1110,18,1,'CANCELLED','2020-03-18 16:30:00','2020-03-18 16:29:00','2020-03-19 17:00:00',NULL,7.50,NULL,'post'),(1111,18,1,'PENDING','2020-03-18 16:30:58','2020-03-18 16:30:00','2020-03-19 17:00:00',NULL,14.00,NULL,'post'),(1114,53,53,'PENDING','2020-03-18 22:31:22','2020-03-18 22:31:00','2020-03-25 14:00:00',NULL,14.00,NULL,'post'),(1115,53,53,'CANCELLED','2020-03-18 23:03:03','2020-03-19 09:00:00','2020-04-09 17:00:00',NULL,14.00,NULL,'1'),(1116,53,1,'ACTIVE','2020-03-18 23:13:56','2020-03-18 23:28:16','2020-03-19 17:00:00',NULL,7.50,NULL,'post'),(1117,4,1,'CANCELLED','2020-03-19 18:49:56','2020-03-19 18:49:00','2020-04-02 17:00:00',NULL,7.50,NULL,'4'),(1118,4,1,'CANCELLED','2020-03-19 18:59:06','2020-03-20 09:00:00','2020-03-27 09:00:00',NULL,7.50,NULL,'1'),(1119,4,1,'CANCELLED','2020-03-19 19:00:10','2020-03-19 09:00:00','2020-03-26 09:00:00',NULL,1.00,NULL,'1'),(1120,4,1,'CLOSED','2020-03-19 19:02:50','2020-03-19 19:08:21','2020-03-19 19:09:24',NULL,7.50,NULL,'post');
/*!40000 ALTER TABLE `loan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_row`
--

DROP TABLE IF EXISTS `loan_row`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loan_row` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loan_id` int(11) DEFAULT NULL,
  `inventory_item_id` int(11) DEFAULT NULL,
  `product_quantity` int(11) NOT NULL,
  `due_in_at` datetime NOT NULL,
  `due_out_at` datetime DEFAULT NULL,
  `checked_out_at` datetime DEFAULT NULL,
  `checked_in_at` datetime DEFAULT NULL,
  `fee` decimal(10,2) NOT NULL,
  `site_from` int(11) DEFAULT NULL,
  `site_to` int(11) DEFAULT NULL,
  `deposit_id` int(11) DEFAULT NULL,
  `item_location` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_922D737F9815E4B1` (`deposit_id`),
  KEY `IDX_922D737FCE73868F` (`loan_id`),
  KEY `IDX_922D737F536BF4A2` (`inventory_item_id`),
  KEY `IDX_922D737F82801C89` (`site_from`),
  KEY `IDX_922D737F9E03A3D2` (`site_to`),
  KEY `IDX_922D737F32934100` (`item_location`),
  CONSTRAINT `FK_922D737F32934100` FOREIGN KEY (`item_location`) REFERENCES `inventory_location` (`id`),
  CONSTRAINT `FK_922D737F536BF4A2` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_item` (`id`),
  CONSTRAINT `FK_922D737F82801C89` FOREIGN KEY (`site_from`) REFERENCES `site` (`id`),
  CONSTRAINT `FK_922D737F9815E4B1` FOREIGN KEY (`deposit_id`) REFERENCES `deposit` (`id`),
  CONSTRAINT `FK_922D737F9E03A3D2` FOREIGN KEY (`site_to`) REFERENCES `site` (`id`),
  CONSTRAINT `FK_922D737FCE73868F` FOREIGN KEY (`loan_id`) REFERENCES `loan` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=164 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_row`
--

LOCK TABLES `loan_row` WRITE;
/*!40000 ALTER TABLE `loan_row` DISABLE KEYS */;
INSERT INTO `loan_row` VALUES (1,1000,1004,1,'2019-11-19 23:00:00','2019-11-11 21:03:00','2019-11-11 15:05:42','2019-11-11 15:17:57',10.00,1,1,1,NULL),(2,1001,1010,1,'2019-11-21 23:00:00','2019-11-14 15:20:00',NULL,NULL,14.00,3,1,NULL,NULL),(3,1002,1011,1,'2019-12-10 23:00:00','2019-11-19 15:00:00','2019-11-14 10:45:51','2019-11-26 15:41:59',1.00,3,1,NULL,NULL),(4,1003,1009,1,'2019-11-21 23:00:00','2019-11-14 17:03:00','2019-11-14 11:09:17','2019-11-14 12:45:26',0.50,1,1,NULL,NULL),(5,1004,1013,1,'2019-11-21 23:00:00','2019-11-14 17:32:00','2019-11-14 11:32:40','2019-11-14 11:33:15',1.00,2,1,NULL,NULL),(6,1005,1013,1,'2019-11-21 23:00:00','2019-11-14 17:34:00','2019-11-14 11:35:08','2019-11-14 11:35:19',1.00,2,1,NULL,NULL),(7,1006,1014,1,'2019-11-21 23:00:00','2019-11-14 17:40:00','2019-11-14 11:40:21','2019-11-14 12:20:16',1.00,2,1,NULL,NULL),(8,1007,1013,1,'2019-11-21 23:00:00','2019-11-14 17:47:00','2019-11-14 11:48:07','2019-11-14 11:48:13',1.00,2,1,NULL,NULL),(9,1008,1013,1,'2019-11-26 23:00:00','2019-11-14 17:52:00','2019-11-14 12:09:45','2019-11-14 12:20:24',2.00,2,1,NULL,NULL),(10,1009,1014,1,'2019-11-21 23:00:00','2019-11-14 18:20:00','2019-11-14 12:21:03','2019-11-14 13:06:40',1.00,2,1,NULL,1),(11,1009,1012,1,'2019-11-26 23:00:00','2019-11-14 18:20:00','2019-11-14 12:21:03','2019-11-26 15:40:37',1.71,2,3,NULL,1),(12,1010,1009,1,'2019-11-27 23:00:00','2019-11-18 20:00:00',NULL,NULL,1.29,3,1,NULL,NULL),(13,1011,1014,1,'2019-11-22 23:00:00','2019-11-15 18:11:00','2019-11-15 13:58:19',NULL,0.00,2,1,NULL,1),(16,1014,1016,1,'2019-11-27 23:00:00','2019-11-20 17:16:00',NULL,NULL,1.00,1,1,NULL,NULL),(17,1015,1016,1,'2019-11-20 23:00:00','2019-11-20 17:28:00',NULL,NULL,0.00,1,1,NULL,NULL),(18,1016,1016,1,'2019-11-27 23:00:00','2019-11-20 17:33:00',NULL,NULL,1.00,1,1,NULL,NULL),(19,1017,1016,1,'2019-11-27 23:00:00','2019-11-20 17:40:00','2019-11-20 11:50:00','2019-11-20 11:50:06',1.00,1,1,NULL,NULL),(20,1018,1016,1,'2019-11-29 23:00:00','2019-11-28 15:00:00','2019-11-20 11:49:47','2019-11-20 11:49:53',1.00,1,1,NULL,NULL),(21,1019,1016,1,'2019-11-29 23:00:00','2019-11-20 17:50:00','2019-11-20 11:50:29','2019-11-26 15:28:18',1.00,2,1,NULL,NULL),(22,1020,1015,1,'2019-11-21 23:00:00','2019-11-20 17:53:00','2019-11-20 11:54:14','2019-11-20 11:54:40',0.00,1,1,NULL,NULL),(23,1020,1008,1,'2019-11-29 11:00:00','2019-11-21 05:53:00',NULL,NULL,5.00,1,1,NULL,NULL),(24,1020,1005,1,'2019-11-29 11:00:00','2019-11-21 05:53:00','2019-11-20 11:54:14','2019-11-20 11:54:44',0.00,1,1,NULL,NULL),(25,1021,1007,1,'2019-11-28 23:00:00','2019-11-26 18:01:00',NULL,NULL,0.29,1,1,NULL,NULL),(26,1022,1007,1,'2019-11-29 23:00:00','2019-11-26 18:03:00',NULL,NULL,0.43,1,1,NULL,NULL),(27,1023,1012,1,'2019-11-28 17:00:00','2019-11-26 16:47:00','2019-11-26 16:47:08','2019-11-26 16:47:17',0.29,2,1,NULL,NULL),(28,1024,1015,1,'2019-11-28 17:00:00','2019-11-26 16:47:00','2019-11-26 16:47:43','2019-11-26 16:47:52',0.00,2,1,NULL,NULL),(29,1025,1015,1,'2019-11-28 17:00:00','2019-11-26 16:55:00','2019-11-26 16:56:01','2019-11-26 16:56:06',0.00,2,1,NULL,NULL),(30,1026,1015,1,'2019-11-28 17:00:00','2019-11-26 16:56:00','2019-11-26 16:56:36','2019-11-26 16:56:43',0.00,2,1,NULL,NULL),(31,1027,1013,1,'2019-11-29 17:00:00','2019-11-27 09:23:00','2019-11-27 09:23:16','2019-11-27 09:37:32',0.29,2,1,NULL,NULL),(32,1028,1011,1,'2019-11-29 17:00:00','2019-11-27 09:23:00','2019-11-27 09:23:45','2019-11-27 09:23:52',0.00,2,1,NULL,NULL),(33,1029,1011,1,'2019-11-29 17:00:00','2019-11-27 09:34:00','2019-11-27 09:34:47','2019-11-27 09:36:25',0.00,2,1,NULL,NULL),(34,1030,1010,1,'2019-11-29 17:00:00','2019-11-27 09:41:00','2019-11-27 09:42:22','2019-11-27 09:43:12',0.29,1,1,NULL,NULL),(35,1030,1011,1,'2019-12-06 17:00:00','2019-11-27 09:41:00','2019-11-27 09:42:22','2019-11-27 09:43:51',1.29,1,1,NULL,NULL),(36,1031,1011,1,'2019-12-05 17:00:00','2019-11-27 09:43:00','2019-11-27 09:44:12','2019-12-16 11:20:33',0.00,2,1,NULL,NULL),(37,1032,1010,1,'2019-12-04 17:00:00','2019-11-27 09:45:00','2019-11-27 09:52:42','2019-11-27 09:53:21',1.00,2,1,2,NULL),(38,1033,1016,1,'2019-12-04 17:00:00','2019-11-27 09:49:00','2019-11-27 09:52:03','2019-11-27 10:13:22',1.00,2,1,NULL,NULL),(39,1034,1010,1,'2019-12-05 17:00:00','2019-11-27 09:54:00','2019-11-27 09:54:48','2019-11-27 09:54:59',1.00,2,1,3,NULL),(40,1035,1015,1,'2019-12-05 15:00:00','2019-11-28 13:00:00','2019-11-29 11:13:00','2019-11-29 11:14:28',0.00,2,1,NULL,NULL),(41,1036,1016,1,'2019-12-06 23:00:00','2019-11-29 17:08:00','2019-11-29 11:11:21','2019-11-29 11:12:41',1.00,2,1,NULL,NULL),(42,1037,1015,1,'2019-12-06 23:00:00','2019-11-29 17:14:00','2019-11-29 11:14:42','2019-11-29 11:15:46',0.00,2,1,NULL,NULL),(43,1038,1015,1,'2019-11-29 23:00:00','2019-11-29 17:16:00','2019-11-29 11:16:22','2019-11-29 11:18:12',0.00,2,1,NULL,NULL),(44,1039,1015,1,'2019-12-06 23:00:00','2019-11-29 17:19:00','2019-11-29 11:19:26','2019-12-10 10:05:00',0.00,2,1,NULL,NULL),(45,1040,1010,1,'2019-12-06 23:00:00','2019-12-02 19:08:00','2019-12-02 13:15:16','2019-12-02 13:23:58',0.57,2,2,4,NULL),(46,1041,1006,1,'2019-12-07 20:00:00','2019-12-02 19:24:00','2019-12-02 13:25:05','2019-12-02 13:28:04',0.71,1,2,NULL,NULL),(47,1042,1006,1,'2019-12-10 15:00:00','2019-12-03 15:00:00','2019-12-02 13:28:31','2019-12-02 13:30:23',1.00,1,1,NULL,NULL),(48,1043,1006,1,'2019-12-13 23:00:00','2019-12-02 19:30:00','2019-12-02 13:30:52','2019-12-16 11:20:05',1.57,2,1,NULL,NULL),(49,1044,1011,1,'2019-12-26 23:00:00','2019-12-16 17:22:00','2019-12-16 11:22:14','2019-12-16 11:22:45',1.43,2,1,NULL,NULL),(50,1045,1011,1,'2019-12-18 23:00:00','2019-12-16 17:24:00','2019-12-16 11:24:37','2019-12-16 11:25:03',0.29,2,1,NULL,NULL),(51,1046,1011,1,'2019-12-19 23:00:00','2019-12-16 17:25:00','2019-12-16 11:25:44','2019-12-16 11:26:09',0.43,2,1,NULL,NULL),(52,1047,1011,1,'2019-12-19 23:00:00','2019-12-16 17:26:00','2019-12-16 11:26:32','2019-12-16 11:28:00',0.43,2,1,NULL,NULL),(53,1048,1011,1,'2019-12-19 23:00:00','2019-12-16 17:28:00','2019-12-16 11:28:39','2019-12-16 11:28:57',0.43,2,1,NULL,NULL),(54,1049,1011,1,'2019-12-20 23:00:00','2019-12-16 17:29:00','2019-12-16 11:29:24','2019-12-16 11:29:37',0.57,2,1,NULL,NULL),(55,1050,1017,1,'2020-01-29 01:12:51','2020-01-29 01:12:51','2020-01-29 13:38:27',NULL,4.00,1,NULL,NULL,2),(56,1051,1017,2,'2020-01-29 15:45:46','2020-01-29 15:45:46','2020-01-29 15:49:38',NULL,5.00,2,NULL,NULL,4),(57,1052,1017,1,'2020-01-29 15:54:20','2020-01-29 15:54:20','2020-01-29 16:20:42',NULL,2.00,2,NULL,NULL,4),(58,1052,1017,2,'2020-01-29 15:54:20','2020-01-29 15:54:20','2020-01-29 16:20:42',NULL,5.00,2,NULL,NULL,5),(59,1053,1017,2,'2020-01-29 10:28:22','2020-01-29 10:28:22','2020-01-29 16:29:50',NULL,3.00,2,NULL,NULL,5),(60,1054,1017,1,'2020-01-29 16:34:40','2020-01-29 16:34:40','2020-01-29 16:34:46',NULL,5.00,1,NULL,NULL,2),(61,1055,1017,1,'2020-01-29 16:36:17','2020-01-29 16:36:17','2020-01-29 16:36:28',NULL,3.00,2,NULL,NULL,5),(62,1056,1017,1,'2020-01-29 04:37:06','2020-01-29 04:37:06','2020-01-29 16:42:33',NULL,5.00,2,NULL,NULL,5),(63,1056,1015,1,'2020-01-31 23:00:00','2020-01-29 16:37:00','2020-01-29 16:42:33','2020-01-29 16:43:23',1.00,2,1,NULL,NULL),(64,1057,1010,1,'2020-02-07 23:00:00','2020-01-29 22:49:00',NULL,NULL,1.29,2,1,NULL,NULL),(65,1058,1011,1,'2020-01-31 23:00:00','2020-01-30 16:01:00','2020-01-30 10:10:26',NULL,1.00,2,1,NULL,1),(66,1058,1017,2,'2020-01-30 04:07:03','2020-01-30 04:07:03','2020-01-30 10:10:26',NULL,4.00,2,NULL,NULL,5),(71,1059,1018,2,'2020-01-30 12:20:21','2020-01-30 12:20:21',NULL,NULL,11.99,1,NULL,NULL,2),(72,1060,1015,1,'2020-01-31 23:00:00','2020-01-30 19:05:00','2020-01-30 13:05:56',NULL,0.00,1,1,NULL,1),(73,1061,1020,1,'2020-01-31 23:00:00','2020-01-30 19:24:00','2020-01-30 13:25:02',NULL,0.14,1,1,NULL,1),(74,1062,1021,1,'2020-01-31 23:00:00','2020-01-30 19:33:00','2020-01-30 13:33:17',NULL,0.00,1,1,NULL,1),(75,1063,1022,1,'2020-01-31 23:00:00','2020-01-30 20:13:00','2020-01-30 14:13:46','2020-03-12 13:13:04',0.14,1,1,NULL,NULL),(76,1064,1009,1,'2020-01-31 23:00:00','2020-01-30 21:13:00','2020-01-30 15:13:18','2020-01-30 15:13:26',0.14,2,1,NULL,NULL),(77,1065,1023,1,'2020-01-31 23:00:00','2020-01-30 21:21:00','2020-01-30 15:21:59','2020-01-30 15:23:54',0.14,1,1,NULL,NULL),(78,1066,1024,1,'2020-01-31 23:00:00','2020-01-30 21:25:00','2020-01-30 15:26:04','2020-01-30 15:26:25',0.00,1,1,NULL,NULL),(79,1067,1009,1,'2020-02-05 23:00:00','2020-01-30 22:31:00',NULL,NULL,0.86,1,1,NULL,NULL),(80,1068,1023,1,'2020-02-05 09:00:00','2020-01-29 09:00:00',NULL,NULL,1.00,1,1,NULL,NULL),(81,1069,1023,1,'2020-01-15 09:00:00','2020-01-08 09:00:00','2020-01-31 10:20:50','2020-03-12 13:05:47',1.00,1,1,NULL,NULL),(82,1070,1023,1,'2020-02-13 09:00:00','2020-02-06 09:00:00',NULL,NULL,0.50,1,1,NULL,NULL),(83,1071,1018,1,'2020-02-03 19:27:21','2020-02-03 19:27:21','2020-02-03 19:27:40',NULL,11.99,1,NULL,NULL,2),(84,1072,1017,2,'2020-02-03 19:28:38','2020-02-03 19:28:38',NULL,NULL,5.00,1,NULL,NULL,2),(85,1073,1006,1,'2020-02-17 17:00:00','2020-02-10 10:08:00',NULL,NULL,15.00,2,3,NULL,NULL),(86,1074,1006,1,'2020-02-25 09:00:00','2020-02-18 09:00:00',NULL,NULL,15.00,1,1,NULL,NULL),(87,1075,1024,1,'2020-02-20 17:00:00','2020-02-13 10:00:00','2020-02-25 13:15:34','2020-03-12 13:05:26',15.00,1,1,NULL,NULL),(88,1076,1008,1,'2020-02-26 17:00:00','2020-02-24 15:59:00',NULL,NULL,4.00,2,1,NULL,NULL),(89,1077,1013,1,'2020-02-26 17:00:00','2020-02-25 14:20:00','2020-02-25 14:21:47','2020-02-26 10:03:19',7.50,2,1,NULL,NULL),(90,1078,1018,1,'2020-02-26 08:57:48','2020-02-26 08:57:48','2020-02-26 10:47:10',NULL,4.99,1,NULL,NULL,2),(91,1078,1007,1,'2020-03-05 17:00:00','2020-02-26 08:56:00','2020-02-26 10:47:10','2020-03-12 13:04:57',5.00,1,1,NULL,NULL),(92,1079,1013,1,'2020-02-28 17:00:00','2020-02-26 10:03:00','2020-02-26 10:05:43','2020-03-12 13:00:35',0.00,3,1,5,NULL),(93,1080,1004,1,'2020-02-27 17:00:00','2020-02-26 10:16:00',NULL,NULL,0.00,1,1,NULL,NULL),(94,1081,1009,1,'2020-03-10 09:00:00','2020-03-03 09:00:00','2020-03-02 11:50:37','2020-03-02 12:55:07',7.50,3,3,NULL,NULL),(95,1082,1006,1,'2020-03-06 17:00:00','2020-03-18 09:00:00','2020-03-02 13:38:13','2020-03-12 12:58:46',7.50,1,1,NULL,NULL),(102,1085,1008,1,'2020-03-05 17:00:00','2020-03-02 15:04:00',NULL,NULL,7.50,2,1,NULL,NULL),(103,1085,1005,1,'2020-03-05 17:00:00','2020-03-02 15:04:00',NULL,NULL,0.00,2,1,NULL,NULL),(104,1085,1029,1,'2020-03-05 17:00:00','2020-03-02 15:04:00',NULL,NULL,0.00,2,1,NULL,NULL),(105,1085,1032,1,'2020-03-05 17:00:00','2020-03-02 15:04:00',NULL,NULL,0.00,2,1,NULL,NULL),(106,1085,1031,1,'2020-03-05 17:00:00','2020-03-02 15:04:00',NULL,NULL,0.00,2,1,NULL,NULL),(110,1084,1023,1,'2020-03-13 17:00:00','2020-03-02 09:00:00','2020-03-12 14:19:19','2020-03-18 15:07:02',0.00,1,1,NULL,NULL),(111,1084,1017,1,'2020-03-02 16:34:21',NULL,'2020-03-12 14:19:19',NULL,0.00,1,NULL,NULL,2),(112,1086,1032,1,'2020-03-11 09:00:00','2020-03-04 09:00:00',NULL,NULL,15.00,1,1,NULL,NULL),(114,1087,1032,1,'2020-03-13 09:00:00','2020-03-06 09:00:00',NULL,NULL,0.00,1,1,NULL,NULL),(115,1088,1032,1,'2020-03-18 10:00:00','2020-03-18 09:00:00','2020-03-18 14:54:00','2020-03-18 15:06:25',0.00,1,1,NULL,NULL),(116,1089,1032,1,'2020-03-18 11:00:00','2020-03-18 10:00:00',NULL,NULL,10.00,1,1,NULL,NULL),(117,1090,1032,1,'2020-03-05 14:00:00','2020-03-05 10:00:00',NULL,NULL,0.00,1,1,NULL,NULL),(118,1091,1032,1,'2020-03-05 17:00:00','2020-03-05 14:00:00','2020-03-12 14:18:15','2020-03-18 13:43:13',7.50,1,1,NULL,NULL),(119,1092,1032,1,'2020-03-03 13:00:00','2020-03-03 10:00:00','2020-03-06 11:21:37','2020-03-12 12:54:34',0.00,1,1,NULL,NULL),(120,1093,1031,1,'2020-03-26 17:00:00','2020-03-11 09:21:00','2020-03-11 09:21:44','2020-03-11 09:22:42',15.00,2,1,6,NULL),(121,1094,1031,1,'2020-03-19 09:00:00','2020-03-12 09:00:00','2020-03-12 14:08:24','2020-03-12 14:09:06',0.00,1,1,NULL,NULL),(122,1095,1032,1,'2020-03-14 09:00:00','2020-03-13 09:00:00','2020-03-18 13:43:31','2020-03-18 13:46:55',5.00,1,2,NULL,NULL),(123,1096,1031,1,'2020-03-20 17:00:00','2020-03-13 09:39:00',NULL,NULL,7.50,2,1,NULL,NULL),(124,1097,1031,1,'2020-03-20 17:00:00','2020-03-13 09:46:00',NULL,NULL,15.00,2,1,NULL,NULL),(125,1098,1031,1,'2020-03-20 17:00:00','2020-03-13 09:47:00',NULL,NULL,15.00,2,1,NULL,NULL),(126,1099,1031,1,'2020-03-25 17:00:00','2020-03-24 09:00:00','2020-03-16 10:11:28','2020-03-18 15:07:22',7.50,1,1,7,NULL),(127,1100,1029,1,'2020-03-17 17:00:00','2020-03-16 10:09:00','2020-03-17 10:32:46','2020-03-17 10:33:26',7.50,2,1,NULL,NULL),(128,1101,1024,1,'2020-03-17 17:00:00','2020-03-16 10:12:00',NULL,NULL,7.50,2,1,NULL,NULL),(131,1104,1031,1,'2020-04-02 09:00:00','2020-03-26 09:00:00',NULL,NULL,10.00,1,1,NULL,NULL),(132,1104,1024,1,'2020-03-27 17:00:00','2020-03-26 09:00:00',NULL,NULL,5.00,1,1,NULL,NULL),(133,1105,1029,1,'2020-03-19 17:00:00','2020-03-18 12:14:00',NULL,NULL,3.50,2,1,NULL,NULL),(134,1106,1031,1,'2020-03-19 17:00:00','2020-03-18 15:07:00',NULL,NULL,7.50,2,1,NULL,NULL),(136,1107,1024,1,'2020-03-19 17:00:00','2020-03-18 15:41:00',NULL,NULL,7.50,2,1,NULL,NULL),(137,1107,1036,1,'2020-03-18 15:41:46','2020-03-18 15:41:46',NULL,NULL,2.00,NULL,NULL,NULL,NULL),(139,1109,1031,1,'2020-03-19 17:00:00','2020-03-18 15:46:00',NULL,NULL,2.00,2,1,NULL,NULL),(146,1110,1032,1,'2020-03-19 17:00:00','2020-03-18 16:29:00',NULL,NULL,7.50,2,1,NULL,NULL),(147,1111,1032,1,'2020-03-19 17:00:00','2020-03-18 16:30:00',NULL,NULL,7.50,2,1,NULL,NULL),(148,1111,1036,1,'2020-03-18 16:30:58','2020-03-18 16:30:00',NULL,NULL,6.50,NULL,NULL,NULL,NULL),(149,1109,1044,1,'2020-03-18 22:18:38',NULL,NULL,NULL,8.00,NULL,NULL,NULL,NULL),(150,1114,1032,1,'2020-03-25 14:00:00','2020-03-18 22:31:00',NULL,NULL,7.50,2,4,NULL,NULL),(151,1114,1044,1,'2020-03-18 22:31:22','2020-03-18 22:31:00',NULL,NULL,6.50,NULL,NULL,NULL,NULL),(152,1115,1032,1,'2020-04-09 17:00:00','2020-03-19 09:00:00',NULL,NULL,1.00,1,1,NULL,NULL),(153,1115,1044,1,'2020-03-18 23:03:03','2020-03-19 09:00:00',NULL,NULL,2.00,NULL,NULL,NULL,NULL),(154,1116,1029,1,'2020-03-19 17:00:00','2020-03-18 23:11:00','2020-03-18 23:28:16',NULL,1.00,2,1,NULL,NULL),(156,1116,1044,1,'2020-03-18 23:18:36',NULL,NULL,NULL,6.50,NULL,NULL,NULL,NULL),(157,1117,1045,1,'2020-04-02 17:00:00','2020-03-19 18:49:00',NULL,NULL,1.00,4,1,NULL,NULL),(158,1117,1044,1,'2020-03-19 18:49:56','2020-03-19 18:49:00',NULL,NULL,6.50,NULL,NULL,NULL,NULL),(159,1118,1045,1,'2020-03-27 09:00:00','2020-03-20 09:00:00',NULL,NULL,1.00,1,1,NULL,NULL),(160,1118,1044,1,'2020-03-19 18:59:06','2020-03-20 09:00:00',NULL,NULL,6.50,NULL,NULL,NULL,NULL),(161,1119,1045,1,'2020-03-26 09:00:00','2020-03-19 09:00:00',NULL,NULL,1.00,1,1,NULL,NULL),(162,1120,1045,1,'2020-03-26 17:00:00','2020-03-19 19:02:00','2020-03-19 19:08:21','2020-03-19 19:09:24',1.00,4,1,8,NULL),(163,1120,1044,1,'2020-03-19 19:02:50','2020-03-19 19:02:00',NULL,NULL,6.50,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `loan_row` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance`
--

DROP TABLE IF EXISTS `maintenance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maintenance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `completed_by` int(11) DEFAULT NULL,
  `inventory_item_id` int(11) NOT NULL,
  `maintenance_plan_id` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `due_at` datetime NOT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `notes` varchar(2055) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_2F84F8E9192FE44` (`completed_by`),
  KEY `IDX_2F84F8E9536BF4A2` (`inventory_item_id`),
  KEY `IDX_2F84F8E9916F4709` (`maintenance_plan_id`),
  KEY `IDX_2F84F8E989EEAF91` (`assigned_to`),
  CONSTRAINT `FK_2F84F8E9192FE44` FOREIGN KEY (`completed_by`) REFERENCES `contact` (`id`),
  CONSTRAINT `FK_2F84F8E9536BF4A2` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_item` (`id`),
  CONSTRAINT `FK_2F84F8E989EEAF91` FOREIGN KEY (`assigned_to`) REFERENCES `contact` (`id`),
  CONSTRAINT `FK_2F84F8E9916F4709` FOREIGN KEY (`maintenance_plan_id`) REFERENCES `maintenance_plan` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance`
--

LOCK TABLES `maintenance` WRITE;
/*!40000 ALTER TABLE `maintenance` DISABLE KEYS */;
INSERT INTO `maintenance` VALUES (1,NULL,1007,1,5,'planned','2019-11-11 15:25:08','2019-11-15 00:00:00',NULL,NULL,0.00,NULL),(2,1,1012,1,5,'completed','2019-11-14 11:30:00','2019-11-16 00:00:00','2019-11-14 11:30:39','2019-11-14 11:30:58',5.00,'PAT test code 987876346456'),(3,NULL,1004,2,NULL,'planned','2019-11-14 11:31:38','2019-11-14 11:31:38',NULL,NULL,0.00,'Please fix the zip'),(4,1,1013,3,NULL,'completed','2019-11-14 11:33:15','2019-11-14 11:33:15','2019-11-14 11:47:18','2019-11-14 11:47:23',0.00,NULL),(5,1,1013,3,NULL,'completed','2019-11-14 11:35:19','2019-11-14 11:35:19','2019-11-14 11:47:30','2019-11-14 11:47:31',0.00,NULL),(6,1,1013,3,NULL,'completed','2019-11-14 11:48:13','2019-11-14 10:48:13','2019-11-14 11:48:46','2019-11-14 11:48:47',0.00,NULL),(7,1,1013,2,NULL,'completed','2019-11-14 11:48:58','2019-11-14 11:48:58','2020-02-25 13:57:55','2020-02-25 13:57:55',0.00,NULL),(8,NULL,1013,3,NULL,'planned','2019-11-14 11:49:10','2019-11-16 00:00:00',NULL,NULL,0.00,NULL),(9,1,1013,3,NULL,'completed','2019-11-14 12:20:24','2019-11-14 11:20:24','2019-11-26 11:25:45','2019-11-26 11:25:45',25.00,NULL),(10,1,1015,2,NULL,'completed','2019-11-26 10:13:29','2019-11-26 10:13:29','2019-11-26 10:13:47','2019-11-26 10:14:03',20.00,'New bulb needed.\r\nBulb replaced.'),(11,1,1015,2,NULL,'completed','2019-11-26 10:29:23','2019-11-26 10:29:23','2019-11-26 10:29:31','2019-11-26 10:29:31',5.00,NULL),(12,1,1014,2,NULL,'completed','2019-11-26 10:39:40','2019-11-26 00:00:00','2019-11-26 10:39:47','2019-11-26 10:39:47',1.00,NULL),(13,1,1013,3,NULL,'completed','2019-11-27 09:37:32','2019-11-27 08:37:32','2020-02-25 14:20:39','2020-02-25 14:20:39',0.00,NULL),(14,NULL,1016,1,5,'in_progress','2020-02-25 14:09:32','2020-02-25 00:00:00','2020-02-25 14:11:47',NULL,0.00,'Regular annual test please'),(15,1,1013,3,NULL,'completed','2020-02-26 10:03:19','2020-02-26 09:03:19','2020-02-26 10:03:31','2020-02-26 10:03:33',0.00,NULL),(16,NULL,1009,1,5,'overdue','2020-03-02 12:55:08','2020-03-02 12:55:08',NULL,NULL,0.00,NULL),(17,NULL,1004,1,5,'overdue','2020-03-02 12:58:12','2020-03-02 12:58:12',NULL,NULL,0.00,'My test is required'),(18,NULL,1022,2,5,'overdue','2020-03-02 13:20:31','2020-03-02 00:00:00',NULL,NULL,0.00,'Hey joe'),(19,1,1031,2,5,'completed','2020-03-11 09:22:43','2020-03-11 09:22:43','2020-03-11 09:24:30','2020-03-11 09:24:35',12.00,'Fix lens crack'),(20,NULL,1013,3,NULL,'overdue','2020-03-12 13:00:35','2020-03-12 12:00:35',NULL,NULL,0.00,NULL),(21,1,1029,4,NULL,'completed','2020-03-17 10:33:26','2020-03-17 09:33:26','2020-03-17 10:34:01','2020-03-17 10:34:13',0.00,'Any notes');
/*!40000 ALTER TABLE `maintenance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_plan`
--

DROP TABLE IF EXISTS `maintenance_plan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maintenance_plan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL,
  `interval_months` int(11) DEFAULT NULL,
  `after_each_loan` tinyint(1) NOT NULL,
  `provider` int(11) DEFAULT NULL,
  `prevent_borrows` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_12493BB192C4739C` (`provider`),
  CONSTRAINT `FK_12493BB192C4739C` FOREIGN KEY (`provider`) REFERENCES `contact` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_plan`
--

LOCK TABLES `maintenance_plan` WRITE;
/*!40000 ALTER TABLE `maintenance_plan` DISABLE KEYS */;
INSERT INTO `maintenance_plan` VALUES (1,'Annual electrical test','Please complete an inspection and attach the report to this task.',1,12,0,5,1),(2,'Ad-hoc maintenance',NULL,1,NULL,0,5,0),(3,'Lawnmower safety check','Check blades are not loose',1,NULL,1,NULL,1),(4,'Calibration',NULL,1,NULL,1,NULL,1);
/*!40000 ALTER TABLE `maintenance_plan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membership`
--

DROP TABLE IF EXISTS `membership`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `membership` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subscription_id` int(11) DEFAULT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` datetime NOT NULL,
  `starts_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_86FFD2859A1887DC` (`subscription_id`),
  KEY `IDX_86FFD285E7A1254A` (`contact_id`),
  KEY `IDX_86FFD285DE12AB56` (`created_by`),
  CONSTRAINT `FK_86FFD2859A1887DC` FOREIGN KEY (`subscription_id`) REFERENCES `membership_type` (`id`),
  CONSTRAINT `FK_86FFD285DE12AB56` FOREIGN KEY (`created_by`) REFERENCES `contact` (`id`),
  CONSTRAINT `FK_86FFD285E7A1254A` FOREIGN KEY (`contact_id`) REFERENCES `contact` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membership`
--

LOCK TABLES `membership` WRITE;
/*!40000 ALTER TABLE `membership` DISABLE KEYS */;
INSERT INTO `membership` VALUES (1,1,2,NULL,0.00,'2019-11-11 14:21:34','2019-11-11 14:21:34','2020-11-11 14:21:34','ACTIVE'),(2,1,4,4,10.00,'2019-11-11 15:03:10','2019-11-11 15:03:10','2020-11-10 15:03:10','CANCELLED'),(3,1,6,6,10.00,'2019-11-14 11:00:43','2019-11-14 11:00:43','2020-11-13 11:00:43','ACTIVE'),(4,2,3,1,0.00,'2019-11-14 12:45:16','2019-11-14 12:45:16','2019-11-28 12:45:16','EXPIRED'),(5,2,8,1,0.00,'2019-11-27 09:54:02','2019-11-27 09:54:02','2019-12-11 09:54:02','ACTIVE'),(6,2,7,1,0.00,'2020-01-28 15:44:25','2020-01-28 15:44:25','2020-02-11 15:44:25','ACTIVE'),(7,1,1,1,0.00,'2020-02-13 09:32:40','2020-02-12 00:00:00','2021-02-12 09:32:40','ACTIVE'),(8,2,4,1,0.00,'2020-02-13 09:36:54','2020-02-13 09:36:54','2020-02-27 09:36:54','ACTIVE'),(9,2,38,38,0.00,'2020-02-18 12:19:06','2020-02-18 12:19:06','2020-03-03 12:19:06','ACTIVE'),(10,2,13,1,0.00,'2020-02-24 17:06:35','2020-02-24 17:06:35','2020-03-09 17:06:35','ACTIVE'),(11,1,40,40,0.00,'2020-03-02 13:37:18','2020-03-02 13:37:18','2021-03-02 13:37:18','ACTIVE'),(12,2,41,41,0.00,'2020-03-02 16:46:14','2020-03-02 16:46:14','2020-03-16 16:46:14','ACTIVE'),(13,1,18,1,0.00,'2020-03-06 11:20:10','2020-03-06 11:20:10','2021-03-06 11:20:10','ACTIVE'),(14,1,53,53,0.00,'2020-03-18 22:23:02','2020-03-18 22:23:02','2021-03-18 22:23:02','ACTIVE');
/*!40000 ALTER TABLE `membership` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membership_type`
--

DROP TABLE IF EXISTS `membership_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `membership_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_by` int(11) DEFAULT NULL,
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration` int(11) NOT NULL,
  `discount` decimal(10,2) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `self_serve` int(11) NOT NULL,
  `description` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `credit_limit` decimal(10,2) DEFAULT NULL,
  `max_items` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `IDX_F7E162E2DE12AB56` (`created_by`),
  CONSTRAINT `FK_F7E162E2DE12AB56` FOREIGN KEY (`created_by`) REFERENCES `contact` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membership_type`
--

LOCK TABLES `membership_type` WRITE;
/*!40000 ALTER TABLE `membership_type` DISABLE KEYS */;
INSERT INTO `membership_type` VALUES (1,NULL,'Regular',0.00,365,50.00,'2016-01-06 16:34:26',1,NULL,0.00,NULL,1),(2,NULL,'No credit limit',0.00,14,0.00,'2016-01-06 16:34:26',1,NULL,0.00,NULL,1);
/*!40000 ALTER TABLE `membership_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migration_versions`
--

DROP TABLE IF EXISTS `migration_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migration_versions` (
  `version` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migration_versions`
--

LOCK TABLES `migration_versions` WRITE;
/*!40000 ALTER TABLE `migration_versions` DISABLE KEYS */;
INSERT INTO `migration_versions` VALUES ('0000'),('20161005213940'),('20161012225923'),('20170214145110'),('20170302141525'),('20170313230830'),('20170314090040'),('20170316110911'),('20170322215000'),('20170411114201'),('20170515114115'),('20170605092732'),('20170614152429'),('20170615121518'),('20170704161755'),('20180207111858'),('20180416182106'),('20180508102216'),('20180620154532'),('20180810102507'),('20190416090739'),('20190416100229'),('20190416101346'),('20190419083704'),('20190426065901'),('20190429091347'),('20190429211412'),('20190614094114'),('20190614220134'),('20190617212310'),('20190617212311'),('20190703085702'),('20190917121711'),('20191021100833'),('20191025144735'),('20191025155432'),('20191101140535'),('20191104155332'),('20191107110709'),('20191110160256'),('20191113095539'),('20200103161822'),('20200129113816'),('20200129115628'),('20200129124143'),('20200318105731');
/*!40000 ALTER TABLE `migration_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `note`
--

DROP TABLE IF EXISTS `note`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `note` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_by` int(11) DEFAULT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `loan_id` int(11) DEFAULT NULL,
  `inventory_item_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `text` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_only` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_CFBDFA14DE12AB56` (`created_by`),
  KEY `IDX_CFBDFA14E7A1254A` (`contact_id`),
  KEY `IDX_CFBDFA14CE73868F` (`loan_id`),
  KEY `IDX_CFBDFA14536BF4A2` (`inventory_item_id`),
  CONSTRAINT `FK_CFBDFA14536BF4A2` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_item` (`id`),
  CONSTRAINT `FK_CFBDFA14CE73868F` FOREIGN KEY (`loan_id`) REFERENCES `loan` (`id`),
  CONSTRAINT `FK_CFBDFA14DE12AB56` FOREIGN KEY (`created_by`) REFERENCES `contact` (`id`),
  CONSTRAINT `FK_CFBDFA14E7A1254A` FOREIGN KEY (`contact_id`) REFERENCES `contact` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=550 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `note`
--

LOCK TABLES `note` WRITE;
/*!40000 ALTER TABLE `note` DISABLE KEYS */;
INSERT INTO `note` VALUES (1,1,NULL,NULL,1001,'2019-11-11 14:21:37','Added item to <strong>Main site / Repair</strong>',NULL),(2,1,NULL,NULL,1002,'2019-11-11 14:21:38','Added item to <strong>Main site / In stock</strong>',NULL),(3,1,NULL,NULL,1003,'2019-11-11 14:21:39','Added item to <strong>In stock</strong>',NULL),(4,1,NULL,NULL,1001,'2019-11-11 14:21:41','Moved to <strong>Main site / In stock</strong>. \nUnit test\'s move notes',NULL),(5,1,NULL,NULL,1002,'2019-11-11 14:23:03','Removed from \"Main site / In stock\" with note:\nBatch deleted',NULL),(6,1,NULL,NULL,1003,'2019-11-11 14:23:03','Removed from \"Main site / In stock\" with note:\nBatch deleted',NULL),(7,1,NULL,NULL,1000,'2019-11-11 14:23:03','Removed from \"Second site / In stock\" with note:\nBatch deleted',NULL),(8,1,NULL,NULL,1001,'2019-11-11 14:23:03','Removed from \"Main site / In stock\" with note:\nBatch deleted',NULL),(9,1,NULL,NULL,1004,'2019-11-11 14:25:17','Added item to <strong>Main site / In stock</strong>',NULL),(10,1,4,NULL,NULL,'2019-11-11 14:59:52','Added by Admin Admin',NULL),(11,4,4,NULL,NULL,'2019-11-11 15:03:10','Subscribed to Regular membership.',NULL),(12,4,NULL,1000,NULL,'2019-11-11 15:03:38','Reservation created by Chris Tanner',NULL),(13,4,NULL,NULL,1004,'2019-11-11 15:05:42','Loaned to <strong>Chris Tanner</strong> on loan <strong>1000</strong>',NULL),(14,4,4,1000,NULL,'2019-11-11 15:05:42','Checked out loan. ',NULL),(15,4,4,1000,NULL,'2019-11-11 15:10:08','Updated return date for <strong>Petzl Fall Arrest Kit</strong> 0 days to 19 November 5:00 pm (extension fee 1.43)',NULL),(16,4,NULL,NULL,1004,'2019-11-11 15:17:57','Checked in to <strong>Main site / In stock</strong> from loan 1000. \nThanks for the loan!',NULL),(17,4,4,1000,NULL,'2019-11-11 15:17:57','Checked in <strong>Petzl Fall Arrest Kit</strong><br>Thanks for the loan!',NULL),(18,1,NULL,NULL,1005,'2019-11-11 15:20:56','Added item to <strong>Main site / In stock</strong>',NULL),(19,1,NULL,NULL,1006,'2019-11-11 15:21:10','Added item to <strong>Main site / In stock</strong>',NULL),(20,1,5,NULL,NULL,'2019-11-11 15:24:17','Added by Admin Admin',NULL),(21,1,NULL,NULL,1007,'2019-11-11 15:24:54','Added item to <strong>Main site / In stock</strong>',NULL),(22,1,NULL,NULL,1008,'2019-11-11 16:39:10','Added item to <strong>Second site / In stock</strong>',NULL),(23,1,NULL,NULL,1009,'2019-11-13 10:00:41','Added item to <strong>Main site / In stock</strong>',NULL),(24,1,NULL,NULL,1010,'2019-11-13 13:54:15','Added item to <strong>Main site / In stock</strong>',NULL),(25,1,NULL,NULL,1011,'2019-11-13 14:10:13','Added item to <strong>Main site / In stock</strong>',NULL),(26,2,NULL,1001,NULL,'2019-11-14 09:20:33','Reservation created by Chris Brightpearl',NULL),(27,1,NULL,1002,NULL,'2019-11-14 10:45:35','Reservation created by Admin Admin',NULL),(28,1,NULL,NULL,1011,'2019-11-14 10:45:51','Loaned to <strong>Chris Brightpearl</strong> on loan <strong>1002</strong>',NULL),(29,1,2,1002,NULL,'2019-11-14 10:45:51','Checked out loan. ',NULL),(30,6,6,NULL,NULL,'2019-11-14 11:00:43','Subscribed to Regular membership.',NULL),(31,6,NULL,1003,NULL,'2019-11-14 11:03:51','Reservation created by Chris Tanner',NULL),(32,1,6,1003,NULL,'2019-11-14 11:06:58','Added fee of £5; No-show',NULL),(33,1,6,1003,NULL,'2019-11-14 11:08:05','Deleted fee of £5.00',NULL),(34,1,NULL,NULL,1009,'2019-11-14 11:09:17','Loaned to <strong>Chris Tanner</strong> on loan <strong>1003</strong>',NULL),(35,1,6,1003,NULL,'2019-11-14 11:09:17','Checked out loan. ',NULL),(36,2,2,1002,NULL,'2019-11-14 11:12:57','Updated return date for <strong>DEWALT 18V XR BRUSHLESS 1/4\" ROUTER</strong> 7 days to 10 December 5:00 pm (extension fee 0.50)',NULL),(37,1,NULL,NULL,1012,'2019-11-14 11:29:37','Added item to <strong>Second site / In stock</strong>',NULL),(38,1,NULL,NULL,1004,'2019-11-14 11:31:38','Moved to <strong>Main site / Repair</strong>. \nPlease fix the zip',NULL),(39,1,NULL,NULL,1013,'2019-11-14 11:32:16','Added item to <strong>Second site / In stock</strong>',NULL),(40,1,NULL,1004,NULL,'2019-11-14 11:32:35','Loan created by Admin Admin',NULL),(41,1,NULL,NULL,1013,'2019-11-14 11:32:40','Loaned to <strong>Chris Brightpearl</strong> on loan <strong>1004</strong>',NULL),(42,1,2,1004,NULL,'2019-11-14 11:32:40','Checked out loan. ',NULL),(43,1,NULL,NULL,1013,'2019-11-14 11:33:15','Checked in to <strong>Second site / In stock</strong> from loan 1004. ',NULL),(44,1,2,1004,NULL,'2019-11-14 11:33:15','Checked in <strong>Mower</strong>',NULL),(45,1,NULL,1005,NULL,'2019-11-14 11:34:47','Loan created by Admin Admin',NULL),(46,1,NULL,NULL,1013,'2019-11-14 11:35:08','Loaned to <strong>Chris Brightpearl</strong> on loan <strong>1005</strong>',NULL),(47,1,2,1005,NULL,'2019-11-14 11:35:08','Checked out loan. ',NULL),(48,1,NULL,NULL,1013,'2019-11-14 11:35:19','Checked in to <strong>Second site / In stock</strong> from loan 1005. ',NULL),(49,1,2,1005,NULL,'2019-11-14 11:35:19','Checked in <strong>Mower</strong>',NULL),(50,1,NULL,NULL,1014,'2019-11-14 11:39:23','Added item to <strong>Second site / In stock</strong>',NULL),(51,1,NULL,1006,NULL,'2019-11-14 11:40:15','Loan created by Admin Admin',NULL),(52,1,NULL,NULL,1014,'2019-11-14 11:40:21','Loaned to <strong>Chris Tanner</strong> on loan <strong>1006</strong>',NULL),(53,1,6,1006,NULL,'2019-11-14 11:40:21','Checked out loan. ',NULL),(54,1,NULL,1007,NULL,'2019-11-14 11:47:58','Loan created by Admin Admin',NULL),(55,1,NULL,NULL,1013,'2019-11-14 11:48:07','Loaned to <strong>Chris Tanner</strong> on loan <strong>1007</strong>',NULL),(56,1,6,1007,NULL,'2019-11-14 11:48:07','Checked out loan. ',NULL),(57,1,NULL,NULL,1013,'2019-11-14 11:48:13','Checked in to <strong>Second site / In stock</strong> from loan 1007. ',NULL),(58,1,6,1007,NULL,'2019-11-14 11:48:13','Checked in <strong>Mower</strong>',NULL),(59,1,NULL,NULL,1013,'2019-11-14 11:48:58','',NULL),(60,1,NULL,1008,NULL,'2019-11-14 11:59:59','Reservation created by Admin Admin',NULL),(61,1,6,1008,NULL,'2019-11-14 12:00:36','Updated return date for <strong>Mower</strong> 0 days to 22 November 5:00 pm',NULL),(62,1,NULL,NULL,1013,'2019-11-14 12:09:45','Loaned to <strong>Chris Tanner</strong> on loan <strong>1008</strong>',NULL),(63,1,6,1008,NULL,'2019-11-14 12:09:45','Checked out loan. ',NULL),(64,1,6,1008,NULL,'2019-11-14 12:10:17','Updated return date for <strong>Mower</strong> 3 days to 26 November 5:00 pm',NULL),(65,1,NULL,NULL,1014,'2019-11-14 12:20:16','Checked in to <strong>Second site / In stock</strong> from loan 1006. ',NULL),(66,1,6,1006,NULL,'2019-11-14 12:20:16','Checked in <strong>Projector</strong>',NULL),(67,1,NULL,NULL,1013,'2019-11-14 12:20:24','Checked in to <strong>Second site / In stock</strong> from loan 1008. ',NULL),(68,1,6,1008,NULL,'2019-11-14 12:20:24','Checked in <strong>Mower</strong>',NULL),(69,1,NULL,1009,NULL,'2019-11-14 12:20:57','Loan created by Admin Admin',NULL),(70,1,NULL,NULL,1014,'2019-11-14 12:21:03','Loaned to <strong>Chris Tanner</strong> on loan <strong>1009</strong>',NULL),(71,1,NULL,NULL,1012,'2019-11-14 12:21:03','Loaned to <strong>Chris Tanner</strong> on loan <strong>1009</strong>',NULL),(72,1,6,1009,NULL,'2019-11-14 12:21:03','Checked out loan. ',NULL),(74,1,NULL,NULL,1009,'2019-11-14 12:45:26','Checked in to <strong>Second site / In stock</strong> from loan 1003. ',NULL),(75,1,6,1003,NULL,'2019-11-14 12:45:26','Checked in <strong>Tent</strong>',NULL),(76,1,NULL,1010,NULL,'2019-11-14 12:45:49','Reservation created by Admin Admin',NULL),(77,1,NULL,NULL,1014,'2019-11-14 13:06:40','Checked in to <strong>Warehouse / In stock</strong> from loan 1009. ',NULL),(78,1,6,1009,NULL,'2019-11-14 13:06:40','Checked in <strong>Projector</strong>',NULL),(79,1,8,NULL,NULL,'2019-11-15 11:29:50','Added by Admin Admin',NULL),(80,1,NULL,1011,NULL,'2019-11-15 12:11:58','Loan created by Admin Admin',NULL),(81,1,NULL,NULL,1014,'2019-11-15 13:58:19','Loaned to <strong>Demo Member</strong> on loan <strong>1011</strong>',NULL),(82,1,2,1011,NULL,'2019-11-15 13:58:19','Checked out loan. ',NULL),(83,1,9,NULL,NULL,'2019-11-18 09:50:53','Added by Admin Admin',NULL),(84,1,NULL,NULL,1015,'2019-11-19 10:34:28','Added item to <strong>In stock</strong>',NULL),(85,1,NULL,NULL,1016,'2019-11-19 10:35:19','Added item to <strong>In stock</strong>',NULL),(88,1,NULL,1014,NULL,'2019-11-20 11:16:33','Reservation created by Admin Admin',NULL),(89,1,NULL,1015,NULL,'2019-11-20 11:28:55','Reservation created by Admin Admin',NULL),(90,1,NULL,1016,NULL,'2019-11-20 11:33:29','Reservation created by Admin Admin',NULL),(91,1,NULL,1017,NULL,'2019-11-20 11:40:41','Reservation created by Admin Admin',NULL),(92,1,NULL,1018,NULL,'2019-11-20 11:49:38','Reservation created by Admin Admin',NULL),(93,1,NULL,NULL,1016,'2019-11-20 11:49:47','Loaned to <strong>Chris Distributed</strong> on loan <strong>1018</strong>',NULL),(94,1,6,1018,NULL,'2019-11-20 11:49:47','Checked out loan. ',NULL),(95,1,NULL,NULL,1016,'2019-11-20 11:49:53','Checked in to <strong>Warehouse / In stock</strong> from loan 1018. ',NULL),(96,1,6,1018,NULL,'2019-11-20 11:49:53','Checked in <strong>DEWALT 18V XR BRUSHLESS 1/4\" ROUTER</strong>',NULL),(97,1,NULL,NULL,1016,'2019-11-20 11:50:00','Loaned to <strong>Chris Distributed</strong> on loan <strong>1017</strong>',NULL),(98,1,6,1017,NULL,'2019-11-20 11:50:00','Checked out loan. ',NULL),(99,1,NULL,NULL,1016,'2019-11-20 11:50:06','Checked in to <strong>Warehouse / In stock</strong> from loan 1017. ',NULL),(100,1,6,1017,NULL,'2019-11-20 11:50:06','Checked in <strong>DEWALT 18V XR BRUSHLESS 1/4\" ROUTER</strong>',NULL),(101,1,NULL,1019,NULL,'2019-11-20 11:50:22','Loan created by Admin Admin',NULL),(102,1,NULL,NULL,1016,'2019-11-20 11:50:29','Loaned to <strong>Chris Distributed</strong> on loan <strong>1019</strong>',NULL),(103,1,6,1019,NULL,'2019-11-20 11:50:29','Checked out loan. ',NULL),(104,1,NULL,1020,NULL,'2019-11-20 11:54:02','Reservation created by Admin Admin',NULL),(105,1,NULL,NULL,1015,'2019-11-20 11:54:14','Loaned to <strong>Chris Distributed</strong> on loan <strong>1020</strong>',NULL),(106,1,NULL,NULL,1008,'2019-11-20 11:54:14','Loaned to <strong>Chris Distributed</strong> on loan <strong>1020</strong>',NULL),(107,1,NULL,NULL,1005,'2019-11-20 11:54:14','Loaned to <strong>Chris Distributed</strong> on loan <strong>1020</strong>',NULL),(108,1,6,1020,NULL,'2019-11-20 11:54:14','Checked out loan. ',NULL),(109,1,NULL,NULL,1015,'2019-11-20 11:54:40','Checked in to <strong>Warehouse / In stock</strong> from loan 1020. ',NULL),(110,1,6,1020,NULL,'2019-11-20 11:54:40','Checked in <strong>Acer P1150 Projector</strong>',NULL),(111,1,NULL,NULL,1005,'2019-11-20 11:54:44','Checked in to <strong>Warehouse / In stock</strong> from loan 1020. ',NULL),(112,1,6,1020,NULL,'2019-11-20 11:54:44','Checked in <strong>Kit part A</strong>',NULL),(113,1,NULL,NULL,1015,'2019-11-26 10:13:29','New bulb needed',NULL),(114,1,NULL,NULL,1015,'2019-11-26 10:29:23','',NULL),(115,1,6,1019,NULL,'2019-11-26 11:20:55','Updated return date for <strong>DEWALT 18V XR BRUSHLESS 1/4\" ROUTER</strong> 1 day to 29 November 5:00 pm (extension fee 0.75)',NULL),(116,1,NULL,1021,NULL,'2019-11-26 12:01:33','Loan created by Admin Admin',NULL),(117,1,NULL,1022,NULL,'2019-11-26 12:03:08','Reservation created by Admin Admin',NULL),(118,1,NULL,1022,NULL,'2019-11-26 12:03:20','Cancelled by Admin Admin.',NULL),(119,1,NULL,NULL,1016,'2019-11-26 15:28:18','Checked in to <strong>Warehouse / In stock</strong> from loan 1019. ',NULL),(120,1,6,1019,NULL,'2019-11-26 15:28:18','Checked in <strong>DEWALT 18V XR BRUSHLESS 1/4\" ROUTER</strong>',NULL),(121,1,NULL,NULL,1012,'2019-11-26 15:40:37','Checked in to <strong>Warehouse / In stock</strong> from loan 1009. ',NULL),(122,1,6,1009,NULL,'2019-11-26 15:40:37','Checked in <strong>Carpet dryer</strong>',NULL),(123,1,NULL,NULL,1011,'2019-11-26 15:41:59','Checked in to <strong>Warehouse / In stock</strong> from loan 1002. ',NULL),(124,1,2,1002,NULL,'2019-11-26 15:41:59','Checked in <strong>DEWALT 18V XR BRUSHLESS 1/4\" ROUTER</strong>',NULL),(125,1,NULL,1023,NULL,'2019-11-26 16:47:05','Loan created by Admin Admin',NULL),(126,1,NULL,NULL,1012,'2019-11-26 16:47:08','Loaned to <strong>Demo Member</strong> on loan <strong>1023</strong>',NULL),(127,1,2,1023,NULL,'2019-11-26 16:47:08','Checked out loan. ',NULL),(128,1,NULL,NULL,1012,'2019-11-26 16:47:17','Checked in to <strong>Warehouse / In stock</strong> from loan 1023. ',NULL),(129,1,2,1023,NULL,'2019-11-26 16:47:17','Checked in <strong>Carpet dryer</strong>',NULL),(130,1,NULL,1024,NULL,'2019-11-26 16:47:40','Loan created by Admin Admin',NULL),(131,1,NULL,NULL,1015,'2019-11-26 16:47:43','Loaned to <strong>Chris Tanner</strong> on loan <strong>1024</strong>',NULL),(132,1,4,1024,NULL,'2019-11-26 16:47:43','Checked out loan. ',NULL),(133,1,NULL,NULL,1015,'2019-11-26 16:47:52','Checked in to <strong>Warehouse / In stock</strong> from loan 1024. ',NULL),(134,1,4,1024,NULL,'2019-11-26 16:47:52','Checked in <strong>Acer P1150 Projector</strong>',NULL),(135,1,NULL,1025,NULL,'2019-11-26 16:55:58','Loan created by Admin Admin',NULL),(136,1,NULL,NULL,1015,'2019-11-26 16:56:01','Loaned to <strong>Chris Tanner</strong> on loan <strong>1025</strong>',NULL),(137,1,4,1025,NULL,'2019-11-26 16:56:01','Checked out loan. ',NULL),(138,1,NULL,NULL,1015,'2019-11-26 16:56:06','Checked in to <strong>Warehouse / In stock</strong> from loan 1025. ',NULL),(139,1,4,1025,NULL,'2019-11-26 16:56:06','Checked in <strong>Acer P1150 Projector</strong>',NULL),(140,1,NULL,1026,NULL,'2019-11-26 16:56:33','Loan created by Admin Admin',NULL),(141,1,NULL,NULL,1015,'2019-11-26 16:56:36','Loaned to <strong>Chris Tanner</strong> on loan <strong>1026</strong>',NULL),(142,1,4,1026,NULL,'2019-11-26 16:56:36','Checked out loan. ',NULL),(143,1,NULL,NULL,1015,'2019-11-26 16:56:43','Checked in to <strong>Warehouse / In stock</strong> from loan 1026. ',NULL),(144,1,4,1026,NULL,'2019-11-26 16:56:43','Checked in <strong>Acer P1150 Projector</strong>',NULL),(145,1,NULL,1027,NULL,'2019-11-27 09:23:05','Loan created by Admin Admin',NULL),(146,1,NULL,NULL,1013,'2019-11-27 09:23:16','Loaned to <strong>Chris Distributed</strong> on loan <strong>1027</strong>',NULL),(147,1,6,1027,NULL,'2019-11-27 09:23:16','Checked out loan. ',NULL),(148,1,NULL,1028,NULL,'2019-11-27 09:23:43','Loan created by Admin Admin',NULL),(149,1,NULL,NULL,1011,'2019-11-27 09:23:45','Loaned to <strong>Chris Tanner</strong> on loan <strong>1028</strong>',NULL),(150,1,4,1028,NULL,'2019-11-27 09:23:45','Checked out loan. ',NULL),(151,1,NULL,NULL,1011,'2019-11-27 09:23:52','Checked in to <strong>Warehouse / In stock</strong> from loan 1028. ',NULL),(152,1,4,1028,NULL,'2019-11-27 09:23:52','Checked in <strong>DEWALT 18V XR BRUSHLESS 1/4\" ROUTER</strong>',NULL),(153,1,NULL,1029,NULL,'2019-11-27 09:34:41','Loan created by Admin Admin',NULL),(154,1,NULL,NULL,1011,'2019-11-27 09:34:47','Loaned to <strong>Chris Tanner</strong> on loan <strong>1029</strong>',NULL),(155,1,4,1029,NULL,'2019-11-27 09:34:47','Checked out loan. ',NULL),(156,1,NULL,NULL,1011,'2019-11-27 09:36:25','Checked in to <strong>Warehouse / In stock</strong> from loan 1029. ',NULL),(157,1,4,1029,NULL,'2019-11-27 09:36:25','Checked in <strong>DEWALT 18V XR BRUSHLESS 1/4\" ROUTER</strong>',NULL),(158,1,NULL,NULL,1013,'2019-11-27 09:37:32','Checked in to <strong>Warehouse / In stock</strong> from loan 1027. ',NULL),(159,1,6,1027,NULL,'2019-11-27 09:37:32','Checked in <strong>Mower</strong>',NULL),(160,1,NULL,1030,NULL,'2019-11-27 09:42:06','Loan created by Admin Admin',NULL),(161,1,NULL,NULL,1010,'2019-11-27 09:42:22','Loaned to <strong>Chris Tanner</strong> on loan <strong>1030</strong>',NULL),(162,1,NULL,NULL,1011,'2019-11-27 09:42:22','Loaned to <strong>Chris Tanner</strong> on loan <strong>1030</strong>',NULL),(163,1,4,1030,NULL,'2019-11-27 09:42:22','Checked out loan. ',NULL),(164,1,NULL,NULL,1010,'2019-11-27 09:43:12','Checked in to <strong>Warehouse / In stock</strong> from loan 1030. ',NULL),(165,1,4,1030,NULL,'2019-11-27 09:43:12','Checked in <strong>DEWALT 18V XR BRUSHLESS CORDLESS COMBI DRILL</strong>',NULL),(166,1,NULL,NULL,1011,'2019-11-27 09:43:51','Checked in to <strong>Warehouse / In stock</strong> from loan 1030. ',NULL),(167,1,4,1030,NULL,'2019-11-27 09:43:51','Checked in <strong>DEWALT 18V XR BRUSHLESS 1/4\" ROUTER</strong>',NULL),(168,1,NULL,1031,NULL,'2019-11-27 09:44:06','Loan created by Admin Admin',NULL),(169,1,NULL,NULL,1011,'2019-11-27 09:44:12','Loaned to <strong>Chris Tanner</strong> on loan <strong>1031</strong>',NULL),(170,1,4,1031,NULL,'2019-11-27 09:44:12','Checked out loan. ',NULL),(171,1,NULL,1032,NULL,'2019-11-27 09:45:37','Reservation created by Admin Admin',NULL),(172,1,NULL,1033,NULL,'2019-11-27 09:49:46','Reservation created by Admin Admin',NULL),(173,1,NULL,NULL,1016,'2019-11-27 09:52:03','Loaned to <strong>Chris Tanner</strong> on loan <strong>1033</strong>',NULL),(174,1,4,1033,NULL,'2019-11-27 09:52:03','Checked out loan. ',NULL),(175,1,NULL,NULL,1010,'2019-11-27 09:52:42','Loaned to <strong>Chris Tanner</strong> on loan <strong>1032</strong>',NULL),(176,1,4,1032,NULL,'2019-11-27 09:52:42','Checked out loan. ',NULL),(177,1,NULL,NULL,1010,'2019-11-27 09:53:21','Checked in to <strong>Warehouse / In stock</strong> from loan 1032. ',NULL),(178,1,4,1032,NULL,'2019-11-27 09:53:21','Checked in <strong>DEWALT 18V XR BRUSHLESS CORDLESS COMBI DRILL</strong>',NULL),(180,1,NULL,1034,NULL,'2019-11-27 09:54:16','Reservation created by Admin Admin',NULL),(181,1,8,1034,NULL,'2019-11-27 09:54:37','Updated return date for <strong>DEWALT 18V XR BRUSHLESS CORDLESS COMBI DRILL</strong> 1 day to 05 December 5:00 pm',NULL),(182,1,NULL,NULL,1010,'2019-11-27 09:54:48','Loaned to <strong>Kelly Banks</strong> on loan <strong>1034</strong>',NULL),(183,1,8,1034,NULL,'2019-11-27 09:54:48','Checked out loan. ',NULL),(184,1,NULL,NULL,1010,'2019-11-27 09:54:59','Checked in to <strong>Warehouse / In stock</strong> from loan 1034. ',NULL),(185,1,8,1034,NULL,'2019-11-27 09:54:59','Checked in <strong>DEWALT 18V XR BRUSHLESS CORDLESS COMBI DRILL</strong>',NULL),(186,1,NULL,NULL,1016,'2019-11-27 10:13:22','Checked in to <strong>Warehouse / In stock</strong> from loan 1033. ',NULL),(187,1,4,1033,NULL,'2019-11-27 10:13:22','Checked in <strong>DEWALT 18V XR BRUSHLESS 1/4\" ROUTER</strong>',NULL),(188,1,NULL,1035,NULL,'2019-11-28 09:51:22','Reservation created by Admin Admin',NULL),(189,1,NULL,1036,NULL,'2019-11-29 11:08:20','Reservation created by Admin Admin',NULL),(190,1,NULL,NULL,1016,'2019-11-29 11:11:21','Loaned to <strong>Chris Tanner</strong> on loan <strong>1036</strong>',NULL),(191,1,4,1036,NULL,'2019-11-29 11:11:21','Checked out loan. ',NULL),(192,1,NULL,NULL,1016,'2019-11-29 11:12:41','Checked in to <strong>Warehouse / In stock</strong> from loan 1036. ',NULL),(193,1,4,1036,NULL,'2019-11-29 11:12:41','Checked in <strong>DEWALT 18V XR BRUSHLESS 1/4\" ROUTER</strong>',NULL),(194,1,NULL,NULL,1015,'2019-11-29 11:13:00','Loaned to <strong>Chris Tanner</strong> on loan <strong>1035</strong>',NULL),(195,1,4,1035,NULL,'2019-11-29 11:13:00','Checked out loan. ',NULL),(196,1,NULL,NULL,1015,'2019-11-29 11:14:28','Checked in to <strong>Warehouse / In stock</strong> from loan 1035. ',NULL),(197,1,4,1035,NULL,'2019-11-29 11:14:28','Checked in <strong>Acer P1150 Projector</strong>',NULL),(198,1,NULL,1037,NULL,'2019-11-29 11:14:40','Reservation created by Admin Admin',NULL),(199,1,NULL,NULL,1015,'2019-11-29 11:14:42','Loaned to <strong>Chris Tanner</strong> on loan <strong>1037</strong>',NULL),(200,1,4,1037,NULL,'2019-11-29 11:14:42','Checked out loan. ',NULL),(201,1,NULL,NULL,1015,'2019-11-29 11:15:46','Checked in to <strong>Warehouse / In stock</strong> from loan 1037. ',NULL),(202,1,4,1037,NULL,'2019-11-29 11:15:46','Checked in <strong>Acer P1150 Projector</strong>',NULL),(203,1,NULL,1038,NULL,'2019-11-29 11:16:20','Loan created by Admin Admin',NULL),(204,1,NULL,NULL,1015,'2019-11-29 11:16:22','Loaned to <strong>Chris Tanner</strong> on loan <strong>1038</strong>',NULL),(205,1,4,1038,NULL,'2019-11-29 11:16:22','Checked out loan. ',NULL),(206,1,NULL,NULL,1015,'2019-11-29 11:18:12','Checked in to <strong>Warehouse / In stock</strong> from loan 1038. ',NULL),(207,1,4,1038,NULL,'2019-11-29 11:18:12','Checked in <strong>Acer P1150 Projector</strong>',NULL),(208,1,NULL,1039,NULL,'2019-11-29 11:19:06','Loan created by Admin Admin',NULL),(209,1,NULL,NULL,1015,'2019-11-29 11:19:26','Loaned to <strong>Chris Tanner</strong> on loan <strong>1039</strong>',NULL),(210,1,4,1039,NULL,'2019-11-29 11:19:26','Checked out loan. ',NULL),(211,1,NULL,1040,NULL,'2019-12-02 13:14:40','Loan created by Admin Admin',NULL),(212,1,NULL,NULL,1010,'2019-12-02 13:15:16','Loaned to <strong>Chris Tanner</strong> on loan <strong>1040</strong>',NULL),(213,1,4,1040,NULL,'2019-12-02 13:15:16','Checked out loan. ',NULL),(214,1,NULL,NULL,1010,'2019-12-02 13:23:58','Checked in to <strong>Warehouse / In stock</strong> from loan 1040. ',NULL),(215,1,4,1040,NULL,'2019-12-02 13:23:58','Checked in <strong>DEWALT 18V XR BRUSHLESS CORDLESS COMBI DRILL</strong>',NULL),(216,1,NULL,1041,NULL,'2019-12-02 13:24:32','Loan created by Admin Admin',NULL),(217,1,NULL,NULL,1006,'2019-12-02 13:25:05','Loaned to <strong>Chris Tanner</strong> on loan <strong>1041</strong>',NULL),(218,1,4,1041,NULL,'2019-12-02 13:25:05','Checked out loan. ',NULL),(219,1,NULL,NULL,1006,'2019-12-02 13:28:04','Checked in to <strong>Warehouse / In stock</strong> from loan 1041. ',NULL),(220,1,4,1041,NULL,'2019-12-02 13:28:04','Checked in <strong>Kit part B</strong>',NULL),(221,1,NULL,1042,NULL,'2019-12-02 13:28:21','Loan created by Admin Admin',NULL),(222,1,NULL,NULL,1006,'2019-12-02 13:28:31','Loaned to <strong>Chris Tanner</strong> on loan <strong>1042</strong>',NULL),(223,1,4,1042,NULL,'2019-12-02 13:28:31','Checked out loan. ',NULL),(224,1,4,1042,NULL,'2019-12-02 13:30:07','Added fee of £1; test',NULL),(225,1,4,1042,NULL,'2019-12-02 13:30:07','Added fee of £1; test',NULL),(226,1,4,1042,NULL,'2019-12-02 13:30:15','Deleted fee of £1.00',NULL),(227,1,NULL,NULL,1006,'2019-12-02 13:30:23','Checked in to <strong>Warehouse / In stock</strong> from loan 1042. ',NULL),(228,1,4,1042,NULL,'2019-12-02 13:30:23','Checked in <strong>Kit part B</strong>',NULL),(229,1,NULL,1043,NULL,'2019-12-02 13:30:46','Reservation created by Admin Admin',NULL),(230,1,NULL,NULL,1006,'2019-12-02 13:30:52','Loaned to <strong>Chris Tanner</strong> on loan <strong>1043</strong>',NULL),(231,1,4,1043,NULL,'2019-12-02 13:30:52','Checked out loan. ',NULL),(232,1,NULL,NULL,1015,'2019-12-10 10:05:00','Checked in to <strong>Warehouse / In stock</strong> from loan 1039. ',NULL),(233,1,4,1039,NULL,'2019-12-10 10:05:00','Checked in <strong>Acer P1150 Projector</strong>',NULL),(234,1,6,NULL,NULL,'2019-12-13 09:58:13','Sent email \'Hey mister\'',NULL),(235,1,NULL,NULL,1006,'2019-12-16 11:20:05','Checked in to <strong>Warehouse / In stock</strong> from loan 1043. ',NULL),(236,1,4,1043,NULL,'2019-12-16 11:20:05','Checked in <strong>Kit part B</strong>',NULL),(237,1,NULL,NULL,1011,'2019-12-16 11:20:33','Checked in to <strong>Warehouse / In stock</strong> from loan 1031. ',NULL),(238,1,4,1031,NULL,'2019-12-16 11:20:33','Checked in <strong>DEWALT 18V XR BRUSHLESS 1/4\" ROUTER</strong>',NULL),(239,1,NULL,1044,NULL,'2019-12-16 11:22:10','Loan created by Admin Admin',NULL),(240,1,NULL,NULL,1011,'2019-12-16 11:22:14','Loaned to <strong>Chris Tanner</strong> on loan <strong>1044</strong>',NULL),(241,1,4,1044,NULL,'2019-12-16 11:22:14','Checked out loan. ',NULL),(242,1,NULL,NULL,1011,'2019-12-16 11:22:45','Checked in to <strong>Warehouse / In stock</strong> from loan 1044. ',NULL),(243,1,4,1044,NULL,'2019-12-16 11:22:45','Checked in <strong>DEWALT 18V XR BRUSHLESS 1/4\" ROUTER</strong>',NULL),(244,1,NULL,1045,NULL,'2019-12-16 11:24:31','Loan created by Admin Admin',NULL),(245,1,NULL,NULL,1011,'2019-12-16 11:24:37','Loaned to <strong>Chris Tanner</strong> on loan <strong>1045</strong>',NULL),(246,1,4,1045,NULL,'2019-12-16 11:24:37','Checked out loan. ',NULL),(247,1,NULL,NULL,1011,'2019-12-16 11:25:03','Checked in to <strong>Warehouse / In stock</strong> from loan 1045. ',NULL),(248,1,4,1045,NULL,'2019-12-16 11:25:03','Checked in <strong>DEWALT 18V XR BRUSHLESS 1/4\" ROUTER</strong>',NULL),(249,1,NULL,1046,NULL,'2019-12-16 11:25:39','Loan created by Admin Admin',NULL),(250,1,NULL,NULL,1011,'2019-12-16 11:25:44','Loaned to <strong>Chris Tanner</strong> on loan <strong>1046</strong>',NULL),(251,1,4,1046,NULL,'2019-12-16 11:25:44','Checked out loan. ',NULL),(252,1,NULL,NULL,1011,'2019-12-16 11:26:09','Checked in to <strong>Warehouse / In stock</strong> from loan 1046. ',NULL),(253,1,4,1046,NULL,'2019-12-16 11:26:09','Checked in <strong>DEWALT 18V XR BRUSHLESS 1/4\" ROUTER</strong>',NULL),(254,1,NULL,1047,NULL,'2019-12-16 11:26:28','Loan created by Admin Admin',NULL),(255,1,NULL,NULL,1011,'2019-12-16 11:26:32','Loaned to <strong>Chris Tanner</strong> on loan <strong>1047</strong>',NULL),(256,1,4,1047,NULL,'2019-12-16 11:26:32','Checked out loan. ',NULL),(257,1,NULL,NULL,1011,'2019-12-16 11:28:00','Checked in to <strong>Warehouse / In stock</strong> from loan 1047. ',NULL),(258,1,4,1047,NULL,'2019-12-16 11:28:00','Checked in <strong>DEWALT 18V XR BRUSHLESS 1/4\" ROUTER</strong>',NULL),(259,1,NULL,1048,NULL,'2019-12-16 11:28:31','Loan created by Admin Admin',NULL),(260,1,NULL,NULL,1011,'2019-12-16 11:28:39','Loaned to <strong>Chris Tanner</strong> on loan <strong>1048</strong>',NULL),(261,1,4,1048,NULL,'2019-12-16 11:28:39','Checked out loan. ',NULL),(262,1,NULL,NULL,1011,'2019-12-16 11:28:57','Checked in to <strong>Warehouse / In stock</strong> from loan 1048. ',NULL),(263,1,4,1048,NULL,'2019-12-16 11:28:57','Checked in <strong>DEWALT 18V XR BRUSHLESS 1/4\" ROUTER</strong>',NULL),(264,1,NULL,1049,NULL,'2019-12-16 11:29:20','Loan created by Admin Admin',NULL),(265,1,NULL,NULL,1011,'2019-12-16 11:29:24','Loaned to <strong>Chris Tanner</strong> on loan <strong>1049</strong>',NULL),(266,1,4,1049,NULL,'2019-12-16 11:29:24','Checked out loan. ',NULL),(267,1,NULL,NULL,1011,'2019-12-16 11:29:37','Checked in to <strong>Warehouse / In stock</strong> from loan 1049. ',NULL),(268,1,4,1049,NULL,'2019-12-16 11:29:37','Checked in <strong>DEWALT 18V XR BRUSHLESS 1/4\" ROUTER</strong>',NULL),(270,1,NULL,NULL,1017,'2020-01-29 11:32:32','Added item to <strong>South campus / In stock</strong>',NULL),(271,1,NULL,NULL,1012,'2020-01-29 11:46:19','Removed from \"Warehouse / In stock\"',NULL),(272,1,NULL,1050,NULL,'2020-01-29 13:27:59','Loan created by Admin Admin',NULL),(273,1,4,1050,NULL,'2020-01-29 13:38:27','Completed sale. ',NULL),(274,1,NULL,NULL,1017,'2020-01-29 15:03:16','Added 1 to <strong>South campus / In stock</strong>',NULL),(275,1,NULL,NULL,1017,'2020-01-29 15:03:16','Removed 1 from <strong>Warehouse / In stock</strong>',NULL),(276,1,NULL,NULL,1017,'2020-01-29 15:09:31','Added 2 to <strong>North campus / In stock</strong>. New stock bought today',NULL),(277,1,NULL,NULL,1017,'2020-01-29 15:09:50','Removed 2 from <strong>North campus / In stock</strong>. Water damage',NULL),(278,1,NULL,NULL,1017,'2020-01-29 15:15:53','Removed 2 from <strong>Warehouse / Repair</strong>. ',NULL),(279,1,NULL,1051,NULL,'2020-01-29 15:45:46','Loan created by Admin Admin',NULL),(280,1,4,1051,NULL,'2020-01-29 15:49:38','Completed sale. ',NULL),(281,1,NULL,1052,NULL,'2020-01-29 15:54:20','Loan created by Admin Admin',NULL),(282,1,4,1052,NULL,'2020-01-29 16:20:42','Completed sale. ',NULL),(283,1,NULL,1053,NULL,'2020-01-29 16:29:32','Loan created by Admin Admin',NULL),(284,1,4,1053,NULL,'2020-01-29 16:29:50','Completed sale. ',NULL),(285,1,NULL,1054,NULL,'2020-01-29 16:34:40','Loan created by Admin Admin',NULL),(286,1,NULL,NULL,1017,'2020-01-29 16:34:46','Sold 1 to <strong>Chris Tanner</strong> on loan <strong>1054</strong>',NULL),(287,1,4,1054,NULL,'2020-01-29 16:34:46','Completed sale. ',NULL),(288,1,NULL,1055,NULL,'2020-01-29 16:36:17','Loan created by Admin Admin',NULL),(289,1,NULL,NULL,1017,'2020-01-29 16:36:28','Sold 1 from <strong>Warehouse / Repair</strong> to <strong>Chris Tanner</strong> on loan <strong>1055</strong>',NULL),(290,1,4,1055,NULL,'2020-01-29 16:36:28','Completed sale. ',NULL),(291,1,NULL,1056,NULL,'2020-01-29 16:40:54','Loan created by Admin Admin',NULL),(292,1,NULL,NULL,1017,'2020-01-29 16:42:33','Sold 1 from <strong>Warehouse / Repair</strong> to <strong>Chris Tanner</strong> on loan <strong>1056</strong>',NULL),(293,1,NULL,NULL,1015,'2020-01-29 16:42:33','Loaned to <strong>Chris Tanner</strong> on loan <strong>1056</strong>',NULL),(294,1,4,1056,NULL,'2020-01-29 16:42:33','Checked out loan. ',NULL),(295,1,NULL,NULL,1015,'2020-01-29 16:43:23','Checked in to <strong>South campus / In stock</strong> from loan 1056. ',NULL),(296,1,4,1056,NULL,'2020-01-29 16:43:23','Checked in <strong>Acer P1150 Projector</strong>',NULL),(297,1,NULL,1057,NULL,'2020-01-29 16:49:25','Loan created by Admin Admin',NULL),(298,1,NULL,1058,NULL,'2020-01-30 10:10:04','Loan created by Admin Admin',NULL),(299,1,NULL,NULL,1011,'2020-01-30 10:10:26','Loaned to <strong>Chris Tanner</strong> on loan <strong>1058</strong>',NULL),(300,1,NULL,NULL,1017,'2020-01-30 10:10:26','Sold 2 from <strong>Warehouse / Repair</strong> to <strong>Chris Tanner</strong> on loan <strong>1058</strong>',NULL),(301,1,4,1058,NULL,'2020-01-30 10:10:26','Checked out loan. ',NULL),(302,1,NULL,NULL,1018,'2020-01-30 10:14:19','Added 1 x item to <strong>South campus / In stock</strong>',NULL),(303,1,NULL,NULL,1018,'2020-01-30 10:18:21','Added 10 to <strong>South campus / In stock</strong>. PO43',NULL),(304,1,NULL,1059,NULL,'2020-01-30 12:20:21','Loan created by Admin Admin',NULL),(305,1,4,1058,NULL,'2020-01-30 13:00:15','Added fee of £25; Sold router',NULL),(306,1,NULL,1060,NULL,'2020-01-30 13:05:41','Loan created by Admin Admin',NULL),(307,1,NULL,NULL,1015,'2020-01-30 13:05:56','Loaned to <strong>Chris Tanner</strong> on loan <strong>1060</strong>',NULL),(308,1,4,1060,NULL,'2020-01-30 13:05:56','Checked out loan. ',NULL),(309,1,4,1060,NULL,'2020-01-30 13:16:21','Added fee of £10; Sold item',NULL),(310,1,NULL,NULL,1020,'2020-01-30 13:24:40','Added 1 x item to <strong>South campus / In stock</strong>',NULL),(311,1,NULL,1061,NULL,'2020-01-30 13:24:55','Loan created by Admin Admin',NULL),(312,1,NULL,NULL,1020,'2020-01-30 13:25:02','Loaned to <strong>Chris Tanner</strong> on loan <strong>1061</strong>',NULL),(313,1,4,1061,NULL,'2020-01-30 13:25:02','Checked out loan. ',NULL),(314,1,NULL,NULL,1021,'2020-01-30 13:33:01','Added 1 x item to <strong>South campus / In stock</strong>',NULL),(315,1,NULL,1062,NULL,'2020-01-30 13:33:14','Loan created by Admin Admin',NULL),(316,1,NULL,NULL,1021,'2020-01-30 13:33:17','Loaned to <strong>Chris Tanner</strong> on loan <strong>1062</strong>',NULL),(317,1,4,1062,NULL,'2020-01-30 13:33:17','Checked out loan. ',NULL),(318,1,NULL,1062,1021,'2020-01-30 13:33:24','Sold Vest (sold) to Chris Tanner',NULL),(319,1,NULL,1011,1014,'2020-01-30 13:39:20','Sold Acer P1150 Projector to Demo Member',NULL),(320,1,NULL,NULL,1011,'2020-01-30 13:49:31','Archived. with note:\nSold',NULL),(321,1,NULL,NULL,1022,'2020-01-30 14:13:32','Added 1 x item to <strong>South campus / In stock</strong>',NULL),(322,1,NULL,1063,NULL,'2020-01-30 14:13:42','Loan created by Admin Admin',NULL),(323,1,NULL,NULL,1022,'2020-01-30 14:13:46','Loaned to <strong>Chris Distributed</strong> on loan <strong>1063</strong>',NULL),(324,1,6,1063,NULL,'2020-01-30 14:13:46','Checked out loan. ',NULL),(325,1,NULL,NULL,1015,'2020-01-30 15:08:05','Added 3 to <strong>Warehouse / In stock</strong>. ',NULL),(326,1,NULL,NULL,1015,'2020-01-30 15:08:17','Added 3 to <strong>North campus / In stock</strong>. ',NULL),(327,1,NULL,NULL,1015,'2020-01-30 15:08:31','Added 3 to <strong>Warehouse / Repair</strong>. ',NULL),(328,1,NULL,NULL,1015,'2020-01-30 15:09:23','Added 13 to <strong>South campus / Repair</strong>. ',NULL),(329,1,NULL,NULL,1015,'2020-01-30 15:09:29','Removed 1 from <strong>South campus / Repair</strong>. ',NULL),(330,1,NULL,1064,NULL,'2020-01-30 15:13:13','Loan created by Admin Admin',NULL),(331,1,NULL,NULL,1009,'2020-01-30 15:13:18','Loaned to <strong>Chris Distributed</strong> on loan <strong>1064</strong>',NULL),(332,1,6,1064,NULL,'2020-01-30 15:13:18','Checked out loan. ',NULL),(333,1,NULL,1064,1009,'2020-01-30 15:13:26','Checked in to <strong>South campus / In stock</strong> from loan 1064. ',NULL),(334,1,6,1064,NULL,'2020-01-30 15:13:26','Checked in <strong>Olaf 4-man tent</strong>',NULL),(335,1,NULL,NULL,1023,'2020-01-30 15:21:44','Added 1 x item to <strong>South campus / In stock</strong>',NULL),(336,1,NULL,1065,NULL,'2020-01-30 15:21:55','Loan created by Admin Admin',NULL),(337,1,NULL,1065,1023,'2020-01-30 15:21:59','Loaned to <strong>Chris Distributed</strong>',NULL),(338,1,6,1065,NULL,'2020-01-30 15:21:59','Checked out loan. ',NULL),(339,1,NULL,1065,1023,'2020-01-30 15:23:54','Checked in to <strong>South campus / In stock</strong>.\nCheck in note',NULL),(340,1,6,1065,1023,'2020-01-30 15:23:54','',NULL),(341,1,NULL,NULL,1024,'2020-01-30 15:25:47','Added 1 x item to <strong>South campus / In stock</strong>',NULL),(342,1,NULL,1066,NULL,'2020-01-30 15:26:01','Loan created by Admin Admin',NULL),(343,1,NULL,1066,1024,'2020-01-30 15:26:04','Loaned to <strong>Chris Distributed</strong>',NULL),(344,1,6,1066,NULL,'2020-01-30 15:26:04','Checked out loan. ',NULL),(345,1,NULL,1066,1024,'2020-01-30 15:26:25','Checked in to <strong>South campus / In stock</strong>.\nNote',NULL),(346,1,6,1066,1024,'2020-01-30 15:26:25','Check-in fee 2.00 ',NULL),(347,1,NULL,1067,NULL,'2020-01-30 16:31:54','Reservation created by Admin Admin',NULL),(348,1,NULL,1068,NULL,'2020-01-30 16:34:06','Reservation created by Admin Admin',NULL),(349,1,NULL,1057,NULL,'2020-01-30 16:39:13','Cancelled by Admin Admin.',NULL),(350,1,NULL,1069,NULL,'2020-01-31 10:20:41','Loan created by Admin Admin',NULL),(351,1,NULL,1069,1023,'2020-01-31 10:20:50','Loaned to <strong>Chris Distributed</strong>',NULL),(352,1,6,1069,NULL,'2020-01-31 10:20:50','Checked out loan. ',NULL),(353,4,NULL,1070,NULL,'2020-02-03 19:25:43','Reservation created by Chris Tanner',NULL),(354,4,NULL,1071,NULL,'2020-02-03 19:27:21','Loan created by Chris Tanner',NULL),(355,4,NULL,NULL,1018,'2020-02-03 19:27:40','Sold 1 from <strong>South campus / In stock</strong> to <strong>Chris Tanner</strong> on loan <strong>1071</strong>',NULL),(356,4,4,1071,NULL,'2020-02-03 19:27:40','Completed sale. ',NULL),(357,4,NULL,1072,NULL,'2020-02-03 19:28:38','Loan created by Chris Tanner',NULL),(358,1,NULL,1073,NULL,'2020-02-10 10:08:30','Reservation created by Admin Admin',NULL),(359,7,NULL,1074,NULL,'2020-02-10 10:12:15','Reservation created by Katy Did',NULL),(360,1,NULL,NULL,1025,'2020-02-13 09:27:10','Added 1 x item to <strong>South campus / In stock</strong>',NULL),(362,1,1,NULL,NULL,'2020-02-13 09:33:35','Membership start date changed from <strong>13 Feb 2020</strong> to <strong>12 Feb 2020</strong>',NULL),(363,1,4,NULL,NULL,'2020-02-13 09:36:54','Subscribed to Temporary membership.',NULL),(364,7,NULL,1075,NULL,'2020-02-13 10:00:57','Reservation created by Katy Did',NULL),(366,1,7,1075,NULL,'2020-02-24 15:54:09','Added fee of £10; removing balance',NULL),(367,1,7,1075,NULL,'2020-02-24 15:54:22','Deleted fee of £10.00',NULL),(368,1,NULL,1076,NULL,'2020-02-24 16:13:18','Reservation created by Admin Admin',NULL),(370,1,NULL,1075,1024,'2020-02-25 13:15:34','Loaned to <strong>Katy Did</strong>',NULL),(371,1,7,1075,NULL,'2020-02-25 13:15:34','Checked out loan. ',NULL),(372,1,NULL,NULL,1026,'2020-02-25 13:30:57','Added item to <strong>In stock</strong>',NULL),(373,1,NULL,NULL,1027,'2020-02-25 13:30:57','Added item to <strong>In stock</strong>',NULL),(374,1,NULL,NULL,1028,'2020-02-25 13:30:57','Added item to <strong>In stock</strong>',NULL),(375,1,NULL,NULL,1027,'2020-02-25 13:31:21','Moved to <strong>Warehouse / Repair</strong>. ',NULL),(376,1,NULL,NULL,1016,'2020-02-25 14:09:32','Moved to <strong>South campus / Repair</strong>. ',NULL),(377,1,NULL,1077,NULL,'2020-02-25 14:21:33','Loan created by Admin Admin',NULL),(378,1,NULL,1077,1013,'2020-02-25 14:21:47','Loaned to <strong>Chris Tanner</strong>',NULL),(379,1,4,1077,NULL,'2020-02-25 14:21:47','Checked out loan. ',NULL),(380,1,NULL,1078,NULL,'2020-02-26 08:58:15','Loan created by Primary Admin',NULL),(381,1,NULL,1077,1013,'2020-02-26 10:03:19','Checked in to <strong>North campus / In stock</strong>.',NULL),(382,1,NULL,1079,NULL,'2020-02-26 10:03:59','Loan created by Primary Admin',NULL),(383,1,NULL,1079,1013,'2020-02-26 10:05:43','Loaned to <strong>Chris Tanner</strong>',NULL),(384,1,4,1079,NULL,'2020-02-26 10:05:43','Checked out loan. ',NULL),(385,1,NULL,1080,NULL,'2020-02-26 10:16:42','Loan created by Primary Admin',NULL),(386,1,NULL,NULL,1004,'2020-02-26 10:18:03','Moved to <strong>South campus / In stock</strong>. ',NULL),(387,1,NULL,NULL,1018,'2020-02-26 10:47:10','Sold 1 from <strong>South campus / In stock</strong> to <strong>Demo Member</strong> on loan <strong>1078</strong>',NULL),(388,1,NULL,1078,1007,'2020-02-26 10:47:10','Loaned to <strong>Demo Member</strong>',NULL),(389,1,2,1078,NULL,'2020-02-26 10:47:10','Checked out loan. ',NULL),(390,1,2,1004,NULL,'2020-03-02 11:40:01','Added fee of £1; Item was damaged',NULL),(391,1,NULL,1081,NULL,'2020-03-02 11:47:38','Loan created by Primary Admin',NULL),(392,1,NULL,1081,1009,'2020-03-02 11:50:37','Loaned to <strong>Demo Member</strong>',NULL),(393,1,2,1081,NULL,'2020-03-02 11:50:37','Checked out loan. ',NULL),(394,1,NULL,1081,1009,'2020-03-02 12:55:07','Checked in to <strong>North campus / In stock</strong>.',NULL),(395,1,NULL,NULL,1004,'2020-03-02 12:58:12','My test is required',NULL),(396,1,39,NULL,NULL,'2020-03-02 13:15:29','Added by Primary Admin',NULL),(397,40,40,NULL,NULL,'2020-03-02 13:37:18','Subscribed to Regular membership.',NULL),(398,40,NULL,1082,NULL,'2020-03-02 13:37:58','Loan created by Hap Ness',NULL),(399,40,NULL,1082,1006,'2020-03-02 13:38:13','Loaned to <strong>Hap Ness</strong>',NULL),(400,40,40,1082,NULL,'2020-03-02 13:38:13','Checked out loan. ',NULL),(401,1,29,NULL,NULL,'2020-03-02 13:44:28','test',NULL),(402,1,40,1082,NULL,'2020-03-02 13:54:16','Updated return date for <strong>Kit part B</strong> 2 days to 27 March 5:00 pm',NULL),(403,1,NULL,NULL,1029,'2020-03-02 14:31:35','Added 1 x item to <strong>Warehouse / In stock</strong>',NULL),(404,1,NULL,NULL,1030,'2020-03-02 14:32:02','Added 1 x item to <strong>Warehouse / In stock</strong>',NULL),(405,1,NULL,NULL,1031,'2020-03-02 14:32:35','Added 1 x item to <strong>Warehouse / In stock</strong>',NULL),(406,1,NULL,NULL,1032,'2020-03-02 14:36:14','Added item to <strong>In stock</strong>',NULL),(407,1,NULL,NULL,1033,'2020-03-02 14:36:14','Added item to <strong>In stock</strong>',NULL),(408,1,NULL,NULL,1033,'2020-03-02 14:36:23','Moved to <strong>South campus / Repair</strong>. ',NULL),(409,1,NULL,NULL,1030,'2020-03-02 14:37:16','Moved to <strong>Warehouse / Repair</strong>. ',NULL),(411,1,NULL,1084,NULL,'2020-03-02 14:51:35','Reservation created by Primary Admin',NULL),(412,1,NULL,1085,NULL,'2020-03-02 15:12:52','Loan created by Primary Admin',NULL),(413,41,41,NULL,NULL,'2020-03-02 16:46:14','Subscribed to Temporary membership.',NULL),(414,41,NULL,1086,NULL,'2020-03-02 16:46:54','Loan created by jamie parker',NULL),(415,1,40,1082,NULL,'2020-03-05 14:51:42','Updated return date for <strong>Kit part B</strong> -21 days to 06 March 5:00 pm',NULL),(416,1,NULL,1087,NULL,'2020-03-05 14:54:58','Reservation created by Primary Admin',NULL),(417,1,NULL,1088,NULL,'2020-03-06 09:36:39','Reservation created by Primary Admin',NULL),(418,1,NULL,1089,NULL,'2020-03-06 09:37:29','Reservation created by Primary Admin',NULL),(419,1,NULL,1090,NULL,'2020-03-06 11:19:25','Reservation created by Primary Admin',NULL),(421,1,NULL,1091,NULL,'2020-03-06 11:20:37','Reservation created by Primary Admin',NULL),(422,1,NULL,1092,NULL,'2020-03-06 11:21:08','Loan created by Primary Admin',NULL),(423,1,NULL,1092,1032,'2020-03-06 11:21:37','Loaned to <strong>Bill Smith</strong>',NULL),(424,1,18,1092,NULL,'2020-03-06 11:21:37','Checked out loan. ',NULL),(425,1,NULL,1093,NULL,'2020-03-11 09:21:21','Loan created by Primary Admin',NULL),(426,1,NULL,1093,1031,'2020-03-11 09:21:44','Loaned to <strong>jamie parker</strong>',NULL),(427,1,41,1093,NULL,'2020-03-11 09:21:44','Checked out loan. ',NULL),(428,1,NULL,1093,1031,'2020-03-11 09:22:42','Checked in to <strong>Warehouse / In stock</strong>.\nFix lens crack',NULL),(429,1,NULL,NULL,1034,'2020-03-11 14:16:01','Added item to <strong>In stock</strong>',NULL),(430,1,NULL,1092,1032,'2020-03-12 12:54:34','Checked in to <strong>Warehouse / In stock</strong>.',NULL),(431,1,NULL,1082,1006,'2020-03-12 12:58:46','Checked in to <strong>Warehouse / In stock</strong>.',NULL),(432,1,NULL,1079,1013,'2020-03-12 13:00:35','Checked in to <strong>Warehouse / In stock</strong>.',NULL),(433,1,NULL,1078,1007,'2020-03-12 13:04:57','Checked in to <strong>Warehouse / In stock</strong>.',NULL),(434,1,NULL,1075,1024,'2020-03-12 13:05:26','Checked in to <strong>Warehouse / In stock</strong>.',NULL),(435,1,NULL,1069,1023,'2020-03-12 13:05:47','Checked in to <strong>Warehouse / In stock</strong>.',NULL),(436,1,NULL,1063,1022,'2020-03-12 13:13:04','Checked in to <strong>Warehouse / In stock</strong>.',NULL),(437,1,4,NULL,NULL,'2020-03-12 13:18:43','Sent email \'test\'.',NULL),(438,1,4,NULL,NULL,'2020-03-12 13:19:49','Sent email \'my subjct\'.',NULL),(439,1,4,NULL,NULL,'2020-03-12 13:20:49','Sent email \'one\'.',NULL),(440,1,4,NULL,NULL,'2020-03-12 13:21:53','Sent email \'again\'.',NULL),(441,1,4,NULL,NULL,'2020-03-12 13:23:31','Sent email \'three\'.',NULL),(442,1,4,NULL,NULL,'2020-03-12 13:24:42','Sent email \'four\'.',NULL),(443,1,4,NULL,NULL,'2020-03-12 13:26:33','Sent email \'five\'.',NULL),(444,1,4,NULL,NULL,'2020-03-12 13:27:44','Sent email \'six\'.',NULL),(445,1,4,NULL,NULL,'2020-03-12 13:28:59','Sent email \'seven\'.',NULL),(446,1,4,NULL,NULL,'2020-03-12 13:31:31','Sent email \'eight\'.',NULL),(447,1,4,NULL,NULL,'2020-03-12 14:01:31','Sent email \'nine\'.',NULL),(448,1,4,NULL,NULL,'2020-03-12 14:02:04','Sent email \'nine\'.',NULL),(449,1,4,NULL,NULL,'2020-03-12 14:02:37','Sent email \'ten\'.',NULL),(450,1,4,NULL,NULL,'2020-03-12 14:04:56','Sent email \'11\'.',NULL),(451,1,4,NULL,NULL,'2020-03-12 14:05:39','Sent email \'12\'.',NULL),(452,1,4,NULL,NULL,'2020-03-12 14:07:38','Sent email \'final\'.',NULL),(453,1,NULL,1094,NULL,'2020-03-12 14:08:12','Loan created by Primary Admin',NULL),(454,1,NULL,1094,1031,'2020-03-12 14:08:24','Loaned to <strong>Chris Tanner</strong>',NULL),(455,1,4,1094,NULL,'2020-03-12 14:08:24','Checked out loan. ',NULL),(456,1,NULL,1094,1031,'2020-03-12 14:09:06','Checked in to <strong>Warehouse / In stock</strong>.',NULL),(457,1,52,NULL,NULL,'2020-03-12 14:09:31','Added by Primary Admin',NULL),(458,1,NULL,1095,NULL,'2020-03-12 14:18:02','Reservation created by Primary Admin',NULL),(459,1,NULL,1091,1032,'2020-03-12 14:18:15','Loaned to <strong>Bill Smith</strong>',NULL),(460,1,18,1091,NULL,'2020-03-12 14:18:15','Checked out loan. ',NULL),(461,1,40,1084,NULL,'2020-03-12 14:19:04','Updated return date for <strong>Uke</strong> 9 days to 13 March 5:00 pm',NULL),(462,1,NULL,1084,1023,'2020-03-12 14:19:19','Loaned to <strong>Hap Ness</strong>',NULL),(463,1,NULL,NULL,1017,'2020-03-12 14:19:19','Sold 1 from <strong>South campus / In stock</strong> to <strong>Hap Ness</strong> on loan <strong>1084</strong>',NULL),(464,1,40,1084,NULL,'2020-03-12 14:19:19','Checked out loan. ',NULL),(465,1,NULL,1096,NULL,'2020-03-13 09:46:20','Reservation created by Primary Admin',NULL),(466,1,NULL,1096,NULL,'2020-03-13 09:46:30','Cancelled by Primary Admin.',NULL),(467,1,NULL,1097,NULL,'2020-03-13 09:46:48','Reservation created by Primary Admin',NULL),(468,1,NULL,1097,NULL,'2020-03-13 09:46:56','Cancelled by Primary Admin.',NULL),(469,1,NULL,1098,NULL,'2020-03-13 09:56:24','Reservation created by Primary Admin',NULL),(470,1,NULL,1099,NULL,'2020-03-16 10:09:35','Reservation created by Primary Admin',NULL),(471,1,NULL,1100,NULL,'2020-03-16 10:10:05','Reservation created by Primary Admin',NULL),(472,1,NULL,1099,1031,'2020-03-16 10:11:28','Loaned to <strong>Chris Distributed</strong>',NULL),(473,1,6,1099,NULL,'2020-03-16 10:11:28','Checked out loan. ',NULL),(474,1,NULL,1101,NULL,'2020-03-16 10:12:42','Reservation created by Primary Admin',NULL),(475,1,NULL,1100,1029,'2020-03-17 10:32:46','Loaned to <strong>Chris Distributed</strong>',NULL),(476,1,6,1100,NULL,'2020-03-17 10:32:46','Checked out loan. ',NULL),(477,1,NULL,1100,1029,'2020-03-17 10:33:26','Checked in to <strong>Warehouse / Repair</strong>.',NULL),(478,1,NULL,NULL,1029,'2020-03-17 10:34:33','Moved to <strong>Warehouse / In stock</strong>. ',NULL),(480,1,6,1099,NULL,'2020-03-18 11:45:13','Added fee of £1; My fee',NULL),(481,1,6,1099,NULL,'2020-03-18 11:45:20','Deleted fee of £1.00',NULL),(484,1,NULL,1101,NULL,'2020-03-18 11:54:16','Cancelled by Primary Admin.',NULL),(485,1,NULL,1104,NULL,'2020-03-18 12:03:10','Reservation created by Primary Admin<br>Charged shipping fee of 0.00.',NULL),(486,1,6,1104,NULL,'2020-03-18 12:39:31','Deleted fee of £5.00',NULL),(487,1,6,1104,NULL,'2020-03-18 12:39:39','Added fee of £1; Shipping',NULL),(488,1,6,1104,NULL,'2020-03-18 12:39:43','Deleted fee of £1.00',NULL),(489,1,6,1104,NULL,'2020-03-18 12:40:49','Added fee of £1; testing',NULL),(490,1,6,1104,NULL,'2020-03-18 12:43:50','Deleted fee of £1.00',NULL),(491,1,NULL,1091,1032,'2020-03-18 13:43:13','Checked in to <strong>Warehouse / In stock</strong>.',NULL),(492,1,NULL,1095,1032,'2020-03-18 13:43:31','Loaned to <strong>Chris Tanner</strong>',NULL),(493,1,4,1095,NULL,'2020-03-18 13:43:31','Checked out loan. ',NULL),(494,1,NULL,1095,1032,'2020-03-18 13:46:55','Checked in to <strong>Warehouse / In stock</strong>.',NULL),(495,1,41,1086,NULL,'2020-03-18 14:11:38','Added fee of £6; Shipping',NULL),(496,1,41,1086,NULL,'2020-03-18 14:12:49','Added fee of £6; Shipping',NULL),(497,1,41,1086,NULL,'2020-03-18 14:12:52','Deleted fee of £6.00',NULL),(498,1,41,1086,NULL,'2020-03-18 14:25:34','Added fee of £6; Shipping',NULL),(499,1,41,1086,NULL,'2020-03-18 14:26:10','Added fee of £6; Shipping',NULL),(500,1,41,1086,NULL,'2020-03-18 14:49:23','Added fee of £1.5; Shipping',NULL),(501,1,41,1086,NULL,'2020-03-18 14:49:31','Deleted fee of £1.50',NULL),(502,1,41,1086,NULL,'2020-03-18 14:49:53','Added fee of £1.5; Shipping',NULL),(503,1,NULL,1088,1032,'2020-03-18 14:54:00','Loaned to <strong>Chris Tanner</strong>',NULL),(504,1,4,1088,NULL,'2020-03-18 14:54:00','Checked out loan. ',NULL),(505,1,NULL,1105,NULL,'2020-03-18 15:04:10','Reservation created by Primary Admin<br>Charged shipping fee of 0.00.',NULL),(506,1,41,1105,NULL,'2020-03-18 15:05:15','Deleted fee of £1.50',NULL),(507,1,NULL,1088,1032,'2020-03-18 15:06:25','Checked in to <strong>Warehouse / In stock</strong>.',NULL),(508,1,NULL,1089,NULL,'2020-03-18 15:06:33','Cancelled by Primary Admin.',NULL),(509,1,NULL,1105,NULL,'2020-03-18 15:06:48','Cancelled by Primary Admin.',NULL),(510,1,NULL,1084,1023,'2020-03-18 15:07:02','Checked in to <strong>Warehouse / In stock</strong>.',NULL),(511,1,NULL,1099,1031,'2020-03-18 15:07:22','Checked in to <strong>Warehouse / In stock</strong>.',NULL),(512,1,NULL,1098,NULL,'2020-03-18 15:07:33','Cancelled by Primary Admin.',NULL),(513,1,NULL,1104,NULL,'2020-03-18 15:07:45','Cancelled by Primary Admin.',NULL),(514,1,NULL,1106,NULL,'2020-03-18 15:08:34','Loan created by Primary Admin<br>Charged shipping fee of 1.50.',NULL),(515,1,NULL,1107,NULL,'2020-03-18 15:42:54','Reservation created by Primary Admin<br>Charged shipping fee of 3.00.',NULL),(516,1,6,1107,NULL,'2020-03-18 15:43:40','Deleted fee of £3.00',NULL),(517,1,NULL,1107,NULL,'2020-03-18 15:45:58','Cancelled by Primary Admin.',NULL),(519,1,NULL,1109,NULL,'2020-03-18 15:50:12','Reservation created by Primary Admin',NULL),(520,1,18,1109,NULL,'2020-03-18 15:52:07','Added fee of £3; Shipping',NULL),(521,1,18,1109,NULL,'2020-03-18 15:52:15','Deleted fee of £3.00',NULL),(522,1,NULL,1110,NULL,'2020-03-18 16:30:00','Reservation created by Primary Admin',NULL),(523,1,NULL,1110,NULL,'2020-03-18 16:30:38','Cancelled by Primary Admin.',NULL),(524,1,NULL,1111,NULL,'2020-03-18 16:30:58','Loan created by Primary Admin',NULL),(525,1,NULL,NULL,1036,'2020-03-18 21:15:19','Archived.',NULL),(526,1,NULL,NULL,1038,'2020-03-18 21:15:28','Archived.',NULL),(527,1,NULL,NULL,1041,'2020-03-18 21:35:31','Archived.',NULL),(531,53,NULL,1114,NULL,'2020-03-18 22:31:22','Loan created by Rose Tanner',NULL),(532,53,NULL,1115,NULL,'2020-03-18 23:03:03','Reservation created by Rose Tanner',NULL),(533,1,NULL,1116,NULL,'2020-03-18 23:13:56','Loan created by Primary Admin',NULL),(534,53,NULL,1115,NULL,'2020-03-18 23:25:39','Cancelled by Rose Tanner.',NULL),(535,53,NULL,1116,1029,'2020-03-18 23:28:16','Loaned to <strong>Rose Tanner</strong>',NULL),(536,53,NULL,1116,1044,'2020-03-18 23:28:16','Loaned to <strong>Rose Tanner</strong>',NULL),(537,53,53,1116,NULL,'2020-03-18 23:28:16','Checked out loan. ',NULL),(538,1,NULL,NULL,1045,'2020-03-19 18:49:14','Added 1 x item to <strong>Wednesday only / In stock</strong>',NULL),(539,1,NULL,1117,NULL,'2020-03-19 18:49:56','Reservation created by Primary Admin',NULL),(540,1,NULL,1117,NULL,'2020-03-19 18:58:38','Cancelled by Primary Admin.',NULL),(541,1,NULL,1118,NULL,'2020-03-19 18:59:06','Reservation created by Primary Admin',NULL),(542,1,NULL,1118,NULL,'2020-03-19 18:59:13','Cancelled by Primary Admin.',NULL),(543,1,NULL,1119,NULL,'2020-03-19 19:00:10','Reservation created by Primary Admin',NULL),(544,1,NULL,1119,NULL,'2020-03-19 19:01:51','Cancelled by Primary Admin.',NULL),(545,1,NULL,1120,NULL,'2020-03-19 19:02:50','Reservation created by Primary Admin',NULL),(546,1,NULL,1120,1045,'2020-03-19 19:08:21','Loaned to <strong>Chris Tanner</strong>',NULL),(547,1,NULL,1120,1044,'2020-03-19 19:08:21','Loaned to <strong>Chris Tanner</strong>',NULL),(548,1,4,1120,NULL,'2020-03-19 19:08:21','Checked out loan. ',NULL),(549,1,NULL,1120,1045,'2020-03-19 19:09:24','Checked in to <strong>Wednesday only / In stock</strong>.',NULL);
/*!40000 ALTER TABLE `note` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth2_access_tokens`
--

DROP TABLE IF EXISTS `oauth2_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oauth2_access_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` int(11) DEFAULT NULL,
  `scope` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_D247A21B5F37A13B` (`token`),
  KEY `IDX_D247A21B19EB6921` (`client_id`),
  KEY `IDX_D247A21BA76ED395` (`user_id`),
  CONSTRAINT `FK_D247A21B19EB6921` FOREIGN KEY (`client_id`) REFERENCES `oauth2_clients` (`id`),
  CONSTRAINT `FK_D247A21BA76ED395` FOREIGN KEY (`user_id`) REFERENCES `contact` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth2_access_tokens`
--

LOCK TABLES `oauth2_access_tokens` WRITE;
/*!40000 ALTER TABLE `oauth2_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth2_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth2_auth_codes`
--

DROP TABLE IF EXISTS `oauth2_auth_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oauth2_auth_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `redirect_uri` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` int(11) DEFAULT NULL,
  `scope` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_A018A10D5F37A13B` (`token`),
  KEY `IDX_A018A10D19EB6921` (`client_id`),
  KEY `IDX_A018A10DA76ED395` (`user_id`),
  CONSTRAINT `FK_A018A10D19EB6921` FOREIGN KEY (`client_id`) REFERENCES `oauth2_clients` (`id`),
  CONSTRAINT `FK_A018A10DA76ED395` FOREIGN KEY (`user_id`) REFERENCES `contact` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth2_auth_codes`
--

LOCK TABLES `oauth2_auth_codes` WRITE;
/*!40000 ALTER TABLE `oauth2_auth_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth2_auth_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth2_clients`
--

DROP TABLE IF EXISTS `oauth2_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oauth2_clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `random_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `redirect_uris` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:array)',
  `secret` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `allowed_grant_types` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth2_clients`
--

LOCK TABLES `oauth2_clients` WRITE;
/*!40000 ALTER TABLE `oauth2_clients` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth2_clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth2_refresh_tokens`
--

DROP TABLE IF EXISTS `oauth2_refresh_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oauth2_refresh_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` int(11) DEFAULT NULL,
  `scope` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_D394478C5F37A13B` (`token`),
  KEY `IDX_D394478C19EB6921` (`client_id`),
  KEY `IDX_D394478CA76ED395` (`user_id`),
  CONSTRAINT `FK_D394478C19EB6921` FOREIGN KEY (`client_id`) REFERENCES `oauth2_clients` (`id`),
  CONSTRAINT `FK_D394478CA76ED395` FOREIGN KEY (`user_id`) REFERENCES `contact` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth2_refresh_tokens`
--

LOCK TABLES `oauth2_refresh_tokens` WRITE;
/*!40000 ALTER TABLE `oauth2_refresh_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth2_refresh_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `page`
--

DROP TABLE IF EXISTS `page`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8_unicode_ci,
  `visibility` varchar(7) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `sort` int(11) NOT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_140AB620DE12AB56` (`created_by`),
  KEY `IDX_140AB62016FE72E1` (`updated_by`),
  CONSTRAINT `FK_140AB62016FE72E1` FOREIGN KEY (`updated_by`) REFERENCES `contact` (`id`),
  CONSTRAINT `FK_140AB620DE12AB56` FOREIGN KEY (`created_by`) REFERENCES `contact` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `page`
--

LOCK TABLES `page` WRITE;
/*!40000 ALTER TABLE `page` DISABLE KEYS */;
INSERT INTO `page` VALUES (1,NULL,NULL,'Home','Home',NULL,'','PUBLIC','2019-11-11 14:21:31','2019-11-11 14:21:31',-1,''),(3,1,1,'Privacy policy','Privacy policy',NULL,'<p>This is our privacy policy.</p>','PUBLIC','2019-11-13 14:05:31','2019-11-13 14:05:31',0,'privacy-policy'),(4,1,1,'A new page','My new page',NULL,'<p>Hello all.</p>','PUBLIC','2019-12-02 13:45:35','2019-12-02 13:45:35',0,'a-new_page');
/*!40000 ALTER TABLE `page` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment`
--

DROP TABLE IF EXISTS `payment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_by` int(11) DEFAULT NULL,
  `payment_method_id` int(11) DEFAULT NULL,
  `loan_id` int(11) DEFAULT NULL,
  `item_id` int(11) DEFAULT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `membership_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `type` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deposit_id` int(11) DEFAULT NULL,
  `psp_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_6D28840DDE12AB56` (`created_by`),
  KEY `IDX_6D28840D5AA1164F` (`payment_method_id`),
  KEY `IDX_6D28840DCE73868F` (`loan_id`),
  KEY `IDX_6D28840D126F525E` (`item_id`),
  KEY `IDX_6D28840DE7A1254A` (`contact_id`),
  KEY `IDX_6D28840D1FB354CD` (`membership_id`),
  KEY `IDX_6D28840D9815E4B1` (`deposit_id`),
  KEY `IDX_6D28840D71F7E88B` (`event_id`),
  CONSTRAINT `FK_6D28840D126F525E` FOREIGN KEY (`item_id`) REFERENCES `inventory_item` (`id`),
  CONSTRAINT `FK_6D28840D1FB354CD` FOREIGN KEY (`membership_id`) REFERENCES `membership` (`id`),
  CONSTRAINT `FK_6D28840D5AA1164F` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_method` (`id`),
  CONSTRAINT `FK_6D28840D71F7E88B` FOREIGN KEY (`event_id`) REFERENCES `event` (`id`),
  CONSTRAINT `FK_6D28840D9815E4B1` FOREIGN KEY (`deposit_id`) REFERENCES `deposit` (`id`),
  CONSTRAINT `FK_6D28840DCE73868F` FOREIGN KEY (`loan_id`) REFERENCES `loan` (`id`),
  CONSTRAINT `FK_6D28840DDE12AB56` FOREIGN KEY (`created_by`) REFERENCES `contact` (`id`),
  CONSTRAINT `FK_6D28840DE7A1254A` FOREIGN KEY (`contact_id`) REFERENCES `contact` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=187 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment`
--

LOCK TABLES `payment` WRITE;
/*!40000 ALTER TABLE `payment` DISABLE KEYS */;
INSERT INTO `payment` VALUES (1,4,NULL,NULL,NULL,4,2,'2019-11-11 15:03:10','FEE','2019-11-11',10.00,'Membership fee (self serve).',NULL,NULL,NULL),(2,4,4,NULL,NULL,4,2,'2019-11-11 15:03:10','PAYMENT','2019-11-11',10.00,'Payment received. ',NULL,'ch_1FdeQ4DuX5OG9FwDXaYI9YjF',NULL),(3,4,4,1000,NULL,4,NULL,'2019-11-11 15:05:42','PAYMENT','2019-11-11',10.00,'Payment received. ',NULL,'ch_1FdeSWDuX5OG9FwDVRWkylB4',NULL),(4,4,4,NULL,NULL,4,NULL,'2019-11-11 15:05:42','DEPOSIT','2019-11-11',20.00,'Deposit received for \"Petzl Fall Arrest Kit\".',1,'ch_1FdeSWDuX5OG9FwDVRWkylB4',NULL),(5,4,NULL,1000,1004,4,NULL,'2019-11-11 15:05:42','FEE','2019-11-11',10.00,NULL,NULL,NULL,NULL),(6,4,NULL,1000,1004,4,NULL,'2019-11-11 15:10:08','FEE','2019-11-11',1.43,'Updated return date for Petzl Fall Arrest Kit 0 days to 19 November 5:00 pm.',NULL,NULL,NULL),(7,4,4,NULL,NULL,4,NULL,'2019-11-11 15:10:08','PAYMENT','2019-11-11',1.43,'Payment received',NULL,'ch_1FdeWoDuX5OG9FwD8K1iQQPB',NULL),(8,1,1,NULL,NULL,2,NULL,'2019-11-14 09:21:14','PAYMENT','2019-11-14',20.00,'Credit added.',NULL,NULL,NULL),(9,1,NULL,1002,1011,2,NULL,'2019-11-14 10:45:51','FEE','2019-11-14',1.00,NULL,NULL,NULL,NULL),(10,6,NULL,NULL,NULL,6,3,'2019-11-14 11:00:43','FEE','2019-11-14',10.00,'Membership fee (self serve).',NULL,NULL,NULL),(11,6,4,NULL,NULL,6,3,'2019-11-14 11:00:43','PAYMENT','2019-11-14',10.00,'Payment received. ',NULL,'ch_1Feg45DuX5OG9FwDrOJomto9',NULL),(13,1,2,1003,NULL,6,NULL,'2019-11-14 11:09:17','PAYMENT','2019-11-14',0.50,'Payment received. ',NULL,NULL,NULL),(14,1,NULL,1003,1009,6,NULL,'2019-11-14 11:09:17','FEE','2019-11-14',0.50,NULL,NULL,NULL,NULL),(15,2,NULL,1002,1011,2,NULL,'2019-11-14 11:12:57','FEE','2019-11-14',0.50,'Updated return date for DEWALT 18V XR BRUSHLESS 1/4\" ROUTER 7 days to 10 December 5:00 pm.',NULL,NULL,NULL),(16,2,4,NULL,NULL,2,NULL,'2019-11-14 11:12:57','PAYMENT','2019-11-14',0.50,'Payment received',NULL,'ch_1FegFvDuX5OG9FwDvyQLvSu5',NULL),(17,1,NULL,1004,1013,2,NULL,'2019-11-14 11:32:40','FEE','2019-11-14',1.00,NULL,NULL,NULL,NULL),(18,1,NULL,1005,1013,2,NULL,'2019-11-14 11:35:08','FEE','2019-11-14',1.00,NULL,NULL,NULL,NULL),(19,1,4,1006,NULL,6,NULL,'2019-11-14 11:40:21','PAYMENT','2019-11-14',1.00,'Payment received. ',NULL,'ch_1FeggSDuX5OG9FwDkkuHYJDu',NULL),(20,1,NULL,1006,1014,6,NULL,'2019-11-14 11:40:21','FEE','2019-11-14',1.00,NULL,NULL,NULL,NULL),(21,1,1,1007,NULL,6,NULL,'2019-11-14 11:48:07','PAYMENT','2019-11-14',1.00,'Payment received. ',NULL,NULL,NULL),(22,1,NULL,1007,1013,6,NULL,'2019-11-14 11:48:07','FEE','2019-11-14',1.00,NULL,NULL,NULL,NULL),(23,1,1,1008,NULL,6,NULL,'2019-11-14 12:09:44','PAYMENT','2019-11-14',2.00,'Payment received. ',NULL,NULL,NULL),(24,1,NULL,1008,1013,6,NULL,'2019-11-14 12:09:45','FEE','2019-11-14',2.00,NULL,NULL,NULL,NULL),(25,1,1,1009,NULL,6,NULL,'2019-11-14 12:21:03','PAYMENT','2019-11-14',2.71,'Payment received. ',NULL,NULL,NULL),(26,1,NULL,1009,1014,6,NULL,'2019-11-14 12:21:03','FEE','2019-11-14',1.00,NULL,NULL,NULL,NULL),(27,1,NULL,1009,1012,6,NULL,'2019-11-14 12:21:03','FEE','2019-11-14',1.71,NULL,NULL,NULL,NULL),(28,1,3,1018,NULL,6,NULL,'2019-11-20 11:49:47','PAYMENT','2019-11-20',1.00,'Payment received. ',NULL,NULL,NULL),(29,1,NULL,1018,1016,6,NULL,'2019-11-20 11:49:47','FEE','2019-11-20',1.00,NULL,NULL,NULL,NULL),(30,1,3,1017,NULL,6,NULL,'2019-11-20 11:50:00','PAYMENT','2019-11-20',1.00,'Payment received. ',NULL,NULL,NULL),(31,1,NULL,1017,1016,6,NULL,'2019-11-20 11:50:00','FEE','2019-11-20',1.00,NULL,NULL,NULL,NULL),(32,1,3,1019,NULL,6,NULL,'2019-11-20 11:50:29','PAYMENT','2019-11-20',1.00,'Payment received. ',NULL,NULL,NULL),(33,1,NULL,1019,1016,6,NULL,'2019-11-20 11:50:29','FEE','2019-11-20',1.00,NULL,NULL,NULL,NULL),(34,1,3,1020,NULL,6,NULL,'2019-11-20 11:54:14','PAYMENT','2019-11-20',5.00,'Payment received. ',NULL,NULL,NULL),(35,1,NULL,1020,1008,6,NULL,'2019-11-20 11:54:14','FEE','2019-11-20',5.00,NULL,NULL,NULL,NULL),(36,1,NULL,1019,1016,6,NULL,'2019-11-26 11:20:55','FEE','2019-11-26',0.75,'Updated return date for DEWALT 18V XR BRUSHLESS 1/4\" ROUTER 1 day to 29 November 5:00 pm.',NULL,NULL,NULL),(37,1,NULL,1023,1012,2,NULL,'2019-11-26 16:47:08','FEE','2019-11-26',0.29,NULL,NULL,NULL,NULL),(38,1,NULL,1027,1013,6,NULL,'2019-11-27 09:23:16','FEE','2019-11-27',0.29,NULL,NULL,NULL,NULL),(39,1,NULL,1030,1010,4,NULL,'2019-11-27 09:42:22','FEE','2019-11-27',0.29,NULL,NULL,NULL,NULL),(40,1,NULL,1030,1011,4,NULL,'2019-11-27 09:42:22','FEE','2019-11-27',1.29,NULL,NULL,NULL,NULL),(41,1,3,1033,NULL,4,NULL,'2019-11-27 09:52:03','PAYMENT','2019-11-27',2.58,'Payment received. ',NULL,NULL,NULL),(42,1,NULL,1033,1016,4,NULL,'2019-11-27 09:52:03','FEE','2019-11-27',1.00,NULL,NULL,NULL,NULL),(43,1,1,1032,NULL,4,NULL,'2019-11-27 09:52:42','PAYMENT','2019-11-27',1.00,'Payment received. ',NULL,NULL,NULL),(44,1,1,NULL,NULL,4,NULL,'2019-11-27 09:52:42','DEPOSIT','2019-11-27',5.00,'Deposit received for \"DEWALT 18V XR BRUSHLESS CORDLESS COMBI DRILL\".',2,NULL,NULL),(45,1,NULL,1032,1010,4,NULL,'2019-11-27 09:52:42','FEE','2019-11-27',1.00,NULL,NULL,NULL,NULL),(46,1,1,NULL,NULL,4,NULL,'2019-11-27 09:53:16','REFUND','2019-11-27',5.00,'Deposit refunded. ',2,NULL,NULL),(47,1,1,1034,NULL,8,NULL,'2019-11-27 09:54:48','PAYMENT','2019-11-27',1.00,'Payment received. ',NULL,NULL,NULL),(48,1,1,NULL,NULL,8,NULL,'2019-11-27 09:54:48','DEPOSIT','2019-11-27',5.00,'Deposit received for \"DEWALT 18V XR BRUSHLESS CORDLESS COMBI DRILL\".',3,NULL,NULL),(49,1,NULL,1034,1010,8,NULL,'2019-11-27 09:54:48','FEE','2019-11-27',1.00,NULL,NULL,NULL,NULL),(50,1,1,NULL,NULL,8,NULL,'2019-11-27 09:54:57','REFUND','2019-11-27',5.00,'Deposit refunded. ',3,NULL,NULL),(51,1,1,1036,NULL,4,NULL,'2019-11-29 11:11:21','PAYMENT','2019-11-29',1.00,'Payment received. ',NULL,NULL,NULL),(52,1,NULL,1036,1016,4,NULL,'2019-11-29 11:11:21','FEE','2019-11-29',1.00,NULL,NULL,NULL,NULL),(53,1,4,1040,NULL,4,NULL,'2019-12-02 13:15:15','PAYMENT','2019-12-02',0.57,'Payment received. ',NULL,'ch_1FlEk6DuX5OG9FwD6U87nAGK',NULL),(54,1,4,NULL,NULL,4,NULL,'2019-12-02 13:15:16','DEPOSIT','2019-12-02',5.00,'Deposit received for \"DEWALT 18V XR BRUSHLESS CORDLESS COMBI DRILL\".',4,'ch_1FlEk6DuX5OG9FwD6U87nAGK',NULL),(55,1,NULL,1040,1010,4,NULL,'2019-12-02 13:15:16','FEE','2019-12-02',0.57,NULL,NULL,NULL,NULL),(56,1,4,NULL,NULL,4,NULL,'2019-12-02 13:24:12','REFUND','2019-12-02',5.00,'Deposit refunded. ',4,'re_1FlEsqDuX5OG9FwDlgRkIKVc',NULL),(57,1,4,1041,NULL,4,NULL,'2019-12-02 13:25:05','PAYMENT','2019-12-02',0.71,'Payment received. ',NULL,'ch_1FlEtVDuX5OG9FwDXRf4O0Ob',NULL),(58,1,NULL,1041,1006,4,NULL,'2019-12-02 13:25:05','FEE','2019-12-02',0.71,NULL,NULL,NULL,NULL),(59,1,4,1042,NULL,4,NULL,'2019-12-02 13:28:31','PAYMENT','2019-12-02',1.00,'Payment received. ',NULL,'ch_1FlEwyDuX5OG9FwDUAFn6BJ1',NULL),(60,1,NULL,1042,1006,4,NULL,'2019-12-02 13:28:31','FEE','2019-12-02',1.00,NULL,NULL,NULL,NULL),(61,1,NULL,1042,NULL,4,NULL,'2019-12-02 13:30:07','FEE','2019-12-02',1.00,'test',NULL,NULL,NULL),(63,1,3,1043,NULL,4,NULL,'2019-12-02 13:30:52','PAYMENT','2019-12-02',2.57,'Payment received. ',NULL,NULL,NULL),(64,1,NULL,1043,1006,4,NULL,'2019-12-02 13:30:52','FEE','2019-12-02',1.57,NULL,NULL,NULL,NULL),(65,1,3,1044,NULL,4,NULL,'2019-12-16 11:22:14','PAYMENT','2019-12-16',1.43,'Payment received. ',NULL,NULL,NULL),(66,1,NULL,1044,1011,4,NULL,'2019-12-16 11:22:14','FEE','2019-12-16',1.43,NULL,NULL,NULL,NULL),(67,1,3,1045,NULL,4,NULL,'2019-12-16 11:24:37','PAYMENT','2019-12-16',0.29,'Payment received. ',NULL,NULL,NULL),(68,1,NULL,1045,1011,4,NULL,'2019-12-16 11:24:37','FEE','2019-12-16',0.29,NULL,NULL,NULL,NULL),(69,1,3,1046,NULL,4,NULL,'2019-12-16 11:25:44','PAYMENT','2019-12-16',0.43,'Payment received. ',NULL,NULL,NULL),(70,1,NULL,1046,1011,4,NULL,'2019-12-16 11:25:44','FEE','2019-12-16',0.43,NULL,NULL,NULL,NULL),(71,1,3,1047,NULL,4,NULL,'2019-12-16 11:26:32','PAYMENT','2019-12-16',0.43,'Payment received. ',NULL,NULL,NULL),(72,1,NULL,1047,1011,4,NULL,'2019-12-16 11:26:32','FEE','2019-12-16',0.43,NULL,NULL,NULL,NULL),(73,1,3,1048,NULL,4,NULL,'2019-12-16 11:28:39','PAYMENT','2019-12-16',0.43,'Payment received. ',NULL,NULL,NULL),(74,1,NULL,1048,1011,4,NULL,'2019-12-16 11:28:39','FEE','2019-12-16',0.43,NULL,NULL,NULL,NULL),(75,1,3,1049,NULL,4,NULL,'2019-12-16 11:29:24','PAYMENT','2019-12-16',0.57,'Payment received. ',NULL,NULL,NULL),(76,1,NULL,1049,1011,4,NULL,'2019-12-16 11:29:24','FEE','2019-12-16',0.57,NULL,NULL,NULL,NULL),(77,1,1,1050,NULL,4,NULL,'2020-01-29 13:38:27','PAYMENT','2020-01-29',4.00,'Payment received. ',NULL,NULL,NULL),(78,1,NULL,1050,1017,4,NULL,'2020-01-29 13:38:27','FEE','2020-01-29',4.00,NULL,NULL,NULL,NULL),(79,1,1,1051,NULL,4,NULL,'2020-01-29 15:49:38','PAYMENT','2020-01-29',5.00,'Payment received. ',NULL,NULL,NULL),(80,1,NULL,1051,1017,4,NULL,'2020-01-29 15:49:38','FEE','2020-01-29',5.00,NULL,NULL,NULL,NULL),(81,1,1,1052,NULL,4,NULL,'2020-01-29 16:20:42','PAYMENT','2020-01-29',12.00,'Payment received. ',NULL,NULL,NULL),(82,1,NULL,1052,1017,4,NULL,'2020-01-29 16:20:42','FEE','2020-01-29',2.00,NULL,NULL,NULL,NULL),(83,1,NULL,1052,1017,4,NULL,'2020-01-29 16:20:42','FEE','2020-01-29',5.00,NULL,NULL,NULL,NULL),(84,1,1,1053,NULL,4,NULL,'2020-01-29 16:29:50','PAYMENT','2020-01-29',1.00,'Payment received. ',NULL,NULL,NULL),(85,1,NULL,1053,1017,4,NULL,'2020-01-29 16:29:50','FEE','2020-01-29',6.00,NULL,NULL,NULL,NULL),(86,1,1,1054,NULL,4,NULL,'2020-01-29 16:34:46','PAYMENT','2020-01-29',5.00,'Payment received. ',NULL,NULL,NULL),(87,1,NULL,1054,1017,4,NULL,'2020-01-29 16:34:46','FEE','2020-01-29',5.00,NULL,NULL,NULL,NULL),(88,1,3,1055,NULL,4,NULL,'2020-01-29 16:36:28','PAYMENT','2020-01-29',3.00,'Payment received. ',NULL,NULL,NULL),(89,1,NULL,1055,1017,4,NULL,'2020-01-29 16:36:28','FEE','2020-01-29',3.00,NULL,NULL,NULL,NULL),(90,1,3,1056,NULL,4,NULL,'2020-01-29 16:42:33','PAYMENT','2020-01-29',6.00,'Payment received. ',NULL,NULL,NULL),(91,1,NULL,1056,1017,4,NULL,'2020-01-29 16:42:33','FEE','2020-01-29',5.00,NULL,NULL,NULL,NULL),(92,1,NULL,1056,1015,4,NULL,'2020-01-29 16:42:33','FEE','2020-01-29',1.00,NULL,NULL,NULL,NULL),(93,1,3,1058,NULL,4,NULL,'2020-01-30 10:10:26','PAYMENT','2020-01-30',9.00,'Payment received. ',NULL,NULL,NULL),(94,1,NULL,1058,1011,4,NULL,'2020-01-30 10:10:26','FEE','2020-01-30',1.00,NULL,NULL,NULL,NULL),(95,1,NULL,1058,1017,4,NULL,'2020-01-30 10:10:26','FEE','2020-01-30',8.00,NULL,NULL,NULL,NULL),(96,1,NULL,1058,NULL,4,NULL,'2020-01-30 13:00:15','FEE','2020-01-30',25.00,'Sold router',NULL,NULL,NULL),(97,1,NULL,1060,NULL,4,NULL,'2020-01-30 13:16:21','FEE','2020-01-30',10.00,'Sold item',NULL,NULL,NULL),(98,1,3,1061,NULL,4,NULL,'2020-01-30 13:25:02','PAYMENT','2020-01-30',35.14,'Payment received. ',NULL,NULL,NULL),(99,1,NULL,1061,1020,4,NULL,'2020-01-30 13:25:02','FEE','2020-01-30',0.14,NULL,NULL,NULL,NULL),(100,1,3,1063,NULL,6,NULL,'2020-01-30 14:13:46','PAYMENT','2020-01-30',1.18,'Payment received. ',NULL,NULL,NULL),(101,1,NULL,1063,1022,6,NULL,'2020-01-30 14:13:46','FEE','2020-01-30',0.14,NULL,NULL,NULL,NULL),(102,1,1,1064,NULL,6,NULL,'2020-01-30 15:13:18','PAYMENT','2020-01-30',0.14,'Payment received. ',NULL,NULL,NULL),(103,1,NULL,1064,1009,6,NULL,'2020-01-30 15:13:18','FEE','2020-01-30',0.14,NULL,NULL,NULL,NULL),(104,1,3,1065,NULL,6,NULL,'2020-01-30 15:21:59','PAYMENT','2020-01-30',0.14,'Payment received. ',NULL,NULL,NULL),(105,1,NULL,1065,1023,6,NULL,'2020-01-30 15:21:59','FEE','2020-01-30',0.14,NULL,NULL,NULL,NULL),(106,1,NULL,1066,1024,6,NULL,'2020-01-30 15:26:25','FEE','2020-01-30',2.00,'Check-in fee for Scope.',NULL,NULL,NULL),(107,1,NULL,1069,1023,6,NULL,'2020-01-31 10:20:50','FEE','2020-01-31',1.00,NULL,NULL,NULL,NULL),(108,4,4,1071,NULL,4,NULL,'2020-02-03 19:27:40','PAYMENT','2020-02-03',11.99,'Payment received. ',NULL,'ch_1G8Aa6DuX5OG9FwDw3zmPQQH',NULL),(109,4,NULL,1071,1018,4,NULL,'2020-02-03 19:27:40','FEE','2020-02-03',11.99,NULL,NULL,NULL,NULL),(110,1,1,NULL,NULL,7,NULL,'2020-02-10 10:07:05','PAYMENT','2020-02-10',10.00,'Credit added.',NULL,NULL,NULL),(111,1,3,NULL,NULL,4,NULL,'2020-02-13 09:35:39','PAYMENT','2020-02-13',1.00,'Credit added.',NULL,NULL,NULL),(113,1,3,1075,NULL,7,NULL,'2020-02-25 13:15:34','PAYMENT','2020-02-25',5.00,'Payment received. ',NULL,NULL,NULL),(114,1,NULL,1075,1024,7,NULL,'2020-02-25 13:15:34','FEE','2020-02-25',15.00,NULL,NULL,NULL,NULL),(115,1,3,1077,NULL,4,NULL,'2020-02-25 14:21:47','PAYMENT','2020-02-25',6.50,'Payment received. ',NULL,NULL,NULL),(116,1,NULL,1077,1013,4,NULL,'2020-02-25 14:21:47','FEE','2020-02-25',7.50,NULL,NULL,NULL,NULL),(117,1,NULL,NULL,NULL,7,NULL,'2020-02-25 15:54:39','FEE','2020-02-25',10.00,NULL,NULL,NULL,7),(118,1,3,NULL,NULL,4,NULL,'2020-02-26 10:05:43','DEPOSIT','2020-02-26',20.00,'Deposit received for \"Mower\".',5,NULL,NULL),(119,1,NULL,1078,1018,2,NULL,'2020-02-26 10:47:10','FEE','2020-02-26',4.99,NULL,NULL,NULL,NULL),(120,1,NULL,1078,1007,2,NULL,'2020-02-26 10:47:10','FEE','2020-02-26',5.00,NULL,NULL,NULL,NULL),(121,1,NULL,1004,NULL,2,NULL,'2020-03-02 11:40:01','FEE','2020-03-02',1.00,'Item was damaged',NULL,NULL,NULL),(122,1,3,1081,NULL,2,NULL,'2020-03-02 11:50:37','PAYMENT','2020-03-02',1.78,'Payment received. ',NULL,NULL,NULL),(123,1,NULL,1081,1009,2,NULL,'2020-03-02 11:50:37','FEE','2020-03-02',7.50,NULL,NULL,NULL,NULL),(124,40,NULL,NULL,NULL,40,NULL,'2020-03-02 13:27:02','FEE','2020-03-02',10.00,NULL,NULL,NULL,7),(125,40,4,NULL,NULL,40,NULL,'2020-03-02 13:27:02','PAYMENT','2020-03-02',10.00,NULL,NULL,'ch_1GIEISDuX5OG9FwDdEufBKfe',7),(126,40,4,1082,NULL,40,NULL,'2020-03-02 13:38:13','PAYMENT','2020-03-02',7.50,'Payment received. ',NULL,'ch_1GIETFDuX5OG9FwDM1AAygwD',NULL),(127,40,NULL,1082,1006,40,NULL,'2020-03-02 13:38:13','FEE','2020-03-02',7.50,NULL,NULL,NULL,NULL),(128,1,NULL,1084,1030,40,NULL,'2020-03-02 14:51:35','FEE','2020-03-02',7.50,NULL,NULL,NULL,NULL),(129,41,4,NULL,NULL,41,NULL,'2020-03-02 16:45:53','PAYMENT','2020-03-02',10.00,'Thanks!',NULL,'ch_1GIHOoDuX5OG9FwDZCDzQbU0',NULL),(130,41,4,NULL,NULL,41,NULL,'2020-03-02 16:46:44','PAYMENT','2020-03-02',50.00,'Credit added.',NULL,'ch_1GIHPfDuX5OG9FwDS6FR0FLt',NULL),(131,1,NULL,1091,1032,18,NULL,'2020-03-06 11:20:37','FEE','2020-03-06',7.50,NULL,NULL,NULL,NULL),(132,1,3,1092,NULL,18,NULL,'2020-03-06 11:21:37','PAYMENT','2020-03-06',7.50,'Payment received. ',NULL,NULL,NULL),(133,1,4,NULL,NULL,41,NULL,'2020-03-11 09:21:44','DEPOSIT','2020-03-11',10.00,'Deposit received for \"Canon lens kit\".',6,'ch_1GLQl1DuX5OG9FwDiSdoxIeU',NULL),(134,1,NULL,1093,1031,41,NULL,'2020-03-11 09:21:44','FEE','2020-03-11',15.00,NULL,NULL,NULL,NULL),(135,1,4,NULL,NULL,41,NULL,'2020-03-11 09:22:09','REFUND','2020-03-11',10.00,'Deposit refunded. ',6,'re_1GLQlQDuX5OG9FwDIgzYIPXw',NULL),(136,1,3,NULL,NULL,4,NULL,'2020-03-12 13:00:28','REFUND','2020-03-12',20.00,'Deposit refunded. ',5,NULL,NULL),(137,1,3,1084,NULL,40,NULL,'2020-03-12 14:19:19','PAYMENT','2020-03-12',7.50,'Payment received. ',NULL,NULL,NULL),(138,1,NULL,1096,1031,4,NULL,'2020-03-13 09:46:20','FEE','2020-03-13',7.50,NULL,NULL,NULL,NULL),(139,1,NULL,1097,1031,4,NULL,'2020-03-13 09:46:48','FEE','2020-03-13',15.00,NULL,NULL,NULL,NULL),(140,1,3,NULL,NULL,1,NULL,'2020-03-13 09:53:24','PAYMENT','2020-03-13',15.00,'Credit added.',NULL,NULL,NULL),(141,1,1,NULL,NULL,4,NULL,'2020-03-13 09:55:06','PAYMENT','2020-03-13',15.00,'Credit added.',NULL,NULL,NULL),(142,1,3,NULL,NULL,4,NULL,'2020-03-13 09:55:26','PAYMENT','2020-03-13',7.50,'Credit added.',NULL,NULL,NULL),(143,1,1,NULL,NULL,4,NULL,'2020-03-13 09:56:16','PAYMENT','2020-03-13',15.00,'Credit added.',NULL,NULL,NULL),(144,1,NULL,1098,1031,4,NULL,'2020-03-13 09:56:24','FEE','2020-03-13',15.00,NULL,NULL,NULL,NULL),(145,1,3,NULL,NULL,4,NULL,'2020-03-15 14:08:48','PAYMENT','2020-03-15',2.00,'Credit added.',NULL,NULL,NULL),(146,1,3,NULL,NULL,4,NULL,'2020-03-15 14:09:06','PAYMENT','2020-03-15',1.50,'Credit added.',NULL,NULL,NULL),(147,4,4,NULL,NULL,4,NULL,'2020-03-15 14:09:52','PAYMENT','2020-03-15',1.00,'Credit added.',NULL,'ch_1GMxA2DuX5OG9FwDVGkQcW6Y',NULL),(148,1,NULL,1099,1031,6,NULL,'2020-03-16 10:09:35','FEE','2020-03-16',7.50,NULL,NULL,NULL,NULL),(149,1,NULL,1100,1029,6,NULL,'2020-03-16 10:10:05','FEE','2020-03-16',7.50,NULL,NULL,NULL,NULL),(150,1,3,1099,NULL,6,NULL,'2020-03-16 10:11:28','PAYMENT','2020-03-16',18.00,'Payment received. ',NULL,NULL,NULL),(151,1,3,NULL,NULL,6,NULL,'2020-03-16 10:11:28','DEPOSIT','2020-03-16',10.00,'Deposit received for \"Canon lens kit\".',7,NULL,NULL),(152,1,NULL,1101,1024,6,NULL,'2020-03-16 10:12:42','FEE','2020-03-16',7.50,NULL,NULL,NULL,NULL),(153,1,3,1100,NULL,6,NULL,'2020-03-17 10:32:46','PAYMENT','2020-03-17',7.50,'Payment received. ',NULL,NULL,NULL),(156,1,NULL,1104,1031,6,NULL,'2020-03-18 12:03:10','FEE','2020-03-18',7.50,NULL,NULL,NULL,NULL),(157,1,NULL,1104,1024,6,NULL,'2020-03-18 12:03:10','FEE','2020-03-18',7.50,NULL,NULL,NULL,NULL),(159,1,1,NULL,NULL,29,NULL,'2020-03-18 12:10:19','PAYMENT','2020-03-18',10.00,'Credit added.',NULL,NULL,NULL),(160,1,1,NULL,NULL,29,NULL,'2020-03-18 12:13:44','PAYMENT','2020-03-18',4.00,'Credit added.',NULL,NULL,NULL),(161,1,1,NULL,NULL,29,NULL,'2020-03-18 12:13:55','PAYMENT','2020-03-18',2.50,'Credit added.',NULL,NULL,NULL),(164,1,3,1095,NULL,4,NULL,'2020-03-18 13:43:31','PAYMENT','2020-03-18',5.00,'Payment received. ',NULL,NULL,NULL),(165,1,NULL,1095,1032,4,NULL,'2020-03-18 13:43:31','FEE','2020-03-18',5.00,NULL,NULL,NULL,NULL),(172,1,NULL,1105,1029,41,NULL,'2020-03-18 15:04:10','FEE','2020-03-18',3.50,NULL,NULL,NULL,NULL),(174,1,3,NULL,NULL,6,NULL,'2020-03-18 15:07:20','REFUND','2020-03-18',10.00,'Deposit refunded. ',7,NULL,NULL),(175,1,NULL,1106,NULL,6,NULL,'2020-03-18 15:08:34','FEE','2020-03-18',1.50,'Shipping',NULL,NULL,NULL),(177,1,3,NULL,NULL,18,NULL,'2020-03-18 15:46:52','PAYMENT','2020-03-18',100.00,'Credit added.',NULL,NULL,NULL),(179,53,4,1116,NULL,53,NULL,'2020-03-18 23:28:16','PAYMENT','2020-03-18',7.50,'Payment received. ',NULL,'ch_1GOBJ4DuX5OG9FwDphYn3GKh',NULL),(180,53,NULL,1116,1029,53,NULL,'2020-03-18 23:28:16','FEE','2020-03-18',1.00,NULL,NULL,NULL,NULL),(181,53,NULL,1116,1044,53,NULL,'2020-03-18 23:28:16','FEE','2020-03-18',6.50,NULL,NULL,NULL,NULL),(182,1,3,1120,NULL,4,NULL,'2020-03-19 19:08:21','PAYMENT','2020-03-19',3.00,'Payment received. ',NULL,NULL,NULL),(183,1,3,NULL,NULL,4,NULL,'2020-03-19 19:08:21','DEPOSIT','2020-03-19',5.00,'Deposit received for \"Deposit item\".',8,NULL,NULL),(184,1,NULL,1120,1045,4,NULL,'2020-03-19 19:08:21','FEE','2020-03-19',1.00,NULL,NULL,NULL,NULL),(185,1,NULL,1120,1044,4,NULL,'2020-03-19 19:08:21','FEE','2020-03-19',6.50,NULL,NULL,NULL,NULL),(186,1,3,NULL,NULL,4,NULL,'2020-03-19 19:09:08','REFUND','2020-03-19',5.00,'Deposit refunded. ',8,NULL,NULL);
/*!40000 ALTER TABLE `payment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_method`
--

DROP TABLE IF EXISTS `payment_method`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_method` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_method`
--

LOCK TABLES `payment_method` WRITE;
/*!40000 ALTER TABLE `payment_method` DISABLE KEYS */;
INSERT INTO `payment_method` VALUES (1,'Cash',1),(2,'Credit/debit card',1),(3,'Bank transfer',1),(4,'Stripe',1);
/*!40000 ALTER TABLE `payment_method` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_field`
--

DROP TABLE IF EXISTS `product_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_field` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `required` tinyint(1) NOT NULL,
  `show_on_item_list` tinyint(1) NOT NULL DEFAULT '0',
  `show_on_website` tinyint(1) NOT NULL DEFAULT '1',
  `sort` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_field`
--

LOCK TABLES `product_field` WRITE;
/*!40000 ALTER TABLE `product_field` DISABLE KEYS */;
INSERT INTO `product_field` VALUES (1,'Donor program','choice',0,1,1,0),(2,'Example textarea field','textarea',0,0,0,0),(3,'Example multi select field','multiselect',0,0,1,0),(4,'Example check box field','checkbox',0,0,1,0);
/*!40000 ALTER TABLE `product_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_field_select_option`
--

DROP TABLE IF EXISTS `product_field_select_option`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_field_select_option` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_field_id` int(11) DEFAULT NULL,
  `option_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_63C603968F876D27` (`product_field_id`),
  CONSTRAINT `FK_63C603968F876D27` FOREIGN KEY (`product_field_id`) REFERENCES `product_field` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_field_select_option`
--

LOCK TABLES `product_field_select_option` WRITE;
/*!40000 ALTER TABLE `product_field_select_option` DISABLE KEYS */;
INSERT INTO `product_field_select_option` VALUES (1,1,'Local council',0),(2,1,'Renny funded',0),(3,3,'Option 1',0),(4,3,'Option 2',0),(5,3,'Option 3',0);
/*!40000 ALTER TABLE `product_field_select_option` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_field_value`
--

DROP TABLE IF EXISTS `product_field_value`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_field_value` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_field_id` int(11) DEFAULT NULL,
  `inventory_item_id` int(11) DEFAULT NULL,
  `field_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_9AFF50D08F876D27` (`product_field_id`),
  KEY `IDX_9AFF50D0536BF4A2` (`inventory_item_id`),
  CONSTRAINT `FK_9AFF50D0536BF4A2` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_item` (`id`),
  CONSTRAINT `FK_9AFF50D08F876D27` FOREIGN KEY (`product_field_id`) REFERENCES `product_field` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_field_value`
--

LOCK TABLES `product_field_value` WRITE;
/*!40000 ALTER TABLE `product_field_value` DISABLE KEYS */;
INSERT INTO `product_field_value` VALUES (1,1,1014,'1'),(2,1,1015,'1'),(3,1,1015,'1'),(4,1,1013,NULL),(5,1,1006,NULL),(6,1,1005,NULL),(7,1,1017,NULL),(8,1,1018,NULL),(9,1,1019,NULL),(10,1,1020,NULL),(11,1,1021,NULL),(12,1,1022,NULL),(13,1,1023,NULL),(14,1,1024,NULL),(15,1,1025,NULL),(16,1,1010,NULL),(17,1,1026,NULL),(18,1,1027,NULL),(19,1,1028,NULL),(20,1,1010,NULL),(21,1,1026,NULL),(22,1,1028,NULL),(23,1,1010,NULL),(24,1,1027,NULL),(25,1,1028,NULL),(26,1,1016,'1'),(27,4,1016,'1'),(28,3,1016,'3,5'),(29,2,1016,NULL),(30,4,1013,''),(31,3,1013,''),(32,2,1013,NULL),(33,4,1026,''),(34,3,1026,''),(35,2,1026,NULL),(36,1,1010,NULL),(37,4,1010,''),(38,3,1010,''),(39,2,1010,NULL),(40,1,1027,NULL),(41,4,1027,''),(42,3,1027,''),(43,2,1027,NULL),(44,1,1028,NULL),(45,4,1028,''),(46,3,1028,''),(47,2,1028,NULL),(48,1,1026,NULL),(49,4,1026,''),(50,3,1026,''),(51,2,1026,NULL),(52,1,1027,NULL),(53,4,1027,''),(54,3,1027,''),(55,2,1027,NULL),(56,1,1028,NULL),(57,4,1028,''),(58,3,1028,''),(59,2,1028,NULL),(60,1,1007,NULL),(61,4,1007,''),(62,3,1007,''),(63,2,1007,NULL),(64,1,1004,NULL),(65,4,1004,''),(66,3,1004,''),(67,2,1004,NULL),(68,4,1022,''),(69,3,1022,''),(70,2,1022,NULL),(71,1,1008,NULL),(72,4,1008,''),(73,3,1008,''),(74,2,1008,NULL),(75,4,1005,''),(76,3,1005,''),(77,2,1005,NULL),(78,1,1029,NULL),(79,4,1029,''),(80,3,1029,''),(81,2,1029,NULL),(82,1,1030,NULL),(83,4,1030,''),(84,3,1030,''),(85,2,1030,NULL),(86,1,1031,NULL),(87,4,1031,''),(88,3,1031,''),(89,2,1031,NULL),(90,1,1032,NULL),(91,4,1032,''),(92,3,1032,''),(93,2,1032,NULL),(94,1,1033,NULL),(95,4,1033,''),(96,3,1033,''),(97,2,1033,NULL),(98,4,1015,''),(99,3,1015,''),(100,2,1015,NULL),(101,4,1014,''),(102,3,1014,''),(103,2,1014,NULL),(104,1,1034,NULL),(105,4,1034,''),(106,3,1034,''),(107,2,1034,NULL),(108,1,1035,NULL),(109,4,1035,''),(110,3,1035,''),(111,2,1035,NULL),(112,1,1036,NULL),(113,4,1036,''),(114,3,1036,''),(115,2,1036,NULL),(116,1,1037,NULL),(117,4,1037,''),(118,3,1037,''),(119,2,1037,NULL),(120,1,1040,NULL),(121,4,1040,''),(122,3,1040,''),(123,2,1040,NULL),(124,1,1044,NULL),(125,4,1044,''),(126,3,1044,''),(127,2,1044,NULL),(128,1,1045,NULL),(129,4,1045,''),(130,3,1045,''),(131,2,1045,NULL);
/*!40000 ALTER TABLE `product_field_value` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_section`
--

DROP TABLE IF EXISTS `product_section`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_section` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `show_on_website` tinyint(1) NOT NULL DEFAULT '1',
  `sort` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_section`
--

LOCK TABLES `product_section` WRITE;
/*!40000 ALTER TABLE `product_section` DISABLE KEYS */;
INSERT INTO `product_section` VALUES (1,'Toys and play',1,2),(2,'Tools',1,3),(3,'Last section',1,1),(5,'Hidden section',0,4);
/*!40000 ALTER TABLE `product_section` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_tag`
--

DROP TABLE IF EXISTS `product_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `show_on_website` tinyint(1) NOT NULL DEFAULT '1',
  `sort` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_E3A6E39CD823E37A` (`section_id`),
  CONSTRAINT `FK_E3A6E39CD823E37A` FOREIGN KEY (`section_id`) REFERENCES `product_section` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_tag`
--

LOCK TABLES `product_tag` WRITE;
/*!40000 ALTER TABLE `product_tag` DISABLE KEYS */;
INSERT INTO `product_tag` VALUES (1,'Power tools',1,3,2),(2,'0-3 years',1,1,1),(3,'Machinery',1,5,2),(4,'4-6 years',1,2,1),(5,'Camping gear',1,0,NULL),(8,'Hand tools',1,4,2),(9,'Hidden category',0,0,2),(10,'Audio visual',1,0,NULL);
/*!40000 ALTER TABLE `product_tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `setting`
--

DROP TABLE IF EXISTS `setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `setting` (
  `setup_key` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setup_value` varchar(2056) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`setup_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `setting`
--

LOCK TABLES `setting` WRITE;
/*!40000 ALTER TABLE `setting` DISABLE KEYS */;
INSERT INTO `setting` VALUES ('auto_sku_stub','EKS'),('automate_email_loan_reminder','1'),('automate_email_membership','1'),('automate_email_overdue_days','1'),('automate_email_reservation_reminder','1'),('charge_daily_fee','0'),('daily_overdue_fee','1'),('default_checkin_location','2'),('default_loan_days','7'),('default_loan_fee','15'),('email_booking_confirmation_foot',''),('email_booking_confirmation_head',''),('email_booking_confirmation_subject',''),('email_cc_admin','1'),('email_donor_notification_foot',''),('email_donor_notification_head',''),('email_donor_notification_subject',''),('email_loan_checkin_foot','Item checkin footer<br>\r\nWith line break<hr>\r\nThank you!'),('email_loan_checkin_head','Item checkin header'),('email_loan_checkin_subject','Item check in subject'),('email_loan_confirmation_foot',''),('email_loan_confirmation_head',''),('email_loan_confirmation_subject',''),('email_loan_extension_foot',''),('email_loan_extension_head',''),('email_loan_extension_subject',''),('email_loan_overdue_foot',''),('email_loan_overdue_head',''),('email_loan_reminder_foot',''),('email_loan_reminder_head',''),('email_membership_expiry_foot',''),('email_membership_expiry_head',''),('email_reservation_reminder_foot','my footer'),('email_reservation_reminder_head','my header'),('email_reservation_reminder_subject','Remindeeee'),('email_reserve_confirmation_foot',''),('email_reserve_confirmation_head',''),('email_reserve_confirmation_subject',''),('email_welcome_foot','Welcome footer'),('email_welcome_head','Welcome header'),('email_welcome_subject','Welcome subject'),('enable_waiting_list','0'),('fixed_fee_pricing','1'),('ft_events','1'),('google_tracking_id',''),('group_similar_items','1'),('hide_branding','0'),('industry','other'),('label_type','11355'),('last_item_type','33'),('loan_terms',''),('mailchimp_api_key',''),('mailchimp_default_list_id',''),('mailchimp_double_optin','0'),('max_loan_days',''),('min_loan_days','7'),('multi_site','1'),('org_address','1 High Street, Cardigan'),('org_country','GB'),('org_currency','GBP'),('org_email','chris+le@brightpearl.com'),('org_email_footer','Footer for all emails.'),('org_languages','en,es,fr'),('org_lat','51.4425439'),('org_locale','en'),('org_long','-2.6152995'),('org_name','Demo Lending Library'),('org_postcode','SA43 1RT'),('org_timezone','Europe/London'),('page_event_header','<p>This is example content for the top of the events page, which can be configured at Settings &gt; Events.</p>\r\n<hr>\r\n<p>Html code is supported, so you can add links, images, tables and so on.</p><p><br></p>'),('page_registration_header','<p>This content shows above the registration form on the website</p><p>With new lines</p>\r\n<table class=\"table\">\r\n  <tbody><tr>\r\n    <td>left</td>\r\n    <td>right</td>\r\n  </tr>\r\n</tbody></table>'),('postal_item_fee','1.50'),('postal_item_text',''),('postal_loan_fee','5.00'),('postal_loan_text',''),('postal_loans','1'),('postal_shipping_item','1044'),('print_css',''),('registration_require_email_confirmation','0'),('registration_terms_uri',''),('reservation_fee','0.00'),('self_checkout','1'),('self_extend','1'),('setup_opening_hours','1'),('show_events_online','1'),('site_allow_registration','1'),('site_css','#select-borrow { display:block }'),('site_description',''),('site_domain',''),('site_font_name',''),('site_is_private','0'),('site_js',''),('site_welcome_user','<p>This is the content that sits on the welcome page.</p>'),('stripe_access_token','sk_test_yFwrtneIsja0GEt2wBCpSnvC'),('stripe_fee',''),('stripe_minimum_payment','1'),('stripe_payment_method','4'),('stripe_publishable_key','pk_test_o3eRfmceedfgkBYuh5AoUxA5'),('stripe_refresh_token','rt_CruBl57U48YfmClW4Ec33wFD8xDE5hD6ZTrEdKvdFz7Xq9Y1'),('stripe_use_saved_cards','1'),('stripe_user_id','acct_1CSAN7BKP1ToXW0r'),('use_labels','1');
/*!40000 ALTER TABLE `setting` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site`
--

DROP TABLE IF EXISTS `site`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `default_check_in_location` int(11) DEFAULT NULL,
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `post_code` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `colour` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_listed` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_694309E45E237E06` (`name`),
  UNIQUE KEY `UNIQ_694309E42726301` (`default_check_in_location`),
  CONSTRAINT `FK_694309E42726301` FOREIGN KEY (`default_check_in_location`) REFERENCES `inventory_location` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site`
--

LOCK TABLES `site` WRITE;
/*!40000 ALTER TABLE `site` DISABLE KEYS */;
INSERT INTO `site` VALUES (1,2,'South campus',1,'34 Elgin St, Sheffield','GB','S10 1UQ','#c2e5f9',0),(2,4,'Warehouse',1,'...','GB','...','#ace7c5',1),(3,6,'North campus',1,'3434 W. Drummond Place','US','60647','#d8aff5',1),(4,7,'Wednesday only',1,'...','GB','...','#ffcc00',1);
/*!40000 ALTER TABLE `site` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_opening`
--

DROP TABLE IF EXISTS `site_opening`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_opening` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT NULL,
  `week_day` int(11) NOT NULL,
  `time_from` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time_to` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time_changeover` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F5913608F6BD1646` (`site_id`),
  CONSTRAINT `FK_F5913608F6BD1646` FOREIGN KEY (`site_id`) REFERENCES `site` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_opening`
--

LOCK TABLES `site_opening` WRITE;
/*!40000 ALTER TABLE `site_opening` DISABLE KEYS */;
INSERT INTO `site_opening` VALUES (1,1,1,'0900','1200',NULL),(2,1,2,'0900','1700',NULL),(3,1,3,'0900','1700',NULL),(4,1,4,'0900','1700',NULL),(5,1,5,'0900','1700',NULL),(8,3,1,'1400','1700',NULL),(9,3,2,'0900','1700',NULL),(10,4,3,'1200','1400',NULL);
/*!40000 ALTER TABLE `site_opening` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `theme`
--

DROP TABLE IF EXISTS `theme`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `theme` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(2055) COLLATE utf8mb4_unicode_ci NOT NULL,
  `thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `font` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_9775E7085E237E06` (`name`),
  UNIQUE KEY `UNIQ_9775E70877153098` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `theme`
--

LOCK TABLES `theme` WRITE;
/*!40000 ALTER TABLE `theme` DISABLE KEYS */;
INSERT INTO `theme` VALUES (1,'Original','default','Left hand menu.',NULL,NULL,0.00),(2,'Top menu','top_menu','Top menu.',NULL,NULL,0.00);
/*!40000 ALTER TABLE `theme` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `waiting_list_item`
--

DROP TABLE IF EXISTS `waiting_list_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `waiting_list_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inventory_item_id` int(11) DEFAULT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `added_at` datetime NOT NULL,
  `removed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_1846EEC6536BF4A2` (`inventory_item_id`),
  KEY `IDX_1846EEC6E7A1254A` (`contact_id`),
  CONSTRAINT `FK_1846EEC6536BF4A2` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_item` (`id`),
  CONSTRAINT `FK_1846EEC6E7A1254A` FOREIGN KEY (`contact_id`) REFERENCES `contact` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `waiting_list_item`
--

LOCK TABLES `waiting_list_item` WRITE;
/*!40000 ALTER TABLE `waiting_list_item` DISABLE KEYS */;
/*!40000 ALTER TABLE `waiting_list_item` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2020-03-23 14:30:47
