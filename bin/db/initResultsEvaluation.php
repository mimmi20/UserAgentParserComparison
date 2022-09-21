<?php
use UserAgentParserComparison\Evaluation\ResultsPerUserAgent;
use UserAgentParserComparison\Entity\UserAgentEvaluation;
use UserAgentParserComparison\Entity\ResultEvaluation;
use UserAgentParserComparison\Evaluation\ResultsPerProviderResult;
use Ramsey\Uuid\Uuid;

include 'bootstrap.php';

echo 'prepare loading..' . PHP_EOL;

/* @var $pdo \PDO */

$statementCreateTempResults = $pdo->prepare('CREATE TEMPORARY TABLE IF NOT EXISTS `temp_result` AS (SELECT * FROM `result` ORDER BY `userAgent_id` LIMIT :start, :count)');

$sql = "
        SELECT
            GROUP_CONCAT(`resBrowserName` SEPARATOR '~~~') AS `browserName`,
    GROUP_CONCAT(`resBrowserVersion` SEPARATOR '~~~') AS `browserVersion`,
    
    GROUP_CONCAT(`resEngineName` SEPARATOR '~~~') AS `engineName`,
    GROUP_CONCAT(`resEngineVersion` SEPARATOR '~~~') AS `engineVersion`,
    
    GROUP_CONCAT(`resOsName` SEPARATOR '~~~') AS `osName`,
    GROUP_CONCAT(`resOsVersion` SEPARATOR '~~~') AS `osVersion`,
    
    GROUP_CONCAT(`resDeviceModel` SEPARATOR '~~~') AS `deviceModel`,
    GROUP_CONCAT(`resDeviceBrand` SEPARATOR '~~~') AS `deviceBrand`,
    GROUP_CONCAT(`resDeviceType` SEPARATOR '~~~') AS `deviceType`,
    
    IFNULL(SUM(`resDeviceIsMobile`), 0) AS `deviceIsMobileCount`,
    IFNULL(SUM(`resDeviceIsTouch`), 0) AS `deviceIsTouchCount`,
    
    IFNULL(SUM(`resBotIsBot`), 0) AS `isBotCount`,
    
    GROUP_CONCAT(`resBotName` SEPARATOR '~~~') AS `botName`,
    GROUP_CONCAT(`resBotType` SEPARATOR '~~~') AS `botType`
        FROM `result`
        WHERE
            `userAgent_id` = :uaId
            AND `provider_id` != :proId
        GROUP BY 
            `userAgent_id`
    ";

$statementSelectResultsByAgent = $pdo->prepare($sql);

$statementSelectResultEvaluation = $pdo->prepare('SELECT * FROM `resultEvaluation` WHERE `result_id` = :resId');

