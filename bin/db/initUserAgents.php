<?php

declare(strict_types = 1);

use Ramsey\Uuid\Uuid;
use UserAgentParserComparison\Provider;
use UserAgentParserComparison\Provider\Test\AbstractTestProvider;

include 'bootstrap.php';

$skipUserAgents = [
    '',
    ' ',
    '-',
    '(){ :; }; :(){ :|:& };:',
    'this user agent should never exist hopefully as it is is only used in browscap tests',
    'SomethingWeNeverKnewExisted',
    'X-WebBrowser',
    'Ploetz + Zeller (http://www.ploetz-zeller.de) Link Validator v1.0 (support@p-und-z.de) for ARIS Business Architect',
];

/** @var PDO $pdo */

$statementSelectProvider = $pdo->prepare('SELECT * FROM `test-provider` WHERE `proName` = :proName');
$statementInsertProvider = $pdo->prepare('INSERT INTO `provider` (`proId`, `proType`, `proName`, `proHomepage`, `proVersion`, `proLastReleaseDate`, `proPackageName`, `proLanguage`, `proLocal`, `proApi`, `proCanDetectBrowserName`, `proCanDetectBrowserVersion`, `proCanDetectEngineName`, `proCanDetectEngineVersion`, `proCanDetectOsName`, `proCanDetectOsVersion`, `proCanDetectDeviceModel`, `proCanDetectDeviceBrand`, `proCanDetectDeviceType`, `proCanDetectDeviceIsMobile`, `proCanDetectDeviceIsTouch`, `proCanDetectBotIsBot`, `proCanDetectBotName`, `proCanDetectBotType`) VALUES (:proId, :proType, :proName, :proHomepage, :proVersion, :proLastReleaseDate, :proPackageName, :proLanguage, :proLocal, :proApi, :proCanDetectBrowserName, :proCanDetectBrowserVersion, :proCanDetectEngineName, :proCanDetectEngineVersion, :proCanDetectOsName, :proCanDetectOsVersion, :proCanDetectDeviceModel, :proCanDetectDeviceBrand, :proCanDetectDeviceType, :proCanDetectDeviceIsMobile, :proCanDetectDeviceIsTouch, :proCanDetectBotIsBot, :proCanDetectBotName, :proCanDetectBotType)');
$statementUpdateProvider = $pdo->prepare('UPDATE `provider` SET `proType` = :proType, `proName` = :proName, `proHomepage` = :proHomepage, `proVersion` = :proVersion, `proLastReleaseDate` = :proLastReleaseDate, `proPackageName` = :proPackageName, `proLanguage` = :proLanguage, `proLocal` = :proLocal, `proApi` = :proApi, `proCanDetectBrowserName` = :proCanDetectBrowserName, `proCanDetectBrowserVersion` = :proCanDetectBrowserVersion, `proCanDetectEngineName` = :proCanDetectEngineName, `proCanDetectEngineVersion` = :proCanDetectEngineVersion, `proCanDetectOsName` = :proCanDetectOsName, `proCanDetectOsVersion` = :proCanDetectOsVersion, `proCanDetectDeviceModel` = :proCanDetectDeviceModel, `proCanDetectDeviceBrand` = :proCanDetectDeviceBrand, `proCanDetectDeviceType` = :proCanDetectDeviceType, `proCanDetectDeviceIsMobile` = :proCanDetectDeviceIsMobile, `proCanDetectDeviceIsTouch` = :proCanDetectDeviceIsTouch, `proCanDetectBotIsBot` = :proCanDetectBotIsBot, `proCanDetectBotName` = :proCanDetectBotName, `proCanDetectBotType` = :proCanDetectBotType WHERE `proId` = :proId');

$statementSelectUa = $pdo->prepare('SELECT * FROM `userAgent` WHERE `uaHash` = :uaHash');
$statementInsertUa = $pdo->prepare('INSERT INTO `useragent` (`uaId`, `uaHash`, `uaString`, `uaAdditionalHeaders`) VALUES (:uaId, :uaHash, :uaString, :uaAdditionalHeaders)');
$statementUpdateUa = $pdo->prepare('UPDATE `useragent` SET `uaHash` = :uaHash, `uaString` = :uaString, `uaAdditionalHeaders` = :uaAdditionalHeaders WHERE `uaId` = :uaId');

