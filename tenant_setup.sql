# Dump of table account
# ------------------------------------------------------------

CREATE TABLE `account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stub` varchar(32) NOT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `server_name` varchar(255) NOT NULL DEFAULT 'lend-engine-eu',
  `name` varchar(255) NOT NULL,
  `db_schema` varchar(255) NOT NULL,
  `schema_version` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `trial_expires_at` datetime DEFAULT NULL,
  `last_access_at` datetime DEFAULT NULL,
  `owner_name` varchar(255) NOT NULL,
  `owner_email` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `plan` varchar(32) DEFAULT NULL,
  `subscription_id` varchar(255) DEFAULT NULL,
  `stripe_customer_id` varchar(255) DEFAULT NULL,
  `time_zone` varchar(255) NOT NULL DEFAULT 'Europe/London',
  `industry` varchar(255) DEFAULT NULL,
  `org_email` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stub_UNIQUE` (`stub`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

LOCK TABLES `account` WRITE;
/*!40000 ALTER TABLE `account` DISABLE KEYS */;

INSERT INTO `account` (`id`, `stub`, `name`, `db_schema`, `created_at`, `trial_expires_at`, `last_access_at`, `owner_name`, `owner_email`, `status`, `subscription_id`, `plan`, `domain`, `server_name`, `schema_version`, `stripe_customer_id`, `time_zone`, `industry`, `org_email`)
VALUES
	(3,'unit_test','Organisation name','unit_test','2015-11-28 09:07:08','2019-05-20 00:00:00','2019-06-28 08:52:15','Account owner','your@email.com','DEPLOYING',NULL,'plus',NULL,'dev','20190416101346',NULL,'Europe/London','other','email@demo.com');

/*!40000 ALTER TABLE `account` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table item_type
# ------------------------------------------------------------

CREATE TABLE `item_type` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_44EE13D2727ACA70` (`parent_id`),
  CONSTRAINT `FK_44EE13D2727ACA70` FOREIGN KEY (`parent_id`) REFERENCES `item_type` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



