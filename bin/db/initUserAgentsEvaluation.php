<?php
use UserAgentParserComparison\Evaluation\ResultsPerUserAgent;
use UserAgentParserComparison\Entity\UserAgentEvaluation;
use Ramsey\Uuid\Uuid;

include 'bootstrap.php';

/* @var $entityManager \Doctrine\ORM\EntityManager */
$conn = $entityManager->getConnection();

$userAgentEvaluationRepo = $entityManager->getRepository('UserAgentParserComparison\Entity\UserAgentEvaluation');

$sql = "
SELECT 
    `userAgent_id`,
    COUNT(1) AS `resultCount`,
    SUM(`resResultFound`) AS `resultFound`,
    
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
    GROUP_CONCAT(`resBotType` SEPARATOR '~~~') AS `botType`,
    
    `result`.*
FROM `result`
GROUP BY `userAgent_id`
ORDER BY `userAgent_id`
";
$statement = $conn->prepare($sql);
$results   = $statement->executeQuery();

echo 'done loading..' . PHP_EOL;

$conn->beginTransaction();

$i = 1;
while ($row = $results->fetchAssociative()) {
    /*
     * Check if already inserted
     */
    $sql = "
        SELECT
            *
        FROM `userAgentEvaluation`
        WHERE
            `userAgent_id` = '" . $row['userAgent_id'] . "'
    ";

    $result = $conn->executeQuery($sql)->fetchAllAssociative();

    if (count($result) === 1) {
        $row2 = $result[0];
        
        // skip date is greater (generated after last result)
        if ($row2['lastChangeDate'] >= $row['resLastChangeDate']) {
            echo 'S';
            continue;
        }
        
        // so go update!
    } else {
        // create
        $row2 = [
            'userAgent_id' => $row['userAgent_id']
        ];
    }
    
    $date = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    $row2['lastChangeDate'] = $date->format('Y-m-d H:i:s');
    $row2['resultCount'] = $row['resultCount'];
    $row2['resultFound'] =$row['resultFound'];
    
    $row2 = hydrateResult($row2, $row);
    
    if (! isset($row2['uevId'])) {
        $row2['uevId'] = Uuid::uuid4()->toString();
    
        $conn->insert('userAgentEvaluation', $row2);
    } else {
        $conn->update('userAgentEvaluation', $row2, [
            'uevId' => $row2['uevId']
        ]);
    }
    
    echo '.';
    
    if ($i % 100 === 0) {
        $conn->commit();
    
        $conn->beginTransaction();
    }
    
    $i++;
}

if ($conn->getTransactionNestingLevel() !== 0) {
    $conn->commit();
}

