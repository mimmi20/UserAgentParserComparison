<?php

declare(strict_types = 1);

use Ramsey\Uuid\Uuid;
use UserAgentParserComparison\Provider\AbstractParseProvider;
use UserAgentParserComparison\Provider\Chain;

include 'bootstrap.php';

$chain = include 'bin/getChainProvider.php';
assert($chain instanceof Chain);

/** @var PDO $pdo */

$statementSelectProvider = $pdo->prepare('SELECT * FROM `real-provider` WHERE `proName` = :proName');
$statementInsertProvider = $pdo->prepare('INSERT INTO `provider` (`proId`, `proType`, `proName`, `proHomepage`, `proVersion`, `proLastReleaseDate`, `proPackageName`, `proLanguage`, `proLocal`, `proApi`, `proCanDetectBrowserName`, `proCanDetectBrowserVersion`, `proCanDetectEngineName`, `proCanDetectEngineVersion`, `proCanDetectOsName`, `proCanDetectOsVersion`, `proCanDetectDeviceModel`, `proCanDetectDeviceBrand`, `proCanDetectDeviceType`, `proCanDetectDeviceIsMobile`, `proCanDetectDeviceIsTouch`, `proCanDetectBotIsBot`, `proCanDetectBotName`, `proCanDetectBotType`) VALUES (:proId, :proType, :proName, :proHomepage, :proVersion, :proLastReleaseDate, :proPackageName, :proLanguage, :proLocal, :proApi, :proCanDetectBrowserName, :proCanDetectBrowserVersion, :proCanDetectEngineName, :proCanDetectEngineVersion, :proCanDetectOsName, :proCanDetectOsVersion, :proCanDetectDeviceModel, :proCanDetectDeviceBrand, :proCanDetectDeviceType, :proCanDetectDeviceIsMobile, :proCanDetectDeviceIsTouch, :proCanDetectBotIsBot, :proCanDetectBotName, :proCanDetectBotType)');
$statementUpdateProvider = $pdo->prepare('UPDATE `provider` SET `proType` = :proType, `proName` = :proName, `proHomepage` = :proHomepage, `proVersion` = :proVersion, `proLastReleaseDate` = :proLastReleaseDate, `proPackageName` = :proPackageName, `proLanguage` = :proLanguage, `proLocal` = :proLocal, `proApi` = :proApi, `proCanDetectBrowserName` = :proCanDetectBrowserName, `proCanDetectBrowserVersion` = :proCanDetectBrowserVersion, `proCanDetectEngineName` = :proCanDetectEngineName, `proCanDetectEngineVersion` = :proCanDetectEngineVersion, `proCanDetectOsName` = :proCanDetectOsName, `proCanDetectOsVersion` = :proCanDetectOsVersion, `proCanDetectDeviceModel` = :proCanDetectDeviceModel, `proCanDetectDeviceBrand` = :proCanDetectDeviceBrand, `proCanDetectDeviceType` = :proCanDetectDeviceType, `proCanDetectDeviceIsMobile` = :proCanDetectDeviceIsMobile, `proCanDetectDeviceIsTouch` = :proCanDetectDeviceIsTouch, `proCanDetectBotIsBot` = :proCanDetectBotIsBot, `proCanDetectBotName` = :proCanDetectBotName, `proCanDetectBotType` = :proCanDetectBotType WHERE `proId` = :proId');

echo '~~~ Load all Providers ~~~' . PHP_EOL;

$proType = 'real';

