-- ------------------------------------------------------
-- Server version	5.5.46-0+deb7u1-log

SET NAMES utf8;
SET TIME_ZONE='+00:00';
SET character_set_client = utf8;

-- ------------------------------------------------------
DROP TABLE IF EXISTS `acme_ownership`;
CREATE TABLE `acme_ownership` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created` int(10) unsigned NOT NULL,
  `modified` int(10) unsigned NOT NULL,
  `type` varchar(128) NOT NULL,
  `value` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `challenges` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `value` (`value`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Registered authz objects on ACME server';


-- ------------------------------------------------------
DROP TABLE IF EXISTS `acme_cert`;
CREATE TABLE `acme_cert` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created` int(10) unsigned NOT NULL,
  `modified` int(10) unsigned NOT NULL,
  `fqdn` varchar(255) NOT NULL,
  `altnames` text NOT NULL,
  `privatekey` text NOT NULL,
  `certificate` text NOT NULL,
  `chain` text NOT NULL,
  `validfrom` int(10) unsigned NOT NULL,
  `validuntil` int(10) unsigned NOT NULL,
  `status` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Registered certificates on ACME server';


-- ------------------------------------------------------
DROP TABLE IF EXISTS `acme_account`;
CREATE TABLE `acme_account` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created` int(10) unsigned NOT NULL,
  `modified` int(10) unsigned NOT NULL,
  `contact` text NOT NULL,
  `privatekey` text NOT NULL,
  `publickey` text NOT NULL,
  `contract` text NOT NULL DEFAULT '',
  `url` text NOT NULL DEFAULT '',
  `status` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Registered accounts on ACME server';


-- ------------------------------------------------------
DROP TABLE IF EXISTS `acme_status`;
CREATE TABLE `acme_status` (
  `nonce` varchar(128) NOT NULL,
  `noncets` int(10) unsigned NOT NULL,
  `apiurls` text NOT NULL,
  `modified` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API endpoints and latest nonce';

INSERT INTO `acme_status` VALUES ('',230792700,'','1977-04-25 07:05:00');

