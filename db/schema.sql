-- MySQL dump 10.16  Distrib 10.1.29-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: nrws_brokerf
-- ------------------------------------------------------
-- Server version	10.1.29-MariaDB


--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;

CREATE TABLE `addresses` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `storeid` int NOT NULL DEFAULT 0,
  `company_name` varchar(140) DEFAULT NULL,
  `address1` varchar(140) DEFAULT NULL,
  `address2` varchar(140) DEFAULT NULL,
  `city` varchar(140) DEFAULT NULL,
  `state` varchar(2) DEFAULT NULL,
  `zip` varchar(10) DEFAULT NULL,
  `zip4` smallint(4) UNSIGNED NULL,
  `standardAddress` varchar(140) DEFAULT NULL,
  `store_phone` varchar(140) DEFAULT NULL,
  `store_phone_num` bigint(12) UNSIGNED NOT NULL DEFAULT 0,
  `store_fax_num` varchar(140) DEFAULT NULL,
  `contact_name` varchar(140) DEFAULT NULL,
  `store_email` varchar(140) DEFAULT NULL,
  `owner_email` varchar(140) DEFAULT NULL,
  `manager_email` varchar(140) DEFAULT NULL,
  `dateadded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `source` varchar(140) DEFAULT NULL,
  `employee_est` smallint(4) UNSIGNED NULL,
  `geolat` decimal(10,8) DEFAULT NULL,
  `geolong` decimal(11,8) DEFAULT NULL,
  `google_placeid` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `storeid` (`storeid`),
  KEY `store_phone_num` (`store_phone_num`),
  KEY `standardAddress` (`standardAddress`)
) ENGINE=Aria DEFAULT CHARSET=latin1 PACK_KEYS=1 PAGE_CHECKSUM=1;

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;

CREATE TABLE `brands` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(150) DEFAULT NULL,
  `isImported` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=latin1 PACK_KEYS=1 PAGE_CHECKSUM=1 ROW_FORMAT=PAGE;

--
-- Table structure for table `campaignIndex`
--

DROP TABLE IF EXISTS `campaignIndex`;

CREATE TABLE `campaignIndex` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `dateadded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `campaignid` int NOT NULL DEFAULT 0,
  `aid` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `campaignid` (`campaignid`),
  KEY `aid` (`aid`)
) ENGINE=Aria DEFAULT CHARSET=latin1 PACK_KEYS=1 PAGE_CHECKSUM=1 ROW_FORMAT=PAGE;

--
-- Table structure for table `campaigns`
--

DROP TABLE IF EXISTS `campaigns`;

CREATE TABLE `campaigns` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(150) DEFAULT NULL,
  `dateadded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=latin1 PACK_KEYS=1 PAGE_CHECKSUM=1 ROW_FORMAT=PAGE;

--
-- Table structure for table `cities`
--

DROP TABLE IF EXISTS `cities`;

CREATE TABLE `cities` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `city_name` varchar(80) DEFAULT NULL,
  `state_abrev` varchar(2) DEFAULT NULL,
  `company_name` varchar(20) DEFAULT NULL,
  `status` tinyint(2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=latin1 PACK_KEYS=1 PAGE_CHECKSUM=1;

--
-- Table structure for table `industry`
--

DROP TABLE IF EXISTS `industry`;

CREATE TABLE `industry` (
  `ititle` varchar(120) DEFAULT NULL,
  UNIQUE KEY `ititle` (`ititle`)
) ENGINE=Aria DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;

CREATE TABLE `jobs` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `dateadded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `title` varchar(80) DEFAULT NULL,
  `slug` varchar(80) DEFAULT NULL,
  `scraper_in_progress` varchar(80) DEFAULT NULL,
  `row_in_progress` int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=latin1 PACK_KEYS=1 PAGE_CHECKSUM=1;

--
-- Table structure for table `log`
--

DROP TABLE IF EXISTS `log`;

CREATE TABLE `log` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `dateadded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `domain` varchar(80) DEFAULT NULL,
  `event` varchar(80) DEFAULT NULL,
  `inserts` int UNSIGNED NOT NULL DEFAULT 0,
  `attempts` int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=latin1 PACK_KEYS=1 PAGE_CHECKSUM=1;

--
-- Table structure for table `providers`
--

DROP TABLE IF EXISTS `providers`;

CREATE TABLE `providers` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `ipaddress` varchar(19) DEFAULT NULL,
  `name` varchar(140) DEFAULT NULL,
  `url` varchar(140) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ipaddress` (`ipaddress`)
) ENGINE=Aria DEFAULT CHARSET=latin1 PACK_KEYS=1 PAGE_CHECKSUM=1;

--
-- Table structure for table `search_term`
--

DROP TABLE IF EXISTS `search_term`;

CREATE TABLE `search_term` (
  `recordid` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `keywords` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`recordid`)
) ENGINE=Aria DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;

CREATE TABLE `settings` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting` varchar(255) DEFAULT NULL,
  `descrip` varchar(255) DEFAULT NULL,
  `hook` varchar(45) DEFAULT NULL,
  `data` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1;

--
-- Table structure for table `storeBrands`
--

DROP TABLE IF EXISTS `storeBrands`;

CREATE TABLE `storeBrands` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `dateadded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `storeid` int UNSIGNED NOT NULL DEFAULT 0,
  `brandid` int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `i_storebrands_` (`brandid`,`storeid`),
  KEY `storeid` (`storeid`)
) ENGINE=Aria DEFAULT CHARSET=latin1 PACK_KEYS=1 PAGE_CHECKSUM=1;

--
-- Table structure for table `stores`
--