$statementInsertResultEvaluation   = $pdo->prepare('INSERT INTO `resultevaluation` (`result_id`, `revId`, `lastChangeDate`, `browserNameSameResult`, `browserNameHarmonizedSameResult`, `browserVersionSameResult`, `browserVersionHarmonizedSameResult`, `engineNameSameResult`, `engineNameHarmonizedSameResult`, `engineVersionSameResult`, `engineVersionHarmonizedSameResult`, `osNameSameResult`, `osNameHarmonizedSameResult`, `osVersionSameResult`, `osVersionHarmonizedSameResult`, `deviceModelSameResult`, `deviceModelHarmonizedSameResult`, `deviceBrandSameResult`, `deviceBrandHarmonizedSameResult`, `deviceTypeSameResult`, `deviceTypeHarmonizedSameResult`, `asMobileDetectedByOthers`, `asTouchDetectedByOthers`, `asBotDetectedByOthers`, `botNameSameResult`, `botNameHarmonizedSameResult`, `botTypeSameResult`, `botTypeHarmonizedSameResult`) VALUES (:resId, :revId, :lastChangeDate, :browserNameSameResult, :browserNameHarmonizedSameResult, :browserVersionSameResult, :browserVersionHarmonizedSameResult, :engineNameSameResult, :engineNameHarmonizedSameResult, :engineVersionSameResult, :engineVersionHarmonizedSameResult, :osNameSameResult, :osNameHarmonizedSameResult, :osVersionSameResult, :osVersionHarmonizedSameResult, :deviceModelSameResult, :deviceModelHarmonizedSameResult, :deviceBrandSameResult, :deviceBrandHarmonizedSameResult, :deviceTypeSameResult, :deviceTypeHarmonizedSameResult, :asMobileDetectedByOthers, :asTouchDetectedByOthers, :asBotDetectedByOthers, :botNameSameResult, :botNameHarmonizedSameResult, :botTypeSameResult, :botTypeHarmonizedSameResult)');
$statementUpdateResultEvaluation   = $pdo->prepare('UPDATE `resultevaluation` SET `result_id` = :resId, `lastChangeDate` = :lastChangeDate, `browserNameSameResult` = :browserNameSameResult, `browserNameHarmonizedSameResult` = :browserNameHarmonizedSameResult, `browserVersionSameResult` = :browserVersionSameResult, `browserVersionHarmonizedSameResult` = :browserVersionHarmonizedSameResult, `engineNameSameResult` = :engineNameSameResult, `engineNameHarmonizedSameResult` = :engineNameHarmonizedSameResult, `engineVersionSameResult` = :engineVersionSameResult, `engineVersionHarmonizedSameResult` = :engineVersionHarmonizedSameResult, `osNameSameResult` = :osNameSameResult, `osNameHarmonizedSameResult` = :osNameHarmonizedSameResult, `osVersionSameResult` = :osVersionSameResult, `osVersionHarmonizedSameResult` = :osVersionHarmonizedSameResult, `deviceModelSameResult` = :deviceModelSameResult, `deviceModelHarmonizedSameResult` = :deviceModelHarmonizedSameResult, `deviceBrandSameResult` = :deviceBrandSameResult, `deviceBrandHarmonizedSameResult` = :deviceBrandHarmonizedSameResult, `deviceTypeSameResult` = :deviceTypeSameResult, `deviceTypeHarmonizedSameResult` = :deviceTypeHarmonizedSameResult, `asMobileDetectedByOthers` = :asMobileDetectedByOthers, `asTouchDetectedByOthers` = :asTouchDetectedByOthers, `asBotDetectedByOthers` = :asBotDetectedByOthers, `botNameSameResult` = :botNameSameResult, `botNameHarmonizedSameResult` = :botNameHarmonizedSameResult, `botTypeSameResult` = :botTypeSameResult, `botTypeHarmonizedSameResult` = :botTypeHarmonizedSameResult WHERE `revId` = :revId');

echo 'start loading..' . PHP_EOL;

$count    = 1000;
$start    = 0;
$colCount = $count;
$currenUserAgent = 0;

