<?php
use UserAgentParserComparison\Html\SimpleList;

/**
 * Generate some general lists
 */
include_once 'bootstrap.php';

/* @var $pdo \PDO */

/*
 * select all real providers
 */
$statementSelectProvider = $pdo->prepare('SELECT * FROM `provider` WHERE `proType` = :proType');

$statementSelectProvider->bindValue(':proType', 'real', \PDO::PARAM_STR);

$statementSelectProvider->execute();

/*
 * Start for each provider
 */

while ($dbResultProvider = $statementSelectProvider->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {

    echo $dbResultProvider['proName'] . PHP_EOL;
    
    $folder = $basePath . '/detected/' . $dbResultProvider['proName'];
    if (! file_exists($folder)) {
        mkdir($folder, 0777, true);
    }
    
    /*
     * detected - browserNames
     */
    if ($dbResultProvider['proCanDetectBrowserName']) {
        $sql = "
            SELECT 
                `resBrowserName` AS `name`,
                `uaId`,
                `uaString`,
                COUNT(*) AS `detectionCount`
            FROM `result`
            INNER JOIN `userAgent`
                ON `uaId` = `userAgent_id`
            WHERE
                `resBrowserName` IS NOT NULL
                AND `provider_id` = :proId
            GROUP BY `resBrowserName`
        ";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':proId', $dbResultProvider['proId'], \PDO::PARAM_STR);

        $statement->execute();
        
        $generate = new SimpleList($pdo, 'Detected browser names - ' . $dbResultProvider['proName'] . ' <small>' . $dbResultProvider['proVersion'] . '</small>');
        $generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));
        
        file_put_contents($folder . '/browser-names.html', $generate->getHtml());
    }
    echo '.';
    
    /*
     * detected - renderingEngines
     */
    if ($dbResultProvider['proCanDetectEngineName']) {
        $sql = "
            SELECT
                `resEngineName` AS `name`,
                `uaId`,
                `uaString`,
                COUNT(*) AS `detectionCount`
            FROM `result`
            INNER JOIN `userAgent`
                ON `uaId` = `userAgent_id`
            WHERE
                `resEngineName` IS NOT NULL
                AND `provider_id` = :proId
            GROUP BY `resEngineName`
        ";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':proId', $dbResultProvider['proId'], \PDO::PARAM_STR);

        $statement->execute();

        $generate = new SimpleList($pdo, 'Detected rendering engines - ' . $dbResultProvider['proName'] . ' <small>' . $dbResultProvider['proVersion'] . '</small>');
        $generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));

        file_put_contents($folder . '/rendering-engines.html', $generate->getHtml());
    }
    echo '.';

    /*
     * detected - OSnames
     */
    if ($dbResultProvider['proCanDetectOsName']) {
        $sql = "
            SELECT
                `resOsName` AS `name`,
                `uaId`,
                `uaString`,
                COUNT(*) AS `detectionCount`
            FROM `result`
            INNER JOIN `userAgent`
                ON `uaId` = `userAgent_id`
            WHERE
                `resOsName` IS NOT NULL
                AND `provider_id` = :proId
            GROUP BY `resOsName`
        ";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':proId', $dbResultProvider['proId'], \PDO::PARAM_STR);

        $statement->execute();

        $generate = new SimpleList($pdo, 'Detected operating systems - ' . $dbResultProvider['proName'] . ' <small>' . $dbResultProvider['proVersion'] . '</small>');
        $generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));

        file_put_contents($folder . '/operating-systems.html', $generate->getHtml());
    }
    echo '.';

    /*
     * detected - deviceBrand
     */
    if ($dbResultProvider['proCanDetectDeviceBrand']) {
        $sql = "
            SELECT
                `resDeviceBrand` AS `name`,
                `uaId`,
                `uaString`,
                COUNT(*) AS `detectionCount`
            FROM `result`
            INNER JOIN `userAgent`
                ON `uaId` = `userAgent_id`
            WHERE
                `resDeviceBrand` IS NOT NULL
                AND `provider_id` = :proId
            GROUP BY `resDeviceBrand`
        ";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':proId', $dbResultProvider['proId'], \PDO::PARAM_STR);

        $statement->execute();

        $generate = new SimpleList($pdo, 'Detected device brands - ' . $dbResultProvider['proName'] . ' <small>' . $dbResultProvider['proVersion'] . '</small>');
        $generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));

        file_put_contents($folder . '/device-brands.html', $generate->getHtml());
    }
    echo '.';

    /*
     * detected - deviceModel
     */
    if ($dbResultProvider['proCanDetectDeviceModel']) {
        $sql = "
            SELECT
                `resDeviceModel` AS `name`,
                `uaId`,
                `uaString`,
                COUNT(*) AS `detectionCount`
            FROM `result`
            INNER JOIN `userAgent`
                ON `uaId` = `userAgent_id`
            WHERE
                `resDeviceModel` IS NOT NULL
                AND `provider_id` = :proId
            GROUP BY `resDeviceModel`
        ";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':proId', $dbResultProvider['proId'], \PDO::PARAM_STR);

        $statement->execute();

        $generate = new SimpleList($pdo, 'Detected device models - ' . $dbResultProvider['proName'] . ' <small>' . $dbResultProvider['proVersion'] . '</small>');
        $generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));

        file_put_contents($folder . '/device-models.html', $generate->getHtml());
    }
    echo '.';

    /*
     * detected - deviceTypes
     */
    if ($dbResultProvider['proCanDetectDeviceType']) {
        $sql = "
            SELECT
                `resDeviceType` AS `name`,
                `uaId`,
                `uaString`,
                COUNT(*) AS `detectionCount`
            FROM `result`
            INNER JOIN `userAgent`
                ON `uaId` = `userAgent_id`
            WHERE
                `resDeviceType` IS NOT NULL
                AND `provider_id` = :proId
            GROUP BY `resDeviceType`
        ";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':proId', $dbResultProvider['proId'], \PDO::PARAM_STR);

        $statement->execute();

        $generate = new SimpleList($pdo, 'Detected device types - ' . $dbResultProvider['proName'] . ' <small>' . $dbResultProvider['proVersion'] . '</small>');
        $generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));

        file_put_contents($folder . '/device-types.html', $generate->getHtml());
    }
    echo '.';

    /*
     * detected - bots
     */
    if ($dbResultProvider['proCanDetectBotIsBot']) {
        $sql = "
            SELECT
                `resBotName` AS `name`,
                `uaId`,
                `uaString`,
                COUNT(*) AS `detectionCount`
            FROM `result`
            INNER JOIN `userAgent`
                ON `uaId` = `userAgent_id`
            WHERE
                `resBotName` IS NOT NULL
                AND `provider_id` = :proId
            GROUP BY `resBotName`
        ";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':proId', $dbResultProvider['proId'], \PDO::PARAM_STR);

        $statement->execute();

        $generate = new SimpleList($pdo, 'Detected as bot - ' . $dbResultProvider['proName'] . ' <small>' . $dbResultProvider['proVersion'] . '</small>');
        $generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));

        file_put_contents($folder . '/bot-is-bot.html', $generate->getHtml());
    }
    echo '.';

    /*
     * detected - botNames
     */
    if ($dbResultProvider['proCanDetectBotName']) {
        $sql = "
            SELECT
                `resBotName` AS `name`,
                `uaId`,
                `uaString`,
                COUNT(*) AS `detectionCount`
            FROM `result`
            INNER JOIN `userAgent`
                ON `uaId` = `userAgent_id`
            WHERE
                `resBotName` IS NOT NULL
                AND `provider_id` = :proId
            GROUP BY `resBotName`
        ";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':proId', $dbResultProvider['proId'], \PDO::PARAM_STR);

        $statement->execute();

        $generate = new SimpleList($pdo, 'Detected bot names - ' . $dbResultProvider['proName'] . ' <small>' . $dbResultProvider['proVersion'] . '</small>');
        $generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));

        file_put_contents($folder . '/bot-names.html', $generate->getHtml());
    }
    echo '.';

    /*
     * detected - botTypes
     */
    if ($dbResultProvider['proCanDetectBotType']) {
        $sql = "
            SELECT
                `resBotType` AS `name`,
                `uaId`,
                `uaString`,
                COUNT(*) AS `detectionCount`
            FROM `result`
            INNER JOIN `userAgent`
                ON `uaId` = `userAgent_id`
            WHERE
                `resBotType` IS NOT NULL
                AND `provider_id` = :proId
            GROUP BY `resBotType`
        ";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':proId', $dbResultProvider['proId'], \PDO::PARAM_STR);

        $statement->execute();

        $generate = new SimpleList($pdo, 'Detected bot types - ' . $dbResultProvider['proName'] . ' <small>' . $dbResultProvider['proVersion'] . '</small>');
        $generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));
        
        file_put_contents($folder . '/bot-types.html', $generate->getHtml());
    }
    echo '.'.PHP_EOL;
}
