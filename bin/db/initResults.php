<?php
use UserAgentParserComparison\Exception;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Entity\Result;
use Ramsey\Uuid\Uuid;

include 'bootstrap.php';

/* @var $chain \UserAgentParserComparison\Provider\Chain */
$chain = include 'bin/getChainProvider.php';

/* @var $pdo \PDO */

$statementSelectProvider = $pdo->prepare('SELECT * FROM `real-provider` WHERE `proName` = :proName');

$statementCreateTempUas  = $pdo->prepare('CREATE TEMPORARY TABLE IF NOT EXISTS `temp_userAgent` AS (SELECT * FROM `userAgent` LIMIT :start, :count)');

$statementSelectResult   = $pdo->prepare('SELECT * FROM `result` WHERE `provider_id` = :proId AND `userAgent_id` = :uaId');
$statementInsertResult   = $pdo->prepare('INSERT INTO `result` (`provider_id`, `userAgent_id`, `resId`, `resProviderVersion`, `resFilename`, `resParseTime`, `resLastChangeDate`, `resResultFound`, `resBrowserName`, `resBrowserVersion`, `resEngineName`, `resEngineVersion`, `resOsName`, `resOsVersion`, `resDeviceModel`, `resDeviceBrand`, `resDeviceType`, `resDeviceIsMobile`, `resDeviceIsTouch`, `resBotIsBot`, `resBotName`, `resBotType`, `resRawResult`) VALUES (:proId, :uaId, :resId, :resProviderVersion, :resFilename, :resParseTime, :resLastChangeDate, :resResultFound, :resBrowserName, :resBrowserVersion, :resEngineName, :resEngineVersion, :resOsName, :resOsVersion, :resDeviceModel, :resDeviceBrand, :resDeviceType, :resDeviceIsMobile, :resDeviceIsTouch, :resBotIsBot, :resBotName, :resBotType, :resRawResult)');
$statementUpdateResult   = $pdo->prepare('UPDATE `result` SET `provider_id` = :proId, `userAgent_id` = :uaId, `resProviderVersion` = :resProviderVersion, `resFilename` = :resFilename, `resParseTime` = :resParseTime, `resLastChangeDate` = :resLastChangeDate, `resResultFound` = :resResultFound, `resBrowserName` = :resBrowserName, `resBrowserVersion` = :resBrowserVersion, `resEngineName` = :resEngineName, `resEngineVersion` = :resEngineVersion, `resOsName` = :resOsName, `resOsVersion` = :resOsVersion, `resDeviceModel` = :resDeviceModel, `resDeviceBrand` = :resDeviceBrand, `resDeviceType` = :resDeviceType, `resDeviceIsMobile` = :resDeviceIsMobile, `resDeviceIsTouch` = :resDeviceIsTouch, `resBotIsBot` = :resBotIsBot, `resBotName` = :resBotName, `resBotType` = :resBotType, `resRawResult` = :resRawResult WHERE `resId` = :resId');



/*
 * Load providers
 */
$providers  = [];
$nameLength = 0;