$statementSelectResult = $pdo->prepare('SELECT * FROM `result` WHERE `provider_id` = :proId AND `userAgent_id` = :uaId');
$statementInsertResult = $pdo->prepare('INSERT INTO `result` (`provider_id`, `userAgent_id`, `resId`, `resProviderVersion`, `resFilename`, `resParseTime`, `resLastChangeDate`, `resResultFound`, `resBrowserName`, `resBrowserVersion`, `resEngineName`, `resEngineVersion`, `resOsName`, `resOsVersion`, `resDeviceModel`, `resDeviceBrand`, `resDeviceType`, `resDeviceIsMobile`, `resDeviceIsTouch`, `resBotIsBot`, `resBotName`, `resBotType`, `resRawResult`) VALUES (:proId, :uaId, :resId, :resProviderVersion, :resFilename, :resParseTime, :resLastChangeDate, :resResultFound, :resBrowserName, :resBrowserVersion, :resEngineName, :resEngineVersion, :resOsName, :resOsVersion, :resDeviceModel, :resDeviceBrand, :resDeviceType, :resDeviceIsMobile, :resDeviceIsTouch, :resBotIsBot, :resBotName, :resBotType, :resRawResult)');
$statementUpdateResult = $pdo->prepare('UPDATE `result` SET `provider_id` = :proId, `userAgent_id` = :uaId, `resProviderVersion` = :resProviderVersion, `resFilename` = :resFilename, `resParseTime` = :resParseTime, `resLastChangeDate` = :resLastChangeDate, `resResultFound` = :resResultFound, `resBrowserName` = :resBrowserName, `resBrowserVersion` = :resBrowserVersion, `resEngineName` = :resEngineName, `resEngineVersion` = :resEngineVersion, `resOsName` = :resOsName, `resOsVersion` = :resOsVersion, `resDeviceModel` = :resDeviceModel, `resDeviceBrand` = :resDeviceBrand, `resDeviceType` = :resDeviceType, `resDeviceIsMobile` = :resDeviceIsMobile, `resDeviceIsTouch` = :resDeviceIsTouch, `resBotIsBot` = :resBotIsBot, `resBotName` = :resBotName, `resBotType` = :resBotType, `resRawResult` = :resRawResult WHERE `resId` = :resId');

/*
 * Grab the userAgents!
 */
echo '~~~ Load all Useragents ~~~' . PHP_EOL;

$chain = new Provider\Chain([
    new Provider\Test\Cbschuld(),
    new Provider\Test\Donatj(),
    new Provider\Test\MobileDetect(),
    new Provider\Test\Sinergi(),
    new Provider\Test\Endorphin(),
    new Provider\Test\UaParserJs(),
    new Provider\Test\WhichBrowser(),
    new Provider\Test\Woothee(),
    new Provider\Test\ZsxSoft(),
    new Provider\Test\CrawlerDetect(),
    new Provider\Test\Browscap(),
    new Provider\Test\Matomo(),
    // new Provider\Test\BrowserDetector(),
]);

$proType = 'testSuite';

