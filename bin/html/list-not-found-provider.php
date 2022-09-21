<?php
use UserAgentParserComparison\Html\SimpleList;

/**
 * Generate some general lists
 */
include_once 'bootstrap.php';

echo '~~~ create html list for all not-founds for all providers ~~~' . PHP_EOL;

/* @var $pdo \PDO */

/*
 * select all real providers
 */
$statementSelectProvider = $pdo->prepare('SELECT * FROM `real-provider`');
$statementSelectProvider->execute();

/*
 * Start for each provider
 */

while ($dbResultProvider = $statementSelectProvider->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {

    echo $dbResultProvider['proName'], PHP_EOL;
    
    $folder = $basePath . '/not-detected/' . $dbResultProvider['proName'];
    if (! file_exists($folder)) {
        mkdir($folder, 0777, true);
    }
    
    /*
     * no result found
     */
    $sql = "
        SELECT
            `result`.`resBrowserName` AS `name`,
            `userAgent`.`uaId`,
            `userAgent`.`uaString`,
            (
                SELECT
                    COUNT(`found-results`.`resBrowserName`)
                FROM `found-results`
                WHERE
                    `found-results`.`userAgent_id` = `userAgent`.`uaId`
                AND `found-results`.`provider_id` != `result`.`provider_id`
            ) AS `detectionCount`
        FROM `result`
        INNER JOIN `userAgent`
            ON `userAgent`.`uaId` = `result`.`userAgent_id`
        WHERE
            `result`.`provider_id` = :proId
            AND `result`.`resResultFound` = 0
    ";
    $statement = $pdo->prepare($sql);
    $statement->bindValue(':proId', $dbResultProvider['proId'], \PDO::PARAM_STR);

    $statement->execute();
    
    $generate = new SimpleList($pdo, 'Not detected - ' . $dbResultProvider['proName'] . ' <small>' . $dbResultProvider['proVersion'] . '</small>');
    $generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));
    
    file_put_contents($folder . '/no-result-found.html', $generate->getHtml());
    
    /*
     * browserName
     */
    if ($dbResultProvider['proCanDetectBrowserName']) {
        echo '.';
        
        $sql = "
            SELECT 
                `found-results`.`resBrowserName` AS `name`,
                `userAgent`.`uaId`,
                `userAgent`.`uaString`,
                (
                    SELECT
                        COUNT(`list-found-general-browser-names`.`resBrowserName`)
                    FROM `list-found-general-browser-names`
                    WHERE 
                        `list-found-general-browser-names`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-browser-names`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionCount`,
                (
                    SELECT
                        COUNT(DISTINCT `list-found-general-browser-names`.`resBrowserName`)
                    FROM `list-found-general-browser-names`
                    WHERE 
                        `list-found-general-browser-names`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-browser-names`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionCountUnique`,
                (
                    SELECT
                        GROUP_CONCAT(DISTINCT `list-found-general-browser-names`.`resBrowserName`)
                    FROM `list-found-general-browser-names`
                    WHERE 
                        `list-found-general-browser-names`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-browser-names`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionValuesDistinct`
            FROM `found-results`
            INNER JOIN `userAgent`
                ON `userAgent`.`uaId` = `found-results`.`userAgent_id`
            WHERE
                `found-results`.`provider_id` = :proId
                AND `found-results`.`resBotIsBot` IS NULL
                AND `found-results`.`resBrowserName` IS NULL
        ";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':proId', $dbResultProvider['proId'], \PDO::PARAM_STR);

        $statement->execute();
        
        $generate = new SimpleList($pdo, 'No browser name found - ' . $dbResultProvider['proName'] . ' <small>' . $dbResultProvider['proVersion'] . '</small>');
        $generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));
        
        file_put_contents($folder . '/browser-names.html', $generate->getHtml());
    }
    
    /*
     * renderingEngine
     */
    if ($dbResultProvider['proCanDetectEngineName']) {
        echo '.';
        
        $sql = "
            SELECT
                `found-results`.`resEngineName` AS `name`,
                `userAgent`.`uaId`,
                `userAgent`.`uaString`,
                (
                    SELECT
                        COUNT(`list-found-general-engine-names`.`resEngineName`)
                    FROM `list-found-general-engine-names`
                    WHERE 
                        `list-found-general-engine-names`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-engine-names`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionCount`,
                (
                    SELECT
                        COUNT(DISTINCT `list-found-general-engine-names`.`resEngineName`)
                    FROM `list-found-general-engine-names`
                    WHERE 
                        `list-found-general-engine-names`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-engine-names`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionCountUnique`,
                (
                    SELECT
                        GROUP_CONCAT(DISTINCT `list-found-general-engine-names`.`resEngineName`)
                    FROM `list-found-general-engine-names`
                    WHERE 
                        `list-found-general-engine-names`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-engine-names`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionValuesDistinct`
            FROM `found-results`
            INNER JOIN `userAgent`
                ON `userAgent`.`uaId` = `found-results`.`userAgent_id`
            WHERE
                `found-results`.`provider_id` = :proId
                AND `found-results`.`resBotIsBot` IS NULL
                AND `found-results`.`resEngineName` IS NULL
        ";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':proId', $dbResultProvider['proId'], \PDO::PARAM_STR);

        $statement->execute();
        
        $generate = new SimpleList($pdo, 'No rendering engine found - ' . $dbResultProvider['proName'] . ' <small>' . $dbResultProvider['proVersion'] . '</small>');
        $generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));
        
        file_put_contents($folder . '/rendering-engines.html', $generate->getHtml());
    }
    
    /*
     * OSname
     */
    if ($dbResultProvider['proCanDetectOsName']) {
        echo '.';
        
        $sql = "
            SELECT
                `found-results`.`resOsName` AS `name`,
                `userAgent`.`uaId`,
                `userAgent`.`uaString`,
                (
                    SELECT
                        COUNT(`list-found-general-os-names`.`resOsName`)
                    FROM `list-found-general-os-names`
                    WHERE 
                        `list-found-general-os-names`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-os-names`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionCount`,
                (
                    SELECT
                        COUNT(DISTINCT `list-found-general-os-names`.`resOsName`)
                    FROM `list-found-general-os-names`
                    WHERE 
                        `list-found-general-os-names`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-os-names`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionCountUnique`,
                (
                    SELECT
                        GROUP_CONCAT(DISTINCT `list-found-general-os-names`.`resOsName`)
                    FROM `list-found-general-os-names`
                    WHERE 
                        `list-found-general-os-names`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-os-names`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionValuesDistinct`
            FROM `found-results`
            INNER JOIN `userAgent`
                ON `userAgent`.`uaId` = `found-results`.`userAgent_id`
            WHERE
                `found-results`.`provider_id` = :proId
                AND `resBotIsBot` IS NULL
                AND `resOsName` IS NULL
        ";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':proId', $dbResultProvider['proId'], \PDO::PARAM_STR);

        $statement->execute();
    
        $generate = new SimpleList($pdo, 'No operating system found - ' . $dbResultProvider['proName'] . ' <small>' . $dbResultProvider['proVersion'] . '</small>');
        $generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));
    
        file_put_contents($folder . '/operating-systems.html', $generate->getHtml());
    }

    /*
     * deviceBrand
     */
    if ($dbResultProvider['proCanDetectDeviceBrand']) {
        echo '.';

        $sql = "
            SELECT
                `found-results`.`resDeviceBrand` AS `name`,
                `userAgent`.`uaId`,
                `userAgent`.`uaString`,
                (
                    SELECT
                        COUNT(`list-found-general-device-brands`.`resDeviceBrand`)
                    FROM `list-found-general-device-brands`
                    WHERE 
                        `list-found-general-device-brands`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-device-brands`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionCount`,
                (
                    SELECT
                        COUNT(DISTINCT `list-found-general-device-brands`.`resDeviceBrand`)
                    FROM `list-found-general-device-brands`
                    WHERE 
                        `list-found-general-device-brands`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-device-brands`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionCountUnique`,
                (
                    SELECT
                        GROUP_CONCAT(DISTINCT `list-found-general-device-brands`.`resDeviceBrand`)
                    FROM `list-found-general-device-brands`
                    WHERE 
                        `list-found-general-device-brands`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-device-brands`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionValuesDistinct`
            FROM `found-results`
            INNER JOIN `userAgent`
                ON `userAgent`.`uaId` = `found-results`.`userAgent_id`
            WHERE
                `found-results`.`provider_id` = :proId
                AND `found-results`.`resBotIsBot` IS NULL
                AND `found-results`.`resDeviceBrand` IS NULL
        ";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':proId', $dbResultProvider['proId'], \PDO::PARAM_STR);

        $statement->execute();

        $generate = new SimpleList($pdo, 'No device brands found - ' . $dbResultProvider['proName'] . ' <small>' . $dbResultProvider['proVersion'] . '</small>');
        $generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));

        file_put_contents($folder . '/device-brands.html', $generate->getHtml());
    }
    
    /*
     * deviceModel
     */
    if ($dbResultProvider['proCanDetectDeviceModel']) {
        echo '.';
        
        $sql = "
            SELECT
                `found-results`.`resDeviceModel` AS `name`,
                `userAgent`.`uaId`,
                `userAgent`.`uaString`,
                (
                    SELECT
                        COUNT(`list-found-general-device-models`.`resDeviceModel`)
                    FROM `list-found-general-device-models`
                    WHERE 
                        `list-found-general-device-models`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-device-models`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionCount`,
                (
                    SELECT
                        COUNT(DISTINCT `list-found-general-device-models`.`resDeviceModel`)
                    FROM `list-found-general-device-models`
                    WHERE 
                        `list-found-general-device-models`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-device-models`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionCountUnique`,
                (
                    SELECT
                        GROUP_CONCAT(DISTINCT `list-found-general-device-models`.`resDeviceModel`)
                    FROM `list-found-general-device-models`
                    WHERE 
                        `list-found-general-device-models`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-device-models`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionValuesDistinct`
            FROM `found-results`
            INNER JOIN `userAgent`
                ON `userAgent`.`uaId` = `found-results`.`userAgent_id`
            WHERE
                `found-results`.`provider_id` = :proId
                AND `found-results`.`resBotIsBot` IS NULL
                AND `found-results`.`resDeviceModel` IS NULL
        ";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':proId', $dbResultProvider['proId'], \PDO::PARAM_STR);

        $statement->execute();
    
        $generate = new SimpleList($pdo, 'No device model found - ' . $dbResultProvider['proName'] . ' <small>' . $dbResultProvider['proVersion'] . '</small>');
        $generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));
    
        file_put_contents($folder . '/device-models.html', $generate->getHtml());
    }
    
    /*
     * deviceTypes
     */
    if ($dbResultProvider['proCanDetectDeviceType']) {
        echo '.';
        
        $sql = "
            SELECT
                `found-results`.`resDeviceType` AS `name`,
                `userAgent`.`uaId`,
                `userAgent`.`uaString`,
                (
                    SELECT
                        COUNT(`list-found-general-device-types`.`resDeviceType`)
                    FROM `list-found-general-device-types`
                    WHERE 
                        `list-found-general-device-types`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-device-types`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionCount`,
                (
                    SELECT
                        COUNT(DISTINCT `list-found-general-device-types`.`resDeviceType`)
                    FROM `list-found-general-device-types`
                    WHERE 
                        `list-found-general-device-types`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-device-types`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionCountUnique`,
                (
                    SELECT
                        GROUP_CONCAT(DISTINCT `list-found-general-device-types`.`resDeviceType`)
                    FROM `list-found-general-device-types`
                    WHERE 
                        `list-found-general-device-types`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-device-types`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionValuesDistinct`
            FROM `found-results`
            INNER JOIN `userAgent`
                ON `userAgent`.`uaId` = `found-results`.`userAgent_id`
            WHERE
                `found-results`.`provider_id` = :proId
                AND `found-results`.`resBotIsBot` IS NULL
                AND `found-results`.`resDeviceType` IS NULL
        ";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':proId', $dbResultProvider['proId'], \PDO::PARAM_STR);

        $statement->execute();
    
        $generate = new SimpleList($pdo, 'No device type found - ' . $dbResultProvider['proName'] . ' <small>' . $dbResultProvider['proVersion'] . '</small>');
        $generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));
    
        file_put_contents($folder . '/device-types.html', $generate->getHtml());
    }
    
    /*
     * not detected as mobile
     */
    if ($dbResultProvider['proCanDetectDeviceIsMobile']) {
        echo '.';
    
        $sql = "
            SELECT
                `found-results`.`resBotName` AS `name`,
                `userAgent`.`uaId`,
                `userAgent`.`uaString`,
                (
                    SELECT
                        COUNT(`list-found-general-device-ismobile`.`resBotName`)
                    FROM `list-found-general-device-ismobile`
                    WHERE
                        `list-found-general-device-ismobile`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-device-ismobile`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionCount`
            FROM `found-results`
            INNER JOIN `userAgent`
                ON `userAgent`.`uaId` = `found-results`.`userAgent_id`
            WHERE
                `found-results`.`provider_id` = :proId
                AND `found-results`.`resDeviceIsMobile` IS NULL
                AND `userAgent`.`uaId` IN(
                    SELECT
                        `result`.`userAgent_id`
                    FROM `test-provider`
                    INNER JOIN `result`
                        ON `result`.`provider_id` = `test-provider`.`proId`
                        AND `result`.`resDeviceIsMobile` = 1
                )
        ";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':proId', $dbResultProvider['proId'], \PDO::PARAM_STR);

        $statement->execute();
    
        $generate = new SimpleList($pdo, 'Not detected as mobile - ' . $dbResultProvider['proName'] . ' <small>' . $dbResultProvider['proVersion'] . '</small>');
        $generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));
    
        file_put_contents($folder . '/device-is-mobile.html', $generate->getHtml());
    }
    
    /*
     * not detected as bot
     */
    if ($dbResultProvider['proCanDetectBotIsBot']) {
        echo '.';
    
        $sql = "
            SELECT
            	`found-results`.`resBotName` AS `name`,
            	`userAgent`.`uaId`,
            	`userAgent`.`uaString`,
            	(
            		SELECT
            			COUNT(`list-found-general-bot-isbot`.`resBotName`)
            		FROM `list-found-general-bot-isbot`
                    WHERE
            			`list-found-general-bot-isbot`.`userAgent_id` = `userAgent`.`uaId`
            			AND `list-found-general-bot-isbot`.`provider_id` != `found-results`.`provider_id`
                ) as `detectionCount`
            FROM `found-results`
            INNER JOIN `userAgent`
                ON `userAgent`.`uaId` = `found-results`.`userAgent_id`
            WHERE
            	`found-results`.`provider_id` = :proId
                AND `found-results`.`resBotIsBot` IS NULL
        	    AND `userAgent`.`uaId` IN(
            		SELECT
                        `result`.`userAgent_id`
                    FROM `test-provider`
                    INNER JOIN `result`
                        ON `result`.`provider_id` = `test-provider`.`proId`
            			AND `result`.`resBotIsBot` = 1
                )
        ";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':proId', $dbResultProvider['proId'], \PDO::PARAM_STR);

        $statement->execute();
    
        $generate = new SimpleList($pdo, 'Not detected as bot - ' . $dbResultProvider['proName'] . ' <small>' . $dbResultProvider['proVersion'] . '</small>');
        $generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));
    
        file_put_contents($folder . '/bot-is-bot.html', $generate->getHtml());
    }
    
    /*
     * botNames
     */
    if ($dbResultProvider['proCanDetectBotName']) {
        echo '.';
        
        $sql = "
            SELECT
                `found-results`.`resBotName` AS `name`,
                `userAgent`.`uaId`,
                `userAgent`.`uaString`,
                (
                    SELECT
                        COUNT(`list-found-general-bot-names`.`resBotName`)
                    FROM `list-found-general-bot-names`
                    WHERE
                        `list-found-general-bot-names`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-bot-names`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionCount`,
                (
                    SELECT
                        COUNT(DISTINCT `list-found-general-bot-names`.`resBotName`)
                    FROM `list-found-general-bot-names`
                    WHERE 
                        `list-found-general-bot-names`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-bot-names`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionCountUnique`,
                (
                    SELECT
                        GROUP_CONCAT(DISTINCT `list-found-general-bot-names`.`resBotName`)
                    FROM `list-found-general-bot-names`
                    WHERE 
                        `list-found-general-bot-names`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-bot-names`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionValuesDistinct`
            FROM `found-results`
            INNER JOIN `userAgent`
                ON `userAgent`.`uaId` = `found-results`.`userAgent_id`
            WHERE
                `found-results`.`provider_id` = :proId
                AND `found-results`.`resBotName` IS NULL
                AND `userAgent`.`uaId` IN(
                    SELECT
                        `result`.`userAgent_id`
                    FROM `test-provider`
                    INNER JOIN `result` 
                        ON `result`.`provider_id` = `test-provider`.`proId`
                        AND `result`.`resBotName` IS NOT NULL
                )
        ";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':proId', $dbResultProvider['proId'], \PDO::PARAM_STR);

        $statement->execute();
    
        $generate = new SimpleList($pdo, 'No bot name found - ' . $dbResultProvider['proName'] . ' <small>' . $dbResultProvider['proVersion'] . '</small>');
        $generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));
    
        file_put_contents($folder . '/bot-names.html', $generate->getHtml());
    }
    
    /*
     * botTypes
     */
    if ($dbResultProvider['proCanDetectBotType']) {
        echo '.';
        
        $sql = "
            SELECT
                `found-results`.`resBotType` AS `name`,
                `userAgent`.`uaId`,
                `userAgent`.`uaString`,
                (
                    SELECT
                        COUNT(`list-found-general-bot-types`.`resBotType`)
                    FROM `list-found-general-bot-types`
                    WHERE
                        `list-found-general-bot-types`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-bot-types`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionCount`,
                (
                    SELECT
                        COUNT(DISTINCT `list-found-general-bot-types`.`resBotType`)
                    FROM `list-found-general-bot-types`
                    WHERE 
                        `list-found-general-bot-types`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-bot-types`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionCountUnique`,
                (
                    SELECT
                        GROUP_CONCAT(DISTINCT `list-found-general-bot-types`.`resBotType`)
                    FROM `list-found-general-bot-types`
                    WHERE 
                         `list-found-general-bot-types`.`userAgent_id` = `userAgent`.`uaId`
                        AND `list-found-general-bot-types`.`provider_id` != `found-results`.`provider_id`
                ) AS `detectionValuesDistinct`
            FROM `found-results`
            INNER JOIN `userAgent`
                ON `userAgent`.`uaId` = `found-results`.`userAgent_id`
            WHERE
                `found-results`.`provider_id` = :proId
                AND `found-results`.`resBotType` IS NULL
                AND `userAgent`.`uaId` IN(
                    SELECT
                        `result`.`userAgent_id`
                    FROM `test-provider`
                    INNER JOIN `result` 
                        ON `result`.`provider_id` = `test-provider`.`proId`
                        AND `result`.`resBotType` IS NOT NULL
                )
        ";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':proId', $dbResultProvider['proId'], \PDO::PARAM_STR);

        $statement->execute();
    
        $generate = new SimpleList($pdo, 'No bot type found - ' . $dbResultProvider['proName'] . ' <small>' . $dbResultProvider['proVersion'] . '</small>');
        $generate->setElements($statement->fetchAll(\PDO::FETCH_ASSOC));
    
        file_put_contents($folder . '/bot-types.html', $generate->getHtml());
    }
    
    echo PHP_EOL;
}
