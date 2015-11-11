-- ------------------------------------------------------
-- Acme PHP Client Sqlite3 schema

-- ------------------------------------------------------
DROP TABLE IF EXISTS `acme_authz`;
CREATE TABLE `acme_authz` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `created` int(10) NOT NULL,
  `modified` int(10) NOT NULL,
  `type` varchar(128) NOT NULL,
  `value` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `challenges` text NOT NULL
);

CREATE INDEX `authz_value` ON `acme_authz` (`value`);

-- ------------------------------------------------------
DROP TABLE IF EXISTS `acme_cert`;
CREATE TABLE `acme_cert` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `created` int(10) NOT NULL,
  `modified` int(10) NOT NULL,
  `fqdn` varchar(255) NOT NULL,
  `altnames` text NOT NULL,
  `privatekey` text NOT NULL,
  `certificate` text NOT NULL,
  `chain` text NOT NULL,
  `validfrom` int(10) NOT NULL,
  `validuntil` int(10) NOT NULL,
  `status` int(10) NOT NULL
);

CREATE INDEX `cert_status` ON `acme_cert` (`status`);

-- ------------------------------------------------------
DROP TABLE IF EXISTS `acme_contact`;
CREATE TABLE `acme_contact` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `created` int(10) NOT NULL,
  `modified` int(10) NOT NULL,
  `contact` text NOT NULL,
  `privatekey` text NOT NULL,
  `publickey` text NOT NULL,
  `contract` text NOT NULL,
  `url` text NOT NULL,
  `status` int(10) NOT NULL
);

CREATE INDEX `contact_status` ON `acme_contact` (`status`);

-- ------------------------------------------------------
DROP TABLE IF EXISTS `acme_status`;
CREATE TABLE `acme_status` (
  `nonce` varchar(128) NOT NULL,
  `noncets` int(10) NOT NULL,
  `apiurls` text NOT NULL,
  `modified` datetime NOT NULL
);

INSERT INTO `acme_status` VALUES ('',230792700,'','1977-04-25 07:05:00');