foreach ($chain->getProviders() as $provider) {
    assert($provider instanceof AbstractTestProvider);

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

    echo $proName . PHP_EOL;

    $statementSelectProvider->bindValue(':proName', $proName, PDO::PARAM_STR);

    $statementSelectProvider->execute();

    $proId = null;

    $dbResultProvider = $statementSelectProvider->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT);

    if (is_array($dbResultProvider)) {
        // update!
        $proId = $dbResultProvider['proId'];

        $statementUpdateProvider->bindValue(':proId', $proId, PDO::PARAM_STR);
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

        echo 'U';
    } else {
        $proId = Uuid::uuid4()->toString();

        $statementInsertProvider->bindValue(':proId', $proId, PDO::PARAM_STR);
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

        echo 'I';
    }

    echo PHP_EOL;

    $updated = 0;
    $inserted = 0;
    $skipped = 0;
    $summary = 0;

    /*
     * Useragents
     */
    foreach ($provider->getTests() as $uaHash => $row) {
        ++$summary;

        echo sprintf(
            "\r" . '%s updated, %s inserted, %s skipped',
            str_pad((string) $updated, 7, ' ', STR_PAD_LEFT),
            str_pad((string) $inserted, 7, ' ', STR_PAD_LEFT),
            str_pad((string) $skipped, 7, ' ', STR_PAD_LEFT)
        );

        if (in_array($row['uaString'], $skipUserAgents, true)) {
            ++$skipped;

            continue;
        }

        /*
         * insert UA itself
         */
        $statementSelectUa->bindValue(':uaHash', $uaHash, PDO::PARAM_STR);

        $statementSelectUa->execute();

        $dbResultUa = $statementSelectUa->fetch(PDO::FETCH_ASSOC);

        if (false !== $dbResultUa) {
            // update!
            $uaId = $dbResultUa['uaId'];

            if (isset($row['uaAdditionalHeaders'])) {
                $statementUpdateUa->bindValue(':uaId', $uaId, PDO::PARAM_STR);
                $statementUpdateUa->bindValue(':uaHash', $uaHash, PDO::PARAM_STR);
                $statementUpdateUa->bindValue(':uaString', $row['uaString'], PDO::PARAM_STR);
                $statementUpdateUa->bindValue(':uaAdditionalHeaders', json_encode($row['uaAdditionalHeaders']));

                $statementUpdateUa->execute();
            }
        } else {
            $uaId = Uuid::uuid4()->toString();

            $additionalHeaders = null;

            if (isset($row['uaAdditionalHeaders'])) {
                $additionalHeaders = json_encode($row['uaAdditionalHeaders']);
            }

            $statementInsertUa->bindValue(':uaId', $uaId, PDO::PARAM_STR);
            $statementInsertUa->bindValue(':uaHash', $uaHash, PDO::PARAM_STR);
            $statementInsertUa->bindValue(':uaString', $row['uaString'], PDO::PARAM_STR);
            $statementInsertUa->bindValue(':uaAdditionalHeaders', $additionalHeaders);

            $statementInsertUa->execute();
        }

        /*
         * Result
         */
        $res = $row['result'];

        $date = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $statementSelectResult->bindValue(':proId', $proId, PDO::PARAM_STR);
        $statementSelectResult->bindValue(':uaId', $uaId, PDO::PARAM_STR);

        $statementSelectResult->execute();

        $dbResultResult = $statementSelectResult->fetch(PDO::FETCH_ASSOC);

        if (is_array($dbResultResult)) {
            // update!
            $statementUpdateResult->bindValue(':resId', $dbResultResult['resId'], PDO::PARAM_STR);
            $statementUpdateResult->bindValue(':proId', $proId, PDO::PARAM_STR);
            $statementUpdateResult->bindValue(':uaId', $uaId, PDO::PARAM_STR);
            $statementUpdateResult->bindValue(':resProviderVersion', $proVersion, PDO::PARAM_STR);

            if (array_key_exists('resFilename', $res)) {
                $statementUpdateResult->bindValue(':resFilename', str_replace('\\', '/', $res['resFilename']));
            } else {
                $statementUpdateResult->bindValue(':resFilename', null);
            }

            $statementUpdateResult->bindValue(':resParseTime', null);
            $statementUpdateResult->bindValue(':resLastChangeDate', $date->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $statementUpdateResult->bindValue(':resResultFound', 1, PDO::PARAM_INT);

            if (array_key_exists('resBrowserName', $res) && !in_array($res['resBrowserName'], ['UNKNOWN', 'unknown', ''], true)) {
                $statementUpdateResult->bindValue(':resBrowserName', $res['resBrowserName']);
            } else {
                $statementUpdateResult->bindValue(':resBrowserName', null);
            }

            if (array_key_exists('resBrowserVersion', $res) && !in_array($res['resBrowserVersion'], ['UNKNOWN', 'unknown', '0.0', ''], true)) {
                $statementUpdateResult->bindValue(':resBrowserVersion', $res['resBrowserVersion']);
            } else {
                $statementUpdateResult->bindValue(':resBrowserVersion', null);
            }

            if (array_key_exists('resEngineName', $res) && !in_array($res['resEngineName'], ['UNKNOWN', 'unknown', ''], true)) {
                $statementUpdateResult->bindValue(':resEngineName', $res['resEngineName']);
            } else {
                $statementUpdateResult->bindValue(':resEngineName', null);
            }

            if (array_key_exists('resEngineVersion', $res) && !in_array($res['resEngineVersion'], ['UNKNOWN', 'unknown', '0.0', ''], true)) {
                $statementUpdateResult->bindValue(':resEngineVersion', $res['resEngineVersion']);
            } else {
                $statementUpdateResult->bindValue(':resEngineVersion', null);
            }

            if (array_key_exists('resOsName', $res) && !in_array($res['resOsName'], ['UNKNOWN', 'unknown', ''], true)) {
                $statementUpdateResult->bindValue(':resOsName', $res['resOsName']);
            } else {
                $statementUpdateResult->bindValue(':resOsName', null);
            }

            if (array_key_exists('resOsVersion', $res) && !in_array($res['resOsVersion'], ['UNKNOWN', 'unknown', '0.0', ''], true)) {
                $statementUpdateResult->bindValue(':resOsVersion', $res['resOsVersion']);
            } else {
                $statementUpdateResult->bindValue(':resOsVersion', null);
            }

            if (array_key_exists('resDeviceModel', $res) && !in_array($res['resDeviceModel'], ['UNKNOWN', 'unknown', ''], true)) {
                $statementUpdateResult->bindValue(':resDeviceModel', $res['resDeviceModel']);
            } else {
                $statementUpdateResult->bindValue(':resDeviceModel', null);
            }

            if (array_key_exists('resDeviceBrand', $res) && !in_array($res['resDeviceBrand'], ['UNKNOWN', 'unknown', ''], true)) {
                $statementUpdateResult->bindValue(':resDeviceBrand', $res['resDeviceBrand']);
            } else {
                $statementUpdateResult->bindValue(':resDeviceBrand', null);
            }

            if (array_key_exists('resDeviceType', $res) && !in_array($res['resDeviceType'], ['UNKNOWN', 'unknown', ''], true)) {
                $statementUpdateResult->bindValue(':resDeviceType', $res['resDeviceType']);
            } else {
                $statementUpdateResult->bindValue(':resDeviceType', null);
            }

            $statementUpdateResult->bindValue(':resDeviceIsMobile', $res['resDeviceIsMobile'] ?? null);
            $statementUpdateResult->bindValue(':resDeviceIsTouch', $res['resDeviceIsTouch'] ?? null);
            $statementUpdateResult->bindValue(':resBotIsBot', $res['resBotIsBot'] ?? null);

            if (array_key_exists('resBotName', $res) && !in_array($res['resBotName'], ['UNKNOWN', 'unknown', ''], true)) {
                $statementUpdateResult->bindValue(':resBotName', $res['resBotName']);
            } else {
                $statementUpdateResult->bindValue(':resBotName', null);
            }

            if (array_key_exists('resBotType', $res) && !in_array($res['resBotType'], ['UNKNOWN', 'unknown', ''], true)) {
                $statementUpdateResult->bindValue(':resBotType', $res['resBotType']);
            } else {
                $statementUpdateResult->bindValue(':resBotType', null);
            }

            if (array_key_exists('resRawResult', $res) && !in_array($res['resRawResult'], ['UNKNOWN', 'unknown', ''], true)) {
                $statementUpdateResult->bindValue(':resRawResult', $res['resRawResult']);
            } else {
                $statementUpdateResult->bindValue(':resRawResult', null);
            }

            $statementUpdateResult->execute();

            ++$updated;

            continue;
        }

        $statementInsertResult->bindValue(':resId', Uuid::uuid4()->toString(), PDO::PARAM_STR);
        $statementInsertResult->bindValue(':proId', $proId, PDO::PARAM_STR);
        $statementInsertResult->bindValue(':uaId', $uaId, PDO::PARAM_STR);
        $statementInsertResult->bindValue(':resProviderVersion', $proVersion, PDO::PARAM_STR);

        if (array_key_exists('resFilename', $res)) {
            $statementInsertResult->bindValue(':resFilename', str_replace('\\', '/', $res['resFilename']));
        } else {
            $statementInsertResult->bindValue(':resFilename', null);
        }

        $statementInsertResult->bindValue(':resParseTime', null);
        $statementInsertResult->bindValue(':resLastChangeDate', $date->format('Y-m-d H:i:s'), PDO::PARAM_STR);
        $statementInsertResult->bindValue(':resResultFound', 1, PDO::PARAM_INT);

        if (array_key_exists('resBrowserName', $res) && !in_array($res['resBrowserName'], ['UNKNOWN', 'unknown', ''], true)) {
            $statementInsertResult->bindValue(':resBrowserName', $res['resBrowserName']);
        } else {
            $statementInsertResult->bindValue(':resBrowserName', null);
        }

        if (array_key_exists('resBrowserVersion', $res) && !in_array($res['resBrowserVersion'], ['UNKNOWN', 'unknown', '0.0', ''], true)) {
            $statementInsertResult->bindValue(':resBrowserVersion', $res['resBrowserVersion']);
        } else {
            $statementInsertResult->bindValue(':resBrowserVersion', null);
        }

        if (array_key_exists('resEngineName', $res) && !in_array($res['resEngineName'], ['UNKNOWN', 'unknown', ''], true)) {
            $statementInsertResult->bindValue(':resEngineName', $res['resEngineName']);
        } else {
            $statementInsertResult->bindValue(':resEngineName', null);
        }

        if (array_key_exists('resEngineVersion', $res) && !in_array($res['resEngineVersion'], ['UNKNOWN', 'unknown', '0.0', ''], true)) {
            $statementInsertResult->bindValue(':resEngineVersion', $res['resEngineVersion']);
        } else {
            $statementInsertResult->bindValue(':resEngineVersion', null);
        }

        if (array_key_exists('resOsName', $res) && !in_array($res['resOsName'], ['UNKNOWN', 'unknown', ''], true)) {
            $statementInsertResult->bindValue(':resOsName', $res['resOsName']);
        } else {
            $statementInsertResult->bindValue(':resOsName', null);
        }

        if (array_key_exists('resOsVersion', $res) && !in_array($res['resOsVersion'], ['UNKNOWN', 'unknown', '0.0', ''], true)) {
            $statementInsertResult->bindValue(':resOsVersion', $res['resOsVersion']);
        } else {
            $statementInsertResult->bindValue(':resOsVersion', null);
        }

        if (array_key_exists('resDeviceModel', $res) && !in_array($res['resDeviceModel'], ['UNKNOWN', 'unknown', ''], true)) {
            $statementInsertResult->bindValue(':resDeviceModel', $res['resDeviceModel']);
        } else {
            $statementInsertResult->bindValue(':resDeviceModel', null);
        }

        if (array_key_exists('resDeviceBrand', $res) && !in_array($res['resDeviceBrand'], ['UNKNOWN', 'unknown', ''], true)) {
            $statementInsertResult->bindValue(':resDeviceBrand', $res['resDeviceBrand']);
        } else {
            $statementInsertResult->bindValue(':resDeviceBrand', null);
        }

        if (array_key_exists('resDeviceType', $res) && !in_array($res['resDeviceType'], ['UNKNOWN', 'unknown', ''], true)) {
            $statementInsertResult->bindValue(':resDeviceType', $res['resDeviceType']);
        } else {
            $statementInsertResult->bindValue(':resDeviceType', null);
        }

        $statementInsertResult->bindValue(':resDeviceIsMobile', $res['resDeviceIsMobile'] ?? null);
        $statementInsertResult->bindValue(':resDeviceIsTouch', $res['resDeviceIsTouch'] ?? null);
        $statementInsertResult->bindValue(':resBotIsBot', $res['resBotIsBot'] ?? null);

        if (array_key_exists('resBotName', $res) && !in_array($res['resBotName'], ['UNKNOWN', 'unknown', ''], true)) {
            $statementInsertResult->bindValue(':resBotName', $res['resBotName']);
        } else {
            $statementInsertResult->bindValue(':resBotName', null);
        }

        if (array_key_exists('resBotType', $res) && !in_array($res['resBotType'], ['UNKNOWN', 'unknown', ''], true)) {
            $statementInsertResult->bindValue(':resBotType', $res['resBotType']);
        } else {
            $statementInsertResult->bindValue(':resBotType', null);
        }

        if (array_key_exists('resRawResult', $res) && !in_array($res['resRawResult'], ['UNKNOWN', 'unknown', ''], true)) {
            $statementInsertResult->bindValue(':resRawResult', $res['resRawResult']);
        } else {
            $statementInsertResult->bindValue(':resRawResult', null);
        }

        $statementInsertResult->execute();

        ++$inserted;
    }

    echo sprintf(
        "\r" . '%s updated, %s inserted, %s skipped',
        str_pad((string) $updated, 7, ' ', STR_PAD_LEFT),
        str_pad((string) $inserted, 7, ' ', STR_PAD_LEFT),
        str_pad((string) $skipped, 7, ' ', STR_PAD_LEFT)
    );

    echo PHP_EOL . PHP_EOL;
}