foreach ($chain->getProviders() as $provider) {
    $statementSelectProvider->bindValue(':proName', $provider->getName(), \PDO::PARAM_STR);

    $statementSelectProvider->execute();

    $found = false;

    while ($dbResultProvider = $statementSelectProvider->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {
        $found = true;

        break;
    }

    if (!$found) {
        throw new \Exception('no provider found with name: ' . $provider->getName());
    }

    $nameLength = max($nameLength, mb_strlen($provider->getName()));
    
    $providers[$provider->getName()] = $dbResultProvider;
}

echo 'load agents...' . PHP_EOL;

echo 'done loading..' . PHP_EOL;
echo 'detecting agents...' . PHP_EOL;

$currenUserAgent = 1;
$count           = 1000;
$start           = 0;
$colCount        = $count;
$providerCount   = count($chain->getProviders());
$baseMessage     = "\r";

do {
    $pdo->prepare('DROP TEMPORARY TABLE IF EXISTS `temp_userAgent`')->execute();

    $statementCreateTempUas->bindValue(':start', $start, \PDO::PARAM_INT);
    $statementCreateTempUas->bindValue(':count', $count, \PDO::PARAM_INT);

    $statementCreateTempUas->execute();

    $statementSelectAllResults = $pdo->prepare('SELECT * FROM `temp_userAgent`');
    $statementSelectAllResults->execute();

    /*
     * load userAgents...
     */
    $statementSelectAllUa = $pdo->prepare('SELECT * FROM `temp_userAgent`');
    $statementSelectAllUa->execute();

    $pdo->beginTransaction();

    while ($row = $statementSelectAllUa->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {
        $message = $baseMessage;

        foreach ($chain->getProviders() as $provider) {
            /* @var $provider \UserAgentParserComparison\Provider\AbstractParseProvider */

            $dbResultProvider = $providers[$provider->getName()];

            $statementSelectResult->bindValue(':proId', $dbResultProvider['proId'], \PDO::PARAM_STR);
            $statementSelectResult->bindValue(':uaId', $row['uaId'], \PDO::PARAM_STR);

            $statementSelectResult->execute();

            $dbResultResult = $statementSelectResult->fetch(\PDO::FETCH_ASSOC);

            if (false !== $dbResultResult) {
                $row2 = $dbResultResult;

                // skip
                if ($row['uaAdditionalHeaders'] === null && ($dbResultResult['resProviderVersion'] === $provider->getVersion() /* || $provider->getVersion() === null/**/)) {
                    $message .= 'S';

                    echo str_pad($message, $providerCount + 3) . ' - Count: ' . str_pad((string) $currenUserAgent, 8, ' ', STR_PAD_LEFT) . ' - ' . str_pad($provider->getName(), $nameLength);
                    continue;
                }
            } else {
                $row2 = [
                    'provider_id' => $dbResultProvider['proId'],
                    'userAgent_id' => $row['uaId']
                ];
            }

            $additionalHeaders = [];
            if ($row['uaAdditionalHeaders'] !== null) {
                $additionalHeaders = json_decode($row['uaAdditionalHeaders'], true);
            }

            /*
             * Get the result with timing
             */
            $startTime = microtime(true);

            try {
                $parseResult = $provider->parse($row['uaString'], $additionalHeaders);
            } catch (NoResultFoundException $ex) {
                $parseResult = null;
            } catch (\UserAgentParserComparison\Exception\RequestException $ex) {
                $message .= 'E';

                echo str_pad($message, $providerCount + 3) . ' - Count: ' . str_pad((string) $currenUserAgent, 8, ' ', STR_PAD_LEFT) . ' - ' . str_pad($provider->getName(), $nameLength);

                continue;
            }

            $endTime = microtime(true);

            $row2['resProviderVersion'] = $provider->getVersion();
            $row2['resParseTime'] = $endTime - $startTime;
            $date = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
            $row2['resLastChangeDate'] = $date->format('Y-m-d H:i:s');

            /*
             * Hydrate the result
             */
            if ($parseResult === null) {
                $row2['resResultFound'] = 0;
            } else {
                $row2['resResultFound'] = 1;

                $row2 = hydrateResult($row2, $parseResult);
            }

            /*
             * Persist
             */
            if (! isset($row2['resId'])) {
                $row2['resId'] = Uuid::uuid4()->toString();

                $statementInsertResult->bindValue(':resId', Uuid::uuid4()->toString(), \PDO::PARAM_STR);
                $statementInsertResult->bindValue(':proId', $dbResultProvider['proId'], \PDO::PARAM_STR);
                $statementInsertResult->bindValue(':uaId', $row['uaId'], \PDO::PARAM_STR);
                $statementInsertResult->bindValue(':resProviderVersion', $row2['resProviderVersion'], \PDO::PARAM_STR);

                if (array_key_exists('resFilename', $row)) {
                    $statementInsertResult->bindValue(':resFilename', str_replace('\\', '/', $row['resFilename']));
                } else {
                    $statementInsertResult->bindValue(':resFilename', null);
                }

                $statementInsertResult->bindValue(':resParseTime', $row2['resParseTime']);
                $statementInsertResult->bindValue(':resLastChangeDate', $row2['resLastChangeDate'], \PDO::PARAM_STR);
                $statementInsertResult->bindValue(':resResultFound', $row2['resResultFound'], \PDO::PARAM_INT);

                if (array_key_exists('resBrowserName', $row2) && !in_array($row2['resBrowserName'], ['UNKNOWN', 'unknown', ''], true)) {
                    $statementInsertResult->bindValue(':resBrowserName', $row2['resBrowserName']);
                } else {
                    $statementInsertResult->bindValue(':resBrowserName', null);
                }

                if (array_key_exists('resBrowserVersion', $row2) && !in_array($row2['resBrowserVersion'], ['UNKNOWN', 'unknown', '0.0', ''], true)) {
                    $statementInsertResult->bindValue(':resBrowserVersion', $row2['resBrowserVersion']);
                } else {
                    $statementInsertResult->bindValue(':resBrowserVersion', null);
                }

                if (array_key_exists('resEngineName', $row2) && !in_array($row2['resEngineName'], ['UNKNOWN', 'unknown', ''], true)) {
                    $statementInsertResult->bindValue(':resEngineName', $row2['resEngineName']);
                } else {
                    $statementInsertResult->bindValue(':resEngineName', null);
                }

                if (array_key_exists('resEngineVersion', $row2) && !in_array($row2['resEngineVersion'], ['UNKNOWN', 'unknown', '0.0', ''], true)) {
                    $statementInsertResult->bindValue(':resEngineVersion', $row2['resEngineVersion']);
                } else {
                    $statementInsertResult->bindValue(':resEngineVersion', null);
                }

                if (array_key_exists('resOsName', $row2) && !in_array($row2['resOsName'], ['UNKNOWN', 'unknown', ''], true)) {
                    $statementInsertResult->bindValue(':resOsName', $row2['resOsName']);
                } else {
                    $statementInsertResult->bindValue(':resOsName', null);
                }

                if (array_key_exists('resOsVersion', $row2) && !in_array($row2['resOsVersion'], ['UNKNOWN', 'unknown', '0.0', ''], true)) {
                    $statementInsertResult->bindValue(':resOsVersion', $row2['resOsVersion']);
                } else {
                    $statementInsertResult->bindValue(':resOsVersion', null);
                }

                if (array_key_exists('resDeviceModel', $row2) && !in_array($row2['resDeviceModel'], ['UNKNOWN', 'unknown', ''], true)) {
                    $statementInsertResult->bindValue(':resDeviceModel', $row2['resDeviceModel']);
                } else {
                    $statementInsertResult->bindValue(':resDeviceModel', null);
                }

                if (array_key_exists('resDeviceBrand', $row2) && !in_array($row2['resDeviceBrand'], ['UNKNOWN', 'unknown', ''], true)) {
                    $statementInsertResult->bindValue(':resDeviceBrand', $row2['resDeviceBrand']);
                } else {
                    $statementInsertResult->bindValue(':resDeviceBrand', null);
                }

                if (array_key_exists('resDeviceType', $row2) && !in_array($row2['resDeviceType'], ['UNKNOWN', 'unknown', ''], true)) {
                    $statementInsertResult->bindValue(':resDeviceType', $row2['resDeviceType']);
                } else {
                    $statementInsertResult->bindValue(':resDeviceType', null);
                }

                $statementInsertResult->bindValue(':resDeviceIsMobile', $row2['resDeviceIsMobile'] ?? null);
                $statementInsertResult->bindValue(':resDeviceIsTouch', $row2['resDeviceIsTouch'] ?? null);
                $statementInsertResult->bindValue(':resBotIsBot', $row2['resBotIsBot'] ?? null);

                if (array_key_exists('resBotName', $row2) && !in_array($row2['resBotName'], ['UNKNOWN', 'unknown', ''], true)) {
                    $statementInsertResult->bindValue(':resBotName', $row2['resBotName']);
                } else {
                    $statementInsertResult->bindValue(':resBotName', null);
                }

                if (array_key_exists('resBotType', $row2) && !in_array($row2['resBotType'], ['UNKNOWN', 'unknown', ''], true)) {
                    $statementInsertResult->bindValue(':resBotType', $row2['resBotType']);
                } else {
                    $statementInsertResult->bindValue(':resBotType', null);
                }

                if (array_key_exists('resRawResult', $row2) && !in_array($row2['resRawResult'], ['UNKNOWN', 'unknown', ''], true)) {
                    $statementInsertResult->bindValue(':resRawResult', $row2['resRawResult']);
                } else {
                    $statementInsertResult->bindValue(':resRawResult', null);
                }

                $statementInsertResult->execute();
            } else {
                $statementUpdateResult->bindValue(':resId', $dbResultResult['resId'], \PDO::PARAM_STR);
                $statementUpdateResult->bindValue(':proId', $dbResultProvider['proId'], \PDO::PARAM_STR);
                $statementUpdateResult->bindValue(':uaId', $row['uaId'], \PDO::PARAM_STR);
                $statementUpdateResult->bindValue(':resProviderVersion', $row2['resProviderVersion'], \PDO::PARAM_STR);

                if (array_key_exists('resFilename', $row)) {
                    $statementUpdateResult->bindValue(':resFilename', str_replace('\\', '/', $row['resFilename']));
                } else {
                    $statementUpdateResult->bindValue(':resFilename', null);
                }

                $statementUpdateResult->bindValue(':resParseTime', $row2['resParseTime']);
                $statementUpdateResult->bindValue(':resLastChangeDate', $row2['resLastChangeDate'], \PDO::PARAM_STR);
                $statementUpdateResult->bindValue(':resResultFound', $row2['resResultFound'], \PDO::PARAM_INT);

                if (array_key_exists('resBrowserName', $row2) && !in_array($row2['resBrowserName'], ['UNKNOWN', 'unknown', ''], true)) {
                    $statementUpdateResult->bindValue(':resBrowserName', $row2['resBrowserName']);
                } else {
                    $statementUpdateResult->bindValue(':resBrowserName', null);
                }

                if (array_key_exists('resBrowserVersion', $row2) && !in_array($row2['resBrowserVersion'], ['UNKNOWN', 'unknown', '0.0', ''], true)) {
                    $statementUpdateResult->bindValue(':resBrowserVersion', $row2['resBrowserVersion']);
                } else {
                    $statementUpdateResult->bindValue(':resBrowserVersion', null);
                }

                if (array_key_exists('resEngineName', $row2) && !in_array($row2['resEngineName'], ['UNKNOWN', 'unknown', ''], true)) {
                    $statementUpdateResult->bindValue(':resEngineName', $row2['resEngineName']);
                } else {
                    $statementUpdateResult->bindValue(':resEngineName', null);
                }

                if (array_key_exists('resEngineVersion', $row2) && !in_array($row2['resEngineVersion'], ['UNKNOWN', 'unknown', '0.0', ''], true)) {
                    $statementUpdateResult->bindValue(':resEngineVersion', $row2['resEngineVersion']);
                } else {
                    $statementUpdateResult->bindValue(':resEngineVersion', null);
                }

                if (array_key_exists('resOsName', $row2) && !in_array($row2['resOsName'], ['UNKNOWN', 'unknown', ''], true)) {
                    $statementUpdateResult->bindValue(':resOsName', $row2['resOsName']);
                } else {
                    $statementUpdateResult->bindValue(':resOsName', null);
                }

                if (array_key_exists('resOsVersion', $row2) && !in_array($row2['resOsVersion'], ['UNKNOWN', 'unknown', '0.0', ''], true)) {
                    $statementUpdateResult->bindValue(':resOsVersion', $row2['resOsVersion']);
                } else {
                    $statementUpdateResult->bindValue(':resOsVersion', null);
                }

                if (array_key_exists('resDeviceModel', $row2) && !in_array($row2['resDeviceModel'], ['UNKNOWN', 'unknown', ''], true)) {
                    $statementUpdateResult->bindValue(':resDeviceModel', $row2['resDeviceModel']);
                } else {
                    $statementUpdateResult->bindValue(':resDeviceModel', null);
                }

                if (array_key_exists('resDeviceBrand', $row2) && !in_array($row2['resDeviceBrand'], ['UNKNOWN', 'unknown', ''], true)) {
                    $statementUpdateResult->bindValue(':resDeviceBrand', $row2['resDeviceBrand']);
                } else {
                    $statementUpdateResult->bindValue(':resDeviceBrand', null);
                }

                if (array_key_exists('resDeviceType', $row2) && !in_array($row2['resDeviceType'], ['UNKNOWN', 'unknown', ''], true)) {
                    $statementUpdateResult->bindValue(':resDeviceType', $row2['resDeviceType']);
                } else {
                    $statementUpdateResult->bindValue(':resDeviceType', null);
                }

                $statementUpdateResult->bindValue(':resDeviceIsMobile', $row2['resDeviceIsMobile'] ?? null);
                $statementUpdateResult->bindValue(':resDeviceIsTouch', $row2['resDeviceIsTouch'] ?? null);
                $statementUpdateResult->bindValue(':resBotIsBot', $row2['resBotIsBot'] ?? null);

                if (array_key_exists('resBotName', $row2) && !in_array($row2['resBotName'], ['UNKNOWN', 'unknown', ''], true)) {
                    $statementUpdateResult->bindValue(':resBotName', $row2['resBotName']);
                } else {
                    $statementUpdateResult->bindValue(':resBotName', null);
                }

                if (array_key_exists('resBotType', $row2) && !in_array($row2['resBotType'], ['UNKNOWN', 'unknown', ''], true)) {
                    $statementUpdateResult->bindValue(':resBotType', $row2['resBotType']);
                } else {
                    $statementUpdateResult->bindValue(':resBotType', null);
                }

                if (array_key_exists('resRawResult', $row2) && !in_array($row2['resRawResult'], ['UNKNOWN', 'unknown', ''], true)) {
                    $statementUpdateResult->bindValue(':resRawResult', $row2['resRawResult']);
                } else {
                    $statementUpdateResult->bindValue(':resRawResult', null);
                }

                $statementUpdateResult->execute();
            }

            $message .= '.';

            echo str_pad($message, $providerCount + 3) . ' - Count: ' . str_pad((string) $currenUserAgent, 8, ' ', STR_PAD_LEFT) . ' - ' . str_pad($provider->getName(), $nameLength);
        }

        // display "progress"
        echo str_pad($message, $providerCount + 3) . ' - Count: ' . str_pad((string) $currenUserAgent, 8, ' ', STR_PAD_LEFT) . '   ' . str_pad(' ', $nameLength), PHP_EOL;

        $currenUserAgent++;
    }

    $pdo->commit();

    $statementCountAllResults = $pdo->prepare('SELECT COUNT(*) AS `count` FROM `temp_userAgent`');
    $statementCountAllResults->execute();

    $colCount = $statementCountAllResults->fetch(\PDO::FETCH_COLUMN);

    $pdo->prepare('DROP TEMPORARY TABLE IF EXISTS `temp_userAgent`')->execute();

    $start += $count;
} while ($colCount > 0);

function hydrateResult(array $row2, \UserAgentParserComparison\Model\UserAgent $result)
{
    $toHydrate = [
        'resBrowserName' => $result->getBrowser()->getName(),
        'resBrowserVersion' => $result->getBrowser()
            ->getVersion()
            ->getComplete(),
        
        'resEngineName' => $result->getRenderingEngine()->getName(),
        'resEngineVersion' => $result->getRenderingEngine()
            ->getVersion()
            ->getComplete(),
        
        'resOsName' => $result->getOperatingSystem()->getName(),
        'resOsVersion' => $result->getOperatingSystem()
            ->getVersion()
            ->getComplete(),
        
        'resDeviceModel' => $result->getDevice()->getModel(),
        'resDeviceBrand' => $result->getDevice()->getBrand(),
        'resDeviceType' => $result->getDevice()->getType(),
        'resDeviceIsMobile' => $result->getDevice()->getIsMobile(),
        'resDeviceIsTouch' => $result->getDevice()->getIsTouch(),
        
        'resBotIsBot' => $result->getBot()->getIsBot(),
        'resBotName' => $result->getBot()->getName(),
        'resBotType' => $result->getBot()->getType(),
        
        'resRawResult' => serialize($result->toArray(true)['providerResultRaw'])
    ];
    
    return array_merge($row2, $toHydrate);
}