do {
    $pdo->prepare('DROP TEMPORARY TABLE IF EXISTS `temp_result`')->execute();

    $statementCreateTempResults->bindValue(':start', $start, \PDO::PARAM_INT);
    $statementCreateTempResults->bindValue(':count', $count, \PDO::PARAM_INT);

    $statementCreateTempResults->execute();

    $statementSelectAllResults = $pdo->prepare('SELECT * FROM `temp_result` ORDER BY `userAgent_id`');
    $statementSelectAllResults->execute();

    $pdo->beginTransaction();

    while ($row = $statementSelectAllResults->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {
        ++$currenUserAgent;

        $statementSelectResultsByAgent->bindValue(':uaId', $row['userAgent_id'], \PDO::PARAM_STR);
        $statementSelectResultsByAgent->bindValue(':proId', $row['provider_id'], \PDO::PARAM_STR);

        $statementSelectResultsByAgent->execute();

        $dbResultResult = $statementSelectResultsByAgent->fetch(\PDO::FETCH_ASSOC);

        if (false === $dbResultResult) {
            echo 'E - Count: ' . str_pad((string) $currenUserAgent, 8, ' ', STR_PAD_LEFT);

            continue;
        }

        /*
         * Check if already inserted
         */
        $statementSelectResultEvaluation->bindValue(':resId', $row['resId'], \PDO::PARAM_STR);

        $statementSelectResultEvaluation->execute();

        $dbResultResultEvaluation = $statementSelectResultEvaluation->fetch(\PDO::FETCH_ASSOC);

        if (false !== $dbResultResultEvaluation) {
            // skip date is greater (generated after last result)
            if ($dbResultResultEvaluation['lastChangeDate'] >= $row['resLastChangeDate']) {
                echo 'S - Count: ' . str_pad((string) $currenUserAgent, 8, ' ', STR_PAD_LEFT);

                continue;
            }

            $row2 = $dbResultResultEvaluation;

            // so go update!
        } else {
            // create
            $row2 = [
                'result_id' => $row['resId']
            ];
        }

        $date = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $row2['lastChangeDate'] = $date->format('Y-m-d H:i:s');

        $row2 = hydrateResult($row2, $row, $dbResultResult);

        if (! isset($row2['revId'])) {
            $statementInsertResultEvaluation->bindValue(':resId', $row2['result_id'], \PDO::PARAM_STR);
            $statementInsertResultEvaluation->bindValue(':revId', Uuid::uuid4()->toString(), \PDO::PARAM_STR);
            $statementInsertResultEvaluation->bindValue(':lastChangeDate', $date->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
            $statementInsertResultEvaluation->bindValue(':browserNameSameResult', $row2['browserNameSameResult'], \PDO::PARAM_INT);
            $statementInsertResultEvaluation->bindValue(':browserNameHarmonizedSameResult', $row2['browserNameHarmonizedSameResult'], \PDO::PARAM_INT);
            $statementInsertResultEvaluation->bindValue(':browserVersionSameResult', $row2['browserVersionSameResult'], \PDO::PARAM_INT);
            $statementInsertResultEvaluation->bindValue(':browserVersionHarmonizedSameResult', $row2['browserVersionHarmonizedSameResult'], \PDO::PARAM_INT);
            $statementInsertResultEvaluation->bindValue(':engineNameSameResult', $row2['engineNameSameResult'], \PDO::PARAM_INT);
            $statementInsertResultEvaluation->bindValue(':engineNameHarmonizedSameResult', $row2['engineNameHarmonizedSameResult'], \PDO::PARAM_INT);
            $statementInsertResultEvaluation->bindValue(':engineVersionSameResult', $row2['engineVersionSameResult'], \PDO::PARAM_INT);
            $statementInsertResultEvaluation->bindValue(':engineVersionHarmonizedSameResult', $row2['engineVersionHarmonizedSameResult'], \PDO::PARAM_INT);
            $statementInsertResultEvaluation->bindValue(':osNameSameResult', $row2['osNameSameResult'], \PDO::PARAM_INT);
            $statementInsertResultEvaluation->bindValue(':osNameHarmonizedSameResult', $row2['osNameHarmonizedSameResult'], \PDO::PARAM_INT);
            $statementInsertResultEvaluation->bindValue(':osVersionSameResult', $row2['osVersionSameResult'], \PDO::PARAM_INT);
            $statementInsertResultEvaluation->bindValue(':osVersionHarmonizedSameResult', $row2['osVersionHarmonizedSameResult'], \PDO::PARAM_INT);
            $statementInsertResultEvaluation->bindValue(':deviceModelSameResult', $row2['deviceModelSameResult'], \PDO::PARAM_INT);
            $statementInsertResultEvaluation->bindValue(':deviceModelHarmonizedSameResult', $row2['deviceModelHarmonizedSameResult'], \PDO::PARAM_INT);
            $statementInsertResultEvaluation->bindValue(':deviceBrandSameResult', $row2['deviceBrandSameResult'], \PDO::PARAM_INT);
            $statementInsertResultEvaluation->bindValue(':deviceBrandHarmonizedSameResult', $row2['deviceBrandHarmonizedSameResult'], \PDO::PARAM_INT);
            $statementInsertResultEvaluation->bindValue(':deviceTypeSameResult', $row2['deviceTypeSameResult'], \PDO::PARAM_INT);
            $statementInsertResultEvaluation->bindValue(':deviceTypeHarmonizedSameResult', $row2['deviceTypeHarmonizedSameResult'], \PDO::PARAM_INT);
            $statementInsertResultEvaluation->bindValue(':asMobileDetectedByOthers', $row2['asMobileDetectedByOthers'], \PDO::PARAM_INT);
            $statementInsertResultEvaluation->bindValue(':asTouchDetectedByOthers', $row2['asTouchDetectedByOthers'], \PDO::PARAM_INT);
            $statementInsertResultEvaluation->bindValue(':asBotDetectedByOthers', $row2['asBotDetectedByOthers'], \PDO::PARAM_INT);
            $statementInsertResultEvaluation->bindValue(':botNameSameResult', $row2['botNameSameResult'], \PDO::PARAM_INT);
            $statementInsertResultEvaluation->bindValue(':botNameHarmonizedSameResult', $row2['botNameHarmonizedSameResult'], \PDO::PARAM_INT);
            $statementInsertResultEvaluation->bindValue(':botTypeSameResult', $row2['botTypeSameResult'], \PDO::PARAM_INT);
            $statementInsertResultEvaluation->bindValue(':botTypeHarmonizedSameResult', $row2['botTypeHarmonizedSameResult'], \PDO::PARAM_INT);

            $statementInsertResultEvaluation->execute();

            echo 'I - Count: ' . str_pad((string) $currenUserAgent, 8, ' ', STR_PAD_LEFT);
        } else {
            $statementUpdateResultEvaluation->bindValue(':resId', $row2['result_id'], \PDO::PARAM_STR);
            $statementUpdateResultEvaluation->bindValue(':revId', Uuid::uuid4()->toString(), \PDO::PARAM_STR);
            $statementUpdateResultEvaluation->bindValue(':lastChangeDate', $date->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
            $statementUpdateResultEvaluation->bindValue(':browserNameSameResult', $row2['browserNameSameResult'], \PDO::PARAM_INT);
            $statementUpdateResultEvaluation->bindValue(':browserNameHarmonizedSameResult', $row2['browserNameHarmonizedSameResult'], \PDO::PARAM_INT);
            $statementUpdateResultEvaluation->bindValue(':browserVersionSameResult', $row2['browserVersionSameResult'], \PDO::PARAM_INT);
            $statementUpdateResultEvaluation->bindValue(':browserVersionHarmonizedSameResult', $row2['browserVersionHarmonizedSameResult'], \PDO::PARAM_INT);
            $statementUpdateResultEvaluation->bindValue(':engineNameSameResult', $row2['engineNameSameResult'], \PDO::PARAM_INT);
            $statementUpdateResultEvaluation->bindValue(':engineNameHarmonizedSameResult', $row2['engineNameHarmonizedSameResult'], \PDO::PARAM_INT);
            $statementUpdateResultEvaluation->bindValue(':engineVersionSameResult', $row2['engineVersionSameResult'], \PDO::PARAM_INT);
            $statementUpdateResultEvaluation->bindValue(':engineVersionHarmonizedSameResult', $row2['engineVersionHarmonizedSameResult'], \PDO::PARAM_INT);
            $statementUpdateResultEvaluation->bindValue(':osNameSameResult', $row2['osNameSameResult'], \PDO::PARAM_INT);
            $statementUpdateResultEvaluation->bindValue(':osNameHarmonizedSameResult', $row2['osNameHarmonizedSameResult'], \PDO::PARAM_INT);
            $statementUpdateResultEvaluation->bindValue(':osVersionSameResult', $row2['osVersionSameResult'], \PDO::PARAM_INT);
            $statementUpdateResultEvaluation->bindValue(':osVersionHarmonizedSameResult', $row2['osVersionHarmonizedSameResult'], \PDO::PARAM_INT);
            $statementUpdateResultEvaluation->bindValue(':deviceModelSameResult', $row2['deviceModelSameResult'], \PDO::PARAM_INT);
            $statementUpdateResultEvaluation->bindValue(':deviceModelHarmonizedSameResult', $row2['deviceModelHarmonizedSameResult'], \PDO::PARAM_INT);
            $statementUpdateResultEvaluation->bindValue(':deviceBrandSameResult', $row2['deviceBrandSameResult'], \PDO::PARAM_INT);
            $statementUpdateResultEvaluation->bindValue(':deviceBrandHarmonizedSameResult', $row2['deviceBrandHarmonizedSameResult'], \PDO::PARAM_INT);
            $statementUpdateResultEvaluation->bindValue(':deviceTypeSameResult', $row2['deviceTypeSameResult'], \PDO::PARAM_INT);
            $statementUpdateResultEvaluation->bindValue(':deviceTypeHarmonizedSameResult', $row2['deviceTypeHarmonizedSameResult'], \PDO::PARAM_INT);
            $statementUpdateResultEvaluation->bindValue(':asMobileDetectedByOthers', $row2['asMobileDetectedByOthers'], \PDO::PARAM_INT);
            $statementUpdateResultEvaluation->bindValue(':asTouchDetectedByOthers', $row2['asTouchDetectedByOthers'], \PDO::PARAM_INT);
            $statementUpdateResultEvaluation->bindValue(':asBotDetectedByOthers', $row2['asBotDetectedByOthers'], \PDO::PARAM_INT);
            $statementUpdateResultEvaluation->bindValue(':botNameSameResult', $row2['botNameSameResult'], \PDO::PARAM_INT);
            $statementUpdateResultEvaluation->bindValue(':botNameHarmonizedSameResult', $row2['botNameHarmonizedSameResult'], \PDO::PARAM_INT);
            $statementUpdateResultEvaluation->bindValue(':botTypeSameResult', $row2['botTypeSameResult'], \PDO::PARAM_INT);
            $statementUpdateResultEvaluation->bindValue(':botTypeHarmonizedSameResult', $row2['botTypeHarmonizedSameResult'], \PDO::PARAM_INT);

            $statementUpdateResultEvaluation->execute();

            echo 'U - Count: ' . str_pad((string) $currenUserAgent, 8, ' ', STR_PAD_LEFT);
        }
    }

    $pdo->commit();

    $statementCountAllResults = $pdo->prepare('SELECT COUNT(*) AS `count` FROM `temp_result`');
    $statementCountAllResults->execute();

    $colCount = $statementCountAllResults->fetch(\PDO::FETCH_COLUMN);

    $pdo->prepare('DROP TEMPORARY TABLE IF EXISTS `temp_result`')->execute();

    $start = $start + $count;
} while ($colCount > 0);

function hydrateResult(array $row2, array $row, array $resultGrouped): array
{
    /*
     * Browser name
     */
    $evaluate = new ResultsPerProviderResult();
    $evaluate->setCurrentValue($row['resBrowserName']);
    $evaluate->setValue($resultGrouped['browserName']);
    $evaluate->setType('browserName');
    $evaluate->evaluate();
    
    $row2['browserNameSameResult'] = $evaluate->getSameResultCount();
    $row2['browserNameHarmonizedSameResult'] = $evaluate->getHarmonizedSameResultCount();
    
    /*
     * Browser version
    */
    $evaluate = new ResultsPerProviderResult();
    $evaluate->setCurrentValue($row['resBrowserVersion']);
    $evaluate->setValue($resultGrouped['browserVersion']);
    $evaluate->setType('version');
    $evaluate->evaluate();
    
    $row2['browserVersionSameResult'] = $evaluate->getSameResultCount();
    $row2['browserVersionHarmonizedSameResult'] = $evaluate->getHarmonizedSameResultCount();
    
    /*
     * Engine name
    */
    $evaluate = new ResultsPerProviderResult();
    $evaluate->setCurrentValue($row['resEngineName']);
    $evaluate->setValue($resultGrouped['engineName']);
    $evaluate->setType('engineName');
    $evaluate->evaluate();
    
    $row2['engineNameSameResult'] = $evaluate->getSameResultCount();
    $row2['engineNameHarmonizedSameResult'] = $evaluate->getHarmonizedSameResultCount();
    
    /*
     * Engine version
    */
    $evaluate = new ResultsPerProviderResult();
    $evaluate->setCurrentValue($row['resEngineVersion']);
    $evaluate->setValue($resultGrouped['engineVersion']);
    $evaluate->setType('version');
    $evaluate->evaluate();
    
    $row2['engineVersionSameResult'] = $evaluate->getSameResultCount();
    $row2['engineVersionHarmonizedSameResult'] = $evaluate->getHarmonizedSameResultCount();
    
    /*
     * Os name
    */
    $evaluate = new ResultsPerProviderResult();
    $evaluate->setCurrentValue($row['resOsName']);
    $evaluate->setValue($resultGrouped['osName']);
    $evaluate->setType('osName');
    $evaluate->evaluate();
    
    $row2['osNameSameResult'] = $evaluate->getSameResultCount();
    $row2['osNameHarmonizedSameResult'] = $evaluate->getHarmonizedSameResultCount();
    
    /*
     * Os version
    */
    $evaluate = new ResultsPerProviderResult();
    $evaluate->setCurrentValue($row['resOsVersion']);
    $evaluate->setValue($resultGrouped['osVersion']);
    $evaluate->setType('version');
    $evaluate->evaluate();
    
    $row2['osVersionSameResult'] = $evaluate->getSameResultCount();
    $row2['osVersionHarmonizedSameResult'] = $evaluate->getHarmonizedSameResultCount();
    
    /*
     * deviceModel
    */
    $evaluate = new ResultsPerProviderResult();
    $evaluate->setCurrentValue($row['resDeviceModel']);
    $evaluate->setValue($resultGrouped['deviceModel']);
    $evaluate->setType('deviceModel');
    $evaluate->evaluate();
    
    $row2['deviceModelSameResult'] = $evaluate->getSameResultCount();
    $row2['deviceModelHarmonizedSameResult'] = $evaluate->getHarmonizedSameResultCount();
    
    /*
     * deviceBrand
    */
    $evaluate = new ResultsPerProviderResult();
    $evaluate->setCurrentValue($row['resDeviceBrand']);
    $evaluate->setValue($resultGrouped['deviceBrand']);
    $evaluate->setType('deviceBrand');
    $evaluate->evaluate();
    
    $row2['deviceBrandSameResult'] = $evaluate->getSameResultCount();
    $row2['deviceBrandHarmonizedSameResult'] = $evaluate->getHarmonizedSameResultCount();
    
    /*
     * deviceType
    */
    $evaluate = new ResultsPerProviderResult();
    $evaluate->setCurrentValue($row['resDeviceType']);
    $evaluate->setValue($resultGrouped['deviceType']);
    $evaluate->setType('deviceType');
    $evaluate->evaluate();
    
    $row2['deviceTypeSameResult'] = $evaluate->getSameResultCount();
    $row2['deviceTypeHarmonizedSameResult'] = $evaluate->getHarmonizedSameResultCount();
    
    /*
     * Detected as mobile
    */
    $row2['asMobileDetectedByOthers'] = (int) $resultGrouped['deviceIsMobileCount'];
    
    /*
     * Detected as touch
    */
    $row2['asTouchDetectedByOthers'] = (int) $resultGrouped['deviceIsTouchCount'];
    
    /*
     * Detected as bot
    */
    $row2['asBotDetectedByOthers'] = (int) $resultGrouped['isBotCount'];
    
    /*
     * botName
    */
    $evaluate = new ResultsPerProviderResult();
    $evaluate->setCurrentValue($row['resBotName']);
    $evaluate->setValue($resultGrouped['botName']);
    $evaluate->setType('botName');
    $evaluate->evaluate();
    
    $row2['botNameSameResult'] = $evaluate->getSameResultCount();
    $row2['botNameHarmonizedSameResult'] = $evaluate->getHarmonizedSameResultCount();
    
    /*
     * botType
    */
    $evaluate = new ResultsPerProviderResult();
    $evaluate->setCurrentValue($row['resBotType']);
    $evaluate->setValue($resultGrouped['botType']);
    $evaluate->setType('botType');
    $evaluate->evaluate();
    
    $row2['botTypeSameResult'] = $evaluate->getSameResultCount();
    $row2['botTypeHarmonizedSameResult'] = $evaluate->getHarmonizedSameResultCount();
    
    return $row2;
}
