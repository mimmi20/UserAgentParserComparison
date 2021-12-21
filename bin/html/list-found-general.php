<?php
use UserAgentParserComparison\Html\SimpleList;

/**
 * Generate some general lists
 */
include_once 'bootstrap.php';

/* @var $pdo \PDO */

/*
 * create the folder
 */
$folder = $basePath . '/detected/general';
if (! file_exists($folder)) {
    mkdir($folder, 0777, true);
}

/*
 * select all real providers
 */
$statementSelectProvider = $pdo->prepare('SELECT * FROM `provider` WHERE `proType` = :proType');

$statementSelectProvider->bindValue(':proType', 'real', \PDO::PARAM_STR);

$statementSelectProvider->execute();

$proIds = array_column($statementSelectProvider->fetchAll(\PDO::FETCH_ASSOC), 'proId');

/*
 * detected - browserNames
 */
$sql = "
    SELECT 
        `resBrowserName` AS `name`,
        `uaId`,
        `uaString`,
        COUNT(1) AS `detectionCount`
    FROM `result`
    INNER JOIN `userAgent`
        ON `uaId` = `userAgent_id`
    WHERE
        `provider_id` IN('" . implode('\', \'', $proIds) . "')
        AND `resBrowserName` IS NOT NULL
    GROUP BY `resBrowserName`
";
$statement = $pdo->prepare($sql);

$statement->execute();

$generate = new SimpleList($pdo, 'Detected browser names');
$generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));

file_put_contents($folder . '/browser-names.html', $generate->getHtml());
echo '.';

/*
 * detected - renderingEngines
 */
$sql = "
    SELECT
        `resEngineName` AS `name`,
        `uaId`,
        `uaString`,
        COUNT(1) AS `detectionCount`
    FROM `result`
    INNER JOIN `userAgent`
        ON `uaId` = `userAgent_id`
    WHERE
        `provider_id` IN('" . implode('\', \'', $proIds) . "')
        AND `resEngineName` IS NOT NULL
    GROUP BY `resEngineName`
";
$statement = $pdo->prepare($sql);

$statement->execute();

$generate = new SimpleList($pdo, 'Detected rendering engines');
$generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));

file_put_contents($folder . '/rendering-engines.html', $generate->getHtml());
echo '.';

/*
 * detected - OSnames
 */
$sql = "
    SELECT
        `resOsName` AS `name`,
        `uaId`,
        `uaString`,
        COUNT(1) AS `detectionCount`
    FROM `result`
    INNER JOIN `userAgent`
        ON `uaId` = `userAgent_id`
    WHERE
        `provider_id` IN('" . implode('\', \'', $proIds) . "')
        AND `resOsName` IS NOT NULL
    GROUP BY `resOsName`
";
$statement = $pdo->prepare($sql);

$statement->execute();

$generate = new SimpleList($pdo, 'Detected operating systems');
$generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));

file_put_contents($folder . '/operating-systems.html', $generate->getHtml());
echo '.';

/*
 * detected - deviceModel
 */
$sql = "
    SELECT
        `resDeviceModel` AS `name`,
        `uaId`,
        `uaString`,
        COUNT(1) AS `detectionCount`
    FROM `result`
    INNER JOIN `userAgent`
        ON `uaId` = `userAgent_id`
    WHERE
        `provider_id` IN('" . implode('\', \'', $proIds) . "')
        AND `resDeviceModel` IS NOT NULL
    GROUP BY `resDeviceModel`
";
$statement = $pdo->prepare($sql);

$statement->execute();

$generate = new SimpleList($pdo, 'Detected device models');
$generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));

file_put_contents($folder . '/device-models.html', $generate->getHtml());
echo '.';

/*
 * detected - deviceBrand
 */
$sql = "
    SELECT
        `resDeviceBrand` AS `name`,
        `uaId`,
        `uaString`,
        COUNT(1) AS `detectionCount`
    FROM `result`
    INNER JOIN `userAgent`
        ON `uaId` = `userAgent_id`
    WHERE
        `provider_id` IN('" . implode('\', \'', $proIds) . "')
        AND `resDeviceBrand` IS NOT NULL
    GROUP BY `resDeviceBrand`
";
$statement = $pdo->prepare($sql);

$statement->execute();

$generate = new SimpleList($pdo, 'Detected device brands');
$generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));

file_put_contents($folder . '/device-brands.html', $generate->getHtml());
echo '.';

/*
 * detected - deviceTypes
 */
$sql = "
    SELECT
        `resDeviceType` AS `name`,
        `uaId`,
        `uaString`,
        COUNT(1) AS `detectionCount`
    FROM `result`
    INNER JOIN `userAgent`
        ON `uaId` = `userAgent_id`
    WHERE
        `provider_id` IN('" . implode('\', \'', $proIds) . "')
        AND `resDeviceType` IS NOT NULL
    GROUP BY `resDeviceType`
";
$statement = $pdo->prepare($sql);

$statement->execute();

$generate = new SimpleList($pdo, 'Detected device types');
$generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));

file_put_contents($folder . '/device-types.html', $generate->getHtml());
echo '.';

/*
 * detected - botNames
 */
$sql = "
    SELECT
        `resBotName` AS `name`,
        `uaId`,
        `uaString`,
        COUNT(1) AS `detectionCount`
    FROM `result`
    INNER JOIN `userAgent`
        ON `uaId` = `userAgent_id`
    WHERE
        `provider_id` IN('" . implode('\', \'', $proIds) . "')
        AND `resBotName` IS NOT NULL
    GROUP BY `resBotName`
";
$statement = $pdo->prepare($sql);

$statement->execute();

$generate = new SimpleList($pdo, 'Detected bot names');
$generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));

file_put_contents($folder . '/bot-names.html', $generate->getHtml());
echo '.';

/*
 * detected - botTypes
 */
$sql = "
    SELECT
        `resBotType` AS `name`,
        `uaId`,
        `uaString`,
        COUNT(1) AS `detectionCount`
    FROM `result`
    INNER JOIN `userAgent`
        ON `uaId` = `userAgent_id`
    WHERE
        `provider_id` IN('" . implode('\', \'', $proIds) . "')
        AND `resBotType` IS NOT NULL
    GROUP BY `resBotType`
";
$statement = $pdo->prepare($sql);

$statement->execute();

$generate = new SimpleList($pdo, 'Detected bot types');
$generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));

file_put_contents($folder . '/bot-types.html', $generate->getHtml());