function hydrateResult(array $row2, array $row): array
{
    /*
     * Browser name
     */
    $evaluate = new ResultsPerUserAgent();
    $evaluate->setValue($row['browserName']);
    $evaluate->setType('browserName');
    $evaluate->evaluate();
    
    $row2['browserNames'] = serialize($evaluate->getValues());
    $row2['browserNamesHarmonized'] = serialize($evaluate->getUniqueHarmonizedValues());
    $row2['browserNameFound'] = $evaluate->getFoundCount();
    $row2['browserNameFoundUnique'] = $evaluate->getFoundCountUnique();
    $row2['browserNameMaxSameResultCount'] = $evaluate->getMaxSameResultCount();
    $row2['browserNameHarmonizedFoundUnique'] = $evaluate->getHarmonizedFoundUnique();
    $row2['browserNameHarmonizedMaxSameResultCount'] = $evaluate->getHarmonizedMaxSameResultCount();
    
    /*
     * Browser version
     */
    $evaluate = new ResultsPerUserAgent();
    $evaluate->setValue($row['browserVersion']);
    $evaluate->setType('version');
    $evaluate->evaluate();
    
    $row2['browserVersions'] = serialize($evaluate->getValues());
    $row2['browserVersionsHarmonized'] = serialize($evaluate->getUniqueHarmonizedValues());
    $row2['browserVersionFound'] = $evaluate->getFoundCount();
    $row2['browserVersionFoundUnique'] = $evaluate->getFoundCountUnique();
    $row2['browserVersionMaxSameResultCount'] = $evaluate->getMaxSameResultCount();
    $row2['browserVersionHarmonizedFoundUnique'] = $evaluate->getHarmonizedFoundUnique();
    $row2['browserVersionHarmonizedMaxSameResultCount'] = $evaluate->getHarmonizedMaxSameResultCount();
    
    /*
     * Engine name
     */
    $evaluate = new ResultsPerUserAgent();
    $evaluate->setValue($row['engineName']);
    $evaluate->setType('engineName');
    $evaluate->evaluate();
    
    $row2['engineNames'] = serialize($evaluate->getValues());
    $row2['engineNamesHarmonized'] = serialize($evaluate->getUniqueHarmonizedValues());
    $row2['engineNameFound'] = $evaluate->getFoundCount();
    $row2['engineNameFoundUnique'] = $evaluate->getFoundCountUnique();
    $row2['engineNameMaxSameResultCount'] = $evaluate->getMaxSameResultCount();
    $row2['engineNameHarmonizedFoundUnique'] = $evaluate->getHarmonizedFoundUnique();
    $row2['engineNameHarmonizedMaxSameResultCount'] = $evaluate->getHarmonizedMaxSameResultCount();
    
    /*
     * Engine version
     */
    $evaluate = new ResultsPerUserAgent();
    $evaluate->setValue($row['engineVersion']);
    $evaluate->setType('version');
    $evaluate->evaluate();
    
    $row2['engineVersions'] = serialize($evaluate->getValues());
    $row2['engineVersionsHarmonized'] = serialize($evaluate->getUniqueHarmonizedValues());
    $row2['engineVersionFound'] = $evaluate->getFoundCount();
    $row2['engineVersionFoundUnique'] = $evaluate->getFoundCountUnique();
    $row2['engineVersionMaxSameResultCount'] = $evaluate->getMaxSameResultCount();
    $row2['engineVersionHarmonizedFoundUnique'] = $evaluate->getHarmonizedFoundUnique();
    $row2['engineVersionHarmonizedMaxSameResultCount'] = $evaluate->getHarmonizedMaxSameResultCount();
    
    /*
     * os name
     */
    $evaluate = new ResultsPerUserAgent();
    $evaluate->setValue($row['osName']);
    $evaluate->setType('osName');
    $evaluate->evaluate();
    
    $row2['osNames'] = serialize($evaluate->getValues());
    $row2['osNamesHarmonized'] = serialize($evaluate->getUniqueHarmonizedValues());
    $row2['osNameFound'] = $evaluate->getFoundCount();
    $row2['osNameFoundUnique'] = $evaluate->getFoundCountUnique();
    $row2['osNameMaxSameResultCount'] = $evaluate->getMaxSameResultCount();
    $row2['osNameHarmonizedFoundUnique'] = $evaluate->getHarmonizedFoundUnique();
    $row2['osNameHarmonizedMaxSameResultCount'] = $evaluate->getHarmonizedMaxSameResultCount();
    
    /*
     * os version
     */
    $evaluate = new ResultsPerUserAgent();
    $evaluate->setValue($row['osVersion']);
    $evaluate->setType('version');
    $evaluate->evaluate();
    
    $row2['osVersions'] = serialize($evaluate->getValues());
    $row2['osVersionsHarmonized'] = serialize($evaluate->getUniqueHarmonizedValues());
    $row2['osVersionFound'] = $evaluate->getFoundCount();
    $row2['osVersionFoundUnique'] = $evaluate->getFoundCountUnique();
    $row2['osVersionMaxSameResultCount'] = $evaluate->getMaxSameResultCount();
    $row2['osVersionHarmonizedFoundUnique'] = $evaluate->getHarmonizedFoundUnique();
    $row2['osVersionHarmonizedMaxSameResultCount'] = $evaluate->getHarmonizedMaxSameResultCount();
    
    /*
     * deviceModel
     */
    $evaluate = new ResultsPerUserAgent();
    $evaluate->setValue($row['deviceModel']);
    $evaluate->setType('deviceModel');
    $evaluate->evaluate();
    
    $row2['deviceModels'] = serialize($evaluate->getValues());
    $row2['deviceModelsHarmonized'] = serialize($evaluate->getUniqueHarmonizedValues());
    $row2['deviceModelFound'] = $evaluate->getFoundCount();
    $row2['deviceModelFoundUnique'] = $evaluate->getFoundCountUnique();
    $row2['deviceModelMaxSameResultCount'] = $evaluate->getMaxSameResultCount();
    $row2['deviceModelHarmonizedFoundUnique'] = $evaluate->getHarmonizedFoundUnique();
    $row2['deviceModelHarmonizedMaxSameResultCount'] = $evaluate->getHarmonizedMaxSameResultCount();
    
    /*
     * deviceBrand
     */
    $evaluate = new ResultsPerUserAgent();
    $evaluate->setValue($row['deviceBrand']);
    $evaluate->setType('deviceBrand');
    $evaluate->evaluate();
    
    $row2['deviceBrands'] = serialize($evaluate->getValues());
    $row2['deviceBrandsHarmonized'] = serialize($evaluate->getUniqueHarmonizedValues());
    $row2['deviceBrandFound'] = $evaluate->getFoundCount();
    $row2['deviceBrandFoundUnique'] = $evaluate->getFoundCountUnique();
    $row2['deviceBrandMaxSameResultCount'] = $evaluate->getMaxSameResultCount();
    $row2['deviceBrandHarmonizedFoundUnique'] = $evaluate->getHarmonizedFoundUnique();
    $row2['deviceBrandHarmonizedMaxSameResultCount'] = $evaluate->getHarmonizedMaxSameResultCount();
    
    /*
     * deviceType
     */
    $evaluate = new ResultsPerUserAgent();
    $evaluate->setValue($row['deviceType']);
    $evaluate->setType('deviceType');
    $evaluate->evaluate();
    
    $row2['deviceTypes'] = serialize($evaluate->getValues());
    $row2['deviceTypesHarmonized'] = serialize($evaluate->getUniqueHarmonizedValues());
    $row2['deviceTypeFound'] = $evaluate->getFoundCount();
    $row2['deviceTypeFoundUnique'] = $evaluate->getFoundCountUnique();
    $row2['deviceTypeMaxSameResultCount'] = $evaluate->getMaxSameResultCount();
    $row2['deviceTypeHarmonizedFoundUnique'] = $evaluate->getHarmonizedFoundUnique();
    $row2['deviceTypeHarmonizedMaxSameResultCount'] = $evaluate->getHarmonizedMaxSameResultCount();
    
    /*
     * Detected as mobile
     */
    $row2['asMobileDetectedCount'] = $row['deviceIsMobileCount'];
    
    /*
     * Detected as touch
     */
    $row2['asTouchDetectedCount'] = $row['deviceIsTouchCount'];
    
    /*
     * Detecte as bot
     */
    $row2['asBotDetectedCount'] = $row['isBotCount'];
    
    /*
     * botName
     */
    $evaluate = new ResultsPerUserAgent();
    $evaluate->setValue($row['botName']);
    $evaluate->setType('botName');
    $evaluate->evaluate();
    
    $row2['botNames'] = serialize($evaluate->getValues());
    $row2['botNamesHarmonized'] = serialize($evaluate->getUniqueHarmonizedValues());
    $row2['botNameFound'] = $evaluate->getFoundCount();
    $row2['botNameFoundUnique'] = $evaluate->getFoundCountUnique();
    $row2['botNameMaxSameResultCount'] = $evaluate->getMaxSameResultCount();
    $row2['botNameHarmonizedFoundUnique'] = $evaluate->getHarmonizedFoundUnique();
    $row2['botNameHarmonizedMaxSameResultCount'] = $evaluate->getHarmonizedMaxSameResultCount();
    
    /*
     * botType
     */
    $evaluate = new ResultsPerUserAgent();
    $evaluate->setValue($row['botType']);
    $evaluate->setType('botType');
    $evaluate->evaluate();
    
    $row2['botTypes'] = serialize($evaluate->getValues());
    $row2['botTypesHarmonized'] = serialize($evaluate->getUniqueHarmonizedValues());
    $row2['botTypeFound'] = $evaluate->getFoundCount();
    $row2['botTypeFoundUnique'] = $evaluate->getFoundCountUnique();
    $row2['botTypeMaxSameResultCount'] = $evaluate->getMaxSameResultCount();
    $row2['botTypeHarmonizedFoundUnique'] = $evaluate->getHarmonizedFoundUnique();
    $row2['botTypeHarmonizedMaxSameResultCount'] = $evaluate->getHarmonizedMaxSameResultCount();
    
    return $row2;
}