DROP TABLE IF EXISTS `stores`;

CREATE TABLE `stores` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `status` tinyint(2) UNSIGNED NOT NULL DEFAULT 2,
  `whiteScore` smallint(3) UNSIGNED NOT NULL DEFAULT 0,
  `blackScore` smallint(3) UNSIGNED NOT NULL DEFAULT 0,
  `statusScore` smallint(3) UNSIGNED NOT NULL DEFAULT 0,
  `scrapeDate` int UNSIGNED NOT NULL DEFAULT 0,
  `domain` varchar(255) DEFAULT NULL,
  `IP` varchar(19) DEFAULT NULL,
  `network` varchar(11) DEFAULT NULL,
  `hostname` varchar(140) DEFAULT NULL,
  `num_locations` smallint(11) UNSIGNED NOT NULL,
  `num_brands` smallint(11) UNSIGNED NOT NULL,
  `num_employees` smallint(11) UNSIGNED NOT NULL,
  `num_google_hits` smallint(11) UNSIGNED NOT NULL,
  `result` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `statusLock` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `domain` (`domain`),
  KEY `IP` (`IP`),
  KEY `network` (`network`),
  KEY `result` (`result`),
  KEY `status` (`status`),
  KEY `statusScore` (`statusScore`)
) ENGINE=Aria DEFAULT CHARSET=latin1 PACK_KEYS=1 PAGE_CHECKSUM=1;

--
-- Table structure for table `storesContent`
--

DROP TABLE IF EXISTS `storesContent`;

CREATE TABLE `storesContent` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `dateadded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `storeid` int UNSIGNED NOT NULL DEFAULT 0,
  `filename` varchar(255) DEFAULT NULL,
  `keywords` text NOT NULL,
  `url` varchar(2083) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `storeid` (`storeid`)
) ENGINE=Aria DEFAULT CHARSET=latin1 PACK_KEYS=1 PAGE_CHECKSUM=1;

--
-- Table structure for table `zip_codes`
--

DROP TABLE IF EXISTS `zip_codes`;

CREATE TABLE `zip_codes` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `zip` int UNSIGNED NOT NULL DEFAULT 0,
  `type` varchar(140) DEFAULT NULL,
  `decommissioned` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `primary_city` varchar(140) DEFAULT NULL,
  `acceptable_cities` varchar(255) DEFAULT NULL,
  `state` varchar(2) DEFAULT NULL,
  `county` varchar(100) DEFAULT NULL,
  `timezone` varchar(100) DEFAULT NULL,
  `area_codes` varchar(255) DEFAULT NULL,
  `world_region` varchar(10) DEFAULT NULL,
  `country` varchar(3) DEFAULT NULL,
  `irs_estimated_population_2014` int(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `zip` (`zip`),
  KEY `state` (`state`)
) ENGINE=Aria DEFAULT CHARSET=latin1 PACK_KEYS=1 PAGE_CHECKSUM=1;

--
-- Table structure for table `phinxlog`
--

DROP TABLE IF EXISTS `phinxlog`;

CREATE TABLE `phinxlog` (
  `version` bigint(20) NOT NULL,
  `migration_name` varchar(100) DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `breakpoint` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Procedures and Triggers
--
DELIMITER $$

--
-- Functions
--

DROP FUNCTION IF EXISTS `ALPHANUM` $$
DROP FUNCTION IF EXISTS `VINCENTY` $$

CREATE FUNCTION `ALPHANUM`( str CHAR(32) ) RETURNS char(16) CHARSET latin1
BEGIN
	DECLARE i, len SMALLINT DEFAULT 1;
	DECLARE ret CHAR(32) DEFAULT '';
	DECLARE c CHAR(1);
	SET len = CHAR_LENGTH( str );
	REPEAT
		BEGIN
			SET c = MID( str, i, 1 );
			IF c REGEXP '[[:alnum:]]' THEN
				SET ret=CONCAT(ret,c);
			END IF;
			SET i = i + 1;
		END;
	UNTIL i > len END REPEAT;
	RETURN ret;
END $$


CREATE FUNCTION `VINCENTY`(
    lat1 FLOAT, lon1 FLOAT,
    lat2 FLOAT, lon2 FLOAT
) RETURNS float
    NO SQL
    DETERMINISTIC
    COMMENT 'Returns the distance in degrees on the Earth between two known points\n    of latitude and longitude using the Vincenty formula from:\n    http://en.wikipedia.org/wiki/Great-circle_distance\n    https://gist.github.com/cviebrock/b2dd681d1970f59e00d04d59f1320bb1'
BEGIN
RETURN DEGREES(
    ATAN2(
        SQRT(
            POW(COS(RADIANS(lat2))*SIN(RADIANS(lon2-lon1)),2) +
            POW(COS(RADIANS(lat1))*SIN(RADIANS(lat2)) -
                (SIN(RADIANS(lat1))*COS(RADIANS(lat2)) *
                    COS(RADIANS(lon2-lon1))
                ),
                2
            )
        ),
        SIN(RADIANS(lat1))*SIN(RADIANS(lat2)) +
        COS(RADIANS(lat1))*COS(RADIANS(lat2))*COS(RADIANS(lon2-lon1))
    )
);
END $$

--
-- Triggers
--

-- Drop triggers before rebuilding
DROP TRIGGER IF EXISTS delStores $$

-- Delete stores and child records
CREATE TRIGGER delStores AFTER DELETE ON `stores`
	FOR EACH ROW BEGIN
    DELETE FROM addresses WHERE storeid = OLD.id ;
    DELETE FROM storesContent WHERE storeid = OLD.id ;
END$$

DELIMITER ;
