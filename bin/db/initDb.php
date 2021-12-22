<?php

use Ramsey\Uuid\Uuid;

include 'bootstrap.php';

/* @var $pdo \PDO */

$pdo->prepare('DROP TABLE IF EXISTS `provider`')->execute();
$pdo->prepare('CREATE TABLE IF NOT EXISTS `provider` (
  `proId` char(36) COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:uuid)\',
  `proType` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `proName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `proVersion` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `proPackageName` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `proHomepage` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `proCanDetectBrowserName` tinyint(1) NOT NULL,
  `proCanDetectBrowserVersion` tinyint(1) NOT NULL,
  `proCanDetectEngineName` tinyint(1) NOT NULL,
  `proCanDetectEngineVersion` tinyint(1) NOT NULL,
  `proCanDetectOsName` tinyint(1) NOT NULL,
  `proCanDetectOsVersion` tinyint(1) NOT NULL,
  `proCanDetectDeviceModel` tinyint(1) NOT NULL,
  `proCanDetectDeviceBrand` tinyint(1) NOT NULL,
  `proCanDetectDeviceType` tinyint(1) NOT NULL,
  `proCanDetectDeviceIsMobile` tinyint(1) NOT NULL,
  `proCanDetectDeviceIsTouch` tinyint(1) NOT NULL,
  `proCanDetectBotIsBot` tinyint(1) NOT NULL,
  `proCanDetectBotName` tinyint(1) NOT NULL,
  `proCanDetectBotType` tinyint(1) NOT NULL,
  PRIMARY KEY (`proId`),
  UNIQUE KEY `unique_provider_name` (`proType`,`proName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC CHECKSUM=1')->execute();