foreach ($chain->getProviders() as $provider) {
    assert($provider instanceof AbstractParseProvider);

    $capabilities               = $provider->getDetectionCapabilities();
    $proName                    = $provider->getName();
    $proHomepage                = $provider->getHomepage();
    $proVersion                 = $provider->getVersion();
    $proReleaseDate             = $provider->getUpdateDate();
    $proPackageName             = $provider->getPackageName();
    $proLanguage                = $provider->getLanguage();
    $proLocal                   = (int) $provider->isLocal();
    $proApi                     = (int) $provider->isApi();
    $proCanDetectBrowserName    = $capabilities['browser']['name'] ?? 0;
    $proCanDetectBrowserVersion = $capabilities['browser']['version'] ?? 0;
    $proCanDetectEngineName     = $capabilities['renderingEngine']['name'] ?? 0;
    $proCanDetectEngineVersion  = $capabilities['renderingEngine']['version'] ?? 0;
    $proCanDetectOsName         = $capabilities['operatingSystem']['name'] ?? 0;
    $proCanDetectOsVersion      = $capabilities['operatingSystem']['version'] ?? 0;
    $proCanDetectDeviceModel    = $capabilities['device']['model'] ?? 0;
    $proCanDetectDeviceBrand    = $capabilities['device']['brand'] ?? 0;
    $proCanDetectDeviceType     = $capabilities['device']['type'] ?? 0;
    $proCanDetectDeviceIsMobile = $capabilities['device']['isMobile'] ?? 0;
    $proCanDetectDeviceIsTouch  = $capabilities['device']['isTouch'] ?? 0;
    $proCanDetectBotIsBot       = $capabilities['bot']['isBot'] ?? 0;
    $proCanDetectBotName        = $capabilities['bot']['name'] ?? 0;
    $proCanDetectBotType        = $capabilities['bot']['type'] ?? 0;

    echo $proName, ': ';

    $statementSelectProvider->bindValue(':proName', $proName, PDO::PARAM_STR);

    $statementSelectProvider->execute();

    $found = false;

    while ($dbResultProvider = $statementSelectProvider->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT)) {
        // update!
        $statementUpdateProvider->bindValue(':proId', $dbResultProvider['proId'], PDO::PARAM_STR);
        $statementUpdateProvider->bindValue(':proType', $proType, PDO::PARAM_STR);
        $statementUpdateProvider->bindValue(':proName', $proName, PDO::PARAM_STR);
        $statementUpdateProvider->bindValue(':proHomepage', $proHomepage, PDO::PARAM_STR);
        $statementUpdateProvider->bindValue(':proVersion', $proVersion, PDO::PARAM_STR);
        if (null !== $proReleaseDate) {
            $statementUpdateProvider->bindValue(':proLastReleaseDate', $proReleaseDate->format('Y-m-d H:i:s'), PDO::PARAM_STR);
        } else {
            $statementUpdateProvider->bindValue(':proLastReleaseDate', null);
        }

        $statementUpdateProvider->bindValue(':proPackageName', $proPackageName, PDO::PARAM_STR);
        $statementUpdateProvider->bindValue(':proLanguage', $proLanguage, PDO::PARAM_STR);
        $statementUpdateProvider->bindValue(':proLocal', $proLocal, PDO::PARAM_INT);
        $statementUpdateProvider->bindValue(':proApi', $proApi, PDO::PARAM_INT);
        $statementUpdateProvider->bindValue(':proCanDetectBrowserName', $proCanDetectBrowserName, PDO::PARAM_INT);
        $statementUpdateProvider->bindValue(':proCanDetectBrowserVersion', $proCanDetectBrowserVersion, PDO::PARAM_INT);
        $statementUpdateProvider->bindValue(':proCanDetectEngineName', $proCanDetectEngineName, PDO::PARAM_INT);
        $statementUpdateProvider->bindValue(':proCanDetectEngineVersion', $proCanDetectEngineVersion, PDO::PARAM_INT);
        $statementUpdateProvider->bindValue(':proCanDetectOsName', $proCanDetectOsName, PDO::PARAM_INT);
        $statementUpdateProvider->bindValue(':proCanDetectOsVersion', $proCanDetectOsVersion, PDO::PARAM_INT);
        $statementUpdateProvider->bindValue(':proCanDetectDeviceModel', $proCanDetectDeviceModel, PDO::PARAM_INT);
        $statementUpdateProvider->bindValue(':proCanDetectDeviceBrand', $proCanDetectDeviceBrand, PDO::PARAM_INT);
        $statementUpdateProvider->bindValue(':proCanDetectDeviceType', $proCanDetectDeviceType, PDO::PARAM_INT);
        $statementUpdateProvider->bindValue(':proCanDetectDeviceIsMobile', $proCanDetectDeviceIsMobile, PDO::PARAM_INT);
        $statementUpdateProvider->bindValue(':proCanDetectDeviceIsTouch', $proCanDetectDeviceIsTouch, PDO::PARAM_INT);
        $statementUpdateProvider->bindValue(':proCanDetectBotIsBot', $proCanDetectBotIsBot, PDO::PARAM_INT);
        $statementUpdateProvider->bindValue(':proCanDetectBotName', $proCanDetectBotName, PDO::PARAM_INT);
        $statementUpdateProvider->bindValue(':proCanDetectBotType', $proCanDetectBotType, PDO::PARAM_INT);

        $statementUpdateProvider->execute();

        $found = true;

        echo 'U', PHP_EOL;

        break;
    }

    if ($found) {
        continue;
    }

    $statementInsertProvider->bindValue(':proId', Uuid::uuid4()->toString(), PDO::PARAM_STR);
    $statementInsertProvider->bindValue(':proType', $proType, PDO::PARAM_STR);
    $statementInsertProvider->bindValue(':proName', $proName, PDO::PARAM_STR);
    $statementInsertProvider->bindValue(':proHomepage', $proHomepage, PDO::PARAM_STR);
    $statementInsertProvider->bindValue(':proVersion', $proVersion, PDO::PARAM_STR);
    if (null !== $proReleaseDate) {
        $statementInsertProvider->bindValue(':proLastReleaseDate', $proReleaseDate->format('Y-m-d H:i:s'), PDO::PARAM_STR);
    } else {
        $statementInsertProvider->bindValue(':proLastReleaseDate', null);
    }

    $statementInsertProvider->bindValue(':proPackageName', $proPackageName, PDO::PARAM_STR);
    $statementInsertProvider->bindValue(':proLanguage', $proLanguage, PDO::PARAM_STR);
    $statementInsertProvider->bindValue(':proLocal', $proLocal, PDO::PARAM_INT);
    $statementInsertProvider->bindValue(':proApi', $proApi, PDO::PARAM_INT);
    $statementInsertProvider->bindValue(':proCanDetectBrowserName', $proCanDetectBrowserName, PDO::PARAM_INT);
    $statementInsertProvider->bindValue(':proCanDetectBrowserVersion', $proCanDetectBrowserVersion, PDO::PARAM_INT);
    $statementInsertProvider->bindValue(':proCanDetectEngineName', $proCanDetectEngineName, PDO::PARAM_INT);
    $statementInsertProvider->bindValue(':proCanDetectEngineVersion', $proCanDetectEngineVersion, PDO::PARAM_INT);
    $statementInsertProvider->bindValue(':proCanDetectOsName', $proCanDetectOsName, PDO::PARAM_INT);
    $statementInsertProvider->bindValue(':proCanDetectOsVersion', $proCanDetectOsVersion, PDO::PARAM_INT);
    $statementInsertProvider->bindValue(':proCanDetectDeviceModel', $proCanDetectDeviceModel, PDO::PARAM_INT);
    $statementInsertProvider->bindValue(':proCanDetectDeviceBrand', $proCanDetectDeviceBrand, PDO::PARAM_INT);
    $statementInsertProvider->bindValue(':proCanDetectDeviceType', $proCanDetectDeviceType, PDO::PARAM_INT);
    $statementInsertProvider->bindValue(':proCanDetectDeviceIsMobile', $proCanDetectDeviceIsMobile, PDO::PARAM_INT);
    $statementInsertProvider->bindValue(':proCanDetectDeviceIsTouch', $proCanDetectDeviceIsTouch, PDO::PARAM_INT);
    $statementInsertProvider->bindValue(':proCanDetectBotIsBot', $proCanDetectBotIsBot, PDO::PARAM_INT);
    $statementInsertProvider->bindValue(':proCanDetectBotName', $proCanDetectBotName, PDO::PARAM_INT);
    $statementInsertProvider->bindValue(':proCanDetectBotType', $proCanDetectBotType, PDO::PARAM_INT);

    $statementInsertProvider->execute();

    echo 'I', PHP_EOL;
}