$pdo->prepare('DROP TABLE IF EXISTS `useragent`')->execute();
$pdo->prepare('CREATE TABLE IF NOT EXISTS `useragent` (
  `uaId` char(36) COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:uuid)\',
  `uaHash` varbinary(255) NOT NULL,
  `uaString` longtext COLLATE utf8_unicode_ci NOT NULL,
  `uaAdditionalHeaders` longtext COLLATE utf8_unicode_ci DEFAULT NULL COMMENT \'(DC2Type:array)\',
  PRIMARY KEY (`uaId`),
  UNIQUE KEY `userAgent_hash` (`uaHash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC CHECKSUM=1')->execute();

$pdo->prepare('DROP TABLE IF EXISTS `result`')->execute();
$pdo->prepare('CREATE TABLE IF NOT EXISTS `result` (
  `resId` char(36) COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:uuid)\',
  `provider_id` char(36) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT \'(DC2Type:uuid)\',
  `resProviderVersion` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `userAgent_id` char(36) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT \'(DC2Type:uuid)\',
  `resFilename` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `resParseTime` decimal(20,5) DEFAULT NULL,
  `resLastChangeDate` datetime NOT NULL,
  `resResultFound` tinyint(1) NOT NULL,
  `resBrowserName` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `resBrowserVersion` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `resEngineName` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `resEngineVersion` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `resOsName` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `resOsVersion` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `resDeviceModel` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `resDeviceBrand` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `resDeviceType` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `resDeviceIsMobile` tinyint(1) DEFAULT NULL,
  `resDeviceIsTouch` tinyint(1) DEFAULT NULL,
  `resBotIsBot` tinyint(1) DEFAULT NULL,
  `resBotName` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `resBotType` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `resRawResult` longtext COLLATE utf8_unicode_ci DEFAULT NULL COMMENT \'(DC2Type:object)\',
  PRIMARY KEY (`resId`),
  UNIQUE KEY `unique_userAgent_provider` (`userAgent_id`,`provider_id`),
  KEY `IDX_136AC113E127EC2A` (`userAgent_id`),
  KEY `IDX_136AC113A53A8AA` (`provider_id`),
  KEY `result_resBrowserName` (`resBrowserName`),
  KEY `result_resEngineName` (`resEngineName`),
  KEY `result_resOsName` (`resOsName`),
  KEY `result_resDeviceModel` (`resDeviceModel`),
  KEY `result_resDeviceBrand` (`resDeviceBrand`),
  KEY `result_resDeviceType` (`resDeviceType`),
  KEY `result_resBotName` (`resBotName`),
  KEY `result_resBotType` (`resBotType`),
  CONSTRAINT `FK_136AC113A53A8AA` FOREIGN KEY (`provider_id`) REFERENCES `provider` (`proId`),
  CONSTRAINT `FK_136AC113E127EC2A` FOREIGN KEY (`userAgent_id`) REFERENCES `useragent` (`uaId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC CHECKSUM=1')->execute();

$pdo->prepare('DROP TABLE IF EXISTS `resultevaluation`')->execute();
$pdo->prepare('CREATE TABLE IF NOT EXISTS `resultevaluation` (
  `result_id` char(36) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT \'(DC2Type:uuid)\',
  `revId` char(36) COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:uuid)\',
  `lastChangeDate` datetime NOT NULL,
  `browserNameSameResult` int(11) NOT NULL,
  `browserNameHarmonizedSameResult` int(11) NOT NULL,
  `browserVersionSameResult` int(11) NOT NULL,
  `browserVersionHarmonizedSameResult` int(11) NOT NULL,
  `engineNameSameResult` int(11) NOT NULL,
  `engineNameHarmonizedSameResult` int(11) NOT NULL,
  `engineVersionSameResult` int(11) NOT NULL,
  `engineVersionHarmonizedSameResult` int(11) NOT NULL,
  `osNameSameResult` int(11) NOT NULL,
  `osNameHarmonizedSameResult` int(11) NOT NULL,
  `osVersionSameResult` int(11) NOT NULL,
  `osVersionHarmonizedSameResult` int(11) NOT NULL,
  `deviceModelSameResult` int(11) NOT NULL,
  `deviceModelHarmonizedSameResult` int(11) NOT NULL,
  `deviceBrandSameResult` int(11) NOT NULL,
  `deviceBrandHarmonizedSameResult` int(11) NOT NULL,
  `deviceTypeSameResult` int(11) NOT NULL,
  `deviceTypeHarmonizedSameResult` int(11) NOT NULL,
  `asMobileDetectedByOthers` int(11) NOT NULL,
  `asTouchDetectedByOthers` int(11) NOT NULL,
  `asBotDetectedByOthers` int(11) NOT NULL,
  `botNameSameResult` int(11) NOT NULL,
  `botNameHarmonizedSameResult` int(11) NOT NULL,
  `botTypeSameResult` int(11) NOT NULL,
  `botTypeHarmonizedSameResult` int(11) NOT NULL,
  PRIMARY KEY (`revId`),
  UNIQUE KEY `UNIQ_2846B1657A7B643` (`result_id`),
  CONSTRAINT `FK_2846B1657A7B643` FOREIGN KEY (`result_id`) REFERENCES `result` (`resId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC CHECKSUM=1')->execute();

$pdo->prepare('DROP TABLE IF EXISTS `useragentevaluation`')->execute();
$pdo->prepare('CREATE TABLE IF NOT EXISTS `useragentevaluation` (
  `uevId` char(36) COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:uuid)\',
  `lastChangeDate` datetime NOT NULL,
  `resultCount` int(11) NOT NULL,
  `resultFound` int(11) NOT NULL,
  `browserNames` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:object)\',
  `browserNamesHarmonized` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:object)\',
  `browserNameFound` int(11) NOT NULL,
  `browserNameFoundUnique` int(11) NOT NULL,
  `browserNameMaxSameResultCount` int(11) NOT NULL,
  `browserNameHarmonizedFoundUnique` int(11) NOT NULL,
  `browserNameHarmonizedMaxSameResultCount` int(11) NOT NULL,
  `browserVersions` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:object)\',
  `browserVersionsHarmonized` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:object)\',
  `browserVersionFound` int(11) NOT NULL,
  `browserVersionFoundUnique` int(11) NOT NULL,
  `browserVersionMaxSameResultCount` int(11) NOT NULL,
  `browserVersionHarmonizedFoundUnique` int(11) NOT NULL,
  `browserVersionHarmonizedMaxSameResultCount` int(11) NOT NULL,
  `engineNames` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:object)\',
  `engineNamesHarmonized` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:object)\',
  `engineNameFound` int(11) NOT NULL,
  `engineNameFoundUnique` int(11) NOT NULL,
  `engineNameMaxSameResultCount` int(11) NOT NULL,
  `engineNameHarmonizedFoundUnique` int(11) NOT NULL,
  `engineNameHarmonizedMaxSameResultCount` int(11) NOT NULL,
  `engineVersions` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:object)\',
  `engineVersionsHarmonized` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:object)\',
  `engineVersionFound` int(11) NOT NULL,
  `engineVersionFoundUnique` int(11) NOT NULL,
  `engineVersionMaxSameResultCount` int(11) NOT NULL,
  `engineVersionHarmonizedFoundUnique` int(11) NOT NULL,
  `engineVersionHarmonizedMaxSameResultCount` int(11) NOT NULL,
  `osNames` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:object)\',
  `osNamesHarmonized` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:object)\',
  `osNameFound` int(11) NOT NULL,
  `osNameFoundUnique` int(11) NOT NULL,
  `osNameMaxSameResultCount` int(11) NOT NULL,
  `osNameHarmonizedFoundUnique` int(11) NOT NULL,
  `osNameHarmonizedMaxSameResultCount` int(11) NOT NULL,
  `osVersions` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:object)\',
  `osVersionsHarmonized` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:object)\',
  `osVersionFound` int(11) NOT NULL,
  `osVersionFoundUnique` int(11) NOT NULL,
  `osVersionMaxSameResultCount` int(11) NOT NULL,
  `osVersionHarmonizedFoundUnique` int(11) NOT NULL,
  `osVersionHarmonizedMaxSameResultCount` int(11) NOT NULL,
  `deviceModels` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:object)\',
  `deviceModelsHarmonized` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:object)\',
  `deviceModelFound` int(11) NOT NULL,
  `deviceModelFoundUnique` int(11) NOT NULL,
  `deviceModelMaxSameResultCount` int(11) NOT NULL,
  `deviceModelHarmonizedFoundUnique` int(11) NOT NULL,
  `deviceModelHarmonizedMaxSameResultCount` int(11) NOT NULL,
  `deviceBrands` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:object)\',
  `deviceBrandsHarmonized` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:object)\',
  `deviceBrandFound` int(11) NOT NULL,
  `deviceBrandFoundUnique` int(11) NOT NULL,
  `deviceBrandMaxSameResultCount` int(11) NOT NULL,
  `deviceBrandHarmonizedFoundUnique` int(11) NOT NULL,
  `deviceBrandHarmonizedMaxSameResultCount` int(11) NOT NULL,
  `deviceTypes` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:object)\',
  `deviceTypesHarmonized` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:object)\',
  `deviceTypeFound` int(11) NOT NULL,
  `deviceTypeFoundUnique` int(11) NOT NULL,
  `deviceTypeMaxSameResultCount` int(11) NOT NULL,
  `deviceTypeHarmonizedFoundUnique` int(11) NOT NULL,
  `deviceTypeHarmonizedMaxSameResultCount` int(11) NOT NULL,
  `asMobileDetectedCount` int(11) NOT NULL,
  `asTouchDetectedCount` int(11) NOT NULL,
  `asBotDetectedCount` int(11) NOT NULL,
  `botNames` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:object)\',
  `botNamesHarmonized` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:object)\',
  `botNameFound` int(11) NOT NULL,
  `botNameFoundUnique` int(11) NOT NULL,
  `botNameMaxSameResultCount` int(11) NOT NULL,
  `botNameHarmonizedFoundUnique` int(11) NOT NULL,
  `botNameHarmonizedMaxSameResultCount` int(11) NOT NULL,
  `botTypes` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:object)\',
  `botTypesHarmonized` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT \'(DC2Type:object)\',
  `botTypeFound` int(11) NOT NULL,
  `botTypeFoundUnique` int(11) NOT NULL,
  `botTypeMaxSameResultCount` int(11) NOT NULL,
  `botTypeHarmonizedFoundUnique` int(11) NOT NULL,
  `botTypeHarmonizedMaxSameResultCount` int(11) NOT NULL,
  `userAgent_id` char(36) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT \'(DC2Type:uuid)\',
  PRIMARY KEY (`uevId`),
  UNIQUE KEY `UNIQ_D98F3DB4E127EC2A` (`userAgent_id`),
  CONSTRAINT `FK_D98F3DB4E127EC2A` FOREIGN KEY (`userAgent_id`) REFERENCES `useragent` (`uaId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC CHECKSUM=1')->execute();

$pdo->prepare('CREATE OR REPLACE VIEW `real-provider` AS SELECT * FROM `provider` WHERE `proType` = :proType')->bindValue(':proType', 'real', \PDO::PARAM_STR)->execute();
$pdo->prepare('CREATE OR REPLACE VIEW `test-provider` AS SELECT * FROM `provider` WHERE `proType` = :proType')->bindValue(':proType', 'testSuite', \PDO::PARAM_STR)->execute();

$pdo->prepare('CREATE OR REPLACE VIEW `providers-general-overview` AS SELECT
                `real-provider`.*,
            
                SUM(`resResultFound`) AS `resultFound`,
            
                COUNT(`resBrowserName`) AS `browserFound`,
                COUNT(DISTINCT `resBrowserName`) AS `browserFoundUnique`,
            
                COUNT(`resEngineName`) AS `engineFound`,
                COUNT(DISTINCT `resEngineName`) AS `engineFoundUnique`,
            
                COUNT(`resOsName`) AS `osFound`,
                COUNT(DISTINCT `resOsName`) AS `osFoundUnique`,
            
                COUNT(`resDeviceModel`) AS `deviceModelFound`,
                COUNT(DISTINCT `resDeviceModel`) AS `deviceModelFoundUnique`,
            
                COUNT(`resDeviceBrand`) AS `deviceBrandFound`,
                COUNT(DISTINCT `resDeviceBrand`) AS `deviceBrandFoundUnique`,
            
                COUNT(`resDeviceType`) AS `deviceTypeFound`,
                COUNT(DISTINCT `resDeviceType`) AS `deviceTypeFoundUnique`,
            
                COUNT(`resDeviceIsMobile`) AS `asMobileDetected`,
            
                COUNT(`resBotIsBot`) AS `asBotDetected`,
            
                AVG(`resParseTime`) AS `avgParseTime`
            FROM `result`
            INNER JOIN `real-provider`
                ON `proId` = `provider_id`
            GROUP BY
                `proId`
            ORDER BY 
                `proName`')->execute();
$pdo->prepare('CREATE OR REPLACE VIEW `useragents-general-overview` AS SELECT 
                `proName`,
                COUNT(*) AS `countNumber`
            FROM `test-provider`
            JOIN `result`
                ON `provider_id` = `proId`
            GROUP BY `proId`
            ORDER BY `proName`')->execute();

$pdo->prepare('CREATE OR REPLACE VIEW `list-found-general-browser-names` AS SELECT * FROM `result` WHERE `provider_id` IN (SELECT `proId` FROM `real-provider`) AND `resBrowserName` IS NOT NULL')->execute();
$pdo->prepare('CREATE OR REPLACE VIEW `found-general-browser-names` AS SELECT 
        `resBrowserName` AS `name`,
        `uaId`,
        `uaString`,
        COUNT(`resBrowserName`) AS `detectionCount`
    FROM `list-found-general-browser-names`
    INNER JOIN `userAgent`
        ON `uaId` = `userAgent_id`
    GROUP BY `resBrowserName`')->execute();
$pdo->prepare('CREATE OR REPLACE VIEW `list-found-general-engine-names` AS SELECT * FROM `result` WHERE `provider_id` IN (SELECT `proId` FROM `real-provider`) AND `resEngineName` IS NOT NULL')->execute();
$pdo->prepare('CREATE OR REPLACE VIEW `found-general-engine-names` AS SELECT
        `resEngineName` AS `name`,
        `uaId`,
        `uaString`,
        COUNT(`resEngineName`) AS `detectionCount`
    FROM `list-found-general-engine-names`
    INNER JOIN `userAgent`
        ON `uaId` = `userAgent_id`
    GROUP BY `resEngineName`')->execute();
$pdo->prepare('CREATE OR REPLACE VIEW `list-found-general-os-names` AS SELECT * FROM `result` WHERE `provider_id` IN (SELECT `proId` FROM `real-provider`) AND `resOsName` IS NOT NULL')->execute();
$pdo->prepare('CREATE OR REPLACE VIEW `found-general-os-names` AS SELECT
        `resOsName` AS `name`,
        `uaId`,
        `uaString`,
        COUNT(`resOsName`) AS `detectionCount`
    FROM `list-found-general-os-names`
    INNER JOIN `userAgent`
        ON `uaId` = `userAgent_id`
    GROUP BY `resOsName`')->execute();
$pdo->prepare('CREATE OR REPLACE VIEW `list-found-general-device-models` AS SELECT * FROM `result` WHERE `provider_id` IN (SELECT `proId` FROM `real-provider`) AND `resDeviceModel` IS NOT NULL')->execute();
$pdo->prepare('CREATE OR REPLACE VIEW `found-general-device-models` AS SELECT
        `resDeviceModel` AS `name`,
        `uaId`,
        `uaString`,
        COUNT(`resDeviceModel`) AS `detectionCount`
    FROM `list-found-general-device-models`
    INNER JOIN `userAgent`
        ON `uaId` = `userAgent_id`
    GROUP BY `resDeviceModel`')->execute();
$pdo->prepare('CREATE OR REPLACE VIEW `list-found-general-device-brands` AS SELECT * FROM `result` WHERE `provider_id` IN (SELECT `proId` FROM `real-provider`) AND `resDeviceBrand` IS NOT NULL')->execute();
$pdo->prepare('CREATE OR REPLACE VIEW `found-general-device-brands` AS SELECT
        `resDeviceBrand` AS `name`,
        `uaId`,
        `uaString`,
        COUNT(`resDeviceBrand`) AS `detectionCount`
    FROM `list-found-general-device-brands`
    INNER JOIN `userAgent`
        ON `uaId` = `userAgent_id`
    GROUP BY `resDeviceBrand`')->execute();
$pdo->prepare('CREATE OR REPLACE VIEW `list-found-general-device-types` AS SELECT * FROM `result` WHERE `provider_id` IN (SELECT `proId` FROM `real-provider`) AND `resDeviceType` IS NOT NULL')->execute();
$pdo->prepare('CREATE OR REPLACE VIEW `found-general-device-types` AS SELECT
        `resDeviceType` AS `name`,
        `uaId`,
        `uaString`,
        COUNT(`resDeviceType`) AS `detectionCount`
    FROM `list-found-general-device-types`
    INNER JOIN `userAgent`
        ON `uaId` = `userAgent_id`
    GROUP BY `resDeviceType`')->execute();
$pdo->prepare('CREATE OR REPLACE VIEW `list-found-general-device-ismobile` AS SELECT * FROM `result` WHERE `provider_id` IN (SELECT `proId` FROM `real-provider`) AND `resDeviceIsMobile` IS NOT NULL')->execute();
$pdo->prepare('CREATE OR REPLACE VIEW `list-found-general-bot-isbot` AS SELECT * FROM `result` WHERE `provider_id` IN (SELECT `proId` FROM `real-provider`) AND `resBotIsBot` IS NOT NULL')->execute();
$pdo->prepare('CREATE OR REPLACE VIEW `found-general-bot-names` AS SELECT
        `resBotName` AS `name`,
        `uaId`,
        `uaString`,
        COUNT(`resBotName`) AS `detectionCount`
    FROM `list-found-general-bot-names`
    INNER JOIN `userAgent`
        ON `uaId` = `userAgent_id`
    GROUP BY `resBotName`')->execute();
$pdo->prepare('CREATE OR REPLACE VIEW `list-found-general-bot-types` AS SELECT * FROM `result` WHERE `provider_id` IN (SELECT `proId` FROM `real-provider`) AND `resBotType` IS NOT NULL')->execute();
$pdo->prepare('CREATE OR REPLACE VIEW `found-general-bot-types` AS SELECT
        `resBotType` AS `name`,
        `uaId`,
        `uaString`,
        COUNT(`resBotType`) AS `detectionCount`
    FROM `list-found-general-bot-types`
    INNER JOIN `userAgent`
        ON `uaId` = `userAgent_id`
    GROUP BY `resBotType`')->execute();

$pdo->prepare('CREATE OR REPLACE VIEW `found-results` AS SELECT * FROM `result` WHERE `resResultFound` = 1 AND `provider_id` IN (SELECT `proId` FROM `real-provider`)')->execute();

//$pdo->prepare('CREATE OR REPLACE VIEW `useragentevaluation`')->execute();
//$pdo->prepare('CREATE OR REPLACE VIEW `useragentevaluation`')->execute();
//$pdo->prepare('CREATE OR REPLACE VIEW `useragentevaluation`')->execute();
